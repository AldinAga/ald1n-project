# Report797 - Batch796 operator workspace source and delivery QA

Date: 2026-10-22
BATCH796_SOURCE=MERGED_PR88
SOURCE_COMMIT=6f81f2bcfca1559dae92c39be323a1c827c31239
TESTED_PR_HEAD=5b234bb6da0b25a9b013b05d874ad753a0f4f593
TESTED_MERGE_REF=801de1025f4ba91003d7d95e51dfe57ccbb1b51d
TESTED_AND_MERGED_TREE=4fae071e51e3fd34907498b2028126d0c41688d8
PRODUCTION_EXECUTION=PENDING
WARRANTY_FIRST_PAYMENT_IMPLEMENTATION=PENDING_SEPARATE_BATCH
PUBLIC_STOREFRONT=NOT_IMPLEMENTED_NOT_ACTIVATED
ANDROID_BUILD_OTA_PLAY_ACTION=NONE

## Delivered application scope

Report796 R1/R3/R6 only. The SuperAdministrator no longer has personal create/view/cancel abilities; User permission projection, Gate and OrderService agree. Operational customer/user orders replace the personal center, with product, seller, final buyer and admin-order links. Existing ordinary buying users retain their own cart/orders; the synthetic non-buying user has no cart. DirectSaleService and historical orders, buyer ownership, financial and commission history are not rewritten.

Glass action/module/KPI cards have one surface reflection on hover/keyboard focus, with stationary text/layout and no pointer interception. Reduced-motion/transparency and forced-colors disable it. The optical vendor layers remain absolute rather than being pulled into document flow.

The first buyer/contact/delivery panel shows canonical courier and shipment number, a safe tracking action when possible, and truthful missing-data states. The URL validator rejects unsafe schemes, credentials, controls, private/reserved IPs, malformed domains and browser-normalized numeric loopback. It validates links without arbitrary server-side URL fetching or inventing a carrier.

## Verified evidence

- PR88: https://github.com/AldinAga/ald1n-project/pull/88 . The final tested PR tree and the squash-merged tree are identical.
- Exact-source PHP8.4/MariaDB10.11.19 scoped regression: **70 tests, 377 assertions, zero errors/failures/skips**, https://github.com/AldinAga/ald1n-project/actions/runs/39805045149 . Covers the new operator/tracking cases plus catalog, ordinary-buyer cart, receivables/direct-sale, warranty baseline, permissions and mobile API regressions.
- Authenticated Chromium: **54 header measurements, 40 screenshots, zero failures**, seven viewport sizes, dark/light/auto, operator/non-buying-user/ordinary-buyer personas: https://github.com/AldinAga/ald1n-project/actions/runs/39805045095 . These are synthetic disposable fixtures, not production customer data.
- All eight final applicable PR workflows succeeded: 39805045149, 39805045095, 39805045017, 39805045080, 39805044964, 39805044955, 39805044967, 39805044974. Dedicated retirement integration ran successfully in its required disposable MySQL8.4 and MariaDB10.11.19 environments.
- Canonical static **983/983**, local actual User role smoke **10/10**, safe URL behavior **24/24**, local headed Chromium rendered-page matrix **28/28** plus motion/focus/optical-layer checks.
- RED/GREEN: original SuperAdministrator permission behavior fails before correction; intended behavior passes after. Numeric short/hex/octal loopback is rejected after a reproduced negative test. A reproduced CSS optical-layer geometry failure is corrected; the authenticated browser retains an explicit absolute-layer assertion.
- Two historical header tests required a SuperAdministrator cart. They were replaced with the approved operator-no-cart behavior AND a new ordinary-buyer cart test, rather than deleting security/role coverage.

## Full-suite limitation - not an all-green claim

The entire configured PHPUnit suite was executed on the baseline and the candidate: **347 -> 358 tests**, with the same **42 problem identities (5 errors and 37 failures)** and no new problems after the approved header expectation changes. This is not a fully green generic suite. Eighteen retirement tests guard their separately named database and pass in their dedicated workflow; the other inherited failures/errors are retained by exact name in the attached QA evidence and comparison JSON. Do not suppress them, label them all environment failures, or represent the scoped green result as a global green suite. Baseline comparison evidence: https://github.com/AldinAga/ald1n-project/actions/runs/39803085869 .

## Operator delivery boundary

Canonical script: `incoming/ald1n-b796-laravel-operator-workspace-v1.sh`.
Script SHA-256: `584ac8332b2fb1701720262168d84b5f31de05c676f9713a19b44f622f9d7fca`.
Manifest SHA-256: `49f76bd1355a9a0b950a731f5e2fcac0d5e3c47bbdbc8ca953bbc4c5911d09a4`.
Runtime closure **683 paths**, exactly **10 allowed runtime writes**, all final target bytes compared to the tested source archive. Nine local delivery safety groups pass, including exact advertised basename, wrong path, tamper rejection, baseline/after closure, unknown-file denial, partial rollback, maintenance attestation, scheduler ownership and failed report-writer recovery. Bash/PHP syntax passes; no forbidden CloudLinux shell substitutions or python3 dependency.

The script binds exact Report793 AND Report795. Keep both local original files. It preserves dirty host HEAD/index/.htaccess and all unrelated source, takes a private scoped source snapshot, pauses Scheduler with owned state, establishes maintenance-origin proof, applies only the approved files, runs canonical checks, releases maintenance, verifies public login/health/CSS and resumes Scheduler. No DB migration, business/test record creation, DB-backup create/prune/restore, old warranty migration rerun, destructive Git synchronization or Mobile/build/release action. Any host result remains pending until its numbered report is supplied. CLI rendering is not independent authenticated PHP-FPM/OPcache or owner visual acceptance.

## Owner decision now resolved - FIRST confirmed incoming payment

The owner explicitly selected issuance **after the FIRST confirmed positive incoming payment**, including deposit/first installment and SuperAdministrator direct sales. Full settlement is NOT required. Do not ask this question again or retain a paid-in-full-only interpretation. Unverified uploads, submitted/rejected proofs, zero payments and refunds do not trigger issuance. Preserve one certificate per order item, retries/concurrency, product/category/global duration rules and existing certificates. Issuance and contractual validity are separate: do not silently start or shorten warranty validity on a pre-delivery deposit. The existing required date schema needs a tested pre-delivery/pending-activation representation; R4/R5 remain the next separate implementation, not activated by B796. No historical backfill, global expiry scheduler run, forced opt-in or customer email is authorized by this checkpoint.

## Progress and remaining work

Ledger v1 denominator is unchanged: original UI 6/6=100%; expanded CMS 7/15=46.7%; program 7/17=41.2%. M08/M09/M12 now have merged source/QA, but receive no CLOSED credit before their production/acceptance gates. R4/R5 payment issuance and isolated recipient opt-in are next; then the complete public storefront at https://cms.ald1n.com/katalog . The public catalog/cart/checkout is still inactive. Android16KB and SDK58 remain separately gated; no duplicate prior builds or unchanged verifier work.

## Review and rulings

Review was author/agent self-review; no independent reviewer was available. Checked exact role/Gate/service contract, ordinary-user isolation, direct-sale separation, scalar Blade presenter inputs, query scope, tracking escaping/destination rules, optical layers, motion preferences, immutable before/after bytes, and owned recovery/report behavior. Important findings fixed: wildcard Gate bypass, obsolete operator-cart expectations, optical layers in normal flow, and numeric loopback URL parsing. No remaining Important finding was identified in this bounded scope; inherited generic-suite failures remain explicitly open.

Ruling: deliver the approved R1/R3/R6 slice first, not mix an unverified payment/schema change into UI activation. Cost: payment/expiry changes require the next tested package. Ruling: retain the full-suite baseline debt rather than unrelated business changes to make old source-contract assertions green; cost: no whole-suite certification. These are explicit scope decisions, not claims of completed warranty or public-storefront functionality.
