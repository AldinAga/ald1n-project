<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\OrderEmailDispatcher;
use Illuminate\Console\Command;

final class OrderEmailDispatchCommand extends Command
{
    protected $signature = 'app:order-email-dispatch {--limit=200}';
    protected $description = 'Pošalji dospela e-mail obaveštenja o porudžbinama iz pouzdanog outbox reda.';

    public function handle(OrderEmailDispatcher $dispatcher): int
    {
        $result = $dispatcher->dispatch((int) $this->option('limit'));
        $this->info(sprintf('Grupe: %d, poslato: %d, neuspešno: %d.', $result['groups'], $result['sent'], $result['failed']));
        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
