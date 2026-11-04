<?php
declare(strict_types=1);
namespace Tests\Feature;

use App\Models\{Product,Role,User};
use App\Services\OrderService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Owner rule: blank=automatic10%; any positive manual EUR overrides it. Synthetic DB only. */
final class ManualCommissionPolicyB1079Test extends TestCase
{
    use RefreshDatabase;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->owner=User::query()->create([
            'role_id'=>Role::query()->where('slug','superadmin')->valueOrFail('id'),
            'username'=>'b1079-owner','email'=>'b1079-owner@example.test',
            'password_hash'=>Hash::make('B1079-Synthetic-Only123!'),'status'=>'active',
        ]);
    }

    private function payload(): array
    {
        return [
            'name'=>'B1079 Synthetic Product','regenerate_sku'=>true,
            'price_amount'=>1000,'price_currency'=>'EUR','description'=>'Disposable policy fixture',
            'stock_quantity'=>1,'low_stock_threshold'=>1,'status'=>'draft',
        ];
    }

    #[DataProvider('invalidManualAmounts')]
    public function test_api_rejects_invalid_manual_amount_without_creating_product(mixed $value): void
    {
        $response=$this->actingAs($this->owner,'sanctum')
            ->postJson('/api/v1/admin/catalog/products',$this->payload()+['manual_commission_eur'=>$value]);
        $response->assertUnprocessable();
        self::assertStringContainsString('manual_commission_eur',(string)$response->getContent());
        self::assertDatabaseCount('products',0);
        self::assertDatabaseCount('stock_movements',0);
    }

    public static function invalidManualAmounts(): array
    {
        return ['zero'=>[0],'zero string'=>['0.00'],'negative'=>[-1],'below one cent'=>['0.001']];
    }

    #[DataProvider('acceptedManualAmounts')]
    public function test_api_preserves_manual_override_or_defaults_to_ten_percent(array $extra,?float $manual,float $expected): void
    {
        $response=$this->actingAs($this->owner,'sanctum')
            ->postJson('/api/v1/admin/catalog/products',$this->payload()+$extra)->assertCreated();
        $product=Product::query()->findOrFail($response->json('data.id'));
        self::assertSame($manual,$product->manual_commission_eur===null?null:(float)$product->manual_commission_eur);
        $snapshot=app(OrderService::class)->itemSnapshot($product,1,117.78);
        self::assertSame($expected,(float)$snapshot['commission_unit_eur_snapshot']);
        self::assertSame($manual===null?'automatic':'manual',$snapshot['commission_source_snapshot']);
        self::assertSame($manual===null?10.0:null,$snapshot['commission_rate_percent_snapshot']);
        self::assertDatabaseCount('orders',0);
    }

    public static function acceptedManualAmounts(): array
    {
        return [
            'omitted'=>[[],null,100.0],
            'null'=>[['manual_commission_eur'=>null],null,100.0],
            'blank'=>[['manual_commission_eur'=>''],null,100.0],
            'one cent'=>[['manual_commission_eur'=>0.01],0.01,0.01],
            'below ten percent'=>[['manual_commission_eur'=>2],2.0,2.0],
            'ten percent'=>[['manual_commission_eur'=>100],100.0,100.0],
            'above ten percent'=>[['manual_commission_eur'=>150],150.0,150.0],
        ];
    }

    public function test_api_update_rejects_zero_and_preserves_existing_override(): void
    {
        $this->actingAs($this->owner,'sanctum')
            ->postJson('/api/v1/admin/catalog/products',$this->payload()+['manual_commission_eur'=>2])->assertCreated();
        $product=Product::query()->sole();
        $this->putJson('/api/v1/admin/catalog/products/'.$product->id,$this->payload()+['manual_commission_eur'=>0])
            ->assertUnprocessable();
        self::assertSame(2.0,(float)$product->fresh()->manual_commission_eur);
        self::assertDatabaseCount('products',1);
    }

    public function test_superadmin_can_clear_override_back_to_automatic_ten_percent(): void
    {
        $this->actingAs($this->owner,'sanctum')
            ->postJson('/api/v1/admin/catalog/products',$this->payload()+['manual_commission_eur'=>2])->assertCreated();
        $product=Product::query()->sole();
        $this->putJson('/api/v1/admin/catalog/products/'.$product->id,$this->payload()+['manual_commission_eur'=>''])
            ->assertOk();
        $product->refresh();
        self::assertNull($product->manual_commission_eur);
        self::assertSame(100.0,(float)app(OrderService::class)->itemSnapshot($product,1,117.78)['commission_unit_eur_snapshot']);
    }

    public function test_web_rejects_zero_and_accepts_positive_below_ten_percent(): void
    {
        $this->actingAs($this->owner)->post('/admin/catalog',$this->payload()+['manual_commission_eur'=>0])
            ->assertSessionHasErrors('manual_commission_eur');
        self::assertDatabaseCount('products',0);
        $this->post('/admin/catalog',$this->payload()+['manual_commission_eur'=>2])->assertRedirect();
        self::assertSame(2.0,(float)Product::query()->sole()->manual_commission_eur);
    }
}
