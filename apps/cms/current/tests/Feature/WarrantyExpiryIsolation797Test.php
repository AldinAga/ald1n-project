<?php

declare(strict_types=1);
namespace Tests\Feature;
use App\Models\{ProductWarranty, MobilePushOutbox};
use App\Notifications\OperationalNotification;
use App\Services\{OperationalNotificationService, ExpoPushTransport, OperationalAutomationService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Notification, Http, DB};
use Tests\Concerns\Warranty797Fixtures;
use Tests\TestCase;

final class WarrantyExpiryIsolation797Test extends TestCase
{
    use RefreshDatabase, Warranty797Fixtures;
    protected function setUp(): void { parent::setUp(); $this->seed(CoreAccessSeeder::class); }

    public function test_opted_in_superadmin_receives_expiry_but_unrelated_admin_does_not(): void
    {
        [$order,$seller,$admin,$super]=$this->order797('recipients'); $warranty=$this->warranty797($order);
        foreach ([$seller,$admin,$super] as $user) $this->consent797($user,true);
        Notification::fake();
        foreach ([$seller,$admin,$super] as $user) $this->expiry797($user,$warranty);
        Notification::assertSentTo($seller,OperationalNotification::class,1);
        Notification::assertSentTo($super,OperationalNotification::class,1);
        Notification::assertNotSentTo($admin,OperationalNotification::class);
    }
    public function test_all_four_consent_combinations_are_independent(): void
    {
        foreach ([[false,false],[true,false],[false,true],[true,true]] as $i=>[$onSeller,$onSuper]) {
            [$order,$seller,,$super]=$this->order797('matrix'.$i); $warranty=$this->warranty797($order);
            $this->consent797($seller,$onSeller); $this->consent797($super,$onSuper);
            Notification::fake(); $this->expiry797($seller,$warranty); $this->expiry797($super,$warranty);
            if($onSeller) Notification::assertSentTo($seller,OperationalNotification::class,1); else Notification::assertNotSentTo($seller,OperationalNotification::class);
            if($onSuper) Notification::assertSentTo($super,OperationalNotification::class,1); else Notification::assertNotSentTo($super,OperationalNotification::class);
        }
    }
    public function test_repeated_event_deduplicates_per_recipient_and_opt_in_later_is_not_globally_blocked(): void
    {
        [$order,$seller,,$super]=$this->order797('dedupe'); $warranty=$this->warranty797($order);
        $this->consent797($seller,true); $this->consent797($super,false); Notification::fake();
        $this->expiry797($seller,$warranty); $this->expiry797($seller,$warranty); $this->expiry797($super,$warranty);
        $this->consent797($super,true); $this->expiry797($super,$warranty); $this->expiry797($super,$warranty);
        Notification::assertSentTo($seller,OperationalNotification::class,1); Notification::assertSentTo($super,OperationalNotification::class,1);
        self::assertSame(2,DB::table('warranty_expiry_deliveries')->count());
    }
    public function test_channel_overrides_cannot_enable_a_disabled_channel(): void
    {
        [$order,$seller]=$this->order797('channels'); $warranty=$this->warranty797($order);
        $this->consent797($seller,true,['in_app_enabled'=>false,'email_enabled'=>false,'push_enabled'=>false]);
        Notification::fake(); $this->expiry797($seller,$warranty,['_in_app'=>true,'_email'=>true,'_push'=>true]);
        Notification::assertNothingSent(); self::assertSame(0,MobilePushOutbox::query()->count());
    }
    public function test_changed_warranty_user_id_is_not_ownership_and_superadmin_also_requires_valid_lineage(): void
    {
        [$order,$seller,,$super]=$this->order797('badline'); $warranty=$this->warranty797($order);
        $other=$this->person797('other797'); $warranty->update(['user_id'=>$other->id]);
        foreach ([$seller,$other,$super] as $u) $this->consent797($u,true);
        Notification::fake(); $this->expiry797($other,$warranty); Notification::assertNothingSent();
        [$otherOrder]=$this->order797('mismatch'); $warranty->update(['order_id'=>$otherOrder->id]);
        Notification::fake(); // Exclude unrelated order-created fixture notifications.
        $this->expiry797($super,$warranty); $this->expiry797($seller,$warranty); Notification::assertNothingSent();
    }
    public function test_direct_sale_without_selling_user_notifies_only_opted_in_superadmin(): void
    {
        [$order,$seller,$admin,$super]=$this->order797('anonymous'); $order->update(['user_id'=>null,'sales_channel'=>'direct_sale']);
        $warranty=$this->warranty797($order->fresh());
        foreach ([$seller,$admin,$super] as $u) $this->consent797($u,true);
        Notification::fake(); foreach ([$seller,$admin,$super] as $u) $this->expiry797($u,$warranty);
        Notification::assertSentTo($super,OperationalNotification::class,1); Notification::assertNotSentTo($seller,OperationalNotification::class); Notification::assertNotSentTo($admin,OperationalNotification::class);
    }
    public function test_via_rechecks_opt_out_when_a_notification_was_created_earlier(): void
    {
        [$order,$seller]=$this->order797('mailguard'); $warranty=$this->warranty797($order); $this->consent797($seller,true);
        $notification=new OperationalNotification(['event'=>'warranty.expiring','warranty_id'=>$warranty->id,'_in_app'=>true,'_email'=>true]);
        $this->consent797($seller,false);
        self::assertSame([],$notification->via($seller));
    }
    public function test_push_transport_rechecks_opt_out_before_any_http_request(): void
    {
        [$order,$seller]=$this->order797('pushguard'); $warranty=$this->warranty797($order);
        $this->consent797($seller,true,['push_enabled'=>true]);
        $device=$seller->mobileDevices()->create(['installation_id'=>'9a783d21-5555-4444-8888-123456789012','platform'=>'android','push_provider'=>'expo',
            'push_token'=>'ExponentPushToken[synthetic797]','push_token_hash'=>hash('sha256','synthetic797'),'notifications_enabled'=>true]);
        $row=MobilePushOutbox::query()->create(['user_id'=>$seller->id,'mobile_device_id'=>$device->id,'provider'=>'expo','event'=>'warranty.expiring',
            'title'=>'Fixture','message'=>'Fixture','data_json'=>['event'=>'warranty.expiring','warranty_id'=>$warranty->id],'status'=>'pending']);
        Http::fake(); $this->consent797($seller,false,['push_enabled'=>true]);
        $result=app(ExpoPushTransport::class)->send($row);
        self::assertSame('error',$result['status']); self::assertSame('WarrantyRecipientIneligible',$result['error_code']); Http::assertNothingSent();
    }
}
