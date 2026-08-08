<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->extendOrders();
        $this->createOrderPayments();
        $this->createStockReceipts();
        $this->createInventoryCounts();
        $this->extendStockMovements();
        $this->seedPermissions();
        $this->backfillPaymentState();
    }

    public function down(): void
    {
        // Production recovery migration: existing financial and inventory history is intentionally preserved.
    }

    private function extendOrders(): void
    {
        if (!Schema::hasTable('orders')) return;

        $missing = [
            'payment_state' => !Schema::hasColumn('orders', 'payment_state'),
            'paid_total_rsd' => !Schema::hasColumn('orders', 'paid_total_rsd'),
            'payment_due_at' => !Schema::hasColumn('orders', 'payment_due_at'),
            'payment_verified_at' => !Schema::hasColumn('orders', 'payment_verified_at'),
        ];

        if (in_array(true, $missing, true)) {
            Schema::table('orders', static function (Blueprint $table) use ($missing): void {
                if ($missing['payment_state']) $table->string('payment_state', 30)->default('unpaid');
                if ($missing['paid_total_rsd']) $table->decimal('paid_total_rsd', 14, 2)->default(0);
                if ($missing['payment_due_at']) $table->dateTime('payment_due_at')->nullable();
                if ($missing['payment_verified_at']) $table->dateTime('payment_verified_at')->nullable();
            });
        }

        try {
            Schema::table('orders', static function (Blueprint $table): void {
                $table->index(['payment_state', 'payment_due_at', 'created_at'], 'orders_payment_attention_index');
            });
        } catch (\Throwable) {
            // Index already exists on repaired installations.
        }
    }

    private function createOrderPayments(): void
    {
        if (Schema::hasTable('order_payments')) return;

        Schema::create('order_payments', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('payment_number', 50)->unique();
            $table->string('entry_type', 20)->default('payment');
            $table->string('status', 20)->default('submitted');
            $table->decimal('amount_rsd', 14, 2);
            $table->string('payment_method', 50);
            $table->dateTime('paid_at')->nullable();
            $table->string('reference', 190)->nullable();
            $table->text('note')->nullable();
            $table->string('proof_path', 500)->nullable();
            $table->string('proof_original_name', 255)->nullable();
            $table->string('proof_mime_type', 100)->nullable();
            $table->unsignedBigInteger('proof_size_bytes')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->string('rejection_reason', 1000)->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('voided_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['order_id', 'status', 'created_at']);
            $table->index(['status', 'paid_at']);
            $table->index(['payment_method', 'status']);
        });
    }

    private function createStockReceipts(): void
    {
        if (!Schema::hasTable('stock_receipts')) {
            Schema::create('stock_receipts', static function (Blueprint $table): void {
                $table->id();
                $table->string('receipt_number', 50)->unique();
                $table->string('status', 20)->default('draft');
                $table->string('supplier_name', 190)->nullable();
                $table->string('supplier_document_number', 100)->nullable();
                $table->date('received_on');
                $table->text('note')->nullable();
                $table->unsignedInteger('total_units')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->dateTime('posted_at')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->dateTime('cancelled_at')->nullable();
                $table->timestamps();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('posted_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'received_on']);
            });
        }

        if (!Schema::hasTable('stock_receipt_items')) {
            Schema::create('stock_receipt_items', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('stock_receipt_id');
                $table->unsignedBigInteger('product_id');
                $table->string('product_sku', 100);
                $table->string('product_name', 190);
                $table->unsignedInteger('quantity');
                $table->decimal('unit_cost_rsd', 14, 2)->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
                $table->foreign('stock_receipt_id')->references('id')->on('stock_receipts')->cascadeOnDelete();
                $table->foreign('product_id')->references('id')->on('products');
                $table->unique(['stock_receipt_id', 'product_id'], 'stock_receipt_product_unique');
                $table->index(['product_id', 'created_at']);
            });
        }
    }

    private function createInventoryCounts(): void
    {
        if (!Schema::hasTable('inventory_counts')) {
            Schema::create('inventory_counts', static function (Blueprint $table): void {
                $table->id();
                $table->string('count_number', 50)->unique();
                $table->string('status', 20)->default('draft');
                $table->string('scope_label', 190)->nullable();
                $table->date('counted_on');
                $table->text('note')->nullable();
                $table->integer('total_variance')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('finalized_by')->nullable();
                $table->dateTime('finalized_at')->nullable();
                $table->timestamps();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('finalized_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'counted_on']);
            });
        }

        if (!Schema::hasTable('inventory_count_items')) {
            Schema::create('inventory_count_items', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('inventory_count_id');
                $table->unsignedBigInteger('product_id');
                $table->string('product_sku', 100);
                $table->string('product_name', 190);
                $table->unsignedInteger('system_quantity');
                $table->unsignedInteger('counted_quantity');
                $table->integer('variance');
                $table->text('note')->nullable();
                $table->timestamps();
                $table->foreign('inventory_count_id')->references('id')->on('inventory_counts')->cascadeOnDelete();
                $table->foreign('product_id')->references('id')->on('products');
                $table->unique(['inventory_count_id', 'product_id'], 'inventory_count_product_unique');
                $table->index(['product_id', 'variance']);
            });
        }
    }

    private function extendStockMovements(): void
    {
        if (!Schema::hasTable('stock_movements')) return;

        $missing = [
            'stock_receipt_id' => !Schema::hasColumn('stock_movements', 'stock_receipt_id'),
            'inventory_count_id' => !Schema::hasColumn('stock_movements', 'inventory_count_id'),
        ];
        if (in_array(true, $missing, true)) {
            Schema::table('stock_movements', static function (Blueprint $table) use ($missing): void {
                if ($missing['stock_receipt_id']) $table->unsignedBigInteger('stock_receipt_id')->nullable();
                if ($missing['inventory_count_id']) $table->unsignedBigInteger('inventory_count_id')->nullable();
            });
        }

        foreach ([['stock_receipt_id', 'stock_receipts'], ['inventory_count_id', 'inventory_counts']] as [$column, $target]) {
            if (!Schema::hasColumn('stock_movements', $column)) continue;
            try {
                Schema::table('stock_movements', static function (Blueprint $table) use ($column, $target): void {
                    $table->foreign($column)->references('id')->on($target)->nullOnDelete();
                });
            } catch (\Throwable) {
                // Foreign key already exists.
            }
        }
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) return;

        $rows = [
            ['name' => 'Upravljanje uplatama', 'slug' => 'payments.manage', 'description' => 'Verifikacija uplata, refundacije i pregled dokaza.', 'sort_order' => 180],
            ['name' => 'Slanje potvrde o uplati', 'slug' => 'payments.upload_proof', 'description' => 'Korisnik šalje potvrdu o uplati za svoju porudžbinu.', 'sort_order' => 49],
            ['name' => 'Pregled svojih uplata', 'slug' => 'payments.view_own', 'description' => 'Korisnik prati uplate svoje porudžbine.', 'sort_order' => 50],
            ['name' => 'Prijem robe', 'slug' => 'inventory.receive', 'description' => 'Kreiranje i knjiženje ulaza robe.', 'sort_order' => 126],
            ['name' => 'Popis lagera', 'slug' => 'inventory.count', 'description' => 'Kreiranje i zaključivanje popisa lagera.', 'sort_order' => 127],
            ['name' => 'Izvoz lagera i uplata', 'slug' => 'inventory.export', 'description' => 'CSV izvoz lagera, ulaza, popisa i uplata.', 'sort_order' => 128],
        ];
        foreach ($rows as $row) {
            DB::table('permissions')->updateOrInsert(['slug' => $row['slug']], $row + ['created_at' => now()]);
        }

        if (!Schema::hasTable('user_group_permissions') || !Schema::hasTable('user_groups')) return;
        $groupIds = DB::table('user_groups')->where('status', 'active')->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('slug', ['payments.upload_proof', 'payments.view_own'])->pluck('id');
        foreach ($groupIds as $groupId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('user_group_permissions')->insertOrIgnore([
                    'group_id' => $groupId,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                ]);
            }
        }
    }

    private function backfillPaymentState(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'payment_state')) return;
        DB::table('orders')->where('payment_status', 'paid')->update([
            'payment_state' => 'paid',
            'paid_total_rsd' => DB::raw('subtotal_rsd'),
            'payment_verified_at' => DB::raw('COALESCE(payment_verified_at, updated_at, created_at)'),
        ]);
        DB::table('orders')->where('payment_status', 'cancelled')->update(['payment_state' => 'cancelled']);
    }
};
