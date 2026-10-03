# Batch 507 - Payment Verify IPS Terminal-State Fix

- RESULT: FAIL
- TIMESTAMP: 20261003-082758
- PRE_HEAD: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- SOURCE_COMMIT: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- ORIGIN_MAIN: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- FINAL_COMMIT: NONE
- FAILED_STAGE: pre-fix-regression
- FAILURE_REASON: unexpected rc=3 line=205
- MUTATED: NO
- COMMITTED: NO
- PUSHED: NO
- ROLLBACK: NOT_NEEDED
- DATABASE_CHANGED: NO
- MIGRATION_RUN: NO
- EAS_BUILD_STARTED: NO
- OTA_PUBLISHED: NO
- GOOGLE_PLAY_ACTION: NO
- ORDER132_PAYMENT48_MANUALLY_MUTATED: NO

## Root cause

OrderPaymentService verifies the submitted payment, recalculates the order to paid, then calls
IpsPaymentPayloadService::persist().

Before Batch507, persist() called strict payload() before its try/catch. A fully paid bank-transfer
order has zero outstanding amount, so payload() raised:
"NBS IPS QR zahteva iznos veći od nule."

Because this happened inside the payment DB transaction, the payment verification was rolled back.

## Fix contract

- strict payload() behavior remains unchanged
- persist() skips implicit refresh for terminal payment states:
  paid, overpaid, cancelled, refunded
- explicit amountRsd calls remain strict
- partial/unpaid bank-transfer QR behavior remains unchanged

## Gates

- source authority: PASS head=4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5 known_htaccess_only
- pre-fix regression reproduction: NOT_RUN
- PHP lint: NOT_RUN
- post-fix regression: NOT_RUN
- payment feature regression: NOT_RUN
- unpaid NBS document regression: NOT_RUN
- IPS payment-state smoke: NOT_RUN
- CMS static: NOT_RUN
- source scope: NOT_RUN
- push verification: NOT_RUN

## Post-deploy verification

After PASS, manually click "Potvrdi" for existing payment 48 on order 132.
Expected result:
- payment 48 -> verified
- paid_total_rsd -> 76603.67
- payment_state -> paid
- payment_status -> paid

No direct database repair is performed by this batch.
