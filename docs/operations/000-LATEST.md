# Ald1n Operations Report Archive

- Canonical directory: `docs/operations`
- Archive policy: **append-only**. Historical numbered reports are never rotated or deleted when a new report is added.
- Current application version: **1.0.0**
- Latest completed source batch: **Batch176 - Receivables payment OpenAPI contract repair (V2 recovery)**
- Frozen application source commit: `0e1035d0ab60108e0648336f14e5b61daed74e3d`
- Latest release-readiness checkpoint: **Batch177 - Final Source Freeze and Release Readiness**
- Batch177 freeze docs commit / final build repository commit: `1aa4eaf45e02cdb8918fbd43608beb4ad6bea176`
- Final Android EAS production build: **Batch178 V2 recovery PASS**
- EAS build ID: `95172337-cf7b-4614-8e17-b7e4e9b955ab`
- Android versionCode: **18**
- App version / runtime: **1.0.0 / 1.0.0-build17**
- AAB SHA-256: `74dbb83780c0406c48bc89ec0fb23e7aaff9f0251a9c6dde05b12b0698fb378a`
- AAB archive: `/home/icaffeco/backups/releases/Ald1n-CMS-v1.0.0-production-vc18-95172337-cf7b-4614-8e17-b7e4e9b955ab.aab`
- Original Batch178 V1 ended locally with SIGHUP after the remote Build18 was created; its FAILED report is preserved as audit evidence.
- Batch178 V2 created no new EAS build and recovered the exact existing Build18.
- OTA publish: **NO**
- Google Play submit/upload by Batch178: **NO**
- Product Variants: **decommissioned guard preserved**
- Latest report: `docs/operations/480-BATCH178-SINGLE-FINAL-EAS-PRODUCTION-BUILD-V2-EXISTING-BUILD-RECOVERY-20260924-090456.md`
- Next operational batch: **Batch179 - Google Play Internal + Final Physical Device Acceptance**
- Next action: `UPLOAD_FINAL_VC18_AAB_TO_GOOGLE_PLAY_INTERNAL_THEN_RUN_FINAL_DEVICE_ACCEPTANCE`
- Next report number: `481`

This index is navigation metadata only. The numbered reports in this directory are the audit evidence of record.

---

## Batch179 final one-pass - 20260925-100803

- Status: **PASS**
- Source commit: 
============================================================
POST-MUTATION FAILURE PRESERVATION
============================================================
SOURCE_ROLLBACK=SKIPPED_BUSINESS_DATA_ALREADY_MUTATED
MANUAL_REVIEW_REQUIRED=YES
ROUTE_CACHE_RESTORE=ROUTE_CACHE_REBUILT

============================================================
FAIL
============================================================
BATCH179_FINAL_ONE_PASS=FAIL
FAILED_REASON=UNEXPECTED_RC_127_LINE_1205
ORDER_ID=81
ORDER_PRICE_CORRECTION_TARGET=2140.00_TO_21240.00
DATABASE_MUTATION=YES_ORDER81_ONLY
BUSINESS_DATA_MUTATION=YES_ORDER81_ONLY
GLASS_SCRIPT_EXECUTED=NO
SOURCE_COMMIT=74f34dbe2b41af14044a06e916e717d803da62ec
SOURCE_PUSH=PASS
TARGETED_BACKUP=/home/icaffeco/backups/batch179-order81-20260925-100803/order81-before.json
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/481-BATCH179-FINAL-ONE-PASS-20260925-100803.md
- Order: 
============================================================
POST-MUTATION FAILURE PRESERVATION
============================================================
SOURCE_ROLLBACK=SKIPPED_BUSINESS_DATA_ALREADY_MUTATED
MANUAL_REVIEW_REQUIRED=YES
ROUTE_CACHE_RESTORE=ROUTE_CACHE_REBUILT

============================================================
FAIL
============================================================
BATCH179_FINAL_ONE_PASS=FAIL
FAILED_REASON=UNEXPECTED_RC_127_LINE_1205
ORDER_ID=81
ORDER_PRICE_CORRECTION_TARGET=2140.00_TO_21240.00
DATABASE_MUTATION=YES_ORDER81_ONLY
BUSINESS_DATA_MUTATION=YES_ORDER81_ONLY
GLASS_SCRIPT_EXECUTED=NO
SOURCE_COMMIT=74f34dbe2b41af14044a06e916e717d803da62ec
SOURCE_PUSH=PASS
TARGETED_BACKUP=/home/icaffeco/backups/batch179-order81-20260925-100803/order81-before.json
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/481-BATCH179-FINAL-ONE-PASS-20260925-100803.md / ID 
============================================================
POST-MUTATION FAILURE PRESERVATION
============================================================
SOURCE_ROLLBACK=SKIPPED_BUSINESS_DATA_ALREADY_MUTATED
MANUAL_REVIEW_REQUIRED=YES
ROUTE_CACHE_RESTORE=ROUTE_CACHE_REBUILT

============================================================
FAIL
============================================================
BATCH179_FINAL_ONE_PASS=FAIL
FAILED_REASON=UNEXPECTED_RC_127_LINE_1205
ORDER_ID=81
ORDER_PRICE_CORRECTION_TARGET=2140.00_TO_21240.00
DATABASE_MUTATION=YES_ORDER81_ONLY
BUSINESS_DATA_MUTATION=YES_ORDER81_ONLY
GLASS_SCRIPT_EXECUTED=NO
SOURCE_COMMIT=74f34dbe2b41af14044a06e916e717d803da62ec
SOURCE_PUSH=PASS
TARGETED_BACKUP=/home/icaffeco/backups/batch179-order81-20260925-100803/order81-before.json
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/481-BATCH179-FINAL-ONE-PASS-20260925-100803.md
- Direct Sale correction: 
============================================================
POST-MUTATION FAILURE PRESERVATION
============================================================
SOURCE_ROLLBACK=SKIPPED_BUSINESS_DATA_ALREADY_MUTATED
MANUAL_REVIEW_REQUIRED=YES
ROUTE_CACHE_RESTORE=ROUTE_CACHE_REBUILT

============================================================
FAIL
============================================================
BATCH179_FINAL_ONE_PASS=FAIL
FAILED_REASON=UNEXPECTED_RC_127_LINE_1205
ORDER_ID=81
ORDER_PRICE_CORRECTION_TARGET=2140.00_TO_21240.00
DATABASE_MUTATION=YES_ORDER81_ONLY
BUSINESS_DATA_MUTATION=YES_ORDER81_ONLY
GLASS_SCRIPT_EXECUTED=NO
SOURCE_COMMIT=74f34dbe2b41af14044a06e916e717d803da62ec
SOURCE_PUSH=PASS
TARGETED_BACKUP=/home/icaffeco/backups/batch179-order81-20260925-100803/order81-before.json
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/481-BATCH179-FINAL-ONE-PASS-20260925-100803.md
- Mutation authority: 
- Payment, item, subtotal and paid total: synchronized and independently verified
- Audit: internal note + 
============================================================
POST-MUTATION FAILURE PRESERVATION
============================================================
SOURCE_ROLLBACK=SKIPPED_BUSINESS_DATA_ALREADY_MUTATED
MANUAL_REVIEW_REQUIRED=YES
ROUTE_CACHE_RESTORE=ROUTE_CACHE_REBUILT

============================================================
FAIL
============================================================
BATCH179_FINAL_ONE_PASS=FAIL
FAILED_REASON=UNEXPECTED_RC_127_LINE_1205
ORDER_ID=81
ORDER_PRICE_CORRECTION_TARGET=2140.00_TO_21240.00
DATABASE_MUTATION=YES_ORDER81_ONLY
BUSINESS_DATA_MUTATION=YES_ORDER81_ONLY
GLASS_SCRIPT_EXECUTED=NO
SOURCE_COMMIT=74f34dbe2b41af14044a06e916e717d803da62ec
SOURCE_PUSH=PASS
TARGETED_BACKUP=/home/icaffeco/backups/batch179-order81-20260925-100803/order81-before.json
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/481-BATCH179-FINAL-ONE-PASS-20260925-100803.md event verified
- Stock: unchanged
- CMS static: 
============================================================
POST-MUTATION FAILURE PRESERVATION
============================================================
SOURCE_ROLLBACK=SKIPPED_BUSINESS_DATA_ALREADY_MUTATED
MANUAL_REVIEW_REQUIRED=YES
ROUTE_CACHE_RESTORE=ROUTE_CACHE_REBUILT

============================================================
FAIL
============================================================
BATCH179_FINAL_ONE_PASS=FAIL
FAILED_REASON=UNEXPECTED_RC_127_LINE_1205
ORDER_ID=81
ORDER_PRICE_CORRECTION_TARGET=2140.00_TO_21240.00
DATABASE_MUTATION=YES_ORDER81_ONLY
BUSINESS_DATA_MUTATION=YES_ORDER81_ONLY
GLASS_SCRIPT_EXECUTED=NO
SOURCE_COMMIT=74f34dbe2b41af14044a06e916e717d803da62ec
SOURCE_PUSH=PASS
TARGETED_BACKUP=/home/icaffeco/backups/batch179-order81-20260925-100803/order81-before.json
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/481-BATCH179-FINAL-ONE-PASS-20260925-100803.md
- Mobile validator: zero failures
- Runtime/OpenAPI: zero drift uncached and cached
- Two-decimal presentation contract: PASS
- Product Variants: remain decommissioned
- Build18: preserved
- Glass Morphism: not executed in Batch179; Batch180 must be re-audited/refreshed against the final post-Batch179 repository head before execution
- Operation report: 
============================================================
POST-MUTATION FAILURE PRESERVATION
============================================================
SOURCE_ROLLBACK=SKIPPED_BUSINESS_DATA_ALREADY_MUTATED
MANUAL_REVIEW_REQUIRED=YES
ROUTE_CACHE_RESTORE=ROUTE_CACHE_REBUILT

============================================================
FAIL
============================================================
BATCH179_FINAL_ONE_PASS=FAIL
FAILED_REASON=UNEXPECTED_RC_127_LINE_1205
ORDER_ID=81
ORDER_PRICE_CORRECTION_TARGET=2140.00_TO_21240.00
DATABASE_MUTATION=YES_ORDER81_ONLY
BUSINESS_DATA_MUTATION=YES_ORDER81_ONLY
GLASS_SCRIPT_EXECUTED=NO
SOURCE_COMMIT=74f34dbe2b41af14044a06e916e717d803da62ec
SOURCE_PUSH=PASS
TARGETED_BACKUP=/home/icaffeco/backups/batch179-order81-20260925-100803/order81-before.json
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/481-BATCH179-FINAL-ONE-PASS-20260925-100803.md
