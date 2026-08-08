# Nadogradnja na v2.1.0-beta7.16

Polazna verzija: **v2.1.0-beta7.15**.

## Šta se dodaje

Migracija `2026_07_30_000025_create_order_email_outbox_beta7_16.php`:

- kreira `order_email_outbox`;
- dodaje `duration_days` u `warranty_rules` i `product_warranties`;
- dodaje NBS IPS snapshot kolone u `order_documents`:
  - `ips_payload_snapshot`;
  - `ips_qr_image_path`;
  - `ips_qr_generated_at`;
  - `ips_qr_error`.

Migracija je nedestruktivna i ima recovery putanju za delimično izvršen MariaDB DDL. Kolone se proveravaju i dodaju pojedinačno, a nedostajući indeksi i strani ključevi se obnavljaju.

## Obavezni koraci

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force
php artisan app:deployment-check
php artisan app:order-emails-doctor
php artisan app:warranties-doctor
php artisan app:payments-inventory-doctor
php bin/ips-qr-pdf-smoke.php
php artisan optimize
```

## Cron

Na hostingu mora postojati:

```cron
* * * * * cd /putanja/do/aplikacije && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler pokreće `app:order-email-dispatch` svakog minuta. Izabrani interval u podešavanjima određuje kada poruka dospeva za slanje.

## E-mail podešavanja

Otvoriti **Podešavanja → E-mail porudžbina** i podesiti:

- uključivanje obaveštenja;
- autora porudžbine;
- odgovorno lice;
- dodatne adrese;
- interval slanja za kreiranje, promene i dokumente;
- događaje koji se šalju;
- tipove PDF dokumenata koji se šalju;
- opciono prilaganje aktivnog računa uz kasnije promene.

Svaki primalac dobija zasebnu poruku. Dodatne adrese nisu izložene drugim primaocima.

## NBS IPS QR

Za porudžbinu sa načinom plaćanja `bank_transfer`, predračun i račun dobijaju QR kod generisan preko zvaničnog NBS API-ja. Payload koristi snapshot računa i tačan ukupan iznos dokumenta u RSD.

`.env` opcije:

```env
NBS_IPS_QR_GENERATE_URL="https://nbs.rs/QRcode/api/qr/v1/generate/320?lang=sr_RS_Latn"
NBS_IPS_QR_TIMEOUT=20
```

Ako bankovni podaci nisu validni ili NBS servis ne vrati validan PNG, finansijski dokument se ne izdaje. Administrator dobija jasnu validacionu poruku, tako da u sistemu ne ostaje račun bez obaveznog QR koda.

## Provera

```bash
php artisan app:order-emails-doctor
php artisan app:order-email-dispatch --limit=500
php bin/ips-qr-pdf-smoke.php
```
