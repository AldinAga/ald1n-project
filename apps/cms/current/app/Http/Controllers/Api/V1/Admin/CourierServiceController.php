<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierService;
use App\Models\User;
use App\Services\CourierDirectoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

// MOBILE_V0_8_SHIPMENT_COURIER_DIRECTORY_BATCH11
final class CourierServiceController extends Controller
{
    public function index(Request $request, CourierDirectoryService $directory): JsonResponse
    {
        $this->actor($request);
        return $this->json(['data' => $directory->all()->map(fn (CourierService $courier): array => $this->payload($courier))->values()->all()]);
    }

    public function store(Request $request, CourierDirectoryService $directory): JsonResponse
    {
        $actor = $this->actor($request);
        $courier = $directory->create($this->validated($request), $actor);
        return $this->json(['message' => 'Kurirska služba je dodata.', 'data' => $this->payload($courier)], 201);
    }

    public function update(Request $request, CourierService $courier, CourierDirectoryService $directory): JsonResponse
    {
        $actor = $this->actor($request);
        $updated = $directory->update($courier, $this->validated($request, $courier), $actor);
        return $this->json(['message' => 'Kurirska služba je sačuvana.', 'data' => $this->payload($updated)]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->hasRole('superadmin'), 403);
        return $actor;
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, ?CourierService $courier = null): array
    {
        $nameRule = Rule::unique('courier_services', 'name');
        if ($courier instanceof CourierService) $nameRule->ignore($courier->id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', $nameRule],
            'tracking_url' => ['required', 'string', 'url', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['required', 'boolean'],
            'is_default' => ['required', 'boolean'],
        ]);
        if (!str_starts_with(strtolower(trim((string) $data['tracking_url'])), 'https://')) {
            throw ValidationException::withMessages(['tracking_url' => 'Tracking URL mora koristiti HTTPS.']);
        }
        return $data;
    }

    /** @return array<string,mixed> */
    private function payload(CourierService $courier): array
    {
        return [
            'id' => (int) $courier->id,
            'name' => (string) $courier->name,
            'tracking_url' => (string) $courier->tracking_url,
            'sort_order' => (int) $courier->sort_order,
            'is_active' => (bool) $courier->is_active,
            'is_default' => (bool) $courier->is_default,
        ];
    }

    /** @param array<string,mixed> $payload */
    private function json(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status, ['Cache-Control' => 'private, no-store, max-age=0']);
    }
}
