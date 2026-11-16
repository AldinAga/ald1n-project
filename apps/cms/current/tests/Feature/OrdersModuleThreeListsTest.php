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
 * Module 3 (order lists on Kobalt components): admin list, archive, customer list and cart, per role.
 * Presentation only: the same data, filters, forms, routes and permission gates as before. Author: Ald1n <https://ald1n.com>
 */
final class OrdersModuleThreeListsTest extends TestCase
{
    use RefreshDatabase;

    /** Class tokens of the old order pages; none may remain inside the page root. */
    private const OLD_CLASSES = '/class="(?:[^"]*\s)?(?:button(?:-[a-z]+)*|panel|admin-[a-z-]+|status-badge|status-[a-z]+|page-heading|filter-panel|ald-ops-[a-z-]+|operational-[a-z-]+|attention-[a-z-]+|quick-order-actions|count-pill|eyebrow|back-link|pagination-wrap|muted|row-overdue|text-danger|header-button-row|build16-[a-z0-9-]+|cart-[a-z-]+|order-archive-[a-z-]+|danger-zone|empty-state|alert)(?:\s[^"]*)?"/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_superadmin_list_has_header_tiles_filters_rows_pills_and_conditional_actions(): void
    {
        $super = $this->person('superadmin');
        $buyer = $this->person('user');
        $this->order($buyer, $super, 'M3L-NEW-001', ['status' => 'new', 'payment_method' => 'bank_transfer', 'payment_status' => 'pending', 'subtotal_rsd' => 15000, 'expected_processing_at' => now()->subDays(2)]);
        $this->order($buyer, $super, 'M3L-PROC-002', ['status' => 'processing', 'accepted_at' => now(), 'subtotal_rsd' => 2500.5]);
        $this->order($buyer, $super, 'M3L-CONF-003', ['status' => 'confirmed', 'accepted_at' => now(), 'payment_method' => 'cash_on_delivery', 'payment_status' => 'pending', 'expected_shipping_at' => now()->addDays(3)]);
        $this->order($buyer, $super, 'M3L-OLD-004', ['status' => 'new', 'source_system' => 'legacy']);
        $this->order($buyer, $super, 'M3L-SHIP-005', ['status' => 'shipped', 'accepted_at' => now(), 'payment_status' => 'paid']);

        $html = (string) $this->actingAs($super)->get('/admin/orders')->assertOk()->getContent();
        $page = $this->page($html);

        self::assertStringContainsString('class="a1-ops" data-orders-page-ready="1"', $page);
        self::assertSame(1, substr_count($page, '<h1 '));
        self::assertStringContainsString('<h1 class="a1-title">Sve porudžbine</h1>', $page);
        self::assertStringNotContainsString('Nazad na vrh', $html);
        self::assertSame(0, preg_match(self::OLD_CLASSES, $page), 'Old order list classes must be gone.');
        self::assertSame(1, substr_count($page, 'a1-btn--primary'), 'Exactly one primary action: the filter submit.');
        self::assertStringContainsString('5 porudžbina</span>', $page);

        // Header links keep their targets; the reports link follows the reports.view gate.
        self::assertStringContainsString('href="'.route('admin.orders.archived').'"', $page);
        self::assertStringContainsString('href="'.route('admin.reports.index').'"', $page);

        // Attention tiles are links that set ?attention=, with the counts of the service (1 unaccepted modern order, 1 overdue).
        self::assertMatchesRegularExpression('/href="[^"]*attention=unaccepted"[^>]*>.{0,900}a1-ol-tile__count">1<\/span>\s*<span class="a1-ol-tile__label">Čekaju preuzimanje/s', $page);
        self::assertMatchesRegularExpression('/href="[^"]*attention=overdue"[^>]*>.{0,900}a1-ol-tile__count">1<\/span>\s*<span class="a1-ol-tile__label">Prekoračen rok/s', $page);
        self::assertStringNotContainsString('aria-current="true"', $page);

        // Filter bar: same parameter names as before, a visible label for every control.
        self::assertStringContainsString('<form class="a1-filters a1-ol-filters" method="get" role="search" aria-label="Filtriranje porudžbina">', $page);
        foreach (['q', 'source_system', 'status', 'payment_status', 'supplier_user_id', 'date_from', 'date_to'] as $name) {
            self::assertMatchesRegularExpression('/<(?:input|select) class="a1-(?:input|select)" id="(ol-[a-z]+)"[^>]*name="'.$name.'"/', $page, $name);
        }
        foreach (['ol-q', 'ol-source', 'ol-status', 'ol-payment', 'ol-supplier', 'ol-from', 'ol-to'] as $id) {
            self::assertStringContainsString('<label class="a1-label" for="'.$id.'">', $page);
        }
        self::assertStringContainsString('<button class="a1-btn a1-btn--primary" type="submit">Filtriraj</button>', $page);
        self::assertStringContainsString('href="'.route('admin.orders.index').'">Reset</a>', $page);

        // Table: five data rows, phone card labels, right aligned money, status as dot plus text.
        self::assertStringContainsString('<table class="a1-table a1-table--cards a1-ol-table" role="table">', $page);
        self::assertSame(5, substr_count($page, 'data-label="Broj"'));
        self::assertSame(5, substr_count($page, 'class="a1-table__num" data-label="Iznos"'));
        self::assertStringContainsString('15.000,00 RSD', $page);
        self::assertStringContainsString('2.500,50 RSD', $page);
        self::assertStringContainsString('a1-pill a1-pill--warning">Nova<', $page);
        self::assertStringContainsString('a1-pill a1-pill--warning">Obrada<', $page);
        self::assertStringContainsString('a1-pill a1-pill--warning">Potvrđena<', $page);
        self::assertStringContainsString('a1-pill a1-pill--info">Poslata<', $page);
        self::assertMatchesRegularExpression('~data-label="Plaćanje i rok">\s*<span class="a1-ol-cell">\s*Na čekanju\s*<span class="a1-ol-sub[^"]*">Rok: ~', $page);
        self::assertMatchesRegularExpression('~data-label="Plaćanje i rok">\s*<span class="a1-ol-cell">\s*Plaćeno\s*<span class="a1-ol-sub~', $page);

        // Overdue is marked with text, and only on the one overdue order.
        self::assertSame(1, substr_count($page, 'class="a1-ol-flag"'));
        self::assertSame(1, substr_count($page, 'a1-ol-row--over'));

        // Conditional row actions, same conditions as before (labels carry the order number).
        foreach (['NEW-001', 'PROC-002', 'CONF-003', 'OLD-004', 'SHIP-005'] as $suffix) {
            self::assertStringContainsString('aria-label="Detalji: M3L-'.$suffix.'"', $page);
        }
        self::assertStringContainsString('aria-label="Preuzmi: M3L-NEW-001"', $page);
        self::assertStringNotContainsString('aria-label="Preuzmi: M3L-PROC-002"', $page);
        self::assertStringNotContainsString('aria-label="Preuzmi: M3L-OLD-004"', $page);
        self::assertStringContainsString('aria-label="U obradu: M3L-NEW-001"', $page);
        self::assertStringNotContainsString('aria-label="U obradu: M3L-OLD-004"', $page);
        self::assertStringContainsString('aria-label="Potvrdi: M3L-PROC-002"', $page);
        self::assertStringNotContainsString('aria-label="Potvrdi: M3L-NEW-001"', $page);
        self::assertStringContainsString('aria-label="Evidentiraj isporuku: M3L-CONF-003"', $page);
        self::assertStringContainsString('aria-label="Evidentiraj isporuku: M3L-SHIP-005"', $page);
        self::assertStringNotContainsString('aria-label="Evidentiraj isporuku: M3L-NEW-001"', $page);
        self::assertStringNotContainsString('aria-label="Evidentiraj isporuku: M3L-PROC-002"', $page);

        // Forms: accept and status changes only, each with CSRF; the status forms spoof PATCH and carry the target status.
        self::assertSame(3, substr_count($page, '<form class="a1-ol-inline"'));
        self::assertSame(3, substr_count($page, 'name="_token"'), 'One token per row form; the filter form is GET and has none.');
        self::assertSame(2, substr_count($page, 'name="_method" value="PATCH"'));
        self::assertSame(1, substr_count($page, '<input type="hidden" name="status" value="processing">'));
        self::assertSame(1, substr_count($page, '<input type="hidden" name="status" value="confirmed">'));
    }

    public function test_admin_list_phone_cards_label_every_cell_with_its_column_and_hide_nothing(): void
    {
        $super = $this->person('superadmin');
        $buyer = $this->person('user');
        $this->order($buyer, $super, 'M3L-CARD-001', ['status' => 'new']);
        $this->order($buyer, $super, 'M3L-CARD-002', ['status' => 'confirmed', 'payment_method' => 'cash_on_delivery']);

        $page = $this->page((string) $this->actingAs($super)->get('/admin/orders')->assertOk()->getContent());

        preg_match_all('/<th\b[^>]*>(.*?)<\/th>/s', $page, $heads);
        $columns = array_map(static fn (string $h): string => trim(strip_tags($h)), $heads[1]);
        self::assertCount(9, $columns, 'positive control: the header row was found');

        preg_match('/<tbody.*?<\/tbody>/s', $page, $body);
        self::assertNotEmpty($body, 'positive control: the table body was found');
        preg_match_all('/<td\b[^>]*>/', $body[0], $cells);
        preg_match_all('/<td\b[^>]*\bdata-label="([^"]+)"[^>]*>/', $body[0], $labelled);
        self::assertCount(18, $cells[0], 'two rows of nine cells');
        self::assertCount(18, $labelled[0], 'every td carries a data-label');
        self::assertSame($columns, array_values(array_unique($labelled[1])), 'the card labels are exactly the column headers, in order');
        // Nothing is switched off for phones: the old late-column class only reordered cards, it never hid a cell.
        self::assertStringNotContainsString('hidden-mobile', $page);
        self::assertStringNotContainsString('display:none', $page);
    }

    public function test_attention_filter_is_marked_in_words_and_chips_remove_filters(): void
    {
        $super = $this->person('superadmin');
        $buyer = $this->person('user');
        $this->order($buyer, $super, 'M3L-NEW-001', ['status' => 'new']);
        $this->order($buyer, $super, 'M3L-PROC-002', ['status' => 'processing', 'accepted_at' => now()]);

        $page = $this->page((string) $this->actingAs($super)->get('/admin/orders?attention=unaccepted&status=new')->assertOk()->getContent());

        self::assertStringContainsString('M3L-NEW-001', $page);
        self::assertStringNotContainsString('M3L-PROC-002', $page);
        self::assertSame(1, substr_count($page, 'aria-current="true"'));
        self::assertStringContainsString('Aktivan filter', $page);
        self::assertStringContainsString('<input type="hidden" name="attention" value="unaccepted">', $page);
        self::assertStringContainsString('Ukloni operativni filter', $page);
        self::assertStringContainsString('Operativni filter: Čekaju preuzimanje', $page);
        self::assertStringContainsString('Status: Nova', $page);
        self::assertStringContainsString('aria-label="Ukloni filter: Status: Nova"', $page);
        self::assertSame(2, substr_count($page, 'class="a1-chip"'));
    }

    public function test_empty_result_shows_empty_state_without_a_table(): void
    {
        $super = $this->person('superadmin');
        $this->order($this->person('user'), $super, 'M3L-NEW-001', ['status' => 'new']);

        $page = $this->page((string) $this->actingAs($super)->get('/admin/orders?q=no-such-order-xyz')->assertOk()->getContent());

        self::assertStringContainsString('<p>Nema porudžbina.</p>', $page);
        self::assertStringContainsString('class="a1-empty"', $page);
        self::assertStringNotContainsString('<table', $page);
        self::assertStringContainsString('Pretraga: no-such-order-xyz', $page);
        // Positive control: the filter bar and header still rendered.
        self::assertStringContainsString('<h1 class="a1-title">Sve porudžbine</h1>', $page);
        self::assertStringContainsString('name="q" value="no-such-order-xyz"', $page);
    }

    public function test_pager_keeps_the_query_string_and_page_parameter(): void
    {
        $super = $this->person('superadmin');
        $buyer = $this->person('user');
        for ($i = 1; $i <= 41; $i++) {
            $this->order($buyer, $super, sprintf('M3L-PG-%03d', $i), ['status' => 'new']);
        }

        $first = $this->page((string) $this->actingAs($super)->get('/admin/orders?status=new')->assertOk()->getContent());
        self::assertStringContainsString('M3L-PG-041', $first);
        self::assertStringNotContainsString('M3L-PG-001', $first);
        self::assertStringContainsString('<nav class="a1-pager" aria-label="Straničenje">', $first);
        self::assertStringContainsString('Strana 1 od 2', $first);
        self::assertSame(1, preg_match('/<a class="a1-page" href="([^"]+)" rel="next">/', $first, $next));
        parse_str((string) parse_url(html_entity_decode($next[1]), PHP_URL_QUERY), $query);
        self::assertEquals(['status' => 'new', 'page' => '2'], $query);

        $second = $this->page((string) $this->actingAs($super)->get('/admin/orders?status=new&page=2')->assertOk()->getContent());
        self::assertStringContainsString('M3L-PG-001', $second);
        self::assertStringContainsString('aria-current="page"><span class="a1-sr">Strana </span>2</span>', $second);
        self::assertStringContainsString('rel="prev"', $second);
    }

    public function test_admin_sees_only_assigned_orders_and_no_responsible_person_filter(): void
    {
        $super = $this->person('superadmin');
        $admin = $this->person('admin');
        $buyer = $this->person('user');
        $this->order($buyer, $admin, 'M3L-ADM-001', ['status' => 'new']);
        $this->order($buyer, $super, 'M3L-OTHER-002', ['status' => 'new']);

        $html = (string) $this->actingAs($admin)->get('/admin/orders')->assertOk()->getContent();
        $page = $this->page($html);

        self::assertStringContainsString('<h1 class="a1-title">Meni dodeljene porudžbine</h1>', $page);
        self::assertStringContainsString('M3L-ADM-001', $page);
        self::assertStringNotContainsString('M3L-OTHER-002', $page);
        self::assertStringContainsString('name="payment_status"', $page);
        self::assertStringNotContainsString('name="supplier_user_id"', $page);
        self::assertSame($admin->can('reports.view'), str_contains($page, 'href="'.route('admin.reports.index').'"'));
        self::assertSame(1, substr_count($page, 'a1-btn--primary'));
        self::assertSame(0, preg_match(self::OLD_CLASSES, $page));
    }

    public function test_archive_for_superadmin_keeps_restore_and_purge_with_confirmations(): void
    {
        $super = $this->person('superadmin');
        $order = $this->order($this->person('user'), $super, 'M3L-ARC-001', [
            'status' => 'cancelled', 'archived_at' => now(), 'archive_reason' => 'Test razlog',
            'shipping_full_name' => 'Test Kupac', 'shipping_phone' => '0601234567',
        ]);

        $html = (string) $this->actingAs($super)->get('/admin/orders/archived')->assertOk()->getContent();
        $page = $this->page($html);

        self::assertStringContainsString('class="a1-ops" data-order-archive-center="1"', $page);
        self::assertSame(1, substr_count($page, '<h1 '));
        self::assertStringContainsString('<h1 class="a1-title">Arhivirane porudžbine</h1>', $page);
        self::assertStringNotContainsString('Nazad na vrh', $html);
        self::assertSame(0, preg_match(self::OLD_CLASSES, $page));
        self::assertStringContainsString('href="'.route('admin.orders.index').'">Aktivne porudžbine</a>', $page);
        self::assertStringContainsString('1 arhiviranih</span>', $page);
        self::assertSame(1, substr_count($page, 'a1-btn--primary'), 'Only the search submit is primary.');
        self::assertStringContainsString('M3L-ARC-001', $page);
        self::assertStringContainsString('a1-pill">Otkazana<', $page);
        self::assertStringContainsString('Test razlog', $page);

        // Restore: POST with CSRF and a confirmation.
        self::assertStringContainsString('action="'.route('admin.orders.restore', ['orderId' => $order->id]).'"', $page);
        self::assertStringContainsString('data-confirm="Opozvati arhiviranje porudžbine M3L-ARC-001?"', $page);

        // Purge: opener with the dialog relation, a danger dialog outside the table, DELETE spoof, token and confirmation.
        self::assertStringContainsString('data-order-purge-open="'.$order->id.'"', $page);
        self::assertStringContainsString('aria-controls="orderPurgeDialog-'.$order->id.'"', $page);
        self::assertStringContainsString('class="a1-btn a1-btn--danger a1-btn--sm"', $page);
        self::assertGreaterThan((int) strpos($page, '</table>'), (int) strpos($page, '<dialog'));
        self::assertStringContainsString('action="'.route('admin.orders.purge', ['orderId' => $order->id]).'"', $page);
        self::assertStringContainsString('name="_method" value="DELETE"', $page);
        self::assertSame(2, substr_count($page, 'name="_token"'));
        self::assertStringContainsString('data-confirm="Trajno ukloniti porudžbinu M3L-ARC-001', $page);
        self::assertStringContainsString('name="purge_reason"', $page);
        self::assertStringContainsString('name="confirmation"', $page);
        self::assertStringContainsString('Za potvrdu upiši tačan broj porudžbine: M3L-ARC-001', $page);
        self::assertStringContainsString("querySelector('[data-order-archive-center=\"1\"]')", $html);
    }

    public function test_archive_for_admin_has_restore_but_no_purge(): void
    {
        $admin = $this->person('admin');
        $this->order($this->person('user'), $admin, 'M3L-ARC-002', ['status' => 'cancelled', 'archived_at' => now(), 'archive_reason' => 'Test razlog']);

        $page = $this->page((string) $this->actingAs($admin)->get('/admin/orders/archived')->assertOk()->getContent());

        self::assertStringContainsString('M3L-ARC-002', $page);
        self::assertStringContainsString('Opozovi Arhiviranje', $page);
        foreach (['data-order-purge-open', 'data-order-permanent-delete', 'name="purge_reason"', 'orderPurgeDialog-', '<dialog'] as $absent) {
            self::assertStringNotContainsString($absent, $page);
        }
    }

    public function test_archive_search_without_match_shows_empty_state_and_removable_chip(): void
    {
        $super = $this->person('superadmin');
        $this->order($this->person('user'), $super, 'M3L-ARC-001', ['status' => 'cancelled', 'archived_at' => now(), 'archive_reason' => 'Test razlog']);

        $page = $this->page((string) $this->actingAs($super)->get('/admin/orders/archived?q=zzz-nothing')->assertOk()->getContent());

        self::assertStringContainsString('Nema arhiviranih porudžbina koje odgovaraju pretrazi.', $page);
        self::assertStringNotContainsString('<table', $page);
        self::assertStringContainsString('Pretraga: zzz-nothing', $page);
        self::assertStringContainsString('href="'.route('admin.orders.archived').'">Reset</a>', $page);
        self::assertStringContainsString('<h1 class="a1-title">Arhivirane porudžbine</h1>', $page);
    }

    public function test_customer_list_shows_serbian_labels_own_orders_and_no_filters(): void
    {
        $buyer = $this->person('user');
        $other = $this->person('user');
        $super = $this->person('superadmin');
        $this->order($buyer, $super, 'M3L-OWN-001', ['status' => 'shipped', 'payment_method' => 'cash_on_delivery', 'payment_status' => 'pending', 'subtotal_rsd' => 4200, 'supplier_name_snapshot' => 'Dobavljač Test']);
        $this->order($buyer, $super, 'M3L-OWN-002', ['status' => 'new', 'payment_method' => 'bank_transfer', 'payment_status' => 'paid']);
        $this->order($other, $super, 'M3L-FOREIGN-003', ['status' => 'new']);

        $html = (string) $this->actingAs($buyer)->get('/orders')->assertOk()->getContent();
        $page = $this->page($html);

        self::assertStringContainsString('class="a1-ops" data-build16-orders-list="1"', $page);
        self::assertSame(1, substr_count($page, '<h1 '));
        self::assertStringContainsString('<h1 class="a1-title">Moje porudžbine</h1>', $page);
        self::assertStringNotContainsString('Nazad na vrh', $html);
        self::assertSame(0, preg_match(self::OLD_CLASSES, $page));
        self::assertStringContainsString('2 ukupno</span>', $page);
        self::assertStringContainsString('M3L-OWN-001', $page);
        self::assertStringContainsString('M3L-OWN-002', $page);
        self::assertStringNotContainsString('M3L-FOREIGN-003', $page);

        // The single primary action is the order button (orders.create), and there are no filters or forms.
        self::assertSame(1, substr_count($page, 'a1-btn--primary'));
        self::assertStringContainsString('href="'.route('orders.create').'"', $page);
        self::assertStringNotContainsString('<form', $page);
        self::assertStringNotContainsString('a1-filters', $page);

        // Columns as before.
        foreach (['Broj', 'Dobavljač', 'Status', 'Plaćanje', 'Iznos', 'Provizija', 'Datum'] as $label) {
            self::assertStringContainsString('data-label="'.$label.'"', $page);
        }
        self::assertStringContainsString('Dobavljač Test', $page);
        self::assertStringContainsString('4.200,00 RSD', $page);
        self::assertStringContainsString('data-label="Provizija">—</td>', $page);

        // Raw enum values are mapped to the labels the admin list uses; nothing English leaks.
        self::assertStringContainsString('Pouzećem / Na čekanju', $page);
        self::assertStringContainsString('Uplata na račun / Plaćeno', $page);
        self::assertStringContainsString('a1-pill a1-pill--info">Poslata<', $page);
        self::assertStringContainsString('a1-pill a1-pill--warning">Nova<', $page);
        foreach (['cash_on_delivery', 'bank_transfer', '>shipped<', '>new<'] as $raw) {
            self::assertStringNotContainsString($raw, $page);
        }
        self::assertStringContainsString('aria-label="Detalji: M3L-OWN-001"', $page);
        self::assertStringContainsString('href="'.route('orders.show', Order::query()->where('order_number', 'M3L-OWN-001')->firstOrFail()).'"', $page);
    }

    public function test_customer_without_orders_gets_empty_state_and_superadmin_has_no_personal_list(): void
    {
        $buyer = $this->person('user');
        $page = $this->page((string) $this->actingAs($buyer)->get('/orders')->assertOk()->getContent());

        self::assertStringContainsString('<p>Još nema porudžbina.</p>', $page);
        self::assertStringContainsString('class="a1-empty"', $page);
        self::assertStringNotContainsString('<table', $page);
        self::assertStringContainsString('<h1 class="a1-title">Moje porudžbine</h1>', $page);

        $this->actingAs($this->person('superadmin'))->get('/orders')->assertForbidden();
        $this->get('/cart')->assertForbidden();
    }

    public function test_cart_keeps_hooks_forms_totals_and_one_primary_action(): void
    {
        $buyer = $this->person('user');
        $product = $this->product($buyer, 'M3L-CART-1', 5);

        $html = (string) $this->actingAs($buyer)
            ->withSession(['orders.cart.items' => [$product->id => 2]])
            ->get('/cart')->assertOk()->getContent();
        $page = $this->page($html);

        self::assertStringContainsString('class="a1-ops" data-user-cart-ready="1"', $page);
        self::assertSame(1, substr_count($page, '<h1 '));
        self::assertStringContainsString('<h1 class="a1-title">Korpa</h1>', $page);
        self::assertStringNotContainsString('Nazad na vrh', $html);
        self::assertSame(0, preg_match(self::OLD_CLASSES, $page));
        self::assertStringContainsString('2 kom.</span>', $page);
        self::assertStringContainsString('M3L-CART-1 name', $page);
        self::assertStringContainsString('SKU: M3L-CART-1', $page);
        self::assertStringContainsString('Lager: 5', $page);
        self::assertStringContainsString('298,00 EUR', $page);

        // JS hooks of cart-ui.js.
        foreach (['data-cart-quantity-form', 'data-cart-quantity-minus', 'data-cart-quantity-plus', 'data-cart-quantity-input', 'data-ux-allow-multiple-submit'] as $hook) {
            self::assertStringContainsString($hook, $page);
        }
        self::assertMatchesRegularExpression('/<input\s+class="a1-input"\s+id="cart-qty-'.$product->id.'"\s+type="number"\s+name="quantity"\s+value="2"\s+min="1"\s+max="5"/', $page);
        self::assertStringContainsString('<label class="a1-label" for="cart-qty-'.$product->id.'">', $page);

        // Forms: update (PATCH), remove (DELETE) and clear (DELETE), each with CSRF.
        self::assertStringContainsString('action="'.route('cart.items.update', $product).'"', $page);
        self::assertStringContainsString('action="'.route('cart.items.destroy', $product).'"', $page);
        self::assertStringContainsString('action="'.route('cart.clear').'"', $page);
        self::assertSame(3, substr_count($page, '<form'));
        self::assertSame(3, substr_count($page, 'name="_token"'));
        self::assertSame(1, substr_count($page, 'name="_method" value="PATCH"'));
        self::assertSame(2, substr_count($page, 'name="_method" value="DELETE"'));

        // Checkout is the single primary action and carries the same target as before.
        self::assertSame(1, substr_count($page, 'a1-btn--primary'));
        self::assertStringContainsString('href="'.route('orders.create', ['from_cart' => 1]).'"', $page);
        self::assertStringContainsString('Nastavi na porudžbinu', $page);
        self::assertStringNotContainsString('Prvo ispravi količine', $page);
        self::assertStringNotContainsString('a1-ol-line--issue', $page);
    }

    public function test_cart_stock_issue_blocks_checkout_with_text_not_colour_alone(): void
    {
        $buyer = $this->person('user');
        $fine = $this->product($buyer, 'M3L-CART-OK', 5);
        $short = $this->product($buyer, 'M3L-CART-SHORT', 1);

        $page = $this->page((string) $this->actingAs($buyer)
            ->withSession(['orders.cart.items' => [$fine->id => 1, $short->id => 3]])
            ->get('/cart')->assertOk()->getContent());

        self::assertStringContainsString('M3L-CART-OK name', $page);
        self::assertStringContainsString('M3L-CART-SHORT name', $page);
        self::assertSame(1, substr_count($page, 'a1-ol-line--issue'));
        self::assertSame(1, substr_count($page, 'Količina prelazi lager'));
        self::assertStringContainsString('role="alert"', $page);
        self::assertStringContainsString('prelazi raspoloživ lager', $page);
        self::assertMatchesRegularExpression('/<button class="a1-btn a1-btn--primary a1-btn--block" type="button" disabled>Prvo ispravi količine<\/button>/', $page);
        self::assertStringNotContainsString('from_cart', $page);
        self::assertSame(1, substr_count($page, 'a1-btn--primary'));
    }

    public function test_empty_cart_shows_empty_state_with_catalog_link(): void
    {
        $page = $this->page((string) $this->actingAs($this->person('user'))->get('/cart')->assertOk()->getContent());

        self::assertStringContainsString('Korpa je prazna', $page);
        self::assertStringContainsString('class="a1-empty"', $page);
        self::assertStringContainsString('href="'.route('catalog.index').'">Otvori katalog</a>', $page);
        self::assertSame(1, substr_count($page, 'a1-btn--primary'));
        self::assertStringNotContainsString('<form', $page);
        self::assertStringContainsString('<h1 class="a1-title">Korpa</h1>', $page);
    }

    /** The page root only, without the application shell. */
    public function test_an_order_with_completed_at_shows_a_green_isporucena_chip_never_poslata(): void
    {
        $buyer = $this->person('user');
        $super = $this->person('superadmin');
        $this->order($buyer, $super, 'M3L-DELIVERED-1', ['status' => 'shipped', 'completed_at' => '2026-01-10 12:00:00']);
        $this->order($buyer, $super, 'M3L-SHIPPED-2', ['status' => 'shipped']);

        foreach ([[$super, '/admin/orders'], [$buyer, '/orders']] as [$who, $url]) {
            $page = $this->page((string) $this->actingAs($who)->get($url)->assertOk()->getContent());
            $row = (string) preg_replace('~^.*?(M3L-DELIVERED-1.*?</tr>).*$~s', '$1', $page);
            // Positive control: the delivered row was found and is the shorter slice, then the chip is green and says Isporučena.
            self::assertStringContainsString('M3L-DELIVERED-1', $row);
            self::assertStringNotContainsString('M3L-SHIPPED-2', $row);
            self::assertMatchesRegularExpression('~a1-pill a1-pill--ok">Isporučena</span>~', $row, $url);
            self::assertStringNotContainsString('Poslata', $row);
            self::assertStringNotContainsString('Kompletirana', $row);
            // The merely shipped order keeps its own label and chip.
            self::assertMatchesRegularExpression('~M3L-SHIPPED-2.*?a1-pill a1-pill--info">Poslata</span>~s', $page, $url);
        }
    }

    private function page(string $html): string
    {
        $start = strpos($html, 'class="a1-ops"');
        self::assertNotFalse($start, 'page root missing');
        $end = strpos($html, '</main>', $start);

        return substr($html, $start, ($end === false ? strlen($html) : $end) - $start);
    }

    private function person(string $role): User
    {
        static $n = 0;
        $n++;

        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => 'm3l-'.$role.'-'.$n, 'email' => 'm3l-'.$role.'-'.$n.'@example.test',
            'password_hash' => Hash::make('TestOnlyM3L!'), 'status' => 'active',
        ]);
    }

    /** @param array<string,mixed> $over */
    private function order(User $buyer, User $supplier, string $number, array $over = []): Order
    {
        return Order::query()->create(array_merge([
            'source_system' => 'laravel', 'order_number' => $number, 'user_id' => $buyer->id, 'supplier_user_id' => $supplier->id,
            'supplier_name_snapshot' => $supplier->displayName(), 'status' => 'new',
            'payment_method' => 'cash_on_delivery', 'payment_status' => 'pending',
            'shipping_full_name' => 'Synthetic Customer', 'shipping_address' => 'Test street 1',
            'shipping_city' => 'Test', 'shipping_postal_code' => '11000', 'shipping_phone' => '0600000000',
            'subtotal_rsd' => 1000, 'paid_total_rsd' => 0,
        ], $over));
    }

    private function product(User $creator, string $sku, int $stock): Product
    {
        return Product::query()->create([
            'sku' => $sku, 'slug' => strtolower($sku), 'name' => $sku.' name',
            'price_amount' => 149, 'price_currency' => 'EUR', 'stock_quantity' => $stock,
            'description' => 'Synthetic product description.', 'status' => 'active', 'created_by' => $creator->id,
        ]);
    }
}
