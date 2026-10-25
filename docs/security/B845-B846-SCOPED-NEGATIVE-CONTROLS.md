# B845-B846: registered-buyer replay negative controls and scoped evidence

Date: 2026-10-25. **Source-only isolated PR; no production changes.**

## Baseline

Parent source: `acb8b9c42ec9254b1a27567abca29a26c1f733f8` (PR93 draft and storefront B798 OFF). GitHub main at batch intake: `09c7176f1d96a46c649a98fbc551cd4d71c5b37f`. Report844 proved existing B843 replay regression 5 tests / 127 assertions and B836 boundary 15 tests / 78 assertions on synthetic PHP8.4/MariaDB10.11.19. These are not full WSTG/ASVS penetration results. Forbidden B805/B809/B815 paths untouched. PR82 native emulator 16KiB PASS remains separate; signed production AAB, physical device and Play are HOLD. M17 Expo SDK58 not started.

## B845 — six new negative controls (SEC06, SEC19, SEC20)

1. Immutable idempotency fingerprint rejects a changed delivery address with the same key.
2. Empty encrypted receipt ciphertext cannot mint another access token.
3. Substituted receipt-token hash cannot replay a receipt.
4. Reassigned order owner cannot disclose private receipt to the original buyer.
5. Blocked buyer cannot replay a completed purchase.
6. Operator storefront emergency OFF stops a replay with HTTP 404.

Each synthetic test verifies exactly one order, attempt, access record, stock movement and unchanged remaining inventory (4 → 3). These tests extend the existing isolated B843 fixture; they do not change the checkout service, payment, mailer or production data.

## B846 — fail-closed evidence admission

The CI job reruns the six new and five existing B843 tests plus 15 B836 receipt-boundary tests with actual MariaDB, and parses both real JUnit files. The evidence compiler checks exactly 11 + 15 tests, six named negative controls, nonduplicate results, and rejects failures, errors, skipped cases, malformed XML, and commit-ID mismatch. It emits a JSON provenance receipt with the exact source SHA, JUnit SHA-256 hashes and observed count. **This is a scoped synthetic regression PASS only**; verified OWASP pass remains zero, independent penetration review remains NOT_RUN, storefront release remains HOLD. No native build or SSH is required.

## Follow-on release gates

The 368-item B844 control census remains individually UNASSESSED/NOT_RUN. Independent applicability mapping and manual/browser/isolated penetration testing are required; full legacy CI retains inherited failures. Merchant payment/shipping/legal policy, actual SMTP, hosting backup/restore/FPM evidence, physical buyer UAT and authorized source/rollback cutover are still required before public ON. Android M16 separately needs a newly authorized EAS-signed production AAB with versionCode greater than 27, artifact provenance/signing/16 KiB checks, physical device UAT and Google Play approval. Do not merge/deploy merely because this isolated test PR is green.
