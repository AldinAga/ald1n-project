# B1073 - Isolated QA manifest fixes and real MariaDB replay no-write Feature tests

**Status (2026-11-03): SCOPED_QA_AND_FEATURE_PASS; FULL_SUITE_HOLD.** This is an isolated PR #93 source/test candidate, not a release, merge, deployed CMS, or CloudLinux command.

## Exact provenance and immutable evidence

- Pinned original GitHub Draft PR #93 head: `acb8b9c42ec9254b1a27567abca29a26c1f733f8`. PR #111 is unrelated M11 warranty source.
- Existing B1071 original unapplied patch SHA-256: `510e661167d31e2b6654afbac4809133bda696be066df199a9a1a19b4e84628a`.
- New B1073 unapplied delta: [Google Drive patch](https://drive.google.com/file/d/1N3-o7zP1DdN4GTbkEs28gHldM9kFiDO6/view), SHA-256 `2db64aeb8ba79c5db30cd16d0a69f1c8b57bd99ac448152b339a7ffc0f350678`, four paths only: two QA changes + two new test files. [Exact GitHub docs-only patch](https://github.com/AldinAga/ald1n-project/blob/main/docs/operations/B1073-PR93-B1071-QA-MANIFEST-NOWRITE-UNAPPLIED.patch), Git blob `7e4c2e4cf8b7a71580e4cca69cc51fd41963c677`.
- Entire reproducible source/QA bundle: [Google Drive ZIP](https://drive.google.com/file/d/1P3XUWhBWN72ia5-b-1sqdTG9ul-95_Yg/view), SHA-256 `b1af124239255405799c8722332909927c26a2d3b817437da658204e64b1f122`, 15 archive entries, each SHA-256 checked. Contains B1071 source bundle, original patch, B1073 delta, source files, workflow, RED/GREEN logs, and manifest.
- Real GitHub Actions run [B1073 #41657873121](https://github.com/AldinAga/ald1n-project/actions/runs/41657873121), exact docs/CI commit `3e9b31eff60e5a8cfe8065a28fbeb04c79c84d42`. Two independently connected disposable MariaDB 10.11.19 instances, PHP 8.4.27 and actual Composer-locked Laravel/PHPUnit 12.5.31. Checkouts both pinned to same PR93 SHA. Only the isolated second source checkout received B1071+B1073 patches. NO production deploy or feature enablement.
- [Complete actual original/candidate/Feature JUnit XML and logs ZIP](https://drive.google.com/file/d/1NNXSQo0CYx10JS9AX2n74GxpELtlF1qy/view), SHA-256 `e3f8f5df53a67a9bbdff0a17a46ecc0bc3ce4ed2a9e6e09519d2ccce21697dbd`; [failure identity JSON](https://drive.google.com/file/d/1j-P10dP_acz0smr-sxdccvg5UEFoOPi8/view); [original CI final report](https://drive.google.com/file/d/1MUizpojZqy4HhMIJpMx5CgOL8TMsTwFM/view).

## QA: exact fixes (SOURCE-ONLY)

1. **R04 source completeness**: `SourceManifestValidator::verify()` now requires a nonempty independently supplied exact expected PHP path list. It compares the *complete discovered* `candidate/` PHP file census with this expected list AND with all exact per-file SHA-256 pins. Zero-file, omitted-file, extra-file, duplicate pin, hash drift and hidden/traversal paths fail closed. Caller must provide its expected set from a trusted independent source; deriving it from the untrusted submitted manifest would defeat this invariant.
2. **R05 path ancestry**: the original absolute isolated root is checked component by component with `is_link`, then verified against its canonical `realpath`; candidate descendants are enumerated without following symlinks. A symlink in an ancestor of the root or in the candidate tree is rejected, including a parent path that formerly bypassed the root-only check.

**Independent RED/GREEN**: original B1071 accepted a one-file subset and a symlink-ancestor path; tests failed deliberately. Changed B1073 source passed 9/9 new manifest adversarial cases, 21/21 preexisting QA cases, 15/15 replay source cases and 4/4 synthetic checkout boundary cases; 4/4 changed/new PHP files linted, delta `git apply --check`, `git diff --check` and byte-for-byte independent patch application passed. These are local source QA results, not production state.

## Real Laravel / MariaDB Feature tests

New `tests/Feature/PublicRetailReplayNoWriteB1073Test.php` is a real Laravel HTTP Feature test, run separately against disposable MariaDB. It creates one synthetic approved buyer and merchant, one synthetic product, and a successful synthetic order to exercise the *completed-nonce replay* path. Then it sends a malformed saved session cart with the same nonce. For every rejected replay it asserts HTTP **422** with the `cart` validation key, identical counts for `orders`, `order_items`, `stock_movements`, `order_status_history`, `public_checkout_attempts`, `public_order_accesses`, `order_payments`, and `order_commissions`, unchanged product stock, unchanged order identity, and **no intercepted operational INSERT/UPDATE/DELETE/REPLACE SQL** through Laravel `DB::listen`.

Cases: string quantity, quantity above 20, zero product ID, SKU-shaped noninteger key, nested quantity, boolean quantity, more than 25 items, empty cart, non-array cart. No real customer data or emails.

**Actual PHP 8.4.27 + MariaDB 10.11.19 result: 9/9 testcases, 261 assertions, 0 failures/errors, exit 0.** This proves the specified rejection/no-write behavior in this synthetic isolated CI context. It does not independently prove real-user browser, concurrent double-submission, owner UAT, production FPM byte provenance or a live merchant approval.

## Unfiltered FULL regression (no historical exclusions)

| Actual CI suite | PHPUnit testcases | Assertions | Errors | Failures | Failed identities | Exit code |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Original PR93, separate baseline MariaDB | 553 | 4,127 | 10 | 89 | 99 | 2 |
| PR93 + B1071 + B1073, separate candidate MariaDB | 564 | 4,412 | 9 | 89 | 98 | 2 |
| B1073 focused real HTTP/MariaDB Feature | 9 | 261 | 0 | 0 | 0 | 0 |

**New regression identities: 0.** One original failure identity was absent in the candidate: `Tests\Unit\OrderDetailPresenterTest::test_presenter_survives_zero_dates_and_uses_only_scalar_view_data`. The B1073 patch did not touch this legacy functionality, so this observed green case is NOT credited as a causal or durable fix. **All original 56/56 historic B803 failed identities remain in the 98 candidate failures**, independently checked against GitHub's canonical unwaived list; these 56 are a subset of 98, not an additional 56. No `--filter`, exclusions, overrides or edits to historical B805/B809/B815 tests were used for the complete baseline and candidate runs. Both complete-suite exit codes remain 2.

**Global GitHub Actions conclusion: FAILURE / HOLD_HISTORICAL_FULL_SUITE_FAILURES**. This is the intentionally enforced release gate, not a failure to install dependencies, connect to MariaDB, or execute the focused B1073 Feature.

## Recovered CI path error (not hidden)

Initial run [#41657605237](https://github.com/AldinAga/ald1n-project/actions/runs/41657605237) stopped *before PHPUnit* because the workflow referred to a different B1073 GitHub patch basename from the actual one. Root cause confirmed from host job log; a dedicated pathname contract test first failed (RED), then passed after the one-line workflow path correction. Verified rerun is #41657873121; the original run is retained as a pretest CI path failure, not recorded as a Laravel failure.

## Formal status and progress

- **PASS:** R04/R05 QA corrections in isolated source (9 new + 21 existing QA checks), independent patch byte parity, full real PHP/MariaDB Feature 9/9 and 261 assertions with no operational SQL writes during invalid replay, all full tests actually executed, zero *new* failing identities, 56/56 history preserved, no production mutations.
- **HOLD / OPEN:** **98** old full-suite failures (including all 56 B803 identities), formal M14/M15 acceptance, browser UAT, production PHP-FPM/OPcache exact source, merchant/legal/payment/concurrency/real-stock acceptance, SEC23/CSP and 368 OWASP WSTG/ASVS review items, earlier B1065 QA state. B1069 remains REVOKED_DO_NOT_RUN.
- **Milestone completion unchanged:** whole program **11/17 = 64.7%**; Laravel CMS **10/15 = 66.7%**; first six UI **6/6 = 100%**; M14/M15 formally accepted **0/2 = 0%**. These percentages reflect owner-accepted deliverables, not coding effort or release readiness. Scoped B1073 Feature suite **9/9 = 100%** is a DIFFERENT metric.
- **Production:** GitHub main application source and Draft PR93 application source unchanged; CloudLinux dirty host source/DB/cache, public catalog/checkout flags, EAS/OTA/Play unchanged. No release or deployment permitted.

## Follow-on safety gate

Next safe work: investigate the 98 preexisting complete-suite failures by *unmodified test identity and real cause*, then add targeted source-only fixes on a reviewed separate candidate; keep original security-denied test write gates and all original CI cases. Do not declare M14/M15 complete solely because the 9 new focused tests passed.
