<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->ensureRoles();
        $this->ensureUserGroups();
        $this->ensurePermissions();
        $this->ensureUsersRuntimeColumns();
        $this->ensureUserGroupPermissions();
        $this->ensurePasswordResetTokens();
        $this->ensureSettings();
        $this->ensurePersonalAccessTokens();
    }

    public function down(): void
    {
        // Repair migracija ne uklanja postojeće korisnike, podešavanja, sesije niti pristupne podatke.
    }

    private function ensureRoles(): void
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', static function (Blueprint $table): void {
                $table->tinyIncrements('id');
                $table->string('name', 50);
                $table->string('slug', 50)->unique();
                $table->timestamp('created_at')->useCurrent();
            });

            return;
        }

        $nameMissing = !Schema::hasColumn('roles', 'name');
        $slugMissing = !Schema::hasColumn('roles', 'slug');
        $createdMissing = !Schema::hasColumn('roles', 'created_at');
        if ($nameMissing || $slugMissing || $createdMissing) {
            Schema::table('roles', static function (Blueprint $table) use ($nameMissing, $slugMissing, $createdMissing): void {
                if ($nameMissing) $table->string('name', 50)->default('Korisnik');
                if ($slugMissing) $table->string('slug', 50)->nullable();
                if ($createdMissing) $table->timestamp('created_at')->nullable();
            });
        }
    }

    private function ensureUserGroups(): void
    {
        if (!Schema::hasTable('user_groups')) {
            Schema::create('user_groups', static function (Blueprint $table): void {
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
            });
        }
    }

    private function ensurePermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', static function (Blueprint $table): void {
                $table->id();
                $table->string('name', 120);
                $table->string('slug', 140)->unique();
                $table->string('description', 500)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    private function ensureUsersRuntimeColumns(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedTinyInteger('role_id')->default(1);
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
            });

            return;
        }

        $rememberMissing = !Schema::hasColumn('users', 'remember_token');
        $lastLoginMissing = !Schema::hasColumn('users', 'last_login_at');
        $passwordChangedMissing = !Schema::hasColumn('users', 'password_changed_at');
        $groupMissing = !Schema::hasColumn('users', 'user_group_id');
        if ($rememberMissing || $lastLoginMissing || $passwordChangedMissing || $groupMissing) {
            Schema::table('users', static function (Blueprint $table) use ($rememberMissing, $lastLoginMissing, $passwordChangedMissing, $groupMissing): void {
                if ($rememberMissing) $table->string('remember_token', 100)->nullable();
                if ($lastLoginMissing) $table->dateTime('last_login_at')->nullable();
                if ($passwordChangedMissing) $table->dateTime('password_changed_at')->nullable();
                if ($groupMissing) $table->unsignedBigInteger('user_group_id')->nullable();
            });
        }
    }

    private function ensureUserGroupPermissions(): void
    {
        if (!Schema::hasTable('user_group_permissions')) {
            Schema::create('user_group_permissions', static function (Blueprint $table): void {
                $table->unsignedBigInteger('group_id');
                $table->unsignedBigInteger('permission_id');
                $table->timestamp('created_at')->useCurrent();
                $table->primary(['group_id', 'permission_id']);
                $table->index('permission_id');
            });
        }
    }

    private function ensurePasswordResetTokens(): void
    {
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->char('token_hash', 64)->unique();
                $table->dateTime('expires_at');
                $table->dateTime('used_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('expires_at');
            });
        }
    }

    private function ensureSettings(): void
    {
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', static function (Blueprint $table): void {
                $table->id();
                $table->string('setting_key', 120)->unique();
                $table->longText('setting_value')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });

            return;
        }

        $keyMissing = !Schema::hasColumn('settings', 'setting_key');
        $valueMissing = !Schema::hasColumn('settings', 'setting_value');
        $updatedByMissing = !Schema::hasColumn('settings', 'updated_by');
        $createdMissing = !Schema::hasColumn('settings', 'created_at');
        $updatedMissing = !Schema::hasColumn('settings', 'updated_at');
        if ($keyMissing || $valueMissing || $updatedByMissing || $createdMissing || $updatedMissing) {
            Schema::table('settings', static function (Blueprint $table) use ($keyMissing, $valueMissing, $updatedByMissing, $createdMissing, $updatedMissing): void {
                if ($keyMissing) $table->string('setting_key', 120)->nullable();
                if ($valueMissing) $table->longText('setting_value')->nullable();
                if ($updatedByMissing) $table->unsignedBigInteger('updated_by')->nullable();
                if ($createdMissing) $table->timestamp('created_at')->nullable();
                if ($updatedMissing) $table->timestamp('updated_at')->nullable();
            });
        }
    }

    private function ensurePersonalAccessTokens(): void
    {
        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', static function (Blueprint $table): void {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }
};
