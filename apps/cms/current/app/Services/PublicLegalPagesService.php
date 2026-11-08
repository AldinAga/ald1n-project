<?php
declare(strict_types=1);

namespace App\Services;

use App\Support\LegalTextRenderer;
use App\Support\PublicLegalTemplates;
use App\Support\PublicRetailOptions;

/**
 * Read-only view model for the public legal pages. Values come only from
 * existing settings; nothing about the trader is invented. A missing value
 * is shown as "podatak nije unet" and listed for the SuperAdministrator.
 *
 * This service never changes merchant approval, storefront state or flags.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicLegalPagesService
{
    /** Placeholder company names that do not identify a real trader. */
    private const PLACEHOLDER_NAMES = ['Ald1n', 'Ald1n CMS'];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly PublicMerchantPolicyService $merchant,
        private readonly PublicLegalTextStore $texts,
        private readonly LegalTextRenderer $renderer,
    ) {}

    public static function routeName(string $page): string
    {
        return 'legal.'.$page;
    }

    /** @return array<string,?string> placeholder => value, null when missing */
    public function values(): array
    {
        $p = $this->merchant->policy();
        $name = $p['company_name'];
        if (in_array($name, self::PLACEHOLDER_NAMES, true)) $name = '';
        $address = $p['company_address'];
        $city = $p['company_city'];
        $payments = array_values(array_filter(array_map(
            static fn (string $m): ?string => PublicRetailOptions::PAYMENT[$m] ?? null, $p['methods'])));
        $deliveries = array_values(array_filter(array_map(
            static fn (string $d): ?string => PublicRetailOptions::DELIVERY[$d] ?? null, $p['deliveries'])));
        $fee = $p['shipping_fee_rsd'];
        $support = filter_var($p['support_email'], FILTER_VALIDATE_EMAIL) ? $p['support_email'] : '';
        $appUrl = rtrim(trim((string) config('app.url')), '/');

        $values = [
            'naziv_firme' => $name,
            'adresa' => $address,
            'mesto' => $city,
            'pib' => $p['company_tax_id'],
            'maticni_broj' => trim((string) $this->settings->get('documents_company_registration_number', '')),
            'telefon' => trim((string) $this->settings->get('documents_company_phone', '')),
            'email_firme' => trim((string) $this->settings->get('documents_company_email', '')),
            'email_podrske' => $support,
            'adresa_za_reklamacije' => ($address !== '' && $city !== '') ? $address.', '.$city : '',
            'nacini_placanja' => implode(', ', $payments),
            'nacini_dostave' => implode(', ', $deliveries),
            'uslovi_dostave' => $p['shipping_description'],
            'cena_dostave' => preg_match('/^\d+(?:\.\d{1,2})?$/D', $fee) === 1
                ? rtrim(rtrim(number_format((float) $fee, 2, ',', '.'), '0'), ',').' RSD' : '',
            'internet_adresa' => str_starts_with($appUrl, 'https://') ? $appUrl : '',
        ];
        return array_map(static fn (string $v): ?string => $v === '' ? null : $v, $values);
    }

    /** @return list<array{label:string,value:?string}> */
    public function traderRows(?array $values = null): array
    {
        $v = $values ?? $this->values();
        return [
            ['label' => 'Poslovno ime', 'value' => $v['naziv_firme']],
            ['label' => 'Sedište', 'value' => ($v['adresa'] !== null && $v['mesto'] !== null)
                ? $v['adresa'].', '.$v['mesto'] : null],
            ['label' => 'PIB', 'value' => $v['pib']],
            ['label' => 'Matični broj', 'value' => $v['maticni_broj']],
            ['label' => 'Telefon', 'value' => $v['telefon']],
            ['label' => 'E-pošta firme', 'value' => $v['email_firme']],
            ['label' => 'E-pošta podrške kupcima', 'value' => $v['email_podrske']],
            ['label' => 'Mesto prijema reklamacija', 'value' => $v['adresa_za_reklamacije']],
        ];
    }

    /** @return list<string> labels of data the legal pages need but are not entered */
    public function missing(): array
    {
        $labels = [];
        foreach ($this->values() as $key => $value) {
            if ($value === null) $labels[] = PublicLegalTemplates::PLACEHOLDERS[$key];
        }
        return $labels;
    }

    /**
     * A public legal page is a draft while the merchant approval does not
     * match the current terms and texts, or while an editable page still
     * shows the shipped template. $page null means the overview page.
     */
    public function isDraft(?string $page): bool
    {
        if (!$this->merchant->review()['approved']) return true;
        return $page !== null && in_array($page, PublicLegalTemplates::EDITABLE, true)
            && !$this->texts->isCustom($page);
    }

    public function renderBody(string $page, ?array $values = null): string
    {
        return $this->renderer->render($this->texts->effective($page), $values ?? $this->values());
    }

    /**
     * Absolute HTTPS addresses derived from the configured APP_URL, never
     * from the request Host. 'valid' uses the same rule as merchant review.
     *
     * @return array<string,array{title:string,url:string,path:string,valid:bool}>
     */
    public function absoluteUrls(): array
    {
        $base = rtrim(trim((string) config('app.url')), '/');
        $out = [];
        foreach (PublicLegalTemplates::PAGES as $key => $page) {
            $path = route(self::routeName($key), [], false);
            $url = $base.$path;
            $out[$key] = [
                'title' => $page['title'], 'url' => $url, 'path' => $path,
                'valid' => $this->merchant->isSafeLegalUrl($url),
            ];
        }
        return $out;
    }
}
