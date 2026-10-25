<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Product,ProductImage,Role,User};
use App\Services\{PublicRegistrationMailSenderService,PublicRetailCatalogService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\{Hash,Mail,Storage};
use Tests\TestCase;

/**
 * B899/SEC02+16+22: synthetic public HTML must never generate first-party
 * links, form actions, media URLs or stylesheets from untrusted request Host.
 * No CloudLinux production request, no real buyer email/stock/order writes.
 */
final class StorefrontHtmlOriginBoundaryB899Test extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private ProductImage $image;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        config()->set('app.url','https://cms.ald1n.com');
        config()->set('public_storefront.enabled',true);
        config()->set('public_storefront.checkout_enabled',false);
        config()->set('public_storefront.privacy_url','https://cms.ald1n.com/privatnost');
        config()->set('services.turnstile.enabled',false);
        config()->set('mail.default','array');
        app(SettingsService::class)->putMany(['public_storefront_open'=>'1'],null);
        Storage::fake('public');
        $this->owner=User::query()->create([
            'role_id'=>Role::query()->where('slug','superadmin')->valueOrFail('id'),
            'username'=>'b899-owner',
            'email'=>'owner@b899-synthetic.example.test',
            'first_name'=>'Synthetic',
            'password_hash'=>Hash::make('B899-OnlySyntheticTestSecret!'),
            'status'=>'active',
        ]);
        $this->product=Product::query()->create([
            'sku'=>'B899-SYNTHETIC-ONLY','slug'=>'b899-origin-item',
            'name'=>'B899 Synthetic Image Item',
            'description'=>'Nonproduction disposable item',
            'price_amount'=>12500,'price_currency'=>'RSD',
            'stock_quantity'=>4,'status'=>'active','created_by'=>$this->owner->id,
        ]);
        $this->image=ProductImage::query()->create([
            'product_id'=>$this->product->id,
            'storage_disk'=>'public',
            'file_path'=>'products/'.$this->product->id.'/sample.png',
            'original_filename'=>'sample.png',
            'mime_type'=>'image/png','file_size'=>67,'file_hash'=>str_repeat('c',64),
            'sort_order'=>1,'is_primary'=>true,
        ]);
    }

    private function hostile(): self
    {
        return $this->withHeaders([
            'Host'=>'poison.b899.synthetic.invalid',
            'X-Forwarded-Host'=>'forwarded.b899.synthetic.invalid',
            'X-Forwarded-Proto'=>'http',
            'Referer'=>'https://poison.b899.synthetic.invalid/elsewhere',
            'Origin'=>'https://poison.b899.synthetic.invalid',
        ]);
    }

    private function assertOriginFree(string $html): void
    {
        self::assertStringNotContainsString('poison.b899.synthetic.invalid',$html);
        self::assertStringNotContainsString('forwarded.b899.synthetic.invalid',$html);
        self::assertStringNotContainsString('href="http://',$html);
        self::assertStringNotContainsString('action="http://',$html);
        self::assertStringNotContainsString('src="http://',$html);
    }

    public function test_catalog_links_images_styles_and_add_form_do_not_follow_host_headers(): void
    {
        $html=$this->hostile()->get('/katalog')->assertOk()->getContent();
        self::assertIsString($html);
        $this->assertOriginFree($html);
        self::assertStringContainsString('href="/assets/css/ald1n-public-storefront.css"',$html);
        self::assertStringContainsString('href="/katalog"',$html);
        self::assertStringContainsString('href="/korpa"',$html);
        self::assertStringContainsString('action="/katalog"',$html);
        self::assertStringContainsString('href="/katalog/b899-origin-item"',$html);
        self::assertStringContainsString('src="/katalog/slika/'.$this->image->id.'"',$html);
        self::assertStringContainsString('action="/korpa/dodaj/'.$this->product->id.'"',$html);
        self::assertDatabaseCount('orders',0);
    }

    public function test_product_detail_preserves_relative_first_party_links_and_image_under_hostile_host(): void
    {
        $html=$this->hostile()->get('/katalog/b899-origin-item')->assertOk()->getContent();
        self::assertIsString($html);
        $this->assertOriginFree($html);
        self::assertStringContainsString('href="/katalog"',$html);
        self::assertStringContainsString('src="/katalog/slika/'.$this->image->id.'"',$html);
        self::assertStringContainsString('action="/korpa/dodaj/'.$this->product->id.'"',$html);
        self::assertDatabaseCount('orders',0);
    }

    public function test_buyer_cart_item_actions_and_login_links_are_relative(): void
    {
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])->assertRedirect('/korpa');
        $html=$this->hostile()->get('/korpa')->assertOk()->getContent();
        self::assertIsString($html);
        $this->assertOriginFree($html);
        self::assertStringContainsString('action="/korpa/'.$this->product->id.'"',$html);
        self::assertStringContainsString('href="/login"',$html);
        self::assertStringContainsString('href="/registracija"',$html);
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('public_checkout_attempts',0);
    }

    public function test_registration_form_action_and_signin_link_not_poisonable(): void
    {
        $mail='approved@b899-synthetic.example.test';
        $name='B899 Synthetic Approved Sender';
        app(SettingsService::class)->putMany([
            'public_registration_sender_address'=>$mail,
            'public_registration_sender_name'=>$name,
            'public_registration_sender_digest'=>PublicRegistrationMailSenderService::fingerprint($mail,$name),
            'public_registration_sender_approved_by'=>(string)$this->owner->id,
            'public_registration_sender_approved_at'=>now()->toISOString(),
        ],(int)$this->owner->id);
        $html=$this->hostile()->get('/registracija')->assertOk()->getContent();
        self::assertIsString($html);
        $this->assertOriginFree($html);
        self::assertStringContainsString('action="/registracija"',$html);
        self::assertStringContainsString('href="/login"',$html);
        // External legal policy URL is a separately configured canonical URL.
        self::assertStringContainsString('https://cms.ald1n.com/privatnost',$html);
        self::assertDatabaseCount('orders',0);
    }

    public function test_second_page_navigation_uses_relative_catalog_path_and_preserves_filters(): void
    {
        for ($i=1;$i<=21;$i++) {
            Product::query()->create([
                'sku'=>'B899-EXTRA-'.$i,
                'slug'=>'b899-extra-'.$i,
                'name'=>'B899 Additional Search Item '.$i,
                'description'=>'Disposable pagination test only',
                'price_amount'=>10000,
                'price_currency'=>'RSD',
                'stock_quantity'=>3,
                'status'=>'active',
                'created_by'=>$this->owner->id,
            ]);
        }
        $html=$this->hostile()->get('/katalog?q=B899')->assertOk()->getContent();
        self::assertIsString($html);
        $this->assertOriginFree($html);
        // An absolute app.url paginator leaves a first-party host embedded in
        // otherwise origin-independent form/navigation links.
        self::assertMatchesRegularExpression('~href="/katalog\?[^"]*page=2[^"]*"~',$html);
        self::assertStringContainsString('q=B899',$html);
        self::assertStringNotContainsString('https://cms.ald1n.com/katalog?',$html);
        self::assertDatabaseCount('orders',0);
    }

    public function test_catalog_dto_returns_relative_image_uri_not_host_derived_absolute_url(): void
    {
        $this->hostile()->get('/katalog')->assertOk();
        $item=app(PublicRetailCatalogService::class)->byId((int)$this->product->id);
        $dto=app(PublicRetailCatalogService::class)->present($item);
        self::assertSame('/katalog/slika/'.$this->image->id,$dto['image']);
        self::assertNotEmpty($dto['name']);
    }
}
