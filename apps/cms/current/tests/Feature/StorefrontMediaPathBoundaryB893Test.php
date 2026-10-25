<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Product,ProductImage,Role,User};
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\{Hash,Storage};
use Tests\TestCase;

/**
 * B893/SEC13 — real public HTTP with synthetic MariaDB and disposable files.
 * A database image path cannot cross either the configured media root or the
 * original product directory through symlink resolution. No production media.
 */
final class StorefrontMediaPathBoundaryB893Test extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    /** @var list<string> */
    private array $outsideDirs = [];
    /** @var list<string> */
    private array $links = [];

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/ak8AAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('public_storefront.enabled',true);
        app(SettingsService::class)->putMany(['public_storefront_open'=>'1'],null);
        Storage::fake('public');
        $this->product=$this->product('b893-primary');
    }

    protected function tearDown(): void
    {
        // Remove only synthetic links made by this test. Never recursively
        // traverse a symlink or touch a configured legacy production root.
        foreach ($this->links as $link) {
            if (is_link($link)) unlink($link);
        }
        foreach ($this->outsideDirs as $dir) {
            if (is_file($dir.'/photo.png')) unlink($dir.'/photo.png');
            if (is_dir($dir)) rmdir($dir);
        }
        parent::tearDown();
    }

    public function test_public_disk_product_directory_symlink_cannot_serve_outside_root_png(): void
    {
        $root=$this->publicRoot();
        mkdir($root.'/products',0770,true);
        $outside=$this->outside();
        $link=$root.'/products/'.$this->product->id;
        self::assertTrue(symlink($outside,$link));
        $this->links[]=$link;

        $image=$this->image('public','products/'.$this->product->id.'/photo.png');
        $this->get('/katalog/slika/'.$image->id)->assertNotFound()
            ->assertHeader('X-Content-Type-Options','nosniff');
        self::assertDatabaseCount('orders',0);
    }

    public function test_public_disk_cannot_alias_another_product_images_via_symlink(): void
    {
        $other=$this->product('b893-other');
        Storage::disk('public')->put('products/'.$other->id.'/photo.png',self::png());
        $root=$this->publicRoot();
        $link=$root.'/products/'.$this->product->id;
        self::assertTrue(symlink($root.'/products/'.$other->id,$link));
        $this->links[]=$link;

        $image=$this->image('public','products/'.$this->product->id.'/photo.png');
        $this->get('/katalog/slika/'.$image->id)->assertNotFound();
        self::assertDatabaseCount('orders',0);
    }

    public function test_legacy_products_folder_symlink_cannot_escape_configured_legacy_root(): void
    {
        $outside=$this->outside();
        $root=$this->emptyLegacyRoot();
        self::assertTrue(symlink($outside,$root.'/uploads/products'));
        $this->links[]=$root.'/uploads/products';
        config()->set('services.legacy_media.root',$root);

        $image=$this->image('legacy','uploads/products/photo.png');
        $this->get('/katalog/slika/'.$image->id)->assertNotFound();
        self::assertDatabaseCount('orders',0);
    }

    public function test_regular_public_png_inside_own_product_directory_remains_accessible(): void
    {
        $relative='products/'.$this->product->id.'/photo.png';
        Storage::disk('public')->put($relative,self::png());
        $image=$this->image('public',$relative);
        $response=$this->get('/katalog/slika/'.$image->id)->assertOk()
            ->assertHeader('Content-Type','image/png')
            ->assertHeader('X-Content-Type-Options','nosniff');
        self::assertStringContainsString('no-store',(string)$response->headers->get('Cache-Control'));
    }

    public function test_regular_legacy_png_under_real_legacy_products_folder_remains_accessible(): void
    {
        $root=$this->emptyLegacyRoot();
        mkdir($root.'/uploads/products',0770,true);
        file_put_contents($root.'/uploads/products/photo.png',self::png());
        config()->set('services.legacy_media.root',$root);

        $image=$this->image('legacy','uploads/products/photo.png');
        $response=$this->get('/katalog/slika/'.$image->id)->assertOk()
            ->assertHeader('Content-Type','image/png');
        self::assertStringContainsString('no-store',(string)$response->headers->get('Cache-Control'));
    }

    private static function png(): string
    {
        return (string)base64_decode(self::PNG,true);
    }

    private function publicRoot(): string
    {
        $root=realpath(Storage::disk('public')->path(''));
        self::assertIsString($root);
        return $root;
    }

    private function outside(): string
    {
        $base=sys_get_temp_dir().'/ald1n-b893-outside-'.bin2hex(random_bytes(10));
        self::assertTrue(mkdir($base,0700,true));
        $this->outsideDirs[]=$base;
        file_put_contents($base.'/photo.png',self::png());
        return $base;
    }

    private function emptyLegacyRoot(): string
    {
        $base=sys_get_temp_dir().'/ald1n-b893-legacy-'.bin2hex(random_bytes(10));
        self::assertTrue(mkdir($base.'/uploads',0700,true));
        // The product file/folder is always removed by the test fixture.
        $this->outsideDirs[]=$base.'/uploads/products';
        $this->outsideDirs[]=$base.'/uploads';
        $this->outsideDirs[]=$base;
        return $base;
    }

    private function product(string $slug): Product
    {
        $owner=User::query()->firstOrCreate(['username'=>'b893-owner'],[
            'role_id'=>Role::query()->where('slug','superadmin')->valueOrFail('id'),
            'email'=>'b893-owner@synthetic.example.test',
            'first_name'=>'B893 Synthetic',
            'password_hash'=>Hash::make('test-only-secret-B893!'),
            'status'=>'active',
        ]);
        return Product::query()->create([
            'sku'=>strtoupper($slug),'slug'=>$slug,'name'=>'B893 Synthetic Product',
            'price_amount'=>12500,'price_currency'=>'RSD',
            'stock_quantity'=>4,'status'=>'active','created_by'=>$owner->id,
        ]);
    }

    private function image(string $disk,string $relative): ProductImage
    {
        return ProductImage::query()->create([
            'product_id'=>$this->product->id,'storage_disk'=>$disk,
            'file_path'=>$relative,'original_filename'=>'photo.png',
            'mime_type'=>'image/png','file_size'=>strlen(self::png()),
            'file_hash'=>str_repeat('a',64),'sort_order'=>1,'is_primary'=>true,
            'created_at'=>now(),
        ]);
    }
}
