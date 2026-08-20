
============================================================
CMS WEB 500 - DIAGNOSTIC BATCH 1
============================================================
DATE=Tue Aug 18 17:26:56 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MODE=READ_ONLY_DIAGNOSTIC_NO_SOURCE_OR_DATABASE_MUTATION
FOCUS=WEB_ROOT_500_AFTER_MODULE_CONTROL_WHILE_ANDROID_API_REMAINS_OPERATIONAL

============================================================
0. PREFLIGHT
============================================================
PHP 8.4.23 (cli) (built: Jul  9 2026 00:00:00) (NTS)
PREFLIGHT=PASS

============================================================
1. CURRENT SOURCE AND ROUTE HEALTH
============================================================
FILE_PRESENT=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ModuleVisibilityService.php
FILE_PRESENT=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ModuleSettingsController.php
FILE_PRESENT=/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ApiAccessService.php
FILE_PRESENT=/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php
FILE_PRESENT=/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php
FILE_PRESENT=/home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php
FILE_PRESENT=/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php
FILE_PRESENT=/home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/settings/modules.blade.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/ModuleVisibilityService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/ModuleSettingsController.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/app/Services/ApiAccessService.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/routes/web.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/routes/api.php

  GET|HEAD  admin/settings/modules ..................................................................... admin.settings.modules.index › Admin\ModuleSettingsController@index
            ⇂ web
            ⇂ Illuminate\Auth\Middleware\Authenticate
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\EnsureTrackedPortalSession
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
  PUT       admin/settings/modules ................................................................... admin.settings.modules.update › Admin\ModuleSettingsController@update
            ⇂ web
            ⇂ Illuminate\Auth\Middleware\Authenticate
            ⇂ App\Http\Middleware\EnsureActiveUser
            ⇂ App\Http\Middleware\EnsureTrackedPortalSession
            ⇂ App\Http\Middleware\RequirePermission:system.manage_settings
            ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write

                                                                                                                                                          Showing [2] routes


  GET|HEAD   api/v1/admin/orders ............................................................................ api.v1.admin.orders.index › Api\V1\Admin\OrderController@index
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
  GET|HEAD   api/v1/admin/orders/{order} ...................................................................... api.v1.admin.orders.show › Api\V1\Admin\OrderController@show
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
  POST       api/v1/admin/orders/{order}/accept ................................................... api.v1.admin.orders.accept › Api\V1\Admin\OrderMutationController@accept
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST       api/v1/admin/orders/{order}/complete ............................................. api.v1.admin.orders.complete › Api\V1\Admin\OrderMutationController@complete
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
             ⇂ App\Http\Middleware\RequirePermission:orders.confirm_delivery
  PATCH      api/v1/admin/orders/{order}/deadlines .......................................... api.v1.admin.orders.deadlines › Api\V1\Admin\OrderMutationController@deadlines
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST       api/v1/admin/orders/{order}/internal-notes ....................... api.v1.admin.orders.internal-notes.store › Api\V1\Admin\OrderMutationController@internalNote
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
             ⇂ App\Http\Middleware\RequirePermission:orders.internal_notes
  PATCH      api/v1/admin/orders/{order}/payment-status ............................ api.v1.admin.orders.payment-status › Api\V1\Admin\OrderMutationController@paymentStatus
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  POST       api/v1/admin/orders/{order}/payments ................................... api.v1.admin.orders.payments.store › Api\V1\Admin\OrderMutationController@paymentStore
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
             ⇂ App\Http\Middleware\RequirePermission:payments.manage
  POST       api/v1/admin/orders/{order}/payments/{payment}/reject ................ api.v1.admin.orders.payments.reject › Api\V1\Admin\OrderMutationController@paymentReject
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
             ⇂ App\Http\Middleware\RequirePermission:payments.manage
  POST       api/v1/admin/orders/{order}/payments/{payment}/verify ................ api.v1.admin.orders.payments.verify › Api\V1\Admin\OrderMutationController@paymentVerify
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
             ⇂ App\Http\Middleware\RequirePermission:payments.manage
  POST       api/v1/admin/orders/{order}/payments/{payment}/void ...................... api.v1.admin.orders.payments.void › Api\V1\Admin\OrderMutationController@paymentVoid
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
             ⇂ App\Http\Middleware\RequirePermission:payments.manage
  PATCH      api/v1/admin/orders/{order}/reassign ............................................. api.v1.admin.orders.reassign › Api\V1\Admin\OrderMutationController@reassign
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
             ⇂ App\Http\Middleware\RequirePermission:orders.reassign
  POST       api/v1/admin/orders/{order}/reopen ................................................... api.v1.admin.orders.reopen › Api\V1\Admin\OrderMutationController@reopen
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
             ⇂ App\Http\Middleware\RequirePermission:orders.reopen
  POST       api/v1/admin/orders/{order}/shipment .......................................... api.v1.admin.orders.shipment.store › Api\V1\Admin\OrderShipmentController@store
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write
  GET|HEAD   api/v1/admin/orders/{order}/shipment-proof .................................... api.v1.admin.orders.shipment.proof › Api\V1\Admin\OrderShipmentController@proof
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
  PATCH      api/v1/admin/orders/{order}/status ................................................... api.v1.admin.orders.status › Api\V1\Admin\OrderMutationController@status
             ⇂ api
             ⇂ Illuminate\Auth\Middleware\Authenticate:sanctum
             ⇂ App\Http\Middleware\EnsureActiveUser
             ⇂ App\Http\Middleware\RequirePermission:orders.manage
             ⇂ Illuminate\Routing\Middleware\ThrottleRequests:admin-write

                                                                                                                                                         Showing [16] routes

SOURCE_AND_ROUTE_HEALTH=PASS

============================================================
2. MODULE CONTROL SOURCE SIGNALS
============================================================
--- ModuleVisibilityService public surface ---
29:    public function __construct(private readonly SettingsService $settings)
34:    public function definitions(): array
40:    public function states(): array
55:    public function enabled(string $module): bool
65:    public function rows(): array
84:    public function update(array $states, int $userId): array
97:    private function settingKey(string $module): string
--- ModuleSettingsController public surface ---
16:    public function index(Request $request, ModuleVisibilityService $modules): View
25:    public function update(Request $request, ModuleVisibilityService $modules, AuditLogger $audit): RedirectResponse
47:    private function authorizeSuperAdmin(Request $request): void
--- Layout module guards ---
1:@inject('moduleVisibility', 'App\Services\ModuleVisibilityService')
127:            @if($moduleVisibility->enabled('notifications'))
184:                    @if($moduleVisibility->enabled('after_sales'))@can('after_sales.view_own')<a href="{{ route('after-sales.index') }}"><x-icon name="alert" />Moje reklamacije i servisi</a>@endcan@endif
185:                    @if($moduleVisibility->enabled('after_sales'))@can('after_sales.manage')<a href="{{ route('admin.after-sales.index') }}"><x-icon name="shield" />Reklamacije i servisi</a>@endcan@endif
186:                    @if($moduleVisibility->enabled('field_operations'))@can('field_operations.view')<a href="{{ route('admin.field-operations.index') }}"><x-icon name="truck" />Terenske operacije</a>@endcan@endif
187:                    @if($moduleVisibility->enabled('field_operations'))@can('field_operations.manage')<a href="{{ route('admin.field-service-teams.index') }}"><x-icon name="users" />Terenske ekipe</a>@endcan@endif
188:                    @if($moduleVisibility->enabled('service_parts'))@can('service_parts.view')<a href="{{ route('admin.service-parts.index') }}"><x-icon name="boxes" />Servisni lager</a>@endcan@endif
189:                    @if($moduleVisibility->enabled('service_parts'))@can('service_parts.procurement')<a href="{{ route('admin.service-part-purchases.index') }}"><x-icon name="receipt" />Nabavka delova</a>@endcan@endif
190:                    @if($moduleVisibility->enabled('warranties'))@can('warranties.view_own')<a href="{{ route('warranties.index') }}"><x-icon name="shield" />Moje garancije</a>@endcan@endif
191:                    @if($moduleVisibility->enabled('warranties'))@can('warranties.manage')<a href="{{ route('admin.warranties.index') }}"><x-icon name="shield" />Garancije i održavanje</a>@endcan@endif
192:                    @if($moduleVisibility->enabled('receivables'))@can('receivables.manage')<a href="{{ route('admin.receivables.index') }}"><x-icon name="wallet" />Potraživanja i naplata</a>@endcan@endif
198:                @if($moduleVisibility->enabled('commissions'))@can('commissions.manage')<a class="{{ request()->routeIs('admin.commissions.*') ? 'active' : '' }}" href="{{ route('admin.commissions.index') }}"><span class="nav-link-content"><x-icon name="wallet" /><span>Provizije</span></span></a>@endcan@endif
200:                @if($moduleVisibility->enabled('commissions'))@can('commissions.view_own')<a class="{{ request()->routeIs('commissions.*') ? 'active' : '' }}" href="{{ route('commissions.index') }}"><span class="nav-link-content"><x-icon name="wallet" /><span>Moje provizije</span></span></a>@endcan@endif
203:            @if($moduleVisibility->enabled('notifications'))@can('notifications.view')<a class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><span class="nav-link-content"><x-icon name="bell" /><span>Obaveštenja @if(($siteHeaderUnreadNotifications ?? 0)>0)<b class="nav-count">{{ min(99,(int)$siteHeaderUnreadNotifications) }}</b>@endif</span></span></a>@endcan@endif
205:            @if($moduleVisibility->enabled('reports'))@can('reports.view')<a class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}"><span class="nav-link-content"><x-icon name="chart" /><span>Izveštaji</span></span></a>@endcan@endif
211:                    @can('stock.view')@if($moduleVisibility->enabled('inventory'))<a href="{{ route('admin.inventory.index') }}"><x-icon name="boxes" />Napredni lager</a>@endif<a href="{{ route('admin.stock.index') }}"><x-icon name="cube" />Sva kretanja lagera</a>@endcan
212:                    @can('catalog.audit')<a href="{{ route('admin.data-quality.index') }}"><x-icon name="health" />Data Quality Center</a>@if($moduleVisibility->enabled('audit'))<a href="{{ route('admin.audit.index') }}"><x-icon name="receipt" />Audit log</a>@endif@endcan
220:                <div class="nav-dropdown-menu"><a href="{{ route('admin.users.index') }}"><x-icon name="users" />Svi korisnici</a>@if($moduleVisibility->enabled('customer_portal'))<a href="{{ route('admin.customer-portal.index') }}"><x-icon name="mail" />Customer Portal</a>@endif<a href="{{ route('admin.user-groups.index') }}"><x-icon name="user-check" />Grupe pristupa</a></div>
241:                    @if($moduleVisibility->enabled('receivables'))@can('receivables.manage')<a href="{{ route('admin.receivables.index') }}"><x-icon name="wallet" />Pravila potraživanja</a>@endcan@endif
242:                    @if($moduleVisibility->enabled('warranties'))@can('warranties.manage')<a href="{{ route('admin.warranties.index') }}"><x-icon name="shield" />Garancijska pravila</a>@endcan@endif
243:                    @if($moduleVisibility->enabled('field_operations'))@can('field_operations.manage')<a href="{{ route('admin.field-service-teams.index') }}"><x-icon name="users" />Terenske ekipe</a>@endcan@endif
244:                    @if($moduleVisibility->enabled('service_parts'))@can('service_parts.procurement')<a href="{{ route('admin.service-part-suppliers.index') }}"><x-icon name="receipt" />Dobavljači delova</a>@endcan@endif
252:                    @if($moduleVisibility->enabled('automation'))@can('automation.manage')<a href="{{ route('admin.settings.automation.index') }}"><x-icon name="cog" />Automatizacija</a>@endcan@endif
255:                    @if($moduleVisibility->enabled('system_health'))@can('system.health')<a href="{{ route('admin.settings.system-health.index') }}"><x-icon name="health" />System Health</a>@endcan@endif
--- Dashboard module guards ---
1:@inject('moduleVisibility', 'App\Services\ModuleVisibilityService')
8:        @if(!$moduleVisibility->enabled('reports'))
11:        @if(!$moduleVisibility->enabled('receivables'))
14:        @if(!$moduleVisibility->enabled('after_sales'))
17:        @if(!$moduleVisibility->enabled('field_operations'))
21:        @if(!$moduleVisibility->enabled('service_parts'))
MODULE_CONTROL_SOURCE_SIGNAL_CAPTURE=PASS

============================================================
3. BOOTSTRAP + MODULE SERVICE READ-ONLY RUNTIME PROBE
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.cms-web-500-diagnostic-batch1.b0v8uK/runtime-probe.php
APP_BOOTSTRAP=PASS
APP_ENV=production
MODULE_VISIBILITY_RESOLUTION=PASS
MODULE_VISIBILITY_CLASS=App\Services\ModuleVisibilityService
MODULE_VISIBILITY_PUBLIC_METHOD=__construct:1
MODULE_VISIBILITY_PUBLIC_METHOD=definitions:0
MODULE_VISIBILITY_PUBLIC_METHOD=states:0
MODULE_VISIBILITY_PUBLIC_METHOD=enabled:1
MODULE_VISIBILITY_PUBLIC_METHOD=rows:0
MODULE_VISIBILITY_PUBLIC_METHOD=update:2
MODULE_VISIBILITY_CALL_DEFINITIONS=PASS:type=array:count=13
MODULE_VISIBILITY_CALL_STATES=PASS:type=array:count=13
MODULE_SETTINGS_CONTROLLER_RESOLUTION=PASS:App\Http\Controllers\Admin\ModuleSettingsController
MODULE_SETTINGS_DB_READ=FAIL:Illuminate\Database\QueryException:SQLSTATE[42S22]: Column not found: 1054 Unknown column 'key' in 'SELECT' (Connection: mysql, Host: localhost, Port: 3306, Database: icaffeco_lrvl, SQL: select `key`, `value` from `settings` where `key` like module_enabled_% order by `key` asc)
RUNTIME_PROBE_CAPTURED=YES

============================================================
4. AUTH DOCTOR DASHBOARD RENDER PROBE
============================================================
PASS session direktorijum je upisiv: /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/sessions
PASS cache direktorijum je upisiv: /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/cache/data
PASS compiled views direktorijum je upisiv: /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views
PASS logs direktorijum je upisiv: /home/icaffeco/ald1n-project/apps/cms/current/storage/logs
PASS Laravel baza dostupna; korisnika: 12
PASS Legacy baza dostupna; korisnika: 7

Aktivni Laravel korisnici:
+----+------------------+-----------------------------+--------+---------+
| ID | Korisničko ime   | E-mail                      | Status | Role ID |
+----+------------------+-----------------------------+--------+---------+
| 1  | Ald1n            | pruzljanin@gmail.com        | active | 3       |
| 2  | daver.ha         | daver.ha.business@gmail.com | active | 1       |
| 3  | samedpruzljanin2 | samedpruzljanin2@gmail.com  | active | 1       |
| 4  | dinsahovic       | dinsahovic@gmail.com        | active | 1       |
| 5  | Sumer89          | sumerarapovic@gmail.com     | active | 1       |
| 6  | nuhovic          | nuhovic@gmail.com           | active | 1       |
| 7  | Shone            | etech.store00@gmail.com     | active | 1       |
| 8  | Hamko            | eleskovic.hamid@gmail.com   | active | 1       |
| 9  | General          | general.np1@live.com        | active | 2       |
| 10 | amarvatic4627    | amarvatic4627@gmail.com     | active | 1       |
| 11 | ajlar91          | ajlar91@gmail.com           | active | 2       |
| 12 | irfan1suljovic   | irfan1suljovic@gmail.com    | active | 1       |
+----+------------------+-----------------------------+--------+---------+
AUTH_DOCTOR_EXIT_CODE=0
AUTH_DOCTOR_DASHBOARD_RENDER=PASS

============================================================
5. RECENT LARAVEL ERROR SIGNALS
============================================================

--- LOG: /home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-18.log ---
mtime=2026-08-18 17:26:25.748925842 +0200 size=346905
[previous exception] [object] (ParseError(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/d9c1fae579fd48a8c10d879a5e4e4ea9.php:1228)
[2026-08-18 17:26:19] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"3a994be0-a185-4071-bb7b-36398573b48c","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-18 17:26:20] production.ERROR: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) {"request_id":"3a994be0-a185-4071-bb7b-36398573b48c","userId":1,"exception":"[object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
[previous exception] [object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
[previous exception] [object] (ParseError(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
[2026-08-18 17:26:22] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"3a5e6190-8eef-4e1f-9a96-840aec5b684f","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-18 17:26:22] production.ERROR: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) {"request_id":"3a5e6190-8eef-4e1f-9a96-840aec5b684f","userId":1,"exception":"[object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
[previous exception] [object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
[previous exception] [object] (ParseError(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
[2026-08-18 17:26:25] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"f6b07628-0bc0-4105-b8ce-7ffcf36f82a2","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-18 17:26:25] production.ERROR: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) {"request_id":"f6b07628-0bc0-4105-b8ce-7ffcf36f82a2","userId":1,"exception":"[object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
[previous exception] [object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
[previous exception] [object] (ParseError(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)

--- LOG: /home/icaffeco/ald1n-project/apps/cms/current/storage/logs/scheduler.log ---
mtime=2026-08-18 17:26:03.944713583 +0200 size=6107473

--- LOG: /home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-17.log ---
mtime=2026-08-17 19:47:58.884216410 +0200 size=12416
[2026-08-17 00:01:44] production.ERROR: Call to undefined method App\Models\User::roles() {"exception":"[object] (BadMethodCallException(code: 0): Call to undefined method App\\Models\\User::roles() at /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Support/Traits/ForwardsCalls.php:67)
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Support/Traits/ForwardsCalls.php(36): Illuminate\\Database\\Eloquent\\Model::throwBadMethodCallException()
[2026-08-17 00:13:02] production.ERROR: The "--render" option does not exist. {"exception":"[object] (Symfony\\Component\\Console\\Exception\\RuntimeException(code: 0): The \"--render\" option does not exist. at /home/icaffeco/ald1n-project/apps/cms/current/vendor/symfony/console/Input/ArgvInput.php:226)
[2026-08-17 00:20:42] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"8f02d67e-bd56-4adf-bf7a-e9fb3c87b452","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 01:18:03] production.ERROR: on-host project text traces remain outside the declared backup boundary {"exception":"[object] (RuntimeException(code: 0): on-host project text traces remain outside the declared backup boundary at /tmp/ald1n-total-product-purge-execute.ea8j1H/post-purge-zero-trace.php:129)
[2026-08-17 01:58:01] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"cbad2399-14c8-4e75-9b4a-756ed3b211b8","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 01:58:59] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"391b0c64-6c9b-4bb3-bf0a-e3b23b918acb","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 09:15:07] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"0ca2b1f3-931a-4faa-be21-b8617d17b4ea","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 09:15:22] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"6c4d7f37-dc60-4100-ae6c-41bf671bc2f3","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 10:36:59] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"aae46c91-f8a0-4967-bef9-ca7e3b49a179","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 10:37:34] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"86870a40-6819-48f7-8e33-074c6406b32f","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 10:37:36] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"ac73f2e9-0e54-47fd-8a8c-9dde04791c6a","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 10:39:13] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"ab4ed8a1-f2b3-4a95-aa4e-91b990e3994d","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 10:45:02] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"047de5d7-ac02-434e-9904-0703e1b62bb5","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 11:08:29] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"11b4c0fb-8948-4802-bd8b-16262b19ab61","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 11:08:42] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"fc65e438-f869-4317-a381-d6f9fc1713b6","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 11:10:30] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"380c9eb3-98eb-43f0-a556-ec07e4257771","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 11:18:06] production.ERROR: Array callback must have exactly two elements {"exception":"[object] (Error(code: 0): Array callback must have exactly two elements at /tmp/ald1n-direct-sale-eligibility.89OWoh/direct-sale-product-audit.php:204)
[2026-08-17 12:00:52] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"ba974035-6d11-4adc-a5d7-d825819ffc46","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 13:32:56] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"959df316-23f7-4d2f-be85-19a01886c8e3","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 13:43:01] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"2add26e3-a858-44da-b1c2-90af12cb1b30","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 13:46:12] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"eac4fc52-7e95-4505-8d85-bb0242c3d2cd","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 13:47:03] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"7fd95a19-9e68-4a5d-a8d0-4ded032e3efa","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 13:54:43] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"f0d6e310-0e7b-48e1-9de3-9b588a5b5381","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 13:58:29] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"4c4e1733-1b1a-442b-a62d-68d494f1b905","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 14:09:36] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"f2a467be-cfab-4356-8079-dad2077d4d46","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 19:45:33] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"e48572cb-80a7-4335-ab8a-62be91efc315","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 19:45:33] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"f1e6cb03-cc29-494f-91a4-7770ccb978dd","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-17 19:47:58] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"6d365575-0a59-44ca-a149-585b199ebbf3","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}

============================================================
6. ROOT ERROR_LOG SIGNALS
============================================================
mtime=2026-08-17 12:25:35.006971876 +0200 size=7101
[08-Aug-2026 19:24:30 Europe/Belgrade] PHP Warning:  require(/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php): Failed to open stream: No such file or directory in /home/icaffeco/ald1n-project/apps/cms/current/artisan on line 11
[08-Aug-2026 19:24:30 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Failed opening required '/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php' (include_path='.:/opt/alt/php84/usr/share/pear:/opt/alt/php84/usr/share/php:/usr/share/pear:/usr/share/php') in /home/icaffeco/ald1n-project/apps/cms/current/artisan:11
[08-Aug-2026 19:24:31 Europe/Belgrade] PHP Warning:  require(/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php): Failed to open stream: No such file or directory in /home/icaffeco/ald1n-project/apps/cms/current/artisan on line 11
[08-Aug-2026 19:24:31 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Failed opening required '/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php' (include_path='.:/opt/alt/php84/usr/share/pear:/opt/alt/php84/usr/share/php:/usr/share/pear:/usr/share/php') in /home/icaffeco/ald1n-project/apps/cms/current/artisan:11
[08-Aug-2026 19:24:32 Europe/Belgrade] PHP Warning:  require(/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php): Failed to open stream: No such file or directory in /home/icaffeco/ald1n-project/apps/cms/current/artisan on line 11
[08-Aug-2026 19:24:32 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Failed opening required '/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php' (include_path='.:/opt/alt/php84/usr/share/pear:/opt/alt/php84/usr/share/php:/usr/share/pear:/usr/share/php') in /home/icaffeco/ald1n-project/apps/cms/current/artisan:11
[11-Aug-2026 00:42:17 Europe/Belgrade] PHP Warning:  require(/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php): Failed to open stream: No such file or directory in /home/icaffeco/ald1n-project/apps/cms/current/artisan on line 11
[11-Aug-2026 00:42:17 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Failed opening required '/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php' (include_path='.:/opt/alt/php84/usr/share/pear:/opt/alt/php84/usr/share/php:/usr/share/pear:/usr/share/php') in /home/icaffeco/ald1n-project/apps/cms/current/artisan:11
[11-Aug-2026 00:42:17 Europe/Belgrade] PHP Warning:  require(/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php): Failed to open stream: No such file or directory in /home/icaffeco/ald1n-project/apps/cms/current/artisan on line 11
[11-Aug-2026 00:42:17 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Failed opening required '/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php' (include_path='.:/opt/alt/php84/usr/share/pear:/opt/alt/php84/usr/share/php:/usr/share/pear:/usr/share/php') in /home/icaffeco/ald1n-project/apps/cms/current/artisan:11
[11-Aug-2026 00:42:17 Europe/Belgrade] PHP Warning:  require(/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php): Failed to open stream: No such file or directory in /tmp/ald1n-b8c-audit.xYTGaB/render-audit.php on line 13
[11-Aug-2026 00:42:17 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Failed opening required '/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php' (include_path='.:/opt/alt/php84/usr/share/pear:/opt/alt/php84/usr/share/php:/usr/share/pear:/usr/share/php') in /tmp/ald1n-b8c-audit.xYTGaB/render-audit.php:13
[11-Aug-2026 00:42:17 Europe/Belgrade] PHP Warning:  require(/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php): Failed to open stream: No such file or directory in /tmp/ald1n-b8c-audit.xYTGaB/render-audit.php on line 13
[11-Aug-2026 00:42:17 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Failed opening required '/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php' (include_path='.:/opt/alt/php84/usr/share/pear:/opt/alt/php84/usr/share/php:/usr/share/pear:/usr/share/php') in /tmp/ald1n-b8c-audit.xYTGaB/render-audit.php:13
[11-Aug-2026 00:51:08 Europe/Belgrade] PHP Warning:  require(/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php): Failed to open stream: No such file or directory in /home/icaffeco/ald1n-project/apps/cms/current/artisan on line 11
[11-Aug-2026 00:51:08 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Failed opening required '/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php' (include_path='.:/opt/alt/php84/usr/share/pear:/opt/alt/php84/usr/share/php:/usr/share/pear:/usr/share/php') in /home/icaffeco/ald1n-project/apps/cms/current/artisan:11
[11-Aug-2026 00:51:08 Europe/Belgrade] PHP Warning:  require(/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php): Failed to open stream: No such file or directory in /home/icaffeco/ald1n-project/apps/cms/current/artisan on line 11
[11-Aug-2026 00:51:08 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Failed opening required '/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php' (include_path='.:/opt/alt/php84/usr/share/pear:/opt/alt/php84/usr/share/php:/usr/share/pear:/usr/share/php') in /home/icaffeco/ald1n-project/apps/cms/current/artisan:11
[11-Aug-2026 00:51:09 Europe/Belgrade] PHP Warning:  require(/home/icaffeco/ald1n-project/apps/cms/current/vendor/autoload.php): Failed to open stream: No such file or directory in /tmp/ald1n-b8c1-audit.ulvwRm/runtime-probe.php on line 40
[17-Aug-2026 02:07:58 Europe/Belgrade] PHP Fatal error:  Uncaught TypeError: {closure:/home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:9}(): Argument #1 ($label) must be of type string, true given, called in /home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php on line 787 and defined in /home/icaffeco/ald1n-project/apps/cms/current/bin/static-check.php:9

============================================================
7. READ-ONLY STATIC GATE
============================================================
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
PASS  v2.1.6 Data Quality audit pokriva katalog slike varijante i specifikacije
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
CMS_STATIC_CHECK_EXIT_CODE=0

============================================================
8. DIAGNOSTIC CLASSIFICATION
============================================================
LIKELY_CAUSE_CLASS=MODULE_CONTROL_RUNTIME_OR_VIEW_INTEGRATION
SOURCE_MUTATION=NO
DATABASE_MUTATION=NO
CACHE_MUTATION=NO
EAS_BUILD=NO
DIAGNOSTIC_BATCH=COMPLETE
REPORT_FILE=/home/icaffeco/ald1n-project/docs/operations/CMS-WEB-500-DIAGNOSTIC-BATCH1-20260818-172656.md
UPLOAD_THIS_REPORT_TO_CHAT=/home/icaffeco/ald1n-project/docs/operations/CMS-WEB-500-DIAGNOSTIC-BATCH1-20260818-172656.md
