<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\UserGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class MobileGoogleAuthDoctorCommand extends Command
{
    protected $signature = 'app:mobile-google-auth-doctor';
    protected $description = 'Proverava Phase 3C Google auth konfiguraciju bez prikaza tajnih vrednosti.';

    public function handle(): int
    {
        $failed = false;
        if (Schema::hasTable('user_external_identities')) {
            $this->info('PASS user_external_identities tabela postoji.');
        } else {
            $this->error('FAIL user_external_identities tabela ne postoji.');
            $failed = true;
        }

        $enabled = (bool) config('mobile.google_auth.enabled', false);
        $registration = (bool) config('mobile.google_auth.registration_enabled', false);
        $autoActivate = (bool) config('mobile.google_auth.auto_activate_registration', false);
        $clientId = trim((string) config('mobile.google_auth.web_client_id', ''));

        $this->line('Google auth enabled: '.($enabled ? 'true' : 'false'));
        $this->line('Google registration enabled: '.($registration ? 'true' : 'false'));
        $this->line('Google registration auto-activate: '.($autoActivate ? 'true' : 'false'));

        if ($enabled && ($clientId === '' || !str_ends_with($clientId, '.apps.googleusercontent.com'))) {
            $this->error('FAIL GOOGLE_OAUTH_WEB_CLIENT_ID nije validno konfigurisan.');
            $failed = true;
        } elseif ($enabled) {
            $this->info('PASS Google Web OAuth client ID je konfigurisan.');
        } else {
            $this->warn('WARN Google auth je bezbedno isključen dok Firebase/OAuth konfiguracija ne bude spremna.');
        }

        if (Role::query()->where('slug', 'user')->exists()) {
            $this->info('PASS standardna user uloga postoji.');
        } else {
            $this->error('FAIL standardna user uloga ne postoji.');
            $failed = true;
        }

        if (UserGroup::query()->where('slug', 'standardni-korisnik')->where('status', 'active')->exists()) {
            $this->info('PASS standardni-korisnik grupa postoji i aktivna je.');
        } else {
            $this->warn('WARN standardni-korisnik grupa nije pronađena/aktivna; nove Google registracije neće dobiti standardnu grupu.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
