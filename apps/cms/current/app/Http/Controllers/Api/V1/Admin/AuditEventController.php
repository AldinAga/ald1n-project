<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\SecurityEventReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AuditEventController extends Controller
{
    public function index(Request $request, SecurityEventReadService $events): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless($user->can('security.view'), 403);

        if (!Schema::hasTable('security_events')) {
            return response()->json([
                'message' => 'Audit događaji trenutno nisu dostupni.',
                'code' => 'audit_events_unavailable',
            ], 503);
        }

        $validator = Validator::make($request->query(), [
            'action' => ['nullable', 'string', 'max:160'],
            'level' => ['nullable', 'string', 'max:40'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:20,50,100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Filteri audit događaja nisu validni.',
                'code' => 'audit_filters_invalid',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();
        $perPage = isset($validated['per_page']) ? (int) $validated['per_page'] : 20;
        $paginator = $events->query($request)->paginate($perPage);

        $rows = collect($paginator->items())
            ->filter(static fn (mixed $item): bool => $item instanceof SecurityEvent)
            ->map(static fn (SecurityEvent $event): array => $events->presentList($event))
            ->values()
            ->all();

        return response()->json([
            'data' => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => [
                'action' => isset($validated['action']) ? (string) $validated['action'] : null,
                'level' => isset($validated['level']) ? (string) $validated['level'] : null,
                'user_id' => isset($validated['user_id']) ? (int) $validated['user_id'] : null,
                'date_from' => isset($validated['date_from']) ? (string) $validated['date_from'] : null,
                'date_to' => isset($validated['date_to']) ? (string) $validated['date_to'] : null,
            ],
            'filter_options' => [
                'levels' => $events->levels(),
                'users' => $events->users(),
                'per_page' => [20, 50, 100],
            ],
            'capabilities' => [
                'detail' => true,
                'export' => $user->can('audit.export'),
                'mutate' => false,
            ],
        ]);
    }

    // MOBILE_V1_0_AUDIT_CSV_EXPORT_PARITY_BATCH29
    public function csv(Request $request, SecurityEventReadService $events): JsonResponse|StreamedResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless($user->can('security.view'), 403);
        abort_unless($user->can('audit.export'), 403);

        if (!Schema::hasTable('security_events')) {
            return response()->json([
                'message' => 'Audit događaji trenutno nisu dostupni.',
                'code' => 'audit_events_unavailable',
            ], 503);
        }

        $validator = Validator::make($request->query(), [
            'action' => ['nullable', 'string', 'max:160'],
            'level' => ['nullable', 'string', 'max:40'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:20,50,100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Filteri audit događaja nisu validni.',
                'code' => 'audit_filters_invalid',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $rows = $events->query($request)
            ->limit(10000)
            ->get()
            ->filter(static fn (mixed $item): bool => $item instanceof SecurityEvent)
            ->values();

        return response()->streamDownload(
            static function () use ($events, $rows): void {
                $handle = fopen('php://output', 'wb');
                if ($handle === false) {
                    throw new \RuntimeException('CSV output stream nije dostupan.');
                }

                fwrite($handle, "\xEF\xBB\xBF");
                fputcsv($handle, $events->csvHeader(), ';', '"', '');
                foreach ($rows as $event) {
                    if ($event instanceof SecurityEvent) {
                        fputcsv($handle, $events->presentCsvRow($event), ';', '"', '');
                    }
                }
                fclose($handle);
            },
            'audit-'.now()->format('Ymd-His').'.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function show(Request $request, SecurityEvent $event, SecurityEventReadService $events): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless($user->can('security.view'), 403);

        $event->loadMissing('user:id,username,first_name,last_name');

        return response()->json([
            'data' => $events->presentDetail($event),
            'capabilities' => [
                'export' => $user->can('audit.export'),
                'mutate' => false,
            ],
        ]);
    }
}
