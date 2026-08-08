# Početni import i Alpha2 sinhronizacija

## Početni snapshot

Na čistoj Laravel bazi koristi se:

```bash
php artisan legacy:check
php artisan legacy:import --dry-run
php artisan legacy:import
```

`legacy:import` je namenjen početnom snapshot-u. Ne menja staru bazu, ali nije više preporučen za redovno osvežavanje nakon što u Laravelu postoje lokalne izmene.

## Redovno poređenje

```bash
php artisan legacy:database-info
php artisan legacy:catalog-diff
php artisan legacy:catalog-sync --dry-run
```

`legacy:database-info` mora potvrditi da legacy nalog nema write privilegije.

## Kontrolisana primena

```bash
php artisan legacy:catalog-sync --apply
```

Primena:

- uspostavlja baseline za identične redove;
- dodaje redove koji nedostaju u Laravelu;
- osvežava legacy red samo kada target nije lokalno menjan;
- ne prepisuje lokalne izmene;
- ne rešava konflikt automatski;
- ne briše target red kada je obrisan iz legacy baze.

## Tabele

Sinhronizuju se kataloške i pomoćne tabele: kategorije, brendovi, linije, tipovi, specifikacije, proizvodi, njihove veze, slike i settings.

## Zabranjeno

Ne koristiti `legacy:import --truncate` bez svežeg backupa nove Laravel baze. Nakon početka rada Alpha2 administracije preferirati `legacy:catalog-sync`.
