# B1154: redizajn, modul 1 (admin okvir i navigacija na novom CSS-u)

Autor paketa: Ald1n <https://ald1n.com>

Ovaj paket je napravljen i proveren samo u CI-ju. Na produkciji još nije pokrenut.

## Šta se instalira

Šest fajlova pod `apps/cms/current`: dva nova i četiri izmenjena. Osnova je commit `482d2fa5`
(kod koji je živ posle B1153, Izveštaj 1119). Izvor je `origin/main` `ad901a58`, spoj PR-ova 175, 176,
178 i 187: temelj redizajna i modul 1, admin okvir. Nema novih ruta, migracija, izmena `composer`-a,
`.env`-a ni `config/`. Build to proverava: razlika tih direktorijuma između `482d2fa5` i `ad901a58`
mora biti prazna. Broj ruta ostaje 568. Nema nove PHP klase.

Pravilo je isto kao u B1153: instaliramo sve pod `apps/cms/current` osim `tests/`. Jedan izuzetak:
`bin/static-check.php`. Taj fajl je u runtime manifestu (spisak fajlova protiv kog proveravamo stablo na
hostu), jer su `bin/` među korenima manifesta. Da ga nismo isporučili, stablo posle instalacije ne bi
bilo jednako `main`-u. Build proverava da je fajl u manifestu i da mu je živ digest jednak digestu iz
`482d2fa5`. Fajl je inertan: to je CLI skripta koju veb ne učitava. Svih 15 `tests/` fajlova razlike (osam novih ili
izmenjenih u `Browser`, pet u `Feature`, dva u `Unit`) se ne instalira.

| Grupa | Fajl | Vrsta | SHA-256 pre | SHA-256 posle |
| --- | --- | --- | --- | --- |
| A | `public/assets/css/ald1n-tokens.css` | novi | nema | `9fc9e850adee6325bce7edc75a4e70fe9d01a7076cfecdac80614147ad2c9701` |
| A | `public/assets/css/ald1n-admin.css` | novi | nema | `059d9f1de74f959da7f98df9f0efaee00d617cb8fc9853ae1a85ca2ab2ecbeb9` |
| B | `resources/views/components/icon.blade.php` | izmenjen | `6c6aae7557c4e5b0d279466afe2b836ae352d9c2eb524bba4a3c92df260ff4f8` | `429a179b98305d1b8a96d4c64051a213cbac6878b4f2ecaa01aa7e71ab3b3bee` |
| B | `public/assets/js/ux-runtime.js` | izmenjen | `22dcdea7c13d46d30125987a93ed83a6795943b3ef7ad74baefc0fc7bfbc75a8` | `26e57f8365b2949de6d16cfe4760637361b715540b328bf91057bd83e9b08fba` |
| C | `bin/static-check.php` | izmenjen | `b4fbe6546774d0ced64f8698fc8cc5c7b21cc84a360c37175c920951b116d33f` | `696323c24a54b31daa76a67b3b372714df1822acd11b82fda62c66af587ed590` |
| C | `resources/views/layouts/app.blade.php` | izmenjen | `3615a6113742b63457965d9acbe47996b045f983830913ff71d7ee272c6bbe3e` | `ed8607dcba5ec2926dd3ba68fc3e5e85e9810453fcdafeebce30b6a1b427c5ed` |

Iste vrednosti su u `b1154-digests.tsv` uz paket i u označenom bloku koordinatora (`B1154_PAYLOAD`).
Stablo posle instalacije ima 8574 fajla (8572 plus dva nova). Izvorni layout je `3615a611`, to jest
isti bajtovi koje je B1153 postavio. Time je potvrđeno da je `482d2fa5` živo stanje za tu putanju.

Šta menja svaki deo:

- Dva nova stilska fajla: tokeni boja i komponente admin okvira (prefiks `--a1-` i `.a1-`).
- Komponenta ikone dobija opcioni parametar `shell` (podrazumevano `false`).
- `ux-runtime.js` fokusira grešku i kad je element označen sa `data-a1-flash-error`.
- Layout: novo zaglavlje, glavna navigacija, pretraga, prekidač teme, mobilni meni. Povezuje oba
  stilska fajla i prosleđuje `shell` ikonama.
- `bin/static-check.php`: ugovori za novi layout (CLI, ne radi na vebu).

Sadržaj stranica ispod zaglavlja ne menjamo. Stari CSS (`app.css` i ostali) se i dalje učitava.

## Redosled i zašto

Layout je jedini fajl koji povezuje nove stilove i jedini koji prosleđuje `shell` ikonama. Svaka admin
stranica koristi taj layout. Zato:

1. A: dva nova stilska fajla. Stari layout ne povezuje nijedan, pa su inertni. Build proverava da nijedan
   fajl u stablu `482d2fa5` ne imenuje ta dva stila.
2. B: komponenta ikone i `ux-runtime.js`. Oba su kompatibilna sa STARIM layoutom. Dokaz sledi niže.
   Zato idu pre layouta, a ne zajedno s njim.
3. C: `bin/static-check.php`, pa layout poslednji. Layout je zadnji korak instalacije.

Pre prvog fajla grupe C prođe puno čekanje od 125 s (izmereno: `max(realpath_cache_ttl, revalidate_freq) + 5`,
računa se od poslednje izmene). Zatim koordinator pet puta zaredom traži oba stilska fajla kroz isti
HTTP klijent (veb SAPI, a u produkciji i Cloudflare ivica). Svaki zahtev nosi nov nasumičan marker,
pa odgovor nikad ne dolazi iz keša. Svaki odgovor mora biti 200, `text/css` i imati tačno SHA-256 iz paketa.
Ako to ne prođe, grupa C se ne dira. Ovo zamenjuje probu učitavanja klase iz B1153, jer B1154 nema novu klasu.

Posle svake grupe ide pauza da veb radnici osveže fajlove, pa kratka provera života: `/up`, `/login`,
pravna stranica, `/katalog`, prvi proizvod i `GET /tema` 405. Posle grupe C se dodaju oba stilska fajla
(200, `text/css`, tačan SHA-256) i gost na `/` i `/admin/settings/smtp`, koji mora dobiti 302 na `/login`
na `https://cms.ald1n.com`. Ako provera padne, prvo se vraća grupa C.

Vraćanje ide obrnuto: layout, `bin/static-check.php`, puno čekanje, ikona i `ux-runtime.js`, pauza, puno
čekanje, pa tek onda brisanje dva nova stilska fajla. Svaki fajl ide kroz privremeni fajl u istom
direktorijumu, `fsync` i `rename` (`link` za nov fajl). OPcache osvežavamo ciljano, po fajlu. Keš ruta ne
postoji i ne pravimo ga. Kompajlirani Blade pogledi se brišu sa `view:clear` na kraju instalacije i
vraćanja (isto kao u B1153). Ne pravimo `view:cache`. Novi direktorijumi ne nastaju: `public/assets/css`
već postoji.

## Dokaz da je grupa B kompatibilna sa starim layoutom

Build izvršava ove provere nad isporučenim bajtovima i staje ako bilo koja padne:

- Komponenta ikone: kad se vrate samo dve izmene (`'shell' => false` u `@props` i izraz klase), dobijaju se
  bajtovi stare komponente, znak po znak. Novi parametar `shell` je podrazumevano `false`, a `false` daje
  klasu `ui-icon`, istu kao pre. Stari layout nikad ne prosleđuje `shell`: nijedan pogled u stablu
  `482d2fa5` ga ne prosleđuje (pretražen `git grep`). Izlaz stare komponente i nove je isti.
- `ux-runtime.js`: razlika je jedan red. Selektor `.alert.error` postao je `.alert.error, [data-a1-flash-error]`.
  To je unija čiji je prvi član stari selektor. Stari layout nema element `data-a1-flash-error`, pa se
  fokusira isti element kao pre.
- U CI-ju (fixture) se posle grupe B u pravoj Laravel aplikaciji prikazuje stara kontrolna tabla sa novom
  ikonom i novim JS-om i vidi se da je izgled isti i da ikone zadržavaju `ui-icon`.

## Najgori prelazni prozor

Posle grupe A i grupe B admin korisnik ne vidi nikakvu promenu: stari layout, ista klasa ikona, isti JS.
Najgori prozor je samo kratak trenutak oko zamene layouta (grupa C):

- Layout se zamenjuje atomski (`rename`). Nijedna stranica nikad ne vidi pola layouta.
- Nekoliko sekundi posle zamene (OPcache osvežava fajl na oko 2 s, aplikacija sama ponovo kompajlira
  layout) uzastopna osvežavanja mogu da prikažu staro pa novo zaglavlje. Oba rade, jer stilovi već postoje.
- `view:clear` na kraju briše sve kompajlirane poglede. Prvo otvaranje svake stranice je zato sporije
  (nekoliko sekundi), isto kao posle B1153. Ništa nije pokvareno.
- Stranica koju je admin već imao otvorenu pre zamene ostaje na starom izgledu dok se ne osveži. Njeni
  zahtevi (forme, AJAX) ne zavise od layouta.
- Nova adresa stilova je `?v=<vreme izmene fajla>`, pa je ni Cloudflare ni pregledač nikad nisu videli.
  Pre instalacije se stilovi ne traže preko HTTP-a pod tom adresom, pa se nijedan 404 ne može keširati
  pod njom. Alat traži stilove samo sa nasumičnim markerom.

Prodavnica (storefront) nema veze sa ovim: njen layout, stilovi i skripte nisu u paketu. Alat proverava da
je digest layouta prodavnice (`a618a234…`) isti pre i posle.

## Pre pokretanja proverite sami

1. Postavite `b1154-operator.zip` i `b1154-operator.zip.sha256` u `/home/icaffeco`. Komanda mora
   da ispiše `b1154-operator.zip: OK`:

   ```
   cd /home/icaffeco && sha256sum -c b1154-operator.zip.sha256
   ```

2. Tačno jedan izveštaj `1119-B1153-INSTALLER-*.md`, a na njegovom kraju
   `B1153_FINAL=PASS_INSTALLED_LOGO_SIZES_MAIL_LOGO_CATALOG_VIEW_PER_PAGE_SHOP_STATE_UNCHANGED` i
   `EXIT=0`. Skripta traži da njegov SHA-256 bude
   `65b56ea73461bf3fb433476ad663c2bc10a3fcd9745dc3af9379ead8e4157c96`. Ako se host primerak
   razlikuje, kraj je bezbedan `HOLD` bez izmena. Isti digest ima i primerak u repozitorijumu
   (`docs/operations/1119-B1153-INSTALLER-20261114-230705.md`), računat nad git blobom (51685 bajtova, bez CR).
   Windows kopija sa CRLF završecima daje drugu vrednost, pa je ne poredite.

   ```
   ls /home/icaffeco/ald1n-project/docs/operations/1119-B1153-INSTALLER-*.md
   sha256sum /home/icaffeco/ald1n-project/docs/operations/1119-B1153-INSTALLER-*.md
   tail -n 3 /home/icaffeco/ald1n-project/docs/operations/1119-B1153-INSTALLER-*.md
   ```

3. Stanje `~/.ald1n-b1153-state/c7ea8ad81af83dcf47c561823f4b4cdf` kaže `INSTALLED_PASS`, a stablo ima
   8572 fajla. Skripta to proverava sama, uključujući broj ruta (568).
4. U `incoming/` su pomoćni fajlovi iz ranijih paketa, nepromenjeni: `b1134-files.php`,
   `b1144-gates.php`, `b1144-probe.php`, `b1144-web.php`, `b1145-env.php`, `b1145-app-state.php`,
   `b1145-coordinator.php`, `b1146-probe.php`, `b1146-app-check.php`, `b1147-web.php`,
   `b1147-app-check.php`, `b1147-coordinator.php`, `b1148-web.php`, `b1148-app-check.php`,
   `b1148-coordinator.php`, `b1149-web.php`, `b1149-app-check.php`, `b1149-coordinator.php`,
   `b1150-web.php`, `b1150-app-check.php`, `b1150-coordinator.php`, `b1151-app-check.php`,
   `b1151-coordinator.php`, `b1152-app-check.php`, `b1152-coordinator.php`, `b1152-load-probe.php`,
   `b1153-app-check.php`, `b1153-coordinator.php` i `b1153-load-probe.php`.
   Ako neki fali ili je izmenjen, kraj je `HOLD_CODE=PACKAGE_FILE_INTEGRITY` i ništa nije promenjeno.
   Poslednja tri proverite odmah:

   ```
   sha256sum /home/icaffeco/ald1n-project/incoming/b1153-coordinator.php /home/icaffeco/ald1n-project/incoming/b1153-app-check.php /home/icaffeco/ald1n-project/incoming/b1153-load-probe.php
   ```

   Moraju da daju `c040b3b2afe9f5401d3a93b3e73edc3894174d250db49cdd362ead8185584cdd`,
   `3049f0a741bc47f4f32a3f0314d4509a9f61b0a047a995f2a5ae1ac7e044ca19` i
   `4b110fa05ba8f18e192a3332525c56dc0e5a8217daef21d2299ef1494e8ace97`. Ovo je dokazano: ZIP koji ste
   pokrenuli (CI run `43637960668`, commit `ab967b45`) u svom `b1153-package.sha256` ima baš ta tri digesta,
   a SHA-256 tog fajla je `6a1169580386dd8d818d25f74d693ae1bd4f4d58b9905d045469e5670f8749f3`, isto što
   piše u redu `CHECKSUMS_SHA256` Izveštaja 1119. Ista tri digesta imaju i git blobovi u repozitorijumu.
   Za `b1151-*` pomoćne fajlove i dalje važi pretpostavka iz B1152 (digesti blobova iz `origin/main`),
   koju CI proverava građenjem B1151 paketa.
5. `APP_URL` u `.env` je tačno `https://cms.ald1n.com` (bez kose crte na kraju). Skripta to proverava
   i staje bez izmena ako nije.
6. Prodavnica je uključena, saobraćaj je mali, a do backup-a (02:30 svaki dan, 03:10 nedeljom,
   Europe/Belgrade) ima dovoljno vremena.

### Prozor i trajanje

Skripta računa potreban prozor po pravilu `5 x čekanje + 2 x smoke + (4 x grupe - 2) x pauza +
(2 x grupe - 1) x provera života (do 260 s svaka, za server koji visi) + 5 x dokaz stilova (do 60 s) +
300 s`, nikad manje od 30 minuta. Sa tri grupe, čekanjem od 125 s, pauzom od 3 s i granicom od 499 s
za jedan smoke to je oko 3550 s, nešto manje od 60 minuta bez backup-a. To je gornja granica za server
koji visi. Očekivano trajanje je mnogo kraće. Tačnu vrednost pročitajte u `PREFLIGHT` izveštaju, polje
`timeBound.requiredWindowSeconds`. Ako backup upada u prozor, skripta staje pre prve izmene.

Sama instalacija traje kraće: dva puna čekanja od po 125 s, pauza između grupa i smoke daju grubu
procenu od 6 do 10 minuta. To je procena, nije mereno na produkciji.

## Šta pokrećete

```
unzip -o /home/icaffeco/b1154-operator.zip -d ~/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1154-installer.sh
```

Radnik ide u pozadini, a izveštaj se upisuje u
`docs/operations/<broj>-B1154-INSTALLER-<datum>-<vreme>.md`. Skripta sama uzima prvi slobodan broj
(prva moguća vrednost je 1120, ali svaki raniji `HOLD` troši broj, pa ne računajte na fiksan broj).
Ako se SSH prekine, posao se nastavlja. Pratite ga sa `tail -f` na izveštaj.

Cloudflare: ne treba nijedan purge i nijedna izmena podešavanja. Od pokretanja do kraja izveštaja ne
menjajte nijedno Cloudflare podešavanje.

## Šta proveravamo pre početka

Svih 8572 fajla mora imati tačan digest (manifest plus B1146 do B1153), a svaki izmenjeni fajl tačno
staru verziju. Oba nova stilska fajla ne smeju postojati. Staje se i ako postoji keš ruta, ako je
prodavnica isključena, ako radi artisan komanda, ako je backup blizu, ako nema mesta na disku ili u
kvoti, ako je konfiguracija keširana, ako efektivni `config('app.url')` nije tačno
`https://cms.ald1n.com`, ako u CLI-ju fali PHP ekstenzija ili ako javne stranice nemaju
`X-Robots-Tag: noindex`.

Kod proveravamo u CLI-ju. Pre instalacije svih sedam oznaka iz ovog paketa mora biti `false`
(stilovi ne postoje, ikona nema `shell`, JS nema novi selektor, layout nema `a1-header__bar`). Posle
instalacije sve moraju biti `true`. Sve što su B1149 do B1153 postavili mora da postoji u oba stanja:
nonce skripta, galerija, potpisana ruta `/media/p/...`, ograničenje promene lozinke, pin `APP_URL`,
veličine logotipa, izbor broja artikala. Broj ruta mora biti 568 pre i posle, a živi
`php artisan app:product-media-doctor` i dalje mora da prođe.

## Provera pogleda posle instalacije

Pre prvog smoke-a, odmah posle grupe C, koordinator samo čita. Layout i komponenta ikone se kompajliraju
u memoriji (`Blade::compileString`) i proverava se da je kompajlirani PHP ispravan. Ništa se ne upisuje u
direktorijum kompajliranih pogleda. Ako bilo šta padne, ide uobičajeno automatsko vraćanje. CI to
dokazuje namerno pokvarenim pogledom.

## Smoke i šta NE proverava

Pun smoke ne zahteva prijavu, koristi samo GET i jedan HEAD. Nikad ne šaljemo formu i ne zovemo
krajnje tačke za lozinku. Klijent je isti kao u B1153. Za dva stilska fajla ne treba nikakvo proširenje
klijenta: zakačeni klijent iz B1150 već dozvoljava običnu putanju sa nasumičnim markerom. Novo u odnosu na B1153:

- Pre instalacije: oba stilska fajla ne postoje na disku, a HTTP odgovor nije 200 i nije 5xx. Layout ima
  digest iz `482d2fa5` (to jest B1153 bajtove).
- Posle instalacije: oba stilska fajla vraćaju 200, `text/css` i SHA-256 jednak paketu.
- `/login` 200, `/up` 200, `/katalog` 200. Layout prodavnice ima isti digest pre i posle.
- Gost na `/`, `/admin/settings/smtp`, `/admin/settings/public-storefront` i `/admin/settings/documents`
  dobija 302 na prijavu na pravom originu. Nijedna 5xx.

**Prijavljeni admin okvir ovaj alat NE proverava.** Alat se nikad ne prijavljuje. Zato ne vidimo kako
izgleda kontrolna tabla, meni, pretraga ni prekidač teme. U CI-ju fixture se prijavljuje na sintetičku
bazu i proverava prozore (posle A, posle B, posle C), ali to nije produkcija.

Sve ostalo iz B1153 ostaje: pravne stranice, galerija, `private, no-store`, logotip, potpisana ruta sa
ETag i 304. Posle instalacije: `app:product-media-doctor` vraća kod 0, nema novih grešaka u `laravel.log`,
prekidač prodavnice je nepromenjen, odobrenje trgovca je nepromenjeno, status mail transporta je
nepromenjen, a svih 8574 fajla u stablu ima tačan digest.

## Ako nešto padne

Sami vraćamo stare fajlove obrnutim redom, uz puna čekanja gde je potrebno. Kraj je
`B1154_FINAL=HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`. Uporni 429, isključena prodavnica ili nestali
`noindex` tokom provera ostavljaju tačne fajlove i daju
`B1154_FINAL=HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: ista komanda kasnije ponavlja samo provere.

Vrednosti `B1154_FINAL`:

- `PASS_INSTALLED_ADMIN_SHELL_NAVIGATION_CSS_AND_VIEWS_SHOP_STATE_UNCHANGED`: instalirano i provereno, `EXIT=0`.
- `PASS_ALREADY_INSTALLED`: ponovno pokretanje posle uspeha, ništa se ne menja.
- `PASS_ROLLED_BACK_BY_OWNER`: `--rollback` je završen.
- `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`: instalacija je pala i vraćena je na B1153 stanje.
- `HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: fajlovi su tačni, ali provere nisu mogle da se zaključe.
- `HOLD_ROLLBACK_INCOMPLETE`: vraćanje je stalo, vidi `RECOVERY_NOTE`.
- `HOLD`: staje se pre ijedne izmene.

## Šta radite vi posle (ručno, kad je izveštaj PASS)

Alat ne vidi prijavljeni okvir, pa ovo proveravate vi:

1. Otvorite kontrolnu tablu na računaru i na telefonu.
2. Otvorite svaku stavku glavnog menija.
3. Probajte pretragu.
4. Prebacite temu: svetla, tamna, sistemska.
5. Odjavite se i prijavite ponovo.
6. Otvorite po jednu stranicu svakog modula. Ispod zaglavlja mora izgledati kao pre.
7. Ako bilo šta nije u redu, odmah pokrenite komandu za vraćanje (niže).
8. Ne pokrećite ponovo B1144 do B1153 ni njihov `--rollback` posle B1154.

## Vraćanje na staro

```
bash /home/icaffeco/ald1n-project/incoming/b1154-installer.sh --rollback
bash /home/icaffeco/ald1n-project/incoming/b1154-installer.sh --rollback --ignore-schedule
```

Druga komanda preskače samo proveru backup termina. Koristite je kad je backup blizu a vraćanje ne
može da čeka. Prodavnica ostaje u stanju u kom jeste. Kraj je `B1154_FINAL=PASS_ROLLED_BACK_BY_OWNER`.
Posle vraćanja stari layout ne povezuje nijedan od dva stila, pa Cloudflare purge nije potreban.

Ako vraćanje stane na pola, izveštaj ima `RECOVERY_NOTE` i blok od `bash -e <<'B1154_RECOVERY'`
do `B1154_RECOVERY`. Prvo pokrenite istu komandu još jednom. Blok nalepite ceo samo ako to ne
uspe. On vraća fajlove obrnutim redom i briše dva nova stilska fajla.

## Šta ne diramo

Prekidač prodavnice i odobrenje trgovca samo čitamo. SMTP podešavanja ne čitamo, ne menjamo i ne
testiramo. Nema migracija, ne menjamo rute, `.env` ni `config/`, ne pravimo keš ruta, OPcache ne
resetujemo nego ciljano osvežavamo fajlove iz paketa. `queue:restart` samo poručuje queue radnicima
da se ponovo pokrenu. Ne prijavljujemo se, ne šaljemo nijednu formu, ne zovemo krajnje tačke za
lozinku, ne šaljemo mejl i ne generišemo PDF. Prava direktorijuma `~/.ald1n-b1134-retained` ne diramo.

## Napomena o `bin/`

`bin/product-media-ux-smoke.php` se razlikuje između `ef3f927b` (osnova manifesta) i `482d2fa5`.
Raniji instaleri nikad nisu isporučivali `bin/`, pa host verovatno drži verziju iz manifesta. Ovaj
paket taj fajl ne dira i ne proverava drugačije nego B1153: proveravamo ga prema manifestu, kao do sada.
`bin/static-check.php` je isti u manifestu i u `482d2fa5`, pa je njegov živ digest siguran.

## Šablon izveštaja (popunjavamo iz `<broj>-B1154-INSTALLER-*.md`)

| Polje | Vrednost |
| --- | --- |
| Broj izveštaja i vreme | prvi slobodan broj (najmanje 1120), datum i vreme početka |
| Izvor i osnova | izvor `ad901a58` (PR 175, 176, 178, 187), osnova `482d2fa5` |
| Pre-stanje | stablo posle B1153, 8572 fajla |
| Ishod | `B1154_FINAL=...` i `EXIT=...` |
| `PREFLIGHT` blockers | prazno |
| `PER_FILE_BACKUP` | kopije 4 izmenjena fajla, 2 nova zabeležena kao odsutna |
| `WEB_ASSET_PROOF` | pet krugova, oba stila 200, `text/css`, SHA-256 jednak paketu, pre grupe C |
| `ROUTES_CHECK` | `routeCount` 568, bez izmene |
| `FEATURE_CLI_CHECK` | sedam oznaka B1154 true posle instalacije, B1153 činjenice true u oba stanja |
| `VIEW_COMPILE_CHECK` | dva pogleda, `failed: []` |
| `PUBLIC_HTTP_SMOKES` (INSTALLED) | `failed: []`, `ADMIN_STYLESHEET` redovi PASS, `STOREFRONT_LAYOUT_DIGEST` PASS |
| `PRODUCT_MEDIA_DOCTOR` | `after-install`, `rc: 0`, `ready: true` |
| `MAIL_TRANSPORT_FINGERPRINT` | `status: MAIL_TRANSPORT_UNCHANGED` |
| `ACTIVE_TREE_AFTER_INSTALL` | 8574 fajla |
| `MERCHANT_APPROVAL` | `UNCHANGED` |
| Nije proveravano | prijavljeni admin okvir, telefon, Gmail, PDF |
