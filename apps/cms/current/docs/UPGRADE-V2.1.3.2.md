# Upgrade na v2.1.3.2 — Product Save Regex Hotfix

## Uzrok Error 500

`ProductRequest` i `ProductVariantRequest` imali su pogrešno escape-ovan `/` unutar SKU `regex` pravila. Laravel je tokom svake izmene artikla pozivao `preg_match()`, PHP je prijavljivao `Unknown modifier '-'`, a produkcioni error handler je upozorenje pretvarao u Error 500 pre nego što je controller uopšte mogao da sačuva proizvod.

Hotfix menja delimiter regexa sa `/` na `#`, pa `/` ostaje dozvoljen znak u SKU-u bez rizičnog escape-ovanja.

## Polazna verzija

UPGRADE paket se primenjuje isključivo preko `v2.1.3.1`.

## Instalacija

```bash
cd /home/icaffeco/cms.ald1n.com

/usr/local/bin/php artisan app:backup-create --type=manual
```

Raspakuj UPGRADE ZIP preko postojeće instalacije, zatim pokreni:

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr

/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan view:clear
/usr/local/bin/php artisan migrate --force

/usr/local/bin/php bin/product-save-regex-hotfix-smoke.php
```

Očekivano:

```text
Product Save Regex Hotfix smoke: 7/7 uspesno.
```

Nova migracija baze ne postoji. `migrate --force` samo potvrđuje da nema neizvršenih ranijih migracija.

## Ručna provera

1. Otvori postojeći proizvod.
2. Izmeni model procesora ili drugu specifikaciju.
3. Sačuvaj proizvod.
4. Ponovo otvori proizvod i potvrdi da je vrednost sačuvana.
5. Po potrebi proveri i čuvanje varijante proizvoda.

## Stable acceptance

Nakon uspešne ručne provere napravi svež backup i pokreni:

```bash
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify

/usr/local/bin/php artisan app:release-check \
  --profile=stable \
  --render \
  --strict \
  --snapshot \
  --report=v2.1.3.2-stable-acceptance.json

/usr/local/bin/php artisan optimize
```

Očekivani završetak je `RELEASE CHECK: STABLE READY`.
