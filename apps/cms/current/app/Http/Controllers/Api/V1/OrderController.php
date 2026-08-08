<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\OrderWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection(
            Order::query()->where('user_id', $request->user()->id)->with(['items', 'commission', 'supplier'])->latest('id')->paginate(30)
        );
    }

    public function store(StoreOrderRequest $request, OrderService $orders): JsonResponse
    {
        $order = $orders->create($request->user(), $request->validated(), (string) $request->validated('idempotency_key'));
        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);
        return new OrderResource($order->load(['items', 'commission', 'supplier']));
    }

    public function cancel(Request $request, Order $order, OrderWorkflowService $workflow): OrderResource
    {
        $validated = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        return new OrderResource($workflow->cancelOwn($order, $request->user(), $validated['note'] ?? null));
    }
}
