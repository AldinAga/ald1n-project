<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ManagementReportService;
use App\Services\OrderItemCostSnapshotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class ManagementReportsDoctorCommand extends Command
{
    protected $signature = 'app:management-reports-doctor {--repair : Pokreni migracije i bezbedno dopuni nepotpune finansijske snapshotove} {--render : Generiši probni PDF}';
    protected $description = 'Proverava profitabilnost, nabavne snapshotove, rasporede i izvoz upravljačkih izveštaja.';

    public function handle(ManagementReportService $reports, OrderItemCostSnapshotService $costSnapshots): int
    {
        $failed = false;

        if ($this->option('repair')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                $this->output->write(Artisan::output());
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CoreAccessSeeder', '--force' => true]);

                $repair = $costSnapshots->repairMissing();
                if ($repair['repaired'] > 0) {
                    $this->line('<fg=green>PASS</> Dopunjeni snapshotovi nabavne cene: '.$repair['repaired'].'.');
                }
                if ($repair['errors'] > 0) {
                    $this->line('<fg=red>FAIL</> Greške tokom dopune snapshotova: '.$repair['errors'].'. Pregledaj storage/logs/laravel.log.');
                    $failed = true;
                }
            } catch (Throwable $exception) {
                $this->error('Repair nije uspeo: '.$exception->getMessage());

                return self::FAILURE;
            }
        }

        $schema = [
            'products' => ['purchase_price_rsd'],
            'order_items' => ['purchase_unit_rsd_snapshot', 'purchase_total_rsd_snapshot', 'cost_source_snapshot', 'brand_name_snapshot', 'product_line_name_snapshot', 'product_type_name_snapshot'],
            'report_schedules' => ['name', 'frequency', 'recipients_json', 'formats_json', 'next_run_at', 'is_active'],
            'report_deliveries' => ['report_schedule_id', 'recipient_email', 'period_from', 'period_to', 'status', 'dedupe_key'],
        ];
        foreach ($schema as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->line('<fg=red>FAIL</> Nedostaje tabela '.$table.'.');
                $failed = true;
                continue;
            }
            $missing = array_values(array_diff($columns, Schema::getColumnListing($table)));
            if ($missing !== []) {
                $this->line('<fg=red>FAIL</> '.$table.' nema: '.implode(', ', $missing));
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> '.$table.' je spremna.');
            }
        }

        foreach (['admin.reports.index', 'admin.reports.management.pdf', 'admin.reports.management.csv', 'admin.report-schedules.store'] as $route) {
            if (!Route::has($route)) {
                $this->line('<fg=red>FAIL</> Nedostaje ruta '.$route);
                $failed = true;
            }
        }

        if (!Schema::hasTable('permissions') || !DB::table('permissions')->where('slug', 'reports.manage')->exists()) {
            $this->line('<fg=red>FAIL</> Nedostaje reports.manage dozvola.');
            $failed = true;
        }

        $missingCosts = $costSnapshots->missingCount();
        $this->line(($missingCosts > 0 ? '<fg=yellow>WARN</>' : '<fg=green>PASS</>').' Stavke bez kompletne nabavne cene: '.$missingCosts.'.');
        if ($missingCosts > 0) {
            foreach ($costSnapshots->inspectMissing(10) as $row) {
                $candidate = $row->candidate_unit_rsd === null
                    ? 'nema automatskog kandidata'
                    : number_format((float) $row->candidate_unit_rsd, 2, ',', '.').' RSD / '.(string) $row->candidate_source;
                $this->line(sprintf(
                    '  Stavka #%d | porudžbina %s | SKU %s | %s',
                    (int) $row->id,
                    (string) ($row->order_number ?: '#'.(int) $row->order_id),
                    (string) ($row->product_sku ?: '—'),
                    $candidate,
                ));
            }
            $this->line('  Pokreni: php artisan app:order-cost-snapshots --repair');
        }

        if ($this->option('render') && !$failed) {
            $actor = User::query()
                ->where('status', 'active')
                ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
                ->first();
            if (!$actor) {
                $this->warn('Nema aktivnog SuperAdministratora za render test.');
            } else {
                try {
                    $pdf = $reports->pdf($actor, [
                        'date_from' => now()->startOfMonth()->format('Y-m-d'),
                        'date_to' => now()->format('Y-m-d'),
                        'scope' => 'all',
                    ]);
                    $valid = str_starts_with($pdf, '%PDF-');
                    $this->line($valid ? '<fg=green>PASS</> PDF render je validan.' : '<fg=red>FAIL</> PDF potpis nije validan.');
                    if (!$valid) {
                        $failed = true;
                    }
                } catch (Throwable $exception) {
                    $this->error('PDF render nije uspeo: '.$exception->getMessage());
                    $failed = true;
                }
            }
        }

        if ($failed) {
            $this->warn('Pokreni: php artisan app:management-reports-doctor --repair --render');

            return self::FAILURE;
        }

        $this->info('Upravljački izveštaji su spremni.');

        return self::SUCCESS;
    }
}
