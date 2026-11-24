<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CommissionPaymentBatch;
use App\Models\ExchangeRateHistory;
use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\CommissionEligibilityService;
use App\Services\CommissionWorkflowService;
use App\Services\OrderPaymentService;
use App\Services\SettingsService;
use App\Support\CommissionSql;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * commissions:fix-norate (analiza 24.11.2026). Pogođena provizija nastaje kroz pravi tok: RSD artikal bez ručne
 * provizije, kurs nije postavljen -> stavka 'automatic', 0,00 EUR, procenat 10, porudžbina bez kursa.
 * Očekivani iznosi su konstante iz testa: round(12000 / 117.2 * 0.10, 2) = 10.24; po kursu 118: 10.17.
 */
final class FixNoRateCommissionsCommandTest extends TestCase
{
    use RefreshDatabase;

    private const HISTORY_RATE = 117.2;
    private const CURRENT_RATE = '118.000000';
    private const EXPECTED_HISTORY = '10.24';
    private const EXPECTED_CURRENT = '10.17';

    private int $seq = 0;
    private User $sub;
    private User $admin;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->sub = $this->user('sub-nr', 'user');
        $this->admin = $this->user('adm-nr', 'admin');
        $this->owner = $this->user('owner-nr', 'superadmin');
    }

    protected function tearDown(): void
    {
        File::delete(File::glob(storage_path('app/reports/commissions-fix-norate-*.csv')) ?: []);
        parent::tearDown();
    }

    private function user(string $username, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $username, 'email' => $username.'@example.test', 'first_name' => ucfirst($username),
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
    }

    /** Pogođena porudžbina kroz pravi tok (bez kursa); $paid = true -> provizija je automatski "approved". */
    private function affected(bool $paid = false, int $quantity = 1): OrderCommission
    {
        $n = ++$this->seq;
        $product = Product::query()->create([
            'sku' => 'NR-'.$n, 'name' => 'NR '.$n, 'slug' => 'nr-'.$n, 'price_amount' => 12000, 'price_currency' => 'RSD',
            'manual_commission_eur' => null, 'description' => 'Test artikal.', 'stock_quantity' => 50, 'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
        $this->actingAs($this->sub)->post('/orders', [
            'idempotency_key' => 'nr-'.$n, 'supplier_user_id' => $this->admin->id,
            'shipping_full_name' => 'Test Kupac', 'shipping_address' => 'Test adresa 1', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '060123456', 'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
        ])->assertRedirect();
        $order = Order::query()->latest('id')->firstOrFail();
        if ($paid) {
            app(OrderPaymentService::class)->record($order->fresh(), [
                'amount_rsd' => (float) $order->fresh()->subtotal_rsd, 'payment_method' => 'bank_transfer', 'paid_at' => now(),
            ], $this->admin);
        }
        $commission = OrderCommission::query()->where('order_id', $order->id)->firstOrFail();
        // preduslov: ovo je zaista stanje iz analize, inače test ne dokazuje ništa
        self::assertNull($order->fresh()->eur_rsd_rate);
        self::assertSame('0.00', $commission->total_eur);
        self::assertSame(['automatic', '0.00', '10.00'], $this->itemState($commission));
        self::assertSame($paid ? 'approved' : 'pending', $commission->status);

        return $commission;
    }

    /** @return list<string> */
    private function itemState(OrderCommission $commission): array
    {
        $item = DB::table('order_items')->where('order_id', $commission->order_id)->orderBy('id')->first();

        return [(string) $item->commission_source_snapshot, number_format((float) $item->commission_unit_eur_snapshot, 2, '.', ''), number_format((float) $item->commission_rate_percent_snapshot, 2, '.', '')];
    }

    private function seedRates(): void
    {
        ExchangeRateHistory::query()->create([
            'old_rate' => null, 'new_rate' => self::HISTORY_RATE, 'mode' => 'manual', 'provider' => 'manual', 'source' => 'test',
            'provider_date' => now()->subDays(3)->toDateString(), 'triggered_by' => 'panel', 'status' => 'success', 'message' => 'test',
        ])->forceFill(['created_at' => now()->subDays(3)])->save();
        // Kasniji kurs (posle dana porudžbine) ne sme da se koristi kao kurs "na dan porudžbine".
        ExchangeRateHistory::query()->create([
            'old_rate' => self::HISTORY_RATE, 'new_rate' => 130, 'mode' => 'manual', 'provider' => 'manual', 'source' => 'test',
            'provider_date' => now()->addDays(2)->toDateString(), 'triggered_by' => 'panel', 'status' => 'success', 'message' => 'test',
        ])->forceFill(['created_at' => now()->addDays(2)])->save();
        app(SettingsService::class)->putMany(['eur_rsd_rate' => self::CURRENT_RATE], null);
    }

    private function fix(array $options = []): \Illuminate\Testing\PendingCommand
    {
        return $this->artisan('commissions:fix-norate', $options + ['--no-interaction' => true]);
    }

    private function fixHistoryCount(): int
    {
        return DB::table('commission_status_history')->where('metadata_json', 'like', '%fix_norate_20261124%')->count();
    }

    /** @return array<string,string> */
    private function tableHashes(): array
    {
        $hash = static fn (string $t): string => md5(json_encode(DB::table($t)->orderBy('id')->get()->map(static fn ($r) => (array) $r)->all(), JSON_THROW_ON_ERROR));

        return array_combine($t = ['order_commissions', 'order_items', 'commission_status_history', 'audit_logs'], array_map($hash, $t));
    }

    // 1
    public function test_dry_run_changes_nothing_and_reports_proposed_amounts_and_csv(): void
    {
        $commission = $this->affected();
        $this->seedRates();
        $before = $this->tableHashes();

        $this->fix()
            ->expectsOutputToContain('DRY-RUN')
            ->expectsOutputToContain(self::EXPECTED_HISTORY)
            ->assertSuccessful();

        self::assertSame($before, $this->tableHashes(), 'dry-run ne sme da upiše ništa');
        $csv = File::glob(storage_path('app/reports/commissions-fix-norate-dry-run-*.csv'));
        self::assertCount(1, $csv);
        $content = (string) file_get_contents($csv[0]);
        self::assertStringContainsString((string) $commission->order->order_number, $content);
        self::assertStringContainsString(self::EXPECTED_HISTORY, $content);
        self::assertStringContainsString('istorija #', $content);
    }

    // 2
    public function test_apply_fixes_class_b_pending_and_approved_without_changing_status(): void
    {
        $pending = $this->affected();
        $approved = $this->affected(paid: true, quantity: 2);
        $this->seedRates();

        $this->fix(['--apply' => true])->assertSuccessful();

        $pending = $pending->fresh();
        $approved = $approved->fresh();
        self::assertSame('pending', $pending->status);
        self::assertSame('approved', $approved->status);
        self::assertSame(self::EXPECTED_HISTORY, $pending->total_eur);
        self::assertSame('20.48', $approved->total_eur, '2 komada x 10.24');
        self::assertSame(['automatic', self::EXPECTED_HISTORY, '10.00'], $this->itemState($pending));
        self::assertSame('20.48', number_format((float) DB::table('order_items')->where('order_id', $approved->order_id)->value('commission_total_eur_snapshot'), 2, '.', ''));
        self::assertNull($pending->order->fresh()->eur_rsd_rate, 'kurs porudžbine se ne dira');
        foreach ([$pending, $approved] as $c) {
            $history = DB::table('commission_status_history')->where('commission_id', $c->id)->where('metadata_json', 'like', '%fix_norate_20261124%')->get();
            self::assertCount(1, $history);
            self::assertSame($history[0]->old_status, $history[0]->new_status);
            self::assertSame(1, DB::table('audit_logs')->where('action', 'commission.norate_corrected')->where('auditable_id', $c->id)->count());
        }
    }

    public function test_use_current_rate_option_takes_rate_from_settings(): void
    {
        $commission = $this->affected();
        $this->seedRates();

        $this->fix(['--apply' => true, '--use-current-rate' => true])->assertSuccessful();

        self::assertSame(self::EXPECTED_CURRENT, $commission->fresh()->total_eur);
    }

    // 3
    public function test_class_a_is_changed_only_with_include_manual(): void
    {
        $classA = $this->affected(quantity: 2);
        Product::query()->whereKey(DB::table('order_items')->where('order_id', $classA->order_id)->value('product_id'))->update(['manual_commission_eur' => 20]);
        $control = $this->affected();
        $this->seedRates();

        $this->fix(['--apply' => true])->expectsOutputToContain('klasa A')->assertSuccessful();

        self::assertSame('0.00', $classA->fresh()->total_eur, 'bez --include-manual klasa A ostaje');
        self::assertSame(['automatic', '0.00', '10.00'], $this->itemState($classA));
        self::assertSame(self::EXPECTED_HISTORY, $control->fresh()->total_eur, 'kontrola u istom pokretanju je ispravljena');

        $this->fix(['--apply' => true, '--include-manual' => true])->assertSuccessful();

        self::assertSame('40.00', $classA->fresh()->total_eur, '20,00 EUR x 2');
        self::assertSame(['manual', '20.00', '10.00'], $this->itemState($classA));
    }

    /** @return array<string,array{0:string}> */
    public static function untouchable(): array
    {
        return array_combine($c = ['paid', 'cancelled', 'payment_batch', 'adjustment', 'order_cancelled', 'direct_sale', 'eur_item', 'rate_percent_zero', 'price_zero', 'order_has_rate'], array_map(static fn (string $x): array => [$x], $c));
    }

    // 4
    #[DataProvider('untouchable')]
    public function test_boundaries_are_never_touched(string $case): void
    {
        $target = $this->affected(paid: true);
        $control = $this->affected(paid: true);
        $items = DB::table('order_items')->where('order_id', $target->order_id);
        match ($case) {
            'paid' => $target->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => $this->owner->id]),
            'cancelled' => $target->update(['status' => 'cancelled', 'cancelled_at' => now()]),
            'payment_batch' => $target->update(['payment_batch_id' => CommissionPaymentBatch::query()->create([
                'batch_number' => 'T-1', 'payment_method' => 'bank_transfer', 'commission_count' => 1, 'total_eur' => '0.00',
                'paid_by' => $this->owner->id, 'paid_at' => now(),
            ])->id]),
            'adjustment' => DB::table('commission_adjustments')->insert([
                'commission_id' => $target->id, 'order_id' => $target->order_id, 'kind' => 'reversal', 'status' => 'proposed',
                'amount_eur' => '0.00', 'reason' => 'test', 'source' => 'test', 'created_at' => now(), 'updated_at' => now(),
            ]),
            'order_cancelled' => DB::table('orders')->where('id', $target->order_id)->update(['status' => 'cancelled']),
            'direct_sale' => $items->update(['commission_source_snapshot' => 'direct_sale']),
            'eur_item' => $items->update(['original_currency' => 'EUR']),
            'rate_percent_zero' => $items->update(['commission_rate_percent_snapshot' => 0]),
            'price_zero' => $items->update(['unit_price_original' => 0]),
            'order_has_rate' => DB::table('orders')->where('id', $target->order_id)->update(['eur_rsd_rate' => 117]),
        };
        $this->seedRates();
        $itemBefore = (array) DB::table('order_items')->where('order_id', $target->order_id)->first();
        $totalBefore = $target->fresh()->total_eur;

        $this->fix(['--apply' => true])->assertSuccessful();

        self::assertSame(self::EXPECTED_HISTORY, $control->fresh()->total_eur, 'kontrola u istom pokretanju je ispravljena');
        self::assertSame($totalBefore, $target->fresh()->total_eur, $case);
        self::assertSame($itemBefore, (array) DB::table('order_items')->where('order_id', $target->order_id)->first(), $case);
        self::assertSame(0, DB::table('commission_status_history')->where('commission_id', $target->id)->where('metadata_json', 'like', '%fix_norate%')->count());
        self::assertSame(0, DB::table('audit_logs')->where('action', 'commission.norate_corrected')->where('auditable_id', $target->id)->count());
    }

    // 5
    public function test_missing_rate_skips_with_reason_and_writes_nothing(): void
    {
        $commission = $this->affected();
        $before = $this->tableHashes();

        $this->fix(['--apply' => true])->expectsOutputToContain('nema kursa')->assertSuccessful();

        self::assertSame($before, $this->tableHashes());
        self::assertSame('0.00', $commission->fresh()->total_eur);
    }

    // 6
    public function test_second_apply_is_idempotent(): void
    {
        $commission = $this->affected(paid: true);
        $this->seedRates();
        $this->fix(['--apply' => true])->assertSuccessful();
        self::assertSame(self::EXPECTED_HISTORY, $commission->fresh()->total_eur);
        $before = $this->tableHashes();

        $this->fix(['--apply' => true])->expectsOutputToContain('0 provizija odgovara uslovu')->assertSuccessful();

        self::assertSame($before, $this->tableHashes());
        self::assertSame(1, $this->fixHistoryCount());
    }

    // 7
    public function test_mixed_order_total_is_sum_of_both_items(): void
    {
        $commission = $this->affected(paid: true);
        // druga stavka u EUR sa ispravnom provizijom 5,00 (kao iz stare porudžbine sa mešanim stavkama)
        $first = (array) DB::table('order_items')->where('order_id', $commission->order_id)->first();
        unset($first['id']);
        DB::table('order_items')->insert(['original_currency' => 'EUR', 'unit_price_original' => 50, 'commission_unit_eur_snapshot' => 5, 'commission_total_eur_snapshot' => 5] + $first);
        $commission->update(['total_eur' => '5.00']);
        $this->seedRates();

        $this->fix(['--apply' => true])->assertSuccessful();

        self::assertSame('15.24', $commission->fresh()->total_eur, '5,00 + 10,24');
    }

    // 8
    public function test_commission_paid_between_selection_and_transaction_is_skipped(): void
    {
        $commission = $this->affected(paid: true);
        $this->seedRates();
        $fired = false;
        $key = 'eloquent.retrieved: '.OrderCommission::class;
        // Prvo čitanje (izbor u chunk-u) vidi "approved"; odmah posle toga isplata stiže iz drugog procesa.
        Event::listen($key, static function (OrderCommission $row) use (&$fired): void {
            if (!$fired) {
                $fired = true;
                DB::table('order_commissions')->where('id', $row->id)->update(['status' => 'paid', 'paid_at' => now()]);
            }
        });
        try {
            $this->fix(['--apply' => true])->expectsOutputToContain('promenila')->assertSuccessful();
        } finally {
            Event::forget($key);
        }

        self::assertTrue($fired);
        self::assertSame('0.00', $commission->fresh()->total_eur);
        self::assertSame(['automatic', '0.00', '10.00'], $this->itemState($commission));
        self::assertSame(0, $this->fixHistoryCount());
    }

    // 9
    public function test_after_fix_eligibility_payout_and_report_use_new_amount(): void
    {
        $commission = $this->affected(paid: true);
        $this->seedRates();
        $this->fix(['--apply' => true])->assertSuccessful();
        $commission = $commission->fresh();

        self::assertSame('approved', app(CommissionEligibilityService::class)->evaluate($commission)['code']);
        $report = DB::selectOne('SELECT SUM('.CommissionSql::itemEur('ri', 'o').') AS eur FROM order_items ri JOIN orders o ON o.id = ri.order_id WHERE o.id = ?', [$commission->order_id]);
        self::assertSame(self::EXPECTED_HISTORY, number_format((float) $report->eur, 2, '.', ''), 'izveštaj = stranica provizija');

        $batch = app(CommissionWorkflowService::class)->markPaidBulk([$commission->id], $this->owner, ['payment_method' => 'bank_transfer']);

        self::assertSame(self::EXPECTED_HISTORY, $batch->total_eur);
        self::assertSame('paid', $commission->fresh()->status);
    }
}
