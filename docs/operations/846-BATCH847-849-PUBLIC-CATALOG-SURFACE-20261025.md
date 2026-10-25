# Report 846 — B847–B849 public catalogue/media isolated security QA

Date: 2026-10-25 (Europe/Belgrade). **SOURCE / DISPOSABLE CI ONLY. NO PRODUCTION/SSH/RELEASE MUTATION.**

## Authority and source
- Fresh GitHub `main` at batch intake: `dbd7666cf4a3c9c8a3e0280267a11d40ad6880d0` (Report845). Root `AGENTS.md`, `docs/operations/000-LATEST.md`, Report845, PR93/PR99 and canonical Google Drive runbook were read. `000-LATEST.md` still starts at older B832 due a prior explicit edit denial; no bypass or workaround.
- Canonical public storefront PR93 remains **DRAFT/unmerged**, source `acb8b9c42ec9254b1a27567abca29a26c1f733f8`. This work extends test-only **Draft PR99**, immutable tested head `062462a5767881b915360f1dc202e2cc2c2c3a00` (parent `c4912edc66e7b6231044ea553c2bf3bad5d2916b`). PR99 is stacked on PR93, not merged or deployed. Production remains B798 with public storefront OFF.
- Latest GitHub operations sequence was 845; this is GitHub Report846. **No local CloudLinux checkout was accessed**; local-only newer uncheckpointed files cannot be independently attested and no host report claimed.

## B847 public media: eight actual synthetic HTTP security tests
New single scoped test file `apps/cms/current/tests/Feature/PublicCatalogSurfaceSecurityB847Test.php` checks:
1. Genuine public PNG and `X-Content-Type-Options: nosniff`.
2. Unapproved private disk.
3. Parent `..` directory traversal.
4. Cross-product directory access when another product's file exists.
5. Linux symlink escape outside the product's directory.
6. Non-image content spoofed as PNG via filename/DB MIME.
7. Inactive and zero-price product images.
8. SuperAdmin storefront OFF closing already-available public media.
All data/files are disposable; no customer uploads or production media touched.

## B848 public catalogue: seven real HTTP/MariaDB filter tests
On the same isolated source: SQL boolean payload is plain text, literal percent/underscore LIKE escapes, strict sort/stock allowlists, rejected brand/category SQL fragments, rejected array and overlong search, and exclusion of draft/unpriced/soft-deleted product rows from public query. Older B799–B808 anonymous/guest-positive fixtures and provider safety-denied B805/B809/B815 paths were neither modified nor suppressed.

## B849 exact-source evidence & CI
- [GitHub Actions 40191150113](https://github.com/AldinAga/ald1n-project/actions/runs/40191150113) complete **SUCCESS / actual workflow RC 0** on immutable `062462a5767881b915360f1dc202e2cc2c2c3a00`; pinned Ubuntu runner, PHP 8.4, MariaDB **10.11.19**, Composer lock, source PHP lint, real HTTP/MySQL PHPUnit.
- Actual result: **15 PHPUnit tests, 49 assertions, 0 failures**; plus **10/10** synthetic adversarial Python evidence-parser tests. Unitary/falsified/missing/duplicate/errored/skipped identity or false JUnit totals are rejected; no generic security PASS.
- Immutable `b847-catalog-junit.xml` SHA-256 `8c7856791167cdd6a30c352405c17c2bc700a80d960ecc3cb017a8c66c32ab51`; `b849-catalog-evidence.json` SHA-256 `bb133e06702395ee154c92232d8aeff0fd1c867437a7dfc9e845b5ab5e7ee296`. GitHub artifact ID `12597837287`, 11,948 bytes.
- [Google Drive exact native CI ZIP](https://drive.google.com/file/d/1dzsQJ8un4z2cRszy0tUInq-ZqU8AP-TC/view?usp=drivesdk) uploaded with original source, workflow, JUnit/logs and generated JSON. Verified Drive readback: matching 11,948 bytes in existing code archive folder.
- Source doc `docs/security/B847-B849-PUBLIC-CATALOG-SECURITY-QA.md`, evidence parser `docs/security/tools/b849_catalog_evidence.py` (Git blob `2fb0ec9d1fc48ae09ff84da17ec7f4c115634dbf`) and adversarial tests (blob `6b7a9d71e8e51dfba6bbb094e47da753a4625e9a`) included. PR99 body updated with evidence.

## Boundaries / unresolved
- These are scoped subcases of SEC04, SEC11, SEC13 and SEC22; **all 368 B844 OWASP WSTG4.2/ASVS5.0.0 L1+L2 control IDs remain UNASSESSED/NOT_RUN**, with `verified_security_pass=0`. No full penetration certificate or independent retest claimed.
- Full baseline/candidate B803 CI was **in progress** when this report was prepared. Older B802/B806/B799 workflows still fail on inherited guest-positive and/or B809 historical fixtures; those safety-denied edits remain blocked and are not fixed or waived by this batch. Earlier scoped B845/B846 26 PHPUnit / 349 assertions remains separate successful evidence.
- Android M16 earlier Android 15 16 KiB emulator with all-native/JS-bundled APK smoke is PASS, but **fresh signed EAS production AAB**, signer/provenance/versionCode>27, physical 16 KiB UAT and Google Play approval remain HOLD; Expo SDK58/M17 deferred. No EAS build, OTA or Play publication attempted.
- Merchant policies, live SMTP sender validation, complete WSTG/ASVS applicability and authorized active CI, backup restore, PHP-FPM/OPcache, physical buyer UAT, full old CI and protected CloudLinux rollback/deploy remain unresolved. Public storefront OFF. No live user/order, DB, mail, remote security scan or privileged SSH action.

## Formal acceptance
- Program **7/17 = 41.2%**, CMS **7/15 = 46.7%**, original UI **6/6 = 100%**; *zero* newly accepted production milestones. The 25 new executed checks (15 PHP + 10 evidence unit) improve verified source quality, not this formal acceptance fraction.
- Next release gate: complete independent 368-item applicability and active CI/staging security checks, preserved full-suite failure identity reconciliation, then approved host/delivery and physical buyer acceptance. Do not activate public storefront or perform a production Android build based on these targeted results.
