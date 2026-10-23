<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Services\PublicCheckoutReadinessService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PublicCheckoutReadinessB799Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('public_storefront.enabled', true);
        config()->set('public_storefront.checkout_enabled', true);
        $this->legalSettings();
        config()->set('public_storefront.terms_url', 'https://cms.ald1n.com/uslovi');
        config()->set('public_storefront.privacy_url', 'https://cms.ald1n.com/privatnost');
        config()->set('public_storefront.shipping_description', 'Isporuka na adresu bez naplate dostave.');
        config()->set('public_storefront.shipping_fee_rsd', '0');
        config()->set('public_storefront.support_email', 'prodaja@example.test');
        config()->set('public_storefront.payment_methods', ['cash_on_delivery']);
    }

    public function test_checkout_remains_closed_even_when_catalog_is_public(): void
    {
        config()->set('public_storefront.checkout_enabled', false);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        app(PublicCheckoutReadinessService::class)->assertReady();
    }

    public function test_genuinely_explicit_free_shipping_and_legal_links_can_pass_preflight(): void
    {
        $settings = app(PublicCheckoutReadinessService::class)->assertReady();
        self::assertSame(0.0, $settings['shipping_fee_rsd']);
        self::assertSame(['cash_on_delivery'], $settings['methods']);
        self::assertSame('https://cms.ald1n.com/uslovi', $settings['terms_url']);
        self::assertNull($settings['bank_account']);
    }

    public function test_missing_or_nonzero_delivery_fee_cannot_be_silently_included_in_items_total(): void
    {
        foreach ([null, '-1', '500', 'free', ''] as $fee) {
            config()->set('public_storefront.shipping_fee_rsd', $fee);
            try {
                app(PublicCheckoutReadinessService::class)->assertReady();
                self::fail('Fee without supported accounting must fail closed');
            } catch (ValidationException $e) {
                self::assertArrayHasKey('checkout', $e->errors());
            }
        }
    }

    public function test_illegal_unsafe_urls_unknown_method_and_missing_merchant_identity_are_blocked(): void
    {
        foreach ([
            ['terms_url'=>'http://localhost/fake'],
            ['privacy_url'=>'javascript:alert(1)'],
            ['privacy_url'=>'https://fake:password@example.test/secret'],
            ['payment_methods'=>['credit_card']],
            ['support_email'=>'not-an-email'],
        ] as $changed) {
            $key=array_key_first($changed);
            $old=config('public_storefront.'.$key);
            config()->set('public_storefront.'.$key, $changed[$key]);
            try {
                app(PublicCheckoutReadinessService::class)->assertReady();
                self::fail('Expected readiness hold for '.$key);
            } catch (ValidationException $e) {
                self::assertArrayHasKey('checkout', $e->errors());
            } finally {
                config()->set('public_storefront.'.$key, $old);
            }
        }
        app(SettingsService::class)->putMany(['documents_company_tax_id'=>''], null);
        $this->expectException(ValidationException::class);
        app(PublicCheckoutReadinessService::class)->assertReady();
    }

    public function test_transfer_method_requires_explicit_active_bank_account(): void
    {
        config()->set('public_storefront.payment_methods', ['bank_transfer']);
        try {
            app(PublicCheckoutReadinessService::class)->assertReady();
            self::fail('Should refuse transfer without account.');
        } catch (ValidationException) {}

        $account=BankAccount::query()->create([
            'label'=>'Sintetička banka','recipient_name'=>'Testno preduzeće',
            'account_number'=>'160000000000000001','account_number_display'=>'160-0000000000000-01',
            'is_active'=>true,
        ]);
        config()->set('public_storefront.bank_account_id', $account->id);
        self::assertSame($account->id,app(PublicCheckoutReadinessService::class)
            ->assertReady()['bank_account']->id);
        $account->update(['is_active'=>false]);
        $this->expectException(ValidationException::class);
        app(PublicCheckoutReadinessService::class)->assertReady();
    }

    private function legalSettings(): void
    {
        app(SettingsService::class)->putMany([
            'documents_company_name'=>'AP Computers Test Trgovac',
            'documents_company_address'=>'Primer ulice 12, Beograd',
            'documents_company_tax_id'=>'100200300',
        ], null);
    }
}
