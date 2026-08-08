<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TurnstileService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class TurnstileServiceTest extends TestCase
{
    public function test_valid_response_requires_expected_host_and_action(): void
    {
        config()->set('services.turnstile.enabled', true);
        config()->set('services.turnstile.secret_key', 'test-secret');
        config()->set('services.turnstile.expected_hostname', 'cms.ald1n.com');
        config()->set('services.turnstile.expected_action', 'login');

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'hostname' => 'cms.ald1n.com',
                'action' => 'login',
            ]),
        ]);

        self::assertTrue(app(TurnstileService::class)->verify('valid-token', '127.0.0.1')['success']);
    }

    public function test_wrong_action_is_rejected(): void
    {
        config()->set('services.turnstile.enabled', true);
        config()->set('services.turnstile.secret_key', 'test-secret');
        config()->set('services.turnstile.expected_hostname', 'cms.ald1n.com');
        config()->set('services.turnstile.expected_action', 'login');

        Http::fake(['*' => Http::response(['success' => true, 'hostname' => 'cms.ald1n.com', 'action' => 'other'])]);

        self::assertFalse(app(TurnstileService::class)->verify('valid-token', '127.0.0.1')['success']);
    }

    public function test_action_can_be_overridden_for_password_reset(): void
    {
        config()->set('services.turnstile.enabled', true);
        config()->set('services.turnstile.secret_key', 'test-secret');
        config()->set('services.turnstile.expected_hostname', 'cms.ald1n.com');
        config()->set('services.turnstile.expected_action', 'login');

        Http::fake(['*' => Http::response([
            'success' => true,
            'hostname' => 'cms.ald1n.com',
            'action' => 'password_reset_request',
        ])]);

        self::assertTrue(app(TurnstileService::class)->verify(
            'valid-token',
            '127.0.0.1',
            'password_reset_request',
        )['success']);
    }

}
