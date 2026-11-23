<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AfterSalesAction;
use App\Models\AfterSalesCase;
use App\Models\AfterSalesCaseItem;
use App\Models\AfterSalesMessage;
use App\Models\AfterSalesStatusHistory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Module 3 (after-sales pages on Kobalt components): what each role sees and can do on the customer
 * and admin sets of routes, and the markup contract. Author: Ald1n <https://ald1n.com>
 */
final class OrdersModuleThreeAfterSalesTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_CLASSES = 'panel|button(?:-[a-z]+)?|status-badge|priority-badge|page-heading|admin-table(?:-wrap)?|filter-panel|filter-grid|form-section|settings-grid|operational-order-grid|order-main-column|form-side|detail-list(?:\s+compact)?|back-link|eyebrow|muted|after-sales-[a-z-]+|action-[a-z_-]+|timeline-[a-z-]+|operation-timeline|inline-action-form|full-width|form-grid|two-columns|row-overdue|success-callout|danger-text|header-button-row|section-heading-row|checkbox-inline|filter-actions|customer-field-work-order|field-work-order-inline|ui-icon';

    private User $customer;
    private User $admin;
    private User $superadmin;
    private Order $order;
    private AfterSalesCase $case;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);

        $this->customer = $this->person('customer', 'user', 1);
        $this->admin = $this->person('admin', 'admin');
        $this->superadmin = $this->person('superadmin', 'superadmin');

        $this->order = Order::query()->create([
            'source_system' => 'laravel', 'order_number' => 'M3-OS-ORDER-001', 'user_id' => $this->customer->id, 'supplier_user_id' => $this->admin->id,
            'status' => 'new', 'shipping_full_name' => 'Kupac Probni', 'shipping_address' => 'Probna ulica 1',
            'shipping_city' => 'Beograd', 'shipping_postal_code' => '11000', 'shipping_phone' => '0600000000',
            'subtotal_rsd' => 15000, 'paid_total_rsd' => 0,
        ]);
        $this->order->forceFill(['status' => 'shipped', 'completed_at' => now(), 'completed_by' => $this->admin->id])->save();
        $item = $this->orderItem($this->order, 'Probni laptop M3', 15000.0);

        $this->case = AfterSalesCase::query()->create([
            'case_number' => 'PS-M3-000001', 'order_id' => $this->order->id, 'opened_by' => $this->customer->id,
            'assigned_to' => $this->admin->id, 'case_type' => 'complaint', 'priority' => 'high', 'status' => 'under_review',
            'subject' => 'Oštećen ekran M3', 'description' => 'Ekran ima liniju odmah nakon isporuke.',
            'requested_resolution' => 'repair', 'customer_name_snapshot' => 'Kupac Probni', 'customer_phone_snapshot' => '0600000000',
            'customer_address_snapshot' => 'Probna ulica 1, 11000 Beograd', 'due_at' => now()->subDay(),
        ]);
        AfterSalesCaseItem::query()->create([
            'after_sales_case_id' => $this->case->id, 'order_item_id' => $item->id, 'product_id' => $item->product_id,
            'sku_snapshot' => $item->product_sku, 'product_name_snapshot' => 'Probni laptop M3', 'quantity' => 1,
            'issue_description' => 'Linija preko ekrana M3.',
        ]);
        AfterSalesMessage::query()->create(['after_sales_case_id' => $this->case->id, 'user_id' => $this->customer->id, 'visibility' => 'public', 'body' => 'Javna poruka kupca M3']);
        AfterSalesMessage::query()->create(['after_sales_case_id' => $this->case->id, 'user_id' => $this->admin->id, 'visibility' => 'public', 'body' => 'Odgovor tima M3']);
        AfterSalesMessage::query()->create(['after_sales_case_id' => $this->case->id, 'user_id' => $this->admin->id, 'visibility' => 'internal', 'body' => 'INTERNA-TAJNA-M3']);
        AfterSalesStatusHistory::query()->create([
            'after_sales_case_id' => $this->case->id, 'from_status' => 'open', 'to_status' => 'under_review',
            'actor_id' => $this->admin->id, 'note' => 'Preuzeto na obradu M3.', 'created_at' => now(),
        ]);
        AfterSalesAction::query()->create([
            'action_number' => 'PSA-M3-000001', 'after_sales_case_id' => $this->case->id, 'action_type' => 'refund', 'status' => 'planned',
            'created_by' => $this->admin->id, 'assigned_to' => $this->admin->id, 'amount_rsd' => 1500.50, 'reference' => 'REF-M3',
            'public_note' => 'Javna napomena radnje M3', 'internal_note' => 'INTERNA-RADNJA-M3',
        ]);
        AfterSalesAction::query()->create([
            'action_number' => 'PSA-M3-000002', 'after_sales_case_id' => $this->case->id, 'action_type' => 'return_receipt', 'status' => 'completed',
            'created_by' => $this->admin->id, 'completion_note' => 'Zavrseno M3',
        ]);
    }

    public function test_customer_list_shows_own_cases_as_table_with_status_pill_and_no_old_classes(): void
    {
        $other = $this->person('other-customer', 'user', 1);
        $otherOrder = $this->orderFor($other, 'M3-OS-ORDER-OTHER');
        AfterSalesCase::query()->create([
            'case_number' => 'PS-M3-OTHER', 'order_id' => $otherOrder->id, 'opened_by' => $other->id, 'assigned_to' => $this->admin->id,
            'case_type' => 'service', 'priority' => 'normal', 'status' => 'open', 'subject' => 'Tudj slucaj', 'description' => 'Tudj opis.',
        ]);

        $html = (string) $this->actingAs($this->customer)->get('/after-sales')->assertOk()->getContent();
        $ops = $this->ops($html);

        self::assertStringContainsString('PS-M3-000001', $ops);
        self::assertStringNotContainsString('PS-M3-OTHER', $ops);
        self::assertSame(1, substr_count($ops, '<h1 '));
        self::assertMatchesRegularExpression('/<span class="a1-pill a1-pill--warning">U obradi<\/span>/', $ops);
        self::assertStringContainsString('Reklamacija', $ops);
        self::assertSame(6, substr_count($ops, 'data-label='), 'one row, six labelled cells');
        self::assertStringContainsString('class="a1-table a1-table--cards"', $ops);
        self::assertStringContainsString('href="'.route('after-sales.show', $this->case).'" aria-label="Otvori slučaj PS-M3-000001"', $ops);
        $this->assertCleanMarkup($ops);
        self::assertStringContainsString('aria-label="Nazad na vrh"', $ops);

        $empty = $this->ops((string) $this->actingAs($this->person('no-cases', 'user', 1))->get('/after-sales')->assertOk()->getContent());
        self::assertStringContainsString('class="a1-empty"', $empty);
        self::assertStringContainsString('Još nema otvorenih postprodajnih slučajeva.', $empty);
        self::assertSame(1, substr_count($empty, '<h1 '));
    }

    public function test_customer_detail_never_shows_internal_notes_or_admin_forms(): void
    {
        $ops = $this->ops((string) $this->actingAs($this->customer)->get('/after-sales/'.$this->case->id)->assertOk()->getContent());

        // Positive controls: the page rendered with the public parts.
        self::assertStringContainsString('PS-M3-000001', $ops);
        self::assertStringContainsString('Javna poruka kupca M3', $ops);
        self::assertStringContainsString('Odgovor tima M3', $ops);
        self::assertStringContainsString('Javna napomena radnje M3', $ops);
        self::assertStringContainsString('1.500,50 RSD', $ops);
        self::assertStringContainsString('Zavrseno M3', $ops);
        self::assertMatchesRegularExpression('/<span class="a1-pill a1-pill--warning">U obradi<\/span>/', $ops);
        self::assertMatchesRegularExpression('/<span class="a1-pill a1-pill--ok">Izvršena<\/span>/', $ops);
        // Staff-only content stays out.
        foreach (['INTERNA-TAJNA-M3', 'INTERNA-RADNJA-M3', 'Interno', 'Poruka kupcu:', '/admin/after-sales', 'name="visibility"', 'name="_method"', 'Opasna zona'] as $hidden) {
            self::assertStringNotContainsString($hidden, $ops, $hidden);
        }

        self::assertSame(1, substr_count($ops, '<h1 '));
        self::assertSame(1, substr_count($ops, 'a1-btn--primary'));
        self::assertMatchesRegularExpression('/<form class="a1-form" method="post" action="'.preg_quote(route('after-sales.messages.store', $this->case), '/').'" enctype="multipart\/form-data">\s*<input type="hidden" name="_token"/', $ops);
        self::assertStringContainsString('name="body" required maxlength="10000"', $ops);
        self::assertStringContainsString('name="attachments[]" multiple', $ops);
        $this->assertCleanMarkup($ops);
        $this->assertEveryPostFormHasToken($ops);
    }

    public function test_closed_case_has_no_message_form_for_the_customer(): void
    {
        $closed = $this->extraCase('PS-M3-CLOSED', 'closed');

        $ops = $this->ops((string) $this->actingAs($this->customer)->get('/after-sales/'.$closed->id)->assertOk()->getContent());

        self::assertStringContainsString('PS-M3-CLOSED', $ops);
        self::assertStringContainsString('Još nema poruka.', $ops);
        self::assertStringNotContainsString(route('after-sales.messages.store', $closed), $ops);
        self::assertSame(0, substr_count($ops, 'a1-btn--primary'));
    }

    public function test_customer_create_form_has_labels_csrf_attachments_and_one_primary_submit(): void
    {
        $ops = $this->ops((string) $this->actingAs($this->customer)->get('/orders/'.$this->order->id.'/after-sales/create')->assertOk()->getContent());
        $itemId = $this->order->items()->value('id');

        self::assertSame(1, substr_count($ops, '<h1 '));
        self::assertStringContainsString('Otvori reklamaciju ili servis', $ops);
        self::assertMatchesRegularExpression('/<form class="a1-form" method="post" action="'.preg_quote(route('after-sales.store', $this->order), '/').'" enctype="multipart\/form-data">\s*<input type="hidden" name="_token"/', $ops);
        self::assertStringNotContainsString('name="_method"', $ops);
        foreach (['name="case_type" required', 'name="priority" required', 'name="subject" maxlength="190" required', 'name="description" minlength="20" maxlength="10000" required', 'name="requested_resolution"',
            'name="items['.$itemId.'][selected]" value="1"', 'name="items['.$itemId.'][quantity]"', 'name="items['.$itemId.'][issue_description]"', 'name="attachments[]" multiple'] as $field) {
            self::assertStringContainsString($field, $ops, $field);
        }
        // Every control is reachable through a label.
        foreach (['case-type', 'case-priority', 'case-subject', 'case-description', 'case-resolution', 'item-'.$itemId.'-qty', 'item-'.$itemId.'-issue'] as $id) {
            self::assertStringContainsString('<label class="a1-label" for="'.$id.'">', $ops, $id);
            self::assertStringContainsString('id="'.$id.'"', $ops, $id);
        }
        self::assertStringContainsString('class="a1-drop"', $ops);
        self::assertStringContainsString('Probni laptop M3', $ops);
        self::assertStringContainsString('Kupac Probni', $ops);
        self::assertStringContainsString('class="a1-actionbar"', $ops);
        self::assertSame(1, substr_count($ops, 'a1-btn--primary'));
        self::assertMatchesRegularExpression('/<button class="a1-btn a1-btn--primary" type="submit">.*Otvori slučaj<\/button>/s', $ops);
        $this->assertCleanMarkup($ops);
        $this->assertEveryPostFormHasToken($ops);
    }

    public function test_after_sales_pages_are_closed_to_other_customers_and_users_without_permissions(): void
    {
        $stranger = $this->person('stranger', 'user', 1);
        $this->actingAs($stranger)->get('/after-sales/'.$this->case->id)->assertNotFound();
        $this->actingAs($stranger)->get('/orders/'.$this->order->id.'/after-sales/create')->assertNotFound();

        $nobody = $this->person('nobody', 'user', null);
        $this->actingAs($nobody)->get('/after-sales')->assertForbidden();
        $this->actingAs($nobody)->get('/after-sales/'.$this->case->id)->assertForbidden();
        $this->actingAs($nobody)->get('/orders/'.$this->order->id.'/after-sales/create')->assertForbidden();

        foreach (['/admin/after-sales', '/admin/after-sales/'.$this->case->id] as $url) {
            foreach ([$this->customer, $nobody] as $person) {
                self::assertContains($this->actingAs($person)->get($url)->getStatusCode(), [403, 404], $url);
            }
        }
    }

    public function test_superadmin_list_has_filters_table_pills_and_overdue_text(): void
    {
        $ops = $this->ops((string) $this->actingAs($this->superadmin)->get('/admin/after-sales')->assertOk()->getContent());

        self::assertSame(1, substr_count($ops, '<h1 '));
        self::assertStringContainsString('<form class="a1-filters" method="get" action="'.route('admin.after-sales.index').'" role="search"', $ops);
        foreach (['name="q"', 'name="status"', 'name="priority"', 'name="case_type"', 'name="overdue" value="1"', 'name="execution_pending" value="1"'] as $field) {
            self::assertStringContainsString($field, $ops, $field);
        }
        self::assertSame(1, substr_count($ops, 'a1-btn--primary'), 'Primeni is the only primary action');
        self::assertStringContainsString('PS-M3-000001', $ops);
        self::assertStringContainsString('Oštećen ekran M3', $ops);
        self::assertStringContainsString('Kupac Probni', $ops);
        self::assertSame(8, substr_count($ops, 'data-label='), 'one row, eight labelled cells');
        self::assertMatchesRegularExpression('/<span class="a1-pill a1-pill--warning">Visok<\/span>/', $ops);
        self::assertMatchesRegularExpression('/<span class="a1-pill a1-pill--warning">U obradi<\/span>/', $ops);
        self::assertMatchesRegularExpression('/<span class="a1-pill a1-pill--info">1 aktivnih<\/span>/', $ops);
        self::assertMatchesRegularExpression('/<span class="a1-pill a1-pill--danger">Probijen rok<\/span>/', $ops);
        self::assertStringContainsString('href="'.route('admin.after-sales.show', $this->case).'" aria-label="Obradi slučaj PS-M3-000001"', $ops);
        $this->assertCleanMarkup($ops);
        self::assertStringContainsString('aria-label="Nazad na vrh"', $ops);
    }

    public function test_admin_list_filters_keep_their_query_parameters(): void
    {
        $none = $this->ops((string) $this->actingAs($this->superadmin)->get('/admin/after-sales?status=resolved')->assertOk()->getContent());
        self::assertStringContainsString('Nema slučajeva za izabrane filtere.', $none);
        self::assertStringNotContainsString('PS-M3-000001', $none);
        self::assertMatchesRegularExpression('/<option value="resolved" selected>Rešen<\/option>/', $none);

        $hit = $this->ops((string) $this->actingAs($this->superadmin)->get('/admin/after-sales?status=under_review&overdue=1&execution_pending=1&q=M3-0000')->assertOk()->getContent());
        self::assertStringContainsString('PS-M3-000001', $hit);
        self::assertMatchesRegularExpression('/name="overdue" value="1" checked/', $hit);
        self::assertMatchesRegularExpression('/name="execution_pending" value="1" checked/', $hit);
        self::assertStringContainsString('value="M3-0000"', $hit);
    }

    public function test_admin_sees_only_assigned_cases_and_unassigned_admin_gets_nothing(): void
    {
        $assigned = $this->ops((string) $this->actingAs($this->admin)->get('/admin/after-sales')->assertOk()->getContent());
        self::assertStringContainsString('PS-M3-000001', $assigned);

        $other = $this->person('admin-other', 'admin');
        $ops = $this->ops((string) $this->actingAs($other)->get('/admin/after-sales')->assertOk()->getContent());
        self::assertStringContainsString('Nema slučajeva za izabrane filtere.', $ops);
        self::assertStringContainsString('class="a1-filters"', $ops);
        self::assertStringNotContainsString('PS-M3-000001', $ops);
        $this->actingAs($other)->get('/admin/after-sales/'.$this->case->id)->assertNotFound();
    }

    public function test_superadmin_detail_has_every_action_form_with_token_method_and_cancel_reason(): void
    {
        $ops = $this->ops((string) $this->actingAs($this->superadmin)->get('/admin/after-sales/'.$this->case->id)->assertOk()->getContent());
        $planned = AfterSalesAction::query()->where('action_number', 'PSA-M3-000001')->firstOrFail();

        self::assertSame(1, substr_count($ops, '<h1 '));
        self::assertStringContainsString('<h1 class="a1-title" id="case-title">PS-M3-000001</h1>', $ops);
        self::assertSame(1, substr_count($ops, 'a1-btn--primary'), 'Sačuvaj obradu is the only primary action');
        self::assertStringContainsString('href="'.route('admin.orders.show', $this->order).'"', $ops);

        // Staff see what the customer never does.
        foreach (['INTERNA-TAJNA-M3', 'INTERNA-RADNJA-M3', 'Poruka kupcu:', 'Interno:', 'Javna napomena radnje M3', '1.500,50 RSD', 'Preuzeto na obradu M3.', 'Linija preko ekrana M3.'] as $visible) {
            self::assertStringContainsString($visible, $ops, $visible);
        }
        self::assertMatchesRegularExpression('/<span class="a1-pill a1-pill--warning">Interno<\/span>/', $ops);

        // Update form: PATCH with token.
        self::assertMatchesRegularExpression('/<form class="a1-form" method="post" action="'.preg_quote(route('admin.after-sales.update', $this->case), '/').'">\s*<input type="hidden" name="_token"[^>]*>\s*<input type="hidden" name="_method" value="PATCH"/', $ops);
        // Messages, planner and the per-action forms point at the same routes as before.
        foreach ([route('admin.after-sales.messages.store', $this->case), route('admin.after-sales.actions.store', $this->case),
            route('admin.after-sales.actions.start', [$this->case, $planned]), route('admin.after-sales.actions.complete', [$this->case, $planned]),
            route('admin.after-sales.actions.cancel', [$this->case, $planned])] as $action) {
            self::assertStringContainsString('method="post" action="'.$action.'"', $ops, $action);
        }
        // update, messages, planner, start, complete, cancel.
        self::assertSame(6, preg_match_all('/<form\b[^>]*method="post"/', $ops));
        $this->assertEveryPostFormHasToken($ops);

        // Cancelling needs a reason and sits in the danger zone; terminal actions get no controls.
        self::assertMatchesRegularExpression('/Opasna zona.*<summary>Otkaži radnju<\/summary>.*<textarea class="a1-textarea" id="act-'.$planned->id.'-reason" name="cancellation_reason" required minlength="5" maxlength="3000">/s', $ops);
        self::assertSame(1, substr_count($ops, '/actions/'.$planned->id.'/cancel'));
        self::assertSame(1, substr_count($ops, '<summary>Otkaži radnju</summary>'));
        self::assertSame(1, substr_count($ops, '<summary>Označi kao izvršenu</summary>'));
        foreach (['Pokreni radnju', 'Planiraj novu radnju', 'Potvrdi izvršenje', 'Potvrdi otkazivanje', 'Sačuvaj plan radnje', 'Sačuvaj poruku', 'Sačuvaj obradu'] as $label) {
            self::assertStringContainsString($label, $ops, $label);
        }
        self::assertStringContainsString('PSA-M3-000002', $ops);
        $this->assertCleanMarkup($ops);
        self::assertStringContainsString('aria-label="Nazad na vrh"', $ops);
    }

    public function test_assigned_admin_gets_the_same_execution_controls_and_customer_is_refused(): void
    {
        $ops = $this->ops((string) $this->actingAs($this->admin)->get('/admin/after-sales/'.$this->case->id)->assertOk()->getContent());
        self::assertStringContainsString('<summary>Planiraj novu radnju</summary>', $ops);
        self::assertStringContainsString('<summary>Otkaži radnju</summary>', $ops);
        self::assertStringContainsString('INTERNA-TAJNA-M3', $ops);
        $this->assertEveryPostFormHasToken($ops);

        self::assertContains($this->actingAs($this->customer)->get('/admin/after-sales/'.$this->case->id)->getStatusCode(), [403, 404]);
    }

    public function test_closed_case_hides_the_planner_and_the_message_form_for_staff(): void
    {
        $closed = $this->extraCase('PS-M3-CLOSED-ADM', 'closed');

        $ops = $this->ops((string) $this->actingAs($this->superadmin)->get('/admin/after-sales/'.$closed->id)->assertOk()->getContent());

        self::assertStringContainsString('PS-M3-CLOSED-ADM', $ops);
        self::assertStringContainsString('Još nema planiranih izvršnih radnji.', $ops);
        self::assertStringNotContainsString('Planiraj novu radnju', $ops);
        self::assertStringNotContainsString(route('admin.after-sales.messages.store', $closed), $ops);
        self::assertStringContainsString(route('admin.after-sales.update', $closed), $ops);
    }

    private function assertCleanMarkup(string $ops): void
    {
        self::assertSame(0, preg_match('/class="(?:[^"]*\s)?(?:'.self::OLD_CLASSES.')(?:\s[^"]*)?"/', $ops, $found), 'Old classes must be gone: '.($found[0] ?? ''));
        self::assertSame(0, preg_match('/style="[^"]*(?:#[0-9a-f]{3,8}|rgb|hsl)/i', $ops), 'No colours in inline styles.');
        self::assertSame(0, preg_match('/\sonclick=|\sonchange=/i', $ops), 'script-src-attr is none: no inline handlers.');
    }

    /** Every POST form carries a CSRF token. */
    private function assertEveryPostFormHasToken(string $ops): void
    {
        $forms = preg_match_all('/<form\b[^>]*method="post"/', $ops);
        self::assertGreaterThan(0, $forms);
        self::assertSame($forms, substr_count($ops, '<input type="hidden" name="_token"'));
    }

    /** The orders root only, without the application shell. */
    private function ops(string $html): string
    {
        $start = strpos($html, 'class="a1-ops"');
        self::assertNotFalse($start, 'a1-ops root missing');
        $end = strpos($html, '</main>', $start);

        return substr($html, $start, ($end === false ? strlen($html) : $end) - $start);
    }

    private function extraCase(string $number, string $status): AfterSalesCase
    {
        return AfterSalesCase::query()->create([
            'case_number' => $number, 'order_id' => $this->order->id, 'opened_by' => $this->customer->id, 'assigned_to' => $this->admin->id,
            'case_type' => 'service', 'priority' => 'normal', 'status' => $status, 'subject' => 'Dodatni slucaj', 'description' => 'Opis dodatnog slucaja.',
            'closed_at' => $status === 'closed' ? now() : null,
        ]);
    }

    private function person(string $name, string $role, ?int $group = null): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? $group : null,
            'username' => 'm3os-'.$name, 'email' => 'm3os-'.$name.'@example.test', 'first_name' => ucfirst($name),
            'password_hash' => Hash::make('TestOnlyM3!'), 'status' => 'active',
        ]);
    }

    private function orderFor(User $owner, string $number): Order
    {
        return Order::query()->create([
            'source_system' => 'laravel', 'order_number' => $number, 'user_id' => $owner->id, 'supplier_user_id' => $this->admin->id,
            'status' => 'new', 'shipping_full_name' => 'Drugi Kupac', 'shipping_address' => 'Test street 2',
            'shipping_city' => 'Test', 'shipping_postal_code' => '11000', 'shipping_phone' => '0600000001',
            'subtotal_rsd' => 1000, 'paid_total_rsd' => 0,
        ]);
    }

    private function orderItem(Order $order, string $name, float $unit): OrderItem
    {
        $id = Product::query()->count() + 1;
        $product = Product::query()->create([
            'sku' => 'M3OS-'.$id, 'slug' => 'm3os-'.$id, 'name' => 'Synthetic laptop '.$id,
            'price_amount' => 149, 'price_currency' => 'EUR', 'stock_quantity' => 4,
            'description' => 'Synthetic product description.', 'status' => 'active', 'created_by' => $order->user_id,
        ]);

        return OrderItem::query()->create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_sku' => $product->sku,
            'product_name' => $name, 'quantity' => 1, 'unit_price_original' => $unit,
            'original_currency' => 'RSD', 'unit_price_rsd' => $unit, 'line_total_rsd' => $unit,
        ]);
    }
}
