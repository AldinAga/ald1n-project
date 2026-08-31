<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\CommissionCalculator;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewPrices = $request->user()?->can('catalog.view_prices') ?? false;
        $rate = app(SettingsService::class)->eurRsdRate();
        $commission = app(CommissionCalculator::class)->unitEur((float) $this->price_amount, (string) $this->price_currency, $this->manual_commission_eur !== null ? (float) $this->manual_commission_eur : null, $rate);

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->when($request->routeIs('api.v1.products.show'), $this->description),
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? ['id' => $this->brand->id, 'name' => $this->brand->name, 'slug' => $this->brand->slug] : null),
            'line' => $this->whenLoaded('line', fn () => $this->line ? ['id' => $this->line->id, 'name' => $this->line->name, 'slug' => $this->line->slug] : null),
            'model' => $this->model_name,
            'type' => $this->whenLoaded('type', fn () => $this->type ? ['id' => $this->type->id, 'name' => $this->type->name, 'slug' => $this->type->slug] : null),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug])->values()),
            'price' => $this->when($canViewPrices, ['amount' => (float) $this->price_amount, 'currency' => $this->price_currency]),
            'commission_eur' => $commission,
            'stock_quantity' => $this->stock_quantity,
            'primary_image_url' => $this->absoluteUrl($this->primaryImage?->url),
            'primary_image_original_url' => $this->absoluteUrl($this->primaryImage?->original_url),
            'primary_image_display_url' => $this->absoluteUrl($this->primaryImage?->display_url),
            'primary_image_thumbnail_url' => $this->absoluteUrl($this->primaryImage?->thumbnail_url),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $this->absoluteUrl($image->url),
                'original_url' => $this->absoluteUrl($image->original_url),
                'display_url' => $this->absoluteUrl($image->display_url),
                'thumbnail_url' => $this->absoluteUrl($image->thumbnail_url),
                'primary' => $image->is_primary,
            ])->values()),

            'specifications' => $this->whenLoaded('specificationValues', fn () => $this->specificationValues->filter(fn ($value) => $value->field !== null && $value->field->status === 'active')->map(fn ($value) => [
                'field' => $value->field?->name,
                'slug' => $value->field?->slug,
                'value' => $value->value_text ?? $value->value_number ?? $value->value_boolean,
                'detail' => $value->value_detail,
                'unit' => $value->field?->unit,
            ])->values()),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }

    private function absoluteUrl(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $value) === 1) {
            return $value;
        }
        if (str_starts_with($value, '//')) {
            return request()->getScheme().':'.$value;
        }
        return url('/'.ltrim($value, '/'));
    }
}
