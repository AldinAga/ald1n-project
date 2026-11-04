# Report1013 — B1087: ECC security fixtures, 73 to51 failures, weekly reserve stop

2026-11-04 Europe/Belgrade. Report sequence1013 follows current GitHub main and both local maxima1012. Overall goal remains incomplete; no source work beyond the19% weekly reserve boundary.

## Exact scope and RED

Source32900c20189e3ee2154dbb6adeb2fbec3d6e7807 follows combined Draft33d7c7e0e6bd09b4138712bf78ad1c161007ef8f. Exactly two paths changed: unprotected tests/Feature/StorefrontSecurityB806Test.php and existing .github/workflows/b1082-storefront-current-main.yml. No application source, auth/session middleware, routes, money, stock logic, schema, dependencies or protected B805/B809/B815 edits. git diff --check and exact path review passed.

The prior actual712/67 run41802399319 showed all16 original B806 cases fail at their anonymous checkout preparation (HTTP302), before their security assertions. ECC laravel-tdd/laravel-verification were explicitly requested by the owner; use existing PHP8.4/MariaDB10.11.19 GitHub-only infrastructure, not generic local/Sail/SQLite examples.

## Minimal fixture repair and actual GREEN

Create a synthetic active customer with required six-field profile, email_verified_at and portal_activated_at, then perform real login and assert authenticated identity. Preserve every original host/forwarded-header, redirect, caching, forged credit/delivery/policy/consent/nonce/honeypot, mass-assignment, XSS, stock, payment, commission and private-capability assertion. The old guest user_id=null assertion becomes the owner-required exact registered buyer ID. No application authorization changed. Added one actual logout/guest GET redirect/JSON401 case proving no orders, stock movements, attempts, capabilities, payments, commissions or mail writes and unchanged stock.

[Actual run41804057598](https://github.com/AldinAga/ald1n-project/actions/runs/41804057598), job126486590863: focused **117tests/1359assertions,0failures/errors/skips**, RC0. Same100prior focused cases plus all16 original security cases and1 new guest denial; both total and unique identity census117 checked. Full suites always run even on a focused failure.

Full unfiltered result: frozen earlier main487/17failedIDs, candidate **713tests/8errors/43failures=51failedIDs**,0skips,0missing baseline cases and all640 actual old PR131 IDs retained. Compared with verified67, exactly16 original B806 identities now pass, zero new failed identities. Compared with initial73, **22 failures resolved**, all prior test identities retained; one additional guest-denial case executes. Full workflow correctly remains FAIL because39 commerce failed IDs are new versus frozen earlier main baseline; this is not a fresh full-main census or a release waiver.

Raw original/candidate/focused XML, full logs, real RCs, complete identity proof and comparison: artifact13309168144, SHA256 **821fab273c23525077a79c6fe7ba9117856f9feb7a3a75932825b5e8e84ba5b4**. No dependency directories, secrets or real customers in retained evidence. Exact owner-readable51-case MD/ExcelCSV/JSON generated from current JUnit with Git source/method line verification.

Remaining51 categories:24HTTP302 old guest setup expectations,7HTTP403 invalid positive buyer fixtures,3HTTP404 old anonymous receipt expectations,9assertions,8actual JUnit runtime errors. Error categories come from XML error nodes, not text search. Protected-fixture denial and all security/merchant/legal/mail/host/backup/device/native/release gates stay binding.

## Release, menu and handoff

PR135 remains Draft/unmerged. Prior verified main menu PR137 and four delegated contracts PR138 stay merged; menu ZIP D:/GPT/Ald1n-menu-direktni-paneli-20261104.zip is still the exact two-file owner delivery. Owner requires navigation left and DIRECT Theme/Currency panels right, opaque background, no settings submenu. No new hosting upload confirmation; never control FileZilla.

No production storefront activation/backend rollout, DB/flags/SMTP, EAS/build/OTA/Play, no local PHP/MariaDB/Docker. Dirty worktree and D:/GPT/Ald1n-mobile preserved. Next agent starts from exact32900c source and51-case census, not old73/67; repair unprotected fixtures through real registered sessions and actual behavior, never relax auth or bypass protected B805/B809/B815 denial.

Formal acceptance unchanged11/17=64.7%,CMS10/15=66.7%,originalUI6/6. Weekly Usage19%, user ceiling20%: stop new source work and retain completion/handoff reserve. No reset redeemed. Goal is incomplete, not marked complete.
