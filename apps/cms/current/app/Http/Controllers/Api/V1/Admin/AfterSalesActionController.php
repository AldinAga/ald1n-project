<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelAfterSalesActionRequest;
use App\Http\Requests\CompleteAfterSalesActionRequest;
use App\Http\Requests\StoreAfterSalesActionRequest;
use App\Models\AfterSalesAction;
use App\Models\AfterSalesCase;
use App\Models\User;
use App\Services\AfterSalesAccessService;
use App\Services\AfterSalesActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AfterSalesActionController extends Controller
{
    public function store(
        StoreAfterSalesActionRequest $request,
        AfterSalesCase $case,
        AfterSalesAccessService $access,
        AfterSalesActionService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $this->authorizeCase($case, $actor, $access);
        $action = $service->create($case, $actor, $request->validated());

        return $this->response($action, 201);
    }

    public function start(
        Request $request,
        AfterSalesCase $case,
        AfterSalesAction $action,
        AfterSalesAccessService $access,
        AfterSalesActionService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $this->authorizeAction($case, $action, $actor, $access);
        $updated = $service->start($case, $action, $actor);

        return $this->response($updated);
    }

    public function complete(
        CompleteAfterSalesActionRequest $request,
        AfterSalesCase $case,
        AfterSalesAction $action,
        AfterSalesAccessService $access,
        AfterSalesActionService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $this->authorizeAction($case, $action, $actor, $access);
        $updated = $service->complete($case, $action, $actor, $request->validated());

        return $this->response($updated);
    }

    public function cancel(
        CancelAfterSalesActionRequest $request,
        AfterSalesCase $case,
        AfterSalesAction $action,
        AfterSalesAccessService $access,
        AfterSalesActionService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $this->authorizeAction($case, $action, $actor, $access);
        $updated = $service->cancel($case, $action, $actor, (string) $request->validated('cancellation_reason'));

        return $this->response($updated);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->hasPermission('after_sales.execute'), 403);
        return $actor;
    }

    private function authorizeCase(AfterSalesCase $case, User $actor, AfterSalesAccessService $access): void
    {
        $case->loadMissing('order');
        $access->authorizeManage($case, $actor);
    }

    private function authorizeAction(
        AfterSalesCase $case,
        AfterSalesAction $action,
        User $actor,
        AfterSalesAccessService $access,
    ): void {
        $this->authorizeCase($case, $actor, $access);
        abort_unless((int) $action->after_sales_case_id === (int) $case->id, 404);
    }

    private function response(AfterSalesAction $action, int $status = 200): JsonResponse
    {
        $action->loadMissing(['assignee', 'payment', 'workOrder.team']);

        return response()->json([
            'data' => [
                'id' => (int) $action->id,
                'after_sales_case_id' => (int) $action->after_sales_case_id,
                'action_number' => (string) $action->action_number,
                'action_type' => (string) $action->action_type,
                'status' => (string) $action->status,
                'reference' => $action->reference,
                'updated_at' => optional($action->updated_at)->toIso8601String(),
            ],
            'meta' => [
                'invalidates' => [
                    'admin.after-sales.list',
                    'admin.after-sales.detail',
                    'admin.field-operations',
                    'admin.orders',
                    'admin.inventory',
                ],
            ],
        ], $status);
    }
}
