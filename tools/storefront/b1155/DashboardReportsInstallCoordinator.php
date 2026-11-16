<?php
declare(strict_types=1);

/**
 * B1155 dashboard, registration page, sub-agent report and login redirects: ships 30 reviewed files (9 new, 21 modified) over the live tree, which is the manifest + B1146 + B1147
 * + B1148 + B1149 + B1150 + B1151 + B1152 + B1153 + B1154 (8574 files, equals apps/cms/current of the B1154 payload commit ad901a58 without tests); tests, CI and docs
 * excluded (the rule B1153 and B1154 used; docs/openapi.yaml is not in the runtime manifest and stays out), bin/static-check.php is a file of the runtime manifest, so it ships
 * and the tree stays equal to main. Payload (one source commit, origin/main SOURCE_COMMIT d2c07f62, the merge of PR 193, 194, 196, 197 and 198 over BASE_COMMIT ad901a58, whose
 * apps/cms/current equals the live tree): four new PHP classes (LocalAuthFormRequest, CustomerActivationRequest, SubagentSalesReportService, SubagentSalesReportController),
 * the registration stylesheet, the dashboard KPI and priority partials, the theme form partial (a NEW directory resources/views/public-shop/partials, created by the install and
 * removed by the rollback), the sub-agent sales report view, three backward compatible libraries (AppUrlOrigin::redirect, PublicStorefrontAppearance::loginBackground,
 * ManagementReportService::reportOrderIds with a sargable date range), the auth requests and controllers that redirect through localRedirect(), the no-script CSP, the admin
 * stylesheet, the static check, bootstrap/app.php (the validation redirect names AppUrlOrigin::redirect), the dashboard and layout and registration views, routes/web.php (two GET
 * routes: /admin/reports/subagents and /admin/reports/subagents.csv) and, after the routes, the management report view that links the new report. No migration, no composer change,
 * no .env, no config change.
 * The Storefront is OPEN in production: its state is recorded, re-read before the first change and around every smoke batch, and never written. SMTP settings are never read for
 * their values, written or tested, no mail is sent; only a hashed fingerprint of the effective mail transport is compared before and after. Password endpoints are never called,
 * nobody logs in: the AUTHENTICATED dashboard and the report with data are therefore NOT verified by this tool, only that the files are exact, that the views compile, that the
 * stylesheets are served by the web SAPI with the payload bytes, that the new classes load in the web SAPI, that the guest report URLs redirect to the login and that the
 * catalog and registration page policies hold.
 * Order: PREFLIGHT (read-only, collect-all; any blocker is a HOLD with nothing changed; Report1120 and the B1154 state, APP_URL exactly https://cms.ald1n.com, PHP extensions,
 * backups, digest check of every modified file, the 9 new paths absent, route names absent and 568 routes, baseline CSP of /katalog and /registracija) -> per-file backup into the
 * private B1155 namespace -> install in the reviewed dependency order, one group at a time with a revalidation pause between. Groups of this rollout: A the nine new files
 * (inert), B three libraries (backward compatible with their old callers), C requests, controllers, no-script CSP, admin stylesheet and static check, D bootstrap/app.php (guard
 * group, classes), E the views and the registration stylesheet proof (guard group, assets), F routes/web.php (guard group, classes again), G the management view (it renders the new
 * route, so it comes AFTER the routes). Before the first file of each guard group the full measured hold elapses and the web SAPI proves its premise (D and F: the 3 modified
 * and 4 new classes load from exactly the payload files; E: both changed stylesheets answer 200 text/css with the payload digest); the group is refused without that proof.
 * After D an immediate liveness smoke runs, after F route:clear, the CLI route check (570 routes, both names, GET) and a liveness smoke that includes the guest report URLs.
 * Each file via same-directory temp + fsync + rename (link for a new file, so nothing is ever clobbered) -> route:clear, view:clear, queue:restart (never a cache build) ->
 * targeted per-file opcache_invalidate through token-gated temporary probes -> wait max(realpath_cache_ttl, revalidate_freq) + 5 s -> a read-only Blade compile and lint of the
 * changed views in memory -> smokes, fresh-code probe, CLI feature check, mail-transport fingerprint, log scan, full tree verification.
 * Any failure after the first file restores the backed-up bytes of all files in exact reverse install order (stopping at the first failed restore, so the tree is always an exact
 * install prefix): G first, a short pause, then F, and the full measured hold after each of F, E and D is undone and before group A, a short pause between the other groups, the
 * new files and the created directory removed last; it re-invalidates and smoke-tests the old state. A re-run converges from the saved phase.
 * ONE place holds the payload: the block marked B1155_PAYLOAD below (BASE_COMMIT, SOURCE_COMMIT, STEPS and DIGESTS).
 * @author Ald1n <https://ald1n.com>
 */
final class DashboardReportsInstallCoordinator
{
    public const MANIFEST_SHA='d737387e60a981b3b404e0980d9b70cb95b6e1d506a39211981d49dcd12be4c6';
    public const MANIFEST_FILES=8531;
    /** The tree after B1146 (manifest + 12 new), after B1147 (+ 3), after B1148 (+ 2), after B1149 (+ 16 new), after B1150 (+ 2 new), after B1151 (+ 1 new), after B1152 (+ 2 new), after B1153 (+ 3 new), after B1154 (+ 2 new); this rollout adds the new files of STEPS. */
    public const B1146_TREE_FILES=8543;
    public const B1147_TREE_FILES=8546;
    public const B1148_TREE_FILES=8548;
    public const B1149_TREE_FILES=8564;
    public const B1150_TREE_FILES=8566;
    public const B1151_TREE_FILES=8567;
    public const B1152_TREE_FILES=8569;
    public const B1153_TREE_FILES=8572;
    public const B1154_TREE_FILES=8574;
    public const B1146_FILES=21;
    public const B1147_FILES=8;
    public const B1148_FILES=11;
    public const B1149_FILES=43;
    public const B1150_FILES=5;
    public const B1151_FILES=6;
    public const B1152_FILES=9;
    public const B1153_FILES=26;
    public const B1154_FILES=6;
    /** config/public_storefront.php after the B1145 unclamp: the staged original bytes. */
    public const CONFIG_SHA='0cfbc864a3d44822a9d14e80f6252e84eb208c9da5a25d5fe6989e4cad789818';
    /** Report 1120 is the B1154 PASS (state id 192319eb0107b9c10c427642c53a53a7); an earlier report of the same tool never matches this pattern. */
    public const B1154_REPORT_PATTERN='~^1120-B1154-INSTALLER-\d{8}-\d{6}\.md$~D';
    public const B1154_PASS='PASS_INSTALLED_ADMIN_SHELL_NAVIGATION_CSS_AND_VIEWS_SHOP_STATE_UNCHANGED';
    /** The B1153 terminal value (Report1119), the B1152 one (Report1118) and the B1151 one (Report1117), still needed to check the pinned B1153, B1152 and B1151 coordinators in b1153Plan(), b1152Plan() and b1151Plan(). */
    public const B1153_PASS='PASS_INSTALLED_LOGO_SIZES_MAIL_LOGO_CATALOG_VIEW_PER_PAGE_SHOP_STATE_UNCHANGED';
    public const B1152_PASS='PASS_INSTALLED_APP_URL_ORIGIN_PIN_PDF_LOGO_RASTER_SHOP_STATE_UNCHANGED';
    public const B1151_PASS='PASS_INSTALLED_LEGAL_EMAIL_MERCHANT_DRAFT_PASSWORD_THROTTLE_SHOP_STATE_UNCHANGED';
    /**
     * The production facts of the B1154 install this rollout is chained to (Report1120, docs/operations/1120-B1154-INSTALLER-20261115-105942.md): the SHA-256 of the report file as it
     * sits on the host (no CR byte in it; archived byte for byte in docs/operations, the digest is that of the git blob, not of a CRLF checkout) and the
     * id of the B1154 state directory. A different report or state is a HOLD with nothing changed. The chain gate compares the host file with this digest.
     */
    public const B1154_REPORT_SHA256='8840b73569db9f2dc1455b14bce5ec7824f27b417e4ef010ddcdcc837030ced0';
    public const B1154_STATE_ID='192319eb0107b9c10c427642c53a53a7';
    public const FINAL_PASS='PASS_INSTALLED_DASHBOARD_DROPDOWNS_REGISTRATION_SUBAGENT_REPORT_REDIRECTS_SHOP_STATE_UNCHANGED';
    public const RAN=71;
    /** The route count B1154 left (Report1120 ROUTES_CHECK routeCount): the count before this rollout. */
    public const ROUTES=568;
    /** The route count after: the two GET routes of the sub-agent report (the names are absent before and present after). */
    public const ROUTES_AFTER=570;
    public const ROUTES_BEFORE=self::ROUTES;
    public const ROUTE_SUBAGENTS='admin.reports.subagents';
    public const ROUTE_SUBAGENTS_CSV='admin.reports.subagents.csv';
    /** The two route names this payload adds (both GET): absent before, present after. */
    public const NEW_ROUTE_NAMES=[self::ROUTE_SUBAGENTS,self::ROUTE_SUBAGENTS_CSV];
    public const SUBAGENTS_PATH='/admin/reports/subagents';
    public const SUBAGENTS_CSV_PATH='/admin/reports/subagents.csv';
    /** The origin B1152 pinned: the preflight HOLDs unless config('app.url') is exactly this string (no trailing slash). */
    public const EXPECTED_APP_URL='https://cms.ald1n.com';
    // ===== B1155_PAYLOAD (begin): the only place that names the payload source, the install order and the digests. package.php and the CI gate read it. =====
    /** The pre-state commit: the code that is live after B1154 (Report1120), commit ad901a58 of origin/main; its apps/cms/current without tests equals the live tree (asserted at build time, byte for byte per file). */
    public const BASE_COMMIT='ad901a580ea85fffbd6567f1f12965b396680d79';
    /** origin/main at the time of writing: the merge of PR 193, 194, 196, 197 and 198 over BASE_COMMIT. Source key M in STEPS; its apps/cms/current is what the install produces (plus the tests that are not installed). */
    public const SOURCE_COMMIT='d2c07f62cee09b22248bcff2d4a8361523d7fee6';
    /**
     * The install order, [path under apps/cms/current, 'new'|'modified', group, source key M]. Groups of this rollout: A nine new files, all inert (nothing old references them; the
     * theme-form partial needs the NEW directory resources/views/public-shop/partials, created by the installer and removed by the rollback); B three libraries that only ADD methods (AppUrlOrigin::redirect,
     * PublicStorefrontAppearance::loginBackground, ManagementReportService::reportOrderIds; the date filter becomes a sargable range that selects the same rows), so every OLD caller keeps working;
     * C the three auth requests (they now extend the new LocalAuthFormRequest of group A), the three auth controllers (they call $request->localRedirect(), the request classes sit before them; a new
     * request with an OLD controller is fine because the old controllers use back()), the no-script CSP (its loginBackground() call sits in a try/catch Throwable), the admin stylesheet (pure
     * additions) and bin/static-check.php (an inert dev tool); D bootstrap/app.php (the validation redirect names AppUrlOrigin::redirect, so it is a GUARD group: the classes of A and B
     * must load in the web SAPI first, a broken bootstrap takes the whole site down); E the views, partials before the dashboard index and the layouts before the registration page (GUARD group:
     * both changed stylesheets must be served first); F routes/web.php (it names SubagentSalesReportController: GUARD group, classes again); G the management report view, AFTER the routes
     * because it renders route('admin.reports.subagents') for users with reports.view. Groups are non-decreasing in this list (plan() refuses anything else), every new file sits in a
     * lower group than the first guard group, every path is under apps/cms/current, none under tests/, database/, config/ or composer.*, routes/web.php is the only routes/ file and
     * bin/static-check.php the only bin/ file allowed (it is in the runtime manifest, so the tree must follow main).
     */
    public const STEPS=[
        ['app/Http/Requests/LocalAuthFormRequest.php','new','A','M'],['app/Http/Requests/CustomerActivationRequest.php','new','A','M'],
        ['app/Services/SubagentSalesReportService.php','new','A','M'],['app/Http/Controllers/Admin/SubagentSalesReportController.php','new','A','M'],
        ['public/assets/css/ald1n-public-register.css','new','A','M'],['resources/views/dashboard/partials/kpi-delta.blade.php','new','A','M'],
        ['resources/views/dashboard/partials/priorities.blade.php','new','A','M'],['resources/views/public-shop/partials/theme-form.blade.php','new','A','M'],
        ['resources/views/admin/reports/subagent-sales.blade.php','new','A','M'],['app/Support/AppUrlOrigin.php','modified','B','M'],
        ['app/Support/PublicStorefrontAppearance.php','modified','B','M'],['app/Services/ManagementReportService.php','modified','B','M'],
        ['app/Http/Requests/LoginRequest.php','modified','C','M'],['app/Http/Requests/ForgotPasswordRequest.php','modified','C','M'],
        ['app/Http/Requests/ResetPasswordRequest.php','modified','C','M'],['app/Http/Controllers/Auth/AuthenticatedSessionController.php','modified','C','M'],
        ['app/Http/Controllers/Auth/PasswordResetController.php','modified','C','M'],['app/Http/Controllers/Auth/CustomerActivationController.php','modified','C','M'],
        ['app/Http/Middleware/PublicNoScriptCsp.php','modified','C','M'],['public/assets/css/ald1n-admin.css','modified','C','M'],
        ['bin/static-check.php','modified','C','M'],['bootstrap/app.php','modified','D','M'],
        ['resources/views/dashboard/partials/operator-orders.blade.php','modified','E','M'],['resources/views/dashboard/partials/customer-center.blade.php','modified','E','M'],
        ['resources/views/dashboard/index.blade.php','modified','E','M'],['resources/views/layouts/app.blade.php','modified','E','M'],
        ['resources/views/public-shop/layout.blade.php','modified','E','M'],['resources/views/public-shop/register.blade.php','modified','E','M'],
        ['routes/web.php','modified','F','M'],['resources/views/admin/reports/management.blade.php','modified','G','M'],
    ];
    public const DIGESTS=[
        'app/Http/Requests/LocalAuthFormRequest.php'=>[null,'29beaa94bfbd933de296bbcd17cc1355bc4b5c6da1e430f39479a47aa6547df2'],
        'app/Http/Requests/CustomerActivationRequest.php'=>[null,'ad47cea2397bc208cb8e19130868dd137f93fdcf385ae0af4c3fb2fe909827fb'],
        'app/Services/SubagentSalesReportService.php'=>[null,'1b97db53622ad8e5dd83485ebd98c53bbe18b7b811d79e6380847e982fc229a6'],
        'app/Http/Controllers/Admin/SubagentSalesReportController.php'=>[null,'56d21c525ae3fea457552f4d9fa295cfd28c62e1b8574aec2b54d44b223b6ffc'],
        'public/assets/css/ald1n-public-register.css'=>[null,'bd2dcd6d9b999fe4e56f82647edc77b528b928a8fc28081b3a3fe188b2095c2f'],
        'resources/views/dashboard/partials/kpi-delta.blade.php'=>[null,'61c4e167f073a1e5ab464c1afd6ec69f276251d8c803bdb2a3e2a2a8248d2b1a'],
        'resources/views/dashboard/partials/priorities.blade.php'=>[null,'61bc9e508db9061a2301eec728a7255441aa2030149341050c1aab0f8dc1f4ac'],
        'resources/views/public-shop/partials/theme-form.blade.php'=>[null,'49541f02f1a0bf3a5572163c4e54276305aa57188edc0b655757ec4b37db173d'],
        'resources/views/admin/reports/subagent-sales.blade.php'=>[null,'368439b7b3e1aa17abbebcaaf8483cb74adf91ada7ba3312a94e5ec226a77061'],
        'app/Support/AppUrlOrigin.php'=>['3de284868251a100609ea8126d663993f6e690ed29b3a3c454fee725a9f265b2','277bd24a94311750dbbe33ab16549bf26138a38dfd02c822a0ff8edb190d9956'],
        'app/Support/PublicStorefrontAppearance.php'=>['a57aa32827510e3e69184d2d4c2303fe2e5785855a89348cadae4ad289550daf','d06e9826101e55b72eaf97bdb2fef22842730f9be87a1919b01035177192d99c'],
        'app/Services/ManagementReportService.php'=>['fe4cbcb2ccd2c7e175011f9f965a01036b2e0a6b33267e0a1f7ed7772c43d284','d178773693a59fbc0e92bd19a36f953b0ffe0634fa2b7f3982f11d1e15428151'],
        'app/Http/Requests/LoginRequest.php'=>['825c792be40b5872c1527e969106caf15d741e02a82449a2d3066ee5cd7a1a5f','f970c24ec4ea3388c7752ef81dd714f3abf996f312dfad2038fc93b6dc727c55'],
        'app/Http/Requests/ForgotPasswordRequest.php'=>['2466da8465acf4200e3856c86f158dac77c2b59411fb4a3dcdf5340652d29b9c','62a105218bdd6a19550c1f93206b9ce721947d099445aae790da17a3bfedbe0d'],
        'app/Http/Requests/ResetPasswordRequest.php'=>['568d0a812887a297bd760968b5401a281d96700c43d906d24a00123dae154512','5a0b08d0baea5badc6a3af698f1e49f09980264ca98515df1aebb7e17093ebca'],
        'app/Http/Controllers/Auth/AuthenticatedSessionController.php'=>['41a8d7e22540c668cbe3adb42fb6df16d4f3a0c6b5d267a6367afdbe857fe1c4','bc65b3825e5c8267b0b3ea0082ce43e136c8dca24ea784640aa085e0891bc5cb'],
        'app/Http/Controllers/Auth/PasswordResetController.php'=>['42664c8c7e6e9a472a7c7392bde4b7646f81e3aa8499656132c2b90b86d49547','ac96e8a466ebf58159153b761d038b19fc9a71a444cb2c163980f9b5270d45e1'],
        'app/Http/Controllers/Auth/CustomerActivationController.php'=>['08b23166e90495a85969de7e9c28a8502b3a9e19777bc0b9118e3dcd818cd296','dfdac343e8ca668e42d87edf64e9c326ed243758a9540ce6a2da1e65f1bd2e67'],
        'app/Http/Middleware/PublicNoScriptCsp.php'=>['2e752a32e0e6d3b9b8a605019df2cdfa44723ee41e635543f01663f5dda37212','54611ec18e18fe329842726078c616e9dc9bb7ffb3ddae480b70a2b7194d9336'],
        'public/assets/css/ald1n-admin.css'=>['059d9f1de74f959da7f98df9f0efaee00d617cb8fc9853ae1a85ca2ab2ecbeb9','2f4211404c531c5afb0c448dd48ad873745f0914cd9a6385da1f26aa905b9667'],
        'bin/static-check.php'=>['696323c24a54b31daa76a67b3b372714df1822acd11b82fda62c66af587ed590','8ee508deff935bd55edfdeafc3030a736d253810f9bb76e8fea15a14550938d7'],
        'bootstrap/app.php'=>['38ba2b88403e6df71fd1b2daacd93071ded7fd286ecf47c4d51374429e031923','ea8fd34f9730426723dc0342e0ef98b289e658a3f417fea7c1848c19df03be04'],
        'resources/views/dashboard/partials/operator-orders.blade.php'=>['358eedd122e51b2c424cd5b2a2d67f35835143828df02d46dd4ee1f9952441b4','b46002803a914ff5c1c8c2015fe04ce36d842b423708e3761c70d7e183cf08b5'],
        'resources/views/dashboard/partials/customer-center.blade.php'=>['52375d9202778408e9e64c64eb79e14ba1f51c5f305e5ce8dd432409a4408c13','129ec5be948a113fc27a94b2a48a551bde0bafa17a7153fd046416e993262aa4'],
        'resources/views/dashboard/index.blade.php'=>['08d53aa6ec19ec0a09645f9f9d77feb054d2c6d152b113d9afebcd5c2e977286','7779926c524cba298de76fa2f43a2faeea32b7013107ddfe1da25e52802d4181'],
        'resources/views/layouts/app.blade.php'=>['ed8607dcba5ec2926dd3ba68fc3e5e85e9810453fcdafeebce30b6a1b427c5ed','de5970cc671deb6061d58734004172b6780c71ee2d67c19779b1e87b53923c97'],
        'resources/views/public-shop/layout.blade.php'=>['a618a2345e02e24c22a4b33275d4a7aa537700d608a3a5f4d4c0f48416c274a9','f112267412e7d82e2b8c656af576dd5438dc3fc8c36f2dd9c334022ec3e51e66'],
        'resources/views/public-shop/register.blade.php'=>['40960fffe92adc68efabc3d1acd37828eee3b0748ccf01e32ff6b43a0c6ecff6','57718b11bfd16d85777cc0d9e488bc2b4e88a27ea3a981dc1b43b47537fce99e'],
        'routes/web.php'=>['a8ebd8c838f5dfd154969570d82a26e53313d4d8677f46c90b886fe3af38c1b9','b72b49f2dbcc6229955bacc2cbf85c402de9e46b5a862d1d8b92094cd93ff363'],
        'resources/views/admin/reports/management.blade.php'=>['1b106c931d66e50b978dcd01d6172bafb57872df8d7b907c2c8dc9dd02e8cfb6','e8d38007a1740503325649c12700fb516bdb16f5c3d89d5ca91b72a5ca2eb026'],
    ];
    // ===== B1155_PAYLOAD (end) =====
    /**
     * The guard groups and the proof that comes before the first file of each (full measured hold first): D and F need the web SAPI to load the classes (the 3 modified libraries of
     * group B and the 4 new classes of group A, each file exactly the payload; bootstrap/app.php and routes/web.php name them), E needs both changed stylesheets served 200 text/css with
     * the payload digest (the views link them). After F the route check follows. Group G needs no proof of its own: the routes it renders were proven after F.
     */
    public const GUARDS=['D'=>'classes','E'=>'assets','F'=>'classes'];
    /** The two changed stylesheets: served by the web SAPI with exactly the payload bytes before the views that link them go live (and again after the install). */
    public const ADMIN_CSS_PATHS=['/assets/css/ald1n-admin.css'=>'public/assets/css/ald1n-admin.css','/assets/css/ald1n-public-register.css'=>'public/assets/css/ald1n-public-register.css'];
    /** The B1154 stylesheet that this payload does not touch: still served with its B1154 payload bytes (checked in the installed acceptance and the preflight smokes). */
    public const TOKENS_CSS_PATH='/assets/css/ald1n-tokens.css';
    public const TOKENS_CSS_SHA256='9fc9e850adee6325bce7edc75a4e70fe9d01a7076cfecdac80614147ad2c9701';
    /** The views the read-only acceptance step compiles and lints in memory: every new and every modified Blade view of the payload. */
    public const VIEW_CHECKS=[
        'resources/views/dashboard/partials/kpi-delta.blade.php','resources/views/dashboard/partials/priorities.blade.php','resources/views/public-shop/partials/theme-form.blade.php','resources/views/admin/reports/subagent-sales.blade.php',
        'resources/views/dashboard/partials/operator-orders.blade.php','resources/views/dashboard/partials/customer-center.blade.php','resources/views/dashboard/index.blade.php','resources/views/layouts/app.blade.php',
        'resources/views/public-shop/layout.blade.php','resources/views/public-shop/register.blade.php','resources/views/admin/reports/management.blade.php',
    ];
    /**
     * Source-level facts of the payload edits that the CLI helper reads from the live files (key => [path, needle]): present in every payload file, absent from its
     * pre-state bytes where the file is modified (package.php asserts both). Installed: all true; old: all false (a new file is simply not there).
     */
    public const SOURCE_MARKERS=[
        'localAuthRequest'=>['app/Http/Requests/LocalAuthFormRequest.php','public function localRedirect('],
        'customerActivationRequest'=>['app/Http/Requests/CustomerActivationRequest.php','extends LocalAuthFormRequest'],
        'subagentService'=>['app/Services/SubagentSalesReportService.php','reportOrderIds('],
        'subagentController'=>['app/Http/Controllers/Admin/SubagentSalesReportController.php',"route('admin.reports.subagents')"],
        'publicRegisterCss'=>['public/assets/css/ald1n-public-register.css','Public registration page (/registracija)'],
        'kpiDeltaView'=>['resources/views/dashboard/partials/kpi-delta.blade.php','Comparison line of a KPI tile'],
        'prioritiesView'=>['resources/views/dashboard/partials/priorities.blade.php','d1-priority-heading'],
        'themeFormView'=>['resources/views/public-shop/partials/theme-form.blade.php','shop-theme-group'],
        'subagentView'=>['resources/views/admin/reports/subagent-sales.blade.php',"route('admin.reports.subagents.csv'"],
        'appUrlRedirect'=>['app/Support/AppUrlOrigin.php','public static function redirect('],
        'appearanceLoginBg'=>['app/Support/PublicStorefrontAppearance.php','public static function loginBackground('],
        'mgmtReportOrderIds'=>['app/Services/ManagementReportService.php','function reportOrderIds('],
        'loginRequest'=>['app/Http/Requests/LoginRequest.php','extends LocalAuthFormRequest'],
        'forgotRequest'=>['app/Http/Requests/ForgotPasswordRequest.php','extends LocalAuthFormRequest'],
        'resetRequest'=>['app/Http/Requests/ResetPasswordRequest.php','extends LocalAuthFormRequest'],
        'sessionController'=>['app/Http/Controllers/Auth/AuthenticatedSessionController.php','$request->localRedirect()'],
        'passwordResetController'=>['app/Http/Controllers/Auth/PasswordResetController.php','$request->localRedirect()'],
        'activationController'=>['app/Http/Controllers/Auth/CustomerActivationController.php','$request->localRedirect()'],
        'noScriptCsp'=>['app/Http/Middleware/PublicNoScriptCsp.php','PublicStorefrontAppearance::loginBackground()'],
        'adminCssDropdown'=>['public/assets/css/ald1n-admin.css','.a1-nav-dd__group{display:grid'],
        'staticCheckDash'=>['bin/static-check.php','a1-kpi--link'],
        'bootstrapRedirect'=>['bootstrap/app.php','AppUrlOrigin::redirect('],
        'operatorOrdersView'=>['resources/views/dashboard/partials/operator-orders.blade.php','a1-item--order'],
        'customerCenterView'=>['resources/views/dashboard/partials/customer-center.blade.php','$preferenceFields = ['],
        'dashboardIndexView'=>['resources/views/dashboard/index.blade.php','a1-hero--split'],
        'layoutDropdown'=>['resources/views/layouts/app.blade.php','a1-nav-dd__group'],
        'shopLayoutAuthShell'=>['resources/views/public-shop/layout.blade.php','$shopAuth'],
        'registerLoginBg'=>['resources/views/public-shop/register.blade.php','PublicStorefrontAppearance::loginBackground()'],
        'routeSubagentsCsv'=>['routes/web.php',"->name('reports.subagents.csv')"],
        'managementSubagentLink'=>['resources/views/admin/reports/management.blade.php',"route('admin.reports.subagents')"],
    ];
    /** The commit a STEPS entry is taken from. */
    public static function sourceCommit(string $key):string
    {
        return match($key){'M'=>self::SOURCE_COMMIT,default=>throw new RuntimeException('OVERLAY_STEPS_SOURCE')};
    }
    /** The B1154 payload (installed by Report1120): its code base (the live tree after Report1119, commit 482d2fa5) and its source commit, origin/main ad901a58 (the BASE_COMMIT of this rollout). */
    public const B1154_BASE_COMMIT='482d2fa51e12617cc5a29fba7ab5802954fa07e2';
    public const B1154_COMMIT='ad901a580ea85fffbd6567f1f12965b396680d79';
    /** The B1153 payload (installed by Report1119): its code base (the live tree after Report1118) and its source commit, origin/main d760bb2c. */
    public const B1153_BASE_COMMIT='af1e46a30169a410ebb269bdfc021f656a4538e9';
    public const B1153_COMMIT='d760bb2c87a5365db305df8aa6f79226770a32f3';
    /** The B1152 payload heads (installed by Report1118) and the code base they were built over (the B1151 tree); the B1151 payload heads (Report1117) and the B1150 payload head (Report1116) they were built over; the B1149 payload head (Report1115) and the commit pair it was built over. */
    public const B1152_BASE_COMMIT='7e22afd4b7cde338d5198d9e1ad0e2e3c72f1d9a';
    public const B1152_URL_COMMIT='8505b11064589ea5514944fea954a6d5ef8acdb1';
    public const B1152_PDF_COMMIT='cfe97253bf4c441b06d2ea6194e2180bf4e23f98';
    public const B1152_MAIN_COMMIT='fc954517a5bfafe0ab6029f938aa956ca27c9575';
    public const B1151_EMAIL_COMMIT='43d3ab6eac41bfb8f69554f2c933e880c5a1e00e';
    public const B1151_THROTTLE_COMMIT='ae00c5f668a8385815f5b6fbdba8a66e55ca4d2e';
    public const B1150_COMMIT='14e628ba61b77e3c6854bed100c634d1999b5f33';
    public const STOREFRONT_COMMIT='7e7481582fa0be3e95503a81b316be63836e508c';
    public const STOREFRONT_BASE_COMMIT='eca7755c6efe05143a6d3cd19f8c9e70b9ee3515';
    public const STOREFRONT_MAIN_COMMIT='55d06b9cc010c976dfc56743763dbfeb09b87acf';
    /** The B1148 payload head (PR 155), the B1147 payload head (PR 154) and the B1146 main. */
    public const GLASS_COMMIT='560828458974a56a8f7173b986a40d46629e4d8b';
    public const CATALOG_COMMIT='764bf5135ad6241d64f728e4b141cc2713dc6c48';
    public const LEGAL_COMMIT='eabe16af8c19fade882e25c70425eb43c3e17ed9';
    /** The named route of GET /media/p/{image}/{rendition} (B1150, present in BOTH states) and its public path prefix. */
    public const MEDIA_ROUTE_NAME='media.product.signed';
    public const MEDIA_PATH_PREFIX='/media/p/';
    /** What ProductImage::url must start with (APP_URL is the bare https origin, asserted by the environment check). */
    public const MEDIA_URL_PREFIX='https://cms.ald1n.com/media/p/';
    /** The B1149 layout/CSS/gallery/catalog markers: they stay in the tree through this rollout, so they hold in BOTH states (package.php asserts them in the old and in the new bytes). */
    public const MARKERS=['layout'=>'aria-label="Tema"','css'=>'@media (prefers-contrast:more){.shop-theme-option','gallery'=>'.shop-ratio-r34','catalog'=>'ald1n-public-gallery.css'];
    /** What the installed catalog page carries and the old one does not: the per-page form and the view switch (read as text, only counted). */
    public const PER_PAGE_MARKER='class="shop-perpage"';
    public const VIEW_SWITCH_MARKER='class="shop-view-group"';
    public const LIST_MARKER='shop-products-list';

    /** The classes the new app/*.php files of STEPS define, by PSR-4 (App\ is app/): the CLI feature check and the web-SAPI probe load exactly these. */
    public static function newClasses():array
    {
        $out=[];foreach(self::STEPS as [$p,$kind]){if($kind==='new'&&str_starts_with($p,'app/')&&str_ends_with($p,'.php'))$out[]='App\\'.str_replace('/','\\',substr($p,4,-4));}return $out;
    }

    /**
     * The classes the web-SAPI class proof loads before the guard groups D and F: the new classes plus the app/*.php files of STEPS that sit in group B (the three libraries bootstrap/app.php and
     * routes/web.php depend on; they are already in place when the proof runs). Each must load from exactly its payload file. The pinned LoadProbe takes at most 8 classes; this rollout has 7.
     */
    public static function proofClasses():array
    {
        $out=self::newClasses();foreach(self::STEPS as [$p,$kind,$group]){if($kind==='modified'&&$group==='B'&&str_starts_with($p,'app/')&&str_ends_with($p,'.php'))$out[]='App\\'.str_replace('/','\\',substr($p,4,-4));}return $out;
    }

    /** Files in the tree after this rollout: the B1154 tree plus the new files of STEPS (derived, never typed twice). */
    public static function installedTreeFiles():int
    {
        $new=0;foreach(self::STEPS as [,$kind])if($kind==='new')$new++;return self::B1154_TREE_FILES+$new;
    }
    public const REGISTER_PATH='/registracija';
    public const CATALOG_PATH='/katalog';
    /** The foreign Referer the redirect check sends: the old validation redirect (back()) follows it, the pinned redirect of this payload must not. */
    public const FOREIGN_REFERER='https://evil.example/x';
    public const TURNSTILE_ORIGIN='https://challenges.cloudflare.com';
    public const YOUTUBE_FRAME_ORIGIN='https://www.youtube-nocookie.com';
    public const LEGAL=['/pravne-informacije','/uslovi-prodaje','/politika-privatnosti','/podaci-o-trgovcu','/pravo-na-odustanak','/reklamacije','/dostava-i-placanje','/kolacici'];
    public const SHOP=['/katalog','/korpa','/porucivanje','/registracija'];
    public const SYSTEM=['/login','/up'];
    public const CSS_PATH='/assets/css/ald1n-public-storefront.css';
    public const GALLERY_CSS_PATH='/assets/css/ald1n-public-gallery.css';
    public const THEME_PATH='/tema';
    public const SMTP_PATH='/admin/settings/smtp';
    /** The merchant settings page of B1151 (stays as it is): a guest is redirected to the login in both states (never logged in, never a form). */
    public const MERCHANT_PATH='/admin/settings/public-storefront';
    /** The document settings page whose logo preview this rollout changes: a guest is redirected to the login in both states. PDF generation itself is never exercised. */
    public const DOCUMENTS_PATH='/admin/settings/documents';
    /** Where the merchant e-mail is shown for sure (the trader page rows) and what Cloudflare e-mail obfuscation leaves behind (counted as a fact, never printed). */
    public const TRADER_PATH='/podaci-o-trgovcu';
    public const EMAIL_ARTIFACTS=['__cf_email__','data-cfemail','/cdn-cgi/l/email-protection'];
    public const B1146_ROUTE_NAMES=11;
    /** The PHP extensions the new code needs in the CLI (the web SAPI is checked by the owner, see the README): all required, plus one image library. */
    public const EXTENSIONS=['dom','libxml','fileinfo','mbstring'];
    private const IN_FLIGHT=['BACKED_UP','INSTALL_INTENT','FILES_INSTALLED','ACCEPTANCE_INTENT','ROLLBACK_INTENT','ROLLBACK_INCOMPLETE'];
    private array $state=[];
    private string $base;
    private string $pointer;
    private mixed $lock=null;
    private object $web;
    private string $php=PHP_BINARY;
    private array $allowedSapis=['litespeed'];
    private string $stage;
    private string $b1146Base;
    private string $b1147Base;
    private string $b1148Base;
    private string $b1149Base;
    private string $b1150Base;
    private string $b1151Base;
    private string $b1152Base;
    private string $b1153Base;
    private string $b1154Base;
    /** Production defaults (Report1120, B1154 state id, route counts, APP_URL); the CI fixture sets its own measured values as explicit doubles. */
    private string $b1154ReportSHA256=self::B1154_REPORT_SHA256;
    private string $b1154StateId=self::B1154_STATE_ID;
    private int $expectedRoutes=self::ROUTES;
    private int $expectedRoutesAfter=self::ROUTES_AFTER;
    private string $expectedAppUrl=self::EXPECTED_APP_URL;
    /** The e-mail obfuscation counts of the last smokes() call (facts only; the old-state ones are kept in the saved state). */
    private ?array $emailFacts=null;
    /** The CSP facts of the last smokes() call (catalog and registration page, nonce normalized, booleans for the Turnstile and YouTube sources): the preflight copies them into the saved state as the baseline. */
    private array $cspSeen=[];
    private string $appStateScript;
    private string $routesScript;
    private string $featureScript;
    private int $censusSeconds=75;
    private int $minFreeBytes=536870912;
    /** Monotonic time of the last tree change made by THIS process (never taken from a saved state of another process). */
    private ?int $lastChangeNs=null;
    private ?int $runStartNs=null;
    /** Wall-clock budget from the start of this run to the first change; beyond it the run HOLDs before any change. */
    private int $budgetSeconds=900;
    /**
     * The shop routes run behind throttle:public-shop-browse (120 per minute per ip(); without TrustProxies that key can be
     * a shared Cloudflare edge address). Every smoke GET is paced, a 429 honours Retry-After (capped), the throttled routes
     * share ONE limiter key, so after one GET stays 429 the remaining throttled GETs of the batch are skipped, and a batch
     * still limited after one whole-batch retry is INCONCLUSIVE, never a failure.
     */
    private int $paceMicros=700000;
    private int $rateLimitPauseSeconds=65;
    private int $rateLimitCapSeconds=120;
    private array $limitBatch=['limited'=>false,'waited'=>0];
    private const RETRY_AFTER_CAP=65;
    /**
     * Worst-case wall time of one liveness smoke after a group against a HANGING server: up to 6 requests (/up, /login, a legal page, /katalog, the first product
     * page, /tema; plus from group D the foreign-Referer redirect request, from group E the three stylesheets and the two guest redirects, from group F the two guest report
     * URLs, so 14 at most), each up to the 25 s curl timeout plus the 0.7 s pacing, no 429 waiting: 14 x 25.7 = 360 s.
     */
    private const LIVENESS_SECONDS=370;
    /** The web-SAPI proofs: this many rounds (GETs of both changed stylesheets, or class-load probes), all must pass; each round is bounded by two 25 s timeouts of the pinned client. */
    private const WEB_PROOF_REQUESTS=5;
    private const WEB_PROOF_SECONDS=60;
    /** Upper bound of the requests in one smoke batch: 2 system, 8 legal, 5 storefront CSS, 2 /tema, 6 guest admin pages (incl. the two report URLs), 3 stylesheets, 1 logo, 15 shop (two of them the per-page and view queries, the registration page and the foreign-Referer request), 1 origin Vary, spare. */
    private const SMOKE_GETS=60;
    private const UNVERIFIED_CODES=['SMOKES_RATE_LIMITED_429','SHOP_STATE_CHANGED_DURING_ACCEPTANCE_STOREFRONT_NOT_VERIFIED','NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES','VARY_COOKIE_NOT_VERIFIABLE_EDGE_HIDES_IT_AND_NO_ORIGIN_ROUTE'];
    private bool $scheduleGate=true;
    private ?int $fixedNow=null;
    private int $scheduleNeedSeconds=1800;
    private int $defaultBackupSeconds=2400;
    private ?Closure $fault=null;
    private ?array $manifest=null;
    private array $plan;
    private array $b1146;
    private array $b1147;
    private array $b1148;
    private array $b1149;
    private array $b1150;
    private array $b1151;
    private array $b1152;
    private array $b1153;
    private array $b1154;
    private array $markers;
    /** The views the read-only acceptance step compiles and lints (every new and modified view of this rollout); the fixture adds a deliberately broken one. */
    private array $viewChecks=self::VIEW_CHECKS;
    /** CI fixture only: other bytes for the CLI boot proof of bootstrap/app.php (production leaves it null: the payload bytes are proven). */
    private ?string $bootProofBytes=null;
    private string $cssVersion;
    private string $oldCssVersion;
    private string $galleryVersion;
    public string $final='HOLD';

    public function __construct(private string $root,private string $home,array $package,private string $incoming,private string $mode='install',private bool $ignoreSchedule=false)
    {
        if($ignoreSchedule&&$mode!=='rollback')throw new RuntimeException('OPERATOR_SCHEDULE_OVERRIDE');
        if($root!=='/home/icaffeco/ald1n-project'||$home!=='/home/icaffeco'||realpath($root)!==$root||realpath($home)!==$home||!function_exists('posix_geteuid')||!extension_loaded('curl')||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('HOST_PROFILE');
        $this->base=$home.'/.ald1n-b1155-state';$this->pointer=$this->base.'/current.json';$this->stage=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU';$this->b1146Base=$home.'/.ald1n-b1146-state';$this->b1147Base=$home.'/.ald1n-b1147-state';$this->b1148Base=$home.'/.ald1n-b1148-state';$this->b1149Base=$home.'/.ald1n-b1149-state';$this->b1150Base=$home.'/.ald1n-b1150-state';$this->b1151Base=$home.'/.ald1n-b1151-state';$this->b1152Base=$home.'/.ald1n-b1152-state';$this->b1153Base=$home.'/.ald1n-b1153-state';$this->b1154Base=$home.'/.ald1n-b1154-state';
        // The HTTP client is DashboardReportsWeb (below): the B1153 one (LogoCatalogWeb of the owner's b1153-coordinator.php, pinned by digest: the pinned B1150 client plus the two catalog layout queries) for
        // everything, plus ONE fixed request it does not allow (GET /katalog?stock=x with a foreign Referer, never followed). No extension is needed for the stylesheets: MediaWeb::target() accepts any plain
        // path with the release marker (the version query ?v= stays reserved for the three versionable paths, and is never used here).
        $this->appStateScript=$incoming.'/b1145-app-state.php';$this->routesScript=$incoming.'/b1146-app-check.php';$this->featureScript=$incoming.'/b1155-app-check.php';$this->web=new DashboardReportsWeb();
        $this->load($package,$incoming);putenv('GIT_OPTIONAL_LOCKS=0');
        if(!in_array($mode,['install','rollback'],true))throw new RuntimeException('OPERATOR_MODE');
    }

    private function load(array $package,string $incoming):void
    {
        $this->plan=self::plan($package,$incoming);$this->b1146=self::b1146Plan($package);$this->b1147=self::b1147Plan($package);$this->b1148=self::b1148Plan($package);$this->b1149=self::b1149Plan($package);$this->b1150=self::b1150Plan($package);$this->b1151=self::b1151Plan($package);$this->b1152=self::b1152Plan($package);$this->b1153=self::b1153Plan($package);$this->b1154=self::b1154Plan($package);$this->markers=self::markers($package);
        foreach(['cssVersion','oldCssVersion','galleryVersion'] as $k){$v=$package[$k]??null;if(!is_string($v)||!preg_match('/^[0-9]{1,12}$/D',$v))throw new RuntimeException('CSS_VERSION_SCOPE');$this->$k=$v;}
    }

    /** The overlay plan from b1155-package.json must be exactly STEPS (paths, kinds, groups, order) with digests and a payload. */
    public static function plan(array $package,string $incoming):array
    {
        if(self::STEPS===[]||self::BASE_COMMIT===self::SOURCE_COMMIT)throw new RuntimeException('B1155_PAYLOAD_NOT_FILLED_IN');
        foreach([self::BASE_COMMIT,self::SOURCE_COMMIT] as $c)if(preg_match('/^[a-f0-9]{40}$/D',$c)!==1)throw new RuntimeException('B1155_PAYLOAD_NOT_FILLED_IN');
        $last='';$seen=[];foreach(self::STEPS as [$p,$k,$g,$src]){if(!in_array($k,['new','modified'],true)||!preg_match('/^[A-G]$/D',$g)||$g<$last||$src!=='M'||isset($seen[$p])||str_starts_with($p,'tests/')||str_starts_with($p,'docs/')||(str_starts_with($p,'bin/')&&$p!=='bin/static-check.php')||(str_starts_with($p,'routes/')&&$p!=='routes/web.php')||preg_match('~^(database/|config/|composer\.|\.env)~',$p)===1)throw new RuntimeException('OVERLAY_STEPS_SCOPE');$last=$g;$seen[$p]=true;}
        // The order invariants of this rollout: every new file sits in a LOWER group than the first guard group (D), the guard groups are exactly GUARDS and hold the files their proofs protect (D bootstrap/app.php, F routes/web.php),
        // the groups are A..G without a gap and the management view (it renders the new route) is the LAST step, after routes/web.php.
        $newTop='';foreach(self::STEPS as [$p,$k,$g])if($k==='new'&&$g>$newTop)$newTop=$g;$groups=array_values(array_unique(array_column(self::STEPS,2)));$guards=array_keys(self::GUARDS);
        $inGroup=static fn(string $grp)=>array_values(array_map(static fn($s)=>$s[0],array_filter(self::STEPS,static fn($s)=>$s[2]===$grp)));
        if($groups!==['A','B','C','D','E','F','G']||$last!=='G'||$newTop===''||$newTop>=$guards[0]||self::STEPS[0][2]!=='A'||self::STEPS[0][1]!=='new'||$inGroup('D')!==['bootstrap/app.php']||$inGroup('F')!==['routes/web.php']||$inGroup('G')!==['resources/views/admin/reports/management.blade.php']||self::STEPS[count(self::STEPS)-1][0]!=='resources/views/admin/reports/management.blade.php')throw new RuntimeException('OVERLAY_GUARD_GROUPS_NOT_D_E_F_OR_THE_MANAGEMENT_VIEW_NOT_LAST_AFTER_THE_ROUTES');
        // The reviewed digests are coded once more here, so a package built over other bytes is refused on the host as well.
        if(array_keys(self::DIGESTS)!==array_map(static fn($s)=>$s[0],self::STEPS))throw new RuntimeException('OVERLAY_DIGESTS_NOT_THE_STEPS');
        $overlay=$package['overlay']??null;if(!is_array($overlay)||count($overlay)!==count(self::STEPS)||($package['sourceCommit']??null)!==self::SOURCE_COMMIT||($package['baseCommit']??null)!==self::BASE_COMMIT)throw new RuntimeException('OVERLAY_PLAN_SCOPE');$plan=[];
        foreach(array_values($overlay) as $i=>$e){
            [$path,$kind,$group,$src]=self::STEPS[$i];
            if(!is_array($e)||($e['path']??null)!==$path||($e['kind']??null)!==$kind||($e['group']??null)!==$group||($e['source']??null)!==self::sourceCommit($src)||!preg_match('/^b1155-payload-\d{2}\.bin$/D',(string)($e['payload']??''))||!preg_match('/^[a-f0-9]{64}$/D',(string)($e['payloadSHA256']??''))||!is_int($e['size']??null))throw new RuntimeException('OVERLAY_PLAN_ENTRY');
            $pre=$e['preSHA256']??null;if(($kind==='new')!==($pre===null)||($pre!==null&&!preg_match('/^[a-f0-9]{64}$/D',$pre))||$pre===$e['payloadSHA256'])throw new RuntimeException('OVERLAY_PLAN_PRE_STATE');
            if($pre!==self::DIGESTS[$path][0]||$e['payloadSHA256']!==self::DIGESTS[$path][1])throw new RuntimeException('OVERLAY_PLAN_DIGEST_NOT_THE_REVIEWED_ONE');
            InstallFileOps::relative($path);$plan[]=['n'=>$i+1,'path'=>$path,'kind'=>$kind,'group'=>$group,'payload'=>$incoming.'/'.$e['payload'],'payloadSHA256'=>$e['payloadSHA256'],'preSHA256'=>$pre,'size'=>$e['size']];
        }
        return $plan;
    }

    /** One earlier overlay list (path, kind, sha256, preSHA256) with exactly $count entries and $new new files. */
    private static function priorPlan(mixed $list,int $count,int $new,string $code):array
    {
        if(!is_array($list)||count($list)!==$count)throw new RuntimeException($code.'_PLAN_SCOPE');$out=[];$n=0;
        foreach($list as $e){
            $path=$e['path']??null;$kind=$e['kind']??null;$sha=$e['sha256']??null;$pre=$e['preSHA256']??null;
            if(!is_string($path)||isset($out[$path])||!in_array($kind,['new','modified'],true)||!is_string($sha)||!preg_match('/^[a-f0-9]{64}$/D',$sha)||(($kind==='new')!==($pre===null))||($pre!==null&&(!is_string($pre)||!preg_match('/^[a-f0-9]{64}$/D',$pre))))throw new RuntimeException($code.'_PLAN_ENTRY');
            InstallFileOps::relative($path);$out[$path]=['path'=>$path,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];if($kind==='new')$n++;
        }
        if($n!==$new)throw new RuntimeException($code.'_PLAN_NEW_COUNT');return $out;
    }

    /** The 21 B1146 entries (eabe16af over 77a3efea): with them the 8531-file manifest becomes the 8543-file tree. */
    public static function b1146Plan(array $package):array {return self::priorPlan($package['b1146Overlay']??null,self::B1146_FILES,self::B1146_TREE_FILES-self::MANIFEST_FILES,'B1146');}

    /** The 8 B1147 entries (764bf513 over eabe16af, the pinned B1147 STEPS). */
    public static function b1147Plan(array $package):array
    {
        $out=self::priorPlan($package['b1147Overlay']??null,self::B1147_FILES,self::B1147_TREE_FILES-self::B1146_TREE_FILES,'B1147');
        // The same paths and kinds the approved B1147 coordinator installed (its pinned bytes are loaded by the entry).
        $expected=[];foreach(CatalogOverlayCoordinator::STEPS as [$p,$k])$expected[$p]=$k;$got=array_map(static fn($e)=>$e['kind'],$out);ksort($expected);ksort($got);if($got!==$expected||CatalogOverlayCoordinator::SOURCE_COMMIT!==self::CATALOG_COMMIT)throw new RuntimeException('B1147_PLAN_NOT_THE_APPROVED_B1147_SET');
        return $out;
    }

    /** The 11 B1148 entries (560828458 over 764bf513, the pinned B1148 STEPS): the 8548-file tree that is live now. */
    public static function b1148Plan(array $package):array
    {
        $out=self::priorPlan($package['b1148Overlay']??null,self::B1148_FILES,self::B1148_TREE_FILES-self::B1147_TREE_FILES,'B1148');
        $expected=[];foreach(GlassOverlayCoordinator::STEPS as [$p,$k])$expected[$p]=$k;$got=array_map(static fn($e)=>$e['kind'],$out);ksort($expected);ksort($got);
        if($got!==$expected||GlassOverlayCoordinator::SOURCE_COMMIT!==self::GLASS_COMMIT||GlassOverlayCoordinator::BASE_COMMIT!==self::CATALOG_COMMIT||GlassOverlayCoordinator::MAIN_COMMIT!==self::LEGAL_COMMIT||GlassOverlayCoordinator::INSTALLED_TREE_FILES!==self::B1148_TREE_FILES)throw new RuntimeException('B1148_PLAN_NOT_THE_APPROVED_B1148_SET');
        return $out;
    }

    /** The 43 B1149 entries (STOREFRONT_COMMIT 7e748158 over eca7755c, the pinned B1149 STEPS): the 8564-file tree that is live now. */
    public static function b1149Plan(array $package):array
    {
        $out=self::priorPlan($package['b1149Overlay']??null,self::B1149_FILES,self::B1149_TREE_FILES-self::B1148_TREE_FILES,'B1149');
        $expected=[];foreach(StorefrontInstallCoordinator::STEPS as [$p,$k])$expected[$p]=$k;$got=array_map(static fn($e)=>$e['kind'],$out);ksort($expected);ksort($got);
        if($got!==$expected||StorefrontInstallCoordinator::SOURCE_COMMIT!==self::STOREFRONT_COMMIT||StorefrontInstallCoordinator::BASE_COMMIT!==self::STOREFRONT_BASE_COMMIT||StorefrontInstallCoordinator::MAIN_COMMIT!==self::STOREFRONT_MAIN_COMMIT||StorefrontInstallCoordinator::INSTALLED_TREE_FILES!==self::B1149_TREE_FILES)throw new RuntimeException('B1149_PLAN_NOT_THE_APPROVED_B1149_SET');
        return $out;
    }

    /** The 5 B1150 entries (14e628ba over 7e748158, the pinned B1150 STEPS): the 8566-file tree that is live now (Report1116). */
    public static function b1150Plan(array $package):array
    {
        $out=self::priorPlan($package['b1150Overlay']??null,self::B1150_FILES,self::B1150_TREE_FILES-self::B1149_TREE_FILES,'B1150');
        $expected=[];foreach(MediaInstallCoordinator::STEPS as [$p,$k])$expected[$p]=$k;$got=array_map(static fn($e)=>$e['kind'],$out);ksort($expected);ksort($got);
        if($got!==$expected||MediaInstallCoordinator::SOURCE_COMMIT!==self::B1150_COMMIT||MediaInstallCoordinator::BASE_COMMIT!==self::STOREFRONT_COMMIT||MediaInstallCoordinator::installedTreeFiles()!==self::B1150_TREE_FILES||MediaInstallCoordinator::MEDIA_ROUTE_NAME!==self::MEDIA_ROUTE_NAME)throw new RuntimeException('B1150_PLAN_NOT_THE_APPROVED_B1150_SET');
        return $out;
    }

    /** The 6 B1151 entries (the pinned B1151 STEPS: EMAIL_COMMIT 43d3ab6e and THROTTLE_COMMIT ae00c5f6 over 14e628ba): the 8567-file tree that is live now (Report1117). */
    public static function b1151Plan(array $package):array
    {
        $out=self::priorPlan($package['b1151Overlay']??null,self::B1151_FILES,self::B1151_TREE_FILES-self::B1150_TREE_FILES,'B1151');
        $expected=[];foreach(LegalAccountInstallCoordinator::STEPS as [$p,$k])$expected[$p]=$k;$got=array_map(static fn($e)=>$e['kind'],$out);ksort($expected);ksort($got);
        if($got!==$expected||LegalAccountInstallCoordinator::EMAIL_COMMIT!==self::B1151_EMAIL_COMMIT||LegalAccountInstallCoordinator::THROTTLE_COMMIT!==self::B1151_THROTTLE_COMMIT||LegalAccountInstallCoordinator::BASE_COMMIT!==self::B1150_COMMIT||LegalAccountInstallCoordinator::installedTreeFiles()!==self::B1151_TREE_FILES||LegalAccountInstallCoordinator::FINAL_PASS!==self::B1151_PASS||LegalAccountInstallCoordinator::ROUTES!==self::ROUTES)throw new RuntimeException('B1151_PLAN_NOT_THE_APPROVED_B1151_SET');
        return $out;
    }

    /** The 9 B1152 entries (the pinned B1152 STEPS: URL_COMMIT 8505b110 and PDF_COMMIT cfe97253 over 7e22afd4): the 8569-file tree that is live now (Report1118). */
    public static function b1152Plan(array $package):array
    {
        $out=self::priorPlan($package['b1152Overlay']??null,self::B1152_FILES,self::B1152_TREE_FILES-self::B1151_TREE_FILES,'B1152');
        $expected=[];foreach(OriginPinInstallCoordinator::STEPS as [$p,$k])$expected[$p]=$k;$got=array_map(static fn($e)=>$e['kind'],$out);ksort($expected);ksort($got);
        if($got!==$expected||OriginPinInstallCoordinator::URL_COMMIT!==self::B1152_URL_COMMIT||OriginPinInstallCoordinator::PDF_COMMIT!==self::B1152_PDF_COMMIT||OriginPinInstallCoordinator::BASE_COMMIT!==self::B1152_BASE_COMMIT||OriginPinInstallCoordinator::MAIN_COMMIT!==self::B1152_MAIN_COMMIT||OriginPinInstallCoordinator::installedTreeFiles()!==self::B1152_TREE_FILES||OriginPinInstallCoordinator::FINAL_PASS!==self::B1152_PASS||OriginPinInstallCoordinator::ROUTES!==self::ROUTES||OriginPinInstallCoordinator::EXPECTED_APP_URL!==self::EXPECTED_APP_URL)throw new RuntimeException('B1152_PLAN_NOT_THE_APPROVED_B1152_SET');
        foreach(OriginPinInstallCoordinator::DIGESTS as $path=>[$pre,$post])if(($out[$path]['sha256']??null)!==$post||($out[$path]['preSHA256']??null)!==$pre)throw new RuntimeException('B1152_PLAN_DIGEST_NOT_THE_APPROVED_ONE');
        return $out;
    }

    /**
     * The 26 B1153 entries (the pinned B1153 STEPS: d760bb2c over af1e46a3, class LogoCatalogInstallCoordinator of the owner's b1153-coordinator.php): the 8572-file tree that is live now
     * (Report1119). Paths, kinds, the source and base commits, the tree count, the PASS value and every digest are compared with the pinned B1153 constants.
     */
    public static function b1153Plan(array $package):array
    {
        $out=self::priorPlan($package['b1153Overlay']??null,self::B1153_FILES,self::B1153_TREE_FILES-self::B1152_TREE_FILES,'B1153');
        $expected=[];foreach(LogoCatalogInstallCoordinator::STEPS as [$p,$k])$expected[$p]=$k;$got=array_map(static fn($e)=>$e['kind'],$out);ksort($expected);ksort($got);
        if($got!==$expected||LogoCatalogInstallCoordinator::SOURCE_COMMIT!==self::B1153_COMMIT||LogoCatalogInstallCoordinator::BASE_COMMIT!==self::B1153_BASE_COMMIT||LogoCatalogInstallCoordinator::installedTreeFiles()!==self::B1153_TREE_FILES||LogoCatalogInstallCoordinator::FINAL_PASS!==self::B1153_PASS||LogoCatalogInstallCoordinator::ROUTES!==self::ROUTES||LogoCatalogInstallCoordinator::EXPECTED_APP_URL!==self::EXPECTED_APP_URL||LogoCatalogInstallCoordinator::MANIFEST_SHA!==self::MANIFEST_SHA)throw new RuntimeException('B1153_PLAN_NOT_THE_APPROVED_B1153_SET');
        foreach(LogoCatalogInstallCoordinator::DIGESTS as $path=>[$pre,$post])if(($out[$path]['sha256']??null)!==$post||($out[$path]['preSHA256']??null)!==$pre)throw new RuntimeException('B1153_PLAN_DIGEST_NOT_THE_APPROVED_ONE');
        return $out;
    }

    /**
     * The 6 B1154 entries (the pinned B1154 STEPS: ad901a58 over 482d2fa5, class AdminShellInstallCoordinator of the owner's b1154-coordinator.php): the 8574-file tree that is live now
     * (Report1120). Paths, kinds, the source and base commits, the tree count, the PASS value and every digest are compared with the pinned B1154 constants.
     */
    public static function b1154Plan(array $package):array
    {
        $out=self::priorPlan($package['b1154Overlay']??null,self::B1154_FILES,self::B1154_TREE_FILES-self::B1153_TREE_FILES,'B1154');
        $expected=[];foreach(AdminShellInstallCoordinator::STEPS as [$p,$k])$expected[$p]=$k;$got=array_map(static fn($e)=>$e['kind'],$out);ksort($expected);ksort($got);
        if($got!==$expected||AdminShellInstallCoordinator::SOURCE_COMMIT!==self::B1154_COMMIT||AdminShellInstallCoordinator::BASE_COMMIT!==self::B1154_BASE_COMMIT||AdminShellInstallCoordinator::installedTreeFiles()!==self::B1154_TREE_FILES||AdminShellInstallCoordinator::FINAL_PASS!==self::B1154_PASS||AdminShellInstallCoordinator::ROUTES!==self::ROUTES||AdminShellInstallCoordinator::EXPECTED_APP_URL!==self::EXPECTED_APP_URL||AdminShellInstallCoordinator::MANIFEST_SHA!==self::MANIFEST_SHA)throw new RuntimeException('B1154_PLAN_NOT_THE_APPROVED_B1154_SET');
        foreach(AdminShellInstallCoordinator::DIGESTS as $path=>[$pre,$post])if(($out[$path]['sha256']??null)!==$post||($out[$path]['preSHA256']??null)!==$pre)throw new RuntimeException('B1154_PLAN_DIGEST_NOT_THE_APPROVED_ONE');
        return $out;
    }

    /** The B1149 markers (layout, css, gallery, catalog): they are in the tree before and after this rollout (package.php asserts them in the old and in the new bytes; the layout marker moved into the theme-form partial in the payload, the rendered pages still carry it). */
    public static function markers(array $package):array
    {
        $m=$package['markers']??null;if(!is_array($m))throw new RuntimeException('MARKER_SCOPE');$out=[];
        foreach(['layout','css','gallery','catalog'] as $k){if(!is_string($m[$k]??null)||!preg_match('/^[A-Za-z0-9 ="._\[\]:()@,{}-]{6,80}$/D',$m[$k])||$m[$k]!==self::MARKERS[$k])throw new RuntimeException('MARKER_SCOPE');$out[$k]=$m[$k];}
        return $out;
    }

    /**
     * No Cloudflare change and no purge is needed: the views link the changed admin stylesheet and the new registration stylesheet as ?v=<file modification time>, a URL the edge has never
     * seen (the new file did not exist, the changed one gets a new modification time, and the tool asks for both only with a random release marker, never under the URL the views link, so
     * no stale copy or 404 can be cached under it). The stylesheets are written before the views that link them. The storefront stylesheet URL does not change.
     * Cloudflare settings are not touched. After a rollback the old views link the old modification time again.
     */
    public function purgeStep(bool $rollback=false):string
    {
        if($rollback)return '1_NO_CLOUDFLARE_PURGE_NEEDED_THE_RESTORED_VIEWS_LINK_THE_OLD_STYLESHEET_URLS_AND_THE_STOREFRONT_CSS_URL_DID_NOT_CHANGE';
        return '1_NO_CLOUDFLARE_CHANGE_AND_NO_PURGE_NEEDED_(THE_VIEWS_LINK_THE_CHANGED_ADMIN_STYLESHEET_AND_THE_NEW_REGISTRATION_STYLESHEET_AS_?v=<FILE_MODIFICATION_TIME>_A_URL_THE_EDGE_HAS_NEVER_SEEN)_DO_NOT_CHANGE_ANY_CLOUDFLARE_SETTING_DURING_THE_INSTALLATION';
    }

    private function open():void
    {
        if($this->lock!==null)return;if(!is_dir($this->base)&&!mkdir($this->base,0700))throw new RuntimeException('PRIVATE_STATE_CREATE');
        if(is_link($this->base)||realpath($this->base)!==$this->base||(fileperms($this->base)&0777)!==0700||fileowner($this->base)!==posix_geteuid())throw new RuntimeException('PRIVATE_STATE_CONFINEMENT');
        $lock=fopen($this->base.'/operator.lock','c');if($lock===false||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('ANOTHER_B1155_RUN_ACTIVE');chmod($this->base.'/operator.lock',0600);$this->lock=$lock;
    }

    /** No B1146, B1147, B1148, B1149, B1150, B1151, B1152, B1153 or B1154 run holds its operator lock (read-only: each lock file is opened for reading and only probed). */
    private function earlierIdle():void
    {
        foreach(['B1146'=>$this->b1146Base,'B1147'=>$this->b1147Base,'B1148'=>$this->b1148Base,'B1149'=>$this->b1149Base,'B1150'=>$this->b1150Base,'B1151'=>$this->b1151Base,'B1152'=>$this->b1152Base,'B1153'=>$this->b1153Base,'B1154'=>$this->b1154Base] as $label=>$dir){
            $path=$dir.'/operator.lock';if(!file_exists($path))continue;if(is_link($path)||!is_file($path))throw new RuntimeException($label.'_LOCK_SCOPE');
            $h=@fopen($path,'r');if($h===false)throw new RuntimeException($label.'_LOCK_UNREADABLE');$free=flock($h,LOCK_SH|LOCK_NB);if($free)flock($h,LOCK_UN);fclose($h);if(!$free)throw new RuntimeException($label.'_RUN_ACTIVE');
        }
    }

    private function emit(string $key,mixed $value):void {echo $key.'='.json_encode($value,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;}
    private static function code(Throwable $error):string {$code=$error->getMessage();return preg_match('/^[A-Z0-9_]+$/D',$code)?$code:'UNEXPECTED_'.strtoupper((new ReflectionClass($error))->getShortName());}
    private function active():string {return $this->root.'/apps/cms/current';}
    private function target(array $step):string {return $this->active().'/'.$step['path'];}
    private function fault(string $point):void {if($this->fault!==null)($this->fault)($point);}
    private function now():int {return $this->fixedNow??time();}
    private function retained():string {$r=$this->state['chain']['retained']??null;if(!is_string($r))throw new RuntimeException('RETAINED_TREE_UNKNOWN');return $r;}
    private function step(string $path):array {foreach($this->plan as $s)if($s['path']===$path)return $s;throw new RuntimeException('PLAN_PATH_UNKNOWN');}

    private function atomicJSON(string $path,array $data):void
    {
        if(realpath(dirname($path))!==dirname($path)||is_link($path))throw new RuntimeException('STATE_CONFINEMENT');$temporary=$path.'.'.bin2hex(random_bytes(8)).'.next';$handle=@fopen($temporary,'x');if($handle===false)throw new RuntimeException('STATE_CREATE');
        chmod($temporary,0600);$text=json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);$ok=fwrite($handle,$text)===strlen($text)&&fflush($handle)&&(!function_exists('fsync')||fsync($handle));fclose($handle);
        if(!$ok){@unlink($temporary);throw new RuntimeException('STATE_WRITE');}if(!rename($temporary,$path)){@unlink($temporary);throw new RuntimeException('STATE_RENAME');}
    }

    /** state.json first, then the journal line, then the pointer; the in-memory phase changes only after the state file holds it. */
    private function save(string $phase):void
    {
        $next=$this->state;$next['phase']=$phase;$this->atomicJSON($this->state['private'].'/state.json',$next);$this->state['phase']=$phase;
        $journal=$this->state['private'].'/journal.jsonl';$h=fopen($journal,'a');if($h===false||!flock($h,LOCK_EX))throw new RuntimeException('JOURNAL_WRITE');
        $line=json_encode(['phase'=>$phase,'at'=>time()],JSON_THROW_ON_ERROR)."\n";$ok=fwrite($h,$line)===strlen($line)&&fflush($h);flock($h,LOCK_UN);fclose($h);if(!$ok)throw new RuntimeException('JOURNAL_WRITE');
        $this->atomicJSON($this->pointer,['schemaVersion'=>1,'kind'=>'B1155','private'=>$this->state['private'],'id'=>$this->state['id']]);$this->emit('B1155_PHASE',$phase);$this->fault('SAVED_'.$phase);
    }

    private function persist():void {$this->atomicJSON($this->state['private'].'/state.json',$this->state);}

    /** No raw application output leaves the process: callers report audited identity, RC and parsed facts only. */
    private function command(array $argv,?string $cwd=null,int $timeout=120,?array $env=null):array
    {
        $pipes=[];$process=proc_open($argv,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd,$env);if(!is_resource($process))throw new RuntimeException('CHILD_START');fclose($pipes[0]);stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$stdout='';$stderr='';$started=microtime(true);
        do{$stdout.=(string)stream_get_contents($pipes[1]);$stderr.=(string)stream_get_contents($pipes[2]);$last=proc_get_status($process);
            if($last['running']&&(microtime(true)-$started>$timeout||strlen($stdout)>4194304||strlen($stderr)>4194304)){foreach(MigrateUnclampCoordinator::descendants((int)$last['pid']) as $p)@posix_kill($p,9);@posix_kill((int)$last['pid'],9);$deadline=microtime(true)+20;while(proc_get_status($process)['running']&&microtime(true)<$deadline)usleep(100000);fclose($pipes[1]);fclose($pipes[2]);proc_close($process);throw new RuntimeException('CHILD_TIMEOUT_OR_OUTPUT_BOUND');}
            if($last['running'])usleep(100000);}while($last['running']);
        $stdout.=(string)stream_get_contents($pipes[1]);$stderr.=(string)stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$closed=proc_close($process);$rc=$last['exitcode']>=0?$last['exitcode']:$closed;return ['rc'=>$rc,'stdout'=>$stdout,'stderrBytes'=>strlen($stderr)];
    }

    private function artisan(array $arguments,int $timeout=300):array
    {
        // The operator entry runs with umask 077; artisan children get 022 so anything they write matches the tree (0644).
        $previous=umask(0022);try{return $this->command(array_merge([$this->php,'-d','opcache.enable=0','-d','opcache.enable_cli=0',$this->active().'/artisan'],$arguments,['--no-interaction','--no-ansi']),$this->active(),$timeout,array_merge(getenv(),['COLUMNS'=>'240']));}finally{umask($previous);}
    }

    private function artisanOk(array $arguments,int $timeout=300):void
    {
        $r=$this->artisan($arguments,$timeout);$this->emit('ARTISAN',['command'=>implode(' ',$arguments),'rc'=>$r['rc'],'stderrBytes'=>$r['stderrBytes']]);if($r['rc']!==0)throw new RuntimeException('ARTISAN_FAILED_'.strtoupper(str_replace([':','-'],'_',$arguments[0])));
    }

    /** One read-only helper (b1145 state, b1146 routes/approval, b1155 features/environment); JSON facts only. */
    private function helper(string $script,array $arguments,int $timeout=120):array
    {
        $r=$this->command(array_merge([$this->php,'-d','opcache.enable=0','-d','opcache.enable_cli=0',$script,$this->active()],$arguments),$this->active(),$timeout,array_merge(getenv(),['COLUMNS'=>'240']));
        $data=json_decode(trim($r['stdout']),true,32);if(!is_array($data))throw new RuntimeException('APP_HELPER_UNPARSED');
        if($r['rc']!==0||($data['ok']??false)!==true){$code=is_string($data['error']??null)&&preg_match('/^[A-Z0-9_]+$/D',$data['error'])?$data['error']:'APP_HELPER_FAILED';throw new RuntimeException($code);}unset($data['ok']);return $data;
    }

    private function manifest():array
    {
        if($this->manifest!==null)return $this->manifest;$path=$this->stage.'/verification/runtime-manifest.json';
        if(is_link($path)||realpath($path)!==$path||!is_file($path)||hash_file('sha256',$path)!==self::MANIFEST_SHA)throw new RuntimeException('RUNTIME_MANIFEST_INTEGRITY');
        $m=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);if(!is_array($m['files']??null)||count($m['files'])!==self::MANIFEST_FILES)throw new RuntimeException('RUNTIME_MANIFEST_COUNT');return $this->manifest=$m;
    }

    /** $files with $changes applied: a modified entry must hold its pre digest and gets the new one; a new path must be absent. */
    private static function applied(array $files,array $changes,string $code):array
    {
        $by=[];foreach($changes as $c)$by[$c['path']]=$c;$out=[];
        foreach($files as $f){$c=$by[$f['path']]??null;if($c!==null&&$c['kind']==='new')throw new RuntimeException($code.'_NEW_FILE_ALREADY_LISTED');if($c!==null){if($f['sha256']!==$c['preSHA256'])throw new RuntimeException($code.'_PRE_STATE_MISMATCH');$f['sha256']=$c['sha256'];}$out[]=$f;}
        foreach($changes as $c)if($c['kind']==='new')$out[]=['path'=>$c['path'],'sha256'=>$c['sha256']];return $out;
    }

    /** The manifest with the 21 B1146, the 8 B1147 and the 11 B1148 files (8548 entries): the tree before B1149. */
    private function b1148Manifest():array
    {
        $files=self::applied($this->manifest()['files'],array_values($this->b1146),'B1146_MANIFEST');if(count($files)!==self::B1146_TREE_FILES)throw new RuntimeException('B1146_MANIFEST_COUNT');
        $files=self::applied($files,array_values($this->b1147),'B1147_MANIFEST');if(count($files)!==self::B1147_TREE_FILES)throw new RuntimeException('B1147_MANIFEST_COUNT');
        $files=self::applied($files,array_values($this->b1148),'B1148_MANIFEST');if(count($files)!==self::B1148_TREE_FILES)throw new RuntimeException('B1148_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** The tree before B1150: the 8548-file tree with the 43 B1149 files (8564 entries). */
    private function b1149Manifest():array
    {
        $files=self::applied($this->b1148Manifest()['files'],array_values($this->b1149),'B1149_MANIFEST');if(count($files)!==self::B1149_TREE_FILES)throw new RuntimeException('B1149_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** The tree after B1150: the 8564-file tree with the 5 B1150 files (8566 entries). */
    private function b1150Manifest():array
    {
        $files=self::applied($this->b1149Manifest()['files'],array_values($this->b1150),'B1150_MANIFEST');if(count($files)!==self::B1150_TREE_FILES)throw new RuntimeException('B1150_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** The tree after B1151: the 8566-file tree with the 6 B1151 files (8567 entries, Report1117). */
    private function b1151Manifest():array
    {
        $files=self::applied($this->b1150Manifest()['files'],array_values($this->b1151),'B1151_MANIFEST');if(count($files)!==self::B1151_TREE_FILES)throw new RuntimeException('B1151_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** The tree after B1152: the 8567-file tree with the 9 B1152 files (8569 entries, Report1118). */
    private function b1152Manifest():array
    {
        $files=self::applied($this->b1151Manifest()['files'],array_values($this->b1152),'B1152_MANIFEST');if(count($files)!==self::B1152_TREE_FILES)throw new RuntimeException('B1152_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** The tree after B1153: the 8569-file tree with the 26 B1153 files (8572 entries, Report1119). */
    private function b1153Manifest():array
    {
        $files=self::applied($this->b1152Manifest()['files'],array_values($this->b1153),'B1153_MANIFEST');if(count($files)!==self::B1153_TREE_FILES)throw new RuntimeException('B1153_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** The live tree before this rollout: the 8572-file tree with the 6 B1154 files (8574 entries, Report1120). */
    private function b1154Manifest():array
    {
        $files=self::applied($this->b1153Manifest()['files'],array_values($this->b1154),'B1154_MANIFEST');if(count($files)!==self::B1154_TREE_FILES)throw new RuntimeException('B1154_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** The tree after this rollout (8574 + the new files of STEPS). */
    private function installedManifest():array
    {
        $changes=array_map(static fn($s)=>['path'=>$s['path'],'kind'=>$s['kind'],'sha256'=>$s['payloadSHA256'],'preSHA256'=>$s['preSHA256']],$this->plan);
        $files=self::applied($this->b1154Manifest()['files'],$changes,'B1155_MANIFEST');if(count($files)!==self::installedTreeFiles())throw new RuntimeException('B1155_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** Last value of every KEY=value line of the B1154 terminal report (Report1120); it must also carry the tree and route count facts of the B1154 acceptance ($routes = the count B1154 left). */
    public static function b1154Facts(string $bytes,int $routes=self::ROUTES):array
    {
        if(str_contains($bytes,"\r")||strlen($bytes)>4194304)throw new RuntimeException('B1154_REPORT_FORMAT');$last=[];
        foreach(explode("\n",$bytes) as $line)if(preg_match('/^([A-Z0-9_]+)=(.*)$/D',$line,$m))$last[$m[1]]=$m[2];
        if(($last['B1154_FINAL']??null)!==self::B1154_PASS||($last['EXIT']??null)!=='0'||json_decode($last['B1154_PHASE']??'null',true)!=='INSTALLED_PASS')throw new RuntimeException('B1154_REPORT_NOT_TERMINAL_PASS');
        if(!str_contains($bytes,'"verifiedFiles":'.self::B1154_TREE_FILES)||!str_contains($bytes,'"routeCount":'.$routes))throw new RuntimeException('B1154_REPORT_TREE_OR_ROUTE_COUNT_MISSING');
        return ['final'=>$last['B1154_FINAL'],'phase'=>'INSTALLED_PASS','exit'=>0];
    }

    /**
     * Chain: exactly one Report1120 of B1154 with its terminal PASS (the pattern carries the sequence number 1120, so any earlier report
     * of the same prefix is not even a candidate) whose bytes equal the digest this package pins, the live B1154 state with the pinned
     * id INSTALLED_PASS, and no B1146-B1154 run. A different report, state id or digest is a HOLD with nothing changed.
     */
    private function chain():array
    {
        $directory=$this->root.'/docs/operations';$found=[];foreach(scandir($directory)?:[] as $name)if(preg_match(self::B1154_REPORT_PATTERN,$name))$found[]=$name;
        if(count($found)!==1)throw new RuntimeException(count($found)===0?'B1154_REPORT1120_MISSING':'B1154_REPORT1120_AMBIGUOUS');$path=$directory.'/'.$found[0];
        if(is_link($path)||realpath($path)!==$path||!is_file($path))throw new RuntimeException('B1154_REPORT1120_SCOPE');$bytes=(string)file_get_contents($path);$facts=self::b1154Facts($bytes,$this->expectedRoutes);
        if(!hash_equals($this->b1154ReportSHA256,hash('sha256',$bytes)))throw new RuntimeException('B1154_REPORT1120_DIGEST_DIFFERS_FROM_THE_PINNED_ONE');
        $pointer=$this->b1154Base.'/current.json';if(is_link($pointer)||!is_file($pointer)||filesize($pointer)>8192)throw new RuntimeException('B1154_POINTER_MISSING');$p=json_decode((string)file_get_contents($pointer),true,8,JSON_THROW_ON_ERROR);
        if(!is_array($p)||($p['kind']??null)!=='B1154'||!preg_match('/^[a-f0-9]{32}$/D',(string)($p['id']??''))||($p['private']??null)!==$this->b1154Base.'/'.$p['id'])throw new RuntimeException('B1154_POINTER_SCOPE');
        if($p['id']!==$this->b1154StateId)throw new RuntimeException('B1154_STATE_ID_DIFFERS_FROM_THE_PINNED_ONE');
        $statePath=$p['private'].'/state.json';if(is_link($statePath)||realpath($statePath)!==$statePath||filesize($statePath)>16777216)throw new RuntimeException('B1154_STATE_MISSING');$stateBytes=(string)file_get_contents($statePath);$s=json_decode($stateBytes,true,512,JSON_THROW_ON_ERROR);
        if(!is_array($s)||($s['kind']??null)!=='B1154'||($s['id']??null)!==$p['id']||($s['phase']??null)!=='INSTALLED_PASS')throw new RuntimeException('B1154_STATE_NOT_INSTALLED_PASS');
        $retained=$s['chain']['retained']??null;$htaccess=$s['chain']['htaccessSHA256']??null;
        if(!is_string($retained)||!preg_match('~^/[A-Za-z0-9._/-]+/retained$~D',$retained)||!is_string($htaccess)||!preg_match('/^[a-f0-9]{64}$/D',$htaccess))throw new RuntimeException('B1154_STATE_FACTS');
        $this->earlierIdle();
        return ['report'=>'docs/operations/'.$found[0],'reportSHA256'=>hash('sha256',$bytes),'b1154Id'=>$p['id'],'b1154StateSHA256'=>hash('sha256',$stateBytes),'retained'=>$retained,'htaccessSHA256'=>$htaccess]+$facts;
    }

    /**
     * The retained tree and its parent directories as the SSH account sees them: mode and owner, read-only, informational. No gate
     * depends on them (the owner opened them with o+x on 12.11.2026 so /storage works and may close them again with o-x after
     * this rollout: 0750 and 0751 are both fine). Recorded so the report shows the state the run saw.
     */
    private function retainedModes():array
    {
        $out=[];$retained=$this->retained();
        foreach([dirname(dirname($retained)),dirname($retained),$retained] as $dir){clearstatcache(true,$dir);$m=@fileperms($dir);$out[]=['path'=>$dir,'mode'=>$m===false?'UNREADABLE':sprintf('%04o',$m&0777),'link'=>is_link($dir)];}
        return ['directories'=>$out,'gate'=>'NONE_INFORMATIONAL_0750_AND_0751_ARE_BOTH_ACCEPTED'];
    }

    /** Payload bytes from incoming/, re-verified against the plan on every read. */
    private function payload(array $step):string
    {
        $p=$step['payload'];if(is_link($p)||realpath($p)!==$p||!is_file($p))throw new RuntimeException('PAYLOAD_MISSING');$bytes=(string)file_get_contents($p);
        if(!hash_equals($step['payloadSHA256'],hash('sha256',$bytes))||strlen($bytes)!==$step['size'])throw new RuntimeException('PAYLOAD_INTEGRITY');return $bytes;
    }

    /** pre | payload | absent | foreign | link for one target; never writes. */
    private function classify(array $step):array
    {
        $t=$this->target($step);clearstatcache(true,$t);if(is_link($t))return ['state'=>'link','sha256'=>null];if(!file_exists($t))return ['state'=>'absent','sha256'=>null];if(!is_file($t))return ['state'=>'foreign','sha256'=>null];
        $sha=hash_file('sha256',$t);$state=$sha===$step['payloadSHA256']?'payload':($step['preSHA256']!==null&&$sha===$step['preSHA256']?'pre':'foreign');return ['state'=>$state,'sha256'=>$sha];
    }

    private function targets():array
    {
        $out=['pre'=>0,'payload'=>0,'absent'=>0,'foreign'=>[],'link'=>[]];
        foreach($this->plan as $s){$c=$this->classify($s);if(in_array($c['state'],['foreign','link'],true))$out[$c['state']][]=['n'=>$s['n'],'path'=>$s['path'],'sha256'=>$c['sha256'],'expectedPre'=>$s['preSHA256'],'payload'=>$s['payloadSHA256']];else $out[$c['state']]++;}
        $new=count(array_filter($this->plan,static fn($s)=>$s['kind']==='new'));$out['preState']=$out['foreign']===[]&&$out['link']===[]&&$out['pre']===count($this->plan)-$new&&$out['absent']===$new;
        $out['installed']=$out['foreign']===[]&&$out['link']===[]&&$out['payload']===count($this->plan);return $out;
    }

    private function routeCacheFiles():array
    {
        $files=[];foreach(glob($this->active().'/bootstrap/cache/routes-*.php')?:[] as $path){if(is_link($path)||!is_file($path))throw new RuntimeException('ROUTE_CACHE_FILE_SCOPE');$files[basename($path)]=hash_file('sha256',$path);}ksort($files,SORT_STRING);return $files;
    }

    /** B1146 to B1150 ran without a route cache and so does this rollout (no route changes, and this tool never clears or builds one before the end). */
    private function requireNoRouteCache():void {if($this->routeCacheFiles()!==[])throw new RuntimeException('ROUTE_CACHE_PRESENT_NOTHING_CHANGED');}

    private function viewsDirectory():string {$d=realpath($this->active().'/storage/framework/views');if(!is_string($d)||!is_dir($d))throw new RuntimeException('COMPILED_VIEWS_DIRECTORY');return $d;}
    private function compiledViews():int {return count(glob($this->viewsDirectory().'/*.php')?:[]);}

    /** No artisan process of this CMS: three consecutive clean samples, bounded (cron schedule:run fires every minute). */
    private function census():array
    {
        $roots=[$this->active(),$this->retained()];$deadline=hrtime(true)+$this->censusSeconds*1000000000;$clean=0;$samples=0;$seen=0;
        do{$b=LeanGates::busy($this->active().'/public/index.php',LeanGates::processes(),getmypid(),$roots);$samples++;if($b['artisan']===0){if(++$clean>=3)return ['clean'=>true,'samples'=>$samples,'busySamples'=>$seen,'otherArtisanIgnored'=>$b['otherArtisanIgnored']];}else{$clean=0;$seen++;}usleep(1000000);}while(hrtime(true)<$deadline);
        throw new RuntimeException('CMS_ARTISAN_RUNNING');
    }

    private function headroom():array
    {
        $need=$this->minFreeBytes;foreach($this->plan as $s)$need+=3*$s['size'];$result=[];
        foreach([$this->active(),$this->base] as $i=>$path){
            $r=$this->command(['stat','-f','-c','%a %S %d %c',$path],null,20);if($r['rc']!==0||!preg_match('/^(\d+) (\d+) (\d+) (\d+)$/D',trim($r['stdout']),$m))throw new RuntimeException('DISK_STAT_UNAVAILABLE');
            $free=(int)$m[1]*(int)$m[2];$result[]=['filesystem'=>$i===0?'CMS_TREE':'PRIVATE_STATE','freeBytes'=>$free,'requiredBytes'=>$need,'freeInodes'=>(int)$m[3]];if($free<$need)throw new RuntimeException('DISK_HEADROOM_INSUFFICIENT');if((int)$m[3]<1000)throw new RuntimeException('INODE_HEADROOM_INSUFFICIENT');
        }
        $result[]=['filesystem'=>'CPANEL_ACCOUNT_QUOTA']+$this->quota($need,1000);
        return $result;
    }

    /** The per-user (cPanel/CageFS) quota as `quota -w -p` shows it, parsed by the pinned B1145 parser; unreadable = filesystem only. */
    private function quota(int $bytes,int $inodes):array
    {
        $bin=null;foreach(explode(':',(string)getenv('PATH')) as $d)if($d!==''&&str_starts_with($d,'/')&&is_file($d.'/quota')&&is_executable($d.'/quota')){$bin=$d.'/quota';break;}
        if($bin===null)return ['read'=>false,'note'=>'ACCOUNT_QUOTA_NOT_READ_NO_QUOTA_TOOL_FILESYSTEM_ONLY'];
        try{$r=$this->command([$bin,'-w','-p'],null,20,array_merge(getenv(),['LC_ALL'=>'C']));}catch(Throwable $e){return ['read'=>false,'note'=>'ACCOUNT_QUOTA_NOT_READ_'.self::code($e).'_FILESYSTEM_ONLY'];}
        $rows=MigrateUnclampCoordinator::parseQuota($r['stdout']);if($rows===null)return ['read'=>false,'note'=>'ACCOUNT_QUOTA_NOT_READ_UNPARSED_RC'.$r['rc'].'_FILESYSTEM_ONLY'];$out=['read'=>true,'limited'=>false,'requiredBytes'=>$bytes,'requiredInodes'=>$inodes,'rows'=>[]];
        foreach($rows as $row){
            $freeBytes=$row['limitBytes']===null?null:$row['limitBytes']-$row['usedBytes'];$freeInodes=$row['limitInodes']===null?null:$row['limitInodes']-$row['usedInodes'];$out['limited']=$out['limited']||$freeBytes!==null||$freeInodes!==null;$out['rows'][]=['freeBytes'=>$freeBytes,'freeInodes'=>$freeInodes];
            if($freeBytes!==null&&$freeBytes<$bytes)throw new RuntimeException('ACCOUNT_QUOTA_HEADROOM_INSUFFICIENT');if($freeInodes!==null&&$freeInodes<$inodes)throw new RuntimeException('ACCOUNT_QUOTA_INODES_INSUFFICIENT');
        }
        return $out;
    }

    /** Scheduled app:backup-create (02:30 daily, 03:10 Sunday, app timezone) must not start within the window this run needs. */
    private function scheduleState(array $app):array
    {
        $last=$app['backups']['lastCompletedSeconds']??null;$base=$last===null?$this->defaultBackupSeconds:max(60,(int)$last);$duration=2*$base+600;$now=$this->now();$tz=(string)($app['appTimezone']??'');
        try{$local=(new DateTimeImmutable('@'.$now))->setTimezone(new DateTimeZone($tz))->format('Y-m-d H:i T');}catch(Throwable){throw new RuntimeException('APP_TIMEZONE_INVALID');}
        return ['conflict'=>MigrateUnclampCoordinator::scheduleConflict($app['backupSchedule']??[],$now,$this->scheduleNeedSeconds,$duration),'appTimezone'=>$tz,'appLocalTime'=>$local,'needSeconds'=>$this->scheduleNeedSeconds,'scheduledBackupSeconds'=>$duration,'events'=>$app['backupSchedule']??[]];
    }

    /** The shop as the app sees it: open = config public_storefront.enabled === true AND DB switch '1'. Read-only. */
    private static function shopOf(array $app):array
    {
        $flag=(string)$app['storefrontFlag'];$enabled=($app['storefrontConfig']['enabled']??null)===true;
        return ['dbSwitch'=>$flag,'configEnabled'=>$enabled,'known'=>in_array($flag,['absent','0','1'],true),'open'=>$enabled&&$flag==='1'];
    }

    private function shop():array {$s=self::shopOf($this->helper($this->appStateScript,['state']));if(!$s['known'])throw new RuntimeException('SHOP_SWITCH_VALUE_UNKNOWN');return $s;}

    private function migrationStatus():array
    {
        $r=$this->artisan(['migrate:status'],120);if($r['rc']!==0)throw new RuntimeException('MIGRATE_STATUS_FAILED_OR_DB_UNREACHABLE');
        $s=LeanGates::migrationStatus($r['stdout'],LeanGates::migrationFiles($this->active().'/database/migrations'));if($s['ran']!==self::RAN||$s['pending']!==[])throw new RuntimeException('MIGRATION_SET_NOT_71_RAN_0_PENDING');return $s;
    }

    /** Leftover temp files of this tool, and of the B1147, B1148, B1149, B1150 and B1151 tools (an interrupted earlier run must be looked at first). */
    private function ownTemps():array
    {
        $found=[];$dirs=[];foreach($this->plan as $s)$dirs[dirname($this->target($s))]=true;foreach(array_merge($this->b1147,$this->b1148,$this->b1149,$this->b1150,$this->b1151,$this->b1152,$this->b1153,$this->b1154) as $e)$dirs[dirname($this->active().'/'.$e['path'])]=true;
        foreach(array_keys($dirs) as $d)if(is_dir($d))foreach(scandir($d)?:[] as $name)if(DashboardReportsFiles::isOwnTemp($d.'/'.$name)||AdminShellFiles::isOwnTemp($d.'/'.$name)||LogoCatalogFiles::isOwnTemp($d.'/'.$name)||OriginPinFiles::isOwnTemp($d.'/'.$name)||LegalAccountFiles::isOwnTemp($d.'/'.$name)||MediaFiles::isOwnTemp($d.'/'.$name)||StorefrontFiles::isOwnTemp($d.'/'.$name)||CatalogFiles::isOwnTemp($d.'/'.$name)||GlassFiles::isOwnTemp($d.'/'.$name))$found[]=$d.'/'.$name;return $found;
    }

    private function cleanupRecorded():void
    {
        $changed=false;
        foreach($this->state['probes']??[] as $i=>$probe){if(($probe['removed']??false)===true)continue;FreshnessProbe::removeStaging($probe['staging']);FreshnessProbe::remove($this->active().'/public',$probe['name'],$probe['sha256']);$this->state['probes'][$i]['removed']=true;$changed=true;}
        foreach($this->state['temps']??[] as $i=>$temp){if(DashboardReportsFiles::isOwnTemp($temp)&&is_file($temp)&&!is_link($temp)&&!unlink($temp))throw new RuntimeException('TEMP_FILE_REMOVE');unset($this->state['temps'][$i]);$changed=true;}
        if($changed){$this->state['temps']=array_values($this->state['temps']??[]);$this->persist();}
    }

    private function recordTemp(string $temp):string {$this->state['temps'][]=$temp;$this->persist();return $temp;}

    /**
     * absolute path => expected sha for the probe: all targets of STEPS ('installed' or 'pre' bytes, null = absent). Only the PHP sources (and the compiled views)
     * can be held by OPcache; the probe invalidates exactly those, one file at a time (never a global reset).
     */
    private function probeTargets(bool $installed):array
    {
        $t=[];foreach($this->plan as $s){$dir=realpath(dirname($this->target($s)));$path=($dir===false?dirname($this->target($s)):$dir).'/'.basename($s['path']);$t[$path]=$installed?$s['payloadSHA256']:$s['preSHA256'];}
        return $t;
    }

    /**
     * The probe request(s) for all targets. The pinned B1146 OverlayProbe accepts at most 40 targets per probe file, so a plan
     * above 40 targets is probed in several equal partitions (a plan of up to 40 in one), each its own token-gated temporary
     * file and request, assessed collect-all and merged; every probe file is removed before the next one is written.
     */
    private function probeRun(string $label,bool $installed,bool $invalidate,bool $requireFresh,bool $collect=false):array
    {
        $this->cleanupRecorded();FreshnessProbe::assertNoLeftover($this->active().'/public');$all=$this->probeTargets($installed);$route='UNKNOWN';$merged=null;$failures=[];$gates=[];$partitions=max(1,(int)ceil(count($all)/40));
        foreach(array_chunk($all,max(1,(int)ceil(count($all)/$partitions)),true) as $part=>$targets){
            $this->cleanupRecorded();$plan=OverlayProbe::plan($this->state['id'],$targets,$this->viewsDirectory());
            $staging=$this->state['private'].'/probe-'.bin2hex(random_bytes(8)).'.staging';$this->state['probes'][]=['name'=>$plan['name'],'sha256'=>$plan['sha256'],'label'=>$label.'-part'.($part+1),'staging'=>$staging];$this->persist();
            try{FreshnessProbe::write($this->active().'/public/'.$plan['name'],$plan['source'],$staging);$response=$this->web->probe($plan['name'],$plan['token'],$invalidate,null);}finally{$this->cleanupRecorded();}
            $route=is_string($response['_route']??null)?$response['_route']:'UNKNOWN';unset($response['_route']);$a=OverlayProbe::assess($response,$this->state['id'],$targets,$this->allowedSapis,$invalidate,$requireFresh,posix_geteuid());
            foreach($a['gates'] as $g=>$c)if($c!==null)$gates[$g]=$c;else $gates[$g]??='PASS';
            $failures=array_merge($failures,$a['failures']);
            if($merged===null)$merged=$a['values'];else foreach(['targets','targetsCached','invalidated','compiledViewsInvalidated','vanishedCachedScripts','contentMismatches','staleTargets'] as $k)$merged[$k]=(int)$merged[$k]+(int)$a['values'][$k];
            if($route!=='UNKNOWN'){$this->state['probeRoute']=$route;$this->persist();}
        }
        $failures=array_values(array_unique($failures));
        $result=['label'=>$label,'route'=>$route,'invalidationRequested'=>$invalidate,'requireFresh'=>$requireFresh,'partitions'=>$partitions,'gates'=>$gates]+$merged+['probeRemoved'=>true,'failures'=>$failures];$this->emit('WEB_OVERLAY_PROBE',$result);
        if(!$collect&&$failures!==[])throw new RuntimeException($failures[0]);return $result;
    }

    /**
     * The same token-gated web probe with NO class list: only the app.url the web worker sees (read the way Laravel reads it, nothing booted, see LoadProbe) must be
     * exactly the pinned origin. Used by the PREFLIGHT (a mismatch is a HOLD before any write); the app.url is the pinned B1152 origin and no file of this payload touches it.
     */
    private function webAppUrlCheck(string $label):array
    {
        $this->cleanupRecorded();FreshnessProbe::assertNoLeftover($this->active().'/public');
        $plan=LogoCatalogLoadProbe::plan($this->state['id'],[],$this->expectedAppUrl);$staging=$this->state['private'].'/probe-'.bin2hex(random_bytes(8)).'.staging';
        $this->state['probes'][]=['name'=>$plan['name'],'sha256'=>$plan['sha256'],'label'=>$label,'staging'=>$staging];$this->persist();
        try{FreshnessProbe::write($this->active().'/public/'.$plan['name'],$plan['source'],$staging);$response=$this->web->probe($plan['name'],$plan['token'],false,null);}finally{$this->cleanupRecorded();}
        $route=is_string($response['_route']??null)?$response['_route']:'UNKNOWN';unset($response['_route']);
        $a=LogoCatalogLoadProbe::assess($response,$this->state['id'],[],$this->allowedSapis,posix_geteuid());
        $this->emit('WEB_APP_URL_CHECK',['label'=>$label,'route'=>$route,'gates'=>array_map(static fn($c)=>$c??'PASS',$a['gates'])]+array_intersect_key($a['values'],array_flip(['sapi','webEuidIsOperator','webAppUrlExact','webAppUrlOrigin','webAppUrlSource']))+['probeRemoved'=>true,'failures'=>$a['failures']]);
        if($a['failures']!==[])throw new RuntimeException($a['failures'][0]);
        return $a['values'];
    }

    /** URL path => payload sha256 of the two changed stylesheets of STEPS (derived from ADMIN_CSS_PATHS and the plan, never typed twice). */
    private function assetFiles():array
    {
        $out=[];foreach(self::ADMIN_CSS_PATHS as $url=>$path)$out[$url]=$this->step($path)['payloadSHA256'];return $out;
    }

    /** What one stylesheet answer is: status, content type (the part before the semicolon), whether the body is exactly the payload, and the verdict. Only facts, never the body. */
    private static function assetFacts(array $r,string $sha):array
    {
        $mime=strtolower(trim(explode(';',(string)$r['contentType'])[0]));$served=!$r['transport']&&$r['status']===200;$equals=$served&&hash_equals($sha,hash('sha256',$r['body']));
        return ['observed'=>$r['transport']?'TRANSPORT':$r['status'],'contentType'=>$mime==='text/css'?'text/css':'OTHER','sha256EqualsThePayload'=>$equals,'bytes'=>$r['bytes'],'cfCache'=>$r['cfCache'],'ok'=>$served&&$mime==='text/css'&&$equals];
    }

    /**
     * The proof that comes right before the first file of the guard group E (the views that link the stylesheets): BOTH changed stylesheets are asked WEB_PROOF_REQUESTS times
     * through the pinned HTTP client (the web SAPI, the edge in production), each request with a fresh random release marker, so the edge never answers from a cache and no
     * 404 can be cached under the URL the views will link (?v=<mtime>). Every answer must be 200 with a text/css content type and exactly the payload bytes. Read-only GETs.
     * The stylesheets are files the full hold already covered (the hold runs from the last tree change, which is after group C). A failure stops the install BEFORE the views.
     */
    private function webAssetProof(string $label):array
    {
        $assets=$this->assetFiles();$last=[];
        for($i=1;$i<=self::WEB_PROOF_REQUESTS;$i++){
            $rows=[];$failed=[];
            foreach($assets as $url=>$sha){$r=$this->get($url,[],bin2hex(random_bytes(8)));$f=self::assetFacts($r,$sha);$rows[]=['path'=>$url]+$f;if(!$f['ok'])$failed[]=$url;}
            $this->emit('WEB_ASSET_PROOF',['label'=>$label,'request'=>$i,'of'=>self::WEB_PROOF_REQUESTS,'rows'=>$rows,'failed'=>$failed]);
            if($failed!==[])throw new RuntimeException('GUARD_REFUSED_A_STYLESHEET_IS_NOT_SERVED_WITH_THE_PAYLOAD_BYTES');$last=$rows;
        }
        $this->state['webAssetProof']=['label'=>$label,'at'=>time(),'requests'=>self::WEB_PROOF_REQUESTS,'assets'=>array_keys($assets)];$this->persist();return $last;
    }

    /** Fully qualified class name => [realpath of its file in the active tree, payload sha256] for the proof classes (new classes plus the group B libraries; PSR-4: App\ is app/). */
    private function proofClassFiles():array
    {
        $out=[];$want=self::proofClasses();
        foreach($this->plan as $s){
            if(!str_starts_with($s['path'],'app/')||!str_ends_with($s['path'],'.php'))continue;$name='App\\'.str_replace('/','\\',substr($s['path'],4,-4));if(!in_array($name,$want,true))continue;
            $dir=realpath(dirname($this->target($s)));$out[$name]=[($dir===false?dirname($this->target($s)):$dir).'/'.basename($s['path']),$s['payloadSHA256']];
        }
        return $out;
    }

    /**
     * The proof that comes right before the first file of the guard groups D (bootstrap/app.php) and F (routes/web.php): a token-gated temporary probe served by the WEB SAPI loads every
     * proof class (the 4 new classes and the 3 modified libraries) through the application's own autoloader (vendor/autoload.php, PSR-4) and reports the file and its digest, which must be
     * the payload. WEB_PROOF_REQUESTS separate requests, ALL must pass; the web worker's own app.url (read the way Laravel reads it, nothing booted) must be exactly the pinned origin as well.
     * Read-only, nothing is called, nothing is booted. A failure stops the install BEFORE the guarded file. The files themselves are the ones the full hold already covered.
     */
    private function webClassProof(string $label,string $group,int $requests=self::WEB_PROOF_REQUESTS):array
    {
        $classes=$this->proofClassFiles();$values=[];
        if($classes===[]||count($classes)!==count(self::proofClasses())||$requests<1||$requests>self::WEB_PROOF_REQUESTS)throw new RuntimeException('WEB_CLASS_PROOF_NO_CLASSES');
        for($i=1;$i<=$requests;$i++){
            $this->cleanupRecorded();FreshnessProbe::assertNoLeftover($this->active().'/public');
            $plan=LogoCatalogLoadProbe::plan($this->state['id'],$classes,$this->expectedAppUrl);$staging=$this->state['private'].'/probe-'.bin2hex(random_bytes(8)).'.staging';
            $this->state['probes'][]=['name'=>$plan['name'],'sha256'=>$plan['sha256'],'label'=>$label.'-'.$i,'staging'=>$staging];$this->persist();
            try{FreshnessProbe::write($this->active().'/public/'.$plan['name'],$plan['source'],$staging);$response=$this->web->probe($plan['name'],$plan['token'],false,null);}finally{$this->cleanupRecorded();}
            $route=is_string($response['_route']??null)?$response['_route']:'UNKNOWN';unset($response['_route']);
            $a=LogoCatalogLoadProbe::assess($response,$this->state['id'],$classes,$this->allowedSapis,posix_geteuid());
            $this->emit('WEB_CLASS_LOAD_PROOF',['label'=>$label,'group'=>$group,'request'=>$i,'of'=>$requests,'route'=>$route,'gates'=>array_map(static fn($c)=>$c??'PASS',$a['gates'])]+$a['values']+['probeRemoved'=>true,'failures'=>$a['failures']]);
            if($a['failures']!==[])throw new RuntimeException($a['failures'][0]);
            $values=$a['values'];
        }
        if($group!==''){$this->state['webClassProof'][$group]=['label'=>$label,'at'=>time(),'requests'=>$requests,'classes'=>array_keys($classes)];$this->persist();}return $values;
    }

    /**
     * Waits until $seconds have passed since the last tree change; heartbeat every 15 s. A change made by this process is
     * timed on the monotonic clock; otherwise (a fresh process resuming a rollback or an acceptance) the wall-clock time of
     * the last change saved in the state is used, clamped to [0, $seconds]; without any saved change the full $seconds.
     */
    private function hold(string $label,int $seconds):void
    {
        if($seconds<0||$seconds>FreshnessProbe::MAX_HOLD_SECONDS)throw new RuntimeException('OVERLAY_HOLD_EXCEEDS_BOUND');
        if($this->lastChangeNs!==null){$deadline=$this->lastChangeNs+$seconds*1000000000;$clock='MONOTONIC_THIS_PROCESS';}
        else{$at=$this->state['lastTreeChangeAt']??null;$rest=is_int($at)?max(0,min($seconds,$at+$seconds-time())):$seconds;$deadline=hrtime(true)+$rest*1000000000;$clock=is_int($at)?'SAVED_WALL_CLOCK_OF_THE_LAST_CHANGE':'FULL_NO_SAVED_CHANGE';}
        $beat=hrtime(true)+15000000000;
        $this->emit('OVERLAY_HOLD_START',['label'=>$label,'seconds'=>$seconds,'remainingSeconds'=>max(0,(int)ceil(($deadline-hrtime(true))/1e9)),'clock'=>$clock]);
        $this->fault('DURING_HOLD_'.$label);
        while(($rest=$deadline-hrtime(true))>0){usleep((int)max(1000,intdiv(min($rest,max(0,$beat-hrtime(true)),1000000000),1000)));if(hrtime(true)>=$beat&&hrtime(true)<$deadline){$this->emit('OVERLAY_HOLD_HEARTBEAT',['label'=>$label,'remainingSeconds'=>(int)ceil(($deadline-hrtime(true))/1e9)]);$beat+=15000000000;}}
        $this->emit('OVERLAY_HOLD',['label'=>$label,'seconds'=>$seconds,'done'=>true]);
    }

    /** Every tree change: the monotonic time for this process and the wall-clock time in the saved state (for a fresh process). */
    private function changed():void {$this->lastChangeNs=hrtime(true);$this->state['lastTreeChangeAt']=time();$this->persist();}

    /**
     * Where a login redirect points: PINNED_ORIGIN (absolute, exactly https://cms.ald1n.com/login...), SAME_HOST_OTHER_SCHEME (the right host, plain http: what
     * the old code sends when the request reached the origin over http), RELATIVE (/login...), OTHER (anything else, an absolute URL on another host included)
     * or NONE. A Location that merely contains the text /login on a foreign host is OTHER.
     */
    public static function locationKind(string $location,string $origin):string
    {
        if($location==='')return 'NONE';
        if(preg_match('~^/login(?:[/?#].*)?$~D',$location)===1)return 'RELATIVE';
        if(preg_match('~^(https?)://([^/?#@:]+)(/login(?:[/?#].*)?)$~Di',$location,$m)!==1||strtolower($m[2])!==strtolower((string)parse_url($origin,PHP_URL_HOST)))return 'OTHER';
        return strtolower($m[1])==='https'&&strtolower((string)parse_url($origin,PHP_URL_SCHEME))==='https'?'PINNED_ORIGIN':'SAME_HOST_OTHER_SCHEME';
    }

    /**
     * Number of <form action="http(s)://..."> whose target is not on $origin (relative actions are fine). $strict (the installed state): exactly the pinned
     * origin, scheme included; otherwise (the old state) the host alone must match, because the old code follows the scheme of the request.
     */
    public static function offOriginActions(string $html,string $origin,bool $strict):int
    {
        $host=strtolower((string)parse_url($origin,PHP_URL_HOST));$n=0;
        if(preg_match_all('~<form\b[^>]*?\saction="(https?://[^"]*)"~i',$html,$m))foreach($m[1] as $a){
            $on=$strict?($a===$origin||str_starts_with($a,$origin.'/')||str_starts_with($a,$origin.'?')):(preg_match('~^https?://([^/?#@:]+)(?:[/?#]|$)~i',$a,$h)===1&&strtolower($h[1])===$host);
            if(!$on)$n++;
        }
        return $n;
    }

    /** The height attribute of the storefront header logo (the img whose class list has shop-logo): an int, NO_LOGO_IMG when the page has none, NO_HEIGHT_ATTRIBUTE when it has no numeric one. */
    public static function headerLogoHeight(string $html):int|string
    {
        if(preg_match_all('~<img\b([^>]*)>~i',$html,$m))foreach($m[1] as $attrs){
            if(preg_match('~\sclass="([^"]*)"~',$attrs,$cl)!==1||!in_array('shop-logo',preg_split('/\s+/',trim($cl[1]))?:[],true))continue;
            return preg_match('~\sheight="(\d{1,3})"~',$attrs,$h)===1?(int)$h[1]:'NO_HEIGHT_ATTRIBUTE';
        }
        return 'NO_LOGO_IMG';
    }

    /** Number of Cloudflare e-mail obfuscation leftovers in a delivered page: substring counts of EMAIL_ARTIFACTS (the address itself is never read or printed). */
    public static function emailArtifacts(string $html):int {$n=0;foreach(self::EMAIL_ARTIFACTS as $needle)$n+=substr_count($html,$needle);return $n;}

    /** The Content-Security-Policy header values with every nonce replaced by a constant (the nonce changes with each response), header order kept. */
    public static function cspNormalized(array $csp):array {return array_values(array_map(static fn($v)=>(string)preg_replace("~'nonce-[A-Za-z0-9+/=_-]+'~","'nonce-N'",(string)$v),$csp));}

    /** Whether $origin is one of the sources of the directive $name in any of the CSP header values (the union of all headers; a directive that is not there has no sources). */
    public static function cspHas(array $csp,string $name,string $origin):bool
    {
        foreach($csp as $policy)foreach(explode(';',(string)$policy) as $d){$t=preg_split('/\s+/',trim($d),-1,PREG_SPLIT_NO_EMPTY)?:[];if($t!==[]&&strtolower($t[0])===$name&&in_array($origin,array_slice($t,1),true))return true;}
        return false;
    }

    private static function throttled(string $path):bool {return in_array($path,self::LEGAL,true)||in_array($path,self::SHOP,true)||str_starts_with($path,'/katalog/');}

    /**
     * One paced request with up to 4 attempts on a transport error, 429 or an edge/gateway status. A 429 waits Retry-After
     * (6 s when absent, at most RETRY_AFTER_CAP, within the per-batch 429 budget); after one throttled GET stays 429 the
     * shared limiter counts as exhausted and the remaining throttled GETs of the batch are skipped without a request.
     */
    private function get(string $path,array $query,?string $marker,string $method='GET',bool $origin=false,array $headers=[]):array
    {
        $throttled=self::throttled($path);
        if($throttled&&$this->limitBatch['limited'])return ['status'=>429,'transport'=>false,'robots'=>[],'csp'=>[],'frame'=>[],'vary'=>[],'cache'=>[],'nosniff'=>[],'location'=>null,'cfRay'=>false,'cfCache'=>null,'contentType'=>null,'retryAfter'=>null,'body'=>'','bytes'=>0,'attempts'=>0,'rateLimited'=>true,'waitedSeconds'=>0,'skippedSharedLimiter'=>true];
        $waited=0;$limited=false;
        for($attempt=1;$attempt<=4;$attempt++){
            if($this->paceMicros>0)usleep($this->paceMicros);
            $r=$this->web->fetch($path,$query,$marker,$method,$origin,$headers);$limited=!$r['transport']&&$r['status']===429;
            if(!$r['transport']&&!$limited&&!in_array($r['status'],[502,503,504,520,521,522,523,524,525,526,527],true))break;
            if($attempt<4){
                $s=$limited?min(self::RETRY_AFTER_CAP,max(1,$r['retryAfter']??6)):2;
                if($limited&&$this->limitBatch['waited']+$s>$this->rateLimitCapSeconds)break;
                if($limited)$this->limitBatch['waited']+=$s;$waited+=$s;sleep($s);
            }
        }
        if($limited&&$throttled)$this->limitBatch['limited']=true;
        return $r+['attempts'=>min($attempt,4),'rateLimited'=>$limited,'waitedSeconds'=>$waited,'skippedSharedLimiter'=>false];
    }

    /** Worst-case wall time of one smokes() call: two batches (429 budget, pacing, 60 s margin) and the pause between. */
    private function smokeCapSeconds():int
    {
        $batch=$this->rateLimitCapSeconds+(int)ceil(self::SMOKE_GETS*$this->paceMicros/1000000)+60;return 2*$batch+$this->rateLimitPauseSeconds;
    }

    private function groupPause():int {return max(1,min(61,(int)($this->state['groupPauseSeconds']??3)));}

    /**
     * The scheduled-backup window this run needs: install (the hold before each guard group D, E and F, the installed hold, and one revalidation pause per group), a full smokes() call, and if
     * the acceptance fails the rollback (the full hold after F, E and D are undone, the hold before the new files, the restored hold, a pause per group) with its smokes; 300 s for artisan,
     * probes and helpers. Never below 30 min. Nine full holds in all (4 install, 5 rollback). The backup-window refusal of the preflight (02:30 daily, 03:10 Sunday) uses this value.
     */
    private function requiredWindowSeconds(int $holdSeconds):int
    {
        $g=count(array_unique(array_column($this->plan,'group')));$holds=count(self::GUARDS)+1+count(self::GUARDS)+2;
        return max(1800,$holds*$holdSeconds+2*$this->smokeCapSeconds()+(4*$g-2)*$this->groupPause()+(2*$g-1)*self::LIVENESS_SECONDS+self::WEB_PROOF_REQUESTS*self::WEB_PROOF_SECONDS*count(self::GUARDS)+300);
    }

    /**
     * GET /katalog?stock=x with a foreign Referer (read-only, never followed): a validation failure of the catalog filter redirects. The old code follows the Referer (back()), the code of this
     * payload (bootstrap/app.php through AppUrlOrigin::redirect) answers 302 with Location exactly the pinned origin plus /katalog. Returns the facts; 'ok' is the installed-state verdict.
     */
    private function refererRedirect(string $marker):array
    {
        if($this->paceMicros>0)usleep($this->paceMicros);
        $r=$this->web->fetch(self::CATALOG_PATH,['stock'=>'x'],$marker,'GET',false,['Referer: '.self::FOREIGN_REFERER]);
        $loc=(string)($r['location']??'');$want=$this->expectedAppUrl.self::CATALOG_PATH;
        $kind=$loc===''?'NONE':($loc===$want?'PINNED_CATALOG':(preg_match('~^https?://evil\.example(?:[/?#]|$)~i',$loc)===1?'FOREIGN_REFERER':'OTHER'));
        return ['status'=>$r['transport']?'TRANSPORT':$r['status'],'limited'=>!$r['transport']&&$r['status']===429,'locationKind'=>$kind,'ok'=>!$r['transport']&&$r['status']===302&&$loc===$want];
    }

    /**
     * Liveness-only smoke after each install group, once the web workers have revalidated it (the group pause):
     * /up and /login 200; one legal page 200 with noindex; with the shop ON /katalog 200 with noindex and the first product page 200;
     * GET /tema 405. From group C both changed stylesheets and the unchanged tokens stylesheet answer 200 text/css with their payload bytes; from group D (bootstrap/app.php) the guest dashboard
     * and the guest SMTP page redirect to the login on the pinned origin and, with the shop ON, GET /katalog?stock=x with a foreign Referer answers 302 to exactly the pinned origin plus /katalog;
     * from group F (the routes) the guest report and its CSV redirect to the login on the pinned origin (never a 5xx). No other new-feature assertion (that is the acceptance). A failure stops
     * the install, which rolls back through the normal path; a 429 is inconclusive and the install continues; a changed shop switch is not judged here.
     */
    private function liveness(string $after):void
    {
        $marker=bin2hex(random_bytes(8));$shop=$this->shop();$rows=[];$failed=[];$limited=[];
        $one=function(string $label,string $path,string $method,int $want,bool $noindex,array $query=[])use(&$rows,&$failed,&$limited,$marker):?array{
            if($this->paceMicros>0)usleep($this->paceMicros);$r=$this->web->fetch($path,$query,$marker,$method,false);
            if(!$r['transport']&&$r['status']===429){$limited[]=$label;$rows[]=['check'=>$label,'result'=>'INCONCLUSIVE_RATE_LIMITED_429'];return null;}
            $pass=!$r['transport']&&$r['status']===$want&&(!$noindex||CatalogWeb::noindex($r['robots']));
            $rows[]=['check'=>$label,'observed'=>$r['transport']?'TRANSPORT':$r['status'],'result'=>$pass?'PASS':'FAIL'];if(!$pass)$failed[]=$label;return $r;};
        $guest=function(string $label,string $guestPath)use($one,&$rows,&$failed):void{
            $r=$one($label,$guestPath,'GET',302,false);
            if($r!==null&&!in_array(self::locationKind((string)($r['location']??''),$this->expectedAppUrl),['PINNED_ORIGIN','RELATIVE'],true)){$failed[]='LOGIN_REDIRECT_LOCATION_'.$label;$rows[]=['check'=>'LOGIN_REDIRECT_LOCATION_'.$label,'observed'=>self::locationKind((string)($r['location']??''),$this->expectedAppUrl),'result'=>'FAIL'];}
        };
        $one('/up','/up','GET',200,false);$one('/login','/login','GET',200,false);$one('/pravne-informacije','/pravne-informacije','GET',200,true);
        if($shop['open']){$c=$one('/katalog','/katalog','GET',200,true);$p=$c!==null&&$c['status']===200?MediaWeb::productPath($c['body']):null;if($p!==null)$one('PRODUCT_PAGE_FIRST_CATALOG_LINK',$p,'GET',200,true);}
        $one('GET /tema',self::THEME_PATH,'GET',405,false);
        // The stylesheets stay served with the payload bytes (the changed ones from the group that carries them, the unchanged tokens stylesheet with its B1154 digest); a guest never renders the admin layout,
        // so the guest pages are redirects, not shell checks. A failure rolls the group back first; a 429 stays inconclusive.
        if($after>='C'){
            foreach($this->assetFiles()+[self::TOKENS_CSS_PATH=>self::TOKENS_CSS_SHA256] as $url=>$sha){$r=$one('STYLESHEET_'.$url,$url,'GET',200,false);if($r!==null&&$r['status']===200&&!self::assetFacts($r,$sha)['ok']){$failed[]='STYLESHEET_BYTES_'.$url;$rows[]=['check'=>'STYLESHEET_BYTES_'.$url,'result'=>'FAIL']+self::assetFacts($r,$sha);}}
        }
        if($after>='D'){
            $guest('GUEST_DASHBOARD','/');$guest('ORIGIN_PIN_'.self::SMTP_PATH,self::SMTP_PATH);
            if($shop['open']){
                $x=$this->refererRedirect($marker);
                if($x['limited']){$limited[]='FOREIGN_REFERER_REDIRECT';$rows[]=['check'=>'FOREIGN_REFERER_REDIRECT','result'=>'INCONCLUSIVE_RATE_LIMITED_429'];}
                else{$rows[]=['check'=>'FOREIGN_REFERER_REDIRECT','expected'=>'302_TO_THE_PINNED_ORIGIN_CATALOG','observed'=>$x['status'],'locationKind'=>$x['locationKind'],'result'=>$x['ok']?'PASS':'FAIL'];if(!$x['ok'])$failed[]='FOREIGN_REFERER_REDIRECT';}
            }
        }
        if($after>='F'){$guest('GUEST_'.self::SUBAGENTS_PATH,self::SUBAGENTS_PATH);$guest('GUEST_'.self::SUBAGENTS_CSV_PATH,self::SUBAGENTS_CSV_PATH);}
        $judged=$failed;if($failed!==[]){try{if($this->shop()!==$shop)$judged=[];}catch(Throwable){$judged=[];}}
        $this->emit('GROUP_LIVENESS_SMOKE',['after'=>$after,'shop'=>$shop,'rows'=>$rows,'failed'=>$judged,'rateLimited'=>$limited,'assertions'=>'LIVENESS_ONLY_NO_NEW_FEATURE']);
        if($judged!==[])throw new RuntimeException('GROUP_SMOKE_FAILED_AFTER_'.$after);
    }

    /**
     * Public smokes; no product, cart, account, theme or setting is written, nobody logs in (GET and one HEAD only). The expectations follow the shop state read right before
     * this batch (never changed by this tool) and $installed (the B1155 payload) or not (old = the live B1154 tree, as Report1120 left it). Everything B1149 to B1154 delivered
     * is asserted in BOTH states (the per-page form, the view switch, the storefront stylesheet rules, the logo height, the media route, the guest redirects, the noindex policy, the unchanged tokens
     * stylesheet with its B1154 digest).
     * New in this rollout, the only rows that differ between the states:
     *   the two changed stylesheets /assets/css/ald1n-admin.css and /assets/css/ald1n-public-register.css (asked only with a random release marker, never under the ?v= URL the views link):
     *   installed 200, text/css and sha256 equal to the payload; old: the admin one has its pre digest (the targets gate) and the register one is absent on disk and not served 200 nor 5xx;
     *   the guest dashboard / and the guest settings pages 302 to /login on the pinned origin in both states (a guest never renders the admin layout), and the guest sub-agent report and its CSV:
     *   installed 302 to the login on the pinned origin (never a 5xx); old: not a 5xx (the route does not exist, 404);
     *   GET /katalog?stock=x with Referer https://evil.example/x: installed 302 with Location exactly the pinned origin plus /katalog, old state INFO (what the old redirect does is recorded);
     *   /registracija: Turnstile sources still in script, frame and connect when the preflight baseline had them and, in the installed state, https://www.youtube-nocookie.com in frame-src only
     *   when the login background settings hold a YouTube mode with a valid id (a boolean from the CLI helper); the catalog CSP (nonce normalized) equals the preflight baseline.
     * The AUTHENTICATED admin shell is NOT checked here: this tool never logs in. The shop, e-mail and storefront rows are the B1153 rows.
     * Scripts are counted as the origin wrote them: production HTML passes the Cloudflare edge, which injects its e-mail decoder
     * and JavaScript-detections loader into every page (MediaWeb::scripts); those are reported, never counted as app scripts.
     * Owner policy: the shop is never indexed. The server-wide public/.htaccess sets X-Robots-Tag noindex on every response;
     * every public shop and legal page and every image response must carry it, else NOINDEX_POLICY_HEADER_MISSING (an edge/server
     * fact: preflight HOLDs, an installed acceptance HOLDs with the exact files kept, a rollback completes with an owner notice).
     * shop ON: /katalog 200 with the nonce CSP shape, layout marker, gallery stylesheet link, one parsable CollectionPage JSON-LD block, Cache-Control private and no-store,
     * Vary: Cookie (if the edge hides the header the origin is asked once over loopback TLS), the sort, page and per_page queries, /korpa, /registracija, /porucivanje, one product page,
     * the /site-assets/ logo, the public image route and the signed media route (B1150, a signed URL from the CLI hook media-sample, no login). shop OFF: the 4 shop paths 404.
     */
    private function smokes(bool $installed,bool $retried=false):array
    {
        $state=$installed?'INSTALLED':'OLD';$this->cspSeen=[];$this->fault('BEFORE_SMOKES_'.$state);$shop=$this->shop();$m=$this->markers;$marker=bin2hex(random_bytes(8));$rows=[];$failed=[];$inconclusive=[];$limited=[];$this->limitBatch=['limited'=>false,'waited'=>0];
        $noindexMissing=[];$notices=[];$unverifiedVary=false;
        $add=function(string $label,array $r,string $expected,bool $pass,array $facts=[],bool $policy=false)use(&$rows,&$failed,&$limited,&$noindexMissing):void{
            if($policy)$expected.='_NOINDEX';
            if(($r['rateLimited']??false)===true){$rows[]=['check'=>$label,'expected'=>$expected,'observed'=>429,'result'=>'INCONCLUSIVE_RATE_LIMITED_429','attempts'=>$r['attempts'],'waitedSeconds'=>$r['waitedSeconds'],'skippedSharedLimiter'=>$r['skippedSharedLimiter']??false];$limited[]=$label;return;}
            $noindex=CatalogWeb::noindex($r['robots']);$facts=['noindex'=>$noindex]+$facts;$result=$pass?'PASS':'FAIL';
            if($pass&&$policy&&!$noindex){$result='FAIL_NOINDEX_POLICY_HEADER_MISSING';$noindexMissing[]=$label;}
            $rows[]=['check'=>$label,'expected'=>$expected,'observed'=>$r['transport']?'TRANSPORT':$r['status'],'result'=>$result,'attempts'=>$r['attempts']]+$facts;if(!$pass)$failed[]=$label;};
        $ok=static fn(array $r,int $status):bool=>!$r['transport']&&$r['status']===$status;
        $isImage=static fn(array $r):bool=>str_starts_with(strtolower((string)$r['contentType']),'image/');
        $shape=static fn(array $r):bool=>MediaWeb::nonceScript($r['csp'],$r['body']);
        foreach(self::SYSTEM as $p){
            $r=$this->get($p,[],$marker);$pass=$ok($r,200);$f=[];
            // The guest login form: an absolute form action must be on the pinned origin (the page is the same in both states; the pin only matters when the Host differs).
            if($p==='/login'&&$pass){$f['absoluteFormActionsOffOrigin']=self::offOriginActions($r['body'],$this->expectedAppUrl,true);$pass=$f['absoluteFormActionsOffOrigin']===0;}
            $add($p,$r,'200',$pass,$f);
        }
        // The merchant e-mail on the legal pages: how many Cloudflare e-mail obfuscation leftovers the delivered HTML carries (a count, never the address
        // and never a data-cfemail value, which is a reversible encoding of it). Data dependent (only meaningful when the merchant e-mail is shown and the
        // edge obfuscation is on), so it is a fact on the row and an INFO row, never a verdict.
        $emailBy=[];
        foreach(self::LEGAL as $p){
            $r=$this->get($p,[],$marker);$art=self::emailArtifacts($r['body']);
            $f=['cspShape'=>$shape($r),'frameDeny'=>CatalogWeb::frameDeny($r['frame']),'layoutMarker'=>str_contains($r['body'],$m['layout']),'originScripts'=>MediaWeb::scriptCount($r['body']),'edgeInjectedScripts'=>MediaWeb::edgeScriptCount($r['body']),'emailObfuscationArtifacts'=>$art];
            if(!$r['transport']&&$r['status']===200)$emailBy[$p]=$art;
            $add($p,$r,'200_CSP_DENY_LAYOUT_NONCE_SCRIPT',$ok($r,200)&&$f['cspShape']&&$f['frameDeny']&&$f['layoutMarker'],$f,true);
        }
        $emailNow=['checked'=>count($emailBy),'pagesWithArtifacts'=>count(array_filter($emailBy)),'artifacts'=>array_sum($emailBy),'traderPageArtifacts'=>$emailBy[self::TRADER_PATH]??null];$this->emailFacts=$emailNow;
        // No legal text, layout or view is in this payload: the obfuscation count of the legal pages must be what it was before the install (a fact, INFO only, never a verdict).
        $emailBase=is_array($this->state['emailBaseline']??null)?$this->state['emailBaseline']:null;
        if(!$installed)$emailVerdict='INFO_OLD_STATE_COUNT_RECORDED_NOT_JUDGED';
        elseif($emailNow['checked']<count(self::LEGAL)||$emailBase===null||($emailBase['checked']??0)<count(self::LEGAL))$emailVerdict='INFO_INCOMPLETE_NOTHING_TO_COMPARE';
        elseif($emailNow['artifacts']===$emailBase['artifacts']&&$emailNow['pagesWithArtifacts']===$emailBase['pagesWithArtifacts'])$emailVerdict='INFO_SAME_AS_THE_OLD_STATE';
        else $emailVerdict='INFO_DIFFERS_FROM_THE_OLD_STATE_NOT_JUDGED_(EDGE_FACT_THE_PAYLOAD_TOUCHES_NO_LAYOUT_OR_LEGAL_TEXT)';
        $rows[]=['check'=>'LEGAL_EMAIL_STATE','result'=>$emailVerdict,'now'=>$emailNow,'oldState'=>$emailBase];
        $this->fault('DURING_SMOKES_'.$state);
        $css=function(string $path,array $query,?string $mk,string $label,string $needle)use(&$rows,&$inconclusive,$add):void{
            $r=$this->get($path,$query,$mk);$has=str_contains($r['body'],$needle);$okay=!$r['transport']&&$r['status']===200;
            if($okay&&!$has&&CatalogWeb::edgeCached($r['cfCache'])){$inconclusive[]=$label;$rows[]=['check'=>$label,'expected'=>'200_CSS','observed'=>$r['status'],'result'=>'INCONCLUSIVE_EDGE_CACHED_COPY','cfCache'=>$r['cfCache'],'cssMarker'=>$has,'attempts'=>$r['attempts']];}
            else $add($label,$r,'200_CSS',$okay&&$has,['cfCache'=>$r['cfCache'],'cssMarker'=>$has]);
        };
        // The storefront stylesheet is not in this payload: the B1153 URL (?v=cssVersion) and its view switch rules are asserted in BOTH states.
        $cssVersion=$this->cssVersion;
        $css(self::CSS_PATH,[],$marker,self::CSS_PATH,$m['css']);
        $css(self::CSS_PATH,['v'=>$cssVersion],null,self::CSS_PATH.'?v='.$cssVersion,$m['css']);
        $css(self::CSS_PATH,['v'=>$cssVersion],null,self::CSS_PATH.'?v='.$cssVersion.' (view switch rules)','.shop-view-group');
        $css(self::GALLERY_CSS_PATH,[],$marker,self::GALLERY_CSS_PATH,$m['gallery']);
        $css(self::GALLERY_CSS_PATH,['v'=>$this->galleryVersion],null,self::GALLERY_CSS_PATH.'?v='.$this->galleryVersion,$m['gallery']);
        // The theme route exists since B1148 and accepts POST only: GET and HEAD are answered 405 by the router in both states.
        foreach(['GET','HEAD'] as $method){$r=$this->get(self::THEME_PATH,[],$marker,$method);$add($method.' '.self::THEME_PATH,$r,'405_NEVER_5XX',$ok($r,405));}
        // The admin dashboard / (the page that renders the dashboard once logged in), the SMTP admin page (B1149), the merchant settings page (B1151) and the document settings page for a guest:
        // never logged in, no form submitted; the auth middleware redirects to the login page in both states (so the admin layout is never rendered here, a 5xx would be a defect of the
        // layout chain), and the Location is on the pinned origin (an absolute URL on any other host fails; a relative /login is accepted and recorded as such). The password endpoints are not requested at all.
        foreach(['/',self::SMTP_PATH,self::MERCHANT_PATH,self::DOCUMENTS_PATH] as $guestPath){
            $r=$this->get($guestPath,[],$marker);$loc=(string)($r['location']??'');$kind=self::locationKind($loc,$this->expectedAppUrl);$good=in_array($kind,['PINNED_ORIGIN','RELATIVE'],true);
            $f=['location'=>$kind==='NONE'?null:($good?'LOGIN':'OTHER'),'locationOrigin'=>$kind];
            $add($guestPath,$r,'302_TO_LOGIN_ON_THE_PINNED_ORIGIN_GUEST',$ok($r,302)&&$good,$f);
        }
        // The sub-agent report and its CSV (GET, no login): installed the route exists and the permission middleware sends a guest to the login on the pinned origin (never a 5xx);
        // old: no route (404), never a 5xx either.
        foreach([self::SUBAGENTS_PATH,self::SUBAGENTS_CSV_PATH] as $guestPath){
            $r=$this->get($guestPath,[],$marker);$loc=(string)($r['location']??'');$kind=self::locationKind($loc,$this->expectedAppUrl);$good=in_array($kind,['PINNED_ORIGIN','RELATIVE'],true);
            $f=['location'=>$kind==='NONE'?null:($good?'LOGIN':'OTHER'),'locationOrigin'=>$kind];
            if($installed)$add($guestPath,$r,'302_TO_LOGIN_ON_THE_PINNED_ORIGIN_GUEST_NEVER_5XX',$ok($r,302)&&$good,$f);
            else $add($guestPath,$r,'NOT_A_5XX_OLD_STATE_HAS_NO_ROUTE',!$r['transport']&&$r['status']<500,$f);
        }
        // The two changed stylesheets: installed = 200, text/css and the payload bytes (sha256); old = the admin stylesheet still holds its pre-state bytes and is served with them, the registration
        // stylesheet is not on disk (this is the targets gate, re-read here) and the HTTP answer is neither 200 nor a 5xx. Always asked with the random release marker, never under the ?v=<mtime> URL the views
        // link, so no answer (a 404 before the install included) is cached under that URL.
        foreach($this->assetFiles() as $url=>$sha){
            $step=$this->step(self::ADMIN_CSS_PATHS[$url]);$r=$this->get($url,[],$marker);$f=self::assetFacts($r,$sha);$onDisk=is_file($this->active().'/'.self::ADMIN_CSS_PATHS[$url]);
            if($installed)$add('ADMIN_STYLESHEET '.$url,$r,'200_TEXT_CSS_SHA256_EQUALS_THE_PAYLOAD',$f['ok']&&$onDisk,$f);
            elseif($step['kind']==='modified'){$fp=self::assetFacts($r,(string)$step['preSHA256']);$add('ADMIN_STYLESHEET '.$url,$r,'200_TEXT_CSS_SHA256_EQUALS_THE_PRE_STATE_BYTES',$fp['ok']&&$onDisk,$fp);}
            else $add('ADMIN_STYLESHEET '.$url,$r,'ABSENT_ON_DISK_AND_NOT_SERVED',!$onDisk&&!$r['transport']&&$r['status']!==200&&$r['status']<500,$f+['onDisk'=>$onDisk]);
        }
        // The tokens stylesheet is not in this payload: its B1154 digest holds in both states.
        $r=$this->get(self::TOKENS_CSS_PATH,[],$marker);$f=self::assetFacts($r,self::TOKENS_CSS_SHA256);$add('TOKENS_STYLESHEET '.self::TOKENS_CSS_PATH,$r,'200_TEXT_CSS_SHA256_EQUALS_THE_B1154_PAYLOAD',$f['ok'],$f);
        $product=null;$logo=null;$publicImage=null;
        if($shop['open']){
            $r=$this->get('/katalog',[],$marker);$vary=MediaWeb::varyCookie($r['vary']);
            $storage=MediaWeb::storageImageCount($r['body']);$links=MediaWeb::mediaLinkCount($r['body']);
            $f=['cspShape'=>$shape($r),'originScripts'=>MediaWeb::scriptCount($r['body']),'edgeInjectedScripts'=>MediaWeb::edgeScriptCount($r['body']),'layoutMarker'=>str_contains($r['body'],$m['layout']),'galleryLink'=>str_contains($r['body'],$m['catalog']),'cacheControlPrivateNoStore'=>MediaWeb::privateNoStore($r['cache']),'jsonLd'=>CatalogWeb::jsonLdProblem($r['body'])??'PASS','varyCookie'=>$vary,'storageImageLinks'=>$storage,'mediaRouteLinks'=>$links,
                'perPageControl'=>str_contains($r['body'],self::PER_PAGE_MARKER),'viewSwitch'=>str_contains($r['body'],self::VIEW_SWITCH_MARKER),'headerLogoHeight'=>self::headerLogoHeight($r['body'])];
            // The per-page form and the grid/list switch are B1153 features, live in BOTH states of this rollout (a page that shows no product has neither, which is why the fixture has products).
            $pass=$ok($r,200)&&$f['cspShape']&&$f['layoutMarker']&&$f['galleryLink']&&$f['cacheControlPrivateNoStore']&&$f['jsonLd']==='PASS'&&$f['perPageControl']===true&&$f['viewSwitch']===true;
            // The storefront header logo (a data-dependent fact: only when a logo is set): its img carries a numeric height attribute, within the 24..80 px range of the setting.
            if($f['headerLogoHeight']==='NO_LOGO_IMG')$rows[]=['check'=>'SHOP_HEADER_LOGO_HEIGHT','result'=>'INFO_NO_HEADER_LOGO_IMG_ON_THE_CATALOG_PAGE_NOT_CHECKED_(THE_HEADER_SHOWS_A_LOGO_ONLY_WHEN_ONE_IS_SET)'];
            else{$hl=$f['headerLogoHeight']!=='NO_HEIGHT_ATTRIBUTE'&&$f['headerLogoHeight']>=24&&$f['headerLogoHeight']<=80;$rows[]=['check'=>'SHOP_HEADER_LOGO_HEIGHT','expected'=>'IMG_WITH_A_HEIGHT_ATTRIBUTE_24_TO_80','result'=>$hl?'PASS':'FAIL','heightAttribute'=>$f['headerLogoHeight']];if(!$hl)$failed[]='SHOP_HEADER_LOGO_HEIGHT';}
            if($pass&&!$vary){
                // The edge may rewrite Vary: the origin itself is asked once over loopback TLS (the B1144 probe route).
                if(($this->state['probeRoute']??null)==='LOOPBACK_TLS_ORIGIN'){$o=$this->get('/katalog',[],$marker,'GET',true);$f['originVaryCookie']=!$o['transport']&&$o['status']===200&&MediaWeb::varyCookie($o['vary']);$f['originObserved']=$o['transport']?'TRANSPORT':$o['status'];
                    if($f['originVaryCookie'])$notices[]='VARY_COOKIE_SENT_BY_THE_ORIGIN_BUT_NOT_VISIBLE_THROUGH_THE_EDGE';elseif(!$o['transport']&&$o['status']===200)$pass=false;else $unverifiedVary=true;}
                else $unverifiedVary=true;
            }
            $add('/katalog',$r,'200_CSP_JSON_LD_LAYOUT_PRIVATE_NO_STORE_VARY_COOKIE_GALLERY_IMAGE_LINK_FACTS_ONLY',$pass,$f,true);
            if($ok($r,200)){$product=MediaWeb::productPath($r['body']);$logo=MediaWeb::siteAssetUrl($r['body']);$publicImage=MediaWeb::publicImagePath($r['body']);
                // The catalog CSP (nonce normalized) is what the preflight recorded: the payload touches the registration page policy only. Old state: recorded, not judged.
                $cspNow=self::cspNormalized($r['csp']);$this->cspSeen['catalog']=$cspNow;$cspBase=$this->state['cspBaseline']['catalog']??null;
                if($installed){$same=$cspBase!==null&&$cspNow===$cspBase;$rows[]=['check'=>'CATALOG_CSP_UNCHANGED','expected'=>'EQUALS_THE_PREFLIGHT_BASELINE_NONCE_NORMALIZED','result'=>$same?'PASS':'FAIL','baselineRecorded'=>$cspBase!==null];if(!$same)$failed[]='CATALOG_CSP_UNCHANGED';}
                else $rows[]=['check'=>'CATALOG_CSP_BASELINE','result'=>'INFO_OLD_STATE_RECORDED_NOT_JUDGED','headers'=>count($cspNow),'equalsRecordedBaseline'=>$cspBase===null?null:$cspNow===$cspBase];
            }
            $r=$this->get('/katalog',['sort'=>'price_asc'],$marker);$add('/katalog?sort=price_asc',$r,'200',$ok($r,200),[],true);
            $r=$this->get('/katalog',['sort'=>'ald1n_unknown'],$marker);$add('/katalog?sort=ald1n_unknown',$r,'302_OR_422',!$r['transport']&&in_array($r['status'],[302,422],true),[],true);
            // The B1153 query values (the code whitelists them, live in both states of this rollout). per_page=999 is not a member of 20/40/80 and falls back to 40.
            $r=$this->get('/katalog',['per_page'=>'20','view'=>'list'],$marker);$f=['listLayout'=>str_contains($r['body'],self::LIST_MARKER),'selected20'=>str_contains($r['body'],'<option value="20" selected')];
            $add('/katalog?per_page=20&view=list',$r,'200_LIST_LAYOUT_20_SELECTED',$ok($r,200)&&$f['listLayout']&&$f['selected20'],$f,true);
            $r=$this->get('/katalog',['per_page'=>'999'],$marker);$f=['selected40'=>str_contains($r['body'],'<option value="40" selected'),'option999'=>str_contains($r['body'],'value="999"')];
            $add('/katalog?per_page=999',$r,'200_FALLS_BACK_TO_40',$ok($r,200)&&$f['selected40']&&!$f['option999'],$f,true);
            $r=$this->get('/katalog',['page'=>'999'],$marker);$add('/katalog?page=999',$r,'404',$ok($r,404),[],true);
            $r=$this->get('/korpa',[],$marker);$f=['layoutMarker'=>str_contains($r['body'],$m['layout']),'cspShape'=>$shape($r)];$add('/korpa',$r,'200_CSP_LAYOUT',$ok($r,200)&&$f['layoutMarker']&&$f['cspShape'],$f,true);
            $r=$this->get(self::REGISTER_PATH,[],$marker);$add(self::REGISTER_PATH,$r,'200',$ok($r,200),[],true);
            if($ok($r,200)&&($r['rateLimited']??false)!==true){
                // The registration page CSP: the Turnstile sources stay where the old CSP had them (script, frame and connect), and https://www.youtube-nocookie.com is in frame-src exactly when the login background
                // settings hold a YouTube mode with a valid 11-character id (a boolean the CLI helper reads; the id and the mode are never printed). Old state: recorded, not judged.
                $ts=['script'=>self::cspHas($r['csp'],'script-src',self::TURNSTILE_ORIGIN),'frame'=>self::cspHas($r['csp'],'frame-src',self::TURNSTILE_ORIGIN),'connect'=>self::cspHas($r['csp'],'connect-src',self::TURNSTILE_ORIGIN)];
                $yt=self::cspHas($r['csp'],'frame-src',self::YOUTUBE_FRAME_ORIGIN);$this->cspSeen['register']=self::cspNormalized($r['csp']);$this->cspSeen['registerTurnstile']=$ts;$this->cspSeen['registerYoutube']=$yt;
                $baseTs=$this->state['cspBaseline']['registerTurnstile']??null;
                if($installed){
                    // The expectation comes from the SAME response: the page embeds the background iframe exactly when frame-src must carry the nocookie host. The CLI value is informational only
                    // (the CLI and the web workers may read different setting caches; that must never roll a good install back).
                    $valid=str_contains($r['body'],'youtube-nocookie.com');$cli=null;try{$cli=$this->loginBackgroundFacts()['youtubeModeValidId'];}catch(Throwable){$cli=null;}
                    $tsOk=true;foreach($ts as $k=>$has)if(!$has&&($baseTs===null||($baseTs[$k]??true)===true))$tsOk=false;
                    $rows[]=['check'=>'REGISTER_CSP','expected'=>'TURNSTILE_SOURCES_AS_BEFORE_YOUTUBE_NOCOOKIE_FRAME_ONLY_WHEN_THE_SAME_PAGE_EMBEDS_THE_BACKGROUND_IFRAME','result'=>$tsOk&&$yt===$valid?'PASS':'FAIL','turnstile'=>$ts,'turnstileBaseline'=>$baseTs,'youtubeNocookieInFrameSrc'=>$yt,'pageEmbedsYoutubeIframe'=>$valid,'cliYoutubeModeValidIdInfo'=>$cli];
                    if(!$tsOk||$yt!==$valid)$failed[]='REGISTER_CSP';
                }else $rows[]=['check'=>'REGISTER_CSP_BASELINE','result'=>'INFO_OLD_STATE_RECORDED_NOT_JUDGED','turnstile'=>$ts,'youtubeNocookieInFrameSrc'=>$yt];
            }
            // GET /katalog?stock=x with a foreign Referer (never followed): the payload pins the validation redirect to the origin. Old state: INFO (what the old redirect does is recorded).
            $x=$this->refererRedirect($marker);
            if($x['limited']){$limited[]='FOREIGN_REFERER_REDIRECT';$rows[]=['check'=>'FOREIGN_REFERER_REDIRECT','result'=>'INCONCLUSIVE_RATE_LIMITED_429'];}
            elseif($installed){$rows[]=['check'=>'FOREIGN_REFERER_REDIRECT','expected'=>'302_LOCATION_EXACTLY_THE_PINNED_ORIGIN_PLUS_/katalog','observed'=>$x['status'],'locationKind'=>$x['locationKind'],'result'=>$x['ok']?'PASS':'FAIL'];if(!$x['ok'])$failed[]='FOREIGN_REFERER_REDIRECT';}
            else $rows[]=['check'=>'FOREIGN_REFERER_REDIRECT','result'=>'INFO_OLD_STATE_RECORDED_NOT_JUDGED','observed'=>$x['status'],'locationKind'=>$x['locationKind']];
            $r=$this->get('/porucivanje',[],$marker);$add('/porucivanje',$r,'302_OR_404',!$r['transport']&&in_array($r['status'],[302,404],true));
            if($product!==null){$r=$this->get($product,[],$marker);$f=['cspShape'=>$shape($r),'layoutMarker'=>str_contains($r['body'],$m['layout']),'galleryLink'=>str_contains($r['body'],$m['catalog']),'cacheControlPrivateNoStore'=>MediaWeb::privateNoStore($r['cache']),'storageImageLinks'=>MediaWeb::storageImageCount($r['body']),'mediaRouteLinks'=>MediaWeb::mediaLinkCount($r['body'])];
                $add('PRODUCT_PAGE_FIRST_CATALOG_LINK',$r,'200_CSP_LAYOUT_PRIVATE_NO_STORE_GALLERY',$ok($r,200)&&$f['cspShape']&&$f['layoutMarker']&&$f['galleryLink']&&$f['cacheControlPrivateNoStore'],$f,true);
                if($publicImage===null)$publicImage=MediaWeb::publicImagePath($r['body']);}
            else $rows[]=['check'=>'PRODUCT_PAGE_FIRST_CATALOG_LINK','result'=>'INFO_NO_PRODUCT_LINK_ON_THE_CATALOG_PAGE_NOT_CHECKED'];
        }else foreach(self::SHOP as $p){$r=$this->get($p,[],$marker);$add($p,$r,'404_SHOP_OFF',$ok($r,404),[],$p!=='/porucivanje');}
        if($shop['open']){
            if($logo!==null){
                // The very URL the catalog links (with its ?v= when it has one; otherwise with the release marker).
                $r=$this->get($logo['path'],$logo['query'],$logo['query']===[]?$marker:null);$okay=$ok($r,200)&&MediaWeb::nosniff($r['nosniff'])&&MediaWeb::sandboxCsp($r['csp'])&&$isImage($r);
                $f=['nosniff'=>MediaWeb::nosniff($r['nosniff']),'sandboxCsp'=>MediaWeb::sandboxCsp($r['csp']),'contentTypeImage'=>$isImage($r),'cfCache'=>$r['cfCache']];
                if($ok($r,200)&&!$okay&&CatalogWeb::edgeCached($r['cfCache'])){$inconclusive[]='SITE_ASSET_LOGO';$this->state['assetsEdgeStale']=true;$rows[]=['check'=>'SITE_ASSET_LOGO','expected'=>'200_NOSNIFF_SANDBOX_CSP','observed'=>200,'result'=>'INCONCLUSIVE_EDGE_CACHED_COPY']+$f;}
                else $add('SITE_ASSET_LOGO',$r,'200_NOSNIFF_SANDBOX_CSP_IMAGE',$okay,$f,true);
            }else $rows[]=['check'=>'SITE_ASSET_LOGO','result'=>'INFO_NO_/site-assets/_LOGO_LINK_ON_THE_CATALOG_PAGE_NOT_CHECKED_(THE_HEADER_SHOWS_A_LOGO_ONLY_WHEN_ONE_IS_SET)'];
            // The public image route of the catalog stays what it is in both states.
            if($publicImage!==null){$r=$this->get($publicImage,[],$marker);$add('PUBLIC_IMAGE_ROUTE',$r,'200_IMAGE',$ok($r,200)&&$isImage($r),['contentTypeImage'=>$isImage($r)],true);}
            else $rows[]=['check'=>'PUBLIC_IMAGE_ROUTE','result'=>'INFO_NO_/katalog/slika/_LINK_ON_THE_CATALOG_OR_PRODUCT_PAGE_NOT_CHECKED'];
        }
        if($shop['open']){
            // B1150 stays: the signed media route is live before and after this rollout, so it is exercised in BOTH states. It, the product image
            // accessor and the route count prove that nothing of B1150 was disturbed. Exercised with a real signed URL and no login. The public storefront does not link /media/p/
            // URLs (it uses /katalog/slika/...), so the CLI hook media-sample of the b1155 helper is the ONLY source: it builds the
            // URL through the ProductImage accessor for the first image that has a file, and counts accessors that returned null or
            // a foreign URL (the silent-null case: the model swallows a route() exception). No URL while images exist, a silent null,
            // or an unavailable hook is a FAILURE; only a proven image count of 0 is INFO.
            $media=null;$original=null;
            $signed=static fn(mixed $s):?array=>is_array($s)&&is_string($s['path']??null)&&is_array($s['query']??null)&&is_string($s['query']['v']??null)&&is_string($s['query']['s']??null)&&preg_match(MediaWeb::MEDIA_PATH_PATTERN,$s['path'])===1&&preg_match(MediaWeb::VERSION_PATTERN,$s['query']['v'])===1&&preg_match(MediaWeb::SIGNATURE_PATTERN,$s['query']['s'])===1?['path'=>$s['path'],'query'=>['v'=>$s['query']['v'],'s'=>$s['query']['s']]]:null;
            try{
                $h=$this->helper($this->featureScript,['media-sample']);$n=$h['imageCount']??null;$nulls=$h['urlNull']??null;
                $rows[]=['check'=>'PRODUCT_IMAGE_URL_ACCESSOR','result'=>is_int($nulls)&&$nulls===0&&is_int($n)?'PASS':'FAIL','imagesChecked'=>$n,'urlNullOrForeign'=>$nulls,'expectedPrefix'=>self::MEDIA_URL_PREFIX];
                if(!is_int($nulls)||$nulls!==0||!is_int($n))$failed[]='PRODUCT_IMAGE_URL_ACCESSOR';
                // The body is fetched on the thumbnail URL (small, or the original when no WebP rendition exists: the body limit of a media GET is 12 MiB,
                // above the 10 MiB upload limit); the original URL is only checked with HEAD, headers without a body.
                $original=$signed($h['sample']??null);$media=$signed($h['thumbnail']??null);
                if($original!==null&&$media!==null){}
                elseif($n===0)$rows[]=['check'=>'MEDIA_ROUTE_IMAGE','result'=>'INFO_NO_PRODUCT_IMAGE_WITH_A_FILE_NOTHING_TO_SIGN_NOT_CHECKED','productImages'=>0];
                else{$media=$original=null;$failed[]='MEDIA_ROUTE_IMAGE';$rows[]=['check'=>'MEDIA_ROUTE_IMAGE','result'=>'FAIL_NO_SIGNED_URL_TO_CHECK_BUT_PRODUCT_IMAGES_EXIST','productImages'=>is_int($n)?$n:'UNKNOWN'];}
            }catch(Throwable $e){$failed[]='MEDIA_ROUTE_IMAGE';$rows[]=['check'=>'MEDIA_ROUTE_IMAGE','result'=>'FAIL_MEDIA_SAMPLE_HOOK_UNAVAILABLE_'.self::code($e)];}
            $mediaFacts=static function(array $r):array{$mime=strtolower(trim(explode(';',(string)$r['contentType'])[0]));return ['contentType'=>in_array($mime,['image/jpeg','image/png','image/webp'],true)?$mime:'OTHER','nosniff'=>MediaWeb::nosniff($r['nosniff']),'cacheControlDirectives'=>MediaWeb::mediaCacheControl($r['cache']),'etag'=>MediaWeb::strongEtag($r['etag']),'crossOriginResourcePolicy'=>MediaWeb::crossOriginPolicy($r['corp']),'setCookies'=>count($r['setCookie']),'cfCache'=>$r['cfCache']];};
            $mediaPass=static fn(array $r,array $f):bool=>!$r['transport']&&$r['status']===200&&$f['contentType']!=='OTHER'&&$f['nosniff']&&$f['cacheControlDirectives']&&$f['etag']&&$f['crossOriginResourcePolicy']&&$f['setCookies']===0;
            if($media!==null&&$original!==null){
                $r=$rImg=$this->get($media['path'],$media['query'],null);$f=$mediaFacts($r);
                $add('MEDIA_ROUTE_IMAGE',$r,'200_IMAGE_JPEG_PNG_WEBP_NOSNIFF_PRIVATE_MAX_AGE_604800_IMMUTABLE_ETAG_CORP_CROSS_ORIGIN_NO_SET_COOKIE',$mediaPass($r,$f),$f+['rendition'=>'thumbnail','bodyBytes'=>$r['bytes']],true);
                $r=$this->get($original['path'],$original['query'],null,'HEAD');$f=$mediaFacts($r);
                $add('MEDIA_ROUTE_ORIGINAL_HEAD',$r,'200_HEADERS_ONLY_NO_BODY_SAME_HEADERS',$mediaPass($r,$f)&&$r['body']==='',$f+['rendition'=>'original'],true);
                if($ok($rImg,200)&&MediaWeb::strongEtag($rImg['etag'])){
                    $r2=$this->get($media['path'],$media['query'],null,'GET',false,['If-None-Match: '.$rImg['etag'][0]]);
                    $add('MEDIA_ROUTE_NOT_MODIFIED',$r2,'304_NO_BODY_NO_SET_COOKIE',$ok($r2,304)&&$r2['body']===''&&count($r2['setCookie'])===0,['setCookies'=>count($r2['setCookie'])]);
                }
                $bad=$media['query'];$sig=$bad['s'];$bad['s']=substr($sig,0,-1).(substr($sig,-1)==='a'?'b':'a');
                $r=$this->get($media['path'],$bad,null);$add('MEDIA_ROUTE_BAD_SIGNATURE',$r,'404_NO_SET_COOKIE',$ok($r,404)&&count($r['setCookie'])===0,['setCookies'=>count($r['setCookie'])]);
                $r=$this->get($media['path'],['v'=>$media['query']['v']],null);$add('MEDIA_ROUTE_MISSING_SIGNATURE',$r,'404_NO_SET_COOKIE',$ok($r,404)&&count($r['setCookie'])===0,['setCookies'=>count($r['setCookie'])]);
                $r=$this->get('/media/p/999999999999/thumbnail',['v'=>$media['query']['v'],'s'=>$media['query']['s']],null);$add('MEDIA_ROUTE_UNKNOWN_IMAGE',$r,'404_NO_SET_COOKIE',$ok($r,404)&&count($r['setCookie'])===0,['setCookies'=>count($r['setCookie'])]);
            }
        }
        // A failed check is judged against the shop state of the whole batch: if the owner changed it meanwhile, nothing is proven.
        $shopAfter=null;if($failed!==[]){try{$shopAfter=$this->shop();}catch(Throwable $e){$shopAfter=['unreadable'=>self::code($e)];}}
        $this->emit('PUBLIC_HTTP_SMOKES',['state'=>$state,'shop'=>$shop,'shopAfterFailure'=>$shopAfter,'rows'=>$rows,'failed'=>$failed,'inconclusive'=>$inconclusive,'rateLimited'=>$limited,'rateLimitWaitedSeconds'=>$this->limitBatch['waited'],'batch'=>$retried?2:1,'noindexPolicy'=>'OWNER_POLICY_NO_INDEXING_X_ROBOTS_TAG_NOINDEX_REQUIRED_ON_PUBLIC_SHOP_AND_LEGAL_PAGES_SENT_BY_THE_SERVER_WIDE_PUBLIC_HTACCESS','noindexMissing'=>$noindexMissing,'notices'=>$notices,'varyUnverified'=>$unverifiedVary,'requestsWrite'=>'NONE_GET_AND_HEAD_ONLY','login'=>'NEVER_LOGGED_IN','logo'=>'DATA_DEPENDENT_INFO_WHEN_NO_LINK','productImages'=>'DATA_DEPENDENT_INFO_WHEN_THE_CATALOG_SHOWS_NONE']);
        if($inconclusive!==[])$this->state['cssEdgeStale']=true;
        if($failed!==[]&&$shopAfter!==$shop)throw new RuntimeException('SHOP_STATE_CHANGED_DURING_ACCEPTANCE_STOREFRONT_NOT_VERIFIED');
        if($failed!==[])throw new RuntimeException($installed?'INSTALLED_SMOKE_FAILED':'OLD_STATE_SMOKE_FAILED');
        if($noindexMissing!==[])throw new RuntimeException('NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES');
        if($limited!==[]){
            if(!$retried){$this->emit('SMOKES_RATE_LIMITED_PAUSE',['seconds'=>$this->rateLimitPauseSeconds,'paths'=>$limited,'next'=>'THE_WHOLE_BATCH_ONCE_MORE']);sleep($this->rateLimitPauseSeconds);return $this->smokes($installed,true);}
            throw new RuntimeException('SMOKES_RATE_LIMITED_429');
        }
        if($unverifiedVary)throw new RuntimeException('VARY_COOKIE_NOT_VERIFIABLE_EDGE_HIDES_IT_AND_NO_ORIGIN_ROUTE');
        return $rows;
    }

    /**
     * `php artisan route:list --name=<name> --json` (read-only, exit code 0 required): how many routes carry exactly this name and whether one of them answers GET. This is the only route:list
     * call shape of the coordinator; route:clear and queue:restart stay the only cache related artisan commands.
     */
    private function routeListHits(string $name,bool $strict=true):array
    {
        $r=$this->artisan(['route:list','--name='.$name,'--json'],120);if($r['rc']!==0){if($strict)throw new RuntimeException('ROUTE_LIST_FAILED');return ['hits'=>0,'get'=>false];}$list=json_decode(trim($r['stdout']),true,16);$n=0;$get=false;
        if(is_array($list))foreach($list as $row)if(is_array($row)&&($row['name']??null)===$name){$n++;if(in_array('GET',explode('|',(string)($row['method']??'')),true))$get=true;}
        return ['hits'=>$n,'get'=>$get];
    }

    /**
     * The 11 B1146 route names stay registered and no route cache exists (before, during and after; never created, cleared only by route:clear). Before this rollout: 568 routes and neither report route name;
     * installed: 570 routes (ROUTES_AFTER) and both report names present with GET (counted by the booted router and by `artisan route:list`, which must exit 0).
     */
    private function routesCheck(bool $installed=false):array
    {
        $want=$installed?$this->expectedRoutesAfter:$this->expectedRoutes;
        $r=$this->helper($this->routesScript,['routes']);$now=$this->routeCacheFiles();$names=[];foreach([self::ROUTE_SUBAGENTS,self::ROUTE_SUBAGENTS_CSV] as $n)$names[$n]=$this->routeListHits($n,$installed);
        $this->emit('ROUTES_CHECK',$r+['routeCacheFiles'=>count($now),'expectedRouteCount'=>$want,'installed'=>$installed,'reportRoutes'=>$names]);
        if($r['b1146RouteNamesPresent']!==self::B1146_ROUTE_NAMES||$r['storefrontSettingsRoute']!==true)throw new RuntimeException('B1146_ROUTES_NOT_ALL_PRESENT');
        if(($r['routeCount']??null)!==$want)throw new RuntimeException($installed?'ROUTE_COUNT_NOT_THE_B1155_COUNT':'ROUTE_COUNT_NOT_THE_B1154_COUNT');
        foreach($names as $f){if($installed?($f['hits']!==1||$f['get']!==true):$f['hits']!==0)throw new RuntimeException($installed?'REPORT_ROUTES_MISSING_OR_NOT_GET':'REPORT_ROUTE_NAMES_PRESENT_BEFORE_THE_INSTALL');}
        if($r['routesCached']!==false||$now!==[])throw new RuntimeException('ROUTE_CACHE_PRESENT_NOTHING_CHANGED');return $r;
    }

    /**
     * The code as the CLI sees it, two ways: `artisan route:list --name=<route> --json` (read-only) and the b1155 helper. The helper calls the pure static functions
     * SettingsService::logoHeight and PublicCatalogPresentation::perPage and viewMode with fixed values, checks PublicStorefrontAppearance::freshBranding and MailBranding::logo by
     * reflection (never called) and reads the payload edits as source markers (text only, never executed). It also loads the four new classes of this payload (autoload only, nothing is called), reports
     * whether the three group B libraries have their new methods (reflection), the facts of the two report routes (present, GET), the route count and whether bootstrap/app.php names AppUrlOrigin::redirect (text).
     * Everything B1149 to B1154 delivered is asserted in BOTH states (B1154: the source markers of the admin shell; B1153: the logo height API, the catalog whitelist, freshBranding, MailBranding;
     * B1152: AppUrlOrigin::configured() is the pinned origin, PinAppUrlOrigin is in the global middleware list, bootstrap/app.php names it, the PDF raster guard; B1149 to B1151: site-assets.show,
     * admin.settings.smtp.index, MailSettingsServiceProvider, the POST-only theme route, the B1150 classes and the media route, the password-change throttle and the merchant draft constant).
     * Only the B1155 facts differ: installed all true, old all false (the report routes, the new classes, the source markers, the library methods, the bootstrap text).
     */
    private function featureCheck(bool $installed):array
    {
        $h=$this->helper($this->featureScript,['features',json_encode(self::newClasses(),JSON_THROW_ON_ERROR),self::MEDIA_ROUTE_NAME,$this->expectedAppUrl,json_encode(self::SOURCE_MARKERS,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),json_encode(AdminShellInstallCoordinator::SOURCE_MARKERS,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),json_encode([self::ROUTE_SUBAGENTS,self::ROUTE_SUBAGENTS_CSV],JSON_THROW_ON_ERROR)]);$hits=[];
        foreach(['site-assets.show','admin.settings.smtp.index','public-shop.theme',self::MEDIA_ROUTE_NAME] as $name)$hits[$name]=$this->routeListHits($name)['hits'];
        $facts=$h+['routeList'=>$hits,'expectInstalled'=>$installed];$this->emit('FEATURE_CLI_CHECK',$facts);
        // B1149, B1150, B1151, B1152, B1153 and B1154 stay in BOTH states (this rollout never removes what they delivered).
        $kept=[$h['b1149ClassesLoaded'],$h['routeSiteAssets'],$h['routeSmtpIndex'],$h['providerRegistered'],$hits['site-assets.show']===1,$hits['admin.settings.smtp.index']===1];
        if($h['themeRoutePostOnly']!==true||$hits['public-shop.theme']!==1||$h['routesCached']!==false)throw new RuntimeException('B1148_THEME_ROUTE_MISSING');
        if(in_array(false,$kept,true))throw new RuntimeException('B1149_FEATURES_MISSING');
        if($h['b1150ClassesLoaded']!==true||$h['routeMedia']!==true||$hits[self::MEDIA_ROUTE_NAME]!==1)throw new RuntimeException('B1150_FEATURES_MISSING');
        if($h['throttleApi']!==true||$h['throttleResolvable']!==true||$h['webPasswordUsesThrottle']!==true||$h['apiPasswordUsesThrottle']!==true||$h['merchantIssueLabels']!==true)throw new RuntimeException('B1151_FEATURES_MISSING');
        if($h['originConfiguredIsExpected']!==true||$h['middlewareGlobal']!==true||$h['bootstrapNamesPin']!==true||$h['pdfRasterGuard']!==true)throw new RuntimeException('B1152_FEATURES_MISSING');
        if($h['logoHeightsApi']!==true||$h['catalogWhitelist']!==true||$h['freshBrandingApi']!==true||$h['mailBrandingApi']!==true)throw new RuntimeException('B1153_FEATURES_MISSING');
        if(in_array(false,array_values($h['b1154Markers']),true)||$h['b1154Markers']===[])throw new RuntimeException('B1154_FEATURES_MISSING');
        // Old tells from new by the B1155 facts (all false before, all true after).
        $new=array_values($h['sourceMarkers']);$apis=array_values($h['libraryApis']);$routeFacts=[];foreach($h['reportRoutes'] as $f)$routeFacts[]=$f['present']===true&&$f['get']===true;
        if($h['newClassesTotal']!==count(self::newClasses()))throw new RuntimeException('NEW_CLASS_LIST_NOT_PASSED_TO_THE_HELPER');
        if($installed){
            if($new===[]||in_array(false,$new,true))throw new RuntimeException('NEW_FILES_NOT_PRESENT_AFTER_INSTALL');
            if($h['newClassesAutoloaded']!==true||$apis===[]||in_array(false,$apis,true)||$routeFacts!==[true,true]||$h['bootstrapNamesRedirect']!==true)throw new RuntimeException('NEW_CODE_NOT_LOADABLE_AFTER_INSTALL');
        }else{
            if($new===[]||in_array(true,$new,true))throw new RuntimeException('NEW_FILES_PRESENT_IN_THE_OLD_STATE');
            if($h['newClassesLoaded']!==0||in_array(true,$apis,true)||in_array(true,$routeFacts,true)||$h['bootstrapNamesRedirect']!==false)throw new RuntimeException('NEW_CODE_PRESENT_IN_THE_OLD_STATE');
        }
        return $facts;
    }

    /**
     * CLI, read-only (the helper mode login-bg): whether the login background settings hold the YouTube mode with a syntactically valid 11-character video id. A BOOLEAN only: neither the mode nor the id
     * is printed. The registration page CSP lists https://www.youtube-nocookie.com in frame-src exactly when this is true.
     */
    private function loginBackgroundFacts():array
    {
        $h=$this->helper($this->featureScript,['login-bg']);if(!is_bool($h['youtubeModeValidId']??null))throw new RuntimeException('LOGIN_BACKGROUND_FACT_UNREADABLE');
        return ['youtubeModeValidId'=>$h['youtubeModeValidId']];
    }

    /** The merchant approval facts from the pinned b1146-app-check approval mode (read-only SELECTs). */
    private static function approvalFacts(array $r):array
    {
        if(!is_bool($r['approved']??null)||!is_bool($r['approvalDigestMatchesFingerprint']??null))throw new RuntimeException('MERCHANT_APPROVAL_UNREADABLE');
        return ['approved'=>$r['approved']??null,'complete'=>$r['complete']??null,'digestPresent'=>$r['approvalDigestPresent']??null,'digestMatchesFingerprint'=>$r['approvalDigestMatchesFingerprint']??null];
    }

    /** Prints the approval before (preflight) and now; informational, the approval is never written. */
    private function approvalReport(string $label):void
    {
        $before=$this->state['approvalBefore']??null;try{$after=self::approvalFacts($this->helper($this->routesScript,['approval']));}catch(Throwable $e){$after=self::code($e);}
        $this->emit('MERCHANT_APPROVAL',['label'=>$label,'before'=>$before,'after'=>$after,'ok'=>$before!==null&&$before===$after?'UNCHANGED':'CHANGED_OR_UNREADABLE_REVIEW_IT_IN_THE_ADMIN','writtenByThisTool'=>false]);
    }

    /** The shop state now equals the state recorded at the preflight and is ON; otherwise the storefront checks prove nothing. */
    private function requireRecordedShop(string $point):array
    {
        $shop=$this->shop();$this->emit('SHOP_STATE_CHECK',['point'=>$point,'shop'=>$shop]);
        if(!$shop['open']||$shop!==array_diff_key($this->state['shopRecorded']??[],['writtenByThisTool'=>true]))throw new RuntimeException('SHOP_STATE_CHANGED_DURING_ACCEPTANCE_STOREFRONT_NOT_VERIFIED');
        return $shop;
    }

    /** The measured web hold (max(realpath_cache_ttl, revalidate_freq) + 5). */
    private function fullHold():int
    {
        return max(1,min(FreshnessProbe::MAX_HOLD_SECONDS,(int)($this->state['holdSeconds']??FreshnessProbe::MAX_HOLD_SECONDS)));
    }

    /**
     * CLI, read-only, in its own uncached process (the pinned-with-the-package b1155-app-check.php env mode): the PHP extensions the
     * new code needs (dom, libxml, fileinfo, mbstring and one of gd/imagick), APP_URL is an origin the shipped catalog rule
     * accepts (JSON-LD) and is EXACTLY the pinned origin (a boolean, the value is not printed), whether a trusted-host or canonical-host
     * setting exists (informational), and an HMAC fingerprint of the effective mail transport (default mailer, transport, host, port,
     * encryption, a hash of the username; never the password, never a value). $label is only printed.
     */
    private function envCheck(string $label):array
    {
        $r=$this->helper($this->featureScript,['env',$this->mailKey(),$this->expectedAppUrl]);$this->emit('ENVIRONMENT_CLI_CHECK',['label'=>$label]+array_diff_key($r,['mailFingerprintHMAC'=>true]));
        foreach(self::EXTENSIONS as $x)if(($r['extensions'][$x]??false)!==true)throw new RuntimeException('PHP_EXTENSION_MISSING_IN_CLI_'.strtoupper($x));
        if(($r['extensions']['gd']??false)!==true&&($r['extensions']['imagick']??false)!==true)throw new RuntimeException('PHP_EXTENSION_MISSING_IN_CLI_GD_OR_IMAGICK');
        return $r;
    }

    /**
     * `php artisan app:product-media-doctor` (read-only checks of the gallery tables, routes, image libraries and, for the newest public
     * image, a signed /media/p/ URL): exit code 0, the closing line "Galerija proizvoda je spremna." and no line starting with FAIL.
     * Only counts and keywords are reported, never the command output.
     */
    private function productMediaDoctor(string $label,bool $required):array
    {
        $r=$this->artisan(['app:product-media-doctor'],300);$lines=preg_split('/\R/',$r['stdout'])?:[];
        $fails=count(array_filter($lines,static fn($l)=>str_starts_with(ltrim((string)$l),'FAIL')));$ready=str_contains($r['stdout'],'Galerija proizvoda je spremna.');
        $facts=['label'=>$label,'rc'=>$r['rc'],'ready'=>$ready,'failLines'=>$fails,'stderrBytes'=>$r['stderrBytes']];$this->emit('PRODUCT_MEDIA_DOCTOR',$facts);
        if($required&&($r['rc']!==0||!$ready||$fails!==0))throw new RuntimeException('PRODUCT_MEDIA_DOCTOR_FAILED');
        return $facts;
    }

    /** The per-run random HMAC key; it lives only in the private 0600 state file and is never printed. */
    private function mailKey():string
    {
        if(!is_string($this->state['mailKey']??null)||preg_match('/^[a-f0-9]{64}$/D',$this->state['mailKey'])!==1){$this->state['mailKey']=bin2hex(random_bytes(32));}
        return $this->state['mailKey'];
    }

    /** The effective mail transport must be byte-for-byte the same after the install as before it (the new provider reads settings only). */
    private function mailFingerprintUnchanged(string $point):void
    {
        $now=$this->helper($this->featureScript,['env',$this->mailKey(),$this->expectedAppUrl])['mailFingerprintHMAC']??null;$before=$this->state['mailFingerprintHMAC']??null;
        $this->emit('MAIL_TRANSPORT_FINGERPRINT',['point'=>$point,'status'=>is_string($now)&&$now===$before?'MAIL_TRANSPORT_UNCHANGED':'MAIL_TRANSPORT_CHANGED','unchanged'=>is_string($now)&&$now===$before,'valuesPrinted'=>false,'testMailSent'=>false,'smtpSettingsRead'=>'FINGERPRINT_ONLY']);
        if(!is_string($now)||!is_string($before)||$now!==$before)throw new RuntimeException('MAIL_TRANSPORT_FINGERPRINT_CHANGED');
    }

    /**
     * PREFLIGHT, read-only apart from the private probe record and its transient probe file. Every gate is evaluated and
     * printed; the caller HOLDs on any blocker. $stage 'fresh' (before install) or 'installed' (re-run after the PASS).
     */
    private function preflight(string $stage):array
    {
        $g=[];$v=['stage'=>$stage];$installed=$stage==='installed';
        $try=static function(string $gate,callable $check)use(&$g):mixed{try{$r=$check();$g[$gate]=null;return $r;}catch(Throwable $e){$g[$gate]=self::code($e);return null;}};
        $chain=$try('chainB1154Report1120',fn()=>$this->chain());if($chain!==null){$this->state['chain']=$chain;$v['retainedDirectoryModes']=$this->retainedModes();}$v['chain']=$chain;
        $manifest=$try('runtimeManifestPlusB1146B1147B1148B1149B1150B1151B1152B1153B1154',fn()=>$this->b1154Manifest());
        $targets=$try('overlayTargets',fn()=>$this->targets());$v['targets']=$targets;
        if($targets!==null){if(!$installed&&!$targets['preState'])$g['overlayTargets']=$targets['installed']?'OVERLAY_ALREADY_PRESENT_WITHOUT_B1155_STATE_NO_BACKUP_NOTHING_CHANGED':($targets['link']!==[]?'TARGET_IS_SYMLINK':'UNKNOWN_PRE_STATE_REFUSED');if($installed&&!$targets['installed'])$g['overlayTargets']='INSTALLED_DIGESTS_CHANGED';}
        // The nine new paths must be absent (and the new directory with them) before the first change: $targets['preState'] already requires absent for every kind new; the directory is checked on its own.
        if(!$installed)$g['newPathsAbsent']=count(array_filter($this->plan,fn($s)=>$s['kind']==='new'&&(file_exists($this->target($s))||is_link($this->target($s)))))===0&&!file_exists($this->active().'/resources/views/public-shop/partials')?null:'NEW_PATH_OR_DIRECTORY_ALREADY_EXISTS_NOTHING_CHANGED';
        $v['tree']=$manifest!==null&&$chain!==null?$try('activeTreeEqualsManifestPlusB1146B1147B1148B1149B1150B1151B1152B1153B1154',fn()=>$installed?LeanGates::verifyTree($this->active(),$this->installedManifest(),self::CONFIG_SHA,$chain['htaccessSHA256'],self::installedTreeFiles()):LeanGates::verifyTree($this->active(),$manifest,self::CONFIG_SHA,$chain['htaccessSHA256'],self::B1154_TREE_FILES)):null;
        if(!array_key_exists('activeTreeEqualsManifestPlusB1146B1147B1148B1149B1150B1151B1152B1153B1154',$g))$g['activeTreeEqualsManifestPlusB1146B1147B1148B1149B1150B1151B1152B1153B1154']='NOT_EVALUATED_CHAIN_OR_MANIFEST';
        if(!$installed)$v['replaceable']=$try('targetsReplaceable',function():array{$n=0;foreach($this->plan as $s){if($s['kind']==='modified'){DashboardReportsFiles::replaceable($this->target($s));$n++;}else{$d=dirname($this->target($s));while(!is_dir($d))$d=dirname($d);if(is_link($d)||!is_writable($d))throw new RuntimeException('TARGET_DIRECTORY_NOT_WRITABLE');}}return ['modifiedOneLinkNoAcl'=>$n];});
        // This payload has four PHP classes: an authoritative composer classmap would hide them from the autoloader, so it blocks (the check cannot fire where B1154 passed).
        $g['composerClassmapNotAuthoritative']=$this->classmapBlocker();
        // No migration, no composer change and no config change: asserted and printed (the helper and the migration gate below read the live state).
        $v['scope']=['migrations'=>'NONE','composer'=>'NONE','config'=>'NONE','env'=>'UNCHANGED','payloadUnderDatabaseConfigComposer'=>count(array_filter($this->plan,static fn($s)=>preg_match('~^(database/|config/|composer\.|\.env)~',$s['path'])===1))];if($v['scope']['payloadUnderDatabaseConfigComposer']!==0)$g['payloadScope']='PAYLOAD_TOUCHES_DATABASE_CONFIG_OR_COMPOSER';
        $g['configNotCached']=file_exists($this->active().'/bootstrap/cache/config.php')?'CONFIG_CACHE_PRESENT':null;
        $g['noRouteCache']=$this->routeCacheFiles()===[]?null:'ROUTE_CACHE_PRESENT_NOTHING_CHANGED';
        $g['noMaintenanceMode']=file_exists($this->active().'/storage/framework/down')||file_exists($this->active().'/storage/framework/maintenance.php')?'MAINTENANCE_MODE_ACTIVE':null;
        $try('noLeftoverProbeOrTemp',function():void{FreshnessProbe::assertNoLeftover($this->active().'/public');if($this->ownTemps()!==[])throw new RuntimeException('LEFTOVER_B1147_B1148_B1149_B1150_B1151_B1152_B1153_B1154_OR_B1155_TEMP_FILE');});
        $v['caches']=$try('cacheState',fn()=>['routeCacheFiles'=>count($this->routeCacheFiles()),'compiledViews'=>$this->compiledViews(),'viewsCached'=>$this->compiledViews()>0]);
        $v['logs']=LeanGates::dailyLogs($this->active().'/storage/logs');$g['laravelDailyLog']=$v['logs']['dailyFiles']>0?null:'LARAVEL_DAILY_LOG_MISSING';
        $v['migrations']=$try('migrateStatus71Ran0Pending',fn()=>$this->migrationStatus());
        $app=$try('appStateDatabase',fn()=>$this->helper($this->appStateScript,['state']));
        if($app!==null){
            $shop=self::shopOf($app);$v['shop']=$shop+['writtenByThisTool'=>false];$v['app']=['backupsRunning'=>$app['backups']['running']];
            $g['shopStateRecorded']=!$shop['known']?'SHOP_SWITCH_VALUE_UNKNOWN':(!$installed&&!$shop['open']?'SHOP_IS_OFF_B1155_INSTALLS_ONLY_WITH_THE_SHOP_ON_NOTHING_CHANGED':null);
            $g['noRunningBackup']=$app['backups']['table']===true&&$app['backups']['running']===0?null:'BACKUP_RUN_IN_PROGRESS_OR_TABLE_MISSING';
            if($this->scheduleGate){$s=$try('outsideScheduledBackupWindow',fn()=>$this->scheduleState($app));$v['schedule']=$s;if($s!==null&&$s['conflict']!==null)$g['outsideScheduledBackupWindow']='SCHEDULED_BACKUP_WITHIN_30_MINUTES_'.strtoupper((string)$s['conflict']['type']);}
        }else foreach(['shopStateRecorded','noRunningBackup','outsideScheduledBackupWindow'] as $gate)$g[$gate]='NOT_EVALUATED_APP_STATE';
        $env=$try('environmentCli',fn()=>$this->envCheck('preflight-'.$stage));$v['environment']=$env===null?null:array_diff_key($env,['mailFingerprintHMAC'=>true]);
        // INFO only, never a blocker: where the rate limiters (and the bad-signature counter of the media route) keep their counters.
        $v['limiterStore']=is_array($env)&&is_string($env['limiterStore']??null)&&preg_match('/^[a-z]{3,12}$/D',$env['limiterStore'])===1?$env['limiterStore']:'UNKNOWN';$this->state['limiterStore']=$v['limiterStore'];
        $g['appUrlHttpsOrigin']=$env===null?'NOT_EVALUATED_ENVIRONMENT_CLI':(($env['appUrlHttpsOrigin']??false)===true?null:'APP_URL_NOT_A_BARE_HTTPS_ORIGIN_JSON_LD_WOULD_BE_MISSING');
        // This rollout pins every generated URL to ONE origin: the effective config('app.url') must be exactly that string (config is not cached, see the gate above).
        $g['appUrlExactlyThePinnedOrigin']=$env===null?'NOT_EVALUATED_ENVIRONMENT_CLI':(($env['appUrlExact']??false)===true?null:'APP_URL_NOT_EXACTLY_THE_PINNED_ORIGIN_NOTHING_CHANGED');
        // Informational: a trusted-host or canonical-host setting would interact with the pin; none is expected (never a blocker).
        $v['trustedOrCanonicalHostConfig']=is_array($env)&&is_bool($env['trustedOrCanonicalHostConfigPresent']??null)?($env['trustedOrCanonicalHostConfigPresent']?'PRESENT_INFO_REVIEW_IT':'NONE'):'UNKNOWN';
        if($env!==null&&!$installed)$this->state['mailFingerprintHMAC']=$env['mailFingerprintHMAC'];
        $v['features']=$try('featureCliState',fn()=>$this->featureCheck($installed));
        $v['routes']=$try('b1146LegalRoutesNoRouteCache',fn()=>$this->routesCheck($installed));
        // The login background facts (booleans only, never the mode or the id): the registration page CSP of the installed state depends on youtubeModeValidId.
        $v['loginBackground']=$try('loginBackgroundRead',fn()=>$this->loginBackgroundFacts());if(is_array($v['loginBackground']))$this->state['loginBackground']=$v['loginBackground'];
        // The doctor is a gate in the preflight too: every check of the live B1150 doctor (tables, columns, routes, GD or Imagick, the file of the
        // newest public image, the signed /media/p/ URL) is a fact about the environment or the data and passes on a healthy production, so a FAIL
        // there is a HOLD with nothing changed (an install that would only be rolled back after the acceptance doctor failed for the same reason).
        // Nothing is ignored; this rollout does not touch the doctor, so the same doctor judges again after the install.
        $v['productMediaDoctor']=$try('productMediaDoctorBeforeAnyChange',fn()=>$this->productMediaDoctor('preflight-'.$stage,true));
        $v['headroom']=$try('diskHeadroom',fn()=>$this->headroom());
        $v['artisanCensus']=$chain!==null?$try('noRunningCmsArtisan',fn()=>$this->census()):null;if(!array_key_exists('noRunningCmsArtisan',$g))$g['noRunningCmsArtisan']='NOT_EVALUATED_CHAIN';
        if($targets!==null&&($targets['preState']||$targets['installed'])&&isset($this->state['id'])&&$v['caches']!==null){$web=$try('webProbe',fn()=>$this->probeRun('preflight-'.$stage,$installed,false,false,true));$v['web']=$web;if($web!==null&&$web['failures']!==[])$g['webProbe']=$web['failures'][0];}else $g['webProbe']='NOT_EVALUATED_TARGETS';
        // The web worker's own app.url must be the pinned origin too (the CLI gate above reads the same files but not the server environment): a HOLD before any write.
        if($targets!==null&&($targets['preState']||$targets['installed'])&&isset($this->state['id']))$v['webAppUrl']=$try('webAppUrlPinned',fn()=>$this->webAppUrlCheck('preflight-'.$stage));else $g['webAppUrlPinned']='NOT_EVALUATED_TARGETS';
        if(isset($v['web']['holdSeconds'])){
            $this->state['groupPauseSeconds']=(int)($v['web']['revalidateFreq']??2)+1;
            $need=$this->requiredWindowSeconds((int)$v['web']['holdSeconds']);$v['timeBound']=['holdSeconds'=>(int)$v['web']['holdSeconds'],'groupPauseSeconds'=>$this->groupPause(),'smokeCapSeconds'=>$this->smokeCapSeconds(),'requiredWindowSeconds'=>$need,'rule'=>'9*HOLD+2*SMOKE_CAP+(4*GROUPS-2)*PAUSE+(2*GROUPS-1)*LIVENESS(370)+3*5*WEB_PROOF(60)+300<=WINDOW,GROUPS=7'];
            $this->scheduleNeedSeconds=max($this->scheduleNeedSeconds,$need);
            if($this->scheduleGate&&$app!==null){$s=$try('scheduleWindowFitsTimeBound',fn()=>$this->scheduleState($app));$v['scheduleTimeBound']=$s;if($s!==null&&$s['conflict']!==null)$g['scheduleWindowFitsTimeBound']='SCHEDULED_BACKUP_WITHIN_THE_'.$need.'_SECONDS_THIS_RUN_MAY_NEED';}
        }else $g['timeBound']='NOT_EVALUATED_WEB_PROBE';
        $v['merchantApproval']=$try('merchantApprovalRead',fn()=>self::approvalFacts($this->helper($this->routesScript,['approval'])));
        $this->emailFacts=null;$try('liveSmokes',fn()=>$this->smokes($installed));
        // The old-state e-mail obfuscation count is the baseline the installed smokes compare with (INFO only); kept in the saved state, never printed as a value.
        if(!$installed&&$this->emailFacts!==null)$this->state['emailBaseline']=$this->emailFacts;
        // The old-state CSP of /katalog (nonce normalized) and the Turnstile and YouTube facts of the CSP of /registracija: the baseline the installed smokes compare with (header values are saved, no secret is in a CSP).
        if(!$installed&&isset($this->cspSeen['catalog'],$this->cspSeen['register']))$this->state['cspBaseline']=$this->cspSeen;
        $blockers=array_values(array_unique(array_filter($g,static fn($c)=>$c!==null)));
        if(isset($v['shop']))$this->state['shopRecorded']=$v['shop'];
        $report=['stage'=>$stage,'gates'=>array_map(static fn($c)=>$c??'PASS',$g),'values'=>$v,'blockers'=>$blockers,'applicationFiles'=>'NONE','database'=>'NO_PRODUCT_CART_OR_SWITCH_WRITES_SESSIONS_AND_APP_CACHE_ONLY','shopSwitch'=>'READ_ONLY','transientProbe'=>'WRITTEN_AND_REMOVED','privateState'=>'B1155_NAMESPACE_ONLY'];$this->emit('PREFLIGHT',$report);return $report;
    }

    /** null = composer autoload is not authoritative; otherwise the blocker code. */
    private function classmapBlocker():?string
    {
        foreach(['autoload_real.php','autoload_static.php'] as $name){
            $path=$this->active().'/vendor/composer/'.$name;if(!is_file($path)||is_link($path)){if($name==='autoload_real.php')return 'COMPOSER_AUTOLOAD_REAL_MISSING_OR_LINK_CANNOT_VERIFY_THE_CLASSMAP_MODE';continue;}$text=(string)file_get_contents($path);
            if(preg_match('/setClassMapAuthoritative\s*\(\s*true\s*\)/i',$text)||preg_match('/classMapAuthoritative\s*=\s*true/i',$text))return 'COMPOSER_CLASSMAP_AUTHORITATIVE_NEW_CLASSES_WOULD_NOT_LOAD';
        }
        return null;
    }

    private function loadExisting():bool
    {
        if(!file_exists($this->pointer))return false;if(is_link($this->pointer)||filesize($this->pointer)>8192)throw new RuntimeException('B1155_POINTER');$p=json_decode((string)file_get_contents($this->pointer),true,8,JSON_THROW_ON_ERROR);
        if(!preg_match('/^[a-f0-9]{32}$/D',(string)($p['id']??''))||($p['private']??null)!==$this->base.'/'.$p['id'])throw new RuntimeException('B1155_POINTER_SCOPE');$path=$p['private'].'/state.json';
        if(is_link($path)||realpath($path)!==$path||filesize($path)>16777216)throw new RuntimeException('B1155_STATE');$this->state=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
        if(($this->state['schemaVersion']??0)!==1||($this->state['kind']??null)!=='B1155'||($this->state['id']??null)!==$p['id'])throw new RuntimeException('B1155_STATE_BINDING');return true;
    }

    private function begin():void
    {
        $id=bin2hex(random_bytes(16));$private=$this->base.'/'.$id;if(!mkdir($private,0700))throw new RuntimeException('PRIVATE_TRANSACTION_CREATE');
        $this->state=['schemaVersion'=>1,'kind'=>'B1155','id'=>$id,'private'=>$private,'phase'=>'NEW','startedAt'=>time(),'probes'=>[],'temps'=>[],'applied'=>[],'createdDirs'=>[]];$this->save('PREFLIGHT');
    }

    /** Per-file 0600 copies of every replaced file with a digest manifest (0600). */
    private function backup(array $preflight):void
    {
        $dir=$this->state['private'].'/backup';if(!is_dir($dir)&&!mkdir($dir,0700))throw new RuntimeException('BACKUP_DIRECTORY_CREATE');$files=[];
        foreach($this->plan as $s){
            if($s['kind']==='new'){$files[$s['n']]=['path'=>$s['path'],'kind'=>'new','before'=>'ABSENT'];continue;}
            $t=$this->target($s);$bytes=(string)file_get_contents($t);if(!hash_equals((string)$s['preSHA256'],hash('sha256',$bytes)))throw new RuntimeException('PRE_STATE_CHANGED_BEFORE_BACKUP');clearstatcache(true,$t);$st=stat($t);
            $copy=$dir.'/'.sprintf('%02d',$s['n']).'.orig';$this->privateBackupCopy($copy,$bytes);
            $files[$s['n']]=['path'=>$s['path'],'kind'=>'modified','copy'=>$copy,'sha256'=>$s['preSHA256'],'size'=>strlen($bytes),'mode'=>$st['mode']&0777,'gid'=>$st['gid'],'mtime'=>$st['mtime']];
        }
        $this->state['backup']=['directory'=>$dir,'files'=>$files];$this->state['cache']=['viewsCached'=>$preflight['values']['caches']['viewsCached']];
        $this->state['groupPauseSeconds']=(int)($preflight['values']['web']['revalidateFreq']??2)+1;$this->state['holdSeconds']=(int)($preflight['values']['web']['holdSeconds']??FreshnessProbe::MAX_HOLD_SECONDS);$this->state['approvalBefore']=$preflight['values']['merchantApproval']??null;$doc=$dir.'/manifest.json';if(is_file($doc))unlink($doc);AtomicFile::privateCopy($doc,json_encode(['batch'=>'B1155','files'=>array_values($files)],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
        $this->fault('AFTER_BACKUP_COPIES');
        $this->emit('PER_FILE_BACKUP',['directory'=>$dir,'modifiedFilesCopied'=>count(array_filter($files,static fn($f)=>$f['kind']==='modified')),'newFilesRecordedAbsent'=>count(array_filter($files,static fn($f)=>$f['kind']==='new')),'mode'=>'0600','manifestSHA256'=>hash_file('sha256',$doc)]);
        $this->save('BACKED_UP');$this->fault('AFTER_BACKUP');
    }

    /** A copy left by an interrupted backup of this transaction is reused only when it holds exactly the live bytes. */
    private function privateBackupCopy(string $copy,string $bytes):void
    {
        if(file_exists($copy)||is_link($copy)){
            try{AtomicFile::readPrivate($copy,hash('sha256',$bytes));return;}catch(Throwable){if(is_link($copy)||!is_file($copy)||!unlink($copy))throw new RuntimeException('BACKUP_COPY_STALE_NOT_REMOVABLE');$this->emit('BACKUP_COPY_REWRITTEN',basename($copy));}
        }
        AtomicFile::privateCopy($copy,$bytes);AtomicFile::readPrivate($copy,hash('sha256',$bytes));
    }

    /** route:clear (no route cache ever exists here; this only guarantees it and makes the CLI read routes/web.php again). Never a route cache build. */
    private function routeClear():void
    {
        $this->artisanOk(['route:clear']);
    }

    /**
     * view:clear only (compiled views are rebuilt on demand by the web workers, never pre-compiled). With $full (the end of the
     * install and of a rollback) also route:clear and queue:restart (running queue workers pick up the new or restored code). Never a route, config or view cache build.
     */
    private function refreshViews(bool $full=false):void
    {
        if($full)$this->routeClear();$this->artisanOk(['view:clear']);if($full)$this->artisanOk(['queue:restart']);$this->changed();$this->persist();
    }

    /**
     * The last gate before the first file of a guard group (D, E or F): every file of an earlier group must hold exactly the payload in the tree and the web-SAPI proof of this run for that
     * group must have passed (D and F: the proof classes load from the payload files; E: both changed stylesheets are served with the payload bytes). Without both, no file of that group is ever written.
     */
    /**
     * Before group D (bootstrap/app.php takes the whole site down when it is broken): every PHP payload file passes `php -l` (private 0600 copies, removed again), and the payload bootstrap/app.php
     * is really loaded in a CLI process with the live base path (the helper boot-proof mode evaluates the private copy, builds the application, resolves the exception handler and the HTTP kernel; no
     * live file is touched, nothing is written, nothing but booleans is printed). A failure stops the install before any group-D byte is written and the normal rollback of A to C follows.
     */
    private function bootProof():void
    {
        $dir=$this->state['private'].'/proof';if(!is_dir($dir)&&!mkdir($dir,0700))throw new RuntimeException('BOOT_PROOF_DIRECTORY');
        $files=[];$linted=0;
        try{
            foreach($this->plan as $s){
                if(!str_ends_with($s['path'],'.php'))continue;
                $f=$dir.'/'.sprintf('%02d',$s['n']).'.php';AtomicFile::privateCopy($f,$this->payload($s));$files[]=$f;
                $r=$this->command([$this->php,'-l',$f],$dir,60);if($r['rc']!==0)throw new RuntimeException('BOOT_PROOF_PHP_LINT_FAILED_'.$s['n']);$linted++;
            }
            $f=$dir.'/bootstrap-app.php';AtomicFile::privateCopy($f,$this->bootProofBytes??$this->payload($this->step('bootstrap/app.php')));$files[]=$f;
            $h=$this->helper($this->featureScript,['boot-proof',$f]);
            if(($h['applicationBuilt']??false)!==true||($h['handlerResolved']??false)!==true||($h['httpKernelResolved']??false)!==true)throw new RuntimeException('BOOT_PROOF_FAILED');
        }finally{foreach($files as $f)if(is_file($f)&&!is_link($f))@unlink($f);@rmdir($dir);}
        $this->state['bootProof']=['at'=>time(),'phpFilesLinted'=>$linted];$this->persist();
        $this->emit('BOOT_PROOF',['phpFilesLinted'=>$linted,'bootstrapLoadedInCli'=>true,'liveTreeTouched'=>false,'writes'=>'NONE']);
    }

    /**
     * Right after the last file of group D (before the group pause and the long hold): /up and /katalog with a short timeout (8 s, release marker so the edge cannot answer from its cache). Anything but
     * 200 (a 429 is inconclusive) throws, so the normal rollback restores bootstrap/app.php at once. The measured outage is printed.
     */
    private function fastCheck():void
    {
        $t0=hrtime(true);$marker=bin2hex(random_bytes(8));$rows=[];$bad=[];$paths=['/up'];if($this->shop()['open'])$paths[]=self::CATALOG_PATH;
        foreach($paths as $p){$r=$this->web->fastGet($p,$marker,8);$ok=!$r['transport']&&($r['status']===200||$r['status']===429);$rows[]=['check'=>$p,'observed'=>$r['transport']?'TRANSPORT':$r['status'],'result'=>$ok?'PASS':'FAIL'];if(!$ok)$bad[]=$p;}
        $this->emit('FAST_CHECK_AFTER_BOOTSTRAP',['rows'=>$rows,'seconds'=>round((hrtime(true)-$t0)/1e9,1),'timeoutSeconds'=>8]);
        if($bad!==[])throw new RuntimeException('FAST_CHECK_FAILED_AFTER_BOOTSTRAP');
    }

    private function guardGroupCheck(string $group):void
    {
        $kind=self::GUARDS[$group]??null;if($kind===null)return;
        foreach($this->plan as $x)if($x['group']<$group&&$this->classify($x)['state']!=='payload')throw new RuntimeException('GUARD_REFUSED_AN_EARLIER_FILE_IS_NOT_IN_PLACE');
        if($kind==='classes'){$proof=$this->state['webClassProof'][$group]??null;if(!is_array($proof)||($proof['classes']??null)!==array_keys($this->proofClassFiles()))throw new RuntimeException('GUARD_REFUSED_NO_WEB_SAPI_CLASS_PROOF');if($group==='D'&&!is_array($this->state['bootProof']??null))throw new RuntimeException('GUARD_REFUSED_NO_BOOT_PROOF');}
        else{$proof=$this->state['webAssetProof']??null;if(!is_array($proof)||($proof['assets']??null)!==array_keys($this->assetFiles()))throw new RuntimeException('GUARD_REFUSED_NO_WEB_SAPI_ASSET_PROOF');}
    }

    private function install():void
    {
        $this->state['logBaseline']=LeanGates::logBaseline($this->active().'/storage/logs');$this->state['applied']=[];unset($this->state['webAssetProof'],$this->state['webClassProof'],$this->state['bootProof']);$this->save('INSTALL_INTENT');$group=null;
        // Between groups every web worker revalidates the files of the previous group first (revalidate_freq + 1 s), so a
        // request never combines new bytecode of a later group with still-cached old bytecode of an earlier one.
        $pause=$this->groupPause();
        foreach($this->plan as $s){
            // At a group boundary: the revalidation pause, the immediate liveness smoke of the group just done (after D this is the check that bootstrap/app.php serves; after F route:clear and the CLI route
            // check run first), and if the next group is a guard group (D, E, F) the full measured hold (it always spans the files before it: the hold runs from the last tree change) and the web-SAPI proof of its premise.
            if($group!==null&&$group!==$s['group']){
                if($group==='D')$this->fastCheck();
                $this->emit('OVERLAY_GROUP_PAUSE',['after'=>$group,'before'=>$s['group'],'seconds'=>$pause]);sleep($pause);
                $this->fault('GROUP_DONE_'.$group);if($group==='F'){$this->routeClear();$this->routesCheck(true);$this->fault('ROUTES_PROVEN');}$this->liveness($group);
                $guard=self::GUARDS[$s['group']]??null;
                if($guard==='classes'){$this->hold('before-guard-group-'.$s['group'],$this->fullHold());$this->webClassProof('before-guard-group-'.$s['group'],$s['group']);$this->fault('CLASSES_PROVEN');if($s['group']==='D'){$this->bootProof();$this->fault('BOOT_PROVEN');}}
                elseif($guard==='assets'){$this->hold('before-guard-group-'.$s['group'],$this->fullHold());$this->webAssetProof('before-guard-group-'.$s['group']);$this->fault('ASSETS_PROVEN');}
            }
            $group=$s['group'];
            $this->fault('BEFORE_STEP_'.$s['n']);$this->guardGroupCheck($s['group']);$t=$this->target($s);$bytes=$this->payload($s);$this->state['applied'][$s['n']]='PENDING';
            if($s['kind']==='new'){
                // Missing directories (resources/views/public-shop/partials is the only one expected; public/assets/css and the other parents exist) are created outermost first, each recorded in the saved
                // state BEFORE its mkdir, so a kill leaves a record; a rollback removes the empty ones in reverse (innermost first). Nothing outside the CMS tree is ever created.
                $dir=dirname($t);$missing=[];for($d=$dir;!is_dir($d);$d=dirname($d)){if(is_link($d)||!str_starts_with($d,$this->active().'/'))throw new RuntimeException('TARGET_PARENT_MISSING');$missing[]=$d;}
                foreach(array_reverse($missing) as $d){$this->state['createdDirs'][]=$d;$this->persist();if(!mkdir($d,0755))throw new RuntimeException('TARGET_DIRECTORY_CREATE');chmod($d,0755);$pg=stat(dirname($d))['gid'];if(stat($d)['gid']!==$pg)@chgrp($d,$pg);clearstatcache(true,$d);if(stat($d)['gid']!==$pg)throw new RuntimeException('OVERLAY_DIRECTORY_GID_NOT_PRESERVABLE');$this->emit('OVERLAY_DIRECTORY_CREATED',['path'=>substr($d,strlen($this->active())+1),'mode'=>'0755']);}
                $temp=$this->recordTemp(DashboardReportsFiles::tempFor($t));$receipt=DashboardReportsFiles::create($t,$bytes,0644,$temp,stat($dir)['gid']);
            }else{$b=$this->state['backup']['files'][$s['n']];$temp=$this->recordTemp(DashboardReportsFiles::tempFor($t));$receipt=DashboardReportsFiles::replace($t,$bytes,$b['mode'],$b['gid'],$temp,(string)$s['preSHA256']);}
            $this->state['temps']=array_values(array_filter($this->state['temps'],static fn($x)=>$x!==$temp));$this->state['applied'][$s['n']]='DONE';$this->changed();$this->persist();
            $this->emit('OVERLAY_STEP',['n'=>$s['n'],'group'=>$s['group'],'kind'=>$s['kind'],'path'=>$s['path'],'sha256'=>$s['payloadSHA256']]+$receipt);$this->fault('AFTER_STEP_'.$s['n']);
        }
        // The last group is G (the management view, after the routes): ONE revalidation pause so every worker runs it, then the liveness smoke at once (the guest report URLs included; a failure rolls group G back
        // first), and only then route:clear, view:clear and queue:restart (the acceptance judges the rest).
        $this->fault('GROUP_DONE_'.$group);$this->emit('OVERLAY_GROUP_PAUSE',['after'=>$group,'before'=>'ACCEPTANCE','seconds'=>$pause]);sleep($pause);$this->liveness($group);$this->refreshViews(true);$this->save('FILES_INSTALLED');
    }

    /**
     * Read-only acceptance step after group G: the CLI helper compiles each changed or new Blade view (VIEW_CHECKS: the 4 new and the 7 modified views) in memory (Blade::compileString, nothing is written to the
     * compiled-view directory) and parses the compiled PHP (token_get_all with TOKEN_PARSE). A view that does not compile or lint fails the acceptance and the normal automatic rollback follows.
     * This proves the syntax of the views, not how the authenticated dashboard or the report with data render: nobody logs in. No database write, no mail, no value printed.
     */
    private function viewsCheck():array
    {
        $h=$this->helper($this->featureScript,['views',json_encode($this->viewChecks,JSON_THROW_ON_ERROR)]);$bad=[];
        foreach($this->viewChecks as $v)if(($h['views'][$v]['compiled']??false)!==true||($h['views'][$v]['lint']??false)!==true)$bad[]=$v;
        $this->emit('VIEW_COMPILE_CHECK',['views'=>count($this->viewChecks),'failed'=>$bad,'compiledViewDirectory'=>'NOT_WRITTEN']);
        if($bad!==[])throw new RuntimeException('BLADE_VIEW_COMPILE_OR_LINT_FAILED');return $h;
    }

    private function accept():void
    {
        $this->save('ACCEPTANCE_INTENT');$this->viewsCheck();$first=$this->probeRun('installed-invalidate',true,true,false);$this->fault('AFTER_INVALIDATE');$this->hold('installed',(int)$first['holdSeconds']);
        $this->requireRecordedShop('BEFORE_INSTALLED_SMOKES');$this->smokes(true);$this->fault('AFTER_SMOKES_INSTALLED');$this->requireRecordedShop('AFTER_INSTALLED_SMOKES');
        $this->unthrottledChecks('ACTIVE_TREE_AFTER_INSTALL');
        $this->approvalReport('after-install');$this->requireRecordedShop('BEFORE_INSTALLED_PASS');
        $this->save('INSTALLED_PASS');
    }

    /**
     * Puts every backed-up byte back in exact reverse install order (a modified file only when it holds the payload, a new
     * file removed only when it holds the payload; anything foreign is reported and never overwritten; the first failure
     * stops the loop), with the full measured hold right after each of the guard groups F, E and D is back to its old bytes and again before the
     * new files go, re-invalidates, waits and smoke-tests the old state with the shop in whatever state it is now.
     */
    private function restore(string $reason):bool
    {
        $this->emit('OVERLAY_ROLLBACK',['reason'=>$reason]);$problems=[];try{$this->save('ROLLBACK_INTENT');}catch(Throwable $e){$problems[]=self::code($e);}
        try{$this->cleanupRecorded();}catch(Throwable $e){$problems[]=self::code($e);}
        $backup=$this->state['backup']??null;$restored=0;$removed=0;$stoppedBefore=null;$touched=true;
        try{$touched=$this->touched();}catch(Throwable $e){$problems[]=self::code($e);}
        if(!$touched){
            try{if(!$this->targets()['preState'])$problems[]='NOTHING_APPLIED_BUT_TARGETS_NOT_EXACTLY_PRE_STATE';}catch(Throwable $e){$problems[]=self::code($e);}
            foreach(array_reverse($this->state['createdDirs']??[]) as $dir)if(is_dir($dir)&&!is_link($dir)&&(scandir($dir)?:[])===['.','..']&&!rmdir($dir))$problems[]='CREATED_DIRECTORY_NOT_REMOVED';
            try{$this->persist();}catch(Throwable $e){$problems[]=self::code($e);}
            $this->emit('ROLLBACK_FILES',['nothingApplied'=>true,'modifiedRestored'=>0,'newRemoved'=>0,'problems'=>$problems]);
            if($problems===[]){$this->save('ROLLED_BACK');$this->emit('ROLLED_BACK',['files'=>'NONE_WERE_CHANGED','refreshAndWebVerification'=>'NOT_NEEDED','shopSwitch'=>'NOT_WRITTEN']);return true;}
        }elseif(!is_array($backup))$problems[]='ROLLBACK_BACKUP_RECORD_MISSING';
        if($touched&&is_array($backup)){
            $group=null;$pause=$this->groupPause();
            foreach(array_reverse($this->plan) as $s){
                // Exact reverse of the install order, stopping at the FIRST failed restore: the tree is then always an exact
                // install prefix (steps 1..n installed, everything after n back to old), a state the install itself passed.
                // Group G (the management view that renders the new route) goes FIRST, then a revalidation pause, then group F (the routes); the full measured hold follows each of the guard groups F, E and D
                // once it is back to its old bytes (so no web worker still runs the code of a guarded file when what it depends on goes: the routes before the controller classes, the views before the
                // stylesheets, bootstrap/app.php before the libraries), the other boundaries get the revalidation pause, before the new files (group A) the full hold again; the new files are removed last
                // and the created directory after them. A fresh process resuming a rollback times each wait from the saved wall-clock time of the last change.
                $key=$s['group'];$boundary=$group!==null&&$group!==$key;$prev=$group;$group=$key;
                try{
                    if($boundary){
                        if(isset(self::GUARDS[$prev])){$this->emit('ROLLBACK_AFTER_GUARD_GROUP',['group'=>$prev,'guardGroupIsOld'=>count(array_filter($this->plan,fn($x)=>$x['group']===$prev&&$this->classify($x)['state']!=='pre'))===0]);$this->hold('rollback-after-guard-group-'.$prev,$this->fullHold());}
                        elseif($key==='A')$this->hold('rollback-before-new-classes',$this->fullHold());
                        else{$this->emit('ROLLBACK_GROUP_PAUSE',['before'=>$key,'seconds'=>$pause]);$this->hold('rollback-pause-'.$key,$pause);}
                    }
                    $this->fault('BEFORE_RESTORE_'.$s['n']);$c=$this->classify($s);$t=$this->target($s);
                    if($s['kind']==='modified'){
                        if($c['state']==='payload'){$b=$backup['files'][$s['n']];$bytes=AtomicFile::readPrivate($b['copy'],$b['sha256']);DashboardReportsFiles::replace($t,$bytes,$b['mode'],$b['gid'],$this->recordTemp(DashboardReportsFiles::tempFor($t)),$s['payloadSHA256']);$restored++;$this->changed();}
                        elseif($c['state']!=='pre')throw new RuntimeException('ROLLBACK_FOREIGN_CONTENT_NOT_OVERWRITTEN_'.$s['n']);
                    }elseif($c['state']==='payload'){DashboardReportsFiles::removeExact($t,$s['payloadSHA256']);$removed++;$this->changed();}
                    elseif($c['state']!=='absent')throw new RuntimeException('ROLLBACK_FOREIGN_NEW_FILE_NOT_REMOVED_'.$s['n']);
                    $this->fault('AFTER_RESTORE_'.$s['n']);
                }catch(Throwable $e){$problems[]=self::code($e);$stoppedBefore=$s['n'];break;}
            }
            if($stoppedBefore===null)foreach(array_reverse($this->state['createdDirs']??[]) as $dir)if(is_dir($dir)&&!is_link($dir)&&(scandir($dir)?:[])===['.','..']&&!rmdir($dir))$problems[]='CREATED_DIRECTORY_NOT_REMOVED';
            try{$this->cleanupRecorded();$this->refreshViews(true);}catch(Throwable $e){$problems[]=self::code($e);}
        }
        if($touched){
            try{$this->persist();}catch(Throwable $e){$problems[]=self::code($e);}
            $this->emit('ROLLBACK_FILES',['modifiedRestored'=>$restored,'newRemoved'=>$removed,'stoppedAtStep'=>$stoppedBefore,'exactInstallPrefixKept'=>$stoppedBefore===null?null:($this->isInstallPrefix($stoppedBefore)?'STEPS_1_TO_'.$stoppedBefore.'_INSTALLED_LATER_STEPS_RESTORED':'NOT_AN_INSTALL_PREFIX_SEE_RECOVERY_NOTE_FILES'),'problems'=>$problems]);
        }
        if($problems===[])try{
            $t=$this->targets();if(!$t['preState'])throw new RuntimeException('ROLLBACK_TARGETS_NOT_PRE_STATE');$this->routesCheck(false);$this->featureCheck(false);
            $first=$this->probeRun('restored-invalidate',false,true,false);$this->hold('restored',(int)$first['holdSeconds']);
            try{try{$this->smokes(false);}catch(Throwable $e){if(self::code($e)!=='SHOP_STATE_CHANGED_DURING_ACCEPTANCE_STOREFRONT_NOT_VERIFIED')throw $e;$this->emit('OLD_STATE_SMOKES_REPEATED','THE_SHOP_STATE_CHANGED_DURING_THE_BATCH');$this->smokes(false);}}catch(Throwable $e){
                if(self::code($e)==='NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES'){$this->state['rollbackNoindexMissing']=true;$this->emit('OWNER_NOTICE_NOINDEX_MISSING','THE_RESTORED_FILES_ARE_EXACT_BUT_PUBLIC_PAGES_ARE_SERVED_WITHOUT_X-ROBOTS-TAG_NOINDEX_RESTORE_THE_HEADER_LINE_IN_apps/cms/current/public/.htaccess_OR_THE_CLOUDFLARE_RULE_THAT_REMOVES_IT');}
                elseif(self::code($e)==='SMOKES_RATE_LIMITED_429'){$this->state['rollbackSmokesRateLimited']=true;$this->emit('OWNER_NOTICE_RATE_LIMITED','THE_RESTORED_FILES_ARE_EXACT_BUT_THE_SITE_ANSWERED_429_TO_THE_SMOKES_OPEN_https://cms.ald1n.com/katalog_AND_ONE_LEGAL_PAGE_YOURSELF');}
                else throw $e;}
            $this->probeRun('restored-fresh',false,false,true);
            $tree=LeanGates::verifyTree($this->active(),$this->b1154Manifest(),self::CONFIG_SHA,$this->state['chain']['htaccessSHA256'],self::B1154_TREE_FILES);$this->emit('ACTIVE_TREE_AFTER_ROLLBACK',$tree+['equalsManifestPlusB1146B1147B1148B1149B1150B1151B1152B1153B1154'=>true]);
            try{$this->mailFingerprintUnchanged('after-rollback');}catch(Throwable $e){$this->emit('OWNER_NOTICE_MAIL_FINGERPRINT',self::code($e));}
            $this->approvalReport('after-rollback');
        }catch(Throwable $e){$problems[]=self::code($e);}
        if($problems!==[]){
            try{$this->save('ROLLBACK_INCOMPLETE');}catch(Throwable){$this->emit('ROLLBACK_STATE_UNWRITABLE','FIX_DISK_OR_PERMISSIONS_THEN_RUN_THE_SAME_COMMAND');}
            $this->recoveryNote(array_values(array_unique($problems)));return false;
        }
        $this->save('ROLLED_BACK');$this->emit('ROLLED_BACK',['files'=>'EXACT_PRIVATE_COPIES','newFiles'=>'REMOVED','routeCache'=>'NONE_NEVER_CREATED','compiledViews'=>'CLEARED_REBUILT_ON_DEMAND_NOT_PRECOMPILED','tree'=>'EQUALS_8574_FILE_MANIFEST_PLUS_B1146_B1147_B1148_B1149_B1150_B1151_B1152_B1153_B1154','shopSwitch'=>'NOT_WRITTEN']);return true;
    }

    /** Whether any overlay byte may have reached the tree: an applied step, a payload file, a created directory, a non-pre target. */
    private function touched():bool
    {
        foreach((array)($this->state['applied']??[]) as $v)if($v==='DONE'||$v==='PENDING')return true;
        if(($this->state['createdDirs']??[])!==[])return true;
        $t=$this->targets();return !$t['preState']||$t['payload']>0||$t['foreign']!==[]||$t['link']!==[];
    }

    /** Verified, not assumed: steps 1..$k hold the payload, every later step its old bytes (a later new file is absent). */
    private function isInstallPrefix(int $k):bool
    {
        try{foreach($this->plan as $s){$c=$this->classify($s)['state'];if($s['n']<=$k?$c!=='payload':$c!==($s['kind']==='new'?'absent':'pre'))return false;}}catch(Throwable){return false;}
        return true;
    }

    /**
     * The recovery note: every target's state and ONE manual block (a quoted heredoc run by `bash -e`, so the first failing
     * line stops the block), in the automatic rollback order: exact reverse install order (each backup copy sha256-verified
     * right before it is installed, each new file removed), the full measured hold after each of the guard groups F, E and D, the full
     * hold again before the new files, a sleep at every other group boundary, then the empty directories and view:clear.
     * The shop may stay ON: these files change no data.
     */
    private function recoveryNote(array $problems,string $kind='ROLLBACK_INCOMPLETE'):void
    {
        $a=$this->active();$files=[];$q=static fn(string $v):string=>escapeshellarg($v);
        foreach($this->plan as $s){try{$now=$this->classify($s)['state'];}catch(Throwable){$now='UNREADABLE';}$b=$this->state['backup']['files'][$s['n']]??null;$files[$s['n']]=['n'=>$s['n'],'group'=>$s['group'],'kind'=>$s['kind'],'path'=>$s['path'],'now'=>$now,'backupCopy'=>$b['copy']??null,'sha256'=>$b['sha256']??null,'mode'=>isset($b['mode'])?sprintf('%04o',$b['mode']):null,'gid'=>$b['gid']??null];}
        $record=is_array($this->state['backup']['files']??null);
        $next=$kind==='ROLLBACK_INCOMPLETE'?'FIX_THE_REPORTED_CAUSE_THEN_RUN_THE_SAME_COMMAND_IT_RETRIES_THE_ROLLBACK_FIRST':'NOTHING_WAS_ATTEMPTED_CLEAR_THE_BLOCKERS_AND_RUN_THE_SAME_COMMAND';
        $this->emit('RECOVERY_NOTE',['kind'=>$kind,'problems'=>$problems,'files'=>array_values($files),'backupDirectory'=>$this->state['backup']['directory']??null,'backupRecord'=>$record?'PRESENT':'MISSING_NEW_FILES_ARE_NOT_REMOVED_BY_THE_BLOCK','privateCopies'=>'KEPT_0600','next'=>$next,'manualBlock'=>'ONLY_IF_THE_SAME_COMMAND_KEEPS_FAILING_THE_SHOP_MAY_STAY_ON','order'=>'THE_AUTOMATIC_ROLLBACK_ORDER_REVERSE_INSTALL_ORDER_G_THEN_F_THEN_THE_FULL_HOLD_AFTER_EACH_OF_F_E_AND_D_THE_FULL_HOLD_BEFORE_THE_NEW_FILES_A_SLEEP_AT_EVERY_OTHER_GROUP_BOUNDARY_THEN_THE_CREATED_DIRECTORY_AND_VIEW_CLEAR']);
        $lines=['set -euo pipefail','umask 022'];$view='cd '.$q($a).' && '.$q($this->php).' -d opcache.enable=0 -d opcache.enable_cli=0 artisan view:clear --no-interaction';
        $pause=$this->groupPause();$last=null;
        foreach(array_reverse($files) as $f){
            $t=$a.'/'.$f['path'];$key=$f['group'];$act=[];
            if($f['kind']==='modified'&&$f['now']==='payload'&&$f['backupCopy']!==null)$act=['printf '.$q('%s  %s\n').' '.$q((string)$f['sha256']).' '.$q((string)$f['backupCopy']).' | sha256sum --check --strict --quiet -','install -m '.$f['mode'].' -g '.(int)$f['gid'].' '.$q((string)$f['backupCopy']).' '.$q($t)];
            elseif($f['kind']==='modified'&&$f['now']==='payload')$act=['echo '.$q('NO_BACKUP_COPY_FOR '.$t.' STOP').' >&2','exit 2'];
            elseif($f['kind']==='new'&&$f['now']==='payload'&&$record)$act=['rm -f -- '.$q($t)];
            elseif($f['kind']==='modified'&&$f['now']==='absent')echo 'OWNER_INSPECT: '.$t.' IS_MISSING_NOT_REMOVED_BY_B1155_THE_OLD_BYTES_ARE_IN '.($f['backupCopy']??'NO_BACKUP_COPY').' SHA256 '.($f['sha256']??'UNKNOWN').PHP_EOL;
            elseif(!in_array($f['now'],['pre','absent','payload'],true))echo 'OWNER_INSPECT: '.$t.' IS_'.strtoupper($f['now']).'_NOT_TOUCHED_BY_B1155'.PHP_EOL;
            if($act===[])continue;
            if($last!==null&&$last!==$key){if(isset(self::GUARDS[$last])||$key==='A')$lines[]='sleep '.$this->fullHold();else $lines[]='sleep '.$pause;}
            $last=$key;array_push($lines,...$act);
        }
        if($record)foreach(array_reverse($this->state['createdDirs']??[]) as $dir)if(is_dir($dir)&&!is_link($dir))$lines[]='rmdir --ignore-fail-on-non-empty -- '.$q($dir);
        $lines[]=$view;$lines[]='echo B1155_MANUAL_RECOVERY_BLOCK_DONE';
        echo 'OWNER_COMMAND_BLOCK=PASTE_EVERYTHING_FROM_THE_bash_-e_LINE_TO_THE_B1155_RECOVERY_LINE_INTO_THE_SSH_SHELL_IT_STOPS_AT_THE_FIRST_FAILING_LINE'.PHP_EOL;
        echo "bash -e <<'B1155_RECOVERY'".PHP_EOL.implode(PHP_EOL,$lines).PHP_EOL.'B1155_RECOVERY'.PHP_EOL;
        echo 'OWNER_COMMAND_NOTE=THE_WEB_WORKERS_SEE_THE_RESTORED_FILES_AFTER_ABOUT_2_MINUTES_(realpath_cache_ttl)'.PHP_EOL;
    }

    /**
     * Gates before any restore that the owner asks for (--rollback, $strict) or that resumes an interrupted install: no CMS
     * artisan process, and for --rollback a readable app state and a clear scheduled-backup window (the owner override
     * --ignore-schedule lifts only that gate). The shop state is only recorded: the shop may stay ON. Read-only.
     */
    private function rollbackGates(bool $strict):array
    {
        $b=[];$v=[];
        try{$app=$this->helper($this->appStateScript,['state']);$v['shop']=self::shopOf($app)+['writtenByThisTool'=>false];
            if($strict&&$this->scheduleGate){$this->scheduleNeedSeconds=max($this->scheduleNeedSeconds,$this->requiredWindowSeconds($this->fullHold()));$s=$this->scheduleState($app);$v['schedule']=$s;
                if($s['conflict']!==null){if($this->ignoreSchedule)$v['scheduleOverride']='SCHEDULED_BACKUP_CONFLICT_IGNORED_BY_THE_OWNER_OVERRIDE';else $b[]='SCHEDULED_BACKUP_WITHIN_30_MINUTES_'.strtoupper((string)$s['conflict']['type']).'_WAIT_OR_RUN_WITH_--rollback_--ignore-schedule';}}
        }catch(Throwable $e){$v['appState']=self::code($e);if($strict)$b[]='APP_STATE_UNREADABLE_'.self::code($e);}
        try{$v['census']=$this->census();}catch(Throwable $e){$b[]=self::code($e);}
        $this->emit('ROLLBACK_GATES',['strict'=>$strict,'values'=>$v,'blockers'=>$b]);return $b;
    }

    /** Right before the first change: inside the time budget, the shop state as recorded, no backup due, no route cache, no B1146/B1147 run. */
    private function beforeFirstChange():void
    {
        $elapsed=intdiv(hrtime(true)-(int)$this->runStartNs,1000000000);if($elapsed>$this->budgetSeconds)throw new RuntimeException('TIME_BUDGET_EXCEEDED_BEFORE_FIRST_CHANGE');
        $app=$this->helper($this->appStateScript,['state']);$shop=self::shopOf($app);if($shop!==array_diff_key($this->state['shopRecorded']??[],['writtenByThisTool'=>true]))throw new RuntimeException('SHOP_STATE_CHANGED_BEFORE_FIRST_CHANGE');if(!$shop['open'])throw new RuntimeException('SHOP_IS_OFF_B1155_INSTALLS_ONLY_WITH_THE_SHOP_ON_NOTHING_CHANGED');
        if($this->scheduleGate){$s=$this->scheduleState($app);if($s['conflict']!==null)throw new RuntimeException('SCHEDULED_BACKUP_WITHIN_THE_NEEDED_WINDOW_RECHECK');}
        $this->requireNoRouteCache();$this->earlierIdle();
        $this->emit('BEFORE_FIRST_CHANGE',['elapsedSeconds'=>$elapsed,'budgetSeconds'=>$this->budgetSeconds,'shop'=>$shop,'scheduleRechecked'=>$this->scheduleGate,'routeCache'=>'NONE','b1146OrB1147RunActive'=>false]);
    }

    private function manualNoteIfTouched(array $blockers):void
    {
        try{$touched=$this->touched();}catch(Throwable){$touched=true;}
        if($touched)$this->recoveryNote($blockers,'ROLLBACK_NOT_STARTED_MANUAL_ONLY');
    }

    private function hold2(string $phase,array $report):int {$this->save($phase);$this->emit('HOLD_NO_MUTATION',['phase'=>$phase,'blockers'=>$report['blockers']]);return 2;}

    private function cssNotice():void
    {
        if(($this->state['cssEdgeStale']??false)===true)echo 'OWNER_NOTICE_CSS=CLOUDFLARE_SERVED_AN_EDGE_CACHED_CSS_COPY_DURING_THE_SMOKES_PURGE_EXACTLY_https://cms.ald1n.com'.self::CSS_PATH.'?v='.$this->cssVersion.'_AND_'.self::GALLERY_CSS_PATH.'?v='.$this->galleryVersion.'_AND_THE_BARE_URLS'.PHP_EOL;
        if(($this->state['assetsEdgeStale']??false)===true)echo 'OWNER_NOTICE_SITE_ASSETS=CLOUDFLARE_SERVED_AN_EDGE_CACHED_COPY_OF_THE_LOGO_WITHOUT_THE_NEW_HEADERS_PURGE_https://cms.ald1n.com/site-assets/*'.PHP_EOL;
    }

    private function finish(string $final):int
    {
        $new=count(array_filter($this->plan,static fn($s)=>$s['kind']==='new'));
        $this->final=$final;$this->emit('B1155_RESULT',['overlay'=>'DASHBOARD_DROPDOWNS_REGISTRATION_SUBAGENT_REPORT_REDIRECTS_'.count($this->plan).'_FILES','baseCommit'=>self::BASE_COMMIT,'sourceCommit'=>self::SOURCE_COMMIT,'newFiles'=>$new,'modifiedFiles'=>count($this->plan)-$new,'treeFiles'=>self::installedTreeFiles(),'appUrl'=>'EXACTLY_'.$this->expectedAppUrl.'_VERIFIED_BEFORE_ANY_CHANGE','newRoutes'=>'TWO_GET_ROUTES_'.self::ROUTE_SUBAGENTS.'_AND_'.self::ROUTE_SUBAGENTS_CSV.'_ROUTE_COUNT_'.$this->expectedRoutes.'_TO_'.$this->expectedRoutesAfter,'authenticatedDashboardAndReport'=>'NOT_VERIFIED_BY_THIS_TOOL_NOBODY_LOGS_IN_THE_OWNER_CHECKS_IT','viewsCompiledAndLinted'=>'IN_MEMORY_'.count($this->viewChecks).'_VIEWS','stylesheets'=>'BOTH_CHANGED_ONES_SERVED_200_TEXT_CSS_WITH_THE_PAYLOAD_SHA256_TOKENS_STYLESHEET_UNCHANGED','classesLoadedInTheWebSapi'=>count(self::proofClasses()).'_BEFORE_BOOTSTRAP_AND_ROUTES','catalogCsp'=>'EQUALS_THE_PREFLIGHT_BASELINE','foreignRefererRedirect'=>'PINNED_TO_THE_ORIGIN_CATALOG','guestReportUrls'=>'302_TO_LOGIN_NEVER_5XX','passwordEndpoints'=>'NEVER_CALLED_NO_LOGIN','mail'=>'NEVER_SENT_NO_TEST_MAIL','pdfGeneration'=>'NOT_EXERCISED','settingsWritten'=>'NO','directoriesCreated'=>count(array_filter((array)($this->state['createdDirs']??[]))),'tests'=>'NOT_INSTALLED','shopSwitch'=>'READ_ONLY_NOT_WRITTEN','smtpSettings'=>'NOT_READ_NOT_WRITTEN_NO_TEST_MAIL_ONLY_A_TRANSPORT_FINGERPRINT','routeCache'=>'NONE','migrations'=>'NONE','composer'=>'NONE','env'=>'UNCHANGED','config'=>'UNCHANGED','queue'=>'RESTARTED_VIA_ARTISAN_queue:restart','opcache'=>'TARGETED_PER_FILE_INVALIDATION_ONLY','backupDirectory'=>$this->state['backup']['directory']??'NONE_ALREADY_INSTALLED_BEFORE_THIS_TOOL_STATE']);
        $this->cssNotice();
        $this->emit('OWNER_NEXT_STEPS',array_merge([$this->purgeStep(),
            '2_THIS_TOOL_NEVER_LOGS_IN_SO_THE_DASHBOARD_AND_THE_SUB-AGENT_REPORT_WITH_DATA_WERE_NOT_SEEN_BY_IT_LOG_IN_OPEN_THE_DASHBOARD_ON_THE_DESKTOP_AND_ON_A_PHONE_OPEN_THE_MENUS_OPEN_REPORTS_THEN_THE_SUB-AGENT_REPORT_AND_EXPORT_ITS_CSV_OPEN_THE_PUBLIC_REGISTRATION_PAGE_AND_ONE_PAGE_OF_EVERY_MODULE_TO_SEE_THAT_IT_LOOKS_AS_BEFORE',
            '3_IF_ANYTHING_IS_WRONG_RUN_THE_ROLLBACK_COMMAND_BELOW_THE_SHOP_MAY_STAY_ON',
            '4_THE_SHOP_AND_THE_SMTP_SETTINGS_STAY_AS_YOU_SET_THEM_THIS_TOOL_NEVER_CHANGES_THEM',
            '5_DO_NOT_RERUN_B1144_TO_B1154_OR_THEIR_ROLLBACK'],isset($this->state['backup']['directory'])?['ROLLBACK_IF_NEEDED=bash /home/icaffeco/ald1n-project/incoming/b1155-installer.sh --rollback (THE_SHOP_MAY_STAY_ON)']:['NO_B1155_BACKUP_IN_THIS_STATE_NO_AUTOMATIC_ROLLBACK']));
        $this->emit('DO_NOT_RERUN_B1144_B1145_B1146_B1147_B1148_B1149_B1150_B1151_B1152_B1153_OR_B1154_AFTER_B1155','THEIR_MANIFEST_GATES_NOW_SEE_THE_'.count($this->plan).'_B1155_FILES_AND_THEIR_--rollback_WOULD_REFUSE_FOREIGN_BYTES');return 0;
    }

    private function alreadyInstalled():int
    {
        $p=$this->preflight('installed');if($p['blockers']!==[]){$this->emit('HOLD_INSTALLED_REVERIFY',$p['blockers']);return 2;}
        $this->emit('ALREADY_INSTALLED_NO_REPEAT',['digestsVerified'=>count($this->plan),'treeFiles'=>self::installedTreeFiles()]);return $this->finish('PASS_ALREADY_INSTALLED');
    }

    public function run():int
    {
        $this->runStartNs=hrtime(true);
        try{$this->open();$existing=$this->loadExisting();if(!$existing||($this->mode==='install'&&$this->state['phase']==='ROLLED_BACK'))$this->begin();}catch(Throwable $e){$this->emit('HOLD_BEFORE_ANY_MUTATION',self::code($e));return 2;}
        try{
            $phase=$this->state['phase'];
            if($this->mode==='rollback'){
                if(!in_array($phase,['INSTALLED_PASS','ACCEPTANCE_NOT_VERIFIED','ROLLBACK_INCOMPLETE','ROLLBACK_INTENT','ACCEPTANCE_INTENT','FILES_INSTALLED','INSTALL_INTENT','BACKED_UP'],true)){$this->emit('HOLD_NOTHING_TO_ROLL_BACK',['phase'=>$phase]);return 2;}
                $this->state['chain']??=$this->chain();
                $blockers=$this->rollbackGates(true);
                if($blockers!==[]){$this->emit('HOLD_ROLLBACK_NOT_STARTED',['blockers'=>$blockers,'filesChanged'=>'NONE','next'=>'CLEAR_THE_BLOCKERS_THEN_RUN_THE_SAME_COMMAND']);$this->manualNoteIfTouched($blockers);return 2;}
                if(!$this->restore('OWNER_REQUESTED_ROLLBACK'))return 2;$this->final='PASS_ROLLED_BACK_BY_OWNER';$this->emit('OWNER_NOTICE','THE_B1154_STATE_IS_ACTIVE_AGAIN_A_LATER_RUN_OF_THE_SAME_COMMAND_WITHOUT_OPTIONS_INSTALLS_AGAIN');
                $this->cssNotice();$this->emit('OWNER_NEXT_STEPS',[$this->purgeStep(true),'2_THE_SHOP_STAYS_IN_THE_STATE_YOU_SET_THIS_TOOL_NEVER_CHANGES_IT']);return 0;
            }
            if($phase==='INSTALLED_PASS')return $this->alreadyInstalled();
            if($phase==='ACCEPTANCE_NOT_VERIFIED')return $this->resumeAcceptance();
            if(in_array($phase,self::IN_FLIGHT,true)){
                $this->emit('RESUMED_INTERRUPTED_INSTALL',['phase'=>$phase]);$this->state['chain']??=$this->chain();
                $blockers=$this->rollbackGates(false);if($blockers!==[]){$this->emit('HOLD_RESUMED_ROLLBACK_NOT_STARTED',['blockers'=>$blockers,'filesChanged'=>'NONE','next'=>'RUN_THE_SAME_COMMAND_AGAIN_WHEN_THE_BLOCKERS_ARE_GONE']);$this->manualNoteIfTouched($blockers);return 2;}
                $ok=$this->restore('RESUMED_INTERRUPTED_'.$phase);$this->final=$ok?'HOLD_ROLLED_BACK_OLD_STATE_VERIFIED':'HOLD_ROLLBACK_INCOMPLETE';if($ok)$this->emit('OWNER_NEXT_STEPS',[$this->purgeStep(true)]);return 2;
            }
            if(!in_array($phase,['PREFLIGHT','HOLD_PREFLIGHT_BLOCKED'],true))throw new RuntimeException('UNKNOWN_SAVED_PHASE');
            $this->cleanupRecorded();$p=$this->preflight('fresh');
            if($p['blockers']!==[])return $this->hold2('HOLD_PREFLIGHT_BLOCKED',$p);
            $this->fault('AFTER_PREFLIGHT');
            try{$this->census();$this->beforeFirstChange();}catch(Throwable $e){$p['blockers']=[self::code($e)];return $this->hold2('HOLD_PREFLIGHT_BLOCKED',$p);}
            $this->backup($p);
            try{$this->install();$this->accept();}catch(Throwable $e){return $this->acceptanceFailed($e);}
            return $this->finish(self::FINAL_PASS);
        }catch(Throwable $e){$this->emit('HOLD_CODE_DETAIL',self::code($e));return 2;}
    }

    /**
     * An install or acceptance failure rolls back, except when the acceptance could not be judged (persistent 429, the
     * shop state changed during the checks, the noindex header missing, or a Vary header the edge hides with no origin
     * route) while all files of STEPS hold exactly the payload and every unthrottled check passes: then the correct files stay,
     * the run HOLDs, and the same command later repeats only the acceptance (or --rollback restores the old files).
     */
    private function acceptanceFailed(Throwable $e):int
    {
        $code=self::code($e);$this->emit('INSTALL_OR_ACCEPTANCE_FAILED',$code);
        if(in_array($code,self::UNVERIFIED_CODES,true)&&($this->state['phase']??'')==='ACCEPTANCE_INTENT'){
            try{$exact=$this->targets()['installed']===true;}catch(Throwable){$exact=false;}
            if($exact)try{$this->unthrottledChecks('ACTIVE_TREE_INSTALLED_UNVERIFIED');}catch(Throwable $f){$exact=false;$code=self::code($f);$this->emit('UNTHROTTLED_CHECK_FAILED',$code);}
            if($exact){
                $this->state['unverifiedReason']=$code;$this->save('ACCEPTANCE_NOT_VERIFIED');$this->final='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED';
                $this->emit('HOLD_INSTALLED_NOT_VERIFIED',['reason'=>$code,'files'=>'ALL_'.count($this->plan).'_HOLD_EXACTLY_THE_PAYLOAD','rollback'=>'NOT_STARTED_THE_FILES_ARE_CORRECT','shopSwitch'=>'NOT_WRITTEN']);
                $first=match($code){'SMOKES_RATE_LIMITED_429'=>'1_THE_SITE_ANSWERED_429_TO_THE_CHECKS_WAIT_A_FEW_MINUTES_(LOW_TRAFFIC)',
                    'NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES'=>'1_PUBLIC_PAGES_ARE_SERVED_WITHOUT_X-ROBOTS-TAG_NOINDEX_RESTORE_THE_HEADER_LINE_IN_apps/cms/current/public/.htaccess_OR_THE_CLOUDFLARE_RULE_THAT_REMOVES_IT_(THE_OWNER_POLICY_IS_NO_INDEXING)',
                    'VARY_COOKIE_NOT_VERIFIABLE_EDGE_HIDES_IT_AND_NO_ORIGIN_ROUTE'=>'1_THE_EDGE_HIDES_THE_VARY_HEADER_OF_/katalog_AND_THE_ORIGIN_COULD_NOT_BE_ASKED_DIRECTLY_CHECK_THAT_CLOUDFLARE_DOES_NOT_CACHE_HTML_(CACHE_LEVEL_STANDARD_NO_CACHE_EVERYTHING_RULE)',
                    default=>'1_THE_SHOP_STATE_CHANGED_DURING_THE_CHECKS_TURN_THE_SHOP_ON_AGAIN_IN_THE_ADMIN'};
                $this->emit('OWNER_NEXT_STEPS',[$first,'2_THEN_RUN_THE_SAME_COMMAND_AGAIN_IT_REPEATS_ONLY_THE_ACCEPTANCE_CHECKS_NO_FILE_IS_CHANGED','3_OR_RESTORE_THE_OLD_FILES=bash /home/icaffeco/ald1n-project/incoming/b1155-installer.sh --rollback',$this->purgeStep()]);
                return 2;
            }
        }
        $ok=$this->restore($code);$this->final=$ok?'HOLD_ROLLED_BACK_OLD_STATE_VERIFIED':'HOLD_ROLLBACK_INCOMPLETE';if($ok)$this->emit('OWNER_NEXT_STEPS',[$this->purgeStep(true)]);return 2;
    }

    /** The acceptance checks that need neither a throttled route nor the shop: fresh probes, routes, the CLI feature facts, mail-transport fingerprint, log, tree. */
    private function unthrottledChecks(string $label):void
    {
        $this->probeRun('installed-fresh',true,false,true);$this->routesCheck(true);$this->featureCheck(true);$this->webClassProof('installed','',1);$this->productMediaDoctor('after-install',true);$this->mailFingerprintUnchanged('after-install');
        $scan=LeanGates::newLogErrors($this->active().'/storage/logs',$this->state['logBaseline']);$this->emit('LARAVEL_LOG_SINCE_INSTALL',$scan);if($scan['newErrorLines']!==0)throw new RuntimeException('NEW_LARAVEL_LOG_ERRORS_SINCE_INSTALL');
        $tree=LeanGates::verifyTree($this->active(),$this->installedManifest(),self::CONFIG_SHA,$this->state['chain']['htaccessSHA256'],self::installedTreeFiles());$this->emit($label,$tree+['manifestPlusB1146B1147B1148B1149B1150B1151B1152B1153B1154B1155'=>true]);
    }

    /**
     * ACCEPTANCE_NOT_VERIFIED: the same command repeats only the acceptance, after the same gates as an owner rollback
     * (no CMS artisan run, no backup running or due, no B1146/B1147 run) and with the shop ON as recorded.
     */
    private function resumeAcceptance():int
    {
        $this->emit('RESUMED_ACCEPTANCE_ONLY',['reason'=>$this->state['unverifiedReason']??null]);$this->state['chain']??=$this->chain();
        try{
            $app=$this->helper($this->appStateScript,['state']);if(($app['backups']['table']??null)!==true||($app['backups']['running']??null)!==0)throw new RuntimeException('BACKUP_RUN_IN_PROGRESS_OR_TABLE_MISSING');
            $this->scheduleNeedSeconds=max($this->scheduleNeedSeconds,$this->requiredWindowSeconds($this->fullHold()));
            if($this->scheduleGate){$s=$this->scheduleState($app);if($s['conflict']!==null)throw new RuntimeException('SCHEDULED_BACKUP_WITHIN_THE_NEEDED_WINDOW_WAIT');}
            $this->earlierIdle();$this->census();$this->requireRecordedShop('BEFORE_RESUMED_ACCEPTANCE');
            $this->state['logBaseline']=LeanGates::logBaseline($this->active().'/storage/logs');$this->persist();
        }catch(Throwable $e){$this->emit('HOLD_ACCEPTANCE_NOT_RESUMED',['blockers'=>[self::code($e)],'filesChanged'=>'NONE','next'=>'TURN_THE_SHOP_ON_AS_BEFORE_OR_WAIT_FOR_THE_ARTISAN_RUN_THEN_RUN_THE_SAME_COMMAND_OR_USE_--rollback']);$this->final='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED';return 2;}
        if($this->targets()['installed']!==true){$ok=$this->restore('RESUMED_ACCEPTANCE_FILES_NOT_EXACT');$this->final=$ok?'HOLD_ROLLED_BACK_OLD_STATE_VERIFIED':'HOLD_ROLLBACK_INCOMPLETE';return 2;}
        try{$this->accept();}catch(Throwable $e){return $this->acceptanceFailed($e);}
        return $this->finish(self::FINAL_PASS);
    }
}

/** Same-directory temp + fsync + rename (replace) or temp + fsync + link (create, never clobbers). Mode and group are kept. */
final class DashboardReportsFiles
{
    public static function tempFor(string $target):string {return dirname($target).'/.'.basename($target).'.b1155-'.bin2hex(random_bytes(8)).'.tmp';}
    public static function isOwnTemp(string $path):bool {return preg_match('~/\.[A-Za-z0-9_.-]+\.b1155-[a-f0-9]{16}\.tmp$~D',$path)===1;}

    public static function replaceable(string $target):array {return AtomicFile::replaceable($target);}

    private static function writeTemp(string $temp,string $bytes,int $mode):void
    {
        $h=@fopen($temp,'x');if($h===false)throw new RuntimeException('OVERLAY_TEMP_CREATE');$ok=false;
        try{if(!chmod($temp,$mode)||fwrite($h,$bytes)!==strlen($bytes)||!fflush($h)||(function_exists('fsync')&&!fsync($h)))throw new RuntimeException('OVERLAY_TEMP_WRITE');$ok=true;}finally{fclose($h);if(!$ok)@unlink($temp);}
        clearstatcache(true,$temp);if(is_link($temp)||hash_file('sha256',$temp)!==hash('sha256',$bytes)||(fileperms($temp)&0777)!==$mode){@unlink($temp);throw new RuntimeException('OVERLAY_TEMP_VERIFY');}
    }

    private static function syncDirectory(string $directory):bool {$d=@fopen($directory,'r');if($d===false)return false;$ok=function_exists('fsync')&&@fsync($d);fclose($d);return $ok;}

    /** Replaces $target only while it still holds $expectedSHA256; the result gets an mtime newer than the replaced file. */
    public static function replace(string $target,string $bytes,int $mode,int $gid,string $temp,string $expectedSHA256):array
    {
        if(!self::isOwnTemp($temp)||dirname($temp)!==dirname($target))throw new RuntimeException('OVERLAY_REPLACE_SCOPE');$facts=AtomicFile::replaceable($target);clearstatcache(true,$target);$before=stat($target);
        if(hash_file('sha256',$target)!==$expectedSHA256)throw new RuntimeException('OVERLAY_TARGET_CHANGED_BEFORE_RENAME');self::writeTemp($temp,$bytes,$mode);
        if(stat($temp)['gid']!==$gid)@chgrp($temp,$gid);clearstatcache(true,$temp);if(stat($temp)['gid']!==$gid){@unlink($temp);throw new RuntimeException('OVERLAY_GID_NOT_PRESERVABLE');}
        if(filemtime($temp)<=$before['mtime']&&!touch($temp,$before['mtime']+1)){@unlink($temp);throw new RuntimeException('OVERLAY_TEMP_MTIME');}
        if(hash_file('sha256',$target)!==$expectedSHA256){@unlink($temp);throw new RuntimeException('OVERLAY_TARGET_CHANGED_BEFORE_RENAME');}
        if(!@rename($temp,$target)){@unlink($temp);throw new RuntimeException('OVERLAY_RENAME');}$synced=self::syncDirectory(dirname($target));
        clearstatcache(true,$target);$after=stat($target);if(is_link($target)||hash_file('sha256',$target)!==hash('sha256',$bytes)||($after['mode']&0777)!==$mode||$after['gid']!==$gid)throw new RuntimeException('OVERLAY_RESULT_VERIFY');
        return ['op'=>'RENAME_OVER','directorySynced'=>$synced,'mode'=>sprintf('%04o',$mode),'gidPreserved'=>true,'nlinkBefore'=>$facts['nlink'],'aclFreeBefore'=>true,'mtimeAdvanced'=>$after['mtime']>$before['mtime']];
    }

    /** Creates $target from a synced temp with link(): it fails if the name exists, so nothing is ever clobbered. */
    public static function create(string $target,string $bytes,int $mode,string $temp,int $gid):array
    {
        if(!self::isOwnTemp($temp)||dirname($temp)!==dirname($target)||is_link(dirname($target)))throw new RuntimeException('OVERLAY_CREATE_SCOPE');clearstatcache(true,$target);if(file_exists($target)||is_link($target))throw new RuntimeException('OVERLAY_NEW_TARGET_EXISTS');
        self::writeTemp($temp,$bytes,$mode);if(stat($temp)['gid']!==$gid)@chgrp($temp,$gid);clearstatcache(true,$temp);if(stat($temp)['gid']!==$gid){@unlink($temp);throw new RuntimeException('OVERLAY_GID_NOT_PRESERVABLE');}if(!@link($temp,$target)){@unlink($temp);throw new RuntimeException(file_exists($target)?'OVERLAY_NEW_TARGET_EXISTS':'OVERLAY_LINK');}@unlink($temp);$synced=self::syncDirectory(dirname($target));
        clearstatcache(true,$target);if(is_link($target)||!is_file($target)||hash_file('sha256',$target)!==hash('sha256',$bytes)||stat($target)['nlink']!==1)throw new RuntimeException('OVERLAY_RESULT_VERIFY');
        return ['op'=>'LINK_NEW','directorySynced'=>$synced,'mode'=>sprintf('%04o',$mode),'gid'=>$gid];
    }

    /** Removes $target only while it holds exactly $sha256. */
    public static function removeExact(string $target,string $sha256):void
    {
        clearstatcache(true,$target);if(is_link($target)||!is_file($target)||hash_file('sha256',$target)!==$sha256)throw new RuntimeException('OVERLAY_REMOVE_FOREIGN_CONTENT');if(!unlink($target))throw new RuntimeException('OVERLAY_REMOVE');self::syncDirectory(dirname($target));
    }
}

/**
 * The HTTP client of B1155. Every request the earlier tools made goes through the pinned B1153 client (LogoCatalogWeb, which wraps MediaWeb; both are delivered by the owner's pinned
 * b1153-coordinator.php and b1150 files). That client refuses one request this payload must be checked with: GET /katalog?stock=x with a foreign Referer header (its query allowlist has no
 * stock key, its header allowlist only If-None-Match). This class adds exactly that one request, fixed in every byte: GET only, https://cms.ald1n.com only, the path /katalog, the query stock=x
 * plus the release marker, the single header Referer: https://evil.example/x, no body, no redirect is ever followed, the same TLS options and body limit as MediaWeb. Anything else is delegated.
 * The probe (the token-gated temporary probe file) is delegated unchanged. The CI fixture replaces the whole client ($web) with its own double, which must honour the same call.
 * @author Ald1n <https://ald1n.com>
 */
final class DashboardReportsWeb
{
    private LogoCatalogWeb $inner;
    public function __construct(){$this->inner=new LogoCatalogWeb();}

    public function fetch(string $path,array $query,?string $marker,string $method='GET',bool $origin=false,array $headers=[]):array
    {
        $referer='Referer: '.DashboardReportsInstallCoordinator::FOREIGN_REFERER;
        if($headers!==[$referer]||$path!==DashboardReportsInstallCoordinator::CATALOG_PATH||$query!==['stock'=>'x']||$method!=='GET'||$origin||$marker===null)return $this->inner->fetch($path,$query,$marker,$method,$origin,$headers);
        if(preg_match('/^[a-f0-9]{1,32}$/D',$marker)!==1)throw new RuntimeException('SMOKE_PATH');
        $h=MediaWeb::headers();$body='';$over=false;
        $curl=curl_init('https://cms.ald1n.com'.$path.'?'.http_build_query(['stock'=>'x','ald1n_release_check'=>$marker],'','&',PHP_QUERY_RFC3986));
        curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_CONNECTTIMEOUT=>6,CURLOPT_TIMEOUT=>25,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_NOPROXY=>'*',CURLOPT_USERAGENT=>'Ald1n-B1155-owned-release',CURLOPT_HTTPHEADER=>['Host: cms.ald1n.com','Cache-Control: no-cache',$referer],
            CURLOPT_HEADERFUNCTION=>static function($c,string $line)use(&$h):int{MediaWeb::header($h,$line);if(preg_match('/^CF-RAY:/i',$line))$h['cfRay']=true;return strlen($line);},
            CURLOPT_WRITEFUNCTION=>static function($c,string $chunk)use(&$body,&$over):int{if(strlen($body)+strlen($chunk)>MediaWeb::MAX_BODY){$over=true;return 0;}$body.=$chunk;return strlen($chunk);}]);
        curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);curl_close($curl);
        return MediaWeb::result($error!==0||$over,$status,$h,$body);
    }

    public function probe(string $name,string $token,bool $invalidate,?string $route=null):array {return $this->inner->probe($name,$token,$invalidate,$route);}

    /** The fast liveness request after group D: GET https://cms.ald1n.com<path> plus the release marker, total timeout $timeout seconds, no redirect followed, the body is discarded. */
    public function fastGet(string $path,string $marker,int $timeout):array
    {
        if(preg_match('~^/[A-Za-z0-9/_-]{1,40}$~D',$path)!==1||preg_match('/^[a-f0-9]{1,32}$/D',$marker)!==1||$timeout<1||$timeout>10)throw new RuntimeException('SMOKE_PATH');
        $curl=curl_init('https://cms.ald1n.com'.$path.'?'.http_build_query(['ald1n_release_check'=>$marker],'','&',PHP_QUERY_RFC3986));
        curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_CONNECTTIMEOUT=>min(4,$timeout),CURLOPT_TIMEOUT=>$timeout,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_NOPROXY=>'*',CURLOPT_USERAGENT=>'Ald1n-B1155-owned-release',CURLOPT_HTTPHEADER=>['Host: cms.ald1n.com','Cache-Control: no-cache']]);
        curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);curl_close($curl);
        return ['status'=>$status,'transport'=>$error!==0||$status===0];
    }
}
