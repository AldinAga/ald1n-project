<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BackupRun;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class BackupDoctorCommand extends Command
{
    protected $signature = 'app:backup-doctor {--create-test : Kreiraj database-only test backup}';
    protected $description = 'Proveri backup direktorijum, mysqldump, bazu i evidenciju';

    public function handle(BackupService $service): int
    {
        $failed = false;
        try {
            $path = $service->safeBasePath();
            $this->info('PASS Backup direktorijum: '.$path);
        } catch (Throwable $exception) {
            $this->error('FAIL Backup direktorijum: '.$exception->getMessage());
            $failed = true;
        }

        if ($service->binaryAvailable()) $this->info('PASS mysqldump je dostupan.');
        else { $this->error('FAIL mysqldump nije dostupan.'); $failed = true; }

        try {
            DB::connection()->getPdo();
            $this->info('PASS Laravel MySQL konekcija.');
        } catch (Throwable $exception) {
            $this->error('FAIL MySQL konekcija: '.$exception->getMessage());
            $failed = true;
        }

        if (Schema::hasTable('backup_runs')) {
            $latest = BackupRun::query()->where('status', 'completed')->latest('started_at')->first();
            $this->line($latest ? 'INFO Poslednji uspešan backup: '.$latest->started_at?->format('d.m.Y H:i:s') : 'WARN Još nema uspešnog backupa.');
        } else {
            $this->error('FAIL backup_runs tabela ne postoji.');
            $failed = true;
        }

        if (!$failed && $this->option('create-test')) {
            try {
                $result = $service->create('manual', null, true);
                $this->info('PASS Database-only backup: '.$result['path']);
            } catch (Throwable $exception) {
                $this->error('FAIL Test backup: '.$exception->getMessage());
                $failed = true;
            }
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
