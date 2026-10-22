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
        if (Schema::hasTable('warranty_issuances')) {
            // Never accept a partial or unrelated table as the required ledger.
            foreach (['order_id', 'order_item_id', 'first_verified_payment_id', 'warranty_number',
                'status', 'snapshot_json', 'activated_warranty_id', 'issued_at'] as $column) {
                if (!Schema::hasColumn('warranty_issuances', $column)) {
                    throw new RuntimeException('WARRANTY_ISSUANCES_SCHEMA_INCOMPLETE '.$column);
                }
            }
            return;
        }
        Schema::create('warranty_issuances', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_item_id')->unique();
            $table->unsignedBigInteger('first_verified_payment_id');
            $table->string('warranty_number', 60)->unique();
            $table->string('status', 30)->default('issued_pending_delivery');
            $table->json('snapshot_json');
            $table->unsignedBigInteger('activated_warranty_id')->nullable();
            $table->timestamp('issued_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
            $table->index(['first_verified_payment_id', 'status']);
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('order_item_id')->references('id')->on('order_items')->cascadeOnDelete();
            $table->foreign('first_verified_payment_id')->references('id')->on('order_payments')->restrictOnDelete();
            $table->foreign('activated_warranty_id')->references('id')->on('product_warranties')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('warranty_issuances')) return;
        if (DB::table('warranty_issuances')->exists()) {
            throw new RuntimeException('WARRANTY_ISSUANCES_HAS_EVIDENCE: do not delete issued certificates');
        }
        Schema::drop('warranty_issuances');
    }
};
