# Nadogradnja na v2.1.0-beta7.17

Osnova: `v2.1.0-beta7.16`

Ova verzija dodaje modul **Potraživanja i naplata** i ispravlja regresije navigacionih podmenija i checkbox/radio kontrola.

## 1. Backup

Pre postavljanja napravi backup glavne baze i postojećih fajlova aplikacije.

## 2. Postavljanje fajlova

Raspakuj beta7.17 preko postojeće beta7.16 instalacije. Ne kopiraj `.env` iz paketa preko produkcionog `.env` fajla.

## 3. Migracija

Nova migracija:

```text
2026_07_31_000026_create_receivables_collection_beta7_17.php
```

Kreira ili popravlja:

- `receivable_cases`;
- `receivable_installments`;
- `receivable_contacts`;
- permission `receivables.manage`;
- podrazumevana podešavanja opomena.

Migracija je nedestruktivna i ima recovery putanju za MariaDB 10.11. Ako je prethodni DDL pokušaj prekinut, pojedinačno dodaje nedostajuće kolone, indekse i strane ključeve.

## 4. Komande

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr

php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force

php artisan app:deployment-check
php artisan app:receivables-doctor --scan
php artisan app:order-emails-doctor
php artisan app:payments-inventory-doctor
php artisan app:automation-doctor

php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php

php artisan optimize
```

Opcija `--scan` odmah proverava postojeće porudžbine sa uplatom na račun, otvara nedostajuće predmete i usklađuje stanje sa stvarnim saldom.

## 5. Cron

Automatske opomene koriste postojeći hourly automation scan i e-mail outbox. Cron mora ostati:

```cron
* * * * * cd /putanja/do/aplikacije && php artisan schedule:run >> /dev/null 2>&1
```

## 6. Podešavanja

Otvori **Porudžbine → Potraživanja i naplata** i proveri:

- uključivanje modula;
- automatsko otvaranje predmeta;
- automatske opomene;
- broj dana za najavu pre dospeća;
- faze kašnjenja, npr. `0,3,7,15,30`;
- pauziranje opomena do obećanog datuma;
- PDF dokument koji se prilaže;
- primaoce i dodatne e-mail adrese.

## 7. Dozvole

Seeder dodaje `receivables.manage`. SuperAdministrator je dobija automatski kroz postojeći access seeder. Ostalim grupama je dodeli samo kada treba da upravljaju naplatom.

## 8. Provera UI ispravki

Na desktopu proveri:

- hover otvara podmeni;
- klik na naslov otvara ili zatvara podmeni;
- klik van menija zatvara podmeni;
- Escape zatvara podmeni;
- izbor linka zatvara podmeni;
- drugi otvoreni podmeni se zatvara.

Na mobilnom uređaju proveri isto ponašanje klikom. Checkbox i radio elementi na ekranima podešavanja treba da budu približno 17 × 17 px.

## 9. Poslovna pravila

- Plan rata ne predstavlja uplatu.
- Samo verifikovane stavke iz `order_payments` menjaju plaćeni iznos.
- Uplate se raspoređuju na najstarije rate prve.
- Predmet se ne može ručno zatvoriti dok postoji preostali dug.
- Kada saldo postane nula, predmet se automatski zatvara.
- Storniranje ili odbijanje uplate ponovo usklađuje rate i predmet.
