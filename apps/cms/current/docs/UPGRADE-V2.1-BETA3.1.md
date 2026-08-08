# Upgrade v2.1.0-beta3 → v2.1.0-beta3.1

## Razlog hotfix-a

Migracija `2026_07_23_000012_create_payments_advanced_inventory_beta3.php` sadržala je globalni `use Throwable;`. PHP ga prijavljuje kao upozorenje „The use statement with non-compound name 'Throwable' has no effect“. Na serveru koji upozorenja pretvara u exception, Laravel prekida `migrate` pre nego što kreira payment i inventory šemu.

Beta3.1 uklanja taj import i koristi `catch (\Throwable)`. Poslovna logika migracije nije promenjena.

## Pre početka

1. Napraviti backup Laravel baze `icaffeco_lrvl`, aplikacije i produkcionog `.env` fajla.
2. Potvrditi da je instalirana verzija `v2.1.0-beta3`.
3. Ne brisati `.env`, `storage/app/public`, `storage/app/payment-proofs`, `public/storage` niti postojeće upload fajlove.
4. Legacy konekcija ostaje read-only, Redis ostaje isključen.

## Raspakivanje

Raspakovati `ald1n-cms-laravel-v2.1.0-beta3.1-upgrade-from-v2.1.0-beta3.zip` preko postojeće beta3 instalacije.

## Komande

```bash
cd /home/icaffeco/cms.ald1n.com

composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr

php artisan optimize:clear

php -l database/migrations/2026_07_23_000012_create_payments_advanced_inventory_beta3.php
php artisan app:payments-inventory-doctor --repair --render
php artisan app:deployment-check --repair

php artisan app:operations-doctor --render
php artisan app:reports-doctor --render
php artisan app:auth-doctor Ald1n --render-dashboard

php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan app:deployment-check
php artisan optimize
```

## Očekivani rezultat

Direktan lint migracije ne sme prikazati warning, a doctor treba da završi sa:

```text
PASS Payments & Advanced Inventory šema je kompletna.
PASS Beta3 dozvole postoje.
PASS Uplate, lager i idempotency upiti su uspešni.
PASS Napredni lager i povezani authenticated layout su uspešno renderovani.
```

Ako je prethodni pokušaj stao pre izvršenja migracije, Laravel će sada normalno izvršiti `000012`. Ako je šema delimično napravljena, idempotentne provere u migraciji i doctor repair toku dopuniće ono što nedostaje.

## Rollback

Hotfix menja samo kod migracije i build provere. Ne briše podatke. Za potpuni rollback vratiti backup aplikacije; bazu nije potrebno vraćati ako je migracija uspešno završena.
