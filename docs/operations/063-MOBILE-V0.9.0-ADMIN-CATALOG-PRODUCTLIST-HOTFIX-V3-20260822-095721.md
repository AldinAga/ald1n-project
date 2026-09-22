# Mobile v0.9.0 Admin Catalog productList hotfix V3

- Timestamp: `20260822-095721`
- RESULT: `FAIL`
- Reason: `ADMIN_CATALOG_RUNTIME_PROBE_V3_FAILED`
- Request ID: `bcba8ecb-9188-41cc-b478-6a9a2ec5e5d1`
- Stage: `READ_ONLY_RUNTIME_PROBE`
- Source mode: `MISSING_CERTIFIED_HELPER_REPAIR`
- Controller source mutation: `YES`
- Rollback: `PASS_RESTORED_CONTROLLER`
- Business DB writes expected: `0`
- Migration: `NO`
- Dependency install: `NO`
- Mobile source mutation: `NO`
- EAS build: `NO`
- Git/GitHub checkpoint: `NO`
- Backup: `/home/icaffeco/backups/releases/mobile-v0.9.0-admin-catalog-productlist-hotfix-v3-20260822-095721`

## Root cause

- `CatalogProductController@index()` and `archived()` call `productList()`, but the certified Batch 8 helper was missing from the current controller.
- The correlated production error was `Call to undefined method ... CatalogProductController::productList()` for request `bcba8ecb-9188-41cc-b478-6a9a2ec5e5d1`.
- V3 restores only the exact certified Batch 8 helper block and preserves later Image Manager and Direct Sale additions.
- V2 did not reach Laravel boot: its standalone probe required bootstrap/app.php before vendor/autoload.php, so Illuminate\Foundation\Application was unavailable.
- V3 fixes the probe itself, binds each synthetic HTTP request into the Laravel container, and only certifies after active list, archived list and detail runtime checks.

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
RUNTIME_EXCEPTION_CLASS=Symfony\Component\HttpKernel\Exception\HttpException
RUNTIME_EXCEPTION_MESSAGE=
RUNTIME_EXCEPTION_FILE=/home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php
RUNTIME_EXCEPTION_LINE=1440
RUNTIME_EXCEPTION_TRACE_BEGIN
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/helpers.php(67): Illuminate\Foundation\Application->abort()
#1 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/helpers.php(104): abort()
#2 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php(740): abort_unless()
#3 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php(596): App\Http\Controllers\Api\V1\Admin\CatalogProductController->actor()
#4 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php(35): App\Http\Controllers\Api\V1\Admin\CatalogProductController->productList()
#5 /tmp/ald1n-admin-catalog-hotfix.hPUEmi/runtime.php(89): App\Http\Controllers\Api\V1\Admin\CatalogProductController->index()
#6 /tmp/ald1n-admin-catalog-hotfix.hPUEmi/runtime.php(112): {closure:/tmp/ald1n-admin-catalog-hotfix.hPUEmi/runtime.php:84}()
#7 {main}
RUNTIME_EXCEPTION_TRACE_END
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

```\n