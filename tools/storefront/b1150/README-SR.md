# B1150: slike artikala preko potpisane rute

Autor paketa: Ald1n <https://ald1n.com>

Ovaj paket je napravljen i proveren samo u CI-ju. Na produkciji još nije pokrenut.

## Šta se instalira

Pet fajlova pod `apps/cms/current`: dva nova i tri izmenjena. Izvor je commit `14e628ba` sa grane
`codex/product-media-route-20261112`. Nema migracija, nema izmena `composer`-a, `.env`-a ni
`config/`. Testove i `bin/` ne instaliramo.

| Grupa | Fajl | Vrsta |
| --- | --- | --- |
| A | `app/Support/ProductMediaUrl.php` | nov |
| A | `app/Http/Controllers/SignedProductMediaController.php` | nov |
| E | `routes/web.php` | izmenjen |
| G | `app/Models/ProductImage.php` | izmenjen |
| H | `app/Console/Commands/ProductMediaDoctorCommand.php` | izmenjen |

Slike artikala prelaze sa `/storage` URL-ova na potpisanu rutu
`GET /media/p/{id}/{original|display|thumbnail}?v=<16 hex>&s=<32 hex>` (ime rute
`media.product.signed`). Ruta radi bez sesije i ne postavlja kolačić. Odgovor nosi `ETag` (uz 304),
`Cross-Origin-Resource-Policy: cross-origin` i `Cache-Control: private, max-age=604800, immutable`.
Loš oblik, loš ili odsutan potpis, nepoznata slika ili fajl daju goli 404. Ispravan potpis se nikad
ne ograničava. Loše potpise server broji sam i ni posle 120 u minuti ne odgovara sa 429.

Javna prodavnica ne koristi tu rutu. Ona i dalje koristi `/katalog/slika/...`, i ta ruta ostaje 200.

Prekidač prodavnice i odobrenje trgovca samo čitamo. SMTP podešavanja ne čitamo i ne menjamo, a
probni mejl ne šaljemo. Mail transport poredimo samo preko HMAC otiska koji živi u privatnom
`state.json` (0600). U izveštaju je samo status `MAIL_TRANSPORT_UNCHANGED` ili
`MAIL_TRANSPORT_CHANGED`.

## Redosled

Klase (A), pa `routes/web.php` (E). Zatim puno čekanje (125 s), pa model `ProductImage` (G), na
kraju komanda (H). Model ide tek posle čekanja. Dok neki web radnik drži staru tabelu ruta, model
ne bi našao rutu, progutao bi izuzetak i svi URL-ovi slika bi tiho postali prazni. Na kraju
radimo `route:clear`, `view:clear` i `queue:restart` (keš ruta ionako ne postoji). Svaki fajl ide
kroz privremeni fajl u istom direktorijumu, `fsync` i `rename` (`link` za nov fajl). OPcache
osvežavamo ciljano, po fajlu. Keš ruta ne postoji i ne pravimo ga.

Pre-stanje je stablo posle B1149: runtime manifest plus B1146, B1147, B1148 i B1149, ukupno 8564
fajla. Posle instalacije stablo ima 8566 fajlova. Pri građenju paketa digest svakog izmenjenog
fajla (git blob iz `7e748158`) mora da se poklopi sa digestom iz ranijeg sloja, inače se paket ne
gradi. Poređenje sa živim stablom radi skripta na hostu, pre prve izmene.

## Pre pokretanja proverite sami

1. Postavite `b1150-operator.zip` i `b1150-operator.zip.sha256` u `/home/icaffeco`. Komanda mora
   da ispiše `b1150-operator.zip: OK`:

   ```
   cd /home/icaffeco && sha256sum -c b1150-operator.zip.sha256
   ```

2. Tačno jedan izveštaj `1115-B1149-INSTALLER-*.md`, a na njegovom kraju
   `B1149_FINAL=PASS_INSTALLED_STOREFRONT_ROLLOUT_SHOP_STATE_UNCHANGED` i `EXIT=0`. Izveštaj 1114
   (istog B1149 prefiksa) je bio HOLD i ne računa se.

   ```
   ls /home/icaffeco/ald1n-project/docs/operations/1115-B1149-INSTALLER-*.md
   tail -n 3 /home/icaffeco/ald1n-project/docs/operations/1115-B1149-INSTALLER-*.md
   ```

3. Stanje `~/.ald1n-b1149-state` kaže `INSTALLED_PASS`, a stablo ima 8564 fajla. Skripta to
   proverava sama.
4. U `incoming/` su pomoćni fajlovi iz ranijih paketa, nepromenjeni: `b1134-files.php`,
   `b1144-gates.php`, `b1144-probe.php`, `b1144-web.php`, `b1145-env.php`, `b1145-app-state.php`,
   `b1145-coordinator.php`, `b1146-probe.php`, `b1146-app-check.php`, `b1147-web.php`,
   `b1147-app-check.php`, `b1147-coordinator.php`, `b1148-web.php`, `b1148-app-check.php`,
   `b1148-coordinator.php`, `b1149-web.php`, `b1149-app-check.php` i `b1149-coordinator.php`.
   Ako neki fali ili je izmenjen, kraj je `HOLD_CODE=PACKAGE_FILE_INTEGRITY` i ništa nije
   promenjeno.
5. PHP na serveru ima `dom`, `libxml`, `fileinfo`, `mbstring` i `gd` ili `imagick`. Skripta to
   proverava u CLI-ju. Web PHP proverite sami.
6. Prodavnica je uključena, saobraćaj je mali, a do backup-a (02:30 svaki dan, 03:10 nedeljom,
   Europe/Belgrade) ima dovoljno vremena.

### Prozor i trajanje

Skripta računa potreban prozor po pravilu `5 x čekanje + 2 x smoke + 14 x pauza + 7 x provera
života + 300 s`, nikad manje od 30 minuta. Sa čekanjem od 125 s to je oko 2350 s, dakle oko 40
minuta bez backup-a. Tačnu vrednost pročitajte u `PREFLIGHT` izveštaju, polje
`timeBound.requiredWindowSeconds`. Ako backup upada u prozor, skripta staje pre prve izmene.

Sama instalacija traje kraće od prozora. Dva puna čekanja od po 125 s, pauze između grupa i dva
smoke-a daju grubu procenu od 8 do 12 minuta. To je procena, nije mereno na produkciji.

## Šta pokrećete

```
unzip -o /home/icaffeco/b1150-operator.zip -d ~/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1150-installer.sh
```

Radnik ide u pozadini, a izveštaj se upisuje u
`docs/operations/<broj>-B1150-INSTALLER-<datum>-<vreme>.md`. Sledeći broj je 1116 (1115 je PASS
izveštaj B1149, 1114 je bio HOLD). Ako se SSH prekine, posao se nastavlja. Pratite ga sa `tail -f`
na izveštaj.

Od pokretanja do kraja izveštaja 1116 ne menjajte nijedno Cloudflare podešavanje (Rocket Loader,
Web Analytics, Email Obfuscation). Za B1150 nije potreban nijedan purge. Nova ruta je potpisana,
bez sesije i odgovara sa `private`, pa je Cloudflare ne kešira.

## Šta proveravamo pre početka

Svih 8564 fajla mora imati tačan digest (manifest plus B1146, B1147, B1148 i B1149), a svaki
izmenjeni fajl tačno staru verziju. Staje se i ako postoji keš ruta, ako je prodavnica isključena,
ako radi artisan komanda, ako je backup blizu, ako nema mesta na disku ili u kvoti, ako je
composer autoload autoritativan, ako APP_URL nije čist https origin, ako u CLI-ju fali PHP
ekstenzija ili ako javne stranice nemaju `X-Robots-Tag: noindex`.

U preflight-u pokrećemo i `php artisan app:product-media-doctor`. Tada je to samo informacija:
izveštaj ima red `PRODUCT_MEDIA_DOCTOR` sa `label: preflight-fresh`, i on ne zaustavlja instalaciju.

Čitamo i koji cache store koriste ograničivači (`limiterStore` u izveštaju: `redis`, `file`,
`database` ili `array`). Samo ime drajvera, nijedna vrednost konekcije. Ni to ne zaustavlja
instalaciju. Ako je `file` ili `database`, na kraju izveštaja dobijate napomenu da se brojač
loših potpisa upisuje na disk ili u bazu.

Skripta ne zavisi od prava direktorijuma `~/.ald1n-b1134-retained` i njegovog podfoldera. Prava
0750 i 0751 su oba prihvaćena. Trenutna vrednost se samo upisuje u izveštaj
(`retainedDirectoryModes`).

## Smoke

Posle svake grupe radimo kratku proveru života: `/up`, `/login`, jedna pravna stranica,
`/katalog` sa noindex-om, prvi proizvod i `GET /tema` 405. Ako padne, instalacija staje i vraća
staro stanje. Odgovor 429 je neodlučan i instalacija ide dalje.

Pun smoke ne zahteva prijavu, koristi samo GET i jedan HEAD:

- Sve što je B1149 postavio ostaje, u starom i u novom stanju: nonce skripta, galerija,
  `private, no-store` na katalogu, `/site-assets/` logo, gost na `/admin/settings/smtp` dobija 302
  na prijavu.
- Javni `/katalog` ne sadrži potpisane URL-ove. Zato je jedini izvor uzorka CLI pomoćnik
  `media-sample` u `b1150-app-check.php`. On pravi URL kroz `ProductImage` accessor za prvu sliku
  koja ima fajl (samo čitanje), i broji slike čiji je URL prazan ili ne počinje sa
  `https://cms.ald1n.com/media/p/`. Svaka takva slika je greška: tako hvatamo slučaj kad model
  tiho vrati prazan URL.
- GET na taj URL daje 200, `image/jpeg`, `image/png` ili `image/webp`, `nosniff`, `ETag`,
  `Cross-Origin-Resource-Policy: cross-origin`, direktive `private`, `max-age=604800` i `immutable`
  (proveravamo ih kao skup, redosled nije bitan), i nijedan `Set-Cookie`.
- Isti zahtev sa `If-None-Match` daje 304. Izmenjen potpis, odsutan potpis i nepoznata slika daju
  404 bez `Set-Cookie`.
- Javna `/katalog/slika/...` ruta ostaje 200, kad je katalog povezuje.
- Bez URL-a, a sa slikama u bazi, instalacija pada i vraća staro stanje. Samo kad u bazi nema
  nijedne slike sa fajlom, provera rute je `INFO`.
- Posle instalacije `php artisan app:product-media-doctor` mora da vrati kod 0, bez reda `FAIL` i
  sa završnom linijom `Galerija proizvoda je spremna.`. Red `PRODUCT_MEDIA_DOCTOR` sa
  `label: after-install` to beleži.
- Rute iz B1146 do B1149 ostaju, nova ruta postoji, `laravel.log` nema novih grešaka, status mail
  transporta je nepromenjen i svih fajlova u stablu ima tačan digest.

Ako nešto padne, sami vraćamo stare fajlove obrnutim redom, uz `view:clear` i puna čekanja gde je
potrebno. Kraj je `B1150_FINAL=HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`. Uporni 429, isključena
prodavnica ili nestali `noindex` tokom provera ostavljaju tačne fajlove i daju
`B1150_FINAL=HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: ista komanda kasnije ponavlja samo provere.

Vrednosti `B1150_FINAL`:

- `PASS_INSTALLED_PRODUCT_MEDIA_ROUTE_SHOP_STATE_UNCHANGED`: instalirano i provereno, `EXIT=0`.
- `PASS_ALREADY_INSTALLED`: ponovno pokretanje posle uspeha, ništa se ne menja.
- `PASS_ROLLED_BACK_BY_OWNER`: `--rollback` je završen.
- `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`: instalacija je pala i vraćena je na B1149 stanje.
- `HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED`: fajlovi su tačni, ali provere nisu mogle da se zaključe.
- `HOLD_ROLLBACK_INCOMPLETE`: vraćanje je stalo, vidi `RECOVERY_NOTE`.
- `HOLD`: staje se pre ijedne izmene.

## Šta radite vi posle

**a) Slike.** Otvorite `https://cms.ald1n.com/katalog` i jedan proizvod u privatnom prozoru. Zatim
otvorite admin katalog i Android aplikaciju i potvrdite da prikazuju slike.

**b) Prava direktorijuma.** Dana 12.11.2026. su privremeno otvorena sa `o+x` da bi `/storage`
proradio. Vratite ih tek kad su mejlovi iz reda čekanja, koji nose stare `/storage` adrese, poslati
i pročitani. Preporuka je da sačekate nekoliko dana.

```
chmod o-x ~/.ald1n-b1134-retained ~/.ald1n-b1134-retained/99111dcf09304b97aac05be0ef6ee1ee
```

Posle toga stare `/storage` adrese prestaju da rade. Ovaj korak je opcioni i skripta ga ne
izvršava.

**c) Trajanje linkova.** Potpisani URL-ovi važe bez isteka. Link prestaje da radi kad se obriše
slika ili kad se rotira `APP_KEY`. Pri rotaciji stari ključ upišite u `APP_PREVIOUS_KEYS`, bez
razmaka oko zareza, da već poslati linkovi ostanu važeći.

**d) Neaktivni artikli.** Slike neaktivnih i arhiviranih artikala dostupne su svakome ko ima URL.
To je prihvaćena odluka vlasnika.

## Vraćanje na staro

```
bash /home/icaffeco/ald1n-project/incoming/b1150-installer.sh --rollback
bash /home/icaffeco/ald1n-project/incoming/b1150-installer.sh --rollback --ignore-schedule
```

Druga komanda preskače samo proveru backup termina. Koristite je kad je backup blizu a vraćanje ne
može da čeka. Prodavnica ostaje u stanju u kom jeste. Kraj je `B1150_FINAL=PASS_ROLLED_BACK_BY_OWNER`.

Ako vraćanje stane na pola, izveštaj ima `RECOVERY_NOTE` i blok od `bash -e <<'B1150_RECOVERY'`
do `B1150_RECOVERY`. Prvo pokrenite istu komandu još jednom. Blok nalepite ceo samo ako to ne
uspe.

## Šta ne diramo

Prekidač prodavnice i odobrenje trgovca samo čitamo. SMTP podešavanja ne čitamo, ne menjamo i ne
testiramo. Nema migracija, ne menjamo `.env` ni `config/`, ne pravimo keš ruta, OPcache ne
resetujemo nego ciljano osvežavamo fajlove iz paketa, a šablone samo brišemo (`view:clear`).
`queue:restart` samo poručuje queue radnicima da se ponovo pokrenu.

Posle B1150 ne pokrećite B1144 do B1149 ni njihov `--rollback`.

## Šablon izveštaja (popunjavamo iz `1116-B1150-INSTALLER-*.md`)

| Polje | Vrednost |
| --- | --- |
| Broj izveštaja i vreme | 1116, datum i vreme početka |
| Glava izvora | `14e628ba` (grana `codex/product-media-route-20261112`) |
| Pre-stanje | stablo posle B1149 (`7e748158`), 8564 fajla |
| Ishod | `B1150_FINAL=...` i `EXIT=...` |
| `PREFLIGHT` blockers | prazno |
| `limiterStore` | `redis`, `file`, `database` ili `array` (informacija) |
| `PER_FILE_BACKUP` | kopije 3 izmenjena fajla, 2 nova zabeležena kao odsutna |
| `PUBLIC_HTTP_SMOKES` (INSTALLED) | `failed: []`, `MEDIA_ROUTE_IMAGE` PASS |
| `PRODUCT_MEDIA_DOCTOR` | `after-install`, `rc: 0`, `ready: true` |
| `MAIL_TRANSPORT_FINGERPRINT` | `status: MAIL_TRANSPORT_UNCHANGED` |
| `ACTIVE_TREE_AFTER_INSTALL` | 8566 fajlova |
| `MERCHANT_APPROVAL` | `UNCHANGED` |
| Nije proveravano | izgled na telefonu, Android aplikacija, slike u mejlovima |
