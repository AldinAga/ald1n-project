# Report817 — B807 real HTTP storefront security and compact QA

DATE=2026-10-24
KIND=ISOLATED_SECURITY_TESTS_ONLY
SOURCE_PR=https://github.com/AldinAga/ald1n-project/pull/93
SOURCE_HEAD=89e8f7cbfe5f0c610835187775acfb4d43ae9232
RUNTIME_APPLICATION_FILES_CHANGED=0
SECURITY_PHPUNIT=17_TESTS_205_ASSERTIONS_PASS
REAL_HTTP_CSRF_SESSION_METHOD_THROTTLE=20_CHECKS_PASS
EXACT_HEAD_CI=14_OF_14_SUCCESS
FULL_CMS_BASELINE=451_CASES_44_EXISTING_PROBLEMS
FULL_CMS_CANDIDATE=476_CASES_42_INHERITED_PROBLEMS
FULL_CMS_NEW_FAILURE_IDENTITIES=0
FULL_SUITE_GLOBALLY_GREEN=NO
COMPLETE_PENETRATION_TEST=NOT_COMPLETED
PRODUCTION_CHANGES=NONE
PUBLIC_STOREFRONT=OFF_AS_LAST_HOST_VERIFIED
OWNER_SSH_REQUIRED=NO
ACCEPTED_PROGRAM=7/17_41.2_PERCENT
ACCEPTED_CMS=7/15_46.7_PERCENT

## Verified incremental coverage

The B807 real-HTTP probe exercised the unmodified Laravel request stack through a PHP server bound only to 127.0.0.1 and an isolated MariaDB10.11.19 database. It verified missing/wrong CSRF tokens rejected with HTTP419 on cart, checkout and SuperAdmin merchant-policy writes; valid CSRF and real session cookies allow cart changes; unsafe GET does not change cart; anonymous merchant-policy writes are denied. Thirty nonexistent private receipt requests received404 and the31st received429 with Retry-After; private no-referrer, checkout/cart no-store and zero orders/payment/stock movements/checkout attempts were independently asserted. All20 checks PASS on the exact source, and the existing targeted17 PHPUnit/205 assertions also PASS.

Partial security coverage: WSTG-SESS-05 (selected real-HTTP CSRF endpoints), WSTG-INPV-03 (selected unsafe verbs), WSTG-ATHZ-02 (anonymous merchant settings), WSTG-BUSL-05 (bounded capability rate limiting), WSTG-ATHN-06 (selected private caching). These are PARTIAL, not a full WSTG or ASVS item-level completion statement. Wider role matrices, auth/session lifecycle, authenticated CSRF, payment-state abuse, injection, media, SAST/SCA/secret/DAST and approved TLS/proxy review remain open under the mandatory security plan.

## Grouped verification, no CI inflation

An initial stand-alone B807 workflow proved20/20 real-HTTP checks. Its duplicate workflow was then removed and these probes were integrated into the existing B806 security job. Final exact-head CI remains14 workflows rather than15. No runtime classes, DB migrations, business-state calculations, checkout money paths, hosted source or feature toggles changed in B807.

B807 combined security run40093845177 and artifact12559514987 SHA256 5d371868635c32acf112d87ab449628b6476a040449dd65b654a5d443dc6d368. Full-suite comparison run40093845128 artifact12559619744 SHA256 74c82a280de58ecb288e186b7d5214f0fe92428f045cf9f6c02bfeabb325cd9a. Source SHA, full inherited failure identities and grouped synthetic proof are preserved in immutable CI artifacts.

Drive archive (grouped source + test evidence): https://drive.google.com/file/d/1aOJe-iOXUAv1Jhx_oVcMjt6fMGabLw1_/view
Drive archive (complete regression evidence): https://drive.google.com/file/d/140UukCbb6HeBFLeUSNsata-XK0_OjziB/view
Both uploads succeeded; grouped ZIP was independently fetched back and matched GitHub artifact digest. Existing operational rule now requires short grouped tool outputs and retains full evidence without repeating already accepted work.

## Deployment and next gate

Production stays B798 / owner Reports804-805, source c4a5f060cc00b5d1be0e4d8068309524a4b99c99. B804 owner Report815 is already accepted; do not rerun. B805 old rollout candidate is invalidated by B806 runtime hashes. PR93 remains DRAFT and NOT MERGED/DEPLOYED. Final inventory and broad applicable WSTG4.2/ASVS5.0.0 Level2 coverage, findings/remediation/retest, merchant policies, exact-source safe OFF rollout, restore drill, FPM and end-user acceptance remain gated. No SSH, production attack, real customer requests/mail, Android/EAS/OTA/Play, cleanup or inode deletion was performed.

The functional acceptance fractions remain7/17=41.2% overall and7/15=46.7% CMS; these count accepted functional milestones, not batch numbers or percent of historical code. Final inode cleanup remains issue95 after releases, no deletion now.
