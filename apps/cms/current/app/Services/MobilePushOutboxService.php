<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MobilePushOutbox;
use App\Models\User;
use Illuminate\Support\Str;

final class MobilePushOutboxService
{
    /** @param array<string,mixed> $data */
    public function enqueue(User $recipient, array $data): int
    {
        if (!(bool) config('mobile.push.enabled', false) || (string) config('mobile.push.provider', 'expo') !== 'expo') {
            return 0;
        }

        $devices = $recipient->mobileDevices()
            ->whereNull('revoked_at')
            ->where('notifications_enabled', true)
            ->where('push_provider', 'expo')
            ->whereNotNull('push_token_hash')
            ->orderBy('id')
            ->get(['id']);

        if ($devices->isEmpty()) {
            return 0;
        }

        $title = trim((string) ($data['title'] ?? 'Ald1n CMS'));
        $message = trim((string) ($data['message'] ?? 'Imate novo poslovno obaveštenje.'));
        $payload = $this->mobileData($data);
        $count = 0;

        foreach ($devices as $device) {
            MobilePushOutbox::query()->create([
                'user_id' => $recipient->id,
                'mobile_device_id' => $device->id,
                'provider' => 'expo',
                'event' => $this->nullableText($data['event'] ?? null, 120),
                'title' => Str::limit($title !== '' ? $title : 'Ald1n CMS', 180, ''),
                'message' => Str::limit($message !== '' ? $message : 'Imate novo poslovno obaveštenje.', 1200, ''),
                'data_json' => $payload,
                'status' => 'pending',
                'scheduled_for' => now(),
            ]);
            $count++;
        }

        return $count;
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function mobileData(array $data): array
    {
        $result = [];
        foreach ([
            'event',
            'severity',
            'route',
            'order_id',
            'order_number',
            'after_sales_case_id',
            'warranty_id',
            'field_work_order_id',
            'commission_id',
        ] as $key) {
            $value = $data[$key] ?? null;
            if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
                $result[$key] = $value;
            }
        }

        if (!isset($result['route'])) {
            $route = $this->mobileRoute($data);
            if ($route !== null) {
                $result['route'] = $route;
            }
        }

        return $result;
    }

    /** @param array<string,mixed> $data */
    private function mobileRoute(array $data): ?string
    {
        foreach ([
            'order_id' => '/orders/%d',
            'after_sales_case_id' => '/after-sales/%d',
            'warranty_id' => '/warranties/%d',
            'field_work_order_id' => '/field-work/%d',
            'commission_id' => '/commissions/%d',
        ] as $key => $pattern) {
            $id = (int) ($data[$key] ?? 0);
            if ($id > 0) {
                return sprintf($pattern, $id);
            }
        }

        return null;
    }

    private function nullableText(mixed $value, int $limit): ?string
    {
        $text = trim((string) $value);
        return $text !== '' ? Str::limit($text, $limit, '') : null;
    }
}
