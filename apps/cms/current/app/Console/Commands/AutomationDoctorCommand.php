<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AutomationRun;
use App\Models\OperationalAlert;
use App\Services\AutomationReadinessService;
use App\Services\OperationalAutomationService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class AutomationDoctorCommand extends Command
{
    protected $signature = 'app:automation-doctor
        {--repair : Pokreni migracije i CoreAccessSeeder}
        {--run : Izvrši operativni scan bez slanja ponovljenih upozorenja}
        {--force : Uz --run zanemari reminder interval}';

    protected $description = 'Proveri automatizaciju, scheduler evidenciju, upozorenja i notification preferences';

    public function handle(AutomationReadinessService $readiness, OperationalAutomationService $automation): int
    {
        if ($this->option('repair')) {
            try {
                $migrationCode = Artisan::call('migrate', ['--force' => true]);
                if ($migrationCode !== self::SUCCESS) {
                    $this->error(trim(Artisan::output()));
                    return self::FAILURE;
                }
                $this->callSilent('db:seed', ['--class' => CoreAccessSeeder::class, '--force' => true]);
            } catch (Throwable $exception) {
                $this->error('Popravka nije uspela: '.$exception->getMessage());
                return self::FAILURE;
            }
        }

        $missing = $readiness->missing();
        if ($missing !== []) {
            $this->error('FAIL Automation šema nije kompletna: '.implode(', ', $missing));
            return self::FAILURE;
        }
        $this->info('PASS Automation tabele i kolone postoje.');

        $permissionOk = Schema::hasTable('permissions')
            && DB::table('permissions')->where('slug', 'automation.manage')->exists();
        $this->line(($permissionOk ? '<fg=green>PASS</>' : '<fg=red>FAIL</>').' automation.manage dozvola');
        if (!$permissionOk) return self::FAILURE;

        try {
            $open = OperationalAlert::query()->where('status', 'open')->count();
            $last = AutomationRun::query()->latest('started_at')->first();
            $this->info('PASS Automation SQL upiti su uspešni. Otvorena upozorenja: '.$open.'.');
            $this->line('Poslednje pokretanje: '.($last?->started_at?->format('d.m.Y H:i:s').' / '.$last?->status ?: 'nije pokretano'));
        } catch (Throwable $exception) {
            $this->error('FAIL Automation SQL: '.$exception->getMessage());
            return self::FAILURE;
        }

        $console = file_get_contents(base_path('routes/console.php')) ?: '';
        $scheduled = str_contains($console, "app:automation-run") && str_contains($console, "app:automation-run --digest");
        $this->line(($scheduled ? '<fg=green>PASS</>' : '<fg=red>FAIL</>').' scheduler definicije');
        if (!$scheduled) return self::FAILURE;

        if ($this->option('run')) {
            try {
                $run = $automation->run(null, (bool) $this->option('force'));
                $this->info('PASS Ručni automation scan: '.$run->status.'.');
            } catch (Throwable $exception) {
                $this->error('FAIL Ručni automation scan: '.$exception->getMessage());
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
