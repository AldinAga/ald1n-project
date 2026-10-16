<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\AdminUserService;
use App\Services\UserRetirementService;
use App\Services\UserRetirementRelationPolicy;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Executed against a dedicated disposable MySQL CI service only.
 * Never use the production connection or migrate the CloudLinux live database.
 */
final class UserRetirementMySqlIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('MySQL-only release gate; isolated test database required.');
        }
        $db = (string) DB::getDatabaseName();
        self::assertSame('ald1n_retirement_ci', $db, 'Never execute retirement integration tests outside the disposable CI database.');
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_transfers_orders_preserves_financial_attribution_and_records_owner_history(): void
    {
        $actor = $this->user('transfer-admin', 'superadmin');
        $target = $this->user('transfer-target');
        $seller = $this->user('transfer-seller');
        $first = $this->order($target, 'TR-1');
        $second = $this->order($target, 'TR-2');
        DB::table('orders')->where('id', $second)->update(['archived_at' => now(), 'archived_by' => $target->id]);

        DB::table('order_commissions')->insert([
            'order_id' => $first,
            'user_id' => $seller->id,
            'total_eur' => '50.00',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productId = $this->product($target, 'TR-P');
        $token = $target->createToken('android-test', ['*']);
        $sessionId = DB::table('user_login_sessions')->insertGetId([
            'user_id' => $target->id, 'session_hash' => hash('sha256', 'retirement-session'),
            'logged_in_at' => now(), 'last_seen_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $deviceId = DB::table('mobile_devices')->insertGetId([
            'user_id' => $target->id,
            'personal_access_token_id' => $token->accessToken->id,
            'installation_id' => 'a79c7e9b-d248-4f64-8c80-4cf964be313f',
            'platform' => 'android', 'push_provider' => 'expo',
            'push_token' => 'test-push-only', 'push_token_hash' => hash('sha256', 'test-push-only'),
            'notifications_enabled' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $preview = app(UserRetirementService::class)->impact($target, $actor);
        self::assertSame(2, $preview['orders']);
        self::assertSame(0, $preview['commissions']);
        $result = app(UserRetirementService::class)->retire($target, $actor);
        self::assertSame(2, $result['orders']);
        self::assertSame((int) $actor->id, $result['recipient_id']);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'blocked']);
        self::assertNotNull($target->fresh()?->retired_at);
        self::assertSame(2, DB::table('orders')->where('user_id', $actor->id)->whereIn('id', [$first, $second])->count());
        self::assertSame(0, DB::table('orders')->where('user_id', $target->id)->count());
        $this->assertDatabaseHas('orders', ['id' => $second, 'user_id' => $actor->id, 'archived_by' => $target->id]);
        self::assertSame('50.00', (string) DB::table('order_commissions')->where('order_id', $first)->value('total_eur'));
        self::assertSame((int) $seller->id, (int) DB::table('order_commissions')->where('order_id', $first)->value('user_id'));
        $this->assertDatabaseHas('products', ['id' => $productId, 'created_by' => $target->id]);
        self::assertSame(2, DB::table('portal_order_link_history')
            ->where('from_user_id', $target->id)->where('to_user_id', $actor->id)
            ->where('changed_by', $actor->id)->whereIn('order_id', [$first, $second])->count());
        self::assertSame(0, $target->tokens()->count());
        $this->assertDatabaseHas('user_login_sessions', [
            'id' => $sessionId, 'user_id' => $target->id, 'revoked_by' => $actor->id,
        ]);
        self::assertNotNull(DB::table('user_login_sessions')->where('id', $sessionId)->value('revoked_at'));
        $this->assertDatabaseHas('mobile_devices', [
            'id' => $deviceId, 'user_id' => $target->id, 'notifications_enabled' => 0,
            'push_token' => null, 'push_token_hash' => null,
        ]);
        self::assertNotNull(DB::table('mobile_devices')->where('id', $deviceId)->value('revoked_at'));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $actor->id, 'auditable_id' => $target->id, 'action' => 'user.retired']);
    }

    public function test_duplicate_retirement_cannot_transfer_orders_or_append_history_twice(): void
    {
        $actor = $this->user('repeat-admin', 'superadmin');
        $target = $this->user('repeat-target');
        $order = $this->order($target, 'RP-1');
        app(UserRetirementService::class)->retire($target, $actor);
        try {
            app(UserRetirementService::class)->retire($target, $actor);
            self::fail('A repeated retirement must fail closed.');
        } catch (HttpException $e) {
            self::assertSame(422, $e->getStatusCode());
        }
        self::assertSame(1, DB::table('portal_order_link_history')->where('order_id', $order)->count());
        self::assertSame(1, DB::table('audit_logs')->where('action', 'user.retired')->count());
    }

    public function test_mysql_unique_idempotency_collision_rolls_back_order_history_and_status(): void
    {
        $actor = $this->user('collision-admin', 'superadmin');
        $target = $this->user('collision-target');
        $keyHash = hash('sha256', 'same-key');
        $targetOrder = $this->order($target, 'CO-1', $keyHash);
        $this->order($actor, 'CO-2', $keyHash);
        $token = $target->createToken('before-collision', ['*']);

        try {
            app(UserRetirementService::class)->retire($target, $actor);
            self::fail('Unique (user_id, idempotency_key_hash) collision must abort.');
        } catch (HttpException $e) {
            self::assertSame(409, $e->getStatusCode());
        }

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'active', 'retired_at' => null]);
        $this->assertDatabaseHas('orders', ['id' => $targetOrder, 'user_id' => $target->id]);
        self::assertSame(0, DB::table('portal_order_link_history')->count());
        self::assertSame(1, $target->tokens()->count());
        self::assertNotNull($token->accessToken->fresh());
        self::assertSame(0, DB::table('audit_logs')->where('action', 'user.retired')->count());
    }

    public function test_commission_ownership_remains_immutable_after_retirement(): void
    {
        $actor = $this->user('commission-admin', 'superadmin');
        $target = $this->user('commission-target');
        $other = $this->user('commission-buyer');
        $order = $this->order($other, 'CP-1');
        DB::table('order_commissions')->insert([
            'order_id' => $order, 'user_id' => $target->id,
            'total_eur' => '73.00', 'status' => 'paid',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        self::assertSame(1, app(UserRetirementService::class)->impact($target, $actor)['commissions']);
        app(UserRetirementService::class)->retire($target, $actor);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'blocked']);
        self::assertNotNull($target->fresh()?->retired_at);
        $this->assertDatabaseHas('order_commissions', ['user_id' => $target->id, 'order_id' => $order, 'status' => 'paid']);
        self::assertSame(0, DB::table('portal_order_link_history')->count());
    }

    public function test_customer_conversation_and_historical_assignments_survive_retirement(): void
    {
        $actor = $this->user('linked-admin', 'superadmin');
        $target = $this->user('linked-target');
        $buyer = $this->user('linked-buyer');
        $order = $this->order($buyer, 'LINK-1');
        $thread = DB::table('portal_conversations')->insertGetId([
            'user_id' => $target->id, 'order_id' => $order,
            'subject' => 'Povrat proizvoda',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $assignment = DB::table('order_assignments')->insertGetId([
            'order_id' => $order,
            'new_supplier_user_id' => $target->id,
            'changed_by' => $actor->id,
            'reason' => 'Originalni dobavljač',
        ]);
        $impact = app(UserRetirementService::class)->impact($target, $actor);
        self::assertSame(1, $impact['conversations']);
        self::assertSame(1, $impact['assignments']);
        app(UserRetirementService::class)->retire($target, $actor);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'blocked']);
        $this->assertDatabaseHas('portal_conversations', ['id' => $thread, 'user_id' => $target->id]);
        $this->assertDatabaseHas('order_assignments', ['id' => $assignment, 'new_supplier_user_id' => $target->id]);
    }

    public function test_superadmin_only_no_self_retirement_and_no_preblocked_reuse(): void
    {
        $actor = $this->user('protect-root', 'superadmin');
        $secondRoot = $this->user('protect-root-2', 'superadmin');
        $normalAdmin = $this->user('protect-admin', 'admin');
        $target = $this->user('protect-target');
        $blocked = $this->user('protect-blocked', 'user', 'blocked');
        $this->assertRetireStatus(422, $actor, $actor);
        $this->assertRetireStatus(422, $secondRoot, $actor);
        $this->assertRetireStatus(403, $target, $normalAdmin);
        $this->assertRetireStatus(422, $blocked, $actor);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'active']);
        $this->assertDatabaseHas('users', ['id' => $secondRoot->id, 'status' => 'active']);
    }

    public function test_retired_account_cannot_be_reactivated_through_admin_user_manager(): void
    {
        $actor = $this->user('reactivation-root', 'superadmin');
        $target = $this->user('reactivation-target');
        app(UserRetirementService::class)->retire($target, $actor);
        try {
            app(AdminUserService::class)->update($target, [
                'role_id' => $target->role_id, 'user_group_id' => $target->user_group_id,
                'status' => 'active',
            ], $actor);
            self::fail('Retired identity must be immutable.');
        } catch (HttpException $e) {
            self::assertSame(422, $e->getStatusCode());
        }
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'blocked']);
    }

    public function test_api_preview_and_confirmation_require_superadmin_and_exact_phrase(): void
    {
        $actor = $this->user('api-root', 'superadmin');
        $target = $this->user('api-target');
        $admin = $this->user('api-admin', 'admin');
        Sanctum::actingAs($admin, ['*']);
        $this->getJson('/api/v1/admin/users/'.$target->id.'/retirement')->assertForbidden();
        $this->app['auth']->forgetGuards();

        Sanctum::actingAs($actor, ['*']);
        $this->getJson('/api/v1/admin/users/'.$target->id.'/retirement')
            ->assertOk()
            ->assertJsonPath('target_user_id', $target->id)
            ->assertJsonPath('data.orders', 0);
        $this->postJson('/api/v1/admin/users/'.$target->id.'/retirement', ['confirmation' => 'obrisi'])
            ->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'active']);
        $this->postJson('/api/v1/admin/users/'.$target->id.'/retirement', ['confirmation' => 'OBRISI'])
            ->assertOk()
            ->assertJsonPath('data.mode', 'non_destructive_retirement');
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'blocked']);
    }

    public function test_auth_token_is_invalid_after_retirement(): void
    {
        $actor = $this->user('token-root', 'superadmin');
        $target = $this->user('token-target');
        $token = $target->createToken('android-retired', ['*'])->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/me')->assertOk();
        app(UserRetirementService::class)->retire($target, $actor);
        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_cascade_foreign_keys_and_unconstrained_actor_ids_are_preserved(): void
    {
        $actor = $this->user('fk-root', 'superadmin');
        $target = $this->user('fk-target');
        $product = $this->product($target, 'FK-P');
        $note = DB::table('customer_crm_notes')->insertGetId([
            'user_id' => $target->id, 'author_user_id' => $actor->id,
            'body' => 'Originalna interna CRM evidencija',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // user_groups.created_by has no FK and must not be normalized to a new actor.
        DB::table('user_groups')->where('id', 1)->update(['created_by' => $target->id]);

        app(UserRetirementService::class)->retire($target, $actor);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'blocked']);
        $this->assertDatabaseHas('products', ['id' => $product, 'created_by' => $target->id]);
        $this->assertDatabaseHas('customer_crm_notes', [
            'id' => $note, 'user_id' => $target->id, 'author_user_id' => $actor->id,
        ]);
        $this->assertDatabaseHas('user_groups', ['id' => 1, 'created_by' => $target->id]);
    }

    public function test_warranty_with_cascade_fk_remains_owned_by_retired_customer(): void
    {
        $actor = $this->user('warranty-root', 'superadmin');
        $target = $this->user('warranty-target');
        $buyer = $this->user('warranty-buyer');
        $product = $this->product($actor, 'WR-P');
        $order = $this->order($buyer, 'WR-1');
        $item = DB::table('order_items')->insertGetId([
            'order_id' => $order, 'product_id' => $product,
            'product_sku' => 'WR-P', 'product_name' => 'QA garantovani artikal',
            'quantity' => 1, 'unit_price_original' => '1000.00',
            'original_currency' => 'RSD', 'unit_price_rsd' => '1000.00',
            'line_total_rsd' => '1000.00',
        ]);
        $warranty = DB::table('product_warranties')->insertGetId([
            'warranty_number' => 'QA-WR-001', 'order_id' => $order,
            'order_item_id' => $item, 'product_id' => $product,
            'user_id' => $target->id, 'starts_at' => '2026-10-01',
            'expires_at' => '2028-10-01', 'duration_months' => 24,
            'customer_name_snapshot' => 'QA Kupac',
            'product_name_snapshot' => 'QA garantovani artikal',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        self::assertSame(1, app(UserRetirementService::class)->impact($target, $actor)['warranties']);
        app(UserRetirementService::class)->retire($target, $actor);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'blocked']);
        self::assertNotNull($target->fresh()?->retired_at);
        $this->assertDatabaseHas('product_warranties', [
            'id' => $warranty, 'user_id' => $target->id, 'status' => 'active',
        ]);
        $this->assertDatabaseHas('order_items', ['id' => $item, 'order_id' => $order]);
    }

    public function test_mysql_fk_inventory_keeps_business_references_even_when_cascade_is_declared(): void
    {
        $keys = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('REFERENCED_TABLE_NAME', 'users')
            ->where('REFERENCED_COLUMN_NAME', 'id')
            ->get(['TABLE_NAME', 'COLUMN_NAME'])
            ->map(static fn ($key): string => $key->TABLE_NAME.'.'.$key->COLUMN_NAME)
            ->all();
        foreach ([
            'orders.user_id',
            'order_commissions.user_id',
            'product_warranties.user_id',
            'portal_conversations.user_id',
            'customer_crm_notes.user_id',
            'order_assignments.new_supplier_user_id',
            'products.created_by',
            'audit_logs.user_id',
            'mobile_devices.user_id',
            'user_login_sessions.user_id',
        ] as $required) {
            self::assertContains($required, $keys, 'Schema FK inventory must retain '.$required);
        }
        self::assertCount(121, $keys, 'Must classify all migrated 121 user-FK references.');
        $policy = app(UserRetirementRelationPolicy::class);
        $report = $policy->verifySchema();
        self::assertSame(121, $report['foreign_keys']);
        self::assertSame(7, $report['unconstrained_history']);
        self::assertCount(121, UserRetirementRelationPolicy::USER_FKS);
    }

    public function test_all_five_active_work_queues_reassign_and_terminal_history_is_preserved(): void
    {
        $actor = $this->user('work-root', 'superadmin');
        $target = $this->user('work-old-assignee', 'admin');
        $buyer = $this->user('work-buyer');
        $openOrder = $this->order($buyer, 'ACTIVE-WORK-1');
        $finishedOrder = $this->order($buyer, 'FINISHED-WORK-1');
        $receivableOpenOrder = $this->order($buyer, 'RECEIVABLE-WORK-1');
        $receivableClosedOrder = $this->order($buyer, 'RECEIVABLE-WORK-2');
        DB::table('orders')->where('id', $openOrder)->update([
            'source_system' => 'laravel',
            'supplier_user_id' => $target->id,
            'supplier_name_snapshot' => 'Originalni dobavljač',
            'accepted_by' => $target->id, 'accepted_at' => now(),
        ]);
        DB::table('orders')->where('id', $finishedOrder)->update([
            'status' => 'shipped', 'completed_at' => now(), 'supplier_user_id' => $target->id,
        ]);
        $openCase = DB::table('after_sales_cases')->insertGetId([
            'case_number' => 'QA-RCASE-1', 'order_id' => $openOrder,
            'opened_by' => $buyer->id, 'assigned_to' => $target->id,
            'case_type' => 'complaint', 'status' => 'open',
            'subject' => 'Reklamacija za prenos', 'description' => 'QA opis',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $closedCase = DB::table('after_sales_cases')->insertGetId([
            'case_number' => 'QA-RCASE-2', 'order_id' => $finishedOrder,
            'opened_by' => $buyer->id, 'assigned_to' => $target->id,
            'case_type' => 'complaint', 'status' => 'closed',
            'subject' => 'Zatvorena reklamacija', 'description' => 'QA opis',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $openAction = DB::table('after_sales_actions')->insertGetId([
            'action_number' => 'QA-RACTION-1', 'after_sales_case_id' => $openCase,
            'action_type' => 'repair', 'status' => 'planned',
            'assigned_to' => $target->id, 'created_by' => $buyer->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $doneAction = DB::table('after_sales_actions')->insertGetId([
            'action_number' => 'QA-RACTION-2', 'after_sales_case_id' => $closedCase,
            'action_type' => 'repair', 'status' => 'completed',
            'assigned_to' => $target->id, 'created_by' => $buyer->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $openConversation = DB::table('portal_conversations')->insertGetId([
            'user_id' => $buyer->id, 'order_id' => $openOrder,
            'assigned_to' => $target->id, 'status' => 'waiting_staff',
            'subject' => 'Aktivna poruka',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $closedConversation = DB::table('portal_conversations')->insertGetId([
            'user_id' => $buyer->id, 'order_id' => $finishedOrder,
            'assigned_to' => $target->id, 'status' => 'closed',
            'subject' => 'Zatvorena poruka',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $openReceivable = DB::table('receivable_cases')->insertGetId([
            'case_number' => 'QA-RREC-1', 'order_id' => $receivableOpenOrder,
            'status' => 'monitoring', 'assigned_to' => $target->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $closedReceivable = DB::table('receivable_cases')->insertGetId([
            'case_number' => 'QA-RREC-2', 'order_id' => $receivableClosedOrder,
            'status' => 'closed', 'assigned_to' => $target->id,
            'closed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $impact = app(UserRetirementService::class)->impact($target, $actor);
        foreach (['supplier_orders', 'after_sales_cases', 'after_sales_actions', 'portal_assignments', 'receivable_cases'] as $work) {
            self::assertSame(1, $impact[$work], $work);
        }
        $result = app(UserRetirementService::class)->retire($target, $actor);
        foreach (['supplier_orders', 'after_sales_cases', 'after_sales_actions', 'portal_assignments', 'receivable_cases'] as $work) {
            self::assertSame(1, $result[$work], $work);
        }
        $this->assertDatabaseHas('orders', [
            'id' => $openOrder, 'supplier_user_id' => $actor->id,
            'supplier_name_snapshot' => $actor->displayName(), 'accepted_by' => null,
        ]);
        $this->assertDatabaseHas('order_assignments', [
            'order_id' => $openOrder, 'old_supplier_user_id' => $target->id,
            'new_supplier_user_id' => $actor->id, 'changed_by' => $actor->id,
        ]);
        $this->assertDatabaseHas('after_sales_cases', ['id' => $openCase, 'assigned_to' => $actor->id]);
        $this->assertDatabaseHas('after_sales_actions', ['id' => $openAction, 'assigned_to' => $actor->id]);
        $this->assertDatabaseHas('portal_conversations', ['id' => $openConversation, 'assigned_to' => $actor->id]);
        $this->assertDatabaseHas('receivable_cases', ['id' => $openReceivable, 'assigned_to' => $actor->id]);
        $this->assertDatabaseHas('orders', ['id' => $finishedOrder, 'supplier_user_id' => $target->id]);
        $this->assertDatabaseHas('after_sales_cases', ['id' => $closedCase, 'assigned_to' => $target->id]);
        $this->assertDatabaseHas('after_sales_actions', ['id' => $doneAction, 'assigned_to' => $target->id]);
        $this->assertDatabaseHas('portal_conversations', ['id' => $closedConversation, 'assigned_to' => $target->id]);
        $this->assertDatabaseHas('receivable_cases', ['id' => $closedReceivable, 'assigned_to' => $target->id]);
        self::assertSame(5, DB::table('audit_logs')->where('action', 'user.retirement.assignment_transferred')->count());
        self::assertSame(1, DB::table('audit_logs')->where('action', 'user.retired')->count());
    }

    public function test_pending_access_credentials_and_unsent_pushes_are_disabled_without_losing_history(): void
    {
        $actor = $this->user('access-root', 'superadmin');
        $target = $this->user('access-target');
        DB::table('password_reset_tokens')->insert([
            'user_id' => $target->id, 'token_hash' => hash('sha256', 'test-reset'),
            'expires_at' => now()->addHour(), 'created_at' => now(),
        ]);
        DB::table('user_activation_tokens')->insert([
            'user_id' => $target->id, 'token_hash' => hash('sha256', 'test-invite'),
            'expires_at' => now()->addHour(), 'created_at' => now(),
        ]);
        $outbox = DB::table('mobile_push_outbox')->insertGetId([
            'user_id' => $target->id, 'title' => 'Test poruka',
            'message' => 'Test iz CI', 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        app(UserRetirementService::class)->retire($target, $actor);
        self::assertSame(0, DB::table('password_reset_tokens')->where('user_id', $target->id)->count());
        self::assertSame(0, DB::table('user_activation_tokens')->where('user_id', $target->id)->count());
        $this->assertDatabaseHas('mobile_push_outbox', [
            'id' => $outbox, 'user_id' => $target->id, 'status' => 'failed',
        ]);
        self::assertNotNull(DB::table('mobile_push_outbox')->where('id', $outbox)->value('failed_at'));
    }

    public function test_web_cms_preview_confirmation_and_users_list_hide_retired_identity(): void
    {
        $root = $this->user('web-root', 'superadmin');
        $admin = $this->user('web-admin', 'admin');
        $target = $this->user('web-target');

        $preview = route('admin.users.retirement.preview', ['user' => $target->id]);
        $submit = route('admin.users.retirement.store', ['user' => $target->id]);

        $this->actingAs($admin)->get($preview)->assertForbidden();
        $this->actingAs($root)->get($preview)
            ->assertOk()
            ->assertSeeText('Bezbedno uklanjanje naloga')
            ->assertSeeText('OBRISI');
        $this->actingAs($root)->post($submit, ['confirmation' => 'obrisi'])
            ->assertSessionHasErrors('confirmation');
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'active']);

        $this->actingAs($root)->post($submit, ['confirmation' => 'OBRISI'])
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'blocked']);
        $this->actingAs($root)->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee($target->username);
    }

    public function test_retired_marker_blocks_login_google_reset_invites_and_permissions_even_if_status_drifts(): void
    {
        $actor = $this->user('drift-root', 'superadmin');
        $target = $this->user('drift-target');
        app(UserRetirementService::class)->retire($target, $actor);

        // Simulate an out-of-band status correction. The irreversible marker
        // must remain authoritative for every authentication path.
        $target->fresh()->forceFill(['status' => 'active'])->save();
        $target = $target->fresh();
        $target->clearResolvedAccessCache();

        self::assertNotNull($target->retired_at);
        self::assertSame([], $target->permissionSlugs());

        $this->postJson('/api/v1/auth/token', [
            'login' => $target->email,
            'password' => 'Secret123!',
            'device_name' => 'android-retired-drift',
        ])->assertUnprocessable();

        $reset = app(\App\Services\PasswordResetService::class);
        $reset->sendResetLink($target->email);
        self::assertSame(0, DB::table('password_reset_tokens')->where('user_id', $target->id)->count());

        DB::table('password_reset_tokens')->insert([
            'user_id' => $target->id, 'token_hash' => hash('sha256', 'qa-retired-reset'),
            'expires_at' => now()->addHour(), 'created_at' => now(),
        ]);
        self::assertFalse($reset->tokenIsValid('qa-retired-reset'));
        self::assertFalse($reset->resetPassword('qa-retired-reset', 'NewSecret123!'));

        $activate = app(\App\Services\CustomerActivationService::class);
        $this->assertRetiredInviteBlocked($activate, $target);
        DB::table('user_activation_tokens')->insert([
            'user_id' => $target->id, 'token_hash' => hash('sha256', 'qa-retired-invite'),
            'expires_at' => now()->addHour(), 'created_at' => now(),
        ]);
        self::assertFalse($activate->tokenIsValid('qa-retired-invite'));
        self::assertNull($activate->activate('qa-retired-invite', 'NewSecret123!'));

        try {
            app(\App\Services\GoogleAuthService::class)->resolve([
                'sub' => 'qa-retired-google-subject',
                'email' => $target->email,
                'email_verified' => true,
                'hd' => null,
                'name' => 'QA retired',
                'given_name' => 'QA',
                'family_name' => 'Retired',
            ]);
            self::fail('Google auth must not restore a retired account.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            self::assertArrayHasKey('google', $e->errors());
        }

        $this->actingAs($target)->get('/orders')->assertRedirect(route('login'));
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($target, ['*']);
        $this->getJson('/api/v1/me')->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'active']);
        self::assertNotNull($target->fresh()?->retired_at);
    }

    public function test_rollback_refuses_to_erase_retired_identity_marker(): void
    {
        $actor = $this->user('rollback-root', 'superadmin');
        $target = $this->user('rollback-target');
        app(UserRetirementService::class)->retire($target, $actor);

        $migration = require database_path('migrations/2026_10_16_160000_add_retired_at_to_users_table.php');
        try {
            $migration->down();
            self::fail('Rollback must never erase an existing retired_at marker.');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('Rollback HOLD', $e->getMessage());
        }

        self::assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('users', 'retired_at'));
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'blocked']);
        self::assertNotNull($target->fresh()?->retired_at);
    }

    private function assertRetiredInviteBlocked(\App\Services\CustomerActivationService $service, User $target): void
    {
        try {
            $service->invite($target);
            self::fail('Retired account must not receive an invitation.');
        } catch (HttpException $e) {
            self::assertSame(422, $e->getStatusCode());
        }
    }

    private function assertRetireStatus(int $expected, User $target, User $actor): void
    {
        try {
            app(UserRetirementService::class)->retire($target, $actor);
            self::fail('Expected retirement refusal '.$expected);
        } catch (HttpException $e) {
            self::assertSame($expected, $e->getStatusCode());
        }
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

    private function order(User $buyer, string $number, ?string $hash = null): int
    {
        return (int) DB::table('orders')->insertGetId([
            'order_number' => 'RET-'.$number,
            'user_id' => $buyer->id,
            'idempotency_key_hash' => $hash,
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

    private function product(User $creator, string $sku): int
    {
        return (int) DB::table('products')->insertGetId([
            'sku' => $sku,
            'name' => 'QA product '.$sku,
            'slug' => strtolower($sku),
            'price_amount' => '1000.00',
            'price_currency' => 'RSD',
            'description' => 'Retirement retention test',
            'stock_quantity' => 2,
            'status' => 'active',
            'created_by' => $creator->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
