#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
};
$source = static fn (string $path): string => (string) @file_get_contents($root.'/'.$path);

$version = trim($source('VERSION'));
$check('Verzija sadrži Product Media UX osnovu', version_compare($version, '2.1.2', '>=') && trim($source('RELEASE-TAG')) === 'v'.$version);

$routes = $source('routes/web.php');
$download = $source('app/Http/Controllers/ProductMediaDownloadController.php');
$imageModel = $source('app/Models/ProductImage.php');
$access = $source('app/Services/CatalogAccessService.php');
$check('Puna rezolucija ima autentifikovanu download rutu', str_contains($routes, "media/product/{image}/download") && str_contains($routes, "name('media.product.download')"));
$check('Download proverava pristup katalogu ili vlasništvo', str_contains($download, 'canManageImages') && str_contains($download, 'canView') && str_contains($download, 'response()->download'));
$check('Download štiti public i legacy putanje', str_contains($download, "str_starts_with(\$relative, 'products/')") && str_contains($download, "str_starts_with(\$relative, 'uploads/products/')") && str_contains($download, "str_contains(\$relative, '..')"));
$check('Model slike daje download URL i cache-busted prikaz', str_contains($imageModel, 'getDownloadUrlAttribute') && str_contains($imageModel, "'v='.") && str_contains($imageModel, 'file_hash'));
$check('Catalog access servis ostaje vlasnički ograničen', str_contains($access, 'canManageImages') && str_contains($access, 'canManage($product, $user)'));

$service = $source('app/Services/ProductImageService.php');
$controller = $source('app/Http/Controllers/Admin/ProductImageController.php');
$check('Rotacija podržava Imagick i GD', str_contains($service, 'rotateWithImagick') && str_contains($service, "extension_loaded('gd')") && str_contains($service, 'imagerotate'));
$check('Rotacija koristi privremeni fajl i legacy copy-on-write', str_contains($service, 'temporaryCopy') && str_contains($service, 'legacy_copy_on_write') && str_contains($service, "storage_disk = 'public'"));
$check('Rotacija osvežava hash i veličinu fajla', str_contains($service, "hash_file('sha256', \$finalPath)") && str_contains($service, 'clearstatcache'));
$check('Glavna slika je uvek prva', str_contains($service, 'setPrimary') && str_contains($service, 'array_unshift($orderedIds') && str_contains($service, "where('is_primary', true)"));
$check('Reorder čuva sve slike i odbacuje tuđe ID-jeve', str_contains($service, '$existingLookup') && str_contains($service, 'Neposlati ID-jevi se dodaju na kraj') && str_contains($service, "whereNull('product_variant_id')"));
$check('Image akcije imaju JSON odgovore', substr_count($controller, '$request->expectsJson()') >= 5 && str_contains($controller, "'reload' => \$wasLegacy"));

$form = $source('resources/views/admin/products/form.blade.php');
$gallery = $source('resources/views/admin/products/images.blade.php');
$imageCard = $source('resources/views/admin/products/partials/image-card.blade.php');
$upload = $source('resources/views/admin/products/partials/image-upload.blade.php');
$js = $source('public/assets/js/product-media-manager.js');
$css = $source('public/assets/css/app.css');
$check('Forma ima redosled naziv slike specifikacije', strpos($form, '<h2>Naziv artikla</h2>') < strpos($form, '<h2>Dodavanje slika</h2>') && strpos($form, '<h2>Dodavanje slika</h2>') < strpos($form, '<h2>Specifikacije</h2>'));
$check('Upload prikazuje izbor fotografija', str_contains($upload, 'data-image-selection-grid') && str_contains($upload, 'data-image-selection-count') && str_contains($js, 'renderSelection'));
$check('Upload ima procenat i progress bar', str_contains($upload, 'data-image-upload-percent') && str_contains($upload, 'data-image-upload-bar') && str_contains($js, 'xhr.upload.addEventListener'));
$check('Upload vraća JSON redirect za product formu', str_contains($form, 'data-image-upload-mode="redirect"') && str_contains($js, 'payload.redirect_url'));
$check('Glavna slika se bira zvezdicom preko slike', str_contains($imageCard, 'image-primary-control') && str_contains($imageCard, 'form="primary-') && str_contains($imageCard, 'name="star"'));
$check('Drag and drop radi pointer događajima za touch i desktop', str_contains($imageCard, 'data-image-drag-handle') && str_contains($js, "addEventListener('pointerdown'") && str_contains($js, "addEventListener('pointermove'") && str_contains($css, 'touch-action:none'));
$check('Raspored se automatski čuva', str_contains($gallery, 'data-reorder-url') && str_contains($js, "data.append('image_ids[]'") && str_contains($controller, "\$request->has('image_ids')"));
$check('Rotacija je dostupna ulevo i udesno', str_contains($imageCard, '↶ 90°') && str_contains($imageCard, '↷ 90°') && str_contains($form, 'rotate-left-') && str_contains($gallery, 'rotate-right-'));

$request = $source('app/Http/Requests/ProductRequest.php');
$field = $source('app/Models/SpecificationField.php');
$adminService = $source('app/Services/ProductAdminService.php');
$check('Disk polje prepoznaje SSD HDD storage i disk', str_contains($field, 'isRepeatableStorageField') && str_contains($field, "str_contains(\$needle, 'ssd')") && str_contains($field, "str_contains(\$needle, 'hdd')"));
$check('Specifikacije podržavaju do osam diskova', str_contains($form, 'data-repeatable-storage') && str_contains($form, 'Dodaj još jedan disk') && str_contains($js, 'najviše 8 diskova'));
$check('Više diskova čuva tip i kapacitet uz kompatibilan prikaz', str_contains($request, "implode(' + ', array_filter(\$display") && str_contains($request, 'spec_capacities') && str_contains($request, 'spec_structured') && str_contains($adminService, 'value_json'));
$check('Select diskovi validiraju svaku pojedinačnu vrednost', str_contains($request, '$allowedValues') && str_contains($request, 'Izabrana opcija diska'));

$catalogIndex = $source('resources/views/catalog/index.blade.php');
$catalogShow = $source('resources/views/catalog/show.blade.php');
$check('Kartica kataloga ima download pune rezolucije', str_contains($catalogIndex, 'catalog-card-download') && str_contains($catalogIndex, 'download_url'));
$check('Galerija ima download uz glavni prikaz i Zoom', str_contains($catalogShow, 'data-gallery-download-link') && str_contains($catalogShow, 'data-lightbox-download') && str_contains($catalogShow, 'Puna rezolucija'));

$doctor = $source('app/Console/Commands/ProductMediaDoctorCommand.php');
$release = require $root.'/config/release.php';
$check('Product media doctor proverava formate i rute', str_contains($doctor, 'app:product-media-doctor') && str_contains($doctor, 'image/webp') && str_contains($doctor, 'media.product.download'));
$check('Stable release profil uključuje product media proveru', in_array('product_media', (array) ($release['profiles']['stable'] ?? []), true) && (($release['checks']['product_media']['command'] ?? '') === 'app:product-media-doctor'));
$check('v2.1.3 migracije za strukturisane diskove i izvedeni zbir su prisutne', is_file($root.'/database/migrations/2026_08_04_000033_create_catalog_type_layout_v2_1_3.php') && is_file($root.'/database/migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php') && count(glob($root.'/database/migrations/*2_1_3*.php') ?: []) === 3);

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("Product Media UX smoke: %d/%d uspesno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
