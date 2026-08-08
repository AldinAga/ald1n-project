# Nadogradnja na v2.1.0-beta7.17.1

Ovaj hotfix rešava grešku:

```text
SQLSTATE[42S22]: Unknown column `updated_at` in `permissions`
```

Na starijim instalacijama tabela `permissions` po projektu ima `created_at`, ali nema `updated_at`. Beta7.17 migracija je u završnom seed koraku slala obe kolone, zbog čega migracija nije bila registrovana kao izvršena iako su receivables tabele možda već kreirane.

## Postupak

1. Napraviti backup baze.
2. Raspakovati beta7.17.1 preko beta7.17.
3. Pokrenuti:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force
php artisan app:deployment-check
php artisan app:receivables-doctor --scan
php artisan optimize
```

Ne brisati ručno tabele `receivable_*` niti zapis iz `migrations`. Pošto neuspešna migracija nije evidentirana kao završena, Laravel će je ponovo pokrenuti. Migracija proverava postojeće tabele, kolone, indekse i strane ključeve i dopunjava samo ono što nedostaje.
