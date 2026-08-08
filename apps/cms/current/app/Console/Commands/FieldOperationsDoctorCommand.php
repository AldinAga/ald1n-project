<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FieldServiceTeam;
use App\Models\FieldWorkOrder;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class FieldOperationsDoctorCommand extends Command
{
    protected $signature = 'app:field-operations-doctor {--repair : Pokreni migracije i CoreAccessSeeder}';
    protected $description = 'Proveri terenske ekipe, radne naloge, kalendar, dozvole i privatne priloge';

    public function handle(): int
    {
        if ($this->option('repair')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                $this->output->write(Artisan::output());
                Artisan::call('db:seed', ['--class' => CoreAccessSeeder::class, '--force' => true]);
                $this->output->write(Artisan::output());
            } catch (Throwable $exception) {
                $this->error('Repair nije uspeo: '.$exception->getMessage());
                return self::FAILURE;
            }
        }

        $requirements = [
            'field_service_teams' => ['id', 'code', 'name', 'team_type', 'phone', 'is_active'],
            'field_work_orders' => ['id', 'work_order_number', 'after_sales_action_id', 'field_service_team_id', 'status', 'planned_start_at', 'planned_end_at', 'en_route_at', 'on_site_at', 'completed_at', 'total_cost_rsd'],
            'field_work_order_attachments' => ['id', 'field_work_order_id', 'visibility', 'path', 'mime_type', 'size_bytes'],
            'after_sales_actions' => ['id', 'action_type', 'status', 'scheduled_at', 'due_at'],
        ];

        $failed = false;
        foreach ($requirements as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->line('<fg=red>FAIL</> Nedostaje tabela '.$table.'.');
                $failed = true;
                continue;
            }
            $missing = array_diff($columns, Schema::getColumnListing($table));
            if ($missing !== []) {
                $this->line('<fg=red>FAIL</> '.$table.' nema kolone: '.implode(', ', $missing));
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> '.$table.' je spremna.');
            }
        }

        try {
            $permissionCount = \App\Models\Permission::query()
                ->whereIn('slug', ['field_operations.view', 'field_operations.manage'])
                ->count();
            if ($permissionCount !== 2) {
                $this->line('<fg=red>FAIL</> Nedostaju dozvole terenskih operacija.');
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> Dozvole terenskih operacija su dostupne.');
            }

            foreach ([
                'admin.field-operations.index',
                'admin.field-operations.show',
                'admin.field-operations.schedule',
                'admin.field-operations.en-route',
                'admin.field-operations.on-site',
                'admin.field-operations.complete',
                'admin.field-service-teams.index',
                'field-work-order-attachments.show',
            ] as $routeName) {
                if (!Route::has($routeName)) {
                    $this->line('<fg=red>FAIL</> Nedostaje ruta '.$routeName.'.');
                    $failed = true;
                }
            }

            FieldServiceTeam::query()->orderBy('name')->limit(3)->get();
            FieldWorkOrder::query()->with(['team', 'action.case.order', 'attachments'])->limit(3)->get();
            $this->line('<fg=green>PASS</> Osnovni SQL upiti i relacije terenskog modula rade.');
        } catch (Throwable $exception) {
            $this->line('<fg=red>FAIL</> SQL/routing provera nije uspela: '.$exception::class.': '.$exception->getMessage());
            $failed = true;
        }

        if ($failed) {
            $this->line('Pokreni: php artisan app:field-operations-doctor --repair');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
