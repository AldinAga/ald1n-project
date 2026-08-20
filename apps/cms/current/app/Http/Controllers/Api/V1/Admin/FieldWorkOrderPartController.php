<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFieldWorkOrderPartRequest;
use App\Models\FieldWorkOrder;
use App\Models\FieldWorkOrderPart;
use App\Models\ServicePart;
use App\Models\User;
use App\Services\ServicePartsInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FieldWorkOrderPartController extends Controller
{
    public function store(
        StoreFieldWorkOrderPartRequest $request,
        FieldWorkOrder $workOrder,
        ServicePartsInventoryService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $line = $service->addToWorkOrder($workOrder, $actor, $request->validated());
        return response()->json([
            'data' => $this->linePayload($line),
            'meta' => ['invalidates' => ['admin.field-operations.list', 'admin.field-operations.detail']],
        ], 201);
    }

    public function reserve(Request $request, FieldWorkOrder $workOrder, ServicePartsInventoryService $service): JsonResponse
    {
        $actor = $this->actor($request);
        $service->reserveWorkOrderParts($workOrder, $actor);
        return response()->json([
            'data' => ['work_order_id' => (int) $workOrder->id, 'reserved' => true],
            'meta' => ['invalidates' => ['admin.field-operations.list', 'admin.field-operations.detail']],
        ]);
    }

    public function destroy(
        Request $request,
        FieldWorkOrder $workOrder,
        FieldWorkOrderPart $line,
        ServicePartsInventoryService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $service->removeFromWorkOrder($workOrder, $line, $actor);
        return response()->json([
            'data' => ['work_order_id' => (int) $workOrder->id, 'removed_line_id' => (int) $line->id],
            'meta' => ['invalidates' => ['admin.field-operations.list', 'admin.field-operations.detail']],
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->hasPermission('service_parts.manage'), 403);
        return $actor;
    }

    /** @return array<string,mixed> */
    private function linePayload(FieldWorkOrderPart $line): array
    {
        $attrs = $line->getAttributes();
        $part = isset($attrs['service_part_id']) ? ServicePart::query()->find((int) $attrs['service_part_id']) : null;
        $partAttrs = $part instanceof ServicePart ? $part->getAttributes() : [];
        return [
            'id' => (int) $line->id,
            'service_part_id' => isset($attrs['service_part_id']) ? (int) $attrs['service_part_id'] : null,
            'service_part' => $part instanceof ServicePart ? [
                'id' => (int) $part->id,
                'sku' => (string) ($partAttrs['sku'] ?? ''),
                'name' => (string) ($partAttrs['name'] ?? ''),
            ] : null,
            'requested_quantity' => isset($attrs['requested_quantity']) && is_numeric($attrs['requested_quantity']) ? (float) $attrs['requested_quantity'] : null,
            'reserved_quantity' => isset($attrs['reserved_quantity']) && is_numeric($attrs['reserved_quantity']) ? (float) $attrs['reserved_quantity'] : null,
            'consumed_quantity' => isset($attrs['consumed_quantity']) && is_numeric($attrs['consumed_quantity']) ? (float) $attrs['consumed_quantity'] : null,
            'supply_mode' => $attrs['supply_mode'] ?? null,
            'notes' => $attrs['notes'] ?? null,
        ];
    }
}
