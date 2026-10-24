# Report821 — B811 registered Kupac lifecycle QA, CI pending

DATE=2026-10-24
KIND=SOURCE_ONLY_NEW_SYNTHETIC_TESTS
PR=https://github.com/AldinAga/ald1n-project/pull/93
PREDECESSOR_SOURCE=c8730eaf90ee0dcbdb86185d3269f5808fd1b0eb
NEW_TEST_COMMIT=c76fe65788badcac0f79db48cfbbeb971c3cdd2f
NEW_WORKFLOW_COMMIT=71e5c92364384ee189486671021e69fff2db036c
B811_WORKFLOW_RUN=https://github.com/AldinAga/ald1n-project/actions/runs/40101779104
B811_CI_RESULT=PENDING_NOT_VERIFIED
FULL_CI_RESULT=NOT_VERIFIED_ON_B811_SOURCE
MERGE_RELEASE=HOLD
PRODUCTION_MUTATIONS=0
STOREFRONT=OFF_LAST_HOST_ATTESTED
OWNER_SSH_REQUIRED=NO
PROGRAM_ACCEPTED=7/17_41.2_PERCENT
CMS_ACCEPTED=7/15_46.7_PERCENT

## Fresh authority and scope
GitHub main `e196ecb7afcdd5359754a32a4888ea99e91229e1`, root AGENTS.md, docs/operations/000-LATEST.md, Report820, and canonical owner Google Drive ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md were independently read before this change. PR93 was Draft at head c8730eaf; no source merge or production rollout was authorized.

Added only NEW `apps/cms/current/tests/Feature/StorefrontRegisteredBuyerLifecycleB811Test.php` with four synthetic MariaDB feature scenarios:
1. Guest may hold cart but cannot checkout; registers pending, activates one-time email link, signs in, then places once-only public order with zero commissions and protected prices.
2. Duplicate pending signup cannot create a second token within the resend throttle; expired activation cannot log in or reserve inventory.
3. Authenticated cross-customer replay of completed checkout nonce cannot transfer or duplicate an order.
4. An obsolete merchant-policy fingerprint cannot reserve stock or create order attempts.

Existing B799–B808 guest-positive historical tests were NOT edited or disabled. No production logic, migrations, authenticated SMTP, real buyer details, stock, DB or mail was touched.

Updated only existing `.github/workflows/b806-storefront-security-qa.yml` to syntax check and run the additional isolated B811 suite against PHP8.4/MariaDB10.11.19, preserving the historical security, RED and HTTP gates and artifact collection. Existing CI also started for exact workflow commit. No new workflow was added. This development evidence is not a CI PASS until the run and JUnit are inspected.

## Known unresolved gate
B810 exact-head final CI was 9/14 green with 48 newly failing historical guest-assumption test identities, while B809/B810 targeted was 12/160 PASS. A prior provider safety restriction blocked modifying legacy historical fixtures; this action was not repeated or routed around. The five legacy jobs may still fail on B811. They must be reconciled by an allowed, independently reviewed approach; no suppressing, skipping or deleting tests.

Pending: verify B811 new JUnit, rerun full CI and browser/role/race scenarios, actual SMTP deliverability, merchant approvals, OWASP inventory and remediation, restore/FPM/OPcache and owner buyer UAT before PR merge/deploy/storefront ON. No owner SSH action needed for source-only tests.

## Acceptance progress
Program 7/17 (41.2%), CMS 7/15 (46.7%), original UI 6/6 (100%). No new accepted production milestone from this source-only addition.
