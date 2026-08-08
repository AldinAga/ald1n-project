<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationRun;
use App\Models\OperationalAlert;
use App\Services\AuditLogger;
use App\Services\AutomationReadinessService;
use App\Services\OperationalAutomationService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

final class AutomationController extends Controller
{
    public function index(SettingsService $settings, AutomationReadinessService $readiness): View
    {
        $ready = $readiness->ready();
        return view('admin.settings.automation', [
            'settings' => $settings->all(),
            'ready' => $ready,
            'missing' => $ready ? [] : $readiness->missing(),
            'alerts' => $ready ? OperationalAlert::query()->with(['order', 'product'])->where('status', 'open')->orderByRaw("CASE severity WHEN 'danger' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")->latest('last_detected_at')->limit(80)->get() : collect(),
            'runs' => $ready ? AutomationRun::query()->with('trigger')->latest('started_at')->limit(20)->get() : collect(),
            'stats' => $ready ? [
                'open' => OperationalAlert::query()->where('status', 'open')->count(),
                'danger' => OperationalAlert::query()->where('status', 'open')->where('severity', 'danger')->count(),
                'warning' => OperationalAlert::query()->where('status', 'open')->where('severity', 'warning')->count(),
                'failed_runs' => AutomationRun::query()->where('status', 'failed')->where('started_at', '>=', now()->subDays(7))->count(),
            ] : ['open' => 0, 'danger' => 0, 'warning' => 0, 'failed_runs' => 0],
        ]);
    }

    public function update(Request $request, SettingsService $settings, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'automation_unaccepted_order_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'automation_alert_reminder_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'warranty_expiry_notice_days' => ['required', 'integer', 'min:1', 'max:365'],
            'warranty_maintenance_notice_days' => ['required', 'integer', 'min:1', 'max:90'],
        ]);
        $before = $settings->all();
        $values = [
            'automation_enabled' => $request->boolean('automation_enabled') ? '1' : '0',
            'automation_unaccepted_order_hours' => (string) $data['automation_unaccepted_order_hours'],
            'automation_alert_reminder_hours' => (string) $data['automation_alert_reminder_hours'],
            'automation_low_stock_enabled' => $request->boolean('automation_low_stock_enabled') ? '1' : '0',
            'automation_overdue_payment_enabled' => $request->boolean('automation_overdue_payment_enabled') ? '1' : '0',
            'automation_deadline_alerts_enabled' => $request->boolean('automation_deadline_alerts_enabled') ? '1' : '0',
            'automation_daily_digest_enabled' => $request->boolean('automation_daily_digest_enabled') ? '1' : '0',
            'automation_warranty_alerts_enabled' => $request->boolean('automation_warranty_alerts_enabled') ? '1' : '0',
            'warranty_expiry_notice_days' => (string) $data['warranty_expiry_notice_days'],
            'warranty_maintenance_notice_days' => (string) $data['warranty_maintenance_notice_days'],
        ];
        $settings->putMany($values, $request->user()->id);
        $audit->log('settings.automation_updated', 'Ažurirana podešavanja automatizacije.', null, $before, $values, user: $request->user());
        return back()->with('status', 'Podešavanja automatizacije su sačuvana.');
    }

    public function run(Request $request, OperationalAutomationService $automation, AuditLogger $audit): RedirectResponse
    {
        try {
            $run = $automation->run($request->user(), true, $request->boolean('digest'));
            $audit->log('automation.manual_run', 'Ručno pokrenuta operativna automatizacija.', $run, metadata: ['task' => $run->task, 'status' => $run->status], user: $request->user());
            return back()->with('status', 'Automatizacija je završena: '.$run->status.'.');
        } catch (Throwable $exception) {
            return back()->withErrors(['automation' => 'Automatizacija nije uspela: '.$exception->getMessage()]);
        }
    }

    public function resolve(Request $request, OperationalAlert $alert, AuditLogger $audit): RedirectResponse
    {
        $before = $alert->only(['status', 'resolved_at']);
        $alert->update(['status' => 'resolved', 'resolved_at' => now()]);
        $audit->log('automation.alert_resolved', 'Ručno zatvoreno operativno upozorenje.', $alert, $before, $alert->only(['status', 'resolved_at']), user: $request->user());
        return back()->with('status', 'Upozorenje je zatvoreno. Sledeći scan će ga ponovo otvoriti ako problem i dalje postoji.');
    }
}
