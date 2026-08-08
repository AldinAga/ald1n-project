<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class SecurityEventLogger
{
    public function __construct(private readonly SensitiveDataSanitizer $sanitizer)
    {
    }

    /** @param array<string,mixed> $context */
    public function log(string $type, string $severity, Request $request, array $context = []): void
    {
        try {
            if (!Schema::hasTable('security_events')) {
                return;
            }

            SecurityEvent::query()->create([
                'user_id' => $request->user()?->getAuthIdentifier(),
                'event_type' => mb_substr($type, 0, 100),
                'severity' => in_array($severity, ['info', 'warning', 'critical'], true) ? $severity : 'warning',
                'request_id' => $request->attributes->get('request_id'),
                'route_name' => $request->route()?->getName(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'context_json' => $this->sanitizer->sanitize($context),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Security event could not be persisted.', [
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
