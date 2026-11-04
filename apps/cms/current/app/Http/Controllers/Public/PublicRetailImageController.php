<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use App\Services\PublicRetailService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PublicRetailImageController extends Controller
{
    public function __invoke(ProductImage $image, PublicRetailService $retail): BinaryFileResponse
    {
        $retail->requireEnabled();
        abort_unless($image->storage_disk === 'legacy'
            && $retail->publicProducts()->whereKey($image->product_id)->exists(), 404);
        $path = ltrim(str_replace('\\', '/', trim((string) $image->file_path)), '/');
        abort_unless(str_starts_with($path, 'uploads/products/') && !in_array('..', explode('/', $path), true), 404);
        $rootPath = trim((string) config('services.legacy_media.root'));
        $root = $rootPath !== '' ? realpath($rootPath) : false;
        abort_unless(is_string($root) && is_dir($root), 404);
        $allowed = realpath($root.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'products');
        $absolute = realpath($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path));
        abort_unless(is_string($allowed) && is_string($absolute) && is_file($absolute)
            && str_starts_with($absolute, rtrim($allowed, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR), 404);
        $mime = mime_content_type($absolute) ?: '';
        abort_unless(in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true), 404);
        return response()->file($absolute, [
            'Content-Type' => $mime, 'Cache-Control' => 'public, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
