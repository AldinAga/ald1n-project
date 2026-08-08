<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SystemHealthService;
use Illuminate\Console\Command;

final class SystemHealthCommand extends Command
{
    protected $signature = 'app:system-health {--snapshot : Sačuvaj rezultat u bazi}';
    protected $description = 'Proveri produkciono zdravlje sistema';

    public function handle(SystemHealthService $service): int
    {
        $report = $service->inspect();
        foreach ($report['checks'] as $check) {
            $color = $check['status'] === 'healthy' ? 'green' : ($check['status'] === 'warning' ? 'yellow' : 'red');
            $this->line('<fg='.$color.'>'.strtoupper($check['status']).'</> '.$check['label'].' — '.$check['message']);
        }
        if ($this->option('snapshot')) $service->snapshot($report);
        $this->newLine();
        $this->line('Ukupni status: '.strtoupper($report['status']));
        return $report['status'] === 'critical' ? self::FAILURE : self::SUCCESS;
    }
}
