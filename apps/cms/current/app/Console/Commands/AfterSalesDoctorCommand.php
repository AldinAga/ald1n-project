<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AfterSalesAction;
use App\Models\AfterSalesCase;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class AfterSalesDoctorCommand extends Command
{
    protected $signature = 'app:after-sales-doctor {--repair : Pokreni migracije i CoreAccessSeeder}';
    protected $description = 'Proveri postprodajni modul, dozvole, privatne priloge i osnovne upite';

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
            'after_sales_cases' => ['id', 'case_number', 'order_id', 'opened_by', 'assigned_to', 'case_type', 'priority', 'status', 'due_at'],
            'after_sales_case_items' => ['id', 'after_sales_case_id', 'product_name_snapshot', 'quantity'],
            'after_sales_messages' => ['id', 'after_sales_case_id', 'user_id', 'visibility', 'body'],
            'after_sales_attachments' => ['id', 'after_sales_case_id', 'disk', 'path', 'mime_type'],
            'after_sales_status_history' => ['id', 'after_sales_case_id', 'to_status', 'actor_id'],
            'after_sales_actions' => ['id', 'action_number', 'after_sales_case_id', 'action_type', 'status', 'inventory_handling', 'due_at'],
            'after_sales_action_items' => ['id', 'after_sales_action_id', 'after_sales_case_item_id', 'quantity', 'disposition', 'stock_effect'],
            'order_payments' => ['id', 'order_id', 'after_sales_action_id', 'entry_type', 'status', 'amount_rsd'],
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
            $permissions = \App\Models\Permission::query()->whereIn('slug', ['after_sales.create', 'after_sales.view_own', 'after_sales.manage', 'after_sales.execute'])->count();
            if ($permissions !== 4) {
                $this->line('<fg=red>FAIL</> Nedostaju postprodajne dozvole.');
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> Postprodajne dozvole su dostupne.');
            }

            AfterSalesCase::query()->with(['order', 'items', 'messages', 'attachments', 'actions.items'])->limit(3)->get();
            AfterSalesAction::query()->with(['case.order', 'items', 'payment'])->limit(3)->get();
            User::query()->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))->limit(1)->get();
            $this->line('<fg=green>PASS</> Osnovni SQL upiti postprodajnog modula rade.');
        } catch (Throwable $exception) {
            $this->line('<fg=red>FAIL</> SQL provera nije uspela: '.$exception::class.': '.$exception->getMessage());
            $failed = true;
        }

        if ($failed) {
            $this->line('Pokreni: php artisan app:after-sales-doctor --repair');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
