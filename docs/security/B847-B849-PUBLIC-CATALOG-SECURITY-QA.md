# B847-B849 public catalogue security scope — source-only

Parent: Draft PR99 `c4912edc66e7b6231044ea553c2bf3bad5d2916b`, stacked on unchanged canonical Draft PR93 `acb8b9c42ec9254b1a27567abca29a26c1f733f8`; fresh GitHub main `dbd7666cf4a3c9c8a3e0280267a11d40ad6880d0`. Production B798 storefront remains OFF.

## B847 SEC13/SEC22 public media negative matrix (eight tests)
Actual synthetic HTTP checks, linked to real MariaDB rows and Laravel public disk fake: valid PNG nosniff, private disk denial, parent-directory segment denial, cross-product path isolation, symlink escape denial, content MIME enforcement independently of metadata, inactive/unpriced product denial, emergency OFF denial. No customer files or production media read or changed. MIME and path controls are not substitutes for complete upload/CDN/browser/edge testing.

## B848 SEC11/SEC04 public catalog search boundaries (seven tests)
Single HTTP/MariaDB tests for SQL boolean text, escaped percent/underscore wildcard searches, strict sort/stock allowlists, integer brand/category validators, array/oversize query validation, and product visibility/purchase-price boundaries. No legacy B799–B808 guest-positive test or safety-denied B805/B809/B815 fixture is modified.

## B849 exact-head scoped evidence
A dedicated PR-triggered workflow installs pinned PHP8.4/MariaDB10.11.19 disposable CI, verifies real DB family, lints new test source and controller/catalog source, executes the fifteen isolated cases to a JUnit artifact, invokes a fail-closed JUnit evidence admission with exact source SHA and negative-case names, and archives logs/JSON and test/CI source. An additional six Python unit tests exercise malformed/failed/missing/duplicated or falsely green input rejection. CI success confirms only the scoped regression and its evidence integrity, **not** complete OWASP WSTG 4.2/ASVS 5.0.0 security audit. All 368 B844 items retain NOT_RUN/UNASSESSED until individually reviewed; no implicit PASS or percentage.

## Release boundaries
No CMS/Android production mutation, no public ON, no SMTP, EAS, OTA, Play, SSH, or automatic merge. Full old guest-positive CI still fails; forbidden fixes remain excluded. Complete independent scope mapping, dynamic browser/security testing, global legacy CI, merchant rules, actual mail delivery, restore/FPM/OPcache, source/rollback CloudLinux checks and buyer UAT are still mandatory. Android M16 still needs signed new AAB and physical/Play acceptance before SDK58.
