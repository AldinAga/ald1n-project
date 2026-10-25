<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Order,OrderPayment,Product,ProductWarranty,Role,User,WarrantyIssuance,WarrantyRule};
use App\Notifications\OperationalNotification;
use App\Services\OrderPaymentService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Mail,Notification};
use Tests\TestCase;

/**
 * B902/M10: a verified payment or a voided verified payment is one business
 * state transition. A repeated request must not re-deliver an event; later
 * genuinely distinct payments must still notify normally.
 *
 * Real PHP8.4/MariaDB transaction, no real SMTP, no real customer or stock.
 */
final class PaymentTransitionNotificationB902Test extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private User $operator;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        Notification::fake();
        Mail::fake();
        $rule=WarrantyRule::query()->where('scope_type','global')->orderByDesc('id')->first();
        if ($rule !== null) {
            $rule->update([
                'duration_months'=>12,'duration_days'=>0,'is_active'=>true,
                'priority'=>100,'terms'=>'B902 disposable synthetic warranty terms',
            ]);
        } else {
            WarrantyRule::query()->create([
                'name'=>'B902 disposable global rule','scope_type'=>'global',
                'duration_months'=>12,'duration_days'=>0,'is_active'=>true,
                'priority'=>100,'terms'=>'B902 disposable synthetic warranty terms',
            ]);
        }
        $this->buyer=$this->user('b902-buyer','user');
        $this->operator=$this->user('b902-operator','admin');
        $product=Product::query()->create([
            'sku'=>'B902-SYNTHETIC','slug'=>'b902-disposable-item',
            'name'=>'B902 synthetic item','description'=>'CI only, no sale',
            'price_amount'=>12000,'price_currency'=>'RSD','stock_quantity'=>3,
            'status'=>'active','created_by'=>$this->operator->id,
        ]);
        $this->order=Order::query()->create([
            'source_system'=>'laravel',
            'order_number'=>'APC-B902-SYNTHETIC',
            'user_id'=>$this->buyer->id,
            'supplier_user_id'=>$this->operator->id,
            'supplier_name_snapshot'=>$this->operator->displayName(),
            'supplier_role_snapshot'=>'Administrator',
            'status'=>'new','inventory_state'=>'reserved',
            'shipping_full_name'=>'B902 Synthetic Buyer',
            'shipping_address'=>'Synthetic Street 10',
            'shipping_city'=>'Test City','shipping_postal_code'=>'11000',
            'shipping_phone'=>'0600000000',
            'subtotal_rsd'=>12000,
            'payment_method'=>'bank_transfer','payment_status'=>'pending',
            'payment_state'=>'unpaid','paid_total_rsd'=>0,
        ]);
        $this->order->items()->create([
            'product_id'=>$product->id,'product_sku'=>$product->sku,
            'product_name'=>$product->name,'quantity'=>1,
            'unit_price_original'=>12000,'original_currency'=>'RSD',
            'unit_price_rsd'=>12000,'line_total_rsd'=>12000,
        ]);
    }

    public function test_verification_retries_emit_one_event_and_one_unchanged_pending_warranty(): void
    {
        $payment=$this->proof('b902-payment-one',1000);
        $service=app(OrderPaymentService::class);
        self::assertSame('verified',$service->verify($payment,$this->operator)->status);
        $first=WarrantyIssuance::query()->sole();
        $events=$this->events();
        self::assertSame(['order.payment_verified'],$events);
        self::assertSame('issued_pending_delivery',$first->status);
        self::assertDatabaseCount('product_warranties',0);

        self::assertSame('verified',$service->verify($payment->fresh(),$this->operator)->status);
        self::assertSame('verified',$service->verify($payment->fresh(),$this->operator)->status);
        self::assertSame(['order.payment_verified'],$this->events(),
            'Repeated verify must not send an additional in-app or email event.');
        self::assertDatabaseCount('order_payments',1);
        self::assertDatabaseCount('warranty_issuances',1);
        self::assertDatabaseCount('product_warranties',0);
        self::assertSame($first->warranty_number,WarrantyIssuance::query()->sole()->warranty_number);
        self::assertSame((int)$payment->id,(int)$first->first_verified_payment_id);
    }

    public function test_void_retries_notify_only_the_first_transition(): void
    {
        $payment=$this->proof('b902-void-one',1000);
        $service=app(OrderPaymentService::class);
        $verified=$service->verify($payment,$this->operator);
        self::assertSame('voided',$service->void($verified,$this->operator)->status);
        self::assertSame(
            ['order.payment_verified','order.payment_voided'],
            $this->events()
        );
        self::assertSame('voided',$service->void($payment->fresh(),$this->operator)->status);
        self::assertSame('voided',$service->void($payment->fresh(),$this->operator)->status);
        self::assertSame(
            ['order.payment_verified','order.payment_voided'],
            $this->events(),
            'Repeated void must not duplicate the buyer notification.'
        );
        self::assertDatabaseCount('order_payments',1);
        self::assertDatabaseCount('warranty_issuances',1);
        self::assertDatabaseCount('product_warranties',0);
    }

    public function test_second_distinct_verified_installment_notifies_again_but_does_not_reissue_warranty(): void
    {
        $service=app(OrderPaymentService::class);
        $first=$this->proof('b902-installment-one',900);
        $second=$this->proof('b902-installment-two',400);
        $service->verify($first,$this->operator);
        $warranty=WarrantyIssuance::query()->sole()->warranty_number;
        $service->verify($first->fresh(),$this->operator);
        $service->verify($second,$this->operator);
        self::assertSame(
            ['order.payment_verified','order.payment_verified'],
            $this->events(),
            'A genuinely distinct verified payment must still deliver its own event.'
        );
        self::assertDatabaseCount('order_payments',2);
        self::assertDatabaseCount('warranty_issuances',1);
        self::assertSame($warranty,WarrantyIssuance::query()->sole()->warranty_number);
        self::assertDatabaseCount('product_warranties',0);
    }

    private function proof(string $number,float $amount): OrderPayment
    {
        return OrderPayment::query()->create([
            'order_id'=>$this->order->id,
            'payment_number'=>$number,
            'entry_type'=>'payment','status'=>'submitted',
            'amount_rsd'=>$amount,'payment_method'=>'bank_transfer',
            'paid_at'=>now(),'submitted_by'=>$this->buyer->id,
        ]);
    }

    /** @return list<string> */
    private function events(): array
    {
        return Notification::sent($this->buyer,OperationalNotification::class)
            ->map(static fn(OperationalNotification $n): string=>
                (string)($n->toArray(new \stdClass())['event']??''))
            ->values()->all();
    }

    private function user(string $username,string $slug): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$slug)->valueOrFail('id'),
            'user_group_id'=>$slug==='user'?1:null,
            'username'=>$username,
            'email'=>$username.'@synthetic.example.test',
            'first_name'=>'B902 Test','password_hash'=>Hash::make('DisposableOnlyB902!'),
            'status'=>'active',
        ]);
    }
}
