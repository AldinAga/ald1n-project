
============================================================
CMS PRODUCT VARIANTS - BATCH 2C2C V2 FINAL PURGE + CERTIFICATION
============================================================
DATE=Wed Aug 19 09:43:29 CEST 2026
ROOT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2C2C-V2-FORWARD-MIGRATION-STATIC-TEST-SCHEMA-PURGE-FINAL-CERTIFICATION-20260819-094329.md
BACKUP=/home/icaffeco/backups/releases/product-variants-decommission-batch2c2c-v2-final-20260819-094329
MODE=FINAL_FORWARD_MIGRATION_STATIC_TEST_CONTRACT_REWRITE_SCHEMA_PURGE_CERTIFICATION_V2_OPENAPI_CLASSIFIER_FIX
SOURCE_REPLACEMENTS_EXPECTED=15
FORWARD_MIGRATION_EXPECTED=1
DATABASE_SCHEMA_CHANGES=PRODUCT_VARIANTS_FINAL_PURGE
DEPENDENCY_CHANGES=NO
EAS_BUILD=NO

============================================================
0. PREFLIGHT + BATCH2C2B V3 PREREQUISITE
============================================================
PREFLIGHT_COMMAND_bash=PASS
PREFLIGHT_COMMAND_php=PASS
PREFLIGHT_COMMAND_grep=PASS
PREFLIGHT_COMMAND_sed=PASS
PREFLIGHT_COMMAND_awk=PASS
PREFLIGHT_COMMAND_cat=PASS
PREFLIGHT_COMMAND_cp=PASS
PREFLIGHT_COMMAND_mkdir=PASS
PREFLIGHT_COMMAND_rm=PASS
PREFLIGHT_COMMAND_rmdir=PASS
PREFLIGHT_COMMAND_sha256sum=PASS
PREFLIGHT_COMMAND_find=PASS
PREFLIGHT_COMMAND_sort=PASS
PREFLIGHT_COMMAND_wc=PASS
PREFLIGHT_COMMAND_git=PASS
PREFLIGHT_COMMAND_cmp=PASS
PREFLIGHT_COMMAND_mktemp=PASS
PREFLIGHT_COMMAND_date=PASS
PREFLIGHT_COMMAND_dirname=PASS
PREFLIGHT_COMMAND_basename=PASS
PREFLIGHT_COMMAND_tee=PASS
PREFLIGHT_COMMAND_tr=PASS
PREFLIGHT_COMMAND_head=PASS
PREFLIGHT_COMMAND_tail=PASS
PREFLIGHT_COMMAND_cut=PASS
PREFLIGHT_COMMAND_stat=PASS
PREFLIGHT_COMMAND_chmod=PASS
BATCH2C2B_V3_PREREQUISITE=PASS_DEEP_RUNTIME_PRODUCT_ONLY_SOURCE_CUTOVER
PRIOR_BATCH2C2C_V1_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2C2C-FORWARD-MIGRATION-STATIC-TEST-SCHEMA-PURGE-FINAL-CERTIFICATION-20260819-093316.md
PRIOR_BATCH2C2C_V1_FAILURE=BROAD_OPENAPI_VARIANT_TEXT_SCAN_FALSE_POSITIVE_AFTER_SCHEMA_PURGE
PRIOR_BATCH2C2C_V1_ROLLBACK=PASS_SCHEMA_AND_SOURCE_RESTORED_NO_BUSINESS_DATA
BATCH2C2C_V2_FIX=OPENAPI_PRODUCT_VARIANT_CONTRACT_CLASSIFIER_REPLACES_BROAD_WORD_SCAN

============================================================
1. EXACT POST-2C2B SOURCE CONTRACT + DEFERRED 15 BASELINES
============================================================
POST_BATCH2C2B_RUNTIME_VARIANT_SIGNALS=PASS_ZERO
POST_BATCH2C2B_CORE_HASHES=PASS_48_OF_48_EXACT
RETIRED_VARIANT_FEATURE_FILES=PASS_8_OF_8_ABSENT
POST_BATCH2C2B_RESIDUAL_HASH_BASELINE=CAPTURED_9_ZERO_VARIANT_FILES
DEFERRED_STATIC_TEST_BASELINE=PASS_15_OF_15_EXACT
HISTORICAL_VARIANT_MIGRATION=PASS_PRESERVED_EXACT
ROUTE_CACHE_BASELINE=CAPTURED_0_FILES

============================================================
1B. OPENAPI PRODUCT VARIANT CONTRACT CLASSIFIER BEFORE DDL
============================================================
OPENAPI_BROAD_VARIANT_TEXT_SIGNAL_COUNT_BEFORE_CANONICAL=1
OPENAPI_FORBIDDEN_PRODUCT_VARIANT_CONTRACT_SIGNAL_COUNT_BEFORE_CANONICAL=0
OPENAPI_BROAD_VARIANT_TEXT_SIGNAL_COUNT_BEFORE_CMS=1
OPENAPI_FORBIDDEN_PRODUCT_VARIANT_CONTRACT_SIGNAL_COUNT_BEFORE_CMS=0
OPENAPI_BROAD_VARIANT_TEXT_SIGNAL_COUNT_BEFORE_MOBILE=1
OPENAPI_FORBIDDEN_PRODUCT_VARIANT_CONTRACT_SIGNAL_COUNT_BEFORE_MOBILE=0
OPENAPI_BROAD_VARIANT_TEXT_SIGNAL_COUNT_BEFORE=3
OPENAPI_FORBIDDEN_PRODUCT_VARIANT_CONTRACT_SIGNAL_COUNT_BEFORE=0
OPENAPI_BROAD_VARIANT_TEXT_DIAGNOSTIC_BEFORE_BEGIN
CANONICAL:1941:        '422': { description: Shipment validation or domain invariant failed. }
CMS:1941:        '422': { description: Shipment validation or domain invariant failed. }
MOBILE:1941:        '422': { description: Shipment validation or domain invariant failed. }
OPENAPI_BROAD_VARIANT_TEXT_DIAGNOSTIC_BEFORE_END
OPENAPI_PRODUCT_VARIANT_CONTRACT_CLASSIFIER=PASS_ZERO_FORBIDDEN_CONTRACT_SIGNALS_BROAD_TEXT_CLASSIFIED_SEPARATELY
OPENAPI_PRE_DDL_PARITY=PASS_3_COPIES
OPENAPI_PRE_DDL_HASH_BASELINE=CAPTURED_3_COPIES

============================================================
2. LIVE DB ZERO-STATE + EXACT 39/13/20 SCHEMA TOPOLOGY BEFORE DDL
============================================================
PRODUCT_VARIANTS_COUNT=0
PRODUCT_VARIANT_SPEC_VALUES_COUNT=0
NON_NULL_PRODUCT_IMAGES_PRODUCT_VARIANT_ID=0
NON_NULL_ORDER_ITEMS_PRODUCT_VARIANT_ID=0
NON_NULL_STOCK_MOVEMENTS_PRODUCT_VARIANT_ID=0
NON_NULL_AFTER_SALES_CASE_ITEMS_PRODUCT_VARIANT_ID=0
NON_NULL_AFTER_SALES_ACTION_ITEMS_PRODUCT_VARIANT_ID=0
NON_NULL_PRODUCT_WARRANTIES_PRODUCT_VARIANT_ID=0
NON_NULL_PRODUCTS_DEFAULT_VARIANT_ID=0
PRODUCTS_VARIANTS_ENABLED=0
NON_EMPTY_ORDER_ITEMS_VARIANT_SKU_SNAPSHOT=0
NON_EMPTY_ORDER_ITEMS_VARIANT_NAME_SNAPSHOT=0
NON_EMPTY_ORDER_ITEMS_VARIANT_ATTRIBUTES_JSON=0
VARIANT_RELATED_COLUMN_COUNT=39
VARIANT_RELATED_FOREIGN_KEY_COUNT=13
VARIANT_RELATED_INDEX_COUNT=20
LIVE_DB_ZERO_VARIANT_STATE=PASS
VARIANT_SNAPSHOT_DROP_ELIGIBLE=YES
CORE_VARIANT_SCHEMA_DROP_ELIGIBLE=YES
EXACT_SCHEMA_TOPOLOGY=PASS_39_COLUMNS_13_FKS_20_INDEXES

============================================================
3. BACKUP 15 CONTRACT FILES + BUILD TEMP REPLACEMENTS + FORWARD MIGRATION
============================================================
BACKUP_VERIFIED=PASS_15_OF_15
BACKUP_PATH=/home/icaffeco/backups/releases/product-variants-decommission-batch2c2c-v2-final-20260819-094329
TEMP_REPLACEMENT_HASHES=PASS_15_CONTRACT_FILES_PLUS_1_FORWARD_MIGRATION
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/bin/catalog-settings-integrity-hotfix-smoke.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/bin/cms-v2.1.6-smoke.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/bin/product-media-ux-smoke.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/bin/product-save-regex-hotfix-smoke.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/bin/product-variant-smoke.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/bin/stable-maintenance-smoke.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/bin/static-check.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/bin/storage-capacity-total-smoke.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/tests/Feature/CatalogDetailPageTest.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/tests/Feature/ProductVariantsWorkflowTest.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/tests/Unit/CatalogSettingsIntegrityHotfixContractTest.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/tests/Unit/DirectSaleMaxUnitPriceContractTest.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/tests/Unit/ProductSaveRegexHotfixContractTest.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/tests/Unit/ProductVariantsMigrationContractTest.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/tests/Unit/ProductVariantsUiContractTest.php
No syntax errors detected in /tmp/ald1n-variants-2c2c-v2.Oh6xPZ/new/database/migrations/2026_08_19_091800_decommission_product_variants.php
TEMP_PHP_SYNTAX=PASS_16_PHP_FILES
TEMP_MANAGED_WHITESPACE_GUARD=PASS_16_OF_16
TEMP_STATIC_TEST_DECOMMISSION_CONTRACT=PASS_NO_POSITIVE_RETIRED_FILE_READS

============================================================
4. INSTALL 15 CONTRACT REPLACEMENTS + FORWARD MIGRATION
============================================================
SOURCE_WRITES=15_STATIC_TEST_CONTRACT_REPLACEMENTS_PLUS_1_FORWARD_MIGRATION
INSTALLED_HASHES=PASS_16_OF_16

============================================================
5. EXECUTE ONLY THE FORWARD DECOMMISSION MIGRATION
============================================================

   INFO  Running migrations.  

  2026_08_19_091800_decommission_product_variants .................................................................................... 398.28ms DONE

FORWARD_MIGRATION=PASS_APPLIED_ONLY_DECOMMISSION_PATH

============================================================
6. POST-DDL ZERO-SCHEMA CERTIFICATION
============================================================
POST_PURGE_VARIANT_RELATED_COLUMN_COUNT=0
POST_PURGE_VARIANT_FOREIGN_KEY_COUNT=0
POST_PURGE_NAMED_INDEX_ROW_COUNT=0
DECOMMISSION_MIGRATION_RECORD_COUNT=1
VARIANT_SCHEMA_PURGE=PASS_ZERO_TABLES_ZERO_COLUMNS_ZERO_FKS_ZERO_INDEXES

============================================================
7. CMS RUNTIME + ROUTES + OPENAPI PRODUCT-ONLY FINAL RECERTIFICATION
============================================================
CMS_RUNTIME_PRODUCT_ONLY_CONTRACT=PASS_ZERO_VARIANT_SIGNALS_APP_CONFIG_RESOURCES
VARIANT_RUNTIME_ROUTE_SIGNAL_COUNT=0
VARIANT_RUNTIME_ROUTES=PASS_ZERO
OPENAPI_HASHES=PASS_EXACTLY_UNCHANGED_3_COPIES
OPENAPI_BROAD_VARIANT_TEXT_SIGNAL_COUNT_AFTER_CANONICAL=1
OPENAPI_FORBIDDEN_PRODUCT_VARIANT_CONTRACT_SIGNAL_COUNT_AFTER_CANONICAL=0
OPENAPI_BROAD_VARIANT_TEXT_SIGNAL_COUNT_AFTER_CMS=1
OPENAPI_FORBIDDEN_PRODUCT_VARIANT_CONTRACT_SIGNAL_COUNT_AFTER_CMS=0
OPENAPI_BROAD_VARIANT_TEXT_SIGNAL_COUNT_AFTER_MOBILE=1
OPENAPI_FORBIDDEN_PRODUCT_VARIANT_CONTRACT_SIGNAL_COUNT_AFTER_MOBILE=0
OPENAPI_BROAD_VARIANT_TEXT_SIGNAL_COUNT_AFTER=3
OPENAPI_FORBIDDEN_PRODUCT_VARIANT_CONTRACT_SIGNAL_COUNT_AFTER=0
OPENAPI_V1_FALSE_POSITIVE_GUARD=PASS_BROAD_VARIANT_TEXT_SEPARATED_FROM_PRODUCT_VARIANT_PUBLIC_CONTRACT
OPENAPI_PUBLIC_VARIANT_CONTRACT=PASS_ZERO_FORBIDDEN_SIGNALS_PARITY_3_COPIES

============================================================
8. READ-ONLY POST-SCHEMA RUNTIME MODEL + CONTAINER PROBE
============================================================
POST_SCHEMA_READ_ONLY_RUNTIME_PROBE=PASS_23_SERVICES_5_MODEL_QUERIES

============================================================
9. DECOMMISSION SMOKES + STATIC TEST CONTRACTS + FULL CMS STATIC CHECK
============================================================
PASS Istorijska beta7.20 migracija ostaje sačuvana
PASS Postoji tačno jedna forward decommission migracija
PASS Decommission migracija ima idempotentni schema restore za rollback
PASS Retired feature fajl ne postoji: app/Http/Controllers/Admin/ProductVariantController.php
PASS Retired feature fajl ne postoji: app/Http/Requests/ProductVariantRequest.php
PASS Retired feature fajl ne postoji: app/Services/ProductVariantService.php
PASS Retired feature fajl ne postoji: app/Models/ProductVariant.php
PASS Retired feature fajl ne postoji: app/Models/ProductVariantSpecValue.php
PASS Retired feature fajl ne postoji: app/Console/Commands/ProductVariantsDoctorCommand.php
PASS Retired feature fajl ne postoji: resources/views/admin/products/variants.blade.php
PASS Retired feature fajl ne postoji: resources/views/admin/products/partials/variant-fields.blade.php
PASS Aktivni CMS runtime nema Product Variants signal
PASS Porudžbine su product-only
PASS Inventory je product-only
PASS Katalog je product-only
Product Variants Decommission smoke: 15/15 uspešno.
PASS Aktuelna verzija zadržava catalog-integrity hotfix
PASS Aktivni tip bez kategorije dobija postojecu ili novu sistemsku kategoriju
PASS Automatska kategorija nasledjuje relevantan selected-group pristup
PASS Artikli se automatski svode na kategoriju svog tipa
PASS UI vise ne zahteva rucni izbor automatske kategorije
PASS Trajno brisanje polja eksplicitno uklanja sve product-only povezane reference
PASS Brisanje polja cisti roditeljske korelacije i sablone naziva
PASS Controller koristi lifecycle servis umesto oslanjanja samo na FK cascade
PASS Repair detektuje i uklanja orphan i product-only polja koja vise ne pripadaju tipu
PASS Product request prihvata samo aktivna polja trenutnog tipa
PASS Product service filtrira stare ID vrednosti pre cuvanja
PASS DB problem pojedinacne specifikacije vraca validacionu poruku umesto Error 500
PASS Product Variants lifecycle je dekomisioniran bez dormant request/service fajlova
PASS Sabloni naziva uvek ucitavaju samo aktivna polja
PASS Hotfix migracija je idempotentna data-integrity popravka
PASS Catalog settings doctor automatski popravlja obe klase problema
Catalog Settings Integrity Hotfix smoke: 16/16 uspesno.
PASS Aktuelna verzija zadržava v2.1.6 ugovor
PASS Migracija 000038 ostaje prisutna
PASS Migracija kreira indeks aktivnog kataloga
PASS Migracija kreira indekse vlasništva i tipova
PASS Migracija kreira indekse galerija i varijanti
PASS Migracija je idempotentna prema imenima indeksa
PASS Data Quality model postoji
PASS Data Quality servis proverava identitet i kompletnost
PASS Data Quality servis proverava slike u product-only katalogu
PASS Data Quality servis proverava specifikacije i korisnike
PASS Bezbedna popravka ne briše artikle
PASS Bezbedna popravka normalizuje slike bez retired variant grane
PASS Kompletnost se može preračunati bez deaktiviranja
PASS Katalog šifarnici koriste cache
PASS Promene šifarnika brišu katalog cache
PASS Role i permission provere se keširaju po requestu
PASS Katalog ima filter kvaliteta podataka
PASS Catalog query primenjuje quality filtere samo za upravljanje
PASS Data Quality Center controller ima pregled repair i export
PASS Data Quality rute su zaštićene catalog.audit dozvolom
PASS Navigacija sadrži Data Quality Center
PASS Data Quality prikaz ima score probleme i repair
PASS Data Quality prikaz koristi postojeće ikonice
PASS Data Quality CSS ima desktop i mobilni raspored
PASS Performance doctor proverava sve ciljane indekse
PASS Performance doctor meri reprezentativne upite
PASS Dashboard kešira schema metadata tokom requesta
PASS v2.1.6 doctor podržava render repair i strict
PASS Stable release check uključuje v2.1.6
PASS Composer ima v2.1.6 smoke i doctor
PASS Upgrade dokument zahteva migrate doctor i Stable proveru
CMS v2.1.6 smoke: 31/31 uspesno.
PASS Verzija sadrži Product Media UX osnovu
PASS Puna rezolucija ima autentifikovanu download rutu
PASS Download proverava pristup katalogu ili vlasništvo
PASS Download štiti public i legacy putanje
PASS Model slike daje download URL i cache-busted prikaz
PASS Catalog access servis ostaje vlasnički ograničen
PASS Rotacija podržava Imagick i GD
PASS Rotacija koristi privremeni fajl i legacy copy-on-write
PASS Rotacija osvežava hash i veličinu fajla
PASS Glavna slika je uvek prva
PASS Reorder čuva sve product-only slike i odbacuje tuđe ID-jeve
PASS Image akcije imaju JSON odgovore
PASS Forma ima redosled naziv slike specifikacije
PASS Upload prikazuje izbor fotografija
PASS Upload ima procenat i progress bar
PASS Upload vraća JSON redirect za product formu
PASS Glavna slika se bira zvezdicom preko slike
PASS Drag and drop radi pointer događajima za touch i desktop
PASS Raspored se automatski čuva
PASS Rotacija je dostupna ulevo i udesno
PASS Disk polje prepoznaje SSD HDD storage i disk
PASS Specifikacije podržavaju do osam diskova
PASS Više diskova čuva tip i kapacitet uz kompatibilan prikaz
PASS Select diskovi validiraju svaku pojedinačnu vrednost
PASS Kartica kataloga ima download pune rezolucije
PASS Galerija ima download uz glavni prikaz i Zoom
PASS Product media doctor proverava formate i rute
PASS Stable release profil uključuje product media proveru
PASS v2.1.3 migracije za strukturisane diskove i izvedeni zbir su prisutne
Product Media UX smoke: 29/29 uspesno.
PASS Aktuelna verzija zadržava Product Save Regex hotfix
PASS ProductRequest koristi bezbedan SKU regex delimiter
PASS Retired ProductVariantRequest vise ne postoji
PASS Stari neispravan SKU regex je uklonjen
PASS SKU regex se izvrsava bez preg_match upozorenja
PASS SKU regex prihvata dozvoljene znakove
PASS SKU regex odbija mala slova i razmake
Product Save Regex Hotfix smoke: 7/7 uspesno.
PASS Verzija sadrži Stable Maintenance osnovu
PASS /order/new je product-only bez variant map ugovora
PASS /order/new Blade zadržava jednostavan product-only rows ugovor
PASS /order/new ima readiness marker
PASS Order create doctor postoji i renderuje kontroler kroz container
PASS Stable release profil proverava order create
PASS Jedinstveni katalog razlikuje vidljivo i izmenjivo
PASS SuperAdministrator može sve a Administrator samo svoje
PASS Administrator u jedinstvenom katalogu vidi samo svoje artikle
PASS Admin katalog URL preusmerava na jedinstveni katalog
PASS Katalog prikazuje management akcije samo po artiklu
PASS Product forma proverava vlasništvo
PASS Retired Product Variant request vise nije deo ownership povrsine
PASS Slike proveravaju vlasništvo
PASS Bulk izmena je ograničena na manageable scope
PASS Catalog ownership doctor proverava scope render i legacy redirect
PASS Stable release profil proverava vlasništvo kataloga
PASS Navigacija nema duplu listu artikala
PASS Filter paneli imaju Expand Collapse kontrolu i pamćenje
PASS Aktivni filteri ostaju vidljivi
PASS Žiro računi koriste dve kompaktne kartice po redu
PASS Dashboard katalog link vodi na jedinstveni katalog
PASS Dashboard agregira product i user KPI u jednom upitu
FAIL Backup poruka više ne pominje RC
Stable Maintenance smoke: 23/24 uspesno.
FAIL: smoke failed: bin/stable-maintenance-smoke.php

============================================================
ROLLBACK
============================================================
SCHEMA_ROLLBACK=RESTORED_EMPTY_PRE_DECOMMISSION_VARIANT_SCHEMA
ROLLBACK_SOURCE=RESTORED_15_STATIC_TEST_CONTRACT_FILES_AND_REMOVED_FORWARD_MIGRATION
ROLLBACK_DATABASE_DATA=NO_PRODUCT_VARIANT_DATA_EXISTED_BY_EXACT_PREFLIGHT
REPORT_READY_TO_UPLOAD=/home/icaffeco/ald1n-project/docs/operations/CMS-PRODUCT-VARIANTS-DECOMMISSION-BATCH2C2C-V2-FORWARD-MIGRATION-STATIC-TEST-SCHEMA-PURGE-FINAL-CERTIFICATION-20260819-094329.md
