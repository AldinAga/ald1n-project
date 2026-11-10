<?php
declare(strict_types=1);

/**
 * B1149 storefront rollout: ships the reviewed SVG-logo, SMTP-settings and product-gallery file set (SOURCE_COMMIT over the
 * live tree, which is the manifest + B1146 + B1147 + B1148 and equals main eca7755c under apps/cms/current; 16 new and 27
 * modified files, tests, CI and docs excluded) into the installed CMS release apps/cms/current. No migration, no composer
 * change, no .env, no config change. The Storefront is OPEN in production: its state is recorded, re-read before the first
 * change and around every smoke batch, and never written. SMTP settings are never read for their values, written or tested,
 * no mail is sent; only a hashed fingerprint of the effective mail transport is compared before and after.
 * Order: PREFLIGHT (read-only, collect-all; any blocker is a HOLD with nothing changed; PHP extensions, backups, 29-style
 * digest check of every modified file) -> per-file backup into the private B1149 namespace -> install in the reviewed
 * dependency order, one group at a time with a revalidation pause between:
 * A the 11 new classes, B changed services/support/commands, C controllers, D middleware, E routes/web.php, F bootstrap/app.php,
 * then the full measured hold and G the CSS and views (both stylesheets first, so a page never links a new URL whose CSS is still old; then public layout, catalog, product, then the admin views),
 * H bootstrap/providers.php LAST (it activates MailSettingsServiceProvider), each file via same-directory temp + fsync + rename
 * (link for a new file, so nothing is ever clobbered) -> route:clear, view:clear, queue:restart (never a cache build) ->
 * targeted per-file opcache_invalidate through token-gated temporary probes (the pinned probe takes at most 40 targets, so the
 * 43 files are probed in two partitions) -> wait max(realpath_cache_ttl, revalidate_freq) + 5 s -> smokes, fresh-code probe, CLI
 * feature check, mail-transport fingerprint, log scan, full tree verification.
 * Any failure after the first file restores the backed-up bytes of all 43 files in exact reverse install order (stopping at the
 * first failed restore, so the tree is always an exact install prefix): providers.php first, then the views, then route:clear +
 * view:clear and the full measured hold BEFORE bootstrap/app.php and routes/web.php go (a worker still holding a compiled admin
 * layout must never meet a missing route), pauses between the other groups, the full hold again before the new classes go; it
 * re-invalidates and smoke-tests the old state. A re-run converges from the saved phase.
 * @author Ald1n <https://ald1n.com>
 */
final class StorefrontInstallCoordinator
{
    public const MANIFEST_SHA='d737387e60a981b3b404e0980d9b70cb95b6e1d506a39211981d49dcd12be4c6';
    public const MANIFEST_FILES=8531;
    /** The tree after B1146 (manifest + 12 new), after B1147 (+ 3), after B1148 (+ 2) and after this rollout (+ 16 new). */
    public const B1146_TREE_FILES=8543;
    public const B1147_TREE_FILES=8546;
    public const B1148_TREE_FILES=8548;
    public const INSTALLED_TREE_FILES=8564;
    public const B1146_FILES=21;
    public const B1147_FILES=8;
    public const B1148_FILES=11;
    /** config/public_storefront.php after the B1145 unclamp: the staged original bytes. */
    public const CONFIG_SHA='0cfbc864a3d44822a9d14e80f6252e84eb208c9da5a25d5fe6989e4cad789818';
    public const B1148_REPORT_PATTERN='~^1113-B1148-GLASS-OVERLAY-\d{8}-\d{6}\.md$~D';
    public const B1148_PASS='PASS_INSTALLED_GLASS_OVERLAY_SHOP_STATE_UNCHANGED';
    public const RAN=71;
    /** The reviewed merged head (svg-logo + gallery + SMTP); the payload is git show SOURCE_COMMIT:<path>. */
    public const SOURCE_COMMIT='7e7481582fa0be3e95503a81b316be63836e508c';
    /** The pre-state: main eca7755c, whose apps/cms/current equals the live tree after B1148 (asserted at build time). */
    public const BASE_COMMIT='eca7755c6efe05143a6d3cd19f8c9e70b9ee3515';
    /** Main with Report1113 (docs only above BASE_COMMIT; asserted at build time). */
    public const MAIN_COMMIT='55d06b9cc010c976dfc56743763dbfeb09b87acf';
    /** The B1148 payload head (PR 155), the B1147 payload head (PR 154) and the B1146 main. */
    public const GLASS_COMMIT='560828458974a56a8f7173b986a40d46629e4d8b';
    public const CATALOG_COMMIT='764bf5135ad6241d64f728e4b141cc2713dc6c48';
    public const LEGAL_COMMIT='eabe16af8c19fade882e25c70425eb43c3e17ed9';
    /**
     * Install order (owner-coordinator mandated; a wrong order 500s the site): A the new classes (no caller in the old tree);
     * B changed services/support/commands (PublicStorefrontAppearance::staleThemeCookie before PublicNoScriptCsp,
     * ProductImageDerivativeService::exifOrientation before PublicProductImages callers); C controllers; D middleware
     * (SecurityHeaders, then PublicNoScriptCsp); E routes/web.php (needs SiteLogoAssetService::FILE_PATTERN); F bootstrap/app.php;
     * G the views and CSS after the full hold (layouts/app needs the route admin.settings.smtp.index); H providers.php last.
     */
    public const STEPS=[
        ['app/Services/SvgSanitizer.php','new','A'],['app/Services/SvgSanitizeResult.php','new','A'],['app/Services/SvgSanitizerException.php','new','A'],
        ['app/Services/SiteLogoAssetService.php','new','A'],['app/Rules/SiteLogoFile.php','new','A'],
        ['app/Services/SmtpMailSettingsService.php','new','A'],['app/Services/MailRuntimeConfigurator.php','new','A'],['app/Services/SmtpTestTransportFactory.php','new','A'],
        ['app/Providers/MailSettingsServiceProvider.php','new','A'],['app/Support/PublicProductImageFiles.php','new','A'],['app/Support/PublicProductImages.php','new','A'],
        ['app/Services/SettingsService.php','modified','B'],['app/Services/SiteAssetUrlService.php','modified','B'],['app/Services/ProductImageDerivativeService.php','modified','B'],
        ['app/Services/PublicRetailCatalogService.php','modified','B'],['app/Support/PublicStorefrontAppearance.php','modified','B'],['app/Services/PublicRegistrationMailSenderService.php','modified','B'],
        ['app/Console/Commands/DeploymentCheckCommand.php','modified','B'],['app/Console/Commands/MailDoctorCommand.php','modified','B'],['app/Console/Commands/OrderEmailsDoctorCommand.php','modified','B'],['app/Console/Commands/SendTestMailCommand.php','modified','B'],
        ['app/Http/Controllers/SiteAssetController.php','new','C'],['app/Http/Controllers/Admin/SmtpSettingsController.php','new','C'],['app/Http/Controllers/Admin/SiteAppearanceController.php','modified','C'],
        ['app/Http/Controllers/Admin/DocumentSettingsController.php','modified','C'],['app/Http/Controllers/Api/V1/Admin/SystemSettingsController.php','modified','C'],
        ['app/Http/Controllers/PublicThemeController.php','modified','C'],['app/Http/Controllers/PublicShopController.php','modified','C'],
        ['app/Http/Middleware/SecurityHeaders.php','modified','D'],['app/Http/Middleware/PublicNoScriptCsp.php','modified','D'],
        ['routes/web.php','modified','E'],
        ['bootstrap/app.php','modified','F'],
        ['public/assets/css/ald1n-public-gallery.css','new','G'],['public/assets/css/ald1n-public-storefront.css','modified','G'],
        ['resources/views/public-shop/layout.blade.php','modified','G'],['resources/views/public-shop/catalog.blade.php','modified','G'],['resources/views/public-shop/show.blade.php','modified','G'],
        ['resources/views/admin/settings/partials/svg-logo-panel.blade.php','new','G'],['resources/views/admin/settings/smtp.blade.php','new','G'],
        ['resources/views/layouts/app.blade.php','modified','G'],['resources/views/admin/settings/appearance.blade.php','modified','G'],['resources/views/admin/settings/index.blade.php','modified','G'],
        ['bootstrap/providers.php','modified','H'],
    ];
    public const LEGAL=['/pravne-informacije','/uslovi-prodaje','/politika-privatnosti','/podaci-o-trgovcu','/pravo-na-odustanak','/reklamacije','/dostava-i-placanje','/kolacici'];
    public const SHOP=['/katalog','/korpa','/porucivanje','/registracija'];
    public const SYSTEM=['/login','/up'];
    public const CSS_PATH='/assets/css/ald1n-public-storefront.css';
    public const GALLERY_CSS_PATH='/assets/css/ald1n-public-gallery.css';
    public const THEME_PATH='/tema';
    public const SMTP_PATH='/admin/settings/smtp';
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
    /** Worst-case wall time of one liveness smoke after a group (5 pages at 0.7 s pacing, 25 s timeout each, no 429 waiting). */
    private const LIVENESS_SECONDS=60;
    /** Upper bound of the requests in one smoke batch: 2 system, 8 legal, 4 CSS, 2 /tema, 1 SMTP page, 1 logo, 12 shop, 1 origin Vary, spare. */
    private const SMOKE_GETS=34;
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
    private array $markers;
    private string $cssVersion;
    private string $oldCssVersion;
    private string $galleryVersion;
    public string $final='HOLD';

    public function __construct(private string $root,private string $home,array $package,private string $incoming,private string $mode='install',private bool $ignoreSchedule=false)
    {
        if($ignoreSchedule&&$mode!=='rollback')throw new RuntimeException('OPERATOR_SCHEDULE_OVERRIDE');
        if($root!=='/home/icaffeco/ald1n-project'||$home!=='/home/icaffeco'||realpath($root)!==$root||realpath($home)!==$home||!function_exists('posix_geteuid')||!extension_loaded('curl')||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('HOST_PROFILE');
        $this->base=$home.'/.ald1n-b1149-state';$this->pointer=$this->base.'/current.json';$this->stage=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU';$this->b1146Base=$home.'/.ald1n-b1146-state';$this->b1147Base=$home.'/.ald1n-b1147-state';$this->b1148Base=$home.'/.ald1n-b1148-state';
        $this->appStateScript=$incoming.'/b1145-app-state.php';$this->routesScript=$incoming.'/b1146-app-check.php';$this->featureScript=$incoming.'/b1149-app-check.php';$this->web=new StorefrontWeb();
        $this->load($package,$incoming);putenv('GIT_OPTIONAL_LOCKS=0');
        if(!in_array($mode,['install','rollback'],true))throw new RuntimeException('OPERATOR_MODE');
    }

    private function load(array $package,string $incoming):void
    {
        $this->plan=self::plan($package,$incoming);$this->b1146=self::b1146Plan($package);$this->b1147=self::b1147Plan($package);$this->b1148=self::b1148Plan($package);$this->markers=self::markers($package);
        foreach(['cssVersion','oldCssVersion','galleryVersion'] as $k){$v=$package[$k]??null;if(!is_string($v)||!preg_match('/^[0-9]{1,12}$/D',$v))throw new RuntimeException('CSS_VERSION_SCOPE');$this->$k=$v;}
        if($this->cssVersion===$this->oldCssVersion)throw new RuntimeException('CSS_VERSION_UNCHANGED');
    }

    /** The overlay plan from b1149-package.json must be exactly STEPS (paths, kinds, groups, order) with digests and a payload. */
    public static function plan(array $package,string $incoming):array
    {
        $overlay=$package['overlay']??null;if(!is_array($overlay)||count($overlay)!==count(self::STEPS)||($package['sourceCommit']??null)!==self::SOURCE_COMMIT||($package['baseCommit']??null)!==self::BASE_COMMIT||($package['mainCommit']??null)!==self::MAIN_COMMIT)throw new RuntimeException('OVERLAY_PLAN_SCOPE');$plan=[];
        foreach(array_values($overlay) as $i=>$e){
            [$path,$kind,$group]=self::STEPS[$i];
            if(!is_array($e)||($e['path']??null)!==$path||($e['kind']??null)!==$kind||($e['group']??null)!==$group||!preg_match('/^b1149-payload-\d{2}\.bin$/D',(string)($e['payload']??''))||!preg_match('/^[a-f0-9]{64}$/D',(string)($e['payloadSHA256']??''))||!is_int($e['size']??null))throw new RuntimeException('OVERLAY_PLAN_ENTRY');
            $pre=$e['preSHA256']??null;if(($kind==='new')!==($pre===null)||($pre!==null&&!preg_match('/^[a-f0-9]{64}$/D',$pre))||$pre===$e['payloadSHA256'])throw new RuntimeException('OVERLAY_PLAN_PRE_STATE');
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

    /** Build-time markers: present in the new bytes, absent from the old (package.php asserts both). */
    public static function markers(array $package):array
    {
        $m=$package['markers']??null;if(!is_array($m))throw new RuntimeException('MARKER_SCOPE');$out=[];
        foreach(['layout','css','gallery','catalog'] as $k){if(!is_string($m[$k]??null)||!preg_match('/^[A-Za-z0-9 ="._\[\]:()@,{}-]{6,80}$/D',$m[$k]))throw new RuntimeException('MARKER_SCOPE');$out[$k]=$m[$k];}
        return $out;
    }

    /**
     * The Cloudflare advice: the new layout links a NEW ?v= URL for the storefront CSS and the gallery CSS is new, so no CSS purge
     * is needed; /site-assets/* is purged (SVG logos are served from a versioned cache key and the route is new). After a rollback
     * the old CSS URLs and /site-assets/* are purged.
     */
    public function purgeStep(bool $rollback=false):string
    {
        $u='https://cms.ald1n.com'.self::CSS_PATH;
        if($rollback)return '1_CLOUDFLARE_PURGE_EXACTLY_'.$u.'?v='.$this->oldCssVersion.'_AND_'.$u.'_AND_https://cms.ald1n.com'.self::GALLERY_CSS_PATH.'_AND_https://cms.ald1n.com/site-assets/*_(THE_OLD_LAYOUT_LINKS_?v='.$this->oldCssVersion.'_WHICH_MAY_HAVE_CACHED_THE_NEW_CSS_WHILE_THE_FILES_WERE_CHANGING)';
        return '1_CLOUDFLARE_PURGE_https://cms.ald1n.com/site-assets/*_(CACHING_LEVEL_STANDARD_NO_QUERY_STRIPPING_FOR_/site-assets/*_NO_CACHE_EVERYTHING_FOR_/katalog*)_THE_STOREFRONT_CSS_HAS_A_NEW_URL_'.$u.'?v='.$this->cssVersion.'_AND_THE_GALLERY_CSS_IS_NEW_PURGE_THEM_ONLY_IF_A_PAGE_LOOKS_UNSTYLED_OR_OLD';
    }

    private function open():void
    {
        if($this->lock!==null)return;if(!is_dir($this->base)&&!mkdir($this->base,0700))throw new RuntimeException('PRIVATE_STATE_CREATE');
        if(is_link($this->base)||realpath($this->base)!==$this->base||(fileperms($this->base)&0777)!==0700||fileowner($this->base)!==posix_geteuid())throw new RuntimeException('PRIVATE_STATE_CONFINEMENT');
        $lock=fopen($this->base.'/operator.lock','c');if($lock===false||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('ANOTHER_B1149_RUN_ACTIVE');chmod($this->base.'/operator.lock',0600);$this->lock=$lock;
    }

    /** No B1146, B1147 or B1148 run holds its operator lock (read-only: each lock file is opened for reading and only probed). */
    private function earlierIdle():void
    {
        foreach(['B1146'=>$this->b1146Base,'B1147'=>$this->b1147Base,'B1148'=>$this->b1148Base] as $label=>$dir){
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
        $this->atomicJSON($this->pointer,['schemaVersion'=>1,'kind'=>'B1149','private'=>$this->state['private'],'id'=>$this->state['id']]);$this->emit('B1149_PHASE',$phase);$this->fault('SAVED_'.$phase);
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

    /** One read-only helper (b1145 state, b1146 routes/approval, b1149 features/environment); JSON facts only. */
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

    /** The live tree before this rollout: the manifest with the 21 B1146, the 8 B1147 and the 11 B1148 files (8548 entries). */
    private function b1148Manifest():array
    {
        $files=self::applied($this->manifest()['files'],array_values($this->b1146),'B1146_MANIFEST');if(count($files)!==self::B1146_TREE_FILES)throw new RuntimeException('B1146_MANIFEST_COUNT');
        $files=self::applied($files,array_values($this->b1147),'B1147_MANIFEST');if(count($files)!==self::B1147_TREE_FILES)throw new RuntimeException('B1147_MANIFEST_COUNT');
        $files=self::applied($files,array_values($this->b1148),'B1148_MANIFEST');if(count($files)!==self::B1148_TREE_FILES)throw new RuntimeException('B1148_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** The tree after this rollout (8564 entries). */
    private function installedManifest():array
    {
        $changes=array_map(static fn($s)=>['path'=>$s['path'],'kind'=>$s['kind'],'sha256'=>$s['payloadSHA256'],'preSHA256'=>$s['preSHA256']],$this->plan);
        $files=self::applied($this->b1148Manifest()['files'],$changes,'B1149_MANIFEST');if(count($files)!==self::INSTALLED_TREE_FILES)throw new RuntimeException('B1149_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** Last value of every KEY=value line of the B1148 terminal report (Report1113). */
    public static function b1148Facts(string $bytes):array
    {
        if(str_contains($bytes,"\r")||strlen($bytes)>4194304)throw new RuntimeException('B1148_REPORT_FORMAT');$last=[];
        foreach(explode("\n",$bytes) as $line)if(preg_match('/^([A-Z0-9_]+)=(.*)$/D',$line,$m))$last[$m[1]]=$m[2];
        if(($last['B1148_FINAL']??null)!==self::B1148_PASS||($last['EXIT']??null)!=='0'||json_decode($last['B1148_PHASE']??'null',true)!=='INSTALLED_PASS')throw new RuntimeException('B1148_REPORT_NOT_TERMINAL_PASS');
        return ['final'=>$last['B1148_FINAL'],'phase'=>'INSTALLED_PASS','exit'=>0];
    }

    /** Chain: exactly one Report1113 of B1148 with its terminal PASS, the live B1148 state INSTALLED_PASS, no B1146/B1147/B1148 run. */
    private function chain():array
    {
        $directory=$this->root.'/docs/operations';$found=[];foreach(scandir($directory)?:[] as $name)if(preg_match(self::B1148_REPORT_PATTERN,$name))$found[]=$name;
        if(count($found)!==1)throw new RuntimeException(count($found)===0?'B1148_REPORT1113_MISSING':'B1148_REPORT1113_AMBIGUOUS');$path=$directory.'/'.$found[0];
        if(is_link($path)||realpath($path)!==$path||!is_file($path))throw new RuntimeException('B1148_REPORT1113_SCOPE');$bytes=(string)file_get_contents($path);$facts=self::b1148Facts($bytes);
        $pointer=$this->b1148Base.'/current.json';if(is_link($pointer)||!is_file($pointer)||filesize($pointer)>8192)throw new RuntimeException('B1148_POINTER_MISSING');$p=json_decode((string)file_get_contents($pointer),true,8,JSON_THROW_ON_ERROR);
        if(!is_array($p)||($p['kind']??null)!=='B1148'||!preg_match('/^[a-f0-9]{32}$/D',(string)($p['id']??''))||($p['private']??null)!==$this->b1148Base.'/'.$p['id'])throw new RuntimeException('B1148_POINTER_SCOPE');
        $statePath=$p['private'].'/state.json';if(is_link($statePath)||realpath($statePath)!==$statePath||filesize($statePath)>16777216)throw new RuntimeException('B1148_STATE_MISSING');$stateBytes=(string)file_get_contents($statePath);$s=json_decode($stateBytes,true,512,JSON_THROW_ON_ERROR);
        if(!is_array($s)||($s['kind']??null)!=='B1148'||($s['id']??null)!==$p['id']||($s['phase']??null)!=='INSTALLED_PASS')throw new RuntimeException('B1148_STATE_NOT_INSTALLED_PASS');
        $retained=$s['chain']['retained']??null;$htaccess=$s['chain']['htaccessSHA256']??null;
        if(!is_string($retained)||!preg_match('~^/[A-Za-z0-9._/-]+/retained$~D',$retained)||!is_string($htaccess)||!preg_match('/^[a-f0-9]{64}$/D',$htaccess))throw new RuntimeException('B1148_STATE_FACTS');
        $this->earlierIdle();
        return ['report'=>'docs/operations/'.$found[0],'reportSHA256'=>hash('sha256',$bytes),'b1148Id'=>$p['id'],'b1148StateSHA256'=>hash('sha256',$stateBytes),'retained'=>$retained,'htaccessSHA256'=>$htaccess]+$facts;
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

    /** This overlay adds a route: a route cache would hide it (and this tool never clears or builds one). */
    private function requireNoRouteCache():void {if($this->routeCacheFiles()!==[])throw new RuntimeException('ROUTE_CACHE_PRESENT_THE_NEW_ROUTES_WOULD_BE_INVISIBLE');}

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

    /** Leftover temp files of this tool, and of the B1147 tool (an interrupted earlier run must be looked at first). */
    private function ownTemps():array
    {
        $found=[];$dirs=[];foreach($this->plan as $s)$dirs[dirname($this->target($s))]=true;foreach(array_merge($this->b1147,$this->b1148) as $e)$dirs[dirname($this->active().'/'.$e['path'])]=true;
        foreach(array_keys($dirs) as $d)if(is_dir($d))foreach(scandir($d)?:[] as $name)if(StorefrontFiles::isOwnTemp($d.'/'.$name)||CatalogFiles::isOwnTemp($d.'/'.$name)||GlassFiles::isOwnTemp($d.'/'.$name))$found[]=$d.'/'.$name;return $found;
    }

    private function cleanupRecorded():void
    {
        $changed=false;
        foreach($this->state['probes']??[] as $i=>$probe){if(($probe['removed']??false)===true)continue;FreshnessProbe::removeStaging($probe['staging']);FreshnessProbe::remove($this->active().'/public',$probe['name'],$probe['sha256']);$this->state['probes'][$i]['removed']=true;$changed=true;}
        foreach($this->state['temps']??[] as $i=>$temp){if(StorefrontFiles::isOwnTemp($temp)&&is_file($temp)&&!is_link($temp)&&!unlink($temp))throw new RuntimeException('TEMP_FILE_REMOVE');unset($this->state['temps'][$i]);$changed=true;}
        if($changed){$this->state['temps']=array_values($this->state['temps']??[]);$this->persist();}
    }

    private function recordTemp(string $temp):string {$this->state['temps'][]=$temp;$this->persist();return $temp;}

    /**
     * absolute path => expected sha for the probe: all 43 targets ('installed' or 'pre' bytes, null = absent), among them
     * bootstrap/app.php, providers.php, the middleware and routes/web.php. Only the PHP sources (and the compiled views)
     * can be held by OPcache; the probe invalidates exactly those, one file at a time (never a global reset).
     */
    private function probeTargets(bool $installed):array
    {
        $t=[];foreach($this->plan as $s){$dir=realpath(dirname($this->target($s)));$path=($dir===false?dirname($this->target($s)):$dir).'/'.basename($s['path']);$t[$path]=$installed?$s['payloadSHA256']:$s['preSHA256'];}
        return $t;
    }

    /**
     * The probe request(s) for all 43 targets. The pinned B1146 OverlayProbe accepts at most 40 targets per probe file, so the
     * targets are probed in two partitions (22 + 21), each its own token-gated temporary file and request, assessed
     * collect-all and merged; every probe file is removed before the next one is written.
     */
    private function probeRun(string $label,bool $installed,bool $invalidate,bool $requireFresh,bool $collect=false):array
    {
        $this->cleanupRecorded();FreshnessProbe::assertNoLeftover($this->active().'/public');$all=$this->probeTargets($installed);$route='UNKNOWN';$merged=null;$failures=[];$gates=[];
        foreach(array_chunk($all,(int)ceil(count($all)/2),true) as $part=>$targets){
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
        $result=['label'=>$label,'route'=>$route,'invalidationRequested'=>$invalidate,'requireFresh'=>$requireFresh,'partitions'=>2,'gates'=>$gates]+$merged+['probeRemoved'=>true,'failures'=>$failures];$this->emit('WEB_OVERLAY_PROBE',$result);
        if(!$collect&&$failures!==[])throw new RuntimeException($failures[0]);return $result;
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

    private static function throttled(string $path):bool {return in_array($path,self::LEGAL,true)||in_array($path,self::SHOP,true)||str_starts_with($path,'/katalog/');}

    /**
     * One paced request with up to 4 attempts on a transport error, 429 or an edge/gateway status. A 429 waits Retry-After
     * (6 s when absent, at most RETRY_AFTER_CAP, within the per-batch 429 budget); after one throttled GET stays 429 the
     * shared limiter counts as exhausted and the remaining throttled GETs of the batch are skipped without a request.
     */
    private function get(string $path,array $query,?string $marker,string $method='GET',bool $origin=false):array
    {
        $throttled=self::throttled($path);
        if($throttled&&$this->limitBatch['limited'])return ['status'=>429,'transport'=>false,'robots'=>[],'csp'=>[],'frame'=>[],'vary'=>[],'cache'=>[],'nosniff'=>[],'location'=>null,'cfRay'=>false,'cfCache'=>null,'contentType'=>null,'retryAfter'=>null,'body'=>'','bytes'=>0,'attempts'=>0,'rateLimited'=>true,'waitedSeconds'=>0,'skippedSharedLimiter'=>true];
        $waited=0;$limited=false;
        for($attempt=1;$attempt<=4;$attempt++){
            if($this->paceMicros>0)usleep($this->paceMicros);
            $r=$this->web->fetch($path,$query,$marker,$method,$origin);$limited=!$r['transport']&&$r['status']===429;
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
        return max(1800,5*$holdSeconds+2*$this->smokeCapSeconds()+14*$this->groupPause()+7*self::LIVENESS_SECONDS+300);
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
        if($shop['open']){$c=$one('/katalog','/katalog','GET',200,true);$p=$c!==null&&$c['status']===200?StorefrontWeb::productPath($c['body']):null;if($p!==null)$one('PRODUCT_PAGE_FIRST_CATALOG_LINK',$p,'GET',200,true);}
        $one('GET /tema',self::THEME_PATH,'GET',405,false);
        $judged=$failed;if($failed!==[]){try{if($this->shop()!==$shop)$judged=[];}catch(Throwable){$judged=[];}}
        $this->emit('GROUP_LIVENESS_SMOKE',['after'=>$after,'shop'=>$shop,'rows'=>$rows,'failed'=>$judged,'rateLimited'=>$limited,'assertions'=>'LIVENESS_ONLY_NO_NEW_FEATURE']);
        if($judged!==[])throw new RuntimeException('GROUP_SMOKE_FAILED_AFTER_'.$after);
    }

    /**
     * Public smokes; no product, cart, account, theme or setting is written, nobody logs in (GET and one HEAD only). The
     * expectations follow the shop state read right before this batch (never changed by this tool) and $installed (new
     * B1149 code) or not (old = B1148 code):
     * always: /login and /up 200; the 8 legal pages 200 with DENY and noindex; their CSP is script-src 'none' with no script
     * in the page when old, and exactly one nonce'd script (script-src and script-src-elem 'nonce-<same value as the
     * <script nonce> of the page>', script-src-attr 'none', no 'self'/'unsafe-inline'/'strict-dynamic') when installed; the
     * layout marker exactly when installed; the storefront CSS (cache-busted) with its new rule exactly when installed, the
     * exact versioned URL the new layout links (installed only, never fetched before the install) and the new gallery CSS
     * (200 with its marker installed, 404 old); GET and HEAD /tema 405 in both states (the POST-only route since B1148);
     * the admin SMTP page for a GUEST: 302 to /login installed, 404 old (never logged in, never read, nothing sent).
     * Owner policy: the shop is never indexed. The server-wide public/.htaccess sets X-Robots-Tag noindex on every response;
     * every public shop and legal page must carry it in both states, else NOINDEX_POLICY_HEADER_MISSING (an edge/server fact:
     * preflight HOLDs, an installed acceptance HOLDs with the exact files kept, a rollback completes with an owner notice).
     * shop ON: /katalog 200 with the CSP shape of the state, the layout marker and the gallery stylesheet link exactly when
     * installed, one parsable CollectionPage JSON-LD block, Cache-Control private and no-store (installed only: new in B1149, the old state is just recorded), installed
     * Vary: Cookie (if the edge hides the header the origin is asked once over loopback TLS: origin without it FAILS, origin
     * unreachable is UNVERIFIED); ?sort=price_asc 200, an unknown sort 302/422, ?page=999 404, /korpa 200, /registracija 200,
     * /porucivanje 302 or 404, one product page (the first /katalog/<slug> link) 200 with the CSP shape, private/no-store and
     * the gallery link; installed: the logo URL the catalog page links under /site-assets/ answers 200 with nosniff, a
     * sandboxed CSP (default-src 'none'; sandbox) and noindex (no logo link: INFO, never a failure).
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
        $shape=static fn(array $r):bool=>$installed?StorefrontWeb::nonceScript($r['csp'],$r['body']):(CatalogWeb::scriptless($r['csp'])&&StorefrontWeb::scriptCount($r['body'])===0);
        foreach(self::SYSTEM as $p){$r=$this->get($p,[],$marker);$add($p,$r,'200',$ok($r,200));}
        foreach(self::LEGAL as $p){
            $r=$this->get($p,[],$marker);$f=['cspShape'=>$shape($r),'frameDeny'=>CatalogWeb::frameDeny($r['frame']),'layoutMarker'=>str_contains($r['body'],$m['layout'])];
            $add($p,$r,'200_CSP_DENY_LAYOUT_'.($installed?'NEW_NONCE_SCRIPT':'OLD_SCRIPTLESS'),$ok($r,200)&&$f['cspShape']&&$f['frameDeny']&&$f['layoutMarker']===$installed,$f,true);
        }
        $this->fault('DURING_SMOKES_'.$state);
        $css=function(string $path,array $query,?string $mk,string $label,bool $expectNew,string $needle)use(&$rows,&$inconclusive,$add):void{
            $r=$this->get($path,$query,$mk);$has=str_contains($r['body'],$needle);$okay=!$r['transport']&&$r['status']===200;
            if($okay&&$has!==$expectNew&&CatalogWeb::edgeCached($r['cfCache'])){$inconclusive[]=$label;$rows[]=['check'=>$label,'expected'=>'200_CSS_'.($expectNew?'NEW':'OLD'),'observed'=>$r['status'],'result'=>'INCONCLUSIVE_EDGE_CACHED_COPY','cfCache'=>$r['cfCache'],'cssMarker'=>$has,'attempts'=>$r['attempts']];}
            else $add($label,$r,'200_CSS_'.($expectNew?'NEW':'OLD'),$okay&&$has===$expectNew,['cfCache'=>$r['cfCache'],'cssMarker'=>$has]);
        };
        $css(self::CSS_PATH,[],$marker,self::CSS_PATH,$installed,$m['css']);
        if($installed){
            $css(self::CSS_PATH,['v'=>$this->cssVersion],null,self::CSS_PATH.'?v='.$this->cssVersion,true,$m['css']);
            $css(self::GALLERY_CSS_PATH,[],$marker,self::GALLERY_CSS_PATH,true,$m['gallery']);
            $css(self::GALLERY_CSS_PATH,['v'=>$this->galleryVersion],null,self::GALLERY_CSS_PATH.'?v='.$this->galleryVersion,true,$m['gallery']);
        }else{$r=$this->get(self::GALLERY_CSS_PATH,[],$marker);$add(self::GALLERY_CSS_PATH,$r,'404_OLD',$ok($r,404));}
        // The theme route exists since B1148 and accepts POST only: GET and HEAD are answered 405 by the router in both states.
        foreach(['GET','HEAD'] as $method){$r=$this->get(self::THEME_PATH,[],$marker,$method);$add($method.' '.self::THEME_PATH,$r,'405_NEVER_5XX',$ok($r,405));}
        // The SMTP admin page for a guest: never logged in. Installed = the auth middleware redirects to the login page; old = no route.
        $r=$this->get(self::SMTP_PATH,[],$marker);$loc=(string)($r['location']??'');$f=['location'=>$loc===''?null:(str_contains($loc,'/login')?'LOGIN':'OTHER')];
        $add(self::SMTP_PATH,$r,$installed?'302_TO_LOGIN_GUEST':'404_GUEST',$installed?($ok($r,302)&&$f['location']==='LOGIN'):$ok($r,404),$f);
        $product=null;$logo=null;
        if($shop['open']){
            $r=$this->get('/katalog',[],$marker);$vary=StorefrontWeb::varyCookie($r['vary']);
            $f=['cspShape'=>$shape($r),'layoutMarker'=>str_contains($r['body'],$m['layout']),'galleryLink'=>str_contains($r['body'],$m['catalog']),'cacheControlPrivateNoStore'=>StorefrontWeb::privateNoStore($r['cache']),'jsonLd'=>CatalogWeb::jsonLdProblem($r['body'])??'PASS','varyCookie'=>$vary];
            $pass=$ok($r,200)&&$f['cspShape']&&$f['layoutMarker']===$installed&&$f['galleryLink']===$installed&&(!$installed||$f['cacheControlPrivateNoStore'])&&$f['jsonLd']==='PASS';
            if($pass&&$installed&&!$vary){
                // The edge may rewrite Vary: the origin itself is asked once over loopback TLS (the B1144 probe route).
                if(($this->state['probeRoute']??null)==='LOOPBACK_TLS_ORIGIN'){$o=$this->get('/katalog',[],$marker,'GET',true);$f['originVaryCookie']=!$o['transport']&&$o['status']===200&&StorefrontWeb::varyCookie($o['vary']);$f['originObserved']=$o['transport']?'TRANSPORT':$o['status'];
                    if($f['originVaryCookie'])$notices[]='VARY_COOKIE_SENT_BY_THE_ORIGIN_BUT_NOT_VISIBLE_THROUGH_THE_EDGE';elseif(!$o['transport']&&$o['status']===200)$pass=false;else $unverifiedVary=true;}
                else $unverifiedVary=true;
            }
            $add('/katalog',$r,'200_CSP_JSON_LD_LAYOUT_PRIVATE_NO_STORE_'.($installed?'NEW_VARY_COOKIE_GALLERY':'OLD'),$pass,$f,true);
            if($ok($r,200)){$product=StorefrontWeb::productPath($r['body']);$logo=$installed?StorefrontWeb::siteAssetUrl($r['body']):null;}
            $r=$this->get('/katalog',['sort'=>'price_asc'],$marker);$add('/katalog?sort=price_asc',$r,'200',$ok($r,200),[],true);
            $r=$this->get('/katalog',['sort'=>'ald1n_unknown'],$marker);$add('/katalog?sort=ald1n_unknown',$r,'302_OR_422',!$r['transport']&&in_array($r['status'],[302,422],true),[],true);
            $r=$this->get('/katalog',['page'=>'999'],$marker);$add('/katalog?page=999',$r,'404',$ok($r,404),[],true);
            $r=$this->get('/korpa',[],$marker);$f=['layoutMarker'=>str_contains($r['body'],$m['layout']),'cspShape'=>$shape($r)];$add('/korpa',$r,'200_CSP_LAYOUT_'.($installed?'NEW':'OLD'),$ok($r,200)&&$f['layoutMarker']===$installed&&$f['cspShape'],$f,true);
            $r=$this->get('/registracija',[],$marker);$add('/registracija',$r,'200',$ok($r,200),[],true);
            $r=$this->get('/porucivanje',[],$marker);$add('/porucivanje',$r,'302_OR_404',!$r['transport']&&in_array($r['status'],[302,404],true));
            if($product!==null){$r=$this->get($product,[],$marker);$f=['cspShape'=>$shape($r),'layoutMarker'=>str_contains($r['body'],$m['layout']),'galleryLink'=>str_contains($r['body'],$m['catalog']),'cacheControlPrivateNoStore'=>StorefrontWeb::privateNoStore($r['cache'])];
                $add('PRODUCT_PAGE_FIRST_CATALOG_LINK',$r,'200_CSP_LAYOUT_PRIVATE_NO_STORE_'.($installed?'NEW_GALLERY':'OLD'),$ok($r,200)&&$f['cspShape']&&$f['layoutMarker']===$installed&&$f['galleryLink']===$installed&&(!$installed||$f['cacheControlPrivateNoStore']),$f,true);}
            else $rows[]=['check'=>'PRODUCT_PAGE_FIRST_CATALOG_LINK','result'=>'INFO_NO_PRODUCT_LINK_ON_THE_CATALOG_PAGE_NOT_CHECKED'];
        }else foreach(self::SHOP as $p){$r=$this->get($p,[],$marker);$add($p,$r,'404_SHOP_OFF',$ok($r,404),[],$p!=='/porucivanje');}
        if($installed&&$shop['open']){
            if($logo!==null){
                // The very URL the catalog links (with its ?v= when it has one; otherwise with the release marker).
                $r=$this->get($logo['path'],$logo['query'],$logo['query']===[]?$marker:null);$okay=$ok($r,200)&&StorefrontWeb::nosniff($r['nosniff'])&&StorefrontWeb::sandboxCsp($r['csp'])&&str_starts_with(strtolower((string)$r['contentType']),'image/');
                $f=['nosniff'=>StorefrontWeb::nosniff($r['nosniff']),'sandboxCsp'=>StorefrontWeb::sandboxCsp($r['csp']),'contentTypeImage'=>str_starts_with(strtolower((string)$r['contentType']),'image/'),'cfCache'=>$r['cfCache']];
                if($ok($r,200)&&!$okay&&CatalogWeb::edgeCached($r['cfCache'])){$inconclusive[]='SITE_ASSET_LOGO';$this->state['assetsEdgeStale']=true;$rows[]=['check'=>'SITE_ASSET_LOGO','expected'=>'200_NOSNIFF_SANDBOX_CSP','observed'=>200,'result'=>'INCONCLUSIVE_EDGE_CACHED_COPY']+$f;}
                else $add('SITE_ASSET_LOGO',$r,'200_NOSNIFF_SANDBOX_CSP_IMAGE',$okay,$f,true);
            }else $rows[]=['check'=>'SITE_ASSET_LOGO','result'=>'INFO_NO_/site-assets/_LOGO_LINK_ON_THE_CATALOG_PAGE_NOT_CHECKED_(THE_HEADER_SHOWS_A_LOGO_ONLY_WHEN_ONE_IS_SET)'];
        }
        // A failed check is judged against the shop state of the whole batch: if the owner changed it meanwhile, nothing is proven.
        $shopAfter=null;if($failed!==[]){try{$shopAfter=$this->shop();}catch(Throwable $e){$shopAfter=['unreadable'=>self::code($e)];}}
        $this->emit('PUBLIC_HTTP_SMOKES',['state'=>$state,'shop'=>$shop,'shopAfterFailure'=>$shopAfter,'rows'=>$rows,'failed'=>$failed,'inconclusive'=>$inconclusive,'rateLimited'=>$limited,'rateLimitWaitedSeconds'=>$this->limitBatch['waited'],'batch'=>$retried?2:1,'noindexPolicy'=>'OWNER_POLICY_NO_INDEXING_X_ROBOTS_TAG_NOINDEX_REQUIRED_ON_PUBLIC_SHOP_AND_LEGAL_PAGES_SENT_BY_THE_SERVER_WIDE_PUBLIC_HTACCESS','noindexMissing'=>$noindexMissing,'notices'=>$notices,'varyUnverified'=>$unverifiedVary,'requestsWrite'=>'NONE_GET_AND_HEAD_ONLY','login'=>'NEVER_LOGGED_IN','logo'=>'DATA_DEPENDENT_INFO_WHEN_NO_LINK']);
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

    /** The 11 B1146 route names stay registered and no route cache exists (before, during and after; never created or cleared). */
    private function routesCheck():array
    {
        $r=$this->helper($this->routesScript,['routes']);$now=$this->routeCacheFiles();$this->emit('ROUTES_CHECK',$r+['routeCacheFiles'=>count($now)]);
        if($r['b1146RouteNamesPresent']!==self::B1146_ROUTE_NAMES||$r['storefrontSettingsRoute']!==true)throw new RuntimeException('B1146_ROUTES_NOT_ALL_PRESENT');
        if($r['routesCached']!==false||$now!==[])throw new RuntimeException('ROUTE_CACHE_PRESENT_THE_NEW_ROUTES_WOULD_BE_INVISIBLE');return $r;
    }

    /**
     * The new code as the CLI sees it, two ways: `artisan route:list --name=<route> --json` (read-only) and the b1149 helper
     * (the 11 new classes autoload, the changed static APIs exist, the routes site-assets.show / admin.settings.smtp.index
     * exist, MailSettingsServiceProvider is registered, plus the B1148 theme route that must stay). Installed: everything
     * present; old: everything new absent and the theme route present.
     */
    private function featureCheck(bool $installed):array
    {
        $h=$this->helper($this->featureScript,['features']);$hits=[];
        foreach(['site-assets.show','admin.settings.smtp.index','public-shop.theme'] as $name){
            $r=$this->artisan(['route:list','--name='.$name,'--json'],120);if($r['rc']!==0)throw new RuntimeException('ROUTE_LIST_FAILED');$list=json_decode(trim($r['stdout']),true,16);$n=0;
            if(is_array($list))foreach($list as $row)if(is_array($row)&&($row['name']??null)===$name)$n++;$hits[$name]=$n;
        }
        $facts=$h+['routeList'=>$hits,'expectInstalled'=>$installed];$this->emit('FEATURE_CLI_CHECK',$facts);
        $new=[$h['newClassesAutoloaded'],$h['changedStaticApis'],$h['routeSiteAssets'],$h['routeSmtpIndex'],$h['providerRegistered'],$hits['site-assets.show']===1,$hits['admin.settings.smtp.index']===1];
        if($h['themeRoutePostOnly']!==true||$hits['public-shop.theme']!==1||$h['routesCached']!==false)throw new RuntimeException('B1148_THEME_ROUTE_MISSING');
        if($installed&&in_array(false,$new,true))throw new RuntimeException('NEW_CLASSES_ROUTES_OR_PROVIDER_NOT_PRESENT_AFTER_INSTALL');
        if(!$installed&&($h['newClassesAutoloaded']||$h['routeSiteAssets']||$h['routeSmtpIndex']||$h['providerRegistered']||$hits['site-assets.show']!==0||$hits['admin.settings.smtp.index']!==0))throw new RuntimeException('NEW_CLASSES_ROUTES_OR_PROVIDER_PRESENT_IN_THE_OLD_STATE');
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
    private function viewHold():int
    {
        return max(1,min(FreshnessProbe::MAX_HOLD_SECONDS,(int)($this->state['holdSeconds']??FreshnessProbe::MAX_HOLD_SECONDS)));
    }

    /**
     * CLI, read-only, in its own uncached process (the pinned-with-the-package b1149-app-check.php env mode): the PHP extensions the
     * new code needs (dom, libxml, fileinfo, mbstring and one of gd/imagick), APP_URL is an origin the shipped catalog rule
     * accepts (JSON-LD), and a SHA-256 fingerprint of the effective mail transport (default mailer, transport, host, port,
     * encryption, a hash of the username; never the password, never a value). $label is only printed.
     */
    private function envCheck(string $label):array
    {
        $r=$this->helper($this->featureScript,['env',$this->mailKey()]);$this->emit('ENVIRONMENT_CLI_CHECK',['label'=>$label]+array_diff_key($r,['mailFingerprintHMAC'=>true]));
        foreach(self::EXTENSIONS as $x)if(($r['extensions'][$x]??false)!==true)throw new RuntimeException('PHP_EXTENSION_MISSING_IN_CLI_'.strtoupper($x));
        if(($r['extensions']['gd']??false)!==true&&($r['extensions']['imagick']??false)!==true)throw new RuntimeException('PHP_EXTENSION_MISSING_IN_CLI_GD_OR_IMAGICK');
        return $r;
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
        $now=$this->helper($this->featureScript,['env',$this->mailKey()])['mailFingerprintHMAC']??null;$before=$this->state['mailFingerprintHMAC']??null;
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
        $chain=$try('chainB1148Report1113',fn()=>$this->chain());if($chain!==null)$this->state['chain']=$chain;$v['chain']=$chain;
        $manifest=$try('runtimeManifestPlusB1146PlusB1147PlusB1148',fn()=>$this->b1148Manifest());
        $targets=$try('overlayTargets',fn()=>$this->targets());$v['targets']=$targets;
        if($targets!==null){if(!$installed&&!$targets['preState'])$g['overlayTargets']=$targets['installed']?'OVERLAY_ALREADY_PRESENT_WITHOUT_B1149_STATE_NO_BACKUP_NOTHING_CHANGED':($targets['link']!==[]?'TARGET_IS_SYMLINK':'UNKNOWN_PRE_STATE_REFUSED');if($installed&&!$targets['installed'])$g['overlayTargets']='INSTALLED_DIGESTS_CHANGED';}
        $v['tree']=$manifest!==null&&$chain!==null?$try('activeTreeEqualsManifestPlusB1146PlusB1147PlusB1148',fn()=>$installed?LeanGates::verifyTree($this->active(),$this->installedManifest(),self::CONFIG_SHA,$chain['htaccessSHA256'],self::INSTALLED_TREE_FILES):LeanGates::verifyTree($this->active(),$manifest,self::CONFIG_SHA,$chain['htaccessSHA256'],self::B1148_TREE_FILES)):null;
        if(!array_key_exists('activeTreeEqualsManifestPlusB1146PlusB1147PlusB1148',$g))$g['activeTreeEqualsManifestPlusB1146PlusB1147PlusB1148']='NOT_EVALUATED_CHAIN_OR_MANIFEST';
        if(!$installed)$v['replaceable']=$try('targetsReplaceable',function():array{$n=0;foreach($this->plan as $s){if($s['kind']==='modified'){StorefrontFiles::replaceable($this->target($s));$n++;}else{$d=dirname($this->target($s));while(!is_dir($d))$d=dirname($d);if(is_link($d)||!is_writable($d))throw new RuntimeException('TARGET_DIRECTORY_NOT_WRITABLE');}}return ['modifiedOneLinkNoAcl'=>$n];});
        // The 16 new files are found through the PSR-4 fallback; an authoritative classmap would make them invisible.
        $g['composerClassmapNotAuthoritative']=$this->classmapBlocker();
        $g['configNotCached']=file_exists($this->active().'/bootstrap/cache/config.php')?'CONFIG_CACHE_PRESENT':null;
        $g['noRouteCache']=$this->routeCacheFiles()===[]?null:'ROUTE_CACHE_PRESENT_THE_NEW_ROUTES_WOULD_BE_INVISIBLE_NOTHING_CHANGED';
        $g['noMaintenanceMode']=file_exists($this->active().'/storage/framework/down')||file_exists($this->active().'/storage/framework/maintenance.php')?'MAINTENANCE_MODE_ACTIVE':null;
        $try('noLeftoverProbeOrTemp',function():void{FreshnessProbe::assertNoLeftover($this->active().'/public');if($this->ownTemps()!==[])throw new RuntimeException('LEFTOVER_B1147_B1148_OR_B1149_TEMP_FILE');});
        $v['caches']=$try('cacheState',fn()=>['routeCacheFiles'=>count($this->routeCacheFiles()),'compiledViews'=>$this->compiledViews(),'viewsCached'=>$this->compiledViews()>0]);
        $v['logs']=LeanGates::dailyLogs($this->active().'/storage/logs');$g['laravelDailyLog']=$v['logs']['dailyFiles']>0?null:'LARAVEL_DAILY_LOG_MISSING';
        $v['migrations']=$try('migrateStatus71Ran0Pending',fn()=>$this->migrationStatus());
        $app=$try('appStateDatabase',fn()=>$this->helper($this->appStateScript,['state']));
        if($app!==null){
            $shop=self::shopOf($app);$v['shop']=$shop+['writtenByThisTool'=>false];$v['app']=['backupsRunning'=>$app['backups']['running']];
            $g['shopStateRecorded']=!$shop['known']?'SHOP_SWITCH_VALUE_UNKNOWN':(!$installed&&!$shop['open']?'SHOP_IS_OFF_B1149_INSTALLS_ONLY_WITH_THE_SHOP_ON_NOTHING_CHANGED':null);
            $g['noRunningBackup']=$app['backups']['table']===true&&$app['backups']['running']===0?null:'BACKUP_RUN_IN_PROGRESS_OR_TABLE_MISSING';
            if($this->scheduleGate){$s=$try('outsideScheduledBackupWindow',fn()=>$this->scheduleState($app));$v['schedule']=$s;if($s!==null&&$s['conflict']!==null)$g['outsideScheduledBackupWindow']='SCHEDULED_BACKUP_WITHIN_30_MINUTES_'.strtoupper((string)$s['conflict']['type']);}
        }else foreach(['shopStateRecorded','noRunningBackup','outsideScheduledBackupWindow'] as $gate)$g[$gate]='NOT_EVALUATED_APP_STATE';
        $env=$try('environmentCli',fn()=>$this->envCheck('preflight-'.$stage));$v['environment']=$env===null?null:array_diff_key($env,['mailFingerprintHMAC'=>true]);
        $g['appUrlHttpsOrigin']=$env===null?'NOT_EVALUATED_ENVIRONMENT_CLI':(($env['appUrlHttpsOrigin']??false)===true?null:'APP_URL_NOT_A_BARE_HTTPS_ORIGIN_JSON_LD_WOULD_BE_MISSING');
        if($env!==null&&!$installed)$this->state['mailFingerprintHMAC']=$env['mailFingerprintHMAC'];
        $v['features']=$try('featureCliState',fn()=>$this->featureCheck($installed));
        $v['routes']=$try('b1146LegalRoutesNoRouteCache',fn()=>$this->routesCheck());
        $v['headroom']=$try('diskHeadroom',fn()=>$this->headroom());
        $v['artisanCensus']=$chain!==null?$try('noRunningCmsArtisan',fn()=>$this->census()):null;if(!array_key_exists('noRunningCmsArtisan',$g))$g['noRunningCmsArtisan']='NOT_EVALUATED_CHAIN';
        if($targets!==null&&($targets['preState']||$targets['installed'])&&isset($this->state['id'])&&$v['caches']!==null){$web=$try('webProbe',fn()=>$this->probeRun('preflight-'.$stage,$installed,false,false,true));$v['web']=$web;if($web!==null&&$web['failures']!==[])$g['webProbe']=$web['failures'][0];}else $g['webProbe']='NOT_EVALUATED_TARGETS';
        if(isset($v['web']['holdSeconds'])){
            $this->state['groupPauseSeconds']=(int)($v['web']['revalidateFreq']??2)+1;
            $need=$this->requiredWindowSeconds((int)$v['web']['holdSeconds']);$v['timeBound']=['holdSeconds'=>(int)$v['web']['holdSeconds'],'groupPauseSeconds'=>$this->groupPause(),'smokeCapSeconds'=>$this->smokeCapSeconds(),'requiredWindowSeconds'=>$need,'rule'=>'5*HOLD+2*SMOKE_CAP+14*PAUSE+300<=WINDOW'];
            $this->scheduleNeedSeconds=max($this->scheduleNeedSeconds,$need);
            if($this->scheduleGate&&$app!==null){$s=$try('scheduleWindowFitsTimeBound',fn()=>$this->scheduleState($app));$v['scheduleTimeBound']=$s;if($s!==null&&$s['conflict']!==null)$g['scheduleWindowFitsTimeBound']='SCHEDULED_BACKUP_WITHIN_THE_'.$need.'_SECONDS_THIS_RUN_MAY_NEED';}
        }else $g['timeBound']='NOT_EVALUATED_WEB_PROBE';
        $v['merchantApproval']=$try('merchantApprovalRead',fn()=>self::approvalFacts($this->helper($this->routesScript,['approval'])));
        $try('liveSmokes',fn()=>$this->smokes($installed));
        $blockers=array_values(array_unique(array_filter($g,static fn($c)=>$c!==null)));
        if(isset($v['shop']))$this->state['shopRecorded']=$v['shop'];
        $report=['stage'=>$stage,'gates'=>array_map(static fn($c)=>$c??'PASS',$g),'values'=>$v,'blockers'=>$blockers,'applicationFiles'=>'NONE','database'=>'NO_PRODUCT_CART_OR_SWITCH_WRITES_SESSIONS_AND_APP_CACHE_ONLY','shopSwitch'=>'READ_ONLY','transientProbe'=>'WRITTEN_AND_REMOVED','privateState'=>'B1149_NAMESPACE_ONLY'];$this->emit('PREFLIGHT',$report);return $report;
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
        if(!file_exists($this->pointer))return false;if(is_link($this->pointer)||filesize($this->pointer)>8192)throw new RuntimeException('B1149_POINTER');$p=json_decode((string)file_get_contents($this->pointer),true,8,JSON_THROW_ON_ERROR);
        if(!preg_match('/^[a-f0-9]{32}$/D',(string)($p['id']??''))||($p['private']??null)!==$this->base.'/'.$p['id'])throw new RuntimeException('B1149_POINTER_SCOPE');$path=$p['private'].'/state.json';
        if(is_link($path)||realpath($path)!==$path||filesize($path)>16777216)throw new RuntimeException('B1149_STATE');$this->state=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
        if(($this->state['schemaVersion']??0)!==1||($this->state['kind']??null)!=='B1149'||($this->state['id']??null)!==$p['id'])throw new RuntimeException('B1149_STATE_BINDING');return true;
    }

    private function begin():void
    {
        $id=bin2hex(random_bytes(16));$private=$this->base.'/'.$id;if(!mkdir($private,0700))throw new RuntimeException('PRIVATE_TRANSACTION_CREATE');
        $this->state=['schemaVersion'=>1,'kind'=>'B1149','id'=>$id,'private'=>$private,'phase'=>'NEW','startedAt'=>time(),'probes'=>[],'temps'=>[],'applied'=>[],'createdDirs'=>[]];$this->save('PREFLIGHT');
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
        $this->state['groupPauseSeconds']=(int)($preflight['values']['web']['revalidateFreq']??2)+1;$this->state['holdSeconds']=(int)($preflight['values']['web']['holdSeconds']??FreshnessProbe::MAX_HOLD_SECONDS);$this->state['approvalBefore']=$preflight['values']['merchantApproval']??null;$doc=$dir.'/manifest.json';if(is_file($doc))unlink($doc);AtomicFile::privateCopy($doc,json_encode(['batch'=>'B1149','files'=>array_values($files)],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
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

    private function install():void
    {
        $this->state['logBaseline']=LeanGates::logBaseline($this->active().'/storage/logs');$this->state['applied']=[];$this->save('INSTALL_INTENT');$group=null;
        // Between groups every web worker revalidates the files of the previous group first (revalidate_freq + 1 s), so a
        // request never combines new bytecode of a later group with still-cached old bytecode of an earlier one.
        $pause=$this->groupPause();
        foreach($this->plan as $s){
            // The views come after the full measured hold: by then every worker runs the new classes, controllers, middleware, routes
            // (site-assets.show, admin.settings.smtp.index) and bootstrap, so no view renders without its route; providers.php is last.
            if($group!==null&&$group!==$s['group']){if($s['group']==='G')$this->hold('before-views',$this->viewHold());else{$this->emit('OVERLAY_GROUP_PAUSE',['after'=>$group,'before'=>$s['group'],'seconds'=>$pause]);sleep($pause);}$this->fault('GROUP_DONE_'.$group);$this->liveness($group);}$group=$s['group'];
            $this->fault('BEFORE_STEP_'.$s['n']);$t=$this->target($s);$bytes=$this->payload($s);$this->state['applied'][$s['n']]='PENDING';
            if($s['kind']==='new'){
                $dir=dirname($t);if(!is_dir($dir)){if(!is_dir(dirname($dir)))throw new RuntimeException('TARGET_PARENT_MISSING');$this->state['createdDirs'][]=$dir;$this->persist();if(!mkdir($dir,0755))throw new RuntimeException('TARGET_DIRECTORY_CREATE');chmod($dir,0755);$pg=stat(dirname($dir))['gid'];if(stat($dir)['gid']!==$pg)@chgrp($dir,$pg);clearstatcache(true,$dir);if(stat($dir)['gid']!==$pg)throw new RuntimeException('OVERLAY_DIRECTORY_GID_NOT_PRESERVABLE');}
                $temp=$this->recordTemp(StorefrontFiles::tempFor($t));$receipt=StorefrontFiles::create($t,$bytes,0644,$temp,stat($dir)['gid']);
            }else{$b=$this->state['backup']['files'][$s['n']];$temp=$this->recordTemp(StorefrontFiles::tempFor($t));$receipt=StorefrontFiles::replace($t,$bytes,$b['mode'],$b['gid'],$temp,(string)$s['preSHA256']);}
            $this->state['temps']=array_values(array_filter($this->state['temps'],static fn($x)=>$x!==$temp));$this->state['applied'][$s['n']]='DONE';$this->changed();$this->persist();
            $this->emit('OVERLAY_STEP',['n'=>$s['n'],'group'=>$s['group'],'kind'=>$s['kind'],'path'=>$s['path'],'sha256'=>$s['payloadSHA256']]+$receipt);$this->fault('AFTER_STEP_'.$s['n']);
        }
        $this->fault('GROUP_DONE_'.$group);$this->refreshViews(true);$this->save('FILES_INSTALLED');
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
     * stops the loop), with view:clear and the full measured hold after the views (before the route goes) and before the
     * new classes, re-invalidates, waits and smoke-tests the old state with the shop in whatever state it is now.
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
                // After the views (group G): view:clear, then the full measured hold BEFORE bootstrap/app.php and routes/web.php go,
                // so no worker still renders the new compiled admin layout (route('admin.settings.smtp.index')) once the route is gone. Before
                // the new classes (group A): the full hold again. Every other boundary: the revalidation pause. A fresh
                // process resuming a rollback times each wait from the saved wall-clock time of the last change.
                $key=$s['group'];$boundary=$group!==null&&$group!==$key;$prev=$group;$group=$key;
                try{
                    if($boundary){
                        if($prev==='G'){
                            // Only while bootstrap/app.php still holds the payload: a resumed rollback past this boundary keeps the saved clock.
                            $clear=$this->classify($s)['state']==='payload';if($clear)$this->refreshViews();
                            $this->emit('ROLLBACK_AFTER_VIEWS',['viewsClearedFirst'=>$clear]);$this->hold('rollback-after-views',$this->viewHold());
                        }elseif($key==='A')$this->hold('rollback-before-new-classes',$this->viewHold());
                        else{$this->emit('ROLLBACK_GROUP_PAUSE',['before'=>$key,'seconds'=>$pause]);$this->hold('rollback-pause-'.$key,$pause);}
                    }
                    $this->fault('BEFORE_RESTORE_'.$s['n']);$c=$this->classify($s);$t=$this->target($s);
                    if($s['kind']==='modified'){
                        if($c['state']==='payload'){$b=$backup['files'][$s['n']];$bytes=AtomicFile::readPrivate($b['copy'],$b['sha256']);StorefrontFiles::replace($t,$bytes,$b['mode'],$b['gid'],$this->recordTemp(StorefrontFiles::tempFor($t)),$s['payloadSHA256']);$restored++;$this->changed();}
                        elseif($c['state']!=='pre')throw new RuntimeException('ROLLBACK_FOREIGN_CONTENT_NOT_OVERWRITTEN_'.$s['n']);
                    }elseif($c['state']==='payload'){StorefrontFiles::removeExact($t,$s['payloadSHA256']);$removed++;$this->changed();}
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
            $tree=LeanGates::verifyTree($this->active(),$this->b1148Manifest(),self::CONFIG_SHA,$this->state['chain']['htaccessSHA256'],self::B1148_TREE_FILES);$this->emit('ACTIVE_TREE_AFTER_ROLLBACK',$tree+['equalsManifestPlusB1146PlusB1147PlusB1148'=>true]);
            try{$this->mailFingerprintUnchanged('after-rollback');}catch(Throwable $e){$this->emit('OWNER_NOTICE_MAIL_FINGERPRINT',self::code($e));}
            $this->approvalReport('after-rollback');
        }catch(Throwable $e){$problems[]=self::code($e);}
        if($problems!==[]){
            try{$this->save('ROLLBACK_INCOMPLETE');}catch(Throwable){$this->emit('ROLLBACK_STATE_UNWRITABLE','FIX_DISK_OR_PERMISSIONS_THEN_RUN_THE_SAME_COMMAND');}
            $this->recoveryNote(array_values(array_unique($problems)));return false;
        }
        $this->save('ROLLED_BACK');$this->emit('ROLLED_BACK',['files'=>'EXACT_PRIVATE_COPIES','newFiles'=>'REMOVED','routeCache'=>'NONE_NEVER_CREATED','compiledViews'=>'CLEARED_REBUILT_ON_DEMAND_NOT_PRECOMPILED','tree'=>'EQUALS_8548_FILE_MANIFEST_PLUS_B1146_PLUS_B1147_PLUS_B1148','shopSwitch'=>'NOT_WRITTEN']);return true;
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
            elseif($f['kind']==='modified'&&$f['now']==='absent')echo 'OWNER_INSPECT: '.$t.' IS_MISSING_NOT_REMOVED_BY_B1149_THE_OLD_BYTES_ARE_IN '.($f['backupCopy']??'NO_BACKUP_COPY').' SHA256 '.($f['sha256']??'UNKNOWN').PHP_EOL;
            elseif(!in_array($f['now'],['pre','absent','payload'],true))echo 'OWNER_INSPECT: '.$t.' IS_'.strtoupper($f['now']).'_NOT_TOUCHED_BY_B1149'.PHP_EOL;
            if($act===[])continue;
            if($last!==null&&$last!==$key){if($last==='G'){$lines[]=$view;$lines[]='sleep '.$this->viewHold();}elseif($key==='A')$lines[]='sleep '.$this->viewHold();else $lines[]='sleep '.$pause;}
            $last=$key;array_push($lines,...$act);
        }
        if($record)foreach(array_reverse($this->state['createdDirs']??[]) as $dir)if(is_dir($dir)&&!is_link($dir))$lines[]='rmdir --ignore-fail-on-non-empty -- '.$q($dir);
        $lines[]=$view;$lines[]='echo B1149_MANUAL_RECOVERY_BLOCK_DONE';
        echo 'OWNER_COMMAND_BLOCK=PASTE_EVERYTHING_FROM_THE_bash_-e_LINE_TO_THE_B1149_RECOVERY_LINE_INTO_THE_SSH_SHELL_IT_STOPS_AT_THE_FIRST_FAILING_LINE'.PHP_EOL;
        echo "bash -e <<'B1149_RECOVERY'".PHP_EOL.implode(PHP_EOL,$lines).PHP_EOL.'B1149_RECOVERY'.PHP_EOL;
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
            if($strict&&$this->scheduleGate){$this->scheduleNeedSeconds=max($this->scheduleNeedSeconds,$this->requiredWindowSeconds($this->viewHold()));$s=$this->scheduleState($app);$v['schedule']=$s;
                if($s['conflict']!==null){if($this->ignoreSchedule)$v['scheduleOverride']='SCHEDULED_BACKUP_CONFLICT_IGNORED_BY_THE_OWNER_OVERRIDE';else $b[]='SCHEDULED_BACKUP_WITHIN_30_MINUTES_'.strtoupper((string)$s['conflict']['type']).'_WAIT_OR_RUN_WITH_--rollback_--ignore-schedule';}}
        }catch(Throwable $e){$v['appState']=self::code($e);if($strict)$b[]='APP_STATE_UNREADABLE_'.self::code($e);}
        try{$v['census']=$this->census();}catch(Throwable $e){$b[]=self::code($e);}
        $this->emit('ROLLBACK_GATES',['strict'=>$strict,'values'=>$v,'blockers'=>$b]);return $b;
    }

    /** Right before the first change: inside the time budget, the shop state as recorded, no backup due, no route cache, no B1146/B1147 run. */
    private function beforeFirstChange():void
    {
        $elapsed=intdiv(hrtime(true)-(int)$this->runStartNs,1000000000);if($elapsed>$this->budgetSeconds)throw new RuntimeException('TIME_BUDGET_EXCEEDED_BEFORE_FIRST_CHANGE');
        $app=$this->helper($this->appStateScript,['state']);$shop=self::shopOf($app);if($shop!==array_diff_key($this->state['shopRecorded']??[],['writtenByThisTool'=>true]))throw new RuntimeException('SHOP_STATE_CHANGED_BEFORE_FIRST_CHANGE');if(!$shop['open'])throw new RuntimeException('SHOP_IS_OFF_B1149_INSTALLS_ONLY_WITH_THE_SHOP_ON_NOTHING_CHANGED');
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
        $this->final=$final;$this->emit('B1149_RESULT',['overlay'=>'STOREFRONT_SVG_LOGO_SMTP_SETTINGS_PRODUCT_GALLERY_43_FILES','sourceCommit'=>self::SOURCE_COMMIT,'newFiles'=>16,'modifiedFiles'=>27,'newRoutes'=>'GET_/site-assets/{file}_site-assets.show_AND_ADMIN_SMTP_SETTINGS_ROUTES','tests'=>'NOT_INSTALLED','shopSwitch'=>'READ_ONLY_NOT_WRITTEN','smtpSettings'=>'NOT_READ_NOT_WRITTEN_NO_TEST_MAIL_ONLY_A_TRANSPORT_FINGERPRINT','routeCache'=>'NONE','migrations'=>'NONE','composer'=>'NONE','env'=>'UNCHANGED','config'=>'UNCHANGED','queue'=>'RESTARTED_VIA_ARTISAN_queue:restart','opcache'=>'TARGETED_PER_FILE_INVALIDATION_ONLY','backupDirectory'=>$this->state['backup']['directory']??'NONE_ALREADY_INSTALLED_BEFORE_THIS_TOOL_STATE']);
        $this->cssNotice();
        $this->emit('OWNER_NEXT_STEPS',array_merge([$this->purgeStep(),'2_RIGHT_NOW_RUN_THE_PRODUCT_IMAGE_RENDITIONS_(README_SECTION_RENDITIONS):_nice_-n_19_php_artisan_app:product-image-optimize_--apply_--force_--chunk=10_AND_CHECK_FAILED=0_UNTIL_THEN_THE_OLD_SQUARE_RENDITIONS_ARE_REJECTED_AND_THE_ORIGINAL_IS_SERVED','3_OPEN_https://cms.ald1n.com/katalog_AND_A_PRODUCT_PAGE_IN_A_PRIVATE_WINDOW_(GALLERY_THEME_SWITCH_LOGO)','4_PDF_DOCUMENTS_NEED_A_JPG_OR_PNG_LOGO_AN_SVG_LOGO_GIVES_A_YELLOW_SQUARE_IN_THE_PDF','5_ADD_THE_shop_theme_COOKIE_TO_THE_KOLACICI_TEXT_AND_CONFIRM_THE_MERCHANT_APPROVAL_AGAIN_(README)','6_THE_SHOP_AND_THE_SMTP_SETTINGS_STAY_AS_YOU_SET_THEM_THIS_TOOL_NEVER_CHANGES_THEM'],isset($this->state['backup']['directory'])?['ROLLBACK_IF_NEEDED=bash /home/icaffeco/ald1n-project/incoming/b1149-installer.sh --rollback (THE_SHOP_MAY_STAY_ON)']:['NO_B1149_BACKUP_IN_THIS_STATE_NO_AUTOMATIC_ROLLBACK']));
        $this->emit('DO_NOT_RERUN_B1144_B1145_B1146_B1147_OR_B1148_AFTER_B1149','THEIR_MANIFEST_GATES_NOW_SEE_THE_43_B1149_FILES_AND_THEIR_--rollback_WOULD_REFUSE_FOREIGN_BYTES');return 0;
    }

    private function alreadyInstalled():int
    {
        $p=$this->preflight('installed');if($p['blockers']!==[]){$this->emit('HOLD_INSTALLED_REVERIFY',$p['blockers']);return 2;}
        $this->emit('ALREADY_INSTALLED_NO_REPEAT',['digestsVerified'=>count($this->plan),'treeFiles'=>self::INSTALLED_TREE_FILES]);return $this->finish('PASS_ALREADY_INSTALLED');
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
                if(!$this->restore('OWNER_REQUESTED_ROLLBACK'))return 2;$this->final='PASS_ROLLED_BACK_BY_OWNER';$this->emit('OWNER_NOTICE','THE_B1148_STOREFRONT_IS_ACTIVE_AGAIN_A_LATER_RUN_OF_THE_SAME_COMMAND_WITHOUT_OPTIONS_INSTALLS_AGAIN');
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
            return $this->finish('PASS_INSTALLED_STOREFRONT_ROLLOUT_SHOP_STATE_UNCHANGED');
        }catch(Throwable $e){$this->emit('HOLD_CODE_DETAIL',self::code($e));return 2;}
    }

    /**
     * An install or acceptance failure rolls back, except when the acceptance could not be judged (persistent 429, the
     * shop state changed during the checks, the noindex header missing, or a Vary header the edge hides with no origin
     * route) while all 43 files hold exactly the payload and every unthrottled check passes: then the correct files stay,
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
                $this->emit('HOLD_INSTALLED_NOT_VERIFIED',['reason'=>$code,'files'=>'ALL_43_HOLD_EXACTLY_THE_PAYLOAD','rollback'=>'NOT_STARTED_THE_FILES_ARE_CORRECT','shopSwitch'=>'NOT_WRITTEN']);
                $first=match($code){'SMOKES_RATE_LIMITED_429'=>'1_THE_SITE_ANSWERED_429_TO_THE_CHECKS_WAIT_A_FEW_MINUTES_(LOW_TRAFFIC)',
                    'NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES'=>'1_PUBLIC_PAGES_ARE_SERVED_WITHOUT_X-ROBOTS-TAG_NOINDEX_RESTORE_THE_HEADER_LINE_IN_apps/cms/current/public/.htaccess_OR_THE_CLOUDFLARE_RULE_THAT_REMOVES_IT_(THE_OWNER_POLICY_IS_NO_INDEXING)',
                    'VARY_COOKIE_NOT_VERIFIABLE_EDGE_HIDES_IT_AND_NO_ORIGIN_ROUTE'=>'1_THE_EDGE_HIDES_THE_VARY_HEADER_OF_/katalog_AND_THE_ORIGIN_COULD_NOT_BE_ASKED_DIRECTLY_CHECK_THAT_CLOUDFLARE_DOES_NOT_CACHE_HTML_(CACHE_LEVEL_STANDARD_NO_CACHE_EVERYTHING_RULE)',
                    default=>'1_THE_SHOP_STATE_CHANGED_DURING_THE_CHECKS_TURN_THE_SHOP_ON_AGAIN_IN_THE_ADMIN'};
                $this->emit('OWNER_NEXT_STEPS',[$first,'2_THEN_RUN_THE_SAME_COMMAND_AGAIN_IT_REPEATS_ONLY_THE_ACCEPTANCE_CHECKS_NO_FILE_IS_CHANGED','3_OR_RESTORE_THE_OLD_FILES=bash /home/icaffeco/ald1n-project/incoming/b1149-installer.sh --rollback',$this->purgeStep()]);
                return 2;
            }
        }
        $ok=$this->restore($code);$this->final=$ok?'HOLD_ROLLED_BACK_OLD_STATE_VERIFIED':'HOLD_ROLLBACK_INCOMPLETE';if($ok)$this->emit('OWNER_NEXT_STEPS',[$this->purgeStep(true)]);return 2;
    }

    /** The acceptance checks that need neither a throttled route nor the shop: fresh probes, routes, new classes/routes/provider, mail-transport fingerprint, log, tree. */
    private function unthrottledChecks(string $label):void
    {
        $this->probeRun('installed-fresh',true,false,true);$this->routesCheck();$this->featureCheck(true);$this->mailFingerprintUnchanged('after-install');
        $scan=LeanGates::newLogErrors($this->active().'/storage/logs',$this->state['logBaseline']);$this->emit('LARAVEL_LOG_SINCE_INSTALL',$scan);if($scan['newErrorLines']!==0)throw new RuntimeException('NEW_LARAVEL_LOG_ERRORS_SINCE_INSTALL');
        $tree=LeanGates::verifyTree($this->active(),$this->installedManifest(),self::CONFIG_SHA,$this->state['chain']['htaccessSHA256'],self::INSTALLED_TREE_FILES);$this->emit($label,$tree+['manifestPlusB1146PlusB1147PlusB1148PlusB1149'=>true]);
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
            $this->scheduleNeedSeconds=max($this->scheduleNeedSeconds,$this->requiredWindowSeconds($this->viewHold()));
            if($this->scheduleGate){$s=$this->scheduleState($app);if($s['conflict']!==null)throw new RuntimeException('SCHEDULED_BACKUP_WITHIN_THE_NEEDED_WINDOW_WAIT');}
            $this->earlierIdle();$this->census();$this->requireRecordedShop('BEFORE_RESUMED_ACCEPTANCE');
            $this->state['logBaseline']=LeanGates::logBaseline($this->active().'/storage/logs');$this->persist();
        }catch(Throwable $e){$this->emit('HOLD_ACCEPTANCE_NOT_RESUMED',['blockers'=>[self::code($e)],'filesChanged'=>'NONE','next'=>'TURN_THE_SHOP_ON_AS_BEFORE_OR_WAIT_FOR_THE_ARTISAN_RUN_THEN_RUN_THE_SAME_COMMAND_OR_USE_--rollback']);$this->final='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED';return 2;}
        if($this->targets()['installed']!==true){$ok=$this->restore('RESUMED_ACCEPTANCE_FILES_NOT_EXACT');$this->final=$ok?'HOLD_ROLLED_BACK_OLD_STATE_VERIFIED':'HOLD_ROLLBACK_INCOMPLETE';return 2;}
        try{$this->accept();}catch(Throwable $e){return $this->acceptanceFailed($e);}
        return $this->finish('PASS_INSTALLED_STOREFRONT_ROLLOUT_SHOP_STATE_UNCHANGED');
    }
}

/** Same-directory temp + fsync + rename (replace) or temp + fsync + link (create, never clobbers). Mode and group are kept. */
final class StorefrontFiles
{
    public static function tempFor(string $target):string {return dirname($target).'/.'.basename($target).'.b1149-'.bin2hex(random_bytes(8)).'.tmp';}
    public static function isOwnTemp(string $path):bool {return preg_match('~/\.[A-Za-z0-9_.-]+\.b1149-[a-f0-9]{16}\.tmp$~D',$path)===1;}

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
