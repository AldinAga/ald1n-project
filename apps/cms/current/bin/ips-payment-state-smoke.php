#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Models\Order;
use App\Services\IpsPaymentPayloadService;
use App\Services\NbsIpsQrService;

require dirname(__DIR__).'/vendor/autoload.php';

$payloads = new IpsPaymentPayloadService();
$nbs = new NbsIpsQrService($payloads);

$paid = new Order();
$paid->forceFill([
    'order_number' => 'APC-TEST-PAID',
    'payment_method' => 'bank_transfer',
    'payment_state' => 'paid',
    'subtotal_rsd' => 12000.00,
    'paid_total_rsd' => 12000.00,
]);

$refunded = new Order();
$refunded->forceFill([
    'order_number' => 'APC-TEST-REFUNDED',
    'payment_method' => 'bank_transfer',
    'payment_state' => 'refunded',
    'subtotal_rsd' => 12000.00,
    'paid_total_rsd' => 0.00,
]);

$partial = new Order();
$partial->forceFill([
    'id' => 999,
    'order_number' => 'ALD-TEST-PARTIAL',
    'payment_method' => 'bank_transfer',
    'payment_state' => 'partial',
    'subtotal_rsd' => 10000.00,
    'paid_total_rsd' => 2500.00,
    'bank_account_number_snapshot' => '160000000000000001',
    'payment_recipient_name_snapshot' => 'Ald1n Test',
    'payment_recipient_address_snapshot' => 'Test address 1',
    'payment_code_snapshot' => '221',
    'payment_purpose_snapshot' => 'Payment order ALD-TEST-PARTIAL',
    'payment_reference_snapshot' => '999',
    'shipping_full_name' => 'Test Buyer',
    'shipping_address' => 'Buyer address 1',
    'shipping_postal_code' => '11000',
    'shipping_city' => 'Beograd',
]);

$cash = new Order();
$cash->forceFill([
    'payment_method' => 'cash',
    'payment_state' => 'paid',
    'subtotal_rsd' => 10000.00,
    'paid_total_rsd' => 10000.00,
]);

$payload = $payloads->payload($partial);
$reflection = new ReflectionMethod(NbsIpsQrService::class, 'generate');
$amountParameter = $reflection->getParameters()[1] ?? null;

$checks = [
    'paid_invoice_skips_qr' => !$nbs->applies($paid, 'invoice'),
    'paid_proforma_skips_qr' => !$nbs->applies($paid, 'proforma'),
    'refunded_invoice_skips_qr' => !$nbs->applies($refunded, 'invoice'),
    'partial_invoice_requires_qr' => $nbs->applies($partial, 'invoice'),
    'partial_proforma_requires_qr' => $nbs->applies($partial, 'proforma'),
    'delivery_note_never_requires_qr' => !$nbs->applies($partial, 'delivery_note'),
    'cash_never_requires_qr' => !$nbs->applies($cash, 'invoice'),
    'partial_payload_uses_outstanding_only' => is_string($payload) && str_contains($payload, 'I:RSD7500,00'),
    'generate_amount_is_optional' => $amountParameter instanceof ReflectionParameter && $amountParameter->allowsNull() && $amountParameter->isDefaultValueAvailable() && $amountParameter->getDefaultValue() === null,
];

$failed = array_keys(array_filter($checks, static fn (bool $ok): bool => !$ok));
if ($failed !== []) {
    fwrite(STDERR, 'FAIL IPS payment-state smoke: '.implode(', ', $failed).PHP_EOL);
    exit(1);
}

echo 'PASS IPS payment-state financial document policy'.PHP_EOL;
echo 'PAID_BANK_TRANSFER_QR=SKIP'.PHP_EOL;
echo 'PARTIAL_BANK_TRANSFER_QR=REQUIRED'.PHP_EOL;
echo 'PARTIAL_QR_AMOUNT=7500.00_RSD_OUTSTANDING_ONLY'.PHP_EOL;
