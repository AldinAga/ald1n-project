<?php

declare(strict_types=1);
namespace Tests\Feature;

use App\Models\{WarrantyIssuance,ProductWarranty,Role};
use App\Notifications\OperationalNotification;
use App\Services\{DirectSaleService,OperationalAutomationService,WarrantyService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Notification,DB};
use Tests\Concerns\Warranty797Fixtures;
use Tests\TestCase;

final class Warranty797EndToEndTest extends TestCase
{
    use RefreshDatabase, Warranty797Fixtures;
    protected function setUp(): void
    {
        parent::setUp(); $this->seed(CoreAccessSeeder::class);
        config(['services.operational_notifications.mail_enabled'=>false,'mobile.push.enabled'=>false]);
    }
    public function test_direct_sale_first_installment_issues_one_immediately_active_certificate(): void
    {
        [,,,$super,$product]=$this->order797('installment');
        $input=['quantity'=>1,'sale_price_amount'=>12000,'sale_price_currency'=>'RSD','payment_method'=>'deferred_payment',
            'buyer_name'=>'Synthetic installment buyer','installment_count'=>2,'payment_due_at'=>today()->addDays(30)->toDateString(),
            'first_payment_method'=>'cash','installments'=>[
                ['due_at'=>today()->toDateString(),'amount_rsd'=>1,'note'=>'First confirmed installment'],
                ['due_at'=>today()->addDays(30)->toDateString(),'amount_rsd'=>11999,'note'=>'Remaining balance']]];
        $service=app(DirectSaleService::class); $order=$service->record($product,$super,$input,'b797-first-installment');
        self::assertSame(1.0,(float)$order->paid_total_rsd);
        $issuance=WarrantyIssuance::query()->where('order_id',$order->id)->sole();
        $warranty=ProductWarranty::query()->where('order_id',$order->id)->sole();
        self::assertSame('activated',$issuance->status);
        self::assertSame($warranty->warranty_number,$issuance->warranty_number);
        self::assertSame($warranty->id,$issuance->activated_warranty_id);
        $service->record($product,$super,$input,'b797-first-installment');
        self::assertSame(1,WarrantyIssuance::query()->where('order_id',$order->id)->count());
        self::assertSame(1,ProductWarranty::query()->where('order_id',$order->id)->count());
    }
    public function test_actual_automation_deduplicates_force_and_does_not_suppress_late_admin_opt_in(): void
    {
        [$order,$seller,$admin,$super]=$this->order797('automation'); $w=$this->warranty797($order);
        $this->consent797($seller,true); $this->consent797($admin,true); $this->consent797($super,false);
        Notification::fake();
        $service=app(OperationalAutomationService::class);
        $service->run($super,true); $service->run($super,true);
        $this->consent797($super,true); $service->run($super,true);
        $expiry=static fn ($n): bool => ($n->toArray($seller)['event']??null)==='warranty.expiring';
        self::assertCount(1,Notification::sent($seller,OperationalNotification::class)->filter($expiry));
        self::assertCount(1,Notification::sent($super,OperationalNotification::class)->filter($expiry));
        self::assertCount(0,Notification::sent($admin,OperationalNotification::class)->filter($expiry));
        self::assertSame(2,DB::table('warranty_expiry_deliveries')->where('product_warranty_id',$w->id)->count());
    }
    public function test_deferred_delivery_checks_changed_expiry_role_status_and_channel(): void
    {
        [$order,$seller,,$super]=$this->order797('dispatch'); $w=$this->warranty797($order);
        $this->consent797($seller,true); $this->consent797($super,true);
        $notice=new OperationalNotification(['event'=>'warranty.expiring','warranty_id'=>$w->id,
            'warranty_expires_on'=>$w->expires_at->toDateString(),'_in_app'=>true,'_email'=>true]);
        self::assertTrue($notice->shouldSend($seller,'database'));
        self::assertTrue($notice->shouldSend($super,'database'));
        self::assertFalse($notice->shouldSend($seller,'mail'));
        $super->update(['role_id'=>Role::query()->where('slug','admin')->valueOrFail('id')]);
        self::assertFalse($notice->shouldSend($super,'database'));
        $seller->update(['status'=>'blocked']); self::assertFalse($notice->shouldSend($seller,'database'));
        $seller->update(['status'=>'active']); $w->update(['expires_at'=>today()->addDays(10)]);
        self::assertFalse($notice->shouldSend($seller,'database'));
    }
    public function test_actual_pending_and_active_document_bytes_are_exported_only_for_synthetic_qa(): void
    {
        [$order,$seller,$admin,$super]=$this->order797('document'); $this->pay797($order,$admin);
        $issuance=WarrantyIssuance::query()->sole();
        $this->actingAs($super);
        $page=$this->get('/warranty-issuances/'.$issuance->id)->assertOk();
        $pdf=$this->get('/warranty-issuances/'.$issuance->id.'.pdf')->assertOk();
        self::assertStringContainsString('Po evidentiranoj isporuci',$page->getContent());
        self::assertStringStartsWith('%PDF',$pdf->getContent());
        $order->update(['completed_at'=>today()->addDays(3),'status'=>'shipped']);
        app(WarrantyService::class)->ensureForOrder($order->fresh(),$admin);
        $active=ProductWarranty::query()->sole();
        $this->get('/warranty-issuances/'.$issuance->id)->assertRedirect(route('admin.warranties.show',$active));
        $activePdf=$this->get(route('warranties.pdf',$active))->assertOk();
        if (($dir=getenv('B797_EVIDENCE_DIR')) !== false && $dir !== '') {
            self::assertSame('testing',app()->environment());
            self::assertDirectoryExists($dir);
            file_put_contents($dir.'/pending-certificate.html',$page->getContent());
            file_put_contents($dir.'/pending-certificate.pdf',$pdf->getContent());
            file_put_contents($dir.'/active-certificate.pdf',$activePdf->getContent());
        }
    }
}
