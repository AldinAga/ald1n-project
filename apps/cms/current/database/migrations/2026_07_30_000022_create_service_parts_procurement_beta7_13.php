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
        $this->createSuppliers();
        $this->createParts();
        $this->createWorkOrderParts();
        $this->createMovements();
        $this->createPurchaseRequests();
        $this->createPurchaseRequestItems();
        $this->seedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('service_part_purchase_request_items');
        Schema::dropIfExists('service_part_purchase_requests');
        Schema::dropIfExists('service_part_movements');
        Schema::dropIfExists('field_work_order_parts');
        Schema::dropIfExists('service_parts');
        Schema::dropIfExists('service_part_suppliers');
    }

    private function createSuppliers(): void
    {
        if (Schema::hasTable('service_part_suppliers')) return;
        Schema::create('service_part_suppliers', static function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 190);
            $table->string('contact_person', 190)->nullable();
            $table->string('phone', 80)->nullable();
            $table->string('email', 190)->nullable();
            $table->text('address')->nullable();
            $table->unsignedInteger('lead_time_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'name'], 'service_part_suppliers_active_name_index');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function createParts(): void
    {
        if (Schema::hasTable('service_parts')) return;
        Schema::create('service_parts', static function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 80)->unique();
            $table->string('name', 190);
            $table->string('unit', 30)->default('kom');
            $table->decimal('stock_quantity', 15, 3)->default(0);
            $table->decimal('reserved_quantity', 15, 3)->default(0);
            $table->decimal('minimum_quantity', 15, 3)->default(0);
            $table->decimal('average_cost_rsd', 15, 2)->default(0);
            $table->unsignedBigInteger('preferred_supplier_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'name'], 'service_parts_active_name_index');
            $table->index(['stock_quantity', 'minimum_quantity'], 'service_parts_stock_threshold_index');
            $table->foreign('preferred_supplier_id')->references('id')->on('service_part_suppliers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function createWorkOrderParts(): void
    {
        if (Schema::hasTable('field_work_order_parts')) return;
        Schema::create('field_work_order_parts', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('field_work_order_id');
            $table->unsignedBigInteger('service_part_id')->nullable();
            $table->string('supply_mode', 30)->default('local_stock');
            $table->string('part_sku_snapshot', 80);
            $table->string('part_name_snapshot', 190);
            $table->string('unit_snapshot', 30)->default('kom');
            $table->decimal('requested_quantity', 15, 3);
            $table->decimal('reserved_quantity', 15, 3)->default(0);
            $table->decimal('consumed_quantity', 15, 3)->default(0);
            $table->decimal('unit_cost_snapshot_rsd', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['field_work_order_id', 'service_part_id'], 'field_work_order_part_unique');
            $table->index(['field_work_order_id', 'supply_mode'], 'field_work_order_parts_mode_index');
            $table->foreign('field_work_order_id')->references('id')->on('field_work_orders')->cascadeOnDelete();
            $table->foreign('service_part_id')->references('id')->on('service_parts')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function createMovements(): void
    {
        if (Schema::hasTable('service_part_movements')) return;
        Schema::create('service_part_movements', static function (Blueprint $table): void {
            $table->id();
            $table->string('event_key', 190)->unique();
            $table->unsignedBigInteger('service_part_id');
            $table->unsignedBigInteger('field_work_order_id')->nullable();
            $table->unsignedBigInteger('purchase_request_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('movement_type', 40);
            $table->decimal('stock_change', 15, 3)->default(0);
            $table->decimal('reserved_change', 15, 3)->default(0);
            $table->decimal('stock_before', 15, 3);
            $table->decimal('stock_after', 15, 3);
            $table->decimal('reserved_before', 15, 3);
            $table->decimal('reserved_after', 15, 3);
            $table->decimal('unit_cost_rsd', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['service_part_id', 'created_at'], 'service_part_movements_part_date_index');
            $table->index(['field_work_order_id', 'movement_type'], 'service_part_movements_work_order_index');
            $table->foreign('service_part_id')->references('id')->on('service_parts')->cascadeOnDelete();
            $table->foreign('field_work_order_id')->references('id')->on('field_work_orders')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function createPurchaseRequests(): void
    {
        if (Schema::hasTable('service_part_purchase_requests')) return;
        Schema::create('service_part_purchase_requests', static function (Blueprint $table): void {
            $table->id();
            $table->string('request_number', 60)->unique();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('supplier_reference', 190)->nullable();
            $table->date('expected_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->decimal('total_cost_rsd', 15, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['status', 'expected_at'], 'service_part_purchase_status_expected_index');
            $table->foreign('supplier_id')->references('id')->on('service_part_suppliers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function createPurchaseRequestItems(): void
    {
        if (Schema::hasTable('service_part_purchase_request_items')) return;
        Schema::create('service_part_purchase_request_items', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('purchase_request_id');
            $table->unsignedBigInteger('service_part_id');
            $table->decimal('ordered_quantity', 15, 3);
            $table->decimal('received_quantity', 15, 3)->default(0);
            $table->decimal('unit_cost_rsd', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['purchase_request_id', 'service_part_id'], 'service_part_purchase_item_unique');
            $table->foreign('purchase_request_id')->references('id')->on('service_part_purchase_requests')->cascadeOnDelete();
            $table->foreign('service_part_id')->references('id')->on('service_parts')->restrictOnDelete();
        });
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) return;
        foreach ([
            ['slug' => 'service_parts.view', 'name' => 'Pregled servisnog lagera', 'description' => 'Pregled rezervnih delova, rezervacija i kretanja.', 'sort_order' => 113],
            ['slug' => 'service_parts.manage', 'name' => 'Upravljanje servisnim lagerom', 'description' => 'Kreiranje delova, korekcije, rezervacije i utrošak na radnim nalozima.', 'sort_order' => 114],
            ['slug' => 'service_parts.procurement', 'name' => 'Nabavka servisnih delova', 'description' => 'Dobavljači, zahtevi za nabavku i prijem rezervnih delova.', 'sort_order' => 115],
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(['slug' => $permission['slug']], $permission + ['created_at' => now()]);
        }
    }
};
