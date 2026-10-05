<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\IpsPaymentPayloadService;
use App\Services\OrderFinancialReconciliationService;
use App\Services\OrderFinancialStateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class FinancialStateDoctorCommand extends Command
{
    protected $signature = 'app:financial-state-doctor {--order-id= : Ograniči proveru na jednu porudžbinu} {--apply : Primeni samo bezbednu derived-state korekciju}';
    protected $description = 'Dry-run-first provera canonical order financial state projekcije, IPS cache-a i ledger integriteta.';

    public function handle(OrderFinancialStateService $financial, IpsPaymentPayloadService $ips, OrderFinancialReconciliationService $reconciliation): int
    {
        $query = Order::query()->where('source_system', 'laravel')->orderBy('id');
        $orderId = trim((string) $this->option('order-id'));
        if ($orderId !== '') $query->whereKey((int) $orderId);
        $orders = $query->get();

        $projectionMismatches = [];
        $paidTotalMismatches = [];
        $directSaleMismatches = [];
        $ipsMismatches = [];
        $derivedRepairIds = [];

        foreach ($orders as $order) {
            $expected = $financial->derive($order);
            $paidMismatch = abs((float) $order->paid_total_rsd - (float) $expected['paid_total_rsd']) > 0.004;
            $stateMismatch = (string) $order->payment_state !== (string) $expected['payment_state'];
            $statusMismatch = (string) $order->payment_status !== (string) $expected['payment_status'];
            $expectedVerified = $expected['payment_verified_at'];
            $actualVerified = $order->payment_verified_at;
            $verifiedMismatch = ($expectedVerified === null) !== ($actualVerified === null);
            if ($paidMismatch || $stateMismatch || $statusMismatch || $verifiedMismatch) {
                $projectionMismatches[] = (int) $order->id;
                if ($paidMismatch) $paidTotalMismatches[] = (int) $order->id;
                else $derivedRepairIds[] = (int) $order->id;
            }

            if ((string) $order->sales_channel === 'direct_sale') {
                $net = (float) OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')
                    ->selectRaw("COALESCE(SUM(CASE WHEN entry_type='refund' THEN -amount_rsd ELSE amount_rsd END),0) AS net_total")
                    ->value('net_total');
                $total = (float) $order->subtotal_rsd;
                if ((string) $order->payment_method === 'deferred_payment') {
                    if ($net < -0.004 || $net > $total + 0.004) $directSaleMismatches[] = (int) $order->id;
                } elseif (abs($net - $total) > 0.004) {
                    $directSaleMismatches[] = (int) $order->id;
                }
            }

            if (Schema::hasTable('order_ips_qr')) {
                try {
                    $expectedPayload = $ips->forDisplay($order);
                    $cached = DB::table('order_ips_qr')->where('order_id', $order->id)->first();
                    if ($expectedPayload === null) {
                        if ($cached !== null) $ipsMismatches[] = (int) $order->id;
                    } elseif ($cached === null || (string) ($cached->payload_text ?? '') !== $expectedPayload) {
                        $ipsMismatches[] = (int) $order->id;
                    }
                } catch (Throwable) {
                    $ipsMismatches[] = (int) $order->id;
                }
            }
        }

        $submitted = OrderPayment::query()->whereIn('order_id', $orders->pluck('id'))->where('status', 'submitted')->count();
        $verifiedRows = OrderPayment::query()->whereIn('order_id', $orders->pluck('id'))->where('status', 'verified')->count();

        $this->line('FINANCIAL_DOCTOR_MODE='.($this->option('apply') ? 'APPLY' : 'DRY_RUN'));
        $this->line('ORDERS='.$orders->count());
        $this->line('VERIFIED_PAYMENT_ROWS='.$verifiedRows);
        $this->line('SUBMITTED_PAYMENTS='.$submitted);
        $this->line('CANONICAL_PROJECTION_MISMATCHES='.count(array_unique($projectionMismatches)));
        $this->line('PAID_TOTAL_LEDGER_MISMATCHES='.count(array_unique($paidTotalMismatches)));
        $this->line('DIRECT_SALE_LEDGER_MISMATCHES='.count(array_unique($directSaleMismatches)));
        $this->line('IPS_CACHE_MISMATCHES='.count(array_unique($ipsMismatches)));
        $this->line('SAFE_DERIVED_REPAIR_IDS='.implode(',', array_values(array_unique($derivedRepairIds))));

        if (!$this->option('apply')) return self::SUCCESS;

        if ($paidTotalMismatches !== [] || $directSaleMismatches !== []) {
            $this->error('UNSAFE_APPLY_BLOCKED=YES');
            return self::FAILURE;
        }

        $repairIds = array_values(array_unique(array_merge($derivedRepairIds, $ipsMismatches)));
        foreach ($repairIds as $id) {
            $order = Order::query()->findOrFail($id);
            if (in_array($id, $derivedRepairIds, true)) $financial->project($order);
            $reconciliation->reconcile($order);
        }

        $this->line('APPLIED_DERIVED_REPAIRS='.count(array_unique($derivedRepairIds)));
        $this->line('APPLIED_RECONCILIATIONS='.count($repairIds));
        return self::SUCCESS;
    }
}
