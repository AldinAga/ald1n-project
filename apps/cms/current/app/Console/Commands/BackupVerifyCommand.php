<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BackupRun;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

final class BackupVerifyCommand extends Command
{
    protected $signature = 'app:backup-verify
        {--path= : Apsolutna ili BACKUP_PATH-relativna putanja konkretnog backupa}
        {--run= : ID backup_runs zapisa}
        {--allow-older-version : Ne prijavljuj WARN kada je backup napravljen starijom verzijom aplikacije}';

    protected $description = 'Read-only verifikacija backup manifesta, SQL gzip-a, hash vrednosti i privatnih fajlova.';

    public function handle(BackupService $service): int
    {
        try {
            [$path, $run] = $this->resolveBackup($service);
        } catch (Throwable $exception) {
            $this->error('FAIL Backup nije pronadjen: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->line('Backup: '.$path);
        $failed = false;
        $manifestPath = $path.DIRECTORY_SEPARATOR.'manifest.json';
        if (!is_file($manifestPath)) {
            $this->error('FAIL manifest.json ne postoji.');

            return self::FAILURE;
        }

        try {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->error('FAIL manifest.json nije validan JSON: '.$exception->getMessage());

            return self::FAILURE;
        }
        if (!is_array($manifest)) {
            $this->error('FAIL manifest.json nema validan root objekat.');

            return self::FAILURE;
        }

        $backupVersion = trim((string) ($manifest['version'] ?? ''));
        if ($backupVersion === '') {
            $this->error('FAIL Manifest nema verziju aplikacije.');
            $failed = true;
        } elseif ($backupVersion !== (string) config('app.version') && !$this->option('allow-older-version')) {
            $this->warn('WARN Backup je napravljen verzijom '.$backupVersion.', a aplikacija je '.(string) config('app.version').'. Sačuvaj ga kao rollback backup, zatim pokreni `php artisan app:backup-create --type=manual` da strict Stable provera dobije svež backup trenutne verzije.');
        } else {
            $this->info('PASS Backup verzija: '.$backupVersion.'.');
        }

        $createdAt = trim((string) ($manifest['created_at'] ?? ''));
        if ($createdAt === '') {
            $this->error('FAIL Manifest nema created_at.');
            $failed = true;
        } else {
            try {
                $ageHours = now()->diffInMinutes(\Illuminate\Support\Carbon::parse($createdAt), true) / 60;
                if ($ageHours > 24) {
                    $this->warn('WARN Backup je star '.number_format($ageHours, 1, ',', '.').' h. Za RC proveru koristi backup mladji od 24 h.');
                } else {
                    $this->info('PASS Backup je svez: '.number_format($ageHours, 1, ',', '.').' h.');
                }
            } catch (Throwable) {
                $this->error('FAIL Manifest created_at nije validan datum.');
                $failed = true;
            }
        }

        $databaseName = basename(trim((string) ($manifest['database'] ?? '')));
        $databaseHash = mb_strtolower(trim((string) ($manifest['database_sha256'] ?? '')));
        if ($databaseName === '' || preg_match('/\A[a-f0-9]{64}\z/', $databaseHash) !== 1) {
            $this->error('FAIL Manifest nema validan database/database_sha256 zapis.');
            $failed = true;
        } else {
            $databasePath = $path.DIRECTORY_SEPARATOR.$databaseName;
            if (!is_file($databasePath)) {
                $this->error('FAIL SQL backup ne postoji: '.$databaseName.'.');
                $failed = true;
            } else {
                $actualHash = hash_file('sha256', $databasePath);
                if (!is_string($actualHash) || !hash_equals($databaseHash, $actualHash)) {
                    $this->error('FAIL SQL backup SHA-256 ne odgovara manifestu.');
                    $failed = true;
                } else {
                    $this->info('PASS SQL backup SHA-256 je validan.');
                }

                try {
                    $scan = $this->scanSqlGzip($databasePath);
                    if ($scan['uncompressed_bytes'] < 10240) {
                        $this->error('FAIL SQL backup je sumnjivo mali nakon dekompresije: '.$scan['uncompressed_bytes'].' B.');
                        $failed = true;
                    } else {
                        $this->info('PASS SQL gzip je citljiv; dekompresovano '.number_format($scan['uncompressed_bytes'] / 1048576, 2, ',', '.').' MB.');
                    }
                    foreach (['create_table' => 'CREATE TABLE', 'migrations_table' => 'migrations tabela'] as $key => $label) {
                        if (!$scan[$key]) {
                            $this->error('FAIL SQL backup ne sadrzi '.$label.'.');
                            $failed = true;
                        } else {
                            $this->info('PASS SQL backup sadrzi '.$label.'.');
                        }
                    }
                } catch (Throwable $exception) {
                    $this->error('FAIL SQL gzip nije citljiv: '.$exception->getMessage());
                    $failed = true;
                }
            }
        }

        $fileRecords = $manifest['files'] ?? [];
        if (!is_array($fileRecords)) {
            $this->error('FAIL Manifest files polje nije niz.');
            $failed = true;
            $fileRecords = [];
        }
        $verifiedFiles = 0;
        foreach ($fileRecords as $index => $record) {
            if (!is_array($record)) {
                $this->error('FAIL Nevalidan files manifest zapis #'.($index + 1).'.');
                $failed = true;
                continue;
            }
            $target = str_replace('\\', '/', trim((string) ($record['target'] ?? '')));
            $expectedHash = mb_strtolower(trim((string) ($record['sha256'] ?? '')));
            $expectedSize = (int) ($record['size'] ?? -1);
            if (!$this->safeRelativePath($target) || preg_match('/\A[a-f0-9]{64}\z/', $expectedHash) !== 1 || $expectedSize < 0) {
                $this->error('FAIL Nevalidan files manifest zapis #'.($index + 1).'.');
                $failed = true;
                continue;
            }
            $targetPath = $path.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $target);
            if (!is_file($targetPath)) {
                $this->error('FAIL Nedostaje backup fajl: '.$target.'.');
                $failed = true;
                continue;
            }
            $actualSize = filesize($targetPath);
            $actualHash = hash_file('sha256', $targetPath);
            if ($actualSize !== $expectedSize || !is_string($actualHash) || !hash_equals($expectedHash, $actualHash)) {
                $this->error('FAIL Backup fajl ne odgovara manifestu: '.$target.'.');
                $failed = true;
                continue;
            }
            $verifiedFiles++;
        }
        $this->info('PASS Verifikovani privatni backup fajlovi: '.$verifiedFiles.'/'.count($fileRecords).'.');

        $actualDirectorySize = $this->directorySize($path);
        if ($run instanceof BackupRun && (int) $run->size_bytes > 0 && (int) $run->size_bytes !== $actualDirectorySize) {
            $this->error('FAIL backup_runs.size_bytes ne odgovara stvarnoj velicini direktorijuma.');
            $failed = true;
        } else {
            $this->info('PASS Backup evidencija i velicina direktorijuma su uskladjene: '.number_format($actualDirectorySize / 1048576, 2, ',', '.').' MB.');
        }

        if ($failed) {
            $this->error('Backup verifikacija nije prosla. Backup se ne sme smatrati restore-ready.');

            return self::FAILURE;
        }

        $this->info('Backup je logicki validan i spreman za kontrolisanu restore probu na odvojenoj bazi.');

        return self::SUCCESS;
    }

    /** @return array{0:string,1:?BackupRun} */
    private function resolveBackup(BackupService $service): array
    {
        $base = $service->safeBasePath();
        $run = null;
        $requestedRun = trim((string) ($this->option('run') ?? ''));
        $requestedPath = trim((string) ($this->option('path') ?? ''));

        if ($requestedRun !== '') {
            if (!ctype_digit($requestedRun) || !Schema::hasTable('backup_runs')) {
                throw new RuntimeException('--run mora biti postojeci numericki backup_runs ID.');
            }
            $run = BackupRun::query()->whereKey((int) $requestedRun)->where('status', 'completed')->first();
            if (!$run instanceof BackupRun) {
                throw new RuntimeException('Uspesan backup_runs zapis nije pronadjen.');
            }
            $requestedPath = (string) $run->backup_path;
        } elseif ($requestedPath === '') {
            if (!Schema::hasTable('backup_runs')) {
                throw new RuntimeException('backup_runs tabela ne postoji i --path nije prosledjen.');
            }
            $run = BackupRun::query()->where('status', 'completed')->latest('started_at')->first();
            if (!$run instanceof BackupRun) {
                throw new RuntimeException('Nema uspesnog backup_runs zapisa.');
            }
            $requestedPath = (string) $run->backup_path;
        }

        if (!str_starts_with($requestedPath, DIRECTORY_SEPARATOR)) {
            $requestedPath = $base.DIRECTORY_SEPARATOR.ltrim($requestedPath, DIRECTORY_SEPARATOR);
        }
        $realBase = realpath($base) ?: $base;
        $realPath = realpath($requestedPath);
        if ($realPath === false || !is_dir($realPath)) {
            throw new RuntimeException('Backup direktorijum ne postoji: '.$requestedPath);
        }
        if ($realPath !== $realBase && !str_starts_with($realPath.DIRECTORY_SEPARATOR, $realBase.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Backup putanja mora biti unutar BACKUP_PATH.');
        }

        return [$realPath, $run];
    }

    /** @return array{uncompressed_bytes:int,create_table:bool,migrations_table:bool} */
    private function scanSqlGzip(string $path): array
    {
        $handle = gzopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('gzopen nije uspeo.');
        }

        $bytes = 0;
        $createTable = false;
        $migrationsTable = false;
        $carry = '';
        try {
            while (!gzeof($handle)) {
                $chunk = gzread($handle, 1024 * 1024);
                if ($chunk === false) {
                    throw new RuntimeException('gzread nije uspeo.');
                }
                $bytes += strlen($chunk);
                $search = $carry.$chunk;
                $createTable = $createTable || stripos($search, 'CREATE TABLE') !== false;
                $migrationsTable = $migrationsTable
                    || stripos($search, '`migrations`') !== false
                    || stripos($search, '"migrations"') !== false;
                $carry = substr($search, -128);
            }
        } finally {
            gzclose($handle);
        }

        return [
            'uncompressed_bytes' => $bytes,
            'create_table' => $createTable,
            'migrations_table' => $migrationsTable,
        ];
    }

    private function safeRelativePath(string $path): bool
    {
        return $path !== ''
            && !str_starts_with($path, '/')
            && !str_contains('/'.$path.'/', '/../');
    }

    private function directorySize(string $path): int
    {
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
