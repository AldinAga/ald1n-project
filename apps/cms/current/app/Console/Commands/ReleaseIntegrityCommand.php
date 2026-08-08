<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class ReleaseIntegrityCommand extends Command
{
    protected $signature = 'app:release-integrity {--manifest=MANIFEST-SHA256.txt : Manifest relativno u odnosu na aplikacioni root}';

    protected $description = 'Verifikuj SHA-256 integritet svih fajlova iz release manifesta.';

    public function handle(): int
    {
        $manifestName = trim((string) $this->option('manifest'));
        if ($manifestName === '' || str_contains($manifestName, '..') || str_starts_with($manifestName, '/') || str_starts_with($manifestName, '\\')) {
            $this->error('FAIL Nevalidna manifest putanja.');

            return self::INVALID;
        }

        $manifestPath = base_path($manifestName);
        if (!is_file($manifestPath)) {
            $this->error('FAIL Release manifest ne postoji: '.$manifestName.'.');

            return self::FAILURE;
        }

        $lines = file($manifestPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines) || $lines === []) {
            $this->error('FAIL Release manifest je prazan.');

            return self::FAILURE;
        }

        $failed = false;
        $verified = 0;
        $seen = [];

        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/\A([a-f0-9]{64})  (.+)\z/', (string) $line, $match) !== 1) {
                $this->error('FAIL Nevalidan manifest red '.($lineNumber + 1).'.');
                $failed = true;
                continue;
            }

            $expected = $match[1];
            $relative = str_replace('\\', '/', $match[2]);
            if ($relative === '' || str_starts_with($relative, '/') || str_contains('/'.$relative.'/', '/../')) {
                $this->error('FAIL Nevalidna putanja u manifestu: '.$relative.'.');
                $failed = true;
                continue;
            }
            if (isset($seen[$relative])) {
                $this->error('FAIL Dupliran manifest zapis: '.$relative.'.');
                $failed = true;
                continue;
            }
            $seen[$relative] = true;

            $path = base_path($relative);
            if (!is_file($path)) {
                $this->error('FAIL Nedostaje release fajl: '.$relative.'.');
                $failed = true;
                continue;
            }
            if (is_link($path)) {
                $this->error('FAIL Manifest fajl ne sme biti simbolicki link: '.$relative.'.');
                $failed = true;
                continue;
            }

            $actual = hash_file('sha256', $path);
            if (!is_string($actual) || !hash_equals($expected, $actual)) {
                $this->error('FAIL SHA-256 mismatch: '.$relative.'.');
                $failed = true;
                continue;
            }

            $verified++;
        }

        if ($failed) {
            $this->error('Release integritet nije validan. Uspesno provereno: '.$verified.'/'.count($lines).'.');

            return self::FAILURE;
        }

        $this->info('PASS Release manifest je validan: '.$verified.' fajlova.');

        return self::SUCCESS;
    }
}
