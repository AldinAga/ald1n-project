<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class AdminOrdersImageRotationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_admin_orders_index_renders_complete_protected_page(): void
    {
        $admin = $this->user('orders-super', 'superadmin');
        $customer = $this->user('orders-customer', 'user');

        Order::query()->create([
            'source_system' => 'laravel',
            'order_number' => 'ORD-TEST-0001',
            'user_id' => $customer->id,
            'supplier_user_id' => $admin->id,
            'supplier_name_snapshot' => $admin->displayName(),
            'status' => 'new',
            'inventory_state' => 'reserved',
            'shipping_full_name' => 'Test Kupac',
            'shipping_address' => 'Test 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060000000',
            'subtotal_rsd' => 12000,
            'eur_rsd_rate' => 117.5,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('data-orders-page-ready="1"', false)
            ->assertSee('ORD-TEST-0001')
            ->assertDontSee('Porudžbine privremeno nisu dostupne');
    }


    public function test_admin_and_customer_order_details_render_complete_pages(): void
    {
        $admin = $this->user('detail-super', 'superadmin');
        $customer = $this->user('detail-customer', 'user');
        $order = $this->order($admin, $customer, 'ORD-DETAIL-0001');

        $this->actingAs($admin)
            ->get('/admin/orders/'.$order->id)
            ->assertOk()
            ->assertSee('data-order-detail-ready="1"', false)
            ->assertSee('ORD-DETAIL-0001')
            ->assertDontSee('Detalji porudžbine privremeno nisu dostupni');

        $this->actingAs($customer)
            ->get('/orders/'.$order->id)
            ->assertOk()
            ->assertSee('data-order-user-detail-ready="1"', false)
            ->assertSee('ORD-DETAIL-0001')
            ->assertDontSee('Porudžbina privremeno nije dostupna');
    }

    public function test_orders_doctor_initializes_session_and_renders_both_detail_pages(): void
    {
        $admin = $this->user('doctor-super', 'superadmin');
        $customer = $this->user('doctor-customer', 'user');
        $order = $this->order($admin, $customer, 'ORD-DOCTOR-0001');

        $this->artisan('app:orders-doctor', [
            '--render' => true,
            '--order-id' => (string) $order->id,
        ])
            ->expectsOutputToContain('Administratorski detalj porudžbine #'.$order->id.' je uspešno renderovan.')
            ->expectsOutputToContain('Korisnički detalj porudžbine #'.$order->id.' je uspešno renderovan.')
            ->assertExitCode(0);
    }

    public function test_order_detail_survives_missing_optional_operational_tables(): void
    {
        $admin = $this->user('optional-super', 'superadmin');
        $customer = $this->user('optional-customer', 'user');
        $order = $this->order($admin, $customer, 'ORD-OPTIONAL-0001');

        foreach ([
            'order_ips_qr', 'audit_logs', 'order_documents', 'order_payments',
            'order_internal_notes', 'order_assignments', 'commission_status_history',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        $this->actingAs($admin)
            ->get('/admin/orders/'.$order->id)
            ->assertOk()
            ->assertSee('data-order-detail-ready="1"', false)
            ->assertSee('Napomena sistema')
            ->assertDontSee('Server Error');
    }


    public function test_order_detail_templates_use_scalar_presenter_data_only(): void
    {
        $adminView = (string) file_get_contents(resource_path('views/admin/orders/show.blade.php'));
        $adminPayments = (string) file_get_contents(resource_path('views/admin/orders/partials/payments.blade.php'));
        $userView = (string) file_get_contents(resource_path('views/orders/show.blade.php'));
        $userPayments = (string) file_get_contents(resource_path('views/orders/partials/payments.blade.php'));

        foreach ([$adminView, $adminPayments, $userView, $userPayments] as $template) {
            self::assertStringContainsString('$detail[', $template);
            self::assertStringNotContainsString('$order->', $template);
            self::assertStringNotContainsString('route(', $template);
            self::assertStringNotContainsString('auth()->', $template);
            self::assertStringNotContainsString('?->format(', $template);
        }
    }

    public function test_order_detail_controllers_have_non_503_fallback_and_exact_diagnostics(): void
    {
        $adminController = (string) file_get_contents(app_path('Http/Controllers/Admin/OrderController.php'));
        $userController = (string) file_get_contents(app_path('Http/Controllers/OrderController.php'));
        $doctor = (string) file_get_contents(app_path('Console/Commands/OrdersDoctorCommand.php'));

        foreach ([$adminController, $userController] as $controller) {
            self::assertStringContainsString('X-Ald1n-Detail-Fallback', $controller);
            self::assertStringContainsString('lastDetailRenderException', $controller);
            self::assertStringContainsString('200,', $controller);
        }

        self::assertStringContainsString('<fg=yellow>UZROK</>', $doctor);
        self::assertStringContainsString('getFile()', $doctor);
    }

    public function test_product_edit_exposes_left_and_right_rotation_for_legacy_image(): void
    {
        $admin = $this->user('image-super', 'superadmin');
        $product = Product::query()->create([
            'sku' => 'ROTATE-1',
            'name' => 'Artikal za rotaciju',
            'slug' => 'artikal-za-rotaciju',
            'price_amount' => 100,
            'price_currency' => 'EUR',
            'description' => 'Opis',
            'stock_quantity' => 1,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
        ProductImage::query()->create([
            'product_id' => $product->id,
            'file_path' => 'uploads/products/rotate.jpg',
            'storage_disk' => 'legacy',
            'original_filename' => 'rotate.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'sort_order' => 10,
            'is_primary' => true,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/catalog/'.$product->id.'/edit')
            ->assertOk()
            ->assertSee('Postojeće slike')
            ->assertSee('↶ 90°')
            ->assertSee('↷ 90°')
            ->assertSee('Legacy · pri rotaciji se pravi lokalna kopija');
    }


    private function order(User $admin, User $customer, string $number): Order
    {
        return Order::query()->create([
            'source_system' => 'laravel',
            'order_number' => $number,
            'user_id' => $customer->id,
            'supplier_user_id' => $admin->id,
            'supplier_name_snapshot' => $admin->displayName(),
            'supplier_role_snapshot' => 'SuperAdmin',
            'status' => 'new',
            'inventory_state' => 'reserved',
            'shipping_full_name' => 'Test Kupac',
            'shipping_address' => 'Test 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060000000',
            'subtotal_rsd' => 12000,
            'eur_rsd_rate' => 117.5,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
        ]);
    }

    private function user(string $username, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $username,
            'email' => $username.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }
}
