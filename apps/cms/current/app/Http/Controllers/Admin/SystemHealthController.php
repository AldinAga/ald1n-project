<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupRun;
use App\Models\SecurityEvent;
use App\Models\SystemHealthSnapshot;
use App\Services\AuditLogger;
use App\Services\BackupService;
use App\Services\SystemHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

final class SystemHealthController extends Controller
{
    public function index(SystemHealthService $health): View
    {
        $report = $health->inspect();
        return view('admin.settings.system-health', [
            'report' => $report,
            'snapshots' => Schema::hasTable('system_health_snapshots')
                ? SystemHealthSnapshot::query()->with('checker')->latest('checked_at')->limit(12)->get()
                : collect(),
            'backups' => Schema::hasTable('backup_runs')
                ? BackupRun::query()->with('creator')->latest('started_at')->limit(20)->get()
                : collect(),
            'securityEvents' => Schema::hasTable('security_events')
                ? SecurityEvent::query()->with('user')->latest('created_at')->limit(15)->get()
                : collect(),
        ]);
    }

    public function run(Request $request, SystemHealthService $health, AuditLogger $audit): RedirectResponse
    {
        $report = $health->inspect();
        $health->snapshot($report, $request->user()?->id);
        $audit->log('system.health_checked', 'Ručno pokrenuta System Health provera.', null, metadata: ['status' => $report['status']], user: $request->user());
        return back()->with('status', 'System Health provera je završena: '.strtoupper($report['status']).'.');
    }

    public function backup(Request $request, BackupService $backup, AuditLogger $audit): RedirectResponse
    {
        try {
            $result = $backup->create('manual', $request->user()?->id, $request->boolean('database_only'));
            $audit->log('backup.created', 'Kreiran ručni bezbednosni backup.', $result['run'], metadata: ['path' => $result['path'], 'size' => $result['size']], user: $request->user());
            return back()->with('status', 'Backup je uspešno kreiran ('.number_format($result['size'] / 1048576, 2, ',', '.').' MB).');
        } catch (Throwable $exception) {
            $audit->log('backup.failed', 'Ručni backup nije uspeo.', null, metadata: ['error' => $exception->getMessage()], user: $request->user(), level: 'error');
            return back()->withErrors(['backup' => 'Backup nije uspeo: '.$exception->getMessage()]);
        }
    }

    public function prune(Request $request, BackupService $backup, AuditLogger $audit): RedirectResponse
    {
        $result = $backup->prune();
        $audit->log('backup.pruned', 'Primena backup retention pravila.', null, metadata: $result, user: $request->user());
        return back()->with('status', 'Backup retention je primenjen. Uklonjeno: '.$result['removed'].'.');
    }
}
