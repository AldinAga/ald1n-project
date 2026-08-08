<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MobileDevice;
use App\Models\MobilePushOutbox;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class MobilePushDoctorCommand extends Command
{
    protected $signature = 'app:mobile-push-doctor {--strict : Zahtevaj aktivan MOBILE_PUSH_ENABLED i Expo provider}';
    protected $description = 'Proveri Phase 3B mobilnu push registraciju, outbox i dispatcher konfiguraciju.';

    public function handle(): int
    {
        $failed = false;
        foreach ([
            app_path('Services/MobilePushOutboxService.php'),
            app_path('Services/ExpoPushTransport.php'),
            app_path('Services/MobilePushDispatcher.php'),
            app_path('Models/MobilePushOutbox.php'),
            app_path('Console/Commands/MobilePushDispatchCommand.php'),
            database_path('migrations/2026_08_07_000042_create_mobile_push_outbox_phase3b.php'),
        ] as $path) {
            if (!is_file($path) || filesize($path) === 0) {
                $this->error('FAIL Nedostaje '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path));
                $failed = true;
            }
        }

        if (!Schema::hasTable('mobile_push_outbox')) {
            $this->error('FAIL Nedostaje mobile_push_outbox tabela. Pokreni migrate --force.');
            $failed = true;
        } else {
            $this->info('PASS mobile_push_outbox tabela postoji.');
        }

        $provider = (string) config('mobile.push.provider', 'expo');
        if ($provider !== 'expo') {
            $this->error('FAIL MOBILE_PUSH_PROVIDER mora biti expo za ovu fazu.');
            $failed = true;
        } else {
            $this->info('PASS Expo push provider je izabran.');
        }

        $enabled = (bool) config('mobile.push.enabled', false);
        if (!$enabled && $this->option('strict')) {
            $this->error('FAIL MOBILE_PUSH_ENABLED nije true.');
            $failed = true;
        } elseif (!$enabled) {
            $this->warn('WARN MOBILE_PUSH_ENABLED=false; registracija tokena je dozvoljena, delivery je bezbedno ugašen.');
        } else {
            $this->info('PASS Mobile push delivery je aktivan.');
        }

        if (Schema::hasTable('mobile_devices')) {
            $registered = MobileDevice::query()->whereNull('revoked_at')->whereNotNull('push_token_hash')->count();
            $this->line('Registrovani aktivni push uređaji: '.$registered);
        }
        if (Schema::hasTable('mobile_push_outbox')) {
            $this->line('Push outbox pending: '.MobilePushOutbox::query()->where('status', 'pending')->count());
            $this->line('Push outbox failed: '.MobilePushOutbox::query()->where('status', 'failed')->count());
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
