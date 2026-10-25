<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Role,User};
use App\Services\{PublicMerchantPolicyService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Real HTTP boundary: no spoofed request authority in buyer-login Location. */
final class StorefrontCheckoutOriginB888Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('app.url','https://cms.ald1n.com');
        config()->set('public_storefront.enabled',true);
        config()->set('public_storefront.checkout_enabled',true);
        foreach ([
            'terms_url'=>'https://cms.ald1n.com/uslovi',
            'privacy_url'=>'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd'=>'0',
            'shipping_description'=>'B888 synthetic shipping',
            'payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','own_transport','customer_pickup'],
            'support_email'=>'support@example.test',
        ] as $key=>$value) config()->set('public_storefront.'.$key,$value);
        $owner=User::query()->create([
            'role_id'=>Role::query()->where('slug','superadmin')->valueOrFail('id'),
            'username'=>'b888-owner','email'=>'b888-owner@example.test',
            'first_name'=>'Synthetic','password_hash'=>Hash::make('B888-Synthetic!23'),
            'status'=>'active',
        ]);
        $settings=app(SettingsService::class);
        $settings->putMany([
            'public_storefront_open'=>'1',
            'documents_company_name'=>'Synthetic Merchant',
            'documents_company_address'=>'Synthetic Address 1',
            'documents_company_city'=>'Beograd',
            'documents_company_tax_id'=>'100200300',
        ],(int)$owner->id);
        $review=app(PublicMerchantPolicyService::class)->review();
        self::assertTrue($review['complete'],implode('; ',$review['issues']));
        $settings->putMany([
            'public_checkout_approval_digest'=>$review['fingerprint'],
            'public_checkout_approved_by'=>(string)$owner->id,
            'public_checkout_approved_at'=>now()->toISOString(),
        ],(int)$owner->id);
    }

    public function test_guest_checkout_get_and_post_only_redirect_to_relative_login(): void
    {
        foreach (['GET','POST'] as $method) {
            $request=$this->withHeaders([
                'Host'=>'attacker.synthetic.invalid',
                'X-Forwarded-Host'=>'forwarded.synthetic.invalid',
                'X-Forwarded-Proto'=>'http',
                'Referer'=>'https://attacker.synthetic.invalid/path',
                'Origin'=>'https://attacker.synthetic.invalid',
            ]);
            $response=$method==='GET'
                ? $request->get('/porucivanje')
                : $request->post('/porucivanje',['checkout_key'=>str_repeat('a',48)]);
            $response->assertRedirect('/login');
            self::assertSame('/login',(string)$response->headers->get('Location'));
            self::assertDatabaseCount('orders',0);
            self::assertDatabaseCount('public_checkout_attempts',0);
        }
    }

    public function test_json_unauthenticated_checkout_preserves_401_without_redirect(): void
    {
        $response=$this->postJson('/porucivanje',[
            'checkout_key'=>str_repeat('a',48),
        ]);
        $response->assertUnauthorized();
        self::assertFalse($response->headers->has('Location'));
        self::assertDatabaseCount('orders',0);
    }

    public function test_storefront_off_remains_404_not_login_redirect(): void
    {
        config()->set('public_storefront.enabled',false);
        $this->get('/porucivanje')->assertNotFound();
        $this->post('/porucivanje',['checkout_key'=>str_repeat('a',48)])
            ->assertNotFound();
    }
}
