<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\ManagementReportService;
use App\Services\SubagentSalesReportService;
use Carbon\Carbon;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Prodaja po subagentima. Author: Ald1n <https://ald1n.com>
 *
 * Expected numbers below are hand-calculated from the fixture (not read back from the
 * code under test). Today is frozen at 2026-11-15 12:00.
 */
final class SubagentSalesReportTest extends TestCase
{
    use RefreshDatabase;

    private const RANGE = ['period' => 'custom', 'date_from' => '2026-10-01', 'date_to' => '2026-11-15'];

    private User $super;
    private User $adminA;
    private User $adminB;
    private User $ana;
    private User $bora;
    private User $cene;
    private Product $product;
    /** @var array<string,Order> */
    private array $orders = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->travelTo(Carbon::parse('2026-11-15 12:00:00'));

        $this->super = $this->user('sbs-super', 'superadmin');
        $this->adminA = $this->user('sbs-admin-a', 'admin');
        $this->adminB = $this->user('sbs-admin-b', 'admin');
        $this->ana = $this->user('sbs-ana', 'user', 'Ana', 'Anić');
        $this->bora = $this->user('sbs-bora', 'user', '=Evil', 'Borić');
        $this->cene = $this->user('sbs-cene', 'user', 'Cene', 'Cenić');
        $this->product = Product::query()->create([
            'sku' => 'SBS-1', 'name' => 'SBS proizvod', 'slug' => 'sbs-1', 'price_amount' => 1000, 'price_currency' => 'RSD',
            'description' => 'Test proizvod.', 'stock_quantity' => 100, 'low_stock_threshold' => 1, 'status' => 'active', 'created_by' => $this->super->id,
        ]);

        // O1: manual commission, paid commission, October.
        $this->order('O1', $this->ana, $this->adminA, '2026-10-10 09:00:00', 117.0, 'paid', 'Kupac Prvi', [
            ['Alfa', 'Laptop', 2, 20000, 12000, 20, 'manual'],
        ], 'paid');
        // O2: partly paid; one item has NO purchase price.
        $this->order('O2', $this->ana, $this->adminA, '2026-11-03 09:00:00', 118.0, 'partial', 'Kupac Drugi', [
            ['Beta', 'Punjač', 1, 5000, null, 5, 'automatic'],
            ['Alfa', 'Laptop', 1, 7000, 4000, 7.5, 'automatic'],
        ], 'pending');
        $this->order('O3', $this->bora, $this->adminB, '2026-11-05 09:00:00', 117.5, 'unpaid', 'Kupac Treći', [
            ['Gama', 'Laptop', 3, 30000, 21000, 30, 'automatic'],
        ], 'approved');
        // O4: cancelled but completed_at is set: must be excluded by status alone.
        $this->order('O4', $this->bora, $this->adminB, '2026-11-06 09:00:00', 117.5, 'unpaid', 'Otkazan kupac', [
            ['Gama', 'Laptop', 1, 99999, 1, 99, 'automatic'],
        ], 'cancelled', 'cancelled');
        $this->order('O5', $this->cene, $this->adminA, '2026-11-09 09:00:00', 119.0, 'paid', 'Kupac Peti', [
            ['Beta', 'Punjač', 2, 8000, 5000, 8, 'automatic'],
        ], 'paid');
        // O6: not completed (completed_at null).
        $this->order('O6', $this->cene, $this->adminA, null, 119.0, 'unpaid', 'Nezavršen kupac', [
            ['Beta', 'Punjač', 1, 77777, 1, 77, 'automatic'],
        ], 'pending');
        // O7: direct sale, no subagent, no commission row.
        $this->order('O7', null, null, '2026-11-08 09:00:00', 117.5, 'paid', 'Kupac Direktni', [
            ['Alfa', 'Laptop', 1, 1000, 600, 0, 'automatic'],
        ], null, 'shipped', 'direct_sale');
        // O8: archived, still counted exactly like the management report counts it.
        $this->order('O8', $this->ana, $this->adminA, '2026-11-04 09:00:00', 117.0, 'unpaid', 'Arhivirani kupac', [
            ['Alfa', 'Laptop', 1, 3000, 2000, 3, 'automatic'],
        ], 'pending', 'shipped', 'order', '2026-11-20 09:00:00');
        // O9: September, outside RANGE.
        $this->order('O9', $this->ana, $this->adminA, '2026-09-15 09:00:00', 116.0, 'paid', 'Septembarski kupac', [
            ['Alfa', 'Laptop', 1, 4000, 1000, 4, 'automatic'],
        ], 'paid');
    }

    public function test_per_subagent_rows_and_totals_are_exact(): void
    {
        $report = $this->build($this->super, self::RANGE);
        $rows = array_column($report['rows'], null, 'label');

        self::assertCount(4, $rows);
        $this->assertRow($rows['Ana Anić'], orders: 3, units: 5, revenue: 35000, gross: 12000, margin: 40.0, commission: 4166, paid: 2340, unpaid: 1826, net: 7834, avg: 11666.67, share: 47.3);
        $this->assertRow($rows['=Evil Borić'], orders: 1, units: 3, revenue: 30000, gross: 9000, margin: 30.0, commission: 3525, paid: 0, unpaid: 3525, net: 5475, avg: 30000, share: 40.54);
        $this->assertRow($rows['Cene Cenić'], orders: 1, units: 2, revenue: 8000, gross: 3000, margin: 37.5, commission: 952, paid: 952, unpaid: 0, net: 2048, avg: 8000, share: 10.81);
        $this->assertRow($rows['Direktna prodaja'], orders: 1, units: 1, revenue: 1000, gross: 400, margin: 40.0, commission: 0, paid: 0, unpaid: 0, net: 400, avg: 1000, share: 1.35);
        self::assertNull($rows['Direktna prodaja']['drill_id'], 'direct sale has no subagent to drill into');

        $this->assertRow($report['totals'], orders: 6, units: 11, revenue: 74000, gross: 24400, margin: 35.36, commission: 8643, paid: 3292, unpaid: 5351, net: 15757, avg: 12333.33, share: 100.0);
        self::assertSame(1, $report['totals']['missing_lines']);
        self::assertEqualsWithDelta(5000.0, $report['totals']['missing_revenue'], 0.001);
    }

    public function test_per_brand_totals_are_exact(): void
    {
        $report = $this->build($this->super, self::RANGE + ['group' => 'brand']);
        $rows = array_column($report['rows'], null, 'label');

        self::assertCount(3, $rows);
        $this->assertRow($rows['Alfa'], orders: 4, units: 5, revenue: 31000, gross: 12400, margin: 40.0, commission: 3576, paid: 2340, unpaid: 1236, net: 8824, avg: 7750, share: 41.89);
        $this->assertRow($rows['Beta'], orders: 2, units: 3, revenue: 13000, gross: 3000, margin: 37.5, commission: 1542, paid: 952, unpaid: 590, net: 1458, avg: 6500, share: 17.57);
        $this->assertRow($rows['Gama'], orders: 1, units: 3, revenue: 30000, gross: 9000, margin: 30.0, commission: 3525, paid: 0, unpaid: 3525, net: 5475, avg: 30000, share: 40.54);
        // Brand rows overlap orders (O2 has two brands) but the totals must not double count.
        self::assertSame(6, $report['totals']['orders']);
        self::assertEqualsWithDelta(74000.0, $report['totals']['revenue'], 0.001);
    }

    public function test_totals_reconcile_with_the_management_report(): void
    {
        $report = $this->build($this->super, self::RANGE);
        $summary = app(ManagementReportService::class)->summary($this->super, app(ManagementReportService::class)->normalizeFilters([
            'date_from' => '2026-10-01', 'date_to' => '2026-11-15',
        ]));

        // The independent oracle first: the management numbers are what the fixture says they must be.
        self::assertEqualsWithDelta(74000.0, $summary['revenue_rsd'], 0.001);
        self::assertSame(6, $summary['orders_count']);

        $t = $report['totals'];
        self::assertSame($summary['orders_count'], $t['orders']);
        self::assertSame($summary['units_count'], $t['units']);
        self::assertEqualsWithDelta($summary['revenue_rsd'], $t['revenue'], 0.001);
        self::assertEqualsWithDelta($summary['cogs_rsd'], $t['cogs'], 0.001);
        self::assertEqualsWithDelta($summary['gross_profit_rsd'], $t['gross'], 0.001);
        self::assertEqualsWithDelta($summary['commissions_rsd'], $t['commission'], 0.001);
        self::assertEqualsWithDelta($summary['gross_margin_percent'], $t['margin'], 0.001);
        self::assertEqualsWithDelta($summary['average_order_rsd'], $t['avg'], 0.001);
        self::assertSame($summary['missing_cost_lines'], $t['missing_lines']);
    }

    public function test_month_grouping_and_period_presets(): void
    {
        $months = array_column($this->build($this->super, self::RANGE + ['group' => 'month'])['rows'], 'revenue', 'label');
        self::assertSame(['oktobar 2026', 'novembar 2026'], array_keys($months), 'default month order is chronological');
        self::assertEqualsWithDelta(20000.0, $months['oktobar 2026'], 0.001);
        self::assertEqualsWithDelta(54000.0, $months['novembar 2026'], 0.001);

        $revenue = fn (string $period): float => $this->build($this->super, ['period' => $period])['totals']['revenue'];
        self::assertEqualsWithDelta(54000.0, $revenue('month'), 0.001);
        self::assertEqualsWithDelta(20000.0, $revenue('prev_month'), 0.001);
        self::assertEqualsWithDelta(8000.0, $revenue('7d'), 0.001, 'last 7 days start 2026-11-09');
        self::assertEqualsWithDelta(0.0, $revenue('today'), 0.001);
        self::assertEqualsWithDelta(78000.0, $revenue('year'), 0.001, 'the year includes the September order');
        self::assertEqualsWithDelta(54000.0, $revenue('30d'), 0.001, '30 days start 2026-10-17: the 10 October and September orders fall outside');
    }

    public function test_group_by_switch_changes_the_rows(): void
    {
        $labels = fn (string $group): array => array_column($this->build($this->super, self::RANGE + ['group' => $group])['rows'], 'label');

        self::assertEqualsCanonicalizing(['Ana Anić', '=Evil Borić', 'Cene Cenić', 'Direktna prodaja'], $labels('subagent'));
        self::assertEqualsCanonicalizing(['Alfa', 'Beta', 'Gama'], $labels('brand'));
        self::assertEqualsCanonicalizing([
            'Ana Anić · Alfa', 'Ana Anić · Beta', '=Evil Borić · Gama', 'Cene Cenić · Beta', 'Direktna prodaja · Alfa',
        ], $labels('subagent_brand'));
        self::assertCount(2, $labels('month'));
    }

    public function test_each_filter_narrows_the_totals(): void
    {
        $revenue = fn (array $q): float => $this->build($this->super, self::RANGE + $q)['totals']['revenue'];
        $orders = fn (array $q): int => $this->build($this->super, self::RANGE + $q)['totals']['orders'];

        self::assertEqualsWithDelta(74000.0, $revenue([]), 0.001);
        self::assertEqualsWithDelta(35000.0, $revenue(['subagents' => [$this->ana->id]]), 0.001);
        self::assertEqualsWithDelta(43000.0, $revenue(['subagents' => [$this->ana->id, $this->cene->id]]), 0.001);
        self::assertEqualsWithDelta(13000.0, $revenue(['brands' => ['Beta']]), 0.001);
        self::assertEqualsWithDelta(61000.0, $revenue(['types' => ['Laptop']]), 0.001);
        self::assertEqualsWithDelta(29000.0, $revenue(['payment' => ['paid']]), 0.001);
        self::assertEqualsWithDelta(12000.0, $revenue(['payment' => ['partial']]), 0.001);
        self::assertEqualsWithDelta(33000.0, $revenue(['payment' => ['unpaid']]), 0.001);
        self::assertEqualsWithDelta(1000.0, $revenue(['channel' => 'direct_sale']), 0.001);
        self::assertEqualsWithDelta(73000.0, $revenue(['channel' => 'order']), 0.001);
        self::assertSame(0, $orders(['channel' => 'public_retail']));
        self::assertEqualsWithDelta(12000.0, $revenue(['customer' => 'Drugi']), 0.001);
        // LIKE wildcards typed by the user are literals, not patterns.
        self::assertSame(0, $orders(['customer' => '%']));
        self::assertSame(0, $orders(['customer' => '_rugi']));
        // Scope: all orders by creation date (everything was created "today"), incl. cancelled and unfinished.
        self::assertEqualsWithDelta(155777.0, $revenue(['scope' => 'active']), 0.001);
        self::assertEqualsWithDelta(255776.0, $revenue(['scope' => 'all']), 0.001);
    }

    public function test_cost_only_filter_reports_what_it_removed(): void
    {
        $all = $this->build($this->super, self::RANGE);
        self::assertSame(['lines' => 1, 'revenue' => 5000.0, 'removed' => false], $all['excluded']);

        $only = $this->build($this->super, self::RANGE + ['cost_only' => 1]);
        self::assertEqualsWithDelta(69000.0, $only['totals']['revenue'], 0.001);
        self::assertEqualsWithDelta(24400.0, $only['totals']['gross'], 0.001, 'profit is unchanged: the missing-cost line never counted in it');
        self::assertSame(['lines' => 1, 'revenue' => 5000.0, 'removed' => true], $only['excluded']);
    }

    public function test_comparison_with_previous_period_of_the_same_length(): void
    {
        // 2026-10-11..2026-11-10 is 31 days; the previous window is 2026-09-10..2026-10-10.
        $report = $this->build($this->super, ['period' => 'custom', 'date_from' => '2026-10-11', 'date_to' => '2026-11-10', 'compare' => 1]);

        self::assertSame(['2026-09-10', '2026-10-10'], $report['previous_period']);
        self::assertEqualsWithDelta(54000.0, $report['deltas']['revenue']['current'], 0.001);
        self::assertEqualsWithDelta(24000.0, $report['deltas']['revenue']['previous'], 0.001);
        self::assertEqualsWithDelta(30000.0, $report['deltas']['revenue']['change'], 0.001);
        self::assertEqualsWithDelta(125.0, $report['deltas']['revenue']['percent'], 0.001);
        self::assertSame('up', $report['deltas']['revenue']['direction']);
        self::assertEqualsWithDelta(5.0, $report['deltas']['orders']['current'], 0.001);
        self::assertEqualsWithDelta(2.0, $report['deltas']['orders']['previous'], 0.001);
    }

    public function test_sorting_is_server_side_and_junk_is_rejected(): void
    {
        $labels = fn (array $q): array => array_column($this->build($this->super, self::RANGE + $q)['rows'], 'label');

        self::assertSame(['Ana Anić', '=Evil Borić', 'Cene Cenić', 'Direktna prodaja'], $labels([]));
        self::assertSame(['Direktna prodaja', 'Cene Cenić', '=Evil Borić', 'Ana Anić'], $labels(['sort' => 'revenue', 'dir' => 'asc']));
        self::assertSame(['=Evil Borić', 'Ana Anić', 'Cene Cenić', 'Direktna prodaja'], $labels(['sort' => 'unpaid', 'dir' => 'desc']), 'ties (Cene, Direktna: 0) fall back to the label');
        self::assertSame(['Direktna prodaja', 'Cene Cenić', '=Evil Borić', 'Ana Anić'], $labels(['sort' => 'commission', 'dir' => 'asc']));
        self::assertSame(['=Evil Borić', 'Ana Anić', 'Cene Cenić', 'Direktna prodaja'], $labels(['sort' => 'avg', 'dir' => 'desc']));
        self::assertSame(['=Evil Borić', 'Ana Anić', 'Cene Cenić', 'Direktna prodaja'], $labels(['sort' => 'label', 'dir' => 'asc']));
        self::assertSame(['Direktna prodaja', 'Cene Cenić', 'Ana Anić', '=Evil Borić'], $labels(['sort' => 'label', 'dir' => 'desc']));
        self::assertSame(['Ana Anić', 'Direktna prodaja', 'Cene Cenić', '=Evil Borić'], $labels(['sort' => 'margin', 'dir' => 'desc']), 'margin tie (40) falls back to the label');

        foreach (['revenue;DROP TABLE orders', 'password', 'orders desc', '1', 'revenue,gross'] as $junk) {
            $this->actingAs($this->super)->get('/admin/reports/subagents?'.http_build_query(self::RANGE + ['sort' => $junk]))
                ->assertStatus(422)
                ->assertSee('Sortiranje po izabranoj koloni nije dozvoljeno.')
                ->assertDontSee('Ukupno za izabrani period');
        }
        $this->actingAs($this->super)->get('/admin/reports/subagents?'.http_build_query(self::RANGE + ['dir' => 'sideways']))
            ->assertStatus(422)->assertSee('Smer sortiranja mora biti rastući ili opadajući.');
        $this->actingAs($this->super)->get('/admin/reports/subagents?'.http_build_query(self::RANGE + ['group' => 'order']))
            ->assertStatus(422)->assertSee('Izabrano grupisanje nije podržano.');
    }

    public function test_date_range_validation(): void
    {
        $get = fn (array $q) => $this->actingAs($this->super)->get('/admin/reports/subagents?'.http_build_query($q));

        $get(['period' => 'custom', 'date_from' => '2026-11-10', 'date_to' => '2026-11-01'])
            ->assertStatus(422)->assertSee('Datum „Od“ ne sme biti posle datuma „Do“.');
        $get(['period' => 'custom', 'date_from' => '2001-01-01', 'date_to' => '2026-11-01'])
            ->assertStatus(422)->assertSee('Opseg može obuhvatiti najviše 3 godine (1100 dana).');
        $get(['period' => 'custom', 'date_from' => '1999-12-31', 'date_to' => '2000-01-02'])
            ->assertStatus(422)->assertSee('Datumi moraju biti između 01.01.2000. i godinu dana unapred.');
        $get(['period' => 'custom', 'date_from' => '2026-11-01', 'date_to' => '2030-01-01'])
            ->assertStatus(422)->assertSee('Datumi moraju biti između 01.01.2000. i godinu dana unapred.');
        $get(['period' => 'custom', 'date_from' => '2026-02-31', 'date_to' => '2026-03-01'])
            ->assertStatus(422)->assertSee('Datum „Od“ mora biti u obliku GGGG-MM-DD.');
        $get(['period' => 'custom', 'date_from' => 'juce', 'date_to' => '2026-03-01'])
            ->assertStatus(422)->assertSee('Datum „Od“ mora biti u obliku GGGG-MM-DD.');
        $get(['period' => 'custom', 'date_from' => '2026-11-01'])
            ->assertStatus(422)->assertSee('Za prilagođeni opseg unesite oba datuma.');
        // A valid custom range renders (the form is not what rejects it).
        $get(self::RANGE)->assertOk()->assertSee('01.10.2026');
    }

    public function test_page_renders_totals_empty_state_and_has_no_back_to_top(): void
    {
        $page = $this->actingAs($this->super)->get('/admin/reports/subagents?'.http_build_query(self::RANGE));
        $page->assertOk()
            ->assertSee('Prodaja po subagentima')
            ->assertSee('Ana Anić')
            ->assertSee('Direktna prodaja')
            ->assertSee('Zarada (RSD)')
            ->assertSee('Neto posle provizije')
            ->assertSee('74.000,00 RSD')
            ->assertSee('Deo prometa nema nabavnu cenu')
            ->assertSee('Ukupno za izabrani period')
            ->assertDontSee('Nazad na vrh');

        $this->actingAs($this->super)->get('/admin/reports/subagents?'.http_build_query(self::RANGE + ['customer' => 'ne postoji nijedan takav']))
            ->assertOk()->assertSee('Nema prodaje za izabrane filtere')->assertDontSee('Ukupno za izabrani period');
    }

    public function test_drill_down_lists_only_that_subagents_orders(): void
    {
        $page = $this->actingAs($this->super)->get('/admin/reports/subagents?'.http_build_query(self::RANGE + ['drill' => $this->ana->id]));
        $page->assertOk()
            ->assertSee('SBS-O1')->assertSee('SBS-O2')->assertSee('SBS-O8')
            ->assertDontSee('SBS-O3')->assertDontSee('SBS-O5')->assertDontSee('SBS-O7')->assertDontSee('SBS-O9')
            ->assertDontSee('=Evil Borić');

        $report = $this->build($this->super, self::RANGE + ['drill' => $this->ana->id]);
        self::assertSame('brand', $report['group'], 'drill-down shows the subagent by brand');
        self::assertSame(3, $report['orders']->total());
        self::assertEqualsWithDelta(35000.0, $report['totals']['revenue'], 0.001);
    }

    public function test_csv_is_streamed_utf8_bom_with_plain_numbers_and_escaped_text(): void
    {
        $response = $this->actingAs($this->super)->get('/admin/reports/subagents.csv?'.http_build_query(self::RANGE));
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        self::assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $content = $response->streamedContent();

        self::assertStringStartsWith("\xEF\xBB\xBF", $content);
        $lines = array_values(array_filter(explode("\n", substr($content, 3)), static fn (string $l): bool => $l !== ''));
        $table = array_map(static fn (string $l): array => str_getcsv($l, ';', '"', ''), $lines);

        self::assertSame(['Subagent', 'Porudžbina', 'Komada', 'Promet RSD', 'Zarada RSD', 'Marža %', 'Provizija RSD', 'Provizija isplaćena RSD', 'Provizija neisplaćena RSD', 'Neto posle provizije RSD', 'Prosečna porudžbina RSD', 'Udeo u prometu %'], $table[0]);
        $byLabel = array_column($table, null, 0);
        self::assertSame(['Ana Anić', '3', '5', '35000.00', '12000.00', '40.00', '4166.00', '2340.00', '1826.00', '7834.00', '11666.67', '47.30'], $byLabel['Ana Anić']);
        self::assertSame(['Ukupno', '6', '11', '74000.00', '24400.00', '35.36', '8643.00', '3292.00', '5351.00', '15757.00', '12333.33', '100.00'], $byLabel['Ukupno']);
        // Formula injection: a name that starts with "=" is neutralised, the real value is still there.
        self::assertArrayHasKey("'=Evil Borić", $byLabel);
        self::assertArrayNotHasKey('=Evil Borić', $byLabel);
    }

    public function test_csv_keeps_negative_numbers_plain_and_escapes_every_formula_prefix(): void
    {
        $service = app(SubagentSalesReportService::class);
        foreach (['=1+1', '+1', '-1', '@SUM(A1)', "\t1", "\r1"] as $text) {
            self::assertSame("'".$text, $service->csvText($text), 'prefix '.json_encode($text));
        }
        self::assertSame('Ana', $service->csvText('Ana'));
        self::assertSame('', $service->csvText(''));

        // A loss-making sale: its profit must come out as a plain negative number, not as "'-2000.00".
        $this->order('LOSS', $this->cene, $this->adminA, '2026-11-12 09:00:00', 119.0, 'paid', 'Gubitak kupac', [
            ['Alfa', 'Laptop', 1, 1000, 3000, 0, 'automatic'],
        ], 'paid');
        $csv = $this->actingAs($this->super)->get('/admin/reports/subagents.csv?'.http_build_query(self::RANGE + ['group' => 'brand', 'customer' => 'Gubitak']))
            ->assertOk()->streamedContent();
        self::assertStringContainsString(';-2000.00;-200.00;', $csv);
        self::assertStringNotContainsString("'-2000.00", $csv);
    }

    public function test_csv_follows_filters_and_grouping_of_the_view(): void
    {
        $csv = $this->actingAs($this->super)->get('/admin/reports/subagents.csv?'.http_build_query(self::RANGE + ['group' => 'brand', 'brands' => ['Beta']]))
            ->assertOk()->streamedContent();
        $table = array_map(static fn (string $l): array => str_getcsv($l, ';', '"', ''), array_values(array_filter(explode("\n", substr($csv, 3)))));

        self::assertSame('Brend', $table[0][0]);
        self::assertSame(['Beta', '2', '3', '13000.00'], array_slice($table[1], 0, 4));
        self::assertSame('Ukupno', $table[2][0]);
        self::assertCount(3, $table);
    }

    public function test_authorization_matrix(): void
    {
        $page = '/admin/reports/subagents?'.http_build_query(self::RANGE);
        $csv = '/admin/reports/subagents.csv?'.http_build_query(self::RANGE);

        $this->get($page)->assertRedirect('/login');
        $this->get($csv)->assertRedirect('/login');

        $noGroup = $this->user('sbs-nogroup', 'user', 'Bez', 'Grupe', null);
        $customer = $this->user('sbs-customer', 'customer', 'Kupac', 'Retail', null);
        foreach ([$noGroup, $customer] as $denied) {
            $this->actingAs($denied)->get($page)->assertForbidden();
            $this->actingAs($denied)->get($csv)->assertForbidden();
            $this->actingAs($denied)->get($page.'&drill='.$this->ana->id)->assertForbidden();
        }

        foreach ([$this->super, $this->adminA] as $allowed) {
            $this->actingAs($allowed)->get($page)->assertOk();
            $this->actingAs($allowed)->get($csv)->assertOk();
        }

        // A subagent (standard group: commissions.view_own) may open the page for their own data, never the CSV.
        $this->actingAs($this->ana)->get($page)->assertOk();
        $this->actingAs($this->ana)->get($csv)->assertForbidden();
        $this->actingAs($this->ana)->get($page.'&drill='.$this->ana->id)->assertOk();
    }

    public function test_a_subagent_sees_only_their_own_data_and_no_purchase_cost(): void
    {
        $page = $this->actingAs($this->ana)->get('/admin/reports/subagents?'.http_build_query(self::RANGE));
        $page->assertOk()
            ->assertSee('Ana Anić')->assertSee('35.000,00')->assertSee('4.166,00')
            ->assertSee('Prikazani su samo tvoji podaci')
            ->assertDontSee('=Evil Borić')->assertDontSee('Cene Cenić')->assertDontSee('Direktna prodaja')
            ->assertDontSee('Zarada (RSD)')->assertDontSee('Neto posle provizije')->assertDontSee('Marža')
            ->assertDontSee('Samo artikli sa nabavnom cenom');

        $report = $this->build($this->ana, self::RANGE);
        self::assertSame(['Ana Anić'], array_column($report['rows'], 'label'));
        foreach (['gross', 'margin', 'net', 'cogs'] as $secret) {
            self::assertNull($report['totals'][$secret], $secret.' must not leave the service for a subagent');
            self::assertNull($report['rows'][0][$secret], $secret.' must not leave the service for a subagent');
        }

        // Passing other people's ids changes nothing: the ids are ignored, the scope stays "own orders".
        $other = $this->actingAs($this->ana)->get('/admin/reports/subagents?'.http_build_query(self::RANGE + ['subagents' => [$this->bora->id], 'drill' => $this->bora->id]));
        $other->assertOk()->assertSee('Ana Anić')->assertSee('35.000,00')->assertDontSee('=Evil Borić')->assertDontSee('SBS-O3');
        // Cost columns are not even sortable.
        $this->actingAs($this->ana)->get('/admin/reports/subagents?'.http_build_query(self::RANGE + ['sort' => 'gross']))
            ->assertStatus(422)->assertSee('Sortiranje po izabranoj koloni nije dozvoljeno.');
        $this->actingAs($this->ana)->get('/admin/reports/subagents?'.http_build_query(self::RANGE + ['cost_only' => 1]))
            ->assertOk()->assertSee('35.000,00');
    }

    public function test_an_admin_sees_only_orders_they_supply_and_cannot_widen_the_scope_with_ids(): void
    {
        $a = $this->build($this->adminA, self::RANGE);
        self::assertEqualsCanonicalizing(['Ana Anić', 'Cene Cenić'], array_column($a['rows'], 'label'));
        self::assertEqualsWithDelta(43000.0, $a['totals']['revenue'], 0.001);

        $b = $this->build($this->adminB, self::RANGE);
        self::assertSame(['=Evil Borić'], array_column($b['rows'], 'label'));
        self::assertEqualsWithDelta(30000.0, $b['totals']['revenue'], 0.001);

        $query = '/admin/reports/subagents?'.http_build_query(self::RANGE);
        $this->actingAs($this->adminA)->get($query)
            ->assertOk()->assertSee('Ana Anić')->assertSee('43.000,00')->assertDontSee('Borić');
        $this->actingAs($this->adminA)->get($query.'&'.http_build_query(['subagents' => [$this->bora->id]]))
            ->assertStatus(422)->assertSee('Izabrani subagent nije dostupan.')->assertDontSee('30.000,00');
        $this->actingAs($this->adminA)->get($query.'&drill='.$this->bora->id)
            ->assertStatus(422)->assertSee('Izabrani subagent nije dostupan.')->assertDontSee('SBS-O3');
        $this->actingAs($this->adminA)->get($query.'&'.http_build_query(['brands' => ['Gama']]))
            ->assertStatus(422)->assertSee('Izabrani brend nije dostupan.');

        $csv = $this->actingAs($this->adminA)->get('/admin/reports/subagents.csv?'.http_build_query(self::RANGE))->assertOk()->streamedContent();
        self::assertStringContainsString('Ana Anić', $csv);
        self::assertStringNotContainsString('Borić', $csv);
        self::assertStringContainsString('43000.00', $csv);
    }

    public function test_entry_points_exist_for_operators_and_subagents(): void
    {
        $this->actingAs($this->super)->get('/admin/reports')->assertOk()->assertSee('/admin/reports/subagents');
        $this->actingAs($this->ana)->get('/commissions')->assertOk()->assertSee('/admin/reports/subagents');
    }

    // ---------------------------------------------------------------- helpers

    /** @param array<string,mixed> $query @return array<string,mixed> */
    private function build(User $user, array $query): array
    {
        $service = app(SubagentSalesReportService::class);
        $validator = $service->validator($user, $query, $service->options($user));
        self::assertFalse($validator->fails(), implode(' ', $validator->errors()->all()));

        return $service->build($user, $service->filters($user, $validator->validated()));
    }

    /** @param array<string,mixed> $row */
    private function assertRow(array $row, int $orders, int $units, float $revenue, float $gross, float $margin, float $commission, float $paid, float $unpaid, float $net, float $avg, float $share): void
    {
        $label = (string) ($row['label'] ?? 'totals');
        self::assertSame($orders, $row['orders'], $label.' orders');
        self::assertSame($units, $row['units'], $label.' units');
        foreach (['revenue' => $revenue, 'gross' => $gross, 'margin' => $margin, 'commission' => $commission, 'paid' => $paid, 'unpaid' => $unpaid, 'net' => $net, 'avg' => $avg, 'share' => $share] as $key => $expected) {
            self::assertEqualsWithDelta($expected, (float) $row[$key], 0.001, $label.' '.$key);
        }
    }

    private function user(string $username, string $role, string $first = '', string $last = '', ?int $group = 1): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? $group : null,
            'username' => $username,
            'email' => $username.'@example.test',
            'first_name' => $first !== '' ? $first : null,
            'last_name' => $last !== '' ? $last : null,
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    /**
     * @param list<array{0:string,1:string,2:int,3:int|float,4:int|float|null,5:int|float,6:string}> $items brand, type, qty, revenue, cost, commission EUR, source
     */
    private function order(string $key, ?User $buyer, ?User $supplier, ?string $completedAt, float $rate, string $paymentState, string $customer, array $items, ?string $commissionStatus, string $status = 'shipped', string $channel = 'order', ?string $archivedAt = null): void
    {
        $order = Order::query()->create([
            'source_system' => 'laravel',
            'sales_channel' => $channel,
            'order_number' => 'SBS-'.$key,
            'user_id' => $buyer?->id,
            'supplier_user_id' => $supplier?->id,
            'status' => $status,
            'shipping_full_name' => $customer,
            'shipping_address' => 'Adresa 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060111222',
            'subtotal_rsd' => array_sum(array_column($items, 3)),
            'eur_rsd_rate' => $rate,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'payment_state' => $paymentState,
            'paid_total_rsd' => 0,
            'completed_at' => $completedAt,
            'archived_at' => $archivedAt,
        ]);
        $this->orders[$key] = $order;

        foreach ($items as [$brand, $type, $qty, $revenue, $cost, $commission, $source]) {
            DB::table('order_items')->insert([
                'order_id' => $order->id,
                'product_id' => $this->product->id,
                'product_sku' => 'SBS-1',
                'product_name' => 'SBS proizvod',
                'quantity' => $qty,
                'unit_price_original' => $revenue / $qty,
                'original_currency' => 'RSD',
                'unit_price_rsd' => $revenue / $qty,
                'line_total_rsd' => $revenue,
                'purchase_total_rsd_snapshot' => $cost,
                'brand_name_snapshot' => $brand,
                'product_type_name_snapshot' => $type,
                'commission_source_snapshot' => $source,
                'commission_rate_percent_snapshot' => null,
                'commission_unit_eur_snapshot' => $commission / $qty,
                'commission_total_eur_snapshot' => $commission,
            ]);
        }

        if ($commissionStatus !== null && $buyer !== null) {
            DB::table('order_commissions')->insert([
                'order_id' => $order->id,
                'user_id' => $buyer->id,
                'total_eur' => array_sum(array_column($items, 5)),
                'status' => $commissionStatus,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
