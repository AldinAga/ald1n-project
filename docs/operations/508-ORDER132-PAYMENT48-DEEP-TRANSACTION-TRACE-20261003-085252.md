# Batch 508 - Order132 Payment48 Deep Transaction Trace

- RESULT: PASS_READ_ONLY_DEEP_TRACE
- TIMESTAMP: 20261003-085252
- SOURCE_COMMIT: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- ORIGIN_MAIN: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- ORDER_ID: 132
- PAYMENT_ID: 48
- SOURCE_CHANGED: NO
- DATABASE_PERSISTED_CHANGE: NO
- MIGRATION_RUN: NO
- COMPOSER_INSTALL_RUN: NO
- EAS_BUILD_STARTED: NO
- OTA_PUBLISHED: NO
- GOOGLE_PLAY_ACTION: NO

## Deep trace
```text
=== AUTHORITY ===
APP_ENV=production
DB_DRIVER=mysql
TRANSACTION_LEVEL_INITIAL=0

=== CURRENT ORDER ===
{
    "id": 132,
    "number": "APC-20260929-00000132",
    "status": "shipped",
    "completed_at": null,
    "payment_method": "bank_transfer",
    "subtotal_rsd": "76603.67",
    "paid_total_rsd": "0.00",
    "payment_state": "unpaid",
    "payment_status": "pending",
    "payment_verified_at": null,
    "bank_account_snapshot_present": true,
    "bank_account_snapshot_digits": 18,
    "recipient_snapshot_present": true,
    "payment_code_snapshot_present": true,
    "reference_snapshot_present": true
}

=== CURRENT PAYMENT ===
{
    "id": 48,
    "order_id": 132,
    "number": "UPL-2026-000042",
    "status": "submitted",
    "entry_type": "payment",
    "amount_rsd": "76603.67",
    "verified_by": null,
    "verified_at": null,
    "rejected_by": null,
    "rejected_at": null,
    "voided_by": null,
    "voided_at": null
}
PROOF_EXISTS=YES

=== CURRENT IPS CACHE ===
{
    "table": true,
    "row": true,
    "status": "ready",
    "payload_sha256": "462b87a09ea05cc0f6384d214e9d26e86e74a34cd880cd36cd7eaa1695c4308d",
    "error_message": null,
    "generated_at": "2026-10-03 08:11:44",
    "updated_at": "2026-10-03 08:11:44"
}

=== HISTORICAL PAYMENT ROWS ===
[
    {
        "id": 48,
        "order_id": 132,
        "number": "UPL-2026-000042",
        "status": "submitted",
        "entry_type": "payment",
        "amount_rsd": "76603.67",
        "verified_by": null,
        "verified_at": null,
        "rejected_by": null,
        "rejected_at": null,
        "voided_by": null,
        "voided_at": null
    },
    {
        "id": 54,
        "order_id": 132,
        "number": "UPL-2026-000043",
        "status": "voided",
        "entry_type": "payment",
        "amount_rsd": "76603.00",
        "verified_by": 1,
        "verified_at": "2026-10-03 08:10:49",
        "rejected_by": null,
        "rejected_at": null,
        "voided_by": 1,
        "voided_at": "2026-10-03 08:10:59"
    }
]

=== PURE IPS ZERO-OUTSTANDING PROBE (NO DB WRITE) ===
PURE_IPS_RESULT=VALIDATION_EXCEPTION
PURE_IPS_ERRORS={"ips_qr":["NBS IPS QR zahteva iznos veći od nule."]}

=== ROLLBACK TRANSACTION TRACE ===
TRACE_LOCKED_PAYMENT_STATUS=submitted
TRACE_LOCKED_ORDER_STATE=unpaid
TRACE_FAILURE_STAGE=RECALCULATE_LOCKED
TRACE_EXCEPTION=ErrorException
TRACE_MESSAGE=Cannot bind an instance to a static closure
TRACE_RESULT=THROWABLE
TRANSACTION_LEVEL_AFTER_ROLLBACK=0

=== POST-ROLLBACK IMMUTABILITY ===
PAYMENT_RESTORED=YES
ORDER_RESTORED=YES
AUDIT_COUNT_RESTORED=YES
IPS_CACHE_RESTORED=YES
AUDIT_COUNT_BEFORE=0
AUDIT_COUNT_AFTER=0

=== DIAGNOSTIC CONCLUSION ===
INNER_TRANSACTION_FAILURE_PROVEN=INDETERMINATE
DATABASE_PERSISTED_CHANGE=NO
```

## Interpretation
- ROOT_CAUSE: PROVEN_IPS_ZERO_OUTSTANDING_INSIDE_PAYMENT_RECALCULATION

## Immutability
PAYMENT_RESTORED=YES
ORDER_RESTORED=YES
AUDIT_COUNT_RESTORED=YES
IPS_CACHE_RESTORED=YES
DATABASE_PERSISTED_CHANGE=NO
