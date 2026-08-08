<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MobilePushDispatcher;
use Illuminate\Console\Command;

final class MobilePushDispatchCommand extends Command
{
    protected $signature = 'app:mobile-push-dispatch {--limit=100} {--receipts-only}';
    protected $description = 'Pošalji dospele Expo push poruke iz mobilnog outbox-a i proveri dospele push receipte.';

    public function handle(MobilePushDispatcher $dispatcher): int
    {
        $limit = max(1, min(500, (int) $this->option('limit')));
        if (!$this->option('receipts-only')) {
            $sent = $dispatcher->dispatch($limit);
            $this->info(sprintf(
                'Push: obrađeno %d, poslato %d, retry %d, neuspešno %d.',
                $sent['processed'], $sent['sent'], $sent['retried'], $sent['failed'],
            ));
        }

        $receipts = $dispatcher->checkReceipts(min(1000, max(100, $limit * 5)));
        $this->info(sprintf(
            'Receipts: provereno %d, prihvaćeno %d, retry %d, neuspešno %d, još nema %d.',
            $receipts['checked'], $receipts['delivered'], $receipts['retried'], $receipts['failed'], $receipts['missing'],
        ));

        return self::SUCCESS;
    }
}
