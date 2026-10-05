<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderAccessService;
use App\Services\OrderDetailPresenter;
use App\Services\OrderDetailService;
use App\Services\OrderIndexService;
use App\Services\OrderTimelineService;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Throwable;

final class OrderController extends Controller
{
    /** @var list<string> */
    private const STATUSES = ['new', 'processing', 'confirmed', 'shipped', 'completed', 'cancelled'];

    /** @var list<string> */
    private const PAYMENT_STATUSES = ['pending', 'paid', 'refunded', 'cancelled'];

    /** @var list<string> */
    private const SOURCE_SYSTEMS = ['laravel', 'legacy'];

    /** @var list<int> */
    private const PER_PAGE = [20, 40, 50, 100];

    public function index(Request $request, OrderIndexService $orders, OrderAccessService $access): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->can('orders.manage'), 403);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'payment_status' => ['nullable', Rule::in(self::PAYMENT_STATUSES)],
            'source_system' => ['nullable', Rule::in(self::SOURCE_SYSTEMS)],
            'supplier_user_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'attention' => ['nullable', 'string', 'max:40'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE)],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 40);

        try {
            $paginator = $orders->paginate($actor, $validated, $access, $perPage);
            $attention = $orders->attentionCounts($actor, $access);
            $suppliers = $orders->suppliers($actor);
        } catch (Throwable $exception) {
            report($exception);

            return $this->unavailable();
        }

        $payload = [
            'data' => $paginator->getCollection()
                ->map(fn (mixed $order): array => $order instanceof Order ? $this->presentListOrder($order) : [])
                ->filter(static fn (array $row): bool => $row !== [])
                ->values()
                ->all(),
            'pagination' => $this->pagination($paginator),
            'filters' => $this->normalizedFilters($validated, $perPage),
            'filter_options' => [
                'statuses' => self::STATUSES,
                'payment_statuses' => self::PAYMENT_STATUSES,
                'source_systems' => self::SOURCE_SYSTEMS,
                'suppliers' => $this->presentSuppliers($suppliers),
                'attention' => array_values(array_map('strval', array_keys($attention))),
                'per_page' => self::PER_PAGE,
            ],
            'attention' => $attention,
            'capabilities' => [
                'detail' => true,
                'workflow_mutations' => true,
            ],
        ];

        return $this->jsonNoStore($payload);
    }

    public function show(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderDetailService $details,
        OrderDetailPresenter $presenter,
        OrderTimelineService $timeline,
        OrderIndexService $orders,
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->can('orders.manage'), 403);

        $access->authorizeManage($order, $actor);

        try {
            $warnings = $details->prepare($order, true);
            $events = $timeline->build($order, true);
            $suppliers = $orders->suppliers($actor);
            $detail = $presenter->admin($order, $actor, $events, $suppliers, null, $warnings);
            $detail = $this->sanitizeDetail($detail);
        } catch (Throwable $exception) {
            report($exception);

            return $this->unavailable();
        }

        return $this->jsonNoStore([
            'data' => $detail,
            'capabilities' => [
                'read' => true,
                'workflow_mutations' => true,
                'orders_manage' => $actor->can('orders.manage'),
                'internal_notes' => $actor->can('orders.internal_notes'),
                'reassign' => $actor->hasRole('superadmin') && $actor->can('orders.reassign'),
                'payments' => $actor->can('payments.manage'),
                'sale_price_correction' => $actor->hasRole('superadmin')
                    && $actor->can('orders.manage')
                    && $actor->can('payments.manage')
                    && (string) $order->sales_channel === 'direct_sale'
                    && $order->completed_at !== null
                    && (string) $order->status !== 'cancelled'
                    && (string) $order->payment_method !== 'deferred_payment',
                'documents' => $actor->can('invoices.manage'),
                'confirm_delivery' => $actor->can('orders.confirm_delivery'),
                'reopen' => $actor->can('orders.reopen'),
            ],
        ]);
    }

    /** @return array<string,mixed> */
    private function presentListOrder(Order $order): array
    {
        $customer = $order->relationLoaded('user') ? $order->getRelation('user') : null;
        $supplier = $order->relationLoaded('supplier') ? $order->getRelation('supplier') : null;

        return [
            'id' => (int) $order->getKey(),
            'order_number' => (string) $order->getAttribute('order_number'),
            'source_system' => (string) $order->getAttribute('source_system'),
            'sales_channel' => $this->nullableString($order->getAttribute('sales_channel')),
            'status' => (string) $order->getAttribute('status'),
            'is_completed' => $order->getAttribute('completed_at') !== null,
            'payment_status' => $this->nullableString($order->getAttribute('payment_status')),
            'payment_state' => $this->nullableString($order->getAttribute('payment_state')),
            'inventory_state' => $this->nullableString($order->getAttribute('inventory_state')),
            'subtotal_rsd' => (float) ($order->getAttribute('subtotal_rsd') ?? 0),
            'customer' => [
                'name' => $customer instanceof User
                    ? $customer->displayName()
                    : (string) $order->getAttribute('shipping_full_name'),
            ],
            'supplier' => $supplier instanceof User
                ? $this->presentUser($supplier)
                : $this->snapshotSupplier($order),
            'assigned_at' => $this->dateValue($order->getAttribute('assigned_at')),
            'accepted_at' => $this->dateValue($order->getAttribute('accepted_at')),
            'expected_processing_at' => $this->dateValue($order->getAttribute('expected_processing_at')),
            'expected_shipping_at' => $this->dateValue($order->getAttribute('expected_shipping_at')),
            'tracking_number' => $this->nullableString($order->getAttribute('tracking_number')),
            'created_at' => $this->dateValue($order->getAttribute('created_at')),
            'updated_at' => $this->dateValue($order->getAttribute('updated_at')),
        ];
    }

    /** @return array{id:int,name:string,username:string} */
    private function presentUser(User $user): array
    {
        return [
            'id' => (int) $user->getKey(),
            'name' => $user->displayName(),
            'username' => (string) $user->getAttribute('username'),
        ];
    }

    /** @return array{id:int|null,name:string}|null */
    private function snapshotSupplier(Order $order): ?array
    {
        $id = $order->getAttribute('supplier_user_id');
        $name = trim((string) $order->getAttribute('supplier_name_snapshot'));
        if ($id === null && $name === '') {
            return null;
        }

        return [
            'id' => is_numeric($id) ? (int) $id : null,
            'name' => $name,
        ];
    }

    /** @return list<array{id:int,name:string,username:string}> */
    private function presentSuppliers(Collection $suppliers): array
    {
        return $suppliers
            ->filter(static fn (mixed $supplier): bool => $supplier instanceof User)
            ->map(fn (mixed $supplier): array => $this->presentUser($supplier))
            ->values()
            ->all();
    }

    /** @return array<string,int|null> */
    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /** @param array<string,mixed> $validated @return array<string,mixed> */
    private function normalizedFilters(array $validated, int $perPage): array
    {
        return [
            'q' => $this->nullableString($validated['q'] ?? null),
            'status' => $this->nullableString($validated['status'] ?? null),
            'payment_status' => $this->nullableString($validated['payment_status'] ?? null),
            'source_system' => $this->nullableString($validated['source_system'] ?? null),
            'supplier_user_id' => isset($validated['supplier_user_id']) ? (int) $validated['supplier_user_id'] : null,
            'date_from' => $this->nullableString($validated['date_from'] ?? null),
            'date_to' => $this->nullableString($validated['date_to'] ?? null),
            'attention' => $this->nullableString($validated['attention'] ?? null),
            'page' => isset($validated['page']) ? (int) $validated['page'] : 1,
            'per_page' => $perPage,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        return null;
    }

    // MOBILE_V0_8_SHIPMENT_COURIER_DIRECTORY_BATCH11
    // HTTPS courier tracking_url is public operational metadata; private/internal URLs remain stripped.
    private function sanitizeDetail(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null) {
            $normalized = strtolower($key);
            if ($normalized !== 'tracking_url' && (
                $normalized === 'urls'
                || str_ends_with($normalized, '_url')
                || str_ends_with($normalized, '_path')
                || str_ends_with($normalized, '_disk'))) {
                return null;
            }
        }

        if (!is_array($value)) {
            return $value;
        }

        $clean = [];
        foreach ($value as $childKey => $childValue) {
            $name = is_string($childKey) ? $childKey : null;
            if ($name !== null) {
                $normalized = strtolower($name);
                if ($normalized !== 'tracking_url' && (
                    $normalized === 'urls'
                    || str_ends_with($normalized, '_url')
                    || str_ends_with($normalized, '_path')
                    || str_ends_with($normalized, '_disk'))) {
                    continue;
                }
            }
            $clean[$childKey] = $this->sanitizeDetail($childValue, $name);
        }

        return $clean;
    }

    /** @param array<string,mixed> $payload */
    private function jsonNoStore(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function unavailable(): JsonResponse
    {
        return $this->jsonNoStore([
            'message' => 'Administracija porudžbina trenutno nije dostupna.',
            'code' => 'admin_orders_unavailable',
        ], 503);
    }
}
