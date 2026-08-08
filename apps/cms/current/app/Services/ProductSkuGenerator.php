<?php

declare(strict_types=1);

namespace App\Services;

final class ProductSkuGenerator
{
    private const MAX_LENGTH = 100;

    /** @param list<string> $parts @param callable(string):bool $exists */
    public function generate(array $parts, callable $exists): string
    {
        $tokens = [];
        $seen = [];
        foreach ($parts as $part) {
            $normalized = $this->normalizePart($part);
            if ($normalized === '') continue;
            foreach (explode('-', $normalized) as $token) {
                if ($token === '' || isset($seen[$token])) continue;
                $seen[$token] = true;
                $tokens[] = $token;
            }
        }

        $base = $this->limit($tokens === [] ? 'ARTIKAL' : implode('-', $tokens), self::MAX_LENGTH);
        $candidate = $base;
        $counter = 2;
        while ($exists($candidate)) {
            $suffix = '-'.$counter++;
            $candidate = $this->limit($base, self::MAX_LENGTH - strlen($suffix)).$suffix;
        }
        return $candidate;
    }

    private function normalizePart(string $value): string
    {
        $value = strtr(trim($value), [
            'č'=>'c','ć'=>'c','š'=>'s','ž'=>'z','đ'=>'dj','Č'=>'C','Ć'=>'C','Š'=>'S','Ž'=>'Z','Đ'=>'DJ',
        ]);
        if ($value === '') return '';
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($converted) && $converted !== '') $value = $converted;
        }
        return trim((string) preg_replace('/[^A-Z0-9]+/', '-', strtoupper($value)), '-');
    }

    private function limit(string $value, int $maximum): string
    {
        return rtrim(substr($value, 0, max(1, $maximum)), '-');
    }
}
