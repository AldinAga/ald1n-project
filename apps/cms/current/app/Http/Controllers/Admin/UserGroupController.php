<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Permission;
use App\Models\UserGroup;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class UserGroupController extends Controller
{
    public function index(): View
    {
        return view('admin.user-groups.index', [
            'groups' => UserGroup::query()->with(['permissions', 'categories'])->withCount('users')->orderBy('sort_order')->orderBy('name')->get(),
            'permissions' => Permission::query()->orderBy('sort_order')->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $group = DB::transaction(function () use ($data, $request): UserGroup {
            $group = UserGroup::query()->create($this->groupPayload($data, $request));
            $group->permissions()->sync($data['permissions'] ?? []);
            $group->categories()->sync($data['categories'] ?? []);
            return $group;
        });
        $audit->log('user_group.created', 'Grupa korisnika', $group, null, $group->load(['permissions', 'categories'])->toArray());
        return back()->with('status', 'Grupa korisnika je dodata.');
    }

    public function update(Request $request, UserGroup $userGroup, AuditLogger $audit): RedirectResponse
    {
        $before = $userGroup->load(['permissions', 'categories'])->toArray();
        $data = $this->validated($request, $userGroup);
        DB::transaction(function () use ($userGroup, $data, $request): void {
            $userGroup->update($this->groupPayload($data, $request));
            $userGroup->permissions()->sync($data['permissions'] ?? []);
            $userGroup->categories()->sync($data['categories'] ?? []);
        });
        $audit->log('user_group.updated', 'Grupa korisnika', $userGroup, $before, $userGroup->fresh()->load(['permissions', 'categories'])->toArray());
        return back()->with('status', 'Grupa korisnika je izmenjena.');
    }

    public function destroy(UserGroup $userGroup, AuditLogger $audit): RedirectResponse
    {
        abort_if($userGroup->users()->exists(), 422, 'Grupa se ne može obrisati dok ima korisnike.');
        $before = $userGroup->load(['permissions', 'categories'])->toArray();
        $userGroup->delete();
        $audit->log('user_group.deleted', 'Grupa korisnika', null, $before, null);
        return back()->with('status', 'Grupa korisnika je obrisana.');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, ?UserGroup $group = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('user_groups', 'slug')->ignore($group?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'category_access_mode' => ['required', Rule::in(['all', 'selected', 'none'])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', Rule::exists('categories', 'id')],
        ]);
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function groupPayload(array $data, Request $request): array
    {
        return [
            'name' => trim((string) $data['name']),
            'slug' => trim((string) ($data['slug'] ?? '')) ?: Str::slug((string) $data['name']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'status' => $data['status'],
            'category_access_mode' => $data['category_access_mode'],
            'include_uncategorized' => $request->boolean('include_uncategorized'),
            'sort_order' => (int) $data['sort_order'],
        ];
    }
}
