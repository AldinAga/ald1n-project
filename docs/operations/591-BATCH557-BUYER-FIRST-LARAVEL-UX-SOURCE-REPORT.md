# Report591 / Batch557 — Laravel Admin Order Buyer-First UX (SOURCE CANDIDATE)

**Date:** 2026-10-10  
**Source authority before batch:** GitHub main `a6da85b9e35fb6043d5d498af1403487d48cc43b` + verified Project `AGENTS.md`, Report590 and documented UX2.0 spec/plan on design branch.  
**Design parent:** `320eaf41544c820f5cbb909edd728671363f8568` (documentation-only against main).  
**Implementation branch:** `feat/ald1n-order-buyer-first-web-20261010`  
**Last implementation commit before this report:** `c2e0ca2bbed5a17d8f6271ecf634530276573f41`  
**Deployment:** NOT deployed, NOT merged, source-only.  
**Mobile/Build25/Google Play:** unchanged. `BUILD_CREATED=NO`, `GOOGLE_PLAY_ACTION=NO`.

## Implemented
- `apps/cms/current/resources/views/admin/orders/show.blade.php`: move single authoritative saved shipping-recipient contact section `#order-workspace-customer` from bottom of right sidebar to **the first actual card in the new top priority layout**, in DOM order before the command and items.
- `apps/cms/current/public/assets/css/ald1n-ui-v2.css`: desktop two-column priority with buyer on **right** and compact command on left; at <=980px, buyer stacks **first**. Readable labels, safe phone call action, full address and buyer note; no new dependency, extra API call or DB change.
- `apps/cms/current/tests/Feature/OperationalOrdersCommissionsTest.php`: add three regression tests for unique buyer card/ordering/shipping fields/creator separation, empty phone safe fallback, and CSS responsive ordering/touch/focus.

## TDD and available evidence
- Negative-first 5 failing source-layout assertions against unmodified main; old buyer below full command and items; old CSS lacked buyer-first grid.
- Source-contract check on branch: **13/13 PASS**, including unique card anchor, right/first layout areas, existing shipment/delivery anchors and regression test presence.
- Isolated PHP CLI sanitizer probe for `+381 60 123 4567`, `060123456`, `—`, `abc+44`: normalized expected valid/invalid outcomes.
- **NOT RUN / NOT CLAIMED:** Laravel PHPUnit feature suite, Blade rendering and canonical `php bin/static-check.php` 983/983. The chat sandbox does not contain the canonical Laravel repo/composer/vendor or private production host. Source contract is NOT an integration PASS.
- **NOT RUN:** responsive browser visual acceptance; validate on authorized staging/physical small viewport before merge.

## Canonical-host validation instructions (isolated worktree only)
1. Fetch this exact branch and preserve known-dirty production checkout; do not checkout/merge/reset `/home/icaffeco/ald1n-project` directly.
2. `cd apps/cms/current && php artisan test --filter OperationalOrdersCommissionsTest` — expect the three new tests and unchanged prior assertions to pass.
3. `cd apps/cms/current && php bin/static-check.php` — canonical suite expected `Ukupno: 983, neuspešno: 0` only if it actually runs; unexpected new failures require diagnosis.
4. Render 1440px/1024px/390px and verify actual visual right-first and buyer-first order plus focus/tel/CSRF.
5. Verify no unrelated files/OPenAPI drift, source SHA, diff --check before merge/deploy.

## Gate/result
**SOURCE PATCH READY FOR CANONICAL TESTING, NOT PRODUCTION READY.** Preserve existing Laravel financial and logistics transition semantics; #134 root cause is separately unresolved. No release authorization inferred.
