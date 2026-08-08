# Upgrade: v2.0.0-beta6-stable → v2.1.0-beta1

## 1. Pre deploya

Napravite backup:

- Laravel baze `icaffeco_lrvl`;
- produkcionog `.env` fajla;
- kompletnog aplikacionog foldera;
- `storage/app/public` i drugih upload foldera.

Legacy baza `icaffeco_cms` se ovom verzijom ne migrira i ostaje read-only.

## 2. Raspakivanje

Raspakujte Upgrade ZIP preko postojeće beta6-stable instalacije. Ne prepisujte:

- `.env`;
- `storage/app/public`;
- `public/storage`;
- korisničke upload fajlove;
- postojeće runtime logove i session podatke.

## 3. Komande

```bash
cd /home/icaffeco/cms.ald1n.com
mkdir -p storage/framework/sessions storage/framework/cache/data storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan app:deployment-check --repair
php artisan app:auth-doctor Ald1n --render-dashboard
php artisan legacy:check
php artisan app:version
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan app:deployment-check
php artisan optimize
```

`app:deployment-check --repair` primenjuje migraciju `000009`, dopunjava dozvole i proverava nove tabele/kolone.

## 4. Poslovna podešavanja

Posle migracije prijavite se kao SuperAdministrator i otvorite:

**Podešavanja → PDF i fakturisanje**

Unesite najmanje:

- naziv firme;
- adresu i grad;
- PIB i matični broj pre izdavanja računa;
- telefon, e-mail i web adresu;
- PDV režim i stopu;
- rok plaćanja;
- podrazumevanu napomenu i footer.

Logo se uzima iz postojećeg branding podešavanja. Ako nema kompatibilnog lokalnog JPEG logotipa, PDF koristi Ald1n monogram kao bezbedan fallback.

## 5. Kontrolna provera

1. Kreirajte test korisničku porudžbinu i izaberite konkretnog Administratora.
2. Proverite da taj Administrator vidi porudžbinu, a drugi Administrator ne.
3. Preuzmite korisničku PDF potvrdu.
4. Izdajte test predračun i račun.
5. Otvorite **Izveštaji**, primenite filter i preuzmite CSV i PDF.
6. Proverite da račun sadrži tačne poslovne i poreske podatke.

## 6. Rollback

Preporučeni rollback je vraćanje backup-a aplikacije i Laravel baze. Migracija ima `down()`, ali produkcioni rollback baze ne treba raditi bez prethodnog backup-a i provere da nema novih dokumenata ili dodeljenih porudžbina.
