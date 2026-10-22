<?php

declare(strict_types=1);
namespace Tests\Concerns;
use App\Models\{Order, OrderPayment, Product, ProductWarranty, Role, User};
use App\Services\OrderPaymentService;
use Illuminate\Support\Facades\Hash;

trait Warranty797Fixtures
{
    private function person797(string $key, string $role = 'user'): User
    {
        return User::query()->create(['role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'user_group_id'=>$role==='user'?1:null, 'username'=>$key, 'email'=>$key.'@example.test',
            'first_name'=>'Synthetic', 'last_name'=>$key, 'password_hash'=>Hash::make('Test797!Password'), 'status'=>'active']);
    }
    private function order797(string $key = 'case', string $method = 'cash_on_delivery'): array
    {
        $seller=$this->person797($key.'-seller'); $admin=$this->person797($key.'-supplier','admin');
        $super=$this->person797($key.'-super','superadmin');
        $product=Product::query()->create(['sku'=>'P797-'.$key, 'slug'=>'p797-'.$key, 'name'=>'Synthetic laptop '.$key,
            'description'=>'Synthetic fixture.','low_stock_threshold'=>1,'price_amount'=>12000,'price_currency'=>'RSD','stock_quantity'=>10,'status'=>'active','created_by'=>$admin->id]);
        $this->actingAs($seller)->post('/orders',['idempotency_key'=>'order797-'.$key,'supplier_user_id'=>$admin->id,
            'shipping_full_name'=>'Synthetic final buyer','shipping_address'=>'Fixture address','shipping_city'=>'Test city',
            'shipping_postal_code'=>'11000','shipping_phone'=>'000000000','payment_method'=>$method,
            'items'=>[['product_id'=>$product->id,'quantity'=>1]]])->assertRedirect()->assertSessionHasNoErrors();
        $order=Order::query()->where('user_id',$seller->id)->latest('id')->firstOrFail();
        return [$order,$seller,$admin,$super,$product];
    }
    private function pay797(Order $order, User $actor, float $amount=1.0, string $type='payment'): OrderPayment
    {
        return app(OrderPaymentService::class)->record($order,['entry_type'=>$type,'amount_rsd'=>$amount,
            'payment_method'=>'bank_transfer','paid_at'=>now()],$actor);
    }
    private function warranty797(Order $order): ProductWarranty
    {
        $item=$order->items()->firstOrFail();
        return ProductWarranty::query()->create(['warranty_number'=>'GAR797-'.$order->id,'order_id'=>$order->id,
            'order_item_id'=>$item->id,'product_id'=>$item->product_id,'user_id'=>$order->user_id,'status'=>'active',
            'starts_at'=>today()->subMonths(11),'expires_at'=>today()->addDays(5),'duration_months'=>12,
            'customer_name_snapshot'=>'Synthetic buyer','product_name_snapshot'=>$item->product_name,'quantity'=>1]);
    }
    private function consent797(User $user, bool $enabled, array $channels=[]): void
    {
        $user->notificationPreference()->updateOrCreate([],array_replace(['in_app_enabled'=>true,'email_enabled'=>false,
            'push_enabled'=>false,'warranty_updates'=>false,'warranty_expiry_alerts'=>$enabled],$channels));
    }
    private function expiry797(User $user, ProductWarranty $warranty, array $extra=[]): void
    {
        app(\App\Services\OperationalNotificationService::class)->send($user,array_replace([
            'event'=>'warranty.expiring','warranty_id'=>$warranty->id,'title'=>'Expiry fixture',
            'message'=>'Synthetic fixture only.'], $extra));
    }
}
