
============================================================
CMS MODULE CONTROL - READ-ONLY AUDIT - BATCH 1
============================================================
DATE=Tue Aug 18 14:20:25 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-MODULE-CONTROL-AUDIT-BATCH1-20260818-142025.md
MODE=READ_ONLY_MODULE_CONTROL_DISCOVERY
TARGET=SUPERADMIN_MAIN_SETTINGS_OPTIONAL_MODULE_VISIBILITY_CONTROL
SOURCE_WRITES_EXPECTED=0
DATABASE_WRITES_EXPECTED=0
MIGRATIONS_RUN=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
INTENDED_POLICY=SOFT_DEACTIVATION_HIDE_UI_PRESERVE_DATA_AND_DOMAIN_LOGIC

============================================================
0. CONCURRENCY + ROOT PREFLIGHT
============================================================
CONCURRENCY_LOCK=ACQUIRED
CANONICAL_ROOT=PASS
PHP_VERSION=8.4.23
MOBILE_APP_VERSION=0.7.0

============================================================
1. CURRENT SETTINGS ARCHITECTURE
============================================================

--- SettingsService: /home/icaffeco/ald1n-project/apps/cms/current/app/Services/SettingsService.php ---
13:final class SettingsService
15:    private const CACHE_KEY = 'ald1n.settings.all';
23:    private const DEFAULTS = [
40:        'login_background_mode' => 'default',
95:        'documents_default_note' => 'Hvala na ukazanom poverenju.',
141:    public function all(): array
143:        $settings = $this->raw();
145:            unset($settings[$key]);
148:        $merged = array_replace(self::DEFAULTS, $settings);
156:        $merged['login_background_mode'] = in_array($merged['login_background_mode'], ['default', 'image', 'slideshow', 'youtube'], true) ? $merged['login_background_mode'] : 'default';
171:        if ($merged['login_background_mode'] === 'image' && trim((string) $merged['login_background_image_path']) === '') $merged['login_background_mode'] = 'default';
172:        if ($merged['login_background_mode'] === 'slideshow' && $activeLoginSlides === 0) $merged['login_background_mode'] = 'default';
173:        if ($merged['login_background_mode'] === 'youtube' && $merged['login_background_youtube_id'] === '') $merged['login_background_mode'] = 'default';
178:    public function get(string $key, ?string $default = null): ?string
181:        return array_key_exists($key, $all) ? $all[$key] : $default;
184:    public function hasStoredValue(string $key): bool
189:    public function getSecret(string $key): ?string
201:    public function eurRsdRate(): ?float
208:    public function putMany(array $values, ?int $userId): void
210:        DB::transaction(function () use ($values, $userId): void {
213:                    ['setting_key' => $key],
214:                    ['setting_value' => $value, 'updated_by' => $userId],
221:    public function putSecret(string $key, string $value, ?int $userId): void
226:        $this->putMany([$key => Crypt::encryptString($value)], $userId);
229:    public function forgetCache(): void
234:    public function renderTemplate(string $text): string
244:    private function raw(): array
246:        /** @var array<string,string> $settings */
247:        $settings = Cache::remember(self::CACHE_KEY, now()->addMinutes(10), static function (): array {
248:            $rows = Setting::query()->pluck('setting_value', 'setting_key')->all();
252:        return $settings;

--- SiteAppearanceController: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Admin/SiteAppearanceController.php ---
9:use App\Services\SettingsService;
23:    public function index(Request $request, SettingsService $settings, SiteAssetUrlService $assets): View
51:    public function update(Request $request, SettingsService $settings, AuditLogger $audit): RedirectResponse
69:            $this->authorizeLoginBackground($request);
286:            $settings->putMany($values, (int) $request->user()->getAuthIdentifier());
305:    public function removeAsset(Request $request, string $asset, SettingsService $settings, AuditLogger $audit): RedirectResponse
316:            $settings->putMany([$key => ''], (int) $request->user()->getAuthIdentifier());
325:        $this->authorizeLoginBackground($request);
366:        $settings->putMany($values, (int) $request->user()->getAuthIdentifier());
375:    private function canManageLoginBackground(Request $request): bool
377:        return $request->user()?->hasRole('superadmin') === true;
380:    private function authorizeLoginBackground(Request $request): void
385:    private function extractYoutubeVideoId(string $value): ?string

--- Appearance settings view: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/admin/settings/appearance.blade.php ---
2:@section('title', 'Izgled sajta')
3:@section('content')
18:@include('admin.settings.partials.context-nav', ['settingsSection' => 'Izgled i interfejs'])
22:<form method="post" action="{{ route('admin.settings.appearance.update') }}" enctype="multipart/form-data" class="admin-form-grid settings-primary-form">
25:    <div class="form-main">
26:        <section class="panel form-section">
33:        </section>
35:        <section class="panel form-section">
42:                    @if($settings['site_logo_light_path'])<button class="button button-danger button-small" type="submit" form="remove-logo-light">Ukloni</button>@endif
48:                    @if($settings['site_logo_dark_path'])<button class="button button-danger button-small" type="submit" form="remove-logo-dark">Ukloni</button>@endif
54:                    @if($settings['site_favicon_path'])<button class="button button-danger button-small" type="submit" form="remove-favicon">Ukloni</button>@endif
57:        </section>
60:            <section class="panel form-section login-background-settings" data-login-background-settings>
61:                <div class="section-heading-row">
65:                        <p class="muted">Izaberi statičnu sliku, slideshow ili YouTube video iza cele login stranice. Autentikacija i forma za prijavu ostaju potpuno odvojene od ovih vizuelnih podešavanja.</p>
107:                        @if($settings['login_background_image_path'] ?? '')<button class="button button-danger button-small" type="submit" form="remove-login-background">Ukloni</button>@endif
117:                        @if($settings['login_background_fallback_path'] ?? '')<button class="button button-danger button-small" type="submit" form="remove-login-fallback">Ukloni</button>@endif
122:                    <div class="section-heading-row compact">
136:                                        <button class="button button-danger button-small" type="submit" form="remove-login-slide-{{ $slide['slot'] }}">Ukloni</button>
157:                    <div class="section-heading-row compact">
177:            </section>
180:        <section class="panel form-section">
196:        </section>
198:    <aside class="form-side">
199:        <section class="panel sticky-card form-section">
202:            <button class="button button-primary" type="submit">Sačuvaj izgled</button>
203:        </section>
205:</form>
207:<form id="remove-logo-light" method="post" action="{{ route('admin.settings.appearance.asset', 'logo-light') }}">@csrf @method('delete')</form>
208:<form id="remove-logo-dark" method="post" action="{{ route('admin.settings.appearance.asset', 'logo-dark') }}">@csrf @method('delete')</form>
209:<form id="remove-favicon" method="post" action="{{ route('admin.settings.appearance.asset', 'favicon') }}">@csrf @method('delete')</form>
211:    <form id="remove-login-background" method="post" action="{{ route('admin.settings.appearance.asset', 'login-background') }}">@csrf @method('delete')</form>
212:    <form id="remove-login-fallback" method="post" action="{{ route('admin.settings.appearance.asset', 'login-fallback') }}">@csrf @method('delete')</form>
215:            <form id="remove-login-slide-{{ $slide['slot'] }}" method="post" action="{{ route('admin.settings.appearance.asset', 'login-slide-'.$slide['slot']) }}">@csrf @method('delete')</form>
339:@endsection

--- Settings web routes: /home/icaffeco/ald1n-project/apps/cms/current/routes/web.php ---
12:use App\Http\Controllers\Admin\AutomationController;
25:use App\Http\Controllers\Admin\CourierServiceController;
27:use App\Http\Controllers\Admin\DocumentSettingsController;
30:use App\Http\Controllers\Admin\OrderEmailSettingsController;
43:use App\Http\Controllers\Admin\SiteAppearanceController;
47:use App\Http\Controllers\Admin\TurnstileSettingsController;
48:use App\Http\Controllers\Admin\SettingsHubController;
244:            Route::get('/catalog-settings/product-type/{productType:slug}', [CatalogDictionaryController::class, 'productType'])->name('dictionary.product-type');
245:            Route::patch('/catalog-settings/product-type/{productType:slug}/fields/reorder', [CatalogDictionaryController::class, 'reorderTypeFields'])->name('dictionary.product-type.fields.reorder');
246:            Route::patch('/catalog-settings/{resource}/reorder', [CatalogDictionaryController::class, 'reorder'])->where('resource', 'categories|brands|product-lines|product-types|specification-fields')->name('dictionary.reorder');
247:            Route::delete('/catalog-settings/{resource}/{item}/purge', [CatalogDictionaryController::class, 'purge'])->where('resource', 'specification-fields')->name('dictionary.purge');
248:            Route::get('/catalog-settings/{resource}', [CatalogDictionaryController::class, 'index'])->where('resource', 'categories|brands|product-lines|product-types|specification-fields')->name('dictionary.index');
249:            Route::post('/catalog-settings/{resource}', [CatalogDictionaryController::class, 'store'])->where('resource', 'categories|brands|product-lines|product-types|specification-fields')->name('dictionary.store');
250:            Route::put('/catalog-settings/{resource}/{item}', [CatalogDictionaryController::class, 'update'])->where('resource', 'categories|brands|product-lines|product-types|specification-fields')->name('dictionary.update');
251:            Route::delete('/catalog-settings/{resource}/{item}', [CatalogDictionaryController::class, 'destroy'])->where('resource', 'categories|brands|product-lines|product-types|specification-fields')->name('dictionary.destroy');
275:            Route::put('/receivables/settings', [ReceivablesController::class, 'updateSettings'])->name('receivables.settings.update');
421:        Route::get('/settings', [SettingsHubController::class, 'index'])->name('settings.index');
423:        Route::prefix('settings')->name('settings.')->middleware('permission:system.manage_settings')->group(function (): void {
424:            Route::get('/automation', [AutomationController::class, 'index'])->middleware('permission:automation.manage')->name('automation.index');
425:            Route::put('/automation', [AutomationController::class, 'update'])->middleware('permission:automation.manage')->name('automation.update');
426:            Route::post('/automation/run', [AutomationController::class, 'run'])->middleware('permission:automation.manage')->name('automation.run');
427:            Route::post('/automation/alerts/{alert}/resolve', [AutomationController::class, 'resolve'])->middleware('permission:automation.manage')->name('automation.alerts.resolve');
428:            Route::get('/system-health', [SystemHealthController::class, 'index'])->middleware('permission:system.health')->name('system-health.index');
429:            Route::post('/system-health/run', [SystemHealthController::class, 'run'])->middleware(['permission:system.health','throttle:admin-write'])->name('system-health.run');
430:            Route::post('/system-health/backup', [SystemHealthController::class, 'backup'])->middleware(['permission:backups.manage','throttle:backup'])->name('system-health.backup');
431:            Route::post('/system-health/prune', [SystemHealthController::class, 'prune'])->middleware(['permission:backups.manage','throttle:admin-write'])->name('system-health.prune');
432:            Route::get('/turnstile', [TurnstileSettingsController::class, 'index'])->name('turnstile.index');
433:            Route::put('/turnstile', [TurnstileSettingsController::class, 'update'])->middleware('throttle:admin-write')->name('turnstile.update');
434:            Route::get('/appearance', [SiteAppearanceController::class, 'index'])->name('appearance');
435:            Route::put('/appearance', [SiteAppearanceController::class, 'update'])->name('appearance.update');
436:            Route::delete('/appearance/assets/{asset}', [SiteAppearanceController::class, 'removeAsset'])->name('appearance.asset');
437:            Route::get('/exchange-rate', [ExchangeRateController::class, 'index'])->name('exchange.index');
438:            Route::post('/exchange-rate/manual', [ExchangeRateController::class, 'manual'])->name('exchange.manual');
439:            Route::post('/exchange-rate/automatic', [ExchangeRateController::class, 'automatic'])->name('exchange.automatic');
440:            Route::post('/exchange-rate/refresh', [ExchangeRateController::class, 'refresh'])->name('exchange.refresh');
441:            Route::get('/couriers', [CourierServiceController::class, 'index'])->name('couriers.index');
442:            Route::post('/couriers', [CourierServiceController::class, 'store'])->middleware('throttle:admin-write')->name('couriers.store');
443:            Route::put('/couriers/{courier}', [CourierServiceController::class, 'update'])->whereNumber('courier')->middleware('throttle:admin-write')->name('couriers.update');
444:            Route::get('/order-emails', [OrderEmailSettingsController::class, 'index'])->name('order-emails.index');
445:            Route::put('/order-emails', [OrderEmailSettingsController::class, 'update'])->middleware('throttle:admin-write')->name('order-emails.update');
446:            Route::post('/order-emails/dispatch', [OrderEmailSettingsController::class, 'dispatch'])->middleware('throttle:admin-write')->name('order-emails.dispatch');
447:            Route::post('/order-emails/retry', [OrderEmailSettingsController::class, 'retry'])->middleware('throttle:admin-write')->name('order-emails.retry');
448:            Route::get('/documents', [DocumentSettingsController::class, 'index'])->name('documents.index');
449:            Route::put('/documents', [DocumentSettingsController::class, 'update'])->name('documents.update');
450:            Route::delete('/documents/logo', [DocumentSettingsController::class, 'removeLogo'])->name('documents.logo.destroy');
ADMIN_SETTINGS_ROUTE_COUNT=32

  GET|HEAD  admin/settings ........................................................................................ admin.settings.index › Admin\SettingsHubController@index
  GET|HEAD  admin/settings/appearance ..................................................................... admin.settings.appearance › Admin\SiteAppearanceController@index
  PUT       admin/settings/appearance ............................................................. admin.settings.appearance.update › Admin\SiteAppearanceController@update
  DELETE    admin/settings/appearance/assets/{asset} .......................................... admin.settings.appearance.asset › Admin\SiteAppearanceController@removeAsset
  GET|HEAD  admin/settings/automation ................................................................... admin.settings.automation.index › Admin\AutomationController@index
  PUT       admin/settings/automation ................................................................. admin.settings.automation.update › Admin\AutomationController@update
  POST      admin/settings/automation/alerts/{alert}/resolve ................................. admin.settings.automation.alerts.resolve › Admin\AutomationController@resolve
  POST      admin/settings/automation/run ................................................................... admin.settings.automation.run › Admin\AutomationController@run
  GET|HEAD  admin/settings/bank-accounts ............................................................ admin.settings.bank-accounts.index › Admin\BankAccountController@index
  POST      admin/settings/bank-accounts ............................................................ admin.settings.bank-accounts.store › Admin\BankAccountController@store
  PUT       admin/settings/bank-accounts/{bankAccount} ............................................ admin.settings.bank-accounts.update › Admin\BankAccountController@update
  DELETE    admin/settings/bank-accounts/{bankAccount} .......................................... admin.settings.bank-accounts.destroy › Admin\BankAccountController@destroy
  GET|HEAD  admin/settings/couriers ................................................................... admin.settings.couriers.index › Admin\CourierServiceController@index
  POST      admin/settings/couriers ................................................................... admin.settings.couriers.store › Admin\CourierServiceController@store
  PUT       admin/settings/couriers/{courier} ....................................................... admin.settings.couriers.update › Admin\CourierServiceController@update
  GET|HEAD  admin/settings/documents ............................................................... admin.settings.documents.index › Admin\DocumentSettingsController@index
  PUT       admin/settings/documents ............................................................. admin.settings.documents.update › Admin\DocumentSettingsController@update
  DELETE    admin/settings/documents/logo .............................................. admin.settings.documents.logo.destroy › Admin\DocumentSettingsController@removeLogo
  GET|HEAD  admin/settings/exchange-rate ................................................................ admin.settings.exchange.index › Admin\ExchangeRateController@index
  POST      admin/settings/exchange-rate/automatic .............................................. admin.settings.exchange.automatic › Admin\ExchangeRateController@automatic
  POST      admin/settings/exchange-rate/manual ....................................................... admin.settings.exchange.manual › Admin\ExchangeRateController@manual
  POST      admin/settings/exchange-rate/refresh .................................................... admin.settings.exchange.refresh › Admin\ExchangeRateController@refresh
  GET|HEAD  admin/settings/order-emails ....................................................... admin.settings.order-emails.index › Admin\OrderEmailSettingsController@index
  PUT       admin/settings/order-emails ..................................................... admin.settings.order-emails.update › Admin\OrderEmailSettingsController@update
  POST      admin/settings/order-emails/dispatch ........................................ admin.settings.order-emails.dispatch › Admin\OrderEmailSettingsController@dispatch
  POST      admin/settings/order-emails/retry ................................................. admin.settings.order-emails.retry › Admin\OrderEmailSettingsController@retry
  GET|HEAD  admin/settings/system-health ........................................................... admin.settings.system-health.index › Admin\SystemHealthController@index
  POST      admin/settings/system-health/backup .................................................. admin.settings.system-health.backup › Admin\SystemHealthController@backup
  POST      admin/settings/system-health/prune ..................................................... admin.settings.system-health.prune › Admin\SystemHealthController@prune
  POST      admin/settings/system-health/run ........................................................... admin.settings.system-health.run › Admin\SystemHealthController@run
  GET|HEAD  admin/settings/turnstile .............................................................. admin.settings.turnstile.index › Admin\TurnstileSettingsController@index
  PUT       admin/settings/turnstile ............................................................ admin.settings.turnstile.update › Admin\TurnstileSettingsController@update

                                                                                                                                                         Showing [32] routes


============================================================
2. SETTINGS TABLE READ-ONLY PROBE
============================================================
No syntax errors detected in /tmp/ald1n-module-control-audit.025GNG/settings-probe.php
SETTINGS_TABLE_EXISTS=YES
SETTINGS_COLUMNS=id,setting_key,setting_value,updated_by,created_at,updated_at
SETTINGS_KEY_COLUMN=setting_key
SETTINGS_VALUE_COLUMN=setting_value
EXISTING_MODULE_SETTING_ROWS=0
DATABASE_WRITES_DURING_SETTINGS_PROBE=0

============================================================
3. WEB INTERFACE ENTRY POINTS
============================================================

--- Authenticated layout navigation: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php ---
125:            @can('notifications.view')
126:            <a class="notification-header-button" href="{{ route('notifications.index') }}" aria-label="Obaveštenja" title="Obaveštenja">
128:                @if(($siteHeaderUnreadNotifications ?? 0)>0)<span>{{ min(99,(int)$siteHeaderUnreadNotifications) }}</span>@endif
139:            @canany(['catalog.view','orders.manage','orders.view_own','system.manage_users','warranties.manage','warranties.view_own','after_sales.manage','after_sales.view_own','stock.view','reports.view','commissions.manage','commissions.view_own','notifications.view'])
154:            <a class="{{ request()->routeIs('dashboard', 'portal.messages.*') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-link-content"><x-icon name="home" /><span>Početna</span></span></a>
168:                        <a href="{{ route('admin.dictionary.index','specification-fields') }}">Polja specifikacija</a>
174:            @canany(['orders.view_own','orders.manage','after_sales.view_own','after_sales.manage','field_operations.view','service_parts.view','service_parts.procurement','warranties.view_own','warranties.manage','receivables.manage'])
176:                <summary class="{{ request()->routeIs('orders.*','admin.orders.*','after-sales.*','admin.after-sales.*','admin.field-operations.*','admin.field-service-teams.*','admin.service-parts.*','admin.service-part-*','warranties.*','admin.warranties.*','admin.receivables.*') ? 'active' : '' }}"><span><x-icon name="orders" />Upravljanje porudžbinama</span></summary>
180:                    @can('after_sales.view_own')<a href="{{ route('after-sales.index') }}"><x-icon name="alert" />Moje reklamacije i servisi</a>@endcan
181:                    @can('after_sales.manage')<a href="{{ route('admin.after-sales.index') }}"><x-icon name="shield" />Reklamacije i servisi</a>@endcan
182:                    @can('field_operations.view')<a href="{{ route('admin.field-operations.index') }}"><x-icon name="truck" />Terenske operacije</a>@endcan
183:                    @can('field_operations.manage')<a href="{{ route('admin.field-service-teams.index') }}"><x-icon name="users" />Terenske ekipe</a>@endcan
184:                    @can('service_parts.view')<a href="{{ route('admin.service-parts.index') }}"><x-icon name="boxes" />Servisni lager</a>@endcan
185:                    @can('service_parts.procurement')<a href="{{ route('admin.service-part-purchases.index') }}"><x-icon name="receipt" />Nabavka delova</a>@endcan
186:                    @can('warranties.view_own')<a href="{{ route('warranties.index') }}"><x-icon name="shield" />Moje garancije</a>@endcan
187:                    @can('warranties.manage')<a href="{{ route('admin.warranties.index') }}"><x-icon name="shield" />Garancije i održavanje</a>@endcan
188:                    @can('receivables.manage')<a href="{{ route('admin.receivables.index') }}"><x-icon name="wallet" />Potraživanja i naplata</a>@endcan
194:                @can('commissions.manage')<a class="{{ request()->routeIs('admin.commissions.*') ? 'active' : '' }}" href="{{ route('admin.commissions.index') }}"><span class="nav-link-content"><x-icon name="wallet" /><span>Provizije</span></span></a>@endcan
196:                @can('commissions.view_own')<a class="{{ request()->routeIs('commissions.*') ? 'active' : '' }}" href="{{ route('commissions.index') }}"><span class="nav-link-content"><x-icon name="wallet" /><span>Moje provizije</span></span></a>@endcan
199:            @can('notifications.view')<a class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><span class="nav-link-content"><x-icon name="bell" /><span>Obaveštenja @if(($siteHeaderUnreadNotifications ?? 0)>0)<b class="nav-count">{{ min(99,(int)$siteHeaderUnreadNotifications) }}</b>@endif</span></span></a>@endcan
201:            @can('reports.view')<a class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}"><span class="nav-link-content"><x-icon name="chart" /><span>Izveštaji</span></span></a>@endcan
203:            @canany(['stock.view','stock.adjust','inventory.receive','inventory.count','inventory.export','catalog.audit','security.view'])
205:                <summary class="{{ request()->routeIs('admin.stock.*','admin.inventory.*','admin.audit.*','admin.data-quality.*') ? 'active' : '' }}"><span><x-icon name="sliders" />Administracija</span></summary>
207:                    @can('stock.view')<a href="{{ route('admin.inventory.index') }}"><x-icon name="boxes" />Napredni lager</a><a href="{{ route('admin.stock.index') }}"><x-icon name="cube" />Sva kretanja lagera</a>@endcan
208:                    @can('catalog.audit')<a href="{{ route('admin.data-quality.index') }}"><x-icon name="health" />Data Quality Center</a><a href="{{ route('admin.audit.index') }}"><x-icon name="receipt" />Audit log</a>@endcan
215:                <summary class="{{ request()->routeIs('admin.users.*','admin.user-groups.*','admin.customer-portal.*') ? 'active' : '' }}"><span><x-icon name="users" />Korisnici</span></summary>
216:                <div class="nav-dropdown-menu"><a href="{{ route('admin.users.index') }}"><x-icon name="users" />Svi korisnici</a><a href="{{ route('admin.customer-portal.index') }}"><x-icon name="mail" />Customer Portal</a><a href="{{ route('admin.user-groups.index') }}"><x-icon name="user-check" />Grupe pristupa</a></div>
220:            @canany(['system.manage_settings','catalog.manage_taxonomy','receivables.manage','warranties.manage','field_operations.manage','service_parts.procurement','system.manage_users'])
222:                <summary class="{{ request()->routeIs('admin.settings.*','admin.dictionary.*','admin.receivables.index','admin.warranties.index','admin.field-service-teams.*','admin.service-part-suppliers.*','admin.user-groups.*') ? 'active' : '' }}"><span><x-icon name="settings" />Podešavanja</span></summary>
234:                    @canany(['catalog.manage_taxonomy','receivables.manage','warranties.manage','field_operations.manage','service_parts.procurement'])
237:                    @can('receivables.manage')<a href="{{ route('admin.receivables.index') }}"><x-icon name="wallet" />Pravila potraživanja</a>@endcan
238:                    @can('warranties.manage')<a href="{{ route('admin.warranties.index') }}"><x-icon name="shield" />Garancijska pravila</a>@endcan
239:                    @can('field_operations.manage')<a href="{{ route('admin.field-service-teams.index') }}"><x-icon name="users" />Terenske ekipe</a>@endcan
240:                    @can('service_parts.procurement')<a href="{{ route('admin.service-part-suppliers.index') }}"><x-icon name="receipt" />Dobavljači delova</a>@endcan
246:                    <a href="{{ route('admin.settings.order-emails.index') }}"><x-icon name="mail" />E-mail obaveštenja</a>
247:                    @can('automation.manage')<a href="{{ route('admin.settings.automation.index') }}"><x-icon name="cog" />Automatizacija</a>@endcan
250:                    @can('system.health')<a href="{{ route('admin.settings.system-health.index') }}"><x-icon name="health" />System Health</a>@endcan
267:@canany(['catalog.view','orders.manage','orders.view_own','system.manage_users','warranties.manage','warranties.view_own','after_sales.manage','after_sales.view_own','stock.view','reports.view','commissions.manage','commissions.view_own','notifications.view'])
276:                <input class="header-product-search-input" type="search" autocomplete="off" spellcheck="false" placeholder="Artikal, porudžbina, korisnik, garancija..." data-header-product-search-input aria-autocomplete="list" aria-controls="headerProductSearchResults">
483:                    bits.push(`lager ${Number(item.stock_quantity)}`);

--- Dashboard module cards: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/home.blade.php ---
MISSING_FILE=/home/icaffeco/ald1n-project/apps/cms/current/resources/views/home.blade.php
LAYOUT_MODULE_SIGNAL_COUNT=36
DASHBOARD_MODULE_SIGNAL_COUNT=0

============================================================
4. ROUTE SURFACE BY OPTIONAL MODULE
============================================================
  PUT       account/notifications .................................................................................. account.notifications › AccountController@notifications
  GET|HEAD  admin/after-sales ................................................................................... admin.after-sales.index › Admin\AfterSalesController@index
  GET|HEAD  admin/after-sales/{case} .............................................................................. admin.after-sales.show › Admin\AfterSalesController@show
  PATCH     admin/after-sales/{case} .......................................................................... admin.after-sales.update › Admin\AfterSalesController@update
  POST      admin/after-sales/{case}/actions ...................................................... admin.after-sales.actions.store › Admin\AfterSalesActionController@store
  POST      admin/after-sales/{case}/actions/{action}/cancel .................................... admin.after-sales.actions.cancel › Admin\AfterSalesActionController@cancel
  POST      admin/after-sales/{case}/actions/{action}/complete .............................. admin.after-sales.actions.complete › Admin\AfterSalesActionController@complete
  POST      admin/after-sales/{case}/actions/{action}/start ....................................... admin.after-sales.actions.start › Admin\AfterSalesActionController@start
  POST      admin/after-sales/{case}/messages ........................................................ admin.after-sales.messages.store › Admin\AfterSalesController@message
  GET|HEAD  admin/audit-log ................................................................................................... admin.audit.index › Admin\AuditLogController
  GET|HEAD  admin/audit-log.csv ............................................................................................. admin.audit.csv › Admin\AuditLogController@csv
  GET|HEAD  admin/commissions ................................................................................... admin.commissions.index › Admin\CommissionController@index
  GET|HEAD  admin/commissions.csv ................................................................................... admin.commissions.csv › Admin\CommissionController@csv
  GET|HEAD  admin/commissions.pdf ................................................................................... admin.commissions.pdf › Admin\CommissionController@pdf
  POST      admin/commissions/bulk-pay ..................................................................... admin.commissions.bulk-pay › Admin\CommissionController@bulkPay
  PATCH     admin/commissions/{commission}/status ..................................................... admin.commissions.transition › Admin\CommissionController@transition
  GET|HEAD  admin/customer-portal ....................................................................... admin.customer-portal.index › Admin\CustomerPortalController@index
  GET|HEAD  admin/customer-portal/conversations/{conversation} .......................... admin.customer-portal.conversations.show › Admin\PortalConversationController@show
  PATCH     admin/customer-portal/conversations/{conversation} ...................... admin.customer-portal.conversations.update › Admin\PortalConversationController@update
  POST      admin/customer-portal/conversations/{conversation}/reply .................. admin.customer-portal.conversations.reply › Admin\PortalConversationController@reply
  POST      admin/customer-portal/users ........................................................... admin.customer-portal.users.store › Admin\CustomerPortalController@store
  GET|HEAD  admin/customer-portal/users/{user} ...................................................... admin.customer-portal.users.show › Admin\CustomerPortalController@show
  POST      admin/customer-portal/users/{user}/invite ........................................... admin.customer-portal.users.invite › Admin\CustomerPortalController@invite
  POST      admin/customer-portal/users/{user}/orders/link .............................. admin.customer-portal.users.orders.link › Admin\CustomerPortalController@linkOrder
  DELETE    admin/customer-portal/users/{user}/sessions ........................ admin.customer-portal.users.sessions.revoke › Admin\CustomerPortalController@revokeSessions
  GET|HEAD  admin/field-operations .................................................................... admin.field-operations.index › Admin\FieldOperationsController@index
  GET|HEAD  admin/field-operations/{workOrder} .......................................................... admin.field-operations.show › Admin\FieldOperationsController@show
  POST      admin/field-operations/{workOrder}/cancel ............................................... admin.field-operations.cancel › Admin\FieldOperationsController@cancel
  POST      admin/field-operations/{workOrder}/complete ......................................... admin.field-operations.complete › Admin\FieldOperationsController@complete
  POST      admin/field-operations/{workOrder}/en-route .......................................... admin.field-operations.en-route › Admin\FieldOperationsController@enRoute
  POST      admin/field-operations/{workOrder}/on-site ............................................. admin.field-operations.on-site › Admin\FieldOperationsController@onSite
  POST      admin/field-operations/{workOrder}/parts ......................................... admin.field-operations.parts.store › Admin\FieldWorkOrderPartController@store
  POST      admin/field-operations/{workOrder}/parts/reserve ............................. admin.field-operations.parts.reserve › Admin\FieldWorkOrderPartController@reserve
  DELETE    admin/field-operations/{workOrder}/parts/{line} .............................. admin.field-operations.parts.destroy › Admin\FieldWorkOrderPartController@destroy
  PATCH     admin/field-operations/{workOrder}/schedule ......................................... admin.field-operations.schedule › Admin\FieldOperationsController@schedule
  GET|HEAD  admin/inventory ........................................................................................ admin.inventory.index › Admin\InventoryController@index
  GET|HEAD  admin/inventory.csv ........................................................................................ admin.inventory.csv › Admin\InventoryController@csv
  POST      admin/inventory/counts ................................................................................. admin.inventory.count › Admin\InventoryController@count
  POST      admin/inventory/receipts ........................................................................... admin.inventory.receive › Admin\InventoryController@receive
  GET|HEAD  admin/receivables .................................................................................. admin.receivables.index › Admin\ReceivablesController@index
  GET|HEAD  admin/receivables/export.csv ........................................................................... admin.receivables.csv › Admin\ReceivablesController@csv
  POST      admin/receivables/scan ............................................................................... admin.receivables.scan › Admin\ReceivablesController@scan
  PUT       admin/receivables/settings ...................................................... admin.receivables.settings.update › Admin\ReceivablesController@updateSettings
  GET|HEAD  admin/receivables/{receivable} ....................................................................... admin.receivables.show › Admin\ReceivablesController@show
  PATCH     admin/receivables/{receivable} ................................................................... admin.receivables.update › Admin\ReceivablesController@update
  POST      admin/receivables/{receivable}/contacts ................................................. admin.receivables.contacts.store › Admin\ReceivablesController@contact
  PUT       admin/receivables/{receivable}/plan .................................................................. admin.receivables.plan › Admin\ReceivablesController@plan
  POST      admin/receivables/{receivable}/reminder ...................................................... admin.receivables.reminder › Admin\ReceivablesController@reminder
  GET|HEAD  admin/reports ..................................................................................... admin.reports.index › Admin\ManagementReportController@index
  POST      admin/reports/deliveries/{delivery}/retry ................................................. admin.report-deliveries.retry › Admin\ReportScheduleController@retry
  GET|HEAD  admin/reports/inventory.csv .................................................................. admin.reports.inventory.csv › Admin\ReportController@inventoryCsv
  GET|HEAD  admin/reports/management.csv ............................................................... admin.reports.management.csv › Admin\ManagementReportController@csv
  GET|HEAD  admin/reports/management.pdf ............................................................... admin.reports.management.pdf › Admin\ManagementReportController@pdf
  GET|HEAD  admin/reports/orders.csv ................................................................................. admin.reports.orders.csv › Admin\ReportController@csv
  GET|HEAD  admin/reports/orders.pdf ................................................................................. admin.reports.orders.pdf › Admin\ReportController@pdf
  GET|HEAD  admin/reports/payments.csv ..................................................................... admin.reports.payments.csv › Admin\ReportController@paymentsCsv
  POST      admin/reports/schedules .................................................................... admin.report-schedules.store › Admin\ReportScheduleController@store
  PUT       admin/reports/schedules/{schedule} ....................................................... admin.report-schedules.update › Admin\ReportScheduleController@update
  DELETE    admin/reports/schedules/{schedule} ..................................................... admin.report-schedules.destroy › Admin\ReportScheduleController@destroy
  POST      admin/reports/schedules/{schedule}/run ......................................................... admin.report-schedules.run › Admin\ReportScheduleController@run
  PATCH     admin/reports/schedules/{schedule}/toggle ................................................ admin.report-schedules.toggle › Admin\ReportScheduleController@toggle
  GET|HEAD  admin/service-parts .............................................................................. admin.service-parts.index › Admin\ServicePartController@index
  POST      admin/service-parts .............................................................................. admin.service-parts.store › Admin\ServicePartController@store
  PUT       admin/service-parts/{part} ..................................................................... admin.service-parts.update › Admin\ServicePartController@update
  POST      admin/service-parts/{part}/adjust .............................................................. admin.service-parts.adjust › Admin\ServicePartController@adjust
  GET|HEAD  admin/settings/automation ................................................................... admin.settings.automation.index › Admin\AutomationController@index
  PUT       admin/settings/automation ................................................................. admin.settings.automation.update › Admin\AutomationController@update
  POST      admin/settings/automation/alerts/{alert}/resolve ................................. admin.settings.automation.alerts.resolve › Admin\AutomationController@resolve
  POST      admin/settings/automation/run ................................................................... admin.settings.automation.run › Admin\AutomationController@run
  GET|HEAD  admin/settings/system-health ........................................................... admin.settings.system-health.index › Admin\SystemHealthController@index
  POST      admin/settings/system-health/backup .................................................. admin.settings.system-health.backup › Admin\SystemHealthController@backup
  POST      admin/settings/system-health/prune ..................................................... admin.settings.system-health.prune › Admin\SystemHealthController@prune
  POST      admin/settings/system-health/run ........................................................... admin.settings.system-health.run › Admin\SystemHealthController@run
  GET|HEAD  admin/warranties ....................................................................................... admin.warranties.index › Admin\WarrantyController@index
  POST      admin/warranties/backfill ........................................................................ admin.warranties.backfill › Admin\WarrantyController@backfill
  POST      admin/warranties/rules ....................................................................... admin.warranties.rules.store › Admin\WarrantyController@storeRule
  PUT       admin/warranties/rules/{rule} .............................................................. admin.warranties.rules.update › Admin\WarrantyController@updateRule
  GET|HEAD  admin/warranties/{warranty} .............................................................................. admin.warranties.show › Admin\WarrantyController@show
  PUT       admin/warranties/{warranty} .......................................................................... admin.warranties.update › Admin\WarrantyController@update
  POST      admin/warranties/{warranty}/maintenance/{record}/complete ............................ admin.warranties.maintenance.complete › Admin\WarrantyController@complete
  POST      admin/warranties/{warranty}/maintenance/{record}/schedule ............................ admin.warranties.maintenance.schedule › Admin\WarrantyController@schedule
  POST      admin/warranties/{warranty}/void ......................................................................... admin.warranties.void › Admin\WarrantyController@void
  GET|HEAD  after-sales ..................................................................................................... after-sales.index › AfterSalesController@index
  GET|HEAD  after-sales/attachments/{attachment} ............................................................. after-sales.attachments.show › AfterSalesAttachmentController
  GET|HEAD  after-sales/{case} ................................................................................................ after-sales.show › AfterSalesController@show
  POST      after-sales/{case}/messages .......................................................................... after-sales.messages.store › AfterSalesController@message
  GET|HEAD  api/v1/admin/commissions .............................................................. api.v1.admin.commissions.index › Api\V1\Admin\CommissionController@index
  GET|HEAD  api/v1/admin/commissions.csv .............................................................. api.v1.admin.commissions.csv › Api\V1\Admin\CommissionController@csv
  GET|HEAD  api/v1/admin/commissions.pdf .............................................................. api.v1.admin.commissions.pdf › Api\V1\Admin\CommissionController@pdf
  POST      api/v1/admin/commissions/bulk-pay ................................................ api.v1.admin.commissions.bulk-pay › Api\V1\Admin\CommissionController@bulkPay
  GET|HEAD  api/v1/admin/commissions/{commission} ................................................... api.v1.admin.commissions.show › Api\V1\Admin\CommissionController@show
  PATCH     api/v1/admin/commissions/{commission}/status ................................ api.v1.admin.commissions.transition › Api\V1\Admin\CommissionController@transition
  POST      api/v1/admin/report-deliveries/{delivery}/retry ............................. api.v1.admin.report-deliveries.retry › Api\V1\Admin\ReportScheduleController@retry
  GET|HEAD  api/v1/admin/report-schedules ................................................ api.v1.admin.report-schedules.index › Api\V1\Admin\ReportScheduleController@index
  POST      api/v1/admin/report-schedules ................................................ api.v1.admin.report-schedules.store › Api\V1\Admin\ReportScheduleController@store
  PATCH     api/v1/admin/report-schedules/{schedule} ................................... api.v1.admin.report-schedules.update › Api\V1\Admin\ReportScheduleController@update
  DELETE    api/v1/admin/report-schedules/{schedule} ................................. api.v1.admin.report-schedules.destroy › Api\V1\Admin\ReportScheduleController@destroy
  POST      api/v1/admin/report-schedules/{schedule}/run ..................................... api.v1.admin.report-schedules.run › Api\V1\Admin\ReportScheduleController@run
  PATCH     api/v1/admin/report-schedules/{schedule}/toggle ............................ api.v1.admin.report-schedules.toggle › Api\V1\Admin\ReportScheduleController@toggle
  GET|HEAD  api/v1/admin/reports/management ..................................................... api.v1.admin.reports.management › Api\V1\Admin\ReportController@management
  GET|HEAD  api/v1/admin/reports/management.csv .......................................... api.v1.admin.reports.management.csv › Api\V1\Admin\ReportController@managementCsv
  GET|HEAD  api/v1/admin/reports/management.pdf .......................................... api.v1.admin.reports.management.pdf › Api\V1\Admin\ReportController@managementPdf
  GET|HEAD  api/v1/admin/system-health ........................................................ api.v1.admin.system-health.index › Api\V1\Admin\SystemHealthController@index
  GET|HEAD  api/v1/admin/warranties .................................................................. api.v1.admin.warranties.index › Api\V1\Admin\WarrantyController@index
  POST      api/v1/admin/warranties/backfill ................................................... api.v1.admin.warranties.backfill › Api\V1\Admin\WarrantyController@backfill
  GET|HEAD  api/v1/admin/warranties/rules ...................................................... api.v1.admin.warranties.rules.index › Api\V1\Admin\WarrantyController@rules
  POST      api/v1/admin/warranties/rules .................................................. api.v1.admin.warranties.rules.store › Api\V1\Admin\WarrantyController@storeRule
  PUT       api/v1/admin/warranties/rules/{rule} ......................................... api.v1.admin.warranties.rules.update › Api\V1\Admin\WarrantyController@updateRule
  GET|HEAD  api/v1/admin/warranties/{warranty} ......................................................... api.v1.admin.warranties.show › Api\V1\Admin\WarrantyController@show
  PUT       api/v1/admin/warranties/{warranty} ..................................................... api.v1.admin.warranties.update › Api\V1\Admin\WarrantyController@update
  GET|HEAD  api/v1/admin/warranties/{warranty}.pdf ....................................................... api.v1.admin.warranties.pdf › Api\V1\Admin\WarrantyController@pdf
  POST      api/v1/admin/warranties/{warranty}/maintenance/{record}/complete ....... api.v1.admin.warranties.maintenance.complete › Api\V1\Admin\WarrantyController@complete
  POST      api/v1/admin/warranties/{warranty}/maintenance/{record}/schedule ....... api.v1.admin.warranties.maintenance.schedule › Api\V1\Admin\WarrantyController@schedule
  POST      api/v1/admin/warranties/{warranty}/void .................................................... api.v1.admin.warranties.void › Api\V1\Admin\WarrantyController@void
  GET|HEAD  api/v1/after-sales ................................................................................ api.v1.after-sales.index › Api\V1\AfterSalesController@index
  GET|HEAD  api/v1/after-sales/attachments/{attachment} ............................................... api.v1.after-sales.attachments.show › AfterSalesAttachmentController
  GET|HEAD  api/v1/after-sales/{case} ........................................................................... api.v1.after-sales.show › Api\V1\AfterSalesController@show
  POST      api/v1/after-sales/{case}/messages ..................................................... api.v1.after-sales.messages.store › Api\V1\AfterSalesController@message
  GET|HEAD  api/v1/commissions ................................................................................ api.v1.commissions.index › Api\V1\CommissionController@index
  GET|HEAD  api/v1/commissions/{commission} ..................................................................... api.v1.commissions.show › Api\V1\CommissionController@show
  PUT       api/v1/me/notification-preferences ................................................. api.v1.me.notification-preferences › Api\V1\AccountController@notifications
  GET|HEAD  api/v1/notifications .......................................................................... api.v1.notifications.index › Api\V1\NotificationController@index
  POST      api/v1/notifications/read-all ............................................................ api.v1.notifications.read-all › Api\V1\NotificationController@readAll
  POST      api/v1/notifications/{notification}/read ........................................................ api.v1.notifications.read › Api\V1\NotificationController@read
  POST      api/v1/orders/{order}/after-sales ................................................................. api.v1.after-sales.store › Api\V1\AfterSalesController@store
  GET|HEAD  api/v1/orders/{order}/after-sales/options ..................................................... api.v1.after-sales.options › Api\V1\AfterSalesController@options
  GET|HEAD  api/v1/warranties .................................................................................... api.v1.warranties.index › Api\V1\WarrantyController@index
  GET|HEAD  api/v1/warranties/{warranty} ........................................................................... api.v1.warranties.show › Api\V1\WarrantyController@show
  GET|HEAD  api/v1/warranties/{warranty}.pdf ......................................................................... api.v1.warranties.pdf › Api\V1\WarrantyController@pdf
  GET|HEAD  commissions ..................................................................................................... commissions.index › CommissionController@index
  GET|HEAD  notifications ............................................................................................... notifications.index › NotificationController@index
  POST      notifications/read-all ................................................................................. notifications.read-all › NotificationController@readAll
  POST      notifications/{notification}/read ............................................................................. notifications.read › NotificationController@read
  POST      orders/{order}/after-sales ...................................................................................... after-sales.store › AfterSalesController@store
  GET|HEAD  orders/{order}/after-sales/create ............................................................................. after-sales.create › AfterSalesController@create
  GET|HEAD  warranties ......................................................................................................... warranties.index › WarrantyController@index
  GET|HEAD  warranties/{warranty} ................................................................................................ warranties.show › WarrantyController@show
  GET|HEAD  warranties/{warranty}.pdf .............................................................................................. warranties.pdf › WarrantyController@pdf
ROUTE_COUNT_COMMISSIONS=14
ROUTE_COUNT_AFTER_SALES=20
ROUTE_COUNT_FIELD_OPERATIONS=10
ROUTE_COUNT_SERVICE_PARTS=4
ROUTE_COUNT_WARRANTIES=26
ROUTE_COUNT_RECEIVABLES=9
ROUTE_COUNT_REPORTS=23
ROUTE_COUNT_INVENTORY=5
ROUTE_COUNT_AUTOMATION=4
ROUTE_COUNT_SYSTEM_HEALTH=5
ROUTE_COUNT_AUDIT_LOG=2
ROUTE_COUNT_CUSTOMER_PORTAL=9
ROUTE_COUNT_NOTIFICATIONS=8

============================================================
5. PERMISSION AND DOMAIN DEPENDENCY SIGNALS
============================================================
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:47:                Route::middleware('permission:commissions.manage')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:48:                    Route::get('/commissions', [AdminCommissionController::class, 'index'])->name('commissions.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:49:                    Route::post('/commissions/bulk-pay', [AdminCommissionController::class, 'bulkPay'])->name('commissions.bulk-pay');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:50:                    Route::get('/commissions.csv', [AdminCommissionController::class, 'csv'])->middleware('throttle:exports')->name('commissions.csv');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:51:                    Route::get('/commissions.pdf', [AdminCommissionController::class, 'pdf'])->middleware('throttle:exports')->name('commissions.pdf');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:52:                    Route::get('/commissions/{commission}', [AdminCommissionController::class, 'show'])->whereNumber('commission')->name('commissions.show');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:53:                    Route::patch('/commissions/{commission}/status', [AdminCommissionController::class, 'transition'])->whereNumber('commission')->name('commissions.transition');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:55:                Route::middleware('permission:warranties.manage')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:56:                    Route::get('/warranties', [AdminWarrantyController::class, 'index'])->name('warranties.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:57:                    Route::get('/warranties/rules', [AdminWarrantyController::class, 'rules'])->name('warranties.rules.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:58:                    Route::post('/warranties/rules', [AdminWarrantyController::class, 'storeRule'])->middleware('throttle:admin-write')->name('warranties.rules.store');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:59:                    Route::put('/warranties/rules/{rule}', [AdminWarrantyController::class, 'updateRule'])->whereNumber('rule')->middleware('throttle:admin-write')->name('warranties.rules.update');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:60:                    Route::post('/warranties/backfill', [AdminWarrantyController::class, 'backfill'])->middleware('throttle:admin-write')->name('warranties.backfill');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:61:                    Route::get('/warranties/{warranty}.pdf', [AdminWarrantyController::class, 'pdf'])->whereNumber('warranty')->name('warranties.pdf');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:62:                    Route::get('/warranties/{warranty}', [AdminWarrantyController::class, 'show'])->whereNumber('warranty')->name('warranties.show');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:63:                    Route::put('/warranties/{warranty}', [AdminWarrantyController::class, 'update'])->whereNumber('warranty')->name('warranties.update');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:64:                    Route::post('/warranties/{warranty}/void', [AdminWarrantyController::class, 'void'])->whereNumber('warranty')->name('warranties.void');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:65:                    Route::post('/warranties/{warranty}/maintenance/{record}/schedule', [AdminWarrantyController::class, 'schedule'])->whereNumber('warranty')->whereNumber('record')->name('warranties.maintenance.schedule');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:66:                    Route::post('/warranties/{warranty}/maintenance/{record}/complete', [AdminWarrantyController::class, 'complete'])->whereNumber('warranty')->whereNumber('record')->name('warranties.maintenance.complete');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:68:                Route::middleware('permission:reports.view')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:69:                    Route::get('/reports/management', [AdminReportController::class, 'management'])->name('reports.management');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:70:                    Route::get('/reports/management.csv', [AdminReportController::class, 'managementCsv'])->middleware(['permission:reports.export', 'throttle:exports'])->name('reports.management.csv');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:71:                    Route::get('/reports/management.pdf', [AdminReportController::class, 'managementPdf'])->middleware(['permission:reports.export', 'throttle:exports'])->name('reports.management.pdf');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:73:                Route::middleware('permission:reports.manage')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:82:                Route::get('/system-health', [AdminSystemHealthController::class, 'index'])->middleware('permission:system.health')->name('system-health.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:83:                Route::middleware('permission:security.view')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:175:        Route::middleware('permission:after_sales.view_own')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:181:        Route::middleware('permission:after_sales.create')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:192:        Route::middleware('permission:warranties.view_own')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:193:            Route::get('/warranties', [WarrantyController::class, 'index'])->name('warranties.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:194:            Route::get('/warranties/{warranty}.pdf', [WarrantyController::class, 'pdf'])->whereNumber('warranty')->name('warranties.pdf');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:195:            Route::get('/warranties/{warranty}', [WarrantyController::class, 'show'])->whereNumber('warranty')->name('warranties.show');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:198:        Route::middleware('permission:commissions.view_own')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:199:            Route::get('/commissions', [CommissionController::class, 'index'])->name('commissions.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:200:            Route::get('/commissions/{commission}', [CommissionController::class, 'show'])->whereNumber('commission')->name('commissions.show');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:203:        Route::middleware('permission:notifications.view')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:204:            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:205:            Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
/home/icaffeco/ald1n-project/apps/cms/current/routes/api.php:206:            Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:162:    Route::middleware('permission:after_sales.view_own')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:168:    Route::middleware('permission:after_sales.create')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:179:    Route::middleware('permission:warranties.view_own')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:180:        Route::get('/warranties', [WarrantyController::class, 'index'])->name('warranties.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:181:        Route::get('/warranties/{warranty}', [WarrantyController::class, 'show'])->whereNumber('warranty')->name('warranties.show');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:184:        ->whereNumber('warranty')->name('warranties.pdf');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:187:        ->middleware('permission:commissions.view_own')
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:188:        ->name('commissions.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:190:    Route::middleware('permission:notifications.view')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:191:        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:192:        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:193:        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:272:        Route::middleware('permission:receivables.manage')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:273:            Route::get('/receivables', [ReceivablesController::class, 'index'])->name('receivables.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:274:            Route::get('/receivables/export.csv', [ReceivablesController::class, 'csv'])->middleware('throttle:exports')->name('receivables.csv');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:275:            Route::put('/receivables/settings', [ReceivablesController::class, 'updateSettings'])->name('receivables.settings.update');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:276:            Route::post('/receivables/scan', [ReceivablesController::class, 'scan'])->name('receivables.scan');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:277:            Route::get('/receivables/{receivable}', [ReceivablesController::class, 'show'])->whereNumber('receivable')->name('receivables.show');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:278:            Route::patch('/receivables/{receivable}', [ReceivablesController::class, 'update'])->whereNumber('receivable')->name('receivables.update');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:279:            Route::put('/receivables/{receivable}/plan', [ReceivablesController::class, 'plan'])->whereNumber('receivable')->name('receivables.plan');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:280:            Route::post('/receivables/{receivable}/contacts', [ReceivablesController::class, 'contact'])->whereNumber('receivable')->name('receivables.contacts.store');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:281:            Route::post('/receivables/{receivable}/reminder', [ReceivablesController::class, 'reminder'])->whereNumber('receivable')->name('receivables.reminder');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:283:        Route::middleware('permission:after_sales.manage')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:290:        Route::middleware('permission:after_sales.execute')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:300:        Route::middleware('permission:field_operations.view')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:304:        Route::middleware('permission:field_operations.manage')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:316:        Route::get('/service-parts', [ServicePartController::class, 'index'])->middleware('permission:service_parts.view')->name('service-parts.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:317:        Route::middleware('permission:service_parts.manage')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:325:        Route::middleware('permission:warranties.manage')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:326:            Route::get('/warranties', [AdminWarrantyController::class, 'index'])->name('warranties.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:327:            Route::post('/warranties/rules', [AdminWarrantyController::class, 'storeRule'])->name('warranties.rules.store');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:328:            Route::put('/warranties/rules/{rule}', [AdminWarrantyController::class, 'updateRule'])->whereNumber('rule')->name('warranties.rules.update');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:329:            Route::post('/warranties/backfill', [AdminWarrantyController::class, 'backfill'])->middleware('throttle:admin-write')->name('warranties.backfill');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:330:            Route::get('/warranties/{warranty}', [AdminWarrantyController::class, 'show'])->whereNumber('warranty')->name('warranties.show');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:331:            Route::put('/warranties/{warranty}', [AdminWarrantyController::class, 'update'])->whereNumber('warranty')->name('warranties.update');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:332:            Route::post('/warranties/{warranty}/void', [AdminWarrantyController::class, 'void'])->whereNumber('warranty')->name('warranties.void');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:333:            Route::post('/warranties/{warranty}/maintenance/{record}/schedule', [AdminWarrantyController::class, 'schedule'])->whereNumber('warranty')->whereNumber('record')->name('warranties.maintenance.schedule');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:334:            Route::post('/warranties/{warranty}/maintenance/{record}/complete', [AdminWarrantyController::class, 'complete'])->whereNumber('warranty')->whereNumber('record')->name('warranties.maintenance.complete');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:337:        Route::middleware('permission:service_parts.procurement')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:355:        Route::middleware('permission:commissions.manage')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:356:            Route::get('/commissions', [CommissionController::class, 'index'])->name('commissions.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:357:            Route::patch('/commissions/{commission}/status', [CommissionController::class, 'transition'])->name('commissions.transition');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:358:            Route::post('/commissions/bulk-pay', [CommissionController::class, 'bulkPay'])->name('commissions.bulk-pay');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:359:            Route::get('/commissions.csv', [CommissionController::class, 'csv'])->middleware('throttle:exports')->name('commissions.csv');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:360:            Route::get('/commissions.pdf', [CommissionController::class, 'pdf'])->middleware('throttle:exports')->name('commissions.pdf');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:362:        Route::middleware('permission:reports.view')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:363:            Route::get('/reports', [ManagementReportController::class, 'index'])->name('reports.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:364:            Route::get('/reports/management.pdf', [ManagementReportController::class, 'pdf'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.management.pdf');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:365:            Route::get('/reports/management.csv', [ManagementReportController::class, 'csv'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.management.csv');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:366:            Route::get('/reports/orders.pdf', [ReportController::class, 'pdf'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.orders.pdf');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:367:            Route::get('/reports/orders.csv', [ReportController::class, 'csv'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.orders.csv');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:368:            Route::get('/reports/payments.csv', [ReportController::class, 'paymentsCsv'])->middleware(['permission:reports.export','throttle:exports'])->name('reports.payments.csv');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:369:            Route::get('/reports/inventory.csv', [ReportController::class, 'inventoryCsv'])->middleware(['permission:inventory.export','throttle:exports'])->name('reports.inventory.csv');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:371:        Route::middleware('permission:reports.manage')->group(function (): void {
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:397:        Route::get('/inventory', [InventoryController::class, 'index'])->middleware('permission:stock.view')->name('inventory.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:398:        Route::post('/inventory/receipts', [InventoryController::class, 'receive'])->middleware('permission:inventory.receive')->name('inventory.receive');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:399:        Route::post('/inventory/counts', [InventoryController::class, 'count'])->middleware('permission:inventory.count')->name('inventory.count');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:400:        Route::get('/inventory.csv', [InventoryController::class, 'csv'])->middleware(['permission:inventory.export','throttle:exports'])->name('inventory.csv');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:424:            Route::get('/automation', [AutomationController::class, 'index'])->middleware('permission:automation.manage')->name('automation.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:425:            Route::put('/automation', [AutomationController::class, 'update'])->middleware('permission:automation.manage')->name('automation.update');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:426:            Route::post('/automation/run', [AutomationController::class, 'run'])->middleware('permission:automation.manage')->name('automation.run');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:427:            Route::post('/automation/alerts/{alert}/resolve', [AutomationController::class, 'resolve'])->middleware('permission:automation.manage')->name('automation.alerts.resolve');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:428:            Route::get('/system-health', [SystemHealthController::class, 'index'])->middleware('permission:system.health')->name('system-health.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:429:            Route::post('/system-health/run', [SystemHealthController::class, 'run'])->middleware(['permission:system.health','throttle:admin-write'])->name('system-health.run');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:457:        Route::get('/audit-log', AuditLogController::class)->middleware('permission:catalog.audit')->name('audit.index');
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:458:        Route::get('/audit-log.csv', [AuditLogController::class, 'csv'])->middleware(['permission:audit.export','throttle:exports'])->name('audit.csv');
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:176:        Gate::define('commissions.manage', static fn (User $user): bool => $user->hasPermission('commissions.manage'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:177:        Gate::define('commissions.view_own', static fn (User $user): bool => $user->hasPermission('commissions.view_own'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:182:        Gate::define('after_sales.create', static fn (User $user): bool => $user->hasPermission('after_sales.create'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:183:        Gate::define('after_sales.view_own', static fn (User $user): bool => $user->hasPermission('after_sales.view_own'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:184:        Gate::define('after_sales.manage', static fn (User $user): bool => $user->hasPermission('after_sales.manage'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:185:        Gate::define('after_sales.execute', static fn (User $user): bool => $user->hasPermission('after_sales.execute'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:186:        Gate::define('field_operations.view', static fn (User $user): bool => $user->hasPermission('field_operations.view'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:187:        Gate::define('field_operations.manage', static fn (User $user): bool => $user->hasPermission('field_operations.manage'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:188:        Gate::define('service_parts.view', static fn (User $user): bool => $user->hasPermission('service_parts.view'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:189:        Gate::define('service_parts.manage', static fn (User $user): bool => $user->hasPermission('service_parts.manage'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:190:        Gate::define('service_parts.procurement', static fn (User $user): bool => $user->hasPermission('service_parts.procurement'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:191:        Gate::define('warranties.view_own', static fn (User $user): bool => $user->hasPermission('warranties.view_own'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:192:        Gate::define('warranties.manage', static fn (User $user): bool => $user->hasPermission('warranties.manage'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:193:        Gate::define('receivables.manage', static fn (User $user): bool => $user->hasPermission('receivables.manage'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:194:        Gate::define('notifications.view', static fn (User $user): bool => $user->hasPermission('notifications.view'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:199:        Gate::define('reports.view', static fn (User $user): bool => $user->hasPermission('reports.view'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:200:        Gate::define('reports.export', static fn (User $user): bool => $user->hasPermission('reports.export'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:201:        Gate::define('reports.manage', static fn (User $user): bool => $user->hasPermission('reports.manage'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:207:        Gate::define('inventory.receive', static fn (User $user): bool => $user->hasPermission('inventory.receive'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:208:        Gate::define('inventory.count', static fn (User $user): bool => $user->hasPermission('inventory.count'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:209:        Gate::define('inventory.export', static fn (User $user): bool => $user->hasPermission('inventory.export'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:210:        Gate::define('automation.manage', static fn (User $user): bool => $user->hasPermission('automation.manage'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:211:        Gate::define('system.health', static fn (User $user): bool => $user->hasPermission('system.health'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:213:        Gate::define('audit.export', static fn (User $user): bool => $user->hasPermission('audit.export'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Providers/AppServiceProvider.php:214:        Gate::define('security.view', static fn (User $user): bool => $user->hasPermission('security.view'));
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:116:                $this->audit->log('after_sales.created', 'Otvoren postprodajni slučaj '.$case->case_number, $case, null, $case->toArray(), ['order_id' => $lockedOrder->id], $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:138:        if ($visibility === 'internal' && !$actor->hasPermission('after_sales.manage')) abort(403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:165:                $this->audit->log('after_sales.message', 'Dodata poruka u slučaju '.$locked->case_number, $locked, null, ['visibility' => $visibility], null, $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesCaseService.php:252:            $this->audit->log('after_sales.updated', 'Ažuriran postprodajni slučaj '.$locked->case_number, $locked, $before, $locked->fresh()->toArray(), null, $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:39:            'inventory.receive',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:91:                $this->audit->log('inventory.receipt_posted', 'Proknjižen ulaz robe '.$receipt->receipt_number, $receipt, after: ['total_units' => $receipt->total_units, 'items' => count($items)], user: $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:109:            'inventory.count',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AdvancedInventoryService.php:164:                $this->audit->log('inventory.count_finalized', 'Zaključen popis '.$count->count_number, $count, after: ['total_variance' => $totalVariance, 'items' => count($items)], user: $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesAccessService.php:44:        return $user->hasPermission('after_sales.manage')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesAccessService.php:52:        if (!$user->hasPermission('after_sales.create')) return false;
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderPaymentService.php:170:        $this->audit->log('after_sales.refund_recorded', 'Evidentirana refundacija '.$payment->payment_number.' po radnji '.$action->action_number, $payment, after: ['amount_rsd' => $payment->amount_rsd, 'after_sales_action_id' => $action->id], user: $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:50:            'after_sales_manage' => $this->allows($actor, 'after_sales.manage'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:59:            'commission' => $isDirectSale ? null : $this->route('admin.commissions.index', ['q' => $detail['order']['order_number']]),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:180:            'after_sales_create' => $this->allows($actor, 'after_sales.create'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderDetailPresenter.php:185:            'commission' => $isDirectSale ? null : $this->route('commissions.index'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:196:                    'url' => route('warranties.show', $warranty),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CustomerPortalService.php:197:                    'pdf_url' => route('warranties.pdf', $warranty),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ApiAccessService.php:40:            'notifications' => $has('notifications.view'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ApiAccessService.php:41:            'commissions' => $has('commissions.view_own'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ApiAccessService.php:42:            'after_sales' => $has('after_sales.view_own') || $has('after_sales.create'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ApiAccessService.php:43:            'warranties' => $has('warranties.view_own'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ApiAccessService.php:44:            'field_operations' => $has('field_operations.view'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/FieldOperationsService.php:195:        abort_unless($actor->hasPermission('field_operations.manage'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionWorkflowService.php:148:            return $batch->fresh(['commissions.user', 'payer']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionReportService.php:73:        $this->managedQuery($actor, $filters)->reorder('order_commissions.id')->chunkById(500, static function (Collection $rows) use ($handle): void {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/CommissionReportService.php:88:        }, 'order_commissions.id', 'id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ReportScheduleService.php:210:        if (!$actor instanceof User || !$actor->hasPermission('reports.view')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderReportService.php:129:                $join->on('report_orders.id', '=', 'report_commissions.order_id');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderReportService.php:131:            ->where('report_commissions.status', '!=', 'cancelled')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderReportService.php:132:            ->sum('report_commissions.total_eur');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:156:            $this->audit->log('service_parts.reserved', 'Rezervisani delovi za '.$lockedWorkOrder->work_order_number, $lockedWorkOrder, null, null, ['line_count' => $lines->count()], $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:314:            $this->audit->log('service_parts.purchase_created', 'Kreiran zahtev za nabavku '.$request->request_number, $request, null, $request->toArray(), null, $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:352:            $this->audit->log('service_parts.purchase_'.$target, 'Promenjen status nabavke '.$locked->request_number, $locked, $before, $locked->toArray(), null, $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/ServicePartsInventoryService.php:379:        abort_unless($actor->hasPermission('service_parts.manage'), 403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:296:            return $isAdmin && \Illuminate\Support\Facades\Route::has('admin.receivables.show')
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OrderEmailOutboxService.php:297:                ? route('admin.receivables.show', $case)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:119:                'after_sales.action_created',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:175:            $this->audit->log('after_sales.action_started', 'Pokrenuta postprodajna radnja '.$locked->action_number, $locked, $before, $locked->toArray(), null, $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:229:            $this->audit->log('after_sales.action_completed', 'Izvršena postprodajna radnja '.$locked->action_number, $locked, $before, $locked->toArray(), ['case_number' => $locked->case->case_number], $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:266:            $this->audit->log('after_sales.action_cancelled', 'Otkazana postprodajna radnja '.$locked->action_number, $locked, $before, $locked->toArray(), null, $actor);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/AfterSalesActionService.php:376:        if (!$actor->hasPermission('after_sales.execute')) abort(403);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:67:        if ($actor->hasPermission('warranties.manage') || $actor->hasPermission('warranties.view_own')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:74:        if ($actor->hasPermission('after_sales.manage') || $actor->hasPermission('after_sales.view_own')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:278:        $managed = $actor->hasPermission('warranties.manage');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:279:        $own = $actor->hasPermission('warranties.view_own');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:352:                        ? route('admin.warranties.show', $warranty)
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:353:                        : route('warranties.show', $warranty),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:366:        $managed = $actor->hasPermission('after_sales.manage');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:367:        $own = $actor->hasPermission('after_sales.view_own');
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:487:        if ($actor->hasPermission('warranties.manage')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:488:            $add(true, 'Garancije', 'Otvori administraciju garancija.', route('admin.warranties.index'), ['garancija', 'warranty']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:489:        } elseif ($actor->hasPermission('warranties.view_own')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:490:            $add(true, 'Moje garancije', 'Otvori svoje garancije.', route('warranties.index'), ['garancija', 'warranty']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:493:        if ($actor->hasPermission('after_sales.manage')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:495:        } elseif ($actor->hasPermission('after_sales.view_own')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:511:            route('admin.inventory.index'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:516:            $actor->hasPermission('reports.view'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:519:            route('admin.reports.index'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:523:        if ($actor->hasPermission('commissions.manage')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:524:            $add(true, 'Provizije', 'Otvori administraciju provizija.', route('admin.commissions.index'), ['commission']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:525:        } elseif ($actor->hasPermission('commissions.view_own')) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:526:            $add(true, 'Moje provizije', 'Otvori svoje provizije.', route('commissions.index'), ['commission']);
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:530:            $actor->hasPermission('notifications.view'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/GlobalCommandSearchService.php:533:            route('notifications.index'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:217:                    'action_url' => route('admin.inventory.index', ['q' => $product->sku]),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:225:                            'event' => 'automation.low_stock',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:267:                                'event' => 'automation.low_stock_variant',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:315:                            'event' => 'automation.after_sales_overdue',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:366:                            'event' => 'automation.after_sales_action_overdue',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:414:                            'event' => 'automation.field_work_order_overdue',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:459:                            'event' => 'automation.field_work_order_unscheduled',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:498:                    foreach ($this->administrators()->filter(static fn (User $user): bool => $user->hasPermission('service_parts.manage') || $user->hasPermission('service_parts.procurement')) as $recipient) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:500:                            'event' => 'automation.service_part_low', 'title' => $alert->title, 'message' => $alert->message,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:533:                    foreach ($this->administrators()->filter(static fn (User $user): bool => $user->hasPermission('service_parts.procurement')) as $recipient) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:535:                            'event' => 'automation.service_part_purchase_overdue', 'title' => $alert->title, 'message' => $alert->message,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:563:                    'action_url' => route('admin.warranties.show', $warranty),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:571:                            'url' => route('warranties.show', $warranty), 'action_label' => 'Otvori garanciju',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:576:                    foreach ($this->administrators()->filter(static fn (User $user): bool => $user->hasPermission('warranties.manage')) as $recipient) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:603:                    'message' => $message, 'action_url' => route('admin.warranties.show', $warranty),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:611:                            'url' => route('warranties.show', $warranty), 'action_label' => 'Otvori garanciju',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:616:                    foreach ($this->administrators()->filter(static fn (User $user): bool => $user->hasPermission('warranties.manage')) as $recipient) {
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:668:                    'event' => 'automation.'.$type,
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:749:                    'event' => 'automation.daily_digest',
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalAutomationService.php:752:                    'url' => route('admin.settings.automation.index'),
/home/icaffeco/ald1n-project/apps/cms/current/app/Services/OperationalNotificationService.php:43:            'url' => route('commissions.index'),
CORE_MODULE_POLICY=catalog,orders,auth,users,settings,documents,payments
CORE_MODULES_LOCKED_NON_DISABLEABLE=YES
OPTIONAL_MODULE_CANDIDATES=commissions,after_sales,field_operations,service_parts,warranties,receivables,reports,inventory,automation,system_health,audit,customer_portal,notifications

============================================================
6. MOBILE BOOTSTRAP / ADMIN HUB FEATURE SIGNALS
============================================================

--- BootstrapController features: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/BootstrapController.php ---
30:            $unreadCount = $user->unreadNotifications()->count();
52:            'features' => $access->features($user),

--- Admin foundation controller: /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/AdminFoundationController.php ---
MISSING_FILE=/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Api/V1/Admin/AdminFoundationController.php

--- Mobile auth feature helper: /home/icaffeco/ald1n-project/apps/mobile/current/src/features/auth/auth-provider.tsx ---
7:import { clearGoogleCredentialState, getGoogleIdToken } from '@/features/auth/google-auth';
8:import type { BootstrapData, User } from '@/types/api';
15:  bootstrap: BootstrapData | null;
19:  refreshBootstrap: () => Promise<void>;
20:  replaceBootstrapUser: (user: User) => void;
24:  hasFeature: (feature: string) => boolean;
32:  const [bootstrap, setBootstrap] = useState<BootstrapData | null>(null);
36:    setBootstrap(null);
41:  const refreshBootstrap = useCallback(async () => {
42:    const next = await api.auth.bootstrap();
43:    setBootstrap(next);
46:  const replaceBootstrapUser = useCallback((user: User) => {
47:    setBootstrap((current) => current ? { ...current, user } : current);
53:    setBootstrap((current) => current ? {
74:      const data = await api.auth.bootstrap();
75:      setBootstrap(data);
79:      setBootstrap(null);
101:        const data = await api.auth.bootstrap();
102:        if (active) setBootstrap(data);
137:  const can = useCallback((permission: string) => Boolean(bootstrap?.permissions.includes(permission)), [bootstrap]);
138:  const hasFeature = useCallback((feature: string) => Boolean(bootstrap?.features[feature]), [bootstrap]);
141:    status, bootstrap, signIn, signInWithGoogle, signOut, refreshBootstrap,
142:    replaceBootstrapUser, setNotificationUnreadCount, requireReauthentication, can, hasFeature
144:    bootstrap, can, hasFeature, refreshBootstrap, replaceBootstrapUser, setNotificationUnreadCount, requireReauthentication,

--- Mobile admin hub: /home/icaffeco/ald1n-project/apps/mobile/current/src/app/(app)/admin/index.tsx ---
12:import { hasAdminAccess } from '@/features/admin/admin-access';
13:import { apiAdmin } from '@/features/admin/admin-api';
14:import { adminQueryKeys } from '@/features/admin/admin-query-keys';
18:export default function AdminIndexScreen() {
22:  const allowed = hasAdminAccess({
23:    permissions: bootstrap?.permissions ?? [],
28:    queryKey: adminQueryKeys.foundation(),
29:    queryFn: apiAdmin.foundation,
35:    return <UnavailableState title="Administracija nije dostupna" />;
39:    return <LoadingState label="Učitavanje admin prostora…" />;
49:    return <UnavailableState title="Admin podaci nisu dostupni" />;
57:        title="Administracija"
58:        eyebrow="Ald1n CMS · Admin"
63:        <Pill tone="primary">ADMIN RADNI PROSTOR</Pill>
65:          Centralizovan pristup administratorskim modulima.
79:            onPress={() => router.push('/admin/catalog/create')}
85:        {can('orders.manage') ? (
88:            onPress={() => router.push('/admin/orders')}
94:        {can('commissions.manage') ? (
97:            onPress={() => router.push('/admin/commissions')}
99:            Provizije admin
103:        {can('warranties.manage') ? (
106:            onPress={() => router.push('/admin/warranties')}
108:            Garancije admin
112:        {can('reports.view') ? (
115:            onPress={() => router.push('/admin/reports')}
117:            Izveštaji admin
121:        {can('system.health') ? (
124:            onPress={() => router.push('/admin/system-health')}
133:            onPress={() => router.push('/admin/audit')}
135:            Audit i bezbednost
141:        <Text style={styles.sectionTitle}>Admin moduli</Text>

============================================================
7. CURRENT MODULE FLAG COLLISION GUARDS
============================================================
