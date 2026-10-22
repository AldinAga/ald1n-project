<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\TrackingUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TrackingUrl796Test extends TestCase
{
    #[DataProvider('urls')]
    public function test_only_valid_public_tracking_destinations_are_returned(?string $url, bool $allowed): void
    {
        self::assertSame($allowed ? trim((string) $url) : null, TrackingUrl::safe($url));
    }

    public static function urls(): array
    {
        return [
            ['https://tracking.example.test/parcel?code=A%20B', true],
            ['http://post.example.test/tracking', true],
            ['https://tracking.example.test:443/', true],
            ['http://127.1/', false],
            ['http://0177.0.0.1/', false],
            ['http://0x7f.0.0.1/', false],
            ['https://a..example.test/', false],
            ['https://-bad.example.test/', false],
            ['javascript:alert(1)', false],
            ['data:text/html,xx', false],
            ['//example.test/x', false],
            ['https://user:pass@example.test/x', false],
            ['https://127.0.0.1/x', false],
            ['http://192.168.1.1/x', false],
            ['http://[::1]/', false],
            ['https://localhost/x', false],
            ['https://foo.internal/', false],
            ['https://foo.local/', false],
            ['https://example.test:22/', false],
            ["https://example.test/\r\nx", false],
            ['https://example.test/%0ax', false],
            ['https://example.test\\@evil.test/', false],
            ['', false],
            [null, false],
        ];
    }
}
