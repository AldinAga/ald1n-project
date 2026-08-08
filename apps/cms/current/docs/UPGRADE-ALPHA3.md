# Upgrade na v2.0.0-alpha3

## 1. Backup

Sačuvaj:

- MySQL bazu `icaffeco_lrvl`;
- `/home/icaffeco/cms.ald1n.com`;
- `/home/icaffeco/cms.ald1n.com/.env` odvojeno od web projekta.

Ne menjaj staru bazu i ne raspakuj paket u `public`.

## 2. Raspakivanje i Composer

Raspakuj upgrade preko:

```text
/home/icaffeco/cms.ald1n.com
```

Zatim:

```bash
cd /home/icaffeco/cms.ald1n.com
composer install
php artisan optimize:clear
php artisan app:version
```

Očekivana verzija je `2.0.0-alpha3`.

U serverski `.env` upiši stvarni javni folder starog CMS-a, na primer tek nakon potvrde putanje:

```dotenv
LEGACY_MEDIA_ROOT=/APSOLUTNA/PUTANJA/DO/STAROG/CMSA
```

Folder mora sadržati `uploads/products`. Laravel zatim slike servira kroz svoju autorizovanu media rutu, bez deljenja sesije sa starim CMS-om.

## 3. Migracija i dozvole

```bash
php artisan migrate --force
php artisan db:seed --class=CoreAccessSeeder --force
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

## 4. Pregled i import

```bash
php artisan legacy:check
php artisan legacy:import --scope=remaining --dry-run
```

Dry-run samo prikazuje redove iz izvora. Kada su brojevi očekivani:

```bash
php artisan legacy:import --scope=remaining
```

`remaining` prenosi nedostajuće korisnike i kataloške redove, a postojeći Laravel korisnik i njegova trenutna lozinka ostaju sačuvani.

Za preuzimanje najnovijih podešavanja izgleda/kursa iz starog sistema:

```bash
php artisan legacy:import --scope=settings --overwrite-settings
php artisan optimize:clear
php artisan legacy:media-check
```

## 5. Scheduler

U cPanel Cron Jobs dodaj jednom u minuti:

```cron
* * * * * cd /home/icaffeco/cms.ald1n.com && php artisan schedule:run >> /dev/null 2>&1
```

Laravel će sam pokretati automatsko ažuriranje kursa u definisano vreme, samo kada je auto režim uključen.

## 6. Testovi

```bash
composer test
php bin/static-check.php
php bin/domain-smoke.php
php bin/catalog-admin-smoke.php
php artisan app:deployment-check
```

## 7. Produkcijska optimizacija

Tek posle uspešnih testova:

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize
```

## Rollback

Kod problema vrati backup projekta i `icaffeco_lrvl`. Stari CMS i legacy baza nisu menjani ovim upgrade-om.
