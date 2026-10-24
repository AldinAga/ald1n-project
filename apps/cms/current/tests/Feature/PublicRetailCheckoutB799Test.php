<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\PublicCheckoutAttempt;
use App\Models\PublicOrderAccess;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class PublicRetailCheckoutB799Test extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        config()->set('public_storefront.enabled',true);
        config()->set('public_storefront.checkout_enabled',true);
        config()->set('public_storefront.shipping_description','Besplatna dostava na adresu.');
        config()->set('public_storefront.shipping_fee_rsd','0');
        config()->set('public_storefront.terms_url','https://cms.ald1n.com/uslovi');
        config()->set('public_storefront.privacy_url','https://cms.ald1n.com/privatnost');
        config()->set('public_storefront.support_email','prodaja@example.test');
        config()->set('public_storefront.payment_methods',['cash_on_delivery']);
        app(SettingsService::class)->putMany([
            'documents_company_name'=>'AP Computers Test',
            'documents_company_address'=>'Primer 1, Beograd',
            'documents_company_city'=>'Beograd',
            'documents_company_tax_id'=>'123456789',
        ],null);
        $admin=User::query()->create([
            'role_id'=>Role::query()->where('slug','superadmin')->valueOrFail('id'),
            'username'=>'b799-retail-admin','email'=>'staff@example.test',
            'first_name'=>'Test','password_hash'=>Hash::make('SyntheticOnly123!'),'status'=>'active',
        ]);
        $this->product=Product::query()->create([
            'sku'=>'B799-CART-RETAIL','slug'=>'b799-cart-retail','name'=>'Laptop retail',
            'price_amount'=>12000,'price_currency'=>'RSD',
            'stock_quantity'=>4,'description'=>'Testni retail artikal','purchase_price_rsd'=>5000,
            'manual_commission_eur'=>250,
            'status'=>'active','created_by'=>$admin->id,
        ]);
        Mail::fake();
    }

    public function test_anonymous_checkout_creates_one_real_operational_order_with_no_commission(): void
    {
        [$data,$initialStock]=$this->prepare();
        $response=$this->post('/porucivanje',$data+['price_rsd'=>1,'user_id'=>1])
            ->assertRedirect();
        $location=$response->headers->get('Location');
        self::assertStringContainsString('/porudzbina/',$location);
        self::assertSame(1,Order::query()->count());
        $order=Order::query()->sole();
        self::assertNull($order->user_id);
        self::assertSame('public_retail',$order->sales_channel);
        self::assertSame('new',$order->status);
        self::assertSame('reserved',$order->inventory_state);
        self::assertSame('unpaid',$order->payment_state);
        self::assertSame('cash_on_delivery',$order->payment_method);
        self::assertSame(24000.0,(float)$order->subtotal_rsd);
        self::assertSame(2,$order->items->first()->quantity);
        self::assertSame(0.0,(float)$order->items->first()->commission_total_eur_snapshot);
        self::assertSame(0.0,(float)$order->items->first()->commission_unit_eur_snapshot);
        self::assertSame(1,StockMovement::query()->where('order_id',$order->id)->count());
        self::assertSame($initialStock-2,(int)$this->product->fresh()->stock_quantity);
        self::assertSame(0,OrderCommission::query()->count());
        self::assertSame(0,OrderPayment::query()->count());
        self::assertSame(1,PublicOrderAccess::query()->count());
        self::assertSame(1,PublicCheckoutAttempt::query()->count());
        $acceptance=PublicCheckoutAttempt::query()->sole();
        self::assertNotNull($acceptance->terms_accepted_at);
        self::assertSame('https://cms.ald1n.com/uslovi',$acceptance->terms_url);
        self::assertSame('https://cms.ald1n.com/privatnost',$acceptance->privacy_url);
        self::assertSame('Besplatna dostava na adresu.',$acceptance->shipping_description);
        self::assertSame(0.0,(float)$acceptance->shipping_fee_rsd);
        $this->get($location)->assertOk()->assertSee('Laptop retail')
            ->assertDontSee('5000')->assertDontSee('250');
    }

    public function test_duplicate_submission_returns_same_capability_without_rebooking_stock(): void
    {
        [$data]=$this->prepare();
        $first=$this->post('/porucivanje',$data)->assertRedirect();
        $second=$this->post('/porucivanje',$data)->assertRedirect();
        self::assertSame($first->headers->get('Location'),$second->headers->get('Location'));
        self::assertSame(1,Order::query()->count());
        self::assertSame(1,StockMovement::query()->count());
        self::assertSame(2,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_reused_checkout_nonce_cannot_change_buyer_payment_or_cart_after_commit(): void
    {
        [$data]=$this->prepare();
        $this->post('/porucivanje',$data)->assertRedirect();

        // Even a bearer form nonce may only replay the ORIGINAL immutable purchase.
        $this->postJson('/porucivanje',array_replace($data,[
            'shipping_address'=>'Synthetic changed address 99',
        ]))->assertUnprocessable()->assertJsonValidationErrors('checkout');

        self::assertSame(1,Order::query()->count());
        self::assertSame(1,PublicCheckoutAttempt::query()->count());
        self::assertSame(1,PublicOrderAccess::query()->count());
        self::assertSame(1,StockMovement::query()->count());
        self::assertSame(2,(int)$this->product->fresh()->stock_quantity);
        self::assertSame('Test 12',Order::query()->sole()->shipping_address);
    }

    public function test_admin_cancellation_of_guest_retail_order_restores_stock_once_without_commission(): void
    {
        [$data,$initialStock]=$this->prepare();
        $privatePath=$this->post('/porucivanje',$data)->assertRedirect()->headers->get('Location');
        $order=Order::query()->sole();
        $admin=User::query()->where('username','b799-retail-admin')->firstOrFail();
        $workflow=app(\App\Services\OrderWorkflowService::class);

        $cancelled=$workflow->changeStatus($order,'cancelled',$admin,'Synthetic buyer withdrawal.');
        $workflow->changeStatus($cancelled,'cancelled',$admin,'Synthetic idempotent retry.');

        $order->refresh();
        self::assertSame('cancelled',$order->status);
        self::assertSame('returned',$order->inventory_state);
        self::assertSame($initialStock,(int)$this->product->fresh()->stock_quantity);
        self::assertSame(1,StockMovement::query()->where('order_id',$order->id)
            ->where('movement_type','sale')->count());
        self::assertSame(1,StockMovement::query()->where('order_id',$order->id)
            ->where('movement_type','cancelled_order')->count());
        self::assertSame(0,OrderCommission::query()->count());
        self::assertSame(0,OrderPayment::query()->count());
        $this->get($privatePath)->assertOk()->assertSee($order->order_number);
    }

    public function test_guest_retail_order_shipment_tracking_delivery_and_single_verified_cod_payment(): void
    {
        $this->enableSyntheticWarrantyRule();
        [$data,$initialStock]=$this->prepare();
        $privatePath=$this->post('/porucivanje',$data)->assertRedirect()->headers->get('Location');
        $order=Order::query()->sole();
        $admin=User::query()->where('username','b799-retail-admin')->firstOrFail();
        $workflow=app(\App\Services\OrderWorkflowService::class);
        $versions=app(\App\Services\OrderVersionService::class);

        $processing=$workflow->changeStatus($order,'processing',$admin);
        $confirmed=$workflow->changeStatus($processing,'confirmed',$admin,null,$versions->token($processing));
        $courier=\App\Models\CourierService::query()->create([
            'name'=>'B800 Sinteticki Kurir','slug'=>'b800-synthetic-retail-courier',
            'tracking_url'=>'https://tracking.example.test/order',
            'is_active'=>true,'is_default'=>false,'sort_order'=>8,
        ]);
        $shipment=app(\App\Services\OrderShipmentService::class)->record(
            $confirmed,$admin,[
                'shipment_method'=>'courier','courier_service_id'=>$courier->id,
                'shipped_at'=>now()->subMinute()->toDateTimeString(),
                'recipient_name'=>'Sinteticki Kupac',
                'tracking_number'=>'B800-CI-TRACK-111',
            ],null,$versions->token($confirmed)
        );
        self::assertSame('B800-CI-TRACK-111',$shipment->tracking_number_snapshot);
        self::assertSame('shipped',$order->fresh()->status);
        self::assertSame('unpaid',$order->fresh()->payment_state);
        self::assertSame(0,OrderPayment::query()->count(),
            'Shipment must never imply a collected COD payment.');
        $this->get($privatePath)->assertOk()
            ->assertSee('B800-CI-TRACK-111')
            ->assertSee('B800 Sinteticki Kurir')
            ->assertSee('https://tracking.example.test/order')
            ->assertHeader('Referrer-Policy','no-referrer')
            ->assertHeader('Cache-Control','max-age=0, no-store, private');

        $deliveredAt=now()->subMinute()->toDateTimeString();
        $completed=$workflow->complete($order->fresh(),$admin,[
            'delivery_method'=>'courier','delivered_at'=>$deliveredAt,
            'recipient_name'=>'Sinteticki Kupac',
            'delivery_reference'=>'B800-CI-TRACK-111',
        ]);
        self::assertNotNull($completed->completed_at);
        self::assertSame('paid',$completed->payment_state);
        self::assertSame(24000.0,(float)$completed->paid_total_rsd);
        $payment=OrderPayment::query()->sole();
        self::assertSame('verified',$payment->status);
        self::assertSame('cash_on_delivery',$payment->payment_method);
        self::assertSame(24000.0,(float)$payment->amount_rsd);
        $issuance=\App\Models\WarrantyIssuance::query()->sole();
        $warranty=\App\Models\ProductWarranty::query()->sole();
        self::assertSame('activated',$issuance->status);
        self::assertSame($payment->id,$issuance->first_verified_payment_id);
        self::assertSame($issuance->warranty_number,$warranty->warranty_number);
        self::assertSame('Sinteticki B800 garantni uslovi.',$warranty->terms_snapshot);
        self::assertNull($warranty->user_id,
            'Guest buyer cannot acquire a fabricated internal login.');
        self::assertSame(substr($deliveredAt,0,10),$warranty->starts_at->format('Y-m-d'));

        $workflow->complete($completed,$admin,[
            'delivery_method'=>'courier','delivered_at'=>$deliveredAt,
        ]);
        self::assertSame(1,OrderPayment::query()->count(),'COD receipt must not repeat.');
        self::assertSame(1,\App\Models\WarrantyIssuance::query()->count());
        self::assertSame(1,\App\Models\ProductWarranty::query()->count());
        self::assertSame(1,\App\Models\OrderDelivery::query()->count());
        self::assertSame($initialStock-2,(int)$this->product->fresh()->stock_quantity);
        self::assertSame(1,StockMovement::query()->where('order_id',$order->id)->count());
        self::assertSame(0,OrderCommission::query()->count());

        $note=app(\App\Services\OrderDocumentService::class)
            ->issue($completed->fresh(),'delivery_note',$admin);
        self::assertSame('issued',$note->status);
        $this->get($privatePath.'/dokument/'.$note->id)->assertOk()
            ->assertHeader('Content-Type','application/pdf');
        $this->get('/porudzbina/'.str_repeat('b',64).'/dokument/'.$note->id)
            ->assertNotFound();
        $this->get($privatePath)->assertOk()
            ->assertSee('Isporučeno')
            ->assertSee($note->document_number);
    }

    public function test_unpaid_guest_bank_transfer_cannot_be_completed_or_fabricate_a_payment(): void
    {
        $this->enableSyntheticWarrantyRule();
        $account=BankAccount::query()->create([
            'label'=>'Sinteticki B800 racun','recipient_name'=>'AP Computers Test',
            'account_number'=>'160000000000000001',
            'account_number_display'=>'160-0000000000000-01',
            'is_active'=>true,
        ]);
        config()->set('public_storefront.payment_methods',['bank_transfer']);
        config()->set('public_storefront.bank_account_id',$account->id);
        [$data]=$this->prepare();
        $data['payment_method']='bank_transfer';
        $privatePath=$this->post('/porucivanje',$data)->assertRedirect()->headers->get('Location');
        $order=Order::query()->sole();
        $admin=User::query()->where('username','b799-retail-admin')->firstOrFail();
        $workflow=app(\App\Services\OrderWorkflowService::class);
        $versions=app(\App\Services\OrderVersionService::class);
        $processing=$workflow->changeStatus($order,'processing',$admin);
        $confirmed=$workflow->changeStatus($processing,'confirmed',$admin,null,$versions->token($processing));

        try {
            $workflow->complete($confirmed,$admin,[
                'delivery_method'=>'own_transport',
                'delivered_at'=>now()->subMinute()->toDateTimeString(),
            ]);
            self::fail('Unpaid transfer must not be completed automatically.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            self::assertArrayHasKey('completion',$e->errors());
        }
        self::assertNull($order->fresh()->completed_at);
        self::assertSame('unpaid',$order->fresh()->payment_state);
        self::assertSame(0,OrderPayment::query()->count());
        self::assertSame(0,\App\Models\OrderDelivery::query()->count());
        self::assertSame(0,\App\Models\WarrantyIssuance::query()->count());
        self::assertSame(0,\App\Models\ProductWarranty::query()->count());

        $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/payments',[
            'entry_type'=>'payment','amount_rsd'=>'24000.00',
            'payment_method'=>'bank_transfer',
            'paid_at'=>now()->toDateTimeString(),
            'reference'=>'B800-SYNTHETIC-BANK-ACK',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $receipt=OrderPayment::query()->sole();
        self::assertSame('verified',$receipt->status);
        self::assertSame('paid',$order->fresh()->payment_state);
        self::assertSame(24000.0,(float)$order->fresh()->paid_total_rsd);
        $pending=\App\Models\WarrantyIssuance::query()->sole();
        self::assertSame('issued_pending_delivery',$pending->status);
        self::assertNull($pending->activated_warranty_id);
        self::assertSame(0,\App\Models\ProductWarranty::query()->count());
        $workflow->complete($order->fresh(),$admin,[
            'delivery_method'=>'own_transport',
            'delivered_at'=>now()->subMinute()->toDateTimeString(),
        ]);
        self::assertNotNull($order->fresh()->completed_at);
        self::assertSame(1,OrderPayment::query()->count(),
            'Verified transfer must not trigger a duplicate automatic COD entry.');
        $activated=$pending->fresh();
        self::assertSame('activated',$activated->status);
        self::assertSame($activated->warranty_number,
            \App\Models\ProductWarranty::query()->sole()->warranty_number);
        self::assertSame(0,OrderCommission::query()->count());
        $this->get($privatePath)->assertOk()->assertSee('24.000,00');
    }

    public function test_second_separate_checkout_rotates_nonce_and_can_create_a_new_order(): void
    {
        [$first]=$this->prepare();
        $this->post('/porucivanje',$first)->assertRedirect();
        self::assertSame(1,Order::query()->count());

        // Repeated POST of the first purchase is idempotent (covered separately),
        // but adding more articles must allow an entirely new checkout.
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])
            ->assertRedirect('/korpa');
        $form=$this->get('/porucivanje')->assertOk();
        preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$form->getContent(),$matches);
        self::assertNotEmpty($matches[1]??null);
        self::assertNotSame($first['checkout_key'],$matches[1]);
        $second=array_replace($first,['checkout_key'=>$matches[1]]);
        $this->post('/porucivanje',$second)->assertRedirect();
        self::assertSame(2,Order::query()->count());
        self::assertSame(2,StockMovement::query()->count());
        self::assertSame(1,(int)$this->product->fresh()->stock_quantity);
        self::assertSame(0,OrderCommission::query()->count());
    }

    public function test_stock_changed_between_cart_and_checkout_rejects_without_partial_writes(): void
    {
        [$data]=$this->prepare();
        $this->product->update(['stock_quantity'=>1]);
        $this->postJson('/porucivanje',$data)->assertUnprocessable()->assertJsonValidationErrors('cart');
        self::assertSame(0,Order::query()->count());
        self::assertSame(0,PublicOrderAccess::query()->count());
        self::assertSame(0,StockMovement::query()->count());
        self::assertSame(1,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_unaccepted_terms_and_invalid_checkout_key_never_create_orders(): void
    {
        [$data]=$this->prepare();
        $this->postJson('/porucivanje',array_merge($data,['accepted_terms'=>'0']))
            ->assertUnprocessable()->assertJsonValidationErrors('accepted_terms');
        $this->postJson('/porucivanje',array_merge($data,['checkout_key'=>str_repeat('a',48)]))
            ->assertUnprocessable()->assertJsonValidationErrors('checkout');
        self::assertSame(0,Order::query()->count());
    }

    public function test_public_order_integrates_admin_processing_and_private_confirmation_pdf(): void
    {
        [$data]=$this->prepare();
        $location=$this->post('/porucivanje',$data)->assertRedirect()->headers->get('Location');
        $order=Order::query()->sole();
        $admin=User::query()->where('username','b799-retail-admin')->firstOrFail();

        $this->actingAs($admin)->get('/admin/orders/'.$order->id)->assertOk();
        $workflow=app(\App\Services\OrderWorkflowService::class);
        $processing=$workflow->changeStatus($order->fresh(),'processing',$admin);
        self::assertSame('processing',$processing->status);
        $token=app(\App\Services\OrderVersionService::class)->token($processing);
        $confirmed=$workflow->changeStatus($processing,'confirmed',$admin,null,$token);
        self::assertSame('confirmed',$confirmed->status);

        $document=app(\App\Services\OrderDocumentService::class)
            ->issue($confirmed,'order_confirmation',$admin);
        self::assertSame($order->id,$document->order_id);
        $this->get($location)->assertOk()->assertSee('Potvrđena');
        $this->get($location.'/dokument/'.$document->id)->assertOk()
            ->assertHeader('Content-Type','application/pdf');
        $this->get('/porudzbina/'.str_repeat('a',64).'/dokument/'.$document->id)->assertNotFound();
    }

    public function test_private_guest_order_link_can_be_revoked_only_by_authorized_superadmin(): void
    {
        [$data]=$this->prepare();
        $location=$this->post('/porucivanje',$data)->assertRedirect()->headers->get('Location');
        $order=Order::query()->sole();
        $revoke='/admin/orders/'.$order->id.'/public-access/revoke';

        $this->post($revoke)->assertRedirect('/login');
        $this->get($location)->assertOk();

        $admin=User::query()->where('username','b799-retail-admin')->firstOrFail();
        $this->actingAs($admin)->post($revoke)->assertRedirect();
        $this->get($location)->assertNotFound();
        self::assertNotNull(PublicOrderAccess::query()->sole()->revoked_at);
        // Repeated staff actions cannot reactivate the revoked capability.
        $this->post($revoke)->assertRedirect();
        $this->get($location)->assertNotFound();
    }

    public function test_bank_transfer_uses_only_configured_active_account_and_is_not_marked_paid(): void
    {
        $account=BankAccount::query()->create([
            'label'=>'Sintetički račun','recipient_name'=>'AP Computers Test',
            'account_number'=>'160000000000000001',
            'account_number_display'=>'160-0000000000000-01',
            'is_active'=>true,
        ]);
        config()->set('public_storefront.payment_methods',['bank_transfer']);
        config()->set('public_storefront.bank_account_id',$account->id);
        [$data]=$this->prepare();
        $data['payment_method']='bank_transfer';
        $this->post('/porucivanje',$data)->assertRedirect();
        $order=Order::query()->sole();
        self::assertSame((int)$account->id,(int)$order->bank_account_id);
        self::assertSame($account->account_number_display,$order->bank_account_number_display_snapshot);
        self::assertSame('unpaid',$order->payment_state);
        self::assertSame(0,OrderPayment::query()->count());
        self::assertSame(0,OrderCommission::query()->count());
    }

    private function enableSyntheticWarrantyRule(): void
    {
        $rule=\App\Models\WarrantyRule::query()
            ->where('scope_type','global')->orderByDesc('id')->first();
        if ($rule !== null) {
            $rule->update([
                'is_active'=>true,'duration_months'=>12,'duration_days'=>10,
                'priority'=>100,'terms'=>'Sinteticki B800 garantni uslovi.',
            ]);
        } else {
            \App\Models\WarrantyRule::query()->create([
                'name'=>'Sinteticki B800 garantni uslovi','scope_type'=>'global',
                'is_active'=>true,'duration_months'=>12,'duration_days'=>10,
                'priority'=>100,'terms'=>'Sinteticki B800 garantni uslovi.',
            ]);
        }
    }

    /** @return array{array<string,string>,int} */
    private function prepare(): array
    {
        $stock=(int)$this->product->stock_quantity;
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>2])->assertRedirect('/korpa');
        $form=$this->get('/porucivanje')->assertOk();
        preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$form->getContent(),$matches);
        self::assertNotEmpty($matches[1]??null);
        return [[
            'checkout_key'=>$matches[1],
            'shipping_full_name'=>'Sintetički Kupac',
            'email'=>'buyer@example.test','shipping_phone'=>'0601234567',
            'shipping_address'=>'Test 12','shipping_city'=>'Beograd',
            'shipping_postal_code'=>'11000',
            'payment_method'=>'cash_on_delivery','accepted_terms'=>'1',
        ],$stock];
    }
}
