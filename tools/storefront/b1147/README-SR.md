# B1147: novi izgled kataloga na produkciji (prodavnica ostaje kako jeste)

Autor paketa: Ald1n <https://ald1n.com>

## Šta pokrećete

Prvo raspakujte ZIP u `/home/icaffeco/ald1n-project` (fajlovi idu u `incoming/`). Zatim jedna
komanda preko SSH-a:

```
bash /home/icaffeco/ald1n-project/incoming/b1147-catalog-overlay.sh
```

Skripta pokrene radnika u pozadini. Izveštaj ide u
`docs/operations/<broj>-B1147-CATALOG-OVERLAY-<datum>-<vreme>.md`. Ako se SSH veza prekine,
posao se nastavlja.

Instaliramo tačno 8 fajlova iz PR #154 (`764bf513`): 3 nova i 5 izmenjenih. Redosled: dve nove
klase u `app/Support`, servis i kontroler, CSS, pa tek na kraju Blade šabloni. Testove ne
instaliramo.

## Pre početka proveravamo

B1147 radi samo posle uspešnog B1146: tražimo tačno jedan izveštaj `1109-B1146-LEGAL-OVERLAY`
sa `B1146_FINAL=PASS_INSTALLED_LEGAL_OVERLAY_STOREFRONT_CLOSED`. Svih 8543 fajlova mora imati
tačan digest, a svaki od 8 fajlova tačno očekivanu staru verziju. Prodavnica mora biti
uključena, jer samo tada možemo da proverimo novi katalog. Ako je isključena, staje se sa
`HOLD_PREFLIGHT_BLOCKED` i ništa nije promenjeno. Isto važi ako radi artisan komanda, ako je
blizu zakazani backup (02:30 svaki dan, 03:10 nedeljom), ako nema mesta na disku ili ako
`APP_URL` nije čist `https://` origin.

## Koliko traje

Obično 4 do 6 minuta. Najduže traje čekanje od oko 2 minuta dok svi PHP procesi ne vide nove
fajlove. OPcache ne resetujemo, samo ciljano osvežavamo ovih 8 fajlova.

## Šta proveravamo sami (samo GET zahtevi)

`/katalog` vraća 200 sa novim izgledom, jednim ispravnim JSON-LD blokom i CSP `script-src
'none'`. Sortiranje po ceni vraća 200, nepoznato sortiranje nikad 500, a `?page=999` vraća 404.
Osam pravnih stranica, `/login`, `/up`, `/korpa` i `/registracija` vraćaju 200. CSS sadrži nova
pravila. U `laravel.log` nema novih grešaka, a svih 8546 fajlova ima tačan digest.

Ako bilo šta padne, sami vraćamo stare fajlove bajt po bajt i ponovo proveravamo staro stanje
(`HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`). Uspešan kraj je
`B1147_FINAL=PASS_INSTALLED_CATALOG_OVERLAY_SHOP_STATE_UNCHANGED`.

## Šta radite vi posle instalacije

1. U Cloudflare-u očistite oba URL-a:
   `https://cms.ald1n.com/assets/css/ald1n-public-storefront.css` i
   `https://cms.ald1n.com/assets/css/ald1n-public-storefront.css?v=202611082`.
   Cloudflare ključ keša sadrži i query string, pa je svaki URL poseban unos. Do čišćenja
   stranica može da dobije stari CSS. Ako izveštaj ima `OWNER_NOTICE_CSS`, čišćenje je obavezno.
2. Otvorite `https://cms.ald1n.com/katalog` u privatnom prozoru, na telefonu i na računaru, u
   svetloj i tamnoj temi. Probajte filtere, sortiranje, stranice i dugme za povratak na vrh.
3. Otvorite jednu pravnu stranicu, na primer `/pravne-informacije`, i pogledajte novo zaglavlje.

## Vraćanje na staro

```
bash /home/icaffeco/ald1n-project/incoming/b1147-catalog-overlay.sh --rollback
```

Prodavnica može ostati uključena, jer ovih 8 fajlova ne menja podatke ni rute. Ako je zakazani
backup blizu, a vraćanje ne može da čeka:

```
bash /home/icaffeco/ald1n-project/incoming/b1147-catalog-overlay.sh --rollback --ignore-schedule
```

Ova opcija preskače samo proveru backup termina. Artisan komanda i dalje ne sme da radi. Kraj je
`PASS_ROLLED_BACK_BY_OWNER`. Posle vraćanja ponovo očistite oba CSS URL-a.

Ako vraćanje stane na pola, izveštaj ima `RECOVERY_NOTE` i blok od linije
`bash -e <<'B1147_RECOVERY'` do linije `B1147_RECOVERY`. Prvo otklonite uzrok i pokrenite istu
komandu još jednom. Blok koristite samo ako i to ne uspe: nalepite ga ceo, on proverava sha256
svake kopije i staje na prvoj grešci.

## Šta ne diramo

Prekidač prodavnice samo čitamo: ostaje u stanju u kom ste ga ostavili. Nema migracija, ne
menjamo `.env`, `config/` ni keš ruta. Odobrenje trgovca ostaje važeće, jer ovih 8 fajlova ne
ulazi u njegov digest. Blade šablone samo brišemo (`view:clear`), Laravel ih pravi ponovo.

Posle B1147 ne pokrećite ponovo B1144, B1145 ni B1146. Njihove provere bi videle 8 fajlova
kataloga kao nepoznate.
