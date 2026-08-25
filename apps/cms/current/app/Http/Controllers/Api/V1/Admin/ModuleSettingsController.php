<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ModuleVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ModuleSettingsController extends Controller
{
    /** @var list<string> */
    private const CORE_MODULES = [
        'Katalog',
        'Porudžbine',
        'Autentifikacija',
        'Korisnici',
        'Podešavanja',
        'Dokumenti',
        'Plaćanja',
    ];

    public function index(Request $request, ModuleVisibilityService $modules): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $this->authorizeSuperAdmin($user);

        return response()->json(['data' => $this->payload($modules)]);
    }

    public function update(
        Request $request,
        ModuleVisibilityService $modules,
        AuditLogger $audit,
    ): JsonResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $this->authorizeSuperAdmin($user);

        $rules = ['modules' => ['required', 'array']];
        foreach (array_keys($modules->definitions()) as $module) {
            $rules['modules.'.$module] = ['required', 'boolean'];
        }
        $request->validate($rules);

        $before = $modules->states();
        $states = [];
        foreach (array_keys($modules->definitions()) as $module) {
            $states[$module] = $request->boolean('modules.'.$module);
        }

        $after = $modules->update($states, (int) $user->getAuthIdentifier());
        $audit->log('settings.modules.updated', 'Moduli sistema', null, $before, $after);

        return response()->json([
            'message' => 'Podešavanja modula su sačuvana.',
            'data' => $this->payload($modules),
        ]);
    }

    /** @return array{modules:array<int,array{key:string,label:string,description:string,enabled:bool}>,core_modules:list<string>,capabilities:array{update:bool}} */
    private function payload(ModuleVisibilityService $modules): array
    {
        return [
            'modules' => $modules->rows(),
            'core_modules' => self::CORE_MODULES,
            'capabilities' => ['update' => true],
        ];
    }

    private function authorizeSuperAdmin(User $user): void
    {
        abort_unless($user->hasRole('superadmin'), 403);
        abort_unless($user->can('system.manage_settings'), 403);
    }
}