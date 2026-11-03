# B1072 - Independent B1071 patch review + real PR93 Laravel/MariaDB CI

**Date:** 2026-11-03. **Review scope:** B1071 10-file unapplied patch against exact Draft PR #93 commit `acb8b9c42ec9254b1a27567abca29a26c1f733f8`. No production deploy, no app-source merge, no database mutation outside isolated Actions containers.

## Evidence provenance

- B1071 patch SHA256: `510e661167d31e2b6654afbac4809133bda696be066df199a9a1a19b4e84628a`, exact Git blob `24a9793d53814218fe04cfc97703cc633f6fe521` in GitHub `docs/operations/B1072-B1071-M14-M15-QA-UNAPPLIED.patch`.
- Pinned original PR93 Git blobs: M14 cart `87237d51a372f200e511a7114ae80808d4ba6e2d`; M15 checkout `fb29ad856a5989b8a460a69d702e33b5a28aad45`.
- Freshly reconstructed *independent* isolated checkout: 10/10 patched outputs byte-for-byte equal B1071 ZIP contents, `git apply --check` PASS, `git diff --check` PASS, 9/9 PHP lint PASS.
- Local isolated B1071 source runner: 21/21 QA grant/manifest cases, 15/15 replay cases, 4/4 checkout early-boundary cases PASS. These use lightweight stubs, NOT Laravel or MariaDB integration.
- Independent 18-case PHP adversarial source audit: 18/18 expectations reproduced. See `B1072-independent-adversarial.txt` and `B1072-independent-adversarial.php`. Two unexpected *acceptance gaps* confirmed in manifest helper (see below).

## Independent reviewer findings

| ID | Severity | Area | Verified finding and action |
| --- | --- | --- | --- |
| R01 | PASS in isolated source | M14 replay | Rejects malformed, mixed, oversized, zero/negative quantity or non-integer product ID and sorts valid positive cart keys. Independent adversarial cases pass. |
| R02 | PASS for exact boundary, not full integration | M15 checkout | Uses validated B1071 `replaySnapshot()` only for completed-nonce fallback; invalid saved cart is rejected before simulated `DB::transaction`. Other original stock/identity/merchant SQL unchanged. Need real feature/concurrency testing. |
| R03 | PASS strict scope | QA grants | Offline `ScopedQaGrantValidator` rejects unescaped `_`, global grants, roles, duplicates and unrelated database scope. Real QA account grants NOT accessed or attested. |
| R04 | IMPORTANT HOLD | QA source manifest completeness | `SourceManifestValidator::verify()` returns success for a correct 1-file pin even when more files exist in candidate. Thus nonempty input is NOT proof of complete expected source inventory. Require a trusted, independently pinned exact expected path set + counts. Independent fixture with 1/2 nominated files accepted. |
| R05 | IMPORTANT HOLD | QA source path confinement | `is_link($root)` and per-descendant checks do not reject a symlink in a *parent of $root*. Independent fixture passes a root path traversing parent symlink. Require `realpath` canonical root and an allowlisted parent ancestry, with tests. |
| R06 | HOLD for acceptance | Regression coverage | New B1071 PHPUnit unit test is narrow; no newly added Laravel feature test proves invalid replay cart causes zero *actual* MariaDB writes, no concurrency replay test specifically for B1071. Full preexisting tests must run unchanged. |
| R07 | OPEN_NOT_WAIVED | Historical B803 | Exact 56 historical failing identities recovered from original CI job #121144690534 (run #40185291552), not filtered or suppressed. GitHub manifest: `docs/operations/B1072-B803-56-UNWAIVED-TEST-IDENTITIES-20261103.md`. One B815 protected fixture remains safety-denied and must not be rewritten/excluded. |

**Review decision:** M14/M15 replay guard is a reasonable isolated source candidate, but the full B1071 QA package is **NOT READY FOR MERGE/RELEASE** due QA path/manifest completeness gaps and open full-suite gates. No claim of a new full functional milestone.

## Actual integration execution

GitHub Actions run: https://github.com/AldinAga/ald1n-project/actions/runs/41653736902

- GitHub Actions workflow source: commit `d849230c00cf1a63c058967521ce11de702cbc5f`, file `.github/workflows/b1072-pr93-b1071-full-mariadb-qa.yml`, exact blob `104264655d8ccdd80883ff22f2608931db96a17f`.
- Two independent ephemeral MariaDB **10.11.19** instances, separate ports 3306/3307 and databases; actual PHP **8.4** with full Composer dependencies.
- Checkouts both pinned to exact PR93 commit; patch verified by SHA256 and applies **only** in `source-patched` runner directory.
- Original unfiltered `vendor/bin/phpunit -c phpunit.mysql.xml --no-coverage --log-junit ...` run for PR93 and independently for patched source, **without** `--filter`, `--exclude-group`, `--stop-on-failure` or edits to old B805/B809/B815 fixtures.
- New B1071 unit test runs separately and is also included in full candidate test discovery.
- Raw JUnit and logs are stored in Actions artifact. The job must show HOLD/failure if historical full-suite problems remain, even when new regression delta is zero.

**ACTUAL CI FINAL RESULT: completed, GitHub Actions failure/HOLD caused by 99 existing full-suite test problems, NOT a workflow-bootstrap failure.** CI run #41653736902 completed at 2026-11-03T14:30:58Z. Two database instances independently confirmed `10.11.19-MariaDB-ubu2204`, PHP 8.4.27, Composer `phpunit/phpunit` 12.5.31. Exact patch scope, 10 file checks, source QA, MariaDB connectivity and dependencies all passed.

| Real test stream | PHPUnit test cases | Assertions | Errors | Failures | Failed test identities | Exit code |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Original unpatched PR #93 full suite | 553 | 4,127 | 10 | 89 | 99 | 2 |
| Original PR #93 + B1071 full suite | 555 | 4,142 | 10 | 89 | 99 | 2 |
| New B1071 unit test separately | 2 | 15 | 0 | 0 | 0 | 0 |

**Exact identity comparison: NEW FAILURES 0; CLOSED FAILURES 0.** The 99 exact baseline and candidate failed identities match, independently re-parsed from both real JUnit XML files and the CI comparison JSON. The **56/56 historically known B803 IDs are present in the latest 99** (verified against the original unwaived manifest), hence not skipped or silently reclassified. The job deliberately exited **failure** on `HOLD_HISTORICAL_FULL_SUITE_FAILURES`, preserving this safety gate.

Evidence: [GitHub Actions actual run](https://github.com/AldinAga/ald1n-project/actions/runs/41653736902), [complete real JUnit/log/identity ZIP](https://drive.google.com/file/d/152-Ae6ZIphBKmGcFZzfFiKKuWIf_5wgR/view) SHA256 `c63e19d1d79e732ffd126aa4b05fcc08e6d2a5a57203ba73794b01b09b4e134b`, [independent identity comparison JSON](https://drive.google.com/file/d/1KxJ7ozuA_s4saWTf9kW8-bzlw75Dv4__/view) SHA256 `ebbaeb5465654a8e83088b7109543e72bdaa1626f3378b28d4b76bceb0dfb77d`, [original exact 56/56 manifest](https://github.com/AldinAga/ald1n-project/blob/main/docs/operations/B1072-B803-56-UNWAIVED-TEST-IDENTITIES-20261103.md).

No original failing test was disabled. Neither the local pure tests nor the new unit test constitute true Laravel/MariaDB stock/transaction integration coverage for the new B1071 rejection path; a focused new Feature test with real SQL and zero-write invariants is still required. The two manifest QA issues R04/R05 remain unresolved.

## Project completion and release gates

- Formal user-accepted program: **11/17 = 64.7%**; CMS **10/15 = 66.7%**; original UI **6/6 = 100.0%**. Code review or tests do not independently change these owner acceptance numbers.
- Last completed host audit B1068: **24 PASS, 2 HOLD, 11 INFO**; 24/26 = **92.3%** of decisive *audit* checks, NOT commerce readiness.
- Production StoreFront and checkout remain OFF. M13 enabled browser and FPM/OPcache source proof, M14/M15, 56 historical B803 cases, SEC23/CSP, all 368 OWASP WSTG/ASVS items, merchant/legal and owner acceptance gates remain OPEN/HOLD.
- B1069 revoked (`DO_NOT_RUN`); B1065 incomplete QA receipt remains HOLD; no automatic QA schema cleanup, no CloudLinux source writes, no EAS/OTA/Google Play build.

## Final acceptance decision and percentages

**PASS:** independently pinned 2 original service Git blobs, exact 10/10 patch byte identity; php -l 9/9; 18 independently designed edge assertions; both disposable MariaDB 10.11.19 connections; PHP 8.4 and Composer; 2 new real PHPUnit tests/15 assertions; full 553+555 unfiltered real PHPUnit runs completed; **0 new failed test identities**; 56/56 historic B803 IDs included.

**HOLD:** full Laravel suite still **99** existing failed test identities in both copies, hence global CI is red; exactly 56 previously identified B803 failures remain among them; QA SourceManifest completeness/path ancestry defects R04/R05; no new real MariaDB feature test proving invalid replay has zero transaction/stock effects; production FPM/OPcache, M13 browser and owner UAT, all 368 OWASP items, SEC23/CSP, merchant/legal approvals, and B1065 QA lifecycle.

**Audit-scoped nonregression:** zero added failures in 99 original candidate failing identities = **0 new regressions**. Do not present this as 100% full-suite pass or release readiness. Pure adversarial source review expectations **18/18** reproduced, including 2 tests exposing QA gaps; CI infrastructure prep checks passed, not a checkout release waiver.

**Formal owner-accepted milestones unchanged:** program **11/17=64.7%**, CMS **10/15=66.7%**, six initial UI tasks **6/6=100%**; M14/M15 owner accepted **0/2=0%**. A technically improved candidate is not an accepted feature until release gates pass.

**Next permitted engineering step:** source-only fix of manifest completeness and symlink ancestry with independent RED/GREEN tests; add real Laravel/MariaDB Feature replay rejection + zero-stock/order/movement tests to the isolated PR93 candidate; repeat the whole suite with *every* original test unchanged. No production activation, merge, DB migration, CloudLinux script, EAS/OTA/Play, or safety-denied fixture rewrite in this batch.
