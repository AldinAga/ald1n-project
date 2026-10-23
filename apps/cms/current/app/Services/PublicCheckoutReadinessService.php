<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\BankAccount;
use Illuminate\Validation\ValidationException;

/**
 * Read-only go-live gate. Merchant legal/shipping/payment settings must be
 * supplied explicitly. No implicit free shipping, payment proof, or tax policy.
 */
final class PublicCheckoutReadinessService
{
    public function __construct(private readonly SettingsService $settings) {}

    /** @return array{methods:list<string>,shipping_fee_rsd:float,shipping_description:string,terms_url:string,privacy_url:string,support_email:string,bank_account:?BankAccount} */
    public function assertReady(): array
    {
        abort_unless(config('public_storefront.enabled') === true
            && config('public_storefront.checkout_enabled') === true, 404);

        $terms = trim((string) config('public_storefront.terms_url'));
        $privacy = trim((string) config('public_storefront.privacy_url'));
        $delivery = trim((string) config('public_storefront.shipping_description'));
        $support = trim((string) config('public_storefront.support_email'));
        $fee = config('public_storefront.shipping_fee_rsd');
        $methods = array_values(array_unique(array_map('trim', (array) config('public_storefront.payment_methods', []))));

        $company = trim((string) $this->settings->get('documents_company_name', ''));
        $companyAddress = trim((string) $this->settings->get('documents_company_address', ''));
        $taxId = trim((string) $this->settings->get('documents_company_tax_id', ''));
        $ready = $company !== '' && $company !== 'Ald1n' && $company !== 'Ald1n CMS'
            && $companyAddress !== '' && $taxId !== ''
            && $this->secureUrl($terms) && $this->secureUrl($privacy)
            && $delivery !== '' && filter_var($support, FILTER_VALIDATE_EMAIL) !== false
            && $fee !== null && is_numeric($fee)
            // The existing order subtotal cannot represent a separate shipping
            // charge: reject nonzero fees rather than silently undercharge.
            && (float) $fee === 0.0
            && $methods !== []
            && array_diff($methods, ['cash_on_delivery', 'bank_transfer']) === [];

        $bank = null;
        if (in_array('bank_transfer', $methods, true)) {
            $accountId = filter_var(config('public_storefront.bank_account_id'), FILTER_VALIDATE_INT);
            $bank = $accountId && $accountId > 0
                ? BankAccount::query()->whereKey($accountId)->where('is_active', true)->first()
                : null;
            $ready = $ready && $bank !== null;
        }
        if (!$ready) {
            throw ValidationException::withMessages(['checkout' =>
                'Javno poručivanje nije omogućeno dok trgovac ne potvrdi cenu dostave, pravne uslove i naplatu.']);
        }
        return [
            'methods' => $methods, 'shipping_fee_rsd' => 0.0,
            'shipping_description' => $delivery,
            'terms_url' => $terms, 'privacy_url' => $privacy,
            'support_email' => $support, 'bank_account' => $bank,
        ];
    }

    private function secureUrl(string $value): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) return false;
        $parsed = parse_url($value);
        return is_array($parsed) && ($parsed['scheme'] ?? '') === 'https'
            && is_string($parsed['host'] ?? null)
            && !isset($parsed['user']) && !isset($parsed['pass']);
    }
}
