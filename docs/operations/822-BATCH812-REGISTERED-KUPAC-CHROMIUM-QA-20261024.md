# Report822 — B812 registered Kupac Chromium acceptance (source-only, CI pending)

DATE=2026-10-24
BATCH=B812
KIND=DISPOSABLE_REAL_HTTP_BROWSER_QA
PR=https://github.com/AldinAga/ald1n-project/pull/93
SOURCE_HEAD=76be91d1fe434708dd5f4a47ea635bb59f1fbfd6
LAST_ACCEPTED_PRODUCTION=B798_STOREFRONT_OFF
B812_BROWSER_RUN=https://github.com/AldinAga/ald1n-project/actions/runs/40102331513
B812_BROWSER_RESULT=PENDING_NO_PASS_CLAIM
FULL_CI_RESULT=HOLD_PREVIOUS_GUEST_FIXTURES
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
