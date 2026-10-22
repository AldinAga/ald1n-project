<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\ShipmentTrackingUrl;
use PHPUnit\Framework\TestCase;

final class ShipmentTrackingUrlTest extends TestCase
{
    public function test_safe_carrier_links_are_preserved_and_unsafe_destinations_are_not_links(): void
    {
        foreach ([
            'https://carrier.example.test/track?code=A-123',
            'https://www.posta.rs/',
            'https://carrier.example.test:443/track',
        ] as $url) {
            self::assertSame($url, ShipmentTrackingUrl::safe($url));
        }
        foreach ([
            null, '', 'javascript:alert(1)', 'data:text/html,invalid',
            '//carrier.example.test/track', 'http://carrier.example.test/',
            'https://user:password@carrier.example.test/', 'https://carrier.example.test:8443/',
            'https://localhost/', 'https://127.0.0.1/', 'https://[::1]/',
            'https://0177.0.0.1/', 'https://0x7f.0.0.1/', 'https://carrier.local/',
            'https://carrier.internal/', 'https://carrier.example.test/%0atrack',
            'https://2130706433/', 'https://carrier.example.test/%7f',
            "https://carrier.example.test/\ntrack", 'https://carrier.example.test\\@invalid.test/',
        ] as $url) {
            self::assertNull(ShipmentTrackingUrl::safe($url), (string) $url);
        }
    }
}
