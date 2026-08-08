<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReportDelivery;
use App\Models\ReportSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

final class ReportScheduleService
{
    public function __construct(private readonly ManagementReportService $reports) {}

    /** @param array<string,mixed> $data */
    public function create(User $actor, array $data): ReportSchedule
    {
        $schedule = ReportSchedule::query()->create($this->payload($data) + ['created_by' => $actor->id, 'updated_by' => $actor->id]);
        $schedule->forceFill(['next_run_at' => $this->nextRunAt($schedule)])->save();
        return $schedule;
    }

    /** @param array<string,mixed> $data */
    public function update(ReportSchedule $schedule, User $actor, array $data): ReportSchedule
    {
        $schedule->fill($this->payload($data) + ['updated_by' => $actor->id]);
        $schedule->next_run_at = $schedule->is_active ? $this->nextRunAt($schedule) : null;
        $schedule->save();
        return $schedule;
    }

    public function toggle(ReportSchedule $schedule, User $actor): ReportSchedule
    {
        $schedule->forceFill([
            'is_active' => !$schedule->is_active,
            'next_run_at' => !$schedule->is_active ? $this->nextRunAt($schedule) : null,
            'updated_by' => $actor->id,
        ])->save();
        return $schedule;
    }

    /** @return array{queued:int,schedules:int} */
    public function enqueueDue(int $limit = 50): array
    {
        if (!Schema::hasTable('report_schedules') || !Schema::hasTable('report_deliveries')) return ['queued' => 0, 'schedules' => 0];
        $schedules = ReportSchedule::query()->where('is_active', true)
            ->where(static fn ($q) => $q->whereNull('next_run_at')->orWhere('next_run_at', '<=', now()))
            ->orderBy('next_run_at')->limit(max(1, min(200, $limit)))->get();
        $queued = 0;
        foreach ($schedules as $schedule) {
            $queued += DB::transaction(function () use ($schedule): int {
                $locked = ReportSchedule::query()->whereKey($schedule->id)->where('is_active', true)->lockForUpdate()->first();
                if (!$locked instanceof ReportSchedule || ($locked->next_run_at && $locked->next_run_at->isFuture())) return 0;
                [$from, $to] = $this->periodFor($locked);
                $count = $this->queue($locked, $from, $to, false);
                $locked->forceFill(['last_run_at' => now(), 'next_run_at' => $this->nextRunAt($locked, CarbonImmutable::now())])->save();
                return $count;
            }, 5);
        }
        return ['queued' => $queued, 'schedules' => $schedules->count()];
    }

    public function queueNow(ReportSchedule $schedule): int
    {
        $today = CarbonImmutable::today($schedule->timezone ?: config('app.timezone'));
        $filters = (array) ($schedule->filters_json ?? []);
        $from = isset($filters['date_from']) ? CarbonImmutable::parse((string) $filters['date_from']) : $today->startOfMonth();
        $to = isset($filters['date_to']) ? CarbonImmutable::parse((string) $filters['date_to']) : $today;
        return $this->queue($schedule, $from, $to, true);
    }

    /** @return array{sent:int,failed:int,queued:int} */
    public function dispatch(int $limit = 50): array
    {
        $queued = $this->enqueueDue($limit)['queued'];
        if (!Schema::hasTable('report_deliveries')) return ['sent' => 0, 'failed' => 0, 'queued' => $queued];
        ReportDelivery::query()->where('status', 'sending')->where('last_attempt_at', '<=', now()->subMinutes(20))
            ->update(['status' => 'retry', 'scheduled_for' => now(), 'updated_at' => now()]);
        $rows = ReportDelivery::query()->with(['schedule.creator'])
            ->whereIn('status', ['pending', 'retry'])
            ->where(static fn ($q) => $q->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', now()))
            ->orderBy('scheduled_for')->orderBy('id')->limit(max(1, min(200, $limit)))->get();
        $sent = 0; $failed = 0;
        foreach ($rows as $row) {
            $claimed = DB::transaction(function () use ($row): ?ReportDelivery {
                $locked = ReportDelivery::query()->whereKey($row->id)->whereIn('status', ['pending', 'retry'])->lockForUpdate()->first();
                if (!$locked) return null;
                $locked->forceFill(['status' => 'sending', 'last_attempt_at' => now()])->save();
                return $locked->load(['schedule.creator']);
            }, 5);
            if (!$claimed) continue;
            try {
                $attachments = $this->send($claimed);
                $claimed->forceFill(['status' => 'sent', 'sent_at' => now(), 'last_error' => null, 'attachment_names_json' => $attachments])->save();
                if ($claimed->schedule) $claimed->schedule->forceFill(['last_success_at' => now()])->save();
                $sent++;
            } catch (Throwable $exception) {
                $attempt = (int) $claimed->attempt_count + 1;
                $status = $attempt >= 5 ? 'failed' : 'retry';
                $claimed->forceFill([
                    'status' => $status, 'attempt_count' => $attempt,
                    'scheduled_for' => $status === 'retry' ? now()->addMinutes(min(240, 5 * (2 ** min(5, $attempt - 1)))) : null,
                    'last_error' => $this->truncate($exception->getMessage(), 4000),
                ])->save();
                Log::error('Management report delivery failed.', ['delivery_id' => $claimed->id, 'exception' => $exception]);
                $failed++;
            }
        }
        return compact('sent', 'failed', 'queued');
    }

    public function retry(ReportDelivery $delivery): void
    {
        $delivery->forceFill(['status' => 'retry', 'scheduled_for' => now(), 'last_error' => null])->save();
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function payload(array $data): array
    {
        return [
            'name' => trim((string) $data['name']), 'report_type' => (string) ($data['report_type'] ?? 'management_summary'),
            'frequency' => (string) $data['frequency'], 'send_time' => (string) $data['send_time'],
            'weekday' => ($data['frequency'] ?? '') === 'weekly' ? (int) ($data['weekday'] ?? 1) : null,
            'month_day' => ($data['frequency'] ?? '') === 'monthly' ? (int) ($data['month_day'] ?? 1) : null,
            'timezone' => (string) ($data['timezone'] ?? 'Europe/Belgrade'),
            'recipients_json' => $this->recipients((string) ($data['recipients'] ?? '')),
            'formats_json' => array_values(array_intersect(['pdf', 'csv'], (array) ($data['formats'] ?? ['pdf']))),
            'filters_json' => $this->reports->normalizeFilters((array) ($data['filters'] ?? [])),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }

    /** @return list<array{email:string,name:?string}> */
    private function recipients(string $value): array
    {
        $rows = preg_split('/[\r\n,;]+/', $value) ?: [];
        $result = [];
        foreach ($rows as $row) {
            $email = mb_strtolower(trim($row));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
            $result[$email] = ['email' => $email, 'name' => null];
        }
        return array_values($result);
    }

    private function nextRunAt(ReportSchedule $schedule, ?CarbonImmutable $from = null): CarbonImmutable
    {
        $timezone = $schedule->timezone ?: config('app.timezone', 'Europe/Belgrade');
        $from = ($from ?: CarbonImmutable::now($timezone))->setTimezone($timezone);
        [$hour, $minute] = array_map('intval', explode(':', $schedule->send_time ?: '08:00'));
        $candidate = $from->setTime($hour, $minute);
        if ($schedule->frequency === 'daily') {
            if ($candidate->lessThanOrEqualTo($from)) $candidate = $candidate->addDay();
        } elseif ($schedule->frequency === 'weekly') {
            $weekday = max(1, min(7, (int) ($schedule->weekday ?: 1)));
            $delta = ($weekday - $candidate->isoWeekday() + 7) % 7;
            $candidate = $candidate->addDays($delta);
            if ($candidate->lessThanOrEqualTo($from)) $candidate = $candidate->addWeek();
        } else {
            $day = max(1, min(28, (int) ($schedule->month_day ?: 1)));
            $candidate = $from->startOfMonth()->day($day)->setTime($hour, $minute);
            if ($candidate->lessThanOrEqualTo($from)) $candidate = $candidate->addMonthNoOverflow()->startOfMonth()->day($day)->setTime($hour, $minute);
        }
        return $candidate->setTimezone(config('app.timezone', 'Europe/Belgrade'));
    }

    /** @return array{CarbonImmutable,CarbonImmutable} */
    private function periodFor(ReportSchedule $schedule): array
    {
        $timezone = $schedule->timezone ?: config('app.timezone', 'Europe/Belgrade');
        $today = CarbonImmutable::today($timezone);
        if ($schedule->frequency === 'daily') return [$today->subDay(), $today->subDay()];
        if ($schedule->frequency === 'weekly') return [$today->subDays(7), $today->subDay()];
        $previous = $today->subMonthNoOverflow();
        return [$previous->startOfMonth(), $previous->endOfMonth()];
    }

    private function queue(ReportSchedule $schedule, CarbonImmutable $from, CarbonImmutable $to, bool $manual): int
    {
        $count = 0;
        $filters = array_replace((array) ($schedule->filters_json ?? []), ['date_from' => $from->format('Y-m-d'), 'date_to' => $to->format('Y-m-d')]);
        $formats = array_values(array_intersect(['pdf', 'csv'], (array) ($schedule->formats_json ?? ['pdf'])));
        if ($formats === []) $formats = ['pdf'];
        foreach ((array) ($schedule->recipients_json ?? []) as $recipient) {
            $email = is_array($recipient) ? (string) ($recipient['email'] ?? '') : (string) $recipient;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
            $seed = implode('|', [$schedule->id, strtolower($email), $from->format('Y-m-d'), $to->format('Y-m-d'), $schedule->report_type, $manual ? now()->format('YmdHisv') : 'scheduled']);
            $delivery = ReportDelivery::query()->firstOrCreate(['dedupe_key' => hash('sha256', $seed)], [
                'report_schedule_id' => $schedule->id, 'report_type' => $schedule->report_type,
                'recipient_email' => strtolower($email), 'recipient_name' => is_array($recipient) ? ($recipient['name'] ?? null) : null,
                'filters_json' => $filters, 'formats_json' => $formats, 'period_from' => $from->format('Y-m-d'),
                'period_to' => $to->format('Y-m-d'), 'status' => 'pending', 'scheduled_for' => now(),
            ]);
            if ($delivery->wasRecentlyCreated) $count++;
        }
        return $count;
    }

    /** @return list<string> */
    private function send(ReportDelivery $delivery): array
    {
        $actor = $delivery->schedule?->creator;
        if (!$actor instanceof User || !$actor->hasPermission('reports.view')) {
            $actor = User::query()->where('status', 'active')->whereHas('role', static fn ($q) => $q->where('slug', 'superadmin'))->orderBy('id')->first();
        }
        if (!$actor instanceof User) throw new RuntimeException('Nema aktivnog korisnika sa pravom pristupa izveštajima.');
        $filters = (array) ($delivery->filters_json ?? []);
        $formats = (array) ($delivery->formats_json ?? ['pdf']);
        $attachments = [];
        if (in_array('pdf', $formats, true)) $attachments[] = ['name' => 'upravljački-izveštaj-'.$delivery->period_from?->format('Ymd').'-'.$delivery->period_to?->format('Ymd').'.pdf', 'mime' => 'application/pdf', 'data' => $this->reports->pdf($actor, $filters, $delivery->report_type)];
        if (in_array('csv', $formats, true)) $attachments[] = ['name' => 'upravljački-izveštaj-'.$delivery->period_from?->format('Ymd').'-'.$delivery->period_to?->format('Ymd').'.csv', 'mime' => 'text/csv', 'data' => $this->reports->csv($actor, $filters, $delivery->report_type)];
        $summary = $this->reports->summary($actor, $this->reports->normalizeFilters($filters));
        Mail::send('emails.management-report', ['delivery' => $delivery, 'summary' => $summary], function ($mail) use ($delivery, $attachments): void {
            $mail->to($delivery->recipient_email, $delivery->recipient_name)->subject('Upravljački izveštaj · '.$delivery->period_from?->format('d.m.Y').' – '.$delivery->period_to?->format('d.m.Y'));
            foreach ($attachments as $attachment) $mail->attachData($attachment['data'], $attachment['name'], ['mime' => $attachment['mime']]);
        });
        return array_column($attachments, 'name');
    }

    private function truncate(string $value, int $length): string
    {
        return function_exists('mb_substr') ? (string) mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
    }
}
