# Report855 — B888–B889 Storefront response integrity, regression RED evidence and release HOLD (2026-10-25)

## Scope and verified authority

GitHub main at intake: `068b9a36474f948ae29fcd15fda5aff9a3b140ab` (Report854). Read canonical `AGENTS.md`, latest operations index (known stale B832), Report854, Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md`, open PR82 and PR105, and the 56-identity storefront release gate. This is a **source-only / disposable CI** workstream. No SSH, deployment, production DB, store ON, EAS, OTA, Google Play, merge or host file cleanup.

## Source handoff

Draft [PR106](https://github.com/AldinAga/ald1n-project/pull/106) is stacked on PR105 `601ea692dcfd34f922d7f6544089a622521b8a3b`; current source head at this report `a531f597dde1194132d2fe1664d8bb8886f2f9f1`.

B888 introduces relative `/login` for missing anonymous buyer identity, preserving JSON 401 and storefront OFF before authentication; adds three independent HTTP cases. Initial [CI #40216030207](https://github.com/AldinAga/ald1n-project/actions/runs/40216030207) ran **9 cases, 1 FAIL** because unchanged B809 fixture expected a newly prohibited 200 from a buyer lacking current registration/session prerequisites. Its original failure remains explicitly visible, not waived.

B889 adds four independent cases for invalid registration, authenticated checkout validation, native JSON 422, and revoked browser session under hostile referer/origin. First [RED CI #40216384896](https://github.com/AldinAga/ald1n-project/actions/runs/40216384896): **7 cases, 3 FAIL**; confirmed HTML registration and checkout validation used attacker-supplied external Referer, while revoked-session case returned HTTP 409 instead of local login. Original failing JUnit archived [Drive RED](https://drive.google.com/file/d/1HGsGLiPJByBTXtGxXMj9T1i0EA0QJ1an/view?usp=drivesdk).

A route middleware catch failed because framework exception rendering had already produced a redirect. This ineffective route hook and middleware source were removed. Narrow central Laravel validation exception renderer was introduced in `bootstrap/app.php` for HTML POST `public-shop.register.submit` / `public-shop.checkout.submit`, with trusted local destinations `/registracija` / `/porucivanje`, while preserving native JSON and avoiding nonce/Turnstile secrets in flashed old input. [CI #40216536830](https://github.com/AldinAga/ald1n-project/actions/runs/40216536830): 7 cases, **3 FAIL** at earlier source, pending adequate session-auth handling.

Attempt to bind tracked login proof to session hash was found to deny a legitimate PHP test flow. **Reverted** exactly to PR105 original `PortalSessionService.php` before this report; no unproven session change retained. Following revision [CI #40216768904](https://github.com/AldinAga/ald1n-project/actions/runs/40216768904): 7 cases, 2 FAIL — registration origin was fixed under targeted synthetic test, two authenticated buyer scenarios still failed 302 /login and 401. Last regression probe focuses untrusted Referer without changing Host in the authenticated client, pending verification at [CI #40217023899](https://github.com/AldinAga/ald1n-project/actions/runs/40217023899) for exact head `a531f597dde1194132d2fe1664d8bb8886f2f9f1`. **No final B888/B889 targeted PASS is claimed.** The B803 global full suite [run40217023896](https://github.com/AldinAga/ald1n-project/actions/runs/40217023896) is independently pending; initial prior verified 56 unwaived extra historical failure identities still block release.

## Android 16 KiB — no redundant work

PR82 exact native debug Android15/16KiB emulator + all ELF/zipalign/debug AAB source test already passed [40192813557](https://github.com/AldinAga/ald1n-project/actions/runs/40192813557), source `6d11b766131ca3cfa946a984b1cc9ed834c00239`. Do not confuse this with newly signed EAS/Play artifact. M16 still HOLD: clean integrated source, remote versionCode > rejected 27, EAS signing identity, independently checked signed AAB, physical 16KiB device and Play acceptance. M17 SDK58 deferred.

## Formal status

Storefront public OFF; PR106 Draft, no merge. The unresolved 56 full-suite historical identities and 368 OWASP item checks are not bypassed; physical/SMTP/legal/backup/FPM/merchant acceptance absent. Formal accepted program **7/17 = 41.2%**, CMS **7/15 = 46.7%**, initial UI **6/6 = 100%**. No new milestone accepted and no new host action needed. Next action is observe exact-head B888/B889 synthetic CI and correct actual failed cases from evidence (without changing protected old tests), then exact-head complete B803 and independently complete security approvals. **PRODUCTION MUTATION=NO.**
