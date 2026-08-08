<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ReceivablesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ReceivablesDoctorCommand extends Command
{
    protected $signature = 'app:receivables-doctor {--scan : Pokreni kontrolisanu proveru predmeta i automatskih opomena}';
    protected $description = 'Proverava aging, predmete naplate, rate, komunikaciju, outbox, dozvole i scheduler.';

    public function handle(ReceivablesService $service): int
    {
        $required = [
            'receivable_cases' => ['id','order_id','case_number','status','collection_stage','assigned_to','next_action_at','promised_payment_at','last_reminder_stage','last_reminder_at','closed_at'],
            'receivable_installments' => ['id','receivable_case_id','sequence_no','due_at','amount_rsd','paid_amount_rsd','status','paid_at'],
            'receivable_contacts' => ['id','receivable_case_id','order_email_outbox_id','event_key','channel','direction','note','visible_to_customer','is_automatic','contacted_at'],
            'order_email_outbox' => ['id','order_id','recipient_email','event_type','dedupe_key','status','scheduled_for'],
        ];
        $failed = false;
        foreach ($required as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->error('FAIL Nedostaje tabela '.$table); $failed = true; continue;
            }
            $missing = array_values(array_filter($columns, static fn (string $column): bool => !Schema::hasColumn($table, $column)));
            if ($missing !== []) { $this->error('FAIL '.$table.': '.implode(', ', $missing)); $failed = true; }
            else $this->info('PASS '.$table);
        }
        $permission = Schema::hasTable('permissions') && DB::table('permissions')->where('slug','receivables.manage')->exists();
        $this->line(($permission?'<fg=green>PASS</>':'<fg=red>FAIL</>').' receivables.manage');
        $failed = $failed || !$permission;
        $schedule = is_file(base_path('routes/console.php')) && str_contains((string) file_get_contents(base_path('routes/console.php')), "app:automation-run");
        $this->line(($schedule?'<fg=green>PASS</>':'<fg=red>FAIL</>').' hourly automation schedule');
        $failed = $failed || !$schedule;
        if (!$failed && $this->option('scan')) {
            $result = $service->runAutomation(true);
            $this->info(sprintf('SCAN porudžbine=%d novi=%d opomene=%d zatvoreni=%d', $result['examined'],$result['cases_created'],$result['reminders'],$result['closed']));
        }
        if ($failed) {
            $this->newLine();
            $this->warn('Pokreni: php artisan migrate --force && php artisan db:seed --class="Database\\Seeders\\CoreAccessSeeder" --force');
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
