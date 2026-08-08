<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServicePartSupplierRequest;
use App\Http\Requests\UpdateServicePartSupplierRequest;
use App\Models\ServicePartSupplier;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ServicePartSupplierController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $suppliers = ServicePartSupplier::query()->withCount(['parts', 'purchaseRequests'])
            ->when($q !== '', static function ($query) use ($q): void {
                $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(fn ($nested) => $nested->where('code', 'like', $needle)->orWhere('name', 'like', $needle)->orWhere('contact_person', 'like', $needle));
            })->orderByDesc('is_active')->orderBy('name')->paginate(50)->withQueryString();
        return view('admin.service-parts.suppliers', compact('suppliers', 'q'));
    }

    public function store(StoreServicePartSupplierRequest $request, AuditLogger $audit): RedirectResponse
    {
        $supplier = ServicePartSupplier::query()->create($request->validated() + [
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        $audit->log('service_part_supplier.created', 'Kreiran dobavljač '.$supplier->name, $supplier, null, $supplier->toArray(), null, $request->user());
        return back()->with('status', 'Dobavljač je kreiran.');
    }

    public function update(UpdateServicePartSupplierRequest $request, ServicePartSupplier $supplier, AuditLogger $audit): RedirectResponse
    {
        if (!$request->boolean('is_active') && $supplier->purchaseRequests()->whereIn('status', ['submitted', 'ordered'])->exists()) {
            throw ValidationException::withMessages(['is_active' => 'Dobavljač ima aktivne zahteve za nabavku. Prvo ih završite ili otkažite.']);
        }
        $before = $supplier->toArray();
        $supplier->fill($request->validated());
        $supplier->is_active = $request->boolean('is_active');
        $supplier->updated_by = $request->user()->id;
        $supplier->save();
        $audit->log('service_part_supplier.updated', 'Izmenjen dobavljač '.$supplier->name, $supplier, $before, $supplier->toArray(), null, $request->user());
        return back()->with('status', 'Dobavljač je izmenjen.');
    }
}
