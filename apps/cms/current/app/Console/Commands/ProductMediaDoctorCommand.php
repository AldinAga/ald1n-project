<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProductMediaDoctorCommand extends Command
{
    protected $signature = 'app:product-media-doctor';

    protected $description = 'Proveri upload, rotaciju, download, glavnu sliku i reorder infrastrukturu galerije proizvoda.';

    public function handle(): int
    {
        $failed = false;

        if (!Schema::hasTable('product_images')) {
            $this->error('FAIL Nedostaje tabela product_images.');
            return self::FAILURE;
        }
        $this->info('PASS product_images je spremna.');

        foreach (['id', 'product_id', 'file_path', 'storage_disk', 'file_hash', 'rotation_degrees', 'sort_order', 'is_primary'] as $column) {
            if (!Schema::hasColumn('product_images', $column)) {
                $this->error('FAIL product_images nema kolonu '.$column.'.');
                $failed = true;
            }
        }
        if (!$failed) $this->info('PASS Kolone za galeriju, rotaciju i raspored postoje.');

        foreach (['media.product', 'media.product.download', 'admin.products.images.store', 'admin.products.images.primary', 'admin.products.images.rotate', 'admin.products.images.reorder'] as $route) {
            if (!Route::has($route)) {
                $this->error('FAIL Nedostaje ruta '.$route.'.');
                $failed = true;
            }
        }
        if (!$failed) $this->info('PASS Rute galerije i preuzimanja su registrovane.');

        $supportedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $this->info('PASS Podržani formati: '.implode(', ', $supportedMimes).'.');

        $imagick = class_exists(\Imagick::class);
        $gd = extension_loaded('gd')
            && function_exists('imagerotate')
            && function_exists('imagecreatefromjpeg')
            && function_exists('imagecreatefrompng')
            && function_exists('imagecreatefromwebp')
            && function_exists('imagewebp');
        if (!$imagick && !$gd) {
            $this->error('FAIL Rotacija zahteva Imagick ili GD sa JPEG/PNG/WebP podrškom.');
            $failed = true;
        } else {
            $this->info('PASS Obrada slika je dostupna: '.($imagick ? 'Imagick' : 'GD').'.');
        }

        try {
            $sample = ProductImage::query()->where('storage_disk', 'public')->orderByDesc('id')->first();
            if ($sample instanceof ProductImage) {
                $path = ltrim(str_replace('\\', '/', (string) $sample->file_path), '/');
                if (!Storage::disk('public')->exists($path)) {
                    $this->error('FAIL Poslednji javni zapis slike nema fajl: '.$path.'.');
                    $failed = true;
                } elseif (!is_string($sample->url) || !str_contains($sample->url, 'v=')) {
                    $this->error('FAIL URL slike nema cache-busting verziju.');
                    $failed = true;
                } elseif (!is_string($sample->download_url) || $sample->download_url === '') {
                    $this->error('FAIL Slika nema sigurnu download rutu.');
                    $failed = true;
                } else {
                    $this->info('PASS Primer javne slike, verzionisani URL i download ruta su validni.');
                }
            } else {
                $this->line('<fg=yellow>SKIP</> Nema javne slike za proveru konkretnog fajla.');
            }
        } catch (Throwable $exception) {
            $this->error('FAIL Provera medijskih fajlova nije uspela: '.$exception::class.': '.$exception->getMessage());
            $failed = true;
        }

        if ($failed) {
            $this->error('Galerija proizvoda nije spremna.');
            return self::FAILURE;
        }

        $this->info('Galerija proizvoda je spremna.');
        return self::SUCCESS;
    }
}
