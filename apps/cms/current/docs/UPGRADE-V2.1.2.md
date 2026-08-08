# Upgrade na v2.1.2 — Product Media & Notification Maintenance

## Polazna verzija

`v2.1.1`

## Šta se menja

- rotacija fotografija koristi Imagick ili GD i URL sa verzijom fajla, pa browser više ne prikazuje staru keširanu sliku;
- glavna fotografija bira se zvezdicom direktno preko slike;
- redosled fotografija menja se prevlačenjem na telefonu i računaru i automatski se čuva;
- forma prikazuje izabrane fotografije, veličinu, broj fajlova i upload progress u procentima;
- puna rezolucija može da se preuzme iz kartice kataloga, glavne galerije i lightbox prikaza;
- forma počinje redosledom Naziv artikla → Dodavanje slika → Specifikacije;
- polje diska podržava do osam uređaja i čuva ih kompatibilno kao `SSD + SSD + HDD`;
- dodata je komanda `app:product-media-doctor`.
- u glavnim e-mail podešavanjima dodata je podrazumevano isključena opt-in opcija za automatsko obaveštavanje svih aktivnih registrovanih korisnika kada se novi artikal prvi put objavi;
- obaveštenja o artiklima koriste postojeći pouzdani outbox, deduplikaciju po korisniku i artiklu, retry mehanizam i podesiv interval slanja;
- nacrti i neaktivni artikli ne šalju obaveštenje, a ponovno uređivanje već aktivnog artikla ne šalje duplikat;

Nema nove migracije baze.

## Postavljanje

```bash
cd /home/icaffeco/cms.ald1n.com
/usr/local/bin/php artisan app:backup-create --type=manual

# Raspakuj UPGRADE paket preko v2.1.1 instalacije.
composer install --no-dev --optimize-autoloader
/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan migrate --force

/usr/local/bin/php bin/product-media-ux-smoke.php
/usr/local/bin/php bin/product-announcement-smoke.php
/usr/local/bin/php artisan app:product-media-doctor
/usr/local/bin/php artisan app:order-emails-doctor

/usr/local/bin/php artisan app:release-check \
  --profile=stable \
  --render \
  --strict \
  --snapshot \
  --report=v2.1.2-stable-acceptance.json

/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
/usr/local/bin/php artisan optimize
```

Očekivani završetak je `RELEASE CHECK: STABLE READY`.

## Ručna provera

- izaberi više fotografija sa telefona i potvrdi preview i procenat slanja;
- rotiraj JPEG, PNG i WebP ulevo i udesno;
- postavi drugu fotografiju kao glavnu klikom na zvezdicu;
- promeni raspored prevlačenjem na telefonu i računaru;
- preuzmi punu rezoluciju iz kataloga i lightbox prikaza;
- sačuvaj artikal sa kombinacijom `SSD + SSD + HDD`;
- u Podešavanja → E-mail obaveštenja uključi obaveštavanje o novim artiklima i potvrdi da aktiviranje novog artikla priprema po jednu outbox poruku za svakog aktivnog korisnika, bez duplikata.
