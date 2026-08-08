<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductImage;
use App\Services\CatalogAccessService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ProductMediaController extends Controller
{
    public function __invoke(Request $request, ProductImage $image, CatalogAccessService $access): BinaryFileResponse
    {
        abort_unless($image->storage_disk === 'legacy', 404);
        $product = $image->product()->firstOrFail();

        $canAdminister = $access->canManageImages($product, $request->user());
        $canViewCatalog = $product->status === 'active'
            && $product->deleted_at === null
            && $access->canView($product, $request->user());
        abort_unless($canAdminister || $canViewCatalog, 404);

        $relative = ltrim(str_replace('\\', '/', trim((string) $image->file_path)), '/');
        abort_if($relative === '' || str_contains($relative, '..') || !str_starts_with($relative, 'uploads/products/'), 404);

        $configuredRoot = trim((string) config('services.legacy_media.root'));
        $root = $configuredRoot !== '' ? realpath($configuredRoot) : false;
        abort_unless(is_string($root) && is_dir($root), 404, 'Legacy media root nije podešen.');

        $absolute = realpath($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative));
        $allowedRoot = realpath($root.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'products');
        abort_unless(
            is_string($absolute)
            && is_file($absolute)
            && is_string($allowedRoot)
            && str_starts_with($absolute, rtrim($allowedRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR),
            404,
        );

        $mime = mime_content_type($absolute) ?: 'application/octet-stream';
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        return response()->file($absolute, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
