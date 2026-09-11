============================================================
305 - MOBILE v1.0.0 NBS PUBLIC PRIMARY CONTROLLED SYNC - BATCH75
============================================================
DATE=Fri Aug 28 23:11:11 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=ONE_CONTROLLED_DATABASE_SYNC_OF_CURRENT_NBS_PUBLIC_EUR_978_SELLING_RATE_AFTER_CREDENTIAL_FREE_PRIMARY_PROVIDER_COMMIT
TRACKED_SOURCE_MUTATION=NO
DATABASE_WRITES=EXPECTED_ONLY_INSIDE_ATOMIC_EXCHANGE_RATE_SYNC_TRANSACTION
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
EAS_BUILD_COMMANDS_RUN=0
BUILD14_CREATED=NO
PRIMARY_PROVIDER_REQUIRED=nbs_html
SECONDARY_PROVIDER_PRESERVED=frankfurter
EMERGENCY_FALLBACK=PRESERVE_LAST_SUCCESSFUL_RATE_WITHOUT_FRESHNESS_RESET
FORCED_SYNC_PRESERVES_EXISTING_MANUAL_OR_AUTO_MODE=YES
SOURCE_AUTHORITY_PRE=PASS_HEAD_REMOTE_AND_ONLY_CANONICAL_HTACCESS_TRACKED_DRIFT_PLUS_OPERATIONAL_EVIDENCE
BATCH74_V2_COMMIT_AUTHORITY=PASS_HEAD_9d699dabcfbd37a7660a803694e16ef417a5f941
PROVIDER_CHAIN_SOURCE_CONTRACT=PASS_NBS_PUBLIC_PRIMARY_FRANKFURTER_SECONDARY_LAST_SAVED_EMERGENCY
PROVIDER_PREFLIGHT=PASS
PROVIDER_PREFLIGHT_SELECTED=nbs_html
NBS_PUBLIC_RATE=117.722800
NBS_PUBLIC_DATE=2026-08-28
CANONICAL_RATE_BEFORE=117.450000
CANONICAL_PROVIDER_BEFORE=frankfurter
CANONICAL_MODE_BEFORE=auto
DATABASE_WRITE_QUERY_COUNT=0
MOBILE_VALIDATE=PASS
MOBILE_TYPECHECK=PASS_TSC_NO_EMIT
CMS_STATIC_CHECK=PASS_983_OF_983
CLEAN_STABLE_VERIFY=PASS_RUN88
CANONICAL_BUILD13_PRE_SYNC=PASS_PRESERVED_SHA256

In Connection.php line 857:
                                                                                                                                                                             
  SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'triggered_by' at row 1 (Connection: mysql, Host: localhost, Port: 3306, Database: icaffeco_  
  lrvl, SQL: insert into `exchange_rate_history` (`old_rate`, `new_rate`, `mode`, `provider`, `source`, `provider_date`, `triggered_by`, `status`, `message`, `updated_by`,  
   `created_at`) values (117.45, ?, auto, provider_chain, NBS javna kursna lista -> Frankfurter API -> poslednji uspesno sacuvan kurs ostaje aktivan, 2026-08-28 00:00:00,   
  batch75-nbs-public-primary-sync-20260828-231143, failed, SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'triggered_by' at row 1 (Connection  
  : mysql, Host: localhost, Port: 3306, Database: icaffeco_lrvl, SQL: insert into `exchange_rate_history` (`old_rate`, `new_rate`, `mode`, `provider`, `source`, `provider_  
  date`, `triggered_by`, `status`, `message`, `updated_by`, `created_at, ?, 2026-08-28 23:11:43))                                                                            
                                                                                                                                                                             

In MySqlConnection.php line 53:
                                                                                                        
  SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'triggered_by' at row 1  
                                                                                                        

FAILED_STAGE=CONTROLLED_DATABASE_SYNC
FAIL_REASON=CONTROLLED_SYNC_PASS_MARKER_MISSING
TRACKED_SOURCE_MUTATION=NO
DATABASE_SYNC_COMMITTED=NO_OR_ROLLED_BACK_IF_STAGE_SYNC
GIT_COMMIT_CREATED=0
GIT_PUSHED=0
EAS_BUILD_COMMANDS_RUN=0
BUILD14_CREATED=NO
BATCH75_RESULT=FAIL
