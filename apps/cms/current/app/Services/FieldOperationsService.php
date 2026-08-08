<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FieldWorkOrder;
use App\Models\FieldWorkOrderAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class FieldOperationsService
{
    public function __construct(
        private readonly AfterSalesAccessService $access,
        private readonly AfterSalesActionService $actions,
        private readonly FieldWorkOrderPlanner $planner,
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
        private readonly ServicePartsInventoryService $serviceParts,
    ) {}

    /** @param array<string,mixed> $data */
    public function schedule(FieldWorkOrder $workOrder, User $actor, array $data): FieldWorkOrder
    {
        $this->authorize($workOrder, $actor);
        return DB::transaction(function () use ($workOrder, $actor, $data): FieldWorkOrder {
            $locked = FieldWorkOrder::query()->with(['action.case.order', 'team'])->lockForUpdate()->findOrFail($workOrder->id);
            $this->authorize($locked, $actor);
            $before = $locked->toArray();
            $updated = $this->planner->schedule($locked, $actor, $data);
            $this->audit->log('field_work_order.scheduled', 'Raspoređen radni nalog '.$updated->work_order_number, $updated, $before, $updated->toArray(), null, $actor);
            return $updated;
        }, 5);
    }

    public function markEnRoute(FieldWorkOrder $workOrder, User $actor): FieldWorkOrder
    {
        return $this->transition($workOrder, $actor, 'en_route');
    }

    public function markOnSite(FieldWorkOrder $workOrder, User $actor): FieldWorkOrder
    {
        return $this->transition($workOrder, $actor, 'on_site');
    }

    /** @param array<string,mixed> $data @param array<int,UploadedFile> $attachments */
    public function complete(FieldWorkOrder $workOrder, User $actor, array $data, array $attachments = []): FieldWorkOrder
    {
        $this->authorize($workOrder, $actor);
        $stored = $this->storeUploads($workOrder, $attachments, (string) ($data['attachment_visibility'] ?? 'internal'));

        try {
            $completed = DB::transaction(function () use ($workOrder, $actor, $data, $stored): FieldWorkOrder {
                $relations = ['action.case.order', 'action.items', 'team'];
                if (Schema::hasTable('field_work_order_parts') && Schema::hasTable('service_parts')) $relations[] = 'parts.part';
                $locked = FieldWorkOrder::query()->with($relations)->lockForUpdate()->findOrFail($workOrder->id);
                $this->authorize($locked, $actor);
                if ($locked->status === 'completed') return $locked;
                if ($locked->status === 'cancelled') {
                    throw ValidationException::withMessages(['work_order' => 'Otkazan radni nalog ne može biti završen.']);
                }
                if ($locked->status !== 'on_site') {
                    throw ValidationException::withMessages(['work_order' => 'Pre završetka evidentirajte da je ekipa stigla na lokaciju.']);
                }

                $result = trim((string) ($data['completion_result'] ?? ''));
                if ($result === '') {
                    throw ValidationException::withMessages(['completion_result' => 'Unesite rezultat terenske intervencije.']);
                }

                $automaticPartsCost = $this->serviceParts->finalizeWorkOrderParts($locked, $actor, (array) ($data['part_consumption'] ?? []));

                $this->actions->complete(
                    $locked->action->case,
                    $locked->action,
                    $actor,
                    ['reference' => $data['route_reference'] ?? $locked->route_reference, 'completion_note' => $result],
                    true,
                );

                $travel = $this->amount($data['travel_cost_rsd'] ?? 0);
                $labor = $this->amount($data['labor_cost_rsd'] ?? 0);
                $parts = $automaticPartsCost + $this->amount($data['parts_cost_rsd'] ?? 0);
                $before = $locked->toArray();
                $locked->forceFill([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'status_by' => $actor->id,
                    'route_reference' => filled($data['route_reference'] ?? null) ? trim((string) $data['route_reference']) : $locked->route_reference,
                    'completion_result' => $result,
                    'travel_km' => filled($data['travel_km'] ?? null) ? max(0, round((float) $data['travel_km'], 2)) : null,
                    'travel_cost_rsd' => $travel,
                    'labor_cost_rsd' => $labor,
                    'parts_cost_rsd' => $parts,
                    'total_cost_rsd' => $travel + $labor + $parts,
                    'updated_by' => $actor->id,
                    'cancelled_at' => null,
                    'cancellation_reason' => null,
                ])->save();

                foreach ($stored as $file) {
                    FieldWorkOrderAttachment::query()->create($file + [
                        'field_work_order_id' => $locked->id,
                        'uploaded_by' => $actor->id,
                    ]);
                }

                $this->audit->log('field_work_order.completed', 'Završen radni nalog '.$locked->work_order_number, $locked, $before, $locked->toArray(), ['attachment_count' => count($stored)], $actor);
                return $locked->fresh(['team', 'action.case.order', 'attachments']);
            }, 5);
        } catch (Throwable $exception) {
            foreach ($stored as $file) Storage::disk('local')->delete((string) $file['path']);
            throw $exception;
        }

        $this->notifyCustomer($completed, 'Terenska intervencija je završena', 'Radni nalog '.$completed->work_order_number.' je završen.', 'success');
        return $completed;
    }

    public function cancel(FieldWorkOrder $workOrder, User $actor, string $reason): FieldWorkOrder
    {
        $this->authorize($workOrder, $actor);
        $cancelled = DB::transaction(function () use ($workOrder, $actor, $reason): FieldWorkOrder {
            $locked = FieldWorkOrder::query()->with(['action.case.order', 'team'])->lockForUpdate()->findOrFail($workOrder->id);
            $this->authorize($locked, $actor);
            if ($locked->status === 'cancelled') return $locked;
            if ($locked->status === 'completed') {
                throw ValidationException::withMessages(['cancellation_reason' => 'Završen radni nalog se ne može otkazati.']);
            }
            $this->serviceParts->releaseWorkOrderReservations($locked, $actor, 'Otkazivanje radnog naloga: '.trim($reason));
            $this->actions->cancel($locked->action->case, $locked->action, $actor, $reason);
            $before = $locked->toArray();
            $locked->forceFill([
                'status' => 'cancelled', 'cancelled_at' => now(), 'status_by' => $actor->id,
                'cancellation_reason' => trim($reason), 'updated_by' => $actor->id,
            ])->save();
            $this->audit->log('field_work_order.cancelled', 'Otkazan radni nalog '.$locked->work_order_number, $locked, $before, $locked->toArray(), null, $actor);
            return $locked->fresh(['team', 'action.case.order']);
        }, 5);
        $this->notifyCustomer($cancelled, 'Terenski termin je otkazan', 'Radni nalog '.$cancelled->work_order_number.' je otkazan. Bićete kontaktirani radi novog dogovora.', 'warning');
        return $cancelled;
    }

    private function transition(FieldWorkOrder $workOrder, User $actor, string $target): FieldWorkOrder
    {
        $this->authorize($workOrder, $actor);
        $updated = DB::transaction(function () use ($workOrder, $actor, $target): FieldWorkOrder {
            $locked = FieldWorkOrder::query()->with(['action.case.order', 'team'])->lockForUpdate()->findOrFail($workOrder->id);
            $this->authorize($locked, $actor);
            if ($locked->isTerminal()) {
                throw ValidationException::withMessages(['work_order' => 'Završen ili otkazan radni nalog ne može promeniti status.']);
            }
            if (!$locked->team || !$locked->planned_start_at || !$locked->planned_end_at) {
                throw ValidationException::withMessages(['work_order' => 'Pre promene statusa dodelite aktivnu ekipu i kompletan termin.']);
            }
            if ($target === 'en_route' && !in_array($locked->status, ['planned', 'en_route'], true)) {
                throw ValidationException::withMessages(['work_order' => 'Ekipa može krenuti samo sa planiranog radnog naloga.']);
            }
            if ($target === 'on_site' && !in_array($locked->status, ['planned', 'en_route', 'on_site'], true)) {
                throw ValidationException::withMessages(['work_order' => 'Dolazak na lokaciju nije moguć iz trenutnog statusa.']);
            }
            if ($locked->action->status === 'planned') {
                $this->actions->start($locked->action->case, $locked->action, $actor, true);
            }
            $before = $locked->toArray();
            $locked->forceFill([
                'status' => $target,
                'en_route_at' => $target === 'en_route' ? ($locked->en_route_at ?? now()) : $locked->en_route_at,
                'on_site_at' => $target === 'on_site' ? ($locked->on_site_at ?? now()) : $locked->on_site_at,
                'status_by' => $actor->id,
                'updated_by' => $actor->id,
            ])->save();
            $this->audit->log('field_work_order.'.$target, 'Promenjen status radnog naloga '.$locked->work_order_number, $locked, $before, $locked->toArray(), null, $actor);
            return $locked->fresh(['team', 'action.case.order']);
        }, 5);

        if ($target === 'en_route') {
            $this->notifyCustomer($updated, 'Ekipa je krenula', 'Ekipa je krenula na zakazani termin '.$updated->work_order_number.'.', 'info');
        }
        if ($target === 'on_site') {
            $this->notifyCustomer($updated, 'Ekipa je stigla', 'Evidentiran je dolazak ekipe za radni nalog '.$updated->work_order_number.'.', 'info');
        }
        return $updated;
    }

    private function authorize(FieldWorkOrder $workOrder, User $actor): void
    {
        abort_unless($actor->hasPermission('field_operations.manage'), 403);
        $workOrder->loadMissing('action.case.order');
        $this->access->authorizeManage($workOrder->action->case, $actor);
    }

    /** @param array<int,UploadedFile> $uploads @return array<int,array<string,mixed>> */
    private function storeUploads(FieldWorkOrder $workOrder, array $uploads, string $visibility): array
    {
        $result = [];
        foreach ($uploads as $upload) {
            if (!$upload instanceof UploadedFile || !$upload->isValid()) continue;
            if ($upload->getSize() > 10 * 1024 * 1024) {
                throw ValidationException::withMessages(['attachments' => 'Svaki prilog može imati najviše 10 MB.']);
            }
            $mime = (string) ($upload->getMimeType() ?: $upload->getClientMimeType());
            if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)) {
                throw ValidationException::withMessages(['attachments' => 'Dozvoljeni su PDF, JPG, PNG i WebP fajlovi.']);
            }
            $storedName = Str::uuid().'.'.strtolower((string) ($upload->guessExtension() ?: 'bin'));
            $path = $upload->storeAs('field-work-orders/'.$workOrder->id, $storedName, 'local');
            if (!is_string($path) || $path === '') {
                throw ValidationException::withMessages(['attachments' => 'Prilog nije mogao biti sačuvan.']);
            }
            $result[] = [
                'visibility' => $visibility === 'public' ? 'public' : 'internal',
                'file_type' => 'proof',
                'original_name' => mb_substr((string) $upload->getClientOriginalName(), 0, 255),
                'stored_name' => $storedName,
                'path' => $path,
                'mime_type' => $mime,
                'size_bytes' => (int) $upload->getSize(),
            ];
        }
        return $result;
    }

    private function amount(mixed $value): float
    {
        return max(0, round((float) ($value ?: 0), 2));
    }

    private function notifyCustomer(FieldWorkOrder $workOrder, string $title, string $message, string $severity): void
    {
        $workOrder->loadMissing('action.case.opener');
        $customer = $workOrder->action?->case?->opener;
        if (!$customer instanceof User) return;
        $this->notifications->send($customer, [
            'event' => 'field_work_order_'.$workOrder->status,
            'title' => $title,
            'message' => $message,
            'url' => route('after-sales.show', $workOrder->action->case),
            'action_label' => 'Otvori slučaj',
            'icon' => 'truck',
            'severity' => $severity,
        ]);
    }
}
