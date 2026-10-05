<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class ProductImagePublicationService
{
    public function __construct(
        private readonly ProductImageDerivativeService $derivatives,
    ) {}

    public function publish(ProductImage $image): ProductImage
    {
        $image = $image->fresh() ?? $image;
        $diskName = trim((string) $image->storage_disk);

        if ($diskName === 'public') {
            $source = $this->derivatives->sourceAbsolutePath($image);
            $this->supportedMime($source);
            if (!$this->derivatives->ensureSafe($image)) {
                throw new RuntimeException('Optimizovane verzije javne slike nisu mogle biti generisane.');
            }
            return $image->fresh() ?? $image;
        }

        if ($diskName !== 'legacy') {
            throw new RuntimeException('Storage slike nije podržan za publication: '.$diskName);
        }

        $source = $this->derivatives->sourceAbsolutePath($image);
        $mime = $this->supportedMime($source);
        $extension = $this->extensionForMime($mime);
        $relative = 'products/'.(int) $image->product_id.'/legacy-'.(int) $image->getKey().'.'.$extension;
        $disk = Storage::disk('public');
        $target = $disk->path($relative);
        $directory = dirname($target);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Public direktorijum za sliku nije moguće napraviti.');
        }

        $temporary = $target.'.tmp-'.bin2hex(random_bytes(6));
        $switched = false;

        try {
            if (!@copy($source, $temporary) || !is_file($temporary) || (int) filesize($temporary) < 1) {
                throw new RuntimeException('Legacy slika nije mogla biti kopirana u public storage.');
            }
            @chmod($temporary, 0644);
            $hash = hash_file('sha256', $temporary);
            if (!is_string($hash) || $hash === '') {
                throw new RuntimeException('SHA-256 public kopije nije mogao biti izračunat.');
            }
            $size = (int) filesize($temporary);

            if (!@rename($temporary, $target)) {
                if (!@copy($temporary, $target)) {
                    throw new RuntimeException('Public kopija slike nije mogla biti atomically instalirana.');
                }
                @unlink($temporary);
            }
            @chmod($target, 0644);
            clearstatcache(true, $target);

            $published = DB::transaction(function () use ($image, $relative, $mime, $size, $hash): ProductImage {
                $locked = ProductImage::query()->lockForUpdate()->findOrFail($image->getKey());
                if ((string) $locked->storage_disk !== 'legacy') {
                    throw new RuntimeException('Slika je promenjena tokom publication postupka.');
                }
                $locked->forceFill([
                    'storage_disk' => 'public',
                    'file_path' => $relative,
                    'mime_type' => $mime,
                    'file_size' => $size,
                    'file_hash' => $hash,
                ])->save();
                return $locked->fresh() ?? $locked;
            });
            $switched = true;

            if (!$this->derivatives->refreshSafe($published)) {
                throw new RuntimeException('Public original je sačuvan, ali derivati nisu mogli biti generisani.');
            }

            return $published->fresh() ?? $published;
        } catch (Throwable $exception) {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
            if (!$switched && is_file($target)) {
                @unlink($target);
            }
            throw $exception;
        }
    }

    private function supportedMime(string $path): string
    {
        $mime = mime_content_type($path) ?: '';
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('Nepodržan MIME format slike: '.($mime !== '' ? $mime : 'unknown'));
        }
        return $mime;
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new RuntimeException('Nepodržan MIME format slike.'),
        };
    }
}
