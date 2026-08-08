<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CatalogSyncService;
use Illuminate\Console\Command;
use Throwable;

final class LegacyDatabaseInfoCommand extends Command
{
    protected $signature = 'legacy:database-info';
    protected $description = 'Prikaži uloge baza i proveri da li legacy nalog ima samo read-only prava';

    public function handle(CatalogSyncService $sync): int
    {
        try {
            $info = $sync->legacyGrants();
            $this->line('Ciljna Laravel baza: '.config('database.connections.mysql.database').' (READ/WRITE)');
            $this->line('Legacy baza: '.$info['database'].' (mora biti READ ONLY)');
            $this->line('Legacy korisnik: '.$info['current_user']);
            foreach ($info['grants'] as $grant) $this->line('  '.$grant);
            if ($info['has_write_privileges']) {
                $this->error('FAIL: legacy korisnik ima write privilegije. Napravi korisnika sa SELECT/SHOW VIEW pravima.');
                return self::FAILURE;
            }
            $this->info('PASS: legacy korisnik nema detektovane write privilegije.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Provera nije uspela: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
