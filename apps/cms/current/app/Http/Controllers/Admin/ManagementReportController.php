<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportDelivery;
use App\Models\ReportSchedule;
use App\Models\User;
use App\Services\ManagementReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class ManagementReportController extends Controller
{
    public function index(Request $request, ManagementReportService $reports): Response
    {
        /** @var User $user */
        $user = $request->user();
        $filters = $reports->normalizeFilters($request->all());
        $issues = $reports->readinessIssues();
        $report = $issues === [] ? $reports->build($user, $filters) : null;
        $schedules = collect();
        $deliveries = collect();
        try {
            if (Schema::hasTable('report_schedules')) {
                $schedules = ReportSchedule::query()->with('creator')->when(!$user->hasRole('superadmin'), static fn ($q) => $q->where('created_by', $user->id))->latest('id')->get();
            }
            if (Schema::hasTable('report_deliveries')) {
                $deliveries = ReportDelivery::query()->with('schedule')->when(!$user->hasRole('superadmin'), static fn ($q) => $q->whereHas('schedule', static fn ($s) => $s->where('created_by', $user->id)))->latest('id')->limit(30)->get();
            }
        } catch (Throwable $exception) {
            report($exception);
            $issues[] = 'Rasporedi i istorija slanja trenutno nisu dostupni.';
        }
        $suppliers = $user->hasRole('superadmin') ? User::query()->where('status', 'active')->whereHas('role', static fn ($q) => $q->whereIn('slug', ['admin', 'superadmin']))->with('role')->orderBy('first_name')->orderBy('username')->get() : collect();
        $dimensions = $this->dimensions();
        return response()->view('admin.reports.management', compact('filters', 'issues', 'report', 'schedules', 'deliveries', 'suppliers', 'dimensions'));
    }

    public function csv(Request $request, ManagementReportService $reports): Response
    {
        try {
            $content = $reports->csv($request->user(), $request->all());
            return response($content, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="upravljački-izveštaj-'.now()->format('Ymd-His').'.csv"', 'X-Content-Type-Options' => 'nosniff']);
        } catch (Throwable $exception) {
            report($exception);
            return response('Upravljački CSV izveštaj trenutno nije dostupan. Pokrenite app:management-reports-doctor --repair.', 503, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
    }

    public function pdf(Request $request, ManagementReportService $reports): Response
    {
        try {
            $content = $reports->pdf($request->user(), $request->all());
            return response($content, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="upravljački-izveštaj-'.now()->format('Ymd-His').'.pdf"', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store, max-age=0']);
        } catch (Throwable $exception) {
            report($exception);
            return response('Upravljački PDF izveštaj trenutno nije dostupan. Pokrenite app:management-reports-doctor --repair.', 503, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
    }

    /** @return array<string,array<int,string>> */
    private function dimensions(): array
    {
        $empty = ['brands' => [], 'lines' => [], 'types' => []];
        try {
            if (!Schema::hasTable('order_items')) return $empty;
            foreach (['brand_name_snapshot', 'product_line_name_snapshot', 'product_type_name_snapshot'] as $column) {
                if (!Schema::hasColumn('order_items', $column)) return $empty;
            }
            return [
                'brands' => DB::table('order_items')->whereNotNull('brand_name_snapshot')->where('brand_name_snapshot', '!=', '')->distinct()->orderBy('brand_name_snapshot')->pluck('brand_name_snapshot')->all(),
                'lines' => DB::table('order_items')->whereNotNull('product_line_name_snapshot')->where('product_line_name_snapshot', '!=', '')->distinct()->orderBy('product_line_name_snapshot')->pluck('product_line_name_snapshot')->all(),
                'types' => DB::table('order_items')->whereNotNull('product_type_name_snapshot')->where('product_type_name_snapshot', '!=', '')->distinct()->orderBy('product_type_name_snapshot')->pluck('product_type_name_snapshot')->all(),
            ];
        } catch (Throwable $exception) {
            report($exception);
            return $empty;
        }
    }
}
