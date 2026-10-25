# Report851 — B866–B871: scoped CSP base-uri hardening, HTTP privacy, Composer idempotence and 585x2 locked regression

Date: 2026-10-25, Europe/Belgrade. **B866/B870/B871 exact-source scoped CI PASS; global release HOLD.** No production/CloudLinux SSH, no source merge, no EAS native build, no OTA/Play, no customer order/mail/data mutation.

## Fresh authority and stacked release states
- Read GitHub `main` `793e2dffc9ad19ffd81dd0814e8e20a41f53e672` (Report850), root `AGENTS.md`, `docs/operations/000-LATEST.md` (B832 stale but an earlier direct edit was provider-denied), canonical [Drive update runbook](https://drive.google.com/file/d/10Lxe_reZ1csYOMK9ESjZ8NUmEglDpOMy/view), and [OWASP penetration plan](../security/STOREFRONT-PENETRATION-TEST-PLAN-20261024.md).
- Public storefront remains production B798 **OFF**. Draft PR93 `acb8b9c42ec9254b1a27567abca29a26c1f733f8`, Draft PR99 `659b247dc744a30ece29c064b92fb2bf70fa2c49`, Draft Composer/security PR100 `1f63318f1f1c2a76544c53d540f39dbb9307a56c` remain unmerged. Android PR82 `6d11b766131ca3cfa946a984b1cc9ed834c00239` remains unmerged.
- Every earlier provider safety-denied B805/B809/B815 test-fixture edit and the standalone active HTTP CSRF probe stay forbidden. No reattempt, alternative fixture, exclusion or change in permission boundary.

## B866 — concrete ASVS V3.4.3 source gap fixed narrowly
- Original `SecurityHeaders.php` CSP included `base-uri 'self'`, contrary to the pinned ASVS v5.0.0 Level 2 `V3.4.3` requirement for `base-uri 'none'`. A one-line change on Draft PR100 now enforces `base-uri 'none'` while preserving frame/form/object policies. Exact original CSP source `d8142bb61edd0dd5737f065a1b1c53e5782bf942`; fixed code source entered `aaef34701e550875694481532b966c51bee4eb53`. CI verified that no Blade HTML `<base>` tag uses the old behavior.
- **Not a full CSP PASS:** the global policy lacks complete `script-src`/`default-src` control. Existing CMS layouts use inline JS; blindly enforcing script restrictions would break UI. [GitHub issue #101](https://github.com/AldinAga/ald1n-project/issues/101) tracks nonce/hash-based script-loading migration and browser/UAT. ASVS `v5.0.0-3.4.3` remains PARTIAL/NOT_RUN, never independently verified PASS.

## B867–B869 — real Laravel HTTP privacy / security regression proof
- New `PublicResponseHeadersB866Test.php` adds **11** actual framework HTTP tests for 200 login/password forms, storefront-OFF 404 registration/checkout/private receipt/document/warranty, JSON 404 capability, no-store/noindex/referrer, nosniff, CSP base-uri/frame controls and no reflected untrusted CORS headers. Synthetic MariaDB10.11.19/PHP8.4 only; no real browser or standalone CSRF probe.
- CI re-ran 3 **unchanged** existing suites B836 registered buyer receipt (15), B847 public media/catalog (15), B843 checkout replay/no duplicate stock (11) together with the new 11: **52 tests / 515 assertions PASS**, no skipped/duplicated/failed identities.
- [Exact final-head CI #40204443961](https://github.com/AldinAga/ald1n-project/actions/runs/40204443961) **SUCCESS** on `1f63318f1f1c2a76544c53d540f39dbb9307a56c`, after the lock-only bot update. [Exact-head CI source/JUnit/hash archive on Google Drive](https://drive.google.com/file/d/1Ik3FQVtUvfS5qXK99Sbd5d5obNzuNGUl/view?usp=drivesdk), verified **12,499 bytes**. Prior old-lock HTTP CI #40203973248 was also 52/515 PASS but does not substitute for the new exact-head run. This is scoped SEC22/SEC23 evidence, **0 complete OWASP item PASS**.

## B870 — recurring Composer lock drift corrected without weakening advisory safety
- Original B858 workflow ran `composer update` **unconditionally on every PR100 push**, even on a clean `composer audit`. This unexpectedly pushed bot commit `fbd98208bc6e8f15541a8fcd7b1255a584589a5f` after original HTTP CI, changing just **10 lines** of `composer.lock`, causing stale exact-head provenance. Bot-triggered Actions showed `action_required`, not a fresh test PASS.
- Scoped workflow change now executes and validates **an initial read-only Composer locked audit**. If the current lock has zero advisories/abandoned packages, it performs `composer install --no-scripts --no-plugins` without solving/updating and verifies **zero lock diff**. If real verified advisories exist, the original bounded security resolver + full 41-test MariaDB/re-audit/branch-head guarded commit remains available. Malformed audit/network failures explicitly HOLD, never silently green.
- [Exact final-head B858 CI #40204444046](https://github.com/AldinAga/ald1n-project/actions/runs/40204444046) **SUCCESS**, markers `B858_PRE_EXISTING_LOCK=PASS_LOCKED_COMPOSER_AUDIT_ACTION=clean`, `B858_NO_VULNERABLE_LOCKED_PACKAGE_NO_SOLVER_UPDATE`, `B858_LOCK_ALREADY_CURRENT_NO_PUSH`. Subsequent official Composer audit **0 advisories / 0 abandoned**. Three real suites **41 PHPUnit / 398 assertions PASS**. [Drive evidence](https://drive.google.com/file/d/1xQ2AnWAktNbnGVS_bHqDqcf3LNKR0cTr/view?usp=drivesdk) **48,881 bytes**. **The running production Composer lock is not verified patched**.

## B871 — valid, fresh lock-only full-suite comparison 585×2
- Original immutable B859 comparison to source `6af1e49df4f96d8a7fb284b3f93cc7c90075c264` (574 cases) was not suitable after the B866 legitimate middleware/test addition; [CI #40204240982](https://github.com/AldinAga/ald1n-project/actions/runs/40204240982) correctly failed its fail-closed identical-PHP-source gate. It was **not** bypassed or silently waived.
- Kept B859 parser/test history intact; created separate `b871_dependency_lock_delta.py` plus 13 adversarial tests and re-targeted the existing full-suite job to **immutable pre-bot source `aaef34701e550875694481532b966c51bee4eb53`** (already including CSP fix and 11 new HTTP tests). Both before and after share exactly the same 585 PHP test identities/source; **only `composer.lock` differs**.
- [Exact head B871 CI #40204443889](https://github.com/AldinAga/ald1n-project/actions/runs/40204443889) **SUCCESS**: 13/13 synthetic negative controls PASS, before lock SHA256 `498f133764970cf8250688a31c0c44f6459d433f89cd2577cf10df3b0b82c636`, after SHA256 `06da404d8fd7dfe57fd300cdca7d29819a7c743c849283b1a1aa2f163d745c2e`. **585/585 tests before, 585/585 after; 100 failure/error identities before, 98 after; 0 new failed identities, 0 newly warned, 2 previously failing now PASS, 0 skipped/missing/duplicate identities**. The two improved cases: `OrderDetailPresenterTest::test_presenter_survives_zero_dates_and_uses_only_scalar_view_data` and `WarrantyExpiryOptInSourceTest::test_openapi_mirrors_include_explicit_warranty_expiry_opt_in`. This is **PASS_DEPENDENCY_ONLY_NO_NEW_FAILURE_IDENTITIES**, NOT full suite PASS.
- [B871 original immutable JUnit before+after, logs and SHA-256 JSON on Drive](https://drive.google.com/file/d/1RCTEATmsQQSoflueUhtZ_OiDRrBavhvW/view?usp=drivesdk), original ZIP size **416,647 bytes**, ZIP SHA-256 `77b66542eea8fe830482c20a00802066aa489344b25d131183ab65f2bc758b0e`.

## B803 and release HOLD reconfirmed at exact PR100 head
- [Current B803 full baseline-versus-candidate run #40204443858](https://github.com/AldinAga/ald1n-project/actions/runs/40204443858): immutable production-code baseline **451 tests / 44 failures+errors**; current source candidate **585 tests / 98 failures+errors**; still **56 newly failing identities**, global CI FAIL/HOLD. Unaltered [original B803 JUnit/evidence archive on Drive](https://drive.google.com/file/d/1BUbFmk9stTklkS2I6qefN9lH8dCnao2_/view?usp=drivesdk), **363,640 bytes**.
- All **368 OWASP WSTG4.2 + ASVS5 L1/L2 item IDs remain UNASSESSED/NOT_RUN**, no independent full security PASS. The six ASVS headers/cache-related controls discussed are targeted *partial source/HTTP evidence only*, not signed-off complete per-item tests. Production storefront remains OFF.
- Android M16 exact PR82 native 16KiB Kotlin/ELF/debug AAB/bundletool/offline emulator technical CI remains PASS. **No EAS-signed production AAB**, remote versionCode not verified above rejected 27, no independently verified signer, real device UAT or Google Play acceptance; SDK58 remains postponed.

## Milestone ledger, handoff and guardrails

**Accepted program: 7/17 = 41.2%. CMS: 7/15 = 46.7%. Original UI: 6/6 = 100%.** These are owner acceptance gates, not total source development percentage. Improved source regression and archive evidence do not close a new production milestone.

Final current source: **Draft PR100 `1f63318f1f1c2a76544c53d540f39dbb9307a56c`**, unmerged. No hosting SSH needed. Next steps: independently assess/fix the 56 new full-suite identities without touching safety-denied tests, complete OWASP item-wise isolated security and merchant/SMTP/backup/owner acceptance gates, and keep the signed M16 Android release separate until EAS/real device/Play confirmation. Defer high-risk cleanup to the approved final issue #95 plan.

BATCH_RESULT=PASS_SCOPED_52_HTTP_AND_585X2_LOCK_NONREGRESSION
SOURCE_MUTATED=DRAFT_PR100_ONLY_CSP_AND_CI
SOURCE_COMMIT=1f63318f1f1c2a76544c53d540f39dbb9307a56c
COMPOSER_ADVISORIES_ON_DRAFT=0
OWASP_FULL_VERIFIED_PASS=0_OF_368
CMS_GLOBAL_CI=HOLD_56_NEW_IDENTITIES
ANDROID_M16=HOLD_SIGNED_AAB_UAT_PLAY
OWNER_SSH_REQUIRED=NO
PRODUCTION_MUTATED=NO
ACCEPTED_PROGRAM=7_OF_17
