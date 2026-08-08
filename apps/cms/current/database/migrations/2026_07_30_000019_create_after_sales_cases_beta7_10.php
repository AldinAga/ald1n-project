<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->createCases();
        $this->createCaseItems();
        $this->createMessages();
        $this->createAttachments();
        $this->createHistory();
        $this->seedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('after_sales_status_history');
        Schema::dropIfExists('after_sales_attachments');
        Schema::dropIfExists('after_sales_messages');
        Schema::dropIfExists('after_sales_case_items');
        Schema::dropIfExists('after_sales_cases');
    }

    private function createCases(): void
    {
        if (Schema::hasTable('after_sales_cases')) return;

        Schema::create('after_sales_cases', static function (Blueprint $table): void {
            $table->id();
            $table->string('case_number', 40)->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('opened_by');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('case_type', 40);
            $table->string('priority', 20)->default('normal');
            $table->string('status', 40)->default('open');
            $table->string('subject', 190);
            $table->text('description');
            $table->string('requested_resolution', 60)->nullable();
            $table->string('resolution_type', 60)->nullable();
            $table->text('resolution_summary')->nullable();
            $table->string('customer_name_snapshot', 190)->nullable();
            $table->string('customer_phone_snapshot', 60)->nullable();
            $table->string('customer_address_snapshot', 500)->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('first_response_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority', 'due_at'], 'after_sales_queue_index');
            $table->index(['assigned_to', 'status'], 'after_sales_assignee_index');
            $table->index(['order_id', 'created_at'], 'after_sales_order_index');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('opened_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function createCaseItems(): void
    {
        if (Schema::hasTable('after_sales_case_items')) return;

        Schema::create('after_sales_case_items', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('after_sales_case_id');
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('sku_snapshot', 120)->nullable();
            $table->string('product_name_snapshot', 255);
            $table->unsignedInteger('quantity')->default(1);
            $table->text('issue_description')->nullable();
            $table->timestamps();

            $table->unique(['after_sales_case_id', 'order_item_id'], 'after_sales_case_item_unique');
            $table->foreign('after_sales_case_id')->references('id')->on('after_sales_cases')->cascadeOnDelete();
            $table->foreign('order_item_id')->references('id')->on('order_items')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    private function createMessages(): void
    {
        if (Schema::hasTable('after_sales_messages')) return;

        Schema::create('after_sales_messages', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('after_sales_case_id');
            $table->unsignedBigInteger('user_id');
            $table->string('visibility', 20)->default('public');
            $table->text('body');
            $table->timestamps();

            $table->index(['after_sales_case_id', 'visibility', 'created_at'], 'after_sales_message_index');
            $table->foreign('after_sales_case_id')->references('id')->on('after_sales_cases')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    private function createAttachments(): void
    {
        if (Schema::hasTable('after_sales_attachments')) return;

        Schema::create('after_sales_attachments', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('after_sales_case_id');
            $table->unsignedBigInteger('message_id')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->string('disk', 40)->default('local');
            $table->string('path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->index(['after_sales_case_id', 'created_at'], 'after_sales_attachment_index');
            $table->foreign('after_sales_case_id')->references('id')->on('after_sales_cases')->cascadeOnDelete();
            $table->foreign('message_id')->references('id')->on('after_sales_messages')->nullOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->restrictOnDelete();
        });
    }

    private function createHistory(): void
    {
        if (Schema::hasTable('after_sales_status_history')) return;

        Schema::create('after_sales_status_history', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('after_sales_case_id');
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->unsignedBigInteger('actor_id');
            $table->text('note')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['after_sales_case_id', 'created_at'], 'after_sales_history_index');
            $table->foreign('after_sales_case_id')->references('id')->on('after_sales_cases')->cascadeOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) return;

        foreach ([
            ['slug' => 'after_sales.create', 'name' => 'Otvaranje reklamacije', 'description' => 'Otvaranje reklamacije, povrata ili servisnog zahteva za sopstvenu isporučenu porudžbinu.', 'sort_order' => 51],
            ['slug' => 'after_sales.view_own', 'name' => 'Pregled svojih reklamacija', 'description' => 'Pregled komunikacije, statusa i priloga sopstvenih postprodajnih slučajeva.', 'sort_order' => 52],
            ['slug' => 'after_sales.manage', 'name' => 'Upravljanje reklamacijama', 'description' => 'Obrada, dodela, odluka i zatvaranje reklamacija, povrata i servisnih zahteva.', 'sort_order' => 108],
        ] as $row) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $row['slug']],
                $row + ['created_at' => now()],
            );
        }
    }
};
