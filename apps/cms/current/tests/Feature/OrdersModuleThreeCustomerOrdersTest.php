<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Module 3 (customer side of orders on Kobalt components): own order detail, payments block,
 * new order form and order amendment form, per role. Author: Ald1n <https://ald1n.com>
 */
final class OrdersModuleThreeCustomerOrdersTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_CLASSES = '/class="(?:[^"]*\s)?(?:button|button-[a-z]+|panel|admin-table|admin-table-wrap|status-badge|page-heading|form-section|detail-list|settings-grid|header-button-row|back-link|danger-zone|order-amendment-item|sticky-save-bar|supplier-choice|supplier-option|document-row|build16-[a-z0-9-]+|muted|alpha-note|order-total|payment-summary-grid|operation-timeline)(?:\s[^"]*)?"/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_customer_detail_has_the_new_structure_and_own_only_actions(): void
    {
        [$customer, , $order] = $this->scenario();

        $html = $this->root((string) $this->actingAs($customer)->get('/orders/'.$order->id)->assertOk()->getContent());

        self::assertNotSame('', $order->order_number);
        self::assertStringContainsString('data-order-user-detail-ready="1"', $html);
        self::assertSame(1, substr_count($html, '<h1 '));
        self::assertStringContainsString('>'.$order->order_number.'</h1>', $html);
        self::assertStringNotContainsString('Nazad na vrh', $html);
        self::assertSame(0, preg_match(self::OLD_CLASSES, $html), 'Old classes must be gone.');
        self::assertSame(0, preg_match('/style="[^"]*(?:#[0-9a-f]{3,8}|rgb|hsl)/i', $html));

        // Items: product, quantity and money formatting kept (2 x 12.000,00 RSD).
        self::assertStringContainsString('Stavke porudžbine', $html);
        self::assertStringContainsString('LEDGER-ITEM proizvod', $html);
        self::assertStringContainsString('12.000,00 RSD', $html);
        self::assertStringContainsString('24.000,00 RSD', $html);
        self::assertStringContainsString('data-label="Količina">2<', $html);
        self::assertMatchesRegularExpression('/<span class="a1-pill a1-pill--warning">Nova<\/span>/', $html);

        // Own actions: exactly one primary (edit), cancel in the danger card with its confirmation and CSRF.
        self::assertSame(1, substr_count($html, 'a1-btn--primary'));
        self::assertStringContainsString('href="'.route('orders.edit', $order).'"', $html);
        self::assertStringContainsString('href="'.route('orders.documents.confirmation', $order).'"', $html);
        self::assertStringContainsString('data-order-amendment-available', $html);
        self::assertMatchesRegularExpression('/<form class="a1-form" method="post" action="'.preg_quote(route('orders.cancel', $order), '/').'">\s*<input type="hidden" name="_token"/', $html);
        self::assertStringContainsString('data-confirm="Otkazati porudžbinu i vratiti lager?"', $html);
        self::assertStringContainsString('class="a1-btn a1-btn--danger"', $html);

        // Not yet delivered: no after-sales entry, no proof form for cash on delivery, nothing internal.
        self::assertStringNotContainsString('Reklamacija / servis', $html);
        self::assertStringNotContainsString('multipart/form-data', $html);
        self::assertStringContainsString('Pouzećem', $html);
        foreach (['Interna', 'nabavn', 'Marža', 'marža'] as $internal) {
            self::assertStringNotContainsString($internal, $html);
        }
    }

    public function test_bank_transfer_order_shows_the_proof_form_without_a_second_primary_action(): void
    {
        [$customer, , $order] = $this->scenario();
        $order->forceFill(['payment_method' => 'bank_transfer'])->save();

        $html = $this->root((string) $this->actingAs($customer)->get('/orders/'.$order->id)->assertOk()->getContent());

        self::assertStringContainsString('Uplata na račun', $html);
        self::assertMatchesRegularExpression('/<form class="a1-form" method="post" enctype="multipart\/form-data" action="'.preg_quote(route('orders.payments.proof.store', $order), '/').'">\s*<input type="hidden" name="_token"/', $html);
        foreach (['name="amount_rsd"', 'name="paid_at"', 'name="reference"', 'name="proof"', 'name="note"'] as $field) {
            self::assertStringContainsString($field, $html);
        }
        self::assertStringContainsString('for="oc-pay-amount"', $html);
        self::assertStringContainsString('value="24000.00"', $html);
        // The edit button already is the primary action of the screen.
        self::assertSame(1, substr_count($html, 'a1-btn--primary'));
        self::assertStringContainsString('<button class="a1-btn" type="submit">Pošalji potvrdu uplate</button>', $html);
        self::assertSame(0, preg_match(self::OLD_CLASSES, $html));
    }

    public function test_shipped_order_is_locked_and_the_proof_form_takes_the_primary_action(): void
    {
        [$customer, , $order] = $this->scenario();
        $order->forceFill(['payment_method' => 'bank_transfer', 'status' => 'shipped'])->save();

        $html = $this->root((string) $this->actingAs($customer)->get('/orders/'.$order->id)->assertOk()->getContent());

        self::assertStringContainsString('data-order-amendment-locked', $html);
        self::assertMatchesRegularExpression('/<span class="a1-pill a1-pill--info">Poslata<\/span>/', $html);
        self::assertStringNotContainsString('Uredi porudžbinu', $html);
        self::assertStringNotContainsString('href="'.route('orders.edit', $order).'"', $html);
        self::assertStringNotContainsString('action="'.route('orders.cancel', $order).'"', $html);
        self::assertStringNotContainsString('Otkaži porudžbinu', $html);
        self::assertSame(1, substr_count($html, 'a1-btn--primary'));
        self::assertStringContainsString('<button class="a1-btn a1-btn--primary" type="submit">Pošalji potvrdu uplate</button>', $html);
    }

    public function test_another_customer_cannot_open_the_order_and_guests_are_sent_to_login(): void
    {
        [$owner, , $order] = $this->scenario();
        $other = $this->person('user', 'other');

        $this->actingAs($owner)->get('/orders/'.$order->id)->assertOk();
        $this->actingAs($other)->get('/orders/'.$order->id)->assertNotFound();
        $this->actingAs($other)->get('/orders/'.$order->id.'/edit')->assertNotFound();
    }

    public function test_guests_are_sent_to_login_from_every_customer_order_page(): void
    {
        [, , $order] = $this->scenario();
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->get('/orders/'.$order->id)->assertRedirect(route('login'));
        $this->get('/order/new')->assertRedirect(route('login'));
        $this->get('/orders/'.$order->id.'/edit')->assertRedirect(route('login'));
    }

    public function test_superadmin_keeps_getting_forbidden_on_the_customer_pages(): void
    {
        [, , $order] = $this->scenario();
        $super = $this->person('superadmin', 'super');

        $this->actingAs($super)->get('/orders/'.$order->id)->assertForbidden();
        $this->actingAs($super)->get('/order/new')->assertForbidden();
        $this->actingAs($super)->get('/orders/'.$order->id.'/edit')->assertForbidden();
    }

    public function test_administrator_sees_only_own_orders_on_the_customer_detail(): void
    {
        [, , $order] = $this->scenario();
        $admin = $this->person('admin', 'second-admin');

        // Somebody else's order stays hidden, even for an administrator.
        $this->actingAs($admin)->get('/orders/'.$order->id)->assertNotFound();

        $order->forceFill(['user_id' => $admin->id])->save();
        $html = $this->root((string) $this->actingAs($admin)->get('/orders/'.$order->id)->assertOk()->getContent());

        self::assertSame(1, substr_count($html, '<h1 '));
        self::assertStringContainsString('>'.$order->order_number.'</h1>', $html);
        self::assertStringContainsString('Stavke porudžbine', $html);
        self::assertSame(0, preg_match(self::OLD_CLASSES, $html));
    }

    public function test_new_order_form_keeps_fields_hooks_script_and_has_one_primary_action(): void
    {
        [$customer, $supplier] = $this->scenario();
        $product = Product::query()->where('sku', 'LEDGER-ITEM')->firstOrFail();

        $page = (string) $this->actingAs($customer)->get('/order/new?product='.$product->id)->assertOk()->getContent();
        $html = $this->root($page);

        self::assertSame(1, substr_count($html, '<h1 '));
        self::assertStringContainsString('data-order-create-ready="1"', $html);
        self::assertStringNotContainsString('Nazad na vrh', $page);
        self::assertSame(0, preg_match(self::OLD_CLASSES, $html));
        self::assertMatchesRegularExpression('/<form method="post" action="'.preg_quote(route('orders.store'), '/').'" class="a1-form" data-order-create-form data-ux-sticky-actions>\s*<input type="hidden" name="_token"/', $html);
        self::assertMatchesRegularExpression('/name="idempotency_key" value="[0-9a-f-]{36}"/', $html);
        self::assertSame(5, substr_count($html, 'data-order-item-row'));
        self::assertStringContainsString('name="items[0][product_id]"', $html);
        self::assertStringContainsString('name="items[4][quantity]"', $html);
        self::assertStringContainsString('for="oc-item-product-0"', $html);
        self::assertMatchesRegularExpression('/<option\s+value="'.$product->id.'"\s+selected\s*>\s*LEDGER-ITEM/', $html);
        self::assertMatchesRegularExpression('/type="radio"\s+name="supplier_user_id"\s+value="'.$supplier->id.'"/', $html);
        foreach (['data-payment-method', 'data-bank-account', 'data-payment-due', 'value="deferred_payment"', 'value="bank_transfer"', 'name="payment_due_at"', 'name="bank_account_id"', 'name="customer_note"'] as $hook) {
            self::assertStringContainsString($hook, $html);
        }
        foreach (['shipping_full_name', 'shipping_address', 'shipping_city', 'shipping_postal_code', 'shipping_phone'] as $name) {
            self::assertMatchesRegularExpression('/<input class="a1-input"[^>]*name="'.$name.'" required/', $html);
        }
        self::assertSame(1, substr_count($html, 'a1-btn--primary'));
        self::assertStringContainsString('<div class="a1-actionbar" data-product-editor-action-bar>', $html);
        self::assertMatchesRegularExpression('/<script nonce="[^"]+">\s*\(\(\) => \{\s*const method = document\.querySelector\(.\[data-payment-method\].\)/', $page);
        self::assertStringContainsString('<div class="a1-field a1-oc-toggle" data-bank-account>', $html);
        self::assertStringContainsString('<div class="a1-field a1-oc-toggle" data-payment-due>', $html);
    }

    public function test_new_order_validation_errors_stay_next_to_their_fields(): void
    {
        [$customer, $supplier] = $this->scenario();
        $product = Product::query()->where('sku', 'LEDGER-ITEM')->firstOrFail();
        $payload = $this->payload($product->id, 1, 'm3-oc-invalid', $supplier->id);
        $payload['shipping_full_name'] = '';

        $page = (string) $this->actingAs($customer)->from('/order/new')->followingRedirects()->post('/orders', $payload)->getContent();
        $html = $this->root($page);

        self::assertStringContainsString('id="oc-shipping-name-err"', $html);
        self::assertStringContainsString('aria-describedby="oc-shipping-name-err"', $html);
        self::assertMatchesRegularExpression('/id="oc-shipping-name"[^>]*aria-invalid="true"/', $html);
        self::assertSame(1, Order::query()->count());
    }

    public function test_amendment_form_keeps_tokens_hooks_script_and_has_one_primary_action(): void
    {
        [$customer, , $order] = $this->scenario();

        $page = (string) $this->actingAs($customer)->get('/orders/'.$order->id.'/edit')->assertOk()->getContent();
        $html = $this->root($page);

        self::assertSame(1, substr_count($html, '<h1 '));
        self::assertStringContainsString('data-order-amendment-edit="1"', $html);
        self::assertSame(0, preg_match(self::OLD_CLASSES, $html));
        self::assertMatchesRegularExpression('/<form method="post" action="'.preg_quote(route('orders.update', $order), '/').'" class="a1-form" data-order-amendment-form>\s*<input type="hidden" name="_token"[^>]*>\s*<input type="hidden" name="_method" value="PATCH"/', $html);
        self::assertMatchesRegularExpression('/name="expected_edit_token" value="[^"]+"/', $html);
        self::assertMatchesRegularExpression('/name="idempotency_key" value="[0-9a-f-]{36}"/', $html);
        // One rendered line plus the <template> the script clones for new lines.
        self::assertSame(2, substr_count($html, 'data-order-item data-product-id='));
        self::assertSame(1, substr_count((string) preg_replace('~<template\b.*?</template>~s', '', $html), 'data-order-item data-product-id='));
        self::assertStringContainsString('LEDGER-ITEM proizvod', $html);
        foreach (['data-qty-minus', 'data-qty-plus', 'data-remove-item', 'data-qty-input', 'data-product-input', 'data-product-search', 'data-product-results', 'data-order-item-template'] as $hook) {
            self::assertStringContainsString($hook, $html);
        }
        self::assertStringContainsString('data-search-url="'.route('catalog.quick-search').'"', $html);
        self::assertStringContainsString('aria-label="Smanji količinu: LEDGER-ITEM proizvod"', $html);
        self::assertSame(1, substr_count($html, 'a1-btn--primary'));
        self::assertStringContainsString('<div class="a1-actionbar">', $html);
        self::assertMatchesRegularExpression('/<script nonce="[^"]+">\s*\(\(\) => \{\s*const root = document\.querySelector\(.\[data-order-amendment-form\].\)/', $page);
        self::assertStringNotContainsString('<style>', $html);
    }

    public function test_shipped_order_can_no_longer_be_edited(): void
    {
        [$customer, , $order] = $this->scenario();
        $order->forceFill(['status' => 'shipped'])->save();

        $this->actingAs($customer)->get('/orders/'.$order->id.'/edit')->assertStatus(409);
    }

    /** @return array{0:User,1:User,2:Order} customer, supplier administrator, the customer's new order (2 x 12.000,00 RSD) */
    private function scenario(): array
    {
        $customer = $this->person('user', 'customer');
        $supplier = $this->person('admin', 'supplier');
        $product = Product::query()->create([
            'sku' => 'LEDGER-ITEM', 'name' => 'LEDGER-ITEM proizvod', 'slug' => 'ledger-item',
            'price_amount' => 12000, 'price_currency' => 'RSD', 'description' => 'Test proizvod.',
            'stock_quantity' => 10, 'status' => 'active', 'created_by' => $customer->id,
        ]);
        $this->actingAs($customer)->post('/orders', $this->payload($product->id, 2, 'm3-oc-key-1', $supplier->id))->assertRedirect();

        return [$customer, $supplier, Order::query()->latest('id')->firstOrFail()];
    }

    /** @return array<string,mixed> */
    private function payload(int $productId, int $quantity, string $key, int $supplierId): array
    {
        return [
            'idempotency_key' => $key, 'supplier_user_id' => $supplierId,
            'shipping_full_name' => 'Test Kupac', 'shipping_address' => 'Test adresa 1', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '060123456', 'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $productId, 'quantity' => $quantity]],
        ];
    }

    private function person(string $role, string $name): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => 'm3-oc-'.$name, 'email' => 'm3-oc-'.$name.'@example.test',
            'first_name' => ucfirst($name),
            'password_hash' => Hash::make('TestOnlyM3!'), 'status' => 'active',
        ]);
    }

    /** The orders root only, without the application shell. */
    private function root(string $html): string
    {
        $start = strpos($html, 'class="a1-ops"');
        self::assertNotFalse($start, 'orders root missing');
        $end = strpos($html, '</main>', $start);

        return substr($html, $start, ($end === false ? strlen($html) : $end) - $start);
    }
}
