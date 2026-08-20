<?php

declare(strict_types=1);
$cms = dirname(__DIR__);
require $cms.'/vendor/autoload.php';
$app = require $cms.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ReceivablesService;
use Illuminate\Support\Facades\DB;

$failures = 0;
$pass = static function (bool $ok, string $label) use (&$failures): void {
    if ($ok) echo 'PASS '.$label.PHP_EOL;
    else { echo 'FAIL '.$label.PHP_EOL; $failures++; }
};

$column = DB::selectOne("SHOW COLUMNS FROM `orders` LIKE 'payment_method'");
$type = strtolower((string) ($column->Type ?? ''));
$pass(str_contains($type, 'deferred_payment'), 'orders.payment_method enum includes deferred_payment');
$pass(in_array('bank_transfer', ReceivablesService::RECEIVABLE_PAYMENT_METHODS, true), 'Receivables retains bank_transfer authority');
$pass(in_array('deferred_payment', ReceivablesService::RECEIVABLE_PAYMENT_METHODS, true), 'Receivables includes deferred_payment authority');

$root = dirname($cms, 3);
$store = file_get_contents($cms.'/app/Http/Requests/StoreOrderRequest.php') ?: '';
$orderService = file_get_contents($cms.'/app/Services/OrderService.php') ?: '';
$options = file_get_contents($cms.'/app/Http/Controllers/Api/V1/OrderOptionsController.php') ?: '';
$web = file_get_contents($cms.'/resources/views/orders/create.blade.php') ?: '';
$paymentService = file_get_contents($cms.'/app/Services/OrderPaymentService.php') ?: '';
$types = file_get_contents($root.'/apps/mobile/current/src/types/api.ts') ?: '';
$checkout = file_get_contents($root.'/apps/mobile/current/src/app/(app)/checkout.tsx') ?: '';
$openapi = file_get_contents($root.'/packages/api-contract/openapi.yaml') ?: '';

$pass(str_contains($store, "Rule::in(['cash_on_delivery', 'bank_transfer', 'deferred_payment'])"), 'StoreOrderRequest accepts deferred_payment');
$pass(str_contains($store, "payment_due_at") && str_contains($store, "after_or_equal:today"), 'StoreOrderRequest requires a non-past due date for deferred payment');
$pass(str_contains($orderService, "=== 'deferred_payment' ? (string) \$data['payment_due_at'] : null"), 'OrderService persists deferred due date only for deferred payment');
$pass(str_contains($options, "'value' => 'deferred_payment'") && str_contains($options, "'requires_due_date' => true"), 'Order options expose server-driven deferred payment metadata');
$pass(str_contains($web, 'value="deferred_payment"') && str_contains($web, 'data-payment-due'), 'Web order create exposes deferred payment and due date');
$pass(str_contains($types, "'deferred_payment'") && str_contains($types, 'requires_due_date: boolean'), 'Mobile types expose deferred payment contract');
$pass(str_contains($checkout, 'selectedPayment?.requires_due_date') && str_contains($checkout, 'payment_due_at:'), 'Mobile checkout conditionally sends deferred due date');
$pass(str_contains($paymentService, "payment_method !== 'bank_transfer'"), 'Payment-proof semantics remain bank-transfer-only');
$pass(str_contains($openapi, 'enum: [cash_on_delivery, bank_transfer, deferred_payment]') && str_contains($openapi, 'payment_due_at:'), 'OpenAPI documents deferred payment contract');

if ($failures > 0) {
    echo 'BATCH4_SMOKE_FAIL='.$failures.PHP_EOL;
    exit(1);
}
echo 'BATCH4_SMOKE_FAIL=0'.PHP_EOL;
echo 'BATCH4_SMOKE=PASS'.PHP_EOL;
