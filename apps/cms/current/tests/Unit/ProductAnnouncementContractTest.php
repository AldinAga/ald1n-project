<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProductAnnouncementContractTest extends TestCase
{
    public function test_new_product_announcement_contract_is_present(): void
    {
        $root = dirname(__DIR__, 2);
        $service = (string) file_get_contents($root.'/app/Services/ProductAnnouncementService.php');
        $controller = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php');
        $settings = (string) file_get_contents($root.'/app/Services/SettingsService.php');
        $view = (string) file_get_contents($root.'/resources/views/admin/settings/order-emails.blade.php');
        $mailView = (string) file_get_contents($root.'/resources/views/emails/order-events.blade.php');

        self::assertStringContainsString("'product_email_new_items_enabled' => '0'", $settings);
        self::assertStringContainsString("'event_type' => 'product_published'", $service);
        self::assertStringContainsString("where('status', 'active')", $service);
        self::assertStringContainsString('firstOrCreate', $service);
        self::assertStringContainsString("'product_image_url'", $service);
        self::assertStringContainsString("'product_description'", $service);
        self::assertStringContainsString("'product_price_formatted'", $service);
        self::assertStringContainsString('queueForNewlyPublished($product', $controller);
        self::assertStringContainsString('$wasActive', $controller);
        self::assertStringContainsString('product_email_new_items_enabled', $view);
        self::assertStringContainsString('product_email_new_items_interval_minutes', $view);
        self::assertStringContainsString('$productImage', $mailView);
        self::assertStringContainsString('$productDescription', $mailView);
        self::assertStringContainsString('$productPrice', $mailView);
    }
}
