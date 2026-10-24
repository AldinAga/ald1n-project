<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Services\AuditLogger;
use App\Services\PublicMerchantPolicyService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class PublicMerchantSettingsController extends Controller
{
    public function index(Request $request, PublicMerchantPolicyService $merchant): View
    {
        $this->authorizeOwner($request);
        return view('admin.settings.public-storefront',[
            'review'=>$merchant->review(),
            'bankAccounts'=>BankAccount::query()->where('is_active',true)->orderBy('label')
                ->get(['id','label','recipient_name']),
            'catalogEnabled'=>config('public_storefront.enabled')===true,
            'checkoutEnabled'=>config('public_storefront.checkout_enabled')===true,
        ]);
    }

    public function update(
        Request $request,
        SettingsService $settings,
        PublicMerchantPolicyService $merchant,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorizeOwner($request);
        $data=$request->validate([
            'terms_url'=>['nullable','string','url','max:500'],
            'privacy_url'=>['nullable','string','url','max:500'],
            'shipping_description'=>['nullable','string','max:1000'],
            'shipping_fee_rsd'=>['nullable','regex:/^\d+(?:\.\d{1,2})?$/D'],
            'support_email'=>['nullable','email:rfc','max:254'],
            'payment_methods'=>['sometimes','array','max:2'],
            'payment_methods.*'=>[Rule::in(['cash_on_delivery','bank_transfer'])],
            'bank_account_id'=>['nullable','integer','min:1'],
            'merchant_approval'=>['nullable',Rule::in(['1'])],
        ]);
        $methods=array_values(array_unique(array_map('strval',
            (array)($data['payment_methods']??[]))));
        sort($methods,SORT_STRING);
        $account=(string)($data['bank_account_id']??'');
        if ($account!=='' && !BankAccount::query()->whereKey((int)$account)
            ->where('is_active',true)->exists()) {
            throw ValidationException::withMessages(['bank_account_id'=>
                'Mora biti izabran postojeći aktivan žiro račun.']);
        }
        $owner=$request->user();
        $before=$merchant->review();

        // All data, attestation and revocation are one atomic DB write. A
        // partially saved policy cannot inherit the signature of old terms.
        DB::transaction(function () use ($settings,$merchant,$data,$methods,$account,$owner): void {
            $values=[
                'public_checkout_terms_url'=>trim((string)($data['terms_url']??'')),
                'public_checkout_privacy_url'=>trim((string)($data['privacy_url']??'')),
                'public_checkout_shipping_fee_rsd'=>trim((string)($data['shipping_fee_rsd']??'')),
                'public_checkout_shipping_description'=>trim((string)($data['shipping_description']??'')),
                'public_checkout_payment_methods'=>implode(',',$methods),
                'public_checkout_support_email'=>trim((string)($data['support_email']??'')),
                'public_checkout_bank_account_id'=>$account,
                'public_checkout_approval_digest'=>'',
                'public_checkout_approved_at'=>'',
                'public_checkout_approved_by'=>'',
            ];
            $settings->putMany($values,(int)$owner->id);
            if (($data['merchant_approval']??null)==='1') {
                $review=$merchant->review();
                if (!$review['complete']) {
                    throw ValidationException::withMessages(['merchant_approval'=>
                        'Nije moguće potvrditi nepotpune uslove. Proveri kontrolnu listu.']);
                }
                $settings->putMany([
                    'public_checkout_approval_digest'=>$review['fingerprint'],
                    'public_checkout_approved_at'=>now()->toISOString(),
                    'public_checkout_approved_by'=>(string)$owner->id,
                ],(int)$owner->id);
            }
        },5);
        $after=$merchant->review();
        // Record whether a human actually attested to these EXACT terms, but
        // never write account numbers, legal text or secrets to audit metadata.
        $audit->log('settings.public_checkout_policy_updated',
            'SuperAdministrator je ažurirao uslove javnog poručivanja.',
            null,
            ['fingerprint'=>$before['fingerprint'],'approved'=>$before['approved']],
            ['fingerprint'=>$after['fingerprint'],'approved'=>$after['approved']],
            user:$owner,
        );

        return back()->with('status',$after['approved']
            ? 'Poslovni uslovi su potvrđeni. Produkciono poručivanje ostaje odvojeno zaključano.'
            : 'Nacrt je sačuvan. Javno poručivanje ostaje zaključano bez važeće potvrde.');
    }

    private function authorizeOwner(Request $request): void
    {
        abort_unless($request->user()?->hasRole('superadmin')===true,403);
    }
}
