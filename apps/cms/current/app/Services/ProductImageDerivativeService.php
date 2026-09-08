<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class ProductImageDerivativeService
{
    public const THUMBNAIL_MAX_EDGE = 512;
    public const DISPLAY_MAX_EDGE = 1600;
    private const THUMBNAIL_QUALITY = 78;
    private const DISPLAY_QUALITY = 84;

    /** @return array{thumbnail:string,display:string} */
    public static function relativePaths(ProductImage $image): array
    {
        $productId = (int) $image->product_id;
        $imageId = (int) $image->getKey();
        if ($productId < 1 || $imageId < 1) {
            throw new RuntimeException('Slika nema validan product/image identitet.');
        }

        $base = 'products/_optimized/'.$productId.'/'.$imageId;
        return [
            'thumbnail' => $base.'/thumbnail.webp',
            'display' => $base.'/display.webp',
        ];
    }

    public function supported(): bool
    {
        return class_exists(\Imagick::class)
            || (extension_loaded('gd') && function_exists('imagewebp'));
    }

    public function sourceAbsolutePath(ProductImage $image): string
    {
        if ($image->storage_disk === 'public') {
            $relative = ltrim(str_replace('\\', '/', trim((string) $image->file_path)), '/');
            if ($relative === '' || str_contains($relative, '..')) {
                throw new RuntimeException('Javna putanja originalne slike nije bezbedna.');
            }
            $path = Storage::disk('public')->path($relative);
            if (!is_file($path)) {
                throw new RuntimeException('Originalni javni fajl slike ne postoji.');
            }
            return $path;
        }

        if ($image->storage_disk !== 'legacy') {
            throw new RuntimeException('Storage originalne slike nije podržan.');
        }

        $relative = ltrim(str_replace('\\', '/', trim((string) $image->file_path)), '/');
        if ($relative === '' || str_contains($relative, '..') || !str_starts_with($relative, 'uploads/products/')) {
            throw new RuntimeException('Legacy putanja originalne slike nije bezbedna.');
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
            throw new RuntimeException('Legacy originalna slika nije pronađena ili nije dozvoljena.');
        }

        return $absolute;
    }

    /** @return array{generated:int,existing:int} */
    public function ensure(ProductImage $image, bool $force = false): array
    {
        if (!$this->supported()) {
            throw new RuntimeException('Optimizacija fotografija zahteva PHP Imagick ili GD sa WebP podrškom.');
        }

        $source = $this->sourceAbsolutePath($image);
        $paths = self::relativePaths($image);
        $renditions = [
            'thumbnail' => [self::THUMBNAIL_MAX_EDGE, self::THUMBNAIL_QUALITY],
            'display' => [self::DISPLAY_MAX_EDGE, self::DISPLAY_QUALITY],
        ];
        $generated = 0;
        $existing = 0;

        foreach ($renditions as $rendition => [$maxEdge, $quality]) {
            $relative = $paths[$rendition];
            if (!$force && Storage::disk('public')->exists($relative)) {
                $existing++;
                continue;
            }
            $target = Storage::disk('public')->path($relative);
            $this->renderWebp($source, $target, $maxEdge, $quality);
            $generated++;
        }

        return ['generated' => $generated, 'existing' => $existing];
    }

    public function ensureSafe(ProductImage $image, bool $force = false): bool
    {
        try {
            $this->ensure($image, $force);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function refreshSafe(ProductImage $image): bool
    {
        $this->delete($image);
        return $this->ensureSafe($image, true);
    }

    public function delete(ProductImage $image): void
    {
        try {
            $paths = self::relativePaths($image);
            Storage::disk('public')->deleteDirectory(dirname($paths['thumbnail']));
        } catch (Throwable) {
            // Derivatives are disposable cache files; original image lifecycle must continue.
        }
    }

    private function renderWebp(string $source, string $target, int $maxEdge, int $quality): void
    {
        $directory = dirname($target);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Direktorijum za optimizovanu fotografiju nije moguće napraviti.');
        }

        $temporary = $target.'.tmp-'.bin2hex(random_bytes(6));
        try {
            if (class_exists(\Imagick::class)) {
                $this->renderWithImagick($source, $temporary, $maxEdge, $quality);
            } else {
                $this->renderWithGd($source, $temporary, $maxEdge, $quality);
            }

            if (!is_file($temporary) || (int) filesize($temporary) < 1) {
                throw new RuntimeException('Optimizovana fotografija nije generisana.');
            }
            if (!@rename($temporary, $target)) {
                if (!@copy($temporary, $target)) {
                    throw new RuntimeException('Optimizovana fotografija nije mogla biti atomically instalirana.');
                }
                @unlink($temporary);
            }
            @chmod($target, 0644);
            clearstatcache(true, $target);
        } catch (Throwable $exception) {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
            throw $exception;
        }
    }

    private function renderWithImagick(string $source, string $target, int $maxEdge, int $quality): void
    {
        try {
            $image = new \Imagick($source);
            if ($image->getNumberImages() > 1) {
                $image->setIteratorIndex(0);
            }
            $this->normalizeImagickOrientation($image);
            $width = max(1, $image->getImageWidth());
            $height = max(1, $image->getImageHeight());
            if (max($width, $height) > $maxEdge) {
                $image->thumbnailImage($maxEdge, $maxEdge, true, true);
            }
            $image->stripImage();
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality($quality);
            $image->setOption('webp:method', '4');
            $image->writeImage($target);
            $image->clear();
            $image->destroy();
        } catch (Throwable $exception) {
            throw new RuntimeException('Imagick optimizacija nije uspela: '.$exception->getMessage(), 0, $exception);
        }
    }

    private function normalizeImagickOrientation(\Imagick $image): void
    {
        if (method_exists($image, 'autoOrient')) {
            $image->autoOrient();
            $image->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
            return;
        }

        if (method_exists($image, 'autoOrientImage')) {
            $image->autoOrientImage();
            $image->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
            return;
        }

        $orientation = $image->getImageOrientation();
        $transparent = new \ImagickPixel('transparent');

        switch ($orientation) {
            case \Imagick::ORIENTATION_TOPRIGHT:
                $image->flopImage();
                break;
            case \Imagick::ORIENTATION_BOTTOMRIGHT:
                $image->rotateImage($transparent, 180);
                break;
            case \Imagick::ORIENTATION_BOTTOMLEFT:
                $image->flipImage();
                break;
            case \Imagick::ORIENTATION_LEFTTOP:
                $image->transposeImage();
                break;
            case \Imagick::ORIENTATION_RIGHTTOP:
                $image->rotateImage($transparent, 90);
                break;
            case \Imagick::ORIENTATION_RIGHTBOTTOM:
                $image->transverseImage();
                break;
            case \Imagick::ORIENTATION_LEFTBOTTOM:
                $image->rotateImage($transparent, -90);
                break;
        }

        $image->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
    }

    private function renderWithGd(string $source, string $target, int $maxEdge, int $quality): void
    {
        if (!extension_loaded('gd') || !function_exists('imagewebp')) {
            throw new RuntimeException('GD WebP podrška nije dostupna.');
        }

        $mime = mime_content_type($source) ?: '';
        $create = match ($mime) {
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp',
            default => null,
        };
        if ($create === null || !function_exists($create)) {
            throw new RuntimeException('GD ne podržava format originalne fotografije: '.$mime);
        }

        $input = @$create($source);
        if ($input === false) {
            throw new RuntimeException('Originalna fotografija nije mogla biti otvorena.');
        }
        $width = max(1, imagesx($input));
        $height = max(1, imagesy($input));
        $scale = min(1.0, $maxEdge / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $output = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($output === false) {
            imagedestroy($input);
            throw new RuntimeException('GD canvas nije mogao biti kreiran.');
        }

        imagealphablending($output, false);
        imagesavealpha($output, true);
        $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
        imagefill($output, 0, 0, $transparent);
        $copied = imagecopyresampled($output, $input, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagedestroy($input);
        if (!$copied) {
            imagedestroy($output);
            throw new RuntimeException('GD resize originalne fotografije nije uspeo.');
        }

        $saved = imagewebp($output, $target, $quality);
        imagedestroy($output);
        if (!$saved) {
            throw new RuntimeException('GD WebP fotografija nije mogla biti sačuvana.');
        }
    }
}
