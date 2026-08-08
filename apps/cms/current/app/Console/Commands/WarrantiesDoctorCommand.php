<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class WarrantiesDoctorCommand extends Command
{
    protected $signature = 'app:warranties-doctor {--backfill : Generiši nedostajuće garancije za najviše 1000 kompletiranih porudžbina}';
    protected $description = 'Proverava garancije, pravila, održavanje, dozvole i GAR brojač.';

    public function handle(): int
    {
        $required = [
            'warranty_rules' => ['id', 'scope_type', 'duration_months', 'duration_days', 'maintenance_interval_months', 'is_active'],
            'product_warranties' => ['id', 'warranty_number', 'order_id', 'order_item_id', 'user_id', 'status', 'starts_at', 'expires_at', 'duration_months', 'duration_days', 'next_maintenance_at'],
            'warranty_maintenance_records' => ['id', 'product_warranty_id', 'status', 'due_at', 'scheduled_at', 'completed_at'],
        ];
        $failed = false;
        foreach ($required as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->error('FAIL Nedostaje tabela '.$table);
                $failed = true;
                continue;
            }
            $missing = array_values(array_filter($columns, static fn (string $column): bool => !Schema::hasColumn($table, $column)));
            if ($missing !== []) {
                $this->error('FAIL '.$table.': '.implode(', ', $missing));
                $failed = true;
            } else {
                $this->info('PASS '.$table);
            }
        }
        foreach (['warranties.view_own', 'warranties.manage'] as $permission) {
            $ok = Schema::hasTable('permissions') && DB::table('permissions')->where('slug', $permission)->exists();
            $this->line(($ok ? '<fg=green>PASS</>' : '<fg=red>FAIL</>').' '.$permission);
            $failed = $failed || !$ok;
        }
        $defaultRule = Schema::hasTable('warranty_rules') && DB::table('warranty_rules')->where('is_active', true)->exists();
        $this->line(($defaultRule ? '<fg=green>PASS</>' : '<fg=yellow>WARN</>').' aktivno pravilo garancije');

        if (!$failed && $this->option('backfill')) {
            $exit = $this->call('app:warranties-backfill', ['--limit' => 1000]);
            $failed = $exit !== self::SUCCESS;
        }
        if ($failed) {
            $this->newLine();
            $this->warn('Pokreni: php artisan migrate --force && php artisan db:seed --class="Database\\Seeders\\CoreAccessSeeder" --force');
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
