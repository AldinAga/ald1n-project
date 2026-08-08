<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DataQualityService;
use Illuminate\Console\Command;

final class DataQualityDoctorCommand extends Command
{
    protected $signature = 'app:data-quality-doctor
        {--repair : Primeni isključivo bezbedne i nedestruktivne popravke}
        {--limit=20 : Broj primera po grupi problema}
        {--json= : Sačuvaj JSON izveštaj u storage/app/data-quality}';

    protected $description = 'Proveri duplikate, vlasništvo, fotografije, varijante, kategorije, specifikacije i kompletnost podataka.';

    public function handle(DataQualityService $quality): int
    {
        $limit = max(1, min(100, (int) $this->option('limit')));
        if ($this->option('repair')) {
            $summary = $quality->repairSafe(auth()->id());
            $this->line('Bezbedna popravka: '.json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $report = $quality->audit($limit);
        $quality->storeSnapshot($report, 'command', auth()->id());

        foreach ((array) $report['issues'] as $issue) {
            $count = (int) ($issue['count'] ?? 0);
            if ($count === 0) {
                $this->info('PASS '.(string) $issue['label'].'.');
                continue;
            }

            $line = (string) $issue['label'].': '.$count.'. '.(string) $issue['description'];
            if (($issue['severity'] ?? 'info') === 'critical') {
                $this->line('<fg=red>FAIL</> '.$line);
            } elseif (($issue['severity'] ?? 'info') === 'warning') {
                $this->line('<fg=yellow>WARN</> '.$line);
            } else {
                $this->line('<fg=cyan>INFO</> '.$line);
            }
        }

        $this->line(sprintf(
            'Score: %d/100 | Critical: %d | Warning: %d | Informativno: %d | Trajanje: %d ms',
            (int) $report['score'],
            (int) $report['summary']['critical'],
            (int) $report['summary']['warning'],
            (int) $report['summary']['info'],
            (int) $report['duration_ms'],
        ));
        $this->writeJson($report);

        if ((int) $report['summary']['critical'] > 0) {
            $this->error('Data quality audit ima kritične probleme.');
            return self::FAILURE;
        }

        $this->info('Data quality audit je završen.');
        return self::SUCCESS;
    }

    /** @param array<string,mixed> $report */
    private function writeJson(array $report): void
    {
        $target = trim((string) $this->option('json'));
        if ($target === '') return;
        $target = basename($target);
        if (!str_ends_with($target, '.json')) $target .= '.json';
        $directory = storage_path('app/data-quality');
        if (!is_dir($directory)) @mkdir($directory, 0775, true);
        $path = $directory.DIRECTORY_SEPARATOR.$target;
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->line('JSON izveštaj: '.$path);
    }
}
