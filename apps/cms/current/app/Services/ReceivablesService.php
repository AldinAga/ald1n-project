<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\ReceivableCase;
use App\Models\ReceivableContact;
use App\Models\ReceivableInstallment;
use App\Models\ReceivablePaymentAllocation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class ReceivablesService
{
    /** @var list<string> */
    public const STATUSES = ['monitoring', 'contacted', 'promised', 'installment_plan', 'escalated', 'disputed', 'closed'];

    /** @var list<string> */
    public const RECEIVABLE_PAYMENT_METHODS = ['bank_transfer', 'deferred_payment'];

    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly AuditLogger $audit,
        private readonly OrderEmailOutboxService $emails,
        private readonly SettingsService $settings,
    ) {}

    public function ready(): bool
    {
        return Schema::hasTable('receivable_cases')
            && Schema::hasTable('receivable_installments')
            && Schema::hasTable('receivable_contacts');
    }

    public function ensureForOrder(Order $order, ?User $actor = null): ?ReceivableCase
    {
        if (!$this->ready() || !in_array((string) $order->payment_method, self::RECEIVABLE_PAYMENT_METHODS, true) || $order->status === 'cancelled') return null;

        $existing = ReceivableCase::query()->where('order_id', $order->id)->first();
        if ($existing instanceof ReceivableCase) {
            return $this->syncForOrder($order) ?? $existing;
        }
        if ($actor === null && $this->settings->get('receivables_auto_create_cases', '1') !== '1') return null;

        if (in_array((string) $order->payment_state, ['paid', 'overpaid', 'refunded', 'cancelled'], true)) return null;

        return DB::transaction(function () use ($order, $actor): ReceivableCase {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $existing = ReceivableCase::query()->where('order_id', $locked->id)->lockForUpdate()->first();
            if ($existing instanceof ReceivableCase) return $existing;

            $case = ReceivableCase::query()->create([
                'order_id' => $locked->id,
                'case_number' => $this->numbers->next('receivable', (int) now()->format('Y')),
                'status' => 'monitoring',
                'collection_stage' => 0,
                'assigned_to' => $locked->supplier_user_id,
                'next_action_at' => $locked->payment_due_at,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
                'metadata_json' => ['created_automatically' => $actor === null],
            ]);
            $this->audit->log('receivable.created', 'Otvoren predmet naplate '.$case->case_number, $case, after: $case->toArray(), user: $actor);
            return $case;
        }, 5);
    }

    /** @param array<string,mixed> $data */
    public function update(ReceivableCase $case, User $actor, array $data): ReceivableCase
    {
        return DB::transaction(function () use ($case, $actor, $data): ReceivableCase {
            /** @var ReceivableCase $locked */
            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
            if (!$locked->order instanceof Order) {
                throw ValidationException::withMessages(['status' => 'Porudžbina nije dostupna.']);
            }
            $remaining = $this->remaining($locked->order);
            $status = (string) ($data['status'] ?? $locked->status);
            if (!in_array($status, self::STATUSES, true)) {
                throw ValidationException::withMessages(['status' => 'Izabran je nepoznat status predmeta naplate.']);
            }
            if ($status === 'closed' && $remaining > 0.004) {
                throw ValidationException::withMessages(['status' => 'Predmet se zatvara automatski kada dug bude u potpunosti izmiren.']);
            }
            $promisedAt = filled($data['promised_payment_at'] ?? null)
                ? Carbon::parse((string) $data['promised_payment_at'])
                : null;
            if ($status === 'promised' && $promisedAt === null) {
                throw ValidationException::withMessages(['promised_payment_at' => 'Za status „Obećana uplata” unesi obećani datum plaćanja.']);
            }

            $before = $locked->toArray();
            $locked->update([
                'status' => $status,
                'assigned_to' => filled($data['assigned_to'] ?? null) ? (int) $data['assigned_to'] : $locked->assigned_to,
                'next_action_at' => filled($data['next_action_at'] ?? null) ? Carbon::parse((string) $data['next_action_at']) : ($promisedAt ?: null),
                'promised_payment_at' => $promisedAt,
                'internal_note' => trim((string) ($data['internal_note'] ?? '')) ?: null,
                'updated_by' => $actor->id,
                'closed_at' => $status === 'closed' ? now() : null,
            ]);
            $this->audit->log('receivable.updated', 'Ažuriran predmet naplate '.$locked->case_number, $locked, $before, $locked->fresh()?->toArray() ?? [], user: $actor);
            return $locked->fresh(['order.user', 'order.supplier', 'assignee', 'installments', 'contacts.user']) ?? $locked;
        }, 5);
    }

    /** @param list<array{due_at:string,amount_rsd:mixed,note?:string|null}> $installments */
    public function replacePlan(ReceivableCase $case, User $actor, array $installments): ReceivableCase
    {
        return DB::transaction(function () use ($case, $actor, $installments): ReceivableCase {
            /** @var ReceivableCase $locked */
            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
            if (!$locked->order instanceof Order) throw ValidationException::withMessages(['installments' => 'Porudžbina nije dostupna.']);
            $remaining = $this->remaining($locked->order);
            if ($remaining <= 0.004) throw ValidationException::withMessages(['installments' => 'Porudžbina nema preostalo dugovanje.']);
            if ($installments === [] || count($installments) > 24) throw ValidationException::withMessages(['installments' => 'Unesi između 1 i 24 rate.']);
            if (ReceivableInstallment::query()->where('receivable_case_id', $locked->id)->where('paid_amount_rsd', '>', 0)->exists()) {
                throw ValidationException::withMessages(['installments' => 'Plan sa već raspoređenom uplatom ne može se zameniti. Evidentiraj novi dogovor u komunikaciji.']);
            }

            $normalized = [];
            $sum = 0.0;
            $previous = null;
            foreach ($installments as $index => $row) {
                $amount = round((float) ($row['amount_rsd'] ?? 0), 2);
                try {
                    $due = Carbon::parse((string) ($row['due_at'] ?? ''))->endOfDay();
                } catch (\Throwable) {
                    throw ValidationException::withMessages(['installments' => 'Sve rate moraju imati ispravan datum dospeća.']);
                }
                if ($amount <= 0) throw ValidationException::withMessages(['installments' => 'Svaka rata mora imati iznos veći od nule.']);
                if ($due->lt(today())) throw ValidationException::withMessages(['installments' => 'Datum rate ne može biti u prošlosti.']);
                if ($previous !== null && $due->lt($previous)) throw ValidationException::withMessages(['installments' => 'Datumi rata moraju biti hronološki poređani.']);
                $previous = $due;
                $sum += $amount;
                $normalized[] = [
                    'sequence_no' => $index + 1,
                    'due_at' => $due,
                    'amount_rsd' => $amount,
                    'paid_amount_rsd' => 0,
                    'status' => 'pending',
                    'note' => trim((string) ($row['note'] ?? '')) ?: null,
                ];
            }
            if (abs(round($sum, 2) - $remaining) > 0.01) {
                throw ValidationException::withMessages(['installments' => 'Zbir rata mora biti jednak preostalom dugu '.number_format($remaining, 2, ',', '.').' RSD.']);
            }

            ReceivableInstallment::query()->where('receivable_case_id', $locked->id)->delete();
            foreach ($normalized as $row) ReceivableInstallment::query()->create(['receivable_case_id' => $locked->id] + $row);
            $metadata = (array) ($locked->metadata_json ?? []);
            $metadata['plan_paid_baseline_rsd'] = round((float) $locked->order->paid_total_rsd, 2);
            $metadata['plan_payment_high_water_id'] = (int) (OrderPayment::query()
                ->where('order_id', $locked->order->id)
                ->where('entry_type', 'payment')
                ->where('status', 'verified')
                ->max('id') ?? 0);
            $metadata['plan_created_at'] = now()->toISOString();
            $locked->update([
                'status' => 'installment_plan',
                'collection_stage' => max(1, (int) $locked->collection_stage),
                'next_action_at' => $normalized[0]['due_at'],
                'metadata_json' => $metadata,
                'updated_by' => $actor->id,
                'closed_at' => null,
            ]);
            $this->audit->log('receivable.plan_created', 'Kreiran plan otplate za '.$locked->case_number, $locked, after: ['installments' => $normalized, 'total_rsd' => $sum], user: $actor);
            return $this->syncForOrder($locked->order->fresh() ?? $locked->order) ?? $locked->fresh(['installments']) ?? $locked;
        }, 5);
    }

    /** @param array<string,mixed> $data */
    public function addContact(ReceivableCase $case, User $actor, array $data): ReceivableContact
    {
        return DB::transaction(function () use ($case, $actor, $data): ReceivableContact {
            /** @var ReceivableCase $locked */
            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
            $note = trim((string) ($data['note'] ?? ''));
            if ($note === '') throw ValidationException::withMessages(['note' => 'Unesite sadržaj komunikacije.']);
            $contact = ReceivableContact::query()->create([
                'receivable_case_id' => $locked->id,
                'user_id' => $actor->id,
                'channel' => (string) ($data['channel'] ?? 'internal'),
                'direction' => (string) ($data['direction'] ?? 'internal'),
                'subject' => trim((string) ($data['subject'] ?? '')) ?: null,
                'note' => $note,
                'visible_to_customer' => (bool) ($data['visible_to_customer'] ?? false),
                'is_automatic' => false,
                'contacted_at' => filled($data['contacted_at'] ?? null) ? Carbon::parse((string) $data['contacted_at']) : now(),
            ]);
            $locked->update([
                'status' => $locked->status === 'monitoring' ? 'contacted' : $locked->status,
                'last_contact_at' => $contact->contacted_at,
                'collection_stage' => max(1, (int) $locked->collection_stage),
                'updated_by' => $actor->id,
            ]);
            if ($contact->visible_to_customer && $locked->order instanceof Order) {
                $this->emails->receivableMessage($locked->order, $locked, $contact->subject ?: 'Obaveštenje o plaćanju', $contact->note, 'manual-'.$contact->id);
            }
            $this->audit->log('receivable.contact_recorded', 'Evidentiran kontakt za '.$locked->case_number, $contact, after: $contact->toArray(), user: $actor);
            return $contact->fresh('user') ?? $contact;
        }, 5);
    }

    public function sendReminder(ReceivableCase $case, User $actor, ?string $customMessage = null): ReceivableCase
    {
        return DB::transaction(function () use ($case, $actor, $customMessage): ReceivableCase {
            /** @var ReceivableCase $locked */
            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
            if (!$locked->order instanceof Order) throw ValidationException::withMessages(['reminder' => 'Porudžbina nije dostupna.']);
            $remaining = $this->remaining($locked->order);
            if ($remaining <= 0.004) throw ValidationException::withMessages(['reminder' => 'Potraživanje je već izmireno.']);
            $days = $this->daysOverdue($locked->order);
            $message = trim((string) $customMessage);
            if ($message === '') $message = $this->defaultReminderMessage($locked->order, $remaining, $days);
            $this->emails->receivableReminder(
                $locked->order,
                $locked,
                max(0, $days),
                'Podsetnik za uplatu · '.$locked->order->order_number,
                $message,
            );
            ReceivableContact::query()->create([
                'receivable_case_id' => $locked->id,
                'user_id' => $actor->id,
                'channel' => 'email',
                'direction' => 'outbound',
                'subject' => 'Podsetnik za uplatu',
                'note' => $message,
                'visible_to_customer' => true,
                'is_automatic' => false,
                'contacted_at' => now(),
            ]);
            $locked->update([
                'status' => $locked->status === 'monitoring' ? 'contacted' : $locked->status,
                'collection_stage' => max((int) $locked->collection_stage, 1),
                'last_contact_at' => now(),
                'last_reminder_at' => now(),
                'updated_by' => $actor->id,
            ]);
            $this->audit->log('receivable.reminder_queued', 'Pripremljen podsetnik za '.$locked->case_number, $locked, after: ['remaining_rsd' => $remaining], user: $actor);
            return $locked->fresh(['order', 'installments', 'contacts.user', 'assignee']) ?? $locked;
        }, 5);
    }

    /** @return array{examined:int,cases_created:int,reminders:int,closed:int,skipped_promises:int} */
    public function runAutomation(bool $force = false): array
    {
        $result = ['examined' => 0, 'cases_created' => 0, 'reminders' => 0, 'closed' => 0, 'skipped_promises' => 0];
        if (!$this->ready() || $this->settings->get('receivables_enabled', '1') !== '1') return $result;

        $orders = Order::query()
            ->with(['user.role', 'supplier.role'])
            ->whereIn('payment_method', self::RECEIVABLE_PAYMENT_METHODS)
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('payment_due_at')
            ->whereNotIn('payment_state', ['cancelled', 'refunded'])
            ->orderBy('id')
            ->limit(1500)
            ->get();

        foreach ($orders as $order) {
            $result['examined']++;
            $existingId = ReceivableCase::query()->where('order_id', $order->id)->value('id');
            $case = $this->ensureForOrder($order);
            if (!$case instanceof ReceivableCase) continue;
            if ($existingId === null) $result['cases_created']++;
            $case = $this->syncForOrder($order->fresh() ?? $order) ?? $case;
            if ($case->status === 'closed') {
                $result['closed']++;
                continue;
            }
            if ($this->settings->get('receivables_auto_reminders_enabled', '1') !== '1') continue;
            if (!$force && $this->settings->get('receivables_pause_on_promise', '1') === '1' && $case->promised_payment_at?->isFuture()) {
                $result['skipped_promises']++;
                continue;
            }
            $stage = $this->eligibleReminderStage($order);
            if ($stage === null) continue;
            if ($this->queueAutomaticReminder($case, $order, $stage, $force)) $result['reminders']++;
        }

        return $result;
    }

    public function syncForOrder(Order $order): ?ReceivableCase
    {
        if (!$this->ready()) return null;
        $case = ReceivableCase::query()->where('order_id', $order->id)->first();
        if (!$case instanceof ReceivableCase) return null;
        $metadata = (array) ($case->metadata_json ?? []);
        if (Schema::hasTable('receivable_payment_allocations') && array_key_exists('plan_payment_high_water_id', $metadata)) {
            return $this->syncForOrderWithPaymentLedger($order, $case);
        }

        return DB::transaction(function () use ($case, $order): ReceivableCase {
            /** @var ReceivableCase $locked */
            $locked = ReceivableCase::query()->lockForUpdate()->findOrFail($case->id);
            /** @var Order $freshOrder */
            $freshOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $paid = round(max(0, (float) $freshOrder->paid_total_rsd), 2);
            $metadata = (array) ($locked->metadata_json ?? []);
            $baseline = round((float) ($metadata['plan_paid_baseline_rsd'] ?? 0), 2);
            $allocatable = max(0, $paid - $baseline);

            $installments = ReceivableInstallment::query()->where('receivable_case_id', $locked->id)->orderBy('sequence_no')->lockForUpdate()->get();
            foreach ($installments as $installment) {
                $allocated = min((float) $installment->amount_rsd, $allocatable);
                $allocatable = max(0, $allocatable - $allocated);
                $isPaid = $allocated + 0.004 >= (float) $installment->amount_rsd;
                $status = $isPaid ? 'paid' : ($installment->due_at?->isPast() ? 'overdue' : 'pending');
                $installment->update([
                    'paid_amount_rsd' => round($allocated, 2),
                    'status' => $status,
                    'paid_at' => $isPaid ? ($installment->paid_at ?: now()) : null,
                ]);
            }

            $remaining = $this->remaining($freshOrder);
            if ($remaining <= 0.004 || in_array((string) $freshOrder->payment_state, ['paid', 'overpaid'], true)) {
                $locked->update(['status' => 'closed', 'closed_at' => $locked->closed_at ?: now(), 'next_action_at' => null, 'promised_payment_at' => null]);
            } else {
                $nextInstallment = $installments->first(static fn (ReceivableInstallment $item): bool => $item->status !== 'paid');
                $nextAction = $locked->promised_payment_at ?: $nextInstallment?->due_at ?: $freshOrder->payment_due_at;
                $updates = ['next_action_at' => $nextAction, 'closed_at' => null];
                if ($locked->status === 'closed') $updates['status'] = $installments->isNotEmpty() ? 'installment_plan' : 'monitoring';
                $locked->update($updates);
            }
            return $locked->fresh(['order.user', 'order.supplier', 'installments', 'contacts.user', 'assignee']) ?? $locked;
        }, 5);
    }

    private function syncForOrderWithPaymentLedger(Order $order, ReceivableCase $case): ReceivableCase
    {
        return DB::transaction(function () use ($order, $case): ReceivableCase {
            /** @var ReceivableCase $locked */
            $locked = ReceivableCase::query()->lockForUpdate()->findOrFail($case->id);
            /** @var Order $freshOrder */
            $freshOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $metadata = (array) ($locked->metadata_json ?? []);
            $highWater = max(0, (int) ($metadata['plan_payment_high_water_id'] ?? 0));
            $planCreatedAt = Carbon::parse((string) ($metadata['plan_created_at'] ?? now()->toISOString()));
            $baseline = round((float) ($metadata['plan_paid_baseline_rsd'] ?? 0), 2);
            $allocatable = round(max(0, (float) $freshOrder->paid_total_rsd - $baseline), 2);

            $installments = ReceivableInstallment::query()
                ->where('receivable_case_id', $locked->id)
                ->orderBy('sequence_no')
                ->lockForUpdate()
                ->get();
            ReceivablePaymentAllocation::query()->where('receivable_case_id', $locked->id)->delete();
            foreach ($installments as $installment) {
                $installment->update(['paid_amount_rsd' => 0, 'paid_at' => null]);
            }

            $payments = OrderPayment::query()
                ->where('order_id', $freshOrder->id)
                ->where('entry_type', 'payment')
                ->where('status', 'verified')
                ->where(function ($query) use ($highWater, $planCreatedAt): void {
                    $query->where('id', '>', $highWater)->orWhere('verified_at', '>', $planCreatedAt);
                })
                ->orderBy('paid_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $remainingAllocatable = $allocatable;
            foreach ($payments as $payment) {
                if ($remainingAllocatable <= 0.004) break;
                $paymentRemaining = min(round((float) $payment->amount_rsd, 2), $remainingAllocatable);
                foreach ($installments as $installment) {
                    if ($paymentRemaining <= 0.004) break;
                    $already = (float) $installment->paid_amount_rsd;
                    $open = round(max(0, (float) $installment->amount_rsd - $already), 2);
                    if ($open <= 0.004) continue;
                    $amount = round(min($open, $paymentRemaining), 2);
                    if ($amount <= 0) continue;
                    ReceivablePaymentAllocation::query()->create([
                        'receivable_case_id' => $locked->id,
                        'receivable_installment_id' => $installment->id,
                        'order_payment_id' => $payment->id,
                        'amount_rsd' => $amount,
                    ]);
                    $newPaid = round($already + $amount, 2);
                    $paidInFull = $newPaid + 0.004 >= (float) $installment->amount_rsd;
                    $installment->update([
                        'paid_amount_rsd' => $newPaid,
                        'paid_at' => $paidInFull ? $payment->paid_at : null,
                    ]);
                    $installment->refresh();
                    $paymentRemaining = round(max(0, $paymentRemaining - $amount), 2);
                    $remainingAllocatable = round(max(0, $remainingAllocatable - $amount), 2);
                }
            }

            foreach ($installments as $installment) {
                $installment->refresh();
                $isPaid = (float) $installment->paid_amount_rsd + 0.004 >= (float) $installment->amount_rsd;
                $status = $isPaid ? 'paid' : ($installment->due_at?->isPast() ? 'overdue' : 'pending');
                $installment->update(['status' => $status, 'paid_at' => $isPaid ? $installment->paid_at : null]);
            }

            $remaining = $this->remaining($freshOrder);
            if ($remaining <= 0.004 || in_array((string) $freshOrder->payment_state, ['paid', 'overpaid'], true)) {
                $locked->update(['status' => 'closed', 'closed_at' => $locked->closed_at ?: now(), 'next_action_at' => null, 'promised_payment_at' => null]);
            } else {
                $installments->each->refresh();
                $nextInstallment = $installments->first(static fn (ReceivableInstallment $item): bool => $item->status !== 'paid');
                $nextAction = $locked->promised_payment_at ?: $nextInstallment?->due_at ?: $freshOrder->payment_due_at;
                $updates = ['next_action_at' => $nextAction, 'closed_at' => null];
                if ($locked->status === 'closed') $updates['status'] = $installments->isNotEmpty() ? 'installment_plan' : 'monitoring';
                $locked->update($updates);
            }
            return $locked->fresh(['order.user', 'order.supplier', 'installments', 'contacts.user', 'assignee']) ?? $locked;
        }, 5);
    }
    public function remaining(Order $order): float
    {
        return round(max(0, (float) $order->subtotal_rsd - (float) $order->paid_total_rsd), 2);
    }

    public function daysOverdue(Order $order): int
    {
        if ($order->payment_due_at === null || !$order->payment_due_at->copy()->startOfDay()->lt(today())) return 0;
        return (int) $order->payment_due_at->copy()->startOfDay()->diffInDays(today());
    }

    public function agingBucket(Order $order): string
    {
        if ($order->payment_due_at === null || $order->payment_due_at->copy()->startOfDay()->gte(today())) return 'current';
        $days = $this->daysOverdue($order);
        return match (true) {
            $days <= 7 => '1_7',
            $days <= 15 => '8_15',
            $days <= 30 => '16_30',
            $days <= 60 => '31_60',
            $days <= 90 => '61_90',
            default => '90_plus',
        };
    }

    private function eligibleReminderStage(Order $order): ?int
    {
        if ($order->payment_due_at === null) return null;
        $due = $order->payment_due_at->copy()->startOfDay();
        $today = today();
        $dueSoon = max(0, min(60, (int) $this->settings->get('receivables_due_soon_days', '3')));
        if ($due->gt($today)) {
            $daysUntil = (int) $today->diffInDays($due);
            return $dueSoon > 0 && $daysUntil <= $dueSoon ? -$dueSoon : null;
        }
        if ($due->equalTo($today)) return 0;

        $days = (int) $due->diffInDays($today);
        $stages = $this->reminderStages();
        $eligible = null;
        foreach ($stages as $stage) {
            if ($stage <= $days) $eligible = $stage;
        }
        return $eligible;
    }

    /** @return list<int> */
    private function reminderStages(): array
    {
        $raw = preg_split('/[\s,;]+/', (string) $this->settings->get('receivables_reminder_stages', '0,3,7,15,30')) ?: [];
        $stages = [];
        foreach ($raw as $value) {
            if ($value === '' || !is_numeric($value)) continue;
            $stage = max(0, min(3650, (int) $value));
            $stages[$stage] = $stage;
        }
        if ($stages === []) $stages = [0 => 0, 3 => 3, 7 => 7, 15 => 15, 30 => 30];
        ksort($stages);
        return array_values($stages);
    }

    private function queueAutomaticReminder(ReceivableCase $case, Order $order, int $stage, bool $force): bool
    {
        return DB::transaction(function () use ($case, $order, $stage, $force): bool {
            /** @var ReceivableCase $locked */
            $locked = ReceivableCase::query()->lockForUpdate()->findOrFail($case->id);
            /** @var Order $freshOrder */
            $freshOrder = Order::query()->with(['user.role', 'supplier.role'])->lockForUpdate()->findOrFail($order->id);
            if ($this->remaining($freshOrder) <= 0.004) return false;
            $dueKey = $freshOrder->payment_due_at?->format('Ymd') ?? 'none';
            $eventKey = 'receivable-auto:'.$locked->id.':'.$dueKey.':'.$stage;
            if (!$force && ReceivableContact::query()->where('event_key', $eventKey)->exists()) return false;

            $remaining = $this->remaining($freshOrder);
            $days = $this->daysOverdue($freshOrder);
            $subject = $stage < 0
                ? 'Podsetnik: približava se rok plaćanja · '.$freshOrder->order_number
                : ($stage === 0 ? 'Danas dospeva plaćanje · '.$freshOrder->order_number : 'Opomena za dospelo plaćanje · '.$freshOrder->order_number);
            $message = $this->defaultReminderMessage($freshOrder, $remaining, $days);
            $queued = $this->emails->receivableReminder($freshOrder, $locked, $stage, $subject, $message);
            if ($queued <= 0) return false;

            $contact = ReceivableContact::query()->firstOrCreate(
                ['event_key' => $eventKey],
                [
                    'receivable_case_id' => $locked->id,
                    'channel' => 'email',
                    'direction' => 'outbound',
                    'subject' => $subject,
                    'note' => $message,
                    'visible_to_customer' => true,
                    'is_automatic' => true,
                    'contacted_at' => now(),
                ],
            );
            $metadata = (array) ($locked->metadata_json ?? []);
            $metadata['last_reminder_due_date'] = $freshOrder->payment_due_at?->toDateString();
            $metadata['last_reminder_event_key'] = $eventKey;
            $locked->update([
                'status' => $stage >= 15 ? 'escalated' : ($stage >= 0 && $locked->status === 'monitoring' ? 'contacted' : $locked->status),
                'collection_stage' => max((int) $locked->collection_stage, $this->stageOrdinal($stage)),
                'last_contact_at' => $contact->contacted_at,
                'last_reminder_stage' => $stage,
                'last_reminder_at' => now(),
                'next_action_at' => $this->nextReminderAt($freshOrder, $stage),
                'metadata_json' => $metadata,
            ]);
            $this->audit->log('receivable.automatic_reminder_queued', 'Automatska opomena za '.$locked->case_number, $locked, after: ['stage' => $stage, 'remaining_rsd' => $remaining, 'queued_recipients' => $queued]);
            return true;
        }, 5);
    }

    private function stageOrdinal(int $stage): int
    {
        if ($stage < 0) return 0;
        $ordinal = 1;
        foreach ($this->reminderStages() as $configured) {
            if ($configured <= $stage) $ordinal++;
        }
        return min(255, $ordinal);
    }

    private function nextReminderAt(Order $order, int $stage): ?Carbon
    {
        if ($order->payment_due_at === null) return null;
        $due = $order->payment_due_at->copy()->endOfDay();
        if ($stage < 0) return $due;
        foreach ($this->reminderStages() as $configured) {
            if ($configured > $stage) return $due->copy()->addDays($configured);
        }
        return $due->copy()->addDays(max(1, $stage + 30));
    }

    private function defaultReminderMessage(Order $order, float $remaining, int $daysOverdue): string
    {
        $due = $order->payment_due_at?->format('d.m.Y') ?? 'nije definisan';
        if ($order->payment_due_at?->isFuture()) {
            return sprintf(
                'Podsećamo da za porudžbinu %s preostaje za uplatu %s RSD. Rok plaćanja je %s.',
                $order->order_number,
                number_format($remaining, 2, ',', '.'),
                $due,
            );
        }
        if ($daysOverdue <= 0) {
            return sprintf('Za porudžbinu %s danas dospeva iznos od %s RSD.', $order->order_number, number_format($remaining, 2, ',', '.'));
        }
        return sprintf(
            'Za porudžbinu %s preostaje dospelo dugovanje od %s RSD. Rok plaćanja je bio %s, pre %d dana.',
            $order->order_number,
            number_format($remaining, 2, ',', '.'),
            $due,
            $daysOverdue,
        );
    }
}
