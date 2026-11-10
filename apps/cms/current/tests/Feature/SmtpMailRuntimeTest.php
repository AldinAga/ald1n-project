<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\MailRuntimeConfigurator;
use App\Services\PublicRegistrationMailSenderService;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\MailManager;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Mockery;
use RuntimeException;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Tests\TestCase;

/**
 * Runtime selection of the database SMTP transport for every mail path,
 * fallback to .env, long-running queue workers and the B810 guard.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SmtpMailRuntimeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('mail.default', 'log');
        config()->set('mail.mailers.smtp.host', 'env-smtp.example.test');
        $this->owner = User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'runtime-owner', 'email' => 'runtime-owner@example.test', 'first_name' => 'Synthetic',
            'password_hash' => Hash::make('OnlySynthetic123!'), 'status' => 'active',
        ]);
    }

    public function test_disabled_or_absent_settings_keep_env_mailer_untouched(): void
    {
        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
        self::assertSame('log', config('mail.default'));
        self::assertSame('env-smtp.example.test', config('mail.mailers.smtp.host'));

        $this->store(['smtp_enabled' => '0']);
        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
        self::assertSame('log', config('mail.default'));
        self::assertSame('log', app('mail.manager')->getDefaultDriver());
    }

    public function test_enabled_complete_settings_drive_mail_facade_mailables_and_notifications(): void
    {
        $this->store();
        /** @var MailManager $manager */
        $manager = app('mail.manager');

        self::assertSame('smtp', config('mail.default'));
        self::assertSame('smtp', $manager->getDefaultDriver());
        $transport = $manager->mailer()->getSymfonyTransport();
        self::assertInstanceOf(EsmtpTransport::class, $transport);
        $stream = $transport->getStream();
        self::assertInstanceOf(SocketStream::class, $stream);
        self::assertSame('192.0.2.30', $stream->getHost());
        self::assertSame(465, $stream->getPort());
        self::assertTrue($stream->isTLS());
        self::assertSame('noreply@ald1n.com', $transport->getUsername());
        self::assertSame('Runtime-Secret-1', $transport->getPassword());
        // Notifications' MailChannel and Mailables resolve the same factory.
        self::assertSame($manager, app(\Illuminate\Contracts\Mail\Factory::class));
        self::assertSame('database', app(MailRuntimeConfigurator::class)->source());
        self::assertSame('log', app(MailRuntimeConfigurator::class)->envDefaultMailer());
    }

    public function test_starttls_and_plain_modes_map_to_transport_options(): void
    {
        $this->store(['smtp_encryption' => 'tls', 'smtp_port' => '587']);
        $transport = app('mail.manager')->mailer('smtp')->getSymfonyTransport();
        self::assertInstanceOf(EsmtpTransport::class, $transport);
        self::assertFalse($transport->getStream()->isTLS());
        self::assertTrue((bool) config('mail.mailers.smtp.require_tls'));
        self::assertTrue((bool) config('mail.mailers.smtp.auto_tls'));

        $this->store(['smtp_encryption' => 'none', 'smtp_port' => '25', 'smtp_username' => '']);
        app(MailRuntimeConfigurator::class)->apply();
        self::assertFalse((bool) config('mail.mailers.smtp.auto_tls'));
        self::assertFalse((bool) config('mail.mailers.smtp.require_tls'));
        self::assertNull(config('mail.mailers.smtp.password'));
    }

    public function test_incomplete_or_undecryptable_settings_fall_back_to_env(): void
    {
        $this->store(['smtp_encryption' => '']);
        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
        self::assertSame('log', config('mail.default'));
        self::assertSame('incomplete', app(MailRuntimeConfigurator::class)->reason());

        // Ciphertext from another APP_KEY (or corrupted) must never break mail.
        $this->store();
        Setting::query()->where('setting_key', 'smtp_password')->update(['setting_value' => 'not-a-valid-ciphertext']);
        app(SettingsService::class)->forgetCache();
        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
        self::assertSame('log', config('mail.default'));
        self::assertSame('env-smtp.example.test', config('mail.mailers.smtp.host'));
        self::assertSame('password_unreadable', app(MailRuntimeConfigurator::class)->reason());
    }

    public function test_settings_store_failure_never_throws_and_keeps_env(): void
    {
        $this->store(['smtp_enabled' => '0']);
        Cache::shouldReceive('remember')->andThrow(new RuntimeException('cache down'));
        Cache::shouldReceive('forget')->andReturnTrue();

        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
        self::assertSame('log', config('mail.default'));
        self::assertSame('unavailable', app(MailRuntimeConfigurator::class)->reason());
        self::assertSame('log', app(MailRuntimeConfigurator::class)->effectiveDefaultMailer());
    }

    public function test_long_running_queue_worker_picks_up_changes_before_each_job(): void
    {
        $this->store();
        /** @var MailManager $manager */
        $manager = app('mail.manager');
        $first = $manager->mailer('smtp');
        self::assertSame('192.0.2.30', $first->getSymfonyTransport()->getStream()->getHost());

        // Another process (the admin page) saves new settings.
        app(SettingsService::class)->putMany(['smtp_host' => '192.0.2.31'], $this->owner->id);
        event(new JobProcessing('database', Mockery::mock(Job::class)));
        $second = $manager->mailer('smtp');
        self::assertNotSame($first, $second);
        self::assertSame('192.0.2.31', $second->getSymfonyTransport()->getStream()->getHost());

        // Unchanged settings keep the cached mailer.
        event(new JobProcessing('database', Mockery::mock(Job::class)));
        self::assertSame($second, $manager->mailer('smtp'));

        // Switching OFF restores the exact .env configuration in the worker.
        app(SettingsService::class)->putMany(['smtp_enabled' => '0'], $this->owner->id);
        event(new JobProcessing('database', Mockery::mock(Job::class)));
        self::assertSame('log', config('mail.default'));
        self::assertSame('env-smtp.example.test', config('mail.mailers.smtp.host'));
        self::assertSame('env-smtp.example.test', $manager->mailer('smtp')->getSymfonyTransport()->getStream()->getHost());
    }

    public function test_b810_registration_transport_guard_accepts_database_smtp_in_production(): void
    {
        $this->app['env'] = 'production';
        $sender = app(PublicRegistrationMailSenderService::class);
        self::assertFalse($sender->status()['transport_ready'], 'log transport is not deliverable');

        $this->store();
        self::assertTrue($sender->status()['transport_ready']);
        self::assertSame('smtp', config('mail.default'));

        app(SettingsService::class)->putMany(['smtp_enabled' => '0'], $this->owner->id);
        self::assertFalse($sender->status()['transport_ready']);
        self::assertSame('log', config('mail.default'));
        // The sender identity itself is untouched by the SMTP page.
        self::assertFalse($sender->status()['approved']);
        self::assertNull($sender->sender());
    }

    public function test_password_never_reaches_the_cached_public_settings_array(): void
    {
        $this->store();
        $all = app(SettingsService::class)->all();
        self::assertArrayNotHasKey('smtp_password', $all);
        self::assertStringNotContainsString('Runtime-Secret-1', json_encode($all) ?: '');
    }

    /** @param array<string,string> $change */
    private function store(array $change = []): void
    {
        $values = array_replace([
            'smtp_enabled' => '1',
            'smtp_host' => '192.0.2.30',
            'smtp_port' => '465',
            'smtp_encryption' => 'ssl',
            'smtp_username' => 'noreply@ald1n.com',
            'smtp_timeout' => '',
        ], $change);
        $settings = app(SettingsService::class);
        $settings->putMany($values + ['smtp_password' => Crypt::encryptString('Runtime-Secret-1')], $this->owner->id);
        app(MailRuntimeConfigurator::class)->apply();
    }
}
