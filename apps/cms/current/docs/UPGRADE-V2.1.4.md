# Prelazak na v2.1.4

Ovo izdanje se primenjuje preko **v2.1.3.3**.

## Promene

- Jedinstveni moderni sistem tastera kroz ceo CMS: ista visina, radius, padding, fokus i interakcije.
- Postojeće funkcionalne boje ostaju semantičke: plava za primarne, zelena za potvrde, amber za oprez, crvena za destruktivne i slate za sekundarne radnje.
- Novo polje `Model proizvoda` nalazi se posle brenda i linije i učestvuje u automatskom nazivu, SKU predlogu, pretrazi, kloniranju, snapshotu i API odgovoru.
- Primer naziva: `HP EliteBook 830 G8`.
- Trajno brisanje zahteva tačan SKU i nudi izbor brisanja lokalnog direktorijuma svih slika proizvoda i varijanti.
- Artikal sa porudžbinama, promenama lagera, ulazima robe ili popisima ne može se trajno obrisati; mora se arhivirati radi očuvanja poslovne istorije.

## Instalacija

```bash
cd /home/icaffeco/cms.ald1n.com
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
```

Raspakuj UPGRADE paket, zatim:

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr
/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan view:clear
/usr/local/bin/php artisan migrate --force
/usr/local/bin/php artisan app:cms-v2-1-4-doctor --repair
/usr/local/bin/php artisan app:catalog-settings-doctor --repair
/usr/local/bin/php bin/cms-v2.1.4-smoke.php
```

Napravi svež backup trenutne verzije pre strict provere:

```bash
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
/usr/local/bin/php artisan app:release-check --profile=stable --render --strict --snapshot --report=v2.1.4-stable-acceptance.json
/usr/local/bin/php artisan optimize
```

Očekivani završetak je `RELEASE CHECK: STABLE READY`.

## Migracija podataka

Migracija `2026_08_04_000036_add_product_model_and_name_templates_v2_1_4.php`:

1. dodaje nullable `products.model_name` sa indeksom;
2. prenosi model samo iz tačno prepoznatih starih polja `Model proizvoda`, bez mešanja sa modelom procesora;
3. dopunjava postojeće prilagođene šablone naziva tokenom `{model}`;
4. doctor `--repair` normalizuje višak razmaka u postojećim modelima;
5. ne briše nijedan poslovni podatak i ima namerno nedestruktivan rollback.

## Trajno brisanje

- Dostupno je samo korisniku koji ima pravo upravljanja konkretnim artiklom.
- Potvrđuje se tačnim SKU-om.
- Opcija za brisanje slika je uključena, ali korisnik može da je isključi.
- Lokalne slike se uklanjaju tek nakon uspešne DB transakcije.
- Legacy read-only slike se ne brišu fizički.
- Greška fajl sistema ne vraća obrisan artikal niti izaziva nekontrolisani DB rollback; ostavlja audit zapis za ručnu proveru storage-a.
