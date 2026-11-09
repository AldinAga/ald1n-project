# Report1107 - ALD1N post-install verification v4 (read-only)

KIND=POST_INSTALL_VERIFY_READONLY
PREPARED_BY=Claude (Anthropic) for owner, 2026-11-08
DATE=2026-11-08 11:24:19 CET
SCRIPT=/home/icaffeco/ald1n-project/incoming/ald1n-post-install-verify-v4.sh
SCRIPT_SHA256=50659ee2584a4012d04fb6b63c20843a7f9f043a7780294978771e9d62b6cc40
REPORT_NUMBER_BASIS=github_main_max=1103 local_max=1106 next=1107
MUTATION_POLICY=READ_ONLY; only this report file is written

## 1. Versions and security advisories


$ laravel/framework, guzzle, commonmark in host composer.lock
```
           
"name": "guzzlehttp/guzzle",             "version": "7.16.1",            
"name": "laravel/framework",             "version": "v13.40.0",            
"name": "league/commonmark",             "version": "2.10.7",            
```
(truncated: 6 lines total, shown 4)
RC=0

$ artisan --version
```
Laravel Framework 13.40.0
```
RC=0

$ composer audit --locked (summary lines only)
```
No security vulnerability advisories found.
```
RC=0

## 2. Release layout and private-data links (names only)


$ current release and retained original
```
drwxr-xr-x 14 icaffeco icaffeco 4096 Nov  6 08:24 /home/icaffeco/ald1n-project/apps/cms/current
drwxr-x--- 3 icaffeco icaffeco 4096 Nov  6 08:24 /home/icaffeco/.ald1n-b1134-retained
99111dcf09304b97aac05be0ef6ee1ee
```
RC=0

$ .env and storage links (targets, no contents)
```
lrwxrwxrwx 1 icaffeco icaffeco   83 Nov  6 08:24 .env -> /home/icaffeco/.ald1n-b1134-retained/99111dcf09304b97aac05be0ef6ee1ee/retained/.env

storage:
total 12
lrwxrwxrwx 1 icaffeco icaffeco   90 Nov  6 08:24 app -> /home/icaffeco/.ald1n-b1134-retained/99111dcf09304b97aac05be0ef6ee1ee/retained/storage/app
drwx------ 3 icaffeco icaffeco 4096 Nov  8 10:16 framework
lrwxrwxrwx 1 icaffeco icaffeco   91 Nov  6 08:24 logs -> /home/icaffeco/.ald1n-b1134-retained/99111dcf09304b97aac05be0ef6ee1ee/retained/storage/logs
.env -> /home/icaffeco/.ald1n-b1134-retained/99111dcf09304b97aac05be0ef6ee1ee/retained/.env readable=yes
storage/app -> /home/icaffeco/.ald1n-b1134-retained/99111dcf09304b97aac05be0ef6ee1ee/retained/storage/app readable=yes
storage/logs -> /home/icaffeco/.ald1n-b1134-retained/99111dcf09304b97aac05be0ef6ee1ee/retained/storage/logs readable=yes
storage/framework/sessions -> /home/icaffeco/.ald1n-b1134-retained/99111dcf09304b97aac05be0ef6ee1ee/retained/storage/framework/sessions readable=yes
```
RC=0

$ leftover probe files in public/
```
ls: cannot access '/home/icaffeco/ald1n-project/apps/cms/current/public/ald1n-b1144-*.php': No such file or directory
0
```
RC=0

$ b1144 files in incoming/ and state directories
```
11
/home/icaffeco/.ald1n-b1143-state
/home/icaffeco/.ald1n-b1144-state
```
RC=0

$ installed release has storefront code
```
-rw-r--r-- 1 icaffeco icaffeco 1402 Nov  6 08:24 /home/icaffeco/ald1n-project/apps/cms/current/config/public_storefront.php
-rw-r--r-- 1 icaffeco icaffeco  406 Nov  6 08:24 /home/icaffeco/ald1n-project/apps/cms/current/routes/public-retail-access.php
```
RC=0

## 3. Storefront state


$ config/public_storefront.php as installed (clamp values; non-secret)
```
<?php
declare(strict_types=1);
// Owned runtime-only OFF clamp; original SHA256: 0cfbc864a3d44822a9d14e80f6252e84eb208c9da5a25d5fe6989e4cad789818
$values=(static function():array {

/**
 * Deliberately OFF until the complete guest checkout and after-sales flow
 * passes MariaDB, security, merchant-policy and CloudLinux release gates.
 */
return [
    'enabled' => filter_var(env('PUBLIC_STOREFRONT_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'max_items' => 25,
    'max_quantity_per_item' => 20,
    // Unavailable until checkout, after-sales and merchant terms have passed QA.
    'checkout_enabled' => filter_var(env('PUBLIC_CHECKOUT_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'terms_url' => env('PUBLIC_CHECKOUT_TERMS_URL'),
    'privacy_url' => env('PUBLIC_CHECKOUT_PRIVACY_URL'),
    'shipping_fee_rsd' => env('PUBLIC_CHECKOUT_SHIPPING_FEE_RSD'),
    'shipping_description' => env('PUBLIC_CHECKOUT_SHIPPING_DESCRIPTION'),
    'payment_methods' => array_filter(explode(',', (string) env('PUBLIC_CHECKOUT_PAYMENT_METHODS', ''))),
    'support_email' => env('PUBLIC_CHECKOUT_SUPPORT_EMAIL'),
    'bank_account_id' => env('PUBLIC_CHECKOUT_BANK_ACCOUNT_ID'),
    'delivery_methods' => array_filter(explode(',', (string) env('PUBLIC_CHECKOUT_DELIVERY_METHODS', 'courier,own_transport,customer_pickup'))),
];

})();
$values['enabled']=false;
$values['checkout_enabled']=false;
return $values;
```
RC=0

$ storefront-related routes
```
 GET|HEAD admin/settings/public-storefront ......................................... admin.settings.public-storefront.index › Admin\PublicMerchantSettingsController@index
 PUT admin/settings/public-storefront ....................................... admin.settings.public-storefront.update › Admin\PublicMerchantSettingsController@update
 PUT admin/settings/public-storefront/registration-sender admin.settings.public-storefront.registration-sender › Admin\PublicMerchantSettingsController@updateRegist…
 POST admin/settings/public-storefront/toggle ....................................... admin.settings.public-storefront.toggle › Admin\PublicStorefrontToggleController
 GET|HEAD katalog ..................................................................................................... public-shop.catalog › PublicShopController@catalog
 GET|HEAD katalog/slika/{image} ........................................................................................... public-shop.media › PublicShopController@media
 GET|HEAD katalog/{slug} .................................................................................................... public-shop.show › PublicShopController@show
 GET|HEAD korpa ............................................................................................................. public-shop.cart › PublicShopController@cart
 POST korpa/dodaj/{product} .......................................................................................... public-shop.cart.add › PublicShopController@add
 PATCH korpa/{product} .......................................................................................... public-shop.cart.update › PublicShopController@update
 DELETE korpa/{product} .......................................................................................... public-shop.cart.remove › PublicShopController@remove
 GET|HEAD porucivanje ............................................................................................... public-shop.checkout › PublicCheckoutController@form
 POST porucivanje ....................................................................................... public-shop.checkout.submit › PublicCheckoutController@store
 GET|HEAD porudzbina/{token} ................................................................................... public-shop.order.show › PublicOrderAccessController@show
 GET|HEAD porudzbina/{token}/dokument/{document} ....................................................... public-shop.order.document › PublicOrderAccessController@document
 GET|HEAD porudzbina/{token}/garancija/{issuance} ...................................................... public-shop.order.warranty › PublicOrderAccessController@warranty
 GET|HEAD registracija ................................................................................... public-shop.register › PublicBuyerRegistrationController@create
 POST registracija ............................................................................. public-shop.register.submit › PublicBuyerRegistrationController@store
```
RC=0

$ total route count
```
548
```
RC=0

$ migration status (counts and pending names only)
```
68
  2026_10_23_020000_create_public_order_accesses ........................................................................................... Pending  
  2026_10_23_020100_create_public_checkout_attempts ........................................................................................ Pending  
  2026_10_24_030000_add_retail_customer_role ............................................................................................... Pending  
```
RC=0

## 4. Database (read-only SELECTs; setting VALUES are never printed)


$ storefront DB flag (0 or 1)
```
```
RC=0

$ public_* and registration settings: key, length of value, updated (no values)
```
+---------------------------------------+-----------+---------------------+
| setting_key                           | value_len | updated_at          |
+---------------------------------------+-----------+---------------------+
| documents_company_registration_number |         9 | 2026-08-17 11:50:36 |
+---------------------------------------+-----------+---------------------+
```
RC=0

$ roles (slug and name only)
```
+----+------------+---------------+
| id | slug       | name          |
+----+------------+---------------+
|  1 | user       | Korisnik      |
|  2 | admin      | Administrator |
|  3 | superadmin | SuperAdmin    |
+----+------------+---------------+
```
RC=0

$ users per role (counts only)
```
+------------+-------+
| slug       | users |
+------------+-------+
| admin      |     0 |
| superadmin |     1 |
| user       |    23 |
+------------+-------+
```
RC=0

$ scheduler heartbeat age and runtime states
```
+-------------------------+---------------------+---------+
| state_key               | recorded_at         | age_min |
+-------------------------+---------------------+---------+
| automation_last_success | 2026-11-08 11:10:03 |      14 |
| scheduler_heartbeat     | 2026-11-08 11:24:03 |       0 |
+-------------------------+---------------------+---------+
```
RC=0

$ queue and failures
```
+--------------+-------------+
| jobs_waiting | failed_jobs |
+--------------+-------------+
|            0 |           0 |
+--------------+-------------+
```
RC=0

$ mail outbox by status (counts)
```
+--------+-----+
| status | n   |
+--------+-----+
| sent   | 948 |
+--------+-----+
```
RC=0

$ push outbox by status (counts)
```
+-----------+-----+
| status    | n   |
+-----------+-----+
| delivered | 136 |
| failed    |   8 |
+-----------+-----+
```
RC=0

$ latest backup runs
```
+-----+-------------+-----------+---------------------+
| id  | backup_type | status    | started_at          |
+-----+-------------+-----------+---------------------+
| 187 | weekly      | completed | 2026-11-08 03:10:05 |
| 186 | daily       | completed | 2026-11-08 02:30:06 |
+-----+-------------+-----------+---------------------+
```
RC=0

## 5. Laravel log since the install (messages, masked)

LATEST_LOG=laravel-2026-11-08.log SIZE_BYTES=1276
```
      1 production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"c234681e-d0c0-4a89-993c-24034ad31856","section":"field_operations_stats","exc
      1 production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"b9b7d508-7e86-4935-8686-2f3a13321ebb","section":"field_operations_stats","exc
      1 production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"9d348e17-44fa-4e6b-b9e3-481b46691e31","section":"field_operations_stats","exc
      1 production.WARNING: Dashboard section unavailable; fallback values were used. {"request_id":"6be82e76-a57c-4d7d-9fbf-4274bd22e025","section":"field_operations_stats","exc
```
ERROR_LINES_TODAY=0 CRITICAL_PLUS=0

## 6. HTTP

GET /login -> 200
GET /up -> 200
GET /katalog -> 404
GET /registracija -> 404
GET /korpa -> 404
GET /porucivanje -> 404
GET /admin/settings/public-storefront -> 302

$ response headers for /login
```
HTTP/2 200 
x-powered-by: PHP/8.4.27
cache-control: max-age=0, no-store, private
content-security-policy: base-uri 'none'; frame-ancestors 'self'; form-action 'self'; object-src 'none'; frame-src 'self' https://challenges.cloudflare.com https://www.youtube-nocookie.com https://www.youtube.com; script-src 'nonce-2i30x3+YE+Os/LRiYqCWeQ=='; s
strict-transport-security: max-age=31536000; includeSubDomains
```
RC=0

## 7. Disk


$ space and inodes
```
/dev/sda3       7.7T  6.5T  834G  89% /home/icaffeco
/dev/sda3      257077248 44056558 213020690   18% /home/icaffeco
```
RC=0

## 8. End

MUTATIONS=REPORT_FILE_ONLY
