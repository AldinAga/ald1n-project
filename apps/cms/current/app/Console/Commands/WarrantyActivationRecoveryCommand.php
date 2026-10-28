<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WarrantyActivationRecoveryService;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class WarrantyActivationRecoveryCommand extends Command
{
    protected $signature = 'app:warranty-activation-recover
        {--limit=25 : Najviše toliko porudžbina (1-50)}
        {--execute : Zahteva posebno odobren WARRANTY_ACTIVATION_RECOVERY_ENABLED}
        {--alert : Jednokratno posalji operateru sazetak HOLD statusa; zahteva zasebni alert opt-in}';

    protected $description = 'Bezbedna provera ili kontrolisani oporavak isporučenih, prethodno plaćenih garancija (podrazumevano samo čitanje).';

    public function handle(WarrantyActivationRecoveryService $recovery): int
    {
        try {
            $result = $recovery->run((int) $this->option('limit'), (bool) $this->option('execute'));
        } catch (Throwable $exception) {
            $this->error('HOLD_WARRANTY_RECOVERY: '.$exception::class);
            return self::FAILURE;
        }

        $this->line(sprintf(
            '%s mode=%s candidates=%d pending_total=%d recovered=%d failed=%d deferred=%d manual_review=%d manual_review_total=%d skipped=%d',
            $result['failed'] || $result['pending_total'] || $result['manual_review_total'] ? 'HOLD' : 'PASS',
            $result['mode'],
            $result['candidates'],
            $result['pending_total'],
            $result['recovered'],
            $result['failed'],
            $result['deferred'],
            $result['manual_review'],
            $result['manual_review_total'],
            $result['skipped'],
        ));

        if ((bool) $this->option('alert')) {
            if (!(bool) config('warranty_recovery.alerts_enabled', false)) {
                $this->error('HOLD_WARRANTY_RECOVERY_ALERT_DISABLED');
                return self::FAILURE;
            }

            $recipient = trim((string) config('warranty_recovery.alert_email', ''));
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $this->error('HOLD_WARRANTY_RECOVERY_ALERT_RECIPIENT_INVALID');
                return self::FAILURE;
            }

            if ($result['pending_total'] || $result['manual_review_total'] || $result['failed']) {
                try {
                    // Minimal metadata only: never include certificate, customer, token
                    // or individual order identifiers in an operational email.
                    Mail::raw(sprintf(
                        'Warranty recovery HOLD. Pending orders: %d; manual review: %d; failed: %d. Inspect the authorized internal operator ledger.',
                        $result['pending_total'],
                        $result['manual_review_total'],
                        $result['failed'],
                    ), static function (Message $message) use ($recipient): void {
                        $message->to($recipient)->subject('Ald1n warranty recovery HOLD');
                    });
                    $this->info('ALERT_DISPATCH_REQUESTED');
                } catch (Throwable $exception) {
                    $this->error('HOLD_WARRANTY_RECOVERY_ALERT_DELIVERY: '.$exception::class);
                    return self::FAILURE;
                }
            } else {
                $this->info('ALERT_NOT_SENT_NO_BACKLOG');
            }
        }

        return $result['failed'] || $result['pending_total'] || $result['manual_review_total'] ? self::FAILURE : self::SUCCESS;
    }
}
