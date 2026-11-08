# B1146: pravne stranice na produkciji (prodavnica ostaje zatvorena)

Autor paketa: Ald1n <https://ald1n.com>

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
nađemo bilo šta drugo, ništa ne diramo i staje se sa `HOLD_PREFLIGHT_BLOCKED`.

## Koliko traje

Obično 4 do 6 minuta. Najduži deo je čekanje od oko 2 minuta posle izmene, dok svi PHP procesi
ne vide nove fajlove. Skripta ne radi u periodu od 30 minuta pre zakazanog backup-a (02:30 svaki
dan, 03:10 nedeljom).

## Šta proveravamo sami

- `/login` i `/up` vraćaju 200.
- Osam pravnih stranica vraća 200 sa `X-Robots-Tag: noindex`: `/pravne-informacije`,
  `/uslovi-prodaje`, `/politika-privatnosti`, `/podaci-o-trgovcu`, `/pravo-na-odustanak`,
  `/reklamacije`, `/dostava-i-placanje`, `/kolacici`.
- `/katalog`, `/korpa`, `/porucivanje` i `/registracija` i dalje vraćaju 404.
- U `laravel.log` nema novih grešaka, a svih 8543 fajlova ima tačan digest.

Ako bilo koja provera padne, sami vraćamo stare fajlove, bajt po bajt, i ponovo proveravamo
staro stanje. Rezultat je tada `HOLD_ROLLED_BACK_OLD_STATE_VERIFIED`.

Uspešan kraj je `B1146_FINAL=PASS_INSTALLED_LEGAL_OVERLAY_STOREFRONT_CLOSED`.

## Šta proveravate vi

1. Prijavite se na cms.ald1n.com kao SuperAdmin.
2. Otvorite `/admin/settings/public-storefront`. Pogledajte listu za uključivanje i linkove ka
   pravnim tekstovima.
3. Odobrenje trgovca je poništeno. Pravni tekstovi su sada deo onoga što potpisujete, pa
   pregledajte tekstove i ponovo potvrdite odobrenje.
4. Prodavnica ostaje isključena dok je vi ne uključite.

## Vraćanje na staro

```
bash /home/icaffeco/ald1n-project/incoming/b1146-legal-overlay.sh --rollback
```

Vraća tačne privatne kopije iz `~/.ald1n-b1146-state`, briše 12 novih fajlova i vraća keš ruta
kakav je bio. Kraj je `PASS_ROLLED_BACK_BY_OWNER`. Ako vraćanje ne uspe do kraja, izveštaj ima
`RECOVERY_NOTE` i tačne `OWNER_COMMAND` linije.

Ponovno pokretanje iste komande posle uspeha samo proveri stanje i završi sa
`PASS_ALREADY_INSTALLED`.

## Šta ne diramo

Bazu ne menjamo: nema migracija, a prekidač prodavnice i odobrenje trgovca samo čitamo. Ne
menjamo `.env` ni `config/`. Ne radimo OPcache reset, samo ciljano osvežavamo navedene fajlove.
Ne šaljemo mejlove i ne diramo druge sajtove na nalogu.

Posle B1146 ne pokrećite ponovo B1144 ni B1145. Njihove provere bi videle 9 izmenjenih fajlova
kao nepoznate.
