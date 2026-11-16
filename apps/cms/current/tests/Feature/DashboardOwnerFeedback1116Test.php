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
            self::assertMatchesRegularExpression('/data-dashboard-earnings-kpi="'.$key.'" href="[^"]+">\s*<span class="a1-kpi__label">'.preg_quote($label, '/').'<\/span>\s*<span class="a1-kpi__value a1-num">'.preg_quote($value, '/').' /', $section[0], $key);
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
        // The old Prihod / Bruto dobit / Neto doprinos tiles are gone; the stock tiles stay.
        foreach (['revenue', 'gross', 'net'] as $old) {
            self::assertStringNotContainsString('data-dashboard-d1-kpi="'.$old.'"', $dash);
        }
        self::assertSame(3, substr_count($dash, 'data-dashboard-d1-kpi='));
        // Fixed sparkline slot: every series here has three different daily values, so all four cards draw one line.
        self::assertSame(4, substr_count($section[0], 'class="a1-kpi__spark"'));
        self::assertSame(0, substr_count($section[0], 'a1-kpi__row'));
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

    public function test_sparkline_is_hidden_for_a_single_day_and_for_a_flat_series(): void
    {
        $admin = $this->person('superadmin');
        // One day, one order, no commission: fewer than two points, so no card shows a line.
        $this->order($admin, 'SP-ONE', 4000.0, 1000.0, 0.0, '2026-01-10 10:00:00');
        $one = $this->dashboard((string) $this->actingAs($admin)->get(self::JANUARY)->assertOk()->getContent());
        self::assertSame(0, substr_count($one, 'class="a1-kpi__spark"'));

        // Two days with different revenue but zero commission: revenue gets a line, the flat commission series does not.
        $this->order($admin, 'SP-TWO', 9000.0, 1000.0, 0.0, '2026-01-12 10:00:00');
        $two = $this->dashboard((string) $this->actingAs($admin)->get(self::JANUARY)->assertOk()->getContent());
        preg_match('/data-dashboard-earnings-kpi="commission".*?<\/a>/s', $two, $commission);
        preg_match('/data-dashboard-earnings-kpi="revenue".*?<\/a>/s', $two, $revenue);
        self::assertStringNotContainsString('a1-kpi__spark', $commission[0]);
        self::assertStringContainsString('a1-kpi__spark', $revenue[0]);
    }

    public function test_overdue_debt_counter_is_a_whole_number_of_cases_and_the_amount_stays_in_the_subtitle(): void
    {
        $admin = $this->person('superadmin');
        foreach (['DEBT-1' => 1000.5, 'DEBT-2' => 2000.0] as $number => $amount) {
            $order = $this->order($admin, $number, $amount, 1.0, 0.0, null, 'shipped');
            DB::table('orders')->where('id', $order->id)->update(['payment_due_at' => now()->subDays(3), 'paid_total_rsd' => 0]);
            DB::table('receivable_cases')->insert(['order_id' => $order->id, 'case_number' => 'RC-'.$number, 'status' => 'monitoring', 'created_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        }

        $response = $this->actingAs($admin)->get('/')->assertOk();
        $item = collect($response->viewData('priorityActions'))->firstWhere('title', 'Dospelo dugovanje');
        self::assertSame(2, $item['count']);
        self::assertStringContainsString('3.000,50 RSD', $item['description']);
        self::assertMatchesRegularExpression('/a1-item__title">Dospelo dugovanje<\/span>.*?a1-item__count">2</s', (string) $response->getContent());
        self::assertDoesNotMatchRegularExpression('/a1-item__count">[^<]*[,.]/', $this->dashboard((string) $response->getContent()));
    }

    public function test_period_bar_is_one_row_with_custom_fields_behind_prilagodeno(): void
    {
        $admin = $this->person('superadmin');
        $default = $this->dashboard((string) $this->actingAs($admin)->get('/')->assertOk()->getContent());
        foreach (['Ovaj mesec', '7 dana', '30 dana', 'Prilagođeno'] as $label) {
            self::assertStringContainsString('>'.$label.'<', $default);
        }
        self::assertStringNotContainsString('>Default<', $default);
        self::assertMatchesRegularExpression('/<details class="a1-periodbar__custom"\s*>/', $default);
        self::assertSame(1, substr_count($default, 'Primeni period'));

        $custom = $this->dashboard((string) $this->actingAs($admin)->get(self::JANUARY)->assertOk()->getContent());
        self::assertMatchesRegularExpression('/<details class="a1-periodbar__custom"\s+open\s*>/', $custom);
        self::assertStringContainsString('name="date_from" required value="2026-01-01"', $custom);
    }

    public function test_orders_list_shows_user_and_buyer_names_and_the_removed_blocks_are_gone(): void
    {
        $admin = $this->person('superadmin');
        $seller = $this->person('user', 'seller');
        $this->order($seller, 'NM-1', 5000.0, 1000.0, 0.0, null, 'new');
        DB::table('orders')->where('order_number', 'NM-1')->update(['supplier_user_id' => $admin->id, 'shipping_full_name' => 'Petar Petrović']);

        $dash = $this->dashboard((string) $this->actingAs($admin)->get('/')->assertOk()->getContent());

        self::assertMatchesRegularExpression('/NM-1 · [^<]*· Korisnik: [^<]*· Kupac: Petar Petrović</', $dash);
        self::assertSame(1, substr_count($dash, 'Poslednje porudžbine'));
        foreach (['Porudžbine korisnika', 'data-operator-order-center', 'Brze akcije', 'Svi dostupni moduli na jednom mestu', 'a1-modules'] as $gone) {
            self::assertStringNotContainsString($gone, $dash);
        }
    }

    public function test_trend_rows_carry_three_different_day_labels_matching_the_axis(): void
    {
        $admin = $this->person('superadmin');
        $this->order($admin, 'TL-1', 1000.0, 100.0, 0.0, '2026-01-05 10:00:00');
        $this->order($admin, 'TL-2', 2000.0, 100.0, 0.0, '2026-01-17 10:00:00');
        $this->order($admin, 'TL-3', 3000.0, 100.0, 0.0, '2026-01-29 10:00:00');

        $dash = $this->dashboard((string) $this->actingAs($admin)->get(self::JANUARY)->assertOk()->getContent());

        preg_match_all('/<th scope="row">([^<]*)<\/th><td class="a1-table__num">/', $dash, $rows);
        preg_match_all('/class="a1-trend__label">([^<]*)</', $dash, $axis);
        self::assertSame(['05.01.2026.', '17.01.2026.', '29.01.2026.'], $rows[1]);
        self::assertSame(['01-05', '01-17', '01-29'], $axis[1]);
        self::assertCount(3, array_unique($rows[1]));
    }

    public function test_trend_table_carries_every_series_per_period(): void
    {
        $admin = $this->person('superadmin');
        $this->earningsFixture($admin);

        $dash = $this->dashboard((string) $this->actingAs($admin)->get(self::JANUARY)->assertOk()->getContent());

        foreach (['Prihod', 'Bruto', 'Provizija', 'Porudžbine'] as $column) {
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

    public function test_subagent_dashboard_shows_own_commission_by_status_and_own_orders_only(): void
    {
        $sub = $this->person('user');
        $other = $this->person('user', 'other');
        // Own orders (rate 117): commission pending 10 EUR, approved 20 EUR, paid 30 EUR, cancelled 99 EUR (ignored); one cancelled order is not counted.
        foreach ([['A', 10000.0, 'pending', 10], ['B', 20000.0, 'approved', 20], ['C', 30000.0, 'paid', 30], ['D', 40000.0, 'cancelled', 99]] as [$n, $value, $status, $eur]) {
            $order = $this->order($sub, 'SA-'.$n, $value, 5555.0, (float) $eur, null, 'new');
            DB::table('order_commissions')->insert(['order_id' => $order->id, 'user_id' => $sub->id, 'total_eur' => $eur, 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->order($sub, 'SA-X', 50000.0, 1.0, 5.0, null, 'cancelled');
        // Somebody else's order and commission: nothing of it may show.
        $foreign = $this->order($other, 'SA-OTHER', 12345.67, 777.0, 777.0, null, 'new');
        DB::table('order_commissions')->insert(['order_id' => $foreign->id, 'user_id' => $other->id, 'total_eur' => 777, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->actingAs($sub)->get('/')->assertOk();
        $html = (string) $response->getContent();
        $dash = $this->dashboard($html);

        foreach (['pending' => ['1.170,00', '10,00'], 'approved' => ['2.340,00', '20,00'], 'paid' => ['3.510,00', '30,00']] as $status => [$rsd, $eur]) {
            self::assertMatchesRegularExpression('/data-subagent-commission="'.$status.'">\s*<span class="a1-kpi__label">[^<]+<\/span>\s*<span class="a1-kpi__value a1-num">'.preg_quote($rsd, '/').' <span class="a1-kpi__unit">RSD<\/span><\/span>\s*<span class="a1-kpi__eur">'.preg_quote($eur, '/').' EUR</', $dash, $status);
        }
        self::assertStringContainsString('>Za isplatu<', $dash);
        // Own orders in the period: A + B + C + D = 100.000 RSD (854,70 EUR at the stored rate), the cancelled order is out.
        self::assertStringContainsString('4 porudžbina u periodu', $dash);
        self::assertStringContainsString('data-subagent-orders-value>100.000,00 RSD</b> · 854,70 EUR', $dash);
        self::assertStringContainsString('SA-A', $dash);
        self::assertStringContainsString('a1-pill a1-pill--warning">Nova<', $dash);
        self::assertStringContainsString('href="'.route('orders.create').'"', $dash);
        // None of: purchase prices, company profit, other users' data.
        foreach (['12.345,67', '777,00', '5.555,00', 'Bruto', 'nabavn', 'data-dashboard-earnings', 'data-trend-series'] as $absent) {
            self::assertStringNotContainsString($absent, $dash, 'SubAgent must not receive '.$absent);
        }
        self::assertNull($response->viewData('reportStats')['earnings'] ?? null);
    }

    public function test_administrator_dashboard_has_revenue_and_tasks_but_no_commission_gross_or_net(): void
    {
        $admin = $this->person('admin');
        $other = $this->person('admin', 'other');
        $now = now()->format('Y-m-d H:i:s');
        $this->order($admin, 'AD-OWN-1', 10000.0, 6000.0, 20.0, $now);
        $this->order($admin, 'AD-OWN-NEW', 4000.0, 1000.0, 0.0, null, 'new');
        $this->order($other, 'AD-FOREIGN', 7777.0, 1000.0, 33.0, $now);

        $response = $this->actingAs($admin)->get('/')->assertOk();
        $html = (string) $response->getContent();
        $dash = $this->dashboard($html);

        self::assertStringContainsString('Prihod ovog meseca', $dash);
        self::assertStringContainsString('10.000,00', $dash);
        self::assertStringContainsString('85,47 EUR', $dash);
        self::assertStringNotContainsString('7.777,00', $html);
        foreach (['data-dashboard-earnings', 'Bruto zarada', 'Neto zarada', 'Bruto dobit', 'Neto doprinos', 'Provizija (RSD)', 'Ukupan promet', 'data-trend-series="gross"', 'data-trend-series="comm"'] as $absent) {
            self::assertStringNotContainsString($absent, $dash, 'Administrator must not receive '.$absent);
        }
        // His task counter: only his own new order counts (the foreign one is another administrator's).
        self::assertSame(1, preg_match('/a1-item__title">Nove porudžbine za potvrdu<\/span>.*?a1-item__count">(\d+)</s', $dash, $m));
        self::assertSame('1', $m[1]);
        self::assertStringNotContainsString('Provizije za isplatu', $dash);
        self::assertStringNotContainsString('Nalozi na čekanju', $dash);
    }

    public function test_priority_counters_scope_link_and_hide_at_zero(): void
    {
        $admin = $this->person('superadmin');
        // Empty system: no counters, the calm state is shown.
        $empty = $this->dashboard((string) $this->actingAs($admin)->get('/')->assertOk()->getContent());
        self::assertStringContainsString('Nema hitnih stavki', $empty);
        self::assertStringNotContainsString('a1-item__count', $empty);

        $this->order($admin, 'PR-NEW-1', 1000.0, 1.0, 0.0, null, 'new');
        $this->order($admin, 'PR-NEW-2', 1000.0, 1.0, 0.0, null, 'new');
        $payOrder = $this->order($admin, 'PR-PAY-1', 1000.0, 1.0, 0.0, null, 'processing');
        DB::table('order_payments')->insert(['order_id' => $payOrder->id, 'payment_number' => 'PR-PAY-N1', 'status' => 'submitted', 'amount_rsd' => 1000, 'payment_method' => 'bank', 'created_at' => now(), 'updated_at' => now()]);
        $late = $this->order($admin, 'PR-CONF-LATE', 1000.0, 1.0, 0.0, null, 'confirmed');
        DB::table('orders')->where('id', $late->id)->update(['updated_at' => now()->subDays(5)]);
        $this->order($admin, 'PR-CONF-FRESH', 1000.0, 1.0, 0.0, null, 'confirmed');
        $commissioned = $this->order($admin, 'PR-COM-1', 1000.0, 1.0, 5.0, null, 'shipped');
        DB::table('order_commissions')->insert(['order_id' => $commissioned->id, 'user_id' => $admin->id, 'total_eur' => 5, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
        User::query()->create(['role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'), 'user_group_id' => 1, 'username' => 'pending-one', 'email' => 'pending-one@example.test', 'password_hash' => Hash::make('TestOnlyOF1116!'), 'status' => 'pending']);

        $dash = $this->dashboard((string) $this->actingAs($admin)->get('/')->assertOk()->getContent());
        $count = static function (string $title) use ($dash): ?string {
            return preg_match('/a1-item__title">'.preg_quote($title, '/').'<\/span>.*?a1-item__count">(\d+)</s', $dash, $m) === 1 ? $m[1] : null;
        };
        self::assertSame('2', $count('Nove porudžbine za potvrdu'));
        self::assertSame('1', $count('Uplate čekaju potvrdu'));
        self::assertSame('1', $count('Potvrđene, a neposlate'));
        self::assertSame('1', $count('Provizije za isplatu'));
        self::assertSame('1', $count('Nalozi na čekanju'));
        self::assertNull($count('Otvorene reklamacije'), 'a zero counter is hidden');
        self::assertStringNotContainsString('Nema hitnih stavki', $dash);
        foreach ([
            route('admin.orders.index', ['status' => 'new']), route('admin.orders.index', ['payment_status' => 'pending']),
            route('admin.orders.index', ['status' => 'confirmed']), route('admin.commissions.index', ['status' => 'approved']),
            route('admin.users.index', ['status' => 'pending']),
        ] as $href) {
            self::assertStringContainsString('href="'.e($href).'"', $dash);
        }
    }

    public function test_confirmed_ship_days_setting_is_superadmin_only_validated_and_drives_the_counter(): void
    {
        $admin = $this->person('superadmin');
        $order = $this->order($admin, 'SD-CONF', 1000.0, 1.0, 0.0, null, 'confirmed');
        DB::table('orders')->where('id', $order->id)->update(['updated_at' => now()->subDays(3)]);
        $late = static fn (string $dash): bool => str_contains($dash, 'Potvrđene, a neposlate');

        // Default 2 days: three days old is late.
        self::assertTrue($late($this->dashboard((string) $this->actingAs($admin)->get('/')->assertOk()->getContent())));

        $this->actingAs($admin)->put(route('admin.settings.modules.operations'), ['dashboard_confirmed_ship_days' => 5])->assertSessionHasNoErrors();
        self::assertSame('5', app(\App\Services\SettingsService::class)->get('dashboard_confirmed_ship_days'));
        self::assertFalse($late($this->dashboard((string) $this->actingAs($admin)->get('/')->assertOk()->getContent())), 'N = 5: three days is not late');

        foreach ([0, 31, 'abc', ''] as $bad) {
            $this->actingAs($admin)->put(route('admin.settings.modules.operations'), ['dashboard_confirmed_ship_days' => $bad])->assertSessionHasErrors('dashboard_confirmed_ship_days');
        }
        self::assertSame('5', app(\App\Services\SettingsService::class)->get('dashboard_confirmed_ship_days'));

        $this->actingAs($this->person('admin'))->put(route('admin.settings.modules.operations'), ['dashboard_confirmed_ship_days' => 1])->assertForbidden();
        self::assertSame('5', app(\App\Services\SettingsService::class)->get('dashboard_confirmed_ship_days'));
        $this->actingAs($admin)->get(route('admin.settings.modules.index'))->assertOk()->assertSee('name="dashboard_confirmed_ship_days"', false);
    }

    public function test_bell_endpoint_returns_the_last_ten_notifications_and_the_header_has_a_button(): void
    {
        $admin = $this->person('superadmin');
        foreach (range(1, 12) as $i) {
            DB::table('notifications')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'test', 'notifiable_type' => $admin->getMorphClass(), 'notifiable_id' => $admin->id,
                'data' => json_encode(['title' => 'Naslov '.$i, 'message' => 'Poruka '.$i]), 'read_at' => $i <= 2 ? now() : null,
                'created_at' => now()->subMinutes(100 - $i), 'updated_at' => now(),
            ]);
        }

        $json = $this->actingAs($admin)->getJson(route('notifications.recent'))->assertOk()->json();
        self::assertSame(10, $json['unread'] === 10 ? count($json['items']) : -1, 'ten unread of twelve');
        self::assertSame('Naslov 12', $json['items'][0]['title']);
        self::assertSame('Naslov 3', $json['items'][9]['title']);
        self::assertFalse($json['items'][0]['read']);

        $html = (string) $this->actingAs($admin)->get('/')->assertOk()->getContent();
        self::assertStringContainsString('data-bell-toggle aria-expanded="false" aria-controls="a1BellPanel"', $html);
        self::assertStringContainsString('Označi sve kao pročitano', $html);
        self::assertStringContainsString('<noscript><a class="a1-btn a1-btn--icon a1-btn--sm" href="'.e(route('notifications.index')).'"', $html);

        $this->actingAs($admin)->post(route('notifications.read-all'))->assertRedirect();
        self::assertSame(0, $this->actingAs($admin)->getJson(route('notifications.recent'))->json('unread'));
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
