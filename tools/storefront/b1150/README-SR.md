# B1150: slike artikala preko potpisane rute

Autor paketa: Ald1n <https://ald1n.com>

Ovaj paket je napravljen i proveren samo u CI-ju. Na produkciji još nije pokrenut.

## Šta se instalira

Instaliramo fajlove iz liste `STEPS` (tačan broj novih i izmenjenih fajlova piše u liniji
`B1150_RESULT` u izveštaju), svi pod `apps/cms/current`. Nema migracija, nema izmena `composer`-a, `.env`-a ni `config/`, testove ne
instaliramo.

Slike artikala prelaze sa `/storage` URL-ova na novu potpisanu Laravel rutu
`GET /media/p/{image}/{rendition}?v=&s=`. Ruta radi bez sesije, ne postavlja kolačić i odgovara sa
`Cache-Control: private`. Potpis proverava server, pa pogrešan potpis daje 404. Javna ruta
`/katalog/slika/...` ostaje kakva jeste.

Prekidač prodavnice i odobrenje trgovca samo čitamo. SMTP podešavanja ne čitamo i ne menjamo, a
probni mejl ne šaljemo. Mail transport poredimo samo preko HMAC otiska koji živi u privatnom
`state.json` (0600); u izveštaju je samo status `MAIL_TRANSPORT_UNCHANGED` ili
`MAIL_TRANSPORT_CHANGED`.

## Redosled

Redosled fajlova i grupe (A do H) određuje spisak koraka u paketu. Pravila su ista kao kod B1149:
nove klase, pa izmenjeni servisi, kontroleri, middleware, `routes/web.php`, `bootstrap/app.php`,
puno čekanje, pa CSS i pogledi, na kraju `bootstrap/providers.php`. Svaki fajl ide kroz privremeni
fajl u istom direktorijumu, `fsync` i `rename` (`link` za nov fajl). Posle toga: `route:clear`,
`view:clear`, `queue:restart` i ciljano osvežavanje OPcache-a po fajlu. Keš ruta ne postoji i ne
pravimo ga.

Pre-stanje je stablo posle B1149: runtime manifest plus B1146, B1147, B1148 i B1149, ukupno 8564
fajla. Pri građenju paketa digest svakog izmenjenog fajla (git blob iz `7e748158`) mora da se
poklopi sa digestom iz B1149 payload-a ili ranijih slojeva; inače se paket ne gradi. Poređenje sa
živim stablom radi skripta na hostu, pre prve izmene.

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
   proverava sama; ovde samo znate šta očekujemo.
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
   Europe/Belgrade) ima dovoljno vremena. Potrebno vreme skripta izračuna sama: u `PREFLIGHT`
   izveštaju pročitajte `timeBound.requiredWindowSeconds` (nikad manje od 30 minuta). Ako ga nema,
   skripta staje pre izmene.

## Šta pokrećete

```
unzip -o /home/icaffeco/b1150-operator.zip -d ~/ald1n-project
bash /home/icaffeco/ald1n-project/incoming/b1150-installer.sh
```

Radnik ide u pozadini, a izveštaj se upisuje u
`docs/operations/<broj>-B1150-INSTALLER-<datum>-<vreme>.md`. Sledeći broj je 1116 (1115 je PASS
izveštaj B1149, 1114 je bio HOLD). Ako se SSH prekine, posao se nastavlja; pratite ga sa
`tail -f` na izveštaj.

Od pokretanja do kraja izveštaja 1116 ne menjajte nijedno Cloudflare podešavanje (Rocket Loader,
Web Analytics, Email Obfuscation). Za B1150 nije potreban nijedan purge. Nova ruta je potpisana,
bez sesije i odgovara sa `private`, pa je Cloudflare ne kešira.

## Šta proveravamo pre početka

Svih 8564 fajla mora imati tačan digest (manifest plus B1146, B1147, B1148 i B1149), a svaki
izmenjeni fajl tačno staru verziju. Staje se i ako postoji keš ruta, ako je prodavnica isključena,
ako radi artisan komanda, ako je backup blizu, ako nema mesta na disku ili u kvoti, ako je
composer autoload autoritativan, ako APP_URL nije čist https origin, ako u CLI-ju fali PHP
ekstenzija ili ako javne stranice nemaju `X-Robots-Tag: noindex`.

Skripta ne zavisi od prava direktorijuma `~/.ald1n-b1134-retained` i njegovog podfoldera. Prava
0750 i 0751 su oba prihvaćena; trenutna vrednost se samo upisuje u izveštaj.

## Smoke

Posle svake grupe radimo kratku proveru života: `/up`, `/login`, jedna pravna stranica,
`/katalog` sa noindex-om, prvi proizvod i `GET /tema` 405. Ako padne, instalacija staje i vraća
staro stanje. Odgovor 429 je neodlučan i instalacija ide dalje.

Pun smoke ne zahteva prijavu, koristi samo GET i jedan HEAD:

- Sve što je B1149 postavio ostaje: nonce skripta, galerija, `private, no-store` na katalogu,
  `/site-assets/` logo, gost na `/admin/settings/smtp` dobija 302 na prijavu.
- URL slike uzimamo iz javnog HTML-a stranice `/katalog` ili prve stranice artikla. GET na njega
  daje 200, `image/*`, `nosniff`, `Cache-Control` sa `private`, `max-age` i `immutable`, i nijedan
  `Set-Cookie`.
- Isti URL sa izmenjenim potpisom, i bez potpisa, daje 404 bez `Set-Cookie`.
- Javna `/katalog/slika/...` ruta ostaje 200.
- Ako stranice ne povezuju potpisani URL, CLI pomoćnik `media-sample` u `b1150-app-check.php`
  izda jedan potpisani URL za prvu sliku artikla iz baze (samo čitanje). Bez URL-a, a sa slikama u
  bazi, instalacija pada i vraća staro stanje. Samo kad u bazi nema nijedne slike artikla, provera
  rute je `INFO` (nema šta da se potpiše).
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

Otvorite `https://cms.ald1n.com/katalog` i jedan proizvod u privatnom prozoru. Zatim proverite
slike u admin katalogu i u Android aplikaciji.

Tek kad potvrdite da admin katalog i Android prikazuju slike preko nove rute, možete vratiti prava
direktorijuma. Dana 12.11.2026. su privremeno otvorena sa `o+x` da bi `/storage` proradio:

```
chmod o-x ~/.ald1n-b1134-retained ~/.ald1n-b1134-retained/99111dcf09304b97aac05be0ef6ee1ee
```

Pošto to uradite, stari keširani `/storage` URL-ovi u već poslatim mejlovima prestaju da rade.
Ako vam ti linkovi trebaju, ostavite prava kakva jesu. Ovaj korak je opcioni i skripta ga ne
izvršava.

## Vraćanje na staro

```
bash /home/icaffeco/ald1n-project/incoming/b1150-installer.sh --rollback
bash /home/icaffeco/ald1n-project/incoming/b1150-installer.sh --rollback --ignore-schedule
```

Druga komanda preskače samo proveru backup termina; koristite je kad je backup blizu a vraćanje
ne može da čeka. Prodavnica ostaje u stanju u kom jeste. Kraj je
`B1150_FINAL=PASS_ROLLED_BACK_BY_OWNER`.

Ako vraćanje stane na pola, izveštaj ima `RECOVERY_NOTE` i blok od `bash -e <<'B1150_RECOVERY'`
do `B1150_RECOVERY`. Prvo pokrenite istu komandu još jednom; blok nalepite ceo samo ako to ne
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
| Glava izvora | upisuje se kad je payload gotov |
| Pre-stanje | stablo posle B1149 (`7e748158`), 8564 fajla |
| Ishod | `B1150_FINAL=...` i `EXIT=...` |
| `PREFLIGHT` blockers | prazno |
| `PER_FILE_BACKUP` | kopije izmenjenih fajlova, novi fajlovi zabeleženi kao odsutni |
| `PUBLIC_HTTP_SMOKES` (INSTALLED) | `failed: []`, media red PASS ili INFO |
| `MAIL_TRANSPORT_FINGERPRINT` | `status: MAIL_TRANSPORT_UNCHANGED` |
| `ACTIVE_TREE_AFTER_INSTALL` | broj fajla posle instalacije |
| `MERCHANT_APPROVAL` | `UNCHANGED` |
| Nije proveravano | izgled na telefonu, Android aplikacija, slike u mejlovima |
