# Batch 509 - Order132 Payment Verify IPS Final Fix

- RESULT: FAIL
- TIMESTAMP: 20261003-085905
- PRE_HEAD: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- SOURCE_COMMIT: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- ORIGIN_MAIN: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- FINAL_COMMIT: NONE
- FAILED_STAGE: ips-production-smoke
- FAILURE_REASON: IPS payment-state smoke rc=255
- MUTATED: YES
- COMMITTED: NO
- PUSHED: NO
- ROLLBACK: SOURCE_RESTORED
- DATABASE_PERSISTED_CHANGE_DURING_BATCH: NO
- MIGRATION_RUN: NO
- COMPOSER_INSTALL_RUN: NO
- EAS_BUILD_STARTED: NO
- OTA_PUBLISHED: NO
- GOOGLE_PLAY_ACTION: NO
- ORDER132_PAYMENT48_PERSISTENT_MUTATION_DURING_BATCH: NO

## Audited root cause

The original payment ledger contract verifies a submitted payment and recalculates the order atomically.

For a full bank-transfer payment, recalculateLocked() sets the order to paid and then calls
IpsPaymentPayloadService::persist().

Before Batch509, implicit persist() called strict payload() first. Strict payload() correctly rejects a
zero outstanding QR amount, but that exception occurred inside the payment DB transaction and rolled the
payment verification back.

A second UX issue hid the real validation message: Admin OrderController renderShowProtected() explicitly
overwrote flashed validation errors with a new empty ViewErrorBag, so the browser appeared to just refresh.

## Fix

1. IpsPaymentPayloadService::persist()
   - implicit persist is a no-op for paid, overpaid, cancelled and refunded states
   - payload() remains strict
   - explicit positive amount payload remains supported
   - partial/unpaid behavior remains unchanged

2. Admin OrderController
   - order detail rendering no longer replaces flashed validation errors with an empty error bag

3. IPS payment-state smoke
   - permanently covers paid implicit persist skip
   - verifies zero-outstanding payload is still strictly rejected
   - verifies explicit positive payload remains allowed
   - preserves partial outstanding-only QR behavior

## Gates

- source authority: PASS head=4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5 source_clean_except_known_htaccess
- pre-fix runtime proof: PASS real_order132_snapshot_reproduces_zero_outstanding_validation
- PHP lint: PASS all_three_files
- IPS production smoke: NOT_RUN
- order132/payment48 rollback regression: NOT_RUN
- payments/inventory doctor: NOT_RUN
- CMS static: NOT_RUN
- source scope: NOT_RUN
- push verification: NOT_RUN

## Acceptance after PASS

Manually click "Potvrdi" for payment 48 / UPL-2026-000042 on order 132.

Expected persistent state:
- payment 48 status = verified
- order paid_total_rsd = 76603.67
- order payment_state = paid
- order payment_status = paid

Batch509 itself does not persist that business transition; the real admin confirmation remains the
authoritative action.
