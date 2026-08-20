<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExchangeRateHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use RuntimeException;
use Throwable;

final class ExchangeRateService
{
    private const ENDPOINT = 'https://api.frankfurter.dev/v2/rate/EUR/RSD';

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

        return [
            'rate' => $rate,
            'mode' => in_array($all['eur_rsd_mode'], ['manual', 'auto'], true) ? $all['eur_rsd_mode'] : 'manual',
            'provider' => 'frankfurter',
            'source' => trim($all['eur_rsd_source']) ?: 'Nije podešeno',
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
            'eur_rsd_source' => 'Ručni unos',
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
            'source' => 'Ručni unos',
            'provider_date' => now()->toDateString(),
            'triggered_by' => 'panel',
            'status' => 'success',
            'message' => 'Ručni EUR/RSD kurs je sačuvan.',
            'updated_by' => $userId,
        ]);
        $this->audit->log('exchange_rate.manual_updated', 'EUR/RSD kurs', null, $before, $this->configuration());
    }

    public function setAutomatic(bool $enabled, int $staleHours, int $userId): void
    {
        $before = $this->configuration();
        $this->settings->putMany([
            'eur_rsd_mode' => $enabled ? 'auto' : 'manual',
            'eur_rsd_provider' => 'frankfurter',
            'eur_rsd_stale_after_hours' => (string) max(1, min(720, $staleHours)),
            'eur_rsd_last_error' => '',
        ], $userId);
        $this->audit->log('exchange_rate.mode_updated', 'EUR/RSD kurs', null, $before, $this->configuration());
    }

    /** @return array{rate:float,date:string,source:string} */
    public function updateAutomatically(string $triggeredBy = 'cron', ?int $userId = null, bool $force = false): array
    {
        $before = $this->configuration();
        if (!$force && $before['mode'] !== 'auto') {
            throw new RuntimeException('Automatsko ažuriranje nije uključeno.');
        }

        // A forced one-off refresh must not silently enable automatic mode.
        $effectiveMode = $force && $before['mode'] === 'manual' ? 'manual' : 'auto';
        $attemptAt = now()->format('Y-m-d H:i:s');
        $this->settings->putMany(['eur_rsd_last_attempt_at' => $attemptAt], $userId);

        try {
            $response = Http::acceptJson()->timeout(15)->connectTimeout(5)->get(self::ENDPOINT);
            if (!$response->successful()) throw new RuntimeException('API je vratio HTTP status '.$response->status().'.');
            $data = $response->json();
            if (!is_array($data)) throw new RuntimeException('API odgovor nije validan JSON objekat.');
            $base = strtoupper(trim((string) ($data['base'] ?? '')));
            $quote = strtoupper(trim((string) ($data['quote'] ?? '')));
            $rate = $data['rate'] ?? null;
            $date = trim((string) ($data['date'] ?? ''));
            if ($base !== 'EUR' || $quote !== 'RSD' || !is_numeric($rate)) throw new RuntimeException('API nije vratio očekivani EUR/RSD kurs.');
            $rate = (float) $rate;
            $this->assertRate($rate);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new RuntimeException('API nije vratio ispravan datum kursa.');

            $this->settings->putMany([
                'eur_rsd_rate' => number_format($rate, 6, '.', ''),
                'eur_rsd_mode' => $effectiveMode,
                'eur_rsd_provider' => 'frankfurter',
                'eur_rsd_source' => 'Frankfurter API — referentni kurs centralnih banaka',
                'eur_rsd_provider_date' => $date,
                'eur_rsd_updated_at' => $attemptAt,
                'eur_rsd_last_attempt_at' => $attemptAt,
                'eur_rsd_last_error' => '',
            ], $userId);

            ExchangeRateHistory::query()->create([
                'old_rate' => $before['rate'],
                'new_rate' => $rate,
                'mode' => $effectiveMode,
                'provider' => 'frankfurter',
                'source' => 'Frankfurter API — referentni kurs centralnih banaka',
                'provider_date' => $date,
                'triggered_by' => $triggeredBy,
                'status' => 'success',
                'message' => 'Automatsko preuzimanje EUR/RSD kursa je uspešno.',
                'updated_by' => $userId,
            ]);

            return ['rate' => $rate, 'date' => $date, 'source' => 'Frankfurter API — referentni kurs centralnih banaka'];
        } catch (Throwable $exception) {
            $message = mb_substr($exception->getMessage(), 0, 500);
            $this->settings->putMany(['eur_rsd_last_error' => $message, 'eur_rsd_last_attempt_at' => $attemptAt], $userId);
            ExchangeRateHistory::query()->create([
                'old_rate' => $before['rate'],
                'new_rate' => null,
                'mode' => $effectiveMode,
                'provider' => 'frankfurter',
                'source' => 'Frankfurter API',
                'provider_date' => null,
                'triggered_by' => $triggeredBy,
                'status' => 'failed',
                'message' => $message,
                'updated_by' => $userId,
            ]);
            throw new RuntimeException($message, 0, $exception);
        }
    }

    private function assertRate(float $rate): void
    {
        if (!is_finite($rate) || $rate < 50 || $rate > 250) {
            throw new RuntimeException('Kurs mora biti između 50 i 250 RSD za 1 EUR.');
        }
    }
}
