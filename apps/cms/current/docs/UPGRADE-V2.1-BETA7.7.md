# Upgrade na v2.1.0-beta7.7

Ova verzija se postavlja preko **v2.1.0-beta7.6** i sadrži novu, nedestruktivnu migraciju baze.

## Šta je novo

- u **Podešavanja → PDF dokumenti** dodat je poseban upload logotipa za poslovne PDF dokumente;
- PNG/WebP se, kada je PHP GD dostupan, normalizuju u JPEG radi pouzdanog ugrađivanja u interni PDF generator; JPG/JPEG radi i bez GD ekstenzije;
- ako poseban PDF logo nije postavljen, koristi se svetli logo sajta;
- PDF više ne prikazuje e-mail ili identitet subagenta u kartici kupca — prikazuju se samo podaci krajnjeg primaoca iz adrese isporuke;
- uvedeno je terminalno stanje **Kompletirana**, sa datumom, administratorom i završnom napomenom;
- kod plaćanja pouzećem dugme **Kompletiraj porudžbinu** automatski evidentira samo preostali neplaćeni saldo kao verifikovanu COD uplatu;
- kod drugih načina plaćanja porudžbina može biti kompletirana tek nakon potpunog izmirenja salda;
- kompletirana porudžbina je zaključana za dalje promene statusa, tracking-a, rokova, dodele i finansijskih stavki;
- lista porudžbina i izveštaji dobijaju poseban filter/status **Kompletirana**.

## Obavezne komande

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer dump-autoload --optimize --strict-psr
php artisan optimize:clear
php artisan migrate --force
php artisan app:deployment-check
php artisan app:operations-doctor
php bin/php-lint.php
php bin/autoload-check.php
php bin/static-check.php
php artisan optimize
```

## Provera nakon deploy-a

1. Otvori **Podešavanja → PDF dokumenti**, učitaj JPG/PNG/WebP logo i sačuvaj.
2. Generiši potvrdu porudžbine i proveri logo i podatke krajnjeg kupca.
3. Otvori porudžbinu sa statusom „Poslata” i plaćanjem pouzećem.
4. Klikni **Kompletiraj porudžbinu**.
5. Proveri da je status „Kompletirana”, saldo „Plaćeno” i da je nastala jedna verifikovana COD uplata za preostali iznos.
6. Proveri da više nema formi za dodatne operativne ili finansijske izmene.
