# B1075 — storefront source recovery and real GitHub QA
Date: 2026-11-04 Europe/Belgrade. SOURCE ONLY / FULL SUITE HOLD.

## Source and ownership
Draft [PR131](https://github.com/AldinAga/ald1n-project/pull/131) is stacked on Draft PR93 branch feat/b799-public-retail-catalog-cart-checkout, exact base acb8b9c42ec9254b1a27567abca29a26c1f733f8. Tested final application head: 5a96cbc1e9f34aecfda1dcb7ce099f258c3675ab. Neither PR is merged into main or deployed. PR111 remains M11 warranty lineage, not the canonical original checkout candidate.

Reused B1071/B1073 replay/QA corrections and selected already reviewed source for private/no-store dynamic media, symlink confinement, root-relative URLs/redirects, HTML validation, tracked buyer-session rotation and public script-free CSP. Added three genuine registered-buyer HTTP purchase/replay/tampering scenarios. Reused the reviewed B876 stable role-ID seeder and its three cases only after actual full-suite overflow evidence. No schema migration, existing ID reassignment or protected fixture edits.

## Actual RED and GREEN
- RED [run41737500765](https://github.com/AldinAga/ald1n-project/actions/runs/41737500765), head fbb6f2e530aa3ce4373dab7146877326ac1bc501: 42 tests /293 assertions /37 failures /0 errors.
- First correction [run41738118021](https://github.com/AldinAga/ald1n-project/actions/runs/41738118021), head39c98a6fc6b9860c94f654204ea55881ce0a0078: focused42/638 PASS; full606/108 failing identities. All28 new identities independently contained the same MariaDB roles.id AUTO_INCREMENT overflow. They were not waived.
- Final [run41739028346](https://github.com/AldinAga/ald1n-project/actions/runs/41739028346), PHP8.4 + two independent disposable MariaDB10.11.19 instances, each exact ald1n_retirement_ci: focused **45 tests /651 assertions /0 errors/failures/skips**.
- Original unfiltered full **553 tests /4285 assertions /10 errors /71 failures /81 failed identities**.
- Final unfiltered full **609 tests /5221 assertions /9 errors /71 failures /80 failed identities**. **0 newly failing identities; 0 missing original cases; 0 skipped cases**. Global workflow deliberately FAILURE/HOLD_HISTORICAL_FULL_SUITE_FAILURES. One unrelated OrderDetailPresenter identity ceased failing as in B1074; no causal fix is attributed.
- B805/B809/B815 test files and Composer manifests/lock verified byte-identical by CI. No filtering, deleted tests or security policy relaxation.

## Real browser evidence
[run41739028332](https://github.com/AldinAga/ald1n-project/actions/runs/41739028332) at the same final application head: B812 registered Chromium step PASS. Real localhost signup, synthetic activation-token delivery substitute, activation/login, retained guest cart, checkout/receipt/replay; DB verifies one order, stock decremented once, zero payments/commissions, staff denied. Catalog/registration visual checks at1440 and390. This does NOT prove real SMTP delivery, physical devices, Firefox or production FPM/OPcache.
The containing legacy workflow remains FAILURE because its later anonymous-success B799 scenario expects a checkout link for guests. B806 retains one pre-existing B809 403-vs200 identity. These are preserved open findings, not converted to PASS.

## Durable local evidence and continuation
Root: D:\GPT\Ald1n-mobile-tools\storefront-qa
- b1075-red-github.zip SHA256 bede9b163f72f2c5811e9de92d4b9f50892aca7f87a14e037d713b14e86cb667
- b1075-review-github.zip SHA256 8292d74e1ef503819128581f12fbea41107b47b1c509bf8e02dc926c3e2e8aa2
- b1075-final-mariadb.zip SHA256 abae2e2c199faf188c529859caaea693ae77e178934ab4092a8cdd66b37ac580
- b1075-latest-registered-chromium.zip SHA256 90a56e2eaac754ae804110b67b2b826821de37734e6af0ccde11e0b30571e922
- github-final-5a96cbc: complete JUnit, logs, original/candidate exit codes, comparison.json.
- B1075-PR93-source-candidate.patch: exact pinned-base-to-tested-head source/CI patch.
Recovery checkout: C:\Users\ProDesk Mini\.codex\worktrees\storefront-recovery\Ald1n-mobile. Its changes mirror remote source but remain locally uncommitted; do not discard them. Original D:\GPT\Ald1n-mobile retains its migration documents and separate candidate work; no destructive synchronization was used.

Next agent: fetch fresh origin/main, PR93 and PR131; read AGENTS,000-LATEST and canonical Drive runbook. Verify final pinned source and evidence hashes. Use GitHub CI for runtime checks per owner; do not restart local PHP/MariaDB/Docker. Investigate the80 remaining exact JUnit identities by actual source/policy contract, beginning with storefront receipt/buyer/status cases. Preserve original fixtures and registered, active, verified, activated, tracked buyer policy. Do not weaken auth or alter B805/B809/B815 to green CI. Reuse existing code and tests; one consolidated source correction per reproduced cause. No automatic merge/deployment.

## Boundaries
All Windows runtime tests stopped at owner request; unfinished full runs are INTERRUPTED, not evidence. Both exact owned QA database servers13321/13322 shut down cleanly. SQLite failed existing MySQL-specific migration bootstrap and is not a functional test result. Local private password files, .env, DB directories/dumps and vendor are excluded from uploaded evidence.

No CloudLinux/prod DB access or write, migration, storefront activation, real payment/mail, source merge, EAS build, OTA or Play action. Merchant/legal, concurrency/real buyer UAT, physical/native, full historical suite and owner release gates remain OPEN_NOT_WAIVED. Formal accepted ledger unchanged: program11/17=64.7%, CMS10/15=66.7%, first UI6/6; M14/M15 unaccepted. Latest account weekly usage13%; owner ceiling20%.
