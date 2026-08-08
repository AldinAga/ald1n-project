<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AfterSalesAction;
use App\Models\AfterSalesCase;
use App\Models\AfterSalesCaseItem;
use App\Models\AfterSalesStatusHistory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\FieldWorkOrder;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AfterSalesActionService
{
    public function __construct(
        private readonly AfterSalesAccessService $access,
        private readonly OrderPaymentService $payments,
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
        private readonly FieldWorkOrderPlanner $fieldWorkOrders,
        private readonly ProductVariantService $variants,
    ) {}

    /** @param array<string,mixed> $data */
    public function create(AfterSalesCase $case, User $actor, array $data): AfterSalesAction
    {
        $this->assertExecutor($actor);
        $this->access->authorizeManage($case->loadMissing('order'), $actor);

        $action = DB::transaction(function () use ($case, $actor, $data): AfterSalesAction {
            /** @var AfterSalesCase $lockedCase */
            $lockedCase = AfterSalesCase::query()->with(['order', 'items'])->lockForUpdate()->findOrFail($case->id);
            $this->access->authorizeManage($lockedCase, $actor);
            $type = (string) $data['action_type'];
            $this->assertCaseAllowsAction($lockedCase, $type);
            $this->assertAdditionalPermission($type, $actor);

            $selected = collect((array) ($data['items'] ?? []))
                ->filter(static fn (mixed $row): bool => is_array($row) && filter_var($row['selected'] ?? false, FILTER_VALIDATE_BOOL));
            if ($selected->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Izaberite najmanje jednu stavku za izvršnu radnju.']);
            }

            $caseItems = AfterSalesCaseItem::query()
                ->where('after_sales_case_id', $lockedCase->id)
                ->whereIn('id', $selected->keys()->map(static fn (mixed $id): int => (int) $id))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            if ($caseItems->count() !== $selected->count()) {
                throw ValidationException::withMessages(['items' => 'Jedna ili više stavki ne pripadaju ovom postprodajnom slučaju.']);
            }

            $inventoryHandling = $this->inventoryHandling($type, (string) ($data['inventory_handling'] ?? 'none'));
            $amount = filled($data['amount_rsd'] ?? null) ? round((float) $data['amount_rsd'], 2) : null;
            if ($type === 'refund' && ($amount === null || $amount <= 0)) {
                throw ValidationException::withMessages(['amount_rsd' => 'Za refundaciju unesite iznos veći od nule.']);
            }
            if ($type === 'service_visit' && blank($data['scheduled_at'] ?? null)) {
                throw ValidationException::withMessages(['scheduled_at' => 'Za servisnu posetu unesite planirani termin.']);
            }

            $assignee = $this->validatedAssignee($data['assigned_to'] ?? $lockedCase->assigned_to);
            $action = AfterSalesAction::query()->create([
                'action_number' => 'PENDING-'.Str::uuid(),
                'after_sales_case_id' => $lockedCase->id,
                'action_type' => $type,
                'status' => 'planned',
                'inventory_handling' => $inventoryHandling,
                'assigned_to' => $assignee,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'due_at' => $data['due_at'] ?? ($data['scheduled_at'] ?? $lockedCase->due_at),
                'amount_rsd' => $amount,
                'reference' => filled($data['reference'] ?? null) ? trim((string) $data['reference']) : null,
                'public_note' => filled($data['public_note'] ?? null) ? trim((string) $data['public_note']) : null,
                'internal_note' => filled($data['internal_note'] ?? null) ? trim((string) $data['internal_note']) : null,
            ]);
            $action->forceFill([
                'action_number' => 'PRA-'.now()->format('Ymd').'-'.str_pad((string) $action->id, 6, '0', STR_PAD_LEFT),
            ])->save();

            foreach ($selected as $caseItemId => $row) {
                /** @var AfterSalesCaseItem $caseItem */
                $caseItem = $caseItems->get((int) $caseItemId);
                $quantity = max(1, (int) ($row['quantity'] ?? 1));
                if ($quantity > max(1, (int) $caseItem->quantity)) {
                    throw ValidationException::withMessages([
                        'items.'.$caseItem->id.'.quantity' => 'Količina za '.$caseItem->product_name_snapshot.' ne može biti veća od prijavljene količine '.$caseItem->quantity.'.',
                    ]);
                }
                $disposition = $this->disposition($type, (string) ($row['disposition'] ?? 'none'));
                $action->items()->create([
                    'after_sales_case_item_id' => $caseItem->id,
                    'product_id' => $caseItem->product_id,
                    'product_variant_id' => $caseItem->product_variant_id,
                    'sku_snapshot' => $caseItem->sku_snapshot,
                    'product_name_snapshot' => $caseItem->product_name_snapshot,
                    'quantity' => $quantity,
                    'disposition' => $disposition,
                    'stock_effect' => $this->stockEffect($type, $inventoryHandling, $disposition),
                ]);
            }

            $action->setRelation('case', $lockedCase);
            $this->fieldWorkOrders->ensureForAction($action, $actor, $data);

            $this->audit->log(
                'after_sales.action_created',
                'Planirana postprodajna radnja '.$action->action_number,
                $action,
                null,
                $action->toArray(),
                ['case_number' => $lockedCase->case_number, 'item_count' => $action->items()->count()],
                $actor,
            );

            return $action->fresh(['case.order', 'items', 'assignee', 'creator', 'workOrder.team']);
        }, 5);

        $this->notify($action, $actor, 'Planirana postprodajna radnja', 'Radnja '.$action->action_number.' je planirana.', 'warning');
        return $action;
    }

    public function start(AfterSalesCase $case, AfterSalesAction $action, User $actor, bool $fromWorkOrder = false): AfterSalesAction
    {
        $this->assertExecutor($actor);

        $started = DB::transaction(function () use ($case, $action, $actor, $fromWorkOrder): AfterSalesAction {
            /** @var AfterSalesAction $locked */
            $locked = AfterSalesAction::query()->with(['case.order', 'workOrder'])->lockForUpdate()->findOrFail($action->id);
            $this->assertActionBelongsToCase($case, $locked);
            $this->access->authorizeManage($locked->case, $actor);
            $this->assertAdditionalPermission($locked->action_type, $actor);
            if ($locked->status === 'in_progress') return $locked;
            if ($locked->status !== 'planned') {
                throw ValidationException::withMessages(['action' => 'Samo planirana radnja može biti pokrenuta.']);
            }
            if (!$fromWorkOrder && in_array($locked->action_type, FieldWorkOrderPlanner::PHYSICAL_ACTIONS, true)
                && $locked->workOrder instanceof FieldWorkOrder) {
                throw ValidationException::withMessages(['action' => 'Fizičku radnju pokrenite kroz pripadajući terenski radni nalog.']);
            }

            $before = $locked->toArray();
            $locked->forceFill([
                'status' => 'in_progress',
                'started_at' => now(),
                'started_by' => $actor->id,
                'updated_by' => $actor->id,
            ])->save();

            if ($locked->case->status === 'approved') {
                $locked->case->forceFill(['status' => 'in_service'])->save();
                AfterSalesStatusHistory::query()->create([
                    'after_sales_case_id' => $locked->case->id,
                    'from_status' => 'approved',
                    'to_status' => 'in_service',
                    'actor_id' => $actor->id,
                    'note' => 'Pokrenuta je izvršna radnja '.$locked->action_number.'.',
                    'metadata_json' => ['action_id' => $locked->id],
                    'created_at' => now(),
                ]);
            }

            $this->audit->log('after_sales.action_started', 'Pokrenuta postprodajna radnja '.$locked->action_number, $locked, $before, $locked->toArray(), null, $actor);
            return $locked->fresh(['case.order', 'items', 'assignee', 'workOrder.team']);
        }, 5);

        $this->notify($started, $actor, 'Postprodajna radnja je pokrenuta', 'Radnja '.$started->action_number.' je u toku.', 'info');
        return $started;
    }

    /** @param array<string,mixed> $data */
    public function complete(AfterSalesCase $case, AfterSalesAction $action, User $actor, array $data, bool $fromWorkOrder = false): AfterSalesAction
    {
        $this->assertExecutor($actor);

        $completed = DB::transaction(function () use ($case, $action, $actor, $data, $fromWorkOrder): AfterSalesAction {
            /** @var AfterSalesAction $locked */
            $locked = AfterSalesAction::query()
                ->with(['case.order', 'items.caseItem', 'workOrder'])
                ->lockForUpdate()
                ->findOrFail($action->id);
            $this->assertActionBelongsToCase($case, $locked);
            $this->access->authorizeManage($locked->case, $actor);
            $this->assertAdditionalPermission($locked->action_type, $actor);
            if ($locked->status === 'completed') return $locked;
            if (!$fromWorkOrder && in_array($locked->action_type, FieldWorkOrderPlanner::PHYSICAL_ACTIONS, true)
                && $locked->workOrder instanceof FieldWorkOrder && $locked->workOrder->status !== 'completed') {
                throw ValidationException::withMessages(['action' => 'Fizičku radnju završite kroz pripadajući radni nalog kako bi bili evidentirani dolazak, troškovi i dokaz izvršenja.']);
            }
            if (!in_array($locked->status, ['planned', 'in_progress'], true)) {
                throw ValidationException::withMessages(['action' => 'Otkazana radnja ne može biti izvršena.']);
            }

            /** @var Order $order */
            $order = Order::query()->lockForUpdate()->findOrFail($locked->case->order_id);
            if ($locked->action_type === 'refund') {
                $locked->setRelation('case', $locked->case->setRelation('order', $order));
                $payment = $this->payments->recordAfterSalesRefundLocked($locked, $order, $actor);
                $locked->payment_id = $payment->id;
            } elseif ($locked->inventory_handling === 'automatic') {
                $this->applyInventoryEffects($locked, $order, $actor);
            }

            $before = $locked->toArray();
            $locked->forceFill([
                'status' => 'completed',
                'reference' => filled($data['reference'] ?? null) ? trim((string) $data['reference']) : $locked->reference,
                'completion_note' => filled($data['completion_note'] ?? null) ? trim((string) $data['completion_note']) : null,
                'completed_at' => now(),
                'completed_by' => $actor->id,
                'updated_by' => $actor->id,
                'cancelled_at' => null,
                'cancelled_by' => null,
                'cancellation_reason' => null,
            ])->save();

            $this->audit->log('after_sales.action_completed', 'Izvršena postprodajna radnja '.$locked->action_number, $locked, $before, $locked->toArray(), ['case_number' => $locked->case->case_number], $actor);
            return $locked->fresh(['case.order', 'items.stockMovement', 'assignee', 'payment', 'workOrder.team']);
        }, 5);

        $this->notify($completed, $actor, 'Postprodajna radnja je izvršena', 'Radnja '.$completed->action_number.' je završena.', 'success');
        return $completed;
    }

    public function cancel(AfterSalesCase $case, AfterSalesAction $action, User $actor, string $reason): AfterSalesAction
    {
        $this->assertExecutor($actor);

        $cancelled = DB::transaction(function () use ($case, $action, $actor, $reason): AfterSalesAction {
            /** @var AfterSalesAction $locked */
            $locked = AfterSalesAction::query()->with(['case.order', 'workOrder'])->lockForUpdate()->findOrFail($action->id);
            $this->assertActionBelongsToCase($case, $locked);
            $this->access->authorizeManage($locked->case, $actor);
            $this->assertAdditionalPermission($locked->action_type, $actor);
            if ($locked->status === 'cancelled') return $locked;
            if ($locked->status === 'completed') {
                throw ValidationException::withMessages(['cancellation_reason' => 'Izvršena radnja se ne može otkazati. Korekciju evidentirajte novom radnjom.']);
            }

            $before = $locked->toArray();
            $locked->forceFill([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancellation_reason' => trim($reason),
                'updated_by' => $actor->id,
            ])->save();
            if ($locked->workOrder instanceof FieldWorkOrder && $locked->workOrder->status !== 'completed') {
                $locked->workOrder->forceFill([
                    'status' => 'cancelled', 'cancelled_at' => now(), 'status_by' => $actor->id,
                    'cancellation_reason' => trim($reason), 'updated_by' => $actor->id,
                ])->save();
            }
            $this->audit->log('after_sales.action_cancelled', 'Otkazana postprodajna radnja '.$locked->action_number, $locked, $before, $locked->toArray(), null, $actor);
            return $locked->fresh(['case.order', 'items', 'assignee', 'workOrder.team']);
        }, 5);

        $this->notify($cancelled, $actor, 'Postprodajna radnja je otkazana', 'Radnja '.$cancelled->action_number.' je otkazana.', 'warning');
        return $cancelled;
    }

    private function applyInventoryEffects(AfterSalesAction $action, Order $order, User $actor): void
    {
        if (!in_array($action->action_type, ['replacement_dispatch', 'return_receipt'], true)) return;

        $effectItems = $action->items->filter(static fn ($item): bool => in_array($item->stock_effect, ['increase', 'decrease'], true));
        if ($effectItems->isEmpty()) return;
        if ($effectItems->contains(static fn ($item): bool => $item->product_id === null)) {
            throw ValidationException::withMessages(['items' => 'Automatska promena lagera nije moguća jer jedna stavka nema lokalno povezan proizvod. Izaberite eksternu obradu lagera.']);
        }

        $productIds = $effectItems->pluck('product_id')->map(static fn (mixed $id): int => (int) $id)->unique()->sort()->values();
        /** @var Collection<int,Product> $products */
        $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages(['items' => 'Jedan od lokalnih proizvoda više nije dostupan za promenu lagera.']);
        }

        $variantIds = $effectItems->pluck('product_variant_id')->filter()->map(static fn (mixed $id): int => (int) $id)->unique()->sort()->values();
        $variants = ProductVariant::query()->whereIn('id', $variantIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        if ($variants->count() !== $variantIds->count()) {
            throw ValidationException::withMessages(['items' => 'Jedna od varijanti više nije dostupna za promenu lagera.']);
        }
        foreach ($effectItems as $item) {
            /** @var Product $product */
            $product = $products->get((int) $item->product_id);
            if ((bool) $product->variants_enabled && $item->product_variant_id === null) {
                throw ValidationException::withMessages(['items' => 'Za artikal '.$product->sku.' mora biti poznata konkretna varijanta. Izaberite eksternu obradu lagera.']);
            }
            if ($item->product_variant_id !== null) {
                $variant = $variants->get((int) $item->product_variant_id);
                if (!$variant || (int) $variant->product_id !== (int) $product->id) {
                    throw ValidationException::withMessages(['items' => 'Varijanta ne pripada izabranom proizvodu.']);
                }
            }
        }

        $required = $effectItems->where('stock_effect', 'decrease')->groupBy(static fn ($item): string => $item->product_variant_id ? 'v:'.$item->product_variant_id : 'p:'.$item->product_id)->map(static fn ($rows): int => (int) $rows->sum('quantity'));
        foreach ($required as $key => $quantity) {
            if (str_starts_with((string) $key, 'v:')) {
                /** @var ProductVariant $variant */
                $variant = $variants->get((int) substr((string) $key, 2));
                if ((int) $variant->stock_quantity < $quantity) {
                    throw ValidationException::withMessages(['items' => 'Nema dovoljno lagera za varijantu '.$variant->sku.'. Dostupno: '.$variant->stock_quantity.', potrebno: '.$quantity.'.']);
                }
            } else {
                /** @var Product $product */
                $product = $products->get((int) substr((string) $key, 2));
                if ((int) $product->stock_quantity < $quantity) {
                    throw ValidationException::withMessages(['items' => 'Nema dovoljno lagera za zamenski artikal '.$product->sku.'. Dostupno: '.$product->stock_quantity.', potrebno: '.$quantity.'.']);
                }
            }
        }

        $affectedParents = [];
        foreach ($effectItems->sortBy(static fn ($item): string => str_pad((string) $item->product_id, 20, '0', STR_PAD_LEFT).'-'.str_pad((string) ($item->product_variant_id ?? 0), 20, '0', STR_PAD_LEFT).'-'.str_pad((string) $item->id, 20, '0', STR_PAD_LEFT)) as $item) {
            $eventKey = 'after-sales-action:'.$action->id.':item:'.$item->id;
            $existing = StockMovement::query()->where('event_key', $eventKey)->first();
            if ($existing instanceof StockMovement) {
                $item->forceFill(['stock_movement_id' => $existing->id])->save();
                continue;
            }

            /** @var Product $product */
            $product = $products->get((int) $item->product_id);
            /** @var ProductVariant|null $variant */
            $variant = $item->product_variant_id !== null ? $variants->get((int) $item->product_variant_id) : null;
            $stockOwner = $variant ?? $product;
            $before = (int) $stockOwner->stock_quantity;
            $change = $item->stock_effect === 'decrease' ? -(int) $item->quantity : (int) $item->quantity;
            $after = $before + $change;
            if ($after < 0) {
                throw ValidationException::withMessages(['items' => 'Promena lagera bi spustila stanje '.$item->sku_snapshot.' ispod nule.']);
            }

            $stockOwner->forceFill(['stock_quantity' => $after, 'updated_by' => $actor->id])->save();
            $movement = StockMovement::query()->create([
                'event_key' => $eventKey,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'order_id' => $order->id,
                'user_id' => $actor->id,
                'movement_type' => $change < 0 ? 'after_sales_replacement' : 'after_sales_return',
                'source' => 'after_sales_action',
                'quantity_change' => $change,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'note' => $action->action_number.' · '.$item->product_name_snapshot,
                'metadata_json' => [
                    'after_sales_case_id' => $action->after_sales_case_id,
                    'after_sales_action_id' => $action->id,
                    'product_variant_id' => $variant?->id,
                    'disposition' => $item->disposition,
                ],
            ]);
            $item->forceFill(['stock_movement_id' => $movement->id])->save();
            if ($variant) $affectedParents[$product->id] = $product;
        }
        foreach ($affectedParents as $parent) $this->variants->syncParent($parent);
    }

    private function assertExecutor(User $actor): void
    {
        if (!$actor->hasPermission('after_sales.execute')) abort(403);
    }

    private function assertAdditionalPermission(string $type, User $actor): void
    {
        if ($type === 'refund' && !$actor->hasPermission('payments.manage')) {
            throw ValidationException::withMessages(['action_type' => 'Za izvršenje refundacije potrebna je dozvola za upravljanje uplatama.']);
        }
        if (in_array($type, ['replacement_dispatch', 'return_receipt'], true) && !$actor->hasPermission('stock.adjust')) {
            throw ValidationException::withMessages(['action_type' => 'Za izvršenje zamene ili povrata potrebna je dozvola za korekciju lagera.']);
        }
    }

    private function assertCaseAllowsAction(AfterSalesCase $case, string $type): void
    {
        if (in_array($case->status, ['rejected', 'closed'], true)) {
            throw ValidationException::withMessages(['action_type' => 'Za odbijen ili zatvoren slučaj nije moguće planirati novu radnju.']);
        }
        if ($type === 'service_visit') {
            if (!in_array($case->status, ['under_review', 'awaiting_customer', 'approved', 'in_service'], true)) {
                throw ValidationException::withMessages(['action_type' => 'Servisna poseta može se planirati tek nakon početka obrade slučaja.']);
            }
            return;
        }
        if (!in_array($case->status, ['approved', 'in_service'], true)) {
            throw ValidationException::withMessages(['action_type' => 'Zamena, povrat robe ili refundacija mogu se planirati tek kada je slučaj odobren.']);
        }
    }

    private function assertActionBelongsToCase(AfterSalesCase $case, AfterSalesAction $action): void
    {
        if ((int) $action->after_sales_case_id !== (int) $case->id) abort(404);
    }

    private function validatedAssignee(mixed $value): ?int
    {
        if (!filled($value)) return null;
        $id = (int) $value;
        $valid = User::query()->whereKey($id)->where('status', 'active')
            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))->exists();
        if (!$valid) throw ValidationException::withMessages(['assigned_to' => 'Odgovorno lice mora biti aktivni administrator.']);
        return $id;
    }

    private function inventoryHandling(string $type, string $requested): string
    {
        if (!in_array($type, ['replacement_dispatch', 'return_receipt'], true)) return 'none';
        return in_array($requested, ['automatic', 'external'], true) ? $requested : 'automatic';
    }

    private function disposition(string $type, string $requested): string
    {
        return match ($type) {
            'service_visit' => 'repair',
            'replacement_dispatch' => 'replace',
            'return_receipt' => in_array($requested, ['restock', 'quarantine', 'scrap'], true) ? $requested : 'quarantine',
            default => 'none',
        };
    }

    private function stockEffect(string $type, string $handling, string $disposition): string
    {
        if ($handling !== 'automatic') return 'none';
        if ($type === 'replacement_dispatch') return 'decrease';
        if ($type === 'return_receipt' && $disposition === 'restock') return 'increase';
        return 'none';
    }

    private function notify(AfterSalesAction $action, User $actor, string $title, string $message, string $severity): void
    {
        $action->loadMissing(['case.opener', 'assignee']);
        $recipients = collect([$action->case?->opener, $action->assignee])
            ->filter(static fn (mixed $user): bool => $user instanceof User)
            ->unique('id');
        foreach ($recipients as $recipient) {
            if ((int) $recipient->id === (int) $actor->id) continue;
            $this->notifications->send($recipient, [
                'event' => 'after_sales_action_'.$action->status,
                'title' => $title,
                'message' => $message,
                'url' => $recipient->hasRole('admin', 'superadmin') ? route('admin.after-sales.show', $action->case) : route('after-sales.show', $action->case),
                'action_label' => 'Otvori slučaj',
                'icon' => 'cog',
                'severity' => $severity,
            ]);
        }
    }
}
