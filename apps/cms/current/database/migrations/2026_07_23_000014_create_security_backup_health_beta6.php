<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('backup_runs')) {
            Schema::create('backup_runs', function (Blueprint $table): void {
                $table->id();
                $table->string('backup_key', 100)->unique();
                $table->enum('backup_type', ['manual', 'daily', 'weekly'])->default('manual');
                $table->enum('status', ['running', 'completed', 'failed'])->default('running');
                $table->string('backup_path', 1000)->nullable();
                $table->string('database_file', 500)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('started_at');
                $table->dateTime('finished_at')->nullable();
                $table->text('error_message')->nullable();
                $table->json('metadata_json')->nullable();
                $table->timestamps();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'started_at']);
                $table->index(['backup_type', 'started_at']);
            });
        }

        if (!Schema::hasTable('system_health_snapshots')) {
            Schema::create('system_health_snapshots', function (Blueprint $table): void {
                $table->id();
                $table->enum('status', ['healthy', 'warning', 'critical'])->default('healthy');
                $table->json('checks_json');
                $table->json('metrics_json')->nullable();
                $table->unsignedBigInteger('checked_by')->nullable();
                $table->dateTime('checked_at');
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('checked_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'checked_at']);
            });
        }

        if (!Schema::hasTable('system_runtime_states')) {
            Schema::create('system_runtime_states', function (Blueprint $table): void {
                $table->id();
                $table->string('state_key', 120)->unique();
                $table->longText('state_value')->nullable();
                $table->dateTime('recorded_at');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('security_events')) {
            Schema::create('security_events', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('event_type', 100);
                $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
                $table->string('request_id', 64)->nullable();
                $table->string('route_name', 190)->nullable();
                $table->string('method', 12)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->json('context_json')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['event_type', 'created_at']);
                $table->index(['severity', 'created_at']);
                $table->index(['user_id', 'created_at']);
                $table->index('request_id');
            });
        }

        if (Schema::hasTable('audit_logs')) {
            if (!Schema::hasColumn('audit_logs', 'level')) {
                Schema::table('audit_logs', function (Blueprint $table): void {
                    $table->string('level', 20)->default('info')->after('action')->index();
                });
            }
            if (!Schema::hasColumn('audit_logs', 'request_id')) {
                Schema::table('audit_logs', function (Blueprint $table): void {
                    $table->string('request_id', 64)->nullable()->after('level')->index();
                });
            }
        }

        if (Schema::hasTable('permissions')) {
            foreach ([
                ['name' => 'System health', 'slug' => 'system.health', 'description' => 'Pregled zdravlja aplikacije, scheduler-a, storage-a i baze.', 'sort_order' => 200],
                ['name' => 'Upravljanje backupima', 'slug' => 'backups.manage', 'description' => 'Ručno kreiranje, pregled i čišćenje bezbednosnih kopija.', 'sort_order' => 210],
                ['name' => 'Izvoz audit loga', 'slug' => 'audit.export', 'description' => 'CSV izvoz audit i security događaja.', 'sort_order' => 220],
                ['name' => 'Pregled security događaja', 'slug' => 'security.view', 'description' => 'Pregled odbijenih i sumnjivih zahteva.', 'sort_order' => 230],
            ] as $permission) {
                DB::table('permissions')->updateOrInsert(
                    ['slug' => $permission['slug']],
                    $permission + ['created_at' => now()],
                );
            }
        }
    }

    public function down(): void
    {
        // Production hardening migration is intentionally non-destructive.
    }
};
