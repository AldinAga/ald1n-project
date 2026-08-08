<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustServicePartStockRequest;
use App\Http\Requests\StoreServicePartRequest;
use App\Http\Requests\UpdateServicePartRequest;
use App\Models\ServicePart;
use App\Models\ServicePartMovement;
use App\Models\ServicePartSupplier;
use App\Services\AuditLogger;
use App\Services\ServicePartsInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ServicePartController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');
        $parts = ServicePart::query()->with('preferredSupplier')
            ->when($q !== '', static function ($query) use ($q): void {
                $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(fn ($nested) => $nested->where('sku', 'like', $needle)->orWhere('name', 'like', $needle));
            })
            ->when($filter === 'low', static fn ($query) => $query->whereRaw('(stock_quantity - reserved_quantity) <= minimum_quantity'))
            ->when($filter === 'available', static fn ($query) => $query->whereRaw('stock_quantity - reserved_quantity > 0'))
            ->when($filter === 'inactive', static fn ($query) => $query->where('is_active', false))
            ->when($filter !== 'inactive', static fn ($query) => $query->where('is_active', true))
            ->orderBy('name')->paginate(50)->withQueryString();

        $movements = ServicePartMovement::query()->with(['part', 'workOrder'])->latest('id')->limit(30)->get();
        $base = ServicePart::query()->where('is_active', true);
        return view('admin.service-parts.index', [
            'parts' => $parts,
            'suppliers' => ServicePartSupplier::query()->where('is_active', true)->orderBy('name')->get(),
            'movements' => $movements,
            'q' => $q,
            'filter' => $filter,
            'stats' => [
                'active' => (clone $base)->count(),
                'low' => (clone $base)->whereRaw('(stock_quantity - reserved_quantity) <= minimum_quantity')->count(),
                'reserved_lines' => (clone $base)->where('reserved_quantity', '>', 0)->count(),
                'value' => (float) (clone $base)->selectRaw('COALESCE(SUM(stock_quantity * average_cost_rsd),0) AS total')->value('total'),
            ],
        ]);
    }

    public function store(StoreServicePartRequest $request, ServicePartsInventoryService $service): RedirectResponse
    {
        $service->createPart($request->user(), $request->validated() + ['is_active' => $request->boolean('is_active', true)]);
        return back()->with('status', 'Rezervni deo je kreiran.');
    }

    public function update(UpdateServicePartRequest $request, ServicePart $part, AuditLogger $audit): RedirectResponse
    {
        $before = $part->toArray();
        $part->fill($request->validated());
        $part->is_active = $request->boolean('is_active');
        $part->updated_by = $request->user()->id;
        $part->save();
        $audit->log('service_part.updated', 'Izmenjen servisni deo '.$part->sku, $part, $before, $part->toArray(), null, $request->user());
        return back()->with('status', 'Rezervni deo je izmenjen.');
    }

    public function adjust(AdjustServicePartStockRequest $request, ServicePart $part, ServicePartsInventoryService $service): RedirectResponse
    {
        $data = $request->validated();
        $service->adjust($part, $request->user(), (float) $data['quantity_change'], (string) $data['note'], (string) $data['idempotency_key']);
        return back()->with('status', 'Servisni lager je korigovan.');
    }
}
