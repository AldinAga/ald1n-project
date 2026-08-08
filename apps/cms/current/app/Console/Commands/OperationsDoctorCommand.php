<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Admin\CommissionController;
use App\Models\User;
use App\Services\CommissionReportService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Throwable;

final class OperationsDoctorCommand extends Command
{
    protected $signature = 'app:operations-doctor
        {--repair : Pokreni migracije i CoreAccessSeeder}
        {--render : Renderuj administratorsku stranicu provizija}';

    protected $description = 'Proveri Operational Orders & Commissions šemu, upite, dozvole i render';

    /** @var array<string,list<string>> */
    private const SCHEMA = [
        'orders' => ['supplier_user_id', 'assigned_by', 'accepted_by', 'accepted_at', 'expected_processing_at', 'expected_shipping_at', 'last_internal_note_at', 'completed_at', 'completed_by', 'completion_note', 'reopened_at', 'reopened_by', 'reopen_reason'],
        'order_deliveries' => ['order_id', 'delivery_method', 'delivered_at', 'recipient_name', 'recipient_phone', 'reference', 'note', 'proof_disk', 'proof_path', 'confirmed_by'],
        'order_internal_notes' => ['order_id', 'user_id', 'note'],
        'order_assignments' => ['order_id', 'old_supplier_user_id', 'new_supplier_user_id', 'changed_by', 'reason'],
        'order_commissions' => ['payment_batch_id', 'payment_method', 'payment_reference', 'status_updated_at'],
        'commission_status_history' => ['commission_id', 'metadata_json'],
        'commission_payment_batches' => ['batch_number', 'payment_method', 'commission_count', 'total_eur', 'paid_at'],
        'notifications' => ['id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at'],
    ];

    public function handle(CommissionReportService $reports): int
    {
        if ($this->option('repair')) {
            try {
                $exit = Artisan::call('migrate', ['--force' => true]);
                $this->output->write(Artisan::output());
                if ($exit !== self::SUCCESS) return self::FAILURE;
                Artisan::call('db:seed', ['--class' => CoreAccessSeeder::class, '--force' => true]);
                $this->output->write(Artisan::output());
            } catch (Throwable $exception) {
                $this->error('Repair nije uspeo: '.$exception::class.': '.$exception->getMessage());
                return self::FAILURE;
            }
        }

        $failed = false;
        foreach (self::SCHEMA as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->line('<fg=red>FAIL</> Nedostaje tabela '.$table.'.');
                $failed = true;
                continue;
            }
            $missing = array_diff($columns, Schema::getColumnListing($table));
            if ($missing !== []) {
                $this->line('<fg=red>FAIL</> Nedostaju kolone: '.$table.'.'.implode(', '.$table.'.', $missing));
                $failed = true;
            }
        }
        if ($failed) return self::FAILURE;
        $this->line('<fg=green>PASS</> Operational Orders & Commissions šema je kompletna.');

        $requiredPermissions = ['commissions.view_own', 'commissions.manage', 'orders.reassign', 'orders.internal_notes', 'orders.confirm_delivery', 'orders.reopen', 'notifications.view'];
        $existing = DB::table('permissions')->whereIn('slug', $requiredPermissions)->pluck('slug')->all();
        $missingPermissions = array_diff($requiredPermissions, $existing);
        if ($missingPermissions !== []) {
            $this->line('<fg=red>FAIL</> Nedostaju dozvole: '.implode(', ', $missingPermissions));
            return self::FAILURE;
        }
        $this->line('<fg=green>PASS</> Operativne dozvole postoje.');

        $actor = User::query()->where('status', 'active')->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))->with('role')->orderBy('id')->first();
        if (!$actor instanceof User) {
            $this->line('<fg=red>FAIL</> Nema aktivnog Administratora za proveru.');
            return self::FAILURE;
        }

        try {
            $summary = $reports->summaryManaged($actor, []);
            $page = $reports->paginateManaged($actor, [], 5);
            $unread = $actor->unreadNotifications()->count();
            $this->line('<fg=green>PASS</> Provizije, scope i notifications upiti su uspešni.');
            $this->line(sprintf('Korisnik #%d, provizije=%d, isplaćeno=%.2f EUR, prvi page=%d, nepročitano=%d.', $actor->id, $summary['count'], $summary['paid_eur'], $page->count(), $unread));
        } catch (Throwable $exception) {
            $this->line('<fg=red>FAIL</> Operativni upit nije uspeo: '.$exception::class.': '.$exception->getMessage());
            return self::FAILURE;
        }

        if ($this->option('render')) {
            try {
                Auth::guard()->setUser($actor);
                $request = Request::create('/admin/commissions', 'GET');
                $request->setUserResolver(static fn (): User => $actor);
                $request->setLaravelSession(app('session')->driver());
                app()->instance('request', $request);
                View::share('errors', new ViewErrorBag());
                $response = app(CommissionController::class)->index($request, $reports);
                $html = (string) $response->getContent();
                if (!str_contains($html, 'Masovna isplata') || !str_contains($html, 'Provizije')) {
                    $this->line('<fg=red>FAIL</> Render nema očekivani beta2 sadržaj.');
                    return self::FAILURE;
                }
                $this->line('<fg=green>PASS</> Administratorska stranica provizija je uspešno renderovana.');
            } catch (Throwable $exception) {
                $this->line('<fg=red>FAIL</> Render nije uspeo: '.$exception::class.': '.$exception->getMessage());
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
