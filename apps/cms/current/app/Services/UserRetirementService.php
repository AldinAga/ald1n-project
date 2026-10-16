<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Safe first-stage user retirement. Never physically deletes a user or business record.
 *
 * Preview and write paths share this service. Release remains gated on MySQL
 * integration tests and complete relationship classification before merge.
 */
final class UserRetirementService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly UserRetirementRelationPolicy $relations,
        private readonly UserRetirementWorkTransferService $work,
    ) {
    }

    /**
     * Read-only impact preview. Counts are computed again inside the write transaction.
     *
     * @return array<string,int>
     */
    public function impact(User $target, User $actor): array
    {
        abort_unless($actor->status === 'active' && $actor->retired_at === null && $actor->hasRole('superadmin'), 403);
        abort_if((int) $actor->getKey() === (int) $target->getKey(), 422);
        abort_if($target->hasRole('superadmin'), 422);

        // Verify EVERY known foreign key and non-FK actor field, never rely
        // on a partial preview or a stale production schema.
        $schema = $this->relations->verifySchema();
        $id = (int) $target->getKey();

        return [
            'orders' => (int) DB::table('orders')->where('user_id', $id)->count(),
            'commissions' => (int) DB::table('order_commissions')->where('user_id', $id)->count(),
            'warranties' => (int) DB::table('product_warranties')->where('user_id', $id)->count(),
            'conversations' => (int) DB::table('portal_conversations')->where('user_id', $id)->count(),
            'assignments' => (int) DB::table('order_assignments')->where('new_supplier_user_id', $id)->count(),
            // History above remains linked to the original user permanently.
            ...$this->work->counts($id),
            'foreign_keys' => $schema['foreign_keys'],
            'unconstrained_history' => $schema['unconstrained_history'],
        ];
    }

    /**
     * @return array<string,int|string>
     */
    public function retire(User $target, User $actor): array
    {
        abort_unless($actor->status === 'active' && $actor->retired_at === null && $actor->hasRole('superadmin'), 403);
        abort_if((int) $actor->getKey() === (int) $target->getKey(), 422, 'Ne možete obrisati sopstveni nalog.');

        return DB::transaction(function () use ($target, $actor): array {
            $recipient = User::query()
                ->whereKey((int) $actor->getKey())
                ->where('status', 'active')
                ->whereNull('retired_at')
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($recipient->hasRole('superadmin'), 403);

            $locked = User::query()->lockForUpdate()->findOrFail((int) $target->getKey());
            abort_if($locked->hasRole('superadmin'), 422, 'SuperAdministrator nalog se ne briše ovim postupkom.');
            abort_if($locked->retired_at !== null, 422, 'Nalog je već trajno deaktiviran.');
            abort_if($locked->status === 'blocked', 422, 'Nalog je već blokiran. Potrebna je zasebna provera.');

            // 121 FK + 7 unconstrained columns are classified and schema-checked.
            // Commission ownership, warranties, conversations, assignments and
            // audit actors are historical. They remain on this retained user row.
            $impact = $this->impact($locked, $recipient);

            // A business entity is never deleted by cascading FK effects because
            // the user record survives. Keep original attribution in an append-only
            // owner-link history before reassigning the operational order owner.
            // Lock each source order; rollback includes histories if a later step fails.
            $orderIds = DB::table('orders')
                ->where('user_id', (int) $locked->getKey())
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            $orders = $orderIds->count();
            abort_if($orders !== $impact['orders'], 409, 'Lista porudžbina se promenila. Ponovite pregled.');

            if ($orders > 0) {
                $at = now();
                foreach ($orderIds->chunk(300) as $chunk) {
                    $rows = [];
                    foreach ($chunk as $orderId) {
                        $rows[] = [
                            'order_id' => (int) $orderId,
                            'from_user_id' => (int) $locked->getKey(),
                            'to_user_id' => (int) $recipient->getKey(),
                            'changed_by' => (int) $recipient->getKey(),
                            'reason' => 'Bezbedno povlačenje korisničkog naloga (user.retired)',
                            'created_at' => $at,
                        ];
                    }

                    DB::table('portal_order_link_history')->insert($rows);
                }

                // DB uniqueness/FK failures MUST abort the entire transaction.
                $affected = DB::table('orders')
                    ->where('user_id', (int) $locked->getKey())
                    ->whereIn('id', $orderIds->all())
                    ->update(['user_id' => (int) $recipient->getKey()]);
                abort_if($affected !== $orders, 409, 'Prenos porudžbina nije potpun.');
            }

            // Move ONLY active operational assignments and append durable audit
            // records in this same transaction; terminal assignment history stays.
            $transfers = $this->work->transfer($locked, $recipient, $impact);

            // Block login immediately while retaining the original user row,
            // financial ownership, warranties, CRM and historical actor IDs.
            $locked->forceFill(['status' => 'blocked', 'retired_at' => now(), 'remember_token' => null])->save();
            DB::table('user_login_sessions')
                ->where('user_id', (int) $locked->getKey())
                ->whereNull('revoked_at')
                ->whereNull('logged_out_at')
                ->update(['revoked_at' => now(), 'revoked_by' => (int) $recipient->getKey()]);
            // Preserve device records for security history; neutralize every push
            // destination and revoke device access before deleting bearer tokens.
            DB::table('mobile_devices')
                ->where('user_id', (int) $locked->getKey())
                ->update([
                    'revoked_at' => now(),
                    'notifications_enabled' => false,
                    'push_token' => null,
                    'push_token_hash' => null,
                    'updated_at' => now(),
                ]);
            $locked->tokens()->delete();

            // Revoke outstanding one-use recovery/activation credentials.
            // Accepted invitations remain historic evidence.
            DB::table('user_activation_tokens')->where('user_id', (int) $locked->getKey())
                ->whereNull('accepted_at')->delete();
            DB::table('password_reset_tokens')->where('user_id', (int) $locked->getKey())
                ->whereNull('used_at')->delete();
            DB::table('restore_credentials')->where('user_id', (int) $locked->getKey())->delete();

            // Prevent queued pushes to the retired identity. Retain outbox
            // rows as evidence of an attempted notification.
            DB::table('mobile_push_outbox')
                ->where('user_id', (int) $locked->getKey())
                ->where('status', 'pending')
                ->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'last_error' => 'Nalog je trajno deaktiviran; push nije poslat.',
                    'updated_at' => now(),
                ]);

            $this->audit->log(
                'user.retired',
                'Korisnik',
                $locked,
                ['status' => $target->status],
                ['status' => 'blocked'],
                [
                    'mode' => 'non_destructive_retirement',
                    'transferred_orders' => $orders,
                    'recipient_user_id' => (int) $recipient->getKey(),
                    'preserved_commissions' => $impact['commissions'],
                    'preserved_warranties' => $impact['warranties'],
                    'preserved_conversations' => $impact['conversations'],
                    'preserved_order_assignments' => $impact['assignments'],
                    'schema_foreign_keys' => $impact['foreign_keys'],
                    'schema_unconstrained_history' => $impact['unconstrained_history'],
                    'active_work_transfers' => $transfers,
                ],
                user: $actor,
            );

            return [
                'orders' => $orders,
                'recipient_id' => (int) $recipient->getKey(),
                'mode' => 'non_destructive_retirement',
                ...$transfers,
            ];
        }, 5);
    }
}
