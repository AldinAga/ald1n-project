<?php
declare(strict_types=1);
namespace Tests\Feature;

use App\Models\{Role,User};
use App\Services\{PublicRegistrationMailSenderService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Mail,Notification};
use Tests\Support\ShopThemeScript;
use Tests\TestCase;

/** Registration page in the login visual shell: markup, accessibility wiring, CSP unchanged. */
final class PublicRegistrationPageRedesignTest extends TestCase
{
    use RefreshDatabase;

    private const FIELDS=[
        'first_name'=>['Ime','text','given-name'],
        'last_name'=>['Prezime','text','family-name'],
        'address'=>['Adresa','text','street-address'],
        'city'=>['Grad','text','address-level2'],
        'phone'=>['Telefon','tel','tel'],
        'email'=>['E-mail','email','email'],
    ];

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
            'username'=>'reg-redesign-owner','email'=>'reg-redesign-owner@example.test',
            'password_hash'=>Hash::make('Redesign-Synthetic-Only123!'),'status'=>'active',
        ]);
        $address='registration@synthetic.example.test';
        $name='Redesign Synthetic Activation';
        app(SettingsService::class)->putMany([
            'public_storefront_open'=>'1',
            'public_registration_sender_address'=>$address,
            'public_registration_sender_name'=>$name,
            'public_registration_sender_digest'=>PublicRegistrationMailSenderService::fingerprint($address,$name),
            'public_registration_sender_approved_by'=>(string)$owner->id,
            'public_registration_sender_approved_at'=>now()->toISOString(),
        ],$owner->id);
    }

    private function xpath(string $html): \DOMXPath
    {
        $document=new \DOMDocument();
        $prior=libxml_use_internal_errors(true);
        try { $document->loadHTML('<?xml encoding="utf-8"?>'.$html); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($prior); }
        return new \DOMXPath($document);
    }

    public function test_page_renders_every_field_with_label_type_and_autocomplete_in_order(): void
    {
        $html=$this->get('/registracija')->assertOk()->getContent();
        $x=$this->xpath($html);
        $names=[];
        foreach ($x->query('//form[@action="/registracija"]//input[@name and not(@name="website") and not(@name="_token")]') as $input) {
            $names[]=$input->getAttribute('name');
        }
        self::assertSame([...array_keys(self::FIELDS),'accepted_privacy'],$names);
        foreach (self::FIELDS as $name=>[$label,$type,$autocomplete]) {
            $input=$x->query('//input[@name="'.$name.'"]')->item(0);
            self::assertNotNull($input,$name);
            self::assertSame($type,$input->getAttribute('type'),$name);
            self::assertSame($autocomplete,$input->getAttribute('autocomplete'),$name);
            self::assertTrue($input->hasAttribute('required'),$name);
            $id=$input->getAttribute('id');
            self::assertNotSame('',$id,$name);
            $for=$x->query('//label[@for="'.$id.'"]');
            self::assertSame(1,$for->length,$name);
            self::assertStringContainsString($label,$for->item(0)->textContent,$name);
        }
        self::assertSame('tel',$x->query('//input[@name="phone"]')->item(0)->getAttribute('inputmode'));
        self::assertSame(0,$x->query('//input[@type="password"]')->length);
        self::assertSame(1,$x->query('//h1[normalize-space()="Registracija naloga"]')->length);
        self::assertSame('Registruj se',trim($x->query('//button[@type="submit" and contains(@class,"reg-submit")]/span[1]')->item(0)->textContent));
        foreach (['Lični podaci','Adresa i kontakt','Saglasnost'] as $legend) {
            self::assertSame(1,$x->query('//fieldset/legend[normalize-space()="'.$legend.'"]')->length,$legend);
        }
        self::assertStringContainsString('Otvorite nalog i poručujte brže.',$html);
        self::assertStringContainsString('Za poručivanje je obavezna registracija i aktivacija e-mail adrese.',$html);
        // Not checked by default, as before.
        self::assertSame(0,$x->query('//input[@name="accepted_privacy" and @checked]')->length);
    }

    public function test_failed_submit_rerenders_summary_aria_invalid_and_old_input(): void
    {
        $this->from('/registracija')->post('/registracija',[
            'first_name'=>'A','last_name'=>'Kupac','address'=>'Ulica 1','city'=>'Beograd',
            'phone'=>'0601234567','email'=>'nije-email',
        ])->assertRedirect('/registracija');
        $html=$this->get('/registracija')->assertOk()->getContent();
        $x=$this->xpath($html);
        $summary=$x->query('//*[@id="reg-errors"]');
        self::assertSame(1,$summary->length);
        self::assertSame('alert',$summary->item(0)->getAttribute('role'));
        self::assertSame('-1',$summary->item(0)->getAttribute('tabindex'));
        self::assertTrue($summary->item(0)->hasAttribute('autofocus'));
        foreach (['first_name','email','accepted_privacy'] as $bad) {
            $input=$x->query('//input[@name="'.$bad.'"]')->item(0);
            self::assertSame('true',$input->getAttribute('aria-invalid'),$bad);
            $describedBy=$input->getAttribute('aria-describedby');
            $message=$x->query('//*[@id="'.$describedBy.'"]');
            self::assertSame(1,$message->length,$bad);
            self::assertNotSame('',trim($message->item(0)->textContent),$bad);
            self::assertSame(1,$x->query('//*[@id="reg-errors"]//a[@href="#'.$input->getAttribute('id').'"]')->length,$bad);
        }
        foreach (['last_name','address','city','phone'] as $good) {
            self::assertFalse($x->query('//input[@name="'.$good.'"]')->item(0)->hasAttribute('aria-invalid'),$good);
        }
        self::assertSame('Kupac',$x->query('//input[@name="last_name"]')->item(0)->getAttribute('value'));
        self::assertSame('nije-email',$x->query('//input[@name="email"]')->item(0)->getAttribute('value'));
        self::assertSame(0,$x->query('//input[@type="password"]')->length);
        self::assertSame(0,$x->query('//input[@name="accepted_privacy" and @checked]')->length);
    }

    public function test_csp_header_is_exactly_the_registration_policy_of_main(): void
    {
        $policy=(string)$this->get('/registracija')->assertOk()->headers->get('Content-Security-Policy');
        $nonce=ShopThemeScript::nonceFromPolicy(str_replace('https://challenges.cloudflare.com ','',$policy));
        self::assertSame(
            "base-uri 'none'; frame-ancestors 'self'; form-action 'self'; object-src 'none'; ".
            "script-src https://challenges.cloudflare.com 'nonce-{$nonce}'; ".
            "script-src-elem https://challenges.cloudflare.com 'nonce-{$nonce}'; ".
            "script-src-attr 'none'; ".
            "style-src 'self'; img-src 'self' data: https://challenges.cloudflare.com; ".
            "font-src 'self' data:; connect-src 'self' https://challenges.cloudflare.com; ".
            "frame-src https://challenges.cloudflare.com; worker-src 'none'; media-src 'none'",
            $policy
        );
    }

    public function test_no_inline_style_handler_or_extra_script_in_the_html(): void
    {
        config()->set('services.turnstile.enabled',true);
        config()->set('services.turnstile.site_key','1x00000000000000000000AA');
        foreach (['/registracija'] as $uri) {
            $response=$this->get($uri)->assertOk();
            $html=(string)$response->getContent();
            $nonce=ShopThemeScript::nonceFromPolicy(str_replace('https://challenges.cloudflare.com ','',(string)$response->headers->get('Content-Security-Policy')));
            $rest=ShopThemeScript::strip($html,$nonce,$uri);
            self::assertSame(0,preg_match('~\sstyle\s*=~i',$rest),'inline style attribute');
            self::assertStringNotContainsString('<style',strtolower($rest));
            self::assertSame(0,preg_match('~\son[a-z]+\s*=~i',$rest),'inline handler');
            preg_match_all('~<script\b[^>]*>~i',$rest,$open);
            self::assertSame(['<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer>'],$open[0]);
            self::assertStringContainsString('class="cf-turnstile" data-sitekey="1x00000000000000000000AA" data-action="retail_registration"',$rest);
            self::assertStringContainsString('ald1n-public-register.css',$rest);
            self::assertStringContainsString('<noscript>',$rest);
        }
    }

    public function test_links_to_login_catalog_privacy_policy_and_legal_pages(): void
    {
        $html=$this->get('/registracija')->assertOk()->getContent();
        $x=$this->xpath($html);
        self::assertSame(1,$x->query('//form//a[@href="/login"]')->length);
        self::assertGreaterThanOrEqual(2,$x->query('//a[@href="/katalog"]')->length);
        self::assertSame(1,$x->query('//form//label[input[@name="accepted_privacy"]]//a[@href="https://cms.ald1n.com/privatnost"]')->length);
        self::assertGreaterThanOrEqual(1,$x->query('//nav[@aria-label="Pravne informacije"]//a')->length);
        self::assertSame(0,$x->query('//header')->length);
        self::assertSame(1,$x->query('//a[@class="shop-back-to-top" and @href="#vrh" and @aria-label]')->length);
        self::assertSame(1,$x->query('//*[@id="vrh"]')->length);
        self::assertSame(3,$x->query('//form[contains(@class,"shop-theme")]//button[@name="theme"]')->length);
    }

    public function test_login_background_image_is_reused_as_same_origin_image_without_inline_style(): void
    {
        $file='login/'.str_repeat('a',24).'.jpg';
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('site-assets/'.$file,base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA='));
        app(SettingsService::class)->putMany([
            'login_background_mode'=>'image',
            'login_background_image_path'=>'site-assets/'.$file,
            'login_background_overlay_opacity'=>'47',
            'login_background_blur_px'=>'3',
        ],null);
        $html=$this->get('/registracija')->assertOk()->getContent();
        $x=$this->xpath($html);
        $img=$x->query('//img[contains(@class,"reg-bg-img")]');
        self::assertSame(1,$img->length);
        self::assertStringStartsWith('/site-assets/'.$file,$img->item(0)->getAttribute('src'));
        $bg=$x->query('//div[contains(@class,"reg-bg")]')->item(0);
        self::assertSame('45',$bg->getAttribute('data-o'));
        self::assertSame('3',$bg->getAttribute('data-b'));
        self::assertSame(0,preg_match('~\sstyle\s*=~i',$html));
    }

    public function test_default_login_background_has_no_image(): void
    {
        $x=$this->xpath($this->get('/registracija')->assertOk()->getContent());
        self::assertSame(0,$x->query('//img[contains(@class,"reg-bg-img")]')->length);
    }
}
