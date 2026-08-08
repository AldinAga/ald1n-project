<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->createIfMissing('bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 120);
            $table->string('recipient_name', 70);
            $table->string('recipient_address', 70)->nullable();
            $table->char('account_number', 18)->unique();
            $table->string('account_number_display', 30);
            $table->char('payment_code', 3)->default('221');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps(6);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['is_active', 'updated_at', 'id']);
        });

        $this->createIfMissing('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['new', 'processing', 'confirmed', 'shipped', 'cancelled'])->default('new');
            $table->string('shipping_full_name', 190);
            $table->string('shipping_address');
            $table->string('shipping_city', 120);
            $table->string('shipping_postal_code', 20);
            $table->string('shipping_phone', 40);
            $table->decimal('subtotal_rsd', 14, 2);
            $table->decimal('eur_rsd_rate', 12, 6)->nullable();
            $table->text('customer_note')->nullable();
            $table->enum('payment_method', ['cash_on_delivery', 'bank_transfer'])->default('cash_on_delivery');
            $table->enum('payment_status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('bank_account_label_snapshot', 120)->nullable();
            $table->char('bank_account_number_snapshot', 18)->nullable();
            $table->string('bank_account_number_display_snapshot', 30)->nullable();
            $table->string('payment_recipient_name_snapshot', 70)->nullable();
            $table->string('payment_recipient_address_snapshot', 70)->nullable();
            $table->char('payment_code_snapshot', 3)->nullable();
            $table->string('payment_purpose_snapshot', 35)->nullable();
            $table->string('payment_reference_snapshot', 25)->nullable();
            $table->string('tracking_number', 120)->nullable();
            $table->dateTime('tracking_updated_at')->nullable();
            $table->unsignedBigInteger('tracking_updated_by')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
            $table->foreign('tracking_updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['payment_method', 'payment_status']);
            $table->index('bank_account_id');
            $table->index('tracking_number');
        });

        $this->createIfMissing('order_status_history', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->enum('old_status', ['new', 'processing', 'confirmed', 'shipped', 'cancelled'])->nullable();
            $table->enum('new_status', ['new', 'processing', 'confirmed', 'shipped', 'cancelled']);
            $table->string('note', 1000)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['order_id', 'created_at']);
        });

        $this->createIfMissing('order_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_sku', 100);
            $table->string('product_name', 190);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price_original', 12, 2);
            $table->enum('original_currency', ['RSD', 'EUR']);
            $table->decimal('unit_price_rsd', 14, 2);
            $table->decimal('line_total_rsd', 14, 2);
            $table->enum('commission_source_snapshot', ['automatic', 'manual'])->default('automatic');
            $table->decimal('commission_rate_percent_snapshot', 5, 2)->nullable();
            $table->decimal('commission_unit_eur_snapshot', 12, 2)->default(20);
            $table->decimal('commission_total_eur_snapshot', 14, 2)->default(20);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->index('order_id');
        });

        $this->createIfMissing('order_commissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->decimal('total_eur', 14, 2);
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->string('status_note', 1000)->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('paid_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        $this->createIfMissing('commission_status_history', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('commission_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->enum('old_status', ['pending', 'approved', 'paid', 'cancelled'])->nullable();
            $table->enum('new_status', ['pending', 'approved', 'paid', 'cancelled']);
            $table->string('note', 1000)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('commission_id')->references('id')->on('order_commissions')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['order_id', 'created_at']);
            $table->index(['commission_id', 'created_at']);
        });

        $this->createIfMissing('order_ips_qr', function (Blueprint $table): void {
            $table->unsignedBigInteger('order_id')->primary();
            $table->enum('status', ['pending', 'ready', 'failed'])->default('pending');
            $table->text('payload_text');
            $table->binary('qr_png')->nullable();
            $table->string('error_message', 500)->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->timestamps();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->index('status');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) && Schema::hasColumn('order_ips_qr', 'qr_png')) {
            DB::statement('ALTER TABLE order_ips_qr MODIFY qr_png MEDIUMBLOB NULL');
        }

        $this->createIfMissing('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('movement_type', ['initial', 'purchase', 'sale', 'return', 'manual_adjustment', 'cancelled_order']);
            $table->integer('quantity_change');
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['product_id', 'created_at']);
        });

        $this->createIfMissing('legacy_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 120);
            $table->string('entity_type', 80)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('action');
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });

        $this->createIfMissing('exchange_rate_history', function (Blueprint $table): void {
            $table->id();
            $table->decimal('old_rate', 12, 6)->nullable();
            $table->decimal('new_rate', 12, 6)->nullable();
            $table->enum('mode', ['manual', 'auto']);
            $table->string('provider', 80);
            $table->string('source')->nullable();
            $table->date('provider_date')->nullable();
            $table->string('triggered_by', 40);
            $table->enum('status', ['success', 'failed']);
            $table->string('message', 500)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index('created_at');
            $table->index('status');
            $table->index('provider_date');
        });
    }

    private function createIfMissing(string $table, \Closure $definition): void
    {
        if (!Schema::hasTable($table)) {
            Schema::create($table, $definition);
        }
    }

    public function down(): void
    {
        foreach ([
            'exchange_rate_history', 'legacy_audit_logs', 'stock_movements', 'order_ips_qr', 'commission_status_history',
            'order_commissions', 'order_items', 'order_status_history', 'orders', 'bank_accounts',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
