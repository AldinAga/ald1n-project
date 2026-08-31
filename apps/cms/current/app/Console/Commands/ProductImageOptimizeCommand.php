<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProductImage;
use App\Services\ProductImageDerivativeService;
use Illuminate\Console\Command;
use Throwable;

final class ProductImageOptimizeCommand extends Command
{
    protected $signature = 'app:product-image-optimize
        {--apply : Generate missing optimized derivatives}
        {--force : Regenerate derivatives even when they already exist}
        {--chunk=50 : Number of ProductImage rows processed per chunk}';

    protected $description = 'Generate disposable mobile display/thumbnail WebP derivatives without changing original product images.';

    public function handle(ProductImageDerivativeService $derivatives): int
    {
        if (!$derivatives->supported()) {
            $this->error('PRODUCT_IMAGE_OPTIMIZE_PROCESSOR=UNAVAILABLE');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $force = (bool) $this->option('force');
        $chunk = max(1, min(500, (int) $this->option('chunk')));
        $stats = [
            'total' => 0,
            'ready' => 0,
            'generated' => 0,
            'existing' => 0,
            'failed' => 0,
        ];

        ProductImage::query()->orderBy('id')->chunkById($chunk, function ($images) use ($derivatives, $apply, $force, &$stats): void {
            foreach ($images as $image) {
                $stats['total']++;
                try {
                    $derivatives->sourceAbsolutePath($image);
                    $stats['ready']++;
                    if ($apply) {
                        $result = $derivatives->ensure($image, $force);
                        $stats['generated'] += $result['generated'];
                        $stats['existing'] += $result['existing'];
                    }
                } catch (Throwable $exception) {
                    $stats['failed']++;
                    $this->warn('IMAGE_ID='.(int) $image->id.' ERROR='.$exception->getMessage());
                }
            }
        });

        $this->line('PRODUCT_IMAGE_OPTIMIZE_MODE='.($apply ? 'APPLY' : 'DRY_RUN'));
        $this->line('PRODUCT_IMAGE_OPTIMIZE_TOTAL='.$stats['total']);
        $this->line('PRODUCT_IMAGE_OPTIMIZE_READY='.$stats['ready']);
        $this->line('PRODUCT_IMAGE_OPTIMIZE_GENERATED='.$stats['generated']);
        $this->line('PRODUCT_IMAGE_OPTIMIZE_EXISTING='.$stats['existing']);
        $this->line('PRODUCT_IMAGE_OPTIMIZE_FAILED='.$stats['failed']);

        return $stats['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
