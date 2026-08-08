<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AfterSalesAction;
use App\Models\FieldServiceTeam;
use App\Models\FieldWorkOrder;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;

final class FieldWorkOrderPlanner
{
    public const PHYSICAL_ACTIONS = ['service_visit', 'replacement_dispatch', 'return_receipt'];

    /** @param array<string,mixed> $data */
    public function ensureForAction(AfterSalesAction $action, User $actor, array $data = []): ?FieldWorkOrder
    {
        if (!in_array($action->action_type, self::PHYSICAL_ACTIONS, true)) return null;
        if (!Schema::hasTable('field_work_orders') || !Schema::hasTable('field_service_teams')) {
            throw ValidationException::withMessages(['action_type' => 'Terenski radni nalozi nisu spremni. Pokrenite php artisan migrate --force i app:field-operations-doctor.']);
        }

        $action->loadMissing('case.order');
        /** @var Order|null $order */
        $order = $action->case?->order;
        $start = $this->date($data['scheduled_at'] ?? $action->scheduled_at);
        $end = $this->date($data['scheduled_end_at'] ?? null) ?? ($start?->addHours(2));
        $teamId = $this->validatedTeamId($data['field_service_team_id'] ?? null);

        $workOrder = FieldWorkOrder::query()->firstOrNew(['after_sales_action_id' => $action->id]);
        if (!$workOrder->exists) {
            $workOrder->fill([
                'work_order_number' => 'PENDING',
                'status' => match ($action->status) {
                    'completed' => 'completed',
                    'cancelled' => 'cancelled',
                    'in_progress' => 'on_site',
                    default => 'planned',
                },
                'customer_name_snapshot' => $order?->shipping_full_name ?: 'Kupac',
                'customer_phone_snapshot' => $order?->shipping_phone,
                'service_address_snapshot' => $this->address($order),
                'route_reference' => $action->reference,
                'public_note' => $action->public_note,
                'internal_note' => $action->internal_note,
                'created_by' => $actor->id,
            ]);
        }

        $this->assertWindow($start, $end);
        $this->assertNoConflict($teamId, $start, $end, $workOrder->exists ? $workOrder->id : null);
        $workOrder->fill([
            'field_service_team_id' => $teamId,
            'planned_start_at' => $start,
            'planned_end_at' => $end,
            'updated_by' => $actor->id,
        ])->save();

        if ($workOrder->work_order_number === 'PENDING') {
            $workOrder->forceFill([
                'work_order_number' => 'RN-'.now()->format('Ymd').'-'.str_pad((string) $workOrder->id, 6, '0', STR_PAD_LEFT),
            ])->save();
        }

        return $workOrder->fresh(['team', 'attachments']);
    }

    /** @param array<string,mixed> $data */
    public function schedule(FieldWorkOrder $workOrder, User $actor, array $data): FieldWorkOrder
    {
        if ($workOrder->isTerminal()) {
            throw ValidationException::withMessages(['schedule' => 'Završen ili otkazan radni nalog ne može da se ponovo raspoređuje.']);
        }

        $start = $this->date($data['planned_start_at'] ?? null);
        $end = $this->date($data['planned_end_at'] ?? null);
        $teamId = $this->validatedTeamId($data['field_service_team_id'] ?? null);
        $this->assertWindow($start, $end);
        $this->assertNoConflict($teamId, $start, $end, $workOrder->id);

        $workOrder->forceFill([
            'field_service_team_id' => $teamId,
            'planned_start_at' => $start,
            'planned_end_at' => $end,
            'route_reference' => filled($data['route_reference'] ?? null) ? trim((string) $data['route_reference']) : null,
            'public_note' => filled($data['public_note'] ?? null) ? trim((string) $data['public_note']) : null,
            'internal_note' => filled($data['internal_note'] ?? null) ? trim((string) $data['internal_note']) : null,
            'updated_by' => $actor->id,
        ])->save();

        $workOrder->action()->update([
            'scheduled_at' => $start,
            'due_at' => $end,
            'updated_by' => $actor->id,
        ]);

        return $workOrder->fresh(['team', 'action.case.order']);
    }

    public function assertNoConflict(?int $teamId, ?CarbonImmutable $start, ?CarbonImmutable $end, ?int $exceptId = null): void
    {
        if ($teamId === null || $start === null || $end === null) return;

        $conflict = FieldWorkOrder::query()
            ->where('field_service_team_id', $teamId)
            ->whereIn('status', ['planned', 'en_route', 'on_site'])
            ->whereNotNull('planned_start_at')
            ->whereNotNull('planned_end_at')
            ->where('planned_start_at', '<', $end)
            ->where('planned_end_at', '>', $start)
            ->when($exceptId !== null, static fn ($query) => $query->where('id', '<>', $exceptId))
            ->first();

        if ($conflict instanceof FieldWorkOrder) {
            throw ValidationException::withMessages([
                'field_service_team_id' => 'Izabrana ekipa je već zauzeta u tom terminu radnim nalogom '.$conflict->work_order_number.'.',
            ]);
        }
    }

    private function validatedTeamId(mixed $value): ?int
    {
        if (!filled($value)) return null;
        $id = (int) $value;
        $team = FieldServiceTeam::query()->lockForUpdate()->find($id);
        if (!$team instanceof FieldServiceTeam || !$team->is_active) {
            throw ValidationException::withMessages(['field_service_team_id' => 'Izaberite aktivnu terensku ekipu ili servisnog partnera.']);
        }
        return $id;
    }

    private function assertWindow(?CarbonImmutable $start, ?CarbonImmutable $end): void
    {
        if (($start === null) !== ($end === null)) {
            throw ValidationException::withMessages(['planned_end_at' => 'Početak i kraj termina moraju biti uneti zajedno.']);
        }
        if ($start !== null && $end !== null && !$end->isAfter($start)) {
            throw ValidationException::withMessages(['planned_end_at' => 'Kraj termina mora biti posle početka termina.']);
        }
        if ($start !== null && $end !== null && $start->diffInHours($end) > 24) {
            throw ValidationException::withMessages(['planned_end_at' => 'Jedan terenski termin ne može trajati duže od 24 sata.']);
        }
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof \DateTimeInterface) return CarbonImmutable::instance($value);
        if (!filled($value)) return null;
        return CarbonImmutable::parse((string) $value, config('app.timezone'));
    }

    private function address(?Order $order): ?string
    {
        if (!$order instanceof Order) return null;
        $city = trim((string) $order->shipping_postal_code.' '.(string) $order->shipping_city);
        $address = trim(implode(', ', array_filter([trim((string) $order->shipping_address), $city])));
        return $address !== '' ? $address : null;
    }
}
