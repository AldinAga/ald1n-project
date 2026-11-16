<?php

declare(strict_types=1);

namespace App\Services;

// COMMISSION_PERCENTAGE_POLICY_V0_7
final class CommissionCalculator
{
    public const DEFAULT_RATE_PERCENT = 10.0;

    /** @param float|null $ratePercent podešavanje commission_default_percent (SuperAdmin); null = DEFAULT_RATE_PERCENT */
    public function __construct(private readonly ?float $ratePercent = null) {}

    public function ratePercent(): float
    {
        return $this->ratePercent !== null && $this->ratePercent >= 0.0 && $this->ratePercent <= 100.0 ? $this->ratePercent : self::DEFAULT_RATE_PERCENT;
    }

    public function unitEur(
        float $priceAmount,
        string $currency,
        ?float $manualEur,
        ?float $eurRsdRate,
    ): float {
        if ($this->usesManual($priceAmount, $currency, $manualEur, $eurRsdRate)) {
            return round((float) $manualEur, 2);
        }

        return $this->automaticUnitEur($priceAmount, $currency, $eurRsdRate);
    }

    public function automaticUnitEur(float $priceAmount, string $currency, ?float $eurRsdRate): float
    {
        return $this->manualMinimumEur($priceAmount, $currency, $eurRsdRate);
    }

    public function manualMinimumEur(float $priceAmount, string $currency, ?float $eurRsdRate): float
    {
        return round(
            $this->priceEur($priceAmount, $currency, $eurRsdRate)
                * ($this->ratePercent() / 100),
            2,
        );
    }

    public function usesManual(
        float $priceAmount,
        string $currency,
        ?float $manualEur,
        ?float $eurRsdRate,
    ): bool {
        if ($manualEur === null || !is_finite($manualEur) || $manualEur <= 0.0) {
            return false;
        }

        $normalizedCurrency = strtoupper(trim($currency));
        if ($normalizedCurrency === 'RSD' && ($eurRsdRate === null || $eurRsdRate <= 0.0)) {
            return false;
        }
        if (!in_array($normalizedCurrency, ['EUR', 'RSD'], true)) {
            return false;
        }

        return true;
    }

    private function priceEur(float $priceAmount, string $currency, ?float $eurRsdRate): float
    {
        $priceAmount = max(0.0, $priceAmount);
        $normalizedCurrency = strtoupper(trim($currency));

        if ($normalizedCurrency === 'EUR') {
            return $priceAmount;
        }
        if ($normalizedCurrency === 'RSD' && $eurRsdRate !== null && $eurRsdRate > 0.0) {
            return $priceAmount / $eurRsdRate;
        }

        return 0.0;
    }
}
