<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CustomerPortal2ContractTest extends TestCase
{
    public function test_activation_and_session_security_contracts_are_present(): void
    {
        $root = dirname(__DIR__, 2);
        $activation = (string) file_get_contents($root.'/app/Services/CustomerActivationService.php');
        $sessions = (string) file_get_contents($root.'/app/Services/PortalSessionService.php');
        $middleware = (string) file_get_contents($root.'/app/Http/Middleware/EnsureTrackedPortalSession.php');

        self::assertStringContainsString("hash('sha256', \$plainToken)", $activation);
        self::assertStringContainsString("whereNull('accepted_at')", $activation);
        self::assertStringContainsString("where('expires_at', '>', now())", $activation);
        self::assertStringContainsString("hash('sha256', \$id)", $sessions);
        self::assertStringContainsString('validateAndTouch', $middleware);
        self::assertStringContainsString('Auth::logout()', $middleware);
    }

    public function test_customer_messages_never_render_internal_messages(): void
    {
        $root = dirname(__DIR__, 2);
        $customerController = (string) file_get_contents($root.'/app/Http/Controllers/PortalConversationController.php');
        $customerView = (string) file_get_contents($root.'/resources/views/portal/messages/show.blade.php');
        $adminView = (string) file_get_contents($root.'/resources/views/admin/customer-portal/conversation.blade.php');

        self::assertStringContainsString('publicMessages.sender', $customerController);
        self::assertStringContainsString('$conversation->publicMessages', $customerView);
        self::assertStringNotContainsString('$conversation->messages as $message', $customerView);
        self::assertStringContainsString("\$message->visibility === 'internal'", $adminView);
    }

    public function test_order_relink_requires_confirmation_and_writes_history(): void
    {
        $controller = (string) file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/Admin/CustomerPortalController.php');

        self::assertStringContainsString('confirm_reassign', $controller);
        self::assertStringContainsString('PortalOrderLinkHistory::query()->create', $controller);
        self::assertStringContainsString('customer_portal.order_relinked', $controller);
    }

    public function test_session_rotation_and_activation_cache_security_contracts_are_present(): void
    {
        $root = dirname(__DIR__, 2);
        $account = (string) file_get_contents($root.'/app/Http/Controllers/AccountController.php');
        $activation = (string) file_get_contents($root.'/app/Services/CustomerActivationService.php');
        $headers = (string) file_get_contents($root.'/app/Http/Middleware/SecurityHeaders.php');
        $sessions = (string) file_get_contents($root.'/app/Services/PortalSessionService.php');

        self::assertStringContainsString('markLogout($request, $user)', $account);
        self::assertStringContainsString('recordLogin($request, $user, false)', $account);
        self::assertStringContainsString("!in_array(\$user->status, ['active', 'blocked'], true)", $activation);
        self::assertStringContainsString("'customer-activation.*'", $headers);
        self::assertStringContainsString('expireStale', $sessions);
        self::assertStringContainsString('Auth::viaRemember()', $sessions);
    }
}
