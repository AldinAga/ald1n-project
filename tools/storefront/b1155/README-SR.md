# B1155: kontrolna tabla, padajući meniji, registracija, izveštaj po subagentima i bezbedna preusmerenja

Autor paketa: Ald1n <https://ald1n.com>

Ovaj paket je napravljen u CI-ju. Broj CI pokretanja je u izveštaju o isporuci, ne ovde.
Na produkciji još nije pokrenut.

## Šta se instalira

Trideset fajlova pod `apps/cms/current`: devet novih i 21 izmenjen. Osnova je commit `ad901a58`, to jest
B1154 payload, kod koji je živ posle Izveštaja 1120. Izvor je `origin/main` `d2c07f62`, spoj PR-ova 193,
194, 196, 197 i 198.

Main commit `6acf8aa9` nosi Izveštaj 1120 i ima isti `apps/cms/current` kao `ad901a58`. Build to proverava
komandom `git diff --quiet`, pa je osnova živo stanje uz testove koje instaleri nikad ne isporučuju.

Instaliramo sve pod `apps/cms/current` osim `tests/` i `docs/`. `bin/static-check.php` ide u paketu, jer je
u runtime manifestu (spisak fajlova protiv kog proveravamo stablo na hostu). `docs/openapi.yaml` se menja u
izvoru, ali nije u manifestu. Zato ga ne isporučujemo, a build staje ako ga nađe u manifestu. Ukupno 25 fajlova
razlike (testovi i `docs/openapi.yaml`) ne ulazi u paket.

Nema migracija, izmena `composer`-a, `.env`-a ni `config/`. Ispod `routes/` menja se samo `routes/web.php`.
Dodaje dve GET rute, pa broj ruta ide sa 568 na 570. Build staje ako bilo koji drugi fajl iz `database/`,
`config/`, `routes/` ili `composer.*` ima razliku, čak i među testovima.

| Grupa | Fajl | Vrsta | SHA-256 pre | SHA-256 posle |
| --- | --- | --- | --- | --- |
| A | `app/Http/Requests/LocalAuthFormRequest.php` | novi | nema | `29beaa94bfbd933de296bbcd17cc1355bc4b5c6da1e430f39479a47aa6547df2` |
| A | `app/Http/Requests/CustomerActivationRequest.php` | novi | nema | `ad47cea2397bc208cb8e19130868dd137f93fdcf385ae0af4c3fb2fe909827fb` |
| A | `app/Services/SubagentSalesReportService.php` | novi | nema | `1b97db53622ad8e5dd83485ebd98c53bbe18b7b811d79e6380847e982fc229a6` |
| A | `app/Http/Controllers/Admin/SubagentSalesReportController.php` | novi | nema | `56d21c525ae3fea457552f4d9fa295cfd28c62e1b8574aec2b54d44b223b6ffc` |
| A | `public/assets/css/ald1n-public-register.css` | novi | nema | `bd2dcd6d9b999fe4e56f82647edc77b528b928a8fc28081b3a3fe188b2095c2f` |
| A | `resources/views/dashboard/partials/kpi-delta.blade.php` | novi | nema | `61c4e167f073a1e5ab464c1afd6ec69f276251d8c803bdb2a3e2a2a8248d2b1a` |
| A | `resources/views/dashboard/partials/priorities.blade.php` | novi | nema | `61bc9e508db9061a2301eec728a7255441aa2030149341050c1aab0f8dc1f4ac` |
| A | `resources/views/public-shop/partials/theme-form.blade.php` | novi | nema | `49541f02f1a0bf3a5572163c4e54276305aa57188edc0b655757ec4b37db173d` |
| A | `resources/views/admin/reports/subagent-sales.blade.php` | novi | nema | `368439b7b3e1aa17abbebcaaf8483cb74adf91ada7ba3312a94e5ec226a77061` |
| B | `app/Support/AppUrlOrigin.php` | izmenjen | `3de284868251a100609ea8126d663993f6e690ed29b3a3c454fee725a9f265b2` | `277bd24a94311750dbbe33ab16549bf26138a38dfd02c822a0ff8edb190d9956` |
| B | `app/Support/PublicStorefrontAppearance.php` | izmenjen | `a57aa32827510e3e69184d2d4c2303fe2e5785855a89348cadae4ad289550daf` | `d06e9826101e55b72eaf97bdb2fef22842730f9be87a1919b01035177192d99c` |
| B | `app/Services/ManagementReportService.php` | izmenjen | `fe4cbcb2ccd2c7e175011f9f965a01036b2e0a6b33267e0a1f7ed7772c43d284` | `d178773693a59fbc0e92bd19a36f953b0ffe0634fa2b7f3982f11d1e15428151` |
| C | `app/Http/Requests/LoginRequest.php` | izmenjen | `825c792be40b5872c1527e969106caf15d741e02a82449a2d3066ee5cd7a1a5f` | `f970c24ec4ea3388c7752ef81dd714f3abf996f312dfad2038fc93b6dc727c55` |
| C | `app/Http/Requests/ForgotPasswordRequest.php` | izmenjen | `2466da8465acf4200e3856c86f158dac77c2b59411fb4a3dcdf5340652d29b9c` | `62a105218bdd6a19550c1f93206b9ce721947d099445aae790da17a3bfedbe0d` |
| C | `app/Http/Requests/ResetPasswordRequest.php` | izmenjen | `568d0a812887a297bd760968b5401a281d96700c43d906d24a00123dae154512` | `5a0b08d0baea5badc6a3af698f1e49f09980264ca98515df1aebb7e17093ebca` |
| C | `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | izmenjen | `41a8d7e22540c668cbe3adb42fb6df16d4f3a0c6b5d267a6367afdbe857fe1c4` | `bc65b3825e5c8267b0b3ea0082ce43e136c8dca24ea784640aa085e0891bc5cb` |
| C | `app/Http/Controllers/Auth/PasswordResetController.php` | izmenjen | `42664c8c7e6e9a472a7c7392bde4b7646f81e3aa8499656132c2b90b86d49547` | `ac96e8a466ebf58159153b761d038b19fc9a71a444cb2c163980f9b5270d45e1` |
| C | `app/Http/Controllers/Auth/CustomerActivationController.php` | izmenjen | `08b23166e90495a85969de7e9c28a8502b3a9e19777bc0b9118e3dcd818cd296` | `dfdac343e8ca668e42d87edf64e9c326ed243758a9540ce6a2da1e65f1bd2e67` |
| C | `app/Http/Middleware/PublicNoScriptCsp.php` | izmenjen | `2e752a32e0e6d3b9b8a605019df2cdfa44723ee41e635543f01663f5dda37212` | `54611ec18e18fe329842726078c616e9dc9bb7ffb3ddae480b70a2b7194d9336` |
| C | `public/assets/css/ald1n-admin.css` | izmenjen | `059d9f1de74f959da7f98df9f0efaee00d617cb8fc9853ae1a85ca2ab2ecbeb9` | `2f4211404c531c5afb0c448dd48ad873745f0914cd9a6385da1f26aa905b9667` |
| C | `bin/static-check.php` | izmenjen | `696323c24a54b31daa76a67b3b372714df1822acd11b82fda62c66af587ed590` | `8ee508deff935bd55edfdeafc3030a736d253810f9bb76e8fea15a14550938d7` |
| D | `bootstrap/app.php` | izmenjen | `38ba2b88403e6df71fd1b2daacd93071ded7fd286ecf47c4d51374429e031923` | `ea8fd34f9730426723dc0342e0ef98b289e658a3f417fea7c1848c19df03be04` |
| E | `resources/views/dashboard/partials/operator-orders.blade.php` | izmenjen | `358eedd122e51b2c424cd5b2a2d67f35835143828df02d46dd4ee1f9952441b4` | `b46002803a914ff5c1c8c2015fe04ce36d842b423708e3761c70d7e183cf08b5` |
| E | `resources/views/dashboard/partials/customer-center.blade.php` | izmenjen | `52375d9202778408e9e64c64eb79e14ba1f51c5f305e5ce8dd432409a4408c13` | `129ec5be948a113fc27a94b2a48a551bde0bafa17a7153fd046416e993262aa4` |
| E | `resources/views/dashboard/index.blade.php` | izmenjen | `08d53aa6ec19ec0a09645f9f9d77feb054d2c6d152b113d9afebcd5c2e977286` | `7779926c524cba298de76fa2f43a2faeea32b7013107ddfe1da25e52802d4181` |
| E | `resources/views/layouts/app.blade.php` | izmenjen | `ed8607dcba5ec2926dd3ba68fc3e5e85e9810453fcdafeebce30b6a1b427c5ed` | `de5970cc671deb6061d58734004172b6780c71ee2d67c19779b1e87b53923c97` |
| E | `resources/views/public-shop/layout.blade.php` | izmenjen | `a618a2345e02e24c22a4b33275d4a7aa537700d608a3a5f4d4c0f48416c274a9` | `f112267412e7d82e2b8c656af576dd5438dc3fc8c36f2dd9c334022ec3e51e66` |
| E | `resources/views/public-shop/register.blade.php` | izmenjen | `40960fffe92adc68efabc3d1acd37828eee3b0748ccf01e32ff6b43a0c6ecff6` | `57718b11bfd16d85777cc0d9e488bc2b4e88a27ea3a981dc1b43b47537fce99e` |
| F | `routes/web.php` | izmenjen | `a8ebd8c838f5dfd154969570d82a26e53313d4d8677f46c90b886fe3af38c1b9` | `b72b49f2dbcc6229955bacc2cbf85c402de9e46b5a862d1d8b92094cd93ff363` |
| G | `resources/views/admin/reports/management.blade.php` | izmenjen | `1b106c931d66e50b978dcd01d6172bafb57872df8d7b907c2c8dc9dd02e8cfb6` | `e8d38007a1740503325649c12700fb516bdb16f5c3d89d5ca91b72a5ca2eb026` |

Iste vrednosti su u `b1155-digests.tsv` uz paket i u označenom bloku koordinatora (`B1155_PAYLOAD`).
Stablo na hostu ima posle instalacije 8583 fajla: 8574 plus devet novih.

Šta menja svaka celina:

| Celina | Fajlovi | Šta radi |
| --- | --- | --- |
| Kontrolna tabla | `dashboard/index`, dva dela (`operator-orders`, `customer-center`), dva nova (`kpi-delta`, `priorities`) | Novi raspored kontrolne table na `a1-` stilovima. |
| Padajući meniji | `layouts/app`, `ald1n-admin.css` | Podešavanja u dve kolone, bez klizača na desktopu. Panel dobija neprozirnu pozadinu. |
| Registracija | `public-shop/register`, `public-shop/layout`, `partials/theme-form`, `ald1n-public-register.css`, `PublicStorefrontAppearance`, `PublicNoScriptCsp` | Stranica registracije u vizuelnom okviru prijave: ista pozadina, video na desktopu. |
| Izveštaj po subagentima | kontroler, servis, pogled, `routes/web.php`, `ManagementReportService`, `management.blade.php` | Stranica „Prodaja po subagentima“ sa filterima i CSV izvozom, dugme na upravljačkom izveštaju. |
| Bezbedna preusmerenja | `LocalAuthFormRequest`, tri zahteva, tri kontrolera, `CustomerActivationRequest`, `AppUrlOrigin`, `bootstrap/app.php` | Neuspeo obrazac vraća na svoju stranicu na `APP_URL` originu. `Referer` zaglavlje se ne prati. |

## Redosled i zašto

Svaki pozivalac dolazi u kasniju grupu od onoga što poziva, pa zahtev nikad ne sretne novi kod nad starom
bibliotekom. Build ovo proverava nad isporučenim bajtovima i staje ako bilo koja veza pada, uz imenovan kod
(`PACKAGE_ORDER_EDGE_...`). Novi fajlovi grupe A su inertni, pa ih kontroler i servis dostižu tek preko
`routes/web.php` iz grupe F.

| Grupa | Fajlovi | Zašto ovde |
| --- | --- | --- |
| A | devet novih fajlova | Nijedan fajl u stablu `ad901a58` ne imenuje nijedan od njih, pa su inertni. Novi direktorijum `resources/views/public-shop/partials` pravi instaler, a vraćanje ga uklanja. |
| B | `AppUrlOrigin`, `PublicStorefrontAppearance`, `ManagementReportService` | Biblioteke koje novi pozivaoci traže. Svaka je kompatibilna sa starim pozivaocima (dokaz niže). |
| C | tri zahteva, tri kontrolera, `PublicNoScriptCsp`, `ald1n-admin.css`, `bin/static-check.php` | Zahtevi idu pre kontrolera koji zovu `localRedirect()`. Stil dodaje nova pravila koja stari markup ne koristi. |
| D | `bootstrap/app.php` | Novi zatvarač imenuje `AppUrlOrigin::redirect()`. Greška u ovom fajlu obara ceo sajt, pa je to čuvarska grupa. |
| E | šest pogleda | Delovi kontrolne table pre `index`, pa `layouts/app`, pa `public-shop/layout`, pa `register`. Čuvarska grupa sa dokazom stilova. |
| F | `routes/web.php` | Imenuje kontroler iz grupe A, koji zove servis iz grupe A, koji zove `ManagementReportService::reportOrderIds()` iz grupe B. Čuvarska grupa. |
| G | `admin/reports/management.blade.php` | Pogled crta `route('admin.reports.subagents')`. Instaliran pre ruta, rušio bi postojeći upravljački izveštaj sa greškom 500. |

Čuvarske grupe D, E i F imaju puno izmereno čekanje pre prvog fajla. Čekanje se računa kao
`max(realpath_cache_ttl, revalidate_freq) + 5` sekundi od poslednje izmene (u B1154 je to bilo 125 s).
Grupe D i F traže još dokaz klasa. Veb SAPI učitava sedam klasa (tri izmenjene i četiri nove), a fajl svake mora
imati SHA-256 iz paketa. Grupa E traži dokaz stilova: `ald1n-admin.css` i `ald1n-public-register.css` moraju
odgovoriti 200, `text/css` i tačnim SHA-256, pet puta zaredom sa svežim markerom u adresi. Bez tog dokaza
grupa se ne dira.

Od grupe D u kratku proveru života ulazi i GET `/katalog?stock=x` sa stranim `Referer` zaglavljem. Posle F koordinator zove
`route:clear`, pa proverava CLI-jem da je broj ruta tačno 570, da oba imena postoje sa metodom GET, da keš ruta
ne postoji i da `php artisan route:list` izlazi sa 0. Zatim gost na `/admin/reports/subagents` i na
`/admin/reports/subagents.csv` mora dobiti 302 na prijavu, bez greške 5xx.

Svaki fajl ide kroz privremeni fajl u istom direktorijumu, `fsync` i `rename` (`link` za nov fajl). OPcache
osvežavamo ciljano, po fajlu. Ne pravimo `route:cache` ni `view:cache`.

## Privremena stanja po grupama

Posle svake grupe sajt radi, jer svaki novi fajl ili niko ne koristi ili ima već instalirane zavisnosti.
Ovo vide posetilac i prijavljeni administrator u prozoru te grupe.

| Posle grupe | Posetilac | Prijavljeni administrator |
| --- | --- | --- |
| A | Ništa se ne menja. | Ništa se ne menja. |
| B | Ništa se ne menja. | Upravljački izveštaj vraća iste redove, upit samo koristi opseg datuma. |
| C | Neuspela prijava i zaboravljena lozinka kratko rade po starom (`back()`) ili po novom (vraćanje na svoju stranicu), oba daju obrazac sa greškom. | Padajući meniji dobijaju neprozirnu pozadinu i drugačije ograničenje visine. Ostalo je isto. |
| D | Neispravan filter ili korpa vraća na `/katalog` ili korpu na `APP_URL` originu. Ostalo je isto. | Ništa se ne menja. |
| E | Registracija dobija novi izgled tek na kraju grupe. Zaglavlje prodavnice je isto. | Kontrolna tabla je nekoliko sekundi mešana: novi delovi u starom rasporedu, pa novi raspored u starom zaglavlju, pa nove kolone menija. Sve radi. |
| F | Ništa se ne vidi. | Adresa `/admin/reports/subagents` radi za ovlašćene, a dugmeta još nema. |
| G | Ništa se ne menja. | Upravljački izveštaj dobija dugme „Prodaja po subagentima“. |

Najgori prozor je grupa D. Greška u `bootstrap/app.php` vratila bi 500 na svakoj stranici, za posetioce i za
administratore. Zato D ima puno čekanje i dokaz klasa pre fajla, trenutnu proveru života posle njega, a kad
ona padne, prvo se vraća D. Najgori prozor koji vide ljudi je E: administrator koji osvežava kontrolnu tablu
tokom nekoliko sekundi može videti starije pa novije delove. Prvo otvaranje svake admin stranice posle
instalacije je sporije, jer `view:clear` briše kompajlirane poglede.

Prodavnica se u ovom paketu menja samo na dva mesta: `public-shop/layout` sada uključuje deo
`theme-form` (isti obrazac za temu, ista oznaka `aria-label="Tema"`, to build proverava) i stranica
registracije dobija novi izgled. Katalog, galerija i stilovi prodavnice se ne menjaju.

## Dokaz da su biblioteke kompatibilne sa starim pozivaocima

Build izvršava ove provere nad isporučenim bajtovima i staje ako bilo koja padne.

`AppUrlOrigin` i `PublicStorefrontAppearance` samo dobijaju članove. Svaki red starog fajla postoji u
novom, istim redom, a razlika je dodatak (`redirect()`, odnosno `loginBackground()` i
`resolveLoginBackground()`). Stari pozivaoci i stari potpisi su zato nepromenjeni. Stari `AppUrlOrigin` nema
`redirect(`, a stari `PublicStorefrontAppearance` nema `loginBackground(`, što build takođe proverava.

`ManagementReportService` se menja na jednom mestu, uz jedan dodat metod. Uslov datuma `whereDate(kolona, '>=', od)`
i `whereDate(kolona, '<=', do)` postaje `kolona >= 'od 00:00:00'` i `kolona < 'do + 1 dan 00:00:00'`. Redovi su
isti, jer su `date_from` i `date_to` uvek `Y-m-d` iz `normalizeFilters`, a kolona je `DATETIME` ili `TIMESTAMP`.
Novi upit može da koristi indeks po datumu. Kad se taj uslov vrati na stari, svaki red starog fajla je u novom istim redom (jedino
`reportOrderIds()` je dodat), a build to proverava.

`ald1n-admin.css` gubi tačno dva stara pravila panela padajućeg menija i dobija nova. Stari markup menija
ostaje čitljiv sa novim pravilima.

Stari pozivaoci ne koriste nove članove: pre instalacije stari zahtevi ne nasleđuju `LocalAuthFormRequest`,
`bootstrap/app.php` ne zove `AppUrlOrigin::redirect()`, stari `PublicNoScriptCsp` ne zove `loginBackground()`,
a stari `routes/web.php` ne zna kontroler. Novi `PublicNoScriptCsp` zove `loginBackground()` unutar
`try/catch (Throwable)`, pa ni stara biblioteka ne bi mogla da ga obori.

## Pre pokretanja proverite sami

1. Postavite `b1155-operator.zip` i `b1155-operator.zip.sha256` u `/home/icaffeco`. Komanda mora
   da ispiše `b1155-operator.zip: OK`:

   ```
   cd /home/icaffeco && sha256sum -c b1155-operator.zip.sha256
   ```

2. Tačno jedan izveštaj `1120-B1154-INSTALLER-*.md`, a na njegovom kraju
   `B1154_FINAL=PASS_INSTALLED_ADMIN_SHELL_NAVIGATION_CSS_AND_VIEWS_SHOP_STATE_UNCHANGED` i `EXIT=0`.
   Skripta traži da njegov SHA-256 bude
   `8840b73569db9f2dc1455b14bce5ec7824f27b417e4ef010ddcdcc837030ced0`. Ako se host primerak
   razlikuje, kraj je bezbedan `HOLD` bez izmena. Isti digest ima primerak u repozitorijumu
   (`docs/operations/1120-B1154-INSTALLER-20261115-105942.md`), računat nad git blobom. Windows kopija sa
   CRLF završecima daje drugu vrednost, pa je ne poredite.

   ```
   ls /home/icaffeco/ald1n-project/docs/operations/1120-B1154-INSTALLER-*.md
   sha256sum /home/icaffeco/ald1n-project/docs/operations/1120-B1154-INSTALLER-*.md
   tail -n 3 /home/icaffeco/ald1n-project/docs/operations/1120-B1154-INSTALLER-*.md
   ```

3. Stanje `~/.ald1n-b1154-state/192319eb0107b9c10c427642c53a53a7` kaže `INSTALLED_PASS`, a stablo ima
   8574 fajla. Skripta to proverava sama, uključujući broj ruta (568).
4. U `incoming/` su pomoćni fajlovi iz ranijih paketa, nepromenjeni, od `b1134-files.php` do
   `b1153-load-probe.php`, plus dva iz B1154: `b1154-app-check.php` i `b1154-coordinator.php`. Ako neki
   fali ili je izmenjen, kraj je `HOLD_CODE=PACKAGE_FILE_INTEGRITY` i ništa nije promenjeno. Poslednja
   dva proverite odmah:

   ```
   sha256sum /home/icaffeco/ald1n-project/incoming/b1154-coordinator.php /home/icaffeco/ald1n-project/incoming/b1154-app-check.php
   ```

   Moraju da daju `51dd96f6798bbebc74def1d1ddaed9f85a632ea93abcbe22b2ffd8866a05d016` i
   `6da48e7998be940c4cca83f5afbe82c59c79121c036ac4efaf2e02cb08037385`. To su digesti git blobova
   `tools/storefront/b1154` na `origin/main`. Pretpostavka, koju ovde ne možemo dokazati: ZIP koji ste
   pokrenuli nosio je iste bajtove. CI gradi B1154 paket iz repozitorijuma i poredi ta dva člana sa pinom.
   Za `b1153-*` važi dokaz iz B1154 (CI run `43637960668`, `b1153-package.sha256`).
5. `APP_URL` u `.env` je tačno `https://cms.ald1n.com` (bez kose crte na kraju). Skripta to proverava
   i staje bez izmena ako nije.
6. Prodavnica je uključena, saobraćaj je mali, a do backup-a (02:30 svaki dan, 03:10 nedeljom,
   Europe/Belgrade) ima dovoljno vremena.

### Prozor i trajanje

Skripta računa potreban prozor za sedam grupa, tri čuvarska čekanja i završno čekanje, a dodaje izmerene
provere života i dokaze. Prozor je duži nego u B1154. Tačnu vrednost pročitajte u `PREFLIGHT`
izveštaju, polje `timeBound.requiredWindowSeconds`. Ako backup upada u prozor, skripta staje pre prve izmene.
Trajanje instalacije je procena, nije mereno na produkciji.

## Šta pokrećete

Četiri komande, redom. Prva raspakuje paket, druga instalira, treća i četvrta vraćaju.

```
unzip -o /home/icaffeco/b1155-operator.zip -d ~/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1155-installer.sh
bash /home/icaffeco/ald1n-project/incoming/b1155-installer.sh --rollback
bash /home/icaffeco/ald1n-project/incoming/b1155-installer.sh --rollback --ignore-schedule
```

Pokrećete prve dve. Poslednje dve su samo za vraćanje (niže).

Radnik ide u pozadini, a izveštaj se upisuje u
`docs/operations/<broj>-B1155-INSTALLER-<datum>-<vreme>.md`. Skripta sama uzima prvi slobodan broj.
Prva moguća vrednost je 1121, ali svaki raniji `HOLD` troši broj, pa ne računajte na fiksan broj.
Ako se SSH prekine, posao se nastavlja. Pratite ga sa `tail -f` na izveštaj.

Cloudflare: ne treba nijedan purge i nijedna izmena podešavanja. Adrese stilova nose `?v=`, pa ih ni
Cloudflare ni pregledač nikad nisu videli pod tim imenom.

## Šta proveravamo pre početka

Svih 8574 fajla mora imati tačan digest (manifest plus B1146 do B1154), a svaki izmenjeni fajl tačno staru
verziju. Svih devet novih putanja mora biti odsutno, a imena ruta `admin.reports.subagents` i
`admin.reports.subagents.csv` ne smeju postojati. Broj ruta mora biti 568.

Staje se i ako postoji keš ruta, ako je prodavnica isključena, ako radi artisan komanda, ako je backup blizu,
ako nema mesta na disku ili u kvoti, ako je konfiguracija keširana, ako efektivni `config('app.url')` nije
tačno `https://cms.ald1n.com`, ako u CLI-ju fali PHP ekstenzija ili ako javne stranice nemaju
`X-Robots-Tag: noindex`.

Pre prve izmene alat snima osnovni CSP stranica `/katalog` i `/registracija` (nonce se normalizuje) i čita
iz CLI-ja samo logičku vrednost da li je pozadina prijave YouTube video sa ispravnim ID-jem. ID i režim
se nikad ne ispisuju. Migracije, `composer` i `config` se proveravaju kao nepromenjeni i to se ispisuje.

## Provera pogleda posle instalacije

Koordinator samo čita. Svih 11 pogleda iz paketa (četiri nova i sedam izmenjenih) kompajlira se u memoriji
(`Blade::compileString`) i proverava se da je kompajlirani PHP ispravan. Ništa se ne upisuje u direktorijum
kompajliranih pogleda. Ako bilo šta padne, ide uobičajeno automatsko vraćanje.

## Smoke i šta NE proverava

Smoke ne zahteva prijavu i koristi samo GET i jedan HEAD. Nikad ne šaljemo formu i ne zovemo krajnje tačke
za lozinku. Posle instalacije proveravamo:

| Provera | Očekivano |
| --- | --- |
| `/up`, `/login`, `/katalog`, `/registracija` | 200, javne stranice sa `noindex` |
| `ald1n-admin.css` i `ald1n-public-register.css` | 200, `text/css`, SHA-256 jednak paketu |
| `ald1n-tokens.css` | 200, `text/css`, SHA-256 iz B1154 (`9fc9e850…`) |
| `/registracija` CSP | sadrži Turnstile izvore kao pre (`https://challenges.cloudflare.com`); `https://www.youtube-nocookie.com` u `frame-src` samo ako je pozadina prijave ispravan YouTube video |
| `GET /katalog?stock=x` sa `Referer: https://evil.example/x` | 302 na tačno `https://cms.ald1n.com/katalog` |
| gost na `/admin/reports/subagents` i `.csv` | 302 na prijavu na pravom originu, bez 5xx |
| CSP kataloga (nonce normalizovan) | isti kao pre instalacije |
| broj ruta i imena | 570, oba imena sa GET |

U starom stanju provera preusmerenja sa `Referer` zaglavljem samo beleži informaciju, jer tada
`back()` još sledi zaglavlje.

**Prijavljene stranice i forme alat NE proverava.** Alat se nikad ne prijavljuje i ne šalje POST. Zato ne
vidimo kontrolnu tablu, menije, izveštaj po subagentima za prijavljenog korisnika, CSV, registraciju ni
prijavu sa pogrešnom lozinkom. U CI-ju fixture radi to na sintetičkoj bazi. To nije produkcija.

Sve iz ranijih paketa ostaje: pravne stranice, galerija, potpisana ruta sa ETag i 304, nepromenjen
prekidač prodavnice, nepromenjeno odobrenje trgovca i nepromenjen status mail transporta. Posle instalacije
`app:product-media-doctor` vraća kod 0, nema novih grešaka u `laravel.log`, a svih 8583 fajla u stablu ima
tačan digest.

## Ako nešto padne

Sami vraćamo stare fajlove obrnutim redom, uz puna čekanja posle čuvarskih grupa. Uporni 429, isključena
prodavnica ili nestali `noindex` tokom provera ostavljaju tačne fajlove i daju
`B1155_FINAL=HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`. Ista komanda kasnije ponavlja samo provere.

| Vrednost `B1155_FINAL` | Značenje |
| --- | --- |
| `PASS_INSTALLED_DASHBOARD_DROPDOWNS_REGISTRATION_SUBAGENT_REPORT_REDIRECTS_SHOP_STATE_UNCHANGED` | Instalirano i provereno, `EXIT=0`. |
| `PASS_ALREADY_INSTALLED` | Ponovno pokretanje posle uspeha, ništa se ne menja. |
| `PASS_ROLLED_BACK_BY_OWNER` | `--rollback` je završen. |
| `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED` | Instalacija je pala i vraćena je na B1154 stanje. |
| `HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED` | Fajlovi su tačni, ali provere nisu mogle da se zaključe. |
| `HOLD_ROLLBACK_INCOMPLETE` | Vraćanje je stalo, vidi `RECOVERY_NOTE`. |
| `HOLD` | Staje se pre ijedne izmene. |

## Šta radite vi posle (ručno, kad je izveštaj PASS)

Alat ne vidi prijavljene stranice i forme, pa ovo proveravate vi.

1. Kontrolna tabla: otvorite je na računaru i na telefonu, u svetloj i u tamnoj temi.
2. Padajući meniji: Podešavanja se otvaraju u dve kolone, bez klizača. Prođite i ostale menije.
3. Izveštaj „Prodaja po subagentima“: otvorite upravljački izveštaj, kliknite dugme, isprobajte filtere i CSV izvoz.
4. Stranica registracije: pozadina je kao na prijavi, video se pušta na računaru, obrazac je čitljiv. Probna
   registracija sa pogrešnim podacima mora vratiti na obrazac sa greškama.
5. Prijava sa pogrešnom lozinkom mora vratiti na prijavu.
6. Prvo otvaranje svake admin stranice posle PASS-a je sporije jer se pogledi ponovo kompajliraju.
7. Ako bilo šta nije u redu, odmah pokrenite komandu za vraćanje.
8. Ne pokrećite ponovo B1144 do B1154 ni njihov `--rollback` posle B1155.

## Vraćanje na staro

```
bash /home/icaffeco/ald1n-project/incoming/b1155-installer.sh --rollback
bash /home/icaffeco/ald1n-project/incoming/b1155-installer.sh --rollback --ignore-schedule
```

Druga komanda preskače samo proveru backup termina. Koristite je kad je backup blizu a vraćanje ne
može da čeka. Prodavnica ostaje u stanju u kom jeste. Kraj je `B1155_FINAL=PASS_ROLLED_BACK_BY_OWNER`.

Vraćanje ide obrnutim redom: G, F (uz `route:clear`), puno čekanje, E, puno čekanje, D, puno čekanje, C, B, pa
A. Fajlovi grupe A se brišu poslednji, a direktorijum `resources/views/public-shop/partials` se tada uklanja.
Posle vraćanja ima 568 ruta, oba imena ruta ne postoje, a stablo je opet B1154 stablo od 8574 fajla.

Ako vraćanje stane na pola, izveštaj ima `RECOVERY_NOTE` i blok od `bash -e <<'B1155_RECOVERY'`
do `B1155_RECOVERY`. Prvo pokrenite istu komandu još jednom. Blok nalepite ceo samo ako to ne
uspe. On vraća fajlove obrnutim redom i briše devet novih fajlova.

## Šta ne diramo

Prekidač prodavnice i odobrenje trgovca samo čitamo. SMTP podešavanja ne čitamo, ne menjamo i ne
testiramo. Nema migracija, ne menjamo `.env` ni `config/`, ne pravimo keš ruta ni keš pogleda, OPcache ne
resetujemo nego ciljano osvežavamo fajlove iz paketa. `queue:restart` samo poručuje queue radnicima
da se ponovo pokrenu. Ne prijavljujemo se, ne šaljemo nijednu formu, ne zovemo krajnje tačke za
lozinku, ne šaljemo mejl i ne generišemo PDF. Prava direktorijuma `~/.ald1n-b1134-retained` ne diramo.

## Šablon izveštaja (popunjavamo iz `<broj>-B1155-INSTALLER-*.md`)

| Polje | Vrednost |
| --- | --- |
| Broj izveštaja i vreme | prvi slobodan broj (najmanje 1121), datum i vreme početka |
| Izvor i osnova | izvor `d2c07f62` (PR 193, 194, 196, 197, 198), osnova `ad901a58` |
| Pre-stanje | stablo posle B1154, 8574 fajla, 568 ruta |
| Ishod | `B1155_FINAL=...` i `EXIT=...` |
| `PREFLIGHT` blockers | prazno |
| `PER_FILE_BACKUP` | kopije 21 izmenjenog fajla, 9 novih zabeleženo kao odsutni |
| Čuvarske grupe | dokaz klasa pre D i F, dokaz stilova pre E |
| `ROUTES_CHECK` | `routeCount` 570, oba imena sa GET |
| `FEATURE_CLI_CHECK` | oznake B1155 true posle instalacije, B1153 i B1154 činjenice true u oba stanja |
| `VIEW_COMPILE_CHECK` | 11 pogleda, `failed: []` |
| `PUBLIC_HTTP_SMOKES` (INSTALLED) | `failed: []`, CSP registracije, preusmerenje sa `Referer`, gost na izveštaju |
| `PRODUCT_MEDIA_DOCTOR` | `after-install`, `rc: 0`, `ready: true` |
| `MAIL_TRANSPORT_FINGERPRINT` | `status: MAIL_TRANSPORT_UNCHANGED` |
| `ACTIVE_TREE_AFTER_INSTALL` | 8583 fajla |
| `MERCHANT_APPROVAL` | `UNCHANGED` |
| Nije proveravano | prijavljene stranice, POST tokovi, telefon, Gmail, PDF |
