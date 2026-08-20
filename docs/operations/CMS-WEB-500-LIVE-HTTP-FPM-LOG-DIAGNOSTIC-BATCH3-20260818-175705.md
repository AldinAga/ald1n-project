
============================================================
CMS WEB 500 - LIVE HTTP/FPM LOG DIAGNOSTIC - BATCH 3
============================================================
DATE=Tue Aug 18 17:57:05 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
DOMAIN=cms.ald1n.com
MODE=READ_ONLY_APP_DIAGNOSTIC_WITH_EPHEMERAL_TOKEN_GUARDED_WEB_SAPI_PROBE
PURPOSE=CAPTURE_FRESH_AUTHENTICATED_BROWSER_500_AND_COMPARE_CLI_VS_REAL_WEB_FPM_RUNTIME
PERSISTENT_SOURCE_MUTATION=NO
DATABASE_MUTATION=NO
CACHE_MUTATION=NO
EPHEMERAL_PUBLIC_PROBE=YES_RANDOM_TOKEN_AND_TRAP_REMOVAL
MOBILE_SOURCE_CHANGES=NO
EAS_BUILD=NO

============================================================
0. PREFLIGHT
============================================================
CONCURRENCY_LOCK=ACQUIRED
PHP 8.4.23 (cli) (built: Jul  9 2026 00:00:00) (NTS)
CMS_REALPATH=/home/icaffeco/ald1n-project/apps/cms/current
PUBLIC_REALPATH=/home/icaffeco/ald1n-project/apps/cms/current/public
PUBLIC_INDEX_REALPATH=/home/icaffeco/ald1n-project/apps/cms/current/public/index.php
PREFLIGHT=PASS

============================================================
1. CURRENT LARAVEL + AUTH ROUTE RUNTIME
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.cms-web-500-live-http-fpm-batch3.fH7Qsn/runtime.php
LOGIN_ROUTE=login
LOGIN_METHODS=GET,HEAD
LOGIN_MIDDLEWARE=web,guest
LOGIN_ACTION=App\Http\Controllers\Auth\AuthenticatedSessionController@create
DASHBOARD_ROUTE=/
DASHBOARD_METHODS=GET,HEAD
DASHBOARD_MIDDLEWARE=web,auth,active,tracked-session
DASHBOARD_ACTION=App\Http\Controllers\DashboardController
HOME_ROUTE=MISSING
APP_ENV=production
APP_URL=https://cms.ald1n.com
SESSION_DRIVER=file
CACHE_STORE=redis
AUTH_GUARD=web

--- route:list login/dashboard/root signals ---


  The "--columns" option does not exist.




  The "--columns" option does not exist.




  The "--columns" option does not exist.



--- authentication redirect/dashboard source signals ---
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Auth/AuthenticatedSessionController.php:117:        return redirect()->route('dashboard');
/home/icaffeco/ald1n-project/apps/cms/current/app/Http/Controllers/Auth/GoogleWebAuthController.php:192:            return redirect()->intended(route('dashboard'));
/home/icaffeco/ald1n-project/apps/cms/current/routes/web.php:104:    Route::get('/', DashboardController::class)->name('dashboard');

============================================================
2. CPANEL/WEBSERVER DOCUMENT ROOT + PHP HANDLER SIGNALS
============================================================

--- /var/cpanel/userdata/icaffeco/cms.ald1n.com ---
documentroot: /home/icaffeco/ald1n-project/apps/cms/current/public
homedir: /home/icaffeco
ip: 185.119.89.153
serveralias: www.cms.ald1n.com
servername: cms.ald1n.com

--- /var/cpanel/userdata/icaffeco/cms.ald1n.com.cache ---
{"usecanonicalname":"Off","ip":"185.119.89.153","phpopenbasedirprotect":"1","serveradmin":"webmaster@cms.ald1n.com","group":"icaffeco","documentroot":"/home/icaffeco/ald1n-project/apps/cms/current/public","no_cache_update":"0","hascgi":"1","servername":"cms.ald1n.com","owner":"root","serveralias":"www.cms.ald1n.com","user":"icaffeco","homedir":"/home/icaffeco","userdirprotect":"","ipv6":null}

--- /var/cpanel/userdata/icaffeco/main ---

--- UAPI domain metadata ---
   "func" : "single_domain_data",
   "module" : "DomainInfo",
         "documentroot" : "/home/icaffeco/ald1n-project/apps/cms/current/public",
         "domain" : "cms.ald1n.com",
         "phpopenbasedirprotect" : "1",
         "serveradmin" : "webmaster@cms.ald1n.com",
         "serveralias" : "www.cms.ald1n.com",
         "servername" : "cms.ald1n.com",
         "status" : "not redirected",
         "type" : "sub_domain",
      "errors" : null,
      "status" : 1,

--- .htaccess PHP handler/rewrite signals ---

### /home/icaffeco/ald1n-project/apps/cms/current/public/.htaccess ###
7:<IfModule mod_rewrite.c>
12:    RewriteEngine On
14:    RewriteCond %{HTTP:Authorization} .
15:    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
17:    RewriteCond %{HTTP:x-xsrf-token} .
18:    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-TOKEN}]
20:    RewriteCond %{REQUEST_FILENAME} !-d
21:    RewriteCond %{REQUEST_URI} (.+)/$
22:    RewriteRule ^ %1 [L,R=301]
24:    RewriteCond %{REQUEST_FILENAME} !-d
25:    RewriteCond %{REQUEST_FILENAME} !-f
26:    RewriteRule ^ index.php [L]

### /home/icaffeco/public_html/.htaccess ###
2:RewriteEngine On
3:RewriteBase /
4:RewriteCond %{QUERY_STRING} (author=\d+) [NC,OR]
5:RewriteCond %{REQUEST_URI} ^.*wp-json/wp/v2/users(?!/me) [NC]
6:RewriteRule .* - [F,L]
11:<IfModule mod_rewrite.c>
12:RewriteEngine on
13:RewriteRule litespeed/debug/.*\.log$ - [F,L]
14:RewriteRule \.litespeed_conf\.dat - [F,L]
18:RewriteRule .* - [E=Cache-Control:no-autoflush]
21:RewriteCond %{REQUEST_URI} /wp-admin/admin-ajax\.php
22:RewriteCond %{QUERY_STRING} action=async_litespeed
23:RewriteRule .* - [E=noabort:1]
45:<IfModule mod_rewrite.c>
46:RewriteEngine On
47:RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
48:RewriteBase /
49:RewriteRule ^index\.php$ - [L]
50:RewriteCond %{REQUEST_FILENAME} !-f
51:RewriteCond %{REQUEST_FILENAME} !-d
52:RewriteRule . /index.php [L]
57:RewriteCond %{HTTP_HOST} ^ald1n\.com$ [OR]
58:RewriteCond %{HTTP_HOST} ^www\.ald1n\.com$
59:RewriteRule ^cms\/?$ "https\:\/\/cms\.ald1n\.com\/" [R=301,L]
63:<files xmlrpc.php>
80:<FilesMatch "^.*(((?:wp-config)\.(?:php|bak|swp))|php.ini|\.[hH][tT][aApP].*|((?:error_log|readme|license|changelog|-config|-sample)\.(?:php|md|log|txt|htm|html)))$">
86:RewriteEngine on
87:RewriteCond %{HTTP_USER_AGENT} (?:virusbot|spambot|evilbot|acunetix|BLEXBot|domaincrawler\.com|LinkpadBot|MJ12bot/v|majestic12\.co\.uk|AhrefsBot|TwengaBot|SemrushBot|nikto|winhttp|Xenu\s+Link\s+Sleuth|Baiduspider|HTTrack|clshttp|harvest|extract|grab|miner|python-requests) [NC]
88:RewriteRule ^(.*)$ http://no.access/

============================================================
3. REAL WEB-SAPI / FPM PROBE THROUGH HTTPS
============================================================
No syntax errors detected in /home/icaffeco/ald1n-project/incoming/.cms-web-500-live-http-fpm-batch3.fH7Qsn/dashboard-path.php
DASHBOARD_COMPILED_PATH=/home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/d9c1fae579fd48a8c10d879a5e4e4ea9.php
DASHBOARD_SOURCE_MTIME=1787066764
DASHBOARD_COMPILED_MTIME=1787068074
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/d9c1fae579fd48a8c10d879a5e4e4ea9.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/public/__ald1n_runtime_probe_59beadba4557cc8560d21e3d7190656f.php
REAL_WEB_SAPI_PROBE=PASS
HTTP/2 200 
date: Tue, 18 Aug 2026 15:57:08 GMT
content-type: application/json
x-powered-by: PHP/8.4.23
cache-control: no-store
report-to: {"group":"cf-nel","max_age":604800,"endpoints":[{"url":"https://a.nel.cloudflare.com/report/v4?s=vBF5%2FGEyeqV4o3IbV3P1X%2BCvA3YuNX3Y1MY6NRgc5Otk%2BgwC6ZsKw9HwTiPIA5knsH4S%2BEiisLTiayapUdbMDiZzPY%2BPd97YSZbYP5bAWQkyXGIj4Tn0x45%2F1tsQ%2B5nV"}]}
vary: Accept-Encoding
server: cloudflare
x-robots-tag: noindex, nofollow, noarchive
x-content-type-options: nosniff
referrer-policy: strict-origin-when-cross-origin
alt-svc: h3=":443"; ma=86400
x-turbo-charged-by: LiteSpeed
cf-cache-status: DYNAMIC
nel: {"report_to":"cf-nel","success_fraction":0.0,"max_age":604800}
speculation-rules: "/cdn-cgi/speculation"
cf-ray: a2d21cd2699c076b-BEG

{
    "php_version": "8.4.23",
    "php_sapi": "litespeed",
    "document_root": "/home/icaffeco/ald1n-project/apps/cms/current/public",
    "script_filename": "/home/icaffeco/ald1n-project/apps/cms/current/public/__ald1n_runtime_probe_59beadba4557cc8560d21e3d7190656f.php",
    "probe_realpath": "/home/icaffeco/ald1n-project/apps/cms/current/public/__ald1n_runtime_probe_59beadba4557cc8560d21e3d7190656f.php",
    "public_realpath": "/home/icaffeco/ald1n-project/apps/cms/current/public",
    "project_realpath": "/home/icaffeco/ald1n-project/apps/cms/current",
    "dashboard_path": "/home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/d9c1fae579fd48a8c10d879a5e4e4ea9.php",
    "dashboard_exists": true,
    "dashboard_file_mtime": 1787068074,
    "opcache_enable": "1",
    "opcache_validate_timestamps": "1",
    "opcache_revalidate_freq": "2",
    "opcache_file_update_protection": "2",
    "opcache_restrict_api": "",
    "realpath_cache_ttl": "120",
    "opcache_status_available": true,
    "opcache_restart_pending": false,
    "opcache_restart_in_progress": false,
    "dashboard_cached_in_opcache": false,
    "dashboard_cached_timestamp": null,
    "dashboard_cached_last_used_timestamp": null,
    "dashboard_cached_hits": null
}
EPHEMERAL_WEB_PROBE_REMOVED=YES

--- public/login/dashboard HTTP headers without auth ---

### https://cms.ald1n.com/ ###
HTTP/2 302 
date: Tue, 18 Aug 2026 15:57:09 GMT
content-type: text/html; charset=utf-8
location: https://cms.ald1n.com/login
x-powered-by: PHP/8.4.23
cache-control: no-cache, no-store, must-revalidate, max-age=0
x-content-type-options: nosniff
x-frame-options: SAMEORIGIN
referrer-policy: strict-origin-when-cross-origin
permissions-policy: camera=(), microphone=(), geolocation=(), payment=()
cross-origin-opener-policy: same-origin
content-security-policy: base-uri 'self'; frame-ancestors 'self'; form-action 'self'; object-src 'none'; frame-src 'self' https://challenges.cloudflare.com https://www.youtube-nocookie.com https://www.youtube.com
strict-transport-security: max-age=31536000; includeSubDomains
x-request-id: 46d933e6-1cae-4547-ae27-7937d1463bbb
set-cookie: XSRF-TOKEN=eyJpdiI6IjRLdFJIbis2K1ozZzFZbDZPRUpFS1E9PSIsInZhbHVlIjoiQzA1aFVZd1hIMm5ZKzlWbXdhejI5MEVIZGwyMGxQN3hNMDdpQytuaHVuQkwwR1hzT0pPWm5MNzZCdWtrWmtLV082UXJoVnArSEwwRmRnTEdJY0hrRjZKaGU4WDF3NVNlTUFRcUpBSks4YndmRDJGQ296aEcreUV3ak9leDNCSHAiLCJtYWMiOiJmNGQ0NjA2Mjg1NDIwMzg2YjgyYjBiMzU5ODQ5NTk0ZTQ4YTllYzA4N2QzMDQ2MDRmY2UyZWRmOGVlN2E3NjllIiwidGFnIjoiIn0%3D; expires=Tue, 18 Aug 2026 17:57:09 GMT; Max-Age=7200; path=/; domain=cms.ald1n.com; secure; samesite=lax
set-cookie: ald1n-cms-session=eyJpdiI6ImYzT09TbDdJTnR4WDNBbTFsSHJEUVE9PSIsInZhbHVlIjoiUWFkYVRHdUpZTkpRRmZrR2ZsMnZjK0gvdWFSdENqS2dQRmhNRnB1d2RTOXZ2T2hHSExMNVZzK3ZkbjkzRnVjMlpra1lqR1lVcnRSekcvOWV2dWRVWEtDeEQ3c2FJdnMvUnlyWUtTVHl1UWNtUDNFaUZrdk1vVVZtbElGYjRNMlAiLCJtYWMiOiI3MWE4N2M0ODY1ZWQ5YmFlOTRiMTY5Nzc1N2I5MGI3OWUyM2RjMmZjMmUzM2FmMTc2YzBhYTkzYTIzYWRmZWI3IiwidGFnIjoiIn0%3D; expires=Tue, 18 Aug 2026 17:57:09 GMT; Max-Age=7200; path=/; domain=cms.ald1n.com; secure; httponly; samesite=lax
server: cloudflare
x-robots-tag: noindex, nofollow, noarchive
alt-svc: h3=":443"; ma=86400
x-turbo-charged-by: LiteSpeed
cf-cache-status: DYNAMIC
speculation-rules: "/cdn-cgi/speculation"
report-to: {"group":"cf-nel","max_age":604800,"endpoints":[{"url":"https://a.nel.cloudflare.com/report/v4?s=Y2FK37c1DRxPwDoqQ25lzmUeaEBzIlbZmIKDuDV1b5nmqtRJBROuO1J4Y3%2FSZsfeyKVtNvtKo8sRKXWPjIgxlbO84Qg3ZXxo4K%2BBN8YC4i34dWXUAYNUzGIFC88TNeBw"}]}
nel: {"report_to":"cf-nel","success_fraction":0.0,"max_age":604800}
cf-ray: a2d21cd32aeb1778-BEG


### https://cms.ald1n.com/login ###
HTTP/2 200 
date: Tue, 18 Aug 2026 15:57:09 GMT
content-type: text/html; charset=utf-8
x-powered-by: PHP/8.4.23
cache-control: no-store, private
x-content-type-options: nosniff
x-frame-options: SAMEORIGIN
referrer-policy: strict-origin-when-cross-origin
permissions-policy: camera=(), microphone=(), geolocation=(), payment=()
cross-origin-opener-policy: same-origin
content-security-policy: base-uri 'self'; frame-ancestors 'self'; form-action 'self'; object-src 'none'; frame-src 'self' https://challenges.cloudflare.com https://www.youtube-nocookie.com https://www.youtube.com
strict-transport-security: max-age=31536000; includeSubDomains
x-request-id: bfa19d9a-2a9d-4dde-98d6-9d878f4c0e5c
set-cookie: XSRF-TOKEN=eyJpdiI6ImMxOFVteFhVa2VreVZGNE5na1RmQlE9PSIsInZhbHVlIjoielIrUERMTmNqQVNlRFlscmNZTW83Q2pqdUdCbmZWNVJ3WDJYSldYeVM4WWRXcm9saGVDWUNVTnlqRUdqLzExSmt6UndnMmFvcU5GY1N1c2VuT2tuUFk3T0F2elFvS0FQNTFxcTZhUVUvRlExNzRxcy9QL3RGblZxeGk3QW1UMWkiLCJtYWMiOiI0Y2Q2MjVlMDdiYTI2ZGFkY2JiZjRlODJkYWE3NmIwNmMwZjFjZTI3MWRmYWU0NzlmYzc2ZTkxMjUzNDcwY2JlIiwidGFnIjoiIn0%3D; expires=Tue, 18 Aug 2026 17:57:09 GMT; Max-Age=7200; path=/; domain=cms.ald1n.com; secure; samesite=lax
set-cookie: ald1n-cms-session=eyJpdiI6IndHdThhY3h4eU51bjhubVl6Y09aNHc9PSIsInZhbHVlIjoiOUl4eWV5bExlQzg0QTRRVU5TTGpJVHNJUmZXWGlPWFo3VmFTNGxiL1V3TWdwZEV1U2cvdFFjT0J1QXA4NGJvVWVlcTNncVFSMGlxdk96UnEza1FWK0c0MVE0UnFXcEJad1R0aHlXeXFmOFR1L1VldHZCL29scWdaME5XemJBRlIiLCJtYWMiOiI0NTAzZDc2OTA0MjQxNTc4YWZmMDAyNGIyOTBhZjg1MzMxNGRmYzNlZTFiNzk4MzU4MDJkMWY0MjMyODQ3OTE2IiwidGFnIjoiIn0%3D; expires=Tue, 18 Aug 2026 17:57:09 GMT; Max-Age=7200; path=/; domain=cms.ald1n.com; secure; httponly; samesite=lax
server: cloudflare
x-robots-tag: noindex, nofollow, noarchive
alt-svc: h3=":443"; ma=86400
x-turbo-charged-by: LiteSpeed
cf-cache-status: DYNAMIC
speculation-rules: "/cdn-cgi/speculation"
report-to: {"group":"cf-nel","max_age":604800,"endpoints":[{"url":"https://a.nel.cloudflare.com/report/v4?s=n5oBL8s%2BlMj7ZtJt%2BAncV6mBQfHr7OSUjNGwYDs5jYcc8z5xGyC%2BciFYHoMS9ZeKCyS0db2OpBjCjDn5lXdJXYWbVBNvotAF1FerskU6alpO2Jzd2exTbSsIQvfoPsj%2F"}]}
nel: {"report_to":"cf-nel","success_fraction":0.0,"max_age":604800}
cf-ray: a2d21cd4cbc7aeaa-BEG


### https://cms.ald1n.com/dashboard ###
HTTP/2 404 
date: Tue, 18 Aug 2026 15:57:09 GMT
content-type: text/html; charset=utf-8
x-powered-by: PHP/8.4.23
cache-control: no-cache, private
x-content-type-options: nosniff
x-frame-options: SAMEORIGIN
referrer-policy: strict-origin-when-cross-origin
permissions-policy: camera=(), microphone=(), geolocation=(), payment=()
cross-origin-opener-policy: same-origin
content-security-policy: base-uri 'self'; frame-ancestors 'self'; form-action 'self'; object-src 'none'; frame-src 'self' https://challenges.cloudflare.com https://www.youtube-nocookie.com https://www.youtube.com
strict-transport-security: max-age=31536000; includeSubDomains
x-request-id: 6e65249a-df75-4af2-8b87-6b69456ebaf4
server: cloudflare
x-robots-tag: noindex, nofollow, noarchive
alt-svc: h3=":443"; ma=86400
x-turbo-charged-by: LiteSpeed
cf-cache-status: DYNAMIC
speculation-rules: "/cdn-cgi/speculation"
report-to: {"group":"cf-nel","max_age":604800,"endpoints":[{"url":"https://a.nel.cloudflare.com/report/v4?s=9xfHFuvbo2uuJJ4UBSoejG%2BV0PSC7Pu1zyC%2BRUkVBKlZtIT46XuWIQDVNOwMP9Kc62zlY2EgI2JqgVBQM44uOdzix279%2FNmzHJkFLmj8WnZZ9XwJFy0ABMXNv1%2B8EP9I"}]}
nel: {"report_to":"cf-nel","success_fraction":0.0,"max_age":604800}
cf-ray: a2d21cd67c6ee28f-BEG


============================================================
4. LOG SOURCES + EXISTING LAST-450-LINE EVIDENCE
============================================================
READABLE_LOG_SOURCE_COUNT=170
212861448	0	1727790613	/home/icaffeco/.softaculous/logs/error_log.log
212881451	0	1787004022	/home/icaffeco/adalyatobacco.rs/error_log
212861852	23083	1786401494	/home/icaffeco/adalyatobacco.rs/wp-admin/error_log
212861842	215	1751360526	/home/icaffeco/adalyatobacco.rs/wp-admin/network/error_log
212860969	29100	1784821812	/home/icaffeco/adalyatobacco.rs/wp-content/languages/error_log
212962245	4368	1784976838	/home/icaffeco/adalyatobacco.rs/wp-includes/IXR/error_log
212962885	9773	1785818443	/home/icaffeco/adalyatobacco.rs/wp-includes/block-bindings/error_log
212955380	20446	1785594655	/home/icaffeco/adalyatobacco.rs/wp-includes/block-patterns/error_log
212946719	45522	1785815773	/home/icaffeco/adalyatobacco.rs/wp-includes/block-supports/error_log
213285206	3083	1784145261	/home/icaffeco/adalyatobacco.rs/wp-includes/build/error_log
212962726	79505	1785019041	/home/icaffeco/adalyatobacco.rs/wp-includes/customize/error_log
212861295	238368	1784822164	/home/icaffeco/adalyatobacco.rs/wp-includes/error_log
212962890	2358	1784281120	/home/icaffeco/adalyatobacco.rs/wp-includes/interactivity-api/error_log
212963408	2240	1784251195	/home/icaffeco/adalyatobacco.rs/wp-includes/rest-api/error_log
212959847	18505	1784916631	/home/icaffeco/adalyatobacco.rs/wp-includes/theme-compat/error_log
212946589	72401	1785807293	/home/icaffeco/adalyatobacco.rs/wp-includes/widgets/error_log
213830498	7101	1786962335	/home/icaffeco/ald1n-project/apps/cms/current/error_log
213779740	14	1786011199	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/.gitignore
213784991	4305	1786481739	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-11.log
213783407	38429	1786556341	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-12.log
213782322	603	1786573276	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-13.log
213783418	3464	1786779269	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-15.log
213783409	12416	1786988878	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-17.log
213782290	825961	1787068627	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-18.log
213782279	6125560	1787068623	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/scheduler.log
212861469	0	1785880836	/home/icaffeco/arsa-arch.de/error_log
212870589	0	1786399219	/home/icaffeco/arsa-arch.rs/error_log
212892609	219	1787066764	/home/icaffeco/error_log
212871381	1108	1787063914	/home/icaffeco/essenza-online.com/error_log
212902955	12433	1779172370	/home/icaffeco/essenza-online.com/wp-admin/error_log
212967187	318	1779952244	/home/icaffeco/essenza-online.com/wp-includes/PHPMailer/error_log
212869906	1783	1779952199	/home/icaffeco/essenza-online.com/wp-includes/error_log
213004822	313	1779952362	/home/icaffeco/essenza-online.com/wp-includes/widgets/error_log
212881139	0	1785880837	/home/icaffeco/importane.rs/error_log
213125693	2538281	1774943633	/home/icaffeco/importane.rs/wp-admin/error_log
213144790	17597	1784958279	/home/icaffeco/importane.rs/wp-admin/includes/error_log
213190636	8874	1784958596	/home/icaffeco/importane.rs/wp-includes/PHPMailer/error_log
213200573	508	1731022989	/home/icaffeco/importane.rs/wp-includes/blocks/error_log
213143100	164882	1784958540	/home/icaffeco/importane.rs/wp-includes/error_log
213202434	43147	1784958625	/home/icaffeco/importane.rs/wp-includes/widgets/error_log
212881171	295691	1779190775	/home/icaffeco/logs/.php.error.log
212878540	28592	1787011720	/home/icaffeco/logs/adalyatobacco.ald1n.com-Aug-2026.gz
212878541	2230325	1787011720	/home/icaffeco/logs/adalyatobacco.ald1n.com-ssl_log-Aug-2026.gz
212881176	150642	1735257744	/home/icaffeco/logs/adalyatobacco.icaffe24.com-Dec-2024.gz
212881177	2300015	1735257744	/home/icaffeco/logs/adalyatobacco.icaffe24.com-ssl_log-Dec-2024.gz
212876421	47060	1787011720	/home/icaffeco/logs/ald1n.com-Aug-2026.gz
212876422	308012	1787011720	/home/icaffeco/logs/ald1n.com-ssl_log-Aug-2026.gz
212881182	75074	1735257744	/home/icaffeco/logs/ald1n.com.icaffe24.com-Dec-2024.gz
212881183	133385	1735257744	/home/icaffeco/logs/ald1n.com.icaffe24.com-ssl_log-Dec-2024.gz
212878543	41082	1787011720	/home/icaffeco/logs/arsa-arch.de.ald1n.com-Aug-2026.gz
212878544	46614	1787011720	/home/icaffeco/logs/arsa-arch.de.ald1n.com-ssl_log-Aug-2026.gz
212881188	52741	1735257744	/home/icaffeco/logs/arsa-arch.de.icaffe24.com-Dec-2024.gz
212881189	47198	1735257744	/home/icaffeco/logs/arsa-arch.de.icaffe24.com-ssl_log-Dec-2024.gz
212878549	40991	1787011720	/home/icaffeco/logs/arsa-arch.rs.ald1n.com-Aug-2026.gz
212891552	115818	1787011720	/home/icaffeco/logs/arsa-arch.rs.ald1n.com-ssl_log-Aug-2026.gz
212870491	19820	1742257131	/home/icaffeco/logs/arsa.ald1n.com-Mar-2025.gz
212870492	14692	1742430045	/home/icaffeco/logs/arsa.ald1n.com-ssl_log-Mar-2025.gz
212881194	29612	1735257744	/home/icaffeco/logs/arsa.icaffe24.com-Dec-2024.gz
212881195	50723	1735257744	/home/icaffeco/logs/arsa.icaffe24.com-ssl_log-Dec-2024.gz
212891553	5863	1787011720	/home/icaffeco/logs/cms.ald1n.com-Aug-2026.gz
212891554	88280	1787011720	/home/icaffeco/logs/cms.ald1n.com-ssl_log-Aug-2026.gz
212891555	143141	1787011720	/home/icaffeco/logs/essenza-online.com.ald1n.com-Aug-2026.gz
212891556	71275	1787011720	/home/icaffeco/logs/essenza-online.com.ald1n.com-ssl_log-Aug-2026.gz
212891567	1392536	1787011720	/home/icaffeco/logs/ftp.ald1n.com-ftp_log-Aug-2026.gz
212873865	521	1767118486	/home/icaffeco/logs/ftp.ald1n.com-ftp_log-Dec-2025.gz
212881198	198	1733573918	/home/icaffeco/logs/ftp.icaffe24.com-ftp_log-Dec-2024.gz
212881199	631	1732536787	/home/icaffeco/logs/ftp.icaffe24.com-ftp_log-Nov-2024.gz
212891557	38565	1787011720	/home/icaffeco/logs/hastall.rs.ald1n.com-Aug-2026.gz
212891558	10956	1787011720	/home/icaffeco/logs/hastall.rs.ald1n.com-ssl_log-Aug-2026.gz
212881200	41879	1735257744	/home/icaffeco/logs/icaffe24.com-Dec-2024.gz
212881201	863317	1735257744	/home/icaffeco/logs/icaffe24.com-ssl_log-Dec-2024.gz
212876605	58386	1771448785	/home/icaffeco/logs/icaffe24.com.ald1n.com-Feb-2026.gz
212876606	229371	1771448785	/home/icaffeco/logs/icaffe24.com.ald1n.com-ssl_log-Feb-2026.gz
212891559	47025	1787011720	/home/icaffeco/logs/importane.ald1n.com-Aug-2026.gz
212891560	257647	1787011720	/home/icaffeco/logs/importane.ald1n.com-ssl_log-Aug-2026.gz
212881206	31479	1735257744	/home/icaffeco/logs/importane.icaffe24.com-Dec-2024.gz
212881207	260919	1735257744	/home/icaffeco/logs/importane.icaffe24.com-ssl_log-Dec-2024.gz
212891561	13494	1787011720	/home/icaffeco/logs/mandza.ald1n.com-Aug-2026.gz
212891562	424748	1787011720	/home/icaffeco/logs/mandza.ald1n.com-ssl_log-Aug-2026.gz
212881212	20198	1735257744	/home/icaffeco/logs/mandza.icaffe24.com-Dec-2024.gz
212881213	358587	1735257744	/home/icaffeco/logs/mandza.icaffe24.com-ssl_log-Dec-2024.gz
213202435	31956	1769353395	/home/icaffeco/logs/roundcube/carddav.log
213191977	16192	1761116471	/home/icaffeco/logs/roundcube/carddav_http.log
213202436	8324	1769377790	/home/icaffeco/logs/roundcube/errors.log
213202437	3157	1779189636	/home/icaffeco/logs/roundcube/sendmail.log
212891563	69230	1787011720	/home/icaffeco/logs/smart-elektrotechnik.ald1n.com-Aug-2026.gz
212891564	424305	1787011720	/home/icaffeco/logs/smart-elektrotechnik.ald1n.com-ssl_log-Aug-2026.gz
212881218	81474	1735257744	/home/icaffeco/logs/smart-elektrotechnik.icaffe24.com-Dec-2024.gz
212881219	382321	1735257744	/home/icaffeco/logs/smart-elektrotechnik.icaffe24.com-ssl_log-Dec-2024.gz
212871398	11169	1750206341	/home/icaffeco/logs/sprintertravel.rs.ald1n.com-Jun-2025.gz
212871399	14404	1750033247	/home/icaffeco/logs/sprintertravel.rs.ald1n.com-ssl_log-Jun-2025.gz
212881224	69820	1735257744	/home/icaffeco/logs/sprintertravel.rs.icaffe24.com-Dec-2024.gz
212881225	65119	1735257744	/home/icaffeco/logs/sprintertravel.rs.icaffe24.com-ssl_log-Dec-2024.gz
212870551	395	1771448785	/home/icaffeco/logs/turkovic.net.ald1n.com-Feb-2026.gz
212876614	639	1770582956	/home/icaffeco/logs/turkovic.net.ald1n.com-ssl_log-Feb-2026.gz
212870693	63098	1769889631	/home/icaffeco/logs/turkovic.net.ald1n.com-ssl_log-Jan-2026.gz
212881230	105736	1735257744	/home/icaffeco/logs/turkovic.net.icaffe24.com-Dec-2024.gz
212881231	893099	1735257744	/home/icaffeco/logs/turkovic.net.icaffe24.com-ssl_log-Dec-2024.gz
212891565	95618	1787011720	/home/icaffeco/logs/zenskefarmerice.com.ald1n.com-Aug-2026.gz
212891566	835550	1787011720	/home/icaffeco/logs/zenskefarmerice.com.ald1n.com-ssl_log-Aug-2026.gz
212881236	49530	1735257744	/home/icaffeco/logs/zenskefarmerice.com.icaffe24.com-Dec-2024.gz
212881237	2807470	1735257744	/home/icaffeco/logs/zenskefarmerice.com.icaffe24.com-ssl_log-Dec-2024.gz
212881407	0	1786744821	/home/icaffeco/mandza.co.rs/error_log
213268646	1001	1746536666	/home/icaffeco/mandza.co.rs/wp-admin/error_log
213259293	33947	1787042339	/home/icaffeco/mandza.co.rs/wp-admin/includes/error_log
213269288	25705	1787032348	/home/icaffeco/mandza.co.rs/wp-content/languages/error_log
213320703	2432	1787032237	/home/icaffeco/mandza.co.rs/wp-includes/IXR/error_log
213320392	6426	1787032258	/home/icaffeco/mandza.co.rs/wp-includes/PHPMailer/error_log
213320680	2460	1772663491	/home/icaffeco/mandza.co.rs/wp-includes/block-bindings/error_log
213320387	4446	1772663450	/home/icaffeco/mandza.co.rs/wp-includes/block-patterns/error_log
213320681	10916	1772663494	/home/icaffeco/mandza.co.rs/wp-includes/block-supports/error_log
213335792	762	1729383006	/home/icaffeco/mandza.co.rs/wp-includes/blocks/error_log
213320705	24158	1776689249	/home/icaffeco/mandza.co.rs/wp-includes/customize/error_log
213276435	165182	1787032118	/home/icaffeco/mandza.co.rs/wp-includes/error_log
213320684	1298	1772663502	/home/icaffeco/mandza.co.rs/wp-includes/html-api/error_log
213320683	770	1772663501	/home/icaffeco/mandza.co.rs/wp-includes/interactivity-api/error_log
213320682	1280	1772663496	/home/icaffeco/mandza.co.rs/wp-includes/l10n/error_log
213320671	624	1772663479	/home/icaffeco/mandza.co.rs/wp-includes/rest-api/error_log
213320715	5402	1772663560	/home/icaffeco/mandza.co.rs/wp-includes/theme-compat/error_log
213337655	53311	1787032334	/home/icaffeco/mandza.co.rs/wp-includes/widgets/error_log
212871413	54824	1787068489	/home/icaffeco/public_html/error_log
213340382	291582	1786555927	/home/icaffeco/public_html/wp-admin/error_log
213516293	34416	1785959746	/home/icaffeco/public_html/wp-admin/includes/error_log
213519600	25246	1785960089	/home/icaffeco/public_html/wp-content/languages/error_log
213596583	1812	1785960021	/home/icaffeco/public_html/wp-includes/IXR/error_log
213582055	5472	1785960032	/home/icaffeco/public_html/wp-includes/PHPMailer/error_log
213596582	1222	1778960858	/home/icaffeco/public_html/wp-includes/block-bindings/error_log
213596603	2209	1778960922	/home/icaffeco/public_html/wp-includes/block-patterns/error_log
213596579	5422	1778960835	/home/icaffeco/public_html/wp-includes/block-supports/error_log
213596605	11831	1778960935	/home/icaffeco/public_html/wp-includes/customize/error_log
213340617	133469	1785959960	/home/icaffeco/public_html/wp-includes/error_log
213596602	645	1778960919	/home/icaffeco/public_html/wp-includes/html-api/error_log
213596580	383	1778960836	/home/icaffeco/public_html/wp-includes/interactivity-api/error_log
213596581	636	1778960851	/home/icaffeco/public_html/wp-includes/l10n/error_log
213596584	310	1778960866	/home/icaffeco/public_html/wp-includes/rest-api/error_log
213596623	2683	1778960964	/home/icaffeco/public_html/wp-includes/theme-compat/error_log
213586974	43185	1785960075	/home/icaffeco/public_html/wp-includes/widgets/error_log
212881455	4578	1787060874	/home/icaffeco/smart-elektrotechnik.net/error_log
213587029	36729	1785393105	/home/icaffeco/smart-elektrotechnik.net/wp-admin/error_log
213586739	18664	1785034616	/home/icaffeco/smart-elektrotechnik.net/wp-admin/includes/error_log
213659567	1968	1785034985	/home/icaffeco/smart-elektrotechnik.net/wp-includes/IXR/error_log
213647791	5940	1786911512	/home/icaffeco/smart-elektrotechnik.net/wp-includes/PHPMailer/error_log
213659037	2652	1777055170	/home/icaffeco/smart-elektrotechnik.net/wp-includes/block-bindings/error_log
213659519	4782	1777055067	/home/icaffeco/smart-elektrotechnik.net/wp-includes/block-patterns/error_log
213659566	11780	1777055071	/home/icaffeco/smart-elektrotechnik.net/wp-includes/block-supports/error_log
213652527	556	1729510143	/home/icaffeco/smart-elektrotechnik.net/wp-includes/blocks/error_log
213659518	25430	1777055105	/home/icaffeco/smart-elektrotechnik.net/wp-includes/customize/error_log
213601843	134385	1785034917	/home/icaffeco/smart-elektrotechnik.net/wp-includes/error_log
213659038	1394	1777055184	/home/icaffeco/smart-elektrotechnik.net/wp-includes/html-api/error_log
213659034	818	1777055066	/home/icaffeco/smart-elektrotechnik.net/wp-includes/interactivity-api/error_log
213659564	1376	1777055134	/home/icaffeco/smart-elektrotechnik.net/wp-includes/l10n/error_log
213659520	672	1777055171	/home/icaffeco/smart-elektrotechnik.net/wp-includes/rest-api/error_log
213659517	5834	1777055114	/home/icaffeco/smart-elektrotechnik.net/wp-includes/theme-compat/error_log
213654427	45041	1786911776	/home/icaffeco/smart-elektrotechnik.net/wp-includes/widgets/error_log
212881491	0	1785880837	/home/icaffeco/sprintertravel.rs/error_log
213664347	26752	1748782750	/home/icaffeco/sprintertravel.rs/wp-includes/error_log
213664352	6842	1748782758	/home/icaffeco/sprintertravel.rs/wp-includes/widgets/error_log
212881517	0	1785880837	/home/icaffeco/turkovic.netovi/error_log
213663677	2550	1749539901	/home/icaffeco/turkovic.netovi/wp-admin/error_log
213732532	254	1729346063	/home/icaffeco/turkovic.netovi/wp-includes/blocks/error_log
213667333	54279	1748169350	/home/icaffeco/turkovic.netovi/wp-includes/error_log
213732873	14123	1748169437	/home/icaffeco/turkovic.netovi/wp-includes/widgets/error_log
212861240	2484	1787063345	/home/icaffeco/zenskefarmerice.com/error_log
213400461	526264	1785393309	/home/icaffeco/zenskefarmerice.com/wp-admin/error_log
213398290	8589	1782973570	/home/icaffeco/zenskefarmerice.com/wp-content/languages/error_log
213450761	636	1781918289	/home/icaffeco/zenskefarmerice.com/wp-includes/IXR/error_log
213450758	6080	1782973866	/home/icaffeco/zenskefarmerice.com/wp-includes/PHPMailer/error_log
213452006	268	1731164900	/home/icaffeco/zenskefarmerice.com/wp-includes/blocks/error_log
213400351	55817	1782973842	/home/icaffeco/zenskefarmerice.com/wp-includes/error_log
213451252	15248	1782973933	/home/icaffeco/zenskefarmerice.com/wp-includes/widgets/error_log

--- high-signal recent errors before fresh reproduction ---
grep: /home/icaffeco/ald1n-project/incoming/.cms-web-500-live-http-fpm-batch3.fH7Qsn/recent-before.sanitized.txt: binary file matches
5279:[02-Jan-2026 00:38:52 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5283:[30-Jan-2026 06:48:34 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5287:[31-Jan-2026 00:07:28 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5291:[31-Jan-2026 06:13:15 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5295:[31-Jan-2026 07:43:09 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5299:[31-Jan-2026 15:59:15 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5303:[01-Feb-2026 09:35:40 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5307:[01-Feb-2026 11:33:41 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5311:[01-Feb-2026 14:10:58 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5315:[02-Feb-2026 11:02:12 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5319:[02-Feb-2026 17:51:11 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5323:[02-Feb-2026 18:31:07 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5327:[02-Feb-2026 23:31:58 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5331:[03-Feb-2026 05:22:54 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5335:[09-Feb-2026 11:40:35 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5339:[09-Feb-2026 12:34:01 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5343:[20-Feb-2026 12:59:16 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5347:[23-Feb-2026 00:43:54 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5351:[02-Mar-2026 06:06:38 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5355:[05-Mar-2026 13:02:00 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5359:[16-Apr-2026 03:43:46 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5363:[13-May-2026 03:17:24 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5367:[13-May-2026 10:39:10 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5371:[13-May-2026 14:45:21 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5375:[22-May-2026 17:43:10 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5379:[23-May-2026 10:38:30 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5383:[03-Jul-2026 20:16:44 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5387:[15-Jul-2026 19:23:20 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5391:[25-Jul-2026 07:49:56 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "PHPMailer\PHPMailer\OAuthTokenProvider" not found in /home/icaffeco/importane.rs/wp-includes/PHPMailer/OAuth.php:36
5398:[06-Nov-2024 18:29:21 UTC] PHP Fatal error:  Uncaught Error: Undefined constant "ABSPATH" in /home/icaffeco/importane.rs/wp-includes/blocks/index.php:8
5402:[07-Nov-2024 23:43:09 UTC] PHP Fatal error:  Uncaught Error: Undefined constant "ABSPATH" in /home/icaffeco/importane.rs/wp-includes/blocks/index.php:8
5410:[23-Feb-2026 00:43:15 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-scripts.php:18
5414:[23-Feb-2026 00:43:18 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-styles.php:18
5418:[23-Feb-2026 00:43:43 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function get_the_block_template_html() in /home/icaffeco/importane.rs/wp-includes/template-canvas.php:12
5422:[23-Feb-2026 00:43:46 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _wp_can_use_pcre_u() in /home/icaffeco/importane.rs/wp-includes/utf8.php:137
5426:[02-Mar-2026 06:05:59 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "PHPMailer\PHPMailer\PHPMailer" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-phpmailer.php:16
5430:[02-Mar-2026 06:06:02 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-scripts.php:18
5434:[02-Mar-2026 06:06:04 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-styles.php:18
5438:[02-Mar-2026 06:06:25 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function get_the_block_template_html() in /home/icaffeco/importane.rs/wp-includes/template-canvas.php:12
5442:[02-Mar-2026 06:06:29 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _wp_can_use_pcre_u() in /home/icaffeco/importane.rs/wp-includes/utf8.php:137
5446:[05-Mar-2026 13:00:27 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "PHPMailer\PHPMailer\PHPMailer" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-phpmailer.php:16
5450:[05-Mar-2026 13:00:37 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-scripts.php:18
5454:[05-Mar-2026 13:00:42 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-styles.php:18
5458:[05-Mar-2026 13:01:33 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function get_the_block_template_html() in /home/icaffeco/importane.rs/wp-includes/template-canvas.php:12
5462:[05-Mar-2026 13:01:40 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _wp_can_use_pcre_u() in /home/icaffeco/importane.rs/wp-includes/utf8.php:137
5466:[16-Apr-2026 03:40:18 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "PHPMailer\PHPMailer\PHPMailer" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-phpmailer.php:16
5470:[16-Apr-2026 03:40:35 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-scripts.php:18
5474:[16-Apr-2026 03:40:47 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-styles.php:18
5478:[16-Apr-2026 03:42:32 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function get_the_block_template_html() in /home/icaffeco/importane.rs/wp-includes/template-canvas.php:12
5482:[16-Apr-2026 03:42:50 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _wp_can_use_pcre_u() in /home/icaffeco/importane.rs/wp-includes/utf8.php:137
5486:[13-May-2026 03:13:01 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "PHPMailer\PHPMailer\PHPMailer" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-phpmailer.php:16
5490:[13-May-2026 03:13:23 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-scripts.php:18
5494:[13-May-2026 03:13:37 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-styles.php:18
5498:[13-May-2026 03:15:54 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function get_the_block_template_html() in /home/icaffeco/importane.rs/wp-includes/template-canvas.php:12
5502:[13-May-2026 03:16:16 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _wp_can_use_pcre_u() in /home/icaffeco/importane.rs/wp-includes/utf8.php:137
5506:[13-May-2026 10:36:46 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "PHPMailer\PHPMailer\PHPMailer" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-phpmailer.php:16
5510:[13-May-2026 10:36:57 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-scripts.php:18
5514:[13-May-2026 10:37:04 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-styles.php:18
5518:[13-May-2026 10:38:06 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function get_the_block_template_html() in /home/icaffeco/importane.rs/wp-includes/template-canvas.php:12
5522:[13-May-2026 10:38:16 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _wp_can_use_pcre_u() in /home/icaffeco/importane.rs/wp-includes/utf8.php:137
5526:[13-May-2026 14:40:38 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "PHPMailer\PHPMailer\PHPMailer" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-phpmailer.php:16
5530:[13-May-2026 14:41:04 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-scripts.php:18
5534:[13-May-2026 14:41:20 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-styles.php:18
5538:[13-May-2026 14:43:48 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function get_the_block_template_html() in /home/icaffeco/importane.rs/wp-includes/template-canvas.php:12
5542:[13-May-2026 14:44:12 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _wp_can_use_pcre_u() in /home/icaffeco/importane.rs/wp-includes/utf8.php:137
5546:[22-May-2026 17:41:23 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "PHPMailer\PHPMailer\PHPMailer" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-phpmailer.php:16
5550:[22-May-2026 17:41:34 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-scripts.php:18
5554:[22-May-2026 17:41:41 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-styles.php:18
5558:[22-May-2026 17:42:11 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function add_filter() in /home/icaffeco/importane.rs/wp-includes/connectors.php:566
5562:[22-May-2026 17:42:38 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function get_the_block_template_html() in /home/icaffeco/importane.rs/wp-includes/template-canvas.php:12
5566:[22-May-2026 17:42:47 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _wp_can_use_pcre_u() in /home/icaffeco/importane.rs/wp-includes/utf8.php:137
5570:[23-May-2026 10:36:05 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "PHPMailer\PHPMailer\PHPMailer" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-phpmailer.php:16
5574:[23-May-2026 10:36:17 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-scripts.php:18
5578:[23-May-2026 10:36:24 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-styles.php:18
5582:[23-May-2026 10:37:07 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function add_filter() in /home/icaffeco/importane.rs/wp-includes/connectors.php:566
5586:[23-May-2026 10:37:43 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function get_the_block_template_html() in /home/icaffeco/importane.rs/wp-includes/template-canvas.php:12
5590:[23-May-2026 10:37:54 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _wp_can_use_pcre_u() in /home/icaffeco/importane.rs/wp-includes/utf8.php:137
5594:[03-Jul-2026 20:13:47 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "PHPMailer\PHPMailer\PHPMailer" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-phpmailer.php:16
5598:[03-Jul-2026 20:14:09 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-scripts.php:18
5602:[03-Jul-2026 20:14:21 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-styles.php:18
5606:[03-Jul-2026 20:15:11 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function add_filter() in /home/icaffeco/importane.rs/wp-includes/connectors.php:566
5610:[03-Jul-2026 20:15:58 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function get_the_block_template_html() in /home/icaffeco/importane.rs/wp-includes/template-canvas.php:12
5614:[03-Jul-2026 20:16:10 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _wp_can_use_pcre_u() in /home/icaffeco/importane.rs/wp-includes/utf8.php:137
5618:[15-Jul-2026 19:20:27 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "PHPMailer\PHPMailer\PHPMailer" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-phpmailer.php:16
5622:[15-Jul-2026 19:20:42 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-scripts.php:18
5626:[15-Jul-2026 19:20:52 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-styles.php:18
5630:[15-Jul-2026 19:21:44 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function add_filter() in /home/icaffeco/importane.rs/wp-includes/connectors.php:566
5634:[15-Jul-2026 19:22:28 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function get_the_block_template_html() in /home/icaffeco/importane.rs/wp-includes/template-canvas.php:12
5638:[15-Jul-2026 19:22:40 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _wp_can_use_pcre_u() in /home/icaffeco/importane.rs/wp-includes/utf8.php:137
5642:[25-Jul-2026 07:45:10 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function add_theme_support() in /home/icaffeco/importane.rs/wp-includes/block-patterns.php:9
5646:[25-Jul-2026 07:45:15 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Undefined constant "ABSPATH" in /home/icaffeco/importane.rs/wp-includes/cache.php:12
5650:[25-Jul-2026 07:45:20 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/class-feed.php:10
5654:[25-Jul-2026 07:45:22 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/class-http.php:11
5658:[25-Jul-2026 07:45:24 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/class-json.php:2
5662:[25-Jul-2026 07:45:24 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/class-oembed.php:12
5667:[25-Jul-2026 07:45:27 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Undefined constant "ABSPATH" in /home/icaffeco/importane.rs/wp-includes/class-simplepie.php:8
5671:[25-Jul-2026 07:45:28 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/class-smtp.php:6
5675:[25-Jul-2026 07:45:29 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/class-snoopy.php:6
5679:[25-Jul-2026 07:45:29 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "Walker" not found in /home/icaffeco/importane.rs/wp-includes/class-walker-category-dropdown.php:17
5683:[25-Jul-2026 07:45:30 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "Walker" not found in /home/icaffeco/importane.rs/wp-includes/class-walker-category.php:17
5687:[25-Jul-2026 07:45:31 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "Walker" not found in /home/icaffeco/importane.rs/wp-includes/class-walker-comment.php:17
5691:[25-Jul-2026 07:45:31 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "Walker" not found in /home/icaffeco/importane.rs/wp-includes/class-walker-nav-menu.php:17
5695:[25-Jul-2026 07:45:32 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "Walker" not found in /home/icaffeco/importane.rs/wp-includes/class-walker-page-dropdown.php:17
5699:[25-Jul-2026 07:45:33 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "Walker" not found in /home/icaffeco/importane.rs/wp-includes/class-walker-page.php:17
5703:[25-Jul-2026 07:46:10 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Undefined constant "ABSPATH" in /home/icaffeco/importane.rs/wp-includes/class-wp-customize-section.php:408
5707:[25-Jul-2026 07:46:19 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Interface "SimplePie\Cache\Base" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-feed-cache-transient.php:18
5711:[25-Jul-2026 07:46:20 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/class-wp-feed-cache.php:11
5715:[25-Jul-2026 07:46:24 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "IXR_Client" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-http-ixr-client.php:9
5719:[25-Jul-2026 07:46:25 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WpOrg\Requests\Hooks" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-http-requests-hooks.php:18
5723:[25-Jul-2026 07:46:26 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_HTTP_Response" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-http-requests-response.php:17
5727:[25-Jul-2026 07:46:31 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Image_Editor" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-image-editor-gd.php:16
5731:[25-Jul-2026 07:46:32 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Image_Editor" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-image-editor-imagick.php:16
5735:[25-Jul-2026 07:46:43 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "PHPMailer\PHPMailer\PHPMailer" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-phpmailer.php:16
5739:[25-Jul-2026 07:46:57 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-scripts.php:18
5743:[25-Jul-2026 07:46:58 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "SimplePie\File" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-simplepie-file.php:19
5747:[25-Jul-2026 07:47:04 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Dependencies" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-styles.php:18
5751:[25-Jul-2026 07:47:16 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "Text_Diff_Renderer_inline" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-text-diff-renderer-inline.php:17
5755:[25-Jul-2026 07:47:25 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Session_Tokens" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-user-meta-session-tokens.php:17
5758:  thrown in /home/icaffeco/importane.rs/wp-includes/class-wp-user-meta-session-tokens.php on line 17
5759:[25-Jul-2026 07:47:33 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "IXR_Server" not found in /home/icaffeco/importane.rs/wp-includes/class-wp-xmlrpc-server.php:24
5763:[25-Jul-2026 07:47:39 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/class.wp-dependencies.php:11
5767:[25-Jul-2026 07:47:41 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/class.wp-scripts.php:11
5771:[25-Jul-2026 07:47:41 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/class.wp-styles.php:11
5775:[25-Jul-2026 07:47:44 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Undefined constant "ABSPATH" in /home/icaffeco/importane.rs/wp-includes/compat.php:301
5779:[25-Jul-2026 07:47:47 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function add_filter() in /home/icaffeco/importane.rs/wp-includes/connectors.php:566
5783:[25-Jul-2026 07:47:50 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/date.php:11
5787:[25-Jul-2026 07:47:54 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/embed-template.php:11
5791:[25-Jul-2026 07:47:57 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function feed_content_type() in /home/icaffeco/importane.rs/wp-includes/feed-atom-comments.php:8
5795:[25-Jul-2026 07:47:58 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function feed_content_type() in /home/icaffeco/importane.rs/wp-includes/feed-rdf.php:8
5799:[25-Jul-2026 07:47:59 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function feed_content_type() in /home/icaffeco/importane.rs/wp-includes/feed-rss.php:8
5803:[25-Jul-2026 07:47:59 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function feed_content_type() in /home/icaffeco/importane.rs/wp-includes/feed-rss2-comments.php:8
5807:[25-Jul-2026 07:48:00 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function feed_content_type() in /home/icaffeco/importane.rs/wp-includes/feed-rss2.php:8
5811:[25-Jul-2026 07:48:18 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/locale.php:11
5815:[25-Jul-2026 07:48:20 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Undefined constant "ABSPATH" in /home/icaffeco/importane.rs/wp-includes/meta.php:13
5819:[25-Jul-2026 07:48:22 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function add_action() in /home/icaffeco/importane.rs/wp-includes/ms-default-filters.php:16
5823:[25-Jul-2026 07:48:40 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/registration-functions.php:9
5827:[25-Jul-2026 07:48:41 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/registration.php:9
5831:[25-Jul-2026 07:48:45 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/rss.php:19
5835:[25-Jul-2026 07:48:46 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Undefined constant "ABSPATH" in /home/icaffeco/importane.rs/wp-includes/script-loader.php:20
5839:[25-Jul-2026 07:48:47 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/session.php:9
5842:  thrown in /home/icaffeco/importane.rs/wp-includes/session.php on line 9
5843:[25-Jul-2026 07:48:51 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _deprecated_file() in /home/icaffeco/importane.rs/wp-includes/spl-autoload-compat.php:14
5847:[25-Jul-2026 07:48:54 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function get_the_block_template_html() in /home/icaffeco/importane.rs/wp-includes/template-canvas.php:12
5851:[25-Jul-2026 07:48:55 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function wp_using_themes() in /home/icaffeco/importane.rs/wp-includes/template-loader.php:7
5855:[25-Jul-2026 07:49:00 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Call to undefined function _wp_can_use_pcre_u() in /home/icaffeco/importane.rs/wp-includes/utf8.php:137
5864:[04-Mar-2025 01:42:13 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5868:[04-Mar-2025 02:11:49 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5872:[05-Mar-2025 01:39:16 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5876:[05-Mar-2025 04:09:09 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5880:[05-Mar-2025 10:23:35 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5884:[05-Mar-2025 11:54:27 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5888:[06-Mar-2025 04:47:41 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5892:[06-Mar-2025 04:54:18 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5896:[06-Mar-2025 21:16:41 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5900:[06-Mar-2025 22:27:41 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5904:[07-Mar-2025 00:24:59 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5908:[07-Mar-2025 05:58:11 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5912:[07-Mar-2025 07:05:38 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5916:[07-Mar-2025 15:33:13 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5920:[07-Mar-2025 20:33:29 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5924:[08-Mar-2025 10:10:01 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5928:[08-Mar-2025 15:52:52 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5932:[09-Mar-2025 10:15:35 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5936:[09-Mar-2025 16:06:30 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5940:[10-Mar-2025 00:18:12 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5944:[10-Mar-2025 11:54:34 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5948:[10-Mar-2025 13:28:03 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5952:[11-Mar-2025 05:45:57 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5956:[11-Mar-2025 14:30:42 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5960:[11-Mar-2025 19:12:03 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5964:[12-Mar-2025 00:20:21 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5968:[12-Mar-2025 10:34:49 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5972:[12-Mar-2025 14:51:45 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5976:[12-Mar-2025 17:07:58 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5980:[12-Mar-2025 23:21:18 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5984:[13-Mar-2025 11:08:35 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5988:[14-Mar-2025 01:56:06 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5992:[15-Mar-2025 04:04:23 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
5996:[15-Mar-2025 08:49:40 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6000:[15-Mar-2025 16:15:39 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6004:[15-Mar-2025 20:02:09 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6008:[15-Mar-2025 20:24:24 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6012:[16-Mar-2025 05:08:27 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6016:[16-Mar-2025 08:32:22 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6020:[16-Mar-2025 13:03:19 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6024:[16-Mar-2025 13:15:14 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6028:[17-Mar-2025 04:46:09 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6032:[17-Mar-2025 09:13:26 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6036:[17-Mar-2025 11:13:20 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6040:[17-Mar-2025 16:23:56 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6044:[18-Mar-2025 04:37:48 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6048:[18-Mar-2025 09:24:07 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6052:[18-Mar-2025 23:32:50 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6056:[19-Mar-2025 10:56:33 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6060:[10-Jun-2025 20:19:21 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6064:[11-Jun-2025 18:47:07 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6068:[17-Jun-2025 13:49:36 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6072:[25-Jun-2025 20:32:45 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6076:[03-Aug-2025 15:09:01 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6080:[14-Aug-2025 16:48:11 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6084:[01-Sep-2025 12:38:47 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6088:[17-Sep-2025 14:55:16 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6092:[17-Sep-2025 22:43:06 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6096:[24-Sep-2025 02:05:05 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6100:[17-Oct-2025 08:43:18 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6104:[05-Nov-2025 19:36:41 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6108:[07-Nov-2025 05:04:42 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6112:[17-Nov-2025 04:02:51 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6116:[23-Nov-2025 14:20:21 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6120:[02-Jan-2026 00:44:14 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6124:[30-Jan-2026 06:51:43 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6128:[31-Jan-2026 00:22:48 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6132:[31-Jan-2026 06:14:05 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6136:[31-Jan-2026 07:44:24 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6140:[31-Jan-2026 16:01:52 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6144:[01-Feb-2026 09:38:27 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6148:[01-Feb-2026 11:34:48 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6152:[01-Feb-2026 14:13:18 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6156:[02-Feb-2026 11:03:15 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6160:[02-Feb-2026 17:52:29 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6164:[02-Feb-2026 18:34:20 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6168:[02-Feb-2026 23:34:45 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6172:[03-Feb-2026 05:25:43 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6176:[09-Feb-2026 11:42:05 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6180:[09-Feb-2026 12:35:22 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6184:[20-Feb-2026 13:01:05 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6188:[23-Feb-2026 00:44:23 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6192:[02-Mar-2026 06:07:01 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6196:[05-Mar-2026 13:02:57 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6200:[16-Apr-2026 03:46:24 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6204:[13-May-2026 03:20:31 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6208:[13-May-2026 10:41:37 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6212:[13-May-2026 14:48:30 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6216:[22-May-2026 17:44:09 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6220:[23-May-2026 10:40:18 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6224:[03-Jul-2026 20:18:29 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6228:[15-Jul-2026 19:24:42 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6232:[25-Jul-2026 07:50:11 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-nav-menu-widget.php:17
6236:[25-Jul-2026 07:50:12 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-archives.php:17
6240:[25-Jul-2026 07:50:13 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-block.php:17
6244:[25-Jul-2026 07:50:14 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-calendar.php:17
6248:[25-Jul-2026 07:50:14 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-categories.php:17
6252:[25-Jul-2026 07:50:15 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-custom-html.php:17
6256:[25-Jul-2026 07:50:16 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-links.php:17
6260:[25-Jul-2026 07:50:16 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget_Media" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-media-audio.php:18
6264:[25-Jul-2026 07:50:17 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget_Media" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-media-gallery.php:18
6268:[25-Jul-2026 07:50:17 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget_Media" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-media-image.php:18
6272:[25-Jul-2026 07:50:18 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget_Media" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-media-video.php:18
6276:[25-Jul-2026 07:50:20 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-media.php:17
6280:[25-Jul-2026 07:50:21 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-meta.php:19
6284:[25-Jul-2026 07:50:22 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-pages.php:17
6288:[25-Jul-2026 07:50:22 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-recent-comments.php:17
6292:[25-Jul-2026 07:50:23 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-recent-posts.php:17
6296:[25-Jul-2026 07:50:24 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-rss.php:17
6300:[25-Jul-2026 07:50:24 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-search.php:17
6304:[25-Jul-2026 07:50:25 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-tag-cloud.php:17
6308:[25-Jul-2026 07:50:25 Europe/Belgrade] PHP Fatal error:  Uncaught Error: Class "WP_Widget" not found in /home/icaffeco/importane.rs/wp-includes/widgets/class-wp-widget-text.php:17
6315:[10-Nov-2025 09:40:26 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6319:[10-Nov-2025 09:40:26 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6320:[10-Nov-2025 09:40:29 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6324:[10-Nov-2025 09:41:14 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6327:[10-Nov-2025 09:41:14 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6328:[10-Nov-2025 09:41:14 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6332:[10-Nov-2025 09:41:17 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6335:[10-Nov-2025 09:41:18 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6336:[10-Nov-2025 09:41:18 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6340:[10-Nov-2025 15:52:59 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6344:[10-Nov-2025 15:53:00 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6347:[10-Nov-2025 15:53:00 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6350:[10-Nov-2025 15:53:00 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6353:[10-Nov-2025 15:53:02 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6356:[10-Nov-2025 15:53:02 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6357:[10-Nov-2025 15:53:02 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6361:[10-Nov-2025 15:54:01 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6364:[10-Nov-2025 15:55:01 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6368:[10-Nov-2025 15:55:11 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6371:[10-Nov-2025 15:55:11 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6375:[10-Nov-2025 15:55:11 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6376:[10-Nov-2025 15:55:16 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6379:[10-Nov-2025 15:55:17 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6382:[10-Nov-2025 15:55:17 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6384:[10-Nov-2025 15:55:21 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6387:[10-Nov-2025 15:55:22 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6391:[10-Nov-2025 15:55:26 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6394:[10-Nov-2025 15:55:26 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6398:[10-Nov-2025 15:55:30 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6401:[10-Nov-2025 15:55:31 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6405:[10-Nov-2025 15:55:33 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6406:[22-Nov-2025 09:52:09 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6410:[22-Nov-2025 09:52:10 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6413:[22-Nov-2025 09:52:11 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6416:[22-Nov-2025 09:52:11 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6419:[22-Nov-2025 09:52:13 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6422:[22-Nov-2025 09:52:13 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6425:[22-Nov-2025 09:52:25 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6429:[22-Nov-2025 09:52:26 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6433:[22-Nov-2025 09:52:26 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6437:[22-Nov-2025 09:53:03 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6440:[22-Nov-2025 09:53:14 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6443:[22-Nov-2025 09:53:17 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6447:[22-Nov-2025 09:53:19 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6450:[22-Nov-2025 09:53:20 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6453:[22-Nov-2025 09:53:20 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6456:[22-Nov-2025 09:53:22 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6459:[22-Nov-2025 09:53:24 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6462:[22-Nov-2025 09:53:24 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6466:[22-Nov-2025 09:53:30 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6469:[22-Nov-2025 09:53:52 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6472:[22-Nov-2025 09:53:52 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6473:[22-Nov-2025 09:53:52 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6478:[22-Nov-2025 09:53:54 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6481:[22-Nov-2025 09:54:52 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6484:[22-Nov-2025 09:55:52 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6488:[22-Nov-2025 09:55:59 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6491:[22-Nov-2025 09:55:59 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6494:[22-Nov-2025 09:56:02 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6498:[22-Nov-2025 09:56:03 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6502:[22-Nov-2025 09:56:58 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6505:[22-Nov-2025 09:57:00 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119
6508:[22-Nov-2025 09:57:38 Europe/Belgrade] PHP Deprecated:  session_set_save_handler(): Providing individual callbacks instead of an object implementing SessionHandlerInterface is deprecated in /usr/local/cpanel/base/3rdparty/roundcube/program/lib/Roundcube/rcube_session.php on line 119

============================================================
5. FRESH AUTHENTICATED 500 CAPTURE
============================================================
FRESH_REPRO_BASELINE_TIME=2026-08-18 17:57:14 +0200
FRESH_REPRO_CAPTURE_TIME=2026-08-18 17:57:44 +0200

--- ALL NEW LOG BYTES AFTER BASELINE (sanitized, capped per log) ---

===== LIVE DELTA /home/icaffeco/.softaculous/logs/error_log.log =====
mtime=2024-10-01 15:50:13.673108350 +0200 size=0 inode=212861448
BASELINE_INODE=212861448 BASELINE_SIZE=0 BASELINE_MTIME=1727790613
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/error_log =====
mtime=2026-08-18 00:00:22.731431588 +0200 size=0 inode=212881451
BASELINE_INODE=212881451 BASELINE_SIZE=0 BASELINE_MTIME=1787004022
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/wp-admin/error_log =====
mtime=2026-08-11 00:38:14.250480323 +0200 size=23083 inode=212861852
BASELINE_INODE=212861852 BASELINE_SIZE=23083 BASELINE_MTIME=1786401494
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/wp-admin/network/error_log =====
mtime=2025-07-01 11:02:06.627893153 +0200 size=215 inode=212861842
BASELINE_INODE=212861842 BASELINE_SIZE=215 BASELINE_MTIME=1751360526
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/wp-content/languages/error_log =====
mtime=2026-07-23 17:50:12.242312039 +0200 size=29100 inode=212860969
BASELINE_INODE=212860969 BASELINE_SIZE=29100 BASELINE_MTIME=1784821812
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/wp-includes/IXR/error_log =====
mtime=2026-07-25 12:53:58.561910264 +0200 size=4368 inode=212962245
BASELINE_INODE=212962245 BASELINE_SIZE=4368 BASELINE_MTIME=1784976838
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/wp-includes/block-bindings/error_log =====
mtime=2026-08-04 06:40:43.815610555 +0200 size=9773 inode=212962885
BASELINE_INODE=212962885 BASELINE_SIZE=9773 BASELINE_MTIME=1785818443
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/wp-includes/block-patterns/error_log =====
mtime=2026-08-01 16:30:55.056614034 +0200 size=20446 inode=212955380
BASELINE_INODE=212955380 BASELINE_SIZE=20446 BASELINE_MTIME=1785594655
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/wp-includes/block-supports/error_log =====
mtime=2026-08-04 05:56:13.605644358 +0200 size=45522 inode=212946719
BASELINE_INODE=212946719 BASELINE_SIZE=45522 BASELINE_MTIME=1785815773
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/wp-includes/build/error_log =====
mtime=2026-07-15 21:54:21.730775252 +0200 size=3083 inode=213285206
BASELINE_INODE=213285206 BASELINE_SIZE=3083 BASELINE_MTIME=1784145261
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/wp-includes/customize/error_log =====
mtime=2026-07-26 00:37:21.003883800 +0200 size=79505 inode=212962726
BASELINE_INODE=212962726 BASELINE_SIZE=79505 BASELINE_MTIME=1785019041
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/wp-includes/error_log =====
mtime=2026-07-23 17:56:04.839570243 +0200 size=238368 inode=212861295
BASELINE_INODE=212861295 BASELINE_SIZE=238368 BASELINE_MTIME=1784822164
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/wp-includes/interactivity-api/error_log =====
mtime=2026-07-17 11:38:40.413487861 +0200 size=2358 inode=212962890
BASELINE_INODE=212962890 BASELINE_SIZE=2358 BASELINE_MTIME=1784281120
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/wp-includes/rest-api/error_log =====
mtime=2026-07-17 03:19:55.559502438 +0200 size=2240 inode=212963408
BASELINE_INODE=212963408 BASELINE_SIZE=2240 BASELINE_MTIME=1784251195
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/wp-includes/theme-compat/error_log =====
mtime=2026-07-24 20:10:31.456131870 +0200 size=18505 inode=212959847
BASELINE_INODE=212959847 BASELINE_SIZE=18505 BASELINE_MTIME=1784916631
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/adalyatobacco.rs/wp-includes/widgets/error_log =====
mtime=2026-08-04 03:34:53.979439730 +0200 size=72401 inode=212946589
BASELINE_INODE=212946589 BASELINE_SIZE=72401 BASELINE_MTIME=1785807293
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/ald1n-project/apps/cms/current/error_log =====
mtime=2026-08-17 12:25:35.006971876 +0200 size=7101 inode=213830498
BASELINE_INODE=213830498 BASELINE_SIZE=7101 BASELINE_MTIME=1786962335
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/ald1n-project/apps/cms/current/storage/logs/.gitignore =====
mtime=2026-08-06 12:13:19.158909304 +0200 size=14 inode=213779740
BASELINE_INODE=213779740 BASELINE_SIZE=14 BASELINE_MTIME=1786011199
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-11.log =====
mtime=2026-08-11 22:55:39.880068789 +0200 size=4305 inode=213784991
BASELINE_INODE=213784991 BASELINE_SIZE=4305 BASELINE_MTIME=1786481739
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-12.log =====
mtime=2026-08-12 19:39:01.960961874 +0200 size=38429 inode=213783407
BASELINE_INODE=213783407 BASELINE_SIZE=38429 BASELINE_MTIME=1786556341
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-13.log =====
mtime=2026-08-13 00:21:16.753540031 +0200 size=603 inode=213782322
BASELINE_INODE=213782322 BASELINE_SIZE=603 BASELINE_MTIME=1786573276
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-15.log =====
mtime=2026-08-15 09:34:29.856440824 +0200 size=3464 inode=213783418
BASELINE_INODE=213783418 BASELINE_SIZE=3464 BASELINE_MTIME=1786779269
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-17.log =====
mtime=2026-08-17 19:47:58.884216410 +0200 size=12416 inode=213783409
BASELINE_INODE=213783409 BASELINE_SIZE=12416 BASELINE_MTIME=1786988878
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/ald1n-project/apps/cms/current/storage/logs/laravel-2026-08-18.log =====
mtime=2026-08-18 17:57:38.579702849 +0200 size=954838 inode=213782290
BASELINE_INODE=213782290 BASELINE_SIZE=825961 BASELINE_MTIME=1787068627
[2026-08-18 17:57:27] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"9adaae32-01d6-4fbd-b43e-2ac0b39133b3","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-18 17:57:27] production.ERROR: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) {"request_id":"9adaae32-01d6-4fbd-b43e-2ac0b39133b3","userId":1,"exception":"[object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
[stacktrace]
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
#1 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#2 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#3 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#4 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#5 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(82): Illuminate\\View\\View->render()
#6 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(40): Illuminate\\Http\\Response->setContent()
#7 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(939): Illuminate\\Http\\Response->__construct()
#8 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(906): Illuminate\\Routing\\Router::toResponse()
#9 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Routing\\Router->prepareResponse()
#10 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->{closure:Illuminate\\Routing\\Router::runRouteWithinStack():821}()
#11 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
#13 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureActiveUser.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#14 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureActiveUser->handle()
#15 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#16 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle()
#17 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#18 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle()
#19 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php(104): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#20 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery->handle()
#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#22 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
#26 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#27 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle()
#28 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle()
#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then()
#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack()
#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()
#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()
#35 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()
#36 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
#37 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#38 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
#39 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#40 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#41 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#42 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#43 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#44 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#45 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#46 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#47 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#48 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#49 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#50 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
#51 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#52 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#53 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#54 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#55 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(28): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#56 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()
#57 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureRuntimeDirectories.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#58 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureRuntimeDirectories->handle()
#59 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/AttachRequestId.php(25): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#60 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AttachRequestId->handle()
#61 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#62 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()
#63 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#64 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#65 /home/icaffeco/ald1n-project/apps/cms/current/public/index.php(19): Illuminate\\Foundation\\Application->handleRequest()
#66 {main}

[previous exception] [object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
[stacktrace]
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
#1 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#2 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#3 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#4 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#5 /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/d9c1fae579fd48a8c10d879a5e4e4ea9.php(1238): Illuminate\\View\\View->render()
#6 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(123): require('/home/icaffeco/...')
#7 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::{closure:Illuminate\\Filesystem\\Filesystem::getRequire():120}()
#8 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire()
#9 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#10 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#11 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#13 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(82): Illuminate\\View\\View->render()
#14 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(40): Illuminate\\Http\\Response->setContent()
#15 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(939): Illuminate\\Http\\Response->__construct()
#16 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(906): Illuminate\\Routing\\Router::toResponse()
#17 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Routing\\Router->prepareResponse()
#18 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->{closure:Illuminate\\Routing\\Router::runRouteWithinStack():821}()
#19 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#20 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
#21 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureActiveUser.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#22 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureActiveUser->handle()
#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle()
#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#26 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle()
#27 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php(104): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#28 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery->handle()
#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#35 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle()
#36 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#37 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle()
#38 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#39 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then()
#40 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack()
#41 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()
#42 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()
#43 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()
#44 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
#45 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#46 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
#47 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#48 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#49 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#50 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#51 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#52 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#53 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#54 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#55 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#56 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#57 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#58 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
#59 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#60 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#61 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#62 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#63 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(28): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#64 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()
#65 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureRuntimeDirectories.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#66 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureRuntimeDirectories->handle()
#67 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/AttachRequestId.php(25): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#68 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AttachRequestId->handle()
#69 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#70 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()
#71 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#72 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#73 /home/icaffeco/ald1n-project/apps/cms/current/public/index.php(19): Illuminate\\Foundation\\Application->handleRequest()
#74 {main}

[previous exception] [object] (ParseError(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
[stacktrace]
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::{closure:Illuminate\\Filesystem\\Filesystem::getRequire():120}()
#1 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire()
#2 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#3 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#4 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#5 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#6 /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/d9c1fae579fd48a8c10d879a5e4e4ea9.php(1238): Illuminate\\View\\View->render()
#7 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(123): require('/home/icaffeco/...')
#8 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::{closure:Illuminate\\Filesystem\\Filesystem::getRequire():120}()
#9 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire()
#10 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#11 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#13 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#14 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(82): Illuminate\\View\\View->render()
#15 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(40): Illuminate\\Http\\Response->setContent()
#16 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(939): Illuminate\\Http\\Response->__construct()
#17 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(906): Illuminate\\Routing\\Router::toResponse()
#18 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Routing\\Router->prepareResponse()
#19 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->{closure:Illuminate\\Routing\\Router::runRouteWithinStack():821}()
#20 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
#22 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureActiveUser.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureActiveUser->handle()
#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle()
#26 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#27 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle()
#28 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php(104): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery->handle()
#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
#35 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#36 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle()
#37 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#38 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle()
#39 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#40 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then()
#41 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack()
#42 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()
#43 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()
#44 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()
#45 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
#46 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#47 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
#48 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#49 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#50 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#51 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#52 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#53 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#54 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#55 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#56 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#57 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#58 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#59 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
#60 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#61 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#62 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#63 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#64 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(28): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#65 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()
#66 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureRuntimeDirectories.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#67 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureRuntimeDirectories->handle()
#68 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/AttachRequestId.php(25): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#69 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AttachRequestId->handle()
#70 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#71 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()
#72 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#73 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#74 /home/icaffeco/ald1n-project/apps/cms/current/public/index.php(19): Illuminate\\Foundation\\Application->handleRequest()
#75 {main}
"}
[2026-08-18 17:57:37] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"76f6acc4-6c2a-47ca-9f14-46fc926d822b","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-18 17:57:37] production.ERROR: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) {"request_id":"76f6acc4-6c2a-47ca-9f14-46fc926d822b","userId":1,"exception":"[object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
[stacktrace]
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
#1 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#2 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#3 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#4 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#5 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(82): Illuminate\\View\\View->render()
#6 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(40): Illuminate\\Http\\Response->setContent()
#7 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(939): Illuminate\\Http\\Response->__construct()
#8 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(906): Illuminate\\Routing\\Router::toResponse()
#9 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Routing\\Router->prepareResponse()
#10 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->{closure:Illuminate\\Routing\\Router::runRouteWithinStack():821}()
#11 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
#13 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureActiveUser.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#14 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureActiveUser->handle()
#15 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#16 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle()
#17 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#18 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle()
#19 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php(104): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#20 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery->handle()
#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#22 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
#26 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#27 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle()
#28 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle()
#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then()
#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack()
#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()
#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()
#35 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()
#36 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
#37 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#38 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
#39 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#40 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#41 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#42 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#43 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#44 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#45 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#46 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#47 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#48 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#49 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#50 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
#51 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#52 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#53 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#54 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#55 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(28): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#56 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()
#57 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureRuntimeDirectories.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#58 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureRuntimeDirectories->handle()
#59 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/AttachRequestId.php(25): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#60 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AttachRequestId->handle()
#61 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#62 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()
#63 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#64 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#65 /home/icaffeco/ald1n-project/apps/cms/current/public/index.php(19): Illuminate\\Foundation\\Application->handleRequest()
#66 {main}

[previous exception] [object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
[stacktrace]
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
#1 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#2 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#3 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#4 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#5 /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/d9c1fae579fd48a8c10d879a5e4e4ea9.php(1238): Illuminate\\View\\View->render()
#6 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(123): require('/home/icaffeco/...')
#7 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::{closure:Illuminate\\Filesystem\\Filesystem::getRequire():120}()
#8 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire()
#9 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#10 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#11 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#13 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(82): Illuminate\\View\\View->render()
#14 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(40): Illuminate\\Http\\Response->setContent()
#15 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(939): Illuminate\\Http\\Response->__construct()
#16 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(906): Illuminate\\Routing\\Router::toResponse()
#17 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Routing\\Router->prepareResponse()
#18 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->{closure:Illuminate\\Routing\\Router::runRouteWithinStack():821}()
#19 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#20 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
#21 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureActiveUser.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#22 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureActiveUser->handle()
#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle()
#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#26 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle()
#27 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php(104): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#28 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery->handle()
#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#35 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle()
#36 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#37 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle()
#38 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#39 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then()
#40 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack()
#41 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()
#42 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()
#43 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()
#44 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
#45 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#46 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
#47 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#48 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#49 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#50 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#51 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#52 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#53 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#54 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#55 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#56 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#57 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#58 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
#59 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#60 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#61 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#62 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#63 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(28): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#64 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()
#65 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureRuntimeDirectories.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#66 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureRuntimeDirectories->handle()
#67 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/AttachRequestId.php(25): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#68 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AttachRequestId->handle()
#69 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#70 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()
#71 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#72 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#73 /home/icaffeco/ald1n-project/apps/cms/current/public/index.php(19): Illuminate\\Foundation\\Application->handleRequest()
#74 {main}

[previous exception] [object] (ParseError(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
[stacktrace]
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::{closure:Illuminate\\Filesystem\\Filesystem::getRequire():120}()
#1 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire()
#2 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#3 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#4 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#5 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#6 /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/d9c1fae579fd48a8c10d879a5e4e4ea9.php(1238): Illuminate\\View\\View->render()
#7 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(123): require('/home/icaffeco/...')
#8 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::{closure:Illuminate\\Filesystem\\Filesystem::getRequire():120}()
#9 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire()
#10 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#11 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#13 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#14 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(82): Illuminate\\View\\View->render()
#15 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(40): Illuminate\\Http\\Response->setContent()
#16 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(939): Illuminate\\Http\\Response->__construct()
#17 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(906): Illuminate\\Routing\\Router::toResponse()
#18 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Routing\\Router->prepareResponse()
#19 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->{closure:Illuminate\\Routing\\Router::runRouteWithinStack():821}()
#20 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
#22 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureActiveUser.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureActiveUser->handle()
#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle()
#26 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#27 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle()
#28 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php(104): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery->handle()
#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
#35 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#36 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle()
#37 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#38 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle()
#39 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#40 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then()
#41 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack()
#42 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()
#43 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()
#44 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()
#45 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
#46 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#47 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
#48 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#49 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#50 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#51 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#52 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#53 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#54 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#55 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#56 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#57 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#58 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#59 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
#60 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#61 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#62 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#63 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#64 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(28): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#65 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()
#66 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureRuntimeDirectories.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#67 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureRuntimeDirectories->handle()
#68 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/AttachRequestId.php(25): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#69 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AttachRequestId->handle()
#70 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#71 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()
#72 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#73 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#74 /home/icaffeco/ald1n-project/apps/cms/current/public/index.php(19): Illuminate\\Foundation\\Application->handleRequest()
#75 {main}
"}
[2026-08-18 17:57:38] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"a485002f-5da7-49ea-9ba0-cf88f6cce8ab","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-18 17:57:38] production.ERROR: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) {"request_id":"a485002f-5da7-49ea-9ba0-cf88f6cce8ab","userId":1,"exception":"[object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
[stacktrace]
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
#1 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#2 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#3 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#4 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#5 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(82): Illuminate\\View\\View->render()
#6 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(40): Illuminate\\Http\\Response->setContent()
#7 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(939): Illuminate\\Http\\Response->__construct()
#8 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(906): Illuminate\\Routing\\Router::toResponse()
#9 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Routing\\Router->prepareResponse()
#10 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->{closure:Illuminate\\Routing\\Router::runRouteWithinStack():821}()
#11 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
#13 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureActiveUser.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#14 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureActiveUser->handle()
#15 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#16 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle()
#17 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#18 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle()
#19 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php(104): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#20 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery->handle()
#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#22 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
#26 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#27 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle()
#28 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle()
#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then()
#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack()
#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()
#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()
#35 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()
#36 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
#37 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#38 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
#39 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#40 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#41 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#42 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#43 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#44 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#45 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#46 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#47 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#48 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#49 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#50 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
#51 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#52 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#53 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#54 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#55 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(28): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#56 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()
#57 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureRuntimeDirectories.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#58 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureRuntimeDirectories->handle()
#59 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/AttachRequestId.php(25): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#60 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AttachRequestId->handle()
#61 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#62 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()
#63 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#64 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#65 /home/icaffeco/ald1n-project/apps/cms/current/public/index.php(19): Illuminate\\Foundation\\Application->handleRequest()
#66 {main}

[previous exception] [object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
[stacktrace]
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
#1 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#2 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#3 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#4 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#5 /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/d9c1fae579fd48a8c10d879a5e4e4ea9.php(1238): Illuminate\\View\\View->render()
#6 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(123): require('/home/icaffeco/...')
#7 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::{closure:Illuminate\\Filesystem\\Filesystem::getRequire():120}()
#8 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire()
#9 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#10 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#11 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#13 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(82): Illuminate\\View\\View->render()
#14 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(40): Illuminate\\Http\\Response->setContent()
#15 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(939): Illuminate\\Http\\Response->__construct()
#16 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(906): Illuminate\\Routing\\Router::toResponse()
#17 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Routing\\Router->prepareResponse()
#18 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->{closure:Illuminate\\Routing\\Router::runRouteWithinStack():821}()
#19 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#20 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
#21 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureActiveUser.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#22 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureActiveUser->handle()
#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle()
#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#26 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle()
#27 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php(104): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#28 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery->handle()
#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#35 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle()
#36 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#37 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle()
#38 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#39 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then()
#40 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack()
#41 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()
#42 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()
#43 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()
#44 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
#45 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#46 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
#47 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#48 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#49 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#50 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#51 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#52 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#53 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#54 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#55 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#56 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#57 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#58 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
#59 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#60 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#61 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#62 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#63 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(28): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#64 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()
#65 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureRuntimeDirectories.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#66 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureRuntimeDirectories->handle()
#67 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/AttachRequestId.php(25): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#68 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AttachRequestId->handle()
#69 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#70 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()
#71 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#72 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#73 /home/icaffeco/ald1n-project/apps/cms/current/public/index.php(19): Illuminate\\Foundation\\Application->handleRequest()
#74 {main}

[previous exception] [object] (ParseError(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
[stacktrace]
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::{closure:Illuminate\\Filesystem\\Filesystem::getRequire():120}()
#1 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire()
#2 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#3 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#4 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#5 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#6 /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/d9c1fae579fd48a8c10d879a5e4e4ea9.php(1238): Illuminate\\View\\View->render()
#7 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(123): require('/home/icaffeco/...')
#8 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::{closure:Illuminate\\Filesystem\\Filesystem::getRequire():120}()
#9 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire()
#10 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#11 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#13 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#14 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(82): Illuminate\\View\\View->render()
#15 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(40): Illuminate\\Http\\Response->setContent()
#16 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(939): Illuminate\\Http\\Response->__construct()
#17 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(906): Illuminate\\Routing\\Router::toResponse()
#18 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Routing\\Router->prepareResponse()
#19 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->{closure:Illuminate\\Routing\\Router::runRouteWithinStack():821}()
#20 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
#22 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureActiveUser.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureActiveUser->handle()
#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle()
#26 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#27 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle()
#28 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php(104): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery->handle()
#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
#35 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#36 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle()
#37 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#38 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle()
#39 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#40 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then()
#41 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack()
#42 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()
#43 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()
#44 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()
#45 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
#46 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#47 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
#48 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#49 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#50 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#51 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#52 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#53 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#54 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#55 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#56 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#57 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#58 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#59 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
#60 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#61 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#62 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#63 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#64 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(28): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#65 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()
#66 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureRuntimeDirectories.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#67 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureRuntimeDirectories->handle()
#68 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/AttachRequestId.php(25): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#69 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AttachRequestId->handle()
#70 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#71 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()
#72 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#73 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#74 /home/icaffeco/ald1n-project/apps/cms/current/public/index.php(19): Illuminate\\Foundation\\Application->handleRequest()
#75 {main}
"}

===== LIVE DELTA /home/icaffeco/ald1n-project/apps/cms/current/storage/logs/scheduler.log =====
mtime=2026-08-18 17:57:03.523366874 +0200 size=6125560 inode=213782279
BASELINE_INODE=213782279 BASELINE_SIZE=6125560 BASELINE_MTIME=1787068623
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/arsa-arch.de/error_log =====
mtime=2026-08-05 00:00:36.963413186 +0200 size=0 inode=212861469
BASELINE_INODE=212861469 BASELINE_SIZE=0 BASELINE_MTIME=1785880836
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/arsa-arch.rs/error_log =====
mtime=2026-08-11 00:00:19.469176387 +0200 size=0 inode=212870589
BASELINE_INODE=212870589 BASELINE_SIZE=0 BASELINE_MTIME=1786399219
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/error_log =====
mtime=2026-08-18 17:26:04.263716689 +0200 size=219 inode=212892609
BASELINE_INODE=212892609 BASELINE_SIZE=219 BASELINE_MTIME=1787066764
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/essenza-online.com/error_log =====
mtime=2026-08-18 16:38:34.580063803 +0200 size=1108 inode=212871381
BASELINE_INODE=212871381 BASELINE_SIZE=1108 BASELINE_MTIME=1787063914
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/essenza-online.com/wp-admin/error_log =====
mtime=2026-05-19 08:32:50.623108044 +0200 size=12433 inode=212902955
BASELINE_INODE=212902955 BASELINE_SIZE=12433 BASELINE_MTIME=1779172370
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/essenza-online.com/wp-includes/PHPMailer/error_log =====
mtime=2026-05-28 09:10:44.421801814 +0200 size=318 inode=212967187
BASELINE_INODE=212967187 BASELINE_SIZE=318 BASELINE_MTIME=1779952244
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/essenza-online.com/wp-includes/error_log =====
mtime=2026-05-28 09:09:59.796355764 +0200 size=1783 inode=212869906
BASELINE_INODE=212869906 BASELINE_SIZE=1783 BASELINE_MTIME=1779952199
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/essenza-online.com/wp-includes/widgets/error_log =====
mtime=2026-05-28 09:12:42.136952468 +0200 size=313 inode=213004822
BASELINE_INODE=213004822 BASELINE_SIZE=313 BASELINE_MTIME=1779952362
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/importane.rs/error_log =====
mtime=2026-08-05 00:00:37.396417636 +0200 size=0 inode=212881139
BASELINE_INODE=212881139 BASELINE_SIZE=0 BASELINE_MTIME=1785880837
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/importane.rs/wp-admin/error_log =====
mtime=2026-03-31 09:53:53.029602516 +0200 size=2538281 inode=213125693
BASELINE_INODE=213125693 BASELINE_SIZE=2538281 BASELINE_MTIME=1774943633
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/importane.rs/wp-admin/includes/error_log =====
mtime=2026-07-25 07:44:39.401442797 +0200 size=17597 inode=213144790
BASELINE_INODE=213144790 BASELINE_SIZE=17597 BASELINE_MTIME=1784958279
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/importane.rs/wp-includes/PHPMailer/error_log =====
mtime=2026-07-25 07:49:56.176414997 +0200 size=8874 inode=213190636
BASELINE_INODE=213190636 BASELINE_SIZE=8874 BASELINE_MTIME=1784958596
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/importane.rs/wp-includes/blocks/error_log =====
mtime=2024-11-08 00:43:09.687222452 +0100 size=508 inode=213200573
BASELINE_INODE=213200573 BASELINE_SIZE=508 BASELINE_MTIME=1731022989
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/importane.rs/wp-includes/error_log =====
mtime=2026-07-25 07:49:00.915896012 +0200 size=164882 inode=213143100
BASELINE_INODE=213143100 BASELINE_SIZE=164882 BASELINE_MTIME=1784958540
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/importane.rs/wp-includes/widgets/error_log =====
mtime=2026-07-25 07:50:25.642693930 +0200 size=43147 inode=213202434
BASELINE_INODE=213202434 BASELINE_SIZE=43147 BASELINE_MTIME=1784958625
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/.php.error.log =====
mtime=2026-05-19 13:39:35.994914544 +0200 size=295691 inode=212881171
BASELINE_INODE=212881171 BASELINE_SIZE=295691 BASELINE_MTIME=1779190775
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/adalyatobacco.ald1n.com-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.810310996 +0200 size=28592 inode=212878540
BASELINE_INODE=212878540 BASELINE_SIZE=28592 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/adalyatobacco.ald1n.com-ssl_log-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.850311455 +0200 size=2230325 inode=212878541
BASELINE_INODE=212878541 BASELINE_SIZE=2230325 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/adalyatobacco.icaffe24.com-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.586208092 +0100 size=150642 inode=212881176
BASELINE_INODE=212881176 BASELINE_SIZE=150642 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/adalyatobacco.icaffe24.com-ssl_log-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.586208092 +0100 size=2300015 inode=212881177
BASELINE_INODE=212881177 BASELINE_SIZE=2300015 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/ald1n.com-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.793310801 +0200 size=47060 inode=212876421
BASELINE_INODE=212876421 BASELINE_SIZE=47060 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/ald1n.com-ssl_log-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.805310938 +0200 size=308012 inode=212876422
BASELINE_INODE=212876422 BASELINE_SIZE=308012 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/ald1n.com.icaffe24.com-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.587208092 +0100 size=75074 inode=212881182
BASELINE_INODE=212881182 BASELINE_SIZE=75074 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/ald1n.com.icaffe24.com-ssl_log-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.589208093 +0100 size=133385 inode=212881183
BASELINE_INODE=212881183 BASELINE_SIZE=133385 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/arsa-arch.de.ald1n.com-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.854311501 +0200 size=41082 inode=212878543
BASELINE_INODE=212878543 BASELINE_SIZE=41082 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/arsa-arch.de.ald1n.com-ssl_log-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.857311535 +0200 size=46614 inode=212878544
BASELINE_INODE=212878544 BASELINE_SIZE=46614 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/arsa-arch.de.icaffe24.com-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.589208093 +0100 size=52741 inode=212881188
BASELINE_INODE=212881188 BASELINE_SIZE=52741 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/arsa-arch.de.icaffe24.com-ssl_log-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.590208093 +0100 size=47198 inode=212881189
BASELINE_INODE=212881189 BASELINE_SIZE=47198 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/arsa-arch.rs.ald1n.com-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.859311558 +0200 size=40991 inode=212878549
BASELINE_INODE=212878549 BASELINE_SIZE=40991 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/arsa-arch.rs.ald1n.com-ssl_log-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.861311581 +0200 size=115818 inode=212891552
BASELINE_INODE=212891552 BASELINE_SIZE=115818 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/arsa.ald1n.com-Mar-2025.gz =====
mtime=2025-03-18 01:18:51.053328859 +0100 size=19820 inode=212870491
BASELINE_INODE=212870491 BASELINE_SIZE=19820 BASELINE_MTIME=1742257131
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/arsa.ald1n.com-ssl_log-Mar-2025.gz =====
mtime=2025-03-20 01:20:45.478868515 +0100 size=14692 inode=212870492
BASELINE_INODE=212870492 BASELINE_SIZE=14692 BASELINE_MTIME=1742430045
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/arsa.icaffe24.com-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.590208093 +0100 size=29612 inode=212881194
BASELINE_INODE=212881194 BASELINE_SIZE=29612 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/arsa.icaffe24.com-ssl_log-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.591208093 +0100 size=50723 inode=212881195
BASELINE_INODE=212881195 BASELINE_SIZE=50723 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/cms.ald1n.com-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.863311604 +0200 size=5863 inode=212891553
BASELINE_INODE=212891553 BASELINE_SIZE=5863 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/cms.ald1n.com-ssl_log-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.867311650 +0200 size=88280 inode=212891554
BASELINE_INODE=212891554 BASELINE_SIZE=88280 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/essenza-online.com.ald1n.com-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.872311707 +0200 size=143141 inode=212891555
BASELINE_INODE=212891555 BASELINE_SIZE=143141 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/essenza-online.com.ald1n.com-ssl_log-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.877311764 +0200 size=71275 inode=212891556
BASELINE_INODE=212891556 BASELINE_SIZE=71275 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/ftp.ald1n.com-ftp_log-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.973312865 +0200 size=1392536 inode=212891567
BASELINE_INODE=212891567 BASELINE_SIZE=1392536 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/ftp.ald1n.com-ftp_log-Dec-2025.gz =====
mtime=2025-12-30 19:14:46.778627554 +0100 size=521 inode=212873865
BASELINE_INODE=212873865 BASELINE_SIZE=521 BASELINE_MTIME=1767118486
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/ftp.icaffe24.com-ftp_log-Dec-2024.gz =====
mtime=2024-12-07 13:18:38.236370449 +0100 size=198 inode=212881198
BASELINE_INODE=212881198 BASELINE_SIZE=198 BASELINE_MTIME=1733573918
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/ftp.icaffe24.com-ftp_log-Nov-2024.gz =====
mtime=2024-11-25 13:13:07.247808422 +0100 size=631 inode=212881199
BASELINE_INODE=212881199 BASELINE_SIZE=631 BASELINE_MTIME=1732536787
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/hastall.rs.ald1n.com-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.879311787 +0200 size=38565 inode=212891557
BASELINE_INODE=212891557 BASELINE_SIZE=38565 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/hastall.rs.ald1n.com-ssl_log-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.881311810 +0200 size=10956 inode=212891558
BASELINE_INODE=212891558 BASELINE_SIZE=10956 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/icaffe24.com-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.584208091 +0100 size=41879 inode=212881200
BASELINE_INODE=212881200 BASELINE_SIZE=41879 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/icaffe24.com-ssl_log-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.585208092 +0100 size=863317 inode=212881201
BASELINE_INODE=212881201 BASELINE_SIZE=863317 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/icaffe24.com.ald1n.com-Feb-2026.gz =====
mtime=2026-02-18 22:06:25.349395780 +0100 size=58386 inode=212876605
BASELINE_INODE=212876605 BASELINE_SIZE=58386 BASELINE_MTIME=1771448785
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/icaffe24.com.ald1n.com-ssl_log-Feb-2026.gz =====
mtime=2026-02-18 22:06:25.350395789 +0100 size=229371 inode=212876606
BASELINE_INODE=212876606 BASELINE_SIZE=229371 BASELINE_MTIME=1771448785
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/importane.ald1n.com-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.882311822 +0200 size=47025 inode=212891559
BASELINE_INODE=212891559 BASELINE_SIZE=47025 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/importane.ald1n.com-ssl_log-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.892311936 +0200 size=257647 inode=212891560
BASELINE_INODE=212891560 BASELINE_SIZE=257647 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/importane.icaffe24.com-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.591208093 +0100 size=31479 inode=212881206
BASELINE_INODE=212881206 BASELINE_SIZE=31479 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/importane.icaffe24.com-ssl_log-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.592208094 +0100 size=260919 inode=212881207
BASELINE_INODE=212881207 BASELINE_SIZE=260919 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/mandza.ald1n.com-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.894311959 +0200 size=13494 inode=212891561
BASELINE_INODE=212891561 BASELINE_SIZE=13494 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/mandza.ald1n.com-ssl_log-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.907312108 +0200 size=424748 inode=212891562
BASELINE_INODE=212891562 BASELINE_SIZE=424748 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/mandza.icaffe24.com-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.592208094 +0100 size=20198 inode=212881212
BASELINE_INODE=212881212 BASELINE_SIZE=20198 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/mandza.icaffe24.com-ssl_log-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.593208094 +0100 size=358587 inode=212881213
BASELINE_INODE=212881213 BASELINE_SIZE=358587 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/roundcube/carddav.log =====
mtime=2026-01-25 16:03:15.584583296 +0100 size=31956 inode=213202435
BASELINE_INODE=213202435 BASELINE_SIZE=31956 BASELINE_MTIME=1769353395
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/roundcube/carddav_http.log =====
mtime=2025-10-22 09:01:11.835875016 +0200 size=16192 inode=213191977
BASELINE_INODE=213191977 BASELINE_SIZE=16192 BASELINE_MTIME=1761116471
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/roundcube/errors.log =====
mtime=2026-01-25 22:49:50.013454696 +0100 size=8324 inode=213202436
BASELINE_INODE=213202436 BASELINE_SIZE=8324 BASELINE_MTIME=1769377790
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/roundcube/sendmail.log =====
mtime=2026-05-19 13:20:36.538883719 +0200 size=3157 inode=213202437
BASELINE_INODE=213202437 BASELINE_SIZE=3157 BASELINE_MTIME=1779189636
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/smart-elektrotechnik.ald1n.com-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.911312154 +0200 size=69230 inode=212891563
BASELINE_INODE=212891563 BASELINE_SIZE=69230 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/smart-elektrotechnik.ald1n.com-ssl_log-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.925312315 +0200 size=424305 inode=212891564
BASELINE_INODE=212891564 BASELINE_SIZE=424305 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/smart-elektrotechnik.icaffe24.com-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.593208094 +0100 size=81474 inode=212881218
BASELINE_INODE=212881218 BASELINE_SIZE=81474 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/smart-elektrotechnik.icaffe24.com-ssl_log-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.594208094 +0100 size=382321 inode=212881219
BASELINE_INODE=212881219 BASELINE_SIZE=382321 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/sprintertravel.rs.ald1n.com-Jun-2025.gz =====
mtime=2025-06-18 02:25:41.109230266 +0200 size=11169 inode=212871398
BASELINE_INODE=212871398 BASELINE_SIZE=11169 BASELINE_MTIME=1750206341
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/sprintertravel.rs.ald1n.com-ssl_log-Jun-2025.gz =====
mtime=2025-06-16 02:20:47.621669983 +0200 size=14404 inode=212871399
BASELINE_INODE=212871399 BASELINE_SIZE=14404 BASELINE_MTIME=1750033247
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/sprintertravel.rs.icaffe24.com-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.594208094 +0100 size=69820 inode=212881224
BASELINE_INODE=212881224 BASELINE_SIZE=69820 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/sprintertravel.rs.icaffe24.com-ssl_log-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.595208094 +0100 size=65119 inode=212881225
BASELINE_INODE=212881225 BASELINE_SIZE=65119 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/turkovic.net.ald1n.com-Feb-2026.gz =====
mtime=2026-02-18 22:06:25.313395462 +0100 size=395 inode=212870551
BASELINE_INODE=212870551 BASELINE_SIZE=395 BASELINE_MTIME=1771448785
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/turkovic.net.ald1n.com-ssl_log-Feb-2026.gz =====
mtime=2026-02-08 21:35:56.783463026 +0100 size=639 inode=212876614
BASELINE_INODE=212876614 BASELINE_SIZE=639 BASELINE_MTIME=1770582956
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/turkovic.net.ald1n.com-ssl_log-Jan-2026.gz =====
mtime=2026-01-31 21:00:31.810964133 +0100 size=63098 inode=212870693
BASELINE_INODE=212870693 BASELINE_SIZE=63098 BASELINE_MTIME=1769889631
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/turkovic.net.icaffe24.com-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.595208094 +0100 size=105736 inode=212881230
BASELINE_INODE=212881230 BASELINE_SIZE=105736 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/turkovic.net.icaffe24.com-ssl_log-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.596208095 +0100 size=893099 inode=212881231
BASELINE_INODE=212881231 BASELINE_SIZE=893099 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/zenskefarmerice.com.ald1n.com-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.930312372 +0200 size=95618 inode=212891565
BASELINE_INODE=212891565 BASELINE_SIZE=95618 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/zenskefarmerice.com.ald1n.com-ssl_log-Aug-2026.gz =====
mtime=2026-08-18 02:08:40.969312820 +0200 size=835550 inode=212891566
BASELINE_INODE=212891566 BASELINE_SIZE=835550 BASELINE_MTIME=1787011720
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/zenskefarmerice.com.icaffe24.com-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.596208095 +0100 size=49530 inode=212881236
BASELINE_INODE=212881236 BASELINE_SIZE=49530 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/logs/zenskefarmerice.com.icaffe24.com-ssl_log-Dec-2024.gz =====
mtime=2024-12-27 01:02:24.596208095 +0100 size=2807470 inode=212881237
BASELINE_INODE=212881237 BASELINE_SIZE=2807470 BASELINE_MTIME=1735257744
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/error_log =====
mtime=2026-08-15 00:00:21.862279001 +0200 size=0 inode=212881407
BASELINE_INODE=212881407 BASELINE_SIZE=0 BASELINE_MTIME=1786744821
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-admin/error_log =====
mtime=2025-05-06 15:04:26.509747306 +0200 size=1001 inode=213268646
BASELINE_INODE=213268646 BASELINE_SIZE=1001 BASELINE_MTIME=1746536666
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-admin/includes/error_log =====
mtime=2026-08-18 10:38:59.386658825 +0200 size=33947 inode=213259293
BASELINE_INODE=213259293 BASELINE_SIZE=33947 BASELINE_MTIME=1787042339
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-content/languages/error_log =====
mtime=2026-08-18 07:52:28.630770090 +0200 size=25705 inode=213269288
BASELINE_INODE=213269288 BASELINE_SIZE=25705 BASELINE_MTIME=1787032348
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-includes/IXR/error_log =====
mtime=2026-08-18 07:50:37.322783224 +0200 size=2432 inode=213320703
BASELINE_INODE=213320703 BASELINE_SIZE=2432 BASELINE_MTIME=1787032237
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-includes/PHPMailer/error_log =====
mtime=2026-08-18 07:50:58.513967718 +0200 size=6426 inode=213320392
BASELINE_INODE=213320392 BASELINE_SIZE=6426 BASELINE_MTIME=1787032258
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-includes/block-bindings/error_log =====
mtime=2026-03-04 23:31:31.251689699 +0100 size=2460 inode=213320680
BASELINE_INODE=213320680 BASELINE_SIZE=2460 BASELINE_MTIME=1772663491
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-includes/block-patterns/error_log =====
mtime=2026-03-04 23:30:50.346336241 +0100 size=4446 inode=213320387
BASELINE_INODE=213320387 BASELINE_SIZE=4446 BASELINE_MTIME=1772663450
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-includes/block-supports/error_log =====
mtime=2026-03-04 23:31:34.938721578 +0100 size=10916 inode=213320681
BASELINE_INODE=213320681 BASELINE_SIZE=10916 BASELINE_MTIME=1772663494
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-includes/blocks/error_log =====
mtime=2024-10-20 02:10:06.747640932 +0200 size=762 inode=213335792
BASELINE_INODE=213335792 BASELINE_SIZE=762 BASELINE_MTIME=1729383006
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-includes/customize/error_log =====
mtime=2026-04-20 14:47:29.939977058 +0200 size=24158 inode=213320705
BASELINE_INODE=213320705 BASELINE_SIZE=24158 BASELINE_MTIME=1776689249
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-includes/error_log =====
mtime=2026-08-18 07:48:38.081716369 +0200 size=165182 inode=213276435
BASELINE_INODE=213276435 BASELINE_SIZE=165182 BASELINE_MTIME=1787032118
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-includes/html-api/error_log =====
mtime=2026-03-04 23:31:42.895790376 +0100 size=1298 inode=213320684
BASELINE_INODE=213320684 BASELINE_SIZE=1298 BASELINE_MTIME=1772663502
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-includes/interactivity-api/error_log =====
mtime=2026-03-04 23:31:41.446777847 +0100 size=770 inode=213320683
BASELINE_INODE=213320683 BASELINE_SIZE=770 BASELINE_MTIME=1772663501
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-includes/l10n/error_log =====
mtime=2026-03-04 23:31:36.012730864 +0100 size=1280 inode=213320682
BASELINE_INODE=213320682 BASELINE_SIZE=1280 BASELINE_MTIME=1772663496
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-includes/rest-api/error_log =====
mtime=2026-03-04 23:31:19.671589605 +0100 size=624 inode=213320671
BASELINE_INODE=213320671 BASELINE_SIZE=624 BASELINE_MTIME=1772663479
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-includes/theme-compat/error_log =====
mtime=2026-03-04 23:32:40.893292762 +0100 size=5402 inode=213320715
BASELINE_INODE=213320715 BASELINE_SIZE=5402 BASELINE_MTIME=1772663560
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/mandza.co.rs/wp-includes/widgets/error_log =====
mtime=2026-08-18 07:52:14.711643973 +0200 size=53311 inode=213337655
BASELINE_INODE=213337655 BASELINE_SIZE=53311 BASELINE_MTIME=1787032334
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/error_log =====
mtime=2026-08-18 17:57:49.689809327 +0200 size=55002 inode=212871413
BASELINE_INODE=212871413 BASELINE_SIZE=54824 BASELINE_MTIME=1787068489
[18-Aug-2026 15:57:49 UTC] PHP Warning:  Undefined variable $end_time in /home/icaffeco/public_html/wp-content/plugins/indeed-coming-soon/includes/views/footer_1.php on line 223

===== LIVE DELTA /home/icaffeco/public_html/wp-admin/error_log =====
mtime=2026-08-12 19:32:07.993310368 +0200 size=291582 inode=213340382
BASELINE_INODE=213340382 BASELINE_SIZE=291582 BASELINE_MTIME=1786555927
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-admin/includes/error_log =====
mtime=2026-08-05 21:55:46.351546123 +0200 size=34416 inode=213516293
BASELINE_INODE=213516293 BASELINE_SIZE=34416 BASELINE_MTIME=1785959746
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-content/languages/error_log =====
mtime=2026-08-05 22:01:29.364047431 +0200 size=25246 inode=213519600
BASELINE_INODE=213519600 BASELINE_SIZE=25246 BASELINE_MTIME=1785960089
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-includes/IXR/error_log =====
mtime=2026-08-05 22:00:21.287383978 +0200 size=1812 inode=213596583
BASELINE_INODE=213596583 BASELINE_SIZE=1812 BASELINE_MTIME=1785960021
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-includes/PHPMailer/error_log =====
mtime=2026-08-05 22:00:32.947491836 +0200 size=5472 inode=213582055
BASELINE_INODE=213582055 BASELINE_SIZE=5472 BASELINE_MTIME=1785960032
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-includes/block-bindings/error_log =====
mtime=2026-05-16 21:47:38.128827745 +0200 size=1222 inode=213596582
BASELINE_INODE=213596582 BASELINE_SIZE=1222 BASELINE_MTIME=1778960858
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-includes/block-patterns/error_log =====
mtime=2026-05-16 21:48:42.756504063 +0200 size=2209 inode=213596603
BASELINE_INODE=213596603 BASELINE_SIZE=2209 BASELINE_MTIME=1778960922
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-includes/block-supports/error_log =====
mtime=2026-05-16 21:47:15.906604025 +0200 size=5422 inode=213596579
BASELINE_INODE=213596579 BASELINE_SIZE=5422 BASELINE_MTIME=1778960835
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-includes/customize/error_log =====
mtime=2026-05-16 21:48:55.755632079 +0200 size=11831 inode=213596605
BASELINE_INODE=213596605 BASELINE_SIZE=11831 BASELINE_MTIME=1778960935
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-includes/error_log =====
mtime=2026-08-05 21:59:20.895877243 +0200 size=133469 inode=213340617
BASELINE_INODE=213340617 BASELINE_SIZE=133469 BASELINE_MTIME=1785959960
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-includes/html-api/error_log =====
mtime=2026-05-16 21:48:39.543472429 +0200 size=645 inode=213596602
BASELINE_INODE=213596602 BASELINE_SIZE=645 BASELINE_MTIME=1778960919
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-includes/interactivity-api/error_log =====
mtime=2026-05-16 21:47:16.712612139 +0200 size=383 inode=213596580
BASELINE_INODE=213596580 BASELINE_SIZE=383 BASELINE_MTIME=1778960836
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-includes/l10n/error_log =====
mtime=2026-05-16 21:47:31.768763715 +0200 size=636 inode=213596581
BASELINE_INODE=213596581 BASELINE_SIZE=636 BASELINE_MTIME=1778960851
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-includes/rest-api/error_log =====
mtime=2026-05-16 21:47:46.240909413 +0200 size=310 inode=213596584
BASELINE_INODE=213596584 BASELINE_SIZE=310 BASELINE_MTIME=1778960866
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-includes/theme-compat/error_log =====
mtime=2026-05-16 21:49:24.760919666 +0200 size=2683 inode=213596623
BASELINE_INODE=213596623 BASELINE_SIZE=2683 BASELINE_MTIME=1778960964
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/public_html/wp-includes/widgets/error_log =====
mtime=2026-08-05 22:01:15.327912966 +0200 size=43185 inode=213586974
BASELINE_INODE=213586974 BASELINE_SIZE=43185 BASELINE_MTIME=1785960075
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/error_log =====
mtime=2026-08-18 15:47:54.011798752 +0200 size=4578 inode=212881455
BASELINE_INODE=212881455 BASELINE_SIZE=4578 BASELINE_MTIME=1787060874
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-admin/error_log =====
mtime=2026-07-30 08:31:45.716629815 +0200 size=36729 inode=213587029
BASELINE_INODE=213587029 BASELINE_SIZE=36729 BASELINE_MTIME=1785393105
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-admin/includes/error_log =====
mtime=2026-07-26 04:56:56.305964449 +0200 size=18664 inode=213586739
BASELINE_INODE=213586739 BASELINE_SIZE=18664 BASELINE_MTIME=1785034616
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-includes/IXR/error_log =====
mtime=2026-07-26 05:03:05.828473207 +0200 size=1968 inode=213659567
BASELINE_INODE=213659567 BASELINE_SIZE=1968 BASELINE_MTIME=1785034985
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-includes/PHPMailer/error_log =====
mtime=2026-08-16 22:18:32.563738449 +0200 size=5940 inode=213647791
BASELINE_INODE=213647791 BASELINE_SIZE=5940 BASELINE_MTIME=1786911512
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-includes/block-bindings/error_log =====
mtime=2026-04-24 20:26:10.556111446 +0200 size=2652 inode=213659037
BASELINE_INODE=213659037 BASELINE_SIZE=2652 BASELINE_MTIME=1777055170
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-includes/block-patterns/error_log =====
mtime=2026-04-24 20:24:27.706160112 +0200 size=4782 inode=213659519
BASELINE_INODE=213659519 BASELINE_SIZE=4782 BASELINE_MTIME=1777055067
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-includes/block-supports/error_log =====
mtime=2026-04-24 20:24:31.498195187 +0200 size=11780 inode=213659566
BASELINE_INODE=213659566 BASELINE_SIZE=11780 BASELINE_MTIME=1777055071
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-includes/blocks/error_log =====
mtime=2024-10-21 13:29:03.138126041 +0200 size=556 inode=213652527
BASELINE_INODE=213652527 BASELINE_SIZE=556 BASELINE_MTIME=1729510143
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-includes/customize/error_log =====
mtime=2026-04-24 20:25:05.228507183 +0200 size=25430 inode=213659518
BASELINE_INODE=213659518 BASELINE_SIZE=25430 BASELINE_MTIME=1777055105
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-includes/error_log =====
mtime=2026-07-26 05:01:57.609929869 +0200 size=134385 inode=213601843
BASELINE_INODE=213601843 BASELINE_SIZE=134385 BASELINE_MTIME=1785034917
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-includes/html-api/error_log =====
mtime=2026-04-24 20:26:24.727242525 +0200 size=1394 inode=213659038
BASELINE_INODE=213659038 BASELINE_SIZE=1394 BASELINE_MTIME=1777055184
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-includes/interactivity-api/error_log =====
mtime=2026-04-24 20:24:26.198146163 +0200 size=818 inode=213659034
BASELINE_INODE=213659034 BASELINE_SIZE=818 BASELINE_MTIME=1777055066
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-includes/l10n/error_log =====
mtime=2026-04-24 20:25:34.046773744 +0200 size=1376 inode=213659564
BASELINE_INODE=213659564 BASELINE_SIZE=1376 BASELINE_MTIME=1777055134
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-includes/rest-api/error_log =====
mtime=2026-04-24 20:26:11.090116385 +0200 size=672 inode=213659520
BASELINE_INODE=213659520 BASELINE_SIZE=672 BASELINE_MTIME=1777055171
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-includes/theme-compat/error_log =====
mtime=2026-04-24 20:25:14.077589035 +0200 size=5834 inode=213659517
BASELINE_INODE=213659517 BASELINE_SIZE=5834 BASELINE_MTIME=1777055114
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/smart-elektrotechnik.net/wp-includes/widgets/error_log =====
mtime=2026-08-16 22:22:56.473230954 +0200 size=45041 inode=213654427
BASELINE_INODE=213654427 BASELINE_SIZE=45041 BASELINE_MTIME=1786911776
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/sprintertravel.rs/error_log =====
mtime=2026-08-05 00:00:37.522418930 +0200 size=0 inode=212881491
BASELINE_INODE=212881491 BASELINE_SIZE=0 BASELINE_MTIME=1785880837
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/sprintertravel.rs/wp-includes/error_log =====
mtime=2025-06-01 14:59:10.750201339 +0200 size=26752 inode=213664347
BASELINE_INODE=213664347 BASELINE_SIZE=26752 BASELINE_MTIME=1748782750
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/sprintertravel.rs/wp-includes/widgets/error_log =====
mtime=2025-06-01 14:59:18.407264329 +0200 size=6842 inode=213664352
BASELINE_INODE=213664352 BASELINE_SIZE=6842 BASELINE_MTIME=1748782758
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/turkovic.netovi/error_log =====
mtime=2026-08-05 00:00:37.675420503 +0200 size=0 inode=212881517
BASELINE_INODE=212881517 BASELINE_SIZE=0 BASELINE_MTIME=1785880837
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/turkovic.netovi/wp-admin/error_log =====
mtime=2025-06-10 09:18:21.753495178 +0200 size=2550 inode=213663677
BASELINE_INODE=213663677 BASELINE_SIZE=2550 BASELINE_MTIME=1749539901
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/turkovic.netovi/wp-includes/blocks/error_log =====
mtime=2024-10-19 15:54:23.236872833 +0200 size=254 inode=213732532
BASELINE_INODE=213732532 BASELINE_SIZE=254 BASELINE_MTIME=1729346063
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/turkovic.netovi/wp-includes/error_log =====
mtime=2025-05-25 12:35:50.812239955 +0200 size=54279 inode=213667333
BASELINE_INODE=213667333 BASELINE_SIZE=54279 BASELINE_MTIME=1748169350
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/turkovic.netovi/wp-includes/widgets/error_log =====
mtime=2025-05-25 12:37:17.219967070 +0200 size=14123 inode=213732873
BASELINE_INODE=213732873 BASELINE_SIZE=14123 BASELINE_MTIME=1748169437
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/zenskefarmerice.com/error_log =====
mtime=2026-08-18 16:29:05.608663715 +0200 size=2484 inode=212861240
BASELINE_INODE=212861240 BASELINE_SIZE=2484 BASELINE_MTIME=1787063345
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/zenskefarmerice.com/wp-admin/error_log =====
mtime=2026-07-30 08:35:09.456634828 +0200 size=526264 inode=213400461
BASELINE_INODE=213400461 BASELINE_SIZE=526264 BASELINE_MTIME=1785393309
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/zenskefarmerice.com/wp-content/languages/error_log =====
mtime=2026-07-02 08:26:10.722142782 +0200 size=8589 inode=213398290
BASELINE_INODE=213398290 BASELINE_SIZE=8589 BASELINE_MTIME=1782973570
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/zenskefarmerice.com/wp-includes/IXR/error_log =====
mtime=2026-06-20 03:18:09.899828342 +0200 size=636 inode=213450761
BASELINE_INODE=213450761 BASELINE_SIZE=636 BASELINE_MTIME=1781918289
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/zenskefarmerice.com/wp-includes/PHPMailer/error_log =====
mtime=2026-07-02 08:31:06.129621635 +0200 size=6080 inode=213450758
BASELINE_INODE=213450758 BASELINE_SIZE=6080 BASELINE_MTIME=1782973866
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/zenskefarmerice.com/wp-includes/blocks/error_log =====
mtime=2024-11-09 16:08:20.000000000 +0100 size=268 inode=213452006
BASELINE_INODE=213452006 BASELINE_SIZE=268 BASELINE_MTIME=1731164900
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/zenskefarmerice.com/wp-includes/error_log =====
mtime=2026-07-02 08:30:42.759174539 +0200 size=55817 inode=213400351
BASELINE_INODE=213400351 BASELINE_SIZE=55817 BASELINE_MTIME=1782973842
NO_NEW_BYTES=YES

===== LIVE DELTA /home/icaffeco/zenskefarmerice.com/wp-includes/widgets/error_log =====
mtime=2026-07-02 08:32:13.431383821 +0200 size=15248 inode=213451252
BASELINE_INODE=213451252 BASELINE_SIZE=15248 BASELINE_MTIME=1782973933
NO_NEW_BYTES=YES

--- HIGH-SIGNAL NEW ERRORS ONLY ---
120:[2026-08-18 17:57:27] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"9adaae32-01d6-4fbd-b43e-2ac0b39133b3","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
121:[2026-08-18 17:57:27] production.ERROR: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) {"request_id":"9adaae32-01d6-4fbd-b43e-2ac0b39133b3","userId":1,"exception":"[object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
123:#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
134:#11 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
135:#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
144:#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
145:#22 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
146:#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
147:#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
148:#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
191:[previous exception] [object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
193:#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
212:#19 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
213:#20 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
222:#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
223:#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
224:#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
225:#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
226:#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
269:[previous exception] [object] (ParseError(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
291:#20 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
292:#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
301:#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
302:#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
303:#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
304:#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
305:#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
348:[2026-08-18 17:57:37] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"76f6acc4-6c2a-47ca-9f14-46fc926d822b","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
349:[2026-08-18 17:57:37] production.ERROR: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) {"request_id":"76f6acc4-6c2a-47ca-9f14-46fc926d822b","userId":1,"exception":"[object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
351:#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
362:#11 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
363:#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
372:#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
373:#22 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
374:#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
375:#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
376:#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
419:[previous exception] [object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
421:#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
440:#19 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
441:#20 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
450:#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
451:#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
452:#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
453:#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
454:#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
497:[previous exception] [object] (ParseError(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
519:#20 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
520:#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
529:#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
530:#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
531:#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
532:#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
533:#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
576:[2026-08-18 17:57:38] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"a485002f-5da7-49ea-9ba0-cf88f6cce8ab","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
577:[2026-08-18 17:57:38] production.ERROR: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) {"request_id":"a485002f-5da7-49ea-9ba0-cf88f6cce8ab","userId":1,"exception":"[object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
579:#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
590:#11 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
591:#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
600:#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
601:#22 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
602:#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
603:#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
604:#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
647:[previous exception] [object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
649:#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
668:#19 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
669:#20 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
678:#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
679:#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
680:#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
681:#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
682:#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
725:[previous exception] [object] (ParseError(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
747:#20 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
748:#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
757:#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
758:#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
759:#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
760:#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
761:#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()

============================================================
6. POST-REPRODUCTION LARAVEL/VIEW STATE
============================================================
DASHBOARD_COMPILED_PATH_AFTER=/home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/d9c1fae579fd48a8c10d879a5e4e4ea9.php
No syntax errors detected in /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/d9c1fae579fd48a8c10d879a5e4e4ea9.php
DASHBOARD_COMPILED_SHA256=cbc61780a684d3fca8311c33a71f67f0f2be45af23cd84237c33e735b4ca42ea
DASHBOARD_COMPILED_MTIME_AFTER=1787068074

--- newest Laravel log tail after reproduction ---
#45 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
#46 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#47 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
#48 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#49 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#50 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#51 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#52 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#53 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#54 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#55 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#56 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#57 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#58 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#59 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
#60 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#61 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#62 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#63 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#64 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(28): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#65 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()
#66 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureRuntimeDirectories.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#67 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureRuntimeDirectories->handle()
#68 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/AttachRequestId.php(25): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#69 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AttachRequestId->handle()
#70 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#71 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()
#72 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#73 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#74 /home/icaffeco/ald1n-project/apps/cms/current/public/index.php(19): Illuminate\\Foundation\\Application->handleRequest()
#75 {main}
"}
[2026-08-18 17:57:38] production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"a485002f-5da7-49ea-9ba0-cf88f6cce8ab","section":"field_operations_stats","exception":"BadMethodCallException","message":"Call to undefined method Illuminate\\Database\\Eloquent\\Builder::operational()"}
[2026-08-18 17:57:38] production.ERROR: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) {"request_id":"a485002f-5da7-49ea-9ba0-cf88f6cce8ab","userId":1,"exception":"[object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
[stacktrace]
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
#1 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#2 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#3 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#4 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#5 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(82): Illuminate\\View\\View->render()
#6 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(40): Illuminate\\Http\\Response->setContent()
#7 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(939): Illuminate\\Http\\Response->__construct()
#8 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(906): Illuminate\\Routing\\Router::toResponse()
#9 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Routing\\Router->prepareResponse()
#10 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->{closure:Illuminate\\Routing\\Router::runRouteWithinStack():821}()
#11 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
#13 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureActiveUser.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#14 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureActiveUser->handle()
#15 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#16 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle()
#17 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#18 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle()
#19 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php(104): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#20 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery->handle()
#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#22 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
#26 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#27 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle()
#28 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle()
#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then()
#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack()
#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()
#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()
#35 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()
#36 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
#37 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#38 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
#39 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#40 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#41 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#42 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#43 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#44 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#45 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#46 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#47 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#48 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#49 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#50 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
#51 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#52 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#53 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#54 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#55 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(28): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#56 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()
#57 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureRuntimeDirectories.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#58 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureRuntimeDirectories->handle()
#59 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/AttachRequestId.php(25): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#60 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AttachRequestId->handle()
#61 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#62 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()
#63 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#64 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#65 /home/icaffeco/ald1n-project/apps/cms/current/public/index.php(19): Illuminate\\Foundation\\Application->handleRequest()
#66 {main}

[previous exception] [object] (Illuminate\\View\\ViewException(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" (View: /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php) at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
[stacktrace]
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException()
#1 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#2 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#3 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#4 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#5 /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/d9c1fae579fd48a8c10d879a5e4e4ea9.php(1238): Illuminate\\View\\View->render()
#6 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(123): require('/home/icaffeco/...')
#7 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::{closure:Illuminate\\Filesystem\\Filesystem::getRequire():120}()
#8 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire()
#9 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#10 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#11 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#13 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(82): Illuminate\\View\\View->render()
#14 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(40): Illuminate\\Http\\Response->setContent()
#15 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(939): Illuminate\\Http\\Response->__construct()
#16 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(906): Illuminate\\Routing\\Router::toResponse()
#17 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Routing\\Router->prepareResponse()
#18 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->{closure:Illuminate\\Routing\\Router::runRouteWithinStack():821}()
#19 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#20 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
#21 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureActiveUser.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#22 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureActiveUser->handle()
#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle()
#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#26 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle()
#27 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php(104): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#28 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery->handle()
#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#35 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle()
#36 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#37 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle()
#38 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#39 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then()
#40 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack()
#41 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()
#42 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()
#43 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()
#44 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
#45 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#46 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
#47 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#48 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#49 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#50 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#51 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#52 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#53 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#54 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#55 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#56 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#57 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#58 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
#59 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#60 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#61 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#62 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#63 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(28): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#64 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()
#65 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureRuntimeDirectories.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#66 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureRuntimeDirectories->handle()
#67 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/AttachRequestId.php(25): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#68 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AttachRequestId->handle()
#69 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#70 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()
#71 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#72 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#73 /home/icaffeco/ald1n-project/apps/cms/current/public/index.php(19): Illuminate\\Foundation\\Application->handleRequest()
#74 {main}

[previous exception] [object] (ParseError(code: 0): syntax error, unexpected end of file, expecting \"elseif\" or \"else\" or \"endif\" at /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/27fcc058188ef6b3d5c92ea9cd3dbb7d.php:1885)
[stacktrace]
#0 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::{closure:Illuminate\\Filesystem\\Filesystem::getRequire():120}()
#1 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire()
#2 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#3 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#4 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#5 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#6 /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/views/d9c1fae579fd48a8c10d879a5e4e4ea9.php(1238): Illuminate\\View\\View->render()
#7 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(123): require('/home/icaffeco/...')
#8 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::{closure:Illuminate\\Filesystem\\Filesystem::getRequire():120}()
#9 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire()
#10 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php(76): Illuminate\\View\\Engines\\PhpEngine->evaluatePath()
#11 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get()
#12 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(191): Illuminate\\View\\View->getContents()
#13 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/View.php(160): Illuminate\\View\\View->renderContents()
#14 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(82): Illuminate\\View\\View->render()
#15 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Response.php(40): Illuminate\\Http\\Response->setContent()
#16 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(939): Illuminate\\Http\\Response->__construct()
#17 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(906): Illuminate\\Routing\\Router::toResponse()
#18 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Routing\\Router->prepareResponse()
#19 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->{closure:Illuminate\\Routing\\Router::runRouteWithinStack():821}()
#20 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureTrackedPortalSession.php(32): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#21 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureTrackedPortalSession->handle()
#22 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureActiveUser.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#23 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureActiveUser->handle()
#24 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#25 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle()
#26 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#27 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle()
#28 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php(104): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#29 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery->handle()
#30 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#31 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle()
#32 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(120): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#33 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest()
#34 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Session\\Middleware\\StartSession->handle()
#35 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#36 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle()
#37 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#38 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle()
#39 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#40 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then()
#41 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack()
#42 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()
#43 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()
#44 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()
#45 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():197}()
#46 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/SecurityHeaders.php(15): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():178}()
#47 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SecurityHeaders->handle()
#48 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#49 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#50 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#51 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#52 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#53 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#54 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#55 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#56 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#57 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#58 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#59 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()
#60 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#61 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#62 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#63 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#64 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(28): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#65 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()
#66 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/EnsureRuntimeDirectories.php(52): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#67 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\EnsureRuntimeDirectories->handle()
#68 /home/icaffeco/ald1n-project/apps/cms/current/app/Http/Middleware/AttachRequestId.php(25): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#69 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AttachRequestId->handle()
#70 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():194}:195}()
#71 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()
#72 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#73 /home/icaffeco/ald1n-project/apps/cms/current/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#74 /home/icaffeco/ald1n-project/apps/cms/current/public/index.php(19): Illuminate\\Foundation\\Application->handleRequest()
#75 {main}
"}

============================================================
7. DIAGNOSTIC SUMMARY
============================================================
FRESH_HIGH_SIGNAL_ERROR_MATCHES=15
FRESH_EXCEPTION_MATCHES=18
PERSISTENT_SOURCE_MUTATION=NO
DATABASE_MUTATION=NO
CACHE_MUTATION=NO
EPHEMERAL_WEB_PROBE_LEFT_BEHIND=NO
DIAGNOSTIC_BATCH3=COMPLETE

IMPORTANT_INTERPRETATION:
- V4 PASS dokazuje samo CLI/view-cache stanje; ne dokazuje da realni browser/FPM request koristi isti runtime.
- Ovaj Batch 3 hvata samo nove log bajtove nastale tokom ručne reprodukcije 500 greške i poredi realni Web SAPI sa CLI putanjom.
- Ako Laravel delta ostane prazna, a webserver/FPM log dobije grešku, problem je pre Laravel exception handler-a.
- Ako oba ostanu prazna, sledeći fokus je vhost/proxy/CDN/document-root mismatch, što Web SAPI probe i headeri treba da pokažu.
