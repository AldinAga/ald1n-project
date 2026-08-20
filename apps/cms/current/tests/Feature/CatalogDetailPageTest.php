<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CatalogDetailPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_superadmin_can_open_product_detail_by_slug(): void
    {
        $user = $this->superadmin();
        $product = $this->product($user, 'dell-latitude-5440');

        $response = $this->actingAs($user)->get('/catalog/'.$product->slug);

        $response->assertOk()
            ->assertSee('Dell Latitude 5440')
            ->assertSee('SKU: DETAIL-1')
            ->assertDontSee('Poruči artikal');
    }

    public function test_detail_page_renders_legacy_image_url_without_reading_the_file(): void
    {
        $user = $this->superadmin();
        $product = $this->product($user, 'asus-tuf-gaming-a15-fa506iu');
        $image = ProductImage::query()->create([
            'product_id' => $product->id,
            'file_path' => 'uploads/products/asus.jpg',
            'storage_disk' => 'legacy',
            'original_filename' => 'asus.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'sort_order' => 10,
            'is_primary' => true,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/catalog/'.$product->slug);

        $response->assertOk()->assertSee(route('media.product', ['image' => $image->id]), false);
    }

    public function test_invalid_image_record_does_not_turn_product_detail_into_server_error(): void
    {
        $user = $this->superadmin();
        $product = $this->product($user, 'ram-memorija-ddr4-8gb-samsung-skhynix-micron');
        ProductImage::query()->create([
            'product_id' => $product->id,
            'file_path' => '../outside.jpg',
            'storage_disk' => 'public',
            'original_filename' => 'outside.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'sort_order' => 10,
            'is_primary' => true,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/catalog/'.$product->slug);

        $response->assertOk()->assertSee('Bez slike');
    }

    public function test_product_detail_renders_accessible_gallery_lightbox_zoom_and_swipe_controls(): void
    {
        $user = $this->superadmin();
        $product = $this->product($user, 'gallery-product');

        foreach (['front.jpg', 'side.jpg', 'detail.jpg'] as $index => $filename) {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'file_path' => 'uploads/products/'.$filename,
                'storage_disk' => 'legacy',
                'original_filename' => $filename,
                'mime_type' => 'image/jpeg',
                'file_size' => 1024,
                'sort_order' => $index + 1,
                'is_primary' => $index === 0,
                'created_at' => now(),
            ]);
        }

        $response = $this->actingAs($user)->get('/catalog/'.$product->slug);

        $response->assertOk()
            ->assertSee('data-product-gallery', false)
            ->assertSee('data-product-lightbox', false)
            ->assertSee('data-gallery-thumbnail', false)
            ->assertSee('data-zoom-in', false)
            ->assertSee('data-zoom-out', false)
            ->assertSee('Prevuci prstom ili koristi strelice')
            ->assertSee('ArrowRight', false)
            ->assertSee('pointerup', false);
    }

    public function test_single_product_image_still_has_fullscreen_gallery(): void
    {
        $user = $this->superadmin();
        $product = $this->product($user, 'single-gallery-product');
        ProductImage::query()->create([
            'product_id' => $product->id,
            'file_path' => 'uploads/products/single.jpg',
            'storage_disk' => 'legacy',
            'original_filename' => 'single.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'sort_order' => 1,
            'is_primary' => true,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/catalog/'.$product->slug);

        $response->assertOk()
            ->assertSee('data-gallery-open', false)
            ->assertSee('data-product-lightbox', false)
            ->assertSee('1 / 1')
            ->assertDontSee('data-gallery-next', false);
    }

    public function test_product_detail_is_product_only_after_variant_decommission(): void
    {
        $user = $this->superadmin();
        $product = $this->product($user, 'product-only-detail-product');

        $response = $this->actingAs($user)->get('/catalog/'.$product->slug);

        $response->assertOk()
            ->assertSee('SKU: DETAIL-1')
            ->assertDontSee('data-product-variant-picker', false)
            ->assertDontSee('product_variant_id', false)
            ->assertDontSee('variantMap', false);
    }

    public function test_unknown_slug_returns_not_found_instead_of_server_error(): void
    {
        $response = $this->actingAs($this->superadmin())->get('/catalog/nepostojeci-artikal');

        $response->assertNotFound();
    }

    private function superadmin(): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'detail-admin',
            'email' => 'detail-admin@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function product(User $user, string $slug): Product
    {
        return Product::query()->create([
            'sku' => 'DETAIL-1',
            'name' => match ($slug) {
                'dell-latitude-5440' => 'Dell Latitude 5440',
                'asus-tuf-gaming-a15-fa506iu' => 'ASUS TUF Gaming A15 FA506IU',
                default => 'RAM memorija DDR4 8GB',
            },
            'slug' => $slug,
            'price_amount' => 100,
            'price_currency' => 'EUR',
            'description' => 'Opis artikla',
            'stock_quantity' => 2,
            'status' => 'active',
            'created_by' => $user->id,
        ]);
    }
}
