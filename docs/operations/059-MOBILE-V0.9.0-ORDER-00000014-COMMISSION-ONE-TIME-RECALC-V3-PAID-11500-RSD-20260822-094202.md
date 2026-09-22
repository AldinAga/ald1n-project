
============================================================
ONE-TIME COMMISSION V3 - EXACT 11,500 RSD PAYOUT + CANCELLED -> PAID
============================================================
DATE=Sat Aug 22 09:42:02 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
ORDER_NUMBER=ALD-20260819-00000014
EXACT_PAID_RSD=11500.00
ACTOR_USER_ID=1
PURPOSE=REPLACE_LEGACY_INCORRECT_COMMISSION_WITH_CONFIRMED_11500_RSD_ACTUAL_PAYOUT_AND_MARK_PAID_NOW
MUTATION_SCOPE=ONE_ORDER_ONLY_ORDER_ITEM_COMMISSION_SNAPSHOTS_ORDER_COMMISSION_STATUS_AND_AUDIT_HISTORY
COMMISSION_STATUS_TRANSITION=CANCELLED_TO_PAID_ONE_TIME_EXCEPTION
PAID_AT_SOURCE=LARAVEL_NOW_AT_EXECUTION_TIME
PAYMENT_METHOD=other
PAYMENT_REFERENCE=ONE-TIME-PAID-11500-RSD
ORDER_TOTAL_CHANGED=NO
ORDER_PAYMENT_LEDGER_CHANGED=NO
STOCK_CHANGED=NO
ORDER_STATUS_CHANGED=NO
SOURCE_CHANGES=NO
DATABASE_SCHEMA_CHANGES=NO
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
EAS_COMMANDS_RUN=NO

============================================================
0. PREFLIGHT + CONCURRENCY
============================================================
CONCURRENCY_LOCK=ACQUIRED
PHP_VERSION=8.4.24
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionCalculator.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/AuditLogger.php
CURRENT_COMMISSION_POLICY=PASS_10_PERCENT_WITH_50_EUR_AUTOMATIC_CAP
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

============================================================
1. FULL APPLICATION BACKUP + VERIFY
============================================================
PASS Backup je kreiran: /home/icaffeco/backups/current/20260822-094204-manual-646692
Veličina: 256,83 MB
Backup: /home/icaffeco/backups/current/20260822-094204-manual-646692
PASS Backup verzija: 2.2.0.
PASS Backup je svez: 0,0 h.
PASS SQL backup SHA-256 je validan.
PASS SQL gzip je citljiv; dekompresovano 1,82 MB.
PASS SQL backup sadrzi CREATE TABLE.
PASS SQL backup sadrzi migrations tabela.
PASS Verifikovani privatni backup fajlovi: 131/131.
PASS Backup evidencija i velicina direktorijuma su uskladjene: 256,83 MB.
Backup je logicki validan i spreman za kontrolisanu restore probu na odvojenoj bazi.
FULL_APPLICATION_BACKUP=PASS
FULL_APPLICATION_BACKUP_VERIFY=PASS

============================================================
2. TARGETED SNAPSHOT + TRANSACTIONAL V3 CORRECTION
============================================================
TARGETED_BACKUP=/home/icaffeco/backups/releases/mobile-v0.9.0-order-00000014-commission-one-time-recalc-v3-paid-11500-rsd-20260822-094202
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.mobile-v0.9.0-order-00000014-commission-one-time-recalc-v3-paid-11500-rsd.rmsCEE/recalculate-and-mark-paid.php
EMBEDDED_MUTATOR_PHP_LINT=PASS
TARGET_ORDER_ID=14
TARGET_COMMISSION_ID=4
TARGETED_BEFORE_SNAPSHOT=/home/icaffeco/backups/releases/mobile-v0.9.0-order-00000014-commission-one-time-recalc-v3-paid-11500-rsd-20260822-094202/before.json
MUTATION_MODE=corrected_and_marked_paid
ORDER_ID=14
COMMISSION_ID=4
OLD_COMMISSION_STATUS=cancelled
NEW_COMMISSION_STATUS=paid
EXACT_PAID_RSD=11500.00
ORDER_EUR_RSD_RATE_SNAPSHOT=117.370000
STORED_COMMISSION_TOTAL_EUR_EQUIVALENT=97.98
CURRENT_POLICY_AUTOMATIC_TOTAL_EUR=68.60
ACTUAL_PAYOUT_ABOVE_AUTOMATIC_EUR=29.38
OLD_COMMISSION_TOTAL_EUR=840.00
PAID_AT=2026-08-22T07:42:08.970635Z
PAYMENT_METHOD=other
PAYMENT_REFERENCE=ONE-TIME-PAID-11500-RSD
ITEM_COUNT=3
ITEM_1_ID=12
ITEM_1_SKU=SSD-I-HDD-000033
ITEM_1_QTY=23
ITEM_1_OLD_SOURCE=automatic
ITEM_1_OLD_UNIT_EUR=20.00
ITEM_1_OLD_TOTAL_EUR=460.00
ITEM_1_AUTOMATIC_UNIT_EUR=2.40
ITEM_1_AUTOMATIC_TOTAL_EUR=55.20
ITEM_1_NEW_SOURCE=manual
ITEM_1_NEW_UNIT_EUR=3.43
ITEM_1_NEW_TOTAL_EUR=78.84
ITEM_2_ID=13
ITEM_2_SKU=SAMSUNG-000034
ITEM_2_QTY=4
ITEM_2_OLD_SOURCE=automatic
ITEM_2_OLD_UNIT_EUR=20.00
ITEM_2_OLD_TOTAL_EUR=80.00
ITEM_2_AUTOMATIC_UNIT_EUR=1.10
ITEM_2_AUTOMATIC_TOTAL_EUR=4.40
ITEM_2_NEW_SOURCE=manual
ITEM_2_NEW_UNIT_EUR=1.57
ITEM_2_NEW_TOTAL_EUR=6.28
ITEM_3_ID=14
ITEM_3_SKU=MICRON-000035
ITEM_3_QTY=15
ITEM_3_OLD_SOURCE=automatic
ITEM_3_OLD_UNIT_EUR=20.00
ITEM_3_OLD_TOTAL_EUR=300.00
ITEM_3_AUTOMATIC_UNIT_EUR=0.60
ITEM_3_AUTOMATIC_TOTAL_EUR=9.00
ITEM_3_NEW_SOURCE=manual
ITEM_3_NEW_UNIT_EUR=0.86
ITEM_3_NEW_TOTAL_EUR=12.86
TARGETED_AFTER_SNAPSHOT=/home/icaffeco/backups/releases/mobile-v0.9.0-order-00000014-commission-one-time-recalc-v3-paid-11500-rsd-20260822-094202/after.json
COMMISSION_ONE_TIME_V3_TRANSACTION=PASS
BEFORE_SNAPSHOT_SHA256=3d26db0e8c51d2ae08787854acd1eb61e6cf4785ca0c3304b1f93379fd201236
AFTER_SNAPSHOT_SHA256=9aafc7189798daee044318b9045f98c218be1d031cff4adad8684bdd2993549c

============================================================
3. SOURCE IMMUTABILITY
============================================================
CMS_MOBILE_GIT_VISIBLE_STATE_UNCHANGED=PASS

============================================================
4. FINAL
============================================================
TARGET_ORDER=ALD-20260819-00000014
CORRECTION_SCOPE=ONE_ORDER_ONLY
CONFIRMED_ACTUAL_PAYOUT_RSD=11500.00
COMMISSION_STATUS_AFTER=paid
PAID_AT=CURRENT_LARAVEL_TIME_AT_EXECUTION
RSD_AMOUNT_PERSISTENCE=EXACT_11500_RSD_IN_STATUS_HISTORY_AND_AUDIT_METADATA
EUR_STORAGE=ORDER_COMMISSION_TOTAL_EUR_USES_ORDER_EUR_RSD_RATE_SNAPSHOT_EQUIVALENT
ITEM_SNAPSHOT_POLICY=MANUAL_ALLOCATION_OF_CONFIRMED_PAYOUT_WITH_CURRENT_10_PERCENT_AUTOMATIC_POLICY_AS_MINIMUM_GUARD
PAYMENT_METHOD=other
PAYMENT_REFERENCE=ONE-TIME-PAID-11500-RSD
COMMISSION_PAYMENT_BATCH_CREATED=NO_INDIVIDUAL_ONE_TIME_CORRECTION
NOTIFICATION_SENT=NO_HISTORICAL_DATA_CORRECTION
ORDER_TOTAL_CHANGED=NO
ORDER_PAYMENT_LEDGER_CHANGED=NO
STOCK_CHANGED=NO
ORDER_STATUS_CHANGED=NO
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
SOURCE_CHANGES=0
MOBILE_SOURCE_CHANGES=0
EAS_COMMANDS_RUN=NO
BACKUP=/home/icaffeco/backups/releases/mobile-v0.9.0-order-00000014-commission-one-time-recalc-v3-paid-11500-rsd-20260822-094202
REPORT=/home/icaffeco/ald1n-project/docs/operations/059-MOBILE-V0.9.0-ORDER-00000014-COMMISSION-ONE-TIME-RECALC-V3-PAID-11500-RSD-20260822-094202.md
ORDER_00000014_COMMISSION_ONE_TIME_RECALC_V3_PAID_11500_RSD=PASS
NEXT_ACTION=UPLOAD_059_REPORT_TO_CHAT_AND_VERIFY_EXACT_PAYOUT_AND_PAID_TIMESTAMP
PASS: V3 ONE-TIME COMMISSION CORRECTION COMPLETED FOR ALD-20260819-00000014
