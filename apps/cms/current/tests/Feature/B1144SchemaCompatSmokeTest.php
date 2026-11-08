<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * B1144 schema-compatibility smoke test.
 *
 * Runs against a database that was migrated by the workflow with every
 * migration EXCEPT the three that the production host has not applied:
 *   2026_10_23_020000_create_public_order_accesses
 *   2026_10_23_020100_create_public_checkout_attempts
 *   2026_10_24_030000_add_retail_customer_role
 *
 * It must NEVER use RefreshDatabase: that trait would run migrate:fresh with
 * all 71 migrations and silently destroy the condition under test. Every
 * write here is rolled back by DatabaseTransactions.
 */
final class B1144SchemaCompatSmokeTest extends TestCase
{
    use DatabaseTransactions;

    /** @var list<string> */
    private const SKIPPED_URIS = ['logout', 'auth/google', 'auth/google/callback'];

    protected function setUp(): void
    {
        parent::setUp();

        // Guard: refuse to produce a verdict on the wrong schema.
        self::assertFalse(Schema::hasTable('public_order_accesses'), 'schema must be the 68-migration production schema');
        self::assertFalse(Schema::hasTable('public_checkout_attempts'), 'schema must be the 68-migration production schema');
        self::assertTrue(Schema::hasTable('roles'));
        self::assertSame(
            0,
            DB::table('roles')->where('slug', 'customer')->count(),
            'production has no customer role before 2026_10_24_030000; the workflow removes the seeded one'
        );
        self::assertFalse(config('public_storefront.enabled'), 'OFF clamp: public_storefront.enabled must be false');
        self::assertFalse(config('public_storefront.checkout_enabled'), 'OFF clamp: checkout_enabled must be false');
    }

    public function test_pending_migrations_are_exactly_the_three_not_on_production(): void
    {
        $expected = [
            '2026_10_23_020000_create_public_order_accesses',
            '2026_10_23_020100_create_public_checkout_attempts',
            '2026_10_24_030000_add_retail_customer_role',
        ];
        $onDisk = [];
        foreach (glob(database_path('migrations/*.php')) ?: [] as $file) {
            $onDisk[] = basename($file, '.php');
        }
        $ran = DB::table('migrations')->pluck('migration')->all();
        $pending = array_values(array_diff($onDisk, $ran));
        sort($pending);

        self::assertSame($expected, $pending);
        self::assertCount(68, $ran);
    }

    public function test_superadmin_can_log_in_through_the_real_login_route(): void
    {
        $this->person('b1144-login');

        $response = $this->post('/login', ['login' => 'b1144-login', 'password' => 'Secret123!']);

        $response->assertSessionHasNoErrors();
        self::assertTrue($response->isRedirection(), 'login must redirect, got '.$response->getStatusCode());
        $this->assertAuthenticated();
    }

    public function test_every_parameterless_authenticated_get_route_responds_for_superadmin(): void
    {
        $admin = $this->person('b1144-sweep');
        // Rate limiters are not under test; 20+ export routes would otherwise hit 429.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $rows = [];
        $failures = [];
        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (!in_array('GET', $route->methods(), true) || str_contains($route->uri(), '{')) {
                continue;
            }
            $uri = $route->uri();
            if (in_array($uri, self::SKIPPED_URIS, true) || str_starts_with($uri, 'api/')
                || str_starts_with($uri, '_') || $uri === 'up' || str_starts_with($uri, 'storage/')) {
                continue;
            }
            $middleware = $route->gatherMiddleware();
            if (in_array('guest', $middleware, true) || !in_array('auth', $middleware, true)) {
                continue;
            }

            $path = '/'.ltrim($uri, '/');
            $status = 0;
            $cause = '';
            $response = null;
            $exception = null;
            try {
                $response = $this->actingAs($admin)->get($path);
                $status = $response->getStatusCode();
                $exception = $response->baseResponse->exception ?? null;
                if ($exception instanceof \Throwable) {
                    $cause = get_class($exception).': '.mb_substr($exception->getMessage(), 0, 300);
                }
                if ($status >= 300 && $status < 400) {
                    $cause = trim($cause.' -> '.(string) $response->headers->get('Location'));
                }
            } catch (\Throwable $e) {
                $status = 599;
                $cause = get_class($e).': '.mb_substr($e->getMessage(), 0, 300);
            }
            $line = sprintf('%d %s%s', $status, $path, $cause !== '' ? ' <= '.$cause : '');
            $rows[] = $line;
            // 403 = permission model (customer-portal routes are not for superadmin),
            // 3xx = documented redirect. Schema incompatibility shows up as 5xx / 404 / crash.
            $swallowed = isset($exception) && $exception instanceof \Throwable
                && !($exception instanceof \Illuminate\Validation\ValidationException)
                && !($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface)
                && $status < 500;
            $degraded = $status === 200 && isset($response)
                && !$response->baseResponse instanceof \Symfony\Component\HttpFoundation\StreamedResponse
                && preg_match('/privremeno nis?u? dostup/iu', (string) $response->getContent()) === 1;
            if ($status >= 500 || $status === 404 || $status === 0 || $swallowed || $degraded) {
                $failures[] = $line.($swallowed ? ' [SWALLOWED_EXCEPTION]' : '').($degraded ? ' [DEGRADED_PAGE]' : '');
            }
        }

        $this->writeReport('b1144-routes.txt', $rows);
        self::assertGreaterThan(40, count($rows), 'route sweep must actually cover the admin surface');
        self::assertSame([], $failures, "Failing routes on the 68-migration schema:\n".implode("\n", $failures));
    }

    public function test_dashboard_and_main_admin_pages_return_ok(): void
    {
        $admin = $this->person('b1144-main');

        foreach (['/', '/account', '/admin/orders', '/admin/users', '/admin/settings', '/admin/settings/public-storefront'] as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
        // /admin/catalog is a documented redirect (see sweep report for the target).
        $this->actingAs($admin)->get('/admin/catalog')->assertRedirect();
    }

    public function test_admin_order_detail_renders_for_plain_and_public_retail_orders(): void
    {
        $admin = $this->person('b1144-orders');
        $buyer = User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1,
            'username' => 'b1144-buyer',
            'email' => 'b1144-buyer@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        $plain = $this->order($admin, $buyer, 'B1144-PLAIN-1', 'order');
        // A public_retail order drives PublicRetailPreferenceService (view composer
        // on admin.orders.show) which touches public_checkout_attempts.
        $retail = $this->order($admin, $buyer, 'B1144-RETAIL-1', 'public_retail');

        foreach ([$plain, $retail] as $order) {
            $this->actingAs($admin)->get('/admin/orders/'.$order->id)->assertOk();
            $this->actingAs($admin)->get('/admin/orders')->assertOk();
        }
        $this->actingAs($buyer)->get('/orders/'.$plain->id)->assertOk();
    }

    public function test_admin_product_pages_render(): void
    {
        $admin = $this->person('b1144-product');
        $product = Product::query()->create([
            'sku' => 'B1144-SKU', 'slug' => 'b1144-product', 'name' => 'B1144 product',
            'description' => 'schema compat', 'price_amount' => 12000, 'price_currency' => 'RSD',
            'stock_quantity' => 4, 'status' => 'active', 'created_by' => $admin->id,
        ]);

        foreach (['/edit', '/images'] as $suffix) {
            $this->actingAs($admin)->get('/admin/catalog/'.$product->id.$suffix)->assertOk();
        }
    }

    public function test_public_storefront_routes_are_404_for_guests_under_the_off_clamp(): void
    {
        // Even with the DB flag ON, the config clamp must keep everything closed.
        Setting::query()->updateOrCreate(
            ['setting_key' => 'public_storefront_open'],
            ['setting_value' => '1']
        );

        foreach (['/katalog', '/registracija', '/korpa', '/porucivanje', '/katalog/b1144-no-such-product', '/porudzbina/'.str_repeat('a', 64)] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_public_storefront_routes_are_404_with_db_flag_off_too(): void
    {
        Setting::query()->updateOrCreate(
            ['setting_key' => 'public_storefront_open'],
            ['setting_value' => '0']
        );

        foreach (['/katalog', '/registracija', '/korpa'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    private function person(string $username): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => $username,
            'email' => $username.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function order(User $admin, User $buyer, string $number, string $channel): Order
    {
        return Order::query()->create([
            'source_system' => 'laravel',
            'sales_channel' => $channel,
            'order_number' => $number,
            'user_id' => $buyer->id,
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

    /** @param list<string> $lines */
    private function writeReport(string $name, array $lines): void
    {
        $dir = getenv('B1144_REPORT_DIR');
        if (is_string($dir) && $dir !== '' && is_dir($dir)) {
            file_put_contents($dir.'/'.$name, implode("\n", $lines)."\n");
        }
    }
}
