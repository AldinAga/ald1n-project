# B1151: čitljiv e-mail na pravnim stranicama, nacrt trgovca i ograničenje promene lozinke

Autor paketa: Ald1n <https://ald1n.com>

Ovaj paket je napravljen i proveren samo u CI-ju. Na produkciji još nije pokrenut.

## Šta se instalira

Šest fajlova pod `apps/cms/current`: jedan nov i pet izmenjenih. Izvor su dve glave nad commitom
`14e628ba` (to je B1150 koji je sada živ):

- PR 164, grana `codex/legal-email-visible-20261112`, glava `43d3ab6e`: samo `layout.blade.php`.
- PR 165, grana `codex/merchant-draft-password-throttle-20261112`, glava `ae00c5f6`: ostalih pet fajlova.

Nema novih ruta, migracija, izmena `composer`-a, `.env`-a ni `config/`. Testove i `bin/` ne
instaliramo. Tačne SHA-256 vrednosti svakog fajla (pre i posle) su u `b1151-digests.tsv` uz paket.

| Grupa | Fajl | Vrsta |
| --- | --- | --- |
| A | `app/Services/PasswordChangeThrottle.php` | nov |
| B | `app/Http/Controllers/Api/V1/AccountController.php` | izmenjen |
| B | `app/Http/Controllers/AccountController.php` | izmenjen |
| C | `resources/views/admin/settings/public-storefront.blade.php` | izmenjen |
| C | `app/Http/Controllers/Admin/PublicMerchantSettingsController.php` | izmenjen |
| D | `resources/views/public-shop/layout.blade.php` | izmenjen |

Šta menja svaki deo:

- Izgled prodavnice (D) stavlja telo stranice u par `<!--email_off-->`. Cloudflare tada ne pretvara
  e-mail trgovca u `[email protected]`.
- Podešavanja trgovca (C): kad odobrenje bude odbijeno, nacrt ostaje sačuvan. Zastareli ili
  ponovljeni zahtev ne prepisuje ništa i ne povlači odobrenje.
- Promena lozinke (A, B): jedan brojač pokušaja po korisniku, zajednički za veb formu i mobilni API.
  Pet pokušaja u 15 minuta, a pokušaj se broji pre poređenja lozinke.

Javne stranice, prekidač prodavnice i odobrenje trgovca samo čitamo. SMTP podešavanja ne čitamo i ne
menjamo, a probni mejl ne šaljemo. Mail transport poredimo samo preko HMAC otiska u privatnom
`state.json` (0600). U izveštaju je samo status `MAIL_TRANSPORT_UNCHANGED` ili
`MAIL_TRANSPORT_CHANGED`.

## Redosled

Klasa (A), pa dva kontrolera za nalog, prvo API pa veb (B). Pre sledeće grupe ide puno čekanje od
125 s, jer je C prva grupa sa prikazom. U grupi C idu jedan za drugim, bez pauze, prvo prikaz pa
kontroler. Novi prikaz uz stari kontroler je bezopasan. Novi kontroler uz stari prikaz nije: posle
odbijenog zastarelog slanja stari prikaz bi ponovo ispisao svež otisak stanja i sledeće slanje bi
sačuvalo zastarele unose. Na kraju ide izgled prodavnice (D), pa `route:clear`, `view:clear` i
`queue:restart`. Posle svake grupe radimo kratak smoke, a između grupa pauzu dok veb radnici
osveže fajlove.

Vraćanje ide obrnuto: prvo D, pa unutar C kontroler, pauza i tek onda prikaz, zatim `view:clear` i
puno čekanje, pa B, pa puno čekanje, pa klasa A kao poslednja. Svaki fajl ide kroz privremeni fajl u
istom direktorijumu, `fsync` i `rename` (`link` za nov fajl). OPcache osvežavamo ciljano, po fajlu.
Keš ruta ne postoji i ne pravimo ga.

Pre-stanje je stablo posle B1150: runtime manifest plus B1146 do B1150, ukupno 8566 fajlova. Posle
instalacije stablo ima 8567 fajlova. Pri građenju paketa digest svakog izmenjenog fajla (git blob iz
`14e628ba`) mora da se poklopi sa digestom iz ranijeg sloja, inače se paket ne gradi. Poređenje sa
živim stablom radi skripta na hostu, pre prve izmene.

## Pre pokretanja proverite sami

1. Postavite `b1151-operator.zip` i `b1151-operator.zip.sha256` u `/home/icaffeco`. Komanda mora
   da ispiše `b1151-operator.zip: OK`:

   ```
   cd /home/icaffeco && sha256sum -c b1151-operator.zip.sha256
   ```

2. Tačno jedan izveštaj `1116-B1150-INSTALLER-*.md`, a na njegovom kraju
   `B1150_FINAL=PASS_INSTALLED_PRODUCT_MEDIA_ROUTE_SHOP_STATE_UNCHANGED` i `EXIT=0`. Skripta traži da
   njegov SHA-256 bude
   `8977bdcf6ad127af400f55b1b938094bfd0b6496d3fb6ca4e68af9646f895e53`. To je digest primerka koji
   smo pročitali 12.11.2026. Ako se host primerak razlikuje, kraj je bezbedan `HOLD` bez izmena.

   ```
   ls /home/icaffeco/ald1n-project/docs/operations/1116-B1150-INSTALLER-*.md
   sha256sum /home/icaffeco/ald1n-project/docs/operations/1116-B1150-INSTALLER-*.md
   tail -n 3 /home/icaffeco/ald1n-project/docs/operations/1116-B1150-INSTALLER-*.md
   ```

3. Stanje `~/.ald1n-b1150-state/23909125054cabb8dac7a6e3c103b49a` kaže `INSTALLED_PASS`, a stablo ima
   8566 fajlova. Skripta to proverava sama, uključujući broj ruta (568) i rutu `media.product.signed`.
4. U `incoming/` su pomoćni fajlovi iz ranijih paketa, nepromenjeni: `b1134-files.php`,
   `b1144-gates.php`, `b1144-probe.php`, `b1144-web.php`, `b1145-env.php`, `b1145-app-state.php`,
   `b1145-coordinator.php`, `b1146-probe.php`, `b1146-app-check.php`, `b1147-web.php`,
   `b1147-app-check.php`, `b1147-coordinator.php`, `b1148-web.php`, `b1148-app-check.php`,
   `b1148-coordinator.php`, `b1149-web.php`, `b1149-app-check.php`, `b1149-coordinator.php`,
   `b1150-web.php`, `b1150-app-check.php` i `b1150-coordinator.php`. Ako neki fali ili je izmenjen,
   kraj je `HOLD_CODE=PACKAGE_FILE_INTEGRITY` i ništa nije promenjeno.
5. Prodavnica je uključena, saobraćaj je mali, a do backup-a (02:30 svaki dan, 03:10 nedeljom,
   Europe/Belgrade) ima dovoljno vremena.

### Prozor i trajanje

Skripta računa potreban prozor po pravilu `5 x čekanje + 2 x smoke + 14 x pauza + 7 x provera
života + 300 s`, nikad manje od 30 minuta. Sa čekanjem od 125 s to je oko 40 minuta bez backup-a.
Tačnu vrednost pročitajte u `PREFLIGHT` izveštaju, polje `timeBound.requiredWindowSeconds`. Ako
backup upada u prozor, skripta staje pre prve izmene.

Sama instalacija traje kraće od prozora. Dva puna čekanja od po 125 s, pauze između grupa i smoke
daju grubu procenu od 8 do 12 minuta. To je procena, nije mereno na produkciji.

## Šta pokrećete

```
unzip -o /home/icaffeco/b1151-operator.zip -d ~/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1151-installer.sh
```

Radnik ide u pozadini, a izveštaj se upisuje u
`docs/operations/<broj>-B1151-INSTALLER-<datum>-<vreme>.md`. Sledeći broj je 1117. Ako se SSH
prekine, posao se nastavlja. Pratite ga sa `tail -f` na izveštaj.

Od pokretanja do kraja izveštaja 1117 ne menjajte nijedno Cloudflare podešavanje (Rocket Loader,
Web Analytics, Email Obfuscation). Za B1151 nije potreban nijedan purge: stranice prodavnice šalju
`private, no-store`, a adresa CSS-a se ne menja.

## Šta proveravamo pre početka

Svih 8566 fajlova mora imati tačan digest (manifest plus B1146 do B1150), a svaki izmenjeni fajl
tačno staru verziju. Staje se i ako postoji keš ruta, ako je prodavnica isključena, ako radi artisan
komanda, ako je backup blizu, ako nema mesta na disku ili u kvoti, ako je composer autoload
autoritativan, ako APP_URL nije čist https origin, ako u CLI-ju fali PHP ekstenzija ili ako javne
stranice nemaju `X-Robots-Tag: noindex`.

Proveravamo i novu klasu: pre instalacije `PasswordChangeThrottle` ne sme da se učita i nijedan
kontroler ne sme da je traži, posle instalacije se učitava, ima API od pet pokušaja u 900 s i
kontejner je pravi. Kontroler koji traži klasu koje nema je greška u svakom stanju. Broj ruta mora
biti 568 pre i posle, a ruta `media.product.signed` mora da postoji u oba stanja. Živi
`php artisan app:product-media-doctor` i dalje mora da prođe.

Čitamo i koji cache store koriste ograničivači (`limiterStore` u izveštaju). Samo ime drajvera,
nijedna vrednost konekcije. Ni to ne zaustavlja instalaciju. Ako je `file` ili `database`, brojač
pokušaja promene lozinke upisuje se na disk ili u bazu. Fajl store ne garantuje strogo atomarno
uvećanje, pa nekoliko istovremenih pokušaja može da pređe granicu za nekoliko komada.

## Smoke

Posle svake grupe radimo kratku proveru života: `/up`, `/login`, jedna pravna stranica,
`/katalog` sa noindex-om, prvi proizvod i `GET /tema` 405. Ako padne, instalacija staje i vraća
staro stanje. Odgovor 429 je neodlučan i instalacija ide dalje.

Pun smoke ne zahteva prijavu, koristi samo GET i jedan HEAD. Nikad ne šaljemo formu i ne zovemo
krajnje tačke za lozinku.

- Sve što su B1149 i B1150 postavili ostaje, u starom i u novom stanju: nonce skripta, galerija,
  `private, no-store` na katalogu, logo, potpisana ruta `/media/p/...` (200, ETag, 304, goli 404 za
  loš potpis), gost na `/admin/settings/smtp` dobija 302 na prijavu.
- Novo: gost na `/admin/settings/public-storefront` dobija 302 na prijavu, u oba stanja.
- Novo: na svih osam pravnih stranica brojimo ostatke Cloudflare obfuskacije e-mail adrese
  (`__cf_email__`, `data-cfemail`, `/cdn-cgi/l/email-protection`). Brojimo samo pojave, adresu
  i `data-cfemail` vrednost nikad ne ispisujemo.
- Skripte koje ubacuje Cloudflare ostaju dozvoljene. Kad e-mail više nije obfuskiran, dekoder se ne
  ubacuje, pa broj tih skripti na pravnoj stranici može da padne sa 2 na 1. Skripta koju piše naša
  aplikacija mora biti tačno jedna.
- Posle instalacije: `app:product-media-doctor` vraća kod 0, nema novih grešaka u `laravel.log`,
  prekidač prodavnice je nepromenjen, odobrenje trgovca je nepromenjeno, status mail transporta je
  nepromenjen, a svih 8567 fajlova u stablu ima tačan digest.

### Red `LEGAL_EMAIL_VISIBILITY` je samo informacija

Red ne može da obori instalaciju. Obfuskaciju radi Cloudflare, ne mi, pa rezultat zavisi od toga
da li je e-mail uopšte prikazan i da li je obfuskacija uključena. Na produkciji smoke ide preko
javnog imena (u izveštaju 1116 je `route: PUBLIC_TLS_HOSTNAME`), bez zaobilaženja ivice, pa ne
možemo da uporedimo izvor i ivicu. Ni sami komentari `email_off` ne moraju da stignu do
pretraživača, jer ih ivica može ukloniti.

Zato dokaz stoji na izvornoj strani. Paket se ne gradi ako novi izgled nema tačno jedan par
`<!--email_off-->` posle `<body`, zatvoren pre skripte sa nonce-om, a stari izgled ga nema. Provera
stabla zatim potvrđuje da su ti bajtovi živi. Ishod na ivici je vaša provera (korak a ispod).

Vrednosti reda: `INFO_OLD_STATE_COUNT_RECORDED_NOT_JUDGED` (staro stanje, brojimo i pamtimo),
`INFO_OBFUSCATION_LEFTOVERS_GONE_FROM_ALL_LEGAL_PAGES`,
`INFO_OBFUSCATION_LEFTOVERS_REMAIN_THE_OWNER_MUST_CHECK_THE_E-MAIL_IN_A_PRIVATE_WINDOW`,
`INFO_NO_OBFUSCATED_E-MAIL_IN_THE_OLD_STATE_NOTHING_TO_COMPARE_...` (e-mail nije prikazan ili je
obfuskacija isključena) i `INFO_INCOMPLETE_...` (neka stranica nije pročitana).

## Ako nešto padne

Sami vraćamo stare fajlove obrnutim redom, uz `view:clear` i puna čekanja gde je potrebno. Kraj je
`B1151_FINAL=HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`. Uporni 429, isključena prodavnica ili nestali
`noindex` tokom provera ostavljaju tačne fajlove i daju
`B1151_FINAL=HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: ista komanda kasnije ponavlja samo provere.

Vrednosti `B1151_FINAL`:

- `PASS_INSTALLED_LEGAL_EMAIL_MERCHANT_DRAFT_PASSWORD_THROTTLE_SHOP_STATE_UNCHANGED`: instalirano i
  provereno, `EXIT=0`.
- `PASS_ALREADY_INSTALLED`: ponovno pokretanje posle uspeha, ništa se ne menja.
- `PASS_ROLLED_BACK_BY_OWNER`: `--rollback` je završen.
- `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`: instalacija je pala i vraćena je na B1150 stanje.
- `HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: fajlovi su tačni, ali provere nisu mogle da se zaključe.
- `HOLD_ROLLBACK_INCOMPLETE`: vraćanje je stalo, vidi `RECOVERY_NOTE`.
- `HOLD`: staje se pre ijedne izmene.

## Šta radite vi posle

**a) E-mail.** Otvorite `https://cms.ald1n.com/podaci-o-trgovcu` i jednu drugu pravnu stranicu u
privatnom prozoru. E-mail podrške mora biti čitljiv, ne `[email protected]`. Ako je i dalje skriven,
javite nam. Ne vraćajte ništa unazad zbog toga: to je ponašanje ivice, a ne greška fajlova.

**b) Lozinka.** Ništa ne morate da proveravate. Ograničenje nismo pozivali, jer to traži prijavu i
pokušaj lozinke. Ako ipak želite, jedan pogrešan pokušaj promene lozinke treba da vrati običnu
poruku o pogrešnoj lozinci. Pet uzastopnih pogrešnih zaključava promenu na 15 minuta, i to je
očekivano.

**c) Podešavanja trgovca.** Prijavljeni u admin otvorite
`https://cms.ald1n.com/admin/settings/public-storefront` i sačuvajte nacrt bez odobrenja. Nacrt
mora ostati sačuvan. Ovo je opciono.

**d) Ne pokrećite ponovo** B1144 do B1150 ni njihov `--rollback` posle B1151.

## Vraćanje na staro

```
bash /home/icaffeco/ald1n-project/incoming/b1151-installer.sh --rollback
bash /home/icaffeco/ald1n-project/incoming/b1151-installer.sh --rollback --ignore-schedule
```

Druga komanda preskače samo proveru backup termina. Koristite je kad je backup blizu a vraćanje ne
može da čeka. Prodavnica ostaje u stanju u kom jeste. Kraj je `B1151_FINAL=PASS_ROLLED_BACK_BY_OWNER`.

Ako vraćanje stane na pola, izveštaj ima `RECOVERY_NOTE` i blok od `bash -e <<'B1151_RECOVERY'`
do `B1151_RECOVERY`. Prvo pokrenite istu komandu još jednom. Blok nalepite ceo samo ako to ne
uspe.

## Šta ne diramo

Prekidač prodavnice i odobrenje trgovca samo čitamo. SMTP podešavanja ne čitamo, ne menjamo i ne
testiramo. Nema migracija, ne menjamo rute, `.env` ni `config/`, ne pravimo keš ruta, OPcache ne
resetujemo nego ciljano osvežavamo fajlove iz paketa, a šablone samo brišemo (`view:clear`).
`queue:restart` samo poručuje queue radnicima da se ponovo pokrenu. Ne prijavljujemo se, ne šaljemo
nijednu formu i ne zovemo krajnje tačke za promenu lozinke. Prava direktorijuma
`~/.ald1n-b1134-retained` ne diramo.

## Šablon izveštaja (popunjavamo iz `1117-B1151-INSTALLER-*.md`)

| Polje | Vrednost |
| --- | --- |
| Broj izveštaja i vreme | 1117, datum i vreme početka |
| Glave izvora | `43d3ab6e` (PR 164) i `ae00c5f6` (PR 165), osnova `14e628ba` |
| Pre-stanje | stablo posle B1150, 8566 fajlova |
| Ishod | `B1151_FINAL=...` i `EXIT=...` |
| `PREFLIGHT` blockers | prazno |
| `PER_FILE_BACKUP` | kopije 5 izmenjenih fajlova, 1 novi zabeležen kao odsutan |
| `ROUTES_CHECK` | `routeCount` 568, bez izmene |
| `FEATURE_CLI_CHECK` | `throttleApi` i `throttleResolvable` true posle instalacije |
| `PUBLIC_HTTP_SMOKES` (INSTALLED) | `failed: []`, `LEGAL_EMAIL_VISIBILITY` je INFO red |
| `PRODUCT_MEDIA_DOCTOR` | `after-install`, `rc: 0`, `ready: true` |
| `MAIL_TRANSPORT_FINGERPRINT` | `status: MAIL_TRANSPORT_UNCHANGED` |
| `ACTIVE_TREE_AFTER_INSTALL` | 8567 fajlova |
| `MERCHANT_APPROVAL` | `UNCHANGED` |
| Nije proveravano | pokušaj promene lozinke, izgled na telefonu, Android aplikacija |
