<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupRun;
use App\Models\SecurityEvent;
use App\Models\SystemHealthSnapshot;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BackupService;
use App\Services\SystemHealthService;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Throwable;

// MOBILE_V1_0_SYSTEM_HEALTH_MUTATIONS_PARITY_BATCH25
final class SystemHealthController extends Controller
{
    public function index(Request $request, SystemHealthService $health): JsonResponse
    {
        $actor = $this->actor($request);

        try {
            $report = $health->inspect();
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'System Health trenutno nije dostupan.',
                'code' => 'system_health_unavailable',
            ], 503);
        }

        return response()->json($this->statePayload($actor, $report));
    }

    public function run(
        Request $request,
        SystemHealthService $health,
        AuditLogger $audit,
    ): JsonResponse {
        $actor = $this->actor($request);

        try {
            $report = $health->inspect();
            $health->snapshot($report, (int) $actor->id);
            $audit->log(
                'system.health_checked',
                'Ručno pokrenuta System Health provera.',
                null,
                metadata: ['status' => (string) ($report['status'] ?? 'unknown')],
                user: $actor,
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'System Health provera trenutno nije mogla da bude sačuvana.',
                'code' => 'system_health_run_failed',
            ], 503);
        }

        return response()->json([
            'message' => 'System Health provera je završena: '.strtoupper((string) ($report['status'] ?? 'unknown')).'.',
            'data' => [
                'status' => (string) ($report['status'] ?? 'unknown'),
                'checked_at' => $this->dateValue($report['checked_at'] ?? null),
            ],
        ]);
    }

    public function backup(
        Request $request,
        BackupService $backup,
        AuditLogger $audit,
    ): JsonResponse {
        $actor = $this->actor($request);
        abort_unless($actor->can('backups.manage'), 403);

        $data = $request->validate([
            'database_only' => ['nullable', 'boolean'],
        ]);
        $databaseOnly = filter_var($data['database_only'] ?? false, FILTER_VALIDATE_BOOL);

        try {
            $result = $backup->create('manual', (int) $actor->id, $databaseOnly);
            $run = $result['run'] instanceof BackupRun ? $result['run'] : null;
            $audit->log(
                'backup.created',
                'Kreiran ručni bezbednosni backup.',
                $run,
                metadata: [
                    'size' => (int) $result['size'],
                    'database_only' => $databaseOnly,
                ],
                user: $actor,
            );
        } catch (Throwable $exception) {
            report($exception);
            $audit->log(
                'backup.failed',
                'Ručni backup nije uspeo.',
                null,
                metadata: ['error_class' => $exception::class],
                user: $actor,
                level: 'error',
            );

            return response()->json([
                'message' => 'Backup nije uspeo. Proveri System Health i serverski log.',
                'code' => 'backup_failed',
            ], 503);
        }

        return response()->json([
            'message' => $databaseOnly
                ? 'Backup baze je uspešno kreiran.'
                : 'Kompletan backup je uspešno kreiran.',
            'data' => [
                'backup_key' => $run?->backup_key,
                'backup_type' => $run?->backup_type ?? 'manual',
                'status' => $run?->status ?? 'completed',
                'size_bytes' => (int) $result['size'],
                'database_only' => $databaseOnly,
            ],
        ], 201);
    }

    public function prune(
        Request $request,
        BackupService $backup,
        AuditLogger $audit,
    ): JsonResponse {
        $actor = $this->actor($request);
        abort_unless($actor->can('backups.manage'), 403);

        try {
            $result = $backup->prune();
            $audit->log(
                'backup.pruned',
                'Primena backup retention pravila.',
                null,
                metadata: $result,
                user: $actor,
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Backup retention trenutno nije mogao da bude primenjen.',
                'code' => 'backup_prune_failed',
            ], 503);
        }

        return response()->json([
            'message' => 'Backup retention je primenjen. Uklonjeno: '.(int) $result['removed'].'.',
            'data' => [
                'removed' => (int) $result['removed'],
                'kept' => (int) $result['kept'],
            ],
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->can('system.health'), 403);

        return $actor;
    }

    /** @param array<string,mixed> $report @return array<string,mixed> */
    private function statePayload(User $actor, array $report): array
    {
        return [
            'data' => [
                'status' => (string) ($report['status'] ?? 'unknown'),
                'checks' => $this->arrayValue($report['checks'] ?? []),
                'metrics' => $this->mapValue($report['metrics'] ?? []),
                'checked_at' => $this->dateValue($report['checked_at'] ?? null),
            ],
            'history' => $this->history(),
            'backups' => $this->backups(),
            'security_events' => $this->securityEvents(),
            'capabilities' => [
                'refresh' => true,
                'snapshot' => true,
                'backup' => $actor->can('backups.manage'),
                'prune' => $actor->can('backups.manage'),
            ],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function history(): array
    {
        if (!Schema::hasTable('system_health_snapshots')) {
            return [];
        }

        return SystemHealthSnapshot::query()
            ->with('checker')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (SystemHealthSnapshot $snapshot): array => [
                'id' => (int) $snapshot->getKey(),
                'status' => (string) $snapshot->getAttribute('status'),
                'checks' => $this->arrayValue($snapshot->getAttribute('checks_json')),
                'metrics' => $this->mapValue($snapshot->getAttribute('metrics_json')),
                'checker_name' => $snapshot->checker?->displayName(),
                'checked_at' => $this->dateValue(
                    $snapshot->getAttribute('checked_at')
                        ?? $snapshot->getAttribute('created_at'),
                ),
            ])
            ->values()
            ->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function backups(): array
    {
        if (!Schema::hasTable('backup_runs')) {
            return [];
        }

        return BackupRun::query()
            ->with('creator')
            ->latest('started_at')
            ->limit(20)
            ->get()
            ->map(fn (BackupRun $run): array => [
                'id' => (int) $run->getKey(),
                'backup_key' => (string) $run->backup_key,
                'backup_type' => (string) $run->backup_type,
                'status' => (string) $run->status,
                'creator_name' => $run->creator?->displayName(),
                'size_bytes' => (int) ($run->size_bytes ?? 0),
                'started_at' => $this->dateValue($run->started_at),
                'finished_at' => $this->dateValue($run->finished_at),
            ])
            ->values()
            ->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function securityEvents(): array
    {
        if (!Schema::hasTable('security_events')) {
            return [];
        }

        return SecurityEvent::query()
            ->with('user')
            ->latest('created_at')
            ->limit(15)
            ->get()
            ->map(fn (SecurityEvent $event): array => [
                'id' => (int) $event->getKey(),
                'event_type' => (string) $event->event_type,
                'severity' => (string) $event->severity,
                'actor_name' => $event->user?->displayName(),
                'route_name' => $event->route_name !== null ? (string) $event->route_name : null,
                'method' => $event->method !== null ? (string) $event->method : null,
                'created_at' => $this->dateValue($event->created_at),
            ])
            ->values()
            ->all();
    }

    /** @return array<int,mixed> */
    private function arrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? array_values($decoded) : [];
        }

        return [];
    }

    /** @return array<string,mixed> */
    private function mapValue(mixed $value): array
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
