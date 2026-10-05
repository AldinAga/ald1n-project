<?php

declare(strict_types=1);

$root = realpath($argv[1] ?? dirname(__DIR__));
if (!is_string($root)) {
    fwrite(STDERR, "FAIL invalid root\n");
    exit(2);
}

$failures = 0;
$check = static function (bool $ok, string $label) use (&$failures): void {
    if ($ok) {
        fwrite(STDOUT, "PASS {$label}\n");
        return;
    }
    $failures++;
    fwrite(STDERR, "FAIL {$label}\n");
};
$read = static fn (string $path): string => is_file($path) ? (string) file_get_contents($path) : '';

$product = $read($root.'/app/Models/Product.php');
$query = $read($root.'/app/Services/CatalogQueryService.php');
$apiController = $read($root.'/app/Http/Controllers/Api/V1/CatalogController.php');
$resource = $read($root.'/app/Http/Resources/ProductResource.php');
$publisher = $read($root.'/app/Services/ProductImagePublicationService.php');
$doctor = $read($root.'/app/Console/Commands/CatalogImageAvailabilityDoctorCommand.php');
$web = $read($root.'/routes/web.php');
$mobileCard = $read(dirname($root, 2).'/mobile/current/src/components/catalog/product-card.tsx');
$mobileGallery = $read(dirname($root, 2).'/mobile/current/src/components/catalog/product-image-gallery.tsx');

$check(str_contains($product, 'function presentationImage()'), 'Product exposes presentationImage fallback relation');
$check(str_contains($query, "'presentationImage'"), 'Catalog list eager-loads presentation image');
$check(str_contains($apiController, "loadMissing('presentationImage')"), 'API detail loads presentation image');
$check(substr_count($resource, '$this->presentationImage?') >= 4, 'ProductResource primary image fields use presentation image');
$check(str_contains($publisher, 'final class ProductImagePublicationService') && str_contains($publisher, 'sourceAbsolutePath') && str_contains($publisher, "storage_disk !== 'legacy'"), 'Controlled legacy publication service exists');
$check(str_contains($doctor, 'app:catalog-image-availability-doctor') && str_contains($doctor, '{--sku=*}') && str_contains($doctor, '{--apply}'), 'Catalog image availability doctor supports dry-run/apply');
$authPos = strpos($web, "Route::middleware(['auth', 'active', 'tracked-session'])");
$mediaPos = strpos($web, "name('media.product')");
$check($authPos !== false && $mediaPos !== false && $mediaPos > $authPos, 'Legacy media route remains inside authenticated Web group');
$thumb = strpos($mobileCard, 'product.primary_image_thumbnail_url');
$display = strpos($mobileCard, 'product.primary_image_display_url');
$base = strpos($mobileCard, 'product.primary_image_url');
$check($thumb !== false && $display !== false && $base !== false && $thumb < $display && $display < $base, 'Mobile catalog keeps thumbnail display original fallback');
$check(str_contains($mobileGallery, 'image.thumbnail_url') && str_contains($mobileGallery, 'image.display_url') && str_contains($mobileGallery, 'image.url'), 'Mobile detail gallery keeps rendition fallback');
$check(!is_file($root.'/app/Models/ProductVariant.php') && !is_file($root.'/app/Services/ProductVariantService.php'), 'Product Variants remain decommissioned');

fwrite(STDOUT, "BATCH517_CONTRACT_FAIL_COUNT={$failures}\n");
exit($failures === 0 ? 0 : 1);
