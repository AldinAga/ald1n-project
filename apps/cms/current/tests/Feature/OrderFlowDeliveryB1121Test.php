<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AfterSalesCase;
use App\Models\AuditLog;
use App\Models\CourierService;
use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\OrderDocument;
use App\Models\OrderPayment;
use App\Models\OrderShipment;
use App\Models\Product;
use App\Models\ProductWarranty;
use App\Models\ReceivableCase;
use App\Models\Role;
use App\Models\User;
use App\Models\WarrantyIssuance;
use App\Models\WarrantyRule;
use App\Services\OrderEmailOutboxService;
use App\Services\OrderPaymentService;
use App\Services\OrderService;
use App\Services\OrderShipmentService;
use App\Services\OrderVersionService;
use App\Services\OrderWorkflowService;
use App\Services\ReceivablesService;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pojednostavljenje toka porudžbine, deo B (odluke vlasnika 16.11.2026): isporuka odvojena od naplate, garancija od datuma
 * isporuke, jedna isporuka (kurir ili ne), "Pošiljka vraćena", automatski račun, jedan obrazac uplate, reklamacija u ime kupca.
 * Svi događaji idu kroz prave servise i rute; vrednosti su konkretne. Author: Ald1n <https://ald1n.com>
 */
final class OrderFlowDeliveryB1121Test extends TestCase
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
        $this->super = $this->user('b-super', 'superadmin');
        $this->admin = $this->user('b-admin', 'admin');
        $this->otherAdmin = $this->user('b-other-admin', 'admin');
        $this->buyer = $this->user('b-buyer', 'user');
        app(SettingsService::class)->putMany([
            'site_name' => 'Ald1n Test', 'documents_company_name' => 'Ald1n Test',
            'documents_company_address' => 'Test adresa 1', 'documents_company_city' => 'Beograd',
            'documents_company_tax_id' => '123456789', 'documents_company_registration_number' => '12345678',
        ], $this->super->id);
        $rule = WarrantyRule::query()->where('scope_type', 'global')->orderByDesc('id')->first();
        $attrs = ['is_active' => true, 'duration_months' => 12, 'duration_days' => 0, 'priority' => 100, 'terms' => 'Sintetički uslovi.'];
        $rule instanceof WarrantyRule
            ? $rule->update($attrs)
            : WarrantyRule::query()->create($attrs + ['name' => 'Sintetički uslovi', 'scope_type' => 'global']);
    }

    public function test_unpaid_order_is_delivered_with_open_debt_warranty_from_delivery_date_and_commission_payable(): void
    {
        $order = $this->confirmedOrder(['payment_method' => 'deferred_payment', 'payment_due_at' => now()->addDays(14)]);
        app(ReceivablesService::class)->ensureForOrder($order->fresh(), $this->admin);
        $commission = OrderCommission::query()->where('order_id', $order->id)->firstOrFail();
        self::assertSame('pending', $commission->status);
        $deliveredAt = now()->subDays(3)->startOfDay()->addHours(10);

        $completed = app(OrderWorkflowService::class)->complete($order->fresh(), $this->admin, [
            'delivery_method' => 'own_transport', 'delivered_at' => $deliveredAt->toDateTimeString(), 'recipient_name' => 'Primalac Test',
        ]);

        self::assertNotNull($completed->completed_at);
        self::assertSame('unpaid', $completed->payment_state);
        self::assertSame(0, OrderPayment::query()->count(), 'delivery must not fabricate a payment for a non-COD order');
        self::assertSame('monitoring', ReceivableCase::query()->where('order_id', $order->id)->sole()->status, 'the debt stays open in Potraživanja');
        self::assertSame('approved', $commission->fresh()->status, 'delivery alone makes the commission "Za isplatu"');
        self::assertNotNull($commission->fresh()->auto_approved_at);
        $warranty = ProductWarranty::query()->sole();
        self::assertSame('active', $warranty->status);
        self::assertSame($deliveredAt->toDateString(), $warranty->starts_at->toDateString());
        self::assertSame(0, WarrantyIssuance::query()->count());
        self::assertSame(1, OrderDocument::query()->where('order_id', $order->id)->where('document_type', 'invoice')->where('status', 'issued')->count(), 'invoice is issued automatically at delivery');

        // Idempotentno: ponovna potvrda ne pravi drugi račun, drugu garanciju ni drugu isporuku.
        app(OrderWorkflowService::class)->complete($completed, $this->admin, ['delivery_method' => 'own_transport']);
        self::assertSame(1, OrderDocument::query()->where('document_type', 'invoice')->count());
        self::assertSame(1, ProductWarranty::query()->count());
        self::assertSame(1, DB::table('order_deliveries')->count());

        // Uplata posle isporuke se prima (stari ulaz "cod" se čuva kao cash_on_delivery); sertifikat se ne duplira ni pomera.
        $payment = app(OrderPaymentService::class)->record($completed->fresh(), [
            'amount_rsd' => (float) $order->subtotal_rsd, 'payment_method' => 'cod', 'paid_at' => now()->toDateTimeString(),
        ], $this->admin);
        self::assertSame('cash_on_delivery', $payment->payment_method);
        self::assertSame('paid', $order->fresh()->payment_state);
        self::assertSame('closed', ReceivableCase::query()->where('order_id', $order->id)->sole()->status);
        self::assertSame(1, ProductWarranty::query()->count());
        self::assertSame($deliveredAt->toDateString(), ProductWarranty::query()->sole()->starts_at->toDateString());
        self::assertSame(0, WarrantyIssuance::query()->count());
        // Refundacija posle isporuke je takođe moguća (ledger više nije zaključan).
        $refund = app(OrderPaymentService::class)->record($order->fresh(), ['entry_type' => 'refund', 'amount_rsd' => 100, 'payment_method' => 'cash', 'paid_at' => now()->toDateTimeString()], $this->admin);
        self::assertSame('refund', $refund->entry_type);
    }

    public function test_cod_delivery_still_records_the_collected_amount_and_the_buyer_mail_carries_the_invoice(): void
    {
        $order = $this->confirmedOrder(['payment_method' => 'cash_on_delivery']);

        app(OrderWorkflowService::class)->complete($order->fresh(), $this->admin, ['delivery_method' => 'own_transport', 'recipient_name' => 'Kupac']);

        $payment = OrderPayment::query()->sole();
        self::assertGreaterThan(0.0, (float) $order->subtotal_rsd);
        self::assertSame(['cash_on_delivery', 'verified', (float) $order->subtotal_rsd], [$payment->payment_method, $payment->status, (float) $payment->amount_rsd]);
        self::assertSame('paid', $order->fresh()->payment_state);
        $mail = DB::table('order_email_outbox')->where('event_type', 'order_completed')->where('recipient_user_id', $this->buyer->id)->first();
        self::assertNotNull($mail);
        self::assertTrue((bool) $mail->attach_active_invoice);
        self::assertStringContainsString('je isporučena', (string) $mail->message);
        self::assertStringContainsString('Račun', (string) $mail->message);
    }

    public function test_courier_needs_the_shipment_step_with_courier_and_tracking_but_own_transport_and_pickup_do_not(): void
    {
        $order = $this->confirmedOrder();
        $workflow = app(OrderWorkflowService::class);

        try {
            $workflow->complete($order->fresh(), $this->admin, ['delivery_method' => 'courier']);
            self::fail('courier delivery needs the shipment step first');
        } catch (ValidationException $exception) {
            self::assertStringContainsString('najpre potvrdi da je pošiljka poslata', $exception->errors()['delivery_method'][0]);
        }
        self::assertNull($order->fresh()->completed_at);

        $courier = $this->courier();
        $shipments = app(OrderShipmentService::class);
        foreach (['', '   '] as $blank) {
            try {
                $shipments->record($order->fresh(), $this->admin, ['shipment_method' => 'courier', 'courier_service_id' => $courier->id, 'tracking_number' => $blank, 'recipient_name' => 'X'], null, $this->token($order));
                self::fail('tracking number is mandatory for a courier shipment');
            } catch (ValidationException $exception) {
                self::assertArrayHasKey('tracking_number', $exception->errors());
            }
        }
        try {
            $shipments->record($order->fresh(), $this->admin, ['shipment_method' => 'courier', 'tracking_number' => 'T-1', 'recipient_name' => 'X'], null, $this->token($order));
            self::fail('an active courier is mandatory for a courier shipment');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('courier_service_id', $exception->errors());
        }

        // Pickup and own transport: only "Potvrdi isporuku", no shipment row, no courier fields.
        $pickup = $this->confirmedOrder();
        $this->actingAs($this->admin)->post('/admin/orders/'.$pickup->id.'/complete', [
            'delivery_method' => 'customer_pickup', 'delivered_at' => now()->subMinute()->format('Y-m-d\TH:i'), 'recipient_name' => 'Lično',
        ])->assertSessionHasNoErrors()->assertSessionHas('status', fn (string $m): bool => str_contains($m, 'je isporučena') && str_contains($m, 'Račun je izdat automatski'));
        self::assertNotNull($pickup->fresh()->completed_at);
        self::assertSame(0, OrderShipment::query()->where('order_id', $pickup->id)->count());
        self::assertSame('customer_pickup', DB::table('order_deliveries')->where('order_id', $pickup->id)->value('delivery_method'));
    }

    public function test_one_delivery_card_carries_shipment_fields_into_the_delivery_step_and_one_label_everywhere(): void
    {
        $order = $this->confirmedOrder();
        $courier = $this->courier();
        $this->actingAs($this->admin)->post('/admin/orders/'.$order->id.'/shipment', [
            'shipment_method' => 'courier', 'courier_service_id' => $courier->id, 'tracking_number' => 'TRK-777',
            'shipped_at' => now()->subHour()->format('Y-m-d\TH:i'), 'recipient_name' => 'Primalac Iz Slanja', 'recipient_phone' => '0645556667',
            'order_version_token' => $this->token($order),
        ])->assertSessionHasNoErrors()->assertSessionHas('status', fn (string $m): bool => str_contains($m, 'Sledeće: kada roba stigne kod kupca, potvrdi isporuku'));
        self::assertSame('shipped', $order->fresh()->status);

        $page = $this->root((string) $this->actingAs($this->admin)->get('/admin/orders/'.$order->id)->assertOk()->getContent());
        self::assertSame(1, substr_count($page, 'id="order-workspace-logistics-actions"'));
        self::assertStringNotContainsString('id="od-method-switch"', $page, 'the method is fixed once the shipment exists');
        self::assertStringContainsString('<input type="hidden" name="delivery_method" value="courier" data-delivery-method-input>', $page);
        self::assertMatchesRegularExpression('~id="od-del-name" name="recipient_name" maxlength="190" value="Primalac Iz Slanja"~', $page);
        self::assertMatchesRegularExpression('~id="od-del-phone" name="recipient_phone" maxlength="60" value="0645556667"~', $page);
        self::assertMatchesRegularExpression('~id="od-del-ref" name="delivery_reference" maxlength="190" value="TRK-777"~', $page);
        self::assertSame(1, preg_match('~<form class="a1-form a1-od-toggle" id="delivery-completion"[^>]*>~', $page, $deliveryTag));
        self::assertSame(0, preg_match('~\shidden\b~', $deliveryTag[0]), 'the delivery step is visible once the shipment is recorded');
        foreach (['Potvrdi da je pošiljka isporučena', 'Evidentiraj isporuku', 'Evidentiraj slanje'] as $oldLabel) {
            self::assertStringNotContainsString($oldLabel, $page);
        }
        self::assertSame(1, preg_match_all('~>\s*(?:<[^>]+>\s*)*Potvrdi isporuku\s*</button>~', $page));

        // Isporuka bez ponovnog unosa: sva polja stižu iz slanja.
        $this->actingAs($this->admin)->post('/admin/orders/'.$order->id.'/complete', ['delivered_at' => now()->subMinute()->format('Y-m-d\TH:i')])->assertSessionHasNoErrors();
        $delivery = DB::table('order_deliveries')->where('order_id', $order->id)->first();
        self::assertSame(['courier', 'Primalac Iz Slanja', '0645556667'], [$delivery->delivery_method, $delivery->recipient_name, $delivery->recipient_phone]);
        self::assertSame(2, DB::table('audit_logs')->whereIn('action', ['order.shipment_recorded', 'order.delivery_confirmed'])->count(), 'shipment and delivery stay separate audit events');
    }

    public function test_returned_shipment_goes_back_to_confirmed_keeps_stock_and_record_and_can_be_reshipped_or_cancelled(): void
    {
        $order = $this->confirmedOrder();
        $product = Product::query()->sole();
        $stock = $product->fresh()->stock_quantity;
        $courier = $this->courier();
        app(OrderShipmentService::class)->record($order->fresh(), $this->admin, ['shipment_method' => 'courier', 'courier_service_id' => $courier->id, 'tracking_number' => 'RET-1', 'recipient_name' => 'Kupac'], null, $this->token($order));
        self::assertSame('shipped', $order->fresh()->status);
        $url = '/admin/orders/'.$order->id.'/shipment/return';

        // Uloge: kupac/SubAgent 403, tuđi admin 404, bez razloga greška.
        $this->actingAs($this->buyer)->post($url, ['reason' => 'Vraćeno.'])->assertForbidden();
        $this->actingAs($this->otherAdmin)->post($url, ['reason' => 'Vraćeno.'])->assertNotFound();
        $this->actingAs($this->super)->post($url, ['reason' => ''])->assertSessionHasErrors('reason');
        self::assertSame('shipped', $order->fresh()->status);
        $notifiedBefore = $this->admin->notifications()->count();

        $this->actingAs($this->super)->post($url, ['reason' => 'Kupac nije preuzeo paket.'])
            ->assertSessionHas('status', fn (string $m): bool => str_contains($m, 'vraćena') && str_contains($m, 'ponovo Potvrđena'));

        $fresh = $order->fresh();
        self::assertSame('confirmed', $fresh->status);
        self::assertSame('reserved', $fresh->inventory_state);
        self::assertSame($stock, $product->fresh()->stock_quantity, 'stock stays reserved');
        $shipment = OrderShipment::query()->where('order_id', $order->id)->sole();
        self::assertSame(['RET-1', 'Kupac nije preuzeo paket.', $this->super->id], [$shipment->tracking_number_snapshot, $shipment->return_reason, (int) $shipment->returned_by]);
        self::assertNotNull($shipment->returned_at);
        self::assertSame(1, DB::table('order_status_history')->where('order_id', $order->id)->where('old_status', 'shipped')->where('new_status', 'confirmed')->count());
        self::assertTrue(AuditLog::query()->where('action', 'order.shipment_returned')->exists());
        self::assertSame($notifiedBefore + 1, $this->admin->notifications()->count(), 'the responsible person is notified');
        // Drugi povrat i povrat neposlate porudžbine su odbijeni.
        $this->actingAs($this->super)->post($url, ['reason' => 'Ponovo.'])->assertSessionHasErrors('shipment');

        // Ponovno slanje (isti red, nova pošiljka) i isporuka kurirom tek posle toga.
        try {
            app(OrderWorkflowService::class)->complete($fresh, $this->admin, ['delivery_method' => 'courier']);
            self::fail('a returned shipment cannot be delivered');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('delivery_method', $exception->errors());
        }
        app(OrderShipmentService::class)->record($order->fresh(), $this->admin, ['shipment_method' => 'courier', 'courier_service_id' => $courier->id, 'tracking_number' => 'RET-2', 'recipient_name' => 'Kupac'], null, $this->token($order));
        $reshipped = OrderShipment::query()->where('order_id', $order->id)->sole();
        self::assertSame(['RET-2', null, 'shipped'], [$reshipped->tracking_number_snapshot, $reshipped->returned_at, $order->fresh()->status]);

        // Ili otkazivanje: lager se vraća odmah.
        $this->actingAs($this->super)->post($url, ['reason' => 'Opet vraćeno.'])->assertSessionHasNoErrors();
        $this->actingAs($this->super)->post('/admin/orders/'.$order->id.'/cancel', ['reason' => 'Kupac odustaje.'])->assertSessionHasNoErrors();
        self::assertSame(['cancelled', $stock + 2], [$order->fresh()->status, $product->fresh()->stock_quantity]);
    }

    public function test_returned_shipment_api_contract_and_code_tolerates_missing_columns(): void
    {
        $order = $this->confirmedOrder();
        $courier = $this->courier();
        app(OrderShipmentService::class)->record($order->fresh(), $this->admin, ['shipment_method' => 'courier', 'courier_service_id' => $courier->id, 'tracking_number' => 'API-1', 'recipient_name' => 'Kupac'], null, $this->token($order));
        $url = '/api/v1/admin/orders/'.$order->id.'/shipment/return';

        Sanctum::actingAs($this->buyer, ['*']);
        $this->postJson($url, ['reason' => 'x y z'])->assertForbidden();
        Sanctum::actingAs($this->otherAdmin, ['*']);
        $this->postJson($url, ['reason' => 'x y z'])->assertNotFound();
        Sanctum::actingAs($this->admin, ['*']);
        $detail = $this->getJson('/api/v1/admin/orders/'.$order->id)->assertOk();
        $detail->assertJsonPath('data.actions.return_shipment', true)->assertJsonPath('data.shipment.is_returned', false);
        $this->postJson($url, [])->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->postJson($url, ['reason' => 'Paket vraćen kuriru.'])->assertOk()->assertJsonPath('data.status', 'confirmed')->assertJsonPath('data.return_reason', 'Paket vraćen kuriru.');
        $after = $this->getJson('/api/v1/admin/orders/'.$order->id)->assertOk();
        $after->assertJsonPath('data.actions.return_shipment', false)->assertJsonPath('data.actions.shipment', true)->assertJsonPath('data.shipment.is_returned', true);

        // Kod isporučen pre migracije: bez kolona povrat daje jasnu poruku, a slanje i prikaz rade.
        OrderShipment::$returnColumnsOverride = false;
        try {
            $other = $this->confirmedOrder();
            app(OrderShipmentService::class)->record($other->fresh(), $this->admin, ['shipment_method' => 'courier', 'courier_service_id' => $courier->id, 'tracking_number' => 'NOCOL', 'recipient_name' => 'Kupac'], null, $this->token($other));
            $this->postJson('/api/v1/admin/orders/'.$other->id.'/shipment/return', ['reason' => 'Bez kolona.'])->assertStatus(422)->assertJsonValidationErrors('shipment');
            $this->getJson('/api/v1/admin/orders/'.$other->id)->assertOk()->assertJsonPath('data.shipment.is_returned', false)->assertJsonPath('data.actions.return_shipment', true);
        } finally {
            OrderShipment::$returnColumnsOverride = null;
        }
        self::assertTrue(\Illuminate\Support\Facades\Schema::hasColumns('order_shipments', ['returned_at', 'returned_by', 'return_reason']));
    }

    public function test_a_failing_invoice_never_rolls_back_the_delivery_and_is_reported(): void
    {
        $order = $this->confirmedOrder();
        $notifiedBefore = $this->admin->notifications()->count();
        OrderDocument::creating(static function (OrderDocument $document) use ($order): void {
            if ((int) $document->order_id === (int) $order->id) {
                throw new \RuntimeException('B1121_SYNTHETIC_PDF_FAILURE');
            }
        });

        $completed = app(OrderWorkflowService::class)->complete($order->fresh(), $this->admin, ['delivery_method' => 'own_transport', 'recipient_name' => 'Kupac']);

        self::assertNotNull($completed->completed_at);
        self::assertSame(1, DB::table('order_deliveries')->where('order_id', $order->id)->count());
        self::assertSame(1, ProductWarranty::query()->where('order_id', $order->id)->count());
        self::assertSame(0, OrderDocument::query()->where('order_id', $order->id)->where('document_type', 'invoice')->count());
        self::assertTrue(AuditLog::query()->where('action', 'order.invoice_auto_failed')->exists());
        self::assertGreaterThan($notifiedBefore, $this->admin->notifications()->count(), 'the confirmer is told that the invoice was not issued');
    }

    public function test_delivered_order_page_header_has_no_invoice_form_and_keeps_payments_and_debt_visible(): void
    {
        $order = $this->confirmedOrder(['payment_method' => 'deferred_payment', 'payment_due_at' => now()->addDays(14)]);
        app(OrderWorkflowService::class)->complete($order->fresh(), $this->admin, ['delivery_method' => 'own_transport', 'recipient_name' => 'Kupac']);

        $page = $this->root((string) $this->actingAs($this->super)->get('/admin/orders/'.$order->id)->assertOk()->getContent());

        self::assertStringNotContainsString('data-issue-invoice-pdf', $page);
        self::assertStringNotContainsString('Izdaj / otvori račun', $page);
        self::assertStringContainsString('Otvori aktivni račun', $page);
        self::assertStringContainsString('data-order-open-debt', $page);
        self::assertStringContainsString('Isporučeno, a dug je otvoren', $page);
        self::assertStringNotContainsString('Finansije su zaključane', $page);
        self::assertMatchesRegularExpression('~<form class="a1-form" method="post" action="[^"]+/admin/orders/'.$order->id.'/payments" aria-label="Evidentiraj uplatu" data-payment-record-form>~', $page);
        self::assertStringContainsString('a1-pill a1-pill--ok">Isporučena</span>', $page);
    }

    public function test_one_payment_form_for_both_entry_points_with_idempotency_throttle_and_disable_on_submit(): void
    {
        $order = $this->confirmedOrder(['payment_method' => 'deferred_payment', 'payment_due_at' => now()->addDays(14)]);
        $case = app(ReceivablesService::class)->ensureForOrder($order->fresh(), $this->admin);
        self::assertInstanceOf(ReceivableCase::class, $case);

        foreach (['admin.orders.payments.store', 'admin.receivables.payments.store'] as $name) {
            $middleware = Route::getRoutes()->getByName($name)->gatherMiddleware();
            self::assertContains('throttle:admin-write', $middleware, $name);
        }
        foreach (['/admin/orders/'.$order->id, '/admin/receivables/'.$case->id] as $url) {
            $page = (string) $this->actingAs($this->super)->get($url)->assertOk()->getContent();
            self::assertSame(1, substr_count($page, 'data-payment-record-form'), $url);
            self::assertMatchesRegularExpression('~name="idempotency_key" value="[0-9a-f-]{36}"~', $page);
            self::assertStringNotContainsString('data-ux-allow-multiple-submit', (string) preg_replace('~.*?(<form[^>]*data-payment-record-form[^>]*>).*~s', '$1', $page), 'the form uses the global disable-on-submit');
            self::assertStringContainsString('<option value="cash_on_delivery"', $page);
        }

        $key = '11111111-2222-4333-8444-555555555555';
        $body = ['idempotency_key' => $key, 'amount_rsd' => '500.00', 'payment_method' => 'cod', 'paid_at' => now()->format('Y-m-d\TH:i'), 'reference' => 'DUP'];
        $this->actingAs($this->super)->post('/admin/orders/'.$order->id.'/payments', $body)->assertSessionHasNoErrors();
        $this->actingAs($this->super)->post('/admin/orders/'.$order->id.'/payments', $body)->assertSessionHasNoErrors();
        self::assertSame(1, OrderPayment::query()->where('order_id', $order->id)->count(), 'a duplicate submit of the order form is one row');
        self::assertSame('cash_on_delivery', OrderPayment::query()->sole()->payment_method);

        $other = ['idempotency_key' => '99999999-2222-4333-8444-555555555555', 'amount_rsd' => '300.00', 'payment_method' => 'cash_on_delivery', 'paid_at' => now()->format('Y-m-d\TH:i')];
        $this->actingAs($this->super)->post('/admin/receivables/'.$case->id.'/payments', $other)->assertSessionHasNoErrors();
        $this->actingAs($this->super)->post('/admin/receivables/'.$case->id.'/payments', $other)->assertSessionHasNoErrors();
        self::assertSame(2, OrderPayment::query()->where('order_id', $order->id)->count());
        self::assertSame(['cash_on_delivery', 'cash_on_delivery'], OrderPayment::query()->orderBy('id')->pluck('payment_method')->all());
        // Isti kontrakt validacije na oba ulaza.
        $this->actingAs($this->super)->post('/admin/receivables/'.$case->id.'/payments', ['amount_rsd' => '0'] + $other)->assertSessionHasErrors('amount_rsd');
        $this->actingAs($this->super)->post('/admin/orders/'.$order->id.'/payments', ['amount_rsd' => '0'] + $body)->assertSessionHasErrors('amount_rsd');
        $this->actingAs($this->super)->post('/admin/receivables/'.$case->id.'/payments', array_diff_key($other, ['idempotency_key' => 1]))->assertSessionHasErrors('idempotency_key');
        self::assertSame(2, OrderPayment::query()->where('order_id', $order->id)->count());
        // Obrazac porudžbine bez ključa se odbija i ne zove servis.
        $this->actingAs($this->super)->post('/admin/orders/'.$order->id.'/payments', array_diff_key($body, ['idempotency_key' => 1]))->assertSessionHasErrors('idempotency_key');
        self::assertSame(2, OrderPayment::query()->where('order_id', $order->id)->count());
    }

    public function test_api_payment_requires_key_and_same_key_is_one_row(): void
    {
        $order = $this->confirmedOrder(['payment_method' => 'bank_transfer']);
        Sanctum::actingAs($this->admin, ['*']);
        $url = '/api/v1/admin/orders/'.$order->id.'/payments';
        $body = ['entry_type' => 'payment', 'amount_rsd' => '700.00', 'payment_method' => 'bank_transfer', 'paid_at' => now()->format('Y-m-d\TH:i')];

        $this->postJson($url, $body)->assertStatus(422)->assertJsonValidationErrors('idempotency_key');
        self::assertSame(0, OrderPayment::query()->where('order_id', $order->id)->count());

        $this->postJson($url, $body + ['idempotency_key' => 'api-pay-key-1'])->assertSuccessful();
        $this->postJson($url, $body + ['idempotency_key' => 'api-pay-key-1'])->assertSuccessful();
        self::assertSame(1, OrderPayment::query()->where('order_id', $order->id)->count(), 'same key is one ledger row');
    }

    public function test_refund_cannot_exceed_net_paid_also_after_delivery(): void
    {
        $order = $this->confirmedOrder(['payment_method' => 'bank_transfer']);
        $payments = app(OrderPaymentService::class);
        $base = ['payment_method' => 'bank_transfer', 'paid_at' => now()->toDateTimeString()];
        $payments->record($order->fresh(), ['entry_type' => 'payment', 'amount_rsd' => 500] + $base, $this->admin);

        try {
            $payments->record($order->fresh(), ['entry_type' => 'refund', 'amount_rsd' => 600] + $base, $this->admin);
            self::fail('refund above net paid must be rejected');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('amount_rsd', $e->errors());
        }
        self::assertSame(1, OrderPayment::query()->where('order_id', $order->id)->count());

        $done = app(OrderWorkflowService::class)->complete($order->fresh(), $this->admin, ['delivery_method' => 'own_transport', 'recipient_name' => 'Kupac']);
        try {
            $payments->record($done->fresh(), ['entry_type' => 'refund', 'amount_rsd' => 500.01] + $base, $this->admin);
            self::fail('refund above net paid must be rejected after delivery too');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('amount_rsd', $e->errors());
        }
        $payments->record($done->fresh(), ['entry_type' => 'refund', 'amount_rsd' => 500] + $base, $this->admin);
        self::assertSame(2, OrderPayment::query()->where('order_id', $order->id)->count());
        try {
            $payments->record($done->fresh(), ['entry_type' => 'refund', 'amount_rsd' => 1] + $base, $this->admin);
            self::fail('nothing left to refund');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('amount_rsd', $e->errors());
        }
    }

    public function test_repeated_complete_and_cancel_do_not_renotify(): void
    {
        $order = $this->confirmedOrder(['payment_method' => 'bank_transfer']);
        $workflow = app(OrderWorkflowService::class);
        $done = $workflow->complete($order->fresh(), $this->admin, ['delivery_method' => 'own_transport', 'recipient_name' => 'Kupac']);
        $notified = $this->buyer->notifications()->count();
        $outbox = DB::table('order_email_outbox')->count();
        $workflow->complete($done->fresh(), $this->admin, ['delivery_method' => 'own_transport']);
        self::assertSame($notified, $this->buyer->notifications()->count());
        self::assertSame($outbox, DB::table('order_email_outbox')->count());

        $second = $this->confirmedOrder();
        $workflow->cancel($second->fresh(), $this->admin, 'Kupac odustao.');
        $notified = $this->buyer->notifications()->count();
        $outbox = DB::table('order_email_outbox')->count();
        $workflow->cancel($second->fresh(), $this->admin, 'Kupac odustao.');
        self::assertSame($notified, $this->buyer->notifications()->count());
        self::assertSame($outbox, DB::table('order_email_outbox')->count());
    }

    public function test_returned_shipment_is_not_sent_to_buyer_and_hidden_in_buyer_view(): void
    {
        $order = $this->confirmedOrder();
        app(OrderShipmentService::class)->record($order->fresh(), $this->admin, ['shipment_method' => 'courier', 'courier_service_id' => $this->courier()->id, 'tracking_number' => 'BUY-TRK-77', 'recipient_name' => 'Kupac'], null, $this->token($order));
        $this->actingAs($this->buyer)->get('/orders/'.$order->id)->assertOk()->assertSee('BUY-TRK-77');
        $notified = $this->buyer->notifications()->count();

        app(OrderShipmentService::class)->markReturned($order->fresh(), $this->super, 'Interni razlog povrata XYZ.');

        self::assertSame($notified, $this->buyer->notifications()->count(), 'buyer gets no return notification');
        $this->actingAs($this->buyer)->get('/orders/'.$order->id)->assertOk()->assertDontSee('BUY-TRK-77')->assertDontSee('Interni razlog povrata XYZ.');
    }

    public function test_api_complete_without_method_takes_it_from_active_shipment(): void
    {
        $order = $this->confirmedOrder(['payment_method' => 'bank_transfer']);
        app(OrderShipmentService::class)->record($order->fresh(), $this->admin, ['shipment_method' => 'courier', 'courier_service_id' => $this->courier()->id, 'tracking_number' => 'API-C-1', 'recipient_name' => 'Primalac Kurir'], null, $this->token($order));
        Sanctum::actingAs($this->admin, ['*']);

        $this->postJson('/api/v1/admin/orders/'.$order->id.'/complete', [])->assertOk();
        self::assertSame('courier', DB::table('order_deliveries')->where('order_id', $order->id)->value('delivery_method'));
    }

    public function test_open_after_sales_on_behalf_of_the_buyer_role_matrix_and_recorded_opener(): void
    {
        $delivered = $this->confirmedOrder();
        app(OrderWorkflowService::class)->complete($delivered->fresh(), $this->admin, ['delivery_method' => 'own_transport', 'recipient_name' => 'Kupac']);
        $open = $this->confirmedOrder();
        $url = '/admin/orders/'.$delivered->id.'/after-sales';
        $payload = [
            'case_type' => 'complaint', 'priority' => 'normal', 'subject' => 'Kupac javio kvar telefonom',
            'description' => 'Kupac je telefonom prijavio da uređaj ne radi nakon dva dana korišćenja.',
            'items' => [(int) $delivered->items()->value('id') => ['selected' => '1', 'quantity' => 1]],
        ];

        // Dugme: odgovorno lice i superadmin na isporučenoj porudžbini; ne na neisporučenoj; ne drugom adminu.
        $this->actingAs($this->admin)->get('/admin/orders/'.$delivered->id)->assertOk()->assertSee('Otvori reklamaciju / servis');
        $this->actingAs($this->super)->get('/admin/orders/'.$delivered->id)->assertOk()->assertSee('Otvori reklamaciju / servis');
        $this->actingAs($this->admin)->get('/admin/orders/'.$open->id)->assertOk()->assertDontSee('Otvori reklamaciju / servis');
        $this->actingAs($this->admin)->get('/admin/orders/'.$open->id.'/after-sales/create')->assertNotFound();
        $this->actingAs($this->otherAdmin)->get('/admin/orders/'.$delivered->id.'/after-sales/create')->assertNotFound();
        $this->actingAs($this->buyer)->get('/admin/orders/'.$delivered->id.'/after-sales/create')->assertForbidden();
        $this->actingAs($this->buyer)->post($url, $payload)->assertForbidden();
        $this->actingAs($this->otherAdmin)->post($url, $payload)->assertNotFound();
        self::assertSame(0, AfterSalesCase::query()->count());

        $this->actingAs($this->admin)->get('/admin/orders/'.$delivered->id.'/after-sales/create')->assertOk()->assertSee('Otvaraš slučaj u ime kupca');
        $this->actingAs($this->admin)->post($url, $payload)->assertSessionHasNoErrors()->assertRedirect();
        $case = AfterSalesCase::query()->sole();
        self::assertSame([$delivered->id, $this->admin->id, 'open'], [(int) $case->order_id, (int) $case->opened_by, $case->status]);
        $history = \App\Models\AfterSalesStatusHistory::query()->where('after_sales_case_id', $case->id)->first();
        self::assertNotNull($history);
        self::assertStringContainsString('u ime kupca', (string) $history->note);
        $meta = is_array($history->metadata_json) ? $history->metadata_json : (array) json_decode((string) $history->metadata_json, true);
        self::assertSame((int) $delivered->user_id, (int) $meta['opened_on_behalf_of']);
        // Kupac vidi slučaj na svojoj porudžbini, kao da ga je otvorio sam.
        $this->actingAs($this->buyer)->get('/after-sales/'.$case->id)->assertOk();
        $this->actingAs($this->super)->post($url, ['subject' => 'Servis'] + $payload)->assertSessionHasNoErrors();
        self::assertSame(2, AfterSalesCase::query()->count());
    }

    public function test_buyer_mails_received_with_payment_details_shipped_with_tracking_delivered_with_invoice_and_no_confirmed_mail(): void
    {
        $order = $this->confirmedOrder(['payment_method' => 'bank_transfer', 'bank_account_number_display_snapshot' => '160-0000000000000-01', 'payment_recipient_name_snapshot' => 'Ald1n Test', 'payment_reference_snapshot' => '97 12345']);
        DB::table('order_email_outbox')->delete(); // red nastao pri kreiranju (bez podataka za uplatu) ne sme da zaseni novi
        app(OrderEmailOutboxService::class)->orderCreated($order->fresh(), $this->buyer);
        $created = DB::table('order_email_outbox')->where('event_type', 'order_created')->where('recipient_user_id', $this->buyer->id)->first();
        self::assertNotNull($created);
        self::assertStringContainsString('Podaci za uplatu: račun 160-0000000000000-01, primalac Ald1n Test, poziv na broj 97 12345.', (string) $created->message);
        self::assertStringContainsString('/orders/'.$order->id, (string) $created->action_url);

        $courier = $this->courier();
        app(OrderShipmentService::class)->record($order->fresh(), $this->admin, ['shipment_method' => 'courier', 'courier_service_id' => $courier->id, 'tracking_number' => 'MAIL-TRK-9', 'recipient_name' => 'Kupac'], null, $this->token($order));
        $shipped = $this->buyer->notifications()->get()->filter(static fn ($n): bool => str_contains((string) json_encode($n->toArray(), JSON_UNESCAPED_UNICODE), 'MAIL-TRK-9'))->count();
        self::assertGreaterThanOrEqual(1, $shipped, 'the buyer is told the tracking number');

        app(OrderWorkflowService::class)->complete($order->fresh(), $this->admin, ['delivery_method' => 'courier', 'recipient_name' => 'Kupac']);
        self::assertSame(1, DB::table('order_email_outbox')->where('event_type', 'order_completed')->where('recipient_user_id', $this->buyer->id)->where('attach_active_invoice', 1)->count());
        self::assertSame(0, DB::table('order_email_outbox')->where('event_type', 'order_status_changed')->where('metadata_json', 'like', '%"status":"confirmed"%')->count());
    }

    public function test_mobile_complete_accepts_unpaid_orders_and_rejects_courier_without_shipment(): void
    {
        $order = $this->confirmedOrder(['payment_method' => 'bank_transfer']);
        Sanctum::actingAs($this->admin, ['*']);

        $this->postJson('/api/v1/admin/orders/'.$order->id.'/complete', ['delivery_method' => 'courier'])->assertStatus(422)->assertJsonValidationErrors('delivery_method');
        $this->postJson('/api/v1/admin/orders/'.$order->id.'/complete', ['delivery_method' => 'own_transport'])->assertOk()->assertJsonPath('data.action', 'complete')->assertJsonPath('data.payment_state', 'unpaid');
        self::assertNotNull($order->fresh()->completed_at);
        self::assertSame('unpaid', $order->fresh()->payment_state);
    }

    /** @param array<string,mixed> $attrs */
    private function confirmedOrder(array $attrs = []): Order
    {
        $order = $this->placeOrder();
        if ($attrs !== []) {
            $order->forceFill($attrs)->save();
        }
        app(OrderWorkflowService::class)->confirm($order->fresh(), $this->admin, null, $this->token($order));

        return $order->fresh();
    }

    private function token(Order $order): string
    {
        return app(OrderVersionService::class)->token($order->fresh());
    }

    private function courier(): CourierService
    {
        return CourierService::query()->create([
            'name' => 'B1121 Kurir '.++$this->seq, 'slug' => 'b1121-kurir-'.$this->seq, 'tracking_url' => 'https://tracking.example.test/p',
            'is_active' => true, 'is_default' => false, 'sort_order' => 9,
        ]);
    }

    private function root(string $html): string
    {
        $start = strpos($html, 'class="a1-ops"');
        self::assertNotFalse($start, 'page root missing');

        return substr($html, $start);
    }

    /** Porudžbina SubAgenta (cena 1.000 RSD x 2 = 2.000 RSD, provizija 20,00 EUR) kroz pravi OrderService. */
    private function placeOrder(): Order
    {
        $n = ++$this->seq;
        $product = Product::query()->first() ?? Product::query()->create([
            'sku' => 'B1121-1', 'name' => 'B1121 artikal', 'slug' => 'b1121-1', 'price_amount' => 1000, 'price_currency' => 'RSD',
            'description' => 'Test artikal.', 'stock_quantity' => 50, 'status' => 'active', 'created_by' => $this->super->id,
        ]);
        $order = app(OrderService::class)->create($this->buyer, [
            'supplier_user_id' => $this->admin->id,
            'shipping_full_name' => 'Kupac Test', 'shipping_address' => 'Adresa 1', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '060123456', 'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ], 'b1121-order-'.$n);
        DB::table('order_items')->where('order_id', $order->id)->update(['commission_unit_eur_snapshot' => '10.00', 'commission_total_eur_snapshot' => '20.00']);
        OrderCommission::query()->where('order_id', $order->id)->update(['total_eur' => '20.00']);

        return $order->fresh();
    }

    private function user(string $username, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $username, 'email' => $username.'@example.test', 'first_name' => 'B', 'last_name' => ucfirst($username),
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
    }
}
