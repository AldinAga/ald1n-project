# B871 — exact-source rebase of full Composer dependency regression to 585 cases

Date: 2026-10-25. No production mutation, source merge, CSS/authorization fixture change, EAS/OTA/Play, or SSH.

## Why the original B859 gate stopped — correctly

Historical [B859 full-suite comparator](https://github.com/AldinAga/ald1n-project/actions/runs/40201308807) used immutable baseline `6af1e49df4f96d8a7fb284b3f93cc7c90075c264` and exactly **574** test identities; it proved 0 new PHP regressions when only the original B858 `composer.lock` changed. That remains valid immutable evidence.

Later, B866 source-only CSP base-uri correction and the **11 new HTTP header tests** entered Draft PR100 at `aaef34701e550875694481532b966c51bee4eb53`. A B858 bot source-only maintenance action then advanced the branch to `fbd98208bc6e8f15541a8fcd7b1255a584589a5f`, changing **only the locked dependency file (10-line diff)**. The old B859 comparison now fails its untouched-source allowlist because source *intentionally* includes the B866 middleware and test file. [Original source drift CI #40204240982](https://github.com/AldinAga/ald1n-project/actions/runs/40204240982) is an **expected legitimate HOLD**, not a newly discovered application exploit or a reason to weaken security tests.

## New fail-closed comparison

The original B859 script, tests and historical artifact are retained without modification. Dedicated `b871_dependency_lock_delta.py` and 13 adversarial tests are pinned to exact **pre-bot lock-only baseline** `aaef34701e550875694481532b966c51bee4eb53`, which already contains CSP change and the new 11 HTTP tests. The existing B859 GitHub workflow filename is reused to avoid two competing expensive complete-suite runs, but its active job is now explicitly **B871**. It requires:

- `git diff aaef347..HEAD -- apps/cms/current` to be exactly `apps/cms/current/composer.lock`, with identical `composer.json` and all 585 test source identities, and a demonstrably different lock SHA-256.
- PHP 8.4, locked Composer install and MariaDB 10.11.19, two complete **585-case PHPUnit runs**, each with untouched existing tests and normal assertions. Existing suite failures remain visible; no tests are skipped, renamed, adjusted or suppressed.
- SHA-bound JUnit identity-equivalence, **0 new failures/errors and 0 new warnings**, plus the original 13 negative controls. If the new lock introduces failures, maintain an explicit release HOLD and preserve both raw JUnit files.
- An explicit per-run result: `PASS_DEPENDENCY_ONLY_NO_NEW_FAILURE_IDENTITIES` may be returned only for this **lock-only** comparison; it must never be interpreted as full global PHPUnit success, production approval, or OWASP item-level PASS.

## Related independent evidence and remaining risks

- [B866 HTTP privacy/CSP CI #40203973248](https://github.com/AldinAga/ald1n-project/actions/runs/40203973248) passed **52/52 tests, 515 assertions**, on source `aaef347...` with the older lock. A new exact-head B866 run is required for the later lock; old green cannot be silently carried forward.
- [B870 Composer auto-update idempotence fix](B870-COMPOSER-SOLVER-IDEMPOTENCE.md): clean locked advisories now cause `composer install`, **not** `composer update`. Future new advisory findings still trigger tightly scoped remediation gates. No more lock churn merely from docs/header commits.
- [Issue #101](https://github.com/AldinAga/ald1n-project/issues/101): global enforced script-loading CSP/nonce and browser compatibility remain open even after `base-uri 'none'` fix; **ASVS V3.4.3 is NOT fully PASS**.
- The historical full B803 baseline/candidate **56 new failing identities** (20 old identities, 36 candidate-added) and 368 OWASP controls **UNASSESSED/NOT_RUN** remain separate release HOLD. The patched lockfile is still in Draft PR100 and not deployed to CloudLinux. No wholesale test fixture rewrites, especially provider-denied B805/B809/B815 or standalone active CSRF probe.

Formal milestones remain **7/17 = 41.2%** overall, **7/15 = 46.7%** CMS and **6/6 = 100%** original UI. Android M16 emulator technical CI PASS remains separate from signed EAS/physical/Play acceptance and SDK58 (M17) stays deferred.
