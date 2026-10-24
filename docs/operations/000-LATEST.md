# CURRENT B807 — grouped real-HTTP security PASS; full pentest and rollout OPEN

Updated:2026-10-24. Authority: root AGENTS.md, operations [AGENTS supplement](AGENTS.md), [Report817](817-BATCH807-REAL-HTTP-SECURITY-GROUPED-QA-20261024.md), [penetration plan](../security/STOREFRONT-PENETRATION-TEST-PLAN-20261024.md) and original Drive runbook.

## Current source and security evidence

- [PR93](https://github.com/AldinAga/ald1n-project/pull/93) remains DRAFT, NOT MERGED OR DEPLOYED, exact head `89e8f7cbfe5f0c610835187775acfb4d43ae9232`. B807 adds test-only PHP helpers and groups their real HTTP checks into B806 CI; application runtime files changed=0.
- B807 localhost MariaDB/PHP HTTP **20/20 PASS**: missing/bad CSRF returns419; valid session/cart persists; anonymous SuperAdmin settings cannot be changed; unsafe GET cannot mutate cart; 30 nonexistent private capability requests404, next429 with Retry-After; private referrer and no-store checks; zero synthetic sales/payment/stock writes.
- Existing targeted security17 PHPUnit tests/205 assertions PASS. **All14 exact-head CI workflows SUCCESS.** Full CMS comparison451 baseline cases/44 problems against476 candidate cases/42 inherited problems, **zero new failure identities**. The whole legacy suite is **NOT globally green**.
- [Report817](817-BATCH807-REAL-HTTP-SECURITY-GROUPED-QA-20261024.md) contains direct immutable job/artifact IDs and Drive archive links. Full security inventory, scanner review, broader role/IDOR and infrastructure gates remain OPEN. No claim of independent penetration certification.
- New owner rule: group routine tests in existing coherent jobs and summarize tool outputs; preserve every negative test, complete failure evidence and strict release checks. See operations AGENTS supplement. Avoid duplicating green checks or inflating CI job count.

## Host and acceptance

- Production remains owner-certified B798, source `c4a5f060cc00b5d1be0e4d8068309524a4b99c99`; storefront OFF as last independently host-attested. Original owner Report815 already PASS for B804 source44-path and two logical backups166/165. No restore drill, FPM/OPcache provenance or customer UAT yet; do NOT repeat earlier host work.
- Original B805 local OFF-release package is stale after B806 application corrections. Only regenerate exact-source release materials after outstanding security and business gates. No production source/DB/settings mutation, SSH script, build/OTA/Play or inode deletion in B807.
- **Acceptance progress: program7/17=41.2%; CMS7/15=46.7%; earlier UI6/6=100%.** These are accepted milestones M01-M07, not completed batch counts or estimated total engineering effort. B807 security work adds genuine evidence toward M13-M15 but not production acceptance.
- Next: continue WSTG4.2/ASVS5.0.0 Level2 applicability census and selected role/business penetration tests in synthetic isolation, remediate and retest defects, then controlled OFF rollout and actual host/UAT acceptance. Final giant inode cleanup remains mandatory separately under issue95, not now.

Previous index archived byte-identically as [archive/000-LATEST-PRE-B807-20261024.md](archive/000-LATEST-PRE-B807-20261024.md).
