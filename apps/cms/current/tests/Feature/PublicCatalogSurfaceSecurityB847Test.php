<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Product,ProductImage,Role,User};
use App\Services\{PublicRetailCatalogService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\{Hash,Storage};
use Tests\TestCase;

/**
 * B847-B848: actual HTTP public-catalog security controls on synthetic MariaDB.
 * Independently test media path containment, MIME, visibility, search validation.
 * Not full OWASP coverage, staging/browser acceptance, or production authorization.
 */
final class PublicCatalogSurfaceSecurityB847Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('public_storefront.enabled',true);
        app(SettingsService::class)->putMany([
            'public_storefront_open'=>'1','eur_rsd_rate'=>'117.78',
        ],null);
        Storage::fake('public');
    }

    public function test_media_serves_real_png_with_nosniff_header(): void
    {
        $product=$this->product('normal','Visible B847');
        $image=$this->image($product,'products/'.$product->id.'/valid.png');
        $this->get('/katalog/slika/'.$image->id)->assertOk()
            ->assertHeader('Content-Type','image/png')
            ->assertHeader('X-Content-Type-Options','nosniff');
    }

    public function test_media_rejects_unlisted_storage_disk_without_fallback(): void
    {
        $product=$this->product('disk','Disk B847');
        $image=$this->image($product,'products/'.$product->id.'/valid.png','private');
        $this->get('/katalog/slika/'.$image->id)->assertNotFound();
    }

    public function test_media_rejects_parent_directory_segments_before_realpath(): void
    {
        $product=$this->product('path','Path B847');
        $image=$this->image($product,'products/'.$product->id.'/valid.png');
        $image->forceFill(['file_path'=>'products/'.$product->id.'/../valid.png'])->save();
        $this->get('/katalog/slika/'.$image->id)->assertNotFound();
    }

    public function test_media_rejects_cross_product_directory_even_when_file_exists(): void
    {
        $product=$this->product('cross','Cross B847');
        $other=$this->product('other','Other B847');
        $image=$this->image($product,'products/'.$other->id.'/real.png');
        $this->get('/katalog/slika/'.$image->id)->assertNotFound();
    }

    public function test_media_rejects_symlink_to_file_outside_product_directory(): void
    {
        $product=$this->product('link','Link B847');
        $other=$this->product('target','Target B847');
        $source='products/'.$other->id.'/original.png';
        $target='products/'.$product->id.'/link.png';
        Storage::disk('public')->put($source,self::png());
        Storage::disk('public')->put('products/'.$product->id.'/placeholder.txt','fixture');
        self::assertTrue(symlink(
            Storage::disk('public')->path($source),
            Storage::disk('public')->path($target)
        ),'Disposable Linux CI must support fixture symlinks.');
        $image=$this->image($product,$target);
        $this->get('/katalog/slika/'.$image->id)->assertNotFound();
    }

    public function test_media_rejects_php_payload_with_forged_png_metadata(): void
    {
        $product=$this->product('mime','Mime B847');
        $path='products/'.$product->id.'/fake.png';
        Storage::disk('public')->put($path,"<?php echo 'synthetic only';");
        $image=$this->image($product,$path);
        $this->get('/katalog/slika/'.$image->id)->assertNotFound();
    }

    public function test_media_rejects_inactive_and_unpriced_product(): void
    {
        $product=$this->product('inactive','Visibility B847');
        $image=$this->image($product,'products/'.$product->id.'/valid.png');
        $product->forceFill(['status'=>'inactive'])->save();
        $this->get('/katalog/slika/'.$image->id)->assertNotFound();
        $product->forceFill(['status'=>'active','price_amount'=>0])->save();
        $this->get('/katalog/slika/'.$image->id)->assertNotFound();
    }

    public function test_emergency_off_closes_existing_media_capability(): void
    {
        $product=$this->product('off','Off B847');
        $image=$this->image($product,'products/'.$product->id.'/valid.png');
        $this->get('/katalog/slika/'.$image->id)->assertOk();
        config()->set('public_storefront.enabled',false);
        $this->get('/katalog/slika/'.$image->id)->assertNotFound();
    }

    public function test_sql_boolean_injection_is_treated_as_plain_search_text(): void
    {
        $this->product('visible','Visible Search B848');
        $this->product('private','Hidden Search B848',['status'=>'draft']);
        $this->get('/katalog?q='.rawurlencode("' OR 1=1 --"))->assertOk()
            ->assertDontSee('Visible Search B848')
            ->assertDontSee('Hidden Search B848');
    }

    public function test_like_percent_is_escaped_as_literal_character(): void
    {
        $this->product('percent','B848 Real 12% Sign');
        $this->product('ordinary','B848 Ordinary Item');
        $this->get('/katalog?q=%25')->assertOk()
            ->assertSee('B848 Real 12% Sign')
            ->assertDontSee('B848 Ordinary Item');
    }

    public function test_like_underscore_is_escaped_as_literal_character(): void
    {
        $this->product('underscore','B848 ABC_DEF');
        $this->product('ordinary','B848 Ordinary Item');
        $this->get('/katalog?q=_')->assertOk()
            ->assertSee('B848 ABC_DEF')
            ->assertDontSee('B848 Ordinary Item');
    }

    public function test_unlisted_sort_and_stock_values_are_validation_errors(): void
    {
        $this->getJson('/katalog?sort=price_amount%20DESC')->assertUnprocessable()
            ->assertJsonValidationErrors('sort');
        $this->getJson('/katalog?stock=all')->assertUnprocessable()
            ->assertJsonValidationErrors('stock');
    }

    public function test_brand_category_identifier_injection_is_rejected(): void
    {
        $this->getJson('/katalog?brand_id=1%20OR%201%3D1')->assertUnprocessable()
            ->assertJsonValidationErrors('brand_id');
        $this->getJson('/katalog?category_id=0%20UNION%20SELECT')->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }

    public function test_array_and_oversized_search_params_are_rejected(): void
    {
        $this->getJson('/katalog?q%5B%5D=abc')->assertUnprocessable()
            ->assertJsonValidationErrors('q');
        $this->getJson('/katalog?q='.str_repeat('X',81))->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_hidden_and_unpriced_rows_never_enter_public_search_builder(): void
    {
        $visible=$this->product('shown','B848 Visible');
        $this->product('draft','B848 Draft',['status'=>'draft']);
        $this->product('unpriced','B848 Unpriced',['price_amount'=>0]);
        $this->product('archived','B848 Archived',['deleted_at'=>now()]);
        $matches=app(PublicRetailCatalogService::class)->search([]);
        self::assertSame(1,$matches->total());
        self::assertSame((int)$visible->id,(int)$matches->getCollection()->sole()->id);
        $this->get('/katalog')->assertOk()
            ->assertSee('B848 Visible')
            ->assertDontSee('B848 Draft')
            ->assertDontSee('B848 Unpriced')
            ->assertDontSee('B848 Archived');
    }

    private function product(string $suffix,string $name,array $overrides=[]): Product
    {
        $owner=User::query()->firstOrCreate(['username'=>'b847-fixture-owner'],[
            'role_id'=>Role::query()->where('slug','superadmin')->valueOrFail('id'),
            'email'=>'b847-owner@example.test',
            'password_hash'=>Hash::make('B847-Fake-Only-Not-Production!'),
            'status'=>'active',
        ]);
        return Product::query()->create(array_replace([
            'created_by'=>$owner->id,'sku'=>'B847-'.strtoupper($suffix),
            'slug'=>'b847-'.$suffix,'name'=>$name,
            'description'=>'B847 isolated synthetic product',
            'price_amount'=>1000,'price_currency'=>'RSD',
            'stock_quantity'=>4,'status'=>'active',
        ],$overrides));
    }

    private function image(Product $product,string $path,string $disk='public'): ProductImage
    {
        if ($disk==='public' && !Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path,self::png());
        }
        return ProductImage::query()->create([
            'product_id'=>$product->id,'storage_disk'=>$disk,
            'file_path'=>$path,'original_filename'=>'synthetic.png',
            'mime_type'=>'image/png','file_size'=>67,
            'file_hash'=>str_repeat('a',64),'sort_order'=>1,
            'is_primary'=>true,'created_at'=>now(),
        ]);
    }

    private static function png(): string
    {
        return (string)base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/ak8AAAAASUVORK5CYII='
        );
    }
}
