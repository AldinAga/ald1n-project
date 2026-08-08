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
        $this->createActions();
        $this->createActionItems();
        $this->extendPayments();
        $this->seedPermission();
    }

    public function down(): void
    {
        if (Schema::hasTable('order_payments') && Schema::hasColumn('order_payments', 'after_sales_action_id')) {
            Schema::table('order_payments', static function (Blueprint $table): void {
                try { $table->dropForeign('order_payments_after_sales_action_foreign'); } catch (\Throwable) {}
                try { $table->dropUnique('order_payments_after_sales_action_unique'); } catch (\Throwable) {}
                $table->dropColumn('after_sales_action_id');
            });
        }

        Schema::dropIfExists('after_sales_action_items');
        Schema::dropIfExists('after_sales_actions');
    }

    private function createActions(): void
    {
        if (Schema::hasTable('after_sales_actions')) return;

        Schema::create('after_sales_actions', static function (Blueprint $table): void {
            $table->id();
            $table->string('action_number', 50)->unique();
            $table->unsignedBigInteger('after_sales_case_id');
            $table->string('action_type', 40);
            $table->string('status', 30)->default('planned');
            $table->string('inventory_handling', 20)->default('none');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->unsignedBigInteger('started_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->decimal('amount_rsd', 15, 2)->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('reference', 190)->nullable();
            $table->text('public_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->text('completion_note')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['after_sales_case_id', 'status', 'due_at'], 'after_sales_action_case_status_index');
            $table->index(['assigned_to', 'status', 'due_at'], 'after_sales_action_assignee_index');
            $table->foreign('after_sales_case_id')->references('id')->on('after_sales_cases')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('started_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('completed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function createActionItems(): void
    {
        if (Schema::hasTable('after_sales_action_items')) return;

        Schema::create('after_sales_action_items', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('after_sales_action_id');
            $table->unsignedBigInteger('after_sales_case_item_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('sku_snapshot', 120)->nullable();
            $table->string('product_name_snapshot', 255);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('disposition', 30)->default('none');
            $table->string('stock_effect', 20)->default('none');
            $table->unsignedBigInteger('stock_movement_id')->nullable();
            $table->timestamps();

            $table->unique(['after_sales_action_id', 'after_sales_case_item_id'], 'after_sales_action_item_unique');
            $table->foreign('after_sales_action_id')->references('id')->on('after_sales_actions')->cascadeOnDelete();
            $table->foreign('after_sales_case_item_id')->references('id')->on('after_sales_case_items')->restrictOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('stock_movement_id')->references('id')->on('stock_movements')->nullOnDelete();
        });
    }

    private function extendPayments(): void
    {
        if (!Schema::hasTable('order_payments')) return;

        if (!Schema::hasColumn('order_payments', 'after_sales_action_id')) {
            Schema::table('order_payments', static function (Blueprint $table): void {
                $table->unsignedBigInteger('after_sales_action_id')->nullable()->after('order_id');
                $table->unique('after_sales_action_id', 'order_payments_after_sales_action_unique');
                $table->foreign('after_sales_action_id', 'order_payments_after_sales_action_foreign')
                    ->references('id')->on('after_sales_actions')->nullOnDelete();
            });
        }
    }

    private function seedPermission(): void
    {
        if (!Schema::hasTable('permissions')) return;

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'after_sales.execute'],
            [
                'name' => 'Izvršenje postprodajnih radnji',
                'description' => 'Planiranje i izvršenje servisa, zamene, povrata robe i refundacije iz odobrenog postprodajnog slučaja.',
                'sort_order' => 109,
                'created_at' => now(),
            ],
        );
    }
};
