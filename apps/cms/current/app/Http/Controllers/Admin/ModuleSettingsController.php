<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\ModuleVisibilityService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ModuleSettingsController extends Controller
{
    public function index(Request $request, ModuleVisibilityService $modules, SettingsService $settings): View
    {
        $this->authorizeSuperAdmin($request);

        return view('admin.settings.modules', [
            'modules' => $modules->rows(),
            'confirmedShipDays' => (int) $settings->get('dashboard_confirmed_ship_days', '2'),
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

    /** Operational thresholds of the dashboard (SuperAdmin only): days a confirmed order may wait for shipping, 1-30. */
    public function updateOperations(Request $request, SettingsService $settings, AuditLogger $audit): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $request->validate(['dashboard_confirmed_ship_days' => ['required', 'integer', 'min:1', 'max:30']]);
        $before = ['dashboard_confirmed_ship_days' => $settings->get('dashboard_confirmed_ship_days')];
        $settings->putMany(['dashboard_confirmed_ship_days' => (string) (int) $data['dashboard_confirmed_ship_days']], (int) $request->user()->getAuthIdentifier());
        $audit->log('settings.operations.updated', 'Operativni pragovi dashboarda', null, $before, ['dashboard_confirmed_ship_days' => (string) (int) $data['dashboard_confirmed_ship_days']]);

        return back()->with('status', 'Operativni prag je sačuvan.');
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('superadmin') === true, 403);
    }
}
