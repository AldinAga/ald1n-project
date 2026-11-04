<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Role, User, UserLoginSession};
use App\Notifications\{CustomerPortalInvitationNotification, ResetPasswordNotification};
use App\Services\{CustomerActivationService, PasswordResetService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Hash, Notification};
use Tests\TestCase;

/**
 * B814 disposable MariaDB: credential recovery must invalidate tracked
 * web sessions as well as remember cookies and API tokens.
 * No existing legacy guest tests or production accounts are altered.
 */
final class CredentialWebSessionRevocationB814Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        Notification::fake();
    }

    public function test_staff_password_reset_revokes_all_tracked_web_sessions_and_api_tokens(): void
    {
        $owner=$this->person('b814-reset-staff','user');
        $session1=$this->trackedSession($owner,'b814-staff-browser-a',false);
        $session2=$this->trackedSession($owner,'b814-staff-browser-b',true);
        $owner->createToken('b814-old-api-access');
        $before=(string)$owner->remember_token;

        $token=$this->passwordResetToken($owner);
        self::assertTrue(app(PasswordResetService::class)->resetPassword(
            $token,'B814-New-Password123!'
        ));

        $this->assertRevoked($session1);
        $this->assertRevoked($session2);
        self::assertDatabaseCount('personal_access_tokens',0);
        self::assertDatabaseCount('password_reset_tokens',0);
        self::assertNotSame($before,(string)$owner->fresh()->remember_token);
        self::assertTrue(Hash::check('B814-New-Password123!',(string)$owner->fresh()->password_hash));
    }

    public function test_registered_customer_password_reset_revokes_old_web_session(): void
    {
        $buyer=$this->person('b814-reset-buyer','customer');
        $session=$this->trackedSession($buyer,'b814-buyer-browser',false);
        $token=$this->passwordResetToken($buyer);

        self::assertTrue(app(PasswordResetService::class)->resetPassword(
            $token,'B814-Buyer-Password123!'
        ));
        $this->assertRevoked($session);
        self::assertTrue(Hash::check('B814-Buyer-Password123!',(string)$buyer->fresh()->password_hash));
    }

    public function test_existing_staff_invitation_activation_revokes_web_sessions(): void
    {
        $actor=$this->person('b814-inviting-owner','superadmin');
        $staff=$this->person('b814-invited-staff','user');
        $session=$this->trackedSession($staff,'b814-invited-existing-browser',true);
        $staff->createToken('b814-staff-activation-old-token');

        app(CustomerActivationService::class)->invite($staff,$actor);
        $token='';
        Notification::assertSentTo(
            $staff,CustomerPortalInvitationNotification::class,
            static function(CustomerPortalInvitationNotification $notice) use (&$token): bool {
                $token=$notice->token;
                return strlen($token)===80;
            }
        );
        self::assertSame(80,strlen($token));
        $activated=app(CustomerActivationService::class)->activate(
            $token,'B814-Activated-Password123!'
        );
        self::assertSame((int)$staff->id,(int)$activated?->id);
        $this->assertRevoked($session);
        self::assertDatabaseCount('personal_access_tokens',0);
        self::assertTrue(Hash::check(
            'B814-Activated-Password123!',(string)$staff->fresh()->password_hash
        ));
    }

    public function test_expired_password_reset_cannot_change_password_or_revoke_sessions(): void
    {
        $user=$this->person('b814-expired-reset','user');
        $session=$this->trackedSession($user,'b814-expired-browser',false);
        $before=(string)$user->password_hash;
        $token=$this->passwordResetToken($user);
        DB::table('password_reset_tokens')
            ->where('user_id',$user->id)
            ->update(['expires_at'=>now()->subMinute()]);

        self::assertFalse(app(PasswordResetService::class)->resetPassword(
            $token,'B814-Invalid-Password123!'
        ));
        self::assertNull(UserLoginSession::query()->findOrFail($session)->revoked_at);
        self::assertSame($before,(string)$user->fresh()->password_hash);
    }

    private function person(string $username,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$username,
            'email'=>$username.'@example.test',
            'first_name'=>'B814 Synthetic',
            'password_hash'=>Hash::make('B814-Old-Password123!'),
            'status'=>'active',
        ]);
    }

    private function trackedSession(User $user,string $key,bool $remembered): int
    {
        return (int)UserLoginSession::query()->create([
            'user_id'=>$user->id,
            'session_hash'=>hash('sha256',$key),
            'logged_in_at'=>now(),
            'last_seen_at'=>now(),
            'remembered'=>$remembered,
        ])->getKey();
    }

    private function passwordResetToken(User $user): string
    {
        app(PasswordResetService::class)->sendResetLink((string)$user->email);
        $token='';
        Notification::assertSentTo(
            $user,ResetPasswordNotification::class,
            static function(ResetPasswordNotification $notice) use (&$token): bool {
                $token=$notice->token;
                return strlen($token)===80;
            }
        );
        self::assertSame(80,strlen($token));
        return $token;
    }

    private function assertRevoked(int $sessionId): void
    {
        self::assertNotNull(
            UserLoginSession::query()->findOrFail($sessionId)->revoked_at,
            'B814 recovered credential must revoke existing tracked web session'
        );
    }
}
