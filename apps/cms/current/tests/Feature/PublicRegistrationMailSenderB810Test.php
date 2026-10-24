<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Role,User};
use App\Notifications\CustomerPortalInvitationNotification;
use App\Services\{PublicRegistrationMailSenderService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Auth,Hash,Mail,Notification};
use Tests\TestCase;

/** B810: isolated mail-header identity for self-registration, never SMTP secrets. */
final class PublicRegistrationMailSenderB810Test extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Notification::fake();
        Mail::fake();
        config()->set('app.url','https://cms.ald1n.com');
        config()->set('mail.default','array');
        config()->set('mail.from.address','system@ald1n.com');
        config()->set('mail.from.name','Internal CMS');
        config()->set('services.turnstile.enabled',false);
        config()->set('public_storefront.enabled',true);
        config()->set('public_storefront.checkout_enabled',true);
        $this->owner=$this->person('b810-owner','superadmin');
        app(SettingsService::class)->putMany(['public_storefront_open'=>'1'], $this->owner->id);
    }

    public function test_superadmin_is_only_role_allowed_to_set_registration_sender(): void
    {
        $url='/admin/settings/public-storefront/registration-sender';
        $this->get('/admin/settings/public-storefront')->assertRedirect('/login');
        $this->put($url,$this->payload())->assertRedirect('/login');
        $admin=$this->person('b810-admin','admin');
        $this->actingAs($admin)->get('/admin/settings/public-storefront')->assertForbidden();
        $this->put($url,$this->payload())->assertForbidden();
        $customer=$this->person('b810-customer','customer');
        $this->actingAs($customer)->put($url,$this->payload())->assertForbidden();
        self::assertNull(app(PublicRegistrationMailSenderService::class)->sender());
        $this->actingAs($this->owner)->get('/admin/settings/public-storefront')
            ->assertOk()->assertSee('Email za potvrdu registracije Kupca')
            ->assertSee('registration_from_email',false);
    }

    public function test_unconfigured_sender_blocks_self_signup_before_creating_pending_user(): void
    {
        $this->get('/registracija')->assertOk()
            ->assertSee('Registracija trenutno nije dostupna')
            ->assertDontSee('Pošalji registraciju');
        $this->postJson('/registracija',$this->signup())
            ->assertUnprocessable()->assertJsonValidationErrors('registration');
        self::assertDatabaseCount('users',1);
        self::assertDatabaseCount('user_activation_tokens',0);
        Notification::assertNothingSent();
    }

    public function test_superadmin_sender_is_in_activation_mail_only_and_signup_requires_email_activation(): void
    {
        $this->actingAs($this->owner)->put(
            '/admin/settings/public-storefront/registration-sender',$this->payload()
        )->assertRedirect()->assertSessionHasNoErrors();
        $state=app(PublicRegistrationMailSenderService::class)->status();
        self::assertTrue($state['ready']);
        self::assertSame('registracija@apcomputers.rs',$state['address']);
        self::assertSame('AP Computers',$state['name']);
        self::assertSame('system@ald1n.com',config('mail.from.address'),
            'Never mutate application/global SMTP identity or transport.');
        self::assertSame('array',config('mail.default'));
        Auth::logout();
        $this->get('/registracija')->assertOk()->assertSee('Pošalji registraciju');
        $response=$this->withHeaders(['Host'=>'injected.example.test'])->post(
            '/registracija',$this->signup()+[
                'registration_from_email'=>'attacker@example.test',
                'role_id'=>$this->owner->role_id,'status'=>'active',
            ])->assertRedirect('/registracija');
        self::assertSame('/registracija',(string)$response->headers->get('Location'));
        $buyer=User::query()->where('email','b810-buyer@example.test')->sole();
        self::assertTrue($buyer->hasRole('customer'));
        self::assertSame('pending',$buyer->status);
        self::assertNull($buyer->user_group_id);
        self::assertNull($buyer->email_verified_at);
        $messages=[];
        Notification::assertSentTo($buyer,CustomerPortalInvitationNotification::class,
            static function ($notice) use (&$messages,$buyer): bool {
                $messages[]=$notice;
                $mail=$notice->toMail($buyer);
                $from=json_encode($mail->from,JSON_THROW_ON_ERROR);
                self::assertStringContainsString('registracija@apcomputers.rs',$from);
                self::assertStringContainsString('AP Computers',$from);
                self::assertSame('Potvrdite registraciju Kupca',$mail->subject);
                self::assertStringStartsWith('https://cms.ald1n.com/activate-account/',
                    (string)$mail->actionUrl);
                self::assertStringNotContainsString('injected.example.test',(string)$mail->actionUrl);
                return true;
            });
        self::assertCount(1,$messages);
        $legacy=(new CustomerPortalInvitationNotification(str_repeat('a',80),72))
            ->toMail($buyer);
        self::assertSame([], $legacy->from,
            'Admin-generated invitations use existing system MAIL_FROM_* instead.');
        self::assertDatabaseCount('orders',0);
    }

    public function test_sender_update_does_not_revoke_merchant_terms_or_touch_production_flags(): void
    {
        app(SettingsService::class)->putMany([
            'public_checkout_approval_digest'=>'synthetic-unchanged',
            'public_checkout_approved_by'=>(string)$this->owner->id,
        ],$this->owner->id);
        $this->actingAs($this->owner)->put(
            '/admin/settings/public-storefront/registration-sender',$this->payload()
        )->assertRedirect()->assertSessionHasNoErrors();
        self::assertSame('synthetic-unchanged',
            app(SettingsService::class)->get('public_checkout_approval_digest'));
        self::assertSame('1',app(SettingsService::class)->get('public_storefront_open'));
        self::assertTrue(config('public_storefront.enabled'));
        self::assertDatabaseCount('user_activation_tokens',0);
        Notification::assertNothingSent();
    }

    public function test_missing_attestation_or_unsafe_headers_never_override_sender(): void
    {
        $this->actingAs($this->owner);
        foreach ([
            ['confirm_authorized_sender'=>null],
            ['registration_from_email'=>"bad\r\nBcc:evil@example.test"],
            ['registration_from_name'=>"Fake\r\nBcc:evil@example.test"],
            ['registration_from_email'=>'not-an-email'],
        ] as $change) {
            $this->put('/admin/settings/public-storefront/registration-sender',
                array_replace($this->payload(),$change))
                ->assertRedirect()->assertSessionHasErrors();
            self::assertNull(app(PublicRegistrationMailSenderService::class)->sender());
            self::assertFalse(app(SettingsService::class)->hasStoredValue(
                'public_registration_sender_digest'));
        }
    }

    public function test_changed_sender_digest_or_retired_signer_fails_closed(): void
    {
        $this->actingAs($this->owner)->put(
            '/admin/settings/public-storefront/registration-sender',$this->payload()
        )->assertRedirect()->assertSessionHasNoErrors();
        $mail=app(PublicRegistrationMailSenderService::class);
        self::assertNotNull($mail->sender());
        app(SettingsService::class)->putMany([
            'public_registration_sender_address'=>'forged@apcomputers.rs',
        ],$this->owner->id);
        self::assertNull($mail->sender());
        $this->put('/admin/settings/public-storefront/registration-sender',$this->payload())
            ->assertRedirect()->assertSessionHasNoErrors();
        self::assertNotNull($mail->sender());
        $this->owner->forceFill(['retired_at'=>now()])->save();
        self::assertNull($mail->sender());
    }

    /** @return array<string,string> */
    private function payload(): array
    {
        return [
            'registration_from_email'=>'Registracija@APComputers.rs',
            'registration_from_name'=>'AP Computers',
            'confirm_authorized_sender'=>'1',
        ];
    }

    /** @return array<string,string> */
    private function signup(): array
    {
        return [
            'first_name'=>'Synthetic','last_name'=>'Buyer',
            'email'=>'b810-buyer@example.test','accepted_privacy'=>'1',
        ];
    }

    private function person(string $name,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$name,'email'=>$name.'@example.test','first_name'=>'Synthetic',
            'password_hash'=>Hash::make('OnlySynthetic123!'),'status'=>'active',
        ]);
    }
}
