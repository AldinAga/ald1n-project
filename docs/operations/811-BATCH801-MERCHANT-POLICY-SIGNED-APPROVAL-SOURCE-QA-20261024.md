# Report811 — B801 owner-host Report810 certification and signed merchant-policy release gate (2026-10-24)

KIND=OWNER_HOST_REPORT_CERTIFICATION_PLUS_ISOLATED_SOURCE_QA
BATCH_NUMBER=801
PREDECESSOR_OWNER_REPORT=810-B800-HOST-READONLY-PREFLIGHT-20261024-064254.md
OWNER_REPORT810_BYTES=1765
OWNER_REPORT810_SHA256=5213acf1acdcf7e4ce4b32499294908624adb1ece2cb4cef9782874adb5b8029
OWNER_REPORT810_GITHUB_SHA_CONTENT_VERIFIED=YES
OWNER_REPORT810_DRIVE_ARCHIVED=https://drive.google.com/file/d/1k5jgalM7IJmJ1Crpdk1rMAcQ_y55-vqX/view
B800_READONLY_PREFLIGHT=PASS
B800_HOST_SOURCE_WRITES=0
B800_HOST_DB_WRITES=0
SOURCE_PR=https://github.com/AldinAga/ald1n-project/pull/93
SOURCE_BRANCH=feat/b799-public-retail-catalog-cart-checkout
EXACT_SOURCE_SHA=54b9a12708f654cc5d3351900de2bae624eb2cd3
PR_MERGED=NO
PR_DRAFT=YES
CI_HEAD_CHECKS=14/14_COMPLETED_SUCCESS
MARIADB101119_PHP84_PUBLIC_RETAIL=37_TESTS_498_ASSERTIONS_PASS
CMS_STATIC=983_PASS_ZERO_FAIL
DISPOSABLE_GUEST_CHROMIUM=PASS
REAL_TWO_PROCESS_MARIADB_RACES=2/2_PASS
B801_SOURCE_QA=PASS
PRODUCTION_SOURCE_MUTATION=NONE
PRODUCTION_DB_MUTATION=NONE
PRODUCTION_PUBLIC_FLAGS=OFF
M13_M14_M15_GO_LIVE=HOLD
NEXT_OWNER_SSH=NONE_AT_THIS_SOURCE_ONLY_STAGE
ACCEPTED_PROGRAM_MILESTONES=7/17
ACCEPTED_CMS_MILESTONES=7/15

## Original Report810 read-only owner-host findings

The owner returned canonical Report810 (raw 1,765 bytes / 46 lines, SHA256 above). This is a genuine **read-only preflight PASS**, not an application deployment, buyer acceptance or permissions to enable retail. Verified host evidence: Git HEAD `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`, **84 tracked dirty file paths**, public .htaccess SHA256 `d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef`, CLI PHP `8.4.27`, MariaDB `10.11.19`, maintenance flag OFF, B798 owner Report804 predecessor hash PASS. Warranty issuance table PRESENT; public order-access and checkout-attempt tables ABSENT; public source not deployed and catalog/checkout flags OFF or absent.

Merchant document name, address, city, and tax ID are SET. Actual public sales terms URL, privacy URL, shipping policy fee explicitly approved as zero, allowed payment modes and support mailbox were **not configured**; legal content acceptance not attested. Backup root exists but TWO current independently restore-ready copies were **NOT REVALIDATED** by Report810. Authenticated PHP-FPM/OPcache provenance, actual customer UAT and real email deliverability likewise pending. Do not misstate them as completed.

The raw user report was saved verbatim under [docs/operations/810-B800-HOST-READONLY-PREFLIGHT-20261024-064254.md](810-B800-HOST-READONLY-PREFLIGHT-20261024-064254.md) and independently archived as a plaintext file in the owner's Drive (link above). GitHub blob readback preserved the final newline, full 1,765 bytes. No new SSH repeat is needed.

## B801 implemented source — NO guessed merchant policy

Inside the existing PR93, not the divergent draft PR92, added:

- New isolated SuperAdmin-only `/admin/settings/public-storefront` screen reachable from Settings, with draft legal/privacy HTTPS URLs, real delivery description, actual fee, valid support address, explicitly chosen COD/bank-transfer methods, and optional active BankAccount selection. Missing fields are visible; filling a draft **never enables** the storefront.
- New `PublicMerchantPolicyService` computes full policy/merchant-identity SHA256 fingerprint and fails closed on fake identity, invalid/unsafe legal URL, non-HTTPS/local/numeric/trailing-dot host, missing delivery, unapproved nonzero shipping, no supported payment method, invalid contact or no active bank account for bank transfer. A genuine active, non-retired SuperAdmin must explicitly sign the exact current digest. Changes to legal terms, identity, delivery, support, payment method or selected account invalidate the old signature.
- `PublicCheckoutReadinessService::assertReady()` now requires **both separately OFF-by-default public ENV flags AND current signed policy**; neither configuration screen nor owner signature can set those flags.
- `/porucivanje` includes the currently approved exact `merchant_policy_fingerprint` with the buyer's explicit checkbox, then POST re-verifies the unchanged terms before order/stock creation. An altered or stale digest is a 422 fail-closed error with no order, payment, idempotency or stock writes.
- The never-before-deployed additive `public_checkout_attempts` table gains nullable `merchant_policy_digest char(64)` and strict partial-table schema check; each accepted guest order immutably records the exact signed policy fingerprint alongside legal URLs, shipping description, fee and acceptance timestamp. No change to already existing production tables was executed.

### Reproducible RED/GREEN fix: transactional settings cache

Early B801 commit `e1a8a3cf33ebfad50d5b6b5b08c6b15e6999e514` failed [public-retail MariaDB job120807254227](https://github.com/AldinAga/ald1n-project/actions/runs/40072000103/job/120807254227), 1/37 failures: a rejected SuperAdmin approval incorrectly left a cache-populated uncommitted draft visible after transaction rollback. Root cause: `SettingsService::putMany()` clears and reloads a 10-minute cache **within an outer database transaction**, then validation rolls the transaction back while the cache retains phantom values. Corrected `PublicMerchantSettingsController` by unconditionally calling `SettingsService::forgetCache()` in `finally` **after outer DB transaction** commit or rollback. Independent real MariaDB scoped job subsequently PASS.

A separate earlier malformed URL regex also caused a RED CI job and was fixed by explicit control-character regex plus backslash check. Numeric/octal/trailing-dot private-host negative cases added. The latest exact HEAD therefore receives **37 tests, 498 assertions PASS** at [job120808464569](https://github.com/AldinAga/ald1n-project/actions/runs/40072395617/job/120808464569), with PHP lint for new files and CMS static 983/983. All **14 exact-head GitHub check-runs completed SUCCESS**, including disposable browser/mobile-scope-independent CMS regression and two real separate-process MariaDB concurrency paths. Synthetic Chromium checkout/visual widths 1440/768/390/320 PASS, two genuine purchases + two race scenarios PASS. Full legacy suite still retains inherited debt; source CI does NOT prove whole-suite perfection or actual host rollout.

## Evidence and remaining release gates

1. Source candidate is **DRAFT, NOT merged, NOT deployed**. Public catalog and checkout remain OFF. Owner Report810 established zero host mutation; there is NO new production sale, DDL, worker action or email. Source implementation is not a CLOSED acceptance milestone.
2. Actual merchant must still enter and approve real published legal terms/privacy, actual delivery (zero shipping ONLY if truly agreed and published), payment methods and a real support mailbox. If delivery must be charged, current existing order subtotal cannot represent that charge; separate ledger/accounting source and isolated QA are required before deployment, not invented zero.
3. Confirm two genuinely recent restore-ready host backups and protect all 84 dirty tracked paths, original HEAD and .htaccess; controlled source delivery would need preverified closure, bounded MariaDB schema gates, rollback/maintenance, FPM/OPcache provenance and independent UAT. None are established by this source report. Avoid rerunning completed B798/800 checks.
4. No authenticated native EAS build, OTA or Google Play action; Android M16 genuine signed 16KB AAB then SDK58 M17 remain separately gated. Do not merge competing PR92 without explicit source reconciliation.
5. Owner communication remains **action only**: no additional SSH step for Batch801 source development. If a future host deployment becomes genuinely ready, deliver exactly one integrity-self-verifying canonical `incoming/` script and one SSH invocation, then request its numbered `docs/operations` receipt.

Progress ledger unchanged: accepted program `7/17` (41.2%), accepted CMS `7/15` (46.7%), original UI `6/6` (100%). This is equal-weight accepted milestone counting, not total engineering effort percentage.
