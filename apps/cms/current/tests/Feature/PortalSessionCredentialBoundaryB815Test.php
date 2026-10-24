<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Role,User,UserLoginSession};
use App\Notifications\{CustomerPortalInvitationNotification,ResetPasswordNotification};
use App\Services\{CustomerActivationService,PasswordResetService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Notification,Schema};
use Tests\TestCase;

/** B815: isolated real Laravel browser session boundaries, no customer mail. */
final class PortalSessionCredentialBoundaryB815Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('services.turnstile.enabled',false);
        Notification::fake();
    }

    public function test_new_password_login_registers_session_and_allows_dashboard(): void
    {
        $user=$this->person('b815-valid-login','user');
        $this->post('/login',[
            'login'=>$user->email,
            'password'=>'B815-Old-Password123!',
        ])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->get('/')->assertOk();
        // Laravel's isolated test client may rotate its session ID between requests.
        self::assertGreaterThanOrEqual(1,UserLoginSession::query()->where('user_id',$user->id)->count());
    }

    public function test_password_reset_invalidates_existing_authenticated_browser_on_next_request(): void
    {
        $user=$this->person('b815-reset-browser','user');
        $this->login($user);
        $this->get('/account')->assertOk();

        $plain=$this->requestResetToken($user);
        self::assertTrue(app(PasswordResetService::class)->resetPassword(
            $plain,'B815-New-Password123!'
        ));
        $this->assertAuthenticatedAs($user);
        $this->get('/account')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login',[
            'login'=>$user->email,
            'password'=>'B815-New-Password123!',
        ])->assertRedirect('/');
        $this->get('/account')->assertOk();
    }

    public function test_invitation_activation_invalidates_existing_authenticated_browser(): void
    {
        $actor=$this->person('b815-activation-owner','superadmin');
        $user=$this->person('b815-activation-staff','user');
        $this->login($user);
        $this->get('/account')->assertOk();

        app(CustomerActivationService::class)->invite($user,$actor);
        $plain='';
        Notification::assertSentTo(
            $user,CustomerPortalInvitationNotification::class,
            static function(CustomerPortalInvitationNotification $notice) use (&$plain): bool {
                $plain=$notice->token;
                return strlen($plain)===80;
            }
        );
        self::assertSame(80,strlen($plain));
        self::assertNotNull(app(CustomerActivationService::class)->activate(
            $plain,'B815-Activated-Password123!'
        ));
        $this->get('/account')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_revoked_session_missing_from_registry_cannot_resurrect_after_reset(): void
    {
        $user=$this->person('b815-missing-record','user');
        $this->login($user);
        $this->get('/account')->assertOk();
        $plain=$this->requestResetToken($user);
        self::assertTrue(app(PasswordResetService::class)->resetPassword(
            $plain,'B815-New-Password123!'
        ));

        UserLoginSession::query()->where('user_id',$user->id)->delete();
        self::assertDatabaseCount('user_login_sessions',0);
        $this->get('/account')->assertRedirect('/login');
        $this->assertGuest();
        self::assertDatabaseCount('user_login_sessions',0);
    }

    public function test_portal_session_registry_loss_does_not_grant_protected_pages(): void
    {
        $user=$this->person('b815-missing-table','user');
        $this->login($user);
        $this->get('/account')->assertOk();
        Schema::dropIfExists('user_login_sessions');
        $this->get('/account')->assertRedirect('/login');
        $this->assertGuest();
    }

    private function person(string $username,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$username,
            'email'=>$username.'@example.test',
            'first_name'=>'B815 Synthetic',
            'password_hash'=>Hash::make('B815-Old-Password123!'),
            'status'=>'active',
        ]);
    }

    private function login(User $user): void
    {
        $this->post('/login',[
            'login'=>$user->email,
            'password'=>'B815-Old-Password123!',
        ])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    private function requestResetToken(User $user): string
    {
        app(PasswordResetService::class)->sendResetLink((string)$user->email);
        $plain='';
        Notification::assertSentTo(
            $user,ResetPasswordNotification::class,
            static function(ResetPasswordNotification $notice) use (&$plain): bool {
                $plain=$notice->token;
                return strlen($plain)===80;
            }
        );
        self::assertSame(80,strlen($plain));
        return $plain;
    }
}
