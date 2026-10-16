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
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /**
     * Read-only impact preview. Counts are computed again inside the write transaction.
     *
     * @return array<string,int>
     */
    public function impact(User $target, User $actor): array
    {
        abort_unless($actor->status === 'active' && $actor->hasRole('superadmin'), 403);
        abort_if((int) $actor->getKey() === (int) $target->getKey(), 422);
        abort_if($target->hasRole('superadmin'), 422);

        $id = (int) $target->getKey();

        return [
            'orders' => (int) DB::table('orders')->where('user_id', $id)->count(),
            'commissions' => (int) DB::table('order_commissions')->where('user_id', $id)->count(),
            'warranties' => (int) DB::table('product_warranties')->where('user_id', $id)->count(),
            'conversations' => (int) DB::table('portal_conversations')->where('user_id', $id)->count(),
            'assignments' => (int) DB::table('order_assignments')->where('new_supplier_user_id', $id)->count(),
        ];
    }

    /**
     * @return array{orders:int,recipient_id:int,mode:string}
     */
    public function retire(User $target, User $actor): array
    {
        abort_unless($actor->status === 'active' && $actor->hasRole('superadmin'), 403);
        abort_if((int) $actor->getKey() === (int) $target->getKey(), 422, 'Ne možete obrisati sopstveni nalog.');

        return DB::transaction(function () use ($target, $actor): array {
            $recipient = User::query()
                ->whereKey((int) $actor->getKey())
                ->where('status', 'active')
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($recipient->hasRole('superadmin'), 403);

            $locked = User::query()->lockForUpdate()->findOrFail((int) $target->getKey());
            abort_if($locked->hasRole('superadmin'), 422, 'SuperAdministrator nalog se ne briše ovim postupkom.');
            abort_if($locked->retired_at !== null, 422, 'Nalog je već trajno deaktiviran.');
            abort_if($locked->status === 'blocked', 422, 'Nalog je već blokiran. Potrebna je zasebna provera.');

            // Temporary fail-closed gate until every non-order business relationship
            // has a separately tested retention/reassignment policy. Never allow
            // 'retired' to suggest complete deletion of a user with unreviewed links.
            $impact = $this->impact($locked, $recipient);
            foreach (['commissions', 'warranties', 'conversations', 'assignments'] as $relation) {
                abort_if($impact[$relation] > 0, 422, 'Korisnik ima povezane poslovne evidencije: '.$relation);
            }

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

            // Block login immediately while preserving all historic foreign keys,
            // original commission recipients, audit actors and warranty ownership.
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
                ],
                user: $actor,
            );

            return [
                'orders' => $orders,
                'recipient_id' => (int) $recipient->getKey(),
                'mode' => 'non_destructive_retirement',
            ];
        }, 5);
    }
}
