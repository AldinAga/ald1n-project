<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('order_document_items')) {
            Schema::create('order_document_items', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('order_document_id');
                $table->unsignedSmallInteger('sequence_no');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('product_sku', 190)->nullable();
                $table->string('product_name', 255);
                $table->unsignedInteger('quantity');
                $table->decimal('unit_price_rsd', 14, 2);
                $table->decimal('line_total_rsd', 14, 2);
                $table->timestamps();
                $table->foreign('order_document_id', 'order_document_items_document_fk')->references('id')->on('order_documents')->cascadeOnDelete();
                $table->unique(['order_document_id', 'sequence_no'], 'document_item_sequence_unique');
                $table->index('product_id', 'order_document_items_product_index');
            });
        }

        if (!Schema::hasTable('order_documents') || !Schema::hasTable('order_items')) return;

        DB::table('order_documents')->orderBy('id')->chunkById(100, static function ($documents): void {
            foreach ($documents as $document) {
                if (DB::table('order_document_items')->where('order_document_id', $document->id)->exists()) continue;
                $items = DB::table('order_items')->where('order_id', $document->order_id)->orderBy('id')->get();
                $rows = [];
                foreach ($items as $index => $item) {
                    $rows[] = [
                        'order_document_id' => (int) $document->id,
                        'sequence_no' => $index + 1,
                        'product_id' => $item->product_id !== null ? (int) $item->product_id : null,
                        'product_sku' => $item->product_sku,
                        'product_name' => (string) $item->product_name,
                        'quantity' => (int) $item->quantity,
                        'unit_price_rsd' => $item->unit_price_rsd,
                        'line_total_rsd' => $item->line_total_rsd,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                if ($rows !== []) DB::table('order_document_items')->insert($rows);
            }
        });
    }

    public function down(): void
    {
        // Audit-preserving snapshot table: automatic destructive rollback is intentionally disabled.
    }
};
