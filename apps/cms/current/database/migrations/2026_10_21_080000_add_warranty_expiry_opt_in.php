<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('notification_preferences') || Schema::hasColumn('notification_preferences', 'warranty_expiry_alerts')) return;
        Schema::table('notification_preferences', static function (Blueprint $table): void {
            $table->boolean('warranty_expiry_alerts')->default(false);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('notification_preferences') || !Schema::hasColumn('notification_preferences', 'warranty_expiry_alerts')) return;
        Schema::table('notification_preferences', static function (Blueprint $table): void {
            $table->dropColumn('warranty_expiry_alerts');
        });
    }
};
