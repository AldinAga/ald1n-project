<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteWarrantyMaintenanceRequest;
use App\Http\Requests\ScheduleWarrantyMaintenanceRequest;
use App\Http\Requests\StoreWarrantyRuleRequest;
use App\Http\Requests\UpdateProductWarrantyRequest;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductWarranty;
use App\Models\WarrantyMaintenanceRecord;
use App\Models\WarrantyRule;
use App\Services\AuditLogger;
use App\Services\WarrantyAdminService;
use App\Services\WarrantyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class WarrantyController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(['active', 'expired', 'void'])],
            'maintenance' => ['nullable', Rule::in(['due', 'scheduled', 'overdue'])],
        ]);
        $query = ProductWarranty::query()->with(['order', 'user', 'rule'])->latest('id');
        if (!$request->user()->hasRole('superadmin')) {
            $query->whereHas('order', fn ($orders) => $orders->where('supplier_user_id', $request->user()->id));
        }
        if (filled($filters['q'] ?? null)) {
            $q = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $filters['q'])).'%';
            $query->where(fn ($nested) => $nested->where('warranty_number', 'like', $q)
                ->orWhere('product_name_snapshot', 'like', $q)
                ->orWhere('product_sku_snapshot', 'like', $q)
                ->orWhereHas('order', fn ($orders) => $orders->where('order_number', 'like', $q)));
        }
        if (($filters['status'] ?? null) === 'active') $query->where('status', 'active')->whereDate('expires_at', '>=', today());
        if (($filters['status'] ?? null) === 'expired') $query->where('status', 'active')->whereDate('expires_at', '<', today());
        if (($filters['status'] ?? null) === 'void') $query->where('status', 'void');
        if (($filters['maintenance'] ?? null) === 'due') $query->where('status', 'active')->whereNotNull('next_maintenance_at');
        if (($filters['maintenance'] ?? null) === 'scheduled') $query->whereHas('maintenanceRecords', fn ($records) => $records->where('status', 'scheduled'));
        if (($filters['maintenance'] ?? null) === 'overdue') $query->where('status', 'active')->whereDate('next_maintenance_at', '<', today());

        $statsBase = ProductWarranty::query();
        if (!$request->user()->hasRole('superadmin')) {
            $statsBase->whereHas('order', fn ($orders) => $orders->where('supplier_user_id', $request->user()->id));
        }

        return view('admin.warranties.index', [
            'warranties' => $query->paginate(40)->withQueryString(),
            'rules' => WarrantyRule::query()->with(['category', 'product'])->orderByDesc('priority')->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'products' => Product::query()->whereNull('deleted_at')->orderBy('name')->limit(1000)->get(['id', 'sku', 'name']),
            'filters' => $filters,
            'stats' => [
                'active' => (clone $statsBase)->where('status', 'active')->whereDate('expires_at', '>=', today())->count(),
                'expiring' => (clone $statsBase)->where('status', 'active')->whereBetween('expires_at', [today(), today()->addDays(30)])->count(),
                'maintenance_due' => (clone $statsBase)->where('status', 'active')->whereNotNull('next_maintenance_at')->whereDate('next_maintenance_at', '<=', today()->addDays(7))->count(),
                'void' => (clone $statsBase)->where('status', 'void')->count(),
            ],
        ]);
    }

    public function storeRule(
        StoreWarrantyRuleRequest $request,
        WarrantyAdminService $service,
    ): RedirectResponse {
        $service->createRule(
            $request->user(),
            $request->validated(),
        );

        return back()->with('status', 'Pravilo garancije je kreirano.');
    }

    public function updateRule(
        StoreWarrantyRuleRequest $request,
        WarrantyRule $rule,
        WarrantyAdminService $service,
    ): RedirectResponse {
        $service->updateRule(
            $rule,
            $request->user(),
            $request->validated(),
        );

        return back()->with(
            'status',
            'Pravilo garancije je izmenjeno. Postojeći garantni listovi zadržavaju stare uslove.',
        );
    }

    public function show(Request $request, ProductWarranty $warranty): View
    {
        $this->authorizeWarranty($request, $warranty);
        $warranty->load(['order', 'orderItem', 'user', 'rule', 'voidedBy', 'maintenanceRecords.completer']);
        return view('admin.warranties.show', ['warranty' => $warranty]);
    }

    public function update(UpdateProductWarrantyRequest $request, ProductWarranty $warranty, WarrantyService $service): RedirectResponse
    {
        $this->authorizeWarranty($request, $warranty);
        $service->update($warranty, $request->user(), $request->validated());
        return back()->with('status', 'Garantni list je ažuriran.');
    }

    public function void(Request $request, ProductWarranty $warranty, WarrantyService $service): RedirectResponse
    {
        $this->authorizeWarranty($request, $warranty);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:10000']]);
        $service->void($warranty, $request->user(), (string) $data['reason']);
        return back()->with('status', 'Garancija je poništena uz sačuvan audit trag.');
    }

    public function schedule(ScheduleWarrantyMaintenanceRequest $request, ProductWarranty $warranty, WarrantyMaintenanceRecord $record, WarrantyService $service): RedirectResponse
    {
        $this->authorizeWarranty($request, $warranty);
        abort_unless((int) $record->product_warranty_id === (int) $warranty->id, 404);
        $service->scheduleMaintenance($record, $request->user(), $request->validated());
        return back()->with('status', 'Preventivno održavanje je zakazano.');
    }

    public function complete(CompleteWarrantyMaintenanceRequest $request, ProductWarranty $warranty, WarrantyMaintenanceRecord $record, WarrantyService $service): RedirectResponse
    {
        $this->authorizeWarranty($request, $warranty);
        abort_unless((int) $record->product_warranty_id === (int) $warranty->id, 404);
        $service->completeMaintenance($record, $request->user(), $request->validated());
        return back()->with('status', 'Preventivno održavanje je evidentirano.');
    }

    public function backfill(
        Request $request,
        WarrantyAdminService $service,
    ): RedirectResponse {
        $created = $service->backfill($request->user(), 500);

        return back()->with(
            'status',
            'Generisano je '.$created.' nedostajućih garantnih listova.',
        );
    }

    private function authorizeWarranty(Request $request, ProductWarranty $warranty): void
    {
        if ($request->user()->hasRole('superadmin')) return;
        abort_unless((int) $warranty->order()->value('supplier_user_id') === (int) $request->user()->id, 403);
    }
}
