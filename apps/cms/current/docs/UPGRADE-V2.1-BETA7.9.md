# Upgrade na v2.1.0-beta7.9

Ova verzija ispravlja HTTP 500 pri kliku na **Izdaj otpremnicu**.

## Uzrok

Starije migracije su kreirale `order_documents.document_type` kao MySQL ENUM sa vrednostima:

- `order_confirmation`;
- `proforma`;
- `invoice`.

Nova otpremnica koristi vrednost `delivery_note`, pa MySQL odbija INSERT. Beta7.9 konvertuje kolonu u `VARCHAR(40)`, dok aplikacija i dalje validira dozvoljene tipove.

## Postavljanje

1. Napravi backup baze i aplikacije.
2. Postavi beta7.9 preko beta7.8.
3. Pokreni:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan migrate --force
php artisan app:deployment-check
php artisan app:payments-inventory-doctor
php bin/delivery-note-smoke.php
php artisan optimize
```

## Očekivani rezultat

Doctor komanda mora ispisati:

```text
PASS Tip dokumenta podržava PDF otpremnicu.
```

Nakon toga kompletirana porudžbina sa evidentiranom isporukom može dobiti dokument `OTP-YYYY-000001`.

## Napomena

Migracija nema destruktivan `down()` jer bi vraćanje na stari ENUM oštetilo postojeće `delivery_note` zapise.
