<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ProductImageManagementTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); $this->seed(CoreAccessSeeder::class); }

    public function test_admin_can_upload_local_product_image(): void
    {
        Storage::fake('public');
        $admin = User::query()->create(['role_id'=>Role::query()->where('slug','admin')->valueOrFail('id'),'username'=>'image-admin','email'=>'image@test.local','password_hash'=>Hash::make('Secret123!'),'status'=>'active']);
        $product = Product::query()->create(['sku'=>'IMAGE-TEST','name'=>'Image test','slug'=>'image-test','price_amount'=>1,'price_currency'=>'EUR','description'=>'Opis','stock_quantity'=>1,'status'=>'active','created_by'=>$admin->id]);
        $this->actingAs($admin)->post('/admin/catalog/'.$product->id.'/images', ['images'=>[UploadedFile::fake()->image('product.jpg',800,600)]])->assertRedirect();
        $image = $product->images()->firstOrFail();
        self::assertSame('public', $image->storage_disk);
        self::assertTrue($image->is_primary);
        Storage::disk('public')->assertExists($image->file_path);
    }
}
