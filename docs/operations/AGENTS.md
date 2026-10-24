# Operations rules supplement - owner decision 2026-10-24

Read and preserve the repository-root AGENTS.md. This supplement adds the owner's explicit progress and cleanup requirements; it does not waive production, security, backup or source-verification gates.

## Mandatory percentage in every final Ald1n status

Show the current accepted milestone numerator/denominator and percentage in every final status, batch handoff and report. Reconstruct fresh GitHub and host evidence first. Distinguish source/CI, host installation and actual acceptance. Do not award acceptance credit merely for commits or passing scoped CI.

Current verified ledger: program 7/17 = 41.2%; CMS 7/15 = 46.7%; original UI 6/6 = 100%. This measures accepted scope milestones, not all historical code or estimated effort. The new final cleanup is tracked separately; do not silently change the existing denominator.

## Mandatory large inode cleanup AFTER all current changes

Persistent open reminder: https://github.com/AldinAga/ald1n-project/issues/95

After all currently approved changes and their controlled release/acceptance gates finish, execute a dedicated large cleanup of ald1n-project. The owner's estimate of more than 400,000 files/inodes is not a measured fact. Inventory scoped counts and top consumers first, without following CloudLinux symlinks or confusing account-wide inodes with the project.

Before deleting anything: explicit path allowlist and dry run; distinguish active runtime/dependencies/customer data from abandoned build/cache/isolate/archive artifacts; archive required sources/scripts/reports to the existing owner Google Drive folders and verify hashes/readback. Protect .env, credentials/signing keys, .git, active vendor/node_modules and symlink targets, storage/uploads, business data, the active release and its rollback window. Retain the two independently verified stable backups. No broad rm, git clean/reset, blind dependency deletion or automatic backup prune.

Use one tested integrity-self-checking canonical incoming/ SSH batch and sequential docs/operations report. Prove before/after file/inode/byte counts and reduction percentage, plus post-cleanup CMS/cron/build checks. Do not claim saved inodes without actual host evidence. No cleanup occurs as a side effect of feature QA.

## Recurring blocker prevention

Report813 is HOLD_PR_HEAD_MOVED / RC2, not a deployment or completed backup gate. Never rerun its stale B803 patch. Read the actual current PR head immediately before any source mutation or SSH handoff. Use working connected GitHub/Drive write actions for source/document work; do not require host SSH just to publish GitHub code.

Public Chromium concurrency QA must prove independent request nonces, different SQL connection IDs in consecutive snapshots, and unchanged exact stock/HTTP/idempotency outcomes. A second request may wait at checkout-attempt insertion instead of the product lock; do not mistake that different SQL stage for absence of simultaneous requests. Never remove the final one-sale, no-oversell or same-receipt assertions.

The canonical Drive runbook now also contains these owner rules. Test PR94 remains separate from application PR93. Source-only QA is not permission to merge, deploy, activate retail, publish OTA or run a native build.

## Mandatory storefront penetration testing and remediation - owner decision 2026-10-24

Follow [the dedicated security plan](../security/STOREFRONT-PENETRATION-TEST-PLAN-20261024.md). Before public ON, enumerate every applicable test in pinned OWASP WSTG4.2 and ASVS5.0.0 Level2, plus actual application business/role/payment/capability risks and current relevant advisories. Track applicability, exact source, outcome, evidence and limitations per case. All confirmed findings must be fixed, patched and retested with regression coverage. Never equate an initial targeted suite or an automated scanner's clean exit with complete penetration testing or proof against every possible attack.

Use disposable isolated CI/staging with synthetic data. No active production scans, third-party probes, load/DoS on shared hosting, real orders/payments/customer messages or private-data extraction. Infrastructure testing needs its own authorized target. NOT_RUN/BLOCKED/NOT_APPLICABLE are not PASS. Critical/high findings block release; unresolved lower findings stay visible and must not be omitted from the owner's completion criteria. Do not suppress findings to make a build green. Retain red/green test evidence and honest review independence.

## Clarified progress display - acceptance is not batch throughput

The original Report796 ledger is17 functional acceptance milestones: M01-M15 CMS, M16 signed Android16KB release, M17 SDK58. Closed7 means backend activation M01 and six owner-accepted UI requirements M02-M07. Hence7/17=41.2% and7/15=46.7%. This is NOT the development percentage of the entire historical application and a completed SSH batch does not automatically close one functional milestone.

Every final response must also name what actually advanced in the current batch, source/CI status, deployed status and the precise remaining acceptance gate. Keep the numerator honest but do not hide real progress behind the unchanged41.2%. Use no invented weighted completion percentage. Security and cleanup are required gates/additional tasks, not a silent denominator change.

Owner Report815 closes B804 prerequisites:44 affected paths and backups166/165 logically verified, RC0, no production writes, no restore drill/FPM/customer UAT. Do not rerun B804. B805 local rollout preparation remains incomplete and unshipped; any new application security fix invalidates its old exact-target manifest/payload and requires regeneration. A connector action appearing/disappearing is not proof user permissions changed. A provider safety denial is not a read-only connector and must never be bypassed, repackaged or rerouted.
