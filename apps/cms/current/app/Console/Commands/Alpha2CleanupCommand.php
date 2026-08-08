<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class Alpha2CleanupCommand extends Command
{
    protected $signature = 'app:alpha2-cleanup {--force : Izvrši bez potvrde}';
    protected $description = 'Premesti stare .env backup fajlove van projekta i ukloni test/runtime ostatke';

    public function handle(): int
    {
        $envBackups = glob(base_path('.env.backup-*')) ?: [];
        $testBackups = glob(base_path('tests/**/*.backup-*'), GLOB_BRACE) ?: [];
        $runtime = array_filter([base_path('.phpunit.result.cache')], 'is_file');
        $this->line('Env backup fajlova: '.count($envBackups));
        $this->line('Test backup fajlova: '.count($testBackups));
        $this->line('Runtime fajlova: '.count($runtime));
        if ($envBackups === [] && $testBackups === [] && $runtime === []) {
            $this->info('Nema ostataka za čišćenje.');
            return self::SUCCESS;
        }
        if (!$this->option('force') && !$this->confirm('Nastaviti sa bezbednim čišćenjem?')) return self::FAILURE;

        $destination = dirname(base_path()).'/private-backups/cms-env';
        File::ensureDirectoryExists($destination, 0700, true);
        foreach ($envBackups as $file) {
            $target = $destination.'/'.basename($file).'-'.date('Ymd-His');
            if (!@rename($file, $target)) {
                $this->error('Nije moguće premestiti '.$file);
                return self::FAILURE;
            }
            @chmod($target, 0600);
        }
        foreach (array_merge($testBackups, $runtime) as $file) @unlink($file);
        $this->info('Čišćenje završeno. Produkcioni .env nije menjan.');
        return self::SUCCESS;
    }
}
