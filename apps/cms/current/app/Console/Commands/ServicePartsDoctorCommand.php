<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FieldWorkOrder;
use App\Models\ServicePart;
use App\Models\ServicePartPurchaseRequest;
use App\Models\ServicePartSupplier;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class ServicePartsDoctorCommand extends Command
{
    protected $signature = 'app:service-parts-doctor {--repair : Pokreni migracije i CoreAccessSeeder}';
    protected $description = 'Proveri servisni lager, rezervacije, nabavku, dozvole i integraciju sa radnim nalozima';

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
            'service_part_suppliers' => ['id', 'code', 'name', 'lead_time_days', 'is_active'],
            'service_parts' => ['id', 'sku', 'name', 'stock_quantity', 'reserved_quantity', 'minimum_quantity', 'average_cost_rsd', 'is_active'],
            'field_work_order_parts' => ['id', 'field_work_order_id', 'service_part_id', 'supply_mode', 'requested_quantity', 'reserved_quantity', 'consumed_quantity'],
            'service_part_movements' => ['id', 'event_key', 'service_part_id', 'movement_type', 'stock_change', 'reserved_change', 'stock_before', 'stock_after'],
            'service_part_purchase_requests' => ['id', 'request_number', 'supplier_id', 'status', 'expected_at', 'received_at', 'total_cost_rsd'],
            'service_part_purchase_request_items' => ['id', 'purchase_request_id', 'service_part_id', 'ordered_quantity', 'received_quantity', 'unit_cost_rsd'],
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
            $permissionCount = \App\Models\Permission::query()->whereIn('slug', ['service_parts.view', 'service_parts.manage', 'service_parts.procurement'])->count();
            if ($permissionCount !== 3) {
                $this->line('<fg=red>FAIL</> Nedostaju dozvole servisnog lagera.');
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> Dozvole servisnog lagera su dostupne.');
            }

            foreach ([
                'admin.service-parts.index', 'admin.service-parts.store', 'admin.service-parts.adjust',
                'admin.field-operations.parts.store', 'admin.field-operations.parts.reserve',
                'admin.service-part-suppliers.index', 'admin.service-part-purchases.index',
                'admin.service-part-purchases.show', 'admin.service-part-purchases.receive',
            ] as $routeName) {
                if (!Route::has($routeName)) {
                    $this->line('<fg=red>FAIL</> Nedostaje ruta '.$routeName.'.');
                    $failed = true;
                }
            }

            ServicePart::query()->with(['preferredSupplier', 'movements'])->limit(3)->get();
            ServicePartSupplier::query()->withCount('parts')->limit(3)->get();
            ServicePartPurchaseRequest::query()->with(['supplier', 'items.part'])->limit(3)->get();
            FieldWorkOrder::query()->with(['parts.part'])->limit(3)->get();
            $this->line('<fg=green>PASS</> SQL upiti i relacije servisnog lagera rade.');
        } catch (Throwable $exception) {
            $this->line('<fg=red>FAIL</> SQL/routing provera nije uspela: '.$exception::class.': '.$exception->getMessage());
            $failed = true;
        }

        if ($failed) {
            $this->line('Pokreni: php artisan app:service-parts-doctor --repair');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
