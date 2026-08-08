<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

final class LegacyImportCommand extends Command
{
    protected $signature = 'legacy:import
        {--scope=all : all, access, catalog, settings, operations ili remaining}
        {--dry-run : Samo prikaži broj redova}
        {--truncate : Obriši podržane ciljne tabele pre importa}
        {--overwrite-settings : Prepiši postojeća Laravel podešavanja legacy vrednostima}';

    protected $description = 'Bezbedno uvezi podatke iz starog CMS-a bez upisa u legacy bazu';

    /** @var array<string,list<string>> */
    private array $keys = [
        'roles' => ['id'],
        'user_groups' => ['id'],
        'permissions' => ['id'],
        'users' => ['id'],
        'user_group_permissions' => ['group_id', 'permission_id'],
        'categories' => ['id'],
        'user_group_categories' => ['group_id', 'category_id'],
        'brands' => ['id'],
        'product_lines' => ['id'],
        'product_types' => ['id'],
        'specification_fields' => ['id'],
        'product_type_fields' => ['product_type_id', 'field_id'],
        'products' => ['id'],
        'product_categories' => ['product_id', 'category_id'],
        'product_spec_values' => ['product_id', 'field_id'],
        'product_images' => ['id'],
        'settings' => ['setting_key'],
        'bank_accounts' => ['id'],
        'orders' => ['id'],
        'order_status_history' => ['id'],
        'order_items' => ['id'],
        'order_commissions' => ['id'],
        'commission_status_history' => ['id'],
        'order_ips_qr' => ['order_id'],
        'stock_movements' => ['id'],
        'exchange_rate_history' => ['id'],
        'legacy_audit_logs' => ['id'],
    ];

    /** @var array<string,list<string>> */
    private array $scopes = [
        'access' => ['roles', 'user_groups', 'permissions', 'users', 'user_group_permissions'],
        'catalog' => [
            'categories', 'user_group_categories', 'brands', 'product_lines', 'product_types',
            'specification_fields', 'product_type_fields', 'products', 'product_categories',
            'product_spec_values', 'product_images',
        ],
        'settings' => ['settings'],
        'operations' => [
            'bank_accounts', 'orders', 'order_status_history', 'order_items', 'order_commissions',
            'commission_status_history', 'order_ips_qr', 'stock_movements', 'exchange_rate_history',
            'legacy_audit_logs',
        ],
    ];

    public function handle(): int
    {
        try {
            if ($this->call('legacy:check') !== self::SUCCESS) return self::FAILURE;
            $this->assertSeparateDatabases();
            $tables = $this->tablesForScope((string) $this->option('scope'));

            if ((bool) $this->option('dry-run')) {
                $this->displayDryRun($tables);
                return self::SUCCESS;
            }

            if ((bool) $this->option('truncate')) $this->truncateTables($tables);

            DB::transaction(function () use ($tables): void {
                foreach ($tables as $table) {
                    $total = $this->importTable($table);
                    $this->info(sprintf('%-30s %d', $table, $total));
                }
                if (in_array('users', $tables, true)) $this->restoreSelfReferences('users', 'approved_by');
                if (in_array('categories', $tables, true)) $this->restoreSelfReferences('categories', 'parent_id');
            }, 3);

            $this->newLine();
            $this->info('Import završen. Legacy baza nije menjana. Postojeći Laravel korisnici i lokalne kataloške izmene su sačuvani.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            Schema::enableForeignKeyConstraints();
            $this->error('Import nije uspeo: '.$exception->getMessage());
            return self::FAILURE;
        }
    }

    /** @return list<string> */
    private function tablesForScope(string $scope): array
    {
        $scope = trim(mb_strtolower($scope));
        if ($scope === 'all') return array_keys($this->keys);
        if ($scope === 'remaining') {
            return array_values(array_merge($this->scopes['access'], $this->scopes['catalog'], $this->scopes['settings'], $this->scopes['operations']));
        }
        if (!isset($this->scopes[$scope])) {
            throw new InvalidArgumentException('Nepoznat scope. Dozvoljeno: all, access, catalog, settings, operations, remaining.');
        }
        return $this->scopes[$scope];
    }

    /** @param list<string> $tables */
    private function displayDryRun(array $tables): void
    {
        foreach ($tables as $table) {
            $source = $table === 'legacy_audit_logs' ? 'audit_logs' : $table;
            $count = Schema::connection('legacy')->hasTable($source)
                ? DB::connection('legacy')->table($source)->count()
                : 0;
            $this->line(sprintf('%-30s %d', $table, $count));
        }
    }

    /** @param list<string> $tables */
    private function truncateTables(array $tables): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (array_reverse($tables) as $table) {
            if (Schema::hasTable($table)) DB::table($table)->truncate();
        }
        if (in_array('users', $tables, true) && Schema::hasTable('personal_access_tokens')) DB::table('personal_access_tokens')->truncate();
        if (in_array('users', $tables, true) && Schema::hasTable('password_reset_tokens')) DB::table('password_reset_tokens')->truncate();
        Schema::enableForeignKeyConstraints();
    }

    private function importTable(string $table): int
    {
        $source = $table === 'legacy_audit_logs' ? 'audit_logs' : $table;
        if (!Schema::connection('legacy')->hasTable($source)) {
            $this->warn(sprintf('%-30s preskočeno — nema legacy tabele', $table));
            return 0;
        }
        if (!Schema::hasTable($table)) {
            throw new InvalidArgumentException('Ciljna tabela ne postoji: '.$table.'. Pokreni php artisan migrate --force.');
        }

        $keys = $this->keys[$table];
        $orderColumn = $keys[0];
        $total = 0;

        DB::connection('legacy')->table($source)->orderBy($orderColumn)->chunk(300, function ($rows) use ($table, $keys, &$total): void {
            $payload = array_map(static fn ($row): array => (array) $row, $rows->all());
            $payload = array_map(fn (array $row): array => $this->transform($table, $row), $payload);
            $payload = array_values(array_filter($payload, static fn (array $row): bool => $row !== []));
            if ($payload === []) return;

            $targetColumns = Schema::getColumnListing($table);
            $payload = array_map(static fn (array $row): array => array_intersect_key($row, array_flip($targetColumns)), $payload);

            if ($table === 'users') {
                foreach ($payload as $row) DB::table('users')->insertOrIgnore($row);
            } elseif ($table === 'settings') {
                foreach ($payload as $row) {
                    $where = ['setting_key' => $row['setting_key']];
                    if ((bool) $this->option('overwrite-settings')) {
                        DB::table('settings')->updateOrInsert($where, array_diff_key($row, $where));
                    } else {
                        DB::table('settings')->insertOrIgnore($row);
                    }
                }
            } elseif ($this->preserveLocalTable($table) && !(bool) $this->option('truncate')) {
                DB::table($table)->insertOrIgnore($payload);
            } else {
                $updateColumns = array_values(array_diff(array_keys($payload[0]), $keys));
                DB::table($table)->upsert($payload, $keys, $updateColumns);
            }
            $total += count($payload);
        });

        return $total;
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function transform(string $table, array $row): array
    {
        if ($table === 'settings') unset($row['id']);
        if ($table === 'users') {
            $row['approved_by'] = null;
            $row['remember_token'] = null;
        }
        if ($table === 'categories') $row['parent_id'] = null;
        if ($table === 'product_images') {
            $row['storage_disk'] = 'legacy';
            $row['file_hash'] = null;
        }
        if ($table === 'products') {
            $row['legacy_synced_at'] = now();
            $row['locally_modified_at'] = null;
        }
        return $row;
    }

    private function preserveLocalTable(string $table): bool
    {
        return in_array($table, [
            'roles', 'user_groups', 'permissions', 'user_group_permissions', 'categories',
            'user_group_categories', 'brands', 'product_lines', 'product_types', 'specification_fields',
            'product_type_fields', 'products', 'product_categories', 'product_spec_values', 'product_images',
        ], true);
    }

    private function restoreSelfReferences(string $table, string $column): void
    {
        DB::connection('legacy')->table($table)->whereNotNull($column)->orderBy('id')->chunk(300, function ($rows) use ($table, $column): void {
            foreach ($rows as $row) {
                if (!DB::table($table)->where('id', (int) $row->id)->exists()) continue;
                if (!DB::table($table)->where('id', (int) $row->{$column})->exists()) continue;
                DB::table($table)->where('id', (int) $row->id)->whereNull($column)->update([$column => (int) $row->{$column}]);
            }
        });
    }

    private function assertSeparateDatabases(): void
    {
        $target = (string) config('database.connections.mysql.database');
        $legacy = (string) config('database.connections.legacy.database');
        if ($target === '' || $legacy === '' || hash_equals($target, $legacy)) {
            throw new InvalidArgumentException('Ciljna i legacy baza moraju biti različite.');
        }
    }
}
