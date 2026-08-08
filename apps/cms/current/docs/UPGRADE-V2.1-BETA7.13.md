# Upgrade na v2.1.0-beta7.13

Ova verzija uvodi servisni lager rezervnih delova, rezervacije po terenskom radnom nalogu i kontrolisanu nabavku.

## Nova migracija

```text
2026_07_30_000022_create_service_parts_procurement_beta7_13.php
```

Migracija kreira:

- `service_part_suppliers`;
- `service_parts`;
- `field_work_order_parts`;
- `service_part_movements`;
- `service_part_purchase_requests`;
- `service_part_purchase_request_items`;
- dozvole `service_parts.view`, `service_parts.manage` i `service_parts.procurement`.

Migracija je nedestruktivna i ne menja postojeće porudžbine, uplate, dokumente, glavni lager ili radne naloge.

## Obavezne komande

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force
php artisan app:deployment-check
php artisan app:service-parts-doctor
php artisan app:field-operations-doctor
php artisan app:automation-doctor
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

## Poslovno pravilo

Fizičko stanje servisnog dela ne umanjuje se prilikom planiranja intervencije. Najpre se povećava rezervisana količina. Pri završetku terenskog naloga umanjuje se fizičko stanje samo za stvarni utrošak, a neiskorišćena rezervacija se oslobađa. Nizak lager se računa prema raspoloživoj količini, odnosno fizičkom stanju umanjenom za aktivne rezervacije. Početno stanje novog dela odmah dobija `opening_balance` zapis u ledgeru.
