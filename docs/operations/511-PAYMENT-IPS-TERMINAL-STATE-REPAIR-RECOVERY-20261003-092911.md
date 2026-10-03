# Batch 511R - Payment / IPS report recovery

- RESULT: REVIEW_REQUIRED
- TIMESTAMP: 20261003-092911
- MODE: READ_ONLY_APPLICATION_DB_INSPECTION_PLUS_REPORT_WRITE
- REPORT_PATH: /home/icaffeco/ald1n-project/docs/operations/511-PAYMENT-IPS-TERMINAL-STATE-REPAIR-RECOVERY-20261003-092911.md
- CURRENT_BRANCH: main
- HEAD: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- ORIGIN_MAIN: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- EXPECTED_BATCH510_BASE: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- BATCH511_SOURCE_APPLIED: NO
- DATABASE_WRITE_PERFORMED_BY_511R: NO
- PAYMENT48_MUTATED_BY_511R: NO
- MIGRATION_RUN: NO
- COMPOSER_INSTALL_RUN: NO
- EAS_BUILD_STARTED: NO
- OTA_PUBLISHED: NO
- GOOGLE_PLAY_ACTION: NO

## Source verification

- PHP_LINT: PASS
- GIT_DIFF_CHECK: PASS
- IPS_PAYMENT_STATE_SMOKE: PASS
- CMS_STATIC_CHECK: PASS_983_983
- IPS_SERVICE_SHA256: bf3f068297a6842d776f950fb6229f40e4b6a4d2dcb77320955da4fd6eda07fe
- STATIC_CHECK_SHA256: 734fd9e0f8d694768aac5ef6cb5d3a3e120a9cdb2c63accbb92e06ed67b8aac8

### Target diff name/status

```text

```

### Target diff stat

```text

```

## Order 132 / Payment 48 read-only snapshot

```text
SNAPSHOT_RESULT=PASS
ORDER132_ORDER_NUMBER=APC-20260929-00000132
ORDER132_STATUS=shipped
ORDER132_PAYMENT_METHOD=bank_transfer
ORDER132_PAYMENT_STATUS=pending
ORDER132_PAYMENT_STATE=unpaid
ORDER132_SUBTOTAL=76603.67
ORDER132_PAID_TOTAL=0.00
ORDER132_VERIFIED_NET=0.00
ORDER132_REMAINING=76603.67
ORDER132_PAYMENT_VERIFIED_AT=NULL
PAYMENT48_STATUS=submitted
PAYMENT48_AMOUNT=76603.67
PAYMENT48_ENTRY_TYPE=payment
PAYMENT48_VERIFIED_AT=NULL
PAYMENT48_PROOF_EXISTS=YES
IPS_CACHE_ROWS_ORDER132=1
IPS_CACHE_STATUS_ORDER132=ready
IPS_CACHE_PAYLOAD_PRESENT_ORDER132=YES
```

## IPS smoke

```text
PASS IPS payment-state financial document policy
PAID_BANK_TRANSFER_QR=SKIP
PARTIAL_BANK_TRANSFER_QR=REQUIRED
PARTIAL_QR_AMOUNT=7500.00_RSD_OUTSTANDING_ONLY
```

## CMS static summary

```text
PASS  v2.2.0 uređaji deduplikuju push tokene i podržavaju opoziv
PASS  v2.2.0 API greške imaju stabilan envelope
PASS  v2.2.0 OpenAPI i Stable doctor su povezani

Ukupno: 983, neuspešno: 0
```

## Decision gate

- If BATCH511_SOURCE_APPLIED=YES and all source/static/smoke checks PASS, do not rerun Batch 511.
- If PAYMENT48_STATUS=submitted, the next step is the explicitly authorized manual verification of payment 48 followed immediately by a read-only acceptance audit.
- If PAYMENT48_STATUS=verified, skip manual verification and run only the read-only acceptance audit.
- If BATCH511_SOURCE_APPLIED=NO or any gate is FAIL, stop before payment mutation and inspect this report.
