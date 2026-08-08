# Upgrade na v2.1.1 Stable Maintenance

## Polazna verzija

Upgrade paket je namenjen instalaciji preko potvrđene verzije `v2.1.0`.

## Najvažnije promene

- popravljen `/order/new`;
- `/catalog` objedinjuje pregled i administraciju artikala;
- Administrator u katalogu vidi i uređuje samo artikle koje je kreirao;
- SuperAdministrator uređuje sve artikle;
- `/admin/catalog` preusmerava na `/catalog`;
- filter paneli imaju Expand/Collapse;
- žiro računi su kompaktniji, po dve kartice u desktop redu;
- uvedene su `app:order-create-doctor` i `app:catalog-ownership-doctor`.

## Važno o vlasništvu

Ownership se određuje kolonom `products.created_by`. Administrator u jedinstvenom katalogu ne vidi tuđe artikle. Artikle bez vlasnika vidi i menja samo SuperAdministrator. Sistem ne pokušava da nagađa vlasnika istorijskih artikala.

## Postavljanje

```bash
cd /home/icaffeco/cms.ald1n.com
/usr/local/bin/php artisan app:backup-create --type=manual

# raspakuj UPGRADE paket preko instalacije
composer install --no-dev --optimize-autoloader
/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan migrate --force

/usr/local/bin/php artisan app:order-create-doctor --render
/usr/local/bin/php artisan app:catalog-ownership-doctor --render
/usr/local/bin/php bin/stable-maintenance-smoke.php

/usr/local/bin/php artisan app:release-check --profile=stable --render --strict --snapshot --report=v2.1.1-stable-acceptance.json
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
/usr/local/bin/php artisan optimize
```

Očekivani rezultat je `RELEASE CHECK: STABLE READY`.

## Baza

Nema nove migracije baze. `migrate --force` se ipak pokreće radi potvrde da nema zaostalih migracija.
