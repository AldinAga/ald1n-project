<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$fails = [];
$must = static function (bool $ok, string $label) use (&$fails): void {
    if (!$ok) $fails[] = $label;
};
$read = static fn (string $path): string => (string) file_get_contents($root.'/'.$path);

$financial = $read('app/Services/OrderFinancialStateService.php');
$reconciliation = $read('app/Services/OrderFinancialReconciliationService.php');
$payment = $read('app/Services/OrderPaymentService.php');
$workflow = $read('app/Services/OrderWorkflowService.php');
$ips = $read('app/Services/IpsPaymentPayloadService.php');
$direct = $read('app/Services/DirectSaleService.php');
$correction = $read('app/Services/DirectSalePriceCorrectionService.php');
$web = $read('routes/web.php');
$api = $read('routes/api.php');
$webOrder = $read('app/Http/Controllers/OrderController.php');
$adminOrder = $read('app/Http/Controllers/Admin/OrderController.php');
$apiMutation = $read('app/Http/Controllers/Api/V1/Admin/OrderMutationController.php');
$report = $read('app/Http/Controllers/Admin/ReportController.php');
$mobileApi = (string) file_get_contents(dirname(dirname($root)).'/mobile/current/src/features/admin/orders-admin-api.ts');
$mobileActions = (string) file_get_contents(dirname(dirname($root)).'/mobile/current/src/features/admin/orders-admin-actions.tsx');
$openapi = (string) file_get_contents(dirname(dirname(dirname($root))).'/packages/api-contract/openapi.yaml');

$must(str_contains($financial, 'final class OrderFinancialStateService'), 'canonical financial projector exists');
$must(str_contains($financial, "where('status', 'verified')") && str_contains($financial, "entry_type='refund'"), 'projector derives only verified ledger net');
$must(str_contains($financial, "(string) \$order->status === 'cancelled'") && str_contains($financial, "'refunded' => 'refunded'"), 'projector has cancelled/refunded semantics');
$must(str_contains($payment, '$this->financial->projectLocked($order);'), 'payment service delegates projection');
$must((bool) preg_match('/private function recalculateLocked\(Order \$order\): void\s*\{\s*\$this->financial->projectLocked\(\$order\);\s*\}/s', $payment), 'payment service no longer owns derived-state formula');
$must(!str_contains($workflow, 'function updatePaymentStatus'), 'manual workflow payment status removed');
$must(!str_contains($web, "Route::patch('/orders/{order}/payment'"), 'manual web payment status route removed');
$must(!str_contains($api, "'/orders/{order}/payment-status'"), 'manual api payment status route removed');
$must(!str_contains($apiMutation, 'function paymentStatus'), 'manual api controller action removed');
$must(str_contains($ips, 'function forDisplay') && str_contains($ips, 'function refreshCache'), 'IPS read/write split exists');
$must(!str_contains($webOrder, '$ips->persist($order)') && !str_contains($adminOrder, '$ips->persist($order)'), 'GET order detail does not persist IPS');
$must(!str_contains($webOrder, "->with('errors', new ViewErrorBag())") && !str_contains($adminOrder, "->with('errors', new ViewErrorBag())") && !str_contains($report, "view()->share('errors', new ViewErrorBag())"), 'HTTP validation error bag is preserved');
$must(!str_contains($direct, "'paid_total_rsd' => \$deferred ? 0 : \$lineTotal") && str_contains($direct, '$this->financial->projectLocked($order);'), 'Direct Sale projection is canonical');
$must(!str_contains($correction, "'paid_total_rsd' => \$newTotal") && str_contains($correction, '$this->financial->projectLocked($locked);'), 'Direct Sale correction projection is canonical');
$must(str_contains($reconciliation, '$this->ips->refreshCache') && str_contains($reconciliation, '$this->receivables->syncForOrder'), 'downstream reconciliation is centralized');
$must(!str_contains($mobileApi, 'paymentStatus: (orderId') && !str_contains($mobileApi, '/payment-status'), 'Mobile manual payment status API removed');
$must(!str_contains($mobileActions, "'payment-status'") && !str_contains($mobileActions, 'Sačuvaj status plaćanja'), 'Mobile manual payment status UI removed');
$must(!str_contains($openapi, '/api/v1/admin/orders/{order}/payment-status:') && str_contains($openapi, 'refunded'), 'OpenAPI manual mutation removed and refunded documented');
$must(!str_contains($openapi, '/variants') && !str_contains($mobileApi, 'variant_id'), 'Product Variants remain decommissioned');

if ($fails !== []) {
    foreach ($fails as $fail) fwrite(STDERR, 'CONTRACT_FAIL: '.$fail.PHP_EOL);
    fwrite(STDERR, 'CONTRACT_FAIL_COUNT='.count($fails).PHP_EOL);
    exit(1);
}
fwrite(STDOUT, "BATCH516_CONTRACT=PASS\nCONTRACT_FAIL_COUNT=0\n");
