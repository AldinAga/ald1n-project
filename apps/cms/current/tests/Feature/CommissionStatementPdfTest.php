<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CommissionAdjustment;
use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use App\Support\CommissionMoney;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * PDF obračun (mesečni) i PDF potvrda isplate. Scenario: kurs 117,20; SubAgent Alfa ima 20,00 EUR provizije i zaduženje 5,00 EUR,
 * SubAgent Bravo 20,00 EUR; SuperAdmin ih isplaćuje u jednoj seriji. Alfa: neto 15,00 EUR = 1.758,00 RSD; Bravo: 2.344,00 RSD.
 */
final class CommissionStatementPdfTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;
    private User $alfa;
    private User $bravo;
    private User $admin;
    private User $super;
    private int $batchId;

    protected function setUp(): void
    {
        parent::setUp();
        CommissionMoney::$adjustmentsOverride = null;
        $this->seed(CoreAccessSeeder::class);
        app(SettingsService::class)->putMany(['eur_rsd_rate' => '117.200000'], null);
        $this->alfa = $this->user('agent-alfa', 'user');
        $this->bravo = $this->user('agent-bravo', 'user');
        $this->admin = $this->user('agent-admin', 'admin');
        $this->super = $this->user('agent-super', 'superadmin');

        $alfaOrder = $this->approvedCommission($this->alfa);
        $bravoOrder = $this->approvedCommission($this->bravo);
        $old = $this->approvedCommission($this->alfa);
        $old->forceFill(['status' => 'paid', 'paid_at' => now()->subDay()])->save();
        CommissionAdjustment::query()->create([
            'commission_id' => $old->id, 'order_id' => $old->order_id, 'kind' => 'debit', 'status' => 'confirmed',
            'amount_eur' => '5.00', 'reason' => 'Povrat artikla', 'source' => 'manual', 'created_by' => $this->super->id,
        ]);
        $this->actingAs($this->super)->post('/admin/commissions/bulk-pay', [
            'commission_ids' => [$alfaOrder->id, $bravoOrder->id], 'payment_method' => 'bank_transfer',
        ])->assertSessionHasNoErrors();
        $this->batchId = (int) DB::table('commission_payment_batches')->value('id');
    }

    public function test_payout_confirmation_has_rate_debits_and_rsd_amounts(): void
    {
        self::assertSame('117.200000', DB::table('commission_payment_batches')->where('id', $this->batchId)->value('eur_rsd_rate'));
        $response = $this->actingAs($this->super)->get('/admin/commissions/payouts/'.$this->batchId.'.pdf');

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $pdf = $response->getContent();
        self::assertStringStartsWith('%PDF-1.4', $pdf);
        foreach (['ISPL-', '117,2000', '1.758,00 RSD', '2.344,00 RSD', '5,00 EUR', 'Povrat artikla', 'Agent alfa', 'Agent bravo', 'Agent super'] as $expected) {
            self::assertStringContainsString($expected, $pdf);
        }
    }

    public function test_superadmin_can_limit_the_confirmation_to_one_agent(): void
    {
        $pdf = $this->actingAs($this->super)->get('/admin/commissions/payouts/'.$this->batchId.'.pdf?user_id='.$this->bravo->id)->assertOk()->getContent();

        self::assertStringContainsString('Agent bravo', $pdf);
        self::assertStringContainsString('2.344,00 RSD', $pdf);
        self::assertStringNotContainsString('Agent alfa', $pdf);
        self::assertStringNotContainsString('Povrat artikla', $pdf);
    }

    public function test_agent_gets_only_own_confirmation_and_statement(): void
    {
        $own = $this->actingAs($this->alfa)->get('/commissions/payouts/'.$this->batchId.'.pdf?user_id='.$this->bravo->id);
        $own->assertOk()->assertHeader('content-type', 'application/pdf');
        self::assertStringContainsString('Agent alfa', $own->getContent());
        self::assertStringNotContainsString('Agent bravo', $own->getContent());

        $statement = $this->actingAs($this->alfa)->get('/commissions/statement.pdf?month='.now()->format('Y-m').'&user_id='.$this->bravo->id);
        $statement->assertOk()->assertHeader('content-type', 'application/pdf');
        self::assertStringContainsString('Agent alfa', $statement->getContent());
        self::assertStringNotContainsString('Agent bravo', $statement->getContent());
        foreach (['2.344,00 RSD', '20,00 EUR', '5,00 EUR', 'Povrat artikla', now()->format('m.Y')] as $expected) {
            self::assertStringContainsString($expected, $statement->getContent());
        }
    }

    public function test_agent_without_commission_in_batch_gets_404_and_cannot_use_admin_routes(): void
    {
        $other = $this->user('agent-charlie', 'user');
        $this->actingAs($other)->get('/commissions/payouts/'.$this->batchId.'.pdf')->assertNotFound();
        $this->actingAs($other)->get('/commissions/payouts/999999.pdf')->assertNotFound();

        foreach ([$this->alfa, $this->admin] as $actor) {
            foreach (['/admin/commissions/payouts/'.$this->batchId.'.pdf', '/admin/commissions/statement.pdf?month='.now()->format('Y-m').'&user_id='.$this->alfa->id] as $url) {
                self::assertContains($this->actingAs($actor)->get($url)->getStatusCode(), [403, 404], $actor->username.' '.$url);
            }
        }
    }

    public function test_superadmin_statement_needs_agent_and_valid_month(): void
    {
        $month = now()->format('Y-m');
        $this->actingAs($this->super)->get('/admin/commissions/statement.pdf?month='.$month)->assertStatus(422);
        $this->actingAs($this->super)->get('/admin/commissions/statement.pdf?month=2026-13&user_id='.$this->alfa->id)->assertSessionHasErrors('month');
        $pdf = $this->actingAs($this->super)->get('/admin/commissions/statement.pdf?month='.$month.'&user_id='.$this->alfa->id)
            ->assertOk()->assertHeader('content-type', 'application/pdf')->getContent();
        self::assertStringContainsString('Agent alfa', $pdf);
        self::assertStringNotContainsString('Agent bravo', $pdf);
    }

    private function approvedCommission(User $agent): OrderCommission
    {
        $n = ++$this->seq;
        $product = Product::query()->create([
            'sku' => 'CST-'.$n, 'name' => 'CST '.$n, 'slug' => 'cst-'.$n, 'price_amount' => 12000, 'price_currency' => 'RSD',
            'description' => 'Test artikal.', 'stock_quantity' => 20, 'status' => 'active', 'created_by' => $this->admin->id,
        ]);
        $supplier = $this->admin;
        $this->actingAs($agent)->post('/orders', [
            'idempotency_key' => 'cst-'.$n, 'supplier_user_id' => $supplier->id,
            'shipping_full_name' => 'Test Kupac', 'shipping_address' => 'Test adresa 1', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '060123456', 'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();
        $order = Order::query()->latest('id')->firstOrFail();
        DB::table('order_items')->where('order_id', $order->id)->update(['quantity' => 2, 'commission_unit_eur_snapshot' => '10.00', 'commission_total_eur_snapshot' => '20.00']);
        OrderCommission::query()->where('order_id', $order->id)->update(['total_eur' => '20.00', 'status' => 'approved']);

        return OrderCommission::query()->where('order_id', $order->id)->firstOrFail();
    }

    private function user(string $username, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $username,
            'email' => $username.'@example.test',
            'first_name' => ucfirst(str_replace('-', ' ', $username)),
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }
}
