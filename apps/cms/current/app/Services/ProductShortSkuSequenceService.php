<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ProductShortSkuSequenceService
{
    private const COUNTER_ID = 1;
    private const PREFIX_MAX_LENGTH = 14;
    private const SEQUENCE_WIDTH = 6;
    private const SEQUENCE_MAX = 999999;

    public function generate(string $prefix, callable $exists): string
    {
        $normalizedPrefix = $this->normalizePrefix($prefix);

        return DB::transaction(function () use ($normalizedPrefix, $exists): string {
            $row = DB::table('product_sku_sequences')
                ->where('id', self::COUNTER_ID)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                throw new RuntimeException('SKU sequence is not initialized. Run pending migrations.');
            }

            $value = (int) $row->current_value;

            do {
                $value++;
                if ($value > self::SEQUENCE_MAX) {
                    throw new RuntimeException('SKU sequence exhausted its six-digit range.');
                }

                $candidate = $normalizedPrefix.'-'.str_pad(
                    (string) $value,
                    self::SEQUENCE_WIDTH,
                    '0',
                    STR_PAD_LEFT,
                );
            } while ($exists($candidate));

            DB::table('product_sku_sequences')
                ->where('id', self::COUNTER_ID)
                ->update([
                    'current_value' => $value,
                    'updated_at' => now(),
                ]);

            return $candidate;
        }, 3);
    }

    private function normalizePrefix(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'ARTIKAL';
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($ascii) && $ascii !== '') {
            $value = $ascii;
        }

        $value = strtoupper($value);
        $value = preg_replace('/[^A-Z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        if ($value === '') {
            return 'ARTIKAL';
        }

        if (strlen($value) > self::PREFIX_MAX_LENGTH) {
            $value = rtrim(substr($value, 0, self::PREFIX_MAX_LENGTH), '-');
        }

        return $value !== '' ? $value : 'ARTIKAL';
    }
}
