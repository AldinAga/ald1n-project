<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderArchiveService;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

final class OrderArchiveController extends Controller
{
    /** @var list<int> */
    private const PER_PAGE = [20, 40, 50, 100];

    public function index(Request $request, OrderArchiveService $archives): JsonResponse
    {
        $actor = $this->actor($request);
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE)],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 40);
        $paginator = $archives->paginateArchived($actor, (string) ($validated['q'] ?? ''), $perPage);

        return $this->jsonNoStore([
            'data' => $paginator->getCollection()
                ->filter(static fn (mixed $order): bool => $order instanceof Order)
                ->map(fn (Order $order): array => $this->presentArchived($order, $actor))
                ->values()
                ->all(),
            'pagination' => $this->pagination($paginator),
            'filters' => [
                'q' => trim((string) ($validated['q'] ?? '')) ?: null,
                'page' => isset($validated['page']) ? (int) $validated['page'] : 1,
                'per_page' => $perPage,
            ],
            'capabilities' => [
                'archive' => true,
                'restore' => true,
                'purge' => $actor->hasRole('superadmin'),
            ],
        ]);
    }

    public function archive(Request $request, Order $order, OrderArchiveService $archives): JsonResponse
    {
        $actor = $this->actor($request);
        $data = $request->validate([
            'archive_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);
        $archived = $archives->archive($order, $actor, (string) $data['archive_reason']);

        return $this->jsonNoStore([
            'message' => 'Porudžbina je arhivirana.',
            'data' => $this->presentArchived($archived->loadMissing(['user', 'supplier']), $actor),
        ]);
    }

    public function restore(Request $request, int $orderId, OrderArchiveService $archives): JsonResponse
    {
        $actor = $this->actor($request);
        $restored = $archives->restoreById($orderId, $actor);

        return $this->jsonNoStore([
            'message' => 'Porudžbina je vraćena iz arhive.',
            'data' => [
                'id' => (int) $restored->getKey(),
                'order_number' => (string) $restored->order_number,
                'archived_at' => null,
            ],
        ]);
    }

    public function purge(Request $request, int $orderId, OrderArchiveService $archives): JsonResponse
    {
        $actor = $this->actor($request);
        $data = $request->validate([
            'confirmation' => ['required', 'string', 'max:100'],
            'purge_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        $archives->purgeById($orderId, $actor, (string) $data['confirmation'], (string) $data['purge_reason']);

        return $this->jsonNoStore([
            'message' => 'Porudžbina je trajno uklonjena iz operativnih i arhivskih prikaza. Poslovna istorija ostaje sačuvana.',
            'data' => ['id' => $orderId, 'purged' => true],
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->can('orders.manage'), 403);
        return $actor;
    }

    /** @return array<string,mixed> */
    private function presentArchived(Order $order, User $actor): array
    {
        $customer = $order->relationLoaded('user') ? $order->getRelation('user') : null;
        $supplier = $order->relationLoaded('supplier') ? $order->getRelation('supplier') : null;

        return [
            'id' => (int) $order->getKey(),
            'order_number' => (string) $order->order_number,
            'status' => (string) $order->status,
            'subtotal_rsd' => (float) ($order->subtotal_rsd ?? 0),
            'completed_at' => $this->dateValue($order->completed_at),
            'archived_at' => $this->dateValue($order->archived_at),
            'archive_reason' => (string) ($order->archive_reason ?? ''),
            'customer' => [
                'name' => $customer instanceof User ? $customer->displayName() : (string) $order->shipping_full_name,
            ],
            'supplier' => $supplier instanceof User
                ? ['id' => (int) $supplier->getKey(), 'name' => $supplier->displayName()]
                : (trim((string) $order->supplier_name_snapshot) !== ''
                    ? ['id' => $order->supplier_user_id !== null ? (int) $order->supplier_user_id : null, 'name' => (string) $order->supplier_name_snapshot]
                    : null),
            'can_restore' => true,
            'can_purge' => $actor->hasRole('superadmin'),
        ];
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

    private function dateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) return $value->format(DATE_ATOM);
        if (is_string($value) && trim($value) !== '') return $value;
        return null;
    }

    /** @param array<string,mixed> $payload */
    private function jsonNoStore(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status, ['Cache-Control' => 'private, no-store, max-age=0']);
    }
}
