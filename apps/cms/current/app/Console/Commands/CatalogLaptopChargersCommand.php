<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LaptopChargerCatalogProvisioner;
use Illuminate\Console\Command;
use Throwable;

final class CatalogLaptopChargersCommand extends Command
{
    protected $signature = 'app:catalog-laptop-chargers
        {--dry-run : Preview catalog changes without writing data (default)}
        {--apply : Explicitly provision charger reference data (requires separate production gate)}';

    protected $description = 'Plan or idempotently provision laptop charger type, existing brands, connector lines and W specification';

    public function handle(LaptopChargerCatalogProvisioner $provisioner): int
    {
        if ($this->option('apply') && $this->option('dry-run')) {
            $this->error('HOLD_USE_EXACTLY_ONE_OF_APPLY_OR_DRY_RUN');
            return self::FAILURE;
        }
        try {
            $result = $this->option('apply') ? $provisioner->apply() : $provisioner->preview();
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->info($this->option('apply') ? 'CHARGER_CATALOG_APPLY=PASS' : 'CHARGER_CATALOG_DRY_RUN=PASS_NO_MUTATION');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('HOLD_CHARGER_CATALOG: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
