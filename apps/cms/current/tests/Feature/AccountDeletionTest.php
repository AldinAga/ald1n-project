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
        $this->post('/account/deletion')->assertRedirectContains('/account');
        Notification::assertSentTo($user, AccountDeletionNotification::class, static fn ($n): bool => $n->kind === 'confirm');
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
        $session = DB::table('user_login_sessions')->where('user_id', $target->id)->first();
        self::assertNull($session->ip_address);
        self::assertNull($session->device_label);
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
        self::assertFalse($service->cancel($cancelToken));
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'blocked']);
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
