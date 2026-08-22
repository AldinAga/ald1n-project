<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\AdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// MOBILE_V0_8_COMPLETE_USER_MANAGEMENT_BATCH12
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

    public function store(AdminUserRequest $request, AdminUserService $users): RedirectResponse
    {
        $users->create($request->validated(), $request->user());

        return back()->with('status', 'Korisnik je dodat.');
    }

    public function update(
        AdminUserRequest $request,
        User $user,
        AdminUserService $users,
    ): RedirectResponse {
        $users->update($user, $request->validated(), $request->user());

        return back()->with('status', 'Korisnik je izmenjen.');
    }
}
