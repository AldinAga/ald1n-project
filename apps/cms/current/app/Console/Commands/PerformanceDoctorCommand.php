<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class PerformanceDoctorCommand extends Command
{
    protected $signature = 'app:performance-doctor
        {--strict : Tretiraj veoma spore reprezentativne upite kao FAIL}
        {--json= : Sačuvaj JSON izveštaj u storage/app}';

    protected $description = 'Proveri ciljane indekse, permission cache, katalog cache i reprezentativne runtime upite.';

    public function handle(): int
    {
        $failed = false;
        $warnings = [];
        $checks = [];

        $requiredIndexes = [
            'products' => [
                'products_catalog_active_created_v216_idx',
                'products_owner_updated_v216_idx',
                'products_type_status_v216_idx',
            ],
            'product_images' => ['product_images_primary_sort_v216_idx'],
            'product_variants' => ['product_variants_runtime_v216_idx'],
            'users' => ['users_status_role_v216_idx'],
            'audit_logs' => ['audit_logs_level_created_v216_idx'],
        ];

        foreach ($requiredIndexes as $table => $names) {
            if (!Schema::hasTable($table)) {
                $this->error('FAIL Nedostaje tabela '.$table.'.');
                $checks[] = ['key' => 'table_'.$table, 'status' => 'failed', 'message' => 'Nedostaje tabela.'];
                $failed = true;
                continue;
            }

            $existing = $this->indexNames($table);
            foreach ($names as $name) {
                if (!in_array($name, $existing, true)) {
                    $this->error('FAIL Nedostaje indeks '.$name.' na tabeli '.$table.'. Pokreni migrate --force.');
                    $checks[] = ['key' => $name, 'status' => 'failed', 'message' => 'Indeks nedostaje.'];
                    $failed = true;
                } else {
                    $this->info('PASS Indeks '.$name.' postoji.');
                    $checks[] = ['key' => $name, 'status' => 'passed', 'message' => 'Indeks postoji.'];
                }
            }
        }

        $userSource = (string) @file_get_contents(app_path('Models/User.php'));
        $cacheSource = (string) @file_get_contents(app_path('Services/CatalogReferenceCache.php'));
        $catalogSource = (string) @file_get_contents(app_path('Http/Controllers/CatalogController.php'));
        if (str_contains($userSource, 'permissionSlugsResolved') && str_contains($userSource, 'resolvedRoleSlug')) {
            $this->info('PASS Role i permission upiti se keširaju unutar jednog requesta.');
            $checks[] = ['key' => 'access_request_cache', 'status' => 'passed', 'message' => 'Request-scoped access cache je uključen.'];
        } else {
            $this->error('FAIL User model nema request-scoped access cache.');
            $checks[] = ['key' => 'access_request_cache', 'status' => 'failed', 'message' => 'Access cache nedostaje.'];
            $failed = true;
        }

        if (str_contains($cacheSource, 'Cache::remember') && str_contains($catalogSource, 'CatalogReferenceCache')) {
            $this->info('PASS Kataloški šifarnici i filteri koriste kratkotrajni cache.');
            $checks[] = ['key' => 'catalog_reference_cache', 'status' => 'passed', 'message' => 'Catalog reference cache je uključen.'];
        } else {
            $this->error('FAIL Katalog ne koristi očekivani cache šifarnika.');
            $checks[] = ['key' => 'catalog_reference_cache', 'status' => 'failed', 'message' => 'Catalog cache nije povezan.'];
            $failed = true;
        }

        $dashboardSource = (string) @file_get_contents(app_path('Http/Controllers/DashboardController.php'));
        if (str_contains($dashboardSource, 'tableColumnsCache') && str_contains($dashboardSource, 'array_key_exists($table')) {
            $this->info('PASS Dashboard kešira schema metadata tokom requesta.');
            $checks[] = ['key' => 'dashboard_schema_cache', 'status' => 'passed', 'message' => 'Dashboard schema cache je uključen.'];
        } else {
            $this->error('FAIL Dashboard ponavlja schema metadata upite.');
            $checks[] = ['key' => 'dashboard_schema_cache', 'status' => 'failed', 'message' => 'Dashboard schema cache nedostaje.'];
            $failed = true;
        }

        $timings = [];
        foreach ($this->representativeQueries() as $key => $query) {
            try {
                $started = microtime(true);
                $query();
                $milliseconds = (int) round((microtime(true) - $started) * 1000);
                $timings[$key] = $milliseconds;
                $warning = max(50, (int) config('performance.slow_query_warning_ms', 1000));
                $failure = max($warning, (int) config('performance.slow_query_failure_ms', 3000));

                if ($this->option('strict') && $milliseconds >= $failure) {
                    $this->error('FAIL '.$key.' traje '.$milliseconds.' ms, iznad strict praga '.$failure.' ms.');
                    $checks[] = ['key' => $key, 'status' => 'failed', 'duration_ms' => $milliseconds];
                    $failed = true;
                } elseif ($milliseconds >= $warning) {
                    $message = $key.' traje '.$milliseconds.' ms, iznad upozorenja '.$warning.' ms.';
                    $this->line('<fg=yellow>WARN</> '.$message);
                    $warnings[] = $message;
                    $checks[] = ['key' => $key, 'status' => 'warning', 'duration_ms' => $milliseconds];
                } else {
                    $this->info('PASS '.$key.' traje '.$milliseconds.' ms.');
                    $checks[] = ['key' => $key, 'status' => 'passed', 'duration_ms' => $milliseconds];
                }
            } catch (Throwable $exception) {
                $this->error('FAIL '.$key.': '.$exception::class.': '.$exception->getMessage());
                $checks[] = ['key' => $key, 'status' => 'failed', 'message' => $exception->getMessage()];
                $failed = true;
            }
        }

        $report = [
            'version' => (string) config('app.version'),
            'generated_at' => now()->toIso8601String(),
            'strict' => (bool) $this->option('strict'),
            'status' => $failed ? 'failed' : ($warnings !== [] ? 'warning' : 'passed'),
            'warnings' => $warnings,
            'timings_ms' => $timings,
            'checks' => $checks,
        ];
        $this->writeJson($report);

        if ($failed) {
            $this->error('Performance audit nije spreman.');
            return self::FAILURE;
        }

        $this->info('Performance audit je spreman.');
        return self::SUCCESS;
    }

    /** @return array<string,callable():void> */
    private function representativeQueries(): array
    {
        return [
            'Aktivni katalog (18 artikala)' => static function (): void {
                if (!Schema::hasTable('products')) return;
                DB::table('products')
                    ->whereNull('deleted_at')
                    ->where('status', 'active')
                    ->orderByDesc('created_at')
                    ->limit(18)
                    ->get(['id', 'sku', 'name', 'price_amount', 'stock_quantity']);
            },
            'Dashboard agregat kataloga' => static function (): void {
                if (!Schema::hasTable('products')) return;
                DB::table('products')
                    ->whereNull('deleted_at')
                    ->selectRaw('COUNT(*) aggregate_total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) aggregate_active', ['active'])
                    ->first();
            },
            'Poslednji audit zapisi' => static function (): void {
                if (!Schema::hasTable('audit_logs')) return;
                DB::table('audit_logs')->orderByDesc('created_at')->limit(25)->get(['id', 'action', 'created_at']);
            },
        ];
    }

    /** @return list<string> */
    private function indexNames(string $table): array
    {
        try {
            return collect(Schema::getIndexes($table))
                ->pluck('name')
                ->filter(static fn ($name): bool => is_string($name) && $name !== '')
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /** @param array<string,mixed> $report */
    private function writeJson(array $report): void
    {
        $target = trim((string) $this->option('json'));
        if ($target === '') return;
        $target = basename($target);
        if (!str_ends_with($target, '.json')) $target .= '.json';
        $directory = storage_path('app/performance');
        if (!is_dir($directory)) @mkdir($directory, 0775, true);
        $path = $directory.DIRECTORY_SEPARATOR.$target;
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->line('JSON izveštaj: '.$path);
    }
}
