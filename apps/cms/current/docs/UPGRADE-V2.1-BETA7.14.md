# Upgrade na v2.1.0-beta7.14

Ova verzija ispravlja životni ciklus predračuna, računa i otpremnica nakon storniranja.

## Uzrok problema

Starije migracije su postavljale jedinstveni indeks nad kolonama `order_id` i `document_type`. Servis je istovremeno vraćao prvi pronađeni dokument istog tipa bez obzira na status. Zbog toga stornirani predračun ili račun nije mogao dobiti novu verziju.

## Nova migracija

```text
2026_07_30_000023_enable_document_revisions_beta7_14.php
```

Migracija je nedestruktivna i:

- uklanja unique indeks `order_id + document_type` bez brisanja dokumenata;
- dodaje `revision_number`;
- dodaje `supersedes_document_id`;
- dodaje `cancellation_reason`;
- numeriše postojeće dokumente hronološkim redom;
- dodaje indeks za aktivan dokument po porudžbini i tipu.

## Novo ponašanje

- ako postoji aktivan dokument, ponovni klik otvara isti dokument i ne pravi duplikat;
- ako je poslednji dokument storniran, izdaje se nova revizija sa novim poslovnim brojem;
- nova revizija čuva vezu ka dokumentu koji menja;
- razlog storniranja je obavezan;
- korisnik vidi samo aktivnu reviziju;
- administrator vidi kompletnu istoriju, razlog storniranja i vezu između revizija;
- izdavanje odmah otvara PDF u novom tabu.

## Komande

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan migrate --force
php artisan app:deployment-check
php artisan app:payments-inventory-doctor
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```
