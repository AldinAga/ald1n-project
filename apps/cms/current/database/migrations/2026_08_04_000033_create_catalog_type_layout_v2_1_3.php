<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_types') && Schema::hasTable('categories') && !Schema::hasColumn('product_types', 'category_id')) {
            Schema::table('product_types', static function (Blueprint $table): void {
                $table->unsignedBigInteger('category_id')->nullable()->after('id');
                $table->index(['category_id', 'status', 'sort_order'], 'product_types_category_status_sort_index');
                $table->foreign('category_id', 'product_types_category_id_foreign')
                    ->references('id')->on('categories')->nullOnDelete();
            });
        }

        foreach (['product_spec_values', 'product_variant_spec_values'] as $tableName) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'value_json')) continue;
            Schema::table($tableName, static function (Blueprint $table): void {
                $table->longText('value_json')->nullable()->after('value_detail');
            });
        }

        $this->backfillTypeCategories();
        $this->syncProductCategoriesFromType();
    }

    public function down(): void
    {
        foreach (['product_variant_spec_values', 'product_spec_values'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'value_json')) {
                Schema::table($tableName, static function (Blueprint $table): void {
                    $table->dropColumn('value_json');
                });
            }
        }

        if (Schema::hasTable('product_types') && Schema::hasColumn('product_types', 'category_id')) {
            Schema::table('product_types', static function (Blueprint $table): void {
                try { $table->dropForeign('product_types_category_id_foreign'); } catch (Throwable) {}
                try { $table->dropIndex('product_types_category_status_sort_index'); } catch (Throwable) {}
                $table->dropColumn('category_id');
            });
        }
    }

    private function backfillTypeCategories(): void
    {
        if (!Schema::hasTable('product_types') || !Schema::hasTable('categories') || !Schema::hasColumn('product_types', 'category_id')) return;

        $categories = DB::table('categories')->get(['id', 'name', 'slug']);
        $bySlug = [];
        $byName = [];
        foreach ($categories as $category) {
            $slug = Str::slug(trim((string) ($category->slug ?: $category->name)));
            if ($slug !== '' && !isset($bySlug[$slug])) $bySlug[$slug] = (int) $category->id;
            $name = mb_strtolower(trim((string) $category->name));
            if ($name !== '' && !isset($byName[$name])) $byName[$name] = (int) $category->id;
        }

        $canReadAssignments = Schema::hasTable('products') && Schema::hasTable('product_categories');

        DB::table('product_types')->whereNull('category_id')->orderBy('id')->chunkById(100, function ($types) use ($bySlug, $byName, $canReadAssignments): void {
            foreach ($types as $type) {
                $slug = Str::slug(trim((string) ($type->slug ?: $type->name)));
                $name = mb_strtolower(trim((string) $type->name));
                $categoryId = $bySlug[$slug] ?? $byName[$name] ?? null;

                // Kada naziv nije potpuno isti, bezbedno preuzmi postojeću vezu samo
                // ako svi artikli tog tipa već koriste jednu jedinu kategoriju.
                if ($categoryId === null && $canReadAssignments) {
                    $assigned = DB::table('products')
                        ->join('product_categories', 'product_categories.product_id', '=', 'products.id')
                        ->where('products.product_type_id', $type->id)
                        ->distinct()
                        ->pluck('product_categories.category_id')
                        ->map(static fn ($id): int => (int) $id)
                        ->values();
                    if ($assigned->count() === 1) $categoryId = $assigned->first();
                }

                if ($categoryId !== null) DB::table('product_types')->where('id', $type->id)->update(['category_id' => $categoryId]);
            }
        });
    }

    private function syncProductCategoriesFromType(): void
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('product_types') || !Schema::hasTable('product_categories') || !Schema::hasColumn('product_types', 'category_id')) return;

        DB::table('products as products')
            ->join('product_types as types', 'types.id', '=', 'products.product_type_id')
            ->whereNotNull('types.category_id')
            ->orderBy('products.id')
            ->select(['products.id', 'types.category_id'])
            ->chunkById(250, function ($rows): void {
                $productIds = $rows->pluck('id')->map(static fn ($id): int => (int) $id)->all();
                if ($productIds === []) return;
                DB::table('product_categories')->whereIn('product_id', $productIds)->delete();
                DB::table('product_categories')->insertOrIgnore($rows->map(static fn ($row): array => [
                    'product_id' => (int) $row->id,
                    'category_id' => (int) $row->category_id,
                ])->all());
            }, 'products.id', 'id');
    }
};
