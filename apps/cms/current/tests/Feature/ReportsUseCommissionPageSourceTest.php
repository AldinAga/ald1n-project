<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\CommissionReportService;
use App\Services\CommissionReversalService;
use App\Services\ManagementReportService;
use App\Services\SubagentSalesReportService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Audit 23.11.2026: izveštaji su računali proviziju iz snimka stavki x kurs, pa se razilazili sa stranicom provizija
 * posle storna, zaduženja i otkazivanja. Sada se iznosi poklapaju. Kurs je 100, pa je 1 EUR = 100 RSD.
 *
 * Porudžbine: O1 neisplaćena 20+10 EUR, storno 5 (25); O2 isplaćena 20+10, potvrđeno zaduženje 6 (24);
 * O3 stornirana 20 (0); O4 netaknuta 12. Ukupno 25+24+0+12 = 61 EUR = 6100 RSD.
 */
final class ReportsUseCommissionPageSourceTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->owner = $this->person('owner-rep', 'superadmin');
        $buyer = $this->person('buyer-rep', 'user');
        $this->productId = (int) Product::query()->create([
            'sku' => 'REP-1', 'name' => 'Proizvod', 'slug' => 'rep-1', 'price_amount' => 1000, 'price_currency' => 'RSD',
            'description' => 'Test artikal.', 'stock_quantity' => 5, 'status' => 'active', 'created_by' => $this->owner->id,
        ])->id;

        $o1 = $this->order('O1', $buyer, 'pending', 'shipped', [['Alfa', 20], ['Beta', 10]]);
        $o2 = $this->order('O2', $buyer, 'paid', 'shipped', [['Alfa', 20], ['Beta', 10]]);
        $this->order('O3', $buyer, 'cancelled', 'cancelled', [['Alfa', 20]]);
        $this->order('O4', $buyer, 'pending', 'shipped', [['Beta', 12]]);

        $reversal = app(CommissionReversalService::class);
        $reversal->reverse(OrderCommission::query()->where('order_id', $o1->id)->firstOrFail(), $this->owner, 'Povrat dijela robe.', '5.00');
        $reversal->reverse(OrderCommission::query()->where('order_id', $o2->id)->firstOrFail(), $this->owner, 'Povrat posle isplate.', '6.00');
    }

    private function person(string $name, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $name, 'email' => $name.'@example.test', 'first_name' => ucfirst($name),
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
    }

    /** @param list<array{0:string,1:float|int}> $items brend, provizija EUR (snimak) */
    private function order(string $key, User $buyer, string $commissionStatus, string $status, array $items): Order
    {
        $order = Order::query()->create([
            'source_system' => 'laravel', 'sales_channel' => 'order', 'order_number' => 'REP-'.$key,
            'user_id' => $buyer->id, 'supplier_user_id' => $this->owner->id, 'status' => $status,
            'shipping_full_name' => 'Kupac', 'shipping_address' => 'Adresa 1', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '060111222',
            'subtotal_rsd' => 1000 * count($items), 'eur_rsd_rate' => 100, 'payment_method' => 'bank_transfer',
            'payment_status' => 'pending', 'payment_state' => 'paid', 'paid_total_rsd' => 0,
        ]);
        foreach ($items as [$brand, $commission]) {
            DB::table('order_items')->insert([
                'order_id' => $order->id, 'product_id' => $this->productId, 'product_sku' => 'REP-1', 'product_name' => 'Proizvod',
                'quantity' => 1, 'unit_price_original' => 1000, 'original_currency' => 'RSD', 'unit_price_rsd' => 1000,
                'line_total_rsd' => 1000, 'purchase_total_rsd_snapshot' => 400, 'brand_name_snapshot' => $brand,
                'product_type_name_snapshot' => 'Tip', 'commission_source_snapshot' => 'automatic',
                'commission_rate_percent_snapshot' => 10, 'commission_unit_eur_snapshot' => $commission,
                'commission_total_eur_snapshot' => $commission,
            ]);
        }
        DB::table('order_commissions')->insert([
            'order_id' => $order->id, 'user_id' => $buyer->id, 'total_eur' => array_sum(array_column($items, 1)),
            'status' => $commissionStatus, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $order;
    }

    public function test_commission_page_figures_are_the_expected_literals(): void
    {
        // Provera same fikstire: stranica provizija je izvor istine, a očekivanja ispod su literali.
        $page = app(CommissionReportService::class)->summaryManaged($this->owner, []);

        self::assertEqualsWithDelta(37.0, $page['pending_eur'], 0.001);
        self::assertEqualsWithDelta(30.0, $page['paid_eur'], 0.001);
        self::assertEqualsWithDelta(6.0, $page['owed_eur'], 0.001);
        self::assertEqualsWithDelta(20.0, $page['cancelled_eur'], 0.001);
        self::assertEqualsWithDelta(61.0, $page['pending_eur'] + $page['approved_eur'] + $page['paid_eur'] - $page['owed_eur'], 0.001);
    }

    public function test_management_report_commission_matches_the_commission_page_after_storno_debit_and_cancellation(): void
    {
        $management = app(ManagementReportService::class);
        $filters = $management->normalizeFilters(['scope' => 'all']);

        self::assertEqualsWithDelta(6100.0, $management->summary($this->owner, $filters)['commissions_rsd'], 0.01);
        $earnings = $management->earnings($this->owner, $filters, null, false);
        self::assertEqualsWithDelta(6100.0, $earnings['commissions_rsd'], 0.01);
        self::assertEqualsWithDelta(61.0, $earnings['commissions_eur'], 0.01);
        self::assertEqualsWithDelta(6100.0, array_sum(array_column($management->trend($this->owner, $filters), 'commissions_rsd')), 0.01);
    }

    public function test_subagent_sales_report_commission_matches_the_commission_page_and_its_breakdown_adds_up(): void
    {
        $service = app(SubagentSalesReportService::class);
        $filters = $service->filters(['scope' => 'all', 'period' => 'month', 'group' => 'brand']);

        $report = $service->build($this->owner, $filters);

        self::assertEqualsWithDelta(6100.0, $report['totals']['commission'], 0.01);
        self::assertEqualsWithDelta(61.0, $report['totals']['commission_eur'], 0.01);
        self::assertEqualsWithDelta(2400.0, $report['totals']['paid'], 0.01, 'isplaćena provizija umanjena za potvrđeno zaduženje');
        self::assertEqualsWithDelta(3700.0, $report['totals']['unpaid'], 0.01, 'neisplaćena posle storna; stornirana ne ulazi');
        self::assertEqualsWithDelta(6100.0, array_sum(array_column($report['rows'], 'commission')), 0.01, 'redovi po brendu daju ukupno');
        $byBrand = array_column($report['rows'], 'commission', 'label');
        // Srazmjerno snimku stavke: Alfa = 20/30 od 25 + 20/30 od 24 (+ 0 stornirana) = 32,67 EUR; Beta = 10/30 od 25 + 10/30 od 24 + 12 = 28,33 EUR.
        self::assertEqualsWithDelta(3266.67, $byBrand['Alfa'] ?? -1, 0.02);
        self::assertEqualsWithDelta(2833.33, $byBrand['Beta'] ?? -1, 0.02);
    }
}
