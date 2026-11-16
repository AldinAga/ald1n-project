<?php

declare(strict_types=1);

namespace Tests\Feature;

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
 * Module 3 (admin order detail on Kobalt components): what each role sees and can reach on
 * /admin/orders/{id}, with real data, and the markup contract. Author: Ald1n <https://ald1n.com>
 */
final class OrdersModuleThreeAdminDetailTest extends TestCase
{
    use RefreshDatabase;

    private const LONG_NAME = 'Aleksandar Konstantinopoljski-Petrovic Njegosevic Dragoslavljevic';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_superadmin_detail_header_items_and_one_primary_action(): void
    {
        $super = $this->person('superadmin');
        $order = $this->order($super, 'ODM3-0001', 'new', 12345678.90);
        for ($i = 1; $i <= 15; $i++) {
            $this->item($order, 'Stavka broj '.$i, 2, 1000.0 + $i);
        }

        $html = (string) $this->actingAs($super)->get('/admin/orders/'.$order->id)->assertOk()->getContent();
        $root = $this->root($html);

        self::assertStringContainsString('data-order-detail-ready="1"', $root);
        self::assertSame(1, substr_count($root, '<h1 '));
        self::assertStringContainsString('<h1 class="a1-title" id="od-title">ODM3-0001</h1>', $root);
        self::assertStringNotContainsString('Nazad na vrh', $html);
        self::assertSame(1, substr_count($root, 'a1-btn--primary'), 'exactly one primary action');
        // New, unaccepted order: the primary action is the real accept form, not a link.
        self::assertMatchesRegularExpression('~<form id="order-workspace-accept" method="post" action="[^"]+/admin/orders/'.$order->id.'/accept">.{0,400}a1-btn a1-btn--primary~s', $root);

        // Status and payment state are dot plus text pills.
        self::assertMatchesRegularExpression('~a1-pill a1-pill--warning">Nova</span>~', $root);
        self::assertMatchesRegularExpression('~a1-pill a1-pill--off">Nije plaćeno</span>~', $root);

        // 15 items, tabular money cells, every cell labelled, phone-card semantics.
        self::assertSame(15, substr_count($root, 'data-label="Artikal"'));
        self::assertSame(15, substr_count($root, 'data-label="Količina"'));
        self::assertStringContainsString('class="a1-table a1-table--cards" role="table"', $root);
        self::assertStringContainsString('<td role="cell" class="a1-table__num" data-label="Ukupno">2.002,00 RSD</td>', $root);
        self::assertStringContainsString('Stavka broj 15', $root);

        // Large money value formatted as before, in header, totals and balance.
        self::assertGreaterThanOrEqual(3, substr_count($root, '12.345.678,90 RSD'));
        // Long recipient name is rendered whole (wrapping is CSS), in the buyer card and in the header line.
        self::assertGreaterThanOrEqual(2, substr_count($root, self::LONG_NAME));
    }

    public function test_old_markup_is_gone_and_every_form_has_csrf_and_the_right_method(): void
    {
        $super = $this->person('superadmin');
        $order = $this->order($super, 'ODM3-0002', 'confirmed', 15000.0);
        $this->item($order, 'Jedna stavka', 1, 15000.0);

        $root = $this->root((string) $this->actingAs($super)->get('/admin/orders/'.$order->id)->assertOk()->getContent());

        self::assertSame(0, preg_match('/class="(?:[^"]*\s)?(?:panel|button|button-[a-z]+|status-badge|admin-table|admin-table-wrap|detail-list|section-heading-row|header-button-row|page-heading|back-link|eyebrow|count-pill|muted|settings-grid|form-side|order-total|document-row|document-list|timeline-event|shipment-grid|completion-form|alert-warning|order-workspace-[a-z-]+)(?:\s[^"]*)?"/', $root), 'Old classes must be gone from the markup.');
        self::assertSame(0, preg_match('/style="[^"]*(?:#[0-9a-f]{3,8}|rgb|hsl)/i', $root));

        self::assertGreaterThan(0, preg_match_all('~<form\b[^>]*>.*?</form>~s', $root, $forms));
        self::assertGreaterThanOrEqual(5, count($forms[0]), 'invoice pdf, accept, shipment, payment entry, reassign/status/deadlines/note forms');
        foreach ($forms[0] as $form) {
            self::assertStringContainsString('name="_token"', $form);
            if (preg_match('~action="[^"]*/admin/orders/\d+/(?:status|reassign|deadlines)"~', $form) === 1) {
                self::assertStringContainsString('name="_method" value="PATCH"', $form);
            }
        }
        // Money forms post, they never rely on GET.
        self::assertMatchesRegularExpression('~<form class="a1-form" method="post" action="[^"]+/admin/orders/'.$order->id.'/payments"~', $root);
    }

    public function test_logistics_panels_keep_their_js_hooks_and_the_list_page_anchor(): void
    {
        $super = $this->person('superadmin');
        $order = $this->order($super, 'ODM3-0003', 'confirmed', 15000.0);
        $this->item($order, 'Za slanje', 1, 15000.0);

        $html = (string) $this->actingAs($super)->get('/admin/orders/'.$order->id)->assertOk()->getContent();
        $root = $this->root($html);

        self::assertStringContainsString('id="order-workspace-logistics-actions" data-logistics-actions', $root);
        self::assertMatchesRegularExpression('~data-logistics-action="shipment" aria-controls="order-workspace-shipment-entry" aria-expanded="false"~', $root);
        self::assertMatchesRegularExpression('~data-logistics-action="delivery" aria-controls="delivery-completion" aria-expanded="false"~', $root);
        // Both panels start hidden and are shown by the script; #delivery-completion is the anchor the list page links to.
        self::assertMatchesRegularExpression('~id="order-workspace-shipment-entry" aria-labelledby="od-shipentry-title"\s+hidden\s*>~', $root);
        self::assertMatchesRegularExpression('~<section id="delivery-completion" class="[^"]*a1-od-toggle" data-logistics-panel="delivery" aria-labelledby="od-del-title"\s+hidden\s*>~', $root);
        self::assertMatchesRegularExpression('~enctype="multipart/form-data" class="a1-form" data-shipment-form~', $root);
        self::assertStringContainsString('data-shipment-method', $root);
        self::assertStringContainsString('data-courier-field', $root);
        self::assertStringContainsString('data-tracking-field', $root);
        self::assertStringContainsString('data-confirm="Potvrditi da je pošiljka poslata? Ova akcija ne potvrđuje isporuku niti naplatu."', $root);
        self::assertStringContainsString('data-confirm="Potvrditi da je pošiljka isporučena? Ova akcija kompletira porudžbinu."', $root);
        // The "next step" band keeps its anchor id (an unaccepted order points at accepting it first).
        self::assertStringContainsString('id="order-workspace-command"', $root);
        self::assertStringContainsString('Preuzmi je za obradu.', $root);
        // The inline script keeps its CSP nonce and its behaviour.
        self::assertMatchesRegularExpression('/<script nonce="[^"]+">\s*\(\(\) => \{\s*const logisticsButtons/', $html);
        self::assertSame(0, preg_match('/\sonclick=|\sonchange=/i', $root));
        // The route that the list page used still has its own button text.
        self::assertStringContainsString('Potvrdi da je pošiljka isporučena', $root);
    }

    public function test_superadmin_only_blocks_are_absent_for_an_administrator(): void
    {
        $super = $this->person('superadmin');
        $admin = $this->person('admin');
        $order = $this->order($admin, 'ODM3-0004', 'new', 15000.0);
        $this->item($order, 'Admin stavka', 1, 15000.0);

        $asSuper = $this->root((string) $this->actingAs($super)->get('/admin/orders/'.$order->id)->assertOk()->getContent());
        $asAdmin = $this->root((string) $this->actingAs($admin)->get('/admin/orders/'.$order->id)->assertOk()->getContent());

        // Positive controls: both roles got the full page, the buyer card and the manual status form.
        foreach ([$asSuper, $asAdmin] as $page) {
            self::assertStringContainsString('<h1 class="a1-title" id="od-title">ODM3-0004</h1>', $page);
            self::assertStringContainsString('id="order-workspace-customer"', $page);
            self::assertStringContainsString('id="order-workspace-status"', $page);
            self::assertSame(1, substr_count($page, 'a1-btn--primary'));
        }
        // Reassignment and courier tracking are superadministrator-only.
        self::assertStringContainsString('id="order-workspace-reassign"', $asSuper);
        self::assertStringContainsString('<dt>Kurirska služba</dt>', $asSuper);
        self::assertStringNotContainsString('id="order-workspace-reassign"', $asAdmin);
        self::assertStringNotContainsString('<dt>Kurirska služba</dt>', $asAdmin);
        self::assertStringNotContainsString('<dt>Broj pošiljke</dt>', $asAdmin);
    }

    public function test_danger_zone_exists_only_for_a_closed_order_and_keeps_its_confirmation(): void
    {
        $super = $this->person('superadmin');
        $open = $this->order($super, 'ODM3-0005', 'new', 15000.0);
        $closed = $this->order($super, 'ODM3-0006', 'cancelled', 15000.0);
        $this->item($open, 'Otvorena', 1, 15000.0);
        $this->item($closed, 'Otkazana', 1, 15000.0);

        $openRoot = $this->root((string) $this->actingAs($super)->get('/admin/orders/'.$open->id)->assertOk()->getContent());
        $closedRoot = $this->root((string) $this->actingAs($super)->get('/admin/orders/'.$closed->id)->assertOk()->getContent());

        self::assertStringContainsString('<h1 class="a1-title" id="od-title">ODM3-0005</h1>', $openRoot);
        self::assertStringNotContainsString('id="order-workspace-danger"', $openRoot);
        self::assertStringNotContainsString('data-order-archive-workspace', $openRoot);

        self::assertStringContainsString('<h1 class="a1-title" id="od-title">ODM3-0006</h1>', $closedRoot);
        self::assertStringContainsString('id="order-workspace-danger"', $closedRoot);
        self::assertStringContainsString('Opasne radnje', $closedRoot);
        self::assertStringContainsString('data-order-archive-workspace', $closedRoot);
        self::assertMatchesRegularExpression('~<form method="post" action="[^"]+/admin/orders/'.$closed->id.'/archive" class="a1-form"~', $closedRoot);
        self::assertStringContainsString('class="a1-btn a1-btn--danger" type="submit" data-confirm="Arhivirati porudžbinu ODM3-0006?', $closedRoot);
        self::assertMatchesRegularExpression('~a1-pill a1-pill--danger">Otkazana</span>~', $closedRoot);
        // A cancelled order has no forward step: the single primary action points at the history.
        self::assertSame(1, substr_count($closedRoot, 'a1-btn--primary'));
        self::assertStringContainsString('href="#order-workspace-timeline">Pregledaj istoriju</a>', $closedRoot);
    }

    public function test_payment_ledger_and_documents_render_for_superadmin_with_labelled_controls(): void
    {
        $super = $this->person('superadmin');
        $order = $this->order($super, 'ODM3-0007', 'confirmed', 15000.0);
        $this->item($order, 'Uplata stavka', 1, 15000.0);

        $root = $this->root((string) $this->actingAs($super)->get('/admin/orders/'.$order->id)->assertOk()->getContent());

        self::assertStringContainsString('id="order-workspace-payments"', $root);
        self::assertStringContainsString('Još nema evidentiranih uplata.', $root);
        self::assertStringContainsString('name="amount_rsd"', $root);
        self::assertMatchesRegularExpression('~<label class="a1-label" for="od-pay-amount">Iznos RSD~', $root);
        self::assertStringContainsString('15.000,00 RSD', $root);
        self::assertStringContainsString('<input type="hidden" name="document_type" value="invoice">', $root);
        self::assertStringContainsString('Još nema izdatih dokumenata.', $root);
        self::assertStringContainsString('id="order-workspace-timeline"', $root);
        // Every labelled control points at an existing id.
        preg_match_all('~<label class="a1-label" for="([^"]+)"~', $root, $labels);
        self::assertGreaterThanOrEqual(8, count($labels[1]));
        foreach ($labels[1] as $for) {
            self::assertStringContainsString('id="'.$for.'"', $root, 'label target '.$for);
        }
    }

    public function test_customer_role_is_refused_the_admin_detail_page(): void
    {
        $super = $this->person('superadmin');
        $customer = $this->person('user');
        $order = $this->order($super, 'ODM3-0008', 'new', 15000.0);
        $this->item($order, 'Tajna stavka', 1, 15000.0);

        // Positive control: the same order renders for the operator.
        $this->actingAs($super)->get('/admin/orders/'.$order->id)->assertOk()->assertSee('ODM3-0008');

        $response = $this->actingAs($customer)->get('/admin/orders/'.$order->id)->assertForbidden();
        self::assertStringNotContainsString('ODM3-0008', (string) $response->getContent());
        self::assertStringNotContainsString('Tajna stavka', (string) $response->getContent());
    }

    /** The order detail root only, without the application shell. */
    private function root(string $html): string
    {
        $start = strpos($html, 'class="a1-ops"');
        self::assertNotFalse($start, 'order detail root missing');
        $end = strpos($html, '</main>', $start);

        return substr($html, $start, ($end === false ? strlen($html) : $end) - $start);
    }

    private function person(string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => 'm3d-'.$role, 'email' => 'm3d-'.$role.'@example.test',
            'password_hash' => Hash::make('TestOnlyM3D!'), 'status' => 'active',
        ]);
    }

    private function order(User $supplier, string $number, string $status, float $subtotal): Order
    {
        return Order::query()->create([
            'source_system' => 'laravel', 'order_number' => $number, 'user_id' => $supplier->id, 'supplier_user_id' => $supplier->id,
            'status' => $status, 'shipping_full_name' => self::LONG_NAME, 'shipping_address' => 'Test street 1',
            'shipping_city' => 'Test', 'shipping_postal_code' => '11000', 'shipping_phone' => '0600000000',
            'subtotal_rsd' => $subtotal, 'paid_total_rsd' => 0, 'payment_method' => 'bank_transfer',
        ]);
    }

    private function item(Order $order, string $name, int $quantity, float $unit): OrderItem
    {
        $id = Product::query()->count() + 1;
        $product = Product::query()->create([
            'sku' => 'M3D-'.$id, 'slug' => 'm3d-'.$id, 'name' => 'Synthetic product '.$id,
            'price_amount' => 149, 'price_currency' => 'EUR', 'stock_quantity' => 4,
            'description' => 'Synthetic product description.', 'status' => 'active', 'created_by' => $order->user_id,
        ]);

        return OrderItem::query()->create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_sku' => $product->sku,
            'product_name' => $name, 'quantity' => $quantity, 'unit_price_original' => $unit,
            'original_currency' => 'RSD', 'unit_price_rsd' => $unit, 'line_total_rsd' => $unit * $quantity,
        ]);
    }
}
