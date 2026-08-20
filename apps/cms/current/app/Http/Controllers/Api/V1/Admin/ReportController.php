<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ManagementReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ReportController extends Controller
{
    /** @var list<string> */
    private const REPORT_TYPES = [
        'management_summary',
        'profitability',
        'inventory',
        'receivables',
        'after_sales',
    ];

    public function management(Request $request, ManagementReportService $reports): JsonResponse
    {
        $user = $this->viewActor($request);
        $issues = $reports->readinessIssues();

        if ($issues !== []) {
            return response()->json([
                'message' => 'Upravljački izveštaji trenutno nisu dostupni.',
                'code' => 'reports_unavailable',
                'issues' => $issues,
            ], 503);
        }

        try {
            $filters = $reports->normalizeFilters($request->query());
            $reportType = $this->reportType($request);
            $report = $reports->build($user, $filters, $reportType);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Upravljački izveštaj trenutno nije dostupan.',
                'code' => 'report_generation_failed',
            ], 503);
        }

        return response()->json([
            'data' => $report,
            'filters' => $filters,
            'report_type' => $reportType,
            'capabilities' => [
                'export' => $user->can('reports.export'),
                'manage_schedules' => $user->can('reports.manage'),
            ],
        ]);
    }

    public function managementCsv(Request $request, ManagementReportService $reports): Response
    {
        $user = $this->exportActor($request);
        $issues = $reports->readinessIssues();

        if ($issues !== []) {
            return $this->unavailableExport();
        }

        try {
            $filters = $reports->normalizeFilters($request->query());
            $content = $reports->csv($user, $filters, $this->reportType($request));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return $this->unavailableExport();
        }

        return response($content, 200, $this->downloadHeaders(
            'text/csv; charset=UTF-8',
            'attachment; filename="upravljacki-izvestaj-'.now()->format('Ymd-His').'.csv"',
        ));
    }

    public function managementPdf(Request $request, ManagementReportService $reports): Response
    {
        $user = $this->exportActor($request);
        $issues = $reports->readinessIssues();

        if ($issues !== []) {
            return $this->unavailableExport();
        }

        try {
            $filters = $reports->normalizeFilters($request->query());
            $content = $reports->pdf($user, $filters, $this->reportType($request));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return $this->unavailableExport();
        }

        return response($content, 200, $this->downloadHeaders(
            'application/pdf',
            'inline; filename="upravljacki-izvestaj-'.now()->format('Ymd-His').'.pdf"',
        ));
    }

    private function viewActor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless($user->can('reports.view'), 403);

        return $user;
    }

    private function exportActor(Request $request): User
    {
        $user = $this->viewActor($request);
        abort_unless($user->can('reports.export'), 403);

        return $user;
    }

    private function reportType(Request $request): string
    {
        $value = trim((string) $request->query('report_type', 'management_summary'));
        $value = $value !== '' ? $value : 'management_summary';

        if (!in_array($value, self::REPORT_TYPES, true)) {
            throw ValidationException::withMessages([
                'report_type' => 'Nepodržan tip upravljačkog izveštaja.',
            ]);
        }

        return $value;
    }

    /** @return array<string,string> */
    private function downloadHeaders(string $contentType, string $contentDisposition): array
    {
        return [
            'Content-Type' => $contentType,
            'Content-Disposition' => $contentDisposition,
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }

    private function unavailableExport(): Response
    {
        return response(
            'Upravljački izveštaj trenutno nije dostupan.',
            503,
            [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
