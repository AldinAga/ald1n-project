<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Models\{Order,OrderItem,Product,Role,User};
use App\Services\{DirectSaleService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Mail};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class DirectSaleMaxUnitPriceContractTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        Mail::fake();
        $this->actor=User::query()->create([
            'role_id'=>Role::query()->where('slug','superadmin')->valueOrFail('id'),
            'username'=>'b1090-direct-sale','email'=>'b1090@example.test',
            'first_name'=>'Synthetic','password_hash'=>Hash::make('B1090-Synthetic-Only123!'),
            'status'=>'active',
        ]);
        app(SettingsService::class)->putMany(['eur_rsd_rate'=>'117.41'],$this->actor->id);
    }

    public function test_catalog_unit_price_rsd_matches_product_currency_rules(): void
    {
        $rsd=$this->product('RSD',13000.129,'rsd');
        $eur=$this->product('EUR',100,'eur');
        $first=$this->record($rsd,13000.129,'RSD','rsd');
        $second=$this->record($eur,100,'EUR','eur');
        foreach([[$first,13000.13,'RSD',13000.13],[$second,100.0,'EUR',11741.0]] as [$order,$original,$currency,$canonical]) {
            $item=OrderItem::query()->where('order_id',$order->id)->sole();
            self::assertSame($original,(float)$item->unit_price_original);
            self::assertSame($currency,$item->original_currency);
            self::assertSame($canonical,(float)$item->unit_price_rsd);
            self::assertSame($canonical,(float)$order->subtotal_rsd);
            self::assertSame($canonical,(float)$order->paid_total_rsd);
            self::assertSame('paid',$order->payment_state);
        }
        self::assertSame(4,(int)$rsd->fresh()->stock_quantity);
        self::assertSame(4,(int)$eur->fresh()->stock_quantity);
        self::assertDatabaseCount('orders',2);
        self::assertDatabaseCount('stock_movements',2);
        self::assertDatabaseCount('order_payments',2);
    }

    public function test_price_below_and_equal_catalog_are_allowed_but_above_is_rejected(): void
    {
        // Historical testcase ID retained; Report152/8baa290 explicitly removed this ceiling.
        $product=$this->product('RSD',13000,'reference');
        foreach([12999.99,13000.0,13000.01] as $i=>$amount) {
            $order=$this->record($product->fresh(),$amount,'RSD','reference-'.$i);
            self::assertSame($amount,(float)$order->subtotal_rsd);
            self::assertSame($amount,(float)OrderItem::query()->where('order_id',$order->id)->sole()->unit_price_rsd);
            $replay=$this->record($product->fresh(),$amount,'RSD','reference-'.$i);
            self::assertSame($order->id,$replay->id);
        }
        self::assertSame(13000.0,(float)$product->fresh()->price_amount);
        self::assertSame(2,(int)$product->fresh()->stock_quantity);
        self::assertDatabaseCount('orders',3);
        self::assertDatabaseCount('stock_movements',3);
        self::assertDatabaseCount('order_payments',3);
        self::assertDatabaseCount('order_commissions',0);
    }

    public function test_eur_product_requires_a_positive_rate(): void
    {
        $product=$this->product('EUR',100,'invalid-rate');
        foreach(['','0','-1'] as $i=>$rate) {
            app(SettingsService::class)->putMany(['eur_rsd_rate'=>$rate],$this->actor->id);
            try {
                $this->record($product,100,'EUR','invalid-rate-'.$i);
                self::fail('A missing/non-positive rate must reject an actual EUR sale.');
            } catch(ValidationException $exception) {
                self::assertArrayHasKey('sale_price_currency',$exception->errors());
            }
        }
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('order_payments',0);
        self::assertDatabaseCount('stock_movements',0);
        self::assertSame(5,(int)$product->fresh()->stock_quantity);
    }

    public function test_source_contract_is_product_only_and_preserves_order_price_parity(): void
    {
        $root=dirname(__DIR__,2);
        $direct=(string)file_get_contents($root.'/app/Services/DirectSaleService.php');
        $orders=(string)file_get_contents($root.'/app/Services/OrderService.php');
        self::assertStringNotContainsString('DIRECT_SALE_MAX_UNIT_PRICE_GUARD',$direct);
        self::assertStringNotContainsString('assertSalePriceWithinCatalogUnitPrice',$direct);
        self::assertStringContainsString('if ($salePriceAmount <= 0)',$direct);
        self::assertStringContainsString("'unit_price_original' => \$salePriceAmount",$direct);
        self::assertStringContainsString("'original_currency' => \$salePriceCurrency",$direct);
        self::assertStringContainsString("'unit_price_rsd' => \$salePrice",$direct);
        self::assertStringContainsString('$lineTotal = round($salePrice * $quantity, 2);',$direct);
        foreach([$direct,$orders] as $source) {
            self::assertStringNotContainsString('ProductVariant',$source);
            self::assertStringNotContainsString('product_variant_id',$source);
        }
        self::assertStringContainsString('private function priceRsd(Product $product, ?float $rate): float',$orders);
        self::assertStringContainsString("if (\$product->price_currency === 'RSD')",$orders);
        self::assertStringContainsString('return round((float) $product->price_amount * $rate, 2);',$orders);
    }

    public function test_non_positive_direct_sale_amount_is_rejected_without_writes(): void
    {
        $product=$this->product('RSD',13000,'invalid-price');
        foreach([0.0,-0.01] as $i=>$amount) {
            try {
                $this->record($product,$amount,'RSD','invalid-price-'.$i);
                self::fail('A non-positive direct sale must not reach stock/payment mutation.');
            } catch(ValidationException $exception) {
                self::assertArrayHasKey('sale_price_amount',$exception->errors());
            }
        }
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('stock_movements',0);
        self::assertDatabaseCount('order_payments',0);
        self::assertSame(5,(int)$product->fresh()->stock_quantity);
    }

    private function product(string $currency,float $amount,string $suffix): Product
    {
        return Product::query()->create([
            'sku'=>'B1090-'.$suffix,'slug'=>'b1090-'.$suffix,
            'name'=>'B1090 synthetic product','description'=>'Disposable CI only',
            'price_amount'=>$amount,'price_currency'=>$currency,
            'stock_quantity'=>5,'status'=>'active','created_by'=>$this->actor->id,
        ]);
    }

    private function record(Product $product,float $amount,string $currency,string $suffix): Order
    {
        return app(DirectSaleService::class)->record($product,$this->actor,[
            'buyer_name'=>'B1090 Synthetic Buyer','quantity'=>1,
            'sale_price_amount'=>$amount,'sale_price_currency'=>$currency,
            'payment_method'=>'cash',
        ],'b1090-direct-sale-'.$suffix);
    }
}
