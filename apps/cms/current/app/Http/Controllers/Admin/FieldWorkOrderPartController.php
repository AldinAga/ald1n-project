<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFieldWorkOrderPartRequest;
use App\Models\FieldWorkOrder;
use App\Models\FieldWorkOrderPart;
use App\Services\ServicePartsInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class FieldWorkOrderPartController extends Controller
{
    public function store(StoreFieldWorkOrderPartRequest $request, FieldWorkOrder $workOrder, ServicePartsInventoryService $service): RedirectResponse
    {
        $service->addToWorkOrder($workOrder, $request->user(), $request->validated());
        return back()->with('status', 'Rezervni deo je dodat radnom nalogu.');
    }

    public function reserve(Request $request, FieldWorkOrder $workOrder, ServicePartsInventoryService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('service_parts.manage'), 403);
        $service->reserveWorkOrderParts($workOrder, $request->user());
        return back()->with('status', 'Raspoloživi delovi su rezervisani za radni nalog.');
    }

    public function destroy(Request $request, FieldWorkOrder $workOrder, FieldWorkOrderPart $line, ServicePartsInventoryService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('service_parts.manage'), 403);
        $service->removeFromWorkOrder($workOrder, $line, $request->user());
        return back()->with('status', 'Rezervni deo je uklonjen sa radnog naloga.');
    }
}
