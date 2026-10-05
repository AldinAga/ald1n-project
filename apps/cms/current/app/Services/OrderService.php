<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

final class OrderService
{
    public function __construct(
        private readonly IdempotencyService $idempotency,
        private readonly CatalogAccessService $catalogAccess,
        private readonly CommissionCalculator $commissionCalculator,
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
        private readonly OrderEmailOutboxService $emails,
        private readonly ReceivablesService $receivables,
    ) {}

    /** @param array<string,mixed> $data */
    public function create(User $user, array $data, string $idempotencyKey): Order
    {
        $data['items'] = $this->normalizeItems((array) ($data['items'] ?? []));

        /** @var Order $order */
        $order = $this->idempotency->run(
            $user,
            'orders.create',
            $idempotencyKey,
            $data,
            Order::class,
            fn (): Order => $this->createInTransaction($user, $data, $idempotencyKey),
            static fn (int $id): Order => Order::query()->findOrFail($id),
        );

        return $order->load(['items.product', 'commission', 'bankAccount', 'supplier']);
    }

    /** @param array<string,mixed> $data */
    private function createInTransaction(User $user, array $data, string $idempotencyKey): Order
    {
        $items = (array) $data['items'];
        $productIds = array_values(array_unique(array_map('intval', array_column($items, 'product_id'))));
        $productQuery = Product::query()
            ->publiclyVisible()
            ->whereIn('id', $productIds)
            ->with(['brand', 'line', 'type'])
            ->orderBy('id')
            ->lockForUpdate();
        $this->catalogAccess->apply($productQuery, $user);

        /** @var Collection<int,Product> $products */
        $products = $productQuery->get()->keyBy('id');
        if ($products->count() !== count($productIds)) {
            throw ValidationException::withMessages(['items' => 'Jedan ili više artikala nisu dostupni za poručivanje.']);
        }

        $rate = $this->settings->eurRsdRate();
        $supplier = $this->resolveSupplier($data);
        $bankAccount = $this->resolveBankAccount($data);
        $fingerprint = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $order = Order::query()->create([
            'source_system' => 'laravel',
            'order_number' => 'TMP-'.strtoupper(substr(hash('sha256', $idempotencyKey.microtime(true)), 0, 24)),
            'idempotency_key_hash' => hash('sha256', trim($idempotencyKey)),
            'request_fingerprint' => $fingerprint,
            'user_id' => $user->id,
            'supplier_user_id' => $supplier->id,
            'supplier_name_snapshot' => $supplier->displayName(),
            'supplier_email_snapshot' => $supplier->email,
            'supplier_phone_snapshot' => $supplier->phone,
            'supplier_role_snapshot' => $supplier->roleName(),
            'assigned_at' => now(),
            'status' => 'new',
            'inventory_state' => 'reserved',
            'inventory_reserved_at' => now(),
            'shipping_full_name' => (string) $data['shipping_full_name'],
            'shipping_address' => (string) $data['shipping_address'],
            'shipping_city' => (string) $data['shipping_city'],
            'shipping_postal_code' => (string) $data['shipping_postal_code'],
            'shipping_phone' => (string) $data['shipping_phone'],
            'subtotal_rsd' => 0,
            'eur_rsd_rate' => $rate,
            'customer_note' => $data['customer_note'] ?? null,
            'payment_method' => (string) $data['payment_method'],
            'payment_status' => 'pending',
            'payment_due_at' => ($data['payment_method'] ?? null) === 'deferred_payment' ? (string) $data['payment_due_at'] : null,
            'bank_account_id' => $bankAccount?->id,
            'bank_account_label_snapshot' => $bankAccount?->label,
            'bank_account_number_snapshot' => $bankAccount?->account_number,
            'bank_account_number_display_snapshot' => $bankAccount?->account_number_display,
            'payment_recipient_name_snapshot' => $bankAccount?->recipient_name,
            'payment_recipient_address_snapshot' => $bankAccount?->recipient_address,
            'payment_code_snapshot' => $bankAccount?->payment_code,
            'updated_by' => $user->id,
        ]);

        $orderNumber = sprintf('APC-%s-%08d', now()->format('Ymd'), $order->id);
        $order->update([
            'order_number' => $orderNumber,
            'payment_purpose_snapshot' => $bankAccount ? 'Plaćanje porudžbine' : null,
            'payment_reference_snapshot' => $bankAccount ? (string) $order->id : null,
        ]);

        $subtotalRsd = 0.0;
        $commissionTotalEur = 0.0;
        foreach ($items as $itemData) {
            /** @var Product $product */
            $product = $products->get((int) $itemData['product_id']);
            $quantity = (int) $itemData['quantity'];

            if ((int) $product->stock_quantity < $quantity) {
                throw ValidationException::withMessages(['items' => sprintf('Nedovoljan lager za %s (%s). Dostupno: %d.', $product->name, $product->sku, $product->stock_quantity)]);
            }

            $snapshot = $this->itemSnapshot($product, $quantity, $rate);
            $lineTotalRsd = (float) $snapshot['line_total_rsd'];
            $commissionTotal = (float) $snapshot['commission_total_eur_snapshot'];

            $orderItem = $order->items()->create(['product_id' => $product->id] + $snapshot);

            $before = (int) $product->stock_quantity;
            $after = $before - $quantity;
            $product->update(['stock_quantity' => $after, 'updated_by' => $user->id]);

            StockMovement::query()->create([
                'event_key' => sprintf('order:%d:item:%d:sale', $order->id, $orderItem->id),
                'product_id' => $product->id,
                'order_id' => $order->id,
                'user_id' => $user->id,
                'movement_type' => 'sale',
                'source' => 'order',
                'quantity_change' => -$quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'note' => 'Rezervacija lagera za porudžbinu '.$orderNumber,
                'metadata_json' => ['order_item_id' => $orderItem->id, 'source_system' => 'laravel'],
            ]);

            $subtotalRsd += $lineTotalRsd;
            $commissionTotalEur += $commissionTotal;
        }

        $order->update(['subtotal_rsd' => round($subtotalRsd, 2)]);
        OrderCommission::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'total_eur' => round($commissionTotalEur, 2),
            'status' => 'pending',
        ]);
        DB::table('order_status_history')->insert([
            'order_id' => $order->id,
            'changed_by' => $user->id,
            'old_status' => null,
            'new_status' => 'new',
            'note' => 'Porudžbina kreirana u Laravel produkcionom sistemu.',
            'created_at' => now(),
        ]);

        $order->refresh();
        $this->audit->log(
            'order.created',
            'Kreirana porudžbina '.$order->order_number,
            $order,
            after: ['status' => $order->status, 'subtotal_rsd' => $order->subtotal_rsd, 'inventory_state' => $order->inventory_state],
            metadata: ['item_count' => count($items), 'source_system' => 'laravel'],
            user: $user,
        );

        DB::afterCommit(function () use ($order, $user, $supplier): void {
            $fresh = Order::query()->with(['user', 'supplier'])->find($order->id);
            if (!$fresh instanceof Order) return;
            try {
                $this->notifications->order($supplier, 'order.created_for_supplier', 'Nova porudžbina', 'Korisnik '.$user->displayName().' je poslao porudžbinu '.$fresh->order_number.'.', $fresh, ['severity' => 'warning']);
                $this->notifications->order($user, 'order.created', 'Porudžbina je kreirana', 'Porudžbina '.$fresh->order_number.' je uspešno poslata odgovornom licu '.$supplier->displayName().'.', $fresh, ['severity' => 'success']);
            } catch (Throwable $exception) {
                Log::warning('Order creation in-app notification failed after commit.', ['order_id' => $fresh->id, 'exception' => $exception]);
            }
            $this->emails->orderCreated($fresh, $user);
            try {
                $this->receivables->ensureForOrder($fresh, $user);
            } catch (Throwable $exception) {
                Log::warning('Receivable case creation failed after order commit.', ['order_id' => $fresh->id, 'exception' => $exception]);
            }
        });

        return $order;
    }


    /** @param array<string,mixed> $data */
    private function resolveSupplier(array $data): User
    {
        $query = User::query()
            ->where('status', 'active')
            ->whereHas('role', static fn (Builder $role) => $role->whereIn('slug', ['superadmin', 'admin']))
            ->with('role');

        if (!empty($data['supplier_user_id'])) {
            $supplier = (clone $query)->whereKey((int) $data['supplier_user_id'])->first();
            if ($supplier === null) {
                throw ValidationException::withMessages(['supplier_user_id' => 'Izabrani Administrator nije dostupan za prijem porudžbine.']);
            }
            return $supplier;
        }

        $supplier = $query
            ->orderBy('id')
            ->get()
            ->sortBy(static fn (User $candidate): int => $candidate->hasRole('superadmin') ? 0 : 1)
            ->first();
        if ($supplier === null) {
            throw ValidationException::withMessages(['supplier_user_id' => 'Nema aktivnog SuperAdministratora ili Administratora koji može primiti porudžbinu.']);
        }
        return $supplier;
    }

    /** @param array<string,mixed> $data */
    private function resolveBankAccount(array $data): ?BankAccount
    {
        if (($data['payment_method'] ?? null) !== 'bank_transfer') {
            return null;
        }

        $account = BankAccount::query()->whereKey((int) ($data['bank_account_id'] ?? 0))->where('is_active', true)->first();
        if ($account === null) {
            throw ValidationException::withMessages(['bank_account_id' => 'Izabrani žiro račun nije aktivan.']);
        }

        return $account;
    }

    /** @return array<string,mixed> */
    public function itemSnapshot(Product $product, int $quantity, ?float $rate): array
    {
        $quantity = max(1, $quantity);
        $product->loadMissing(['brand', 'line', 'type']);
        $unitPriceRsd = $this->priceRsd($product, $rate);
        $manualCommission = $product->manual_commission_eur !== null ? (float) $product->manual_commission_eur : null;
        $commissionUnit = $this->commissionCalculator->unitEur((float) $product->price_amount, (string) $product->price_currency, $manualCommission, $rate);
        $usesManualCommission = $this->commissionCalculator->usesManual(
            (float) $product->price_amount,
            (string) $product->price_currency,
            $manualCommission,
            $rate,
        );
        $purchaseUnitRsd = $product->purchase_price_rsd !== null && (float) $product->purchase_price_rsd > 0
            ? round((float) $product->purchase_price_rsd, 2)
            : null;

        return [
            'product_sku' => $product->sku,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'unit_price_original' => $product->price_amount,
            'original_currency' => $product->price_currency,
            'unit_price_rsd' => $unitPriceRsd,
            'line_total_rsd' => round($unitPriceRsd * $quantity, 2),
            'purchase_unit_rsd_snapshot' => $purchaseUnitRsd,
            'purchase_total_rsd_snapshot' => $purchaseUnitRsd === null ? null : round($purchaseUnitRsd * $quantity, 2),
            'cost_source_snapshot' => $purchaseUnitRsd === null ? 'missing' : 'product',
            'brand_name_snapshot' => $product->brand?->name,
            'product_line_name_snapshot' => $product->line?->name,
            'product_type_name_snapshot' => $product->type?->name,
            'commission_source_snapshot' => $usesManualCommission ? 'manual' : 'automatic',
            'commission_rate_percent_snapshot' => $usesManualCommission ? null : CommissionCalculator::DEFAULT_RATE_PERCENT,
            'commission_unit_eur_snapshot' => $commissionUnit,
            'commission_total_eur_snapshot' => round($commissionUnit * $quantity, 2),
        ];
    }

    private function priceRsd(Product $product, ?float $rate): float
    {
        if ($product->price_currency === 'RSD') {
            return round((float) $product->price_amount, 2);
        }

        if ($rate === null || $rate <= 0) {
            throw ValidationException::withMessages(['items' => 'EUR/RSD kurs mora biti podešen pre poručivanja EUR artikala.']);
        }

        return round((float) $product->price_amount * $rate, 2);
    }

    /** @param array<int,array<string,mixed>> $items @return array<int,array{product_id:int,quantity:int}> */
    private function normalizeItems(array $items): array
    {
        $normalized = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0) continue;
            if (!isset($normalized[$productId])) $normalized[$productId] = ['product_id' => $productId, 'quantity' => 0];
            $normalized[$productId]['quantity'] += $quantity;
        }
        ksort($normalized);
        return array_values($normalized);
    }

}
