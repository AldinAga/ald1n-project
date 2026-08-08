<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AuditLogger
{
    public function __construct(private readonly SensitiveDataSanitizer $sanitizer)
    {
    }
    /** @param array<string,mixed>|null $before @param array<string,mixed>|null $after @param array<string,mixed>|null $metadata */
    public function log(
        string $action,
        string $subject,
        ?Model $auditable = null,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null,
        ?Authenticatable $user = null,
        ?Request $request = null,
        string $level = 'info',
    ): AuditLog {
        $request ??= request();
        $user ??= $request->user();

        return AuditLog::query()->create([
            'user_id' => $user?->getAuthIdentifier(),
            'action' => $action,
            'level' => in_array($level, ['info', 'warning', 'error', 'critical'], true) ? $level : 'info',
            'request_id' => $request->attributes->get('request_id'),
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'subject' => $subject,
            'before_json' => $this->sanitize($before),
            'after_json' => $this->sanitize($after),
            'metadata_json' => $this->sanitize($metadata),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }

    /** @param array<string,mixed>|null $payload @return array<string,mixed>|null */
    private function sanitize(?array $payload): ?array
    {
        if ($payload === null) return null;
        /** @var array<string,mixed> $clean */
        $clean = $this->sanitizer->sanitize($payload);
        return $clean;
    }
}
