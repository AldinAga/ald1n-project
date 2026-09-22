
============================================================
473 - BATCH173 ADVANCED ANALYTICS MOBILE UI + CUSTOMER360 PROFITABILITY
============================================================
TIMESTAMP=20260922-142555
EXPECTED_HEAD=1f1dbd80b978592e322f00a0d005187676f0d2c3
PREDECESSOR_REPORT=472
TASK=BATCH173_ADVANCED_ANALYTICS_MOBILE_UI_CUSTOMER360_PROFITABILITY_INTEGRATION
LARAVEL_MUTATION=NO
OPENAPI_MUTATION=NO
DATABASE_MUTATION=NO
MOBILE_SOURCE_SCOPE=REPORTS_UI_CUSTOMER360_DETAIL_VALIDATOR_ONLY
EAS_COMMANDS_RUN=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO

============================================================
RUN - lint_patch-validator
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php -l /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/patch-validator.php 
No syntax errors detected in /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/patch-validator.php
RC_lint_patch-validator=0

============================================================
RUN - lint_patch-reports
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php -l /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/patch-reports.php 
No syntax errors detected in /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/patch-reports.php
RC_lint_patch-reports=0

============================================================
RUN - lint_patch-customer
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php -l /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/patch-customer.php 
No syntax errors detected in /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/patch-customer.php
RC_lint_patch-customer=0

============================================================
RUN - lint_backup-count
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php -l /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/backup-count.php 
No syntax errors detected in /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/backup-count.php
RC_lint_backup-count=0

============================================================
RUN - lint_business-counts
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php -l /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/business-counts.php 
No syntax errors detected in /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/business-counts.php
RC_lint_business-counts=0

============================================================
RUN - lint_parse-openapi
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node --check /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/parse-openapi.cjs 
RC_lint_parse-openapi=0

============================================================
1. PREFLIGHT AUTHORITY AND EXACT REPORT472 STATE
============================================================

============================================================
RUN - git_fetch_preflight
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main 
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch_preflight=0
BRANCH=main
LOCAL_HEAD=1f1dbd80b978592e322f00a0d005187676f0d2c3
REMOTE_HEAD=1f1dbd80b978592e322f00a0d005187676f0d2c3
REPORT472_SHA_ACTUAL=d1b5c7caee1cbbb1f0e400a104192d5979b3fd1bbd0871637e50ea31920b1512
REPORT472_SHA_EXPECTED=d1b5c7caee1cbbb1f0e400a104192d5979b3fd1bbd0871637e50ea31920b1512
REPORT472_CERTIFICATION=PASS_EXACT
PREFLIGHT_STAGED_MANIFEST=PASS_EXACT
PREFLIGHT_TRACKED_MANIFEST=PASS_EXACT
PREFLIGHT_UNTRACKED_MANIFEST=PASS_EXACT
HTACCESS_SHA_ACTUAL=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef
HTACCESS_DIFF_SHA_ACTUAL=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
OPENAPI_PKG_SHA_PREFLIGHT=53f45180ef04d7e60e6caaf65cdea595c0206b430eb2599cfcd747a708861d56
OPENAPI_CMS_SHA_PREFLIGHT=53f45180ef04d7e60e6caaf65cdea595c0206b430eb2599cfcd747a708861d56
OPENAPI_MOBILE_SHA_PREFLIGHT=53f45180ef04d7e60e6caaf65cdea595c0206b430eb2599cfcd747a708861d56
BATCH173_SOURCE_PRECONDITION=PASS_ABSENT

============================================================
RUN - openapi_real_parse_preflight
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/parse-openapi.cjs /home/icaffeco/ald1n-project/apps/mobile/current /home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml 
OPENAPI_PARSE=PASS
RC_openapi_real_parse_preflight=0

============================================================
RUN - advanced_smoke_preflight
============================================================
CWD=/home/icaffeco/ald1n-project/apps/cms/current
COMMAND=php bin/advanced-analytics-contract-smoke.php 
PASS ManagementReportService source exists
PASS canonical management report route exists
PASS Mobile reports API authority exists
PASS central management report query key exists
PASS advanced reports source keeps Product Variants decommissioned
PASS no parallel analytics route namespace exists
PASS ManagementReportService exposes advancedAnalytics
PASS ManagementReportService exposes previous-period comparison
PASS ManagementReportService exposes customer profitability and LTV rows
PASS ManagementReportService exposes sales-channel profitability
PASS ManagementReportService exposes top and bottom product profitability
PASS ManagementReportService exposes inventory efficiency
PASS build payload includes advanced_analytics
PASS normalized filters include customer_user_id
PASS order query applies explicit customer_user_id filter
PASS OpenAPI Batch172 structure is normalized for canonical YAML validator
PASS OpenAPI documents advanced analytics schema
PASS OpenAPI documents customer_user_id filter
PASS Mobile API types include advanced analytics
PASS Mobile request contract includes customer_user_id
PASS Mobile query keys include Customer360 profitability handoff
PASS Mobile validator pins Batch172 contract parity
PASS live report build returns advanced_analytics object
PASS comparison revenue metric uses canonical delta shape
PASS customer profitability payload is an array
PASS sales-channel profitability payload is an array
PASS product profitability exposes top and bottom arrays
PASS inventory efficiency is explicitly identified as current-inventory proxy
ADVANCED_ANALYTICS_CONTRACT_SMOKE=28_CHECKS_28_PASS_0_FAIL
RC_advanced_smoke_preflight=0

============================================================
RUN - mobile_validator_preflight
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node scripts/validate-project.mjs 
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
PASS src/app/(app)/account/profile.tsx postoji.
PASS src/app/(app)/account/security.tsx postoji.
PASS src/app/(app)/account/preferences.tsx postoji.
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
PASS src/components/ui/operator-row.tsx postoji.
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
PASS v0.8 Expo compatibility matrix zaključava expo na ~57.0.21.
PASS v0.8 Expo compatibility matrix zaključava expo-constants na ~57.0.17.
PASS v0.8 Expo compatibility matrix zaključava expo-crypto na ~57.0.2.
PASS v0.8 Expo compatibility matrix zaključava expo-dev-client na ~57.0.18.
PASS v0.8 Expo compatibility matrix zaključava expo-file-system na ~57.0.6.
PASS v0.8 Expo compatibility matrix zaključava expo-font na ~57.0.3.
PASS v0.8 Expo compatibility matrix zaključava expo-linking na ~57.0.9.
PASS v0.8 Expo compatibility matrix zaključava expo-notifications na ~57.0.17.
PASS v0.8 Expo compatibility matrix zaključava expo-router na ~57.0.20.
PASS v0.8 Expo compatibility matrix zaključava expo-sharing na ~57.0.18.
PASS v0.8 Expo compatibility matrix zaključava expo-secure-store na ~57.0.3.
PASS v0.8 Expo compatibility matrix zaključava expo-system-ui na ~57.0.3.
PASS v0.8 Expo compatibility matrix zaključava expo-splash-screen na ~57.0.8.
PASS v0.8 Expo compatibility matrix zaključava expo-updates na ~57.0.21.
PASS Static colors consumeri su uklonjeni iz aplikacionog source-a.
PASS Legacy colors.* usage ne postoji van RN theme adaptera.
PASS Unsafe as never / as unknown as castovi ne postoje u source-u.
PASS 184 TypeScript/TSX fajlova prolazi sintaksnu proveru.
PASS app.config.ts prolazi TypeScript sintaksnu proveru.
PASS 1340 lokalnih @/ importa je razrešeno.
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
PASS v1.0 Bottom navigation aktivni TAB koristi puni tonalni pill indikator za ikonicu i naziv.
PASS Tab badge koristi semantic danger/onDanger foreground par.
PASS UI koristi native Expo Symbols umesto tekstualnih pseudo-ikonica.
PASS Build16 Glyph koristi jednu native Symbols porodicu bez tekstualnih pseudo-fallback ikonica.
PASS Build16 OperatorRow koristi UI-thread press motion i reduced-motion ugovor.
PASS Build16 globalni Button koristi canonical operator radius i kontrolisani press feedback.
PASS Build16 globalni Card prati card-diet radius i suptilnu elevation hijerarhiju.
PASS Build16 Home akcije koriste jednu grupisanu operator površinu umesto card-per-row obrasca.
PASS Build16 Home koristi approved Operator welcome/focus hijerarhiju bez legacy gradient-orb/pill hero obrasca.
PASS Build16 Catalog koristi Operator search/filter hijerarhiju i postojeći server taxonomy filter authority.
PASS Build16 Catalog product row je kompaktan operator surface i čuva Batch116 thumbnail/cache contract.
PASS Build16 Catalog slika ima eksplicitne bounds i intrinsic dimenzije fotografije ne mogu da rastegnu product row.
PASS Build16 SelectSheet koristi native Glyph sistem bez tekstualnih pseudo-ikonica.
PASS Build16 Mobile catalog param contract izlaže postojeće CatalogQueryService taxonomy filtere bez novog backend toka.
PASS Build16 Product detail koristi Operator identity/commercial/section hijerarhiju, čuva Batch116 image authority i v0.9 Direct Sale > Uredi contract bez card-zoo/pseudo-back ikonice.
PASS Build16 Orders lista koristi Operator hijerarhiju i čuva v0.9 Moje/Dodeljene granicu bez cross-feature prečica.
PASS Build16 Order row je kompaktan Operator surface bez Card wrappera i koristi canonical AppColors shadow token.
PASS Build16 Order detail koristi Operator section hierarchy i čuva post-create, payment proof, documents, delivery, cancel i after-sales authority.
PASS Build16 Notifications koristi Operator inbox bez Card-per-row obrasca i čuva read/read-all + poslovni deep-link routing authority.
PASS Build16 Account koristi grupisane OperatorRow površine i čuva Profil > Bezbednost > Obaveštenja > Uređaji > Odjava + sessions/preferences authority.
PASS Build16 Cart koristi Operator list/summary hijerarhiju bez Card/pseudo-icon obrasca i čuva Batch116 image cache + product-only quantity/remove/clear/checkout tok.
PASS Build16 Checkout koristi Operator step hijerarhiju i čuva stable idempotency, bank transfer, deferred-payment, product-only create i success routing authority.
PASS Build16 Cart decrement koristi canonical Expo Symbols remove semantic bez tekstualnog pseudo-icon fallbacka.
PASS Build16 Admin Hub koristi OperatorRow grupisane akcije i čuva permission/module/inventory/global-search authority bez button-zoo obrasca.
PASS Build16 shared loading/empty/unavailable/error states koriste canonical radii, reduced-motion i postojeći retry/request-id contract.
PASS Build16 PageHeader zadržava notification badge/routing authority uz Operator control radius i restrained press feedback.
PASS Build16 Laravel final polish zaključava flat body, focus-visible, empty-state, control radius i reduced-motion presentation contract.
PASS Build16 native Expo palette and production EAS remote-version authority are release-locked.
PASS Build16 catalog product row preserves the already-committed bounded image geometry from 33604307.
PASS Build16 catalog refetches server authority whenever the tab regains focus.
PASS Build16 product detail refreshes stock/status authority on focus.
PASS Direct sale and catalog administration invalidate catalog list, detail and filter caches after mutation.
PASS Mobile product create is single-flight per active request and retries the same payload through server idempotency.
PASS Build17 galerija prati stvarni 4:3/3:4 odnos telefonske fotografije bez fiksnog letterbox image box-a.
PASS Build17 ima izolovan runtime 1.0.0-build17 pa OTA ne može slučajno targetirati Build15/Build16 runtime 1.0.0.
PASS Build16 canonical icon registry postoji.
PASS Build16 icon registry zaključava Android Symbols i aktivni Laravel Phosphor runtime authority.
PASS Canonical packages/api-contract/openapi.yaml postoji.
PASS Mobile OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.
PASS CMS OpenAPI kopija postoji.
PASS CMS OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.
PASS Batch172 recovery canonical OpenAPI YAML parsira kroz postojeci Node yaml toolchain i izlaže report/customer schemas i paths.
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
PASS v0.9/Batch151 Direct Sale ekran prikazuje custom plan rata, prvu ratu odmah i konačni datum pune isplate.
PASS OpenAPI dokumentuje deferred Direct Sale payment metodu, rate i konačni datum.
PASS v0.9 Direct Sale deferred tok ne vraća Product Variants.
PASS v0.9 Bottom navigation ostaje Početna, Katalog, Porudžbine, Obaveštenja, Nalog.
PASS v0.9 Home prati Fokus danas > Brze akcije > Moje aktivnosti > Administracija hijerarhiju.
PASS Build18 Home ne duplira primarne Porudžbine, a zadržava provizije, garancije i postprodaju.
PASS v0.9 Porudžbine prikazuju Moje i Dodeljene bez cross-feature prečica.
PASS v0.9 Admin Hub je permission-filtered, pretraživ i grupisan u šest poslovnih sekcija.
PASS v0.9 Nalog prati Profil > Bezbednost > Obaveštenja > Uređaji > Odjava redosled.
PASS v0.9 Navigation reorganizacija ne vraća Product Variants.
PASS Build18 koristi jedan canonical AppBottomNav i drži interni Expo Tabs bar skrivenim.
PASS Build18 Home uklanja duple primarne ulaze i pasivne prazne/status sekcije.
PASS Build18 Admin Hub zadržava permission-aware poslovne grupe bez pasivnih foundation kartica.
PASS v1.0 Batch50 V2 lokalna tema i valuta su per-user/per-device SecureStore preference bez server write-a.
PASS v1.0 Batch50 V2 Profil ima moderne single-choice Tema i Primarna valuta kontrole.
PASS v1.0 Batch50 V2 lokalna tema upravlja RN/Tamagui/StatusBar shell-om posle korisničke preference hidratacije.
PASS v1.0 Batch50 V2 bootstrap izlaže samo read-only presentation metadata postojećeg NBS authority-ja.
PASS v1.0 Batch50 V2 komercijalni prodajni authority ostaje centralan uz NBS javnu listu primary, Frankfurter secondary i poslednji sacuvani kurs emergency fallback.
PASS v1.0 Batch50 V2 primarna valuta je display-only; canonical RSD payment input i payload ostaju nepromenjeni.
PASS v1.0 Batch50 V2 globalni loading koristi branded Reanimated pulse i skeleton.
PASS v1.0 Batch50 V2 bottom nav drži centralno izdvojenu Početnu i role-aware Admin/Obaveštenja četvrti slot.
PASS v1.0 Batch50 V2 header desno koristi notification bell+badge umesto profila, a Nalog ostaje u bottom nav-u.
PASS v1.0 Batch50 V2 čuva Katalog active context za admin/catalog edit, dok ostali admin ekrani aktiviraju Admin slot.
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
PASS v1.0 Admin Orders PDF/shipment binary putanje su relativne i ne dupliraju /api/v1 prefiks.
PASS v1.0 Admin Order detalj ugrađuje permission-gated Poslovni dokumenti workbench bez orphan ekrana.
PASS v1.0 Mobile document workbench pokriva predračun, račun, otpremnicu, istoriju revizija i kontrolisano storniranje.
PASS v1.0 Admin dokument PDF koristi authenticated Bearer download, PDF signature proveru i privatni cache/share flow.
PASS v1.0 Admin dokumenti ostaju product-only bez Product Variants contracta.
PASS v1.0 Admin Catalog API pokriva server-driven deletion readiness, purge i Total Product Purge.
PASS v1.0 Mobile Product detalj ima postojeći archive/restore plus kontrolisani purge i SuperAdmin Total Product Purge danger-zone workflow.
PASS v1.0 Admin Catalog deletion API reuse-uje postojeće Laravel ProductDeletionService i TotalProductPurgeService ZERO TRACE guardove.
PASS OpenAPI dokumentuje ADMIN-CAT-03 deletion readiness, purge i Total Product Purge ugovor.
PASS v1.0 Admin Catalog purge tok ostaje product-only bez Product Variants contracta.
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
PASS Batch170 Task5 Mobile Customer360 tipovi pokrivaju summary, timeline, CRM note request/response i unlinked buyer ugovor.
PASS Batch170 Task5 AdminPortalUserDetail izlaže typed customer_360 payload iz canonical OpenAPI ugovora.
PASS Batch170 Task5 apiAdminCustomerPortal pokriva unlinked-buyers read i append-only CRM note POST bez paralelnog klijenta.
PASS Batch170 Task5 Customer360 query keys ostaju u centralnom adminQueryKeys customer-portal stablu.
PASS Batch170 Task5 Mobile contract parity ne vraća Product Variants contract.
PASS Batch171 Customer360 lista izlaže read-only nepovezane porudžbine kroz canonical API/query-key bez auto-match toka.
PASS Batch171 Customer360 detalj prikazuje canonical komercijalni summary bez lokalnog finansijskog preračunavanja.
PASS Batch171 Customer360 detalj podržava append-only interne CRM beleške kroz postojeći mutation authority.
PASS Batch171 Customer360 detalj prikazuje server timeline i servisne/komunikacione indikatore.
PASS Batch171 visible workspace ostaje postojeći Customer Portal authority bez heurističkog matching-a, profitability duplikata ili Product Variants povratka.
PASS Batch172 ManagementReportService ostaje canonical authority za comparison, customer/channel/product profitability i inventory efficiency.
PASS Batch172 customer profitability filter je server-authoritative, a turnover/GMROI eksplicitno ostaju current-inventory proxy bez lazne istorijske prosečne vrednosti.
PASS Batch172 canonical OpenAPI dokumentuje advanced analytics, Customer360 handoff filter i transparentan inventory proxy basis.
PASS Batch172 Mobile reports contract ima matching advanced analytics tipove i customer_user_id request parity pre visible Batch173 UI.
PASS Batch172 centralni query keys pripremaju Customer360 profitability handoff bez novog API namespace-a.
PASS Batch172 advanced analytics ne vraca Product Variants niti uvodi paralelni analytics API namespace.
PASS v1.0 USER-02 Mobile API pokriva User Groups list/create/update/delete relativni canonical ugovor.
PASS v1.0 USER-02 Mobile ekran pokriva permission, category scope, status, sort i bezbedni delete workflow.
PASS v1.0 USER-02 Admin Hub drži Grupe pristupa u organizovanoj Korisnici sekciji.
PASS v1.0 USER-02 TanStack query keys su centralizovani.
PASS v1.0 USER-02 API rute dele system.manage_users granicu i puni CRUD surface.
PASS v1.0 USER-02 Web i Mobile API dele isti UserGroupAdminService i AdminUserGroupRequest authority.
PASS v1.0 USER-02 shared servis čuva permission/category sync i blokira brisanje grupe sa korisnicima.
PASS OpenAPI dokumentuje kompletan USER-02 User Groups CRUD ugovor.
PASS v1.0 USER-02 parity ne vraća Product Variants contract.
PASS v1.0 Batch97 Admin Audit list is organized into overview, events, filters, export and security-policy workspaces.
PASS v1.0 Batch97 Audit list UX preserves security.view, server filters, pagination, safe detail, secure CSV and read-only capability contracts.
PASS v1.0 Batch97 Audit UX preserves date validation, sanitized payload disclosure and explicit no-mutation policy.
PASS v1.0 Batch97 Audit list UX does not restore Product Variants contract.
PASS v1.0 Batch96 Admin Field Operations list is organized into overview, work orders, filters, unassigned and teams workspaces.
PASS v1.0 Batch96 Field Operations list UX preserves permission, server filters, pagination, virtualized list and detail routing contracts.
PASS v1.0 Batch96 Field Operations focus workspaces reuse existing server unassigned and team filter semantics without parallel business logic.
PASS v1.0 Batch96 Field Operations list UX does not restore Product Variants contract.
PASS v1.0 Batch95 Admin After-sales list is organized into overview, cases, filters, overdue and execution workspaces.
PASS v1.0 Batch95 After-sales list UX preserves permission, server filters, pagination, virtualized list and detail routing contracts.
PASS v1.0 Batch95 attention workspaces reuse existing server overdue and execution_pending semantics without parallel business logic.
PASS v1.0 Batch95 After-sales list UX does not restore Product Variants contract.
PASS v1.0 Batch94 Admin Warranties list is organized into overview, warranties, filters, maintenance and rules workspaces.
PASS v1.0 Batch94 Warranties list UX preserves permission, server list filters, pagination, virtualized list, detail routing and Rules capability contracts.
PASS v1.0 Batch94 Warranties list UX does not restore Product Variants contract.
PASS v1.0 Batch93 Admin Orders list is organized into overview, orders, filters, attention and archive workspaces.
PASS v1.0 Batch93 Orders list UX preserves permission, filters, attention, pagination, detail, archive and server capability contracts.
PASS v1.0 Batch93 Orders list UX does not restore Product Variants contract.
PASS v1.0 Batch156 Admin Commission detail uses one single-page breakdown, approval, payout and history workflow.
PASS v1.0 Batch156 Commission detail preserves permission, server transitions, payout validation, confirmation, history and query invalidation contracts.
PASS v1.0 Batch156 Commission detail UX does not restore Product Variants contract.
PASS v1.0 Batch156 Admin Commissions list opens directly on commissions with compact summary, order value and existing tools.
PASS v1.0 Batch156 Commissions list UX preserves permission, filters, pagination, secure exports, bulk payment and server capability contracts.
PASS v1.0 Batch156 Commissions list UX does not restore Product Variants contract.
PASS v1.0 Batch90 Admin Warranty Rules is organized into overview, rules, editor and backfill workspaces.
PASS v1.0 Batch90 Warranty Rules UX preserves permission, scope, create, update, backfill, query invalidation and confirmation contracts.
PASS v1.0 Batch90 Warranty Rules UX does not restore Product Variants contract.
PASS v1.0 Batch89 Admin Receivables list is organized into overview, cases, filters, operations and settings workspaces.
PASS v1.0 Batch89 Receivables list UX preserves permission, filters, pagination, CSV, settings, scan and server capability contracts.
PASS v1.0 Batch89 Receivables list UX does not restore Product Variants contract.
PASS v1.0 Batch88 Admin Receivables detail is organized into overview, case, plan, payments, communication, reminders and audit workspaces.
PASS v1.0 Batch88 Receivables UX preserves permission, server capabilities, installment validation, contact visibility and outbox reminder contracts.
PASS v1.0 Batch88 Receivables UX does not restore Product Variants contract.
PASS v1.0 Batch87 Admin Service Parts je organizovan u Pregled, Delovi, Novi deo, Korekcije i Nabavka radne prostore.
PASS v1.0 Batch87 Service Parts UX čuva view/manage/procurement permission, CRUD, movement-ledger adjustment, idempotency i procurement poslovni ugovor.
PASS v1.0 Batch87 Service Parts UX ne vraća Product Variants contract.
PASS v1.0 Batch86 Admin Order detalj je organizovan u Pregled, Kupac i stavke, Isporuka, Finansije, Dokumenti i Tok i akcije radne prostore.
PASS v1.0 Batch86 Order UX čuva orders.manage, server-driven workflow, dokumente, archive i capability poslovni ugovor.
PASS v1.0 Batch86 Order UX ne vraća Product Variants contract.
PASS v1.0 Batch85 Admin Warranty detalj je organizovan u Pregled, Podaci, Održavanje, Dokument i Poništavanje radne prostore.
PASS v1.0 Batch85 Warranty UX čuva warranties.manage, update, void, maintenance i secure PDF poslovni ugovor.
PASS v1.0 Batch85 Warranty UX ne vraća Product Variants contract.
PASS v1.0 Batch84 Admin Field Operations detalj je organizovan u Pregled, Planiranje, Izvršenje, Delovi i Dokumentacija radne prostore.
PASS v1.0 Batch84 Field Operations UX čuva permission, schedule, en-route, on-site, complete, cancel, parts i secure attachment poslovni ugovor.
PASS v1.0 Batch84 Field Operations UX ne vraća Product Variants contract.
PASS v1.0 Batch83 Admin After-sales detalj je organizovan u Pregled, Slučaj, Komunikacija, Radnje i Istorija radne prostore.
PASS v1.0 Batch83 After-sales UX čuva postojeći permission, update, message, attachment, execute, complete, cancel i refund capability poslovni ugovor.
PASS v1.0 Batch83 After-sales UX ne vraća Product Variants contract.
PASS v1.0 Batch82 Admin Inventory razdvaja monolitni ekran u permission-aware radne prostore Pregled, Stanje, Prijem, Popis i Promene.
PASS v1.0 Batch82 Inventory UX čuva postojeće permission, idempotency, adjustment, receipt, count i CSV poslovne tokove.
PASS v1.0 Batch82 Inventory UX ne vraća Product Variants contract.
PASS v1.0 EUR/RSD provider chain je NBS javna prodajna lista primary -> Frankfurter secondary -> poslednji uspesno sacuvan kurs emergency fallback.
PASS NBS javna lista i Frankfurter timeouts koriste credential-free server env/config bez SOAP tajni.
PASS Web i Mobile jasno prikazuju NBS javnu listu primary, Frankfurter secondary i sacuvani kurs emergency fallback semantiku.
PASS Batch116 expo-image je SDK57-kompatibilan i zaključan u package/lock authority.
PASS Batch116 Mobile tipovi odvajaju original, display i thumbnail image URL-ove.
PASS Batch116 katalog i galerija koriste thumbnail/display derivatives, expo-image cache i horizontalnu virtualizaciju.
PASS Batch116 Download je zaključan isključivo na canonical original full-quality URL.
PASS Batch116 edit/create preview, product detail i cart koriste optimizovan image presentation path.
PASS Batch116 backend pravi odvojene WebP derivatives, čuva original i automatski osvežava cache nakon upload/clone/rotate.
PASS Batch116 image performance rad ne vraća Product Variants contract.

Ukupno FAIL: 0
RC_mobile_validator_preflight=0

============================================================
RUN - backup_count_preflight
============================================================
CWD=/home/icaffeco/ald1n-project/apps/cms/current
COMMAND=php /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/backup-count.php /home/icaffeco/ald1n-project/apps/cms/current 
COMPLETED_BACKUP_COUNT=2
COMPLETED_BACKUP_IDS=120,119
BACKUP_120_PATH=/home/icaffeco/backups/current/20260922-023004-daily-a7ca23
BACKUP_120_DIR=YES
BACKUP_119_PATH=/home/icaffeco/backups/current/20260921-023005-daily-063b72
BACKUP_119_DIR=YES
RC_backup_count_preflight=0
STABLE_BACKUP_RETENTION_PREFLIGHT=PASS_EXACTLY_2

============================================================
RUN - business_counts_before
============================================================
CWD=/home/icaffeco/ald1n-project/apps/cms/current
COMMAND=php /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/business-counts.php /home/icaffeco/ald1n-project/apps/cms/current 
USERS_COUNT=19
ORDERS_COUNT=36
CUSTOMER_CRM_NOTES_COUNT=0
ORDER_ITEMS_COUNT=40
ORDER_PAYMENTS_COUNT=36
RC_business_counts_before=0

============================================================
2. ARCHIVE REPORT472 PASS EVIDENCE AND ROTATE REPORT471
============================================================
rm 'docs/operations/471-BATCH172-V2-OPENAPI-YAML-RECOVERY-20260921-123812.md'
RC_git_rm_report471=0
RC_git_add_report472=0
EVIDENCE_STAGE_SCOPE=PASS_EXACT

============================================================
RUN - evidence_full_diffcheck
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git diff --cached --check 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:26: trailing whitespace.
+COMMAND=git fetch origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:55: trailing whitespace.
+COMMAND=php -l /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/patch-smoke.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:63: trailing whitespace.
+COMMAND=php -l /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/patch-validator.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:71: trailing whitespace.
+COMMAND=php -l /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/backup-count.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:79: trailing whitespace.
+COMMAND=php -l /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/business-counts.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:87: trailing whitespace.
+COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node --check /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/recovery-check.cjs 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:94: trailing whitespace.
+COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node --check /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/parse-openapi.cjs 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:101: trailing whitespace.
+COMMAND=php /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/backup-count.php /home/icaffeco/ald1n-project/apps/cms/current 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:114: trailing whitespace.
+COMMAND=php /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/business-counts.php /home/icaffeco/ald1n-project/apps/cms/current 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:131: trailing whitespace.
++COMMAND=git fetch origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:133: trailing whitespace.
++COMMAND=php -l /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/backup-count.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:135: trailing whitespace.
++COMMAND=php -l /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/business-counts.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:137: trailing whitespace.
++COMMAND=php -l /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/patch-smoke.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:139: trailing whitespace.
++COMMAND=php -l /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/patch-openapi.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:141: trailing whitespace.
++COMMAND=php /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/backup-count.php /home/icaffeco/ald1n-project/apps/cms/current 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:143: trailing whitespace.
++COMMAND=php /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/business-counts.php /home/icaffeco/ald1n-project/apps/cms/current 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:145: trailing whitespace.
++++COMMAND=git fetch origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:147: trailing whitespace.
++++COMMAND=php /home/icaffeco/.ald1n-batch171-customer360-20260921-113209/backup-count.php /home/icaffeco/ald1n-project/apps/cms/current 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:149: trailing whitespace.
++++COMMAND=git fetch origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:151: trailing whitespace.
++++COMMAND=git commit -m docs:\ archive\ Customer360\ Batch170\ PASS\ evidence 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:153: trailing whitespace.
++++COMMAND=git push origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:155: trailing whitespace.
++++COMMAND=git fetch origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:157: trailing whitespace.
++++COMMAND=php /home/icaffeco/.ald1n-batch171-customer360-20260921-113209/patch-validator.php /home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:159: trailing whitespace.
++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node scripts/validate-project.mjs 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:161: trailing whitespace.
++++COMMAND=php /home/icaffeco/.ald1n-batch171-customer360-20260921-113209/patch-index.php /home/icaffeco/ald1n-project/apps/mobile/current/src/app/\(app\)/admin/customer-portal/index.tsx 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:163: trailing whitespace.
++++COMMAND=php /home/icaffeco/.ald1n-batch171-customer360-20260921-113209/patch-detail.php /home/icaffeco/ald1n-project/apps/mobile/current/src/app/\(app\)/admin/customer-portal/\[userId\].tsx 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:165: trailing whitespace.
++++COMMAND=php /home/icaffeco/.ald1n-batch171-customer360-20260921-113209/patch-hub.php /home/icaffeco/ald1n-project/apps/mobile/current/src/app/\(app\)/admin/index.tsx 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:167: trailing whitespace.
++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js run typecheck 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:169: trailing whitespace.
++++COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node scripts/validate-project.mjs 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:171: trailing whitespace.
++++COMMAND=php bin/customer-360-contract-smoke.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:173: trailing whitespace.
++++COMMAND=php bin/static-check.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:175: trailing whitespace.
++++COMMAND=php /home/icaffeco/.ald1n-batch171-customer360-20260921-113209/backup-count.php /home/icaffeco/ald1n-project/apps/cms/current 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:177: trailing whitespace.
++++COMMAND=git diff --cached --check 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:179: trailing whitespace.
++++COMMAND=git fetch origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:181: trailing whitespace.
++++COMMAND=git commit -m feat\(mobile\):\ add\ Customer\ 360\ workspace 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:183: trailing whitespace.
++++COMMAND=git push origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:185: trailing whitespace.
++++COMMAND=git fetch origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:187: trailing whitespace.
++COMMAND=git fetch origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:189: trailing whitespace.
++COMMAND=git commit -m docs:\ archive\ Batch172\ OpenAPI\ validation\ gap\ evidence 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:191: trailing whitespace.
++COMMAND=git push origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:193: trailing whitespace.
++COMMAND=git fetch origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:195: trailing whitespace.
++COMMAND=php /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/patch-smoke.php /home/icaffeco/ald1n-project/apps/cms/current/bin/advanced-analytics-contract-smoke.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:197: trailing whitespace.
++COMMAND=php -l /home/icaffeco/ald1n-project/apps/cms/current/bin/advanced-analytics-contract-smoke.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:199: trailing whitespace.
++COMMAND=php bin/advanced-analytics-contract-smoke.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:201: trailing whitespace.
++COMMAND=php /home/icaffeco/.ald1n-batch172-v2-openapi-20260921-123812/patch-openapi.php /home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:203: trailing whitespace.
++COMMAND=php bin/advanced-analytics-contract-smoke.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:212: trailing whitespace.
+COMMAND=git fetch origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:222: trailing whitespace.
+COMMAND=git commit -m docs:\ archive\ Batch172\ parser\ harness\ failure 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:234: trailing whitespace.
+COMMAND=git push origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:243: trailing whitespace.
+COMMAND=git fetch origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:267: trailing whitespace.
+COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/parse-openapi.cjs /home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:292: trailing whitespace.
+COMMAND=php /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/patch-smoke.php /home/icaffeco/ald1n-project/apps/cms/current/bin/advanced-analytics-contract-smoke.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:300: trailing whitespace.
+COMMAND=php /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/patch-validator.php /home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:308: trailing whitespace.
+COMMAND=php -l /home/icaffeco/ald1n-project/apps/cms/current/bin/advanced-analytics-contract-smoke.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:316: trailing whitespace.
+COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/recovery-check.cjs /home/icaffeco/ald1n-project 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:334: trailing whitespace.
+COMMAND=php bin/advanced-analytics-contract-smoke.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:370: trailing whitespace.
+COMMAND=php bin/management-report-smoke.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:382: trailing whitespace.
+COMMAND=php bin/customer-360-contract-smoke.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:451: trailing whitespace.
+COMMAND=php bin/static-check.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:1445: trailing whitespace.
+COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js run typecheck 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:1456: trailing whitespace.
+COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node scripts/validate-project.mjs 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:2071: trailing whitespace.
+COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/parse-openapi.cjs /home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:2089: trailing whitespace.
+COMMAND=php /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/business-counts.php /home/icaffeco/ald1n-project/apps/cms/current 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:2103: trailing whitespace.
+COMMAND=php /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/backup-count.php /home/icaffeco/ald1n-project/apps/cms/current 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:2123: trailing whitespace.
+COMMAND=git diff --cached --check 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:2131: trailing whitespace.
+COMMAND=git fetch origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:2142: trailing whitespace.
+COMMAND=git commit -m fix\(api\):\ validate\ canonical\ OpenAPI\ YAML 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:2152: trailing whitespace.
+COMMAND=git push origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:2161: trailing whitespace.
+COMMAND=git fetch origin main 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:2176: trailing whitespace.
+COMMAND=php bin/advanced-analytics-contract-smoke.php 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:2212: trailing whitespace.
+COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node scripts/validate-project.mjs 
docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md:2824: trailing whitespace.
+COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch172-v3-parser-20260921-165801/parse-openapi.cjs /home/icaffeco/ald1n-project/packages/api-contract/openapi.yaml 
RC_evidence_full_diffcheck=2
RC_evidence_full_diffcheck_observed=2

============================================================
RUN - evidence_nonreport_diffcheck
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git diff --cached --check -- . :\(exclude\)docs/operations/\*\* 
RC_evidence_nonreport_diffcheck=0
EVIDENCE_RAW_REPORT_WHITESPACE_POLICY=PASS

============================================================
RUN - git_fetch_evidence_race
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main 
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch_evidence_race=0
EVIDENCE_REMOTE_RACE=1f1dbd80b978592e322f00a0d005187676f0d2c3

============================================================
RUN - evidence_commit
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git commit -m docs:\ archive\ Batch172\ recovery\ PASS\ evidence 
[main 16e5fa4] docs: archive Batch172 recovery PASS evidence
 2 files changed, 2865 insertions(+), 333 deletions(-)
 delete mode 100644 docs/operations/471-BATCH172-V2-OPENAPI-YAML-RECOVERY-20260921-123812.md
 create mode 100644 docs/operations/472-BATCH172-V3-PARSER-HARNESS-RECOVERY-20260921-165801.md
RC_evidence_commit=0
EVIDENCE_COMMIT=16e5fa47133f2ccb8e52a9a8fa1c59e0fe8f7f7b

============================================================
RUN - evidence_push
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git push origin main 
To github.com:AldinAga/ald1n-project.git
   1f1dbd8..16e5fa4  main -> main
RC_evidence_push=0

============================================================
RUN - evidence_postfetch
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main 
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_evidence_postfetch=0
EVIDENCE_PUSH=PASS

============================================================
3. TDD RED - PIN SIX VISIBLE BATCH173 CONTRACTS
============================================================

============================================================
RUN - patch_validator_red
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/patch-validator.php /home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs 
BATCH173_VALIDATOR_PATCH=PASS
RC_patch_validator_red=0

============================================================
RUN - validator_syntax_red
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node --check scripts/validate-project.mjs 
RC_validator_syntax_red=0

============================================================
RUN - mobile_validator_red
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node scripts/validate-project.mjs 
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
PASS src/app/(app)/account/profile.tsx postoji.
PASS src/app/(app)/account/security.tsx postoji.
PASS src/app/(app)/account/preferences.tsx postoji.
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
PASS src/components/ui/operator-row.tsx postoji.
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
PASS v0.8 Expo compatibility matrix zaključava expo na ~57.0.21.
PASS v0.8 Expo compatibility matrix zaključava expo-constants na ~57.0.17.
PASS v0.8 Expo compatibility matrix zaključava expo-crypto na ~57.0.2.
PASS v0.8 Expo compatibility matrix zaključava expo-dev-client na ~57.0.18.
PASS v0.8 Expo compatibility matrix zaključava expo-file-system na ~57.0.6.
PASS v0.8 Expo compatibility matrix zaključava expo-font na ~57.0.3.
PASS v0.8 Expo compatibility matrix zaključava expo-linking na ~57.0.9.
PASS v0.8 Expo compatibility matrix zaključava expo-notifications na ~57.0.17.
PASS v0.8 Expo compatibility matrix zaključava expo-router na ~57.0.20.
PASS v0.8 Expo compatibility matrix zaključava expo-sharing na ~57.0.18.
PASS v0.8 Expo compatibility matrix zaključava expo-secure-store na ~57.0.3.
PASS v0.8 Expo compatibility matrix zaključava expo-system-ui na ~57.0.3.
PASS v0.8 Expo compatibility matrix zaključava expo-splash-screen na ~57.0.8.
PASS v0.8 Expo compatibility matrix zaključava expo-updates na ~57.0.21.
PASS Static colors consumeri su uklonjeni iz aplikacionog source-a.
PASS Legacy colors.* usage ne postoji van RN theme adaptera.
PASS Unsafe as never / as unknown as castovi ne postoje u source-u.
PASS 184 TypeScript/TSX fajlova prolazi sintaksnu proveru.
PASS app.config.ts prolazi TypeScript sintaksnu proveru.
PASS 1340 lokalnih @/ importa je razrešeno.
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
PASS v1.0 Bottom navigation aktivni TAB koristi puni tonalni pill indikator za ikonicu i naziv.
PASS Tab badge koristi semantic danger/onDanger foreground par.
PASS UI koristi native Expo Symbols umesto tekstualnih pseudo-ikonica.
PASS Build16 Glyph koristi jednu native Symbols porodicu bez tekstualnih pseudo-fallback ikonica.
PASS Build16 OperatorRow koristi UI-thread press motion i reduced-motion ugovor.
PASS Build16 globalni Button koristi canonical operator radius i kontrolisani press feedback.
PASS Build16 globalni Card prati card-diet radius i suptilnu elevation hijerarhiju.
PASS Build16 Home akcije koriste jednu grupisanu operator površinu umesto card-per-row obrasca.
PASS Build16 Home koristi approved Operator welcome/focus hijerarhiju bez legacy gradient-orb/pill hero obrasca.
PASS Build16 Catalog koristi Operator search/filter hijerarhiju i postojeći server taxonomy filter authority.
PASS Build16 Catalog product row je kompaktan operator surface i čuva Batch116 thumbnail/cache contract.
PASS Build16 Catalog slika ima eksplicitne bounds i intrinsic dimenzije fotografije ne mogu da rastegnu product row.
PASS Build16 SelectSheet koristi native Glyph sistem bez tekstualnih pseudo-ikonica.
PASS Build16 Mobile catalog param contract izlaže postojeće CatalogQueryService taxonomy filtere bez novog backend toka.
PASS Build16 Product detail koristi Operator identity/commercial/section hijerarhiju, čuva Batch116 image authority i v0.9 Direct Sale > Uredi contract bez card-zoo/pseudo-back ikonice.
PASS Build16 Orders lista koristi Operator hijerarhiju i čuva v0.9 Moje/Dodeljene granicu bez cross-feature prečica.
PASS Build16 Order row je kompaktan Operator surface bez Card wrappera i koristi canonical AppColors shadow token.
PASS Build16 Order detail koristi Operator section hierarchy i čuva post-create, payment proof, documents, delivery, cancel i after-sales authority.
PASS Build16 Notifications koristi Operator inbox bez Card-per-row obrasca i čuva read/read-all + poslovni deep-link routing authority.
PASS Build16 Account koristi grupisane OperatorRow površine i čuva Profil > Bezbednost > Obaveštenja > Uređaji > Odjava + sessions/preferences authority.
PASS Build16 Cart koristi Operator list/summary hijerarhiju bez Card/pseudo-icon obrasca i čuva Batch116 image cache + product-only quantity/remove/clear/checkout tok.
PASS Build16 Checkout koristi Operator step hijerarhiju i čuva stable idempotency, bank transfer, deferred-payment, product-only create i success routing authority.
PASS Build16 Cart decrement koristi canonical Expo Symbols remove semantic bez tekstualnog pseudo-icon fallbacka.
PASS Build16 Admin Hub koristi OperatorRow grupisane akcije i čuva permission/module/inventory/global-search authority bez button-zoo obrasca.
PASS Build16 shared loading/empty/unavailable/error states koriste canonical radii, reduced-motion i postojeći retry/request-id contract.
PASS Build16 PageHeader zadržava notification badge/routing authority uz Operator control radius i restrained press feedback.
PASS Build16 Laravel final polish zaključava flat body, focus-visible, empty-state, control radius i reduced-motion presentation contract.
PASS Build16 native Expo palette and production EAS remote-version authority are release-locked.
PASS Build16 catalog product row preserves the already-committed bounded image geometry from 33604307.
PASS Build16 catalog refetches server authority whenever the tab regains focus.
PASS Build16 product detail refreshes stock/status authority on focus.
PASS Direct sale and catalog administration invalidate catalog list, detail and filter caches after mutation.
PASS Mobile product create is single-flight per active request and retries the same payload through server idempotency.
PASS Build17 galerija prati stvarni 4:3/3:4 odnos telefonske fotografije bez fiksnog letterbox image box-a.
PASS Build17 ima izolovan runtime 1.0.0-build17 pa OTA ne može slučajno targetirati Build15/Build16 runtime 1.0.0.
PASS Build16 canonical icon registry postoji.
PASS Build16 icon registry zaključava Android Symbols i aktivni Laravel Phosphor runtime authority.
PASS Canonical packages/api-contract/openapi.yaml postoji.
PASS Mobile OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.
PASS CMS OpenAPI kopija postoji.
PASS CMS OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.
PASS Batch172 recovery canonical OpenAPI YAML parsira kroz postojeci Node yaml toolchain i izlaže report/customer schemas i paths.
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
PASS v0.9/Batch151 Direct Sale ekran prikazuje custom plan rata, prvu ratu odmah i konačni datum pune isplate.
PASS OpenAPI dokumentuje deferred Direct Sale payment metodu, rate i konačni datum.
PASS v0.9 Direct Sale deferred tok ne vraća Product Variants.
PASS v0.9 Bottom navigation ostaje Početna, Katalog, Porudžbine, Obaveštenja, Nalog.
PASS v0.9 Home prati Fokus danas > Brze akcije > Moje aktivnosti > Administracija hijerarhiju.
PASS Build18 Home ne duplira primarne Porudžbine, a zadržava provizije, garancije i postprodaju.
PASS v0.9 Porudžbine prikazuju Moje i Dodeljene bez cross-feature prečica.
PASS v0.9 Admin Hub je permission-filtered, pretraživ i grupisan u šest poslovnih sekcija.
PASS v0.9 Nalog prati Profil > Bezbednost > Obaveštenja > Uređaji > Odjava redosled.
PASS v0.9 Navigation reorganizacija ne vraća Product Variants.
PASS Build18 koristi jedan canonical AppBottomNav i drži interni Expo Tabs bar skrivenim.
PASS Build18 Home uklanja duple primarne ulaze i pasivne prazne/status sekcije.
PASS Build18 Admin Hub zadržava permission-aware poslovne grupe bez pasivnih foundation kartica.
PASS v1.0 Batch50 V2 lokalna tema i valuta su per-user/per-device SecureStore preference bez server write-a.
PASS v1.0 Batch50 V2 Profil ima moderne single-choice Tema i Primarna valuta kontrole.
PASS v1.0 Batch50 V2 lokalna tema upravlja RN/Tamagui/StatusBar shell-om posle korisničke preference hidratacije.
PASS v1.0 Batch50 V2 bootstrap izlaže samo read-only presentation metadata postojećeg NBS authority-ja.
PASS v1.0 Batch50 V2 komercijalni prodajni authority ostaje centralan uz NBS javnu listu primary, Frankfurter secondary i poslednji sacuvani kurs emergency fallback.
PASS v1.0 Batch50 V2 primarna valuta je display-only; canonical RSD payment input i payload ostaju nepromenjeni.
PASS v1.0 Batch50 V2 globalni loading koristi branded Reanimated pulse i skeleton.
PASS v1.0 Batch50 V2 bottom nav drži centralno izdvojenu Početnu i role-aware Admin/Obaveštenja četvrti slot.
PASS v1.0 Batch50 V2 header desno koristi notification bell+badge umesto profila, a Nalog ostaje u bottom nav-u.
PASS v1.0 Batch50 V2 čuva Katalog active context za admin/catalog edit, dok ostali admin ekrani aktiviraju Admin slot.
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
PASS v1.0 Admin Orders PDF/shipment binary putanje su relativne i ne dupliraju /api/v1 prefiks.
PASS v1.0 Admin Order detalj ugrađuje permission-gated Poslovni dokumenti workbench bez orphan ekrana.
PASS v1.0 Mobile document workbench pokriva predračun, račun, otpremnicu, istoriju revizija i kontrolisano storniranje.
PASS v1.0 Admin dokument PDF koristi authenticated Bearer download, PDF signature proveru i privatni cache/share flow.
PASS v1.0 Admin dokumenti ostaju product-only bez Product Variants contracta.
PASS v1.0 Admin Catalog API pokriva server-driven deletion readiness, purge i Total Product Purge.
PASS v1.0 Mobile Product detalj ima postojeći archive/restore plus kontrolisani purge i SuperAdmin Total Product Purge danger-zone workflow.
PASS v1.0 Admin Catalog deletion API reuse-uje postojeće Laravel ProductDeletionService i TotalProductPurgeService ZERO TRACE guardove.
PASS OpenAPI dokumentuje ADMIN-CAT-03 deletion readiness, purge i Total Product Purge ugovor.
PASS v1.0 Admin Catalog purge tok ostaje product-only bez Product Variants contracta.
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
PASS Batch170 Task5 Mobile Customer360 tipovi pokrivaju summary, timeline, CRM note request/response i unlinked buyer ugovor.
PASS Batch170 Task5 AdminPortalUserDetail izlaže typed customer_360 payload iz canonical OpenAPI ugovora.
PASS Batch170 Task5 apiAdminCustomerPortal pokriva unlinked-buyers read i append-only CRM note POST bez paralelnog klijenta.
PASS Batch170 Task5 Customer360 query keys ostaju u centralnom adminQueryKeys customer-portal stablu.
PASS Batch170 Task5 Mobile contract parity ne vraća Product Variants contract.
PASS Batch171 Customer360 lista izlaže read-only nepovezane porudžbine kroz canonical API/query-key bez auto-match toka.
PASS Batch171 Customer360 detalj prikazuje canonical komercijalni summary bez lokalnog finansijskog preračunavanja.
PASS Batch171 Customer360 detalj podržava append-only interne CRM beleške kroz postojeći mutation authority.
PASS Batch171 Customer360 detalj prikazuje server timeline i servisne/komunikacione indikatore.
PASS Batch171 visible workspace ostaje postojeći Customer Portal authority bez heurističkog matching-a, profitability duplikata ili Product Variants povratka.
PASS Batch172 ManagementReportService ostaje canonical authority za comparison, customer/channel/product profitability i inventory efficiency.
PASS Batch172 customer profitability filter je server-authoritative, a turnover/GMROI eksplicitno ostaju current-inventory proxy bez lazne istorijske prosečne vrednosti.
PASS Batch172 canonical OpenAPI dokumentuje advanced analytics, Customer360 handoff filter i transparentan inventory proxy basis.
PASS Batch172 Mobile reports contract ima matching advanced analytics tipove i customer_user_id request parity pre visible Batch173 UI.
PASS Batch172 centralni query keys pripremaju Customer360 profitability handoff bez novog API namespace-a.
PASS Batch172 advanced analytics ne vraca Product Variants niti uvodi paralelni analytics API namespace.
FAIL Batch173 Reports UI prikazuje server-computed prethodni period i delta metrike bez paralelnog analytics izvora.
FAIL Batch173 Reports UI prikazuje customer/LTV i sales-channel profitabilnost direktno iz ManagementReportService payload-a.
FAIL Batch173 Reports UI prikazuje server-ranked top/bottom product profitability bez lokalnog sortiranja ili preračunavanja.
FAIL Batch173 Inventory UI prikazuje turnover/GMROI uz eksplicitnu current-inventory proxy napomenu.
FAIL Batch173 Customer360 profitability koristi postojeći reports.view + management endpoint + customer_user_id authority.
FAIL Batch173 Mobile UI samo formatira server profitability vrednosti i ne vraća lokalne formule, /analytics namespace ili Product Variants.
PASS v1.0 USER-02 Mobile API pokriva User Groups list/create/update/delete relativni canonical ugovor.
PASS v1.0 USER-02 Mobile ekran pokriva permission, category scope, status, sort i bezbedni delete workflow.
PASS v1.0 USER-02 Admin Hub drži Grupe pristupa u organizovanoj Korisnici sekciji.
PASS v1.0 USER-02 TanStack query keys su centralizovani.
PASS v1.0 USER-02 API rute dele system.manage_users granicu i puni CRUD surface.
PASS v1.0 USER-02 Web i Mobile API dele isti UserGroupAdminService i AdminUserGroupRequest authority.
PASS v1.0 USER-02 shared servis čuva permission/category sync i blokira brisanje grupe sa korisnicima.
PASS OpenAPI dokumentuje kompletan USER-02 User Groups CRUD ugovor.
PASS v1.0 USER-02 parity ne vraća Product Variants contract.
PASS v1.0 Batch97 Admin Audit list is organized into overview, events, filters, export and security-policy workspaces.
PASS v1.0 Batch97 Audit list UX preserves security.view, server filters, pagination, safe detail, secure CSV and read-only capability contracts.
PASS v1.0 Batch97 Audit UX preserves date validation, sanitized payload disclosure and explicit no-mutation policy.
PASS v1.0 Batch97 Audit list UX does not restore Product Variants contract.
PASS v1.0 Batch96 Admin Field Operations list is organized into overview, work orders, filters, unassigned and teams workspaces.
PASS v1.0 Batch96 Field Operations list UX preserves permission, server filters, pagination, virtualized list and detail routing contracts.
PASS v1.0 Batch96 Field Operations focus workspaces reuse existing server unassigned and team filter semantics without parallel business logic.
PASS v1.0 Batch96 Field Operations list UX does not restore Product Variants contract.
PASS v1.0 Batch95 Admin After-sales list is organized into overview, cases, filters, overdue and execution workspaces.
PASS v1.0 Batch95 After-sales list UX preserves permission, server filters, pagination, virtualized list and detail routing contracts.
PASS v1.0 Batch95 attention workspaces reuse existing server overdue and execution_pending semantics without parallel business logic.
PASS v1.0 Batch95 After-sales list UX does not restore Product Variants contract.
PASS v1.0 Batch94 Admin Warranties list is organized into overview, warranties, filters, maintenance and rules workspaces.
PASS v1.0 Batch94 Warranties list UX preserves permission, server list filters, pagination, virtualized list, detail routing and Rules capability contracts.
PASS v1.0 Batch94 Warranties list UX does not restore Product Variants contract.
PASS v1.0 Batch93 Admin Orders list is organized into overview, orders, filters, attention and archive workspaces.
PASS v1.0 Batch93 Orders list UX preserves permission, filters, attention, pagination, detail, archive and server capability contracts.
PASS v1.0 Batch93 Orders list UX does not restore Product Variants contract.
PASS v1.0 Batch156 Admin Commission detail uses one single-page breakdown, approval, payout and history workflow.
PASS v1.0 Batch156 Commission detail preserves permission, server transitions, payout validation, confirmation, history and query invalidation contracts.
PASS v1.0 Batch156 Commission detail UX does not restore Product Variants contract.
PASS v1.0 Batch156 Admin Commissions list opens directly on commissions with compact summary, order value and existing tools.
PASS v1.0 Batch156 Commissions list UX preserves permission, filters, pagination, secure exports, bulk payment and server capability contracts.
PASS v1.0 Batch156 Commissions list UX does not restore Product Variants contract.
PASS v1.0 Batch90 Admin Warranty Rules is organized into overview, rules, editor and backfill workspaces.
PASS v1.0 Batch90 Warranty Rules UX preserves permission, scope, create, update, backfill, query invalidation and confirmation contracts.
PASS v1.0 Batch90 Warranty Rules UX does not restore Product Variants contract.
PASS v1.0 Batch89 Admin Receivables list is organized into overview, cases, filters, operations and settings workspaces.
PASS v1.0 Batch89 Receivables list UX preserves permission, filters, pagination, CSV, settings, scan and server capability contracts.
PASS v1.0 Batch89 Receivables list UX does not restore Product Variants contract.
PASS v1.0 Batch88 Admin Receivables detail is organized into overview, case, plan, payments, communication, reminders and audit workspaces.
PASS v1.0 Batch88 Receivables UX preserves permission, server capabilities, installment validation, contact visibility and outbox reminder contracts.
PASS v1.0 Batch88 Receivables UX does not restore Product Variants contract.
PASS v1.0 Batch87 Admin Service Parts je organizovan u Pregled, Delovi, Novi deo, Korekcije i Nabavka radne prostore.
PASS v1.0 Batch87 Service Parts UX čuva view/manage/procurement permission, CRUD, movement-ledger adjustment, idempotency i procurement poslovni ugovor.
PASS v1.0 Batch87 Service Parts UX ne vraća Product Variants contract.
PASS v1.0 Batch86 Admin Order detalj je organizovan u Pregled, Kupac i stavke, Isporuka, Finansije, Dokumenti i Tok i akcije radne prostore.
PASS v1.0 Batch86 Order UX čuva orders.manage, server-driven workflow, dokumente, archive i capability poslovni ugovor.
PASS v1.0 Batch86 Order UX ne vraća Product Variants contract.
PASS v1.0 Batch85 Admin Warranty detalj je organizovan u Pregled, Podaci, Održavanje, Dokument i Poništavanje radne prostore.
PASS v1.0 Batch85 Warranty UX čuva warranties.manage, update, void, maintenance i secure PDF poslovni ugovor.
PASS v1.0 Batch85 Warranty UX ne vraća Product Variants contract.
PASS v1.0 Batch84 Admin Field Operations detalj je organizovan u Pregled, Planiranje, Izvršenje, Delovi i Dokumentacija radne prostore.
PASS v1.0 Batch84 Field Operations UX čuva permission, schedule, en-route, on-site, complete, cancel, parts i secure attachment poslovni ugovor.
PASS v1.0 Batch84 Field Operations UX ne vraća Product Variants contract.
PASS v1.0 Batch83 Admin After-sales detalj je organizovan u Pregled, Slučaj, Komunikacija, Radnje i Istorija radne prostore.
PASS v1.0 Batch83 After-sales UX čuva postojeći permission, update, message, attachment, execute, complete, cancel i refund capability poslovni ugovor.
PASS v1.0 Batch83 After-sales UX ne vraća Product Variants contract.
PASS v1.0 Batch82 Admin Inventory razdvaja monolitni ekran u permission-aware radne prostore Pregled, Stanje, Prijem, Popis i Promene.
PASS v1.0 Batch82 Inventory UX čuva postojeće permission, idempotency, adjustment, receipt, count i CSV poslovne tokove.
PASS v1.0 Batch82 Inventory UX ne vraća Product Variants contract.
PASS v1.0 EUR/RSD provider chain je NBS javna prodajna lista primary -> Frankfurter secondary -> poslednji uspesno sacuvan kurs emergency fallback.
PASS NBS javna lista i Frankfurter timeouts koriste credential-free server env/config bez SOAP tajni.
PASS Web i Mobile jasno prikazuju NBS javnu listu primary, Frankfurter secondary i sacuvani kurs emergency fallback semantiku.
PASS Batch116 expo-image je SDK57-kompatibilan i zaključan u package/lock authority.
PASS Batch116 Mobile tipovi odvajaju original, display i thumbnail image URL-ove.
PASS Batch116 katalog i galerija koriste thumbnail/display derivatives, expo-image cache i horizontalnu virtualizaciju.
PASS Batch116 Download je zaključan isključivo na canonical original full-quality URL.
PASS Batch116 edit/create preview, product detail i cart koriste optimizovan image presentation path.
PASS Batch116 backend pravi odvojene WebP derivatives, čuva original i automatski osvežava cache nakon upload/clone/rotate.
PASS Batch116 image performance rad ne vraća Product Variants contract.

Ukupno FAIL: 6
RC_mobile_validator_red=1
MOBILE_VALIDATOR_RED_RC=1
BATCH173_RED_FAIL_COUNT=6
BATCH173_RED_ALL_FAIL_COUNT=6
BATCH173_RED_SUMMARY_COUNT=1
TDD_RED=PASS_EXACT_6_VISIBLE_ANALYTICS_GAPS

============================================================
4. GREEN - VISIBLE ANALYTICS AND CUSTOMER360 PROFITABILITY
============================================================

============================================================
RUN - patch_reports_ui
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/patch-reports.php /home/icaffeco/ald1n-project/apps/mobile/current/src/app/\(app\)/admin/reports/index.tsx 
BATCH173_REPORTS_UI_PATCH=PASS
RC_patch_reports_ui=0

============================================================
RUN - patch_customer360_profitability
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php /home/icaffeco/.ald1n-batch173-analytics-ui-20260922-142555/patch-customer.php /home/icaffeco/ald1n-project/apps/mobile/current/src/app/\(app\)/admin/customer-portal/\[userId\].tsx 
BATCH173_CUSTOMER360_PROFITABILITY_PATCH=PASS
RC_patch_customer360_profitability=0

============================================================
RUN - mobile_typecheck
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js run typecheck 

> ald1n-mobile@1.0.0 typecheck
> tsc --noEmit

RC_mobile_typecheck=0
MOBILE_TYPECHECK=PASS_RC0

============================================================
RUN - mobile_validator_green
============================================================
CWD=/home/icaffeco/ald1n-project/apps/mobile/current
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node scripts/validate-project.mjs 
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
PASS src/app/(app)/account/profile.tsx postoji.
PASS src/app/(app)/account/security.tsx postoji.
PASS src/app/(app)/account/preferences.tsx postoji.
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
PASS src/components/ui/operator-row.tsx postoji.
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
PASS v0.8 Expo compatibility matrix zaključava expo na ~57.0.21.
PASS v0.8 Expo compatibility matrix zaključava expo-constants na ~57.0.17.
PASS v0.8 Expo compatibility matrix zaključava expo-crypto na ~57.0.2.
PASS v0.8 Expo compatibility matrix zaključava expo-dev-client na ~57.0.18.
PASS v0.8 Expo compatibility matrix zaključava expo-file-system na ~57.0.6.
PASS v0.8 Expo compatibility matrix zaključava expo-font na ~57.0.3.
PASS v0.8 Expo compatibility matrix zaključava expo-linking na ~57.0.9.
PASS v0.8 Expo compatibility matrix zaključava expo-notifications na ~57.0.17.
PASS v0.8 Expo compatibility matrix zaključava expo-router na ~57.0.20.
PASS v0.8 Expo compatibility matrix zaključava expo-sharing na ~57.0.18.
PASS v0.8 Expo compatibility matrix zaključava expo-secure-store na ~57.0.3.
PASS v0.8 Expo compatibility matrix zaključava expo-system-ui na ~57.0.3.
PASS v0.8 Expo compatibility matrix zaključava expo-splash-screen na ~57.0.8.
PASS v0.8 Expo compatibility matrix zaključava expo-updates na ~57.0.21.
PASS Static colors consumeri su uklonjeni iz aplikacionog source-a.
PASS Legacy colors.* usage ne postoji van RN theme adaptera.
PASS Unsafe as never / as unknown as castovi ne postoje u source-u.
PASS 184 TypeScript/TSX fajlova prolazi sintaksnu proveru.
PASS app.config.ts prolazi TypeScript sintaksnu proveru.
PASS 1341 lokalnih @/ importa je razrešeno.
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
PASS v1.0 Bottom navigation aktivni TAB koristi puni tonalni pill indikator za ikonicu i naziv.
PASS Tab badge koristi semantic danger/onDanger foreground par.
PASS UI koristi native Expo Symbols umesto tekstualnih pseudo-ikonica.
PASS Build16 Glyph koristi jednu native Symbols porodicu bez tekstualnih pseudo-fallback ikonica.
PASS Build16 OperatorRow koristi UI-thread press motion i reduced-motion ugovor.
PASS Build16 globalni Button koristi canonical operator radius i kontrolisani press feedback.
PASS Build16 globalni Card prati card-diet radius i suptilnu elevation hijerarhiju.
PASS Build16 Home akcije koriste jednu grupisanu operator površinu umesto card-per-row obrasca.
PASS Build16 Home koristi approved Operator welcome/focus hijerarhiju bez legacy gradient-orb/pill hero obrasca.
PASS Build16 Catalog koristi Operator search/filter hijerarhiju i postojeći server taxonomy filter authority.
PASS Build16 Catalog product row je kompaktan operator surface i čuva Batch116 thumbnail/cache contract.
PASS Build16 Catalog slika ima eksplicitne bounds i intrinsic dimenzije fotografije ne mogu da rastegnu product row.
PASS Build16 SelectSheet koristi native Glyph sistem bez tekstualnih pseudo-ikonica.
PASS Build16 Mobile catalog param contract izlaže postojeće CatalogQueryService taxonomy filtere bez novog backend toka.
PASS Build16 Product detail koristi Operator identity/commercial/section hijerarhiju, čuva Batch116 image authority i v0.9 Direct Sale > Uredi contract bez card-zoo/pseudo-back ikonice.
PASS Build16 Orders lista koristi Operator hijerarhiju i čuva v0.9 Moje/Dodeljene granicu bez cross-feature prečica.
PASS Build16 Order row je kompaktan Operator surface bez Card wrappera i koristi canonical AppColors shadow token.
PASS Build16 Order detail koristi Operator section hierarchy i čuva post-create, payment proof, documents, delivery, cancel i after-sales authority.
PASS Build16 Notifications koristi Operator inbox bez Card-per-row obrasca i čuva read/read-all + poslovni deep-link routing authority.
PASS Build16 Account koristi grupisane OperatorRow površine i čuva Profil > Bezbednost > Obaveštenja > Uređaji > Odjava + sessions/preferences authority.
PASS Build16 Cart koristi Operator list/summary hijerarhiju bez Card/pseudo-icon obrasca i čuva Batch116 image cache + product-only quantity/remove/clear/checkout tok.
PASS Build16 Checkout koristi Operator step hijerarhiju i čuva stable idempotency, bank transfer, deferred-payment, product-only create i success routing authority.
PASS Build16 Cart decrement koristi canonical Expo Symbols remove semantic bez tekstualnog pseudo-icon fallbacka.
PASS Build16 Admin Hub koristi OperatorRow grupisane akcije i čuva permission/module/inventory/global-search authority bez button-zoo obrasca.
PASS Build16 shared loading/empty/unavailable/error states koriste canonical radii, reduced-motion i postojeći retry/request-id contract.
PASS Build16 PageHeader zadržava notification badge/routing authority uz Operator control radius i restrained press feedback.
PASS Build16 Laravel final polish zaključava flat body, focus-visible, empty-state, control radius i reduced-motion presentation contract.
PASS Build16 native Expo palette and production EAS remote-version authority are release-locked.
PASS Build16 catalog product row preserves the already-committed bounded image geometry from 33604307.
PASS Build16 catalog refetches server authority whenever the tab regains focus.
PASS Build16 product detail refreshes stock/status authority on focus.
PASS Direct sale and catalog administration invalidate catalog list, detail and filter caches after mutation.
PASS Mobile product create is single-flight per active request and retries the same payload through server idempotency.
PASS Build17 galerija prati stvarni 4:3/3:4 odnos telefonske fotografije bez fiksnog letterbox image box-a.
PASS Build17 ima izolovan runtime 1.0.0-build17 pa OTA ne može slučajno targetirati Build15/Build16 runtime 1.0.0.
PASS Build16 canonical icon registry postoji.
PASS Build16 icon registry zaključava Android Symbols i aktivni Laravel Phosphor runtime authority.
PASS Canonical packages/api-contract/openapi.yaml postoji.
PASS Mobile OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.
PASS CMS OpenAPI kopija postoji.
PASS CMS OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.
PASS Batch172 recovery canonical OpenAPI YAML parsira kroz postojeci Node yaml toolchain i izlaže report/customer schemas i paths.
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
PASS v0.9/Batch151 Direct Sale ekran prikazuje custom plan rata, prvu ratu odmah i konačni datum pune isplate.
PASS OpenAPI dokumentuje deferred Direct Sale payment metodu, rate i konačni datum.
PASS v0.9 Direct Sale deferred tok ne vraća Product Variants.
PASS v0.9 Bottom navigation ostaje Početna, Katalog, Porudžbine, Obaveštenja, Nalog.
PASS v0.9 Home prati Fokus danas > Brze akcije > Moje aktivnosti > Administracija hijerarhiju.
PASS Build18 Home ne duplira primarne Porudžbine, a zadržava provizije, garancije i postprodaju.
PASS v0.9 Porudžbine prikazuju Moje i Dodeljene bez cross-feature prečica.
PASS v0.9 Admin Hub je permission-filtered, pretraživ i grupisan u šest poslovnih sekcija.
PASS v0.9 Nalog prati Profil > Bezbednost > Obaveštenja > Uređaji > Odjava redosled.
PASS v0.9 Navigation reorganizacija ne vraća Product Variants.
PASS Build18 koristi jedan canonical AppBottomNav i drži interni Expo Tabs bar skrivenim.
PASS Build18 Home uklanja duple primarne ulaze i pasivne prazne/status sekcije.
PASS Build18 Admin Hub zadržava permission-aware poslovne grupe bez pasivnih foundation kartica.
PASS v1.0 Batch50 V2 lokalna tema i valuta su per-user/per-device SecureStore preference bez server write-a.
PASS v1.0 Batch50 V2 Profil ima moderne single-choice Tema i Primarna valuta kontrole.
PASS v1.0 Batch50 V2 lokalna tema upravlja RN/Tamagui/StatusBar shell-om posle korisničke preference hidratacije.
PASS v1.0 Batch50 V2 bootstrap izlaže samo read-only presentation metadata postojećeg NBS authority-ja.
PASS v1.0 Batch50 V2 komercijalni prodajni authority ostaje centralan uz NBS javnu listu primary, Frankfurter secondary i poslednji sacuvani kurs emergency fallback.
PASS v1.0 Batch50 V2 primarna valuta je display-only; canonical RSD payment input i payload ostaju nepromenjeni.
PASS v1.0 Batch50 V2 globalni loading koristi branded Reanimated pulse i skeleton.
PASS v1.0 Batch50 V2 bottom nav drži centralno izdvojenu Početnu i role-aware Admin/Obaveštenja četvrti slot.
PASS v1.0 Batch50 V2 header desno koristi notification bell+badge umesto profila, a Nalog ostaje u bottom nav-u.
PASS v1.0 Batch50 V2 čuva Katalog active context za admin/catalog edit, dok ostali admin ekrani aktiviraju Admin slot.
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
PASS v1.0 Admin Orders PDF/shipment binary putanje su relativne i ne dupliraju /api/v1 prefiks.
PASS v1.0 Admin Order detalj ugrađuje permission-gated Poslovni dokumenti workbench bez orphan ekrana.
PASS v1.0 Mobile document workbench pokriva predračun, račun, otpremnicu, istoriju revizija i kontrolisano storniranje.
PASS v1.0 Admin dokument PDF koristi authenticated Bearer download, PDF signature proveru i privatni cache/share flow.
PASS v1.0 Admin dokumenti ostaju product-only bez Product Variants contracta.
PASS v1.0 Admin Catalog API pokriva server-driven deletion readiness, purge i Total Product Purge.
PASS v1.0 Mobile Product detalj ima postojeći archive/restore plus kontrolisani purge i SuperAdmin Total Product Purge danger-zone workflow.
PASS v1.0 Admin Catalog deletion API reuse-uje postojeće Laravel ProductDeletionService i TotalProductPurgeService ZERO TRACE guardove.
PASS OpenAPI dokumentuje ADMIN-CAT-03 deletion readiness, purge i Total Product Purge ugovor.
PASS v1.0 Admin Catalog purge tok ostaje product-only bez Product Variants contracta.
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
PASS Batch170 Task5 Mobile Customer360 tipovi pokrivaju summary, timeline, CRM note request/response i unlinked buyer ugovor.
PASS Batch170 Task5 AdminPortalUserDetail izlaže typed customer_360 payload iz canonical OpenAPI ugovora.
PASS Batch170 Task5 apiAdminCustomerPortal pokriva unlinked-buyers read i append-only CRM note POST bez paralelnog klijenta.
PASS Batch170 Task5 Customer360 query keys ostaju u centralnom adminQueryKeys customer-portal stablu.
PASS Batch170 Task5 Mobile contract parity ne vraća Product Variants contract.
PASS Batch171 Customer360 lista izlaže read-only nepovezane porudžbine kroz canonical API/query-key bez auto-match toka.
PASS Batch171 Customer360 detalj prikazuje canonical komercijalni summary bez lokalnog finansijskog preračunavanja.
PASS Batch171 Customer360 detalj podržava append-only interne CRM beleške kroz postojeći mutation authority.
PASS Batch171 Customer360 detalj prikazuje server timeline i servisne/komunikacione indikatore.
FAIL Batch171 visible workspace ostaje postojeći Customer Portal authority bez heurističkog matching-a, profitability duplikata ili Product Variants povratka.
PASS Batch172 ManagementReportService ostaje canonical authority za comparison, customer/channel/product profitability i inventory efficiency.
PASS Batch172 customer profitability filter je server-authoritative, a turnover/GMROI eksplicitno ostaju current-inventory proxy bez lazne istorijske prosečne vrednosti.
PASS Batch172 canonical OpenAPI dokumentuje advanced analytics, Customer360 handoff filter i transparentan inventory proxy basis.
PASS Batch172 Mobile reports contract ima matching advanced analytics tipove i customer_user_id request parity pre visible Batch173 UI.
PASS Batch172 centralni query keys pripremaju Customer360 profitability handoff bez novog API namespace-a.
PASS Batch172 advanced analytics ne vraca Product Variants niti uvodi paralelni analytics API namespace.
PASS Batch173 Reports UI prikazuje server-computed prethodni period i delta metrike bez paralelnog analytics izvora.
PASS Batch173 Reports UI prikazuje customer/LTV i sales-channel profitabilnost direktno iz ManagementReportService payload-a.
PASS Batch173 Reports UI prikazuje server-ranked top/bottom product profitability bez lokalnog sortiranja ili preračunavanja.
PASS Batch173 Inventory UI prikazuje turnover/GMROI uz eksplicitnu current-inventory proxy napomenu.
PASS Batch173 Customer360 profitability koristi postojeći reports.view + management endpoint + customer_user_id authority.
FAIL Batch173 Mobile UI samo formatira server profitability vrednosti i ne vraća lokalne formule, /analytics namespace ili Product Variants.
PASS v1.0 USER-02 Mobile API pokriva User Groups list/create/update/delete relativni canonical ugovor.
PASS v1.0 USER-02 Mobile ekran pokriva permission, category scope, status, sort i bezbedni delete workflow.
PASS v1.0 USER-02 Admin Hub drži Grupe pristupa u organizovanoj Korisnici sekciji.
PASS v1.0 USER-02 TanStack query keys su centralizovani.
PASS v1.0 USER-02 API rute dele system.manage_users granicu i puni CRUD surface.
PASS v1.0 USER-02 Web i Mobile API dele isti UserGroupAdminService i AdminUserGroupRequest authority.
PASS v1.0 USER-02 shared servis čuva permission/category sync i blokira brisanje grupe sa korisnicima.
PASS OpenAPI dokumentuje kompletan USER-02 User Groups CRUD ugovor.
PASS v1.0 USER-02 parity ne vraća Product Variants contract.
PASS v1.0 Batch97 Admin Audit list is organized into overview, events, filters, export and security-policy workspaces.
PASS v1.0 Batch97 Audit list UX preserves security.view, server filters, pagination, safe detail, secure CSV and read-only capability contracts.
PASS v1.0 Batch97 Audit UX preserves date validation, sanitized payload disclosure and explicit no-mutation policy.
PASS v1.0 Batch97 Audit list UX does not restore Product Variants contract.
PASS v1.0 Batch96 Admin Field Operations list is organized into overview, work orders, filters, unassigned and teams workspaces.
PASS v1.0 Batch96 Field Operations list UX preserves permission, server filters, pagination, virtualized list and detail routing contracts.
PASS v1.0 Batch96 Field Operations focus workspaces reuse existing server unassigned and team filter semantics without parallel business logic.
PASS v1.0 Batch96 Field Operations list UX does not restore Product Variants contract.
PASS v1.0 Batch95 Admin After-sales list is organized into overview, cases, filters, overdue and execution workspaces.
PASS v1.0 Batch95 After-sales list UX preserves permission, server filters, pagination, virtualized list and detail routing contracts.
PASS v1.0 Batch95 attention workspaces reuse existing server overdue and execution_pending semantics without parallel business logic.
PASS v1.0 Batch95 After-sales list UX does not restore Product Variants contract.
PASS v1.0 Batch94 Admin Warranties list is organized into overview, warranties, filters, maintenance and rules workspaces.
PASS v1.0 Batch94 Warranties list UX preserves permission, server list filters, pagination, virtualized list, detail routing and Rules capability contracts.
PASS v1.0 Batch94 Warranties list UX does not restore Product Variants contract.
PASS v1.0 Batch93 Admin Orders list is organized into overview, orders, filters, attention and archive workspaces.
PASS v1.0 Batch93 Orders list UX preserves permission, filters, attention, pagination, detail, archive and server capability contracts.
PASS v1.0 Batch93 Orders list UX does not restore Product Variants contract.
PASS v1.0 Batch156 Admin Commission detail uses one single-page breakdown, approval, payout and history workflow.
PASS v1.0 Batch156 Commission detail preserves permission, server transitions, payout validation, confirmation, history and query invalidation contracts.
PASS v1.0 Batch156 Commission detail UX does not restore Product Variants contract.
PASS v1.0 Batch156 Admin Commissions list opens directly on commissions with compact summary, order value and existing tools.
PASS v1.0 Batch156 Commissions list UX preserves permission, filters, pagination, secure exports, bulk payment and server capability contracts.
PASS v1.0 Batch156 Commissions list UX does not restore Product Variants contract.
PASS v1.0 Batch90 Admin Warranty Rules is organized into overview, rules, editor and backfill workspaces.
PASS v1.0 Batch90 Warranty Rules UX preserves permission, scope, create, update, backfill, query invalidation and confirmation contracts.
PASS v1.0 Batch90 Warranty Rules UX does not restore Product Variants contract.
PASS v1.0 Batch89 Admin Receivables list is organized into overview, cases, filters, operations and settings workspaces.
PASS v1.0 Batch89 Receivables list UX preserves permission, filters, pagination, CSV, settings, scan and server capability contracts.
PASS v1.0 Batch89 Receivables list UX does not restore Product Variants contract.
PASS v1.0 Batch88 Admin Receivables detail is organized into overview, case, plan, payments, communication, reminders and audit workspaces.
PASS v1.0 Batch88 Receivables UX preserves permission, server capabilities, installment validation, contact visibility and outbox reminder contracts.
PASS v1.0 Batch88 Receivables UX does not restore Product Variants contract.
PASS v1.0 Batch87 Admin Service Parts je organizovan u Pregled, Delovi, Novi deo, Korekcije i Nabavka radne prostore.
PASS v1.0 Batch87 Service Parts UX čuva view/manage/procurement permission, CRUD, movement-ledger adjustment, idempotency i procurement poslovni ugovor.
PASS v1.0 Batch87 Service Parts UX ne vraća Product Variants contract.
PASS v1.0 Batch86 Admin Order detalj je organizovan u Pregled, Kupac i stavke, Isporuka, Finansije, Dokumenti i Tok i akcije radne prostore.
PASS v1.0 Batch86 Order UX čuva orders.manage, server-driven workflow, dokumente, archive i capability poslovni ugovor.
PASS v1.0 Batch86 Order UX ne vraća Product Variants contract.
PASS v1.0 Batch85 Admin Warranty detalj je organizovan u Pregled, Podaci, Održavanje, Dokument i Poništavanje radne prostore.
PASS v1.0 Batch85 Warranty UX čuva warranties.manage, update, void, maintenance i secure PDF poslovni ugovor.
PASS v1.0 Batch85 Warranty UX ne vraća Product Variants contract.
PASS v1.0 Batch84 Admin Field Operations detalj je organizovan u Pregled, Planiranje, Izvršenje, Delovi i Dokumentacija radne prostore.
PASS v1.0 Batch84 Field Operations UX čuva permission, schedule, en-route, on-site, complete, cancel, parts i secure attachment poslovni ugovor.
PASS v1.0 Batch84 Field Operations UX ne vraća Product Variants contract.
PASS v1.0 Batch83 Admin After-sales detalj je organizovan u Pregled, Slučaj, Komunikacija, Radnje i Istorija radne prostore.
PASS v1.0 Batch83 After-sales UX čuva postojeći permission, update, message, attachment, execute, complete, cancel i refund capability poslovni ugovor.
PASS v1.0 Batch83 After-sales UX ne vraća Product Variants contract.
PASS v1.0 Batch82 Admin Inventory razdvaja monolitni ekran u permission-aware radne prostore Pregled, Stanje, Prijem, Popis i Promene.
PASS v1.0 Batch82 Inventory UX čuva postojeće permission, idempotency, adjustment, receipt, count i CSV poslovne tokove.
PASS v1.0 Batch82 Inventory UX ne vraća Product Variants contract.
PASS v1.0 EUR/RSD provider chain je NBS javna prodajna lista primary -> Frankfurter secondary -> poslednji uspesno sacuvan kurs emergency fallback.
PASS NBS javna lista i Frankfurter timeouts koriste credential-free server env/config bez SOAP tajni.
PASS Web i Mobile jasno prikazuju NBS javnu listu primary, Frankfurter secondary i sacuvani kurs emergency fallback semantiku.
PASS Batch116 expo-image je SDK57-kompatibilan i zaključan u package/lock authority.
PASS Batch116 Mobile tipovi odvajaju original, display i thumbnail image URL-ove.
PASS Batch116 katalog i galerija koriste thumbnail/display derivatives, expo-image cache i horizontalnu virtualizaciju.
PASS Batch116 Download je zaključan isključivo na canonical original full-quality URL.
PASS Batch116 edit/create preview, product detail i cart koriste optimizovan image presentation path.
PASS Batch116 backend pravi odvojene WebP derivatives, čuva original i automatski osvežava cache nakon upload/clone/rotate.
PASS Batch116 image performance rad ne vraća Product Variants contract.

Ukupno FAIL: 2
RC_mobile_validator_green=1

============================================================
FINAL SUMMARY - FAIL
============================================================
BATCH173_RESULT=FAIL
REPORT_NUMBER=473
FAILED_STAGE=TDD_GREEN
FAIL_MESSAGE=Mobile validator failed after Batch173 UI
BATCH172_OVERALL=PASS_FROM_REPORT472
ADVANCED_ANALYTICS_BACKEND=PASS_PRESERVED
MOBILE_VISIBLE_ANALYTICS_UI=NOT_CERTIFIED
CUSTOMER360_PROFITABILITY_UI=NOT_CERTIFIED
EAS_COMMANDS_RUN=NO
OTA_ACTION=NO
BUILD_ACTION=NO
GOOGLE_PLAY_ACTION=NO
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/473-BATCH173-ADVANCED-ANALYTICS-MOBILE-UI-CUSTOMER360-PROFITABILITY-20260922-142555.md
