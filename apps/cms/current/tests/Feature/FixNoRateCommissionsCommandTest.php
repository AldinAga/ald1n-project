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
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\PendingCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * commissions:fix-norate (analiza 24.11.2026). Pogođena provizija nastaje kroz pravi tok: RSD artikal bez ručne
 * provizije, kurs nije postavljen -> stavka 'automatic', 0,00 EUR, procenat 10, porudžbina bez kursa.
 * Očekivani iznosi su konstante iz testa: 12000 RSD x 10 %: kurs 117,2 -> 10.24; 118 -> 10.17; 130 -> 9.23.
 */
final class FixNoRateCommissionsCommandTest extends TestCase
{
    use RefreshDatabase;

    private const HISTORY_RATE = 117.2;
    private const LATER_RATE = 130.0;
    private const CURRENT_RATE = '118.000000';
    private const EXPECTED_HISTORY = '10.24';
    private const EXPECTED_CURRENT = '10.17';
    private const EXPECTED_LATER = '9.23';
    private const CONFIRM = 'Upisati ispravke za %d provizija? Prethodno pregledaj dry-run CSV.';

    private int $seq = 0;
    private User $sub;
    private User $admin;
    private User $owner;
    /** @var list<string> */
    private array $reportsBefore = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportsBefore = $this->reports();
        $this->seed(CoreAccessSeeder::class);
        $this->sub = $this->user('sub-nr', 'user');
        $this->admin = $this->user('adm-nr', 'admin');
        $this->owner = $this->user('owner-nr', 'superadmin');
    }

    protected function tearDown(): void
    {
        File::delete(array_values(array_diff($this->reports(), $this->reportsBefore))); // samo CSV koje je napravio ovaj test
        parent::tearDown();
    }

    /** @return list<string> */
    private function reports(): array
    {
        return File::glob(storage_path('app/reports/commissions-fix-norate-*.csv')) ?: [];
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

    /** @return list<?string> izvor, jedinična provizija, procenat */
    private function itemState(OrderCommission $commission): array
    {
        $item = DB::table('order_items')->where('order_id', $commission->order_id)->orderBy('id')->first();

        return [
            (string) $item->commission_source_snapshot,
            number_format((float) $item->commission_unit_eur_snapshot, 2, '.', ''),
            $item->commission_rate_percent_snapshot === null ? null : number_format((float) $item->commission_rate_percent_snapshot, 2, '.', ''),
        ];
    }

    private function rate(float $rate, Carbon $at): ExchangeRateHistory
    {
        $row = ExchangeRateHistory::query()->create([
            'old_rate' => null, 'new_rate' => $rate, 'mode' => 'manual', 'provider' => 'manual', 'source' => 'test',
            'provider_date' => $at->toDateString(), 'triggered_by' => 'panel', 'status' => 'success', 'message' => 'test',
        ]);
        $row->forceFill(['created_at' => $at])->save();

        return $row;
    }

    private function setCurrentRate(): void
    {
        app(SettingsService::class)->putMany(['eur_rsd_rate' => self::CURRENT_RATE], null);
    }

    /** Raniji kurs (117,2), kasniji kurs (130) i trenutni (118): podrazumevano se mora uzeti raniji. */
    private function seedRates(): void
    {
        $this->rate(self::HISTORY_RATE, now()->subDays(3));
        $this->rate(self::LATER_RATE, now()->addDays(2));
        $this->setCurrentRate();
    }

    private function fix(array $options = []): PendingCommand
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

    /** @return list<list<string>> redovi CSV-a bez zaglavlja */
    private function csvRows(string $mode): array
    {
        $files = array_values(array_diff(File::glob(storage_path("app/reports/commissions-fix-norate-$mode-*.csv")), $this->reportsBefore));
        self::assertCount(1, $files);
        $lines = array_map(static fn (string $l): array => str_getcsv($l, ',', '"', ''), file($files[0], FILE_IGNORE_NEW_LINES));

        return array_slice($lines, 1);
    }

    /** Porudžbina na fiksan datum (10:00), da granica dana ne zavisi od sata izvođenja testa. */
    private function onDay(OrderCommission $commission, string $day): void
    {
        DB::table('orders')->where('id', $commission->order_id)->update(['created_at' => $day.' 10:00:00']);
    }

    private function auditFor(OrderCommission $commission): object
    {
        $rows = DB::table('audit_logs')->where('action', 'commission.norate_corrected')->where('auditable_id', $commission->id)->get();
        self::assertCount(1, $rows);

        return $rows[0];
    }

    // 1
    public function test_dry_run_changes_nothing_and_reports_proposed_amounts_and_csv(): void
    {
        $commission = $this->affected();
        $this->seedRates();
        $before = $this->tableHashes();

        // Mockery: red izlaza troši prvo očekivanje koje mu odgovara, pa očekivanja moraju biti različiti redovi.
        $this->fix()
            ->expectsOutputToContain('DRY-RUN')
            ->expectsOutputToContain('ukupno povećanje: '.self::EXPECTED_HISTORY.' EUR')
            ->assertSuccessful();

        self::assertSame($before, $this->tableHashes(), 'dry-run ne sme da upiše ništa');
        $rows = $this->csvRows('dry-run');
        self::assertCount(1, $rows);
        self::assertSame((string) $commission->order->order_number, $rows[0][0]);
        self::assertSame((string) $commission->order_id, $rows[0][1], 'kolona ID porudžbine za --order');
        self::assertSame(self::EXPECTED_HISTORY, $rows[0][7]);
        self::assertStringStartsWith('istorija #', $rows[0][9]);
        self::assertSame('predlog', $rows[0][10]);
    }

    // 3 (deo): dry-run ne predlaže isključene provizije i ne ubraja ih u zbir
    public function test_dry_run_does_not_propose_excluded_commissions_nor_count_them(): void
    {
        $classA = $this->affected();
        Product::query()->whereKey(DB::table('order_items')->where('order_id', $classA->order_id)->value('product_id'))->update(['manual_commission_eur' => 20]);
        $adjusted = $this->affected();
        DB::table('commission_adjustments')->insert([
            'commission_id' => $adjusted->id, 'order_id' => $adjusted->order_id, 'kind' => 'reversal', 'status' => 'proposed',
            'amount_eur' => '0.00', 'reason' => 'test', 'source' => 'test', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $control = $this->affected();
        $this->seedRates();

        $this->fix()
            ->expectsOutputToContain('1 provizija odgovara uslovu')
            ->expectsOutputToContain('ukupno povećanje: '.self::EXPECTED_HISTORY.' EUR')
            ->assertSuccessful();

        $byCommission = collect($this->csvRows('dry-run'))->keyBy(2);
        self::assertSame(self::EXPECTED_HISTORY, $byCommission[(string) $control->id][7]);
        self::assertSame('', $byCommission[(string) $classA->id][7]);
        self::assertStringContainsString('klasa A', $byCommission[(string) $classA->id][10]);
        self::assertSame('', $byCommission[(string) $adjusted->id][7]);
        self::assertStringContainsString('korekcije', $byCommission[(string) $adjusted->id][10]);
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
            $this->auditFor($c);
        }
    }

    // 7 (N7): sadržaj audit zapisa
    public function test_audit_record_holds_before_after_and_item_details(): void
    {
        $commission = $this->affected(paid: true, quantity: 2);
        $this->seedRates();

        $this->fix(['--apply' => true])->assertSuccessful();

        $audit = $this->auditFor($commission);
        $before = json_decode((string) $audit->before_json, true, flags: JSON_THROW_ON_ERROR);
        $after = json_decode((string) $audit->after_json, true, flags: JSON_THROW_ON_ERROR);
        $meta = json_decode((string) $audit->metadata_json, true, flags: JSON_THROW_ON_ERROR);
        $itemId = (int) DB::table('order_items')->where('order_id', $commission->order_id)->value('id');
        self::assertSame(['total_eur' => '0.00', 'status' => 'approved'], array_intersect_key($before, ['total_eur' => 1, 'status' => 1]));
        self::assertSame(['total_eur' => '20.48', 'status' => 'approved'], array_intersect_key($after, ['total_eur' => 1, 'status' => 1]));
        self::assertSame(['unit' => '0.00', 'total' => '0.00'], $before['items'][$itemId]);
        self::assertSame(['unit' => self::EXPECTED_HISTORY, 'total' => '20.48'], $after['items'][$itemId]);
        self::assertSame('fix_norate_20261124', $meta['source']);
        self::assertEqualsWithDelta(self::HISTORY_RATE, (float) $meta['rate'], 0.000001);
        self::assertStringStartsWith('istorija #', $meta['rate_source']);
        self::assertSame(2, $meta['items'][$itemId]['quantity']);
        self::assertSame('0.00', $meta['items'][$itemId]['old_total']);
        self::assertSame('20.48', $meta['items'][$itemId]['new_total']);
    }

    // kurs, put 1a: kraj dana porudžbine (23:59:59) je još "na dan", sledeći dan 00:00:00 nije
    public function test_rate_end_of_order_day_counts_as_order_day(): void
    {
        $commission = $this->affected();
        $this->onDay($commission, '2026-11-20');
        $this->rate(self::HISTORY_RATE, Carbon::parse('2026-11-20 23:59:59'));
        $this->rate(self::LATER_RATE, Carbon::parse('2026-11-21 00:00:00'));
        $this->setCurrentRate();

        $this->fix(['--apply' => true])->expectsOutputToContain('na dan=1')->assertSuccessful();

        self::assertSame(self::EXPECTED_HISTORY, $commission->fresh()->total_eur);
    }

    // kurs, put 1b (odluka vlasnika): raniji kurs (D-3) i kurs istog dana posle porudžbine (D 22:00) -> kurs od 22:00
    public function test_rate_same_day_after_order_wins_over_earlier_day(): void
    {
        $commission = $this->affected();
        $this->onDay($commission, '2026-11-20');
        $this->rate(self::LATER_RATE, Carbon::parse('2026-11-17 10:00:00'));
        $this->rate(self::HISTORY_RATE, Carbon::parse('2026-11-20 22:00:00'));
        $this->setCurrentRate();

        $this->fix(['--apply' => true])->expectsOutputToContain('na dan=1')->assertSuccessful();

        self::assertSame(self::EXPECTED_HISTORY, $commission->fresh()->total_eur, 'kurs od 22:00 istog dana, ne D-3');
        self::assertStringStartsWith('na dan #', json_decode((string) $this->auditFor($commission)->metadata_json, true)['rate_source']);
    }

    // kurs, put 1c: samo raniji dan -> 'istorija'
    public function test_rate_from_earlier_day_is_history(): void
    {
        $commission = $this->affected();
        $this->onDay($commission, '2026-11-20');
        $this->rate(self::HISTORY_RATE, Carbon::parse('2026-11-17 10:00:00'));
        $this->setCurrentRate();

        $this->fix(['--apply' => true])->expectsOutputToContain('istorija=1')->assertSuccessful();

        self::assertSame(self::EXPECTED_HISTORY, $commission->fresh()->total_eur);
    }

    /** @return array<string,array{0:string}> */
    public static function invalidOrderOption(): array
    {
        return ['nepostojeći broj porudžbine' => ['APC-20261124-99999999'], 'nula' => ['0'], 'broj sa slovima' => ['12abc'], 'prazno' => [''], 'negativan' => ['-5']];
    }

    // P1: neispravan --order nikad ne sme da ukine filter
    #[DataProvider('invalidOrderOption')]
    public function test_invalid_order_option_fails_without_any_write(string $value): void
    {
        $commission = $this->affected();
        $this->seedRates();
        $before = $this->tableHashes();

        $this->fix(['--apply' => true, '--order' => [$value]])->expectsOutputToContain('Ništa nije obrađeno')->assertFailed();

        self::assertSame($before, $this->tableHashes());
        self::assertSame('0.00', $commission->fresh()->total_eur);
        self::assertSame([], array_values(array_diff($this->reports(), $this->reportsBefore)), 'ni CSV se ne pravi');
    }

    // P1: broj porudžbine (APC-...) se razrešava u ID i ograničava obradu na tu porudžbinu
    public function test_order_option_accepts_order_number(): void
    {
        $chosen = $this->affected();
        $other = $this->affected();
        $this->seedRates();
        $number = (string) $chosen->order->order_number;

        $this->fix(['--apply' => true, '--order' => [$number]])
            ->expectsOutputToContain(sprintf('--order=%s -> ID porudžbine %d', $number, $chosen->order_id))
            ->assertSuccessful();

        self::assertSame(self::EXPECTED_HISTORY, $chosen->fresh()->total_eur);
        self::assertSame('0.00', $other->fresh()->total_eur);
    }

    // kurs, put 2: nema ranijeg -> prvi poslije porudžbine (granica: sledeći dan 00:00:00)
    public function test_rate_falls_back_to_first_rate_after_order(): void
    {
        $commission = $this->affected();
        $this->onDay($commission, '2026-11-20');
        $this->rate(self::LATER_RATE, Carbon::parse('2026-11-21 00:00:00'));
        $this->rate(150, Carbon::parse('2026-11-23 09:00:00'));
        $this->setCurrentRate();

        $this->fix(['--apply' => true])->expectsOutputToContain('prvi poslije=1')->assertSuccessful();

        self::assertSame(self::EXPECTED_LATER, $commission->fresh()->total_eur);
        self::assertStringStartsWith('prvi poslije #', json_decode((string) $this->auditFor($commission)->metadata_json, true)['rate_source']);
    }

    // kurs, put 3: istorija prazna -> trenutni
    public function test_rate_falls_back_to_current_setting_without_history(): void
    {
        $commission = $this->affected();
        $this->setCurrentRate();

        $this->fix(['--apply' => true])->expectsOutputToContain('trenutni=1')->assertSuccessful();

        self::assertSame(self::EXPECTED_CURRENT, $commission->fresh()->total_eur);
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

        $this->fix(['--apply' => true, '--include-manual' => true])
            ->expectsOutputToContain('DANAŠNJU ručnu proviziju')
            ->assertSuccessful();

        self::assertSame('40.00', $classA->fresh()->total_eur, '20,00 EUR x 2');
        self::assertSame(['manual', '20.00', null], $this->itemState($classA), 'kao OrderService: ručna stavka nema procenat');
        $itemId = (int) DB::table('order_items')->where('order_id', $classA->order_id)->value('id');
        $meta = json_decode((string) $this->auditFor($classA)->metadata_json, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('20.00', $meta['items'][$itemId]['manual_commission_eur']);
        self::assertSame('0.00', $meta['items'][$itemId]['old_total']);
        self::assertSame('40.00', $meta['items'][$itemId]['new_total']);
        self::assertTrue($meta['items'][$itemId]['estimate']);
    }

    /** @return array<string,array{0:string}> */
    public static function untouchable(): array
    {
        return array_combine($c = ['paid', 'cancelled', 'payment_batch', 'adjustment', 'order_cancelled', 'direct_sale', 'eur_item', 'rate_percent_zero', 'price_zero', 'order_has_rate'], array_map(static fn (string $x): array => [$x], $c));
    }

    // 4: svaka granica posebno, uz kontrolnu proviziju koja se u istom pokretanju ispravlja
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

    // N8: isplaćene pogođene provizije se samo listaju
    public function test_paid_affected_commissions_are_listed_but_not_changed(): void
    {
        $paid = $this->affected(paid: true);
        $paid->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => $this->owner->id]);
        $this->seedRates();
        $before = $this->tableHashes();

        $this->fix(['--apply' => true])
            ->expectsOutputToContain('Već isplaćene pogođene provizije (samo pregled, ne menja se)')
            ->expectsOutputToContain('već isplaćenih pogođenih: 1')
            ->assertSuccessful();

        self::assertSame($before, $this->tableHashes());
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

    // N6 + N7: potvrda broji samo primenjive provizije; odbijanje -> nema upisa
    public function test_declined_confirmation_writes_nothing_and_counts_only_applicable(): void
    {
        $this->affected();
        $classA = $this->affected();
        Product::query()->whereKey(DB::table('order_items')->where('order_id', $classA->order_id)->value('product_id'))->update(['manual_commission_eur' => 20]);
        $this->seedRates();
        $before = $this->tableHashes();

        $this->artisan('commissions:fix-norate', ['--apply' => true])
            ->expectsConfirmation(sprintf(self::CONFIRM, 1), 'no')
            ->expectsOutputToContain('Prekinuto')
            ->assertSuccessful();

        self::assertSame($before, $this->tableHashes());
    }

    public function test_confirmed_interactive_apply_writes(): void
    {
        $commission = $this->affected();
        $this->seedRates();

        $this->artisan('commissions:fix-norate', ['--apply' => true])->expectsConfirmation(sprintf(self::CONFIRM, 1), 'yes')->assertSuccessful();

        self::assertSame(self::EXPECTED_HISTORY, $commission->fresh()->total_eur);
    }

    // N7: --order ograničava na izabranu porudžbinu
    public function test_order_option_limits_to_selected_order(): void
    {
        $chosen = $this->affected();
        $other = $this->affected();
        $this->seedRates();

        $this->fix(['--apply' => true, '--order' => [$chosen->order_id]])->assertSuccessful();

        self::assertSame(self::EXPECTED_HISTORY, $chosen->fresh()->total_eur);
        self::assertSame('0.00', $other->fresh()->total_eur);
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

    /** Jednom, odmah posle izbora provizije u chunk-u (pre transakcije), izvrši izmenu "iz drugog procesa". */
    private function betweenSelectionAndTransaction(callable $change): void
    {
        $fired = false;
        Event::listen('eloquent.retrieved: '.OrderCommission::class, static function (OrderCommission $row) use (&$fired, $change): void {
            if (!$fired) {
                $fired = true;
                $change($row);
            }
        });
    }

    // 8a: isplata u međuvremenu
    public function test_commission_paid_between_selection_and_transaction_is_skipped(): void
    {
        $commission = $this->affected(paid: true);
        $this->seedRates();
        $this->betweenSelectionAndTransaction(static fn (OrderCommission $row) => DB::table('order_commissions')->where('id', $row->id)->update(['status' => 'paid', 'paid_at' => now()]));

        $this->fix(['--apply' => true])->expectsOutputToContain('provizija se u međuvremenu promenila')->assertSuccessful();

        self::assertSame('0.00', $commission->fresh()->total_eur);
        self::assertSame(['automatic', '0.00', '10.00'], $this->itemState($commission));
        self::assertSame(0, $this->fixHistoryCount());
    }

    // 8b: korekcija u međuvremenu
    public function test_adjustment_added_between_selection_and_transaction_is_skipped(): void
    {
        $commission = $this->affected(paid: true);
        $this->seedRates();
        $this->betweenSelectionAndTransaction(static fn (OrderCommission $row) => DB::table('commission_adjustments')->insert([
            'commission_id' => $row->id, 'order_id' => $row->order_id, 'kind' => 'reversal', 'status' => 'proposed',
            'amount_eur' => '0.00', 'reason' => 'test', 'source' => 'test', 'created_at' => now(), 'updated_at' => now(),
        ]));

        $this->fix(['--apply' => true])->expectsOutputToContain('provizija ima korekcije')->assertSuccessful();

        self::assertSame('0.00', $commission->fresh()->total_eur);
        self::assertSame(0, $this->fixHistoryCount());
    }

    // 8c: količina promenjena u međuvremenu -> iznos iz zaključane stavke
    public function test_amount_uses_locked_quantity_changed_after_selection(): void
    {
        $commission = $this->affected(paid: true);
        $this->seedRates();
        $this->betweenSelectionAndTransaction(static fn (OrderCommission $row) => DB::table('order_items')->where('order_id', $row->order_id)->update(['quantity' => 3]));

        $this->fix(['--apply' => true])->assertSuccessful();

        self::assertSame('30.72', $commission->fresh()->total_eur, '3 komada x 10.24, ne 1 komad iz izbora');
        self::assertSame('30.72', number_format((float) DB::table('order_items')->where('order_id', $commission->order_id)->value('commission_total_eur_snapshot'), 2, '.', ''));
    }

    // N1: zaključavanje porudžbina -> proizvodi -> stavke -> provizija
    public function test_apply_locks_order_products_items_commission_in_order(): void
    {
        $this->affected(paid: true);
        $this->seedRates();
        $locked = [];
        DB::listen(static function (QueryExecuted $q) use (&$locked): void {
            if (str_contains(strtolower($q->sql), 'for update') && preg_match('/from [`"]?(\w+)[`"]?/i', $q->sql, $m) === 1) {
                $locked[] = $m[1];
            }
        });

        $this->fix(['--apply' => true])->assertSuccessful();

        self::assertSame(['orders', 'products', 'order_items', 'order_commissions'], $locked);
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
