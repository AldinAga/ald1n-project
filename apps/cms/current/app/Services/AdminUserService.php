<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// MOBILE_V0_8_COMPLETE_USER_MANAGEMENT_BATCH12
final class AdminUserService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /** @param array<string,mixed> $data */
    public function create(array $data, User $actor): User
    {
        $this->authorize($actor);

        return DB::transaction(function () use ($data, $actor): User {
            $password = (string) ($data['password'] ?? '');
            unset($data['password']);

            $data['password_hash'] = Hash::make($password);
            if ((string) $data['status'] === 'active') {
                $data['approved_by'] = (int) $actor->getAuthIdentifier();
                $data['approved_at'] = now();
            }

            $user = User::query()->create($data);
            $user->load(['role', 'group']);

            $this->audit->log(
                'user.created',
                'Korisnik',
                $user,
                null,
                $user->toArray(),
                user: $actor,
            );

            return $user;
        }, 5);
    }

    /**
     * @param array<string,mixed> $data
     * @return array{user:User,password_changed:bool,reauthenticate:bool}
     */
    public function update(User $target, array $data, User $actor): array
    {
        $this->authorize($actor);

        return DB::transaction(function () use ($target, $data, $actor): array {
            $locked = User::query()
                ->with(['role', 'group'])
                ->lockForUpdate()
                ->findOrFail((int) $target->getKey());

            $before = $locked->toArray();
            $newRole = Role::query()->findOrFail((int) $data['role_id']);
            $nextStatus = (string) $data['status'];

            if ($locked->hasRole('superadmin')) {
                $removesEffectiveSuperAdmin = (string) $newRole->slug !== 'superadmin' || $nextStatus !== 'active';
                $isCurrentlyActive = (string) $locked->status === 'active';
                if ($removesEffectiveSuperAdmin && $isCurrentlyActive) {
                    $activeSuperAdminIds = User::query()
                        ->select('users.id')
                        ->where('status', 'active')
                        ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
                        ->lockForUpdate()
                        ->pluck('users.id');

                    abort_if(
                        $activeSuperAdminIds->count() <= 1,
                        422,
                        'Poslednji aktivni SuperAdmin ne može biti degradiran ili blokiran.',
                    );
                }
            }

            $password = (string) ($data['password'] ?? '');
            $passwordChanged = $password !== '';
            unset($data['password']);

            if ($passwordChanged) {
                $data['password_hash'] = Hash::make($password);
                $data['password_changed_at'] = now();
            }

            if ((string) $locked->status !== 'active' && $nextStatus === 'active') {
                $data['approved_by'] = (int) $actor->getAuthIdentifier();
                $data['approved_at'] = now();
            }

            $locked->update($data);
            if ($passwordChanged) {
                $locked->tokens()->delete();
            }

            $locked->clearResolvedAccessCache();
            $fresh = $locked->fresh(['role', 'group']) ?? $locked;

            $this->audit->log(
                'user.updated',
                'Korisnik',
                $fresh,
                $before,
                $fresh->toArray(),
                [
                    'password_changed' => $passwordChanged,
                    'tokens_revoked' => $passwordChanged,
                ],
                user: $actor,
            );

            return [
                'user' => $fresh,
                'password_changed' => $passwordChanged,
                'reauthenticate' => $passwordChanged
                    && (int) $fresh->getKey() === (int) $actor->getAuthIdentifier(),
            ];
        }, 5);
    }

    private function authorize(User $actor): void
    {
        abort_unless($actor->can('system.manage_users'), 403);
    }
}
