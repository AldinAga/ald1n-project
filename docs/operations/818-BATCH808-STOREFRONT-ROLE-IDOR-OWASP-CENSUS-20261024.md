# Report818 — Batch808 storefront role/IDOR security and complete control inventory

DATE=2026-10-24
SOURCE_PR=https://github.com/AldinAga/ald1n-project/pull/93
EXACT_SHA=fa05307efafc067bf13437fe0851543f6e594af8
SOURCE_QA=PASS PROD_DEPLOY=NO STOREFRONT_ON=NO
RED=3_EXPECTED_ROLE_FAILURES GREEN=24_TESTS_324_ASSERTIONS
REAL_HTTP=20_OF_20_PASS EXACT_HEAD_CI=14_OF_14_SUCCESS
FULL_BASELINE=451_CASES_44_PROBLEMS
FULL_CANDIDATE=483_CASES_42_INHERITED_PROBLEMS
FULL_NEW_FAILURES=0 FULL_GLOBALLY_GREEN=NO
OWASP_REGISTER=115_WSTG42+253_ASVS500_CUMULATIVE_L2=368_ALL_REVIEW_REQUIRED_NOT_RUN
ACCEPTED_PROGRAM=7/17_41.2_PERCENT CMS=7/15_46.7_PERCENT
OWNER_SSH=NOT_REQUIRED PRODUCTION_SOURCE_DB_SETTINGS_CHANGES=0

B808 found and patched authenticated operator identity laundering into a guest public checkout. Changed only PublicCheckoutController and PublicRetailCheckoutService, plus isolated test and existing grouped CI. Preserved true Kupac/anonymous checkout, OFF 404, internal ordering, financial ledger, payment/delivery and stock invariants. Red proof restored exact predecessor controller/service in disposable MariaDB; three expected baseline test failures, then final candidate 24 PHPUnit/324 assertions plus HTTP20/20 PASS. Cross-order issued and cancelled private invoice access denied. MariaDB fixture adjustments used real mandatory financial snapshots and status=cancelled, not altered production schema. Fourteen candidate workflows green; full suite baseline451/44 vs candidate483/42 old problems (zero new), not globally green.

Control inventory https://github.com/AldinAga/ald1n-project/blob/main/docs/security/STOREFRONT-WSTG42-ASVS500-L2-CENSUS-B808.csv contains every listed WSTG4.2 scenario (115) and cumulative ASVS5.0.0 L1+L2 requirement (253), all initially REVIEW_REQUIRED/NOT_RUN; previously passing targeted checks do not close entire OWASP controls. Native archival copy: https://docs.google.com/document/d/1Yrh5NOf0UekBJFk4cPDcYluwGmGPC6jEmpea4bK-OH4/edit

Finding: https://github.com/AldinAga/ald1n-project/blob/main/docs/security/STOREFRONT-SECURITY-FINDING-B808-20261024.md
Security QA artifact12560518420 SHA256 46ab52a7acf182147dc33848c619769c7cabc5d6678b7d6c5b7c735b57334959 archived: https://drive.google.com/file/d/1lut7KyzvjNuSfcMEYntRp1YLtJIwm_lr/view
Full suite artifact12561250748 SHA256 3b3eda0cc4dff932b7130ea92f152e6ab15c763bb4a25ec9352f400f6ee38894 archived: https://drive.google.com/file/d/1qvF35W_CHtfZtKpOPWT6QLP7upve21fw/view
Both Drive ZIPs independently fetched and same byte count verified.

Host remains original B798 source c4a5f060cc00b5d1be0e4d8068309524a4b99c99 and OFF as last attested; original Report815 B804 host prerequisites already PASS, never replay. Previous B805 local rollout stale. No production attack, real mail/purchase, DB migration, SSH, OTA/build/Play, cleanup or inode deletions. PR93 remains DRAFT. Complete OWASP applicability review, all scoped pentests/patch+retest, optional independent review, merchant policy, restore drill, PHP-FPM/OPcache and actual user acceptance still block release. Next B809: app-specific remaining role/warranty/IDOR/input scans and classify the pinned controls. Inode final cleanup issue95 deferred.

This batch advances security evidence for M13-M15 but does not close a functional acceptance milestone. Program7/17=41.2%, CMS7/15=46.7%, earlier UI6/6=100%.
