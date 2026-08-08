<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = is_array($this->data) ? $this->data : [];

        return [
            'id' => $this->id,
            'event' => $data['event'] ?? null,
            'title' => $data['title'] ?? 'Obaveštenje',
            'message' => $data['message'] ?? null,
            'icon' => $data['icon'] ?? null,
            'severity' => $data['severity'] ?? 'info',
            'action_label' => $data['action_label'] ?? null,
            'route' => $this->mobileRoute($data),
            'target' => $this->target($data),
            'data' => $this->publicData($data),
            'read' => $this->read_at !== null,
            'read_at' => optional($this->read_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }

    /** @param array<string,mixed> $data */
    private function mobileRoute(array $data): ?string
    {
        $explicit = trim((string) ($data['route'] ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        foreach ([
            'order_id' => '/orders/%s',
            'after_sales_case_id' => '/after-sales/%s',
            'warranty_id' => '/warranties/%s',
            'field_work_order_id' => '/field-work/%s',
            'commission_id' => '/commissions/%s',
        ] as $key => $pattern) {
            $id = (int) ($data[$key] ?? 0);
            if ($id > 0) {
                return sprintf($pattern, $id);
            }
        }

        return null;
    }

    /** @param array<string,mixed> $data @return array{type:string,id:int}|null */
    private function target(array $data): ?array
    {
        foreach ([
            'order_id' => 'order',
            'after_sales_case_id' => 'after_sales_case',
            'warranty_id' => 'warranty',
            'field_work_order_id' => 'field_work_order',
            'commission_id' => 'commission',
        ] as $key => $type) {
            $id = (int) ($data[$key] ?? 0);
            if ($id > 0) {
                return ['type' => $type, 'id' => $id];
            }
        }

        return null;
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function publicData(array $data): array
    {
        unset($data['url'], $data['_in_app'], $data['_email'], $data['_push']);

        return $data;
    }
}
