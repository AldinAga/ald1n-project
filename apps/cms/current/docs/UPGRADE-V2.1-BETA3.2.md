# Upgrade: v2.1.0-beta3.1 → v2.1.0-beta3.2

## Svrha

Hotfix uklanja mali unutrašnji vertikalni scrollbar koji se pojavljivao kada se na stranici **Provizije** klikne na **Obradi**.

Prethodni obrazac bio je `position:absolute` unutar `.admin-table-wrap`, koji koristi `overflow:auto`. Browser je zato povećavao scroll područje tabele umesto prozora za obradu.

Beta3.2 prikazuje obradu kao veliki viewport modal:

- centriran je iznad stranice;
- širok je do 620 px;
- koristi raspoloživu visinu ekrana;
- tabela više ne dobija unutrašnji vertikalni scrollbar;
- zatvara se dugmetom × ili tasterom Escape;
- samo jedan modal može biti otvoren.

## Deploy

```bash
cd /home/icaffeco/cms.ald1n.com

composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr

php artisan optimize:clear
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan app:operations-doctor --render
php artisan optimize
```

Nova migracija baze nije potrebna.

Ako pending beta3 payment/inventory migracija još nije završena, dodatno pokrenuti:

```bash
php artisan app:payments-inventory-doctor --repair --render
php artisan app:deployment-check --repair
```

## Ručna provera

1. Otvori `/admin/commissions`.
2. Klikni **Obradi** na bilo kojoj proviziji.
3. Potvrdi da se otvorio veliki centrirani modal i da tabela nema vertikalni scrollbar.
4. Proveri zatvaranje dugmetom × i tasterom Escape.
5. Otvori istoriju sa više zapisa i potvrdi da se, samo ako je neophodno, skroluje modal.
