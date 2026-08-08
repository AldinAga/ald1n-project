# Nadogradnja na v2.1.0-beta7.24.1

## Namena hotfix paketa

`app:release-check --strict` je ispravno zaustavio prelazak u RC zato što je jedna istorijska stavka porudžbine imala `cost_source_snapshot=missing` i prazne snapshot vrednosti nabavne cene.

Prethodni `app:management-reports-doctor --repair` pokretao je migracije i seeder, ali nije ponovo obrađivao stavke koje su tokom stare migracije već označene kao `missing`. Zbog toga upozorenje nije moglo da se ukloni ni kada je proizvodu ili varijanti naknadno upisana nabavna cena.

## Nova bezbedna dopuna

Dodat je servis `OrderItemCostSnapshotService` koji obrađuje isključivo nepotpune snapshotove. Kompletni postojeći snapshotovi se nikada ne prepisuju.

Automatski kandidat se traži ovim redosledom:

1. postojeći unit/total snapshot ako je samo drugi deo nepotpun;
2. aktuelna nabavna cena konkretne varijante;
3. poslednji knjiženi prijem robe do datuma nastanka stavke;
4. aktuelna nabavna cena proizvoda.

Izvor procene se trajno upisuje u `cost_source_snapshot`, na primer:

- `repair_variant_current`;
- `repair_receipt_historical`;
- `repair_product_current`;
- `repair_existing_unit`.

Svaka uspešna automatska ili ručna finansijska dopuna upisuje se u postojeći `audit_logs` sistem.

## Nova Artisan komanda

Audit bez promene podataka:

```bash
/usr/local/bin/php artisan app:order-cost-snapshots
```

Automatska dopuna svih stavki za koje postoji kandidat:

```bash
/usr/local/bin/php artisan app:order-cost-snapshots --repair
```

Ako za neku stavku nema pouzdanog automatskog kandidata, komanda prikazuje njen ID, porudžbinu i SKU. Ručna dopuna zahteva cenu i obrazloženje:

```bash
/usr/local/bin/php artisan app:order-cost-snapshots \
  --item=ID_STAVKE \
  --unit-cost=12345.67 \
  --reason="Nabavna cena potvrđena prema ulaznom dokumentu"
```

Ručna promena nije dozvoljena bez `audit_logs` tabele.

## Deploy

Polazna verzija mora biti `v2.1.0-beta7.24`.

```bash
cd /home/icaffeco/cms.ald1n.com
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr
/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan view:clear

/usr/local/bin/php bin/order-cost-snapshot-smoke.php
/usr/local/bin/php artisan app:management-reports-doctor --repair --render

/usr/local/bin/php artisan app:release-check \
  --profile=full \
  --repair \
  --render \
  --strict \
  --snapshot

/usr/local/bin/php artisan optimize
```

Nema nove migracije baze.

## Ako stavka ostane nerešena

Pokreni:

```bash
/usr/local/bin/php artisan app:order-cost-snapshots
```

Komanda će prikazati tačan ID stavke. Nabavnu cenu proveri prema originalnom ulaznom dokumentu ili pouzdanoj evidenciji, zatim koristi ručnu komandu sa obaveznim razlogom. Nemoj unositi proizvoljnu cenu samo radi prolaska strict provere.

## Rollback

Vrati fajlove iz `v2.1.0-beta7.24` i pokreni:

```bash
/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan view:clear
```

Podaci koji su već dopunjeni ostaju sačuvani i auditovani; rollback koda ih namerno ne briše.
