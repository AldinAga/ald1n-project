<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MobileDevice;
use App\Models\MobilePushOutbox;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

final class MobilePushDispatcher
{
    public function __construct(private readonly ExpoPushTransport $transport) {}

    /** @return array{processed:int,sent:int,retried:int,failed:int} */
    public function dispatch(int $limit = 100): array
    {
        $result = ['processed' => 0, 'sent' => 0, 'retried' => 0, 'failed' => 0];
        if (!(bool) config('mobile.push.enabled', false)) {
            return $result;
        }

        $this->failStaleProcessingRows();

        $rows = MobilePushOutbox::query()
            ->where('status', 'pending')
            ->where(static function ($query): void {
                $query->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', now());
            })
            ->orderBy('id')
            ->limit(max(1, min(500, $limit)))
            ->get();

        foreach ($rows as $row) {
            $claimed = MobilePushOutbox::query()
                ->whereKey($row->id)
                ->where('status', 'pending')
                ->update(['status' => 'processing', 'updated_at' => now()]);
            if ($claimed !== 1) {
                continue;
            }

            $row->refresh();
            $result['processed']++;
            try {
                $outcome = $this->transport->send($row);
                if ($outcome['status'] === 'ok') {
                    $row->forceFill([
                        'status' => 'sent',
                        'attempt_count' => $row->attempt_count + 1,
                        'provider_ticket_id' => $outcome['ticket_id'],
                        'sent_at' => now(),
                        'receipt_due_at' => now()->addMinutes(max(1, (int) config('mobile.push.expo.receipt_delay_minutes', 15))),
                        'last_error' => null,
                    ])->save();
                    $result['sent']++;
                    continue;
                }

                if (($outcome['error_code'] ?? null) === 'DeviceNotRegistered') {
                    $this->disableDevice($row->mobile_device_id);
                }

                if ($outcome['status'] === 'retry') {
                    if ($this->retry($row, (string) ($outcome['error'] ?? 'Privremena Expo Push greška.'))) {
                        $result['retried']++;
                    } else {
                        $result['failed']++;
                    }
                    continue;
                }

                $this->fail($row, (string) ($outcome['error'] ?? 'Expo Push poruka je odbijena.'));
                $result['failed']++;
            } catch (Throwable $exception) {
                if ($this->retry($row, $exception->getMessage())) {
                    $result['retried']++;
                } else {
                    $result['failed']++;
                }
                Log::warning('Mobile push dispatch exception.', [
                    'outbox_id' => $row->id,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /** @return array{checked:int,delivered:int,retried:int,failed:int,missing:int} */
    public function checkReceipts(int $limit = 500): array
    {
        $result = ['checked' => 0, 'delivered' => 0, 'retried' => 0, 'failed' => 0, 'missing' => 0];
        if (!(bool) config('mobile.push.enabled', false)) {
            return $result;
        }

        $rows = MobilePushOutbox::query()
            ->where('status', 'sent')
            ->whereNotNull('provider_ticket_id')
            ->whereNotNull('receipt_due_at')
            ->where('receipt_due_at', '<=', now())
            ->orderBy('id')
            ->limit(max(1, min(1000, $limit)))
            ->get();

        if ($rows->isEmpty()) {
            return $result;
        }

        $outcome = $this->transport->receipts($rows->pluck('provider_ticket_id')->filter()->values()->all());
        if ($outcome['status'] !== 'ok') {
            $message = (string) ($outcome['error'] ?? 'Expo receipt servis trenutno nije dostupan.');
            foreach ($rows as $row) {
                $row->forceFill([
                    'receipt_due_at' => now()->addMinutes(5),
                    'last_error' => $message,
                ])->save();
            }
            return $result;
        }

        $receipts = $outcome['receipts'] ?? [];
        foreach ($rows as $row) {
            $result['checked']++;
            $ticketId = (string) $row->provider_ticket_id;
            $receipt = $receipts[$ticketId] ?? null;
            if (!is_array($receipt)) {
                $this->missingReceipt($row);
                $result['missing']++;
                continue;
            }

            $row->receipt_check_count++;
            $row->receipt_checked_at = now();
            if (($receipt['status'] ?? null) === 'ok') {
                $row->forceFill([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                    'receipt_due_at' => null,
                    'last_error' => null,
                ])->save();
                $result['delivered']++;
                continue;
            }

            $code = trim((string) ($receipt['details']['error'] ?? ''));
            $message = trim((string) ($receipt['message'] ?? ($code !== '' ? $code : 'Expo Push receipt je vratio grešku.')));
            if ($code === 'DeviceNotRegistered') {
                $this->disableDevice($row->mobile_device_id);
            }
            if ($code === 'MessageRateExceeded' && $this->retryFromReceipt($row, $message)) {
                $result['retried']++;
                continue;
            }

            $this->fail($row, $message);
            $result['failed']++;
        }

        return $result;
    }

    private function failStaleProcessingRows(): void
    {
        MobilePushOutbox::query()
            ->where('status', 'processing')
            ->where('updated_at', '<', now()->subMinutes(15))
            ->update([
                'status' => 'failed',
                'failed_at' => now(),
                'last_error' => 'Push dispatch je prekinut nakon slanja/claim koraka; red nije automatski ponovljen da se izbegne mogući duplikat.',
                'updated_at' => now(),
            ]);
    }

    private function retry(MobilePushOutbox $row, string $message): bool
    {
        $attempts = $row->attempt_count + 1;
        if ($attempts >= max(1, (int) config('mobile.push.expo.max_attempts', 5))) {
            $row->attempt_count = $attempts;
            $this->fail($row, $message);
            return false;
        }

        $seconds = min(3600, 15 * (2 ** min(7, $attempts)));
        $row->forceFill([
            'status' => 'pending',
            'attempt_count' => $attempts,
            'scheduled_for' => now()->addSeconds($seconds),
            'last_error' => $this->limitError($message),
        ])->save();
        return true;
    }

    private function retryFromReceipt(MobilePushOutbox $row, string $message): bool
    {
        if ($row->attempt_count >= max(1, (int) config('mobile.push.expo.max_attempts', 5))) {
            return false;
        }

        $row->forceFill([
            'status' => 'pending',
            'provider_ticket_id' => null,
            'sent_at' => null,
            'receipt_due_at' => null,
            'scheduled_for' => now()->addMinutes(2),
            'last_error' => $this->limitError($message),
        ])->save();
        return true;
    }

    private function missingReceipt(MobilePushOutbox $row): void
    {
        $row->receipt_check_count++;
        $row->receipt_checked_at = now();
        $sentAt = $row->sent_at instanceof Carbon ? $row->sent_at : null;
        if ($sentAt !== null && $sentAt->lt(now()->subHours(23))) {
            $this->fail($row, 'Expo Push receipt nije pronađen pre isteka 24h prozora.');
            return;
        }

        $row->receipt_due_at = now()->addMinutes(5);
        $row->last_error = 'Expo Push receipt još nije dostupan.';
        $row->save();
    }

    private function fail(MobilePushOutbox $row, string $message): void
    {
        $row->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'receipt_due_at' => null,
            'last_error' => $this->limitError($message),
        ])->save();
    }

    private function disableDevice(?int $deviceId): void
    {
        if (!$deviceId) {
            return;
        }
        MobileDevice::query()->whereKey($deviceId)->update([
            'push_provider' => null,
            'push_token' => null,
            'push_token_hash' => null,
            'notifications_enabled' => false,
            'updated_at' => now(),
        ]);
    }

    private function limitError(string $message): string
    {
        return mb_substr(trim($message), 0, 2000);
    }
}
