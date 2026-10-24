# Report822 — B812 registered Kupac Chromium PASS, inherited CI failures / production HOLD

DATE=2026-10-24
BATCH=B812
KIND=DISPOSABLE_REAL_HTTP_BROWSER_QA
PR=https://github.com/AldinAga/ald1n-project/pull/93
SOURCE_HEAD=76be91d1fe434708dd5f4a47ea635bb59f1fbfd6
LAST_ACCEPTED_PRODUCTION=B798_STOREFRONT_OFF
B812_BROWSER_RUN=https://github.com/AldinAga/ald1n-project/actions/runs/40102331513
B812_BROWSER_RESULT=PASS_REGISTERED_REAL_CHROMIUM_AND_DB_POSTCHECK
EXACT_HEAD_OVERALL_CI=9_OF_14_PASS_5_FAILED
B803_BASELINE=451_CASES_44_PROBLEMS
B803_CANDIDATE=499_CASES_90_PROBLEMS
B803_NEW_FAILURE_IDENTITIES=48
MERGE_AND_RELEASE=HOLD
PRODUCTION_MUTATIONS=0
OWNER_SSH_REQUIRED=NO
PROGRAM_ACCEPTED=7/17_41.2_PERCENT
CMS_ACCEPTED=7/15_46.7_PERCENT

## Preflight reconstructed before B812

Read fresh GitHub main e9e9f641140aa9fc6e57f63aa0b148de9115fcc0, Draft PR93 head 71e5c92364384ee189486671021e69fff2db036c, root AGENTS.md, 000-LATEST.md, Report821, and the canonical Drive ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md. B811 new synthetic lifecycle succeeded on the exact earlier head in run 40101779104 with 4 PHPUnit tests / 91 assertions PASS; B809+B810 target suites 12 / 160 PASS. B811 overall B806 workflow still FAIL due untouched historical guest-positive fixtures. These failing test identities remain release blockers, not skipped or waived.

## Implemented B812 — NEW browser flow, old tests preserved

Added new isolated source files:
- apps/cms/current/tests/Browser/seed-b812-registered-buyer.php
- apps/cms/current/tests/Browser/b812-registered-buyer-browser.cjs

Updated the existing .github/workflows/b799-chromium-qa.yml so the new B812 disposable PHP8.4/MariaDB10.11.19 and Playwright Chromium step runs FIRST, followed by the original B799 guest/browser step UNCHANGED. Existing legacy assertions remain enabled and can legitimately fail the job. Added a separately named B812 synthetic browser screenshots artifact. No separate workflow and no changes to application runtime, migrations, HTTP routes, production SMTP, checkout business logic, or historical fixture source.

B812 fixture is strictly pinned to APP_ENV=testing, MariaDB version 10.11.19, test database ald1n_test, loopback HTTP port 8124, and array mailer. A real browser creates a pending customer via HTTP, then a CI-only helper substitutes a deterministic synthetic activation-token hash for its already-created token. This emulates receiving the link WITHOUT ever sending actual mail or claiming SMTP deliverability. Live mail remains an independent release gate.

New real Chromium scenarios include 1440px/390px guest catalog and signup visuals, hidden cost/commission checks, guest checkout redirect, real signup, one-time activation and token replay denial, login with retained guest cart, registered-only checkout with verified readonly email, private no-store receipt, identical nonce replay and unchanged stock, and an independent signed-in operator denied public checkout. CLI postcheck verifies exactly one buyer-linked operational retail order, one stock decrement, zero commissions/payments, and used activation token.

The B812 source/head is 76be91d1fe434708dd5f4a47ea635bb59f1fbfd6. Exact-head browser workflow run 40102331513 was pending when this report was created; do NOT infer QA PASS from committed code. Earlier exact-head CI jobs that had been superseded by further commits may be canceled; only final-head evidence can be release authority.

## Safety and production gates

One nonessential proposed PHP test-postcheck numeric-comparison edit was denied by connector safety control. The denial was respected: no retry, alternate transport, rewritten script, or bypass of that denied write. The actual checked-in test code is the source of truth.

Historic B799/B802/B803/B806/B808 fixtures still assume anonymous successful checkout, which conflicts with B809 owner-mandated registered-only checkout. A previous attempt to alter those historical fixtures was safety-denied; do not repurpose the new B812 tests to waive or overwrite the old ones. Whole-CI and release remain HOLD, including 368 OWASP control items, actual SMTP/SPF/DKIM/DMARC delivery, merchant legal approvals, restore drill, FPM/OPcache attestation, buyer UAT, and production deployment controls. Do not switch storefront ON, merge PR93, run SSH scripts or create Android/OTA/Play artifacts. Final inode cleanup stays deferred to issue95.

Functional acceptance: overall 7/17=41.2%, CMS 7/15=46.7%, initial UI 6/6=100%. B812 test engineering is not a new accepted production milestone.

## Terminal exact-head B812 evidence — 2026-10-24

FINAL_HEAD=76be91d1fe434708dd5f4a47ea635bb59f1fbfd6
EXACT_HEAD_CI=9/14_PASS_5/14_FAIL
B812_CHROMIUM_STEP=SUCCESS
B812_REAL_DB_POSTCHECK=PASS_REGISTERED_ORDER_1_STOCK_DECREMENT_1_NO_COMMISSION_OR_PAYMENT
B799_LEGACY_GUEST_CHROMIUM_STEP=FAIL_AS_EXPECTED
B803_NEW_FAILURES=48_EXISTING_GUEST_ASSUMPTION_IDENTITIES
NO_PRODUCTION_MUTATION=YES

[GitHub Actions B812 exact-head Chromium](https://github.com/AldinAga/ald1n-project/actions/runs/40102331513), job 120897179632, shows:
- PHP seed under disposable MariaDB 10.11.19 and array mail = PASS.
- Registration/activation UI using synthetic token substitution, login, guest cart preservation, registered buyer checkout, receipt and replay = PASS.
- Browser role gate = PASS, operator denied.
- Database postcheck = PASS: one customer-linked order, one stock decrement, no commissions or payments.
- B812 synthetic Chromium screenshots artifact **12563143389** (6 PNG screenshots: desktop/mobile catalog/registration, checkout, receipt), verified downloadable ZIP 3c436fa8fdf942e9741bd97da385ac83028bb766813032a97d46084b4b627c37. Screenshots are synthetic and are browser evidence, NOT owner physical-device UAT.
- Original B799 browser stage remained enabled and independently FAILED awaiting the now-absent `Nastavi na poručivanje` link for a guest. No legacy fixture rewrites, skips or workarounds.

[Full B803 comparison](https://github.com/AldinAga/ald1n-project/actions/runs/40102331597), job 120897000370, records `BASELINE_CASES=451 BASELINE_PROBLEMS=44`, `CANDIDATE_CASES=499 CANDIDATE_PROBLEMS=90`, `NEW_FAILURES=48 WHOLE_SUITE_GREEN=NO`. The same 48 old guest-assumption identities persist as B810/B811; B812 introduced **zero additional full PHPUnit cases** beyond the four previously green B811 cases. Exactly **nine of fourteen** exact-head workflows were successful, five failed on historical guest-role/checkout/browser/full-suite checks. This is source integration QA, **not** a green release.

Drive Report822: https://docs.google.com/document/d/1Mp2ZpITwMuQo4ckinG6qooSVl7EOv6P-olSFYyaMisA/edit
Drive source snapshot: https://docs.google.com/document/d/1WNI9wCo4XUrNh4xu9NxDvIm2TXytcocExGZawbcQEYQ/edit

The attempted automatic direct ZIP-to-Drive artifact upload did not complete because the connector's file_uri validation requires a string while the source connector returned an object. No upload success is claimed; GitHub artifact remains authoritative and user-downloadable in the current conversation. The report and source snapshot DID save to Drive and were verified by readback.

MERGE_RELEASE=HOLD. Preserve Draft PR93, current production B798 OFF, historical failures and pentest/merchant/SMTP/restore/FPM/UAT gates. Owner involvement unnecessary; no SSH.

## B813 archival follow-up — original B812 evidence uploaded and verified

The earlier B812 screenshot ZIP Drive upload failed because it passed a connector `file_uri` object to an upload argument that expected its string `file_id`. On 2026-10-24 the exact existing GitHub Actions artifact **12563143389** was downloaded without alteration and the proper connector file reference string was used. Google Drive **accepted the ZIP**, and independent Drive metadata readback verified its identity and MIME type:
[B812 Chromium screenshots ZIP — Google Drive](https://drive.google.com/file/d/1-Y4eyttGbio00uixEW2CGH_8SeUarTu-/view).

This closes the ZIP archival transport issue only. It does not change the B812 source, synthetic browser PASS, original guest fixture failure, production HOLD or the separately safety-denied B805 archive action. This later archival result supersedes the earlier statement that B812 ZIP had not yet been saved on Drive.
