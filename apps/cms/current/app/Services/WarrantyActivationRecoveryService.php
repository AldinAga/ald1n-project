<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\ProductWarranty;
use App\Models\WarrantyActivationRecoveryAttempt;
use App\Models\WarrantyIssuance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/**
 * B946. Never reissue a certificate or backfill unrelated historical orders.
 * READ-ONLY by default. No scheduler is registered in this source-only batch.
 *
 * Executable mode requires an explicit independent release gate, schema and
 * WARRANTY_ACTIVATION_RECOVERY_ENABLED=true. Process at most 50 orders/run.
 */
final class WarrantyActivationRecoveryService
{
    public function __construct(private readonly WarrantyService $warranties) {}

    /** @return array{mode:string,candidates:int,recovered:int,failed:int,deferred:int,manual_review:int,manual_review_total:int,skipped:int} */
    public function run(int $limit = 25, bool $execute = false): array
    {
        $limit = max(1, min(50, $limit));
        if ($execute && !(bool) config('warranty_recovery.enabled', false)) {
            throw new RuntimeException('B946_EXECUTION_NOT_APPROVED');
        }
        if (!Schema::hasTable('warranty_issuances')) {
            throw new RuntimeException('B946_ISSUANCE_LEDGER_MISSING');
        }
        if ($execute && !Schema::hasTable('warranty_activation_recovery_attempts')) {
            throw new RuntimeException('B946_RECOVERY_LEDGER_MISSING');
        }

        // Only existing numbered pending certificates for physically delivered,
        // completed orders. Neither a status change nor a newly issued number.
        $query = WarrantyIssuance::query()
            ->where('status', 'issued_pending_delivery')
            ->whereNull('activated_warranty_id')
            ->whereHas('order', static fn ($orders) => $orders
                ->whereNotNull('completed_at')
                ->where('status', '!=', 'cancelled')
                ->whereNull('cancelled_at')
                ->whereHas('delivery', static fn ($deliveries) => $deliveries->whereNotNull('delivered_at')));

        if ($execute) {
            // Exclude terminal/manual and future-due attempts BEFORE LIMIT.
            // Otherwise a few older blocked orders could starve all later ones.
            $query->whereNotIn('order_id', static function ($attempts): void {
                $attempts->select('order_id')->from('warranty_activation_recovery_attempts')
                    ->where(static function ($states): void {
                        $states->whereIn('status', ['manual_review', 'resolved'])
                            ->orWhere(static function ($retry): void {
                                $retry->where('status', 'retrying')
                                    ->where('next_attempt_at', '>', now());
                            });
                    });
            });
        }
        $ids = $query->select('order_id')->distinct()->orderBy('order_id')
            ->limit($limit)->pluck('order_id');

        $result = [
            'mode' => $execute ? 'gated_execute' : 'read_only',
            'candidates' => $ids->count(),
            'recovered' => 0, 'failed' => 0, 'deferred' => 0,
            'manual_review' => 0, 'manual_review_total' => Schema::hasTable('warranty_activation_recovery_attempts')
                ? WarrantyActivationRecoveryAttempt::query()->where('status', 'manual_review')->count() : 0,
            'skipped' => 0,
        ];
        if (!$execute) return $result;

        foreach ($ids as $id) {
            try {
                $state = $this->attemptOrder((int) $id);
                $result[$state]++;
            } catch (Throwable $exception) {
                // The failed activation transaction has rolled back; record a
                // durable bounded retry in an independent short transaction.
                $this->recordFailure((int) $id, $exception);
                $result['failed']++;
            }
        }

        $result['manual_review_total'] = WarrantyActivationRecoveryAttempt::query()
            ->where('status', 'manual_review')->count();
        return $result;
    }

    /** @return 'recovered'|'manual_review'|'deferred'|'skipped' */
    private function attemptOrder(int $orderId): string
    {
        return DB::transaction(function () use ($orderId): string {
            $order = Order::query()->lockForUpdate()->find($orderId);
            if (!$order instanceof Order
                || $order->completed_at === null
                || $order->cancelled_at !== null
                || (string) $order->status === 'cancelled'
                || !$order->delivery?->delivered_at) {
                return 'skipped';
            }

            $record = WarrantyActivationRecoveryAttempt::query()
                ->where('order_id', $orderId)->lockForUpdate()->first();

            if ($record?->status === 'manual_review') return 'manual_review';
            if ($record?->next_attempt_at?->isFuture()) return 'deferred';

            $pending = WarrantyIssuance::query()->where('order_id', $orderId)
                ->where('status', 'issued_pending_delivery')
                ->whereNull('activated_warranty_id')
                ->lockForUpdate()->get();
            if ($pending->isEmpty()) return 'skipped';

            // Fail closed for refunds: a disputed transaction needs a person.
            if (OrderPayment::query()->where('order_id', $orderId)
                ->where('entry_type', 'refund')->where('status', 'verified')->exists()) {
                return $this->manualReview($orderId, $record, 'VERIFIED_REFUND_PRESENT');
            }

            $items = $order->items;
            if ($items->isEmpty()) return $this->manualReview($orderId, $record, 'NO_ORDER_ITEMS');

            foreach ($items as $item) {
                $existing = ProductWarranty::query()->where('order_item_id', $item->id)->first();
                $issuance = $pending->firstWhere('order_item_id', $item->id);

                // Never revive voided warranties or issue an additional number
                // for an item whose positive-payment issuance was not recorded.
                if ($existing !== null) {
                    if ($existing->status !== 'active' || $issuance !== null) {
                        return $this->manualReview($orderId, $record, 'WARRANTY_STATE_INCONSISTENT');
                    }
                    continue;
                }
                if (!$issuance instanceof WarrantyIssuance || trim((string) $issuance->warranty_number) === '') {
                    return $this->manualReview($orderId, $record, 'ISSUANCE_MISSING_OR_INVALID');
                }
                $snapshot = (array) $issuance->snapshot_json;
                if ((int) $issuance->order_id !== (int) $order->id
                    || (int) ($snapshot['product_id'] ?? 0) !== (int) $item->product_id
                    // Order ownership must match immutable first-payment owner.
                    || (int) ($snapshot['user_id'] ?? 0) !== (int) ($order->user_id ?? 0)
                    || ((int) ($snapshot['duration_months'] ?? 0) === 0
                        && (int) ($snapshot['duration_days'] ?? 0) === 0)) {
                    return $this->manualReview($orderId, $record, 'ISSUANCE_LINEAGE_INVALID');
                }
                $payment = OrderPayment::query()->find($issuance->first_verified_payment_id);
                if (!$payment instanceof OrderPayment
                    || (int) $payment->order_id !== $orderId
                    || $payment->entry_type !== 'payment'
                    || $payment->status !== 'verified'
                    || $payment->verified_at === null
                    || (float) $payment->amount_rsd <= 0) {
                    return $this->manualReview($orderId, $record, 'VERIFIED_PAYMENT_MISSING');
                }
            }

            // The existing service retains the immutable number, snapshot and
            // actual delivery-based start date. No new issuance is created.
            $this->warranties->ensureForOrder($order->loadMissing('user'));

            foreach ($pending as $issuance) {
                $fresh = $issuance->fresh();
                $active = $fresh?->activatedWarranty;
                if ($fresh?->status !== 'activated'
                    || !$active instanceof ProductWarranty
                    || $active->status !== 'active'
                    || (int) $active->order_item_id !== (int) $issuance->order_item_id
                    || (string) $active->warranty_number !== (string) $issuance->warranty_number) {
                    throw new RuntimeException('B946_INCOMPLETE_ACTIVATION');
                }
            }

            $this->saveResolved($orderId, $record);
            return 'recovered';
        }, 3);
    }

    private function manualReview(int $orderId, ?WarrantyActivationRecoveryAttempt $record, string $code): string
    {
        $record ??= new WarrantyActivationRecoveryAttempt(['order_id' => $orderId]);
        $record->forceFill([
            'status' => 'manual_review',
            'next_attempt_at' => null,
            'last_error_code' => $code,
        ])->save();

        Log::warning('B946 warranty activation requires manual review', ['order_id' => $orderId, 'code' => $code]);
        return 'manual_review';
    }

    private function saveResolved(int $orderId, ?WarrantyActivationRecoveryAttempt $record): void
    {
        $record ??= new WarrantyActivationRecoveryAttempt(['order_id' => $orderId]);
        $record->forceFill([
            'status' => 'resolved',
            'next_attempt_at' => null,
            'last_error_code' => null,
            'last_attempt_at' => now(),
            'resolved_at' => now(),
        ])->save();
    }

    private function recordFailure(int $orderId, Throwable $exception): void
    {
        DB::transaction(function () use ($orderId, $exception): void {
            $order = Order::query()->lockForUpdate()->find($orderId);
            if (!$order instanceof Order) return;
            $record = WarrantyActivationRecoveryAttempt::query()
                ->where('order_id', $orderId)->lockForUpdate()->first();
            if ($record?->status === 'manual_review' || $record?->status === 'resolved') return;

            $max = max(1, min(10, (int) config('warranty_recovery.max_attempts', 5)));
            $count = min(10, (int) ($record?->attempt_count ?? 0) + 1);
            $manual = $count >= $max;
            $minutes = min(1440, 5 * (3 ** max(0, $count - 1)));

            $record ??= new WarrantyActivationRecoveryAttempt(['order_id' => $orderId]);
            $record->forceFill([
                'status' => $manual ? 'manual_review' : 'retrying',
                'attempt_count' => $count,
                'next_attempt_at' => $manual ? null : now()->addMinutes($minutes),
                // Never persist exception messages, tokens or customer data.
                'last_error_code' => class_basename($exception),
                'last_attempt_at' => now(),
            ])->save();
            Log::warning('B946 warranty activation retry recorded', [
                'order_id' => $orderId,
                'attempt' => $count,
                'manual_review' => $manual,
                'exception_type' => class_basename($exception),
            ]);
        }, 3);
    }
}
