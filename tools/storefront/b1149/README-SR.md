# B1149: SVG logo, SMTP podešavanja i galerija proizvoda na produkciji

Autor paketa: Ald1n <https://ald1n.com>

## Šta se instalira

Instaliramo tačno 43 fajla iz spojene glave `7e748158`: 16 novih i 27 izmenjenih, svi pod
`apps/cms/current`. Nema migracija, nema izmena `composer`-a ni `config/`, testove ne instaliramo.
Dobijate:

- SVG logo uz sanitizaciju i novu rutu `GET /site-assets/{file}` (sandbox CSP, `nosniff`),
- stranicu Podešavanja > E-mail (SMTP) samo za superadministratora,
- galeriju proizvoda (novi CSS, renditions, ispravna orijentacija slika),
- novu temu prodavnice sa jednom skriptom pod nonce-om. Prodavnica više nije bez ijedne skripte:
  CSP za `/katalog`, proizvod, korpu i pravne stranice sada dozvoljava tačno jednu skriptu, onu
  koja nosi nonce te stranice. Bez JavaScript-a prekidač teme i dalje radi kroz `POST /tema`.

Skripta ne čita, ne menja i ne testira SMTP podešavanja i ne šalje mejl. Poredi samo heš
efektivnog mail transporta pre i posle instalacije. Otisak je HMAC sa nasumičnim ključem tog
pokretanja i živi samo u privatnom `state.json` (0600); u izveštaj ide samo status
`MAIL_TRANSPORT_UNCHANGED` ili `MAIL_TRANSPORT_CHANGED`. Ako se razlikuje,
vraća staro stanje.

## Redosled (obavezan)

A nove klase (SvgSanitizer, SiteLogoAssetService, SiteLogoFile, SMTP servisi, MailSettingsServiceProvider,
PublicProductImages i PublicProductImageFiles), B izmenjeni servisi i četiri komande, C kontroleri,
D `SecurityHeaders` pa `PublicNoScriptCsp`, E `routes/web.php`, F `bootstrap/app.php`, pa puno
čekanje i G CSS i pogledi (prvo galerijski i storefront CSS, da nijedna stranica ne linkuje novi
URL dok je CSS još star; zatim javni layout, katalog, proizvod, pa admin pogledi), na kraju H `bootstrap/providers.php`. Posle: `route:clear`, `view:clear`, `queue:restart`
i ciljano osvežavanje OPcache-a po fajlu. Keš ruta ne postoji i ne pravimo ga.

Drugačiji redosled ruši sajt. `routes/web.php` traži `SiteLogoAssetService::FILE_PATTERN`,
`layouts/app` traži rutu `admin.settings.smtp.index`, `PublicNoScriptCsp` traži
`PublicStorefrontAppearance::staleThemeCookie`, a `PublicProductImages` traži
`ProductImageDerivativeService::exifOrientation`. Ograničenje `smtp-test` definiše
`MailSettingsServiceProvider`, pa dugme za probni mejl radi tek kad se poslednji fajl postavi.

Pre-stanje je sadržaj `main` `eca7755c`, onakav kakvog su ga B1146, B1147 i B1148 instalirali.
Pri građenju paketa digest svakog od 27 izmenjenih fajlova (git blob iz `eca7755c`) mora da se
poklopi sa digestom iz runtime manifesta ili B1148 payload-a; inače se paket ne gradi. Poređenje
sa živim stablom na serveru radi tek skripta na hostu, pre prve izmene.

## Pre pokretanja proverite sami

1. Postavite `b1149-operator.zip` i `b1149-operator.zip.sha256` u `/home/icaffeco`. Komanda mora
   da ispiše `b1149-operator.zip: OK`:

   ```
   cd /home/icaffeco && sha256sum -c b1149-operator.zip.sha256
   ```

2. Tačno jedan izveštaj `1113-B1148-GLASS-OVERLAY-*.md`, a na njegovom kraju
   `B1148_FINAL=PASS_INSTALLED_GLASS_OVERLAY_SHOP_STATE_UNCHANGED` i `EXIT=0`:

   ```
   ls /home/icaffeco/ald1n-project/docs/operations/1113-B1148-GLASS-OVERLAY-*.md
   tail -n 3 /home/icaffeco/ald1n-project/docs/operations/1113-B1148-GLASS-OVERLAY-*.md
   ```

3. U `incoming/` su i dalje pomoćni fajlovi iz ranijih paketa, nepromenjeni: `b1134-files.php`,
   `b1144-gates.php`, `b1144-probe.php`, `b1144-web.php`, `b1145-env.php`, `b1145-app-state.php`,
   `b1145-coordinator.php`, `b1146-probe.php`, `b1146-app-check.php`, `b1147-web.php`,
   `b1147-app-check.php`, `b1147-coordinator.php`, `b1148-web.php`, `b1148-app-check.php` i
   `b1148-coordinator.php`. Ako neki fali ili je izmenjen, kraj je `HOLD_CODE=PACKAGE_FILE_INTEGRITY`
   i ništa nije promenjeno.
4. PHP na serveru ima `dom`, `libxml`, `fileinfo`, `mbstring` i `gd` ili `imagick`. Skripta to
   proverava u CLI-ju. Web PHP proverite sami (stranica sa `phpinfo` ili `php -m` za isti PHP
   profil), jer skripta ne može da ga pita.
5. Prodavnica je uključena, saobraćaj je mali, a do backup-a (02:30 svaki dan, 03:10 nedeljom,
   Europe/Belgrade). Potrebno vreme skripta sama izračuna: u izveštaju
   (`PREFLIGHT`) pročitajte `timeBound.requiredWindowSeconds` (oko 39 minuta, nikad manje od 30)
   i proverite da do 02:30 (nedeljom do 03:10) ima bar toliko vremena. Skripta staje pre izmene
   ako ga nema.

## Šta pokrećete

```
unzip -o /home/icaffeco/b1149-operator.zip -d /home/icaffeco/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1149-installer.sh
```

Radnik ide u pozadini, a izveštaj se upisuje u
`docs/operations/<broj>-B1149-INSTALLER-<datum>-<vreme>.md` (sledeći broj je 1115; 1114 je iskorišćen). Ako se SSH
prekine, posao se nastavlja (`tail -f` na izveštaj). Obično traje 12 do 16 minuta. Uz uporni 429
traje duže, a najgori slučaj zajedno sa vraćanjem je oko 40 minuta. Toliko vremena do sledećeg
backup-a skripta i traži, inače staje pre bilo kakve izmene.

## Šta proveravamo pre početka

Svih 8548 fajlova mora imati tačan digest (manifest + B1146 + B1147 + B1148), a svaki od 27
izmenjenih fajlova tačno staru verziju. Staje se i ako postoji keš ruta, ako je prodavnica
isključena, ako radi artisan komanda, ako je backup blizu, ako nema mesta na disku ili u kvoti,
ako je composer autoload autoritativan, ako APP_URL nije čist https origin, ako u CLI-ju fali
neka PHP ekstenzija ili ako javne stranice nemaju `X-Robots-Tag: noindex`.

## Smoke posle svake grupe i na kraju

Posle svake od prvih sedam grupa, kad se grupa ustali (pauza ili puno čekanje), radimo kratku
proveru života: `/up`, `/login`, jedna pravna stranica, `/katalog` sa noindex-om, prvi proizvod i
`GET /tema` 405. Tu ne proveravamo ništa novo. Ako padne, instalacija staje i vraća staro stanje.
Odgovor 429 je neodlučan i instalacija ide dalje. Pun smoke je na kraju, posle poslednjeg fajla.

## Posle izveštaja 1114

Izveštaj 1114 je stao pre bilo kakve izmene (`OLD_STATE_SMOKE_FAILED`). CSP zaglavlje je bilo
tačno. Pala je naša provera tela stranice: Cloudflare u svaki HTML ubacuje svoj učitavač sa
`window.__CF$cv$params`, a na stranicama sa e-mail adresom i dekoder pod `/cdn-cgi/scripts/`
(jedna ili dve skripte po stranici). Provera ih je brojala kao skripte sajta. Sada brojimo samo
skripte koje piše server; Cloudflare-ove ispisujemo kao `edgeInjectedScripts`. Raspakujte novi
ZIP preko starog i pokrenite istu komandu. Sledeći izveštaj je 1115.

Od pokretanja do kraja izveštaja 1115 ne menjajte Cloudflare podešavanja (Rocket Loader, Web
Analytics, Email Obfuscation). Svaka nova skripta koju Cloudflare ubaci tokom provera izgleda
kao skripta sajta i zaustavlja instalaciju ili je vraća na staro.

## Šta proveravamo na kraju (GET i jedan HEAD, bez POST-a, bez prijave)

- Javne stranice, katalog i proizvod: 200, noindex, jedna nonce skripta čiji se nonce poklapa sa
  zaglavljem, `Cache-Control` sa `private` i `no-store`, link ka galerijskom CSS-u.
- `/site-assets/<logo>` koji katalog linkuje: 200, `nosniff`, sandbox CSP (`default-src 'none'; sandbox`),
  noindex. Ako logo nije postavljen, red je `INFO` i ne smeta.
- Gost na `/admin/settings/smtp`: 302 na prijavu. Ne prijavljujemo se na produkciji.
- Storefront CSS: stari URL je `?v=202611091`, novi `?v=202611101`. Galerijski CSS je nov
  (`?v=202611106`) i pre instalacije ne postoji. `/tema` GET i HEAD 405.
- Rute `site-assets.show` i `admin.settings.smtp.index`, provajder, nove klase, status mail transporta (bez ikakvog heša u izveštaju),
  `laravel.log` bez novih grešaka i svih 8564 fajla sa tačnim digestom.

Ako nešto padne, sami vraćamo stare fajlove obrnutim redom: prvo `providers.php`, pa pogledi, pa
`view:clear` i puno čekanje pre uklanjanja `bootstrap/app.php` i ruta, a puno čekanje i pre
uklanjanja novih klasa. Kraj je `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`. Uporni 429, isključena
prodavnica ili nestali `noindex` tokom provera ostavljaju tačne fajlove i daju
`HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: ista komanda kasnije ponavlja samo provere.

Uspešan kraj je `B1149_FINAL=PASS_INSTALLED_STOREFRONT_ROLLOUT_SHOP_STATE_UNCHANGED` i `EXIT=0`.

## Šta radite vi posle

### 1. Renditions (odmah posle instalacije)

Dok to ne uradite, stare kvadratne renditions se odbijaju i prodavnica servira original. To je
sporije, ali ispravno.

Sve komande idu iz direktorijuma CMS-a i sa istim PHP binarnim kao instalacija:

```
cd /home/icaffeco/ald1n-project/apps/cms/current
PHP="$(command -v php)"
```

1. Obrada traži Imagick ili GD sa `imagewebp`. Proverite isto u CLI-ju i u web PHP-u:
   `"$PHP" -r "var_dump(extension_loaded('imagick')||function_exists('imagewebp'));"`
   Mora da piše `bool(true)`. Ako obrada javi `PRODUCT_IMAGE_OPTIMIZE_PROCESSOR=UNAVAILABLE`,
   nijedna slika nije obrađena: stanite i javite nam.
2. Probni prolaz bez izmena: `"$PHP" artisan app:product-image-optimize`
3. Backup postojećih renditions (iz istog direktorijuma):
   `tar -czf ~/product-images-optimized-$(date +%Y%m%d).tar.gz -C storage/app/public/products _optimized`
4. Prava obrada, sa niskim prioritetom:
   `nice -n 19 "$PHP" artisan app:product-image-optimize --apply --force --chunk=10`
5. Ako se prekine, pokrenite istu komandu ponovo. Uspeh dokazuje samo `FAILED=0` na kraju ispisa.
6. SQL `SELECT COUNT(*), AVG(file_size), MAX(file_size) FROM product_images;` meri veličine
   originala. Ne dokazuje da je obrada uspela; služi samo za procenu veličine posla.

### 2. Cloudflare

- Očistite `https://cms.ald1n.com/site-assets/*` (SVG logo se kešira po verziji sanitizera).
- Caching Level mora biti Standard, bez query-stripping za `/site-assets/*`.
- Za `/katalog*` ne sme postojati pravilo Cache Everything.
- Novi CSS ima nove URL-ove, pa ga ne morate čistiti. Ako stranica izgleda neuređeno ili po
  starom, očistite samo `https://cms.ald1n.com/assets/css/ald1n-public-storefront.css?v=202611101`
  i `https://cms.ald1n.com/assets/css/ald1n-public-gallery.css?v=202611106`.

### 3. Kolačići i trgovac

Dodajte `shop_theme` u tekst stranice Kolačići (čuva izabranu temu: svetla, tamna ili sistem), pa
ponovo potvrdite merchant approval. Skripta approval samo čita (red `MERCHANT_APPROVAL`,
`"ok":"UNCHANGED"`).

### 4. PDF dokumenti

Za PDF dokumente treba JPG ili PNG logo. SVG logo u PDF-u daje žuti kvadrat. Na sajtu radi sve.

### 5. Pogledajte

Otvorite `https://cms.ald1n.com/katalog` i jedan proizvod u privatnom prozoru, na telefonu i na
računaru. Prebacite temu Sistem, Svetla, Tamna.

## Vraćanje na staro

```
bash /home/icaffeco/ald1n-project/incoming/b1149-installer.sh --rollback
```

Prodavnica može ostati uključena. Ako je backup blizu, a vraćanje ne može da čeka, dodajte
`--ignore-schedule` (preskače samo proveru backup termina). Kraj je `PASS_ROLLED_BACK_BY_OWNER`.
Posle vraćanja očistite u Cloudflare-u `https://cms.ald1n.com/assets/css/ald1n-public-storefront.css?v=202611091`,
isti URL bez `?v=`, `https://cms.ald1n.com/assets/css/ald1n-public-gallery.css` i `/site-assets/*`.

Ako vraćanje stane na pola, izveštaj ima `RECOVERY_NOTE` i blok od `bash -e <<'B1149_RECOVERY'`
do `B1149_RECOVERY`. Prvo pokrenite istu komandu još jednom; blok nalepite ceo samo ako to ne
uspe.

## Šta ne diramo

Prekidač prodavnice i odobrenje trgovca samo čitamo. SMTP podešavanja ne čitamo, ne menjamo i ne
testiramo; probni mejl ne šaljemo. Nema migracija, ne menjamo `.env` ni `config/`, ne pravimo keš
ruta, OPcache ne resetujemo nego ciljano osvežavamo ovih 43 fajla, šablone samo brišemo
(`view:clear`). `queue:restart` samo poručuje queue radnicima da se ponovo pokrenu.

Posle B1149 ne pokrećite B1144, B1145, B1146, B1147 ni B1148, ni njihov `--rollback`.

## Šablon izveštaja (popunjavamo iz `1115-B1149-INSTALLER-*.md`)

| Polje | Vrednost |
| --- | --- |
| Broj izveštaja i vreme | 1115, datum i vreme početka |
| Glava izvora | `7e7481582fa0be3e95503a81b316be63836e508c` |
| Pre-stanje | main `eca7755c` |
| Ishod | `B1149_FINAL=...` i `EXIT=...` |
| `PREFLIGHT` blockers | prazno |
| `ENVIRONMENT_CLI_CHECK` | ekstenzije dom, libxml, fileinfo, mbstring, gd ili imagick |
| `PER_FILE_BACKUP` | 27 kopija, 16 novih fajlova zabeleženo kao odsutni |
| `OVERLAY_STEP` | 43 koraka, grupe A do H |
| `FEATURE_CLI_CHECK` | nove klase, rute i provajder prisutni |
| `MAIL_TRANSPORT_FINGERPRINT` | `status: MAIL_TRANSPORT_UNCHANGED` |
| `PUBLIC_HTTP_SMOKES` (INSTALLED) | `failed: []`, nonce skripta, private/no-store, galerija, logo, gost 302 |
| `ACTIVE_TREE_AFTER_INSTALL` | 8564 fajla |
| `MERCHANT_APPROVAL` | `UNCHANGED` |
| `FAILED=` iz renditions | 0 |
| Nije proveravano | izgled na telefonu, PDF sa SVG logoom, Imagick u web PHP-u |
