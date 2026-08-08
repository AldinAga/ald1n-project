<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class SecurityHealthBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_security_headers_and_request_id_are_present(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'same-origin')
            ->assertHeader('X-Request-ID');
    }

    public function test_failed_login_is_written_to_security_events_without_plain_login(): void
    {
        $this->post('/login', ['login' => 'secret-user@example.test', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('login');

        $event = SecurityEvent::query()->where('event_type', 'login.failed')->firstOrFail();
        self::assertArrayHasKey('login_hash', $event->context_json);
        self::assertStringNotContainsString('secret-user@example.test', json_encode($event->context_json));
    }

    public function test_superadmin_can_open_system_health_page(): void
    {
        $this->actingAs($this->superadmin())
            ->get('/admin/settings/system-health')
            ->assertOk()
            ->assertSee('System Health &amp; Backup', false)
            ->assertSee('Runtime direktorijumi');
    }

    public function test_permission_denial_creates_security_event(): void
    {
        $user = User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1,
            'username' => 'limited-user',
            'email' => 'limited@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        $this->actingAs($user)->get('/admin/settings/system-health')->assertForbidden();
        self::assertTrue(SecurityEvent::query()->where('event_type', 'permission.denied')->exists());
    }

    private function superadmin(): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'health-superadmin',
            'email' => 'health-superadmin@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }
}
