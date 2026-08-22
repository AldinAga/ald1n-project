<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\AdminUserService;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

// MOBILE_V0_8_COMPLETE_USER_MANAGEMENT_BATCH12
final class UserController extends Controller
{
    /** @var list<string> */
    private const STATUSES = ['pending', 'active', 'blocked'];

    /** @var list<int> */
    private const PER_PAGE = [20, 50, 100];

    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE)],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 20);
        $query = User::query()->with(['role', 'group'])->latest('id');

        $search = trim((string) ($validated['q'] ?? ''));
        if ($search !== '') {
            $query->where(static function ($nested) use ($search): void {
                $nested->where('username', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%');
            });
        }
        if (isset($validated['status'])) {
            $query->where('status', (string) $validated['status']);
        }

        $paginator = $query->paginate($perPage);
        $activeSuperAdmins = $this->activeSuperAdminCount();

        return $this->jsonNoStore([
            'data' => $paginator->getCollection()
                ->filter(static fn (mixed $user): bool => $user instanceof User)
                ->map(fn (User $user): array => $this->present($user, $activeSuperAdmins))
                ->values()
                ->all(),
            'pagination' => $this->pagination($paginator),
            'filters' => [
                'q' => $search !== '' ? $search : null,
                'status' => $validated['status'] ?? null,
                'page' => isset($validated['page']) ? (int) $validated['page'] : 1,
                'per_page' => $perPage,
            ],
            'filter_options' => $this->optionsPayload(),
            'capabilities' => $this->capabilities($actor),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $actor = $this->actor($request);

        return $this->jsonNoStore([
            'data' => $this->optionsPayload(),
            'capabilities' => $this->capabilities($actor),
        ]);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $actor = $this->actor($request);
        $user->load(['role', 'group']);

        return $this->jsonNoStore([
            'data' => $this->present($user, $this->activeSuperAdminCount()),
            'capabilities' => $this->capabilities($actor),
        ]);
    }

    public function store(
        AdminUserRequest $request,
        AdminUserService $users,
    ): JsonResponse {
        $created = $users->create($request->validated(), $request->user());

        return $this->jsonNoStore([
            'message' => 'Korisnik je dodat.',
            'data' => $this->present($created, $this->activeSuperAdminCount()),
            'password_changed' => true,
            'reauthenticate' => false,
        ], 201);
    }

    public function update(
        AdminUserRequest $request,
        User $user,
        AdminUserService $users,
    ): JsonResponse {
        $result = $users->update($user, $request->validated(), $request->user());

        return $this->jsonNoStore([
            'message' => 'Korisnik je izmenjen.',
            'data' => $this->present($result['user'], $this->activeSuperAdminCount()),
            'password_changed' => $result['password_changed'],
            'reauthenticate' => $result['reauthenticate'],
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->can('system.manage_users'), 403);

        return $actor;
    }

    /** @return array<string,mixed> */
    private function optionsPayload(): array
    {
        return [
            'roles' => Role::query()->orderBy('id')->get(['id', 'name', 'slug'])
                ->map(static fn (Role $role): array => [
                    'id' => (int) $role->id,
                    'name' => (string) $role->name,
                    'slug' => (string) $role->slug,
                ])->values()->all(),
            'groups' => UserGroup::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug', 'status'])
                ->map(static fn (UserGroup $group): array => [
                    'id' => (int) $group->id,
                    'name' => (string) $group->name,
                    'slug' => (string) $group->slug,
                    'status' => (string) $group->status,
                ])->values()->all(),
            'statuses' => [
                ['value' => 'pending', 'label' => 'Na čekanju'],
                ['value' => 'active', 'label' => 'Aktivan'],
                ['value' => 'blocked', 'label' => 'Blokiran'],
            ],
            'per_page' => self::PER_PAGE,
        ];
    }

    /** @return array<string,bool> */
    private function capabilities(User $actor): array
    {
        return [
            'create' => $actor->can('system.manage_users'),
            'update' => $actor->can('system.manage_users'),
            'password_management' => $actor->can('system.manage_users'),
            'delete' => false,
        ];
    }

    /** @return array<string,mixed> */
    private function present(User $user, int $activeSuperAdmins): array
    {
        $user->loadMissing(['role', 'group']);
        $roleSlug = (string) ($user->role?->slug ?? '');
        $groupStatus = (string) ($user->group?->status ?? '');
        $effectiveAccess = in_array($roleSlug, ['admin', 'superadmin'], true)
            ? 'full'
            : ($user->group !== null && $groupStatus === 'active' ? 'group' : 'restricted');

        return [
            'id' => (int) $user->getKey(),
            'username' => (string) $user->username,
            'email' => (string) $user->email,
            'first_name' => $user->first_name !== null ? (string) $user->first_name : null,
            'last_name' => $user->last_name !== null ? (string) $user->last_name : null,
            'name' => $user->displayName(),
            'phone' => $user->phone !== null ? (string) $user->phone : null,
            'status' => (string) $user->status,
            'role' => $user->role !== null ? [
                'id' => (int) $user->role->id,
                'name' => (string) $user->role->name,
                'slug' => (string) $user->role->slug,
            ] : null,
            'group' => $user->group !== null ? [
                'id' => (int) $user->group->id,
                'name' => (string) $user->group->name,
                'slug' => (string) $user->group->slug,
                'status' => (string) $user->group->status,
            ] : null,
            'effective_access' => $effectiveAccess,
            'last_login_at' => $this->dateValue($user->last_login_at),
            'password_changed_at' => $this->dateValue($user->password_changed_at),
            'approved_at' => $this->dateValue($user->approved_at),
            'last_active_superadmin_protected' => $roleSlug === 'superadmin'
                && (string) $user->status === 'active'
                && $activeSuperAdmins <= 1,
        ];
    }

    /** @return array<string,int|null> */
    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    private function activeSuperAdminCount(): int
    {
        return User::query()
            ->where('status', 'active')
            ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
            ->count();
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        return null;
    }

    /** @param array<string,mixed> $payload */
    private function jsonNoStore(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
