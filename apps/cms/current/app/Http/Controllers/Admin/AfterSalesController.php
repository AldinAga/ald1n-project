<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAfterSalesMessageRequest;
use App\Http\Requests\UpdateAfterSalesCaseRequest;
use App\Models\AfterSalesAction;
use App\Models\AfterSalesCase;
use App\Models\FieldServiceTeam;
use App\Models\User;
use App\Services\AfterSalesAccessService;
use App\Services\AfterSalesCaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class AfterSalesController extends Controller
{
    public function index(Request $request, AfterSalesAccessService $access): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(array_keys($this->labels()['statuses']))],
            'priority' => ['nullable', Rule::in(array_keys($this->labels()['priorities']))],
            'case_type' => ['nullable', Rule::in(array_keys($this->labels()['types']))],
            'overdue' => ['nullable', Rule::in(['1'])],
            'execution_pending' => ['nullable', Rule::in(['1'])],
        ]);

        $query = AfterSalesCase::query()->with(['order', 'opener', 'assignee'])->withCount(['actions as pending_actions_count' => static fn ($actions) => $actions->whereIn('status', ['planned', 'in_progress'])])->latest('id');
        $access->applyVisibleScope($query, $request->user());
        $query->when(filled($filters['q'] ?? null), function ($builder) use ($filters): void {
            $q = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $filters['q'])).'%';
            $builder->where(function ($nested) use ($q): void {
                $nested->where('case_number', 'like', $q)->orWhere('subject', 'like', $q)
                    ->orWhereHas('order', static fn ($orders) => $orders->where('order_number', 'like', $q));
            });
        });
        $query->when(filled($filters['status'] ?? null), fn ($builder) => $builder->where('status', $filters['status']));
        $query->when(filled($filters['priority'] ?? null), fn ($builder) => $builder->where('priority', $filters['priority']));
        $query->when(filled($filters['case_type'] ?? null), fn ($builder) => $builder->where('case_type', $filters['case_type']));
        $query->when(($filters['overdue'] ?? null) === '1', fn ($builder) => $builder->whereNotIn('status', ['resolved', 'rejected', 'closed'])->where('due_at', '<', now()));
        $query->when(($filters['execution_pending'] ?? null) === '1', fn ($builder) => $builder->whereHas('actions', static fn ($actions) => $actions->whereIn('status', ['planned', 'in_progress'])));

        return view('admin.after-sales.index', [
            'cases' => $query->paginate(40)->withQueryString(),
            'labels' => $this->labels(),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, AfterSalesCase $case, AfterSalesAccessService $access): View
    {
        $case->load(['order', 'items', 'messages.user', 'messages.attachments', 'attachments.uploader', 'history.actor', 'opener', 'assignee', 'actions.items.stockMovement', 'actions.workOrder.team', 'actions.workOrder.attachments', 'actions.assignee', 'actions.creator', 'actions.starter', 'actions.completer', 'actions.canceller', 'actions.payment']);
        $access->authorizeManage($case, $request->user());

        return view('admin.after-sales.show', [
            'case' => $case,
            'labels' => $this->labels(),
            'assignees' => User::query()->where('status', 'active')->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))->with('role')->orderBy('first_name')->get(),
            'fieldTeams' => FieldServiceTeam::query()->where('is_active', true)->orderBy('name')->get(),
            'actionLabels' => [
                'types' => AfterSalesAction::typeLabels(),
                'statuses' => AfterSalesAction::statusLabels(),
                'dispositions' => AfterSalesAction::dispositionLabels(),
            ],
        ]);
    }

    public function update(UpdateAfterSalesCaseRequest $request, AfterSalesCase $case, AfterSalesCaseService $service): RedirectResponse
    {
        $service->update($case, $request->user(), $request->validated());
        return back()->with('status', 'Postprodajni slučaj je ažuriran.');
    }

    public function message(StoreAfterSalesMessageRequest $request, AfterSalesCase $case, AfterSalesCaseService $service): RedirectResponse
    {
        $data = $request->validated();
        $service->addMessage($case, $request->user(), (string) $data['body'], (string) ($data['visibility'] ?? 'public'), $request->file('attachments', []));
        return back()->with('status', ($data['visibility'] ?? 'public') === 'internal' ? 'Interna napomena je sačuvana.' : 'Poruka je poslata korisniku.');
    }

    /** @return array<string,array<string,string>> */
    private function labels(): array
    {
        return [
            'types' => ['complaint' => 'Reklamacija', 'return' => 'Povrat', 'service' => 'Servisni zahtev'],
            'priorities' => ['low' => 'Nizak', 'normal' => 'Normalan', 'high' => 'Visok', 'urgent' => 'Hitan'],
            'statuses' => ['open' => 'Otvoren', 'under_review' => 'U obradi', 'awaiting_customer' => 'Čeka odgovor kupca', 'approved' => 'Odobren', 'in_service' => 'Na servisu', 'resolved' => 'Rešen', 'rejected' => 'Odbijen', 'closed' => 'Zatvoren'],
            'resolutions' => ['repair' => 'Popravka', 'replacement' => 'Zamena', 'partial_refund' => 'Delimičan povraćaj novca', 'full_refund' => 'Potpun povraćaj novca', 'return' => 'Povrat robe', 'inspection' => 'Pregled na licu mesta', 'rejected' => 'Zahtev odbijen', 'other' => 'Drugo'],
        ];
    }
}
