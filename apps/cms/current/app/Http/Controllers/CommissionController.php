<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CommissionReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Validation\Rule;

final class CommissionController extends Controller
{
    public function index(Request $request, CommissionReportService $reports): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'paid', 'cancelled'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $issues = $reports->readinessIssues();
        if ($issues !== []) {
            return response(view('commissions.unavailable', ['issues' => $issues, 'isAdmin' => false])->render(), 200);
        }

        try {
            $html = view('commissions.index', [
                'commissions' => $reports->paginateOwn($request->user(), $filters),
                'summary' => $reports->summaryOwn($request->user(), $filters),
                'filters' => $filters,
            ])->render();
            return response($html);
        } catch (Throwable $exception) {
            $incident = substr(hash('sha256', $exception::class.'|'.$exception->getMessage().'|'.microtime(true)), 0, 12);
            try { Log::error('Own commission page render failed.', ['incident' => $incident, 'exception' => $exception]); } catch (Throwable) {}
            return response(view('commissions.unavailable', ['issues' => ['Stranica nije mogla biti renderovana.'], 'incident' => $incident, 'isAdmin' => false])->render(), 200);
        }
    }
}
