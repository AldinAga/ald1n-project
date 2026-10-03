<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

final class IpsPaymentPayloadService
{
    public function payload(Order $order, ?float $amountRsd = null): ?string
    {
        if ($order->payment_method !== 'bank_transfer' || trim((string) $order->bank_account_number_snapshot) === '') {
            return null;
        }

        $amount = $amountRsd ?? max(0.0, (float) $order->subtotal_rsd - (float) ($order->paid_total_rsd ?? 0));
        $amount = round(max(0.0, $amount), 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['ips_qr' => 'NBS IPS QR zahteva iznos veći od nule.']);
        }

        $account = preg_replace('/\D+/', '', (string) $order->bank_account_number_snapshot) ?? '';
        if (strlen($account) !== 18) {
            throw ValidationException::withMessages(['ips_qr' => 'Broj računa za NBS IPS QR mora imati tačno 18 cifara.']);
        }

        $recipientName = $this->cleanLine((string) ($order->payment_recipient_name_snapshot ?: $order->supplier_name_snapshot), 70);
        $recipientAddress = $this->cleanLine((string) $order->payment_recipient_address_snapshot, 70);
        $payerName = $this->cleanLine((string) ($order->shipping_full_name ?: $order->user?->displayName()), 70);
        $payerAddress = $this->cleanLine(trim((string) $order->shipping_address), 70);
        $payerCity = $this->cleanLine(trim((string) $order->shipping_postal_code.' '.(string) $order->shipping_city), 70);
        $purpose = $this->cleanLine((string) ($order->payment_purpose_snapshot ?: 'Plaćanje porudžbine '.$order->order_number), 35);
        $paymentCode = preg_replace('/\D+/', '', (string) ($order->payment_code_snapshot ?: '221')) ?? '';

        $missing = [];
        if ($recipientName === '') $missing[] = 'naziv primaoca';
        if ($payerName === '') $missing[] = 'ime platioca';
        if ($purpose === '') $missing[] = 'svrha plaćanja';
        if (strlen($paymentCode) !== 3) $missing[] = 'tro-cifrena šifra plaćanja';
        if ($missing !== []) {
            throw ValidationException::withMessages(['ips_qr' => 'Za NBS IPS QR nedostaje: '.implode(', ', $missing).'.']);
        }

        $recipient = $this->multiline([$recipientName, $recipientAddress], 70);
        $payer = $this->multiline([$payerName, $payerAddress, $payerCity], 70);
        $parts = [
            'K:PR',
            'V:01',
            'C:1',
            'R:'.$account,
            'N:'.$recipient,
            'I:RSD'.number_format($amount, 2, ',', ''),
            'P:'.$payer,
            'SF:'.$paymentCode,
            'S:'.$purpose,
        ];

        $reference = $this->cleanReference((string) ($order->payment_reference_snapshot ?: $order->id));
        if ($reference !== '') {
            $parts[] = 'RO:00'.$reference;
        }

        return implode('|', $parts);
    }

    public function persist(Order $order, ?float $amountRsd = null): ?string
    {
        // BATCH511_V2_PAYMENT_IPS_CACHE_GUARD
        // Derived IPS cache must never roll back canonical payment ledger state.
        try {
            if ($this->shouldClearCachedPayload($order, $amountRsd)) {
                $this->clearCachedPayload($order);
                return null;
            }

            $payload = $this->payload($order, $amountRsd);
            if ($payload === null) {
                $this->clearCachedPayload($order);
                return null;
            }
            if (!Schema::hasTable('order_ips_qr')) return $payload;
            $columns = Schema::getColumnListing('order_ips_qr');
            if (array_diff(['order_id', 'status', 'payload_text'], $columns) !== []) return $payload;

            $values = ['status' => 'ready', 'payload_text' => $payload];
            if (in_array('error_message', $columns, true)) $values['error_message'] = null;
            if (in_array('generated_at', $columns, true)) $values['generated_at'] = now();
            if (in_array('updated_at', $columns, true)) $values['updated_at'] = now();
            if (in_array('created_at', $columns, true)) $values['created_at'] = now();
            DB::table('order_ips_qr')->updateOrInsert(['order_id' => $order->id], $values);
        } catch (Throwable $exception) {
            // Never leave a previously ready cache row authoritative after a failed refresh.
            try {
                $this->clearCachedPayload($order);
            } catch (Throwable) {
            }

            try {
                Log::warning('IPS payload je generisan, ali nije sačuvan.', [
                    'order_id' => $order->id,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            } catch (Throwable) {
            }
        }

        return $payload;
    }

    private function shouldClearCachedPayload(Order $order, ?float $amountRsd = null): bool
    {
        if ((string) $order->payment_method !== 'bank_transfer') return true;
        if ((string) $order->status === 'cancelled') return true;
        if (in_array((string) $order->payment_state, ['paid', 'overpaid', 'cancelled', 'refunded'], true)) return true;

        return $this->outstandingAmount($order, $amountRsd) <= 0.004;
    }

    private function clearCachedPayload(Order $order): void
    {
        if (!$order->exists || (int) $order->getKey() <= 0) return;
        if (!Schema::hasTable('order_ips_qr')) return;

        $columns = Schema::getColumnListing('order_ips_qr');
        if (!in_array('order_id', $columns, true)) return;

        DB::table('order_ips_qr')->where('order_id', (int) $order->getKey())->delete();
    }

    private function outstandingAmount(Order $order, ?float $amountRsd = null): float
    {
        $amount = $amountRsd ?? max(0.0, (float) $order->subtotal_rsd - (float) ($order->paid_total_rsd ?? 0));

        return round(max(0.0, $amount), 2);
    }
    private function cleanLine(string $value, int $maxLength): string
    {
        $value = trim(preg_replace('/[|\r\n]+/u', ' ', $value) ?? $value);
        return $this->limit($value, $maxLength);
    }

    /** @param list<string> $lines */
    private function multiline(array $lines, int $maxLength): string
    {
        $value = implode("\r\n", array_values(array_filter(array_map('trim', $lines), static fn (string $line): bool => $line !== '')));
        return $this->limit($value, $maxLength);
    }

    private function cleanReference(string $value): string
    {
        $value = preg_replace('/[^0-9A-Za-z\-]/u', '', trim($value)) ?? '';
        return $this->limit($value, 20);
    }

    private function limit(string $value, int $length): string
    {
        return function_exists('mb_substr') ? (string) mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
    }
}
