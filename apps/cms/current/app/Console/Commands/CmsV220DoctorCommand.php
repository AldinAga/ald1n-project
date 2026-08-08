<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

final class CmsV220DoctorCommand extends Command
{
    protected $signature = 'app:cms-v2-2-0-doctor
        {--strict : Zahtevaj database queue i sve produkcione postavke}';

    protected $description = 'Proveri v2.2.0 Mobile API Foundation rute, tabele, konfiguraciju i API ugovor.';

    public function handle(): int
    {
        $failed = false;

        foreach ([
            app_path('Http/Controllers/Api/V1/BootstrapController.php'),
            app_path('Http/Controllers/Api/V1/MobileDeviceController.php'),
            app_path('Http/Controllers/Api/V1/NotificationController.php'),
            app_path('Http/Controllers/Api/V1/AccountController.php'),
            app_path('Models/MobileDevice.php'),
            app_path('Support/ApiErrorResponse.php'),
            config_path('mobile.php'),
            base_path('docs/openapi.yaml'),
            database_path('migrations/2026_08_06_000039_create_mobile_devices_v2_2_0.php'),
            database_path('migrations/2026_08_06_000040_add_push_notification_preference_v2_2_0.php'),
            database_path('migrations/2026_08_06_000041_create_database_queue_tables_v2_2_0.php'),
        ] as $path) {
            if (!is_file($path) || filesize($path) === 0) {
                $this->error('FAIL Nedostaje '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path).'.');
                $failed = true;
            }
        }
        if (!$failed) {
            $this->info('PASS v2.2.0 release fajlovi postoje.');
        }

        foreach ([
            'api.v1.bootstrap',
            'api.v1.me.update',
            'api.v1.me.password',
            'api.v1.me.notification-preferences',
            'api.v1.catalog.filters',
            'api.v1.orders.options',
            'api.v1.devices.index',
            'api.v1.devices.store',
            'api.v1.devices.update',
            'api.v1.devices.destroy',
            'api.v1.notifications.index',
            'api.v1.notifications.read',
            'api.v1.notifications.read-all',
        ] as $routeName) {
            if (!Route::has($routeName)) {
                $this->error('FAIL Nedostaje ruta '.$routeName.'.');
                $failed = true;
            }
        }
        if (!$failed) {
            $this->info('PASS Mobile API rute postoje.');
        }

        foreach (['mobile_devices', 'jobs', 'job_batches', 'failed_jobs'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->error('FAIL Nedostaje tabela '.$table.'. Pokreni migrate --force.');
                $failed = true;
            }
        }
        if (Schema::hasTable('notification_preferences') && !Schema::hasColumn('notification_preferences', 'push_enabled')) {
            $this->error('FAIL Nedostaje notification_preferences.push_enabled.');
            $failed = true;
        }

        if (!$failed) {
            $this->info('PASS Mobile device i queue šema je spremna.');
        }

        if ((string) config('queue.default') !== 'database') {
            $message = 'QUEUE_CONNECTION nije database; postavi QUEUE_CONNECTION=database u .env, zatim pokreni `php artisan optimize:clear` i obezbedi trajni database queue worker.';
            if ($this->option('strict')) {
                $this->error('FAIL '.$message);
                $failed = true;
            } else {
                $this->warn('WARN '.$message);
            }
        } else {
            $this->info('PASS Database queue je aktivan.');
        }

        if ((string) config('mobile.api_version') !== 'v1') {
            $this->error('FAIL MOBILE API verzija nije v1.');
            $failed = true;
        } else {
            $this->info('PASS Mobile API konfiguracija je učitana.');
        }

        if ($failed) {
            $this->error('v2.2.0 Mobile API Foundation nije spreman.');
            return self::FAILURE;
        }

        $this->info('v2.2.0 Mobile API Foundation je spreman.');
        return self::SUCCESS;
    }
}
