<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\AdminUserService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * An Administrator holds every permission, including system.manage_users. That must not let one promote himself,
 * create a SuperAdmin or reset a SuperAdmin's password. Author: Ald1n <https://ald1n.com>
 */
final class AdminUserSuperAdminGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    private function user(string $username, string $role): User
    {
        return User::query()->create([
            'role_id' => $this->roleId($role),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $username,
            'email' => $username.'@example.test',
            'first_name' => ucfirst($username),
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function roleId(string $slug): int
    {
        return (int) Role::query()->where('slug', $slug)->valueOrFail('id');
    }

    /** @return array<string,mixed> */
    private function payload(User $user, string $role, array $override = []): array
    {
        return array_merge([
            'role_id' => $this->roleId($role),
            'user_group_id' => null,
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'status' => 'active',
            'password' => '',
        ], $override);
    }

    public function test_an_administrator_cannot_promote_himself_to_super_administrator(): void
    {
        $admin = $this->user('guard-admin', 'admin');

        try {
            app(AdminUserService::class)->update($admin, $this->payload($admin, 'superadmin'), $admin);
            $this->fail('The promotion must be refused.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame('admin', $admin->fresh()->role->slug);
    }

    public function test_an_administrator_cannot_create_a_super_administrator(): void
    {
        $admin = $this->user('guard-admin', 'admin');

        try {
            app(AdminUserService::class)->create([
                'role_id' => $this->roleId('superadmin'), 'username' => 'new-super', 'email' => 'new-super@example.test',
                'status' => 'active', 'password' => 'A-long-password-123',
            ], $admin);
            $this->fail('The creation must be refused.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame(0, User::query()->where('username', 'new-super')->count());
    }

    public function test_an_administrator_cannot_reset_the_password_of_a_super_administrator(): void
    {
        $admin = $this->user('guard-admin', 'admin');
        $super = $this->user('guard-super', 'superadmin');
        $hash = $super->password_hash;

        try {
            app(AdminUserService::class)->update($super, $this->payload($super, 'superadmin', ['password' => 'A-long-password-123']), $admin);
            $this->fail('The password reset must be refused.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame($hash, $super->fresh()->password_hash);
    }

    public function test_an_administrator_cannot_demote_or_block_a_super_administrator_even_when_two_are_active(): void
    {
        $admin = $this->user('guard-admin', 'admin');
        $super = $this->user('guard-super', 'superadmin');
        $this->user('guard-super-two', 'superadmin');

        foreach ([['admin', []], ['superadmin', ['status' => 'blocked']]] as [$role, $override]) {
            try {
                app(AdminUserService::class)->update($super, $this->payload($super, $role, $override), $admin);
                $this->fail('The change must be refused.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
                $this->assertStringContainsString('SuperAdministrator', $e->getMessage());
            }
        }
        $fresh = $super->fresh();
        $this->assertSame('superadmin', $fresh->role->slug);
        $this->assertSame('active', $fresh->status);
    }

    public function test_a_super_administrator_can_still_manage_roles_and_an_administrator_can_manage_other_users(): void
    {
        $service = app(AdminUserService::class);
        $super = $this->user('guard-super', 'superadmin');
        $admin = $this->user('guard-admin', 'admin');
        $agent = $this->user('guard-agent', 'user');

        $service->update($admin, $this->payload($admin, 'superadmin'), $super);
        $this->assertSame('superadmin', $admin->fresh()->role->slug);

        $other = $this->user('guard-admin-two', 'admin');
        $service->update($agent, $this->payload($agent, 'user', ['user_group_id' => 1, 'first_name' => 'Izmenjeno']), $other);
        $this->assertSame('Izmenjeno', $agent->fresh()->first_name);
    }
}
