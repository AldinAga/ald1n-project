# Upgrade na v2.1.3.3 — Storage Capacity Total & Data Migration Hotfix

## Cilj

Forma više nema dva nezavisna polja koja opisuju isti kapacitet. Redosled je sada:

1. lista diskova — tip i kapacitet svakog diska;
2. read-only polje **Ukupan kapacitet diskova (GB)**;
3. ukupan kapacitet se automatski računa kao zbir svih unetih diskova.

Primer:

```text
NVMe SSD 512 GB
SATA SSD 1000 GB
HDD 2000 GB
Ukupan kapacitet diskova: 3512 GB
```

## Polazna verzija

UPGRADE paket se primenjuje isključivo preko `v2.1.3.2`.

## Migracija postojećih podataka

Migracija `2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php`:

- označava polje tipova diskova kao izvor pojedinačnih diskova;
- označava staro numeričko polje kao izvedeni ukupni kapacitet;
- povezuje ova dva polja i pomera ukupni kapacitet ispod liste;
- normalizuje proizvode i varijante;
- kada JSON već sadrži pojedinačne kapacitete, njihov zbir ima prednost;
- kada postoje tipovi diskova bez pojedinačnog kapaciteta, stari ukupni kapacitet prenosi se na prvi disk;
- kada istorijski zapis nema sačuvan tip diska, stari zbir se čuva bez gubitka dok korisnik ne dopuni detalje;
- svi kapaciteti ostaju celobrojne GB vrednosti.

Backend ponavlja proračun pri svakom čuvanju, zato se ručno izmenjen hidden input ne smatra autoritativnim.

## Instalacija

```bash
cd /home/icaffeco/cms.ald1n.com

/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
```

Raspakuj UPGRADE ZIP preko postojeće instalacije, zatim:

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr

/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan view:clear
/usr/local/bin/php artisan migrate --force
/usr/local/bin/php artisan app:catalog-settings-doctor --repair

/usr/local/bin/php bin/storage-capacity-total-smoke.php
/usr/local/bin/php bin/product-save-regex-hotfix-smoke.php
```

Očekivano:

```text
Storage Capacity Total smoke: 23/23 uspesno.
Product Save Regex Hotfix smoke: 7/7 uspesno.
```

## Ručna provera

1. Otvori postojeći proizvod koji je ranije imao samo jedan kapacitet.
2. Potvrdi da je lista diskova iznad ukupnog kapaciteta.
3. Potvrdi da ukupni kapacitet nije moguće ručno menjati.
4. Dodaj drugi disk i proveri da se zbir odmah menja.
5. Sačuvaj i ponovo otvori proizvod.
6. Ponovi proveru na jednoj varijanti, ako koristiš varijante.

## Stable acceptance

```bash
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify

/usr/local/bin/php artisan app:release-check \
  --profile=stable \
  --render \
  --strict \
  --snapshot \
  --report=v2.1.3.3-stable-acceptance.json

/usr/local/bin/php artisan optimize
```

Očekivani završetak je `RELEASE CHECK: STABLE READY`.
