<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('receivable_payment_allocations')) {
            Schema::create('receivable_payment_allocations', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('receivable_case_id');
                $table->unsignedBigInteger('receivable_installment_id');
                $table->unsignedBigInteger('order_payment_id');
                $table->decimal('amount_rsd', 14, 2);
                $table->timestamps();

                $table->foreign('receivable_case_id', 'receivable_payment_allocations_case_fk')->references('id')->on('receivable_cases')->cascadeOnDelete();
                $table->foreign('receivable_installment_id', 'receivable_payment_allocations_installment_fk')->references('id')->on('receivable_installments')->cascadeOnDelete();
                $table->foreign('order_payment_id', 'receivable_payment_allocations_payment_fk')->references('id')->on('order_payments')->cascadeOnDelete();
                $table->unique(['order_payment_id', 'receivable_installment_id'], 'receivable_payment_allocations_payment_installment_unique');
                $table->index(['receivable_case_id', 'order_payment_id'], 'receivable_payment_allocations_case_payment_index');
                $table->index('receivable_installment_id', 'receivable_payment_allocations_installment_index');
            });
            return;
        }

        $columns = [
            'receivable_case_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('receivable_case_id')->nullable(),
            'receivable_installment_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('receivable_installment_id')->nullable(),
            'order_payment_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('order_payment_id')->nullable(),
            'amount_rsd' => static fn (Blueprint $table) => $table->decimal('amount_rsd', 14, 2)->default(0),
            'created_at' => static fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => static fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];
        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn('receivable_payment_allocations', $column)) continue;
            Schema::table('receivable_payment_allocations', static function (Blueprint $table) use ($definition): void { $definition($table); });
        }
        $this->ensureIndex('receivable_payment_allocations_payment_installment_unique', static fn (Blueprint $table) => $table->unique(['order_payment_id', 'receivable_installment_id'], 'receivable_payment_allocations_payment_installment_unique'));
        $this->ensureIndex('receivable_payment_allocations_case_payment_index', static fn (Blueprint $table) => $table->index(['receivable_case_id', 'order_payment_id'], 'receivable_payment_allocations_case_payment_index'));
        $this->ensureIndex('receivable_payment_allocations_installment_index', static fn (Blueprint $table) => $table->index('receivable_installment_id', 'receivable_payment_allocations_installment_index'));
        $this->ensureForeign('receivable_payment_allocations_case_fk', 'receivable_case_id', 'receivable_cases');
        $this->ensureForeign('receivable_payment_allocations_installment_fk', 'receivable_installment_id', 'receivable_installments');
        $this->ensureForeign('receivable_payment_allocations_payment_fk', 'order_payment_id', 'order_payments');
    }

    public function down(): void
    {
        // Finansijski allocation ledger je audit istorija i namerno se ne briše rollback-om.
    }

    /** @param callable(Blueprint):mixed $definition */
    private function ensureIndex(string $name, callable $definition): void
    {
        try {
            foreach (Schema::getIndexes('receivable_payment_allocations') as $index) {
                if (($index['name'] ?? null) === $name) return;
            }
        } catch (Throwable) {
        }
        Schema::table('receivable_payment_allocations', static function (Blueprint $table) use ($definition): void { $definition($table); });
    }

    private function ensureForeign(string $name, string $column, string $target): void
    {
        if (!Schema::hasTable($target)) return;
        try {
            foreach (Schema::getForeignKeys('receivable_payment_allocations') as $foreign) {
                if (($foreign['name'] ?? null) === $name) return;
            }
        } catch (Throwable) {
        }
        Schema::table('receivable_payment_allocations', static function (Blueprint $table) use ($name, $column, $target): void {
            $table->foreign($column, $name)->references('id')->on($target)->cascadeOnDelete();
        });
    }
};
