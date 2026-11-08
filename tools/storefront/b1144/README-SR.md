# B1144 — kontrolisana OFF instalacija CMS-a (lean)

Autor izrade: Ald1n (https://ald1n.com)

B1144 instalira main runtime (izvor `ef3f927`, CMS stablo `da8ac0c9`) u `apps/cms/current`. Javna prodavnica i checkout ostaju isključeni kroz OFF clamp u `config/public_storefront.php`. Ne pokrećemo migracije, ne menjamo `.env` i ne diramo bazu.

B1143 je stao 08.11.2026 na proveri docroot-a preko cPanel `uapi`, koja sa SSH naloga ne radi (Report1104, `ROLLED_BACK_PRE_SWITCH`, kod i vendor netaknuti). B1144 kreće tačno od tog stanja i prihvata ga samo ako se Report1104 bajt po bajt poklapa (sha256 `3b469c0d…853330`).

## Komanda

```bash
bash /home/icaffeco/ald1n-project/incoming/b1144-controlled-install.sh
```

Pokreće se sa SSH naloga `icaffeco`, bez opcija. Skripta sama kreira izveštaj u `docs/operations/` sa sledećim slobodnim brojem.

## Raspakivanje

ZIP sadrži samo `incoming/b1144-*` fajlove. Raspakuje se u `/home/icaffeco/ald1n-project/`, tako da fajlovi završe u `incoming/`. Fajlovi `b1134-release.php`, `b1134-files.php` i `b1134-scheduler.php` već postoje na hostu iz ranijih paketa; B1144 ih ponovo ne isporučuje. Proverava im sha256.

## Šta B1144 radi, redom

1. Proverava integritet paketa, predhodnika (B1134 → B1143) i runtime manifest od 8531 fajla. Do ovde se ništa ne upisuje.
2. Proverava direktorijume samo na fajl-sistemu: stvarne putanje (ne symlink), vlasnik je `icaffeco`, nema upisa za grupu ili sve, nema `public/.user.ini`.
3. Dijagnostika: broji fajlove koji se razlikuju po sadržaju, a imaju isti mtime (±1 s). Ovo je samo informacija za izveštaj.
4. Postavlja sopstveni maintenance barijer na oba stabla, pauzira scheduler i proverava da `/login` vraća 503 sa našim pečatom.
5. Uski drain: čeka da nestanu `lsphp` procesi ovog CMS-a i svi `artisan` procesi (najviše 90 s). Ako ne nestanu, vraćamo sve kako je bilo, pre bilo kakve zamene.
6. Lokalno, PHP CLI-jem, proverava sha256 svih 8531 fajlova kandidata. Beleži veličine Laravel logova.
7. Zamena direktorijuma (dva rename-a, sa journal-om), zatim `package:discover` i `view:cache`.
8. Provera svežine koda na webu: privremeni PHP fajl sa nasumičnim imenom u novom `public/`, dostupan samo uz tajni header, samo GET i samo za `cms.ald1n.com`, važi najviše 1800 s. Javlja SAPI, docroot i OPcache podešavanja, poredi sha256 novih fajlova i poziva `opcache_invalidate` samo za skripte iz CMS release stabala. `opcache_reset` se nikad ne poziva. Fajl se briše posle provere i na svakoj grešci. Ime i token se nikad ne ispisuju.
9. Pod barijerom: `php artisan about` i `php artisan migrate:status` (samo čitanje). Moraju biti pending tačno tri migracije: `2026_10_23_020000_create_public_order_accesses`, `2026_10_23_020100_create_public_checkout_attempts`, `2026_10_24_030000_add_retail_customer_role`.
10. Skida barijer i proverava: `/login` 200, `/up` 200, `/katalog` 404, `/registracija` 404, `/korpa` 404. U Laravel logovima ne sme biti novih ERROR/CRITICAL linija od zamene.
11. Tek tada je instalacija potvrđena (`COMMITTED_OFF`), scheduler se pušta i stanje se zatvara (`CLOSED_INSTALL`).

Ako bilo koji korak posle zamene padne, B1144 sam vraća stari kod: ponovo postavlja barijer, vraća direktorijume, proverava svežinu starog koda na webu, skida barijer, proverava `/login` 200 i pušta scheduler. To važi i kada padne provera posle skidanja barijera.

## Kako čitati rezultat

- `B1144_FINAL=PASS_INSTALLED_OFF` i `EXIT=0`: instalirano, prodavnica i checkout su OFF.
- `RELEASE_PHASE="ROLLED_BACK_PRE_SWITCH"`: stali smo pre zamene, stari kod i vendor nisu dirani.
- `RELEASE_PHASE="ROLLED_BACK"`: zamena je urađena pa automatski vraćena, stari kod radi i proveren je.
- `HOLD_RECOVERY_OWNED_MAINTENANCE_RETAINED`: povratak nije završen i barijer namerno ostaje. Ponovno pokretanje iste komande nastavlja oporavak.
- `HOLD_INSTALLED_OFF_FINISH_PENDING`: novi kod je potvrđen, ostao je samo završni korak (npr. puštanje schedulera). Ponovno pokretanje ga završava, bez vraćanja koda.
- `LEFTOVER_FRESHNESS_PROBE_REQUIRES_OPERATOR_INSPECTION`: u `public/` postoji `ald1n-b1144-*.php` koji ovo pokretanje nije napravilo. Ne brišemo ga sami; pošaljite izveštaj.

Posle `ROLLED_BACK` ili `ROLLED_BACK_PRE_SWITCH` ista komanda ne instalira ponovo (`PRIOR_ATTEMPT_ROLLED_BACK_NO_AUTOMATIC_REDEPLOY`). Za novi pokušaj pravimo novi paket.

## Šta je uklonjeno u odnosu na B1143

cPanel `uapi`/`DomainInfo`, `.htaccess` overlay `php_flag opcache.enable Off`, A/B witness, LSAPI kalibracija 44/45 i held-execution gate, grupna provera procesa (HostProcessGuard), web petlja `verify-files` i docroot screen.

## Šta ne možemo da dokažemo bez root pristupa

Da li isti `public/` direktorijum koristi još neki hostname. Provera iz koraka 2 je samo na fajl-sistemu. Ostala ograničenja su u izveštaju iz CI-ja.

Retained stablo (`~/.ald1n-b1134-retained/…/retained`) posle instalacije čuva privatne podatke (`.env`, `storage`). Ne briše se.
