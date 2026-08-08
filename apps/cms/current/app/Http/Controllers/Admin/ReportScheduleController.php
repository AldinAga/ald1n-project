<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportScheduleRequest;
use App\Models\ReportDelivery;
use App\Models\ReportSchedule;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReportScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ReportScheduleController extends Controller
{
    public function store(StoreReportScheduleRequest $request, ReportScheduleService $service, AuditLogger $audit): RedirectResponse
    {
        $schedule = $service->create($request->user(), $request->validated());
        $audit->log('report.schedule_created', 'Kreiran raspored izveštaja '.$schedule->name.'.', $schedule, user: $request->user());
        return back()->with('status', 'Raspored izveštaja je kreiran.');
    }

    public function update(StoreReportScheduleRequest $request, ReportSchedule $schedule, ReportScheduleService $service, AuditLogger $audit): RedirectResponse
    {
        $this->authorizeSchedule($request, $schedule);
        $service->update($schedule, $request->user(), $request->validated());
        $audit->log('report.schedule_updated', 'Izmenjen raspored izveštaja '.$schedule->name.'.', $schedule, user: $request->user());
        return back()->with('status', 'Raspored izveštaja je sačuvan.');
    }

    public function toggle(Request $request, ReportSchedule $schedule, ReportScheduleService $service): RedirectResponse
    {
        $this->authorizeSchedule($request, $schedule);
        $service->toggle($schedule, $request->user());
        return back()->with('status', $schedule->fresh()->is_active ? 'Raspored je aktiviran.' : 'Raspored je pauziran.');
    }

    public function run(Request $request, ReportSchedule $schedule, ReportScheduleService $service): RedirectResponse
    {
        $this->authorizeSchedule($request, $schedule);
        $queued = $service->queueNow($schedule);
        return back()->with('status', 'U red za slanje je dodato '.$queued.' izveštaja.');
    }

    public function destroy(Request $request, ReportSchedule $schedule): RedirectResponse
    {
        $this->authorizeSchedule($request, $schedule);
        $schedule->delete();
        return back()->with('status', 'Raspored izveštaja je obrisan. Istorija slanja je sačuvana.');
    }

    public function retry(Request $request, ReportDelivery $delivery, ReportScheduleService $service): RedirectResponse
    {
        if ($delivery->schedule) $this->authorizeSchedule($request, $delivery->schedule); else abort_unless($request->user()?->hasRole('superadmin'), 404);
        $service->retry($delivery);
        return back()->with('status', 'Izveštaj je vraćen u red za slanje.');
    }

    private function authorizeSchedule(Request $request, ReportSchedule $schedule): void
    {
        /** @var User $user */ $user = $request->user();
        abort_unless($user->hasRole('superadmin') || (int) $schedule->created_by === (int) $user->id, 404);
    }
}
