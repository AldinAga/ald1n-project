<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;
use RuntimeException;

final class UpdateExchangeRateCommand extends Command
{
    protected $signature = 'exchange-rate:update {--force : Ažuriraj i kada je uključen ručni režim}';
    protected $description = 'Preuzmi EUR/RSD kurs iz podešenog javnog izvora';

    public function handle(ExchangeRateService $service): int
    {
        if (!(bool) $this->option('force') && $service->configuration()['mode'] !== 'auto') {
            $this->line('SKIP Automatski režim kursa nije uključen.');
            return self::SUCCESS;
        }

        try {
            $result = $service->updateAutomatically('cron', null, (bool) $this->option('force'));
            $this->info('PASS EUR/RSD '.number_format($result['rate'], 4, '.', '').' ('.$result['date'].')');
            return self::SUCCESS;
        } catch (RuntimeException $exception) {
            $this->error('FAIL '.$exception->getMessage());
            return self::FAILURE;
        }
    }
}
