<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\DataQualityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DataQualityController extends Controller
{
    public function index(DataQualityService $quality): View
    {
        $report = $quality->audit((int) config('performance.data_quality_sample_limit', 20));
        $quality->storeSnapshot($report, 'web', auth()->id());

        return view('admin.data-quality.index', [
            'report' => $report,
            'snapshots' => $quality->latestSnapshots((int) config('performance.data_quality_snapshot_history', 20)),
        ]);
    }

    public function repair(Request $request, DataQualityService $quality, AuditLogger $audit): RedirectResponse
    {
        $request->validate(['confirm_repair' => ['accepted']]);
        $before = $quality->audit(10);
        $repair = $quality->repairSafe((int) $request->user()->id);
        $after = $quality->audit(10);
        $quality->storeSnapshot($after, 'web-repair', (int) $request->user()->id);

        $audit->log(
            'data_quality.safe_repair',
            'Pokrenuta bezbedna popravka kvaliteta podataka',
            before: ['summary' => $before['summary'], 'score' => $before['score']],
            after: ['summary' => $after['summary'], 'score' => $after['score']],
            metadata: ['repair' => $repair],
            level: ((int) $after['summary']['critical'] > 0 ? 'warning' : 'info'),
        );

        return redirect()->route('admin.data-quality.index')->with(
            'status',
            'Bezbedna popravka je završena. Score: '.$before['score'].' → '.$after['score'].'.',
        );
    }

    public function export(DataQualityService $quality): JsonResponse
    {
        $report = $quality->audit(100);
        $quality->storeSnapshot($report, 'export', auth()->id());

        return response()->json($report, 200, [
            'Content-Disposition' => 'attachment; filename="data-quality-'.now()->format('Ymd-His').'.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
