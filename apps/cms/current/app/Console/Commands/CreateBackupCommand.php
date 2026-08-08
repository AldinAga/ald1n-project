<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Throwable;

final class CreateBackupCommand extends Command
{
    protected $signature = 'app:backup-create {--type=manual : manual, daily ili weekly} {--database-only : Preskoči privatne fajlove}';
    protected $description = 'Kreiraj privatni MySQL i storage backup';

    public function handle(BackupService $service): int
    {
        try {
            $result = $service->create((string) $this->option('type'), null, (bool) $this->option('database-only'));
            $this->info('PASS Backup je kreiran: '.$result['path']);
            $this->line('Veličina: '.number_format($result['size'] / 1048576, 2, ',', '.').' MB');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('FAIL Backup nije kreiran: '.$exception->getMessage());
            return self::FAILURE;
        }
    }
}
