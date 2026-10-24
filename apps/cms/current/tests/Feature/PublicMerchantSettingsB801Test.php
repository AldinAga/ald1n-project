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
                ->assertRedirect()->assertSessionHasErrors('merchant_approval');
            self::assertFalse(app(SettingsService::class)->hasStoredValue('public_checkout_terms_url'),
                'An invalid signed attempt must not persist partial merchant settings.');
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

    /** @param array<string,mixed> $overrides @return array<string,mixed> */
    private function data(array $overrides=[]): array
    {
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
