<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUserGroupRequest;
use App\Models\Category;
use App\Models\Permission;
use App\Models\UserGroup;
use App\Services\UserGroupAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class UserGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeActor($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $query = UserGroup::query()
            ->with(['permissions', 'categories'])
            ->withCount('users')
            ->orderBy('sort_order')
            ->orderBy('name');

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(static function ($groups) use ($search): void {
                $groups->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }
        if (isset($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        return $this->jsonNoStore([
            'data' => $query->get()->map(fn (UserGroup $group): array => $this->present($group))->values()->all(),
            'options' => $this->options(),
            'capabilities' => $this->capabilities(),
        ]);
    }

    public function store(AdminUserGroupRequest $request, UserGroupAdminService $groups): JsonResponse
    {
        $group = $groups->create($request->payload());

        return $this->jsonNoStore([
            'message' => 'Grupa pristupa je kreirana.',
            'data' => $this->present($group),
        ], 201);
    }

    public function update(
        AdminUserGroupRequest $request,
        UserGroup $userGroup,
        UserGroupAdminService $groups,
    ): JsonResponse {
        $group = $groups->update($userGroup, $request->payload());

        return $this->jsonNoStore([
            'message' => 'Grupa pristupa je izmenjena.',
            'data' => $this->present($group),
        ]);
    }

    public function destroy(Request $request, UserGroup $userGroup, UserGroupAdminService $groups): JsonResponse
    {
        $this->authorizeActor($request);
        $groups->delete($userGroup);

        return $this->jsonNoStore(['message' => 'Grupa pristupa je obrisana.']);
    }

    private function authorizeActor(Request $request): void
    {
        abort_unless($request->user()?->can('system.manage_users'), 403);
    }

    /** @return array<string,mixed> */
    private function options(): array
    {
        return [
            'permissions' => Permission::query()->orderBy('sort_order')->orderBy('name')->get()
                ->map(static fn (Permission $permission): array => [
                    'id' => (int) $permission->id,
                    'name' => (string) $permission->name,
                    'slug' => (string) $permission->slug,
                    'description' => $permission->description !== null ? (string) $permission->description : null,
                ])->values()->all(),
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name')->get()
                ->map(static fn (Category $category): array => [
                    'id' => (int) $category->id,
                    'name' => (string) $category->name,
                ])->values()->all(),
            'statuses' => [
                ['value' => 'active', 'label' => 'Aktivna'],
                ['value' => 'inactive', 'label' => 'Neaktivna'],
            ],
            'category_access_modes' => [
                ['value' => 'all', 'label' => 'Sve kategorije'],
                ['value' => 'selected', 'label' => 'Samo izabrane'],
                ['value' => 'none', 'label' => 'Bez kategorija'],
            ],
        ];
    }

    /** @return array<string,bool> */
    private function capabilities(): array
    {
        return [
            'create' => true,
            'update' => true,
            'delete_empty' => true,
        ];
    }

    /** @return array<string,mixed> */
    private function present(UserGroup $group): array
    {
        $group->loadMissing(['permissions', 'categories']);
        if (!isset($group->users_count)) {
            $group->loadCount('users');
        }

        return [
            'id' => (int) $group->id,
            'name' => (string) $group->name,
            'slug' => (string) $group->slug,
            'description' => $group->description !== null ? (string) $group->description : null,
            'status' => (string) $group->status,
            'category_access_mode' => (string) $group->category_access_mode,
            'include_uncategorized' => (bool) $group->include_uncategorized,
            'sort_order' => (int) $group->sort_order,
            'users_count' => (int) $group->users_count,
            'can_delete' => (int) $group->users_count === 0,
            'permissions' => $group->permissions->map(static fn (Permission $permission): array => [
                'id' => (int) $permission->id,
                'name' => (string) $permission->name,
                'slug' => (string) $permission->slug,
                'description' => $permission->description !== null ? (string) $permission->description : null,
            ])->values()->all(),
            'categories' => $group->categories->map(static fn (Category $category): array => [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
            ])->values()->all(),
        ];
    }

    /** @param array<string,mixed> $payload */
    private function jsonNoStore(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
