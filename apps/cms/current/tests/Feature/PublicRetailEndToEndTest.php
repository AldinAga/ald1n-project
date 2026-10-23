<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\Product;
use App\Models\PublicOrderAccess;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\PublicRetailService;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class PublicRetailEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('public_retail.enabled', true);
        config()->set('public_retail.email_receipt', false);
    }

    public function test_public_routes_are_default_closed_without_explicit_merchant_approval(): void
    {
        $this->get('/katalog')->assertNotFound();
        $this->get('/korpa')->assertNotFound();
        $this->get('/porucivanje')->assertNotFound();
        $this->get('/porudzbina/'.str_repeat('a', 64))->assertNotFound();
        self::assertSame(0, Order::query()->count());
    }

    public function test_public_catalog_only_exposes_active_and_in_stock_products(): void
    {
        $this->merchant();
        $this->product('LIST-ACTIVE', 'Laptop javni', 2);
        $this->product('LIST-PRIVATE', 'Laptop tajni', 2, 'inactive');
        $this->product('LIST-EMPTY', 'Laptop rasprodat', 0);
        $this->get('/katalog')->assertOk()->assertSee('Laptop javni')
            ->assertDontSee('Laptop tajni')->assertDontSee('Laptop rasprodat')
            ->assertDontSee('purchase_price_rsd')->assertDontSee('manual_commission_eur');
        $this->get('/katalog/list-private')->assertNotFound();
        $this->get('/catalog')->assertRedirect('/login');
    }

    public function test_guest_first_checkout_is_full_price_without_commission_or_fake_account(): void
    {
        $this->merchant();
        $product = $this->product('GUEST-PRICE', 'Računar maloprodaja', 4, 'active', 14900.0);
        $key = bin2hex(random_bytes(32));
        $payload = $this->checkoutInput($key, '1490000');
        $this->withSession([PublicRetailService::CART_KEY => [$product->id => 1],
            'public_retail_checkout_key' => $key])
            ->post('/porucivanje', $payload)->assertRedirect();
        $order = Order::query()->sole();
        self::assertSame('public_retail', $order->sales_channel);
        self::assertNull($order->user_id);
        self::assertSame('new', $order->status);
        self::assertSame(14900.0, (float) $order->subtotal_rsd);
        self::assertSame('reserved', $order->inventory_state);
        self::assertSame(3, $product->fresh()->stock_quantity);
        self::assertSame(0, OrderCommission::query()->count());
        self::assertSame(0.0, (float) $order->items()->sole()->commission_total_eur_snapshot);
        self::assertSame(-1, StockMovement::query()->sole()->quantity_change);
        self::assertSame(1, PublicOrderAccess::query()->count());
        self::assertNotSame('guest1@example.test', PublicOrderAccess::query()->sole()->getRawOriginal('guest_email'));
    }

    public function test_duplicate_checkout_key_forged_price_and_last_unit_protect_stock(): void
    {
        $this->merchant();
        $product = $this->product('SINGLE', 'Poslednji uređaj', 1);
        $key = bin2hex(random_bytes(32));
        $valid = $this->checkoutInput($key, '1200000');
        $this->withSession([PublicRetailService::CART_KEY => [$product->id => 1], 'public_retail_checkout_key' => $key])
            ->post('/porucivanje', array_replace($valid, ['expected_total_cents' => '1']))->assertSessionHasErrors('cart');
        self::assertSame(0, Order::query()->count());
        self::assertSame(1, $product->fresh()->stock_quantity);
        $this->withSession([PublicRetailService::CART_KEY => [$product->id => 1], 'public_retail_checkout_key' => $key])
            ->post('/porucivanje', $valid)->assertRedirect();
        self::assertSame(1, Order::query()->count());
        $this->withSession([PublicRetailService::CART_KEY => [$product->id => 1], 'public_retail_checkout_key' => $key])
            ->post('/porucivanje', $valid)->assertSessionHasErrors();
        self::assertSame(1, Order::query()->count());
        self::assertSame(0, $product->fresh()->stock_quantity);
    }

    public function test_capability_is_unguessable_scoped_expires_and_can_be_revoked(): void
    {
        $this->merchant();
        $product = $this->product('CAPABILITY', 'Uređaj za praćenje', 2);
        $key = bin2hex(random_bytes(32));
        $response = $this->withSession([PublicRetailService::CART_KEY => [$product->id => 1],
            'public_retail_checkout_key' => $key])->post('/porucivanje', $this->checkoutInput($key, '1200000'));
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        self::assertMatchesRegularExpression('~^https?://[^/]+/porudzbina/[a-f0-9]{64}$~', $location);
        $token = substr($location, strrpos($location, '/') + 1);
        $this->get('/porudzbina/'.$token)->assertOk()->assertSee('Uređaj za praćenje')
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->get('/porudzbina/'.str_repeat('c', 64))->assertNotFound();
        $this->get('/porudzbina/1')->assertNotFound();
        $this->post('/porudzbina/'.$token.'/opozovi-pristup')->assertRedirect('/katalog');
        $this->get('/porudzbina/'.$token)->assertNotFound();
        PublicOrderAccess::query()->update(['revoked_at' => null, 'expires_at' => now()->subMinute()]);
        $this->get('/porudzbina/'.$token)->assertNotFound();
    }

    public function test_payment_can_only_be_cash_on_delivery_or_active_bank_transfer(): void
    {
        $this->merchant();
        $product = $this->product('METHOD', 'Plaćanje', 3);
        $key = bin2hex(random_bytes(32));
        $base = $this->checkoutInput($key, '1200000');
        $this->withSession([PublicRetailService::CART_KEY => [$product->id => 1], 'public_retail_checkout_key' => $key])
            ->post('/porucivanje', array_replace($base, ['payment_method' => 'card']))->assertSessionHasErrors('payment_method');
        $this->withSession([PublicRetailService::CART_KEY => [$product->id => 1], 'public_retail_checkout_key' => $key])
            ->post('/porucivanje', array_replace($base, ['payment_method' => 'bank_transfer']))->assertSessionHasErrors('payment_method');
        self::assertSame(0, Order::query()->count());
        BankAccount::query()->create([
            'label' => 'Sintetički račun', 'recipient_name' => 'Sintetički trgovac',
            'account_number' => '160-0000000000000-00', 'account_number_display' => '160-0000000000000-00',
            'is_active' => true,
        ]);
        $this->withSession([PublicRetailService::CART_KEY => [$product->id => 1], 'public_retail_checkout_key' => $key])
            ->post('/porucivanje', array_replace($base, ['payment_method' => 'bank_transfer']))->assertRedirect();
        self::assertSame('bank_transfer', Order::query()->sole()->payment_method);
    }

    public function test_guest_document_of_another_order_never_opens_by_numeric_document_id(): void
    {
        $this->merchant();
        $product = $this->product('DOC', 'Dokument uređaj', 2);
        $key = bin2hex(random_bytes(32));
        $response = $this->withSession([PublicRetailService::CART_KEY => [$product->id => 1],
            'public_retail_checkout_key' => $key])->post('/porucivanje', $this->checkoutInput($key, '1200000'));
        $token = substr((string)$response->headers->get('Location'), -64);
        $this->get('/porudzbina/'.$token.'/dokument/99999')->assertNotFound();
    }

    private function merchant(): void
    {
        $role = Role::query()->where('slug', 'superadmin')->firstOrFail();
        User::query()->create([
            'role_id' => $role->id, 'username' => 'guest-shop-operator',
            'email' => 'operator-public@example.test', 'first_name' => 'Operator',
            'password_hash' => Hash::make('UnrelatedStrongPassword!'), 'status' => 'active',
        ]);
        app(SettingsService::class)->putMany([
            'public_retail_merchant_approved' => '1', 'public_retail_free_shipping_approved' => '1',
            'public_retail_terms_text' => 'Sintetički uslovi kupovine za CI. Ne koristiti u produkciji.',
            'documents_company_name' => 'Sintetički trgovac d.o.o.',
            'documents_company_address' => 'Test 55', 'documents_company_city' => 'Beograd',
            'documents_company_tax_id' => '100000000', 'documents_company_email' => 'info@example.test',
            'documents_company_phone' => '0110000000',
        ], null);
        Mail::fake();
    }

    private function product(string $sku, string $name, int $stock, string $status = 'active', float $price = 12000): Product
    {
        return Product::query()->create([
            'sku' => $sku, 'name' => $name, 'slug' => strtolower($sku),
            'status' => $status, 'stock_quantity' => $stock,
            'price_amount' => $price, 'price_currency' => 'RSD',
            'description' => 'Sintetički testni proizvod bez privatnih podataka.',
        ]);
    }

    /** @return array<string,string> */
    private function checkoutInput(string $key, string $price): array
    {
        return [
            'checkout_key' => $key, 'expected_total_cents' => $price,
            'shipping_full_name' => 'Sintetički kupac', 'email' => 'guest1@example.test',
            'shipping_phone' => '060111222', 'shipping_address' => 'Test 1',
            'shipping_city' => 'Beograd', 'shipping_postal_code' => '11000',
            'payment_method' => 'cash_on_delivery', 'terms_accepted' => '1',
        ];
    }
}
