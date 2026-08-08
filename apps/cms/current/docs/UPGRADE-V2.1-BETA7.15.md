# Upgrade na v2.1.0-beta7.15

Ova verzija uvodi garantne listove, serijske brojeve i preventivno održavanje.

## Obavezna migracija

```text
2026_07_30_000024_create_warranties_preventive_maintenance_beta7_15.php
```

Migracija nedestruktivno kreira:

- `warranty_rules`;
- `product_warranties`;
- `warranty_maintenance_records`;
- dozvole `warranties.view_own` i `warranties.manage`;
- podrazumevano globalno pravilo od 24 meseca ako nijedno pravilo ne postoji.

## Pravila garancije

Pravila mogu važiti:

1. za konkretan proizvod;
2. za kategoriju;
3. globalno.

Specifičnije pravilo ima prioritet, a unutar istog nivoa koristi se veći `priority`. Već izdati garantni list čuva snapshot i kasnija izmena pravila ga ne menja.

## Automatsko izdavanje

Nakon kompletiranja isporuke sistem izdaje po jedan garantni list za svaku stavku porudžbine koja ima odgovarajuće aktivno pravilo. Broj je u formatu `GAR-YYYY-######`.

Izdavanje je idempotentno preko jedinstvene veze sa `order_item_id`, pa ponovljen zahtev ne može napraviti duplikat. Greška opcionog izdavanja ne poništava uspešno kompletiranje porudžbine i ostaje zabeležena u logu.

## Ranije kompletirane porudžbine

Za backfill pokrenite:

```bash
php artisan app:warranties-backfill --limit=5000
```

Komanda bira samo porudžbine koje imaju najmanje jednu stavku bez garancije. Može se bezbedno ponavljati.

## Preventivno održavanje

Kada pravilo ima servisni interval, kreira se prvi rok održavanja. Administrator može zakazati i završiti pregled, uneti referencu i rezultat, a sistem izračunava sledeći rok do isteka garancije.

## Komande

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force
php artisan app:deployment-check
php artisan app:warranties-doctor --backfill
php artisan app:automation-doctor
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php bin/warranty-pdf-smoke.php
php artisan optimize
```
