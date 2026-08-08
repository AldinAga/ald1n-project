# Upgrade na v2.1.0-beta7.23.2

## Namena hotfix paketa

Ovaj paket ispravlja `ArgumentCountError` iz centralne render provere:

```text
Too few arguments to function App\Http\Controllers\Admin\ProductController::edit(),
1 passed in app/Console/Commands/DetailPagesDoctorCommand.php and exactly 2 expected
```

`ProductController::edit()` prima `Product` model i `ProductTemplateService`. HTTP router normalno rešava servisnu zavisnost kroz Laravel container, ali je doctor komanda kontroler pozivala direktno i prosleđivala samo model.

Paket ne menja bazu i ne menja poslovne podatke.

## Programska ispravka

`DetailPagesDoctorCommand` sada poziva administratorski edit kroz Laravel container:

```php
app()->call([app(AdminProductController::class), 'edit'], [
    'product' => $product,
]);
```

Container automatski prosleđuje `ProductTemplateService`, isto kao tokom pravog HTTP zahteva. Isti container-safe obrazac primenjen je i na galeriju artikla kako buduća dodatna zavisnost ne bi ponovo oborila audit.

Dodati su:

- `php bin/detail-pages-doctor-smoke.php`;
- `tests/Unit/DetailPagesDoctorContractTest.php`;
- statička regresiona provera da direktni controller pozivi nisu vraćeni.

## Deploy

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan view:clear

php bin/detail-pages-doctor-smoke.php
php artisan app:detail-pages-doctor
```

Zatim ponovi centralnu proveru:

```bash
php artisan app:release-check --profile=full --repair --render --snapshot
```

## Scheduler cron za ovaj server

Komanda `which php` je vratila:

```text
/usr/local/bin/php
```

Zato cPanel cron, jednom u minuti, treba da bude:

```cron
* * * * * cd /home/icaffeco/cms.ald1n.com && /usr/local/bin/php artisan schedule:run >> /home/icaffeco/cms.ald1n.com/storage/logs/scheduler.log 2>&1
```

Jednokratna provera:

```bash
/usr/local/bin/php artisan app:scheduler-heartbeat
/usr/local/bin/php artisan app:system-health
```

## Rollback

Nema migracije baze. Za rollback vrati fajlove iz `v2.1.0-beta7.23.1`, pa pokreni:

```bash
php artisan optimize:clear
php artisan view:clear
```
