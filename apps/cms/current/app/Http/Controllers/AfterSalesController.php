<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAfterSalesCaseRequest;
use App\Http\Requests\StoreAfterSalesMessageRequest;
use App\Models\AfterSalesAction;
use App\Models\AfterSalesCase;
use App\Models\Order;
use App\Services\AfterSalesAccessService;
use App\Services\AfterSalesCaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AfterSalesController extends Controller
{
    public function index(Request $request, AfterSalesAccessService $access): View
    {
        $query = AfterSalesCase::query()->with(['order', 'assignee'])->latest('id');
        $access->applyVisibleScope($query, $request->user());

        return view('after-sales.index', [
            'cases' => $query->paginate(30),
            'labels' => $this->labels(),
        ]);
    }

    public function create(Request $request, Order $order, AfterSalesAccessService $access): View
    {
        abort_unless($access->canCreateForOrder($order, $request->user()), 404);
        $order->load('items');

        return view('after-sales.create', [
            'order' => $order,
            'labels' => $this->labels(),
        ]);
    }

    public function store(StoreAfterSalesCaseRequest $request, Order $order, AfterSalesCaseService $service): RedirectResponse
    {
        $case = $service->create($order, $request->user(), $request->validated(), $request->file('attachments', []));
        return redirect()->route('after-sales.show', $case)->with('status', 'Postprodajni slučaj '.$case->case_number.' je otvoren.');
    }

    public function show(Request $request, AfterSalesCase $case, AfterSalesAccessService $access): View
    {
        $case->load(['order', 'items', 'actions.items', 'actions.assignee', 'actions.workOrder.team', 'actions.workOrder.attachments', 'messages.user', 'messages.attachments', 'attachments.uploader', 'history.actor', 'assignee']);
        $access->authorizeView($case, $request->user());
        $case->setRelation('messages', $case->messages->where('visibility', 'public')->values());

        return view('after-sales.show', [
            'case' => $case,
            'labels' => $this->labels(),
            'actionLabels' => [
                'types' => AfterSalesAction::typeLabels(),
                'statuses' => AfterSalesAction::statusLabels(),
                'dispositions' => AfterSalesAction::dispositionLabels(),
            ],
        ]);
    }

    public function message(StoreAfterSalesMessageRequest $request, AfterSalesCase $case, AfterSalesCaseService $service): RedirectResponse
    {
        $data = $request->validated();
        $service->addMessage($case, $request->user(), (string) $data['body'], 'public', $request->file('attachments', []));
        return back()->with('status', 'Poruka je poslata odgovornom licu.');
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
