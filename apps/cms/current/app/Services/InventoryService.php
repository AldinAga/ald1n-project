<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class InventoryService
{
    public function __construct(
        private readonly IdempotencyService $idempotency,
        private readonly AuditLogger $audit,
    ) {}

    public function adjust(Product $product, int $quantityChange, string $note, string $idempotencyKey, User $actor): StockMovement
    {
        if ((bool) $product->variants_enabled) {
            throw ValidationException::withMessages(['product' => 'Artikal koristi varijante. Lager korigujte na ekranu Varijante proizvoda.']);
        }
        $payload = [
            'product_id' => (int) $product->id,
            'quantity_change' => $quantityChange,
            'note' => trim($note),
        ];

        /** @var StockMovement $movement */
        $movement = $this->idempotency->run(
            $actor,
            'stock.adjust',
            $idempotencyKey,
            $payload,
            StockMovement::class,
            function () use ($product, $quantityChange, $note, $idempotencyKey, $actor): StockMovement {
                /** @var Product $locked */
                $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
                $before = (int) $locked->stock_quantity;
                $after = $before + $quantityChange;
                if ($after < 0) {
                    throw ValidationException::withMessages(['quantity_change' => 'Korekcija ne može spustiti lager ispod nule.']);
                }

                $locked->update(['stock_quantity' => $after, 'updated_by' => $actor->id]);
                $movement = StockMovement::query()->create([
                    'event_key' => 'stock-adjust:'.hash('sha256', $actor->id.'|'.$idempotencyKey),
                    'product_id' => $locked->id,
                    'user_id' => $actor->id,
                    'movement_type' => 'manual_adjustment',
                    'source' => 'admin_adjustment',
                    'quantity_change' => $quantityChange,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'note' => trim($note),
                    'metadata_json' => ['idempotent' => true],
                ]);
                $this->audit->log('stock.adjusted', 'Korigovan lager '.$locked->sku, $locked, ['stock_quantity' => $before], ['stock_quantity' => $after], ['movement_id' => $movement->id, 'note' => trim($note)], $actor);
                return $movement;
            },
            static fn (int $id): StockMovement => StockMovement::query()->findOrFail($id),
        );

        return $movement;
    }
}
