# Upgrade na v2.1.0-beta7.2

Ovo je završni hotfix za detaljne stranice porudžbina i proveru ključnih detail ruta.

## Ispravke

- `/admin/orders/{id}` dobija zaseban protected SQL/data/Blade render tok;
- korisnički `/orders/{id}` koristi isti bezbedni loader;
- opcione tabele i relacije više ne mogu da obore celu stranicu;
- IPS pomoćni zapis ne može da izazove HTTP 500;
- timeline preskače nedostupne audit/operativne izvore;
- `app:orders-doctor --render --order-id=1` proverava admin i korisnički detalj;
- `app:detail-pages-doctor --order-id=1` proverava porudžbine, katalog, izmenu artikla i galeriju slika.

## Deploy

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan app:orders-doctor --repair --render --order-id=1
php artisan app:detail-pages-doctor --order-id=1
php artisan app:deployment-check
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

Nova migracija baze nije potrebna.
