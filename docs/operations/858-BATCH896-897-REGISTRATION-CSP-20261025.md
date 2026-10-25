# Report858 — B896–B897 Storefront Kupac registration script CSP, real PHP HTTP and B803 lineage

2026-10-25. **Source-only / disposable CI. Production and public Storefront were not changed.**

## Fresh source authority and scope

Read latest GitHub `main` `e7c0ddbf368dc4ebdb07e48729c8a7192be9ad5d`, current `AGENTS.md`, `docs/operations/000-LATEST.md` (known stale B832; previously provider safety-denied to edit and NOT retried), Report857, canonical Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md`, SEC23 [Issue101](https://github.com/AldinAga/ald1n-project/issues/101) and B803 [Issue104](https://github.com/AldinAga/ald1n-project/issues/104). Previous B893 source/head `1fa7dd8b4e3226c24aa1febe87be71a159906ae3` confirmed.

- [Draft PR108](https://github.com/AldinAga/ald1n-project/pull/108) branch `fix/b896-storefront-register-csp-20261025`, stacked on PR107 exact immutable head `1fa7dd8b4e3226c24aa1febe87be71a159906ae3`; final scoped source **`f03fc4ddc04cf20abff3596f38f0195dbf8f80dc`**.
- Precisely **three changed paths**: `apps/cms/current/app/Http/Middleware/PublicNoScriptCsp.php`, `apps/cms/current/tests/Feature/PublicRegistrationScriptCspB896Test.php`, `.github/workflows/b896-storefront-registration-csp-qa.yml`. No existing protected B805/B809/B815 fixtures changed; no build/signing files or production checkout touched.

## B896 scoped script-source enforcement, not global CSP acceptance

- Existing five public script-free GET HTML pages preserve their stronger `default-src 'none'; script-src 'none'` policy. For **GET `/registracija` 200 HTML only**, the shared `PublicNoScriptCsp` now sets explicit `script-src https://challenges.cloudflare.com`, `script-src-elem https://challenges.cloudflare.com`, `script-src-attr 'none'`, external `frame-src https://challenges.cloudflare.com`, controlled `connect-src 'self' https://challenges.cloudflare.com` (preclearance-compatible), self stylesheet, image/font/worker/media constraints and original `base-uri 'none'`, `object-src 'none'`, `frame-ancestors 'self'`, `form-action 'self'`. No `unsafe-inline` or `unsafe-eval`, no wildcard, no raw Host/Forwarded/Referer into CSP.
- Official Cloudflare 2026 documentation explicitly supports the Turnstile origin allowlist for both `script-src` and `frame-src`: https://developers.cloudflare.com/turnstile/reference/content-security-policy/ . Registration view contains a single third-party external script, with no inline scripts; legacy CMS guest layouts have other inline JS and were deliberately **not** put under this restrictive policy. This is **bounded enforced script-source control**, not a complete ASVS5 V3.4.3 PASS or claim of all browser/vendor compatibility.
- Existing B872 test expects registration and login to keep the global `frame-ancestors 'self'` and not use the script-free page's `default-src 'none'`/ `script-src 'none'`. Preserved those requirements. OFF 404, POST validation redirects, login and existing scriptless catalog retain their prior behavior.

## Actual RED/GREEN, safety HOLD, and exact full suite

- Independent [B896 first real HTTP CI #40221138145](https://github.com/AldinAga/ald1n-project/actions/runs/40221138145) **RED: 7 tests, 3 intended security assertion failures** (missing enforced source policy) and four pre-existing negative/compatibility controls PASS. [Original RED JUnit and source ZIP on Drive](https://drive.google.com/file/d/1MhoS_AQh3jYpNPPZoovmbcxQUNbsWan6/view?usp=drivesdk), readback 6,696 bytes.
- Initial GREEN attempt required correction of **only the new synthetic fixture**: sender approval needed to render a genuine registration form and sitekey. Next attempted browser workflow was reverted after an independent safety block; no workaround.
- Final [**B896 exact-head scoped CI #40221900482**](https://github.com/AldinAga/ald1n-project/actions/runs/40221900482) **SUCCESS**, actual PHP8.4/MariaDB10.11.19, **7/7 unique HTTP test identities and 95 assertions**, no skip/warning/error/failure. Source path checksum + JUnit manifest preserved in [verified Google Drive 7,396-byte ZIP](https://drive.google.com/file/d/124BGJBloaZrRBFzB4PgiYwQzM4eX3v0P/view?usp=drivesdk).
- **B897 browser UAT remains HOLD.** A disposable loopback Chromium/Firefox smoke attempt [#40221684943](https://github.com/AldinAga/ald1n-project/actions/runs/40221684943) could not reach the HTML page because the separate application public-enabled release flag was correctly OFF (local HTTP 404, with full server log preserved in [Drive 9,571-byte ZIP](https://drive.google.com/file/d/1lCfIxdyxQwsIq_AjR8tYxqGCpETRkwii/view?usp=drivesdk)). A subsequent proposed edit enabling the flag even for a disposable CI server was **provider SAFETY-DENIED**. No alternative flag, branch, script or path used to circumvent it. The browser stage was removed by restoring its **previous already-green PHP-only workflow**. **Do NOT claim any Chromium/Firefox PASS, any real Cloudflare interaction or genuine end-user registration UAT.**
- [**B896 exact-head full B803 run #40221900467**](https://github.com/AldinAga/ald1n-project/actions/runs/40221900467): historical baseline **451 unique tests / 44 errors+failures**, candidate **648 / 98 errors+failures**, `NEW_FAILURES=56 WHOLE_SUITE_GREEN=NO`. Compared all 56 case identities with [Report857 full run #40220164801](https://github.com/AldinAga/ald1n-project/actions/runs/40220164801): **56/56 exact identical; 0 newly added, 0 removed**. No legacy bypass, exclude/skip, weakening of activated/verified Kupac-only purchase, receipt ownership/tracked session or merchant policy. [Verified full JUnit/baseline/candidate/manifest/lineage Drive ZIP](https://drive.google.com/file/d/1Q8Fsz35y17iQRhZrsnCFufl-C4DrBd3m/view?usp=drivesdk), 368,377 bytes.

## Release boundaries, next gates, measured acceptance

Production public Storefront **OFF** at B798. Existing 56 B803 failures require authorized independent reconciliation; complete pinned OWASP WSTG4.2/ASVS5 L2 368-item review still **0/368 independently accepted**; browser CSP nonce/hash + Turnstile real-page UAT, merchant/SMTP/legal/backup/FPM and host acceptance all HOLD. No source merge or deploy approval.

Android PR82 earlier exact native debug AAB+16KiB ELF/zipalign/Android15 offline emulator [#40192813557](https://github.com/AldinAga/ald1n-project/actions/runs/40192813557) remains technical PASS; signed EAS production AAB/remote versionCode > rejected27, signer verification, physical 16KiB device and Play approval remain M16 HOLD; SDK58 M17 deferred. No Android build, OTA, Play, SSH, production DB/media mutation, real mail/order or destructive cleanup in B896–B897.

Formal accepted functional scope unchanged: **program 7/17 = 41.2%; CMS 7/15 = 46.7%; original UI 6/6 = 100%**. This batch improves source security coverage/CI, not the count of officially accepted production milestones. No user SSH action warranted.

SOURCE_PR108_SHA=f03fc4ddc04cf20abff3596f38f0195dbf8f80dc
B896_RED=3_OF_7_EXPECTED_SECURITY_FAILURES
B896_SCOPED_GREEN=7_OF_7_REAL_HTTP_95_ASSERTIONS
B897_BROWSER=HOLD_FLAG_SAFETY_DENIED_NO_BYPASS
B803_BASELINE=451_CASES_44_PROBLEMS
B803_CANDIDATE=648_CASES_98_PROBLEMS
B803_UNRESOLVED=56_EXACT_UNCHANGED_VS_REPORT857
ANDROID_M16_SIGNED=HOLD
STOREFRONT_PRODUCTION=OFF
ACCEPTED_MILESTONES=7_OF_17_41_2_PERCENT
PRODUCTION_MUTATION=NO
OWNER_SSH_REQUIRED=NO
