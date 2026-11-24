<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\AccountDeletionNotification;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Brisanje naloga (Google Play). HTTP tokovi rade na svakoj bazi; konacno brisanje
 * ide kroz UserRetirementRelationPolicy::verifySchema() i zato trci samo na
 * jednokratnoj MySQL/MariaDB bazi (isti uslov kao UserRetirementMySqlIntegrationTest).
 */
final class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Secret123!';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        // cache.limiter je 'file' i CACHE_STORE=array ga ne mijenja; RateLimiter je vec napravljen u boot-u (imenovani limiteri),
        // pa mu samo zamijenimo skladiste svjezim array store-om: brojaci ne prelaze iz testa u test ni iz pokretanja u pokretanje.
        $store = $this->app['cache']->driver('array');
        (fn () => $this->cache = $store)->call($this->app->make(\Illuminate\Cache\RateLimiter::class));
    }

    private function requireMysql(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Konacno brisanje zahteva MySQL semu (verifySchema).');
        }
        self::assertSame('ald1n_retirement_ci', (string) DB::getDatabaseName());
    }

    // ---- API zahtev ------------------------------------------------------

    public function test_api_request_with_password_blocks_account_revokes_access_and_audits_without_pii(): void
    {
        Notification::fake();
        $user = $this->user('del-api', 'user');
        $user->createToken('phone', ['*']);
        $this->loginSession($user);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/account/deletion', ['password' => self::PASSWORD])
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('reauthenticate', true);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'blocked', 'email' => 'del-api@example.test']);
        self::assertSame(0, $user->tokens()->count());
        self::assertNotNull(DB::table('user_login_sessions')->where('user_id', $user->id)->value('revoked_at'));
        $row = DB::table('account_deletion_requests')->where('user_id', $user->id)->first();
        self::assertSame('pending', $row->status);
        self::assertSame('app', $row->source);
        self::assertEqualsWithDelta(14 * 86400, \Illuminate\Support\Carbon::parse($row->requested_at)->diffInSeconds(\Illuminate\Support\Carbon::parse($row->grace_until)), 5);
        $audit = AuditLog::query()->where('action', 'account.deletion_requested')->firstOrFail();
        self::assertNull($audit->ip_address);
        self::assertNull($audit->user_agent);
        self::assertNull($audit->user_id);
        Notification::assertSentTo($user, AccountDeletionNotification::class, static fn ($n): bool => $n->kind === 'scheduled' && strlen((string) $n->token) === 80);
    }

    public function test_api_request_with_wrong_password_or_bad_google_token_changes_nothing(): void
    {
        Notification::fake();
        $user = $this->user('del-wrong', 'user');
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/account/deletion', ['password' => 'nije-ta'])->assertStatus(422)->assertJsonValidationErrors('password');
        $this->postJson('/api/v1/account/deletion', ['id_token' => str_repeat('x', 150)])->assertStatus(422);
        $this->postJson('/api/v1/account/deletion', [])->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'active']);
        self::assertSame(0, DB::table('account_deletion_requests')->count());
        Notification::assertNothingSent();
    }

    public function test_staff_account_needs_another_active_superadmin(): void
    {
        Notification::fake();
        $admin = $this->user('del-admin', 'admin');
        Sanctum::actingAs($admin, ['*']);

        $this->postJson('/api/v1/account/deletion', ['password' => self::PASSWORD])
            ->assertStatus(422)
            ->assertSee('SuperAdministratora', false);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'active']);
        self::assertSame(0, DB::table('account_deletion_requests')->count());

        // Blokiran ili povucen SuperAdministrator nije nasljednik.
        $this->user('del-super-blocked', 'superadmin', 'blocked');
        $this->postJson('/api/v1/account/deletion', ['password' => self::PASSWORD])->assertStatus(422);

        $this->user('del-super', 'superadmin');
        $this->postJson('/api/v1/account/deletion', ['password' => self::PASSWORD])->assertStatus(202);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'blocked']);
    }

    public function test_sole_superadmin_cannot_request_deletion(): void
    {
        $super = $this->user('del-sole', 'superadmin');
        Sanctum::actingAs($super, ['*']);

        $this->postJson('/api/v1/account/deletion', ['password' => self::PASSWORD])->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $super->id, 'status' => 'active']);
    }

    // ---- Web: potvrda e-postom i ponistavanje bez ijedne prijave ------------

    public function test_public_page_renders_and_unknown_email_gets_the_same_answer(): void
    {
        Notification::fake();
        $known = $this->user('del-known', 'customer');

        $this->get('/brisanje-naloga')->assertOk()->assertSee('Brisanje naloga')->assertSee('Rok mirovanja', false);

        $one = $this->post('/brisanje-naloga', ['email' => 'del-known@example.test']);
        $two = $this->post('/brisanje-naloga', ['email' => 'nepoznat@example.test']);
        $one->assertRedirectContains('/brisanje-naloga')->assertSessionHas('status');
        $two->assertRedirectContains('/brisanje-naloga')->assertSessionHas('status');
        self::assertSame($one->getSession()->get('status'), $two->getSession()->get('status'));

        Notification::assertSentToTimes($known, AccountDeletionNotification::class, 1);
        $this->assertDatabaseHas('users', ['id' => $known->id, 'status' => 'active']);
        self::assertSame(1, DB::table('account_deletion_requests')->count());
    }

    public function test_confirm_link_starts_grace_and_cancel_link_works_with_zero_valid_tokens(): void
    {
        Notification::fake();
        $user = $this->user('del-web', 'customer');
        $user->createToken('phone', ['*']);

        $this->post('/brisanje-naloga', ['email' => 'DEL-web@example.test']);
        $confirm = $this->capturedToken($user, 'confirm');

        // GET samo prikazuje stranicu; skener e-poste ne smije pokrenuti brisanje.
        $this->get('/brisanje-naloga/potvrda/'.$confirm)->assertOk();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'active']);

        $this->post('/brisanje-naloga/potvrda', ['token' => $confirm])->assertOk()->assertSee('zakazano');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'blocked']);
        self::assertSame(0, $user->tokens()->count());
        $this->post('/brisanje-naloga/potvrda', ['token' => $confirm])->assertOk()->assertSee('nije važeći', false);

        // Nalog je blokiran, nema nijedne prijave: ponistavanje ide samo linkom iz e-poste.
        // Isti ispravan zahtjev, ali bez ijedne važeće prijave: 401, a nalog ostaje kakav jeste.
        $this->postJson('/api/v1/account/deletion', ['password' => self::PASSWORD])->assertStatus(401);
        self::assertSame(1, DB::table('account_deletion_requests')->where('user_id', $user->id)->count());
        $cancel = $this->capturedToken($user, 'scheduled');
        $this->get('/brisanje-naloga/ponisti/'.$cancel)->assertOk();
        $this->post('/brisanje-naloga/ponisti', ['token' => str_repeat('a', 80)])->assertOk()->assertSee('nije važeći', false);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'blocked']);

        $this->post('/brisanje-naloga/ponisti', ['token' => $cancel])->assertOk()->assertSee('poništeno', false);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'active']);
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $user->id, 'status' => 'cancelled', 'cancel_token_hash' => null]);
        self::assertSame(1, AuditLog::query()->where('action', 'account.deletion_cancelled')->count());
    }

    public function test_expired_confirm_link_is_rejected(): void
    {
        Notification::fake();
        $user = $this->user('del-expired', 'customer');
        $this->post('/brisanje-naloga', ['email' => 'del-expired@example.test']);
        $confirm = $this->capturedToken($user, 'confirm');
        DB::table('account_deletion_requests')->where('user_id', $user->id)->update(['confirm_expires_at' => now()->subMinute()]);

        $this->post('/brisanje-naloga/potvrda', ['token' => $confirm])->assertSee('nije važeći', false);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'active']);
    }

    public function test_logged_in_account_page_button_sends_confirmation_to_own_email(): void
    {
        Notification::fake();
        $user = $this->user('del-button', 'user');

        $this->post('/login', ['login' => $user->email, 'password' => self::PASSWORD]);
        $this->assertAuthenticatedAs($user);
        $this->get('/account')->assertOk()->assertSee('Zatraži brisanje naloga');
        $this->post('/account/deletion', ['deletion_password' => self::PASSWORD])->assertRedirectContains('/account');
        Notification::assertSentTo($user, AccountDeletionNotification::class, static fn ($n): bool => $n->kind === 'confirm');
    }

    public function test_logged_in_request_with_wrong_or_missing_password_sends_nothing_and_spends_no_daily_budget(): void
    {
        Notification::fake();
        $user = $this->user('del-pw', 'user');
        $this->post('/login', ['login' => $user->email, 'password' => self::PASSWORD]);
        $this->assertAuthenticatedAs($user);
        $this->get('/account')->assertOk()->assertSee('name="deletion_password"', false);

        foreach ([['deletion_password' => 'nije-ta'], [], ['deletion_password' => ['x']]] as $payload) {
            // Lozinka se ne vraca u formu (nema old input).
            $this->post('/account/deletion', $payload)->assertStatus(302)->assertRedirectContains('/account')
                ->assertSessionHasErrors('deletion_password')->assertSessionMissing('status')->assertSessionMissing('_old_input');
        }
        Notification::assertNothingSent();
        self::assertSame(0, DB::table('account_deletion_requests')->count());

        // Dnevni brojac slanja (3) je ceo: tri tacna zahteva van pauze salju tri linka.
        for ($i = 0; $i < 3; $i++) {
            $this->post('/account/deletion', ['deletion_password' => self::PASSWORD])->assertSessionHasNoErrors()
                ->assertSessionHas('status', static fn (string $s): bool => str_starts_with($s, 'Poslali smo link'));
            $this->travel(11)->minutes();
        }
        Notification::assertSentToTimes($user, AccountDeletionNotification::class, 3);
    }

    public function test_wrong_password_budget_locks_even_the_correct_password_for_fifteen_minutes(): void
    {
        Notification::fake();
        $user = $this->user('del-pw-lock', 'user');
        $this->post('/login', ['login' => $user->email, 'password' => self::PASSWORD]);
        $this->assertAuthenticatedAs($user);

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/account/deletion', ['deletion_password' => 'pogresna-'.$i])
                ->assertSessionHasErrors(['deletion_password' => 'Lozinka nije ispravna.']);
        }
        $locked = $this->post('/account/deletion', ['deletion_password' => self::PASSWORD])
            ->assertSessionHasErrors('deletion_password')->assertSessionMissing('status');
        self::assertStringStartsWith('Previše neuspešnih pokušaja', (string) $locked->getSession()->get('errors')->first('deletion_password'));
        Notification::assertNothingSent();
        self::assertSame(1, AuditLog::query()->where('action', 'account.password_change_throttled')->count());

        $this->travel(16)->minutes();
        $this->post('/account/deletion', ['deletion_password' => self::PASSWORD])->assertSessionHasNoErrors();
        Notification::assertSentToTimes($user, AccountDeletionNotification::class, 1);
    }

    public function test_account_created_by_google_without_password_keeps_the_email_link_only_flow(): void
    {
        Notification::fake();
        $google = $this->user('del-pw-google', 'user');
        $this->linkGoogle($google, 'sub-del-google');
        $linkedLater = $this->user('del-pw-linked', 'user');
        $withPassword = $this->user('del-pw-set', 'user');
        $this->linkGoogle($withPassword, 'sub-del-set');
        $withPassword->forceFill(['password_changed_at' => now()])->save();
        $this->travel(1)->days();
        $this->linkGoogle($linkedLater, 'sub-del-linked');

        $this->post('/login', ['login' => $google->email, 'password' => self::PASSWORD]);
        $this->assertAuthenticatedAs($google);
        $this->get('/account')->assertOk()->assertDontSee('name="deletion_password"', false)->assertSee('samo linkom iz e-pošte', false);
        $this->post('/account/deletion')->assertSessionHasNoErrors()
            ->assertSessionHas('status', static fn (string $s): bool => str_starts_with($s, 'Poslali smo link'));
        Notification::assertSentToTimes($google, AccountDeletionNotification::class, 1);

        // Nalog sa lozinkom (postavljenom ili povezan sa Google-om naknadno) i dalje trazi lozinku.
        foreach ([$withPassword, $linkedLater] as $other) {
            $this->post('/logout');
            $this->post('/login', ['login' => $other->email, 'password' => self::PASSWORD]);
            $this->assertAuthenticatedAs($other);
            $this->post('/account/deletion')->assertSessionHasErrors('deletion_password');
            Notification::assertNothingSentTo($other);
        }
    }

    // ---- Ogranicenje i izjednaceno vrijeme javnog zahtjeva ---------------------

    public function test_public_request_pauses_per_account_counts_only_real_sends_and_limits_per_ip(): void
    {
        Notification::fake();
        $known = $this->user('del-limit', 'customer');

        // Isti nalog sa razlicitih IP adresa: u pauzi od 10 min samo prvi zahtjev salje mail, token ostaje isti.
        $responses = [];
        foreach (['198.51.100.1', '198.51.100.2', '198.51.100.3', '198.51.100.4'] as $i => $ip) {
            $responses[] = $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->post('/brisanje-naloga', ['email' => $i === 3 ? 'DEL-limit@example.test' : 'del-limit@example.test']);
        }
        Notification::assertSentToTimes($known, AccountDeletionNotification::class, 1);
        $first = $this->capturedToken($known, 'confirm');
        self::assertSame(1, DB::table('account_deletion_requests')->where('user_id', $known->id)->count());
        self::assertSame(hash('sha256', $first), DB::table('account_deletion_requests')->where('user_id', $known->id)->value('confirm_token_hash'));

        // Odgovor za postojeci nalog u pauzi i za nepostojeci nalog je isti.
        $unknown = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.5'])->post('/brisanje-naloga', ['email' => 'nepoznat-limit@example.test']);
        foreach ($responses as $response) {
            self::assertSame($unknown->getStatusCode(), $response->getStatusCode());
            self::assertSame($unknown->headers->get('Location'), $response->headers->get('Location'));
            self::assertSame($unknown->getSession()->get('status'), $response->getSession()->get('status'));
        }

        // Posle pauze novi link; najvise 3 stvarna slanja dnevno po nalogu.
        for ($i = 0; $i < 3; $i++) {
            $this->travel(11)->minutes();
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])->post('/brisanje-naloga', ['email' => 'del-limit@example.test'])->assertStatus(302);
        }
        Notification::assertSentToTimes($known, AccountDeletionNotification::class, 3);

        // Jedna IP adresa, razliciti e-mailovi: gornja granica 20 po satu, isti 429 za postojeci i nepostojeci nalog.
        $ip = ['REMOTE_ADDR' => '203.0.113.50'];
        for ($i = 1; $i <= 20; $i++) {
            $this->withServerVariables($ip)->post('/brisanje-naloga', ['email' => "bulk{$i}@example.test"])->assertStatus(302);
        }
        $blockedKnown = $this->withServerVariables($ip)->post('/brisanje-naloga', ['email' => 'del-limit@example.test'])->assertStatus(429);
        $blockedUnknown = $this->withServerVariables($ip)->post('/brisanje-naloga', ['email' => 'bulk21@example.test'])->assertStatus(429);
        // CSP nonce je drugaciji u svakom odgovoru; ostatak stranice mora biti isti.
        $strip = static fn (string $html): string => (string) preg_replace('/nonce="[^"]*"/', 'nonce=""', $html);
        self::assertSame($strip((string) $blockedKnown->getContent()), $strip((string) $blockedUnknown->getContent()));
    }

    public function test_unicode_and_width_variants_of_an_address_share_the_account_pause(): void
    {
        Notification::fake();
        $known = $this->user('del-uni', 'customer');
        $deletions = app(\App\Services\UserDeletionService::class);

        // Na MySQL utf8mb4_unicode_ci ove varijante pogadjaju isti red; pauza je po nalogu, pa ide samo jedan mail.
        foreach (['del-uni@example.test', 'DÉL-uni@example.test', 'dél-uni@example.test', 'ｄｅｌ-uni@example.test', 'del-ｕni@example.test'] as $variant) {
            $deletions->requestByEmail($variant);
        }
        $this->post('/brisanje-naloga', ['email' => 'Del-Uni@example.test'])->assertStatus(302);

        Notification::assertSentToTimes($known, AccountDeletionNotification::class, 1);
        self::assertSame(1, DB::table('account_deletion_requests')->where('user_id', $known->id)->count());
    }

    public function test_attacker_cannot_spend_the_logged_in_users_send_budget(): void
    {
        Notification::fake();
        $victim = $this->user('del-victim', 'user'); // /account je portal ruta (kao test dugmeta na stranici naloga)

        // Napadac iscrpi javni dnevni budzet za adresu zrtve i javni limit po IP-u.
        $attacker = ['REMOTE_ADDR' => '192.0.2.77'];
        for ($i = 0; $i < 3; $i++) {
            $this->withServerVariables($attacker)->post('/brisanje-naloga', ['email' => 'del-victim@example.test'])->assertStatus(302);
            $this->travel(11)->minutes();
        }
        $this->withServerVariables($attacker)->post('/brisanje-naloga', ['email' => 'del-victim@example.test'])->assertStatus(302);
        Notification::assertSentToTimes($victim, AccountDeletionNotification::class, 3); // 4. javni pokusaj: dnevni limit
        for ($i = 1; $i <= 20; $i++) {
            $this->withServerVariables($attacker)->post('/brisanje-naloga', ['email' => "spam{$i}@example.test"]);
        }
        $this->withServerVariables($attacker)->post('/brisanje-naloga', ['email' => 'del-victim@example.test'])->assertStatus(429);

        // Prijavljena zrtva ima svoj limiter i svoj brojac slanja.
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);
        $this->post('/login', ['login' => $victim->email, 'password' => self::PASSWORD]);
        $this->assertAuthenticatedAs($victim);
        $this->post('/account/deletion', ['deletion_password' => self::PASSWORD])->assertRedirectContains('/account')->assertSessionHasNoErrors()
            ->assertSessionHas('status', static fn (string $s): bool => str_starts_with($s, 'Poslali smo link'));
        Notification::assertSentToTimes($victim, AccountDeletionNotification::class, 4);

        // Odmah ponovo: pauza, bez maila i bez tvrdnje da je poslato.
        $this->post('/account/deletion', ['deletion_password' => self::PASSWORD])->assertRedirectContains('/account')
            ->assertSessionHas('status', 'Ako je potrebno, link je već poslat u poslednjih 10 minuta; proverite poštu.');
        Notification::assertSentToTimes($victim, AccountDeletionNotification::class, 4);
    }

    public function test_logged_in_request_reports_failed_mail_and_keeps_no_row_or_spent_budget(): void
    {
        $user = $this->user('del-own-smtp', 'user');
        $this->post('/login', ['login' => $user->email, 'password' => self::PASSWORD]);
        $this->assertAuthenticatedAs($user);
        $this->app->instance(\Illuminate\Contracts\Notifications\Dispatcher::class, new class implements \Illuminate\Contracts\Notifications\Dispatcher {
            public function send($notifiables, $notification): void { throw new \RuntimeException('smtp down'); }
            public function sendNow($notifiables, $notification, ?array $channels = null): void { throw new \RuntimeException('smtp down'); }
        });

        $this->post('/account/deletion', ['deletion_password' => self::PASSWORD])->assertRedirectContains('/account')->assertSessionHasErrors('deletion')->assertSessionMissing('status');
        self::assertSame(0, DB::table('account_deletion_requests')->where('user_id', $user->id)->count());

        // SMTP radi ponovo: nema pauze (red je obrisan) i dnevni brojac nije potrosen.
        // instance() iznad je skinuo alias Dispatcher -> ChannelManager, pa fake vezujemo i na ugovor.
        $this->app->instance(\Illuminate\Contracts\Notifications\Dispatcher::class, Notification::fake());
        for ($i = 0; $i < 3; $i++) {
            $this->post('/account/deletion', ['deletion_password' => self::PASSWORD])->assertSessionHasNoErrors()
                ->assertSessionHas('status', static fn (string $s): bool => str_starts_with($s, 'Poslali smo link'));
            $this->travel(11)->minutes();
        }
        Notification::assertSentToTimes($user, AccountDeletionNotification::class, 3);
        self::assertSame('own', DB::table('account_deletion_requests')->where('user_id', $user->id)->value('source'));
    }

    public function test_fresh_pending_request_from_a_parallel_request_is_kept_and_not_duplicated(): void
    {
        Notification::fake();
        $user = $this->user('del-race', 'customer');
        // Simulira drugi zahtjev koji je upravo upisao svoj red (sekvencijalno; pravi paralelizam stiti lockForUpdate na users).
        DB::table('account_deletion_requests')->insert([
            'user_id' => $user->id, 'requested_at' => now(), 'status' => 'pending', 'source' => 'web',
            'confirm_token_hash' => hash('sha256', 'parallel'), 'confirm_expires_at' => now()->addHour(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        app(\App\Services\UserDeletionService::class)->requestByEmail('del-race@example.test');
        self::assertSame('skipped', app(\App\Services\UserDeletionService::class)->requestForUser($user));

        self::assertSame(1, DB::table('account_deletion_requests')->where('user_id', $user->id)->count());
        self::assertSame(hash('sha256', 'parallel'), DB::table('account_deletion_requests')->where('user_id', $user->id)->value('confirm_token_hash'));
        Notification::assertNothingSent();
    }

    public function test_array_in_email_field_is_a_validation_error_not_500(): void
    {
        Notification::fake();
        $this->user('del-array', 'customer');

        $this->post('/brisanje-naloga', ['email' => ['del-array@example.test']])
            ->assertStatus(302)->assertSessionHasErrors('email');
        // Forma poslije redirekta ispisuje old('email'): niz tu ne smije stići (TypeError/500).
        $this->get('/brisanje-naloga')->assertOk()->assertSee('name="email" value=""', false);
        $this->followingRedirects()->post('/brisanje-naloga', ['email' => ['x' => ['y']]])->assertOk();
        Notification::assertNothingSent();
    }

    public function test_request_work_runs_after_the_response_so_known_and_unknown_look_the_same(): void
    {
        Notification::fake();
        $known = $this->user('del-defer', 'customer');
        $before = [];
        // RequestHandled ide kad je odgovor gotov, a prije terminate(): do tada se ne smije desiti nista sto odaje nalog.
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Foundation\Http\Events\RequestHandled::class, static function () use (&$before): void {
            $before[] = DB::table('account_deletion_requests')->count();
        });

        $known1 = $this->post('/brisanje-naloga', ['email' => 'del-defer@example.test']);
        self::assertSame([0], $before);
        self::assertSame(1, DB::table('account_deletion_requests')->count());
        Notification::assertSentToTimes($known, AccountDeletionNotification::class, 1);

        $unknown = $this->post('/brisanje-naloga', ['email' => 'nema-me@example.test']);
        // Posao prethodnog zahtjeva se ne ponavlja u sljedecem.
        Notification::assertSentToTimes($known, AccountDeletionNotification::class, 1);
        self::assertSame($known1->getStatusCode(), $unknown->getStatusCode());
        self::assertSame($known1->headers->get('Location'), $unknown->headers->get('Location'));
        self::assertSame($known1->getContent(), $unknown->getContent());
        self::assertSame($known1->getSession()->get('status'), $unknown->getSession()->get('status'));
    }

    // ---- Anonimizacija: ostaci podataka ----------------------------------------

    public function test_anonymisation_removes_notifications_sessions_and_pii_from_security_events(): void
    {
        $target = $this->user('del-leftovers', 'customer');
        $other = $this->user('del-other', 'customer');
        $this->loginSession($target, '203.0.113.7');
        DB::table('notifications')->insert([
            ['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'x', 'notifiable_type' => (new User())->getMorphClass(), 'notifiable_id' => $target->id,
                'data' => json_encode(['customer' => 'Marko', 'email' => 'marko@example.test']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'x', 'notifiable_type' => (new User())->getMorphClass(), 'notifiable_id' => $other->id,
                'data' => '{}', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('security_events')->insert([
            ['user_id' => $target->id, 'event_type' => 'login.failed', 'severity' => 'warning', 'request_id' => 'req-1', 'route_name' => 'login',
                'ip_address' => '203.0.113.7', 'user_agent' => 'Mozilla', 'context_json' => json_encode(['login' => 'marko@example.test']), 'created_at' => now()],
            ['user_id' => $other->id, 'event_type' => 'login.failed', 'severity' => 'warning', 'request_id' => 'req-2', 'route_name' => 'login',
                'ip_address' => '203.0.113.8', 'user_agent' => 'Mozilla', 'context_json' => json_encode(['login' => 'x']), 'created_at' => now()],
        ]);
        // Neuspjele prijave nemaju user_id; vezane su samo preko login_hash (e-posta ili korisnicko ime).
        $failed = static fn (string $hash, string $ip): array => ['user_id' => null, 'event_type' => 'login.failed', 'severity' => 'warning',
            'request_id' => 'req-'.$ip, 'route_name' => 'login', 'ip_address' => $ip, 'user_agent' => 'Mozilla',
            'context_json' => json_encode(['login_hash' => $hash, 'user_found' => true]), 'created_at' => now()];
        DB::table('security_events')->insert([
            $failed(hash('sha256', 'del-leftovers@example.test'), '203.0.113.21'),
            $failed(hash('sha256', 'del-leftovers'), '203.0.113.22'),
            $failed(hash('sha256', 'del-other@example.test'), '203.0.113.23'),
        ]);

        $method = new \ReflectionMethod(\App\Services\UserDeletionService::class, 'anonymize');
        $method->invoke(app(\App\Services\UserDeletionService::class), (int) $target->id);

        self::assertSame(0, DB::table('notifications')->where('notifiable_id', $target->id)->count());
        self::assertSame(1, DB::table('notifications')->where('notifiable_id', $other->id)->count());
        self::assertSame(0, DB::table('user_login_sessions')->where('user_id', $target->id)->count());
        $event = DB::table('security_events')->where('user_id', $target->id)->first();
        self::assertNotNull($event, 'Dogadjaj ostaje.');
        self::assertSame('login.failed', $event->event_type);
        foreach (['context_json', 'request_id', 'ip_address', 'user_agent'] as $column) {
            self::assertNull($event->{$column}, $column);
        }
        $untouched = DB::table('security_events')->where('user_id', $other->id)->first();
        self::assertNotNull($untouched->context_json);
        self::assertSame('req-2', $untouched->request_id);
        self::assertSame(0, DB::table('security_events')->whereIn('ip_address', ['203.0.113.21', '203.0.113.22'])->count());
        self::assertSame(2, DB::table('security_events')->whereNull('user_id')->where('event_type', 'login.failed')
            ->whereNull('ip_address')->whereNull('context_json')->whereNull('request_id')->count(), 'Dogadjaji ostaju bez licnih podataka.');
        $otherFailed = DB::table('security_events')->where('ip_address', '203.0.113.23')->first();
        self::assertNotNull($otherFailed, 'Tudja neuspjela prijava ostaje.');
        self::assertSame('req-203.0.113.23', $otherFailed->request_id);
        self::assertNotNull($otherFailed->context_json);
        self::assertNotNull(DB::table('users')->where('id', $target->id)->value('password_changed_at'));
    }

    // ---- Konacno brisanje (MySQL) -------------------------------------------

    public function test_completion_anonymises_identity_but_keeps_business_history(): void
    {
        $this->requireMysql();
        Notification::fake();
        $target = $this->user('del-full', 'customer');
        $target->forceFill(['first_name' => 'Marko', 'last_name' => 'Markovic', 'phone' => '0601234567', 'city' => 'Beograd'])->save();
        $order = $this->order($target, 'DEL-1');
        DB::table('order_commissions')->insert([
            'order_id' => $order, 'user_id' => $target->id, 'total_eur' => '20.00', 'status' => 'paid',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $token = $target->createToken('phone', ['*']);
        DB::table('mobile_devices')->insert([
            'user_id' => $target->id, 'personal_access_token_id' => $token->accessToken->id,
            'installation_id' => 'a79c7e9b-d248-4f64-8c80-4cf964be313f', 'platform' => 'android', 'device_name' => 'Markov telefon',
            'push_provider' => 'expo', 'push_token' => 'ExponentPushToken[abc]', 'push_token_hash' => hash('sha256', 'abc'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('mobile_push_outbox')->insert([
            'user_id' => $target->id, 'title' => 'Zdravo Marko', 'message' => 'Porudzbina', 'status' => 'sent',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('user_external_identities')->insert([
            'user_id' => $target->id, 'provider' => 'google', 'provider_subject' => 'sub-123', 'provider_email' => 'marko@gmail.test',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->loginSession($target, '203.0.113.7');
        AuditLog::query()->create([
            'user_id' => $target->id, 'action' => 'account.profile_updated', 'level' => 'info', 'subject' => 'Profil',
            'auditable_type' => (new User())->getMorphClass(), 'auditable_id' => $target->id,
            'before_json' => ['phone' => '0601234567'], 'after_json' => ['phone' => '0609999999'],
            'ip_address' => '203.0.113.7', 'user_agent' => 'Mozilla', 'created_at' => now(),
        ]);
        // Radnja koju je nalog izvrsio nad poslovnim podatkom: istorija ostaje, brise se samo IP.
        AuditLog::query()->create([
            'user_id' => $target->id, 'action' => 'order.status_changed', 'level' => 'info', 'subject' => 'Porudzbina',
            'auditable_type' => 'order', 'auditable_id' => $order,
            'before_json' => ['status' => 'new'], 'after_json' => ['status' => 'done'],
            'ip_address' => '203.0.113.7', 'user_agent' => 'Mozilla', 'created_at' => now(),
        ]);
        $oldHash = (string) $target->password_hash;

        app(\App\Services\UserDeletionService::class)->startForVerifiedUser($target);
        $this->processDeletions();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'username' => 'del-full']); // rok mirovanja jos traje

        DB::table('account_deletion_requests')->where('user_id', $target->id)->update(['grace_until' => now()->subMinute()]);
        $this->processDeletions();

        $fresh = DB::table('users')->where('id', $target->id)->first();
        self::assertSame('deleted_'.$target->id, $fresh->username);
        self::assertSame('deleted_'.$target->id.'@invalid.local', $fresh->email);
        self::assertSame('Obrisan', $fresh->first_name);
        foreach (['last_name', 'phone', 'city', 'address', 'postal_code'] as $column) {
            self::assertNull($fresh->{$column}, $column);
        }
        self::assertSame('blocked', $fresh->status);
        self::assertNotNull($fresh->retired_at);
        self::assertNotSame($oldHash, $fresh->password_hash);
        foreach (['user_external_identities', 'mobile_devices', 'mobile_push_outbox'] as $table) {
            self::assertSame(0, DB::table($table)->where('user_id', $target->id)->count(), $table);
        }
        self::assertSame(0, $target->tokens()->count());
        self::assertSame(0, DB::table('user_login_sessions')->where('user_id', $target->id)->count());
        $profileAudit = AuditLog::query()->where('action', 'account.profile_updated')->firstOrFail();
        self::assertNull($profileAudit->ip_address);
        self::assertNull($profileAudit->before_json);
        self::assertNull($profileAudit->after_json);
        self::assertSame(0, AuditLog::query()->where('ip_address', '203.0.113.7')->count());
        $business = AuditLog::query()->where('action', 'order.status_changed')->firstOrFail();
        self::assertSame(['status' => 'new'], $business->before_json);
        self::assertSame(['status' => 'done'], $business->after_json);

        // Poslovna istorija ostaje na anonimizovanom korisniku.
        $this->assertDatabaseHas('orders', ['id' => $order, 'user_id' => $target->id]);
        $this->assertDatabaseHas('order_commissions', ['order_id' => $order, 'user_id' => $target->id, 'total_eur' => '20.00']);
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $target->id, 'status' => 'completed']);
        foreach (['account.deletion_requested', 'account.deletion_completed'] as $action) {
            $audit = AuditLog::query()->where('action', $action)->firstOrFail();
            self::assertNull($audit->ip_address, $action);
            self::assertStringNotContainsString('del-full', json_encode($audit->toArray(), JSON_THROW_ON_ERROR));
        }
    }

    public function test_open_commission_keeps_request_waiting_and_owner_is_told_once(): void
    {
        $this->requireMysql();
        Notification::fake();
        $target = $this->user('del-wait', 'customer');
        $owner = $this->user('del-owner', 'superadmin');
        $order = $this->order($target, 'DEL-W');
        $commissionId = DB::table('order_commissions')->insertGetId([
            'order_id' => $order, 'user_id' => $target->id, 'total_eur' => '50.00', 'status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        app(\App\Services\UserDeletionService::class)->startForVerifiedUser($target);
        DB::table('account_deletion_requests')->where('user_id', $target->id)->update(['grace_until' => now()->subMinute()]);

        $this->processDeletions();
        $this->processDeletions();

        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $target->id, 'status' => 'waiting_obligations']);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'username' => 'del-wait', 'status' => 'blocked']);
        Notification::assertSentToTimes($target, AccountDeletionNotification::class, 2); // zakazano + jedno "ceka"
        Notification::assertSentTo($target, AccountDeletionNotification::class, static fn ($n): bool => $n->kind === 'waiting');
        Notification::assertSentToTimes($owner, AccountDeletionNotification::class, 1);
        Notification::assertSentTo($owner, AccountDeletionNotification::class, static fn ($n): bool => $n->kind === 'owner_waiting' && $n->subjectUserId === $target->id);

        // Neisplacena potvrdjena zaduzenja takodje blokiraju.
        DB::table('order_commissions')->where('id', $commissionId)->update(['status' => 'paid']);
        $adjustment = DB::table('commission_adjustments')->insertGetId([
            'commission_id' => $commissionId, 'order_id' => $order, 'kind' => 'debit', 'status' => 'confirmed',
            'amount_eur' => '10.00', 'reason' => 'povrat', 'source' => 'test', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->processDeletions();
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $target->id, 'status' => 'waiting_obligations']);

        DB::table('commission_debit_settlements')->insert([
            'adjustment_id' => $adjustment, 'batch_id' => 1, 'amount_eur' => '10.00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->processDeletions();
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $target->id, 'status' => 'completed']);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'username' => 'deleted_'.$target->id]);
    }

    public function test_cancelled_request_is_never_processed_and_grace_period_is_respected(): void
    {
        $this->requireMysql();
        Notification::fake();
        $early = $this->user('del-early', 'customer');
        $cancelled = $this->user('del-cancelled', 'customer');
        $service = app(\App\Services\UserDeletionService::class);
        $service->startForVerifiedUser($early);
        $service->startForVerifiedUser($cancelled);
        self::assertTrue($service->cancelForUser($cancelled));
        $this->assertDatabaseHas('users', ['id' => $cancelled->id, 'status' => 'active']);
        DB::table('account_deletion_requests')->where('user_id', $cancelled->id)->update(['grace_until' => now()->subDay()]);

        $this->processDeletions();

        $this->assertDatabaseHas('users', ['id' => $early->id, 'username' => 'del-early']);
        $this->assertDatabaseHas('users', ['id' => $cancelled->id, 'username' => 'del-cancelled', 'status' => 'active']);
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $early->id, 'status' => 'pending']);
    }

    public function test_superadmin_deletion_hands_orders_to_successor_and_waits_when_successor_is_gone(): void
    {
        $this->requireMysql();
        Notification::fake();
        $leaving = $this->user('del-leaving', 'superadmin');
        $successor = $this->user('del-successor', 'superadmin');
        $order = $this->order($leaving, 'DEL-S');
        app(\App\Services\UserDeletionService::class)->startForVerifiedUser($leaving);
        DB::table('account_deletion_requests')->where('user_id', $leaving->id)->update(['grace_until' => now()->subMinute()]);

        // Nasljednik nestaje tokom roka: brisanje ceka, nista se ne prenosi.
        $successor->forceFill(['status' => 'blocked'])->save();
        $this->processDeletions();
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $leaving->id, 'status' => 'waiting_obligations']);
        $this->assertDatabaseHas('orders', ['id' => $order, 'user_id' => $leaving->id]);

        $successor->forceFill(['status' => 'active'])->save();
        $this->processDeletions();

        $this->assertDatabaseHas('orders', ['id' => $order, 'user_id' => $successor->id]);
        $this->assertDatabaseHas('users', ['id' => $leaving->id, 'username' => 'deleted_'.$leaving->id]);
        $this->assertDatabaseHas('portal_order_link_history', ['order_id' => $order, 'from_user_id' => $leaving->id, 'to_user_id' => $successor->id]);
        $this->assertDatabaseHas('users', ['id' => $successor->id, 'username' => 'del-successor', 'status' => 'active']);
    }

    // ---- Administrator, povučen nalog, mejl, Google, kolizija ----------------------

    public function test_admin_unblock_during_grace_cancels_request_and_nothing_is_anonymised(): void
    {
        Notification::fake();
        $actor = $this->user('del-actor', 'superadmin');
        $target = $this->user('del-unblocked', 'user');
        $service = app(\App\Services\UserDeletionService::class);
        $service->startForVerifiedUser($target);
        $cancelToken = $this->capturedToken($target, 'scheduled');

        $this->adminSetStatus($target, 'active', $actor);

        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $target->id, 'status' => 'cancelled']);
        DB::table('account_deletion_requests')->where('user_id', $target->id)->update(['grace_until' => now()->subDay()]);
        $this->processDeletions();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'username' => 'del-unblocked', 'status' => 'active']);

        // Poslije administratorske blokade link iz e-pošte ne može da skine blokadu.
        $this->adminSetStatus($target, 'blocked', $actor);
        self::assertSame('invalid', $service->cancel($cancelToken));
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'blocked']);
    }

    public function test_cancel_link_does_not_lift_a_block_the_admin_took_over(): void
    {
        Notification::fake();
        $target = $this->user('del-admin-block', 'user');
        app(\App\Services\UserDeletionService::class)->startForVerifiedUser($target);
        $cancel = $this->capturedToken($target, 'scheduled');
        DB::table('account_deletion_requests')->where('user_id', $target->id)->update(['blocked_by_request' => false]);

        $this->post('/brisanje-naloga/ponisti', ['token' => $cancel])->assertOk()->assertSee('administrator', false);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'blocked']);
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $target->id, 'status' => 'cancelled']);
    }

    public function test_any_exit_from_blocked_closes_the_request(): void
    {
        Notification::fake();
        $actor = $this->user('del-actor3', 'superadmin');
        $target = $this->user('del-pending-exit', 'user');
        app(\App\Services\UserDeletionService::class)->startForVerifiedUser($target);

        $this->adminSetStatus($target, 'pending', $actor);

        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $target->id, 'status' => 'cancelled']);
        DB::table('account_deletion_requests')->where('user_id', $target->id)->update(['grace_until' => now()->subDay()]);
        $this->processDeletions();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'username' => 'del-pending-exit', 'status' => 'pending']);
    }

    public function test_reserved_prefix_applies_to_new_usernames_but_not_to_editing_an_existing_deleted_user(): void
    {
        $actor = $this->user('del-actor4', 'superadmin');
        $existing = $this->user('deleted_999', 'user', 'blocked');
        $roleId = (int) $existing->role_id;
        $payload = ['role_id' => $roleId, 'user_group_id' => 1, 'email' => $existing->email, 'status' => 'active'];

        $this->actingAs($actor)->put('/admin/users/'.$existing->id, $payload + ['username' => 'deleted_999'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $existing->id, 'status' => 'active']);

        $this->actingAs($actor)->put('/admin/users/'.$existing->id, $payload + ['username' => 'deleted_1000'])->assertSessionHasErrors('username');
        $this->actingAs($actor)->post('/admin/users', $payload + ['username' => 'deleted_5', 'email' => 'novi@example.test', 'password' => 'Secret123!Secret'])->assertSessionHasErrors('username');
    }

    public function test_completion_never_anonymises_an_account_that_is_no_longer_blocked(): void
    {
        Notification::fake();
        $target = $this->user('del-restored', 'user');
        app(\App\Services\UserDeletionService::class)->startForVerifiedUser($target);
        // Status vraćen mimo AdminUserService (direktna izmjena): sigurnosna mreža u complete().
        DB::table('users')->where('id', $target->id)->update(['status' => 'active']);
        DB::table('account_deletion_requests')->where('user_id', $target->id)->update(['grace_until' => now()->subMinute()]);

        $this->processDeletions();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'username' => 'del-restored', 'status' => 'active']);
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $target->id, 'status' => 'cancelled']);
        self::assertSame(1, AuditLog::query()->where('action', 'account.deletion_cancelled')->count());
    }

    public function test_waiting_request_is_closed_by_admin_unblock(): void
    {
        $this->requireMysql();
        Notification::fake();
        $actor = $this->user('del-actor2', 'superadmin');
        $target = $this->user('del-waiting-unblock', 'user');
        $order = $this->order($target, 'DEL-U');
        DB::table('order_commissions')->insert([
            'order_id' => $order, 'user_id' => $target->id, 'total_eur' => '5.00', 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        app(\App\Services\UserDeletionService::class)->startForVerifiedUser($target);
        DB::table('account_deletion_requests')->where('user_id', $target->id)->update(['grace_until' => now()->subMinute()]);
        $this->processDeletions();
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $target->id, 'status' => 'waiting_obligations']);

        $this->adminSetStatus($target, 'active', $actor);

        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $target->id, 'status' => 'cancelled']);
        $this->processDeletions();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'username' => 'del-waiting-unblock']);
    }

    public function test_account_retired_the_ordinary_way_during_grace_is_still_anonymised(): void
    {
        Notification::fake();
        $target = $this->user('del-retired', 'user');
        app(\App\Services\UserDeletionService::class)->startForVerifiedUser($target);
        DB::table('users')->where('id', $target->id)->update(['retired_at' => now()]);
        DB::table('account_deletion_requests')->where('user_id', $target->id)->update(['grace_until' => now()->subMinute()]);

        $this->processDeletions();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'username' => 'deleted_'.$target->id, 'email' => 'deleted_'.$target->id.'@invalid.local']);
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $target->id, 'status' => 'completed']);
    }

    public function test_reserved_username_taken_by_someone_else_does_not_block_completion(): void
    {
        $this->requireMysql();
        Notification::fake();
        $target = $this->user('del-collide', 'user');
        $this->user('deleted_'.$target->id, 'user');
        app(\App\Services\UserDeletionService::class)->startForVerifiedUser($target);
        DB::table('account_deletion_requests')->where('user_id', $target->id)->update(['grace_until' => now()->subMinute()]);

        $this->processDeletions();

        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $target->id, 'status' => 'completed']);
        self::assertStringStartsWith('deleted_'.$target->id.'_', (string) DB::table('users')->where('id', $target->id)->value('username'));
    }

    public function test_google_identity_must_be_linked_and_fresh(): void
    {
        $user = $this->user('del-google', 'user');
        $other = $this->user('del-google-other', 'user');
        DB::table('user_external_identities')->insert([
            ['user_id' => $user->id, 'provider' => 'google', 'provider_subject' => 'sub-mine', 'provider_email' => 'a@gmail.test', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $other->id, 'provider' => 'google', 'provider_subject' => 'sub-other', 'provider_email' => 'b@gmail.test', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $service = app(\App\Services\UserDeletionService::class);

        $service->assertGoogleIdentity($user, ['sub' => 'sub-mine', 'iat' => time() - 30]);
        $this->addToAssertionCount(1);

        foreach ([
            'tudji sub' => ['sub' => 'sub-other', 'iat' => time()],
            'nepoznat sub' => ['sub' => 'sub-none', 'iat' => time()],
            'star token' => ['sub' => 'sub-mine', 'iat' => time() - 301],
            'bez iat' => ['sub' => 'sub-mine'],
        ] as $label => $identity) {
            try {
                $service->assertGoogleIdentity($user, $identity);
                self::fail('Treba odbiti: '.$label);
            } catch (\Illuminate\Validation\ValidationException $e) {
                self::assertArrayHasKey('id_token', $e->errors(), $label);
            }
        }
    }

    public function test_request_is_rolled_back_when_the_cancel_link_cannot_be_mailed(): void
    {
        $user = $this->user('del-nomail', 'user');
        $user->createToken('phone', ['*']);
        $this->app->instance(\Illuminate\Contracts\Notifications\Dispatcher::class, new class implements \Illuminate\Contracts\Notifications\Dispatcher {
            public function send($notifiables, $notification): void { throw new \RuntimeException('smtp down'); }
            public function sendNow($notifiables, $notification, ?array $channels = null): void { throw new \RuntimeException('smtp down'); }
        });
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/account/deletion', ['password' => self::PASSWORD])->assertStatus(503);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'active']);
        self::assertSame(0, DB::table('account_deletion_requests')->count());
        self::assertSame(1, $user->tokens()->count());
        self::assertSame(0, AuditLog::query()->where('action', 'account.deletion_requested')->count());
    }

    public function test_confirm_page_says_unavailable_not_invalid_when_mail_fails(): void
    {
        Notification::fake();
        $user = $this->user('del-confirm-mail', 'user');
        $this->post('/brisanje-naloga', ['email' => 'del-confirm-mail@example.test']);
        $confirm = $this->capturedToken($user, 'confirm');
        $this->app->instance(\Illuminate\Contracts\Notifications\Dispatcher::class, new class implements \Illuminate\Contracts\Notifications\Dispatcher {
            public function send($notifiables, $notification): void { throw new \RuntimeException('smtp down'); }
            public function sendNow($notifiables, $notification, ?array $channels = null): void { throw new \RuntimeException('smtp down'); }
        });

        $this->post('/brisanje-naloga/potvrda', ['token' => $confirm])
            ->assertOk()->assertSee('nije moguće', false)->assertDontSee('Link nije važeći', false);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'active']);
    }

    // ---- pomocno -----------------------------------------------------------

    private function adminSetStatus(User $target, string $status, User $actor): void
    {
        app(\App\Services\AdminUserService::class)->update($target, [
            'role_id' => $target->role_id, 'user_group_id' => $target->user_group_id,
            'username' => $target->username, 'email' => $target->email, 'status' => $status,
        ], $actor);
    }


    private function processDeletions(): void
    {
        $code = \Illuminate\Support\Facades\Artisan::call('app:account-deletions-process');
        self::assertSame(0, $code, \Illuminate\Support\Facades\Artisan::output());
    }

    private function capturedToken(User $user, string $kind): string
    {
        $token = null;
        Notification::assertSentTo($user, AccountDeletionNotification::class, static function ($n) use (&$token, $kind): bool {
            if ($n->kind !== $kind) {
                return false;
            }
            $token = $n->token;

            return true;
        });

        return (string) $token;
    }

    private function linkGoogle(User $user, string $sub): void
    {
        DB::table('user_external_identities')->insert([
            'user_id' => $user->id, 'provider' => 'google', 'provider_subject' => $sub,
            'provider_email' => $sub.'@gmail.test', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function loginSession(User $user, ?string $ip = null): void
    {
        DB::table('user_login_sessions')->insert([
            'user_id' => $user->id, 'session_hash' => hash('sha256', 'sess-'.$user->id),
            'ip_address' => $ip, 'user_agent' => $ip ? 'Mozilla' : null, 'device_label' => $ip ? 'Chrome' : null,
            'logged_in_at' => now(), 'last_seen_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function user(string $id, string $role = 'user', string $status = 'active'): User
    {
        return User::query()->create([
            'role_id' => DB::table('roles')->where('slug', $role)->value('id'),
            'user_group_id' => 1,
            'username' => $id,
            'email' => $id.'@example.test',
            'password_hash' => Hash::make(self::PASSWORD),
            'status' => $status,
        ]);
    }

    private function order(User $buyer, string $number): int
    {
        return (int) DB::table('orders')->insertGetId([
            'order_number' => 'DEL-'.$number,
            'user_id' => $buyer->id,
            'shipping_full_name' => 'QA Kupac',
            'shipping_address' => 'QA ulica 1',
            'shipping_city' => 'QA Grad',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '0601234567',
            'subtotal_rsd' => '1234.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
