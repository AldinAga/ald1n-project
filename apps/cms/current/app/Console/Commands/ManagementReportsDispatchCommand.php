<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ReportScheduleService;
use Illuminate\Console\Command;

final class ManagementReportsDispatchCommand extends Command
{
    protected $signature = 'app:management-report-dispatch {--limit=50}';
    protected $description = 'Kreira i šalje dospele zakazane upravljačke izveštaje.';

    public function handle(ReportScheduleService $service): int
    {
        $result = $service->dispatch((int) $this->option('limit'));
        $this->info(sprintf('Dodato u red: %d, poslato: %d, neuspešno: %d.', $result['queued'], $result['sent'], $result['failed']));
        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
