<?php
declare(strict_types=1);

/**
 * B1153 logo sizes, mail logo and catalog view switch with per-page choice: ships 26 reviewed files (3 new, 23 modified) over the live tree,
 * which is the manifest + B1146 + B1147 + B1148 + B1149 + B1150 + B1151 + B1152 (8569 files, equals apps/cms/current of BASE_COMMIT af1e46a3
 * without tests); tests, CI and docs excluded (the same rule B1152 used). Payload (one source commit, origin/main SOURCE_COMMIT d760bb2c, the
 * merge of PR 181 and PR 182 over BASE_COMMIT): the logo height settings for the places a logo is shown (storefront header and footer, admin
 * header, the login pages, e-mails and PDF documents), the logo in the header of HTML e-mails (a PNG or JPG light-theme logo only), no
 * company name next to an uploaded logo, and on the public catalog the grid/list switch and the 20/40/80 per-page choice (a bad query value
 * falls back to the default). No route, no migration, no composer change, no .env, no config change. The Storefront is OPEN in production:
 * its state is recorded, re-read before the first change and around every smoke batch, and never written. SMTP settings are never read for
 * their values, written or tested, no mail is sent; only a hashed fingerprint of the effective mail transport is compared before and
 * after. Password endpoints are never called, nobody logs in. No PDF is generated and no admin setting is written.
 * Order: PREFLIGHT (read-only, collect-all; any blocker is a HOLD with nothing changed; Report1118 and the B1152 state, APP_URL
 * exactly https://cms.ald1n.com, PHP extensions, backups, digest check of every modified file) -> per-file backup into the private
 * B1153 namespace -> install in the reviewed dependency order, one group at a time with a revalidation pause between. Group letters of this
 * rollout: A the three new files (the class MailBranding and the two e-mail header views; nothing names them yet), B the two library
 * classes everything else calls (SettingsService::logoHeight and LOGO_HEIGHTS, the catalog per-page and view whitelist), C the classes and
 * controllers that call them (storefront appearance, catalog service and controller, appearance and document settings controllers, the PDF
 * layout), D the two PDF services that pass the new PDF layout argument plus the storefront stylesheet (the stylesheet goes before the layout
 * that links its new ?v= URL, so the edge can never keep an old stylesheet under the new URL), E the views, the e-mail views among them, and
 * app/Providers/AppServiceProvider.php (it loads the vendor/mail views, so it is the step that makes the new e-mail header live), the storefront
 * layout LAST. The e-mail views call MailBranding, so before the first group-E file the full measured hold elapses (it always spans group A)
 * and a probe served by the WEB SAPI (not only the CLI) proves that MailBranding loads through the very autoloader the app uses and that
 * its file is the payload; group E is refused without that proof. After E an immediate liveness smoke (/up, /login) runs and a failure rolls
 * group E back first. Each file via same-directory temp + fsync + rename (link for a new file, so nothing is ever clobbered; missing
 * directories are created one level at a time, outermost first, and removed again by a rollback) -> route:clear, view:clear,
 * queue:restart (never a cache build) -> targeted per-file opcache_invalidate through token-gated temporary probes (the pinned probe takes
 * at most 40 targets per request, larger plans are split) -> wait max(realpath_cache_ttl, revalidate_freq) + 5 s -> smokes, fresh-code
 * probe, CLI feature check, mail-transport fingerprint, log scan, full tree verification.
 * Any failure after the first file restores the backed-up bytes of all files in exact reverse install order (stopping at the first
 * failed restore, so the tree is always an exact install prefix): group E first (in reverse, the layout first), then the full measured
 * hold BEFORE anything else goes, short pauses between the other groups, the full hold again before group A (the three new files are
 * removed last); it re-invalidates and smoke-tests the old state. A re-run converges from the saved phase.
 * ONE place holds the payload: the block marked B1153_PAYLOAD below (BASE_COMMIT, SOURCE_COMMIT, STEPS and DIGESTS).
 * @author Ald1n <https://ald1n.com>
 */
final class LogoCatalogInstallCoordinator
{
    public const MANIFEST_SHA='d737387e60a981b3b404e0980d9b70cb95b6e1d506a39211981d49dcd12be4c6';
    public const MANIFEST_FILES=8531;
    /** The tree after B1146 (manifest + 12 new), after B1147 (+ 3), after B1148 (+ 2), after B1149 (+ 16 new), after B1150 (+ 2 new), after B1151 (+ 1 new), after B1152 (+ 2 new); this rollout adds the new files of STEPS. */
    public const B1146_TREE_FILES=8543;
    public const B1147_TREE_FILES=8546;
    public const B1148_TREE_FILES=8548;
    public const B1149_TREE_FILES=8564;
    public const B1150_TREE_FILES=8566;
    public const B1151_TREE_FILES=8567;
    public const B1152_TREE_FILES=8569;
    public const B1146_FILES=21;
    public const B1147_FILES=8;
    public const B1148_FILES=11;
    public const B1149_FILES=43;
    public const B1150_FILES=5;
    public const B1151_FILES=6;
    public const B1152_FILES=9;
    /** config/public_storefront.php after the B1145 unclamp: the staged original bytes. */
    public const CONFIG_SHA='0cfbc864a3d44822a9d14e80f6252e84eb208c9da5a25d5fe6989e4cad789818';
    /** Report 1118 is the B1152 PASS (state id 481f6ec63f535aa680343e9e1059e122); an earlier report of the same tool never matches this pattern. */
    public const B1152_REPORT_PATTERN='~^1118-B1152-INSTALLER-\d{8}-\d{6}\.md$~D';
    public const B1152_PASS='PASS_INSTALLED_APP_URL_ORIGIN_PIN_PDF_LOGO_RASTER_SHOP_STATE_UNCHANGED';
    /** The B1151 terminal value (Report1117), still needed to check the pinned B1151 coordinator in b1151Plan(). */
    public const B1151_PASS='PASS_INSTALLED_LEGAL_EMAIL_MERCHANT_DRAFT_PASSWORD_THROTTLE_SHOP_STATE_UNCHANGED';
    /**
     * The production facts of the B1152 install this rollout is chained to (Report1118, delivered 13.11.2026): the SHA-256 of the report file as it
     * sits on the host (no CR byte in it; archived byte for byte in docs/operations, the digest is that of the git blob, not of a CRLF checkout) and the
     * id of the B1152 state directory. A different report or state is a HOLD with nothing changed. The chain gate compares the host file with this digest.
     */
    public const B1152_REPORT_SHA256='6000b386557fdc016e2789a80bcd5b68d33a3459311eb1ae791149f96e840edc';
    public const B1152_STATE_ID='481f6ec63f535aa680343e9e1059e122';
    public const FINAL_PASS='PASS_INSTALLED_LOGO_SIZES_MAIL_LOGO_CATALOG_VIEW_PER_PAGE_SHOP_STATE_UNCHANGED';
    public const RAN=71;
    /** The route count B1152 left (Report1118 ROUTES_CHECK routeCount); this rollout adds no route, so it must be the same before and after. */
    public const ROUTES=568;
    /** The origin B1152 pinned: the preflight HOLDs unless config('app.url') is exactly this string (no trailing slash); the e-mail logo URL is built from it. */
    public const EXPECTED_APP_URL='https://cms.ald1n.com';
    // ===== B1153_PAYLOAD (begin): the only place that names the payload source, the install order and the digests. package.php and the CI gate read it. =====
    /** The pre-state commit: the code that is live after B1152 (Report1118), commit af1e46a3 of origin/main; its apps/cms/current (without tests) equals the live tree (asserted at build time, byte for byte per file). */
    public const BASE_COMMIT='af1e46a30169a410ebb269bdfc021f656a4538e9';
    /** origin/main at the time of writing: the merge of PR 181 (logo size everywhere, mail logo, no text beside an uploaded logo) and PR 182 (catalog view switch and per-page) over BASE_COMMIT. Source key M in STEPS; its apps/cms/current is what the install produces (plus the tests that are not installed). */
    public const SOURCE_COMMIT='d760bb2c87a5365db305df8aa6f79226770a32f3';
    /**
     * The install order, [path under apps/cms/current, 'new'|'modified', group, source key M]. Groups of this rollout: A the three new files (nothing names them
     * in the old tree); B the two libraries; C their callers; D the PDF services, then the stylesheet; E the views and the provider, the layout last. Groups are
     * non-decreasing in this list (plan() refuses anything else), the new class MailBranding is in a lower group than the e-mail views that call it, group E is the
     * last group, every path is under apps/cms/current, none under tests/, bin/, database/, config/, routes/ or composer.*.
     */
    public const STEPS=[
        ['app/Support/MailBranding.php','new','A','M'],['resources/views/emails/partials/header.blade.php','new','A','M'],
        ['resources/views/vendor/mail/html/header.blade.php','new','A','M'],['app/Services/SettingsService.php','modified','B','M'],
        ['app/Support/PublicCatalogPresentation.php','modified','B','M'],['app/Support/PublicStorefrontAppearance.php','modified','C','M'],
        ['app/Services/PublicRetailCatalogService.php','modified','C','M'],['app/Http/Controllers/PublicShopController.php','modified','C','M'],
        ['app/Http/Controllers/Admin/SiteAppearanceController.php','modified','C','M'],['app/Services/Pdf/FlowPdfLayout.php','modified','C','M'],
        ['app/Http/Controllers/Admin/DocumentSettingsController.php','modified','C','M'],['app/Services/Pdf/BusinessDocumentPdfService.php','modified','D','M'],
        ['app/Services/Pdf/WarrantyCertificatePdfService.php','modified','D','M'],['public/assets/css/ald1n-public-storefront.css','modified','D','M'],
        ['resources/views/admin/settings/appearance.blade.php','modified','E','M'],['resources/views/admin/settings/documents.blade.php','modified','E','M'],
        ['resources/views/auth/activate-account.blade.php','modified','E','M'],['resources/views/auth/forgot-password.blade.php','modified','E','M'],
        ['resources/views/auth/login.blade.php','modified','E','M'],['resources/views/auth/reset-password.blade.php','modified','E','M'],
        ['resources/views/emails/management-report.blade.php','modified','E','M'],['resources/views/emails/order-events.blade.php','modified','E','M'],
        ['resources/views/layouts/app.blade.php','modified','E','M'],['resources/views/public-shop/catalog.blade.php','modified','E','M'],
        ['app/Providers/AppServiceProvider.php','modified','E','M'],['resources/views/public-shop/layout.blade.php','modified','E','M'],
    ];
    public const DIGESTS=[
        'app/Support/MailBranding.php'=>[null,'abba2f1193c50578f41e57e3461ce1c00915ee3cd9abe5f56026fe8aace97634'],
        'resources/views/emails/partials/header.blade.php'=>[null,'e22b571cc3b28df0e125f4302bc0be4ff8d689ba61fc0552551e30637b1d4722'],
        'resources/views/vendor/mail/html/header.blade.php'=>[null,'7e81d51731a0104f373f76e8deae7f54d54d3d12de2f4838c9586ffa74fc4d55'],
        'app/Services/SettingsService.php'=>['fe5d3263c4357f74e2065f058b9cc8947a9f5c9945a23f8d1d55ebe9fc3eef17','889c78afe2c2f07728cc75bcafb2a5a61baad0eeffd52513070934e32eba96eb'],
        'app/Support/PublicCatalogPresentation.php'=>['7881ca241a6b180293e675eee97f5e26ca560c59a7fb650f0d15b74633818b6d','f365b6b666bbfa7561300d62fde12154c9b90d0b01d44f46f27a3f9c934a7352'],
        'app/Support/PublicStorefrontAppearance.php'=>['0961a4bced07bac6ed4616d3ef0210d2c3d040bfdd62df3a8799c149872ac406','a57aa32827510e3e69184d2d4c2303fe2e5785855a89348cadae4ad289550daf'],
        'app/Services/PublicRetailCatalogService.php'=>['de79e2eb5a8532c72e5e1c61d0c90bfb1166307d50111386688e4c106710d0ed','87f81b144c983f680612e7e2c562a1a25966338f15cae305ce34e4bb2a03f462'],
        'app/Http/Controllers/PublicShopController.php'=>['7061b6cd3fa0a422989bfeedd512a5bb764f30594dc9ee1a2159db29fd1326c5','c4817387bf50914e12b222a17a7b0acce5b2619526cbd90507c1369d3d969dfa'],
        'app/Http/Controllers/Admin/SiteAppearanceController.php'=>['13e25bedc85fd86f70b5c973076cdc0986f2f324a4bccc34163119197235e058','bb576349a4d70a72b74b9693ecb8689648ad37bd7cadc5a00cd187ca8fc7d306'],
        'app/Services/Pdf/FlowPdfLayout.php'=>['04731f17cf28336e66d525220f4e6e53508f6a0d6af01d2d1fe2261f54f4011a','7178ecbf7d6f68a1400f08fda837ad547774438cc97d049ee7a63fb3fade55d3'],
        'app/Http/Controllers/Admin/DocumentSettingsController.php'=>['56e9ddf808a8a779df46165df6b32200f33ad4bd0188a09e213468cfed229ead','a127844de8eb89115e22c84cfdd0ff632c0b97a478d1177dbd07f08730ae475c'],
        'app/Services/Pdf/BusinessDocumentPdfService.php'=>['b8fa04c073aa9e75cc2973530c3c0aa17e9fb8395b89ce3346b37545290a3109','a1fddb3dd69920f436540402f759488b96d561836712cab482fe078d434118c0'],
        'app/Services/Pdf/WarrantyCertificatePdfService.php'=>['ecbd263aa51ab189adaef40626c239dfe17e218a89ef0871588bef143fbbfb15','205d8554804f56fb76633490941fa5e062e6a74407c2267c9b437e5c8ef666d0'],
        'public/assets/css/ald1n-public-storefront.css'=>['50f6eaa385cb70612b8a1febc3dbda4c6cbbd4d1b8c33658387de0c581f16a77','9948843560ffd01b6c29a8abe681f0452bf179ea95b48e47d4d4d7c5a620f82f'],
        'resources/views/admin/settings/appearance.blade.php'=>['5d49ab9ffd1becb00cdd4edea1ce3fb04ea64e1cfbcdee185d60c4bf49e236cc','f6e89c1ac3256e6a7431cd3b06ee7c5160b3e51d66931fafffc81559d501f5dc'],
        'resources/views/admin/settings/documents.blade.php'=>['c163a7a08fbb68227ecfccbeddf5c9c033188d6eaa7c5fe19a2e5c053033cf88','c99d7996b4987b148b0fc5a8ec4d08218d16bde4b8492e72e0e56a6633df619f'],
        'resources/views/auth/activate-account.blade.php'=>['ade59b1a61a94639f814510440dd9149d1c9250f1d227bf5b9b43cc068eb55a8','51dbb0b2c596bfae01c9b7c3d85243c6635ca718e808a9ba6b9420d01d0ce599'],
        'resources/views/auth/forgot-password.blade.php'=>['71013284895421c228a0706d200699912e8cfbad33d977923b5b32f3ff26621b','83cdd73223e9e05e3b705383aa2ca5efb0ccc44080c13c5846fdd18365056314'],
        'resources/views/auth/login.blade.php'=>['2975747cc02325f80e4dcbf4a14f4ea4e35caa90d166807fc4e1a1d744a721e4','148e08dd5cabaeee50785ea4f0d2524b0905f045e4c841d2541de2e3fd8fe752'],
        'resources/views/auth/reset-password.blade.php'=>['a59b4a5f63d39ee65bb9b9e9c03bf29f8edfa6f9b514b820f60e27f383a8aa47','dc620efd422e0160cc275a62bff544df2d9ff0efe2e3b43d647f36a7c5875ec6'],
        'resources/views/emails/management-report.blade.php'=>['46a422991b27c36c53cf4fff01dbe744b3f30791281649a4360cb11baa10ce3f','a1e9bc173ffee0d1a9a9b482f07463350a1aa2621dbae4949c8b206b3a3ad8d6'],
        'resources/views/emails/order-events.blade.php'=>['94f8dd4fb72c89b9906e4eab30ed30a0187c38000fcb9b5a0059b84f00f9a9ed','c93e84edeba79aad6e956b4678f6b215ce5d87d83c208d5e0d15f7a87b9e8b89'],
        'resources/views/layouts/app.blade.php'=>['45f2eb6d31e27fd9a4fd0b5e2ba79487de9fc1c95977e828326d12692eab4e0b','3615a6113742b63457965d9acbe47996b045f983830913ff71d7ee272c6bbe3e'],
        'resources/views/public-shop/catalog.blade.php'=>['a827b8cd2feb335562b910000da2af7cda09bb0b868480829eb4db71fa756b05','ea7f7e7c8e8572b9500a80a872f6610107b30b810f82d3d8fab0690122508d1a'],
        'app/Providers/AppServiceProvider.php'=>['db4866d6216e3e2e4139c2669cd4e6b7633b3fa261df31fb3c44e5d5d60367a3','df53e1ed1592b11d6389ed9cd1920b1b075c79f2666d852d03cab1902b68b96c'],
        'resources/views/public-shop/layout.blade.php'=>['46164bfacde4a2bd628ed4714db94803c6335b311deeb99222f38b3d1d4812a3','a618a2345e02e24c22a4b33275d4a7aa537700d608a3a5f4d4c0f48416c274a9'],
    ];
    // ===== B1153_PAYLOAD (end) =====
    /** The last group: the full measured hold and the web-SAPI class proof come before its first file. */
    public const GUARD_GROUP='E';
    /**
     * Source-level facts of the payload edits that the CLI helper reads from the live files (key => [path, needle]): present in every payload file, absent from its
     * pre-state bytes (package.php asserts both). Installed: all true; old: all false.
     */
    public const SOURCE_MARKERS=[
        'logoHeights'=>['app/Services/SettingsService.php','public const LOGO_HEIGHTS'],
        'perPageWhitelist'=>['app/Support/PublicCatalogPresentation.php','public const PER_PAGE_OPTIONS'],
        'freshBranding'=>['app/Support/PublicStorefrontAppearance.php','public static function freshBranding()'],
        'shopControllerPerPage'=>['app/Http/Controllers/PublicShopController.php','PublicCatalogPresentation::perPage($request->query(\'per_page\'))'],
        'catalogServicePerPage'=>['app/Services/PublicRetailCatalogService.php','PublicCatalogPresentation::perPage($filters[\'per_page\']'],
        'appearanceLogoMessages'=>['app/Http/Controllers/Admin/SiteAppearanceController.php','private function logoHeightMessages()'],
        'documentsLogoHeight'=>['app/Http/Controllers/Admin/DocumentSettingsController.php','\'documents_logo_height\' => [\'sometimes\''],
        'pdfLogoHeight'=>['app/Services/Pdf/FlowPdfLayout.php','private static function logoHeight()'],
        'pdfNameBesideLogo'=>['app/Services/Pdf/BusinessDocumentPdfService.php','nameBesideLogo: false'],
        'mailMarkdownPaths'=>['app/Providers/AppServiceProvider.php','loadComponentsFrom'],
        'catalogViewSwitch'=>['resources/views/public-shop/catalog.blade.php','class="shop-perpage"'],
        'layoutFooterLogoHeight'=>['resources/views/public-shop/layout.blade.php','footer_logo_height'],
    ];
    /** The commit a STEPS entry is taken from. */
    public static function sourceCommit(string $key):string
    {
        return match($key){'M'=>self::SOURCE_COMMIT,default=>throw new RuntimeException('OVERLAY_STEPS_SOURCE')};
    }
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

    /** Files in the tree after this rollout: the B1152 tree plus the new files of STEPS (derived, never typed twice). */
    public static function installedTreeFiles():int
    {
        $new=0;foreach(self::STEPS as [,$kind])if($kind==='new')$new++;return self::B1152_TREE_FILES+$new;
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
    private string $b1152Base;
    /** Production defaults (Report1118, B1152 state id, route count, APP_URL); the CI fixture sets its own measured values as explicit doubles. */
    private string $b1152ReportSHA256=self::B1152_REPORT_SHA256;
    private string $b1152StateId=self::B1152_STATE_ID;
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
     * page, /tema; plus the catalog layout query and the origin-pin redirect check after E, so 8 at most), each up to the 25 s curl timeout plus the 0.7 s pacing, no 429 waiting: 8 x 25.7 = 206 s.
     */
    private const LIVENESS_SECONDS=210;
    /** The web-SAPI class proof: this many separate probe requests, all must pass; each is bounded by the 25 s timeout of the pinned client plus the probe file handling. */
    private const WEB_PROOF_REQUESTS=5;
    private const WEB_PROOF_SECONDS=30;
    /** Upper bound of the requests in one smoke batch: 2 system, 8 legal, 4 CSS, 2 /tema, 3 guest admin pages, 1 logo, 14 shop (two of them the per-page and view queries), 1 origin Vary, spare. */
    private const SMOKE_GETS=46;
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
    private array $markers;
    private string $cssVersion;
    private string $oldCssVersion;
    private string $galleryVersion;
    public string $final='HOLD';

    public function __construct(private string $root,private string $home,array $package,private string $incoming,private string $mode='install',private bool $ignoreSchedule=false)
    {
        if($ignoreSchedule&&$mode!=='rollback')throw new RuntimeException('OPERATOR_SCHEDULE_OVERRIDE');
        if($root!=='/home/icaffeco/ald1n-project'||$home!=='/home/icaffeco'||realpath($root)!==$root||realpath($home)!==$home||!function_exists('posix_geteuid')||!extension_loaded('curl')||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('HOST_PROFILE');
        $this->base=$home.'/.ald1n-b1153-state';$this->pointer=$this->base.'/current.json';$this->stage=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU';$this->b1146Base=$home.'/.ald1n-b1146-state';$this->b1147Base=$home.'/.ald1n-b1147-state';$this->b1148Base=$home.'/.ald1n-b1148-state';$this->b1149Base=$home.'/.ald1n-b1149-state';$this->b1150Base=$home.'/.ald1n-b1150-state';$this->b1151Base=$home.'/.ald1n-b1151-state';$this->b1152Base=$home.'/.ald1n-b1152-state';
        $this->appStateScript=$incoming.'/b1145-app-state.php';$this->routesScript=$incoming.'/b1146-app-check.php';$this->featureScript=$incoming.'/b1153-app-check.php';$this->web=new LogoCatalogWeb();
        $this->load($package,$incoming);putenv('GIT_OPTIONAL_LOCKS=0');
        if(!in_array($mode,['install','rollback'],true))throw new RuntimeException('OPERATOR_MODE');
    }

    private function load(array $package,string $incoming):void
    {
        $this->plan=self::plan($package,$incoming);$this->b1146=self::b1146Plan($package);$this->b1147=self::b1147Plan($package);$this->b1148=self::b1148Plan($package);$this->b1149=self::b1149Plan($package);$this->b1150=self::b1150Plan($package);$this->b1151=self::b1151Plan($package);$this->b1152=self::b1152Plan($package);$this->markers=self::markers($package);
        foreach(['cssVersion','oldCssVersion','galleryVersion'] as $k){$v=$package[$k]??null;if(!is_string($v)||!preg_match('/^[0-9]{1,12}$/D',$v))throw new RuntimeException('CSS_VERSION_SCOPE');$this->$k=$v;}
    }

    /** The overlay plan from b1153-package.json must be exactly STEPS (paths, kinds, groups, order) with digests and a payload. */
    public static function plan(array $package,string $incoming):array
    {
        if(self::STEPS===[]||self::BASE_COMMIT===self::SOURCE_COMMIT)throw new RuntimeException('B1153_PAYLOAD_NOT_FILLED_IN');
        foreach([self::BASE_COMMIT,self::SOURCE_COMMIT] as $c)if(preg_match('/^[a-f0-9]{40}$/D',$c)!==1)throw new RuntimeException('B1153_PAYLOAD_NOT_FILLED_IN');
        $last='';$seen=[];foreach(self::STEPS as [$p,$k,$g,$src]){if(!in_array($k,['new','modified'],true)||!preg_match('/^[A-E]$/D',$g)||$g<$last||$src!=='M'||isset($seen[$p])||str_starts_with($p,'tests/')||str_starts_with($p,'bin/')||preg_match('~^(database/|config/|routes/|composer\.|\.env)~',$p)===1)throw new RuntimeException('OVERLAY_STEPS_SCOPE');$last=$g;$seen[$p]=true;}
        // The order invariant of this rollout: the e-mail views of the LAST group call the new class MailBranding, so every new class sits in a LOWER group than the guard group, which is the last one.
        $newTop='';foreach(self::STEPS as [$p,$k,$g])if($k==='new'&&$g>$newTop)$newTop=$g;
        if($last!==self::GUARD_GROUP||$newTop===''||$newTop>=self::GUARD_GROUP||self::STEPS[0][2]!=='A'||self::STEPS[0][1]!=='new')throw new RuntimeException('OVERLAY_GUARD_GROUP_NOT_LAST_AFTER_THE_NEW_CLASSES');
        // The reviewed digests are coded once more here, so a package built over other bytes is refused on the host as well.
        if(array_keys(self::DIGESTS)!==array_map(static fn($s)=>$s[0],self::STEPS))throw new RuntimeException('OVERLAY_DIGESTS_NOT_THE_STEPS');
        $overlay=$package['overlay']??null;if(!is_array($overlay)||count($overlay)!==count(self::STEPS)||($package['sourceCommit']??null)!==self::SOURCE_COMMIT||($package['baseCommit']??null)!==self::BASE_COMMIT)throw new RuntimeException('OVERLAY_PLAN_SCOPE');$plan=[];
        foreach(array_values($overlay) as $i=>$e){
            [$path,$kind,$group,$src]=self::STEPS[$i];
            if(!is_array($e)||($e['path']??null)!==$path||($e['kind']??null)!==$kind||($e['group']??null)!==$group||($e['source']??null)!==self::sourceCommit($src)||!preg_match('/^b1153-payload-\d{2}\.bin$/D',(string)($e['payload']??''))||!preg_match('/^[a-f0-9]{64}$/D',(string)($e['payloadSHA256']??''))||!is_int($e['size']??null))throw new RuntimeException('OVERLAY_PLAN_ENTRY');
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

    /** The B1149 markers (layout, css, gallery, catalog): they are in the tree before and after this rollout (package.php asserts them in the old and in the new bytes). */
    public static function markers(array $package):array
    {
        $m=$package['markers']??null;if(!is_array($m))throw new RuntimeException('MARKER_SCOPE');$out=[];
        foreach(['layout','css','gallery','catalog'] as $k){if(!is_string($m[$k]??null)||!preg_match('/^[A-Za-z0-9 ="._\[\]:()@,{}-]{6,80}$/D',$m[$k])||$m[$k]!==self::MARKERS[$k])throw new RuntimeException('MARKER_SCOPE');$out[$k]=$m[$k];}
        return $out;
    }

    /**
     * No Cloudflare change and no purge is needed: no route is added, the public shop pages send Cache-Control private, no-store and the new layout links the
     * stylesheet under a NEW URL (?v=cssVersion, bumped from oldCssVersion), which the edge has never seen. The stylesheet file is written before the layout that
     * links the new URL, so the edge can never hold the old stylesheet under the new URL. Cloudflare settings are not touched.
     * After a rollback the OLD URL (?v=oldCssVersion) may have cached the new stylesheet while the files were changing: that URL is named for a purge.
     */
    public function purgeStep(bool $rollback=false):string
    {
        $u='https://cms.ald1n.com'.self::CSS_PATH;
        if($rollback&&$this->cssVersion!==$this->oldCssVersion)return '1_CLOUDFLARE_PURGE_EXACTLY_'.$u.'?v='.$this->oldCssVersion.'_AND_'.$u.'_(THE_OLD_LAYOUT_LINKS_?v='.$this->oldCssVersion.'_WHICH_MAY_HAVE_CACHED_THE_NEW_CSS_WHILE_THE_FILES_WERE_CHANGING)';
        if($rollback)return '1_NO_CLOUDFLARE_PURGE_NEEDED_THE_STOREFRONT_CSS_URL_DID_NOT_CHANGE';
        return '1_NO_CLOUDFLARE_CHANGE_AND_NO_PURGE_NEEDED_(THE_SHOP_PAGES_SEND_Cache-Control_private_no-store_AND_THE_NEW_CSS_URL_'.$u.'?v='.$this->cssVersion.'_IS_NOT_CACHED_YET)_DO_NOT_CHANGE_ANY_CLOUDFLARE_SETTING_DURING_THE_INSTALLATION';
    }

    private function open():void
    {
        if($this->lock!==null)return;if(!is_dir($this->base)&&!mkdir($this->base,0700))throw new RuntimeException('PRIVATE_STATE_CREATE');
        if(is_link($this->base)||realpath($this->base)!==$this->base||(fileperms($this->base)&0777)!==0700||fileowner($this->base)!==posix_geteuid())throw new RuntimeException('PRIVATE_STATE_CONFINEMENT');
        $lock=fopen($this->base.'/operator.lock','c');if($lock===false||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('ANOTHER_B1153_RUN_ACTIVE');chmod($this->base.'/operator.lock',0600);$this->lock=$lock;
    }

    /** No B1146, B1147, B1148, B1149, B1150, B1151 or B1152 run holds its operator lock (read-only: each lock file is opened for reading and only probed). */
    private function earlierIdle():void
    {
        foreach(['B1146'=>$this->b1146Base,'B1147'=>$this->b1147Base,'B1148'=>$this->b1148Base,'B1149'=>$this->b1149Base,'B1150'=>$this->b1150Base,'B1151'=>$this->b1151Base,'B1152'=>$this->b1152Base] as $label=>$dir){
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
        $this->atomicJSON($this->pointer,['schemaVersion'=>1,'kind'=>'B1153','private'=>$this->state['private'],'id'=>$this->state['id']]);$this->emit('B1153_PHASE',$phase);$this->fault('SAVED_'.$phase);
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

    /** One read-only helper (b1145 state, b1146 routes/approval, b1153 features/environment); JSON facts only. */
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

    /** The live tree before this rollout: the 8567-file tree with the 9 B1152 files (8569 entries, Report1118). */
    private function b1152Manifest():array
    {
        $files=self::applied($this->b1151Manifest()['files'],array_values($this->b1152),'B1152_MANIFEST');if(count($files)!==self::B1152_TREE_FILES)throw new RuntimeException('B1152_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** The tree after this rollout (8569 + the new files of STEPS). */
    private function installedManifest():array
    {
        $changes=array_map(static fn($s)=>['path'=>$s['path'],'kind'=>$s['kind'],'sha256'=>$s['payloadSHA256'],'preSHA256'=>$s['preSHA256']],$this->plan);
        $files=self::applied($this->b1152Manifest()['files'],$changes,'B1153_MANIFEST');if(count($files)!==self::installedTreeFiles())throw new RuntimeException('B1153_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** Last value of every KEY=value line of the B1152 terminal report (Report1118). */
    public static function b1152Facts(string $bytes):array
    {
        if(str_contains($bytes,"\r")||strlen($bytes)>4194304)throw new RuntimeException('B1152_REPORT_FORMAT');$last=[];
        foreach(explode("\n",$bytes) as $line)if(preg_match('/^([A-Z0-9_]+)=(.*)$/D',$line,$m))$last[$m[1]]=$m[2];
        if(($last['B1152_FINAL']??null)!==self::B1152_PASS||($last['EXIT']??null)!=='0'||json_decode($last['B1152_PHASE']??'null',true)!=='INSTALLED_PASS')throw new RuntimeException('B1152_REPORT_NOT_TERMINAL_PASS');
        return ['final'=>$last['B1152_FINAL'],'phase'=>'INSTALLED_PASS','exit'=>0];
    }

    /**
     * Chain: exactly one Report1118 of B1152 with its terminal PASS (the pattern carries the sequence number 1118, so any earlier report
     * of the same prefix is not even a candidate) whose bytes equal the digest this package pins, the live B1152 state with the pinned
     * id INSTALLED_PASS, and no B1146-B1152 run. A different report, state id or digest is a HOLD with nothing changed.
     */
    private function chain():array
    {
        $directory=$this->root.'/docs/operations';$found=[];foreach(scandir($directory)?:[] as $name)if(preg_match(self::B1152_REPORT_PATTERN,$name))$found[]=$name;
        if(count($found)!==1)throw new RuntimeException(count($found)===0?'B1152_REPORT1118_MISSING':'B1152_REPORT1118_AMBIGUOUS');$path=$directory.'/'.$found[0];
        if(is_link($path)||realpath($path)!==$path||!is_file($path))throw new RuntimeException('B1152_REPORT1118_SCOPE');$bytes=(string)file_get_contents($path);$facts=self::b1152Facts($bytes);
        if(!hash_equals($this->b1152ReportSHA256,hash('sha256',$bytes)))throw new RuntimeException('B1152_REPORT1118_DIGEST_DIFFERS_FROM_THE_PINNED_ONE');
        $pointer=$this->b1152Base.'/current.json';if(is_link($pointer)||!is_file($pointer)||filesize($pointer)>8192)throw new RuntimeException('B1152_POINTER_MISSING');$p=json_decode((string)file_get_contents($pointer),true,8,JSON_THROW_ON_ERROR);
        if(!is_array($p)||($p['kind']??null)!=='B1152'||!preg_match('/^[a-f0-9]{32}$/D',(string)($p['id']??''))||($p['private']??null)!==$this->b1152Base.'/'.$p['id'])throw new RuntimeException('B1152_POINTER_SCOPE');
        if($p['id']!==$this->b1152StateId)throw new RuntimeException('B1152_STATE_ID_DIFFERS_FROM_THE_PINNED_ONE');
        $statePath=$p['private'].'/state.json';if(is_link($statePath)||realpath($statePath)!==$statePath||filesize($statePath)>16777216)throw new RuntimeException('B1152_STATE_MISSING');$stateBytes=(string)file_get_contents($statePath);$s=json_decode($stateBytes,true,512,JSON_THROW_ON_ERROR);
        if(!is_array($s)||($s['kind']??null)!=='B1152'||($s['id']??null)!==$p['id']||($s['phase']??null)!=='INSTALLED_PASS')throw new RuntimeException('B1152_STATE_NOT_INSTALLED_PASS');
        $retained=$s['chain']['retained']??null;$htaccess=$s['chain']['htaccessSHA256']??null;
        if(!is_string($retained)||!preg_match('~^/[A-Za-z0-9._/-]+/retained$~D',$retained)||!is_string($htaccess)||!preg_match('/^[a-f0-9]{64}$/D',$htaccess))throw new RuntimeException('B1152_STATE_FACTS');
        $this->earlierIdle();
        return ['report'=>'docs/operations/'.$found[0],'reportSHA256'=>hash('sha256',$bytes),'b1152Id'=>$p['id'],'b1152StateSHA256'=>hash('sha256',$stateBytes),'retained'=>$retained,'htaccessSHA256'=>$htaccess]+$facts;
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
        $found=[];$dirs=[];foreach($this->plan as $s)$dirs[dirname($this->target($s))]=true;foreach(array_merge($this->b1147,$this->b1148,$this->b1149,$this->b1150,$this->b1151,$this->b1152) as $e)$dirs[dirname($this->active().'/'.$e['path'])]=true;
        foreach(array_keys($dirs) as $d)if(is_dir($d))foreach(scandir($d)?:[] as $name)if(LogoCatalogFiles::isOwnTemp($d.'/'.$name)||OriginPinFiles::isOwnTemp($d.'/'.$name)||LegalAccountFiles::isOwnTemp($d.'/'.$name)||MediaFiles::isOwnTemp($d.'/'.$name)||StorefrontFiles::isOwnTemp($d.'/'.$name)||CatalogFiles::isOwnTemp($d.'/'.$name)||GlassFiles::isOwnTemp($d.'/'.$name))$found[]=$d.'/'.$name;return $found;
    }

    private function cleanupRecorded():void
    {
        $changed=false;
        foreach($this->state['probes']??[] as $i=>$probe){if(($probe['removed']??false)===true)continue;FreshnessProbe::removeStaging($probe['staging']);FreshnessProbe::remove($this->active().'/public',$probe['name'],$probe['sha256']);$this->state['probes'][$i]['removed']=true;$changed=true;}
        foreach($this->state['temps']??[] as $i=>$temp){if(LogoCatalogFiles::isOwnTemp($temp)&&is_file($temp)&&!is_link($temp)&&!unlink($temp))throw new RuntimeException('TEMP_FILE_REMOVE');unset($this->state['temps'][$i]);$changed=true;}
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
        $plan=LogoCatalogLoadProbe::plan($this->state['id'],[],$this->expectedAppUrl);$staging=$this->state['private'].'/probe-'.bin2hex(random_bytes(8)).'.staging';
        $this->state['probes'][]=['name'=>$plan['name'],'sha256'=>$plan['sha256'],'label'=>$label,'staging'=>$staging];$this->persist();
        try{FreshnessProbe::write($this->active().'/public/'.$plan['name'],$plan['source'],$staging);$response=$this->web->probe($plan['name'],$plan['token'],false,null);}finally{$this->cleanupRecorded();}
        $route=is_string($response['_route']??null)?$response['_route']:'UNKNOWN';unset($response['_route']);
        $a=LogoCatalogLoadProbe::assess($response,$this->state['id'],[],$this->allowedSapis,posix_geteuid());
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
            $plan=LogoCatalogLoadProbe::plan($this->state['id'],$classes,$this->expectedAppUrl);$staging=$this->state['private'].'/probe-'.bin2hex(random_bytes(8)).'.staging';
            $this->state['probes'][]=['name'=>$plan['name'],'sha256'=>$plan['sha256'],'label'=>$label.'-'.$i,'staging'=>$staging];$this->persist();
            try{FreshnessProbe::write($this->active().'/public/'.$plan['name'],$plan['source'],$staging);$response=$this->web->probe($plan['name'],$plan['token'],false,null);}finally{$this->cleanupRecorded();}
            $route=is_string($response['_route']??null)?$response['_route']:'UNKNOWN';unset($response['_route']);
            $a=LogoCatalogLoadProbe::assess($response,$this->state['id'],$classes,$this->allowedSapis,posix_geteuid());
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
        $g=count(array_unique(array_column($this->plan,'group')));return max(1800,5*$holdSeconds+2*$this->smokeCapSeconds()+(4*$g-2)*$this->groupPause()+(2*$g-1)*self::LIVENESS_SECONDS+self::WEB_PROOF_REQUESTS*self::WEB_PROOF_SECONDS+300);
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
        $one=function(string $label,string $path,string $method,int $want,bool $noindex,array $query=[])use(&$rows,&$failed,&$limited,$marker):?array{
            if($this->paceMicros>0)usleep($this->paceMicros);$r=$this->web->fetch($path,$query,$marker,$method,false);
            if(!$r['transport']&&$r['status']===429){$limited[]=$label;$rows[]=['check'=>$label,'result'=>'INCONCLUSIVE_RATE_LIMITED_429'];return null;}
            $pass=!$r['transport']&&$r['status']===$want&&(!$noindex||CatalogWeb::noindex($r['robots']));
            $rows[]=['check'=>$label,'observed'=>$r['transport']?'TRANSPORT':$r['status'],'result'=>$pass?'PASS':'FAIL'];if(!$pass)$failed[]=$label;return $r;};
        $one('/up','/up','GET',200,false);$one('/login','/login','GET',200,false);$one('/pravne-informacije','/pravne-informacije','GET',200,true);
        if($shop['open']){$c=$one('/katalog','/katalog','GET',200,true);$p=$c!==null&&$c['status']===200?MediaWeb::productPath($c['body']):null;if($p!==null)$one('PRODUCT_PAGE_FIRST_CATALOG_LINK',$p,'GET',200,true);}
        $one('GET /tema',self::THEME_PATH,'GET',405,false);
        // Right after the last group (E, the views and the provider) the catalog layout query answers 200 at once, and the B1152 origin pin is asserted as well: the guest redirect
        // must be on the pinned origin (https here, relative accepted). A failure rolls group E back first; a 429 stays inconclusive.
        if($after===self::GUARD_GROUP){
            if($shop['open']){$one('/katalog?per_page=20&view=list','/katalog','GET',200,true,['per_page'=>'20','view'=>'list']);}
            $r=$one('ORIGIN_PIN_'.self::SMTP_PATH,self::SMTP_PATH,'GET',302,false);
            if($r!==null&&!in_array(self::locationKind((string)($r['location']??''),$this->expectedAppUrl),['PINNED_ORIGIN','RELATIVE'],true)){$failed[]='ORIGIN_PIN_LOCATION';$rows[]=['check'=>'ORIGIN_PIN_LOCATION','observed'=>self::locationKind((string)($r['location']??''),$this->expectedAppUrl),'result'=>'FAIL'];}
        }
        $judged=$failed;if($failed!==[]){try{if($this->shop()!==$shop)$judged=[];}catch(Throwable){$judged=[];}}
        $this->emit('GROUP_LIVENESS_SMOKE',['after'=>$after,'shop'=>$shop,'rows'=>$rows,'failed'=>$judged,'rateLimited'=>$limited,'assertions'=>'LIVENESS_ONLY_NO_NEW_FEATURE']);
        if($judged!==[])throw new RuntimeException('GROUP_SMOKE_FAILED_AFTER_'.$after);
    }

    /**
     * Public smokes; no product, cart, account, theme or setting is written, nobody logs in (GET and one HEAD only). The
     * expectations follow the shop state read right before this batch (never changed by this tool) and $installed (the B1153
     * payload) or not (old = the live B1152 tree, as Report1118 left it). Everything B1149, B1150, B1151 and B1152 delivered is asserted in BOTH states.
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
     * of the b1153 helper hands over signed URLs for the first product image that has a file. The GET (with the body) is made on
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
        $cssVersion=$installed?$this->cssVersion:$this->oldCssVersion;
        $css(self::CSS_PATH,[],$marker,self::CSS_PATH,$m['css']);
        $css(self::CSS_PATH,['v'=>$cssVersion],null,self::CSS_PATH.'?v='.$cssVersion,$m['css']);
        if($installed)$css(self::CSS_PATH,['v'=>$cssVersion],null,self::CSS_PATH.'?v='.$cssVersion.' (view switch rules)','.shop-view-group');
        $css(self::GALLERY_CSS_PATH,[],$marker,self::GALLERY_CSS_PATH,$m['gallery']);
        $css(self::GALLERY_CSS_PATH,['v'=>$this->galleryVersion],null,self::GALLERY_CSS_PATH.'?v='.$this->galleryVersion,$m['gallery']);
        // The theme route exists since B1148 and accepts POST only: GET and HEAD are answered 405 by the router in both states.
        foreach(['GET','HEAD'] as $method){$r=$this->get(self::THEME_PATH,[],$marker,$method);$add($method.' '.self::THEME_PATH,$r,'405_NEVER_5XX',$ok($r,405));}
        // The SMTP admin page (B1149), the merchant settings page (B1151) and the document settings page (the logo preview of this rollout) for a guest:
        // never logged in, no form submitted; the auth middleware redirects to the login page in both states, and the Location is on the pinned origin
        // (an absolute URL on any other host fails; a relative /login is accepted and recorded as such). The password endpoints are not requested at all.
        foreach([self::SMTP_PATH,self::MERCHANT_PATH,self::DOCUMENTS_PATH] as $guestPath){
            $r=$this->get($guestPath,[],$marker);$loc=(string)($r['location']??'');$kind=self::locationKind($loc,$this->expectedAppUrl);$good=in_array($kind,['PINNED_ORIGIN','RELATIVE'],true);
            $f=['location'=>$kind==='NONE'?null:($good?'LOGIN':'OTHER'),'locationOrigin'=>$kind];
            $add($guestPath,$r,'302_TO_LOGIN_ON_THE_PINNED_ORIGIN_GUEST',$ok($r,302)&&$good,$f);
        }
        $product=null;$logo=null;$publicImage=null;
        if($shop['open']){
            $r=$this->get('/katalog',[],$marker);$vary=MediaWeb::varyCookie($r['vary']);
            $storage=MediaWeb::storageImageCount($r['body']);$links=MediaWeb::mediaLinkCount($r['body']);
            $f=['cspShape'=>$shape($r),'originScripts'=>MediaWeb::scriptCount($r['body']),'edgeInjectedScripts'=>MediaWeb::edgeScriptCount($r['body']),'layoutMarker'=>str_contains($r['body'],$m['layout']),'galleryLink'=>str_contains($r['body'],$m['catalog']),'cacheControlPrivateNoStore'=>MediaWeb::privateNoStore($r['cache']),'jsonLd'=>CatalogWeb::jsonLdProblem($r['body'])??'PASS','varyCookie'=>$vary,'storageImageLinks'=>$storage,'mediaRouteLinks'=>$links,
                'perPageControl'=>str_contains($r['body'],self::PER_PAGE_MARKER),'viewSwitch'=>str_contains($r['body'],self::VIEW_SWITCH_MARKER),'headerLogoHeight'=>self::headerLogoHeight($r['body'])];
            // The per-page form and the grid/list switch are the new feature: present once installed, absent before (a page that shows no product has neither, which is why the fixture has products).
            $pass=$ok($r,200)&&$f['cspShape']&&$f['layoutMarker']&&$f['galleryLink']&&$f['cacheControlPrivateNoStore']&&$f['jsonLd']==='PASS'&&$f['perPageControl']===$installed&&$f['viewSwitch']===$installed;
            // The storefront header logo (a data-dependent fact: only when a logo is set): its img carries a numeric height attribute, within the 24..80 px range of the setting.
            if($f['headerLogoHeight']==='NO_LOGO_IMG')$rows[]=['check'=>'SHOP_HEADER_LOGO_HEIGHT','result'=>'INFO_NO_HEADER_LOGO_IMG_ON_THE_CATALOG_PAGE_NOT_CHECKED_(THE_HEADER_SHOWS_A_LOGO_ONLY_WHEN_ONE_IS_SET)'];
            else{$hl=$f['headerLogoHeight']!=='NO_HEIGHT_ATTRIBUTE'&&$f['headerLogoHeight']>=24&&$f['headerLogoHeight']<=80;$rows[]=['check'=>'SHOP_HEADER_LOGO_HEIGHT','expected'=>'IMG_WITH_A_HEIGHT_ATTRIBUTE_24_TO_80','result'=>$hl?'PASS':'FAIL','heightAttribute'=>$f['headerLogoHeight']];if(!$hl)$failed[]='SHOP_HEADER_LOGO_HEIGHT';}
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
            // The new query values (the new code whitelists them; the old code ignores them: 200 in both states, never a 5xx). per_page=999 is not a member of 20/40/80 and falls back to 40.
            $r=$this->get('/katalog',['per_page'=>'20','view'=>'list'],$marker);$f=['listLayout'=>str_contains($r['body'],self::LIST_MARKER),'selected20'=>str_contains($r['body'],'<option value="20" selected')];
            $add('/katalog?per_page=20&view=list',$r,$installed?'200_LIST_LAYOUT_20_SELECTED':'200_QUERY_IGNORED_BY_THE_OLD_CODE',$ok($r,200)&&($installed?$f['listLayout']&&$f['selected20']:!$f['listLayout']),$f,true);
            $r=$this->get('/katalog',['per_page'=>'999'],$marker);$f=['selected40'=>str_contains($r['body'],'<option value="40" selected'),'option999'=>str_contains($r['body'],'value="999"')];
            $add('/katalog?per_page=999',$r,$installed?'200_FALLS_BACK_TO_40':'200_QUERY_IGNORED_BY_THE_OLD_CODE',$ok($r,200)&&(!$installed||($f['selected40']&&!$f['option999'])),$f,true);
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
            // URLs (it uses /katalog/slika/...), so the CLI hook media-sample of the b1153 helper is the ONLY source: it builds the
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
        if(($r['routeCount']??null)!==$this->expectedRoutes)throw new RuntimeException('ROUTE_COUNT_NOT_THE_B1152_COUNT');
        if($r['routesCached']!==false||$now!==[])throw new RuntimeException('ROUTE_CACHE_PRESENT_NOTHING_CHANGED');return $r;
    }

    /**
     * The new code as the CLI sees it, two ways: `artisan route:list --name=<route> --json` (read-only) and the b1153 helper.
     * The helper loads the new class MailBranding by PSR-4 (and checks by reflection that logo() exists; it is never called, so no setting is read and no URL
     * is built), calls the pure static functions SettingsService::logoHeight and PublicCatalogPresentation::perPage and viewMode with fixed values, checks
     * PublicStorefrontAppearance::freshBranding by reflection, and reads the payload edits (views included) as source markers. B1152 stays in BOTH states
     * (AppUrlOrigin::configured() is the pinned origin, PinAppUrlOrigin is in the global middleware list, bootstrap/app.php names it, the PDF raster guard),
     * and so do B1149, B1150 and B1151: site-assets.show, admin.settings.smtp.index, MailSettingsServiceProvider, the POST-only theme route, the B1150
     * classes and the media route, the password-change throttle and the merchant draft constant.
     * Installed: every new fact true; old: every new fact false.
     */
    private function featureCheck(bool $installed):array
    {
        $h=$this->helper($this->featureScript,['features',json_encode(self::newClasses(),JSON_THROW_ON_ERROR),self::MEDIA_ROUTE_NAME,$this->expectedAppUrl,json_encode(self::SOURCE_MARKERS,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES)]);$hits=[];
        foreach(['site-assets.show','admin.settings.smtp.index','public-shop.theme',self::MEDIA_ROUTE_NAME] as $name){
            $r=$this->artisan(['route:list','--name='.$name,'--json'],120);if($r['rc']!==0)throw new RuntimeException('ROUTE_LIST_FAILED');$list=json_decode(trim($r['stdout']),true,16);$n=0;
            if(is_array($list))foreach($list as $row)if(is_array($row)&&($row['name']??null)===$name)$n++;$hits[$name]=$n;
        }
        $facts=$h+['routeList'=>$hits,'expectInstalled'=>$installed];$this->emit('FEATURE_CLI_CHECK',$facts);
        // B1149, B1150, B1151 and B1152 stay in BOTH states (this rollout never touches the files that carry them).
        $kept=[$h['b1149ClassesLoaded'],$h['routeSiteAssets'],$h['routeSmtpIndex'],$h['providerRegistered'],$hits['site-assets.show']===1,$hits['admin.settings.smtp.index']===1];
        if($h['themeRoutePostOnly']!==true||$hits['public-shop.theme']!==1||$h['routesCached']!==false)throw new RuntimeException('B1148_THEME_ROUTE_MISSING');
        if(in_array(false,$kept,true))throw new RuntimeException('B1149_FEATURES_MISSING');
        if($h['b1150ClassesLoaded']!==true||$h['routeMedia']!==true||$hits[self::MEDIA_ROUTE_NAME]!==1)throw new RuntimeException('B1150_FEATURES_MISSING');
        if($h['throttleApi']!==true||$h['throttleResolvable']!==true||$h['webPasswordUsesThrottle']!==true||$h['apiPasswordUsesThrottle']!==true||$h['merchantIssueLabels']!==true)throw new RuntimeException('B1151_FEATURES_MISSING');
        if($h['originConfiguredIsExpected']!==true||$h['middlewareGlobal']!==true||$h['bootstrapNamesPin']!==true||$h['pdfRasterGuard']!==true)throw new RuntimeException('B1152_FEATURES_MISSING');
        // Old tells from new by the new class (not loadable before) and the markers (all false before, all true after).
        $classesLoadable=$h['newClassesLoaded']===$h['newClassesTotal']&&$h['newClassesTotal']>0;
        $new=[$classesLoadable,$h['logoHeightsApi'],$h['catalogWhitelist'],$h['freshBrandingApi'],$h['mailBrandingApi'],...array_values($h['sourceMarkers'])];
        if($installed&&in_array(false,$new,true))throw new RuntimeException('NEW_CLASSES_OR_FILES_NOT_PRESENT_AFTER_INSTALL');
        if(!$installed&&($h['newClassesLoaded']!==0||in_array(true,array_slice($new,1),true)))throw new RuntimeException('NEW_CLASSES_OR_FILES_PRESENT_IN_THE_OLD_STATE');
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
     * CLI, read-only, in its own uncached process (the pinned-with-the-package b1153-app-check.php env mode): the PHP extensions the
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
        $chain=$try('chainB1152Report1118',fn()=>$this->chain());if($chain!==null){$this->state['chain']=$chain;$v['retainedDirectoryModes']=$this->retainedModes();}$v['chain']=$chain;
        $manifest=$try('runtimeManifestPlusB1146B1147B1148B1149B1150B1151B1152',fn()=>$this->b1152Manifest());
        $targets=$try('overlayTargets',fn()=>$this->targets());$v['targets']=$targets;
        if($targets!==null){if(!$installed&&!$targets['preState'])$g['overlayTargets']=$targets['installed']?'OVERLAY_ALREADY_PRESENT_WITHOUT_B1153_STATE_NO_BACKUP_NOTHING_CHANGED':($targets['link']!==[]?'TARGET_IS_SYMLINK':'UNKNOWN_PRE_STATE_REFUSED');if($installed&&!$targets['installed'])$g['overlayTargets']='INSTALLED_DIGESTS_CHANGED';}
        $v['tree']=$manifest!==null&&$chain!==null?$try('activeTreeEqualsManifestPlusB1146B1147B1148B1149B1150B1151B1152',fn()=>$installed?LeanGates::verifyTree($this->active(),$this->installedManifest(),self::CONFIG_SHA,$chain['htaccessSHA256'],self::installedTreeFiles()):LeanGates::verifyTree($this->active(),$manifest,self::CONFIG_SHA,$chain['htaccessSHA256'],self::B1152_TREE_FILES)):null;
        if(!array_key_exists('activeTreeEqualsManifestPlusB1146B1147B1148B1149B1150B1151B1152',$g))$g['activeTreeEqualsManifestPlusB1146B1147B1148B1149B1150B1151B1152']='NOT_EVALUATED_CHAIN_OR_MANIFEST';
        if(!$installed)$v['replaceable']=$try('targetsReplaceable',function():array{$n=0;foreach($this->plan as $s){if($s['kind']==='modified'){LogoCatalogFiles::replaceable($this->target($s));$n++;}else{$d=dirname($this->target($s));while(!is_dir($d))$d=dirname($d);if(is_link($d)||!is_writable($d))throw new RuntimeException('TARGET_DIRECTORY_NOT_WRITABLE');}}return ['modifiedOneLinkNoAcl'=>$n];});
        // The two new classes of the payload are found through the PSR-4 fallback; an authoritative classmap would make them invisible, and bootstrap/app.php
        // names one of them. (After group A the web-SAPI class proof and the CLI feature check show that they really load.)
        $g['composerClassmapNotAuthoritative']=$this->classmapBlocker();
        $g['configNotCached']=file_exists($this->active().'/bootstrap/cache/config.php')?'CONFIG_CACHE_PRESENT':null;
        $g['noRouteCache']=$this->routeCacheFiles()===[]?null:'ROUTE_CACHE_PRESENT_NOTHING_CHANGED';
        $g['noMaintenanceMode']=file_exists($this->active().'/storage/framework/down')||file_exists($this->active().'/storage/framework/maintenance.php')?'MAINTENANCE_MODE_ACTIVE':null;
        $try('noLeftoverProbeOrTemp',function():void{FreshnessProbe::assertNoLeftover($this->active().'/public');if($this->ownTemps()!==[])throw new RuntimeException('LEFTOVER_B1147_B1148_B1149_B1150_B1151_B1152_OR_B1153_TEMP_FILE');});
        $v['caches']=$try('cacheState',fn()=>['routeCacheFiles'=>count($this->routeCacheFiles()),'compiledViews'=>$this->compiledViews(),'viewsCached'=>$this->compiledViews()>0]);
        $v['logs']=LeanGates::dailyLogs($this->active().'/storage/logs');$g['laravelDailyLog']=$v['logs']['dailyFiles']>0?null:'LARAVEL_DAILY_LOG_MISSING';
        $v['migrations']=$try('migrateStatus71Ran0Pending',fn()=>$this->migrationStatus());
        $app=$try('appStateDatabase',fn()=>$this->helper($this->appStateScript,['state']));
        if($app!==null){
            $shop=self::shopOf($app);$v['shop']=$shop+['writtenByThisTool'=>false];$v['app']=['backupsRunning'=>$app['backups']['running']];
            $g['shopStateRecorded']=!$shop['known']?'SHOP_SWITCH_VALUE_UNKNOWN':(!$installed&&!$shop['open']?'SHOP_IS_OFF_B1153_INSTALLS_ONLY_WITH_THE_SHOP_ON_NOTHING_CHANGED':null);
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
            $need=$this->requiredWindowSeconds((int)$v['web']['holdSeconds']);$v['timeBound']=['holdSeconds'=>(int)$v['web']['holdSeconds'],'groupPauseSeconds'=>$this->groupPause(),'smokeCapSeconds'=>$this->smokeCapSeconds(),'requiredWindowSeconds'=>$need,'rule'=>'5*HOLD+2*SMOKE_CAP+(4*GROUPS-2)*PAUSE+(2*GROUPS-1)*LIVENESS(210)+5*WEB_PROOF(30)+300<=WINDOW,GROUPS=5'];
            $this->scheduleNeedSeconds=max($this->scheduleNeedSeconds,$need);
            if($this->scheduleGate&&$app!==null){$s=$try('scheduleWindowFitsTimeBound',fn()=>$this->scheduleState($app));$v['scheduleTimeBound']=$s;if($s!==null&&$s['conflict']!==null)$g['scheduleWindowFitsTimeBound']='SCHEDULED_BACKUP_WITHIN_THE_'.$need.'_SECONDS_THIS_RUN_MAY_NEED';}
        }else $g['timeBound']='NOT_EVALUATED_WEB_PROBE';
        $v['merchantApproval']=$try('merchantApprovalRead',fn()=>self::approvalFacts($this->helper($this->routesScript,['approval'])));
        $this->emailFacts=null;$try('liveSmokes',fn()=>$this->smokes($installed));
        // The old-state e-mail obfuscation count is the baseline the installed smokes compare with (INFO only); kept in the saved state, never printed as a value.
        if(!$installed&&$this->emailFacts!==null)$this->state['emailBaseline']=$this->emailFacts;
        $blockers=array_values(array_unique(array_filter($g,static fn($c)=>$c!==null)));
        if(isset($v['shop']))$this->state['shopRecorded']=$v['shop'];
        $report=['stage'=>$stage,'gates'=>array_map(static fn($c)=>$c??'PASS',$g),'values'=>$v,'blockers'=>$blockers,'applicationFiles'=>'NONE','database'=>'NO_PRODUCT_CART_OR_SWITCH_WRITES_SESSIONS_AND_APP_CACHE_ONLY','shopSwitch'=>'READ_ONLY','transientProbe'=>'WRITTEN_AND_REMOVED','privateState'=>'B1153_NAMESPACE_ONLY'];$this->emit('PREFLIGHT',$report);return $report;
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
        if(!file_exists($this->pointer))return false;if(is_link($this->pointer)||filesize($this->pointer)>8192)throw new RuntimeException('B1153_POINTER');$p=json_decode((string)file_get_contents($this->pointer),true,8,JSON_THROW_ON_ERROR);
        if(!preg_match('/^[a-f0-9]{32}$/D',(string)($p['id']??''))||($p['private']??null)!==$this->base.'/'.$p['id'])throw new RuntimeException('B1153_POINTER_SCOPE');$path=$p['private'].'/state.json';
        if(is_link($path)||realpath($path)!==$path||filesize($path)>16777216)throw new RuntimeException('B1153_STATE');$this->state=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
        if(($this->state['schemaVersion']??0)!==1||($this->state['kind']??null)!=='B1153'||($this->state['id']??null)!==$p['id'])throw new RuntimeException('B1153_STATE_BINDING');return true;
    }

    private function begin():void
    {
        $id=bin2hex(random_bytes(16));$private=$this->base.'/'.$id;if(!mkdir($private,0700))throw new RuntimeException('PRIVATE_TRANSACTION_CREATE');
        $this->state=['schemaVersion'=>1,'kind'=>'B1153','id'=>$id,'private'=>$private,'phase'=>'NEW','startedAt'=>time(),'probes'=>[],'temps'=>[],'applied'=>[],'createdDirs'=>[]];$this->save('PREFLIGHT');
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
        $this->state['groupPauseSeconds']=(int)($preflight['values']['web']['revalidateFreq']??2)+1;$this->state['holdSeconds']=(int)($preflight['values']['web']['holdSeconds']??FreshnessProbe::MAX_HOLD_SECONDS);$this->state['approvalBefore']=$preflight['values']['merchantApproval']??null;$doc=$dir.'/manifest.json';if(is_file($doc))unlink($doc);AtomicFile::privateCopy($doc,json_encode(['batch'=>'B1153','files'=>array_values($files)],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
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
     * The last gate before the first file of the guard group (the e-mail views and the provider that call MailBranding): every new class of STEPS must hold
     * exactly the payload in the tree and the web-SAPI proof of this run must have passed. Without both, no file of that group is ever written.
     */
    private function guardGroupCheck():void
    {
        foreach($this->plan as $x)if($x['kind']==='new'&&$this->classify($x)['state']!=='payload')throw new RuntimeException('GUARD_REFUSED_A_NEW_CLASS_IS_NOT_IN_PLACE');
        $proof=$this->state['webClassProof']??null;if(!is_array($proof)||($proof['classes']??null)!==array_keys($this->newClassFiles()))throw new RuntimeException('GUARD_REFUSED_NO_WEB_SAPI_CLASS_PROOF');
    }

    private function install():void
    {
        $this->state['logBaseline']=LeanGates::logBaseline($this->active().'/storage/logs');$this->state['applied']=[];unset($this->state['webClassProof']);$this->save('INSTALL_INTENT');$group=null;
        // Between groups every web worker revalidates the files of the previous group first (revalidate_freq + 1 s), so a
        // request never combines new bytecode of a later group with still-cached old bytecode of an earlier one.
        $pause=$this->groupPause();
        foreach($this->plan as $s){
            // Group E (the views and the provider) comes after the full measured hold, which always spans group A (the hold runs from the last tree change), and
            // after the web-SAPI proof that the new class loads; the other group boundaries get the short revalidation pause.
            if($group!==null&&$group!==$s['group']){
                if($s['group']===self::GUARD_GROUP){$this->hold('before-guard-group',$this->fullHold());$this->webClassProof('before-guard-group');$this->fault('CLASSES_PROVEN');}
                else{$this->emit('OVERLAY_GROUP_PAUSE',['after'=>$group,'before'=>$s['group'],'seconds'=>$pause]);sleep($pause);}
                $this->fault('GROUP_DONE_'.$group);$this->liveness($group);
            }
            $group=$s['group'];
            $this->fault('BEFORE_STEP_'.$s['n']);if($s['group']===self::GUARD_GROUP)$this->guardGroupCheck();$t=$this->target($s);$bytes=$this->payload($s);$this->state['applied'][$s['n']]='PENDING';
            if($s['kind']==='new'){
                // Missing directories (resources/views/vendor/mail/html needs three, resources/views/emails/partials one) are created outermost first, each recorded in the saved
                // state BEFORE its mkdir, so a kill leaves a record; a rollback removes the empty ones in reverse (innermost first). Nothing outside the CMS tree is ever created.
                $dir=dirname($t);$missing=[];for($d=$dir;!is_dir($d);$d=dirname($d)){if(is_link($d)||!str_starts_with($d,$this->active().'/'))throw new RuntimeException('TARGET_PARENT_MISSING');$missing[]=$d;}
                foreach(array_reverse($missing) as $d){$this->state['createdDirs'][]=$d;$this->persist();if(!mkdir($d,0755))throw new RuntimeException('TARGET_DIRECTORY_CREATE');chmod($d,0755);$pg=stat(dirname($d))['gid'];if(stat($d)['gid']!==$pg)@chgrp($d,$pg);clearstatcache(true,$d);if(stat($d)['gid']!==$pg)throw new RuntimeException('OVERLAY_DIRECTORY_GID_NOT_PRESERVABLE');$this->emit('OVERLAY_DIRECTORY_CREATED',['path'=>substr($d,strlen($this->active())+1),'mode'=>'0755']);}
                $temp=$this->recordTemp(LogoCatalogFiles::tempFor($t));$receipt=LogoCatalogFiles::create($t,$bytes,0644,$temp,stat($dir)['gid']);
            }else{$b=$this->state['backup']['files'][$s['n']];$temp=$this->recordTemp(LogoCatalogFiles::tempFor($t));$receipt=LogoCatalogFiles::replace($t,$bytes,$b['mode'],$b['gid'],$temp,(string)$s['preSHA256']);}
            $this->state['temps']=array_values(array_filter($this->state['temps'],static fn($x)=>$x!==$temp));$this->state['applied'][$s['n']]='DONE';$this->changed();$this->persist();
            $this->emit('OVERLAY_STEP',['n'=>$s['n'],'group'=>$s['group'],'kind'=>$s['kind'],'path'=>$s['path'],'sha256'=>$s['payloadSHA256']]+$receipt);$this->fault('AFTER_STEP_'.$s['n']);
        }
        // The last group is E (the views and the provider, the storefront layout last): ONE revalidation pause so every worker runs it, then the liveness smoke at once
        // (/up and /login first; a failure rolls group E back before anything else), and only then route:clear, view:clear and queue:restart (the acceptance judges the rest).
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
                // Group E (the views and the provider, the storefront layout first) goes FIRST, then the full measured hold, so no web worker still runs a view or
                // the provider that calls MailBranding when anything else changes; groups D, C and B follow with the revalidation pause between them; before the
                // new files (group A) the full hold again, the new files are removed last. A fresh process resuming a rollback times each wait from the saved
                // wall-clock time of the last change.
                $key=$s['group'];$boundary=$group!==null&&$group!==$key;$prev=$group;$group=$key;
                try{
                    if($boundary){
                        if($prev===self::GUARD_GROUP){$this->emit('ROLLBACK_AFTER_GUARD_GROUP',['guardGroupIsOld'=>count(array_filter($this->plan,fn($x)=>$x['group']===self::GUARD_GROUP&&$this->classify($x)['state']!=='pre'))===0]);$this->hold('rollback-after-guard-group',$this->fullHold());}
                        elseif($key==='A')$this->hold('rollback-before-new-classes',$this->fullHold());
                        else{$this->emit('ROLLBACK_GROUP_PAUSE',['before'=>$key,'seconds'=>$pause]);$this->hold('rollback-pause-'.$key,$pause);}
                    }
                    $this->fault('BEFORE_RESTORE_'.$s['n']);$c=$this->classify($s);$t=$this->target($s);
                    if($s['kind']==='modified'){
                        if($c['state']==='payload'){$b=$backup['files'][$s['n']];$bytes=AtomicFile::readPrivate($b['copy'],$b['sha256']);LogoCatalogFiles::replace($t,$bytes,$b['mode'],$b['gid'],$this->recordTemp(LogoCatalogFiles::tempFor($t)),$s['payloadSHA256']);$restored++;$this->changed();}
                        elseif($c['state']!=='pre')throw new RuntimeException('ROLLBACK_FOREIGN_CONTENT_NOT_OVERWRITTEN_'.$s['n']);
                    }elseif($c['state']==='payload'){LogoCatalogFiles::removeExact($t,$s['payloadSHA256']);$removed++;$this->changed();}
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
            $tree=LeanGates::verifyTree($this->active(),$this->b1152Manifest(),self::CONFIG_SHA,$this->state['chain']['htaccessSHA256'],self::B1152_TREE_FILES);$this->emit('ACTIVE_TREE_AFTER_ROLLBACK',$tree+['equalsManifestPlusB1146B1147B1148B1149B1150B1151B1152'=>true]);
            try{$this->mailFingerprintUnchanged('after-rollback');}catch(Throwable $e){$this->emit('OWNER_NOTICE_MAIL_FINGERPRINT',self::code($e));}
            $this->approvalReport('after-rollback');
        }catch(Throwable $e){$problems[]=self::code($e);}
        if($problems!==[]){
            try{$this->save('ROLLBACK_INCOMPLETE');}catch(Throwable){$this->emit('ROLLBACK_STATE_UNWRITABLE','FIX_DISK_OR_PERMISSIONS_THEN_RUN_THE_SAME_COMMAND');}
            $this->recoveryNote(array_values(array_unique($problems)));return false;
        }
        $this->save('ROLLED_BACK');$this->emit('ROLLED_BACK',['files'=>'EXACT_PRIVATE_COPIES','newFiles'=>'REMOVED','routeCache'=>'NONE_NEVER_CREATED','compiledViews'=>'CLEARED_REBUILT_ON_DEMAND_NOT_PRECOMPILED','tree'=>'EQUALS_8569_FILE_MANIFEST_PLUS_B1146_B1147_B1148_B1149_B1150_B1151_B1152','shopSwitch'=>'NOT_WRITTEN']);return true;
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
            elseif($f['kind']==='modified'&&$f['now']==='absent')echo 'OWNER_INSPECT: '.$t.' IS_MISSING_NOT_REMOVED_BY_B1153_THE_OLD_BYTES_ARE_IN '.($f['backupCopy']??'NO_BACKUP_COPY').' SHA256 '.($f['sha256']??'UNKNOWN').PHP_EOL;
            elseif(!in_array($f['now'],['pre','absent','payload'],true))echo 'OWNER_INSPECT: '.$t.' IS_'.strtoupper($f['now']).'_NOT_TOUCHED_BY_B1153'.PHP_EOL;
            if($act===[])continue;
            if($last!==null&&$last!==$key){if($last===self::GUARD_GROUP||$key==='A')$lines[]='sleep '.$this->fullHold();else $lines[]='sleep '.$pause;}
            $last=$key;array_push($lines,...$act);
        }
        if($record)foreach(array_reverse($this->state['createdDirs']??[]) as $dir)if(is_dir($dir)&&!is_link($dir))$lines[]='rmdir --ignore-fail-on-non-empty -- '.$q($dir);
        $lines[]=$view;$lines[]='echo B1153_MANUAL_RECOVERY_BLOCK_DONE';
        echo 'OWNER_COMMAND_BLOCK=PASTE_EVERYTHING_FROM_THE_bash_-e_LINE_TO_THE_B1153_RECOVERY_LINE_INTO_THE_SSH_SHELL_IT_STOPS_AT_THE_FIRST_FAILING_LINE'.PHP_EOL;
        echo "bash -e <<'B1153_RECOVERY'".PHP_EOL.implode(PHP_EOL,$lines).PHP_EOL.'B1153_RECOVERY'.PHP_EOL;
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
        $app=$this->helper($this->appStateScript,['state']);$shop=self::shopOf($app);if($shop!==array_diff_key($this->state['shopRecorded']??[],['writtenByThisTool'=>true]))throw new RuntimeException('SHOP_STATE_CHANGED_BEFORE_FIRST_CHANGE');if(!$shop['open'])throw new RuntimeException('SHOP_IS_OFF_B1153_INSTALLS_ONLY_WITH_THE_SHOP_ON_NOTHING_CHANGED');
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
        $this->final=$final;$this->emit('B1153_RESULT',['overlay'=>'LOGO_SIZES_MAIL_LOGO_CATALOG_VIEW_PER_PAGE_'.count($this->plan).'_FILES','baseCommit'=>self::BASE_COMMIT,'sourceCommit'=>self::SOURCE_COMMIT,'newFiles'=>$new,'modifiedFiles'=>count($this->plan)-$new,'appUrl'=>'EXACTLY_'.$this->expectedAppUrl.'_VERIFIED_BEFORE_ANY_CHANGE','newRoutes'=>'NONE_ROUTE_COUNT_'.$this->expectedRoutes.'_UNCHANGED','legalEmailCheck'=>'INFO_ONLY_IN_THIS_TOOL_NO_LEGAL_TEXT_IS_IN_THE_PAYLOAD','passwordEndpoints'=>'NEVER_CALLED_NO_LOGIN','mail'=>'NEVER_SENT_NO_TEST_MAIL','pdfGeneration'=>'NOT_EXERCISED','settingsWritten'=>'NO_STORED_LOGO_HEIGHT_IS_CHANGED_BY_THIS_TOOL','directoriesCreated'=>count(array_filter((array)($this->state['createdDirs']??[]))),'tests'=>'NOT_INSTALLED','shopSwitch'=>'READ_ONLY_NOT_WRITTEN','smtpSettings'=>'NOT_READ_NOT_WRITTEN_NO_TEST_MAIL_ONLY_A_TRANSPORT_FINGERPRINT','routeCache'=>'NONE','migrations'=>'NONE','composer'=>'NONE','env'=>'UNCHANGED','config'=>'UNCHANGED','queue'=>'RESTARTED_VIA_ARTISAN_queue:restart','opcache'=>'TARGETED_PER_FILE_INVALIDATION_ONLY','backupDirectory'=>$this->state['backup']['directory']??'NONE_ALREADY_INSTALLED_BEFORE_THIS_TOOL_STATE']);
        $this->cssNotice();
        $this->emit('OWNER_NEXT_STEPS',array_merge([$this->purgeStep(),
            '2_LOG_IN_ON_THE_WEB_AND_IN_THE_ANDROID_APP_AND_CHECK_THAT_YOU_STAY_ON_'.parse_url($this->expectedAppUrl,PHP_URL_HOST).'_(THIS_TOOL_NEVER_LOGS_IN)',
            '3_SEND_ONE_TEST_MAIL_TO_YOURSELF_AND_OPEN_IT_IN_GMAIL_(THE_LOGO_SHOWS_ONLY_WHEN_THE_LIGHT_THEME_LOGO_IS_A_PNG_OR_JPG)_THIS_TOOL_NEVER_SENDS_A_MAIL',
            '4_OPEN_THE_STOREFRONT_AND_CHECK_THE_HEADER_LOGO_HEIGHT_(A_STORED_VALUE_SUCH_AS_38_STAYS_UNTIL_YOU_CHANGE_IT_IN_THE_SITE_APPEARANCE_SETTINGS)_AND_THE_CATALOG_LIST_VIEW_ON_A_PHONE',
            '5_OPEN_ONE_PDF_DOCUMENT_AND_ONE_PDF_REPORT_AND_CHECK_THE_LOGO_(THE_PDF_GENERATION_WAS_NOT_EXERCISED_BY_THIS_TOOL_AND_THE_DOCUMENT_SETTINGS_PAGE_ONLY_ANSWERED_AS_A_GUEST_WITH_THE_LOGIN_REDIRECT)',
            '6_THE_SHOP_AND_THE_SMTP_SETTINGS_STAY_AS_YOU_SET_THEM_THIS_TOOL_NEVER_CHANGES_THEM',
            '7_DO_NOT_RERUN_B1144_TO_B1152_OR_THEIR_ROLLBACK'],isset($this->state['backup']['directory'])?['ROLLBACK_IF_NEEDED=bash /home/icaffeco/ald1n-project/incoming/b1153-installer.sh --rollback (THE_SHOP_MAY_STAY_ON)']:['NO_B1153_BACKUP_IN_THIS_STATE_NO_AUTOMATIC_ROLLBACK']));
        $this->emit('DO_NOT_RERUN_B1144_B1145_B1146_B1147_B1148_B1149_B1150_B1151_OR_B1152_AFTER_B1153','THEIR_MANIFEST_GATES_NOW_SEE_THE_'.count($this->plan).'_B1153_FILES_AND_THEIR_--rollback_WOULD_REFUSE_FOREIGN_BYTES');return 0;
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
                if(!$this->restore('OWNER_REQUESTED_ROLLBACK'))return 2;$this->final='PASS_ROLLED_BACK_BY_OWNER';$this->emit('OWNER_NOTICE','THE_B1152_STATE_IS_ACTIVE_AGAIN_A_LATER_RUN_OF_THE_SAME_COMMAND_WITHOUT_OPTIONS_INSTALLS_AGAIN');
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
                $this->emit('OWNER_NEXT_STEPS',[$first,'2_THEN_RUN_THE_SAME_COMMAND_AGAIN_IT_REPEATS_ONLY_THE_ACCEPTANCE_CHECKS_NO_FILE_IS_CHANGED','3_OR_RESTORE_THE_OLD_FILES=bash /home/icaffeco/ald1n-project/incoming/b1153-installer.sh --rollback',$this->purgeStep()]);
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
        $tree=LeanGates::verifyTree($this->active(),$this->installedManifest(),self::CONFIG_SHA,$this->state['chain']['htaccessSHA256'],self::installedTreeFiles());$this->emit($label,$tree+['manifestPlusB1146B1147B1148B1149B1150B1151B1152B1153'=>true]);
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
final class LogoCatalogFiles
{
    public static function tempFor(string $target):string {return dirname($target).'/.'.basename($target).'.b1153-'.bin2hex(random_bytes(8)).'.tmp';}
    public static function isOwnTemp(string $path):bool {return preg_match('~/\.[A-Za-z0-9_.-]+\.b1153-[a-f0-9]{16}\.tmp$~D',$path)===1;}

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
 * The HTTP client of B1153: the pinned B1150 client (MediaWeb) for every request, plus the two catalog layout queries per_page and view, which its
 * allowlist (sort, page, v, s) refuses. Only GET and HEAD, only /katalog, per_page only digits and view only lowercase letters, the release marker is
 * always sent, no redirect is followed, nothing is ever sent but a request line and the pinned headers; the same host, TLS options and body limit as MediaWeb.
 * The path, the marker and the rest of the query are still judged by MediaWeb::target.
 */
final class LogoCatalogWeb
{
    private MediaWeb $inner;
    public function __construct(){$this->inner=new MediaWeb();}

    private static function extra(array $query):array {return array_intersect_key($query,['per_page'=>1,'view'=>1]);}

    public static function target(string $path,array $query,?string $marker):string
    {
        $extra=self::extra($query);if($extra===[])return MediaWeb::target($path,$query,$marker);$rest=array_diff_key($query,$extra);
        if($path!=='/katalog'||$marker===null)throw new RuntimeException('SMOKE_QUERY_CATALOG_LAYOUT_ONLY');
        foreach($extra as $k=>$v)if(!is_string($v)||preg_match($k==='per_page'?'/^[0-9]{1,4}$/D':'/^[a-z]{1,10}$/D',$v)!==1)throw new RuntimeException('SMOKE_QUERY');
        MediaWeb::target($path,$rest,$marker);
        return $path.'?'.http_build_query($rest+$extra+['ald1n_release_check'=>$marker],'','&',PHP_QUERY_RFC3986);
    }

    public function fetch(string $path,array $query,?string $marker,string $method='GET',bool $origin=false,array $headers=[]):array
    {
        if(self::extra($query)===[])return $this->inner->fetch($path,$query,$marker,$method,$origin,$headers);
        if(!in_array($method,['GET','HEAD'],true))throw new RuntimeException('SMOKE_METHOD');if($origin||$headers!==[])throw new RuntimeException('SMOKE_CATALOG_LAYOUT_PLAIN_GET_ONLY');
        $target=self::target($path,$query,$marker);$h=MediaWeb::headers();$body='';$over=false;
        $curl=curl_init('https://cms.ald1n.com'.$target);
        curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_CONNECTTIMEOUT=>6,CURLOPT_TIMEOUT=>25,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_NOPROXY=>'*',CURLOPT_USERAGENT=>'Ald1n-B1153-owned-release',CURLOPT_HTTPHEADER=>['Host: cms.ald1n.com','Cache-Control: no-cache'],
            CURLOPT_HEADERFUNCTION=>static function($c,string $line)use(&$h):int{MediaWeb::header($h,$line);if(preg_match('/^CF-RAY:/i',$line))$h['cfRay']=true;return strlen($line);},
            CURLOPT_WRITEFUNCTION=>static function($c,string $chunk)use(&$body,&$over):int{if(strlen($body)+strlen($chunk)>MediaWeb::MAX_BODY){$over=true;return 0;}$body.=$chunk;return strlen($chunk);}]+($method==='HEAD'?[CURLOPT_NOBODY=>true]:[]));
        curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);curl_close($curl);
        return MediaWeb::result($error!==0||$over,$status,$h,$body);
    }

    public function probe(string $name,string $token,bool $invalidate,?string $route=null):array {return $this->inner->probe($name,$token,$invalidate,$route);}
}
