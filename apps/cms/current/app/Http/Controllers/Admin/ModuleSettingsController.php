<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\ModuleVisibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ModuleSettingsController extends Controller
{
    public function index(Request $request, ModuleVisibilityService $modules): View
    {
        $this->authorizeSuperAdmin($request);

        return view('admin.settings.modules', [
            'modules' => $modules->rows(),
        ]);
    }

    public function update(Request $request, ModuleVisibilityService $modules, AuditLogger $audit): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $rules = ['modules' => ['required', 'array']];
        foreach (array_keys($modules->definitions()) as $module) {
            $rules['modules.'.$module] = ['required', 'in:0,1'];
        }
        $request->validate($rules);

        $before = $modules->states();
        $states = [];
        foreach (array_keys($modules->definitions()) as $module) {
            $states[$module] = (string) $request->input('modules.'.$module, '0') === '1';
        }

        $after = $modules->update($states, (int) $request->user()->getAuthIdentifier());
        $audit->log('settings.modules.updated', 'Moduli sistema', null, $before, $after);

        return back()->with('status', 'Podešavanja modula su sačuvana.');
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('superadmin') === true, 403);
    }
}
