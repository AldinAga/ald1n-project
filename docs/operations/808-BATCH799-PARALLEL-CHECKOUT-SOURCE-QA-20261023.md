# Report808 — B799 real MariaDB concurrency and guest lifecycle source QA (2026-10-23)

**Authority type:** exact-source disposable-CI QA; **NOT a CloudLinux production receipt**.

| Evidence | Current value |
|---|---|
| GitHub main before this docs checkpoint | `82b67b0cfbdd94d90ea51bbcd148ab42f79cfee3` (docs only) |
| Draft source PR | [#93](https://github.com/AldinAga/ald1n-project/pull/93) |
| Exact candidate head | `6da19a7dfc551e3561c8ccb5c22c6b46b836b464` |
| Source branch | `feat/b799-public-retail-catalog-cart-checkout` |
| Original M13/M14 QA | [Report806](806-BATCH799-PUBLIC-STOREFRONT-M13-M14-SOURCE-QA-20261023.md), older `4be2ff7e` |
| Initial M15 reconstruction | [Report807](807-BATCH799-M15-LATEST-SOURCE-AND-RELEASE-GATES-20261023.md), older `89c19d50` |
| MariaDB version | Disposable `10.11.19` / PHP `8.4` / Laravel 13 |
| Scoped DB PHP tests | **28 tests, 253 assertions, PASS** |
| Actual separate-process simultaneous HTTP cases | **2/2 PASS** |
| Original Chromium responsive cases | **1440, 768, 390, 320 px PASS** |
| All exact-head GitHub check runs at report creation | **13/14 completed SUCCESS; remaining Authenticated Chromium / 7 viewports / dark light auto=in_progress/null** |
| Production / merchant rollout | **HOLD / default-OFF / NOT DEPLOYED** |
| Latest owner host authority | **B798 Report804 original, Report805 redacted; no replay** |
| Accepted program milestone ledger | **7/17; CMS 7/15 CLOSED; unchanged by source-only work** |

## Real concurrency proof: not merely sequential PHPUnit

The CI browser workflow [b799-chromium-qa.yml](https://github.com/AldinAga/ald1n-project/blob/feat/b799-public-retail-catalog-cart-checkout/.github/workflows/b799-chromium-qa.yml) creates only synthetic data, uses two physically independent `php artisan serve` HTTP processes on `127.0.0.1:8124` and `:8125`, and a disposable MariaDB 10.11.19 instance. A separate PHP process holds a `FOR UPDATE` row lock; a SQL `information_schema.PROCESSLIST` barrier verifies that **both** real requests are waiting simultaneously before releasing the lock.

1. **One last stock unit, two distinct guest sessions:** the barrier witnesses two blocked product `FOR UPDATE` queries; after release the responses are exactly HTTP **302 + 422** and only one stock movement/sale commits. One unit is consumed, the other purchase rejected, no oversell.
2. **Same nonce, two simultaneous duplicate submissions:** separate HTTP servers share the same genuine guest session/form. The barrier witnesses a product row lock waiter plus concurrent unique attempt-key upsert waiter. After release both requests respond **302** with the **identical private order capability**. Exactly one order, one stock sale, no duplicate issuance.
3. Final independently queried DB state after original two visually exercised orders and two concurrency scenarios: **4 actual synthetic retail orders**, visual fixture stock **6**, last-unit fixture stock **0**, duplicate fixture stock **1**, exactly **4 public accesses**, **4 accepted attempts**, **4 sale stock movements**, **0 commissions**, **0 payment receipts**. Browser output `B799_BROWSER_DB_POSTCHECK=PASS` and both `B799_*_CONCURRENT_HTTP=PASS`.
4. No production DB, real buyer, email transmission, active bank account, native AAB, OTA or Google Play action was used.

## Code correction

`PublicRetailCheckoutService::place()` now atomically upserts the unique checkout nonce without overwriting the original fingerprint, then locks the attempt row with a **current** read. This closes the previously untested `firstOrCreate`/consistent-snapshot race under MariaDB's default REPEATABLE READ. Real CI tests validate the concrete execution, including 302 replay of the same 256-bit receipt and no extra charge/stock movement.

Added PHP integration regressions: changing a previously accepted form nonce's buyer payload fails 422 without mutating the committed order; admin cancellation of an anonymous guest purchase restores stock exactly once on repeated cancellation while preserving zero commission/zero payments. Basic admin processing/confirmed/issued document, privacy token scope/revoke/expiry, readiness and bank-transfer-unpaid cases remain covered by existing feature suite.

## Unresolved before acceptance or production

- **Merchant policy attestation remains absent:** actual merchant identity, true approved HTTPS sale/privacy policy URLs, support mailbox, delivery description, explicit confirmation of *zero* shipping fee (nonzero shipping currently fails closed) and accepted COD/bank-transfer payment modes. CI example.test values are synthetic and MUST NOT be copied to the host as facts.
- Targeted actual CloudLinux independent PHP-FPM/source/webroot/OPcache provenance and dirty-checkout assessment; protected .htaccess, index/HEAD, full backup verification and exact-source reversible `incoming/` delivery remain necessary before any source deployment.
- Business UAT beyond synthetic coverage: final operations acceptance for post-order payment, delivery, after-sales, notification deliverability, customer-specific document render and merchant approved terms; no fabricated actual customer actions.
- PR93 remains **DRAFT**, no merge to `main`, no changes to production flags (`PUBLIC_STOREFRONT_ENABLED` and `PUBLIC_CHECKOUT_ENABLED` default OFF), no public go-live. Avoid duplicate work from PR92. Follow actual M13/M14/M15 release gates, then distinct Android16KB signed AAB M16, then SDK58 M17.

## Owner workflow

Complete GitHub code, exact-head CI, ops reports and allowed Drive documentation automatically. **Do not ask the owner to execute anything for source-only work.** When verified CloudLinux operator access is the sole remaining step, provide one pretested, integrity-self-verifying script under `/home/icaffeco/ald1n-project/incoming/`, one SSH copy/paste invocation and request its numbered report. No separate checksum or narrative checklist.
