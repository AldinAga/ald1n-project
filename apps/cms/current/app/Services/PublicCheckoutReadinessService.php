<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\BankAccount;
use Illuminate\Validation\ValidationException;

/**
 * Two independent OFF-by-default release flags plus a real, immutable
 * SuperAdmin merchant-policy attestation are required before guest checkout.
 */
final class PublicCheckoutReadinessService
{
    public function __construct(private readonly PublicMerchantPolicyService $merchant) {}

    /** @return array{methods:list<string>,delivery_methods:list<string>,deferred_payment_days:int,deferred_payment_terms:string,shipping_fee_rsd:float,shipping_description:string,terms_url:string,privacy_url:string,support_email:string,approval_digest:string,bank_account:?BankAccount} */
    public function assertReady(): array
    {
        abort_unless(config('public_storefront.enabled') === true
            && config('public_storefront.checkout_enabled') === true
            && app(PublicStorefrontStateService::class)->isOpen(), 404);

        $review=$this->merchant->review();
        if (!$review['complete'] || !$review['approved']) {
            throw ValidationException::withMessages(['checkout'=>
                'Javno poručivanje je zaustavljeno dok SuperAdministrator ne potvrdi stvarne uslove prodaje, dostave, privatnosti i plaćanja.']);
        }

        $p=$review['policy'];
        return [
            'methods'=>$p['methods'],
            'delivery_methods'=>$p['deliveries'],
            'deferred_payment_days'=>(int)$p['deferred_payment_days'],
            'deferred_payment_terms'=>$p['deferred_payment_terms'],
            'shipping_fee_rsd'=>0.0,
            'shipping_description'=>$p['shipping_description'],
            'terms_url'=>$p['terms_url'],
            'privacy_url'=>$p['privacy_url'],
            'support_email'=>$p['support_email'],
            'approval_digest'=>$review['fingerprint'],
            'bank_account'=>$review['bank_account'],
        ];
    }
}
