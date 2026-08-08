<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CatalogSyncService;
use Illuminate\Console\Command;
use Throwable;

final class LegacyCatalogDiffCommand extends Command
{
    protected $signature = 'legacy:catalog-diff {--table= : Samo jedna tabela} {--json : JSON izlaz}';
    protected $description = 'Uporedi legacy katalog sa Laravel bazom bez upisa';

    public function handle(CatalogSyncService $sync): int
    {
        try {
            $summary = $sync->diff($this->option('table') ?: null);
            if ($this->option('json')) { $this->line(json_encode($summary, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)); return self::SUCCESS; }
            $this->table(['Tabela','Isto','Promenjeno','Nedostaje u Laravelu','Samo Laravel'], array_map(fn ($t,$c)=>[$t,$c['same'],$c['changed'],$c['missing_target'],$c['target_only']], array_keys($summary), $summary));
            return self::SUCCESS;
        } catch (Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
    }
}
