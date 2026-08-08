<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderCommission;
use App\Models\User;
use App\Services\CommissionReportService;
use App\Services\CommissionWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

final class CommissionController extends Controller
{
    public function index(Request $request, CommissionReportService $reports): Response
    {
        $filters = $this->filters($request);
        $actor = $request->user();
        $issues = $reports->readinessIssues();
        if ($issues !== []) {
            return response(view('commissions.unavailable', ['issues' => $issues, 'isAdmin' => true])->render(), 200);
        }

        try {
            $html = view('admin.commissions.index', [
                'commissions' => $reports->paginateManaged($actor, $filters),
                'summary' => $reports->summaryManaged($actor, $filters),
                'filters' => $filters,
                'users' => $actor->hasRole('superadmin') ? $this->users() : collect(),
                'suppliers' => $actor->hasRole('superadmin') ? $this->suppliers() : collect(),
            ])->render();
            return response($html);
        } catch (Throwable $exception) {
            $incident = substr(hash('sha256', $exception::class.'|'.$exception->getMessage().'|'.microtime(true)), 0, 12);
            try { Log::error('Commission page render failed.', ['incident' => $incident, 'exception' => $exception]); } catch (Throwable) {}
            return response(view('commissions.unavailable', ['issues' => ['Stranica nije mogla biti renderovana.'], 'incident' => $incident, 'isAdmin' => true])->render(), 200);
        }
    }

    public function transition(Request $request, OrderCommission $commission, CommissionWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'paid', 'cancelled'])],
            'note' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', Rule::in(['bank_transfer', 'cash', 'other'])],
            'payment_reference' => ['nullable', 'string', 'max:190'],
        ]);
        $workflow->transition($commission, (string) $data['status'], $request->user(), $data);
        return back()->with('status', 'Status provizije je ažuriran.');
    }

    public function bulkPay(Request $request, CommissionWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validate([
            'commission_ids' => ['required', 'array', 'min:1', 'max:500'],
            'commission_ids.*' => ['integer', 'distinct', 'exists:order_commissions,id'],
            'payment_method' => ['required', Rule::in(['bank_transfer', 'cash', 'other'])],
            'payment_reference' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $batch = $workflow->markPaidBulk(array_map('intval', $data['commission_ids']), $request->user(), $data);
        return back()->with('status', sprintf('Isplaćeno %d provizija u batch-u %s, ukupno %s EUR.', $batch->commission_count, $batch->batch_number, number_format((float) $batch->total_eur, 2, ',', '.')));
    }

    public function csv(Request $request, CommissionReportService $reports): Response
    {
        if ($reports->readinessIssues() !== []) return response('Provizije nisu spremne. Pokreni app:operations-doctor --repair.', 503);
        $content = $reports->csv($request->user(), $this->filters($request));
        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="provizije-'.now()->format('Ymd-His').'.csv"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function pdf(Request $request, CommissionReportService $reports): Response
    {
        if ($reports->readinessIssues() !== []) return response('Provizije nisu spremne. Pokreni app:operations-doctor --repair.', 503);
        $content = $reports->pdf($request->user(), $this->filters($request));
        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="izvestaj-provizija-'.now()->format('Ymd-His').'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    /** @return array<string,mixed> */
    private function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'paid', 'cancelled'])],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'supplier_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);
    }

    /** @return Collection<int,User> */
    private function users(): Collection
    {
        return User::query()->whereHas('commissions')->orderBy('first_name')->orderBy('last_name')->get();
    }

    /** @return Collection<int,User> */
    private function suppliers(): Collection
    {
        return User::query()->where('status', 'active')->whereHas('role', static fn ($role) => $role->whereIn('slug', ['admin', 'superadmin']))->with('role')->orderBy('first_name')->orderBy('last_name')->get();
    }
}
