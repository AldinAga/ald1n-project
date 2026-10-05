<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageDerivativeService;
use App\Services\ProductImagePublicationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class CatalogImageAvailabilityDoctorCommand extends Command
{
    protected $signature = 'app:catalog-image-availability-doctor {--sku=*} {--apply}';

    protected $description = 'Audit and safely publish selected legacy product images for Mobile presentation.';

    public function __construct(
        private readonly ProductImageDerivativeService $derivatives,
        private readonly ProductImagePublicationService $publisher,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $skus = array_values(array_unique(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) $this->option('sku'),
        ), static fn (string $value): bool => $value !== '')));

        if ($skus === []) {
            $this->error('FAIL Potreban je najmanje jedan --sku.');
            return self::FAILURE;
        }

        $before = $this->audit($skus, true);
        $this->emit($before, (bool) $this->option('apply') ? 'PRE_APPLY' : 'DRY_RUN');

        if (!(bool) $this->option('apply')) {
            return $before['hard_fail'] ? self::FAILURE : self::SUCCESS;
        }

        if ($before['hard_fail']) {
            $this->error('FAIL Apply je blokiran jer dry-run nije bezbedan.');
            return self::FAILURE;
        }

        foreach ($before['products'] as $row) {
            /** @var Product $product */
            $product = $row['product'];
            DB::transaction(function () use ($product): void {
                $images = ProductImage::query()
                    ->where('product_id', $product->id)
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                if ($images->isEmpty()) {
                    return;
                }
                $primaryCount = $images->where('is_primary', true)->count();
                if ($primaryCount !== 1) {
                    $primaryId = (int) $images->first()->id;
                    ProductImage::query()->where('product_id', $product->id)->update(['is_primary' => false]);
                    ProductImage::query()->whereKey($primaryId)->update(['is_primary' => true]);
                }
            });

            $images = ProductImage::query()
                ->where('product_id', $product->id)
                ->orderByDesc('is_primary')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
            foreach ($images as $image) {
                $this->publisher->publish($image);
            }
        }

        $after = $this->audit($skus, false);
        $this->emit($after, 'POST_APPLY');
        if ($after['hard_fail'] || $after['mobile_unsafe_products'] !== 0 || $after['missing_derivatives'] !== 0 || $after['legacy_images'] !== 0 || $after['primary_mismatch_products'] !== 0) {
            $this->error('FAIL Post-apply audit nije potpuno Mobile-safe.');
            return self::FAILURE;
        }

        $this->info('CATALOG_IMAGE_AVAILABILITY=PASS');
        return self::SUCCESS;
    }

    /** @param list<string> $skus @return array<string,mixed> */
    private function audit(array $skus, bool $includeModels): array
    {
        $rows = [];
        $missingProducts = 0;
        $imagelessProducts = 0;
        $invalidSources = 0;
        $unsupportedStorage = 0;
        $mobileUnsafe = 0;
        $legacyImages = 0;
        $publicImages = 0;
        $missingDerivatives = 0;
        $primaryMismatchProducts = 0;

        foreach ($skus as $sku) {
            $product = Product::query()->where('sku', $sku)->first();
            if (!$product instanceof Product) {
                $missingProducts++;
                $rows[] = ['sku' => $sku, 'missing' => true];
                continue;
            }

            $images = ProductImage::query()
                ->where('product_id', $product->id)
                ->orderByDesc('is_primary')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
            if ($images->isEmpty()) {
                $imagelessProducts++;
            }

            $primaryCount = $images->where('is_primary', true)->count();
            if (!$images->isEmpty() && $primaryCount !== 1) {
                $primaryMismatchProducts++;
            }

            $productInvalid = 0;
            $productUnsupported = 0;
            $productLegacy = 0;
            $productPublic = 0;
            $productMissingDerivatives = 0;

            foreach ($images as $image) {
                $disk = trim((string) $image->storage_disk);
                if ($disk === 'legacy') {
                    $legacyImages++;
                    $productLegacy++;
                } elseif ($disk === 'public') {
                    $publicImages++;
                    $productPublic++;
                } else {
                    $unsupportedStorage++;
                    $productUnsupported++;
                    continue;
                }

                try {
                    $source = $this->derivatives->sourceAbsolutePath($image);
                    $mime = mime_content_type($source) ?: '';
                    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                        $invalidSources++;
                        $productInvalid++;
                        continue;
                    }
                    if ($disk === 'public') {
                        $paths = ProductImageDerivativeService::relativePaths($image);
                        foreach (['thumbnail', 'display'] as $rendition) {
                            if (!Storage::disk('public')->exists($paths[$rendition])) {
                                $missingDerivatives++;
                                $productMissingDerivatives++;
                            }
                        }
                    }
                } catch (Throwable) {
                    $invalidSources++;
                    $productInvalid++;
                }
            }

            $safe = !$images->isEmpty()
                && $productInvalid === 0
                && $productUnsupported === 0
                && $productLegacy === 0
                && $productMissingDerivatives === 0
                && $primaryCount === 1;
            if (!$safe) {
                $mobileUnsafe++;
            }

            $rows[] = [
                'sku' => (string) $product->sku,
                'missing' => false,
                'product' => $includeModels ? $product : null,
                'images' => $images->count(),
                'legacy' => $productLegacy,
                'public' => $productPublic,
                'primary' => $primaryCount,
                'invalid' => $productInvalid,
                'unsupported' => $productUnsupported,
                'missing_derivatives' => $productMissingDerivatives,
                'mobile_safe' => $safe,
            ];
        }

        return [
            'products' => $rows,
            'target_products' => count($skus),
            'missing_products' => $missingProducts,
            'imageless_products' => $imagelessProducts,
            'invalid_source_images' => $invalidSources,
            'unsupported_storage_images' => $unsupportedStorage,
            'mobile_unsafe_products' => $mobileUnsafe,
            'legacy_images' => $legacyImages,
            'public_images' => $publicImages,
            'missing_derivatives' => $missingDerivatives,
            'primary_mismatch_products' => $primaryMismatchProducts,
            'hard_fail' => $missingProducts > 0 || $imagelessProducts > 0 || $invalidSources > 0 || $unsupportedStorage > 0,
        ];
    }

    /** @param array<string,mixed> $audit */
    private function emit(array $audit, string $mode): void
    {
        $this->line('CATALOG_IMAGE_DOCTOR_MODE='.$mode);
        foreach ($audit['products'] as $row) {
            if (($row['missing'] ?? false) === true) {
                $this->line('SKU='.$row['sku'].' FOUND=NO');
                continue;
            }
            $this->line(sprintf(
                'SKU=%s FOUND=YES IMAGES=%d LEGACY=%d PUBLIC=%d PRIMARY=%d INVALID=%d UNSUPPORTED=%d MISSING_DERIVATIVES=%d MOBILE_SAFE=%s',
                $row['sku'], $row['images'], $row['legacy'], $row['public'], $row['primary'], $row['invalid'], $row['unsupported'], $row['missing_derivatives'], $row['mobile_safe'] ? 'YES' : 'NO',
            ));
        }
        $this->line('TARGET_PRODUCTS='.$audit['target_products']);
        $this->line('MISSING_PRODUCTS='.$audit['missing_products']);
        $this->line('IMAGELESS_PRODUCTS='.$audit['imageless_products']);
        $this->line('INVALID_SOURCE_IMAGES='.$audit['invalid_source_images']);
        $this->line('UNSUPPORTED_STORAGE_IMAGES='.$audit['unsupported_storage_images']);
        $this->line('MOBILE_UNSAFE_PRODUCTS='.$audit['mobile_unsafe_products']);
        $this->line('LEGACY_IMAGES='.$audit['legacy_images']);
        $this->line('PUBLIC_IMAGES='.$audit['public_images']);
        $this->line('MISSING_DERIVATIVES='.$audit['missing_derivatives']);
        $this->line('PRIMARY_MISMATCH_PRODUCTS='.$audit['primary_mismatch_products']);
    }
}
