<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\OrderEmailDispatcher;
use App\Services\ProductAnnouncementService;
use App\Services\SettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class OrderEmailsDoctorCommand extends Command
{
    protected $signature = 'app:order-emails-doctor {--dispatch : Pošalji trenutno dospele outbox poruke nakon provere}';
    protected $description = 'Proverava e-mail outbox, obaveštenja o porudžbinama i novim artiklima, scheduler, SMTP, NBS IPS QR i garancije.';

    public function handle(SettingsService $settings, OrderEmailDispatcher $dispatcher): int
    {
        $failed = false;
        $required = [
            'order_email_outbox' => [
                'id', 'order_id', 'recipient_user_id', 'document_id', 'recipient_email', 'event_type',
                'dedupe_key', 'batch_key', 'subject', 'message', 'attach_document',
                'attach_active_invoice', 'status', 'attempt_count', 'scheduled_for', 'sent_at',
                'last_error', 'metadata_json', 'created_at', 'updated_at',
            ],
            'order_documents' => [
                'ips_payload_snapshot', 'ips_qr_image_path', 'ips_qr_generated_at', 'ips_qr_error',
            ],
            'warranty_rules' => ['duration_months', 'duration_days'],
            'product_warranties' => ['duration_months', 'duration_days'],
        ];

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

        $schedulePath = base_path('routes/console.php');
        $scheduleReady = is_file($schedulePath)
            && str_contains((string) file_get_contents($schedulePath), "Schedule::command('app:order-email-dispatch')")
            && str_contains((string) file_get_contents($schedulePath), '->everyMinute()');
        $this->line(($scheduleReady ? '<fg=green>PASS</>' : '<fg=red>FAIL</>').' scheduler za e-mail outbox');
        $failed = $failed || !$scheduleReady;

        $from = trim((string) config('mail.from.address'));
        $mailer = trim((string) config('mail.default'));
        $mailReady = $from !== '' && $mailer !== '';
        $this->line(($mailReady ? '<fg=green>PASS</>' : '<fg=red>FAIL</>').' MAIL_FROM_ADDRESS i mailer');
        $failed = $failed || !$mailReady;

        $nbsUrl = trim((string) config('services.ips_qr.generate_url'));
        $nbsReady = filter_var($nbsUrl, FILTER_VALIDATE_URL) !== false && str_starts_with(strtolower($nbsUrl), 'https://');
        $this->line(($nbsReady ? '<fg=green>PASS</>' : '<fg=red>FAIL</>').' NBS IPS QR endpoint');
        $failed = $failed || !$nbsReady;

        $enabled = $settings->get('order_email_enabled', '1') === '1';
        $this->line(($enabled ? '<fg=green>PASS</>' : '<fg=yellow>WARN</>').' e-mail obaveštenja '.($enabled ? 'uključena' : 'isključena u podešavanjima'));

        $productInterval = (int) $settings->get('product_email_new_items_interval_minutes', '0');
        $productAnnouncementsReady = class_exists(ProductAnnouncementService::class)
            && in_array($productInterval, [0, 5, 15, 30, 60, 120, 240, 720, 1440], true);
        $productAnnouncementsEnabled = $settings->get('product_email_new_items_enabled', '0') === '1';
        $this->line(($productAnnouncementsReady ? '<fg=green>PASS</>' : '<fg=red>FAIL</>').' obaveštenja o novim artiklima su dostupna ('.($productAnnouncementsEnabled ? 'uključena' : 'isključena').')');
        $failed = $failed || !$productAnnouncementsReady;

        if (!$failed && $this->option('dispatch')) {
            $result = $dispatcher->dispatch(500);
            $this->info(sprintf('Outbox: %d grupa, %d događaja poslato, %d neuspešno.', $result['groups'], $result['sent'], $result['failed']));
            $failed = $result['failed'] > 0;
        }

        if ($failed) {
            $this->newLine();
            $this->warn('Pokreni migracije i proveri SMTP: php artisan migrate --force && php artisan app:mail-doctor');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
