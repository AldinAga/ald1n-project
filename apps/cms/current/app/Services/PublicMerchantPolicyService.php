<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\BankAccount;
use App\Models\User;

/**
 * Owner-attested merchant configuration. Never infer delivery, legal or payment
 * conditions from the presence of an old PDF/company profile.
 *
 * The two public-facing ENV flags remain independent OFF-by-default release
 * gates. Saving/approving a policy NEVER enables the public storefront.
 */
final class PublicMerchantPolicyService
{
    private const STORED = [
        'terms_url','privacy_url','shipping_fee_rsd','shipping_description',
        'payment_methods','support_email','bank_account_id',
    ];

    public function __construct(private readonly SettingsService $settings) {}

    /** @return array<string,mixed> */
    public function policy(): array
    {
        $value = [];
        foreach (self::STORED as $key) {
            $config = config('public_storefront.'.$key);
            $storedKey = 'public_checkout_'.$key;
            if ($this->settings->hasStoredValue($storedKey)) {
                $raw = (string) $this->settings->get($storedKey,'');
            } elseif (is_array($config)) {
                $raw = implode(',', $config);
            } else {
                $raw = trim((string) $config);
            }
            $value[$key] = trim($raw);
        }
        $value['methods'] = array_values(array_unique(array_filter(
            array_map('trim', explode(',', $value['payment_methods'])),
            static fn (string $name): bool => $name !== ''
        )));
        sort($value['methods'], SORT_STRING);
        $value['company_name'] = trim((string)$this->settings->get('documents_company_name',''));
        $value['company_address'] = trim((string)$this->settings->get('documents_company_address',''));
        $value['company_city'] = trim((string)$this->settings->get('documents_company_city',''));
        $value['company_tax_id'] = trim((string)$this->settings->get('documents_company_tax_id',''));
        return $value;
    }

    /**
     * Only the policies and merchant identity are included. Approval is
     * invalidated by EVERY subsequent policy, contact, identity or method edit.
     * No buyer PII, payment account number or secret is part of this snapshot.
     */
    public function fingerprint(?array $policy = null): string
    {
        $policy ??= $this->policy();
        unset($policy['methods']);
        return hash('sha256', json_encode($policy,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Inspection does not enable checkout, send mail, alter settings, query live
     * external sites or attest legal content. UI may display missing settings.
     *
     * @return array{policy:array<string,mixed>,issues:list<string>,complete:bool,approved:bool,fingerprint:string,bank_account:?BankAccount}
     */
    public function review(): array
    {
        $p = $this->policy();
        $issues = [];
        if ($p['company_name'] === '' || in_array($p['company_name'], ['Ald1n','Ald1n CMS'], true)
            || $p['company_address'] === '' || $p['company_city'] === '' || $p['company_tax_id'] === '') {
            $issues[]='Unesi stvarni naziv, adresu, grad i PIB trgovca u PDF podešavanjima.';
        }
        if (!$this->safeLegalUrl($p['terms_url'])) $issues[]='Nedostaje validna HTTPS adresa uslova prodaje.';
        if (!$this->safeLegalUrl($p['privacy_url'])) $issues[]='Nedostaje validna HTTPS adresa politike privatnosti.';
        if ($p['shipping_description'] === '') $issues[]='Unesi stvarne uslove dostave.';
        // Current existing orders have no independent shipping fee ledger.
        if (!preg_match('/^0(?:\.0{1,2})?$/D', $p['shipping_fee_rsd'])) {
            $issues[]='Trošak dostave mora biti izričito odobren kao 0 RSD. Naplata dostave još nije podržana.';
        }
        if (!filter_var($p['support_email'], FILTER_VALIDATE_EMAIL)) {
            $issues[]='Unesi validan e-mail za podršku kupcima.';
        }
        if ($p['methods'] === [] || array_diff($p['methods'], ['cash_on_delivery','bank_transfer']) !== []) {
            $issues[]='Izaberi podržan način plaćanja (pouzećem ili bankovnim transferom).';
        }
        $bank = null;
        if (in_array('bank_transfer', $p['methods'], true)) {
            $id = filter_var($p['bank_account_id'], FILTER_VALIDATE_INT);
            $bank = $id && $id > 0
                ? BankAccount::query()->whereKey($id)->where('is_active', true)->first()
                : null;
            if (!$bank instanceof BankAccount) $issues[]='Za bankovni transfer izaberi aktivan žiro račun.';
        }
        $fingerprint = $this->fingerprint($p);
        $digest = trim((string)$this->settings->get('public_checkout_approval_digest',''));
        $by = filter_var($this->settings->get('public_checkout_approved_by',''), FILTER_VALIDATE_INT);
        $at = trim((string)$this->settings->get('public_checkout_approved_at',''));
        $approved = $issues === [] && $digest !== '' && hash_equals($digest,$fingerprint)
            && $at !== '' && $by && $by > 0
            && User::query()->whereKey($by)->where('status','active')
                ->whereHas('role', static fn ($query) => $query->where('slug','superadmin'))
                ->exists();
        return [
            'policy'=>$p,'issues'=>$issues,'complete'=>$issues === [],
            'approved'=>$approved,'fingerprint'=>$fingerprint,'bank_account'=>$bank,
        ];
    }

    private function safeLegalUrl(string $url): bool
    {
        if ($url === '' || (preg_match('/[\x00-\x20\x7f]/', $url) || str_contains($url, '\\'))
            || !filter_var($url, FILTER_VALIDATE_URL)) return false;
        $parts=parse_url($url);
        if (!is_array($parts) || strtolower((string)($parts['scheme']??''))!=='https'
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['port'])) return false;
        $host=strtolower((string)($parts['host']??''));
        return $host !== '' && str_contains($host,'.')
            && filter_var($host,FILTER_VALIDATE_IP)===false
            && filter_var($host,FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME)!==false
            && !preg_match('/(?:^|\.)(?:localhost|local|internal)$/D',$host)
            && !(app()->environment('production')
                && preg_match('/(?:^|\.)example\.(?:test|invalid)$/D',$host));
    }
}
