<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SystemRuntimeState;
use App\Services\OperationalAutomationService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\Command;
use Throwable;

final class RunOperationalAutomationCommand extends Command
{
    protected $signature = 'app:automation-run
        {--force : Pošalji upozorenja bez čekanja reminder intervala}
        {--digest : Pošalji dnevni zbir otvorenih upozorenja}';

    protected $description = 'Pokreni operativna upozorenja i automatizovane poslovne provere bez Redis-a';

    public function handle(OperationalAutomationService $automation): int
    {
        try {
            $run = $automation->run(null, (bool) $this->option('force'), (bool) $this->option('digest'));
            $summary = $run->summary_json ?? [];
            $this->line(sprintf(
                '%s task=%s status=%s examined=%d alerts=%d notifications=%d resolved=%d',
                $run->status === 'success' ? '<fg=green>PASS</>' : '<fg=yellow>INFO</>',
                $run->task,
                $run->status,
                (int) ($summary['examined'] ?? 0),
                (int) ($summary['alerts'] ?? 0),
                (int) ($summary['notifications'] ?? 0),
                (int) ($summary['resolved'] ?? 0),
            ));
            if ($run->status !== 'failed' && Schema::hasTable('system_runtime_states')) {
                SystemRuntimeState::query()->updateOrCreate(
                    ['state_key' => 'automation_last_success'],
                    ['state_value' => $run->task, 'recorded_at' => now()],
                );
            }
            return $run->status === 'failed' ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception::class.': '.$exception->getMessage());
            return self::FAILURE;
        }
    }
}
