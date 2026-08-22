
============================================================
ONE-TIME COMMISSION RECALCULATION - EXACT ORDER ONLY
============================================================
DATE=Sat Aug 22 09:35:03 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
ORDER_NUMBER=ALD-20260819-00000014
PURPOSE=RECALCULATE_LEGACY_FIXED_20_EUR_COMMISSION_USING_CURRENT_10_PERCENT_POLICY
MUTATION_SCOPE=ORDER_ITEMS_COMMISSION_SNAPSHOTS_AND_ORDER_COMMISSION_TOTAL_FOR_ONE_ORDER_ONLY
PRICE_SOURCE=HISTORICAL_ORDER_ITEM_UNIT_PRICE_ORIGINAL_AND_ORIGINAL_CURRENCY
CURRENT_PRODUCT_PRICE_USED=NO
ORDER_FINANCIAL_TOTAL_CHANGED=NO
ORDER_PAYMENT_CHANGED=NO
STOCK_CHANGED=NO
ORDER_STATUS_CHANGED=NO
COMMISSION_STATUS_CHANGED=NO
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
PASS Backup je kreiran: /home/icaffeco/backups/current/20260822-093505-manual-f87420
Veličina: 256,83 MB
Backup: /home/icaffeco/backups/current/20260822-093505-manual-f87420
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
2. TARGETED SNAPSHOT + TRANSACTIONAL RECALCULATION
============================================================
TARGETED_BACKUP=/home/icaffeco/backups/releases/mobile-v0.9.0-order-00000014-commission-one-time-recalc-20260822-093503
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.mobile-v0.9.0-order-00000014-commission-one-time-recalc.yuBm0f/recalculate.php
EMBEDDED_MUTATOR_PHP_LINT=PASS
TARGET_ORDER_ID=14
TARGET_COMMISSION_ID=4
TARGETED_BEFORE_SNAPSHOT=/home/icaffeco/backups/releases/mobile-v0.9.0-order-00000014-commission-one-time-recalc-20260822-093503/before.json

In recalculate.php line 21:

  commission status is cancelled; automatic correction is allowed only for pending/approved commissions


FAIL: after snapshot missing
