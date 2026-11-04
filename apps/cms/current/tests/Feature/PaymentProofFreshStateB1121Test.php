<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Order, OrderPayment, Product, Role, User};
use App\Services\OrderPaymentService;
use Closure;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Hash, Http, Mail, Notification, Storage};
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use Throwable;

final class PaymentProofFreshStateB1121Test extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private User $operator;
    private User $foreign;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed(CoreAccessSeeder::class);
        Notification::fake();
        Mail::fake();
        Storage::fake('local');
        $this->buyer = $this->user('buyer', 'user');
        $this->operator = $this->user('operator', 'admin');
        $this->foreign = $this->user('foreign', 'user');
        $product = Product::query()->create([
            'sku' => 'B1121-PROOF', 'slug' => 'b1121-proof', 'name' => 'B1121 proof fixture',
            'description' => 'Disposable CI only', 'price_amount' => 12000, 'price_currency' => 'RSD',
            'stock_quantity' => 3, 'status' => 'active', 'created_by' => $this->operator->id,
        ]);
        $this->order = Order::query()->create([
            'source_system' => 'laravel', 'order_number' => 'APC-B1121-PROOF',
            'user_id' => $this->buyer->id, 'supplier_user_id' => $this->operator->id,
            'supplier_name_snapshot' => $this->operator->displayName(), 'supplier_role_snapshot' => 'Administrator',
            'status' => 'new', 'inventory_state' => 'reserved',
            'shipping_full_name' => 'B1121 Synthetic Buyer', 'shipping_address' => 'Synthetic Street 1',
            'shipping_city' => 'Beograd', 'shipping_postal_code' => '11000', 'shipping_phone' => '0600000000',
            'subtotal_rsd' => 12000, 'payment_method' => 'bank_transfer',
            'payment_status' => 'pending', 'payment_state' => 'unpaid', 'paid_total_rsd' => 0,
        ]);
        $this->order->items()->create([
            'product_id' => $product->id, 'product_sku' => $product->sku, 'product_name' => $product->name,
            'quantity' => 1, 'unit_price_original' => 12000, 'original_currency' => 'RSD',
            'unit_price_rsd' => 12000, 'line_total_rsd' => 12000,
        ]);
    }

    public static function changedStates(): array
    {
        return [
            'cancelled during upload' => ['cancelled', 422],
            'owner changed during upload' => ['owner', 404],
            'payment method changed during upload' => ['method', 422],
            'completed during upload remains forbidden' => ['completed', 422],
            'direct sale during upload remains forbidden' => ['direct-sale', 422],
        ];
    }

    #[DataProvider('changedStates')]
    public function test_fresh_locked_upload_boundary_rejects_and_removes_just_stored_file(string $change, int $status): void
    {
        $beforeTransaction = null;
        $storedPath = null;
        $file = $this->watchedUpload(function (string $path) use ($change, &$beforeTransaction, &$storedPath): void {
            $storedPath = $path;
            Storage::disk('local')->assertExists($path);
            Order::query()->whereKey($this->order->id)->update($this->changes($change));
            $beforeTransaction = $this->snapshot();
        });
        $caught = null;
        try {
            app(OrderPaymentService::class)->submitProof($this->order, $file, $this->data(), $this->buyer);
        } catch (Throwable $exception) {
            $caught = $exception;
        }

        self::assertNotNull($storedPath, 'The real private upload must finish before the order changes.');
        self::assertSame('new', $this->order->status, 'Caller intentionally retains its stale order.');
        self::assertSame('bank_transfer', $this->order->payment_method);
        self::assertSame((int) $this->buyer->id, (int) $this->order->user_id);
        $this->assertRejection($caught, $status);
        Storage::disk('local')->assertMissing($storedPath);
        self::assertSame([], Storage::disk('local')->allFiles());
        self::assertSame($beforeTransaction, $this->snapshot(), 'No financial, audit, numbering, stock or delivery changes may survive.');
        Notification::assertNothingSent();
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public static function initiallyForbiddenStates(): array
    {
        return [
            'already cancelled' => ['cancelled', 422],
            'already foreign owner' => ['owner', 404],
            'already non-bank method' => ['method', 422],
        ];
    }

    #[DataProvider('initiallyForbiddenStates')]
    public function test_initial_rejection_never_stores_a_file(string $change, int $status): void
    {
        $this->order->update($this->changes($change));
        $before = $this->snapshot();
        $stored = false;
        $file = $this->watchedUpload(static function () use (&$stored): void { $stored = true; });
        $caught = null;
        try {
            app(OrderPaymentService::class)->submitProof($this->order, $file, $this->data(), $this->buyer);
        } catch (Throwable $exception) {
            $caught = $exception;
        }
        $this->assertRejection($caught, $status);
        self::assertFalse($stored);
        self::assertSame([], Storage::disk('local')->allFiles());
        self::assertSame($before, $this->snapshot());
        Notification::assertNothingSent();
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_unchanged_owned_bank_transfer_still_keeps_one_private_submitted_proof(): void
    {
        $storedPath = null;
        $file = $this->watchedUpload(static function (string $path) use (&$storedPath): void { $storedPath = $path; });
        $payment = app(OrderPaymentService::class)->submitProof($this->order, $file, $this->data(), $this->buyer);

        self::assertSame('submitted', $payment->status);
        self::assertSame('payment', $payment->entry_type);
        self::assertSame((int) $this->order->id, (int) $payment->order_id);
        self::assertSame((int) $this->buyer->id, (int) $payment->submitted_by);
        self::assertSame($storedPath, $payment->proof_path);
        self::assertSame('proof.pdf', $payment->proof_original_name);
        Storage::disk('local')->assertExists($storedPath);
        self::assertSame([$storedPath], Storage::disk('local')->allFiles());
        self::assertDatabaseCount('order_payments', 1);
        self::assertSame(1, DB::table('audit_logs')->where('action', 'order.payment_proof_submitted')->count());
        self::assertSame('new', $this->order->fresh()->status);
        self::assertSame(0.0, (float) $this->order->fresh()->paid_total_rsd);
        self::assertDatabaseCount('warranty_issuances', 0);
    }

    public function test_preexisting_real_payment_proof_can_still_be_verified_after_cancellation_once(): void
    {
        $payment = OrderPayment::query()->create([
            'order_id' => $this->order->id, 'payment_number' => 'B1121-EXISTING',
            'entry_type' => 'payment', 'status' => 'submitted', 'amount_rsd' => 1000,
            'payment_method' => 'bank_transfer', 'paid_at' => now(), 'submitted_by' => $this->buyer->id,
        ]);
        $this->order->update(['status' => 'cancelled', 'inventory_state' => 'returned']);
        $stock = DB::table('products')->value('stock_quantity');
        $service = app(OrderPaymentService::class);
        $verified = $service->verify($payment, $this->operator);
        $at = $verified->verified_at->toDateTimeString();
        $again = $service->verify($payment->fresh(), $this->operator);

        self::assertSame('verified', $again->status);
        self::assertSame($at, $again->verified_at->toDateTimeString());
        self::assertSame('cancelled', $this->order->fresh()->status);
        self::assertSame('cancelled', $this->order->fresh()->payment_state);
        self::assertSame('cancelled', $this->order->fresh()->payment_status);
        self::assertSame(1000.0, (float) $this->order->fresh()->paid_total_rsd);
        self::assertNull($this->order->fresh()->payment_verified_at);
        self::assertSame('returned', $this->order->fresh()->inventory_state);
        self::assertSame($stock, DB::table('products')->value('stock_quantity'));
        self::assertDatabaseCount('order_payments', 1);
        self::assertDatabaseCount('warranty_issuances', 0);
        self::assertDatabaseCount('product_warranties', 0);
        self::assertSame(1, DB::table('audit_logs')->where('action', 'order.payment_verified')->count());
    }

    private function assertRejection(?Throwable $exception, int $status): void
    {
        if ($status === 404) {
            self::assertInstanceOf(HttpException::class, $exception);
            self::assertSame(404, $exception->getStatusCode());
        } else {
            self::assertInstanceOf(ValidationException::class, $exception);
            self::assertArrayHasKey('proof', $exception->errors());
        }
    }

    private function changes(string $state): array
    {
        return match ($state) {
            'cancelled' => ['status' => 'cancelled'],
            'owner' => ['user_id' => $this->foreign->id],
            'method' => ['payment_method' => 'cash_on_delivery'],
            'completed' => ['completed_at' => now()],
            'direct-sale' => ['sales_channel' => 'direct_sale'],
        };
    }

    private function watchedUpload(Closure $afterStore): UploadedFile
    {
        $file = UploadedFile::fake()->createWithContent('proof.pdf', "%PDF-1.4\nB1121 disposable proof\n%%EOF\n");
        return new class($file, $afterStore) extends UploadedFile {
            public function __construct(private readonly UploadedFile $source, private readonly Closure $afterStore)
            {
                parent::__construct($source->getPathname(), $source->getClientOriginalName(), $source->getClientMimeType(), UPLOAD_ERR_OK, true);
            }

            public function storeAs($path, $name = null, $options = [])
            {
                $stored = parent::storeAs($path, $name, $options);
                ($this->afterStore)($stored);
                return $stored;
            }
        };
    }

    private function user(string $name, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => 'b1121-'.$name, 'email' => 'b1121-'.$name.'@example.test',
            'password_hash' => Hash::make('DisposableOnlyB1121!'), 'status' => 'active',
        ]);
    }

    private function data(): array
    {
        return ['amount_rsd' => 1000, 'paid_at' => now()->toDateTimeString(), 'reference' => 'B1121 synthetic proof'];
    }

    private function snapshot(): array
    {
        $state = [];
        foreach ([
            'orders', 'order_items', 'products', 'stock_movements', 'order_commissions',
            'order_status_history', 'commission_status_history', 'order_payments', 'audit_logs',
            'notifications', 'order_email_outbox', 'mobile_push_outbox', 'product_warranties',
            'warranty_issuances', 'document_counters',
        ] as $table) {
            $state[$table] = DB::table($table)->orderBy('id')->get()->map(static fn (object $row): array => (array) $row)->all();
        }
        return $state;
    }
}
