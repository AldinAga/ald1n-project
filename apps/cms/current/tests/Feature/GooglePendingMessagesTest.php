<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\OperationalNotification;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Poruke za Google naloge na čekanju / u brisanju i obaveštenje administratorima.
 * GoogleIdentityVerifier je final, pa ga ne mokujemo: test potpisuje pravi RS256 token
 * sopstvenim ključem i sertifikat stavlja u keš koji verifikator čita.
 */
final class GooglePendingMessagesTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT_ID = 'test-client.apps.googleusercontent.com';
    private const TOKEN_URL = 'https://oauth2.google.test/token';
    private const PENDING = 'Nalog čeka odobrenje administratora.';
    private const INACTIVE_API = 'CMS nalog nije aktivan. Obrati se administratoru.';
    private const DELETION = 'Nalog je u postupku brisanja do 10.12.2026. Otkažite brisanje linkom iz e-pošte.';

    /** @var \OpenSSLAsymmetricKey */
    private $key;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $store = $this->app['cache']->driver('array');
        (fn () => $this->cache = $store)->call($this->app->make(\Illuminate\Cache\RateLimiter::class));

        config([
            'mobile.google_auth.enabled' => true,
            'mobile.google_auth.web_client_id' => self::CLIENT_ID,
            'mobile.google_auth.registration_enabled' => true,
            'mobile.google_auth.auto_activate_registration' => false,
            'services.google_web.enabled' => true,
            'services.google_web.client_id' => self::CLIENT_ID,
            'services.google_web.client_secret' => 'test-secret',
            'services.google_web.redirect_uri' => 'http://localhost/auth/google/callback',
            'services.google_web.token_url' => self::TOKEN_URL,
            'services.operational_notifications.mail_enabled' => false,
        ]);

        $this->key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['commonName' => 'google-test'], $this->key, ['digest_alg' => 'sha256']);
        openssl_x509_export(openssl_csr_sign($csr, null, $this->key, 1, ['digest_alg' => 'sha256']), $pem);
        Cache::put('mobile-google-auth-certificates-v1', ['kid-test' => $pem], 3600);
    }

    public function test_api_new_registration_notifies_user_managers_once_and_repeat_says_pending(): void
    {
        Notification::fake();
        $super = $this->user('gp-super', 'superadmin');
        $admin = $this->user('gp-admin', 'admin');
        $blockedAdmin = $this->user('gp-admin-off', 'admin', 'blocked');
        $plain = $this->user('gp-plain', 'user');

        $this->api('sub-new', 'novi.korisnik@gmail.com')
            ->assertStatus(202)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('message', 'Registracija je kreirana. Nalog čeka odobrenje administratora.');
        $pending = User::query()->where('email', 'novi.korisnik@gmail.com')->firstOrFail();
        self::assertSame('pending', $pending->status);

        $this->api('sub-new', 'novi.korisnik@gmail.com')
            ->assertStatus(202)
            ->assertJsonPath('message', self::PENDING);

        foreach ([$super, $admin] as $recipient) {
            Notification::assertSentToTimes($recipient, OperationalNotification::class, 1);
            Notification::assertSentTo($recipient, OperationalNotification::class, function (OperationalNotification $n, array $channels) use ($recipient, $pending): bool {
                $data = $n->toArray($recipient);
                return $channels === ['database']
                    && $data['event'] === 'user.google_registration_pending'
                    && $data['user_id'] === $pending->id
                    && str_contains((string) $data['url'], 'status=pending');
            });
        }
        Notification::assertNotSentTo([$blockedAdmin, $plain, $pending], OperationalNotification::class);
        self::assertSame(0, $pending->fresh()->tokens()->count());
    }

    public function test_mail_channel_only_when_operational_email_is_enabled(): void
    {
        Notification::fake();
        config(['services.operational_notifications.mail_enabled' => true]);
        $super = $this->user('gp-mail-super', 'superadmin');
        DB::table('notification_preferences')->insert(['user_id' => $super->id, 'in_app_enabled' => true, 'email_enabled' => true, 'created_at' => now(), 'updated_at' => now()]);

        $this->api('sub-mail', 'mail.korisnik@gmail.com')->assertStatus(202);

        Notification::assertSentTo($super, OperationalNotification::class, static fn ($n, array $channels): bool => $channels === ['database', 'mail']);
    }

    public function test_auto_activated_registration_does_not_notify(): void
    {
        Notification::fake();
        config(['mobile.google_auth.auto_activate_registration' => true]);
        $super = $this->user('gp-auto-super', 'superadmin');

        $this->api('sub-auto', 'auto.korisnik@gmail.com')->assertStatus(201);

        Notification::assertNotSentTo($super, OperationalNotification::class);
    }

    public function test_web_first_and_repeat_attempt_messages_for_pending_account(): void
    {
        Notification::fake();
        $super = $this->user('gp-web-super', 'superadmin');

        $this->web('sub-web', 'web.korisnik@gmail.com')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['google' => 'Google nalog je povezan, ali CMS nalog čeka odobrenje administratora.']);
        $this->web('sub-web', 'web.korisnik@gmail.com')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['google' => self::PENDING]);

        $this->assertGuest();
        Notification::assertSentToTimes($super, OperationalNotification::class, 1);
    }

    public function test_linked_account_in_deletion_grace_gets_dated_message_on_web_and_api(): void
    {
        $user = $this->user('gp-del', 'user', 'blocked');
        $this->link($user, 'sub-del');
        DB::table('account_deletion_requests')->insert([
            'user_id' => $user->id, 'requested_at' => '2026-11-26 12:00:00', 'status' => 'pending',
            'grace_until' => '2026-12-10 12:00:00', 'source' => 'app', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->api('sub-del', $user->email)->assertStatus(422)->assertJsonPath('errors.google.0', self::DELETION);
        $this->web('sub-del', $user->email)->assertSessionHasErrors(['google' => self::DELETION]);
        $this->assertGuest();
    }

    public function test_blocked_without_deletion_and_waiting_obligations_keep_generic_message(): void
    {
        $blocked = $this->user('gp-blocked', 'user', 'blocked');
        $this->link($blocked, 'sub-blocked');
        $waiting = $this->user('gp-waiting', 'user', 'blocked');
        $this->link($waiting, 'sub-waiting');
        DB::table('account_deletion_requests')->insert([
            'user_id' => $waiting->id, 'requested_at' => '2026-11-01 12:00:00', 'status' => 'waiting_obligations',
            'grace_until' => '2026-11-15 12:00:00', 'source' => 'app', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->api('sub-blocked', $blocked->email)->assertStatus(422)->assertJsonPath('errors.google.0', self::INACTIVE_API);
        $this->api('sub-waiting', $waiting->email)->assertStatus(422)->assertJsonPath('errors.google.0', self::INACTIVE_API);
    }

    public function test_unlinked_email_match_does_not_reveal_deletion_state(): void
    {
        // Nepovezan Google identitet koji samo tvrdi isti e-mail ne sme da sazna da je nalog u brisanju ni datum.
        $user = $this->user('gp-claim', 'user', 'blocked');
        DB::table('account_deletion_requests')->insert([
            'user_id' => $user->id, 'requested_at' => '2026-11-26 12:00:00', 'status' => 'pending',
            'grace_until' => '2026-12-10 12:00:00', 'source' => 'app', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->api('sub-stranger', $user->email)->assertStatus(422)->assertJsonPath('errors.google.0', self::INACTIVE_API);
        self::assertSame(0, DB::table('user_external_identities')->count());
    }

    public function test_invalid_token_message_is_the_same_for_existing_and_unknown_accounts(): void
    {
        $user = $this->user('gp-pending', 'user', 'pending');
        $this->link($user, 'sub-pending');
        $bad = ['id_token' => str_repeat('x', 150), 'device_name' => 'phone'];

        $known = $this->postJson('/api/v1/auth/google', $bad)->assertStatus(422)->json('errors.google.0');
        $this->user('gp-other', 'user');
        $unknown = $this->postJson('/api/v1/auth/google', $bad)->assertStatus(422)->json('errors.google.0');

        self::assertSame('Google identitet nije moguće potvrditi. Pokušaj ponovo.', $known);
        self::assertSame($known, $unknown);
    }

    private function api(string $sub, string $email): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/auth/google', ['id_token' => $this->token($sub, $email), 'device_name' => 'phone']);
    }

    private function web(string $sub, string $email): \Illuminate\Testing\TestResponse
    {
        $nonce = 'nonce-'.$sub;
        Http::fake([self::TOKEN_URL => Http::response(['id_token' => $this->token($sub, $email, $nonce)])]);

        return $this->withSession([
            'google_web_oauth_state' => hash('sha256', 'state-'.$sub),
            'google_web_oauth_nonce' => $nonce,
            'google_web_oauth_started_at' => time(),
        ])->get('/auth/google/callback?state=state-'.$sub.'&code=code-1');
    }

    private function token(string $sub, string $email, ?string $nonce = null): string
    {
        $b64 = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $header = $b64((string) json_encode(['alg' => 'RS256', 'kid' => 'kid-test', 'typ' => 'JWT']));
        $payload = $b64((string) json_encode(array_filter([
            'iss' => 'https://accounts.google.com', 'aud' => self::CLIENT_ID, 'sub' => $sub,
            'email' => $email, 'email_verified' => true, 'given_name' => 'Test', 'family_name' => 'Korisnik',
            'iat' => time(), 'exp' => time() + 600, 'nonce' => $nonce,
        ], static fn ($value): bool => $value !== null)));
        openssl_sign($header.'.'.$payload, $signature, $this->key, OPENSSL_ALGO_SHA256);

        return $header.'.'.$payload.'.'.$b64($signature);
    }

    private function link(User $user, string $sub): void
    {
        DB::table('user_external_identities')->insert([
            'user_id' => $user->id, 'provider' => 'google', 'provider_subject' => $sub, 'provider_email' => $user->email,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function user(string $id, string $role = 'user', string $status = 'active'): User
    {
        return User::query()->create([
            'role_id' => DB::table('roles')->where('slug', $role)->value('id'),
            'user_group_id' => 1,
            'username' => $id,
            'email' => $id.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => $status,
        ]);
    }
}
