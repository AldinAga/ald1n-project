<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Role,User,UserLoginSession};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * B813: fail closed at credential acceptance, before creating a web session.
 * A historical/imported row may have active status and non-null retired_at.
 * Legacy storefront guest fixtures are intentionally NOT modified.
 */
final class RetiredActiveLoginB813Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('services.turnstile.enabled', false);
    }

    public function test_retired_active_staff_cannot_log_in_or_create_a_session(): void
    {
        $operator=$this->person('b813-retired-staff','user','active');
        $operator->forceFill(['retired_at'=>now()->subMinute()])->save();

        $this->post('/login', [
            'login'=>$operator->email,
            'password'=>'B813-Test-Password123!',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
        self::assertNull($operator->fresh()->last_login_at);
        self::assertSame(0,UserLoginSession::query()->where('user_id',$operator->id)->count());
        $this->get('/')->assertRedirect('/login');
    }

    public function test_retired_active_customer_cannot_log_in_even_with_valid_password(): void
    {
        $buyer=$this->person('b813-retired-buyer','customer','active');
        $buyer->forceFill(['retired_at'=>now()->subMinute()])->save();

        $this->post('/login', [
            'login'=>$buyer->username,
            'password'=>'B813-Test-Password123!',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
        self::assertNull($buyer->fresh()->last_login_at);
        self::assertSame(0,UserLoginSession::query()->where('user_id',$buyer->id)->count());
    }

    public function test_blocked_user_is_still_denied_without_creating_a_session(): void
    {
        $buyer=$this->person('b813-blocked-buyer','customer','blocked');

        $this->post('/login',[
            'login'=>$buyer->email,
            'password'=>'B813-Test-Password123!',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
        self::assertSame(0,UserLoginSession::query()->where('user_id',$buyer->id)->count());
    }

    public function test_active_nonretired_staff_and_buyer_can_still_log_in(): void
    {
        $operator=$this->person('b813-active-staff','user','active');
        $buyer=$this->person('b813-active-buyer','customer','active');

        $this->post('/login',[
            'login'=>$operator->username,
            'password'=>'B813-Test-Password123!',
        ])->assertRedirect('/');
        $this->assertAuthenticatedAs($operator);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();

        $this->post('/login',[
            'login'=>$buyer->email,
            'password'=>'B813-Test-Password123!',
        ])->assertRedirect('/korpa');
        $this->assertAuthenticatedAs($buyer);
    }

    private function person(string $username,string $role,string $status): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$username,
            'email'=>$username.'@example.test',
            'first_name'=>'B813 Synthetic',
            'password_hash'=>Hash::make('B813-Test-Password123!'),
            'status'=>$status,
        ]);
    }
}
