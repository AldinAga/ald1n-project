<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\MailRuntimeConfigurator;
use App\Services\PublicRegistrationMailSenderService;
use App\Services\SettingsService;
use App\Services\SmtpMailSettingsService;
use App\Services\SmtpTestTransportFactory;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

/**
 * SuperAdmin "E-mail (SMTP)" page: access, validation, write-only password,
 * re-confirmation, credential redirect protection, test-gated switch, audit
 * hygiene and the sanitized test e-mail.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SmtpSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'Synthetic-SMTP-P4ss!word';

    private const OWNER_PASSWORD = 'OnlySynthetic123!';

    private const SERVER_NOTICE = 'Zbog promene servera lozinka mora ponovo da se unese.';

    private User $owner;

    /** @var list<string> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('mail.default', 'array');
        config()->set('mail.from.address', 'noreply@ald1n.com');
        config()->set('mail.from.name', 'Ald1n CMS');
        $this->owner = $this->person('smtp-owner', 'superadmin');
        Event::listen(MessageLogged::class, function (MessageLogged $event): void {
            $this->logged[] = $event->message.' '.json_encode($event->context);
        });
    }

    public function test_only_superadmin_can_open_or_change_smtp_settings(): void
    {
        $this->get('/admin/settings/smtp')->assertRedirect('/login');
        $this->put('/admin/settings/smtp', $this->payload())->assertRedirect('/login');
        $this->post('/admin/settings/smtp/test')->assertRedirect('/login');

        foreach (['admin', 'customer'] as $role) {
            $user = $this->person('smtp-'.$role, $role);
            $this->actingAs($user)->get('/admin/settings/smtp')->assertForbidden();
            $this->actingAs($user)->put('/admin/settings/smtp', $this->payload())->assertForbidden();
            $this->actingAs($user)->post('/admin/settings/smtp/test', $this->confirm())->assertForbidden();
            $this->actingAs($user)->delete('/admin/settings/smtp/password')->assertForbidden();
            $this->actingAs($user)->post('/admin/settings/smtp/disable')->assertForbidden();
        }
        self::assertFalse(app(SettingsService::class)->hasStoredValue('smtp_host'));

        $admin = User::query()->where('username', 'smtp-admin')->sole();
        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertDontSee('E-mail (SMTP)');

        $this->actingAs($this->owner)->get('/admin/settings/smtp')->assertOk()
            ->assertSee('E-mail (SMTP)')
            ->assertSee('Koristi SMTP podešavanja iz baze')
            ->assertSee('Pošalji probni e-mail')
            ->assertSee('Vaša trenutna lozinka (SuperAdmin)')
            ->assertSee('ne pokušava ponovo')
            ->assertSee('najviše 5 puta')
            ->assertSee('MAIL_MAILER=array');
        $this->actingAs($this->owner)->get('/admin/settings')->assertOk()
            ->assertSee(route('admin.settings.smtp.index'), false)
            ->assertSee('data-nav-smtp-settings', false);
    }

    public function test_password_is_encrypted_at_rest_and_never_rendered_back(): void
    {
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_password' => self::SECRET]))
            ->assertRedirect('/admin/settings/smtp')->assertSessionHasNoErrors();

        $stored = (string) Setting::query()->where('setting_key', 'smtp_password')->value('setting_value');
        self::assertNotSame('', $stored);
        self::assertStringNotContainsString(self::SECRET, $stored);
        self::assertSame(self::SECRET, Crypt::decryptString($stored));
        self::assertArrayNotHasKey('smtp_password', app(SettingsService::class)->all());

        $page = $this->actingAs($this->owner)->get('/admin/settings/smtp')->assertOk()
            ->assertSee('Lozinka je sačuvana')
            ->assertSee('Obriši lozinku')
            ->assertDontSee(self::SECRET)
            ->assertDontSee($stored);
        self::assertStringContainsString('name="smtp_password" id="smtp_password" maxlength="255" autocomplete="new-password" value=""', (string) $page->getContent());

        // Same server, blank password: the stored one stays (timeout is not a server field).
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_password' => '', 'smtp_timeout' => '8']))
            ->assertSessionHasNoErrors();
        self::assertSame(self::SECRET, app(SettingsService::class)->getSecret('smtp_password'));
        self::assertSame('8', app(SettingsService::class)->get('smtp_timeout'));

        $this->assertNoSecretAnywhere();
    }

    public function test_validation_errors_never_echo_or_flash_the_password(): void
    {
        $response = $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload([
            'smtp_host' => 'http://bad host',
            'smtp_port' => '70000',
            'smtp_password' => self::SECRET,
        ]))->assertRedirect('/admin/settings/smtp')
            ->assertSessionHasErrors(['smtp_host', 'smtp_port']);

        $old = (array) session('_old_input', []);
        self::assertArrayNotHasKey('smtp_password', $old);
        self::assertArrayNotHasKey('current_password', $old);
        self::assertSame('http://bad host', $old['smtp_host'] ?? null);
        $errors = json_encode(session('errors')?->getBag('default')->toArray(), JSON_UNESCAPED_UNICODE);
        self::assertStringNotContainsString(self::SECRET, (string) $errors);
        self::assertStringNotContainsString(self::SECRET, (string) $response->getContent());
        self::assertFalse(app(SettingsService::class)->hasSecret('smtp_password'));

        $this->actingAs($this->owner)->get('/admin/settings/smtp')->assertOk()->assertDontSee(self::SECRET);
        $this->assertNoSecretAnywhere();
    }

    public function test_host_port_and_encryption_are_validated_with_ssrf_guard(): void
    {
        $this->actingAs($this->owner);
        foreach ([
            ['smtp_host' => '169.254.169.254'],
            ['smtp_host' => '169.254.10.20'],
            ['smtp_host' => 'fe80::1'],
            ['smtp_host' => '::ffff:169.254.169.254'],
            ['smtp_host' => 'metadata.google.internal'],
            ['smtp_host' => 'mail.example.com:587'],
            ['smtp_host' => 'smtp://mail.example.com'],
            // Numeric forms some resolvers read as 169.254.169.254.
            ['smtp_host' => '2852039166'],
            ['smtp_host' => '0251.0376.0251.0376'],
            ['smtp_host' => '0xa9.0xfe.0xa9.0xfe'],
            ['smtp_host' => '169.254.43518'],
            // IPv6 metadata and embedded link-local forms.
            ['smtp_host' => 'fd00:ec2::254'],
            ['smtp_host' => '64:ff9b::a9fe:a9fe'],
            ['smtp_host' => '2002:a9fe:a9fe::1'],
            ['smtp_host' => ''],
        ] as $change) {
            $this->put('/admin/settings/smtp', $this->payload($change + ['smtp_enabled' => '1']))
                ->assertSessionHasErrors('smtp_host');
        }
        foreach (['0', '65536', 'abc'] as $port) {
            $this->put('/admin/settings/smtp', $this->payload(['smtp_port' => $port]))->assertSessionHasErrors('smtp_port');
        }
        $this->put('/admin/settings/smtp', $this->payload(['smtp_port' => '', 'smtp_enabled' => '1']))->assertSessionHasErrors('smtp_port');
        foreach (['starttls', 'TLS'] as $encryption) {
            $this->put('/admin/settings/smtp', $this->payload(['smtp_encryption' => $encryption]))->assertSessionHasErrors('smtp_encryption');
        }
        $this->put('/admin/settings/smtp', $this->payload(['smtp_encryption' => '', 'smtp_enabled' => '1']))->assertSessionHasErrors('smtp_encryption');
        foreach (['11', '60', '0'] as $timeout) {
            $this->put('/admin/settings/smtp', $this->payload(['smtp_timeout' => $timeout]))->assertSessionHasErrors('smtp_timeout');
        }
        $this->put('/admin/settings/smtp', $this->payload(['smtp_username' => "user\r\nX"]))->assertSessionHasErrors('smtp_username');
        $this->put('/admin/settings/smtp', $this->payload(['smtp_password' => '    ']))->assertSessionHasErrors('smtp_password');
        self::assertFalse(app(SettingsService::class)->hasStoredValue('smtp_host'));

        // Loopback and private ranges stay allowed (cPanel exim on the same host).
        foreach (['mail.ald1n.com', '127.0.0.1', '::1', 'localhost', '10.0.0.5', '192.168.1.10', '64:ff9b::c000:219'] as $host) {
            $this->put('/admin/settings/smtp', $this->payload(['smtp_host' => $host, 'smtp_password' => self::SECRET]))
                ->assertSessionHasNoErrors();
            self::assertSame($host, app(SettingsService::class)->get('smtp_host'));
        }

        // Disabled drafts may stay incomplete, but a given host is still checked.
        $this->put('/admin/settings/smtp', ['smtp_host' => '', 'smtp_port' => '', 'smtp_encryption' => ''] + $this->confirm())
            ->assertSessionHasNoErrors();
        $this->put('/admin/settings/smtp', ['smtp_host' => '169.254.169.254'] + $this->confirm())->assertSessionHasErrors('smtp_host');
    }

    public function test_plaintext_auth_is_refused_unless_the_host_is_loopback(): void
    {
        $this->actingAs($this->owner);
        foreach (['192.0.2.25', 'mail.ald1n.com', '10.0.0.5'] as $host) {
            $this->put('/admin/settings/smtp', $this->payload(['smtp_host' => $host, 'smtp_encryption' => 'none', 'smtp_port' => '25']))
                ->assertSessionHasErrors('smtp_encryption');
        }
        self::assertFalse(app(SettingsService::class)->hasStoredValue('smtp_host'));

        foreach (['127.0.0.1', '127.10.0.2', '::1', 'localhost'] as $host) {
            $this->put('/admin/settings/smtp', $this->payload(['smtp_host' => $host, 'smtp_encryption' => 'none', 'smtp_port' => '25']))
                ->assertSessionHasNoErrors();
        }
        // Without a username no credentials travel, so 'none' is allowed anywhere.
        $this->put('/admin/settings/smtp', $this->payload([
            'smtp_host' => '192.0.2.25', 'smtp_encryption' => 'none', 'smtp_port' => '25', 'smtp_username' => '', 'smtp_password' => '',
        ]))->assertSessionHasNoErrors();

        // A direct database write that bypasses the form is still refused at runtime and test time.
        $fake = $this->fakeTransport(null);
        app(SettingsService::class)->putMany(['smtp_username' => 'noreply@ald1n.com'], $this->owner->id);
        app(SettingsService::class)->putSecret('smtp_password', self::SECRET, $this->owner->id);
        self::assertSame('insecure_auth', app(SmtpMailSettingsService::class)->connectionConfig()['reason']);
        $this->post('/admin/settings/smtp/test', $this->confirm())->assertSessionHasErrors('smtp_test');
        self::assertSame('insecure_auth', app(SettingsService::class)->get('smtp_last_test_result'));
        self::assertSame([], $fake->sent);
    }

    public function test_server_change_without_new_password_clears_it_and_switches_off(): void
    {
        $fake = $this->fakeTransport(null);
        $this->enableTested();
        self::assertSame('1', app(SettingsService::class)->get('smtp_enabled'));
        self::assertSame('database', app(MailRuntimeConfigurator::class)->apply());

        foreach ([
            ['smtp_host' => 'attacker.example.net'],
            ['smtp_port' => '587', 'smtp_encryption' => 'tls'],
            ['smtp_username' => 'other@ald1n.com'],
        ] as $change) {
            // Restore a tested, enabled state, then change one server field with a blank password.
            $this->enableTested();
            $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload($change + ['smtp_enabled' => '1', 'smtp_password' => '']))
                ->assertRedirect('/admin/settings/smtp')
                ->assertSessionHasNoErrors()
                ->assertSessionHas('smtp_notices', fn (array $notices): bool => in_array(self::SERVER_NOTICE, $notices, true));

            $settings = app(SettingsService::class);
            self::assertFalse($settings->hasSecret('smtp_password'), 'stored password must be cleared');
            self::assertNull($settings->getSecret('smtp_password'));
            self::assertSame('0', $settings->get('smtp_enabled'), 'switch must be OFF after a server change');
            self::assertSame('', $settings->get('smtp_last_test_result'));
            self::assertSame('', $settings->get('smtp_last_test_fingerprint'));
            self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
        }
        $this->actingAs($this->owner)->get('/admin/settings/smtp')->assertOk()->assertSee(self::SERVER_NOTICE);

        // The test e-mail is refused until a password exists again: nothing reaches a transport.
        $sentBefore = count($fake->sent);
        $configsBefore = count($fake->configs);
        $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $this->confirm())
            ->assertSessionHasErrors('smtp_test');
        self::assertStringContainsString('Lozinka nije sačuvana', (string) session('errors')->first('smtp_test'));
        self::assertSame('password_missing', app(SettingsService::class)->get('smtp_last_test_result'));
        self::assertCount($sentBefore, $fake->sent);
        self::assertCount($configsBefore, $fake->configs);

        // A server change together with a new password keeps the new password.
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_host' => '192.0.2.26', 'smtp_password' => self::SECRET.'-2']))
            ->assertSessionHasNoErrors()->assertSessionMissing('smtp_notices');
        self::assertSame(self::SECRET.'-2', app(SettingsService::class)->getSecret('smtp_password'));
        $this->assertNoSecretAnywhere();
    }

    public function test_switch_goes_on_only_after_a_successful_test_of_the_saved_connection(): void
    {
        $fake = $this->fakeTransport(null);
        $settings = app(SettingsService::class);

        // First save with the switch ON: saved, but the switch stays OFF.
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_enabled' => '1']))
            ->assertSessionHasNoErrors()->assertSessionHas('smtp_notices');
        self::assertSame('0', $settings->get('smtp_enabled'));
        self::assertSame('192.0.2.25', $settings->get('smtp_host'));

        // A failed test keeps it OFF.
        $fake->failure = new TransportException('Connection could not be established with host "ssl://192.0.2.25:465" (Connection refused)');
        $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $this->confirm())->assertSessionHasErrors('smtp_test');
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_enabled' => '1', 'smtp_password' => '']))
            ->assertSessionHas('smtp_notices');
        self::assertSame('0', $settings->get('smtp_enabled'));

        // A successful test, then turning ON requires the current password.
        $fake->failure = null;
        $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $this->confirm())->assertSessionHasNoErrors();
        self::assertTrue(app(SmtpMailSettingsService::class)->testPassedForCurrentSettings());
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_enabled' => '1', 'smtp_password' => '', 'current_password' => '']))
            ->assertSessionHasErrors('current_password');
        self::assertSame('0', $settings->get('smtp_enabled'));
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_enabled' => '1', 'smtp_password' => '']))
            ->assertSessionHasNoErrors()->assertSessionMissing('smtp_notices');
        self::assertSame('1', $settings->get('smtp_enabled'));
        self::assertSame('database', app(MailRuntimeConfigurator::class)->apply());

        // Re-entering the password is a connection edit: the test result is void and the switch goes OFF.
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_enabled' => '1', 'smtp_password' => self::SECRET]))
            ->assertSessionHasNoErrors();
        self::assertSame('0', $settings->get('smtp_enabled'));
        self::assertFalse(app(SmtpMailSettingsService::class)->testPassedForCurrentSettings());

        // A test that fails while ON switches it OFF.
        $this->enableTested();
        $fake->failure = new TransportException('Connection could not be established (Connection timed out)');
        $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $this->confirm())->assertSessionHasErrors('smtp_test');
        self::assertStringContainsString('Prekidač je isključen', (string) session('errors')->first('smtp_test'));
        self::assertSame('0', $settings->get('smtp_enabled'));
        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
    }

    public function test_connection_changes_and_test_mail_require_the_current_password(): void
    {
        $fake = $this->fakeTransport(null);
        foreach (['', 'Wrong-Password-1'] as $given) {
            $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['current_password' => $given]))
                ->assertRedirect('/admin/settings/smtp')
                ->assertSessionHasErrors('current_password');
            self::assertArrayNotHasKey('current_password', (array) session('_old_input', []));
        }
        self::assertFalse(app(SettingsService::class)->hasStoredValue('smtp_host'));
        self::assertFalse(app(SettingsService::class)->hasSecret('smtp_password'));

        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload())->assertSessionHasNoErrors();
        foreach ([[], ['current_password' => 'Wrong-Password-1']] as $body) {
            $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $body)
                ->assertSessionHasErrors('smtp_test');
        }
        self::assertSame([], $fake->configs, 'no connection may be opened without re-confirmation');
        self::assertSame('', app(SettingsService::class)->get('smtp_last_test_result'));
        // Two refused saves and two refused tests.
        self::assertSame(4, AuditLog::query()->where('action', 'settings.smtp.reauth_failed')->count());

        // Non-connection edits (timeout, switching OFF) do not need it.
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_password' => '', 'smtp_timeout' => '5', 'current_password' => '']))
            ->assertSessionHasNoErrors();
        self::assertSame('5', app(SettingsService::class)->get('smtp_timeout'));
        $this->assertNoSecretAnywhere();
        foreach ($this->logged as $line) {
            self::assertStringNotContainsString(self::OWNER_PASSWORD, $line);
        }
    }

    public function test_unchecking_the_switch_is_saved_even_when_the_rest_is_rejected(): void
    {
        $settings = app(SettingsService::class);

        // Failed re-confirmation plus a stale host from old() or autofill.
        $this->enableTested();
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload([
            'smtp_enabled' => '0', 'smtp_host' => 'stale.example.net', 'smtp_password' => '', 'current_password' => 'Wrong-Password-1',
        ]))->assertRedirect('/admin/settings/smtp')
            ->assertSessionHasErrors('current_password')
            ->assertSessionHas('smtp_notices');
        self::assertSame('0', $settings->get('smtp_enabled'));
        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
        // Nothing else in the rejected request was applied.
        self::assertSame('192.0.2.25', $settings->get('smtp_host'));
        self::assertSame(self::SECRET, $settings->getSecret('smtp_password'));
        self::assertSame('ok', $settings->get('smtp_last_test_result'));

        // A validation error in the same request does not block the OFF either.
        $this->enableTested();
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload([
            'smtp_enabled' => '0', 'smtp_host' => 'http://bad host', 'current_password' => '',
        ]))->assertSessionHasErrors('smtp_host');
        self::assertSame('0', $settings->get('smtp_enabled'));
        self::assertSame('192.0.2.25', $settings->get('smtp_host'));

        // A rejected request that keeps the switch checked leaves it ON.
        $this->enableTested();
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload([
            'smtp_enabled' => '1', 'smtp_host' => 'stale.example.net', 'smtp_password' => '', 'current_password' => 'Wrong-Password-1',
        ]))->assertSessionHasErrors('current_password')->assertSessionMissing('smtp_notices');
        self::assertSame('1', $settings->get('smtp_enabled'));

        self::assertSame(2, AuditLog::query()->where('action', 'settings.smtp.disabled')->count());
    }

    public function test_dedicated_off_action_needs_no_password_and_touches_nothing_else(): void
    {
        $route = app('router')->getRoutes()->getByName('admin.settings.smtp.disable');
        self::assertNotNull($route);
        self::assertSame(['POST'], $route->methods());
        self::assertContains('throttle:admin-write', $route->gatherMiddleware());

        $this->enableTested();
        $this->actingAs($this->owner)->get('/admin/settings/smtp')->assertOk()
            ->assertSee('Isključi odmah')->assertSee(route('admin.settings.smtp.disable'), false);

        $this->actingAs($this->owner)->post('/admin/settings/smtp/disable')
            ->assertRedirect('/admin/settings/smtp')->assertSessionHasNoErrors()->assertSessionHas('status');
        $settings = app(SettingsService::class);
        self::assertSame('0', $settings->get('smtp_enabled'));
        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
        self::assertSame('192.0.2.25', $settings->get('smtp_host'));
        self::assertSame(self::SECRET, $settings->getSecret('smtp_password'));
        self::assertTrue(app(SmtpMailSettingsService::class)->testPassedForCurrentSettings());
        $audit = AuditLog::query()->where('action', 'settings.smtp.disabled')->sole();
        self::assertSame('button', $audit->metadata_json['via']);

        $this->actingAs($this->owner)->get('/admin/settings/smtp')->assertOk()->assertDontSee('Isključi odmah');
    }

    public function test_reauth_failures_are_rate_limited_before_the_hash_check(): void
    {
        $fake = $this->fakeTransport(null);
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload())->assertSessionHasNoErrors();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->actingAs($this->owner)->post('/admin/settings/smtp/test', ['current_password' => 'Wrong-Password-'.$attempt])
                ->assertSessionHasErrors('smtp_test');
            self::assertStringContainsString('ispravnu trenutnu lozinku', (string) session('errors')->first('smtp_test'));
        }
        // Even the correct password is refused while locked out.
        $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $this->confirm())->assertSessionHasErrors('smtp_test');
        $message = (string) session('errors')->first('smtp_test');
        self::assertStringContainsString('Previše neuspešnih potvrda lozinke', $message);
        self::assertMatchesRegularExpression('/za \d+ min\./', $message);
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_port' => '587', 'smtp_encryption' => 'tls']))
            ->assertSessionHasErrors('current_password');
        self::assertStringContainsString('Previše neuspešnih', (string) session('errors')->first('current_password'));
        self::assertSame('465', app(SettingsService::class)->get('smtp_port'));

        self::assertSame([], $fake->configs);
        self::assertSame(2, AuditLog::query()->where('action', 'settings.smtp.reauth_throttled')->count());
        self::assertSame(5, AuditLog::query()->where('action', 'settings.smtp.reauth_failed')->count());
        foreach (AuditLog::query()->get() as $row) {
            self::assertStringNotContainsString('Wrong-Password-', (string) json_encode($row->toArray()));
            self::assertStringNotContainsString(self::OWNER_PASSWORD, (string) json_encode($row->toArray()));
        }
    }

    public function test_form_fields_steer_password_managers_away_from_the_smtp_credentials(): void
    {
        $html = (string) $this->actingAs($this->owner)->get('/admin/settings/smtp')->assertOk()
            ->assertSee('Potvrda identiteta')
            ->assertSee('Ako nemate lozinku (prijava samo pristupnim ključem), postavite je preko resetovanja lozinke.')
            ->getContent();

        self::assertStringContainsString('name="smtp_username" id="smtp_username" maxlength="254" autocomplete="off"', $html);
        self::assertStringContainsString('name="smtp_password" id="smtp_password" maxlength="255" autocomplete="new-password"', $html);
        $decoy = 'name="smtp_reauth_username" id="smtp_reauth_username" autocomplete="username" value="smtp-owner@example.test" readonly';
        $current = 'name="current_password" id="current_password" maxlength="255" autocomplete="current-password"';
        self::assertStringContainsString($decoy, $html);
        self::assertStringContainsString($current, $html);
        self::assertStringContainsString('form="smtp-send-test" autocomplete="username"', $html);
        self::assertStringContainsString('id="smtp_test_current_password" form="smtp-send-test" maxlength="255" autocomplete="current-password"', $html);

        // Order: SMTP fields, then the identity block with the decoy right before the current password.
        $smtpPassword = strpos($html, 'name="smtp_password"');
        $fieldset = strpos($html, 'data-smtp-reauth');
        $decoyAt = strpos($html, $decoy);
        $currentAt = strpos($html, $current);
        self::assertNotFalse($smtpPassword);
        self::assertTrue($smtpPassword < $fieldset && $fieldset < $decoyAt && $decoyAt < $currentAt);
        // Exactly one decoy belongs to the settings form; the other one to the test form.
        self::assertSame(1, preg_match_all('/<input[^>]+name="smtp_reauth_username"(?![^>]*form=)/', $html));
        self::assertSame(2, substr_count($html, 'name="smtp_reauth_username"'));
    }

    public function test_changing_the_from_address_or_registration_sender_voids_the_passing_test(): void
    {
        $this->enableTested();
        self::assertSame('database', app(MailRuntimeConfigurator::class)->apply());

        config()->set('mail.from.address', 'other@ald1n.com');
        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
        self::assertSame('not_tested', app(MailRuntimeConfigurator::class)->reason());
        config()->set('mail.from.address', 'noreply@ald1n.com');
        self::assertSame('database', app(MailRuntimeConfigurator::class)->apply());

        app(SettingsService::class)->putMany(['public_registration_sender_name' => 'Novo ime'], $this->owner->id);
        self::assertSame('env', app(MailRuntimeConfigurator::class)->apply());
        self::assertFalse(app(SmtpMailSettingsService::class)->testPassedForCurrentSettings());
        $this->actingAs($this->owner)->get('/admin/settings/smtp')->assertOk()
            ->assertSee('Probni e-mail još nije uspeo za sačuvana podešavanja.');
    }

    public function test_password_spaces_survive_but_other_secrets_keep_trimming(): void
    {
        $spaced = '  spaced Synthetic pass  ';
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_password' => $spaced]))
            ->assertSessionHasNoErrors();
        self::assertSame($spaced, app(SettingsService::class)->getSecret('smtp_password'));

        $fake = $this->fakeTransport(null);
        $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $this->confirm())->assertSessionHasNoErrors();
        self::assertSame($spaced, $fake->configs[0]['password']);

        app(SettingsService::class)->putSecret('synthetic_other_secret', '  trimmed-value  ', $this->owner->id);
        self::assertSame('trimmed-value', app(SettingsService::class)->getSecret('synthetic_other_secret'));
    }

    public function test_audit_records_field_names_and_target_hash_only(): void
    {
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_password' => self::SECRET]))
            ->assertSessionHasNoErrors();
        $this->actingAs($this->owner)->delete('/admin/settings/smtp/password')->assertRedirect('/admin/settings/smtp');
        self::assertFalse(app(SettingsService::class)->hasSecret('smtp_password'));
        self::assertNull(app(SettingsService::class)->getSecret('smtp_password'));

        $updated = AuditLog::query()->where('action', 'settings.smtp.updated')->sole();
        $metadata = $updated->metadata_json;
        self::assertIsArray($metadata);
        self::assertEqualsCanonicalizing(['host', 'port', 'encryption', 'username', 'password'], $metadata['changed_fields']);
        self::assertSame(hash('sha256', '192.0.2.25:465:noreply@ald1n.com'), $metadata['target_sha256']);
        self::assertNull($updated->before_json);
        self::assertNull($updated->after_json);
        self::assertTrue(AuditLog::query()->where('action', 'settings.smtp.password_cleared')->exists());

        $this->actingAs($this->owner)->get('/admin/settings/smtp')->assertOk()
            ->assertSee('Nema lozinke')->assertDontSee('Obriši lozinku');
        $this->assertNoSecretAnywhere();
    }

    public function test_test_mail_goes_only_to_own_address_with_saved_settings_and_short_timeout(): void
    {
        $fake = $this->fakeTransport(null);
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload([
            'smtp_password' => self::SECRET, 'smtp_timeout' => '8',
        ]))->assertSessionHasNoErrors();

        $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $this->confirm() + [
            'to' => 'attacker@example.test', 'recipient' => 'attacker@example.test', 'smtp_host' => '10.0.0.1',
        ])->assertRedirect('/admin/settings/smtp')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        self::assertCount(1, $fake->sent);
        self::assertSame(['smtp-owner@example.test'], $fake->recipients);
        self::assertSame(['noreply@ald1n.com'], $fake->froms);
        self::assertSame('192.0.2.25', $fake->configs[0]['host']);
        self::assertSame(465, $fake->configs[0]['port']);
        self::assertSame('smtps', $fake->configs[0]['scheme']);
        self::assertSame(8, $fake->configs[0]['timeout']);
        self::assertSame(self::SECRET, $fake->configs[0]['password']);

        self::assertSame('ok', app(SettingsService::class)->get('smtp_last_test_result'));
        self::assertNotSame('', app(SettingsService::class)->get('smtp_last_test_at'));
        $audit = AuditLog::query()->where('action', 'settings.smtp.tested')->sole();
        self::assertSame('ok', $audit->metadata_json['result']);
        $this->actingAs($this->owner)->get('/admin/settings/smtp')->assertOk()->assertSee('Uspešno');
        $this->assertNoSecretAnywhere();
    }

    public function test_test_mail_also_verifies_the_approved_registration_sender(): void
    {
        $address = 'registracija@example.test';
        $name = 'Ald1n prodavnica';
        app(SettingsService::class)->putMany([
            'public_registration_sender_address' => $address,
            'public_registration_sender_name' => $name,
            'public_registration_sender_digest' => PublicRegistrationMailSenderService::fingerprint($address, $name),
            'public_registration_sender_approved_by' => (string) $this->owner->id,
            'public_registration_sender_approved_at' => now()->toISOString(),
        ], $this->owner->id);
        self::assertTrue(app(PublicRegistrationMailSenderService::class)->status()['approved']);

        $fake = $this->fakeTransport(null);
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload())->assertSessionHasNoErrors();
        $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $this->confirm())->assertSessionHasNoErrors();
        self::assertSame(['noreply@ald1n.com', $address], $fake->froms);
        self::assertSame(['smtp-owner@example.test', 'smtp-owner@example.test'], $fake->recipients);

        // The server accepts MAIL_FROM_ADDRESS but rejects the registration sender.
        $fake->failure = new TransportException('Expected response code "250" but got code "553", with message "553 sender is not allowed".');
        $fake->failFrom = $address;
        $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $this->confirm())->assertSessionHasErrors('smtp_test');
        $message = (string) session('errors')->first('smtp_test');
        self::assertStringContainsString('Pošiljalac odbijen', $message);
        self::assertStringContainsString('potvrda registracije', $message);
        self::assertStringNotContainsString($address, $message);
        self::assertSame('registration_sender_rejected', app(SettingsService::class)->get('smtp_last_test_result'));
        self::assertFalse(app(SmtpMailSettingsService::class)->testPassedForCurrentSettings());
    }

    /** @return iterable<string,array{0:string,1:string,2:string}> */
    public static function transportFailures(): iterable
    {
        yield 'refused' => ['Connection could not be established with host "ssl://192.0.2.25:465": stream_socket_client(): Unable to connect to ssl://192.0.2.25:465 (Connection refused)', 'connection_refused', 'Server je odbio vezu'];
        yield 'timeout' => ['Connection could not be established with host "ssl://192.0.2.25:465": stream_socket_client(): Unable to connect to ssl://192.0.2.25:465 (Connection timed out)', 'timeout', 'Isteklo je vreme'];
        yield 'tls' => ['Connection could not be established with host "ssl://192.0.2.25:465": stream_socket_client(): SSL operation failed with code 1. OpenSSL Error messages: error:0A000086:SSL routines::certificate verify failed', 'tls', 'TLS/SSL veza'];
        yield 'auth' => ['Failed to authenticate on SMTP server with username "noreply@ald1n.com" using the following authenticators: "LOGIN", "PLAIN". Authenticator "LOGIN" returned "Expected response code "235" but got code "535", with message "535 Incorrect authentication data".".', 'auth', 'Prijava na SMTP server nije uspela'];
        yield 'sender' => ['Expected response code "250" but got code "550", with message "550 Sender verify failed".', 'sender_rejected', 'Pošiljalac odbijen'];
        yield 'dns' => ['Connection could not be established with host "ssl://mail.invalid:465": stream_socket_client(): php_network_getaddresses: getaddrinfo for mail.invalid failed: Name or service not known', 'dns', 'Host nije pronađen'];
        yield 'unknown' => ['220-server.example.net ESMTP Exim 4.98 banner #7 unexpected', 'unknown', 'ne prepoznajemo'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('transportFailures')]
    public function test_test_mail_failure_is_mapped_to_a_short_sanitized_reason(string $raw, string $code, string $label): void
    {
        $this->fakeTransport(new TransportException($raw));
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_password' => self::SECRET]))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $this->confirm())
            ->assertRedirect('/admin/settings/smtp')
            ->assertSessionHasErrors('smtp_test');
        $message = (string) session('errors')->first('smtp_test');
        self::assertStringContainsString($label, $message);
        self::assertStringNotContainsString('192.0.2.25', $message);
        self::assertStringNotContainsString('noreply@ald1n.com', $message);
        self::assertStringNotContainsString('535', $message);
        self::assertStringNotContainsString('Exim', $message);

        self::assertSame($code, app(SettingsService::class)->get('smtp_last_test_result'));
        $audit = AuditLog::query()->where('action', 'settings.smtp.tested')->sole();
        self::assertSame($code, $audit->metadata_json['result']);
        self::assertStringNotContainsString('Exim', json_encode($audit->toArray()) ?: '');
        $page = (string) $this->actingAs($this->owner)->get('/admin/settings/smtp')->assertOk()->getContent();
        self::assertStringNotContainsString('Exim', $page);
        self::assertStringNotContainsString('Incorrect authentication data', $page);
        $this->assertNoSecretAnywhere();
    }

    public function test_test_mail_refuses_incomplete_settings_and_link_local_targets(): void
    {
        $fake = $this->fakeTransport(null);
        $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $this->confirm())->assertSessionHasErrors('smtp_test');
        self::assertSame('incomplete', app(SettingsService::class)->get('smtp_last_test_result'));

        // A metadata address that bypassed the form (direct DB write) is still refused.
        foreach (['169.254.169.254', 'fd00:ec2::254', '2852039166'] as $host) {
            app(SettingsService::class)->putMany(['smtp_host' => $host, 'smtp_port' => '25', 'smtp_encryption' => 'none'], $this->owner->id);
            $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $this->confirm())->assertSessionHasErrors('smtp_test');
        }
        self::assertSame([], $fake->configs);
        self::assertTrue(SmtpMailSettingsService::blockedIp('64:ff9b::a9fe:a9fe'));
        self::assertTrue(SmtpMailSettingsService::blockedIp('2002:a9fe:a9fe::1'));
        self::assertFalse(SmtpMailSettingsService::blockedIp('2002:c000:0219::1'));
        self::assertFalse(SmtpMailSettingsService::blockedIp('::1'));
        self::assertFalse(SmtpMailSettingsService::blockedIp('10.0.0.5'));
    }

    public function test_test_mail_route_is_throttled_and_returns_429(): void
    {
        $route = app('router')->getRoutes()->getByName('admin.settings.smtp.test');
        self::assertNotNull($route);
        self::assertSame(['POST'], $route->methods());
        self::assertContains('throttle:smtp-test', $route->gatherMiddleware());
        self::assertContains('web', $route->gatherMiddleware());

        // Re-enable the real throttle middleware that setUp() bypassed.
        $this->app->forgetInstance(ThrottleRequests::class);
        $fake = $this->fakeTransport(null);
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload())->assertSessionHasNoErrors();
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $this->confirm())->assertRedirect('/admin/settings/smtp');
        }
        $this->actingAs($this->owner)->post('/admin/settings/smtp/test', $this->confirm())->assertStatus(429);
        self::assertCount(3, $fake->sent);
    }

    private function enableTested(): void
    {
        $settings = app(SettingsService::class);
        $settings->putMany([
            'smtp_enabled' => '0', 'smtp_host' => '192.0.2.25', 'smtp_port' => '465',
            'smtp_encryption' => 'ssl', 'smtp_username' => 'noreply@ald1n.com', 'smtp_timeout' => '',
        ], $this->owner->id);
        $settings->putSecret('smtp_password', self::SECRET, $this->owner->id);
        $settings->putMany([
            'smtp_enabled' => '1',
            'smtp_last_test_result' => 'ok',
            'smtp_last_test_fingerprint' => app(SmtpMailSettingsService::class)->connectionFingerprint(),
        ], $this->owner->id);
    }

    private function assertNoSecretAnywhere(): void
    {
        foreach (AuditLog::query()->get() as $row) {
            self::assertStringNotContainsString(self::SECRET, (string) json_encode($row->toArray()));
        }
        foreach ($this->logged as $line) {
            self::assertStringNotContainsString(self::SECRET, $line);
        }
        self::assertStringNotContainsString(self::SECRET, (string) json_encode(session()->all()));
    }

    /** @return array<string,string> */
    private function confirm(): array
    {
        return ['current_password' => self::OWNER_PASSWORD];
    }

    /** @param array<string,string> $change @return array<string,string> */
    private function payload(array $change = []): array
    {
        // 192.0.2.0/24 is TEST-NET-1: an IP literal needs no DNS lookup in CI.
        return array_replace([
            'smtp_enabled' => '0',
            'smtp_host' => '192.0.2.25',
            'smtp_port' => '465',
            'smtp_encryption' => 'ssl',
            'smtp_username' => 'noreply@ald1n.com',
            'smtp_password' => self::SECRET,
            'smtp_timeout' => '',
            'current_password' => self::OWNER_PASSWORD,
        ], $change);
    }

    private function fakeTransport(?\Throwable $failure): object
    {
        $fake = new class($failure) extends SmtpTestTransportFactory {
            /** @var list<array<string,mixed>> */
            public array $configs = [];
            /** @var list<RawMessage> */
            public array $sent = [];
            /** @var list<string> */
            public array $recipients = [];
            /** @var list<string> */
            public array $froms = [];

            /** When set, only messages From this address fail. */
            public ?string $failFrom = null;

            public function __construct(public ?\Throwable $failure) {}

            public function create(array $config): TransportInterface
            {
                $this->configs[] = $config;

                return new class($this) implements TransportInterface {
                    public function __construct(private readonly object $outer) {}

                    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
                    {
                        $from = $message instanceof Email ? ($message->getFrom()[0] ?? null)?->getAddress() : null;
                        if ($this->outer->failure !== null
                            && ($this->outer->failFrom === null || $this->outer->failFrom === $from)) {
                            throw $this->outer->failure;
                        }
                        $this->outer->sent[] = $message;
                        $this->outer->froms[] = (string) $from;
                        foreach ($message->getTo() as $address) {
                            $this->outer->recipients[] = $address->getAddress();
                        }

                        return null;
                    }

                    public function __toString(): string
                    {
                        return 'fake://smtp';
                    }
                };
            }
        };
        $this->app->instance(SmtpTestTransportFactory::class, $fake);

        return $fake;
    }

    private function person(string $name, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'username' => $name,
            'email' => $name.'@example.test',
            'first_name' => 'Synthetic',
            'password_hash' => Hash::make(self::OWNER_PASSWORD),
            'status' => 'active',
        ]);
    }
}
