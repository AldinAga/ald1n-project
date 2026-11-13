<?php
declare(strict_types=1);

/**
 * B1152 APP_URL origin pin and PDF logo raster guard: ships nine reviewed files over the live tree, which is the manifest + B1146 +
 * B1147 + B1148 + B1149 + B1150 + B1151 (8567 files, equals apps/cms/current of BASE_COMMIT 7e22afd4 without tests); tests, CI and
 * docs excluded. Payload: every absolute URL generated during a web or API request starts with the origin of APP_URL instead of the
 * Host header, and the password-reset mail link is built from APP_URL (head URL_COMMIT, PR 171, stacked on PR 168), and the PDF
 * layout draws only a JPEG or PNG logo (by content signature) while the logo preview links never fall back to a dead /storage URL
 * (head PDF_COMMIT, PR 170). No route, no migration, no composer change, no .env, no config change. The Storefront is OPEN in
 * production: its state is recorded, re-read before the first change and around every smoke batch, and never written. SMTP
 * settings are never read for their values, written or tested, no mail is sent (and no password reset is requested); only a hashed
 * fingerprint of the effective mail transport is compared before and after. Password endpoints are never called, nobody logs in.
 * Order: PREFLIGHT (read-only, collect-all; any blocker is a HOLD with nothing changed; Report1117 and the B1151 state, APP_URL
 * exactly https://cms.ald1n.com, PHP extensions, backups, digest check of every modified file) -> per-file backup into the private
 * B1152 namespace -> install in the reviewed dependency order, one group at a time with a revalidation pause between. Group
 * letters of this rollout: A the two new classes AppUrlOrigin and PinAppUrlOrigin (nothing names them yet), B the password-reset
 * notification and the web account controller, C the four PDF-logo files (independent, back to back), D bootstrap/app.php LAST:
 * it names PinAppUrlOrigin::class, so if it were live before both classes load every request (including /up) would be a 500. Before
 * D the full measured hold elapses (it always spans group A), and a probe served by the WEB SAPI (not only the CLI) proves that both
 * classes load through the very autoloader the app uses and that both files are fresh; D is refused without that proof. After D an
 * immediate liveness smoke (/up, /login) runs and a failure rolls D back first. Each file via same-directory temp + fsync +
 * rename (link for a new file, so nothing is ever clobbered) -> route:clear, view:clear, queue:restart (never a cache build) ->
 * targeted per-file opcache_invalidate through token-gated temporary probes (the pinned probe takes at most 40 targets per
 * request, larger plans are split) -> wait max(realpath_cache_ttl, revalidate_freq) + 5 s -> smokes, fresh-code probe, CLI feature
 * check, mail-transport fingerprint, log scan, full tree verification.
 * Any failure after the first file restores the backed-up bytes of all files in exact reverse install order (stopping at the first
 * failed restore, so the tree is always an exact install prefix): D first (old bootstrap/app.php), then the full measured hold
 * BEFORE anything else goes, short pauses between C and B, the full hold again before group A (the two classes are removed last);
 * it re-invalidates and smoke-tests the old state. A re-run converges from the saved phase.
 * ONE place holds the payload: the block marked B1152_PAYLOAD below (BASE_COMMIT, MAIN_COMMIT, URL_COMMIT, PDF_COMMIT, STEPS and DIGESTS).
 * @author Ald1n <https://ald1n.com>
 */
final class OriginPinInstallCoordinator
{
    public const MANIFEST_SHA='d737387e60a981b3b404e0980d9b70cb95b6e1d506a39211981d49dcd12be4c6';
    public const MANIFEST_FILES=8531;
    /** The tree after B1146 (manifest + 12 new), after B1147 (+ 3), after B1148 (+ 2), after B1149 (+ 16 new), after B1150 (+ 2 new), after B1151 (+ 1 new); this rollout adds the new files of STEPS. */
    public const B1146_TREE_FILES=8543;
    public const B1147_TREE_FILES=8546;
    public const B1148_TREE_FILES=8548;
    public const B1149_TREE_FILES=8564;
    public const B1150_TREE_FILES=8566;
    public const B1151_TREE_FILES=8567;
    public const B1146_FILES=21;
    public const B1147_FILES=8;
    public const B1148_FILES=11;
    public const B1149_FILES=43;
    public const B1150_FILES=5;
    public const B1151_FILES=6;
    /** config/public_storefront.php after the B1145 unclamp: the staged original bytes. */
    public const CONFIG_SHA='0cfbc864a3d44822a9d14e80f6252e84eb208c9da5a25d5fe6989e4cad789818';
    /** Report 1117 is the B1151 PASS (state id bce891b87709f9f0a8f722c55c50de69); an earlier report of the same tool never matches this pattern. */
    public const B1151_REPORT_PATTERN='~^1117-B1151-INSTALLER-\d{8}-\d{6}\.md$~D';
    public const B1151_PASS='PASS_INSTALLED_LEGAL_EMAIL_MERCHANT_DRAFT_PASSWORD_THROTTLE_SHOP_STATE_UNCHANGED';
    /**
     * The production facts of the B1151 install this rollout is chained to (Report1117, read on 13.11.2026): the SHA-256 of the report file as it
     * sits on the host (no CR byte in it; archived byte for byte in docs/operations) and the id of the B1151 state directory. A different report or
     * state is a HOLD with nothing changed. The chain gate compares the host file with this digest, so the digest is only as good as the local copy.
     */
    public const B1151_REPORT_SHA256='4787c4b3b2901ca3fc58a5e87a86c5b402dcc769235d872923b53ab907b5f471';
    public const B1151_STATE_ID='bce891b87709f9f0a8f722c55c50de69';
    public const FINAL_PASS='PASS_INSTALLED_APP_URL_ORIGIN_PIN_PDF_LOGO_RASTER_SHOP_STATE_UNCHANGED';
    public const RAN=71;
    /** The route count B1151 left (Report1117 ROUTES_CHECK routeCount); this rollout adds no route, so it must be the same before and after. */
    public const ROUTES=568;
    /** Every generated URL is pinned to this origin: the preflight HOLDs unless config('app.url') is exactly this string (no trailing slash). */
    public const EXPECTED_APP_URL='https://cms.ald1n.com';
    // ===== B1152_PAYLOAD (begin): the only place that names the payload heads, the install order and the digests. package.php and the CI gate read it. =====
    /** The pre-state commit: the code base 7e22afd4 both heads descend from; its apps/cms/current (without tests) equals the live tree after B1151 (asserted at build time, byte for byte per file). */
    public const BASE_COMMIT='7e22afd4b7cde338d5198d9e1ad0e2e3c72f1d9a';
    /** origin/main at the time of writing (Integration PR 167): production code equals main, its apps/cms/current equals BASE_COMMIT's (asserted at build time). */
    public const MAIN_COMMIT='fc954517a5bfafe0ab6029f938aa956ca27c9575';
    /** PR 171 (codex/url-origin-pin-20261113, stacked on PR 168 head b49e33c5): the two new classes, bootstrap/app.php, the reset notification and the web account controller. Source key U in STEPS. */
    public const URL_COMMIT='8505b11064589ea5514944fea954a6d5ef8acdb1';
    /** PR 170 (codex/pdf-logo-raster-fallback-20261113): the PDF layout, the site logo service and the two document-settings controllers. Source key P in STEPS. */
    public const PDF_COMMIT='cfe97253bf4c441b06d2ea6194e2180bf4e23f98';
    /**
     * The install order, [path under apps/cms/current, 'new'|'modified', group, source key U|P]. Groups of this rollout: A the two new classes (nothing names
     * them in the old tree); B the password-reset notification and the web account controller (independent of everything else); C the four PDF-logo files,
     * back to back (independent); D bootstrap/app.php LAST (it names PinAppUrlOrigin::class). Groups are non-decreasing in this list (plan() refuses anything
     * else), the two new classes are in a lower group than bootstrap/app.php, every path is under apps/cms/current, none under tests/, database/, config/,
     * routes/ or composer.*.
     */
    public const STEPS=[
        ['app/Support/AppUrlOrigin.php','new','A','U'],['app/Http/Middleware/PinAppUrlOrigin.php','new','A','U'],
        ['app/Notifications/ResetPasswordNotification.php','modified','B','U'],['app/Http/Controllers/AccountController.php','modified','B','U'],
        ['app/Services/Pdf/FlowPdfLayout.php','modified','C','P'],['app/Services/SiteLogoAssetService.php','modified','C','P'],['app/Http/Controllers/Admin/DocumentSettingsController.php','modified','C','P'],['app/Http/Controllers/Api/V1/Admin/SystemSettingsController.php','modified','C','P'],
        ['bootstrap/app.php','modified','D','U'],
    ];
    /** The reviewed digests (sha256 of the raw git blob bytes): pre = the live bytes (null for a new file), payload = the bytes shipped. plan() and package.php both refuse any other value. */
    public const DIGESTS=[
        'app/Support/AppUrlOrigin.php'=>[null,'3de284868251a100609ea8126d663993f6e690ed29b3a3c454fee725a9f265b2'],
        'app/Http/Middleware/PinAppUrlOrigin.php'=>[null,'9fe967b6be15313b61d2737bbc87835b3832756096ac3660a4aab5e554a404dd'],
        'app/Notifications/ResetPasswordNotification.php'=>['1c075af5c7b26e3a399b7eb067f84474f15720de784411b98cbd69e55933020d','a1463172fa681adbf44d8d5470b355b4db7a1ff41a53257c04735972c2daacb9'],
        'app/Http/Controllers/AccountController.php'=>['c52e379ae38ae2a83ad6eb0e7ff1abe18177836a82fb292242b095d5ea26a2b6','003860a7c79ac08e67ba398b6bf22ff5c6dbabe666b9894db1825fd084a39ff2'],
        'app/Services/Pdf/FlowPdfLayout.php'=>['645296bae8bd45b537bb76ec22ca98c777dc90a6407cabbd5979251668c994c9','04731f17cf28336e66d525220f4e6e53508f6a0d6af01d2d1fe2261f54f4011a'],
        'app/Services/SiteLogoAssetService.php'=>['ca1cfe6640958b8f7d4c389efbed87e2f467fb54cb9e013c74a5a2259fce4b12','f3801a565a32654b60b05bb77e47ca869d2b62be4e4b77bec5a9d5975b54274a'],
        'app/Http/Controllers/Admin/DocumentSettingsController.php'=>['b6e98e2c719daab5deb35f23b6ad9db652c9469b3f4d3f7d6226e3179a03af4a','56e9ddf808a8a779df46165df6b32200f33ad4bd0188a09e213468cfed229ead'],
        'app/Http/Controllers/Api/V1/Admin/SystemSettingsController.php'=>['5e943ee8cff8fe1cb8c7609642155297328b3bc5930c0bbfe9ca790803657133','7e104c0dd5a50474a9530a3d1ee6bab065470bcff7b2aaf1a45837e5aec90091'],
        'bootstrap/app.php'=>['fcdd42940bc8534c285d92e0ac960a3c795b82c8923477c18fd60d15dc0bab4e','38ba2b88403e6df71fd1b2daacd93071ded7fd286ecf47c4d51374429e031923'],
    ];
    // ===== B1152_PAYLOAD (end) =====
    /** The file that names the middleware and so must be installed last, and its group: the full measured hold and the web-SAPI class proof come before it. */
    public const BOOTSTRAP_PATH='bootstrap/app.php';
    public const BOOTSTRAP_GROUP='D';
    /** The middleware bootstrap/app.php registers globally. */
    public const PIN_MIDDLEWARE='App\Http\Middleware\PinAppUrlOrigin';
    /**
     * Source-level facts of the payload edits that the CLI helper reads from the live files (key => [path, needle]): present in every payload file, absent from its
     * pre-state bytes (package.php asserts both). Installed: all true; old: all false.
     */
    public const SOURCE_MARKERS=[
        'resetUrlFromAppUrl'=>['app/Notifications/ResetPasswordNotification.php',"rtrim((string) config('app.url'), '/')"],
        'currentPasswordCapped'=>['app/Http/Controllers/AccountController.php',"'max:4096'"],
        'siteLogoRouteOnly'=>['app/Services/SiteLogoAssetService.php','Only the site-assets route: /storage does not resolve at the origin.'],
        'documentLogoNoStorageFallback'=>['app/Http/Controllers/Admin/DocumentSettingsController.php','A missing or unroutable file gets no preview instead of a dead /storage link.'],
        'systemSettingsLogoNoStorageFallback'=>['app/Http/Controllers/Api/V1/Admin/SystemSettingsController.php','->assetUrl($path); } catch (Throwable) { $logoUrl = null; }'],
    ];
    /** The commit a STEPS entry is taken from. */
    public static function sourceCommit(string $key):string
    {
        return match($key){'U'=>self::URL_COMMIT,'P'=>self::PDF_COMMIT,default=>throw new RuntimeException('OVERLAY_STEPS_SOURCE')};
    }
    /** The B1151 payload heads (installed by Report1117) and the B1150 payload head (Report1116) they were built over; the B1149 payload head (Report1115) and the commit pair it was built over. */
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
    /** The B1149 layout/CSS/gallery/catalog markers: they stay in the tree through this rollout, so they hold in BOTH states. */
    public const MARKERS=['layout'=>'aria-label="Tema"','css'=>'@media (prefers-contrast:more){.shop-theme-option','gallery'=>'.shop-ratio-r34','catalog'=>'ald1n-public-gallery.css'];

    /** The classes the new app/*.php files of STEPS define, by PSR-4 (App\ is app/): the CLI feature check and the web-SAPI probe load exactly these. */
    public static function newClasses():array
    {
        $out=[];foreach(self::STEPS as [$p,$kind]){if($kind==='new'&&str_starts_with($p,'app/')&&str_ends_with($p,'.php'))$out[]='App\\'.str_replace('/','\\',substr($p,4,-4));}return $out;
    }

    /** Files in the tree after this rollout: the B1151 tree plus the new files of STEPS (derived, never typed twice). */
    public static function installedTreeFiles():int
    {
        $new=0;foreach(self::STEPS as [,$kind])if($kind==='new')$new++;return self::B1151_TREE_FILES+$new;
    }
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
    /** Production defaults (Report1117, B1151 state id, route count, APP_URL); the CI fixture sets its own measured values as explicit doubles. */
    private string $b1151ReportSHA256=self::B1151_REPORT_SHA256;
    private string $b1151StateId=self::B1151_STATE_ID;
    private int $expectedRoutes=self::ROUTES;
    private string $expectedAppUrl=self::EXPECTED_APP_URL;
    /** The e-mail obfuscation counts of the last smokes() call (facts only; the old-state ones are kept in the saved state). */
    private ?array $emailFacts=null;
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
     * page, /tema; plus the origin-pin redirect check after D, so 7 at most), each up to the 25 s curl timeout plus the 0.7 s pacing, no 429 waiting: 7 x 25.7 = 180 s.
     */
    private const LIVENESS_SECONDS=180;
    /** The web-SAPI class proof: this many separate probe requests, all must pass; each is bounded by the 25 s timeout of the pinned client plus the probe file handling. */
    private const WEB_PROOF_REQUESTS=5;
    private const WEB_PROOF_SECONDS=30;
    /** Upper bound of the requests in one smoke batch: 2 system, 8 legal, 4 CSS, 2 /tema, 2 guest admin pages, 1 logo, 12 shop, 1 origin Vary, spare. */
    private const SMOKE_GETS=42;
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
    private array $markers;
    private string $cssVersion;
    private string $oldCssVersion;
    private string $galleryVersion;
    public string $final='HOLD';

    public function __construct(private string $root,private string $home,array $package,private string $incoming,private string $mode='install',private bool $ignoreSchedule=false)
    {
        if($ignoreSchedule&&$mode!=='rollback')throw new RuntimeException('OPERATOR_SCHEDULE_OVERRIDE');
        if($root!=='/home/icaffeco/ald1n-project'||$home!=='/home/icaffeco'||realpath($root)!==$root||realpath($home)!==$home||!function_exists('posix_geteuid')||!extension_loaded('curl')||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('HOST_PROFILE');
        $this->base=$home.'/.ald1n-b1152-state';$this->pointer=$this->base.'/current.json';$this->stage=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU';$this->b1146Base=$home.'/.ald1n-b1146-state';$this->b1147Base=$home.'/.ald1n-b1147-state';$this->b1148Base=$home.'/.ald1n-b1148-state';$this->b1149Base=$home.'/.ald1n-b1149-state';$this->b1150Base=$home.'/.ald1n-b1150-state';$this->b1151Base=$home.'/.ald1n-b1151-state';
        $this->appStateScript=$incoming.'/b1145-app-state.php';$this->routesScript=$incoming.'/b1146-app-check.php';$this->featureScript=$incoming.'/b1152-app-check.php';$this->web=new MediaWeb();
        $this->load($package,$incoming);putenv('GIT_OPTIONAL_LOCKS=0');
        if(!in_array($mode,['install','rollback'],true))throw new RuntimeException('OPERATOR_MODE');
    }

    private function load(array $package,string $incoming):void
    {
        $this->plan=self::plan($package,$incoming);$this->b1146=self::b1146Plan($package);$this->b1147=self::b1147Plan($package);$this->b1148=self::b1148Plan($package);$this->b1149=self::b1149Plan($package);$this->b1150=self::b1150Plan($package);$this->b1151=self::b1151Plan($package);$this->markers=self::markers($package);
        foreach(['cssVersion','oldCssVersion','galleryVersion'] as $k){$v=$package[$k]??null;if(!is_string($v)||!preg_match('/^[0-9]{1,12}$/D',$v))throw new RuntimeException('CSS_VERSION_SCOPE');$this->$k=$v;}
    }

    /** The overlay plan from b1152-package.json must be exactly STEPS (paths, kinds, groups, order) with digests and a payload. */
    public static function plan(array $package,string $incoming):array
    {
        if(self::STEPS===[]||self::URL_COMMIT===self::PDF_COMMIT||self::BASE_COMMIT===self::URL_COMMIT||self::BASE_COMMIT===self::PDF_COMMIT||self::BASE_COMMIT===self::MAIN_COMMIT)throw new RuntimeException('B1152_PAYLOAD_NOT_FILLED_IN');
        foreach([self::BASE_COMMIT,self::MAIN_COMMIT,self::URL_COMMIT,self::PDF_COMMIT] as $c)if(preg_match('/^[a-f0-9]{40}$/D',$c)!==1)throw new RuntimeException('B1152_PAYLOAD_NOT_FILLED_IN');
        $last='';$seen=[];foreach(self::STEPS as [$p,$k,$g,$src]){if(!in_array($k,['new','modified'],true)||!preg_match('/^[A-D]$/D',$g)||$g<$last||!in_array($src,['U','P'],true)||isset($seen[$p])||str_starts_with($p,'tests/')||str_starts_with($p,'bin/')||preg_match('~^(database/|config/|routes/|composer\.|\.env)~',$p)===1)throw new RuntimeException('OVERLAY_STEPS_SCOPE');$last=$g;$seen[$p]=true;}
        // The order invariant of this rollout: bootstrap/app.php names PinAppUrlOrigin::class, so it is the one step of the LAST group and every new class sits in a LOWER group.
        $boot=null;$newTop='';foreach(self::STEPS as [$p,$k,$g]){if($p===self::BOOTSTRAP_PATH)$boot=$g;if($k==='new'&&$g>$newTop)$newTop=$g;}
        if($boot!==self::BOOTSTRAP_GROUP||count(array_filter(self::STEPS,static fn($s)=>$s[2]===self::BOOTSTRAP_GROUP))!==1||$newTop===''||$newTop>=$boot||self::STEPS[count(self::STEPS)-1][0]!==self::BOOTSTRAP_PATH)throw new RuntimeException('OVERLAY_BOOTSTRAP_NOT_LAST_AFTER_THE_CLASSES');
        // The reviewed digests are coded once more here, so a package built over other bytes is refused on the host as well.
        if(array_keys(self::DIGESTS)!==array_map(static fn($s)=>$s[0],self::STEPS))throw new RuntimeException('OVERLAY_DIGESTS_NOT_THE_STEPS');
        $overlay=$package['overlay']??null;if(!is_array($overlay)||count($overlay)!==count(self::STEPS)||($package['urlCommit']??null)!==self::URL_COMMIT||($package['pdfCommit']??null)!==self::PDF_COMMIT||($package['baseCommit']??null)!==self::BASE_COMMIT||($package['mainCommit']??null)!==self::MAIN_COMMIT)throw new RuntimeException('OVERLAY_PLAN_SCOPE');$plan=[];
        foreach(array_values($overlay) as $i=>$e){
            [$path,$kind,$group,$src]=self::STEPS[$i];
            if(!is_array($e)||($e['path']??null)!==$path||($e['kind']??null)!==$kind||($e['group']??null)!==$group||($e['source']??null)!==self::sourceCommit($src)||!preg_match('/^b1152-payload-\d{2}\.bin$/D',(string)($e['payload']??''))||!preg_match('/^[a-f0-9]{64}$/D',(string)($e['payloadSHA256']??''))||!is_int($e['size']??null))throw new RuntimeException('OVERLAY_PLAN_ENTRY');
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

    /** The B1149 markers (layout, css, gallery, catalog): they are in the tree before and after this rollout (package.php asserts them in the B1149 bytes). */
    public static function markers(array $package):array
    {
        $m=$package['markers']??null;if(!is_array($m))throw new RuntimeException('MARKER_SCOPE');$out=[];
        foreach(['layout','css','gallery','catalog'] as $k){if(!is_string($m[$k]??null)||!preg_match('/^[A-Za-z0-9 ="._\[\]:()@,{}-]{6,80}$/D',$m[$k])||$m[$k]!==self::MARKERS[$k])throw new RuntimeException('MARKER_SCOPE');$out[$k]=$m[$k];}
        return $out;
    }

    /**
     * No Cloudflare change and no purge is needed: no route is added, the public shop pages send Cache-Control private, no-store and the
     * stylesheet URL stays the same (no layout, view or CSS file is in this payload). Cloudflare settings are not touched.
     * Only when the payload changed the storefront CSS URL (cssVersion differs from oldCssVersion) the old CSS URL is named after a rollback.
     */
    public function purgeStep(bool $rollback=false):string
    {
        $u='https://cms.ald1n.com'.self::CSS_PATH;
        if($rollback&&$this->cssVersion!==$this->oldCssVersion)return '1_CLOUDFLARE_PURGE_EXACTLY_'.$u.'?v='.$this->oldCssVersion.'_AND_'.$u.'_(THE_OLD_LAYOUT_LINKS_?v='.$this->oldCssVersion.'_WHICH_MAY_HAVE_CACHED_THE_NEW_CSS_WHILE_THE_FILES_WERE_CHANGING)';
        if($rollback)return '1_NO_CLOUDFLARE_PURGE_NEEDED_THE_STOREFRONT_CSS_URL_DID_NOT_CHANGE';
        return '1_NO_CLOUDFLARE_CHANGE_AND_NO_PURGE_NEEDED_(THE_SHOP_PAGES_SEND_Cache-Control_private_no-store_AND_THE_CSS_URL_DID_NOT_CHANGE)_DO_NOT_CHANGE_ANY_CLOUDFLARE_SETTING_DURING_THE_INSTALLATION';
    }

    private function open():void
    {
        if($this->lock!==null)return;if(!is_dir($this->base)&&!mkdir($this->base,0700))throw new RuntimeException('PRIVATE_STATE_CREATE');
        if(is_link($this->base)||realpath($this->base)!==$this->base||(fileperms($this->base)&0777)!==0700||fileowner($this->base)!==posix_geteuid())throw new RuntimeException('PRIVATE_STATE_CONFINEMENT');
        $lock=fopen($this->base.'/operator.lock','c');if($lock===false||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('ANOTHER_B1152_RUN_ACTIVE');chmod($this->base.'/operator.lock',0600);$this->lock=$lock;
    }

    /** No B1146, B1147, B1148, B1149, B1150 or B1151 run holds its operator lock (read-only: each lock file is opened for reading and only probed). */
    private function earlierIdle():void
    {
        foreach(['B1146'=>$this->b1146Base,'B1147'=>$this->b1147Base,'B1148'=>$this->b1148Base,'B1149'=>$this->b1149Base,'B1150'=>$this->b1150Base,'B1151'=>$this->b1151Base] as $label=>$dir){
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
        $this->atomicJSON($this->pointer,['schemaVersion'=>1,'kind'=>'B1152','private'=>$this->state['private'],'id'=>$this->state['id']]);$this->emit('B1152_PHASE',$phase);$this->fault('SAVED_'.$phase);
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

    /** One read-only helper (b1145 state, b1146 routes/approval, b1152 features/environment); JSON facts only. */
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

    /** The live tree before this rollout: the 8566-file tree with the 6 B1151 files (8567 entries, Report1117). */
    private function b1151Manifest():array
    {
        $files=self::applied($this->b1150Manifest()['files'],array_values($this->b1151),'B1151_MANIFEST');if(count($files)!==self::B1151_TREE_FILES)throw new RuntimeException('B1151_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** The tree after this rollout (8567 + the new files of STEPS). */
    private function installedManifest():array
    {
        $changes=array_map(static fn($s)=>['path'=>$s['path'],'kind'=>$s['kind'],'sha256'=>$s['payloadSHA256'],'preSHA256'=>$s['preSHA256']],$this->plan);
        $files=self::applied($this->b1151Manifest()['files'],$changes,'B1152_MANIFEST');if(count($files)!==self::installedTreeFiles())throw new RuntimeException('B1152_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** Last value of every KEY=value line of the B1151 terminal report (Report1117). */
    public static function b1151Facts(string $bytes):array
    {
        if(str_contains($bytes,"\r")||strlen($bytes)>4194304)throw new RuntimeException('B1151_REPORT_FORMAT');$last=[];
        foreach(explode("\n",$bytes) as $line)if(preg_match('/^([A-Z0-9_]+)=(.*)$/D',$line,$m))$last[$m[1]]=$m[2];
        if(($last['B1151_FINAL']??null)!==self::B1151_PASS||($last['EXIT']??null)!=='0'||json_decode($last['B1151_PHASE']??'null',true)!=='INSTALLED_PASS')throw new RuntimeException('B1151_REPORT_NOT_TERMINAL_PASS');
        return ['final'=>$last['B1151_FINAL'],'phase'=>'INSTALLED_PASS','exit'=>0];
    }

    /**
     * Chain: exactly one Report1117 of B1151 with its terminal PASS (the pattern carries the sequence number 1117, so any earlier report
     * of the same prefix is not even a candidate) whose bytes equal the digest this package pins, the live B1151 state with the pinned
     * id INSTALLED_PASS, and no B1146-B1151 run. A different report, state id or digest is a HOLD with nothing changed.
     */
    private function chain():array
    {
        $directory=$this->root.'/docs/operations';$found=[];foreach(scandir($directory)?:[] as $name)if(preg_match(self::B1151_REPORT_PATTERN,$name))$found[]=$name;
        if(count($found)!==1)throw new RuntimeException(count($found)===0?'B1151_REPORT1117_MISSING':'B1151_REPORT1117_AMBIGUOUS');$path=$directory.'/'.$found[0];
        if(is_link($path)||realpath($path)!==$path||!is_file($path))throw new RuntimeException('B1151_REPORT1117_SCOPE');$bytes=(string)file_get_contents($path);$facts=self::b1151Facts($bytes);
        if(!hash_equals($this->b1151ReportSHA256,hash('sha256',$bytes)))throw new RuntimeException('B1151_REPORT1117_DIGEST_DIFFERS_FROM_THE_PINNED_ONE');
        $pointer=$this->b1151Base.'/current.json';if(is_link($pointer)||!is_file($pointer)||filesize($pointer)>8192)throw new RuntimeException('B1151_POINTER_MISSING');$p=json_decode((string)file_get_contents($pointer),true,8,JSON_THROW_ON_ERROR);
        if(!is_array($p)||($p['kind']??null)!=='B1151'||!preg_match('/^[a-f0-9]{32}$/D',(string)($p['id']??''))||($p['private']??null)!==$this->b1151Base.'/'.$p['id'])throw new RuntimeException('B1151_POINTER_SCOPE');
        if($p['id']!==$this->b1151StateId)throw new RuntimeException('B1151_STATE_ID_DIFFERS_FROM_THE_PINNED_ONE');
        $statePath=$p['private'].'/state.json';if(is_link($statePath)||realpath($statePath)!==$statePath||filesize($statePath)>16777216)throw new RuntimeException('B1151_STATE_MISSING');$stateBytes=(string)file_get_contents($statePath);$s=json_decode($stateBytes,true,512,JSON_THROW_ON_ERROR);
        if(!is_array($s)||($s['kind']??null)!=='B1151'||($s['id']??null)!==$p['id']||($s['phase']??null)!=='INSTALLED_PASS')throw new RuntimeException('B1151_STATE_NOT_INSTALLED_PASS');
        $retained=$s['chain']['retained']??null;$htaccess=$s['chain']['htaccessSHA256']??null;
        if(!is_string($retained)||!preg_match('~^/[A-Za-z0-9._/-]+/retained$~D',$retained)||!is_string($htaccess)||!preg_match('/^[a-f0-9]{64}$/D',$htaccess))throw new RuntimeException('B1151_STATE_FACTS');
        $this->earlierIdle();
        return ['report'=>'docs/operations/'.$found[0],'reportSHA256'=>hash('sha256',$bytes),'b1151Id'=>$p['id'],'b1151StateSHA256'=>hash('sha256',$stateBytes),'retained'=>$retained,'htaccessSHA256'=>$htaccess]+$facts;
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
        $found=[];$dirs=[];foreach($this->plan as $s)$dirs[dirname($this->target($s))]=true;foreach(array_merge($this->b1147,$this->b1148,$this->b1149,$this->b1150,$this->b1151) as $e)$dirs[dirname($this->active().'/'.$e['path'])]=true;
        foreach(array_keys($dirs) as $d)if(is_dir($d))foreach(scandir($d)?:[] as $name)if(OriginPinFiles::isOwnTemp($d.'/'.$name)||LegalAccountFiles::isOwnTemp($d.'/'.$name)||MediaFiles::isOwnTemp($d.'/'.$name)||StorefrontFiles::isOwnTemp($d.'/'.$name)||CatalogFiles::isOwnTemp($d.'/'.$name)||GlassFiles::isOwnTemp($d.'/'.$name))$found[]=$d.'/'.$name;return $found;
    }

    private function cleanupRecorded():void
    {
        $changed=false;
        foreach($this->state['probes']??[] as $i=>$probe){if(($probe['removed']??false)===true)continue;FreshnessProbe::removeStaging($probe['staging']);FreshnessProbe::remove($this->active().'/public',$probe['name'],$probe['sha256']);$this->state['probes'][$i]['removed']=true;$changed=true;}
        foreach($this->state['temps']??[] as $i=>$temp){if(OriginPinFiles::isOwnTemp($temp)&&is_file($temp)&&!is_link($temp)&&!unlink($temp))throw new RuntimeException('TEMP_FILE_REMOVE');unset($this->state['temps'][$i]);$changed=true;}
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

    /** Fully qualified class name => [realpath of its file in the active tree, payload sha256] for the new classes of STEPS (PSR-4: App\ is app/). */
    private function newClassFiles():array
    {
        $out=[];foreach($this->plan as $s){
            if($s['kind']!=='new'||!str_starts_with($s['path'],'app/')||!str_ends_with($s['path'],'.php'))continue;
            $dir=realpath(dirname($this->target($s)));$out['App\\'.str_replace('/','\\',substr($s['path'],4,-4))]=[($dir===false?dirname($this->target($s)):$dir).'/'.basename($s['path']),$s['payloadSHA256']];
        }
        return $out;
    }

    /**
     * The proof that comes right before bootstrap/app.php: a token-gated temporary probe served by the WEB SAPI loads every new class through the
     * application's own autoloader (vendor/autoload.php, PSR-4) and reports the file and its digest. Read-only, nothing is called, nothing is booted.
     * The class files themselves are the ones the full hold already covered (the hold runs from the last tree change, which is after group A).
     */
    /**
     * The same token-gated web probe with NO class list: only the app.url the web worker sees (read the way Laravel reads it, nothing booted, see LoadProbe) must be
     * exactly the pinned origin. Used by the PREFLIGHT (a mismatch is a HOLD before any write); the five-request class proof before bootstrap/app.php checks it again.
     */
    private function webAppUrlCheck(string $label):array
    {
        $this->cleanupRecorded();FreshnessProbe::assertNoLeftover($this->active().'/public');
        $plan=OriginPinLoadProbe::plan($this->state['id'],[],$this->expectedAppUrl);$staging=$this->state['private'].'/probe-'.bin2hex(random_bytes(8)).'.staging';
        $this->state['probes'][]=['name'=>$plan['name'],'sha256'=>$plan['sha256'],'label'=>$label,'staging'=>$staging];$this->persist();
        try{FreshnessProbe::write($this->active().'/public/'.$plan['name'],$plan['source'],$staging);$response=$this->web->probe($plan['name'],$plan['token'],false,null);}finally{$this->cleanupRecorded();}
        $route=is_string($response['_route']??null)?$response['_route']:'UNKNOWN';unset($response['_route']);
        $a=OriginPinLoadProbe::assess($response,$this->state['id'],[],$this->allowedSapis,posix_geteuid());
        $this->emit('WEB_APP_URL_CHECK',['label'=>$label,'route'=>$route,'gates'=>array_map(static fn($c)=>$c??'PASS',$a['gates'])]+array_intersect_key($a['values'],array_flip(['sapi','webEuidIsOperator','webAppUrlExact','webAppUrlOrigin','webAppUrlSource']))+['probeRemoved'=>true,'failures'=>$a['failures']]);
        if($a['failures']!==[])throw new RuntimeException($a['failures'][0]);
        return $a['values'];
    }

    private function webClassProof(string $label):array
    {
        $classes=$this->newClassFiles();$values=[];
        if($classes===[])throw new RuntimeException('WEB_CLASS_PROOF_NO_CLASSES');
        // WEB_PROOF_REQUESTS separate probe requests (each its own token-gated file), ALL must pass: the classes load and come from the payload files, and the web
        // worker's own app.url (read the way Laravel reads it, nothing booted) is exactly the pinned origin. A failure stops the install BEFORE bootstrap/app.php.
        for($i=1;$i<=self::WEB_PROOF_REQUESTS;$i++){
            $this->cleanupRecorded();FreshnessProbe::assertNoLeftover($this->active().'/public');
            $plan=OriginPinLoadProbe::plan($this->state['id'],$classes,$this->expectedAppUrl);$staging=$this->state['private'].'/probe-'.bin2hex(random_bytes(8)).'.staging';
            $this->state['probes'][]=['name'=>$plan['name'],'sha256'=>$plan['sha256'],'label'=>$label.'-'.$i,'staging'=>$staging];$this->persist();
            try{FreshnessProbe::write($this->active().'/public/'.$plan['name'],$plan['source'],$staging);$response=$this->web->probe($plan['name'],$plan['token'],false,null);}finally{$this->cleanupRecorded();}
            $route=is_string($response['_route']??null)?$response['_route']:'UNKNOWN';unset($response['_route']);
            $a=OriginPinLoadProbe::assess($response,$this->state['id'],$classes,$this->allowedSapis,posix_geteuid());
            $this->emit('WEB_CLASS_LOAD_PROOF',['label'=>$label,'request'=>$i,'of'=>self::WEB_PROOF_REQUESTS,'route'=>$route,'gates'=>array_map(static fn($c)=>$c??'PASS',$a['gates'])]+$a['values']+['probeRemoved'=>true,'failures'=>$a['failures']]);
            if($a['failures']!==[])throw new RuntimeException($a['failures'][0]);
            $values=$a['values'];
        }
        $this->state['webClassProof']=['label'=>$label,'at'=>time(),'requests'=>self::WEB_PROOF_REQUESTS,'classes'=>array_keys($classes)];$this->persist();return $values;
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

    /** Number of Cloudflare e-mail obfuscation leftovers in a delivered page: substring counts of EMAIL_ARTIFACTS (the address itself is never read or printed). */
    public static function emailArtifacts(string $html):int {$n=0;foreach(self::EMAIL_ARTIFACTS as $needle)$n+=substr_count($html,$needle);return $n;}

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
     * The scheduled-backup window this run needs: install (the hold before the views, the installed hold, 7 group pauses),
     * a full smokes() call, and if the acceptance fails the rollback (the hold after the views, the hold before the new
     * classes, the restored hold, 7 pauses) with its smokes; 300 s for artisan, probes and helpers. Never below 30 min.
     */
    private function requiredWindowSeconds(int $holdSeconds):int
    {
        return max(1800,5*$holdSeconds+2*$this->smokeCapSeconds()+14*$this->groupPause()+7*self::LIVENESS_SECONDS+self::WEB_PROOF_REQUESTS*self::WEB_PROOF_SECONDS+300);
    }

    /**
     * Liveness-only smoke after each install group, once the web workers have revalidated it (the group pause or the full hold):
     * /up and /login 200; one legal page 200 with noindex; with the shop ON /katalog 200 with noindex and the first product page 200;
     * GET /tema 405. No new-feature assertion (that is the acceptance). A failure stops the install, which rolls back through the
     * normal path; a 429 is inconclusive and the install continues; a changed shop switch is not judged here.
     */
    private function liveness(string $after):void
    {
        $marker=bin2hex(random_bytes(8));$shop=$this->shop();$rows=[];$failed=[];$limited=[];
        $one=function(string $label,string $path,string $method,int $want,bool $noindex)use(&$rows,&$failed,&$limited,$marker):?array{
            if($this->paceMicros>0)usleep($this->paceMicros);$r=$this->web->fetch($path,[],$marker,$method,false);
            if(!$r['transport']&&$r['status']===429){$limited[]=$label;$rows[]=['check'=>$label,'result'=>'INCONCLUSIVE_RATE_LIMITED_429'];return null;}
            $pass=!$r['transport']&&$r['status']===$want&&(!$noindex||CatalogWeb::noindex($r['robots']));
            $rows[]=['check'=>$label,'observed'=>$r['transport']?'TRANSPORT':$r['status'],'result'=>$pass?'PASS':'FAIL'];if(!$pass)$failed[]=$label;return $r;};
        $one('/up','/up','GET',200,false);$one('/login','/login','GET',200,false);$one('/pravne-informacije','/pravne-informacije','GET',200,true);
        if($shop['open']){$c=$one('/katalog','/katalog','GET',200,true);$p=$c!==null&&$c['status']===200?MediaWeb::productPath($c['body']):null;if($p!==null)$one('PRODUCT_PAGE_FIRST_CATALOG_LINK',$p,'GET',200,true);}
        $one('GET /tema',self::THEME_PATH,'GET',405,false);
        // Right after bootstrap/app.php (group D) the origin pin itself is asserted at once: the guest redirect must be on the pinned origin (https here, relative accepted).
        // A wrong origin fails like a 5xx and rolls bootstrap/app.php back first; a 429 stays inconclusive.
        if($after===self::BOOTSTRAP_GROUP){
            $r=$one('ORIGIN_PIN_'.self::SMTP_PATH,self::SMTP_PATH,'GET',302,false);
            if($r!==null&&!in_array(self::locationKind((string)($r['location']??''),$this->expectedAppUrl),['PINNED_ORIGIN','RELATIVE'],true)){$failed[]='ORIGIN_PIN_LOCATION';$rows[]=['check'=>'ORIGIN_PIN_LOCATION','observed'=>self::locationKind((string)($r['location']??''),$this->expectedAppUrl),'result'=>'FAIL'];}
        }
        $judged=$failed;if($failed!==[]){try{if($this->shop()!==$shop)$judged=[];}catch(Throwable){$judged=[];}}
        $this->emit('GROUP_LIVENESS_SMOKE',['after'=>$after,'shop'=>$shop,'rows'=>$rows,'failed'=>$judged,'rateLimited'=>$limited,'assertions'=>'LIVENESS_ONLY_NO_NEW_FEATURE']);
        if($judged!==[])throw new RuntimeException('GROUP_SMOKE_FAILED_AFTER_'.$after);
    }

    /**
     * Public smokes; no product, cart, account, theme or setting is written, nobody logs in (GET and one HEAD only). The
     * expectations follow the shop state read right before this batch (never changed by this tool) and $installed (the B1152
     * payload) or not (old = the live B1151 tree, as Report1117 left it). Everything B1149, B1150 and B1151 delivered is asserted in BOTH states.
     * New in this rollout: the guest redirects of the SMTP, merchant and document settings pages must point at /login on the pinned origin (https in the
     * installed state; the old state may still follow the scheme of the request), and an absolute action of the login form must be on that origin.
     * No forged-Host request is made (the pinned HTTP client sends only the real Host and Cloudflare rejects a foreign one): that proof is CI-only.
     * Scripts are counted as the origin wrote them: production HTML passes the Cloudflare edge, which injects its e-mail decoder
     * and JavaScript-detections loader into every page (MediaWeb::scripts); those are reported, never counted as app scripts.
     * always: /login and /up 200; the 8 legal pages 200 with DENY, noindex, the new layout marker and exactly one nonce'd script
     * (script-src and script-src-elem 'nonce-<same value as the <script nonce> of the page>', script-src-attr 'none');
     * the storefront CSS with its rule and the gallery CSS (bare and with the exact versioned URL the layout links);
     * GET and HEAD /tema 405; the admin SMTP page and the merchant settings page /admin/settings/public-storefront for a GUEST 302 to
     * /login (never logged in, nothing sent, no form, the password endpoints are never requested). The legal pages carry a count of
     * Cloudflare e-mail obfuscation leftovers (LEGAL_EMAIL_STATE, INFO only: must equal the old state, see the comment at the loop).
     * Owner policy: the shop is never indexed. The server-wide public/.htaccess sets X-Robots-Tag noindex on every response;
     * every public shop and legal page and every image response must carry it, else NOINDEX_POLICY_HEADER_MISSING (an edge/server
     * fact: preflight HOLDs, an installed acceptance HOLDs with the exact files kept, a rollback completes with an owner notice).
     * shop ON: /katalog 200 with the nonce CSP shape, layout marker, gallery stylesheet link, one parsable CollectionPage
     * JSON-LD block, Cache-Control private and no-store, Vary: Cookie (if the edge hides the header the origin is asked once
     * over loopback TLS: origin without it FAILS, origin unreachable is UNVERIFIED); ?sort=price_asc 200, an unknown sort
     * 302/422, ?page=999 404, /korpa 200, /registracija 200, /porucivanje 302 or 404, one product page (the first /katalog/<slug>
     * link) 200; the /site-assets/ logo answers 200 with nosniff, a sandboxed CSP and noindex (no logo link: INFO).
     * Product images (B1150, still in force, both states). The public pages do not link signed URLs (the storefront uses /katalog/slika/...), so
     * the page only contributes facts (/storage and /media/p/ link counts, never judged). The CLI hook media-sample
     * of the b1152 helper hands over signed URLs for the first product image that has a file. The GET (with the body) is made on
     * the thumbnail URL, which is small unless the rendition falls back to the original (body limit 12 MiB, above the 10 MiB
     * upload limit); the original is checked with HEAD (headers only). 200, image/jpeg|png|webp, nosniff, ETag, CORP cross-origin,
     * the directive SET private + max-age=604800 + immutable, noindex and NO Set-Cookie; If-None-Match gives 304; a changed
     * signature, no signature and an unknown image give 404 with no Set-Cookie. No signed URL while images exist, a null or
     * foreign accessor URL, or an unavailable hook FAILS; only a proven count of 0 images is INFO. The public /katalog/slika/<...>
     * image route linked by the catalog stays 200 with image/* in both states.
     * shop OFF: the 4 shop paths 404. The logo markup depends on the owner's settings and is otherwise never asserted.
     */
    private function smokes(bool $installed,bool $retried=false):array
    {
        $state=$installed?'INSTALLED':'OLD';$this->fault('BEFORE_SMOKES_'.$state);$shop=$this->shop();$m=$this->markers;$marker=bin2hex(random_bytes(8));$rows=[];$failed=[];$inconclusive=[];$limited=[];$this->limitBatch=['limited'=>false,'waited'=>0];
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
            if($p==='/login'&&$pass){$f['absoluteFormActionsOffOrigin']=self::offOriginActions($r['body'],$this->expectedAppUrl,$installed);$pass=$f['absoluteFormActionsOffOrigin']===0;}
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
        $cssVersion=$installed?$this->cssVersion:$this->oldCssVersion;
        $css(self::CSS_PATH,[],$marker,self::CSS_PATH,$m['css']);
        $css(self::CSS_PATH,['v'=>$cssVersion],null,self::CSS_PATH.'?v='.$cssVersion,$m['css']);
        $css(self::GALLERY_CSS_PATH,[],$marker,self::GALLERY_CSS_PATH,$m['gallery']);
        $css(self::GALLERY_CSS_PATH,['v'=>$this->galleryVersion],null,self::GALLERY_CSS_PATH.'?v='.$this->galleryVersion,$m['gallery']);
        // The theme route exists since B1148 and accepts POST only: GET and HEAD are answered 405 by the router in both states.
        foreach(['GET','HEAD'] as $method){$r=$this->get(self::THEME_PATH,[],$marker,$method);$add($method.' '.self::THEME_PATH,$r,'405_NEVER_5XX',$ok($r,405));}
        // The SMTP admin page (B1149), the merchant settings page (B1151) and the document settings page (the logo preview of this rollout) for a guest:
        // never logged in, no form submitted; the auth middleware redirects to the login page in both states, and the Location is on the pinned origin
        // (an absolute URL on any other host fails; a relative /login is accepted and recorded as such). The password endpoints are not requested at all.
        foreach([self::SMTP_PATH,self::MERCHANT_PATH,self::DOCUMENTS_PATH] as $guestPath){
            $r=$this->get($guestPath,[],$marker);$loc=(string)($r['location']??'');$kind=self::locationKind($loc,$this->expectedAppUrl);$good=in_array($kind,$installed?['PINNED_ORIGIN','RELATIVE']:['PINNED_ORIGIN','RELATIVE','SAME_HOST_OTHER_SCHEME'],true);
            $f=['location'=>$kind==='NONE'?null:($good?'LOGIN':'OTHER'),'locationOrigin'=>$kind];
            $add($guestPath,$r,'302_TO_LOGIN_ON_THE_PINNED_ORIGIN_GUEST',$ok($r,302)&&$good,$f);
        }
        $product=null;$logo=null;$publicImage=null;
        if($shop['open']){
            $r=$this->get('/katalog',[],$marker);$vary=MediaWeb::varyCookie($r['vary']);
            $storage=MediaWeb::storageImageCount($r['body']);$links=MediaWeb::mediaLinkCount($r['body']);
            $f=['cspShape'=>$shape($r),'originScripts'=>MediaWeb::scriptCount($r['body']),'edgeInjectedScripts'=>MediaWeb::edgeScriptCount($r['body']),'layoutMarker'=>str_contains($r['body'],$m['layout']),'galleryLink'=>str_contains($r['body'],$m['catalog']),'cacheControlPrivateNoStore'=>MediaWeb::privateNoStore($r['cache']),'jsonLd'=>CatalogWeb::jsonLdProblem($r['body'])??'PASS','varyCookie'=>$vary,'storageImageLinks'=>$storage,'mediaRouteLinks'=>$links];
            $pass=$ok($r,200)&&$f['cspShape']&&$f['layoutMarker']&&$f['galleryLink']&&$f['cacheControlPrivateNoStore']&&$f['jsonLd']==='PASS';
            if($pass&&!$vary){
                // The edge may rewrite Vary: the origin itself is asked once over loopback TLS (the B1144 probe route).
                if(($this->state['probeRoute']??null)==='LOOPBACK_TLS_ORIGIN'){$o=$this->get('/katalog',[],$marker,'GET',true);$f['originVaryCookie']=!$o['transport']&&$o['status']===200&&MediaWeb::varyCookie($o['vary']);$f['originObserved']=$o['transport']?'TRANSPORT':$o['status'];
                    if($f['originVaryCookie'])$notices[]='VARY_COOKIE_SENT_BY_THE_ORIGIN_BUT_NOT_VISIBLE_THROUGH_THE_EDGE';elseif(!$o['transport']&&$o['status']===200)$pass=false;else $unverifiedVary=true;}
                else $unverifiedVary=true;
            }
            $add('/katalog',$r,'200_CSP_JSON_LD_LAYOUT_PRIVATE_NO_STORE_VARY_COOKIE_GALLERY_'.($installed?'IMAGE_LINK_FACTS_ONLY':'IMAGE_LINK_FACTS_ONLY'),$pass,$f,true);
            if($ok($r,200)){$product=MediaWeb::productPath($r['body']);$logo=MediaWeb::siteAssetUrl($r['body']);$publicImage=MediaWeb::publicImagePath($r['body']);}
            $r=$this->get('/katalog',['sort'=>'price_asc'],$marker);$add('/katalog?sort=price_asc',$r,'200',$ok($r,200),[],true);
            $r=$this->get('/katalog',['sort'=>'ald1n_unknown'],$marker);$add('/katalog?sort=ald1n_unknown',$r,'302_OR_422',!$r['transport']&&in_array($r['status'],[302,422],true),[],true);
            $r=$this->get('/katalog',['page'=>'999'],$marker);$add('/katalog?page=999',$r,'404',$ok($r,404),[],true);
            $r=$this->get('/korpa',[],$marker);$f=['layoutMarker'=>str_contains($r['body'],$m['layout']),'cspShape'=>$shape($r)];$add('/korpa',$r,'200_CSP_LAYOUT',$ok($r,200)&&$f['layoutMarker']&&$f['cspShape'],$f,true);
            $r=$this->get('/registracija',[],$marker);$add('/registracija',$r,'200',$ok($r,200),[],true);
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
            // URLs (it uses /katalog/slika/...), so the CLI hook media-sample of the b1152 helper is the ONLY source: it builds the
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

    /** The 11 B1146 route names stay registered, the route count is unchanged (568) and no route cache exists (before, during and after; never created or cleared). */
    private function routesCheck():array
    {
        $r=$this->helper($this->routesScript,['routes']);$now=$this->routeCacheFiles();$this->emit('ROUTES_CHECK',$r+['routeCacheFiles'=>count($now),'expectedRouteCount'=>$this->expectedRoutes]);
        if($r['b1146RouteNamesPresent']!==self::B1146_ROUTE_NAMES||$r['storefrontSettingsRoute']!==true)throw new RuntimeException('B1146_ROUTES_NOT_ALL_PRESENT');
        if(($r['routeCount']??null)!==$this->expectedRoutes)throw new RuntimeException('ROUTE_COUNT_NOT_THE_B1151_COUNT');
        if($r['routesCached']!==false||$now!==[])throw new RuntimeException('ROUTE_CACHE_PRESENT_NOTHING_CHANGED');return $r;
    }

    /**
     * The new code as the CLI sees it, two ways: `artisan route:list --name=<route> --json` (read-only) and the b1152 helper.
     * The helper loads the two new classes by PSR-4 and asks AppUrlOrigin::configured() for the origin (it must be the pinned APP_URL), inspects
     * the HTTP kernel's global middleware list for PinAppUrlOrigin, reads bootstrap/app.php as text for PinAppUrlOrigin::class, reads the reset-password
     * notification as text (it is never instantiated, so no mail message is even built), checks by reflection and by the source text that the PDF raster
     * guard and the other payload edits are in place. B1149, B1150 and B1151
     * stay in BOTH states: site-assets.show, admin.settings.smtp.index, MailSettingsServiceProvider, the POST-only theme route, the B1150
     * classes and the media route, the password-change throttle and the merchant draft constant.
     * Installed: every new fact true; old: every new fact false. A bootstrap/app.php that names the middleware while the classes cannot load
     * is wrong in any state (the order guard: the file before its classes).
     */
    private function featureCheck(bool $installed):array
    {
        $h=$this->helper($this->featureScript,['features',json_encode(self::newClasses(),JSON_THROW_ON_ERROR),self::MEDIA_ROUTE_NAME,$this->expectedAppUrl,json_encode(self::SOURCE_MARKERS,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES)]);$hits=[];
        foreach(['site-assets.show','admin.settings.smtp.index','public-shop.theme',self::MEDIA_ROUTE_NAME] as $name){
            $r=$this->artisan(['route:list','--name='.$name,'--json'],120);if($r['rc']!==0)throw new RuntimeException('ROUTE_LIST_FAILED');$list=json_decode(trim($r['stdout']),true,16);$n=0;
            if(is_array($list))foreach($list as $row)if(is_array($row)&&($row['name']??null)===$name)$n++;$hits[$name]=$n;
        }
        $facts=$h+['routeList'=>$hits,'expectInstalled'=>$installed];$this->emit('FEATURE_CLI_CHECK',$facts);
        // The order guard, in any state: bootstrap/app.php must not name the middleware while a class it needs cannot load (every request would be a 500).
        $classesLoadable=$h['newClassesLoaded']===$h['newClassesTotal']&&$h['newClassesTotal']>0;
        if(($h['bootstrapNamesPin']||$h['middlewareGlobal'])&&!$classesLoadable)throw new RuntimeException('BOOTSTRAP_NAMES_THE_PIN_BUT_ITS_CLASSES_CANNOT_LOAD');
        // B1149, B1150 and B1151 stay in BOTH states (this rollout never touches their files).
        $kept=[$h['b1149ClassesLoaded'],$h['routeSiteAssets'],$h['routeSmtpIndex'],$h['providerRegistered'],$hits['site-assets.show']===1,$hits['admin.settings.smtp.index']===1];
        if($h['themeRoutePostOnly']!==true||$hits['public-shop.theme']!==1||$h['routesCached']!==false)throw new RuntimeException('B1148_THEME_ROUTE_MISSING');
        if(in_array(false,$kept,true))throw new RuntimeException('B1149_FEATURES_MISSING');
        if($h['b1150ClassesLoaded']!==true||$h['routeMedia']!==true||$hits[self::MEDIA_ROUTE_NAME]!==1)throw new RuntimeException('B1150_FEATURES_MISSING');
        if($h['throttleApi']!==true||$h['throttleResolvable']!==true||$h['webPasswordUsesThrottle']!==true||$h['apiPasswordUsesThrottle']!==true||$h['merchantIssueLabels']!==true)throw new RuntimeException('B1151_FEATURES_MISSING');
        // The reset-password notification is never run (no mail message is built); its APP_URL-based link is read as a source marker. Old tells from new by the markers.
        $new=[$classesLoadable,$h['originConfiguredIsExpected'],$h['middlewareGlobal'],$h['bootstrapNamesPin'],$h['pdfRasterGuard'],...array_values($h['sourceMarkers'])];
        if($installed&&in_array(false,$new,true))throw new RuntimeException('NEW_CLASSES_MIDDLEWARE_OR_FILES_NOT_PRESENT_AFTER_INSTALL');
        if(!$installed&&($h['newClassesLoaded']!==0||in_array(true,array_slice($new,1),true)))throw new RuntimeException('NEW_CLASSES_MIDDLEWARE_OR_FILES_PRESENT_IN_THE_OLD_STATE');
        return $facts;
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
     * CLI, read-only, in its own uncached process (the pinned-with-the-package b1152-app-check.php env mode): the PHP extensions the
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
        $chain=$try('chainB1151Report1117',fn()=>$this->chain());if($chain!==null){$this->state['chain']=$chain;$v['retainedDirectoryModes']=$this->retainedModes();}$v['chain']=$chain;
        $manifest=$try('runtimeManifestPlusB1146B1147B1148B1149B1150B1151',fn()=>$this->b1151Manifest());
        $targets=$try('overlayTargets',fn()=>$this->targets());$v['targets']=$targets;
        if($targets!==null){if(!$installed&&!$targets['preState'])$g['overlayTargets']=$targets['installed']?'OVERLAY_ALREADY_PRESENT_WITHOUT_B1152_STATE_NO_BACKUP_NOTHING_CHANGED':($targets['link']!==[]?'TARGET_IS_SYMLINK':'UNKNOWN_PRE_STATE_REFUSED');if($installed&&!$targets['installed'])$g['overlayTargets']='INSTALLED_DIGESTS_CHANGED';}
        $v['tree']=$manifest!==null&&$chain!==null?$try('activeTreeEqualsManifestPlusB1146B1147B1148B1149B1150B1151',fn()=>$installed?LeanGates::verifyTree($this->active(),$this->installedManifest(),self::CONFIG_SHA,$chain['htaccessSHA256'],self::installedTreeFiles()):LeanGates::verifyTree($this->active(),$manifest,self::CONFIG_SHA,$chain['htaccessSHA256'],self::B1151_TREE_FILES)):null;
        if(!array_key_exists('activeTreeEqualsManifestPlusB1146B1147B1148B1149B1150B1151',$g))$g['activeTreeEqualsManifestPlusB1146B1147B1148B1149B1150B1151']='NOT_EVALUATED_CHAIN_OR_MANIFEST';
        if(!$installed)$v['replaceable']=$try('targetsReplaceable',function():array{$n=0;foreach($this->plan as $s){if($s['kind']==='modified'){OriginPinFiles::replaceable($this->target($s));$n++;}else{$d=dirname($this->target($s));while(!is_dir($d))$d=dirname($d);if(is_link($d)||!is_writable($d))throw new RuntimeException('TARGET_DIRECTORY_NOT_WRITABLE');}}return ['modifiedOneLinkNoAcl'=>$n];});
        // The two new classes of the payload are found through the PSR-4 fallback; an authoritative classmap would make them invisible, and bootstrap/app.php
        // names one of them. (After group A the web-SAPI class proof and the CLI feature check show that they really load.)
        $g['composerClassmapNotAuthoritative']=$this->classmapBlocker();
        $g['configNotCached']=file_exists($this->active().'/bootstrap/cache/config.php')?'CONFIG_CACHE_PRESENT':null;
        $g['noRouteCache']=$this->routeCacheFiles()===[]?null:'ROUTE_CACHE_PRESENT_NOTHING_CHANGED';
        $g['noMaintenanceMode']=file_exists($this->active().'/storage/framework/down')||file_exists($this->active().'/storage/framework/maintenance.php')?'MAINTENANCE_MODE_ACTIVE':null;
        $try('noLeftoverProbeOrTemp',function():void{FreshnessProbe::assertNoLeftover($this->active().'/public');if($this->ownTemps()!==[])throw new RuntimeException('LEFTOVER_B1147_B1148_B1149_B1150_B1151_OR_B1152_TEMP_FILE');});
        $v['caches']=$try('cacheState',fn()=>['routeCacheFiles'=>count($this->routeCacheFiles()),'compiledViews'=>$this->compiledViews(),'viewsCached'=>$this->compiledViews()>0]);
        $v['logs']=LeanGates::dailyLogs($this->active().'/storage/logs');$g['laravelDailyLog']=$v['logs']['dailyFiles']>0?null:'LARAVEL_DAILY_LOG_MISSING';
        $v['migrations']=$try('migrateStatus71Ran0Pending',fn()=>$this->migrationStatus());
        $app=$try('appStateDatabase',fn()=>$this->helper($this->appStateScript,['state']));
        if($app!==null){
            $shop=self::shopOf($app);$v['shop']=$shop+['writtenByThisTool'=>false];$v['app']=['backupsRunning'=>$app['backups']['running']];
            $g['shopStateRecorded']=!$shop['known']?'SHOP_SWITCH_VALUE_UNKNOWN':(!$installed&&!$shop['open']?'SHOP_IS_OFF_B1152_INSTALLS_ONLY_WITH_THE_SHOP_ON_NOTHING_CHANGED':null);
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
        $v['routes']=$try('b1146LegalRoutesNoRouteCache',fn()=>$this->routesCheck());
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
            $need=$this->requiredWindowSeconds((int)$v['web']['holdSeconds']);$v['timeBound']=['holdSeconds'=>(int)$v['web']['holdSeconds'],'groupPauseSeconds'=>$this->groupPause(),'smokeCapSeconds'=>$this->smokeCapSeconds(),'requiredWindowSeconds'=>$need,'rule'=>'5*HOLD+2*SMOKE_CAP+14*PAUSE+7*LIVENESS(180)+5*WEB_PROOF(30)+300<=WINDOW'];
            $this->scheduleNeedSeconds=max($this->scheduleNeedSeconds,$need);
            if($this->scheduleGate&&$app!==null){$s=$try('scheduleWindowFitsTimeBound',fn()=>$this->scheduleState($app));$v['scheduleTimeBound']=$s;if($s!==null&&$s['conflict']!==null)$g['scheduleWindowFitsTimeBound']='SCHEDULED_BACKUP_WITHIN_THE_'.$need.'_SECONDS_THIS_RUN_MAY_NEED';}
        }else $g['timeBound']='NOT_EVALUATED_WEB_PROBE';
        $v['merchantApproval']=$try('merchantApprovalRead',fn()=>self::approvalFacts($this->helper($this->routesScript,['approval'])));
        $this->emailFacts=null;$try('liveSmokes',fn()=>$this->smokes($installed));
        // The old-state e-mail obfuscation count is the baseline the installed smokes compare with (INFO only); kept in the saved state, never printed as a value.
        if(!$installed&&$this->emailFacts!==null)$this->state['emailBaseline']=$this->emailFacts;
        $blockers=array_values(array_unique(array_filter($g,static fn($c)=>$c!==null)));
        if(isset($v['shop']))$this->state['shopRecorded']=$v['shop'];
        $report=['stage'=>$stage,'gates'=>array_map(static fn($c)=>$c??'PASS',$g),'values'=>$v,'blockers'=>$blockers,'applicationFiles'=>'NONE','database'=>'NO_PRODUCT_CART_OR_SWITCH_WRITES_SESSIONS_AND_APP_CACHE_ONLY','shopSwitch'=>'READ_ONLY','transientProbe'=>'WRITTEN_AND_REMOVED','privateState'=>'B1152_NAMESPACE_ONLY'];$this->emit('PREFLIGHT',$report);return $report;
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
        if(!file_exists($this->pointer))return false;if(is_link($this->pointer)||filesize($this->pointer)>8192)throw new RuntimeException('B1152_POINTER');$p=json_decode((string)file_get_contents($this->pointer),true,8,JSON_THROW_ON_ERROR);
        if(!preg_match('/^[a-f0-9]{32}$/D',(string)($p['id']??''))||($p['private']??null)!==$this->base.'/'.$p['id'])throw new RuntimeException('B1152_POINTER_SCOPE');$path=$p['private'].'/state.json';
        if(is_link($path)||realpath($path)!==$path||filesize($path)>16777216)throw new RuntimeException('B1152_STATE');$this->state=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
        if(($this->state['schemaVersion']??0)!==1||($this->state['kind']??null)!=='B1152'||($this->state['id']??null)!==$p['id'])throw new RuntimeException('B1152_STATE_BINDING');return true;
    }

    private function begin():void
    {
        $id=bin2hex(random_bytes(16));$private=$this->base.'/'.$id;if(!mkdir($private,0700))throw new RuntimeException('PRIVATE_TRANSACTION_CREATE');
        $this->state=['schemaVersion'=>1,'kind'=>'B1152','id'=>$id,'private'=>$private,'phase'=>'NEW','startedAt'=>time(),'probes'=>[],'temps'=>[],'applied'=>[],'createdDirs'=>[]];$this->save('PREFLIGHT');
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
        $this->state['groupPauseSeconds']=(int)($preflight['values']['web']['revalidateFreq']??2)+1;$this->state['holdSeconds']=(int)($preflight['values']['web']['holdSeconds']??FreshnessProbe::MAX_HOLD_SECONDS);$this->state['approvalBefore']=$preflight['values']['merchantApproval']??null;$doc=$dir.'/manifest.json';if(is_file($doc))unlink($doc);AtomicFile::privateCopy($doc,json_encode(['batch'=>'B1152','files'=>array_values($files)],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
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

    /**
     * view:clear only (compiled views are rebuilt on demand by the web workers, never pre-compiled). With $full (the end of the
     * install and of a rollback) also route:clear (no route cache ever exists here; this only guarantees it) and queue:restart
     * (running queue workers pick up the new or restored code). Never a route, config or view cache build.
     */
    private function refreshViews(bool $full=false):void
    {
        if($full)$this->artisanOk(['route:clear']);$this->artisanOk(['view:clear']);if($full)$this->artisanOk(['queue:restart']);$this->changed();$this->persist();
    }

    /**
     * The last gate before bootstrap/app.php (the step that names PinAppUrlOrigin::class): every new class of STEPS must hold exactly the payload
     * in the tree and the web-SAPI proof of this run must have passed. Without both, the file is never written (a 500 on every request, /up included).
     */
    private function bootstrapGuard():void
    {
        foreach($this->plan as $x)if($x['kind']==='new'&&$this->classify($x)['state']!=='payload')throw new RuntimeException('BOOTSTRAP_REFUSED_A_NEW_CLASS_IS_NOT_IN_PLACE');
        $proof=$this->state['webClassProof']??null;if(!is_array($proof)||($proof['classes']??null)!==array_keys($this->newClassFiles()))throw new RuntimeException('BOOTSTRAP_REFUSED_NO_WEB_SAPI_CLASS_PROOF');
    }

    private function install():void
    {
        $this->state['logBaseline']=LeanGates::logBaseline($this->active().'/storage/logs');$this->state['applied']=[];unset($this->state['webClassProof']);$this->save('INSTALL_INTENT');$group=null;
        // Between groups every web worker revalidates the files of the previous group first (revalidate_freq + 1 s), so a
        // request never combines new bytecode of a later group with still-cached old bytecode of an earlier one.
        $pause=$this->groupPause();
        foreach($this->plan as $s){
            // Group D (bootstrap/app.php) comes after the full measured hold, which always spans group A (the hold runs from the last tree change), and
            // after the web-SAPI proof that both new classes load; the other group boundaries get the short revalidation pause.
            if($group!==null&&$group!==$s['group']){
                if($s['group']===self::BOOTSTRAP_GROUP){$this->hold('before-bootstrap',$this->fullHold());$this->webClassProof('before-bootstrap');$this->fault('CLASSES_PROVEN');}
                else{$this->emit('OVERLAY_GROUP_PAUSE',['after'=>$group,'before'=>$s['group'],'seconds'=>$pause]);sleep($pause);}
                $this->fault('GROUP_DONE_'.$group);$this->liveness($group);
            }
            $group=$s['group'];
            $this->fault('BEFORE_STEP_'.$s['n']);if($s['path']===self::BOOTSTRAP_PATH)$this->bootstrapGuard();$t=$this->target($s);$bytes=$this->payload($s);$this->state['applied'][$s['n']]='PENDING';
            if($s['kind']==='new'){
                $dir=dirname($t);if(!is_dir($dir)){if(!is_dir(dirname($dir)))throw new RuntimeException('TARGET_PARENT_MISSING');$this->state['createdDirs'][]=$dir;$this->persist();if(!mkdir($dir,0755))throw new RuntimeException('TARGET_DIRECTORY_CREATE');chmod($dir,0755);$pg=stat(dirname($dir))['gid'];if(stat($dir)['gid']!==$pg)@chgrp($dir,$pg);clearstatcache(true,$dir);if(stat($dir)['gid']!==$pg)throw new RuntimeException('OVERLAY_DIRECTORY_GID_NOT_PRESERVABLE');}
                $temp=$this->recordTemp(OriginPinFiles::tempFor($t));$receipt=OriginPinFiles::create($t,$bytes,0644,$temp,stat($dir)['gid']);
            }else{$b=$this->state['backup']['files'][$s['n']];$temp=$this->recordTemp(OriginPinFiles::tempFor($t));$receipt=OriginPinFiles::replace($t,$bytes,$b['mode'],$b['gid'],$temp,(string)$s['preSHA256']);}
            $this->state['temps']=array_values(array_filter($this->state['temps'],static fn($x)=>$x!==$temp));$this->state['applied'][$s['n']]='DONE';$this->changed();$this->persist();
            $this->emit('OVERLAY_STEP',['n'=>$s['n'],'group'=>$s['group'],'kind'=>$s['kind'],'path'=>$s['path'],'sha256'=>$s['payloadSHA256']]+$receipt);$this->fault('AFTER_STEP_'.$s['n']);
        }
        // The last group is bootstrap/app.php: ONE revalidation pause so every worker runs it, then the liveness smoke at once (/up and /login first; a
        // failure rolls bootstrap/app.php back before anything else), and only then route:clear, view:clear and queue:restart (the acceptance judges the rest).
        $this->fault('GROUP_DONE_'.$group);$this->emit('OVERLAY_GROUP_PAUSE',['after'=>$group,'before'=>'ACCEPTANCE','seconds'=>$pause]);sleep($pause);$this->liveness($group);$this->refreshViews(true);$this->save('FILES_INSTALLED');
    }

    private function accept():void
    {
        $this->save('ACCEPTANCE_INTENT');$first=$this->probeRun('installed-invalidate',true,true,false);$this->fault('AFTER_INVALIDATE');$this->hold('installed',(int)$first['holdSeconds']);
        $this->requireRecordedShop('BEFORE_INSTALLED_SMOKES');$this->smokes(true);$this->fault('AFTER_SMOKES_INSTALLED');$this->requireRecordedShop('AFTER_INSTALLED_SMOKES');
        $this->unthrottledChecks('ACTIVE_TREE_AFTER_INSTALL');
        $this->approvalReport('after-install');$this->requireRecordedShop('BEFORE_INSTALLED_PASS');
        $this->save('INSTALLED_PASS');
    }

    /**
     * Puts every backed-up byte back in exact reverse install order (a modified file only when it holds the payload, a new
     * file removed only when it holds the payload; anything foreign is reported and never overwritten; the first failure
     * stops the loop), with the full measured hold right after bootstrap/app.php is back to its old bytes and again before the
     * new classes go, re-invalidates, waits and smoke-tests the old state with the shop in whatever state it is now.
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
                // bootstrap/app.php (group D) goes FIRST, then the full measured hold, so no web worker still runs the new bootstrap (and so still names the
                // middleware class) when anything else changes; group C and group B follow with the revalidation pause between them; before the two new
                // classes (group A) the full hold again, the classes are removed last. A fresh process resuming a rollback times each wait from the saved
                // wall-clock time of the last change.
                $key=$s['group'];$boundary=$group!==null&&$group!==$key;$prev=$group;$group=$key;
                try{
                    if($boundary){
                        if($prev===self::BOOTSTRAP_GROUP){$this->emit('ROLLBACK_AFTER_BOOTSTRAP',['bootstrapIsOld'=>$this->classify($this->step(self::BOOTSTRAP_PATH))['state']==='pre']);$this->hold('rollback-after-bootstrap',$this->fullHold());}
                        elseif($key==='A')$this->hold('rollback-before-new-classes',$this->fullHold());
                        else{$this->emit('ROLLBACK_GROUP_PAUSE',['before'=>$key,'seconds'=>$pause]);$this->hold('rollback-pause-'.$key,$pause);}
                    }
                    $this->fault('BEFORE_RESTORE_'.$s['n']);$c=$this->classify($s);$t=$this->target($s);
                    if($s['kind']==='modified'){
                        if($c['state']==='payload'){$b=$backup['files'][$s['n']];$bytes=AtomicFile::readPrivate($b['copy'],$b['sha256']);OriginPinFiles::replace($t,$bytes,$b['mode'],$b['gid'],$this->recordTemp(OriginPinFiles::tempFor($t)),$s['payloadSHA256']);$restored++;$this->changed();}
                        elseif($c['state']!=='pre')throw new RuntimeException('ROLLBACK_FOREIGN_CONTENT_NOT_OVERWRITTEN_'.$s['n']);
                    }elseif($c['state']==='payload'){OriginPinFiles::removeExact($t,$s['payloadSHA256']);$removed++;$this->changed();}
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
            $t=$this->targets();if(!$t['preState'])throw new RuntimeException('ROLLBACK_TARGETS_NOT_PRE_STATE');$this->routesCheck();$this->featureCheck(false);
            $first=$this->probeRun('restored-invalidate',false,true,false);$this->hold('restored',(int)$first['holdSeconds']);
            try{try{$this->smokes(false);}catch(Throwable $e){if(self::code($e)!=='SHOP_STATE_CHANGED_DURING_ACCEPTANCE_STOREFRONT_NOT_VERIFIED')throw $e;$this->emit('OLD_STATE_SMOKES_REPEATED','THE_SHOP_STATE_CHANGED_DURING_THE_BATCH');$this->smokes(false);}}catch(Throwable $e){
                if(self::code($e)==='NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES'){$this->state['rollbackNoindexMissing']=true;$this->emit('OWNER_NOTICE_NOINDEX_MISSING','THE_RESTORED_FILES_ARE_EXACT_BUT_PUBLIC_PAGES_ARE_SERVED_WITHOUT_X-ROBOTS-TAG_NOINDEX_RESTORE_THE_HEADER_LINE_IN_apps/cms/current/public/.htaccess_OR_THE_CLOUDFLARE_RULE_THAT_REMOVES_IT');}
                elseif(self::code($e)==='SMOKES_RATE_LIMITED_429'){$this->state['rollbackSmokesRateLimited']=true;$this->emit('OWNER_NOTICE_RATE_LIMITED','THE_RESTORED_FILES_ARE_EXACT_BUT_THE_SITE_ANSWERED_429_TO_THE_SMOKES_OPEN_https://cms.ald1n.com/katalog_AND_ONE_LEGAL_PAGE_YOURSELF');}
                else throw $e;}
            $this->probeRun('restored-fresh',false,false,true);
            $tree=LeanGates::verifyTree($this->active(),$this->b1151Manifest(),self::CONFIG_SHA,$this->state['chain']['htaccessSHA256'],self::B1151_TREE_FILES);$this->emit('ACTIVE_TREE_AFTER_ROLLBACK',$tree+['equalsManifestPlusB1146B1147B1148B1149B1150B1151'=>true]);
            try{$this->mailFingerprintUnchanged('after-rollback');}catch(Throwable $e){$this->emit('OWNER_NOTICE_MAIL_FINGERPRINT',self::code($e));}
            $this->approvalReport('after-rollback');
        }catch(Throwable $e){$problems[]=self::code($e);}
        if($problems!==[]){
            try{$this->save('ROLLBACK_INCOMPLETE');}catch(Throwable){$this->emit('ROLLBACK_STATE_UNWRITABLE','FIX_DISK_OR_PERMISSIONS_THEN_RUN_THE_SAME_COMMAND');}
            $this->recoveryNote(array_values(array_unique($problems)));return false;
        }
        $this->save('ROLLED_BACK');$this->emit('ROLLED_BACK',['files'=>'EXACT_PRIVATE_COPIES','newFiles'=>'REMOVED','routeCache'=>'NONE_NEVER_CREATED','compiledViews'=>'CLEARED_REBUILT_ON_DEMAND_NOT_PRECOMPILED','tree'=>'EQUALS_8567_FILE_MANIFEST_PLUS_B1146_B1147_B1148_B1149_B1150_B1151','shopSwitch'=>'NOT_WRITTEN']);return true;
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
     * right before it is installed, each new file removed), after the views view:clear and the full measured hold, the full
     * hold again before the new classes, a sleep at every other group boundary, then the empty directories and view:clear.
     * The shop may stay ON: these files change no data.
     */
    private function recoveryNote(array $problems,string $kind='ROLLBACK_INCOMPLETE'):void
    {
        $a=$this->active();$files=[];$q=static fn(string $v):string=>escapeshellarg($v);
        foreach($this->plan as $s){try{$now=$this->classify($s)['state'];}catch(Throwable){$now='UNREADABLE';}$b=$this->state['backup']['files'][$s['n']]??null;$files[$s['n']]=['n'=>$s['n'],'group'=>$s['group'],'kind'=>$s['kind'],'path'=>$s['path'],'now'=>$now,'backupCopy'=>$b['copy']??null,'sha256'=>$b['sha256']??null,'mode'=>isset($b['mode'])?sprintf('%04o',$b['mode']):null,'gid'=>$b['gid']??null];}
        $record=is_array($this->state['backup']['files']??null);
        $next=$kind==='ROLLBACK_INCOMPLETE'?'FIX_THE_REPORTED_CAUSE_THEN_RUN_THE_SAME_COMMAND_IT_RETRIES_THE_ROLLBACK_FIRST':'NOTHING_WAS_ATTEMPTED_CLEAR_THE_BLOCKERS_AND_RUN_THE_SAME_COMMAND';
        $this->emit('RECOVERY_NOTE',['kind'=>$kind,'problems'=>$problems,'files'=>array_values($files),'backupDirectory'=>$this->state['backup']['directory']??null,'backupRecord'=>$record?'PRESENT':'MISSING_NEW_FILES_ARE_NOT_REMOVED_BY_THE_BLOCK','privateCopies'=>'KEPT_0600','next'=>$next,'manualBlock'=>'ONLY_IF_THE_SAME_COMMAND_KEEPS_FAILING_THE_SHOP_MAY_STAY_ON','order'=>'THE_AUTOMATIC_ROLLBACK_ORDER_REVERSE_INSTALL_ORDER_VIEW_CLEAR_AND_THE_FULL_HOLD_AFTER_THE_VIEWS_THE_FULL_HOLD_BEFORE_THE_NEW_CLASSES_A_SLEEP_AT_EVERY_OTHER_GROUP_BOUNDARY_THEN_VIEW_CLEAR']);
        $lines=['set -euo pipefail','umask 022'];$view='cd '.$q($a).' && '.$q($this->php).' -d opcache.enable=0 -d opcache.enable_cli=0 artisan view:clear --no-interaction';
        $pause=$this->groupPause();$last=null;
        foreach(array_reverse($files) as $f){
            $t=$a.'/'.$f['path'];$key=$f['group'];$act=[];
            if($f['kind']==='modified'&&$f['now']==='payload'&&$f['backupCopy']!==null)$act=['printf '.$q('%s  %s\n').' '.$q((string)$f['sha256']).' '.$q((string)$f['backupCopy']).' | sha256sum --check --strict --quiet -','install -m '.$f['mode'].' -g '.(int)$f['gid'].' '.$q((string)$f['backupCopy']).' '.$q($t)];
            elseif($f['kind']==='modified'&&$f['now']==='payload')$act=['echo '.$q('NO_BACKUP_COPY_FOR '.$t.' STOP').' >&2','exit 2'];
            elseif($f['kind']==='new'&&$f['now']==='payload'&&$record)$act=['rm -f -- '.$q($t)];
            elseif($f['kind']==='modified'&&$f['now']==='absent')echo 'OWNER_INSPECT: '.$t.' IS_MISSING_NOT_REMOVED_BY_B1152_THE_OLD_BYTES_ARE_IN '.($f['backupCopy']??'NO_BACKUP_COPY').' SHA256 '.($f['sha256']??'UNKNOWN').PHP_EOL;
            elseif(!in_array($f['now'],['pre','absent','payload'],true))echo 'OWNER_INSPECT: '.$t.' IS_'.strtoupper($f['now']).'_NOT_TOUCHED_BY_B1152'.PHP_EOL;
            if($act===[])continue;
            if($last!==null&&$last!==$key){if($last===self::BOOTSTRAP_GROUP||$key==='A')$lines[]='sleep '.$this->fullHold();else $lines[]='sleep '.$pause;}
            $last=$key;array_push($lines,...$act);
        }
        if($record)foreach(array_reverse($this->state['createdDirs']??[]) as $dir)if(is_dir($dir)&&!is_link($dir))$lines[]='rmdir --ignore-fail-on-non-empty -- '.$q($dir);
        $lines[]=$view;$lines[]='echo B1152_MANUAL_RECOVERY_BLOCK_DONE';
        echo 'OWNER_COMMAND_BLOCK=PASTE_EVERYTHING_FROM_THE_bash_-e_LINE_TO_THE_B1152_RECOVERY_LINE_INTO_THE_SSH_SHELL_IT_STOPS_AT_THE_FIRST_FAILING_LINE'.PHP_EOL;
        echo "bash -e <<'B1152_RECOVERY'".PHP_EOL.implode(PHP_EOL,$lines).PHP_EOL.'B1152_RECOVERY'.PHP_EOL;
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
        $app=$this->helper($this->appStateScript,['state']);$shop=self::shopOf($app);if($shop!==array_diff_key($this->state['shopRecorded']??[],['writtenByThisTool'=>true]))throw new RuntimeException('SHOP_STATE_CHANGED_BEFORE_FIRST_CHANGE');if(!$shop['open'])throw new RuntimeException('SHOP_IS_OFF_B1152_INSTALLS_ONLY_WITH_THE_SHOP_ON_NOTHING_CHANGED');
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
        $this->final=$final;$this->emit('B1152_RESULT',['overlay'=>'APP_URL_ORIGIN_PIN_AND_PDF_LOGO_RASTER_GUARD_'.count($this->plan).'_FILES','baseCommit'=>self::BASE_COMMIT,'mainCommit'=>self::MAIN_COMMIT,'urlCommit'=>self::URL_COMMIT,'pdfCommit'=>self::PDF_COMMIT,'newFiles'=>$new,'modifiedFiles'=>count($this->plan)-$new,'appUrl'=>'EXACTLY_'.$this->expectedAppUrl.'_VERIFIED_BEFORE_ANY_CHANGE','newRoutes'=>'NONE_ROUTE_COUNT_'.$this->expectedRoutes.'_UNCHANGED','legalEmailCheck'=>'INFO_ONLY_IN_THIS_TOOL_NO_LAYOUT_OR_LEGAL_FILE_IS_IN_THE_PAYLOAD','passwordEndpoints'=>'NEVER_CALLED_NO_LOGIN','passwordReset'=>'NEVER_REQUESTED_NO_MAIL_SENT','pdfGeneration'=>'NOT_EXERCISED','tests'=>'NOT_INSTALLED','shopSwitch'=>'READ_ONLY_NOT_WRITTEN','smtpSettings'=>'NOT_READ_NOT_WRITTEN_NO_TEST_MAIL_ONLY_A_TRANSPORT_FINGERPRINT','routeCache'=>'NONE','migrations'=>'NONE','composer'=>'NONE','env'=>'UNCHANGED','config'=>'UNCHANGED','queue'=>'RESTARTED_VIA_ARTISAN_queue:restart','opcache'=>'TARGETED_PER_FILE_INVALIDATION_ONLY','backupDirectory'=>$this->state['backup']['directory']??'NONE_ALREADY_INSTALLED_BEFORE_THIS_TOOL_STATE']);
        $this->cssNotice();
        $this->emit('OWNER_NEXT_STEPS',array_merge([$this->purgeStep(),
            '2_LOG_IN_ON_THE_WEB_AND_IN_THE_ANDROID_APP_AND_CHECK_THAT_YOU_STAY_ON_'.parse_url($this->expectedAppUrl,PHP_URL_HOST).'_(THIS_TOOL_NEVER_LOGS_IN)',
            '3_REQUEST_ONE_PASSWORD_RESET_FOR_YOUR_OWN_ACCOUNT_AND_CHECK_THAT_THE_LINK_IN_THE_MAIL_STARTS_WITH_'.$this->expectedAppUrl.'_(THIS_TOOL_NEVER_REQUESTS_A_RESET_AND_SENDS_NO_MAIL)',
            '4_OPEN_ONE_PDF_DOCUMENT_AND_CHECK_THE_LOGO_(THE_PDF_GENERATION_WAS_NOT_EXERCISED_BY_THIS_TOOL_AND_THE_DOCUMENT_SETTINGS_PAGE_ONLY_ANSWERED_AS_A_GUEST_WITH_THE_LOGIN_REDIRECT)',
            '5_THE_FORGED-HOST_CHECK_WAS_NOT_MADE_BY_THIS_TOOL_(ITS_PINNED_HTTP_CLIENT_SENDS_ONLY_THE_REAL_HOST_AND_CLOUDFLARE_REJECTS_A_FOREIGN_HOST)_THE_PIN_ITSELF_IS_PROVEN_BY_THE_CI_FIXTURE_AND_BY_STEP_3',
            '6_THE_SHOP_AND_THE_SMTP_SETTINGS_STAY_AS_YOU_SET_THEM_THIS_TOOL_NEVER_CHANGES_THEM',
            '7_DO_NOT_RERUN_B1144_TO_B1151_OR_THEIR_ROLLBACK'],isset($this->state['backup']['directory'])?['ROLLBACK_IF_NEEDED=bash /home/icaffeco/ald1n-project/incoming/b1152-installer.sh --rollback (THE_SHOP_MAY_STAY_ON)']:['NO_B1152_BACKUP_IN_THIS_STATE_NO_AUTOMATIC_ROLLBACK']));
        $this->emit('DO_NOT_RERUN_B1144_B1145_B1146_B1147_B1148_B1149_B1150_OR_B1151_AFTER_B1152','THEIR_MANIFEST_GATES_NOW_SEE_THE_'.count($this->plan).'_B1152_FILES_AND_THEIR_--rollback_WOULD_REFUSE_FOREIGN_BYTES');return 0;
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
                if(!$this->restore('OWNER_REQUESTED_ROLLBACK'))return 2;$this->final='PASS_ROLLED_BACK_BY_OWNER';$this->emit('OWNER_NOTICE','THE_B1151_STATE_IS_ACTIVE_AGAIN_A_LATER_RUN_OF_THE_SAME_COMMAND_WITHOUT_OPTIONS_INSTALLS_AGAIN');
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
                $this->emit('OWNER_NEXT_STEPS',[$first,'2_THEN_RUN_THE_SAME_COMMAND_AGAIN_IT_REPEATS_ONLY_THE_ACCEPTANCE_CHECKS_NO_FILE_IS_CHANGED','3_OR_RESTORE_THE_OLD_FILES=bash /home/icaffeco/ald1n-project/incoming/b1152-installer.sh --rollback',$this->purgeStep()]);
                return 2;
            }
        }
        $ok=$this->restore($code);$this->final=$ok?'HOLD_ROLLED_BACK_OLD_STATE_VERIFIED':'HOLD_ROLLBACK_INCOMPLETE';if($ok)$this->emit('OWNER_NEXT_STEPS',[$this->purgeStep(true)]);return 2;
    }

    /** The acceptance checks that need neither a throttled route nor the shop: fresh probes, routes, new classes/routes/provider, mail-transport fingerprint, log, tree. */
    private function unthrottledChecks(string $label):void
    {
        $this->probeRun('installed-fresh',true,false,true);$this->routesCheck();$this->featureCheck(true);$this->productMediaDoctor('after-install',true);$this->mailFingerprintUnchanged('after-install');
        $scan=LeanGates::newLogErrors($this->active().'/storage/logs',$this->state['logBaseline']);$this->emit('LARAVEL_LOG_SINCE_INSTALL',$scan);if($scan['newErrorLines']!==0)throw new RuntimeException('NEW_LARAVEL_LOG_ERRORS_SINCE_INSTALL');
        $tree=LeanGates::verifyTree($this->active(),$this->installedManifest(),self::CONFIG_SHA,$this->state['chain']['htaccessSHA256'],self::installedTreeFiles());$this->emit($label,$tree+['manifestPlusB1146B1147B1148B1149B1150B1151B1152'=>true]);
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
final class OriginPinFiles
{
    public static function tempFor(string $target):string {return dirname($target).'/.'.basename($target).'.b1152-'.bin2hex(random_bytes(8)).'.tmp';}
    public static function isOwnTemp(string $path):bool {return preg_match('~/\.[A-Za-z0-9_.-]+\.b1152-[a-f0-9]{16}\.tmp$~D',$path)===1;}

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
