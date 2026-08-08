# Upgrade v2.0.0-alpha1 -> v2.0.0-alpha2

## 1. Backup

Sačuvaj novu Laravel bazu, projekat i serverski `.env`. Stari CMS se ne menja.

## 2. Kopiranje

Raspakuj upgrade paket preko `/home/icaffeco/cms.ald1n.com`. Paket ne sadrži `.env`, `vendor`, uploadove, logove ni cache.

## 3. Komande

```bash
cd /home/icaffeco/cms.ald1n.com
composer install
php artisan app:alpha2-cleanup --force
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=CoreAccessSeeder --force
php artisan storage:link
php artisan legacy:database-info
php artisan legacy:catalog-diff
php artisan legacy:catalog-sync --dry-run
composer test
php bin/static-check.php
php artisan app:deployment-check
```

Tek posle pregleda dry-run rezultata:

```bash
php artisan legacy:catalog-sync --apply
```

Produkcijska optimizacija:

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize
```

## Rollback

Vrati backup alpha1 fajlova i Laravel baze. Migracija `2026_07_22_000004_create_catalog_admin_tables.php` može se vratiti komandom `php artisan migrate:rollback --step=1`, ali je sigurniji kompletan DB backup.
