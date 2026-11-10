<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\MailRuntimeConfigurator;
use App\Services\PublicRegistrationMailSenderService;
use App\Services\SettingsService;
use App\Services\SmtpMailSettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\MailManager;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
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
        // The real transport refuses to continue without STARTTLS.
        self::assertTrue($transport->isTlsRequired());
        self::assertTrue($transport->isAutoTls());

        $this->store(['smtp_encryption' => 'ssl', 'smtp_port' => '465']);
        $transport = app('mail.manager')->mailer('smtp')->getSymfonyTransport();
        self::assertInstanceOf(EsmtpTransport::class, $transport);
        self::assertTrue($transport->getStream()->isTLS());
        self::assertFalse($transport->isTlsRequired());

        $this->store(['smtp_encryption' => 'none', 'smtp_port' => '25', 'smtp_username' => '']);
        $transport = app('mail.manager')->mailer('smtp')->getSymfonyTransport();
        self::assertInstanceOf(EsmtpTransport::class, $transport);
        self::assertFalse($transport->getStream()->isTLS());
        self::assertFalse($transport->isAutoTls());
        self::assertFalse($transport->isTlsRequired());
        self::assertSame('', $transport->getPassword());
    }

    public function test_plaintext_auth_runs_only_against_loopback(): void
    {
        $this->store(['smtp_encryption' => 'none', 'smtp_port' => '25']);
        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
        self::assertSame('insecure_auth', app(MailRuntimeConfigurator::class)->reason());
        self::assertSame('log', config('mail.default'));

        $this->store(['smtp_encryption' => 'none', 'smtp_port' => '25', 'smtp_host' => '127.0.0.1']);
        self::assertSame('database', app(MailRuntimeConfigurator::class)->apply());
        self::assertSame('127.0.0.1', app('mail.manager')->mailer('smtp')->getSymfonyTransport()->getStream()->getHost());
    }

    public function test_runtime_requires_a_passing_test_for_the_saved_connection(): void
    {
        $this->store([], tested: false);
        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
        self::assertSame('not_tested', app(MailRuntimeConfigurator::class)->reason());

        $this->store();
        self::assertSame('database', app(MailRuntimeConfigurator::class)->apply());

        // A connection edit that bypasses the page (direct write) voids the test result.
        app(SettingsService::class)->putMany(['smtp_port' => '2525'], $this->owner->id);
        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
        self::assertSame('not_tested', app(MailRuntimeConfigurator::class)->reason());
        self::assertSame('log', config('mail.default'));

        $this->store();
        app(SettingsService::class)->putMany(['smtp_password' => Crypt::encryptString('Runtime-Secret-1')], $this->owner->id);
        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply(), 'a re-stored password is a new connection');

        $this->store();
        app(SettingsService::class)->putMany(['smtp_last_test_result' => 'timeout'], $this->owner->id);
        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
    }

    public function test_runtime_timeout_is_capped_at_ten_seconds(): void
    {
        $this->store();
        self::assertSame(10, config('mail.mailers.smtp.timeout'));

        $this->store(['smtp_timeout' => '45']);
        self::assertSame(10, config('mail.mailers.smtp.timeout'));

        $this->store(['smtp_timeout' => '4']);
        self::assertSame(4, config('mail.mailers.smtp.timeout'));
    }

    public function test_switched_off_settings_cost_a_single_query_per_apply(): void
    {
        $this->store(['smtp_enabled' => '0']);
        $runtime = app(MailRuntimeConfigurator::class);
        $runtime->apply();

        DB::flushQueryLog();
        DB::enableQueryLog();
        self::assertSame('env', $runtime->apply());
        $off = count(DB::getQueryLog());

        app(SettingsService::class)->putMany(['smtp_enabled' => '1'], $this->owner->id);
        $runtime->apply();
        DB::flushQueryLog();
        self::assertSame('database', $runtime->apply());
        $on = count(DB::getQueryLog());
        DB::disableQueryLog();

        // The ON path proves the log records the settings table checks.
        self::assertGreaterThan(1, $on);
        self::assertLessThanOrEqual(1, $off);
    }

    public function test_diagnostic_commands_report_the_database_smtp_without_secrets(): void
    {
        config()->set('mail.from.address', 'noreply@ald1n.com');
        $this->store();

        $this->artisan('app:mail-doctor')
            ->expectsOutputToContain('SMTP iz baze aktivan')
            ->expectsOutputToContain('Efektivni mailer: smtp')
            ->expectsOutputToContain('192.0.2.30:465')
            ->doesntExpectOutputToContain('Runtime-Secret-1');

        app(SettingsService::class)->putMany(['smtp_enabled' => '0'], $this->owner->id);
        $this->artisan('app:mail-doctor')
            ->expectsOutputToContain('MAIL_MAILER: log')
            ->doesntExpectOutputToContain('SMTP iz baze aktivan');
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

        // Another process (the admin page) saves and successfully tests new settings.
        app(SettingsService::class)->putMany(['smtp_host' => '192.0.2.31'], $this->owner->id);
        $this->markTested();
        event(new JobProcessing('database', $this->job()));
        $second = $manager->mailer('smtp');
        self::assertNotSame($first, $second);
        self::assertSame('192.0.2.31', $second->getSymfonyTransport()->getStream()->getHost());

        // Unchanged settings keep the cached mailer.
        event(new JobProcessing('database', $this->job()));
        self::assertSame($second, $manager->mailer('smtp'));

        // Switching OFF restores the exact .env configuration in the worker.
        app(SettingsService::class)->putMany(['smtp_enabled' => '0'], $this->owner->id);
        event(new JobProcessing('database', $this->job()));
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

    /** A queued job as the worker sees it; framework listeners read its payload. */
    private function job(): Job
    {
        $job = Mockery::mock(Job::class);
        $job->shouldReceive('payload')->andReturn([]);
        $job->shouldIgnoreMissing();

        return $job;
    }

    /**
     * Stores settings as the page would after a successful test e-mail.
     *
     * @param array<string,string> $change
     */
    private function store(array $change = [], bool $tested = true): void
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
        $settings->putMany($values + [
            'smtp_password' => Crypt::encryptString('Runtime-Secret-1'),
            'smtp_last_test_result' => '',
            'smtp_last_test_fingerprint' => '',
        ], $this->owner->id);
        if ($tested) {
            $this->markTested();
        }
        app(MailRuntimeConfigurator::class)->apply();
    }

    private function markTested(): void
    {
        app(SettingsService::class)->putMany([
            'smtp_last_test_result' => 'ok',
            'smtp_last_test_fingerprint' => app(SmtpMailSettingsService::class)->connectionFingerprint(),
        ], $this->owner->id);
    }
}
