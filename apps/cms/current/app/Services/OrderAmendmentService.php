<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

final class OrderAmendmentService
{
    public function __construct(
        private readonly IdempotencyService $idempotency,
        private readonly CatalogAccessService $catalogAccess,
        private readonly OrderVersionService $versions,
        private readonly OrderService $orders,
        private readonly OrderDocumentService $documents,
        private readonly OrderPaymentService $payments,
        private readonly ReceivablesService $receivables,
        private readonly CommissionWorkflowService $commissions,
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
        private readonly OrderEmailOutboxService $emails,
    ) {}

    public function canAmend(Order $order, User $actor): bool
    {
        return $this->versions->canCustomerAmend($order, $actor);
    }

    /** @param array<string,mixed> $data */
    public function amend(Order $order, User $actor, array $data, string $idempotencyKey): Order
    {
        if ((int) $order->user_id !== (int) $actor->id) abort(404);
        $data['items'] = $this->normalizeItems((array) ($data['items'] ?? []));
        if ($data['items'] === []) {
            throw ValidationException::withMessages(['items' => 'Porudžbina mora imati najmanje jednu stavku.']);
        }

        /** @var Order $updated */
        $updated = $this->idempotency->run(
            $actor,
            'order.amend:'.(int) $order->id,
            $idempotencyKey,
            $data,
            Order::class,
            fn (): Order => $this->amendLocked($order, $actor, $data, $idempotencyKey),
            static fn (int $id): Order => Order::query()->findOrFail($id),
        );

        return $updated->load(['items.product', 'commission', 'supplier', 'shipment']);
    }

    /** @param array<string,mixed> $data */
    private function amendLocked(Order $order, User $actor, array $data, string $idempotencyKey): Order
    {
        /** @var Order $locked */
        $locked = Order::query()
            ->with(['items', 'commission', 'shipment', 'supplier'])
            ->lockForUpdate()
            ->findOrFail($order->id);

        if ((int) $locked->user_id !== (int) $actor->id) abort(404);
        if (!$this->canAmend($locked, $actor)) {
            throw ValidationException::withMessages([
                'order' => 'Porudžbina više ne može da se menja jer je poslata, završena, otkazana ili ne pripada korisničkom Laravel toku.',
            ]);
        }
        $this->versions->assertFresh($locked, (string) ($data['expected_edit_token'] ?? ''));

        $requested = collect($data['items'])->keyBy(static fn (array $item): int => (int) $item['product_id']);
        /** @var Collection<int,OrderItem> $existingItems */
        $existingItems = $locked->items;
        $existingByProduct = collect();
        foreach ($existingItems as $existing) {
            if ($existing->product_id === null) {
                throw ValidationException::withMessages(['items' => 'Porudžbina sadrži istorijsku stavku bez aktivnog proizvoda. Za izmenu je potrebna SuperAdmin korekcija.']);
            }
            $productId = (int) $existing->product_id;
            if ($existingByProduct->has($productId)) {
                throw ValidationException::withMessages(['items' => 'Porudžbina sadrži duplirane istorijske stavke i mora se prvo administrativno uskladiti.']);
            }
            $existingByProduct->put($productId, $existing);
        }

        $productIds = array_values(array_unique(array_merge(
            $existingByProduct->keys()->map('intval')->all(),
            $requested->keys()->map('intval')->all(),
        )));
        sort($productIds, SORT_NUMERIC);

        /** @var Collection<int,Product> $products */
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->with(['brand', 'line', 'type'])
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($existingByProduct as $productId => $existing) {
            if (!$products->has((int) $productId)) {
                throw ValidationException::withMessages(['items' => 'Jedna istorijska stavka više nema proizvod u katalogu. Za izmenu je potrebna SuperAdmin korekcija.']);
            }
        }

        $deltas = [];
        $itemsChanged = false;
        foreach ($productIds as $productId) {
            $oldQuantity = $existingByProduct->has($productId) ? (int) $existingByProduct->get($productId)->quantity : 0;
            $newQuantity = $requested->has($productId) ? (int) $requested->get($productId)['quantity'] : 0;
            $delta = $newQuantity - $oldQuantity;
            $deltas[$productId] = $delta;
            if ($delta !== 0) $itemsChanged = true;
        }

        if ($itemsChanged && $locked->commission !== null && in_array((string) $locked->commission->status, ['paid', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'items' => 'Stavke se ne mogu menjati preko već isplaćene ili stornirane provizije. Obrati se SuperAdministratoru za kontrolisanu korekciju.',
            ]);
        }

        $positiveIds = array_values(array_map('intval', array_keys(array_filter($deltas, static fn (int $delta): bool => $delta > 0))));
        if ($positiveIds !== []) {
            $visibleQuery = Product::query()->publiclyVisible()->whereIn('id', $positiveIds);
            $this->catalogAccess->apply($visibleQuery, $actor);
            $visibleIds = $visibleQuery->pluck('id')->map('intval')->all();
            foreach ($positiveIds as $productId) {
                if (!in_array($productId, $visibleIds, true)) {
                    $existingQuantity = $existingByProduct->has($productId) ? (int) $existingByProduct->get($productId)->quantity : 0;
                    throw ValidationException::withMessages([
                        'items' => $existingQuantity > 0
                            ? 'Arhivirana ili nedostupna stavka može ostati ista, smanjiti se ili ukloniti, ali se njena količina ne može povećati.'
                            : 'Jedan ili više novih artikala više nisu dostupni za poručivanje.',
                    ]);
                }
                /** @var Product $product */
                $product = $products->get($productId);
                if ((int) $product->stock_quantity < $deltas[$productId]) {
                    throw ValidationException::withMessages([
                        'items' => sprintf('Nedovoljan lager za %s (%s). Dostupno: %d.', $product->name, $product->sku, $product->stock_quantity),
                    ]);
                }
            }
        }

        $before = [
            'status' => (string) $locked->status,
            'subtotal_rsd' => round((float) $locked->subtotal_rsd, 2),
            'shipping_full_name' => (string) $locked->shipping_full_name,
            'shipping_address' => (string) $locked->shipping_address,
            'shipping_city' => (string) $locked->shipping_city,
            'shipping_postal_code' => (string) $locked->shipping_postal_code,
            'shipping_phone' => (string) $locked->shipping_phone,
            'customer_note' => $locked->customer_note,
        ];
        $fieldsChanged = $before['shipping_full_name'] !== trim((string) $data['shipping_full_name'])
            || $before['shipping_address'] !== trim((string) $data['shipping_address'])
            || $before['shipping_city'] !== trim((string) $data['shipping_city'])
            || $before['shipping_postal_code'] !== trim((string) $data['shipping_postal_code'])
            || $before['shipping_phone'] !== trim((string) $data['shipping_phone'])
            || (string) ($before['customer_note'] ?? '') !== trim((string) ($data['customer_note'] ?? ''));
        if (!$itemsChanged && !$fieldsChanged) {
            return $locked;
        }

        $oldStatus = (string) $locked->status;
        $amendmentHash = substr(hash('sha256', trim($idempotencyKey)), 0, 24);

        foreach ($productIds as $productId) {
            $delta = (int) $deltas[$productId];
            if ($delta === 0) continue;
            /** @var Product $product */
            $product = $products->get($productId);
            $stockBefore = (int) $product->stock_quantity;
            $stockAfter = $stockBefore - $delta;
            if ($stockAfter < 0) {
                throw ValidationException::withMessages(['items' => 'Lager se promenio tokom obrade. Osveži porudžbinu i pokušaj ponovo.']);
            }
            $product->update(['stock_quantity' => $stockAfter, 'updated_by' => $actor->id]);
            StockMovement::query()->create([
                'event_key' => sprintf('order:%d:amend:%s:product:%d', $locked->id, $amendmentHash, $productId),
                'product_id' => $productId,
                'order_id' => $locked->id,
                'user_id' => $actor->id,
                'movement_type' => 'order_amendment',
                'source' => 'order_amendment',
                'quantity_change' => -$delta,
                'quantity_before' => $stockBefore,
                'quantity_after' => $stockAfter,
                'note' => 'Izmena rezervacije lagera za porudžbinu '.$locked->order_number,
                'metadata_json' => ['delta_order_quantity' => $delta, 'idempotency_hash' => $amendmentHash],
            ]);
            $product->refresh();
        }

        $rate = (float) $locked->eur_rsd_rate;
        $subtotal = 0.0;
        foreach ($requested as $productId => $row) {
            $productId = (int) $productId;
            $quantity = (int) $row['quantity'];
            if ($existingByProduct->has($productId)) {
                /** @var OrderItem $item */
                $item = $existingByProduct->get($productId);
                $lineTotal = round((float) $item->unit_price_rsd * $quantity, 2);
                $purchaseTotal = $item->purchase_unit_rsd_snapshot !== null
                    ? round((float) $item->purchase_unit_rsd_snapshot * $quantity, 2)
                    : null;
                $commissionTotal = round((float) $item->commission_unit_eur_snapshot * $quantity, 2);
                $item->update([
                    'quantity' => $quantity,
                    'line_total_rsd' => $lineTotal,
                    'purchase_total_rsd_snapshot' => $purchaseTotal,
                    'commission_total_eur_snapshot' => $commissionTotal,
                ]);
                $subtotal += $lineTotal;
                continue;
            }

            /** @var Product $product */
            $product = $products->get($productId);
            $snapshot = $this->orders->itemSnapshot($product, $quantity, $rate > 0 ? $rate : null);
            $locked->items()->create(['product_id' => $productId] + $snapshot);
            $subtotal += (float) $snapshot['line_total_rsd'];
        }

        foreach ($existingByProduct as $productId => $item) {
            if (!$requested->has((int) $productId)) $item->delete();
        }

        $subtotal = round($subtotal, 2);
        $newStatus = $oldStatus === 'confirmed' ? 'processing' : $oldStatus;
        $locked->update([
            'shipping_full_name' => trim((string) $data['shipping_full_name']),
            'shipping_address' => trim((string) $data['shipping_address']),
            'shipping_city' => trim((string) $data['shipping_city']),
            'shipping_postal_code' => trim((string) $data['shipping_postal_code']),
            'shipping_phone' => trim((string) $data['shipping_phone']),
            'customer_note' => trim((string) ($data['customer_note'] ?? '')) ?: null,
            'subtotal_rsd' => $subtotal,
            'status' => $newStatus,
            'updated_by' => $actor->id,
        ]);

        if ($oldStatus === 'confirmed') {
            DB::table('order_status_history')->insert([
                'order_id' => $locked->id,
                'changed_by' => $actor->id,
                'old_status' => 'confirmed',
                'new_status' => 'processing',
                'note' => 'Kupac je izmenio porudžbinu. Potrebna je ponovna potvrda pre slanja.',
                'created_at' => now(),
            ]);
        }

        $subtotalChanged = abs($before['subtotal_rsd'] - $subtotal) > 0.004;
        $this->commissions->reconcileAfterCustomerAmendmentLocked($locked, $actor, $itemsChanged);
        $this->payments->recalculateForAmendmentLocked($locked);
        if ($subtotalChanged) {
            $this->receivables->invalidatePlanForOrderAmendmentLocked($locked, $actor, [
                'subtotal_before_rsd' => $before['subtotal_rsd'],
                'subtotal_after_rsd' => $subtotal,
            ]);
        }

        $documentRelevantChanged = $itemsChanged || $fieldsChanged;
        $invalidated = $documentRelevantChanged
            ? $this->documents->invalidateIssuedForAmendmentLocked($locked, $actor, 'Porudžbina je izmenjena od strane kupca pre slanja.')
            : collect();

        $locked->refresh()->load(['items', 'commission', 'supplier', 'shipment']);
        $this->audit->log(
            'order.customer_amended',
            'Kupac je izmenio porudžbinu '.$locked->order_number,
            $locked,
            before: $before,
            after: [
                'status' => (string) $locked->status,
                'subtotal_rsd' => round((float) $locked->subtotal_rsd, 2),
                'shipping_full_name' => (string) $locked->shipping_full_name,
                'shipping_address' => (string) $locked->shipping_address,
                'shipping_city' => (string) $locked->shipping_city,
                'shipping_postal_code' => (string) $locked->shipping_postal_code,
                'shipping_phone' => (string) $locked->shipping_phone,
                'customer_note' => $locked->customer_note,
            ],
            metadata: [
                'item_deltas' => $deltas,
                'items_changed' => $itemsChanged,
                'subtotal_changed' => $subtotalChanged,
                'invalidated_document_ids' => $invalidated->pluck('id')->map('intval')->all(),
            ],
            user: $actor,
        );

        $orderId = (int) $locked->id;
        DB::afterCommit(function () use ($orderId, $actor): void {
            $fresh = Order::query()->with(['user', 'supplier'])->find($orderId);
            if (!$fresh instanceof Order) return;
            try {
                if ($fresh->supplier instanceof User) {
                    $this->notifications->order(
                        $fresh->supplier,
                        'order.customer_amended',
                        'Kupac je izmenio porudžbinu',
                        'Porudžbina '.$fresh->order_number.' je izmenjena. Proveri poslednju verziju pre potvrde i slanja.',
                        $fresh,
                        ['severity' => 'warning'],
                    );
                }
            } catch (Throwable $exception) {
                Log::warning('Customer amendment notification failed after commit.', ['order_id' => $orderId, 'exception' => $exception]);
            }
            try {
                $this->emails->orderChanged(
                    $fresh,
                    'order_customer_amended',
                    'Izmenjena porudžbina '.$fresh->order_number,
                    'Kupac je izmenio porudžbinu pre slanja. Proveri poslednju verziju.',
                    ['actor_id' => $actor->id, 'changed_at' => now()->toISOString()],
                );
            } catch (Throwable $exception) {
                Log::warning('Customer amendment email enqueue failed after commit.', ['order_id' => $orderId, 'exception' => $exception]);
            }
        });

        return $locked;
    }

    /** @param array<int,array<string,mixed>> $items @return list<array{product_id:int,quantity:int}> */
    private function normalizeItems(array $items): array
    {
        $normalized = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0) continue;
            if (isset($normalized[$productId])) {
                throw ValidationException::withMessages(['items' => 'Isti artikal može biti naveden samo jednom.']);
            }
            $normalized[$productId] = ['product_id' => $productId, 'quantity' => $quantity];
        }
        ksort($normalized);
        return array_values($normalized);
    }
}
