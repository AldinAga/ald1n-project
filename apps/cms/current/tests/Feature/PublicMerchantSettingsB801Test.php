<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Role;
use App\Models\User;
use App\Services\PublicCheckoutReadinessService;
use App\Services\PublicMerchantPolicyService;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PublicMerchantSettingsB801Test extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        config()->set('public_storefront.enabled',false);
        config()->set('public_storefront.checkout_enabled',false);
        $this->superadmin=$this->user('b801-owner','superadmin');
        app(SettingsService::class)->putMany([
            'documents_company_name'=>'Sinteticki Test Trgovac',
            'documents_company_address'=>'Testna ulica 10',
            'documents_company_city'=>'Beograd',
            'documents_company_tax_id'=>'100200300',
        ],$this->superadmin->id);
    }

    public function test_only_superadmin_can_access_or_mutate_unpublished_merchant_terms(): void
    {
        $this->get('/admin/settings/public-storefront')->assertRedirect('/login');
        $this->put('/admin/settings/public-storefront',$this->data())->assertRedirect('/login');

        $admin=$this->user('b801-regular-admin','admin');
        $this->actingAs($admin)->get('/admin/settings/public-storefront')->assertForbidden();
        $this->actingAs($admin)->put('/admin/settings/public-storefront',
            $this->data(['merchant_approval'=>'1']))->assertForbidden();
        self::assertFalse(app(SettingsService::class)->hasStoredValue('public_checkout_terms_url'));

        $this->actingAs($this->superadmin)
            ->get('/admin/settings/public-storefront')->assertOk()
            ->assertSee('Uslovi javnog poručivanja')->assertSee('Isključen');
    }

    public function test_draft_then_owner_attestation_is_revocable_and_does_not_flip_public_flags(): void
    {
        $this->actingAs($this->superadmin)->put('/admin/settings/public-storefront',$this->data())
            ->assertRedirect()->assertSessionHasNoErrors();
        $merchant=app(PublicMerchantPolicyService::class);
        $review=$merchant->review();
        self::assertTrue($review['complete']);
        self::assertFalse($review['approved']);
        $settings=app(SettingsService::class);
        self::assertSame('',$settings->get('public_checkout_approval_digest'));
        self::assertSame('0',$settings->get('public_checkout_shipping_fee_rsd'));
        self::assertFalse(config('public_storefront.enabled'));
        self::assertFalse(config('public_storefront.checkout_enabled'));

        $this->put('/admin/settings/public-storefront',
            $this->data(['merchant_approval'=>'1']))->assertRedirect()->assertSessionHasNoErrors();
        $approved=$merchant->review();
        self::assertTrue($approved['approved']);
        self::assertSame($approved['fingerprint'],
            $settings->get('public_checkout_approval_digest'));
        self::assertSame((string)$this->superadmin->id,
            $settings->get('public_checkout_approved_by'));
        $this->get('/porucivanje')->assertNotFound('Approval must NOT expose public routes.');
        $this->superadmin->forceFill(['retired_at'=>now()])->save();
        self::assertFalse($merchant->review()['approved'],
            'Retired owner cannot silently authorize future guest sales.');
        $this->superadmin->forceFill(['retired_at'=>null])->save();

        $this->put('/admin/settings/public-storefront',$this->data())
            ->assertRedirect()->assertSessionHasNoErrors();
        self::assertFalse($merchant->review()['approved']);
        self::assertSame('',$settings->get('public_checkout_approval_digest'));
    }

    public function test_missing_bank_account_nonzero_shipping_and_unsafe_legal_url_cannot_be_attested(): void
    {
        foreach ([
            ['payment_methods'=>['bank_transfer'],'bank_account_id'=>''],
            ['shipping_fee_rsd'=>'500'],
            ['terms_url'=>'https://127.0.0.1/fake'],
            ['terms_url'=>'https://0177.0.0.1/fake'],
            ['terms_url'=>'https://127.0.0.1./fake'],
            ['terms_url'=>'https://localhost./fake'],
            ['terms_url'=>'https://2130706433/fake'],
        ] as $invalid) {
            $payload=$this->data($invalid+['merchant_approval'=>'1']);
            $this->actingAs($this->superadmin)
                ->put('/admin/settings/public-storefront',$payload)
                ->assertRedirect()->assertSessionHasNoErrors()
                ->assertSessionHas('approval_refused',
                    static fn (string $message): bool => str_starts_with($message,
                        'Podaci su sačuvani. Odobrenje nije potvrđeno jer nedostaje: '));
            // Changed in B-merchant-draft: the valid input is kept as a draft,
            // only the approval is refused (it used to roll everything back).
            $settings=app(SettingsService::class);
            self::assertSame($payload['terms_url'],$settings->get('public_checkout_terms_url'),
                'A refused approval must still keep the submitted draft.');
            self::assertSame('',$settings->get('public_checkout_approval_digest'));
            self::assertFalse(app(PublicMerchantPolicyService::class)->review()['approved'],
                'An incomplete or unsafe policy cannot be attested.');
        }

        $account=BankAccount::query()->create([
            'label'=>'Synthetic Bank','recipient_name'=>'Test Merchant',
            'account_number'=>'160000000000000001',
            'account_number_display'=>'160-0000000000000-01',
            'is_active'=>true,
        ]);
        $payload=$this->data([
            'merchant_approval'=>'1','payment_methods'=>['bank_transfer'],
            'bank_account_id'=>(string)$account->id,
        ]);
        $this->put('/admin/settings/public-storefront',$payload)
            ->assertRedirect()->assertSessionHasNoErrors();
        self::assertTrue(app(PublicMerchantPolicyService::class)->review()['approved']);
        $account->update(['is_active'=>false]);
        self::assertFalse(app(PublicMerchantPolicyService::class)->review()['approved']);
    }

    public function test_stale_identity_or_merchant_edit_invalidates_approval_even_if_env_flags_are_on(): void
    {
        $this->actingAs($this->superadmin)
            ->put('/admin/settings/public-storefront',$this->data(['merchant_approval'=>'1']))
            ->assertRedirect()->assertSessionHasNoErrors();
        app(SettingsService::class)->putMany(['public_storefront_open'=>'1'],null);
        config()->set('public_storefront.enabled',true);
        config()->set('public_storefront.checkout_enabled',true);
        $policy=app(PublicCheckoutReadinessService::class)->assertReady();
        self::assertSame(0.0,$policy['shipping_fee_rsd']);
        self::assertSame(['cash_on_delivery'],$policy['methods']);

        app(SettingsService::class)->putMany([
            'documents_company_tax_id'=>'100200301',
        ],$this->superadmin->id);
        try {
            app(PublicCheckoutReadinessService::class)->assertReady();
            self::fail('Changed merchant identity cannot inherit prior owner approval.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('checkout',$e->errors());
        }
    }

    public function test_refused_approval_keeps_the_draft_and_names_what_is_missing(): void
    {
        $payload=$this->data([
            'merchant_approval'=>'1','terms_url'=>'','shipping_description'=>'',
            'support_email'=>'draft-kept@example.test',
        ]);
        $this->actingAs($this->superadmin)->put('/admin/settings/public-storefront',$payload)
            ->assertRedirect()->assertSessionHasNoErrors()
            ->assertSessionHas('approval_refused',
                'Podaci su sačuvani. Odobrenje nije potvrđeno jer nedostaje: '
                .'HTTPS adresa uslova prodaje, opis uslova dostave.');

        $settings=app(SettingsService::class);
        self::assertSame('draft-kept@example.test',$settings->get('public_checkout_support_email'));
        self::assertSame('https://cms.ald1n.com/privatnost',$settings->get('public_checkout_privacy_url'));
        self::assertSame('',$settings->get('public_checkout_approval_digest'));
        self::assertSame('',$settings->get('public_checkout_approved_by'));
        self::assertFalse(app(PublicMerchantPolicyService::class)->review()['approved']);

        $this->get('/admin/settings/public-storefront')->assertOk()
            ->assertSee('Odobrenje nije potvrđeno jer nedostaje: HTTPS adresa uslova prodaje, opis uslova dostave.')
            ->assertSee('draft-kept@example.test');
    }

    public function test_refused_approval_still_revokes_an_older_approval_and_store_stays_locked(): void
    {
        $this->actingAs($this->superadmin)
            ->put('/admin/settings/public-storefront',$this->data(['merchant_approval'=>'1']))
            ->assertRedirect()->assertSessionHasNoErrors();
        self::assertTrue(app(PublicMerchantPolicyService::class)->review()['approved']);

        $this->put('/admin/settings/public-storefront',
            $this->data(['merchant_approval'=>'1','privacy_url'=>'']))
            ->assertSessionHas('approval_refused');
        $settings=app(SettingsService::class);
        self::assertSame('',$settings->get('public_checkout_privacy_url'),'The edit itself is kept.');
        self::assertSame('',$settings->get('public_checkout_approval_digest'));
        self::assertFalse(app(PublicMerchantPolicyService::class)->review()['approved']);

        config()->set('public_storefront.enabled',true);
        config()->set('public_storefront.checkout_enabled',true);
        $this->post('/admin/settings/public-storefront/toggle',['enabled'=>'1'])
            ->assertSessionHasErrors('enabled');
        self::assertFalse(app(\App\Services\PublicStorefrontStateService::class)->isOpen());
    }

    public function test_repeated_submit_of_the_same_form_keeps_the_approval(): void
    {
        $payload=$this->data(['merchant_approval'=>'1']);
        $this->actingAs($this->superadmin)->put('/admin/settings/public-storefront',$payload)
            ->assertRedirect()->assertSessionHasNoErrors();
        $settings=app(SettingsService::class);
        $merchant=app(PublicMerchantPolicyService::class);
        self::assertTrue($merchant->review()['approved']);
        $digest=$settings->get('public_checkout_approval_digest');
        $approvedAt=$settings->get('public_checkout_approved_at');
        self::assertNotSame('',$digest);

        // Double click / second tab: the same body, still carrying the old fingerprint.
        $this->put('/admin/settings/public-storefront',$payload)
            ->assertRedirect()->assertSessionHasNoErrors()
            ->assertSessionMissing('approval_refused')
            ->assertSessionHas('status','Podešavanja su već sačuvana. Ništa nije promenjeno.');
        self::assertTrue($merchant->review()['approved'],'The repeated submit must not revoke the approval.');
        self::assertSame($digest,$settings->get('public_checkout_approval_digest'));
        self::assertSame($approvedAt,$settings->get('public_checkout_approved_at'));
        self::assertSame(1,\App\Models\AuditLog::query()
            ->where('action','settings.public_checkout_policy_updated')->count());
    }

    public function test_out_of_date_tab_with_other_values_writes_nothing_and_keeps_the_approval(): void
    {
        $payload=$this->data(['merchant_approval'=>'1']);
        $this->actingAs($this->superadmin)->put('/admin/settings/public-storefront',$payload)
            ->assertRedirect()->assertSessionHasNoErrors();
        $merchant=app(PublicMerchantPolicyService::class);
        self::assertTrue($merchant->review()['approved']);

        $older=array_replace($payload,['support_email'=>'older-tab@example.test']);
        $this->put('/admin/settings/public-storefront',$older)
            ->assertRedirect()->assertSessionHasNoErrors()
            ->assertSessionHas('approval_refused',
                'Uslovi su se promenili posle učitavanja stranice. Osveži stranicu, proveri uslove i potvrdi ponovo.'
                .' Tvoj unos je zadržan u formi i nije sačuvan.')
            ->assertSessionHasInput('support_email','older-tab@example.test');
        self::assertSame('retail@example.test',
            app(SettingsService::class)->get('public_checkout_support_email'),'Nothing from the old tab is written.');
        self::assertTrue($merchant->review()['approved'],'The approval given in the newer request stays.');
        self::assertSame(1,\App\Models\AuditLog::query()
            ->where('action','settings.public_checkout_policy_updated')->count());

        // Same stale tab without approval is refused the same way.
        unset($older['merchant_approval']);
        $this->put('/admin/settings/public-storefront',$older)->assertSessionHas('approval_refused');
        self::assertSame('retail@example.test',
            app(SettingsService::class)->get('public_checkout_support_email'));
    }

    public function test_every_checklist_code_has_a_label_for_the_missing_items_message(): void
    {
        $settings=app(SettingsService::class);
        $codes=[];
        // State A: every field wrong and no methods; state B: methods that need
        // pickup/own delivery and a bank account.
        $settings->putMany([
            'documents_company_name'=>'','public_checkout_terms_url'=>'http://insecure.example',
            'public_checkout_privacy_url'=>'','public_checkout_shipping_description'=>'',
            'public_checkout_shipping_fee_rsd'=>'5','public_checkout_support_email'=>'',
            'public_checkout_payment_methods'=>'','public_checkout_delivery_methods'=>'',
            'public_checkout_bank_account_id'=>'',
        ],$this->superadmin->id);
        $codes=array_merge($codes,app(PublicMerchantPolicyService::class)->review()['issue_codes']);
        $settings->putMany([
            'public_checkout_payment_methods'=>'bank_transfer,cash',
            'public_checkout_delivery_methods'=>'courier',
        ],$this->superadmin->id);
        $codes=array_merge($codes,app(PublicMerchantPolicyService::class)->review()['issue_codes']);
        $codes=array_values(array_unique($codes));
        sort($codes);

        $expected=['bank_account','cash_delivery','delivery_methods','identity','payment_methods',
            'privacy_url','shipping_description','shipping_fee','support_email','terms_url'];
        self::assertSame($expected,$codes,'review() produced a code this test does not know: add a label.');
        $labels=(new \ReflectionClassConstant(
            \App\Http\Controllers\Admin\PublicMerchantSettingsController::class,'ISSUE_LABELS'))->getValue();
        $labelCodes=array_keys($labels);
        sort($labelCodes);
        self::assertSame($expected,$labelCodes);
        foreach ($labels as $code=>$label) {
            self::assertNotSame('',trim($label),$code);
        }
    }

    public function test_input_that_fails_validation_saves_nothing_and_grants_nothing(): void
    {
        foreach ([
            'support_email'=>['support_email'=>'not-an-email'],
            'shipping_fee_rsd'=>['shipping_fee_rsd'=>'abc'],
            'payment_methods.0'=>['payment_methods'=>['not-a-method']],
            'terms_url'=>['terms_url'=>'not a url'],
        ] as $errorKey=>$invalid) {
            $this->actingAs($this->superadmin)->put('/admin/settings/public-storefront',
                $this->data($invalid+['merchant_approval'=>'1']))
                ->assertRedirect()->assertSessionHasErrors($errorKey)
                ->assertSessionMissing('approval_refused');
            self::assertFalse(app(SettingsService::class)->hasStoredValue('public_checkout_support_email'),
                'Invalid input must not be stored as a draft.');
            self::assertFalse(app(SettingsService::class)->hasStoredValue('public_checkout_approved_by'));
        }
    }

    /** @param array<string,mixed> $overrides @return array<string,mixed> */
    private function data(array $overrides=[]): array
    {
        // An approving submit carries the fingerprint the settings page
        // showed, i.e. the stored state just before this request.
        if (($overrides['merchant_approval']??null)==='1' && !array_key_exists('approval_fingerprint',$overrides)) {
            $overrides['approval_fingerprint']=app(PublicMerchantPolicyService::class)->review()['fingerprint'];
        }
        return array_replace([
            'terms_url'=>'https://cms.ald1n.com/uslovi',
            'privacy_url'=>'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd'=>'0',
            'shipping_description'=>'Synthetic agreement: collection included in retail price.',
            'payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','own_transport','customer_pickup'],
            'support_email'=>'retail@example.test',
            'bank_account_id'=>'',
        ],$overrides);
    }

    private function user(string $name,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$name,'email'=>$name.'@example.test',
            'first_name'=>'Test','password_hash'=>Hash::make('SyntheticOnly123!'),
            'status'=>'active',
        ]);
    }
}
