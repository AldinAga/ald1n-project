<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CmsV214ContractTest extends TestCase
{
    public function test_product_model_is_persisted_and_used_by_name_generator(): void
    {
        $root = dirname(__DIR__, 2);
        $migration = (string) file_get_contents($root.'/database/migrations/2026_08_04_000036_add_product_model_and_name_templates_v2_1_4.php');
        $request = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
        $template = (string) file_get_contents($root.'/app/Services/ProductTemplateService.php');

        self::assertStringContainsString("string('model_name', 190)", $migration);
        self::assertStringContainsString("'model_name' => ['nullable', 'string', 'max:190']", $request);
        self::assertStringContainsString("'model' => trim((string) (\$data['model_name'] ?? ''))", $template);
        self::assertStringContainsString("['{brand}', '{line}', '{model}']", $template);
    }

    public function test_permanent_delete_is_confirmed_blocked_by_history_and_can_remove_images(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php');
        $service = (string) file_get_contents($root.'/app/Services/ProductDeletionService.php');
        $form = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');

        self::assertStringContainsString('hash_equals((string) $product->sku', $controller);
        self::assertStringContainsString("'Porudžbine' => 'order_items'", $service);
        self::assertStringContainsString("Storage::disk('public')->deleteDirectory", $service);
        self::assertStringContainsString('name="delete_images"', $form);
    }

    public function test_button_system_preserves_semantic_colours_with_one_geometry(): void
    {
        $css = (string) file_get_contents(dirname(__DIR__, 2).'/public/assets/css/app.css');

        self::assertStringContainsString('--button-height:46px', $css);
        self::assertStringContainsString('--button-radius:15px', $css);
        foreach (['primary', 'secondary', 'success', 'warning', 'danger'] as $role) {
            self::assertStringContainsString('.button-'.$role, $css);
        }
        self::assertStringContainsString('.button:focus-visible', $css);
        self::assertStringContainsString('prefers-reduced-motion', $css);
    }

    public function test_release_profile_contains_v214_doctor(): void
    {
        $config = require dirname(__DIR__, 2).'/config/release.php';

        self::assertContains('cms_v214', $config['profiles']['stable']);
        self::assertSame('app:cms-v2-1-4-doctor', $config['checks']['cms_v214']['command']);
        self::assertSame(['--repair' => true], $config['checks']['cms_v214']['repair']);
    }
}
