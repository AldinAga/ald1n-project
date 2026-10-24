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
        $this->get(route('dashboard'))->assertRedirect('/katalog');
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

    public function test_subagent_keeps_existing_group_rights_and_role_migration_preserves_ids(): void
    {
        $subagent=$this->actor('b802-subagent','user',1);
        $before=$subagent->only(['id','role_id','user_group_id','username','email']);
        self::assertTrue($subagent->hasPermission('catalog.view'));
        self::assertTrue($subagent->hasPermission('commissions.view_own'));
        $migration=require database_path('migrations/2026_10_24_030000_add_retail_customer_role.php');
        $migration->up();
        $migration->up();
        self::assertSame($before,$subagent->fresh()->only(array_keys($before)));
        self::assertSame(1,Role::query()->where('slug','customer')->count());
        self::assertSame('SubAgent',$subagent->fresh()->roleName());
    }

    public function test_customer_wildcard_token_cannot_open_employee_api_or_group_features(): void
    {
        $customer=$this->actor('b802-api-buyer','customer',1);
        \Laravel\Sanctum\Sanctum::actingAs($customer,['*']);
        foreach (['/api/v1/bootstrap','/api/v1/global-search?q=test',
            '/api/v1/admin/foundation','/api/v1/catalog'] as $path) {
            $this->getJson($path)->assertForbidden();
        }
        self::assertSame([],$customer->permissionSlugs());
    }

    public function test_on_off_is_an_actual_audited_control_and_off_does_not_require_valid_merchant_terms(): void
    {
        $owner=$this->actor('b802-switch-owner','superadmin');
        config()->set('public_storefront.enabled',true);
        config()->set('public_storefront.checkout_enabled',true);
        foreach ([
            'terms_url'=>'https://cms.ald1n.com/uslovi',
            'privacy_url'=>'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd'=>'0','shipping_description'=>'Synthetic approved delivery.',
            'support_email'=>'support@example.test','payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','customer_pickup'],
        ] as $key=>$value) config()->set('public_storefront.'.$key,$value);
        $settings=app(SettingsService::class);
        $settings->putMany([
            'documents_company_name'=>'Synthetic Merchant',
            'documents_company_address'=>'Test 12','documents_company_city'=>'Beograd',
            'documents_company_tax_id'=>'100200300',
        ],$owner->id);
        $review=app(PublicMerchantPolicyService::class)->review();
        self::assertTrue($review['complete']);
        $settings->putMany([
            'public_checkout_approval_digest'=>$review['fingerprint'],
            'public_checkout_approved_by'=>(string)$owner->id,
            'public_checkout_approved_at'=>now()->toISOString(),
        ],$owner->id);
        $this->get('/katalog')->assertNotFound();
        $this->actingAs($owner)->post('/admin/settings/public-storefront/toggle',['enabled'=>'1'])
            ->assertRedirect()->assertSessionHasNoErrors();
        self::assertTrue(app(\App\Services\PublicStorefrontStateService::class)->isOpen());
        $this->get('/katalog')->assertOk();
        $settings->all(); // Populate the cache, then prove OFF ignores it.
        \App\Models\Setting::query()->where('setting_key','public_storefront_open')
            ->update(['setting_value'=>'0']);
        $this->get('/katalog')->assertNotFound();
        $settings->putMany(['public_checkout_approval_digest'=>''],null);
        $this->post('/admin/settings/public-storefront/toggle',['enabled'=>'0'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/admin/settings/public-storefront/toggle',['enabled'=>'0'])
            ->assertRedirect()->assertSessionHasNoErrors();
        self::assertFalse(app(\App\Services\PublicStorefrontStateService::class)->isOpen());
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
