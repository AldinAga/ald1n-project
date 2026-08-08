# Prelazak na v2.1.4.1

Polazna verzija: **v2.1.4**.

## 1. Backup

```bash
cd /home/icaffeco/cms.ald1n.com
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
```

## 2. Raspakivanje

Raspakuj UPGRADE paket preko postojeće instalacije. Ne briši `.env`, `storage` ni korisničke fotografije.

## 3. Komande

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr
/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan view:clear
/usr/local/bin/php artisan route:clear
/usr/local/bin/php artisan migrate --force
/usr/local/bin/php bin/product-type-page-render-hotfix-smoke.php
/usr/local/bin/php artisan app:cms-v2-1-4-doctor --repair
```

Nema nove migracije; `migrate --force` samo potvrđuje stanje.

## 4. Ručna provera

Otvori:

- `/admin/catalog-settings/product-type/desktop-racunar`
- `/admin/catalog-settings/product-type/laptop`
- još najmanje jedan postojeći tip proizvoda

Proveri prikaz, Drag & Drop i čuvanje specifikacija.

## 5. Stable potvrda

```bash
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
/usr/local/bin/php artisan app:release-check --profile=stable --render --strict --snapshot --report=v2.1.4.1-stable-acceptance.json
/usr/local/bin/php artisan optimize
```

Očekivano: `RELEASE CHECK: STABLE READY`.
