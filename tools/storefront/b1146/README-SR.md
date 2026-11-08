# B1146: pravne stranice na produkciji (prodavnica ostaje zatvorena)

Autor paketa: Ald1n <https://ald1n.com>

## Pre pokretanja

Pravne stranice odmah posle instalacije javno prikazuju PIB, matični broj, telefon, mejl i adresu
firme iz podešavanja za PDF, bez obzira na to da li je prodavnica uključena. Zato prvo proverite
ta podešavanja u CMS-u.

## Šta pokrećete

Jedna komanda preko SSH-a:

```
bash /home/icaffeco/ald1n-project/incoming/b1146-legal-overlay.sh
```

Skripta prvo očisti terminal, pa pokrene radnika u pozadini. Izveštaj ide u
`docs/operations/<broj>-B1146-LEGAL-OVERLAY-<datum>-<vreme>.md`. Ako se SSH veza prekine,
posao se nastavlja, a izveštaj ostaje na istom mestu.

Instaliramo tačno 21 fajl iz PR #152 (main `eabe16af`): 12 novih i 9 izmenjenih. Testove ne
instaliramo. Pre svake izmene proveravamo da je na serveru tačno očekivana verzija fajla. Ako
nađemo bilo šta drugo, ništa ne diramo i staje se sa `HOLD_PREFLIGHT_BLOCKED`. Isto važi ako
radi neka artisan komanda, ako nema dovoljno mesta na disku ili u kvoti naloga (kada je kvota
čitljiva), ako je composer autoload u autoritativnom režimu ili ako je prošlo više od 15 minuta
od početka provere.

## Koliko traje

Obično 4 do 6 minuta. Najduži deo je čekanje od oko 2 minuta posle izmene, dok svi PHP procesi
ne vide nove fajlove. Skripta ne radi u periodu od 30 minuta pre zakazanog backup-a (02:30 svaki
dan, 03:10 nedeljom). Taj period proveravamo dva puta: na početku i neposredno pre prve izmene.

## Šta proveravamo sami

- `/login` i `/up` vraćaju 200.
- Osam pravnih stranica vraća 200 sa `X-Robots-Tag: noindex`, `Content-Security-Policy` sa
  `script-src 'none'` i `X-Frame-Options: DENY`: `/pravne-informacije`, `/uslovi-prodaje`,
  `/politika-privatnosti`, `/podaci-o-trgovcu`, `/pravo-na-odustanak`, `/reklamacije`,
  `/dostava-i-placanje`, `/kolacici`.
- `/katalog`, `/korpa`, `/porucivanje` i `/registracija` i dalje vraćaju 404.
- U `laravel.log` nema novih grešaka, a svih 8543 fajlova ima tačan digest.

Ako bilo koja provera padne, sami vraćamo stare fajlove, bajt po bajt, i ponovo proveravamo
staro stanje. Rezultat je tada `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`.

Uspešan kraj je `B1146_FINAL=PASS_INSTALLED_LEGAL_OVERLAY_STOREFRONT_CLOSED`.

Kompajlirane Blade šablone samo brišemo (`view:clear`). Laravel ih pravi ponovo pri prvom
otvaranju stranice. Ne pokrećemo `view:cache`.

## Šta proveravate vi

1. Ako je Cloudflare keš uključen, posle instalacije očistite tačno jedan URL:
   `https://cms.ald1n.com/assets/css/ald1n-public-storefront.css`. Druga mogućnost je da
   sačekate da keš istekne. Do tada se pravni blokovi prikazuju bez stilova.
2. Prijavite se na cms.ald1n.com kao SuperAdmin.
3. Otvorite `/admin/settings/public-storefront`. Pogledajte listu za uključivanje i linkove ka
   pravnim tekstovima.
4. Pogledajte liniju `OWNER_NOTICE_MERCHANT_APPROVAL` u izveštaju. Ona kaže jedno od dvoje:
   ova instalacija je poništila odobrenje trgovca (`THE_MERCHANT_APPROVAL_IS_RESET`), ili
   odobrenje nije važilo ni pre instalacije (`THE_MERCHANT_APPROVAL_WAS_NOT_VALID_BEFORE_THIS_INSTALL`).
   U oba slučaja pregledajte pravne tekstove i ponovo potvrdite odobrenje.
5. Prodavnica ostaje isključena dok je vi ne uključite.

Ako izveštaj ima `LEFTOVER_FRESHNESS_PROBE_REQUIRES_OPERATOR_INSPECTION`, ništa nije
instalirano. To je privremeni probni fajl iz prekinutog pokretanja. Pokrenite istu komandu još
jednom: ona ga ukloni i nastavi.

## Ponovno pokretanje

Dok je prodavnica isključena, ista komanda posle uspeha samo proveri stanje i završi sa
`PASS_ALREADY_INSTALLED`. Kada je prodavnica uključena, provera staje sa
`HOLD_INSTALLED_REVERIFY` i ništa ne menja.

## Vraćanje na staro

Prvo isključite prodavnicu u adminu. Dok je uključena, vraćanje ne počinje
(`HOLD_ROLLBACK_NOT_STARTED`, ništa nije promenjeno). Zatim:

```
bash /home/icaffeco/ald1n-project/incoming/b1146-legal-overlay.sh --rollback
```

Pre početka proveravamo i da ne radi artisan komanda i da nije blizu zakazani backup.

Vraćanje radi obrnutim redom od instalacije. Vraća tačne privatne kopije iz
`~/.ald1n-b1146-state`, briše 12 novih fajlova i vraća keš ruta kakav je bio. Kraj je
`PASS_ROLLED_BACK_BY_OWNER`.

Ako jedan fajl ne može da se vrati, stajemo pre sledeće grupe. Ranije grupe ostaju
instalirane, jer ih kasnije grupe koriste, pa sajt i dalje radi. Rezultat je
`HOLD_ROLLBACK_INCOMPLETE`, a izveštaj ima `RECOVERY_NOTE` i `OWNER_COMMAND` linije u tačnom
redosledu. Prvo otklonite uzrok iz izveštaja i pokrenite istu komandu još jednom: ona prvo
ponovo pokuša vraćanje. Ručne komande su samo za slučaj da i to ne uspe.

Posle vraćanja:

1. Ponovo očistite isti CSS URL u Cloudflare-u ili sačekajte da keš istekne.
2. Otvorite `/admin/settings/public-storefront` i proverite odobrenje trgovca. Odobrenje dato
   dok su pravne stranice bile instalirane više ne odgovara starom kodu. Potvrdite ga ponovo pre
   uključivanja prodavnice.

## Šta ne diramo

Bazu ne menjamo: nema migracija, a prekidač prodavnice i odobrenje trgovca samo čitamo. Ne
menjamo `.env` ni `config/`. Ne radimo OPcache reset, samo ciljano osvežavamo navedene fajlove.
Novi fajlovi i folderi dobijaju istu grupu kao folder u kom se nalaze. Ne šaljemo mejlove i ne
diramo druge sajtove na nalogu.

Posle B1146 ne pokrećite ponovo B1144 ni B1145. Njihove provere bi videle 9 izmenjenih fajlova
kao nepoznate.
