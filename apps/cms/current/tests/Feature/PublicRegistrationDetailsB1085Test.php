<?php
declare(strict_types=1);
namespace Tests\Feature;

use App\Models\{Role,User};
use App\Notifications\CustomerPortalInvitationNotification;
use App\Services\{PublicRegistrationMailSenderService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Mail,Notification};
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Owner-confirmed required Kupac details; synthetic users and captured activation only. */
final class PublicRegistrationDetailsB1085Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Notification::fake();
        Mail::fake();
        config()->set('app.url','https://cms.ald1n.com');
        config()->set('services.turnstile.enabled',false);
        config()->set('mail.default','array');
        config()->set('public_storefront.enabled',true);
        config()->set('public_storefront.privacy_url','https://cms.ald1n.com/privatnost');
        $owner=User::query()->create([
            'role_id'=>Role::query()->where('slug','superadmin')->valueOrFail('id'),
            'username'=>'b1085-owner','email'=>'b1085-owner@example.test',
            'password_hash'=>Hash::make('B1085-Synthetic-Only123!'),'status'=>'active',
        ]);
        $address='registration@synthetic.example.test';
        $name='B1085 Synthetic Activation';
        app(SettingsService::class)->putMany([
            'public_storefront_open'=>'1',
            'public_registration_sender_address'=>$address,
            'public_registration_sender_name'=>$name,
            'public_registration_sender_digest'=>PublicRegistrationMailSenderService::fingerprint($address,$name),
            'public_registration_sender_approved_by'=>(string)$owner->id,
            'public_registration_sender_approved_at'=>now()->toISOString(),
        ],$owner->id);
    }

    private function payload(): array
    {
        return [
            'first_name'=>' Test ','last_name'=>' Kupac ',
            'address'=>' Ulica Test 12 ','city'=>' Beograd ',
            'phone'=>' 0601234567 ','email'=>'B1085-BUYER@EXAMPLE.TEST',
            'accepted_privacy'=>'1',
        ];
    }

    #[DataProvider('requiredDetails')]
    public function test_signup_rejects_missing_or_blank_required_detail_without_account_or_mail(string $field,bool $blank): void
    {
        $data=$this->payload();
        if ($blank) $data[$field]='   '; else unset($data[$field]);
        $this->postJson('/registracija',$data)->assertUnprocessable()->assertJsonValidationErrors($field);
        self::assertDatabaseCount('users',1);
        self::assertDatabaseCount('user_activation_tokens',0);
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('stock_movements',0);
        Notification::assertNothingSent();
        Mail::assertNothingSent();
    }

    public static function requiredDetails(): array
    {
        $cases=[];
        foreach (['first_name','last_name','address','city','phone','email'] as $field) {
            $cases[$field.' omitted']=[$field,false];
            $cases[$field.' blank']=[$field,true];
        }
        return $cases;
    }

    public function test_complete_details_are_saved_on_pending_customer_without_privilege_escalation(): void
    {
        $this->post('/registracija',$this->payload()+[
            'role_id'=>Role::query()->where('slug','superadmin')->valueOrFail('id'),
            'status'=>'active','user_group_id'=>1,
        ])->assertRedirect('/registracija');
        $buyer=User::query()->where('email','b1085-buyer@example.test')->sole();
        self::assertTrue($buyer->hasRole('customer'));
        self::assertSame('pending',$buyer->status);
        self::assertSame('Test',$buyer->first_name);
        self::assertSame('Kupac',$buyer->last_name);
        self::assertSame('Ulica Test 12',$buyer->address);
        self::assertSame('Beograd',$buyer->city);
        self::assertSame('0601234567',$buyer->phone);
        self::assertNull($buyer->email_verified_at);
        self::assertNull($buyer->portal_activated_at);
        self::assertNull($buyer->user_group_id);
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('stock_movements',0);
        Notification::assertSentTo($buyer,CustomerPortalInvitationNotification::class);
    }

    public static function privacyPolicySources(): array
    {
        return ['configured fallback'=>[false], 'current CMS setting'=>[true]];
    }

    #[DataProvider('privacyPolicySources')]
    public function test_registration_uses_current_merchant_privacy_link(bool $stored): void
    {
        $fallback='https://cms.ald1n.com/old-config-privacy';
        $current='https://cms.ald1n.com/current-cms-privacy';
        config()->set('public_storefront.privacy_url',$fallback);
        if ($stored) app(SettingsService::class)->putMany(['public_checkout_privacy_url'=>$current],null);
        $html=$this->get('/registracija')->assertOk()->getContent();
        $document=new \DOMDocument();
        $prior=libxml_use_internal_errors(true);
        try { $document->loadHTML($html); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($prior); }
        $links=(new \DOMXPath($document))->query('//form//label[input[@name="accepted_privacy"]]//a');
        self::assertSame(1,$links->length);
        self::assertSame($stored?$current:$fallback,$links->item(0)->getAttribute('href'));
        self::assertDatabaseCount('users',1);
        self::assertDatabaseCount('orders',0);
        Notification::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_registration_form_marks_every_required_detail_as_required(): void
    {
        $html=$this->get('/registracija')->assertOk()->getContent();
        $document=new \DOMDocument();
        $prior=libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prior);
        }
        $xpath=new \DOMXPath($document);
        foreach (['first_name','last_name','address','city','phone','email'] as $field) {
            self::assertSame(1,$xpath->query('//form//input[@name="'.$field.'" and @required]')->length,$field);
        }
    }
}
