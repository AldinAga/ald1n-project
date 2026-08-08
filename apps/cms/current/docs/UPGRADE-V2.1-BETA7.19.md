# Upgrade na v2.1.0-beta7.19 — Smart Product Management

Polazna verzija: **v2.1.0-beta7.18.1**.

## Novine

- pametni šabloni po tipu artikla;
- obavezna osnovna i specifikaciona polja;
- podrazumevane vrednosti i redosled specifikacija;
- ponderisana kompletnost artikla i minimalni prag za objavu;
- automatsko formiranje naziva preko placeholdera;
- kloniranje proizvoda uz novi SKU, nulti lager i izbor sadržaja koji se kopira;
- bulk centar sa pregledom promena pre izvršenja;
- masovna promena statusa, tipa, brenda, linije, kategorija, cena i specifikacija;
- automatsko čišćenje nevažećih linija i specifikacija;
- backfill kompletnosti za postojeći katalog;
- doctor i dependency-free smoke testovi.

## Migracija

```text
2026_07_31_000028_create_smart_product_management_beta7_19.php
```

Migracija je ponovljiva i pripremljena za MariaDB 10.11 delimična DDL izvršenja. Ne briše proizvode, SKU vrednosti, slike, lager, dokumente niti istoriju prodaje.

## Instalacija

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr

php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force

php artisan app:deployment-check
php artisan app:catalog-correlations-doctor
php artisan app:smart-products-doctor

php bin/catalog-page-smoke.php
php bin/smart-product-smoke.php
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php

php artisan optimize
```

Za oporavak nepotpune migracije i ponovni obračun kompletnosti:

```bash
php artisan app:smart-products-doctor --repair
```

## Pravila kloniranja

Klonirani proizvod uvek dobija novi SKU, status `draft`, lager 0 i vezu ka izvornom proizvodu. Rezervacije, serijski brojevi, prodaja i istorija se nikada ne kopiraju. Slike, opis, specifikacije, kategorije, cena i garancijska pravila kopiraju se samo ako su označeni.

## Bulk bezbednost

Bulk operacija zahteva pregled promena pre izvršenja i podržava najviše 500 proizvoda u jednom zahvatu. Promena tipa uklanja specifikacije koje više ne pripadaju novom šablonu. Promena brenda bez nove linije briše staru liniju ako nije kompatibilna.
