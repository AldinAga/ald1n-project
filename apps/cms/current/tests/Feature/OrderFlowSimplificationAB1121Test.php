<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReceivableCase;
use App\Models\Role;
use App\Models\User;
use App\Services\CustomerPortalService;
use App\Services\OrderAmendmentService;
use App\Services\OrderService;
use App\Services\OrderVersionService;
use App\Services\OrderWorkflowService;
use App\Support\OrderStatus;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

/**
 * Pojednostavljenje toka porudžbine (odluke vlasnika 16.11.2026, deo A): jedan korak potvrde, otkazivanje sa razlogom,
 * ručni status samo za superadmina, jedan izvor naziva statusa, poruke i linkovi. Nijedno pravilo nije proveravano bez fixture-a:
 * svaki test asertuje konkretne vrednosti (status, accepted_by, lager, poruke). Author: Ald1n <https://ald1n.com>
 */
final class OrderFlowSimplificationAB1121Test extends TestCase
{
    use RefreshDatabase;

    private User $super;

    private User $admin;

    private User $otherAdmin;

    private User $buyer;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->super = $this->user('flow-super', 'superadmin');
        $this->admin = $this->user('flow-admin', 'admin');
        $this->otherAdmin = $this->user('flow-other-admin', 'admin');
        $this->buyer = $this->user('flow-buyer', 'user');
    }

    public function test_confirm_takes_the_order_over_and_confirms_it_in_one_step(): void
    {
        $order = $this->placeOrder();
        self::assertNull($order->accepted_at);

        $this->actingAs($this->admin)->post('/admin/orders/'.$order->id.'/confirm', [
            'order_version_token' => app(OrderVersionService::class)->token($order),
            'note' => 'Roba je na stanju.',
        ])->assertSessionHasNoErrors()->assertSessionHas('status', fn (string $m): bool => str_contains($m, 'je potvrđena') && str_contains($m, 'Sledeće: slanje'));

        $fresh = $order->fresh();
        self::assertSame('confirmed', $fresh->status);
        self::assertSame($this->admin->id, (int) $fresh->accepted_by);
        self::assertNotNull($fresh->accepted_at);
        self::assertSame(1, DB::table('order_status_history')->where('order_id', $order->id)->where('old_status', 'new')->where('new_status', 'confirmed')->count());
        self::assertTrue(AuditLog::query()->where('action', 'order.accepted')->exists());
        // Kupac ne dobija mejl o potvrdi, niti stari mejl "preuzeta".
        self::assertSame(0, DB::table('order_email_outbox')->whereIn('event_type', ['order_status_changed', 'order_accepted'])->count());
    }

    public function test_confirm_keeps_the_freshness_token_check(): void
    {
        $order = $this->placeOrder();
        $staleToken = app(OrderVersionService::class)->token($order);
        $order->forceFill(['shipping_phone' => '0611111111'])->save();

        $this->actingAs($this->admin)->post('/admin/orders/'.$order->id.'/confirm', ['order_version_token' => $staleToken])->assertStatus(409);
        self::assertSame('new', $order->fresh()->status);
        self::assertNull($order->fresh()->accepted_at);

        $this->actingAs($this->admin)->post('/admin/orders/'.$order->id.'/confirm', [])->assertSessionHasErrors('order_version_token');
        self::assertSame('new', $order->fresh()->status);
    }

    public function test_superadmin_confirming_somebody_elses_order_takes_it_over_for_himself(): void
    {
        $order = $this->placeOrder();

        app(OrderWorkflowService::class)->confirm($order, $this->super, null, app(OrderVersionService::class)->token($order));

        self::assertSame($this->super->id, (int) $order->fresh()->accepted_by);
        self::assertSame($this->admin->id, (int) $order->fresh()->supplier_user_id);
    }

    public function test_confirm_and_cancel_role_matrix_on_the_web(): void
    {
        $order = $this->placeOrder();
        $token = app(OrderVersionService::class)->token($order);

        $this->post('/admin/orders/'.$order->id.'/confirm', ['order_version_token' => $token])->assertRedirect();
        $this->actingAs($this->buyer)->post('/admin/orders/'.$order->id.'/confirm', ['order_version_token' => $token])->assertForbidden();
        $this->actingAs($this->buyer)->post('/admin/orders/'.$order->id.'/cancel', ['reason' => 'Ne želim.'])->assertForbidden();
        $this->actingAs($this->otherAdmin)->post('/admin/orders/'.$order->id.'/confirm', ['order_version_token' => $token])->assertNotFound();
        $this->actingAs($this->otherAdmin)->post('/admin/orders/'.$order->id.'/cancel', ['reason' => 'Nije moja.'])->assertNotFound();
        self::assertSame('new', $order->fresh()->status);

        $this->actingAs($this->admin)->post('/admin/orders/'.$order->id.'/cancel', ['reason' => 'Kupac je odustao.'])->assertSessionHasNoErrors();
        self::assertSame('cancelled', $order->fresh()->status);
    }

    public function test_cancel_by_the_responsible_person_needs_a_reason_and_returns_the_stock(): void
    {
        $order = $this->placeOrder();
        $product = Product::query()->sole();
        self::assertSame(48, $product->fresh()->stock_quantity);

        $this->actingAs($this->admin)->post('/admin/orders/'.$order->id.'/cancel', ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($this->admin)->post('/admin/orders/'.$order->id.'/cancel', ['reason' => 'x'])->assertSessionHasErrors('reason');
        self::assertSame('new', $order->fresh()->status);
        self::assertSame(48, $product->fresh()->stock_quantity);

        $this->actingAs($this->admin)->post('/admin/orders/'.$order->id.'/cancel', ['reason' => 'Artikal je povučen iz ponude.'])
            ->assertSessionHas('status', fn (string $m): bool => str_contains($m, 'je otkazana') && str_contains($m, 'lager je vraćen'));
        self::assertSame('cancelled', $order->fresh()->status);
        self::assertSame(50, $product->fresh()->stock_quantity);
        self::assertSame('Artikal je povučen iz ponude.', DB::table('order_status_history')->where('order_id', $order->id)->where('new_status', 'cancelled')->value('note'));
    }

    public function test_legacy_processing_rows_display_as_nova_and_can_still_be_confirmed_or_cancelled(): void
    {
        $toConfirm = $this->placeOrder();
        $toCancel = $this->placeOrder();
        DB::table('orders')->whereIn('id', [$toConfirm->id, $toCancel->id])->update(['status' => 'processing']);

        $page = $this->root((string) $this->actingAs($this->super)->get('/admin/orders/'.$toConfirm->id)->assertOk()->getContent());
        self::assertStringContainsString('a1-pill a1-pill--warning">Nova</span>', $page);
        self::assertStringNotContainsString('U obradi', $page);
        self::assertStringContainsString('id="order-confirm-form"', $page);

        $this->actingAs($this->admin)->post('/admin/orders/'.$toConfirm->id.'/confirm', ['order_version_token' => app(OrderVersionService::class)->token($toConfirm->fresh())])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post('/admin/orders/'.$toCancel->id.'/cancel', ['reason' => 'Dupla porudžbina.'])->assertSessionHasNoErrors();

        self::assertSame(['confirmed', 'cancelled'], [$toConfirm->fresh()->status, $toCancel->fresh()->status]);
        // Filter "Nova" vraća i stare redove, a "U obradi" ne postoji među opcijama.
        $list = (string) $this->actingAs($this->super)->get('/admin/orders?status=new')->assertOk()->getContent();
        self::assertStringNotContainsString('value="processing"', $list);
        $newOne = $this->placeOrder();
        DB::table('orders')->where('id', $newOne->id)->update(['status' => 'processing']);
        self::assertStringContainsString($newOne->order_number, (string) $this->actingAs($this->super)->get('/admin/orders?status=new')->getContent());
    }

    public function test_nothing_new_can_enter_processing_and_the_old_mobile_calls_fail_soft(): void
    {
        $order = $this->placeOrder();

        try {
            app(OrderWorkflowService::class)->changeStatus($order, 'processing', $this->super);
            self::fail('processing must be refused.');
        } catch (ValidationException $exception) {
            self::assertSame(['status' => ['Korak „U obradi“ više ne postoji. Potvrdi porudžbinu ili je otkaži.']], $exception->errors());
        }
        self::assertSame('new', $order->fresh()->status);

        Sanctum::actingAs($this->admin, ['*']);
        $this->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'processing'])
            ->assertStatus(422)->assertJsonValidationErrors('status')
            ->assertJsonPath('errors.status.0', 'Korak „U obradi“ više ne postoji. Potvrdi porudžbinu ili je otkaži.');
        $this->postJson('/api/v1/admin/orders/'.$order->id.'/accept')
            ->assertStatus(422)->assertJsonPath('code', 'order_accept_removed')
            ->assertJsonPath('message', 'Korak „Preuzmi“ više ne postoji. Potvrdi porudžbinu: potvrda je ujedno preuzima na tvoje ime.');
        self::assertNull($order->fresh()->accepted_at);
        self::assertSame('new', $order->fresh()->status);
    }

    public function test_mobile_status_endpoint_still_confirms_with_takeover_and_cancels_only_with_a_reason(): void
    {
        $order = $this->placeOrder();
        Sanctum::actingAs($this->admin, ['*']);

        $this->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'cancelled'])->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'confirmed', 'order_version_token' => app(OrderVersionService::class)->token($order)])
            ->assertOk()->assertJsonPath('data.status', 'confirmed');
        self::assertSame($this->admin->id, (int) $order->fresh()->accepted_by);
        $this->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'cancelled', 'note' => 'Stari klijent šalje note.'])
            ->assertOk()->assertJsonPath('data.status', 'cancelled');
    }

    public function test_api_detail_drops_accept_and_advertises_confirm_cancel_and_manual_options(): void
    {
        $order = $this->placeOrder();

        Sanctum::actingAs($this->admin, ['*']);
        $detail = $this->getJson('/api/v1/admin/orders/'.$order->id)->assertOk();
        $detail->assertJsonMissingPath('data.actions.accept');
        $detail->assertJsonPath('data.actions.confirm', true)->assertJsonPath('data.actions.cancel', true)->assertJsonPath('data.actions.manual_status', false);
        $detail->assertJsonPath('data.order.status_label', 'Nova')->assertJsonPath('data.manual_status_options', []);

        Sanctum::actingAs($this->super, ['*']);
        $asSuper = $this->getJson('/api/v1/admin/orders/'.$order->id)->assertOk();
        $asSuper->assertJsonPath('data.actions.manual_status', true);
        $asSuper->assertJsonPath('data.manual_status_options', [['value' => 'confirmed', 'label' => 'Potvrđena'], ['value' => 'cancelled', 'label' => 'Otkazana']]);

        $this->getJson('/api/v1/admin/orders')->assertOk()->assertJsonPath('data.0.status_label', 'Nova');
    }

    public function test_manual_status_is_superadmin_only_and_offers_only_allowed_transitions(): void
    {
        self::assertSame(['confirmed', 'cancelled'], OrderWorkflowService::manualTransitions('new'));
        self::assertSame(['confirmed', 'cancelled'], OrderWorkflowService::manualTransitions('processing'));
        self::assertSame(['cancelled'], OrderWorkflowService::manualTransitions('confirmed'));
        self::assertSame(['cancelled'], OrderWorkflowService::manualTransitions('shipped'));
        self::assertSame([], OrderWorkflowService::manualTransitions('cancelled'));

        $order = $this->placeOrder();
        $url = '/admin/orders/'.$order->id.'/status';
        $this->actingAs($this->admin)->patch($url, ['status' => 'confirmed', 'order_version_token' => app(OrderVersionService::class)->token($order)])->assertForbidden();
        self::assertSame('new', $order->fresh()->status);

        $this->actingAs($this->super)->patch($url, ['status' => 'shipped'])->assertSessionHasErrors('status');
        $this->actingAs($this->super)->patch($url, ['status' => 'cancelled'])->assertSessionHasErrors('note')->assertSessionDoesntHaveErrors('reason');
        $this->actingAs($this->super)->patch($url, ['status' => 'confirmed', 'order_version_token' => app(OrderVersionService::class)->token($order->fresh())])
            ->assertSessionHas('status', fn (string $m): bool => str_contains($m, 'je potvrđena'));
        self::assertSame('confirmed', $order->fresh()->status);

        $this->actingAs($this->super)->patch($url, ['status' => 'cancelled', 'note' => 'Greška u unosu.'])->assertSessionHasNoErrors();
        $this->actingAs($this->super)->patch($url, ['status' => 'confirmed', 'order_version_token' => app(OrderVersionService::class)->token($order->fresh())])
            ->assertSessionHasErrors(['status' => 'Porudžbina je „Otkazana“ i ne može da pređe u „Potvrđena“. Za ovaj status nema dalje ručne izmene.']);
    }

    public function test_refused_transition_message_is_human_and_names_what_is_allowed(): void
    {
        $order = $this->placeOrder();
        $workflow = app(OrderWorkflowService::class);
        $workflow->confirm($order, $this->admin, null, app(OrderVersionService::class)->token($order));

        try {
            $workflow->changeStatus($order->fresh(), 'new', $this->super);
            self::fail('confirmed -> new must be refused.');
        } catch (ValidationException $exception) {
            self::assertSame('Porudžbina je „Potvrđena“ i ne može da pređe u „Nova“. Dozvoljeno: Otkazana.', $exception->errors()['status'][0]);
        }
    }

    public function test_the_manual_status_form_and_the_dead_routes_follow_the_role(): void
    {
        $order = $this->placeOrder();

        $asSuper = (string) $this->actingAs($this->super)->get('/admin/orders/'.$order->id)->assertOk()->getContent();
        $asAdmin = (string) $this->actingAs($this->admin)->get('/admin/orders/'.$order->id)->assertOk()->getContent();
        self::assertStringContainsString('Ručna promena statusa', $asSuper);
        self::assertStringNotContainsString('Ručna promena statusa', $asAdmin);

        self::assertFalse(Route::has('admin.orders.tracking'));
        self::assertFalse(Route::has('admin.orders.accept'));
        $this->actingAs($this->super)->patch('/admin/orders/'.$order->id.'/tracking', ['tracking_number' => 'X'])->assertNotFound();
        $this->actingAs($this->super)->post('/admin/orders/'.$order->id.'/accept')->assertNotFound();
        self::assertFalse(method_exists(OrderWorkflowService::class, 'updateTracking'));
    }

    public function test_a_buyer_amendment_returns_a_confirmed_order_to_nova_for_reconfirmation(): void
    {
        $order = $this->placeOrder();
        app(OrderWorkflowService::class)->confirm($order, $this->admin, null, app(OrderVersionService::class)->token($order));
        $confirmed = $order->fresh();
        self::assertNotNull($confirmed->accepted_at);

        $updated = app(OrderAmendmentService::class)->amend($confirmed, $this->buyer, [
            'expected_edit_token' => app(OrderVersionService::class)->token($confirmed),
            'shipping_full_name' => 'Flow Kupac', 'shipping_address' => 'Nova adresa 5', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '0612223334',
            'items' => [['product_id' => Product::query()->sole()->id, 'quantity' => 2]],
        ], 'flow-amend-1');

        self::assertSame('new', $updated->status);
        self::assertNull($updated->accepted_at);
        self::assertNull($updated->accepted_by);
        self::assertSame('Nova adresa 5', $updated->shipping_address);
        self::assertSame(1, DB::table('order_status_history')->where('order_id', $order->id)->where('old_status', 'confirmed')->where('new_status', 'new')->count());
        self::assertSame(0, DB::table('order_status_history')->where('order_id', $order->id)->where('new_status', 'processing')->count());
        // Odgovorno lice je ponovo potvrđuje, i to je regularan prelaz.
        $again = app(OrderWorkflowService::class)->confirm($updated, $this->admin, null, app(OrderVersionService::class)->token($updated));
        self::assertSame(['confirmed', $this->admin->id], [$again->status, (int) $again->accepted_by]);
    }

    public function test_buyer_sees_why_edit_is_unavailable_after_shipping(): void
    {
        $shipped = $this->placeOrder();
        $cancelled = $this->placeOrder();
        $delivered = $this->placeOrder();
        DB::table('orders')->where('id', $shipped->id)->update(['status' => 'shipped']);
        DB::table('orders')->where('id', $cancelled->id)->update(['status' => 'cancelled']);
        DB::table('orders')->where('id', $delivered->id)->update(['status' => 'shipped', 'completed_at' => now()]);

        $this->actingAs($this->buyer)->get('/orders/'.$shipped->id)->assertOk()
            ->assertSee('Pošiljka je već poslata, pa se porudžbina više ne može menjati. Za promenu pozovi odgovorno lice', false);
        $this->actingAs($this->buyer)->get('/orders/'.$cancelled->id)->assertOk()->assertSee('Porudžbina je otkazana, pa je zaključana.');
        $this->actingAs($this->buyer)->get('/orders/'.$delivered->id)->assertOk()->assertSee('Porudžbina je isporučena, pa je zaključana.');
    }

    public function test_order_detail_links_to_the_receivable_case_and_the_commission(): void
    {
        $order = $this->placeOrder();
        $case = ReceivableCase::query()->create(['order_id' => $order->id, 'case_number' => 'PS-FLOW-1', 'status' => 'monitoring', 'created_by' => $this->super->id]);

        $page = (string) $this->actingAs($this->super)->get('/admin/orders/'.$order->id)->assertOk()->getContent();

        self::assertMatchesRegularExpression('~data-order-receivable-link.*?Potraživanje PS-FLOW-1.*?href="[^"]*/admin/receivables/'.$case->id.'"~s', $page);
        self::assertMatchesRegularExpression('~Otvori proviziju~', $page);
    }

    public function test_one_status_label_source_everywhere(): void
    {
        self::assertSame(
            ['Nova', 'Nova', 'Potvrđena', 'Poslata', 'Otkazana'],
            array_map(static fn (string $s): string => OrderStatus::label($s), ['new', 'processing', 'confirmed', 'shipped', 'cancelled']),
        );
        self::assertSame('Isporučena', OrderStatus::label('shipped', true));
        self::assertSame('ok', OrderStatus::tone('shipped', true));

        // CustomerPortalService je govorio "Završena".
        $portal = app(CustomerPortalService::class);
        $method = new ReflectionMethod($portal, 'orderStatus');
        self::assertSame('Isporučena', $method->invoke($portal, 'completed'));
        self::assertSame('Nova', $method->invoke($portal, 'processing'));

        // Mobilni API: ista imena i za kupca.
        $order = $this->placeOrder();
        DB::table('orders')->where('id', $order->id)->update(['status' => 'shipped', 'completed_at' => now()]);
        Sanctum::actingAs($this->buyer, ['*']);
        $this->getJson('/api/v1/orders/'.$order->id)->assertOk()->assertJsonPath('data.status_label', 'Isporučena');
        // Javna strana porudžbine: stari fallback "U obradi" je nestao.
        self::assertStringNotContainsString('U obradi', (string) file_get_contents(resource_path('views/public-shop/order.blade.php')));
    }

    public function test_order_received_mail_does_not_mix_login_with_the_public_link(): void
    {
        // Rutu checkout-a pokriva PublicRetailCheckoutB799Test (prazna korpa); mejl se šalje kroz Mail::raw, pa se tekst proverava u izvoru.
        $controller = (string) file_get_contents(app_path('Http/Controllers/PublicCheckoutController.php'));
        self::assertStringNotContainsString('Prijavite se na svoj potvrđeni nalog Kupca', $controller);
        self::assertStringNotContainsString('zahteva prijavu', $controller);
        self::assertStringContainsString('Link otvara samo ovu porudžbinu i ne traži prijavu.', $controller);
    }

    /** Koren stranice bez ljuske aplikacije (bez menija i podnožja). */
    private function root(string $html): string
    {
        $start = strpos($html, 'class="a1-ops"');
        self::assertNotFalse($start, 'page root missing');

        return substr($html, $start);
    }

    private function placeOrder(): Order
    {
        $n = ++$this->seq;
        $product = Product::query()->first() ?? Product::query()->create([
            'sku' => 'FLOW-1', 'name' => 'Flow artikal', 'slug' => 'flow-1', 'price_amount' => 1000, 'price_currency' => 'RSD',
            'description' => 'Test artikal.', 'stock_quantity' => 50, 'status' => 'active', 'created_by' => $this->super->id,
        ]);
        $order = app(OrderService::class)->create($this->buyer, [
            'supplier_user_id' => $this->admin->id,
            'shipping_full_name' => 'Flow Kupac', 'shipping_address' => 'Adresa 1', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '060123456', 'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ], 'flow-order-'.$n);

        return $order->fresh();
    }

    private function user(string $username, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $username, 'email' => $username.'@example.test', 'first_name' => 'Flow', 'last_name' => ucfirst(str_replace('flow-', '', $username)),
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
    }
}
