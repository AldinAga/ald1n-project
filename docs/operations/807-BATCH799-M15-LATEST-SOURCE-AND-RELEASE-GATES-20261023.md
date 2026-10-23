# Report807 — B799 M15 expanded source candidate and release gates (2026-10-23)

KIND=READ_ONLY_GITHUB_RECONSTRUCTION_AND_DRAFT_REVIEW
LATEST_MAIN_AT_START=9ff167b0214a311d35e8e8ab5c33148312adcc42
SOURCE_PR=https://github.com/AldinAga/ald1n-project/pull/93
SOURCE_BRANCH=feat/b799-public-retail-catalog-cart-checkout
SOURCE_COMMIT=89c19d50a4985aaa35d5d3f8cbc52ddcc4c418f4
PREVIOUS_SOURCE_QA=806
LAST_ACCEPTED_HOST_BATCH=798
LAST_ACCEPTED_RAW_HOST_REPORT=804
LAST_ACCEPTED_REDACTED_HOST_REPORT=805
PRODUCTION_MUTATION=NONE
HOST_SSH_EXECUTION=NONE
GOOGLE_PLAY_OTA_NATIVE_BUILD=NONE
PUBLIC_STOREFRONT_RELEASE=HOLD
BATCH799_PROGRESS=SOURCE_CANDIDATE_M13_M14_M15_CODED_NOT_ACCEPTED

## Updated branch evidence

The original Report806 documented commit 4be2ff7e568f26f34ca8d59ba3d268a5a80a29bf (catalog/cart only). Since that checkpoint, PR93 has 35 commits, ending at 89c19d50a4985aaa35d5d3f8cbc52ddcc4c418f4, and 32 changed files. The new source additionally implements merchant readiness, anonymous idempotent purchase, private 60-day order capability, per-order issued document access, admin revoke, two new additive tables and synthetic Chromium checkout tests. This is development evidence; the PR remains DRAFT, not merged/deployed.

GitHub commit check-runs for the exact head: 14/14 completed success. Includes isolated public-retail PHP8.4/MariaDB10.11.19 workflow, real disposable localhost Chromium purchase with separate second checkout, older CMS/integration/retirement/warranty pipelines. These successful check runs do not prove a live production payment, merchant policy readiness, or two independent transactions racing for one unit.

## Targeted source review findings

1. Checkout transaction locks product rows with orderBy(id)/lockForUpdate and binds one checkout nonce through a unique `public_checkout_attempts.key_hash`. The PHP tests prove sequential duplicate/retry and the browser test proves distinct second purchase. They do NOT explicitly prove last-unit contention across two independent concurrent database connections. That is a distinct release QA gate.
2. The checkout readiness guard accepts only explicitly configured legal/privacy HTTPS URLs, merchant identity, support email, payment methods, and delivery policy; its current order total cannot account for separately charged delivery, so it correctly rejects nonzero shipping fees. Real merchant approval/configuration is still absent from available evidence. CI synthetic example.test settings are not merchant attestation.
3. The bearer access token is 256-bit, hashed at rest, time-limited/revocable and private headers are applied; public order/document fetching checks the matching order. Production token/notification telemetry and end-to-end guest after-sales acceptance remain unproven.
4. New code uses a genuine operational `orders` record with `user_id=NULL`, no commission, stock movement and payment pending. The source CI has a basic admin processing/confirmation/PDF test. Full legacy workflow/notification branch parity under real order handling still needs final targeted review.
5. The source uses two additive tables with lock-wait bounds; there is no authorization to execute migrations on CloudLinux. Existing host Git HEAD/index and protected .htaccess must be preserved; source may be ahead of the host's dirty checkout.

## Next action

Continue B799 on **existing PR93** with independent concurrent stock/idempotency tests plus targeted security/after-sales review; eliminate remaining reproducible issues on that branch and rerun exact-head GitHub CI. Do not create another competing public-shop implementation, do not replay B798/797/etc., and do not mark M13–M15 CLOSED merely because code exists. Once source, merchant readiness, two fresh stable backups and host constraints are verified, deliver only one self-checking canonical `incoming/` CloudLinux deployment script for owner SSH. No SSH handoff at this stage.

## Progress and owner policy

Closed acceptance ledger unchanged: original six UI checks 6/6, CMS 7/15 and program 7/17 = 41.2%; source work is not production acceptance. Permanent owner rule: do all possible GitHub/CI/Drive work autonomously and communicate only completed results or genuinely necessary SSH commands. `AGENTS.md` owner execution autonomy was reinforced in commit f92622e10e1fb92370d609e0ca7befad08568664. Drive canonical runbook may carry stale B797-PENDING status due a previously blocked tool write: GitHub current 000-LATEST plus Reports805/806/807 supersede dated snapshots; do not bypass a denied in-place Drive edit.
