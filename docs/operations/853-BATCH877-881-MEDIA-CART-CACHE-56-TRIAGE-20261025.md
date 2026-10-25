# Report853 — B877–B881: dynamically gated media no-store, Host-safe cart, verified B803 symptom census

Date: 2026-10-25 Europe/Belgrade. Result **PASS for independent scoped real HTTP**, **HOLD for full Laravel release**. No CloudLinux SSH, CMS production update, public storefront ON, EAS, OTA, Google Play, merge, real mail/orders/payment, test suppression or provider-denied workaround.

## Fresh authority, source and stacked PR provenance

- Reconstructed at intake: GitHub `main` `017f1c33a2d5c8d2a7871d47f34341ceb0aaa3a4` (Report852), root `AGENTS.md`, stale `docs/operations/000-LATEST.md` B832 (earlier edit provider-denied; do not retry), canonical Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md` and WSTG/ASVS penetration plan. Public storefront production B798 **OFF**. Reviewed unchanged parents Draft PR93/99/100/102, Android PR82 technical signed gate.
- New [Draft PR103](https://github.com/AldinAga/ald1n-project/pull/103), **base PR102** exact prior commit `5315d72a7b918f7a9f0104a820656d0a6920fe0d`, child exact HEAD **`985c289e5d2dc1f8532af4ea6b87eb18ba630478`**, unmerged. Only **three CMS paths changed**: `PublicShopController.php`, `SecurityHeaders.php`, and new `PublicMediaCacheAndCartRedirectB877Test.php`; plus isolated CI workflow and scoped source/triage documentation. Prior exact-head Composer lock and B871 585×2 proof remain immutable; no protected B805/B809/B815 fixture edit.

## B877 — real media caching gap fixed in source

- Previously `PublicShopController::media` sent `Cache-Control: public, max-age=3600` for a real publicly visible dynamic media route. That permitted conforming browsers/shared proxies/CDNs to retain previously authorized images for up to one hour after the origin's emergency storefront OFF switch returned 404. This was a **source review identified** possible intermediary-policy bypass; **no production CDN incident verified**.
- New response is `Cache-Control: private, no-store, max-age=0`, `Pragma: no-cache`, `X-Robots-Tag: noindex, nofollow, noarchive`, `X-Content-Type-Options: nosniff` for successful files. The global `SecurityHeaders` middleware additionally covers route `public-shop.media` for **both 200 and route-resolved 404**. Existing disk allowlist, realpath containment, visibility, MIME and symlink guards unchanged.
- Explicit limitation: this cannot retroactively purge copies already cached under the former `public,max-age=3600`. Before eventual production ON, separate authorized read-only/proxy/CDN validation and controlled edge cache purge are needed. `no-store` can increase image fetches, intentionally prioritizing strict emergency OFF semantics pending a reviewed cache strategy.

## B878 — cart redirects cannot use untrusted Host authority

- `PublicShopController` add/update/remove previously used `redirect()->route('public-shop.cart')`, which may use request-based absolute redirect generation. They now build a native `RedirectResponse(route('public-shop.cart',[],false))` with exact relative `Location: /korpa`. Add explicitly attaches the existing web session so the ordinary status flash survives. Existing CSRF middleware, stock/cart limits, route names and buyer checkout security remain unchanged.
- New actual Laravel synthetic MariaDB HTTP tests inject `Host`, `X-Forwarded-Host`, `X-Forwarded-Proto`, `Origin` and verify no attacker authority enters the Location for add/update/remove. Tests cover 200 media no-store, source-configuration OFF and DB switch OFF media 404 no-store, hidden/nonexistent media 404 no-store, cart flash/update/remove and OFF blocks write. A unit-local relative Location test does not certify production upstream/proxy behavior or all SEC02 threats.

## B879 — scoped QA PASS, original suites unchanged

- [Exact-source isolated CI #40211193045](https://github.com/AldinAga/ald1n-project/actions/runs/40211193045) on `985c289e5d2dc1f8532af4ea6b87eb18ba630478` **SUCCESS** with **77/77 PHPUnit case identities, 930 assertions, 0 failed/error/skipped/warning, zero duplicate IDs**, actual PHP8.4/MariaDB10.11.19 and locked Composer install on synthetic local database.
- Breakdown: **9 new B877 tests** plus **68 unchanged** B847=15/B866=11/B872=13/B876=3/B843=11/B836=15. Exact CMS diff gate **3 paths**, no Composer.lock change, no provider-denied B805/B809/B815 changes. JUnit parser accepts dot or backslash class delimiter without renaming/skipping and fails closed on any missing/extra identity. PHP syntax and source SHA verified. [Complete GitHub action JUnit, JSON, source evidence and scripts on Drive](https://drive.google.com/file/d/1NvzbDRIJUX61BRhh2AX8W7VnzAlaWNsp/view?usp=drivesdk), ZIP **18,830 bytes**, parent archive folder verified.

## B880 — full B803 comparison, 56 still unwaived and exactly identical

- [Current full B803 CI #40211193029](https://github.com/AldinAga/ald1n-project/actions/runs/40211193029) at exact `985c289e5d2dc1f8532af4ea6b87eb18ba630478`: immutable production baseline **451 cases, 44 failed/error identities** (38 failure + 6 errors), candidate **610 cases, 98 failed/error identities** (89 failure + 9 errors), **56 new** relative to immutable baseline. Full suite **FAIL** by design. Independent comparison of all 56 `NEW_FAILURE=` lines against previous B803 #40209090923 on PR102 found **56/56 identical, 0 newly added, 0 removed**. Nine additional B877 cases did not add failing identities. [Immutable JUnit before/after and SHA256 source archive](https://drive.google.com/file/d/1y8h1b0l4GaaUBOQIy-x8lXwGoKAONPeb/view?usp=drivesdk), ZIP **365,326 bytes**.
- Inspecting raw previous B803 candidate JUnit, 56 new failure symptoms are exactly: **40 expected HTTP200 → 302, 9 expected200 → 403, 3 expected200 → 404, 3 `ValidationException`, 1 B815 undefined method Error**. This is an observed **status census**, not proof all causes are identical or all contracts obsolete. Older B799 anonymous checkout success conflicts with newer **registered/verified Kupac** requirement; B799 direct capability issuance without a buyer conflicts with `PublicOrderAccessService::issue`; B818 unauthenticated synthetic registered buyer receipt fails the current matching session guard. Downgrading those guards to make obsolete fixtures green would weaken security, and changing provider safety-denied B815/B809/B805 tests is prohibited. Do not automatically waive/exclude or misrepresent the 56 as 0; historical baseline also has 44 own failures.
- Created [Issue #104 — B882 exact 56 case reconciliation](https://github.com/AldinAga/ald1n-project/issues/104) with immutable JUnit evidence, symptom counts and strict security-compatibility gates; exact PR103 full recheck added to that issue. This is an explicit tracked block, **not** approval to remove/skip any tests.

## B881 — security and release boundary

- Controls `SEC02` Host/redirect origin and `SEC22` CDN/cache isolation improved with real scoped tests. **None** of the full **368 OWASP WSTG4.2 + ASVS5 L1/L2 items** has an independent complete assessment; the register remains **368 UNASSESSED/NOT_RUN, 0 complete PASS**. Issue #101 CSP nonce/hash work remains open; PR102 targeted no-script browsers remain valid source-specific PASS.
- **Public production storefront remains OFF; Laravel global 56-failure B803 HOLD**. Before ON: independent correction or compliant reconciliation of historical test identities without denied fixture edits; complete applicable OWASP pen testing/repair/retest; actual merchant legal/terms, SMTP, backups/restore, PHP-FPM, CDN purge/readback, owner UAT and strictly authorized canonical `incoming/` deploy.
- Android M16 remains technical native 16KiB/debug AAB/offline emulator PASS but signed EAS production AAB, independently verified remote versionCode > Play-rejected 27, real 16KiB physical UAT and Play acceptance **HOLD**. SDK58/M17 deferred. No new native build, EAS/OTA/Play command or version change in this batch.
- Formal accepted owner-led milestones **7/17=41.2% overall; CMS 7/15=46.7%; original UI 6/6=100%**. These do NOT measure total code completion. This batch closed two actual source/cache/redirect origin risks and tested nine additional cases but closed **zero** new production acceptance milestones. Owner SSH **not** required.

BATCH_RESULT=PASS_SCOPED_77_CASES_930_ASSERTIONS
SOURCE_DRAFT_PR103=985c289e5d2dc1f8532af4ea6b87eb18ba630478
SEC02_SEC22=PARTIAL_SOURCE_REAL_HTTP_ONLY
B803_BASELINE=451_CASES_44_PROBLEMS
B803_CANDIDATE=610_CASES_98_PROBLEMS
B803_NEW_FAILURE_IDENTITIES=56_EXACTLY_UNCHANGED
OWASP_INDEPENDENT_COMPLETE=0_OF_368
CDN_PREVIOUS_CACHE_PURGE=NOT_RUN_PRODUCTION_OFF
ANDROID_M16_ACCEPTANCE=HOLD_SIGNED_EAS_DEVICE_PLAY
ACCEPTED_PROGRAM=7_OF_17
OWNER_SSH_REQUIRED=NO
PRODUCTION_MUTATED=NO
