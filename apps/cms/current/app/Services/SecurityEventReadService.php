<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SecurityEvent;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class SecurityEventReadService
{
    /** @var list<string> */
    private const CONTEXT_KEYS_TO_REMOVE = [
        'header',
        'headers',
        'request_headers',
        'response_headers',
        'cookie',
        'cookies',
        'raw_body',
        'raw_payload',
    ];

    public function __construct(private readonly SensitiveDataSanitizer $sanitizer)
    {
    }

    /** @return Builder<SecurityEvent> */
    public function query(Request $request): Builder
    {
        $query = SecurityEvent::query()
            ->with('user:id,username,first_name,last_name')
            ->orderByDesc('id');

        if ($request->filled('action')) {
            $query->where('event_type', 'like', '%'.$request->string('action').'%');
        }

        if ($request->filled('level')) {
            $query->where('severity', $request->string('level'));
        }

        if ($request->integer('user_id') > 0) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date('date_from')?->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date('date_to')?->endOfDay());
        }

        return $query;
    }

    /** @return array<string,mixed> */
    public function presentList(SecurityEvent $event): array
    {
        return [
            'id' => (int) $event->getKey(),
            'event_type' => (string) $event->getAttribute('event_type'),
            'severity' => (string) $event->getAttribute('severity'),
            'request_id' => $this->nullableString($event->getAttribute('request_id')),
            'route_name' => $this->nullableString($event->getAttribute('route_name')),
            'method' => $this->nullableString($event->getAttribute('method')),
            'user' => $this->presentUser($event->getRelation('user')),
            'created_at' => $this->dateValue($event->getAttribute('created_at')),
        ];
    }

    /** @return array<string,mixed> */
    public function presentDetail(SecurityEvent $event): array
    {
        return array_merge($this->presentList($event), [
            'ip_address' => $this->nullableString($event->getAttribute('ip_address')),
            'context' => $this->safeContext($event->getAttribute('context_json')),
        ]);
    }

    /** @return list<string> */
    public function levels(): array
    {
        return SecurityEvent::query()
            ->whereNotNull('severity')
            ->where('severity', '<>', '')
            ->distinct()
            ->orderBy('severity')
            ->pluck('severity')
            ->map(static fn (mixed $value): string => (string) $value)
            ->values()
            ->all();
    }

    /** @return list<array{id:int,name:string,username:string}> */
    public function users(): array
    {
        $userIds = SecurityEvent::query()
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->map(static fn (mixed $value): int => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->values()
            ->all();

        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('username')
            ->get(['id', 'username', 'first_name', 'last_name'])
            ->map(static function (User $user): array {
                return [
                    'id' => (int) $user->getKey(),
                    'name' => $user->displayName(),
                    'username' => (string) $user->getAttribute('username'),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<string,mixed> */
    private function safeContext(mixed $value): array
    {
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        $sanitized = $this->sanitizer->sanitize($value);
        if (!is_array($sanitized)) {
            return [];
        }

        return $this->removeUnsafeContextContainers($sanitized);
    }

    /** @param array<mixed> $value @return array<mixed> */
    private function removeUnsafeContextContainers(array $value): array
    {
        $clean = [];

        foreach ($value as $key => $item) {
            $normalizedKey = strtolower((string) $key);
            if (in_array($normalizedKey, self::CONTEXT_KEYS_TO_REMOVE, true)) {
                continue;
            }

            $clean[$key] = is_array($item)
                ? $this->removeUnsafeContextContainers($item)
                : $item;
        }

        return $clean;
    }

    /** @return array{id:int,name:string,username:string}|null */
    private function presentUser(mixed $user): ?array
    {
        if (!$user instanceof User) {
            return null;
        }

        return [
            'id' => (int) $user->getKey(),
            'name' => $user->displayName(),
            'username' => (string) $user->getAttribute('username'),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        return $string === '' ? null : $string;
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
