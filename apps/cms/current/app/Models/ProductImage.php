<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProductImage extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'product_id', 'product_variant_id', 'file_path', 'storage_disk', 'original_filename', 'mime_type', 'file_size',
        'file_hash', 'rotation_degrees', 'sort_order', 'is_primary', 'created_at',
    ];
    protected $casts = ['is_primary' => 'boolean', 'rotation_degrees' => 'integer', 'sort_order' => 'integer', 'created_at' => 'datetime'];
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }

    public function getUrlAttribute(): ?string
    {
        try {
            $disk = trim((string) $this->storage_disk);
            $path = ltrim(str_replace('\\', '/', trim((string) $this->file_path)), '/');
            $url = null;

            if ($disk === 'public') {
                if ($path === '' || in_array('..', explode('/', $path), true)) {
                    return null;
                }
                $url = Storage::disk('public')->url($path);
            } elseif ($disk === 'legacy' && (int) $this->getKey() > 0) {
                $url = route('media.product', ['image' => (int) $this->getKey()]);
            }

            if (!is_string($url) || $url === '') {
                return null;
            }

            // Rotacija zadržava istu putanju javnog fajla. Verzija u URL-u sprečava
            // browser/CDN da nakon rotacije nastavi da prikazuje staru fotografiju.
            $version = trim((string) $this->file_hash);
            if ($version === '') {
                $version = hash('sha256', implode('|', [
                    (string) $this->getKey(),
                    $path,
                    (string) $this->rotation_degrees,
                    (string) $this->file_size,
                ]));
            }

            return $url.(str_contains($url, '?') ? '&' : '?').'v='.substr($version, 0, 16);
        } catch (Throwable) {
            // Neispravan pojedinačni zapis slike ne sme oboriti stranicu artikla.
        }

        return null;
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
}
