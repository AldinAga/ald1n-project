<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\SmtpTestTransportFactory;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

/**
 * SuperAdmin "E-mail (SMTP)" page: access, validation, write-only password,
 * audit hygiene and the sanitized test e-mail.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SmtpSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'Synthetic-SMTP-P4ss!word';

    private User $owner;

    /** @var list<string> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
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
            $this->actingAs($user)->post('/admin/settings/smtp/test')->assertForbidden();
            $this->actingAs($user)->delete('/admin/settings/smtp/password')->assertForbidden();
        }
        self::assertFalse(app(SettingsService::class)->hasStoredValue('smtp_host'));

        $admin = User::query()->where('username', 'smtp-admin')->sole();
        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertDontSee('E-mail (SMTP)');

        $this->actingAs($this->owner)->get('/admin/settings/smtp')->assertOk()
            ->assertSee('E-mail (SMTP)')
            ->assertSee('Koristi SMTP podešavanja iz baze')
            ->assertSee('Pošalji probni e-mail')
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
        self::assertStringContainsString('name="smtp_password" maxlength="255" autocomplete="off" value=""', (string) $page->getContent());

        // A blank password keeps the stored one.
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_password' => '', 'smtp_timeout' => '20']))
            ->assertSessionHasNoErrors();
        self::assertSame(self::SECRET, app(SettingsService::class)->getSecret('smtp_password'));
        self::assertSame('20', app(SettingsService::class)->get('smtp_timeout'));

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
            ['smtp_host' => ''],
        ] as $change) {
            $this->put('/admin/settings/smtp', $this->payload($change))->assertSessionHasErrors('smtp_host');
        }
        foreach (['0', '65536', 'abc', ''] as $port) {
            $this->put('/admin/settings/smtp', $this->payload(['smtp_port' => $port]))->assertSessionHasErrors('smtp_port');
        }
        foreach (['', 'starttls', 'TLS'] as $encryption) {
            $this->put('/admin/settings/smtp', $this->payload(['smtp_encryption' => $encryption]))->assertSessionHasErrors('smtp_encryption');
        }
        $this->put('/admin/settings/smtp', $this->payload(['smtp_password' => '']))->assertSessionHasErrors('smtp_password');
        $this->put('/admin/settings/smtp', $this->payload(['smtp_timeout' => '120']))->assertSessionHasErrors('smtp_timeout');
        $this->put('/admin/settings/smtp', $this->payload(['smtp_username' => "user\r\nX"]))->assertSessionHasErrors('smtp_username');
        self::assertFalse(app(SettingsService::class)->hasStoredValue('smtp_host'));

        foreach (['mail.ald1n.com', '127.0.0.1', '::1', 'localhost'] as $host) {
            $this->put('/admin/settings/smtp', $this->payload(['smtp_host' => $host, 'smtp_password' => self::SECRET]))
                ->assertSessionHasNoErrors();
            self::assertSame($host, app(SettingsService::class)->get('smtp_host'));
        }

        // Disabled drafts may stay incomplete, but a given host is still checked.
        $this->put('/admin/settings/smtp', ['smtp_host' => '', 'smtp_port' => '', 'smtp_encryption' => ''])->assertSessionHasNoErrors();
        $this->put('/admin/settings/smtp', ['smtp_host' => '169.254.169.254'])->assertSessionHasErrors('smtp_host');
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
        self::assertEqualsCanonicalizing(['enabled', 'host', 'port', 'encryption', 'username', 'password'], $metadata['changed_fields']);
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
            'smtp_enabled' => '0', 'smtp_password' => self::SECRET, 'smtp_timeout' => '45',
        ]))->assertSessionHasNoErrors();

        $this->actingAs($this->owner)->post('/admin/settings/smtp/test', [
            'to' => 'attacker@example.test', 'recipient' => 'attacker@example.test', 'smtp_host' => '10.0.0.1',
        ])->assertRedirect('/admin/settings/smtp')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        self::assertCount(1, $fake->sent);
        self::assertSame(['smtp-owner@example.test'], $fake->recipients);
        self::assertSame('192.0.2.25', $fake->configs[0]['host']);
        self::assertSame(465, $fake->configs[0]['port']);
        self::assertSame('smtps', $fake->configs[0]['scheme']);
        self::assertSame(10, $fake->configs[0]['timeout']);
        self::assertSame(self::SECRET, $fake->configs[0]['password']);

        self::assertSame('ok', app(SettingsService::class)->get('smtp_last_test_result'));
        self::assertNotSame('', app(SettingsService::class)->get('smtp_last_test_at'));
        $audit = AuditLog::query()->where('action', 'settings.smtp.tested')->sole();
        self::assertSame('ok', $audit->metadata_json['result']);
        $this->actingAs($this->owner)->get('/admin/settings/smtp')->assertOk()->assertSee('Uspešno');
        $this->assertNoSecretAnywhere();
    }

    /** @return iterable<string,array{0:string,1:string,2:string}> */
    public static function transportFailures(): iterable
    {
        yield 'refused' => ['Connection could not be established with host "ssl://192.0.2.25:465": stream_socket_client(): Unable to connect to ssl://192.0.2.25:465 (Connection refused)', 'connection_refused', 'Server je odbio vezu'];
        yield 'timeout' => ['Connection could not be established with host "ssl://192.0.2.25:465": stream_socket_client(): Unable to connect to ssl://192.0.2.25:465 (Connection timed out)', 'timeout', 'Isteklo je vreme'];
        yield 'tls' => ['Connection could not be established with host "ssl://192.0.2.25:465": stream_socket_client(): SSL operation failed with code 1. OpenSSL Error messages: error:0A000086:SSL routines::certificate verify failed', 'tls', 'TLS/SSL veza'];
        yield 'auth' => ['Failed to authenticate on SMTP server with username "noreply@ald1n.com" using the following authenticators: "LOGIN", "PLAIN". Authenticator "LOGIN" returned "Expected response code "235" but got code "535", with message "535 Incorrect authentication data".".', 'auth', 'Prijava na SMTP server nije uspela'];
        yield 'sender' => ['Expected response code "250" but got code "550", with message "550 Sender verify failed".', 'sender_rejected', 'odbio pošiljaoca'];
        yield 'dns' => ['Connection could not be established with host "ssl://mail.invalid:465": stream_socket_client(): php_network_getaddresses: getaddrinfo for mail.invalid failed: Name or service not known', 'dns', 'Host nije pronađen'];
        yield 'unknown' => ['220-server.example.net ESMTP Exim 4.98 banner #7 unexpected', 'unknown', 'ne prepoznajemo'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('transportFailures')]
    public function test_test_mail_failure_is_mapped_to_a_short_sanitized_reason(string $raw, string $code, string $label): void
    {
        $this->fakeTransport(new TransportException($raw));
        $this->actingAs($this->owner)->put('/admin/settings/smtp', $this->payload(['smtp_password' => self::SECRET]))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->owner)->post('/admin/settings/smtp/test')
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
        $this->actingAs($this->owner)->post('/admin/settings/smtp/test')->assertSessionHasErrors('smtp_test');
        self::assertSame('incomplete', app(SettingsService::class)->get('smtp_last_test_result'));

        // A metadata address that bypassed the form (direct DB write) is still refused.
        app(SettingsService::class)->putMany(['smtp_host' => '169.254.169.254', 'smtp_port' => '25', 'smtp_encryption' => 'none'], $this->owner->id);
        $this->actingAs($this->owner)->post('/admin/settings/smtp/test')->assertSessionHasErrors('smtp_test');
        self::assertSame([], $fake->sent);
    }

    public function test_test_mail_route_is_throttled_and_requires_csrf_stack(): void
    {
        $route = app('router')->getRoutes()->getByName('admin.settings.smtp.test');
        self::assertNotNull($route);
        self::assertSame(['POST'], $route->methods());
        self::assertContains('throttle:smtp-test', $route->gatherMiddleware());
        self::assertContains('web', $route->gatherMiddleware());
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

    /** @param array<string,string> $change @return array<string,string> */
    private function payload(array $change = []): array
    {
        // 192.0.2.0/24 is TEST-NET-1: an IP literal needs no DNS lookup in CI.
        return array_replace([
            'smtp_enabled' => '1',
            'smtp_host' => '192.0.2.25',
            'smtp_port' => '465',
            'smtp_encryption' => 'ssl',
            'smtp_username' => 'noreply@ald1n.com',
            'smtp_password' => self::SECRET,
            'smtp_timeout' => '',
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

            public function __construct(private readonly ?\Throwable $failure) {}

            public function create(array $config): TransportInterface
            {
                $this->configs[] = $config;
                $outer = $this;

                return new class($outer, $this->failure) implements TransportInterface {
                    public function __construct(private readonly object $outer, private readonly ?\Throwable $failure) {}

                    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
                    {
                        if ($this->failure !== null) {
                            throw $this->failure;
                        }
                        $this->outer->sent[] = $message;
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
            'password_hash' => Hash::make('OnlySynthetic123!'),
            'status' => 'active',
        ]);
    }
}
