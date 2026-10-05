
============================================================
PHASE authority-reconstruction
============================================================
+ git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
LOCAL_HEAD=40005b9819be70320046ec091a62c08657ab5e9b
REMOTE_HEAD=40005b9819be70320046ec091a62c08657ab5e9b
EXPECTED_DOCS_HEAD=40005b9819be70320046ec091a62c08657ab5e9b
APP_SOURCE_AUTH=318c0cb9f7225f2f2139f72debbb5723872e6316

============================================================
PHASE recovery-binding
============================================================
RECOVERS_REPORT=docs/operations/519-BATCH517-CATALOG-IMAGE-AVAILABILITY-20261005-204659.md
RECOVERS_REPORT_SHA256_EXPECTED=c143b34f4a0404cc726a63830a3469b4f9406c4012d324f1618215e88cf76896
RECOVERS_REPORT_SHA256_ACTUAL=c143b34f4a0404cc726a63830a3469b4f9406c4012d324f1618215e88cf76896
RECOVERY_BINDING=PASS_PRE_MUTATION_FAIL

============================================================
PHASE worktree-preflight
============================================================
HTACCESS_FILE_SHA256=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA256=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
WORKTREE_PREFLIGHT=PASS_KNOWN_RUNTIME_AND_BOUND_RESIDUE

============================================================
PHASE dependency-free-contract-red
============================================================
No syntax errors detected in /home/icaffeco/.ald1n-batch517-20261005-212417/batch517-contract.php
FAIL Product exposes presentationImage fallback relation
FAIL Catalog list eager-loads presentation image
FAIL API detail loads presentation image
FAIL ProductResource primary image fields use presentation image
FAIL Controlled legacy publication service exists
FAIL Catalog image availability doctor supports dry-run/apply
PASS Legacy media route remains inside authenticated Web group
PASS Mobile catalog keeps thumbnail display original fallback
PASS Mobile detail gallery keeps rendition fallback
PASS Product Variants remain decommissioned
BATCH517_CONTRACT_FAIL_COUNT=6
TDD_RED=PASS_EXPECTED_FAILURE

============================================================
PHASE source-patch
============================================================
+ /home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node --check /home/icaffeco/.ald1n-batch517-20261005-212417/patcher.mjs
+ /home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch517-20261005-212417/patcher.mjs /home/icaffeco/ald1n-project

============================================================
PHASE source-scope-and-syntax
============================================================
EXPECTED_SCOPE:
apps/cms/current/app/Console/Commands/CatalogImageAvailabilityDoctorCommand.php
apps/cms/current/app/Http/Controllers/Api/V1/CatalogController.php
apps/cms/current/app/Http/Resources/ProductResource.php
apps/cms/current/app/Models/Product.php
apps/cms/current/app/Services/CatalogQueryService.php
apps/cms/current/app/Services/ProductImagePublicationService.php
apps/cms/current/bin/batch517-catalog-image-availability-contract.php
apps/cms/current/bin/static-check.php
apps/mobile/current/scripts/validate-project.mjs
ACTUAL_SCOPE:
apps/cms/current/app/Console/Commands/CatalogImageAvailabilityDoctorCommand.php
apps/cms/current/app/Http/Controllers/Api/V1/CatalogController.php
apps/cms/current/app/Http/Resources/ProductResource.php
apps/cms/current/app/Models/Product.php
apps/cms/current/app/Services/CatalogQueryService.php
apps/cms/current/app/Services/ProductImagePublicationService.php
apps/cms/current/bin/batch517-catalog-image-availability-contract.php
apps/cms/current/bin/static-check.php
apps/cms/current/public/.htaccess
apps/mobile/current/scripts/validate-project.mjs
docs/operations/482-BATCH180-LARAVEL-LOGIN-GLASSMORPHISM-POLISH-FAILED-20260925-101030.md
docs/operations/482-BATCH180-LARAVEL-LOGIN-GLASSMORPHISM-POLISH-V2-20260925-101900.md
docs/operations/483-BATCH180-LARAVEL-LOGIN-GLASSMORPHISM-FINAL-20260925-104641.md
docs/operations/484-BUILD19-EAS-PRODUCTION-ONE-PASS-20260925-110833.md
docs/operations/485-BUILD20-STORAGE-UNITS-DIRECT-SALE-CURRENCY-20260926-111857.md
docs/operations/486-BUILD20-STORAGE-UNITS-DIRECT-SALE-CURRENCY-RECOVERY-20260926-113520.md
docs/operations/487-BUILD20-STORAGE-UNITS-DIRECT-SALE-CURRENCY-RUNTIME-LOG-RECOVERY-20260926-114147.md
docs/operations/488-BUILD20-STORAGE-UNITS-DIRECT-SALE-CURRENCY-PHP-NEWLINE-RECOVERY-20260926-123853.md
docs/operations/489-BUILD20-STORAGE-UNITS-DIRECT-SALE-CURRENCY-FINAL-RECOVERY-20260926-125002.md
docs/operations/490-BUILD20-STORAGE-UNITS-DIRECT-SALE-CURRENCY-VALIDATOR-RECOVERY-20260926-134825.md
docs/operations/491-BUILD20-EAS-PRODUCTION-ONE-PASS-20260926-141114.md
docs/operations/492-EAS-CLI-UPDATE-24.8.0-20260926-145103.md
docs/operations/493-EAS-CLI-UPDATE-24.8.0-RECOVERY-20260926-145602.md
docs/operations/494-BUILD21-DEX-R8-SOURCE-OPTIMIZATION-20260926-213130.md
docs/operations/495-BUILD21-EAS-PRODUCTION-R8-ONE-PASS-20260926-213803.md
docs/operations/496-ANDROID15-16-ADAPTIVE-EDGE-IMAGE-MODERNIZATION-20260927-010838.md
docs/operations/497-ANDROID-NATIVE-MODERNIZATION-20260927-013820.md
docs/operations/497-ANDROID-NATIVE-MODERNIZATION-V2-20260927-014804.md
docs/operations/497-ANDROID-NATIVE-MODERNIZATION-V3-20260927-020420.md
docs/operations/497A-ANDROID-NATIVE-ENVIRONMENT-DISCOVERY-20260927-021435-2184537.md
docs/operations/497B-NATIVE-AUDIT-EXPORT-20260927-022934-2218328.md
docs/operations/498-BUILD22-SERVER-RELEASE-READINESS-20261001-150808.md
docs/operations/499-BUILD22-EAS-REMOTE-VERSION-GATE-20261001-151651.md
docs/operations/500-BUILD22-GOOGLE-PLAY-PRODUCTION-SUBMIT-PROFILE-20261001-153135.md
docs/operations/501-BUILD22-PRODUCTION-AUTO-SUBMIT-20261001-153751.md
docs/operations/501A-BUILD22-EAS-FAILURE-RECOVERY-AUDIT-20261001-154607.md
docs/operations/501B-BUILD22-GOOGLE-PLAY-PRODUCTION-SUBMIT-20261002-082457.md
docs/operations/502-BUILD22-PRODUCTION-ACCEPTANCE-READINESS-20261002-094504.md
docs/operations/507R-PAYMENT-VERIFY-IPS-TERMINAL-STATE-FIX-RECOVERY-20261003-083147.md
docs/operations/507R2-PAYMENT-VERIFY-IPS-TERMINAL-STATE-FIX-TEST-RUNNER-RECOVERY-20261003-084051.md
docs/operations/507R3-PAYMENT-VERIFY-IPS-PRODUCTION-RUNTIME-FIX-20261003-084429.md
docs/operations/514-CUSTOMER-ORDER-AMENDMENTS-BEFORE-SHIPMENT-20261003-131853.md
docs/operations/516-FINANCIAL-STATE-CANONICALIZATION-20261005-151607.md
docs/operations/517-BATCH516R-FINANCIAL-STATE-CANONICALIZATION-RECOVERY-20261005-152156.md
docs/operations/519-BATCH517-CATALOG-IMAGE-AVAILABILITY-20261005-204659.md
error_log-20260927.gz
error_log-20261004.gz
FAIL_STAGE=source-scope-and-syntax
FAIL_REASON=source scope mismatch
SOURCE_ROLLBACK=APPLIED_PRE_DATA_MUTATION

============================================================
ALD1N BATCH517R FINAL CERTIFICATION
RECOVERS_REPORT=docs/operations/519-BATCH517-CATALOG-IMAGE-AVAILABILITY-20261005-204659.md
RECOVERS_REPORT_SHA256=c143b34f4a0404cc726a63830a3469b4f9406c4012d324f1618215e88cf76896
RECOVERY_TARGETS=WORKTREE_PREFLIGHT_KNOWN_HTACCESS_AND_RECORDED_RESIDUE
============================================================
BATCH_RESULT=FAIL
FAILED_STAGE=source-scope-and-syntax
SOURCE_MUTATION=YES
COMMIT_CREATED=NO
PUSH_COMPLETED=NO
SOURCE_COMMIT=NONE
TARGET_SKUS=LATITUDE-551P,HP-630-G10,ThinPad-T15-Gen2,SSD-256GB-M2,T490S-TOUCHSCREEN,ASUS-TUF-GAMING,DELL-LATITUDE-5440,SAMSUNG-RAM-MEMORIJA-DDR4-8GB-SKHYNIX-MICRON-POLOVNO
IMAGE_AUDIT_BEFORE=NOT_RUN
DB_BACKUP=NONE
IMAGE_REPAIR_APPLIED=NO
IMAGE_AUDIT_AFTER=NOT_RUN
CMS_STATIC=NOT_RUN
MOBILE_TYPECHECK=NOT_RUN
MOBILE_VALIDATOR=NOT_RUN
EXPO_CHECK=NOT_RUN
EXPO_DOCTOR=NOT_RUN
OPENAPI_PARITY=NOT_RUN
PHPUNIT_FEATURE_TESTS=NOT_RUN_PRODUCTION_NO_DEV_DEPENDENCIES
APP_VERSION=1.0.0
RUNTIME_VERSION=1.0.0-build17
VERSION_CODE_LAST_PRODUCTION=22
PROFILE_CHANNEL=production/production
EAS_BUILD_STARTED=NO
EAS_SUBMIT_STARTED=NO
OTA_PUBLISHED=NO
GOOGLE_PLAY_ACTION=NO
BUILD23_DEFERRED=YES
NEXT_ACTION=TARGETED_BATCH517_RECOVERY_FROM_THIS_REPORT
