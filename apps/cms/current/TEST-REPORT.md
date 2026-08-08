# Test Report — Ald1n CMS v2.2.0 Mobile API Foundation

Datum pakovanja: 2026-08-06

## Izvršene statičke i regresione provere

- CMS v2.2.0 Mobile API Foundation smoke: 29/29
- Production readiness hotfix smoke: 10/10
- statički ugovori aplikacije: 983/983
- PHP lint: 531 PHP fajlova, 0 grešaka
- Composer PSR-4/autoload provera: 0 grešaka
- OpenAPI 3.1 validacija: 17 putanja, 22 operacije, 0 duplih YAML ključeva
- Composer JSON validacija: uspešna
- CMS v2.1.6 regresija: 31/31
- CMS v2.1.5 regresija: 30/30
- CMS v2.1.4 regresija: 23/23
- Product Type Page Render hotfix: 14/14
- Storage Capacity Total regresija: 23/23
- Product Save Regex regresija: 7/7
- Release Check smoke ugovor: 22/22
- Stable hardening smoke: 29/29
- RC hardening smoke: 24/24
- FULL manifest: 741 kontrolisan fajl

## Pokrivenost v2.2.0

- verzioni i migration ugovor za `000039`–`000041`;
- `/api/v1/bootstrap`, efektivne token dozvole i feature flagovi;
- katalog filteri i opcije za kreiranje porudžbine;
- registracija, izmena, lista i opoziv Android/iOS instalacija;
- šifrovano čuvanje raw push tokena i jedinstveni SHA-256 hash;
- zamena starog Sanctum tokena pri ponovnoj registraciji iste instalacije;
- opoziv vezanog Sanctum tokena pri uklanjanju uređaja;
- profil, promena lozinke i preference obaveštenja;
- lista i označavanje obaveštenja kao pročitanih;
- standardizovan API error envelope sa `code` i `request_id`;
- apsolutni URL-ovi slika proizvoda za native klijente;
- database queue infrastruktura i v2.2.0 doctor ugovor;
- disk health politika koja ne blokira 750,52 GB slobodnog prostora samo zato što predstavlja 9,6% shared filesystema;
- OpenAPI 3.1 ugovor i React Native / Expo integraciona dokumentacija.

Dodati su Laravel feature i contract testovi za navedene tokove, uključujući zamenu i opoziv stvarnih Sanctum tokena. Njihovo izvršavanje zahteva instaliran Composer `vendor` i test bazu.

## Ograničenja build okruženja

Build okruženje nema Composer executable, instaliran `vendor` ni projektnu bazu. Zbog toga ovde nisu izvršeni Laravel bootstrap, `artisan route:list`, stvarne migracije niti PHPUnit test suite.

Pre produkcije obavezno izvršiti korake iz `docs/UPGRADE-V2.2.0.md`, posebno:

- `php artisan migrate --force`;
- PHPUnit test suite na staging/local okruženju sa instaliranim `require-dev` paketima;
- `php artisan app:cms-v2-2-0-doctor --strict`;
- `php artisan app:release-check --profile=stable --strict`;
- pokretanje trajnog `queue:work database` procesa.

v2.2.0 registruje i bezbedno čuva push tokene, ali ne tvrdi da je Expo/FCM/APNs dispatcher već implementiran. `MOBILE_PUSH_ENABLED` ostaje `false` do naredne faze.


## Produkcioni Composer režim

Posle `composer install --no-dev` Laravel Artisan `test` komanda nije dostupna jer `phpunit` i `nunomaduro/collision` pripadaju `require-dev`. To je očekivano ponašanje. Produkciona validacija koristi dependency-free smoke skripte i `app:release-check`; PHPUnit ostaje obavezna CI/staging provera pre `--no-dev` instalacije.
