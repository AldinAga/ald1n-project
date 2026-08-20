============================================================
MOBILE v0.7.0 - COMMISSION MINIMUM POLICY 10 PERCENT - BATCH 5
============================================================
DATE=Wed Aug 19 22:58:44 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-COMMISSION-MINIMUM-POLICY-10-PERCENT-BATCH5-20260819-225844.md
BACKUP=/home/icaffeco/backups/releases/mobile-v0.7.0-commission-minimum-policy-10-percent-batch5-20260819-225844
MODE=MUTATING_COMMISSION_POLICY_SOURCE_AND_SCHEMA_DEFAULTS_WITH_BACKUP_ROLLBACK
TARGET=REMOVE_FIXED_20_EUR_FLOOR_USE_DEFAULT_10_PERCENT_OF_PRODUCT_VALUE
AUTOMATIC_POLICY=TEN_PERCENT_WITH_EXISTING_50_EUR_MAXIMUM_PRESERVED
MANUAL_POLICY=OPTIONAL_AND_AT_LEAST_UNCAPPED_TEN_PERCENT_OF_PRODUCT_VALUE_IN_EUR
EXISTING_PRODUCT_POLICY=NO_AUTOMATIC_DATA_REWRITE_INVALID_MANUAL_VALUES_FALL_BACK_TO_AUTOMATIC
HISTORICAL_COMMISSION_POLICY=ORDER_ITEM_SNAPSHOTS_AND_ORDER_COMMISSIONS_IMMUTABLE
DIRECT_SALE_POLICY=ZERO_COMMISSION_IMMUTABLE
DATABASE_BUSINESS_WRITES_EXPECTED=0
DATABASE_SCHEMA_CHANGES=2_COLUMN_DEFAULTS_ONLY
MIGRATIONS_RUN=YES_ONE_FORWARD_DEFAULT_ONLY_MIGRATION
ROUTE_CHANGES=NO
DEPENDENCY_CHANGES=NO
NEW_NATIVE_DEPENDENCY=NO
APP_VERSION_CHANGE=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO

============================================================
0. PREFLIGHT + BATCH 4 V4 PREREQUISITE
============================================================
CONCURRENCY_LOCK=ACQUIRED
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
BATCH4_V4_REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-ADMIN-MOBILE-CLIENT-UI-COMMISSION-VISIBILITY-BATCH4-V4-20260819-221354.md
BATCH4_V4_PREREQUISITE=PASS_90_PERCENT
OPENAPI_PRE_PARITY=PASS_3_COPIES
COMMISSION_VISIBILITY_SOURCE_PREREQUISITE=PASS
IMMUTABLE_BASELINE_CAPTURED=YES
ROUTE_CACHE_STATE_CAPTURED=YES
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/commission-db-probe.php
DB_CONNECTION=mysql
EUR_RSD_RATE=117.370000
PRODUCT_ROW_COUNT=30
ORDER_ITEM_ROW_COUNT=13
ORDER_COMMISSION_ROW_COUNT=4
PRODUCT_BUSINESS_STATE_HASH=be60cc040c34224c04e0c6073f1f9c97c8863a62dc53a2a4604bf55fda20d954
ORDER_ITEM_COMMISSION_SNAPSHOT_HASH=8b376dd7c24b224a4cd3c161332f69d3d0c8b5ed5ad5bd204b7f7816272b3a50
ORDER_COMMISSION_STATE_HASH=78bd236683078670755fe22b7597201c5dd505fe5039a33dc300112bba0f948f
ORDER_ITEM_COMMISSION_UNIT_COLUMN_TYPE=decimal(12,2)
ORDER_ITEM_COMMISSION_TOTAL_COLUMN_TYPE=decimal(14,2)
ORDER_ITEM_COMMISSION_UNIT_DEFAULT=20.00
ORDER_ITEM_COMMISSION_TOTAL_DEFAULT=20.00
TARGET_COMMISSION_DEFAULT_MIGRATION_RAN=NO
PRODUCTS_WITH_MANUAL_COMMISSION=12
MANUAL_PRODUCTS_BELOW_TARGET_10_PERCENT_COUNT=4
MANUAL_PRODUCTS_BELOW_TARGET_10_PERCENT_IDS=14,15,24,36
RSD_MANUAL_PRODUCTS_WITHOUT_VERIFIABLE_RATE_COUNT=0
RSD_MANUAL_PRODUCTS_WITHOUT_VERIFIABLE_RATE_IDS=
PRODUCT_VARIANTS_TABLE_PRESENT=NO
PRODUCT_VARIANT_SPEC_VALUES_TABLE_PRESENT=NO
COMMISSION_DB_PROBE_FINAL_SENTINEL=PASS
COMMISSION_SCHEMA_PRESTATE=LEGACY_20_00_DEFAULTS_CONFIRMED
DATABASE_PREFLIGHT=PASS_READ_ONLY
--- ACTIVE FIXED 20 EUR POLICY HITS BEFORE ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionCalculator.php:11:        if ($manualEur !== null && $manualEur >= 20.0) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionCalculator.php:19:        return round(min(50.0, max(20.0, $priceEur * 0.10)), 2);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:154:                'commission_source_snapshot' => $manualCommission !== null && $manualCommission >= 20.0 ? 'manual' : 'automatic',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:155:                'commission_rate_percent_snapshot' => $manualCommission !== null && $manualCommission >= 20.0 ? null : 10,
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/commissions/index.blade.php:5:<div class="page-heading"><div><span class="eyebrow">Lični pregled</span><h1>Moje provizije</h1><p>Provizija po komadu nikada nije manja od 20 EUR. Ovde pratiš obračun, odobrenje i isplatu.</p></div><div class="count-pill">{{ $commissions->total() }} zapisa</div></div>
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:251:$check('korisnik vidi samo svoje provizije i minimum 20 EUR', str_contains($commissionReport, "where('user_id', \$user->id)") && str_contains($ownCommissionView, 'nikada nije manja od 20 EUR') && !str_contains($ownCommissionView, '50 EUR'));
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/show.blade.php:164:                            <p>Provizija po komadu nije manja od 20 EUR.</p>
CURRENT_COMMISSION_POLICY_STATE=LEGACY_FIXED_20_EUR_CONFIRMED

============================================================
1. EXACT FILE BACKUP + PRESTATE MANIFEST
============================================================
BACKUP_READY=PASS_15_EXISTING_FILES_PLUS_OPTIONAL_POLICY_AND_MIGRATION_PRESTATE
HISTORICAL_BUSINESS_STATE_BASELINE=CAPTURED

============================================================
2. BUILD TARGET COMMISSION CALCULATOR + TESTS + SMOKE + SCHEMA DEFAULT MIGRATION
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/2026_08_19_222500_remove_fixed_commission_snapshot_defaults_v0_7.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/commission-schema-default-rollback.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/domain-smoke.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/CommissionCalculator.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/CommissionCalculatorTest.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/CommissionPercentagePolicyContractTest.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/commission-percentage-policy-smoke.php
TARGET_CALCULATOR_TESTS_SMOKE_MIGRATION_BUILD=PASS

============================================================
3. PATCH ORDER SNAPSHOTS + VALIDATION + UI + STATIC + OPENAPI + MOBILE VALIDATOR
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/patch-policy.php
COMMISSION_POLICY_PATCHER=PASS
TEMP_SOURCE_PATCH=PASS_10_PATCHED_EXISTING_FILES_PLUS_OPENAPI_COPIES

============================================================
4. TEMP PHP + POLICY CONTRACT VALIDATION
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/2026_08_19_222500_remove_fixed_commission_snapshot_defaults_v0_7.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/CommissionCalculator.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/OrderService.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/ProductRequest.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/CommissionCalculatorTest.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/CommissionPercentagePolicyContractTest.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/domain-smoke.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/commission-percentage-policy-smoke.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/static-check.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/CatalogAccessTest.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/patch-policy.php
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/policy-contract-probe.php
TEMP_COMMISSION_POLICY_CONTRACT=PASS
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.7.0-commission-policy-batch5.nITg8C/temp-calculator-probe.php
TEMP_CALCULATOR_RUNTIME=PASS
TEMP_STATIC_VALIDATION=PASS

============================================================
5. INSTALL COMMISSION POLICY SOURCE + FORWARD SCHEMA DEFAULT MIGRATION
============================================================
MANAGED_SOURCE_INSTALLED=PASS_15_EXISTING_PLUS_2_POLICY_FILES_PLUS_1_MIGRATION
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_08_19_222500_remove_fixed_commission_snapshot_defaults_v0_7.php

   INFO  Running migrations.

  2026_08_19_222500_remove_fixed_commission_snapshot_defaults_v0_7 .................................................................... 10.46ms DONE

COMMISSION_SCHEMA_DEFAULT_MIGRATION=PASS_APPLIED_BY_BATCH

============================================================
6. EARLY PHP LINT + TARGETED COMMISSION TESTS
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_08_19_222500_remove_fixed_commission_snapshot_defaults_v0_7.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionCalculator.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/CommissionCalculatorTest.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/CatalogAccessTest.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/CommissionPercentagePolicyContractTest.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/bin/domain-smoke.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/bin/commission-percentage-policy-smoke.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php
PASS 20 EUR artikal daje 2 EUR provizije
PASS 50 EUR artikal daje 5 EUR provizije
PASS 100 EUR artikal daje 10 EUR provizije
PASS Automatski maksimum 50 EUR ostaje očuvan
PASS Ručni minimum je punih 10 procenata bez nominalnog poda
PASS Ručna provizija ispod 10 procenata se ne koristi
PASS Ručna provizija jednaka 10 procenata se koristi
PASS RSD konverzija koristi kurs
PASS Bez RSD kursa nema izmišljenog nominalnog minimuma
Domain smoke: 9/9 uspešno.
DOMAIN_SMOKE=PASS_PERCENTAGE_POLICY
PASS 20 EUR artikal daje 2 EUR automatske provizije
PASS 50 EUR artikal daje 5 EUR automatske provizije
PASS 100 EUR artikal daje 10 EUR automatske provizije
PASS Automatski maksimum 50 EUR ostaje očuvan
PASS RSD cena koristi aktuelni kurs za 10 procenata
PASS Nedostupan RSD kurs ne vraća lažnih 20 EUR
PASS Ručna provizija jednaka automatskom obračunu je validna
PASS Ručna provizija ispod automatskog obračuna pada na automatic
PASS Ručna provizija iznad automatskog obračuna ostaje dozvoljena
PASS Ručni minimum za 1000 EUR je punih 100 EUR
PASS Calculator nema legacy max 20 floor
PASS Calculator nema legacy manual 20 floor
PASS Order snapshots koriste centralni usesManual autoritet i centralnu stopu
PASS ProductRequest validira dinamički automatski minimum
PASS Customer copy više ne tvrdi minimum 20 EUR
PASS Direct Sale ostaje nulta provizija
Commission Percentage Policy smoke: 16/16 uspešno.
COMMISSION_POLICY_SMOKE=PASS

   ERROR  Command "test" is not defined. Did you mean one of these?

  ⇂ app:send-test-mail
  ⇂ app:test-database-doctor
  ⇂ make:test
  ⇂ schedule:test


============================================================
ROLLBACK
============================================================

   INFO  Rolling back migrations.

  2026_08_19_222500_remove_fixed_commission_snapshot_defaults_v0_7 ..................................................................... 2.64ms DONE

COMMISSION_SCHEMA_DEFAULT_ROLLBACK=PASS
ROLLBACK_COMMISSION_SCHEMA_DEFAULTS=RESTORED_TO_20_00_PRESTATE
ROLLBACK_COMMISSION_POLICY_SOURCE=RESTORED
ROLLBACK_NEW_POLICY_FILES=RESTORED_OR_REMOVED_TO_PRESTATE
ROLLBACK_DATABASE_BUSINESS_ROWS=NO_WRITES_TO_REVERSE
ROLLBACK_MIGRATION_RECORD_AND_SCHEMA_DEFAULTS=RESTORED_WHEN_APPLIED_BY_BATCH
ROLLBACK_ROUTES=NOT_TOUCHED
ROLLBACK_DEPENDENCIES=NOT_TOUCHED
BACKUP=/home/icaffeco/backups/releases/mobile-v0.7.0-commission-minimum-policy-10-percent-batch5-20260819-225844
