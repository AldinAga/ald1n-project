# Upgrade na v2.0.0-alpha3.1

Hotfix ispravlja tri regresije iz Alpha3:

1. login i reset lozinke više ne dobijaju 500 zbog nedostajućih branding promenljivih;
2. istorija EUR/RSD kursa koristi postojeću tabelu `exchange_rate_history`;
3. `app:version` prikazuje stvarnu verziju paketa.

## Instalacija

Raspakovati upgrade paket direktno u Laravel root:

```text
/home/icaffeco/cms.ald1n.com
```

Zatim:

```bash
cd /home/icaffeco/cms.ald1n.com
composer dump-autoload --optimize
php artisan optimize:clear
php artisan app:version
composer test
php bin/static-check.php
php artisan app:deployment-check
```

Ova verzija nema novu migraciju baze.

## Legacy slike

`FAIL legacy product media root` je konfiguraciona provera. `LEGACY_MEDIA_ROOT` mora pokazivati na root starog CMS-a koji direktno sadrži `uploads/products`.
