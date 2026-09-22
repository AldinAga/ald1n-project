
============================================================
240 - MOBILE v1.0 CATALOG EDIT HANDOFF PERFORMANCE + CONTRAST HOTFIX - BATCH 45 V2
============================================================
DATE=Thu Aug 27 08:48:12 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=RETRY_BATCH45_WITH_VALIDATOR_ALIGNED_TO_CERTIFIED_LIGHTWEIGHT_CATALOG_EDIT_HANDOFF
USER_DEVICE_EVIDENCE=ADMIN_EDIT_SMOOTH_WITH_MANY_IMAGES_CATALOG_PRODUCT_EDIT_PATH_JANKY
BATCH45_V1_FAILURE_CLASS=VALIDATOR_FALSE_NEGATIVE_AFTER_INTENTIONAL_NAVIGATION_CONTRACT_CHANGE
RUNTIME_HOTFIX_SCOPE=SAME_AS_BATCH45_V1
VALIDATOR_SCOPE=STRICTLY_ACCEPT_NEW_HANDOFF_TOPOLOGY_AND_REJECT_OLD_DIRECT_EDIT_REPLACE
REMOTE_IMAGE_MANAGER_MODIFIED=NO
SOURCE_MUTATION=YES_FIVE_MOBILE_PATHS_FOUR_RUNTIME_ONE_VALIDATOR
CMS_SOURCE_MUTATION=NO
HTACCESS_LOCAL_DRIFT_PRESERVED=YES_SEPARATE_REVIEW
DATABASE_WRITES_EXPECTED=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
DEPENDENCY_INSTALL=NO
DEPENDENCY_VERSION_CHANGES=0
EAS_BUILD_COMMANDS_RUN=0
EAS_SUBMIT_COMMANDS_RUN=0
APP_VERSION=1.0.0
CURRENT_CERTIFIED_ANDROID_VERSION_CODE=9
NEXT_EXPECTED_ANDROID_VERSION_CODE=10_ONLY_AFTER_BATCH45_V2_PASS
EXPECTED_PRE_HEAD=2508f77304dd50e04b7a8235b6d978631c1077e5
REPORT=/home/icaffeco/ald1n-project/docs/operations/240-MOBILE-V1.0-CATALOG-EDIT-HANDOFF-PERFORMANCE-CONTRAST-HOTFIX-BATCH45-V2-20260827-084812.md
BACKUP=/home/icaffeco/backups/releases/mobile-v1.0-catalog-edit-handoff-performance-contrast-hotfix-batch45-v2-20260827-084812
CONCURRENCY_LOCK=ACQUIRED

============================================================
0. HARD PRECONDITIONS + REPORT 239 FAILURE RECONCILIATION + HTACCESS PRESERVATION
============================================================
SOURCE_BRANCH=main
LOCAL_HEAD_PRE=2508f77304dd50e04b7a8235b6d978631c1077e5
REMOTE_HEAD_PRE=2508f77304dd50e04b7a8235b6d978631c1077e5
REMOTE_SYNC_PRE=PASS
BATCH45_V1_DOCS_CHECKPOINT_ALREADY_COMPLETED=2508f77304dd50e04b7a8235b6d978631c1077e5
TRACKED_WORKTREE_DIRTY_PATH_COUNT=1
TRACKED_WORKTREE_DIRTY_PATH=apps/cms/current/public/.htaccess
TRACKED_INDEX_DIRTY_PATH_COUNT=0
HTACCESS_DIFF_SHA256=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
HTACCESS_DRIFT_BACKUP=PASS_BYTE_COPY_AND_BINARY_DIFF
HTACCESS_DRIFT_POLICY=DO_NOT_RESET_DO_NOT_STAGE_DO_NOT_COMMIT
REPORT_239_SHA256=794d24c23f28f462cfcb65a26b3828bbe5e5e664ce5bdee6e004c2c712e24944
REPORT_239_TRAILING_WHITESPACE_LINES=0
REPORT_239_FAILURE_EVIDENCE=PASS_SINGLE_OUTDATED_V09_PRODUCT_DETAIL_VALIDATOR_CONTRACT
BATCH45_V1_SOURCE_COMMIT_CREATED=NO
BATCH45_V1_EAS_ACTIONS=ZERO
BUILD9_AAB_IMMUTABLE=PASS_2b9aef555b5b65cea25c27b96e4a1ef99c07b74b5605bfc8a0fb12febb6d5067
SOURCE_BLOB_PRECONDITIONS=PASS_EXACT_POST_BATCH45_V1_ROLLBACK_BASE
PRESTATE_UNTRACKED_ONLY_REPORT_239_PLUS_CURRENT_240=PASS
SOURCE_AND_REPORT_BACKUP=PASS
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
CANONICAL_NODE_PATH_PREPENDED=YES

============================================================
1. CHECKPOINT FAILED REPORT 239 ONLY
============================================================
STAGED_PATHS_EXACTLY_REPORT_239=PASS
REPORT_239_DIFF_CHECK=PASS
REPORT_239_SECRET_GUARD=PASS
HTACCESS_EXCLUDED_FROM_REPORT_239_COMMIT=PASS
REPORT_239_CHECKPOINT_COMMIT=1b3a1809edf3edc7a5f89a44f42959ada1064992
REPORT_239_CHECKPOINT_EXACT_PATHS=PASS_REPORT_239_ONLY
REPORT_239_CHECKPOINT_PUSH=PASS
REPORT_239_CHECKPOINT_REMOTE_SYNC=PASS

============================================================
2. APPLY SAME RUNTIME HOTFIX + STRICT VALIDATOR ALIGNMENT
============================================================
VALIDATOR_SYNTAX_CHECK=PASS
PRODUCT_DETAIL_EDIT_ENTRY=HANDOFF_TO_ADMIN_CATALOG_THEN_PUSH_EDIT
ADMIN_CATALOG_HANDOFF=LIGHTWEIGHT_QUERY_SUSPENDED_UNTIL_PRODUCT_DETAIL_IS_GONE
EDIT_FORM_SOURCE_MODIFIED=NO
REMOTE_PRODUCT_IMAGE_MANAGER_MODIFIED=NO
ADVANCED_ACTIONS_DARK_THEME_CONTRAST=FIX_EXPLICIT_INK_AND_MUTED_TOKENS
DIRECT_SALE_ROUTE_MODIFIED=NO
VALIDATOR_ALIGNMENT=STRICT_NEW_HANDOFF_CONTRACT_WITH_OLD_DIRECT_REPLACE_REJECTED
SOURCE_FILES_MODIFIED=5_MOBILE_PATHS_4_RUNTIME_1_VALIDATOR
PATCH_DIFF_CHECK=PASS

============================================================
3. FULL QUALITY GATES - NO EAS BUILD
============================================================

> ald1n-mobile@1.0.0 typecheck
> tsc --noEmit

MOBILE_TYPECHECK=PASS

> ald1n-mobile@1.0.0 validate
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
PASS src/app/(auth)/forgot-password.tsx postoji.
PASS src/app/(auth)/reset-password.tsx postoji.
PASS src/app/(auth)/activate-account.tsx postoji.
PASS src/app/(app)/(tabs)/home.tsx postoji.
PASS src/app/(app)/(tabs)/catalog.tsx postoji.
PASS src/app/(app)/(tabs)/orders.tsx postoji.
PASS src/app/(app)/(tabs)/notifications.tsx postoji.
PASS src/app/(app)/(tabs)/account.tsx postoji.
PASS src/app/(app)/product/[slug].tsx postoji.
PASS src/app/(app)/order/[id].tsx postoji.
PASS src/app/(app)/devices.tsx postoji.
PASS src/app/(app)/sessions.tsx postoji.
PASS src/app/(app)/portal/messages/index.tsx postoji.
PASS src/app/(app)/portal/messages/[id].tsx postoji.
PASS src/app/(app)/admin/customer-portal/index.tsx postoji.
PASS src/app/(app)/admin/customer-portal/[userId].tsx postoji.
PASS src/app/(app)/admin/customer-portal/conversations/[id].tsx postoji.
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
PASS src/features/admin/order-documents-admin.tsx postoji.
PASS src/features/admin/order-document-files.ts postoji.
PASS src/features/admin/product-deletion-admin.tsx postoji.
PASS src/features/admin/audit-admin-export.ts postoji.
PASS src/features/admin/module-settings-admin-api.ts postoji.
PASS src/features/portal/portal-api.ts postoji.
PASS src/features/admin/customer-portal-admin-api.ts postoji.
PASS src/features/admin/user-groups-admin-api.ts postoji.
PASS src/app/(app)/admin/user-groups/index.tsx postoji.
PASS src/features/admin/catalog-advanced-admin-api.ts postoji.
PASS src/features/admin/catalog-advanced-product-actions.tsx postoji.
PASS src/features/catalog/catalog-product-edit-handoff.ts postoji.
PASS src/features/admin/data-quality-admin-api.ts postoji.
PASS src/features/admin/data-quality-admin-export.ts postoji.
PASS src/app/(app)/admin/catalog/[id]/clone.tsx postoji.
PASS src/app/(app)/admin/catalog/bulk/index.tsx postoji.
PASS src/app/(app)/admin/catalog/data-quality/index.tsx postoji.
PASS src/features/admin/order-archive-admin.tsx postoji.
PASS src/app/(app)/admin/orders/archived.tsx postoji.
PASS src/features/admin/operational-reports-admin-export.ts postoji.
PASS src/features/admin/global-search-admin-api.ts postoji.
PASS src/app/(app)/admin/search.tsx postoji.
PASS src/app/(app)/admin/settings/modules/index.tsx postoji.
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
PASS Aplikaciona package verzija je 1.0.0.
PASS package-lock release verzija je 1.0.0.
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
PASS 175 TypeScript/TSX fajlova prolazi sintaksnu proveru.
PASS app.config.ts prolazi TypeScript sintaksnu proveru.
PASS 1278 lokalnih @/ importa je razrešeno.
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
PASS Expo app verzija je 1.0.0.
PASS Expo display naziv je Ald1n CMS bez Preview suffixa.
PASS Ald1n V2 logo je canonical icon/adaptive/splash/favicon asset.
PASS App runtime version fallback je 1.0.0.
PASS Account version fallback je 1.0.0.
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
PASS P3/v1.0 Admin System Health koristi relativni API ugovor i izlaže run/backup/prune mutacije kroz canonical servisni tok.
PASS P3 Admin System Health 2C zaključava centralizovani System Health query key.
PASS P3/v1.0 Admin System Health UI ostaje permission-gated i dodaje snapshot, backup, retention, backup istoriju i security događaje.
PASS v0.9 Admin Hub drži System Health u grupi Sistem samo kroz system.health dozvolu.
PASS OpenAPI dokumentuje puni v1.0 Admin System Health GET/run/backup/prune ugovor.
PASS P3/v1.0 Admin Audit zaključava relativni read-only list/detail/CSV Mobile API ugovor bez raw user_agent/context_json polja.
PASS v1.0 AUDIT-01 CSV koristi postojeći Bearer binary transport, privatni cache i Expo Sharing bez paralelnog fetch toka.
PASS P3 Admin Audit 2C zakljucava centralizovane Audit list/detail query key ugovore.
PASS P3/v1.0 Admin Audit zaključava security.view list/filter/pagination/refetch UI i server-driven audit.export CSV akciju.
PASS P3 Admin Audit 2C zakljucava permission-gated safe detail UI i server-driven read-only capabilities.
PASS P3 Admin Audit 2C zakljucava Admin hub ulaz samo za security.view.
PASS OpenAPI dokumentuje kompletan AUDIT-01 read/filter/detail + sanitizovani CSV export ugovor bez mutacija.
PASS v1.0 SET-01 Mobile API koristi relativni canonical GET/PUT module settings ugovor.
PASS v1.0 SET-01 ekran je SuperAdmin-only, server-driven i osvežava bootstrap/foundation bez destruktivnog ponašanja.
PASS v1.0 SET-01 Admin Hub poštuje server module visibility i izlaže Moduli sistema u organizovanoj Sistem grupi.
PASS v1.0 SET-01 TanStack query key je centralizovan.
PASS OpenAPI dokumentuje kompletan SET-01 read/update ugovor i SuperAdmin permission granicu.
PASS v1.0 AUTH-02/AUTH-03 Mobile API koristi guest recovery/activation ugovor bez paralelnog token sistema.
PASS v1.0 AUTH-02/AUTH-03 Mobile UI pokriva forgot/reset/activation i prihvata 80-char CMS recovery token.
PASS v1.0 ACCOUNT-02 Mobile UI pokriva aktivne API/web prijave, pojedinačni revoke i revoke-others uz current-session zaštitu.
PASS OpenAPI dokumentuje kompletan AUTH-02 + AUTH-03 + ACCOUNT-02 mobile parity ugovor.
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
PASS v0.9/v1.0 Admin Hub izlaže Šifarnike taxonomy administratorima i pretraga obuhvata Brendove.
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
PASS v1.0 Šifarnici ne vraćaju aktivni Product Variants contract.
PASS v1.0 Admin Orders API klijent pokriva issue/cancel/PDF document workflow.
PASS v1.0 Admin Order detalj ugrađuje permission-gated Poslovni dokumenti workbench bez orphan ekrana.
PASS v1.0 Mobile document workbench pokriva predračun, račun, otpremnicu, istoriju revizija i kontrolisano storniranje.
PASS v1.0 Admin dokument PDF koristi authenticated Bearer download, PDF signature proveru i privatni cache/share flow.
PASS v1.0 Admin dokumenti ostaju product-only bez Product Variants contracta.
PASS v1.0 Admin Catalog API pokriva server-driven deletion readiness, purge i Total Product Purge.
PASS v1.0 Mobile Product detalj ima postojeći archive/restore plus kontrolisani purge i SuperAdmin Total Product Purge danger-zone workflow.
PASS v1.0 Admin Catalog deletion API reuse-uje postojeće Laravel ProductDeletionService i TotalProductPurgeService ZERO TRACE guardove.
PASS OpenAPI dokumentuje ADMIN-CAT-03 deletion readiness, purge i Total Product Purge ugovor.
PASS v1.0 Admin Catalog purge tok ostaje product-only bez Product Variants contracta.

Ukupno FAIL: 0
PASS v1.0 System Health Mobile API pokriva snapshot, backup i retention mutacije relativnim canonical putanjama.
PASS v1.0 System Health API reuse-uje postojeće SystemHealthService i BackupService business guardove bez paralelne logike.
PASS v1.0 System Health Mobile state izlaže bezbednu backup/security istoriju bez privatnih backup putanja.
PASS v1.0 System Health ekran pokriva Web health/backup workflow uz kontrolisani retention confirm i repeatable-action contract.
PASS v1.0 System Health API rute imaju system.health/backups.manage i odgovarajuće write/backup throttle guardove.
PASS OpenAPI dokumentuje kompletan SET-03 System Health GET/run/backup/prune i safe history ugovor.
PASS v1.0 System Health parity ne vraća Product Variants contract.
PASS v1.0 PORTAL-01 Mobile pokriva customer inbox/create/detail/reply kroz relativni canonical API.
PASS v1.0 PORTAL-ADMIN-01 Mobile pokriva customer create/invite/order-link/session-revoke i conversation workflow.
PASS v1.0 Customer Portal API rute čuvaju customer ownership i admin permission/throttle granice.
PASS v1.0 Customer Portal Web i Mobile write workflow dele isti CustomerPortalAdminService authority.
PASS v1.0 Customer Portal je organizovan u Moje aktivnosti i Admin/Korisnici uz module visibility.
PASS OpenAPI dokumentuje PORTAL-01 i PORTAL-ADMIN-01 route surface i popravlja raniji purchase-cost/module-settings line-break drift.
PASS v1.0 Customer Portal parity ne vraća Product Variants contract.
PASS v1.0 USER-02 Mobile API pokriva User Groups list/create/update/delete relativni canonical ugovor.
PASS v1.0 USER-02 Mobile ekran pokriva permission, category scope, status, sort i bezbedni delete workflow.
PASS v1.0 USER-02 Admin Hub drži Grupe pristupa u organizovanoj Korisnici sekciji.
PASS v1.0 USER-02 TanStack query keys su centralizovani.
PASS v1.0 USER-02 API rute dele system.manage_users granicu i puni CRUD surface.
PASS v1.0 USER-02 Web i Mobile API dele isti UserGroupAdminService i AdminUserGroupRequest authority.
PASS v1.0 USER-02 shared servis čuva permission/category sync i blokira brisanje grupe sa korisnicima.
PASS OpenAPI dokumentuje kompletan USER-02 User Groups CRUD ugovor.
PASS v1.0 USER-02 parity ne vraća Product Variants contract.
PASS v1.0 glavni EUR/RSD authority je zakljucan na NBS Komercijalni prodajni kurs.
PASS Web i Mobile jasno oznacavaju Komercijalni prodajni kao glavni kurs.
PASS v1.0 ADMIN-CAT-04 Mobile pokriva clone, name preview i regenerate-name kroz canonical ProductAdmin/ProductTemplate authority.
PASS v1.0 ADMIN-CAT-05 Mobile pokriva bulk selection, preview i execute kroz postojeći ProductBulkService.
PASS v1.0 ADMIN-CAT-11 Mobile pokriva Data Quality audit, safe repair, history i JSON export.
PASS v1.0 CATALOG_ADVANCED catalog.manage_products API route surface je kompletan.
PASS v1.0 ADMIN-CAT-11 API čuva catalog.audit granicu i shared DataQualityService authority.
PASS v1.0 ADMIN-CAT-04/05 API reuse-uje postojeće Laravel catalog authority servise bez paralelne poslovne logike.
PASS v1.0 CATALOG_ADVANCED opcije ostaju organizovane u Katalog i lager Admin grupi.
PASS v1.0 CATALOG_ADVANCED TanStack query keys su centralizovani.
PASS OpenAPI dokumentuje kompletan CATALOG_ADVANCED route surface.
PASS v1.0 CATALOG_ADVANCED parity ne vraća Product Variants contract.
PASS v1.0 ADMIN-ORDER-02 Mobile API pokriva archived list, archive, restore i purge ugovor.
PASS v1.0 ADMIN-ORDER-02 Mobile UI pokriva arhivu, restore i SuperAdmin purge sa kontrolisanom potvrdom.
PASS v1.0 ADMIN-ORDER-02 ostaje organizovan u postojećem Prodaja/Porudžbine toku.
PASS v1.0 ADMIN-ORDER-02 API route surface je kompletan.
PASS v1.0 ADMIN-ORDER-02 reuse-uje canonical OrderArchiveService i čuva SuperAdmin-only purge.
PASS v1.0 REPORT-02 Mobile pokriva orders PDF/CSV, payments CSV i inventory CSV kroz secure Bearer download.
PASS v1.0 REPORT-02 API route surface je kompletan.
PASS v1.0 REPORT-02 Web i Mobile API dele isti OrderReportService export authority.
PASS v1.0 ORDER_REPORT_OPS TanStack query keys su centralizovani.
PASS OpenAPI dokumentuje kompletan ORDER_REPORT_OPS route surface.
PASS v1.0 ORDER_REPORT_OPS parity ne vraća Product Variants contract.
PASS v1.0 SYSTEM_SETTINGS file picker koristi Expo SDK57 literal overload za single/multiple izbor.
PASS v1.0 SET-02 Mobile pokriva automation settings, manual run i resolve alert kroz canonical automation authority.
PASS v1.0 SET-04 Mobile pokriva Turnstile bez izlaganja secret vrednosti.
PASS v1.0 SET-05 Mobile pokriva brending, footer i SuperAdmin login background/slideshow asset workflow.
PASS v1.0 SET-08 Mobile pokriva order e-mail settings, dispatch i retry workflow.
PASS v1.0 SET-09 Mobile pokriva poslovne dokumente i kontrolisani PDF logo lifecycle.
PASS v1.0 SET-10 Mobile pokriva Bank Accounts CRUD uz canonical server MOD97 validaciju.
PASS v1.0 SYSTEM_SETTINGS opcije su organizovane kroz jedan Sistem hub bez zagušenja glavne administracije.
PASS v1.0 SYSTEM_SETTINGS TanStack query keys su centralizovani.
PASS v1.0 SYSTEM_SETTINGS API route surface ima 20 kontrolisanih operacija sa permission/throttle granicama.
PASS v1.0 SYSTEM_SETTINGS API reuse-uje postojeće Web/service authority-je umesto paralelne poslovne logike.
PASS OpenAPI dokumentuje kompletan SYSTEM_SETTINGS route surface.
PASS v1.0 SYSTEM_SETTINGS parity ne vraća Product Variants contract.
PASS v1.0 CAT-02 Mobile API koristi relativni centralizovani global-search ugovor i tipizovane Mobile targete.
PASS v1.0 CAT-02 Mobile ekran pokriva debounce, grouped rezultate i navigaciju kroz server-driven Mobile target.
PASS v1.0 CAT-02 Global Search je organizovan kao jedna jasna Admin quick-action destinacija bez zagušenja poslovnih sekcija.
PASS v1.0 CAT-02 user rezultat otvara postojeći User Manager sa primenjenim q filterom.
PASS v1.0 CAT-02 TanStack query key je centralizovan.
PASS v1.0 CAT-02 API ruta je auth/active nasledjena i čuva postojeći Web search throttle.
PASS v1.0 CAT-02 Mobile API reuse-uje postojeći GlobalCommandSearchService authority i samo adaptira Web URL u Mobile target.
PASS OpenAPI dokumentuje kompletan CAT-02 permission-aware Global Search ugovor.
PASS v1.0 CAT-02 Global Search parity ne vraća Product Variants contract.
MOBILE_PROJECT_VALIDATOR=PASS_ZERO_FAIL
V09_PRODUCT_DETAIL_VALIDATOR=PASS_HANDOFF_AWARE
PASS light primary/onPrimary contrast 5.78:1
PASS dark primary/onPrimary contrast 7.88:1
PASS up-to-date apps/mobile/current/src/design/ald1n-tokens.generated.ts
PASS up-to-date packages/web-theme/ald1n-violet.css
DESIGN_TOKEN_CHECK=PASS

> ald1n-mobile@1.0.0 doctor
> expo-doctor

Running 20 checks on your project...
19/20 checks passed. 1 checks failed. Possible issues detected:
Use the --verbose flag to see more details about passed checks.

✖ Check that packages match versions required by installed Expo SDK

🔧 Patch version mismatches
package             expected  found    
expo                ~57.0.17  57.0.16  
expo-constants      ~57.0.15  57.0.14  
expo-dev-client     ~57.0.16  57.0.15  
expo-file-system    ~57.0.6   57.0.5   
expo-linking        ~57.0.8   57.0.7   
expo-notifications  ~57.0.15  57.0.14  
expo-router         ~57.0.17  57.0.16  
expo-secure-store   ~57.0.2   57.0.1   
expo-sharing        ~57.0.16  57.0.15  
expo-system-ui      ~57.0.3   57.0.2   
expo-updates        ~57.0.18  57.0.17  
react-native        0.86.3    0.86.2   

Changelogs:
- expo-constants → https://github.com/expo/expo/blob/sdk-57/packages/expo-constants/CHANGELOG.md
- expo-dev-client → https://github.com/expo/expo/blob/sdk-57/packages/expo-dev-client/CHANGELOG.md
- expo-file-system → https://github.com/expo/expo/blob/sdk-57/packages/expo-file-system/CHANGELOG.md
- expo-linking → https://github.com/expo/expo/blob/sdk-57/packages/expo-linking/CHANGELOG.md
- expo-notifications → https://github.com/expo/expo/blob/sdk-57/packages/expo-notifications/CHANGELOG.md
- expo-router → https://github.com/expo/expo/blob/sdk-57/packages/expo-router/CHANGELOG.md
- expo-secure-store → https://github.com/expo/expo/blob/sdk-57/packages/expo-secure-store/CHANGELOG.md
- expo-sharing → https://github.com/expo/expo/blob/sdk-57/packages/expo-sharing/CHANGELOG.md
- expo-system-ui → https://github.com/expo/expo/blob/sdk-57/packages/expo-system-ui/CHANGELOG.md
- expo-updates → https://github.com/expo/expo/blob/sdk-57/packages/expo-updates/CHANGELOG.md

12 packages out of date.
Advice:
Use 'npx expo install --check' to review and upgrade your dependencies.
To ignore specific packages, add them to "expo.install.exclude" in package.json. Learn more: https://expo.fyi/dependency-validation

1 check failed, indicating possible issues with the project.

============================================================
FAILURE
============================================================
ERROR=Unexpected shell error at line 153 (rc=1).
FAILED_STAGE=QUALITY_GATES
REPORT_239_CHECKPOINT_COMMIT=1b3a1809edf3edc7a5f89a44f42959ada1064992
HOTFIX_COMMIT=NONE
SOURCE_ROLLBACK_IF_UNCOMMITTED=ENABLED_MOBILE_ONLY
HTACCESS_AUTOMATIC_RESET=FORBIDDEN
EAS_BUILD_COMMANDS_RUN=0
EAS_SUBMIT_COMMANDS_RUN=0
BATCH45_V2_RESULT=FAIL
MOBILE_V1_0_CATALOG_EDIT_HANDOFF_PERFORMANCE_CONTRAST_HOTFIX_BATCH45_V2=FAIL
REPORT=/home/icaffeco/ald1n-project/docs/operations/240-MOBILE-V1.0-CATALOG-EDIT-HANDOFF-PERFORMANCE-CONTRAST-HOTFIX-BATCH45-V2-20260827-084812.md
