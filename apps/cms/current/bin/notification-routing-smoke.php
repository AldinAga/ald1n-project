<?php

declare(strict_types=1);

// Batch632 dependency-free behavioral smoke. Launch directly with php, never via
// Laravel bootstrap. All model, transport and framework collaborators are stubs.
// The real source files are required and exercised; no application/database write.

namespace Illuminate\Http\Resources\Json {
    class JsonResource {}
}

namespace Illuminate\Http {
    class Request {}
}

namespace App\Models {
    class NotificationPreference
    {
        public function __construct(array $data = [])
        {
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    class Order
    {
        public int $id = 134;
        public string $order_number = 'ALD-134';
    }

    class OrderCommission {}

    class User
    {
        public string $status = 'active';
        public int $id = 1;
        public array $received = [];

        public function __construct(public string $role) {}

        public function hasRole(string ...$roles): bool
        {
            return in_array($this->role, $roles, true);
        }

        public function notificationPreference(): object
        {
            return new class {
                public function firstOrCreate(array $query, array $defaults): NotificationPreference
                {
                    return new NotificationPreference(array_replace($defaults, ['push_enabled' => true]));
                }
            };
        }

        public function notify($notification): void
        {
            $this->received[] = $notification->payload;
        }
    }
}

namespace App\Notifications {
    class OperationalNotification
    {
        public function __construct(public array $payload) {}
    }
}

namespace App\Services {
    class MobilePushOutboxService
    {
        public array $payloads = [];

        public function enqueue($recipient, array $data): void
        {
            $this->payloads[] = $data;
        }
    }
}

namespace Illuminate\Support\Facades {
    class Log
    {
        public static function warning(...$unused): void {}
    }
}

namespace {
    function route(string $name, ?\App\Models\Order $order = null): string
    {
        return match ($name) {
            'admin.orders.show' => 'https://cms.example.test/admin/orders/'.$order?->id,
            'orders.show' => 'https://cms.example.test/orders/'.$order?->id,
            default => 'https://cms.example.test/',
        };
    }

    $root = dirname(__DIR__);
    require $root.'/app/Http/Resources/Api/V1/NotificationResource.php';
    require $root.'/app/Services/OperationalNotificationService.php';

    $passed = 0;
    $total = 0;
    $check = static function (string $label, mixed $actual, mixed $expected) use (&$passed, &$total): void {
        $total++;
        $ok = $actual === $expected;
        $passed += (int) $ok;
        printf("%s %s\n", $ok ? 'PASS' : 'FAIL', $label);
        if (!$ok) {
            fprintf(STDERR, "  expected=%s actual=%s\n", var_export($expected, true), var_export($actual, true));
        }
    };

    $resource = new \App\Http\Resources\Api\V1\NotificationResource();
    $method = new \ReflectionMethod($resource, 'mobileRoute');
    foreach ([
        ['legacy_admin_134', ['order_id' => 134, 'url' => 'https://cms.example.test/admin/orders/134'], '/admin/orders/134'],
        ['legacy_admin_query', ['order_id' => 134, 'url' => 'https://cms.example.test/admin/orders/134?tab=history'], '/admin/orders/134'],
        ['mismatched_admin_id', ['order_id' => 134, 'url' => 'https://cms.example.test/admin/orders/999'], '/orders/134'],
        ['customer_url', ['order_id' => 134, 'url' => 'https://cms.example.test/orders/134'], '/orders/134'],
        ['legacy_no_url', ['order_id' => 134], '/orders/134'],
        ['explicit_route_precedence', ['order_id' => 134, 'route' => '/order/134', 'url' => 'https://cms.example.test/admin/orders/134'], '/order/134'],
        ['unsupported_url', ['order_id' => 134, 'url' => 'https://example.test/admin/orders/134/tracking'], '/orders/134'],
        ['missing_order_id', ['url' => 'https://cms.example.test/admin/orders/134'], null],
    ] as [$label, $data, $expected]) {
        $check($label, $method->invoke($resource, $data), $expected);
    }

    foreach (['admin' => '/admin/orders/134', 'superadmin' => '/admin/orders/134', 'user' => '/orders/134'] as $role => $expected) {
        $recipient = new \App\Models\User($role);
        $push = new \App\Services\MobilePushOutboxService();
        $service = new \App\Services\OperationalNotificationService($push);
        $service->order($recipient, 'order.status_changed', 'Status', 'Porudžbina ažurirana', new \App\Models\Order());

        $check('new_'.$role.'_inbox', $recipient->received[0]['route'] ?? null, $expected);
        $check('new_'.$role.'_push', $push->payloads[0]['route'] ?? null, $expected);
    }

    printf("BATCH632_ROUTING_BEHAVIOR=%d/%d\n", $passed, $total);
    exit($total === 14 && $passed === $total ? 0 : 1);
}
