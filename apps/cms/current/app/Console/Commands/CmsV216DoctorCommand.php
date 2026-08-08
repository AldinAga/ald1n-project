<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DataQualityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Throwable;

final class CmsV216DoctorCommand extends Command
{
    protected $signature = 'app:cms-v2-1-6-doctor
        {--render : Renderuj Data Quality Center}
        {--repair : Primeni bezbedne data-quality popravke}
        {--strict : Uključi stroge performance pragove}';

    protected $description = 'Proveri v2.1.6 performance indekse, cache slojeve, Data Quality Center i runtime integritet.';

    public function handle(DataQualityService $quality): int
    {
        $failed = false;
        foreach ([
            app_path('Services/DataQualityService.php'),
            app_path('Services/CatalogReferenceCache.php'),
            app_path('Console/Commands/PerformanceDoctorCommand.php'),
            app_path('Console/Commands/DataQualityDoctorCommand.php'),
            app_path('Http/Controllers/Admin/DataQualityController.php'),
            app_path('Models/DataQualitySnapshot.php'),
            resource_path('views/admin/data-quality/index.blade.php'),
            database_path('migrations/2026_08_05_000038_create_performance_data_quality_v2_1_6.php'),
        ] as $path) {
            if (!is_file($path) || filesize($path) === 0) {
                $this->error('FAIL Nedostaje '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path).'.');
                $failed = true;
            }
        }
        if (!$failed) $this->info('PASS v2.1.6 release fajlovi postoje.');

        foreach (['admin.data-quality.index', 'admin.data-quality.repair', 'admin.data-quality.export'] as $routeName) {
            if (!Route::has($routeName)) {
                $this->error('FAIL Nedostaje ruta '.$routeName.'.');
                $failed = true;
            }
        }
        if (!$failed) $this->info('PASS Data Quality Center rute postoje.');

        $performanceExit = $this->call('app:performance-doctor', ['--strict' => (bool) $this->option('strict')]);
        if ($performanceExit !== self::SUCCESS) $failed = true;

        if ($this->option('repair')) {
            $repair = $quality->repairSafe(auth()->id());
            $this->line('Repair summary: '.json_encode($repair, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        $report = $quality->audit((int) config('performance.data_quality_sample_limit', 20));
        $quality->storeSnapshot($report, 'release', auth()->id());
        if ((int) $report['summary']['critical'] > 0) {
            $this->error('FAIL Data quality audit ima '.$report['summary']['critical'].' kritičnih problema.');
            $failed = true;
        } elseif ((int) $report['summary']['warning'] > 0) {
            $this->line('<fg=yellow>WARN</> Data quality audit ima '.$report['summary']['warning'].' upozorenja.');
        } else {
            $this->info('PASS Data quality nema kritičnih ni warning problema.');
        }

        if ($this->option('render')) {
            try {
                View::share('errors', new ViewErrorBag());
                View::make('admin.data-quality.index', [
                    'report' => $report,
                    'snapshots' => collect(),
                ])->render();
                $this->info('PASS Data Quality Center Blade prikaz se renderuje.');
            } catch (Throwable $exception) {
                $this->error('FAIL Render Data Quality Center-a: '.$exception::class.': '.$exception->getMessage());
                $failed = true;
            }
        } else {
            $this->line('SKIP Render nije tražen.');
        }

        if ($failed) {
            $this->error('v2.1.6 nije spreman.');
            return self::FAILURE;
        }

        $this->info('v2.1.6 Performance & Data Quality je spreman.');
        return self::SUCCESS;
    }
}
