============================================================
157 - MOBILE v1.0 ADMIN CATALOG DICTIONARIES + NAV HUB + BRAND LINES PARITY - BATCH 22 V4
============================================================
DATE=Mon Aug 24 15:07:59 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=CLOSE_ADMIN_CAT_10_FULL_CATALOG_DICTIONARIES_PARITY_AND_EXPAND_TYPE_SCOPED_BRAND_LINES_WITH_SAFE_ADDITIVE_MASTER_DATA_ENRICHMENT
SOURCE_SCOPE=EXACT_22_PATHS
EXISTING_SOURCE_PATHS=14
NEW_SOURCE_PATHS=8
DATABASE_WRITES=ADDITIVE_BRAND_LINE_MASTER_DATA_ONLY_AFTER_ALL_SOURCE_GATES_PASS
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
PRODUCT_VARIANTS=DECOMMISSIONED_AND_FORBIDDEN
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
NAVIGATION=ADMINISTRATION_TO_CATALOG_AND_INVENTORY_TO_DICTIONARIES
BRAND_LINE_LIMIT=10_PER_BRAND_PER_PRODUCT_TYPE
BRAND_LINE_ENRICHMENT=EXISTING_BRANDS_AND_ALREADY_LINKED_PRODUCT_TYPES_ONLY_NO_DELETE_NO_RENAME
REPORT=/home/icaffeco/ald1n-project/docs/operations/158-MOBILE-V1.0-ADMIN-CATALOG-DICTIONARIES-NAV-HUB-BRAND-LINES-PARITY-BATCH22-V4-20260824-150759.md
BACKUP=/home/icaffeco/backups/releases/mobile-v1.0-admin-catalog-dictionaries-nav-hub-brand-lines-parity-batch22-v4-20260824-150759
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
REPORT_153_EVIDENCE=PASS_COMMIT_PUSH_SUCCEEDED_POST_CERT_FALSE_NEGATIVE
REPORT_154_FOUNDATION=PASS_GITHUB_CHECKPOINT_8baa290b2ae758d3733f5b8abddb7ff855897018
REPORT_155_FAILED_BATCH22_EVIDENCE=PASS_SOURCE_PATCH_ANCHOR_FAILURE_FULL_ROLLBACK
REPORT_156_FAILED_BATCH22_V2_EVIDENCE=PASS_STALE_ADMIN_HUB_BRAND_SMOKE_FULL_ROLLBACK_ZERO_DB_WRITES
REPORT_157_FAILED_BATCH22_V3_EVIDENCE=PASS_TYPESCRIPT_STRICTNESS_FULL_ROLLBACK_ZERO_DB_WRITES
HARD_PRECONDITIONS=PASS
SOURCE_HEAD=8baa290b2ae758d3733f5b8abddb7ff855897018
PRESTATE_TRACKED_WORKTREE=CLEAN
PRESTATE_REAL_GIT_INDEX=CLEAN
PRESTATE_UNTRACKED_EVIDENCE=PASS_REPORTS_153_154_155_156_157_PLUS_CURRENT_158
PASS Backup je kreiran: /home/icaffeco/backups/current/20260824-150802-manual-8883c8
Veličina: 380,80 MB
Backup: /home/icaffeco/backups/current/20260824-150802-manual-8883c8
PASS Backup verzija: 2.2.0.
PASS Backup je svez: 0,0 h.
PASS SQL backup SHA-256 je validan.
PASS SQL gzip je citljiv; dekompresovano 2,16 MB.
PASS SQL backup sadrzi CREATE TABLE.
PASS SQL backup sadrzi migrations tabela.
PASS Verifikovani privatni backup fajlovi: 221/221.
PASS Backup evidencija i velicina direktorijuma su uskladjene: 380,80 MB.
Backup je logicki validan i spreman za kontrolisanu restore probu na odvojenoj bazi.
FULL_APPLICATION_BACKUP=PASS
FULL_APPLICATION_BACKUP_VERIFY=PASS
No syntax errors detected in /tmp/ald1n-batch22-v4.dodmoE/brand-line-prestate.php
BRAND_LINE_DATABASE_PRESTATE_BACKUP=PASS
BRAND_LINE_DATABASE_PRESTATE_SHA256=f18f93b9ccd47445e2ebf7cb96ba1d93681079c12a8a05346d9cbaa627a51ba7
ROLLBACK_NOTE=PASS_CONTROLLED_RESTORE_ONLY
TARGETED_SOURCE_BACKUP=PASS_14_EXISTING_PATHS
BATCH22_EXISTING_PATCH=PASS
SOURCE_PATCH=PASS
SHARED_DICTIONARY_BUSINESS_LOGIC=PATCHED_WEB_AND_API_TO_MANAGER
SOURCE_PATCH=PASS_EXACT_22_PATHS
NEW_SOURCE_PATHS=PASS_8_CREATED
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/CatalogDictionaryManagerService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/BrandLineCatalogService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/BrandManagerService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/CatalogDictionaryRequest.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Requests/BrandManagerRequest.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogDictionaryController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/CatalogDictionaryController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/bin/brand-manager-v0.9-smoke.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/routes/api.php
PASS BrandManagerService exists with marker
PASS BrandManagerRequest exists with taxonomy authorization
PASS Web BrandManagerController exists
PASS API CatalogBrandController exists
PASS Specialized Laravel Brand Manager routes exist
PASS API Brand Manager routes use taxonomy permission
PASS Laravel Brand Manager view has filter/add/compact table
PASS Service uses existing type-scoped pivots
PASS Service protects historical product relations
PASS Service caps curated line input to ten per type
PASS Mobile Brand Manager API is relative and centralized
PASS Mobile Brand Manager screen is taxonomy permission gated
PASS Mobile dictionaries hierarchy exposes canonical Brand Manager
PASS Mobile Brand Manager query keys exist
PASS Canonical OpenAPI documents Brand Manager
PASS Brand Manager implementation contains no Product Variants contract
BRAND_MANAGER_SMOKE_FAIL_COUNT=0
BRAND_MANAGER_SMOKE=PASS
BRAND_MANAGER_SMOKE=PASS
TARGETED_TYPESCRIPT_SYNTAX=PASS
TARGETED_SYNTAX=PASS
PRODUCTION_ROUTE_CACHE_IMMUTABLE=PASS
DICTIONARY_FRESH_ROUTE_DIAGNOSTICS=[{"method":"PATCH","uri":"api/v1/admin/catalog/dictionaries/brands/reorder","name":"api.v1.admin.catalog.dictionaries.brands.reorder"},{"method":"GET|HEAD","uri":"api/v1/admin/catalog/dictionaries/product-types/{productType}","name":"api.v1.admin.catalog.dictionaries.product-types.show"},{"method":"PATCH","uri":"api/v1/admin/catalog/dictionaries/product-types/{productType}/fields/reorder","name":"api.v1.admin.catalog.dictionaries.product-types.fields.reorder"},{"method":"DELETE","uri":"api/v1/admin/catalog/dictionaries/specification-fields/{item}/purge","name":"api.v1.admin.catalog.dictionaries.specification-fields.purge"},{"method":"GET|HEAD","uri":"api/v1/admin/catalog/dictionaries/{resource}","name":"api.v1.admin.catalog.dictionaries.index"},{"method":"POST","uri":"api/v1/admin/catalog/dictionaries/{resource}","name":"api.v1.admin.catalog.dictionaries.store"},{"method":"PATCH","uri":"api/v1/admin/catalog/dictionaries/{resource}/reorder","name":"api.v1.admin.catalog.dictionaries.reorder"},{"method":"PUT","uri":"api/v1/admin/catalog/dictionaries/{resource}/{item}","name":"api.v1.admin.catalog.dictionaries.update"},{"method":"DELETE","uri":"api/v1/admin/catalog/dictionaries/{resource}/{item}","name":"api.v1.admin.catalog.dictionaries.destroy"}]
API_DICTIONARY_ROUTE_CONTRACT=PASS_9_FRESH_SOURCE_ROUTES_WEB_ROUTES_PRESERVED
API_DICTIONARY_ROUTE_CONTRACT=PASS_9_FRESH_SOURCE_ROUTES_WEB_ROUTES_PRESERVED
SHARED_DICTIONARY_BUSINESS_LOGIC=PASS_CATALOG_DICTIONARY_MANAGER_SERVICE_WEB_AND_API
WEB_DICTIONARY_CONTROLLER=PASS_DELEGATES_TO_SHARED_MANAGER
BRAND_MANAGER_REUSE=PASS_EXISTING_CANONICAL_DESTINATION_WITH_SHARED_REORDER_NO_DUPLICATE
MOBILE_DICTIONARIES_HUB=PASS
MOBILE_CATEGORIES_PARITY=PASS_CREATE_UPDATE_DEACTIVATE_REORDER
MOBILE_PRODUCT_LINES_PARITY=PASS_CREATE_UPDATE_DEACTIVATE_REORDER
MOBILE_PRODUCT_TYPES_PARITY=PASS_CREATE_UPDATE_DEACTIVATE_REORDER_FULL_FIELD_CONFIG
MOBILE_SPECIFICATION_FIELDS_PARITY=PASS_CREATE_UPDATE_DEACTIVATE_REORDER_DEPENDENCIES_PURGE
SPECIFICATION_FIELD_PURGE=PASS_SHARED_LIFECYCLE_EXACT_NAME_CONFIRMATION
ADMIN_NAVIGATION_PLACEMENT=PASS
NO_ORPHAN_MOBILE_ADMIN_SCREEN=PASS
NO_DUPLICATE_ADMIN_DESTINATION=PASS
PERMISSION_GATED_NAVIGATION=PASS
BRAND_LINE_LIMIT=PASS_10_PER_PRODUCT_TYPE
BRAND_LINE_CATALOG_SOURCE=PASS_DELL_HP_LENOVO_PLUS_RELEVANT_EXISTING_BRANDS
BRAND_LINE_COMPONENT_FAMILIES=PASS_LAPTOP_DESKTOP_STORAGE_RAM_MONITOR_GPU_MOTHERBOARD_CPU_PSU_CASE_COOLING_KEYBOARD_MOUSE_HEADSET
SHARED_DICTIONARY_BUSINESS_LOGIC=PASS_CATALOG_DICTIONARY_MANAGER_SERVICE_WEB_AND_API
WEB_DICTIONARY_CONTROLLER=PASS_DELEGATES_TO_SHARED_MANAGER
BRAND_MANAGER_REUSE=PASS_EXISTING_CANONICAL_DESTINATION_WITH_SHARED_REORDER_NO_DUPLICATE
MOBILE_DICTIONARIES_HUB=PASS
MOBILE_CATEGORIES_PARITY=PASS_CREATE_UPDATE_DEACTIVATE_REORDER
MOBILE_PRODUCT_LINES_PARITY=PASS_CREATE_UPDATE_DEACTIVATE_REORDER
MOBILE_PRODUCT_TYPES_PARITY=PASS_CREATE_UPDATE_DEACTIVATE_REORDER_FULL_FIELD_CONFIG
MOBILE_SPECIFICATION_FIELDS_PARITY=PASS_CREATE_UPDATE_DEACTIVATE_REORDER_DEPENDENCIES_PURGE
SPECIFICATION_FIELD_PURGE=PASS_SHARED_LIFECYCLE_EXACT_NAME_CONFIRMATION
ADMIN_NAVIGATION_PLACEMENT=PASS
NO_ORPHAN_MOBILE_ADMIN_SCREEN=PASS
NO_DUPLICATE_ADMIN_DESTINATION=PASS
PERMISSION_GATED_NAVIGATION=PASS
BRAND_LINE_LIMIT=PASS_10_PER_PRODUCT_TYPE
BRAND_LINE_CATALOG_SOURCE=PASS_DELL_HP_LENOVO_PLUS_RELEVANT_EXISTING_BRANDS
BRAND_LINE_COMPONENT_FAMILIES=PASS_LAPTOP_DESKTOP_STORAGE_RAM_MONITOR_GPU_MOTHERBOARD_CPU_PSU_CASE_COOLING_KEYBOARD_MOUSE_HEADSET
TYPESCRIPT_STRICT_REORDER_GUARDS=PASS_THREE_MOBILE_SCREENS_NO_UNDEFINED_SWAP
PRODUCT_TYPE_DATA_NARROWING=PASS_CAPTURED_NON_OPTIONAL_LOADED_DATA
OPENAPI_BRAND_LINE_LIMIT=PASS_10_TWO_WRITE_SCHEMAS
OPENAPI_PARITY=PASS_3_COPIES
OPENAPI_ADMIN_CAT_10=PASS_FULL_ROUTE_SURFACE
OPENAPI_BRAND_LINE_LIMIT=PASS_10_TWO_WRITE_SCHEMAS

> ald1n-mobile@0.9.0 typecheck
> tsc --noEmit

MOBILE_TYPECHECK=PASS

> ald1n-mobile@0.9.0 validate
> node scripts/validate-project.mjs

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
PASS src/features/admin/dictionary-admin-api.ts postoji.
PASS src/app/(app)/admin/catalog/dictionaries/index.tsx postoji.
PASS src/app/(app)/admin/catalog/dictionaries/[resource].tsx postoji.
PASS src/app/(app)/admin/catalog/dictionaries/product-types/[id].tsx postoji.
PASS tamagui.config.ts postoji.
PASS src/design/ald1n-tokens.generated.ts postoji.
PASS Generated design token fajlovi su sinhronizovani sa canonical JSON source-om.
PASS Tamagui onBrand koristi canonical onPrimary semantic token.
PASS package.json je validan JSON.
PASS eas.json je validan JSON.
PASS Expo SDK 57 verzija prati aktuelni SDK 57 patch baseline.
PASS React Native verzija prati Expo SDK 57 template.
PASS Expo Router verzija je zaključana.
PASS Expo development client je uključen.
PASS SecureStore zavisnost postoji.
PASS TanStack Query zavisnost postoji.
PASS Minimalna Node.js verzija odgovara SDK 57 zahtevu.
PASS Aplikaciona package verzija je 0.9.0.
PASS package-lock release verzija je 0.9.0.
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
PASS v0.8 Expo compatibility matrix zaključava expo na ~57.0.16.
PASS v0.8 Expo compatibility matrix zaključava expo-constants na ~57.0.14.
PASS v0.8 Expo compatibility matrix zaključava expo-crypto na ~57.0.2.
PASS v0.8 Expo compatibility matrix zaključava expo-dev-client na ~57.0.15.
PASS v0.8 Expo compatibility matrix zaključava expo-file-system na ~57.0.5.
PASS v0.8 Expo compatibility matrix zaključava expo-linking na ~57.0.7.
PASS v0.8 Expo compatibility matrix zaključava expo-notifications na ~57.0.14.
PASS v0.8 Expo compatibility matrix zaključava expo-router na ~57.0.16.
PASS v0.8 Expo compatibility matrix zaključava expo-sharing na ~57.0.15.
PASS v0.8 Expo compatibility matrix zaključava expo-splash-screen na ~57.0.8.
PASS v0.8 Expo compatibility matrix zaključava expo-updates na ~57.0.17.
PASS Static colors consumeri su uklonjeni iz aplikacionog source-a.
PASS Legacy colors.* usage ne postoji van RN theme adaptera.
PASS Unsafe as never / as unknown as castovi ne postoje u source-u.
PASS 135 TypeScript/TSX fajlova prolazi sintaksnu proveru.
PASS app.config.ts prolazi TypeScript sintaksnu proveru.
PASS 952 lokalnih @/ importa je razrešeno.
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
PASS v0.9 Orders ekran otvara Dodeljene porudžbine samo korisniku sa orders.manage dozvolom.
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
PASS v0.9 After-sales prečica je uklonjena iz Porudžbina i premeštena u Moje aktivnosti.
PASS Warranty API tipovi pokrivaju listu, detalj i maintenance timeline.
PASS API klijent sadrži Warranty list/detail ugovor.
PASS Warranty lista koristi API, permission gate, detail rutu i maintenance summary.
PASS Warranty detalj prikazuje customer-safe garantni list, uslove, serijske brojeve, status i maintenance timeline.
PASS Warranty PDF se preuzima Bearer transportom, validira kao PDF i čuva u provereni privatni cache.
PASS Warranty PDF koristi postojeći Expo Sharing tek nakon platform/device provere.
PASS Warranty detalj otvara privatni PDF kroz bezbedan Bearer/cache/share flow bez direktnog URL-a.
PASS v0.9 Warranty prečica je uklonjena iz Porudžbina i premeštena u Moje aktivnosti.
PASS Commission API tipovi pokrivaju customer list/detail, statuse, summary i pagination ugovor.
PASS API klijent sadrži Commission list/filter/detail ugovor.
PASS Commission Mobile contract ne izlaže admin actor/history/payment-batch interne identifikatore.
PASS Commission lista koristi customer permission, q/status/date filtere, server summary, pagination i detail rutu.
PASS Commission detalj prikazuje customer-safe obračun, status, napomenu, isplatu i link ka porudžbini.
PASS v0.9 Commission prečica je uklonjena iz Porudžbina i premeštena u Moje aktivnosti.
PASS Commission customer UI ne izlaže admin/interne workflow identifikatore ili akcije.
PASS Lokalna korpa koristi samo proizvod i količinu; variant identitet je dekomisioniran.
PASS Korpa se čisti pri odjavi/promeni korisnika.
PASS Mobile API tipovi više ne izlažu Product Variants.
PASS Mobile Product detalj više nema variant izbor.
PASS Mobile checkout šalje samo product_id i quantity.
PASS Admin After-sales Mobile contract više ne izlaže product_variant_id.
PASS Checkout čuva stabilan idempotency ključ za retry istog payload-a.
PASS Checkout podržava uslovni izbor računa za bank transfer.
PASS v0.8 Mobile API tipovi pokrivaju Odloženo plaćanje i datum dospeća.
PASS v0.8 Checkout prikazuje i šalje datum dospeća samo za Odloženo plaćanje.
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
PASS Expo app verzija je 0.9.0.
PASS Expo display naziv je Ald1n CMS bez Preview suffixa.
PASS Ald1n V2 logo je canonical icon/adaptive/splash/favicon asset.
PASS App runtime version fallback je 0.9.0.
PASS Account version fallback je 0.9.0.
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
PASS v0.8 Product Edit izlaže SuperAdmin Evidentiraj prodaju direktno sa artikla.
PASS v0.8 Direct Sale ekran koristi server options, stable idempotency i unrestricted tap contract.
PASS v0.8 Admin Catalog API klijent pokriva Direct Sale options i record ugovor.
PASS OpenAPI dokumentuje SuperAdmin Direct Sale options/record i idempotency ugovor.
PASS v1.0 Direct Sale dozvoljava cenu iznad kataloške uz pozitivnu cenu i SuperAdmin workflow.
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
PASS v0.9 Admin Hub drži System Health u grupi Sistem samo kroz system.health dozvolu.
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
PASS v0.9 Home izlaže Moje provizije kroz Moje aktivnosti i view-own permission model.
PASS v0.9 Home zadržava Brze akcije pre sekcije Moje aktivnosti.
PASS v0.9 Admin Hub drži Provizije u grupisanoj sekciji Prodaja.
PASS v0.9 korisničke Moje provizije ostaju dostupne kroz Home Moje aktivnosti i view-own list/detail tok.
PASS v0.7 Admin Provizije zadržavaju list/detail/bulk-pay/export/status workflow.
PASS v0.7 Provizije koriste postojeći Admin API i secure export bez paralelne logike.
PASS v0.7 release-critical Provizije ostaju vezane za kompletan canonical Admin OpenAPI surface.
PASS v1.0 Commission policy koristi automatskih 10 procenata bez plafona i SuperAdmin-gated ručni unos bez policy disclosure-a.
PASS v0.8 Shipment UI koristi centralni courier izbor, tracking URL i canonical courier_service_id.
PASS v0.8 Courier Directory Mobile API pokriva list/create/update bez delete workflow-a.
PASS v0.8 Courier Directory UI je SuperAdmin-only i uređuje HTTPS tracking, status, default i redosled.
PASS v0.8 Admin Hub izlaže centralni Courier Directory SuperAdministratoru.
PASS OpenAPI dokumentuje centralni Courier Directory list/create/update ugovor.
PASS v0.8 Admin User request deli Laravel permission, unique identitet i 12-char password contract.
PASS v0.8 centralni AdminUserService opoziva tokene, auditira izmene i štiti poslednjeg aktivnog SuperAdmina.
PASS v0.8 User Management API pokriva list/options/detail/create/update bez delete workflow-a.
PASS v0.8 Mobile User API pokriva kompletan Laravel User Manager bez hard delete-a.
PASS v0.8 shared User form pokriva identitet, ulogu, grupu, status i password management.
PASS v0.8 User Management UI ima permission-gated list/create/edit i self-password reauthentication.
PASS v0.8 User Management query keys i Admin Hub entry su centralizovani.
PASS OpenAPI dokumentuje kompletan Admin User list/options/detail/create/update ugovor.
PASS v0.8 Exchange Rate API koristi centralni ExchangeRateService i 50 zapisa istorije.
PASS v0.8 Mobile Exchange Rate API pokriva state, manual, automatic i refresh ugovor.
PASS v0.8 Exchange Rate ekran ima permission-gated manual/automatic/refresh/history UX.
PASS v0.8 Exchange Rate UI koristi canonical API client bez paralelnog fetch toka.
PASS v0.8 Exchange Rate query key i Admin Hub entry su centralizovani.
PASS OpenAPI dokumentuje kompletan EUR/RSD Admin contract.
PASS v0.9 Brand Manager koristi relativni centralizovani API ugovor sa type-scoped brand/line podacima.
PASS v0.9 Mobile Brand Manager je permission-gated i pokriva globalni filter/search/add/edit/type/line UX bez Product Variants.
FAIL v0.9 Admin Hub izlaže Brand Manager samo taxonomy administratorima.
PASS v0.9 Brand Manager koristi centralizovane TanStack query keys.
PASS v0.9 OpenAPI dokumentuje globalni Brand Manager read/create/update/options ugovor i taxonomy permission.
PASS v0.9 Home prikazuje dve SuperAdmin inventory valuation pločice ispod postojeća četiri KPI-ja i pre Finansijskog pulsa.
PASS v0.9 Home inventory KPI koristi postojeći centralizovani Admin Foundation valuation contract.
PASS v0.9 Product detalj prikazuje server proviziju, SuperAdmin Direct Sale i Uredi artikal kao poslednju admin akciju.
PASS v0.9 Catalog kartica prikazuje server obračunatu proviziju.
PASS v0.9 Product detail/catalog commission tok ostaje product-only bez Product Variants.
PASS v0.9 Direct Sale deferred tok ostavlja finansijski saldo otvoren i koristi postojeći Receivables plan.
PASS v0.9 deferred Direct Sale dozvoljava payment lifecycle, blokira ad-hoc refund i čuva canonical after-sales refund.
PASS v0.9 Direct Sale API validira odloženo plaćanje, 1–24 rate i konačni datum.
PASS v0.9 Mobile Direct Sale API ugovor sadrži deferred payment polja.
PASS v0.9 Direct Sale ekran prikazuje uslovni plan rata i konačni datum pune isplate.
PASS OpenAPI dokumentuje deferred Direct Sale payment metodu, rate i konačni datum.
PASS v0.9 Direct Sale deferred tok ne vraća Product Variants.
PASS v0.9 Bottom navigation ostaje Početna, Katalog, Porudžbine, Obaveštenja, Nalog.
PASS v0.9 Home prati Fokus danas > Brze akcije > Moje aktivnosti > Administracija hijerarhiju.
PASS v0.9 Moje aktivnosti centralizuju porudžbine, provizije, garancije i postprodaju.
PASS v0.9 Porudžbine prikazuju Moje i Dodeljene bez cross-feature prečica.
PASS v0.9 Admin Hub je permission-filtered, pretraživ i grupisan u šest poslovnih sekcija.
PASS v0.9 Nalog prati Profil > Bezbednost > Obaveštenja > Uređaji > Odjava redosled.
PASS v0.9 Navigation reorganizacija ne vraća Product Variants.
PASS v1.0 Šifarnici hub je permission-gated i vodi na svih pet canonical destinacija bez duplog Brand Managera.
PASS v1.0 Dictionary API koristi relativni centralizovani CRUD/reorder/purge/product-type ugovor.
PASS v1.0 Mobile šifarnici pokrivaju create/update/deactivate/reorder i bezbedni specification purge sa korelacijama.
PASS v1.0 Product Type detalj pokriva kompletan CMS field/completeness/name-template i reorder ugovor.
PASS v1.0 postojeći Global Brand Manager ostaje canonical CRUD ekran i dobija shared reorder bez duplog odredišta.
PASS v1.0 Brand Manager podržava do deset type-scoped linija i dinamički Mobile add/remove editor.
PASS v1.0 Admin Hub postavlja Šifarnike u Katalog i lager i pretraga nalazi ugnježdene opcije.
PASS v1.0 Dictionary TanStack query keys su centralizovani.
PASS OpenAPI dokumentuje kompletan ADMIN-CAT-10 dictionary route surface.
FAIL v1.0 Šifarnici ne vraćaju Product Variants.

Ukupno FAIL: 2
FAIL_STAGE=MOBILE_QUALITY_GATES
FAIL: Mobile validator failed
SOURCE_ROLLBACK=EXECUTED_TO_PRE_BATCH22_CHECKPOINT_STATE
DATABASE_WRITES_COMMITTED=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
EXIT_CODE=2
