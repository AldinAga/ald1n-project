<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MobilePushPhase3BContractTest extends TestCase
{
    public function test_phase3b_push_delivery_contract_is_present(): void
    {
        $root = dirname(__DIR__, 2);
        $notificationService = (string) file_get_contents($root.'/app/Services/OperationalNotificationService.php');
        $transport = (string) file_get_contents($root.'/app/Services/ExpoPushTransport.php');
        $dispatcher = (string) file_get_contents($root.'/app/Services/MobilePushDispatcher.php');
        $schedule = (string) file_get_contents($root.'/routes/console.php');
        $config = (string) file_get_contents($root.'/config/mobile.php');

        self::assertStringContainsString("\$data['_push']", $notificationService);
        self::assertStringContainsString('MobilePushOutboxService', $notificationService);
        self::assertStringContainsString('/api/v2/push/send', $config);
        self::assertStringContainsString('/api/v2/push/getReceipts', $config);
        self::assertStringContainsString('DeviceNotRegistered', $transport);
        self::assertStringContainsString('MessageRateExceeded', $dispatcher);
        self::assertStringContainsString("app:mobile-push-dispatch --limit=100", $schedule);
    }
}
