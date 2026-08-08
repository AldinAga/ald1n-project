# Upgrade Alpha3.1 → v2.0.0-beta1

## 1. Backup

Napraviti backup Laravel baze `icaffeco_lrvl`, aplikacionog foldera i `.env` fajla. Legacy baza `icaffeco_cms` se ne menja.

## 2. Maintenance mode

```bash
php artisan down --retry=60
```

## 3. Raspakivanje Upgrade ZIP-a

Raspakovati sadržaj u Laravel root. Ne prepisivati produkcioni `.env` fajl. Pregledati `UPGRADE-FILES.txt` i `DELETE-ON-UPGRADE.txt`.

## 4. Composer i cache

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
```

## 5. Obavezna migracija i dozvole

```bash
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\CoreAccessSeeder --force
```

Migracija postojeće porudžbine označava kao `source_system=legacy` i `inventory_state=none`. Samo nove Laravel porudžbine rezervišu i vraćaju lager.

## 6. Redis ostaje isključen

U `.env` mora ostati:

```env
APP_ENV=production
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Ne dodavati Redis vrednosti iz ranijih Alpha dokumenata.

## 7. Legacy read-only

Legacy korisnik mora imati samo read privilegije. Provera:

```bash
php artisan legacy:check
```

Aplikacija dodatno koristi `SET SESSION TRANSACTION READ ONLY` i SQL guard koji blokira mutirajuće naredbe pre izvršavanja.

## 8. Validacija

```bash
php bin/php-lint.php
php bin/autoload-check.php
php artisan test --testsuite=Feature
php bin/static-check.php
php artisan app:deployment-check
```

## 9. Aktivacija

```bash
php artisan optimize
php artisan up
```

Prva kontrola u UI: kreirati test porudžbinu za RSD artikal, proveriti jedno `sale` kretanje, zatim otkazati i proveriti jedno `cancelled_order` kretanje i vraćenu količinu.
