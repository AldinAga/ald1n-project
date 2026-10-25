# B858 — secure PHP dependency refresh candidate (no production deployment)

## B858 isolated remediation result (2026-10-25)

**Initial verified isolated CI SUCCESS**: [GitHub Actions 40194083864](https://github.com/AldinAga/ald1n-project/actions/runs/40194083864). The controlled PHP 8.4 + MariaDB 10.11.19 Composer solver revised **51 locked packages** (including 5 vulnerable), followed by live `composer audit --locked`: **0 advisories, 0 abandoned packages**. The original 19 advisories remain a documented baseline finding, not ignored or waived. All **41 targeted PHPUnit tests / 398 assertions PASS** (B843 11/271, B836 15/78, B847 15/49).

Pinned corrected versions: Laravel Framework **v13.38.0**, league/commonmark **2.10.4**, Guzzle **7.16.0** (kept in compatible 7.x, not major 8), league/flysystem **3.36.0**, Symfony http-foundation **v8.1.9**.

The integrity-checked, lock-only CI commit `703e657e205ec906ef8e49455d3250b05779f084` was pushed **only to this Draft PR100 branch** after exact remote-head comparison, source diff allowlist and tests; no application source code or `composer.json` was modified. The new lockfile is Git blob `e945b577dbaebbe172ace98fa7776f115b19394d`, SHA-256 `498f133764970cf8250688a31c0c44f6459d433f89cd2577cf10df3b0b82c636`.

[Full original CI source/audit/JUnit proof archived on Google Drive](https://drive.google.com/file/d/1kzSWSZ45Ihlc5aDdC9lX16-B-f5e02zd/view?usp=drivesdk) (48,689 bytes). Subsequent exact-head revalidation is separate from this pre-commit result. **Draft PR100 remains unmerged, full B803 baseline/candidate still failing 56 known identities, storefront OFF, production still vulnerable until a separately authorized patch deployment.** The 368 item security inventory is not deemed PASS.

---



2026-10-25. New **Draft PR stacked on Draft PR99**, not merged and never on `main` or the CloudLinux checkout. Source parent `6af1e49df4f96d8a7fb284b3f93cc7c90075c264`.

## Real SCA finding that motivates this branch

[Existing PR99 exact-head Composer lock audit](https://github.com/AldinAga/ald1n-project/actions/runs/40193434750) returned 19 advisories across five locked packages, including a **critical** Laravel TokenGuard advisory, on source `d9f0bb35762485ce9ba613cc5518372da53ae54b`:
- `laravel/framework` v13.20.0: 3 (critical, medium, low), patched versions according to advisory constraints at or above 13.30.0 for all listed issues.
- `league/commonmark` 2.8.3: 12 (many high; XSS and DoS families).
- `guzzlehttp/guzzle` 7.15.1: 2 (high, medium).
- `league/flysystem` 3.35.2: 1 low.
- `symfony/http-foundation` v8.1.1: 1 with unspecified severity, Cache-Control header parsing ReDoS.
Actual vulnerabilities are actionable **advisory matches on package versions**, not a proof that each vulnerable code path is reachable in Ald1n.

[Original audit evidence](https://github.com/AldinAga/ald1n-project/actions/runs/40193434750/artifacts/12599061319). The status is `HOLD_ADVISORIES_OR_ABANDONED`, never suppressed or counted as security PASS.

## Controlled automated repair candidate

Only the exact existing `composer.json` constraints are used. An isolated PHP8.4/MariaDB10.11.19 workflow runs a Composer update confined to `laravel/framework`, `guzzlehttp/guzzle`, `league/commonmark`, `league/flysystem` and `symfony/http-foundation` with transitive updates as needed. Composer plugins/scripts are disabled. Any unexpected modified path (other than `apps/cms/current/composer.lock`), initial head drift, lockfile solver failure, Composer invalidity, remaining security advisory/abandoned package, or failure in the **41 targeted buyer replay/private receipt/media PHP tests** blocks the push.

Only when every independent bounded check succeeds does the workflow compare its PR head to the freshly fetched remote branch, stage exactly `composer.lock`, perform `git diff --cached --check`, and commit **to this Draft PR branch only**. If branch protection/token policy rejects that push, the workflow retains the source-evidence ZIP and remains HOLD. Further global CI baseline/candidate comparison and independent security review are mandatory before any source merge or deploy. A green subset is never full acceptance.

## Safety and release

No source code service changes, production mutation, PHP-FPM restart, CloudLinux SSH, customer data, real SMTP, storefront ON, EAS, OTA or Google Play. Historical safety-denied B805/B809/B815 fixtures untouched. B844's 368 OWASP item IDs still UNASSESSED / NOT_RUN. Formal M01–M17 ledger remains 7/17=41.2%, CMS 7/15=46.7%, original UI 6/6=100%. Android M16 signed AAB/physical/Play and SDK58 remain separately gated.
