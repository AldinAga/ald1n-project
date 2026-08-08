<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelFieldWorkOrderRequest;
use App\Http\Requests\CompleteFieldWorkOrderRequest;
use App\Http\Requests\ScheduleFieldWorkOrderRequest;
use App\Models\FieldServiceTeam;
use App\Models\FieldWorkOrder;
use App\Models\ServicePart;
use App\Services\AfterSalesAccessService;
use App\Services\FieldOperationsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

final class FieldOperationsController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(array_keys(FieldWorkOrder::statusLabels()))],
            'team' => ['nullable', 'integer', 'exists:field_service_teams,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'unscheduled' => ['nullable', Rule::in(['1'])],
        ]);
        $from = filled($filters['from'] ?? null) ? CarbonImmutable::parse((string) $filters['from'])->startOfDay() : now()->startOfWeek()->toImmutable();
        $to = filled($filters['to'] ?? null) ? CarbonImmutable::parse((string) $filters['to'])->endOfDay() : $from->addDays(6)->endOfDay();
        if ($from->diffInDays($to) > 31) {
            throw ValidationException::withMessages(['to' => 'Kalendar može prikazati najviše 32 dana odjednom.']);
        }

        $query = FieldWorkOrder::query()->with(['team', 'action.case.order', 'action.assignee'])->latest('id');
        if (!$request->user()->hasRole('superadmin')) {
            $query->whereHas('action.case', function ($cases) use ($request): void {
                $cases->where(function ($scope) use ($request): void {
                    $scope->where('assigned_to', $request->user()->id)
                        ->orWhereHas('order', static fn ($orders) => $orders->where('supplier_user_id', $request->user()->id));
                });
            });
        }
        $query->when(filled($filters['status'] ?? null), fn ($builder) => $builder->where('status', $filters['status']));
        $query->when(filled($filters['team'] ?? null), fn ($builder) => $builder->where('field_service_team_id', (int) $filters['team']));
        $query->when(($filters['unscheduled'] ?? null) === '1', fn ($builder) => $builder->whereNull('planned_start_at'));
        $query->when(($filters['unscheduled'] ?? null) !== '1', fn ($builder) => $builder->whereBetween('planned_start_at', [$from, $to]));
        $query->when(filled($filters['q'] ?? null), function ($builder) use ($filters): void {
            $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $filters['q'])).'%';
            $builder->where(function ($nested) use ($needle): void {
                $nested->where('work_order_number', 'like', $needle)
                    ->orWhere('customer_name_snapshot', 'like', $needle)
                    ->orWhere('service_address_snapshot', 'like', $needle)
                    ->orWhereHas('action', static fn ($actions) => $actions->where('action_number', 'like', $needle));
            });
        });

        $workOrders = $query->get();
        $days = collect(range(0, $from->diffInDays($to)))->map(static fn (int $offset) => $from->addDays($offset));
        $statsBase = FieldWorkOrder::query();
        if (!$request->user()->hasRole('superadmin')) {
            $statsBase->whereHas('action.case', function ($cases) use ($request): void {
                $cases->where(function ($scope) use ($request): void {
                    $scope->where('assigned_to', $request->user()->id)
                        ->orWhereHas('order', static fn ($orders) => $orders->where('supplier_user_id', $request->user()->id));
                });
            });
        }

        return view('admin.field-operations.index', [
            'workOrders' => $workOrders,
            'days' => $days,
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'teams' => FieldServiceTeam::query()->where('is_active', true)->orderBy('name')->get(),
            'statuses' => FieldWorkOrder::statusLabels(),
            'serviceParts' => Schema::hasTable('service_parts') ? ServicePart::query()->where('is_active', true)->orderBy('name')->get() : collect(),
            'stats' => [
                'today' => (clone $statsBase)->whereDate('planned_start_at', today())->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'overdue' => (clone $statsBase)->whereIn('status', ['planned', 'en_route', 'on_site'])->whereNotNull('planned_end_at')->where('planned_end_at', '<', now())->count(),
                'unscheduled' => (clone $statsBase)->whereIn('status', ['planned'])->whereNull('planned_start_at')->count(),
                'active_teams' => FieldServiceTeam::query()->where('is_active', true)->count(),
            ],
        ]);
    }

    public function show(Request $request, FieldWorkOrder $workOrder, AfterSalesAccessService $access): View
    {
        $relations = ['team', 'action.case.order', 'action.case.items', 'action.items', 'action.assignee', 'attachments.uploader', 'statusActor'];
        if (Schema::hasTable('field_work_order_parts') && Schema::hasTable('service_parts')) $relations[] = 'parts.part';
        $workOrder->load($relations);
        if (!$workOrder->relationLoaded('parts')) $workOrder->setRelation('parts', collect());
        $access->authorizeManage($workOrder->action->case, $request->user());
        return view('admin.field-operations.show', [
            'workOrder' => $workOrder,
            'teams' => FieldServiceTeam::query()->where('is_active', true)->orWhereKey($workOrder->field_service_team_id)->orderBy('name')->get(),
            'statuses' => FieldWorkOrder::statusLabels(),
            'serviceParts' => Schema::hasTable('service_parts') ? ServicePart::query()->where('is_active', true)->orderBy('name')->get() : collect(),
        ]);
    }

    public function schedule(ScheduleFieldWorkOrderRequest $request, FieldWorkOrder $workOrder, FieldOperationsService $service): RedirectResponse
    {
        $service->schedule($workOrder, $request->user(), $request->validated());
        return back()->with('status', 'Termin i terenska ekipa su sačuvani.');
    }

    public function enRoute(Request $request, FieldWorkOrder $workOrder, FieldOperationsService $service): RedirectResponse
    {
        $service->markEnRoute($workOrder, $request->user());
        return back()->with('status', 'Evidentirano je da je ekipa krenula.');
    }

    public function onSite(Request $request, FieldWorkOrder $workOrder, FieldOperationsService $service): RedirectResponse
    {
        $service->markOnSite($workOrder, $request->user());
        return back()->with('status', 'Evidentiran je dolazak ekipe na lokaciju.');
    }

    public function complete(CompleteFieldWorkOrderRequest $request, FieldWorkOrder $workOrder, FieldOperationsService $service): RedirectResponse
    {
        $service->complete($workOrder, $request->user(), $request->validated(), $request->file('attachments', []));
        return back()->with('status', 'Radni nalog i povezana postprodajna radnja su završeni.');
    }

    public function cancel(CancelFieldWorkOrderRequest $request, FieldWorkOrder $workOrder, FieldOperationsService $service): RedirectResponse
    {
        $service->cancel($workOrder, $request->user(), (string) $request->validated('cancellation_reason'));
        return back()->with('status', 'Radni nalog je otkazan.');
    }
}
