<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportScheduleRequest;
use App\Models\ReportDelivery;
use App\Models\ReportSchedule;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReportScheduleService;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

final class ReportScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->actor($request);

        if (!Schema::hasTable('report_schedules') || !Schema::hasTable('report_deliveries')) {
            return response()->json([
                'message' => 'Rasporedi izveštaja trenutno nisu dostupni.',
                'code' => 'report_schedules_unavailable',
            ], 503);
        }

        $schedules = ReportSchedule::query()
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (ReportSchedule $schedule): array => $this->schedulePayload($schedule))
            ->values()
            ->all();

        $deliveries = ReportDelivery::query()
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (ReportDelivery $delivery): array => $this->deliveryPayload($delivery))
            ->values()
            ->all();

        return response()->json([
            'data' => $schedules,
            'deliveries' => $deliveries,
            'capabilities' => [
                'create' => true,
                'update' => true,
                'toggle' => true,
                'run' => true,
                'delete' => true,
                'retry_delivery' => true,
            ],
        ]);
    }

    public function store(
        StoreReportScheduleRequest $request,
        ReportScheduleService $service,
        AuditLogger $audit,
    ): JsonResponse {
        $actor = $this->actor($request);
        $schedule = $service->create($actor, $request->validated());

        $audit->log(
            'report.schedule_created',
            'Kreiran raspored izveštaja '.$schedule->name.'.',
            $schedule,
            user: $actor,
        );

        return response()->json([
            'message' => 'Raspored izveštaja je kreiran.',
            'data' => $this->schedulePayload($schedule->fresh() ?? $schedule),
        ], 201);
    }

    public function update(
        StoreReportScheduleRequest $request,
        ReportSchedule $schedule,
        ReportScheduleService $service,
        AuditLogger $audit,
    ): JsonResponse {
        $actor = $this->actor($request);
        $updated = $service->update($schedule, $actor, $request->validated());

        $audit->log(
            'report.schedule_updated',
            'Izmenjen raspored izveštaja '.$updated->name.'.',
            $updated,
            user: $actor,
        );

        return response()->json([
            'message' => 'Raspored izveštaja je izmenjen.',
            'data' => $this->schedulePayload($updated->fresh() ?? $updated),
        ]);
    }

    public function toggle(
        Request $request,
        ReportSchedule $schedule,
        ReportScheduleService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $updated = $service->toggle($schedule, $actor);

        return response()->json([
            'message' => $updated->is_active
                ? 'Raspored izveštaja je aktiviran.'
                : 'Raspored izveštaja je pauziran.',
            'data' => $this->schedulePayload($updated->fresh() ?? $updated),
        ]);
    }

    public function run(
        Request $request,
        ReportSchedule $schedule,
        ReportScheduleService $service,
    ): JsonResponse {
        $this->actor($request);
        $queued = $service->queueNow($schedule);

        return response()->json([
            'message' => 'Ručno slanje izveštaja je stavljeno u red.',
            'data' => [
                'schedule_id' => (int) $schedule->id,
                'queued' => $queued,
            ],
        ], 202);
    }

    public function destroy(Request $request, ReportSchedule $schedule): JsonResponse
    {
        $this->actor($request);
        $id = (int) $schedule->id;
        $schedule->delete();

        return response()->json([
            'message' => 'Raspored izveštaja je obrisan.',
            'data' => ['id' => $id],
        ]);
    }

    public function retry(
        Request $request,
        ReportDelivery $delivery,
        ReportScheduleService $service,
    ): JsonResponse {
        $this->actor($request);
        $service->retry($delivery);
        $fresh = $delivery->fresh() ?? $delivery;

        return response()->json([
            'message' => 'Ponovno slanje izveštaja je zakazano.',
            'data' => $this->deliveryPayload($fresh),
        ], 202);
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('reports.manage'), 403);

        return $user;
    }

    /** @return array<string,mixed> */
    private function schedulePayload(ReportSchedule $schedule): array
    {
        return [
            'id' => (int) $schedule->id,
            'name' => (string) $schedule->name,
            'report_type' => (string) $schedule->report_type,
            'frequency' => (string) $schedule->frequency,
            'send_time' => (string) $schedule->send_time,
            'weekday' => $schedule->weekday === null ? null : (int) $schedule->weekday,
            'month_day' => $schedule->month_day === null ? null : (int) $schedule->month_day,
            'timezone' => (string) $schedule->timezone,
            'recipients' => $this->arrayValue($schedule->recipients_json),
            'filters' => $this->arrayValue($schedule->filters_json),
            'formats' => $this->arrayValue($schedule->formats_json),
            'is_active' => (bool) $schedule->is_active,
            'next_run_at' => $this->dateValue($schedule->next_run_at),
            'last_run_at' => $this->dateValue($schedule->last_run_at),
            'last_success_at' => $this->dateValue($schedule->last_success_at),
            'created_at' => $this->dateValue($schedule->created_at),
            'updated_at' => $this->dateValue($schedule->updated_at),
        ];
    }

    /** @return array<string,mixed> */
    private function deliveryPayload(ReportDelivery $delivery): array
    {
        $status = (string) $delivery->status;

        return [
            'id' => (int) $delivery->id,
            'report_schedule_id' => $delivery->report_schedule_id === null
                ? null
                : (int) $delivery->report_schedule_id,
            'report_type' => (string) $delivery->report_type,
            'recipient_email' => (string) $delivery->recipient_email,
            'recipient_name' => $delivery->recipient_name ?: null,
            'period_from' => $this->dateValue($delivery->period_from),
            'period_to' => $this->dateValue($delivery->period_to),
            'status' => $status,
            'attempt_count' => (int) $delivery->attempt_count,
            'scheduled_for' => $this->dateValue($delivery->scheduled_for),
            'last_attempt_at' => $this->dateValue($delivery->last_attempt_at),
            'sent_at' => $this->dateValue($delivery->sent_at),
            'can_retry' => in_array($status, ['failed', 'retry'], true),
            'created_at' => $this->dateValue($delivery->created_at),
            'updated_at' => $this->dateValue($delivery->updated_at),
        ];
    }

    /** @return array<mixed> */
    private function arrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        return null;
    }
}
