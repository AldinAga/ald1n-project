<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Output of SvgSanitizer: the canonical re-serialised SVG, its viewBox size
 * and how many items of each category were removed (counts only).
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SvgSanitizeResult
{
    /** @param array<string,int> $removed category => count, only categories with a count above zero */
    public function __construct(
        public readonly string $svg,
        public readonly array $removed,
        public readonly float $width,
        public readonly float $height,
    ) {
    }

    public function removedTotal(): int
    {
        return array_sum($this->removed);
    }

    public function sha256(): string
    {
        return hash('sha256', $this->svg);
    }
}
