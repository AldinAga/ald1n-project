# Report809 — Batch800 public guest retail after-sales and COD warranty source certification (2026-10-24)

KIND=SOURCE_QA_CERTIFICATION_AND_READONLY_OPERATOR_PREFLIGHT_HANDOFF
BATCH=800
SOURCE_PR=https://github.com/AldinAga/ald1n-project/pull/93
SOURCE_BRANCH=feat/b799-public-retail-catalog-cart-checkout
SOURCE_COMMIT=e453f91a1dcbd0f038305e1c9a417165f158dbea
SOURCE_MERGED=NO
SOURCE_PR_DRAFT=YES
BATCH800_SOURCE_QA=PASS
GITHUB_EXACT_HEAD_CHECKS=14/14_SUCCESS
PHP84_MARIADB101119_PUBLIC_RETAIL=31_TESTS_378_ASSERTIONS_PASS
PHP84_MARIADB101119_WARRANTIES=25_TESTS_195_ASSERTIONS_PASS
PHP84_MARIADB101119_PDF=42_TESTS_353_ASSERTIONS_PASS
CMS_STATIC=983_PASS_0_FAIL
DISPOSABLE_CHROMIUM=PASS
SOURCE_PR93_PREDECESSOR=6da19a7dfc551e3561c8ccb5c22c6b46b836b464
PRIOR_HOST_CERTIFICATION=805-B798_OWNER_REPORT804_PASS
HOST_SOURCE_MUTATION=NONE
HOST_DATABASE_MUTATION=NONE
PUBLIC_STOREFRONT_GO_LIVE=HOLD
ANDROID_EAS_OTA_PLAY=NONE
PROGRAM_ACCEPTED_MILESTONES=7/17
CMS_ACCEPTED_MILESTONES=7/15

## RED/GREEN production-significant defect

**Reproduction (RED)**: Earlier B800 source commit `65adcc1022548a90a88616066c8151013d47e741` in MariaDB 10.11.19 job [120799791092](https://github.com/AldinAga/ald1n-project/actions/runs/40069539593/job/120799791092) failed a real-order regression: `OrderWorkflowService::complete()` created a verified positive cash-on-delivery payment but skipped the immutable R4 `warranty_issuances` write, so `WarrantyIssuance::sole()` raised ModelNotFoundException. This defect violated the owner requirement to issue a numbered warranty on the **first verified positive payment** including COD.

**Correction (GREEN)**: inject `WarrantyIssuanceService` into `OrderWorkflowService`, invoking `issueForVerifiedPaymentLocked($locked,$payment,$actor)` in the SAME locked transaction creating the verified COD receipt, BEFORE commit. Existing post-delivery `WarrantyService::ensureForOrder` activates that frozen certificate under the identical number and effective delivered-at date. No duplicate receipt or warranty on repeated complete. Tests cover both registered and genuine `user_id=NULL` guest retail orders. Existing bank transfer verifies payment only with an authorized payment ledger action, issues **pending** numbered warranty without fabricated coverage dates, and activates it on actual delivery. Invalid/unverified payment cannot complete a transfer order or fabricate COD receipt. No live payment was created.

## Guest retail authenticated post-sale and document scope

- Synthetic workflow exercises real superadmin operational order processing/confirmation, courier shipment, tracking number, delivery/completion and exact-once COD settlement. Shipment alone never implies COD collection. Source maintained 0 commission and bounded inventory.
- Adds buyer’s private `/porudzbina/{token}/garancija/{issuance}` PDF endpoint and warranty list for pending and activated warranty. High-entropy order-scoped bearer capability validates the exact order and order-item lineage, non-void issuance, activated warranty ID and certificate number. No guest login or public numeric-ID warranty lookup. Expired, revoked and cross-order tokens are hard denials. PDF response is `private, no-store`, `no-referrer`, `nosniff`.
- Public courier links reuse canonical `App\Support\TrackingUrl::safe()` plus HTTPS-only restriction; CI tests exclude internal/private IPs, noncanonical numeric/octals, localhost and insecure HTTP.
- Real two-process MariaDB last-unit and same-nonce parallel races stay PASS with SQL waiter barrier (Report808). Original browser widths 1440/768/390/320 and two synthetic purchases PASS.
- Exact source GitHub checks: all 14 jobs `completed/success`, including PHP source/static, MariaDB, PDF, browser and older regression. Scoped CI **31/378 retail**, **25/195 warranty**, **42/353 PDF**, CMS static **983/983**. Legacy general-suite baseline problems are NOT claimed globally resolved.

## Verified read-only operator artifact and next host evidence

- Tracked source: [tools/operator-source/ald1n-b800-host-readonly-release-audit-v1.sh](https://github.com/AldinAga/ald1n-project/blob/9961450962578ba9b3af80924a9c39456dc7abb2/tools/operator-source/ald1n-b800-host-readonly-release-audit-v1.sh), commit `9961450962578ba9b3af80924a9c39456dc7abb2`.
- SHA256 (verified local): `dc89cc5413c66ef690044ee1b22c8491f00f7537f7cd27a7480c5ef17eb3d604`. 8,720 bytes. `bash -n` and prohibited process-substitution, /dev/fd and python3 scan PASS. `GIT_OPTIONAL_LOCKS=0` prevents index refresh while inspecting a dirty host. The bootstrap downloads **exact immutable GitHub commit bytes** into `/home/icaffeco/ald1n-project/incoming/ald1n-b800-host-readonly-release-audit-v1.sh`; caller and the script itself validate SHA256 automatically. No separate owner hash command.
- Raw script archived on owner [Google Drive](https://drive.google.com/file/d/1V-4m77iWpqCN5XkDQJAAaW60zBkJwpvM/view). Prior 8,675-byte revision was explicitly renamed `OBSOLETE-DO-NOT-RUN`.
- Script reads B798 owner raw Report804 SHA, exact dirty Git HEAD/track status/.htaccess digest, CLI PHP/MariaDB family, actual public source/feature flags and **presence only** of merchant setup, and backup root. It creates only one numbered report in canonical `docs/operations`, allocating max(GitHub current, local host)+1. No customer/payment/merchant secrets printed; no source/DB mutation, migrations, maintenance, sync or backup prune.
- A host **read-only PASS is NOT an authorization for M13–M15 publication**. It cannot prove legal acceptance, support-mail delivery, actual buyer UAT, PHP-FPM/OPcache source or two restore-ready backups. User still must explicitly approve true merchant policies, shipping fee (currently nonzero unsupported; fail-closed) and accepted payment methods before any public go-live. No guessed zero-fee or fictional terms.

## Sequencing / owner handoff

The next step requiring owner involvement is one bootstrap+SSH invocation of the canonical read-only audit, then return its generated numbered operations report. Review evidence before designing a separate controlled full-source CloudLinux host delivery; preserve any dirty host state and keep release flags OFF. **Do not merge/deploy PR93 merely because CI is green**; PR92 remains separate. B798 and earlier completed host batches must not be replayed. Android signed 16KB AAB M16 and SDK58 M17 remain deferred by their own gates. Source work and documentation stay autonomous; no owner explanation-only handoffs.
