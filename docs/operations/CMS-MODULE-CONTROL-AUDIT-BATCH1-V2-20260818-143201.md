
============================================================
CMS MODULE CONTROL - READ-ONLY AUDIT - BATCH 1 V2
============================================================
DATE=Tue Aug 18 14:32:01 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-MODULE-CONTROL-AUDIT-BATCH1-V2-20260818-143201.md
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
V1_REPORT=/home/icaffeco/ald1n-project/docs/operations/CMS-MODULE-CONTROL-AUDIT-BATCH1-20260818-142025.md
V1_NO_MATCH_PIPEFAIL_INCIDENT=CONFIRMED_SAFE_READ_ONLY_EXIT
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
No syntax errors detected in /tmp/ald1n-module-control-audit.1w1I0q/settings-probe.php
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
EXISTING_MODULE_CONTROL_SOURCE_SIGNAL_COUNT=0

============================================================
8. QUALITY BASELINE
============================================================
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
PASS  korisnik vidi samo svoje provizije i minimum 20 EUR
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
PASS  beta7.20 migracija uvodi varijante specifikacije slike i snapshot
PASS  beta7.20 migracija je recovery-safe za MariaDB i proširuje istorijske module
PASS  beta7.20 varijanta ima SKU cenu lager status default i garanciju
PASS  beta7.20 default preferira aktivnu varijantu i roditelj sabira aktivan lager
PASS  beta7.20 serverska validacija štiti SKU i korelisane specifikacije
PASS  beta7.20 admin ima CRUD lager slike i default varijantu
PASS  beta7.20 UI filtrira zavisne specifikacije varijante
PASS  beta7.20 porudžbina čuva variant snapshot i vraća isti lager
PASS  beta7.20 postprodaja garancija i stock movement nose variant id
PASS  beta7.20 clone kopira varijante bez lagera i sa novim SKU
PASS  beta7.20 parent inventory korekcija je blokirana
PASS  beta7.20 filter i pretraga vide aktivne varijante
PASS  beta7.20 doctor proverava SKU default snapshot i aggregate
PASS  beta7.20 feature i smoke testovi postoje
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
PASS  beta7.23.1 catalog detail nema problematične inline Blade lance
PASS  beta7.23.1 catalog detail Blade direktive su izbalansirane
PASS  beta7.23.1 variant detail Feature i smoke regresija postoje
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
PASS  beta7.24.1 kandidati imaju transparentan izvor
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
PASS  v2.1.3.3 ProductVariantRequest zadržava validan SKU regex delimiter
PASS  v2.1.3.3 migracija povezuje listu diskova i izvedeni ukupni kapacitet
PASS  v2.1.3.3 stari kapacitet se bezbedno prenosi na prvi disk
PASS  v2.1.3.3 backend ne veruje ručnom ukupnom zbiru
PASS  v2.1.3.3 ukupni kapacitet je ispod diskova i readonly
PASS  v2.1.3.3 frontend sabira diskove i čuva početni legacy zbir
PASS  v2.1.3.3 proizvod i varijante dele isti storage model
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
CMS_STATIC_CHECK=PASS
MOBILE_TYPECHECK=SKIPPED_NPM_MISSING

============================================================
9. DECISION CONTRACT
============================================================
FIXES_BATCH1_SILENT_EXIT=YES_NO_MATCH_GREP_SAFE_UNDER_PIPEFAIL
MODULE_CONTROL_STORAGE=EXISTING_SETTINGS_TABLE_DYNAMIC_KEYS_PREFERRED
MODULE_CONTROL_ADMIN_SCOPE=SUPERADMIN_ONLY
MODULE_CONTROL_UI_LOCATION=MAIN_SETTINGS_DEDICATED_MODULES_PANEL_OR_PAGE
MODULE_CONTROL_DEFAULT_STATE=ALL_OPTIONAL_MODULES_ENABLED_FOR_BACKWARD_COMPATIBILITY
MODULE_CONTROL_DISABLE_SEMANTICS=HIDE_FROM_NAVIGATION_DASHBOARD_AND_EXPOSE_DISABLED_FEATURE_TO_MOBILE_BOOTSTRAP
MODULE_CONTROL_DATA_DELETE=NO
MODULE_CONTROL_SCHEMA_MIGRATION_EXPECTED=NO_IF_DYNAMIC_SETTINGS_CONFIRMED
MODULE_CONTROL_DOMAIN_LOGIC_DELETE=NO
MODULE_CONTROL_BACKGROUND_JOB_DISABLE=NO_IN_FIRST_PHASE
DIRECT_URL_HARD_BLOCK=DEFER_UNTIL_DEPENDENCY_MAP_IS_CONFIRMED
CMS_MODULE_CONTROL_AUDIT_BATCH1_V2=PASS
NEXT_ACTION=GENERATE_CMS_MODULE_CONTROL_IMPLEMENTATION_BATCH2_FROM_THIS_REPORT
REPORT_READY_TO_UPLOAD=/home/icaffeco/ald1n-project/docs/operations/CMS-MODULE-CONTROL-AUDIT-BATCH1-V2-20260818-143201.md
UPLOAD_THIS_REPORT_TO_CHAT=/home/icaffeco/ald1n-project/docs/operations/CMS-MODULE-CONTROL-AUDIT-BATCH1-V2-20260818-143201.md

============================================================
FINAL
============================================================
PASS: CMS MODULE CONTROL AUDIT BATCH 1 V2 COMPLETE
