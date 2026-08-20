<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\FieldServiceTeam;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FieldServiceTeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->hasPermission('field_operations.manage'), 403);

        $teams = FieldServiceTeam::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(static function (FieldServiceTeam $team): array {
                $attrs = $team->getAttributes();
                return [
                    'id' => (int) $team->id,
                    'code' => (string) ($attrs['code'] ?? ''),
                    'name' => (string) ($attrs['name'] ?? ''),
                    'team_type' => $attrs['team_type'] ?? null,
                    'contact_person' => $attrs['contact_person'] ?? null,
                    'phone' => $attrs['phone'] ?? null,
                    'email' => $attrs['email'] ?? null,
                    'vehicle_registration' => $attrs['vehicle_registration'] ?? null,
                    'service_area' => $attrs['service_area'] ?? null,
                    'is_active' => (bool) ($attrs['is_active'] ?? false),
                    'notes' => $attrs['notes'] ?? null,
                ];
            })
            ->values();

        return response()->json([
            'data' => $teams,
            'capabilities' => [
                'can_manage_work_orders' => true,
                'team_mutations_available_in_mobile_api' => false,
            ],
        ]);
    }
}
