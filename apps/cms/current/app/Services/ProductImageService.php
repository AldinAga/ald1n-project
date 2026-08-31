<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class ProductImageService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ProductImageDerivativeService $derivatives,
    ) {}

    /** @param list<UploadedFile> $files */
    public function upload(Product $product, array $files): int
    {
        return $this->uploadInternal($product, $files);
    }


    /** @param list<UploadedFile> $files */
    private function uploadInternal(Product $product, array $files): int
    {
        $created = 0;
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) continue;
            $sourcePath = $file->getRealPath();
            $fileHash = is_string($sourcePath) && is_file($sourcePath) ? (hash_file('sha256', $sourcePath) ?: null) : null;
            $directory = 'products/'.$product->id;
            $path = $file->store($directory, 'public');
            if (!is_string($path)) throw new RuntimeException('Slika nije mogla biti sačuvana.');
            $scope = ProductImage::query()->where('product_id', $product->id);
            $isFirst = !$scope->exists();
            $createdImage = ProductImage::query()->create([
                'product_id' => $product->id,
                'file_path' => $path,
                'storage_disk' => 'public',
                'original_filename' => mb_substr((string) $file->getClientOriginalName(), 0, 255),
                'mime_type' => (string) ($file->getMimeType() ?: $file->getClientMimeType()),
                'file_size' => (int) $file->getSize(),
                'file_hash' => $fileHash,
                'rotation_degrees' => 0,
                'sort_order' => (int) (clone $scope)->max('sort_order') + 10,
                'is_primary' => $isFirst,
                'created_at' => now(),
            ]);
            $this->derivatives->ensureSafe($createdImage);
            $created++;
        }
        if ($created > 0) {
            $this->audit->log('product.images.uploaded', 'Dodate slike artikla '.$product->sku, $product, metadata: ['count' => $created, 'product_id' => $product->id]);
        }
        return $created;
    }

    public function cloneImages(Product $source, Product $target): int
    {
        $source->loadMissing('images');
        $created = 0;
        foreach ($source->images as $image) {
            $path = (string) $image->file_path;
            $disk = (string) $image->storage_disk;
            if ($disk === 'public') {
                if (!Storage::disk('public')->exists($path)) continue;
                $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
                $newPath = 'products/'.$target->id.'/'.Str::uuid().'.'.$extension;
                Storage::disk('public')->makeDirectory('products/'.$target->id);
                if (!Storage::disk('public')->copy($path, $newPath)) continue;
                $path = $newPath;
            } elseif ($disk !== 'legacy') {
                continue;
            }

            $createdImage = ProductImage::query()->create([
                'product_id' => $target->id,
                'file_path' => $path,
                'storage_disk' => $disk,
                'original_filename' => $image->original_filename,
                'mime_type' => $image->mime_type,
                'file_size' => $image->file_size,
                'file_hash' => $image->file_hash,
                'rotation_degrees' => $image->rotation_degrees,
                'sort_order' => $image->sort_order,
                'is_primary' => $image->is_primary,
                'created_at' => now(),
            ]);
            $this->derivatives->ensureSafe($createdImage);
            $created++;
        }
        if ($created > 0) $this->audit->log('product.images.cloned', 'Klonirane slike sa artikla '.$source->sku.' na '.$target->sku, $target, metadata: ['source_product_id' => $source->id, 'count' => $created]);
        return $created;
    }


    public function setPrimary(Product $product, ProductImage $image): void
    {
        $this->assertOwner($product, $image);
        DB::transaction(function () use ($product, $image): void {
            $orderedIds = ProductImage::query()
                ->where('product_id', $product->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
            $orderedIds = array_values(array_unique(array_merge([(int) $image->id], $orderedIds)));

            ProductImage::query()->where('product_id', $product->id)->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
            $this->applyOrder($product, $orderedIds);
        });
        $this->audit->log('product.image.primary', 'Promenjena glavna slika artikla '.$product->sku, $product, metadata: ['image_id' => $image->id]);
    }

    public function rotate(Product $product, ProductImage $image, int $degrees): void
    {
        $this->assertOwner($product, $image);
        $degrees = (($degrees % 360) + 360) % 360;
        if (!in_array($degrees, [90, 180, 270], true)) {
            throw new RuntimeException('Rotacija mora biti 90, 180 ili 270 stepeni.');
        }
        $source = $this->sourceAbsolutePath($image);
        $mime = $this->supportedMime($source, (string) $image->mime_type);
        $temporary = $this->temporaryCopy($source);
        $newPublicPath = null;

        try {
            $this->rotatePhysicalFile($temporary, $mime, $degrees);

            if ($image->storage_disk === 'public') {
                $destination = Storage::disk('public')->path((string) $image->file_path);
                if (!@rename($temporary, $destination)) {
                    if (!@copy($temporary, $destination)) {
                        throw new RuntimeException('Rotirana slika nije mogla da zameni postojeći fajl.');
                    }
                    @unlink($temporary);
                }
            } else {
                $extension = $this->extensionForMime($mime);
                $newPublicPath = 'products/'.$product->id.'/'.Str::uuid().'.'.$extension;
                Storage::disk('public')->makeDirectory('products/'.$product->id);
                $destination = Storage::disk('public')->path($newPublicPath);
                if (!@rename($temporary, $destination)) {
                    if (!@copy($temporary, $destination)) {
                        throw new RuntimeException('Lokalna kopija legacy slike nije mogla biti sačuvana.');
                    }
                    @unlink($temporary);
                }
            }

            @chmod($destination, 0644);
            clearstatcache(true, $destination);

            $finalPath = $image->storage_disk === 'public'
                ? Storage::disk('public')->path((string) $image->file_path)
                : Storage::disk('public')->path((string) $newPublicPath);

            $wasLegacy = $image->storage_disk === 'legacy';
            if ($wasLegacy) {
                $image->storage_disk = 'public';
                $image->file_path = (string) $newPublicPath;
                $image->original_filename = 'rotated-'.($image->original_filename ?: 'legacy-image.'.$this->extensionForMime($mime));
            }
            $image->mime_type = $mime;
            $image->file_size = is_file($finalPath) ? (int) filesize($finalPath) : 0;
            $image->file_hash = is_file($finalPath) ? (hash_file('sha256', $finalPath) ?: null) : null;
            $image->rotation_degrees = ((int) $image->rotation_degrees + $degrees) % 360;
            $image->save();
            $this->derivatives->refreshSafe($image);

            $this->audit->log(
                'product.image.rotated',
                'Rotirana slika artikla '.$product->sku,
                $product,
                metadata: [
                    'image_id' => $image->id,
                    'degrees' => $degrees,
                    'legacy_copy_on_write' => $wasLegacy,
                ],
            );
        } catch (\Throwable $exception) {
            if (is_file($temporary)) @unlink($temporary);
            if ($newPublicPath !== null) Storage::disk('public')->delete($newPublicPath);
            throw $exception;
        }
    }

    /** @param list<int> $orderedIds */
    public function reorder(Product $product, array $orderedIds): void
    {
        $existingIds = ProductImage::query()
            ->where('product_id', $product->id)

            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $existingLookup = array_fill_keys($existingIds, true);
        $orderedIds = array_values(array_unique(array_filter(
            array_map('intval', $orderedIds),
            static fn (int $id): bool => isset($existingLookup[$id]),
        )));

        // Neposlati ID-jevi se dodaju na kraj. Tako parcijalni/malformirani zahtev
        // ne može izgubiti sliku iz rasporeda niti napraviti duple sort vrednosti.
        foreach ($existingIds as $existingId) {
            if (!in_array($existingId, $orderedIds, true)) {
                $orderedIds[] = $existingId;
            }
        }

        $primaryId = ProductImage::query()
            ->where('product_id', $product->id)

            ->where('is_primary', true)
            ->value('id');
        if ($primaryId !== null) {
            $orderedIds = array_values(array_filter($orderedIds, static fn (int $id): bool => $id !== (int) $primaryId));
            array_unshift($orderedIds, (int) $primaryId);
        }

        DB::transaction(fn () => $this->applyOrder($product, $orderedIds));
        $this->audit->log('product.images.reordered', 'Promenjen redosled slika artikla '.$product->sku, $product, metadata: ['image_ids' => $orderedIds]);
    }

    /** @param list<int> $orderedIds */
    private function applyOrder(Product $product, array $orderedIds): void
    {
        $valid = ProductImage::query()
            ->where('product_id', $product->id)

            ->whereIn('id', $orderedIds)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        foreach ($orderedIds as $index => $id) {
            if (in_array($id, $valid, true)) {
                ProductImage::query()->whereKey($id)->update(['sort_order' => ($index + 1) * 10]);
            }
        }
    }

    public function delete(Product $product, ProductImage $image): void
    {
        $this->assertOwner($product, $image);
        abort_if($image->storage_disk !== 'public', 422, 'Legacy fajl je read-only. Rotiraj ga prvo ako želiš lokalnu kopiju.');
        $wasPrimary = (bool) $image->is_primary;
        $this->derivatives->delete($image);
        Storage::disk('public')->delete($image->file_path);
        $imageId = $image->id;
        $image->delete();
        if ($wasPrimary) {
            $next = ProductImage::query()->where('product_id', $product->id)->orderBy('sort_order')->orderBy('id')->first();
            $next?->update(['is_primary' => true]);
        }
        $this->audit->log('product.image.deleted', 'Uklonjena slika artikla '.$product->sku, $product, metadata: ['image_id' => $imageId]);
    }

    private function assertOwner(Product $product, ProductImage $image): void
    {
        abort_unless((int) $image->product_id === (int) $product->id, 404);
    }

    private function sourceAbsolutePath(ProductImage $image): string
    {
        if ($image->storage_disk === 'public') {
            $path = Storage::disk('public')->path((string) $image->file_path);
            if (!is_file($path)) throw new RuntimeException('Lokalni fajl slike ne postoji.');
            return $path;
        }

        if ($image->storage_disk !== 'legacy') {
            throw new RuntimeException('Storage slike nije podržan za rotaciju.');
        }

        $relative = ltrim(str_replace('\\', '/', trim((string) $image->file_path)), '/');
        if ($relative === '' || str_contains($relative, '..') || !str_starts_with($relative, 'uploads/products/')) {
            throw new RuntimeException('Legacy putanja slike nije bezbedna.');
        }

        $configuredRoot = trim((string) config('services.legacy_media.root'));
        $root = $configuredRoot !== '' ? realpath($configuredRoot) : false;
        if (!is_string($root) || !is_dir($root)) {
            throw new RuntimeException('Legacy media root nije podešen.');
        }

        $allowedRoot = realpath($root.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'products');
        $absolute = realpath($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative));
        if (!is_string($allowedRoot) || !is_string($absolute) || !is_file($absolute)
            || !str_starts_with($absolute, rtrim($allowedRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Legacy slika nije pronađena ili nije dozvoljena.');
        }

        return $absolute;
    }

    private function temporaryCopy(string $source): string
    {
        $directory = storage_path('app/tmp/image-rotation');
        if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Privremeni direktorijum za rotaciju nije dostupan.');
        }
        $temporary = tempnam($directory, 'rotate-');
        if (!is_string($temporary) || !@copy($source, $temporary)) {
            throw new RuntimeException('Privremena kopija slike nije mogla biti napravljena.');
        }
        return $temporary;
    }

    private function supportedMime(string $path, string $declared): string
    {
        $detected = mime_content_type($path) ?: $declared;
        if (!in_array($detected, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('Format slike nije podržan za rotaciju.');
        }
        return $detected;
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new RuntimeException('Format slike nije podržan.'),
        };
    }

    private function rotatePhysicalFile(string $path, string $mime, int $degrees): void
    {
        if (class_exists(\Imagick::class)) {
            $this->rotateWithImagick($path, $mime, $degrees);
            return;
        }

        if (!extension_loaded('gd') || !function_exists('imagerotate')) {
            throw new RuntimeException('Rotacija zahteva PHP Imagick ili GD ekstenziju.');
        }

        $createFunction = match ($mime) {
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp',
            default => null,
        };
        $saveFunction = match ($mime) {
            'image/jpeg' => 'imagejpeg',
            'image/png' => 'imagepng',
            'image/webp' => 'imagewebp',
            default => null,
        };
        if ($createFunction === null || $saveFunction === null || !function_exists($createFunction) || !function_exists($saveFunction)) {
            throw new RuntimeException('PHP obrada ne podržava format ove slike.');
        }

        $image = @$createFunction($path);
        if ($image === false) throw new RuntimeException('Slika nije mogla biti otvorena za rotaciju.');

        $background = imagecolorallocatealpha($image, 0, 0, 0, 127);
        $rotated = imagerotate($image, -$degrees, $background);
        imagedestroy($image);
        if ($rotated === false) throw new RuntimeException('Rotacija slike nije uspela.');
        imagesavealpha($rotated, true);

        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($rotated, $path, 92),
            'image/png' => imagepng($rotated, $path, 6),
            'image/webp' => imagewebp($rotated, $path, 92),
            default => false,
        };
        imagedestroy($rotated);
        if (!$ok) throw new RuntimeException('Rotirana slika nije mogla biti sačuvana.');
        clearstatcache(true, $path);
    }

    private function rotateWithImagick(string $path, string $mime, int $degrees): void
    {
        try {
            $image = new \Imagick($path);
            if (method_exists($image, 'autoOrientImage')) {
                $image->autoOrientImage();
            }
            $image->setImageBackgroundColor(new \ImagickPixel('transparent'));
            $image->rotateImage(new \ImagickPixel('transparent'), $degrees);
            $image->setImageFormat(match ($mime) {
                'image/jpeg' => 'jpeg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => throw new RuntimeException('Format slike nije podržan.'),
            });
            if ($mime !== 'image/png') {
                $image->setImageCompressionQuality(92);
            }
            $image->writeImage($path);
            $image->clear();
            $image->destroy();
            clearstatcache(true, $path);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Imagick rotacija nije uspela: '.$exception->getMessage(), 0, $exception);
        }
    }
}
