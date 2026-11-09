# Report1105 - ALD1N host FACTS PROBE v3 (read-only)

KIND=HOST_FACTS_PROBE_READONLY
PREPARED_BY=Claude (Anthropic) for owner, 2026-11-08
DATE=2026-11-08 02:57:26 CET
SCRIPT=/home/icaffeco/ald1n-project/incoming/ald1n-host-facts-probe-v3.sh
SCRIPT_SHA256=f9c74fdba0ad2c1882c1194ad24c30cfdea7e2f57ac4b1290f8bcf33837958aa
REPORT_NUMBER_BASIS=github_main_max=1103 local_max=1104 next=1105
MUTATION_POLICY=READ_ONLY; one ordinary GET of /login; only this report file is written

## 1. cPanel UAPI DomainInfo (the call that stopped B1143)


$ uapi binary
```
lrwxrwxrwx 1 root root 7 Dec 18  2024 /usr/local/cpanel/bin/uapi -> apitool
command -v uapi: /bin/uapi
whoami: icaffeco uid=1109
```
RC=0

UAPI_BINARY_USED=/usr/local/cpanel/bin/uapi

### UAPI variant: with_user_flag
UAPI_with_user_flag_RC=2 STDOUT_BYTES=0 STDERR_BYTES=290
[stderr first 8 lines]
```
[2026-11-08 02:57:26 +0100] die [uapi] setuids failed: Attempting to setuid as a normal user with RUID 1109
die [uapi] setuids failed: Attempting to setuid as a normal user with RUID 1109
exit level [die] [pid=2843686] (setuids failed: Attempting to setuid as a normal user with RUID 1109)
```
```
stdout_bytes=0
json_valid=false
```
(empty stdout; first 300 bytes of nothing)

### UAPI variant: without_user_flag
UAPI_without_user_flag_RC=2 STDOUT_BYTES=0 STDERR_BYTES=290
[stderr first 8 lines]
```
[2026-11-08 02:57:26 +0100] warn [uapi] Failed to execute /usr/local/cpanel/cpanel: No such file or directory
warn [uapi] Failed to execute /usr/local/cpanel/cpanel: No such file or directory
Failed to execute /usr/local/cpanel/cpanel: No such file or directory at bin/apitool.pl line 278.
```
```
stdout_bytes=0
json_valid=false
```
(empty stdout; first 300 bytes of nothing)

## 2. CMS public directory and its configuration files


$ public dir and .htaccess identity
```
directory | owner=icaffeco | links=4 | size=4096 | mode=750 | .
regular file | owner=icaffeco | links=1 | size=870 | mode=644 | .htaccess
stat: cannot statx '.user.ini': No such file or directory
```
RC=1

$ .htaccess sha256
```
d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef  /home/icaffeco/ald1n-project/apps/cms/current/public/.htaccess
```
RC=0

$ .user.ini directives that would block the uncached profile (names only)
```
.user.ini absent
```
RC=0

$ active CMS path vs symlink
```
drwxr-x---  5 icaffeco nobody 4096 Aug  8 11:28 /home/icaffeco/ald1n-project/apps/cms
drwxr-x--- 16 icaffeco nobody 4096 Oct  6 17:56 /home/icaffeco/ald1n-project/apps/cms/current
/home/icaffeco/ald1n-project/apps/cms/current
```
RC=0

## 3. Filesystem layout (an atomic release swap needs one filesystem)


$ device ids (same number = same filesystem)
```
dev=2051 /home/icaffeco/ald1n-project/apps/cms/current
dev=2051 /home/icaffeco/ald1n-project/apps/cms
dev=2051 /home/icaffeco/.ald1n-stage
dev=2051 /home/icaffeco/.ald1n-stage/b1131-ef3f927.6hh7mU
dev=2051 /home/icaffeco
```
RC=0

$ free space and inodes
```
/dev/sda3       7.7T  6.5T  811G  90% /home/icaffeco
/dev/sda3      257077248 44041540 213035708   18% /home/icaffeco
```
RC=0

$ candidate stage directory
```
drwx------ 4 icaffeco icaffeco 4096 Nov  5 00:07 /home/icaffeco/.ald1n-stage/b1131-ef3f927.6hh7mU
fetch.log
head.after
head.before
index.after
index.before
remote-reports.txt
runtime
status.after
```
RC=0
STAGE_FILES=8543 STAGE_DIRS=1184 (Report1077 recorded 8531 files / 1181 directories)
STAGE_MANIFEST_runtime-manifest.json=MATCH
STAGE_MANIFEST_runtime-directories.json=MATCH

## 4. What the host runs now (names and versions only)


$ laravel/framework in host composer.lock
```
            "name": "laravel/framework",
            "version": "v13.20.0",
            "source": {
```
RC=0

$ storefront code present on host? (OFF clamp file)
```
ls: cannot access '/home/icaffeco/ald1n-project/apps/cms/current/routes/public-retail-access.php': No such file or directory
-rw------- 1 icaffeco icaffeco 1449 Nov  3 01:34 /home/icaffeco/ald1n-project/apps/cms/current/config/public_storefront.php
```
RC=2

$ host public/index.php sha256
```
3a0d98b25997752a47c5f065c3fa05ec518170bf97b679e9339b2e17b4f25d55  /home/icaffeco/ald1n-project/apps/cms/current/public/index.php
```
RC=0

$ state and stage directories from earlier attempts (names only)
```
/home/icaffeco/.ald1n-b1134-retained
/home/icaffeco/.ald1n-b1134-state
/home/icaffeco/.ald1n-b1135-state
/home/icaffeco/.ald1n-b1136-state
/home/icaffeco/.ald1n-b1137-state
/home/icaffeco/.ald1n-b1138-IZboekQT
/home/icaffeco/.ald1n-b1139-state
/home/icaffeco/.ald1n-b1140-state
/home/icaffeco/.ald1n-b1141-state
/home/icaffeco/.ald1n-b1142-state
/home/icaffeco/.ald1n-b1143-state
/home/icaffeco/.ald1n-stage
```
RC=0

$ state directory permissions (no contents)
```
700 icaffeco /home/icaffeco/.ald1n-b1143-state
```
RC=0

$ operator packet files present in incoming/
```
-rw-r--r-- 1 icaffeco icaffeco  7856 Nov  6 08:22 /home/icaffeco/ald1n-project/incoming/b1134-scheduler.php
-rw-r--r-- 1 icaffeco icaffeco  3591 Nov  8 02:34 /home/icaffeco/ald1n-project/incoming/b1143-README.md
-rw-r--r-- 1 icaffeco icaffeco  1912 Nov  8 02:34 /home/icaffeco/ald1n-project/incoming/b1143-controlled-install.php
-rw-r--r-- 1 icaffeco icaffeco  5124 Nov  8 02:34 /home/icaffeco/ald1n-project/incoming/b1143-controlled-install.sh
-rw-r--r-- 1 icaffeco icaffeco 26920 Nov  8 02:34 /home/icaffeco/ald1n-project/incoming/b1143-coordinator.php
-rw-r--r-- 1 icaffeco icaffeco  1445 Nov  8 02:34 /home/icaffeco/ald1n-project/incoming/b1143-package.json
-rw-r--r-- 1 icaffeco icaffeco   853 Nov  8 02:34 /home/icaffeco/ald1n-project/incoming/b1143-package.sha256
-rw-r--r-- 1 icaffeco icaffeco  3636 Nov  8 02:34 /home/icaffeco/ald1n-project/incoming/b1143-provenance.json
-rw-r--r-- 1 icaffeco icaffeco 27564 Nov  8 02:34 /home/icaffeco/ald1n-project/incoming/b1143-recovery.php
-rw-r--r-- 1 icaffeco icaffeco  8068 Nov  8 02:34 /home/icaffeco/ald1n-project/incoming/b1143-uncached-profile.php
-rw-r--r-- 1 icaffeco icaffeco 21881 Nov  8 02:34 /home/icaffeco/ald1n-project/incoming/b1143-web-client.php
-rw-r--r-- 1 icaffeco icaffeco 12353 Nov  8 02:34 /home/icaffeco/ald1n-project/incoming/b1143-webcache-6065a4d3739fa956ab90c00213761c3fb3d022ae97c4ce67c9249a77d7beb0d3.php
```
RC=0

$ PHP used by the installer
```
PHP 8.4.27 (cli) (built: Oct 24 2026 00:00:00) (NTS)
Copyright (c) The PHP Group
Zend Engine v4.4.27, Copyright (c) Zend Technologies
    with Zend OPcache v8.4.27, Copyright (c), by Zend Technologies
```
RC=0

## 5. LiteSpeed lsphp workers during one ordinary GET of /login


$ own lsphp processes BEFORE the request
```
2842223 2834198       00:42 lsphp           lsphp
```
RC=0
REQUEST_RESULT=HTTP_STATUS=200

Per-worker summary over 48 samples at 0.25 s (first and last sample where each pid was seen):
```
pid=2842223 ppid=2834198 first_sample=0 last_sample=47 args_len=5 args=lsphp
pid=2844124 ppid=2842223 first_sample=1 last_sample=47 args_len=50 args=lsphp:1n-project/apps/cms/current/public/index.php
pid=2844598 ppid=2842223 first_sample=47 last_sample=47 args_len=49 args=lsphp:/home/icaffeco/adalyatobacco.rs/wp-cron.php
```
SAMPLES_WITH_AT_LEAST_ONE_LSPHP=48 of 48

$ own lsphp processes AFTER the request
```
2842223 2834198       00:54 lsphp           lsphp
2844124 2842223       00:12 lsphp           lsphp
2844598 2842223       00:00 lsphp           lsphp:/home/icaffeco/adalyatobacco.rs/wp-cron.php
```
RC=0

## 6. HTTP basics


$ response headers for /login (cookies masked)
```
HTTP/2 200 
x-powered-by: PHP/8.4.27
cache-control: no-store, private
set-cookie: XSRF-TOKEN=<masked>; expires=Sun, 08 Nov 2026 03:57:40 GMT; Max-Age=7200; path=/; domain=cms.ald1n.com; secure; samesite=lax
set-cookie: ald1n-cms-session=<masked>; expires=Sun, 08 Nov 2026 03:57:40 GMT; Max-Age=7200; path=/; domain=cms.ald1n.com; secure; httponly; samesite=lax
server: cloudflare
```
RC=0
GET /katalog -> 404 (expected 404 while the Storefront is not installed or is OFF)
GET /registracija -> 404 (expected 404 while the Storefront is not installed or is OFF)
GET /korpa -> 404 (expected 404 while the Storefront is not installed or is OFF)

## 7. End

MUTATIONS=REPORT_FILE_ONLY (plus one ordinary GET of /login and three GETs of public paths)
NEXT=Claude reads this report and proposes the smallest safe install step. No installer is run from this probe.
