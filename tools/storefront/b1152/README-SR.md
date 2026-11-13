# B1152: svi generisani URL-ovi na origin iz APP_URL i zaštita PDF loga

Autor paketa: Ald1n <https://ald1n.com>

Ovaj paket je napravljen i proveren samo u CI-ju. Na produkciji još nije pokrenut.

## Šta se instalira

Devet fajlova pod `apps/cms/current`: dva nova i sedam izmenjenih. Osnova je commit `7e22afd4`
(kod koji je živ posle B1151, isto što je na `origin/main` `fc954517`). Izvor su dve glave nad tom
osnovom:

- PR 171, grana `codex/url-origin-pin-20261113`, glava `8505b110` (nad PR 168, glava `b49e33c5`):
  prvih pet fajlova u tabeli.
- PR 170, grana `codex/pdf-logo-raster-fallback-20261113`, glava `cfe97253`: poslednja četiri
  pre `bootstrap/app.php`.

Nema novih ruta, migracija, izmena `composer`-a, `.env`-a ni `config/`. Testove, `bin/` i radne
tokove ne instaliramo. Tačne SHA-256 vrednosti svakog fajla (pre i posle) su u `b1152-digests.tsv`
uz paket i u jednom označenom bloku u koordinatoru (`B1152_PAYLOAD`).

| Grupa | Fajl | Vrsta |
| --- | --- | --- |
| A | `app/Support/AppUrlOrigin.php` | nov |
| A | `app/Http/Middleware/PinAppUrlOrigin.php` | nov |
| B | `app/Notifications/ResetPasswordNotification.php` | izmenjen |
| B | `app/Http/Controllers/AccountController.php` | izmenjen |
| C | `app/Services/Pdf/FlowPdfLayout.php` | izmenjen |
| C | `app/Services/SiteLogoAssetService.php` | izmenjen |
| C | `app/Http/Controllers/Admin/DocumentSettingsController.php` | izmenjen |
| C | `app/Http/Controllers/Api/V1/Admin/SystemSettingsController.php` | izmenjen |
| D | `bootstrap/app.php` | izmenjen |

Šta menja svaki deo:

- Origin (A, D): svaki apsolutni URL koji se pravi tokom veb ili API zahteva počinje sa originom iz
  `APP_URL`, a ne sa zaglavljem `Host`. Linkovi koji se čuvaju ili šalju mejlom ne mogu da se
  otruju lažnim hostom. Zahtevi se ne odbijaju: nepoznat host i dalje radi.
- Reset lozinke (B): link u mejlu se gradi iz `config('app.url')`. Veb forma za promenu lozinke
  prima najviše 4096 znakova u polju trenutne lozinke.
- PDF logo (C): PDF crta samo logo koji po sadržaju počinje JPEG ili PNG potpisom, nikad SVG.
  Pregled loga u podešavanjima dokumenata više ne pada na mrtav `/storage` link.

Prekidač prodavnice i odobrenje trgovca samo čitamo. Nijedan od ovih devet fajlova ne dira pravni
tekst. SMTP podešavanja ne čitamo i ne menjamo, probni mejl ne šaljemo, reset lozinke ne tražimo.
Mail transport poredimo samo preko HMAC otiska u privatnom `state.json` (0600). U izveštaju je samo
status `MAIL_TRANSPORT_UNCHANGED` ili `MAIL_TRANSPORT_CHANGED`.

## Zašto je `bootstrap/app.php` poslednji

`bootstrap/app.php` imenuje `PinAppUrlOrigin::class`. Ako bi taj fajl bio živ pre nego što se obe
nove klase mogu učitati, svaki zahtev bi bio 500, i `/up` takođe. Zato:

1. Prvo idu obe klase (A). Dok `bootstrap/app.php` ne bude zamenjen, niko ih ne poziva.
2. Zatim B i C, pa puno čekanje od 125 s (izmereno: `max(realpath_cache_ttl, revalidate_freq) + 5`).
   Čekanje se računa od poslednje izmene u stablu, pa uvek obuhvata i grupu A.
3. Posle čekanja pet puta zaredom (svaki zahtev mora da prođe) privremena probna stranica, koju servira
   **veb SAPI** (ne CLI), učitava obe klase kroz isti composer autoloader koji koristi aplikacija i javlja
   iz kog fajla su došle i koji im je SHA-256. Ista stranica čita `APP_URL` onako kako ga čita Laravel
   (vrednost iz okruženja servera ima prednost, inače linije `APP_URL` iz `.env`; BOM i CR se
   uklanjaju), bez podizanja aplikacije. Više linija sa različitim originima je dvosmisleno
   (`LOAD_PROBE_APP_URL_AMBIGUOUS_IN_DOTENV`), vrednost sa `${` se odbija
   (`LOAD_PROBE_APP_URL_INTERPOLATION`). Kad `APP_URL` nema nigde, važi podrazumevana vrednost iz
   `config/app.php`, koju čitamo kao tekst jednim strogim izrazom (izvor `CONFIG_DEFAULT`); ako se ne
   može pročitati jednoznačno, to je neslaganje. Ista provera (bez klasa) radi i u preflight-u, pa je
   neslaganje `HOLD` pre ijedne izmene. Probna stranica javlja samo da li je tačno `https://cms.ald1n.com` i origin (bez putanje i podataka za
   prijavu). Ako veb radnik vidi drugačiju vrednost nego CLI, staje se pre `bootstrap/app.php` i vraćaju se
   A do C. Probna stranica ima token u zaglavlju, radi samo za GET, proverava Host, ističe i odmah se
   briše. Ništa ne poziva i ne podiže aplikaciju.
4. Tek tada se piše `bootstrap/app.php`. Koordinator odbija taj korak ako neka nova klasa nije tačno
   na svom mestu ili ako dokaz iz tačke 3 nije prošao u ovom pokretanju.
5. Posle D ide jedna pauza da veb radnici osveže fajl, pa odmah smoke (`/up`, `/login`, jedna pravna
   stranica, `/katalog`, `GET /tema` 405) i odmah provera pina: gost na `/admin/settings/smtp` mora dobiti
   `Location` na `https://cms.ald1n.com/login`. Pogrešan origin pada kao 5xx. Ako padne, prvi se vraća `bootstrap/app.php`, pre bilo
   čega drugog. Tek posle toga idu `route:clear`, `view:clear` i `queue:restart`.

Vraćanje ide obrnuto: prvo `bootstrap/app.php`, puno čekanje, pa C, pauza, pa B, puno čekanje, pa
obe klase kao poslednje. Svaki fajl ide kroz privremeni fajl u istom direktorijumu, `fsync` i
`rename` (`link` za nov fajl). OPcache osvežavamo ciljano, po fajlu. Keš ruta ne postoji i ne
pravimo ga.

Pre-stanje je stablo posle B1151: runtime manifest plus B1146 do B1151, ukupno 8567 fajlova. Posle
instalacije stablo ima 8569 fajlova. Pri građenju paketa digest svakog izmenjenog fajla (git blob iz
`7e22afd4`) mora da se poklopi sa digestom iz ranijeg sloja i sa blobom iz `origin/main`, a oba
moraju da budu tačno oni digesti koje su recenzenti potvrdili. Inače se paket ne gradi. Poređenje sa
živim stablom radi skripta na hostu, pre prve izmene.

## Pre pokretanja proverite sami

1. Postavite `b1152-operator.zip` i `b1152-operator.zip.sha256` u `/home/icaffeco`. Komanda mora
   da ispiše `b1152-operator.zip: OK`:

   ```
   cd /home/icaffeco && sha256sum -c b1152-operator.zip.sha256
   ```

2. Tačno jedan izveštaj `1117-B1151-INSTALLER-*.md`, a na njegovom kraju
   `B1151_FINAL=PASS_INSTALLED_LEGAL_EMAIL_MERCHANT_DRAFT_PASSWORD_THROTTLE_SHOP_STATE_UNCHANGED` i
   `EXIT=0`. Skripta traži da njegov SHA-256 bude
   `4787c4b3b2901ca3fc58a5e87a86c5b402dcc769235d872923b53ab907b5f471`. Ako se host primerak
   razlikuje, kraj je bezbedan `HOLD` bez izmena.

   ```
   ls /home/icaffeco/ald1n-project/docs/operations/1117-B1151-INSTALLER-*.md
   sha256sum /home/icaffeco/ald1n-project/docs/operations/1117-B1151-INSTALLER-*.md
   tail -n 3 /home/icaffeco/ald1n-project/docs/operations/1117-B1151-INSTALLER-*.md
   ```

3. Stanje `~/.ald1n-b1151-state/bce891b87709f9f0a8f722c55c50de69` kaže `INSTALLED_PASS`, a stablo ima
   8567 fajlova. Skripta to proverava sama, uključujući broj ruta (568).
4. U `incoming/` su pomoćni fajlovi iz ranijih paketa, nepromenjeni: `b1134-files.php`,
   `b1144-gates.php`, `b1144-probe.php`, `b1144-web.php`, `b1145-env.php`, `b1145-app-state.php`,
   `b1145-coordinator.php`, `b1146-probe.php`, `b1146-app-check.php`, `b1147-web.php`,
   `b1147-app-check.php`, `b1147-coordinator.php`, `b1148-web.php`, `b1148-app-check.php`,
   `b1148-coordinator.php`, `b1149-web.php`, `b1149-app-check.php`, `b1149-coordinator.php`,
   `b1150-web.php`, `b1150-app-check.php`, `b1150-coordinator.php`, `b1151-app-check.php` i
   `b1151-coordinator.php`. Ako neki fali ili je izmenjen, kraj je `HOLD_CODE=PACKAGE_FILE_INTEGRITY`
   i ništa nije promenjeno. Poslednja dva proverite odmah:

   ```
   sha256sum /home/icaffeco/ald1n-project/incoming/b1151-coordinator.php /home/icaffeco/ald1n-project/incoming/b1151-app-check.php
   ```

   Moraju da daju `becac22c0582f69338a27bf5acd82aa3e6583d846f939c9b4bb079300fee8872` i
   `6842828a326e0397022479c6cd6a46ebf6da04a40326b7c03be5facffe8182ec`. To su digesti git bloba iz
   `origin/main` i krajnje glave grane B1151 (`934c49cc`, 12.11.2026 20:58). Nismo mogli da potvrdimo da
   je ZIP koji ste pokrenuli nosio baš te bajtove, pa je ovo jedino mesto gde se pretpostavka proverava.
   Ako koordinator na hostu daje `827cafc804efeb583cb4b88815451a2766013c2ccc36e028d841c25bd7ae3284`,
   ZIP je građen iz ranijeg commita `6ca4579f` (razlika je samo komentar). Tada je kraj bezbedan
   `HOLD_CODE=PACKAGE_FILE_INTEGRITY` bez izmena, a pin se menja u paketu, ne na hostu.
5. `APP_URL` u `.env` je tačno `https://cms.ald1n.com` (bez kose crte na kraju). Skripta to proverava
   i staje bez izmena ako nije.
6. Prodavnica je uključena, saobraćaj je mali, a do backup-a (02:30 svaki dan, 03:10 nedeljom,
   Europe/Belgrade) ima dovoljno vremena.

### Prozor i trajanje

Skripta računa potreban prozor po pravilu `5 x čekanje + 2 x smoke + 14 x pauza + 7 x provera
života (do 180 s svaka, za server koji visi) + 5 x probni zahtev (do 30 s) + 300 s`, nikad manje od
30 minuta. Sa čekanjem od 125 s, pauzom od 3 s i granicom od 485 s za jedan smoke to je 3347 s, oko 55 minuta
bez backup-a (to je gornja granica za server koji visi, ne očekivano trajanje).
Tačnu vrednost pročitajte u `PREFLIGHT` izveštaju, polje `timeBound.requiredWindowSeconds`. Ako
backup upada u prozor, skripta staje pre prve izmene.

Sama instalacija traje kraće od prozora: dva puna čekanja od po 125 s, pauze između grupa i smoke
daju grubu procenu od 8 do 12 minuta. To je procena, nije mereno na produkciji.

## Šta pokrećete

```
unzip -o /home/icaffeco/b1152-operator.zip -d ~/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1152-installer.sh
```

Radnik ide u pozadini, a izveštaj se upisuje u
`docs/operations/<broj>-B1152-INSTALLER-<datum>-<vreme>.md`. Skripta sama uzima prvi slobodan broj
(prva moguća vrednost je 1118, ali svaki raniji `HOLD` troši broj, pa ne računajte na fiksan broj).
Ako se SSH prekine, posao se nastavlja. Pratite ga sa `tail -f` na izveštaj.

Od pokretanja do kraja izveštaja ne menjajte nijedno Cloudflare podešavanje. Za B1152 nije potreban
nijedan purge: stranice prodavnice šalju `private, no-store`, a adresa CSS-a se ne menja.

## Šta proveravamo pre početka

Svih 8567 fajlova mora imati tačan digest (manifest plus B1146 do B1151), a svaki izmenjeni fajl
tačno staru verziju. Staje se i ako postoji keš ruta, ako je prodavnica isključena, ako radi artisan
komanda, ako je backup blizu, ako nema mesta na disku ili u kvoti, ako je composer autoload
autoritativan (nove klase se tada ne bi našle), ako efektivni `config('app.url')` nije tačno
`https://cms.ald1n.com`, ako je konfiguracija keširana, ako u CLI-ju fali PHP ekstenzija ili ako javne
stranice nemaju `X-Robots-Tag: noindex`.

Za informaciju (nikad blokada) čitamo da li postoji podešavanje pouzdanih ili kanonskih hostova
(`trustedOrCanonicalHostConfig` u izveštaju), jer bi moglo da deluje zajedno sa novim middleware-om.

Proveravamo i novi kod, u CLI-ju: pre instalacije klase `AppUrlOrigin` i `PinAppUrlOrigin` ne smeju da
se učitaju, a `bootstrap/app.php` ih ne sme imenovati. Posle instalacije se učitavaju,
`AppUrlOrigin::configured()` vraća pun origin iz `APP_URL`, `PinAppUrlOrigin` je u globalnoj listi
middleware-a HTTP kernela, a ostalih sedam izmena je u fajlovima (čitamo tekst, ništa ne izvršavamo).
`ResetPasswordNotification` se nikad ne instancira, pa se ne pravi ni poruka. `bootstrap/app.php` koji
imenuje middleware dok se klase ne mogu učitati je greška u svakom stanju. Broj ruta mora biti 568
pre i posle. Sve što su B1149, B1150 i B1151 postavili mora da postoji u oba stanja, a živi
`php artisan app:product-media-doctor` i dalje mora da prođe.

## Smoke

Posle svake grupe radimo kratku proveru života: `/up`, `/login`, jedna pravna stranica, `/katalog` sa
noindex-om, prvi proizvod i `GET /tema` 405. Ako padne, instalacija staje i vraća staro stanje.
Odgovor 429 je neodlučan i instalacija ide dalje.

Pun smoke ne zahteva prijavu, koristi samo GET i jedan HEAD. Nikad ne šaljemo formu i ne zovemo
krajnje tačke za lozinku.

- Sve što su B1149, B1150 i B1151 postavili ostaje, u starom i u novom stanju: nonce skripta, galerija,
  `private, no-store`, logo, potpisana ruta `/media/p/...` (200, ETag, 304, goli 404 za loš potpis),
  gost na `/admin/settings/smtp` i na `/admin/settings/public-storefront` dobija 302 na prijavu.
- Novo: gost na `/admin/settings/documents` dobija 302 na prijavu. PDF se ne generiše.
- Novo: sva tri 302 odgovora moraju da pokažu na `/login` na pravom originu. U novom stanju to je
  tačno `https://cms.ald1n.com/login`. Apsolutna adresa na bilo kom drugom hostu obara proveru. U
  starom stanju prihvatamo i pravi host sa običnim `http`, jer stari kod prati šemu zahteva.
- Novo: ako forma za prijavu ima apsolutni `action`, mora biti na tom originu.
- Red `LEGAL_EMAIL_STATE` je samo informacija i u ovom alatu nikad ne obara instalaciju: u paketu nema
  nijednog fajla izgleda ni pravnog teksta, pa B1151 provera e-maila ovde ne može da se promeni.
  U izveštaju to piše i u redu `B1152_RESULT` (`legalEmailCheck`). Brojimo ostatke Cloudflare obfuskacije na pravnim
  stranicama treba da bude isti kao pre instalacije (izgled i pravni tekst nisu u paketu).
- Posle instalacije: `app:product-media-doctor` vraća kod 0, nema novih grešaka u `laravel.log`,
  prekidač prodavnice je nepromenjen, odobrenje trgovca je nepromenjeno, status mail transporta je
  nepromenjen, a svih 8569 fajlova u stablu ima tačan digest.

### Šta nismo mogli da proverimo sa produkcije

Probu sa lažnim `Host` zaglavljem ovaj alat ne šalje. Njegov HTTP klijent iz B1150 je pinovan i šalje
samo pravi `Host`, a Cloudflare odbija strani host. Da pin radi dokazuje CI: u fixture-u se šalje
zahtev sa lažnim hostom i `Location` ostaje na `https://cms.ald1n.com`. Na produkciji to dokazuje
korak 3 u odeljku "Šta radite vi posle".

## Ako nešto padne

Sami vraćamo stare fajlove obrnutim redom, uz puna čekanja gde je potrebno. Kraj je
`B1152_FINAL=HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`. Uporni 429, isključena prodavnica ili nestali
`noindex` tokom provera ostavljaju tačne fajlove i daju
`B1152_FINAL=HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: ista komanda kasnije ponavlja samo provere.

Vrednosti `B1152_FINAL`:

- `PASS_INSTALLED_APP_URL_ORIGIN_PIN_PDF_LOGO_RASTER_SHOP_STATE_UNCHANGED`: instalirano i provereno,
  `EXIT=0`.
- `PASS_ALREADY_INSTALLED`: ponovno pokretanje posle uspeha, ništa se ne menja.
- `PASS_ROLLED_BACK_BY_OWNER`: `--rollback` je završen.
- `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`: instalacija je pala i vraćena je na B1151 stanje.
- `HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: fajlovi su tačni, ali provere nisu mogle da se zaključe.
- `HOLD_ROLLBACK_INCOMPLETE`: vraćanje je stalo, vidi `RECOVERY_NOTE`.
- `HOLD`: staje se pre ijedne izmene.

## Šta radite vi posle

**a) Prijava.** Prijavite se na vebu i u Android aplikaciji i proverite da ostajete na
`cms.ald1n.com`.

**b) Reset lozinke.** Zatražite jedan reset lozinke za svoj nalog i proverite da link u mejlu počinje
sa `https://cms.ald1n.com/`. Alat ne traži reset i ne šalje mejl.

**c) PDF.** Otvorite jedan PDF dokument i proverite logo. Alat PDF ne generiše.

**d) Ne pokrećite ponovo** B1144 do B1151 ni njihov `--rollback` posle B1152.

## Vraćanje na staro

```
bash /home/icaffeco/ald1n-project/incoming/b1152-installer.sh --rollback
bash /home/icaffeco/ald1n-project/incoming/b1152-installer.sh --rollback --ignore-schedule
```

Druga komanda preskače samo proveru backup termina. Koristite je kad je backup blizu a vraćanje ne
može da čeka. Prodavnica ostaje u stanju u kom jeste. Kraj je `B1152_FINAL=PASS_ROLLED_BACK_BY_OWNER`.

Ako vraćanje stane na pola, izveštaj ima `RECOVERY_NOTE` i blok od `bash -e <<'B1152_RECOVERY'`
do `B1152_RECOVERY`. Prvo pokrenite istu komandu još jednom. Blok nalepite ceo samo ako to ne
uspe.

## Šta ne diramo

Prekidač prodavnice i odobrenje trgovca samo čitamo. SMTP podešavanja ne čitamo, ne menjamo i ne
testiramo. Nema migracija, ne menjamo rute, `.env` ni `config/`, ne pravimo keš ruta, OPcache ne
resetujemo nego ciljano osvežavamo fajlove iz paketa. `queue:restart` samo poručuje queue radnicima
da se ponovo pokrenu. Ne prijavljujemo se, ne šaljemo nijednu formu, ne zovemo krajnje tačke za
lozinku, ne tražimo reset lozinke i ne generišemo PDF. Prava direktorijuma
`~/.ald1n-b1134-retained` ne diramo.

## Šablon izveštaja (popunjavamo iz `<broj>-B1152-INSTALLER-*.md`)

| Polje | Vrednost |
| --- | --- |
| Broj izveštaja i vreme | prvi slobodan broj, datum i vreme početka |
| Glave izvora | `8505b110` (PR 171) i `cfe97253` (PR 170), osnova `7e22afd4`, main `fc954517` |
| Pre-stanje | stablo posle B1151, 8567 fajlova |
| Ishod | `B1152_FINAL=...` i `EXIT=...` |
| `PREFLIGHT` blockers | prazno |
| `PER_FILE_BACKUP` | kopije 7 izmenjenih fajlova, 2 nova zabeležena kao odsutna |
| `WEB_CLASS_LOAD_PROOF` | obe klase učitane u veb SAPI-ju, pre D |
| `ROUTES_CHECK` | `routeCount` 568, bez izmene |
| `FEATURE_CLI_CHECK` | `originConfiguredIsExpected` i `middlewareGlobal` true posle instalacije |
| `PUBLIC_HTTP_SMOKES` (INSTALLED) | `failed: []`, tri 302 na `/login` na pravom originu |
| `PRODUCT_MEDIA_DOCTOR` | `after-install`, `rc: 0`, `ready: true` |
| `MAIL_TRANSPORT_FINGERPRINT` | `status: MAIL_TRANSPORT_UNCHANGED` |
| `ACTIVE_TREE_AFTER_INSTALL` | 8569 fajlova |
| `MERCHANT_APPROVAL` | `UNCHANGED` |
| Nije proveravano | prijava, reset lozinke, PDF, lažni `Host`, Android aplikacija |
