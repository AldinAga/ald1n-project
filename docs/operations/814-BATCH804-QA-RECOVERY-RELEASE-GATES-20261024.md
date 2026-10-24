# Report814 - B804 QA recovery, permanent cleanup/progress rules and exact-source backup prerequisites

DATE=2026-10-24
KIND=SOURCE_QA_AND_OPERATOR_PREPARATION_NOT_PRODUCTION_DEPLOYMENT
SOURCE_PR=https://github.com/AldinAga/ald1n-project/pull/93
SOURCE_BRANCH=feat/b799-public-retail-catalog-cart-checkout
SOURCE_COMMIT=d4e9e2ba506e82d2f4b5ec639c6ce6b39748f34d
SOURCE_TREE=67e51b9112c93414277c5ca8eb5a8e57045318bc
SOURCE_PREDECESSOR=08b85fc85303e3649a340f159d07ee5c8df795ff
EXACT_HEAD_WORKFLOWS=13/13_COMPLETED_SUCCESS
PAYMENT_AND_ROLE_TESTS=20_PASS_211_ASSERTIONS_ZERO_ERRORS_FAILURES_SKIPS
CONCURRENCY_OBSERVER_LOCAL_FIXTURES=12_PASS
OPERATOR_PATH_INTEGRITY_LOCAL_FIXTURES=10_PASS
FULL_SUITE_BASELINE=451_CASES_44_PROBLEMS
FULL_SUITE_CANDIDATE=459_CASES_42_INHERITED_PROBLEMS
FULL_SUITE_NEW_FAILURE_IDENTITIES=0
FULL_SUITE_GLOBALLY_GREEN=NO
APP_RUNTIME_CHANGES_THIS_BATCH=NONE
PRODUCTION_SOURCE_DB_SETTINGS_WRITES=NONE
PRODUCTION_DEPLOYED=NO
PROGRAM_ACCEPTED=7/17_41.2_PERCENT
CMS_ACCEPTED=7/15_46.7_PERCENT
ORIGINAL_UI_ACCEPTED=6/6_100_PERCENT
FINAL_INODE_CLEANUP=REQUIRED_AFTER_CURRENT_CHANGES_ISSUE95
INODE_COUNT_OVER_400000=OWNER_ESTIMATE_NOT_MEASURED

## Current evidence and correction

The current B803 payment implementation was already published at 08b85fc; the old operator patch expected ddf6b8ff and safely stopped in owner Report813. Do not replay it. Original Report813 SHA256 b8bfa32f69c0a9f3cced662b246f042af3e93d32e9ac799519f0d76c05d2a038 is archived unchanged. No new host backup validation occurred in that stopped batch.

Live CI reconstruction found a failed public Chromium workflow40083073647/job120840421730: the last-unit SQL barrier observed one product-lock query and one checkout-attempt insertion but required two product queries. This was a test-observation failure, not proof of a successful production purchase or oversell. A local regression reproduced rejection of this simultaneous mixed-stage snapshot (11 cases, one expected failure). The observer now requires two distinct connection IDs and two consecutive stable snapshots; the browser additionally asserts different buyer nonces and identities. Same-nonce replay still specifically requires a product and an attempt-insert stage. Final assertions were not weakened: competing buyers must yield302+422, duplicate submissions302+302 with identical capability, and independent DB verification proves one sale per race, correct stock, zero payments/commissions.

Exact candidate public Chromium workflow40085394641 PASSED. All13 exact-head workflows completed successfully, including full CMS comparison40085394618 and payment/access40085394538. Downloaded JUnit artifact12557061572 (SHA256 ef323187b54f8a930dff7afdfb39ce3d581af911c25a745fe6cabb89c8b72b0a) proves20tests/211assertions. Full comparison artifact12556384893 (SHA2562a90354b8772abce011046bcdc0b38884d3a01201f7a0ffed3573b20ea2768ae) proves451baseline cases/44problems versus459candidate cases/42problems, zero NEW failure identities. This is NOT a fully green legacy suite. The exact inherited failure names and raw synthetic JUnit/logs are retained in the archived B804 evidence package. Author self-review plus automated CI is not independent review.

Public payment choices remain exactly Pouzece, Uplata na racun, and Placanje pri licnom preuzimanju/dostavi. Internal SubAgent/direct-sale deferred credit is unchanged. In-person payment remains unpaid until real operator-recorded collection and cannot use courier delivery. No new application runtime code was modified by B804; only deterministic QA instrumentation/workflow was corrected.

## Owner's permanent rules recorded now

Issue95 records the mandatory large inode cleanup after all current changes and acceptance. The canonical Google Drive ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md was updated in place under its original ID10Lxe_reZ1csYOMK9ESjZ8NUmEglDpOMy and read back; all prior runbook bytes are retained after the new owner rules. The operations AGENTS supplement contains the same requirements. Every final Ald1n status must show accepted fraction/percentage. Current7/17=41.2%, CMS7/15=46.7%; cleanup is separately tracked, not silently added to the denominator. Over400,000 inodes is an unverified owner estimate. No inode deletion or backup pruning was performed.

## Next genuine host action - not source publication or deployment

Prepared tools/operator-source/ald1n-b804-release-readiness-v1.sh with companion PHP and runtime manifest. Install only in canonical incoming/ via one SHA-checked bootstrap. The shell verifies itself, immutable helper hashes, exact current PR93 head and successful checks, exact original Report813, protected host HEAD/index/.htaccess/.env. It then checks44 affected runtime paths against the certified B798 predecessor and invokes the unchanged first-party read-only backup verifier for two distinct completed full backups. Candidate/preexisting target paths are HOLD for reconciliation, not silently overwritten. No git checkout/pull/reset/clean, clone, dependency install, migration, backup create/prune, maintenance, application settings write, public toggle, EAS/OTA/Play or customer mail.

Script SHA256=be560e0490bd6336d91084e2c3c5c77e9de9781b8c79d3c1875c35c3a8a16597
PHP SHA256=0b7ae942705806b1610f46201265bc71e5d324764e9f145e6dbc892bdcb03cb2
Manifest SHA256=2d25f76fcf59235218458293aedc127dbadf8212e21fb45a5d2fdeaf95726108

Local bash-n, PHP lint, forbidden CloudLinux syntax scan and10 filesystem/age fixtures PASS. The manifest was independently matched to the final candidate CI artifact:44 paths,13 existing modifications and31 additions. This is affected-path preparation, NOT full-runtime or FPM provenance. Logical backup verification and a stated preparation age window (newest<=24h, second<=48h) are separately reported. No restore drill is performed and no production readiness is inferred before actual host evidence. Reports are allocated as max(current GitHub main, local host)+1, expected815 only if no later report exists. All terminal output is live and recorded in docs/operations.

After the actual report: resolve any named gate, then prepare separately controlled full-source rollout with storefront OFF. Public opening remains a distinct SuperAdmin decision after true merchant policies, financial/delivery rules, fresh backup/restoration gates and host/customer acceptance. No extra scope or destructive cleanup should be introduced during this preparation.

The previous 000-LATEST content is preserved byte-for-byte in archive/000-LATEST-PRE-B804-20261024.md before any index refresh. Do not claim root AGENTS.md was rewritten: the new policy is in the operations supplement, persistent issue and canonical runbook.
