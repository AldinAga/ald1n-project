<?php

declare(strict_types=1);
namespace Tests\Feature;
use App\Models\{OrderPayment, ProductWarranty, WarrantyRule, WarrantyIssuance};
use App\Services\{OrderPaymentService, WarrantyService, DirectSaleService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Schema, Notification};
use Tests\Concerns\Warranty797Fixtures;
use Tests\TestCase;

final class FirstPaymentWarranty797Test extends TestCase
{
    use RefreshDatabase, Warranty797Fixtures;
    protected function setUp(): void { parent::setUp(); $this->seed(CoreAccessSeeder::class); Notification::fake(); }

    public function test_first_positive_payment_issues_a_numbered_certificate_without_starting_validity(): void
    {
        [$order,,$admin]=$this->order797('deposit');
        $payment=$this->pay797($order,$admin,1);
        self::assertTrue(Schema::hasTable('warranty_issuances'),'First-payment certificate issuance ledger is missing.');
        $issued=WarrantyIssuance::query()->sole();
        self::assertSame($payment->id,$issued->confirmed_payment_id);
        self::assertSame('pending_activation',$issued->status);
        self::assertStringStartsWith('GAR-',$issued->warranty_number);
        self::assertNotNull($issued->issued_at);
        self::assertSame(0,ProductWarranty::query()->count());
        self::assertArrayNotHasKey('starts_at',$issued->snapshot_json);
        self::assertArrayNotHasKey('expires_at',$issued->snapshot_json);
        self::assertSame(24,$issued->snapshot_json['duration_months']);
        self::assertSame('partial',$order->fresh()->payment_state);
    }
    public function test_unverified_rejected_refund_and_zero_entries_do_not_issue(): void
    {
        [$order,$seller,$admin]=$this->order797('negative');
        $proof=OrderPayment::query()->create(['order_id'=>$order->id,'payment_number'=>'UPL797-unverified',
            'entry_type'=>'payment','status'=>'submitted','amount_rsd'=>1,'payment_method'=>'bank_transfer',
            'paid_at'=>now(),'submitted_by'=>$seller->id]);
        self::assertSame(0,WarrantyIssuance::query()->count());
        app(OrderPaymentService::class)->reject($proof,'Fixture rejection',$admin);
        // A refund needs an existing positive balance; do not exercise the unrelated legacy refunded-ENUM defect.
        WarrantyRule::query()->update(['is_active'=>false]); $this->pay797($order,$admin,5);
        WarrantyRule::query()->update(['is_active'=>true]);
        $this->pay797($order,$admin,1,'refund'); $this->pay797($order,$admin,0);
        self::assertSame(0,WarrantyIssuance::query()->count());
        self::assertSame(0,ProductWarranty::query()->count());
    }
    public function test_verifying_first_proof_and_later_installments_preserve_the_single_number(): void
    {
        [$order,$seller,$admin]=$this->order797('retry');
        $proof=OrderPayment::query()->create(['order_id'=>$order->id,'payment_number'=>'UPL797-proof','entry_type'=>'payment',
            'status'=>'submitted','amount_rsd'=>1,'payment_method'=>'bank_transfer','paid_at'=>now(),'submitted_by'=>$seller->id]);
        $payments=app(OrderPaymentService::class); $payments->verify($proof,$admin);
        $first=WarrantyIssuance::query()->sole()->toArray();
        $payments->verify($proof,$admin); $this->pay797($order,$admin,10);
        self::assertSame(1,WarrantyIssuance::query()->count());
        self::assertSame($first['warranty_number'],WarrantyIssuance::query()->sole()->warranty_number);
        self::assertSame($proof->id,WarrantyIssuance::query()->sole()->confirmed_payment_id);
        self::assertSame(0,ProductWarranty::query()->count());
    }
    public function test_delivery_activates_the_same_number_and_issue_time_with_original_terms(): void
    {
        [$order,,$admin]=$this->order797('activation');
        WarrantyRule::query()->where('scope_type','global')->update(['duration_months'=>12,'terms'=>'Terms at payment']);
        $this->pay797($order,$admin);
        $issued=WarrantyIssuance::query()->sole(); $number=$issued->warranty_number;
        WarrantyRule::query()->where('scope_type','global')->update(['duration_months'=>1,'terms'=>'Changed later']);
        $delivery=today()->addDays(10); $order->update(['completed_at'=>$delivery,'status'=>'shipped']);
        app(WarrantyService::class)->ensureForOrder($order->fresh(),$admin);
        $active=ProductWarranty::query()->sole();
        self::assertSame($number,$active->warranty_number);
        self::assertSame('Terms at payment',$active->terms_snapshot);
        self::assertSame($delivery->toDateString(),$active->starts_at->toDateString());
        self::assertSame($delivery->copy()->addMonthsNoOverflow(12)->toDateString(),$active->expires_at->toDateString());
        self::assertSame('activated',$issued->fresh()->status);
        self::assertSame($active->id,$issued->fresh()->activated_warranty_id);
        self::assertSame($issued->issued_at->format('d.m.Y H:i'),app(\App\Services\Pdf\WarrantyCertificatePayloadService::class)->build($active)['issued_at']);
        app(WarrantyService::class)->ensureForOrder($order->fresh(),$admin);
        self::assertSame(1,ProductWarranty::query()->count());
    }
    public function test_pending_certificate_is_visible_to_owner_and_operator_but_not_other_users(): void
    {
        [$order,$seller,$admin,$super]=$this->order797('access'); $this->pay797($order,$admin);
        $issued=WarrantyIssuance::query()->sole();
        foreach ([$seller,$admin,$super] as $actor) {
            $this->actingAs($actor)->get('/warranty-issuances/'.$issued->id)->assertOk()->assertSee($issued->warranty_number)->assertSee('isporuk');
            $response=$this->get('/warranty-issuances/'.$issued->id.'.pdf');
            $response->assertOk()->assertHeader('content-type','application/pdf');
            self::assertStringStartsWith('%PDF-1.4',$response->getContent());
            self::assertStringContainsString('no-store',(string)$response->headers->get('Cache-Control'));
        }
        $other=$this->person797('outsider'); $otherAdmin=$this->person797('outsider-admin','admin');
        foreach ([$other,$otherAdmin] as $actor) {
            $this->actingAs($actor)->get('/warranty-issuances/'.$issued->id)->assertNotFound();
            $this->get('/warranty-issuances/'.$issued->id.'.pdf')->assertNotFound();
        }
        $this->actingAs($seller)->get('/orders/'.$order->id)->assertOk()->assertSee($issued->warranty_number);
        $this->get('/warranties')->assertOk()->assertSee($issued->warranty_number);
        $this->actingAs($super)->get('/admin/warranties')->assertOk()->assertSee($issued->warranty_number);
    }
    public function test_cancelled_pending_certificate_never_activates_and_pdf_is_gone(): void
    {
        [$order,$seller,$admin]=$this->order797('cancel'); $this->pay797($order,$admin);
        $issued=WarrantyIssuance::query()->sole();
        app(WarrantyService::class)->voidForOrder($order,$admin,'Cancelled fixture');
        self::assertSame('void',$issued->fresh()->status);
        $order->update(['completed_at'=>now(),'status'=>'shipped']);
        app(WarrantyService::class)->ensureForOrder($order->fresh(),$admin);
        self::assertSame(0,ProductWarranty::query()->count());
        $this->actingAs($seller)->get('/warranty-issuances/'.$issued->id.'.pdf')->assertStatus(410);
    }
    public function test_certificate_failure_rolls_back_the_payment_and_projected_totals(): void
    {
        [$order,,$admin]=$this->order797('rollback');
        WarrantyIssuance::creating(static function (): void { throw new \RuntimeException('fixture issuance failure'); });
        try { $this->pay797($order,$admin); self::fail('Expected atomic issuance failure'); }
        catch (\RuntimeException $e) { self::assertSame('fixture issuance failure',$e->getMessage()); }
        finally { WarrantyIssuance::flushEventListeners(); }
        self::assertSame(0,OrderPayment::query()->where('order_id',$order->id)->count());
        self::assertSame(0.0,(float)$order->fresh()->paid_total_rsd);
    }
    public function test_existing_certificate_and_direct_sale_are_not_duplicated_or_redated(): void
    {
        [$order,,$admin,$super,$product]=$this->order797('existing');
        $existing=$this->warranty797($order); $before=$existing->fresh()->toArray();
        $this->pay797($order,$admin);
        self::assertSame($before,$existing->fresh()->toArray());
        self::assertSame(0,WarrantyIssuance::query()->count());
        $input=['quantity'=>1,'sale_price_amount'=>12000,'sale_price_currency'=>'RSD','payment_method'=>'cash','buyer_name'=>'Synthetic direct buyer'];
        $service=app(DirectSaleService::class); $direct=$service->record($product,$super,$input,'direct797-test');
        self::assertSame(1,ProductWarranty::query()->where('order_id',$direct->id)->count());
        $service->record($product,$super,$input,'direct797-test');
        self::assertSame(1,ProductWarranty::query()->where('order_id',$direct->id)->count());
    }
    public function test_no_rule_does_not_invent_a_duration(): void
    {
        [$order,,$admin]=$this->order797('norule'); WarrantyRule::query()->update(['is_active'=>false]);
        $this->pay797($order,$admin);
        self::assertSame(0,WarrantyIssuance::query()->count()); self::assertSame(0,ProductWarranty::query()->count());
    }
    public function test_tampered_pending_lineage_denies_even_superadmin_pdf(): void
    {
        [$order,,$admin,$super]=$this->order797('lineage'); $this->pay797($order,$admin); $issued=WarrantyIssuance::query()->sole();
        [$otherOrder]=$this->order797('otherlineage'); $issued->update(['order_id'=>$otherOrder->id]);
        $this->actingAs($super)->get('/warranty-issuances/'.$issued->id.'.pdf')->assertNotFound();
    }
}
