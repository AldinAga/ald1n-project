# Progress ledger explained - 2026-10-24

Authority: Report796 scope ledger v1, later source/production reports, original owner Report815 and current PR93. No denominator or closure credit has been silently changed.

## What7/17 and7/15 mean

A milestone is an accepted functional outcome, not a batch, commit, script or test. Each of the17 milestones has equal weight and closes only when its applicable implementation/test/deployment/acceptance requirements are supported by evidence. The CMS subset contains M01-M15. M16-M17 are two mobile release outcomes.

7 CLOSED /17 total =41.2% of this dated acceptance scope.
7 CLOSED /15 CMS =46.7% of the CMS acceptance subset.
6 CLOSED /6 original UI checks =100% of the original UI acceptance.

The seven are M01 backend activation and M02-M07 the six earlier UI checks. This does NOT mean that only41.2% of the whole historical Ald1n application is written, nor that58.8% of engineering work/time remains. There is no evidence-based historical effort denominator for such a claim.

## The explicit17 milestones

| ID | Functional milestone | Accepted closure | Evidence / actual stage |
| --- | --- | --- | --- |
| M01 | Laravel CloudLinux backend activation | CLOSED | Report791/793 |
| M02 | Whole-document horizontal overflow correction | CLOSED | Report795 check1 |
| M03 | Desktop period and priorities row | CLOSED | Report795 check2 |
| M04 | Product-first recent-order labels | CLOSED | Report795 check3 |
| M05 | Readable Liquid Glass action/module styling | CLOSED | Report795 check4 |
| M06 | Product specification insets and gallery/summary height | CLOSED | Report795 check5 |
| M07 | Grouped user editor and separate retirement zone | CLOSED | Report795 check6 |
| M08 | SuperAdmin operational order context, no personal purchase flow | OPEN acceptance | B796 source/host evidence exists; final acceptance not closed |
| M09 | Reusable readable Liquid Glass animation | OPEN acceptance | B796 source/host evidence exists; static UI acceptance is not animation acceptance |
| M10 | Idempotent warranty issuance on verified incoming payment, including direct sales | OPEN acceptance | B798 source/host evidence exists; later COD correction in undeployed PR93 |
| M11 | Per-recipient opt-in expiry for original seller/SuperAdmin | OPEN acceptance | B798 source/host evidence exists; independent final acceptance pending |
| M12 | Shipment number/safe tracking in first buyer panel | OPEN acceptance | B796 source/host evidence exists; final acceptance not closed |
| M13 | Public catalog/product pages | OPEN release/acceptance | Implemented in PR93; new mandatory security work and host release remain |
| M14 | Guest/Kupac cart and full-retail checkout | OPEN release/acceptance | Implemented in PR93 with3payment modes/role/switch; security and host release remain |
| M15 | Secure private order follow-up/tracking/documents | OPEN release/acceptance | Implemented in PR93; new security corrections require tested exact-source rollout |
| M16 | Signed Android16KB-compatible artifact and release acceptance | OPEN | Separate actual signed-binary/release gate not closed |
| M17 | Actual SDK58 upgrade and release acceptance | OPEN | Deferred until current CMS and Android gates close |

## Why successful recent batches did not increment7

- B802/B803 delivered source/test changes: SubAgent/Kupac boundaries, ON/OFF and payment choices. They contribute to M13-M15; they do not automatically close three independent production milestones.
- B804 corrected QA and supplied source/backup prerequisites. Owner Report815 proves44 affected paths checked, two existing full backups166/165 logically verified and RC0. It explicitly says PRODUCTION_DEPLOYED=NO and RESTORE_DRILL=NOT_RUN. This prerequisite is complete and must not be repeated as unfinished work.
- B805 verified a local44-file rollback mechanism and prepared a721-path manifest, but remained incomplete, unshipped and undeployed. A local rehearsal is not a production source rollout or a production restore drill.
- B806 begins the newly required comprehensive security work, with targeted negative tests and source fixes. A security source correction changes the target and requires regeneration of any old release payload. An initial security suite is not a completed full penetration test.

Therefore the acceptance numerator honestly remains7 even while batch work advances. Showing only this number concealed useful progress. Every subsequent final status must also report the exact work completed in that batch, source/CI state, host deployment state and the gate preventing the next functional closure. Do not substitute a made-up development percentage or add points merely because a script ran.

## Added tasks and progress discipline

Mandatory full applicable storefront penetration testing, patch/retest and final inode cleanup issue95 are tracked explicitly. Security is a release gate for M13-M15. Cleanup is a separately tracked required final task after approved changes and acceptance. Do not silently turn17 into18/19 or award free completion credit. A changed overall scope ledger must be versioned and explained.

For security, report actual checked cases and remaining coverage, not a global coverage percentage before the complete applicable test inventory is established. Keep NOT_RUN, BLOCKED and NOT_APPLICABLE separate from PASS. All confirmed findings must be patched/retested before the entire security task is called complete.

Production installation can precede public opening only through a separately verified controlled OFF rollout. Merchant policy, source/backup/restoration, PHP-FPM/OPcache and acceptance gates stay explicit. No production command or public ON is authorized by this document.
