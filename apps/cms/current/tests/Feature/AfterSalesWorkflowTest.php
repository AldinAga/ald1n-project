<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AfterSalesAttachment;
use App\Models\AfterSalesCase;
use App\Models\AfterSalesMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AfterSalesWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        Storage::fake('local');
    }

    public function test_customer_opens_case_admin_processes_it_and_attachment_stays_private(): void
    {
        $customer = $this->user('claim-customer', 'user');
        $otherCustomer = $this->user('other-customer', 'user');
        $admin = $this->user('claim-admin', 'admin');
        $product = $this->product($admin, 'CLAIM-ITEM');

        $this->actingAs($customer)->post('/orders', $this->orderPayload($product, $admin, 'claim-order'))->assertRedirect();
        $order = Order::query()->with('items')->sole();
        $order->forceFill(['status' => 'shipped', 'completed_at' => now(), 'completed_by' => $admin->id])->save();
        $item = $order->items->firstOrFail();

        $response = $this->actingAs($customer)->post('/orders/'.$order->id.'/after-sales', [
            'case_type' => 'complaint',
            'priority' => 'high',
            'subject' => 'Oštećen naslon nakon isporuke',
            'description' => 'Na desnoj strani naslona postoji vidljivo oštećenje primećeno odmah nakon isporuke.',
            'requested_resolution' => 'repair',
            'items' => [
                $item->id => ['selected' => '1', 'quantity' => 1, 'issue_description' => 'Oštećenje na desnom uglu naslona.'],
            ],
            'attachments' => [UploadedFile::fake()->image('ostecenje.jpg', 900, 700)],
        ]);

        $case = AfterSalesCase::query()->with(['items', 'attachments'])->sole();
        $response->assertRedirect('/after-sales/'.$case->id)->assertSessionHas('status');
        self::assertStringStartsWith('PS-', $case->case_number);
        self::assertSame($admin->id, $case->assigned_to);
        self::assertSame('open', $case->status);
        self::assertCount(1, $case->items);
        self::assertCount(1, $case->attachments);
        Storage::disk('local')->assertExists($case->attachments->first()->path);

        $attachment = AfterSalesAttachment::query()->sole();
        $this->actingAs($customer)->get('/after-sales/attachments/'.$attachment->id)
            ->assertOk()
            ->assertHeader('Cache-Control', 'private, no-store, max-age=0');
        $this->actingAs($admin)->get('/after-sales/attachments/'.$attachment->id)->assertOk();
        $this->actingAs($otherCustomer)->get('/after-sales/attachments/'.$attachment->id)->assertNotFound();

        $this->actingAs($admin)->post('/admin/after-sales/'.$case->id.'/messages', [
            'visibility' => 'internal',
            'body' => 'Interna fotografija za procenu servisera.',
            'attachments' => [UploadedFile::fake()->image('interna-procena.jpg', 600, 400)],
        ])->assertRedirect()->assertSessionHas('status');
        $internalAttachment = AfterSalesAttachment::query()->whereNotNull('message_id')->latest('id')->firstOrFail();
        $this->actingAs($admin)->get('/after-sales/attachments/'.$internalAttachment->id)->assertOk();
        $this->actingAs($customer)->get('/after-sales/attachments/'.$internalAttachment->id)->assertNotFound();

        $this->actingAs($admin)->post('/admin/after-sales/'.$case->id.'/messages', [
            'visibility' => 'public',
            'body' => 'Reklamacija je preuzeta. Organizujemo pregled artikla.',
        ])->assertRedirect()->assertSessionHas('status');

        $this->actingAs($admin)->patch('/admin/after-sales/'.$case->id, [
            'status' => 'under_review',
            'priority' => 'high',
            'assigned_to' => $admin->id,
            'due_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'resolution_type' => 'inspection',
            'resolution_summary' => null,
            'note' => 'Zakazan pregled kod kupca.',
        ])->assertRedirect()->assertSessionHas('status');

        $updated = $case->fresh();
        self::assertSame('under_review', $updated->status);
        self::assertNotNull($updated->first_response_at);
        self::assertSame(1, AfterSalesMessage::query()->where('visibility', 'public')->count());
    }

    public function test_case_requires_delivered_order_and_closed_case_rejects_new_messages(): void
    {
        $customer = $this->user('blocked-claim-customer', 'user');
        $admin = $this->user('blocked-claim-admin', 'admin');
        $product = $this->product($admin, 'BLOCKED-CLAIM');

        $this->actingAs($customer)->post('/orders', $this->orderPayload($product, $admin, 'blocked-claim-order'))->assertRedirect();
        $order = Order::query()->with('items')->sole();
        $item = $order->items->firstOrFail();

        $this->actingAs($customer)->post('/orders/'.$order->id.'/after-sales', [
            'case_type' => 'service', 'priority' => 'normal', 'subject' => 'Servisni zahtev',
            'description' => 'Ovaj zahtev ne sme biti otvoren pre isporuke porudžbine.',
            'items' => [$item->id => ['selected' => '1', 'quantity' => 1]],
        ])->assertSessionHasErrors('order');

        $case = AfterSalesCase::query()->create([
            'case_number' => 'PS-TEST-000001', 'order_id' => $order->id, 'opened_by' => $customer->id,
            'assigned_to' => $admin->id, 'case_type' => 'service', 'priority' => 'normal', 'status' => 'closed',
            'subject' => 'Zatvoren slučaj', 'description' => 'Test zatvorenog slučaja.', 'closed_at' => now(), 'closed_by' => $admin->id,
        ]);

        $this->actingAs($customer)->post('/after-sales/'.$case->id.'/messages', ['body' => 'Nova poruka'])->assertSessionHasErrors('body');
    }

    public function test_closing_preserves_resolution_time_and_reopening_requires_a_reason(): void
    {
        $customer = $this->user('resolution-customer', 'user');
        $admin = $this->user('resolution-admin', 'admin');
        $superadmin = $this->user('resolution-superadmin', 'superadmin');
        $product = $this->product($admin, 'RESOLUTION-ITEM');

        $this->actingAs($customer)->post('/orders', $this->orderPayload($product, $admin, 'resolution-order'))->assertRedirect();
        $order = Order::query()->with('items')->sole();
        $order->forceFill(['status' => 'shipped', 'completed_at' => now(), 'completed_by' => $admin->id])->save();

        $case = AfterSalesCase::query()->create([
            'case_number' => 'PS-TEST-000002', 'order_id' => $order->id, 'opened_by' => $customer->id,
            'assigned_to' => $admin->id, 'case_type' => 'complaint', 'priority' => 'normal', 'status' => 'resolved',
            'subject' => 'Rešen slučaj', 'description' => 'Rešenje je potvrđeno i slučaj treba zatvoriti.',
            'resolution_type' => 'repair', 'resolution_summary' => 'Artikal je popravljen.', 'resolved_at' => now()->subHour(),
        ]);
        $resolvedAt = $case->resolved_at?->format('Y-m-d H:i:s');

        $this->actingAs($admin)->patch('/admin/after-sales/'.$case->id, [
            'status' => 'closed', 'priority' => 'normal', 'assigned_to' => $admin->id,
            'resolution_type' => 'repair', 'resolution_summary' => 'Artikal je popravljen i kupac je potvrdio prijem.',
            'note' => 'Slučaj je konačno zatvoren.',
        ])->assertRedirect()->assertSessionHas('status');

        self::assertSame($resolvedAt, $case->fresh()->resolved_at?->format('Y-m-d H:i:s'));

        $this->actingAs($superadmin)->patch('/admin/after-sales/'.$case->id, [
            'status' => 'under_review', 'priority' => 'normal', 'assigned_to' => $admin->id,
            'resolution_type' => 'repair', 'resolution_summary' => 'Artikal je popravljen i kupac je potvrdio prijem.',
            'note' => null,
        ])->assertSessionHasErrors('note');

        $this->actingAs($superadmin)->patch('/admin/after-sales/'.$case->id, [
            'status' => 'under_review', 'priority' => 'normal', 'assigned_to' => $admin->id,
            'resolution_type' => 'repair', 'resolution_summary' => 'Artikal je popravljen i kupac je potvrdio prijem.',
            'note' => 'Kupac je dostavio novu fotografiju i potrebna je dodatna provera.',
        ])->assertRedirect()->assertSessionHas('status');

        self::assertSame('under_review', $case->fresh()->status);
        self::assertNull($case->fresh()->resolved_at);
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

    private function product(User $creator, string $sku): Product
    {
        return Product::query()->create([
            'sku' => $sku, 'name' => $sku.' proizvod', 'slug' => strtolower($sku),
            'price_amount' => 35000, 'price_currency' => 'RSD', 'description' => 'Test proizvod.',
            'stock_quantity' => 3, 'low_stock_threshold' => 1, 'status' => 'active', 'created_by' => $creator->id,
        ]);
    }

    /** @return array<string,mixed> */
    private function orderPayload(Product $product, User $supplier, string $key): array
    {
        return [
            'idempotency_key' => $key, 'supplier_user_id' => $supplier->id,
            'shipping_full_name' => 'Krajnji Kupac', 'shipping_address' => 'Adresa 10',
            'shipping_city' => 'Beograd', 'shipping_postal_code' => '11000', 'shipping_phone' => '060111222',
            'payment_method' => 'cash_on_delivery', 'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];
    }
}
