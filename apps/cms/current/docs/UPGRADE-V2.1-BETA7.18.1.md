# Nadogradnja na v2.1.0-beta7.18.1

Ovaj hotfix rešava Error 500 na stranicama **Dodaj artikal** i **Izmeni artikal** uveden u beta7.18.

## Uzrok

Zajednička metoda `ProductController::formView()` sadržala je kopirani deo podataka namenjen samo administratorskoj listi artikala:

- pozivala je promenljivu `$specificationFilters` koja nije bila argument metode niti lokalno definisana;
- prosleđivala je ključ `types` dva puta.

PHP je zato prekidao renderovanje forme pre prikaza stranice.

## Ispravka

- uklonjen je nedostupan poziv `$specificationFilters->fields()` iz forme;
- uklonjen je duplirani `types` ključ;
- forma sada dobija samo jedan, kompletno eager-loadovan skup tipova sa poljima, opcijama i roditeljskim korelacijama;
- potvrđeno je da isti `formView()` bezbedno koriste i kreiranje i izmena artikla;
- pregledane su administratorska lista artikala, javni katalog i šifarnik specifikacija;
- dodat je `bin/catalog-page-smoke.php` i PHPUnit contract test za view payload i korelisane stranice.

## Postavljanje

Paket se postavlja preko v2.1.0-beta7.18. Nema nove migracije baze.

```bash
php artisan optimize:clear
php artisan view:clear
php bin/catalog-page-smoke.php
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

Ako beta7.18 migracija još nije izvršena, pre ovih provera pokrenuti:

```bash
php artisan migrate --force
php artisan app:catalog-correlations-doctor
```

Nakon postavljanja uraditi hard refresh administratorske stranice (`Ctrl+F5`).
