<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\SensitiveDataSanitizer;
use PHPUnit\Framework\TestCase;

final class SensitiveDataSanitizerTest extends TestCase
{
    public function test_nested_secrets_are_redacted(): void
    {
        $clean = (new SensitiveDataSanitizer())->sanitize([
            'email' => 'user@example.test',
            'database' => ['DB_PASSWORD' => 'never-store-this'],
            'payload' => ['token' => 'private-token', 'safe' => 'value'],
        ]);

        self::assertSame('user@example.test', $clean['email']);
        self::assertSame('[REDACTED]', $clean['database']['DB_PASSWORD']);
        self::assertSame('[REDACTED]', $clean['payload']['token']);
        self::assertSame('value', $clean['payload']['safe']);
    }
}
