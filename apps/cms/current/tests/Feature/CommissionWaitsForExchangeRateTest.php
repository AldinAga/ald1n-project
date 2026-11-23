<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\CommissionEligibilityService;
use App\Services\ExchangeRateService;
use App\Services\OrderPaymentService;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

/**
 * Audit 23.11.2026: ručna EUR provizija za RSD artikal bez postavljenog kursa nije smela da postane 0,00 i da se
 * automatski odobri. Porudžbina ostaje na čekanju sa upozorenjem dok se kurs ne postavi, pa se oslobađa.
 */
final class CommissionWaitsForExchangeRateTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
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

    /** Porudžbina od 1 komada RSD artikla (12.000 RSD) sa ručnom provizijom 20,00 EUR, kroz pravi tok narudžbine. */
    private function placeManualRsdOrder(User $sub, User $admin): Order
    {
        $n = ++$this->seq;
        $product = Product::query()->create([
            'sku' => 'XR-'.$n, 'name' => 'XR '.$n, 'slug' => 'xr-'.$n, 'price_amount' => 12000, 'price_currency' => 'RSD',
            'manual_commission_eur' => 20, 'description' => 'Test artikal.', 'stock_quantity' => 20, 'status' => 'active',
            'created_by' => $admin->id,
        ]);
        $this->actingAs($sub)->post('/orders', [
            'idempotency_key' => 'xr-'.$n, 'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Test Kupac', 'shipping_address' => 'Test adresa 1', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '060123456', 'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();

        return Order::query()->latest('id')->firstOrFail();
    }

    private function pay(Order $order, User $admin): void
    {
        app(OrderPaymentService::class)->record($order->fresh(), [
            'amount_rsd' => (float) $order->fresh()->subtotal_rsd, 'payment_method' => 'bank_transfer', 'paid_at' => now(),
        ], $admin);
    }

    public function test_manual_eur_commission_without_rate_stays_pending_with_warning_until_rate_is_set(): void
    {
        $sub = $this->user('sub-xr', 'user');
        $admin = $this->user('adm-xr', 'admin');
        $owner = $this->user('owner-xr', 'superadmin');
        self::assertNull(app(SettingsService::class)->eurRsdRate(), 'kurs nije postavljen');

        $order = $this->placeManualRsdOrder($sub, $admin);
        $commission = OrderCommission::query()->where('order_id', $order->id)->firstOrFail();
        self::assertNull($order->eur_rsd_rate);
        self::assertSame('20.00', $commission->total_eur, 'ručni iznos u EUR ne smije postati 0,00');
        self::assertSame('manual', DB::table('order_items')->where('order_id', $order->id)->value('commission_source_snapshot'));

        $this->pay($order, $admin);

        $commission = $commission->fresh();
        self::assertSame('paid', $order->fresh()->payment_state, 'porudžbina je u potpunosti plaćena');
        self::assertSame('pending', $commission->status, 'bez kursa se ne odobrava automatski');
        $check = app(CommissionEligibilityService::class)->evaluate($commission);
        self::assertSame('waiting_exchange_rate', $check['code']);
        self::assertFalse($check['payable']);
        self::assertFalse(app(CommissionEligibilityService::class)->orderMeetsCondition($order->fresh()));
        $scope = static fn (string $state): bool => app(CommissionEligibilityService::class)
            ->scopeState(OrderCommission::query()->whereKey($commission->id), $state)->exists();
        self::assertFalse($scope('due'));
        self::assertTrue($scope('not_due'));
        $this->actingAs($owner)->get(route('admin.commissions.index'))->assertOk()->assertSee('čeka se kurs EUR/RSD', false);

        self::assertTrue(app(ExchangeRateService::class)->saveManual(117.0, $owner->id));

        self::assertSame('117.000000', $order->fresh()->eur_rsd_rate, 'kurs se upisuje na oslobođenu porudžbinu, da RSD izveštaji ne računaju 0');
        $commission = $commission->fresh();
        self::assertSame('approved', $commission->status, 'čim kurs postoji, plaćena provizija postaje za isplatu');
        self::assertSame('20.00', $commission->total_eur);
        self::assertTrue($scope('approved'));
    }

    public function test_failed_release_is_visible_and_retried_later(): void
    {
        $sub = $this->user('sub-xf', 'user');
        $admin = $this->user('adm-xf', 'admin');
        $owner = $this->user('owner-xf', 'superadmin');
        $order = $this->placeManualRsdOrder($sub, $admin);
        $this->pay($order, $admin);
        $key = 'eloquent.updating: '.OrderCommission::class;
        Event::listen($key, static function (): never {
            throw new RuntimeException('simulirani greška');
        });
        try {
            $released = app(ExchangeRateService::class)->saveManual(117.0, $owner->id);
        } finally {
            Event::forget($key);
        }

        self::assertFalse($released);
        self::assertSame('pending', OrderCommission::query()->where('order_id', $order->id)->firstOrFail()->status);
        self::assertStringContainsString('nisu oslobođene', (string) app(ExchangeRateService::class)->configuration()['last_error']);
        self::assertTrue(DB::table('audit_logs')->where('action', 'exchange_rate.commission_release_failed')->exists());

        $this->artisan('exchange-rate:update')->assertSuccessful();

        self::assertSame('approved', OrderCommission::query()->where('order_id', $order->id)->firstOrFail()->status, 'ponovni pokušaj oslobađa proviziju');
    }

    public function test_control_same_order_with_rate_is_approved_on_payment(): void
    {
        app(SettingsService::class)->putMany(['eur_rsd_rate' => '117.000000'], null);
        $order = $this->placeManualRsdOrder($this->user('sub-xc', 'user'), $admin = $this->user('adm-xc', 'admin'));
        self::assertSame('117.000000', $order->eur_rsd_rate);

        $this->pay($order, $admin);

        $commission = OrderCommission::query()->where('order_id', $order->id)->firstOrFail();
        self::assertSame('approved', $commission->status);
        self::assertSame('20.00', $commission->total_eur);
    }

    public function test_automatic_commission_order_is_not_held_without_rate(): void
    {
        // Zadržavaju se samo stavke sa ručnom provizijom; ostalo ponašanje je nepromijenjeno.
        $admin = $this->user('adm-xa', 'admin');
        $order = $this->placeManualRsdOrder($this->user('sub-xa', 'user'), $admin);
        DB::table('order_items')->where('order_id', $order->id)->update(['commission_source_snapshot' => 'automatic']);

        $this->pay($order, $admin);

        self::assertSame('approved', OrderCommission::query()->where('order_id', $order->id)->firstOrFail()->status);
    }
}
