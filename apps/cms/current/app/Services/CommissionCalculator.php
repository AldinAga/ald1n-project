<?php

declare(strict_types=1);

namespace App\Services;

final class CommissionCalculator
{
    public function unitEur(float $priceAmount, string $currency, ?float $manualEur, ?float $eurRsdRate): float
    {
        if ($manualEur !== null && $manualEur >= 20.0) {
            return round($manualEur, 2);
        }

        $priceEur = strtoupper($currency) === 'EUR'
            ? $priceAmount
            : (($eurRsdRate ?? 0.0) > 0.0 ? $priceAmount / (float) $eurRsdRate : 0.0);

        return round(min(50.0, max(20.0, $priceEur * 0.10)), 2);
    }
}
