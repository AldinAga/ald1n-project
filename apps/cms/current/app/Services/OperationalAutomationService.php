<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AutomationRun;
use App\Models\AfterSalesAction;
use App\Models\AfterSalesCase;
use App\Models\FieldWorkOrder;
use App\Models\OperationalAlert;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductWarranty;
use App\Models\ServicePart;
use App\Models\ServicePartPurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class OperationalAutomationService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly OperationalNotificationService $notifications,
        private readonly ReceivablesService $receivables,
    ) {}

    public function run(?User $actor = null, bool $force = false, bool $digest = false): AutomationRun
    {
        $task = $digest ? 'operational_daily_digest' : 'operational_alert_scan';
        $run = AutomationRun::query()->create([
            'task' => $task,
            'status' => 'running',
            'started_at' => now(),
            'triggered_by' => $actor?->id,
        ]);

        if (!$force && $this->settings->get('automation_enabled', '1') !== '1') {
            $run->update([
                'status' => 'skipped',
                'finished_at' => now(),
                'summary_json' => ['reason' => 'automation_disabled'],
            ]);
            return $run->fresh();
        }

        $lock = Cache::lock('ald1n:automation:'.$task, 900);
        if (!$lock->get()) {
            $run->update([
                'status' => 'skipped',
                'finished_at' => now(),
                'summary_json' => ['reason' => 'already_running'],
            ]);
            return $run->fresh();
        }

        try {
            if ($digest) {
                $summary = $this->sendDigest();
            } else {
                $summary = $this->scan($force);
            }

            $run->update([
                'status' => 'success',
                'finished_at' => now(),
                'examined_count' => $summary['examined'],
                'action_count' => $summary['alerts'],
                'notification_count' => $summary['notifications'],
                'summary_json' => $summary,
            ]);
            $this->settings->putMany([
                'automation_last_success_at' => now()->toDateTimeString(),
                'automation_last_error' => '',
            ], $actor?->id);
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_text' => mb_substr($exception->getMessage(), 0, 4000),
                'summary_json' => ['exception' => $exception::class],
            ]);
            try {
                $this->settings->putMany([
                    'automation_last_error' => mb_substr($exception->getMessage(), 0, 1000),
                ], $actor?->id);
                Log::error('Operational automation failed.', [
                    'task' => $task,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            } catch (Throwable) {
                // The original automation exception must remain the primary failure.
            }
            throw $exception;
        } finally {
            $lock->release();
        }

        return $run->fresh();
    }

    /** @return array{examined:int,alerts:int,notifications:int,resolved:int,types:array<string,int>} */
    private function scan(bool $force): array
    {
        $seen = [];
        $examined = 0;
        $alerts = 0;
        $notifications = 0;
        $types = [];

        $unacceptedHours = $this->intSetting('automation_unaccepted_order_hours', 4, 1, 168);
        $reminderHours = $this->intSetting('automation_alert_reminder_hours', 24, 1, 720);

        $unaccepted = Order::query()
            ->with(['supplier.role'])
            ->where('source_system', 'laravel')
            ->whereNull('accepted_at')
            ->whereIn('status', ['new', 'processing'])
            ->where('created_at', '<=', now()->subHours($unacceptedHours))
            ->limit(500)
            ->get();
        $examined += $unaccepted->count();
        foreach ($unaccepted as $order) {
            [$created, $sent] = $this->recordOrderAlert(
                $order,
                'order_unaccepted',
                'warning',
                'Porudžbina čeka preuzimanje',
                sprintf('%s čeka preuzimanje duže od %d h.', $order->order_number, $unacceptedHours),
                $force,
                $reminderHours,
                $seen,
            );
            $alerts += $created;
            $notifications += $sent;
            $types['order_unaccepted'] = ($types['order_unaccepted'] ?? 0) + 1;
        }

        if ($this->settings->get('automation_deadline_alerts_enabled', '1') === '1') {
            $processing = Order::query()
                ->with(['supplier.role'])
                ->whereIn('status', ['new', 'processing'])
                ->whereNotNull('expected_processing_at')
                ->where('expected_processing_at', '<', now())
                ->limit(500)
                ->get();
            $shipping = Order::query()
                ->with(['supplier.role'])
                ->whereNotIn('status', ['shipped', 'cancelled'])
                ->whereNotNull('expected_shipping_at')
                ->where('expected_shipping_at', '<', now())
                ->limit(500)
                ->get();
            $examined += $processing->count() + $shipping->count();

            foreach ($processing as $order) {
                [$created, $sent] = $this->recordOrderAlert($order, 'processing_overdue', 'danger', 'Rok obrade je istekao', $order->order_number.' nije obrađena u očekivanom roku.', $force, $reminderHours, $seen);
                $alerts += $created; $notifications += $sent; $types['processing_overdue'] = ($types['processing_overdue'] ?? 0) + 1;
            }
            foreach ($shipping as $order) {
                [$created, $sent] = $this->recordOrderAlert($order, 'shipping_overdue', 'danger', 'Rok slanja je istekao', $order->order_number.' nije poslata u očekivanom roku.', $force, $reminderHours, $seen);
                $alerts += $created; $notifications += $sent; $types['shipping_overdue'] = ($types['shipping_overdue'] ?? 0) + 1;
            }
        }

        if ($this->settings->get('automation_overdue_payment_enabled', '1') === '1') {
            $overduePayments = Order::query()
                ->with(['supplier.role'])
                ->whereNotIn('payment_state', ['paid', 'overpaid', 'cancelled', 'refunded'])
                ->whereNotNull('payment_due_at')
                ->where('payment_due_at', '<', now())
                ->where('status', '!=', 'cancelled')
                ->limit(500)
                ->get();
            $examined += $overduePayments->count();
            foreach ($overduePayments as $order) {
                $remaining = max(0, (float) $order->subtotal_rsd - (float) $order->paid_total_rsd);
                [$created, $sent] = $this->recordOrderAlert(
                    $order,
                    'payment_overdue',
                    'danger',
                    'Dospelo neplaćeno potraživanje',
                    sprintf('%s ima dospelo dugovanje od %s RSD.', $order->order_number, number_format($remaining, 2, ',', '.')),
                    $force,
                    $reminderHours,
                    $seen,
                );
                $alerts += $created; $notifications += $sent; $types['payment_overdue'] = ($types['payment_overdue'] ?? 0) + 1;
            }
        }

        if ($this->settings->get('automation_low_stock_enabled', '1') === '1') {
            $lowStock = Product::query()
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->where('variants_enabled', false)
                ->where('low_stock_threshold', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->limit(1000)
                ->get();
            $examined += $lowStock->count();
            foreach ($lowStock as $product) {
                $key = 'low_stock:'.$product->id;
                $seen[] = $key;
                $alert = $this->upsertAlert($key, [
                    'type' => 'low_stock',
                    'severity' => (int) $product->stock_quantity <= 0 ? 'danger' : 'warning',
                    'product_id' => $product->id,
                    'title' => (int) $product->stock_quantity <= 0 ? 'Artikal je bez lagera' : 'Nizak lager',
                    'message' => sprintf('%s (%s): stanje %d, prag %d.', $product->name, $product->sku, $product->stock_quantity, $product->low_stock_threshold),
                    'action_url' => route('admin.inventory.index', ['q' => $product->sku]),
                    'metadata_json' => ['stock' => $product->stock_quantity, 'threshold' => $product->low_stock_threshold],
                ]);
                $alerts++;
                if ($this->shouldNotify($alert, $force, $reminderHours)) {
                    $recipients = $this->administrators();
                    foreach ($recipients as $recipient) {
                        $this->notifications->send($recipient, [
                            'event' => 'automation.low_stock',
                            'title' => $alert->title,
                            'message' => $alert->message,
                            'url' => $alert->action_url,
                            'action_label' => 'Otvori lager',
                            'icon' => 'boxes',
                            'severity' => $alert->severity,
                            'alert_id' => $alert->id,
                        ]);
                        $notifications++;
                    }
                    $alert->update(['last_notified_at' => now()]);
                }
                $types['low_stock'] = ($types['low_stock'] ?? 0) + 1;
            }

            if (Schema::hasTable('product_variants')) {
                $lowVariants = ProductVariant::query()
                    ->with('product:id,sku,name')
                    ->where('status', 'active')
                    ->whereNull('deleted_at')
                    ->where('low_stock_threshold', '>', 0)
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                    ->limit(1000)
                    ->get();
                $examined += $lowVariants->count();
                foreach ($lowVariants as $variant) {
                    $key = 'low_stock_variant:'.$variant->id;
                    $seen[] = $key;
                    $alert = $this->upsertAlert($key, [
                        'type' => 'low_stock_variant',
                        'severity' => (int) $variant->stock_quantity <= 0 ? 'danger' : 'warning',
                        'product_id' => $variant->product_id,
                        'title' => (int) $variant->stock_quantity <= 0 ? 'Varijanta je bez lagera' : 'Nizak lager varijante',
                        'message' => sprintf('%s — %s (%s): stanje %d, prag %d.', $variant->product?->name ?? 'Artikal', $variant->name, $variant->sku, $variant->stock_quantity, $variant->low_stock_threshold),
                        'action_url' => route('admin.products.variants.index', $variant->product_id).'#variant-'.$variant->id,
                        'metadata_json' => ['product_variant_id' => $variant->id, 'stock' => $variant->stock_quantity, 'threshold' => $variant->low_stock_threshold],
                    ]);
                    $alerts++;
                    if ($this->shouldNotify($alert, $force, $reminderHours)) {
                        foreach ($this->administrators() as $recipient) {
                            $this->notifications->send($recipient, [
                                'event' => 'automation.low_stock_variant',
                                'title' => $alert->title,
                                'message' => $alert->message,
                                'url' => $alert->action_url,
                                'action_label' => 'Otvori varijantu',
                                'icon' => 'boxes',
                                'severity' => $alert->severity,
                                'alert_id' => $alert->id,
                            ]);
                            $notifications++;
                        }
                        $alert->update(['last_notified_at' => now()]);
                    }
                    $types['low_stock_variant'] = ($types['low_stock_variant'] ?? 0) + 1;
                }
            }
        }

        if (Schema::hasTable('after_sales_cases')) {
            $overdueCases = AfterSalesCase::query()
                ->with(['assignee.role', 'order'])
                ->whereNotIn('status', ['resolved', 'rejected', 'closed'])
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->limit(500)
                ->get();
            $examined += $overdueCases->count();

            foreach ($overdueCases as $case) {
                $key = 'after_sales_overdue:'.$case->id;
                $seen[] = $key;
                $alert = $this->upsertAlert($key, [
                    'type' => 'after_sales_overdue',
                    'severity' => $case->priority === 'urgent' ? 'danger' : 'warning',
                    'order_id' => $case->order_id,
                    'user_id' => $case->assigned_to,
                    'title' => 'Probijen rok postprodajnog slučaja',
                    'message' => sprintf('%s (%s) nije obrađen u zadatom roku.', $case->case_number, $case->subject),
                    'action_url' => route('admin.after-sales.show', $case),
                    'metadata_json' => ['case_id' => $case->id, 'case_number' => $case->case_number, 'priority' => $case->priority],
                ]);
                $alerts++;
                if ($this->shouldNotify($alert, $force, $reminderHours)) {
                    $recipients = $this->administrators()->filter(static function (User $user) use ($case): bool {
                        return $user->hasRole('superadmin') || (int) $user->id === (int) $case->assigned_to;
                    });
                    foreach ($recipients as $recipient) {
                        $this->notifications->send($recipient, [
                            'event' => 'automation.after_sales_overdue',
                            'title' => $alert->title,
                            'message' => $alert->message,
                            'url' => $alert->action_url,
                            'action_label' => 'Otvori slučaj',
                            'icon' => 'alert',
                            'severity' => $alert->severity,
                            'alert_id' => $alert->id,
                        ]);
                        $notifications++;
                    }
                    $alert->update(['last_notified_at' => now()]);
                }
                $types['after_sales_overdue'] = ($types['after_sales_overdue'] ?? 0) + 1;
            }
        }

        if (Schema::hasTable('after_sales_actions')) {
            $overdueActions = AfterSalesAction::query()
                ->with(['assignee.role', 'case.order'])
                ->whereIn('status', ['planned', 'in_progress'])
                ->where(function ($query): void {
                    $query->whereNotIn('action_type', ['service_visit', 'replacement_dispatch', 'return_receipt'])
                        ->orWhereDoesntHave('workOrder');
                })
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->limit(500)
                ->get();
            $examined += $overdueActions->count();

            foreach ($overdueActions as $action) {
                $key = 'after_sales_action_overdue:'.$action->id;
                $seen[] = $key;
                $alert = $this->upsertAlert($key, [
                    'type' => 'after_sales_action_overdue',
                    'severity' => 'danger',
                    'order_id' => $action->case?->order_id,
                    'user_id' => $action->assigned_to,
                    'title' => 'Probijen rok izvršne postprodajne radnje',
                    'message' => sprintf('%s nije izvršena u zadatom roku.', $action->action_number),
                    'action_url' => route('admin.after-sales.show', $action->case),
                    'metadata_json' => ['case_id' => $action->after_sales_case_id, 'action_id' => $action->id, 'action_number' => $action->action_number],
                ]);
                $alerts++;
                if ($this->shouldNotify($alert, $force, $reminderHours)) {
                    $recipients = $this->administrators()->filter(static function (User $user) use ($action): bool {
                        return $user->hasRole('superadmin') || (int) $user->id === (int) $action->assigned_to;
                    });
                    foreach ($recipients as $recipient) {
                        $this->notifications->send($recipient, [
                            'event' => 'automation.after_sales_action_overdue',
                            'title' => $alert->title,
                            'message' => $alert->message,
                            'url' => $alert->action_url,
                            'action_label' => 'Otvori radnju',
                            'icon' => 'cog',
                            'severity' => 'danger',
                            'alert_id' => $alert->id,
                        ]);
                        $notifications++;
                    }
                    $alert->update(['last_notified_at' => now()]);
                }
                $types['after_sales_action_overdue'] = ($types['after_sales_action_overdue'] ?? 0) + 1;
            }
        }


        if (Schema::hasTable('field_work_orders')) {
            $overdueWorkOrders = FieldWorkOrder::query()
                ->with(['action.assignee.role', 'action.case.order'])
                ->whereIn('status', ['planned', 'en_route', 'on_site'])
                ->whereNotNull('planned_end_at')
                ->where('planned_end_at', '<', now())
                ->limit(500)
                ->get();
            $examined += $overdueWorkOrders->count();

            foreach ($overdueWorkOrders as $workOrder) {
                $key = 'field_work_order_overdue:'.$workOrder->id;
                $seen[] = $key;
                $alert = $this->upsertAlert($key, [
                    'type' => 'field_work_order_overdue',
                    'severity' => 'danger',
                    'order_id' => $workOrder->action?->case?->order_id,
                    'user_id' => $workOrder->action?->assigned_to,
                    'title' => 'Probijen rok terenskog radnog naloga',
                    'message' => sprintf('%s nije završen do planiranog termina.', $workOrder->work_order_number),
                    'action_url' => route('admin.field-operations.show', $workOrder),
                    'metadata_json' => ['work_order_id' => $workOrder->id, 'work_order_number' => $workOrder->work_order_number],
                ]);
                $alerts++;
                if ($this->shouldNotify($alert, $force, $reminderHours)) {
                    $recipients = $this->administrators()->filter(static function (User $user) use ($workOrder): bool {
                        return $user->hasRole('superadmin') || (int) $user->id === (int) $workOrder->action?->assigned_to;
                    });
                    foreach ($recipients as $recipient) {
                        $this->notifications->send($recipient, [
                            'event' => 'automation.field_work_order_overdue',
                            'title' => $alert->title,
                            'message' => $alert->message,
                            'url' => $alert->action_url,
                            'action_label' => 'Otvori radni nalog',
                            'icon' => 'truck',
                            'severity' => 'danger',
                            'alert_id' => $alert->id,
                        ]);
                        $notifications++;
                    }
                    $alert->update(['last_notified_at' => now()]);
                }
                $types['field_work_order_overdue'] = ($types['field_work_order_overdue'] ?? 0) + 1;
            }

            $unscheduledWorkOrders = FieldWorkOrder::query()
                ->with(['action.assignee.role', 'action.case.order'])
                ->where('status', 'planned')
                ->whereNull('planned_start_at')
                ->where('created_at', '<', now()->subDay())
                ->limit(500)
                ->get();
            $examined += $unscheduledWorkOrders->count();

            foreach ($unscheduledWorkOrders as $workOrder) {
                $key = 'field_work_order_unscheduled:'.$workOrder->id;
                $seen[] = $key;
                $alert = $this->upsertAlert($key, [
                    'type' => 'field_work_order_unscheduled',
                    'severity' => 'warning',
                    'order_id' => $workOrder->action?->case?->order_id,
                    'user_id' => $workOrder->action?->assigned_to,
                    'title' => 'Terenski radni nalog nije raspoređen',
                    'message' => sprintf('%s nema dodeljenu ekipu i termin duže od 24 sata.', $workOrder->work_order_number),
                    'action_url' => route('admin.field-operations.show', $workOrder),
                    'metadata_json' => ['work_order_id' => $workOrder->id, 'work_order_number' => $workOrder->work_order_number],
                ]);
                $alerts++;
                if ($this->shouldNotify($alert, $force, $reminderHours)) {
                    $recipients = $this->administrators()->filter(static function (User $user) use ($workOrder): bool {
                        return $user->hasRole('superadmin') || (int) $user->id === (int) $workOrder->action?->assigned_to;
                    });
                    foreach ($recipients as $recipient) {
                        $this->notifications->send($recipient, [
                            'event' => 'automation.field_work_order_unscheduled',
                            'title' => $alert->title,
                            'message' => $alert->message,
                            'url' => $alert->action_url,
                            'action_label' => 'Rasporedi radni nalog',
                            'icon' => 'calendar',
                            'severity' => 'warning',
                            'alert_id' => $alert->id,
                        ]);
                        $notifications++;
                    }
                    $alert->update(['last_notified_at' => now()]);
                }
                $types['field_work_order_unscheduled'] = ($types['field_work_order_unscheduled'] ?? 0) + 1;
            }
        }

        if (Schema::hasTable('service_parts')) {
            $lowServiceParts = ServicePart::query()
                ->where('is_active', true)
                ->whereRaw('(stock_quantity - reserved_quantity) <= minimum_quantity')
                ->limit(1000)
                ->get();
            $examined += $lowServiceParts->count();
            foreach ($lowServiceParts as $part) {
                $key = 'service_part_low:'.$part->id;
                $seen[] = $key;
                $available = max(0, (float) $part->stock_quantity - (float) $part->reserved_quantity);
                $alert = $this->upsertAlert($key, [
                    'type' => 'service_part_low',
                    'severity' => $available <= 0 ? 'danger' : 'warning',
                    'product_id' => null,
                    'title' => $available <= 0 ? 'Servisni deo nije raspoloživ' : 'Nizak servisni lager',
                    'message' => sprintf('%s (%s): stanje %s, rezervisano %s, minimum %s.', $part->name, $part->sku, $part->stock_quantity, $part->reserved_quantity, $part->minimum_quantity),
                    'action_url' => route('admin.service-parts.index', ['filter' => 'low']),
                    'metadata_json' => ['service_part_id' => $part->id, 'sku' => $part->sku, 'available' => $available],
                ]);
                $alerts++;
                if ($this->shouldNotify($alert, $force, $reminderHours)) {
                    foreach ($this->administrators()->filter(static fn (User $user): bool => $user->hasPermission('service_parts.manage') || $user->hasPermission('service_parts.procurement')) as $recipient) {
                        $this->notifications->send($recipient, [
                            'event' => 'automation.service_part_low', 'title' => $alert->title, 'message' => $alert->message,
                            'url' => $alert->action_url, 'action_label' => 'Otvori servisni lager', 'icon' => 'boxes',
                            'severity' => $alert->severity, 'alert_id' => $alert->id,
                        ]);
                        $notifications++;
                    }
                    $alert->update(['last_notified_at' => now()]);
                }
                $types['service_part_low'] = ($types['service_part_low'] ?? 0) + 1;
            }
        }

        if (Schema::hasTable('service_part_purchase_requests')) {
            $latePurchases = ServicePartPurchaseRequest::query()
                ->with('supplier')
                ->whereIn('status', ['submitted', 'ordered'])
                ->whereNotNull('expected_at')
                ->whereDate('expected_at', '<', today())
                ->limit(500)
                ->get();
            $examined += $latePurchases->count();
            foreach ($latePurchases as $purchase) {
                $key = 'service_part_purchase_overdue:'.$purchase->id;
                $seen[] = $key;
                $alert = $this->upsertAlert($key, [
                    'type' => 'service_part_purchase_overdue', 'severity' => 'warning',
                    'title' => 'Kasni nabavka servisnih delova',
                    'message' => sprintf('%s od dobavljača %s nije primljena do očekivanog datuma.', $purchase->request_number, $purchase->supplier?->name ?? 'bez izabranog dobavljača'),
                    'action_url' => route('admin.service-part-purchases.show', $purchase),
                    'metadata_json' => ['purchase_request_id' => $purchase->id, 'request_number' => $purchase->request_number],
                ]);
                $alerts++;
                if ($this->shouldNotify($alert, $force, $reminderHours)) {
                    foreach ($this->administrators()->filter(static fn (User $user): bool => $user->hasPermission('service_parts.procurement')) as $recipient) {
                        $this->notifications->send($recipient, [
                            'event' => 'automation.service_part_purchase_overdue', 'title' => $alert->title, 'message' => $alert->message,
                            'url' => $alert->action_url, 'action_label' => 'Otvori nabavku', 'icon' => 'receipt',
                            'severity' => 'warning', 'alert_id' => $alert->id,
                        ]);
                        $notifications++;
                    }
                    $alert->update(['last_notified_at' => now()]);
                }
                $types['service_part_purchase_overdue'] = ($types['service_part_purchase_overdue'] ?? 0) + 1;
            }
        }

        if ($this->settings->get('automation_warranty_alerts_enabled', '1') === '1' && Schema::hasTable('product_warranties')) {
            $expiryDays = $this->intSetting('warranty_expiry_notice_days', 30, 1, 365);
            $maintenanceDays = $this->intSetting('warranty_maintenance_notice_days', 7, 1, 90);

            $expiring = ProductWarranty::query()->with(['user', 'order'])
                ->where('status', 'active')
                ->whereBetween('expires_at', [today(), today()->addDays($expiryDays)])
                ->limit(1000)->get();
            $examined += $expiring->count();
            foreach ($expiring as $warranty) {
                $key = 'warranty_expiring:'.$warranty->id;
                $seen[] = $key;
                $message = sprintf('%s za %s ističe %s.', $warranty->warranty_number, $warranty->product_name_snapshot, $warranty->expires_at?->format('d.m.Y'));
                $alert = $this->upsertAlert($key, [
                    'type' => 'warranty_expiring', 'severity' => 'warning', 'order_id' => $warranty->order_id,
                    'user_id' => $warranty->user_id, 'title' => 'Garancija uskoro ističe', 'message' => $message,
                    'action_url' => route('admin.warranties.show', $warranty),
                    'metadata_json' => ['warranty_id' => $warranty->id, 'warranty_number' => $warranty->warranty_number],
                ]);
                $alerts++;
                if ($this->shouldNotify($alert, $force, $reminderHours)) {
                    if ($warranty->user instanceof User) {
                        $this->notifications->send($warranty->user, [
                            'event' => 'warranty.expiring', 'title' => $alert->title, 'message' => $message,
                            'url' => route('warranties.show', $warranty), 'action_label' => 'Otvori garanciju',
                            'icon' => 'shield', 'severity' => 'warning', 'alert_id' => $alert->id,
                        ]);
                        $notifications++;
                    }
                    foreach ($this->administrators()->filter(static fn (User $user): bool => $user->hasPermission('warranties.manage')) as $recipient) {
                        $this->notifications->send($recipient, [
                            'event' => 'warranty.expiring', 'title' => $alert->title, 'message' => $message,
                            'url' => $alert->action_url, 'action_label' => 'Otvori garanciju', 'icon' => 'shield',
                            'severity' => 'warning', 'alert_id' => $alert->id,
                        ]);
                        $notifications++;
                    }
                    $alert->update(['last_notified_at' => now()]);
                }
                $types['warranty_expiring'] = ($types['warranty_expiring'] ?? 0) + 1;
            }

            $maintenance = ProductWarranty::query()->with(['user', 'order'])
                ->where('status', 'active')->whereNotNull('next_maintenance_at')
                ->whereDate('next_maintenance_at', '<=', today()->addDays($maintenanceDays))
                ->limit(1000)->get();
            $examined += $maintenance->count();
            foreach ($maintenance as $warranty) {
                $overdue = $warranty->next_maintenance_at?->isBefore(today()) === true;
                $key = 'warranty_maintenance_due:'.$warranty->id;
                $seen[] = $key;
                $message = sprintf('Preventivno održavanje za %s (%s) je %s.', $warranty->product_name_snapshot, $warranty->warranty_number, $overdue ? 'prekoračeno' : 'planirano '.$warranty->next_maintenance_at?->format('d.m.Y'));
                $alert = $this->upsertAlert($key, [
                    'type' => 'warranty_maintenance_due', 'severity' => $overdue ? 'danger' : 'warning',
                    'order_id' => $warranty->order_id, 'user_id' => $warranty->user_id,
                    'title' => $overdue ? 'Preventivno održavanje kasni' : 'Približava se preventivno održavanje',
                    'message' => $message, 'action_url' => route('admin.warranties.show', $warranty),
                    'metadata_json' => ['warranty_id' => $warranty->id, 'next_maintenance_at' => $warranty->next_maintenance_at?->toDateString()],
                ]);
                $alerts++;
                if ($this->shouldNotify($alert, $force, $reminderHours)) {
                    if ($warranty->user instanceof User) {
                        $this->notifications->send($warranty->user, [
                            'event' => 'warranty.maintenance_due', 'title' => $alert->title, 'message' => $message,
                            'url' => route('warranties.show', $warranty), 'action_label' => 'Otvori garanciju',
                            'icon' => 'cog', 'severity' => $alert->severity, 'alert_id' => $alert->id,
                        ]);
                        $notifications++;
                    }
                    foreach ($this->administrators()->filter(static fn (User $user): bool => $user->hasPermission('warranties.manage')) as $recipient) {
                        $this->notifications->send($recipient, [
                            'event' => 'warranty.maintenance_due', 'title' => $alert->title, 'message' => $message,
                            'url' => $alert->action_url, 'action_label' => 'Otvori garanciju', 'icon' => 'cog',
                            'severity' => $alert->severity, 'alert_id' => $alert->id,
                        ]);
                        $notifications++;
                    }
                    $alert->update(['last_notified_at' => now()]);
                }
                $types['warranty_maintenance_due'] = ($types['warranty_maintenance_due'] ?? 0) + 1;
            }
        }

        if ($this->receivables->ready()) {
            $collection = $this->receivables->runAutomation($force);
            $examined += $collection['examined'];
            $notifications += $collection['reminders'];
            $types['receivable_cases_created'] = $collection['cases_created'];
            $types['receivable_reminders'] = $collection['reminders'];
            $types['receivable_cases_closed'] = $collection['closed'];
        }

        $resolved = OperationalAlert::query()
            ->where('status', 'open')
            ->when($seen !== [], static fn ($query) => $query->whereNotIn('alert_key', $seen))
            ->update(['status' => 'resolved', 'resolved_at' => now()]);

        return compact('examined', 'alerts', 'notifications', 'resolved', 'types');
    }

    /** @param list<string> $seen @return array{0:int,1:int} */
    private function recordOrderAlert(Order $order, string $type, string $severity, string $title, string $message, bool $force, int $reminderHours, array &$seen): array
    {
        $key = $type.':'.$order->id;
        $seen[] = $key;
        $alert = $this->upsertAlert($key, [
            'type' => $type,
            'severity' => $severity,
            'order_id' => $order->id,
            'user_id' => $order->supplier_user_id,
            'title' => $title,
            'message' => $message,
            'action_url' => route('admin.orders.show', $order),
            'metadata_json' => ['order_number' => $order->order_number],
        ]);

        $sent = 0;
        if ($this->shouldNotify($alert, $force, $reminderHours)) {
            $recipients = $this->orderRecipients($order);
            foreach ($recipients as $recipient) {
                $this->notifications->send($recipient, [
                    'event' => 'automation.'.$type,
                    'title' => $title,
                    'message' => $message,
                    'url' => $alert->action_url,
                    'action_label' => 'Otvori porudžbinu',
                    'icon' => $type === 'payment_overdue' ? 'money' : 'orders',
                    'severity' => $severity,
                    'alert_id' => $alert->id,
                    'order_id' => $order->id,
                ]);
                $sent++;
            }
            $alert->update(['last_notified_at' => now()]);
        }

        return [1, $sent];
    }

    /** @param array<string,mixed> $attributes */
    private function upsertAlert(string $key, array $attributes): OperationalAlert
    {
        $alert = OperationalAlert::query()->firstOrNew(['alert_key' => $key]);
        $isNew = !$alert->exists;
        $alert->fill($attributes + [
            'status' => 'open',
            'last_detected_at' => now(),
            'resolved_at' => null,
        ]);
        if ($isNew) {
            $alert->first_detected_at = now();
        }
        $alert->save();
        return $alert;
    }

    private function shouldNotify(OperationalAlert $alert, bool $force, int $reminderHours): bool
    {
        return $force
            || $alert->last_notified_at === null
            || $alert->last_notified_at->lte(now()->subHours($reminderHours));
    }

    /** @return Collection<int,User> */
    private function administrators(): Collection
    {
        return User::query()
            ->with('role')
            ->where('status', 'active')
            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))
            ->get();
    }

    /** @return Collection<int,User> */
    private function orderRecipients(Order $order): Collection
    {
        $recipients = $this->administrators()->filter(static function (User $user) use ($order): bool {
            return $user->hasRole('superadmin') || (int) $user->id === (int) $order->supplier_user_id;
        });
        if ($order->supplier instanceof User && !$recipients->contains('id', $order->supplier->id)) {
            $recipients->push($order->supplier);
        }
        return $recipients->unique('id')->values();
    }

    /** @return array{examined:int,alerts:int,notifications:int,resolved:int,types:array<string,int>} */
    private function sendDigest(): array
    {
        if ($this->settings->get('automation_daily_digest_enabled', '1') !== '1') {
            return ['examined' => 0, 'alerts' => 0, 'notifications' => 0, 'resolved' => 0, 'types' => []];
        }

        $open = OperationalAlert::query()->where('status', 'open')->get();
        $counts = $open->groupBy('type')->map->count()->all();
        $notifications = 0;
        if ($open->isNotEmpty()) {
            foreach ($this->administrators() as $recipient) {
                $preference = $recipient->notificationPreference()->first();
                if ($preference !== null && !$preference->daily_digest) {
                    continue;
                }
                $this->notifications->send($recipient, [
                    'event' => 'automation.daily_digest',
                    'title' => 'Dnevni operativni pregled',
                    'message' => sprintf('Otvoreno je %d upozorenja: %d kritičnih i %d upozorenja.', $open->count(), $open->where('severity', 'danger')->count(), $open->where('severity', 'warning')->count()),
                    'url' => route('admin.settings.automation.index'),
                    'action_label' => 'Otvori automatizaciju',
                    'icon' => 'dashboard',
                    'severity' => $open->where('severity', 'danger')->isNotEmpty() ? 'danger' : 'warning',
                ]);
                $notifications++;
            }
        }

        return [
            'examined' => $open->count(),
            'alerts' => $open->count(),
            'notifications' => $notifications,
            'resolved' => 0,
            'types' => $counts,
        ];
    }

    private function intSetting(string $key, int $default, int $min, int $max): int
    {
        $value = (int) $this->settings->get($key, (string) $default);
        return max($min, min($max, $value));
    }
}
