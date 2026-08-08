<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LegacyAuditLog;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

final class AuditLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $source = in_array($request->query('source'), ['legacy', 'security'], true) ? (string) $request->query('source') : 'current';
        $query = $this->query($request, $source);
        return view('admin.audit.index', [
            'logs' => $query->paginate(40)->withQueryString(),
            'source' => $source,
            'users' => User::query()->orderBy('first_name')->orderBy('last_name')->get(['id', 'username', 'first_name', 'last_name']),
            'securityReady' => Schema::hasTable('security_events'),
        ]);
    }

    public function csv(Request $request): Response
    {
        $source = in_array($request->query('source'), ['legacy', 'security'], true) ? (string) $request->query('source') : 'current';
        $rows = $this->query($request, $source)->limit(10000)->get();
        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['Izvor', 'Datum', 'Nivo', 'Akcija/događaj', 'Korisnik', 'Predmet/ruta', 'IP', 'Request ID', 'Detalji'], ';');
        foreach ($rows as $row) {
            if ($source === 'security') {
                fputcsv($handle, [$source, $row->created_at?->format('Y-m-d H:i:s'), $row->severity, $row->event_type, $row->user?->displayName() ?? 'Gost/Sistem', $row->route_name, $row->ip_address, $row->request_id, json_encode($row->context_json, JSON_UNESCAPED_UNICODE)], ';');
            } elseif ($source === 'legacy') {
                fputcsv($handle, [$source, $row->created_at?->format('Y-m-d H:i:s'), 'info', $row->action, $row->user?->displayName() ?? 'Sistem', ($row->entity_type ?: 'Sistem').' #'.($row->entity_id ?: ''), '', '', json_encode(['before' => $row->old_values, 'after' => $row->new_values], JSON_UNESCAPED_UNICODE)], ';');
            } else {
                fputcsv($handle, [$source, $row->created_at?->format('Y-m-d H:i:s'), $row->level, $row->action, $row->user?->displayName() ?? 'Sistem', $row->subject, $row->ip_address, $row->request_id, json_encode(['before' => $row->before_json, 'after' => $row->after_json, 'metadata' => $row->metadata_json], JSON_UNESCAPED_UNICODE)], ';');
            }
        }
        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);
        return response("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="audit-'.now()->format('Ymd-His').'.csv"',
        ]);
    }

    /** @return Builder<AuditLog|LegacyAuditLog|SecurityEvent> */
    private function query(Request $request, string $source): Builder
    {
        if ($source === 'security') {
            $query = SecurityEvent::query()->with('user:id,username,first_name,last_name')->orderByDesc('id');
            if ($request->filled('action')) $query->where('event_type', 'like', '%'.$request->string('action').'%');
            if ($request->filled('level')) $query->where('severity', $request->string('level'));
        } elseif ($source === 'legacy') {
            $query = LegacyAuditLog::query()->with('user:id,username,first_name,last_name')->orderByDesc('id');
            if ($request->filled('action')) $query->where('action', 'like', '%'.$request->string('action').'%');
        } else {
            $query = AuditLog::query()->with('user:id,username,first_name,last_name')->orderByDesc('id');
            if ($request->filled('action')) $query->where(function (Builder $builder) use ($request): void {
                $term = '%'.$request->string('action').'%';
                $builder->where('action', 'like', $term)->orWhere('subject', 'like', $term);
            });
            if ($request->filled('level')) $query->where('level', $request->string('level'));
        }
        if ($request->integer('user_id') > 0) $query->where('user_id', $request->integer('user_id'));
        if ($request->filled('date_from')) $query->where('created_at', '>=', $request->date('date_from')?->startOfDay());
        if ($request->filled('date_to')) $query->where('created_at', '<=', $request->date('date_to')?->endOfDay());
        return $query;
    }
}
