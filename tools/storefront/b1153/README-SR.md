# B1153: veličina logotipa, logotip u e-mailu i prikaz kataloga sa izborom broja artikala

Autor paketa: Ald1n <https://ald1n.com>

Ovaj paket je napravljen i proveren samo u CI-ju. Na produkciji još nije pokrenut.

## Šta se instalira

26 fajlova pod `apps/cms/current`: tri nova i 23 izmenjena. Osnova je commit `af1e46a3` (kod koji je
živ posle B1152, Izveštaj 1118). Izvor je `origin/main` `d760bb2c`, spoj dva PR-a nad tom osnovom:

- PR 181: veličina logotipa svuda, logotip u zaglavlju e-maila, bez teksta pored otpremljenog logotipa.
- PR 182: prekidač mreža/lista u katalogu i izbor broja artikala po strani (20, 40, 80).

Izvor je jedan commit, jer oba PR-a menjaju `public-shop/layout.blade.php`. Nema novih ruta,
migracija, izmena `composer`-a, `.env`-a ni `config/`. Build to proverava: razlika tih direktorijuma
između `af1e46a3` i `d760bb2c` mora biti prazna. Broj ruta ostaje 568.

Pravilo je isto kao u B1152: instaliramo sve pod `apps/cms/current` osim `tests/` i `bin/`. Sedam
fajlova iz `tests/` (jedan `.cjs`, šest PHP testova) razlika sadrži, a mi ih ne instaliramo. Tačne
SHA-256 vrednosti svakog fajla (pre i posle) su u `b1153-digests.tsv` uz paket i u jednom označenom
bloku u koordinatoru (`B1153_PAYLOAD`).

| Grupa | Fajlovi | Vrsta |
| --- | --- | --- |
| A | `app/Support/MailBranding.php`, `resources/views/emails/partials/header.blade.php`, `resources/views/vendor/mail/html/header.blade.php` | novi |
| B | `SettingsService.php`, `PublicCatalogPresentation.php` | izmenjeni |
| C | `PublicStorefrontAppearance.php`, `PublicRetailCatalogService.php`, `PublicShopController.php`, `SiteAppearanceController.php`, `FlowPdfLayout.php`, `DocumentSettingsController.php` | izmenjeni |
| D | `BusinessDocumentPdfService.php`, `WarrantyCertificatePdfService.php`, `public/assets/css/ald1n-public-storefront.css` | izmenjeni |
| E | admin podešavanja (izgled, dokumenti), četiri stranice za prijavu, dva e-maila, `layouts/app`, `public-shop/catalog`, `AppServiceProvider.php`, na kraju `public-shop/layout` | izmenjeni |

Šta menja svaki deo:

- Veličine logotipa: šest mesta (zaglavlje prodavnice, podnožje, admin zaglavlje, stranice za
  prijavu, e-mail, PDF dokumenti), svako sa svojim opsegom. Sačuvana vrednost ostaje. Vrednost van
  opsega ili neispravna pada na podrazumevanu. Nove podrazumevane vrednosti su manje: zaglavlje
  prodavnice 32 px (bilo 38), podnožje 28, admin 32, prijava 48, e-mail 36, PDF 26 pt.
- Logotip u e-mailu: u zaglavlje HTML poruka ide logotip svetle teme, ali samo PNG ili JPG.
  Gmail i Outlook ne crtaju SVG. Bez takvog logotipa piše naziv sajta kao do sada. Adresa slike se
  gradi iz `APP_URL`, nikad iz zaglavlja `Host`.
- Pored otpremljenog logotipa nema teksta firme (zaglavlje prodavnice i admina, PDF dokumenti).
  PDF izveštaji i dalje imaju naziv firme pored logotipa.
- Katalog: prekidač mreža/lista i izbor 20, 40 ili 80 artikala po strani. Podrazumevano je 40
  (bilo 20). Nepoznata vrednost u upitu pada na podrazumevanu i nikad ne daje grešku.

Prekidač prodavnice i odobrenje trgovca samo čitamo. SMTP podešavanja ne čitamo i ne menjamo, probni
mejl ne šaljemo. Nijedna sačuvana vrednost veličine logotipa se ne piše. Mail transport poredimo
samo preko HMAC otiska u privatnom `state.json` (0600).

## Redosled i zašto

Pravilo: ono što niko još ne zove ide prvo, pozivalac ide uvek u kasniju grupu od onoga što poziva.
Build proverava svaku takvu vezu u isporučenim bajtovima, a koordinator odbija plan koji ih krši.

1. A: tri nova fajla. Niko ih ne imenuje, dok `AppServiceProvider.php` ne bude zamenjen.
2. B: dve biblioteke (`SettingsService::logoHeight`, `PublicCatalogPresentation::perPage`). Dodaju
   metode i konstante, ništa ne uklanjaju.
3. C: klase koje ih zovu. Nova `PublicStorefrontAppearance` na staroj `SettingsService` bila bi 500
   na svakoj stranici prodavnice. CI to namerno pravi, vidi grešku i vraća fajl.
4. D: dva PDF servisa (traže novi argument u `FlowPdfLayout`) i stilovi. Stilovi idu pre
   `public-shop/layout`, koji menja adresu stilova sa `?v=202611101` na `?v=202611141`. Tako Cloudflare
   ne može da zadrži stare stilove pod novom adresom.
5. E: pogledi. E-mail pogledi pozivaju `MailBranding`. Zato pre prvog fajla grupe E prođe puno
   čekanje od 125 s (izmereno: `max(realpath_cache_ttl, revalidate_freq) + 5`, računa se od poslednje izmene,
   pa uvek obuhvata i grupu A), a zatim pet puta zaredom privremena probna stranica koju servira
   **veb SAPI** (ne CLI) učitava `MailBranding` kroz isti composer autoloader koji koristi aplikacija
   i javlja iz kog fajla je došla i koji joj je SHA-256. Ista stranica čita `APP_URL` onako kako ga čita
   Laravel. Koordinator odbija grupu E ako klasa nije tačno na svom mestu ili ako dokaz nije prošao u
   ovom pokretanju. `AppServiceProvider.php` uključuje `resources/views/vendor/mail`, pa on čini novo zaglavlje
   e-maila živim. `public-shop/layout` ide poslednji.
6. Između četvrte i pete grupe (nekoliko minuta zbog punog čekanja) katalog već prikazuje 40 artikala po strani, a kontrole za broj artikala i prikaz još nisu vidljive. To je očekivano, nije greška.
7. Posle svake grupe ide pauza da veb radnici osveže fajlove, pa kratka provera života (`/up`,
   `/login`, pravna stranica, `/katalog`, prvi proizvod, `GET /tema` 405). Posle grupe E se dodaju
   `/katalog?per_page=20&view=list` i provera da gost na `/admin/settings/smtp` i dalje dobija
   `Location` na `https://cms.ald1n.com/login`. Ako provera padne, prvo se vraća grupa E.

Novi direktorijumi: `resources/views/emails/partials`, `resources/views/vendor`,
`resources/views/vendor/mail` i `resources/views/vendor/mail/html`. Nijedan ne postoji na produkciji.
Prave se jedan po jedan, spolja ka unutra, svaki zapisan u stanju pre nego što se napravi. Vraćanje ih
briše obrnutim redom, a samo ako su prazni. Prava su 0755.

Vraćanje ide obrnuto: grupa E (layout prvi), puno čekanje, D, pauza, C, pauza, B, puno čekanje, pa
na kraju tri nova fajla. Svaki fajl ide kroz privremeni fajl u istom direktorijumu, `fsync` i
`rename` (`link` za nov fajl). OPcache osvežavamo ciljano, po fajlu. Keš ruta ne postoji i ne pravimo ga.
Kompajlirani Blade pogledi se brišu sa `view:clear` na kraju instalacije i vraćanja, a pravi ih
sama aplikacija kad zatreba. Ne pravimo `view:cache`.

Pre-stanje je stablo posle B1152: runtime manifest plus B1146 do B1152, ukupno 8569 fajlova. Posle
instalacije stablo ima 8572 fajla. Pri građenju paketa digest svakog izmenjenog fajla (git blob iz
`af1e46a3`) mora da se poklopi sa digestom iz ranijeg sloja (B1152, pa B1151 i tako nadole, pa
manifest), a svih devet B1152 fajlova mora u `af1e46a3` imati tačno B1152 digest. Tako je dokazano
da je `af1e46a3` živo stanje. Poređenje sa živim stablom radi skripta na hostu, pre prve izmene.

## Pre pokretanja proverite sami

1. Postavite `b1153-operator.zip` i `b1153-operator.zip.sha256` u `/home/icaffeco`. Komanda mora
   da ispiše `b1153-operator.zip: OK`:

   ```
   cd /home/icaffeco && sha256sum -c b1153-operator.zip.sha256
   ```

2. Tačno jedan izveštaj `1118-B1152-INSTALLER-*.md`, a na njegovom kraju
   `B1152_FINAL=PASS_INSTALLED_APP_URL_ORIGIN_PIN_PDF_LOGO_RASTER_SHOP_STATE_UNCHANGED` i
   `EXIT=0`. Skripta traži da njegov SHA-256 bude
   `6000b386557fdc016e2789a80bcd5b68d33a3459311eb1ae791149f96e840edc`. Ako se host primerak
   razlikuje, kraj je bezbedan `HOLD` bez izmena. Isti digest ima i primerak u repozitorijumu
   (`docs/operations/1118-B1152-INSTALLER-20261113-193458.md`), računat nad git blobom. Windows
   kopija sa CRLF završecima daje drugu vrednost, pa je ne poredite.

   ```
   ls /home/icaffeco/ald1n-project/docs/operations/1118-B1152-INSTALLER-*.md
   sha256sum /home/icaffeco/ald1n-project/docs/operations/1118-B1152-INSTALLER-*.md
   tail -n 3 /home/icaffeco/ald1n-project/docs/operations/1118-B1152-INSTALLER-*.md
   ```

3. Stanje `~/.ald1n-b1152-state/481f6ec63f535aa680343e9e1059e122` kaže `INSTALLED_PASS`, a stablo ima
   8569 fajlova. Skripta to proverava sama, uključujući broj ruta (568).
4. U `incoming/` su pomoćni fajlovi iz ranijih paketa, nepromenjeni: `b1134-files.php`,
   `b1144-gates.php`, `b1144-probe.php`, `b1144-web.php`, `b1145-env.php`, `b1145-app-state.php`,
   `b1145-coordinator.php`, `b1146-probe.php`, `b1146-app-check.php`, `b1147-web.php`,
   `b1147-app-check.php`, `b1147-coordinator.php`, `b1148-web.php`, `b1148-app-check.php`,
   `b1148-coordinator.php`, `b1149-web.php`, `b1149-app-check.php`, `b1149-coordinator.php`,
   `b1150-web.php`, `b1150-app-check.php`, `b1150-coordinator.php`, `b1151-app-check.php`,
   `b1151-coordinator.php`, `b1152-app-check.php`, `b1152-coordinator.php` i `b1152-load-probe.php`.
   Ako neki fali ili je izmenjen, kraj je `HOLD_CODE=PACKAGE_FILE_INTEGRITY` i ništa nije promenjeno.
   Poslednja tri proverite odmah:

   ```
   sha256sum /home/icaffeco/ald1n-project/incoming/b1152-coordinator.php /home/icaffeco/ald1n-project/incoming/b1152-app-check.php /home/icaffeco/ald1n-project/incoming/b1152-load-probe.php
   ```

   Moraju da daju `721ffe3f7387ef0326c798d50217bcdefee4b0a2aaebed190b75debb9c0ae97f`,
   `96cb4550a6e2f0d5158788cdb62eff4fd4bf8d217ab0955d138bbe826f548262` i
   `457f3da1776654bf69930075e5c1851062e597a7e136217eae57a07ee6388a4b`. Ovo je dokazano:
   ZIP koji ste pokrenuli (CI run `43469605280`) u svom `b1152-package.sha256` ima baš ta tri digesta, a
   SHA-256 tog fajla je `b9287ffc90fd2878e3835f94e7e78046ad2b5455807d9c96d1e73a3fcc01dda8`, isto što piše u
   redu `CHECKSUMS_SHA256` Izveštaja 1118. Ista tri digesta imaju i git blobovi u repozitorijumu. Za
   `b1151-*` pomoćne fajlove važi pretpostavka iz B1152 (digesti blobova iz `origin/main`), koju CI
   proverava građenjem B1151 paketa.
5. `APP_URL` u `.env` je tačno `https://cms.ald1n.com` (bez kose crte na kraju). Skripta to proverava
   i staje bez izmena ako nije.
6. Prodavnica je uključena, saobraćaj je mali, a do backup-a (02:30 svaki dan, 03:10 nedeljom,
   Europe/Belgrade) ima dovoljno vremena.

### Prozor i trajanje

Skripta računa potreban prozor po pravilu `5 x čekanje + 2 x smoke + (4 x grupe - 2) x pauza +
(2 x grupe - 1) x provera života (do 210 s svaka, za server koji visi) + 5 x probni zahtev (do 30 s) +
300 s`, nikad manje od 30 minuta. Sa pet grupa, čekanjem od 125 s, pauzom od 3 s i granicom od 491 s
za jedan smoke to je oko 4000 s, nešto više od 66 minuta bez backup-a. To je gornja granica za server
koji visi. Očekivano trajanje je mnogo kraće. Tačnu vrednost pročitajte u `PREFLIGHT` izveštaju, polje
`timeBound.requiredWindowSeconds`. Ako backup upada u prozor, skripta staje pre prve izmene.

Sama instalacija traje kraće od prozora: dva puna čekanja od po 125 s, pauze između grupa i smoke
daju grubu procenu od 8 do 12 minuta. To je procena, nije mereno na produkciji.

## Šta pokrećete

```
unzip -o /home/icaffeco/b1153-operator.zip -d ~/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1153-installer.sh
```

Radnik ide u pozadini, a izveštaj se upisuje u
`docs/operations/<broj>-B1153-INSTALLER-<datum>-<vreme>.md`. Skripta sama uzima prvi slobodan broj
(prva moguća vrednost je 1119, ali svaki raniji `HOLD` troši broj, pa ne računajte na fiksan broj).
Ako se SSH prekine, posao se nastavlja. Pratite ga sa `tail -f` na izveštaj.

Cloudflare: ne treba nijedan purge i nijedna izmena podešavanja. Stranice prodavnice šalju
`private, no-store`, a adresa stilova se menja na novu (`?v=202611141`) koju ivica još nije videla.
Od pokretanja do kraja izveštaja ne menjajte nijedno Cloudflare podešavanje. Samo ako izveštaj
ispiše `OWNER_NOTICE_CSS`, očistite tačno navedene adrese.

## Šta proveravamo pre početka

Svih 8569 fajlova mora imati tačan digest (manifest plus B1146 do B1152), a svaki izmenjeni fajl
tačno staru verziju. Staje se i ako postoji keš ruta, ako je prodavnica isključena, ako radi artisan
komanda, ako je backup blizu, ako nema mesta na disku ili u kvoti, ako je composer autoload
autoritativan (nova klasa se tada ne bi našla), ako efektivni `config('app.url')` nije tačno
`https://cms.ald1n.com`, ako je konfiguracija keširana, ako u CLI-ju fali PHP ekstenzija ili ako javne
stranice nemaju `X-Robots-Tag: noindex`.

Novi kod proveravamo u CLI-ju: pre instalacije se `MailBranding` ne sme učitati, a `SettingsService`
i `PublicCatalogPresentation` ne smeju imati nove metode. Posle instalacije klasa se učitava (metoda
`logo()` postoji, ali je nikad ne zovemo), `logoHeight` drži vrednost u opsegu i vraća podrazumevanu za
neispravnu, `perPage` prihvata samo 20, 40 i 80, a ostale izmene čitamo kao tekst (pogledi
uključeni). Sve što su B1149 do B1152 postavili mora da postoji u oba stanja: nonce skripta,
galerija, potpisana ruta `/media/p/...`, ograničenje promene lozinke i pin `APP_URL`. Broj ruta mora biti 568
pre i posle, a živi `php artisan app:product-media-doctor` i dalje mora da prođe.

## Provera pogleda posle instalacije

Pre prvog smoke-a, odmah posle grupe E, koordinator samo čita: svaki od deset izmenjenih ili novih Blade pogleda (`layouts/app`, podešavanja izgleda i dokumenata, tri stranice za lozinku i aktivaciju, dva e-maila, oba zaglavlja e-maila) kompajlira se u memoriji i proverava se da je kompajliran PHP ispravan. Ništa se ne upisuje u direktorijum kompajliranih pogleda. Jednom se pozivaju `MailBranding::logo()` i `PublicStorefrontAppearance::freshBranding()` bez zahteva. Ako bilo šta padne, ide uobičajeno automatsko vraćanje. CI to dokazuje namerno pokvarenim pogledom.

## Smoke

Pun smoke ne zahteva prijavu, koristi samo GET i jedan HEAD. Nikad ne šaljemo formu i ne zovemo
krajnje tačke za lozinku. Zakačeni klijent iz B1150 ne dozvoljava upite `per_page` i `view`, pa za njih
postoji mala dodatna klasa u koordinatoru: isti host i TLS, samo GET i HEAD, samo `/katalog`, `per_page` samo cifre,
`view` samo mala slova. Novo u odnosu na B1152:

- `/up` 200, stranica za prijavu 200.
- `/katalog` 200. U novom stanju sadrži formu `shop-perpage` i prekidač `shop-view-group`, u starom ih
  nema.
- `/katalog?per_page=20&view=list` 200. U novom stanju je lista i 20 je izabrano, u starom se upit ignoriše.
- `/katalog?per_page=999` 200. U novom stanju pada na 40, u starom se ignoriše.
- U zaglavlju prodavnice slika logotipa ima atribut `height` (24 do 80). Sa podrazumevanom vrednošću je
  38 pre i 32 posle. Sačuvana vrednost, na primer 38, ostaje dok je ne promenite u Izgledu sajta.
- Nova adresa stilova `?v=202611141` sadrži pravila prekidača prikaza.
- Nijedna 5xx.

Sve ostalo iz B1152 ostaje: pravne stranice sa DENY, noindex i nonce skriptom, galerija, `private,
no-store`, logotip, potpisana ruta sa ETag i 304, gost na `/admin/settings/smtp`,
`/admin/settings/public-storefront` i `/admin/settings/documents` dobija 302 na prijavu na pravom
originu. Posle instalacije: `app:product-media-doctor` vraća kod 0, nema novih grešaka u
`laravel.log`, prekidač prodavnice je nepromenjen, odobrenje trgovca je nepromenjeno, status mail
transporta je nepromenjen, a svih 8572 fajla u stablu ima tačan digest.

### Šta nismo mogli da proverimo sa produkcije

Ne šaljemo mejl, ne generišemo PDF, ne prijavljujemo se i ne šaljemo lažni `Host`. Izgled e-maila u
Gmailu, visinu logotipa na telefonu i PDF zato proveravate vi, vidi dole. U CI-ju fixture renderuje
jednu notifikaciju u CLI procesu (mailer je `array`, ništa se ne šalje) i vidi logotip u zaglavlju.

## Ako nešto padne

Sami vraćamo stare fajlove obrnutim redom, uz puna čekanja gde je potrebno. Kraj je
`B1153_FINAL=HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`. Uporni 429, isključena prodavnica ili nestali
`noindex` tokom provera ostavljaju tačne fajlove i daju
`B1153_FINAL=HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: ista komanda kasnije ponavlja samo provere.

Vrednosti `B1153_FINAL`:

- `PASS_INSTALLED_LOGO_SIZES_MAIL_LOGO_CATALOG_VIEW_PER_PAGE_SHOP_STATE_UNCHANGED`: instalirano i
  provereno, `EXIT=0`.
- `PASS_ALREADY_INSTALLED`: ponovno pokretanje posle uspeha, ništa se ne menja.
- `PASS_ROLLED_BACK_BY_OWNER`: `--rollback` je završen.
- `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`: instalacija je pala i vraćena je na B1152 stanje.
- `HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: fajlovi su tačni, ali provere nisu mogle da se zaključe.
- `HOLD_ROLLBACK_INCOMPLETE`: vraćanje je stalo, vidi `RECOVERY_NOTE`.
- `HOLD`: staje se pre ijedne izmene.

## Šta radite vi posle (ručno, kad je izveštaj PASS)

1. E-mail. Pošaljite probni mejl sebi i otvorite ga u Gmailu. Logotip se vidi samo ako je logotip
   svetle teme PNG ili JPG. Sa SVG-om, WebP-om ili bez logotipa piše naziv sajta, kao do sada.
2. Zaglavlje prodavnice. Otvorite prodavnicu i pogledajte visinu logotipa. Već sačuvana vrednost
   (na primer 38) ostaje dok je ne promenite u Izgledu sajta. Nova podrazumevana vrednost 32
   važi samo tamo gde ništa nije sačuvano.
3. Katalog u listi na telefonu. Otvorite katalog, prebacite na listu i proverite izbor 20, 40 i 80.
4. PDF. Otvorite jedan PDF dokument i jedan PDF izveštaj i proverite logotip. Alat PDF ne generiše.
5. Admin. Otvorite bilo koju admin stranicu i jednom stranicu Izgled sajta. Instalacija proverava da se svi izmenjeni pogledi kompajliraju, ali je ne prijavljuje.
6. Queue radnici preuzimaju novo zaglavlje e-maila posle `queue:restart`, koji instalacija sama pokreće.
7. Ne pokrećite ponovo B1144 do B1152 ni njihov `--rollback` posle B1153.

## Vraćanje na staro

```
bash /home/icaffeco/ald1n-project/incoming/b1153-installer.sh --rollback
bash /home/icaffeco/ald1n-project/incoming/b1153-installer.sh --rollback --ignore-schedule
```

Druga komanda preskače samo proveru backup termina. Koristite je kad je backup blizu a vraćanje ne
može da čeka. Prodavnica ostaje u stanju u kom jeste. Kraj je `B1153_FINAL=PASS_ROLLED_BACK_BY_OWNER`.
Posle vraćanja ponovo važi adresa stilova `?v=202611101`. Ako je ivica za to vreme zadržala nove
stilove pod starom adresom, izveštaj imenuje tačne adrese za purge.

Ako vraćanje stane na pola, izveštaj ima `RECOVERY_NOTE` i blok od `bash -e <<'B1153_RECOVERY'`
do `B1153_RECOVERY`. Prvo pokrenite istu komandu još jednom. Blok nalepite ceo samo ako to ne
uspe. On vraća fajlove obrnutim redom, briše tri nova fajla i prazne nove direktorijume.

## Šta ne diramo

Prekidač prodavnice i odobrenje trgovca samo čitamo. SMTP podešavanja ne čitamo, ne menjamo i ne
testiramo. Nema migracija, ne menjamo rute, `.env` ni `config/`, ne pravimo keš ruta, OPcache ne
resetujemo nego ciljano osvežavamo fajlove iz paketa. `queue:restart` samo poručuje queue radnicima
da se ponovo pokrenu. Ne prijavljujemo se, ne šaljemo nijednu formu, ne zovemo krajnje tačke za
lozinku, ne šaljemo mejl i ne generišemo PDF. Sačuvane veličine logotipa ne pišemo. Prava
direktorijuma `~/.ald1n-b1134-retained` ne diramo.

## Šablon izveštaja (popunjavamo iz `<broj>-B1153-INSTALLER-*.md`)

| Polje | Vrednost |
| --- | --- |
| Broj izveštaja i vreme | prvi slobodan broj, datum i vreme početka |
| Izvor i osnova | izvor `d760bb2c` (PR 181 i PR 182), osnova `af1e46a3` |
| Pre-stanje | stablo posle B1152, 8569 fajlova |
| Ishod | `B1153_FINAL=...` i `EXIT=...` |
| `PREFLIGHT` blockers | prazno |
| `PER_FILE_BACKUP` | kopije 23 izmenjena fajla, 3 nova zabeležena kao odsutna |
| `OVERLAY_DIRECTORY_CREATED` | četiri direktorijuma, spolja ka unutra |
| `WEB_CLASS_LOAD_PROOF` | `MailBranding` učitan u veb SAPI-ju, pre grupe E |
| `ROUTES_CHECK` | `routeCount` 568, bez izmene |
| `FEATURE_CLI_CHECK` | sve nove činjenice true posle instalacije, B1152 činjenice true u oba stanja |
| `PUBLIC_HTTP_SMOKES` (INSTALLED) | `failed: []`, forma po strani i prekidač prikaza prisutni |
| `PRODUCT_MEDIA_DOCTOR` | `after-install`, `rc: 0`, `ready: true` |
| `MAIL_TRANSPORT_FINGERPRINT` | `status: MAIL_TRANSPORT_UNCHANGED` |
| `ACTIVE_TREE_AFTER_INSTALL` | 8572 fajla |
| `MERCHANT_APPROVAL` | `UNCHANGED` |
| Nije proveravano | Gmail izgled e-maila, PDF, prijava, telefon |
