<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\SiteLogoAssetService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Content check for a site logo (PNG, JPG, WEBP or SVG) or favicon (PNG, ICO
 * or SVG) upload. The bytes decide: a renamed file of another format and an
 * SVG that the strict sanitizer refuses fail with a Serbian reason.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SiteLogoFile implements ValidationRule
{
    public function __construct(private readonly string $kind = SiteLogoAssetService::KIND_LOGO)
    {
    }

    public static function logo(): self
    {
        return new self(SiteLogoAssetService::KIND_LOGO);
    }

    public static function favicon(): self
    {
        return new self(SiteLogoAssetService::KIND_FAVICON);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $assets = app(SiteLogoAssetService::class);
        $label = SiteLogoAssetService::inputLabel($attribute);
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            $fail($label.': '.$assets->message('unreadable', $this->kind));

            return;
        }
        $code = $assets->inspect($value, $this->kind);
        if ($code !== null) {
            $fail($label.': '.$assets->message($code, $this->kind));
        }
    }
}
