# Upgrade na v2.1.3 - Catalog Settings & Product Data Maintenance

## Polazna verzija

`v2.1.2`

## Sta se menja

- svaki tip proizvoda dobija posebnu stranicu `/admin/catalog-settings/product-type/{slug}`;
- kartice sifarnika i specifikacija mogu da se rasporede Drag & Drop postupkom na telefonu i racunaru;
- na vrhu se ukljucuje rezim `Uredi raspored`, a na dnu se raspored cuva dugmetom `Zavrsi uredjivanje`;
- specifikaciono polje moze da se deaktivira ili trajno obrise uz upis tacnog naziva i audit zapis;
- tip proizvoda trajno odredjuje jednu automatsku kategoriju, pa se kategorija vise ne bira na formi artikla, bulk izmeni ili kloniranju;
- postojeci tipovi se automatski povezuju po nazivu/slug-u ili po jedinoj kategoriji koju vec koriste njihovi artikli;
- svaki disk ima zaseban tip i kapacitet u GB, na primer `NVMe SSD 512 GB + SATA SSD 1000 GB + HDD 2000 GB`;
- sva numericka polja sa jedinicom GB prihvataju samo cele brojeve;
- thumbnail i galerijski prikazi koriste `object-fit: contain`, bez secenja fotografije;
- e-mail o novom artiklu prikazuje glavnu sliku, opis i cenu;
- Stable release profil ukljucuje `app:catalog-settings-doctor`.

## Migracija baze

Obavezna je migracija:

```text
2026_08_04_000033_create_catalog_type_layout_v2_1_3.php
```

Migracija dodaje:

- `product_types.category_id`;
- `product_spec_values.value_json`;
- `product_variant_spec_values.value_json`.

## Postavljanje

```bash
cd /home/icaffeco/cms.ald1n.com

# Backup postojece v2.1.2 instalacije
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify

# Raspakuj UPGRADE paket preko v2.1.2 instalacije.
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr
/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan migrate --force

/usr/local/bin/php bin/catalog-settings-product-data-smoke.php
/usr/local/bin/php bin/product-media-ux-smoke.php
/usr/local/bin/php bin/product-announcement-smoke.php

# Uskladi tipove i kategorije gde postoji bezbedno automatsko poklapanje.
/usr/local/bin/php artisan app:catalog-settings-doctor --repair

# Napravi svez v2.1.3 backup pre strict release provere.
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify

/usr/local/bin/php artisan app:release-check \
  --profile=stable \
  --render \
  --strict \
  --snapshot \
  --report=v2.1.3-stable-acceptance.json

/usr/local/bin/php artisan optimize
```

Ocekivani zavrsetak je `RELEASE CHECK: STABLE READY`.

## Ako doctor prijavi nepovezan tip

Otvori:

```text
/admin/catalog-settings/product-types
```

Zatim udji u konkretan tip, izaberi njegovu `Automatsku kategoriju`, sacuvaj i ponovi:

```bash
/usr/local/bin/php artisan app:catalog-settings-doctor
/usr/local/bin/php artisan app:release-check --profile=stable --render --strict --snapshot --report=v2.1.3-stable-acceptance.json
```

## Rucna provera

- promeni raspored kartica na telefonu i racunaru;
- otvori posebnu stranicu laptopova, racunara i ostalih tipova;
- deaktiviraj testno specifikaciono polje, a trajno brisanje proveri samo na polju bez poslovne vrednosti;
- kreiraj artikal izborom samo tipa i potvrdi da je kategorija automatski dodeljena;
- sacuvaj kombinaciju vise diskova sa zasebnim kapacitetima;
- proveri da galerija prikazuje celu vertikalnu i horizontalnu fotografiju;
- posalji testno obavestenje o novom artiklu i potvrdi sliku, opis i cenu.
