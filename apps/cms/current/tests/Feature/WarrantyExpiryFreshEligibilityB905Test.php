<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{NotificationPreference,Order,Product,ProductWarranty,Role,User};
use App\Notifications\OperationalNotification;
use App\Services\{OperationalNotificationService,WarrantyExpiryRecipientPolicy};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Notification};
use Tests\TestCase;

/**
 * B905-M11 live eligibility: an earlier authorization or an Eloquent-loaded
 * association may not preserve expiry consent after the underlying DB changes.
 * Synthetic MariaDB only, no scheduler, SMTP, production buyers or real pushes.
 */
final class WarrantyExpiryFreshEligibilityB905Test extends TestCase
{
    use RefreshDatabase;

    private User $seller;
    private User $admin;
    private User $other;
    private Order $order;
    private ProductWarranty $warranty;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        Notification::fake();
        $this->seller=$this->user('b905-original-seller','user');
        $this->admin=$this->user('b905-opted-superadmin','superadmin');
        $this->other=$this->user('b905-other-admin','admin');
        foreach ([$this->seller,$this->admin,$this->other] as $user) {
            $this->optIn($user);
        }
        $product=Product::query()->create([
            'sku'=>'B905-SYNTHETIC-SKU','slug'=>'b905-synthetic-item',
            'name'=>'Disposable B905 item','description'=>'Synthetic warranty test',
            'price_amount'=>12000,'price_currency'=>'RSD','stock_quantity'=>1,
            'status'=>'active','created_by'=>$this->admin->id,
        ]);
        $this->order=Order::query()->create([
            'source_system'=>'laravel','order_number'=>'B905-SYNTHETIC-ORDER',
            'user_id'=>$this->seller->id,'supplier_user_id'=>$this->admin->id,
            'supplier_name_snapshot'=>$this->admin->displayName(),
            'supplier_role_snapshot'=>'SuperAdministrator',
            'status'=>'new','inventory_state'=>'reserved',
            'shipping_full_name'=>'Synthetic B905 Recipient',
            'shipping_address'=>'Nonproduction Street 1',
            'shipping_city'=>'Synthetic City','shipping_postal_code'=>'11000',
            'shipping_phone'=>'0600000000','subtotal_rsd'=>12000,
            'payment_method'=>'bank_transfer','payment_status'=>'pending',
            'payment_state'=>'unpaid','paid_total_rsd'=>0,
        ]);
        $item=$this->order->items()->create([
            'product_id'=>$product->id,'product_sku'=>$product->sku,
            'product_name'=>$product->name,'quantity'=>1,
            'unit_price_original'=>12000,'original_currency'=>'RSD',
            'unit_price_rsd'=>12000,'line_total_rsd'=>12000,
        ]);
        $this->warranty=ProductWarranty::query()->create([
            'warranty_number'=>'B905-SYNTHETIC-WARRANTY',
            'order_id'=>$this->order->id,
            'order_item_id'=>$item->id,
            'product_id'=>$product->id,
            'user_id'=>$this->seller->id,
            'status'=>'active',
            'starts_at'=>today()->subMonth()->toDateString(),
            'expires_at'=>today()->addMonth()->toDateString(),
            'duration_months'=>2,'duration_days'=>0,
            'quantity'=>1,'serial_numbers_json'=>[],
            'product_sku_snapshot'=>$product->sku,
            'product_name_snapshot'=>$product->name,
            'customer_name_snapshot'=>'Synthetic B905 Recipient',
        ]);
    }

    public function test_live_expiry_opt_in_allows_original_seller_and_superadmin_but_never_other_admin(): void
    {
        $policy=app(WarrantyExpiryRecipientPolicy::class);
        self::assertTrue($policy->allows($this->warranty,$this->seller));
        self::assertTrue($policy->allows($this->warranty,$this->admin));
        self::assertFalse($policy->allows($this->warranty,$this->other));
        self::assertEqualsCanonicalizing(
            [$this->seller->id,$this->admin->id],
            $policy->recipients($this->warranty)->pluck('id')->all()
        );
    }

    public function test_no_implicit_expiry_opt_in_and_existing_warranty_updates_remain_independent(): void
    {
        $this->seller->notificationPreference()->update(['warranty_expiry_alerts'=>false]);
        self::assertFalse(app(WarrantyExpiryRecipientPolicy::class)->allows($this->warranty,$this->seller));
        $preference=NotificationPreference::query()->where('user_id',$this->seller->id)->sole();
        self::assertTrue($preference->warranty_updates);
        self::assertFalse($preference->warranty_expiry_alerts);
        self::assertTrue(app(WarrantyExpiryRecipientPolicy::class)->allows($this->warranty,$this->admin));
    }

    public function test_stale_opted_in_superadmin_cannot_keep_expiry_privilege_after_role_demotion(): void
    {
        $policy=app(WarrantyExpiryRecipientPolicy::class);
        self::assertTrue($policy->allows($this->warranty,$this->admin));
        self::assertTrue($this->admin->hasRole('superadmin'));
        User::query()->whereKey($this->admin->id)->update([
            'role_id'=>Role::query()->where('slug','admin')->valueOrFail('id'),
        ]);
        self::assertFalse($policy->allows($this->warranty,$this->admin),
            'Live permission must supersede the cached SuperAdmin role in an earlier User object.');
        self::assertFalse($policy->recipients($this->warranty)->contains('id',$this->admin->id));
    }

    public function test_stale_opted_in_seller_cannot_receive_expiry_after_deactivation(): void
    {
        $policy=app(WarrantyExpiryRecipientPolicy::class);
        self::assertTrue($policy->allows($this->warranty,$this->seller));
        User::query()->whereKey($this->seller->id)->update(['status'=>'pending']);
        self::assertFalse($policy->allows($this->warranty,$this->seller));
    }

    public function test_stale_opted_in_seller_cannot_receive_expiry_after_retirement(): void
    {
        $policy=app(WarrantyExpiryRecipientPolicy::class);
        self::assertTrue($policy->allows($this->warranty,$this->seller));
        User::query()->whereKey($this->seller->id)->update(['retired_at'=>now()]);
        self::assertFalse($policy->allows($this->warranty,$this->seller));
    }

    public function test_loaded_order_relationship_must_not_override_later_cancellation(): void
    {
        $policy=app(WarrantyExpiryRecipientPolicy::class);
        $this->warranty->load('orderItem.order.user');
        self::assertTrue($policy->allows($this->warranty,$this->seller));
        Order::query()->whereKey($this->order->id)->update(['status'=>'cancelled']);
        self::assertFalse($policy->allows($this->warranty,$this->seller),
            'An already-loaded historical order cannot authorize expiry after cancellation.');
        self::assertFalse($policy->allows($this->warranty,$this->admin));
    }

    public function test_mismatched_product_reference_invalidates_warranty_item_lineage(): void
    {
        $otherProduct=Product::query()->create([
            'sku'=>'B905-DIFFERENT-SYNTHETIC-PRODUCT',
            'slug'=>'b905-synthetic-other',
            'name'=>'Different synthetic item','description'=>'Test only',
            'price_amount'=>1000,'price_currency'=>'RSD','stock_quantity'=>1,
            'status'=>'active','created_by'=>$this->admin->id,
        ]);
        $this->warranty->update(['product_id'=>$otherProduct->id]);
        self::assertFalse(app(WarrantyExpiryRecipientPolicy::class)->allows($this->warranty,$this->seller));
        self::assertFalse(app(WarrantyExpiryRecipientPolicy::class)->allows($this->warranty,$this->admin));
    }

    public function test_actual_notification_send_does_not_deliver_to_stale_demoted_superadmin(): void
    {
        $policy=app(WarrantyExpiryRecipientPolicy::class);
        self::assertTrue($policy->allows($this->warranty,$this->admin));
        User::query()->whereKey($this->admin->id)->update([
            'role_id'=>Role::query()->where('slug','admin')->valueOrFail('id'),
        ]);
        app(OperationalNotificationService::class)->send($this->admin,[
            'event'=>'warranty.expiry_warning',
            'warranty_id'=>$this->warranty->id,
            'title'=>'B905 synthetic expiry',
            'message'=>'Disposable nonproduction alert',
            '_in_app'=>true,'_email'=>false,'_push'=>false,
        ]);
        Notification::assertNotSentTo($this->admin,OperationalNotification::class);
    }

    private function optIn(User $user): void
    {
        $user->notificationPreference()->create([
            'in_app_enabled'=>true,'email_enabled'=>false,'push_enabled'=>false,
            'warranty_updates'=>true,'warranty_expiry_alerts'=>true,
        ]);
    }

    private function user(string $username,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'user_group_id'=>$role==='user'?1:null,
            'username'=>$username,'email'=>$username.'@synthetic.example.test',
            'first_name'=>'Synthetic',
            'password_hash'=>Hash::make('B905OnlySynthetic!'),
            'status'=>'active',
        ]);
    }
}
