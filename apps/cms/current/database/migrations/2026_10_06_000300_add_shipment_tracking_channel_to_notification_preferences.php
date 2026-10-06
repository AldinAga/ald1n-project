<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('notification_preferences') || Schema::hasColumn('notification_preferences', 'shipment_tracking_channel')) {
            return;
        }

        Schema::table('notification_preferences', static function (Blueprint $table): void {
            $table->string('shipment_tracking_channel', 16)->default('both')->after('push_enabled');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('notification_preferences') || !Schema::hasColumn('notification_preferences', 'shipment_tracking_channel')) {
            return;
        }

        Schema::table('notification_preferences', static function (Blueprint $table): void {
            $table->dropColumn('shipment_tracking_channel');
        });
    }
};
