<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

final class DirectSaleService
{
    public function __construct(
        private readonly IdempotencyService $idempotency,
        private readonly SettingsService $settings,
        private readonly DocumentNumberService $numbers,
        private readonly AuditLogger $audit,
        private readonly WarrantyService $warranties,
    ) {
    }

    /** @param array<string,mixed> $input */
    public function record(Product $product, User $actor, array $input, string $idempotencyKey): Order
    {
        abort_unless($actor->hasRole('superadmin'), 403);

        $paymentMethod = trim((string) ($input['payment_method'] ?? ''));
        if (!in_array($paymentMethod, ['cash', 'card', 'bank_transfer', 'other'], true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Izabrani način plaćanja nije dozvoljen za direktnu prodaju.',
            ]);
        }

        $buyerName = trim((string) ($input['buyer_name'] ?? ''));
        if ($buyerName === '') {
            $buyerName = 'Krajnji kupac';
        }
        $buyerPhone = trim((string) ($input['buyer_phone'] ?? ''));


        $payload = [
            'product_id' => (int) $product->getKey(),
            'buyer_name' => $buyerName,
            'buyer_phone' => $buyerPhone !== '' ? $buyerPhone : null,
            'quantity' => (int) ($input['quantity'] ?? 0),
            'sale_price_rsd' => round((float) ($input['sale_price_rsd'] ?? 0), 2),
            'payment_method' => $paymentMethod,
        ];

        $order = $this->idempotency->run(
            $actor,
            'direct_sale.record',
            $idempotencyKey,
            $payload,
            Order::class,
            fn (): Order => $this->recordInTransaction($product, $actor, $payload, $idempotencyKey),
            static fn (int $id): Order => Order::query()->findOrFail($id),
        );

        try {
            $this->warranties->ensureForOrder($order->loadMissing('user'), $actor);
        } catch (Throwable $exception) {
            Log::warning('Direct sale warranty backfill failed', [
                'order_id' => (int) $order->id,
                'actor_id' => (int) $actor->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }

        return $order->fresh([
            'items.product',
            'payments',
            'delivery',
            'user',
            'directSaleRecorder',
            'commission',
        ]) ?? $order;
    }

    /** @param array{product_id:int,buyer_name:string,buyer_phone:?string,quantity:int,sale_price_rsd:float,payment_method:string} $payload */
    private function recordInTransaction(Product $product, User $actor, array $payload, string $idempotencyKey): Order
    {
        $lockedProduct = Product::query()
            ->with(['brand', 'line', 'type'])
            ->whereKey((int) $product->getKey())
            ->whereIn('status', ['active', 'inactive'])
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if (!$lockedProduct instanceof Product) {
            throw ValidationException::withMessages([
                'product' => 'Artikal više nije dostupan za direktnu prodaju.',
            ]);
        }

        $quantity = $payload['quantity'];
        if ($quantity < 1 || $quantity > 1000) {
            throw ValidationException::withMessages([
                'quantity' => 'Količina mora biti između 1 i 1000.',
            ]);
        }

        $salePrice = round($payload['sale_price_rsd'], 2);
        if ($salePrice <= 0) {
            throw ValidationException::withMessages([
                'sale_price_rsd' => 'Prodajna cena mora biti veća od nule.',
            ]);
        }

        // DIRECT_SALE_MAX_UNIT_PRICE_GUARD
        $rate = $this->settings->eurRsdRate();
        $catalogUnitPriceRsd = $this->catalogUnitPriceRsd($lockedProduct, $rate);
        $this->assertSalePriceWithinCatalogUnitPrice($salePrice, $catalogUnitPriceRsd);
        $quantityBefore = (int) $lockedProduct->stock_quantity;

        if ($quantityBefore < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Nema dovoljno artikala na lageru za ovu direktnu prodaju.',
            ]);
        }

        $soldAt = now();
        $lineTotal = round($salePrice * $quantity, 2);
        $fingerprint = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $order = Order::query()->create([
            'source_system' => 'laravel',
            'sales_channel' => 'direct_sale',
            'direct_sale_recorded_by' => (int) $actor->id,
            'order_number' => 'TMP-'.$soldAt->format('YmdHis').'-'.bin2hex(random_bytes(3)),
            'idempotency_key_hash' => hash('sha256', trim($idempotencyKey)),
            'request_fingerprint' => $fingerprint,
            'user_id' => null,
            'supplier_user_id' => null,
            'supplier_name_snapshot' => null,
            'supplier_email_snapshot' => null,
            'supplier_phone_snapshot' => null,
            'supplier_role_snapshot' => null,
            'assigned_at' => null,
            'status' => 'shipped',
            'inventory_state' => 'reserved',
            'inventory_reserved_at' => $soldAt,
            'shipping_full_name' => $payload['buyer_name'],
            'shipping_address' => '',
            'shipping_city' => '',
            'shipping_postal_code' => '',
            'shipping_phone' => $payload['buyer_phone'] ?? '',
            'subtotal_rsd' => $lineTotal,
            'eur_rsd_rate' => $rate,
            'customer_note' => 'Direktna prodaja Super Administratora krajnjem kupcu.',
            'payment_method' => $payload['payment_method'],
            'payment_status' => 'paid',
            'payment_state' => 'paid',
            'paid_total_rsd' => $lineTotal,
            'payment_verified_at' => $soldAt,
            'completed_at' => $soldAt,
            'completed_by' => (int) $actor->id,
            'completion_note' => 'Direktna prodaja evidentirana i lično dostavljena krajnjem kupcu.',
            'updated_by' => (int) $actor->id,
        ]);

        $orderNumber = sprintf('ALD-%s-%08d', $soldAt->format('Ymd'), (int) $order->id);
        $order->forceFill(['order_number' => $orderNumber])->save();

        $purchaseUnit = null;
        $costSource = 'missing';
        if ((float) ($lockedProduct->purchase_price_rsd ?? 0) > 0) {
            $purchaseUnit = round((float) $lockedProduct->purchase_price_rsd, 2);
            $costSource = 'product';
        }

        $item = OrderItem::query()->create([
            'order_id' => (int) $order->id,
            'product_id' => (int) $lockedProduct->id,
            'product_sku' => (string) $lockedProduct->sku,
            'product_name' => (string) $lockedProduct->name,
            'quantity' => $quantity,
            'unit_price_original' => $salePrice,
            'original_currency' => 'RSD',
            'unit_price_rsd' => $salePrice,
            'line_total_rsd' => $lineTotal,
            'purchase_unit_rsd_snapshot' => $purchaseUnit,
            'purchase_total_rsd_snapshot' => $purchaseUnit !== null ? round($purchaseUnit * $quantity, 2) : null,
            'cost_source_snapshot' => $costSource,
            'brand_name_snapshot' => $lockedProduct->brand?->name,
            'product_line_name_snapshot' => $lockedProduct->line?->name,
            'product_type_name_snapshot' => $lockedProduct->type?->name,
            'commission_source_snapshot' => 'direct_sale',
            'commission_rate_percent_snapshot' => 0,
            'commission_unit_eur_snapshot' => 0,
            'commission_total_eur_snapshot' => 0,
        ]);

        $quantityAfter = $quantityBefore - $quantity;
        $lockedProduct->forceFill([
            'stock_quantity' => $quantityAfter,
            'updated_by' => (int) $actor->id,
        ])->save();

        StockMovement::query()->create([
            'event_key' => sprintf('direct-sale:%d:item:%d:sale', (int) $order->id, (int) $item->id),
            'product_id' => (int) $lockedProduct->id,
            'order_id' => (int) $order->id,
            'user_id' => (int) $actor->id,
            'movement_type' => 'sale',
            'source' => 'direct_sale',
            'quantity_change' => -$quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'note' => 'Direktna prodaja '.$orderNumber,
            'metadata_json' => [
                'order_item_id' => (int) $item->id,
                'source_system' => 'laravel',
                'sales_channel' => 'direct_sale',
            ],
        ]);

        OrderPayment::query()->create([
            'order_id' => (int) $order->id,
            'payment_number' => $this->numbers->next('payment', (int) $soldAt->format('Y')),
            'entry_type' => 'payment',
            'status' => 'verified',
            'amount_rsd' => $lineTotal,
            'payment_method' => $payload['payment_method'],
            'paid_at' => $soldAt,
            'reference' => 'Direktna prodaja '.$orderNumber,
            'note' => 'Verifikovana uplata evidentirana uz direktnu prodaju.',
            'submitted_by' => (int) $actor->id,
            'verified_by' => (int) $actor->id,
            'verified_at' => $soldAt,
        ]);

        OrderDelivery::query()->create([
            'order_id' => (int) $order->id,
            'delivery_method' => 'own_transport',
            'delivered_at' => $soldAt,
            'recipient_name' => $payload['buyer_name'],
            'recipient_phone' => $payload['buyer_phone'],
            'reference' => 'Direktna prodaja '.$orderNumber,
            'note' => 'Artikal je lično dostavljen krajnjem kupcu u okviru direktne prodaje Super Administratora.',
            'confirmed_by' => (int) $actor->id,
        ]);

        DB::table('order_status_history')->insert([
            'order_id' => (int) $order->id,
            'old_status' => null,
            'new_status' => 'shipped',
            'changed_by' => (int) $actor->id,
            'note' => 'Direktna prodaja evidentirana, plaćena i lično dostavljena krajnjem kupcu.',
            'created_at' => $soldAt,
        ]);

        $this->audit->log(
            'direct_sale.recorded',
            'Evidentirana direktna prodaja '.$orderNumber,
            $order,
            after: [
                'status' => 'shipped',
                'payment_state' => 'paid',
                'subtotal_rsd' => $lineTotal,
                'completed_at' => $soldAt->toISOString(),
            ],
            metadata: [
                'product_id' => (int) $lockedProduct->id,
                'customer_mode' => 'walk_in',
                'quantity' => $quantity,
                'sale_price_rsd' => $salePrice,
                'payment_method' => $payload['payment_method'],
                'sales_channel' => 'direct_sale',
            ],
            user: $actor,
        );

        return $order;
    }

    private function assertSalePriceWithinCatalogUnitPrice(float $salePrice, float $catalogUnitPriceRsd): void
    {
        if ($salePrice > $catalogUnitPriceRsd) {
            throw ValidationException::withMessages([
                'sale_price_rsd' => sprintf(
                    'Prodajna cena po komadu ne sme biti veća od zadate cene artikla (%.2f RSD).',
                    $catalogUnitPriceRsd,
                ),
            ]);
        }
    }

    private function catalogUnitPriceRsd(Product $sellable, ?float $rate): float
    {
        if ($sellable->price_currency === 'RSD') {
            return round((float) $sellable->price_amount, 2);
        }

        if ($rate === null || $rate <= 0) {
            throw ValidationException::withMessages([
                'sale_price_rsd' => 'EUR/RSD kurs mora biti podešen pre direktne prodaje EUR artikla.',
            ]);
        }

        return round((float) $sellable->price_amount * $rate, 2);
    }
}
