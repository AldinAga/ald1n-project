<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductImage;
use App\Services\CatalogAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ProductMediaDownloadController extends Controller
{
    public function __invoke(Request $request, ProductImage $image, CatalogAccessService $access): BinaryFileResponse
    {
        $product = $image->product()->firstOrFail();
        $canAdminister = $access->canManageImages($product, $request->user());
        $canViewCatalog = $product->status === 'active'
            && $product->deleted_at === null
            && $access->canView($product, $request->user());
        abort_unless($canAdminister || $canViewCatalog, 404);

        [$absolute, $mime] = $image->storage_disk === 'public'
            ? $this->publicFile($image)
            : $this->legacyFile($image);

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };
        $original = trim((string) $image->original_filename);
        $filename = $original !== ''
            ? basename(str_replace('\\', '/', $original))
            : Str::slug((string) $product->name).'-'.$image->id.'.'.$extension;
        if (!str_contains($filename, '.')) {
            $filename .= '.'.$extension;
        }

        return response()->download($absolute, $filename, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @return array{0:string,1:string} */
    private function publicFile(ProductImage $image): array
    {
        $relative = ltrim(str_replace('\\', '/', trim((string) $image->file_path)), '/');
        abort_if($relative === '' || str_contains($relative, '..') || !str_starts_with($relative, 'products/'), 404);
        abort_unless(Storage::disk('public')->exists($relative), 404);
        $absolute = Storage::disk('public')->path($relative);
        abort_unless(is_file($absolute), 404);

        return [$absolute, $this->safeMime($absolute)];
    }

    /** @return array{0:string,1:string} */
    private function legacyFile(ProductImage $image): array
    {
        abort_unless($image->storage_disk === 'legacy', 404);
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

        return [$absolute, $this->safeMime($absolute)];
    }

    private function safeMime(string $absolute): string
    {
        $mime = mime_content_type($absolute) ?: 'application/octet-stream';
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        return $mime;
    }
}
