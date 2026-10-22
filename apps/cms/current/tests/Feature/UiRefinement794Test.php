<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UiRefinement794Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_recent_order_uses_immutable_product_snapshot_and_secondary_order_number(): void
    {
        $actor = $this->person('superadmin');
        $order = $this->order($actor, 'UI794-ONE');
        $this->item($order, 'Snapshot laptop at purchase');
        Product::query()->update(['name' => 'Changed live catalog name']);
        $response = $this->actingAs($actor)->get('/')->assertOk();
        $row = $response->viewData('recentOrders')[0];
        self::assertSame('Snapshot laptop at purchase', $row['product_name']);
        self::assertSame(0, $row['other_products']);
        $response->assertSee('dashboard-order-product', false)->assertSee('UI794-ONE');
        self::assertSame(route('admin.orders.show', $order), $row['url']);
    }

    public function test_many_item_lines_have_count_but_quantity_does_not_count_as_extra_products(): void
    {
        $actor = $this->person('superadmin');
        $order = $this->order($actor, 'UI794-MULTI');
        $this->item($order, 'Laptop', 3);
        $this->item($order, 'Memory');
        $this->item($order, 'SSD');
        $response = $this->actingAs($actor)->get('/')->assertOk();
        self::assertSame(2, $response->viewData('recentOrders')[0]['other_products']);
        $response->assertSee('data-order-extra-products="2"', false);
    }

    public function test_empty_order_is_not_removed_and_snapshot_markup_is_escaped(): void
    {
        $actor = $this->person('superadmin');
        $this->order($actor, 'UI794-EMPTY');
        $withItem = $this->order($actor, 'UI794-ESCAPED');
        $this->item($withItem, '<script>alert("snapshot")</script>');
        $response = $this->actingAs($actor)->get('/')->assertOk();
        $response->assertSee('Stavke nisu dostupne')->assertSee('UI794-EMPTY');
        $response->assertSee('&lt;script&gt;alert', false)->assertDontSee('<script>alert("snapshot")</script>', false);
        self::assertCount(2, $response->viewData('recentOrders'));
    }

    public function test_recent_order_supplier_scope_and_archival_are_preserved(): void
    {
        $actor = $this->person('admin');
        $other = $this->person('admin', '-other');
        $mine = $this->order($actor, 'UI794-MINE');
        $foreign = $this->order($other, 'UI794-FOREIGN');
        $archived = $this->order($actor, 'UI794-ARCHIVED');
        $archived->update(['archived_at' => now()]);
        $this->item($mine, 'Visible snapshot');
        $this->item($foreign, 'Private foreign snapshot');
        $this->item($archived, 'Archived snapshot');
        $response = $this->actingAs($actor)->get('/')->assertOk();
        self::assertSame(['UI794-MINE'], array_column($response->viewData('recentOrders'), 'number'));
        $response->assertDontSee('Private foreign snapshot')->assertDontSee('Archived snapshot');
    }

    public function test_snapshot_fetch_is_eager_loaded_once_for_seven_orders(): void
    {
        $actor = $this->person('superadmin');
        for ($i = 0; $i < 8; $i++) $this->item($this->order($actor, 'UI794-'.$i), 'Laptop '.$i);
        DB::flushQueryLog(); DB::enableQueryLog();
        $response = $this->actingAs($actor)->get('/')->assertOk();
        $queries = array_filter(DB::getQueryLog(), static fn (array $row): bool =>
            str_contains($row['query'], 'order_items') && str_contains($row['query'], 'product_name') && str_contains($row['query'], ' in ('));
        DB::disableQueryLog();
        self::assertCount(7, $response->viewData('recentOrders'));
        self::assertCount(1, $queries);
    }

    public function test_desktop_command_row_preserves_period_filters_and_priorities(): void
    {
        $actor = $this->person('superadmin');
        $demoOrder = $this->order($actor, 'DEMO-2026-00001');
        $this->item($demoOrder, 'Dell OptiPlex 7040 Mini Tower');
        $this->item($demoOrder, 'SSD 256 GB');
        $this->item($demoOrder, 'Memorija 16 GB');
        Product::query()->first()->update(['stock_quantity' => 0]);
        $response = $this->actingAs($actor)->get('/?period=custom&date_from=2026-10-01&date_to=2026-10-22')->assertOk();
        $response->assertSee('data-dashboard-command-row', false)
            ->assertSee('data-dashboard-period="custom"', false)
            ->assertSee('d1-priority-heading', false)->assertSee('name="date_from"', false)
            ->assertSee('ald1n-ui-refinement.css', false);
        $this->exportFixture('dashboard', (string) $response->getContent());
    }

    public function test_user_editor_keeps_csrf_method_names_and_retirement_permissions(): void
    {
        $actor = $this->person('superadmin');
        $target = $this->person('user');
        $target->update(['first_name' => 'Demo', 'last_name' => 'Kupac', 'phone' => '0600000000']);
        $response = $this->actingAs($actor)->get('/admin/users')->assertOk();
        $response->assertSee('data-user-editor-sections', false)->assertSee('access-control-user-avatar', false)
            ->assertSee('access-control-user-danger-zone', false)
            ->assertSee(route('admin.users.retirement.preview', $target), false)
            ->assertDontSee(route('admin.users.retirement.preview', $actor), false)
            ->assertSee('name="_token"', false)->assertSee('name="_method" value="put"', false);
        foreach (['username','email','phone','first_name','last_name','password','role_id','user_group_id','status'] as $field) {
            $response->assertSee('name="'.$field.'"', false);
        }
        $this->exportFixture('users', (string) $response->getContent());
    }

    public function test_regular_user_cannot_open_user_management(): void
    {
        $this->actingAs($this->person('user'))->get('/admin/users')->assertForbidden();
    }

    public function test_product_view_retains_gallery_and_direct_sale_and_loads_refinement_styles(): void
    {
        $actor = $this->person('superadmin');
        $product = $this->product($actor);
        $response = $this->actingAs($actor)->get('/catalog/'.$product->slug)->assertOk();
        $response->assertSee('data-catalog-detail-ready="1"', false)
            ->assertSee('build16-product-summary', false)->assertSee('data-product-gallery', false)
            ->assertSee('ald1n-ui-refinement.css', false)->assertSee('Direktna prodaja');
        $this->exportFixture('product', (string) $response->getContent());
    }

    private function person(string $role, string $suffix = ''): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'username' => 'ui794-'.$role.$suffix, 'email' => 'ui794-'.$role.$suffix.'@example.test',
            'password_hash' => Hash::make('TestOnly794!'), 'status' => 'active',
        ]);
    }

    private function order(User $actor, string $number): Order
    {
        return Order::query()->create([
            'source_system'=>'laravel','order_number'=>$number,'user_id'=>$actor->id,'supplier_user_id'=>$actor->id,
            'status'=>'new','shipping_full_name'=>'Synthetic Customer','shipping_address'=>'Test street 1',
            'shipping_city'=>'Test','shipping_postal_code'=>'11000','shipping_phone'=>'0600000000',
            'subtotal_rsd'=>15000,'paid_total_rsd'=>0,
        ]);
    }

    private function product(User $actor): Product
    {
        $id = Product::query()->count() + 1;
        return Product::query()->create([
            'sku'=>'UI794-'.$id,'slug'=>'ui794-'.$id,'name'=>'Synthetic laptop '.$id,
            'price_amount'=>149,'price_currency'=>'EUR','stock_quantity'=>4,
            'description'=>'Synthetic product description.','status'=>'active','created_by'=>$actor->id,
        ]);
    }

    private function item(Order $order, string $name, int $quantity = 1): void
    {
        $product = $this->product(User::query()->findOrFail($order->user_id));
        OrderItem::query()->create([
            'order_id'=>$order->id,'product_id'=>$product->id,'product_sku'=>$product->sku,
            'product_name'=>$name,'quantity'=>$quantity,'unit_price_original'=>100,
            'original_currency'=>'RSD','unit_price_rsd'=>100,'line_total_rsd'=>100*$quantity,
        ]);
    }

    /** Test-only synthetic HTML export; never invoked on a production database. */
    private function exportFixture(string $name, string $html): void
    {
        $directory = getenv('B794_RENDER_DIR');
        if (!is_string($directory) || $directory === '') return;
        self::assertSame('testing', app()->environment());
        if (!is_dir($directory)) mkdir($directory, 0700, true);
        file_put_contents($directory.'/'.$name.'.html', $html);
    }
}
