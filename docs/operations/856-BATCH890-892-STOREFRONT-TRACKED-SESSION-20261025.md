# Report856 — B890–B892: Kupac tracked-session repair, exact 9/9 HTTP PASS and B803 56 unchanged HOLD

Date: 2026-10-25 Europe/Belgrade. **Source-only Draft PR106; no release/production mutation.**

## Fresh authority, scope and provenance

- Read GitHub `main` (Report855 at `b0d867f9339144754531d35be876157c92d31179`, followed by B890 AGENTS update `0365eb0bc721d8eb01b5b9a0ca3176207616d24e`), canonical `AGENTS.md`, `docs/operations/000-LATEST.md` (stale B832; its previous direct edit was provider safety-denied and NOT retried), Report854/855, Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md` and Issue104 release gate.
- Source Draft [PR106](https://github.com/AldinAga/ald1n-project/pull/106), branch `fix/b888-storefront-checkout-origin-20261025`, stacked on PR105 immutable `601ea692dcfd34f922d7f6544089a622521b8a3b`; **B892 exact branch HEAD `c84ea75ace582ce8f8ac85920fc9f543693d1c3a`**. No merge. Six changed paths relative to parent: `app/Http/Controllers/PublicCheckoutController.php`, `app/Services/PortalSessionService.php`, `bootstrap/app.php`, two new Storefront test files and exact-source GitHub workflow. No historical protected B805/B809/B815 fixture edit, Composer lock, native Android source or production change.
- Previous B888/B889: untrusted Host/Forwarded/Referer must not affect relative anonymous checkout `Location: /login`, registration form error `/registracija` or checkout validation error `/porucivanje`; JSON preserves 401/422 and Storefront OFF stays 404.

## B890–B892 RED-to-GREEN, no weakening of session authority

1. Source originally auto-created a tracked login row when current registry hash was absent, even for a signed-in Kupac with an evicted or revoked session. Added two synthetic RED cases: absent row must return JSON 401 with zero stock/order/attempt writes; active session-ID rotation must retain authorization by transferring only its prior active record, never creating a duplicate.
2. Initial strict missing-row rejection passed **8/8 real PHPUnit / 85 assertions** at [CI #40218242903](https://github.com/AldinAga/ald1n-project/actions/runs/40218242903), original JUnit + source SHA [Drive](https://drive.google.com/file/d/1UhmTbzVREQ1eWh7Y_IfVBy9PkvH0bIat/view?usp=drivesdk). However full B803 [CI #40218242950](https://github.com/AldinAga/ald1n-project/actions/runs/40218242950) exposed **18 additional** failures beyond the 56 preexisting cases: baseline 451/44, candidate 635/116, `NEW_FAILURES=74`, including valid registered buyer/replay/receipt/CSP scenarios. Exact all 18 identities were enumerated against PR105's previous 56, not dismissed.
3. Found two independent causes: (a) Laravel HTTP test client requires `withUnencryptedCookie()` for a real already-encrypted Set-Cookie and `withCredentials()` for JSON or test-only guard/session IDs differ; only **new B889 synthetic tests** were corrected, never protected fixtures. (b) the genuine application must preserve an authorized session-ID rotation without allowing revoked/deleted record resurrection.
4. Added positive session-rotation RED test [CI #40218755377](https://github.com/AldinAga/ald1n-project/actions/runs/40218755377): **9 cases, 1 expected failure**; actual service `validateAndTouch` rejected a rotated session that retained the original credential proof.
5. **B892 source fix**: `PortalSessionService::recordLogin()` stores the original tracked `session_hash` alongside principal+password digest **inside server session proof**. When the current hash differs, `validateAndTouch()` permits a registered Kupac to transfer *only* the original `user_id`/hash-matching and still active/not logged-out tracked row, with fresh credential proof and bounded atomic update. A missing, revoked, expired-by-password-rotation, unrelated or no-proof record still fails closed. No new row is silently created for Kupac; legitimate separately verified Laravel `Auth::viaRemember()` path preserved; existing staff tracking behavior unchanged.
6. Final [**B892 exact-head targeted CI #40218859254**](https://github.com/AldinAga/ald1n-project/actions/runs/40218859254) **SUCCESS**, real PHP **8.4**/MariaDB **10.11.19**, **9/9 exact distinct PHPUnit identities and 97 assertions**, no failures/errors/skips/warnings/duplicate cases; exact changed-path and PHP syntax, SHA source evidence. [Drive original source+SHA+JUnit ZIP](https://drive.google.com/file/d/1FtAko99q491z_mB0R_rU73kPoSLefNQq/view?usp=drivesdk), file metadata and 18,069-byte size verified.
7. Final [**B892 full B803 #40218859228**](https://github.com/AldinAga/ald1n-project/actions/runs/40218859228): immutable baseline **451 distinct cases/44 failures+errors** (38 failures, 6 errors, four warnings); current candidate **636 cases/98 failures+errors** (89 failures, 9 errors); **`NEW_FAILURES=56`**, exactly **56/56 same identities** as original [PR105 B803 #40214113299](https://github.com/AldinAga/ald1n-project/actions/runs/40214113299), **0 added / 0 removed**. ALL 18 new regression identities from the earlier strict variant are resolved by the safe B892 source repair. Full global suite remains **FAIL / release HOLD**, no waiver/exclusion or fake PASS. [Full original baseline+candidate JUnit, terminal logs, comparison and runtime manifest ZIP on Drive](https://drive.google.com/file/d/1Q2kx1inF2BKVjqjqpThn4rh6HzRXO-MA/view?usp=drivesdk); 367,640-byte Drive readback verified.

## Release boundaries and next closing gates

- **Production Storefront OFF** on B798; original 56 B803 failures in [Issue104](https://github.com/AldinAga/ald1n-project/issues/104) still unreconciled, some old anonymous-sale fixtures conflict with approved verified registered Kupac requirement. Protected fixture/method edits previously safety-denied; no bypass. 368 OWASP WSTG4.2/ASVS5 full applicable items **0 independently accepted**, merchant/SMTP/legal/backup/FPM/CloudLinux/physical acceptance not attested. No owner SSH is justified.
- Android PR82 exact source `6d11b766131ca3cfa946a984b1cc9ed834c00239` native ELF/zipalign/debug AAB/offline 16KiB emulator [CI #40192813557](https://github.com/AldinAga/ald1n-project/actions/runs/40192813557) technical PASS, no redundant build. Signed EAS `versionCode > rejected 27`, verified signer/AAB/device/Google Play M16 **HOLD**; SDK58 M17 deferred.
- One optional PR106 title/body update was **provider safety-blocked** in this development batch and was NOT retried or routed around. Source branch and other permitted operations were independent of that denial.
- Formal owner acceptance **7/17 = 41.2% overall**, CMS **7/15 = 46.7%**, original UI **6/6 = 100%**. No new accepted milestone; local source and scoped CI are not live production approval. No SSH, CloudLinux code/database changes, merge, EAS build, OTA, Play submission, invoice/payment/real SMTP or destructive cleanup.

SOURCE_PR106=c84ea75ace582ce8f8ac85920fc9f543693d1c3a
B892_TARGETED_REAL_HTTP=9_OF_9_PASS_97_ASSERTIONS
B892_B803_BASELINE=451_CASES_44_PROBLEMS
B892_B803_CANDIDATE=636_CASES_98_PROBLEMS
B892_NEW_HISTORICAL_IDENTITIES=56_EXACT_UNCHANGED
B892_ADDED_FAILED_IDENTITIES_VS_PR105=0
OWASP_FULL_ITEM_COMPLETE=0_OF_368
PRODUCTION_STORE_FRONT=OFF
ANDROID_M16=HOLD_SIGNED_AAB_DEVICE_PLAY
ACCEPTED_MILESTONES=7_OF_17_41_2_PERCENT
PRODUCTION_MUTATED=NO
OWNER_SSH_REQUIRED=NO
