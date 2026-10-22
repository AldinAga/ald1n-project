<?php

declare(strict_types=1);

namespace App\Support;

/** Presentation only: immutable order lines, never prices or live catalog lookups. */
final class OrderProductSummary
{
    /** @param iterable<string|null> $names
     *  @return array{product_name:string,other_products:int}
     */
    public static function fromNames(iterable $names): array
    {
        $first = null;
        $count = 0;
        foreach ($names as $name) {
            if ($count === 0) {
                $first = trim((string) $name);
                if ($first === '') $first = 'Artikal bez naziva';
            }
            $count++;
        }

        return [
            'product_name' => $first ?? 'Stavke nisu dostupne',
            'other_products' => max(0, $count - 1),
        ];
    }
}
