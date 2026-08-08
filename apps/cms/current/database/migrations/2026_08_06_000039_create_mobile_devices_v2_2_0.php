<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('mobile_devices')) {
            return;
        }

        Schema::create('mobile_devices', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('personal_access_token_id')->nullable();
            $table->uuid('installation_id');
            $table->string('platform', 20);
            $table->string('device_name', 120)->nullable();
            $table->string('push_provider', 20)->nullable();
            $table->text('push_token')->nullable();
            $table->string('push_token_hash', 64)->nullable();
            $table->string('app_version', 40)->nullable();
            $table->string('build_number', 40)->nullable();
            $table->string('locale', 20)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->boolean('notifications_enabled')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'installation_id'], 'mobile_devices_user_installation_unique');
            $table->index(['user_id', 'revoked_at', 'last_seen_at'], 'mobile_devices_user_active_index');
            $table->unique('push_token_hash', 'mobile_devices_push_token_hash_unique');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('personal_access_token_id')->references('id')->on('personal_access_tokens')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_devices');
    }
};
