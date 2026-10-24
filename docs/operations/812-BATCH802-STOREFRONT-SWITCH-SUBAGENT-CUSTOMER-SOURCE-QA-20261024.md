# Report812 - B802 storefront ON/OFF, SubAgent and Kupac source QA

DATE=2026-10-24
KIND=ISOLATED_SOURCE_QA_NOT_PRODUCTION_DEPLOYMENT
SOURCE_PR=https://github.com/AldinAga/ald1n-project/pull/93
SOURCE_BRANCH=feat/b799-public-retail-catalog-cart-checkout
SOURCE_COMMIT=32def73e4145b00f51e672b7b1810b34d8fe44e4
SOURCE_TREE=7ef83d5fbb598d16ccf9f8c48e75ad8432b5a737
PREDECESSOR_SOURCE=54b9a12708f654cc5d3351900de2bae624eb2cd3
CURRENT_MAIN_AT_START=cc834f29f4cb9ba5bc201dc9c49bb9f919562a5a
EXACT_HEAD_CI_WORKFLOWS=12/12_COMPLETED_SUCCESS
B802_REAL_MARIADB_TESTS=12_PASS_116_ASSERTIONS_0_ERRORS_0_FAILURES_0_SKIPPED
PHP_CI=8.4.27
DATABASE_CI=MARIADB_10_11_19
SOURCE_PR_DRAFT=YES
SOURCE_MERGED=NO
PRODUCTION_SOURCE_DB_SETTINGS_WRITES=NONE
PRODUCTION_PUBLIC_RELEASE=HOLD
EAS_NATIVE_OTA_PLAY=NONE
NEXT_OWNER_SSH=NOT_REQUIRED_FOR_SOURCE_ONLY_WORK
ACCEPTED_PROGRAM_MILESTONES=7/17_UNCHANGED

## Implemented owner requirements

1. Real SuperAdmin ON/OFF control in Settings / Public Storefront. State is persisted as public_storefront_open, defaults OFF, and is enforced by public catalog, cart and checkout. OFF is read directly from the database rather than a stale settings cache; checkout shared locking serializes committed purchases against OFF. ON requires technically deployed code/schema and an approved merchant policy. OFF needs no valid merchant policy. Both actions are authorized, CSRF-protected, throttled and audited. OFF does not delete orders or disable existing private receipt/document access.
2. Legacy role user (ID1) becomes SubAgent by display/name only. Existing IDs, role slug, group rights, orders and commissions are preserved. A separate customer role named Kupac is added with an idempotent non-destructive migration. A customer cannot inherit internal group permissions even when assigned a privileged group; internal catalog, commissions, admin and internal APIs are denied, including with a wildcard token. The dashboard redirects the customer to /katalog. No new mobile buyer UI or public self-registration was silently invented.
3. Kupac uses the same public retail layout, retail prices, cart and ordering as the public storefront. The authenticated buyer ID is resolved server-side and stored on the real order; posted user_id is ignored. Buyer-selected delivery and payment are allowlisted and snapshotted, shown in private receipt and existing admin order workspace, and bound into idempotency. No commission or artificial payment/delivery proof is created.

Existing delivery codes: courier, own_transport, customer_pickup and optional other. Existing payment codes: cash_on_delivery, bank_transfer and deferred_payment. Only merchant-enabled options are offered. Deferred payment additionally requires explicitly configured terms and a 1-365 day due period; the due date is server-calculated, order remains unpaid, and no payment receipt is fabricated. Separate nonzero delivery charging remains unsupported by the existing order subtotal and must not be guessed or treated as free without real merchant approval.

## Verification and RED/GREEN evidence

- Initial RED commit800a76fa71a1bb8f47765b43fef44354f6943ffd: four expected missing-feature failures, real PHP8.4.27/MariaDB10.11.19, workflow40074026968. Second RED08b41006e2c847afb18555e1c28e7fe6aa61ee18: seven expected failures including delivery selector, invalid delivery rejection and deferred payment support, workflow40074467381.
- First implementation edb08e4acd10c29ae8ddeb9ed73acf90664d22b8: ten tests,87 assertions, one test-target error (invented /api/v1/catalog instead of actual /api/v1/products). Actual customer purchase/delivery/deferred tests passed. Corrected the route, retaining all authorization denial assertions.
- Final exact head32def73e4145b00f51e672b7b1810b34d8fe44e4: B802 workflow40076274715, twelve tests,116 assertions, zero failures/errors/skips. Seven access/switch/migration tests and five purchase/preference tests. JUnit artifact12553686244 SHA256=1e49f9693b290e8ec826c8a0f91b8be7f2974b8a7af8057a63d52ddfb97900eb, downloaded and parsed.
- All twelve exact-head PR workflows completed SUCCESS, including prior public retail, PDF/warranty, retirement, dashboard, authenticated Chromium and public Chromium. Public Chromium workflow40076274728 includes two real simultaneous MariaDB checkout cases and responsive checkout; it passed after mandatory delivery selection was added to the browser fixtures. No actual production purchase or email was involved.
- Local 37 changed PHP files passed php -l and browser JS passed node --check. The intentionally text-only local source snapshot excludes tests/Fixtures/pdf-logo.jpg; its static scan was982/983 with that sole missing binary fixture, not a source defect. The canonical static gate passed in full-source CI (Liquid Glass workflow40076274726). Do not describe the stripped local snapshot as a complete runnable deployment.
- Local/remote blob equality was verified for the implementation tree; the final workflow blob is f9ae96ed68693773fd558885ca1987188d8c4694. Archive includes changed text source, tests, manifests, plans, development helper scripts and RED/GREEN evidence. No font binaries or production data.
- Review was structured author self-review plus automated CI, not an independent human/subagent review. Existing unrelated full-suite debt is not claimed resolved.

## Permanent behavior and delivery boundary

Preserve SubAgent identities and financial ownership; never mass-convert legacy users to Kupac. Kupac must not inherit internal permissions through groups, tokens or intended redirects. The storefront control is an actual database-backed business switch, not merely editable ENV text. It must remain OFF until the controlled initial deployment and merchant approval are ready. New checkout selection is a preference, never proof of dispatch, delivery or paid status. Repeated checkout cannot change owner/delivery after commit; OFF cannot erase existing purchase evidence.

Latest actual hosting authority remains owner Report810 read-only PASS and B798 deployment Report804/805: 84 dirty tracked paths, preserved HEAD/index/.htaccess, public storefront code/tables not deployed, real legal/shipping/payment/support policy and backup/FPM/UAT gates pending. No new SSH diagnostic replay or deployment script was issued in B802. The source stays in existing draft PR93; do not merge competing PR92 or auto-launch production.

Earlier safety-blocked edits to 000-LATEST.md and the canonical Drive runbook were not retried or bypassed. This new report and PR description are the current B802 evidence; no claim is made that those blocked indexes or root AGENTS were updated in this turn.
