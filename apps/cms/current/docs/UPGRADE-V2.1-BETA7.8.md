# Upgrade na v2.1.0-beta7.8

Ova verzija uvodi evidenciju isporuke, privatni dokaz isporuke, PDF otpremnicu i kontrolisano ponovno otvaranje kompletirane porudžbine.

## Pre nadogradnje

1. Napravi backup baze i `storage/app` direktorijuma.
2. Sačuvaj postojeći `.env` i ne prepisuj ga sadržajem paketa.
3. Postavi fajlove verzije beta7.8 preko beta7.7.

## Obavezne komande

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\CoreAccessSeeder --force
php artisan app:deployment-check
php artisan app:orders-doctor --render
php artisan app:operations-doctor
php artisan app:payments-inventory-doctor
php artisan optimize
```

## Šta migracija dodaje

- `orders.reopened_at`, `orders.reopened_by`, `orders.reopen_reason`;
- tabelu `order_deliveries`;
- delivery snapshot kolone na `order_documents`;
- dozvole `orders.confirm_delivery` i `orders.reopen`.

## Novi tok kompletiranja

Administrator prilikom kompletiranja evidentira način i datum isporuke, primaoca i opciono dokaz. Kod pouzeća se kao i ranije automatski evidentira samo preostali saldo. Nakon kompletiranja može se izdati otpremnica.

SuperAdministrator može ponovo otvoriti porudžbinu samo uz obavezan razlog. Uplate i istorijski dokaz isporuke ostaju sačuvani.

## Storage

Dokazi se čuvaju privatno na `local` disku u `storage/app/private` ili efektivnoj local putanji. Nemoj ih izlagati kroz `public/storage`.
