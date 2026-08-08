# Nadogradnja na v2.1.0-beta7.20

## Polazna verzija

Ovaj UPGRADE paket se primenjuje preko **v2.1.0-beta7.19**. Za drugu ili nepoznatu verziju koristi FULL paket.

## Backup

Pre raspakivanja napravi backup baze, `.env` fajla, `storage/app` i postojeće aplikacije.

## Komande

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force
php artisan app:deployment-check
php artisan app:catalog-correlations-doctor
php artisan app:smart-products-doctor
php artisan app:product-variants-doctor
php artisan app:payments-inventory-doctor
php artisan app:after-sales-doctor
php artisan app:warranties-doctor
php artisan app:automation-doctor
php bin/catalog-page-smoke.php
php bin/smart-product-smoke.php
php bin/product-variant-smoke.php
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

## Nova migracija

`2026_07_31_000029_create_product_variants_beta7_20.php`

Migracija je ponovljiva i dodaje nedostajuće kolone pojedinačno, pa može nastaviti nakon delimičnog MariaDB DDL izvršenja. Ne briše postojeće proizvode, porudžbine, slike, dokumente ni lager istoriju.

## Recovery

```bash
php artisan app:product-variants-doctor --repair
```

## Važna pravila

- roditeljski artikal sa varijantama ima zbirni lager koji se ne menja ručno;
- ulaz robe i popis za takav artikal rade se na konkretnoj varijanti;
- svaka porudžbina čuva snapshot izabrane varijante;
- varijantu sa lagerom većim od nule nije moguće arhivirati;
- klonirane varijante uvek počinju sa lagerom 0 i statusom Nacrt.
