# Mobile v0.9.0 Admin Catalog request-id diagnostic

- Timestamp: `20260822-092747`
- Request ID: `bcba8ecb-9188-41cc-b478-6a9a2ec5e5d1`
- Mode: `READ_ONLY`
- Source mutation: `NO`
- Database writes: `NO`
- Dependency install: `NO`
- EAS build: `NO`

## Request ID correlation

- REQUEST_ID_MATCH_COUNT: `1`
- REQUEST_ID_CORRELATION: `PASS_FOUND`

```text
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log:160:[2026-08-22 09:23:30] production.ERROR: Call to undefined method App\Http\Controllers\Api\V1\Admin\CatalogProductController::productList() {"request_id":"bcba8ecb-9188-41cc-b478-6a9a2ec5e5d1","userId":1,"exception":"[object] (Error(code: 0): Call to undefined method App\\Http\\Controllers\\Api\\V1\\Admin\\CatalogProductController::productList() at /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:35)
```

## Correlated log context

```text
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-125-#16 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-126-#17 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-127-#18 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-128-#19 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-129-#20 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-130-#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-131-#22 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-132-#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-133-#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-134-#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-135-#26 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-136-#27 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-137-#28 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-138-#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-139-#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-140-#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-141-#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(74): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-142-#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-143-#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-144-#35 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-145-#36 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-146-#37 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-147-#38 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(28): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-148-#39 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-149-#40 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureRuntimeDirectories.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-150-#41 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureRuntimeDirectories->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-151-#42 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/AttachRequestId.php(25): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-152-#43 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AttachRequestId->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-153-#44 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-154-#45 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-155-#46 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-156-#47 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-157-#48 /home/icaffeco/ald1n-project/apps/cms/current/public/index.php(19): Illuminate\\Foundation\\Application->handleRequest()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-158-#49 {main}
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-159-"}
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log:160:[2026-08-22 09:23:30] production.ERROR: Call to undefined method App\Http\Controllers\Api\V1\Admin\CatalogProductController::productList() {"request_id":"bcba8ecb-9188-41cc-b478-6a9a2ec5e5d1","userId":1,"exception":"[object] (Error(code: 0): Call to undefined method App\\Http\\Controllers\\Api\\V1\\Admin\\CatalogProductController::productList() at /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:35)
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-161-[stacktrace]
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-162-#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php(46): App\\Http\\Controllers\\Api\\V1\\Admin\\CatalogProductController->index()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-163-#1 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Route.php(276): Illuminate\\Routing\\ControllerDispatcher->dispatch()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-164-#2 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Route.php(216): Illuminate\\Routing\\Route->runController()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-165-#3 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(822): Illuminate\\Routing\\Route->run()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-166-#4 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->{closure:Illuminate\\Routing\\Router::runRouteWithinStack():821}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-167-#5 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/RequirePermission.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-168-#6 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\RequirePermission->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-169-#7 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureActiveUser.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-170-#8 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureActiveUser->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-171-#9 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-172-#10 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-173-#11 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-174-#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-175-#13 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-176-#14 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-177-#15 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-178-#16 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-179-#17 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-180-#18 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-181-#19 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-182-#20 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-183-#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-184-#22 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-185-#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-186-#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-187-#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-188-#26 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-189-#27 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-190-#28 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-191-#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-192-#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-193-#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-194-#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(74): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-22.log-195-#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
```

## Admin Catalog route contract

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

## Static source sentinels

- PRESENT: `/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php`
- PRESENT: `/home/icaffeco/ald1n-project/apps/cms/current/app/Models/Product.php`
- PRESENT: `/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php`
- PRESENT: `/home/icaffeco/ald1n-project/apps/mobile/current/src/features/admin/catalog-admin-api.ts`
- PRESENT: `/home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/catalog/index.tsx`

### Controller list implementation

```text
```

### Product relationship definitions

```text
```

### Mobile Admin Catalog API

```text
162-    page: params.page,
163-    per_page: params.per_page,
164-  });
165-}
166-
167-// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8
168-// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8_V2
169-// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8_V3
170-// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8_V4
171-export const apiAdminCatalog = {
172:  list: (params: AdminCatalogProductListParams = {}, archived = false) =>
173-    apiRequest<AdminCatalogProductListResponse> (
174:      `admin/catalog/products${archived ? '/archived' : ''}${listQuery(params)}`,
175-    ),
176-  detail: (productId: number) =>
177:    apiRequest<{ data: AdminCatalogProductDetail }> (`admin/catalog/products/${productId}`),
178-  update: (productId: number, input: AdminCatalogProductUpdateInput) =>
179:    apiRequest<AdminCatalogProductMutationResponse> (`admin/catalog/products/${productId}`, {
180-      method: 'PUT',
181-      body: input,
182-    }),
183-  archive: (productId: number) =>
184:    apiRequest<AdminCatalogProductMutationResponse> (`admin/catalog/products/${productId}/archive`, {
185-      method: 'POST',
186-    }),
187-  restore: (productId: number) =>
188:    apiRequest<AdminCatalogProductMutationResponse> (`admin/catalog/products/${productId}/restore`, {
189-      method: 'POST',
190-    }),
191-  // MOBILE_V0_8_SUPERADMIN_DIRECT_SALE_BATCH10
192-  directSaleOptions: async (productId: number) => {
193-    const response = await apiRequest<{ data: AdminDirectSaleOptions }> (
194:      `admin/catalog/products/${productId}/direct-sale/options`,
195-    );
196-    return response.data;
197-  },
198-  recordDirectSale: (productId: number, input: AdminDirectSaleInput) =>
199-    apiRequest<AdminDirectSaleResponse> (
200:      `admin/catalog/products/${productId}/direct-sale`,
201-      { method: 'POST', body: input },
202-    ),
203-  // MOBILE_V0_8_SHARED_PRODUCT_IMAGE_MANAGER_BATCH9
204-  images: (productId: number) =>
205:    apiRequest<AdminProductImageCollectionResponse> (`admin/catalog/products/${productId}/images`),
206-  setPrimaryImage: (productId: number, imageId: number) =>
207-    apiRequest<AdminProductImageMutationResponse> (
208:      `admin/catalog/products/${productId}/images/${imageId}/primary`,
209-      { method: 'POST' },
210-    ),
211-  rotateImage: (productId: number, imageId: number, degrees: 90 | 180 | 270) =>
212-    apiRequest<AdminProductImageMutationResponse> (
213:      `admin/catalog/products/${productId}/images/${imageId}/rotate`,
214-      { method: 'POST', body: { degrees } },
215-    ),
216-  reorderImages: (productId: number, imageIds: number[]) =>
217-    apiRequest<AdminProductImageMutationResponse> (
218:      `admin/catalog/products/${productId}/images/reorder`,
219-      { method: 'POST', body: { image_ids: imageIds } },
220-    ),
221-  deleteImage: (productId: number, imageId: number) =>
222-    apiRequest<AdminProductImageMutationResponse> (
223:      `admin/catalog/products/${productId}/images/${imageId}`,
224-      { method: 'DELETE' },
225-    ),
226-};
```

## Recent Laravel error tail

```text
LOG=/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/scheduler.log
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:46:03 Running ['artisan' app:scheduler-heartbeat]  444.83ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 08:47:02 Running ['artisan' app:order-email-dispatch]  475.99ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 08:47:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  393.96ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:47:03 Running ['artisan' app:scheduler-heartbeat]  420.68ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 08:48:02 Running ['artisan' app:order-email-dispatch]  495.87ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 08:48:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  560.14ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:48:03 Running ['artisan' app:scheduler-heartbeat]  506.19ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 08:49:02 Running ['artisan' app:order-email-dispatch]  509.29ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 08:49:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  397.51ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:49:03 Running ['artisan' app:scheduler-heartbeat]  345.54ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 08:50:02 Running ['artisan' app:order-email-dispatch]  521.48ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 08:50:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  429.64ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:50:03 Running ['artisan' app:management-report-dispatch --limit=100]  491.88ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:management-report-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:50:04 Running ['artisan' app:scheduler-heartbeat]  489.51ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 08:51:02 Running ['artisan' app:order-email-dispatch]  468.99ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 08:51:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  370.76ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:51:02 Running ['artisan' app:scheduler-heartbeat]  361.96ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 08:52:02 Running ['artisan' app:order-email-dispatch]  627.03ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 08:52:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  617.18ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:52:03 Running ['artisan' app:scheduler-heartbeat]  605.81ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 08:53:01 Running ['artisan' app:order-email-dispatch]  402.83ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 08:53:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  396.17ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:53:02 Running ['artisan' app:scheduler-heartbeat]  374.75ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 08:54:02 Running ['artisan' app:order-email-dispatch]  577.25ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 08:54:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  512.66ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:54:03 Running ['artisan' app:scheduler-heartbeat]  365.61ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 08:55:02 Running ['artisan' app:order-email-dispatch]  393.08ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 08:55:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  456.15ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:55:03 Running ['artisan' app:management-report-dispatch --limit=100]  459.69ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:management-report-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:55:03 Running ['artisan' app:scheduler-heartbeat]  497.13ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 08:56:02 Running ['artisan' app:order-email-dispatch]  372.18ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 08:56:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  448.14ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:56:02 Running ['artisan' app:scheduler-heartbeat]  498.02ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 08:57:02 Running ['artisan' app:order-email-dispatch]  380.42ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 08:57:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  432.65ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:57:03 Running ['artisan' app:scheduler-heartbeat]  418.93ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 08:58:02 Running ['artisan' app:order-email-dispatch]  445.19ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 08:58:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  384.30ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:58:03 Running ['artisan' app:scheduler-heartbeat]  365.07ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 08:59:01 Running ['artisan' app:order-email-dispatch]  351.86ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 08:59:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  371.48ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 08:59:02 Running ['artisan' app:scheduler-heartbeat]  345.83ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:00:03 Running ['artisan' app:order-email-dispatch]  669.22ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:00:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  629.83ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:00:04 Running ['artisan' app:management-report-dispatch --limit=100]  597.12ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:management-report-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:00:04 Running ['artisan' app:scheduler-heartbeat]  573.53ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:01:02 Running ['artisan' app:order-email-dispatch]  506.66ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:01:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  485.53ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:01:03 Running ['artisan' app:scheduler-heartbeat]  463.31ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:02:02 Running ['artisan' app:order-email-dispatch]  381.44ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:02:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  365.64ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:02:03 Running ['artisan' app:scheduler-heartbeat]  461.63ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:03:01 Running ['artisan' app:order-email-dispatch]  383.57ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:03:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  360.64ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:03:02 Running ['artisan' app:scheduler-heartbeat]  360.62ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:04:02 Running ['artisan' app:order-email-dispatch]  463.42ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:04:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  420.03ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:04:03 Running ['artisan' app:scheduler-heartbeat]  401.27ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:05:02 Running ['artisan' app:order-email-dispatch]  429.05ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:05:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  393.83ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:05:03 Running ['artisan' app:management-report-dispatch --limit=100]  364.84ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:management-report-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:05:03 Running ['artisan' app:scheduler-heartbeat]  402.38ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:06:02 Running ['artisan' app:order-email-dispatch]  534.45ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:06:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  477.92ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:06:03 Running ['artisan' app:scheduler-heartbeat]  524.61ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:07:01 Running ['artisan' app:order-email-dispatch]  503.98ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:07:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  390.68ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:07:02 Running ['artisan' app:scheduler-heartbeat]  468.63ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:08:01 Running ['artisan' app:order-email-dispatch]  573.78ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:08:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  387.98ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:08:02 Running ['artisan' app:scheduler-heartbeat]  360.45ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:09:02 Running ['artisan' app:order-email-dispatch]  402.61ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:09:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  380.97ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:09:03 Running ['artisan' app:scheduler-heartbeat]  407.45ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:10:02 Running ['artisan' app:automation-run] ... 614.11ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:automation-run > '/dev/null' 2>&1
  2026-08-22 09:10:03 Running ['artisan' app:order-email-dispatch]  440.98ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:10:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  377.62ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:10:03 Running ['artisan' app:management-report-dispatch --limit=100]  384.71ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:management-report-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:10:04 Running ['artisan' app:scheduler-heartbeat]  363.16ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:11:01 Running ['artisan' app:order-email-dispatch]  456.11ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:11:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  422.61ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:11:02 Running ['artisan' app:scheduler-heartbeat]  387.36ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:12:02 Running ['artisan' app:order-email-dispatch]  591.26ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:12:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  488.98ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:12:03 Running ['artisan' app:scheduler-heartbeat]  444.56ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:13:01 Running ['artisan' app:order-email-dispatch]  414.32ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:13:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  470.69ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:13:02 Running ['artisan' app:scheduler-heartbeat]  473.33ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:14:02 Running ['artisan' app:order-email-dispatch]  484.82ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:14:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  418.32ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:14:03 Running ['artisan' app:scheduler-heartbeat]  420.26ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:15:02 Running ['artisan' app:order-email-dispatch]  396.92ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:15:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  401.80ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:15:02 Running ['artisan' app:management-report-dispatch --limit=100]  464.07ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:management-report-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:15:03 Running ['artisan' app:scheduler-heartbeat]  380.68ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:16:01 Running ['artisan' app:order-email-dispatch]  503.70ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:16:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  374.47ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:16:02 Running ['artisan' app:scheduler-heartbeat]  465.77ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:17:02 Running ['artisan' app:order-email-dispatch]  435.88ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:17:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  394.86ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:17:02 Running ['artisan' app:scheduler-heartbeat]  370.59ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:18:02 Running ['artisan' app:order-email-dispatch]  554.41ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:18:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  479.38ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:18:03 Running ['artisan' app:scheduler-heartbeat]  460.96ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:19:02 Running ['artisan' app:order-email-dispatch]  376.63ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:19:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  389.96ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:19:02 Running ['artisan' app:scheduler-heartbeat]  512.15ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:20:02 Running ['artisan' app:order-email-dispatch]  559.80ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:20:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  535.13ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:20:04 Running ['artisan' app:management-report-dispatch --limit=100]  613.35ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:management-report-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:20:04 Running ['artisan' app:scheduler-heartbeat]  478.65ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:21:02 Running ['artisan' app:order-email-dispatch]  522.22ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:21:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  563.71ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:21:03 Running ['artisan' app:scheduler-heartbeat]  489.23ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:22:02 Running ['artisan' app:order-email-dispatch]  477.19ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:22:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  413.93ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:22:03 Running ['artisan' app:scheduler-heartbeat]  488.87ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:23:02 Running ['artisan' app:order-email-dispatch]  387.53ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:23:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  423.14ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:23:03 Running ['artisan' app:scheduler-heartbeat]  420.89ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:24:02 Running ['artisan' app:order-email-dispatch]  501.10ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:24:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  470.26ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:24:03 Running ['artisan' app:scheduler-heartbeat]  426.34ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:25:02 Running ['artisan' app:order-email-dispatch]  430.03ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:25:02 Running ['artisan' app:mobile-push-dispatch --limit=100]  431.59ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:25:02 Running ['artisan' app:management-report-dispatch --limit=100]  413.55ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:management-report-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:25:03 Running ['artisan' app:scheduler-heartbeat]  374.25ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:26:02 Running ['artisan' app:order-email-dispatch]  422.17ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:26:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  376.42ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:26:03 Running ['artisan' app:scheduler-heartbeat]  440.25ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1


  2026-08-22 09:27:02 Running ['artisan' app:order-email-dispatch]  434.24ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:order-email-dispatch > '/dev/null' 2>&1
  2026-08-22 09:27:03 Running ['artisan' app:mobile-push-dispatch --limit=100]  368.91ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:mobile-push-dispatch --limit=100 > '/dev/null' 2>&1
  2026-08-22 09:27:03 Running ['artisan' app:scheduler-heartbeat]  388.78ms DONE
  ⇂ '/opt/alt/php84/usr/bin/php' 'artisan' app:scheduler-heartbeat > '/dev/null' 2>&1

```

## Result

- DIAGNOSTIC_RESULT: `PASS_REQUEST_ID_CORRELATED`
- NEXT_ACTION: `UPLOAD_REPORT_TO_CHAT_FOR_ROOT_CAUSE_AND_FIX`
