<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderInternalNote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OrderOperationalService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
        private readonly OrderAccessService $access,
        private readonly OrderEmailOutboxService $emails,
    ) {}

    public function accept(Order $order, User $actor): Order
    {
        $this->access->authorizeManage($order, $actor);
        $updated = DB::transaction(function () use ($order, $actor): Order {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $this->assertNotCompleted($locked);
            if ($locked->source_system !== 'laravel') {
                throw ValidationException::withMessages(['order' => 'Legacy porudžbina je read-only.']);
            }
            if ($locked->accepted_at !== null) {
                return $locked;
            }
            $locked->update([
                'accepted_by' => $actor->id,
                'accepted_at' => now(),
                'updated_by' => $actor->id,
            ]);
            $this->audit->log('order.accepted', 'Preuzeta porudžbina '.$locked->order_number, $locked, after: ['accepted_by' => $actor->id, 'accepted_at' => $locked->accepted_at], user: $actor);
            return $locked->fresh(['user', 'supplier']);
        }, 5);

        if ($updated->user instanceof User) {
            $this->notifications->order($updated->user, 'order.accepted', 'Porudžbina je preuzeta', 'Odgovorno lice je preuzelo obradu porudžbine '.$updated->order_number.'.', $updated);
        }
        $this->emails->orderChanged($updated, 'order_accepted', 'Porudžbina '.$updated->order_number.' je preuzeta', 'Odgovorno lice je preuzelo porudžbinu u obradu.', ['actor_id' => $actor->id, 'accepted_at' => $updated->accepted_at?->toISOString()]);
        return $updated;
    }

    public function addInternalNote(Order $order, User $actor, string $note): OrderInternalNote
    {
        $this->access->authorizeManage($order, $actor);
        $note = trim($note);
        if ($note === '') {
            throw ValidationException::withMessages(['note' => 'Interna napomena je obavezna.']);
        }

        return DB::transaction(function () use ($order, $actor, $note): OrderInternalNote {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $this->assertNotCompleted($locked);
            $entry = OrderInternalNote::query()->create([
                'order_id' => $locked->id,
                'user_id' => $actor->id,
                'note' => $note,
            ]);
            $locked->update(['last_internal_note_at' => now(), 'updated_by' => $actor->id]);
            $this->audit->log('order.internal_note_added', 'Dodata interna napomena '.$locked->order_number, $locked, after: ['note_id' => $entry->id], metadata: ['note' => $note], user: $actor);
            return $entry->load('user');
        }, 5);
    }

    public function reassign(Order $order, User $newSupplier, User $actor, string $reason): Order
    {
        abort_unless($actor->hasRole('superadmin'), 403);
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Razlog ponovne dodele je obavezan.']);
        }
        if (!$newSupplier->hasRole('admin', 'superadmin') || $newSupplier->status !== 'active') {
            throw ValidationException::withMessages(['supplier_user_id' => 'Izabrano odgovorno lice nije aktivan Administrator ili SuperAdministrator.']);
        }

        $oldSupplier = null;
        $updated = DB::transaction(function () use ($order, $newSupplier, $actor, $reason, &$oldSupplier): Order {
            /** @var Order $locked */
            $locked = Order::query()->with('supplier')->lockForUpdate()->findOrFail($order->id);
            $this->assertNotCompleted($locked);
            if ($locked->source_system !== 'laravel') {
                throw ValidationException::withMessages(['order' => 'Legacy porudžbina je read-only.']);
            }
            if ((int) $locked->supplier_user_id === (int) $newSupplier->id) {
                throw ValidationException::withMessages(['supplier_user_id' => 'Porudžbina je već dodeljena tom odgovornom licu.']);
            }
            $oldSupplier = $locked->supplier;
            OrderAssignment::query()->create([
                'order_id' => $locked->id,
                'old_supplier_user_id' => $locked->supplier_user_id,
                'new_supplier_user_id' => $newSupplier->id,
                'changed_by' => $actor->id,
                'reason' => $reason,
                'created_at' => now(),
            ]);
            $before = ['supplier_user_id' => $locked->supplier_user_id, 'supplier_name_snapshot' => $locked->supplier_name_snapshot];
            $locked->update([
                'supplier_user_id' => $newSupplier->id,
                'supplier_name_snapshot' => $newSupplier->displayName(),
                'supplier_email_snapshot' => $newSupplier->email,
                'supplier_phone_snapshot' => $newSupplier->phone,
                'supplier_role_snapshot' => $newSupplier->roleName(),
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'reassigned_at' => now(),
                'accepted_by' => null,
                'accepted_at' => null,
                'updated_by' => $actor->id,
            ]);
            $this->audit->log('order.reassigned', 'Ponovo dodeljena porudžbina '.$locked->order_number, $locked, before: $before, after: ['supplier_user_id' => $newSupplier->id, 'supplier_name_snapshot' => $newSupplier->displayName()], metadata: ['reason' => $reason], user: $actor);
            return $locked->fresh(['user', 'supplier']);
        }, 5);

        $this->notifications->order($newSupplier, 'order.assigned', 'Dodeljena vam je porudžbina', 'Porudžbina '.$updated->order_number.' je dodeljena vama. Razlog: '.$reason, $updated, ['severity' => 'warning']);
        if ($oldSupplier instanceof User) {
            $this->notifications->order($oldSupplier, 'order.reassigned_away', 'Porudžbina je ponovo dodeljena', 'Porudžbina '.$updated->order_number.' više nije dodeljena vama.', $updated, [
                'severity' => 'info',
                'url' => route('admin.orders.index', ['q' => $updated->order_number]),
                'action_label' => 'Otvori listu porudžbina',
            ]);
        }
        if ($updated->user instanceof User) {
            $this->notifications->order($updated->user, 'order.supplier_changed', 'Promenjeno odgovorno lice', 'Za porudžbinu '.$updated->order_number.' novo odgovorno lice je '.$newSupplier->displayName().'.', $updated);
        }
        $this->emails->orderChanged($updated, 'order_reassigned', 'Promenjeno odgovorno lice za '.$updated->order_number, 'Novo odgovorno lice je '.$newSupplier->displayName().'. Razlog: '.$reason, ['supplier_user_id' => $newSupplier->id, 'reason' => $reason, 'actor_id' => $actor->id]);
        return $updated;
    }

    /** @param array<string,mixed> $data */
    public function updateDeadlines(Order $order, User $actor, array $data): Order
    {
        $this->access->authorizeManage($order, $actor);
        $updated = DB::transaction(function () use ($order, $actor, $data): Order {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $this->assertNotCompleted($locked);
            $before = [
                'expected_processing_at' => $locked->expected_processing_at?->toISOString(),
                'expected_shipping_at' => $locked->expected_shipping_at?->toISOString(),
            ];
            $locked->update([
                'expected_processing_at' => $data['expected_processing_at'] ?? null,
                'expected_shipping_at' => $data['expected_shipping_at'] ?? null,
                'updated_by' => $actor->id,
            ]);
            $this->audit->log('order.deadlines_changed', 'Promenjeni rokovi '.$locked->order_number, $locked, before: $before, after: ['expected_processing_at' => $locked->expected_processing_at?->toISOString(), 'expected_shipping_at' => $locked->expected_shipping_at?->toISOString()], user: $actor);
            return $locked->fresh(['user', 'supplier']) ?? $locked;
        }, 5);

        $parts = array_filter([
            $updated->expected_processing_at ? 'obrada do '.$updated->expected_processing_at->format('d.m.Y H:i') : null,
            $updated->expected_shipping_at ? 'slanje do '.$updated->expected_shipping_at->format('d.m.Y H:i') : null,
        ]);
        $message = $parts !== []
            ? 'Planirani rokovi za '.$updated->order_number.': '.implode(', ', $parts).'.'
            : 'Planirani rokovi za '.$updated->order_number.' su uklonjeni.';
        if ($updated->user instanceof User) {
            $this->notifications->order($updated->user, 'order.deadlines_changed', 'Ažurirani su rokovi porudžbine', $message, $updated);
        }
        $this->emails->orderChanged($updated, 'order_deadlines_changed', 'Ažurirani rokovi porudžbine '.$updated->order_number, $message, ['expected_processing_at' => $updated->expected_processing_at?->toISOString(), 'expected_shipping_at' => $updated->expected_shipping_at?->toISOString(), 'actor_id' => $actor->id]);

        return $updated;
    }
    private function assertNotCompleted(Order $order): void
    {
        if ($order->completed_at !== null) {
            throw ValidationException::withMessages([
                'order' => 'Porudžbina je kompletirana i zaključana za dalje operativne izmene.',
            ]);
        }
    }

}
