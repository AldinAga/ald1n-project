<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Odluka vlasnika (audit 23.11.2026): backups.manage i automation.manage samo SuperAdministrator, kao SMTP.
 * Administrator ih ne dobija ni preko role-wide prava (Gate::before), ni kroz hasPermission / listu dozvola.
 */
final class SuperadminOnlySystemPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function person(string $role, string $name): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $name, 'email' => $name.'@example.test', 'first_name' => ucfirst($name),
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
    }

    /** @return array<string,array{0:string,1:string}> */
    public static function webRoutes(): array
    {
        return [
            'automatizacija pregled' => ['get', 'admin.settings.automation.index'],
            'automatizacija izmjena' => ['put', 'admin.settings.automation.update'],
            'automatizacija pokretanje' => ['post', 'admin.settings.automation.run'],
            'backup' => ['post', 'admin.settings.system-health.backup'],
            'retention' => ['post', 'admin.settings.system-health.prune'],
            'api automatizacija pregled' => ['getJson', 'api.v1.admin.system-settings.automation.index'],
            'api automatizacija izmjena' => ['putJson', 'api.v1.admin.system-settings.automation.update'],
            'api automatizacija pokretanje' => ['postJson', 'api.v1.admin.system-settings.automation.run'],
            'api backup' => ['postJson', 'api.v1.admin.system-health.backup'],
            'api retention' => ['postJson', 'api.v1.admin.system-health.prune'],
        ];
    }

    #[DataProvider('webRoutes')]
    public function test_admin_is_forbidden_on_every_route(string $verb, string $route): void
    {
        $admin = $this->person('admin', 'adm-so');
        str_ends_with($verb, 'Json') ? Sanctum::actingAs($admin, ['*']) : $this->actingAs($admin);

        $this->{$verb}(route($route))->assertForbidden();
    }

    public function test_superadmin_positive_control_on_same_routes(): void
    {
        $owner = $this->person('superadmin', 'owner-so');

        $this->actingAs($owner)->get(route('admin.settings.automation.index'))->assertOk();
        $this->actingAs($owner)->post(route('admin.settings.system-health.prune'))->assertRedirect();
        Sanctum::actingAs($owner, ['*']);
        $this->getJson(route('api.v1.admin.system-settings.automation.index'))->assertOk();
        $this->postJson(route('api.v1.admin.system-health.prune'))->assertOk();
    }

    public function test_group_member_holding_the_permission_is_still_forbidden(): void
    {
        $sub = $this->person('user', 'sub-so');
        foreach (['backups.manage', 'automation.manage', 'system.health', 'system.manage_settings'] as $slug) {
            DB::table('user_group_permissions')->insert(['group_id' => 1, 'permission_id' => DB::table('permissions')->where('slug', $slug)->value('id')]);
        }

        self::assertFalse($sub->hasPermission('backups.manage'));
        self::assertNotContains('automation.manage', $sub->permissionSlugs());
        $this->actingAs($sub)->post(route('admin.settings.system-health.prune'))->assertForbidden();
        $this->actingAs($sub)->get(route('admin.settings.automation.index'))->assertForbidden();
    }

    public function test_permission_lists_only_expose_the_slugs_to_superadmin(): void
    {
        $admin = $this->person('admin', 'adm-list');
        $owner = $this->person('superadmin', 'owner-list');

        foreach (['backups.manage', 'automation.manage'] as $slug) {
            self::assertFalse($admin->hasPermission($slug));
            self::assertFalse($admin->can($slug));
            self::assertNotContains($slug, $admin->permissionSlugs());
            self::assertTrue($owner->hasPermission($slug));
            self::assertTrue($owner->can($slug));
        }
        self::assertTrue($admin->can('system.health'), 'ostale dozvole Administratora ostaju netaknute');
    }

    public function test_links_and_buttons_are_hidden_from_admin_and_shown_to_superadmin(): void
    {
        $automation = route('admin.settings.automation.index');
        $backup = route('admin.settings.system-health.backup');

        $adminHub = $this->actingAs($this->person('admin', 'adm-nav'))->get(route('admin.settings.index'))->assertOk()->getContent();
        self::assertStringNotContainsString($automation, $adminHub);
        $adminHealth = $this->get(route('admin.settings.system-health.index'))->assertOk()->getContent();
        self::assertStringNotContainsString($backup, $adminHealth);

        $ownerHub = $this->actingAs($this->person('superadmin', 'owner-nav'))->get(route('admin.settings.index'))->assertOk()->getContent();
        self::assertStringContainsString($automation, $ownerHub);
        self::assertStringContainsString($backup, $this->get(route('admin.settings.system-health.index'))->assertOk()->getContent());
    }
}
