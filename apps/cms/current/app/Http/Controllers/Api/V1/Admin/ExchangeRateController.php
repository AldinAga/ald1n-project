<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRateHistory;
use App\Models\User;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

// MOBILE_V0_8_EUR_RSD_EXCHANGE_RATE_BATCH13
final class ExchangeRateController extends Controller
{
    public function index(Request $request, ExchangeRateService $service): JsonResponse
    {
        $this->actor($request);

        return response()->json(['data' => $this->payload($service)], 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function manual(Request $request, ExchangeRateService $service): JsonResponse
    {
        $actor = $this->actor($request);
        $data = $request->validate([
            'rate' => ['required', 'numeric', 'min:50', 'max:250'],
        ]);

        try {
            $service->saveManual((float) $data['rate'], (int) $actor->getAuthIdentifier());
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['rate' => [$exception->getMessage()]],
            ], 422);
        }

        return response()->json([
            'message' => 'Ručni EUR/RSD kurs je sačuvan.',
            'data' => $this->payload($service),
        ]);
    }

    public function automatic(Request $request, ExchangeRateService $service): JsonResponse
    {
        $actor = $this->actor($request);
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'stale_after_hours' => ['required', 'integer', 'min:1', 'max:720'],
        ]);
        $enabled = $request->boolean('enabled');
        $userId = (int) $actor->getAuthIdentifier();

        $service->setAutomatic($enabled, (int) $data['stale_after_hours'], $userId);

        if ($enabled) {
            try {
                $service->updateAutomatically('mobile', $userId, true);
            } catch (RuntimeException $exception) {
                return response()->json([
                    'message' => 'Automatski režim je uključen, ali sinhronizacija trenutno nije uspela: '.$exception->getMessage(),
                    'code' => 'exchange_rate_sync_failed',
                    'data' => $this->payload($service),
                ], 502);
            }
        }

        return response()->json([
            'message' => $enabled
                ? 'Automatsko ažuriranje kursa je uključeno.'
                : 'Ručni režim kursa je uključen.',
            'data' => $this->payload($service),
        ]);
    }

    public function refresh(Request $request, ExchangeRateService $service): JsonResponse
    {
        $actor = $this->actor($request);

        try {
            $result = $service->updateAutomatically(
                'mobile',
                (int) $actor->getAuthIdentifier(),
                true,
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => 'exchange_rate_sync_failed',
                'data' => $this->payload($service),
            ], 502);
        }

        return response()->json([
            'message' => 'Kurs je sinhronizovan na '.number_format((float) $result['rate'], 4, ',', '.').' RSD.',
            'data' => $this->payload($service),
            'sync' => [
                'rate' => (float) $result['rate'],
                'source' => (string) $result['source'],
                'provider_date' => (string) $result['date'],
            ],
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->can('system.manage_settings'), 403);

        return $actor;
    }

    /** @return array<string,mixed> */
    private function payload(ExchangeRateService $service): array
    {
        return [
            'configuration' => $service->configuration(),
            'history' => ExchangeRateHistory::query()
                ->with('updater')
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(static fn (ExchangeRateHistory $row): array => [
                    'id' => (int) $row->getKey(),
                    'old_rate' => $row->old_rate !== null ? (float) $row->old_rate : null,
                    'new_rate' => $row->new_rate !== null ? (float) $row->new_rate : null,
                    'mode' => (string) $row->mode,
                    'provider' => (string) $row->provider,
                    'source' => (string) $row->source,
                    'provider_date' => $row->provider_date?->toDateString(),
                    'triggered_by' => (string) $row->triggered_by,
                    'status' => (string) $row->status,
                    'message' => $row->message !== null ? (string) $row->message : null,
                    'created_at' => $row->created_at?->toISOString(),
                    'updated_by' => $row->updated_by !== null ? (int) $row->updated_by : null,
                    'updater' => $row->updater !== null ? [
                        'id' => (int) $row->updater->getKey(),
                        'name' => $row->updater->displayName(),
                    ] : null,
                ])
                ->values()
                ->all(),
            'capabilities' => [
                'manage' => true,
                'manual' => true,
                'automatic' => true,
                'refresh' => true,
            ],
        ];
    }
}
