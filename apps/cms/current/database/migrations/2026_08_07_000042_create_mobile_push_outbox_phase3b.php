<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mobile_push_outbox')) {
            return;
        }

        Schema::create('mobile_push_outbox', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('mobile_device_id')->nullable();
            $table->string('provider', 20)->default('expo');
            $table->string('event', 120)->nullable();
            $table->string('title', 180);
            $table->text('message');
            $table->json('data_json')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->unsignedSmallInteger('receipt_check_count')->default(0);
            $table->timestamp('scheduled_for')->nullable();
            $table->string('provider_ticket_id', 120)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('receipt_due_at')->nullable();
            $table->timestamp('receipt_checked_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for', 'id'], 'mobile_push_outbox_dispatch_index');
            $table->index(['status', 'receipt_due_at', 'id'], 'mobile_push_outbox_receipt_index');
            $table->index(['user_id', 'created_at'], 'mobile_push_outbox_user_index');
            $table->index(['mobile_device_id', 'created_at'], 'mobile_push_outbox_device_index');
            $table->unique('provider_ticket_id', 'mobile_push_outbox_ticket_unique');
            $table->foreign('user_id', 'mobile_push_outbox_user_foreign')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('mobile_device_id', 'mobile_push_outbox_device_foreign')->references('id')->on('mobile_devices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_push_outbox');
    }
};
