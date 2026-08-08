# Upgrade na v2.1.0-beta7.11

Ova verzija uvodi kontrolisano izvršenje odluka iz reklamacija, povrata i servisnih slučajeva.

## Nova migracija

```text
2026_07_30_000020_create_after_sales_actions_beta7_11.php
```

Migracija:

- kreira `after_sales_actions`;
- kreira `after_sales_action_items`;
- dodaje `order_payments.after_sales_action_id` radi idempotentne refundacije;
- dodaje dozvolu `after_sales.execute`.

Migracija je nedestruktivna i ne menja postojeće porudžbine, uplate, lager ili postprodajne slučajeve.

## Deploy

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force
php artisan app:deployment-check
php artisan app:after-sales-doctor
php artisan app:payments-inventory-doctor
php artisan app:automation-doctor
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

## Novi tok

1. Postprodajni slučaj se obrađuje i odobrava.
2. Administrator planira servisnu posetu, zamensku isporuku, prijem vraćene robe ili refundaciju.
3. Radnja dobija `PRA-YYYYMMDD-NNNNNN` broj, odgovorno lice, termin i rok.
4. Radnja se pokreće, izvršava ili otkazuje uz audit trag.
5. Automatski lager koristi jedinstveni stock event i ne može se knjižiti dvaput.
6. Refundacija kreira jednu verifikovanu finansijsku stavku povezanu sa radnjom i ne može premašiti neto uplaćeni iznos.
7. Slučaj se ne može označiti kao rešen dok postoje aktivne radnje; za odluke koje zahtevaju izvršenje mora postojati završena radnja.

Za istorijske ili spoljne sisteme može se izabrati **Eksterno / bez automatske promene lagera**.
