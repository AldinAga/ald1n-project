<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('brand_product_type')) {
            Schema::create('brand_product_type', static function (Blueprint $table): void {
                $table->unsignedBigInteger('brand_id');
                $table->unsignedBigInteger('product_type_id');
                $table->primary(['brand_id', 'product_type_id'], 'brand_product_type_primary');
                $table->index(['product_type_id', 'brand_id'], 'brand_product_type_type_brand_index');
                $table->foreign('brand_id', 'brand_product_type_brand_fk')->references('id')->on('brands')->cascadeOnDelete();
                $table->foreign('product_type_id', 'brand_product_type_type_fk')->references('id')->on('product_types')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('product_line_product_type')) {
            Schema::create('product_line_product_type', static function (Blueprint $table): void {
                $table->unsignedBigInteger('product_line_id');
                $table->unsignedBigInteger('product_type_id');
                $table->primary(['product_line_id', 'product_type_id'], 'product_line_product_type_primary');
                $table->index(['product_type_id', 'product_line_id'], 'product_line_product_type_type_line_index');
                $table->foreign('product_line_id', 'product_line_product_type_line_fk')->references('id')->on('product_lines')->cascadeOnDelete();
                $table->foreign('product_type_id', 'product_line_product_type_type_fk')->references('id')->on('product_types')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('products')) {
            DB::table('products')
                ->whereNotNull('brand_id')
                ->whereNotNull('product_type_id')
                ->select(['brand_id', 'product_type_id'])
                ->distinct()
                ->orderBy('brand_id')
                ->get()
                ->each(static function ($row): void {
                    DB::table('brand_product_type')->insertOrIgnore([
                        'brand_id' => (int) $row->brand_id,
                        'product_type_id' => (int) $row->product_type_id,
                    ]);
                });

            DB::table('products')
                ->whereNotNull('product_line_id')
                ->whereNotNull('product_type_id')
                ->select(['product_line_id', 'product_type_id'])
                ->distinct()
                ->orderBy('product_line_id')
                ->get()
                ->each(static function ($row): void {
                    DB::table('product_line_product_type')->insertOrIgnore([
                        'product_line_id' => (int) $row->product_line_id,
                        'product_type_id' => (int) $row->product_type_id,
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_line_product_type');
        Schema::dropIfExists('brand_product_type');
    }
};
