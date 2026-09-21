<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BackupRun;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

final class BackupService
{
    /** @return array{run:?BackupRun,path:string,size:int,manifest:array<string,mixed>} */
    public function create(string $type = 'manual', ?int $userId = null, bool $databaseOnly = false): array
    {
        $type = in_array($type, ['manual', 'daily', 'weekly'], true) ? $type : 'manual';
        $key = now()->format('Ymd-His').'-'.$type.'-'.bin2hex(random_bytes(3));
        $basePath = $this->safeBasePath();
        $target = $basePath.DIRECTORY_SEPARATOR.$key;
        File::ensureDirectoryExists($target, 0750, true);

        $run = null;
        if (Schema::hasTable('backup_runs')) {
            $run = BackupRun::query()->create([
                'backup_key' => $key,
                'backup_type' => $type,
                'status' => 'running',
                'backup_path' => $target,
                'created_by' => $userId,
                'started_at' => now(),
            ]);
        }

        try {
            $databaseFile = $this->dumpDatabase($target);
            $copied = $databaseOnly ? [] : $this->copyConfiguredFiles($target.DIRECTORY_SEPARATOR.'files');
            $manifest = [
                'version' => (string) config('app.version'),
                'created_at' => now()->toIso8601String(),
                'type' => $type,
                'database' => basename($databaseFile),
                'database_sha256' => hash_file('sha256', $databaseFile),
                'files' => $copied,
            ];
            file_put_contents(
                $target.DIRECTORY_SEPARATOR.'manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                LOCK_EX,
            );
            $size = $this->directorySize($target);

            $run?->update([
                'status' => 'completed',
                'database_file' => $databaseFile,
                'size_bytes' => $size,
                'finished_at' => now(),
                'metadata_json' => ['database_only' => $databaseOnly, 'file_count' => count($copied)],
            ]);

            $this->prune();

            return ['run' => $run, 'path' => $target, 'size' => $size, 'manifest' => $manifest];
        } catch (Throwable $exception) {
            $run?->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => mb_substr($exception->getMessage(), 0, 4000),
            ]);
            throw $exception;
        }
    }

    /** @return array{removed:int,kept:int,keeper_ids:list<int>} */
    public function prune(): array
    {
        if (!Schema::hasTable('backup_runs')) {
            return ['removed' => 0, 'kept' => 0, 'keeper_ids' => []];
        }

        $limit = (int) config('backup.stable_retention', 2);
        if ($limit !== 2) {
            throw new RuntimeException('Stable backup retention owner policy mora ostati tacno 2.');
        }

        $runs = BackupRun::query()
            ->where('status', 'completed')
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get();

        if ($runs->count() < $limit) {
            return [
                'removed' => 0,
                'kept' => $runs->count(),
                'keeper_ids' => $runs->map(static fn (BackupRun $run): int => (int) $run->getKey())->values()->all(),
            ];
        }

        $keepers = [];
        foreach ($runs as $run) {
            $verifyRc = Artisan::call('app:backup-verify', ['--run' => (string) $run->getKey()]);
            if ($verifyRc !== 0) {
                continue;
            }
            $keepers[] = $run;
            if (count($keepers) === $limit) {
                break;
            }
        }

        if (count($keepers) !== $limit) {
            throw new RuntimeException('Nisu pronadjena dva restore-ready backupa; retention cleanup je blokiran.');
        }

        $keeperIds = array_map(static fn (BackupRun $run): int => (int) $run->getKey(), $keepers);
        $removed = 0;
        foreach ($runs as $run) {
            if (in_array((int) $run->getKey(), $keeperIds, true)) {
                continue;
            }
            if ($run->backup_path && is_dir($run->backup_path)) {
                File::deleteDirectory($run->backup_path);
            }
            $run->delete();
            $removed++;
        }

        return ['removed' => $removed, 'kept' => count($keeperIds), 'keeper_ids' => $keeperIds];
    }
    public function binaryAvailable(): bool
    {
        $binary = trim((string) config('backup.mysqldump_binary'));
        if ($binary === '') {
            return false;
        }
        $process = new Process([$binary, '--version']);
        $process->setTimeout(10);
        try {
            $process->run();
            return $process->isSuccessful();
        } catch (Throwable) {
            return false;
        }
    }

    public function safeBasePath(): string
    {
        $path = rtrim((string) config('backup.path'), DIRECTORY_SEPARATOR);
        if ($path === '') {
            throw new RuntimeException('BACKUP_PATH nije podešen.');
        }
        File::ensureDirectoryExists($path, 0750, true);
        $public = realpath(public_path()) ?: public_path();
        $real = realpath($path) ?: $path;
        if ($real === $public || str_starts_with($real.DIRECTORY_SEPARATOR, $public.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('BACKUP_PATH ne sme biti unutar public direktorijuma.');
        }
        if (!is_writable($real)) {
            throw new RuntimeException('Backup direktorijum nije upisiv: '.$real);
        }
        return $real;
    }

    private function dumpDatabase(string $target): string
    {
        $connection = config('database.default');
        $database = (array) config('database.connections.'.$connection, []);
        if (($database['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('Produkcioni backup trenutno podržava MySQL/MariaDB konekciju.');
        }
        $name = trim((string) ($database['database'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Naziv baze nije podešen.');
        }

        $plain = $target.DIRECTORY_SEPARATOR.'database.sql';
        $gzip = $plain.'.gz';
        $handle = fopen($plain, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Ne mogu da otvorim privremeni SQL fajl.');
        }

        $args = [
            (string) config('backup.mysqldump_binary'),
            '--host='.(string) ($database['host'] ?? '127.0.0.1'),
            '--port='.(string) ($database['port'] ?? '3306'),
            '--user='.(string) ($database['username'] ?? ''),
            '--single-transaction', '--quick', '--skip-lock-tables', '--routines', '--triggers', '--hex-blob',
            '--default-character-set=utf8mb4', $name,
        ];
        $process = new Process($args, base_path(), ['MYSQL_PWD' => (string) ($database['password'] ?? '')]);
        $process->setTimeout(max(60, (int) config('backup.timeout_seconds')));
        try {
            $process->run(static function (string $type, string $buffer) use ($handle): void {
                if ($type === Process::OUT) {
                    fwrite($handle, $buffer);
                }
            });
        } finally {
            fclose($handle);
        }
        if (!$process->isSuccessful()) {
            @unlink($plain);
            throw new RuntimeException('mysqldump nije uspeo: '.mb_substr(trim($process->getErrorOutput()), 0, 1000));
        }

        $input = fopen($plain, 'rb');
        $output = gzopen($gzip, 'wb9');
        if ($input === false || $output === false) {
            if (is_resource($input)) fclose($input);
            if (is_resource($output)) gzclose($output);
            throw new RuntimeException('GZIP kompresija SQL backupa nije dostupna.');
        }
        while (!feof($input)) {
            $chunk = fread($input, 1024 * 1024);
            if ($chunk === false) break;
            gzwrite($output, $chunk);
        }
        fclose($input);
        gzclose($output);
        @unlink($plain);

        return $gzip;
    }

    /** @return list<array{source:string,target:string,size:int,sha256:string}> */
    private function copyConfiguredFiles(string $targetRoot): array
    {
        $records = [];
        foreach ((array) config('backup.include_paths', []) as $source) {
            $source = (string) $source;
            if (!is_dir($source)) continue;
            $label = basename(dirname($source)).'-'.basename($source);
            $destination = $targetRoot.DIRECTORY_SEPARATOR.$label;
            foreach (File::allFiles($source) as $file) {
                $relative = ltrim(str_replace($source, '', $file->getPathname()), DIRECTORY_SEPARATOR);
                $target = $destination.DIRECTORY_SEPARATOR.$relative;
                File::ensureDirectoryExists(dirname($target), 0750, true);
                if (!copy($file->getPathname(), $target)) {
                    throw new RuntimeException('Ne mogu da kopiram backup fajl: '.$relative);
                }
                $records[] = [
                    'source' => $label.'/'.$relative,
                    'target' => 'files/'.$label.'/'.$relative,
                    'size' => $file->getSize(),
                    'sha256' => hash_file('sha256', $target),
                ];
            }
        }
        return $records;
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
