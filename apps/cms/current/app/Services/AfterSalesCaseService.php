<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AfterSalesAction;
use App\Models\AfterSalesAttachment;
use App\Models\AfterSalesCase;
use App\Models\AfterSalesMessage;
use App\Models\AfterSalesStatusHistory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class AfterSalesCaseService
{
    public function __construct(
        private readonly AfterSalesAccessService $access,
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
    ) {}

    /** @param array<string,mixed> $data @param list<UploadedFile> $files */
    public function create(Order $order, User $actor, array $data, array $files = []): AfterSalesCase
    {
        if (!$this->access->canCreateForOrder($order, $actor)) {
            throw ValidationException::withMessages([
                'order' => 'Postprodajni slučaj može se otvoriti samo za isporučenu ili kompletiranu sopstvenu porudžbinu.',
            ]);
        }

        $storedPaths = [];
        try {
            $case = DB::transaction(function () use ($order, $actor, $data, $files, &$storedPaths): AfterSalesCase {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
                if (!$this->access->canCreateForOrder($lockedOrder, $actor)) {
                    throw ValidationException::withMessages(['order' => 'Porudžbina više nije dostupna za postprodajni zahtev.']);
                }

                $selected = collect((array) ($data['items'] ?? []))
                    ->filter(static fn (mixed $row): bool => is_array($row) && filter_var($row['selected'] ?? false, FILTER_VALIDATE_BOOL));
                if ($selected->isEmpty()) {
                    throw ValidationException::withMessages(['items' => 'Izaberite najmanje jednu stavku na koju se zahtev odnosi.']);
                }

                $orderItems = OrderItem::query()
                    ->where('order_id', $lockedOrder->id)
                    ->whereIn('id', $selected->keys()->map(static fn (mixed $id): int => (int) $id))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($orderItems->count() !== $selected->count()) {
                    throw ValidationException::withMessages(['items' => 'Jedna ili više izabranih stavki ne pripadaju ovoj porudžbini.']);
                }

                $assignedTo = null;
                if ($lockedOrder->supplier_user_id !== null) {
                    $assignedTo = User::query()
                        ->whereKey($lockedOrder->supplier_user_id)
                        ->where('status', 'active')
                        ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))
                        ->value('id');
                }

                $case = AfterSalesCase::query()->create([
                    'case_number' => 'PENDING-'.Str::uuid(),
                    'order_id' => $lockedOrder->id,
                    'opened_by' => $actor->id,
                    'assigned_to' => $assignedTo !== null ? (int) $assignedTo : null,
                    'case_type' => (string) $data['case_type'],
                    'priority' => (string) $data['priority'],
                    'status' => 'open',
                    'subject' => trim((string) $data['subject']),
                    'description' => trim((string) $data['description']),
                    'requested_resolution' => $data['requested_resolution'] ?? null,
                    'customer_name_snapshot' => $lockedOrder->shipping_full_name,
                    'customer_phone_snapshot' => $lockedOrder->shipping_phone,
                    'customer_address_snapshot' => trim(implode(', ', array_filter([
                        $lockedOrder->shipping_address,
                        trim((string) $lockedOrder->shipping_postal_code.' '.(string) $lockedOrder->shipping_city),
                    ]))),
                    'due_at' => now()->addDays($this->slaDays((string) $data['priority'])),
                ]);
                $case->forceFill([
                    'case_number' => 'PS-'.now()->format('Ymd').'-'.str_pad((string) $case->id, 6, '0', STR_PAD_LEFT),
                ])->save();

                foreach ($selected as $itemId => $row) {
                    /** @var OrderItem $item */
                    $item = $orderItems->get((int) $itemId);
                    $quantity = min(max(1, (int) ($row['quantity'] ?? 1)), max(1, (int) $item->quantity));
                    $case->items()->create([
                        'order_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'sku_snapshot' => $item->variant_sku_snapshot ?: $item->product_sku,
                        'product_name_snapshot' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
                        'quantity' => $quantity,
                        'issue_description' => filled($row['issue_description'] ?? null) ? trim((string) $row['issue_description']) : null,
                    ]);
                }

                AfterSalesStatusHistory::query()->create([
                    'after_sales_case_id' => $case->id,
                    'from_status' => null,
                    'to_status' => 'open',
                    'actor_id' => $actor->id,
                    'note' => 'Postprodajni slučaj je otvoren.',
                    'metadata_json' => ['case_type' => $case->case_type, 'priority' => $case->priority],
                    'created_at' => now(),
                ]);

                $this->storeAttachments($case, null, $actor, $files, $storedPaths);
                $this->audit->log('after_sales.created', 'Otvoren postprodajni slučaj '.$case->case_number, $case, null, $case->toArray(), ['order_id' => $lockedOrder->id], $actor);

                return $case;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) Storage::disk('local')->delete($path);
            throw $exception;
        }

        $this->notifyCreated($case->load(['order', 'assignee']), $actor);
        return $case->load(['order', 'items', 'messages.user', 'attachments']);
    }

    /** @param list<UploadedFile> $files */
    public function addMessage(AfterSalesCase $case, User $actor, string $body, string $visibility, array $files = []): AfterSalesMessage
    {
        $this->access->authorizeView($case->loadMissing('order'), $actor);
        if ($case->isClosed()) {
            throw ValidationException::withMessages(['body' => 'Zatvoren slučaj ne prima nove poruke. Administrator ga prvo mora ponovo otvoriti.']);
        }

        if (!$actor->hasRole('admin', 'superadmin')) $visibility = 'public';
        if ($visibility === 'internal' && !$actor->hasPermission('after_sales.manage')) abort(403);

        $storedPaths = [];
        try {
            $message = DB::transaction(function () use ($case, $actor, $body, $visibility, $files, &$storedPaths): AfterSalesMessage {
                $locked = AfterSalesCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
                $this->access->authorizeView($locked, $actor);
                if ($locked->isClosed()) {
                    throw ValidationException::withMessages(['body' => 'Zatvoren slučaj ne prima nove poruke. Administrator ga prvo mora ponovo otvoriti.']);
                }

                $message = $locked->messages()->create([
                    'user_id' => $actor->id,
                    'visibility' => $visibility,
                    'body' => trim($body),
                ]);
                $this->storeAttachments($locked, $message, $actor, $files, $storedPaths);

                if ($actor->hasRole('admin', 'superadmin') && $visibility === 'public' && $locked->first_response_at === null) {
                    $locked->forceFill(['first_response_at' => now()])->save();
                }
                if (!$actor->hasRole('admin', 'superadmin') && $locked->status === 'awaiting_customer') {
                    $from = $locked->status;
                    $locked->forceFill(['status' => 'under_review'])->save();
                    $this->history($locked, $actor, $from, 'under_review', 'Kupac je dostavio odgovor; slučaj je vraćen u obradu.');
                }

                $this->audit->log('after_sales.message', 'Dodata poruka u slučaju '.$locked->case_number, $locked, null, ['visibility' => $visibility], null, $actor);
                return $message;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) Storage::disk('local')->delete($path);
            throw $exception;
        }

        $this->notifyMessage($case->fresh(['order', 'opener', 'assignee']), $actor, $visibility);
        return $message->load(['user', 'attachments']);
    }

    /** @param array<string,mixed> $data */
    public function update(AfterSalesCase $case, User $actor, array $data): AfterSalesCase
    {
        $this->access->authorizeManage($case->loadMissing('order'), $actor);

        $updated = DB::transaction(function () use ($case, $actor, $data): AfterSalesCase {
            $locked = AfterSalesCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
            $this->access->authorizeManage($locked, $actor);
            $from = $locked->status;
            $to = (string) $data['status'];
            $this->assertTransition($from, $to, $actor);
            if (in_array($from, ['resolved', 'rejected', 'closed'], true) && $to === 'under_review' && blank($data['note'] ?? null)) {
                throw ValidationException::withMessages(['note' => 'Za ponovno otvaranje slučaja obavezno unesite razlog.']);
            }

            if (filled($data['assigned_to'] ?? null)) {
                $assignee = User::query()->where('status', 'active')->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))->find((int) $data['assigned_to']);
                if (!$assignee) throw ValidationException::withMessages(['assigned_to' => 'Izabrano odgovorno lice nije aktivni administrator.']);
            }

            if (in_array($to, ['resolved', 'rejected', 'closed'], true) && Schema::hasTable('after_sales_actions')) {
                $activeActions = AfterSalesAction::query()
                    ->where('after_sales_case_id', $locked->id)
                    ->whereIn('status', ['planned', 'in_progress'])
                    ->count();
                if ($activeActions > 0) {
                    throw ValidationException::withMessages(['status' => 'Slučaj ne može biti završen dok postoje planirane ili aktivne izvršne radnje.']);
                }

                $resolutionType = (string) ($data['resolution_type'] ?? $locked->resolution_type);
                $requiresExecution = in_array($resolutionType, ['repair', 'replacement', 'partial_refund', 'full_refund', 'return'], true);
                if ($requiresExecution && !in_array($from, ['resolved', 'rejected'], true)) {
                    $completedActions = AfterSalesAction::query()
                        ->where('after_sales_case_id', $locked->id)
                        ->where('status', 'completed')
                        ->count();
                    if ($completedActions === 0) {
                        throw ValidationException::withMessages(['status' => 'Za izabrano rešenje prvo evidentirajte i izvršite odgovarajuću postprodajnu radnju.']);
                    }
                }
            }

            if (in_array($to, ['resolved', 'rejected', 'closed'], true) && blank($data['resolution_summary'] ?? null)) {
                throw ValidationException::withMessages(['resolution_summary' => 'Za rešavanje, odbijanje ili zatvaranje obavezno unesite obrazloženje odluke.']);
            }
            if (in_array($to, ['resolved', 'rejected', 'closed'], true) && blank($data['resolution_type'] ?? null)) {
                throw ValidationException::withMessages(['resolution_type' => 'Za rešavanje, odbijanje ili zatvaranje izaberite vrstu odluke.']);
            }

            $before = $locked->toArray();
            $priority = (string) $data['priority'];
            $dueAt = filled($data['due_at'] ?? null)
                ? $data['due_at']
                : ($priority !== (string) $locked->priority ? now()->addDays($this->slaDays($priority)) : $locked->due_at);
            $locked->fill([
                'status' => $to,
                'priority' => $priority,
                'assigned_to' => filled($data['assigned_to'] ?? null) ? (int) $data['assigned_to'] : $locked->assigned_to,
                'due_at' => $dueAt,
                'resolution_type' => $data['resolution_type'] ?? $locked->resolution_type,
                'resolution_summary' => filled($data['resolution_summary'] ?? null) ? trim((string) $data['resolution_summary']) : $locked->resolution_summary,
            ]);
            if ($to === 'resolved' && $locked->resolved_at === null) $locked->resolved_at = now();
            if (in_array($to, ['open', 'under_review', 'awaiting_customer', 'approved', 'in_service'], true)) $locked->resolved_at = null;
            if ($to === 'closed') {
                $locked->closed_at = now();
                $locked->closed_by = $actor->id;
            } else {
                $locked->closed_at = null;
                $locked->closed_by = null;
            }
            if ($locked->first_response_at === null) $locked->first_response_at = now();
            $locked->save();

            if ($from !== $to) $this->history($locked, $actor, $from, $to, $data['note'] ?? null);
            $this->audit->log('after_sales.updated', 'Ažuriran postprodajni slučaj '.$locked->case_number, $locked, $before, $locked->fresh()->toArray(), null, $actor);

            return $locked->fresh(['order', 'items', 'messages.user', 'attachments', 'history.actor', 'opener', 'assignee']);
        });

        $this->notifyStatus($updated, $actor);
        return $updated;
    }

    private function slaDays(string $priority): int
    {
        return match ($priority) { 'urgent' => 1, 'high' => 2, 'low' => 5, default => 3 };
    }

    private function assertTransition(string $from, string $to, User $actor): void
    {
        if ($from === $to) return;
        $allowed = [
            'open' => ['under_review', 'awaiting_customer', 'approved', 'rejected', 'closed'],
            'under_review' => ['awaiting_customer', 'approved', 'in_service', 'resolved', 'rejected', 'closed'],
            'awaiting_customer' => ['under_review', 'resolved', 'rejected', 'closed'],
            'approved' => ['in_service', 'resolved', 'closed'],
            'in_service' => ['awaiting_customer', 'resolved', 'closed'],
            'resolved' => ['closed', 'under_review'],
            'rejected' => ['closed', 'under_review'],
            'closed' => $actor->hasRole('superadmin') ? ['under_review'] : [],
        ];
        if (!in_array($to, $allowed[$from] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'Prelaz iz trenutnog u izabrani status nije dozvoljen.']);
        }
    }

    private function history(AfterSalesCase $case, User $actor, ?string $from, string $to, ?string $note): void
    {
        AfterSalesStatusHistory::query()->create([
            'after_sales_case_id' => $case->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor->id,
            'note' => filled($note) ? trim((string) $note) : null,
            'metadata_json' => ['priority' => $case->priority, 'assigned_to' => $case->assigned_to],
            'created_at' => now(),
        ]);
    }

    /** @param list<UploadedFile> $files @param list<string> $storedPaths */
    private function storeAttachments(AfterSalesCase $case, ?AfterSalesMessage $message, User $actor, array $files, array &$storedPaths): void
    {
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                throw ValidationException::withMessages(['attachments' => 'Jedan od priloga nije ispravno otpremljen.']);
            }
            $mime = (string) ($file->getMimeType() ?: $file->getClientMimeType());
            if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)) {
                throw ValidationException::withMessages(['attachments' => 'Dozvoljeni su PDF, JPG, PNG i WebP prilozi.']);
            }
            if ((int) $file->getSize() > 10 * 1024 * 1024) {
                throw ValidationException::withMessages(['attachments' => 'Svaki prilog može imati najviše 10 MB.']);
            }

            $path = $file->store('after-sales/'.$case->id, 'local');
            if (!is_string($path) || $path === '') throw ValidationException::withMessages(['attachments' => 'Prilog nije mogao biti bezbedno sačuvan.']);
            $storedPaths[] = $path;
            AfterSalesAttachment::query()->create([
                'after_sales_case_id' => $case->id,
                'message_id' => $message?->id,
                'uploaded_by' => $actor->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'mime_type' => $mime,
                'size_bytes' => (int) $file->getSize(),
            ]);
        }
    }

    private function notifyCreated(AfterSalesCase $case, User $actor): void
    {
        $recipients = collect();
        if ($case->assignee instanceof User) {
            $recipients->push($case->assignee);
        } else {
            $recipients = User::query()
                ->where('status', 'active')
                ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
                ->get();
        }

        foreach ($recipients->unique('id') as $recipient) {
            if ((int) $recipient->id === (int) $actor->id) continue;
            $this->notifications->send($recipient, [
                'event' => 'after_sales_created', 'title' => 'Novi postprodajni slučaj',
                'message' => $case->case_number.' · '.$case->subject,
                'url' => route('admin.after-sales.show', $case), 'action_label' => 'Otvori slučaj', 'icon' => 'alert', 'severity' => 'warning',
            ]);
        }
    }

    private function notifyMessage(AfterSalesCase $case, User $actor, string $visibility): void
    {
        if ($visibility === 'internal') return;
        $recipient = $actor->hasRole('admin', 'superadmin') ? $case->opener : $case->assignee;
        if ($recipient instanceof User && (int) $recipient->id !== (int) $actor->id) {
            $this->notifications->send($recipient, [
                'event' => 'after_sales_message', 'title' => 'Nova poruka u slučaju '.$case->case_number,
                'message' => $case->subject,
                'url' => $recipient->hasRole('admin', 'superadmin') ? route('admin.after-sales.show', $case) : route('after-sales.show', $case),
                'action_label' => 'Otvori komunikaciju', 'icon' => 'alert', 'severity' => 'info',
            ]);
        }
    }

    private function notifyStatus(AfterSalesCase $case, User $actor): void
    {
        if ($case->opener instanceof User && (int) $case->opener->id !== (int) $actor->id) {
            $this->notifications->send($case->opener, [
                'event' => 'after_sales_status', 'title' => 'Ažuriran slučaj '.$case->case_number,
                'message' => 'Novi status: '.$case->status,
                'url' => route('after-sales.show', $case), 'action_label' => 'Otvori slučaj', 'icon' => 'alert', 'severity' => 'info',
            ]);
        }
    }
}
