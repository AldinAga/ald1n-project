# Backup i restore proba

`app:backup-verify` dokazuje da su manifest, SQL gzip i backup fajlovi citljivi i hash-validni. To nije isto sto i stvarna restore proba. Pre Stable izdanja potrebno je najmanje jednom vratiti backup na odvojenu test bazu.

## 1. Kreiranje i verifikacija

```bash
cd /home/icaffeco/cms.ald1n.com
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
```

Zabelezi putanju prikazanu u izlazu. Unutar nje se nalaze:

```text
manifest.json
database.sql.gz
files/
```

## 2. Odvojena baza

U cPanel-u napravi posebnu praznu bazu i korisnika, na primer:

```text
icaffeco_cms_restore_test
```

Nikada ne koristi produkcionu bazu kao restore metu.

## 3. Import SQL backupa

Primer:

```bash
gunzip -c /APSOLUTNA/PUTANJA/database.sql.gz | \
  mysql -h localhost -u RESTORE_USER -p RESTORE_DATABASE
```

Lozinku ne upisuj direktno u shell komandu ili istoriju terminala.

## 4. Izolovana aplikaciona provera

Napravi privremeni `.env.restore-test` ili odvojenu kopiju aplikacije koja pokazuje samo na restore bazu. Obavezno podesi:

```env
APP_ENV=testing
APP_DEBUG=false
MAIL_MAILER=log
QUEUE_CONNECTION=sync
TURNSTILE_ENABLED=false
```

Nemoj dozvoliti slanje stvarnih e-mailova, scheduler ili poslovnu automatizaciju iz restore okruzenja.

Pokreni:

```bash
/usr/local/bin/php artisan --env=restore-test migrate:status
/usr/local/bin/php artisan --env=restore-test app:migrations-doctor --strict
/usr/local/bin/php artisan --env=restore-test app:auth-doctor --render-dashboard
/usr/local/bin/php artisan --env=restore-test app:customer-portal-doctor --render
/usr/local/bin/php artisan --env=restore-test app:management-reports-doctor --render
```

## 5. Kontrolni podaci

Uporedi produkciju i restore kopiju za:

- broj korisnika;
- broj proizvoda i varijanti;
- broj porudzbina i stavki;
- zbir uplata;
- broj dokumenata;
- stanje lagera;
- broj reklamacija i garancija;
- poslednji audit zapis.

Ne kopiraj osetljive vrednosti u javne izvestaje. Dovoljni su brojevi, sume i poslednji ID/datumi.

## 6. Privatni fajlovi

Proveri da `files/` sadrzi ocekivane privatne priloge i da njihove SHA-256 vrednosti odgovaraju `manifest.json`. Ne objavljuj ovaj direktorijum kroz web server.

## 7. Zavrsna evidencija

Sacuvaj datum probe, backup key, osobu koja je izvrsila probu, rezultat i eventualna odstupanja. Test bazu i privremene kredencijale zatim bezbedno ukloni.
