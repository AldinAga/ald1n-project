<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('notification_preferences')) {
            throw new RuntimeException('notification_preferences table is missing; refusing to mark warranty opt-in as migrated.');
        }
        if (Schema::hasColumn('notification_preferences', 'warranty_expiry_alerts')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            $this->instantAlter('ALTER TABLE `notification_preferences` ADD COLUMN `warranty_expiry_alerts` TINYINT(1) NOT NULL DEFAULT 0, ALGORITHM=INSTANT, LOCK=NONE');
            return;
        }

        // Non-MySQL test environments retain the Laravel schema abstraction.
        Schema::table('notification_preferences', static function (Blueprint $table): void {
            $table->boolean('warranty_expiry_alerts')->default(false);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('notification_preferences')) {
            throw new RuntimeException('notification_preferences table is missing; refusing unsafe warranty opt-in rollback.');
        }
        if (!Schema::hasColumn('notification_preferences', 'warranty_expiry_alerts')) {
            return;
        }
        if (DB::table('notification_preferences')->where('warranty_expiry_alerts', true)->exists()) {
            throw new RuntimeException('Warranty expiry opt-in records exist: refusing destructive rollback without an independent approved recovery.');
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            $this->instantAlter('ALTER TABLE `notification_preferences` DROP COLUMN `warranty_expiry_alerts`, ALGORITHM=INSTANT, LOCK=NONE');
            return;
        }

        Schema::table('notification_preferences', static function (Blueprint $table): void {
            $table->dropColumn('warranty_expiry_alerts');
        });
    }

    private function instantAlter(string $statement): void
    {
        $table = DB::selectOne("SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notification_preferences'");
        if (strcasecmp((string) ($table->engine ?? ''), 'InnoDB') !== 0) {
            throw new RuntimeException('Warranty expiry schema change requires a verified InnoDB table.');
        }

        $original = (string) (DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS seconds')?->seconds ?? '');
        if (!preg_match('/^[0-9]{1,10}$/D', $original)) {
            throw new RuntimeException('Cannot safely read the existing lock_wait_timeout session setting.');
        }

        try {
            DB::statement('SET SESSION lock_wait_timeout = 5');
            DB::statement($statement);
        } finally {
            DB::statement('SET SESSION lock_wait_timeout = '.$original);
        }
    }
};
