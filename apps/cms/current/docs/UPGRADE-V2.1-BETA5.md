# Upgrade na v2.1.0-beta5

## Svrha

Beta5 preuređuje stranicu **Administracija → Napredni lager**. Uklonjeno je istovremeno prikazivanje dve široke operativne forme, prelivanje inputa iz kartica, horizontalni scroll cele stranice i mali unutrašnji vertikalni scroll tabele.

## Upgrade osnova

Paket se primenjuje preko potvrđene `v2.1.0-beta4` instalacije.

## Postupak

1. Napraviti backup aplikacije, produkcionog `.env` fajla, privatnih potvrda uplate i Laravel baze.
2. Raspakovati Upgrade ZIP preko postojeće instalacije.
3. Ne prepisivati `.env`, `storage/app`, `storage/logs`, `public/storage` niti korisničke upload fajlove.
4. Pokrenuti:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan app:payments-inventory-doctor --render
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan app:deployment-check
php artisan optimize
```

## Promene na stranici lagera

- Ulaz robe i popis lagera su odvojene operativne kartice/tabovi.
- Prikazuje se samo aktivna operacija, u punoj širini.
- Filter podržava 25, 50, 100 ili 250 artikala.
- Pojam pretrage i broj artikala ostaju sačuvani posle knjiženja.
- Tabela više nema unutrašnji vertikalni scroll.
- Široka tabela skroluje samo lokalno, nikada celu stranicu.
- Na telefonu se svaki red pretvara u čitljivu karticu sa nazivima polja.
- Istorija ulaza i popisa ostaje u dve kolone na desktopu, a prelazi u jednu kolonu na manjim ekranima.

## Baza

Beta5 ne uvodi novu migraciju i ne menja poslovnu logiku lagera. Transakcije, idempotency, audit i stock movement zapisi ostaju nepromenjeni.
