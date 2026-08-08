<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\WarrantyService;
use Illuminate\Console\Command;

final class WarrantiesBackfillCommand extends Command
{
    protected $signature = 'app:warranties-backfill {--limit=5000 : Maksimalan broj kompletiranih porudžbina}';
    protected $description = 'Generiše nedostajuće garantne listove za ranije kompletirane porudžbine.';

    public function handle(WarrantyService $service): int
    {
        $limit = max(1, min(100000, (int) $this->option('limit')));
        $orders = 0;
        $created = 0;
        Order::query()->whereNotNull('completed_at')->whereHas('items', static fn ($items) => $items->whereDoesntHave('warranty'))->orderBy('id')->limit($limit)->chunkById(100, function ($chunk) use ($service, &$orders, &$created): void {
            foreach ($chunk as $order) {
                $orders++;
                $created += $service->ensureForOrder($order->load('user'))->count();
            }
        });
        $this->info(sprintf('PASS Provereno %d porudžbina; generisano %d garantnih listova.', $orders, $created));
        return self::SUCCESS;
    }
}
