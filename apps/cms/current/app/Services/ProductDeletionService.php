<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ProductDeletionService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @return array<string,int> */
    public function blockers(Product $product): array
    {
        $checks = [
            'Porudžbine' => 'order_items',
            'Ulazi robe' => 'stock_receipt_items',
            'Popisi lagera' => 'inventory_count_items',
        ];

        $blockers = [];
        foreach ($checks as $label => $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'product_id')) {
                continue;
            }

            $count = (int) DB::table($table)->where('product_id', (int) $product->id)->count();
            if ($count > 0) {
                $blockers[$label] = $count;
            }
        }

        if (Schema::hasTable('stock_movements') && Schema::hasColumn('stock_movements', 'product_id')) {
            $movements = DB::table('stock_movements')->where('product_id', (int) $product->id);
            if (Schema::hasColumn('stock_movements', 'movement_type') && Schema::hasColumn('stock_movements', 'source')) {
                $hasOrderId = Schema::hasColumn('stock_movements', 'order_id');
                $movements->where(function ($query) use ($hasOrderId): void {
                    $query->where('movement_type', '!=', 'initial')
                        ->orWhere('source', '!=', 'product_creation')
                        ->orWhereNull('movement_type')
                        ->orWhereNull('source');
                    if ($hasOrderId) {
                        $query->orWhereNotNull('order_id');
                    }
                });
            }
            $count = (int) $movements->count();
            if ($count > 0) {
                $blockers['Promene lagera'] = $count;
            }
        }

        return $blockers;
    }

    /**
     * @return array{deleted_images:int,public_images:int,legacy_images:int,files_deleted:bool,files_requested:bool}
     */
    public function purge(Product $product, User $actor, bool $deleteFiles): array
    {
        $productId = (int) $product->id;
        $directory = 'products/'.$productId;
        $result = [
            'deleted_images' => 0,
            'public_images' => 0,
            'legacy_images' => 0,
            'files_deleted' => false,
            'files_requested' => $deleteFiles,
        ];

        try {
            $result = DB::transaction(function () use ($productId, $actor, $deleteFiles, $result): array {
                /** @var Product $locked */
                $locked = Product::query()->lockForUpdate()->findOrFail($productId);
                $blockers = $this->blockers($locked);
                if ($blockers !== []) {
                    $details = collect($blockers)
                        ->map(static fn (int $count, string $label): string => $label.': '.$count)
                        ->implode(', ');

                    throw ValidationException::withMessages([
                        'confirmation' => 'Artikal ima poslovnu istoriju i ne može trajno da se obriše ('.$details.'). Arhiviraj ga da istorijski dokumenti ostanu ispravni.',
                    ]);
                }

                $images = ProductImage::query()
                    ->where('product_id', $productId)
                    ->get(['id', 'storage_disk', 'file_path']);
                $result['deleted_images'] = $images->count();
                $result['public_images'] = $images->where('storage_disk', 'public')->count();
                $result['legacy_images'] = $images->where('storage_disk', 'legacy')->count();

                $snapshot = $locked->only([
                    'id', 'sku', 'name', 'slug', 'product_type_id', 'brand_id', 'product_line_id', 'model_name',
                    'price_amount', 'price_currency', 'status', 'created_by', 'updated_by', 'deleted_at',
                ]);

                // Klonovi moraju ostati validni i nakon brisanja izvornog artikla.
                Product::query()->where('source_product_id', $productId)->update(['source_product_id' => null]);
                $deletedInitialMovements = 0;
                if (Schema::hasTable('stock_movements')
                    && Schema::hasColumn('stock_movements', 'product_id')
                    && Schema::hasColumn('stock_movements', 'movement_type')
                    && Schema::hasColumn('stock_movements', 'source')) {
                    $initialMovements = DB::table('stock_movements')
                        ->where('product_id', $productId)
                        ->where('movement_type', 'initial')
                        ->where('source', 'product_creation');
                    if (Schema::hasColumn('stock_movements', 'order_id')) {
                        $initialMovements->whereNull('order_id');
                    }
                    $deletedInitialMovements = $initialMovements->delete();
                }
                if (Schema::hasColumn('products', 'default_variant_id')) {
                    $locked->forceFill(['default_variant_id' => null])->saveQuietly();
                }

                $this->audit->log(
                    'product.purged',
                    'Trajno obrisan artikal '.$locked->sku,
                    $locked,
                    before: $snapshot,
                    metadata: [
                        'delete_image_files' => $deleteFiles,
                        'image_records' => $result['deleted_images'],
                        'public_images' => $result['public_images'],
                        'legacy_images' => $result['legacy_images'],
                        'deleted_initial_stock_movements' => $deletedInitialMovements,
                    ],
                    user: $actor,
                    level: 'warning',
                );

                $locked->delete();

                return $result;
            }, 3);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            throw ValidationException::withMessages([
                'confirmation' => 'Artikal nije obrisan jer je povezan sa drugim poslovnim podacima. Arhiviraj ga ili prvo ukloni bezbedne veze. Tehnički kod: '.(string) ($exception->errorInfo[1] ?? $exception->getCode()),
            ]);
        }

        if ($deleteFiles) {
            try {
                $result['files_deleted'] = !Storage::disk('public')->exists($directory)
                    || Storage::disk('public')->deleteDirectory($directory);
            } catch (Throwable $exception) {
                $this->audit->log(
                    'product.purge_files_failed',
                    'Artikal je obrisan, ali direktorijum slika nije potpuno uklonjen',
                    metadata: [
                        'product_id' => $productId,
                        'directory' => $directory,
                        'exception' => $exception::class,
                        'message' => mb_substr($exception->getMessage(), 0, 500),
                    ],
                    user: $actor,
                    level: 'error',
                );
                $result['files_deleted'] = false;
            }
        }

        return $result;
    }
}
