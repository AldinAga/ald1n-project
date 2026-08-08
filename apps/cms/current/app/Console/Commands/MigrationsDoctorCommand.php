<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class MigrationsDoctorCommand extends Command
{
    protected $signature = 'app:migrations-doctor {--strict : Tretiraj DB zapise bez odgovarajuceg migration fajla kao gresku}';

    protected $description = 'Proveri migration fajlove, migrations tabelu, SQL mode, charset i foreign key stanje.';

    public function handle(): int
    {
        $failed = false;
        $files = $this->migrationFiles();

        if ($files === []) {
            $this->error('FAIL Nije pronadjen nijedan migration fajl.');
            $failed = true;
        } else {
            $this->info('PASS Pronadjeno migration fajlova: '.count($files).'.');
        }

        $duplicateNames = $this->duplicates(array_keys($files));
        if ($duplicateNames !== []) {
            foreach ($duplicateNames as $name) {
                $this->error('FAIL Dupliran migration naziv: '.$name.'.');
            }
            $failed = true;
        } else {
            $this->info('PASS Migration nazivi su jedinstveni.');
        }

        $prefixes = [];
        foreach (array_keys($files) as $name) {
            if (preg_match('/\A(\d{4}_\d{2}_\d{2}_\d{6})_/', $name, $match) !== 1) {
                $this->error('FAIL Migration nema standardni timestamp prefiks: '.$name.'.');
                $failed = true;
                continue;
            }
            $prefixes[] = $match[1];
        }
        $duplicatePrefixes = $this->duplicates($prefixes);
        if ($duplicatePrefixes !== []) {
            foreach ($duplicatePrefixes as $prefix) {
                $this->error('FAIL Vise migration fajlova koristi isti timestamp: '.$prefix.'.');
            }
            $failed = true;
        } else {
            $this->info('PASS Migration timestamp prefiksi su jedinstveni.');
        }

        try {
            DB::connection()->getPdo();
            $this->info('PASS Primarna baza je dostupna.');
        } catch (Throwable $exception) {
            $this->error('FAIL Primarna baza nije dostupna: '.$exception->getMessage());

            return self::FAILURE;
        }

        if (!Schema::hasTable('migrations')) {
            $this->error('FAIL migrations tabela ne postoji.');

            return self::FAILURE;
        }

        $databaseMigrations = DB::table('migrations')
            ->orderBy('id')
            ->pluck('migration')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all();
        $databaseSet = array_fill_keys($databaseMigrations, true);
        $fileSet = array_fill_keys(array_keys($files), true);

        $pending = array_values(array_diff(array_keys($fileSet), array_keys($databaseSet)));
        if ($pending !== []) {
            foreach ($pending as $migration) {
                $this->error('FAIL Migration nije primenjena: '.$migration.'.');
            }
            $failed = true;
        } else {
            $this->info('PASS Sve migration datoteke su primenjene.');
        }

        $orphanRecords = array_values(array_diff(array_keys($databaseSet), array_keys($fileSet)));
        if ($orphanRecords !== []) {
            foreach ($orphanRecords as $migration) {
                if ($this->option('strict')) {
                    $this->error('FAIL migrations tabela sadrzi zapis bez fajla: '.$migration.'.');
                    $failed = true;
                } else {
                    $this->warn('WARN migrations tabela sadrzi zapis bez fajla: '.$migration.'.');
                }
            }
        } else {
            $this->info('PASS Svaki DB migration zapis ima odgovarajuci fajl.');
        }

        $duplicateDatabaseRows = DB::table('migrations')
            ->select('migration')
            ->groupBy('migration')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('migration')
            ->all();
        if ($duplicateDatabaseRows !== []) {
            foreach ($duplicateDatabaseRows as $migration) {
                $this->error('FAIL Dupliran zapis u migrations tabeli: '.(string) $migration.'.');
            }
            $failed = true;
        } else {
            $this->info('PASS migrations tabela nema duplirane zapise.');
        }

        $this->checkDatabaseRuntime($failed);

        if ($failed) {
            $this->error('Migration audit nije prosao.');

            return self::FAILURE;
        }

        $latestBatch = (int) DB::table('migrations')->max('batch');
        $this->info('Migration audit je spreman. Poslednji batch: '.$latestBatch.'.');

        return self::SUCCESS;
    }

    /** @return array<string,string> */
    private function migrationFiles(): array
    {
        $result = [];
        foreach (glob(database_path('migrations/*.php')) ?: [] as $path) {
            $result[pathinfo($path, PATHINFO_FILENAME)] = $path;
        }
        ksort($result);

        return $result;
    }

    /** @param list<string> $values @return list<string> */
    private function duplicates(array $values): array
    {
        $counts = array_count_values($values);
        $duplicates = array_keys(array_filter($counts, static fn (int $count): bool => $count > 1));
        sort($duplicates);

        return array_values($duplicates);
    }

    private function checkDatabaseRuntime(bool &$failed): void
    {
        $driver = (string) DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            $this->error('FAIL Produkcioni driver mora biti mysql/mariadb, trenutno: '.$driver.'.');
            $failed = true;

            return;
        }

        try {
            $row = DB::selectOne('SELECT @@SESSION.sql_mode AS sql_mode, @@SESSION.foreign_key_checks AS foreign_key_checks, @@character_set_database AS character_set_database, @@collation_database AS collation_database');
            $sqlMode = mb_strtoupper((string) ($row->sql_mode ?? ''));
            $foreignKeys = (int) ($row->foreign_key_checks ?? 0);
            $charset = mb_strtolower((string) ($row->character_set_database ?? ''));
            $collation = mb_strtolower((string) ($row->collation_database ?? ''));

            if ($foreignKeys !== 1) {
                $this->error('FAIL foreign_key_checks mora biti ukljucen za aplikacionu sesiju.');
                $failed = true;
            } else {
                $this->info('PASS foreign_key_checks je ukljucen.');
            }

            if (!str_contains($sqlMode, 'STRICT_TRANS_TABLES') && !str_contains($sqlMode, 'STRICT_ALL_TABLES')) {
                $this->warn('WARN MySQL session nema STRICT_TRANS_TABLES/STRICT_ALL_TABLES.');
            } else {
                $this->info('PASS MySQL strict mode je ukljucen.');
            }

            if (!str_contains($sqlMode, 'ONLY_FULL_GROUP_BY')) {
                $this->warn('WARN MySQL session nema ONLY_FULL_GROUP_BY; izvestaji mogu sakriti neispravne GROUP BY upite.');
            } else {
                $this->info('PASS ONLY_FULL_GROUP_BY je ukljucen.');
            }

            if ($charset !== 'utf8mb4') {
                $this->warn('WARN Baza koristi charset '.$charset.' umesto utf8mb4.');
            } else {
                $this->info('PASS Baza koristi utf8mb4.');
            }

            if (!str_starts_with($collation, 'utf8mb4_')) {
                $this->warn('WARN Collation baze nije utf8mb4: '.$collation.'.');
            } else {
                $this->info('PASS Collation baze je '.$collation.'.');
            }
        } catch (Throwable $exception) {
            $this->error('FAIL MySQL runtime parametri nisu mogli da se provere: '.$exception->getMessage());
            $failed = true;
        }
    }
}
