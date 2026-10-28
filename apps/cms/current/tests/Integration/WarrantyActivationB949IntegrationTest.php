<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\ProductWarranty;
use App\Models\Role;
use App\Models\User;
use App\Models\WarrantyActivationRecoveryAttempt;
use App\Models\WarrantyIssuance;
use App\Models\WarrantyRule;
use App\Services\OrderPaymentService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * B949: disposable MariaDB and local Mailpit only. Not production or
 * externally attested SMTP-provider acceptance. This is not a default suite.
 */
final class WarrantyActivationB949IntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // This class is NEVER part of the default test suite; it deliberately
        // migrates a disposable CI database. Fail closed before any DDL.
        if (getenv('B949_DISPOSABLE_INTEGRATION_ENABLED') !== '1'
            || !app()->environment('testing')
            || DB::connection()->getDatabaseName() !== 'ald1n_test'
            || DB::connection()->getConfig('host') !== '127.0.0.1') {
            throw new \RuntimeException('B949_DISPOSABLE_DATABASE_GATE_NOT_MET');
        }
        $this->artisan('migrate:fresh', ['--force' => true]);
        $this->seed(CoreAccessSeeder::class);
        WarrantyRule::query()->where('scope_type', 'global')->delete();
        WarrantyRule::query()->create([
            'name' => 'B949 synthetic global',
            'scope_type' => 'global',
            'duration_months' => 12,
            'duration_days' => 10,
            'is_active' => true,
            'priority' => 100,
            'terms' => 'Frozen synthetic B949 terms.',
        ]);
        config()->set('warranty_recovery.enabled', false);
        config()->set('warranty_recovery.alerts_enabled', false);
    }

    public function test_two_independent_workers_serialize_on_the_same_order_and_keep_one_certificate(): void
    {
        $order = $this->paidAndDelivered('concurrent');
        $number = WarrantyIssuance::query()->where('order_id', $order->id)->sole()->warranty_number;

        $workers = [];
        DB::beginTransaction();
        try {
            // Hold a real InnoDB row lock while two distinct PHP processes
            // reach the gated recovery command. Both must block here.
            Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            for ($i = 0; $i < 2; $i++) {
                $worker = new Process(
                    [PHP_BINARY, base_path('artisan'), 'app:warranty-activation-recover', '--execute', '--limit=1'],
                    base_path(),
                    [
                        'APP_ENV' => 'testing',
                        'WARRANTY_ACTIVATION_RECOVERY_ENABLED' => 'true',
                        'WARRANTY_ACTIVATION_RECOVERY_ALERTS_ENABLED' => 'false',
                        'MAIL_MAILER' => 'array',
                        'QUEUE_CONNECTION' => 'sync',
                    ],
                );
                $worker->setTimeout(40);
                $worker->start();
                $workers[] = $worker;
            }
            usleep(1000000);
            self::assertTrue($workers[0]->isRunning(), 'First worker did not wait for order lock.');
            self::assertTrue($workers[1]->isRunning(), 'Second worker did not wait for order lock.');
        } finally {
            DB::commit();
        }
        foreach ($workers as $worker) {
            $worker->wait();
            self::assertTrue($worker->isSuccessful(), $worker->getErrorOutput().' '.$worker->getOutput());
        }

        self::assertSame(1, ProductWarranty::query()->where('order_id', $order->id)->count());
        self::assertSame($number, ProductWarranty::query()->where('order_id', $order->id)->sole()->warranty_number);
        self::assertSame('activated', WarrantyIssuance::query()->where('order_id', $order->id)->sole()->status);
        self::assertSame('resolved', WarrantyActivationRecoveryAttempt::query()->where('order_id', $order->id)->sole()->status);
        self::assertSame(1, OrderPayment::query()->where('order_id', $order->id)->count());
    }

    public function test_two_failed_workers_are_bounded_to_one_retry_when_same_order_fails(): void
    {
        $order = $this->paidAndDelivered('concurrent-failure');
        $orderId = (int) $order->id;

        // Disposable CI MariaDB only: database-injected failure is shared by
        // both real PHP processes, unlike PHPUnit in-process model listeners.
        DB::unprepared("CREATE TRIGGER b950_recovery_fail BEFORE INSERT ON product_warranties "
            ."FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'B950_SYNTHETIC_ACTIVATION_FAILURE'");

        $workers = [];
        try {
            DB::beginTransaction();
            try {
                Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
                for ($i = 0; $i < 2; $i++) {
                    $worker = new Process(
                        [PHP_BINARY, base_path('artisan'), 'app:warranty-activation-recover', '--execute', '--limit=1'],
                        base_path(),
                        [
                            'APP_ENV' => 'testing',
                            'WARRANTY_ACTIVATION_RECOVERY_ENABLED' => 'true',
                            'WARRANTY_ACTIVATION_RECOVERY_ALERTS_ENABLED' => 'false',
                            'MAIL_MAILER' => 'array',
                            'QUEUE_CONNECTION' => 'sync',
                        ],
                    );
                    $worker->setTimeout(40);
                    $worker->start();
                    $workers[] = $worker;
                }
                usleep(1000000);
                self::assertTrue($workers[0]->isRunning(), 'First failing worker must wait for lock.');
                self::assertTrue($workers[1]->isRunning(), 'Second failing worker must wait for lock.');
            } finally {
                DB::commit();
            }

            foreach ($workers as $worker) {
                $worker->wait();
                // A pending warranty is a nonzero HOLD, not a command success.
                self::assertSame(1, $worker->getExitCode(), $worker->getErrorOutput().' '.$worker->getOutput());
            }
            self::assertSame(0, ProductWarranty::query()->where('order_id', $orderId)->count());
            self::assertSame('issued_pending_delivery',
                WarrantyIssuance::query()->where('order_id', $orderId)->sole()->status);
            $attempt = WarrantyActivationRecoveryAttempt::query()->where('order_id', $orderId)->sole();
            // A simultaneous failed event must consume one bounded retry slot,
            // not two, and the next retry must wait for the durable backoff.
            self::assertSame(1, $attempt->attempt_count);
            self::assertSame('retrying', $attempt->status);
            self::assertTrue($attempt->next_attempt_at->isFuture());
        } finally {
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop(1);
                }
            }
            DB::unprepared('DROP TRIGGER IF EXISTS b950_recovery_fail');
        }
    }

    public function test_real_local_smtp_transport_captures_only_aggregate_operator_alert(): void
    {
        $order = $this->paidAndDelivered('smtp');
        config()->set('warranty_recovery.alerts_enabled', true);
        config()->set('warranty_recovery.alert_email', 'b949-operator@example.test');
        config()->set('mail.default', 'smtp');
        config()->set('mail.from.address', 'b949-no-reply@example.test');
        config()->set('mail.from.name', 'Ald1n Synthetic CI');
        config()->set('mail.mailers.smtp', [
            'transport' => 'smtp',
            'host' => '127.0.0.1',
            'port' => 1025,
            'encryption' => null,
            'username' => null,
            'password' => null,
            'timeout' => 8,
        ]);
        Mail::purge('smtp');

        $before = Http::timeout(10)->get('http://127.0.0.1:8025/api/v1/messages')->throw()->json('messages', []);
        self::assertIsArray($before);
        self::assertSame(0, count($before), 'Mailpit must start with an empty disposable mailbox.');

        // The actual Artisan command sends over SMTP, with no Mail::fake().
        $rc = Artisan::call('app:warranty-activation-recover', ['--alert' => true]);
        self::assertSame(1, $rc, 'Pending warranty must remain HOLD despite SMTP delivery.');
        self::assertStringContainsString('ALERT_DISPATCH_REQUESTED', Artisan::output());

        $messages = Http::timeout(10)->get('http://127.0.0.1:8025/api/v1/messages')->throw()->json('messages', []);
        self::assertCount(1, $messages);
        $mail = $messages[0];
        self::assertSame('Ald1n warranty recovery HOLD', $mail['Subject'] ?? null);
        self::assertSame('b949-operator@example.test', $mail['To'][0]['Address'] ?? null);
        self::assertStringContainsString('Pending orders: 1', (string) ($mail['Snippet'] ?? ''));
        self::assertStringNotContainsString($order->order_number, json_encode($mail, JSON_THROW_ON_ERROR));
        self::assertSame(1, WarrantyIssuance::query()->where('order_id', $order->id)->where('status', 'issued_pending_delivery')->count());
        self::assertSame(0, ProductWarranty::query()->where('order_id', $order->id)->count());
    }

    private function paidAndDelivered(string $suffix): Order
    {
        $seller = User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1,
            'username' => 'b949-seller-'.$suffix,
            'email' => 'b949-seller-'.$suffix.'@example.test',
            'first_name' => 'Synthetic',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
        $admin = User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'b949-admin-'.$suffix,
            'email' => 'b949-admin-'.$suffix.'@example.test',
            'first_name' => 'Synthetic',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'sku' => 'B949-'.$suffix,
            'name' => 'Synthetic product '.$suffix,
            'slug' => 'b949-'.$suffix,
            'price_amount' => 12000,
            'price_currency' => 'RSD',
            'description' => 'Test only.',
            'stock_quantity' => 10,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
        $order = Order::query()->create([
            'source_system' => 'laravel',
            'order_number' => 'B949-'.$suffix,
            'user_id' => $seller->id,
            'supplier_user_id' => $admin->id,
            'supplier_name_snapshot' => 'Synthetic Admin',
            'supplier_role_snapshot' => 'Administrator',
            'status' => 'confirmed',
            'inventory_state' => 'reserved',
            'shipping_full_name' => 'Synthetic Buyer',
            'shipping_address' => 'Test 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '0601111222',
            'subtotal_rsd' => 12000,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'payment_state' => 'unpaid',
            'paid_total_rsd' => 0,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price_original' => 12000,
            'original_currency' => 'RSD',
            'unit_price_rsd' => 12000,
            'line_total_rsd' => 12000,
        ]);
        $payment = app(OrderPaymentService::class)->record($order, [
            'entry_type' => 'payment',
            'amount_rsd' => 1000,
            'payment_method' => 'bank_transfer',
            'paid_at' => now()->toDateTimeString(),
        ], $admin);
        self::assertSame('verified', $payment->status);
        self::assertSame('issued_pending_delivery', WarrantyIssuance::query()->where('order_id', $order->id)->sole()->status);
        $order->update(['status' => 'shipped', 'completed_at' => now()]);
        OrderDelivery::query()->create([
            'order_id' => $order->id,
            'delivery_method' => 'own_transport',
            'delivered_at' => now()->subDays(2),
            'recipient_name' => 'Synthetic Buyer',
            'confirmed_by' => $admin->id,
        ]);
        return $order->fresh(['delivery', 'user']);
    }
}
