<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class OperatorWorkspace796Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_superadministrator_permissions_never_grant_personal_order_flow(): void
    {
        $operator = $this->person('superadmin', 'operator');
        foreach (['orders.create', 'orders.view_own', 'orders.cancel_own'] as $ability) {
            self::assertFalse($operator->hasPermission($ability), $ability);
            self::assertFalse(Gate::forUser($operator)->allows($ability), $ability);
            self::assertNotContains($ability, $operator->permissionSlugs());
        }
        self::assertTrue($operator->hasPermission('orders.manage'));
        self::assertTrue(Gate::forUser($operator)->allows('catalog.manage_products'));
    }

    public function test_superadministrator_dashboard_lists_operational_user_orders_not_personal_center(): void
    {
        $operator = $this->person('superadmin', 'operator');
        $seller = $this->person('user', 'seller-a');
        $other = $this->person('user', 'seller-b');
        $a = $this->order($seller, $operator, 'OP796-A');
        $b = $this->order($other, $operator, 'OP796-B');
        $this->item($a, $operator, 'First sold laptop');
        $this->item($b, $operator, 'Second sold laptop');
        $response = $this->actingAs($operator)->get('/')->assertOk();
        $response->assertSee('data-operator-order-center', false)
            ->assertSee('Porudžbine korisnika')->assertSee('seller-a')->assertSee('seller-b')
            ->assertSee('First sold laptop')->assertSee('Second sold laptop')
            ->assertSee(route('admin.orders.show', $a), false)
            ->assertDontSee('Moje porudžbine')->assertDontSee('data-customer-center-ready', false)
            ->assertDontSee('href="'.route('orders.create').'"', false)
            ->assertDontSee('href="'.route('cart.index').'"', false);
        self::assertFalse($response->viewData('portal')['enabled']);
        $this->export('operator', (string) $response->getContent());
    }

    public function test_ordinary_user_retains_own_orders_without_foreign_or_administrative_access(): void
    {
        $operator = $this->person('superadmin', 'operator');
        $seller = $this->person('user', 'seller-a');
        $other = $this->person('user', 'seller-b');
        $a = $this->order($seller, $operator, 'VISIBLE796');
        $b = $this->order($other, $operator, 'PRIVATE796');
        $this->actingAs($seller)->get('/')->assertOk()->assertSee('Moje porudžbine')
            ->assertSee('VISIBLE796')->assertDontSee('PRIVATE796')
            ->assertDontSee('data-operator-order-center', false);
        $this->get('/orders')->assertOk()->assertSee('VISIBLE796')->assertDontSee('PRIVATE796');
        $this->get('/admin/orders')->assertForbidden();
        $this->get('/orders/'.$b->id)->assertNotFound();
        $this->get('/order/new')->assertOk();
    }

    public function test_personal_web_endpoints_fail_closed_for_superadministrator_without_writes(): void
    {
        $operator = $this->person('superadmin', 'operator');
        $product = $this->product($operator);
        $this->actingAs($operator)->get('/orders')->assertForbidden();
        $this->get('/order/new')->assertForbidden();
        $this->get('/cart')->assertForbidden();
        $this->post('/orders', [])->assertForbidden();
        $this->post('/cart/items/'.$product->id, ['quantity'=>1])->assertForbidden();
        self::assertSame(0, Order::query()->count());
        self::assertSame(5, (int) $product->fresh()->stock_quantity);
        $this->get('/catalog/'.$product->slug)->assertOk()->assertSee('Direktna prodaja')
            ->assertDontSee('data-cart-add-control', false);
    }

    public function test_api_personal_create_is_forbidden_but_managed_orders_remain_available(): void
    {
        $operator = $this->person('superadmin', 'operator');
        Sanctum::actingAs($operator, ['*']);
        $this->postJson('/api/v1/orders', [])->assertForbidden();
        $this->getJson('/api/v1/admin/orders')->assertOk();
        self::assertSame(0, Order::query()->count());
    }

    public function test_personal_order_factory_rejects_superadministrator_before_idempotency_or_stock(): void
    {
        $operator = $this->person('superadmin', 'operator');
        try {
            app(OrderService::class)->create($operator, [], 'operator-no-self-order');
            self::fail('A SuperAdministrator cannot be the personal order customer.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }
        self::assertSame(0, Order::query()->count());
    }

    public function test_first_buyer_panel_has_canonical_tracking_and_safe_external_action(): void
    {
        $operator = $this->person('superadmin', 'operator');
        $seller = $this->person('user', 'seller');
        $order = $this->order($seller, $operator, 'TRACK796');
        $this->item($order, $operator, 'Synthetic tracking laptop');
        $this->shipment($order, 'https://tracking.example.test/parcel?code=TEST796');
        $response = $this->actingAs($operator)->get('/admin/orders/'.$order->id)->assertOk();
        $html = (string) $response->getContent();
        $panel = $this->buyerPanel($html);
        self::assertStringContainsString('TEST796', $panel);
        self::assertStringContainsString('Synthetic Courier', $panel);
        self::assertStringContainsString('Prati pošiljku', $panel);
        self::assertStringContainsString('rel="noopener noreferrer"', $panel);
        self::assertStringContainsString('https://tracking.example.test/parcel?code=TEST796', $panel);
        $this->export('order', $html);
    }

    public function test_unsafe_tracking_url_is_never_rendered_as_a_link(): void
    {
        $operator = $this->person('superadmin', 'operator');
        $order = $this->order($this->person('user', 'seller'), $operator, 'BAD796');
        $this->shipment($order, 'javascript:alert(document.cookie)');
        $html = (string) $this->actingAs($operator)->get('/admin/orders/'.$order->id)->assertOk()->getContent();
        self::assertStringNotContainsString('href="javascript:', $html);
        self::assertStringContainsString('TEST796', $this->buyerPanel($html));
        self::assertStringNotContainsString('data-buyer-track-link', $this->buyerPanel($html));
    }

    public function test_unrecorded_shipment_has_truthful_empty_state_not_a_tracking_link(): void
    {
        $operator = $this->person('superadmin', 'operator');
        $order = $this->order($this->person('user', 'seller'), $operator, 'EMPTY796');
        $panel = $this->buyerPanel((string) $this->actingAs($operator)->get('/admin/orders/'.$order->id)->assertOk()->getContent());
        self::assertStringContainsString('Pošiljka još nije evidentirana', $panel);
        self::assertStringNotContainsString('data-buyer-track-link', $panel);
    }

    public function test_legacy_tracking_number_is_visible_without_invented_carrier_url(): void
    {
        $operator = $this->person('superadmin', 'operator');
        $order = $this->order($this->person('user', 'seller'), $operator, 'LEGACY796');
        $order->update(['tracking_number'=>'LEGACY-TRACK-796']);
        $panel = $this->buyerPanel((string) $this->actingAs($operator)->get('/admin/orders/'.$order->id)->assertOk()->getContent());
        self::assertStringContainsString('LEGACY-TRACK-796', $panel);
        self::assertStringNotContainsString('data-buyer-track-link', $panel);
    }

    private function buyerPanel(string $html): string
    {
        $start = strpos($html, 'id="order-workspace-customer"');
        self::assertNotFalse($start);
        $end = strpos($html, '</section>', $start);
        self::assertNotFalse($end);
        return substr($html, $start, $end-$start);
    }

    private function person(string $role, string $name): User
    {
        return User::query()->create(['role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'user_group_id'=>$role==='user'?1:null,'username'=>$name,'email'=>$name.'@example.test',
            'password_hash'=>Hash::make('TestOnly796!'),'status'=>'active']);
    }
    private function order(User $seller, User $operator, string $number): Order
    {
        return Order::query()->create(['source_system'=>'laravel','order_number'=>$number,
            'user_id'=>$seller->id,'supplier_user_id'=>$operator->id,'status'=>'new',
            'shipping_full_name'=>'Synthetic Customer','shipping_address'=>'Test street 1',
            'shipping_city'=>'Test','shipping_postal_code'=>'11000','shipping_phone'=>'0600000000',
            'subtotal_rsd'=>15000,'paid_total_rsd'=>0]);
    }
    private function product(User $operator): Product
    {
        $id=Product::query()->count()+1;
        return Product::query()->create(['sku'=>'OP796-'.$id,'slug'=>'op796-'.$id,'name'=>'Synthetic laptop '.$id,
            'description'=>'Synthetic isolated product.','price_amount'=>149,'price_currency'=>'EUR','stock_quantity'=>5,'status'=>'active','created_by'=>$operator->id]);
    }
    private function item(Order $order, User $operator, string $name): void
    {
        $product=$this->product($operator);
        OrderItem::query()->create(['order_id'=>$order->id,'product_id'=>$product->id,'product_sku'=>$product->sku,
            'product_name'=>$name,'quantity'=>1,'unit_price_original'=>100,'original_currency'=>'RSD',
            'unit_price_rsd'=>100,'line_total_rsd'=>100]);
    }
    private function shipment(Order $order, string $url): void
    {
        OrderShipment::query()->create(['order_id'=>$order->id,'shipment_method'=>'courier',
            'courier_name_snapshot'=>'Synthetic Courier','courier_tracking_url_snapshot'=>$url,
            'tracking_number_snapshot'=>'TEST796','shipped_at'=>now(),'recipient_name'=>'Synthetic Customer']);
    }
    private function export(string $name, string $html): void
    {
        $dir=getenv('B796_RENDER_DIR');
        if (!is_string($dir)||$dir==='') return;
        self::assertSame('testing',app()->environment());
        if (!is_dir($dir)) mkdir($dir,0700,true);
        file_put_contents($dir.'/'.$name.'.html',$html);
    }
}
