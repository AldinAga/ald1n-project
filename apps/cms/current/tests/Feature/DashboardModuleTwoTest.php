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

/**
 * Module 2 (admin dashboard on Kobalt components): what each role sees, with real data,
 * the links behind it and the markup contract. Author: Ald1n <https://ald1n.com>
 */
final class DashboardModuleTwoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_superadmin_sees_period_data_in_kpis_trend_and_brand_mix(): void
    {
        $admin = $this->person('superadmin');
        $this->completedOrder($admin, 'M2-DONE-001', '2026-01-15 12:00:00', 'Lenovo', 2000.0, 1200.0);

        $html = (string) $this->actingAs($admin)
            ->get('/?period=custom&date_from=2026-01-01&date_to=2026-01-31')->assertOk()->getContent();
        $dash = $this->dashboard($html);

        // Revenue 2.000,00, gross profit 800,00 (revenue minus the purchase snapshot), net 800,00 (no commissions).
        self::assertMatchesRegularExpression('/data-dashboard-d1-kpi="revenue".{0,900}2\.000,00\s*(?:<[^>]+>\s*)*RSD/s', $dash);
        self::assertMatchesRegularExpression('/data-dashboard-d1-kpi="gross".{0,900}800,00\s*(?:<[^>]+>\s*)*RSD/s', $dash);
        self::assertMatchesRegularExpression('/data-dashboard-d1-kpi="net".{0,900}800,00\s*(?:<[^>]+>\s*)*RSD/s', $dash);

        // Trend: one bar pair, a text alternative and the exact-values table.
        self::assertSame(1, substr_count($dash, 'a1-trend__pair'));
        self::assertStringContainsString('Tačne vrednosti po periodima', $dash);
        self::assertMatchesRegularExpression('/<th scope="row">15\.01\.2026\.<\/th><td class="a1-table__num">2\.000,00<\/td><td class="a1-table__num">800,00<\/td>/', $dash);

        // Brand mix: both metrics rendered, the slice carries its label, value and share as text.
        self::assertStringContainsString('data-dashboard-d3="1"', $dash);
        self::assertMatchesRegularExpression('/a1-mix__label">Lenovo<.{0,200}a1-mix__value">2\.000,00 RSD<.{0,120}a1-mix__pct">100,0%</s', $dash);
        self::assertMatchesRegularExpression('/data-d3-view="units"\s+hidden/', $dash);
        self::assertMatchesRegularExpression('/data-d3-mode="revenue" aria-pressed="true"/', $dash);
        self::assertMatchesRegularExpression('/data-d3-mode="units" aria-pressed="false"/', $dash);
        self::assertStringNotContainsString('conic-gradient', $dash);
    }

    public function test_trend_table_period_cells_are_the_chart_bucket_labels_in_dd_mm_yyyy(): void
    {
        $admin = $this->person('superadmin');
        $this->completedOrder($admin, 'M2-B-001', '2026-01-12 10:00:00', 'Lenovo', 1000.0, 600.0);
        $this->completedOrder($admin, 'M2-B-002', '2026-01-15 10:00:00', 'ASUS', 2000.0, 1200.0);
        $this->completedOrder($admin, 'M2-B-003', '2026-01-20 10:00:00', 'HP', 3000.0, 1800.0);

        $dash = $this->dashboard((string) $this->actingAs($admin)
            ->get('/?period=custom&date_from=2026-01-01&date_to=2026-01-31')->assertOk()->getContent());

        // Axis labels (MM-DD) of the three buckets, in order.
        preg_match_all('/class="a1-trend__label">([^<]*)</', $dash, $axis);
        self::assertSame(['01-12', '01-15', '01-20'], $axis[1]);
        // Table rows: each bucket's own date, formatted like the rest of the UI.
        preg_match_all('/<th scope="row">([^<]*)<\/th><td class="a1-table__num">/', $dash, $rows);
        self::assertSame(['12.01.2026.', '15.01.2026.', '20.01.2026.'], $rows[1]);
        // The same buckets: the table day matches the axis day.
        foreach ($rows[1] as $i => $label) {
            self::assertSame($axis[1][$i], substr($label, 3, 2).'-'.substr($label, 0, 2));
        }
    }

    public function test_superadmin_period_without_sales_shows_empty_states_not_zero_charts(): void
    {
        $html = (string) $this->actingAs($this->person('superadmin'))
            ->get('/?period=custom&date_from=2020-01-01&date_to=2020-01-31')->assertOk()->getContent();
        $dash = $this->dashboard($html);

        self::assertStringContainsString('Trend će biti prikazan kada postoje završene porudžbine', $dash);
        self::assertStringContainsString('Nema prodaje u izabranom periodu.', $dash);
        self::assertStringNotContainsString('a1-trend__pair', $dash);
        self::assertStringContainsString('class="a1-empty"', $dash);
    }

    public function test_superadmin_markup_contract_and_links(): void
    {
        $html = (string) $this->actingAs($this->person('superadmin'))->get('/')->assertOk()->getContent();
        $dash = $this->dashboard($html);

        self::assertSame(1, substr_count($dash, '<h1 '));
        self::assertStringNotContainsString('Nazad na vrh', $html);
        self::assertStringNotContainsString('ald1n-dashboard-d1.css', $html);
        self::assertSame(0, preg_match('/class="(?:[^"]*\s)?(?:panel|dashboard-[a-z0-9-]+|build16-[a-z0-9-]+|portal-[a-z-]+)(?:\s[^"]*)?"/', $dash), 'Old dashboard classes must be gone from the markup.');
        self::assertSame(0, preg_match('/style="[^"]*(?:#[0-9a-f]{3,8}|rgb|hsl)/i', $dash), 'No colours in inline styles.');

        // Period selector: real buttons of a GET form, exactly one pressed.
        self::assertSame(3, substr_count($dash, 'class="a1-seg__opt" name="period"'));
        self::assertSame(1, substr_count($dash, 'name="period" value="default" aria-current="true"'));

        // Every KPI tile is an outermost link to the same targets as before.
        foreach ([
            'revenue' => route('admin.reports.index'), 'gross' => route('admin.reports.index'), 'net' => route('admin.reports.index'),
            'receivables' => route('admin.receivables.index'), 'purchase' => route('admin.products.purchase-costs'),
            'sale' => route('admin.inventory.index'),
        ] as $key => $href) {
            self::assertMatchesRegularExpression('/<a data-dashboard-d1-kpi="'.$key.'" class="a1-kpi a1-kpi--link" href="'.preg_quote($href, '/').'">/', $dash);
        }

        // Quick actions, module cards and the rate card the layout script drives.
        foreach ([route('catalog.index'), route('admin.products.create'), route('admin.products.bulk'), route('admin.receivables.index'),
            route('admin.after-sales.index'), route('admin.reports.index'), route('admin.users.index')] as $href) {
            self::assertStringContainsString('href="'.$href.'"', $dash);
        }
        foreach (['Fokus danas', 'Prioritetne aktivnosti', 'Trend prodaje', 'Najprodavaniji brendovi', 'Poslednje porudžbine', 'Brze akcije', 'Porudžbine korisnika', 'Svi dostupni moduli na jednom mestu'] as $heading) {
            self::assertStringContainsString($heading, $dash);
        }
        // Header band and "Brze akcije" never show the same action twice.
        self::assertSame(1, substr_count($dash, 'href="'.route('admin.products.create').'"'));
        // The band now offers "Dodaj artikal"; direct sale stays in the catalog (product page).
        self::assertSame(1, substr_count($dash, 'Dodaj artikal'));
        self::assertSame(0, substr_count($dash, 'Direktna prodaja'));
        // The rate left the greeting card: it lives in the header chip (desktop) and the drawer card (phones).
        self::assertStringNotContainsString('a1-ratecard', $dash);
        self::assertStringContainsString('data-exchange-rate-value data-exchange-rate-decimals="2"', $html);
        // Quick actions: only targets this user may open; no Moj nalog and no Direktna prodaja in the row.
        self::assertStringContainsString('Evidentiraj uplatu', $dash);
        self::assertStringContainsString('Provizije', $dash);
        self::assertStringContainsString('<details class="a1-acc a1-modules">', $dash);
        self::assertStringNotContainsString('<details class="a1-acc a1-modules" open', $dash);
    }

    public function test_recent_order_row_shows_status_amounts_and_links_to_the_order(): void
    {
        $admin = $this->person('superadmin');
        $order = $this->order($admin, 'M2-ROW-001');
        $this->item($order, 'Row laptop', 1, 15000.0);

        $dash = $this->dashboard((string) $this->actingAs($admin)->get('/')->assertOk()->getContent());

        self::assertStringContainsString('href="'.route('admin.orders.show', $order).'" class="a1-item a1-item--order"', $dash);
        self::assertMatchesRegularExpression('/a1-pill a1-pill--warning">Nova</', $dash);
        self::assertStringContainsString('15.000,00 RSD', $dash);
        self::assertStringContainsString('Preostalo 15.000,00', $dash);
    }

    public function test_administrator_dashboard_has_no_superadmin_blocks_and_follows_its_permissions(): void
    {
        $response = $this->actingAs($this->person('admin'))->get('/')->assertOk();
        $access = $response->viewData('access');
        $dash = $this->dashboard((string) $response->getContent());

        // Not a superadmin: no period selector, valuation, brand mix, operator order centre or six-KPI grid.
        foreach (['data-dashboard-period', 'data-dashboard-d1-kpi=', 'data-dashboard-d3', 'data-operator-order-center', 'a1-seg-form', 'Vrednost po nabavnoj ceni', 'Direktna prodaja'] as $absent) {
            self::assertStringNotContainsString($absent, $dash);
        }
        self::assertStringContainsString('class="a1-kpis a1-kpis--4"', $dash);
        self::assertSame(4, substr_count($dash, 'class="a1-kpi a1-kpi--link"'));

        // Module cards appear exactly when the permission flag is set.
        foreach ([
            route('admin.users.index') => (bool) $access['manage_users'],
            route('admin.receivables.index') => (bool) $access['receivables_manage'],
            route('admin.field-operations.index') => (bool) $access['field_operations_view'],
            route('admin.service-parts.index') => (bool) $access['service_parts_view'],
            route('catalog.index') => (bool) ($access['catalog_view'] || $access['catalog_manage_products']),
        ] as $href => $allowed) {
            self::assertSame($allowed, str_contains($dash, 'class="a1-item" href="'.$href.'"'), $href);
        }
        // The priorities card is part of the business dashboard, not of the customer one.
        self::assertSame($this->hasBusinessAccess($access), str_contains($dash, 'id="d1-priority-heading"'));
        // The customer centre follows the own-orders permission (portal flag), never the business access.
        self::assertSame((bool) ($response->viewData('portal')['enabled'] ?? false), str_contains($dash, 'data-customer-center-ready="1"'));
    }

    public function test_user_role_gets_the_customer_dashboard(): void
    {
        $dash = $this->dashboard((string) $this->actingAs($this->person('user'))->get('/')->assertOk()->getContent());

        self::assertStringContainsString('data-customer-center-ready="1"', $dash);
        self::assertStringContainsString('class="a1-kpis a1-kpis--4"', $dash);
        self::assertStringContainsString('Moje porudžbine', $dash);
        self::assertStringContainsString('Još nema porudžbina', $dash);
        self::assertStringContainsString('class="a1-empty"', $dash);
        foreach (['data-dashboard-period', 'data-dashboard-d1-kpi=', 'data-dashboard-d3', 'data-operator-order-center', 'a1-trend', 'id="d1-priority-heading"'] as $absent) {
            self::assertStringNotContainsString($absent, $dash);
        }
        self::assertStringContainsString('href="'.route('portal.messages.index').'"', $dash);
        self::assertStringContainsString('href="'.route('account.show').'"', $dash);
    }

    public function test_dashboard_script_keeps_its_csp_nonce(): void
    {
        $response = $this->actingAs($this->person('superadmin'))->get('/')->assertOk();
        $html = (string) $response->getContent();
        self::assertMatchesRegularExpression('/<script nonce="[^"]+">\s*document\.addEventListener\(.DOMContentLoaded.,[^<]*data-dashboard-d3/s', $html);
        self::assertSame(0, preg_match('/\sonclick=|\sonchange=/i', $this->dashboard($html)), 'script-src-attr is none: no inline handlers.');
    }

    /** @param array<string,bool> $access */
    private function hasBusinessAccess(array $access): bool
    {
        foreach (['orders_manage', 'catalog_manage_products', 'stock_view', 'manage_users', 'manage_settings', 'after_sales_manage', 'field_operations_view', 'service_parts_view', 'warranties_manage', 'receivables_manage', 'reports_view'] as $key) {
            if ($access[$key] ?? false) {
                return true;
            }
        }

        return false;
    }

    /** The dashboard root only, without the application shell. */
    private function dashboard(string $html): string
    {
        $start = strpos($html, 'class="a1-dash"');
        self::assertNotFalse($start, 'dashboard root missing');
        $end = strpos($html, '</main>', $start);

        return substr($html, $start, ($end === false ? strlen($html) : $end) - $start);
    }

    private function person(string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => 'm2-'.$role, 'email' => 'm2-'.$role.'@example.test',
            'password_hash' => Hash::make('TestOnlyM2!'), 'status' => 'active',
        ]);
    }

    private function order(User $actor, string $number): Order
    {
        return Order::query()->create([
            'source_system' => 'laravel', 'order_number' => $number, 'user_id' => $actor->id, 'supplier_user_id' => $actor->id,
            'status' => 'new', 'shipping_full_name' => 'Synthetic Customer', 'shipping_address' => 'Test street 1',
            'shipping_city' => 'Test', 'shipping_postal_code' => '11000', 'shipping_phone' => '0600000000',
            'subtotal_rsd' => 15000, 'paid_total_rsd' => 0,
        ]);
    }

    private function item(Order $order, string $name, int $quantity, float $unit): OrderItem
    {
        $id = Product::query()->count() + 1;
        $product = Product::query()->create([
            'sku' => 'M2-'.$id, 'slug' => 'm2-'.$id, 'name' => 'Synthetic laptop '.$id,
            'price_amount' => 149, 'price_currency' => 'EUR', 'stock_quantity' => 4,
            'description' => 'Synthetic product description.', 'status' => 'active', 'created_by' => $order->user_id,
        ]);

        return OrderItem::query()->create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_sku' => $product->sku,
            'product_name' => $name, 'quantity' => $quantity, 'unit_price_original' => $unit,
            'original_currency' => 'RSD', 'unit_price_rsd' => $unit, 'line_total_rsd' => $unit * $quantity,
        ]);
    }

    private function completedOrder(User $actor, string $number, string $completedAt, string $brand, float $revenue, float $purchase): void
    {
        $order = $this->order($actor, $number);
        $this->item($order, 'Completed laptop', 2, $revenue / 2);
        // The orders.status enum has no "completed"; reports treat completed_at (and not cancelled) as completed.
        DB::table('orders')->where('id', $order->id)->update([
            'status' => 'shipped', 'completed_at' => $completedAt, 'subtotal_rsd' => $revenue, 'paid_total_rsd' => $revenue,
        ]);
        DB::table('order_items')->where('order_id', $order->id)->update([
            'brand_name_snapshot' => $brand, 'purchase_total_rsd_snapshot' => $purchase,
        ]);
    }
}
