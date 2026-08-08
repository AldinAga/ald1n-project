<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BackupRun;
use App\Models\SecurityEvent;
use App\Models\SystemHealthSnapshot;
use App\Models\SystemRuntimeState;
use App\Support\DiskSpaceHealthPolicy;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class SystemHealthService
{
    /** @return array{status:string,checks:list<array{key:string,label:string,status:string,message:string}>,metrics:array<string,mixed>,checked_at:string} */
    public function inspect(): array
    {
        $checks = [];
        $add = static function (string $key, string $label, string $status, string $message) use (&$checks): void {
            $checks[] = compact('key', 'label', 'status', 'message');
        };

        $add('app_debug', 'Produkcioni debug', config('app.debug') ? 'critical' : 'healthy', config('app.debug') ? 'APP_DEBUG mora biti false.' : 'APP_DEBUG je isključen.');
        $add('environment', 'Aplikaciono okruženje', app()->environment('production') ? 'healthy' : 'warning', 'APP_ENV='.(string) app()->environment());
        $add('redis_disabled', 'Redis', !array_key_exists('redis', (array) config('database.connections')) ? 'healthy' : 'warning', 'Redis ostaje isključen iz runtime arhitekture.');

        $runtimeOk = true;
        $runtimeMessages = [];
        foreach ([
            storage_path('framework/sessions'), storage_path('framework/cache/data'),
            storage_path('framework/views'), storage_path('logs'), base_path('bootstrap/cache'),
        ] as $directory) {
            $ok = is_dir($directory) && is_writable($directory);
            $runtimeOk = $runtimeOk && $ok;
            if (!$ok) $runtimeMessages[] = $directory;
        }
        $add('runtime', 'Runtime direktorijumi', $runtimeOk ? 'healthy' : 'critical', $runtimeOk ? 'Session, cache, view i log direktorijumi su upisivi.' : 'Nisu upisivi: '.implode(', ', $runtimeMessages));

        $databaseOk = false;
        $pendingMigrations = null;
        try {
            DB::connection()->getPdo();
            $databaseOk = true;
            $pendingMigrations = $this->pendingMigrationCount();
        } catch (Throwable $exception) {
            $add('database', 'Laravel baza', 'critical', $exception->getMessage());
        }
        if ($databaseOk) {
            $add('database', 'Laravel baza', 'healthy', 'Konekcija je uspešna.');
            $add('migrations', 'Migracije', $pendingMigrations === 0 ? 'healthy' : 'critical', $pendingMigrations === 0 ? 'Nema migracija na čekanju.' : 'Migracija na čekanju: '.$pendingMigrations.'.');
        }

        $heartbeat = $this->runtimeState('scheduler_heartbeat');
        $heartbeatAge = $heartbeat?->recorded_at !== null
            ? (int) floor(abs($heartbeat->recorded_at->diffInMinutes(now())))
            : null;
        $schedulerHealthy = $heartbeatAge !== null && $heartbeatAge <= 5;
        $schedulerMessage = $heartbeatAge === null
            ? 'Heartbeat još nije zabeležen.'
            : 'Poslednji heartbeat pre '.$heartbeatAge.' min.';
        if (!$schedulerHealthy) {
            $schedulerMessage .= ' Pokreni `php artisan app:scheduler-heartbeat`, a zatim proveri hosting cron: `* * * * * cd '.base_path().' && '.PHP_BINARY.' artisan schedule:run >> /dev/null 2>&1`.';
        }
        $add('scheduler', 'Laravel scheduler', $schedulerHealthy ? 'healthy' : 'critical', $schedulerMessage);

        $automation = $this->runtimeState('automation_last_success');
        $automationAge = $automation?->recorded_at !== null
            ? (int) floor(abs($automation->recorded_at->diffInHours(now())))
            : null;
        $automationHealthy = $automationAge !== null && $automationAge <= 3;
        $automationMessage = $automationAge === null
            ? 'Uspešno pokretanje još nije zabeleženo.'
            : 'Poslednji uspeh pre '.$automationAge.' h.';
        if (!$automationHealthy) {
            $automationMessage .= ' Pokreni `php artisan app:automation-run` i proveri da scheduler izvršava hourly zadatak.';
        }
        $add('automation', 'Operativna automatizacija', $automationHealthy ? 'healthy' : 'warning', $automationMessage);

        $latestBackup = null;
        try {
            if (Schema::hasTable('backup_runs')) {
                $latestBackup = BackupRun::query()->where('status', 'completed')->latest('started_at')->first();
            }
        } catch (Throwable) {
            $latestBackup = null;
        }
        $backupAge = $latestBackup?->finished_at !== null
            ? (int) floor(abs($latestBackup->finished_at->diffInHours(now())))
            : null;
        $backupStatus = $backupAge === null ? 'warning' : ($backupAge <= 26 ? 'healthy' : 'critical');
        $backupMessage = $backupAge === null
            ? 'Još nema uspešnog backupa.'
            : 'Poslednji backup pre '.$backupAge.' h, '.number_format(((int) $latestBackup->size_bytes) / 1048576, 2, ',', '.').' MB.';
        if ($backupStatus !== 'healthy') {
            $backupMessage .= ' Pokreni `php artisan app:backup-create --type=manual`, proveri rezultat i zadrži dnevni scheduler zadatak u 02:30.';
        }
        $add('backup', 'Bezbednosna kopija', $backupStatus, $backupMessage);

        $backupPathStatus = 'healthy';
        $backupPathMessage = '';
        try {
            $path = app(BackupService::class)->safeBasePath();
            $backupPathMessage = 'Privatni direktorijum: '.$path;
        } catch (Throwable $exception) {
            $backupPathStatus = 'critical';
            $backupPathMessage = $exception->getMessage();
        }
        $add('backup_path', 'Backup lokacija', $backupPathStatus, $backupPathMessage);

        $legacyStatus = 'healthy';
        $legacyMessage = 'Legacy konekcija je dostupna i sesija je read-only.';
        try {
            $legacy = DB::connection('legacy');
            $legacy->getPdo();
            $row = null;
            try {
                $row = $legacy->selectOne('SELECT @@session.transaction_read_only AS read_only');
            } catch (Throwable) {
                $row = $legacy->selectOne('SELECT @@session.tx_read_only AS read_only');
            }
            if ((int) (($row->read_only ?? 0)) !== 1) {
                $legacyStatus = 'critical';
                $legacyMessage = 'Legacy MySQL sesija nije read-only.';
            }
        } catch (Throwable $exception) {
            $legacyStatus = 'warning';
            $legacyMessage = 'Legacy konekcija nije dostupna: '.$exception->getMessage();
        }
        $add('legacy', 'Legacy read-only', $legacyStatus, $legacyMessage);

        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());
        $disk = DiskSpaceHealthPolicy::evaluate($free, $total, (array) config('system_health.disk', []));
        $add('disk', 'Prostor na disku', $disk['status'], $disk['message']);

        $securityCritical = 0;
        try {
            if (Schema::hasTable('security_events')) {
                $securityCritical = SecurityEvent::query()->where('severity', 'critical')->where('created_at', '>=', now()->subDay())->count();
            }
        } catch (Throwable) {
            $securityCritical = 0;
        }
        $add('security_events', 'Security događaji / 24h', $securityCritical > 0 ? 'warning' : 'healthy', $securityCritical.' kritičnih događaja.');

        $logErrors = $this->recentLogErrorCount();
        $add('errors', 'Aplikacione greške / 24h', $logErrors > 0 ? 'warning' : 'healthy', $logErrors.' zapisa ERROR/CRITICAL/ALERT/EMERGENCY.');

        $overall = 'healthy';
        foreach ($checks as $check) {
            if ($check['status'] === 'critical') {
                $overall = 'critical';
                break;
            }
            if ($check['status'] === 'warning') $overall = 'warning';
        }

        return [
            'status' => $overall,
            'checks' => $checks,
            'metrics' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => Application::VERSION,
                'app_version' => (string) config('app.version'),
                'pending_migrations' => $pendingMigrations,
                'security_critical_24h' => $securityCritical,
                'log_errors_24h' => $logErrors,
                'disk_free_bytes' => $free,
                'disk_total_bytes' => $total,
                'disk_free_percent' => $disk['free_percent'],
            ],
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /** @param array{status:string,checks:array<int,array<string,mixed>>,metrics:array<string,mixed>,checked_at:string} $report */
    public function snapshot(array $report, ?int $userId = null): ?SystemHealthSnapshot
    {
        if (!Schema::hasTable('system_health_snapshots')) return null;
        return SystemHealthSnapshot::query()->create([
            'status' => $report['status'],
            'checks_json' => $report['checks'],
            'metrics_json' => $report['metrics'],
            'checked_by' => $userId,
            'checked_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function pendingMigrationCount(): int
    {
        if (!Schema::hasTable('migrations')) return count(File::files(database_path('migrations')));
        $ran = DB::table('migrations')->pluck('migration')->all();
        $files = array_map(static fn ($file): string => pathinfo($file->getFilename(), PATHINFO_FILENAME), File::files(database_path('migrations')));
        return count(array_diff($files, $ran));
    }

    private function runtimeState(string $key): ?SystemRuntimeState
    {
        try {
            if (!Schema::hasTable('system_runtime_states')) return null;
            return SystemRuntimeState::query()->where('state_key', $key)->first();
        } catch (Throwable) {
            return null;
        }
    }

    private function recentLogErrorCount(): int
    {
        $path = storage_path('logs/laravel.log');
        if (!is_file($path) || filesize($path) > 50_000_000) return 0;
        $contents = (string) @file_get_contents($path);
        if ($contents === '') return 0;
        $date = now()->format('Y-m-d');
        preg_match_all('/\['.preg_quote($date, '/').'[^\]]*\].*\.(?:ERROR|CRITICAL|ALERT|EMERGENCY):/i', $contents, $matches);
        return count($matches[0]);
    }
}
