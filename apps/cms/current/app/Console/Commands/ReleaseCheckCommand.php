<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class ReleaseCheckCommand extends Command
{
    protected $signature = 'app:release-check
        {--profile=standard : Profil provere: quick, standard, full, rc ili stable}
        {--repair : Pokreni bezbedne migracije/seed i module koji imaju eksplicitnu repair normalizaciju}
        {--render : Uključi Blade/PDF/detail render provere}
        {--snapshot : Sačuvaj app:system-health snapshot u bazi}
        {--strict : Uključi stroge legacy grant provere i tretiraj WARN kao neuspeh}
        {--list : Samo prikaži plan bez izvršavanja}
        {--no-report : Nemoj čuvati JSON izveštaj}
        {--report= : Naziv JSON izveštaja unutar storage/app/release-check}';

    protected $description = 'Objedinjena produkciona release provera svih ključnih Ald1n CMS modula.';

    public function handle(): int
    {
        $profile = mb_strtolower(trim((string) $this->option('profile')));
        $profiles = (array) config('release.profiles', []);
        $registry = (array) config('release.checks', []);

        if (!isset($profiles[$profile]) || !is_array($profiles[$profile])) {
            $this->error('Nepoznat profil "'.$profile.'". Dozvoljeno: '.implode(', ', array_keys($profiles)).'.');

            return self::INVALID;
        }

        $plan = $this->buildPlan($profiles[$profile], $registry);
        if ($plan === null) {
            return self::FAILURE;
        }

        $this->displayHeader($profile, count($plan));

        if ($this->option('list')) {
            $this->displayPlan($plan);
            $this->newLine();
            $this->info('Plan je prikazan. Nijedna provera niti popravka nije izvršena.');

            return self::SUCCESS;
        }

        $startedAt = now();
        $results = [];
        $metadata = $this->checkReleaseMetadata();
        $results[] = $metadata;
        $this->displayMetadataResult($metadata);

        $total = count($plan);
        foreach ($plan as $index => $definition) {
            $results[] = $this->runCheck($definition, $index + 1, $total);
        }

        $summary = $this->summarize($results);
        $finishedAt = now();
        $reportPath = null;

        if (!$this->option('no-report')) {
            try {
                $reportPath = $this->saveReport([
                    'schema_version' => 1,
                    'release' => [
                        'app_version' => (string) config('app.version'),
                        'release_tag' => $this->readTrimmedFile(base_path('RELEASE-TAG')),
                        'environment' => (string) app()->environment(),
                        'profile' => $profile,
                        'repair' => (bool) $this->option('repair'),
                        'render' => (bool) $this->option('render'),
                        'snapshot' => (bool) $this->option('snapshot'),
                        'strict' => (bool) $this->option('strict'),
                    ],
                    'started_at' => $startedAt->toIso8601String(),
                    'finished_at' => $finishedAt->toIso8601String(),
                    'duration_seconds' => round($startedAt->diffInMilliseconds($finishedAt) / 1000, 3),
                    'status' => $summary['failed'] > 0 ? 'not_ready' : ($summary['warnings'] > 0 ? 'ready_with_warnings' : ($profile === 'stable' ? 'ready_for_stable' : ($profile === 'rc' ? 'ready_for_release_candidate' : 'ready_for_production'))),
                    'summary' => $summary,
                    'checks' => $results,
                ]);
            } catch (Throwable $exception) {
                $reportFailure = $exception->getMessage();
                $results[] = [
                    'key' => 'release_report',
                    'label' => 'JSON release izveštaj',
                    'group' => 'Paket',
                    'command' => null,
                    'parameters' => [],
                    'status' => 'failed',
                    'exit_code' => self::FAILURE,
                    'warning_count' => 0,
                    'skip_count' => 0,
                    'duration_seconds' => 0.0,
                    'output' => $reportFailure,
                    'output_truncated' => false,
                ];
                $summary = $this->summarize($results);
                $this->newLine();
                $this->error('JSON izveštaj nije sačuvan: '.$reportFailure);
            }
        }

        $this->displaySummary($results, $summary, $reportPath);

        if ($summary['failed'] > 0) {
            $this->newLine();
            $this->error('RELEASE CHECK: NOT READY');
            $this->line('Ispravi FAIL rezultate i ponovi istu komandu.');

            return self::FAILURE;
        }

        $this->newLine();
        if ($summary['warnings'] > 0) {
            $this->warn('RELEASE CHECK: READY WITH WARNINGS');
            $this->line('Pregledaj WARN rezultate pre finalnog puštanja u rad.');
        } else {
            $this->info(match ($profile) {
                'stable' => 'RELEASE CHECK: STABLE READY',
                'rc' => 'RELEASE CHECK: RC READY',
                default => 'RELEASE CHECK: READY FOR PRODUCTION',
            });
        }

        return self::SUCCESS;
    }

    /**
     * @param array<int|string,mixed> $profileChecks
     * @param array<string,mixed> $registry
     * @return list<array<string,mixed>>|null
     */
    private function buildPlan(array $profileChecks, array $registry): ?array
    {
        $plan = [];
        $seen = [];

        foreach ($profileChecks as $key) {
            if (!is_string($key) || $key === '') {
                $this->error('Release profil sadrži nevalidan ključ provere.');

                return null;
            }
            if (isset($seen[$key])) {
                $this->error('Release profil ponavlja proveru: '.$key.'.');

                return null;
            }
            if (!isset($registry[$key]) || !is_array($registry[$key])) {
                $this->error('Release profil koristi neregistrovanu proveru: '.$key.'.');

                return null;
            }

            $definition = $registry[$key];
            if (!isset($definition['command'], $definition['label']) || !is_string($definition['command']) || !is_string($definition['label'])) {
                $this->error('Provera '.$key.' nema validne command/label vrednosti.');

                return null;
            }

            if (($definition['requires_render'] ?? false) && !$this->option('render')) {
                continue;
            }

            $definition['key'] = $key;
            $definition['parameters'] = $this->parametersFor($definition);
            $plan[] = $definition;
            $seen[$key] = true;
        }

        return $plan;
    }

    /** @param array<string,mixed> $definition @return array<string,mixed> */
    private function parametersFor(array $definition): array
    {
        $parameters = $this->validParameters($definition['base'] ?? []);

        if ($this->option('repair')) {
            $parameters = array_merge($parameters, $this->validParameters($definition['repair'] ?? []));
        }
        if ($this->option('render')) {
            $parameters = array_merge($parameters, $this->validParameters($definition['render'] ?? []));
        }
        if ($this->option('snapshot')) {
            $parameters = array_merge($parameters, $this->validParameters($definition['snapshot'] ?? []));
        }
        if ($this->option('strict')) {
            $parameters = array_merge($parameters, $this->validParameters($definition['strict'] ?? []));
        }

        return $parameters;
    }

    /** @return array<string,mixed> */
    private function validParameters(mixed $parameters): array
    {
        if (!is_array($parameters)) {
            return [];
        }

        return array_filter(
            $parameters,
            static fn (string|int $key): bool => is_string($key) && str_starts_with($key, '--'),
            ARRAY_FILTER_USE_KEY,
        );
    }

    private function displayHeader(string $profile, int $count): void
    {
        $this->components->info('Ald1n CMS Release Check — v'.(string) config('app.version'));
        $this->line('Profil: '.$profile.' | Provera: '.$count.' | Repair: '.$this->yesNo((bool) $this->option('repair')).' | Render: '.$this->yesNo((bool) $this->option('render')).' | Strict: '.$this->yesNo((bool) $this->option('strict')));
        $this->line('Komanda je bez slanja e-mailova, dispatch-a automatizacije i kreiranja backupa.');
        $this->newLine();
    }

    /** @param list<array<string,mixed>> $plan */
    private function displayPlan(array $plan): void
    {
        $rows = [];
        foreach ($plan as $index => $definition) {
            $rows[] = [
                $index + 1,
                (string) ($definition['group'] ?? 'Ostalo'),
                (string) $definition['label'],
                $this->formatCommand((string) $definition['command'], (array) $definition['parameters']),
            ];
        }

        $this->table(['#', 'Grupa', 'Provera', 'Komanda'], $rows);
    }

    /** @return array<string,mixed> */
    private function checkReleaseMetadata(): array
    {
        $started = microtime(true);
        $configuredVersion = trim((string) config('app.version'));
        $releaseTag = $this->readTrimmedFile(base_path('RELEASE-TAG'));
        $versionFile = $this->readTrimmedFile(base_path('VERSION'));
        $changelog = is_file(base_path('CHANGELOG.md')) ? (string) file_get_contents(base_path('CHANGELOG.md')) : '';
        $expectedTag = 'v'.$configuredVersion;

        $checks = [
            'config/app.php version postoji' => $configuredVersion !== '',
            'RELEASE-TAG odgovara config verziji' => hash_equals($expectedTag, $releaseTag),
            'VERSION odgovara config verziji' => hash_equals($configuredVersion, $versionFile),
            'CHANGELOG sadrži aktivnu verziju' => str_contains($changelog, '## '.$configuredVersion.' '),
            'composer.json postoji' => is_file(base_path('composer.json')),
            'composer.lock postoji' => is_file(base_path('composer.lock')),
            'Artisan bootstrap postoji' => is_file(base_path('artisan')),
        ];

        $lines = [];
        $failed = false;
        foreach ($checks as $label => $ok) {
            $lines[] = ($ok ? 'PASS ' : 'FAIL ').$label;
            $failed = $failed || !$ok;
        }

        return [
            'key' => 'release_metadata',
            'label' => 'Release metadata',
            'group' => 'Paket',
            'command' => null,
            'parameters' => [],
            'status' => $failed ? 'failed' : 'passed',
            'exit_code' => $failed ? self::FAILURE : self::SUCCESS,
            'warning_count' => 0,
            'skip_count' => 0,
            'duration_seconds' => round(microtime(true) - $started, 3),
            'output' => implode("\n", $lines),
            'output_truncated' => false,
        ];
    }

    /** @param array<string,mixed> $result */
    private function displayMetadataResult(array $result): void
    {
        $this->line('<fg=cyan>[PREFLIGHT]</> Release metadata');
        $this->displayNestedOutput((string) $result['output']);
        $this->line($result['status'] === 'passed' ? '<fg=green>PASS</> Release metadata' : '<fg=red>FAIL</> Release metadata');
        $this->newLine();
    }

    /** @param array<string,mixed> $definition @return array<string,mixed> */
    private function runCheck(array $definition, int $position, int $total): array
    {
        $command = (string) $definition['command'];
        $parameters = (array) $definition['parameters'];
        $label = (string) $definition['label'];
        $group = (string) ($definition['group'] ?? 'Ostalo');
        $started = microtime(true);

        $this->line(sprintf('<fg=cyan>[%d/%d]</> %s — %s', $position, $total, $group, $label));
        $this->line('  '.$this->formatCommand($command, $parameters));

        $buffer = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);
        $exitCode = self::FAILURE;
        $output = '';

        try {
            $application = $this->getApplication();
            if ($application === null || !$application->has($command)) {
                throw new \RuntimeException('Artisan komanda nije registrovana: '.$command);
            }

            $exitCode = $this->runCommand($command, $parameters, $buffer);
            $output = trim($buffer->fetch());
        } catch (Throwable $exception) {
            $buffered = trim($buffer->fetch());
            $output = trim($buffered."\n".$exception::class.': '.$exception->getMessage());
            $exitCode = self::FAILURE;
        }

        $plainOutput = $this->plainOutput($output);
        $warningCount = $this->countWarnings($output);
        $skipCount = preg_match_all('/^\s*SKIP\b/im', $plainOutput) ?: 0;
        $strictWarningFailure = (bool) $this->option('strict') && $warningCount > 0;
        $failed = $exitCode !== self::SUCCESS || $strictWarningFailure;
        $status = $failed ? 'failed' : ($warningCount > 0 ? 'warning' : 'passed');

        if ($plainOutput !== '') {
            $this->displayNestedOutput($plainOutput);
        }

        $duration = round(microtime(true) - $started, 3);
        if ($failed) {
            $suffix = $strictWarningFailure && $exitCode === self::SUCCESS ? ' — WARN nije dozvoljen u strict režimu' : '';
            $this->line('<fg=red>FAIL</> '.$label.' (exit '.$exitCode.', '.$duration.' s)'.$suffix);
        } elseif ($status === 'warning') {
            $this->line('<fg=yellow>WARN</> '.$label.' ('.$warningCount.' upozorenja, '.$duration.' s)');
        } else {
            $this->line('<fg=green>PASS</> '.$label.' ('.$duration.' s)');
        }
        $this->newLine();

        [$reportOutput, $truncated] = $this->reportOutput($plainOutput);

        return [
            'key' => (string) $definition['key'],
            'label' => $label,
            'group' => $group,
            'command' => $command,
            'parameters' => $parameters,
            'status' => $status,
            'exit_code' => $exitCode,
            'warning_count' => $warningCount,
            'skip_count' => $skipCount,
            'duration_seconds' => $duration,
            'output' => $reportOutput,
            'output_truncated' => $truncated,
        ];
    }

    private function displayNestedOutput(string $output): void
    {
        foreach (preg_split('/\R/', trim($output)) ?: [] as $line) {
            $this->line('  '.$line);
        }
    }

    private function countWarnings(string $output): int
    {
        $count = 0;
        foreach (preg_split('/\R/', $output) ?: [] as $line) {
            $plain = $this->plainOutput($line);
            if ($plain === '' || preg_match('/^\s*SKIP\b/i', $plain)) {
                continue;
            }
            if (preg_match('/^\s*(?:WARN|WARNING)\b/i', $plain)
                || preg_match('/\e\[[0-9;]*(?:33|93)(?:;[0-9;]*)?m/', $line)) {
                $count++;
            }
        }

        return $count;
    }

    private function plainOutput(string $output): string
    {
        $plain = preg_replace('/\e\[[0-9;?]*[ -\/]*[@-~]/', '', $output) ?? $output;
        $plain = str_replace('\\', '/', $plain);

        return trim($plain);
    }

    /** @return array{0:string,1:bool} */
    private function reportOutput(string $output): array
    {
        $sanitized = str_replace(
            [str_replace('\\', '/', storage_path()), str_replace('\\', '/', base_path())],
            ['{STORAGE_PATH}', '{BASE_PATH}'],
            $output,
        );
        $limit = max(1000, (int) config('release.report_output_limit', 30000));
        if (mb_strlen($sanitized) <= $limit) {
            return [$sanitized, false];
        }

        return [mb_substr($sanitized, 0, $limit)."\n...[OUTPUT TRUNCATED]", true];
    }

    /** @param list<array<string,mixed>> $results @return array{total:int,passed:int,warnings:int,failed:int,skipped_lines:int} */
    private function summarize(array $results): array
    {
        $summary = [
            'total' => count($results),
            'passed' => 0,
            'warnings' => 0,
            'failed' => 0,
            'skipped_lines' => 0,
        ];

        foreach ($results as $result) {
            $status = (string) ($result['status'] ?? 'failed');
            if ($status === 'passed') {
                $summary['passed']++;
            } elseif ($status === 'warning') {
                $summary['warnings']++;
            } else {
                $summary['failed']++;
            }
            $summary['skipped_lines'] += (int) ($result['skip_count'] ?? 0);
        }

        return $summary;
    }

    /**
     * @param list<array<string,mixed>> $results
     * @param array{total:int,passed:int,warnings:int,failed:int,skipped_lines:int} $summary
     */
    private function displaySummary(array $results, array $summary, ?string $reportPath): void
    {
        $this->newLine();
        $this->components->info('Release check rezultat');

        $rows = [];
        foreach ($results as $result) {
            $status = match ((string) $result['status']) {
                'passed' => 'PASS',
                'warning' => 'WARN',
                default => 'FAIL',
            };
            $rows[] = [
                (string) ($result['group'] ?? 'Ostalo'),
                (string) $result['label'],
                $status,
                (string) $result['exit_code'],
                number_format((float) $result['duration_seconds'], 3, '.', '').' s',
            ];
        }

        $this->table(['Grupa', 'Provera', 'Status', 'Exit', 'Vreme'], $rows);
        $this->line(sprintf(
            'Ukupno: %d | PASS: %d | WARN: %d | FAIL: %d | SKIP redovi: %d',
            $summary['total'],
            $summary['passed'],
            $summary['warnings'],
            $summary['failed'],
            $summary['skipped_lines'],
        ));

        if ($reportPath !== null) {
            $this->line('JSON izveštaj: '.$reportPath);
            $this->line('Poslednji izveštaj: '.storage_path('app/'.trim((string) config('release.report_directory', 'release-check'), '/').'/latest.json'));
        }
    }

    /** @param array<string,mixed> $report */
    private function saveReport(array $report): string
    {
        $directoryName = trim((string) config('release.report_directory', 'release-check'), '/');
        if ($directoryName === '' || str_contains($directoryName, '..')) {
            throw new \RuntimeException('Nevalidan release report direktorijum.');
        }

        $directory = storage_path('app/'.$directoryName);
        File::ensureDirectoryExists($directory, 0775, true);

        $requested = trim((string) ($this->option('report') ?? ''));
        if ($requested !== '') {
            if (basename($requested) !== $requested || !preg_match('/\A[a-zA-Z0-9._-]+\.json\z/', $requested)) {
                throw new \RuntimeException('--report mora biti samo bezbedan naziv fajla koji se završava sa .json.');
            }
            $fileName = $requested;
        } else {
            $fileName = 'release-check-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.json';
        }

        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        $target = $directory.'/'.$fileName;
        $temporary = $target.'.tmp-'.bin2hex(random_bytes(4));

        if (file_put_contents($temporary, $json, LOCK_EX) === false) {
            throw new \RuntimeException('Privremeni JSON izveštaj nije mogao da se upiše.');
        }
        @chmod($temporary, 0664);
        if (!rename($temporary, $target)) {
            @unlink($temporary);
            throw new \RuntimeException('JSON izveštaj nije mogao atomski da se objavi.');
        }

        $latest = $directory.'/latest.json';
        $latestTemporary = $latest.'.tmp-'.bin2hex(random_bytes(4));
        if (file_put_contents($latestTemporary, $json, LOCK_EX) === false || !rename($latestTemporary, $latest)) {
            @unlink($latestTemporary);
            throw new \RuntimeException('latest.json nije mogao da se ažurira.');
        }
        @chmod($target, 0664);
        @chmod($latest, 0664);

        return $target;
    }

    /** @param array<string,mixed> $parameters */
    private function formatCommand(string $command, array $parameters): string
    {
        $parts = ['php artisan', $command];
        foreach ($parameters as $key => $value) {
            if ($value === false || $value === null) {
                continue;
            }
            if ($value === true) {
                $parts[] = (string) $key;
                continue;
            }
            $parts[] = (string) $key.'='.escapeshellarg((string) $value);
        }

        return implode(' ', $parts);
    }

    private function readTrimmedFile(string $path): string
    {
        return is_file($path) ? trim((string) file_get_contents($path)) : '';
    }

    private function yesNo(bool $value): string
    {
        return $value ? 'DA' : 'NE';
    }
}
