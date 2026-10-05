<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Throwable;

final class OrderFinancialReconciliationService
{
    public function __construct(
        private readonly IpsPaymentPayloadService $ips,
        private readonly ReceivablesService $receivables,
    ) {}

    public function reconcile(Order $order): void
    {
        $fresh = $order->fresh() ?? $order;
        try {
            $this->ips->refreshCache($fresh);
        } catch (Throwable $exception) {
            Log::warning('IPS reconciliation failed after canonical financial mutation.', [
                'order_id' => (int) $fresh->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            $this->receivables->syncForOrder($fresh->fresh() ?? $fresh);
        } catch (Throwable $exception) {
            Log::warning('Receivable reconciliation failed after canonical financial mutation.', [
                'order_id' => (int) $fresh->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
