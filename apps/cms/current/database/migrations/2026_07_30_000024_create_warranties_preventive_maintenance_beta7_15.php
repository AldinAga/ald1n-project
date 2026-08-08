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
        $this->createRules();
        $this->createWarranties();
        $this->createMaintenanceRecords();
        $this->seedPermissions();
        $this->seedDefaultRule();
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_maintenance_records');
        Schema::dropIfExists('product_warranties');
        Schema::dropIfExists('warranty_rules');
    }

    private function createRules(): void
    {
        if (Schema::hasTable('warranty_rules')) return;

        Schema::create('warranty_rules', static function (Blueprint $table): void {
            $table->id();
            $table->string('name', 190);
            $table->string('scope_type', 30)->default('global');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedInteger('duration_months')->default(24);
            $table->unsignedInteger('maintenance_interval_months')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('terms')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['scope_type', 'is_active', 'priority'], 'warranty_rules_scope_active_priority_index');
            $table->index(['product_id', 'is_active'], 'warranty_rules_product_active_index');
            $table->index(['category_id', 'is_active'], 'warranty_rules_category_active_index');
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function createWarranties(): void
    {
        if (Schema::hasTable('product_warranties')) return;

        Schema::create('product_warranties', static function (Blueprint $table): void {
            $table->id();
            $table->string('warranty_number', 60)->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_item_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('warranty_rule_id')->nullable();
            $table->string('status', 30)->default('active');
            $table->date('starts_at');
            $table->date('expires_at');
            $table->unsignedInteger('duration_months');
            $table->unsignedInteger('maintenance_interval_months')->nullable();
            $table->date('last_maintenance_at')->nullable();
            $table->date('next_maintenance_at')->nullable();
            $table->string('customer_name_snapshot', 190);
            $table->string('customer_address_snapshot', 255)->nullable();
            $table->string('customer_city_snapshot', 190)->nullable();
            $table->string('customer_postal_code_snapshot', 40)->nullable();
            $table->string('customer_phone_snapshot', 80)->nullable();
            $table->string('product_sku_snapshot', 100)->nullable();
            $table->string('product_name_snapshot', 255);
            $table->unsignedInteger('quantity')->default(1);
            $table->json('serial_numbers_json')->nullable();
            $table->text('terms_snapshot')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->text('void_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique('order_item_id', 'product_warranties_order_item_unique');
            $table->index(['user_id', 'status', 'expires_at'], 'product_warranties_user_status_expiry_index');
            $table->index(['status', 'next_maintenance_at'], 'product_warranties_status_maintenance_index');
            $table->index(['order_id', 'status'], 'product_warranties_order_status_index');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('order_item_id')->references('id')->on('order_items')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('warranty_rule_id')->references('id')->on('warranty_rules')->nullOnDelete();
            $table->foreign('voided_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function createMaintenanceRecords(): void
    {
        if (Schema::hasTable('warranty_maintenance_records')) return;

        Schema::create('warranty_maintenance_records', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_warranty_id');
            $table->string('status', 30)->default('due');
            $table->date('due_at');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->string('service_reference', 190)->nullable();
            $table->text('result')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'due_at'], 'warranty_maintenance_status_due_index');
            $table->index(['product_warranty_id', 'status'], 'warranty_maintenance_warranty_status_index');
            $table->foreign('product_warranty_id')->references('id')->on('product_warranties')->cascadeOnDelete();
            $table->foreign('completed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) return;

        foreach ([
            ['slug' => 'warranties.view_own', 'name' => 'Pregled svojih garancija', 'description' => 'Pregled garantnih listova, rokova i preventivnog održavanja za sopstvene porudžbine.', 'sort_order' => 53],
            ['slug' => 'warranties.manage', 'name' => 'Upravljanje garancijama', 'description' => 'Pravila garancije, serijski brojevi, poništavanje i preventivno održavanje.', 'sort_order' => 116],
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(['slug' => $permission['slug']], $permission + ['created_at' => now()]);
        }
    }

    private function seedDefaultRule(): void
    {
        if (!Schema::hasTable('warranty_rules') || DB::table('warranty_rules')->exists()) return;

        DB::table('warranty_rules')->insert([
            'name' => 'Podrazumevana garancija',
            'scope_type' => 'global',
            'duration_months' => 24,
            'maintenance_interval_months' => null,
            'priority' => 0,
            'is_active' => true,
            'terms' => 'Garancija važi za nedostatke koji su obuhvaćeni garantnim uslovima prodavca. Sačuvajte garantni list i broj porudžbine.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
