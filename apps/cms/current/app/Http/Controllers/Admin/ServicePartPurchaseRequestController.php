<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelServicePartPurchaseRequest;
use App\Http\Requests\StoreServicePartPurchaseRequest;
use App\Models\ServicePart;
use App\Models\ServicePartPurchaseRequest;
use App\Models\ServicePartSupplier;
use App\Services\ServicePartsInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ServicePartPurchaseRequestController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:190'], 'status' => ['nullable', Rule::in(array_keys(ServicePartPurchaseRequest::statusLabels()))]]);
        $query = ServicePartPurchaseRequest::query()->with(['supplier', 'items.part'])->latest('id');
        $query->when(filled($filters['status'] ?? null), fn ($builder) => $builder->where('status', $filters['status']));
        $query->when(filled($filters['q'] ?? null), function ($builder) use ($filters): void {
            $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $filters['q'])).'%';
            $builder->where(fn ($nested) => $nested->where('request_number', 'like', $needle)->orWhere('supplier_reference', 'like', $needle)->orWhereHas('supplier', fn ($suppliers) => $suppliers->where('name', 'like', $needle)));
        });
        return view('admin.service-parts.purchase-requests', [
            'purchaseRequests' => $query->paginate(40)->withQueryString(),
            'parts' => ServicePart::query()->where('is_active', true)->orderBy('name')->get(),
            'suppliers' => ServicePartSupplier::query()->where('is_active', true)->orderBy('name')->get(),
            'statuses' => ServicePartPurchaseRequest::statusLabels(),
            'filters' => $filters,
        ]);
    }

    public function show(ServicePartPurchaseRequest $purchaseRequest): View
    {
        $purchaseRequest->load(['supplier', 'items.part', 'creator', 'updater']);
        return view('admin.service-parts.purchase-show', ['purchaseRequest' => $purchaseRequest, 'statuses' => ServicePartPurchaseRequest::statusLabels()]);
    }

    public function store(StoreServicePartPurchaseRequest $request, ServicePartsInventoryService $service): RedirectResponse
    {
        $purchase = $service->createPurchaseRequest($request->user(), $request->validated());
        return redirect()->route('admin.service-part-purchases.show', $purchase)->with('status', 'Zahtev za nabavku je kreiran.');
    }

    public function submit(Request $request, ServicePartPurchaseRequest $purchaseRequest, ServicePartsInventoryService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('service_parts.procurement'), 403);
        $service->transitionPurchaseRequest($purchaseRequest, $request->user(), 'submitted');
        return back()->with('status', 'Zahtev je označen kao poslat dobavljaču.');
    }

    public function order(Request $request, ServicePartPurchaseRequest $purchaseRequest, ServicePartsInventoryService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('service_parts.procurement'), 403);
        $service->transitionPurchaseRequest($purchaseRequest, $request->user(), 'ordered');
        return back()->with('status', 'Nabavka je označena kao poručena.');
    }

    public function receive(Request $request, ServicePartPurchaseRequest $purchaseRequest, ServicePartsInventoryService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('service_parts.procurement'), 403);
        $service->transitionPurchaseRequest($purchaseRequest, $request->user(), 'received');
        return back()->with('status', 'Delovi su primljeni i knjiženi na servisni lager.');
    }

    public function cancel(CancelServicePartPurchaseRequest $request, ServicePartPurchaseRequest $purchaseRequest, ServicePartsInventoryService $service): RedirectResponse
    {
        $service->transitionPurchaseRequest($purchaseRequest, $request->user(), 'cancelled', (string) $request->validated('cancellation_reason'));
        return back()->with('status', 'Zahtev za nabavku je otkazan.');
    }
}
