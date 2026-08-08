<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LegacyReadOnlyGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use LogicException;
use Throwable;

final class LegacyCheckCommand extends Command
{
    protected $signature = 'legacy:check {--strict-grants : Zahtevaj isključivo SELECT/SHOW VIEW DB grantove}';
    protected $description = 'Proveri legacy konekciju, tabele i višeslojnu read-only zaštitu';

    public function handle(LegacyReadOnlyGuard $guard): int
    {
        try {
            $connection = DB::connection('legacy');
            $database = $connection->getDatabaseName();
            $this->info('Legacy baza: '.$database);

            $sessionReadOnly = $this->sessionIsReadOnly($connection);
            $guardActive = $this->mutationGuardIsActive($connection);
            $this->line(($sessionReadOnly ? '<fg=green>PASS</>' : '<fg=red>FAIL</>').' legacy session read-only');
            $this->line(($guardActive ? '<fg=green>PASS</>' : '<fg=red>FAIL</>').' legacy SQL guard');
            if (!$sessionReadOnly || !$guardActive) {
                return self::FAILURE;
            }

            $grants = array_map(
                static fn ($row): string => (string) array_values((array) $row)[0],
                $connection->select('SHOW GRANTS FOR CURRENT_USER()'),
            );
            $violations = $guard->grantViolations($grants);
            if ($violations === []) {
                $this->info('Legacy grantovi: SELECT/SHOW VIEW least-privilege PASS');
            } elseif ($this->option('strict-grants')) {
                $this->error('Legacy korisnik ima šire write/DDL grantove.');
                foreach ($violations as $violation) {
                    $this->line('  - '.$violation);
                }
                return self::FAILURE;
            } else {
                $this->warn('Legacy DB grantovi su širi od preporučenih, ali runtime read-only session i SQL guard su aktivni.');
                foreach ($violations as $violation) {
                    $this->line('  - '.$violation);
                }
                $this->line('Stroga provera: php artisan legacy:check --strict-grants');
            }

            foreach ([
                'roles', 'user_groups', 'permissions', 'users', 'user_group_permissions',
                'categories', 'user_group_categories', 'brands', 'product_lines', 'product_types',
                'specification_fields', 'product_type_fields', 'products', 'product_categories',
                'product_spec_values', 'product_images', 'settings',
                'bank_accounts', 'orders', 'order_status_history', 'order_items', 'order_commissions',
                'commission_status_history', 'order_ips_qr', 'stock_movements', 'exchange_rate_history', 'audit_logs',
            ] as $table) {
                $this->line(sprintf('%-30s %d', $table, $connection->table($table)->count()));
            }
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Legacy konekcija nije uspela: '.$exception->getMessage());
            return self::FAILURE;
        }
    }

    private function sessionIsReadOnly($connection): bool
    {
        foreach (['transaction_read_only', 'tx_read_only'] as $variable) {
            try {
                $row = $connection->selectOne('SELECT @@SESSION.'.$variable.' AS read_only_value');
                if ($row !== null) {
                    return (int) ($row->read_only_value ?? 0) === 1;
                }
            } catch (Throwable) {
            }
        }

        return false;
    }

    private function mutationGuardIsActive($connection): bool
    {
        try {
            $connection->statement('SET @ald1n_legacy_guard_probe = 1');
            return false;
        } catch (LogicException) {
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
