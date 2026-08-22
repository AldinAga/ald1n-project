# Mobile v0.9.0 Admin Catalog productList hotfix V4

- Timestamp: `20260822-100256`
- RESULT: `PASS`
- Reason: `CERTIFIED_HELPER_RESTORED_AND_RUNTIME_VERIFIED_WITH_POST_REBIND_ACTOR_RESOLVER`
- Request ID: `bcba8ecb-9188-41cc-b478-6a9a2ec5e5d1`
- Stage: `FINAL`
- Source mode: `MISSING_CERTIFIED_HELPER_REPAIR`
- Controller source mutation: `YES`
- Rollback: `NOT_REQUIRED_CERTIFIED`
- Business DB writes expected: `0`
- Migration: `NO`
- Dependency install: `NO`
- Mobile source mutation: `NO`
- EAS build: `NO`
- Git/GitHub checkpoint: `NO`
- Backup: `/home/icaffeco/backups/releases/mobile-v0.9.0-admin-catalog-productlist-hotfix-v4-20260822-100256`

## Root cause

- `CatalogProductController@index()` and `archived()` call `productList()`, but the certified Batch 8 helper was missing from the current controller.
- The correlated production error was `Call to undefined method ... CatalogProductController::productList()` for request `bcba8ecb-9188-41cc-b478-6a9a2ec5e5d1`.
- V4 restores only the exact certified Batch 8 helper block and preserves later Image Manager and Direct Sale additions.
- V3 reached the controller, but its probe set the synthetic request user resolver before binding the request into Laravel. Laravel request rebinding replaced that resolver, so `actor()` correctly returned HTTP 401 in the synthetic probe.
- V4 binds each request first, then reapplies and verifies the actor resolver before active list, archived list and detail runtime checks.

## Preflight helper state

```text
PRODUCTLIST_COUNT=0
SUMMARY_PAYLOAD_COUNT=0
DETAIL_PAYLOAD_COUNT=0
ACTOR_COUNT=0
```

## Patch

```text
PATCH_MODE=RESTORED_4_MISSING_HELPERS
PRODUCTLIST_COUNT_AFTER=1
SUMMARYPAYLOAD_COUNT_AFTER=1
DETAILPAYLOAD_COUNT_AFTER=1
ACTOR_COUNT_AFTER=1
```

## PHP lint

```text
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php
```

## Read-only runtime probe

```text
RUNTIME_PROBE_PROCESS_STARTED
RUNTIME_STAGE=AUTOLOAD_REQUIRE
RUNTIME_STAGE=AUTOLOAD_REQUIRE_PASS
RUNTIME_CWD=/home/icaffeco/ald1n-project/apps/cms/current
RUNTIME_STAGE=BOOTSTRAP_REQUIRE
RUNTIME_STAGE=BOOTSTRAP_REQUIRE_PASS
RUNTIME_STAGE=CONSOLE_BOOTSTRAP
RUNTIME_STAGE=CONSOLE_BOOTSTRAP_PASS
RUNTIME_STAGE=ACTOR_LOOKUP
RUNTIME_ACTOR_ID=1
RUNTIME_ACTOR_CATALOG_MANAGE=YES
RUNTIME_STAGE=CONTROLLER_RESOLVE
RUNTIME_STAGE=CONTROLLER_RESOLVE_PASS
RUNTIME_STAGE=ACTIVE_LIST
ACTIVE_LIST_REQUEST_ACTOR_RESOLVER=PASS
ACTIVE_LIST_RUNTIME=PASS
ACTIVE_LIST_PAGE_COUNT=20
ACTIVE_LIST_TOTAL=30
RUNTIME_STAGE=ARCHIVED_LIST
ARCHIVED_LIST_REQUEST_ACTOR_RESOLVER=PASS
ARCHIVED_LIST_RUNTIME=PASS
ARCHIVED_LIST_PAGE_COUNT=0
ARCHIVED_LIST_TOTAL=0
RUNTIME_STAGE=DETAIL
DETAIL_REQUEST_ACTOR_RESOLVER=PASS
DETAIL_RUNTIME=PASS
DETAIL_PRODUCT_ID=35
DATABASE_WRITES_DURING_RUNTIME_PROBE=0
RUNTIME_PROBE_FINAL=PASS
```

## Route contract

```text

  GET|HEAD  api/v1/admin/catalog/products ................................................ api.v1.admin.catalog.products.index › Api\V1\Admin\CatalogProductController@index
  POST      api/v1/admin/catalog/products ................................................ api.v1.admin.catalog.products.store › Api\V1\Admin\CatalogProductController@store
  GET|HEAD  api/v1/admin/catalog/products/archived ................................. api.v1.admin.catalog.products.archived › Api\V1\Admin\CatalogProductController@archived
  GET|HEAD  api/v1/admin/catalog/products/{product} ........................................ api.v1.admin.catalog.products.show › Api\V1\Admin\CatalogProductController@show
  PUT       api/v1/admin/catalog/products/{product} .................................... api.v1.admin.catalog.products.update › Api\V1\Admin\CatalogProductController@update
  POST      api/v1/admin/catalog/products/{product}/archive .......................... api.v1.admin.catalog.products.archive › Api\V1\Admin\CatalogProductController@archive
  POST      api/v1/admin/catalog/products/{product}/direct-sale ......... api.v1.admin.catalog.products.direct-sale.store › Api\V1\Admin\CatalogProductController@directSale
  GET|HEAD  api/v1/admin/catalog/products/{product}/direct-sale/options api.v1.admin.catalog.products.direct-sale.options › Api\V1\Admin\CatalogProductController@directSal…
  GET|HEAD  api/v1/admin/catalog/products/{product}/images ................... api.v1.admin.catalog.products.images.index › Api\V1\Admin\CatalogProductController@imageIndex
  POST      api/v1/admin/catalog/products/{product}/images ....................... api.v1.admin.catalog.products.images.store › Api\V1\Admin\CatalogProductController@images
  POST      api/v1/admin/catalog/products/{product}/images/reorder ....... api.v1.admin.catalog.products.images.reorder › Api\V1\Admin\CatalogProductController@imageReorder
  DELETE    api/v1/admin/catalog/products/{product}/images/{image} ....... api.v1.admin.catalog.products.images.destroy › Api\V1\Admin\CatalogProductController@imageDestroy
  POST      api/v1/admin/catalog/products/{product}/images/{image}/primary api.v1.admin.catalog.products.images.primary › Api\V1\Admin\CatalogProductController@imagePrimary
  POST      api/v1/admin/catalog/products/{product}/images/{image}/rotate .. api.v1.admin.catalog.products.images.rotate › Api\V1\Admin\CatalogProductController@imageRotate
  POST      api/v1/admin/catalog/products/{product}/restore .......................... api.v1.admin.catalog.products.restore › Api\V1\Admin\CatalogProductController@restore

                                                                                                                                                         Showing [15] routes

```

## CMS static check tail

```text
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
```

## OpenAPI immutability

```text
OPENAPI_CANONICAL_SHA=7dd499215ff54409f27fb2c95c5b1c61ccb451355358a10940f0e15176131f55
OPENAPI_CMS_SHA=7dd499215ff54409f27fb2c95c5b1c61ccb451355358a10940f0e15176131f55
OPENAPI_MOBILE_SHA=7dd499215ff54409f27fb2c95c5b1c61ccb451355358a10940f0e15176131f55
OPENAPI_PARITY=PASS_3_COPIES
OPENAPI_UNCHANGED=PASS
```

## Final

- ADMIN_CATALOG_PRODUCTLIST_HOTFIX_V4: `PASS`
- ADMIN_CATALOG_ACTIVE_LIST_RUNTIME: `PASS`
- ADMIN_CATALOG_ARCHIVED_LIST_RUNTIME: `PASS`
- ADMIN_CATALOG_DETAIL_RUNTIME: `PASS_OR_NO_ACTIVE_ITEM`
- DATABASE_WRITES_DURING_RUNTIME_PROBE: `0`
- CMS_STATIC_CHECK: `PASS`
- OPENAPI_UNCHANGED: `PASS`
- EAS_BUILD_REQUIRED_FOR_THIS_FIX: `NO`
- NEXT_ACTION: `RETEST_ADMINISTRATION_CATALOG_ITEMS_ON_CURRENT_PLAY_BUILD`
