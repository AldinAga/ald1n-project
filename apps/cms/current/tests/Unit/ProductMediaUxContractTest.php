<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProductMediaUxContractTest extends TestCase
{
    public function test_product_form_starts_with_name_images_and_specifications(): void
    {
        $view = (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/admin/products/form.blade.php');

        $name = strpos($view, '<h2>Naziv artikla</h2>');
        $images = strpos($view, '<h2>Dodavanje slika</h2>');
        $specifications = strpos($view, '<h2>Specifikacije</h2>');

        self::assertIsInt($name);
        self::assertIsInt($images);
        self::assertIsInt($specifications);
        self::assertLessThan($images, $name);
        self::assertLessThan($specifications, $images);
    }

    public function test_gallery_has_touch_sort_primary_rotation_and_upload_progress(): void
    {
        $root = dirname(__DIR__, 2);
        $card = (string) file_get_contents($root.'/resources/views/admin/products/partials/image-card.blade.php');
        $upload = (string) file_get_contents($root.'/resources/views/admin/products/partials/image-upload.blade.php');
        $script = (string) file_get_contents($root.'/public/assets/js/product-media-manager.js');

        self::assertStringContainsString('image-primary-control', $card);
        self::assertStringContainsString('data-image-drag-handle', $card);
        self::assertStringContainsString('↶ 90°', $card);
        self::assertStringContainsString('↷ 90°', $card);
        self::assertStringContainsString('data-image-upload-percent', $upload);
        self::assertStringContainsString("addEventListener('pointerdown'", $script);
        self::assertStringContainsString('xhr.upload.addEventListener', $script);
    }

    public function test_multiple_storage_devices_are_backward_compatible(): void
    {
        $root = dirname(__DIR__, 2);
        $request = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
        $field = (string) file_get_contents($root.'/app/Models/SpecificationField.php');
        $form = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');

        self::assertStringContainsString('isRepeatableStorageField', $field);
        self::assertStringContainsString('spec_lists', $request);
        self::assertStringContainsString("implode(' + ', array_filter(\$display", $request);
        self::assertStringContainsString('spec_capacities', $request);
        self::assertStringContainsString('spec_structured', $request);
        self::assertStringContainsString('Izabrana opcija diska', $request);
        self::assertStringContainsString('Dodaj još jedan disk', $form);
    }

    public function test_full_resolution_download_is_authorized(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = (string) file_get_contents($root.'/app/Http/Controllers/ProductMediaDownloadController.php');
        $routes = (string) file_get_contents($root.'/routes/web.php');

        self::assertStringContainsString('canManageImages', $controller);
        self::assertStringContainsString('canView', $controller);
        self::assertStringContainsString('response()->download', $controller);
        self::assertStringContainsString("name('media.product.download')", $routes);
    }
}
