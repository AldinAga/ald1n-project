<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\UserGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class UserGroupAdminService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): UserGroup
    {
        $group = DB::transaction(function () use ($data): UserGroup {
            $group = UserGroup::query()->create($this->groupPayload($data));
            $group->permissions()->sync($data['permissions'] ?? []);
            $group->categories()->sync($data['categories'] ?? []);

            return $group;
        }, 3);

        $this->audit->log(
            'user_group.created',
            'Grupa korisnika',
            $group,
            null,
            $group->load(['permissions', 'categories'])->toArray(),
        );

        return $group->fresh()->load(['permissions', 'categories'])->loadCount('users');
    }

    /** @param array<string,mixed> $data */
    public function update(UserGroup $group, array $data): UserGroup
    {
        $before = $group->load(['permissions', 'categories'])->toArray();

        DB::transaction(function () use ($group, $data): void {
            $group->update($this->groupPayload($data));
            $group->permissions()->sync($data['permissions'] ?? []);
            $group->categories()->sync($data['categories'] ?? []);
        }, 3);

        $fresh = $group->fresh()->load(['permissions', 'categories'])->loadCount('users');
        $this->audit->log('user_group.updated', 'Grupa korisnika', $fresh, $before, $fresh->toArray());

        return $fresh;
    }

    public function delete(UserGroup $group): void
    {
        abort_if($group->users()->exists(), 422, 'Grupa se ne može obrisati dok ima korisnike.');
        $before = $group->load(['permissions', 'categories'])->toArray();
        $group->delete();
        $this->audit->log('user_group.deleted', 'Grupa korisnika', null, $before, null);
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function groupPayload(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'slug' => trim((string) ($data['slug'] ?? '')) ?: Str::slug((string) $data['name']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'status' => (string) $data['status'],
            'category_access_mode' => (string) $data['category_access_mode'],
            'include_uncategorized' => (bool) ($data['include_uncategorized'] ?? false),
            'sort_order' => (int) $data['sort_order'],
        ];
    }
}
