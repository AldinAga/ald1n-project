<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SystemRuntimeState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class SchedulerHeartbeatCommand extends Command
{
    protected $signature = 'app:scheduler-heartbeat';
    protected $description = 'Zabeleži da Laravel scheduler radi';

    public function handle(): int
    {
        if (!Schema::hasTable('system_runtime_states')) {
            $this->warn('system_runtime_states tabela još ne postoji.');
            return self::FAILURE;
        }
        SystemRuntimeState::query()->updateOrCreate(
            ['state_key' => 'scheduler_heartbeat'],
            ['state_value' => now()->toIso8601String(), 'recorded_at' => now()],
        );
        $this->info('PASS Scheduler heartbeat '.now()->format('d.m.Y H:i:s'));
        return self::SUCCESS;
    }
}
