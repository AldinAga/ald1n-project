<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\DB;

final class OrderFinancialStateService
{
    private const TOLERANCE = 0.004;

    /** @return array{paid_total_rsd:float,payment_state:string,payment_status:string,payment_verified_at:mixed} */
    public function derive(Order $order): array
    {
        $payments = OrderPayment::query()
            ->where('order_id', (int) $order->getKey())
            ->where('status', 'verified');

        $net = (float) (clone $payments)
            ->selectRaw("COALESCE(SUM(CASE WHEN entry_type='refund' THEN -amount_rsd ELSE amount_rsd END),0) AS net_total")
            ->value('net_total');
        $refundGross = (float) (clone $payments)->where('entry_type', 'refund')->sum('amount_rsd');
        $total = (float) $order->subtotal_rsd;

        if ((string) $order->status === 'cancelled') {
            $state = 'cancelled';
        } elseif ($net < -self::TOLERANCE || ($refundGross > self::TOLERANCE && $net <= self::TOLERANCE)) {
            $state = 'refunded';
        } elseif ($net <= self::TOLERANCE) {
            $state = 'unpaid';
        } elseif ($net + self::TOLERANCE < $total) {
            $state = 'partial';
        } elseif ($net > $total + self::TOLERANCE) {
            $state = 'overpaid';
        } else {
            $state = 'paid';
        }

        $status = match ($state) {
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'paid', 'overpaid' => 'paid',
            default => 'pending',
        };

        $verifiedAt = null;
        if (in_array($state, ['paid', 'overpaid'], true)) {
            $verifiedAt = $order->payment_verified_at;
            if ($verifiedAt === null) {
                $verifiedAt = (clone $payments)->whereNotNull('verified_at')->max('verified_at') ?: now();
            }
        }

        return [
            'paid_total_rsd' => round($net, 2),
            'payment_state' => $state,
            'payment_status' => $status,
            'payment_verified_at' => $verifiedAt,
        ];
    }

    public function projectLocked(Order $order): Order
    {
        $order->forceFill($this->derive($order));
        if ($order->isDirty(['paid_total_rsd', 'payment_state', 'payment_status', 'payment_verified_at'])) {
            $order->save();
        }
        return $order;
    }

    public function project(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail((int) $order->getKey());
            $this->projectLocked($locked);
            return $locked->refresh();
        }, 5);
    }
}
