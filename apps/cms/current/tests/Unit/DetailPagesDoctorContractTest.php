<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DetailPagesDoctorContractTest extends TestCase
{
    public function test_product_edit_dependencies_are_resolved_by_the_container(): void
    {
        $root = dirname(__DIR__, 2);
        $doctor = (string) file_get_contents($root.'/app/Console/Commands/DetailPagesDoctorCommand.php');
        $controller = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php');

        self::assertStringContainsString('function edit(Product $product, ProductTemplateService $templates)', $controller);
        self::assertStringContainsString("app()->call([app(AdminProductController::class), 'edit']", $doctor);
        self::assertStringContainsString("'product' => \$product->fresh() ?? \$product", $doctor);
        self::assertStringNotContainsString('app(AdminProductController::class)->edit(', $doctor);
    }

    public function test_image_detail_call_uses_the_same_container_safe_pattern(): void
    {
        $doctor = (string) file_get_contents(dirname(__DIR__, 2).'/app/Console/Commands/DetailPagesDoctorCommand.php');

        self::assertStringContainsString("app()->call([app(ProductImageController::class), 'index']", $doctor);
        self::assertStringNotContainsString('app(ProductImageController::class)->index(', $doctor);
    }
}
