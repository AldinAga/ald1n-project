<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelAfterSalesActionRequest;
use App\Http\Requests\CompleteAfterSalesActionRequest;
use App\Http\Requests\StoreAfterSalesActionRequest;
use App\Models\AfterSalesAction;
use App\Models\AfterSalesCase;
use App\Services\AfterSalesActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AfterSalesActionController extends Controller
{
    public function store(StoreAfterSalesActionRequest $request, AfterSalesCase $case, AfterSalesActionService $service): RedirectResponse
    {
        $action = $service->create($case, $request->user(), $request->validated());
        return back()->with('status', 'Planirana je postprodajna radnja '.$action->action_number.'.');
    }

    public function start(Request $request, AfterSalesCase $case, AfterSalesAction $action, AfterSalesActionService $service): RedirectResponse
    {
        $service->start($case, $action, $request->user());
        return back()->with('status', 'Postprodajna radnja je pokrenuta.');
    }

    public function complete(CompleteAfterSalesActionRequest $request, AfterSalesCase $case, AfterSalesAction $action, AfterSalesActionService $service): RedirectResponse
    {
        $service->complete($case, $action, $request->user(), $request->validated());
        return back()->with('status', 'Postprodajna radnja je izvršena i evidentirana.');
    }

    public function cancel(CancelAfterSalesActionRequest $request, AfterSalesCase $case, AfterSalesAction $action, AfterSalesActionService $service): RedirectResponse
    {
        $service->cancel($case, $action, $request->user(), (string) $request->validated('cancellation_reason'));
        return back()->with('status', 'Postprodajna radnja je otkazana.');
    }
}
