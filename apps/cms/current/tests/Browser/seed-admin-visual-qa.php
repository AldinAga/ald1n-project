<?php

declare(strict_types=1);

/*
 * ISOLATED TEST FIXTURE ONLY. Author: Ald1n <https://ald1n.com>
 * Writes synthetic records exclusively to the disposable GitHub Actions CI DB used by
 * admin-visual-qa. Same fail-closed guard pattern as seed-liquid-glass-preview.php.
 */

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductWarranty;
use App\Models\ReceivableCase;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

if (
    getenv('ADMIN_VISUAL_CI') !== '1'
    || getenv('APP_ENV') !== 'testing'
    || getenv('DB_CONNECTION') !== 'mysql'
    || getenv('DB_DATABASE') !== 'ald1n_admin_visual_ci'
    || getenv('DB_HOST') !== '127.0.0.1'
    || !is_string(getenv('ADMIN_VISUAL_FIXTURES_PATH'))
    || !str_starts_with((string) getenv('ADMIN_VISUAL_FIXTURES_PATH'), '/')
) {
    fwrite(STDERR, "HOLD: admin visual fixture requires isolated testing env and loopback ald1n_admin_visual_ci.\n");
    exit(2);
}

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (
    config('app.env') !== 'testing'
    || config('database.default') !== 'mysql'
    || config('database.connections.mysql.host') !== '127.0.0.1'
    || config('database.connections.mysql.database') !== 'ald1n_admin_visual_ci'
    || config('session.secure') !== false
    || (string) config('session.domain') !== ''
) {
    fwrite(STDERR, "HOLD: resolved Laravel configuration is not the disposable CI DB.\n");
    exit(3);
}

$password = 'AdminVisualSyntheticOnly-2026!';
$admin = User::query()->create([
    'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
    'username' => 'admin-visual-superadmin',
    'email' => 'admin-visual-superadmin@example.test',
    'password_hash' => Hash::make($password),
    'first_name' => 'Test SuperAdmin',
    'status' => 'active',
]);
// Second admin so Chromium and WebKit (run in parallel) never share a session list on /account.
$adminWebkit = User::query()->create([
    'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
    'username' => 'admin-visual-superadmin-wk',
    'email' => 'admin-visual-superadmin-wk@example.test',
    'password_hash' => Hash::make($password),
    'first_name' => 'Test SuperAdmin',
    'status' => 'active',
]);
// Administrator role (not superadmin): dashboard without the period selector, valuation and brand mix.
$administrator = User::query()->create([
    'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
    'username' => 'admin-visual-administrator',
    'email' => 'admin-visual-administrator@example.test',
    'password_hash' => Hash::make($password),
    'first_name' => 'Test Administrator',
    'status' => 'active',
]);
$customer = User::query()->create([
    'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
    'user_group_id' => 1,
    'username' => 'admin-visual-customer',
    'email' => 'admin-visual-customer@example.test',
    'password_hash' => Hash::make($password),
    'first_name' => 'Test Kupac',
    'status' => 'active',
    'portal_activated_at' => '2026-01-10 09:00:00',
]);

$product = Product::query()->create([
    'sku' => 'ADMIN-VISUAL-1',
    'name' => 'Admin vizuelni testni artikal',
    'description' => 'Samo izolovana CI vizuelna provera, nikad stvarna prodaja.',
    'slug' => 'admin-visual-1',
    'price_amount' => 290,
    'price_currency' => 'EUR',
    'status' => 'active',
    'stock_quantity' => 3,
    'low_stock_threshold' => 1,
    'created_by' => $admin->id,
]);

// Brands page (/admin/catalog-settings/brands): an active and an inactive brand, the active one linked to a product type, so the
// "Aktivan" / "Neaktivan" status badges and the type badges are rendered by the word gate. The type link is best effort (schema may require more columns).
$brandNow = now();
try {
    $brandActive = DB::table('brands')->insertGetId(['name' => 'Admin vizuelni brend', 'slug' => 'admin-vizuelni-brend', 'status' => 'active', 'sort_order' => 1, 'created_at' => $brandNow, 'updated_at' => $brandNow]);
    DB::table('brands')->insert(['name' => 'Neaktivan vizuelni brend', 'slug' => 'neaktivan-vizuelni-brend', 'status' => 'inactive', 'sort_order' => 2, 'created_at' => $brandNow, 'updated_at' => $brandNow]);
    $brandType = DB::table('product_types')->insertGetId(['name' => 'Grafičke kartice', 'slug' => 'graficke-kartice-vizuelni', 'status' => 'active', 'sort_order' => 1, 'created_at' => $brandNow, 'updated_at' => $brandNow]);
    DB::table('brand_product_type')->insert(['brand_id' => $brandActive, 'product_type_id' => $brandType]);
} catch (\Throwable $e) {
    fwrite(STDERR, "brand seed (partly) skipped: " . $e->getMessage() . "\n");
}

$order = Order::query()->create([
    'source_system' => 'laravel',
    'order_number' => 'APC-20260115-00000001',
    'user_id' => $customer->id,
    'supplier_user_id' => $admin->id,
    'supplier_name_snapshot' => 'Test SuperAdmin',
    'supplier_role_snapshot' => 'SuperAdministrator',
    'status' => 'new',
    'inventory_state' => 'reserved',
    'shipping_full_name' => 'Samo Test Kupac',
    'shipping_address' => 'Testna adresa 1',
    'shipping_city' => 'Test Grad',
    'shipping_postal_code' => '11000',
    'shipping_phone' => '0600000000',
    'subtotal_rsd' => 12500,
    'eur_rsd_rate' => 117.78,
    'payment_method' => 'deferred_payment',
    'payment_status' => 'pending',
    'payment_state' => 'unpaid',
    'paid_total_rsd' => 0,
    'payment_due_at' => '2026-03-01 00:00:00',
]);
$item = $order->items()->create([
    'product_id' => $product->id, 'product_sku' => $product->sku, 'product_name' => $product->name,
    'quantity' => 1, 'unit_price_original' => 12500, 'original_currency' => 'RSD',
    'unit_price_rsd' => 12500, 'line_total_rsd' => 12500,
]);
$warranty = ProductWarranty::query()->create([
    'warranty_number' => 'ADMIN-VISUAL-WARRANTY', 'order_id' => $order->id, 'order_item_id' => $item->id,
    'product_id' => $product->id, 'user_id' => $customer->id, 'status' => 'active',
    'starts_at' => '2026-02-01', 'expires_at' => '2027-02-01', 'duration_months' => 12,
    'customer_name_snapshot' => 'Samo Test Kupac', 'product_name_snapshot' => $product->name,
    'product_sku_snapshot' => $product->sku, 'quantity' => 1, 'serial_numbers_json' => [],
]);
ReceivableCase::query()->create([
    'order_id' => $order->id, 'case_number' => 'ADMIN-VISUAL-CASE', 'status' => 'monitoring', 'created_by' => $admin->id,
]);

// Module 2 (dashboard): completed orders with fixed January 2026 dates, brand and purchase snapshots, so the trend,
// the brand mix and the KPIs have data at /?period=custom&date_from=2026-01-01&date_to=2026-01-31. They are ARCHIVED:
// the management report reads them (it does not filter archived orders) while every operational list (orders,
// recent orders, receivables, open-balance KPI) skips them, so the other modules' baselines stay as they were.
foreach ([
    ['001', '2026-01-12 11:00:00', 'Lenovo', 3, 41500.0, 90000.0],
    ['002', '2026-01-15 12:00:00', 'ASUS', 2, 62500.0, 98000.0],
    ['003', '2026-01-20 15:30:00', 'HP', 1, 54900.0, 41000.0],
    ['004', '2026-01-20 16:00:00', 'Lenovo', 1, 38000.0, 29000.0],
    // The month before: the comparison line then shows revenue up and gross profit and net down (arrow icon plus percent).
    ['005', '2025-12-15 12:00:00', 'Lenovo', 1, 300000.0, 100000.0],
] as [$suffix, $completedAt, $brand, $quantity, $unit, $purchase]) {
    $done = Order::query()->create([
        'source_system' => 'laravel',
        'order_number' => 'ADMIN-VISUAL-DONE-'.$suffix,
        'user_id' => $admin->id,
        'supplier_user_id' => $admin->id,
        'supplier_name_snapshot' => 'Test SuperAdmin',
        'supplier_role_snapshot' => 'SuperAdministrator',
        'status' => 'new',
        'inventory_state' => 'reserved',
        'shipping_full_name' => 'Samo Test Kupac',
        'shipping_address' => 'Testna adresa 1',
        'shipping_city' => 'Test Grad',
        'shipping_postal_code' => '11000',
        'shipping_phone' => '0600000000',
        'subtotal_rsd' => $unit * $quantity,
        'eur_rsd_rate' => 117.78,
        'payment_method' => 'deferred_payment',
        'payment_status' => 'pending',
        'payment_state' => 'unpaid',
        'paid_total_rsd' => 0,
    ]);
    $doneItem = $done->items()->create([
        'product_id' => $product->id, 'product_sku' => $product->sku, 'product_name' => $product->name,
        'quantity' => $quantity, 'unit_price_original' => $unit, 'original_currency' => 'RSD',
        'unit_price_rsd' => $unit, 'line_total_rsd' => $unit * $quantity,
    ]);
    DB::table('order_items')->where('id', $doneItem->id)->update(['brand_name_snapshot' => $brand, 'purchase_total_rsd_snapshot' => $purchase]);
    // The orders.status enum has no "completed"; the management report counts an order as completed when completed_at is set and it is not cancelled.
    DB::table('orders')->where('id', $done->id)->update(['status' => 'shipped', 'completed_at' => $completedAt, 'archived_at' => '2026-01-25 09:00:00']);
}

// Owner feedback 2026-11-16: ONE delivered order that is NOT archived, so the recent-orders list shows the green "Isporučena" chip.
// Completed in November 2025: outside the default month, January 2026 and the December 2025 comparison window of the cells.
$delivered = Order::query()->create([
    'source_system' => 'laravel',
    'order_number' => 'APC-20260116-00000002',
    'user_id' => $admin->id,
    'supplier_user_id' => $admin->id,
    'supplier_name_snapshot' => 'Test SuperAdmin',
    'supplier_role_snapshot' => 'SuperAdministrator',
    'status' => 'new',
    'inventory_state' => 'reserved',
    'shipping_full_name' => 'Samo Test Kupac',
    'shipping_address' => 'Testna adresa 1',
    'shipping_city' => 'Test Grad',
    'shipping_postal_code' => '11000',
    'shipping_phone' => '0600000000',
    'subtotal_rsd' => 18000,
    'eur_rsd_rate' => 117.78,
    'payment_method' => 'deferred_payment',
    'payment_status' => 'pending',
    'payment_state' => 'unpaid',
    'paid_total_rsd' => 18000,
]);
$delivered->items()->create([
    'product_id' => $product->id, 'product_sku' => $product->sku, 'product_name' => $product->name,
    'quantity' => 1, 'unit_price_original' => 18000, 'original_currency' => 'RSD',
    'unit_price_rsd' => 18000, 'line_total_rsd' => 18000,
]);
DB::table('orders')->where('id', $delivered->id)->update(['status' => 'shipped', 'completed_at' => '2025-11-20 12:00:00']);

// Fixed timestamps so two runs render the same dates.
foreach (['users', 'products', 'orders', 'order_items', 'product_warranties', 'receivable_cases'] as $table) {
    $set = [];
    foreach (['created_at', 'updated_at'] as $column) {
        if (\Illuminate\Support\Facades\Schema::hasColumn($table, $column)) {
            $set[$column] = '2026-01-15 10:00:00';
        }
    }
    if ($set !== []) {
        DB::table($table)->update($set);
    }
}

// ---------------------------------------------------------------------------------------------------------------
// Module 3 (orders): every state the order screens can show, plus after-sales, field operations and service parts.
// Column combinations follow the real services (OrderFinancialStateService, OrderWorkflowService, OrderShipmentService,
// OrderArchiveService): payment_state/paid_total_rsd always match verified order_payments rows, a cancelled order has
// inventory_state returned and payment_state cancelled, and only a completed or cancelled order can be archived.
//
// Dashboard protection (shell and dashboard are strict and read non-archived orders):
//  - the dashboard's "recent orders" are the 7 operational orders with the newest updated_at: the original order keeps
//    2026-01-15 10:00 (first), the plain filler orders sit one day older, every stress order (long name, 15 items,
//    long amount, ...) is older still, so only short, plain rows can ever reach the dashboard;
//  - the 12.345.678,90 order is CANCELLED: the dashboard's value and open-balance KPIs skip cancelled orders, so no
//    KPI tile ever receives an 8-digit amount; every other active order is small (the active total stays below 1 M);
//  - the completed order is completed on 2025-10-20, outside the January 2026 period, the December 2025 comparison
//    period and the current month, so the management report numbers stay as they were;
//  - after-sales case and field work order are due/planned in 2031, so no "overdue" priority item appears; the
//    service part has stock above its minimum, so no "low stock" item appears. Priority items stay: new orders, overdue receivable.
// ---------------------------------------------------------------------------------------------------------------
$seedStartedAt = date('Y-m-d H:i:s', time() - 2);
$maxOrderIdBefore = (int) DB::table('orders')->max('id');
$FIXED = '2026-01-15 10:00:00';

$qaOrder = function (string $tag, array $attrs, array $lines, string $stamp) use ($customer, $admin, $product): Order {
    $subtotal = 0.0;
    foreach ($lines as [, $qty, $unit]) {
        $subtotal += $qty * $unit;
    }
    $row = Order::query()->create($attrs + [
        'source_system' => 'laravel',
        // Production number format APC-YYYYMMDD-NNNNNNNN (21 characters), never longer.
        'order_number' => 'APC-20251202-'.str_pad((string) (900 + (crc32($tag) % 99999)), 8, '0', STR_PAD_LEFT),
        'user_id' => $customer->id,
        'supplier_user_id' => $admin->id,
        'supplier_name_snapshot' => 'Test SuperAdmin',
        'supplier_role_snapshot' => 'SuperAdministrator',
        'status' => 'new',
        'inventory_state' => 'reserved',
        'shipping_full_name' => 'Samo Test Kupac',
        'shipping_address' => 'Testna adresa 1',
        'shipping_city' => 'Test Grad',
        'shipping_postal_code' => '11000',
        'shipping_phone' => '0600000000',
        'subtotal_rsd' => $subtotal,
        'eur_rsd_rate' => 117.78,
        'payment_method' => 'cash_on_delivery',
        'payment_status' => 'pending',
        'payment_state' => 'unpaid',
        'paid_total_rsd' => 0,
    ]);
    foreach ($lines as [$name, $qty, $unit]) {
        $row->items()->create([
            'product_id' => $product->id, 'product_sku' => $product->sku, 'product_name' => $name,
            'quantity' => $qty, 'unit_price_original' => $unit, 'original_currency' => 'RSD',
            'unit_price_rsd' => $unit, 'line_total_rsd' => $qty * $unit,
        ]);
    }
    DB::table('orders')->where('id', $row->id)->update(['created_at' => $stamp, 'updated_at' => $stamp]);

    return $row;
};
$qaHistory = static function (Order $row, array $steps): void {
    foreach ($steps as [$old, $new, $at, $note]) {
        DB::table('order_status_history')->insert([
            'order_id' => $row->id, 'changed_by' => $row->supplier_user_id, 'old_status' => $old, 'new_status' => $new, 'note' => $note, 'created_at' => $at,
        ]);
    }
};
$qaPayment = static function (Order $row, string $number, float $amount, string $method, string $status, string $at) use ($customer, $admin): void {
    $verified = $status === 'verified';
    \App\Models\OrderPayment::query()->create([
        'order_id' => $row->id, 'payment_number' => $number, 'entry_type' => 'payment', 'status' => $status,
        'amount_rsd' => $amount, 'payment_method' => $method, 'paid_at' => $at, 'reference' => 'VQA-'.$number,
        'note' => 'Sintetička uplata za vizuelnu proveru.', 'submitted_by' => $customer->id,
        'verified_by' => $verified ? $admin->id : null, 'verified_at' => $verified ? $at : null,
    ]);
};
$accepted = ['accepted_by' => $admin->id, 'accepted_at' => '2025-12-02 09:30:00', 'assigned_at' => '2025-12-02 09:00:00'];
$older = '2025-12-02 09:00:00'; // older than every filler (2026-01-14) and than the original order (2026-01-15)

// Paid: bank transfer, one verified payment for the full amount.
$orderPaid = $qaOrder('PAID', $accepted + [
    'status' => 'confirmed', 'payment_method' => 'bank_transfer', 'payment_status' => 'paid', 'payment_state' => 'paid',
    'paid_total_rsd' => 18000, 'payment_verified_at' => '2025-12-03 12:00:00',
], [['Kancelarijska stolica Test', 2, 6000.0], ['Radni sto Test', 1, 6000.0]], $older);
$qaPayment($orderPaid, 'UPL-ADMIN-VISUAL-PAID', 18000.0, 'bank_transfer', 'verified', '2025-12-03 12:00:00');
$qaHistory($orderPaid, [[null, 'new', '2025-12-02 09:00:00', null], ['new', 'confirmed', '2025-12-02 10:00:00', 'Potvrđeno nakon uplate.']]);

// Partly paid: 5.000 of 18.000 verified, the rest open.
$orderPartly = $qaOrder('PARTLY', $accepted + [
    'status' => 'processing', 'payment_method' => 'bank_transfer', 'payment_status' => 'pending', 'payment_state' => 'partial',
    'paid_total_rsd' => 5000, 'payment_due_at' => '2026-02-15 00:00:00',
], [['Ormar sa kliznim vratima Test', 1, 18000.0]], $older);
$qaPayment($orderPartly, 'UPL-ADMIN-VISUAL-PARTLY', 5000.0, 'bank_transfer', 'verified', '2025-12-04 11:00:00');
$qaHistory($orderPartly, [[null, 'new', '2025-12-02 09:00:00', null], ['new', 'processing', '2025-12-02 09:40:00', 'Pripremamo robu.']]);

// Shipped by courier with tracking and the shipment row (order_shipments has one row per order). Not completed.
$orderShipped = $qaOrder('SHIPPED', $accepted + [
    'status' => 'shipped', 'tracking_number' => 'VQA-TRACK-123456789', 'tracking_updated_at' => '2025-12-05 14:00:00', 'tracking_updated_by' => $admin->id,
], [['Polica za knjige Test', 3, 3500.0]], $older);
\App\Models\OrderShipment::query()->create([
    'order_id' => $orderShipped->id, 'shipment_method' => 'courier', 'courier_name_snapshot' => 'Test Kurirska Služba',
    'courier_tracking_url_snapshot' => 'https://tracking.example.test/{tracking}', 'shipped_at' => '2025-12-05 14:00:00',
    'recipient_name' => 'Samo Test Kupac', 'recipient_phone' => '0600000000', 'tracking_number_snapshot' => 'VQA-TRACK-123456789',
    'note' => 'Pošiljka predata kuriru.', 'recorded_by' => $admin->id,
]);
$qaHistory($orderShipped, [[null, 'new', '2025-12-02 09:00:00', null], ['new', 'processing', '2025-12-02 09:30:00', null], ['processing', 'confirmed', '2025-12-03 09:30:00', null], ['confirmed', 'shipped', '2025-12-05 14:00:00', 'Poslato kurirom.']]);

// Completed (status shipped + completed_at, as the app stores it), fully paid, delivered. completed_at is outside every dashboard period.
$orderCompleted = $qaOrder('COMPLETED', $accepted + [
    'status' => 'shipped', 'payment_method' => 'cash_on_delivery', 'payment_status' => 'paid', 'payment_state' => 'paid',
    'paid_total_rsd' => 9000, 'payment_verified_at' => '2025-10-20 12:00:00', 'completed_at' => '2025-10-20 12:00:00', 'completed_by' => $admin->id,
], [['Fotelja Test', 1, 9000.0]], '2025-10-15 09:00:00');
$qaPayment($orderCompleted, 'UPL-ADMIN-VISUAL-DONE', 9000.0, 'cash_on_delivery', 'verified', '2025-10-20 12:00:00');
\App\Models\OrderDelivery::query()->create([
    'order_id' => $orderCompleted->id, 'delivery_method' => 'courier', 'delivered_at' => '2025-10-20 12:00:00',
    'recipient_name' => 'Samo Test Kupac', 'reference' => 'VQA-DOSTAVA-1', 'confirmed_by' => $admin->id,
]);
$qaHistory($orderCompleted, [[null, 'new', '2025-10-15 09:00:00', null], ['new', 'shipped', '2025-10-18 09:00:00', 'Poslato.']]);

// Cancelled by the customer while new, stock returned.
$cancelledAttrs = [
    'status' => 'cancelled', 'inventory_state' => 'returned', 'inventory_returned_at' => '2025-12-06 10:00:00',
    'cancelled_at' => '2025-12-06 10:00:00', 'cancelled_by' => $customer->id, 'payment_status' => 'cancelled', 'payment_state' => 'cancelled',
];
$orderCancelled = $qaOrder('CANCELLED', $cancelledAttrs, [['Komoda Test', 1, 11000.0]], $older);
$qaHistory($orderCancelled, [[null, 'new', '2025-12-02 09:00:00', null], ['new', 'cancelled', '2025-12-06 10:00:00', 'Kupac je otkazao porudžbinu.']]);

// Archived: cancelled and archived (archive requires completed or cancelled). The 5 archived DONE orders above stay.
$orderArchived = $qaOrder('ARCHIVED', $cancelledAttrs + [
    'archived_at' => '2026-01-05 09:00:00', 'archived_by' => $admin->id, 'archive_reason' => 'Otkazana porudžbina, završena evidencija.',
], [['Noćni stočić Test', 2, 4000.0]], $older);
$qaHistory($orderArchived, [[null, 'new', '2025-12-02 09:00:00', null], ['new', 'cancelled', '2025-12-06 10:00:00', null]]);

// 15 line items (all point at the one seeded product; names are order-item snapshots, as in production).
$lines15 = [];
for ($n = 1; $n <= 15; $n++) {
    $lines15[] = [sprintf('Stavka %02d - Servisni komplet za vizuelnu proveru dugačkih naziva', $n), 1 + ($n % 3), 400.0 + $n * 50];
}
$orderItems15 = $qaOrder('ITEMS15', [], $lines15, $older);

// Exactly 60 characters, the first token 45 characters without a space: stresses wrapping in list rows, cards and detail.
$longName = substr(str_repeat('Predugackoprezimebezrazmaka', 3), 0, 45).' '.substr(str_repeat('Imekupca', 2), 0, 14);
if (strlen($longName) !== 60) {
    fwrite(STDERR, "FAIL: long customer name is not 60 characters.\n");
    exit(5);
}
$orderLongName = $qaOrder('LONGNAME', [
    'shipping_full_name' => $longName, 'shipping_city' => 'Veoma Dugacak Naziv Grada Za Proveru Preloma',
    'shipping_address' => 'Ulica '.substr(str_repeat('Predugackanazivulice', 3), 0, 45).' 12',
], [['Stolica Test', 1, 3000.0]], $older);

// 12.345.678,90 RSD. Cancelled on purpose: cancelled orders are excluded from the dashboard value and open-balance KPIs.
$orderLongAmount = $qaOrder('LONGAMOUNT', $cancelledAttrs, [['Poslovna oprema Test (velika vrednost)', 1, 12345678.90]], $older);
$qaHistory($orderLongAmount, [[null, 'new', '2025-12-02 09:00:00', null], ['new', 'cancelled', '2025-12-06 10:00:00', 'Vrednost iznad odobrenog limita.']]);

// 36 plain orders so the list paginates (40 per page; the operational total is 45). Short names, small amounts.
for ($n = 1; $n <= 36; $n++) {
    $tag = 'F'.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    $stamp = '2026-01-14 '.sprintf('%02d:%02d:00', 8 + intdiv($n, 60), $n % 60);
    // Production number format APC-YYYYMMDD-NNNNNNNN (21 characters): the number must always be fully readable.
    $filler = $qaOrder($tag, ['order_number' => sprintf('APC-20260114-%08d', 100 + $n)] + ($n % 6 === 0
        ? $accepted + ['status' => 'processing']
        : ($n % 9 === 0 ? $accepted + ['status' => 'confirmed'] : [])), [['Test artikal '.$n, 1, 1000.0 + $n * 100]], $stamp);
    $qaHistory($filler, [[null, 'new', $stamp, null]]);
}

// The detailed order: submitted (not yet verified) bank-transfer payment, an issued proforma and an internal note.
// A submitted payment is not counted by OrderFinancialStateService, so the order stays unpaid with paid_total_rsd 0.
$qaPayment($order, 'UPL-ADMIN-VISUAL-001', 6000.0, 'bank_transfer', 'submitted', '2026-01-20 10:00:00');
\App\Models\OrderDocument::query()->create([
    'order_id' => $order->id, 'document_type' => 'proforma', 'revision_number' => 1, 'document_number' => 'PRE-ADMIN-VISUAL-001', 'status' => 'issued',
    'issued_by' => $admin->id, 'issued_at' => '2026-01-16 09:00:00', 'currency' => 'RSD', 'subtotal_rsd' => 12500, 'total_rsd' => 12500,
    'company_name' => 'Test Firma d.o.o.', 'company_city' => 'Test Grad', 'customer_name' => 'Samo Test Kupac',
    'customer_address' => 'Testna adresa 1', 'customer_city' => 'Test Grad', 'supplier_name' => 'Test SuperAdmin',
    'payment_method_snapshot' => 'deferred_payment', 'payment_status_snapshot' => 'pending',
]);
\App\Models\OrderInternalNote::query()->create(['order_id' => $order->id, 'user_id' => $admin->id, 'note' => 'Kupac je tražio isporuku posle 15 časova. Interna napomena, kupac je ne vidi.']);
DB::table('orders')->where('id', $order->id)->update(['last_internal_note_at' => '2026-01-16 09:30:00']);
$qaHistory($order, [[null, 'new', '2026-01-15 10:00:00', null]]);

// After-sales (opened by the customer on the completed order), its service-visit action and the field work order.
$completedItem = $orderCompleted->items()->firstOrFail();
$case = \App\Models\AfterSalesCase::query()->create([
    'case_number' => 'PS-ADMIN-VISUAL-001', 'order_id' => $orderCompleted->id, 'opened_by' => $customer->id, 'assigned_to' => $admin->id,
    'case_type' => 'service', 'priority' => 'normal', 'status' => 'approved', 'subject' => 'Servisna intervencija na fotelji',
    'description' => 'Mehanizam za naslon ne drži položaj. Potreban izlazak ekipe na adresu.', 'requested_resolution' => 'repair',
    'resolution_type' => 'repair', 'resolution_summary' => 'Odobrena servisna intervencija.', 'customer_name_snapshot' => 'Samo Test Kupac',
    'customer_phone_snapshot' => '0600000000', 'customer_address_snapshot' => 'Testna adresa 1, 11000 Test Grad',
    'due_at' => '2031-06-01 12:00:00', 'first_response_at' => '2025-11-02 09:00:00',
]);
\App\Models\AfterSalesCaseItem::query()->create([
    'after_sales_case_id' => $case->id, 'order_item_id' => $completedItem->id, 'product_id' => $product->id, 'sku_snapshot' => $completedItem->product_sku,
    'product_name_snapshot' => $completedItem->product_name, 'quantity' => 1, 'issue_description' => 'Naslon ne drži položaj.',
]);
\App\Models\AfterSalesMessage::query()->create(['after_sales_case_id' => $case->id, 'user_id' => $customer->id, 'visibility' => 'public', 'body' => 'Poštovani, naslon popušta nakon par minuta sedenja. Hvala unapred.']);
\App\Models\AfterSalesMessage::query()->create(['after_sales_case_id' => $case->id, 'user_id' => $admin->id, 'visibility' => 'public', 'body' => 'Primili smo prijavu i odobrili intervenciju. Ekipa će vas kontaktirati radi termina.']);
\App\Models\AfterSalesMessage::query()->create(['after_sales_case_id' => $case->id, 'user_id' => $admin->id, 'visibility' => 'internal', 'body' => 'Interno: proveriti rezervni mehanizam na lageru delova.']);
DB::table((new \App\Models\AfterSalesStatusHistory())->getTable())->insert([
    ['after_sales_case_id' => $case->id, 'from_status' => null, 'to_status' => 'open', 'actor_id' => $customer->id, 'note' => null, 'created_at' => '2025-11-01 09:00:00'],
    ['after_sales_case_id' => $case->id, 'from_status' => 'open', 'to_status' => 'approved', 'actor_id' => $admin->id, 'note' => 'Intervencija odobrena.', 'created_at' => '2025-11-02 09:00:00'],
]);
$action = \App\Models\AfterSalesAction::query()->create([
    'action_number' => 'AKC-ADMIN-VISUAL-001', 'after_sales_case_id' => $case->id, 'action_type' => 'service_visit', 'status' => 'planned',
    'inventory_handling' => 'none', 'assigned_to' => $admin->id, 'created_by' => $admin->id, 'scheduled_at' => '2031-03-10 09:00:00',
    'public_note' => 'Izlazak ekipe radi popravke mehanizma.',
]);
$team = \App\Models\FieldServiceTeam::query()->create([
    'code' => 'VQA-01', 'name' => 'Test terenska ekipa', 'team_type' => 'internal', 'contact_person' => 'Test Serviser', 'phone' => '0601234567',
    'email' => 'ekipa@example.test', 'vehicle_registration' => 'BG-000-TS', 'service_area' => 'Test Grad i okolina', 'is_active' => true,
    'notes' => 'Sintetička ekipa za vizuelnu proveru.', 'created_by' => $admin->id, 'updated_by' => $admin->id,
]);
$workOrder = \App\Models\FieldWorkOrder::query()->create([
    'work_order_number' => 'RN-ADMIN-VISUAL-001', 'after_sales_action_id' => $action->id, 'field_service_team_id' => $team->id, 'status' => 'planned',
    'planned_start_at' => '2031-03-10 09:00:00', 'planned_end_at' => '2031-03-10 11:00:00', 'customer_name_snapshot' => 'Samo Test Kupac',
    'customer_phone_snapshot' => '0600000000', 'service_address_snapshot' => 'Testna adresa 1, 11000 Test Grad',
    'public_note' => 'Ekipa dolazi u dogovorenom terminu.', 'created_by' => $admin->id, 'updated_by' => $admin->id,
]);

// Service parts: one part (stock above its minimum, so no low-stock priority item) and one ordered purchase request.
$supplier = \App\Models\ServicePartSupplier::query()->create([
    'code' => 'VQA-DOB', 'name' => 'Test dobavljač delova', 'contact_person' => 'Test Komercijalista', 'phone' => '0111234567',
    'email' => 'dobavljac@example.test', 'address' => 'Dobavljačka 5, Test Grad', 'lead_time_days' => 5, 'is_active' => true,
    'created_by' => $admin->id, 'updated_by' => $admin->id,
]);
$part = \App\Models\ServicePart::query()->create([
    'sku' => 'VQA-DEO-001', 'name' => 'Mehanizam za naslon (test)', 'unit' => 'kom', 'stock_quantity' => 12, 'reserved_quantity' => 1,
    'minimum_quantity' => 2, 'average_cost_rsd' => 700, 'preferred_supplier_id' => $supplier->id, 'is_active' => true,
    'created_by' => $admin->id, 'updated_by' => $admin->id,
]);
$purchase = \App\Models\ServicePartPurchaseRequest::query()->create([
    'request_number' => 'NAB-ADMIN-VISUAL-001', 'supplier_id' => $supplier->id, 'status' => 'ordered', 'supplier_reference' => 'VQA-PO-77',
    'expected_at' => '2031-02-01', 'submitted_at' => '2025-12-10 09:00:00', 'ordered_at' => '2025-12-11 09:00:00', 'notes' => 'Dopuna lagera delova.',
    'total_cost_rsd' => 7000, 'created_by' => $admin->id, 'updated_by' => $admin->id,
]);
\App\Models\ServicePartPurchaseRequestItem::query()->create([
    'purchase_request_id' => $purchase->id, 'service_part_id' => $part->id, 'ordered_quantity' => 10, 'received_quantity' => 0, 'unit_cost_rsd' => 700,
]);

// Fixed timestamps for everything created above except orders (those carry their own stamps): same discipline as before.
foreach ([
    'order_items', 'order_payments', 'order_documents', 'order_internal_notes', 'order_deliveries', 'order_shipments',
    'after_sales_cases', 'after_sales_case_items', 'after_sales_messages', 'after_sales_actions', 'field_service_teams', 'field_work_orders',
    'service_part_suppliers', 'service_parts', 'service_part_purchase_requests', 'service_part_purchase_request_items',
] as $table) {
    $set = [];
    foreach (['created_at', 'updated_at'] as $column) {
        if (\Illuminate\Support\Facades\Schema::hasColumn($table, $column)) {
            $set[$column] = $FIXED;
        }
    }
    if ($set !== [] && \Illuminate\Support\Facades\Schema::hasColumn($table, 'created_at')) {
        DB::table($table)->where('created_at', '>=', $seedStartedAt)->update($set);
    }
}
$newOrders = (int) DB::table('orders')->where('id', '>', $maxOrderIdBefore)->count();

$payload = [
    'sentinel' => 'ALD1N_ADMIN_VISUAL_CI_SYNTHETIC_ONLY',
    'admin' => ['login' => $admin->username, 'password' => $password],
    'admin_webkit' => ['login' => $adminWebkit->username, 'password' => $password],
    'administrator' => ['login' => $administrator->username, 'password' => $password],
    'customer' => ['login' => $customer->username, 'password' => $password],
    'ids' => [
        'product' => $product->id, 'order' => $order->id, 'warranty' => $warranty->id, 'customer' => $customer->id,
        'order_paid' => $orderPaid->id, 'order_partly_paid' => $orderPartly->id, 'order_shipped' => $orderShipped->id,
        'order_completed' => $orderCompleted->id, 'order_cancelled' => $orderCancelled->id, 'order_archived' => $orderArchived->id,
        'order_items15' => $orderItems15->id, 'order_long_name' => $orderLongName->id, 'order_long_amount' => $orderLongAmount->id,
        'case' => $case->id, 'work_order' => $workOrder->id, 'team' => $team->id, 'part' => $part->id, 'purchase_request' => $purchase->id,
    ],
];
$dest = (string) getenv('ADMIN_VISUAL_FIXTURES_PATH');
if (file_put_contents($dest, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)) === false) {
    fwrite(STDERR, "FAIL: couldn't save ephemeral browser fixture.\n");
    exit(4);
}
chmod($dest, 0600);
echo "ADMIN_VISUAL_SYNTHETIC_FIXTURES=READY users=4 products=1 orders=2 archived_completed_orders=5 delivered_open_orders=1 warranties=1 module3_orders={$newOrders} after_sales_cases=1 field_work_orders=1 service_part_purchases=1\n";
