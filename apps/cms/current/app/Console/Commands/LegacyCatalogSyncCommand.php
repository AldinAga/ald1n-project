<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CatalogSyncService;
use Illuminate\Console\Command;
use Throwable;

final class LegacyCatalogSyncCommand extends Command
{
    protected $signature = 'legacy:catalog-sync {--dry-run : Eksplicitno probni režim} {--apply : Primeni bezkonfliktne legacy izmene} {--force : Bez interaktivne potvrde}';
    protected $description = 'Kontrolisana jednosmerna sinhronizacija legacy kataloga u Laravel';

    public function handle(CatalogSyncService $sync): int
    {
        $apply = (bool) $this->option('apply');
        if ($apply && !$this->option('force') && !$this->confirm('Primeni samo bezkonfliktne legacy izmene u Laravel bazu?')) return self::FAILURE;
        try {
            $summary = $sync->sync($apply);
            $rows = [];
            foreach ($summary as $table => $c) $rows[] = [$table,$c['same'],$c['baseline'],$c['insert'],$c['update'],$c['local_only'],$c['conflict']];
            $this->table(['Tabela','Isto','Baseline','Insert','Update','Lokalno','Konflikt'], $rows);
            $conflicts = array_sum(array_column($summary, 'conflict'));
            $this->line($apply ? 'Apply je završen. Konflikti nisu prepisani.' : 'DRY RUN: ništa nije upisano.');
            if ($conflicts > 0) $this->warn('Pronađeno konflikata: '.$conflicts.'. Reši ih pre finalnog prelaska.');
            return self::SUCCESS;
        } catch (Throwable $e) { $this->error('Sinhronizacija nije uspela: '.$e->getMessage()); return self::FAILURE; }
    }
}
