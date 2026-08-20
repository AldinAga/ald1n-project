<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SettingsHubController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null && $this->canOpenSettingsHub($user), 403);

        return view('admin.settings.index');
    }

    private function canOpenSettingsHub(object $user): bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
            return true;
        }

        foreach ([
            'system.manage_settings',
            'catalog.manage_taxonomy',
            'receivables.manage',
            'warranties.manage',
            'field_operations.manage',
            'service_parts.procurement',
            'system.manage_users',
        ] as $ability) {
            try {
                if ($user->can($ability)) {
                    return true;
                }
            } catch (\Throwable) {
            }
        }

        return false;
    }
}
