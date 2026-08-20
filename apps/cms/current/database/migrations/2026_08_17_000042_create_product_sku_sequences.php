<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_sku_sequences')) {
            Schema::create('product_sku_sequences', function (Blueprint $table): void {
                $table->unsignedTinyInteger('id')->primary();
                $table->unsignedBigInteger('current_value')->default(0);
                $table->timestamps();
            });
        }

        $maxProductId = Schema::hasTable('products')
            ? (int) (DB::table('products')->max('id') ?? 0)
            : 0;

        $maxExistingSequence = 0;
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'sku')) {
            foreach (DB::table('products')->pluck('sku') as $sku) {
                if (preg_match('/-(\d{6})$/', (string) $sku, $matches) === 1) {
                    $maxExistingSequence = max($maxExistingSequence, (int) $matches[1]);
                }
            }
        }

        $initialValue = max($maxProductId, $maxExistingSequence);
        $now = now();

        DB::table('product_sku_sequences')->updateOrInsert(
            ['id' => 1],
            [
                'current_value' => $initialValue,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sku_sequences');
    }
};
