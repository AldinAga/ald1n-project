<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->createAutomationRuns();
        $this->createOperationalAlerts();
        $this->createNotificationPreferences();
        $this->seedPermissions();
        $this->seedDefaults();
    }

    public function down(): void
    {
        // Production-safe migration: automation history and alert evidence are never dropped automatically.
    }

    private function createAutomationRuns(): void
    {
        if (Schema::hasTable('automation_runs')) {
            return;
        }

        Schema::create('automation_runs', static function (Blueprint $table): void {
            $table->id();
            $table->string('task', 80);
            $table->string('status', 20)->default('running');
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->unsignedInteger('examined_count')->default(0);
            $table->unsignedInteger('action_count')->default(0);
            $table->unsignedInteger('notification_count')->default(0);
            $table->json('summary_json')->nullable();
            $table->text('error_text')->nullable();
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->timestamps();
            $table->foreign('triggered_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['task', 'status', 'started_at']);
        });
    }

    private function createOperationalAlerts(): void
    {
        if (Schema::hasTable('operational_alerts')) {
            return;
        }

        Schema::create('operational_alerts', static function (Blueprint $table): void {
            $table->id();
            $table->string('alert_key', 190)->unique();
            $table->string('type', 60);
            $table->string('severity', 20)->default('warning');
            $table->string('status', 20)->default('open');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title', 190);
            $table->text('message');
            $table->string('action_url', 500)->nullable();
            $table->dateTime('first_detected_at');
            $table->dateTime('last_detected_at');
            $table->dateTime('last_notified_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'severity', 'last_detected_at']);
            $table->index(['type', 'status', 'last_notified_at']);
        });
    }

    private function createNotificationPreferences(): void
    {
        if (Schema::hasTable('notification_preferences')) {
            return;
        }

        Schema::create('notification_preferences', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('email_enabled')->default(false);
            $table->boolean('order_updates')->default(true);
            $table->boolean('payment_alerts')->default(true);
            $table->boolean('commission_updates')->default(true);
            $table->boolean('stock_alerts')->default(false);
            $table->boolean('daily_digest')->default(false);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'automation.manage'],
            [
                'name' => 'Upravljanje automatizacijom',
                'description' => 'Podešavanje, ručno pokretanje i pregled operativnih automatizacija.',
                'sort_order' => 190,
                'created_at' => now(),
            ],
        );
    }

    private function seedDefaults(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $defaults = [
            'automation_enabled' => '1',
            'automation_unaccepted_order_hours' => '4',
            'automation_alert_reminder_hours' => '24',
            'automation_low_stock_enabled' => '1',
            'automation_overdue_payment_enabled' => '1',
            'automation_deadline_alerts_enabled' => '1',
            'automation_daily_digest_enabled' => '1',
            'automation_last_success_at' => '',
            'automation_last_error' => '',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->insertOrIgnore([
                'setting_key' => $key,
                'setting_value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
