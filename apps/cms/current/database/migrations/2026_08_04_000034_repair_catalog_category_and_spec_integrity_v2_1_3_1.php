<?php

declare(strict_types=1);

use App\Services\ProductTypeCategoryService;
use App\Services\SpecificationFieldLifecycleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('product_types')
            && Schema::hasTable('categories')
            && Schema::hasColumn('product_types', 'category_id')
        ) {
            app(ProductTypeCategoryService::class)->ensureAll();
        }

        if (Schema::hasTable('specification_fields')) {
            app(SpecificationFieldLifecycleService::class)->repairIntegrity();
        }
    }

    public function down(): void
    {
        // Data-integrity repair is intentionally not reversed.
    }
};
