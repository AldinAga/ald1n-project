# Report1007 — B1076 private receipt eligibility and GitHub-only QA
Date: 2026-11-04 Europe/Belgrade. SOURCE CANDIDATE ONLY / FULL SUITE HOLD.

## What changed and why
[Draft PR131](https://github.com/AldinAga/ald1n-project/pull/131) remains stacked on unmerged PR93, base acb8b9c42ec9254b1a27567abca29a26c1f733f8. Tested application head **13edc7567cbca299fa6cbf915f5d4caab85f637c**. GitHub main application source and production are unchanged; this report/index are documentation only.

PublicOrderAccessService::order previously trusted the account/role already loaded by authentication for email verification, activation and customer-role eligibility. Independently withdrawing those fields in the DB after authentication still allowed HTTP200 receipt access. The minimal correction reloads the current buyer before the existing identity/status/verification/role/tracked-session checks. The same service guards receipt HTML, order documents and warranty documents; no token, expiry, revocation, emergency OFF or buyer authorization check was removed.

Scope is exactly one service, six new cases in the already-added RegisteredStorefrontFlowB1075Test, and CI expected counts45→51 and609→615. No changes to historic fixtures, schema, stock/money logic, identifiers, dependencies or mobile. All runtime checks on GitHub; no local runtime/database was started.

## Evidence before and after
- Initial [run41740842006](https://github.com/AldinAga/ald1n-project/actions/runs/41740842006):51 cases/743 assertions,2 new-fixture errors,3 intended failures. Fixture errors were an invalid inactive enum (correct value blocked) and attempting approved storefront reopen while checkout config remained OFF. Only these NEW fixtures were corrected; not application behavior.
- Clean RED [run41741044276](https://github.com/AldinAga/ald1n-project/actions/runs/41741044276), head81c0886a6a49d088b74662056c9d69113c91c233: **51 tests /754 assertions /3 failures /0 errors**. The three are revoked email verification, revoked activation and changed customer role returning200 instead of404. Blocked/retired-account controls and real-login emergency OFF/revoke/reopen already pass.
- GREEN focused in [run41741259048](https://github.com/AldinAga/ald1n-project/actions/runs/41741259048): **51 tests /772 assertions /0 failures/errors/skips** after the two-line service correction.
- Same run full unfiltered original: **553 tests /4285 assertions /10 errors /71 failures /81 failing identities**.
- Same run full candidate: **615 tests /5342 assertions /9 errors /71 failures /80 failing identities**. **0 new failures,0 missing original cases,0 skips**. Independent comparison also confirms the exact same80 identities as B1075, not merely equal totals. The global run deliberately remains FAILURE/HOLD_HISTORICAL_FULL_SUITE_FAILURES.
- [Canonical CMS static gate41741259059](https://github.com/AldinAga/ald1n-project/actions/runs/41741259059): php bin/static-check.php **983 checks /0 unsuccessful**; workflow SUCCESS.
- [Real Chromium41741258934](https://github.com/AldinAga/ald1n-project/actions/runs/41741258934): B812 registered-buyer step PASS:signup/synthetic activation/login/retained cart/order/receipt/replay,1 order,1 stock decrement,0 payments/commissions,staff denied. The containing workflow remains FAILURE at unchanged legacy anonymous-success B799 checkout-link expectation. Synthetic activation delivery is not real SMTP evidence.

## Remaining exact findings and gates
The same80 IDs comprise40 HTTP302,9 HTTP403,3 HTTP404 mismatches (all expecting200),19 other assertion/source-contract failures and9 execution errors. This is symptom classification, not proof of52 auth defects. Original B805/B809/B815 bytes and Composer locks remain identical under CI. Do not rewrite these protected cases or permit guest buying to green the old suite. The unrelated presenter stop-failing is still not attributed to this change.

No production deployment, CloudLinux access/write, DB migration, storefront activation, real payment/mail, merge, EAS build, OTA or Play. Full historical-suite, merchant/legal, real buyer/concurrency UAT, production FPM/OPcache and owner release gates remain OPEN_NOT_WAIVED. No new Firefox/physical-device/real SMTP acceptance. Mobile checks not applicable to this CMS-only change. Accepted ledger unchanged: program11/17=64.7%,CMS10/15=66.7%,UI6/6;M14/M15 not owner-accepted. Account weekly Usage14%,owner ceiling20%.

## Evidence and continuation
[Permanent Drive archive](https://drive.google.com/file/d/1NR9hodG46J-2jk9ORfphayUpUSfnC6qW/view), SHA256 **2a66cc067e085e7fdc71d6da377ce3cb00f539935b2588142edc212410b10b6d**. Includes initial and clean RED, complete final JUnit/logs/exit codes/comparison, actual Chromium screenshots, exact a3edd52e→13edc756 source/test/CI patch and remaining80 identity classification. No .env, private passwords, vendor, database data or dumps.
Local evidence: D:\GPT\Ald1n-mobile-tools\storefront-qa\b1076.
Recovery source: C:\Users\ProDesk Mini\.codex\worktrees\storefront-recovery\Ald1n-mobile. Existing B1075 and B1076 source changes remain locally uncommitted and match the GitHub candidate; do not reset/clean or overwrite them. D:\GPT\Ald1n-mobile migration/handoff files remain preserved.

Next agent: read fresh main AGENTS/000-LATEST, this report, canonical Drive runbook and PR131 metadata; verify exact tested head and source patch hashes. Reuse the current recovery worktree and GitHub-only checks. Reproduce the remaining source-contract failures before choosing fixes; preserve current registered/active/verified/activated/tracked buyer identity, receipt isolation and zero-write denials. Historical tests that conflict with current product rules stay explicit HOLD, without silent waiver. No production or native action is authorized by these scoped PASS results.
