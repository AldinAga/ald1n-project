<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->tinyIncrements('id');
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('user_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('description', 1000)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('category_access_mode', ['all', 'selected', 'none'])->default('all');
            $table->boolean('include_uncategorized')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['status', 'sort_order', 'name']);
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('role_id');
            $table->unsignedBigInteger('user_group_id')->nullable();
            $table->string('username', 50)->unique();
            $table->string('email', 190)->unique();
            $table->string('password_hash');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('phone', 40)->nullable();
            $table->enum('status', ['pending', 'active', 'blocked'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->dateTime('password_changed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles');
            $table->foreign('user_group_id')->references('id')->on('user_groups')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index('status');
            $table->index('role_id');
            $table->index('user_group_id');
            $table->index('created_at');
        });

        Schema::create('user_group_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['group_id', 'permission_id']);
            $table->foreign('group_id')->references('id')->on('user_groups')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            $table->index('permission_id');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->char('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('user_group_permissions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('user_groups');
        Schema::dropIfExists('roles');
    }
};
