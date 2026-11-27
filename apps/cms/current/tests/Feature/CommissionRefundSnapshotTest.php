<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CommissionAdjustment;
use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\OrderPayment;
use App\Models\User;
use App\Services\OrderPaymentService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CommissionRefundSnapshotTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            self::markTestSkipped('Test zahteva dve MariaDB konekcije.');
        }

        RefreshDatabaseState::$migrated = false;
        $this->refreshTestDatabase();

        // Dve konekcije zahtevaju potvrđene podatke, a šema uključuje nepovratne migracije.
        $this->beforeApplicationDestroyed(function (): void {
            $this->refreshTestDatabase();
            RefreshDatabaseState::$migrated = true;
        });
    }

    public function test_refund_sees_manual_debit_committed_after_its_transaction_snapshot(): void
    {
        $this->seed(CoreAccessSeeder::class);
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $order = Order::query()->create([
            'order_number' => 'REFUND-SNAPSHOT', 'user_id' => $owner->id, 'supplier_user_id' => $actor->id,
            'status' => 'confirmed', 'shipping_full_name' => 'Test kupac', 'shipping_address' => 'Test adresa 1',
            'shipping_city' => 'Beograd', 'shipping_postal_code' => '11000', 'shipping_phone' => '060123456',
            'subtotal_rsd' => '12000.00', 'payment_method' => 'bank_transfer', 'payment_status' => 'paid',
            'payment_state' => 'paid', 'paid_total_rsd' => '12000.00',
        ]);
        $commission = OrderCommission::query()->create([
            'order_id' => $order->id, 'user_id' => $owner->id, 'total_eur' => '20.00', 'status' => 'paid',
        ]);
        OrderPayment::query()->create([
            'order_id' => $order->id, 'payment_number' => 'PAY-SNAPSHOT', 'entry_type' => 'payment',
            'status' => 'verified', 'amount_rsd' => '12000.00', 'payment_method' => 'bank_transfer',
            'paid_at' => now(), 'verified_at' => now(), 'submitted_by' => $actor->id, 'verified_by' => $actor->id,
        ]);

        $reader = DB::connection();
        $writerName = 'commission_snapshot_writer';
        config(['database.connections.'.$writerName => array_replace($reader->getConfig(), ['name' => $writerName])]);
        $writer = DB::connection($writerName);
        try {
            $reader->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $reader->transaction(function () use ($writer, $order, $commission, $actor): void {
                self::assertSame(0, CommissionAdjustment::query()->where('commission_id', $commission->id)->count());

                $writer->transaction(function () use ($writer, $order, $commission, $actor): void {
                    $writer->table('orders')->where('id', $order->id)->lockForUpdate()->first();
                    $writer->table('order_commissions')->where('id', $commission->id)->lockForUpdate()->first();
                    $writer->table('commission_adjustments')->insert([
                        'commission_id' => $commission->id, 'order_id' => $order->id, 'kind' => 'debit',
                        'status' => 'confirmed', 'amount_eur' => '15.00', 'source' => 'manual',
                        'reason' => 'Ručno zaduženje', 'created_by' => $actor->id,
                    ]);
                });

                self::assertSame(0, CommissionAdjustment::query()->where('commission_id', $commission->id)->count());
                app(OrderPaymentService::class)->record($order, [
                    'entry_type' => 'refund', 'amount_rsd' => 12000, 'payment_method' => 'bank_transfer', 'paid_at' => now(),
                ], $actor);
            });

            self::assertSame(['15.00', '5.00'], CommissionAdjustment::query()
                ->where('commission_id', $commission->id)->orderBy('id')->pluck('amount_eur')->all());
        } finally {
            DB::purge($writerName);
        }
    }
}
