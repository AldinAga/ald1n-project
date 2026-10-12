# Report 646 — Batch646 Mobile unavailable-order UI Draft PR7, exact-source CI

Date (Europe/Belgrade): 2026-10-13
Authority: `AGENTS.md`, latest `docs/operations/000-LATEST.md`, Report645, unchanged production Build25
Mode: GitHub source-only Draft PR + isolated GitHub Actions; no production or customer data mutation.

## Report645 dependency and business conclusion
- Original Report645 `645-BATCH645-LIVE-ORDER134-READONLY-DIAG-20261013-013543.md` archived byte-for-byte to GitHub main, raw SHA256 `7f13558d9061d10b8eb45c46d0df1191cc9a5fa6d2d982a365b6d2cb41a657ac`, Git blob `7f6659ab9539e872a88dbb707ef8ee868ea892c6`, archive commit `82e96623454ba12fdb0aad772c802a50c026c9fa`.
- Host MySQL read-only result: Order134 FOUND, CANCELLED, ARCHIVED=YES, PURGED=YES. It is not a valid positive 200/detail UAT fixture. Canonical model route binding excludes archived rows and returns EXPECTED_404; actual live HTTP not run.
- Historical inbox Order134: 12 records (2 admin legacy, 10 user), 2 push records; real CMS source matches current main, not Draft PR4. No authenticated test sessions or physical Android UAT.

## Draft PR7 Mobile-only change
- [Draft PR7](https://github.com/AldinAga/ald1n-project/pull/7) `feat/batch646-mobile-order-unavailable-ux-20261013`, exact candidate HEAD `a08322a62d7a62a95464ba0345325a73ffc78af2`, based on canonical GitHub main `6744bf0c058db632621338bc3442abf89918072b`.
- Exactly four Mobile-only file changes:
  1. `src/features/orders/order-unavailable-state.tsx` — neutral inaccessible-order message, no purged/owner details, safe return to `/orders` or `/admin/orders` depending on current screen; only `ApiError.status === 404` is treated as missing.
  2. `src/app/(app)/admin/orders/[id].tsx` — 404 state and disabled automatic 404 retries; other API errors keep existing generic error UI/retry behavior.
  3. `src/app/(app)/order/[id].tsx` — same for owned order detail.
  4. `scripts/batch646-order-unavailable.test.mjs` — executable real TypeScript TSX component transpilation, actual 404 classifier, button route and source integration checks.
- No CMS model/API/OpenAPI/permission modifications, no deleted data and no native/runtime critical files changed. Draft PR4 remains independent/unmerged.

## Exact-source CI verification
- CI runner workflow branch `audit/batch646-mobile-order-unavailable-ci-20261013`, workflow HEAD `f079183e9992d9e3d3691d5a58827f30b09cb578`, **checks out PR7 candidate exact SHA `a08322a62d7a62a95464ba0345325a73ffc78af2`**, not the workflow commit.
- GitHub Actions [run 38323541320](https://github.com/AldinAga/ald1n-project/actions/runs/38323541320) **SUCCESS** using ephemeral Node 22.16, Temurin JDK17 and npm ci --ignore-scripts with no persisted artifacts.
- New real TSX-component behavior regression tests **4/4 PASS, 0 fail** (404-only classifier; admin return route; user return route; both screen integrations).
- TypeScript `npm run typecheck` **PASS**.
- Canonical `node scripts/validate-project.mjs` **PASS, Ukupno FAIL: 0**.
- Full 15-file Release25 JDK17/Java signature suite **125/125 PASS, 0 fail**.
- Git tracked/staged/untracked source immutability **PASS**; no build artifacts uploaded.
- Note: CI proves source/UI logic and regressions, NOT physical Android cold/warm-start or real user HTTP response.

## Open gates and strict non-mutation
- PR4: [Draft PR4](https://github.com/AldinAga/ald1n-project/pull/4) head `25d29162c01938b7a58abe6914fee403beb72879`, **DRAFT/NOT MERGED/NOT DEPLOYED**. Historical administrator routing is correctly tested in isolation but cannot restore Order134's purged detail.
- PR7 **DRAFT/NOT MERGED/NOT DEPLOYED**. Explicit codeowner review, merge approval, authenticated role-scoped HTTP tests on nonpurged authorized order (separate gating), and Android UAT still required.
- Never use Order134 as a positive 200 detail acceptance fixture; 404 is expected for archived/purged status. No reactivation or DB mutation to force it open.
- Owner skipped isolated backup restore; backup #151/#150 file and DB authority PASS, restore readiness HOLD. Third backup and 82 unarchived operations reports protected.
- No production CMS code, customer database, mobile build, EAS update/OTA group, Google Play, signing or backup mutation.

REPORT646_PR7_BEHAVIOR=4_OF_4_PASS
REPORT646_PR7_TYPESCRIPT=PASS
REPORT646_PR7_VALIDATOR=FAIL_ZERO
REPORT646_PR7_RELEASE25=125_OF_125_PASS
REPORT646_PR7_CI_RUN=38323541320_SUCCESS
REPORT646_PR7_HEAD=a08322a62d7a62a95464ba0345325a73ffc78af2
REPORT646_PR7_MERGED=NO
REPORT646_PR4_MERGED=NO
REPORT646_LIVE_HTTP=HOLD
REPORT646_ANDROID_UAT=HOLD
REPORT646_PRODUCTION_MUTATIONS=NONE
