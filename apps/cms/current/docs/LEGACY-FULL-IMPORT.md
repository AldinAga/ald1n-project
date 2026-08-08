# Potpuni import preostalih legacy podataka

## Scope opcije

```text
access      uloge, grupe, dozvole i korisnici
catalog     kategorije, brendovi, linije, tipovi, specifikacije, artikli i slike
settings    izgled, footer, kurs i druga podešavanja
operations  žiro računi, porudžbine, stavke, provizije, IPS QR, lager, kurs istorija i audit
remaining   sve gore navedeno uz očuvanje lokalnih korisnika i kataloških redova
all         sve podržane tabele
```

## Bezbedan postupak

```bash
php artisan legacy:check
php artisan legacy:import --scope=remaining --dry-run
php artisan legacy:import --scope=remaining
```

Komanda proverava da su ciljna i legacy baza različite. Legacy konekcija se koristi samo za SELECT.

## Podešavanja

Podrazumevano se postojeće Laravel vrednosti ne prepisuju. Za nameran prenos aktuelnog izgleda/kursa iz starog sistema:

```bash
php artisan legacy:import --scope=settings --overwrite-settings
```

## Truncate režim

`--truncate` briše podržane ciljne tabele pre importa i namenjen je samo svežem staging okruženju sa prethodnim backupom. Ne koristi ga na sistemu u kome su već nastale Laravel izmene.

## Posle importa

```bash
php artisan optimize:clear
php artisan app:deployment-check
```

Ručno uporedi broj redova i nekoliko reprezentativnih zapisa: korisnike, artikle sa slikama, porudžbine sa stavkama, provizije, promene lagera i izgled sajta.

## Slike artikala

Legacy slike se ne izlažu direktnim javnim URL-om. Podesi `LEGACY_MEDIA_ROOT` na apsolutni public folder starog CMS-a koji sadrži `uploads/products`, zatim pokreni `php artisan optimize:clear`. Pristup slikama prolazi kroz Laravel autorizaciju i kategorijski scope.
