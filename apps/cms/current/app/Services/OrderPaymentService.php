<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AfterSalesAction;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class OrderPaymentService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
        private readonly IpsPaymentPayloadService $ips,
        private readonly OrderEmailOutboxService $emails,
        private readonly ReceivablesService $receivables,
    ) {}

    /** @param array<string,mixed> $data */
    public function submitProof(Order $order, UploadedFile $file, array $data, User $actor): OrderPayment
    {
        if ((int) $order->user_id !== (int) $actor->id) abort(404);
        $this->assertOrderOpen($order, 'proof');
        // Imported orders are immutable only for stock/inventory operations. Their local
        // financial ledger remains writable so old completed orders can be reconciled.
        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages(['proof' => 'Potvrda se ne može poslati za otkazanu porudžbinu.']);
        }
        if ($order->payment_method !== 'bank_transfer') {
            throw ValidationException::withMessages(['proof' => 'Potvrda uplate je dostupna samo za uplatu na račun.']);
        }

        $path = $file->storeAs(
            'payment-proofs/order-'.$order->id,
            now()->format('YmdHis').'-'.bin2hex(random_bytes(8)).'.'.strtolower($file->getClientOriginalExtension() ?: 'bin'),
            'local',
        );
        if (!is_string($path) || $path === '') {
            throw ValidationException::withMessages(['proof' => 'Čuvanje potvrde nije uspelo.']);
        }

        try {
            $payment = DB::transaction(function () use ($order, $file, $data, $actor, $path): OrderPayment {
                /** @var Order $locked */
                $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
                $this->assertOrderOpen($locked, 'proof');
                $payment = OrderPayment::query()->create([
                    'order_id' => $locked->id,
                    'payment_number' => $this->numbers->next('payment', (int) now()->format('Y')),
                    'entry_type' => 'payment',
                    'status' => 'submitted',
                    'amount_rsd' => (float) $data['amount_rsd'],
                    'payment_method' => 'bank_transfer',
                    'paid_at' => $data['paid_at'],
                    'reference' => trim((string) ($data['reference'] ?? '')) ?: null,
                    'note' => trim((string) ($data['note'] ?? '')) ?: null,
                    'proof_path' => $path,
                    'proof_original_name' => $file->getClientOriginalName(),
                    'proof_mime_type' => $file->getMimeType(),
                    'proof_size_bytes' => $file->getSize(),
                    'submitted_by' => $actor->id,
                ]);
                $this->audit->log('order.payment_proof_submitted', 'Poslata potvrda uplate '.$payment->payment_number, $payment, after: ['amount_rsd' => $payment->amount_rsd, 'status' => 'submitted'], user: $actor);
                return $payment;
            }, 5);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        $order->loadMissing('supplier');
        if ($order->supplier instanceof User) {
            $this->notifications->order($order->supplier, 'order.payment_proof_submitted', 'Nova potvrda uplate', sprintf('%s je poslao potvrdu uplate od %s RSD za %s.', $actor->displayName(), number_format((float) $payment->amount_rsd, 2, ',', '.'), $order->order_number), $order, ['icon' => 'wallet', 'severity' => 'warning']);
        }
        $this->emails->orderChanged($order->fresh(['user', 'supplier']) ?? $order, 'order_payment_changed', 'Poslata potvrda uplate za '.$order->order_number, sprintf('Poslata je potvrda uplate od %s RSD.', number_format((float) $payment->amount_rsd, 2, ',', '.')), ['payment_number' => $payment->payment_number, 'status' => $payment->status]);

        return $payment;
    }

    /** @param array<string,mixed> $data */
    public function record(Order $order, array $data, User $actor): OrderPayment
    {
        $payment = DB::transaction(function () use ($order, $data, $actor): OrderPayment {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $this->assertOrderOpen($locked, 'amount_rsd');
            if ($locked->status === 'cancelled') {
                throw ValidationException::withMessages(['amount_rsd' => 'Uplata se ne može evidentirati za otkazanu porudžbinu.']);
            }
            $type = (string) ($data['entry_type'] ?? 'payment');
            // MOBILE_V0_9_DIRECT_SALE_DEFERRED_PAYMENT_RECEIVABLES_BATCH5B_V2
            if ($this->isDeferredDirectSale($locked)) {
                if ($type !== 'payment') {
                    throw ValidationException::withMessages([
                        'entry_type' => 'Refundacija direktne prodaje vodi se isključivo kroz odobrenu postprodajnu radnju.',
                    ]);
                }
                $remaining = round(max(0, (float) $locked->subtotal_rsd - (float) $locked->paid_total_rsd), 2);
                $amount = round((float) ($data['amount_rsd'] ?? 0), 2);
                if ($amount <= 0) {
                    throw ValidationException::withMessages(['amount_rsd' => 'Iznos uplate mora biti veći od nule.']);
                }
                if ($remaining <= 0.004) {
                    throw ValidationException::withMessages(['amount_rsd' => 'Odloženo plaćanje je već u celosti izmireno.']);
                }
                if ($amount > $remaining + 0.004) {
                    throw ValidationException::withMessages([
                        'amount_rsd' => 'Uplata ne može biti veća od preostalog duga '.number_format($remaining, 2, ',', '.').' RSD.',
                    ]);
                }
            }
            $payment = OrderPayment::query()->create([
                'order_id' => $locked->id,
                'payment_number' => $this->numbers->next($type === 'refund' ? 'refund' : 'payment', (int) now()->format('Y')),
                'entry_type' => $type,
                'status' => 'verified',
                'amount_rsd' => (float) $data['amount_rsd'],
                'payment_method' => (string) $data['payment_method'],
                'paid_at' => $data['paid_at'],
                'reference' => trim((string) ($data['reference'] ?? '')) ?: null,
                'note' => trim((string) ($data['note'] ?? '')) ?: null,
                'submitted_by' => $actor->id,
                'verified_by' => $actor->id,
                'verified_at' => now(),
            ]);
            $this->recalculateLocked($locked);
            $this->audit->log('order.payment_recorded', 'Evidentirana '.($type === 'refund' ? 'refundacija' : 'uplata').' '.$payment->payment_number, $payment, after: ['amount_rsd' => $payment->amount_rsd, 'entry_type' => $type], user: $actor);
            return $payment;
        }, 5);
        $payment->loadMissing('order.user', 'order.supplier');
        if ($payment->order instanceof Order) {
            $entry = $payment->entry_type === 'refund' ? 'Refundacija' : 'Uplata';
            $this->emails->orderChanged($payment->order, 'order_payment_changed', $entry.' za '.$payment->order->order_number, sprintf('%s %s RSD je evidentirana.', $entry, number_format((float) $payment->amount_rsd, 2, ',', '.')), ['payment_number' => $payment->payment_number, 'status' => $payment->status, 'entry_type' => $payment->entry_type]);
            $this->syncReceivable($payment->order);
        }
        return $payment;
    }

    public function recordAfterSalesRefundLocked(AfterSalesAction $action, Order $order, User $actor): OrderPayment
    {
        if ((int) $action->case?->order_id !== (int) $order->id) {
            throw ValidationException::withMessages(['amount_rsd' => 'Postprodajna radnja ne pripada izabranoj porudžbini.']);
        }
        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages(['amount_rsd' => 'Refundacija se ne može evidentirati za otkazanu porudžbinu.']);
        }

        $existing = OrderPayment::query()->where('after_sales_action_id', $action->id)->first();
        if ($existing instanceof OrderPayment) {
            return $existing;
        }

        $amount = round((float) $action->amount_rsd, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount_rsd' => 'Iznos refundacije mora biti veći od nule.']);
        }

        $netPaid = (float) OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')
            ->selectRaw("COALESCE(SUM(CASE WHEN entry_type='refund' THEN -amount_rsd ELSE amount_rsd END),0) AS net_total")
            ->value('net_total');
        if ($amount > round(max(0, $netPaid), 2) + 0.004) {
            throw ValidationException::withMessages([
                'amount_rsd' => 'Refundacija ne može biti veća od trenutno neto uplaćenog iznosa '.number_format(max(0, $netPaid), 2, ',', '.').' RSD.',
            ]);
        }

        $payment = OrderPayment::query()->create([
            'order_id' => $order->id,
            'after_sales_action_id' => $action->id,
            'payment_number' => $this->numbers->next('refund', (int) now()->format('Y')),
            'entry_type' => 'refund',
            'status' => 'verified',
            'amount_rsd' => $amount,
            'payment_method' => 'after_sales_refund',
            'paid_at' => now(),
            'reference' => $action->reference,
            'note' => 'Refundacija po postprodajnoj radnji '.$action->action_number.'. '.trim((string) $action->public_note),
            'submitted_by' => $actor->id,
            'verified_by' => $actor->id,
            'verified_at' => now(),
        ]);
        $this->recalculateLocked($order);
        $this->audit->log('after_sales.refund_recorded', 'Evidentirana refundacija '.$payment->payment_number.' po radnji '.$action->action_number, $payment, after: ['amount_rsd' => $payment->amount_rsd, 'after_sales_action_id' => $action->id], user: $actor);

        return $payment;
    }

    public function verify(OrderPayment $payment, User $actor): OrderPayment
    {
        $verified = DB::transaction(function () use ($payment, $actor): OrderPayment {
            /** @var OrderPayment $locked */
            $locked = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
            /** @var Order $order */
            $order = Order::query()->lockForUpdate()->findOrFail($locked->order_id);
            $this->assertOrderOpen($order, 'payment');
            if ($locked->status === 'verified') return $locked;
            if ($locked->status !== 'submitted') throw ValidationException::withMessages(['payment' => 'Samo poslata potvrda može biti verifikovana.']);
            $locked->update(['status' => 'verified', 'verified_by' => $actor->id, 'verified_at' => now(), 'rejected_by' => null, 'rejected_at' => null, 'rejection_reason' => null]);
            $this->recalculateLocked($order);
            $this->audit->log('order.payment_verified', 'Verifikovana uplata '.$locked->payment_number, $locked, before: ['status' => 'submitted'], after: ['status' => 'verified'], user: $actor);
            return $locked;
        }, 5);

        $this->notifyCustomer($verified, 'Uplata je potvrđena', 'Potvrda uplate '.$verified->payment_number.' je verifikovana.', 'success');
        $verified->loadMissing('order');
        if ($verified->order instanceof Order) $this->syncReceivable($verified->order);
        return $verified;
    }

    public function reject(OrderPayment $payment, string $reason, User $actor): OrderPayment
    {
        $rejected = DB::transaction(function () use ($payment, $reason, $actor): OrderPayment {
            /** @var OrderPayment $locked */
            $locked = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
            /** @var Order $order */
            $order = Order::query()->lockForUpdate()->findOrFail($locked->order_id);
            $this->assertOrderOpen($order, 'reason');
            if ($locked->status !== 'submitted') throw ValidationException::withMessages(['reason' => 'Samo poslata potvrda može biti odbijena.']);
            $locked->update(['status' => 'rejected', 'rejected_by' => $actor->id, 'rejected_at' => now(), 'rejection_reason' => trim($reason)]);
            $this->audit->log('order.payment_rejected', 'Odbijena potvrda uplate '.$locked->payment_number, $locked, before: ['status' => 'submitted'], after: ['status' => 'rejected', 'reason' => trim($reason)], user: $actor);
            return $locked;
        }, 5);
        $this->notifyCustomer($rejected, 'Potvrda uplate nije prihvaćena', trim($reason), 'danger');
        return $rejected;
    }

    public function void(OrderPayment $payment, User $actor): OrderPayment
    {
        $voided = DB::transaction(function () use ($payment, $actor): OrderPayment {
            /** @var OrderPayment $locked */
            $locked = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
            /** @var Order $order */
            $order = Order::query()->lockForUpdate()->findOrFail($locked->order_id);
            $this->assertOrderOpen($order, 'payment');
            if ($locked->status === 'voided') return $locked;
            if ($locked->status !== 'verified') throw ValidationException::withMessages(['payment' => 'Samo verifikovana stavka može biti stornirana.']);
            $locked->update(['status' => 'voided', 'voided_by' => $actor->id, 'voided_at' => now()]);
            $this->recalculateLocked($order);
            $this->audit->log('order.payment_voided', 'Stornirana uplata '.$locked->payment_number, $locked, before: ['status' => 'verified'], after: ['status' => 'voided'], user: $actor);
            return $locked;
        }, 5);
        $this->notifyCustomer($voided, 'Uplata je stornirana', 'Finansijska stavka '.$voided->payment_number.' je stornirana.', 'warning');
        $voided->loadMissing('order');
        if ($voided->order instanceof Order) $this->syncReceivable($voided->order);
        return $voided;
    }

    public function proof(OrderPayment $payment): ?string
    {
        if (!$payment->proof_path || !Storage::disk('local')->exists($payment->proof_path)) return null;
        return Storage::disk('local')->path($payment->proof_path);
    }

    public function recalculate(Order $order): Order
    {
        $fresh = DB::transaction(function () use ($order): Order {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $this->recalculateLocked($locked);
            return $locked->refresh();
        }, 5);
        $this->syncReceivable($fresh);
        return $fresh;
    }


    public function recalculateForAmendmentLocked(Order $order): void
    {
        $this->recalculateLocked($order);
    }

    private function syncReceivable(Order $order): void
    {
        try {
            $this->receivables->syncForOrder($order->fresh() ?? $order);
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('Receivable synchronization failed after payment change.', [
                'order_id' => $order->id,
                'exception' => $exception,
            ]);
        }
    }

    private function recalculateLocked(Order $order): void
    {
        $net = (float) OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')
            ->selectRaw("COALESCE(SUM(CASE WHEN entry_type='refund' THEN -amount_rsd ELSE amount_rsd END),0) AS net_total")
            ->value('net_total');
        $refundTotal = (float) OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')->where('entry_type', 'refund')->sum('amount_rsd');
        $total = (float) $order->subtotal_rsd;
        $state = 'unpaid';
        if ($order->status === 'cancelled') $state = 'cancelled';
        elseif ($net < -0.004 || ($refundTotal > 0.004 && $net <= 0.004)) $state = 'refunded';
        elseif ($net <= 0.004) $state = 'unpaid';
        elseif ($net + 0.004 < $total) $state = 'partial';
        elseif ($net > $total + 0.004) $state = 'overpaid';
        else $state = 'paid';

        $order->update([
            'paid_total_rsd' => round($net, 2),
            'payment_state' => $state,
            'payment_status' => $state === 'cancelled' ? 'cancelled' : ($state === 'refunded' ? 'refunded' : (in_array($state, ['paid', 'overpaid'], true) ? 'paid' : 'pending')),
            'payment_verified_at' => in_array($state, ['paid', 'overpaid'], true) ? ($order->payment_verified_at ?: now()) : null,
        ]);
        $this->ips->persist($order->fresh());
    }

    private function isDeferredDirectSale(Order $order): bool
    {
        return (string) ($order->sales_channel ?? 'order') === 'direct_sale'
            && (string) $order->payment_method === 'deferred_payment';
    }

    private function assertOrderOpen(Order $order, string $field): void
    {
        $deferredDirectSale = $this->isDeferredDirectSale($order);
        if ((string) ($order->sales_channel ?? 'order') === 'direct_sale' && !$deferredDirectSale) {
            throw ValidationException::withMessages([
                $field => 'Direktna prodaja ima zaključan finansijski ledger. Refundacija se evidentira isključivo kroz odobrenu postprodajnu radnju.',
            ]);
        }

        // Deferred Direct Sale is physically completed/delivered while its receivable stays open.
        // Payment record/verify/void lifecycle is therefore allowed; generic refunds remain blocked above.
        if ($order->completed_at !== null && !$deferredDirectSale) {
            throw ValidationException::withMessages([
                $field => 'Porudžbina je kompletirana i finansijske stavke su zaključane.',
            ]);
        }
    }

    private function notifyCustomer(OrderPayment $payment, string $title, string $message, string $severity): void
    {
        $payment->loadMissing('order.user');
        if ($payment->order?->user instanceof User) {
            $this->notifications->order($payment->order->user, 'order.payment_'.$payment->status, $title, $message, $payment->order, ['icon' => 'wallet', 'severity' => $severity]);
        }
        if ($payment->order instanceof Order) {
            $this->emails->orderChanged($payment->order, 'order_payment_changed', $title.' · '.$payment->order->order_number, $message, ['payment_number' => $payment->payment_number, 'status' => $payment->status, 'entry_type' => $payment->entry_type]);
        }
    }
}
