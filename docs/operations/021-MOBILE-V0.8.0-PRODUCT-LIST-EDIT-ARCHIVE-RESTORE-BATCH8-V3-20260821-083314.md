# Mobile v0.8.0 Product List/Edit/Archive/Restore - Batch 8 V3

- Timestamp: `20260821-083314`
- RESULT: `FAIL`
- Exit code: `1`
- Failure: `MOBILE_PROJECT_VALIDATOR_FAILED`
- Stage: `MOBILE_PROJECT_VALIDATOR`
- Failed line: `113`
- Rollback: `ATTEMPTED`
- EAS build: `NO`
- Git/GitHub checkpoint: `NO`
- Mandatory extension parity remains: `14%` until this batch passes.

## Mobile typecheck diagnostics

```text

> ald1n-mobile@0.8.0 typecheck
> tsc --noEmit

```

## Mobile validator diagnostics

```text

> ald1n-mobile@0.8.0 validate
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
PASS Aplikaciona package verzija je 0.8.0.
PASS package-lock release verzija je 0.8.0.
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
PASS v0.8 Expo compatibility matrix zaključava expo na ~57.0.15.
PASS v0.8 Expo compatibility matrix zaključava expo-constants na ~57.0.13.
PASS v0.8 Expo compatibility matrix zaključava expo-dev-client na ~57.0.14.
PASS v0.8 Expo compatibility matrix zaključava expo-file-system na ~57.0.5.
PASS v0.8 Expo compatibility matrix zaključava expo-linking na ~57.0.7.
PASS v0.8 Expo compatibility matrix zaključava expo-notifications na ~57.0.13.
PASS v0.8 Expo compatibility matrix zaključava expo-router na ~57.0.15.
PASS v0.8 Expo compatibility matrix zaključava expo-sharing na ~57.0.14.
PASS v0.8 Expo compatibility matrix zaključava expo-updates na ~57.0.16.
PASS Static colors consumeri su uklonjeni iz aplikacionog source-a.
FAIL Legacy colors.* usage postoji: src/app/(app)/admin/catalog/[id].tsx, src/app/(app)/admin/catalog/index.tsx
PASS Unsafe as never / as unknown as castovi ne postoje u source-u.
FAIL 117 TypeScript/TSX fajlova prolazi sintaksnu proveru.
PASS app.config.ts prolazi TypeScript sintaksnu proveru.
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
PASS Expo app verzija je 0.8.0.
PASS Expo display naziv je Ald1n CMS bez Preview suffixa.
PASS Ald1n V2 logo je canonical icon/adaptive/splash/favicon asset.
PASS App runtime version fallback je 0.8.0.
PASS Account version fallback je 0.8.0.
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

Ukupno FAIL: 2
```
