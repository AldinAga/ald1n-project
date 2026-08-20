<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemHealthSnapshot;
use App\Models\User;
use App\Services\SystemHealthService;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class SystemHealthController extends Controller
{
    public function index(Request $request, SystemHealthService $health): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless($user->can('system.health'), 403);

        try {
            $report = $health->inspect();
            $history = $this->history();
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'System Health trenutno nije dostupan.',
                'code' => 'system_health_unavailable',
            ], 503);
        }

        return response()->json([
            'data' => [
                'status' => (string) ($report['status'] ?? 'unknown'),
                'checks' => $this->arrayValue($report['checks'] ?? []),
                'metrics' => $this->mapValue($report['metrics'] ?? []),
                'checked_at' => $this->dateValue($report['checked_at'] ?? null),
            ],
            'history' => $history,
            'capabilities' => [
                'refresh' => true,
                'snapshot' => false,
                'backup' => false,
                'prune' => false,
            ],
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    private function history(): array
    {
        if (!Schema::hasTable('system_health_snapshots')) {
            return [];
        }

        return SystemHealthSnapshot::query()
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(function (SystemHealthSnapshot $snapshot): array {
                return [
                    'id' => (int) $snapshot->getKey(),
                    'status' => (string) $snapshot->getAttribute('status'),
                    'checks' => $this->arrayValue($snapshot->getAttribute('checks_json')),
                    'metrics' => $this->mapValue($snapshot->getAttribute('metrics_json')),
                    'checked_at' => $this->dateValue(
                        $snapshot->getAttribute('checked_at')
                            ?? $snapshot->getAttribute('created_at'),
                    ),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<int,mixed> */
    private function arrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? array_values($decoded) : [];
        }

        return [];
    }

    /** @return array<string,mixed> */
    private function mapValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        return null;
    }
}
