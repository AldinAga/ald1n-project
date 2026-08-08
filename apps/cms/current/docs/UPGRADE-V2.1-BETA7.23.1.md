# Upgrade na v2.1.0-beta7.23.1

## Namena hotfix paketa

Ovaj paket ispravlja `ViewException` na korisničkom detalju artikla:

```text
syntax error, unexpected token "endforeach", expecting "elseif" or "else" or "endif"
(View: resources/views/catalog/show.blade.php)
```

Uzrok je bio zgusnut niz Blade direktiva u jednom redu unutar prikaza varijanti proizvoda. Laravel Blade compiler je u toj kombinaciji mogao da generiše PHP u kome `@endforeach` dolazi pre očekivanog zatvaranja `@if` bloka.

Paket ne menja bazu i ne menja poslovne podatke.

## Programske ispravke

- prikaz varijanti u `catalog/show.blade.php` prebačen je na eksplicitne višelinijske `@php`, `@if`, `@foreach` i `@can` blokove;
- isto je urađeno za specifikacije proizvoda kako se slična regresija ne bi ponovila;
- dodat je Feature test sa aktivnom varijantom;
- dodat je `php bin/catalog-detail-smoke.php`;
- System Health prikazuje celobrojnu starost runtime događaja i konkretne remediation komande.

## Važno: programski FAIL i operativni CRITICAL nisu isto

Hotfix rešava Blade/render FAIL. Sledeće stavke se ne smeju maskirati izmenom koda:

- stale Laravel scheduler heartbeat;
- automatizacija koja nikada nije uspešno pokrenuta;
- backup stariji od dozvoljenog praga.

One dokazuju da hosting cron ili dnevne operacije ne rade i moraju se stvarno pokrenuti.

## Deploy

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan view:clear
```

Proveri Blade hotfix:

```bash
php bin/catalog-detail-smoke.php
php artisan app:detail-pages-doctor
```

## Runtime remediation na serveru

### 1. Potvrdi PHP binarnu putanju

```bash
which php
```

### 2. Pokreni heartbeat jednom

```bash
php artisan app:scheduler-heartbeat
```

### 3. U cPanel Cron Jobs mora postojati jedan cron svakog minuta

```cron
* * * * * cd /home/icaffeco/cms.ald1n.com && php artisan schedule:run >> /home/icaffeco/cms.ald1n.com/storage/logs/scheduler.log 2>&1
```

Ako cPanel ne prepoznaje `php`, zameni ga punom putanjom dobijenom komandom `which php`.

Posle dva minuta proveri:

```bash
php artisan app:system-health
```

Heartbeat mora biti mlađi od pet minuta.

### 4. Pokreni operativnu automatizaciju

```bash
php artisan app:automation-run
```

Ova komanda može kreirati poslovna upozorenja i obaveštenja prema postojećim podešavanjima. Pregledaj izlaz pre nastavka.

### 5. Kreiraj novu bezbednosnu kopiju

```bash
php artisan app:backup-create --type=manual
```

Proveri da komanda vraća `PASS`, realnu putanju i veličinu. Zatim proveri:

```bash
php artisan app:backup-doctor
php artisan app:system-health --snapshot
```

## Finalna provera

```bash
php artisan app:release-check --profile=full --repair --render --snapshot
```

Očekivani rezultat je `READY FOR PRODUCTION` ili eventualno `READY WITH WARNINGS`. `NOT READY` ne treba zaobilaziti promenom praga ili ručnim upisom lažnog heartbeat-a.

## Rollback

Pošto nema migracije baze:

1. vrati fajlove iz `v2.1.0-beta7.23` paketa;
2. pokreni `php artisan optimize:clear` i `php artisan view:clear`;
3. ponovi `php artisan app:detail-pages-doctor`.
