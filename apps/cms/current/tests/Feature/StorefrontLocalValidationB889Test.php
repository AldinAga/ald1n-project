<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Role,User,UserLoginSession};
use App\Services\{PublicMerchantPolicyService,PublicRegistrationMailSenderService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\{Hash,Mail,Notification};
use Tests\TestCase;

/** B889: non-JSON storefront errors must never follow attacker Referer/Host.
 * Independent synthetic HTTP coverage; no protected historical fixture edits.
 */
final class StorefrontLocalValidationB889Test extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        Notification::fake();
        config()->set('app.url','https://cms.ald1n.com');
        config()->set('services.turnstile.enabled',false);
        config()->set('mail.default','array');
        foreach ([
            'enabled'=>true,'checkout_enabled'=>true,
            'terms_url'=>'https://cms.ald1n.com/uslovi',
            'privacy_url'=>'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd'=>'0',
            'shipping_description'=>'B889 synthetic-only courier',
            'payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','own_transport','customer_pickup'],
            'support_email'=>'support@example.test',
        ] as $key=>$value) config()->set('public_storefront.'.$key,$value);
        $owner=$this->user('b889-owner','superadmin');
        $this->buyer=$this->user('b889-buyer','customer');
        $senderAddress='registration@synthetic.example.test';
        $senderName='B889 synthetic only';
        $settings=app(SettingsService::class);
        $settings->putMany([
            'public_storefront_open'=>'1',
            'documents_company_name'=>'B889 Synthetic Merchant',
            'documents_company_address'=>'Test 10',
            'documents_company_city'=>'Beograd',
            'documents_company_tax_id'=>'100200300',
            'public_registration_sender_address'=>$senderAddress,
            'public_registration_sender_name'=>$senderName,
            'public_registration_sender_digest'=>PublicRegistrationMailSenderService::fingerprint($senderAddress,$senderName),
            'public_registration_sender_approved_by'=>(string)$owner->id,
            'public_registration_sender_approved_at'=>now()->toISOString(),
        ],(int)$owner->id);
        $review=app(PublicMerchantPolicyService::class)->review();
        self::assertTrue($review['complete'],implode('; ',$review['issues']));
        $settings->putMany([
            'public_checkout_approval_digest'=>$review['fingerprint'],
            'public_checkout_approved_by'=>(string)$owner->id,
            'public_checkout_approved_at'=>now()->toISOString(),
        ],(int)$owner->id);
    }

    public function test_invalid_public_registration_html_redirects_locally_without_creating_buyer(): void
    {
        $response=$this->hostile()->post('/registracija',[
            'first_name'=>'B889','last_name'=>'Synthetic',
            'email'=>'invalid','accepted_privacy'=>'1',
            'cf-turnstile-response'=>'SECRET_SHOULD_NOT_FLASH',
        ])->assertRedirect('/registracija')->assertSessionHasErrors('email');
        self::assertSame('/registracija',(string)$response->headers->get('Location'));
        self::assertNull(session()->getOldInput('cf-turnstile-response'));
        self::assertDatabaseCount('users',2);
        self::assertDatabaseCount('user_activation_tokens',0);
        Notification::assertNothingSent();
    }

    public function test_invalid_registered_buyer_checkout_html_stays_local_without_stock_or_order_write(): void
    {
        $this->loginBuyer();
        $response=$this->hostileReferer()->post('/porucivanje',[
            'checkout_key'=>'INVALID_NONCE_B889',
            'merchant_policy_fingerprint'=>str_repeat('a',64),
            'email'=>$this->buyer->email,
        ])->assertRedirect('/porucivanje')->assertSessionHasErrors('checkout_key');
        self::assertSame('/porucivanje',(string)$response->headers->get('Location'));
        self::assertNull(session()->getOldInput('checkout_key'));
        self::assertNull(session()->getOldInput('merchant_policy_fingerprint'));
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('public_checkout_attempts',0);
    }

    public function test_json_invalid_checkout_keeps_native_422_and_no_location(): void
    {
        $this->loginBuyer();
        $response=$this->hostileReferer()->postJson('/porucivanje',[
            'checkout_key'=>'INVALID_NONCE_B889',
            'merchant_policy_fingerprint'=>str_repeat('a',64),
        ])->assertUnprocessable()->assertJsonValidationErrors('checkout_key');
        self::assertFalse($response->headers->has('Location'));
        self::assertDatabaseCount('orders',0);
    }

    public function test_revoked_registered_buyer_html_session_fails_closed_with_relative_login(): void
    {
        $this->loginBuyer();
        self::assertSame(1,UserLoginSession::query()->where('user_id',$this->buyer->id)->count());
        $revoked=UserLoginSession::query()->where('user_id',$this->buyer->id)
            ->whereNull('revoked_at')->update(['revoked_at'=>now()]);
        self::assertSame(1,$revoked);
        $response=$this->hostile()->get('/porucivanje')->assertRedirect('/login');
        self::assertSame('/login',(string)$response->headers->get('Location'));
        $this->assertGuest();
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('public_checkout_attempts',0);
    }

    public function test_missing_buyer_tracking_row_is_not_silently_recreated_by_checkout(): void
    {
        $this->loginBuyer();
        $deleted=UserLoginSession::query()->where('user_id',$this->buyer->id)->delete();
        self::assertSame(1,$deleted);

        // The guard remains authenticated, but the tracked browser session
        // was destroyed. A new checkout cannot bootstrap another registry.
        $response=$this->hostileReferer()->postJson('/porucivanje',[
            'checkout_key'=>str_repeat('c',48),
            'merchant_policy_fingerprint'=>str_repeat('d',64),
        ])->assertUnauthorized();
        self::assertFalse($response->headers->has('Location'));
        self::assertDatabaseCount('user_login_sessions',0);
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('public_checkout_attempts',0);
        $this->assertGuest();
    }

    public function test_authenticated_session_rotation_preserves_exact_active_tracked_row_without_duplicate(): void
    {
        $this->loginBuyer();
        $first=UserLoginSession::query()->where('user_id',$this->buyer->id)->sole();
        $oldHash=$first->session_hash;

        // Exercise a server-authorized session ID rotation with a retained
        // valid credential proof. It must migrate only this active registry
        // record, not create a second session or rescue a revoked record.
        $session=app('session.store');
        $session->migrate(true);
        $rotatedHash=hash('sha256',$session->getId());
        self::assertNotSame($oldHash,$rotatedHash);
        $request=\Illuminate\Http\Request::create('/porucivanje','GET');
        $request->setLaravelSession($session);
        self::assertTrue(app(\App\Services\PortalSessionService::class)
            ->validateAndTouch($request,$this->buyer));
        self::assertDatabaseCount('user_login_sessions',1);
        self::assertSame($first->id,UserLoginSession::query()->sole()->id);
        self::assertSame($rotatedHash,UserLoginSession::query()->sole()->session_hash);
    }

    private function user(string $name,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$name,'email'=>$name.'@example.test','first_name'=>'B889 Synthetic',
            'password_hash'=>Hash::make('B889-Synthetic-Only123!'),'status'=>'active',
            'email_verified_at'=>now(),'portal_activated_at'=>now(),
        ]);
    }

    private function loginBuyer(): void
    {
        $response=$this->post('/login',[
            'login'=>$this->buyer->email,'password'=>'B889-Synthetic-Only123!',
        ])->assertRedirect('/korpa');
        $this->assertAuthenticatedAs($this->buyer);

        // Laravel feature tests auto-encrypt withCookie() values and omit
        // cookies from JSON calls unless withCredentials() is set. The real
        // encrypted Set-Cookie value MUST pass through unchanged, including
        // for JSON, or later requests get a different session ID while
        // the test's cached Auth guard wrongly retains the logged-in user.
        $this->withCredentials();
        $sessionCookieSeen=false;
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName()!==(string)config('session.cookie')) continue;
            $this->withUnencryptedCookie($cookie->getName(),$cookie->getValue());
            $sessionCookieSeen=true;
        }
        self::assertTrue($sessionCookieSeen,'B890_TEST_BROWSER_SESSION_COOKIE_MISSING');
    }

    private function hostileReferer(): static
    {
        // Keep the authenticated PHP session's request authority stable.
        // Only the untrusted Referer/Origin is changed for this boundary.
        $this->withHeaders([
            'Referer'=>'https://attacker.synthetic.invalid/fake',
            'Origin'=>'https://attacker.synthetic.invalid',
        ]);
        return $this;
    }

    private function hostile(): static
    {
        $this->withHeaders([
            'Host'=>'attacker.synthetic.invalid',
            'X-Forwarded-Host'=>'forwarded.synthetic.invalid',
            'X-Forwarded-Proto'=>'http',
            'Referer'=>'https://attacker.synthetic.invalid/fake',
            'Origin'=>'https://attacker.synthetic.invalid',
        ]);
        return $this;
    }
}
