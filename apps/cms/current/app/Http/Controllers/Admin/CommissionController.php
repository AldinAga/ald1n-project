<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionAdjustment;
use App\Models\OrderCommission;
use App\Services\IdempotencyService;
use App\Models\User;
use App\Services\CommissionEligibilityService;
use App\Services\CommissionReportService;
use App\Services\CommissionReversalService;
use App\Services\CommissionWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
                'proposedDebits' => $actor->hasRole('superadmin') && Schema::hasTable('commission_adjustments')
                    ? CommissionAdjustment::query()->where('kind', 'debit')->where('status', 'proposed')->with(['commission.order', 'commission.user'])->latest('id')->get()
                    : collect(),
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
            'status' => ['required', Rule::in(['pending', 'approved', 'paid', 'cancelled'])],
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
        $skipped = [];
        $batch = $workflow->markPaidBulk(array_map('intval', $data['commission_ids']), $request->user(), $data, $skipped);
        return back()->with('status', sprintf('Isplaćeno %d provizija u batch-u %s: bruto %s EUR, zaduženja %s EUR, neto za isplatu %s EUR.', $batch->commission_count, $batch->batch_number, number_format((float) $batch->total_eur, 2, ',', '.'), number_format((float) ($batch->debit_eur ?? 0), 2, ',', '.'), number_format((float) ($batch->net_eur ?? $batch->total_eur), 2, ',', '.')))
            ->with('commission_bulk_results', $skipped);
    }

    public function reverse(Request $request, OrderCommission $commission, CommissionReportService $reports, CommissionReversalService $reversal, IdempotencyService $idempotency): RedirectResponse
    {
        $data = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:200'],
            'reason' => ['required', 'string', 'max:1000'],
            'amount_eur' => ['nullable', 'regex:/^\d{1,9}([.,]\d{1,2})?$/'],
            'order_item_id' => ['nullable', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);
        $actor = $request->user();
        $scoped = $reports->managedQuery($actor, [])->whereKey($commission->id)->firstOrFail();
        $adjustment = $idempotency->run(
            $actor,
            'admin.commissions.reverse',
            (string) $data['idempotency_key'],
            ['commission_id' => (int) $scoped->id] + collect($data)->except('idempotency_key')->all(),
            CommissionAdjustment::class,
            fn (): CommissionAdjustment => $reversal->reverse(
                $scoped,
                $actor,
                (string) $data['reason'],
                $data['amount_eur'] ?? null,
                isset($data['order_item_id']) ? (int) $data['order_item_id'] : null,
                isset($data['quantity']) ? (int) $data['quantity'] : null,
            ),
            static fn (int $id): CommissionAdjustment => CommissionAdjustment::query()->findOrFail($id),
        );
        return back()->with('status', ($adjustment->kind === 'debit' ? 'Zaduženje ' : 'Storno ').number_format((float) $adjustment->amount_eur, 2, ',', '.').' EUR je evidentirano.');
    }

    /** Podrazumevana stopa za NOVE porudžbine; postojeće provizije se ne preračunavaju. Samo SuperAdmin. */
    public function updateDefaultPercent(Request $request, \App\Services\SettingsService $settings, \App\Services\AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasRole('superadmin'), 403);
        $data = $request->validate(['percent' => ['required', 'numeric', 'min:0', 'max:100']]);
        $old = $settings->get('commission_default_percent', '10');
        $new = rtrim(rtrim(number_format((float) $data['percent'], 2, '.', ''), '0'), '.');
        $settings->putMany(['commission_default_percent' => $new === '' ? '0' : $new], (int) $request->user()->id);
        $audit->log('commission.default_percent_changed', 'Promenjena podrazumevana stopa provizije', null, ['percent' => $old], ['percent' => $new], user: $request->user());
        return back()->with('status', 'Podrazumevana stopa provizije je '.$new.' %. Važi za nove porudžbine; postojeće provizije se ne preračunavaju.');
    }

    public function confirmDebit(Request $request, CommissionAdjustment $adjustment, CommissionReversalService $reversal): RedirectResponse
    {
        $reversal->confirmDebit($adjustment, $request->user());
        return back()->with('status', 'Zaduženje je potvrđeno.');
    }

    public function rejectDebit(Request $request, CommissionAdjustment $adjustment, CommissionReversalService $reversal): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:500']]);
        $reversal->rejectDebit($adjustment, $request->user(), (string) $data['note']);
        return back()->with('status', 'Predlog zaduženja je odbijen.');
    }

    public function settleCredit(Request $request, string $adjustment, CommissionReversalService $reversal): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->hasRole('superadmin'), 403);
        $data = $request->validate([
            'amount_eur' => ['required', 'string', 'regex:/^\d{1,9}\.\d{2}$/'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $credit = CommissionAdjustment::query()->findOrFail($adjustment);
        $settled = $reversal->settleCredit($credit, $actor, $data['amount_eur'], $data['note'] ?? null);

        return back()->with('status', $settled ? 'Isplata kredita je evidentirana.' : 'Kredit je već označen kao isplaćen.');
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
            'state' => ['nullable', Rule::in(CommissionEligibilityService::STATES)],
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
