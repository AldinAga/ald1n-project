# Definicija legacy i Laravel baza

| Konekcija | Baza | Uloga | Prava |
|---|---|---|---|
| `mysql` | `icaffeco_lrvl` | nova Laravel baza | READ/WRITE |
| `legacy` | `icaffeco_cms` | produkcioni stari CMS | SELECT, SHOW VIEW |
| PHPUnit | posebna test baza / SQLite | izolovani testovi | test runtime |

## Obavezne provere

```bash
php artisan legacy:database-info
php artisan legacy:check
php artisan app:deployment-check
php artisan legacy:catalog-diff
php artisan legacy:catalog-sync --dry-run
```

## Višeslojna read-only zaštita

Standardna deployment provera zahteva:

- `PASS legacy session read-only`;
- `PASS legacy SQL guard`.

Stroga least-privilege provera:

```bash
php artisan legacy:check --strict-grants
php artisan app:deployment-check --strict-legacy-grants
```

## Trenutni `icaffeco_cms` nalog

Ako `SHOW GRANTS` vraća:

```text
GRANT ALL PRIVILEGES ON `icaffeco_cms`.* TO `icaffeco_cms`@`localhost`
```

nalog nije DB-level read-only, bez obzira na isto ime korisnika i baze. Laravel runtime i dalje blokira upis kroz read-only session i SQL guard, ali najbezbednija konfiguracija je poseban korisnik za Laravel legacy konekciju.

U cPanel-u:

1. kreirati novog MySQL korisnika, na primer `icaffeco_legacy_ro`;
2. dodati ga bazi `icaffeco_cms`;
3. označiti samo `SELECT` i, ako je ponuđeno, `SHOW VIEW`;
4. u produkcionom `.env` postaviti novi `LEGACY_DB_USERNAME` i `LEGACY_DB_PASSWORD`;
5. pokrenuti `php artisan optimize:clear` i `php artisan legacy:check --strict-grants`.

Ako hosting dozvoljava SQL upravljanje grantovima:

```sql
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'icaffeco_legacy_ro'@'localhost';
GRANT SELECT, SHOW VIEW ON `icaffeco_cms`.* TO 'icaffeco_legacy_ro'@'localhost';
FLUSH PRIVILEGES;
```

Ne menjati grantove naloga `icaffeco_cms` ako taj nalog koristi aktivni legacy PHP CMS za sopstveni upis. Umesto toga napraviti drugi nalog samo za Laravel.

## Sinhronizacija kataloga

Prvi `--apply` uspostavlja baseline za identične redove i prenosi samo nedostajuće ili bezkonfliktno izmenjene legacy redove:

```bash
php artisan legacy:catalog-sync --apply
```

Lokalno izmenjen red i istovremeno izmenjen legacy red postaju konflikt i ne prepisuju se automatski.
