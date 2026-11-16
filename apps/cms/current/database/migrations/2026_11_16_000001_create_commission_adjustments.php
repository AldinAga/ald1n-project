<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Storno provizije pri povratu artikla, zaduženja i prebijanje zaduženja pri isplati.
// Bez stranih ključeva (legacy tabele), samo indeksi.
// ROLLBACK: down() ODBIJA da radi dok postoji i jedan red u commission_adjustments, jer bi se izgubila istorija
// storniranja (order_commissions.total_eur je već umanjen i ne vraća se automatski). Ako je rollback neophodan,
// prvo ručno vrati total_eur iz commission_adjustments (kind = reversal, status = confirmed), pa isprazni tabelu.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('commission_adjustments')) {
            Schema::create('commission_adjustments', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('commission_id');
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('order_item_id')->nullable();
                // reversal = umanjuje neisplaćenu proviziju; debit = zaduženje na već isplaćenu proviziju
                $table->string('kind', 20);
                // confirmed = važi; proposed = predlog zaduženja, ne važi dok SuperAdmin ne potvrdi; rejected = odbijen predlog
                $table->string('status', 20)->default('confirmed');
                $table->decimal('amount_eur', 12, 2);
                $table->unsignedInteger('quantity')->nullable();
                $table->string('reason', 1000);
                $table->string('source', 30);
                $table->unsignedBigInteger('after_sales_action_item_id')->nullable()->unique('commission_adjustments_action_item_unique');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('decided_by')->nullable();
                $table->dateTime('decided_at')->nullable();
                $table->string('decision_note', 500)->nullable();
                $table->timestamps();
                $table->index(['commission_id', 'kind'], 'commission_adjustments_commission_kind_index');
                $table->index('order_item_id', 'commission_adjustments_order_item_index');
                $table->index(['kind', 'status'], 'commission_adjustments_kind_status_index');
            });
        }

        if (!Schema::hasTable('commission_debit_settlements')) {
            Schema::create('commission_debit_settlements', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('adjustment_id');
                $table->unsignedBigInteger('batch_id');
                $table->decimal('amount_eur', 12, 2);
                $table->timestamps();
                $table->index('adjustment_id', 'commission_debit_settlements_adjustment_index');
                $table->index('batch_id', 'commission_debit_settlements_batch_index');
            });
        }

        if (Schema::hasTable('order_commissions') && !Schema::hasColumn('order_commissions', 'auto_approved_at')) {
            Schema::table('order_commissions', static function (Blueprint $table): void {
                $table->dateTime('auto_approved_at')->nullable();
            });
        }
        if (Schema::hasTable('commission_payment_batches') && !Schema::hasColumn('commission_payment_batches', 'debit_eur')) {
            Schema::table('commission_payment_batches', static function (Blueprint $table): void {
                $table->decimal('debit_eur', 12, 2)->default(0);
                $table->decimal('net_eur', 12, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('commission_adjustments') && DB::table('commission_adjustments')->exists()) {
            throw new RuntimeException('Rollback odbijen: commission_adjustments sadrži istoriju storno i zaduženja. Vidi komentar u migraciji.');
        }
        Schema::dropIfExists('commission_debit_settlements');
        Schema::dropIfExists('commission_adjustments');
        if (Schema::hasColumn('order_commissions', 'auto_approved_at')) {
            Schema::table('order_commissions', static function (Blueprint $table): void {
                $table->dropColumn('auto_approved_at');
            });
        }
        if (Schema::hasColumn('commission_payment_batches', 'debit_eur')) {
            Schema::table('commission_payment_batches', static function (Blueprint $table): void {
                $table->dropColumn(['debit_eur', 'net_eur']);
            });
        }
    }
};
