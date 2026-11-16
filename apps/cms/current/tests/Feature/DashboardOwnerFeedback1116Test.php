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
 * Owner feedback 2026-11-16 on the dashboard: delivered chip, SuperAdmin earnings cards (numbers on a small fixture),
 * who gets the cards, the "Dodaj artikal" action and the trend columns. Author: Ald1n <https://ald1n.com>
 */
final class DashboardOwnerFeedback1116Test extends TestCase
{
    use RefreshDatabase;

    private const JANUARY = '/?period=custom&date_from=2026-01-01&date_to=2026-01-31';

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_order_with_completed_at_shows_the_green_delivered_chip_and_the_stored_status_is_unchanged(): void
    {
        $admin = $this->person('superadmin');
        $delivered = $this->order($admin, 'OF-DELIVERED-1', 10000.0, 6000.0, 0.0, '2026-01-10 10:00:00');
        $shipped = $this->order($admin, 'OF-SHIPPED-1', 2000.0, 1000.0, 0.0, null);
        $cancelled = $this->order($admin, 'OF-CANCELLED-1', 3000.0, 1000.0, 0.0, '2026-01-11 10:00:00', 'cancelled');

        $dash = $this->dashboard((string) $this->actingAs($admin)->get('/')->assertOk()->getContent());

        self::assertMatchesRegularExpression('/OF-DELIVERED-1 · [^<]*<\/span><\/span><span class="a1-item__status"><span class="a1-pill a1-pill--ok">Isporučena</', $dash);
        self::assertMatchesRegularExpression('/OF-SHIPPED-1 · [^<]*<\/span><\/span><span class="a1-item__status"><span class="a1-pill a1-pill--warning">Poslata</', $dash);
        self::assertMatchesRegularExpression('/OF-CANCELLED-1 · [^<]*<\/span><\/span><span class="a1-item__status"><span class="a1-pill a1-pill--danger">Otkazana</', $dash);
        // Display only: the stored status values are what they were.
        self::assertSame('shipped', DB::table('orders')->where('id', $delivered->id)->value('status'));
        self::assertSame('shipped', DB::table('orders')->where('id', $shipped->id)->value('status'));
        self::assertSame('cancelled', DB::table('orders')->where('id', $cancelled->id)->value('status'));
        // The compact list modifier replaces the full-width stretch.
        self::assertStringContainsString('class="a1-list a1-list--recent"', $dash);
    }

    public function test_superadmin_earnings_cards_show_the_period_numbers(): void
    {
        $admin = $this->person('superadmin');
        $this->earningsFixture($admin);

        $response = $this->actingAs($admin)->get(self::JANUARY)->assertOk();
        $dash = $this->dashboard((string) $response->getContent());

        // promet 10.000 + 5.000 + 3.000; provizija 20 EUR x 117 + 10 EUR x 117 (the 5 EUR commission is cancelled, the cancelled order is out);
        // bruto = known revenue (10.000 + 3.000) - purchase (6.000 + 1.000), the 5.000 item has no purchase price; neto = 6.000 - 3.510.
        self::assertEqualsWithDelta(18000.0, $response->viewData('reportStats')['earnings']['revenue_rsd'], 0.001);
        self::assertEqualsWithDelta(3510.0, $response->viewData('reportStats')['earnings']['commissions_rsd'], 0.001);
        self::assertEqualsWithDelta(6000.0, $response->viewData('reportStats')['earnings']['gross_rsd'], 0.001);
        self::assertEqualsWithDelta(2490.0, $response->viewData('reportStats')['earnings']['net_rsd'], 0.001);

        self::assertSame(1, preg_match('/<section class="a1-kpis a1-kpis--4" data-dashboard-earnings.*?<\/section>/s', $dash, $section));
        foreach ([
            'revenue' => ['Ukupan promet', '18.000,00', '153,85'],
            'commission' => ['Provizija', '3.510,00', '30,00'],
            'gross' => ['Bruto zarada', '6.000,00', '51,28'],
            'net' => ['Neto zarada', '2.490,00', '21,28'],
        ] as $key => [$label, $value, $eur]) {
            self::assertMatchesRegularExpression('/data-dashboard-earnings-kpi="'.$key.'" href="[^"]+">\s*<span class="a1-kpi__label">'.preg_quote($label, '/').'<\/span>\s*<span class="a1-kpi__row">\s*<span class="a1-kpi__value a1-num">'.preg_quote($value, '/').' /', $section[0], $key);
            // EUR line: items at the rate stored on their order (117), commissions as stored in EUR (the cancelled one is out).
            self::assertStringContainsString('data-earnings-eur="'.$key.'">'.$eur.' EUR<', $section[0], $key);
            // No previous period in this fixture: the comparison says so in words.
            self::assertMatchesRegularExpression('/data-earnings-delta="'.$key.'">\s*<span>Nema podataka za prethodni period<\/span>/', $section[0], $key);
        }
        self::assertStringContainsString('3 porudžbina · prosek 6.000,00 RSD (51,28 EUR)', $section[0]);
        // Every card links to a report with the same period: management report, and sales by subagent for the commission.
        $management = e(route('admin.reports.index', ['date_from' => '2026-01-01', 'date_to' => '2026-01-31', 'scope' => 'completed']));
        $subagents = e(route('admin.reports.subagents', ['period' => 'custom', 'date_from' => '2026-01-01', 'date_to' => '2026-01-31']));
        self::assertStringContainsString('data-dashboard-earnings-kpi="revenue" href="'.$management.'"', $section[0]);
        self::assertStringContainsString('data-dashboard-earnings-kpi="commission" href="'.$subagents.'"', $section[0]);
        self::assertStringContainsString('<svg class="a1-kpi__spark" viewBox="0 0 96 28" aria-hidden="true"', $section[0]);
        // The period KPI tile carries the same EUR figure.
        self::assertMatchesRegularExpression('/data-dashboard-d1-kpi="revenue".{0,700}153,85 EUR/s', $dash);
        self::assertStringContainsString('Neto zarada: bruto zarada minus provizija.', $dash);
        // Outside the period nothing of the February order shows up (9.000 would change promet to 27.000).
        self::assertStringNotContainsString('27.000,00', $section[0]);
    }

    public function test_earnings_comparison_with_the_previous_period_of_the_same_length(): void
    {
        $admin = $this->person('superadmin');
        $this->earningsFixture($admin);
        // Previous period of 31 days = December 2025: revenue 6.000, purchase 3.000, no commission.
        $this->order($admin, 'EF-PREV-1', 6000.0, 3000.0, 0.0, '2025-12-10 10:00:00');

        $response = $this->actingAs($admin)->get(self::JANUARY)->assertOk();
        $dash = $this->dashboard((string) $response->getContent());
        preg_match('/<section class="a1-kpis a1-kpis--4" data-dashboard-earnings.*?<\/section>/s', $dash, $section);

        $deltaOf = static function (string $key) use ($section): string {
            self::assertSame(1, preg_match('/data-earnings-delta="'.$key.'">(.*?)<span class="a1-kpi__note">/s', $section[0], $m), $key);

            return trim((string) preg_replace('/\s+/', ' ', strip_tags($m[1])));
        };
        // 18.000 vs 6.000 = +200 %, gross 6.000 vs 3.000 = +100 %, net 2.490 vs 3.000 = -17 %, commission 3.510 vs 0 = no percent.
        self::assertStringContainsString('+200,0%', $deltaOf('revenue'));
        self::assertStringContainsString('više nego u prethodnom periodu', $deltaOf('revenue'));
        self::assertStringContainsString('+100,0%', $deltaOf('gross'));
        self::assertStringContainsString('-17,0%', $deltaOf('net'));
        self::assertStringContainsString('manje nego u prethodnom periodu', $deltaOf('net'));
        self::assertStringContainsString('Nema podataka za prethodni period', $deltaOf('commission'));
        self::assertStringNotContainsString('%', $deltaOf('commission'));
        self::assertSame('2025-12-01', $response->viewData('reportStats')['earnings']['previous_period']['date_from']);
        self::assertSame('2025-12-31', $response->viewData('reportStats')['earnings']['previous_period']['date_to']);
    }

    public function test_recent_order_shows_eur_from_the_rate_stored_on_the_order(): void
    {
        $admin = $this->person('superadmin');
        $this->order($admin, 'OF-EUR-1', 10000.0, 6000.0, 0.0, '2026-01-10 10:00:00');

        $dash = $this->dashboard((string) $this->actingAs($admin)->get('/')->assertOk()->getContent());

        // 10.000 RSD at the stored rate 117 = 85,47 EUR; today's rate does not enter.
        self::assertStringContainsString('<span class="a1-item__money">10.000,00 RSD</span><span class="a1-item__eur">85,47 EUR</span>', $dash);
    }

    public function test_subagent_sales_totals_show_eur_with_the_stored_rates(): void
    {
        $admin = $this->person('superadmin');
        $this->earningsFixture($admin);

        $html = (string) $this->actingAs($admin)
            ->get('/admin/reports/subagents?period=custom&date_from=2026-01-01&date_to=2026-01-31')->assertOk()->getContent();

        // Promet 18.000 RSD / 117 = 153,85 EUR; bruto 6.000 RSD / 117 = 51,28 EUR; provizija as stored in EUR (20 + 10 + 5);
        // neto EUR = 51,28 - 35,00 = 16,28; prosek 153,85 / 3 = 51,28.
        self::assertStringContainsString('data-sbs-eur="revenue">153,85 EUR<', $html);
        self::assertStringContainsString('data-sbs-eur="gross">51,28 EUR<', $html);
        self::assertStringContainsString('data-sbs-eur="commission">35,00 EUR<', $html);
        self::assertStringContainsString('data-sbs-eur="net">16,28 EUR<', $html);
        self::assertStringContainsString('data-sbs-eur="avg">51,28 EUR<', $html);
    }

    public function test_trend_table_carries_every_series_per_period(): void
    {
        $admin = $this->person('superadmin');
        $this->earningsFixture($admin);

        $dash = $this->dashboard((string) $this->actingAs($admin)->get(self::JANUARY)->assertOk()->getContent());

        foreach (['Prihod (RSD)', 'Bruto dobit (RSD)', 'Provizija (RSD)', 'Broj porudžbina'] as $column) {
            self::assertStringContainsString('>'.$column.'<', $dash);
        }
        // Day: revenue | gross | commission | orders.
        self::assertStringContainsString('<th scope="row">10.01.2026.</th><td class="a1-table__num">10.000,00</td><td class="a1-table__num">4.000,00</td><td class="a1-table__num">2.340,00</td><td class="a1-table__num">1</td>', $dash);
        self::assertStringContainsString('<th scope="row">12.01.2026.</th><td class="a1-table__num">5.000,00</td><td class="a1-table__num">0,00</td><td class="a1-table__num">1.170,00</td><td class="a1-table__num">1</td>', $dash);
        self::assertStringContainsString('<th scope="row">15.01.2026.</th><td class="a1-table__num">3.000,00</td><td class="a1-table__num">2.000,00</td><td class="a1-table__num">0,00</td><td class="a1-table__num">1</td>', $dash);
        // Summary per series: total, average per calendar day (31 days), best day.
        self::assertStringContainsString('Ukupno 18.000,00 RSD · Prosek po danu 580,65 RSD · Najbolji dan 10.01.2026. (10.000,00 RSD)', $dash);
        self::assertStringContainsString('Ukupno 3.510,00 RSD · Prosek po danu 113,23 RSD · Najbolji dan 10.01.2026. (2.340,00 RSD)', $dash);
        self::assertStringContainsString('Ukupno 3 · Prosek po danu 0,10 · Najbolji dan 10.01.2026. (1)', $dash);
        // The default selection is Prihod and Bruto dobit; the other two series are drawn only after the toggle.
        self::assertStringContainsString('data-trend-off="comm orders"', $dash);
        self::assertMatchesRegularExpression('/data-trend-series="rev" aria-pressed="true"/', $dash);
        self::assertMatchesRegularExpression('/data-trend-series="comm" aria-pressed="false"/', $dash);
    }

    public function test_administrator_and_subagent_get_neither_the_earnings_cards_nor_the_numbers(): void
    {
        foreach (['admin', 'user'] as $role) {
            $user = $this->person($role);
            // The fixture belongs to this user, so an administrator really has revenue and commission in scope.
            $this->earningsFixture($user);

            $response = $this->actingAs($user)->get(self::JANUARY)->assertOk();
            $html = (string) $response->getContent();

            self::assertNull($response->viewData('reportStats')['earnings'] ?? null, $role);
            foreach (['data-dashboard-earnings', 'Neto zarada', 'Bruto zarada', 'Ukupan promet', 'Provizija (RSD)', 'data-trend-series="comm"', 'data-trend-summary="comm"', '3.510,00', '2.340,00', '1.170,00'] as $absent) {
                self::assertStringNotContainsString($absent, $html, $role.' must not receive '.$absent);
            }
        }

        // Non-vacuous: the administrator really has report data in scope (the fixture revenue), only the earnings block is withheld.
        $admin = $this->person('admin', 'second');
        $this->earningsFixture($admin);
        $response = $this->actingAs($admin)->get(self::JANUARY)->assertOk();
        self::assertTrue($response->viewData('reportStats')['ready']);
        self::assertEqualsWithDelta(18000.0, $response->viewData('reportStats')['summary']['revenue_rsd'], 0.001);
    }

    public function test_dodaj_artikal_in_the_header_band_follows_the_product_permission(): void
    {
        $super = $this->person('superadmin');
        $subagent = $this->person('user');
        self::assertTrue($super->can('catalog.manage_products'));
        self::assertFalse($subagent->can('catalog.manage_products'));

        $superDash = $this->dashboard((string) $this->actingAs($super)->get('/')->assertOk()->getContent());
        self::assertSame(1, substr_count($superDash, 'Dodaj artikal'));
        self::assertStringContainsString('<a class="a1-btn a1-btn--primary a1-btn--sm" href="'.route('admin.products.create').'"', $superDash);
        self::assertStringNotContainsString('Direktna prodaja', $superDash);

        $subDash = $this->dashboard((string) $this->actingAs($subagent)->get('/')->assertOk()->getContent());
        self::assertStringNotContainsString('Dodaj artikal', $subDash);
        self::assertStringNotContainsString('href="'.route('admin.products.create').'"', $subDash);

        // Administrator: whatever the permission says, the button follows it exactly.
        $admin = $this->person('admin');
        $adminDash = $this->dashboard((string) $this->actingAs($admin)->get('/')->assertOk()->getContent());
        self::assertSame($admin->can('catalog.manage_products'), str_contains($adminDash, 'href="'.route('admin.products.create').'"'));
    }

    public function test_brand_mix_renders_top_5_and_top_10_variants(): void
    {
        $admin = $this->person('superadmin');
        foreach (range(1, 12) as $i) {
            $order = $this->order($admin, 'OF-BRAND-'.$i, 1000.0 * $i, 100.0, 0.0, '2026-01-10 10:00:00');
            DB::table('order_items')->where('order_id', $order->id)->update(['brand_name_snapshot' => 'Marka'.$i]);
        }

        $dash = $this->dashboard((string) $this->actingAs($admin)->get(self::JANUARY)->assertOk()->getContent());

        self::assertMatchesRegularExpression('/data-d3-view="revenue"\s+data-d3-top="5"/', $dash);
        self::assertMatchesRegularExpression('/data-d3-view="revenue" hidden\s+data-d3-top="10"/', $dash);
        self::assertMatchesRegularExpression('/data-d3-view="units"\s+hidden\s+data-d3-top="5"/', $dash);
        self::assertStringContainsString('data-d3-topn="5" aria-pressed="true"', $dash);
        self::assertStringContainsString('data-d3-topn="10" aria-pressed="false"', $dash);
        // Top 10 list: ten brands plus "Ostali" in the revenue view; Marka12 is first (12.000), Marka3 is the tenth.
        preg_match('/data-d3-view="revenue" hidden\s+data-d3-top="10">(.*?)<\/ol>/s', $dash, $top10);
        self::assertSame(11, substr_count($top10[1], 'a1-mix__label'));
        self::assertStringContainsString('Marka3<', $top10[1]);
        self::assertStringNotContainsString('Marka2<', $top10[1]);
    }

    /**
     * Four orders in January 2026 and one in February, all with revenue, purchase snapshot, EUR commission snapshot and rate 117:
     * A 10.000 / purchase 6.000 / 20 EUR, B 5.000 / NO purchase price / 10 EUR, C 3.000 / 1.000 / 5 EUR with a CANCELLED commission record,
     * D cancelled order 7.000, E (February) 9.000.
     */
    private function earningsFixture(User $actor): void
    {
        $this->order($actor, 'EF-A-'.$actor->id, 10000.0, 6000.0, 20.0, '2026-01-10 10:00:00');
        $this->order($actor, 'EF-B-'.$actor->id, 5000.0, null, 10.0, '2026-01-12 10:00:00');
        $c = $this->order($actor, 'EF-C-'.$actor->id, 3000.0, 1000.0, 5.0, '2026-01-15 10:00:00');
        DB::table('order_commissions')->insert([
            'order_id' => $c->id, 'user_id' => $actor->id, 'total_eur' => 5, 'status' => 'cancelled',
            'created_at' => '2026-01-15 11:00:00', 'updated_at' => '2026-01-15 11:00:00',
        ]);
        $this->order($actor, 'EF-D-'.$actor->id, 7000.0, 1000.0, 99.0, '2026-01-16 10:00:00', 'cancelled');
        $this->order($actor, 'EF-E-'.$actor->id, 9000.0, 1000.0, 1.0, '2026-02-03 10:00:00');
    }

    private function order(User $actor, string $number, float $revenue, ?float $purchase, float $commissionEur, ?string $completedAt, string $status = 'shipped'): Order
    {
        $order = Order::query()->create([
            'source_system' => 'laravel', 'order_number' => $number, 'user_id' => $actor->id, 'supplier_user_id' => $actor->id,
            'status' => 'new', 'shipping_full_name' => 'Synthetic Customer', 'shipping_address' => 'Test street 1',
            'shipping_city' => 'Test', 'shipping_postal_code' => '11000', 'shipping_phone' => '0600000000',
            'subtotal_rsd' => $revenue, 'paid_total_rsd' => 0,
        ]);
        $id = ++$this->sequence;
        $product = Product::query()->create([
            'sku' => 'OF-'.$id.'-'.$actor->id, 'slug' => 'of-'.$id.'-'.$actor->id, 'name' => 'Synthetic laptop '.$id,
            'price_amount' => 149, 'price_currency' => 'EUR', 'stock_quantity' => 4,
            'description' => 'Synthetic product description.', 'status' => 'active', 'created_by' => $actor->id,
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_sku' => $product->sku,
            'product_name' => 'Laptop '.$number, 'quantity' => 1, 'unit_price_original' => $revenue,
            'original_currency' => 'RSD', 'unit_price_rsd' => $revenue, 'line_total_rsd' => $revenue,
        ]);
        // The orders.status enum has no "completed": delivered means completed_at is set (and the order is not cancelled).
        DB::table('orders')->where('id', $order->id)->update(['status' => $status, 'completed_at' => $completedAt, 'eur_rsd_rate' => 117]);
        DB::table('order_items')->where('order_id', $order->id)->update([
            'brand_name_snapshot' => 'Lenovo', 'purchase_total_rsd_snapshot' => $purchase, 'commission_total_eur_snapshot' => $commissionEur,
        ]);

        return $order;
    }

    /** The dashboard root only, without the application shell. */
    private function dashboard(string $html): string
    {
        $start = strpos($html, 'class="a1-dash"');
        self::assertNotFalse($start, 'dashboard root missing');
        $end = strpos($html, '</main>', $start);

        return substr($html, $start, ($end === false ? strlen($html) : $end) - $start);
    }

    private function person(string $role, string $suffix = ''): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => 'of-'.$role.$suffix, 'email' => 'of-'.$role.$suffix.'@example.test',
            'password_hash' => Hash::make('TestOnlyOF1116!'), 'status' => 'active',
        ]);
    }
}
