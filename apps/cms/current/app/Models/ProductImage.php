<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\ProductImageDerivativeService;
use App\Support\ProductMediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProductImage extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'product_id', 'file_path', 'storage_disk', 'original_filename', 'mime_type', 'file_size',
        'file_hash', 'rotation_degrees', 'sort_order', 'is_primary', 'created_at',
    ];
    protected $casts = ['is_primary' => 'boolean', 'rotation_degrees' => 'integer', 'sort_order' => 'integer', 'created_at' => 'datetime'];
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    /**
     * Absolute, signed URL of the original photo (GET /media/p/{id}/original).
     * It does not depend on the /storage symlink or on a login, so the admin
     * pages, the mobile app and e-mail all use the same address. The version
     * in it follows the file (hash, size, rotation), so the URL is stable
     * between calls and changes when the photo is replaced or rotated.
     */
    public function getUrlAttribute(): ?string
    {
        try {
            $disk = trim((string) $this->storage_disk);
            $path = ltrim(str_replace('\\', '/', trim((string) $this->file_path)), '/');

            if (!in_array($disk, ['public', 'legacy'], true) || (int) $this->getKey() < 1) {
                return null;
            }
            if ($path === '' || in_array('..', explode('/', $path), true)) {
                return null;
            }

            return $this->signedUrl('original', $this->cacheVersion($path).'|original');
        } catch (Throwable) {
            // A malformed image row must never crash product presentation.
        }

        return null;
    }

    public function getOriginalUrlAttribute(): ?string
    {
        return $this->url;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->optimizedUrl('thumbnail') ?? $this->url;
    }

    public function getDisplayUrlAttribute(): ?string
    {
        return $this->optimizedUrl('display') ?? $this->url;
    }

    public function getDownloadUrlAttribute(): ?string
    {
        try {
            return (int) $this->getKey() > 0
                ? route('media.product.download', ['image' => (int) $this->getKey()])
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function optimizedUrl(string $rendition): ?string
    {
        try {
            $paths = ProductImageDerivativeService::relativePaths($this);
            $path = $paths[$rendition] ?? null;
            if (!is_string($path) || !Storage::disk('public')->exists($path)) {
                return null;
            }
            if ((int) $this->getKey() < 1) {
                return null;
            }

            return $this->signedUrl($rendition, $this->optimizedCacheVersion($path).'|'.$rendition);
        } catch (Throwable) {
            return null;
        }
    }

    private function signedUrl(string $rendition, string $versionSeed): ?string
    {
        $id = (int) $this->getKey();
        $version = ProductMediaUrl::version($versionSeed);
        $signature = ProductMediaUrl::signature($id, $rendition, $version);
        if ($signature === null) {
            return null;
        }

        return route('media.product.signed', ['image' => $id, 'rendition' => $rendition, 'v' => $version, 's' => $signature]);
    }

    private function optimizedCacheVersion(string $path): string
    {
        try {
            $disk = Storage::disk('public');
            $absolute = $disk->path($path);
            $modified = is_file($absolute) ? (int) filemtime($absolute) : 0;

            return substr(hash('sha256', implode('|', [
                $this->cacheVersion($path),
                $path,
                (string) $modified,
            ])), 0, 16);
        } catch (Throwable) {
            return $this->cacheVersion($path);
        }
    }

    private function cacheVersion(string $path): string
    {
        $version = trim((string) $this->file_hash);
        if ($version === '') {
            $version = hash('sha256', implode('|', [
                (string) $this->getKey(),
                $path,
                (string) $this->rotation_degrees,
                (string) $this->file_size,
            ]));
        }
        return substr($version, 0, 16);
    }
}
