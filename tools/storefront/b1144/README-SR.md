# B1144 — kontrolisana OFF instalacija CMS-a (lean)

Autor izrade: Ald1n (https://ald1n.com)

B1144 instalira main runtime (izvor `ef3f927`, CMS stablo `da8ac0c9`) u `apps/cms/current`. Javna prodavnica i checkout ostaju isključeni kroz OFF clamp u `config/public_storefront.php`. Ne pokrećemo migracije, ne menjamo `.env` i ne diramo bazu.

B1143 je stao 08.11.2026 na proveri docroot-a preko cPanel `uapi`, koja sa SSH naloga ne radi (Report1104, `ROLLED_BACK_PRE_SWITCH`, kod i vendor netaknuti). B1144 kreće tačno od tog stanja i prihvata ga samo ako se Report1104 bajt po bajt poklapa (sha256 `3b469c0d…853330`).

## Raspakivanje

ZIP sadrži samo `incoming/b1144-*` fajlove. Pre raspakivanja proverite da u `incoming/` nema starih B1144 fajlova:

```bash
ls /home/icaffeco/ald1n-project/incoming/b1144-* 2>/dev/null
```

Ova komanda ne sme ništa da ispiše. Ako ispiše bilo koji fajl, stanite i pošaljite nam spisak. Tek onda raspakujte, bez prepisivanja:

```bash
unzip -n b1144-operator.zip -d /home/icaffeco/ald1n-project
```

`-n` znači da `unzip` nikad ne prepisuje postojeći fajl. Fajlovi `b1134-release.php`, `b1134-files.php` i `b1134-scheduler.php` već postoje na hostu iz ranijih paketa; B1144 ih ne isporučuje ponovo, samo im proverava sha256.

## Komanda

```bash
bash /home/icaffeco/ald1n-project/incoming/b1144-controlled-install.sh
```

Pokreće se sa SSH naloga `icaffeco`, bez opcija. Skripta rezerviše izveštaj u `docs/operations/` sa sledećim slobodnim brojem i odmah ispiše njegovu putanju (`REPORT=…`).

Sam posao radi odvojeni proces (worker) u sopstvenoj sesiji, nezavisno od SSH veze. Worker sve upisuje direktno u izveštaj. Terminal samo prati izveštaj i na kraju ispiše `EXIT=`. Ako SSH veza pukne ili pritisnete Ctrl-C, worker radi dalje. Praćenje nastavljate ovako:

```bash
tail -f /home/icaffeco/ald1n-project/docs/operations/<broj>-B1144-CONTROLLED-OFF-INSTALL-<vreme>.md
```

Posao je gotov kada se u izveštaju pojavi red `EXIT=`. Komandu ne pokrećite ponovo dok taj red ne postoji.

## Šta B1144 radi, redom

1. Proverava integritet paketa, predhodnika (B1134 → B1143) i runtime manifest od 8531 fajla. Zatim upisuje sopstveno privatno stanje u `~/.ald1n-b1144-state/` (arhivirane bajtove predhodnika, journal i stanje). U stablima aplikacije se ovde ništa ne menja.
2. Proverava direktorijume samo na fajl-sistemu: stvarne putanje (ne symlink), vlasnik je `icaffeco`, nema upisa za grupu ili sve, nema `public/.user.ini`.
3. Dijagnostika: broji fajlove koji se razlikuju po sadržaju, a imaju isti mtime (±1 s). Ovo je samo informacija za izveštaj.
4. Provera hosta pre barijera, na starom živom stablu (`HOST_PREFLIGHT`). Probni fajl iz koraka 10 se postavlja u stari `public/`, bez invalidacije OPcache-a. Proveravamo SAPI, PHP 8.4, docroot, OPcache, `realpath_cache_ttl` i da li bi čekanje iz koraka 12 bilo duže od 600 s. Dok ide `GET /login`, gledamo `ps` i moramo bar jednom da vidimo proces `lsphp:` sa poslednja 44 znaka putanje ovog CMS-a (kontrola da drain zaista vidi naše procese). U `storage/logs/` mora postojati bar jedan `laravel-GGGG-MM-DD.log` (`LOG_CHANNEL=daily`); bez njega bi provera logova posle zamene prošla naprazno. Izveštaj ispisuje svaku proveru i svaku vrednost, i kada neke padnu. Ako bilo šta padne: `HOST_PREFLIGHT_BLOCKED`, pa `ROLLED_BACK_PRE_SWITCH`. Sajt nijednog trenutka nije bio nedostupan, jer barijer još ne postoji. Probni fajl se briše u svakom slučaju.
5. Zapisuje nameru za barijer (`NEW_MAINTENANCE_INTENT`) pre nego što napravi ijedan barijer fajl, pa prekid u bilo kom trenutku ne ostavlja nepoznat barijer.
6. Postavlja barijer na kandidat stablo (`CANDIDATE_READY`), pauzira scheduler, postavlja barijer na živo stablo, proverava da `/login` vraća 503 sa našim pečatom i tek onda proverava stanje schedulera.
7. Uski drain: čeka da nestanu `lsphp` procesi ovog CMS-a i `artisan` procesi ovog CMS-a (argument ili radni direktorijum u CMS release stablima), najviše 90 s. `artisan` procesi drugih sajtova se ne čekaju. Ako ne nestanu, vraćamo sve kako je bilo, pre bilo kakve zamene.
8. Lokalno, PHP CLI-jem, proverava sha256 svih 8531 fajlova kandidata. Beleži veličine Laravel logova.
9. Zamena direktorijuma (dva rename-a, sa journal-om), zatim `package:discover` i `view:cache`.
10. Provera svežine koda na webu: privremeni PHP fajl sa nasumičnim imenom u novom `public/`, dostupan samo uz tajni header, samo GET i samo za `cms.ald1n.com`, važi najviše 1800 s. Fajl se prvo upiše u privatni direktorijum stanja, sinhronizuje na disk i tek onda hard-linkom dobije javno ime; prekid usred upisa nikad ne ostavlja polovičan fajl u `public/`. Javlja SAPI, docroot, OPcache podešavanja i PHP `realpath_cache_ttl` / `realpath_cache_size`, poredi sha256 novih fajlova i poziva `opcache_invalidate` samo za skripte iz CMS release stabala. OPcache se nikad ne prazni globalno. Fajl se briše posle provere i na svakoj grešci. Ime fajla čuvamo u privatnom stanju da bi ga ponovno pokretanje posle prekida našlo i obrisalo; ime i token se nikad ne ispisuju, a token se ne čuva.
11. Pod barijerom: `php artisan about` i `php artisan migrate:status` (samo čitanje). Moraju biti pending tačno tri migracije: `2026_10_23_020000_create_public_order_accesses`, `2026_10_23_020100_create_public_checkout_attempts`, `2026_10_24_030000_add_retail_customer_role`.
12. Barijer ostaje do trenutka `max(zamena + realpath_cache_ttl, provera + opcache.revalidate_freq) + 5` sekundi. `realpath_cache_ttl` se računa od zamene, `revalidate_freq` od trenutka provere iz koraka 10, i obe vrednosti javlja probni fajl. Čekanje se meri monotonim satom, pa ga pomeranje sistemskog vremena ne skraćuje. Svakih 15 s izveštaj dobija red `BARRIER_HOLD_HEARTBEAT`. Na kraju su linije `BARRIER_HOLD_START` i `BARRIER_HOLD`.
13. Skida barijer i proverava: `/login` 200, `/up` 200, `/katalog` 404, `/registracija` 404, `/korpa` 404. U Laravel logovima ne sme biti novih ERROR/CRITICAL linija od zamene.
14. Tek tada je instalacija potvrđena (`COMMITTED_OFF`), scheduler se pušta i stanje se zatvara (`CLOSED_INSTALL`).

## Koliko dugo sajt vraća 503

Uz podrazumevani `realpath_cache_ttl=120` čekanje iz koraka 12 je oko 125 s od zamene. Pre zamene barijer stoji još 10–30 s (drain i sha256 provera). Pri uspešnoj instalaciji `/login` vraća 503 ukupno oko 135–160 s.

Ako se vraća stari kod posle zamene, dolazi još jedno čekanje od oko 125 s od vraćanja, pa je ukupno oko 260–300 s. Kada je provera vraćenog koda morala na rezervni put (vidi niže), čekanje se računa od trenutka odluke, pa traje nekoliko sekundi duže.

## Vraćanje starog koda

Ako bilo koji korak posle zamene padne, B1144 sam vraća stari kod. Redom: zapisuje nameru, ponovo postavlja barijer na oba stabla, javlja stanje schedulera (samo izveštaj), čeka drain najviše 90 s i vraća direktorijume. Ako zapis stanja, barijer korak, scheduler ili drain padnu, to se upiše kao `ROLLBACK_PREFIX_STEP` i vraćanje direktorijuma ide dalje; ono zavisi samo od journal-a.

Zatim proverava svežinu vraćenog koda na webu i drži barijer kao u koraku 12, računato od vraćanja. Skida barijer, proverava `/login` 200 i pušta scheduler. To važi i kada padne provera posle skidanja barijera.

Ako provera vraćenog koda padne, ponavlja se jednom. Ako padne i drugi put, koristimo rezervni put: čekanje iz provere hosta pre barijera (korak 4), računato od tog trenutka. Posle čekanja proveravamo da je aktivno baš staro stablo, da stari fajlovi imaju očekivani sha256 i da privatni `/login` iza našeg barijera (tajni header) vraća 200. Tek tada skidamo barijer.

Ako i ta provera padne, barijer namerno ostaje i faza je `RESTORED_BARRIER_RETAINED_OPERATOR_LIFT`. Tada je aktivan stari kod, ali svaki javni zahtev dobija 503 sa našim pečatom; scheduler je i dalje pauziran. Izveštaj sadrži red `OPERATOR_LIFT_COMMAND` sa tačnom komandom:

```bash
rm -f /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/down /home/icaffeco/ald1n-project/apps/cms/current/storage/framework/maintenance.php && bash /home/icaffeco/ald1n-project/incoming/b1144-controlled-install.sh
```

Prvo pošaljite izveštaj. Kada se dogovorimo, komanda skida barijer i ponovo pokreće B1144. Novo pokretanje vidi da je barijer skinut, ne postavlja ga ponovo, proverava javni `/login` 200 i pušta scheduler. Ponovno pokretanje bez `rm` samo ponavlja proveru i ostavlja barijer.

## Kako čitati rezultat

- `B1144_FINAL=PASS_INSTALLED_OFF` i `EXIT=0`: instalirano, prodavnica i checkout su OFF.
- `HOST_PREFLIGHT_BLOCKED`, pa `RELEASE_PHASE="ROLLED_BACK_PRE_SWITCH"`: host ne ispunjava uslove; red `HOST_PREFLIGHT` pokazuje šta i sa kojim vrednostima. Sajt nije bio nedostupan.
- `RELEASE_PHASE="ROLLED_BACK_PRE_SWITCH"`: stali smo pre zamene, stari kod i vendor nisu dirani.
- `RELEASE_PHASE="ROLLED_BACK"`: zamena je urađena pa automatski vraćena, stari kod radi i proveren je.
- `ORIGINAL_RELEASED_SCHEDULER_PENDING` (uz `SCHEDULER_RELEASE_PENDING_ORIGINAL_SITE_LIVE_NO_BARRIER`): stari kod radi, barijera nema, samo puštanje schedulera nije uspelo. Ista komanda ponavlja samo puštanje schedulera.
- `RESTORED_BARRIER_RETAINED_OPERATOR_LIFT`: vidi „Vraćanje starog koda“.
- `HOLD_RECOVERY_OWNED_MAINTENANCE_RETAINED`: povratak nije završen i barijer namerno ostaje. Ponovno pokretanje iste komande nastavlja oporavak.
- `RECOVERY_STATE_UNWRITABLE`: privatno stanje nije moglo da se upiše (disk ili dozvole). Stari kod je vraćen iza barijera ako je journal to dozvolio. Popravite disk ili dozvole i pokrenite istu komandu.
- `HOLD_INSTALLED_OFF_FINISH_PENDING`: novi kod je potvrđen, ostao je samo završni korak (npr. puštanje schedulera). Ponovno pokretanje ga završava, bez vraćanja koda.
- `FRESHNESS_REALPATH_CACHE_UNREADABLE`: probni fajl nije mogao da pročita `realpath_cache_ttl` ili `realpath_cache_size`. Pre barijera to je `HOST_PREFLIGHT_BLOCKED`. Posle instalacije to znači automatsko vraćanje. Pri proveri vraćenog koda ide rezervni put.
- `BARRIER_HOLD_EXCEEDS_BOUND`: potrebno čekanje je duže od 600 s. Pre barijera to je `HOST_PREFLIGHT_BLOCKED`. Posle instalacije (uz liniju `BARRIER_HOLD_BOUND`) sledi automatsko vraćanje. Pri proveri vraćenog koda ide rezervni put.
- `DRAIN_POSITIVE_CONTROL_NOT_OBSERVED`: tokom `GET /login` nismo videli `lsphp` proces ovog CMS-a. Drain ne bi bio pouzdan, pa stajemo pre barijera. Red `HOST_PREFLIGHT` sadrži `lsphp` nazive koje smo videli.
- `LOG_GATE_NO_DAILY_LARAVEL_LOG`: nema `laravel-GGGG-MM-DD.log`; stajemo pre barijera.
- `LEFTOVER_FRESHNESS_PROBE_REQUIRES_OPERATOR_INSPECTION`: u `public/` postoji `ald1n-b1144-*.php` koji ovo pokretanje nije napravilo. Ne brišemo ga sami; pošaljite izveštaj.
- `WORKER_ENDED_WITHOUT_EXIT_LINE` ili `WORKER_DID_NOT_START`: worker je nestao bez završnog reda. Pošaljite izveštaj pre bilo kakvog novog pokretanja.

Posle `ROLLED_BACK` ili `ROLLED_BACK_PRE_SWITCH` ista komanda ne instalira ponovo (`PRIOR_ATTEMPT_ROLLED_BACK_NO_AUTOMATIC_REDEPLOY`). Za novi pokušaj pravimo novi paket.

## Zašto čekamo

Svaki PHP proces pamti razrešene putanje (realpath keš) do `realpath_cache_ttl` sekundi. Posle zamene `.env` i `storage/*` u novom stablu su symlink-ovi ka retained stablu. Proces koji još pamti staru putanju čita pogrešan ili nepostojeći `.env`. U CI-ju je baš to dalo `/login` 500 („No application encryption key has been specified“) odmah posle vraćanja, dok keš nije istekao. Brisanje keša unutar probnog fajla važi samo za taj jedan proces, zato se oslanjamo na vreme.

## Šta je uklonjeno u odnosu na B1143

cPanel `uapi`/`DomainInfo`, `.htaccess` overlay `php_flag opcache.enable Off`, A/B witness, LSAPI kalibracija 44/45 i held-execution gate, grupna provera procesa (HostProcessGuard), web petlja `verify-files` i docroot screen.

## Šta ne možemo da dokažemo bez root pristupa

Da li isti `public/` direktorijum koristi još neki hostname. Provera iz koraka 2 je samo na fajl-sistemu. Ostala ograničenja su u izveštaju iz CI-ja.

Retained stablo (`~/.ald1n-b1134-retained/…/retained`) posle instalacije čuva privatne podatke (`.env`, `storage`). Ne briše se.
