<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExchangeRateHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class ExchangeRateService
{
    public const RATE_KIND = 'commercial_sell';
    public const RATE_LABEL = 'Komercijalni prodajni';

    private const ENDPOINT = 'https://webappcenter.nbs.rs/ExchangeRateWebApp/ExchangeRate/CurrentForeignExchange';
    private const PROVIDER = 'nbs';
    private const SOURCE = 'Narodna banka Srbije - prodajni kurs za devize (Komercijalni prodajni)';
    private const MANUAL_SOURCE = 'Rucni override - Komercijalni prodajni EUR/RSD kurs';

    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
    ) {}

    /** @return array<string,mixed> */
    public function configuration(): array
    {
        $all = $this->settings->all();
        $rate = is_numeric($all['eur_rsd_rate']) ? (float) $all['eur_rsd_rate'] : null;
        $updatedAt = trim($all['eur_rsd_updated_at']) ?: null;
        $staleHours = max(1, min(720, (int) $all['eur_rsd_stale_after_hours']));
        $isStale = $updatedAt === null || now()->diffInHours(Carbon::parse($updatedAt), true) > $staleHours;
        $provider = trim((string) ($all['eur_rsd_provider'] ?? '')) ?: self::PROVIDER;

        return [
            'rate' => $rate,
            'mode' => in_array($all['eur_rsd_mode'], ['manual', 'auto'], true) ? $all['eur_rsd_mode'] : 'manual',
            'provider' => $provider,
            'source' => trim($all['eur_rsd_source']) ?: 'Nije podeseno',
            'provider_date' => trim($all['eur_rsd_provider_date']) ?: null,
            'updated_at' => $updatedAt,
            'last_attempt_at' => trim($all['eur_rsd_last_attempt_at']) ?: null,
            'last_error' => trim($all['eur_rsd_last_error']) ?: null,
            'stale_after_hours' => $staleHours,
            'is_stale' => $isStale,
        ];
    }

    public function saveManual(float $rate, int $userId): void
    {
        $this->assertRate($rate);
        $before = $this->configuration();
        $now = now()->format('Y-m-d H:i:s');
        $this->settings->putMany([
            'eur_rsd_rate' => number_format($rate, 6, '.', ''),
            'eur_rsd_mode' => 'manual',
            'eur_rsd_provider' => 'manual',
            'eur_rsd_source' => self::MANUAL_SOURCE,
            'eur_rsd_provider_date' => now()->toDateString(),
            'eur_rsd_updated_at' => $now,
            'eur_rsd_last_attempt_at' => $now,
            'eur_rsd_last_error' => '',
        ], $userId);
        ExchangeRateHistory::query()->create([
            'old_rate' => $before['rate'],
            'new_rate' => $rate,
            'mode' => 'manual',
            'provider' => 'manual',
            'source' => self::MANUAL_SOURCE,
            'provider_date' => now()->toDateString(),
            'triggered_by' => 'panel',
            'status' => 'success',
            'message' => 'Rucni Komercijalni prodajni EUR/RSD kurs je sacuvan.',
            'updated_by' => $userId,
        ]);
        $this->audit->log('exchange_rate.manual_updated', 'Komercijalni prodajni EUR/RSD kurs', null, $before, $this->configuration());
    }

    public function setAutomatic(bool $enabled, int $staleHours, int $userId): void
    {
        $before = $this->configuration();
        $this->settings->putMany([
            'eur_rsd_mode' => $enabled ? 'auto' : 'manual',
            'eur_rsd_stale_after_hours' => (string) max(1, min(720, $staleHours)),
            'eur_rsd_last_error' => '',
        ], $userId);
        $this->audit->log('exchange_rate.mode_updated', 'Komercijalni prodajni EUR/RSD kurs', null, $before, $this->configuration());
    }

    /** @return array{rate:float,date:string,source:string} */
    public function fetchCommercialSellingRate(): array
    {
        $response = Http::withHeaders([
            'Accept' => 'text/html,application/xhtml+xml',
            'Accept-Language' => 'sr-RS,sr;q=0.9,en;q=0.7',
            'User-Agent' => 'Mozilla/5.0 (compatible; Ald1nCMS/2.2; +https://cms.ald1n.com)',
        ])->timeout(20)->connectTimeout(7)->get(self::ENDPOINT);

        if (!$response->successful()) {
            throw new RuntimeException('NBS je vratio HTTP status '.$response->status().'.');
        }

        return $this->parseNbsCommercialSellingRate($response->body());
    }

    /** @return array{rate:float,date:string,source:string} */
    public function updateAutomatically(string $triggeredBy = 'cron', ?int $userId = null, bool $force = false): array
    {
        $before = $this->configuration();
        if (!$force && $before['mode'] !== 'auto') {
            throw new RuntimeException('Automatsko azuriranje nije ukljuceno.');
        }

        // Forced refresh changes the canonical value but preserves manual/auto mode.
        $effectiveMode = $force && $before['mode'] === 'manual' ? 'manual' : 'auto';
        $attemptAt = now()->format('Y-m-d H:i:s');

        try {
            $result = $this->fetchCommercialSellingRate();
            $rate = (float) $result['rate'];
            $date = (string) $result['date'];
            $this->assertRate($rate);

            $this->settings->putMany([
                'eur_rsd_rate' => number_format($rate, 6, '.', ''),
                'eur_rsd_mode' => $effectiveMode,
                'eur_rsd_provider' => self::PROVIDER,
                'eur_rsd_source' => self::SOURCE,
                'eur_rsd_provider_date' => $date,
                'eur_rsd_updated_at' => $attemptAt,
                'eur_rsd_last_attempt_at' => $attemptAt,
                'eur_rsd_last_error' => '',
            ], $userId);

            ExchangeRateHistory::query()->create([
                'old_rate' => $before['rate'],
                'new_rate' => $rate,
                'mode' => $effectiveMode,
                'provider' => self::PROVIDER,
                'source' => self::SOURCE,
                'provider_date' => $date,
                'triggered_by' => $triggeredBy,
                'status' => 'success',
                'message' => 'NBS Komercijalni prodajni EUR/RSD kurs je uspesno preuzet i postavljen kao glavni kurs.',
                'updated_by' => $userId,
            ]);

            return $result;
        } catch (Throwable $exception) {
            $message = mb_substr($exception->getMessage(), 0, 500);
            $this->settings->putMany([
                'eur_rsd_last_error' => $message,
                'eur_rsd_last_attempt_at' => $attemptAt,
            ], $userId);
            ExchangeRateHistory::query()->create([
                'old_rate' => $before['rate'],
                'new_rate' => null,
                'mode' => $effectiveMode,
                'provider' => self::PROVIDER,
                'source' => self::SOURCE,
                'provider_date' => null,
                'triggered_by' => $triggeredBy,
                'status' => 'failed',
                'message' => $message,
                'updated_by' => $userId,
            ]);
            throw new RuntimeException($message, 0, $exception);
        }
    }

    /** @return array{rate:float,date:string,source:string} */
    private function parseNbsCommercialSellingRate(string $html): array
    {
        if (trim($html) === '') {
            throw new RuntimeException('NBS odgovor je prazan.');
        }

        $rows = [];
        preg_match_all('/<tr\b[^\x3e]*\x3e(.*?)<\/tr>/isu', $html, $rows);
        $sellingRate = null;

        foreach ($rows[1] ?? [] as $rowHtml) {
            $matches = [];
            preg_match_all('/<td\b[^\x3e]*\x3e(.*?)<\/td>/isu', (string) $rowHtml, $matches);
            $cells = array_map(fn (string $cell): string => $this->htmlText($cell), $matches[1] ?? []);
            if (count($cells) < 6) {
                continue;
            }
            if (strtoupper($cells[0]) !== 'EUR' || preg_replace('/\D+/', '', $cells[1]) !== '978') {
                continue;
            }

            $unit = (int) preg_replace('/\D+/', '', $cells[3]);
            if ($unit !== 1) {
                throw new RuntimeException('NBS EUR red nema ocekivanu jedinicu 1.');
            }

            $sellingRate = $this->decimalFromNbs($cells[5]);
            break;
        }

        if ($sellingRate === null) {
            throw new RuntimeException('NBS kursna lista ne sadrzi EUR prodajni kurs za devize.');
        }
        $this->assertRate($sellingRate);

        $plain = html_entity_decode(
            preg_replace('/<[^>]+>/u', ' ', $html) ?? $html,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );
        $plain = preg_replace('/\s+/u', ' ', $plain) ?? $plain;
        $dateMatch = [];
        if (!preg_match('/\b(\d{1,2})\.(\d{1,2})\.(\d{4})\./u', $plain, $dateMatch)) {
            throw new RuntimeException('NBS kursna lista nema prepoznatljiv datum formiranja.');
        }
        $day = (int) $dateMatch[1];
        $month = (int) $dateMatch[2];
        $year = (int) $dateMatch[3];
        if (!checkdate($month, $day, $year)) {
            throw new RuntimeException('NBS datum kursne liste nije validan.');
        }
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

        return [
            'rate' => $sellingRate,
            'date' => $date,
            'source' => self::SOURCE,
        ];
    }

    private function htmlText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\u{00A0}", "\u{202F}"], ' ', $text);
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function decimalFromNbs(string $value): float
    {
        $normalized = str_replace(["\u{00A0}", "\u{202F}", ' '], '', trim($value));
        $normalized = str_replace(',', '.', $normalized);
        if (!preg_match('/^\d+(?:\.\d+)?$/', $normalized)) {
            throw new RuntimeException('NBS prodajni EUR kurs nije numericki validan.');
        }
        return (float) $normalized;
    }

    private function assertRate(float $rate): void
    {
        if (!is_finite($rate) || $rate < 50 || $rate > 250) {
            throw new RuntimeException('Kurs mora biti izmedju 50 i 250 RSD za 1 EUR.');
        }
    }
}