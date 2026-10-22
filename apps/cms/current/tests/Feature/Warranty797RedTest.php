<?php

declare(strict_types=1);
namespace Tests\Feature;

use App\Models\{Order, Product, ProductWarranty, Role, User};
use App\Notifications\OperationalNotification;
use App\Services\{OrderPaymentService, OperationalNotificationService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Notification, Schema};
use Tests\TestCase;

final class Warranty797RedTest extends TestCase
{
    use RefreshDatabase;
    private function user(string $name, string $role): User
    {
        return User::query()->create(['role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'user_group_id'=>$role==='user'?1:null,'username'=>$name,'email'=>$name.'@example.test',
            'first_name'=>'Synthetic','password_hash'=>Hash::make('Test797!'),'status'=>'active']);
    }
    private function fixture(): array
    {
        $this->seed(CoreAccessSeeder::class);
        $seller=$this->user('seller797','user'); $admin=$this->user('admin797','admin'); $super=$this->user('super797','superadmin');
        $p=Product::query()->create(['sku'=>'RED797','slug'=>'red797','name'=>'Synthetic laptop','price_amount'=>12000,
            'price_currency'=>'RSD','stock_quantity'=>10,'status'=>'active','created_by'=>$admin->id]);
        $this->actingAs($seller)->post('/orders',['idempotency_key'=>'red797-order','supplier_user_id'=>$admin->id,
            'shipping_full_name'=>'Synthetic buyer','shipping_address'=>'Fixture','shipping_city'=>'Fixture',
            'shipping_postal_code'=>'11000','shipping_phone'=>'000000000','payment_method'=>'cash_on_delivery',
            'items'=>[['product_id'=>$p->id,'quantity'=>1]]])->assertRedirect()->assertSessionHasNoErrors();
        return [Order::query()->latest('id')->firstOrFail(),$seller,$admin,$super];
    }
    public function test_first_payment_requires_a_numbered_pending_certificate(): void
    {
        [$order,,$admin]=$this->fixture();
        app(OrderPaymentService::class)->record($order,['entry_type'=>'payment','amount_rsd'=>1,
            'payment_method'=>'bank_transfer','paid_at'=>now()],$admin);
        self::assertTrue(Schema::hasTable('warranty_issuances'),'Missing first-payment issuance ledger');
        self::assertDatabaseCount('warranty_issuances',1);
        self::assertDatabaseHas('warranty_issuances',['order_id'=>$order->id,'status'=>'pending_activation']);
    }
    public function test_opted_in_superadmin_requires_a_valid_expiry_notification(): void
    {
        [$order,,$admin,$super]=$this->fixture(); $item=$order->items()->firstOrFail();
        $w=ProductWarranty::query()->create(['warranty_number'=>'GAR797','order_id'=>$order->id,'order_item_id'=>$item->id,
            'user_id'=>$order->user_id,'product_id'=>$item->product_id,'status'=>'active','starts_at'=>today()->subMonths(11),
            'expires_at'=>today()->addDays(5),'duration_months'=>12,'customer_name_snapshot'=>'Fixture','product_name_snapshot'=>'Fixture']);
        $super->notificationPreference()->updateOrCreate([],['in_app_enabled'=>true,'email_enabled'=>false,'push_enabled'=>false,
            'warranty_updates'=>false,'warranty_expiry_alerts'=>true]);
        Notification::fake();
        app(OperationalNotificationService::class)->send($super,['event'=>'warranty.expiring','warranty_id'=>$w->id,
            'title'=>'Fixture expiry','message'=>'Synthetic test']);
        Notification::assertSentTo($super,OperationalNotification::class,1);
    }
}
