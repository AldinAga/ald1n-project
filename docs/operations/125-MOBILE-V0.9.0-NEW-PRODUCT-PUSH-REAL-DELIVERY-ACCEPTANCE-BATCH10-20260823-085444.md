============================================================
125 - MOBILE v0.9.0 NEW PRODUCT PUSH REAL DELIVERY ACCEPTANCE - BATCH 10
============================================================
DATE=Sun Aug 23 08:54:44 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/125-MOBILE-V0.9.0-NEW-PRODUCT-PUSH-REAL-DELIVERY-ACCEPTANCE-BATCH10-20260823-085444.md
PURPOSE=SEND_EXACTLY_ONE_CONTROLLED_PRODUCT_PUSH_THROUGH_EXISTING_PRODUCTION_OUTBOX_AND_DISPATCHER
MODE=CONTROLLED_REAL_PUSH_ACCEPTANCE
SOURCE_WRITES=0
PRODUCT_WRITES=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
OPENAPI_CHANGES=0
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
EXPECTED_REAL_PUSH_MESSAGES=1
EXPECTED_ELIGIBLE_USERS=1
EXPECTED_ELIGIBLE_DEVICES=1
PRODUCT_VARIANTS=DECOMMISSIONED_AND_FORBIDDEN

============================================================
0. HARD PRECONDITIONS + BATCH 9 PASS
============================================================
PREREQUISITE_BATCH9=PASS
PREREQUISITE_BATCH9_REPORT=/home/icaffeco/ald1n-project/docs/operations/124-MOBILE-V0.9.0-NEW-PRODUCT-PUSH-ANNOUNCEMENT-IMPLEMENTATION-BATCH9-20260823-010558.md
ACCEPTANCE_PRODUCT_ID=45
ACCEPTANCE_PRODUCT_SLUG=hp-pavillion-amd-ryzen-5-5500u-16gb-256gb
CERTIFIED_GIT_HEAD=PASS_4ee5f845c0e24299af19528d03b1510f3eb6d9ee
BATCH9_SOURCE_CONTRACT=PASS
SOURCE_PRESTATE=PASS_BATCH9_EXACT_4_FILE_DIFF_ONLY

============================================================
1. PRODUCTION PUSH DOCTOR + SCHEDULER
============================================================
PASS mobile_push_outbox tabela postoji.
PASS Expo push provider je izabran.
PASS Mobile push delivery je aktivan.
Registrovani aktivni push uređaji: 2
Push outbox pending: 0
Push outbox failed: 3
MOBILE_PUSH_DOCTOR_STRICT=PASS

  15  6 * * *  php artisan exchange-rate:update ........................................................................................................ Next Due: za 21 sat
  10  * * * *  php artisan app:automation-run ....................................................................................................... Next Due: za 15 minuta
  5   8 * * *  php artisan app:automation-run --digest ................................................................................................ Next Due: za 23 sata
  *   * * * *  php artisan app:order-email-dispatch ................................................................................................ Next Due: za 14 sekundi
  *   * * * *  php artisan app:mobile-push-dispatch --limit=100 .................................................................................... Next Due: za 14 sekundi
  */5 * * * *  php artisan app:management-report-dispatch --limit=100 .............................................................................. Next Due: za 14 sekundi
  *   * * * *  php artisan app:scheduler-heartbeat ................................................................................................. Next Due: za 14 sekundi
  45  3 * * *  php artisan app:customer-portal-maintenance ............................................................................................ Next Due: za 18 sati
  30  2 * * *  php artisan app:backup-create --type=daily ............................................................................................. Next Due: za 17 sati
  10  3 * * 0  php artisan app:backup-create --type=weekly ............................................................................................. Next Due: za 6 dana
  45  7 * * *  php artisan app:system-health --snapshot ............................................................................................... Next Due: za 22 sata

MOBILE_PUSH_SCHEDULE_ENTRY=PASS_PRESENT

============================================================
2. READ-ONLY LIVE RECIPIENT + PRODUCT + OUTBOX PREFLIGHT
============================================================
No syntax errors detected in /tmp/ald1n-mobile-v0.9.0-product-push-real-acceptance-batch10.20260823-085444.2461421/preflight.php
PRODUCT_ID=45
PRODUCT_SLUG=hp-pavillion-amd-ryzen-5-5500u-16gb-256gb
PRODUCT_NAME_LENGTH=41
ELIGIBLE_DEVICE_COUNT=1
ELIGIBLE_DISTINCT_USER_COUNT=1
EXISTING_PENDING_OR_PROCESSING_PUSH_ROWS=0
ELIGIBLE_USER_ID=1
PREFLIGHT=PASS
CONTROLLED_RECIPIENT_PREFLIGHT=PASS_EXACTLY_ONE_ELIGIBLE_USER_AND_DEVICE

============================================================
3. ENQUEUE EXACTLY ONE CONTROLLED REAL PRODUCT PUSH
============================================================
No syntax errors detected in /tmp/ald1n-mobile-v0.9.0-product-push-real-acceptance-batch10.20260823-085444.2461421/enqueue.php
TEST_EVENT=acceptance.product.published.45.20260823-085444
ENQUEUED_ROWS=1
ENQUEUED_PENDING_ROWS=1
ENQUEUE=PASS_ONE_PENDING_ROW
CONTROLLED_REAL_PUSH_ENQUEUE=PASS_EXACTLY_ONE_ROW
CONTROLLED_TEST_EVENT=acceptance.product.published.45.20260823-085444
PRODUCT_DATABASE_MUTATION=NO

============================================================
4. EXECUTE EXISTING PRODUCTION DISPATCHER FOR ONE ROW
============================================================
Push: obrađeno 1, poslato 1, retry 0, neuspešno 0.
Receipts: provereno 0, prihvaćeno 0, retry 0, neuspešno 0, još nema 0.
MOBILE_PUSH_DISPATCH_COMMAND_EXECUTED=YES_LIMIT_1

============================================================
5. IMMEDIATE PROVIDER-TICKET POSTSTATE
============================================================
No syntax errors detected in /tmp/ald1n-mobile-v0.9.0-product-push-real-acceptance-batch10.20260823-085444.2461421/poststate.php
POST_EVENT_ROWS=1
POST_STATUS=sent
PROVIDER_TICKET_PRESENT=YES
LAST_ERROR_PRESENT=NO
RECEIPT_DUE_PRESENT=YES
POSTSTATE=PASS_PROVIDER_ACCEPTED
REAL_PUSH_PROVIDER_ACCEPTANCE=PASS_TICKET_ISSUED
REAL_PUSH_NETWORK_SEND=PASS_ONE_CONTROLLED_MESSAGE_SUBMITTED

============================================================
6. POST-SEND PUSH DOCTOR + SOURCE IMMUTABILITY
============================================================
PASS mobile_push_outbox tabela postoji.
PASS Expo push provider je izabran.
PASS Mobile push delivery je aktivan.
Registrovani aktivni push uređaji: 2
Push outbox pending: 0
Push outbox failed: 3
MOBILE_PUSH_DOCTOR_POSTSEND=PASS
APPLICATION_SOURCE_IMMUTABILITY=PASS
GIT_DIFF_SCOPE=PRESERVED_BATCH9_EXACT_4_FILES
PRODUCT_VARIANTS_REINTRODUCED=NO

============================================================
7. ACCEPTANCE CLASSIFICATION
============================================================
BACKEND_PRODUCT_PUSH_TRIGGER_CONTRACT=PASS_FROM_BATCH9_TRANSACTIONAL_PUBLIC_SERVICE_PROBE
REAL_PRODUCTION_OUTBOX_ENQUEUE=PASS_EXACTLY_ONE_TEST_ROW
REAL_PRODUCTION_DISPATCHER_EXECUTION=PASS_LIMIT_1
REAL_EXPO_PROVIDER_TICKET=PASS
REAL_DEVICE_VISUAL_RECEIPT=REQUIRES_PHYSICAL_DEVICE_CONFIRMATION
PRODUCT_DEEP_LINK_SOURCE=PASS_V0_9_SOURCE
PRODUCT_DEEP_LINK_PHYSICAL_TAP=DEFER_UNTIL_V0_9_CLIENT_IS_INSTALLED_UNLESS_CURRENT_DEVICE_RUNS_THIS_V0_9_SOURCE
CURRENT_PLAY_OLDER_CLIENT_TAP_FALLBACK_IS_NOT_A_BATCH10_FAILURE
TEST_PUSH_TITLE=TEST_-_Novi_artikal_u_katalogu
TEST_PUSH_ROUTE=/product/hp-pavillion-amd-ryzen-5-5500u-16gb-256gb

============================================================
8. FINAL STATUS
============================================================
PREREQUISITE_BATCH9=PASS
CONTROLLED_REAL_PUSH_MESSAGES_SUBMITTED=1
CONTROLLED_ELIGIBLE_USERS=1
CONTROLLED_ELIGIBLE_DEVICES=1
PRODUCT_DATABASE_MUTATION=NO
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
OPENAPI_CHANGES=0
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
SOURCE_WRITES=0
MOBILE_V0_9_NEW_PRODUCT_PUSH_REAL_DELIVERY_ACCEPTANCE_BATCH10=PASS_SERVER_PROVIDER
NEXT_ACTION=CONFIRM_NOTIFICATION_VISIBLE_ON_TARGET_DEVICE_THEN_PACKAGE_V0_9_CLIENT_FOR_FINAL_TAP_TO_PRODUCT_DETAIL_ACCEPTANCE
EXIT_CODE=0
REPORT=/home/icaffeco/ald1n-project/docs/operations/125-MOBILE-V0.9.0-NEW-PRODUCT-PUSH-REAL-DELIVERY-ACCEPTANCE-BATCH10-20260823-085444.md
UPLOAD_THIS_REPORT_TO_CHAT=/home/icaffeco/ald1n-project/docs/operations/125-MOBILE-V0.9.0-NEW-PRODUCT-PUSH-REAL-DELIVERY-ACCEPTANCE-BATCH10-20260823-085444.md
