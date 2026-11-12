<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * An uploaded SVG that the strict sanitizer refuses outright. The reason is a
 * short stable code (see SvgSanitizer::REJECT_*); SiteLogoAssetService maps
 * it to a Serbian message. The SVG content itself is never part of it.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SvgSanitizerException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct('SVG rejected: '.$reason);
    }
}
