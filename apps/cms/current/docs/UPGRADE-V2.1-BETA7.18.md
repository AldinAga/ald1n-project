# Nadogradnja na v2.1.0-beta7.18

Ova verzija uvodi opšti sistem korelisanih izbora u katalogu i specifikacijama. Paket se postavlja preko `v2.1.0-beta7.17.2`.

## Šta se menja

- izbor brenda odmah prikazuje samo njegove linije proizvoda;
- ista korelacija radi na unosu artikla, administratorskoj listi i korisničkom katalogu;
- specifikaciono dropdown polje može zavisiti od drugog dropdown polja;
- dozvoljene veze se podešavaju u šifarniku bez nove izmene koda;
- izbor tipa artikla prikazuje samo specifikacione filtere dodeljene tom tipu;
- zavisno polje se automatski skriva kada izabrana roditeljska opcija nema nijednu povezanu vrednost;
- server ponovo proverava brend/liniju i sve veze opcija, pa se JavaScript ograničenja ne mogu zaobići ručnim zahtevom;
- kružne veze između polja nisu dozvoljene;
- kada se zavisno polje dodeli tipu artikla, automatski se dodeljuje i njegovo roditeljsko polje.

## Procesori

Polje `Procesor` je dropdown za stabilnu porodicu, uz posebno tekstualno polje `Tačan model procesora`.

Primer:

- porodica: `Intel Core i5`;
- tačan model: `1135G7`.

Ili:

- porodica: `AMD Ryzen 5`;
- tačan model: `PRO 5625U`.

Migracija dodaje porodice Intel Core/Core Ultra, AMD Ryzen/Ryzen AI, Qualcomm Snapdragon X/X2 i Apple M1–M5. Opcija `Ostalo / drugo` ostaje dostupna.

## Postojeći podaci

- postojeće select opcije se uvoze u novu strukturiranu tabelu;
- postojeći tekst procesora se deli samo kada je porodica pouzdano prepoznata;
- originalna tačna oznaka čuva se u `value_detail`;
- nepoznata vrednost se ne briše;
- postojeći brendovi dobijaju uobičajene laptop linije samo ako ista linija već ne postoji;
- migracija je ponovljiva i popravlja nedostajuće kolone, indekse i strane ključeve na MariaDB 10.11.

## Komande

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr

php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force

php artisan app:deployment-check
php artisan app:catalog-correlations-doctor
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php

php artisan optimize
```

Ako doctor prijavi delimičnu šemu, pokreni:

```bash
php artisan app:catalog-correlations-doctor --repair
```

Ako prijavi postojeći artikal sa neusaglašenim brendom i linijom, otvori taj artikal, ponovo izaberi brend i odaberi jednu od prikazanih linija.

## Podešavanje sopstvenih korelacija

U **Katalog → Šifarnici → Specifikaciona polja**:

1. oba polja postavi kao `select`;
2. na zavisnom polju izaberi roditeljsko polje;
3. unesi mapu u obliku:

```text
Intel => Intel Core i3 | Intel Core i5 | Intel Core i7 | Intel Core i9
AMD => AMD Ryzen 3 | AMD Ryzen 5 | AMD Ryzen 7 | AMD Ryzen 9
```

Za procesor je preporučeno da dropdown sadrži porodicu, a dodatno tekstualno polje tačnu oznaku modela.
