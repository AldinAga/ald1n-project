<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class LegacyUserRecoveryService
{
    public function __construct(private readonly UserLoginResolver $users) {}

    /** @return array<string,mixed>|null */
    public function findLegacy(string $login): ?array
    {
        $normalized = mb_strtolower(trim($login));
        if ($normalized === '') {
            return null;
        }

        $row = DB::connection('legacy')
            ->table('users')
            ->where(static function ($query) use ($normalized): void {
                $query->whereRaw('LOWER(username) = ?', [$normalized])
                    ->orWhereRaw('LOWER(email) = ?', [$normalized]);
            })
            ->first();

        return $row === null ? null : (array) $row;
    }

    public function recover(string $login): ?User
    {
        $existing = $this->users->find($login);
        if ($existing !== null) {
            return $existing;
        }

        $legacyUser = $this->findLegacy($login);
        if ($legacyUser === null) {
            return null;
        }

        return DB::transaction(function () use ($legacyUser): User {
            $legacyId = (int) ($legacyUser['id'] ?? 0);
            if ($legacyId <= 0) {
                throw new RuntimeException('Legacy korisnik nema validan ID.');
            }

            $idConflict = User::query()->find($legacyId);
            if ($idConflict !== null) {
                throw new RuntimeException('ID legacy korisnika je već zauzet drugim Laravel nalogom.');
            }

            $this->copyById('roles', (int) ($legacyUser['role_id'] ?? 0));

            $groupId = isset($legacyUser['user_group_id']) ? (int) $legacyUser['user_group_id'] : 0;
            if ($groupId > 0) {
                $this->copyById('user_groups', $groupId);
                $this->copyGroupPermissions($groupId);
                $this->copyGroupCategories($groupId);
            }

            $payload = $this->filterForTarget('users', $legacyUser);
            $payload['approved_by'] = null;
            $payload['remember_token'] = null;

            DB::table('users')->insert($payload);

            return User::query()->findOrFail($legacyId);
        }, 3);
    }

    private function copyById(string $table, int $id): void
    {
        if ($id <= 0 || DB::table($table)->where('id', $id)->exists()) {
            return;
        }

        $row = DB::connection('legacy')->table($table)->where('id', $id)->first();
        if ($row === null) {
            throw new RuntimeException("Nedostaje legacy zapis {$table}#{$id}.");
        }

        DB::table($table)->insert($this->filterForTarget($table, (array) $row));
    }

    private function copyGroupPermissions(int $groupId): void
    {
        $rows = DB::connection('legacy')->table('user_group_permissions')->where('group_id', $groupId)->get();
        foreach ($rows as $row) {
            $permissionId = (int) $row->permission_id;
            $this->copyById('permissions', $permissionId);
            DB::table('user_group_permissions')->updateOrInsert(
                ['group_id' => $groupId, 'permission_id' => $permissionId],
                $this->filterForTarget('user_group_permissions', (array) $row),
            );
        }
    }

    private function copyGroupCategories(int $groupId): void
    {
        if (!Schema::connection('legacy')->hasTable('user_group_categories') || !Schema::hasTable('user_group_categories')) {
            return;
        }

        $rows = DB::connection('legacy')->table('user_group_categories')->where('group_id', $groupId)->get();
        foreach ($rows as $row) {
            $categoryId = (int) $row->category_id;
            if (!DB::table('categories')->where('id', $categoryId)->exists()) {
                continue;
            }

            DB::table('user_group_categories')->updateOrInsert(
                ['group_id' => $groupId, 'category_id' => $categoryId],
                $this->filterForTarget('user_group_categories', (array) $row),
            );
        }
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function filterForTarget(string $table, array $row): array
    {
        $columns = array_flip(Schema::getColumnListing($table));
        return array_intersect_key($row, $columns);
    }
}
