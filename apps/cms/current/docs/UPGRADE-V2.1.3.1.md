# Upgrade na v2.1.3.1 — Catalog Category & Specification Integrity Hotfix

## Polazna verzija

Ovaj UPGRADE paket se primenjuje isključivo preko `v2.1.3`.

## 1. Backup

```bash
cd /home/icaffeco/cms.ald1n.com

/usr/local/bin/php artisan app:version
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
```

`app:version` pre raspakivanja treba da prikaže `2.1.3`.

## 2. Raspakivanje

Raspakuj UPGRADE ZIP preko postojeće instalacije, bez brisanja `.env`, `storage` i produkcionih upload fajlova.

## 3. Composer i cache

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr

/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan view:clear
```

## 4. Migracija i automatska popravka

```bash
/usr/local/bin/php artisan migrate --force
/usr/local/bin/php artisan app:catalog-settings-doctor --repair
```

Migracija `2026_08_04_000034_repair_catalog_category_and_spec_integrity_v2_1_3_1.php` je idempotentna data-integrity migracija. Ne menja kolone i ne briše validne specifikacije.

Očekivani završetak doctor komande:

```text
PASS Svi aktivni tipovi imaju automatsku sistemsku kategoriju.
PASS Artikli koriste isključivo kategoriju povezanu sa svojim tipom.
PASS Nema zastarelih vrednosti, pivot veza, opcija ni korelacija obrisanih specifikacionih polja.
Podešavanja kataloga su spremna.
```

## 5. Smoke provera

```bash
/usr/local/bin/php bin/catalog-settings-integrity-hotfix-smoke.php
```

Očekivano:

```text
Catalog Settings Integrity Hotfix smoke: 16/16 uspesno.
```

## 6. Ručna provera kritičnog toka

1. Otvori problematični artikal.
2. Dodeli novi model procesora.
3. Sačuvaj artikal.
4. Ponovo otvori artikal i potvrdi da je nova vrednost sačuvana.
5. Proveri katalog i detalj artikla.

## 7. Svež hotfix backup

```bash
/usr/local/bin/php artisan app:version
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
```

`app:version` sada mora da prikaže `2.1.3.1`.

## 8. Stable release provera

```bash
/usr/local/bin/php artisan app:release-check \
  --profile=stable \
  --render \
  --strict \
  --snapshot \
  --report=v2.1.3.1-stable-acceptance.json

/usr/local/bin/php artisan optimize
```

Očekivano:

```text
RELEASE CHECK: STABLE READY
```

## Ako se Error 500 ipak ponovi

Odmah sačuvaj poslednjih 120 redova loga pre nove izmene:

```bash
tail -n 120 storage/logs/laravel.log
```

Hotfix pretvara očekivane DB probleme specifikacionog polja u validacionu poruku. Novi Error 500 bi zato ukazivao na drugi, konkretan problem koji treba analizirati iz loga.
