<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUserGroupRequest;
use App\Models\Category;
use App\Models\Permission;
use App\Models\UserGroup;
use App\Services\UserGroupAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(AdminUserGroupRequest $request, UserGroupAdminService $groups): RedirectResponse
    {
        $groups->create($request->payload());

        return back()->with('status', 'Grupa korisnika je dodata.');
    }

    public function update(
        AdminUserGroupRequest $request,
        UserGroup $userGroup,
        UserGroupAdminService $groups,
    ): RedirectResponse {
        $groups->update($userGroup, $request->payload());

        return back()->with('status', 'Grupa korisnika je izmenjena.');
    }

    public function destroy(Request $request, UserGroup $userGroup, UserGroupAdminService $groups): RedirectResponse
    {
        abort_unless($request->user()?->can('system.manage_users'), 403);
        $groups->delete($userGroup);

        return back()->with('status', 'Grupa korisnika je obrisana.');
    }
}
