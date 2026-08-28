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

    private const NBS_API_ENDPOINT = 'https://webservices.nbs.rs/CommunicationOfficeService1_0/ExchangeRateService.asmx';
    private const NBS_API_ACTION = 'http://communicationoffice.nbs.rs/GetCurrentExchangeRate';
    private const NBS_HTML_ENDPOINT = 'https://webappcenter.nbs.rs/ExchangeRateWebApp/ExchangeRate/CurrentForeignExchange';
    private const FRANKFURTER_ENDPOINT = 'https://api.frankfurter.dev/v2/rate/EUR/RSD';

    private const PRIMARY_PROVIDER = 'nbs_api';
    private const NBS_API_SOURCE = 'Narodna banka Srbije API - prodajni kurs za devize (Komercijalni prodajni)';
    private const FRANKFURTER_PROVIDER = 'frankfurter';
    private const FRANKFURTER_SOURCE = 'Frankfurter API v2 - sekundarni EUR/RSD referentni fallback';
    private const NBS_HTML_PROVIDER = 'nbs_html';
    private const NBS_HTML_SOURCE = 'Narodna banka Srbije javna kursna lista - tercijarni emergency fallback prodajni kurs za devize';
    private const FAILURE_PROVIDER = 'provider_chain';
    private const FAILURE_SOURCE = 'NBS API -> Frankfurter API -> NBS javna kursna lista';
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
        $provider = trim((string) ($all['eur_rsd_provider'] ?? '')) ?: self::PRIMARY_PROVIDER;

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

    /** @return array{rate:float,date:string,source:string,provider:string,fallback_reason?:string} */
    public function fetchCommercialSellingRate(): array
    {
        $errors = [];

        try {
            return $this->fetchNbsApiCommercialSellingRate();
        } catch (Throwable $exception) {
            $errors[] = 'NBS API: '.$this->safeProviderError($exception);
        }

        try {
            $result = $this->fetchFrankfurterRate();
            $result['fallback_reason'] = implode(' | ', $errors);
            return $result;
        } catch (Throwable $exception) {
            $errors[] = 'Frankfurter: '.$this->safeProviderError($exception);
        }

        try {
            $result = $this->fetchNbsHtmlCommercialSellingRate();
            $result['fallback_reason'] = implode(' | ', $errors);
            return $result;
        } catch (Throwable $exception) {
            $errors[] = 'NBS HTML: '.$this->safeProviderError($exception);
        }

        throw new RuntimeException('Automatski EUR/RSD izvori nisu dostupni. '.implode(' | ', $errors));
    }

    /** @return array{rate:float,date:string,source:string,provider:string,fallback_reason?:string} */
    public function updateAutomatically(string $triggeredBy = 'cron', ?int $userId = null, bool $force = false): array
    {
        $before = $this->configuration();
        if (!$force && $before['mode'] !== 'auto') {
            throw new RuntimeException('Automatsko azuriranje nije ukljuceno.');
        }

        $effectiveMode = $force && $before['mode'] === 'manual' ? 'manual' : 'auto';
        $attemptAt = now()->format('Y-m-d H:i:s');

        try {
            $result = $this->fetchCommercialSellingRate();
            $rate = (float) $result['rate'];
            $date = (string) $result['date'];
            $provider = (string) $result['provider'];
            $source = (string) $result['source'];
            $this->assertRate($rate);

            $this->settings->putMany([
                'eur_rsd_rate' => number_format($rate, 6, '.', ''),
                'eur_rsd_mode' => $effectiveMode,
                'eur_rsd_provider' => $provider,
                'eur_rsd_source' => $source,
                'eur_rsd_provider_date' => $date,
                'eur_rsd_updated_at' => $attemptAt,
                'eur_rsd_last_attempt_at' => $attemptAt,
                'eur_rsd_last_error' => '',
            ], $userId);

            $message = match ($provider) {
                self::PRIMARY_PROVIDER => 'NBS API Komercijalni prodajni EUR/RSD kurs je uspesno preuzet i postavljen kao glavni kurs.',
                self::FRANKFURTER_PROVIDER => 'NBS API nije bio dostupan; Frankfurter API v2 sekundarni fallback je postavljen kao privremeni EUR/RSD kurs.',
                self::NBS_HTML_PROVIDER => 'NBS API i Frankfurter nisu bili dostupni; postojeca NBS javna kursna lista je upotrebljena kao tercijarni emergency fallback.',
                default => 'EUR/RSD kurs je automatski osvezen.',
            };
            $fallbackReason = trim((string) ($result['fallback_reason'] ?? ''));
            if ($fallbackReason !== '') {
                $message .= ' Fallback razlog: '.mb_substr($fallbackReason, 0, 250);
            }

            ExchangeRateHistory::query()->create([
                'old_rate' => $before['rate'],
                'new_rate' => $rate,
                'mode' => $effectiveMode,
                'provider' => $provider,
                'source' => $source,
                'provider_date' => $date,
                'triggered_by' => $triggeredBy,
                'status' => 'success',
                'message' => mb_substr($message, 0, 500),
                'updated_by' => $userId,
            ]);

            return $result;
        } catch (Throwable $exception) {
            $message = mb_substr($this->safeProviderError($exception), 0, 500);
            $this->settings->putMany([
                'eur_rsd_last_error' => $message,
                'eur_rsd_last_attempt_at' => $attemptAt,
            ], $userId);
            ExchangeRateHistory::query()->create([
                'old_rate' => $before['rate'],
                'new_rate' => null,
                'mode' => $effectiveMode,
                'provider' => self::FAILURE_PROVIDER,
                'source' => self::FAILURE_SOURCE,
                'provider_date' => null,
                'triggered_by' => $triggeredBy,
                'status' => 'failed',
                'message' => $message,
                'updated_by' => $userId,
            ]);
            throw new RuntimeException($message, 0, $exception);
        }
    }

    /** @return array{rate:float,date:string,source:string,provider:string} */
    private function fetchNbsApiCommercialSellingRate(): array
    {
        [$username, $password, $licenceId] = $this->nbsApiCredentials();
        $soap = $this->nbsSoapEnvelope($username, $password, $licenceId);
        $timeout = max(3, min(60, (int) config('services.nbs_exchange.timeout_seconds', 20)));
        $connectTimeout = max(2, min(20, (int) config('services.nbs_exchange.connect_timeout_seconds', 7)));

        $response = Http::withHeaders([
            'Accept' => 'text/xml,application/xml',
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => self::NBS_API_ACTION,
            'User-Agent' => 'Ald1nCMS/2.2 (+https://cms.ald1n.com)',
        ])->withBody($soap, 'text/xml; charset=utf-8')
            ->timeout($timeout)
            ->connectTimeout($connectTimeout)
            ->post(self::NBS_API_ENDPOINT);

        if (!$response->successful()) {
            throw new RuntimeException('NBS API je vratio HTTP status '.$response->status().'.');
        }

        return $this->parseNbsSoapCommercialSellingRate($response->body());
    }

    /** @return array{rate:float,date:string,source:string,provider:string} */
    private function fetchFrankfurterRate(): array
    {
        $timeout = max(3, min(60, (int) config('services.nbs_exchange.frankfurter_timeout_seconds', 12)));
        $connectTimeout = max(2, min(20, (int) config('services.nbs_exchange.frankfurter_connect_timeout_seconds', 5)));
        $response = Http::acceptJson()
            ->withHeaders(['User-Agent' => 'Ald1nCMS/2.2 (+https://cms.ald1n.com)'])
            ->timeout($timeout)
            ->connectTimeout($connectTimeout)
            ->get(self::FRANKFURTER_ENDPOINT);

        if (!$response->successful()) {
            throw new RuntimeException('Frankfurter je vratio HTTP status '.$response->status().'.');
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            throw new RuntimeException('Frankfurter odgovor nije validan JSON objekat.');
        }
        if (strtoupper((string) ($payload['base'] ?? '')) !== 'EUR' || strtoupper((string) ($payload['quote'] ?? '')) !== 'RSD') {
            throw new RuntimeException('Frankfurter odgovor ne odgovara EUR/RSD paru.');
        }
        if (!is_numeric($payload['rate'] ?? null)) {
            throw new RuntimeException('Frankfurter EUR/RSD kurs nije numericki validan.');
        }
        $rate = (float) $payload['rate'];
        $this->assertRate($rate);
        $date = $this->normalizeProviderDate((string) ($payload['date'] ?? ''));

        return [
            'rate' => $rate,
            'date' => $date,
            'source' => self::FRANKFURTER_SOURCE,
            'provider' => self::FRANKFURTER_PROVIDER,
        ];
    }

    /** @return array{rate:float,date:string,source:string,provider:string} */
    private function fetchNbsHtmlCommercialSellingRate(): array
    {
        $response = Http::withHeaders([
            'Accept' => 'text/html,application/xhtml+xml',
            'Accept-Language' => 'sr-RS,sr;q=0.9,en;q=0.7',
            'User-Agent' => 'Mozilla/5.0 (compatible; Ald1nCMS/2.2; +https://cms.ald1n.com)',
        ])->timeout(20)->connectTimeout(7)->get(self::NBS_HTML_ENDPOINT);

        if (!$response->successful()) {
            throw new RuntimeException('NBS javna kursna lista je vratila HTTP status '.$response->status().'.');
        }

        return $this->parseNbsHtmlCommercialSellingRate($response->body());
    }

    /** @return array{0:string,1:string,2:string} */
    private function nbsApiCredentials(): array
    {
        $username = trim((string) config('services.nbs_exchange.username', ''));
        $password = trim((string) config('services.nbs_exchange.password', ''));
        $licenceId = trim((string) config('services.nbs_exchange.licence_id', ''));
        if ($username === '' || $password === '' || $licenceId === '') {
            throw new RuntimeException('NBS API kredencijali nisu podeseni.');
        }
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $licenceId) !== 1) {
            throw new RuntimeException('NBS API LicenceID nema validan GUID format.');
        }
        return [$username, $password, $licenceId];
    }

    private function nbsSoapEnvelope(string $username, string $password, string $licenceId): string
    {
        $xml = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return '<?xml version="1.0" encoding="utf-8"?>'
            .'<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            .'<soap:Header><AuthenticationHeader xmlns="http://communicationoffice.nbs.rs">'
            .'<UserName>'.$xml($username).'</UserName>'
            .'<Password>'.$xml($password).'</Password>'
            .'<LicenceID>'.$xml($licenceId).'</LicenceID>'
            .'</AuthenticationHeader></soap:Header>'
            .'<soap:Body><GetCurrentExchangeRate xmlns="http://communicationoffice.nbs.rs">'
            .'<exchangeRateListTypeID>1</exchangeRateListTypeID>'
            .'</GetCurrentExchangeRate></soap:Body></soap:Envelope>';
    }

    /** @return array{rate:float,date:string,source:string,provider:string} */
    private function parseNbsSoapCommercialSellingRate(string $xml): array
    {
        if (trim($xml) === '') {
            throw new RuntimeException('NBS API odgovor je prazan.');
        }
        $fault = [];
        if (preg_match('/<faultstring\b[^\x3e]*\x3e(.*?)<\/faultstring>/isu', $xml, $fault) === 1) {
            throw new RuntimeException('NBS API SOAP greska: '.mb_substr($this->xmlText((string) $fault[1]), 0, 180));
        }

        $rows = [];
        preg_match_all('/\x3c(?:[A-Za-z0-9_]+:)?ExchangeRate\b[^\x3e]*\x3e(.*?)<\/(?:[A-Za-z0-9_]+:)?ExchangeRate>/isu', $xml, $rows);
        foreach ($rows[1] ?? [] as $row) {
            $rowXml = (string) $row;
            $currencyCode = preg_replace('/\D+/', '', (string) ($this->xmlChild($rowXml, 'CurrencyCode') ?? ''));
            $currencyAlpha = strtoupper(trim((string) ($this->xmlChild($rowXml, 'CurrencyCodeAlfaChar') ?? '')));
            if ($currencyCode !== '978' && $currencyAlpha !== 'EUR') {
                continue;
            }
            $unit = (int) preg_replace('/\D+/', '', (string) ($this->xmlChild($rowXml, 'Unit') ?? ''));
            if ($unit !== 1) {
                throw new RuntimeException('NBS API EUR red nema ocekivanu jedinicu 1.');
            }
            $sellingRaw = (string) ($this->xmlChild($rowXml, 'SellingRate') ?? '');
            if ($sellingRaw === '') {
                throw new RuntimeException('NBS API EUR red nema prodajni kurs.');
            }
            $rate = $this->decimalFromNbs($sellingRaw);
            $this->assertRate($rate);
            $date = $this->normalizeProviderDate((string) ($this->xmlChild($rowXml, 'Date') ?? ''));
            return [
                'rate' => $rate,
                'date' => $date,
                'source' => self::NBS_API_SOURCE,
                'provider' => self::PRIMARY_PROVIDER,
            ];
        }

        throw new RuntimeException('NBS API tekuca lista ne sadrzi EUR prodajni kurs za devize.');
    }

    /** @return array{rate:float,date:string,source:string,provider:string} */
    private function parseNbsHtmlCommercialSellingRate(string $html): array
    {
        if (trim($html) === '') {
            throw new RuntimeException('NBS javni HTML odgovor je prazan.');
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
        $date = $this->normalizeProviderDate($dateMatch[1].'.'.$dateMatch[2].'.'.$dateMatch[3].'.');

        return [
            'rate' => $sellingRate,
            'date' => $date,
            'source' => self::NBS_HTML_SOURCE,
            'provider' => self::NBS_HTML_PROVIDER,
        ];
    }

    private function xmlChild(string $xml, string $name): ?string
    {
        $quoted = preg_quote($name, '/');
        $match = [];
        if (preg_match('/\x3c(?:[A-Za-z0-9_]+:)?'.$quoted.'\b[^\x3e]*\x3e(.*?)<\/(?:[A-Za-z0-9_]+:)?'.$quoted.'>/isu', $xml, $match) !== 1) {
            return null;
        }
        return $this->xmlText((string) $match[1]);
    }

    private function xmlText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_XML1, 'UTF-8'));
    }

    private function normalizeProviderDate(string $value): string
    {
        $value = trim($value);
        $match = [];
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $value, $match) === 1) {
            $year = (int) $match[1];
            $month = (int) $match[2];
            $day = (int) $match[3];
        } elseif (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})\.?/', $value, $match) === 1) {
            $day = (int) $match[1];
            $month = (int) $match[2];
            $year = (int) $match[3];
        } else {
            throw new RuntimeException('Datum izvora kursa nije u podrzanom formatu.');
        }
        if (!checkdate($month, $day, $year)) {
            throw new RuntimeException('Datum izvora kursa nije validan.');
        }
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
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

    private function safeProviderError(Throwable $exception): string
    {
        $message = $exception->getMessage();
        foreach (['username', 'password', 'licence_id'] as $key) {
            $secret = trim((string) config('services.nbs_exchange.'.$key, ''));
            if ($secret !== '') {
                $message = str_replace($secret, '[redacted]', $message);
            }
        }
        $message = preg_replace('/<Password>.*?<\/Password>/isu', '<Password>[redacted]</Password>', $message) ?? $message;
        return mb_substr(trim($message), 0, 350);
    }

    private function assertRate(float $rate): void
    {
        if (!is_finite($rate) || $rate < 50 || $rate > 250) {
            throw new RuntimeException('Kurs mora biti izmedju 50 i 250 RSD za 1 EUR.');
        }
    }
}