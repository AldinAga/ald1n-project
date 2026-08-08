<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

final class SecurityHardeningDoctorCommand extends Command
{
    protected $signature = 'app:security-hardening-doctor';

    protected $description = 'Proveri produkciono okruzenje, session zastitu, tajne i javno izlozene fajlove.';

    public function handle(): int
    {
        $failed = false;

        $environment = (string) app()->environment();
        if ($environment === 'production') {
            $this->info('PASS Aplikaciono okruzenje je production.');
        } else {
            $this->warn('WARN Aplikaciono okruzenje je APP_ENV='.$environment.'. Za RC i produkciju postavi APP_ENV=production.');
        }

        if ((bool) config('app.debug')) {
            $this->error('FAIL APP_DEBUG mora biti false u produkciji.');
            $failed = true;
        } else {
            $this->info('PASS APP_DEBUG je iskljucen.');
        }

        $url = trim((string) config('app.url'));
        $urlParts = parse_url($url);
        $scheme = mb_strtolower((string) ($urlParts['scheme'] ?? ''));
        $host = mb_strtolower((string) ($urlParts['host'] ?? ''));
        if ($scheme !== 'https' || $host === '') {
            $this->error('FAIL APP_URL mora biti validan HTTPS URL. Trenutno: '.$url);
            $failed = true;
        } else {
            $this->info('PASS APP_URL koristi HTTPS: '.$host);
        }

        if (!$this->validApplicationKey((string) config('app.key'))) {
            $this->error('FAIL APP_KEY nije validan AES-256 kljuc. Nemoj generisati novi kljuc preko postojece produkcije bez plana rotacije.');
            $failed = true;
        } else {
            $this->info('PASS APP_KEY je validan za AES-256.');
        }

        $sessionChecks = [
            'SESSION_SECURE_COOKIE je ukljucen' => (bool) config('session.secure'),
            'Session payload je sifrovan' => (bool) config('session.encrypt'),
            'Session cookie je HttpOnly' => (bool) config('session.http_only'),
            'SameSite je lax ili strict' => in_array((string) config('session.same_site'), ['lax', 'strict'], true),
        ];
        foreach ($sessionChecks as $label => $ok) {
            if ($ok) {
                $this->info('PASS '.$label.'.');
            } else {
                $this->error('FAIL '.$label.'.');
                $failed = true;
            }
        }

        $logLevel = mb_strtolower(trim((string) env('LOG_LEVEL', 'warning')));
        if (in_array($logLevel, ['debug', 'notice', 'info'], true)) {
            $this->warn('WARN LOG_LEVEL='.$logLevel.' je previse detaljan za stabilnu produkciju. Preporuka: warning.');
        } else {
            $this->info('PASS LOG_LEVEL je produkciono ogranicen: '.$logLevel.'.');
        }

        if ($host !== '') {
            $turnstileHost = mb_strtolower(trim((string) config('services.turnstile.expected_hostname')));
            if ((bool) config('services.turnstile.enabled') && $turnstileHost !== $host) {
                $this->warn('WARN TURNSTILE_EXPECTED_HOSTNAME='.$turnstileHost.' ne odgovara APP_URL hostu '.$host.'.');
            } else {
                $this->info('PASS Turnstile hostname je uskladjen sa aplikacijom.');
            }

            $stateful = array_map(
                static fn (mixed $value): string => mb_strtolower(trim((string) $value)),
                (array) config('sanctum.stateful', []),
            );
            if (!in_array($host, $stateful, true)) {
                $this->warn('WARN SANCTUM_STATEFUL_DOMAINS ne sadrzi '.$host.'.');
            } else {
                $this->info('PASS Sanctum stateful domen sadrzi produkcioni host.');
            }
        }

        $envPath = base_path('.env');
        if (!is_file($envPath)) {
            $this->error('FAIL Produkcioni .env fajl ne postoji.');
            $failed = true;
        } elseif (is_link($envPath)) {
            $this->warn('WARN .env je simbolicki link. Proveri da cilj nije javno dostupan i da ima stroge dozvole.');
        } else {
            $permissions = @fileperms($envPath);
            if ($permissions === false) {
                $this->warn('WARN Dozvole .env fajla nisu mogle da se procitaju.');
            } else {
                $mode = $permissions & 0777;
                if (($mode & 0007) !== 0 || ($mode & 0020) !== 0) {
                    $this->warn('WARN .env dozvole su '.sprintf('%04o', $mode).'. Preporuka je 0600 ili 0640 bez group-write/other pristupa.');
                } else {
                    $this->info('PASS .env dozvole su ogranicene: '.sprintf('%04o', $mode).'.');
                }
            }
        }

        foreach ([
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/cache/data' => storage_path('framework/cache/data'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ] as $label => $path) {
            if (is_dir($path) && is_writable($path)) {
                $this->info('PASS Runtime direktorijum je upisiv: '.$label.'.');
            } else {
                $this->error('FAIL Runtime direktorijum nije upisiv: '.$label.'.');
                $failed = true;
            }
        }

        $sensitive = $this->publicSensitiveFiles();
        if ($sensitive === []) {
            $this->info('PASS Public direktorijum ne sadrzi ocigledne tajne, backup ili development artefakte.');
        } else {
            foreach ($sensitive as $file) {
                $this->error('FAIL Osetljiv fajl je javno dostupan: '.$file);
            }
            $failed = true;
        }

        if ($this->securityHeadersContractIsPresent()) {
            $this->info('PASS SecurityHeaders sadrzi CSP, HSTS, frame, MIME i referrer zastitu.');
        } else {
            $this->error('FAIL SecurityHeaders middleware nema kompletan obavezni skup zaglavlja.');
            $failed = true;
        }

        if ($failed) {
            $this->error('Security hardening provera nije prosla.');

            return self::FAILURE;
        }

        $this->info('Security hardening provera je zavrsena bez kriticnih gresaka.');

        return self::SUCCESS;
    }

    private function validApplicationKey(string $key): bool
    {
        $key = trim($key);
        if ($key === '') {
            return false;
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);

            return is_string($decoded) && strlen($decoded) === 32;
        }

        return strlen($key) >= 32;
    }

    /** @return list<string> */
    private function publicSensitiveFiles(): array
    {
        $public = public_path();
        if (!is_dir($public)) {
            return ['public direktorijum ne postoji'];
        }

        $matches = [];
        try {
            foreach (File::allFiles($public, true) as $file) {
                $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($public))), '/');
                $base = mb_strtolower($file->getFilename());
                $extension = mb_strtolower($file->getExtension());

                if (str_starts_with($base, '.env')
                    || in_array($base, ['composer.json', 'composer.lock', 'artisan', 'phpunit.xml', 'phpunit.xml.dist'], true)
                    || in_array($extension, ['sql', 'sqlite', 'log', 'zip', 'tar', 'tgz', 'bak', 'backup'], true)) {
                    $matches[] = 'public/'.$relative;
                }
            }
        } catch (Throwable $exception) {
            return ['public skeniranje nije uspelo: '.$exception->getMessage()];
        }

        sort($matches);

        return array_values(array_unique($matches));
    }

    private function securityHeadersContractIsPresent(): bool
    {
        $path = app_path('Http/Middleware/SecurityHeaders.php');
        $source = is_file($path) ? (string) file_get_contents($path) : '';

        foreach ([
            'Content-Security-Policy',
            'Strict-Transport-Security',
            'X-Content-Type-Options',
            'X-Frame-Options',
            'Referrer-Policy',
            'Permissions-Policy',
        ] as $header) {
            if (!str_contains($source, $header)) {
                return false;
            }
        }

        return true;
    }
}
