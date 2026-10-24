<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\PublicMerchantPolicyService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class PublicRetailAccessB802Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    public function test_existing_user_role_is_subagent_and_customer_is_separate_without_reassignment(): void
    {
        self::assertSame('SubAgent',Role::query()->where('slug','user')->value('name'));
        self::assertSame('Kupac',Role::query()->where('slug','customer')->value('name'));
        self::assertSame(1,(int)Role::query()->where('slug','user')->value('id'));
    }

    public function test_customer_cannot_inherit_internal_group_permissions_or_read_internal_catalog(): void
    {
        $customer=$this->actor('b802-buyer','customer',1);
        self::assertFalse($customer->hasPermission('catalog.view'));
        self::assertFalse($customer->hasPermission('commissions.view_own'));
        self::assertFalse($customer->hasPermission('system.manage_settings'));
        $this->actingAs($customer)->get('/catalog')->assertForbidden();
        $this->get('/commissions')->assertForbidden();
        $this->get('/admin/settings')->assertForbidden();
        $this->get('/dashboard')->assertRedirect('/katalog');
    }

    public function test_superadmin_has_a_real_storefront_switch_and_regular_admin_cannot_toggle_it(): void
    {
        $owner=$this->actor('b802-owner','superadmin');
        $this->actingAs($owner)->get('/admin/settings/public-storefront')->assertOk()
            ->assertSee('ON / OFF');
        $this->postJson('/admin/settings/public-storefront/toggle',['enabled'=>'1'])
            ->assertUnprocessable();
        self::assertNotSame('1',app(SettingsService::class)->get('public_storefront_open','0'));
        $admin=$this->actor('b802-admin','admin');
        $this->actingAs($admin)->postJson('/admin/settings/public-storefront/toggle',['enabled'=>'0'])
            ->assertForbidden();
    }

    public function test_switch_off_blocks_new_storefront_traffic_even_with_release_flags_set(): void
    {
        config()->set('public_storefront.enabled',true);
        config()->set('public_storefront.checkout_enabled',true);
        app(SettingsService::class)->putMany(['public_storefront_open'=>'0'],null);
        $this->get('/katalog')->assertNotFound();
        $this->get('/korpa')->assertNotFound();
    }

    private function actor(string $name,string $slug,?int $group=null): User
    {
        $role=Role::query()->firstOrCreate(['slug'=>$slug],['name'=>$slug]);
        return User::query()->create([
            'role_id'=>$role->id,'user_group_id'=>$group,'username'=>$name,
            'email'=>$name.'@example.test','first_name'=>'Synthetic',
            'password_hash'=>Hash::make('SyntheticOnly123!'),'status'=>'active',
        ]);
    }
}
