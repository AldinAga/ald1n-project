# RC operativni plan

## Cilj

`v2.1.0-rc1` je zamrznuta funkcionalna osnova. Tokom RC perioda dozvoljene su samo:

- ispravke kriticnih i visokih gresaka;
- bezbednosne ispravke;
- korekcije koje sprecavaju gubitak ili pogresan prikaz podataka;
- dokumentacija i testovi.

Novi poslovni moduli i veliki UI redizajn cekaju stabilnu verziju ili sledeci minor release.

## Minimalni period

Pre oznake `v2.1.0 Stable` preporucen je najmanje 7 radnih dana, idealno 14 kalendarskih dana stvarnog rada.

## Dnevna kontrola

```bash
cd /home/icaffeco/cms.ald1n.com
/usr/local/bin/php artisan app:system-health --snapshot
/usr/local/bin/php artisan app:release-check --profile=quick --strict
```

Pregledati:

- `storage/logs/laravel.log`;
- scheduler heartbeat;
- neuspele automation i outbox runove;
- poslednji backup i njegovu velicinu;
- security dogadjaje;
- ERROR/CRITICAL log zapise.

## Nedeljna kontrola

```bash
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
/usr/local/bin/php artisan app:release-check --profile=rc --render --strict --snapshot
```

## Obavezni poslovni scenariji

Tokom RC perioda potvrditi najmanje po jedan stvaran ili kontrolisan test za:

1. prijavu administratora i kupca;
2. aktivaciju Customer Portal naloga;
3. kreiranje porudzbine sa varijantom;
4. rezervaciju i povrat lagera;
5. uplatu i verifikaciju dokaza;
6. predacun, racun i PDF;
7. isporuku i dokaz isporuke;
8. garanciju;
9. reklamaciju i servisni tok;
10. portal poruke i internu belesku;
11. upravljacki izvestaj i PDF/CSV;
12. rucni backup i odvojenu restore probu.

## Blokatori Stable verzije

Stable se ne objavljuje ako postoji bilo sta od sledeceg:

- `app:release-check --profile=rc --render --strict` ne prolazi;
- otvorena kriticna ili high severity greska;
- rucna intervencija u bazi bez audit traga;
- gubitak, dupliranje ili pogresno knjizenje poslovnih podataka;
- backup nije prosao `app:backup-verify`;
- restore proba nije izvrsena na odvojenoj bazi;
- scheduler, outbox ili automatizacija nisu stabilni;
- aktivan `APP_DEBUG=true` ili okruzenje nije `production`;
- postoje WARN rezultati u strict profilu.

## Evidencija incidenta

Za svaku RC gresku sacuvati:

- datum i vreme;
- korisnika i akciju;
- request ID iz loga;
- URL ili Artisan komandu;
- relevantan screenshot;
- JSON release report;
- SQL ili model ID bez osetljivih podataka;
- opis ispravke i regresioni test.
