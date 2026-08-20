<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class OrderDetailService
{
    /** @return list<string> */
    public function prepare(Order $order, bool $includeInternal): array
    {
        $warnings = [];

        $this->loadOne($order, 'user', 'users', ['id'], $warnings);
        $this->loadOne($order, 'supplier', 'users', ['id'], $warnings, 'supplier_user_id');
        $this->loadOne($order, 'bankAccount', 'bank_accounts', ['id'], $warnings, 'bank_account_id');
        $this->loadOne($order, 'acceptedBy', 'users', ['id'], $warnings, 'accepted_by');
        $this->loadOne($order, 'assignedBy', 'users', ['id'], $warnings, 'assigned_by');
        $this->loadOne($order, 'completedBy', 'users', ['id'], $warnings, 'completed_by');
        $this->loadOne($order, 'reopenedBy', 'users', ['id'], $warnings, 'reopened_by');
        $this->loadOne($order, 'delivery', 'order_deliveries', ['id', 'order_id'], $warnings);
        $this->loadOne($order, 'shipment', 'order_shipments', ['id', 'order_id'], $warnings);
        if ($order->relationLoaded('shipment') && $order->shipment !== null) {
            try {
                $shipmentRelations = [];
                if ($this->hasColumns('courier_services', ['id'])) $shipmentRelations[] = 'courier';
                if ($this->hasColumns('users', ['id'])) $shipmentRelations[] = 'recorder';
                if ($shipmentRelations !== []) $order->shipment->load($shipmentRelations);
            } catch (Throwable $exception) {
                $warnings[] = 'Detalji slanja pošiljke trenutno nisu potpuno dostupni.';
                $this->safeLog($order, 'shipment', $exception);
            }
        }
        $this->loadOne($order, 'receivableCase', 'receivable_cases', ['id', 'order_id'], $warnings);
        if ($order->relationLoaded('receivableCase') && $order->receivableCase !== null) {
            try {
                $relations = [];
                if ($this->hasColumns('receivable_installments', ['id', 'receivable_case_id'])) $relations[] = 'installments';
                if ($this->hasColumns('receivable_contacts', ['id', 'receivable_case_id'])) $relations['contacts'] = static fn ($query) => $query->where('visible_to_customer', true)->orderByDesc('contacted_at')->limit(20);
                if ($relations !== []) $order->receivableCase->load($relations);
            } catch (Throwable $exception) {
                $warnings[] = 'Plan naplate trenutno nije potpuno dostupan.';
                $this->safeLog($order, 'receivableCase', $exception);
            }
        }
        if ($order->relationLoaded('delivery') && $order->delivery !== null && $this->hasColumns('users', ['id'])) {
            try {
                $order->delivery->load('confirmer');
            } catch (Throwable $exception) {
                $warnings[] = 'Podatak o potvrdiocu isporuke trenutno nije dostupan.';
                $this->safeLog($order, 'delivery.confirmer', $exception);
            }
        }

        $this->loadMany($order, 'items', 'order_items', ['id', 'order_id'], $warnings, ['product' => ['products', ['id']]]);
        $this->loadMany($order, 'documents', 'order_documents', ['id', 'order_id'], $warnings, [
            'issuer' => ['users', ['id']],
            'supersedes' => ['order_documents', ['id']],
        ]);
        $this->loadMany($order, 'statusHistory', 'order_status_history', ['id', 'order_id'], $warnings, ['actor' => ['users', ['id']]]);
        $this->loadMany($order, 'assignments', 'order_assignments', ['id', 'order_id'], $warnings, [
            'oldSupplier' => ['users', ['id']],
            'newSupplier' => ['users', ['id']],
            'actor' => ['users', ['id']],
        ]);
        $this->loadMany($order, 'payments', 'order_payments', ['id', 'order_id'], $warnings, [
            'submitter' => ['users', ['id']],
            'verifier' => ['users', ['id']],
        ]);

        if ($includeInternal) {
            $this->loadMany($order, 'internalNotes', 'order_internal_notes', ['id', 'order_id'], $warnings, ['user' => ['users', ['id']]]);
        } else {
            $order->setRelation('internalNotes', collect());
        }

        $this->loadCommission($order, $warnings);

        return array_values(array_unique($warnings));
    }

    /** @param list<string> $warnings */
    private function loadOne(
        Order $order,
        string $relation,
        string $table,
        array $columns,
        array &$warnings,
        ?string $foreignKey = null,
    ): void {
        if ($foreignKey !== null && !$this->hasColumns('orders', [$foreignKey])) {
            $order->setRelation($relation, null);
            $warnings[] = 'Nedostaje kolona orders.'.$foreignKey.'; deo detalja je privremeno sakriven.';
            return;
        }

        if (!$this->hasColumns($table, $columns)) {
            $order->setRelation($relation, null);
            $warnings[] = 'Nedostaje potrebna šema '.$table.'; deo detalja je privremeno sakriven.';
            return;
        }

        try {
            $order->load($relation);
        } catch (Throwable $exception) {
            $order->setRelation($relation, null);
            $warnings[] = 'Podatak „'.$relation.'” trenutno nije dostupan.';
            $this->safeLog($order, $relation, $exception);
        }
    }

    /**
     * @param list<string> $columns
     * @param list<string> $warnings
     * @param array<string,array{0:string,1:list<string>}> $nested
     */
    private function loadMany(
        Order $order,
        string $relation,
        string $table,
        array $columns,
        array &$warnings,
        array $nested = [],
    ): void {
        if (!$this->hasColumns($table, $columns)) {
            $order->setRelation($relation, collect());
            $warnings[] = 'Nedostaje potrebna šema '.$table.'; sekcija „'.$relation.'” je privremeno prazna.';
            return;
        }

        $relations = [$relation];
        foreach ($nested as $nestedRelation => [$nestedTable, $nestedColumns]) {
            if ($this->hasColumns($nestedTable, $nestedColumns)) {
                $relations[] = $relation.'.'.$nestedRelation;
            }
        }

        try {
            $order->load($relations);
        } catch (Throwable $exception) {
            $order->setRelation($relation, collect());
            $warnings[] = 'Sekcija „'.$relation.'” trenutno nije dostupna.';
            $this->safeLog($order, $relation, $exception);
        }
    }

    /** @param list<string> $warnings */
    private function loadCommission(Order $order, array &$warnings): void
    {
        if (!$this->hasColumns('order_commissions', ['id', 'order_id'])) {
            $order->setRelation('commission', null);
            return;
        }

        $relations = ['commission'];
        if ($this->hasColumns('users', ['id'])) {
            $relations[] = 'commission.user';
        }

        try {
            $order->load($relations);
        } catch (Throwable $exception) {
            $order->setRelation('commission', null);
            $warnings[] = 'Provizija trenutno nije dostupna.';
            $this->safeLog($order, 'commission', $exception);
            return;
        }

        $commission = $order->commission;
        if ($commission === null) {
            return;
        }

        if ($this->hasColumns('commission_status_history', ['id', 'commission_id'])) {
            try {
                $commission->load($this->hasColumns('users', ['id']) ? ['history.actor'] : ['history']);
            } catch (Throwable $exception) {
                $commission->setRelation('history', collect());
                $warnings[] = 'Istorija provizije trenutno nije dostupna.';
                $this->safeLog($order, 'commission.history', $exception);
            }
        } else {
            $commission->setRelation('history', collect());
        }

        if ($this->hasColumns('commission_payment_batches', ['id'])) {
            try {
                $commission->load('paymentBatch');
            } catch (Throwable $exception) {
                $commission->setRelation('paymentBatch', null);
                $this->safeLog($order, 'commission.paymentBatch', $exception);
            }
        } else {
            $commission->setRelation('paymentBatch', null);
        }
    }

    /** @param list<string> $columns */
    private function hasColumns(string $table, array $columns): bool
    {
        try {
            if (!Schema::hasTable($table)) {
                return false;
            }

            $existing = Schema::getColumnListing($table);
            return array_diff($columns, $existing) === [];
        } catch (Throwable) {
            return false;
        }
    }

    private function safeLog(Order $order, string $relation, Throwable $exception): void
    {
        try {
            Log::warning('Order detail relation nije učitana.', [
                'order_id' => $order->id,
                'relation' => $relation,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        } catch (Throwable) {
            // Logging ne sme prikriti problem detaljne stranice porudžbine.
        }
    }
}
