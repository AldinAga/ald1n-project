<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('notification_preferences') || Schema::hasColumn('notification_preferences', 'push_enabled')) {
            return;
        }

        Schema::table('notification_preferences', static function (Blueprint $table): void {
            $table->boolean('push_enabled')->default(false)->after('email_enabled');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('notification_preferences') || !Schema::hasColumn('notification_preferences', 'push_enabled')) {
            return;
        }

        Schema::table('notification_preferences', static function (Blueprint $table): void {
            $table->dropColumn('push_enabled');
        });
    }
};
