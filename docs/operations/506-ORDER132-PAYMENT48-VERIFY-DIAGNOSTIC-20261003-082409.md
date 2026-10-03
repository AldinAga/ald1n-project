# Batch 506 - Order132 Payment48 Verify Diagnostic

- RESULT: PASS_READ_ONLY_DIAGNOSTIC
- TIMESTAMP: 20261003-082409
- SOURCE_COMMIT: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- ORIGIN_MAIN: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- ORDER_ID: 132
- PAYMENT_ID: 48
- SOURCE_CHANGED: NO
- DATABASE_CHANGED: NO
- EAS_BUILD_STARTED: NO
- OTA_PUBLISHED: NO
- GOOGLE_PLAY_ACTION: NO

## Routes
```text

  POST       admin/orders/{order}/payments ..................................................................... admin.orders.payments.store › Admin\PaymentController@store
  POST       admin/orders/{order}/payments/{payment}/reject .................................................. admin.orders.payments.reject › Admin\PaymentController@reject
  POST       admin/orders/{order}/payments/{payment}/verify .................................................. admin.orders.payments.verify › Admin\PaymentController@verify
  POST       admin/orders/{order}/payments/{payment}/void ........................................................ admin.orders.payments.void › Admin\PaymentController@void
  POST       api/v1/admin/orders/{order}/payments ................................... api.v1.admin.orders.payments.store › Api\V1\Admin\OrderMutationController@paymentStore
  POST       api/v1/admin/orders/{order}/payments/{payment}/reject ................ api.v1.admin.orders.payments.reject › Api\V1\Admin\OrderMutationController@paymentReject
  POST       api/v1/admin/orders/{order}/payments/{payment}/verify ................ api.v1.admin.orders.payments.verify › Api\V1\Admin\OrderMutationController@paymentVerify
  POST       api/v1/admin/orders/{order}/payments/{payment}/void ...................... api.v1.admin.orders.payments.void › Api\V1\Admin\OrderMutationController@paymentVoid

                                                                                                                                                          Showing [8] routes

```

## Database inspection
```text
=== ORDER ===
{
    "id": 132,
    "order_number": "APC-20260929-00000132",
    "source_system": "laravel",
    "sales_channel": "order",
    "status": "shipped",
    "completed_at": null,
    "payment_method": "bank_transfer",
    "subtotal_rsd": "76603.67",
    "paid_total_rsd": "0.00",
    "payment_state": "unpaid",
    "payment_status": "pending",
    "payment_verified_at": null,
    "archived_at": null,
    "reopened_at": null
}

=== TARGET PAYMENT ===
{
    "id": 48,
    "order_id": 132,
    "payment_number": "UPL-2026-000042",
    "entry_type": "payment",
    "status": "submitted",
    "amount_rsd": "76603.67",
    "payment_method": "bank_transfer",
    "paid_at": "2026-10-01 19:09:00",
    "proof_path": "[PRESENT]",
    "proof_original_name": "aldin hpg11.pdf",
    "proof_mime_type": "application/pdf",
    "proof_size_bytes": 57329,
    "submitted_by": 2,
    "verified_by": null,
    "verified_at": null,
    "rejected_by": null,
    "rejected_at": null,
    "rejection_reason": null,
    "voided_by": null,
    "voided_at": null,
    "created_at": "2026-10-01 19:11:24",
    "updated_at": "2026-10-01 19:11:24",
    "proof_exists": true
}

=== ALL ORDER PAYMENTS ===
[
    {
        "id": 48,
        "payment_number": "UPL-2026-000042",
        "entry_type": "payment",
        "status": "submitted",
        "amount_rsd": "76603.67",
        "payment_method": "bank_transfer",
        "paid_at": "2026-10-01 19:09:00",
        "submitted_by": 2,
        "verified_by": null,
        "verified_at": null,
        "rejected_by": null,
        "rejected_at": null,
        "voided_by": null,
        "voided_at": null
    },
    {
        "id": 54,
        "payment_number": "UPL-2026-000043",
        "entry_type": "payment",
        "status": "voided",
        "amount_rsd": "76603.00",
        "payment_method": "bank_transfer",
        "paid_at": "2026-10-03 08:10:00",
        "submitted_by": 1,
        "verified_by": 1,
        "verified_at": "2026-10-03 08:10:49",
        "rejected_by": null,
        "rejected_at": null,
        "voided_by": 1,
        "voided_at": "2026-10-03 08:10:59"
    }
]

=== REQUIRED COLUMNS ===
orders=YES
  completed_at=YES
  sales_channel=YES
  payment_method=YES
  paid_total_rsd=YES
  payment_state=YES
  payment_status=YES
order_payments=YES
  status=YES
  verified_by=YES
  verified_at=YES
  rejected_by=YES
  rejected_at=YES
  rejection_reason=YES
  voided_by=YES
  voided_at=YES

=== READ-ONLY LEDGER CALCULATION ===
verified_net_now=0.00
target_signed=76603.67
hypothetical_net_after_verify=76603.67
order_total=76603.67
hypothetical_payment_state=paid

=== ASSERT_ORDER_OPEN PREDICTION ===
WOULD_ALLOW=YES
```

## Relevant Laravel log
```text
laravel.log missing
```
