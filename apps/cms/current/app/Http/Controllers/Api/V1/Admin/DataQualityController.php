<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\DataQualityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DataQualityController extends Controller
{
    public function index(Request $request, DataQualityService $quality): JsonResponse
    {
        abort_unless($request->user()?->can('catalog.audit'), 403);
        $limit = max(1, min(100, (int) $request->query('limit', config('performance.data_quality_sample_limit', 20))));

        return $this->jsonNoStore([
            'data' => [
                'report' => $quality->audit($limit),
                'snapshots' => $quality->latestSnapshots((int) config('performance.data_quality_snapshot_history', 20))->values()->toArray(),
                'capabilities' => ['repair' => true, 'export' => true],
            ],
        ]);
    }

    public function repair(Request $request, DataQualityService $quality, AuditLogger $audit): JsonResponse
    {
        abort_unless($request->user()?->can('catalog.audit'), 403);
        $request->validate(['confirm_repair' => ['accepted']]);
        $before = $quality->audit(10);
        $repair = $quality->repairSafe((int) $request->user()->id);
        $after = $quality->audit(10);
        $quality->storeSnapshot($after, 'mobile-repair', (int) $request->user()->id);
        $audit->log(
            'data_quality.safe_repair',
            'Pokrenuta bezbedna popravka kvaliteta podataka iz Mobile aplikacije',
            before: ['summary' => $before['summary'], 'score' => $before['score']],
            after: ['summary' => $after['summary'], 'score' => $after['score']],
            metadata: ['repair' => $repair, 'surface' => 'mobile'],
            level: ((int) $after['summary']['critical'] > 0 ? 'warning' : 'info'),
        );

        return $this->jsonNoStore([
            'message' => 'Bezbedna popravka je završena.',
            'data' => ['before' => $before, 'after' => $after, 'repair' => $repair],
        ]);
    }

    public function export(Request $request, DataQualityService $quality): JsonResponse
    {
        abort_unless($request->user()?->can('catalog.audit'), 403);
        return response()->json(
            $quality->audit(100),
            200,
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'Content-Disposition' => 'attachment; filename="data-quality-'.now()->format('Ymd-His').'.json"',
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }

    /** @param array<string,mixed> $payload */
    private function jsonNoStore(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status, ['Cache-Control' => 'private, no-store, max-age=0']);
    }
}
