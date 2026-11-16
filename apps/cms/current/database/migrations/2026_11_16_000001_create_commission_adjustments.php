<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Storno provizije pri povratu artikla: jedan red po storniranju. Bez stranih ključeva (legacy tabele),
// samo indeksi. Rollback samo briše ovu tabelu; order_commissions se ne dira.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commission_adjustments')) {
            return;
        }

        Schema::create('commission_adjustments', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('commission_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_item_id')->nullable();
            // reversal = umanjuje neisplaćenu proviziju; debit = zaduženje na već isplaćenu proviziju
            $table->string('kind', 20);
            $table->decimal('amount_eur', 12, 2);
            $table->unsignedInteger('quantity')->nullable();
            $table->string('reason', 1000);
            $table->string('source', 30);
            $table->unsignedBigInteger('after_sales_action_item_id')->nullable()->unique('commission_adjustments_action_item_unique');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['commission_id', 'kind'], 'commission_adjustments_commission_kind_index');
            $table->index('order_item_id', 'commission_adjustments_order_item_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_adjustments');
    }
};
