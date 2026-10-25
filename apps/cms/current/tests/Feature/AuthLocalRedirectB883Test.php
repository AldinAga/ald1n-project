<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Role,User};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\{Hash,Mail,Notification};
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * B883-B886: auth POST 302 must never echo attacker Host/Forwarded/Referer.
 * Genuine Laravel requests on synthetic MariaDB; never standalone CSRF probes,
 * real Google/Turnstile/SMTP, customer data, production or guarded old fixtures.
 */
final class AuthLocalRedirectB883Test extends TestCase
{
    use RefreshDatabase;

    private const ATTACKER='https://external.synthetic.invalid/steal';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('app.url','https://cms.ald1n.com');
        config()->set('services.turnstile.enabled',false);
        Mail::fake();
        Notification::fake();
    }

    public function test_valid_operator_login_uses_relative_dashboard_despite_host_spoof(): void
    {
        $operator=$this->person('b883-operator','superadmin');
        $response=$this->hostile()->post('/login',[
            'login'=>$operator->username,'password'=>'B883-Synthetic-Only123!',
        ])->assertRedirect('/');
        $this->assertLocal($response,'/');
        $this->assertAuthenticatedAs($operator);
    }

    public function test_valid_buyer_login_preserves_relative_cart(): void
    {
        $buyer=$this->person('b883-buyer','customer');
        $response=$this->hostile()->post('/login',[
            'login'=>$buyer->email,'password'=>'B883-Synthetic-Only123!',
        ])->assertRedirect('/korpa');
        $this->assertLocal($response,'/korpa');
        $this->assertAuthenticatedAs($buyer);
    }

    public function test_wrong_password_error_stays_local_and_never_flashes_password(): void
    {
        $operator=$this->person('b883-wrong','superadmin');
        $response=$this->hostile()->post('/login',[
            'login'=>$operator->email,'password'=>'WRONG_SECRET_B883',
        ])->assertRedirect('/login')->assertSessionHasErrors('login');
        $this->assertLocal($response,'/login');
        $this->assertGuest();
        self::assertNull(session()->getOldInput('password'));
        self::assertSame($operator->email, session()->getOldInput('login'));
    }

    public function test_unverified_user_is_denied_without_referrer_redirect(): void
    {
        $buyer=$this->person('b883-blocked','customer','blocked');
        $response=$this->hostile()->post('/login',[
            'login'=>$buyer->email,'password'=>'B883-Synthetic-Only123!',
        ])->assertRedirect('/login')->assertSessionHasErrors('login');
        $this->assertLocal($response,'/login');
        $this->assertGuest();
    }

    public function test_login_form_request_missing_password_cannot_redirect_to_referer(): void
    {
        $response=$this->hostile()->post('/login',[
            'login'=>'b883-missing@example.test',
        ])->assertRedirect('/login')->assertSessionHasErrors('password');
        $this->assertLocal($response,'/login');
        $this->assertGuest();
    }

    public function test_operator_logout_uses_relative_local_login_destination(): void
    {
        $operator=$this->person('b883-logout','superadmin');
        $this->post('/login',[
            'login'=>$operator->username,'password'=>'B883-Synthetic-Only123!',
        ])->assertRedirect('/');
        $this->assertAuthenticatedAs($operator);
        $response=$this->hostile()->post('/logout')->assertRedirect('/login');
        $this->assertLocal($response,'/login');
        $this->assertGuest();
    }

    public function test_password_request_validation_invalid_email_stays_local(): void
    {
        $response=$this->hostile()->post('/forgot-password',[
            'email'=>'not-an-email',
        ])->assertRedirect('/forgot-password')->assertSessionHasErrors('email');
        $this->assertLocal($response,'/forgot-password');
        $this->assertDatabaseCount('password_reset_tokens',0);
        Notification::assertNothingSent();
    }

    public function test_unknown_password_request_is_non_enumerating_and_local(): void
    {
        $response=$this->hostile()->post('/forgot-password',[
            'email'=>'b883-absent@example.test',
        ])->assertRedirect('/forgot-password')->assertSessionHas('status');
        $this->assertLocal($response,'/forgot-password');
        $this->assertDatabaseCount('password_reset_tokens',0);
        Notification::assertNothingSent();
    }

    public function test_known_password_request_is_local_with_fake_notification_only(): void
    {
        $user=$this->person('b883-reset-account','user');
        $response=$this->hostile()->post('/forgot-password',[
            'email'=>$user->email,
        ])->assertRedirect('/forgot-password')->assertSessionHas('status');
        $this->assertLocal($response,'/forgot-password');
        $this->assertDatabaseCount('password_reset_tokens',1);
        Notification::assertCount(1);
    }

    public function test_reset_form_validation_bad_token_never_follows_external_referer(): void
    {
        $response=$this->hostile()->post('/reset-password',[
            'token'=>'bad',
            'password'=>'ValidSynthetic123!',
            'password_confirmation'=>'ValidSynthetic123!',
        ])->assertRedirect('/forgot-password')->assertSessionHasErrors('token');
        $this->assertLocal($response,'/forgot-password');
        $this->assertDatabaseCount('password_reset_tokens',0);
    }

    public function test_reset_form_valid_format_invalid_password_keeps_local_token_page(): void
    {
        $token=str_repeat('A',80);
        $response=$this->hostile()->post('/reset-password',[
            'token'=>$token,'password'=>'short','password_confirmation'=>'short',
        ])->assertRedirect('/reset-password/'.$token)->assertSessionHasErrors('password');
        $this->assertLocal($response,'/reset-password/'.$token);
        $this->assertDatabaseCount('password_reset_tokens',0);
    }

    public function test_reset_unknown_valid_shape_token_never_uses_referer(): void
    {
        $token=str_repeat('B',80);
        $response=$this->hostile()->post('/reset-password',[
            'token'=>$token,'password'=>'ValidSynthetic123!',
            'password_confirmation'=>'ValidSynthetic123!',
        ])->assertRedirect('/reset-password/'.$token)->assertSessionHasErrors('token');
        $this->assertLocal($response,'/reset-password/'.$token);
        $this->assertDatabaseCount('users',0);
    }

    public function test_activation_bad_shape_validation_returns_local_login(): void
    {
        $response=$this->hostile()->post('/activate-account',[
            'token'=>'bad','password'=>'ValidSynthetic123!',
            'password_confirmation'=>'ValidSynthetic123!',
        ])->assertRedirect('/login')->assertSessionHasErrors('token');
        $this->assertLocal($response,'/login');
        $this->assertDatabaseCount('users',0);
    }

    public function test_activation_validation_error_keeps_only_local_alnum_token(): void
    {
        $token=str_repeat('C',80);
        $response=$this->hostile()->post('/activate-account',[
            'token'=>$token,'password'=>'weak','password_confirmation'=>'weak',
        ])->assertRedirect('/activate-account/'.$token)->assertSessionHasErrors('password');
        $this->assertLocal($response,'/activate-account/'.$token);
        $this->assertDatabaseCount('user_activation_tokens',0);
    }

    public function test_activation_expired_or_unknown_token_error_keeps_local_form(): void
    {
        $token=str_repeat('D',80);
        $response=$this->hostile()->post('/activate-account',[
            'token'=>$token,'password'=>'ValidSynthetic123!',
            'password_confirmation'=>'ValidSynthetic123!',
        ])->assertRedirect('/activate-account/'.$token)->assertSessionHasErrors('token');
        $this->assertLocal($response,'/activate-account/'.$token);
        $this->assertDatabaseCount('user_activation_tokens',0);
        Notification::assertNothingSent();
    }

    public function test_json_login_validation_preserves_real_422_instead_of_html_redirect(): void
    {
        $response=$this->hostile()->postJson('/login',[
            'login'=>'b883-json@example.test',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        self::assertFalse($response->headers->has('Location'));
        $this->assertGuest();
    }

    public function test_json_activation_validation_still_returns_real_422(): void
    {
        $response=$this->hostile()->postJson('/activate-account',[
            'token'=>'invalid','password'=>'weak',
        ])->assertUnprocessable()->assertJsonValidationErrors(['token','password']);
        self::assertFalse($response->headers->has('Location'));
        $this->assertDatabaseCount('user_activation_tokens',0);
    }

    private function hostile(): static
    {
        $this->withHeaders([
            'Host'=>'spoofed.synthetic.invalid',
            'X-Forwarded-Host'=>'forwarded.synthetic.invalid',
            'X-Forwarded-Proto'=>'http',
            'Referer'=>self::ATTACKER,
            'Origin'=>self::ATTACKER,
        ]);
        return $this;
    }

    private function assertLocal(TestResponse $response,string $target): void
    {
        $actual=(string)$response->headers->get('Location');
        self::assertSame($target,$actual);
        self::assertStringNotContainsString('synthetic.invalid',$actual);
        self::assertStringNotContainsString('cms.ald1n.com',$actual);
        $response->assertHeader('Referrer-Policy','no-referrer');
        self::assertStringContainsString('no-store',
            (string)$response->headers->get('Cache-Control'));
        $response->assertHeader('X-Content-Type-Options','nosniff');
    }

    private function person(string $username,string $role,string $status='active'): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$username,
            'email'=>$username.'@example.test',
            'first_name'=>'B883 Synthetic',
            'password_hash'=>Hash::make('B883-Synthetic-Only123!'),
            'status'=>$status,
            'email_verified_at'=>now(),
            'portal_activated_at'=>now(),
        ]);
    }
}
