
============================================================
0. V1+V2+V3+V4+V5+V6 FAILURE RECONCILIATION + STABLE CHECKPOINT + SOURCE AUTHORITY PREFLIGHT
============================================================
V1_FAILURE_RECONCILIATION=PASS_WRONG_EXPO_ROUTER_GROUP_PATH_ONLY_NO_MIGRATION_NO_COMMIT
V2_FAILURE_RECONCILIATION=PASS_ROUTE_ANCHOR_ONLY_NO_MIGRATION_NO_COMMIT
V3_FAILURE_RECONCILIATION=PASS_DUPLICATE_STATIC_ASSIGNMENT_ANCHOR_ONLY_NO_MIGRATION_NO_COMMIT
V4_FAILURE_RECONCILIATION=PASS_PHP_TEMPLATE_INTERPOLATION_ANCHOR_ONLY_NO_MIGRATION_NO_COMMIT
V5_FAILURE_RECONCILIATION=PASS_ARTISAN_TEST_COMMAND_UNAVAILABLE_NO_MIGRATION_NO_COMMIT
V6_FAILURE_RECONCILIATION=PASS_PHPUNIT_DEV_BINARY_ABSENT_NO_MIGRATION_NO_COMMIT
STABLE_CHECKPOINT_GUARD=PASS
STABLE_CHECKPOINT_PATH=/home/icaffeco/backups/stable/ald1n-stable-20260909-112358-7e84c70
LOCAL_HEAD=7e84c7032b03aae31dbad2fafc8bd50156c69790
REMOTE_HEAD=7e84c7032b03aae31dbad2fafc8bd50156c69790
 M apps/cms/current/public/.htaccess
SOURCE_AUTHORITY=PASS_EXACT_REDIS_FINAL_BASELINE

============================================================
1. COPY-FIRST SOURCE BACKUP
============================================================
SOURCE_BACKUP=PASS_10_EXISTING_FILES

============================================================
2. CREATE ADDITIVE ALLOCATION MIGRATION + MODEL
============================================================
ALLOCATION_SCHEMA_SOURCE=PASS_ADDITIVE_RECOVERY_SAFE

============================================================
3. PATCH CMS + MOBILE SOURCE WITH EXACT BASELINE ANCHORS
============================================================
SOURCE_PATCH=PASS_ALL_EXACT_ANCHORS

============================================================
4. PRE-MIGRATION SOURCE VALIDATION - DB UNTOUCHED
============================================================
PHP_LINT=PASS_10_FILES
CMS_STATIC_SUMMARY=Ukupno: 983, neuspešno: 0
CMS_STATIC=PASS_983_TOTAL_0_FAILED
PDO_SQLITE_EXTENSION=yes

   INFO  Preparing database.  

  Creating migration table ............................................................................................................. 9.81ms DONE

   INFO  Running migrations.  

  2026_07_21_000001_create_access_tables .............................................................................................. 32.18ms DONE
  2026_07_21_000002_create_catalog_tables ............................................................................................. 74.79ms DONE
  2026_07_21_000003_create_system_tables ............................................................................................... 8.52ms DONE
  2026_07_22_000004_create_catalog_admin_tables ....................................................................................... 24.99ms DONE
  2026_07_22_000005_create_legacy_operations_tables ................................................................................... 55.70ms DONE
  2026_07_22_000006_enable_production_orders_inventory ................................................................................ 85.34ms DONE
  2026_07_23_000007_repair_production_schema_beta5 .................................................................................... 13.46ms DONE
  2026_07_23_000008_repair_authenticated_runtime_beta6 ................................................................................. 5.93ms DONE
  2026_07_23_000009_create_reports_documents_and_supplier_assignment .................................................................. 72.92ms DONE
  2026_07_23_000010_repair_reports_schema_beta1_2 ..................................................................................... 14.87ms DONE
  2026_07_23_000011_create_operational_orders_commissions_beta2 ...................................................................... 153.21ms DONE
  2026_07_23_000012_create_payments_advanced_inventory_beta3 ......................................................................... 111.07ms DONE
  2026_07_23_000013_create_automation_alerts_beta4 .................................................................................... 47.25ms DONE
  2026_07_23_000014_create_security_backup_health_beta6 ............................................................................... 33.80ms DONE
  2026_07_29_000015_repair_order_documents_and_payments_beta7_5 ....................................................................... 35.14ms DONE
  2026_07_30_000016_add_order_completion_beta7_7 ...................................................................................... 20.79ms DONE
  2026_07_30_000017_add_delivery_workflow_beta7_8 ..................................................................................... 37.22ms DONE
  2026_07_30_000018_fix_delivery_note_document_type_beta7_9 ............................................................................ 1.13ms DONE
  2026_07_30_000019_create_after_sales_cases_beta7_10 ................................................................................. 22.94ms DONE
  2026_07_30_000020_create_after_sales_actions_beta7_11 ............................................................................... 34.92ms DONE
  2026_07_30_000021_create_field_operations_beta7_12 .................................................................................. 22.15ms DONE
  2026_07_30_000022_create_service_parts_procurement_beta7_13 ......................................................................... 30.25ms DONE
  2026_07_30_000023_enable_document_revisions_beta7_14 ................................................................................ 15.09ms DONE
  2026_07_30_000024_create_warranties_preventive_maintenance_beta7_15 ................................................................. 21.25ms DONE
  2026_07_30_000025_create_order_email_outbox_beta7_16 ................................................................................ 38.15ms DONE
  2026_07_31_000026_create_receivables_collection_beta7_17 ............................................................................ 44.24ms DONE
  2026_07_31_000027_create_correlated_specifications_beta7_18 ........................................................................ 256.01ms DONE
  2026_07_31_000028_create_smart_product_management_beta7_19 .......................................................................... 85.18ms DONE
  2026_07_31_000029_create_product_variants_beta7_20 ................................................................................. 340.30ms DONE
  2026_07_31_000030_create_management_reports_beta7_21 ............................................................................... 120.96ms DONE
  2026_07_31_000031_create_customer_portal_beta7_22 ................................................................................... 23.76ms DONE
  2026_08_01_000032_create_customer_portal_2_beta7_24 ................................................................................. 52.82ms DONE
  2026_08_04_000033_create_catalog_type_layout_v2_1_3 ................................................................................. 34.47ms DONE
  2026_08_04_000034_repair_catalog_category_and_spec_integrity_v2_1_3_1 ............................................................... 29.04ms DONE
  2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3 ............................................................... 15.04ms DONE
  2026_08_04_000036_add_product_model_and_name_templates_v2_1_4 ....................................................................... 10.65ms DONE
  2026_08_05_000037_place_desktop_power_supply_field_v2_1_5 ............................................................................ 0.70ms DONE
  2026_08_05_000038_create_performance_data_quality_v2_1_6 ............................................................................ 46.39ms DONE
  2026_08_06_000039_create_mobile_devices_v2_2_0 ....................................................................................... 4.83ms DONE
  2026_08_06_000040_add_push_notification_preference_v2_2_0 ............................................................................ 4.74ms DONE
  2026_08_06_000041_create_database_queue_tables_v2_2_0 ................................................................................ 7.13ms DONE
  2026_08_07_000042_create_mobile_push_outbox_phase3b .................................................................................. 8.29ms DONE
  2026_08_08_000043_create_user_external_identities_phase3c ............................................................................ 5.49ms DONE
  2026_08_10_000100_add_direct_sale_channel_vnext ..................................................................................... 65.48ms DONE
  2026_08_10_000101_create_shipment_courier_foundation_vnext .......................................................................... 12.69ms DONE
  2026_08_10_000102_expand_order_payment_method_for_direct_sale_vnext .................................................................. 3.04ms FAIL

In Connection.php line 857:
                                                                                                                                                                             
  SQLSTATE[HY000]: General error: 1 no such table: information_schema.COLUMNS (Connection: sqlite, Database: /home/icaffeco/ald1n-project/tmp/deferred-payment-batch147-v7-  
  20260909-120813/receivables-smoke.sqlite, SQL: SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_  
  NAME = 'orders' AND COLUMN_NAME = 'payment_method' LIMIT 1)                                                                                                                
                                                                                                                                                                             

In Connection.php line 435:
                                                                               
  SQLSTATE[HY000]: General error: 1 no such table: information_schema.COLUMNS  
                                                                               

BATCH147_RESULT=FAIL_ISOLATED_SQLITE_MIGRATION_RC_1
MIGRATION_DONE=0
COMMIT_DONE=0
ROLLBACK_SOURCE=ATTEMPTED_PRE_MIGRATION
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/374-MOBILE-DEFERRED-PAYMENT-RANDOM-DATE-BATCH147-V7-20260909-120813.md
