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

    private function fakeJpeg(string $file): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('site-assets/'.$file,base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA='));
    }

    private function frameSrc(string $uri): string
    {
        $policy=(string)$this->get($uri)->assertOk()->headers->get('Content-Security-Policy');
        self::assertSame(1,preg_match('~frame-src ([^;]*);~',$policy,$m),$policy);
        return $m[1];
    }

    public function test_youtube_mode_renders_the_login_embed_and_adds_only_its_host_to_the_registration_csp(): void
    {
        $this->fakeJpeg('login/'.str_repeat('p',24).'.jpg');
        app(SettingsService::class)->putMany([
            'login_background_mode'=>'youtube',
            'login_background_youtube_id'=>'dQw4w9WgXcQ',
            'login_background_fallback_path'=>'site-assets/login/'.str_repeat('p',24).'.jpg',
        ],null);
        $response=$this->get('/registracija')->assertOk();
        $html=(string)$response->getContent();
        $x=$this->xpath($html);
        $frame=$x->query('//iframe');
        self::assertSame(1,$frame->length);
        $iframe=$frame->item(0);
        self::assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&mute=1&controls=0&loop=1&playlist=dQw4w9WgXcQ&playsinline=1&rel=0&modestbranding=1',$iframe->getAttribute('src'));
        self::assertSame('',$iframe->getAttribute('title'));
        self::assertSame('-1',$iframe->getAttribute('tabindex'));
        self::assertSame('autoplay; encrypted-media; picture-in-picture',$iframe->getAttribute('allow'));
        self::assertSame(1,$x->query('//div[@aria-hidden="true"]//iframe')->length);
        self::assertSame(1,$x->query('//img[contains(@class,"reg-bg-poster")]')->length);
        self::assertSame(0,preg_match('~\sstyle\s*=~i',$html));
        $policy=(string)$response->headers->get('Content-Security-Policy');
        self::assertStringContainsString('frame-src https://challenges.cloudflare.com https://www.youtube-nocookie.com; worker-src',$policy);
        self::assertStringNotContainsString('*',$policy);
        self::assertStringNotContainsString("'unsafe-inline'",$policy);
        self::assertStringNotContainsString('www.youtube.com',$policy);
    }

    public function test_other_modes_and_invalid_video_id_do_not_widen_the_registration_csp(): void
    {
        self::assertSame('https://challenges.cloudflare.com',$this->frameSrc('/registracija'));
        $this->fakeJpeg('login/'.str_repeat('a',24).'.jpg');
        app(SettingsService::class)->putMany(['login_background_mode'=>'image','login_background_image_path'=>'site-assets/login/'.str_repeat('a',24).'.jpg'],null);
        self::assertSame('https://challenges.cloudflare.com',$this->frameSrc('/registracija'));
        app(SettingsService::class)->putMany(['login_background_mode'=>'youtube','login_background_youtube_id'=>'bad id!'],null);
        self::assertSame('https://challenges.cloudflare.com',$this->frameSrc('/registracija'));
        self::assertSame(0,$this->xpath($this->get('/registracija')->assertOk()->getContent())->query('//iframe')->length);
    }

    public function test_slideshow_mode_renders_every_slide_with_css_timing_and_no_script(): void
    {
        $this->fakeJpeg('login/slides/'.str_repeat('s',24).'.jpg');
        \Illuminate\Support\Facades\Storage::disk('public')->put('site-assets/login/slides/'.str_repeat('t',24).'.jpg',\Illuminate\Support\Facades\Storage::disk('public')->get('site-assets/login/slides/'.str_repeat('s',24).'.jpg'));
        app(SettingsService::class)->putMany([
            'login_background_mode'=>'slideshow','login_background_slide_interval'=>'7',
            'login_background_slide_1_path'=>'site-assets/login/slides/'.str_repeat('s',24).'.jpg','login_background_slide_1_active'=>'1','login_background_slide_1_order'=>'20',
            'login_background_slide_2_path'=>'site-assets/login/slides/'.str_repeat('t',24).'.jpg','login_background_slide_2_active'=>'1','login_background_slide_2_order'=>'10',
        ],null);
        $response=$this->get('/registracija')->assertOk();
        $x=$this->xpath((string)$response->getContent());
        $slides=$x->query('//div[contains(@class,"reg-slides")]//img');
        self::assertSame(2,$slides->length);
        self::assertStringContainsString(str_repeat('t',24),$slides->item(0)->getAttribute('src'),'order setting respected');
        $box=$x->query('//div[contains(@class,"reg-slides")]')->item(0);
        self::assertSame('2',$box->getAttribute('data-n'));
        self::assertSame('7',$box->getAttribute('data-t'));
        self::assertSame('https://challenges.cloudflare.com',$this->frameSrc('/registracija'));
        $nonce=ShopThemeScript::nonceFromPolicy(str_replace('https://challenges.cloudflare.com ','',(string)$response->headers->get('Content-Security-Policy')));
        ShopThemeScript::strip((string)$response->getContent(),$nonce,'/registracija');
    }

    public function test_other_storefront_pages_keep_the_exact_strict_policy_in_youtube_mode(): void
    {
        app(SettingsService::class)->putMany(['login_background_mode'=>'youtube','login_background_youtube_id'=>'dQw4w9WgXcQ'],null);
        foreach (['/katalog','/korpa','/uslovi-prodaje'] as $uri) {
            ShopThemeScript::assertStrictPolicy($this->get($uri)->assertOk()->headers->get('Content-Security-Policy'));
        }
    }

    public function test_video_iframe_is_lazy_and_sandboxed_with_strict_referrer(): void
    {
        app(SettingsService::class)->putMany(['login_background_mode'=>'youtube','login_background_youtube_id'=>'dQw4w9WgXcQ'],null);
        $iframe=$this->xpath($this->get('/registracija')->assertOk()->getContent())->query('//iframe')->item(0);
        self::assertSame('lazy',$iframe->getAttribute('loading'));
        self::assertSame('allow-scripts allow-same-origin allow-presentation',$iframe->getAttribute('sandbox'));
        self::assertSame('strict-origin-when-cross-origin',$iframe->getAttribute('referrerpolicy'));
    }

    public function test_brand_and_back_links_follow_the_storefront_home_target(): void
    {
        $render=static fn (bool $hide): string => view('public-shop.register',[
            'turnstileEnabled'=>false,'turnstileSiteKey'=>'','registrationSenderReady'=>true,
            'errors'=>new \Illuminate\Support\ViewErrorBag(),'hideShopNav'=>$hide,
        ])->render();
        $open=$this->xpath($render(false));
        self::assertSame(2,$open->query('//a[@href="/katalog" and (contains(@class,"reg-brand") or contains(@class,"reg-back"))]')->length);
        $closed=$this->xpath($render(true));
        self::assertSame(2,$closed->query('//a[@href="/pravne-informacije" and (contains(@class,"reg-brand") or contains(@class,"reg-back"))]')->length);
        self::assertSame(0,$closed->query('//a[@href="/katalog"]')->length);
    }

    public function test_only_the_registration_view_enables_the_auth_shell(): void
    {
        $users=[];
        $it=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'),\FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && str_contains((string)file_get_contents($file->getPathname()),'authShell')) {
                $users[]=str_replace('\\','/',substr($file->getPathname(),strlen(resource_path('views'))+1));
            }
        }
        sort($users);
        self::assertSame(['public-shop/layout.blade.php','public-shop/register.blade.php'],$users);
        self::assertSame(1,preg_match("~@extends\\('public-shop\\.layout', \\['authShell' => true\\]\\)~",(string)file_get_contents(resource_path('views/public-shop/register.blade.php'))));
    }

    public function test_csp_middleware_leaves_non_200_registration_responses_untouched(): void
    {
        $middleware=app(\App\Http\Middleware\PublicNoScriptCsp::class);
        foreach ([302,404,419,429] as $status) {
            $request=\Illuminate\Http\Request::create('/registracija','GET');
            $request->setRouteResolver(static fn () => (new \Illuminate\Routing\Route(['GET'],'/registracija',[]))->name('public-shop.register'));
            $response=$middleware->handle($request,static fn () => new \Illuminate\Http\Response('x',$status,['Content-Type'=>'text/html']));
            self::assertFalse($response->headers->has('Content-Security-Policy'),(string)$status);
        }
    }

    public function test_post_validation_error_and_closed_shop_keep_the_global_policy(): void
    {
        $baseline=static function (string $policy): void {
            self::assertSame(1,preg_match("~^base-uri 'none'; frame-ancestors 'self'; form-action 'self'; object-src 'none'; frame-src 'self' https://challenges.cloudflare.com https://www.youtube-nocookie.com https://www.youtube.com; script-src 'nonce-[A-Za-z0-9+/]{22}=='; script-src-attr 'none'~",$policy),$policy);
            self::assertStringNotContainsString('script-src-elem',$policy);
        };
        app(SettingsService::class)->putMany(['login_background_mode'=>'youtube','login_background_youtube_id'=>'dQw4w9WgXcQ'],null);
        $post=$this->from('/registracija')->post('/registracija',['first_name'=>'A'])->assertRedirect('/registracija');
        $baseline((string)$post->headers->get('Content-Security-Policy'));
        config()->set('public_storefront.enabled',false);
        app(SettingsService::class)->putMany(['public_storefront_open'=>'0'],null);
        $closed=$this->get('/registracija')->assertNotFound();
        $baseline((string)$closed->headers->get('Content-Security-Policy'));
    }

    public function test_default_login_background_has_no_image(): void
    {
        $x=$this->xpath($this->get('/registracija')->assertOk()->getContent());
        self::assertSame(0,$x->query('//img[contains(@class,"reg-bg-img")]')->length);
    }
}
