# Upgrade na v2.1.0-beta7.14.1

Ovo je nedestruktivni hotfix za MySQL grešku:

```text
SQLSTATE[HY000]: General error: 1553 Cannot drop index
'order_documents_order_type_unique': needed in a foreign key constraint
```

## Uzrok

InnoDB je koristio složeni unique indeks `order_id + document_type` kao jedini indeks za foreign key kolonu `order_id`. MySQL zato nije dozvolio njegovo uklanjanje.

## Ispravka

Migracija sada:

1. dodaje kolone revizija samo ako nedostaju;
2. proverava sve postojeće indekse;
3. po potrebi kreira `order_documents_order_id_fk_index (order_id)`;
4. tek zatim uklanja unique indeks `order_id + document_type`;
5. formira revizioni lanac i dodaje nove operativne indekse.

Postojeći dokumenti, brojevi, statusi i PDF istorija ostaju sačuvani.

## Komande

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr

php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force

php artisan app:deployment-check
php artisan app:payments-inventory-doctor
php artisan optimize
```

Nije potrebno ručno dodavati kolone, uklanjati foreign key ili menjati tabelu `migrations`.

Posle migracije deployment provera treba da prikaže:

```text
PASS operations tables
PASS document revisions
```

`Redis disabled` proverava samo aktivne cache/session/queue drivere; samo postojanje neaktivne Redis konfiguracije više nije greška.
