CONCURRENCY_LOCK=ACQUIRED

============================================================
MOBILE v0.8.0 - FOUNDATION READ-ONLY AUDIT - BATCH 1 V2
============================================================
DATE=Thu Aug 20 08:13:46 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.8.0-FOUNDATION-READ-ONLY-AUDIT-BATCH1-V2-20260820-081346.md
MODE=STRICT_READ_ONLY_DISCOVERY
TARGET_1=PRODUCT_ACTIVE_STATUS_VS_STOCK_AND_STATUS_MUTATION_AUTHORITY
TARGET_2=DUPLICATE_DISK_STORAGE_FIELDS_AND_CANONICAL_MULTI_DISK_REPEATER
TARGET_3=ONE_TIME_PURCHASE_PRICE_ENTRY_AND_SUPERADMIN_INVENTORY_KPIS
TARGET_4=DEFERRED_PAYMENT_TO_EXISTING_RECEIVABLES_WORKFLOW
TARGET_5=WARRANTY_EXPIRY_NOTIFICATIONS_OWNER_ORDER_SCOPE_ONLY
PRE_V1_EPIC=UNIVERSAL_DELETE_SEPARATE_FUTURE_WORKSTREAM
APPLICATION_SOURCE_WRITES_EXPECTED=0
DATABASE_WRITES_EXPECTED=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO

============================================================
0. v0.7.0 CERTIFICATION PREREQUISITE + TOOLCHAIN
============================================================
V0_7_BATCH6_REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-FINAL-READ-ONLY-CERTIFICATION-BATCH6-20260819-232152.md
V0_7_SOURCE_RUNTIME_CERTIFICATION=PASS
V0_7_DEVICE_ACCEPTANCE=USER_CONFIRMED_PASS_OUTSIDE_SCRIPT
NODE_VERSION=v22.23.2
PHP_VERSION=8.4.24
CURRENT_APP_VERSION=0.7.0
CURRENT_PACKAGE_LOCK_VERSION=0.7.0
NPM_VERSION=10.9.8
BATCH1_V1_INCIDENT=READ_ONLY_DISCOVERY_COMPLETE_QUALITY_GATE_FAILED_NPM_NOT_IN_PATH
BATCH1_V2_FIX=USE_AUDITED_NODE22_PLUS_DIRECT_NPM10_CLI

============================================================
1. DATABASE DISCOVERY - PRODUCTS, STOCK, COST, PAYMENT, RECEIVABLES, WARRANTY, SPECS
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.ald1n-mobile-v0.8.0-foundation-audit-batch1-v2.20260820-081346.2234707/v08-foundation-db-probe.php
DB_CONNECTION=mysql
DB_DATABASE=icaffeco_lrvl
PRODUCT_ROW_COUNT=30
PRODUCT_COLUMN_COUNT=28
PRODUCT_COLUMN=id|type=bigint(20) unsigned|nullable=NO|default=NULL
PRODUCT_COLUMN=product_type_id|type=bigint(20) unsigned|nullable=YES|default=NULL
PRODUCT_COLUMN=brand_id|type=bigint(20) unsigned|nullable=YES|default=NULL
PRODUCT_COLUMN=product_line_id|type=bigint(20) unsigned|nullable=YES|default=NULL
PRODUCT_COLUMN=model_name|type=varchar(190)|nullable=YES|default=NULL
PRODUCT_COLUMN=sku|type=varchar(100)|nullable=NO|default=NULL
PRODUCT_COLUMN=name|type=varchar(190)|nullable=NO|default=NULL
PRODUCT_COLUMN=slug|type=varchar(210)|nullable=NO|default=NULL
PRODUCT_COLUMN=price_amount|type=decimal(12,2)|nullable=NO|default=NULL
PRODUCT_COLUMN=price_currency|type=enum('RSD','EUR')|nullable=NO|default='RSD'
PRODUCT_COLUMN=manual_commission_eur|type=decimal(12,2)|nullable=YES|default=NULL
PRODUCT_COLUMN=description|type=mediumtext|nullable=NO|default=NULL
PRODUCT_COLUMN=notes|type=text|nullable=YES|default=NULL
PRODUCT_COLUMN=stock_quantity|type=int(10) unsigned|nullable=NO|default=0
PRODUCT_COLUMN=low_stock_threshold|type=int(10) unsigned|nullable=NO|default=1
PRODUCT_COLUMN=status|type=enum('draft','active','inactive','archived')|nullable=NO|default='draft'
PRODUCT_COLUMN=created_by|type=bigint(20) unsigned|nullable=NO|default=NULL
PRODUCT_COLUMN=updated_by|type=bigint(20) unsigned|nullable=YES|default=NULL
PRODUCT_COLUMN=created_at|type=timestamp|nullable=YES|default=NULL
PRODUCT_COLUMN=updated_at|type=timestamp|nullable=YES|default=NULL
PRODUCT_COLUMN=deleted_at|type=datetime|nullable=YES|default=NULL
PRODUCT_COLUMN=legacy_checksum|type=char(64)|nullable=YES|default=NULL
PRODUCT_COLUMN=legacy_synced_at|type=datetime|nullable=YES|default=NULL
PRODUCT_COLUMN=locally_modified_at|type=datetime|nullable=YES|default=NULL
PRODUCT_COLUMN=completeness_percent|type=tinyint(3) unsigned|nullable=NO|default=0
PRODUCT_COLUMN=name_is_manual|type=tinyint(1)|nullable=NO|default=1
PRODUCT_COLUMN=source_product_id|type=bigint(20) unsigned|nullable=YES|default=NULL
PRODUCT_COLUMN=purchase_price_rsd|type=decimal(14,2)|nullable=YES|default=NULL
PRODUCT_STOCK_COLUMN=stock_quantity
PRODUCT_ACTIVE_BOOLEAN_COLUMN=NONE
PRODUCT_STATUS_COLUMN=status
PRODUCTS_WITH_POSITIVE_STOCK=24
POSITIVE_STOCK_STATUS=status_value=active|row_count=21
POSITIVE_STOCK_STATUS=status_value=inactive|row_count=3
POSITIVE_STOCK_PRODUCT_SAMPLE=id=2|sku=HP-630-G10|name=HP EliteBook 630 G10 Intel Core i5 1335U 16GB 256GB|stock_quantity=2|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=4|sku=SSD-256GB-M2|name=Micron 256GB M.2 SATA|stock_quantity=18|status=inactive
POSITIVE_STOCK_PRODUCT_SAMPLE=id=5|sku=T490S-TOUCHSCREEN|name=ThinkPad T490s i5-8265U TouchScreen|stock_quantity=1|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=7|sku=DELL-LATITUDE-5440|name=Dell Latitude 5440|stock_quantity=1|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=9|sku=HP-ELITEBOOK-845-G8-RYZEN-5-PRO-16-0000GB-256-LAPTOP|name=HP EliteBook 845 G8|stock_quantity=1|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=10|sku=DELL-LATITUDE-3540-I5-1335U-16GB-256GB-INTEL-CORE-16-0000GB-256-LAPTOP|name=Dell Latitude 3540 i5-1335U 16GB 256GB|stock_quantity=1|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=11|sku=DELL-LATITUDE-INTEL-CORE-I7-1185G7-16GB-512GB-LAPTOP|name=Dell Latitude 7320 Intel Core i7-1185G7 I7-1185G7 16GB 512GB|stock_quantity=1|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=12|sku=DELL-LATITUDE-7410-INTEL-CORE-I7-10610U-16GB-256GB-LAPTOP|name=Dell Latitude 7410 Intel Core  I7-10610U 16GB 256GB|stock_quantity=1|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=13|sku=DELL-LATITUDE-I7-8665U-16GB-256GB-TOUCHSCREEN-INTEL-CORE-7-LAPTOP|name=Dell Latitude 7400 I7-8665U 16GB 256GB|stock_quantity=1|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=14|sku=7950X-16GB-GAINWARD-NVIDIA-GEFORCE-RTX-3090-AMD-RYZEN-9-POLOVNO-DESKTOP-RACUNAR|name=Gamer/WorkStation Ryzen 9 7950X  + DDR5 32GB RAM  + 1TB SSD|stock_quantity=1|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=15|sku=RYZEN-7-5700-16GB-512GB-GIGABYTE-NVIDIA-RTX-3060TI-AMD-POLOVNO-DESKTOP-RACUNAR|name=GAMER Ryzen 7 5700 16GB 512GB + 2TB HDD|stock_quantity=3|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=17|sku=8GB-GAINWARD-NVIDIA-GEFORCE-RTX-3060TI-POLOVNO-GRAFICKA-KARTA|name=8GB|stock_quantity=2|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=18|sku=LENOVO-THINKPAD-16GB-256GB-INTEL-CORE-I5-8265U-LAPTOP|name=Lenovo ThinkPad T480 i5-8265U 16GB 256GB Intel|stock_quantity=1|status=inactive
POSITIVE_STOCK_PRODUCT_SAMPLE=id=21|sku=HP-DESKTOP-RACUNAR|name=HP EliteDesk 800 G2 i5-6500T|stock_quantity=2|status=inactive
POSITIVE_STOCK_PRODUCT_SAMPLE=id=22|sku=HP-ELITEBOOK-INTEL-CORE-I5-1145G7-16GB-256GB-LAPTOP|name=HP EliteBook 830 G8 Intel Core i5 1145G7 16GB 256GB|stock_quantity=1|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=24|sku=LENOVO-THINKPAD-INTEL-CORE-I5-7200U-8GB-256GB-POLOVNO-LAPTOP|name=Lenovo ThinkPad Intel Core i5 7200U 8GB 256GB|stock_quantity=1|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=25|sku=DELL-LATITUDE-INTEL-CORE-I5-1145G7-16GB-256GB-KAO-NOVO-LAPTOP|name=Dell Latitude 5420 Intel Core i5 1145G7 16GB 256GB|stock_quantity=2|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=26|sku=GAMER-INTEL-CORE-I5-8400-16GB-NVME-SSD-256-GB-HDD-2048-ASUS-TUF-GAMING-NVIDIA-GTX-1660-SUPER-KAO-NOV|name=Gamer Intel Core i5 8400 16GB NVMe SSD 256 GB + HDD 2048 GB|stock_quantity=4|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=30|sku=DELL-LATITUDE-E5570-INTEL-CORE-I5-6300U-16GB-256GB-POLOVNO-LAPTOP|name=Dell Latitude E5570 Intel Core i5 I5-6300U 16GB 256GB|stock_quantity=5|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=31|sku=DELL-LATITUDE-5410-INTEL-CORE-I5-10210U-256GB-POLOVNO-DDR4-LAPTOP|name=Dell Latitude 5410 Intel Core i5 10210U 256GB|stock_quantity=2|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=34|sku=SAMSUNG-000032|name=Samsung PM991 256GB NVMe Gen3|stock_quantity=6|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=35|sku=SSD-I-HDD-000033|name=Micron & SanDisk SATA 256GB SSD|stock_quantity=32|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=36|sku=SAMSUNG-000034|name=Samsung 8GB DDR3 PC3L Desktop|stock_quantity=16|status=active
POSITIVE_STOCK_PRODUCT_SAMPLE=id=37|sku=MICRON-000035|name=Micron 4GB DDR3 PC3L|stock_quantity=35|status=active
PRODUCT_COST_COLUMN_CANDIDATES=purchase_price_rsd
PRODUCT_COST_MISSING_OR_ZERO=purchase_price_rsd:9
ORDER_PAYMENT_METHOD_COLUMN=payment_method
ORDER_PAYMENT_METHOD_VALUE=payment_method=cash|row_count=5
ORDER_PAYMENT_METHOD_VALUE=payment_method=cash_on_delivery|row_count=3
ORDER_PAYMENT_METHOD_VALUE=payment_method=bank_transfer|row_count=3
RELATED_TABLE=product_spec_values
RELATED_TABLE=product_warranties
RELATED_TABLE=receivable_cases
RELATED_TABLE=receivable_contacts
RELATED_TABLE=receivable_installments
RELATED_TABLE=specification_fields
RELATED_TABLE=specification_options
RELATED_TABLE=specification_option_dependencies
RELATED_TABLE=warranty_maintenance_records
RELATED_TABLE=warranty_rules
WARRANTY_TABLE=product_warranties|rows=12
WARRANTY_COLUMNS=product_warranties:id,warranty_number,order_id,order_item_id,product_id,user_id,warranty_rule_id,status,starts_at,expires_at,duration_months,duration_days,maintenance_interval_months,last_maintenance_at,next_maintenance_at,customer_name_snapshot,customer_address_snapshot,customer_city_snapshot,customer_postal_code_snapshot,customer_phone_snapshot,product_sku_snapshot,product_name_snapshot,quantity,serial_numbers_json,terms_snapshot,voided_at,voided_by,void_reason,created_by,created_at,updated_at
WARRANTY_TABLE=warranty_maintenance_records|rows=0
WARRANTY_COLUMNS=warranty_maintenance_records:id,product_warranty_id,status,due_at,scheduled_at,completed_at,completed_by,service_reference,result,notes,created_at,updated_at
SPEC_STORAGE_MATCH_TABLE=product_spec_values|rows_sampled=28
SPEC_STORAGE_MATCH=product_id=1|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-04 10:36:23|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=2|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-08 20:59:40|updated_at=2026-08-08 20:59:40|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=3|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-04 10:35:20|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=4|field_id=10|value_text=SATA SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-18 08:53:37|updated_at=2026-08-18 08:53:37|value_detail=NULL|value_json=[{"type":"SATA SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=4|field_id=11|value_text=SATA III 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-18 08:53:37|updated_at=2026-08-18 08:53:37|value_detail=NULL|value_json=[{"type":"SATA III","capacity_gb":"256"}]
SPEC_STORAGE_MATCH=product_id=5|field_id=10|value_text=NVMe SSD 512 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-04 10:36:47|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":512}]
SPEC_STORAGE_MATCH=product_id=6|field_id=10|value_text=NVMe SSD 1024 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-04 10:37:07|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":1024}]
SPEC_STORAGE_MATCH=product_id=7|field_id=10|value_text=NVMe SSD 512 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-04 10:37:23|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":512}]
SPEC_STORAGE_MATCH=product_id=9|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-04 10:37:42|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=10|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-03 20:08:56|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=11|field_id=10|value_text=NVMe SSD 512 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-03 23:49:37|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":512}]
SPEC_STORAGE_MATCH=product_id=12|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-04 08:17:23|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=13|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-03 23:52:17|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=14|field_id=10|value_text=NVMe SSD 1024 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-04 00:12:59|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":1024}]
SPEC_STORAGE_MATCH=product_id=15|field_id=10|value_text=NVMe SSD 512 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-04 00:17:57|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":512}]
SPEC_STORAGE_MATCH=product_id=16|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-11 17:34:55|updated_at=2026-08-11 17:34:55|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=18|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-04 08:54:47|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=21|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-16 18:24:00|updated_at=2026-08-16 18:24:00|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=22|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-11 00:16:50|updated_at=2026-08-11 00:16:50|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=23|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-04 23:51:59|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=24|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-04 23:56:18|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=25|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-16 18:22:00|updated_at=2026-08-16 18:22:00|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=26|field_id=10|value_text=NVMe SSD 256 GB + HDD 2048 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-05 11:16:51|updated_at=2026-08-06 08:15:01|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256},{"type":"HDD","capacity_gb":2048}]
SPEC_STORAGE_MATCH=product_id=30|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-19 22:57:06|updated_at=2026-08-19 22:57:06|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=31|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-17 09:16:53|updated_at=2026-08-17 09:16:53|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=34|field_id=10|value_text=NVMe SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-19 21:28:42|updated_at=2026-08-19 21:28:42|value_detail=NULL|value_json=[{"type":"NVMe SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH=product_id=34|field_id=11|value_text=PCIe 3.0 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-19 21:28:42|updated_at=2026-08-19 21:28:42|value_detail=NULL|value_json=[{"type":"PCIe 3.0","capacity_gb":"256"}]
SPEC_STORAGE_MATCH=product_id=35|field_id=10|value_text=SATA SSD 256 GB|value_number=NULL|value_boolean=NULL|created_at=2026-08-19 21:19:02|updated_at=2026-08-19 21:19:02|value_detail=NULL|value_json=[{"type":"SATA SSD","capacity_gb":256}]
SPEC_STORAGE_MATCH_TABLE=specification_fields|rows_sampled=5
SPEC_STORAGE_MATCH=id=6|name=RAM kapacitet|slug=ram-kapacitet|data_type=integer|filter_type=range|unit=GB|placeholder=16|help_text=Ukupan instalirani RAM.|options_text=NULL|min_value=1.0000|max_value=4096.0000|status=active|sort_order=60|created_by=NULL|updated_by=NULL|created_at=2026-07-15 10:01:26|updated_at=2026-07-15 10:01:26|parent_field_id=NULL|detail_input_enabled=0|detail_label=NULL|detail_placeholder=NULL|storage_role=NULL|storage_source_field_id=NULL
SPEC_STORAGE_MATCH=id=9|name=Ukupan kapacitet diskova|slug=kapacitet-diska|data_type=integer|filter_type=range|unit=GB|placeholder=512|help_text=Automatski zbir kapaciteta svih unetih diskova. Polje se ne unosi ručno.|options_text=NULL|min_value=1.0000|max_value=100000.0000|status=active|sort_order=100|created_by=NULL|updated_by=NULL|created_at=2026-07-15 10:01:26|updated_at=2026-08-04 08:13:23|parent_field_id=NULL|detail_input_enabled=0|detail_label=NULL|detail_placeholder=NULL|storage_role=total_capacity|storage_source_field_id=10
SPEC_STORAGE_MATCH=id=10|name=Tip diska|slug=tip-diska|data_type=select|filter_type=select|unit=NULL|placeholder=Izaberi tip|help_text=Vrsta glavnog uredjaja za skladistenje.|options_text=HDD SSD SATA SSD NVMe SSD eMMC|min_value=NULL|max_value=NULL|status=active|sort_order=100|created_by=NULL|updated_by=NULL|created_at=2026-07-15 10:01:26|updated_at=2026-07-15 10:01:26|parent_field_id=NULL|detail_input_enabled=0|detail_label=NULL|detail_placeholder=NULL|storage_role=components|storage_source_field_id=NULL
SPEC_STORAGE_MATCH=id=11|name=Interfejs diska|slug=interfejs-diska|data_type=select|filter_type=select|unit=NULL|placeholder=Izaberi interfejs|help_text=Interfejs ili generacija magistrale diska.|options_text=SATA II SATA III PCIe 3.0 PCIe 4.0 PCIe 5.0 USB|min_value=NULL|max_value=NULL|status=active|sort_order=110|created_by=NULL|updated_by=NULL|created_at=2026-07-15 10:01:26|updated_at=2026-07-15 10:01:26|parent_field_id=NULL|detail_input_enabled=0|detail_label=NULL|detail_placeholder=NULL|storage_role=components|storage_source_field_id=NULL
SPEC_STORAGE_MATCH=id=12|name=Model grafike|slug=model-grafike|data_type=text|filter_type=text|unit=NULL|placeholder=Intel® Iris® Xe|help_text=Naziv integrisane ili diskretne grafike.|options_text=NULL|min_value=NULL|max_value=NULL|status=active|sort_order=120|created_by=NULL|updated_by=NULL|created_at=2026-07-15 10:01:26|updated_at=2026-07-31 09:15:31|parent_field_id=NULL|detail_input_enabled=0|detail_label=NULL|detail_placeholder=NULL|storage_role=NULL|storage_source_field_id=NULL
SPEC_STORAGE_MATCH_TABLE=specification_options|rows_sampled=4
SPEC_STORAGE_MATCH=id=18|field_id=10|label=HDD|value=HDD|status=active|sort_order=10|created_at=2026-07-31 09:53:57|updated_at=2026-07-31 09:53:57
SPEC_STORAGE_MATCH=id=19|field_id=10|label=SSD|value=SSD|status=active|sort_order=20|created_at=2026-07-31 09:53:57|updated_at=2026-07-31 09:53:57
SPEC_STORAGE_MATCH=id=20|field_id=10|label=SATA SSD|value=SATA SSD|status=active|sort_order=30|created_at=2026-07-31 09:53:57|updated_at=2026-07-31 09:53:57
SPEC_STORAGE_MATCH=id=21|field_id=10|label=NVMe SSD|value=NVMe SSD|status=active|sort_order=40|created_at=2026-07-31 09:53:57|updated_at=2026-07-31 09:53:57
DATABASE_WRITES_DURING_AUDIT=0_BY_EXECUTION_CONTRACT
V08_FOUNDATION_DB_PROBE_SENTINEL=PASS

============================================================
2. PRODUCT STATUS / COMPLETENESS / INVENTORY AUTHORITY SOURCE MAP
============================================================
--- CMS PRODUCT STATUS AND COMPLETENESS HITS ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:49:                    'status' => 'draft',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:116:                    'status' => 'draft',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerActivationService.php:72:    public function activate(string $plainToken, string $password): ?User
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerActivationService.php:103:                'portal_activated_at' => now(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:151:            'inventory_state' => 'reserved',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:152:            WarrantyMaintenanceRecord::query()->where('product_warranty_id', $locked->id)->whereIn('status', ['due', 'scheduled'])->update(['status' => 'cancelled', 'updated_at' => now()]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:240:            $productRule = WarrantyRule::query()->where('is_active', true)->where('scope_type', 'product')->where('product_id', $product->id)->orderByDesc('priority')->orderByDesc('id')->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:245:                $categoryRule = WarrantyRule::query()->where('is_active', true)->where('scope_type', 'category')->whereIn('category_id', $categoryIds)->orderByDesc('priority')->orderByDesc('id')->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:250:        return WarrantyRule::query()->where('is_active', true)->where('scope_type', 'global')->orderByDesc('priority')->orderByDesc('id')->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductCompletenessService.php:10:final class ProductCompletenessService
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductCompletenessService.php:18:            'type.fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductCompletenessService.php:31:        $result = $this->templates->completeness(
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductCompletenessService.php:38:        $updates = ['completeness_percent' => $result['percent']];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductCompletenessService.php:40:        $minimum = max(0, min(100, (int) ($product->type?->minimum_completeness_percent ?? 0)));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductCompletenessService.php:41:        if ($enforceMinimum && $product->status === 'active' && $result['percent'] < $minimum) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductCompletenessService.php:42:            $updates['status'] = 'draft';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:296:                'inventory_state' => $this->text($order, 'inventory_state', '—'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:749:            default => 'draft',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GoogleAuthService.php:105:        $autoActivate = (bool) config('mobile.google_auth.auto_activate_registration', false);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GoogleAuthService.php:124:            'portal_activated_at' => $autoActivate ? $now : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GoogleAuthService.php:134:            || !(bool) config('mobile.google_auth.auto_activate_registration', false)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GoogleAuthService.php:143:            'portal_activated_at' => $user->portal_activated_at ?? $now,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:37:            'active_warranties' => $this->safeCount(fn () => ProductWarranty::query()->where('user_id', $user->id)->where('status', 'active')->whereDate('expires_at', '>=', today())->count(), 'product_warranties'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:408:                if (Schema::hasColumn('warranty_rules', 'is_active')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:409:                    $updates['is_active'] = false;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTypeCategoryService.php:16:     * @return array{examined:int,matched:int,reactivated:int,created:int,products_synced:int}
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTypeCategoryService.php:23:            'reactivated' => 0,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTypeCategoryService.php:44:     * @return array{category:Category,action:'matched'|'reactivated'|'created',products_synced:int}
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTypeCategoryService.php:81:                $action = 'reactivated';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:180:        if ($product->deleted_at === null || (string) $product->status !== 'archived') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:20:        private readonly ProductCompletenessService $completeness,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:81:            'completeness_recalculated' => 0,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:99:        if (Schema::hasTable('products') && Schema::hasColumn('products', 'completeness_percent')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:100:            $completeness = $this->completeness->recalculateAll(false);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:101:            $summary['completeness_recalculated'] = (int) $completeness['examined'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:102:            $summary['products_downgraded'] = (int) $completeness['downgraded'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:192:        $active = DB::table('products')->whereNull('deleted_at')->where('status', 'active');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:229:        if (Schema::hasTable('product_types') && Schema::hasColumn('products', 'completeness_percent') && Schema::hasColumn('product_types', 'minimum_completeness_percent')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:233:                ->where('products.status', 'active')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:234:                ->whereColumn('products.completeness_percent', '<', 'product_types.minimum_completeness_percent');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:237:                'products.id', 'products.sku', 'products.name', 'products.completeness_percent',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:238:                'product_types.minimum_completeness_percent',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:349:            $metrics['products_active'] = (int) DB::table('products')->whereNull('deleted_at')->where('status', 'active')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:120:                after: ['status' => $locked->status, 'inventory_state' => $locked->inventory_state],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:579:        if ($order->inventory_state === 'returned' || $order->inventory_returned_at !== null) return;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:580:        if ($order->inventory_state !== 'reserved') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:608:        $order->update(['inventory_state' => 'returned', 'inventory_returned_at' => now(), 'updated_by' => $actor->id]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyAdminService.php:102:        $data['is_active'] = (bool) ($data['is_active'] ?? false);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTemplateService.php:21:        if ($type === null) return ['specs' => [], 'details' => [], 'status' => 'draft'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTemplateService.php:22:        $type->load(['fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order')]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTemplateService.php:31:        $status = in_array($type->default_product_status, ['draft', 'active', 'inactive'], true) ? $type->default_product_status : 'draft';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTemplateService.php:36:    public function completeness(?ProductType $type, array $data, array $specs, array $details = []): array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTemplateService.php:39:        $type->load(['fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order')]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTemplateService.php:62:            $weight = max(1, (int) ($field->pivot?->completeness_weight ?? 1));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTemplateService.php:83:        $type->load(['fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order')]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTemplateService.php:129:        $type->load(['fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order')]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CourierDirectoryService.php:53:            $isActive = $isDefault ? true : (bool) ($data['is_active'] ?? false);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CourierDirectoryService.php:59:                'is_active' => $isActive,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CourierDirectoryService.php:79:            $isActive = $isDefault ? true : (bool) ($data['is_active'] ?? false);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CourierDirectoryService.php:80:            if (!$isActive && (int) CourierService::query()->where('is_active', true)->where('id', '<>', $locked->id)->count() === 0) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CourierDirectoryService.php:81:                throw ValidationException::withMessages(['is_active' => 'Najmanje jedna kurirska služba mora ostati aktivna.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CourierDirectoryService.php:88:                'is_active' => $isActive,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CourierDirectoryService.php:102:        if (CourierService::query()->where('is_active', true)->where('is_default', true)->exists()) return;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CourierDirectoryService.php:103:        $first = CourierService::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->lockForUpdate()->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:118:                'draft', 'inactive' => $query->where('status', $status)->whereNull('deleted_at'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:144:                'incomplete' => $query->where('completeness_percent', '<', 100),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReportScheduleService.php:34:        $schedule->next_run_at = $schedule->is_active ? $this->nextRunAt($schedule) : null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReportScheduleService.php:42:            'is_active' => !$schedule->is_active,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReportScheduleService.php:43:            'next_run_at' => !$schedule->is_active ? $this->nextRunAt($schedule) : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReportScheduleService.php:53:        $schedules = ReportSchedule::query()->where('is_active', true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReportScheduleService.php:59:                $locked = ReportSchedule::query()->whereKey($schedule->id)->where('is_active', true)->lockForUpdate()->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReportScheduleService.php:136:            'is_active' => (bool) ($data['is_active'] ?? true),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAnnouncementService.php:34:        if ($product->status !== 'active' || $product->deleted_at !== null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:89:            'inventory_state' => 'reserved',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:210:            after: ['status' => $order->status, 'subtotal_rsd' => $order->subtotal_rsd, 'inventory_state' => $order->inventory_state],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:270:        $account = BankAccount::query()->whereKey((int) ($data['bank_account_id'] ?? 0))->where('is_active', true)->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:42:            $completeness = $this->templates->completeness($type, $data, $specs, $specDetails);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:43:            $data['completeness_percent'] = $completeness['percent'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:71:            $this->audit->log('product.created', 'Kreiran artikal '.$product->sku, $product, after: $this->snapshot($product), metadata: ['completeness' => $completeness]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:91:            $completeness = $this->templates->completeness($type, $data, $specs, $specDetails);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:92:            $data['completeness_percent'] = $completeness['percent'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:124:            $this->audit->log('product.updated', 'Izmenjen artikal '.$locked->sku, $locked, $before, $this->snapshot($locked), ['completeness' => $completeness]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:165:                'status' => 'draft',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:184:            $completeness = $this->templates->completeness($this->typeFromData($data), $data + ['category_ids' => $categoryIds], $specs, $details);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:185:            $data['completeness_percent'] = $completeness['percent'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:204:                'type.fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:227:        $product->update(['status' => 'archived', 'deleted_at' => now(), 'updated_by' => $user->id, 'locally_modified_at' => now()]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:234:        $product->update(['status' => 'inactive', 'deleted_at' => null, 'updated_by' => $user->id, 'locally_modified_at' => now()]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:278:            'fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:426:            'completeness_percent','name_is_manual','source_product_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductBulkService.php:19:        private readonly ProductCompletenessService $completeness,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductBulkService.php:107:                    $this->completeness->recalculate($locked);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductBulkService.php:247:        return $product->fresh()?->only(['id','sku','name','product_type_id','brand_id','product_line_id','price_amount','price_currency','status','completeness_percent']) ?? [];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/FieldWorkOrderPlanner.php:131:        if (!$team instanceof FieldServiceTeam || !$team->is_active) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:38:                'is_active' => (bool) ($data['is_active'] ?? true),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:67:            if (!$part->is_active) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:287:                'status' => 'draft',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:298:                if (!$part->is_active) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:328:                'draft' => ['submitted', 'cancelled'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:335:            if (in_array($target, ['submitted', 'ordered'], true) && (!$locked->supplier_id || !$locked->supplier?->is_active)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:364:            if (array_key_exists('is_active', $data)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:365:                $locked->setAttribute('is_active', (bool) $data['is_active']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:382:            if (array_key_exists('is_active', $data)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:383:                $supplier->setAttribute('is_active', (bool) $data['is_active']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:384:            } elseif ($supplier->getAttribute('is_active') === null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:385:                $supplier->setAttribute('is_active', true);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:406:            if (array_key_exists('is_active', $data)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:407:                $locked->setAttribute('is_active', (bool) $data['is_active']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:15:    public function __construct(private readonly ProductCompletenessService $completeness) {}
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:76:            $result = $this->completeness->recalculateType($type);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:87:            $result = $this->completeness->recalculate($product);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:125:                $status = (string) ($product->status ?? '');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:135:                        $this->productStatusLabel($status),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:596:    private function productStatusLabel(string $status): string
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:600:            'draft' => 'Nacrt',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:434:                ->where('is_active', true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OperationalAlert.php:13:        'alert_key', 'type', 'severity', 'status', 'order_id', 'product_id', 'user_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/BankAccount.php:15:        'payment_code', 'is_active', 'created_by', 'updated_by',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/BankAccount.php:20:        return ['is_active' => 'boolean'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReportSchedule.php:15:        'recipients_json', 'filters_json', 'formats_json', 'is_active', 'next_run_at',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReportSchedule.php:23:            'is_active' => 'boolean', 'weekday' => 'integer', 'month_day' => 'integer',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/CourierService.php:15:        'name', 'slug', 'tracking_url', 'is_active', 'is_default', 'sort_order', 'created_by', 'updated_by',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/CourierService.php:21:            'is_active' => 'boolean',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/CourierService.php:44:        return $query->where('is_active', true);
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductType.php:14:    protected $fillable = ['category_id', 'name', 'slug', 'description', 'status', 'sort_order', 'name_template', 'auto_name_enabled', 'minimum_completeness_percent', 'default_product_status', 'required_core_fields_json'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductType.php:19:    protected function casts(): array { return ['auto_name_enabled' => 'boolean', 'minimum_completeness_percent' => 'integer', 'required_core_fields_json' => 'array']; }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductType.php:20:    public function fields(): BelongsToMany { return $this->belongsToMany(SpecificationField::class, 'product_type_fields', 'product_type_id', 'field_id')->withPivot(['is_required', 'is_filterable', 'show_in_summary', 'sort_order', 'default_value', 'default_detail', 'completeness_weight', 'include_in_name']); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/SpecificationField.php:86:        return $this->belongsToMany(ProductType::class, 'product_type_fields', 'field_id', 'product_type_id')->withPivot(['is_required', 'is_filterable', 'show_in_summary', 'sort_order', 'default_value', 'default_detail', 'completeness_weight', 'include_in_name']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ServicePartSupplier.php:13:    protected $fillable = ['code', 'name', 'contact_person', 'phone', 'email', 'address', 'lead_time_days', 'is_active', 'notes', 'created_by', 'updated_by'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ServicePartSupplier.php:14:    protected function casts(): array { return ['lead_time_days' => 'integer', 'is_active' => 'boolean']; }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/FieldServiceTeam.php:15:        'service_area', 'is_active', 'notes', 'created_by', 'updated_by',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/FieldServiceTeam.php:20:        return ['is_active' => 'boolean'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/WarrantyMaintenanceRecord.php:13:        'product_warranty_id', 'status', 'due_at', 'scheduled_at', 'completed_at',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ServicePart.php:13:    protected $fillable = ['sku', 'name', 'unit', 'stock_quantity', 'reserved_quantity', 'minimum_quantity', 'average_cost_rsd', 'preferred_supplier_id', 'is_active', 'notes', 'created_by', 'updated_by'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ServicePart.php:14:    protected function casts(): array { return ['stock_quantity' => 'decimal:3', 'reserved_quantity' => 'decimal:3', 'minimum_quantity' => 'decimal:3', 'average_cost_rsd' => 'decimal:2', 'is_active' => 'boolean']; }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Order.php:16:        'inventory_state', 'inventory_reserved_at', 'inventory_returned_at', 'cancelled_at', 'cancelled_by', 'shipping_full_name', 'shipping_address', 'shipping_city',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/User.php:30:        'password_changed_at', 'email_verified_at', 'portal_activated_at',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/User.php:42:            'portal_activated_at' => 'datetime',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:20:        'legacy_synced_at', 'locally_modified_at', 'completeness_percent', 'name_is_manual', 'source_product_id',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:34:            'completeness_percent' => 'integer',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ServicePartPurchaseRequest.php:20:    public static function statusLabels(): array { return ['draft' => 'Nacrt', 'submitted' => 'Poslato dobavljaču', 'ordered' => 'Poručeno', 'received' => 'Primljeno', 'cancelled' => 'Otkazano']; }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/WarrantyRule.php:15:        'maintenance_interval_months', 'priority', 'is_active', 'terms',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/WarrantyRule.php:26:            'is_active' => 'boolean',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreFieldServiceTeamRequest.php:30:            'is_active' => ['nullable', 'boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreServicePartSupplierRequest.php:24:            'is_active' => ['nullable', 'boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/UpdateFieldServiceTeamRequest.php:31:            'is_active' => ['nullable', 'boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreReportScheduleRequest.php:32:            'is_active' => ['nullable', 'boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreReportScheduleRequest.php:51:            'is_active' => $this->boolean('is_active'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:56:            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:160:                    'fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:236:                $result = app(ProductTemplateService::class)->completeness($type, $this->all(), $specs, $details);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:237:                $minimum = max(0, min(100, (int) ($type->minimum_completeness_percent ?? 0)));
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:254:            'fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/UpdateServicePartRequest.php:24:            'is_active' => ['nullable', 'boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreServicePartRequest.php:24:            'is_active' => ['nullable', 'boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/UpdateServicePartSupplierRequest.php:25:            'is_active' => ['nullable', 'boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreWarrantyRuleRequest.php:26:            'is_active' => ['nullable', 'boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/OrderResource.php:23:            'inventory_state' => $this->inventory_state,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartController.php:33:            ->when($filter === 'inactive', static fn ($query) => $query->where('is_active', false))
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartController.php:34:            ->when($filter !== 'inactive', static fn ($query) => $query->where('is_active', true))
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartController.php:38:        $base = ServicePart::query()->where('is_active', true);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartController.php:41:            'suppliers' => ServicePartSupplier::query()->where('is_active', true)->orderBy('name')->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartController.php:56:        $service->createPart($request->user(), $request->validated() + ['is_active' => $request->boolean('is_active', true)]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartController.php:64:        $part->is_active = $request->boolean('is_active');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CourierServiceController.php:50:            'is_active' => ['required', 'boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportScheduleController.php:38:        return back()->with('status', $schedule->fresh()->is_active ? 'Raspored je aktiviran.' : 'Raspored je pauziran.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldServiceTeamController.php:29:            ->orderByDesc('is_active')->orderBy('name')->paginate(40)->withQueryString();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldServiceTeamController.php:42:            'is_active' => $request->boolean('is_active'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldServiceTeamController.php:52:        if (!$request->boolean('is_active') && $team->is_active
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldServiceTeamController.php:54:            throw ValidationException::withMessages(['is_active' => 'Ekipa ima aktivne radne naloge. Prvo ih prerasporedite ili završite.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldServiceTeamController.php:58:        $team->is_active = $request->boolean('is_active');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldServiceTeamController.php:71:        $team->forceFill(['is_active' => false, 'updated_by' => $request->user()->id])->save();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldServiceTeamController.php:72:        $audit->log('field_team.deactivated', 'Deaktivirana terenska ekipa '.$team->name, $team, $before, $team->toArray(), null, $request->user());
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:59:        return $this->formView(new Product(['status' => 'draft', 'price_currency' => 'EUR', 'stock_quantity' => 0, 'low_stock_threshold' => 1]), $templates);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:90:        $wasActive = (string) $product->status === 'active';
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:132:        return redirect()->route('admin.products.edit', $clone)->with('status', 'Artikal je kloniran. Novi SKU je '.$clone->sku.'.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:239:                'fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order')->orderBy('specification_fields.name'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:277:            'currentCompleteness' => $product->exists ? (int) $product->completeness_percent : 0,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:15:use App\Services\ProductCompletenessService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:107:        ProductCompletenessService $completeness,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:133:            $completeness->recalculateType($model);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:147:            return redirect()->route('admin.dictionary.product-type', $model)->with('status', 'Tip artikla je kreiran. Sada podesi njegove specifikacije.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:160:        ProductCompletenessService $completeness,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:189:        $recalculated = $resource === 'product-types' ? $completeness->recalculateType($model) : ['examined' => 0, 'downgraded' => 0];
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:218:        $audit->log('catalog.dictionary.deactivated', 'Deaktivirano: '.$model->name, $model, $before, $model->fresh()->toArray(), ['resource' => $resource]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:345:            'minimum_completeness_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:346:            'default_product_status' => ['nullable', Rule::in(['draft','active','inactive'])],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:356:            'field_config.*.completeness_weight' => ['nullable', 'integer', 'min:1', 'max:100'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:387:            $data['minimum_completeness_percent'] = max(0, min(100, (int) ($data['minimum_completeness_percent'] ?? 0)));
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:388:            $data['default_product_status'] = in_array(($data['default_product_status'] ?? 'draft'), ['draft','active','inactive'], true) ? $data['default_product_status'] : 'draft';
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:449:                'completeness_weight' => max(1, min(100, (int) ($row['completeness_weight'] ?? 1))),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartSupplierController.php:26:            })->orderByDesc('is_active')->orderBy('name')->paginate(50)->withQueryString();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartSupplierController.php:33:            'is_active' => $request->boolean('is_active', true),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartSupplierController.php:43:        if (!$request->boolean('is_active') && $supplier->purchaseRequests()->whereIn('status', ['submitted', 'ordered'])->exists()) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartSupplierController.php:44:            throw ValidationException::withMessages(['is_active' => 'Dobavljač ima aktivne zahteve za nabavku. Prvo ih završite ili otkažite.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartSupplierController.php:48:        $supplier->is_active = $request->boolean('is_active');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/StockAdjustmentController.php:27:            ->with('status', sprintf('Lager %s je korigovan: %d → %d.', $product->sku, $movement->quantity_before, $movement->quantity_after));
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/AfterSalesController.php:65:            'fieldTeams' => FieldServiceTeam::query()->where('is_active', true)->orderBy('name')->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldOperationsController.php:83:            'teams' => FieldServiceTeam::query()->where('is_active', true)->orderBy('name')->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldOperationsController.php:85:            'serviceParts' => Schema::hasTable('service_parts') ? ServicePart::query()->where('is_active', true)->orderBy('name')->get() : collect(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldOperationsController.php:90:                'active_teams' => FieldServiceTeam::query()->where('is_active', true)->count(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldOperationsController.php:104:            'teams' => FieldServiceTeam::query()->where('is_active', true)->orWhereKey($workOrder->field_service_team_id)->orderBy('name')->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldOperationsController.php:106:            'serviceParts' => Schema::hasTable('service_parts') ? ServicePart::query()->where('is_active', true)->orderBy('name')->get() : collect(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/AutomationController.php:28:            'alerts' => $ready ? OperationalAlert::query()->with(['order', 'product'])->where('status', 'open')->orderByRaw("CASE severity WHEN 'danger' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")->latest('last_detected_at')->limit(80)->get() : collect(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/BankAccountController.php:67:            'is_active' => ['nullable', 'boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/BankAccountController.php:88:        $data['is_active'] = $request->boolean('is_active');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductBulkController.php:39:            'status' => ['nullable', 'required_if:apply_status,1', Rule::in(['draft', 'active', 'inactive'])],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductBulkController.php:71:        $selected = $query->orderBy('name')->get(['id','sku','name','status','completeness_percent']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartPurchaseRequestController.php:32:            'parts' => ServicePart::query()->where('is_active', true)->orderBy('name')->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ServicePartPurchaseRequestController.php:33:            'suppliers' => ServicePartSupplier::query()->where('is_active', true)->orderBy('name')->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/ProductMediaController.php:20:        $canViewCatalog = $product->status === 'active'
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:359:            if (!$this->tableHasColumns('service_parts', ['id', 'is_active', 'stock_quantity', 'reserved_quantity', 'minimum_quantity', 'average_cost_rsd'])) return $fallback;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:360:            $parts = ServicePart::query()->where('is_active', true);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:363:                $openPurchases = ServicePartPurchaseRequest::query()->whereIn('status', ['draft', 'submitted', 'ordered'])->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:383:            if (!$this->tableHasColumns('product_warranties', ['id', 'user_id', 'status', 'expires_at', 'next_maintenance_at'])) return $fallback;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:436:            if (!$this->tableHasColumns('products', ['id', 'status', 'stock_quantity', 'low_stock_threshold', 'deleted_at'])) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/CatalogOptionsController.php:73:                'statuses' => ['active', 'draft', 'inactive'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ServicePartsController.php:40:            $query->where('is_active', filter_var($request->query('active'), FILTER_VALIDATE_BOOLEAN));
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ServicePartsController.php:90:            $query->where('is_active', filter_var($request->query('active'), FILTER_VALIDATE_BOOLEAN));
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ServicePartsController.php:211:            'is_active' => (bool) ($attrs['is_active'] ?? false),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ServicePartsController.php:232:            'is_active' => (bool) ($attrs['is_active'] ?? false),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ServicePartsController.php:303:        return ServicePartSupplier::query()->where('is_active', true)->orderBy('name')->limit(200)->get()
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ServicePartsController.php:310:        return ServicePart::query()->where('is_active', true)->orderBy('sku')->limit(500)->get()
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ServicePartsController.php:330:            'can_submit' => $can && $status === 'draft',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ServicePartsController.php:333:            'can_cancel' => $can && in_array($status, ['draft', 'submitted', 'ordered'], true),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReportScheduleController.php:114:            'message' => $updated->is_active
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReportScheduleController.php:188:            'is_active' => (bool) $schedule->is_active,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FieldServiceTeamController.php:21:            ->orderByDesc('is_active')
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FieldServiceTeamController.php:36:                    'is_active' => (bool) ($attrs['is_active'] ?? false),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/InventoryController.php:227:                                (string) $product->status,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/InventoryController.php:284:            'status' => (string) $product->status,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:152:                'status' => 'draft',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:162:                ['value' => 'draft', 'label' => 'Nacrt'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:195:                'status' => (string) $product->status,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php:152:            'inventory_state' => $this->nullableString($order->getAttribute('inventory_state')),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/AfterSalesController.php:267:                    ->where('is_active', true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/WarrantyController.php:622:            'is_active' => (bool) $rule->is_active,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FieldOperationsController.php:363:            $query->where('is_active', true);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FieldOperationsController.php:406:            'is_active' => (bool) ($attrs['is_active'] ?? false),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/OrderOptionsController.php:41:                ->where('is_active', true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/ProductMediaDownloadController.php:20:        $canViewCatalog = $product->status === 'active'
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:64:            'bankAccounts' => BankAccount::query()->where('is_active', true)->orderBy('label')->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:103:                $status = (string) $product->status;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:114:                        'draft' => 'Nacrt',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:143:            && in_array((string) $product->status, ['active', 'inactive'], true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Auth/CustomerActivationController.php:21:        return view('auth.activate-account', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Auth/CustomerActivationController.php:58:        $user = $activations->activate((string) $data['token'], (string) $data['password']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/SmartProductsDoctorCommand.php:8:use App\Services\ProductCompletenessService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/SmartProductsDoctorCommand.php:24:    public function handle(ProductCompletenessService $completeness, ProductTemplateService $templates): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/SmartProductsDoctorCommand.php:30:                $result = $completeness->recalculateAll();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/SmartProductsDoctorCommand.php:44:            'product_types' => ['id', 'name_template', 'auto_name_enabled', 'minimum_completeness_percent', 'default_product_status', 'required_core_fields_json'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/SmartProductsDoctorCommand.php:45:            'product_type_fields' => ['product_type_id', 'field_id', 'default_value', 'default_detail', 'completeness_weight', 'include_in_name'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/SmartProductsDoctorCommand.php:46:            'products' => ['id', 'product_type_id', 'model_name', 'completeness_percent', 'name_is_manual', 'source_product_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/SmartProductsDoctorCommand.php:92:                    $minimum = (int) $type->minimum_completeness_percent;
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/SmartProductsDoctorCommand.php:93:                    if ($minimum < 0 || $minimum > 100 || !in_array($type->default_product_status, ['draft', 'active', 'inactive'], true)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/SmartProductsDoctorCommand.php:128:                ->where(fn ($query) => $query->where('completeness_weight', '<', 1)->orWhere('completeness_weight', '>', 100))
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/SmartProductsDoctorCommand.php:138:                ->whereNull('completeness_percent')
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/SmartProductsDoctorCommand.php:139:                ->orWhere('completeness_percent', '<', 0)
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/SmartProductsDoctorCommand.php:140:                ->orWhere('completeness_percent', '>', 100)
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/SmartProductsDoctorCommand.php:151:                ->where('products.status', 'active')
--- MOBILE PRODUCT STATUS / ADMIN CATALOG HITS ---
/home/icaffeco/ald1n-project/apps/mobile/current/src/lib/api/endpoints.ts:113:        const response = await apiRequest<{ data: import('@/types/api').AdminCatalogCreateOptions }> ('admin/catalog/options');
/home/icaffeco/ald1n-project/apps/mobile/current/src/lib/api/endpoints.ts:117:        apiRequest<import('@/types/api').AdminProductCreateResponse> ('admin/catalog/products', { method: 'POST', body: input }),
/home/icaffeco/ald1n-project/apps/mobile/current/src/lib/api/endpoints.ts:125:          `admin/catalog/products/${productId}/images`,
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/layout/app-bottom-nav.tsx:32:    || pathname.startsWith('/admin/catalog/')
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/service-parts-admin-api.ts:19:  is_active: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/service-parts-admin-api.ts:49:  is_active: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/service-parts-admin-api.ts:108:  is_active: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/service-parts-admin-api.ts:151:  is_active: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-access.ts:2:  'catalog.manage_products',
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/warranties-admin-api.ts:140:  is_active: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/warranties-admin-api.ts:155:  is_active: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/field-operations-admin-api.ts:18:  is_active: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:179:  is_active: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:216:  is_active: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:760:    status: 'draft';
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:766:  statuses: Array<{ value: 'draft' | 'active' | 'inactive'; label: string }>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:791:  status: 'draft' | 'active' | 'inactive';
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:805:  status: 'draft' | 'active' | 'inactive';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/catalog.tsx:24:  const [draft, setDraft] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/catalog.tsx:60:              value={draft}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/catalog.tsx:62:              onSubmitEditing={() => setSearch(draft.trim())}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:50:    can('catalog.manage_products') ? { title: 'Dodaj artikal', copy: 'Kreiraj novi artikal', glyph: 'catalog' as const, route: '/admin/catalog/create' as const } : null,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:60:    route: '/catalog' | '/admin/catalog/create' | '/admin/commissions' | '/commissions' | '/cart' | '/orders' | '/admin' | '/notifications';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/commissions/index.tsx:95:  const [draftQ, setDraftQ] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/commissions/index.tsx:96:  const [draftDateFrom, setDraftDateFrom] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/commissions/index.tsx:97:  const [draftDateTo, setDraftDateTo] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/commissions/index.tsx:116:    const q = draftQ.trim();
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/commissions/index.tsx:117:    const dateFrom = draftDateFrom.trim();
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/commissions/index.tsx:118:    const dateTo = draftDateTo.trim();
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/commissions/index.tsx:203:                value={draftQ}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/commissions/index.tsx:245:                    value={draftDateFrom}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/commissions/index.tsx:255:                    value={draftDateTo}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/index.tsx:57:  const [draftQ, setDraftQ] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/index.tsx:58:  const [draftFrom, setDraftFrom] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/index.tsx:59:  const [draftTo, setDraftTo] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/index.tsx:108:    if (draftFrom && draftTo && draftTo < draftFrom) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/index.tsx:112:    setAppliedQ(draftQ.trim());
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/index.tsx:113:    setAppliedFrom(draftFrom.trim());
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/index.tsx:114:    setAppliedTo(draftTo.trim());
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/index.tsx:173:        <TextField label="Pretraga" value={draftQ} onChangeText={setDraftQ} placeholder="Porudžbina, korisnik ili e-mail" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/index.tsx:177:        <DateTimeField label="Od datuma" mode="date" value={draftFrom} onChangeText={setDraftFrom} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/index.tsx:178:        <DateTimeField label="Do datuma" mode="date" value={draftTo} onChangeText={setDraftTo} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:89:  const [draftQ, setDraftQ] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:90:  const [draftStatus, setDraftStatus] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:91:  const [draftAssignee, setDraftAssignee] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:92:  const [draftAction, setDraftAction] = useState<'' | 'overdue' | 'today' | 'promised'> ('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:93:  const [draftAging, setDraftAging] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:94:  const [draftPerPage, setDraftPerPage] = useState(35);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:139:      q: draftQ.trim() || undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:140:      status: draftStatus || undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:141:      assigned_to: draftAssignee ? Number(draftAssignee) : undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:142:      action: draftAction || undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:143:      aging: draftAging ? draftAging as AdminReceivableListParams['aging'] : undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:145:      per_page: draftPerPage,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:240:        <TextField label="Pretraga" value={draftQ} onChangeText={setDraftQ} placeholder="Broj predmeta ili porudžbine" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:241:        <SelectSheet label="Status" value={draftStatus} options={statusOptions} onChange={setDraftStatus} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:242:        <SelectSheet label="Odgovorno lice" value={draftAssignee} options={assigneeOptions} onChange={setDraftAssignee} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:243:        <SelectSheet label="Aging" value={draftAging} options={agingOptions} onChange={setDraftAging} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:244:        <FilterBar activeCount={draftAction ? 1 : 0} onClear={() => setDraftAction('')}>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:245:          <FilterChip label="Zakasnela akcija" active={draftAction === 'overdue'} onPress={() => setDraftAction((value) => value === 'overdue' ? '' : 'overdue')} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:246:          <FilterChip label="Akcija danas" active={draftAction === 'today'} onPress={() => setDraftAction((value) => value === 'today' ? '' : 'today')} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:247:          <FilterChip label="Obećana uplata" active={draftAction === 'promised'} onPress={() => setDraftAction((value) => value === 'promised' ? '' : 'promised')} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:251:          value={String(draftPerPage)}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:78:  const [draftQ, setDraftQ] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:79:  const [draftStatus, setDraftStatus] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:80:  const [draftPayment, setDraftPayment] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:81:  const [draftSource, setDraftSource] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:82:  const [draftSupplier, setDraftSupplier] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:83:  const [draftFrom, setDraftFrom] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:84:  const [draftTo, setDraftTo] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:85:  const [draftAttention, setDraftAttention] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:86:  const [draftPerPage, setDraftPerPage] = useState<AdminOrdersPerPage> (40);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:95:  const activeCount = Number(Boolean(draftQ.trim()))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:96:    + Number(Boolean(draftStatus))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:97:    + Number(Boolean(draftPayment))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:98:    + Number(Boolean(draftSource))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:99:    + Number(Boolean(draftSupplier))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:100:    + Number(Boolean(draftFrom))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:101:    + Number(Boolean(draftTo))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:102:    + Number(Boolean(draftAttention))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:103:    + Number(draftPerPage !== 40);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:106:    if (draftFrom && draftTo && draftTo < draftFrom) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:115:    const next: AdminOrdersRequestParams = { page: 1, per_page: draftPerPage };
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:116:    const q = trimmed(draftQ);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:118:    if (draftStatus) next.status = draftStatus;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:119:    if (draftPayment) next.payment_status = draftPayment;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:120:    if (draftSource) next.source_system = draftSource;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:121:    const supplierId = Number(draftSupplier);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:122:    if (draftSupplier && Number.isInteger(supplierId) && supplierId > 0) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:125:    if (draftFrom) next.date_from = draftFrom;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:126:    if (draftTo) next.date_to = draftTo;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:127:    if (draftAttention) next.attention = draftAttention;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:205:          <FilterBar activeCount={draftAttention ? 1 : 0} onClear={() => setDraftAttention('')}>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:210:                active={draftAttention === key}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:211:                onPress={() => setDraftAttention(draftAttention === key ? '' : key)}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:226:          value={draftQ}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:230:        <SelectSheet label="Status" value={draftStatus} options={statusOptions} onChange={setDraftStatus} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:231:        <SelectSheet label="Placanje" value={draftPayment} options={paymentOptions} onChange={setDraftPayment} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:232:        <SelectSheet label="Izvor" value={draftSource} options={sourceOptions} onChange={setDraftSource} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:236:            value={draftSupplier}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:241:        <DateTimeField label="Od datuma" mode="date" value={draftFrom} onChangeText={setDraftFrom} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:242:        <DateTimeField label="Do datuma" mode="date" value={draftTo} onChangeText={setDraftTo} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/index.tsx:245:          value={String(draftPerPage)}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:145:  const [draftFrom, setDraftFrom] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:146:  const [draftTo, setDraftTo] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:149:  const [draftBrand, setDraftBrand] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:150:  const [draftLine, setDraftLine] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:151:  const [draftType, setDraftType] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:152:  const [draftQ, setDraftQ] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:164:  const activeCount = Number(Boolean(draftFrom))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:165:    + Number(Boolean(draftTo))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:168:    + Number(Boolean(draftBrand.trim()))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:169:    + Number(Boolean(draftLine.trim()))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:170:    + Number(Boolean(draftType.trim()))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:171:    + Number(Boolean(draftQ.trim()));
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:174:    if (draftFrom && draftTo && draftTo < draftFrom) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:189:    const dateFrom = trimmed(draftFrom);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:190:    const dateTo = trimmed(draftTo);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:191:    const brand = trimmed(draftBrand);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:192:    const line = trimmed(draftLine);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:193:    const type = trimmed(draftType);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:194:    const q = trimmed(draftQ);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:302:          value={draftFrom}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:308:          value={draftTo}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:339:          value={draftBrand}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:345:          value={draftLine}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:351:          value={draftType}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:357:          value={draftQ}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:463:    setIsActive(schedule.is_active);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:573:      is_active: isActive,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:609:        title: schedule.is_active ? 'Raspored je aktiviran' : 'Raspored je pauziran',
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:828:              <Text style={schedule.is_active ? styles.scheduleActive : styles.schedulePaused}>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:829:                {schedule.is_active ? 'Aktivan' : 'Pauziran'}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:857:                  {schedule.is_active ? 'Pauziraj' : 'Aktiviraj'}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:70:    active: part.is_active,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:86:  const [draftQ, setDraftQ] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:87:  const [draftActive, setDraftActive] = useState<'' | 'true' | 'false'> ('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:114:  const writeInput = (draft: PartDraft, includeOpeningStock: boolean): AdminServicePartWrite => {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:115:    if (!draft.sku.trim()) throw new Error('SKU je obavezan.');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:116:    if (!draft.name.trim()) throw new Error('Naziv je obavezan.');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:117:    if (!draft.unit.trim()) throw new Error('Jedinica mere je obavezna.');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:119:      sku: draft.sku.trim(),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:120:      name: draft.name.trim(),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:121:      unit: draft.unit.trim(),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:122:      ...(includeOpeningStock ? { stock_quantity: decimal(draft.stock, 'Početno stanje') } : {}),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:123:      minimum_quantity: decimal(draft.minimum, 'Minimalna količina'),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:124:      average_cost_rsd: decimal(draft.averageCost, 'Prosečna nabavna cena'),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:125:      is_active: draft.active,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:126:      notes: compact(draft.notes),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:193:    q: draftQ.trim() || undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:194:    active: draftActive === '' ? undefined : draftActive === 'true',
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:261:            <TextField label="Pretraga" value={draftQ} onChangeText={setDraftQ} placeholder="SKU ili naziv" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:262:            <SelectSheet label="Aktivnost" value={draftActive} options={[{ value: '', label: 'Svi' }, { value: 'true', label: 'Aktivni' }, { value: 'false', label: 'Neaktivni' }]} onChange={(value) => setDraftActive(value as '' | 'true' | 'false')} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx:270:                <View style={styles.rowBetween}><View style={styles.grow}><Text style={styles.title}>{part.sku} · {part.name}</Text><Text style={styles.meta}>{part.is_active ? 'Aktivan' : 'Neaktivan'} · {part.unit ?? '—'}</Text></View><Pill tone={part.available_quantity !== null && part.minimum_quantity !== null && part.available_quantity <= part.minimum_quantity ? 'warning' : 'success'}>{part.is_active ? 'Aktivan' : 'Neaktivan'}</Pill></View>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/purchases/[id].tsx:70:        ? 'Nacrt prelazi iz draft u submitted.'
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/purchases/[id].tsx:71:        : 'Otkazivanje je dozvoljeno samo za draft, submitted ili ordered nabavku.';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/purchases/index.tsx:53:  const [draftQ, setDraftQ] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/purchases/index.tsx:54:  const [draftStatus, setDraftStatus] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/purchases/index.tsx:55:  const [draftSupplier, setDraftSupplier] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/purchases/index.tsx:72:  const statusOptions = [{ value: '', label: 'Svi statusi' }, { value: 'draft', label: 'Nacrt' }, { value: 'submitted', label: 'Poslato' }, { value: 'ordered', label: 'Naručeno' }, { value: 'received', label: 'Primljeno' }, { value: 'cancelled', label: 'Otkazano' }];
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/purchases/index.tsx:87:      feedback.notify({ tone: 'success', title: 'Nacrt nabavke je kreiran', message: 'Statusni tok ostaje draft → submitted → ordered → received.' });
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/purchases/index.tsx:117:        <TextField label="Broj nabavke" value={draftQ} onChangeText={setDraftQ} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/purchases/index.tsx:118:        <SelectSheet label="Status" value={draftStatus} options={statusOptions} onChange={setDraftStatus} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/purchases/index.tsx:119:        <SelectSheet label="Dobavljač" value={draftSupplier} options={[{ value: '', label: 'Svi dobavljači' }, ...supplierOptions.filter((option) => option.value !== '')]} onChange={setDraftSupplier} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/purchases/index.tsx:120:        <View style={styles.actions}><Button onPress={() => setApplied({ q: draftQ.trim() || undefined, status: draftStatus || undefined, supplier_id: draftSupplier ? Number(draftSupplier) : undefined, page: 1, per_page: 40 })}>Primeni</Button><Button variant="secondary" loading={query.isFetching} onPress={() => void query.refetch()}>Osveži</Button></View>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:31:  return { code: item.code, name: item.name, contact: item.contact_person ?? '', phone: item.phone ?? '', email: item.email ?? '', address: item.address ?? '', leadTime: item.lead_time_days === null ? '' : String(item.lead_time_days), notes: item.notes ?? '', active: item.is_active };
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:41:  const [draftQ, setDraftQ] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:42:  const [draftActive, setDraftActive] = useState<'' | 'true' | 'false'> ('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:57:  const input = (draft: Draft): AdminServicePartSupplierWrite => {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:58:    if (!draft.code.trim()) throw new Error('Šifra dobavljača je obavezna.');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:59:    if (!draft.name.trim()) throw new Error('Naziv dobavljača je obavezan.');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:60:    const lead = draft.leadTime.trim() === '' ? null : Number(draft.leadTime);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:62:    return { code: draft.code.trim(), name: draft.name.trim(), contact_person: compact(draft.contact), phone: compact(draft.phone), email: compact(draft.email), address: compact(draft.address), lead_time_days: lead, notes: compact(draft.notes), is_active: draft.active };
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:89:  const form = (draft: Draft, setter: Dispatch<SetStateAction<Draft>>, action: () => void, label: string) => (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:91:      <TextField label="Šifra" value={draft.code} onChangeText={(value) => setField(setter, 'code', value)} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:92:      <TextField label="Naziv" value={draft.name} onChangeText={(value) => setField(setter, 'name', value)} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:93:      <TextField label="Kontakt osoba" value={draft.contact} onChangeText={(value) => setField(setter, 'contact', value)} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:94:      <TextField label="Telefon" value={draft.phone} onChangeText={(value) => setField(setter, 'phone', value)} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:95:      <TextField label="Email" value={draft.email} onChangeText={(value) => setField(setter, 'email', value)} keyboardType="email-address" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:96:      <TextField label="Adresa" value={draft.address} onChangeText={(value) => setField(setter, 'address', value)} multiline />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:97:      <TextField label="Rok isporuke (dana)" value={draft.leadTime} onChangeText={(value) => setField(setter, 'leadTime', value)} keyboardType="numeric" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:98:      <TextField label="Napomena" value={draft.notes} onChangeText={(value) => setField(setter, 'notes', value)} multiline />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:99:      <FilterChip label={draft.active ? 'Aktivan' : 'Neaktivan'} active={draft.active} onPress={() => setField(setter, 'active', !draft.active)} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:113:        <TextField label="Pretraga" value={draftQ} onChangeText={setDraftQ} placeholder="Šifra, naziv, kontakt ili email" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:114:        <SelectSheet label="Aktivnost" value={draftActive} options={[{ value: '', label: 'Svi' }, { value: 'true', label: 'Aktivni' }, { value: 'false', label: 'Neaktivni' }]} onChange={(value) => setDraftActive(value as '' | 'true' | 'false')} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:115:        <View style={styles.actions}><Button onPress={() => setApplied({ q: draftQ.trim() || undefined, active: draftActive === '' ? undefined : draftActive === 'true', page: 1, per_page: 40 })}>Primeni</Button><Button variant="secondary" loading={query.isFetching} onPress={() => void query.refetch()}>Osveži</Button></View>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/service-parts/suppliers.tsx:117:      {response.data.length === 0 ? <EmptyState title="Nema dobavljača" message="Nema rezultata za izabrane filtere." /> : <View style={styles.list}>{response.data.map((supplier) => <Card key={supplier.id} style={styles.card}><View style={styles.rowBetween}><View style={styles.grow}><Text style={styles.title}>{supplier.code} · {supplier.name}</Text><Text style={styles.meta}>{supplier.contact_person ?? 'Bez kontakt osobe'} · {supplier.phone ?? 'bez telefona'}</Text></View><FilterChip label={supplier.is_active ? 'Aktivan' : 'Neaktivan'} active={supplier.is_active} onPress={() => {}} /></View><Text style={styles.meta}>{supplier.email ?? 'Bez emaila'} · rok {supplier.lead_time_days ?? '—'} dana</Text>{supplier.address ? <Text style={styles.copy}>{supplier.address}</Text> : null}<Button variant="secondary" onPress={() => { setEditing(supplier); setEditDraft(toDraft(supplier)); }}>Izmeni</Button></Card>)}</View>}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:60:  const [draftAction, setDraftAction] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:61:  const [draftLevel, setDraftLevel] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:62:  const [draftUserId, setDraftUserId] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:63:  const [draftFrom, setDraftFrom] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:64:  const [draftTo, setDraftTo] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:65:  const [draftPerPage, setDraftPerPage] = useState<AdminAuditPerPage> (20);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:74:  const activeCount = Number(Boolean(draftAction.trim()))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:75:    + Number(Boolean(draftLevel))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:76:    + Number(Boolean(draftUserId))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:77:    + Number(Boolean(draftFrom))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:78:    + Number(Boolean(draftTo))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:79:    + Number(draftPerPage !== 20);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:82:    if (draftFrom && draftTo && draftTo < draftFrom) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:93:      per_page: draftPerPage,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:95:    const action = trimmed(draftAction);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:97:    if (draftLevel) next.level = draftLevel;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:98:    const userId = Number(draftUserId);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:99:    if (draftUserId && Number.isInteger(userId) && userId > 0) next.user_id = userId;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:100:    if (draftFrom) next.date_from = draftFrom;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:101:    if (draftTo) next.date_to = draftTo;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:172:              active={draftLevel === level}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:173:              onPress={() => setDraftLevel(draftLevel === level ? '' : level)}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:180:          value={draftAction}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:186:          value={draftLevel}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:192:          value={draftUserId}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:199:          value={draftFrom}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:205:          value={draftTo}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/audit/index.tsx:210:          value={String(draftPerPage)}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/inventory/index.tsx:103:  const [draftQ, setDraftQ] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/inventory/index.tsx:105:  const [draftStatus, setDraftStatus] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/inventory/index.tsx:179:    q: optional(draftQ) ?? undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/inventory/index.tsx:181:    status: optional(draftStatus) ?? undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/inventory/index.tsx:301:        <TextField label="Pretraga" value={draftQ} onChangeText={setDraftQ} placeholder="SKU ili naziv artikla" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/inventory/index.tsx:302:        <TextField label="Status" value={draftStatus} onChangeText={setDraftStatus} placeholder="Opcionalno" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/inventory/index.tsx:303:        <FilterBar activeCount={(stockLow ? 1 : 0) + (draftQ.trim() ? 1 : 0) + (draftStatus.trim() ? 1 : 0)} onClear={() => { setStockLow(false); setDraftQ(''); setDraftStatus(''); }}>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/inventory/index.tsx:366:          <Text style={styles.meta}>Isti idempotency ključ ostaje tokom neuspelog retry-a; draft se briše tek posle uspešnog knjiženja.</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/rules.tsx:157:    setIsActive(rule.is_active);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/rules.tsx:190:      is_active: isActive,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/rules.tsx:471:              <Text style={rule.is_active ? styles.active : styles.inactive}>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/rules.tsx:472:                {rule.is_active ? 'Aktivno' : 'Neaktivno'}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:62:  const [draftQ, setDraftQ] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:96:    setAppliedQ(draftQ.trim());
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:190:          value={draftQ}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:38:    ...teams.map((team) => ({ value: String(team.id), label: `${team.name}${team.is_active ? '' : ' · neaktivna'}` })),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:76:  const [draftQ, setDraftQ] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:77:  const [draftStatus, setDraftStatus] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:78:  const [draftTeam, setDraftTeam] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:79:  const [draftDateFrom, setDraftDateFrom] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:80:  const [draftDateTo, setDraftDateTo] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:81:  const [draftUnassigned, setDraftUnassigned] = useState(false);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:82:  const [draftPerPage, setDraftPerPage] = useState(40);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:109:      q: draftQ.trim() || undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:110:      status: draftStatus || undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:111:      team_id: draftTeam ? Number(draftTeam) : undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:112:      date_from: draftDateFrom.trim() || undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:113:      date_to: draftDateTo.trim() || undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:114:      unassigned: draftUnassigned || undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:116:      per_page: draftPerPage,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:144:        <TextField label="Pretraga" value={draftQ} onChangeText={setDraftQ} placeholder="Broj naloga, slučaja, porudžbine ili referenca" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:145:        <SelectSheet label="Status" value={draftStatus} options={statusOptions} onChange={setDraftStatus} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:146:        <SelectSheet label="Ekipa" value={draftTeam} options={teamOptions(response.filter_options.teams)} onChange={setDraftTeam} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:147:        <TextField label="Datum od" value={draftDateFrom} onChangeText={setDraftDateFrom} placeholder="YYYY-MM-DD" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:148:        <TextField label="Datum do" value={draftDateTo} onChangeText={setDraftDateTo} placeholder="YYYY-MM-DD" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:149:        <FilterBar activeCount={Number(draftUnassigned)} onClear={() => setDraftUnassigned(false)}>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:150:          <FilterChip label="Bez dodeljene ekipe" active={draftUnassigned} onPress={() => setDraftUnassigned((value) => !value)} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:154:          value={String(draftPerPage)}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/after-sales/[id].tsx:265:        const draft = actionItems[item.id];
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/after-sales/[id].tsx:268:          selected: draft?.selected ?? false,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/after-sales/[id].tsx:269:          quantity: Number(draft?.quantity ?? '1'),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/after-sales/[id].tsx:270:          disposition: draft?.disposition || undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/after-sales/[id].tsx:390:            const draft = actionItems[item.id];
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/after-sales/[id].tsx:391:            const selected = draft?.selected ?? false;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/after-sales/[id].tsx:392:            const quantity = draft?.quantity ?? '1';

============================================================
3. DISK / STORAGE DUPLICATION SOURCE MAP
============================================================
--- CMS DISK STORAGE REPEATER AND SPEC HITS ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:120:            foreach ($storedPaths as $path) Storage::disk('local')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:168:            foreach ($storedPaths as $path) Storage::disk('local')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:318:                'disk' => 'local',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:490:            $full = Storage::disk('public')->path($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogSyncService.php:143:        if ($table === 'product_images') $row['storage_disk'] = 'legacy';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogSyncService.php:166:        foreach (['legacy_checksum','legacy_synced_at','locally_modified_at','storage_disk','file_hash'] as $column) unset($row[$column]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:75:            Storage::disk('local')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:237:        if (!$payment->proof_path || !Storage::disk('local')->exists($payment->proof_path)) return null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:238:        return Storage::disk('local')->path($payment->proof_path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:14:final class StorageSpecificationService
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:17:    public const ROLE_TOTAL_CAPACITY = 'total_capacity';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:47:                // Preserve an old total even when the historical product did not store a disk type.
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:48:                // The user can add the missing disk details later without losing the existing value.
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:59:    /** @param array<int,array<string,mixed>> $rows @return array<int,array{type:string,capacity_gb:?int}> */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:66:            $capacity = $this->normalizeCapacity($row['capacity_gb'] ?? null);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:67:            if ($type === '' && $capacity === null) continue;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:68:            $normalized[] = ['type' => $type, 'capacity_gb' => $capacity];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:74:                if ($row['capacity_gb'] !== null) { $hasCapacity = true; break; }
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:76:            if (!$hasCapacity) $normalized[0]['capacity_gb'] = $fallbackTotal;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:82:    /** @return array<int,array{type:string,capacity_gb:?int}> */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:90:                'capacity_gb' => isset($match[2]) && $match[2] !== '' ? (int) $match[2] : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:97:    /** @param array<int,array{type:string,capacity_gb:?int}> $rows */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:100:        return array_sum(array_map(static fn (array $row): int => max(0, (int) ($row['capacity_gb'] ?? 0)), $rows));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:103:    /** @param array<int,array{type:string,capacity_gb:?int}> $rows */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:108:            $capacity = $row['capacity_gb'] ?? null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:109:            return trim($type.($capacity !== null ? ' '.(int) $capacity.' GB' : ''));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:114:     * Detect storage fields, link the old capacity field to the repeatable disk field,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:115:     * move the total below the disk list and normalize existing product data.
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:143:                'help_text' => 'Automatski zbir kapaciteta svih unetih diskova. Polje se ne unosi ručno.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:145:            if (!str_contains(Str::lower((string) $total->name), 'ukupan')) $update['name'] = 'Ukupan kapacitet diskova';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:247:        $mentionsStorage = str_contains($needle, 'disk') || str_contains($needle, 'storage') || str_contains($needle, 'skladist') || str_contains($needle, 'ssd') || str_contains($needle, 'hdd');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:248:        return $mentionsStorage && (str_contains($needle, 'tip') || str_contains($needle, 'vrsta') || str_contains($needle, 'disk'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:256:        $mentionsStorage = str_contains($needle, 'disk') || str_contains($needle, 'storage') || str_contains($needle, 'skladist');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:257:        $mentionsCapacity = str_contains($needle, 'kapacitet') || str_contains($needle, 'capacity');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:281:            if (str_contains($needle, 'tip_diska') || str_contains($needle, 'disk_type')) $score += 25;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:282:            if (str_contains($needle, 'disk')) $score += 10;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:427:            // A preserved old total without a historical disk type is not corrupt; it remains editable
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:428:            // until the disk details are entered and then becomes fully structured.
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:68:            ->get(['id', 'storage_disk', 'file_path']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:160:                'storage_disk' => (string) $image->storage_disk,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:215:            $disk = (string) ($image['storage_disk'] ?? '');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:218:            if ($disk !== 'public') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:220:                    'total_confirmation' => 'Total Product Purge je blokiran jer artikal ima sliku van lokalnog public storage-a ('.$disk.'). Takav izvor prvo mora biti migriran ili zasebno uklonjen.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/FieldOperationsService.php:119:            foreach ($stored as $file) Storage::disk('local')->delete((string) $file['path']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:19:        private readonly StorageSpecificationService $storage,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:320:        $issues[] = $this->issue('storage_specification_mismatches', 'Neusklađeni pojedinačni diskovi i ukupan kapacitet', 'warning', $storageProblems, 'Bezbedna popravka ponovo računa izvedeni ukupan kapacitet.', true, [$storageCounts]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:300:                            'disk' => trim((string) ($delivery->proof_disk ?: 'local')) ?: 'local',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:419:                $this->deletePrivateFile((string) ($newProof['proof_disk'] ?? 'local'), (string) ($newProof['proof_path'] ?? ''));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:425:            $newDisk = (string) ($newProof['proof_disk'] ?? 'local');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:427:            if ($oldProof['disk'] !== $newDisk || $oldProof['path'] !== $newPath) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:428:                $this->deletePrivateFile((string) $oldProof['disk'], (string) $oldProof['path']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:554:            'proof_disk' => 'local',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:562:    private function deletePrivateFile(string $disk, string $path): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:564:        $disk = trim($disk) ?: 'local';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:571:            Storage::disk($disk)->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTemplateService.php:61:            if ($field->isDerivedStorageTotalField()) continue; // Izvedeno polje se automatski računa iz pojedinačnih diskova.
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTemplateService.php:171:            if (str_contains($slug, 'ssd') || str_contains($slug, 'storage') || str_contains($slug, 'disk')) $aliases['storage'] ??= $field->slug;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:431:        try { $full = Storage::disk('public')->path($path); return is_file($full) ? $full : null; } catch (Throwable) { return null; }
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderShipmentService.php:153:                $this->deletePrivateFile((string) ($storedProof['proof_disk'] ?? 'local'), (string) ($storedProof['proof_path'] ?? ''));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderShipmentService.php:205:            'proof_disk' => 'local',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderShipmentService.php:213:    private function deletePrivateFile(string $disk, string $path): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderShipmentService.php:215:        $disk = trim($disk) ?: 'local';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderShipmentService.php:218:        try { Storage::disk($disk)->delete($path); } catch (Throwable) {}
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/BusinessDocumentPdfService.php:59:            // Diskretan status u zaglavlju/footru; PDF ostaje čitljiv i arhivski upotrebljiv.
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/WarrantyCertificatePayloadService.php:31:                $candidate = Storage::disk('public')->path($logoPath);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderReportService.php:219:            $full = Storage::disk('public')->path($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:28:        private readonly StorageSpecificationService $storageSpecifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:51:            unset($data['category_ids'], $data['specs'], $data['spec_details'], $data['spec_lists'], $data['spec_capacities'], $data['spec_structured'], $data['images'], $data['regenerate_sku'], $data['regenerate_name']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:102:            unset($data['category_ids'], $data['specs'], $data['spec_details'], $data['spec_lists'], $data['spec_capacities'], $data['spec_structured'], $data['images'], $data['regenerate_sku'], $data['regenerate_name']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:244:        $structured = (array) ($data['spec_structured'] ?? []);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductBulkService.php:145:                'specification_field_id' => 'Diskovi i ukupan kapacitet menjaju se na formi konkretnog artikla, jer se zbir računa automatski.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:97:                    ->get(['id', 'storage_disk', 'file_path']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:99:                $result['public_images'] = $images->where('storage_disk', 'public')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:100:                $result['legacy_images'] = $images->where('storage_disk', 'legacy')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:154:                $result['files_deleted'] = !Storage::disk('public')->exists($directory)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:155:                    || Storage::disk('public')->deleteDirectory($directory);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SystemHealthService.php:11:use App\Support\DiskSpaceHealthPolicy;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SystemHealthService.php:136:        $free = @disk_free_space(storage_path());
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SystemHealthService.php:137:        $total = @disk_total_space(storage_path());
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SystemHealthService.php:138:        $disk = DiskSpaceHealthPolicy::evaluate($free, $total, (array) config('system_health.disk', []));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SystemHealthService.php:139:        $add('disk', 'Prostor na disku', $disk['status'], $disk['message']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SystemHealthService.php:173:                'disk_free_bytes' => $free,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SystemHealthService.php:174:                'disk_total_bytes' => $total,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SystemHealthService.php:175:                'disk_free_percent' => $disk['free_percent'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/NbsIpsQrService.php:82:        if (!Storage::disk('local')->put($path, $generated['png'])) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/NbsIpsQrService.php:116:            if ($storedPath !== null) Storage::disk('local')->delete($storedPath);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/NbsIpsQrService.php:134:        return $path !== null ? Storage::disk('local')->path($path) : null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/NbsIpsQrService.php:139:        if (is_string($path) && $path !== '') Storage::disk('local')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/NbsIpsQrService.php:154:        if ($path === '' || !Storage::disk('local')->exists($path)) return null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SiteAssetUrlService.php:17:        if (Str::startsWith($path, 'site-assets/')) return Storage::disk('public')->url($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:42:                'storage_disk' => 'public',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:66:            $disk = (string) $image->storage_disk;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:67:            if ($disk === 'public') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:68:                if (!Storage::disk('public')->exists($path)) continue;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:71:                Storage::disk('public')->makeDirectory('products/'.$target->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:72:                if (!Storage::disk('public')->copy($path, $newPath)) continue;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:74:            } elseif ($disk !== 'legacy') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:81:                'storage_disk' => $disk,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:133:            if ($image->storage_disk === 'public') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:134:                $destination = Storage::disk('public')->path((string) $image->file_path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:144:                Storage::disk('public')->makeDirectory('products/'.$product->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:145:                $destination = Storage::disk('public')->path($newPublicPath);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:157:            $finalPath = $image->storage_disk === 'public'
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:158:                ? Storage::disk('public')->path((string) $image->file_path)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:159:                : Storage::disk('public')->path((string) $newPublicPath);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:161:            $wasLegacy = $image->storage_disk === 'legacy';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:163:                $image->storage_disk = 'public';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:185:            if ($newPublicPath !== null) Storage::disk('public')->delete($newPublicPath);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:250:        abort_if($image->storage_disk !== 'public', 422, 'Legacy fajl je read-only. Rotiraj ga prvo ako želiš lokalnu kopiju.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:252:        Storage::disk('public')->delete($image->file_path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:269:        if ($image->storage_disk === 'public') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:270:            $path = Storage::disk('public')->path((string) $image->file_path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:275:        if ($image->storage_disk !== 'legacy') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductImage.php:16:        'product_id', 'file_path', 'storage_disk', 'original_filename', 'mime_type', 'file_size',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductImage.php:25:            $disk = trim((string) $this->storage_disk);
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductImage.php:29:            if ($disk === 'public') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductImage.php:33:                $url = Storage::disk('public')->url($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ProductImage.php:34:            } elseif ($disk === 'legacy' && (int) $this->getKey() > 0) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/SpecificationField.php:34:        return (str_contains($needle, 'memorij') || str_contains($needle, 'ram') || str_contains($needle, 'kapacitet'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/SpecificationField.php:40:        return (string) $this->storage_role === \App\Services\StorageSpecificationService::ROLE_COMPONENTS;
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/SpecificationField.php:45:        if ((string) $this->storage_role === \App\Services\StorageSpecificationService::ROLE_TOTAL_CAPACITY) return true;
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/SpecificationField.php:49:        return (str_contains($needle, 'disk') || str_contains($needle, 'storage') || str_contains($needle, 'skladist'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/SpecificationField.php:50:            && (str_contains($needle, 'kapacitet') || str_contains($needle, 'capacity'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/SpecificationField.php:67:        return str_contains($needle, 'disk')
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AfterSalesAttachment.php:12:    protected $fillable = ['after_sales_case_id', 'message_id', 'uploaded_by', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderDelivery.php:14:        'reference', 'note', 'proof_disk', 'proof_path', 'proof_original_name',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderShipment.php:15:        'tracking_number_snapshot', 'note', 'proof_disk', 'proof_path', 'proof_original_name',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:13:use App\Services\StorageSpecificationService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:63:            'spec_lists' => ['array'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:64:            'spec_lists.*' => ['array', 'max:8'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:65:            'spec_lists.*.*' => ['nullable', 'string', 'max:255'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:66:            'spec_capacities' => ['array'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:67:            'spec_capacities.*' => ['array', 'max:8'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:68:            'spec_capacities.*.*' => ['nullable', 'integer', 'min:0', 'max:10000000'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:69:            'spec_structured' => ['array'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:70:            'spec_structured.*' => ['array', 'max:8'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:71:            'spec_structured.*.*.type' => ['required', 'string', 'max:255'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:72:            'spec_structured.*.*.capacity_gb' => ['nullable', 'integer', 'min:0', 'max:10000000'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:167:            $structured = (array) $this->input('spec_structured', []);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:197:                                $validator->errors()->add('spec_lists.'.$field->id.'.'.$index, 'Izabrana opcija diska „'.$selectedValue.'“ više nije dostupna.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:259:        $capacityLists = (array) $this->input('spec_capacities', []);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:261:        foreach ((array) $this->input('spec_lists', []) as $fieldId => $values) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:263:            $capacities = is_array($capacityLists[$fieldId] ?? null) ? $capacityLists[$fieldId] : [];
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:267:                $diskType = mb_substr(trim((string) $value), 0, 255);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:268:                $capacityRaw = trim((string) ($capacities[$index] ?? ''));
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:269:                if ($diskType === '' && $capacityRaw === '') continue;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:271:                $capacity = $capacityRaw === '' ? null : $capacityRaw;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:272:                $rows[] = ['type' => $diskType, 'capacity_gb' => $capacity];
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:273:                $display[] = trim($diskType.($capacity !== null ? ' '.$capacity.' GB' : ''));
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:287:            app(StorageSpecificationService::class)->applyComputedTotals($type->fields, $specs, $structured);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:303:            'spec_structured' => $structured,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/SiteAppearanceController.php:290:                    Storage::disk('public')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/SiteAppearanceController.php:297:            Storage::disk('public')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/SiteAppearanceController.php:318:                Storage::disk('public')->delete($old);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/SiteAppearanceController.php:368:            Storage::disk('public')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductImageController.php:75:            $wasLegacy = $image->storage_disk === 'legacy';
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductImageController.php:88:                        'storage_disk' => (string) $image->storage_disk,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DocumentSettingsController.php:108:        $disk = Storage::disk('public');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DocumentSettingsController.php:109:        $disk->makeDirectory('document-assets');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DocumentSettingsController.php:111:        $destination = $disk->path($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DocumentSettingsController.php:170:            $disk->setVisibility($path, 'public');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DocumentSettingsController.php:172:            // Local public disk može već imati odgovarajuće dozvole.
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DocumentSettingsController.php:182:            Storage::disk('public')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DocumentSettingsController.php:207:            return Storage::disk('public')->url($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/ProductMediaController.php:16:        abort_unless($image->storage_disk === 'legacy', 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/FieldWorkOrderAttachmentController.php:26:        abort_unless(Storage::disk('local')->exists($attachment->path), 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/FieldWorkOrderAttachmentController.php:27:        return Storage::disk('local')->download($attachment->path, $attachment->original_name, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:17:use App\Services\StorageSpecificationService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:26:        private readonly StorageSpecificationService $storageSpecifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:83:                            'storage_repeater' => $storageMap[$fieldId] ?? null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:169:                'specialized_storage_repeater' => $storageRepeaterCount > 0,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:221:     * StorageSpecificationService with a harmless in-memory 256 + 512 GB probe.
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:223:     * @return array<int,array{enabled:true,total_field_id:int,total_field_name:string,total_unit:?string,max_items:int,capacity_unit:string}>
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:236:            if ($probeType === '') $probeType = 'SSD';
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:241:                    ['type' => $probeType, 'capacity_gb' => 256],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:242:                    ['type' => $probeType, 'capacity_gb' => 512],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:267:                'capacity_unit' => 'GB',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/AfterSalesAttachmentController.php:26:        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/AfterSalesAttachmentController.php:28:        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php:266:                || str_ends_with($normalized, '_disk')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php:283:                    || str_ends_with($normalized, '_disk')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderShipmentController.php:81:        $disk = trim((string) ($shipment->proof_disk ?: 'local')) ?: 'local';
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderShipmentController.php:83:        abort_if($path === '' || !Storage::disk($disk)->exists($path), 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderShipmentController.php:91:        return response()->file(Storage::disk($disk)->path($path), [
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/OrderController.php:245:        $disk = trim((string) ($delivery->proof_disk ?: 'local')) ?: 'local';
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/OrderController.php:247:        abort_if($path === '' || !Storage::disk($disk)->exists($path), 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/OrderController.php:249:        $fullPath = Storage::disk($disk)->path($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AfterSalesAttachmentController.php:22:        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AfterSalesAttachmentController.php:24:        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/ProductMediaDownloadController.php:25:        [$absolute, $mime] = $image->storage_disk === 'public'
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/ProductMediaDownloadController.php:55:        abort_unless(Storage::disk('public')->exists($relative), 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/ProductMediaDownloadController.php:56:        $absolute = Storage::disk('public')->path($relative);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/ProductMediaDownloadController.php:65:        abort_unless($image->storage_disk === 'legacy', 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderDeliveryController.php:19:        $disk = trim((string) ($delivery->proof_disk ?: 'local')) ?: 'local';
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderDeliveryController.php:21:        abort_if($path === '' || !Storage::disk($disk)->exists($path), 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderDeliveryController.php:23:        $fullPath = Storage::disk($disk)->path($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderShipmentProofController.php:19:        $disk = trim((string) ($shipment->proof_disk ?: 'local')) ?: 'local';
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderShipmentProofController.php:21:        abort_if($path === '' || !Storage::disk($disk)->exists($path), 404);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderShipmentProofController.php:29:        return response()->file(Storage::disk($disk)->path($path), [
/home/icaffeco/ald1n-project/apps/cms/current/app/Support/DiskSpaceHealthPolicy.php:7:final class DiskSpaceHealthPolicy
/home/icaffeco/ald1n-project/apps/cms/current/app/Support/DiskSpaceHealthPolicy.php:20:                'message' => 'Nije moguće pouzdano očitati slobodan prostor na disku.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CmsV214DoctorCommand.php:35:            'product_images' => ['id', 'product_id', 'storage_disk', 'file_path'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AfterSalesDoctorCommand.php:39:            'after_sales_attachments' => ['id', 'after_sales_case_id', 'disk', 'path', 'mime_type'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogSettingsDoctorCommand.php:10:use App\Services\StorageSpecificationService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogSettingsDoctorCommand.php:19:    protected $signature = 'app:catalog-settings-doctor {--repair : Automatski poveži kategorije i disk polja, preračunaj kapacitete i ukloni zastarele reference}';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogSettingsDoctorCommand.php:21:    protected $description = 'Proveri stranice tipova proizvoda, automatske kategorije, diskove i integritet specifikacija.';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogSettingsDoctorCommand.php:23:    public function handle(ProductTypeCategoryService $typeCategories, SpecificationFieldLifecycleService $fieldLifecycle, StorageSpecificationService $storage): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogSettingsDoctorCommand.php:71:            app_path('Services/StorageSpecificationService.php'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogSettingsDoctorCommand.php:72:            database_path('migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogSettingsDoctorCommand.php:102:                    'Repair diskova: komponente %d, ukupna polja %d, parovi %d, proizvodi %d.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogSettingsDoctorCommand.php:151:                $this->line('<fg=yellow>WARN</> Diskovi i ukupan kapacitet nisu potpuno usklađeni: '.$details.'. Pokreni komandu sa --repair.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogSettingsDoctorCommand.php:154:                $this->info('PASS Pojedinačni diskovi, stari kapaciteti i automatski ukupni zbir su usklađeni.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OperationsDoctorCommand.php:32:        'order_deliveries' => ['order_id', 'delivery_method', 'delivered_at', 'recipient_name', 'recipient_phone', 'reference', 'note', 'proof_disk', 'proof_path', 'confirmed_by'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:102:        'order_deliveries' => ['id', 'order_id', 'delivery_method', 'delivered_at', 'recipient_name', 'recipient_phone', 'reference', 'note', 'proof_disk', 'proof_path', 'confirmed_by'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:106:        'after_sales_attachments' => ['id', 'after_sales_case_id', 'message_id', 'disk', 'path', 'mime_type', 'size_bytes'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:330:                && DB::table('product_images')->where('storage_disk', 'legacy')->exists();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductMediaDoctorCommand.php:30:        foreach (['id', 'product_id', 'file_path', 'storage_disk', 'file_hash', 'rotation_degrees', 'sort_order', 'is_primary'] as $column) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductMediaDoctorCommand.php:64:            $sample = ProductImage::query()->where('storage_disk', 'public')->orderByDesc('id')->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ProductMediaDoctorCommand.php:67:                if (!Storage::disk('public')->exists($path)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/LegacyImportCommand.php:198:            $row['storage_disk'] = 'legacy';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/LegacyMediaCheckCommand.php:36:            ->where('storage_disk', 'legacy')
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:191:                    <p class="muted">Prvo izaberi tip artikla. Za disk možeš dodati više stavki, na primer SSD + SSD + HDD.</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:206:                                        $storageRows = old('spec_structured.'.$field->id, $specStructured[$field->id] ?? null);
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:209:                                            foreach (preg_split('/\s*\+\s*/u', trim((string) $currentValue), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $storedDisk) {
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:210:                                                preg_match('/^(.*?)(?:\s+(\d+)\s*GB)?$/iu', trim($storedDisk), $storedMatch);
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:211:                                                $storageRows[] = ['type' => trim((string) ($storedMatch[1] ?? $storedDisk)), 'capacity_gb' => ($storedMatch[2] ?? '') !== '' ? (int) $storedMatch[2] : null];
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:214:                                        if ($storageRows === []) $storageRows = [['type' => '', 'capacity_gb' => null]];
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:215:                                        $hasStorageCapacity = collect($storageRows)->contains(fn ($row) => isset($row['capacity_gb']) && $row['capacity_gb'] !== '' && $row['capacity_gb'] !== null);
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:216:                                        $storageCalculatedTotal = collect($storageRows)->sum(fn ($row) => max(0, (int) ($row['capacity_gb'] ?? 0)));
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:225:                                                    <button class="button button-small button-ghost" type="button" data-repeatable-add><x-icon name="plus-circle" size="16" /> Dodaj još jedan disk</button>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:231:                                                        @php($storageCapacity = ($storageRow['capacity_gb'] ?? '') === null ? '' : (string) ($storageRow['capacity_gb'] ?? ''))
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:233:                                                            <label class="repeatable-storage-type"><span>Tip diska</span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:235:                                                                    <select name="spec_lists[{{ $field->id }}][]" data-repeatable-input>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:236:                                                                        <option value="">Izaberi disk</option>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:244:                                                                    <input name="spec_lists[{{ $field->id }}][]" maxlength="255" value="{{ $storageType }}" placeholder="Npr. NVMe SSD" data-repeatable-input>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:247:                                                            <label class="repeatable-storage-capacity"><span>Kapacitet (GB)</span><input name="spec_capacities[{{ $field->id }}][]" type="number" min="0" max="10000000" step="1" inputmode="numeric" value="{{ $storageCapacity }}" placeholder="512" data-repeatable-capacity></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:248:                                                            <button class="repeatable-remove" type="button" data-repeatable-remove aria-label="Ukloni disk"><x-icon name="x" size="17" /></button>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:254:                                                        <label class="repeatable-storage-type"><span>Tip diska</span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:256:                                                                <select name="spec_lists[{{ $field->id }}][]" data-repeatable-input>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:257:                                                                    <option value="">Izaberi disk</option>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:265:                                                                <input name="spec_lists[{{ $field->id }}][]" maxlength="255" placeholder="Npr. NVMe SSD" data-repeatable-input>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:268:                                                        <label class="repeatable-storage-capacity"><span>Kapacitet (GB)</span><input name="spec_capacities[{{ $field->id }}][]" type="number" min="0" max="10000000" step="1" inputmode="numeric" placeholder="512" data-repeatable-capacity></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:269:                                                        <button class="repeatable-remove" type="button" data-repeatable-remove aria-label="Ukloni disk"><x-icon name="x" size="17" /></button>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:272:                                                <small class="muted">Do 8 diskova. Redosled unosa je redosled prikaza.</small>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:276:                                                            <span>Ukupan kapacitet diskova (GB)</span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:280:                                                        <small class="muted">Automatski zbir kapaciteta svih diskova iznad. Ovo polje se ne unosi ručno.</small>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:548:                control: () => firstNamed(['product_type_id']) || firstByPrefix(['specs[','spec_lists[','spec_structured[','spec_capacities[']),
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/images.blade.php:55:        @if($image->storage_disk === 'public')
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/partials/image-card.blade.php:36:        <small>{{ $image->storage_disk === 'legacy' ? 'Legacy · rotacija pravi lokalnu kopiju' : 'Laravel storage' }}</small>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/partials/image-card.blade.php:53:        @if($allowDelete && $image->storage_disk === 'public')
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/settings/index.blade.php:31:        <a href="#settings-interface">Izgled i bezbednost</a>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/settings/index.blade.php:146:        <section id="settings-interface" class="settings-hub-section" data-settings-section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/settings/modules.blade.php:59:            <p class="muted">Ova faza je namerno bezbedna: modul se skriva iz interfejsa i postojeći Mobile bootstrap feature flag se gasi kada postoji. Direktne URL rute, istorijski podaci, background poslovi i domain servisi se ne brišu niti prisilno zaustavljaju.</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/settings/appearance.blade.php:18:@include('admin.settings.partials.context-nav', ['settingsSection' => 'Izgled i interfejs'])
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/data-quality/index.blade.php:66:    <div><h2>Bezbedna automatska popravka</h2><p class="muted">Usklađuje kategorije tipova, čisti zastarele specifikacione veze, preračunava diskove i kompletnost, normalizuje glavne slike. Ne briše artikle, slike ni poslovnu istoriju.</p></div>
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_08_10_000101_create_shipment_courier_foundation_vnext.php:102:                $table->string('proof_disk', 40)->nullable();
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_30_000019_create_after_sales_cases_beta7_10.php:117:            $table->string('disk', 40)->default('local');
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php:5:use App\Services\StorageSpecificationService;
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php:35:        app(StorageSpecificationService::class)->repair();
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_30_000017_add_delivery_workflow_beta7_8.php:104:            $table->string('proof_disk', 40)->nullable();
--- MOBILE DISK STORAGE REPEATER AND SPEC HITS ---
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:462:  interface FormData {
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:715:  storage_repeater: {
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:721:    capacity_unit: 'GB';
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:770:    specialized_storage_repeater: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:795:  spec_lists?: Record<string, string[]>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:796:  spec_capacities?: Record<string, number[]>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:797:  spec_structured?: Record<string, Array<{ type: string; capacity_gb?: number }>>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(auth)/login.tsx:95:          <Text style={styles.heroCopy}>Katalog, porudžbine i obaveštenja u brzom mobilnom interfejsu.</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:94:  capacityGb: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:98:  return { type: '', capacityGb: '' };
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:209:  const storageFields = specificationFields.filter((field) => field.storage_repeater?.enabled);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:211:    storageFields.map((field) => field.storage_repeater?.total_field_id).filter((id): id is number => typeof id === 'number'),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:214:    (field) => !field.storage_repeater?.enabled && !field.read_only_derived && !storageDerivedFieldIds.has(field.id),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:240:          || key.startsWith('spec_lists.')
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:241:          || key.startsWith('spec_capacities.')
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:242:          || key.startsWith('spec_structured.')
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:259:      if (field.storage_repeater?.enabled) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:299:    const repeater = field.storage_repeater;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:319:    .reduce((sum, row) => sum + (nonNegativeInteger(row.capacityGb) ?? 0), 0);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:378:      const hasEnteredRow = rows.some((row) => row.type.trim() || row.capacityGb.trim());
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:380:        nextErrors[`spec_lists.${field.id}`] = `${field.name} je obavezno polje.`;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:384:          nextErrors[`spec_lists.${field.id}.${index}`] = 'Izaberi tip diska.';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:386:        const capacity = nonNegativeInteger(row.capacityGb);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:387:        if (capacity === null || capacity > 10000000) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:388:          nextErrors[`spec_capacities.${field.id}.${index}`] = 'Kapacitet mora biti ceo broj od 0 do 10000000 GB.';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:408:    const cleanSpecStructured: Record<string, Array<{ type: string; capacity_gb?: number }>> = {};
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:420:        .filter((row) => row.type.trim() || row.capacityGb.trim())
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:423:          capacity_gb: nonNegativeInteger(row.capacityGb) ?? 0,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:429:      cleanSpecCapacities[key] = rows.map((row) => row.capacity_gb);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:452:      spec_lists: Object.keys(cleanSpecLists).length ? cleanSpecLists : undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:453:      spec_capacities: Object.keys(cleanSpecCapacities).length ? cleanSpecCapacities : undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:454:      spec_structured: Object.keys(cleanSpecStructured).length ? cleanSpecStructured : undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:478:        <Text style={styles.noticeTitle}>Batch 2B · Diskovi i automatski kapacitet</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:480:          Multi-disk polje prati isti CMS storage model: do 8 diskova, redosled unosa je redosled prikaza, a ukupan kapacitet je samo pregled i ponovo ga računa backend.
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:552:            const repeater = field.storage_repeater;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:557:            const groupError = errors[`spec_lists.${field.id}`] ?? errors[`spec_structured.${field.id}`] ?? errors[`specs.${field.id}`];
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:564:                    <Text style={styles.help}>Do {repeater.max_items} diskova · redosled unosa je redosled prikaza.</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:569:                      {total} {repeater.total_unit ?? repeater.capacity_unit}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:578:                    <Text style={styles.storageRowTitle}>Disk {index + 1}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:580:                      label="Tip diska"
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:582:                      placeholder="Izaberi tip diska"
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:588:                      error={errors[`spec_lists.${field.id}.${index}`] ?? errors[`spec_structured.${field.id}.${index}.type`]}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:591:                      label={`Kapacitet (${repeater.capacity_unit})`}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:592:                      value={row.capacityGb}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:593:                      onChangeText={(next) => updateStorageRow(field.id, index, { capacityGb: next })}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:595:                      error={errors[`spec_capacities.${field.id}.${index}`] ?? errors[`spec_structured.${field.id}.${index}.capacity_gb`]}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:598:                    {rows.length > 1 || row.type || row.capacityGb ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:601:                        accessibilityLabel={`Ukloni disk ${index + 1}`}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:604:                        <Text style={styles.remove}>Ukloni disk</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:614:                  Dodaj još jedan disk ({rows.length}/{repeater.max_items})
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:266:assert(!/\b(?:submitted_by|verified_by|rejected_by|voided_by|confirmed_by|issued_by|proof_path|proof_disk|payment_batch_id)\b/.test(orderPostCreateTypeScope), 'Order post-create Mobile types do not expose internal actor IDs or storage paths.');
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:284:assert(!/api\.orders\.(?:cancel|postCreate|submitPaymentProof)|completeOrder|reopenOrder|verifyPayment|rejectPayment|voidPayment|reassign|internal_note|proof_path|proof_disk/.test(`${assignedOrdersListScreen}\n${assignedOrdersDetailScreen}`), 'Assigned Orders UI ostaje read-only i ne izlaže owner post-create ili admin workflow mutacije/interne storage podatke.');
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:294:assert(!/\b(?:verifyPayment|rejectPayment|voidPayment|completeOrder|reopenOrder|submitted_by|verified_by|rejected_by|voided_by|confirmed_by|issued_by|proof_path|proof_disk|admin\.)\b/.test(`${orderDetailScreen}\n${orderPostCreateFileHelper}`), 'Order customer post-create UI/helper ne izlažu admin akcije, actor ID-jeve ili storage putanje.');
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:439:assert(!/\b(?:submitted_by|verified_by|rejected_by|voided_by|confirmed_by|issued_by|proof_path|proof_disk|verifyOrderPayment|rejectOrderPayment|voidOrderPayment|completeOrder|reopenOrder)\b/.test(orderPostCreateOpenApiScope), 'Order post-create OpenAPI does not expose internal actor/storage fields or admin workflow actions.');
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:641:    && adminProductCreateBatch2TypesV06.includes('spec_structured?'),
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:657:    && adminProductCreateBatch2BScreenV06.includes('Dodaj još jedan disk')
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:660:  'Admin Product Create ima specijalizovani multi-disk repeater i skriva izvedeni total iz standardnih polja.',
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:668:    && adminProductCreateBatch2BInputBlockV06.includes('spec_lists: Object.keys(cleanSpecLists)')
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:669:    && adminProductCreateBatch2BInputBlockV06.includes('spec_capacities: Object.keys(cleanSpecCapacities)')
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:670:    && adminProductCreateBatch2BInputBlockV06.includes('spec_structured: Object.keys(cleanSpecStructured)')
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:673:  'Storage repeater šalje canonical specs/spec_lists/spec_capacities/spec_structured payload bez ručnog derived total-a.',
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:677:    && adminProductCreateBatch2BTypesV06.includes('storage_repeater: {')
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:678:    && adminProductCreateBatch2BTypesV06.includes("capacity_unit: 'GB'"),
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:679:  'Mobile tipovi izlažu server-driven storage repeater i read-only derived metadata.',
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:683:    && adminProductCreateBatch2BOpenApiV06.includes('storage_repeater:')
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:685:    && adminProductCreateBatch2BOpenApiV06.includes('capacity_unit: { type: string, enum: [GB] }'),
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:686:  'OpenAPI dokumentuje server-driven storage repeater metadata i derived total polje.',
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3772:      required: [id, name, slug, data_type, detail_input_enabled, required, read_only_derived, storage_repeater, options]
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3788:        storage_repeater:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3790:          required: [enabled, total_field_id, total_field_name, total_unit, max_items, capacity_unit]
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3797:            capacity_unit: { type: string, enum: [GB] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3889:          required: [advanced_specifications, image_upload, specialized_storage_repeater]
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3893:            specialized_storage_repeater: { type: boolean }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3930:        spec_lists:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3936:        spec_capacities:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3942:        spec_structured:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3952:                capacity_gb: { type: integer, minimum: 0, maximum: 10000000 }

============================================================
4. PURCHASE PRICE + SUPERADMIN INVENTORY KPI SOURCE MAP
============================================================
--- CMS COST / PURCHASE PRICE / INVENTORY VALUE HITS ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:67:                        'unit_cost_rsd' => $row['unit_cost_rsd'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:167:    /** @param list<mixed> $raw @return list<array{product_id:int,quantity?:int,counted_quantity?:int,unit_cost_rsd:?float,note:?string}> */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:179:                'unit_cost_rsd' => isset($row['unit_cost_rsd']) && $row['unit_cost_rsd'] !== '' ? (float) $row['unit_cost_rsd'] : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:177:        if ((float) ($lockedProduct->purchase_price_rsd ?? 0) > 0) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:178:            $purchaseUnit = round((float) $lockedProduct->purchase_price_rsd, 2);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:24:        'products' => ['id', 'sku', 'name', 'stock_quantity', 'purchase_price_rsd'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:135:            'gross_margin_percent' => $knownRevenue > 0 ? round($gross / $knownRevenue * 100, 2) : 0.0,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:140:            'net_margin_percent' => $knownRevenue > 0 ? round($net / $knownRevenue * 100, 2) : 0.0,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:190:                    'gross_margin_percent' => $known > 0 ? round($gross / $known * 100, 2) : 0.0,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:231:            ->select('id', 'sku', 'name', 'stock_quantity', 'purchase_price_rsd', 'created_at')->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:233:            $rows->push($this->inventoryRow($product->id, $product->sku, $product->name, (int) $product->stock_quantity, $product->purchase_price_rsd, $product->created_at));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:307:        fputcsv($handle, ['SEGMENTI', 'Porudžbine', 'Komada', 'Prihod RSD', 'Nabavna vrednost RSD', 'Bruto dobit RSD', 'Bruto marža %', 'Pokrivenost troška %'], ';');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:308:        foreach ($report['segments'] as $row) fputcsv($handle, [$row['label'], $row['orders_count'], $row['units_count'], $row['revenue_rsd'], $row['cogs_rsd'], $row['gross_profit_rsd'], $row['gross_margin_percent'], $row['cost_coverage_percent']], ';');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:399:            'unit_cost_rsd' => $hasCost ? round((float) $cost, 2) : 0.0,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:17:final class OrderItemCostSnapshotService
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:63:            $row->candidate_unit_rsd = $candidate['unit_cost_rsd'] ?? null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:119:                        Log::error('Automatska dopuna snapshot nabavne cene nije uspela.', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:142:     * @return array{item_id:int,order_id:int,unit_cost_rsd:float,total_cost_rsd:float,source:string}
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:168:                'unit_cost_rsd' => round($unitCostRsd, 2),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:176:                'unit_cost_rsd' => round($unitCostRsd, 2),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:202:     * @return array{unit_cost_rsd:float,source:string}|null
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:213:                'unit_cost_rsd' => $existingUnit,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:222:                'unit_cost_rsd' => round($existingTotal / $quantity, 2),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:237:            return ['unit_cost_rsd' => $historicalReceipt, 'source' => 'repair_receipt_historical'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:240:        if (Schema::hasTable('products') && Schema::hasColumn('products', 'purchase_price_rsd')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:241:            $productCost = $this->positive(DB::table('products')->where('id', $productId)->value('purchase_price_rsd'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:243:                return ['unit_cost_rsd' => $productCost, 'source' => 'repair_product_current'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:260:            ->whereNotNull('sri.unit_cost_rsd')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:261:            ->where('sri.unit_cost_rsd', '>', 0);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:271:            ->value('sri.unit_cost_rsd');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:277:     * @param array{unit_cost_rsd:float,source:string} $candidate
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:285:        $unitCost = round($candidate['unit_cost_rsd'], 2);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:300:            $manual ? 'order_item.cost_snapshot.manual' : 'order_item.cost_snapshot.repaired',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderItemCostSnapshotService.php:301:            ($manual ? 'Ručna' : 'Automatska').' dopuna nabavne cene za stavku porudžbine #'.$item->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/ManagementReportPdfService.php:24:            $pdf->text(455, $top + 9, number_format((float) ($row['gross_margin_percent'] ?? 0), 1, ',', '.').'%', 7.5, true, '#202735', 'right');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/ManagementReportPdfService.php:108:            ['Vrednost lagera', $this->money((float) ($inventory['value_rsd'] ?? 0)), 'Bez nabavne cene: '.(int) ($inventory['missing_cost_items'] ?? 0)],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:141:            $purchaseUnitRsd = $product->purchase_price_rsd !== null && (float) $product->purchase_price_rsd > 0
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:142:                ? round((float) $product->purchase_price_rsd, 2)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:158:                'purchase_price_rsd' => $copyPrice ? $source->purchase_price_rsd : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:424:            'id','sku','name','slug','product_type_id','brand_id','product_line_id','model_name','price_amount','price_currency','purchase_price_rsd',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:107:                'unit_cost_snapshot_rsd' => $part->average_cost_rsd,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:230:            $totalCost += $consumed * (float) $line->unit_cost_snapshot_rsd;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:302:                $unitCost = round((float) ($row['unit_cost_rsd'] ?? $part->average_cost_rsd), 2);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:308:                    'unit_cost_rsd' => $unitCost,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:429:            $newValue = $qty * (float) $item->unit_cost_rsd;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:430:            $average = $stockAfter > 0 ? round(($oldValue + $newValue) / $stockAfter, 2) : (float) $item->unit_cost_rsd;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:433:            $this->movement($part, null, $purchaseRequest, $actor, 'purchase_receipt', $qty, 0, $stockBefore, $stockAfter, $reservedBefore, $reservedBefore, 'Prijem po '.$purchaseRequest->request_number, $eventKey, true, (float) $item->unit_cost_rsd);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:486:            'unit_cost_rsd' => $unitCost ?? (float) $part->average_cost_rsd,
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ServicePartPurchaseRequestItem.php:12:    protected $fillable = ['purchase_request_id', 'service_part_id', 'ordered_quantity', 'received_quantity', 'unit_cost_rsd', 'notes'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ServicePartPurchaseRequestItem.php:13:    protected function casts(): array { return ['ordered_quantity' => 'decimal:3', 'received_quantity' => 'decimal:3', 'unit_cost_rsd' => 'decimal:2']; }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/StockReceiptItem.php:12:    protected $fillable = ['stock_receipt_id', 'product_id', 'product_sku', 'product_name', 'quantity', 'unit_cost_rsd', 'note'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/StockReceiptItem.php:13:    protected function casts(): array { return ['quantity' => 'integer', 'unit_cost_rsd' => 'decimal:2']; }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/FieldWorkOrderPart.php:12:    protected $fillable = ['field_work_order_id', 'service_part_id', 'supply_mode', 'part_sku_snapshot', 'part_name_snapshot', 'unit_snapshot', 'requested_quantity', 'reserved_quantity', 'consumed_quantity', 'unit_cost_snapshot_rsd', 'notes', 'created_by', 'updated_by'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/FieldWorkOrderPart.php:13:    protected function casts(): array { return ['requested_quantity' => 'decimal:3', 'reserved_quantity' => 'decimal:3', 'consumed_quantity' => 'decimal:3', 'unit_cost_snapshot_rsd' => 'decimal:2']; }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ServicePartMovement.php:13:    protected $fillable = ['event_key', 'service_part_id', 'field_work_order_id', 'purchase_request_id', 'user_id', 'movement_type', 'stock_change', 'reserved_change', 'stock_before', 'stock_after', 'reserved_before', 'reserved_after', 'unit_cost_rsd', 'note', 'metadata_json'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ServicePartMovement.php:14:    protected function casts(): array { return ['stock_change' => 'decimal:3', 'reserved_change' => 'decimal:3', 'stock_before' => 'decimal:3', 'stock_after' => 'decimal:3', 'reserved_before' => 'decimal:3', 'reserved_after' => 'decimal:3', 'unit_cost_rsd' => 'decimal:2', 'metadata_json' => 'array', 'created_at' => 'datetime']; }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:18:        'price_currency', 'purchase_price_rsd', 'manual_commission_eur', 'description', 'notes', 'stock_quantity',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:27:            'purchase_price_rsd' => 'decimal:2',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreReportScheduleRequest.php:18:            'report_type' => ['required', Rule::in(['management_summary', 'profitability', 'inventory', 'receivables', 'after_sales'])],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:50:            'purchase_price_rsd' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/ProductRequest.php:294:            'purchase_price_rsd' => $this->filled('purchase_price_rsd') ? str_replace(',', '.', (string) $this->input('purchase_price_rsd')) : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/StoreServicePartPurchaseRequest.php:22:            'items.*.unit_cost_rsd' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportController.php:333:            .'<title>Izveštaji — recovery</title><style>body{margin:0;background:#252b31;color:#f4f6f8;font-family:Arial,sans-serif}.wrap{max-width:760px;margin:8vh auto;padding:24px}.card{background:#30373e;border:1px solid #505b66;border-radius:20px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,.25)}h1{margin-top:0}.code{display:inline-block;background:#20262b;border-radius:10px;padding:7px 10px;color:#ffb000}.cmd{display:block;white-space:pre-wrap;background:#20262b;padding:14px;border-radius:12px;margin:18px 0;color:#dce7f2}a{color:#60a5fa}</style></head><body><div class="wrap"><div class="card">'
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:87:            'items.*.unit_cost_rsd' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:410:            .'<style>body{margin:0;background:#20262d;color:#eef2f7;font-family:Arial,sans-serif}main{max-width:960px;margin:4vh auto;padding:28px}.card{border:1px solid #46515e;border-radius:18px;background:#2b323a;padding:24px;margin-bottom:18px}.warn{background:#4b351d;border-color:#8b642d}a{color:#8bc6ff}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:10px;border-bottom:1px solid #46515e}</style></head><body><main data-order-detail-fallback="1">'
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:420:        return '<!doctype html><html lang="sr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($title).'</title></head><body style="margin:0;background:#20262d;color:#eef2f7;font-family:Arial,sans-serif"><main style="max-width:760px;margin:8vh auto;padding:32px;border:1px solid #46515e;border-radius:18px;background:#2b323a"><h1>'.e($title).'</h1><p>'.$instruction.'</p><p>Incident: <strong>'.e($incident).'</strong></p></main></body></html>';
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:166:            'summary' => ['revenue_rsd' => 0.0, 'gross_profit_rsd' => 0.0, 'net_contribution_rsd' => 0.0, 'gross_margin_percent' => 0.0, 'cost_coverage_percent' => 0.0, 'orders_count' => 0, 'outstanding_rsd' => 0.0],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ServicePartsController.php:286:                $cost = $this->number($attrs['unit_cost_rsd'] ?? null);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ServicePartsController.php:292:                    'unit_cost_rsd' => $cost,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReportController.php:21:        'profitability',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/InventoryController.php:172:            'items.*.unit_cost_rsd' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FieldOperationsController.php:318:            'unit_cost_snapshot_rsd' => $this->numericValue($attrs['unit_cost_snapshot_rsd'] ?? null),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:153:                    '<!doctype html><html lang="sr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Porudžbina u bezbednom režimu</title></head><body style="margin:0;background:#20262d;color:#eef2f7;font-family:Arial,sans-serif"><main style="max-width:760px;margin:8vh auto;padding:32px;border:1px solid #46515e;border-radius:18px;background:#2b323a"><h1>Porudžbina je otvorena u bezbednom režimu</h1><p>Administrator može proveriti detalj komandom <code>php artisan app:orders-doctor --render --order-id='.(int) $order->getKey().'</code>.</p><p>Incident: <strong>'.e($incident).'</strong></p></main></body></html>',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/OrderController.php:228:            .'<style>body{margin:0;background:#20262d;color:#eef2f7;font-family:Arial,sans-serif}main{max-width:960px;margin:4vh auto;padding:28px}.card{border:1px solid #46515e;border-radius:18px;background:#2b323a;padding:24px;margin-bottom:18px}.warn{background:#4b351d;border-color:#8b642d}a{color:#8bc6ff}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:10px;border-bottom:1px solid #46515e}</style></head><body><main data-order-user-detail-fallback="1">'
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:32:        'stock_receipt_items' => ['stock_receipt_id', 'product_id', 'quantity', 'unit_cost_rsd'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:118:        'service_part_purchase_request_items' => ['id', 'purchase_request_id', 'service_part_id', 'ordered_quantity', 'received_quantity', 'unit_cost_rsd'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:129:        'products' => ['id', 'product_type_id', 'brand_id', 'product_line_id', 'model_name', 'purchase_price_rsd', 'completeness_percent', 'name_is_manual', 'source_product_id'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:7:use App\Services\OrderItemCostSnapshotService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:21:    protected $description = 'Auditira i bezbedno dopunjava snapshotove nabavnih cena stavki porudžbina.';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:23:    public function handle(OrderItemCostSnapshotService $snapshots): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:61:    private function manual(OrderItemCostSnapshotService $snapshots): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:81:            'Stavka #%d porudžbine #%d: nabavna cena %.2f RSD, ukupno %.2f RSD.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:84:            $result['unit_cost_rsd'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:92:    private function audit(OrderItemCostSnapshotService $snapshots): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:99:            $this->info('PASS Sve stavke porudžbina imaju kompletan snapshot nabavne cene.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderCostSnapshotsCommand.php:105:        $this->warn('Nepotpuni snapshotovi nabavne cene: '.$missing.'.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:9:use App\Services\OrderItemCostSnapshotService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:20:    protected $description = 'Proverava profitabilnost, nabavne snapshotove, rasporede i izvoz upravljačkih izveštaja.';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:22:    public function handle(ManagementReportService $reports, OrderItemCostSnapshotService $costSnapshots): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:34:                    $this->line('<fg=green>PASS</> Dopunjeni snapshotovi nabavne cene: '.$repair['repaired'].'.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:48:            'products' => ['purchase_price_rsd'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ManagementReportsDoctorCommand.php:81:        $this->line(($missingCosts > 0 ? '<fg=yellow>WARN</>' : '<fg=green>PASS</>').' Stavke bez kompletne nabavne cene: '.$missingCosts.'.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ServicePartsDoctorCommand.php:43:            'service_part_purchase_request_items' => ['id', 'purchase_request_id', 'service_part_id', 'ordered_quantity', 'received_quantity', 'unit_cost_rsd'],
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:58:        .header-product-search-dialog{position:relative;width:min(680px,calc(100vw - 24px));max-height:min(680px,calc(100dvh - 100px));margin:clamp(72px,9vh,108px) auto 0;display:flex;flex-direction:column;overflow:hidden;border:1px solid var(--line);border-radius:22px;background:var(--panel);box-shadow:0 28px 90px rgba(0,0,0,.28)}
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:68:        .header-product-search-group{display:flex;align-items:center;gap:8px;margin:9px 4px 4px;color:var(--muted);font-size:11px;font-weight:900;letter-spacing:.07em;text-transform:uppercase}
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:75:        .header-product-search-copy small{margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--muted);font-size:12px}
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:78:        @media(max-width:1250px){.header-product-search-toggle{width:44px;height:44px}.header-product-search-dialog{width:calc(100vw - 20px);max-height:calc(100dvh - 76px);margin:66px auto 0;border-radius:18px}.header-product-search-head{padding:10px}.header-product-search-body{padding:7px}}
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:91:        .exchange-rate-sync-status{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:345:                    <label><span>Nabavna cena RSD</span><input name="purchase_price_rsd" type="number" step="0.01" min="0" value="{{ old('purchase_price_rsd',$product->purchase_price_rsd) }}"><small>Koristi se za obračun marže i snapshotuje se pri prodaji.</small></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:5:<div class="page-heading"><div><span class="eyebrow">Management Analytics</span><h1>Izveštaji i profitabilnost</h1><p>Promet, bruto marža, provizije, refundacije, servisni troškovi, lager i potraživanja na jednom mestu.</p></div>@can('reports.export')<div class="report-export-actions"><a class="button button-ghost" href="{{ route('admin.reports.management.csv',request()->query()) }}"><x-icon name="download" /> CSV</a><a class="button button-primary" target="_blank" rel="noopener" href="{{ route('admin.reports.management.pdf',request()->query()) }}"><x-icon name="file-text" /> PDF</a></div>@endcan</div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:24:<div class="analytics-card"><small>Bruto dobit</small><strong class="{{ $s['gross_profit_rsd']>=0?'analytics-good':'analytics-bad' }}">{{ number_format($s['gross_profit_rsd'],2,',','.') }} RSD</strong><em>Bruto marža {{ number_format($s['gross_margin_percent'],2,',','.') }}%</em></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:25:<div class="analytics-card"><small>Neto doprinos</small><strong class="{{ $s['net_contribution_rsd']>=0?'analytics-good':'analytics-bad' }}">{{ number_format($s['net_contribution_rsd'],2,',','.') }} RSD</strong><em>Neto marža {{ number_format($s['net_margin_percent'],2,',','.') }}%</em></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:26:<div class="analytics-card"><small>Pokrivenost nabavne cene</small><strong class="{{ $s['cost_coverage_percent']>=95?'analytics-good':'analytics-warning' }}">{{ number_format($s['cost_coverage_percent'],1,',','.') }}%</strong><em>{{ $s['missing_cost_lines'] }} stavki bez troška · {{ number_format($s['revenue_missing_cost_rsd'],2,',','.') }} RSD prometa</em></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:34:<section class="panel form-section"><h2>Kvalitet finansijskih podataka</h2><dl class="detail-list"><dt>Pokriven promet</dt><dd>{{ number_format($s['known_revenue_rsd'],2,',','.') }} RSD</dd><dt>Promet bez nabavne cene</dt><dd>{{ number_format($s['revenue_missing_cost_rsd'],2,',','.') }} RSD</dd><dt>Stavke bez troška</dt><dd>{{ $s['missing_cost_lines'] }}</dd><dt>Preporuka</dt><dd>@if($s['cost_coverage_percent']<95)<span class="analytics-warning">Dopuniti nabavne cene proizvoda.</span>@else<span class="analytics-good">Podaci su dovoljno pokriveni.</span>@endif</dd></dl></section></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:36:<section class="panel form-section"><div class="section-heading-row"><div><h2>Profitabilnost po segmentu</h2><p class="muted">Grupisano: {{ ['brand'=>'brend','line'=>'linija','type'=>'tip artikla','product'=>'proizvod','admin'=>'odgovorno lice'][$filters['group_by']] }}</p></div></div><div class="admin-table-wrap"><table class="admin-table analytics-table"><thead><tr><th>Segment</th><th>Porudžbine</th><th>Komada</th><th>Prihod</th><th>Nabavna vrednost</th><th>Bruto dobit</th><th>Marža</th><th>Pokrivenost</th></tr></thead><tbody>@forelse($report['segments'] as $row)<tr><td><strong>{{ $row['label'] }}</strong></td><td>{{ $row['orders_count'] }}</td><td>{{ $row['units_count'] }}</td><td>{{ number_format($row['revenue_rsd'],2,',','.') }} RSD</td><td>{{ number_format($row['cogs_rsd'],2,',','.') }} RSD</td><td class="{{ $row['gross_profit_rsd']>=0?'analytics-good':'analytics-bad' }}">{{ number_format($row['gross_profit_rsd'],2,',','.') }} RSD</td><td>{{ number_format($row['gross_margin_percent'],1,',','.') }}%</td><td>{{ number_format($row['cost_coverage_percent'],1,',','.') }}%</td></tr>@empty<tr><td colspan="8">Nema podataka.</td></tr>@endforelse</tbody></table></div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:38:<div class="analytics-three"><section class="panel form-section"><h2>Lager i kapital</h2><dl class="detail-list"><dt>Vrednost lagera</dt><dd><strong>{{ number_format($report['inventory']['value_rsd'],2,',','.') }} RSD</strong></dd><dt>Komada</dt><dd>{{ $report['inventory']['units_count'] }}</dd><dt>Bez nabavne cene</dt><dd>{{ $report['inventory']['missing_cost_items'] }}</dd><dt>Spori lager 90+ dana</dt><dd>{{ $report['inventory']['slow_items'] }}</dd></dl><h3>Starost vrednosti lagera</h3><div class="analytics-aging">@foreach(['0_30'=>'0–30','31_60'=>'31–60','61_90'=>'61–90','91_180'=>'91–180','over_180'=>'180+'] as $key=>$label)<div><small>{{ $label }} dana</small><strong>{{ number_format($report['inventory']['aging'][$key]??0,0,',','.') }}</strong></div>@endforeach</div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:42:<section class="panel form-section"><div class="section-heading-row"><div><h2>Najveća vrednost lagera</h2><p class="muted">Artikli koji vezuju najviše kapitala.</p></div><a class="button button-ghost button-small" href="{{ route('admin.reports.inventory.csv') }}">Postojeći lager CSV</a></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>SKU</th><th>Artikal</th><th>Količina</th><th>Jedinični trošak</th><th>Vrednost</th><th>Starost</th></tr></thead><tbody>@forelse(array_slice($report['inventory']['top_value'],0,15) as $row)<tr><td>{{ $row['sku'] }}</td><td>{{ $row['name'] }}</td><td>{{ $row['quantity'] }}</td><td>{{ $row['has_cost']?number_format($row['unit_cost_rsd'],2,',','.').' RSD':'Nedostaje' }}</td><td>{{ number_format($row['value_rsd'],2,',','.') }} RSD</td><td>{{ $row['age_days'] }} dana</td></tr>@empty<tr><td colspan="6">Nema lagera.</td></tr>@endforelse</tbody></table></div></section>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/reports/management.blade.php:47:<label class="span-2"><span>Naziv rasporeda</span><input name="name" required placeholder="Nedeljni upravljački izveštaj"></label><label><span>Tip</span><select name="report_type"><option value="management_summary">Kompletan upravljački</option><option value="profitability">Profitabilnost</option><option value="inventory">Lager</option><option value="receivables">Potraživanja</option><option value="after_sales">Postprodaja</option></select></label><label><span>Učestalost</span><select name="frequency"><option value="daily">Dnevno</option><option value="weekly" selected>Nedeljno</option><option value="monthly">Mesečno</option></select></label>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/service-parts/purchase-requests.blade.php:7:<template data-purchase-item-template><div class="purchase-item-row"><select name="items[__INDEX__][service_part_id]" required><option value="">Izaberi deo</option>@foreach($parts as $part)<option value="{{ $part->id }}">{{ $part->sku }} · {{ $part->name }}</option>@endforeach</select><input type="number" name="items[__INDEX__][ordered_quantity]" min="0.001" step="0.001" placeholder="Količina" required><input type="number" name="items[__INDEX__][unit_cost_rsd]" min="0" step="0.01" placeholder="Cena RSD"><button type="button" class="button button-danger button-small" data-remove-purchase-item>×</button></div></template>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/service-parts/purchase-show.blade.php:5:<div class="settings-grid"><section class="panel form-section"><h2>Stavke</h2><div class="table-wrap"><table><thead><tr><th>Deo</th><th>Poručeno</th><th>Primljeno</th><th>Cena</th><th>Ukupno</th></tr></thead><tbody>@foreach($purchaseRequest->items as $item)<tr><td><strong>{{ $item->part?->name }}</strong><small>{{ $item->part?->sku }}</small></td><td>{{ number_format((float)$item->ordered_quantity,3,',','.') }} {{ $item->part?->unit }}</td><td>{{ number_format((float)$item->received_quantity,3,',','.') }}</td><td>{{ number_format((float)$item->unit_cost_rsd,2,',','.') }} RSD</td><td>{{ number_format((float)$item->ordered_quantity*(float)$item->unit_cost_rsd,2,',','.') }} RSD</td></tr>@endforeach</tbody></table></div></section><aside class="form-side"><section class="panel form-section"><h2>Podaci</h2><dl class="detail-list"><dt>Dobavljač</dt><dd>{{ $purchaseRequest->supplier?->name ?? '—' }}</dd><dt>Referenca</dt><dd>{{ $purchaseRequest->supplier_reference ?: '—' }}</dd><dt>Očekivano</dt><dd>{{ $purchaseRequest->expected_at?->format('d.m.Y') ?? '—' }}</dd><dt>Vrednost</dt><dd><strong>{{ number_format((float)$purchaseRequest->total_cost_rsd,2,',','.') }} RSD</strong></dd></dl>@if($purchaseRequest->notes)<p>{{ $purchaseRequest->notes }}</p>@endif</section><section class="panel form-section"><h2>Akcije</h2><div class="form-grid">@if($purchaseRequest->status==='draft')<form method="post" action="{{ route('admin.service-part-purchases.submit',$purchaseRequest) }}">@csrf<button class="button button-primary full-width">Označi kao poslato</button></form>@endif @if($purchaseRequest->status==='submitted')<form method="post" action="{{ route('admin.service-part-purchases.order',$purchaseRequest) }}">@csrf<button class="button button-primary full-width">Označi kao poručeno</button></form>@endif @if($purchaseRequest->status==='ordered')<form method="post" action="{{ route('admin.service-part-purchases.receive',$purchaseRequest) }}" data-confirm="Potvrditi prijem svih stavki na servisni lager?">@csrf<button class="button button-primary full-width">Primi sve stavke</button></form>@endif @unless($purchaseRequest->isTerminal())<form method="post" action="{{ route('admin.service-part-purchases.cancel',$purchaseRequest) }}">@csrf<label><span>Razlog otkazivanja</span><textarea name="cancellation_reason" required minlength="5"></textarea></label><button class="button button-danger full-width">Otkaži zahtev</button></form>@endunless</div></section></aside></div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/inventory/index.blade.php:81:                                        <td data-label="Nabavna cena RSD"><input type="number" min="0" step="0.01" name="items[{{ $i }}][unit_cost_rsd]" placeholder="opciono"></td>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/management-report.blade.php:1:<!doctype html><html lang="sr"><body style="margin:0;background:#f3f5f8;font-family:Arial,sans-serif;color:#202735"><table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td style="padding:28px"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;margin:auto;background:#fff;border-radius:16px;overflow:hidden"><tr><td style="background:#172033;color:#fff;padding:24px 30px"><strong style="font-size:20px">{{ config('app.name','Ald1n CMS') }}</strong><div style="color:#cbd5e1;margin-top:6px">Upravljački izveštaj</div></td></tr><tr><td style="padding:30px"><p style="margin-top:0">U prilogu se nalazi izveštaj za period <strong>{{ $delivery->period_from?->format('d.m.Y') }} – {{ $delivery->period_to?->format('d.m.Y') }}</strong>.</p><table width="100%" cellspacing="0" cellpadding="10" style="background:#f8fafc;border:1px solid #dce2ea;border-radius:12px"><tr><td>Prihod</td><td align="right"><strong>{{ number_format((float)($summary['revenue_rsd'] ?? 0),2,',','.') }} RSD</strong></td></tr><tr><td>Bruto dobit</td><td align="right"><strong>{{ number_format((float)($summary['gross_profit_rsd'] ?? 0),2,',','.') }} RSD</strong></td></tr><tr><td>Neto doprinos</td><td align="right"><strong>{{ number_format((float)($summary['net_contribution_rsd'] ?? 0),2,',','.') }} RSD</strong></td></tr><tr><td>Pokrivenost nabavne cene</td><td align="right"><strong>{{ number_format((float)($summary['cost_coverage_percent'] ?? 0),1,',','.') }}%</strong></td></tr></table><p style="color:#667085;font-size:12px;margin-bottom:0;margin-top:24px">Poruka je automatski poslata iz {{ config('app.name','Ald1n CMS') }} sistema.</p></td></tr></table></td></tr></table></body></html>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/order-events.blade.php:12:<body style="margin:0;background:#f3f5f8;font-family:Arial,sans-serif;color:#202735">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/order-events.blade.php:15:<tr><td style="background:#172033;color:#fff;padding:24px 30px"><strong style="font-size:20px">{{ config('app.name','Ald1n CMS') }}</strong><div style="color:#cbd5e1;margin-top:6px">{{ $headerLabel }}</div></td></tr>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/order-events.blade.php:17:<p style="margin-top:0">Poštovani{{ $recipientName ? ' '.$recipientName : '' }},</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/order-events.blade.php:27:    <div style="border:1px solid #dce2ea;border-radius:12px;padding:18px;margin:16px 0;background:#f8fafc;overflow:hidden">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/order-events.blade.php:28:        <strong style="display:block;font-size:18px;margin-bottom:12px">{{ $event->subject }}</strong>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/order-events.blade.php:31:            <div style="margin:-2px -2px 16px;background:#fff;border:1px solid #e5eaf0;border-radius:10px;padding:12px;text-align:center">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/order-events.blade.php:32:                <img src="{{ $productImage }}" alt="{{ $metadata['product_name'] ?? $event->subject }}" width="560" style="display:block;width:100%;max-width:560px;max-height:420px;height:auto;object-fit:contain;margin:0 auto;border:0">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/order-events.blade.php:37:            @if($productSku !== '')<div style="color:#667085;font-size:12px;margin-bottom:8px">Šifra artikla: <strong style="color:#344054">{{ $productSku }}</strong></div>@endif
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/order-events.blade.php:39:                <p style="margin:0 0 14px;line-height:1.6;color:#344054">{{ $productDescription }}</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/order-events.blade.php:41:                <p style="margin:0 0 14px;line-height:1.55">{{ $event->message }}</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/order-events.blade.php:44:                <div style="margin:0 0 16px;padding:12px 14px;border-radius:10px;background:#eef5ff;color:#174ea6;font-size:18px;font-weight:700">Cena: {{ $productPrice }}</div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/order-events.blade.php:47:            <p style="margin:0 0 12px;line-height:1.55">{{ $event->message }}</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/emails/order-events.blade.php:55:<p style="color:#667085;font-size:12px;margin-bottom:0">Ova poruka je automatski poslata iz {{ config('app.name','Ald1n CMS') }} sistema.</p>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php:141:        <a class="dashboard-kpi-card" href="{{ route('admin.reports.index') }}"><span class="dashboard-kpi-icon tone-green"><x-icon name="money" /></span><div><small>Bruto dobit</small><strong>{{ number_format((float) ($reportSummary['gross_profit_rsd'] ?? 0), 2, ',', '.') }} RSD</strong><span>Marža {{ number_format((float) ($reportSummary['gross_margin_percent'] ?? 0), 1, ',', '.') }}%</span></div></a>
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php:30:        $this->addColumn('products', 'purchase_price_rsd', static fn (Blueprint $table) => $table->decimal('purchase_price_rsd', 14, 2)->nullable());
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php:31:        $this->index('products', ['purchase_price_rsd'], 'products_purchase_price_index');
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php:176:        DB::table('products')->whereNull('purchase_price_rsd')->orderBy('id')->chunkById(200, static function ($products): void {
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php:179:                    ->whereNotNull('unit_cost_rsd')->where('unit_cost_rsd', '>', 0)->latest('id')->value('unit_cost_rsd');
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php:180:                if ($cost !== null) DB::table('products')->where('id', $product->id)->update(['purchase_price_rsd' => $cost]);
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php:194:                    ->select('products.purchase_price_rsd', 'brands.name as brand_name', 'product_lines.name as line_name', 'product_types.name as type_name')->first();
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php:198:                    $variantCost = DB::table('product_variants')->where('id', $item->product_variant_id)->value('purchase_price_rsd');
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php:204:                if ($cost === null && $product && $product->purchase_price_rsd !== null && (float) $product->purchase_price_rsd > 0) {
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php:205:                    $cost = (float) $product->purchase_price_rsd;
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_23_000012_create_payments_advanced_inventory_beta3.php:129:                $table->decimal('unit_cost_rsd', 14, 2)->nullable();
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_08_19_091800_decommission_product_variants.php:138:                $table->decimal('purchase_price_rsd', 14, 2)->nullable();
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_30_000022_create_service_parts_procurement_beta7_13.php:96:            $table->decimal('unit_cost_snapshot_rsd', 15, 2)->default(0);
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_30_000022_create_service_parts_procurement_beta7_13.php:127:            $table->decimal('unit_cost_rsd', 15, 2)->default(0);
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_30_000022_create_service_parts_procurement_beta7_13.php:175:            $table->decimal('unit_cost_rsd', 15, 2)->default(0);
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php:47:                $table->decimal('purchase_price_rsd', 14, 2)->nullable();
/home/icaffeco/ald1n-project/apps/cms/current/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php:67:                'purchase_price_rsd' => static fn (Blueprint $t) => $t->decimal('purchase_price_rsd', 14, 2)->nullable(),
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/OrderCostSnapshotRepairContractTest.php:14:        $service = (string) file_get_contents($root.'/app/Services/OrderItemCostSnapshotService.php');
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/OrderCostSnapshotRepairContractTest.php:21:        self::assertStringContainsString('order_item.cost_snapshot.repaired', $service);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/OrderCostSnapshotRepairContractTest.php:22:        self::assertStringContainsString('order_item.cost_snapshot.manual', $service);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/OrderCostSnapshotRepairContractTest.php:27:        $service = (string) file_get_contents(dirname(__DIR__, 2).'/app/Services/OrderItemCostSnapshotService.php');
/home/icaffeco/ald1n-project/apps/cms/current/tests/Unit/OrderCostSnapshotRepairContractTest.php:38:        self::assertStringContainsString('OrderItemCostSnapshotService $costSnapshots', $doctor);
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/PaymentsAdvancedInventoryTest.php:206:            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_cost_rsd' => 700]],
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/OperationalOrdersCommissionsTest.php:83:            ->post('/admin/orders/'.$order->id.'/internal-notes', ['note' => 'Interna nabavna napomena.'])
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/OperationalOrdersCommissionsTest.php:97:            ->assertDontSee('Interna nabavna napomena.');
/home/icaffeco/ald1n-project/apps/cms/current/tests/Feature/ServicePartsWorkflowTest.php:88:            'items' => [['service_part_id' => $part->id, 'ordered_quantity' => 3, 'unit_cost_rsd' => 700]],
/home/icaffeco/ald1n-project/apps/cms/current/bin/order-cost-snapshot-smoke.php:13:$servicePath = $root.'/app/Services/OrderItemCostSnapshotService.php';
/home/icaffeco/ald1n-project/apps/cms/current/bin/order-cost-snapshot-smoke.php:23:$check('OrderItemCostSnapshotService postoji', $service !== '');
/home/icaffeco/ald1n-project/apps/cms/current/bin/order-cost-snapshot-smoke.php:29:$check('Ručna promena zahteva razlog i audit', str_contains($service, 'mb_strlen($reason) < 5') && str_contains($service, "Schema::hasTable('audit_logs')") && str_contains($service, 'order_item.cost_snapshot.manual'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/order-cost-snapshot-smoke.php:30:$check('Management doctor pokreće pravi repair servis', str_contains($doctor, 'OrderItemCostSnapshotService $costSnapshots') && str_contains($doctor, '$costSnapshots->repairMissing()'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/order-cost-snapshot-smoke.php:38:fwrite(STDOUT, sprintf("Order cost snapshot smoke: %d/%d uspešno.\n", count($checks) - $failed, count($checks)));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:43:    'app/Services/OrderItemCostSnapshotService.php',
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:772:$check('beta7.21 migracija uvodi nabavne snapshotove i rasporede', str_contains($managementMigration, 'purchase_total_rsd_snapshot') && str_contains($managementMigration, 'report_schedules') && str_contains($managementMigration, 'report_deliveries'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:774:$check('beta7.21 marža koristi snapshot i prikazuje pokrivenost troška', str_contains($managementService, 'known_revenue_rsd') && str_contains($managementService, 'cost_coverage_percent') && str_contains($managementService, 'purchase_total_rsd_snapshot'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:780:$check('beta7.21 UI ima CSV PDF rasporede i cost coverage', str_contains($managementView, 'reports.management.csv') && str_contains($managementView, 'report-schedules.store') && str_contains($managementView, 'Pokrivenost nabavne cene'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:855:$orderCostService = (string) file_get_contents($root.'/app/Services/OrderItemCostSnapshotService.php');
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:864:$check('beta7.24.1 ručna finansijska promena zahteva razlog i audit', str_contains($orderCostService, 'mb_strlen($reason) < 5') && str_contains($orderCostService, "Schema::hasTable('audit_logs')") && str_contains($orderCostService, 'order_item.cost_snapshot.manual'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:865:$check('beta7.24.1 audit/repair komanda i regresije postoje', str_contains($orderCostCommand, 'app:order-cost-snapshots') && str_contains($orderCostSmoke, 'Order cost snapshot smoke') && str_contains($orderCostContract, 'test_repair_is_conservative_and_uses_audited_sources'));
/home/icaffeco/ald1n-project/apps/cms/current/bin/management-report-smoke.php:27:        ['label' => 'HP', 'orders_count' => 12, 'units_count' => 15, 'revenue_rsd' => 750000.00, 'gross_profit_rsd' => 190000.00, 'gross_margin_percent' => 25.33, 'cost_coverage_percent' => 100.0],
/home/icaffeco/ald1n-project/apps/cms/current/bin/management-report-smoke.php:28:        ['label' => 'Lenovo', 'orders_count' => 8, 'units_count' => 9, 'revenue_rsd' => 450000.00, 'gross_profit_rsd' => 110000.00, 'gross_margin_percent' => 24.44, 'cost_coverage_percent' => 91.0],
--- MOBILE DASHBOARD / MANAGEMENT KPI HITS ---
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/orders/order-card.tsx:158:      marginTop: 3,
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/orders/order-card.tsx:180:      marginTop: 2,
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/orders/order-card.tsx:186:      marginTop: 2,
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/ui/status-timeline.tsx:9:function createStyles(theme:AppColors){return StyleSheet.create({row:{flexDirection:'row',gap:spacing.md},rail:{width:18,alignItems:'center'},dot:{width:12,height:12,borderRadius:999,marginTop:4},line:{width:2,flex:1,minHeight:36,backgroundColor:theme.line,marginVertical:4},content:{flex:1,gap:spacing.xs,paddingBottom:spacing.lg},last:{paddingBottom:0},heading:{flexDirection:'row',alignItems:'center',gap:spacing.sm},title:{...typography.body,color:theme.ink,fontWeight:'800',flex:1},current:{...typography.small,color:theme.primary,fontWeight:'800'},description:{...typography.body,color:theme.muted},meta:{...typography.small,color:theme.muted},empty:{...typography.body,color:theme.muted}});}
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/ui/select-sheet.tsx:149:      marginTop: spacing.sm,
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/ui/select-sheet.tsx:150:      marginBottom: spacing.lg,
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/ui/select-sheet.tsx:157:      marginBottom: spacing.md,
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/ui/select-sheet.tsx:178:    optionDetail: { ...typography.small, color: theme.muted, marginTop: 2 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/ui/app-feedback.tsx:507:      marginBottom:
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/ui/app-feedback.tsx:523:      marginTop:
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/ui/data-list.tsx:90:      marginBottom: spacing.lg,
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/ui/data-list.tsx:93:      marginTop: spacing.lg,
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/catalog/product-card.tsx:172:      marginTop: spacing.sm,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-access.ts:41:export const ADMIN_ROLE_SLUGS = ['admin', 'superadmin'] as const;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-query-keys.ts:15:  managementReport: (params: unknown) => ['admin', 'reports', 'management', params] as const,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-export.ts:65:  const reportType = params.report_type ?? 'management_summary';
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:4:  | 'management_summary'
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:5:  | 'profitability'
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:53:  gross_profit_rsd: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:54:  gross_margin_percent: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:59:  net_margin_percent: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:73:  gross_profit_rsd: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:74:  gross_margin_percent: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:81:  gross_profit_rsd: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:258:  return `admin/reports/management.${format}${requestQuery(params)}`;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:262:  management: (params: AdminReportRequestParams = {}) =>
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:264:      `admin/reports/management${requestQuery(params)}`,
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:182:  management_filters: Nullable<{ statuses: string[]; quality: string[] }>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:785:  purchase_price_rsd?: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(auth)/login.tsx:143:  kicker: { ...typography.small, color: theme.accent, letterSpacing: 1.8, fontWeight: '900', marginTop: spacing.xl },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(auth)/login.tsx:144:  heroTitle: { ...typography.hero, color: theme.white, marginTop: spacing.sm, maxWidth: 340 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(auth)/login.tsx:145:  heroCopy: { ...typography.body, color: theme.heroMuted, marginTop: spacing.md, maxWidth: 350 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(auth)/login.tsx:150:  subtitle: { ...typography.body, color: theme.muted, marginTop: spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/notifications.tsx:125:  header: { gap: spacing.md, marginBottom: spacing.lg },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/notifications.tsx:128:  dot: { width: 9, height: 9, marginTop: 7, borderRadius: radii.pill, backgroundColor: theme.primary },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/catalog.tsx:87:  headerWrap: { gap: spacing.lg, marginBottom: spacing.lg },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:25:  report_type: 'management_summary' as const,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:41:    queryKey: adminQueryKeys.managementReport(HOME_REPORT_PARAMS),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:42:    queryFn: () => apiAdminReports.management(HOME_REPORT_PARAMS),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:135:                  value={formatMoney(report.summary.gross_profit_rsd, 'RSD')}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:136:                  meta={`Marža ${report.summary.gross_margin_percent.toFixed(1)}%`}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:379:      marginTop: spacing.xs,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:398:    sectionSubtitle: { ...typography.small, color: theme.muted, marginTop: 2 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:416:    dashboardMetricValue: { ...typography.h3, color: theme.ink, marginTop: spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:454:    focusTitle: { ...typography.h3, color: theme.ink, marginTop: 2 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:467:    metricDot: { width: 8, height: 8, borderRadius: 4, marginBottom: spacing.sm },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:483:    actionText: { ...typography.small, color: theme.muted, marginTop: 3 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:494:    foundationCopy: { ...typography.small, color: theme.muted, marginTop: 4 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/orders.tsx:77:  header: { gap: spacing.sm, marginBottom: spacing.lg },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/account.tsx:888:      marginBottom: spacing.sm
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/account.tsx:969:      marginTop: spacing.xs
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/account.tsx:1025:      marginTop: 3
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/account.tsx:1036:      marginTop: spacing.xs
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/commissions/[id].tsx:149:      marginTop: 3,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/commissions/[id].tsx:165:      marginBottom: spacing.md,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/commissions/index.tsx:316:      marginBottom: spacing.lg,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/commissions/index.tsx:396:      marginTop: 3,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:608:    title: { ...typography.h1, color: theme.ink, marginTop: 3 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:611:    value: { ...typography.label, color: theme.ink, marginTop: 3 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:613:    totalValue: { ...typography.h2, color: theme.primaryDark, marginTop: 3 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:616:    subsection: { gap: spacing.sm, marginTop: spacing.sm },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:620:    itemMeta: { ...typography.small, color: theme.muted, marginTop: 3 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:622:    muted: { ...typography.body, color: theme.muted, marginTop: spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:630:    rejection: { ...typography.small, color: theme.danger, marginTop: spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:634:    fileMeta: { ...typography.small, color: theme.muted, marginTop: 3 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/notification-settings.tsx:243:  noticeCopy: { ...typography.small, color: theme.muted, marginTop: spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/notification-settings.tsx:244:  sectionTitle: { ...typography.h2, color: theme.ink, marginTop: spacing.md },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/notification-settings.tsx:247:  preferenceCopy: { ...typography.small, color: theme.muted, marginTop: spacing.xs }
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/cart.tsx:133:  title: { ...typography.h1, color: theme.ink, marginTop: spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/cart.tsx:134:  subtitle: { ...typography.body, color: theme.muted, marginTop: spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/cart.tsx:146:  itemMeta: { ...typography.small, color: theme.muted, marginTop: 3 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/cart.tsx:147:  itemPrice: { ...typography.label, color: theme.primaryDark, marginTop: spacing.sm },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/cart.tsx:156:  summaryTitle: { ...typography.h3, color: theme.ink, marginBottom: spacing.sm },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/cart.tsx:160:  note: { ...typography.small, color: theme.muted, marginTop: spacing.sm }
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx:228:  title: { ...typography.h1, color: theme.ink, marginTop: spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx:229:  subtitle: { ...typography.body, color: theme.muted, marginTop: spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx:237:  optionCopy: { ...typography.small, color: theme.muted, marginTop: 3 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx:241:  help: { ...typography.small, color: theme.muted, marginTop: -spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx:245:  reviewCopy: { ...typography.small, color: theme.muted, marginTop: spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/product/[slug].tsx:149:  price: { ...typography.h2, color: theme.primaryDark, marginTop: spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/product/[slug].tsx:151:  sectionTitle: { ...typography.h3, color: theme.ink, marginBottom: spacing.md },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/product/[slug].tsx:152:  descriptionHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md, marginBottom: spacing.md },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/product/[slug].tsx:163:  buyCopy: { ...typography.small, color: theme.muted, marginTop: -spacing.sm },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/index.tsx:272:    amount: { ...typography.h3, color: theme.ink, marginTop: 2 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:63:  { value: 'management_summary', label: 'Kompletan upravljački' },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:64:  { value: 'profitability', label: 'Profitabilnost' },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:154:    report_type: 'management_summary',
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:159:    queryKey: adminQueryKeys.managementReport(applied),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:160:    queryFn: () => apiAdminReports.management(applied),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:184:      report_type: 'management_summary',
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:215:    setApplied({ report_type: 'management_summary' });
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:411:  const [reportType, setReportType] = useState<AdminReportType> ('management_summary');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:438:    setReportType('management_summary');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:974:        <MetricCard label="Bruto dobit" value={formatMoney(summary.gross_profit_rsd, 'RSD')} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:979:        <MetricCard label="Bruto marža" value={`${summary.gross_margin_percent.toFixed(1)}%`} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:1033:          <DetailRow label="Bruto dobit" value={formatMoney(point.gross_profit_rsd, 'RSD')} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:1180:      <DetailRow label="Bruto dobit" value={formatMoney(segment.gross_profit_rsd, 'RSD')} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:1181:      <DetailRow label="Bruto marža" value={`${segment.gross_margin_percent.toFixed(1)}%`} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/field-operations/index.tsx:216:    subject: { ...typography.h3, color: theme.ink, marginTop: spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/after-sales/index.tsx:227:    subject: { ...typography.h3, color: theme.ink, marginTop: spacing.xs },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:363:    if (purchasePriceRsd.trim() && purchase === null) nextErrors.purchase_price_rsd = 'Unesi ispravnu nabavnu cenu.';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:443:      purchase_price_rsd: purchase ?? undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:712:          error={errors.purchase_price_rsd}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx:843:    sectionTitle: { ...typography.h2, color: theme.ink, marginTop: spacing.sm },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:221:      marginTop: 3,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:233:      marginBottom: spacing.md,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/index.tsx:146:      marginBottom: spacing.lg,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/index.tsx:177:      marginTop: 3,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/assigned-orders/[id].tsx:118:    title: { ...typography.h1, color: theme.ink, marginTop: 3 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/assigned-orders/[id].tsx:120:    sectionTitle: { ...typography.h3, color: theme.ink, marginBottom: spacing.sm },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/assigned-orders/[id].tsx:126:    itemMeta: { ...typography.small, color: theme.muted, marginTop: 3 },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/assigned-orders/index.tsx:91:    header: { gap: spacing.sm, marginBottom: spacing.lg },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/after-sales/[id].tsx:462:      marginTop: 3,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/after-sales/[id].tsx:478:      marginBottom: spacing.md,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/after-sales/[id].tsx:547:      marginTop: spacing.md,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/after-sales/[id].tsx:583:      marginTop: 3,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/after-sales/[id].tsx:597:      marginTop: spacing.md,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/after-sales/create/[orderId].tsx:497:      marginTop: spacing.xs,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/after-sales/create/[orderId].tsx:502:      marginTop: spacing.xs,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/after-sales/create/[orderId].tsx:551:      marginTop: -spacing.sm,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/after-sales/create/[orderId].tsx:600:      marginTop: 3,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/after-sales/create/[orderId].tsx:628:      marginTop: 3,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/after-sales/create/[orderId].tsx:642:      marginTop: spacing.xs,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/after-sales/index.tsx:128:      marginBottom: spacing.lg,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/after-sales/index.tsx:159:      marginTop: 3,
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:705:    && p2AdminFoundationAccessV06.includes("ADMIN_ROLE_SLUGS = ['admin', 'superadmin']")
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:709:  'P2 Admin access helper centralizuje administratorske dozvole i admin/superadmin role fallback.',
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:946:  'P3 Admin Reports 2G zaključava management dashboard, permission gate i schedule manager UI.',
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:951:    && p3ReportsQueryBatch2GV06.includes("managementReport: (params: unknown) => ['admin', 'reports', 'management', params] as const")
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:957:  p3ReportsOpenApiBatch2GV06.includes('/api/v1/admin/reports/management:')
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:958:    && p3ReportsOpenApiBatch2GV06.includes('/api/v1/admin/reports/management.csv:')
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:959:    && p3ReportsOpenApiBatch2GV06.includes('/api/v1/admin/reports/management.pdf:')
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1363:  /api/v1/admin/reports/management:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1378:        - { in: query, name: report_type, schema: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales], default: management_summary } }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1389:  /api/v1/admin/reports/management.csv:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1404:        - { in: query, name: report_type, schema: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales], default: management_summary } }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1423:  /api/v1/admin/reports/management.pdf:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1438:        - { in: query, name: report_type, schema: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales], default: management_summary } }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3217:        report_type: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3233:      required: [orders_count, units_count, revenue_rsd, known_revenue_rsd, cogs_rsd, gross_profit_rsd, gross_margin_percent, commissions_rsd, refunds_rsd, service_cost_rsd, net_contribution_rsd, net_margin_percent, average_order_rsd, outstanding_rsd, missing_cost_lines, revenue_missing_cost_rsd, cost_coverage_percent]
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3240:        gross_profit_rsd: { type: number }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3241:        gross_margin_percent: { type: number }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3246:        net_margin_percent: { type: number }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3254:      required: [label, orders_count, units_count, revenue_rsd, cogs_rsd, gross_profit_rsd, gross_margin_percent, cost_coverage_percent]
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3261:        gross_profit_rsd: { type: number }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3262:        gross_margin_percent: { type: number }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3266:      required: [period, revenue_rsd, gross_profit_rsd]
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3270:        gross_profit_rsd: { type: number }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3337:        report_type: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3360:        report_type: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3373:        report_type: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3398:        report_type: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3416:        report_type: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3913:        purchase_price_rsd: { type: number, minimum: 0 }

============================================================
5. DEFERRED PAYMENT -> RECEIVABLES SOURCE MAP
============================================================
--- CMS PAYMENT METHOD / RECEIVABLES HITS ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DocumentNumberService.php:42:            'receivable' => 'NAP',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/UserNotificationPreferenceService.php:44:            'receivable_updates' => true,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/BackupService.php:50:                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:28:        private readonly ReceivablesService $receivables,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:134:                'payment_method_snapshot' => $locked->payment_method,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:219:                    $this->receivables->ensureForOrder($document->order, $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:221:                    Log::warning('Receivable case synchronization failed after document issuance.', ['document_id' => $document->id, 'exception' => $exception]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:337:            'payment_method_snapshot' => $document->payment_method_snapshot,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:35:        $paymentMethod = trim((string) ($input['payment_method'] ?? ''));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:36:        if (!in_array($paymentMethod, ['cash', 'card', 'bank_transfer', 'other'], true)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:38:                'payment_method' => 'Izabrani način plaćanja nije dozvoljen za direktnu prodaju.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:55:            'payment_method' => $paymentMethod,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:89:    /** @param array{product_id:int,buyer_name:string,buyer_phone:?string,quantity:int,sale_price_rsd:float,payment_method:string} $payload */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:134:        $fingerprint = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:161:            'payment_method' => $payload['payment_method'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:234:            'payment_method' => $payload['payment_method'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:278:                'payment_method' => $payload['payment_method'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogSyncService.php:168:        return hash('sha256', json_encode($row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRESERVE_ZERO_FRACTION|JSON_THROW_ON_ERROR));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:24:        private readonly ReceivablesService $receivables,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:37:        if ($order->payment_method !== 'bank_transfer') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:61:                    'payment_method' => 'bank_transfer',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:105:                'payment_method' => (string) $data['payment_method'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:121:            $this->syncReceivable($payment->order);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:161:            'payment_method' => 'after_sales_refund',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:193:        if ($verified->order instanceof Order) $this->syncReceivable($verified->order);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:231:        if ($voided->order instanceof Order) $this->syncReceivable($voided->order);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:249:        $this->syncReceivable($fresh);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:254:    private function syncReceivable(Order $order): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:257:            $this->receivables->syncForOrder($order->fresh() ?? $order);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:259:            \Illuminate\Support\Facades\Log::warning('Receivable synchronization failed after payment change.', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:194:                && $detail['order']['payment_method'] === 'bank_transfer'
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:252:        $receivable = $this->relation($order, 'receivableCase');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:310:                'payment_method' => $this->text($order, 'payment_method', '—'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:335:            'receivable' => $this->receivable($receivable),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:358:    private function receivable(mixed $receivable): ?array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:360:        if (!$receivable instanceof Model) return null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:361:        $installments = $this->collectionRelation($receivable, 'installments')->filter(static fn (mixed $item): bool => $item instanceof Model)->map(fn (Model $item): array => [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:368:        $contacts = $this->collectionRelation($receivable, 'contacts')->filter(static fn (mixed $item): bool => $item instanceof Model)->map(fn (Model $item): array => [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:374:            'case_number' => $this->text($receivable, 'case_number'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:375:            'status' => $this->text($receivable, 'status', 'monitoring'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:376:            'next_action_at' => $this->date($receivable, 'next_action_at'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:377:            'promised_payment_at' => $this->date($receivable, 'promised_payment_at'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:378:            'installments' => $installments,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:507:                    'payment_method' => $this->text($payment, 'payment_method', '—'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:18:use App\Models\ReceivableInstallment;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:53:        $installments = $this->installments($user);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:57:        return compact('summary', 'orders', 'documents', 'payments', 'warranties', 'cases', 'serviceAppointments', 'installments', 'timeline', 'conversations');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:81:            'receivable_updates' => true,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:253:    private function installments(User $user): Collection
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:255:        if (!Schema::hasTable('receivable_installments')) return collect();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:257:            return ReceivableInstallment::query()->whereHas('case.order', static fn ($orders) => $orders->operational()->where('user_id', $user->id))
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:260:                ->map(fn (ReceivableInstallment $installment): array => [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:261:                    'sequence_no' => $installment->sequence_no,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:262:                    'case_number' => $installment->case?->case_number,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:263:                    'order_number' => $installment->case?->order?->order_number,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:264:                    'due_at' => $installment->due_at,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:265:                    'amount_rsd' => (float) $installment->amount_rsd,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:266:                    'paid_rsd' => (float) $installment->paid_amount_rsd,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:267:                    'remaining_rsd' => max(0, (float) $installment->amount_rsd - (float) $installment->paid_amount_rsd),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:268:                    'status' => $installment->status,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:269:                    'url' => $installment->case?->order ? route('orders.show', $installment->case->order) : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:719:                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/StorageSpecificationService.php:368:                'value_json' => json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:348:                    if ((string) $locked->payment_method !== 'cash_on_delivery') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:360:                        'payment_method' => 'cash_on_delivery',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:362:                        'reference' => 'COD-'.$locked->order_number,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:51:                $method = $this->paymentMethod((string) ($data['payment_method'] ?? ''));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:56:                    'payment_method' => $method,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:59:                $metadata = ['payment_method' => $method, 'payment_reference' => $reference !== '' ? $reference : null];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:71:                after: ['status' => $newStatus, 'payment_method' => $locked->payment_method, 'payment_reference' => $locked->payment_reference],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:90:        $method = $this->paymentMethod((string) ($data['payment_method'] ?? ''));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:110:                'payment_method' => $method,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:129:                    'payment_method' => $method,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:134:                    'payment_method' => $method,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:144:                metadata: ['commission_ids' => $ids, 'payment_method' => $method, 'payment_reference' => $reference],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:187:        if (!in_array($method, ['bank_transfer', 'cash', 'other'], true)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:188:            throw ValidationException::withMessages(['payment_method' => 'Izaberi način isplate provizije.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:203:            'metadata_json' => $metadata !== [] ? json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:81:        $receivables = $this->receivables($user, $filters);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:94:            'receivables' => $receivables,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:236:        $aging = ['0_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, '91_180' => 0.0, 'over_180' => 0.0];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:237:        foreach ($positive as $row) $aging[$this->agingBucket((int) $row['age_days'])] += (float) $row['value_rsd'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:243:            'aging' => array_map(static fn ($value): float => round((float) $value, 2), $aging),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:250:    public function receivables(User $user, array $filters): array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:255:        $aging = ['not_due' => 0.0, '1_7' => 0.0, '8_15' => 0.0, '16_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, 'over_90' => 0.0];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:264:            $aging[$bucket] += $balance;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:266:        return ['open_orders' => $open, 'outstanding_rsd' => round(array_sum($aging), 2), 'aging' => array_map(static fn ($value): float => round((float) $value, 2), $aging)];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:406:    private function agingBucket(int $days): string
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:414:        return ['items_count' => 0, 'units_count' => 0, 'value_rsd' => 0.0, 'missing_cost_items' => 0, 'slow_items' => 0, 'aging' => [], 'top_value' => [], 'slow_stock' => []];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionReportService.php:81:                    $row->payment_method,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionReportService.php:121:            'order_commissions' => ['id', 'order_id', 'user_id', 'total_eur', 'status', 'payment_method', 'payment_reference', 'status_updated_at'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionReportService.php:123:            'commission_payment_batches' => ['id', 'batch_number', 'payment_method', 'total_eur'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:8:use App\Models\ReceivableCase;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:9:use App\Models\ReceivableContact;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:10:use App\Models\ReceivableInstallment;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:17:final class ReceivablesService
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:20:    public const STATUSES = ['monitoring', 'contacted', 'promised', 'installment_plan', 'escalated', 'disputed', 'closed'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:31:        return Schema::hasTable('receivable_cases')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:32:            && Schema::hasTable('receivable_installments')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:33:            && Schema::hasTable('receivable_contacts');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:36:    public function ensureForOrder(Order $order, ?User $actor = null): ?ReceivableCase
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:38:        if (!$this->ready() || $order->payment_method !== 'bank_transfer' || $order->status === 'cancelled') return null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:40:        $existing = ReceivableCase::query()->where('order_id', $order->id)->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:41:        if ($existing instanceof ReceivableCase) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:44:        if ($actor === null && $this->settings->get('receivables_auto_create_cases', '1') !== '1') return null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:48:        return DB::transaction(function () use ($order, $actor): ReceivableCase {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:51:            $existing = ReceivableCase::query()->where('order_id', $locked->id)->lockForUpdate()->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:52:            if ($existing instanceof ReceivableCase) return $existing;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:54:            $case = ReceivableCase::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:56:                'case_number' => $this->numbers->next('receivable', (int) now()->format('Y')),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:65:            $this->audit->log('receivable.created', 'Otvoren predmet naplate '.$case->case_number, $case, after: $case->toArray(), user: $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:71:    public function update(ReceivableCase $case, User $actor, array $data): ReceivableCase
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:73:        return DB::transaction(function () use ($case, $actor, $data): ReceivableCase {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:74:            /** @var ReceivableCase $locked */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:75:            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:104:            $this->audit->log('receivable.updated', 'Ažuriran predmet naplate '.$locked->case_number, $locked, $before, $locked->fresh()?->toArray() ?? [], user: $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:105:            return $locked->fresh(['order.user', 'order.supplier', 'assignee', 'installments', 'contacts.user']) ?? $locked;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:109:    /** @param list<array{due_at:string,amount_rsd:mixed,note?:string|null}> $installments */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:110:    public function replacePlan(ReceivableCase $case, User $actor, array $installments): ReceivableCase
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:112:        return DB::transaction(function () use ($case, $actor, $installments): ReceivableCase {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:113:            /** @var ReceivableCase $locked */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:114:            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:115:            if (!$locked->order instanceof Order) throw ValidationException::withMessages(['installments' => 'Porudžbina nije dostupna.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:117:            if ($remaining <= 0.004) throw ValidationException::withMessages(['installments' => 'Porudžbina nema preostalo dugovanje.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:118:            if ($installments === [] || count($installments) > 24) throw ValidationException::withMessages(['installments' => 'Unesi između 1 i 24 rate.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:119:            if (ReceivableInstallment::query()->where('receivable_case_id', $locked->id)->where('paid_amount_rsd', '>', 0)->exists()) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:120:                throw ValidationException::withMessages(['installments' => 'Plan sa već raspoređenom uplatom ne može se zameniti. Evidentiraj novi dogovor u komunikaciji.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:126:            foreach ($installments as $index => $row) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:131:                    throw ValidationException::withMessages(['installments' => 'Sve rate moraju imati ispravan datum dospeća.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:133:                if ($amount <= 0) throw ValidationException::withMessages(['installments' => 'Svaka rata mora imati iznos veći od nule.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:134:                if ($due->lt(today())) throw ValidationException::withMessages(['installments' => 'Datum rate ne može biti u prošlosti.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:135:                if ($previous !== null && $due->lt($previous)) throw ValidationException::withMessages(['installments' => 'Datumi rata moraju biti hronološki poređani.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:148:                throw ValidationException::withMessages(['installments' => 'Zbir rata mora biti jednak preostalom dugu '.number_format($remaining, 2, ',', '.').' RSD.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:151:            ReceivableInstallment::query()->where('receivable_case_id', $locked->id)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:152:            foreach ($normalized as $row) ReceivableInstallment::query()->create(['receivable_case_id' => $locked->id] + $row);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:157:                'status' => 'installment_plan',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:164:            $this->audit->log('receivable.plan_created', 'Kreiran plan otplate za '.$locked->case_number, $locked, after: ['installments' => $normalized, 'total_rsd' => $sum], user: $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:165:            return $this->syncForOrder($locked->order->fresh() ?? $locked->order) ?? $locked->fresh(['installments']) ?? $locked;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:170:    public function addContact(ReceivableCase $case, User $actor, array $data): ReceivableContact
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:172:        return DB::transaction(function () use ($case, $actor, $data): ReceivableContact {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:173:            /** @var ReceivableCase $locked */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:174:            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:177:            $contact = ReceivableContact::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:178:                'receivable_case_id' => $locked->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:195:                $this->emails->receivableMessage($locked->order, $locked, $contact->subject ?: 'Obaveštenje o plaćanju', $contact->note, 'manual-'.$contact->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:197:            $this->audit->log('receivable.contact_recorded', 'Evidentiran kontakt za '.$locked->case_number, $contact, after: $contact->toArray(), user: $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:202:    public function sendReminder(ReceivableCase $case, User $actor, ?string $customMessage = null): ReceivableCase
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:204:        return DB::transaction(function () use ($case, $actor, $customMessage): ReceivableCase {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:205:            /** @var ReceivableCase $locked */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:206:            $locked = ReceivableCase::query()->with('order')->lockForUpdate()->findOrFail($case->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:213:            $this->emails->receivableReminder(
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:220:            ReceivableContact::query()->create([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:221:                'receivable_case_id' => $locked->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:238:            $this->audit->log('receivable.reminder_queued', 'Pripremljen podsetnik za '.$locked->case_number, $locked, after: ['remaining_rsd' => $remaining], user: $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:239:            return $locked->fresh(['order', 'installments', 'contacts.user', 'assignee']) ?? $locked;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:247:        if (!$this->ready() || $this->settings->get('receivables_enabled', '1') !== '1') return $result;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:251:            ->where('payment_method', 'bank_transfer')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:261:            $existingId = ReceivableCase::query()->where('order_id', $order->id)->value('id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:263:            if (!$case instanceof ReceivableCase) continue;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:270:            if ($this->settings->get('receivables_auto_reminders_enabled', '1') !== '1') continue;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:271:            if (!$force && $this->settings->get('receivables_pause_on_promise', '1') === '1' && $case->promised_payment_at?->isFuture()) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:283:    public function syncForOrder(Order $order): ?ReceivableCase
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:286:        $case = ReceivableCase::query()->where('order_id', $order->id)->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:287:        if (!$case instanceof ReceivableCase) return null;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:289:        return DB::transaction(function () use ($case, $order): ReceivableCase {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:290:            /** @var ReceivableCase $locked */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:291:            $locked = ReceivableCase::query()->lockForUpdate()->findOrFail($case->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:299:            $installments = ReceivableInstallment::query()->where('receivable_case_id', $locked->id)->orderBy('sequence_no')->lockForUpdate()->get();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:300:            foreach ($installments as $installment) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:301:                $allocated = min((float) $installment->amount_rsd, $allocatable);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:303:                $isPaid = $allocated + 0.004 >= (float) $installment->amount_rsd;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:304:                $status = $isPaid ? 'paid' : ($installment->due_at?->isPast() ? 'overdue' : 'pending');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:305:                $installment->update([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:308:                    'paid_at' => $isPaid ? ($installment->paid_at ?: now()) : null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:316:                $nextInstallment = $installments->first(static fn (ReceivableInstallment $item): bool => $item->status !== 'paid');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:319:                if ($locked->status === 'closed') $updates['status'] = $installments->isNotEmpty() ? 'installment_plan' : 'monitoring';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:322:            return $locked->fresh(['order.user', 'order.supplier', 'installments', 'contacts.user', 'assignee']) ?? $locked;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:337:    public function agingBucket(Order $order): string
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:356:        $dueSoon = max(0, min(60, (int) $this->settings->get('receivables_due_soon_days', '3')));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:375:        $raw = preg_split('/[\s,;]+/', (string) $this->settings->get('receivables_reminder_stages', '0,3,7,15,30')) ?: [];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:387:    private function queueAutomaticReminder(ReceivableCase $case, Order $order, int $stage, bool $force): bool
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:390:            /** @var ReceivableCase $locked */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:391:            $locked = ReceivableCase::query()->lockForUpdate()->findOrFail($case->id);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:396:            $eventKey = 'receivable-auto:'.$locked->id.':'.$dueKey.':'.$stage;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:397:            if (!$force && ReceivableContact::query()->where('event_key', $eventKey)->exists()) return false;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:405:            $queued = $this->emails->receivableReminder($freshOrder, $locked, $stage, $subject, $message);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:408:            $contact = ReceivableContact::query()->firstOrCreate(
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:411:                    'receivable_case_id' => $locked->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:433:            $this->audit->log('receivable.automatic_reminder_queued', 'Automatska opomena za '.$locked->case_number, $locked, after: ['stage' => $stage, 'remaining_rsd' => $remaining, 'queued_recipients' => $queued]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/ManagementReportPdfService.php:70:            'Otvorena potraživanja' => (float) ($s['outstanding_rsd'] ?? 0), 'Promet bez troška' => (float) ($s['revenue_missing_cost_rsd'] ?? 0),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/ManagementReportPdfService.php:105:        $receivables = (array) ($report['receivables'] ?? []);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/ManagementReportPdfService.php:110:            ['Potraživanja', $this->money((float) ($receivables['outstanding_rsd'] ?? 0)), 'Otvorenih porudžbina: '.(int) ($receivables['open_orders'] ?? 0)],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/BusinessDocumentPdfService.php:262:        $payment = $this->paymentLabel((string) ($document['payment_method_snapshot'] ?? ''));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/BusinessDocumentPdfService.php:625:            'bank_transfer' => 'Uplata na račun',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/BusinessDocumentPdfService.php:626:            'cash_on_delivery' => 'Pouzećem',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:30:        private readonly ReceivablesService $receivables,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:74:        $fingerprint = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:99:            'payment_method' => (string) $data['payment_method'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:226:                $this->receivables->ensureForOrder($fresh, $user);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:228:                Log::warning('Receivable case creation failed after order commit.', ['order_id' => $fresh->id, 'exception' => $exception]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:266:        if (($data['payment_method'] ?? null) !== 'bank_transfer') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:384:                    $row['value_json'] = json_encode(array_values($structuredValue), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:10:use App\Models\ReceivableCase;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:88:    public function receivableReminder(Order $order, ReceivableCase $case, int $stage, string $subject, string $message): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:90:        return $this->queueReceivableEvent($order, $case, 'receivable_reminder', $subject, $message, 'stage-'.$stage.'-'.($order->payment_due_at?->format('Ymd') ?? 'none'), $stage);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:93:    public function receivableMessage(Order $order, ReceivableCase $case, string $subject, string $message, string $fingerprint): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:95:        return $this->queueReceivableEvent($order, $case, 'receivable_message', $subject, $message, $fingerprint, null);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:98:    private function queueReceivableEvent(
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:100:        ReceivableCase $case,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:107:        if ($this->settings->get('receivables_enabled', '1') !== '1' || !Schema::hasTable('order_email_outbox')) return 0;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:113:            $documentType = (string) $this->settings->get('receivables_attach_document', 'invoice');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:128:            if ($this->settings->get('receivables_send_creator', '1') === '1' && $order->user instanceof User) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:131:            if ($this->settings->get('receivables_send_supplier', '1') === '1' && $order->supplier instanceof User) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:134:            foreach (preg_split('/[\s,;]+/', (string) $this->settings->get('receivables_custom_recipients', '')) ?: [] as $email) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:152:                        'batch_key' => 'receivable-'.now()->format('YmdHi'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:155:                        'action_url' => $this->receivableActionUrl($order, $case, (bool) $recipient['is_admin']),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:160:                        'metadata_json' => ['receivable_case_id' => $case->id, 'case_number' => $case->case_number, 'stage' => $stage],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:166:            Log::warning('Receivable email enqueue failed.', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:168:                'receivable_case_id' => $case->id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:209:                    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:293:    private function receivableActionUrl(Order $order, ReceivableCase $case, bool $isAdmin): ?string
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:296:            return $isAdmin && \Illuminate\Support\Facades\Route::has('admin.receivables.show')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:297:                ? route('admin.receivables.show', $case)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:29:        private readonly ReceivablesService $receivables,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:187:                    'Dospelo neplaćeno potraživanje',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:586:        if ($this->receivables->ready()) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:587:            $collection = $this->receivables->runAutomation($force);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:590:            $types['receivable_cases_created'] = $collection['cases_created'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:591:            $types['receivable_reminders'] = $collection['reminders'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:592:            $types['receivable_cases_closed'] = $collection['closed'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ModuleVisibilityService.php:16:        'receivables' => ['label' => 'Potraživanja', 'description' => 'Naplata, planovi, kontakti i podsetnici.'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/IpsPaymentPayloadService.php:18:        if ($order->payment_method !== 'bank_transfer' || trim((string) $order->bank_account_number_snapshot) === '') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/IdempotencyService.php:32:        $requestHash = hash('sha256', json_encode($this->canonicalize($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php:106:                'receivable_updates' => true,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php:122:                'receivable_updates' => true,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php:134:        if (str_contains($event, 'receivable') || str_contains($event, 'installment') || str_contains($event, 'collection')) return 'receivable_updates';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/NbsIpsQrService.php:23:        return $order->payment_method === 'bank_transfer'
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:40:        $this->loadOne($order, 'receivableCase', 'receivable_cases', ['id', 'order_id'], $warnings);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:41:        if ($order->relationLoaded('receivableCase') && $order->receivableCase !== null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:44:                if ($this->hasColumns('receivable_installments', ['id', 'receivable_case_id'])) $relations[] = 'installments';
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:45:                if ($this->hasColumns('receivable_contacts', ['id', 'receivable_case_id'])) $relations['contacts'] = static fn ($query) => $query->where('visible_to_customer', true)->orderByDesc('contacted_at')->limit(20);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:46:                if ($relations !== []) $order->receivableCase->load($relations);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailService.php:49:                $this->safeLog($order, 'receivableCase', $exception);
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableCase.php:11:final class ReceivableCase extends Model
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableCase.php:37:    public function installments(): HasMany { return $this->hasMany(ReceivableInstallment::class)->orderBy('sequence_no'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableCase.php:38:    public function contacts(): HasMany { return $this->hasMany(ReceivableContact::class)->latest('contacted_at')->latest('id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableCase.php:46:            'installment_plan' => 'Plan otplate',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableCase.php:48:            'disputed' => 'Sporno potraživanje',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableInstallment.php:10:final class ReceivableInstallment extends Model
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableInstallment.php:13:        'receivable_case_id', 'sequence_no', 'due_at', 'amount_rsd', 'paid_amount_rsd', 'status', 'paid_at', 'note',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableInstallment.php:27:    public function case(): BelongsTo { return $this->belongsTo(ReceivableCase::class, 'receivable_case_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableContact.php:10:final class ReceivableContact extends Model
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableContact.php:13:        'receivable_case_id', 'user_id', 'order_email_outbox_id', 'event_key', 'channel', 'direction',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/ReceivableContact.php:26:    public function case(): BelongsTo { return $this->belongsTo(ReceivableCase::class, 'receivable_case_id'); }
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/NotificationPreference.php:14:        'document_updates', 'after_sales_updates', 'warranty_updates', 'service_updates', 'receivable_updates',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/NotificationPreference.php:30:            'receivable_updates' => 'boolean',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/CommissionPaymentBatch.php:14:        'batch_number', 'payment_method', 'payment_reference', 'note', 'commission_count', 'total_eur', 'paid_by', 'paid_at',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/OrderDocument.php:19:        'supplier_email', 'payment_method_snapshot', 'payment_status_snapshot', 'bank_account_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Order.php:18:        'payment_method', 'payment_status', 'payment_state', 'paid_total_rsd', 'payment_due_at', 'payment_verified_at', 'bank_account_id', 'bank_account_label_snapshot',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Order.php:146:    public function receivableCase(): HasOne
--- MOBILE CHECKOUT / PAYMENT METHOD HITS ---
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-api.ts:15:  | 'receivables'
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/commissions-admin-api.ts:4:export type AdminCommissionPaymentMethod = 'bank_transfer' | 'cash' | 'other';
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/commissions-admin-api.ts:83:    payment_methods: AdminCommissionSelectOption<AdminCommissionPaymentMethod>[];
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/commissions-admin-api.ts:98:  payment_method?: AdminCommissionPaymentMethod;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/commissions-admin-api.ts:104:  payment_method: AdminCommissionPaymentMethod;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/commissions-admin-api.ts:115:    payment_method: AdminCommissionPaymentMethod;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:72:  installments: AdminReceivableInstallment[];
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:84:  receivables_enabled: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:85:  receivables_auto_create_cases: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:86:  receivables_auto_reminders_enabled: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:87:  receivables_due_soon_days: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:88:  receivables_reminder_stages: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:89:  receivables_pause_on_promise: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:90:  receivables_attach_document: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:91:  receivables_send_creator: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:92:  receivables_send_supplier: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:93:  receivables_custom_recipients: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:139:    max_installments: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:163:  installments: Array<{
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:183:  receivables_enabled: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:184:  receivables_auto_create_cases: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:185:  receivables_auto_reminders_enabled: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:186:  receivables_due_soon_days: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:187:  receivables_reminder_stages: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:188:  receivables_pause_on_promise: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:189:  receivables_attach_document: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:190:  receivables_send_creator: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:191:  receivables_send_supplier: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:192:  receivables_custom_recipients: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:213:    apiRequest<AdminReceivableListResponse> (`admin/receivables${queryString({
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:222:  detail: (receivableId: number) =>
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:223:    apiRequest<AdminReceivableDetailResponse> (`admin/receivables/${receivableId}`),
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:224:  update: (receivableId: number, input: AdminReceivableUpdateInput) =>
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:225:    apiRequest<AdminReceivableMutationResponse> (`admin/receivables/${receivableId}`, {
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:229:  replacePlan: (receivableId: number, input: AdminReceivablePlanInput) =>
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:230:    apiRequest<AdminReceivableMutationResponse> (`admin/receivables/${receivableId}/plan`, {
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:234:  addContact: (receivableId: number, input: AdminReceivableContactInput) =>
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:235:    apiRequest<AdminReceivableMutationResponse> (`admin/receivables/${receivableId}/contacts`, {
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:239:  sendReminder: (receivableId: number, input: AdminReceivableReminderInput = {}) =>
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:240:    apiRequest<AdminReceivableMutationResponse> (`admin/receivables/${receivableId}/reminder`, {
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:245:    apiRequest<AdminReceivableSettingsResponse> ('admin/receivables/settings', {
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:249:  scan: () => apiRequest<AdminReceivableScanResponse> ('admin/receivables/scan', { method: 'POST' }),
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:250:  csvPath: () => '/api/v1/admin/receivables/export.csv',
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/receivables-admin-api.ts:273:  const file = new File(Paths.cache, `admin-receivables-${Date.now()}.csv`);
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-access.ts:38:  'receivables.manage',
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/orders-admin-api.ts:123:export type AdminOrderPaymentMethod = 'bank_transfer' | 'cash' | 'cash_on_delivery' | 'card' | 'other';
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/orders-admin-api.ts:165:  payment_method: AdminOrderPaymentMethod;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-query-keys.ts:28:  receivables: () => ['admin', 'receivables'] as const,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-query-keys.ts:29:  receivablesList: (params: unknown) => ['admin', 'receivables', 'list', params] as const,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-query-keys.ts:30:  receivable: (receivableId: number) => ['admin', 'receivables', 'detail', receivableId] as const,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/orders-admin-actions.tsx:132:  return value === 'bank_transfer' || value === 'cash' || value === 'cash_on_delivery' || value === 'card' || value === 'other';
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/orders-admin-actions.tsx:178:  const [paymentMethod, setPaymentMethod] = useState<AdminOrderPaymentMethod> ('bank_transfer');
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/orders-admin-actions.tsx:269:      payment_method: paymentMethod,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/orders-admin-actions.tsx:424:            { value: 'bank_transfer', label: 'Bankovni prenos' },
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/orders-admin-actions.tsx:426:            { value: 'cash_on_delivery', label: 'Pouzećem' },
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:7:  | 'receivables'
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/reports-admin-api.ts:148:  receivables: AdminReportReceivables;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:82:  receivable_updates: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:205:  payment_method: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:230:  payment_method: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:291:    payment_method: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:302:  bank_transfer: Nullable<OrderBankTransferSnapshot>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:527:export type CommissionPaymentMethod = 'bank_transfer' | 'cash' | 'other';
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:628:export type PaymentMethod = 'cash_on_delivery' | 'bank_transfer';
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:655:  payment_methods: OrderPaymentMethodOption[];
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:685:  payment_method: PaymentMethod;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:146:                  label="Otvoreno potraživanje"
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:148:                  meta={`${report.receivables.open_orders} otvorenih porudžbina`}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:278:        <Info label="Način plaćanja" value={humanize(order.payment_method)} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:314:            {postCreate.bank_transfer ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:317:                <Info label="Račun" value={postCreate.bank_transfer.account_number ?? '—'} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:318:                <Info label="Primalac" value={postCreate.bank_transfer.recipient_name ?? '—'} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:319:                <Info label="Model / poziv" value={postCreate.bank_transfer.reference ?? '—'} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:320:                <Info label="Šifra plaćanja" value={postCreate.bank_transfer.payment_code ?? '—'} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/order/[id].tsx:321:                <Info label="Svrha" value={postCreate.bank_transfer.purpose ?? '—'} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/notification-settings.tsx:31:  { key: 'receivable_updates', title: 'Potraživanja', copy: 'Rate, naplata i dospela potraživanja.' },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx:50:    const firstPayment = options.data.payment_methods[0]?.value;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx:56:    if (paymentMethod === 'bank_transfer' && bankAccountId === null && options.data?.bank_accounts[0]) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx:59:    if (paymentMethod !== 'bank_transfer') setBankAccountId(null);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx:99:  const selectedPayment = options.data.payment_methods.find((method) => method.value === paymentMethod);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx:136:      payment_method: paymentMethod,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/checkout.tsx:165:        {options.data.payment_methods.map((method) => (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/[id].tsx:84:    { value: 'bank_transfer', label: 'Prenos na račun' },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/[id].tsx:129:    if (formAction === 'paid' && paymentMethod) input.payment_method = paymentMethod;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/[id].tsx:165:              <SelectSheet label="Način isplate" value={paymentMethod} options={paymentOptions} onChange={(value) => { if (value === 'bank_transfer' || value === 'cash' || value === 'other') setPaymentMethod(value); }} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/index.tsx:142:    const input = { commission_ids: selectedIds, payment_method: bulkMethod } as const;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/commissions/index.tsx:198:          <SelectSheet label="Način isplate" value={bulkMethod} options={data.filters.payment_methods.map((item) => ({ value: item.value, label: item.label }))} onChange={(value) => { if (value === 'bank_transfer' || value === 'cash' || value === 'other') setBulkMethod(value); }} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:24:} from '@/features/admin/receivables-admin-api';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:40:  if (status === 'installment_plan') return 'info';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:96:  const receivableId = Number(id);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:98:  const allowed = can('receivables.manage');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:99:  const validId = Number.isInteger(receivableId) && receivableId > 0;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:120:    queryKey: adminQueryKeys.receivable(receivableId),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:121:    queryFn: () => apiAdminReceivables.detail(receivableId),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:127:    await client.invalidateQueries({ queryKey: adminQueryKeys.receivables() });
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:167:    const rows = data.installments.length > 0
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:168:      ? data.installments.map((item) => ({ key: key++, due_at: dateOnly(item.due_at), amount_rsd: String(item.amount_rsd), note: item.note ?? '' }))
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:189:  const submitUpdate = () => void execute('Predmet je ažuriran', () => apiAdminReceivables.update(receivableId, {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:198:    if (planRows.length >= response.options.max_installments) return;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:213:      if (planRows.length < 1 || planRows.length > response.options.max_installments) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:214:        throw new Error(`Plan mora imati između 1 i ${response.options.max_installments} rata.`);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:216:      const installments = planRows.map((row, index) => {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:225:      void execute('Plan otplate je sačuvan', () => apiAdminReceivables.replacePlan(receivableId, { installments }));
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:239:    void execute('Komunikacija je evidentirana', () => apiAdminReceivables.addContact(receivableId, {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:248:  const submitReminder = () => void execute('Podsetnik je prosleđen u postojeći outbox', () => apiAdminReceivables.sendReminder(receivableId, {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:261:            <Text style={styles.meta}>Preostalo potraživanje</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:305:          <Text style={styles.meta}>Server proverava zbir rata, hronologiju i zabranu zamene plana nakon alocirane uplate. Maksimalno {response.options.max_installments} rata.</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:306:          {data.installments.some((item) => item.paid_amount_rsd > 0) ? <Text style={styles.warning}>Postoje već alocirane uplate. Server može odbiti zamenu postojećeg plana; evidentiraj novi dogovor kroz komunikaciju.</Text> : null}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:308:            <Card key={row.key} style={styles.installmentDraft}>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:319:            <Button variant="secondary" disabled={planRows.length >= response.options.max_installments} onPress={addPlanRow}>Dodaj ratu</Button>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:359:          <Text style={styles.meta}>{data.installments.length}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:361:        {data.installments.length === 0 ? <EmptyState title="Nema plana otplate" message="Plan nije definisan za ovaj predmet." /> : data.installments.map((item) => <InstallmentRow item={item} key={item.id} />)}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/[id].tsx:392:    installmentDraft: { gap: spacing.sm },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:24:} from '@/features/admin/receivables-admin-api';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:38:  if (status === 'installment_plan') return 'info';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:59:      onPress={() => router.push({ pathname: '/admin/receivables/[id]', params: { id: String(item.id) } })}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:87:  const allowed = can('receivables.manage');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:102:    queryKey: adminQueryKeys.receivablesList(applied),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:109:  if (query.isLoading) return <LoadingState label="Učitavanje potraživanja…" />;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:172:        receivables_enabled: isOn(settingsDraft.receivables_enabled),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:173:        receivables_auto_create_cases: isOn(settingsDraft.receivables_auto_create_cases),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:174:        receivables_auto_reminders_enabled: isOn(settingsDraft.receivables_auto_reminders_enabled),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:175:        receivables_due_soon_days: positiveInt(settingsDraft.receivables_due_soon_days, 'Broj dana pre dospeća'),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:176:        receivables_reminder_stages: settingsDraft.receivables_reminder_stages.trim(),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:177:        receivables_pause_on_promise: isOn(settingsDraft.receivables_pause_on_promise),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:178:        receivables_attach_document: settingsDraft.receivables_attach_document.trim(),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:179:        receivables_send_creator: isOn(settingsDraft.receivables_send_creator),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:180:        receivables_send_supplier: isOn(settingsDraft.receivables_send_supplier),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:181:        receivables_custom_recipients: settingsDraft.receivables_custom_recipients.trim(),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:184:      await client.invalidateQueries({ queryKey: adminQueryKeys.receivables() });
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:195:      await client.invalidateQueries({ queryKey: adminQueryKeys.receivables() });
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:294:            <FilterChip label="Receivables uključen" active={isOn(settingsDraft.receivables_enabled)} onPress={() => updateSetting('receivables_enabled', isOn(settingsDraft.receivables_enabled) ? '0' : '1')} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:295:            <FilterChip label="Automatsko kreiranje" active={isOn(settingsDraft.receivables_auto_create_cases)} onPress={() => updateSetting('receivables_auto_create_cases', isOn(settingsDraft.receivables_auto_create_cases) ? '0' : '1')} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:296:            <FilterChip label="Automatske opomene" active={isOn(settingsDraft.receivables_auto_reminders_enabled)} onPress={() => updateSetting('receivables_auto_reminders_enabled', isOn(settingsDraft.receivables_auto_reminders_enabled) ? '0' : '1')} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:297:            <FilterChip label="Pauza uz obećanje" active={isOn(settingsDraft.receivables_pause_on_promise)} onPress={() => updateSetting('receivables_pause_on_promise', isOn(settingsDraft.receivables_pause_on_promise) ? '0' : '1')} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:298:            <FilterChip label="Pošalji kreatoru" active={isOn(settingsDraft.receivables_send_creator)} onPress={() => updateSetting('receivables_send_creator', isOn(settingsDraft.receivables_send_creator) ? '0' : '1')} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:299:            <FilterChip label="Pošalji odgovornom" active={isOn(settingsDraft.receivables_send_supplier)} onPress={() => updateSetting('receivables_send_supplier', isOn(settingsDraft.receivables_send_supplier) ? '0' : '1')} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:301:          <TextField label="Dana pre dospeća" value={settingsDraft.receivables_due_soon_days} onChangeText={(value) => updateSetting('receivables_due_soon_days', value)} keyboardType="numeric" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:302:          <TextField label="Faze opomena" value={settingsDraft.receivables_reminder_stages} onChangeText={(value) => updateSetting('receivables_reminder_stages', value)} placeholder="0,3,7,15,30" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:303:          <TextField label="Dokument uz opomenu" value={settingsDraft.receivables_attach_document} onChangeText={(value) => updateSetting('receivables_attach_document', value)} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/receivables/index.tsx:304:          <TextField label="Dodatni primaoci" value={settingsDraft.receivables_custom_recipients} onChangeText={(value) => updateSetting('receivables_custom_recipients', value)} multiline />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/[id].tsx:118:  const receivable = asRecord(data.receivable);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/[id].tsx:160:        <DetailRow label="Nacin placanja" value={text(order, 'payment_method')} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/[id].tsx:249:      {receivable ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/[id].tsx:252:          <DetailRow label="Status" value={text(receivable, 'status')} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/[id].tsx:253:          <DetailRow label="Sledeca akcija" value={formatDateTime(text(receivable, 'next_action_at'))} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/orders/[id].tsx:254:          <DetailRow label="Obecano placanje" value={formatDateTime(text(receivable, 'promised_payment_at'))} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:66:  { value: 'receivables', label: 'Potraživanja' },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:1079:  const receivables = report.receivables;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:1085:        <MetricCard label="Otvorene porudžbine" value={String(receivables.open_orders)} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:1086:        <MetricCard label="Ukupno otvoreno" value={formatMoney(receivables.outstanding_rsd, 'RSD')} styles={styles} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/reports/index.tsx:1090:        {Object.entries(receivables.aging).map(([key, value]) => (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/index.tsx:119:        {can('receivables.manage') ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/index.tsx:122:            onPress={() => router.push('/admin/receivables')}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/assigned-orders/[id].tsx:56:        <Info label="Način plaćanja" value={humanize(order.payment_method)} />
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:356:assert(checkout.includes("paymentMethod === 'bank_transfer'") && checkout.includes('bankAccountId'), 'Checkout podržava uslovni izbor računa za bank transfer.');
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:436:              required: [shipping_full_name, shipping_address, shipping_city, shipping_postal_code, shipping_phone, payment_method, items]
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:445:                payment_method: { type: string, enum: [cash_on_delivery, bank_transfer] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1378:        - { in: query, name: report_type, schema: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales], default: management_summary } }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1404:        - { in: query, name: report_type, schema: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales], default: management_summary } }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1438:        - { in: query, name: report_type, schema: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales], default: management_summary } }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1845:              required: [entry_type, amount_rsd, payment_method, paid_at]
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1849:                payment_method: { type: string, enum: [bank_transfer, cash, cash_on_delivery, card, other] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1964:  /api/v1/admin/receivables:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1971:        - { in: query, name: status, schema: { type: string, enum: [monitoring, contacted, promised, installment_plan, escalated, disputed, closed] } }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1979:        '403': { description: Nedostaje receivables.manage dozvola. }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1980:  /api/v1/admin/receivables/export.csv:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1983:      summary: CSV izvoz potraživanja kroz postojeći web Receivables autoritet i isti scope
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1987:          description: UTF-8 CSV izvoz potraživanja.
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1991:        '403': { description: Nedostaje receivables.manage dozvola. }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:1993:  /api/v1/admin/receivables/settings:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2005:                receivables_enabled: { type: boolean }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2006:                receivables_auto_create_cases: { type: boolean }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2007:                receivables_auto_reminders_enabled: { type: boolean }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2008:                receivables_due_soon_days: { type: integer, minimum: 0 }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2009:                receivables_reminder_stages: { type: string, example: '0,3,7,15,30' }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2010:                receivables_pause_on_promise: { type: boolean }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2011:                receivables_attach_document: { type: string, maxLength: 40 }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2012:                receivables_send_creator: { type: boolean }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2013:                receivables_send_supplier: { type: boolean }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2014:                receivables_custom_recipients: { type: string }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2017:        '403': { description: Nedostaje receivables.manage dozvola. }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2020:  /api/v1/admin/receivables/scan:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2027:        '403': { description: Nedostaje receivables.manage dozvola. }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2029:  /api/v1/admin/receivables/{receivable}:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2035:        - { in: path, name: receivable, required: true, schema: { type: integer } }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2045:        - { in: path, name: receivable, required: true, schema: { type: integer } }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2053:                status: { type: string, enum: [monitoring, contacted, promised, installment_plan, escalated, disputed, closed] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2063:  /api/v1/admin/receivables/{receivable}/plan:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2069:        - { in: path, name: receivable, required: true, schema: { type: integer } }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2076:              required: [installments]
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2078:                installments:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2094:  /api/v1/admin/receivables/{receivable}/contacts:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2100:        - { in: path, name: receivable, required: true, schema: { type: integer } }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2118:  /api/v1/admin/receivables/{receivable}/reminder:
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:2124:        - { in: path, name: receivable, required: true, schema: { type: integer } }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3217:        report_type: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3335:      required: [report_type, filters, period_label, generated_at, summary, segments, trend, inventory, receivables, after_sales, teams]
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3337:        report_type: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3349:        receivables: { $ref: '#/components/schemas/AdminReportReceivables' }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3360:        report_type: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3373:        report_type: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3398:        report_type: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3416:        report_type: { type: string, enum: [management_summary, profitability, inventory, receivables, after_sales] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3599:        method: { type: [string, 'null'], enum: [bank_transfer, cash, other, null] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3694:        payment_method: { type: string, enum: [bank_transfer, cash, other] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3698:      required: [commission_ids, payment_method]
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3706:        payment_method: { type: string, enum: [bank_transfer, cash, other] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:3733:            - receivables
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:4023:        receivable_updates: { type: boolean }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:4098:      required: [id, number, entry_type, entry_label, status, amount_rsd, payment_method, has_proof]
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:4106:        payment_method: { type: string }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:4180:          required: [id, order_number, status, payment_method, payment_status, payment_state, subtotal_rsd, paid_total_rsd, remaining_rsd]
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:4185:            payment_method: { type: string }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:4195:        bank_transfer: { anyOf: [{ $ref: '#/components/schemas/OrderBankTransferSnapshot' }, { type: 'null' }] }
/home/icaffeco/ald1n-project/apps/mobile/current/docs/openapi.yaml:4463:        method: { type: [string, 'null'], enum: [bank_transfer, cash, other, null] }

============================================================
6. WARRANTY EXPIRY NOTIFICATION OWNERSHIP SOURCE MAP
============================================================
--- CMS WARRANTY NOTIFICATION / AUTOMATION HITS ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Notifications/OperationalNotification.php:25:        if ((bool) config('services.operational_notifications.mail_enabled', false)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:28:        private readonly OperationalNotificationService $notifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:370:            $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:384:            $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:397:            $this->notifications->send($case->opener, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/UserNotificationPreferenceService.php:7:use App\Models\NotificationPreference;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/UserNotificationPreferenceService.php:11:final class UserNotificationPreferenceService
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/UserNotificationPreferenceService.php:13:    public function for(User $user): NotificationPreference
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/UserNotificationPreferenceService.php:15:        return $user->notificationPreference()->firstOrCreate([], $this->supported($this->defaults($user)));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/UserNotificationPreferenceService.php:19:    public function update(User $user, array $values): NotificationPreference
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/UserNotificationPreferenceService.php:26:        return $user->notificationPreference()->updateOrCreate([], $values);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/UserNotificationPreferenceService.php:54:        if (!Schema::hasTable('notification_preferences')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/UserNotificationPreferenceService.php:58:        $columns = array_flip(Schema::getColumnListing('notification_preferences'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:25:        private readonly OperationalNotificationService $notifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:188:                $this->notifications->order(
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:203:                // notification kanala ne sme korisniku vratiti HTTP 500.
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:204:                Log::warning('Document notification failed after successful issuance.', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:265:                $this->notifications->order(
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:274:                Log::warning('Document cancellation notification failed.', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:24:        private readonly OperationalNotificationService $notifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:103:            $this->notifications->order(
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:178:            if ($locked->warranty?->expires_at !== null && $scheduledAt->copy()->startOfDay()->gt($locked->warranty->expires_at)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:179:                throw ValidationException::withMessages(['scheduled_at' => 'Termin održavanja ne može biti nakon isteka garancije.']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:217:                if ($candidate->lte($warranty->expires_at)) $next = $candidate;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/WarrantyService.php:255:        return Schema::hasTable('warranty_rules') && Schema::hasTable('product_warranties') && Schema::hasTable('warranty_maintenance_records');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:21:        private readonly OperationalNotificationService $notifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:81:            $this->notifications->order($order->supplier, 'order.payment_proof_submitted', 'Nova potvrda uplate', sprintf('%s je poslao potvrdu uplate od %s RSD za %s.', $actor->displayName(), number_format((float) $payment->amount_rsd, 2, ',', '.'), $order->order_number), $order, ['icon' => 'wallet', 'severity' => 'warning']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:309:            $this->notifications->order($payment->order->user, 'order.payment_'.$payment->status, $title, $message, $payment->order, ['icon' => 'wallet', 'severity' => $severity]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:10:use App\Models\NotificationPreference;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:37:            'active_warranties' => $this->safeCount(fn () => ProductWarranty::query()->where('user_id', $user->id)->where('status', 'active')->whereDate('expires_at', '>=', today())->count(), 'product_warranties'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:60:    public function preference(User $user): NotificationPreference
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:63:            return $user->notificationPreference()->firstOrCreate([], $this->preferenceDefaults($user));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:65:            return new NotificationPreference($this->preferenceDefaults($user));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:184:        if (!Schema::hasTable('product_warranties')) return collect();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:194:                    'expires_at' => $warranty->expires_at,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ApiAccessService.php:40:            'notifications' => app(ModuleVisibilityService::class)->enabled('notifications') && $has('notifications.view'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ApiAccessService.php:43:            'warranties' => app(ModuleVisibilityService::class)->enabled('warranties') && $has('warranties.view_own'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:254:            'product_warranties',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:379:            'product_warranties',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:433:            'product_warranties',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:482:        foreach (['notifications', 'mobile_push_outbox', 'jobs', 'failed_jobs', 'idempotency_keys'] as $table) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:26:        'product_warranties',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:43:        'notifications',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:50:        'product_warranties',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:251:                'product_warranties',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/FieldOperationsService.php:25:        private readonly OperationalNotificationService $notifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/FieldOperationsService.php:241:        $this->notifications->send($customer, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/MobilePushOutboxService.php:22:            ->where('notifications_enabled', true)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:36:        private readonly OperationalNotificationService $notifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:130:            $this->notifications->order(
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:140:            $this->notifications->commission($updated->commission->user, 'commission.cancelled', 'Provizija je stornirana', 'Provizija je stornirana jer je porudžbina '.$updated->order_number.' otkazana.', $updated->commission, ['severity' => 'danger']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:152:            $this->notifications->order(
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:189:            $this->notifications->order($updated->user, 'order.payment_status_changed', 'Promenjen status plaćanja', 'Status plaćanja za '.$updated->order_number.' je '.$updated->payment_status.'.', $updated);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:217:            $this->notifications->order($updated->user, 'order.tracking_changed', 'Dodat je tracking broj', 'Tracking broj za '.$updated->order_number.' je '.$trackingNumber.'.', $updated, ['severity' => 'success']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:443:            $this->notifications->order(
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:500:            $this->notifications->order(
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:510:            $this->notifications->order(
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:19:        private readonly OperationalNotificationService $notifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:213:        $this->notifications->commission(
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ExpoPushTransport.php:20:        if ($device === null || !$device->isActive() || !$device->notifications_enabled || $device->push_provider !== 'expo' || $token === '') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderShipmentService.php:23:        private readonly OperationalNotificationService $notifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderShipmentService.php:163:            $this->notifications->order($updatedOrder->user, 'order.shipped', 'Porudžbina je poslata', implode(' ', $parts), $updatedOrder, ['severity' => 'success', 'icon' => 'truck']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderOperationalService.php:18:        private readonly OperationalNotificationService $notifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderOperationalService.php:46:            $this->notifications->order($updated->user, 'order.accepted', 'Porudžbina je preuzeta', 'Odgovorno lice je preuzelo obradu porudžbine '.$updated->order_number.'.', $updated);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderOperationalService.php:124:        $this->notifications->order($newSupplier, 'order.assigned', 'Dodeljena vam je porudžbina', 'Porudžbina '.$updated->order_number.' je dodeljena vama. Razlog: '.$reason, $updated, ['severity' => 'warning']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderOperationalService.php:126:            $this->notifications->order($oldSupplier, 'order.reassigned_away', 'Porudžbina je ponovo dodeljena', 'Porudžbina '.$updated->order_number.' više nije dodeljena vama.', $updated, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderOperationalService.php:133:            $this->notifications->order($updated->user, 'order.supplier_changed', 'Promenjeno odgovorno lice', 'Za porudžbinu '.$updated->order_number.' novo odgovorno lice je '.$newSupplier->displayName().'.', $updated);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderOperationalService.php:168:            $this->notifications->order($updated->user, 'order.deadlines_changed', 'Ažurirani su rokovi porudžbine', $message, $updated);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/WarrantyCertificatePayloadService.php:66:            'expires_at' => $warranty->expires_at?->format('d.m.Y'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SettingsService.php:105:        'warranty_expiry_notice_days' => '30',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/PortalConversationService.php:17:    public function __construct(private readonly OperationalNotificationService $notifications)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/PortalConversationService.php:134:            $this->notifications->send($conversation->customer, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/PortalConversationService.php:200:                $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:28:        private readonly OperationalNotificationService $notifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:219:                $this->notifications->order($supplier, 'order.created_for_supplier', 'Nova porudžbina', 'Korisnik '.$user->displayName().' je poslao porudžbinu '.$fresh->order_number.'.', $fresh, ['severity' => 'warning']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:220:                $this->notifications->order($user, 'order.created', 'Porudžbina je kreirana', 'Porudžbina '.$fresh->order_number.' je uspešno poslata odgovornom licu '.$supplier->displayName().'.', $fresh, ['severity' => 'success']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderService.php:222:                Log::warning('Order creation in-app notification failed after commit.', ['order_id' => $fresh->id, 'exception' => $exception]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:27:        private readonly OperationalNotificationService $notifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:416:            $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/MobilePushDispatcher.php:251:            'notifications_enabled' => false,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:67:        if ($actor->hasPermission('warranties.manage') || $actor->hasPermission('warranties.view_own')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:279:        $own = $actor->hasPermission('warranties.view_own');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:489:        } elseif ($actor->hasPermission('warranties.view_own')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:530:            $actor->hasPermission('notifications.view'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:533:            route('notifications.index'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:534:            ['notifications', 'inbox'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:24:final class OperationalAutomationService
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:28:        private readonly OperationalNotificationService $notifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:73:                'notification_count' => $summary['notifications'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:107:    /** @return array{examined:int,alerts:int,notifications:int,resolved:int,types:array<string,int>} */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:113:        $notifications = 0;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:140:            $notifications += $sent;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:163:                $alerts += $created; $notifications += $sent; $types['processing_overdue'] = ($types['processing_overdue'] ?? 0) + 1;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:167:                $alerts += $created; $notifications += $sent; $types['shipping_overdue'] = ($types['shipping_overdue'] ?? 0) + 1;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:193:                $alerts += $created; $notifications += $sent; $types['payment_overdue'] = ($types['payment_overdue'] ?? 0) + 1;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:222:                        $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:232:                        $notifications++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:270:                        $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:280:                        $notifications++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:321:                        $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:331:                        $notifications++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:369:                        $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:379:                        $notifications++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:414:                        $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:424:                        $notifications++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:455:                        $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:460:                        $notifications++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:490:                        $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:495:                        $notifications++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:503:        if ($this->settings->get('automation_warranty_alerts_enabled', '1') === '1' && Schema::hasTable('product_warranties')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:504:            $expiryDays = $this->intSetting('warranty_expiry_notice_days', 30, 1, 365);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:512:            foreach ($expiring as $warranty) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:513:                $key = 'warranty_expiring:'.$warranty->id;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:515:                $message = sprintf('%s za %s ističe %s.', $warranty->warranty_number, $warranty->product_name_snapshot, $warranty->expires_at?->format('d.m.Y'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:517:                    'type' => 'warranty_expiring', 'severity' => 'warning', 'order_id' => $warranty->order_id,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:525:                        $this->notifications->send($warranty->user, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:526:                            'event' => 'warranty.expiring', 'title' => $alert->title, 'message' => $message,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:530:                        $notifications++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:533:                        $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:534:                            'event' => 'warranty.expiring', 'title' => $alert->title, 'message' => $message,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:538:                        $notifications++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:542:                $types['warranty_expiring'] = ($types['warranty_expiring'] ?? 0) + 1;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:565:                        $this->notifications->send($warranty->user, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:570:                        $notifications++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:573:                        $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:578:                        $notifications++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:589:            $notifications += $collection['reminders'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:600:        return compact('examined', 'alerts', 'notifications', 'resolved', 'types');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:623:                $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:688:    /** @return array{examined:int,alerts:int,notifications:int,resolved:int,types:array<string,int>} */
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:692:            return ['examined' => 0, 'alerts' => 0, 'notifications' => 0, 'resolved' => 0, 'types' => []];
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:697:        $notifications = 0;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:700:                $preference = $recipient->notificationPreference()->first();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:704:                $this->notifications->send($recipient, [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:713:                $notifications++;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:720:            'notifications' => $notifications,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ModuleVisibilityService.php:23:        'notifications' => ['label' => 'Obaveštenja', 'description' => 'In-app obaveštenja i korisničke preference.'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php:7:use App\Models\NotificationPreference;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php:15:final class OperationalNotificationService
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php:81:                Log::warning('Operational notification failed.', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php:93:    private function preference(User $recipient): NotificationPreference
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php:96:            return $recipient->notificationPreference()->firstOrCreate([], [
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php:112:            return new NotificationPreference([
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php:144:    private function categoryEnabled(NotificationPreference $preference, string $category): bool
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AutomationReadinessService.php:17:            'notification_preferences' => ['id', 'user_id', 'in_app_enabled', 'email_enabled', 'daily_digest'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/NotificationPreference.php:10:final class NotificationPreference extends Model
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/MobileDevice.php:26:        'notifications_enabled',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/MobileDevice.php:37:            'notifications_enabled' => 'boolean',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/User.php:165:    public function notificationPreference(): HasOne
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/User.php:167:        return $this->hasOne(NotificationPreference::class);
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/AutomationRun.php:14:        'notification_count', 'summary_json', 'error_text', 'triggered_by',
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:191:        Gate::define('warranties.view_own', static fn (User $user): bool => $user->hasPermission('warranties.view_own'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:194:        Gate::define('notifications.view', static fn (User $user): bool => $user->hasPermission('notifications.view'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/Api/V1/StoreMobileDeviceRequest.php:29:            'notifications_enabled' => ['sometimes', 'boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/Api/V1/UpdateNotificationPreferencesRequest.php:9:final class UpdateNotificationPreferencesRequest extends FormRequest
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/Api/V1/UpdateMobileDeviceRequest.php:27:            'notifications_enabled' => ['sometimes', 'boolean'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/Api/V1/MobileDeviceResource.php:29:            'notifications_enabled' => (bool) $this->notifications_enabled,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Resources/Api/V1/NotificationPreferenceResource.php:10:final class NotificationPreferenceResource extends JsonResource
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/AutomationController.php:12:use App\Services\OperationalAutomationService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/AutomationController.php:44:            'warranty_expiry_notice_days' => ['required', 'integer', 'min:1', 'max:365'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/AutomationController.php:57:            'warranty_expiry_notice_days' => (string) $data['warranty_expiry_notice_days'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/AutomationController.php:65:    public function run(Request $request, OperationalAutomationService $automation, AuditLogger $audit): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/NotificationController.php:15:        return view('notifications.index', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/NotificationController.php:16:            'notifications' => $request->user()->notifications()->latest()->paginate(40),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/NotificationController.php:21:    public function read(Request $request, string $notification): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/NotificationController.php:23:        $entry = $request->user()->notifications()->findOrFail($notification);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:56:            'warranties_view_own' => $this->allows($user, 'warranties.view_own'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:130:            'notificationPreference' => null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:139:            $data['notificationPreference'] = $portalService->preference($user);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:149:                $fallback['notificationPreference'] = $portalService->preference($user);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:243:        $push($items, (bool) (($access['warranties_manage'] ?? false) || ($access['warranties_view_own'] ?? false)), (int) $warrantyStats['maintenance_due'], 'Preventivno održavanje', 'Termini dospevaju u narednih sedam dana.', ($access['warranties_manage'] ?? false) ? route('admin.warranties.index') : route('warranties.index'), 'info', 'shield');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:381:        if (!($access['warranties_manage'] ?? false) && !($access['warranties_view_own'] ?? false)) return $fallback;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:383:            if (!$this->tableHasColumns('product_warranties', ['id', 'user_id', 'status', 'expires_at', 'next_maintenance_at'])) return $fallback;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/WarrantyController.php:505:            'expires_at' => optional($warranty->expires_at)->toDateString(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/NotificationController.php:18:        $query = $request->user()->notifications()->latest();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/NotificationController.php:26:    public function read(Request $request, string $notification): NotificationResource
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/NotificationController.php:28:        $entry = $request->user()->notifications()->findOrFail($notification);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/BootstrapController.php:8:use App\Http\Resources\Api\V1\NotificationPreferenceResource;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/BootstrapController.php:12:use App\Services\UserNotificationPreferenceService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/BootstrapController.php:23:        UserNotificationPreferenceService $preferences,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/BootstrapController.php:53:            'notification_counts' => [
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/BootstrapController.php:56:            'notification_preferences' => (new NotificationPreferenceResource($preference))->resolve($request),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AccountController.php:8:use App\Http\Requests\Api\V1\UpdateNotificationPreferencesRequest;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AccountController.php:11:use App\Http\Resources\Api\V1\NotificationPreferenceResource;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AccountController.php:14:use App\Services\UserNotificationPreferenceService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AccountController.php:51:    public function notifications(
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AccountController.php:52:        UpdateNotificationPreferencesRequest $request,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AccountController.php:53:        UserNotificationPreferenceService $preferences,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AccountController.php:55:    ): NotificationPreferenceResource {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AccountController.php:60:            'account.notification_preferences_updated_api',
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AccountController.php:69:        return new NotificationPreferenceResource($preference);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AccountController.php:92:                'notifications_enabled' => false,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AuthTokenController.php:76:                            'notifications_enabled' => false,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/WarrantyController.php:97:            'expires_at' => optional($warranty->expires_at)->toDateString(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/MobileDeviceController.php:53:        foreach (['device_name', 'push_provider', 'app_version', 'build_number', 'locale', 'timezone', 'notifications_enabled'] as $field) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/MobileDeviceController.php:61:        if (($created || $wasRevoked) && !array_key_exists('notifications_enabled', $attributes)) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/MobileDeviceController.php:62:            $attributes['notifications_enabled'] = true;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/MobileDeviceController.php:183:                'notifications_enabled' => false,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/MobileDeviceController.php:212:                'notifications_enabled' => false,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountController.php:7:use App\Models\NotificationPreference;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountController.php:22:        $user = $request->user()->loadMissing(['role', 'group', 'notificationPreference']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountController.php:23:        $preference = $user->notificationPreference ?? new NotificationPreference([
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountController.php:40:            'notificationPreference' => $preference,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountController.php:84:    public function notifications(Request $request, AuditLogger $audit): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountController.php:87:        $before = $user->notificationPreference()->first()?->toArray();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountController.php:102:        if (Schema::hasTable('notification_preferences')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountController.php:103:            $existing = array_flip(Schema::getColumnListing('notification_preferences'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountController.php:106:        $preference = $user->notificationPreference()->updateOrCreate([], $values);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountController.php:107:        $audit->log('account.notification_preferences_updated', 'Ažurirane postavke obaveštenja.', $preference, $before, $preference->toArray(), user: $user);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/WarrantyController.php:46:            abort_unless($request->user()->can('warranties.view_own'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Auth/PasswordResetController.php:44:            Log::error('Password reset notification failed.', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/RunOperationalAutomationCommand.php:8:use App\Services\OperationalAutomationService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/RunOperationalAutomationCommand.php:21:    public function handle(OperationalAutomationService $automation): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/RunOperationalAutomationCommand.php:27:                '%s task=%s status=%s examined=%d alerts=%d notifications=%d resolved=%d',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/RunOperationalAutomationCommand.php:33:                (int) ($summary['notifications'] ?? 0),
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OrderEmailsDoctorCommand.php:32:            'product_warranties' => ['duration_months', 'duration_days'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CustomerPortalDoctorCommand.php:37:        $failed = !$this->checkColumns('notification_preferences', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CustomerPortalDoctorCommand.php:125:            $data['notificationPreference'] = $portal->preference($actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OperationsDoctorCommand.php:38:        'notifications' => ['id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OperationsDoctorCommand.php:72:        $requiredPermissions = ['commissions.view_own', 'commissions.manage', 'orders.reassign', 'orders.internal_notes', 'orders.confirm_delivery', 'orders.reopen', 'notifications.view'];
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/OperationsDoctorCommand.php:91:            $this->line('<fg=green>PASS</> Provizije, scope i notifications upiti su uspešni.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:38:        'notifications',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:58:        'product_warranties',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:79:        'notification_preferences',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:95:        'notifications' => ['id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at', 'created_at', 'updated_at'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:120:        'product_warranties' => ['id', 'warranty_number', 'order_id', 'order_item_id', 'product_id', 'user_id', 'status', 'starts_at', 'expires_at', 'duration_months', 'duration_days', 'serial_numbers_json', 'next_maintenance_at'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:141:        'notification_preferences' => ['id', 'user_id', 'in_app_enabled', 'email_enabled', 'order_updates', 'payment_alerts', 'document_updates', 'after_sales_updates', 'warranty_updates', 'service_updates', 'receivable_updates', 'daily_digest'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:197:        'notifications.view',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:220:        'warranties.view_own',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CmsV215DoctorCommand.php:100:            'notifications.index',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AutomationDoctorCommand.php:10:use App\Services\OperationalAutomationService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AutomationDoctorCommand.php:25:    protected $description = 'Proveri automatizaciju, scheduler evidenciju, upozorenja i notification preferences';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/AutomationDoctorCommand.php:27:    public function handle(AutomationReadinessService $readiness, OperationalAutomationService $automation): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CmsV220DoctorCommand.php:32:            database_path('migrations/2026_08_06_000040_add_push_notification_preference_v2_2_0.php'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CmsV220DoctorCommand.php:48:            'api.v1.me.notification-preferences',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CmsV220DoctorCommand.php:55:            'api.v1.notifications.index',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CmsV220DoctorCommand.php:56:            'api.v1.notifications.read',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CmsV220DoctorCommand.php:57:            'api.v1.notifications.read-all',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CmsV220DoctorCommand.php:74:        if (Schema::hasTable('notification_preferences') && !Schema::hasColumn('notification_preferences', 'push_enabled')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CmsV220DoctorCommand.php:75:            $this->error('FAIL Nedostaje notification_preferences.push_enabled.');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/WarrantiesDoctorCommand.php:20:            'product_warranties' => ['id', 'warranty_number', 'order_id', 'order_item_id', 'user_id', 'status', 'starts_at', 'expires_at', 'duration_months', 'duration_days', 'next_maintenance_at'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/WarrantiesDoctorCommand.php:38:        foreach (['warranties.view_own', 'warranties.manage'] as $permission) {
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:161:        <form method="post" action="{{ route('account.notifications') }}" class="notification-preference-grid">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:166:                <input type="checkbox" name="in_app_enabled" value="1" @checked($notificationPreference->in_app_enabled)>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:170:                <input type="checkbox" name="email_enabled" value="1" @checked($notificationPreference->email_enabled)>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:174:                <input type="checkbox" name="order_updates" value="1" @checked($notificationPreference->order_updates)>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:178:                <input type="checkbox" name="payment_alerts" value="1" @checked($notificationPreference->payment_alerts)>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:182:                <input type="checkbox" name="document_updates" value="1" @checked($notificationPreference->document_updates ?? true)>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:186:                <input type="checkbox" name="after_sales_updates" value="1" @checked($notificationPreference->after_sales_updates ?? true)>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:190:                <input type="checkbox" name="warranty_updates" value="1" @checked($notificationPreference->warranty_updates ?? true)>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:194:                <input type="checkbox" name="service_updates" value="1" @checked($notificationPreference->service_updates ?? true)>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:198:                <input type="checkbox" name="receivable_updates" value="1" @checked($notificationPreference->receivable_updates ?? true)>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:202:                <input type="checkbox" name="commission_updates" value="1" @checked($notificationPreference->commission_updates)>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:206:                <input type="checkbox" name="stock_alerts" value="1" @checked($notificationPreference->stock_alerts)>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:210:                <input type="checkbox" name="daily_digest" value="1" @checked($notificationPreference->daily_digest)>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:128:            <span data-module-visibility="{{ $moduleVisibility->enabled('notifications') ? '1' : '0' }}">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:129:@can('notifications.view')
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:130:<a class="notification-header-button" href="{{ route('notifications.index') }}" aria-label="Obaveštenja" title="Obaveštenja">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:144:            @canany(['catalog.view','orders.manage','orders.view_own','system.manage_users','warranties.manage','warranties.view_own','after_sales.manage','after_sales.view_own','stock.view','reports.view','commissions.manage','commissions.view_own','notifications.view'])
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:179:            @canany(['orders.view_own','orders.manage','after_sales.view_own','after_sales.manage','field_operations.view','service_parts.view','service_parts.procurement','warranties.view_own','warranties.manage','receivables.manage'])
--- MOBILE WARRANTY NOTIFICATION HITS ---
/home/icaffeco/ald1n-project/apps/mobile/current/src/lib/api/endpoints.ts:155:    notificationPreferences: async (input: Partial<NotificationPreferences>) => {
/home/icaffeco/ald1n-project/apps/mobile/current/src/lib/api/endpoints.ts:156:      const response = await apiRequest<{ data: NotificationPreferences }>('me/notification-preferences', {
/home/icaffeco/ald1n-project/apps/mobile/current/src/lib/api/endpoints.ts:268:  notifications: {
/home/icaffeco/ald1n-project/apps/mobile/current/src/lib/api/endpoints.ts:270:      apiRequest<PaginatedResponse<BusinessNotification>>(`notifications${queryString({ unread, per_page: 60 })}`),
/home/icaffeco/ald1n-project/apps/mobile/current/src/lib/api/endpoints.ts:272:      const response = await apiRequest<{ data: BusinessNotification }>(`notifications/${id}/read`, { method: 'POST' });
/home/icaffeco/ald1n-project/apps/mobile/current/src/lib/api/endpoints.ts:276:      const response = await apiRequest<{ data: { marked_read: number; unread: number } }>('notifications/read-all', { method: 'POST' });
/home/icaffeco/ald1n-project/apps/mobile/current/src/lib/api/client.ts:170:        message: 'Slanje fotografija je isteklo. Pokusaj ponovo.',
/home/icaffeco/ald1n-project/apps/mobile/current/src/lib/api/client.ts:255:      throw new ApiError(0, { message: 'Preuzimanje je isteklo. Pokušaj ponovo.', code: 'request_timeout' });
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/layout/app-bottom-nav.tsx:12:type MainRoute = '/home' | '/catalog' | '/orders' | '/notifications' | '/account';
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/layout/app-bottom-nav.tsx:13:type NavKey = 'home' | 'catalog' | 'orders' | 'notifications' | 'account';
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/layout/app-bottom-nav.tsx:48:  if (pathname === '/notifications') return 'notifications';
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/layout/app-bottom-nav.tsx:53:    || pathname === '/notification-settings'
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/layout/app-bottom-nav.tsx:103:      key: 'notifications',
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/layout/app-bottom-nav.tsx:105:      route: '/notifications',
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/layout/app-bottom-nav.tsx:107:      visible: hasFeature('notifications'),
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/layout/app-bottom-nav.tsx:108:      badge: bootstrap?.notification_counts.unread || undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/layout/app-bottom-nav.tsx:117:  ], [bootstrap?.notification_counts.unread, hasFeature]);
/home/icaffeco/ald1n-project/apps/mobile/current/src/components/ui/glyph.tsx:9:  bell: { ios: 'bell.fill', android: 'notifications', web: 'notifications' },
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-query-keys.ts:12:  warranty: (id: number) => ['admin', 'warranties', 'detail', id] as const,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/admin-query-keys.ts:13:  warrantyRules: () => ['admin', 'warranties', 'rules'] as const,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/warranties-admin-api.ts:3:export type AdminWarrantyStatus = 'active' | 'expired' | 'void';
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/warranties-admin-api.ts:23:  warranty_number: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/warranties-admin-api.ts:34:  expires_at: string | null;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/warranties-admin-api.ts:100:    expiring: number;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/warranties-admin-api.ts:190:  expires_at: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/warranties-admin-api.ts:285:    warrantyId: number,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/warranties-admin-api.ts:291:        `admin/warranties/${warrantyId}/maintenance/${recordId}/schedule`,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/warranties-admin-api.ts:300:    warrantyId: number,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/warranties-admin-api.ts:306:        `admin/warranties/${warrantyId}/maintenance/${recordId}/complete`,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:25:function assertWarrantyId(warrantyId: number): void {
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:26:  if (!Number.isInteger(warrantyId) || warrantyId <= 0) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:27:    throw new Error('Neispravan identifikator garancije.');
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:31:function expectedWarrantyPdfPath(warrantyId: number): string {
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:32:  assertWarrantyId(warrantyId);
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:33:  return `/api/v1/warranties/${warrantyId}.pdf`;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:36:function expectedAdminWarrantyPdfPath(warrantyId: number): string {
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:37:  assertWarrantyId(warrantyId);
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:38:  return `admin/warranties/${warrantyId}.pdf`;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:64:  warrantyId: number,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:65:  warrantyNumber: string,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:90:  const safeNumber = sanitizeWarrantyNumber(warrantyNumber);
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:94:    `${cachePrefix}-${warrantyId}-${name}`,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:117:  warrantyNumber: string,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:128:    dialogTitle: `Garantni list ${warrantyNumber}`,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:133:  warrantyId: number,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:134:  warrantyNumber: string,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:137:    expectedWarrantyPdfPath(warrantyId),
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:138:    warrantyId,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:139:    warrantyNumber,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:140:    'warranty',
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:145:  warrantyId: number,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:146:  warrantyNumber: string,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:149:    warrantyId,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:150:    warrantyNumber,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:152:  await openDownloadedWarrantyPdf(downloaded, warrantyNumber);
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:156:  warrantyId: number,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:157:  warrantyNumber: string,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:160:    expectedAdminWarrantyPdfPath(warrantyId),
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:161:    warrantyId,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:162:    warrantyNumber,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:163:    'admin-warranty',
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:168:  warrantyId: number,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:169:  warrantyNumber: string,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:172:    warrantyId,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:173:    warrantyNumber,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/warranties/warranty-pdf.ts:175:  await openDownloadedWarrantyPdf(downloaded, warrantyNumber);
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/auth/auth-provider.tsx:55:      notification_counts: {
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/auth/auth-provider.tsx:56:        ...current.notification_counts,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/push-notification-bridge.tsx:2:import * as Notifications from 'expo-notifications';
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/push-notification-bridge.tsx:7:import { resolvePushNotificationNavigation } from '@/features/notifications/notification-routing';
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/push-notification-bridge.tsx:8:import { getPushPermissionState, registerCurrentDeviceForPush } from '@/features/notifications/push-service';
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/push-notification-bridge.tsx:22:  const requestId = response.notification.request.identifier;
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/push-notification-bridge.tsx:26:  const data = response.notification.request.content.data ?? {};
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/push-notification-bridge.tsx:45:  router.push('/notifications');
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/push-notification-bridge.tsx:57:      void queryClient.invalidateQueries({ queryKey: ['notifications'] });
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/push-service.ts:5:import * as Notifications from 'expo-notifications';
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/push-service.ts:115:    notifications_enabled: true
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/push-service.ts:128:    notifications_enabled: false
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/notification-routing.ts:13:      kind: 'notifications';
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/notification-routing.ts:136:      kind: 'notifications',
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/notification-routing.ts:166:      kind: 'notifications',
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/notification-routing.ts:220:    kind: 'notifications',
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/notification-routing.ts:226:  notification: Pick<
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/notification-routing.ts:233:      notification.event,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/notification-routing.ts:236:      notification.target,
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/notification-routing.ts:239:      notification.data[
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/notification-routing.ts:244:      notification.data[
/home/icaffeco/ald1n-project/apps/mobile/current/src/features/notifications/notification-routing.ts:249:      notification.route
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:80:  warranty_updates: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:98:  notification_counts: { unread: number };
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:99:  notification_preferences: NotificationPreferences;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:114:  expires_at: Nullable<string>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:487:export type WarrantyStatus = 'active' | 'expired' | 'void';
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:492:  warranty_number: string;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:499:  expires_at: Nullable<string>;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:595:  notifications_enabled: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:614:  notifications_enabled?: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/types/api.ts:625:  notifications_enabled?: boolean;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/notifications.tsx:15:import { resolveBusinessNotificationNavigation } from '@/features/notifications/notification-routing';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/notifications.tsx:29:  const allowed = hasFeature('notifications');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/notifications.tsx:30:  const query = useQuery({ queryKey: ['notifications'], queryFn: () => api.notifications.list(), enabled: allowed });
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/notifications.tsx:31:  const read = useMutation({ mutationFn: api.notifications.read, onSuccess: async () => { await client.invalidateQueries({ queryKey: ['notifications'] }); await refreshBootstrap(); } });
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/notifications.tsx:33:    mutationFn: api.notifications.readAll,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/notifications.tsx:38:        ['notifications'],
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:55:    hasFeature('notifications') ? { title: 'Obaveštenja', copy: `${bootstrap?.notification_counts.unread ?? 0} nepročitanih`, glyph: 'bell' as const, route: '/notifications' as const } : null,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:60:    route: '/catalog' | '/admin/catalog/create' | '/admin/commissions' | '/commissions' | '/cart' | '/orders' | '/admin' | '/notifications';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/home.tsx:184:          <Metric value={String(bootstrap?.notification_counts.unread ?? 0)} label="Nepročitano" tone="primary" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/orders.tsx:52:              Moje garancije
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/account.tsx:691:            '/notification-settings'
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/_layout.tsx:102:        name="notifications"
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/_layout.tsx:105:          href: hasFeature('notifications') ? undefined : null,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/(tabs)/_layout.tsx:106:          tabBarBadge: bootstrap?.notification_counts.unread || undefined,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/notification-settings.tsx:20:} from '@/features/notifications/push-service';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/notification-settings.tsx:29:  { key: 'warranty_updates', title: 'Garancije', copy: 'Garancije i preventivno održavanje.' },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/notification-settings.tsx:46:  const allowed = hasFeature('notifications');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/notification-settings.tsx:48:  const preferences = bootstrap?.notification_preferences;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/notification-settings.tsx:64:    mutationFn: (input: Partial<NotificationPreferences>) => api.account.notificationPreferences(input),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/notification-settings.tsx:78:      await api.account.notificationPreferences({ push_enabled: true });
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/notification-settings.tsx:138:  const deviceReady = Boolean(currentDevice?.push_registered && currentDevice.notifications_enabled);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:42:import { openAdminWarrantyPdf } from '@/features/warranties/warranty-pdf';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:75:  const warrantyId = Number(rawId);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:77:    Number.isInteger(warrantyId)
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:78:    && warrantyId > 0;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:88:  const openPdf = async (warrantyId: number, warrantyNumber: string) => {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:97:        warrantyId,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:98:        warrantyNumber,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:115:  const [expiresAt, setExpiresAt] = useState('');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:136:    queryKey: adminQueryKeys.warranty(warrantyId),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:137:    queryFn: () => apiAdminWarranties.detail(warrantyId),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:142:    const warranty = query.data;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:144:    if (!warranty) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:148:    setStartsAt(warranty.starts_at ?? '');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:149:    setExpiresAt(warranty.expires_at ?? '');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:150:    setSerialNumbers(warranty.serial_numbers.join('\n'));
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:151:    setTerms(warranty.terms ?? '');
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:159:      adminQueryKeys.warranty(warrantyId),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:176:      apiAdminWarranties.update(warrantyId, input),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:200:        warrantyId,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:244:          warrantyId,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:255:        warrantyId,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:314:    if (!startsAt || !expiresAt) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:318:        message: 'Unesi datum početka i datum isteka garancije.',
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:323:    if (expiresAt < startsAt) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:327:        message: 'Datum isteka ne može biti pre datuma početka.',
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:334:      expires_at: expiresAt,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:421:      <UnavailableState title="Administracija garancija nije dostupna" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:427:      <UnavailableState title="Neispravan identifikator garancije" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:432:    return <LoadingState label="Učitavanje garancije…" />;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:444:  const warranty = query.data;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:456:        title={warranty.warranty_number}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:464:        onPress={() => void openPdf(warranty.id, warranty.warranty_number)}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:473:              {warranty.product_name}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:477:              {warranty.order.order_number}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:479:              {warranty.customer.name}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:484:            {warranty.status_label}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:490:          value={warranty.product_sku ?? '—'}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:495:          value={String(warranty.quantity)}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:500:          value={warranty.customer.phone ?? '—'}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:506:            warranty.customer.address,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:507:            warranty.customer.postal_code,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:508:            warranty.customer.city,
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:514:          value={warranty.rule?.name ?? 'Snapshot bez aktivnog pravila'}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:519:      {warranty.capabilities.can_update ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:536:            value={expiresAt}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:551:            label="Uslovi garancije"
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:572:          value={warranty.starts_at
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:573:            ? formatDate(warranty.starts_at)
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:580:          value={warranty.expires_at
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:581:            ? formatDate(warranty.expires_at)
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:588:          value={durationLabel(warranty)}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:594:          value={warranty.last_maintenance_at
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:595:            ? formatDate(warranty.last_maintenance_at)
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:602:          value={warranty.next_maintenance_at
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:603:            ? formatDate(warranty.next_maintenance_at)
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:614:        {warranty.maintenance_records.length === 0 ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:616:            Za ovu garanciju nije definisano preventivno održavanje.
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:619:          warranty.maintenance_records.map((record) => (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:697:      {warranty.void ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:705:            value={warranty.void.reason ?? '—'}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:711:            value={warranty.void.voided_at
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:712:              ? formatDate(warranty.void.voided_at)
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:719:            value={warranty.void.voided_by_name ?? '—'}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:725:      {warranty.capabilities.can_void ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:728:            Poništi garanciju
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:747:            Poništi garanciju
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:754:        title="Poništi garanciju"
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:756:        confirmLabel="Poništi garanciju"
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:853:function durationLabel(warranty: AdminWarranty): string {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:856:  if (warranty.duration_months) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:857:    parts.push(`${warranty.duration_months} meseci`);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:860:  if (warranty.duration_days) {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/[id].tsx:861:    parts.push(`${warranty.duration_days} dana`);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/rules.tsx:117:    queryKey: adminQueryKeys.warrantyRules(),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/rules.tsx:229:        client.invalidateQueries({ queryKey: adminQueryKeys.warrantyRules() }),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/rules.tsx:269:    return <UnavailableState title="Pravila garancije nisu dostupna" />;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/rules.tsx:273:    return <LoadingState label="Učitavanje pravila garancije…" />;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/rules.tsx:308:        title="Pravila garancije"
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/rules.tsx:323:          Generiši nedostajuće garancije
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/rules.tsx:481:        title="Generiši nedostajuće garancije"
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:36:  { value: 'expired', label: 'Istekle' },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:110:      <UnavailableState title="Administracija garancija nije dostupna" />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:115:    return <LoadingState label="Učitavanje garancija…" />;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:158:          Pravila garancije
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:170:          value={data.stats.expiring}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:192:          placeholder="Broj garancije, porudžbina, SKU ili naziv"
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:220:            { value: '', label: 'Sve garancije' },
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:280:      emptyMessage="Nema garancija za izabrane filtere."
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:294:                <Text style={styles.warrantyNumber}>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:295:                  {item.warranty_number}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:321:              Važi do: {item.expires_at
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:322:                ? formatDate(item.expires_at)
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/warranties/index.tsx:407:    warrantyNumber: {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:13:import { openWarrantyPdf } from '@/features/warranties/warranty-pdf';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:19:function warrantyTone(status: WarrantyStatus): PillTone {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:21:  if (status === 'expired') return 'warning';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:45:  const warrantyId = Number(id);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:48:  const validId = Number.isInteger(warrantyId) && warrantyId > 0;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:50:    queryKey: ['warranties', warrantyId],
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:51:    queryFn: () => api.warranties.detail(warrantyId),
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:56:  if (!validId) return <ErrorState error={new Error('Neispravan identifikator garancije.')} />;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:57:  if (query.isLoading) return <LoadingState label="Učitavanje garancije…" />;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:60:  const warranty = query.data;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:67:      await openWarrantyPdf(warranty.id, warranty.warranty_number);
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:85:        <Text style={styles.back}>‹ Nazad na garancije</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:91:          <Text style={styles.title}>{warranty.warranty_number}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:93:        <Pill tone={warrantyTone(warranty.status)}>{warranty.status_label}</Pill>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:97:        <Text style={styles.productName}>{warranty.product_name}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:98:        {warranty.product_sku ? <Text style={styles.meta}>SKU {warranty.product_sku}</Text> : null}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:100:        <Info label="Porudžbina" value={warranty.order.order_number} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:101:        <Info label="Količina" value={`${warranty.quantity} kom.`} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:104:          value={warranty.serial_numbers.length > 0 ? warranty.serial_numbers.join(', ') : 'Nisu evidentirani'}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:106:        <Info label="Početak" value={warranty.starts_at ? formatDate(warranty.starts_at) : '—'} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:107:        <Info label="Važi do" value={warranty.expires_at ? formatDate(warranty.expires_at) : '—'} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:118:            onPress={() => router.push({ pathname: '/order/[id]', params: { id: String(warranty.order.id) } })}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:126:        <Text style={styles.sectionTitle}>Sažetak garancije</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:127:        <Info label="Trajanje" value={formatDuration(warranty.duration_months, warranty.duration_days)} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:130:          value={warranty.maintenance_interval_months ? `${warranty.maintenance_interval_months} meseci` : 'Nije definisano'}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:134:          value={warranty.last_maintenance_at ? formatDate(warranty.last_maintenance_at) : '—'}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:138:          value={warranty.next_maintenance_at ? formatDate(warranty.next_maintenance_at) : '—'}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:144:        <Text style={styles.bodyText}>{warranty.terms?.trim() || 'Nisu uneti posebni uslovi.'}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:147:      {warranty.status === 'void' ? (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:150:          <Text style={styles.voidCopy}>{warranty.void_reason?.trim() || 'Razlog nije naveden.'}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/[id].tsx:156:        {warranty.maintenance_records.length > 0 ? warranty.maintenance_records.map((record) => (
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/index.tsx:19:  if (status === 'expired') return 'warning';
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/index.tsx:30:      accessibilityLabel={`Otvori garanciju ${item.warranty_number}`}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/index.tsx:38:            <Text style={styles.warrantyNumber}>{item.warranty_number}</Text>
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/index.tsx:48:          <Info label="Važi do" value={item.expires_at ? formatDate(item.expires_at) : '—'} />
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/index.tsx:92:  if (query.isLoading) return <LoadingState label="Učitavanje garancija…" />;
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/index.tsx:113:              title="Moje garancije"
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/index.tsx:125:            message="Garancije se automatski formiraju nakon završene isporuke kada proizvod ima odgovarajuće pravilo garancije."
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/warranties/index.tsx:174:    warrantyNumber: {
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/devices.tsx:56:      {query.data?.length ? query.data.map((device) => <Card key={device.id} style={styles.card}><View style={styles.icon}><Glyph name="device" size={26} color={themeColors.primary} /></View><View style={styles.deviceCopy}><View style={styles.deviceHead}><Text style={styles.deviceName}>{device.device_name ?? humanPlatform(device.platform)}</Text>{device.is_current ? <Pill tone="success">Ovaj uređaj</Pill> : <Pill>{humanPlatform(device.platform)}</Pill>}</View><Text style={styles.meta}>Verzija {device.app_version ?? '—'} · build {device.build_number ?? '—'}</Text><Text style={styles.meta}>Push: {device.push_registered && device.notifications_enabled ? 'registrovan' : 'nije aktivan'}</Text><Text style={styles.meta}>Poslednja aktivnost: {formatDate(device.last_seen_at, true)}</Text><Button variant="danger" onPress={() => confirm(device)} loading={revoke.isPending}>Opozovi sesiju</Button></View></Card>) : <EmptyState title="Nema uređaja" message="Ponovo pokreni aplikaciju kako bi registracija pokušala ponovo." />}
/home/icaffeco/ald1n-project/apps/mobile/current/src/app/_layout.tsx:12:import { PushNotificationBridge } from '@/features/notifications/push-notification-bridge';
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:20:  'src/app/(app)/(tabs)/orders.tsx', 'src/app/(app)/(tabs)/notifications.tsx',
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:24:  'src/app/(app)/notification-settings.tsx',
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:34:  'src/features/warranties/warranty-pdf.ts',
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:41:  'src/features/notifications/push-service.ts',
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:42:  'src/features/notifications/push-notification-bridge.tsx',
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:115:assert(packageJson.dependencies?.['expo-notifications'] === '~57.0.12' && packageLockJson.packages?.['']?.dependencies?.['expo-notifications'] === '~57.0.12' && packageLockJson.packages?.['node_modules/expo-notifications']?.version === '57.0.12', 'expo-notifications prati SDK 57 preporučenu verziju.');
/home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:252:for (const endpoint of ['auth/token', 'auth/google', 'bootstrap', 'catalog/filters', 'products', 'orders/options', 'Idempotency-Key', 'orders', 'notifications', 'devices', 'me/notification-preferences', 'PATCH']) {

============================================================
7. PRE-v1 UNIVERSAL DELETE FOOTPRINT - MAP ONLY, NO IMPLEMENTATION
============================================================
--- CMS CURRENT DELETE / PURGE / ARCHIVE INFRASTRUCTURE ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:120:            foreach ($storedPaths as $path) Storage::disk('local')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:168:            foreach ($storedPaths as $path) Storage::disk('local')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/BackupService.php:95:                $run->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDocumentService.php:180:            $this->nbsIpsQr->delete($storedIpsPath);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerActivationService.php:34:                ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerActivationService.php:106:            $user->tokens()->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerActivationService.php:112:                ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerActivationService.php:118:    public function purgeExpired(): int
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerActivationService.php:129:            ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DirectSaleService.php:96:            ->whereNull('deleted_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:75:            Storage::disk('local')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:51:            'archive' => $this->allows($actor, 'orders.manage'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:71:            'archive' => $this->route('admin.orders.archive', ['order' => $id]),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:479:                    'status_class' => $status === 'issued' ? 'active' : 'archived',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:747:            'cancelled' => 'archived',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:36:    public function purge(Product $product, User $actor, array $input): array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:127:                    ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:140:                    'catalog.total_purge',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:288:        $root = storage_path('app/private/total-product-purge-quarantine/'.bin2hex(random_bytes(16)));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:476:                    ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:512:                $counts['async_rows_deleted'] += DB::table('operational_alerts')->whereIn('id', $matchedIds)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:532:                    ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeService.php:706:        return DB::table($table)->whereIn('id', $ids)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductTypeCategoryService.php:118:                DB::table('product_categories')->whereIn('product_id', $ids)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:180:        if ($product->deleted_at === null || (string) $product->status !== 'archived') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/TotalProductPurgeVerifier.php:357:                if (str_contains($path, 'total-product-purge-quarantine')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/FieldOperationsService.php:119:            foreach ($stored as $file) Storage::disk('local')->delete((string) $file['path']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:178:            $base = DB::table('products')->whereNull('deleted_at')->whereNull('created_by');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:192:        $active = DB::table('products')->whereNull('deleted_at')->where('status', 'active');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:232:                ->whereNull('products.deleted_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:253:            ->whereNull('products.deleted_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:342:            'products_archived' => 0,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:349:            $metrics['products_active'] = (int) DB::table('products')->whereNull('deleted_at')->where('status', 'active')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/DataQualityService.php:350:            $metrics['products_archived'] = (int) DB::table('products')->whereNotNull('deleted_at')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderWorkflowService.php:571:            Storage::disk($disk)->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ManagementReportService.php:230:        $products = DB::table('products')->whereNull('deleted_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogAccessService.php:56:     * - arhivirani artikli se prikazuju samo kroz zaseban archive tok.
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogAccessService.php:61:            $query->whereNull('deleted_at');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogAccessService.php:70:        $query->where('status', 'active')->whereNull('deleted_at');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationDependencyService.php:65:                    ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationDependencyService.php:187:            DB::table('specification_option_dependencies')->whereIn('child_option_id', $childOptionIds)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationDependencyService.php:196:            DB::table('specification_option_dependencies')->whereIn('parent_option_id', $parentOptionIds)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:53:            ->select(['id', 'brand_id', 'sku', 'name', 'model_name', 'slug', 'stock_quantity', 'status', 'deleted_at'])
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:118:                'draft', 'inactive' => $query->where('status', $status)->whereNull('deleted_at'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:119:                'active' => $query->where('status', 'active')->whereNull('deleted_at'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:120:                'archived' => $query->whereRaw('1 = 0'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogQueryService.php:133:            $query->where('status', 'active')->whereNull('deleted_at');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/PasswordResetService.php:25:        $this->purgeExpiredTokens();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/PasswordResetService.php:41:                ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/PasswordResetService.php:57:                ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/PasswordResetService.php:107:            $user->tokens()->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/PasswordResetService.php:111:                ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/PasswordResetService.php:117:    private function purgeExpiredTokens(): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/PasswordResetService.php:124:            ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReceivablesService.php:151:            ReceivableInstallment::query()->where('receivable_case_id', $locked->id)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderShipmentService.php:218:        try { Storage::disk($disk)->delete($path); } catch (Throwable) {}
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/BusinessDocumentPdfService.php:517:                imagedestroy($image);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/BusinessDocumentPdfService.php:520:            imagedestroy($source);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/Pdf/BusinessDocumentPdfService.php:563:            $image->destroy();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAnnouncementService.php:34:        if ($product->status !== 'active' || $product->deleted_at !== null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:224:    public function archive(Product $product, User $user): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:227:        $product->update(['status' => 'archived', 'deleted_at' => now(), 'updated_by' => $user->id, 'locally_modified_at' => now()]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:228:        $this->audit->log('product.archived', 'Arhiviran artikal '.$product->sku, $product, $before, $this->snapshot($product));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:234:        $product->update(['status' => 'inactive', 'deleted_at' => null, 'updated_by' => $user->id, 'locally_modified_at' => now()]);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:355:        DB::table('product_spec_values')->where('product_id', $product->id)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductAdminService.php:425:            'manual_commission_eur','description','notes','stock_quantity','low_stock_threshold','status','deleted_at',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductBulkService.php:152:            DB::table('product_spec_values')->where('product_id', $product->id)->where('field_id', $fieldId)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductBulkService.php:200:            DB::table('product_spec_values')->where('product_id', $product->id)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductBulkService.php:204:        DB::table('product_spec_values')->where('product_id', $product->id)->when($allowed !== [], fn ($query) => $query->whereNotIn('field_id', $allowed), fn ($query) => $query)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:180:            $lockedLine->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:20:    public function purge(SpecificationField $field): array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:42:                    ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:54:                DB::table('product_spec_values')->where('field_id', $fieldId)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:57:                DB::table('product_type_fields')->where('field_id', $fieldId)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:60:                DB::table('specification_options')->where('field_id', $fieldId)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:64:            SpecificationField::query()->whereKey($fieldId)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:193:        })->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:219:            ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/SpecificationFieldLifecycleService.php:305:            ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:17:final class ProductDeletionService
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:68:    public function purge(Product $product, User $actor, bool $deleteFiles): array
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:104:                    'price_amount', 'price_currency', 'status', 'created_by', 'updated_by', 'deleted_at',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:121:                    $deletedInitialMovements = $initialMovements->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:125:                    'product.purged',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:140:                $locked->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductDeletionService.php:158:                    'product.purge_files_failed',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:602:            'archived' => 'Arhiviran',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:200:                ->whereNull('deleted_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/NbsIpsQrService.php:116:            if ($storedPath !== null) Storage::disk('local')->delete($storedPath);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/NbsIpsQrService.php:137:    public function delete(?string $path): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/NbsIpsQrService.php:139:        if (is_string($path) && $path !== '') Storage::disk('local')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:185:            if ($newPublicPath !== null) Storage::disk('public')->delete($newPublicPath);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:247:    public function delete(Product $product, ProductImage $image): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:252:        Storage::disk('public')->delete($image->file_path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:254:        $image->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:364:        imagedestroy($image);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:374:        imagedestroy($rotated);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ProductImageService.php:399:            $image->destroy();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:23:    public function archivedQuery(User $actor): Builder
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:25:        $query = Order::query()->archived();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:33:        $query = $this->archivedQuery($actor)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:35:            ->orderByDesc('archived_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:52:    public function archive(Order $order, User $actor, string $reason): Order
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:59:                'archive_reason' => 'Unesi razlog arhiviranja od najmanje 3 karaktera.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:67:            if ($locked->archived_at !== null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:69:                    'archive_reason' => 'Porudžbina je već arhivirana.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:75:                    'archive_reason' => 'Arhivirati se može samo završena ili otkazana porudžbina.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:84:                'archived_at' => null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:88:                'archived_at' => now(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:89:                'archived_by' => $actor->getAuthIdentifier(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:90:                'archive_reason' => $reason,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:95:                'order.archived',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:102:                    'archived_at' => $locked->archived_at?->toISOString(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:104:                metadata: ['archive_reason' => $reason],
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:113:    public function purgeById(int $orderId, User $actor, string $confirmation, string $reason): void
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:124:                'purge_reason' => 'Unesi razlog trajnog uklanjanja od najmanje 5 karaktera.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:131:                ->whereNull('purged_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:142:            if ($locked->archived_at === null) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:157:                'archived_at' => $locked->archived_at?->toISOString(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:158:                'purged_at' => null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:162:                'purged_at' => now(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:163:                'purged_by' => $actor->getAuthIdentifier(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:164:                'purge_reason' => $reason,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:169:                'order.purged_operationally',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:175:                    'archived_at' => $locked->archived_at?->toISOString(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:176:                    'purged_at' => $locked->purged_at?->toISOString(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:179:                    'purge_reason' => $reason,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:190:            $query = Order::query()->archived()->whereKey($orderId)->lockForUpdate();
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:202:                'archived_at' => $locked->archived_at?->toISOString(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:203:                'archive_reason' => (string) ($locked->archive_reason ?? ''),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:207:                'archived_at' => null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:208:                'archived_by' => null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:209:                'archive_reason' => null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:221:                    'archived_at' => null,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderArchiveService.php:243:                'archive_reason' => 'Porudžbina ima aktivan postprodajni slučaj. Zatvori ga pre arhiviranja.',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Order.php:23:        'expected_processing_at', 'expected_shipping_at', 'last_internal_note_at', 'completed_at', 'completed_by', 'completion_note', 'reopened_at', 'reopened_by', 'reopen_reason', 'archived_at', 'archived_by', 'archive_reason', 'purged_at', 'purged_by', 'purge_reason', 'updated_by',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Order.php:42:            'archived_at' => 'datetime',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Order.php:43:            'purged_at' => 'datetime',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Order.php:158:        return $query->whereNull($this->qualifyColumn('archived_at'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Order.php:164:            ->whereNotNull($this->qualifyColumn('archived_at'))
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Order.php:165:            ->whereNull($this->qualifyColumn('purged_at'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Order.php:170:        return $query->whereNotNull($this->qualifyColumn('purged_at'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Order.php:176:            ->whereNull($this->qualifyColumn('archived_at'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:19:        'low_stock_threshold', 'status', 'created_by', 'updated_by', 'deleted_at', 'legacy_checksum',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:31:            'deleted_at' => 'datetime',
/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php:41:        return $query->where('status', 'active')->whereNull('deleted_at');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportScheduleController.php:48:    public function destroy(Request $request, ReportSchedule $schedule): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportScheduleController.php:51:        $schedule->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldServiceTeamController.php:65:    public function destroy(Request $request, FieldServiceTeam $team, AuditLogger $audit): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportController.php:165:            foreach (Product::query()->whereNull('deleted_at')->orderBy('sku')->cursor() as $product) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ReportController.php:219:        $base = Product::query()->whereNull('deleted_at');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/StockMovementController.php:31:            ->whereNull('deleted_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:40:                ->whereNull('deleted_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:54:                'lowStock' => Product::query()->whereNull('deleted_at')->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->orderBy('stock_quantity')->limit(50)->get(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/InventoryController.php:126:            $rows = Product::query()->whereNull('deleted_at')->orderBy('sku')->get(['sku', 'name', 'stock_quantity', 'low_stock_threshold', 'status']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/SiteAppearanceController.php:290:                    Storage::disk('public')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/SiteAppearanceController.php:297:            Storage::disk('public')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/SiteAppearanceController.php:318:                Storage::disk('public')->delete($old);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/SiteAppearanceController.php:368:            Storage::disk('public')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:16:use App\Services\ProductDeletionService;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:30:        private readonly ProductDeletionService $deletions,
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:38:    public function archived(Request $request): View
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:40:        $query = Product::query()->whereNotNull('deleted_at');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:51:        return view('admin.products.archived', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:52:            'products' => $query->orderByDesc('deleted_at')->orderByDesc('id')->paginate(30)->withQueryString(),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:157:    public function archive(Product $product, Request $request, ProductAdminService $service): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:160:        $service->archive($product, $request->user());
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:171:    public function purge(Product $product, Request $request): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:185:        $result = $this->deletions->purge($product, $request->user(), $request->boolean('delete_images'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:201:    public function totalPurge(Product $product, Request $request, TotalProductPurgeService $purge): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:212:        $result = $purge->purge($product, $request->user(), [
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductController.php:220:            ->route('admin.products.archived')
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductImageController.php:149:    public function destroy(Request $request, Product $product, ProductImage $image, ProductImageService $service): RedirectResponse|JsonResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ProductImageController.php:153:        $service->delete($product, $image);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:213:    public function destroy(string $resource, int $item, CatalogDictionary $dictionary, AuditLogger $audit): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:223:    public function purge(Request $request, string $resource, int $item, CatalogDictionary $dictionary, AuditLogger $audit, SpecificationFieldLifecycleService $lifecycle): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:238:        $cleanup = $lifecycle->purge($model);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php:469:                DB::table('product_categories')->whereIn('product_id', $ids)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/FieldWorkOrderPartController.php:30:    public function destroy(Request $request, FieldWorkOrder $workOrder, FieldWorkOrderPart $line, ServicePartsInventoryService $service): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DocumentSettingsController.php:149:                    imagedestroy($canvas);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DocumentSettingsController.php:152:                imagedestroy($image);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/DocumentSettingsController.php:182:            Storage::disk('public')->delete($path);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:160:    public function archived(Request $request, \App\Services\OrderArchiveService $archives): \Illuminate\View\View
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:164:        return view('admin.orders.archived', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:165:            'orders' => $archives->paginateArchived($request->user(), $query),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:171:    public function archive(Request $request, Order $order, \App\Services\OrderArchiveService $archives): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:174:            'archive_reason' => ['required', 'string', 'min:3', 'max:1000'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:177:        $archives->archive($order, $request->user(), (string) $data['archive_reason']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:180:            ->route('admin.orders.archived')
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:184:    public function restore(Request $request, int $orderId, \App\Services\OrderArchiveService $archives): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:186:        $order = $archives->restoreById($orderId, $request->user());
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:192:    public function purge(Request $request, int $orderId, \App\Services\OrderArchiveService $archives): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:196:            'purge_reason' => ['required', 'string', 'min:5', 'max:1000'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:199:        $archives->purgeById(
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:203:            (string) $data['purge_reason'],
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/OrderController.php:207:            ->route('admin.orders.archived')
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/UserController.php:63:            $user->tokens()->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/WarrantyController.php:62:            'products' => Product::query()->whereNull('deleted_at')->orderBy('name')->limit(1000)->get(['id', 'sku', 'name']),
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CustomerPortalController.php:247:        $user->tokens()->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/UserGroupController.php:56:    public function destroy(UserGroup $userGroup, AuditLogger $audit): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/UserGroupController.php:60:        $userGroup->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/BankAccountController.php:45:    public function destroy(BankAccount $bankAccount, AuditLogger $audit): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/BankAccountController.php:53:        $bankAccount->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/ProductMediaController.php:21:            && $product->deleted_at === null
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:436:            if (!$this->tableHasColumns('products', ['id', 'status', 'stock_quantity', 'low_stock_threshold', 'deleted_at'])) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/DashboardController.php:441:                ->whereNull('deleted_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReportScheduleController.php:138:    public function destroy(Request $request, ReportSchedule $schedule): JsonResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReportScheduleController.php:142:        $schedule->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/InventoryController.php:38:            ->whereNull('deleted_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/InventoryController.php:67:                    ->whereNull('deleted_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/InventoryController.php:216:                    ->whereNull('deleted_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/FieldWorkOrderPartController.php:42:    public function destroy(
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/WarrantyController.php:106:                ->whereNull('deleted_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AccountController.php:97:        $user->tokens()->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AuthTokenController.php:64:    public function destroy(Request $request): JsonResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/AuthTokenController.php:81:                $token->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/MobileDeviceController.php:172:    public function destroy(Request $request, MobileDevice $mobileDevice): JsonResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/MobileDeviceController.php:223:            ->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountController.php:127:        $user->tokens()->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/ProductMediaDownloadController.php:21:            && $product->deleted_at === null
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:30:        // ALD1N B8F V4: archived status bridges to Archive Center; archived rows stay outside the normal catalog.
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:31:        if ($access->canCreateProducts($user) && strtolower(trim((string) $request->query('status', ''))) === 'archived') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:32:            $archiveQuery = [];
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:33:            $archiveSearch = trim((string) $request->query('q', ''));
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:34:            if ($archiveSearch !== '') {
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:35:                $archiveQuery['q'] = $archiveSearch;
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:38:            return redirect()->route('admin.products.archived', $archiveQuery);
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/CatalogController.php:144:            && $product->deleted_at === null;        return view('catalog.show', [
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountSessionController.php:15:    public function destroy(
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/AccountSessionController.php:53:        $user->tokens()->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Auth/AuthenticatedSessionController.php:120:    public function destroy(Request $request, PortalSessionService $portalSessions): RedirectResponse
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CmsV214DoctorCommand.php:58:            'admin.products.purge',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CmsV214DoctorCommand.php:72:            app_path('Services/ProductDeletionService.php'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CmsV214DoctorCommand.php:90:            $deletion = (string) file_get_contents(app_path('Services/ProductDeletionService.php'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CmsV214DoctorCommand.php:99:            if (!str_contains($form, 'name="model_name"') || !str_contains($form, 'purge-product') || !str_contains($form, 'delete_images')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ResetUserPasswordCommand.php:95:            $user->tokens()->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/ResetUserPasswordCommand.php:96:            DB::table('password_reset_tokens')->where('user_id', $user->id)->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PaymentsInventoryDoctorCommand.php:103:            $lowStock = DB::table('products')->whereNull('deleted_at')->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogSettingsDoctorCommand.php:55:            'admin.dictionary.purge',
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogSettingsDoctorCommand.php:180:            ->whereNull('products.deleted_at');
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/DeploymentCheckCommand.php:537:            DB::purge();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CreateSuperAdminCommand.php:74:        $user->tokens()->delete();
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CatalogOwnershipDoctorCommand.php:38:        foreach (['id', 'created_by', 'updated_by', 'status', 'deleted_at'] as $column) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/MigrationsDoctorCommand.php:16:    protected $description = 'Proveri migration fajlove, migrations tabelu, SQL mode, charset i foreign key stanje.';
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PerformanceDoctorCommand.php:146:                    ->whereNull('deleted_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/PerformanceDoctorCommand.php:155:                    ->whereNull('deleted_at')
/home/icaffeco/ald1n-project/apps/cms/current/app/Console/Commands/CustomerPortalMaintenanceCommand.php:20:            $tokens = $activations->purgeExpired();
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/account/show.blade.php:11:    <span class="status-badge status-{{ $user->status === 'active' ? 'active' : 'archived' }}">{{ $user->status }}</span>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/components/icon.blade.php:49:    @case('archive')<path d="M4 7h16v13H4z"/><path d="M3 3h18v4H3z"/><path d="M9 11h6"/>@break
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/guest.blade.php:6:    <meta name="robots" content="noindex,nofollow,noarchive">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:34:    <meta name="robots" content="noindex,nofollow,noarchive">
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:802:        hoverCloseTimers.delete(dropdown);
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:810:        clickPinnedDropdowns.delete(dropdown);
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:820:            hoverCloseTimers.delete(dropdown);
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php:834:                clickPinnedDropdowns.delete(dropdown);
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/orders/index.blade.php:5:<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Broj</th><th>Dobavljač</th><th>Status</th><th>Plaćanje</th><th>Iznos</th><th>Provizija</th><th>Datum</th><th></th></tr></thead><tbody>@forelse($orders as $order)<tr><td data-label="Broj"><strong>{{ $order->order_number }}</strong></td><td data-label="Dobavljač">{{ $order->sales_channel === 'direct_sale' ? 'Direktna prodaja' : ($order->supplier_name_snapshot ?: $order->supplier?->displayName() ?: '—') }}</td><td data-label="Status"><span class="status-badge status-{{ $order->completed_at ? 'completed' : ($order->status === 'cancelled' ? 'archived' : ($order->status === 'shipped' ? 'active' : 'draft')) }}">{{ $order->completed_at ? 'Kompletirana' : $order->status }}</span></td><td data-label="Plaćanje">{{ $order->payment_method }} / {{ $order->payment_status }}</td><td data-label="Iznos">{{ number_format((float)$order->subtotal_rsd,2,',','.') }} RSD</td><td data-label="Provizija">{{ $order->commission ? number_format((float)$order->commission->total_eur,2,',','.').' EUR' : '—' }}</td><td data-label="Datum">{{ $order->created_at?->format('d.m.Y H:i') }}</td><td><a class="button button-ghost button-small" href="{{ route('orders.show',$order) }}">Detalji</a></td></tr>@empty<tr><td colspan="8">Još nema porudžbina.</td></tr>@endforelse</tbody></table></div><div class="pagination-wrap">{{ $orders->links() }}</div>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/index.blade.php:17:@forelse($products as $product)<tr><td data-label="Izbor"><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" data-product-select></td><td data-label="Artikal"><div class="table-product"><div class="table-thumb">@if($product->primaryImage)<img src="{{ $product->primaryImage->url }}" alt="">@else ◫ @endif</div><div><strong>{{ $product->name }}</strong><small>SKU {{ $product->sku }} · {{ $product->images_count }} slika</small></div></div></td><td data-label="Brend / linija">{{ $product->brand?->name ?? '—' }}@if($product->line)<small class="muted"> · {{ $product->line->name }}</small>@endif</td><td data-label="Status"><span class="status-badge status-{{ $product->deleted_at ? 'archived' : $product->status }}">{{ $product->deleted_at ? 'Arhiviran' : $product->status }}</span></td><td data-label="Kompletnost"><div class="mini-completeness"><span><i style="width:{{ (int)$product->completeness_percent }}%"></i></span><b>{{ (int)$product->completeness_percent }}%</b></div></td><td data-label="Cena">{{ number_format((float)$product->price_amount,2,',','.') }} {{ $product->price_currency }}</td><td data-label="Lager">{{ $product->stock_quantity }}</td><td data-label="Izmena">{{ optional($product->updated_at)->format('d.m.Y. H:i') }}</td><td class="row-actions"><a class="button button-small button-ghost" href="{{ route('admin.products.edit',$product) }}">Izmeni</a><a class="button button-small button-ghost" href="{{ route('admin.products.clone',$product) }}">Kloniraj</a>@can('catalog.manage_images')<a class="button button-small button-ghost" href="{{ route('admin.products.images.index',$product) }}">Slike</a>@endcan</td></tr>@empty<tr><td colspan="9"><div class="empty-state">Nema artikala za izabrane filtere.</div></td></tr>@endforelse
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:362:                        @if($product->deleted_at)
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:365:                            <button form="archive-product" class="button button-warning" type="submit" data-confirm="Arhivirati artikal {{ $product->sku }}?"><x-icon name="archive" size="18" /> Arhiviraj artikal</button>
/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/products/form.blade.php:368:                        <details class="product-purge-details">

============================================================
8. CURRENT QUALITY BASELINE - READ ONLY
============================================================

> ald1n-mobile@0.7.0 typecheck
> tsc --noEmit

MOBILE_TYPECHECK=PASS
PASS package.json postoji.
PASS app.config.js postoji.
PASS eas.json postoji.
PASS .env.example postoji.
PASS assets/icon.png postoji.
PASS assets/adaptive-icon.png postoji.
PASS assets/splash-icon.png postoji.
PASS src/app/_layout.tsx postoji.
PASS src/app/(auth)/login.tsx postoji.
PASS src/app/(app)/(tabs)/home.tsx postoji.
PASS src/app/(app)/(tabs)/catalog.tsx postoji.
PASS src/app/(app)/(tabs)/orders.tsx postoji.
PASS src/app/(app)/(tabs)/notifications.tsx postoji.
PASS src/app/(app)/(tabs)/account.tsx postoji.
PASS src/app/(app)/product/[slug].tsx postoji.
PASS src/app/(app)/order/[id].tsx postoji.
PASS src/app/(app)/devices.tsx postoji.
PASS src/app/(app)/cart.tsx postoji.
PASS src/app/(app)/checkout.tsx postoji.
PASS src/app/(app)/notification-settings.tsx postoji.
PASS src/app/(app)/after-sales/index.tsx postoji.
PASS src/app/(app)/after-sales/[id].tsx postoji.
PASS src/app/(app)/after-sales/create/[orderId].tsx postoji.
PASS src/app/(app)/warranties/index.tsx postoji.
PASS src/app/(app)/warranties/[id].tsx postoji.
PASS src/app/(app)/commissions/index.tsx postoji.
PASS src/app/(app)/commissions/[id].tsx postoji.
PASS src/app/(app)/assigned-orders/index.tsx postoji.
PASS src/app/(app)/assigned-orders/[id].tsx postoji.
PASS src/features/warranties/warranty-pdf.ts postoji.
PASS src/features/orders/order-post-create-files.ts postoji.
PASS src/features/after-sales/attachment-picker.ts postoji.
PASS src/features/after-sales/attachment-download.ts postoji.
PASS src/lib/api/client.ts postoji.
PASS src/lib/api/endpoints.ts postoji.
PASS src/features/auth/auth-provider.tsx postoji.
PASS src/features/auth/google-auth.ts postoji.
PASS src/features/device/device-registrar.tsx postoji.
PASS src/features/cart/cart-provider.tsx postoji.
PASS src/features/notifications/push-service.ts postoji.
PASS src/features/notifications/push-notification-bridge.tsx postoji.
PASS docs/openapi.yaml postoji.
PASS tamagui.config.ts postoji.
PASS src/design/ald1n-tokens.generated.ts postoji.
PASS Generated design token fajlovi su sinhronizovani sa canonical JSON source-om.
PASS Tamagui onBrand koristi canonical onPrimary semantic token.
PASS package.json je validan JSON.
PASS eas.json je validan JSON.
PASS Expo SDK 57 verzija prati zvanični template.
PASS React Native verzija prati Expo SDK 57 template.
PASS Expo Router verzija je zaključana.
PASS Expo development client je uključen.
PASS SecureStore zavisnost postoji.
PASS TanStack Query zavisnost postoji.
PASS Minimalna Node.js verzija odgovara SDK 57 zahtevu.
PASS Aplikaciona package verzija je 0.7.0.
PASS package-lock release verzija je 0.7.0.
PASS expo-notifications prati SDK 57 preporučenu verziju.
PASS Expo Symbols je uključen za native Material/SF ikonice.
PASS Moderni Google Credential Manager bridge je uključen.
PASS Nitro Modules runtime je pinovan.
PASS Tamagui 2 runtime je pinovan.
PASS Tamagui Config v5 paket je pinovan.
PASS Tamagui Reanimated driver je pinovan.
PASS Expo System UI prati SDK 57 preporucenu verziju.
PASS Expo Status Bar prati SDK 57 preporucenu verziju.
PASS Expo FileSystem je direktno zakljucan za after-sales izbor priloga.
PASS Expo Sharing je zakljucan za bezbedno otvaranje privatnih after-sales priloga.
PASS Static colors consumeri su uklonjeni iz aplikacionog source-a.
PASS Legacy colors.* usage ne postoji van RN theme adaptera.
PASS Unsafe as never / as unknown as castovi ne postoje u source-u.
PASS 114 TypeScript/TSX fajlova prolazi sintaksnu proveru.
PASS app.config.ts prolazi TypeScript sintaksnu proveru.
PASS 758 lokalnih @/ importa je razrešeno.
PASS Bearer token header je implementiran.
PASS Globalni 401 logout je implementiran.
PASS Request ID je sačuvan u API grešci.
PASS API timeout je implementiran.
PASS Secure auth lifecycle je implementiran.
PASS Neuspešan bootstrap posle logina vraća aplikaciju u bezbedno anonymous stanje.
PASS API klijent koristi auth/token ugovor.
PASS API klijent koristi auth/google ugovor.
PASS API klijent koristi bootstrap ugovor.
PASS API klijent koristi catalog/filters ugovor.
PASS API klijent koristi products ugovor.
PASS API klijent koristi orders/options ugovor.
PASS API klijent koristi Idempotency-Key ugovor.
PASS API klijent koristi orders ugovor.
PASS API klijent koristi notifications ugovor.
PASS API klijent koristi devices ugovor.
PASS API klijent koristi me/notification-preferences ugovor.
PASS API klijent koristi PATCH ugovor.
PASS Order API client exposes Assigned-to-me list/detail contract.
PASS Assigned Orders client reuses the canonical Order contract for list/detail.
PASS Assigned Orders customer/mobile contract adds discovery only and no workflow mutation methods.
PASS Order post-create API types cover summary, payment ledger and proof upload.
PASS Order API client covers post-create summary, proof upload and secure binary path contracts.
PASS Order post-create Mobile types do not expose internal actor IDs or storage paths.
PASS Order customer API client does not expose admin payment or delivery workflow actions.
PASS Order private-file paths are prepared for the existing authenticated apiDownload transport.
PASS Orders ekran otvara Assigned-to-me inbox samo korisniku sa orders.manage dozvolom.
PASS Assigned Orders lista koristi dedicated API, permission gate, detail rutu i server pagination.
PASS Assigned Order detalj koristi dedicated detail API i prikazuje canonical Order customer/assignment podatke.
PASS Assigned Orders UI ostaje read-only i ne izlaže owner post-create ili admin workflow mutacije/interne storage podatke.
PASS Order detalj prikazuje server-driven payment/document/delivery post-create summary.
PASS Order detalj šalje payment proof samo kada server capability to dozvoli i koristi server file limite.
PASS Order payment-proof picker koristi postojeći Expo FileSystem i server MIME/extension/size limite.
PASS Order privatni fajlovi koriste Bearer binary transport i provereni privatni cache.
PASS Order PDF/proof helper validira PDF i otvara privatne fajlove kroz postojeći Expo Sharing flow.
PASS Order private-file helper prihvata samo tipizovane customer API path buildere.
PASS Order detalj ne otvara privatne URL-ove direktno već koristi secure Bearer/cache/share helper.
PASS Post-create UI čuva postojeći customer cancel i After-sales create tok.
PASS Order customer post-create UI/helper ne izlažu admin akcije, actor ID-jeve ili storage putanje.
PASS API klijent sadrži after-sales ugovor.
PASS After-sales lista koristi API, dozvolu i detalj rutu.
PASS After-sales detalj prikazuje slučaj, radnje i javnu komunikaciju.
PASS After-sales detalj podržava slanje javne poruke samo kada je komunikacija otvorena.
PASS After-sales create ekran koristi server options, create endpoint, create dozvolu i izabrane stavke.
PASS After-sales attachment picker koristi Expo FileSystem i server limite bez novog picker paketa.
PASS API klijent podržava autentifikovan binary download uz postojeći Bearer lifecycle.
PASS After-sales privatni prilog se preuzima samo kroz očekivanu API putanju i čuva u provereni privatni cache.
PASS After-sales privatni prilog koristi Expo Sharing tek nakon provere platforme i dostupnosti sistema.
PASS After-sales detalj otvara privatne priloge kroz bezbedan Bearer download umesto direktnog privatnog URL-a.
PASS After-sales work-order tip izlaže javne field-work priloge.
PASS Secure attachment helper dozvoljava samo očekivanu field-work Bearer putanju i odvaja cache namespace.
PASS After-sales detalj prikazuje javnu terensku dokumentaciju i otvara je kroz postojeći secure flow.
PASS After-sales create ekran bira, prikazuje i šalje priloge prema server limitima.
PASS After-sales detail tip izlaže server-driven limite.
PASS After-sales message composer bira, prikazuje i šalje priloge prema server limitima.
PASS Order detalj otvara create-from-order ekran samo korisniku sa after_sales.create dozvolom.
PASS Orders ekran otvara after-sales listu samo korisniku sa view_own dozvolom.
PASS Warranty API tipovi pokrivaju listu, detalj i maintenance timeline.
PASS API klijent sadrži Warranty list/detail ugovor.
PASS Warranty lista koristi API, permission gate, detail rutu i maintenance summary.
PASS Warranty detalj prikazuje customer-safe garantni list, uslove, serijske brojeve, status i maintenance timeline.
PASS Warranty PDF se preuzima Bearer transportom, validira kao PDF i čuva u provereni privatni cache.
PASS Warranty PDF koristi postojeći Expo Sharing tek nakon platform/device provere.
PASS Warranty detalj otvara privatni PDF kroz bezbedan Bearer/cache/share flow bez direktnog URL-a.
PASS Orders ekran otvara Warranty listu samo korisniku sa warranties.view_own dozvolom.
PASS Commission API tipovi pokrivaju customer list/detail, statuse, summary i pagination ugovor.
PASS API klijent sadrži Commission list/filter/detail ugovor.
PASS Commission Mobile contract ne izlaže admin actor/history/payment-batch interne identifikatore.
PASS Commission lista koristi customer permission, q/status/date filtere, server summary, pagination i detail rutu.
PASS Commission detalj prikazuje customer-safe obračun, status, napomenu, isplatu i link ka porudžbini.
PASS Orders ekran otvara Commission listu samo korisniku sa commissions.view_own dozvolom.
PASS Commission customer UI ne izlaže admin/interne workflow identifikatore ili akcije.
PASS Lokalna korpa koristi samo proizvod i količinu; variant identitet je dekomisioniran.
PASS Korpa se čisti pri odjavi/promeni korisnika.
PASS Mobile API tipovi više ne izlažu Product Variants.
PASS Mobile Product detalj više nema variant izbor.
PASS Mobile checkout šalje samo product_id i quantity.
PASS Admin After-sales Mobile contract više ne izlaže product_variant_id.
PASS Checkout čuva stabilan idempotency ključ za retry istog payload-a.
PASS Checkout podržava uslovni izbor računa za bank transfer.
PASS Device heartbeat više ne gasi push registraciju pri svakom startu.
PASS Android kanal se kreira pre Expo push tokena.
PASS Expo push token koristi EAS projectId.
PASS Push token se registruje kao Expo device token.
PASS Push token se ne loguje u klijentu.
PASS Foreground i tap push listeneri su implementirani.
PASS Cold-start notification response se čisti nakon obrade.
PASS Push order deep link vodi na detalj porudžbine.
PASS Notification settings uređuju push i poslovne kategorije.
PASS Notification settings podržavaju per-device push uključivanje i isključivanje.
PASS Account ekran podrzava izmenu profila i lokalno osvezavanje bootstrap korisnika.
PASS Account ekran podrzava promenu lozinke i obaveznu ponovnu prijavu.
PASS Account ekran zahteva najmanje 12 znakova za novu lozinku.
PASS Account ekran proverava potvrdu nove lozinke.
PASS API klijent koristi PATCH /me za profil.
PASS API klijent koristi PUT /me/password za lozinku.
PASS Google Sign-In koristi web client ID iz google-services.json i vraća ID token backendu.
PASS Google login ima saved-account, registration/account-picker i explicit fallback tok.
PASS Google Sign-In dugme prati aktivnu light/dark temu.
PASS Bottom navigation ima Material 3 tonalni aktivni indikator.
PASS Tab badge koristi semantic danger/onDanger foreground par.
PASS UI koristi native Expo Symbols umesto tekstualnih pseudo-ikonica.
PASS Canonical packages/api-contract/openapi.yaml postoji.
PASS Mobile OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.
PASS CMS OpenAPI kopija postoji.
PASS CMS OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.
PASS OpenAPI documents Assigned-to-me list/detail routes.
PASS Assigned Orders OpenAPI documents permission denial and strict detail not-found behavior.
PASS Assigned Orders OpenAPI contains no workflow mutation operations.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/post-create:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/payments/proof:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/payments/{payment}/proof:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/documents/confirmation.pdf:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/documents/{document}.pdf:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/delivery-proof:.
PASS OpenAPI contains OrderPrivateFile: schema.
PASS OpenAPI contains OrderPaymentLedgerEntry: schema.
PASS OpenAPI contains OrderDocumentSummary: schema.
PASS OpenAPI contains OrderDeliverySummary: schema.
PASS OpenAPI contains OrderBankTransferSnapshot: schema.
PASS OpenAPI contains OrderPostCreateCapabilities: schema.
PASS OpenAPI contains OrderPaymentProofLimits: schema.
PASS OpenAPI contains OrderPostCreate: schema.
PASS Order post-create OpenAPI covers proof upload, binary downloads and private no-store cache policy.
PASS Order post-create OpenAPI does not expose internal actor/storage fields or admin workflow actions.
PASS OpenAPI dokumentuje Commission list/filter/detail, summary i pagination ugovor.
PASS Commission OpenAPI customer ugovor ne izlaže admin/interne identifikatore.
PASS OpenAPI dokumentuje Warranty list/detail i maintenance schema ugovor.
PASS OpenAPI dokumentuje privatni Warranty PDF Bearer download ugovor.
PASS OpenAPI AfterSalesCase detalj izlaže server-driven limite za poruke i priloge.
PASS OpenAPI work-order schema izlaže javne field-work priloge.
PASS OpenAPI field-work attachment ruta dokumentuje Bearer download ugovor.
PASS OpenAPI kopija sadrži /auth/token.
PASS OpenAPI kopija sadrži /auth/google.
PASS OpenAPI kopija sadrži /bootstrap.
PASS OpenAPI kopija sadrži /catalog/filters.
PASS OpenAPI kopija sadrži /products.
PASS OpenAPI kopija sadrži /orders/options.
PASS OpenAPI kopija sadrži Idempotency-Key.
PASS OpenAPI kopija sadrži /orders.
PASS OpenAPI kopija sadrži /notifications.
PASS OpenAPI kopija sadrži /devices.
PASS Deep-link scheme je postavljen.
PASS Android/iOS identifikatori su postavljeni.
PASS Expo Router typed routes su uključene.
PASS Dinamički EAS project ID je podržan.
PASS Expo app verzija je 0.7.0.
PASS Expo display naziv je Ald1n CMS bez Preview suffixa.
PASS Ald1n V2 logo je canonical icon/adaptive/splash/favicon asset.
PASS App runtime version fallback je 0.7.0.
PASS Account version fallback je 0.7.0.
PASS Android config podržava Firebase google-services.json kada postoji.
PASS App config uključuje Google Sign-In plugin kada je Firebase config prisutan.
PASS Expo userInterfaceStyle prati sistemsku light/dark temu.
PASS App theme mode je zakljucan na system.
PASS App theme resolver koristi React Native system color scheme.
PASS RN theme adapter koristi canonical onDanger semantic token.
PASS TamaguiProvider je povezan na root aplikacije.
PASS Root Tamagui, StatusBar i navigation background prate isti resolved scheme.
PASS Tamagui Config v5 i Reanimated driver su aktivni.
PASS Ald1n Light/Dark Tamagui palette su povezane.
PASS Tamagui onDanger koristi canonical onDanger semantic token.
PASS Product detail omogućava kopiranje ručno unetog opisa na Android/iOS.
PASS Mobile ima SDK 57 expo-clipboard zavisnost za kopiranje opisa.
PASS Admin Product Create ekran koristi catalog.manage_products i canonical admin catalog API.
PASS Admin Product Create prikazuje server validation grešku i posle uspeha otvara novi artikal.
PASS SelectSheet primitive postoji bez dodatnog native dependency-ja.
PASS API klijent sadrži Admin Catalog options/create ugovor.
PASS Mobile tipovi pokrivaju Admin Product Create metadata/input/response.
PASS Home prikazuje Dodaj artikal samo korisniku sa catalog.manage_products dozvolom.
PASS OpenAPI dokumentuje Admin Catalog options i product create rute.
PASS Admin Product Create renderuje dinamičke specifikacije, zavisne select opcije i detaljna polja.
PASS Admin Product Create fotografije su permission-gated i šalju se kroz canonical image API.
PASS Product image picker koristi postojeći Expo FileSystem i server-driven limite bez novog native dependency-ja.
PASS API klijent podržava multipart upload slika posle kreiranja artikla.
PASS Mobile tipovi pokrivaju dinamičke specifikacije, image limite i storage contract za sledeći specijalizovani korak.
PASS OpenAPI dokumentuje napredne spec metadata podatke i multipart product-image upload.
PASS Admin Product Create ima specijalizovani multi-disk repeater i skriva izvedeni total iz standardnih polja.
PASS Storage repeater šalje canonical specs/spec_lists/spec_capacities/spec_structured payload bez ručnog derived total-a.
PASS Mobile tipovi izlažu server-driven storage repeater i read-only derived metadata.
PASS OpenAPI dokumentuje server-driven storage repeater metadata i derived total polje.
PASS P2 Admin hub koristi centralni access helper, API i query-key foundation.
PASS P2 Admin access helper centralizuje administratorske dozvole i admin/superadmin role fallback.
PASS P2 Admin API helper koristi canonical /api/v1/admin foundation endpoint.
PASS P2 Admin query-key family je centralizovana.
PASS Home prikazuje centralni Admin entry kroz isti access helper.
PASS OpenAPI dokumentuje P2 Admin foundation endpoint i schema ugovor.
PASS P2 FilterBar ima chips, active count i clear contract.
PASS P2 DateTimeField je dependency-free kontrolisani date/datetime input.
PASS P2 MoneyField centralizuje decimalni unos i currency prikaz.
PASS P2 AsyncLookup je server-query friendly lookup bez duplog cache-a.
PASS P2 DataList je mobile-first virtualizovana lista sa refresh i empty state contractom.
PASS P2 ActionSheet koristi dependency-free Modal i aktuelni RN absoluteFill API.
PASS P2 ConfirmAction reuse-uje ActionSheet i odvaja confirm/cancel tok.
PASS P2 StatusTimeline ima reusable server-driven timeline contract.
PASS P2 postojeći SelectSheet i AppFeedback ostaju očuvani.
PASS P3 Admin Commissions API klijent pokriva list/detail/status/bulk-pay ugovor.
PASS P3 Admin Commissions CSV/PDF koristi relativnu API putanju i postojeći Bearer binary/cache/share flow.
PASS P3 Admin Commissions lista ima permission gate, filtere, bulk-pay i izvoze.
PASS P3 Admin Commissions detalj koristi server-driven prelaze i shared timeline.
PASS P3 Admin hub izlaže Provizije samo commissions.manage korisniku.
PASS P3 Admin Commissions query keys su centralizovani.
PASS OpenAPI dokumentuje kompletan P3 Admin Commissions route surface.
PASS OpenAPI dokumentuje P3 Admin Commissions schema ugovor.
PASS P3 Admin Warranties API klijent pokriva list/detail/update/void/maintenance ugovor.
PASS P3 Admin Warranties lista ima permission gate, filtere, statistiku i detail rutu.
PASS P3 Admin Warranties detalj koristi server-side warranty i maintenance mutacije.
PASS P3 Admin hub izlaže Garancije samo warranties.manage korisniku.
PASS P3 Admin Warranties query keys su centralizovani.
PASS OpenAPI dokumentuje P3 Admin Warranties core route i schema ugovor.
PASS P3 Admin Warranties 2E zaključava rules/backfill i relativni Admin PDF API ugovor.
PASS P3 Admin Warranties 2E zaključava Rules UI i Backfill tok.
PASS P3 Admin Warranties 2E zaključava Rules navigaciju i Admin PDF UI entry.
PASS P3 Admin Warranties 2E zaključava secure relativni Admin PDF Bearer/cache/share flow.
PASS OpenAPI dokumentuje kompletan P3 Admin Warranties Rules/Backfill/Admin PDF ugovor.
PASS P3 Admin Reports 2G zaključava read/schedule Mobile API ugovor i relativne Admin putanje.
PASS P3 Admin Reports 2G zaključava secure CSV/PDF Bearer/cache/share export tok.
PASS P3 Admin Reports 2G zaključava management dashboard, permission gate i schedule manager UI.
PASS P3 Admin Reports 2G zaključava centralizovane Reports query-key ugovore.
PASS OpenAPI dokumentuje kompletan P3 Admin Reports read/export/schedule ugovor od 10 operacija.
PASS P3 Admin System Health 2C zaključava read-only Mobile API ugovor i relativnu admin/system-health putanju.
PASS P3 Admin System Health 2C zaključava centralizovani System Health query key.
PASS P3 Admin System Health 2C zaključava permission-gated read-only UI, refresh, checks, metrics i history tok.
PASS P3 Admin System Health 2C zaključava Admin hub ulaz samo za system.health.
PASS OpenAPI dokumentuje samo read-only P3 Admin System Health GET ugovor bez snapshot/backup/prune mutacija.
PASS P3 Admin Audit 2C zakljucava relativni read-only Mobile API ugovor bez raw user_agent/context_json polja.
PASS P3 Admin Audit 2C zakljucava centralizovane Audit list/detail query key ugovore.
PASS P3 Admin Audit 2C zakljucava security.view list/filter/pagination/refetch read-only UI.
PASS P3 Admin Audit 2C zakljucava permission-gated safe detail UI i server-driven read-only capabilities.
PASS P3 Admin Audit 2C zakljucava Admin hub ulaz samo za security.view.
PASS OpenAPI dokumentuje samo P3 Admin Audit read/filter list i safe detail ugovor bez export/mutation ruta.
PASS Product image upload koristi eksplicitni Expo fetch transport sa postojecim auth/error lifecycle-om.
PASS Product image multipart koristi pravi Expo File umesto legacy uri/name/type pseudo-fajla.
PASS Product image multipart ne postavlja rucno Content-Type boundary.
PASS Product image picker prihvata Android image provider fajl bez ekstenzije kada je MIME dozvoljen, uz zadrzan MIME/extension guard za ostale fajlove.
PASS iOS Google Sign-In koristi canonical GoogleService-Info.plist kroz Expo i Nitro config plugin.
PASS iOS GoogleService-Info.plist sadrži preview bundle, iOS OAuth, reversed scheme i web client ID za autoDetect.
PASS iOS koristi zaseban 1024x1024 opaque RGB app icon bez alpha/tRNS transparentnosti.
PASS v0.7 Home izlaže release-critical Provizije odmah kroz manage/view-own permission model.
PASS v0.7 Home prioritet zadržava Dodaj artikal pre Provizija.
PASS v0.7 Admin Hub drži Provizije kao drugu prioritetnu akciju odmah posle Dodaj artikal.
PASS v0.7 korisničke Moje provizije ostaju dostupne kroz view-own list/detail tok.
PASS v0.7 Admin Provizije zadržavaju list/detail/bulk-pay/export/status workflow.
PASS v0.7 Provizije koriste postojeći Admin API i secure export bez paralelne logike.
PASS v0.7 release-critical Provizije ostaju vezane za kompletan canonical Admin OpenAPI surface.
PASS v0.7 Commission contract uklanja fiksni minimum 20 EUR i dokumentuje podrazumevanih 10 procenata u Product Create toku.

Ukupno FAIL: 0
MOBILE_PROJECT_VALIDATOR=PASS
DESIGN_TOKEN_CHECK=SKIPPED_SCRIPT_NOT_FOUND
PASS  postoji artisan
PASS  postoji composer.json
PASS  postoji composer.lock
PASS  postoji .env.example
PASS  postoji VERSION
PASS  postoji RELEASE-TAG
PASS  postoji UPGRADE-FROM
PASS  postoji docs/UPGRADE-V2.1-BETA1.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.3.md
PASS  postoji docs/UPGRADE-V2.1-BETA2.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA4.md
PASS  postoji docs/UPGRADE-V2.1-BETA5.md
PASS  postoji docs/UPGRADE-V2.1-BETA6.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.3.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.4.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.5.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.6.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.7.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.8.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.9.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.10.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.11.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.12.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.13.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.14.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.14.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.15.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.16.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.20.md
PASS  postoji DATABASE-MIGRATION-REQUIRED.txt
PASS  postoji app/Models/Order.php
PASS  postoji app/Models/OrderItem.php
PASS  postoji app/Models/OrderDocument.php
PASS  postoji app/Models/OrderCommission.php
PASS  postoji app/Models/CommissionStatusHistory.php
PASS  postoji app/Models/CommissionPaymentBatch.php
PASS  postoji app/Models/OrderInternalNote.php
PASS  postoji app/Models/OrderAssignment.php
PASS  postoji app/Models/OrderStatusHistory.php
PASS  postoji app/Models/StockMovement.php
PASS  postoji app/Models/IdempotencyKey.php
PASS  postoji app/Models/OrderPayment.php
PASS  postoji app/Models/OrderDelivery.php
PASS  postoji app/Models/AfterSalesCase.php
PASS  postoji app/Models/AfterSalesCaseItem.php
PASS  postoji app/Models/AfterSalesMessage.php
PASS  postoji app/Models/AfterSalesAttachment.php
PASS  postoji app/Models/AfterSalesStatusHistory.php
PASS  postoji app/Models/AfterSalesAction.php
PASS  postoji app/Models/AfterSalesActionItem.php
PASS  postoji app/Models/FieldServiceTeam.php
PASS  postoji app/Models/FieldWorkOrder.php
PASS  postoji app/Models/FieldWorkOrderAttachment.php
PASS  postoji app/Models/ServicePartSupplier.php
PASS  postoji app/Models/ServicePart.php
PASS  postoji app/Models/FieldWorkOrderPart.php
PASS  postoji app/Models/ServicePartMovement.php
PASS  postoji app/Models/ServicePartPurchaseRequest.php
PASS  postoji app/Models/ServicePartPurchaseRequestItem.php
PASS  postoji app/Models/WarrantyRule.php
PASS  postoji app/Models/ProductWarranty.php
PASS  postoji app/Models/WarrantyMaintenanceRecord.php
PASS  postoji app/Models/OrderEmailOutbox.php
PASS  postoji app/Models/StockReceipt.php
PASS  postoji app/Models/StockReceiptItem.php
PASS  postoji app/Models/InventoryCount.php
PASS  postoji app/Models/InventoryCountItem.php
PASS  postoji app/Models/AutomationRun.php
PASS  postoji app/Models/OperationalAlert.php
PASS  postoji app/Models/NotificationPreference.php
PASS  postoji app/Models/BackupRun.php
PASS  postoji app/Models/SystemHealthSnapshot.php
PASS  postoji app/Models/SystemRuntimeState.php
PASS  postoji app/Models/SecurityEvent.php
PASS  postoji app/Services/OrderService.php
PASS  postoji app/Services/OrderWorkflowService.php
PASS  postoji app/Services/InventoryService.php
PASS  postoji app/Services/IdempotencyService.php
PASS  postoji app/Services/OrderPaymentService.php
PASS  postoji app/Services/IpsPaymentPayloadService.php
PASS  postoji app/Services/AdvancedInventoryService.php
PASS  postoji app/Services/LegacyReadOnlyGuard.php
PASS  postoji app/Services/OrderAccessService.php
PASS  postoji app/Services/OrderReportService.php
PASS  postoji app/Services/CommissionReportService.php
PASS  postoji app/Services/CommissionWorkflowService.php
PASS  postoji app/Services/OrderOperationalService.php
PASS  postoji app/Services/OrderTimelineService.php
PASS  postoji app/Services/OperationalNotificationService.php
PASS  postoji app/Notifications/OperationalNotification.php
PASS  postoji app/Services/OperationalAutomationService.php
PASS  postoji app/Services/AutomationReadinessService.php
PASS  postoji app/Services/BackupService.php
PASS  postoji app/Services/SystemHealthService.php
PASS  postoji app/Services/SecurityEventLogger.php
PASS  postoji app/Services/SensitiveDataSanitizer.php
PASS  postoji app/Services/OrderIndexService.php
PASS  postoji app/Services/OrderDetailService.php
PASS  postoji app/Services/OrderDetailPresenter.php
PASS  postoji app/Support/ViewValue.php
PASS  postoji app/Services/OrderDocumentService.php
PASS  postoji app/Services/AfterSalesAccessService.php
PASS  postoji app/Services/AfterSalesCaseService.php
PASS  postoji app/Services/AfterSalesActionService.php
PASS  postoji app/Services/FieldWorkOrderPlanner.php
PASS  postoji app/Services/FieldOperationsService.php
PASS  postoji app/Services/ServicePartsInventoryService.php
PASS  postoji app/Services/WarrantyService.php
PASS  postoji app/Services/OrderEmailOutboxService.php
PASS  postoji app/Services/OrderEmailDispatcher.php
PASS  postoji app/Services/NbsIpsQrService.php
PASS  postoji app/Services/DocumentNumberService.php
PASS  postoji app/Services/Pdf/SimplePdfWriter.php
PASS  postoji app/Services/Pdf/BusinessDocumentPdfService.php
PASS  postoji app/Services/Pdf/WarrantyCertificatePdfService.php
PASS  postoji app/Http/Requests/StoreOrderRequest.php
PASS  postoji app/Http/Requests/AdjustStockRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesCaseRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesMessageRequest.php
PASS  postoji app/Http/Requests/UpdateAfterSalesCaseRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/CompleteAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/CancelAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/StoreFieldServiceTeamRequest.php
PASS  postoji app/Http/Requests/UpdateFieldServiceTeamRequest.php
PASS  postoji app/Http/Requests/ScheduleFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/CompleteFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/CancelFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/StoreServicePartRequest.php
PASS  postoji app/Http/Requests/UpdateServicePartRequest.php
PASS  postoji app/Http/Requests/AdjustServicePartStockRequest.php
PASS  postoji app/Http/Requests/StoreServicePartSupplierRequest.php
PASS  postoji app/Http/Requests/UpdateServicePartSupplierRequest.php
PASS  postoji app/Http/Requests/StoreFieldWorkOrderPartRequest.php
PASS  postoji app/Http/Requests/StoreServicePartPurchaseRequest.php
PASS  postoji app/Http/Requests/CancelServicePartPurchaseRequest.php
PASS  postoji app/Http/Requests/StoreWarrantyRuleRequest.php
PASS  postoji app/Http/Requests/UpdateProductWarrantyRequest.php
PASS  postoji app/Http/Requests/ScheduleWarrantyMaintenanceRequest.php
PASS  postoji app/Http/Requests/CompleteWarrantyMaintenanceRequest.php
PASS  postoji app/Http/Controllers/OrderController.php
PASS  postoji app/Http/Controllers/WarrantyController.php
PASS  postoji app/Http/Controllers/AfterSalesController.php
PASS  postoji app/Http/Controllers/AfterSalesAttachmentController.php
PASS  postoji app/Http/Controllers/FieldWorkOrderAttachmentController.php
PASS  postoji app/Http/Controllers/CommissionController.php
PASS  postoji app/Http/Controllers/NotificationController.php
PASS  postoji app/Http/Controllers/Api/V1/OrderController.php
PASS  postoji app/Http/Controllers/Admin/OrderController.php
PASS  postoji app/Http/Controllers/Admin/AfterSalesController.php
PASS  postoji app/Http/Controllers/Admin/AfterSalesActionController.php
PASS  postoji app/Http/Controllers/Admin/FieldOperationsController.php
PASS  postoji app/Http/Controllers/Admin/FieldServiceTeamController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartSupplierController.php
PASS  postoji app/Http/Controllers/Admin/FieldWorkOrderPartController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartPurchaseRequestController.php
PASS  postoji app/Http/Controllers/Admin/WarrantyController.php
PASS  postoji app/Http/Controllers/Admin/CommissionController.php
PASS  postoji app/Http/Controllers/Admin/StockAdjustmentController.php
PASS  postoji app/Http/Controllers/OrderDocumentController.php
PASS  postoji app/Http/Controllers/Admin/OrderDocumentController.php
PASS  postoji app/Http/Controllers/Admin/ReportController.php
PASS  postoji app/Http/Controllers/Admin/DocumentSettingsController.php
PASS  postoji app/Http/Controllers/OrderPaymentController.php
PASS  postoji app/Http/Controllers/OrderDeliveryController.php
PASS  postoji app/Http/Controllers/Admin/PaymentController.php
PASS  postoji app/Http/Controllers/Admin/InventoryController.php
PASS  postoji app/Http/Controllers/Admin/AutomationController.php
PASS  postoji app/Http/Controllers/Admin/SystemHealthController.php
PASS  postoji app/Http/Controllers/Admin/TurnstileSettingsController.php
PASS  postoji app/Http/Controllers/Admin/OrderEmailSettingsController.php
PASS  postoji app/Http/Resources/OrderResource.php
PASS  postoji app/Console/Commands/OrdersDoctorCommand.php
PASS  postoji app/Console/Commands/OrderCreateDoctorCommand.php
PASS  postoji app/Console/Commands/CatalogOwnershipDoctorCommand.php
PASS  postoji app/Console/Commands/DetailPagesDoctorCommand.php
PASS  postoji app/Console/Commands/ReportsDoctorCommand.php
PASS  postoji app/Console/Commands/OperationsDoctorCommand.php
PASS  postoji app/Console/Commands/PaymentsInventoryDoctorCommand.php
PASS  postoji app/Console/Commands/RunOperationalAutomationCommand.php
PASS  postoji app/Console/Commands/AutomationDoctorCommand.php
PASS  postoji app/Console/Commands/CreateBackupCommand.php
PASS  postoji app/Console/Commands/BackupDoctorCommand.php
PASS  postoji app/Console/Commands/SystemHealthCommand.php
PASS  postoji app/Console/Commands/SchedulerHeartbeatCommand.php
PASS  postoji app/Console/Commands/TestDatabaseDoctorCommand.php
PASS  postoji app/Console/Commands/AfterSalesDoctorCommand.php
PASS  postoji app/Console/Commands/FieldOperationsDoctorCommand.php
PASS  postoji app/Console/Commands/ServicePartsDoctorCommand.php
PASS  postoji app/Console/Commands/WarrantiesDoctorCommand.php
PASS  postoji app/Console/Commands/WarrantiesBackfillCommand.php
PASS  postoji app/Console/Commands/OrderEmailDispatchCommand.php
PASS  postoji app/Console/Commands/OrderEmailsDoctorCommand.php
PASS  postoji database/migrations/2026_07_22_000006_enable_production_orders_inventory.php
PASS  postoji database/migrations/2026_07_23_000007_repair_production_schema_beta5.php
PASS  postoji database/migrations/2026_07_23_000008_repair_authenticated_runtime_beta6.php
PASS  postoji database/migrations/2026_07_23_000009_create_reports_documents_and_supplier_assignment.php
PASS  postoji database/migrations/2026_07_23_000010_repair_reports_schema_beta1_2.php
PASS  postoji database/migrations/2026_07_23_000011_create_operational_orders_commissions_beta2.php
PASS  postoji database/migrations/2026_07_23_000012_create_payments_advanced_inventory_beta3.php
PASS  postoji database/migrations/2026_07_23_000013_create_automation_alerts_beta4.php
PASS  postoji database/migrations/2026_07_23_000014_create_security_backup_health_beta6.php
PASS  postoji database/migrations/2026_07_29_000015_repair_order_documents_and_payments_beta7_5.php
PASS  postoji database/migrations/2026_07_30_000016_add_order_completion_beta7_7.php
PASS  postoji database/migrations/2026_07_30_000017_add_delivery_workflow_beta7_8.php
PASS  postoji database/migrations/2026_07_30_000018_fix_delivery_note_document_type_beta7_9.php
PASS  postoji database/migrations/2026_07_30_000019_create_after_sales_cases_beta7_10.php
PASS  postoji database/migrations/2026_07_30_000020_create_after_sales_actions_beta7_11.php
PASS  postoji database/migrations/2026_07_30_000021_create_field_operations_beta7_12.php
PASS  postoji database/migrations/2026_07_30_000022_create_service_parts_procurement_beta7_13.php
PASS  postoji database/migrations/2026_07_30_000023_enable_document_revisions_beta7_14.php
PASS  postoji database/migrations/2026_07_30_000024_create_warranties_preventive_maintenance_beta7_15.php
PASS  postoji database/migrations/2026_07_30_000025_create_order_email_outbox_beta7_16.php
PASS  postoji resources/views/orders/index.blade.php
PASS  postoji resources/views/admin/orders/show.blade.php
PASS  postoji resources/views/commissions/index.blade.php
PASS  postoji resources/views/notifications/index.blade.php
PASS  postoji resources/views/admin/commissions/index.blade.php
PASS  postoji resources/views/orders/create.blade.php
PASS  postoji resources/views/orders/show.blade.php
PASS  postoji resources/views/admin/reports/index.blade.php
PASS  postoji resources/views/admin/settings/documents.blade.php
PASS  postoji resources/views/admin/inventory/index.blade.php
PASS  postoji resources/views/admin/orders/partials/payments.blade.php
PASS  postoji resources/views/orders/partials/payments.blade.php
PASS  postoji resources/views/after-sales/index.blade.php
PASS  postoji resources/views/after-sales/create.blade.php
PASS  postoji resources/views/after-sales/show.blade.php
PASS  postoji resources/views/admin/after-sales/index.blade.php
PASS  postoji resources/views/admin/after-sales/show.blade.php
PASS  postoji resources/views/admin/field-operations/index.blade.php
PASS  postoji resources/views/admin/field-operations/show.blade.php
PASS  postoji resources/views/admin/field-operations/teams.blade.php
PASS  postoji resources/views/admin/service-parts/index.blade.php
PASS  postoji resources/views/admin/service-parts/suppliers.blade.php
PASS  postoji resources/views/admin/service-parts/purchase-requests.blade.php
PASS  postoji resources/views/admin/service-parts/purchase-show.blade.php
PASS  postoji resources/views/admin/settings/automation.blade.php
PASS  postoji resources/views/admin/settings/system-health.blade.php
PASS  postoji resources/views/admin/settings/turnstile.blade.php
PASS  postoji resources/views/admin/settings/order-emails.blade.php
PASS  postoji resources/views/emails/order-events.blade.php
PASS  postoji tests/Feature/AdminOrdersImageRotationTest.php
PASS  postoji tests/Feature/OperationalOrdersCommissionsTest.php
PASS  postoji tests/Feature/OperationalAutomationTest.php
PASS  postoji tests/Feature/PaymentsAdvancedInventoryTest.php
PASS  postoji tests/Feature/OrderDeliveryWorkflowTest.php
PASS  postoji tests/Feature/AfterSalesWorkflowTest.php
PASS  postoji tests/Feature/AfterSalesActionExecutionTest.php
PASS  postoji tests/Feature/FieldOperationsWorkflowTest.php
PASS  postoji tests/Feature/ServicePartsWorkflowTest.php
PASS  postoji tests/Feature/InventoryWorkspaceUiTest.php
PASS  postoji tests/Feature/SecurityHealthBackupTest.php
PASS  postoji tests/Feature/MySqlTestDatabaseSafetyTest.php
PASS  postoji tests/Unit/SensitiveDataSanitizerTest.php
PASS  postoji tests/Feature/ProductionOrderInventoryTest.php
PASS  postoji tests/Feature/InventoryAdjustmentTest.php
PASS  postoji tests/Feature/ProductionPermissionsTest.php
PASS  postoji tests/Feature/DashboardLegacyDesignTest.php
PASS  postoji tests/Feature/ReportsDocumentsSupplierTest.php
PASS  postoji tests/Fixtures/pdf-logo.jpg
PASS  postoji tests/Unit/BusinessDocumentPdfServiceTest.php
PASS  postoji tests/Unit/DeliveryNoteMigrationContractTest.php
PASS  postoji tests/Unit/DocumentRevisionMigrationContractTest.php
PASS  postoji tests/Unit/CommissionReportPdfServiceTest.php
PASS  postoji tests/Feature/OrderEmailsIpsWarrantyTest.php
PASS  postoji tests/Unit/OrderEmailIpsMigrationContractTest.php
PASS  postoji tests/Unit/ReceivablesPermissionMigrationContractTest.php
PASS  postoji tests/Unit/LegacyReadOnlyGuardTest.php
PASS  postoji tests/Unit/OrderDetailPresenterTest.php
PASS  postoji tests/Unit/ViewValueTest.php
PASS  postoji tests/Feature/CatalogDetailPageTest.php
PASS  postoji tests/Feature/LoginDashboardFallbackTest.php
PASS  postoji resources/views/components/icon.blade.php
PASS  postoji app/Http/Middleware/EnsureRuntimeDirectories.php
PASS  postoji app/Http/Middleware/AttachRequestId.php
PASS  postoji app/Http/Middleware/SecurityHeaders.php
PASS  postoji app/Console/Commands/AuthDoctorCommand.php
PASS  postoji .env.testing.mysql.example
PASS  postoji phpunit.mysql.xml
PASS  postoji bin/php-lint.php
PASS  postoji bin/autoload-check.php
PASS  postoji bin/pdf-smoke.php
PASS  postoji bin/delivery-note-smoke.php
PASS  postoji bin/warranty-pdf-smoke.php
PASS  postoji bin/ips-qr-pdf-smoke.php
PASS  postoji storage/framework/cache/data/.gitignore
PASS  postoji storage/framework/sessions/.gitignore
PASS  postoji storage/framework/views/.gitignore
PASS  postoji storage/logs/.gitignore
PASS  postoji storage/app/backups/.gitignore
PASS  postoji config/backup.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.md
PASS  postoji docs/RELEASE-CHECK.md
PASS  postoji app/Console/Commands/ReleaseCheckCommand.php
PASS  postoji config/release.php
PASS  postoji bin/release-check-smoke.php
PASS  postoji tests/Unit/ReleaseCheckContractTest.php
PASS  postoji tests/Feature/ReleaseCheckCommandTest.php
PASS  postoji storage/app/release-check/.gitignore
PASS  postoji docs/UPGRADE-V2.1-BETA7.22.1.md
PASS  postoji bin/theme-css-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.21.md
PASS  postoji database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php
PASS  postoji app/Models/ReportSchedule.php
PASS  postoji app/Models/ReportDelivery.php
PASS  postoji app/Services/ManagementReportService.php
PASS  postoji app/Services/ReportScheduleService.php
PASS  postoji app/Services/Pdf/ManagementReportPdfService.php
PASS  postoji app/Http/Controllers/Admin/ManagementReportController.php
PASS  postoji app/Http/Controllers/Admin/ReportScheduleController.php
PASS  postoji app/Console/Commands/ManagementReportsDoctorCommand.php
PASS  postoji app/Console/Commands/OrderCostSnapshotsCommand.php
PASS  postoji app/Services/OrderItemCostSnapshotService.php
PASS  postoji app/Console/Commands/ManagementReportsDispatchCommand.php
PASS  postoji resources/views/admin/reports/management.blade.php
PASS  postoji resources/views/emails/management-report.blade.php
PASS  postoji tests/Feature/ManagementReportsProfitabilityTest.php
PASS  postoji tests/Unit/OrderCostSnapshotRepairContractTest.php
PASS  postoji bin/management-report-smoke.php
PASS  postoji bin/order-cost-snapshot-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji database/migrations/2026_07_31_000028_create_smart_product_management_beta7_19.php
PASS  postoji app/Services/ProductTemplateService.php
PASS  postoji app/Services/ProductCompletenessService.php
PASS  postoji app/Services/ProductBulkService.php
PASS  postoji app/Http/Controllers/Admin/ProductBulkController.php
PASS  postoji app/Console/Commands/SmartProductsDoctorCommand.php
PASS  postoji resources/views/admin/products/clone.blade.php
PASS  postoji resources/views/admin/products/bulk.blade.php
PASS  postoji tests/Unit/SmartProductManagementMigrationContractTest.php
PASS  postoji tests/Unit/SmartProductManagementUiContractTest.php
PASS  postoji tests/Feature/SmartProductManagementTest.php
PASS  postoji bin/smart-product-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.24.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.24.1.md
PASS  postoji docs/UPGRADE-V2.1-RC1.md
PASS  postoji docs/RC-OPERATIONS.md
PASS  postoji docs/UPGRADE-V2.1-STABLE.md
PASS  postoji docs/UPGRADE-V2.1.1.md
PASS  postoji docs/UPGRADE-V2.1.2.md
PASS  postoji docs/UPGRADE-V2.1.3.md
PASS  postoji docs/UPGRADE-V2.1.3.1.md
PASS  postoji docs/UPGRADE-V2.1.3.2.md
PASS  postoji docs/UPGRADE-V2.1.3.3.md
PASS  postoji docs/STABLE-OPERATIONS.md
PASS  postoji docs/BACKUP-RESTORE-DRILL.md
PASS  postoji bin/rc-hardening-smoke.php
PASS  postoji bin/stable-hardening-smoke.php
PASS  postoji bin/stable-maintenance-smoke.php
PASS  postoji bin/product-media-ux-smoke.php
PASS  postoji bin/product-announcement-smoke.php
PASS  postoji bin/catalog-settings-product-data-smoke.php
PASS  postoji bin/catalog-settings-integrity-hotfix-smoke.php
PASS  postoji bin/product-save-regex-hotfix-smoke.php
PASS  postoji bin/storage-capacity-total-smoke.php
PASS  postoji tests/Unit/ProductSaveRegexHotfixContractTest.php
PASS  postoji tests/Unit/StorageCapacityTotalContractTest.php
PASS  postoji tests/Unit/CatalogSettingsProductDataContractTest.php
PASS  postoji tests/Unit/CatalogSettingsIntegrityHotfixContractTest.php
PASS  postoji app/Console/Commands/CatalogSettingsDoctorCommand.php
PASS  postoji app/Services/ProductTypeCategoryService.php
PASS  postoji app/Services/SpecificationFieldLifecycleService.php
PASS  postoji app/Services/StorageSpecificationService.php
PASS  postoji database/migrations/2026_08_04_000033_create_catalog_type_layout_v2_1_3.php
PASS  postoji database/migrations/2026_08_04_000034_repair_catalog_category_and_spec_integrity_v2_1_3_1.php
PASS  postoji database/migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php
PASS  postoji public/assets/js/dictionary-sort-manager.js
PASS  postoji resources/views/admin/dictionary/product-type.blade.php
PASS  postoji tests/Unit/ProductMediaUxContractTest.php
PASS  postoji tests/Unit/ProductAnnouncementContractTest.php
PASS  postoji app/Services/ProductAnnouncementService.php
PASS  postoji tests/Unit/ReleaseCandidateHardeningContractTest.php
PASS  postoji tests/Unit/StableReleaseContractTest.php
PASS  postoji tests/Unit/StableMaintenanceContractTest.php
PASS  postoji app/Console/Commands/SecurityHardeningDoctorCommand.php
PASS  postoji app/Console/Commands/MigrationsDoctorCommand.php
PASS  postoji app/Console/Commands/AccessControlDoctorCommand.php
PASS  postoji app/Console/Commands/ReleaseIntegrityCommand.php
PASS  postoji app/Console/Commands/BackupVerifyCommand.php
PASS  postoji app/Console/Commands/ProductMediaDoctorCommand.php
PASS  postoji app/Http/Controllers/ProductMediaDownloadController.php
PASS  postoji public/assets/js/product-media-manager.js
PASS  postoji resources/views/admin/products/partials/image-card.blade.php
PASS  postoji resources/views/admin/products/partials/image-upload.blade.php
PASS  postoji bin/catalog-detail-smoke.php
PASS  postoji bin/detail-pages-doctor-smoke.php
PASS  postoji tests/Unit/CatalogDetailBladeContractTest.php
PASS  postoji tests/Unit/SystemHealthRemediationContractTest.php
PASS  postoji tests/Unit/DetailPagesDoctorContractTest.php
PASS  postoji database/migrations/2026_07_31_000027_create_correlated_specifications_beta7_18.php
PASS  postoji app/Models/SpecificationOption.php
PASS  postoji app/Services/SpecificationDependencyService.php
PASS  postoji app/Services/CatalogSpecificationFilterService.php
PASS  postoji app/Console/Commands/CatalogCorrelationsDoctorCommand.php
PASS  postoji resources/views/partials/correlated-specification-filters.blade.php
PASS  postoji resources/views/partials/correlated-specification-filter-script.blade.php
PASS  postoji tests/Unit/CorrelatedSpecificationsMigrationContractTest.php
PASS  postoji tests/Unit/CorrelatedSpecificationUiContractTest.php
PASS  postoji docs/UPGRADE-V2.1.4.md
PASS  postoji bin/cms-v2.1.4-smoke.php
PASS  postoji tests/Unit/CmsV214ContractTest.php
PASS  postoji app/Console/Commands/CmsV214DoctorCommand.php
PASS  postoji app/Services/ProductDeletionService.php
PASS  postoji database/migrations/2026_08_04_000036_add_product_model_and_name_templates_v2_1_4.php
PASS  postoji docs/UPGRADE-V2.1.4.1.md
PASS  postoji bin/product-type-page-render-hotfix-smoke.php
PASS  postoji tests/Unit/ProductTypePageRenderHotfixContractTest.php
PASS  postoji docs/UPGRADE-V2.1.5.md
PASS  postoji bin/cms-v2.1.5-smoke.php
PASS  postoji tests/Unit/CmsV215ContractTest.php
PASS  postoji app/Console/Commands/CmsV215DoctorCommand.php
PASS  postoji public/assets/js/ux-runtime.js
PASS  postoji resources/views/errors/minimal.blade.php
PASS  postoji resources/views/errors/403.blade.php
PASS  postoji resources/views/errors/404.blade.php
PASS  postoji resources/views/errors/419.blade.php
PASS  postoji resources/views/errors/429.blade.php
PASS  postoji resources/views/errors/500.blade.php
PASS  postoji resources/views/errors/503.blade.php
PASS  postoji database/migrations/2026_08_05_000037_place_desktop_power_supply_field_v2_1_5.php
PASS  postoji docs/UPGRADE-V2.2.0.md
PASS  postoji docs/openapi.yaml
PASS  postoji bin/cms-v2.2.0-smoke.php
PASS  postoji tests/Feature/MobileApiFoundationTest.php
PASS  postoji tests/Unit/MobileApiFoundationContractTest.php
PASS  postoji app/Console/Commands/CmsV220DoctorCommand.php
PASS  postoji app/Http/Controllers/Api/V1/BootstrapController.php
PASS  postoji app/Http/Controllers/Api/V1/MobileDeviceController.php
PASS  postoji app/Models/MobileDevice.php
PASS  postoji database/migrations/2026_08_06_000039_create_mobile_devices_v2_2_0.php
PASS  postoji database/migrations/2026_08_06_000040_add_push_notification_preference_v2_2_0.php
PASS  postoji database/migrations/2026_08_06_000041_create_database_queue_tables_v2_2_0.php
PASS  verzija je 2.2.0 Mobile API Foundation
PASS  release tag je v2.2.0
PASS  upgrade osnova je v2.1.6
PASS  composer.json validan
PASS  PHP minimum 8.4
PASS  Laravel 13
PASS  Composer lint/autoload/test/release skripte postoje
PASS  runtime verzija je 2.2.0
PASS  migracija sadrži idempotency_keys
PASS  migracija sadrži source_system
PASS  migracija sadrži inventory_state
PASS  migracija sadrži inventory_returned_at
PASS  migracija sadrži event_key
PASS  migracija sadrži orders_user_idempotency_unique
PASS  porudžbina zaključava proizvode
PASS  porudžbina umanjuje lager u transakciji
PASS  povrat lagera ima jedinstveni event key
PASS  idempotency koristi unique zapis i row lock
PASS  legacy porudžbine su blokirane
PASS  legacy SQL guard je registrovan pre izvršavanja
PASS  legacy MySQL sesija je READ ONLY
PASS  Redis je uklonjen iz database konfiguracije
PASS  Redis je uklonjen iz cache konfiguracije
PASS  Redis je uklonjen iz queue konfiguracije
PASS  login rate limiter koristi file store
PASS  dozvola orders.create
PASS  dozvola orders.view_own
PASS  dozvola orders.cancel_own
PASS  dozvola orders.manage
PASS  dozvola stock.view
PASS  dozvola stock.adjust
PASS  dozvola reports.view
PASS  dozvola reports.export
PASS  dozvola invoices.manage
PASS  dozvola invoices.view_own
PASS  web ruta orders.store
PASS  web ruta orders.cancel
PASS  web ruta admin.orders.status
PASS  web ruta admin.orders.payment
PASS  web ruta admin.orders.tracking
PASS  web ruta admin.stock.adjust
PASS  API porudžbine postoje
PASS  porudžbina ima dodeljenog SuperAdmin/Admin dobavljača
PASS  admin scope vidi samo njemu dodeljene porudžbine
PASS  izveštaji podržavaju filtere i CSV/PDF
PASS  poslovni dokumenti koriste nepromenljivi snapshot
PASS  PDF renderer je lokalni i bez Redis/eksternog servisa
PASS  brojevi dokumenata su transakcioni i jedinstveni
PASS  web rute imaju reports CSV/PDF i dokumente
PASS  reports stranica ima schema fallback umesto 500
PASS  reports render je unutar zaštićenog controller toka
PASS  reports view ima render marker i bezbedne URL-ove
PASS  reports export vraća kontrolisani 503
PASS  reports doctor izvršava repair i stvarne SQL upite
PASS  reports doctor renderuje controller Blade i layout
PASS  reports logging je best-effort
PASS  beta1.2 repair migracija je nedestruktivna
PASS  hamburger dugme postoji
PASS  mobilni meni ima kontrolni JavaScript
PASS  mobilni meni nema horizontalni scroll
PASS  direktne mobilne stavke koriste zajednički levi wrapper
PASS  Početna Provizije i Izveštaji su poravnati ulevo
PASS  CSS ima pouzdan cache busting
PASS  legacy desktop header ima dva reda
PASS  legacy mobilni header zadržava kurs temu nalog i hamburger
PASS  dashboard ima moderni hero KPI prioritete i module
PASS  dashboard CSS ima 4 desktop i 2 mobilne kolone
PASS  admin gridovi su poravnati na vrh
PASS  forme koriste sadržajnu visinu
PASS  deployment check ima bezbedan repair režim
PASS  deployment check razlikuje runtime zaštitu i grant warning
PASS  deployment check proverava i operativne kolone
PASS  dashboard koristi DB fallback umesto 500
PASS  login telemetry je best-effort
PASS  login hvata session i remember-token probleme
PASS  authenticated layout nema direktan SettingsService upit
PASS  authenticated layout koristi bezbedne user helper metode
PASS  dashboard logging ne može da obori fallback
PASS  runtime middleware prethodi session/cache middleware-u
PASS  deployment repair kreira runtime direktorijume i kompajlira Blade
PASS  auth doctor može da renderuje kompletan dashboard
PASS  Turnstile hvata sve transportne/JSON greške
PASS  Turnstile podešavanja imaju DB prioritet i env fallback
PASS  Turnstile secret se čuva šifrovano i ne izlaže kroz all
PASS  Turnstile admin ekran i ruta postoje
PASS  beta6 repair migracija popravlja core login šemu
PASS  static check razdvaja runtime i ZIP režim
PASS  operativna migracija sadrži order_internal_notes
PASS  operativna migracija sadrži order_assignments
PASS  operativna migracija sadrži commission_payment_batches
PASS  operativna migracija sadrži notifications
PASS  operativna migracija sadrži payment_batch_id
PASS  operativna migracija sadrži status_updated_at
PASS  operativna migracija sadrži last_internal_note_at
PASS  beta2 dozvola commissions.view_own
PASS  beta2 dozvola orders.reassign
PASS  beta2 dozvola orders.internal_notes
PASS  beta2 dozvola notifications.view
PASS  provizije imaju odobravanje isplatu storniranje i istoriju
PASS  masovna isplata koristi transakciju row lock i batch
PASS  korisnik vidi samo svoje provizije i podrazumevanih 10 procenata
PASS  interne napomene nisu u javnom timeline-u
PASS  ponovna dodela je ograničena na SuperAdministratora
PASS  preuzimanje i rokovi porudžbine imaju audit i obaveštenja
PASS  database notifikacije su neblokirajuće i mail je opcioni
PASS  operativni doctor proverava šemu SQL i render
PASS  admin provizije imaju filtere CSV PDF i masovnu isplatu
PASS  commission tabela nema unutrašnji vertikalni scroll pri obradi
PASS  obrada provizije koristi veliki viewport modal
PASS  commission modal ima naslov i eksplicitno zatvaranje
PASS  otvaranje commission modala zatvara prethodni
PASS  porudžbina ima timeline interne napomene preuzimanje rokove i reassignment UI
PASS  inbox obaveštenja podržava read i read-all
PASS  operativni feature testovi postoje
PASS  commission modal regresioni feature test postoji
PASS  beta3.1 migracija nema globalni use Throwable
PASS  beta3.1 migracija koristi potpuno kvalifikovani Throwable
PASS  PHP lint odbija warning deprecated i notice izlaz
PASS  beta3 migracija sadrži order_payments
PASS  beta3 migracija sadrži stock_receipts
PASS  beta3 migracija sadrži stock_receipt_items
PASS  beta3 migracija sadrži inventory_counts
PASS  beta3 migracija sadrži inventory_count_items
PASS  beta3 migracija sadrži payment_state
PASS  beta3 migracija sadrži paid_total_rsd
PASS  beta3 migracija sadrži payment_due_at
PASS  beta3 dozvola payments.manage
PASS  beta3 dozvola payments.upload_proof
PASS  beta3 dozvola payments.view_own
PASS  beta3 dozvola inventory.receive
PASS  beta3 dozvola inventory.count
PASS  beta3 dozvola inventory.export
PASS  uplate koriste transakciju row lock audit i saldo
PASS  potvrde uplate su privatne i autorizovane
PASS  IPS podaci koriste snapshot porudžbine
PASS  predračun i račun postavljaju dospeće porudžbine
PASS  ulaz robe i popis koriste idempotency transakciju i row lock
PASS  napredni lager ima readiness fallback umesto 500
PASS  reports beta3 sažeci i izvozi su zaštićeni
PASS  beta3 doctor proverava repair SQL i render
PASS  beta3 feature testovi pokrivaju uplate ulaz i popis
PASS  beta7.5 repair migracija obnavlja PDF i payment šemu
PASS  beta7.5 repair migracija je nedestruktivna
PASS  beta7.5 potvrda koristi site name fallback
PASS  beta7.5 ručno evidentiranje uplate ima regresioni test
PASS  beta7.5 doctor proverava dokument i payment tabele
PASS  beta7.6 PDF dozvoljava lokalno uvezene porudžbine
PASS  beta7.6 uplate dozvoljavaju lokalno uvezene porudžbine
PASS  beta7.6 legacy lager zaštita ostaje aktivna
PASS  beta7.7 migracija dodaje terminalno stanje porudžbine
PASS  beta7.7 PDF podešavanja imaju upload pregled i uklanjanje logotipa
PASS  beta7.7 PDF logo se ugrađuje kao lokalni JPEG
PASS  beta7.7 PDF ne prikazuje subagent email kupca
PASS  beta7.7 kompletiranje COD porudžbine evidentira preostali saldo
PASS  beta7.7 kompletirana porudžbina zaključava dalje izmene
PASS  beta7.7 kompletiranje je jasno dostupno u detalju i listi
PASS  beta7.8 migracija dodaje evidenciju isporuke i reopening stanje
PASS  beta7.8 kompletiranje čuva dokaz isporuke privatno
PASS  beta7.8 otpremnica koristi OTP broj i delivery snapshot
PASS  beta7.8 ponovno otvaranje je superadmin-only i auditovano
PASS  beta7.8 detalj prikazuje strukturiranu evidenciju isporuke
PASS  beta7.8 doctor proverava novu šemu i dozvole
PASS  beta7.8 feature testovi pokrivaju dokaz otpremnicu i reopening
PASS  beta7.8 UI ima delivery workflow responsive stilove
PASS  beta7.9 migracija uklanja legacy ENUM blokadu za delivery_note
PASS  beta7.9 servis radi schema preflight pre izdavanja otpremnice
PASS  beta7.9 pomoćni notification kvar ne obara izdat dokument, a IPS važi samo za finansijske dokumente
PASS  beta7.9 kontroleri vraćaju incident poruku umesto Error 500
PASS  beta7.9 doctor proverava stvarni MySQL tip dokumenta
PASS  beta7.9 ima migration contract i delivery note PDF smoke test
PASS  detail koristi eksplicitan slug upit
PASS  slug upit primenjuje objedinjeni visibility scope
PASS  API detail koristi isti slug upit
PASS  neispravna slika ne obara detail
PASS  detail filtrira slike bez validnog URL-a
PASS  katalog generiše eksplicitan slug link
PASS  detail ima interaktivnu thumbnail galeriju
PASS  detail ima fullscreen lightbox i zoom kontrole
PASS  gallery podržava tastaturu swipe i preload
PASS  gallery radi i sa jednom slikom
PASS  gallery CSS ima fullscreen viewport i responsive mobile
PASS  gallery feature testovi postoje
PASS  beta4 migracija sadrži automation_runs
PASS  beta4 migracija sadrži operational_alerts
PASS  beta4 migracija sadrži notification_preferences
PASS  beta4 nema Redis i koristi scheduler/file lock
PASS  beta4 detektuje nepreuzete porudžbine dospele obaveze i nizak lager
PASS  beta4 upozorenja su deduplikovana i razrešavaju se
PASS  notification preferences upravljaju kanalima i kategorijama
PASS  automation settings UI i ručno pokretanje postoje
PASS  automation doctor proverava repair scheduler i run
PASS  beta4 dozvola automation.manage postoji
PASS  beta4 feature testovi pokrivaju deduplikaciju i preference
PASS  beta5 inventory koristi jednu aktivnu operaciju
PASS  beta5 inventory čuva filter i limit nakon knjiženja
PASS  beta5 inventory nema unutrašnji vertikalni scrollbar
PASS  beta5 inventory responsive tabela koristi data-label kartice
PASS  beta5 feature test pokriva inventory workspace
PASS  beta6 migracija sadrži backup_runs
PASS  beta6 migracija sadrži system_health_snapshots
PASS  beta6 migracija sadrži system_runtime_states
PASS  beta6 migracija sadrži security_events
PASS  beta6 migracija sadrži system.health
PASS  beta6 migracija sadrži backups.manage
PASS  beta6 migracija sadrži audit.export
PASS  beta6 migracija sadrži security.view
PASS  beta6 backup koristi mysqldump bez lozinke u argumentima
PASS  beta6 backup odbija public putanju i pravi SHA-256 manifest
PASS  beta6 system health proverava scheduler backup migracije i legacy
PASS  beta6 security header-i i request ID postoje
PASS  beta6 audit koristi rekurzivnu sanitizaciju i request ID
PASS  beta6 rate limiter-i pokrivaju upload export admin i backup
PASS  beta6 test DB doctor ima višestruku zaštitu
PASS  beta6 system health UI i backup akcije postoje
PASS  beta6 scheduler ima heartbeat backup i health snapshot
PASS  beta6 feature i unit testovi postoje
PASS  beta7.1 orders ima readiness SQL i render zaštitu
PASS  beta7.1 orders doctor proverava isti browser render
PASS  beta7.1 orders recovery ne završava generičkim 500
PASS  beta7.1 edit artikla ima rotaciju ulevo i udesno
PASS  beta7.1 legacy rotacija koristi copy-on-write
PASS  beta7.1 rotacija koristi privremeni fajl i kontrolisani Imagick/GD fallback
PASS  beta7.1 feature testovi postoje
PASS  beta7.2 order detail koristi opcioni schema-aware loader
PASS  beta7.2 admin i user detail imaju protected render
PASS  beta7.2 admin i user detail imaju readiness markere
PASS  beta7.2 timeline i IPS ne mogu oboriti detalj
PASS  beta7.2 orders doctor renderuje oba detalja
PASS  beta7.2 detail-pages doctor proverava ključne detail stranice
PASS  beta7.2 feature testovi pokrivaju detail i opcione tabele
PASS  beta7.3 detail koristi scalar presenter umesto Eloquent objekata u Blade-u
PASS  beta7.3 presenter bezbedno obrađuje raw i zero datume
PASS  beta7.3 presenter bezbedno generiše named rute
PASS  beta7.3 detail view nema direktne auth, relation ili datetime pozive
PASS  beta7.3 admin i user detail imaju ne-503 read-only fallback
PASS  beta7.3 orders doctor prikazuje tačan exception uzrok za oba detaila
PASS  beta7.3 orders doctor nastavlja admin i user audit
PASS  beta7.3 payment i inventory Gates su definisani
PASS  beta7.3 presenter i ViewValue regresioni testovi postoje
PASS  beta7.10 migracija sadrži after_sales_cases
PASS  beta7.10 migracija sadrži after_sales_case_items
PASS  beta7.10 migracija sadrži after_sales_messages
PASS  beta7.10 migracija sadrži after_sales_attachments
PASS  beta7.10 migracija sadrži after_sales_status_history
PASS  beta7.10 ima tri postprodajne dozvole
PASS  beta7.10 pristup poštuje vlasnika dodeljenog admina i superadmin scope
PASS  beta7.10 slučaj zahteva isporučenu ili kompletiranu porudžbinu
PASS  beta7.10 čuva pogođene stavke snapshot i SLA rok
PASS  beta7.10 privatni prilozi proveravaju MIME veličinu i autorizaciju
PASS  beta7.10 javne i interne poruke su odvojene
PASS  beta7.10 statusni tok zahteva obrazloženje konačne odluke
PASS  beta7.10 automatizacija upozorava na probijene rokove slučaja
PASS  beta7.10 UI ima korisnički i administratorski postprodajni tok
PASS  beta7.10 doctor proverava šemu dozvole i SQL
PASS  beta7.10 feature test pokriva privatni prilog i obradu
PASS  beta7.10 privatni download zabranjuje browser cache
PASS  beta7.10 konkurentno zatvaranje ne propušta novu poruku
PASS  beta7.10 reopening zahteva razlog i čuva vreme prethodnog rešenja
PASS  beta7.10 nedodeljeni slučajevi obaveštavaju superadministratore
PASS  beta7.10 dashboard prikazuje aktivne probijene i waiting slučajeve
PASS  beta7.11 migracija sadrži after_sales_actions
PASS  beta7.11 migracija sadrži after_sales_action_items
PASS  beta7.11 migracija sadrži after_sales_action_id
PASS  beta7.11 migracija sadrži after_sales.execute
PASS  beta7.11 podržava četiri izvršne radnje
PASS  beta7.11 lager efekti su zaključani i idempotentni
PASS  beta7.11 refundacija je vezana za radnju i ograničena neto uplatom
PASS  beta7.11 slučaj čeka završetak aktivnih radnji
PASS  beta7.11 UI ima planiranje pokretanje izvršenje i otkazivanje
PASS  beta7.11 Gate i permission middleware štite izvršne kontrole
PASS  beta7.11 controller ima sve izvršne endpoint-e
PASS  beta7.11 automatizacija prati rok izvršne radnje
PASS  beta7.11 dashboard prikazuje radnje za izvršenje
PASS  beta7.11 testovi pokrivaju idempotentni lager povrat i refundaciju
PASS  beta7.12 migracija sadrži field_service_teams
PASS  beta7.12 migracija sadrži field_work_orders
PASS  beta7.12 migracija sadrži field_work_order_attachments
PASS  beta7.12 migracija sadrži field_operations.view
PASS  beta7.12 migracija sadrži field_operations.manage
PASS  beta7.12 fizičke radnje automatski dobijaju radni nalog
PASS  beta7.12 sprečava preklapanje termina iste ekipe
PASS  beta7.12 završetak zahteva dolazak na lokaciju
PASS  beta7.12 radni nalog čuva troškove kilometražu i privatne dokaze
PASS  beta7.12 UI ima kalendar ekipe i operativne statuse
PASS  beta7.12 rute i Gate štite terenske operacije
PASS  beta7.12 automatizacija prati neplanirane i probijene radne naloge
PASS  beta7.12 doctor proverava tabele dozvole rute i SQL
PASS  beta7.12 test pokriva auto nalog konflikt i on-site završetak
PASS  beta7.13 migracija sadrži service_part_suppliers
PASS  beta7.13 migracija sadrži service_parts
PASS  beta7.13 migracija sadrži field_work_order_parts
PASS  beta7.13 migracija sadrži service_part_movements
PASS  beta7.13 migracija sadrži service_part_purchase_requests
PASS  beta7.13 migracija sadrži service_part_purchase_request_items
PASS  beta7.13 migracija sadrži service_parts.view
PASS  beta7.13 migracija sadrži service_parts.manage
PASS  beta7.13 migracija sadrži service_parts.procurement
PASS  beta7.13 početno stanje ulazi u movement ledger
PASS  beta7.13 rezervacija ne umanjuje fizičko stanje
PASS  beta7.13 završetak skida stvarni utrošak i oslobađa ostatak
PASS  beta7.13 otkazivanje oslobađa sve rezervacije
PASS  beta7.13 kretanja servisnog lagera su idempotentna i ponovo proverena pod lockom
PASS  beta7.13 nacrt nabavke koristi konkurentno bezbedan privremeni broj
PASS  beta7.13 prijem nabavke računa ponderisanu prosečnu cenu
PASS  beta7.13 UI ima servisni lager dobavljače nabavku i utrošak
PASS  beta7.13 Gate i rute štite lager i nabavku
PASS  beta7.13 automatizacija prati nizak lager i kašnjenje nabavke
PASS  beta7.13 doctor proverava tabele dozvole rute i SQL
PASS  beta7.13 testovi pokrivaju ledger rezervaciju utrošak i ponderisanu cenu
PASS  beta7.13 UI ima responsive stilove servisnog lagera
PASS  beta7.14.1 migracija prvo obezbeđuje FK indeks
PASS  beta7.14 migracija uklanja unique order/type ograničenje
PASS  beta7.14 migracija uvodi revizije i vezu sa prethodnim dokumentom
PASS  beta7.14 servis vraća samo aktivan dokument ili izdaje novu reviziju
PASS  beta7.14 storniranje zahteva razlog i čuva audit podatak
PASS  beta7.14 model podržava supersedes relaciju
PASS  beta7.14 UI razlikuje aktivan dokument i novu reviziju
PASS  beta7.14 PDF prikazuje broj revizije
PASS  beta7.14 regresioni test pokriva ponovno izdavanje
PASS  beta7.15 migracija sadrži warranty_rules
PASS  beta7.15 migracija sadrži product_warranties
PASS  beta7.15 migracija sadrži warranty_maintenance_records
PASS  beta7.15 migracija sadrži warranties.view_own
PASS  beta7.15 migracija sadrži warranties.manage
PASS  beta7.15 pravila imaju product category global prioritet
PASS  beta7.15 kompletiranje automatski izdaje garanciju bez obaranja porudžbine
PASS  beta7.15 otkazivanje poništava aktivne garancije
PASS  beta7.15 GAR poslovni broj je registrovan
PASS  beta7.15 garancija čuva snapshot kupca artikla uslova i serijskih brojeva
PASS  beta7.15 preventivno održavanje generiše sledeći termin
PASS  beta7.15 zakazivanje ne menja vreme tokom provere datuma
PASS  beta7.15 backfill bira samo stavke bez garancije
PASS  beta7.15 PDF garantni list prikazuje ključne snapshot podatke
PASS  beta7.15 korisnički i administratorski prikazi postoje
PASS  beta7.15 rute Gates i administratorski scope štite garancije
PASS  beta7.15 automatizacija prati istek i održavanje
PASS  beta7.15 dashboard prikazuje garancije
PASS  beta7.15 doctor i backfill komande postoje
PASS  beta7.15 feature test pokriva automatsko izdavanje i prioritet pravila
PASS  beta7.15 warranty PDF smoke postoji
PASS  beta7.16 migracija uvodi outbox QR snapshot i dane garancije
PASS  beta7.16 migracija ima recovery putanju za delimičan MariaDB DDL
PASS  beta7.16 e-mail outbox ima dedupe intervale i pojedinačne primaoce
PASS  beta7.16 e-mail prima autor odgovorno lice i dodatne adrese
PASS  beta7.16 workflow šalje status tracking plaćanje i dokumente
PASS  beta7.16 dispatcher ima retry stuck recovery i zaštitu storniranog priloga
PASS  beta7.16 scheduler šalje outbox svake minute
PASS  beta7.16 admin podešava intervale događaje i dokumente
PASS  beta7.16 e-mail šablon ima događaje i bezbedan action link
PASS  beta7.16 NBS payload koristi zvanične oznake i RSD zarez
PASS  beta7.16 NBS servis koristi zvanični HTTPS endpoint i čuva privatni PNG snapshot
PASS  beta7.16 stornirani istorijski dokument ostaje pregledljiv bez ponovnog NBS poziva
PASS  beta7.16 finansijski dokument bez validnog NBS QR se ne izdaje
PASS  beta7.16 PDF crta PNG bez GD i prikazuje NBS IPS QR oznaku
PASS  beta7.16 IPS QR smoke potvrđuje sliku oznaku i tačan RSD iznos
PASS  beta7.16 garancija podržava kombinaciju meseci i dana
PASS  beta7.16 admin može kreirati porudžbinu
PASS  beta7.16 doctor proverava outbox SMTP scheduler NBS i garancijske dane
PASS  beta7.16 feature test pokriva admin porudžbinu događaje NBS QR i dane garancije
PASS  beta7.17 migracija uvodi predmete rate i komunikaciju naplate
PASS  beta7.17 migracija je recovery-safe za delimičan DDL
PASS  beta7.17 servis automatski otvara zatvara i usklađuje predmete
PASS  beta7.17 rate se raspoređuju prema stvarno plaćenom iznosu
PASS  beta7.17 automatske opomene koriste faze dedupe i outbox
PASS  beta7.17 admin ima aging pregled plan i evidenciju komunikacije
PASS  beta7.17 podmeni se zatvara klikom van escape i izborom stavke
PASS  beta7.17 checkbox i radio imaju normalnu globalnu veličinu
PASS  beta7.17 doctor proverava šemu dozvolu i scheduler
PASS  beta7.17.1 permission seed je schema-aware
PASS  beta7.17.1 seeder ne zahteva permissions.updated_at
PASS  beta7.17.2 hover podmeni ima grace period i click pin
PASS  beta7.17.2 CSS premošćava razmak do podmenija
PASS  beta7.18 migracija uvodi strukturirane opcije i korelacije
PASS  beta7.18 migracija je recovery-safe za MariaDB
PASS  beta7.18 procesor ima porodicu i tačan model
PASS  beta7.18 brend filtrira samo sopstvene linije
PASS  beta7.18 generičke zavisnosti imaju server validaciju i zaštitu ciklusa
PASS  beta7.18 forma skriva nepovezane opcije i čuva detalj
PASS  beta7.18 kataloški filteri podržavaju select range boolean text i detalj
PASS  beta7.18 oba kataloga koriste korelisane filtere
PASS  beta7.18 doctor proverava procesor linije veze i tipove
PASS  beta7.18.1 forma artikla ne koristi nedostupni index filter servis
PASS  beta7.18.1 jedinstveni katalog dobija podatke za korelisane filtere
PASS  beta7.18.1 šifarnici dobijaju podatke za roditelje i mape zavisnosti
PASS  beta7.19 migracija uvodi šablone kompletnost i poreklo klona
PASS  beta7.19 migracija je recovery-safe i obračunava postojeći katalog
PASS  beta7.19 template servis podržava alias placeholdere
PASS  beta7.19 completeness servis vraća nepotpun aktivan artikal u nacrt
PASS  beta7.19 kloniranje čuva novi SKU i nulti lager
PASS  beta7.19 clone checkboxi eksplicitno šalju nulu
PASS  beta7.19 bulk zahteva pregled i blokira praznu operaciju
PASS  beta7.19 bulk promena brenda čisti neusklađenu liniju
PASS  beta7.19 preview naziva uklanja method spoof
PASS  beta7.19 doctor proverava šemu rute i kompletnost
PASS  beta7.19 feature test pokriva naziv klon i bulk
PASS  beta7.20 istorijska migracija ostaje sačuvana kao migration history
PASS  Product Variants forward decommission migracija postoji jednom
PASS  Product Variants decommission migracija ima recovery-safe rollback rekonstrukciju
PASS  Product Variants runtime klase su fizički uklonjene
PASS  Product Variants admin UI fajlovi su fizički uklonjeni
PASS  Porudžbine su product-only bez variant identiteta i snapshotova
PASS  Postprodaja garancija i stock movement su product-only
PASS  Inventory je product-only bez variants_enabled grane
PASS  Kataloški query filter i detalj su product-only
PASS  Product slike i model su product-only
PASS  Clone vise ne nudi niti obrađuje kopiranje varijanti
PASS  Product Variants Feature test sada proverava retired route i uklonjenu šemu
PASS  Product Variants UI contract sada zahteva potpuno uklonjen variant UI
PASS  Product Variants decommission smoke postoji kao završni regresioni guard
PASS  beta7.17 feature test pokriva dedupe rate zatvaranje i UI regresiju
PASS  beta7.21 migracija uvodi nabavne snapshotove i rasporede
PASS  beta7.21 migracija je recovery-safe i permission schema-aware
PASS  beta7.21 marža koristi snapshot i prikazuje pokrivenost troška
PASS  beta7.21 filteri važe za KPI trend i segmente
PASS  beta7.21 dashboard pokriva lager potraživanja postprodaju i tim
PASS  beta7.21 PDF upravljačkog izveštaja postoji
PASS  beta7.21 raspored ima retry dedupe i zasebne primaoce
PASS  beta7.21 ekran je bezbedan pre migracije
PASS  beta7.21 UI ima CSV PDF rasporede i cost coverage
PASS  beta7.22.1 management analytics koristi aktivnu temu bez belog fallback-a
PASS  beta7.22.1 CSS kompatibilni aliasi postoje
PASS  beta7.21 feature test pokriva ekran export i raspored
PASS  beta7.22 portal servis i fallback podaci postoje
PASS  beta7.22 portal objedinjuje porudžbine dokumente uplate garancije i servis
PASS  beta7.22 report grouping je kompatibilan sa ONLY_FULL_GROUP_BY
PASS  beta7.22 dashboard ima trend prioritete brze akcije i operativne module
PASS  beta7.22.1 portal doctor prosleđuje ViewErrorBag
PASS  beta7.22.1 layout bezbedno proverava errors bag
PASS  beta7.23 release-check komanda ima profile i kontrolisane režime
PASS  beta7.23 release registry ima quick standard i full profile
PASS  beta7.23 release plan ne dispatchuje poslovne akcije
PASS  beta7.23 release metadata i atomski JSON report postoje
PASS  beta7.23 release rezultat ima READY i NOT READY ugovor
PASS  beta7.23 smoke i dokumentacija postoje
PASS  beta7.23.1 catalog detail je product-only i nema retired variant Blade markere
PASS  beta7.23.1 catalog detail Blade direktive su izbalansirane
PASS  beta7.23.1 product-only detail Feature i smoke regresija postoje
PASS  beta7.23.1 health daje čitljive runtime remediation komande
PASS  beta7.23.2 detail doctor rešava controller zavisnosti kroz container
PASS  beta7.23.2 detail doctor nema direktan edit poziv sa jednim argumentom
PASS  beta7.23.2 detail doctor smoke i contract regresija postoje
PASS  beta7.24 migracija uvodi aktivacije sesije komunikaciju i order-link audit
PASS  beta7.24 aktivacioni token je hashiran jednokratan i vremenski ograničen
PASS  beta7.24 session registry koristi hash i podržava revoke
PASS  beta7.24 kupac vidi samo javne poruke a admin interne
PASS  beta7.24 portal rute aktivacija i admin centar postoje
PASS  beta7.24 komunikacija razdvaja public i internal
PASS  beta7.24 smoke i PHPUnit regresije postoje
PASS  beta7.24 maintenance čisti tokene i stare session evidencije
PASS  beta7.24 reinvite ne deaktivira aktivnog kupca i aktivacija nije cache-ovana
PASS  beta7.24.1 management repair obrađuje missing snapshotove
PASS  beta7.24.1 repair ne prepisuje kompletne snapshotove
PASS  beta7.24.1 repair je transakcioni i koristi row lock
PASS  beta7.24.1 kandidati imaju transparentan product-only izvor
PASS  beta7.24.1 ručna finansijska promena zahteva razlog i audit
PASS  beta7.24.1 audit/repair komanda i regresije postoje
PASS  rc1 profil sadrzi final hardening provere
PASS  rc1 security doctor proverava production debug HTTPS session i public fajlove
PASS  rc1 migration doctor proverava pending SQL mode i foreign keys
PASS  rc1 access doctor proverava route permission i superadmin
PASS  rc1 release integrity proverava SHA-256 i path traversal
PASS  rc1 backup verify je read-only i proverava SQL gzip i file hash
PASS  rc1 smoke i contract regresije postoje
PASS  rc1 nema novu migration datoteku
PASS  stable profil je identican potvrdenom rc profilu
PASS  stable smoke i contract regresije postoje
PASS  stable početna je univerzalni dashboard sa integrisanim korisničkim centrom
PASS  stable nema zasebnu Moj portal stranicu ni stavku menija
PASS  stable nema novu migration datoteku
PASS  v2.1.2 obaveštenja o novom artiklu su opt-in i koriste outbox
PASS  v2.1.2 novi artikal se šalje aktivnim registrovanim korisnicima bez duplikata
PASS  v2.1.2 mail podešavanja i šablon podržavaju nove artikle
PASS  v2.1.2 product announcement regresije postoje
PASS  v2.1.2 nema novu migration datoteku
PASS  APP_ENV production
PASS  Redis nije obavezan za database queue
PASS  file session/cache/limiter i database queue
PASS  secret vrednosti su prazne
PASS  import ne upisuje legacy konekciju
INFO  ZIP hygiene provere su preskočene na instaliranoj aplikaciji; za raspakovani sanitized ZIP koristi --package.
PASS  v2.1.3 tipovi proizvoda imaju posebne stranice i Drag & Drop
PASS  v2.1.3 specifikaciona polja mogu trajno da se obrišu
PASS  v2.1.3 tip automatski određuje kategoriju
PASS  v2.1.3 diskovi imaju pojedinačne celobrojne GB kapacitete
PASS  v2.1.3 catalog settings doctor postoji
PASS  v2.1.3 grana ima tri kontrolisane migration datoteke
PASS  v2.1.3.3 ProductRequest zadržava validan SKU regex delimiter
PASS  v2.1.3.3 ProductVariantRequest je retired a ProductRequest zadržava validan SKU regex
PASS  v2.1.3.3 migracija povezuje listu diskova i izvedeni ukupni kapacitet
PASS  v2.1.3.3 stari kapacitet se bezbedno prenosi na prvi disk
PASS  v2.1.3.3 backend ne veruje ručnom ukupnom zbiru
PASS  v2.1.3.3 ukupni kapacitet je ispod diskova i readonly
PASS  v2.1.3.3 frontend sabira diskove i čuva početni legacy zbir
PASS  v2.1.3.3 product-only storage model zadržava izvedeni zbir bez variant servisa
PASS  v2.1.3.3 storage smoke i contract test postoje
PASS  v2.1.4 migracija dodaje model proizvoda i usklađuje šablone
PASS  v2.1.4 model se validira čuva i koristi u nazivu
PASS  v2.1.4 forma ima model proizvoda posle linije
PASS  v2.1.4 trajno brisanje ima SKU potvrdu i izbor brisanja slika
PASS  v2.1.4 poslovna istorija blokira destruktivno brisanje
PASS  v2.1.4 semantički sistem tastera pokriva sve uloge
PASS  v2.1.4 route i stable doctor postoje
PASS  v2.1.4 smoke i contract test postoje
PASS  v2.1.4.1 controller priprema i prosledjuje orderedFields
PASS  v2.1.4.1 Blade bezbedno inicijalizuje orderedFields
PASS  v2.1.4.1 doctor renderuje formulare svih tipova
PASS  v2.1.4.1 smoke i contract test postoje
PASS  v2.1.5 globalni UX runtime štiti submit i nesačuvane izmene
PASS  v2.1.5 mobilni action dock koristi originalni submit
PASS  v2.1.5 validacija i accessibility markeri postoje
PASS  v2.1.5 dugi formulari su eksplicitno označeni
PASS  v2.1.5 sistemske error stranice postoje
PASS  v2.1.5 migracija koristi postojeće snaga-napajanja polje
PASS  v2.1.5 migracija postavlja napajanje u sredinu
PASS  v2.1.5 doctor proverava Blade, route akcije i napajanje
PASS  v2.1.5 stable release koristi render i repair
PASS  v2.1.5 smoke i contract test postoje
PASS  v2.1.6 migracija kreira snapshot istoriju i ciljane indekse
PASS  v2.1.6 Data Quality audit pokriva product-only katalog slike i specifikacije
PASS  v2.1.6 repair je nedestruktivan i preračunava izvedene vrednosti
PASS  v2.1.6 performance doctor proverava indekse cache i SQL pragove
PASS  v2.1.6 dashboard kešira schema metadata po requestu
PASS  v2.1.6 Data Quality Center rute i prikaz postoje
PASS  v2.1.6 katalog ima quality filtere
PASS  v2.1.6 doctor renderuje centar i pokreće performance audit
PASS  v2.1.6 Stable release uključuje render repair i strict
PASS  v2.1.6 smoke i contract test postoje
PASS  v2.2.0 bootstrap device catalog order i notification rute postoje
PASS  v2.2.0 bootstrap vraća permissions features i app policy
PASS  v2.2.0 uređaji deduplikuju push tokene i podržavaju opoziv
PASS  v2.2.0 API greške imaju stabilan envelope
PASS  v2.2.0 OpenAPI i Stable doctor su povezani

Ukupno: 983, neuspešno: 0
CMS_STATIC_CHECK=PASS

============================================================
9. FINAL v0.8 FOUNDATION AUDIT
============================================================
V0_7_RELEASE_STATUS=100_PERCENT_DEVICE_ACCEPTED_BY_USER
V0_8_IMPLEMENTATION_PROGRESS=5_PERCENT_FOUNDATION_AUDIT_COMPLETE
PRODUCT_STATUS_LAGER_AUDIT=COLLECTED
DISK_DUPLICATION_AUDIT=COLLECTED
PURCHASE_PRICE_AND_KPI_AUDIT=COLLECTED
DEFERRED_PAYMENT_RECEIVABLES_AUDIT=COLLECTED
WARRANTY_NOTIFICATION_SCOPE_AUDIT=COLLECTED
UNIVERSAL_DELETE_PRE_V1_FOOTPRINT=COLLECTED_DEFERRED_FROM_V0_8_CORE
SOURCE_WRITES_DURING_BATCH=0
DATABASE_WRITES_DURING_BATCH=0
MIGRATIONS_RUN=NO
DEPENDENCY_CHANGES=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
MOBILE_V0_8_FOUNDATION_READ_ONLY_AUDIT_BATCH1=PASS
MOBILE_V0_8_FOUNDATION_READ_ONLY_AUDIT_BATCH1_V2=PASS
NEXT_ACTION=PATCH_PRODUCT_STATUS_CONTROL_AND_REMOVE_DUPLICATE_DISK_STORAGE_CONTRACT_BATCH2
UPLOAD_THIS_REPORT_TO_CHAT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.8.0-FOUNDATION-READ-ONLY-AUDIT-BATCH1-V2-20260820-081346.md

PASS: MOBILE v0.8.0 FOUNDATION READ-ONLY AUDIT BATCH 1 V2 COMPLETE
