<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SubagentSalesReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sales by subagent. Author: Ald1n <https://ald1n.com>
 *
 * Access (owner decision): only superadmin (all orders) and admin (orders they supply), the same
 * permission as the management report (reports.view). Subagents get 403 on the page, the
 * drill-down and the CSV. Enforced by route middleware and again here; the CSV also needs reports.export.
 */
final class SubagentSalesReportController extends Controller
{
    public function index(Request $request, SubagentSalesReportService $reports): Response
    {
        $user = $this->actor($request);
        abort_unless($user->can('reports.view'), 403);

        $issues = $reports->readinessIssues();
        $input = $request->query();
        // Validation only needs the lists of the filters the request actually sends.
        $options = $issues === [] ? $reports->options($user, $reports->rawPeriod($input), $reports->neededLists($input)) : ['subagents' => [], 'brands' => [], 'types' => []];
        $validator = $reports->validator($input, $options);
        $failed = $validator->fails();
        $filters = $failed ? $reports->defaults() : $reports->filters($validator->validated());
        if (!$failed && $issues === []) {
            // Valid request: now load the lists the form shows, for the selected period only.
            $options = $reports->options($user, $filters, ['subagents', 'brands', 'types']);
        }
        $report = ($issues === [] && !$failed) ? $reports->build($user, $filters) : null;
        $query = $reports->queryParams($filters);
        if ($report !== null && $report['orders'] !== null) {
            $report['orders']->withPath(route('admin.reports.subagents'))->appends($query);
        }

        $view = view('admin.reports.subagent-sales', [
            'filters' => $filters,
            'query' => $query,
            'options' => $options,
            'issues' => $issues,
            'report' => $report,
            'canExport' => $user->can('reports.export'),
            'groups' => SubagentSalesReportService::GROUPS,
            'periods' => SubagentSalesReportService::PERIODS,
            'scopes' => SubagentSalesReportService::SCOPES,
            'channels' => SubagentSalesReportService::CHANNELS,
            'payments' => SubagentSalesReportService::PAYMENTS,
        ]);
        if ($failed) {
            $view->withErrors($validator);
        }

        return response($view->render(), $failed ? 422 : 200, ['Cache-Control' => 'private, no-store, max-age=0']);
    }

    public function csv(Request $request, SubagentSalesReportService $reports): Response|StreamedResponse
    {
        $user = $this->actor($request);
        abort_unless($user->can('reports.view') && $user->can('reports.export'), 403);

        if ($reports->readinessIssues() !== []) {
            return response('Izveštaj prodaje po subagentima trenutno nije dostupan.', 503, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
        $input = $request->query();
        $options = $reports->options($user, $reports->rawPeriod($input), $reports->neededLists($input));
        $validator = $reports->validator($input, $options);
        if ($validator->fails()) {
            return response($validator->errors()->first(), 422, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
        $table = $reports->csvTable($reports->build($user, $reports->filters($validator->validated()), true));

        return response()->streamDownload(static function () use ($table): void {
            $out = fopen('php://output', 'wb');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $table['header'], ';', '"', '');
            foreach ($table['rows'] as $row) {
                fputcsv($out, $row, ';', '"', '');
            }
            fclose($out);
        }, 'prodaja-po-subagentima-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    /** Operators only: a custom group may grant reports.view to a subagent, which still must not open this report. */
    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasRole('admin', 'superadmin'), 403);

        return $user;
    }
}
