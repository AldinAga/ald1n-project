# Upgrade v2.1.0-beta2 → v2.1.0-beta3

## Pre početka

1. Napraviti backup Laravel baze `icaffeco_lrvl`, aplikacije, produkcionog `.env` fajla i upload sadržaja.
2. Potvrditi da je instalirana verzija `v2.1.0-beta2`.
3. Ne prepisivati `.env`, `storage/app/public`, postojeće `storage/app/payment-proofs` podatke niti `public/storage`.
4. Legacy konekcija mora ostati read-only, a Redis isključen.

## Raspakivanje

Raspakovati `ald1n-cms-laravel-v2.1.0-beta3-upgrade-from-v2.1.0-beta2.zip` preko postojeće aplikacije.

## Komande

```bash
cd /home/icaffeco/cms.ald1n.com

mkdir -p storage/app/payment-proofs \
    storage/framework/sessions \
    storage/framework/cache/data \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chmod -R ug+rwX storage bootstrap/cache

composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr

php artisan optimize:clear
php artisan app:payments-inventory-doctor --repair --render
php artisan app:operations-doctor --render
php artisan app:reports-doctor --render
php artisan app:deployment-check --repair
php artisan app:auth-doctor Ald1n --render-dashboard

php artisan legacy:check
php artisan app:version
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan app:deployment-check
php artisan optimize
```

## Očekivani doctor rezultat

```text
PASS Payments & Advanced Inventory šema je kompletna.
PASS Beta3 dozvole postoje.
PASS Uplate, lager i idempotency upiti su uspešni.
PASS Napredni lager i povezani authenticated layout su uspešno renderovani.
```

## Posle deploya

- napraviti test porudžbinu sa uplatom na račun;
- poslati PDF/JPG potvrdu iz korisničkog naloga;
- verifikovati uplatu kao dodeljeni Administrator;
- proveriti promenu salda i timeline;
- proknjižiti test ulaz robe i potvrditi da ponovljen isti idempotency ključ ne duplira stanje;
- zaključiti test popis i proveriti stock movement;
- otvoriti `/admin/reports` i `/admin/inventory`;
- proveriti CSV izvoze.

## Rollback

Migracija je nedestruktivna i `down()` namerno ne briše finansijsku niti inventurnu istoriju. Za potpuni rollback vratiti backup aplikacije i baze napravljen pre deploya.
