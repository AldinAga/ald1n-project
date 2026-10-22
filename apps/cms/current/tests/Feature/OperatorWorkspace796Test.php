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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

#[Group('b796')]
final class OperatorWorkspace796Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_superadmin_has_customer_orders_not_a_personal_shopping_center(): void
    {
        $admin = $this->person('superadmin');
        $seller = $this->person('user', 'a');
        $other = $this->person('user', 'b');
        $a = $this->order($seller, $admin, 'B796-USER-A');
        $b = $this->order($other, $admin, 'B796-USER-B');
        $this->item($a, 'Laptop snapshot A');
        $this->item($b, 'Laptop snapshot B');
        $response = $this->actingAs($admin)->get('/')->assertOk();
        $response->assertSee('data-operator-order-center="1"', false)
            ->assertSee('Laptop snapshot A')->assertSee('Laptop snapshot B')
            ->assertSee($seller->displayName())->assertSee($other->displayName())
            ->assertSee('Buyer B796-USER-A')->assertSee(route('admin.orders.show', $a), false)
            ->assertDontSee("Moje porud\u{017e}bine")
            ->assertDontSee('data-customer-center-ready="1"', false)
            ->assertDontSee('href="'.route('orders.create').'"', false)
            ->assertDontSee('href="'.route('cart.index').'"', false);
        self::assertFalse($response->viewData('portal')['enabled']);
        $this->fixture('operator-dashboard', $response->getContent());
    }

    public function test_regular_user_retains_personal_orders_and_cannot_read_other_users_orders(): void
    {
        $admin = $this->person('superadmin');
        $a = $this->person('user', 'a');
        $b = $this->person('user', 'b');
        $this->order($a, $admin, 'B796-MINE');
        $foreign = $this->order($b, $admin, 'B796-PRIVATE');
        $this->actingAs($a)->get('/')->assertOk()->assertSee('B796-MINE')
            ->assertDontSee('B796-PRIVATE')->assertSee('data-customer-center-ready="1"', false)
            ->assertDontSee('data-operator-order-center="1"', false);
        $this->get('/orders/'.$foreign->id)->assertNotFound();
        $this->get('/admin/orders/'.$foreign->id)->assertForbidden();
        $this->get('/order/new')->assertOk();
    }

    public function test_superadmin_personal_permissions_are_denied_consistently_but_managed_orders_remain(): void
    {
        $admin = $this->person('superadmin');
        foreach (['orders.create', 'orders.view_own', 'orders.cancel_own'] as $ability) {
            self::assertFalse(Gate::forUser($admin)->allows($ability), $ability.' Gate');
            self::assertFalse($admin->hasPermission($ability), $ability.' model');
            self::assertNotContains($ability, $admin->permissionSlugs());
        }
        self::assertTrue(Gate::forUser($admin)->allows('orders.manage'));
        self::assertTrue(Gate::forUser($admin)->allows('catalog.manage_products'));
        $this->actingAs($admin)->get('/admin/orders')->assertOk();
    }

    public function test_superadmin_cannot_use_web_personal_order_or_cart_endpoints(): void
    {
        $admin = $this->person('superadmin');
        $product = $this->product($admin);
        foreach (['/orders', '/order/new', '/cart'] as $url) {
            $this->actingAs($admin)->get($url)->assertForbidden();
        }
        $this->post('/orders', [])->assertForbidden();
        $this->post('/cart/items/'.$product->id, ['quantity'=>1])->assertForbidden();
        self::assertSame(0, Order::query()->count());
        self::assertSame(5, $product->fresh()->stock_quantity);
    }

    public function test_superadmin_cannot_create_a_personal_order_through_the_domain_service(): void
    {
        $admin = $this->person('superadmin');
        $product = $this->product($admin);
        $payload = ['shipping_full_name'=>'Synthetic Buyer', 'shipping_address'=>'Test street 1',
            'shipping_city'=>'Test', 'shipping_postal_code'=>'11000', 'shipping_phone'=>'0600000000',
            'payment_method'=>'cash_on_delivery', 'supplier_user_id'=>$admin->id,
            'items'=>[['product_id'=>$product->id, 'quantity'=>1]]];
        try {
            app(OrderService::class)->create($admin, $payload, 'b796-personal-denied');
            self::fail('Personal order creation must be rejected before idempotency or stock writes.');
        } catch (HttpException $e) {
            self::assertSame(403, $e->getStatusCode());
        }
        self::assertSame(0, Order::query()->count());
        $this->assertDatabaseCount('idempotency_keys', 0);
    }

    public function test_superadmin_api_personal_endpoints_are_denied_with_managed_transport_retained(): void
    {
        $admin = $this->person('superadmin');
        Sanctum::actingAs($admin, ['*']);
        $this->getJson('/api/v1/orders')->assertForbidden();
        $this->postJson('/api/v1/orders', [])->assertForbidden();
        $this->getJson('/api/v1/orders/options')->assertForbidden();
        $this->getJson('/api/v1/admin/orders')->assertOk();
    }

    public function test_catalog_preserves_direct_sale_but_removes_personal_cart_for_superadmin(): void
    {
        $admin = $this->person('superadmin');
        $product = $this->product($admin);
        $this->actingAs($admin)->get('/catalog/'.$product->slug)->assertOk()
            ->assertSee('Direktna prodaja')
            ->assertSee(route('admin.products.direct-sale', $product), false)
            ->assertDontSee('action="'.route('cart.items.store', $product).'"', false);
    }

    public function test_operational_dashboard_keeps_direct_sales_and_excludes_archived_orders(): void
    {
        $admin = $this->person('superadmin');
        $user = $this->person('user');
        $direct = $this->order($admin, $admin, 'B796-DIRECT');
        $direct->update(['sales_channel'=>'direct_sale', 'user_id'=>null]);
        $hidden = $this->order($user, $admin, 'B796-ARCHIVED');
        $hidden->update(['archived_at'=>now()]);
        $response = $this->actingAs($admin)->get('/')->assertOk();
        $response->assertSee('B796-DIRECT')->assertSee('Direktna prodaja')->assertDontSee('B796-ARCHIVED');
        self::assertSame('Buyer B796-DIRECT', $response->viewData('recentOrders')[0]['buyer_name']);
    }

    public function test_first_buyer_panel_contains_canonical_shipment_and_secure_external_link(): void
    {
        $admin = $this->person('superadmin');
        $seller = $this->person('user');
        $order = $this->order($seller, $admin, 'B796-TRACK');
        $this->shipment($order, $admin, 'https://carrier.example.test/track?code=SYN796');
        $response = $this->actingAs($admin)->get('/admin/orders/'.$order->id)->assertOk();
        self::assertFalse($response->headers->has('X-Ald1n-Detail-Fallback'));
        $panel = $this->buyerPanel($response->getContent());
        self::assertStringContainsString('SYN796', $panel);
        self::assertStringContainsString('Synthetic Carrier', $panel);
        self::assertStringContainsString('data-buyer-shipment-tracking', $panel);
        self::assertStringContainsString('https://carrier.example.test/track?code=SYN796', $panel);
        self::assertStringContainsString('rel="noopener noreferrer"', $panel);
        $this->fixture('operator-order', $response->getContent());
    }

    #[DataProvider('unsafeTrackingUrls')]
    public function test_unsafe_tracking_destination_is_not_rendered_as_a_link(string $url): void
    {
        $admin = $this->person('superadmin');
        $seller = $this->person('user');
        $order = $this->order($seller, $admin, 'B796-BADLINK');
        $this->shipment($order, $admin, $url);
        $response = $this->actingAs($admin)->get('/admin/orders/'.$order->id)->assertOk();
        $panel = $this->buyerPanel($response->getContent());
        self::assertStringContainsString('SYN796', $panel);
        self::assertStringNotContainsString('data-buyer-shipment-tracking', $panel);
        self::assertStringNotContainsString('href="'.e($url).'"', $response->getContent());
    }

    public static function unsafeTrackingUrls(): array
    {
        return [
            'script'=>['javascript:alert(1)'], 'data'=>['data:text/html,test'],
            'relative'=>['//carrier.example.test/track'], 'credentials'=>['https://user:pass@carrier.example.test/'],
            'plaintext'=>['http://carrier.example.test/'], 'localhost'=>['https://localhost/track'],
            'loopback'=>['https://127.0.0.1/'], 'control'=>["https://carrier.example.test/\ntrack"],
        ];
    }

    public function test_missing_shipment_has_truthful_empty_state_and_no_tracking_action(): void
    {
        $admin = $this->person('superadmin');
        $order = $this->order($this->person('user'), $admin, 'B796-NOSHIP');
        $response = $this->actingAs($admin)->get('/admin/orders/'.$order->id)->assertOk();
        $panel = $this->buyerPanel($response->getContent());
        self::assertStringContainsString('data-buyer-shipment-empty', $panel);
        self::assertStringNotContainsString('data-buyer-shipment-tracking', $panel);
    }

    private function buyerPanel(string $html): string
    {
        self::assertSame(1, preg_match('/<section\b[^>]*id="order-workspace-customer"[^>]*>(.*?)<\/section>/s', $html, $m));
        return $m[1];
    }

    private function person(string $role, string $suffix = ''): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id'=>$role === 'user' ? 1 : null,
            'username'=>'b796-'.$role.$suffix, 'email'=>'b796-'.$role.$suffix.'@example.test',
            'first_name'=>'Synthetic', 'last_name'=>$role.$suffix,
            'password_hash'=>Hash::make('TestOnly796!'), 'status'=>'active',
        ]);
    }

    private function order(User $user, User $supplier, string $number): Order
    {
        return Order::query()->create([
            'source_system'=>'laravel','order_number'=>$number,'user_id'=>$user->id,'supplier_user_id'=>$supplier->id,
            'status'=>'new','shipping_full_name'=>'Buyer '.$number,'shipping_address'=>'Synthetic street 1',
            'shipping_city'=>'Test','shipping_postal_code'=>'11000','shipping_phone'=>'0600000000',
            'subtotal_rsd'=>15000,'paid_total_rsd'=>0,
        ]);
    }

    private function product(User $actor): Product
    {
        $id = Product::query()->count()+1;
        return Product::query()->create([
            'sku'=>'B796-'.$id,'slug'=>'b796-'.$id,'name'=>'Synthetic laptop '.$id,
            'description'=>'Synthetic fixture','price_amount'=>15000,'price_currency'=>'RSD','stock_quantity'=>5,'status'=>'active','created_by'=>$actor->id,
        ]);
    }

    private function item(Order $order, string $name): void
    {
        $product = $this->product($order->user);
        OrderItem::query()->create([
            'order_id'=>$order->id,'product_id'=>$product->id,'product_sku'=>$product->sku,'product_name'=>$name,
            'quantity'=>1,'unit_price_original'=>15000,'original_currency'=>'RSD','unit_price_rsd'=>15000,'line_total_rsd'=>15000,
        ]);
    }

    private function shipment(Order $order, User $admin, string $url): void
    {
        OrderShipment::query()->create([
            'order_id'=>$order->id,'shipment_method'=>'courier','courier_name_snapshot'=>'Synthetic Carrier',
            'courier_tracking_url_snapshot'=>$url,'tracking_number_snapshot'=>'SYN796','shipped_at'=>now(),
            'recipient_name'=>'Synthetic Buyer','recorded_by'=>$admin->id,
        ]);
    }

    private function fixture(string $name, string $html): void
    {
        $dir = getenv('B796_RENDER_DIR');
        if (!is_string($dir) || $dir === '') return;
        if (!is_dir($dir)) mkdir($dir, 0700, true);
        file_put_contents($dir.'/'.$name.'.html', $html);
    }
}
