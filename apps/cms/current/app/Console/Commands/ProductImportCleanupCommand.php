<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ProductImportService;
use Illuminate\Console\Command;

final class ProductImportCleanupCommand extends Command
{
    protected $signature = 'app:product-import-cleanup {--hours=24 : Obriši fajlove starije od zadatog broja sati}';

    protected $description = 'Briše napuštene fajlove CSV uvoza artikala (product-imports) starije od 24 sata.';

    public function handle(ProductImportService $imports): int
    {
        $deleted = $imports->purgeOld(max(1, (int) $this->option('hours')));
        $this->info('Obrisano fajlova: '.$deleted);

        return self::SUCCESS;
    }
}
