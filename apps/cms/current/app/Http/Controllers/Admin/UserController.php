<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with(['role', 'group'])->latest('id');
        if ($search = trim((string) $request->query('q'))) {
            $query->where(static function ($q) use ($search): void {
                $q->where('username', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%');
            });
        }
        if (in_array($request->query('status'), ['pending', 'active', 'blocked'], true)) {
            $query->where('status', $request->query('status'));
        }

        return view('admin.users.index', [
            'users' => $query->paginate(30)->withQueryString(),
            'roles' => Role::query()->orderBy('id')->get(),
            'groups' => UserGroup::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $data['password_hash'] = Hash::make((string) $data['password']);
        unset($data['password']);
        if ($data['status'] === 'active') {
            $data['approved_by'] = (int) $request->user()->getAuthIdentifier();
            $data['approved_at'] = now();
        }
        $user = User::query()->create($data);
        $audit->log('user.created', 'Korisnik', $user, null, $user->toArray());
        return back()->with('status', 'Korisnik je dodat.');
    }

    public function update(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $before = $user->toArray();
        $data = $this->validated($request, $user);
        if (!empty($data['password'])) {
            $data['password_hash'] = Hash::make((string) $data['password']);
            $data['password_changed_at'] = now();
            $user->tokens()->delete();
        }
        unset($data['password']);

        $newRole = Role::query()->findOrFail((int) $data['role_id']);
        if ($user->hasRole('superadmin') && $newRole->slug !== 'superadmin') {
            abort_if(User::query()->whereHas('role', static fn ($q) => $q->where('slug', 'superadmin'))->count() <= 1, 422, 'Poslednji SuperAdmin ne može biti degradiran.');
        }
        if ($user->status !== 'active' && $data['status'] === 'active') {
            $data['approved_by'] = (int) $request->user()->getAuthIdentifier();
            $data['approved_at'] = now();
        }
        $user->update($data);
        $audit->log('user.updated', 'Korisnik', $user, $before, $user->fresh()->toArray());
        return back()->with('status', 'Korisnik je izmenjen.');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'user_group_id' => ['nullable', 'integer', Rule::exists('user_groups', 'id')],
            'username' => ['required', 'string', 'min:3', 'max:50', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user?->id)],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(['pending', 'active', 'blocked'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:12', 'max:200'],
        ]);
    }
}
