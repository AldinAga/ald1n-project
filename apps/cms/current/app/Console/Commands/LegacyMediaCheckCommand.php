<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;

final class LegacyMediaCheckCommand extends Command
{
    protected $signature = 'legacy:media-check {--limit=20 : Maksimalan broj nedostajućih putanja za prikaz}';
    protected $description = 'Proveri da li Laravel može bezbedno da pročita uvezene slike artikala';

    public function handle(): int
    {
        $legacyCount = ProductImage::query()->where('storage_disk', 'legacy')->count();
        if ($legacyCount === 0) {
            $this->info('LEGACY_MEDIA_NONE=PASS');
            $this->info('PASS Nema legacy image redova; legacy media root nije potreban za aktivni katalog.');
            return self::SUCCESS;
        }

        $configuredRoot = trim((string) config('services.legacy_media.root'));
        if ($configuredRoot === '') {
            $this->error('LEGACY_MEDIA_ROOT nije podešen u serverskom .env fajlu.');
            return self::FAILURE;
        }

        $root = realpath($configuredRoot);
        if (!is_string($root) || !is_dir($root.'/uploads/products')) {
            $this->error('Podešeni LEGACY_MEDIA_ROOT ne sadrži uploads/products: '.$configuredRoot);
            return self::FAILURE;
        }

        $total = 0;
        $missing = 0;
        $invalid = 0;
        $shown = 0;
        $limit = max(0, min(100, (int) $this->option('limit')));

        ProductImage::query()
            ->where('storage_disk', 'legacy')
            ->orderBy('id')
            ->chunkById(300, function ($images) use ($root, $limit, &$total, &$missing, &$invalid, &$shown): void {
                foreach ($images as $image) {
                    $total++;
                    $relative = ltrim(str_replace('\\', '/', trim((string) $image->file_path)), '/');
                    if ($relative === '' || str_contains($relative, '..') || !str_starts_with($relative, 'uploads/products/')) {
                        $invalid++;
                        if ($shown < $limit) {
                            $this->warn('Neispravna putanja za image ID '.$image->id.': '.$relative);
                            $shown++;
                        }
                        continue;
                    }

                    if (!is_file($root.'/'.$relative)) {
                        $missing++;
                        if ($shown < $limit) {
                            $this->warn('Nedostaje image ID '.$image->id.': '.$relative);
                            $shown++;
                        }
                    }
                }
            });

        $this->table(['Provera', 'Vrednost'], [
            ['Legacy slike u bazi', $total],
            ['Nedostajući fajlovi', $missing],
            ['Neispravne putanje', $invalid],
            ['Media root', $root],
        ]);

        if ($missing > 0 || $invalid > 0) {
            $this->error('FAIL Nisu sve uvezene slike dostupne.');
            return self::FAILURE;
        }

        $this->info('PASS Sve uvezene slike su dostupne.');
        return self::SUCCESS;
    }
}
