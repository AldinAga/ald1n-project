<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Safe first-stage user retirement. Never physically deletes a user or business record.
 *
 * Deliberately not routed yet: controller/UI integration requires an approved deletion
 * preview, end-to-end tests and the target-specific relational impact gate.
 */
final class UserRetirementService
{
    public function __construct(private readonly AuditLogger $audit)
    {
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
            abort_if($locked->status === 'blocked', 422, 'Nalog je već blokiran. Potrebna je zasebna provera.');

            $orders = (int) DB::table('orders')->where('user_id', (int) $locked->getKey())->count();
            if ($orders > 0) {
                DB::table('orders')
                    ->where('user_id', (int) $locked->getKey())
                    ->update(['user_id' => (int) $recipient->getKey()]);
            }

            // Block login immediately while preserving all historic foreign keys,
            // original commission recipients, audit actors and warranty ownership.
            $locked->forceFill(['status' => 'blocked'])->save();
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
