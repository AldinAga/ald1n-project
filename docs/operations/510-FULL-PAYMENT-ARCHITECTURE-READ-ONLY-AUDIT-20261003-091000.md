# Batch 510 - Full Payment Architecture Read-Only Audit

- RESULT: PASS_READ_ONLY_AUDIT
- TIMESTAMP: 20261003-091000
- SOURCE_COMMIT: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- ORIGIN_MAIN: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- SOURCE_CHANGED: NO
- DATABASE_WRITE_PERFORMED: NO
- MIGRATION_RUN: NO
- COMPOSER_INSTALL_RUN: NO
- EAS_BUILD_STARTED: NO
- OTA_PUBLISHED: NO
- GOOGLE_PLAY_ACTION: NO

## Audit scope

- payment schema and domain values
- all order/payment ledger invariants
- submitted/verified/rejected/voided lifecycle metadata
- proof metadata and private-file existence
- full/over-remaining submitted proofs and duplicate submissions
- refund source/integrity signals
- receivables synchronization/allocation consistency
- IPS cache versus financial state
- legacy payment_status write history
- web/API payment and receivable routes/middleware
- source writer inventory and error-bag behavior
- actual test-runner availability
- exact order 132 / payment 48 / payment 54 forensic snapshot

## Runtime payment audit
```text
=== RUNTIME AUTHORITY ===
APP_ENV=production
DB_DRIVER=mysql
DB_TRANSACTION_LEVEL=0

=== CORE PAYMENT SCHEMA ===
TABLE|orders|PRESENT
COLUMN|orders.id|PRESENT
COLUMN|orders.source_system|PRESENT
COLUMN|orders.sales_channel|PRESENT
COLUMN|orders.status|PRESENT
COLUMN|orders.completed_at|PRESENT
COLUMN|orders.archived_at|PRESENT
COLUMN|orders.payment_method|PRESENT
COLUMN|orders.payment_status|PRESENT
COLUMN|orders.payment_state|PRESENT
COLUMN|orders.subtotal_rsd|PRESENT
COLUMN|orders.paid_total_rsd|PRESENT
COLUMN|orders.payment_verified_at|PRESENT
COLUMN|orders.bank_account_number_snapshot|PRESENT
COLUMN|orders.payment_recipient_name_snapshot|PRESENT
COLUMN|orders.payment_code_snapshot|PRESENT
COLUMN|orders.payment_reference_snapshot|PRESENT
TABLE|order_payments|PRESENT
COLUMN|order_payments.id|PRESENT
COLUMN|order_payments.order_id|PRESENT
COLUMN|order_payments.payment_number|PRESENT
COLUMN|order_payments.entry_type|PRESENT
COLUMN|order_payments.status|PRESENT
COLUMN|order_payments.amount_rsd|PRESENT
COLUMN|order_payments.payment_method|PRESENT
COLUMN|order_payments.paid_at|PRESENT
COLUMN|order_payments.proof_path|PRESENT
COLUMN|order_payments.submitted_by|PRESENT
COLUMN|order_payments.verified_by|PRESENT
COLUMN|order_payments.verified_at|PRESENT
COLUMN|order_payments.rejected_by|PRESENT
COLUMN|order_payments.rejected_at|PRESENT
COLUMN|order_payments.rejection_reason|PRESENT
COLUMN|order_payments.voided_by|PRESENT
COLUMN|order_payments.voided_at|PRESENT
CORE_SCHEMA_MISSING_COUNT=0

=== ROUTE INVENTORY ===
ROUTE|{"methods":["PATCH"],"uri":"admin/orders/{order}/payment","name":"admin.orders.payment","middleware":["web","auth","active","tracked-session","permission:orders.manage"]}
ROUTE|{"methods":["POST"],"uri":"admin/orders/{order}/payments","name":"admin.orders.payments.store","middleware":["web","auth","active","tracked-session","permission:payments.manage"]}
ROUTE|{"methods":["POST"],"uri":"admin/orders/{order}/payments/{payment}/reject","name":"admin.orders.payments.reject","middleware":["web","auth","active","tracked-session","permission:payments.manage"]}
ROUTE|{"methods":["POST"],"uri":"admin/orders/{order}/payments/{payment}/verify","name":"admin.orders.payments.verify","middleware":["web","auth","active","tracked-session","permission:payments.manage"]}
ROUTE|{"methods":["POST"],"uri":"admin/orders/{order}/payments/{payment}/void","name":"admin.orders.payments.void","middleware":["web","auth","active","tracked-session","permission:payments.manage"]}
ROUTE|{"methods":["GET"],"uri":"admin/receivables","name":"admin.receivables.index","middleware":["web","auth","active","tracked-session","permission:receivables.manage"]}
ROUTE|{"methods":["GET"],"uri":"admin/receivables/export.csv","name":"admin.receivables.csv","middleware":["web","auth","active","tracked-session","permission:receivables.manage","throttle:exports"]}
ROUTE|{"methods":["POST"],"uri":"admin/receivables/scan","name":"admin.receivables.scan","middleware":["web","auth","active","tracked-session","permission:receivables.manage"]}
ROUTE|{"methods":["PUT"],"uri":"admin/receivables/settings","name":"admin.receivables.settings.update","middleware":["web","auth","active","tracked-session","permission:receivables.manage"]}
ROUTE|{"methods":["GET"],"uri":"admin/receivables/{receivable}","name":"admin.receivables.show","middleware":["web","auth","active","tracked-session","permission:receivables.manage"]}
ROUTE|{"methods":["PATCH"],"uri":"admin/receivables/{receivable}","name":"admin.receivables.update","middleware":["web","auth","active","tracked-session","permission:receivables.manage"]}
ROUTE|{"methods":["POST"],"uri":"admin/receivables/{receivable}/contacts","name":"admin.receivables.contacts.store","middleware":["web","auth","active","tracked-session","permission:receivables.manage"]}
ROUTE|{"methods":["POST"],"uri":"admin/receivables/{receivable}/payments","name":"admin.receivables.payments.store","middleware":["web","auth","active","tracked-session","permission:receivables.manage","permission:payments.manage","throttle:admin-write"]}
ROUTE|{"methods":["PUT"],"uri":"admin/receivables/{receivable}/plan","name":"admin.receivables.plan","middleware":["web","auth","active","tracked-session","permission:receivables.manage"]}
ROUTE|{"methods":["POST"],"uri":"admin/receivables/{receivable}/reminder","name":"admin.receivables.reminder","middleware":["web","auth","active","tracked-session","permission:receivables.manage"]}
ROUTE|{"methods":["GET"],"uri":"admin/reports/payments.csv","name":"admin.reports.payments.csv","middleware":["web","auth","active","tracked-session","permission:reports.view","permission:reports.export","throttle:exports"]}
ROUTE|{"methods":["PATCH"],"uri":"api/v1/admin/orders/{order}/payment-status","name":"api.v1.admin.orders.payment-status","middleware":["api","auth:sanctum","active","permission:orders.manage","throttle:admin-write"]}
ROUTE|{"methods":["POST"],"uri":"api/v1/admin/orders/{order}/payments","name":"api.v1.admin.orders.payments.store","middleware":["api","auth:sanctum","active","permission:orders.manage","throttle:admin-write","permission:payments.manage"]}
ROUTE|{"methods":["POST"],"uri":"api/v1/admin/orders/{order}/payments/{payment}/reject","name":"api.v1.admin.orders.payments.reject","middleware":["api","auth:sanctum","active","permission:orders.manage","throttle:admin-write","permission:payments.manage"]}
ROUTE|{"methods":["POST"],"uri":"api/v1/admin/orders/{order}/payments/{payment}/verify","name":"api.v1.admin.orders.payments.verify","middleware":["api","auth:sanctum","active","permission:orders.manage","throttle:admin-write","permission:payments.manage"]}
ROUTE|{"methods":["POST"],"uri":"api/v1/admin/orders/{order}/payments/{payment}/void","name":"api.v1.admin.orders.payments.void","middleware":["api","auth:sanctum","active","permission:orders.manage","throttle:admin-write","permission:payments.manage"]}
ROUTE|{"methods":["GET"],"uri":"api/v1/admin/receivables","name":"api.v1.admin.receivables.index","middleware":["api","auth:sanctum","active","permission:receivables.manage"]}
ROUTE|{"methods":["GET"],"uri":"api/v1/admin/receivables/export.csv","name":"api.v1.admin.receivables.csv","middleware":["api","auth:sanctum","active","permission:receivables.manage","throttle:exports"]}
ROUTE|{"methods":["POST"],"uri":"api/v1/admin/receivables/scan","name":"api.v1.admin.receivables.scan","middleware":["api","auth:sanctum","active","permission:receivables.manage","throttle:admin-write"]}
ROUTE|{"methods":["PUT"],"uri":"api/v1/admin/receivables/settings","name":"api.v1.admin.receivables.settings.update","middleware":["api","auth:sanctum","active","permission:receivables.manage","throttle:admin-write"]}
ROUTE|{"methods":["GET"],"uri":"api/v1/admin/receivables/{receivable}","name":"api.v1.admin.receivables.show","middleware":["api","auth:sanctum","active","permission:receivables.manage"]}
ROUTE|{"methods":["PATCH"],"uri":"api/v1/admin/receivables/{receivable}","name":"api.v1.admin.receivables.update","middleware":["api","auth:sanctum","active","permission:receivables.manage","throttle:admin-write"]}
ROUTE|{"methods":["POST"],"uri":"api/v1/admin/receivables/{receivable}/contacts","name":"api.v1.admin.receivables.contacts.store","middleware":["api","auth:sanctum","active","permission:receivables.manage","throttle:admin-write"]}
ROUTE|{"methods":["POST"],"uri":"api/v1/admin/receivables/{receivable}/payments","name":"api.v1.admin.receivables.payments.store","middleware":["api","auth:sanctum","active","permission:receivables.manage","permission:payments.manage","throttle:admin-write"]}
ROUTE|{"methods":["PUT"],"uri":"api/v1/admin/receivables/{receivable}/plan","name":"api.v1.admin.receivables.plan","middleware":["api","auth:sanctum","active","permission:receivables.manage","throttle:admin-write"]}
ROUTE|{"methods":["POST"],"uri":"api/v1/admin/receivables/{receivable}/reminder","name":"api.v1.admin.receivables.reminder","middleware":["api","auth:sanctum","active","permission:receivables.manage","throttle:admin-write"]}
ROUTE|{"methods":["GET"],"uri":"api/v1/admin/reports/payments.csv","name":"api.v1.admin.reports.payments.csv","middleware":["api","auth:sanctum","active","permission:reports.view","permission:reports.export","throttle:exports"]}
ROUTE|{"methods":["POST"],"uri":"api/v1/orders/{order}/payments/proof","name":"api.v1.orders.payments.proof.store","middleware":["api","auth:sanctum","active","permission:payments.upload_proof","throttle:uploads"]}
ROUTE|{"methods":["GET"],"uri":"api/v1/orders/{order}/payments/{payment}/proof","name":"api.v1.orders.payments.proof","middleware":["api","auth:sanctum","active","permission:payments.view_own"]}
ROUTE|{"methods":["POST"],"uri":"orders/{order}/payments/proof","name":"orders.payments.proof.store","middleware":["web","auth","active","tracked-session","permission:payments.upload_proof","throttle:uploads"]}
ROUTE|{"methods":["GET"],"uri":"orders/{order}/payments/{payment}/proof","name":"orders.payments.proof","middleware":["web","auth","active","tracked-session","permission:payments.view_own"]}
PAYMENT_RECEIVABLE_ROUTE_COUNT=36

=== PAYMENT PERMISSIONS ===
PERMISSION|{"id":25,"slug":"payments.manage","name":"Upravljanje uplatama"}
PERMISSION|{"id":26,"slug":"payments.upload_proof","name":"Slanje potvrde o uplati"}
PERMISSION|{"id":27,"slug":"payments.view_own","name":"Pregled svojih uplata"}
PERMISSION|{"id":49,"slug":"receivables.manage","name":"Upravljanje potraživanjima"}
PAYMENT_PERMISSION_COUNT=4

=== ACTUAL ORDER PAYMENT DOMAINS ===
DOMAIN|orders.payment_method|{"value":"cash_on_delivery","count":8}
DOMAIN|orders.payment_method|{"value":"bank_transfer","count":5}
DOMAIN|orders.payment_method|{"value":"cash","count":24}
DOMAIN|orders.payment_method|{"value":"other","count":1}
DOMAIN|orders.payment_method|{"value":"deferred_payment","count":2}
DOMAIN|orders.payment_state|{"value":"overpaid","count":1}
DOMAIN|orders.payment_state|{"value":"paid","count":34}
DOMAIN|orders.payment_state|{"value":"partial","count":1}
DOMAIN|orders.payment_state|{"value":"unpaid","count":4}
DOMAIN|orders.payment_status|{"value":"pending","count":5}
DOMAIN|orders.payment_status|{"value":"paid","count":35}
DOMAIN|orders.source_system|{"value":"laravel","count":39}
DOMAIN|orders.source_system|{"value":"legacy","count":1}
DOMAIN|orders.sales_channel|{"value":"direct_sale","count":29}
DOMAIN|orders.sales_channel|{"value":"order","count":11}
DOMAIN|order_payments.status|{"value":"submitted","count":1}
DOMAIN|order_payments.status|{"value":"verified","count":40}
DOMAIN|order_payments.status|{"value":"voided","count":1}
DOMAIN|order_payments.entry_type|{"value":"payment","count":42}
DOMAIN|order_payments.payment_method|{"value":"bank_transfer","count":8}
DOMAIN|order_payments.payment_method|{"value":"cash","count":30}
DOMAIN|order_payments.payment_method|{"value":"cash_on_delivery","count":3}
DOMAIN|order_payments.payment_method|{"value":"other","count":1}

=== ORDER PAYMENT LEDGER AGGREGATES ===
ORDERS_TOTAL=40
ORDER_PAYMENTS_TOTAL=42
CANONICAL_LARAVEL_LEDGER_MISMATCH_COUNT=1
LARAVEL_LEDGER_MISMATCH|{"order_id":28,"source_system":"laravel","sales_channel":"order","order_status":"cancelled","payment_method":"cash_on_delivery","actual_paid_total":"0.00","verified_net":"0.00","actual_state":"unpaid","derived_state":"cancelled","actual_status":"pending","derived_status":"cancelled","payment_rows":0,"mismatch":["payment_state","payment_status"]}
LEGACY_LEDGER_DIVERGENCE_COUNT=0
NEGATIVE_VERIFIED_NET_ORDER_COUNT=0
NEGATIVE_VERIFIED_NET_ORDER_IDS=
REFUND_GROSS_EXCEEDS_PAYMENT_GROSS_COUNT=0
REFUND_GROSS_EXCEEDS_PAYMENT_GROSS_IDS=
PAYMENT_VERIFIED_AT_MISMATCH_COUNT=0

=== PAYMENT ROW LIFECYCLE INVARIANTS ===
PAYMENT_ROW_LIFECYCLE_ANOMALY_COUNT=0
SUBMITTED_PROOF_METADATA_MISSING_COUNT=0
SUBMITTED_PROOF_METADATA_MISSING_IDS=
PAYMENT_PROOF_FILE_MISSING_COUNT=0
PAYMENT_PROOF_FILE_MISSING_IDS=
MULTIPLE_SUBMITTED_ORDER_COUNT=0
EXACT_FULL_SUBMITTED_PAYMENT_COUNT=1
EXACT_FULL_SUBMITTED|{"payment_id":48,"order_id":132,"amount_rsd":"76603.67","remaining_before_submit_verification":"76603.67"}
OVER_REMAINING_SUBMITTED_PAYMENT_COUNT=0

=== REFUND SOURCE INVENTORY ===
REFUND_ROW_COUNT=0
GENERIC_REFUND_WITHOUT_AFTER_SALES_ACTION_COUNT=0

=== RECEIVABLE CONSISTENCY ===
RECEIVABLE_CASE_COUNT=4
RECEIVABLE_PAYMENT_ALLOCATION_COUNT=3
RECEIVABLE_STATE_MISMATCH_COUNT=0
RECEIVABLE_ALLOCATION_ANOMALY_COUNT=0

=== IPS CACHE CONSISTENCY ===
IPS_CACHE_ROW_COUNT=2
IPS_TERMINAL_STATE_CACHE_COUNT=0
IPS_ZERO_OUTSTANDING_CACHE_COUNT=0
IPS_NON_BANK_TRANSFER_CACHE_COUNT=0

=== AUDIT LOG PAYMENT HISTORY ===
AUDIT_ACTION|{"action":"order.payment_proof_submitted","count":1}
AUDIT_ACTION|{"action":"order.payment_recorded","count":10}
AUDIT_ACTION|{"action":"order.payment_status_changed","count":1}
AUDIT_ACTION|{"action":"order.payment_voided","count":1}
AUDIT_ACTION|{"action":"receivable.automatic_reminder_queued","count":2}
AUDIT_ACTION|{"action":"receivable.created","count":4}
AUDIT_ACTION|{"action":"receivable.plan_created","count":2}
AUDIT_ACTION|{"action":"receivable.updated","count":2}
LEGACY_PAYMENT_STATUS_AUDIT_COUNT=1
LEGACY_PAYMENT_STATUS_CHANGE|{"audit_id":684,"user_id":1,"order_id":24,"before":{"payment_status":"pending"},"after":{"payment_status":"paid"},"created_at":"2026-09-10 19:18:04"}

=== ORDER 132 / PAYMENT 48 FORENSIC SNAPSHOT ===
ORDER132|{"id":132,"order_number":"APC-20260929-00000132","source_system":"laravel","sales_channel":"order","status":"shipped","completed_at":null,"payment_method":"bank_transfer","payment_status":"pending","payment_state":"unpaid","subtotal_rsd":"76603.67","paid_total_rsd":"0.00","payment_verified_at":null,"bank_snapshot_present":true,"bank_snapshot_digits":18,"recipient_snapshot_present":true,"payment_code_snapshot_present":true,"reference_snapshot_present":true}
ORDER132_PAYMENT|{"id":48,"payment_number":"UPL-2026-000042","entry_type":"payment","status":"submitted","amount_rsd":"76603.67","payment_method":"bank_transfer","proof_present":true,"proof_exists":true,"submitted_by":2,"verified_by":null,"verified_at":null,"rejected_by":null,"rejected_at":null,"voided_by":null,"voided_at":null}
ORDER132_PAYMENT|{"id":54,"payment_number":"UPL-2026-000043","entry_type":"payment","status":"voided","amount_rsd":"76603.00","payment_method":"bank_transfer","proof_present":false,"proof_exists":false,"submitted_by":1,"verified_by":1,"verified_at":"2026-10-03 08:10:49","rejected_by":null,"rejected_at":null,"voided_by":1,"voided_at":"2026-10-03 08:10:59"}
ORDER132_LEDGER|{"verified_payment_gross":"0.00","verified_refund_gross":"0.00","verified_net":"0.00","remaining":"76603.67"}
PAYMENT48_PRESENT=YES
PAYMENT48_IS_EXACT_FULL_SUBMITTED=YES
PAYMENT54_PRESENT=YES
PAYMENT54_NATURAL_EXPERIMENT|{"order_id":132,"status":"voided","amount_rsd":"76603.00","verified_at":"2026-10-03 08:10:49","voided_at":"2026-10-03 08:10:59"}

=== HIGH LEVEL FINDING COUNTS ===
FINDING_CANONICAL_LARAVEL_LEDGER_MISMATCH=1
FINDING_LEGACY_LEDGER_DIVERGENCE=0
FINDING_PAYMENT_ROW_LIFECYCLE_ANOMALY=0
FINDING_PROOF_METADATA_MISSING=0
FINDING_PROOF_FILE_MISSING=0
FINDING_MULTIPLE_SUBMITTED_ORDER=0
FINDING_EXACT_FULL_SUBMITTED=1
FINDING_OVER_REMAINING_SUBMITTED=0
FINDING_NEGATIVE_VERIFIED_NET=0
FINDING_REFUND_EXCEEDS_PAYMENTS=0
FINDING_GENERIC_REFUND_WITHOUT_AFTER_SALES=0
FINDING_RECEIVABLE_STATE_MISMATCH=0
FINDING_RECEIVABLE_ALLOCATION_ANOMALY=0
FINDING_IPS_TERMINAL_CACHE=0
FINDING_IPS_ZERO_OUTSTANDING_CACHE=0
FINDING_IPS_NON_BANK_TRANSFER_CACHE=0
FINDING_LEGACY_PAYMENT_STATUS_WRITES=1
DATABASE_WRITE_PERFORMED=NO
```

## Source architecture inventory
```text
=== PAYMENT WRITERS / STATE WRITERS ===
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:344:            'paid_total_rsd' => (float) ($order?->paid_total_rsd ?? 0),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:345:            'payment_state' => (string) ($order?->payment_state ?? 'unpaid'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:256:            'payment_status' => $deferred ? 'pending' : 'paid',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:257:            'payment_state' => $deferred ? 'unpaid' : 'paid',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:258:            'paid_total_rsd' => $deferred ? 0 : $lineTotal,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:324:            OrderPayment::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:368:                'payment_state' => $deferred ? 'unpaid' : 'paid',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:55:                $payment = OrderPayment::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:120:            $payment = OrderPayment::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:175:        $payment = OrderPayment::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:303:            'paid_total_rsd' => round($net, 2),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:304:            'payment_state' => $state,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:305:            'payment_status' => $state === 'cancelled' ? 'cancelled' : ($state === 'refunded' ? 'refunded' : (in_array($state, ['paid', 'overpaid'], true) ? 'paid' : 'pending')),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:308:        $this->ips->persist($order->fresh());
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:316:                'paid_total_rsd' => $paid,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:321:                'payment_status' => $this->text($order, 'payment_status', 'pending'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:322:                'payment_state' => $paymentState,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:183:            $locked->update(['payment_status' => $paymentStatus, 'updated_by' => $actor->id]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:184:            $this->audit->log('order.payment_status_changed', 'Promenjen status plaćanja '.$locked->order_number, $locked, ['payment_status' => $before], ['payment_status' => $paymentStatus], user: $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:191:        $this->emails->orderChanged($updated, 'order_payment_changed', 'Promenjen status plaćanja '.$updated->order_number, 'Status plaćanja je '.$updated->payment_status.'.', ['payment_status' => $updated->payment_status, 'actor_id' => $actor->id, 'changed_at' => $updated->updated_at?->toISOString()]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:354:                    $payment = OrderPayment::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:391:                    'paid_total_rsd' => round($net, 2),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:392:                    'payment_state' => $state,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:393:                    'payment_status' => 'paid',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:398:                $this->ips->persist($locked->fresh());
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:403:                    before: ['status' => $oldStatus, 'payment_state' => $oldPaymentState, 'completed_at' => null],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:406:                        'payment_state' => $state,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:100:            'payment_status' => 'pending',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSalePriceCorrectionService.php:102:                'paid_total_rsd' => $newTotal,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSalePriceCorrectionService.php:104:                'payment_status' => 'paid',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSalePriceCorrectionService.php:105:                'payment_state' => 'paid',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Order.php:45:            'paid_total_rsd' => 'decimal:2',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/OrderResource.php:25:            'payment_status' => $this->payment_status,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportController.php:209:            'payment_status' => ['nullable', 'in:pending,paid,cancelled'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:111:            $ipsPayload = $ips->persist($order);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:274:        $data = $request->validate(['payment_status' => ['required', Rule::in(['pending', 'paid', 'cancelled'])]]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:329:            'payment_status' => ['nullable', Rule::in(['pending', 'paid', 'cancelled'])],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReportController.php:194:            'payment_status' => ['nullable', 'in:pending,paid,cancelled'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:393:                'payment_state' => (string) $order->payment_state,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php:46:            'payment_status' => ['nullable', Rule::in(self::PAYMENT_STATUSES)],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php:157:            'payment_status' => $this->nullableString($order->getAttribute('payment_status')),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php:158:            'payment_state' => $this->nullableString($order->getAttribute('payment_state')),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php:233:            'payment_status' => $this->nullableString($validated['payment_status'] ?? null),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php:120:            'payment_status' => ['required', Rule::in(['pending', 'paid', 'cancelled'])],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php:124:        return $this->ok($updated, 'payment_status', ['payment_status' => (string) $updated->payment_status]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php:160:            'payment_status' => (string) $updated->payment_status,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php:199:            'paid_total_rsd' => round((float) $updated->paid_total_rsd, 2),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php:225:            'payment_status' => (string) $payment->status,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php:243:            'payment_status' => (string) $updated->status,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php:262:            'payment_status' => (string) $updated->status,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php:280:            'payment_status' => (string) $updated->status,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php:317:                'payment_status' => $fresh->payment_status !== null ? (string) $fresh->payment_status : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php:318:                'payment_state' => $fresh->payment_state !== null ? (string) $fresh->payment_state : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReceivablesController.php:502:            'payment_state' => $order->payment_state,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReceivablesController.php:504:            'paid_total_rsd' => (float) $order->paid_total_rsd,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/OrderController.php:137:                    'payment_status' => (string) ($order->payment_status ?: 'pending'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/OrderController.php:138:                    'payment_state' => $paymentState,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/OrderController.php:140:                    'paid_total_rsd' => $paid,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:155:            $ipsPayload = $ips->persist($order);

=== LEGACY PAYMENT STATUS WORKFLOW ===
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:76:            'payment_store' => $this->route('admin.orders.payments.store', ['order' => $id]),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:157:            $payment['verify_url'] = $this->route('admin.orders.payments.verify', ['order' => $id, 'payment' => $paymentId]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:158:            $payment['reject_url'] = $this->route('admin.orders.payments.reject', ['order' => $id, 'payment' => $paymentId]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:159:            $payment['void_url'] = $this->route('admin.orders.payments.void', ['order' => $id, 'payment' => $paymentId]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:122:                    'payment_label' => $this->paymentStatus((string) ($order->payment_state ?: $order->payment_status)),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:339:    private function paymentStatus(string $status): string
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:174:    public function updatePaymentStatus(Order $order, string $paymentStatus, User $actor): Order
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:275:        $workflow->updatePaymentStatus($order, (string) $data['payment_status'], $request->user());
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php:111:    public function paymentStatus(
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php:122:        $updated = $workflow->updatePaymentStatus($order, (string) $data['payment_status'], $actor);
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:256:                        Route::patch('/orders/{order}/payment-status', [AdminOrderMutationController::class, 'paymentStatus'])->whereNumber('order')->name('orders.payment-status');

=== ERROR BAG OVERRIDES ===
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportController.php:279:                view()->share('errors', new ViewErrorBag());
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:341:            $html = view('admin.orders.index', $data)->with('errors', new ViewErrorBag())->render();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:363:            $html = view('admin.orders.show', $data)->with('errors', new ViewErrorBag())->render();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:171:                ->with('errors', new ViewErrorBag())

=== IPS PERSIST CALL SITES ===
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:22:        private readonly IpsPaymentPayloadService $ips,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:308:        $this->ips->persist($order->fresh());
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:38:        private readonly IpsPaymentPayloadService $ips,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:398:                $this->ips->persist($locked->fresh());
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/IpsPaymentPayloadService.php:14:final class IpsPaymentPayloadService
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/NbsIpsQrService.php:19:    public function __construct(private readonly IpsPaymentPayloadService $payloads) {}
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:10:use App\Services\IpsPaymentPayloadService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:78:        IpsPaymentPayloadService $ips,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:111:            $ipsPayload = $ips->persist($order);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:13:use App\Services\IpsPaymentPayloadService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:130:        IpsPaymentPayloadService $ips,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:155:            $ipsPayload = $ips->persist($order);

=== PAYMENT FEATURE TEST SIGNALS ===
34:    public function test_customer_proof_can_be_verified_and_updates_order_balance(): void
81:    public function test_assigned_admin_can_record_payment_from_order_workspace(): void
116:    public function test_assigned_admin_can_record_payment_for_imported_completed_order(): void
153:    public function test_admin_can_complete_cod_order_and_lock_all_further_financial_actions(): void
193:        $lockedResponse->assertRedirect()->assertSessionHasErrors('amount_rsd');

=== OPENAPI PAYMENT CONTRACT SIGNALS ===
2672:  /api/v1/admin/orders/{order}/payment-status:
2676:      summary: Update legacy order payment status
2771:  /api/v1/admin/orders/{order}/payments:
2798:  /api/v1/admin/orders/{order}/payments/{payment}/verify:
2804:      operationId: adminOrdersPaymentVerify
2813:  /api/v1/admin/orders/{order}/payments/{payment}/reject:
2819:      operationId: adminOrdersPaymentReject
2837:  /api/v1/admin/orders/{order}/payments/{payment}/void:
2843:      operationId: adminOrdersPaymentVoid
```

## Test execution inventory
```text
PHPUNIT_VENDOR_BINARY=ABSENT
GITHUB_WORKFLOW_DIR=ABSENT
GITHUB_WORKFLOW_FILE_COUNT=0
COMPOSER_PHPUNIT_DECLARED=^12.5.12
COMPOSER_TEST_SCRIPT=["@php artisan config:clear --ansi @no_additional_args","@php artisan test"]
COMPOSER_TEST_PRODUCTION=["@lint","@autoload:check","@php artisan test --testsuite=Feature"]
app:test-database-doctor          Bezbedno proveri izolovanu MySQL test bazu
```

## Payments / inventory doctor
```text
PASS PDF dokumenti, Payments, IPS i Advanced Inventory šema su kompletni.
PASS Tip dokumenta podržava PDF otpremnicu.
PASS Stornirani dokumenti mogu dobiti novu reviziju.
PASS Payment i inventory dozvole postoje.
PASS SQL upiti su uspešni. Uplate=42, ulazi=1, popisi=0, nizak lager=14.
```

## CMS static summary

```text
PASS v2.2.0 uređaji deduplikuju push tokene i podržavaju opoziv PASS v2.2.0 API greške imaju stabilan envelope PASS v2.2.0 OpenAPI i Stable doctor su povezani Ukupno: 983, neuspešno: 0 
```

## Audit immutability

- HEAD_AFTER: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- ORIGIN_MAIN_AFTER: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- HTACCESS_SHA256_AFTER: d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
- TRACKED_WORKTREE_UNCHANGED: YES
- DATABASE_WRITE_PERFORMED: NO

## Decision gate

No payment source mutation is authorized by this batch.
The next architecture repair must be designed from the findings in this report, not from a single suspected symptom.
