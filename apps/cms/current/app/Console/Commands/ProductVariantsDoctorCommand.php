<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductVariantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class ProductVariantsDoctorCommand extends Command
{
    protected $signature = 'app:product-variants-doctor {--repair : Pokreni migracije i uskladi zbirni lager/cenu roditeljskih artikala}';
    protected $description = 'Proverava šemu, veze, SKU, lager i istorijske snapshot podatke varijanti proizvoda.';

    public function handle(ProductVariantService $variants): int
    {
        if ($this->option('repair')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                $this->output->write(Artisan::output());
                Product::query()->where('variants_enabled', true)->orderBy('id')->chunkById(100, function ($products) use ($variants): void {
                    foreach ($products as $product) $variants->syncParent($product);
                });
                $this->info('Roditeljski artikli su ponovo usklađeni sa aktivnim varijantama.');
            } catch (Throwable $exception) {
                $this->error('Repair nije uspeo: '.$exception->getMessage());
                return self::FAILURE;
            }
        }

        $schema = [
            'products' => ['id', 'variants_enabled', 'default_variant_id', 'stock_quantity', 'price_amount', 'price_currency'],
            'product_variants' => ['id', 'product_id', 'sku', 'name', 'price_amount', 'price_currency', 'stock_quantity', 'low_stock_threshold', 'status', 'is_default', 'warranty_rule_id', 'deleted_at'],
            'product_variant_spec_values' => ['product_variant_id', 'field_id', 'value_text', 'value_detail', 'value_number', 'value_boolean'],
            'product_images' => ['product_id', 'product_variant_id'],
            'order_items' => ['product_id', 'product_variant_id', 'variant_sku_snapshot', 'variant_name_snapshot', 'variant_attributes_json'],
            'stock_movements' => ['product_id', 'product_variant_id', 'event_key'],
            'after_sales_case_items' => ['product_id', 'product_variant_id'],
            'after_sales_action_items' => ['product_id', 'product_variant_id'],
            'product_warranties' => ['product_id', 'product_variant_id'],
        ];
        $failed = false;
        foreach ($schema as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->line('<fg=red>FAIL</> Nedostaje tabela '.$table.'.');
                $failed = true;
                continue;
            }
            $missing = array_values(array_diff($columns, Schema::getColumnListing($table)));
            if ($missing !== []) {
                $this->line('<fg=red>FAIL</> '.$table.' nema kolone: '.implode(', ', $missing));
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> '.$table.' je spremna.');
            }
        }
        if ($failed) {
            $this->warn('Pokreni: php artisan app:product-variants-doctor --repair');
            return self::FAILURE;
        }

        $routes = ['admin.products.variants.index', 'admin.products.variants.store', 'admin.products.variants.update', 'admin.products.variants.stock', 'admin.products.variants.default'];
        $missingRoutes = array_values(array_filter($routes, static fn (string $name): bool => !Route::has($name)));
        if ($missingRoutes !== []) {
            $this->error('FAIL Nedostaju rute: '.implode(', ', $missingRoutes));
            return self::FAILURE;
        }
        $this->info('PASS Rute za varijante su registrovane.');

        try {
            $duplicateSku = DB::table('product_variants')->selectRaw('LOWER(sku) normalized, COUNT(*) total')->whereNull('deleted_at')->groupByRaw('LOWER(sku)')->havingRaw('COUNT(*) > 1')->count();
            $crossSku = DB::table('product_variants as variants')->join('products', DB::raw('LOWER(products.sku)'), '=', DB::raw('LOWER(variants.sku)'))->whereNull('variants.deleted_at')->count();
            $multipleDefaults = DB::table('product_variants')->select('product_id')->whereNull('deleted_at')->where('is_default', true)->groupBy('product_id')->havingRaw('COUNT(*) > 1')->count();
            $wrongDefault = DB::table('products')->join('product_variants', 'product_variants.id', '=', 'products.default_variant_id')->whereColumn('product_variants.product_id', '!=', 'products.id')->count();
            $wrongOrderVariant = DB::table('order_items')->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')->whereColumn('product_variants.product_id', '!=', 'order_items.product_id')->count();
            $wrongAfterSales = DB::table('after_sales_case_items')->join('product_variants', 'product_variants.id', '=', 'after_sales_case_items.product_variant_id')->whereColumn('product_variants.product_id', '!=', 'after_sales_case_items.product_id')->count();
            $aggregateMismatch = DB::table('products')->where('variants_enabled', true)->whereRaw('stock_quantity <> (SELECT COALESCE(SUM(stock_quantity),0) FROM product_variants WHERE product_variants.product_id=products.id AND product_variants.status=? AND product_variants.deleted_at IS NULL)', ['active'])->count();

            $problems = compact('duplicateSku', 'crossSku', 'multipleDefaults', 'wrongDefault', 'wrongOrderVariant', 'wrongAfterSales', 'aggregateMismatch');
            foreach ($problems as $name => $count) {
                if ($count > 0) {
                    $this->line('<fg=red>FAIL</> '.$name.': '.$count);
                    $failed = true;
                }
            }
            if (!$failed) $this->info('PASS SKU, default veze, snapshot veze i zbirni lager varijanti su validni.');
        } catch (Throwable $exception) {
            $this->error('FAIL SQL provera varijanti nije uspela: '.$exception->getMessage());
            return self::FAILURE;
        }

        if ($failed) {
            $this->warn('Pokreni: php artisan app:product-variants-doctor --repair');
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
