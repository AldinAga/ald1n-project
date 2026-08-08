<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Admin\InventoryController;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Throwable;

final class PaymentsInventoryDoctorCommand extends Command
{
    protected $signature = 'app:payments-inventory-doctor {--repair : Pokreni migracije i CoreAccessSeeder} {--render : Renderuj napredni lager sa kompletnim layout-om}';
    protected $description = 'Proveri PDF dokumente, payment ledger, IPS podatke, ulaz robe i popis lagera.';

    /** @var array<string,list<string>> */
    private const SCHEMA = [
        'document_counters' => ['document_type', 'year', 'next_number'],
        'order_documents' => ['order_id', 'document_type', 'revision_number', 'supersedes_document_id', 'document_number', 'status', 'issued_at', 'company_name', 'customer_name', 'delivery_method_snapshot', 'delivery_recipient_snapshot', 'delivered_at_snapshot', 'ips_payload_snapshot', 'ips_qr_image_path', 'ips_qr_generated_at', 'ips_qr_error', 'cancelled_at', 'cancellation_reason'],
        'orders' => ['payment_state', 'paid_total_rsd', 'payment_due_at', 'payment_verified_at', 'completed_at', 'completed_by', 'completion_note', 'reopened_at', 'reopened_by', 'reopen_reason'],
        'order_deliveries' => ['order_id', 'delivery_method', 'delivered_at', 'recipient_name', 'proof_path', 'confirmed_by'],
        'order_payments' => ['order_id', 'payment_number', 'entry_type', 'status', 'amount_rsd', 'proof_path', 'submitted_by', 'verified_by'],
        'stock_receipts' => ['receipt_number', 'status', 'received_on', 'total_units', 'posted_at'],
        'stock_receipt_items' => ['stock_receipt_id', 'product_id', 'quantity', 'unit_cost_rsd'],
        'inventory_counts' => ['count_number', 'status', 'counted_on', 'total_variance', 'finalized_at'],
        'inventory_count_items' => ['inventory_count_id', 'product_id', 'system_quantity', 'counted_quantity', 'variance'],
        'stock_movements' => ['stock_receipt_id', 'inventory_count_id', 'product_variant_id', 'event_key', 'metadata_json'],
        'order_ips_qr' => ['order_id', 'status', 'payload_text'],
    ];

    /** @var list<string> */
    private const PERMISSIONS = ['payments.manage', 'payments.upload_proof', 'payments.view_own', 'inventory.receive', 'inventory.count', 'inventory.export', 'orders.confirm_delivery', 'orders.reopen'];

    public function handle(): int
    {
        if ($this->option('repair')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                $this->line(Artisan::output());
            } catch (Throwable $exception) {
                $this->error('Migracije nisu završene: '.$exception->getMessage());
            }
            try {
                app(CoreAccessSeeder::class)->run();
            } catch (Throwable $exception) {
                $this->error('Seeder nije završen: '.$exception->getMessage());
            }
        }

        $missing = [];
        try {
            DB::connection()->getPdo();
            foreach (self::SCHEMA as $table => $columns) {
                if (!Schema::hasTable($table)) {
                    $missing[] = $table;
                    continue;
                }
                foreach ($columns as $column) if (!Schema::hasColumn($table, $column)) $missing[] = $table.'.'.$column;
            }
        } catch (Throwable $exception) {
            $this->error('FAIL Laravel baza nije dostupna: '.$exception->getMessage());
            return self::FAILURE;
        }

        if ($missing !== []) {
            $this->error('FAIL Payments & Inventory šema nije kompletna: '.implode(', ', $missing));
            return self::FAILURE;
        }
        $this->info('PASS PDF dokumenti, Payments, IPS i Advanced Inventory šema su kompletni.');

        if (!$this->deliveryNoteTypeSupported()) {
            $this->error('FAIL order_documents.document_type ne podržava delivery_note. Pokreni: php artisan migrate --force');
            return self::FAILURE;
        }
        $this->info('PASS Tip dokumenta podržava PDF otpremnicu.');

        if (!$this->documentRevisionSchemaSupported()) {
            $this->error('FAIL Baza ne podržava novu reviziju predračuna/računa nakon storniranja. Pokreni: php artisan migrate --force');
            return self::FAILURE;
        }
        $this->info('PASS Stornirani dokumenti mogu dobiti novu reviziju.');

        $existing = DB::table('permissions')->whereIn('slug', self::PERMISSIONS)->pluck('slug')->all();
        $missingPermissions = array_values(array_diff(self::PERMISSIONS, $existing));
        if ($missingPermissions !== []) {
            $this->error('FAIL Nedostaju dozvole: '.implode(', ', $missingPermissions));
            return self::FAILURE;
        }
        $this->info('PASS Payment i inventory dozvole postoje.');

        try {
            $paymentCount = DB::table('order_payments')->count();
            $receiptCount = DB::table('stock_receipts')->count();
            $countCount = DB::table('inventory_counts')->count();
            $lowStock = DB::table('products')->whereNull('deleted_at')->where('variants_enabled', false)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();
            $lowVariantStock = Schema::hasTable('product_variants') ? DB::table('product_variants')->whereNull('deleted_at')->where('status', 'active')->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count() : 0;
            $this->info(sprintf('PASS SQL upiti su uspešni. Uplate=%d, ulazi=%d, popisi=%d, nizak lager=%d, niske varijante=%d.', $paymentCount, $receiptCount, $countCount, $lowStock, $lowVariantStock));
        } catch (Throwable $exception) {
            $this->error('FAIL SQL upiti nisu uspešni: '.$exception->getMessage());
            return self::FAILURE;
        }

        if ($this->option('render')) {
            $user = User::query()->where('status', 'active')->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))->with('role')->first();
            if (!$user) {
                $this->error('FAIL Nema aktivnog Administratora za render proveru.');
                return self::FAILURE;
            }
            try {
                Auth::setUser($user);
                if (!view()->shared('errors')) view()->share('errors', new ViewErrorBag());
                $request = Request::create('/admin/inventory', 'GET');
                $request->setUserResolver(static fn () => $user);
                $view = app(InventoryController::class)->index($request);
                $html = $view->render();
                if (!str_contains($html, 'Advanced Inventory') || !str_contains($html, 'Ulaz robe')) throw new \RuntimeException('Render nije vratio očekivani sadržaj.');
                $this->info('PASS Napredni lager i authenticated layout su uspešno renderovani.');
            } catch (Throwable $exception) {
                $this->error('FAIL Render naprednog lagera nije uspeo: '.$exception::class.': '.$exception->getMessage());
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    private function deliveryNoteTypeSupported(): bool
    {
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return true;
        }

        try {
            $column = DB::selectOne("SHOW COLUMNS FROM `order_documents` WHERE `Field` = 'document_type'");
            $definition = strtolower((string) ($column->Type ?? ''));

            return !str_starts_with($definition, 'enum(') || str_contains($definition, "'delivery_note'");
        } catch (Throwable) {
            return false;
        }
    }

    private function documentRevisionSchemaSupported(): bool
    {
        foreach (['revision_number', 'supersedes_document_id', 'cancellation_reason'] as $column) {
            if (!Schema::hasColumn('order_documents', $column)) {
                return false;
            }
        }

        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return true;
        }

        try {
            $rows = DB::select('SHOW INDEX FROM `order_documents`');
            $indexes = [];
            foreach ($rows as $row) {
                if ((int) ($row->Non_unique ?? 1) !== 0 || (string) ($row->Key_name ?? '') === 'PRIMARY') {
                    continue;
                }
                $indexes[(string) $row->Key_name][(int) $row->Seq_in_index] = (string) $row->Column_name;
            }
            foreach ($indexes as $columns) {
                ksort($columns);
                if (array_values($columns) === ['order_id', 'document_type']) {
                    return false;
                }
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

}
