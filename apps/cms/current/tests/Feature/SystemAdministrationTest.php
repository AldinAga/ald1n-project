<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\ExchangeRateHistory;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class SystemAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_admin_can_update_appearance_settings(): void
    {
        $this->actingAs($this->admin())->put('/admin/settings/appearance', [
            'site_name' => 'Ald1n Test',
            'site_logo_alt' => 'Test logo',
            'site_header_logo_height' => 44,
            'site_footer_layout' => 'centered',
            'site_footer_show_logo' => '1',
            'site_footer_copyright_text' => '© {year} {site_name}',
            'site_footer_secondary_text' => 'v{version}',
        ])->assertRedirect();

        self::assertSame('Ald1n Test', Setting::query()->where('setting_key', 'site_name')->value('setting_value'));
        self::assertSame('centered', Setting::query()->where('setting_key', 'site_footer_layout')->value('setting_value'));
    }

    public function test_admin_can_set_manual_exchange_rate(): void
    {
        $this->actingAs($this->admin())->post('/admin/settings/exchange-rate/manual', ['rate' => '117.2500'])->assertRedirect();
        self::assertSame('117.250000', Setting::query()->where('setting_key', 'eur_rsd_rate')->value('setting_value'));
        self::assertTrue(ExchangeRateHistory::query()->where('mode', 'manual')->where('status', 'success')->exists());
    }

    public function test_admin_can_create_valid_bank_account(): void
    {
        $this->actingAs($this->admin())->post('/admin/settings/bank-accounts', [
            'label' => 'Glavni račun',
            'recipient_name' => 'Ald1n',
            'recipient_address' => 'Novi Pazar',
            'account_number' => '160-1234567890123-12',
            'payment_code' => '221',
            'is_active' => '1',
        ])->assertRedirect();

        $account = BankAccount::query()->firstOrFail();
        self::assertSame('160123456789012312', $account->account_number);
        self::assertSame('160-1234567890123-12', $account->account_number_display);
        self::assertTrue($account->is_active);
    }


    public function test_admin_can_store_encrypted_turnstile_keys(): void
    {
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        config()->set('services.turnstile.enabled', false);
        config()->set('services.turnstile.site_key', 'env-site-key');
        config()->set('services.turnstile.secret_key', '');
        config()->set('services.turnstile.expected_hostname', 'env.example.com');

        $this->actingAs($this->admin())->put('/admin/settings/turnstile', [
            'turnstile_enabled' => '1',
            'turnstile_site_key' => 'database-site-key',
            'turnstile_secret_key' => 'database-secret-key',
            'turnstile_expected_hostname' => 'cms.example.com',
        ])->assertRedirect();

        self::assertSame('1', Setting::query()->where('setting_key', 'turnstile_enabled')->value('setting_value'));
        self::assertSame('database-site-key', Setting::query()->where('setting_key', 'turnstile_site_key')->value('setting_value'));
        self::assertNotSame('database-secret-key', Setting::query()->where('setting_key', 'turnstile_secret_key')->value('setting_value'));

        $settings = app(\App\Services\SettingsService::class);
        self::assertSame('database-secret-key', $settings->getSecret('turnstile_secret_key'));
        self::assertArrayNotHasKey('turnstile_secret_key', $settings->all());
    }

    public function test_login_uses_turnstile_site_key_from_settings(): void
    {
        Setting::query()->insert([
            ['setting_key' => 'turnstile_enabled', 'setting_value' => '1'],
            ['setting_key' => 'turnstile_site_key', 'setting_value' => 'database-site-key'],
            ['setting_key' => 'turnstile_expected_hostname', 'setting_value' => 'cms.example.com'],
        ]);
        app(\App\Services\SettingsService::class)->forgetCache();

        config()->set('services.turnstile.enabled', false);
        config()->set('services.turnstile.site_key', 'env-site-key');

        $this->get('/login')
            ->assertOk()
            ->assertSee('database-site-key')
            ->assertDontSee('env-site-key');
    }

    private function admin(): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'system-admin-test',
            'email' => 'system-admin@test.local',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }
}
