## 2.2.0 - Mobile API Foundation

### Production Readiness Hotfix 1

- ispravljena je lažna CRITICAL disk health procena na velikim/shared filesystemima: apsolutno slobodan prostor sada ima prednost nad samim procentom;
- primer sa 750,52 GB slobodnog prostora pri 9,6% više nije blokirajući;
- queue doctor sada prikazuje tačne remediation korake za `QUEUE_CONNECTION=database`;
- backup verifier jasno razlikuje rollback backup prethodne verzije od obaveznog svežeg post-upgrade backupa;
- deployment dokumentacija više ne zahteva `php artisan test` nakon `composer install --no-dev`, jer tada `require-dev` test komande nisu instalirane;
- dodat je dependency-free `bin/v2.2.0-production-readiness-hotfix-smoke.php` i PHPUnit regresija disk politike.
- dodata je bezbedna automatizovana skripta `fix-v2.2.0-production-readiness.sh` koja čuva `.env`, aktivira database queue, pravi svež backup i ponavlja Stable strict proveru.

- Laravel ostaje centralni backend, dok se mobilni smer standardizuje na zaseban React Native / Expo klijent;
- dodat je `/api/v1/bootstrap` sa profilom, efektivnim token dozvolama, feature flagovima, brojem nepročitanih obaveštenja i Android/iOS version policy podacima;
- dodati su API tokovi za profil, promenu lozinke, notification preference, katalog filtere, opcije porudžbine i poslovna obaveštenja;
- dodata je bezbedna registracija Android/iOS instalacija, šifrovano čuvanje push tokena, deduplikacija tokena i opoziv uređaja;
- promena lozinke opoziva sve Sanctum tokene i sve registrovane mobilne instalacije;
- API greške koriste stabilan envelope sa `message`, `code`, `errors` i `request_id` poljima;
- dodat je OpenAPI 3.1 ugovor za mobilni klijent;
- dodate su database queue tabele i produkcioni queue worker ugovor;
- dodate su migracije `000039`, `000040` i `000041`, bez izmene postojećih poslovnih podataka;
- dodati su Mobile API feature/contract testovi, `app:cms-v2-2-0-doctor` i dependency-free smoke provera;
- produkciona push isporuka namerno ostaje isključena dok se ne uvede zaseban queued Expo/FCM/APNs dispatcher.

## 2.1.6 - Performance & Data Quality

- dodat je Data Quality Center za pregled integriteta artikala, slika, varijanti, kategorija, specifikacija i korisničkih uloga;
- dodat je `app:data-quality-doctor` sa bezbednim `--repair` režimom i JSON izveštajem;
- dodat je `app:performance-doctor` za ciljane indekse, request cache i reprezentativna SQL merenja;
- katalog dobija administratorske filtere za artikle bez slike, cene, modela, vlasnika i nepotpune artikle;
- šifarnici kataloga koriste kratkotrajni cache sa automatskim invalidiranjem nakon izmene;
- role i permission provere se keširaju tokom jednog requesta radi uklanjanja ponovljenih SQL upita;
- dodata je istorija Data Quality provera i ciljani indeksi baze;
- kompletna bezbedna popravka ne briše artikle, slike niti poslovnu istoriju.

# Changelog

## 2.1.5 - UX, Mobile & Runtime Stability

- dodat je globalni UX runtime za zaštitu od duplog slanja formulara, loading stanje i `aria-busy` signal;
- dugi formulari proizvoda i porudžbine dobijaju mobilni sticky action dock sa brzim čuvanjem i povratkom;
- formulari označeni za sticky akcije upozoravaju pre napuštanja stranice kada postoje nesačuvane izmene;
- `Ctrl+S` i `Cmd+S` pokreću primarno čuvanje aktivnog formulara;
- validaciona polja dobijaju `aria-invalid`, vezanu poruku greške, jasnije focus stanje i automatski fokus prvog neispravnog polja;
- alert poruke imaju odgovarajući `role`, live region i automatski fokus na prvu grešku;
- tabele dobijaju pristupačan horizontalni scroll region, stabilniji mobilni prikaz i sticky prvu kolonu;
- formulari, kartice, akcije i razmaci su dodatno prilagođeni telefonima i tabletima;
- dodate su prilagođene 403, 404, 419, 429, 500 i 503 stranice sa jasnim povratkom u aplikaciju;
- postojeće specifikaciono polje sa slugom `snaga-napajanja` automatski se povezuje sa tipom `desktop-racunar` i postavlja približno u sredinu njegovih specifikacija;
- migracija `2026_08_05_000037_place_desktop_power_supply_field_v2_1_5.php` ne kreira duplirano polje i bezbedno čuva postojeća pivot podešavanja;
- dodat je `app:cms-v2-1-5-doctor --render --repair`, koji proverava ključne rute, duplikate imena ruta, postojanje svih controller akcija ruta, kompajlira sve Blade prikaze, renderuje sistemske error stranice i proverava raspored snage napajanja;
- dodat je dependency-free `bin/cms-v2.1.5-smoke.php` i PHPUnit contract test;
- nema ručnog brisanja release fajlova.

## 2.1.4.1 - Product Type Page Render Hotfix

- ispravljen je Error 500 na svim stranicama `/admin/catalog-settings/product-type/{slug}`;
- tačan uzrok je bio `Undefined variable $orderedFields` u `resources/views/admin/dictionary/fields.blade.php`;
- sortirana kolekcija specifikacionih polja sada se priprema u `CatalogDictionaryController` i eksplicitno prosleđuje prikazu;
- Blade partial dodatno inicijalizuje `$fields` i `$orderedFields`, pa ostaje bezbedan i pri parcijalnom ili CLI renderovanju;
- v2.1.4 doctor sada proverava obe product-type rute i renderuje formular specifikacija za svaki postojeći tip proizvoda;
- dodat je dependency-free hotfix smoke i PHPUnit contract test;
- nema nove migracije baze niti ručnog brisanja fajlova.

## 2.1.4 - Modern Button System, Product Model & Lifecycle

- uveden je jedinstveni moderan sistem tastera kroz ceo CMS sa istom visinom, radiusom, paddingom, focus stanjem, hover/active interakcijama i pristupačnim reduced-motion režimom;
- sačuvane su semantičke boje prema funkciji: primary plava, success zelena, warning amber, danger crvena i secondary/neutral slate;
- dodato je posebno polje `Model proizvoda` posle brenda i linije proizvoda, sa normalizacijom razmaka i primerom `HP EliteBook 830 G8`;
- model proizvoda uključen je u automatski naziv, live preview, kompletnost, kloniranje, generisanje SKU-a, snapshot, API i pretragu kataloga;
- migracija bezbedno prenosi postojeće modele samo iz tačno prepoznatih starih polja i dopunjava prilagođene šablone tokenom `{model}`;
- dodato je kontrolisano trajno brisanje artikla uz potvrdu tačnim SKU-om i izbor uklanjanja svih lokalnih slika proizvoda i varijanti;
- artikal sa porudžbinama, promenama lagera, ulazima robe ili popisima ne može se trajno obrisati i mora se arhivirati;
- legacy read-only slike se ne brišu fizički, a problem brisanja storage direktorijuma beleži se u audit log bez vraćanja uspešne DB transakcije;
- dodati su `app:cms-v2-1-4-doctor --repair`, `bin/cms-v2.1.4-smoke.php` i PHPUnit contract regresija;
- dodata je migracija `2026_08_04_000036_add_product_model_and_name_templates_v2_1_4.php`;
- nema ručnog brisanja release fajlova.

## 2.1.3.3 - Storage Capacity Total & Data Migration Hotfix

- uklonjeno je duplo ručno značenje polja kapaciteta diska: lista diskova sa pojedinačnim kapacitetima je iznad, a staro polje ispod prikazuje samo automatski ukupni zbir;
- dodat je `StorageSpecificationService` kao jedina backend tačka za normalizaciju, prikaz i zbir do osam diskova na proizvodima i varijantama;
- migracija povezuje izvorno polje diskova sa izvedenim ukupnim poljem i normalizuje postojeće `value_text`, `value_json` i `value_number` podatke;
- stari kapacitet prenosi se na prvi disk samo kada pojedinačni kapaciteti nisu postojali, dok postojeći pojedinačni kapaciteti imaju prednost;
- istorijski ukupni kapacitet bez sačuvanog tipa diska čuva se bez gubitka dok korisnik ne dopuni detalje;
- ukupni kapacitet je read-only u UI-u, ponovo se računa na backendu i zaštićen je od bulk izmene;
- forma i JavaScript čuvaju početni legacy zbir pri prvom otvaranju, a nakon korisničke izmene računaju novi zbir u realnom vremenu;
- lifecycle repair čisti zastarele storage veze, a catalog settings doctor proverava proizvode i varijante;
- izvedeni zbir ne duplira kompletnost, automatski naziv, naziv varijante ni generisani SKU;
- dodat je dependency-free `bin/storage-capacity-total-smoke.php` i PHPUnit contract regresija;
- dodata je migracija `2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php`;
- nema ručnog brisanja fajlova.

## 2.1.3.2 - Product Save Regex Hotfix

- ispravljen je Error 500 pri čuvanju proizvoda i varijanti;
- uzrok je bio pogrešno escape-ovan `/` unutar Laravel SKU `regex` pravila, zbog čega je `preg_match()` prijavljivao `Unknown modifier '-'`;
- SKU validacija sada koristi `#` delimiter i bezbedno prihvata velika slova, brojeve, tačku, donju crtu, kosu crtu i crticu;
- dodat je dependency-free `bin/product-save-regex-hotfix-smoke.php` i PHPUnit contract regresija;
- nema nove migracije baze niti ručnog brisanja fajlova.

## 2.1.3.1 - Catalog Category & Specification Integrity Hotfix

- automatsko povezivanje svih aktivnih tipova sa postojećom ili internom sistemskom kategorijom;
- čišćenje zastarelih specifikacionih vrednosti i veza nakon trajnog brisanja polja;
- zaštita product i variant update tokova od obrisanih ID vrednosti.

## 2.1.3 - Catalog Settings & Product Data Maintenance

- svaki tip proizvoda dobija posebnu slug stranicu za podesavanje specifikacija, automatske kategorije, kompletnosti i sablona naziva;
- glavne sifarnicke stranice koriste pregledne sažete kartice umesto prikaza svih opcija svakog tipa na jednoj stranici;
- uveden je rezim `Uredi raspored` sa Drag & Drop podrskom preko pointer dogadjaja na telefonu i racunaru i dugmetom `Zavrsi uredjivanje` na dnu stranice;
- specifikaciona polja mogu da se deaktiviraju ili trajno obrisu uz potvrdu tacnim nazivom, usage pregled i warning audit zapis;
- tip proizvoda sada trajno poseduje jednu automatsku kategoriju, dok su rucni izbor kategorija uklonjeni sa forme artikla, bulk izmene i kloniranja;
- migracija povezuje tip i kategoriju po nazivu/slug-u ili po jedinoj postojecoj kategoriji artikala tog tipa, a zatim uskladjuje `product_categories`;
- dodat je `app:catalog-settings-doctor {--repair}` i Stable release profil ga pokrece pre ostalih kataloskih runtime provera;
- vise diskova se cuva strukturisano u `value_json`, uz kompatibilan tekstualni prikaz i zaseban celobrojni kapacitet svakog diska u GB;
- sva numericka GB polja u artiklima, varijantama i bulk izmeni odbijaju decimalne vrednosti;
- kartice, thumbnail prikazi i varijante koriste `object-fit: contain` kako fotografije ne bi bile cropovane;
- e-mail o novoobjavljenom artiklu prikazuje glavnu sliku, cist opis, cenu, SKU i dugme ka katalogu;
- dodati su `bin/catalog-settings-product-data-smoke.php` i PHPUnit contract regresije;
- dodata je migracija `2026_08_04_000033_create_catalog_type_layout_v2_1_3.php`.

## 2.1.2 - Product Media & Notification Maintenance

- rotacija slika sada podržava Imagick i GD fallback, JPEG/PNG/WebP formate, kontrolisanu privremenu kopiju i bezbedan legacy copy-on-write;
- URL fotografije dobija cache-busting verziju iz SHA-256 hasha, pa se rotirana slika odmah prikazuje umesto stare browser/CDN kopije;
- glavna slika bira se klikom na zvezdicu direktno preko fotografije i automatski ostaje prva u galeriji;
- redosled ostalih slika menja se Drag & Drop postupkom na touch i desktop uređajima i odmah se čuva AJAX zahtevom;
- dodavanje artikla i galerija prikazuju preview, naziv, veličinu i broj izabranih slika, kao i upload progress u procentima;
- slike se mogu preuzeti u punoj rezoluciji iz kartice kataloga, glavnog prikaza i lightbox Zoom prikaza kroz autorizovanu download rutu;
- forma artikla sada počinje redosledom Naziv artikla, Dodavanje slika, Specifikacije;
- disk specifikacija podržava do osam unosa i kombinacije poput SSD + HDD ili SSD + SSD + HDD bez nove tabele i migracije;
- dodati su `app:product-media-doctor`, `bin/product-media-ux-smoke.php` i PHPUnit contract regresije;
- glavna stranica e-mail podešavanja sada ima podrazumevano isključeno, opciono automatsko obaveštavanje svih aktivnih registrovanih korisnika o novoobjavljenom artiklu;
- obaveštenje se kreira samo pri prvom prelasku artikla u aktivan status, koristi postojeći outbox, deduplikaciju, retry i podesiv interval, bez slanja nacrta i bez duplih poruka;
- generički e-mail šablon i pregled outbox-a razlikuju porudžbinu od novog artikla i nude direktno dugme „Pogledaj artikal“;
- dodati su `bin/product-announcement-smoke.php` i PHPUnit contract regresija za e-mail najave artikala;
- nema nove migracije baze.

## 2.1.1 - Stable Maintenance

- ispravljen je HTTP 500 na `/order/new`: složeno formiranje JSON mape varijanti više se ne izvršava unutar Blade `@json` direktive, već se bezbedno priprema u `OrderController`;
- dodat je `app:order-create-doctor --render`, a Stable release profil sada renderuje kompletnu stranicu za kreiranje porudžbine;
- `/catalog` je postao jedinstvena stranica za pregled i upravljanje artiklima, dok `/admin/catalog` kompatibilno preusmerava na objedinjeni katalog;
- obični korisnici pregledaju aktivan katalog u okviru dozvoljenih kategorija, Administratori u katalogu vide i uređuju isključivo artikle čiji `created_by` odgovara njihovom korisničkom ID-u, a SuperAdministrator vidi i upravlja svim artiklima;
- isto ownership pravilo primenjeno je na izmenu, arhiviranje, vraćanje, kloniranje, varijante, slike i bulk operacije;
- dodat je `app:catalog-ownership-doctor --render` za proveru scope-a, rendera jedinstvenog kataloga i legacy redirecta;
- svi GET filter paneli dobijaju dugme „Prikaži/Sakrij filtere“, pamćenje stanja u browseru i automatsko otvaranje kada su filteri aktivni;
- kartice žiro računa su kompaktnije i na desktopu se prikazuju po dve u redu;
- Dashboard product/user KPI upiti objedinjeni su u agregatne SQL upite, sekcije zadržavaju bezbedne fallback vrednosti kada relacija ili tabela nije dostupna, a svi linkovi kataloga vode na jedinstveni `/catalog`;
- mobilni prikaz jedinstvenog kataloga, filtera, akcija i bankovnih kartica je dodatno prilagođen manjim ekranima;
- tekst provere backupa više ne pominje RC nakon Stable izdanja;
- dodati su Stable Maintenance smoke i Feature regresije za vlasništvo artikala i redirect starog admin kataloga;
- nema nove migracije baze.

## 2.1.0 - Stable

- potvrđeni `v2.1.0-rc1` hardening profil promovisan je u Stable i završava sa `RELEASE CHECK: STABLE READY`;
- početna ruta `/` postaje jedinstveni moderni univerzalni dashboard za zaposlene i kupce;
- poslovni KPI, prioriteti, operativni moduli, porudžbine, dokumenti, uplate, garancije, reklamacije, servisni termini, poruke i podešavanja naloga objedinjeni su na istoj početnoj strani prema permission scope-u prijavljenog korisnika;
- zasebna korisnička stranica `Moj portal` je uklonjena iz navigacije i aktivnog render toka;
- stari URL `/portal` trajno preusmerava na početnu stranu radi kompatibilnosti sa postojećim bookmark-ovima;
- uklonjeni su zastareli `CustomerPortalController` i `resources/views/portal/index.blade.php`, dok komunikacija ostaje dostupna kroz autorizovane message rute;
- `app:customer-portal-doctor --render` sada proverava integrisani korisnički centar unutar univerzalnog dashboarda;
- `app:auth-doctor --render-dashboard` sada zaista renderuje aktuelni dashboard kroz Laravel container i radi i bez eksplicitno prosleđenog korisničkog imena;
- dodat je `DELETE-FILES.txt` za bezbedno uklanjanje obsolete fajlova pri UPGRADE instalaciji;
- dodat je `stable` release profil, Stable smoke i PHPUnit contract/Feature regresije;
- nema nove migracije baze.

## 2.1.0-rc1 - Release Candidate & Final Hardening

- funkcionalni scope je zamrznut; RC1 ne uvodi novi poslovni modul niti novu migration datoteku;
- dodat je `rc` profil u `app:release-check`, namenjen zavrsnoj proveri bez automatskih repair promena;
- uveden je `app:release-integrity` za SHA-256 proveru kompletnog release paketa;
- uveden je `app:security-hardening-doctor` za APP_ENV/APP_DEBUG/HTTPS/session/.env/public security audit;
- uveden je `app:migrations-doctor --strict` za pending/orphan migracije, SQL mode, charset i foreign key proveru;
- uveden je `app:access-control-doctor` za role, permission, route middleware, active superadmin i orphan pristupne zapise;
- uveden je `app:backup-verify` za read-only proveru backup manifesta, SQL gzip-a i privatnih fajlova;
- dodati su RC operativni plan, backup/restore drill, acceptance komande, smoke i PHPUnit contract testovi;
- `app:release-check --profile=rc --render --strict --snapshot` zavrsava porukom `RELEASE CHECK: RC READY` samo bez FAIL i WARN rezultata.

## 2.1.0-beta7.24.1 — Management profitability cost snapshot hotfix

- ispravljen je `app:management-reports-doctor --repair`: pored migracija i seedera sada stvarno ponovo obrađuje istorijske stavke označene kao `missing`;
- uveden je `OrderItemCostSnapshotService` koji menja isključivo nepotpune nabavne snapshotove i nikada ne prepisuje kompletne istorijske vrednosti;
- automatski repair koristi postojeći delimični snapshot, cenu varijante, istorijski knjiženi prijem robe ili aktuelnu cenu proizvoda, uz trajno označen izvor procene;
- dodata je komanda `app:order-cost-snapshots` za audit, automatsku dopunu i kontrolisanu ručnu dopunu sa obaveznim obrazloženjem;
- automatske i ručne finansijske promene upisuju se u postojeći `audit_logs`;
- management doctor sada prikazuje tačan ID stavke, porudžbinu, SKU i kandidata kada upozorenje ostane;
- dodati su dependency-free smoke test i PHPUnit contract regresije;
- nema nove migracije baze.

## 2.1.0-beta7.24 — Customer Portal 2.0

- uveden je bezbedan aktivacioni tok za kupce sa jednokratnim SHA-256 tokenom, rokom važenja od 72 sata, Cloudflare Turnstile proverom i samostalnim postavljanjem lozinke;
- Customer Portal dobija centralnu administraciju kupaca, ponovno slanje poziva i pregled statusa aktivacije;
- omogućeno je kontrolisano povezivanje ili prenos postojeće porudžbine na potvrđen nalog kupca, uz eksplicitnu potvrdu, razlog promene i trajnu audit istoriju;
- uveden je centar za komunikaciju kupca i podrške sa javnim porukama, internim beleškama, statusima, prioritetima, zaduženom osobom i povezivanjem sa porudžbinom;
- interna beleška se filtrira na nivou relacije i nikada se ne prikazuje u korisničkom portalu;
- korisnik može da ažurira ime, prezime, telefon i adresne podatke, dok promena e-mail adrese ostaje administratorska operacija;
- uveden je nezavisan registry aktivnih prijava koji radi i sa file session driverom, prikazuje uređaj/IP/poslednju aktivnost i omogućava opozivanje pojedinačnih ili svih drugih sesija;
- zastarele evidencije prijava se automatski zatvaraju, remember-me prijava se prepoznaje pri ponovnom uspostavljanju sesije, a promena lozinke pravilno rotira i ponovo registruje trenutnu sesiju;
- promena lozinke opoziva ostale prijave, API tokene i trajne remember-me tokene;
- ponovno slanje aktivacionog linka više ne deaktivira kupca koji već ima aktivan nalog, dok postojeći aktivni kupci dobijaju nedestruktivan activation backfill;
- aktivaciona stranica koristi `no-store` cache politiku, a scheduler svakodnevno izvršava `app:customer-portal-maintenance` radi čišćenja iskorišćenih tokena i starih session evidencija;
- dodati su admin i korisnički UI, responsive stilovi, novi rate limiteri, System/Deployment provere i prošireni `app:customer-portal-doctor`;
- dodati su `bin/customer-portal-2-smoke.php`, PHPUnit contract i Feature testovi;
- paket sadrži novu recovery-safe migraciju `2026_08_01_000032_create_customer_portal_2_beta7_24.php`.

## 2.1.0-beta7.23.2 — Detail page controller dependency hotfix

- ispravljen je `ArgumentCountError` u `app:detail-pages-doctor` pri proveri administratorske izmene artikla;
- `ProductController::edit()` se više ne poziva direktno sa samo jednim argumentom, već kroz Laravel container koji automatski rešava `ProductTemplateService`;
- i galerija artikla prebačena je na isti `app()->call()` obrazac radi otpornosti na buduće controller zavisnosti;
- dodat je `bin/detail-pages-doctor-smoke.php`, PHPUnit contract test i statička regresiona provera;
- dokumentovan je tačan scheduler cron sa `/usr/local/bin/php` za trenutni server;
- nema nove migracije baze.

## 2.1.0-beta7.23.1 — Catalog detail render & runtime health hotfix

- ispravljen je Blade parse error na `resources/views/catalog/show.blade.php` koji je nastajao zbog zgusnutog lanca `@foreach`, `@php`, `@if` i `@can` direktiva u prikazu varijanti;
- blok varijanti i blok specifikacija prebačeni su na eksplicitne višelinijske Blade direktive, pa compiler više ne može da poveže `@endforeach` sa nezatvorenim `@if`;
- dodat je Feature test koji zaista otvara detalj proizvoda sa aktivnom varijantom i proverava SKU, naziv i akciju poručivanja;
- dodat je dependency-free `bin/catalog-detail-smoke.php` i dva PHPUnit contract testa za Blade strukturu i System Health remediation poruke;
- System Health starost heartbeat-a, automatizacije i backupa prikazuje kao ceo broj umesto Carbon decimalnih vrednosti;
- CRITICAL/WARNING runtime rezultati sada ispisuju konkretne komande za heartbeat, hosting cron, automatizaciju i ručni backup;
- bezbednosna semantika nije oslabljena: release check i dalje neće lažno označiti stale scheduler ili backup kao zdrav;
- nema nove migracije baze.

## 2.1.0-beta7.23 — Production Stabilization & QA

- uvedena je centralna Artisan komanda `app:release-check` koja objedinjuje postojeće deployment, health i module doctor provere;
- dodati su `quick`, `standard` i `full` profili sa determinističkim redosledom i zaštitom od dupliranih ili neregistrovanih provera;
- podrazumevani režim je dijagnostički i ne šalje e-mailove, ne izvršava outbox dispatch, ne pokreće automatizaciju, ne pravi backup i ne radi warranty backfill;
- opcija `--repair` eksplicitno pokreće deployment repair, Smart Products normalizaciju, Product Variants agregate i Management Reports finansijske snapshot popravke;
- opcija `--render` uključuje Dashboard, Blade, PDF i detail-page regresione provere;
- opcija `--snapshot` čuva System Health snapshot, a `--strict` uključuje least-privilege legacy grant kontrolu i pretvara WARN u FAIL;
- opcija `--list` prikazuje tačan plan bez izvršavanja ili promene sistema;
- release preflight proverava usklađenost `config/app.php`, `VERSION`, `RELEASE-TAG`, `CHANGELOG.md`, `composer.json`, `composer.lock` i Artisan bootstrap-a;
- svaki korak čuva komandu, parametre, exit kod, trajanje, warning/skip brojače i sanitizovan izlaz;
- uvedeni su atomski JSON izveštaji u `storage/app/release-check`, uključujući stabilan `latest.json`;
- dodati su dependency-free `bin/release-check-smoke.php`, PHPUnit contract test i Feature test `--list` režima;
- dodate su Composer skripte `release:check`, `release:check:full` i `smoke:release`;
- nema nove migracije baze.

## 2.1.0-beta7.22.1 — Customer Portal render & theme CSS hotfix

- ispravljen je `app:customer-portal-doctor --render`, koji je direktno renderovao authenticated Blade layout bez Laravel `ShareErrorsFromSession` middleware-a i zato nije imao `$errors` promenljivu;
- doctor sada eksplicitno prosleđuje prazan `ViewErrorBag`, dok layout dodatno bezbedno proverava da li je error bag dostupan;
- ispravljen je prikaz KPI kartica na `/admin/reports` u tamnoj temi: uklonjen je fallback na belu pozadinu iz nepostojeće `--panel-bg` promenljive;
- management analytics koristi kanonske promenljive `--panel`, `--panel-2`, `--line`, `--text`, `--muted`, `--primary`, `--green`, `--red` i `--amber`;
- uvedeni su kompatibilni aliasi za ranije korišćene CSS promenljive (`--border`, `--surface-2`, `--surface-soft`, `--panel-soft`, `--accent`, `--panel-bg`, `--border-color`), čime su stabilizovani i Payments, Field Operations, Service Parts i drugi spojeni moduli;
- pregledani su inline stilovi varijanti proizvoda i javnog kataloga i prebačeni na aktivnu temu;
- dodat je dependency-free `bin/theme-css-smoke.php` koji prijavljuje svaku CSS promenljivu koja se koristi, a nije definisana;
- nema nove migracije baze.
## 2.1.0-beta7.22 — Customer Portal, Modern Dashboard & Report SQL Hotfix

- uveden je jedinstveni Customer Portal sa porudžbinama, statusima, trackingom, dokumentima, uplatama, planom otplate, garancijama, reklamacijama, servisnim terminima i javnim porukama;
- portal prikazuje objedinjenu vremensku liniju poslovnih događaja i sve podatke ograničava na porudžbine prijavljenog korisnika;
- dodate su preference za dokumente, postprodaju, garancije, servis i naplatu, uz očuvanje obaveznih poslovnih e-mailova iz order outbox modula;
- Dashboard je redizajniran u moderan operativni centar sa KPI karticama, trendom prihoda i bruto dobiti, prioritetnim zadacima, poslednjim porudžbinama, brzim akcijama i modulskim karticama;
- Dashboard zadržava postojeće permission scope-ove i bezbedno prikazuje fallback metrike kada management reports migracija nije dostupna;
- ispravljena je MySQL/MariaDB `ONLY_FULL_GROUP_BY` greška kod segmentnog PDF izveštaja;
- profitabilnost po brendu, liniji, tipu, proizvodu i administratoru sada se grupiše po jednostavnom aliasu u spoljnom upitu;
- istim pristupom zaštićen je dnevni i mesečni trend od strogog SQL režima;
- Customer Portal je schema-aware i prikazuje kontrolisano upozorenje umesto Error 500 ako opcioni modul ili tabela nisu dostupni;
- dodati su recovery-safe migracija, portal doctor, strict-grouping smoke, portal smoke i Feature testovi.

## 2.1.0-beta7.21 — Reports & Profitability

- uveden je centralni upravljački dashboard za prihod, COGS, bruto dobit, provizije, refundacije, servisne troškove i neto doprinos;
- proizvod dobija nabavnu cenu u RSD, a stavka porudžbine nepromenljiv snapshot nabavne cene i izvora troška;
- snapshotuju se brend, linija i tip proizvoda radi stabilnih istorijskih izveštaja;
- istorijske stavke se nedestruktivno dopunjavaju aktuelnom nabavnom cenom samo kada je dostupna i označavaju kao migration procena;
- uveden je indikator pokrivenosti troška i promet bez nabavne cene, pa marža nije prikazana kao lažno potpuna;
- profitabilnost se grupiše po brendu, liniji, tipu, proizvodu ili odgovornom administratoru;
- filteri perioda, statusa, administratora, brenda, linije, tipa i proizvoda važe za KPI, trend i segmente;
- uvedeni su vrednost lagera, aging kapitala, spori lager i artikli bez nabavne cene;
- uvedeni su aging potraživanja, stopa reklamacija, SLA kašnjenja i trošak završenih terenskih intervencija;
- dodat je profesionalni PDF i UTF-8 CSV izvoz;
- dodati su dnevni, nedeljni i mesečni rasporedi sa zasebnim primaocima, PDF/CSV prilozima, deduplikacijom, retry mehanizmom i istorijom slanja;
- ekran bezbedno prikazuje upozorenje pre izvršene migracije umesto Error 500;
- dodata je `reports.manage` dozvola, recovery-safe MariaDB migracija, doctor, dispatch, Feature i dependency-free PDF smoke test.

## 2.1.0-beta7.20 — Product Variants & Configurations

- uveden roditeljski proizvod sa neograničenim brojem varijanti;
- svaka varijanta ima sopstveni SKU, naziv, cenu, valutu, nabavnu cenu, proviziju, lager, prag, status, redosled, slike i opciono garantno pravilo;
- podrazumevana aktivna varijanta određuje prikazanu cenu roditelja, dok roditelj prikazuje zbir lagera aktivnih varijanti;
- korelisane specifikacije rade i u formama varijanti;
- javni katalog prikazuje dostupne konfiguracije i raspon cena;
- porudžbina zahteva konkretnu varijantu, zaključava njen lager i čuva SKU, naziv, cenu i atribute kao istorijski snapshot;
- otkazivanje porudžbine vraća lager tačno na izvornu varijantu;
- račun, predračun, otpremnica, garancija i postprodaja koriste snapshot izabrane konfiguracije;
- postprodajna zamena i povrat menjaju lager konkretne varijante idempotentno;
- direktna korekcija zbirnog lagera roditelja, ulaz robe i popis su blokirani za artikle sa varijantama;
- kloniranje može kopirati varijante, specifikacije i slike, ali uvek daje nove SKU oznake, status Nacrt i lager 0;
- kataloška pretraga i specifikacioni filteri pronalaze podatke iz roditelja ili aktivnih varijanti;
- automatizacija posebno upozorava na nizak lager varijante;
- dodata je recovery-safe MariaDB 10.11 migracija, doctor komanda, smoke i regresioni testovi.

## 2.1.0-beta7.19 — Smart Product Management

- uvedeni pametni šabloni po tipu artikla;
- dodata obavezna osnovna i specifikaciona polja, podrazumevane vrednosti, redosled i težina kompletnosti;
- uveden procenat kompletnosti proizvoda i minimalni prag za aktivan status;
- migracija obračunava početnu kompletnost postojećeg kataloga;
- dodato automatsko formiranje naziva sa alias placeholderima i zaštitom ručnog naziva;
- dodato kloniranje artikla sa novim SKU-om, nultim lagerom i opcionalnim kopiranjem sadržaja;
- dodata bulk izmena sa preview korakom, ograničenjem od 500 artikala i serverskom validacijom;
- promena tipa uklanja specifikacije koje novom tipu ne pripadaju;
- promena brenda čisti nekompatibilnu liniju;
- dodate doctor, smoke, contract i Feature provere;
- migracija je recovery-safe za MariaDB 10.11.

## 2.1.0-beta7.18.1 — Catalog product form Error 500 hotfix

- ispravljen je Error 500 na stranicama **Dodaj artikal** i **Izmeni artikal**;
- uklonjen je poziv nedostupne `$specificationFilters` promenljive iz zajedničke `formView()` metode;
- uklonjen je duplirani `types` ključ u view payload-u;
- zadržan je jedan kompletan skup tipova sa specifikacionim poljima, opcijama i roditeljskim korelacijama;
- pregledane su administratorska lista artikala, korisnički katalog i šifarnik specifikacija;
- dodat je dependency-free `bin/catalog-page-smoke.php` sa proverom svih korelisanih kataloških stranica;
- dodati su statički i PHPUnit contract testovi koji sprečavaju povratak undefined-variable greške;
- nema nove migracije niti izmene podataka u bazi.

## 2.1.0-beta7.18 — Korelisane specifikacije i strukturirani procesori

- uveden je generički sistem zavisnih dropdown specifikacija sa roditeljskim poljem i mapom dozvoljenih opcija;
- izbor brenda automatski filtrira linije proizvoda na unosu artikla, u administratorskom pregledu i u korisničkom katalogu;
- serverska validacija sprečava povezivanje linije sa pogrešnim brendom i čuvanje nepovezane zavisne opcije;
- kružne veze specifikacionih polja nisu dozvoljene;
- dodela zavisnog polja tipu artikla automatski uključuje i roditeljsko polje;
- postojeće `options_text` vrednosti uvoze se u `specification_options` bez brisanja starog izvora;
- dodata je tabela `specification_option_dependencies` za više-prema-više mapu roditeljskih i zavisnih opcija;
- `product_spec_values.value_detail` čuva tačan model odvojeno od standardizovane porodice;
- procesor je pretvoren u dropdown porodica uz tekstualno polje za oznake poput `1135G7` i `PRO 5625U`;
- dodate su porodice Intel Core/Core Ultra, AMD Ryzen/Ryzen AI, Qualcomm Snapdragon X/X2 i Apple M1–M5;
- postojeće pune CPU oznake se nedestruktivno normalizuju samo kada je porodica pouzdano prepoznata;
- uvedeni su generički specifikacioni filteri u oba kataloška pregleda, uključujući dodatni detalj i opseg;
- izbor tipa artikla prikazuje samo filtere dodeljene tom tipu;
- dodata je komanda `app:catalog-correlations-doctor` i deployment schema provera;
- migracija je ponovljiva i prilagođena MariaDB 10.11 delimičnim DDL izvršenjima.

## 2.1.0-beta7.17.2 — Navigation hover bridge hotfix

- ispravljeno je prerano zatvaranje desktop podmenija pri pomeranju kursora sa glavne stavke na padajući meni;
- dodat je 280 ms hover grace period koji se prekida čim kursor uđe u sam podmeni;
- dodat je nevidljiv CSS most preko razmaka između summary stavke i apsolutno pozicioniranog podmenija;
- klik sada pouzdano „prikvači” podmeni dok korisnik ne klikne van menija, ponovo na istu stavku ili pritisne Escape;
- hover-open podmeni se i dalje automatski zatvara nakon napuštanja i glavne stavke i podmenija;
- zadržano je pravilo da istovremeno može biti otvoren samo jedan navigacioni podmeni;
- dodate su statičke i PHPUnit contract provere za hover bridge, grace timer i click-pin ponašanje.

## 2.1.0-beta7.17.1 — Legacy permissions schema hotfix

- ispravljena je MariaDB/MySQL greška `Unknown column updated_at in permissions` pri beta7.17 migraciji;
- migracija potraživanja više ne pretpostavlja da `permissions` ima obe Laravel timestamp kolone;
- dozvola `receivables.manage` se ažurira ili upisuje samo kroz kolone koje stvarno postoje;
- isti schema-aware pristup dodat je `CoreAccessSeeder` klasi i pivot tabeli dozvola;
- migracija ostaje recovery-safe nakon prethodnog delimičnog DDL izvršenja i ne briše već kreirane tabele naplate;
- dodate su statičke provere koje sprečavaju ponovno uvođenje obaveznog `permissions.updated_at` upisa.

## 2.1.0-beta7.17 — Potraživanja, plan naplate i UI regresije

- uveden je kompletan modul **Potraživanja i naplata** za porudžbine sa uplatom na račun;
- predmet naplate se automatski otvara pri kreiranju porudžbine ili izdavanju predračuna/računa, ako postoji stvarno dugovanje;
- svaki predmet dobija jedinstveni `NAP` broj, odgovorno lice, status, fazu naplate, sledeću akciju i audit istoriju;
- uveden je aging pregled po segmentima: nije dospelo, 1–7, 8–15, 16–30, 31–60, 61–90 i više od 90 dana;
- podržane su automatske opomene pre dospeća i po podesivim fazama kašnjenja;
- opomene koriste postojeći pouzdani e-mail outbox, dedupe ključeve, retry i aktivnu reviziju dokumenta;
- mogu se podesiti autor porudžbine, odgovorno lice i dodatne adrese, uz račun ili predračun kao PDF prilog;
- uvedeni su statusi praćenja, kontakta, obećane uplate, plana rata, eskalacije, spora i automatski zatvorenog predmeta;
- obećani datum može privremeno zaustaviti automatske opomene do isteka dogovorenog roka;
- plan otplate podržava od 1 do 24 rate, a zbir mora biti jednak stvarnom preostalom dugu;
- rate ne kreiraju lažne uplate: samo verifikovane finansijske stavke raspoređuju se na najstarije rate;
- predmet i preostale rate automatski se zatvaraju kada stvarni saldo porudžbine postane nula;
- uveden je komunikacioni dnevnik sa javnim i internim beleškama, kanalima i smerom komunikacije;
- korisnički detalj porudžbine prikazuje samo javnu komunikaciju i sopstveni plan otplate;
- dodati su dashboard KPI, filteri, CSV izvoz i nova dozvola `receivables.manage`;
- ispravljeno je ponašanje svih navigacionih podmenija: hover ili klik otvara, klik van, Escape ili izbor linka zatvara;
- istovremeno može biti otvoren samo jedan podmeni, a `aria-expanded` ostaje sinhronizovan;
- globalni checkbox i radio elementi smanjeni su na normalnu veličinu i više ne nasleđuju širinu standardnih input polja;
- migracija je recovery-safe za MariaDB i popravlja nedostajuće kolone, indekse i strane ključeve nakon delimičnog DDL pokušaja;
- dodati su `app:receivables-doctor`, deployment provere i Feature testovi za opomene, deduplikaciju, rate, zatvaranje i UI regresije.

# Changelog

## 2.1.3.3 - Storage Capacity Total & Data Migration Hotfix

- uklonjeno je duplo ručno značenje polja kapaciteta diska: lista diskova sa pojedinačnim kapacitetima je iznad, a staro polje ispod prikazuje samo automatski ukupni zbir;
- dodat je `StorageSpecificationService` kao jedina backend tačka za normalizaciju, prikaz i zbir do osam diskova na proizvodima i varijantama;
- migracija povezuje izvorno polje diskova sa izvedenim ukupnim poljem i normalizuje postojeće `value_text`, `value_json` i `value_number` podatke;
- stari kapacitet prenosi se na prvi disk samo kada pojedinačni kapaciteti nisu postojali, dok postojeći pojedinačni kapaciteti imaju prednost;
- istorijski ukupni kapacitet bez sačuvanog tipa diska čuva se bez gubitka dok korisnik ne dopuni detalje;
- ukupni kapacitet je read-only u UI-u, ponovo se računa na backendu i zaštićen je od bulk izmene;
- forma i JavaScript čuvaju početni legacy zbir pri prvom otvaranju, a nakon korisničke izmene računaju novi zbir u realnom vremenu;
- lifecycle repair čisti zastarele storage veze, a catalog settings doctor proverava proizvode i varijante;
- izvedeni zbir ne duplira kompletnost, automatski naziv, naziv varijante ni generisani SKU;
- dodat je dependency-free `bin/storage-capacity-total-smoke.php` i PHPUnit contract regresija;
- dodata je migracija `2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php`;
- nema ručnog brisanja fajlova.

## 2.1.0-beta7.24 — Customer Portal 2.0

- uveden je bezbedan aktivacioni tok za kupce sa jednokratnim SHA-256 tokenom, rokom važenja od 72 sata, Cloudflare Turnstile proverom i samostalnim postavljanjem lozinke;
- Customer Portal dobija centralnu administraciju kupaca, ponovno slanje poziva i pregled statusa aktivacije;
- omogućeno je kontrolisano povezivanje ili prenos postojeće porudžbine na potvrđen nalog kupca, uz eksplicitnu potvrdu, razlog promene i trajnu audit istoriju;
- uveden je centar za komunikaciju kupca i podrške sa javnim porukama, internim beleškama, statusima, prioritetima, zaduženom osobom i povezivanjem sa porudžbinom;
- interna beleška se filtrira na nivou relacije i nikada se ne prikazuje u korisničkom portalu;
- korisnik može da ažurira ime, prezime, telefon i adresne podatke, dok promena e-mail adrese ostaje administratorska operacija;
- uveden je nezavisan registry aktivnih prijava koji radi i sa file session driverom, prikazuje uređaj/IP/poslednju aktivnost i omogućava opozivanje pojedinačnih ili svih drugih sesija;
- zastarele evidencije prijava se automatski zatvaraju, remember-me prijava se prepoznaje pri ponovnom uspostavljanju sesije, a promena lozinke pravilno rotira i ponovo registruje trenutnu sesiju;
- promena lozinke opoziva ostale prijave, API tokene i trajne remember-me tokene;
- ponovno slanje aktivacionog linka više ne deaktivira kupca koji već ima aktivan nalog, dok postojeći aktivni kupci dobijaju nedestruktivan activation backfill;
- aktivaciona stranica koristi `no-store` cache politiku, a scheduler svakodnevno izvršava `app:customer-portal-maintenance` radi čišćenja iskorišćenih tokena i starih session evidencija;
- dodati su admin i korisnički UI, responsive stilovi, novi rate limiteri, System/Deployment provere i prošireni `app:customer-portal-doctor`;
- dodati su `bin/customer-portal-2-smoke.php`, PHPUnit contract i Feature testovi;
- paket sadrži novu recovery-safe migraciju `2026_08_01_000032_create_customer_portal_2_beta7_24.php`.

## 2.1.0-beta7.16 — E-mail porudžbina, NBS IPS QR i garancijski dani

- korisnik, Administrator i SuperAdministrator mogu kreirati porudžbinu kroz isti transakcijski workflow;
- uveden je pouzdani `order_email_outbox` koji odvaja poslovnu transakciju od SMTP slanja;
- podešavaju se autor porudžbine, odgovorno lice i proizvoljna lista dodatnih adresa;
- uvedeni su zasebni intervali slanja za novu porudžbinu, promene i dokumente;
- podržani su događaji statusa, tracking broja, plaćanja, preuzimanja, dodele, rokova, kompletiranja, ponovnog otvaranja i storniranja;
- bira se koji se dokument šalje: potvrda, predračun, račun ili otpremnica;
- opciono se aktivan račun može prilagati uz kasnija obaveštenja;
- svaka adresa dobija zaseban e-mail, bez otkrivanja drugih primalaca;
- outbox koristi dedupe ključ, zbirne intervale, retry sa backoff-om i recovery zaglavljenih poruka;
- storniran dokument se više ne može poslati kao zastareli prilog;
- uveden je zvanični NBS IPS QR za predračun i račun kod uplate na račun;
- QR payload čuva primaoca, platioca, račun, šifru, svrhu, poziv na broj i tačan ukupan iznos u RSD;
- NBS PNG se čuva privatno kao snapshot revizije dokumenta i ugrađuje u PDF bez GD zavisnosti;
- nova revizija nakon storniranja dobija novi NBS QR snapshot;
- garancijsko pravilo i garantni list podržavaju trajanje kao kombinaciju meseci i dana;
- dodati su `app:order-emails-doctor`, IPS QR PDF smoke test, deployment provere i Feature testovi;
- migracija ima MariaDB-safe recovery putanju za delimično izvršene DDL korake.

## 2.1.0-beta7.15 — Garancije, serijski brojevi i preventivno održavanje

- uvedena su globalna, kategorijska i proizvodna pravila garancije sa prioritetom i snapshot uslovima;
- po kompletiranju isporuke automatski se izdaje jedan `GAR` garantni list za svaku pokrivenu stavku;
- garantni list čuva kupca, adresu, telefon, proizvod, SKU, količinu, trajanje, uslove i serijske brojeve;
- korisnik dobija ekran **Moje garancije** i privatni PDF garantni list;
- administrator upravlja pravilima, serijskim brojevima, datumima, uslovima i poništavanjem;
- uvedeno je preventivno održavanje sa rokom, zakazivanjem, rezultatom i automatskim sledećim terminom;
- automatizacija upozorava na skori istek garancije i dospelo ili prekoračeno održavanje;
- dashboard prikazuje aktivne garancije, garancije pred istekom i dospelo održavanje;
- `app:warranties-backfill` bira samo porudžbine sa nedostajućim garancijama i bezbedno se ponavlja;
- administratorske statistike su ograničene na scope dodeljenih porudžbina;
- zakazivanje održavanja više ne menja izabrano vreme na ponoć tokom provere datuma;
- dodati su `app:warranties-doctor`, PDF smoke test, deployment provere i Feature testovi.

## 2.1.0-beta7.14.1 — MySQL FK indeks hotfix za revizije dokumenata

- ispravljena je MySQL greška 1553 pri uklanjanju `order_documents_order_type_unique` indeksa;
- migracija sada prvo kreira poseban `order_documents_order_id_fk_index` za strani ključ `order_id`, pa tek zatim uklanja staro unique ograničenje;
- kolone revizija se dodaju pre izmene indeksa, pa je ponavljanje migracije bezbedno i nakon delimično uspešnog DDL pokušaja;
- migracija ostaje nedestruktivna: ne briše dokumente, poslovne brojeve niti istoriju storniranja;
- deployment provera više ne prijavljuje neaktivnu Redis konfiguracionu sekciju kao grešku; proveravaju se samo aktivni cache, session i queue driveri;
- dodat je migration contract test za tačan redosled FK indeksa i DROP INDEX operacije.

## 2.1.0-beta7.14 — Revizije predračuna, računa i otpremnica

- ispravljeno je ponovno izdavanje predračuna i računa nakon storniranja;
- uklonjeno je staro unique ograničenje koje je dozvoljavalo samo jedan dokument istog tipa po porudžbini;
- aktivan dokument ostaje idempotentan, dok stornirani dokument dobija novu reviziju i novi poslovni broj;
- uvedeni su `revision_number`, `supersedes_document_id` i obavezan razlog storniranja;
- postojeći dokumenti se nedestruktivno povezuju u istorijski lanac;
- administratorski prikaz razlikuje aktivan dokument, stornirane revizije i dokument koji nova revizija menja;
- dugme prikazuje „Izdaj novi predračun/račun” kada postoji samo stornirana istorija;
- izdavanje dokumenta odmah otvara PDF u novom tabu;
- korisnik vidi samo aktivnu reviziju, dok administrator može pregledati i stornirani PDF sa STORNIRANO oznakom;
- notification greška posle storniranja više ne može vratiti HTTP 500;
- deployment i payments doctor proveravaju da li je unique ograničenje zaista uklonjeno;
- dodati su Feature i migration contract testovi za predračun i račun posle storniranja.

## 2.1.0-beta7.13 — Servisni lager rezervnih delova i nabavka

- uveden je zaseban servisni lager sa šifrom, jedinicom mere, fizičkim stanjem, rezervacijama, minimumom i prosečnom cenom;
- delovi se dodaju direktno na terenski radni nalog kao lokalni lager ili spoljni izvor;
- lokalni deo se prvo rezerviše, a fizičko stanje se umanjuje tek pri završetku prema stvarno utrošenoj količini;
- neiskorišćena rezervacija automatski se oslobađa, dok otkazivanje radnog naloga vraća sve rezervacije;
- uveden je nepromenljiv ledger kretanja servisnih delova sa jedinstvenim event key vrednostima;
- početno stanje novog dela evidentira se kao `opening_balance` kretanje, pa ledger ostaje potpun od prvog unosa;
- nizak lager se računa prema stvarno raspoloživoj količini (`stanje - rezervisano`), a ne samo prema fizičkom stanju;
- ručne korekcije su idempotentne i ne mogu spustiti stanje ispod rezervisane količine;
- idempotency provera ručne korekcije ponavlja se nakon zaključavanja reda, čime je zatvorena konkurentna dvostruka korekcija;
- privremeni broj nacrta nabavke je jedinstven i bezbedan pri istovremenom kreiranju više zahteva;
- uveden je šifarnik dobavljača rezervnih delova sa kontaktom i očekivanim rokom isporuke;
- zahtevi za nabavku prolaze kroz nacrt, slanje, poručivanje, prijem i otkazivanje;
- prijem nabavke povećava stanje i računa ponderisanu prosečnu nabavnu cenu;
- dashboard prikazuje nizak servisni lager, rezervacije, otvorene nabavke i vrednost servisnih zaliha;
- automatizacija upozorava na nizak servisni lager i nabavke koje kasne;
- dodate su dozvole `service_parts.view`, `service_parts.manage` i `service_parts.procurement`;
- dodat je `app:service-parts-doctor`, deployment provera i Feature testovi za rezervaciju, utrošak, oslobađanje i prijem nabavke.

## 2.1.0-beta7.12 — Terenske operacije i radni nalozi

- uveden je operativni kalendar za servisne posete, zamenske isporuke i preuzimanje vraćene robe;
- svaka fizička postprodajna radnja automatski dobija terenski radni nalog sa `RN-YYYYMMDD-000001` brojem;
- uveden je šifarnik internih ekipa i spoljnih servisnih partnera sa kontaktom, vozilom i područjem rada;
- radni nalog čuva termin, krajnjeg primaoca, adresu, telefon, referencu, javnu i internu napomenu;
- statusni tok obuhvata planiran nalog, ekipu na putu, dolazak na lokaciju, završetak i otkazivanje;
- polazak nije moguć bez aktivne ekipe i kompletnog termina, a završetak zahteva prethodno evidentiran dolazak;
- sprečeno je preklapanje aktivnih termina iste ekipe, uz zaključavanje ekipe u transakciji;
- završetak evidentira rezultat, kilometražu, putne troškove, rad, delove i ukupan trošak;
- PDF i slikovni dokazi čuvaju se privatno, sa javnom ili internom vidljivošću po prilogu;
- korisnik vidi javni termin, status, kontakt ekipe, rezultat i dozvoljene priloge;
- postojeće fizičke postprodajne radnje se nedestruktivno pretvaraju u radne naloge;
- automatizacija upozorava na naloge bez termina duže od 24 sata i probijene završne termine;
- dashboard prikazuje današnje, neraspoređene i zakasnele radne naloge;
- dodate su dozvole `field_operations.view` i `field_operations.manage`;
- dodat je `app:field-operations-doctor`, deployment readiness provera i Feature testovi za konflikt termina i kontrolisani završetak.

## 2.1.0-beta7.11 — Izvršenje postprodajnih odluka

- uvedene su izvršne postprodajne radnje sa `PRA` brojem, statusom, odgovornim licem, terminom, rokom i audit tragom;
- podržane su servisna poseta, zamenska isporuka, prijem vraćene robe i refundacija;
- svaka radnja sadrži pogođene stavke, količine, postupak sa robom, javnu poruku i internu napomenu;
- automatska zamena umanjuje lokalni lager, a odobren povrat može vratiti ispravnu robu na lager;
- stock movements koriste jedinstveni event key i ponovljen zahtev ne može dvaput promeniti stanje;
- refundacija radi i za kompletiranu porudžbinu, ali ne može premašiti neto potvrđeni iznos uplata;
- finansijska stavka se vezuje za izvršnu radnju preko `after_sales_action_id`, čime je sprečeno dupliranje refundacije;
- slučaj se ne može završiti dok postoji planirana ili aktivna radnja;
- odluke o popravci, zameni, povratu i refundaciji zahtevaju najmanje jednu završenu izvršnu radnju;
- korisnik vidi javni napredak radnje, dok interni detalji lagera i napomene ostaju u administraciji;
- dashboard i lista slučajeva prikazuju broj radnji koje čekaju izvršenje;
- automatizacija upozorava na prekoračen rok izvršne radnje;
- dodata je dozvola `after_sales.execute`, repair doctor provera i Feature testovi za idempotentni lager i refundaciju.

## 2.1.0-beta7.10 — Reklamacije, povrati i servisni slučajevi

- uveden je kompletan postprodajni modul povezan sa isporučenim i kompletiranim porudžbinama;
- korisnik može otvoriti reklamaciju, povrat ili servisni zahtev i izabrati pogođene stavke i količine;
- svaki slučaj dobija PS broj, prioritet, SLA rok, odgovorno lice i statusnu istoriju;
- uvedene su javne poruke korisniku i interne administratorske napomene;
- PDF i slikovni prilozi čuvaju se privatno i preuzimaju samo kroz autorizovanu rutu;
- administratorski red podržava pretragu, tip, status, prioritet i probijene rokove;
- konačna odluka sadrži vrstu rešenja i obavezno obrazloženje;
- automatizacija kreira deduplikovano upozorenje za probijen rok postprodajnog slučaja;
- dodate su dozvole `after_sales.create`, `after_sales.view_own` i `after_sales.manage`;
- dodat je `app:after-sales-doctor` i Feature testovi za scope, privatne priloge, poruke i obradu;
- dashboard prikazuje broj aktivnih slučajeva, probijene rokove i slučajeve koji čekaju odgovor kupca;
- ako porudžbina nema aktivno odgovorno lice, novi slučaj obaveštava aktivne SuperAdministratore;
- privatni download priloga koristi `no-store`, `nosniff` i autorizaciju po slučaju;
- statusna obrada je dodatno zaštićena od konkurentnog zatvaranja, a ponovno otvaranje zahteva razlog;
- konačno zatvaranje čuva datum prethodnog rešavanja umesto da ga izgubi;
- otvaranje slučaja namerno ne menja automatski lager ili finansije.

## 2.1.0-beta7.9 — Otpremnica Error 500 hotfix

- ispravljen je MySQL/MariaDB schema problem zbog kog je `order_documents.document_type` na starijim instalacijama ostao ENUM bez vrednosti `delivery_note`;
- nova nedestruktivna migracija konvertuje tip dokumenta u `VARCHAR(40)` i omogućava izdavanje otpremnice sa OTP brojem;
- pre izdavanja dokumenta proveravaju se potrebne tabele, snapshot kolone i kompatibilnost tipa kolone;
- ako migracija nije primenjena, administrator dobija jasnu poruku sa komandom `php artisan migrate --force`, umesto Error 500;
- neočekivana greška pri izdavanju dobija incident ID i zapis u logu, bez prikaza tehničkih detalja korisniku;
- kvar opcionog notification kanala više ne obara već uspešno izdat dokument;
- IPS payload se generiše samo za predračun i račun, ne i za otpremnicu;
- PDF renderer vraća kontrolisani HTTP 503 sa incident ID-em umesto generičkog Error 500;
- deployment i payments doctor proveravaju da li tip dokumenta podržava `delivery_note`;
- dodat je poseban smoke test za PDF otpremnicu i regresioni migration contract test.

## 2.1.0-beta7.8 — Evidencija isporuke, dokaz i otpremnica

- uvedena je strukturirana evidencija isporuke sa načinom, datumom, primaocem, telefonom, referencom i napomenom;
- završetak porudžbine sada može da sačuva privatni dokaz isporuke u PDF/JPG/PNG/WebP formatu do 10 MB;
- dokaz isporuke se isporučuje samo kroz autorizovanu Laravel rutu i nije javno dostupan;
- dodat je novi PDF dokument **Otpremnica** sa zasebnim `OTP` brojačem i snapshotom podataka o isporuci;
- SuperAdministrator može kontrolisano ponovo otvoriti greškom kompletiranu porudžbinu uz obavezan razlog;
- ponovno otvaranje čuva uplate, dokaz isporuke i istoriju, ali uklanja terminalnu blokadu radi korekcije;
- detalj porudžbine za administratora i korisnika prikazuje jasnu karticu evidencije isporuke;
- timeline prikazuje potvrdu isporuke i ponovno otvaranje;
- dodate su dozvole `orders.confirm_delivery` i `orders.reopen`;
- doctor i deployment provere proveravaju novu tabelu, kolone i dozvole;
- dodati su regresioni testovi za privatni dokaz, COD saldo, otpremnicu, autorizaciju i ponovno otvaranje.

## 2.1.0-beta7.7 — PDF identitet i konačno kompletiranje porudžbine

- dodat je poseban PDF logo u administratorska podešavanja poslovnih dokumenata, sa pregledom, zamenom i uklanjanjem;
- logo se čuva na public storage disku i normalizuje u JPEG za pouzdano ugrađivanje u interni PDF generator;
- PDF kartica kupca prikazuje isključivo podatke krajnjeg primaoca iz adrese isporuke i više ne prikazuje e-mail subagenta;
- uvedeno je jasno terminalno stanje **Kompletirana**, sa datumom, administratorom i završnom napomenom;
- kompletiranje porudžbine sa plaćanjem pouzećem automatski evidentira samo preostali saldo kao verifikovanu COD uplatu;
- porudžbine sa drugim načinom plaćanja mogu se kompletirati tek nakon potpune naplate;
- nakon kompletiranja zaključane su promene statusa, tracking-a, rokova, dodele i sve nove finansijske stavke;
- lista porudžbina i izveštaji dobijaju filter i vidljivu oznaku **Kompletirana**;
- dodata nedestruktivna migracija za `completed_at`, `completed_by` i `completion_note`;
- dodati regresioni testovi za PDF logo, uklanjanje subagent e-maila, COD kompletiranje i zaključavanje uplata.

## 2.1.0-beta7.6 — Legacy PDF i uplate redirect hotfix

- uklonjena je kontradikcija zbog koje su PDF i payment akcije bile prikazane na uvezenim porudžbinama, ali su ih servisi odbijali zbog `source_system = legacy`;
- potvrda porudžbine, predračun i račun sada mogu da se izdaju za lokalno uvezene porudžbine, uključujući završene/poslate porudžbine;
- ručno evidentiranje uplata i refundacija sada radi i za lokalno uvezene porudžbine bez izmene legacy baze ili lagera;
- korisnička potvrda uplate može da se doda i za uvezenu porudžbinu kada pripada prijavljenom korisniku i koristi uplatu na račun;
- PDF snapshot dobija bezbedan naziv kupca `Kupac` kada stariji zapis nema popunjeno ime;
- ograničenje za legacy porudžbine ostaje aktivno za lager, rezervacije, dodelu i ostale operacije koje bi menjale istorijske podatke;
- dodati su regresioni testovi za PDF i uplatu na završenoj uvezenoj porudžbini;
- nema nove migracije baze.

## 2.1.0-beta7.5 — PDF potvrda i evidencija uplata hotfix

- potvrda porudžbine više ne zahteva posebno popunjen `documents_company_name`; koristi naziv sajta/aplikacije kao bezbedan fallback;
- dodata nedestruktivna repair migracija za `document_counters`, `order_documents`, `order_payments` i payment kolone na tabeli `orders`;
- repair migracija obnavlja nedostajuće dozvole i standardnim grupama vraća pristup sopstvenim dokumentima i potvrdama uplata;
- `app:payments-inventory-doctor --repair --render` sada proverava i PDF dokument šemu;
- dodati regresioni testovi za izdavanje potvrde bez dodatnih document podešavanja i ručno evidentiranje delimične uplate iz admin radne table.

## 2.1.0-beta7.4 — Turnstile podešavanja u administraciji

- dodat je ekran **Podešavanja → Cloudflare Turnstile**;
- Site Key, uključivanje zaštite i očekivani hostname mogu se menjati bez pristupa `.env` fajlu;
- Secret Key se čuva šifrovano Laravel `APP_KEY` ključem i nikada se ne vraća u HTML ili audit log;
- podešavanja iz baze imaju prioritet, dok `.env` ostaje fallback;
- login i zahtev za resetovanje lozinke koriste efektivnu konfiguraciju odmah nakon čuvanja;
- deployment check sada proverava efektivnu Turnstile konfiguraciju;
- dodati su feature testovi za šifrovano čuvanje i korišćenje Site Key-a iz baze;
- nema nove migracije baze.

## 2.1.0-beta7.3 — Order detail render hardening

- otklonjen je preostali HTTP 503 slučaj na `/admin/orders/{id}`;
- uveden je scalar `OrderDetailPresenter` za bezbedno čitanje raw podataka, datuma i named ruta pre Blade rendera;
- MySQL zero datum i nevažeći datum više ne mogu oboriti Blade render kroz Eloquent cast;
- admin i korisnički detalj dobijaju minimalni HTTP 200 read-only fallback umesto 503;
- fallback je detektabilan u doctor komandi i ne može proći kao lažni PASS;
- `app:orders-doctor` ispisuje originalnu exception klasu, poruku, fajl i liniju i proverava oba detail prikaza;
- `app:detail-pages-doctor` nastavlja audit kataloga i galerije i kada order detail ne prođe;
- dopunjene su payment/inventory Gate definicije;
- dodati su regresioni testovi za zero/invalid datum, named route fallback i protected detail render;
- nema nove migracije baze.

## 2.1.0-beta7.2 — Order detail pages hotfix

- zaštićeni su administratorski i korisnički detalj porudžbine;
- opcione relacije, timeline i IPS podaci više ne mogu oboriti celu stranicu;
- dodati su `--order-id` render audit i `app:detail-pages-doctor`;
- nema nove migracije baze.

## 2.1.0-beta7.1 — Orders render & image rotation hotfix

- administratorska lista `/admin/orders` dobija zaseban readiness/SQL/render zaštitni sloj;
- kompletan Blade i authenticated layout renderuju se unutar zaštićenog toka, pa greška više ne završava kao generički HTTP 500;
- dodat je `app:orders-doctor --repair --render`, koji proverava istu stranicu koju otvara browser;
- query za porudžbine učitava opcione relacije samo kada njihove tabele postoje;
- recovery režim prikazuje precizne nedostajuće tabele/kolone i komandu za popravku;
- ekran izmene artikla sada prikazuje postojeće slike i rotaciju ulevo/udesno za 90°;
- lokalne slike se rotiraju preko privremenog fajla i atomskog upisa;
- legacy fotografija koristi copy-on-write: originalni legacy fajl ostaje netaknut, a rotirana kopija prelazi u Laravel storage;
- nedostupna GD ekstenzija ili neispravan fajl vraćaju validacionu poruku umesto HTTP 500;
- nema nove migracije baze.

## 2.1.0-beta7 — Product Gallery & Buyer Experience

- pojedinačni prikaz artikla dobija veliku interaktivnu galeriju namenjenu kupcu;
- thumbnail traka omogućava brzo listanje svih fotografija bez ponovnog učitavanja stranice;
- dodati su prethodna/sledeća kontrola, brojač fotografija i preload susednih slika;
- klik na glavnu fotografiju otvara fullscreen lightbox sa zatamnjenom pozadinom;
- lightbox podržava zoom od 100% do 400%, točkić miša, dvostruki klik i pomeranje uvećane fotografije;
- tastatura podržava Escape, strelice, +, -, i 0 za reset zoom-a;
- mobilni prikaz podržava swipe ulevo/udesno na glavnoj slici i u fullscreen režimu;
- dodati su pristupačni ARIA nazivi, vraćanje fokusa nakon zatvaranja i reduced-motion podrška;
- greška jedne fotografije prikazuje kontrolisanu poruku i ne obara stranicu proizvoda;
- galerija radi i kada artikal ima samo jednu fotografiju;
- nema nove migracije baze niti nove frontend zavisnosti.

## 2.1.0-beta6 — Security, Audit, Backup & System Health

- dodat je centralni **System Health & Backup** ekran sa stanjem baze, migracija, scheduler-a, automatizacije, backupa, storage direktorijuma, diska, legacy read-only zaštite i aplikacionih grešaka;
- dodat je scheduler heartbeat i dnevni snapshot sistemskog zdravlja;
- dodat je privatni MySQL backup preko `mysqldump`, GZIP kompresija, SHA-256 manifest i kopiranje poslovnih upload fajlova;
- dodati su dnevni i nedeljni retention planovi, CLI doctor i ručno kreiranje backupa iz administracije;
- backup putanja se odbija ako je unutar `public` direktorijuma;
- dodati su request ID, security response header-i i CSP zaštita koja ne remeti postojeći UI;
- uvedeni su rate limiter-i za upload, export, administrativne upise i backup;
- odbijene dozvole, neuspele prijave i pristup blokiranog naloga upisuju se u `security_events` bez čuvanja lozinke ili login vrednosti;
- audit log dobija nivo, request ID, filtere po korisniku/nivou/datumu i CSV izvoz;
- rekurzivna sanitizacija uklanja password/token/secret vrednosti iz audit i security metadata;
- uvedena je bezbedna MySQL test DB provera koja odbija `migrate:fresh` van potvrđene `_test` baze;
- dodata je migracija `2026_07_23_000014_create_security_backup_health_beta6.php` i nove dozvole.

## 2.1.0-beta5 — Inventory UX & Workflow Polish

- stranica **Napredni lager** više ne prikazuje Ulaz robe i Popis lagera kao dve zbijene široke forme;
- uvedeni su operativni tabovi, a aktivna forma koristi punu raspoloživu širinu;
- uklonjeni su prelivanje inputa iz kartica, horizontalni scroll cele stranice i unutrašnji vertikalni scroll tabele;
- tabela koristi kontrolisani lokalni horizontalni scroll samo kada je to neophodno;
- mobilni redovi lagera prikazuju se kao kartice sa jasnim `data-label` nazivima;
- dodat je izbor prikaza 25, 50, 100 ili 250 artikala i poruka kada je rezultat skraćen;
- pretraga i limit se čuvaju nakon ulaza robe ili završetka popisa;
- istorija i upozorenja lagera dobijaju stabilan responsive raspored;
- dodat je regresioni `InventoryWorkspaceUiTest` i proširene statičke CSS/Blade provere;
- nema nove migracije niti izmene transakcijske/idempotency logike lagera.

## 2.1.0-beta4 — Notifications, Automation & Release Hardening

- dodat je operativni scheduler bez Redis-a: file lock, sync notifikacije i evidencija svakog pokretanja;
- automatski se detektuju nepreuzete porudžbine, istekli rokovi obrade/slanja, dospela neplaćena potraživanja i nizak/nulti lager;
- upozorenja su deduplikovana, imaju reminder interval, automatsko razrešenje i ručno zatvaranje;
- dodat je dnevni operativni pregled Administratorima i SuperAdministratorima;
- dodat je ekran **Podešavanja → Automatizacija** sa KPI karticama, otvorenim upozorenjima, istorijom pokretanja i ručnim scan/digest akcijama;
- korisnici dobijaju preference za in-app/e-mail kanal i kategorije porudžbina, plaćanja, provizija, lagera i dnevnog pregleda;
- dodate su tabele `automation_runs`, `operational_alerts` i `notification_preferences`;
- dodate su komande `app:automation-run` i `app:automation-doctor --repair --run`;
- Laravel scheduler pokreće scan svakog sata i digest svakog dana u 08:05;
- deployment check i CoreAccessSeeder obuhvataju novu `automation.manage` dozvolu;
- dodati su Feature testovi za deduplikaciju upozorenja i notification preference.

## 2.1.0-beta3.2 — Commission action modal sizing hotfix

- dugme **Obradi** više ne otvara apsolutno pozicioniran panel unutar scroll kontejnera tabele;
- tabela provizija više ne dobija mali vertikalni scrollbar kada je obrazac otvoren;
- obrazac se prikazuje kao veliki centrirani viewport modal sa backdrop-om;
- modal koristi do 620 px širine i gotovo celu raspoloživu visinu ekrana;
- dodat je jasan naslov sa brojem porudžbine i korisnikom;
- dodat je taster × za zatvaranje, podrška za Escape i automatsko zatvaranje prethodnog modala;
- unutrašnji skrol se koristi samo kada sadržaj stvarno ne može stati u ekran;
- mobilni modal ostaje unutar viewporta i akcijska dugmad su pune širine;
- nema nove migracije niti promene poslovne logike.

## 2.1.0-beta3.1 — Payments & Inventory migration hotfix

- uklonjen je nevažeći globalni `use Throwable;` iz migracije `000012`;
- `catch` blokovi u migraciji sada koriste potpuno kvalifikovani `\Throwable`;
- hosting više ne prekida migraciju upozorenjem „The use statement with non-compound name 'Throwable' has no effect“;
- `bin/php-lint.php` sada tretira PHP warning, deprecated i notice izlaz kao neuspeh, čak i kada `php -l` vrati exit code 0;
- statičke provere zabranjuju ponovno uvođenje globalnog `use Throwable;` u migracije;
- hotfix ne menja poslovnu logiku niti briše podatke; nakon deploya se ponovo pokreće pending migracija `000012`.

## 2.1.0-beta3 — Payments, Advanced Inventory & Operational Reports

- dodata je evidencija uplata i refundacija sa automatskim saldom porudžbine;
- korisnik može poslati potvrdu uplate, koja se čuva privatno i otvara samo kroz autorizovanu rutu;
- Administrator verifikuje, odbija ili stornira stavke, uz audit, timeline i obaveštenja;
- predračun i račun postavljaju rok dospeća, a PDF prikazuje IPS podatke, plaćeno i preostalo;
- dodati su transakcijski i idempotentni ulaz robe i popis lagera sa row lock-om i stock movement tragom;
- dodata je stranica Napredni lager sa upozorenjima, istorijom ulaza/popisa i CSV izvozom;
- Reports sada prikazuje KPI naplate i lagera i nudi posebne CSV izvoze;
- dodate su tabele `order_payments`, `stock_receipts`, `stock_receipt_items`, `inventory_counts` i `inventory_count_items`;
- dodate su dozvole `payments.manage`, `payments.upload_proof`, `payments.view_own`, `inventory.receive`, `inventory.count` i `inventory.export`;
- dodat je `app:payments-inventory-doctor --repair --render`;
- Reports i Inventory imaju kontrolisani fallback/503 umesto generičkog HTTP 500 kada šema nije kompletna;
- dodati su Feature testovi za upload/verifikaciju uplate, idempotentni ulaz robe, popis i nove stranice.

## 2.1.0-beta2 — Operational Orders & Commissions

- završen je operativni tok provizija: odobravanje, pojedinačna i masovna isplata, storniranje, napomena, referenca isplate i istorija statusa;
- dodata je stranica **Moje provizije** sa korisničkim scope-om i porukom da provizija po komadu nije manja od 20 EUR, bez prikazivanja automatskog maksimuma;
- dodati su CSV i PDF izvoz provizija, filteri po korisniku, odgovornom licu, porudžbini, periodu i statusu;
- dodati su preuzimanje porudžbine, operativni rokovi, privatne interne napomene, timeline i SuperAdministrator ponovna dodela drugom Administratoru;
- Administrator i dalje vidi samo njemu dodeljene porudžbine, dok SuperAdministrator upravlja kompletnim scope-om;
- uvedena su database obaveštenja za kreiranje, preuzimanje, status, tracking, rokove, dokumente, dodelu i provizije; e-mail kanal je opcion i podrazumevano isključen;
- dodati su auditovani batch-evi isplate provizija i nove tabele `order_internal_notes`, `order_assignments`, `commission_payment_batches` i `notifications`;
- dodata je migracija `2026_07_23_000011_create_operational_orders_commissions_beta2.php` i komanda `app:operations-doctor --repair --render`;
- deployment provera sada obuhvata sve beta2 tabele, kolone i dozvole;
- stranice provizija imaju schema/render fallback umesto generičkog HTTP 500;
- dodati su Feature testovi za workflow provizija, privatnost internih napomena, ponovnu dodelu i masovnu isplatu.

## 2.1.0-beta1.3 — Reports protected render hotfix

- kompletan Reports Blade i authenticated layout renderuju se unutar zaštićenog toka;
- `app:reports-doctor --render` proverava isti HTML koji dobija browser;
- render/logging greška više ne daje nezaštićeni generički 500 odgovor.

## 2.1.0-beta1.2 — Reports 500 recovery hotfix

- `/admin/reports` više ne vraća generički HTTP 500 kada nedostaje reports tabela, kolona ili delimično primenjena migracija;
- dodat je schema readiness pregled za `orders`, `order_items`, `order_commissions`, `order_documents`, `users` i `roles`;
- stranica prikazuje kontrolisano upozorenje i nulte vrednosti dok se šema ne popravi;
- CSV i PDF export vraćaju kontrolisani HTTP 503 sa repair instrukcijom umesto neobrađene greške;
- summary SQL je prebačen na eksplicitne `joinSub` upite radi stabilnosti na MySQL/MariaDB;
- dodata je komanda `php artisan app:reports-doctor --repair` koja pokreće migracije, seeder i stvarne reports upite;
- dodata je nedestruktivna recovery migracija `2026_07_23_000010_repair_reports_schema_beta1_2.php`;
- `000009` i `CoreAccessSeeder` više ne zavise od fiksnih permission ID vrednosti za reports/invoices dozvole;
- dodat je feature test koji potvrđuje da nepotpuna reports šema daje kontrolisanu stranicu umesto HTTP 500.

## 2.1.0-beta1.1 — Mobile navigation alignment hotfix

- direktne stavke mobilnog menija sada koriste isti unutrašnji wrapper kao dropdown stavke;
- tekstovi **Početna**, **Provizije** i **Izveštaji** poravnati su ulevo uz svoje ikonice;
- aktivna pozadina, širina stavke, dropdown strelice i desktop navigacija nisu menjani;
- CSS asset koristi `filemtime` cache-busting, pa browser ne može zadržati prethodno pravilo nakon deploya;
- patch ne uvodi migraciju niti menja reports/export/fakturisanje funkcije.

## 2.1.0-beta1 — Reports, Export & Invoicing

- potvrđena beta6 osnova je označena kao `v2.0.0-beta6-stable`;
- tok poručivanja je prilagođen modelu korisnik → izabrani SuperAdministrator/Administrator;
- svaka Laravel porudžbina čuva dodeljenog primaoca i snapshot njegovog imena, e-maila, telefona i uloge;
- Administrator vidi, obrađuje, izvozi i dokumentuje samo njemu dodeljene porudžbine, dok SuperAdministrator vidi sve;
- dodati operativni izveštaji sa filterima, zbirnim pokazateljima i paginacijom;
- dodat CSV izvoz filtriranih porudžbina sa UTF-8 BOM-om i separatorom kompatibilnim sa lokalnim Excel podešavanjima;
- dodat profesionalni A4 PDF izveštaj;
- dodati PDF potvrda porudžbine, predračun i račun sa logotipom, kontaktima, poslovnim podacima, kupcem, primaocem, stavkama, PDV obračunom i ukupnim iznosom;
- dokumenti čuvaju nepromenljiv snapshot podataka i koriste transakcijski brojač `POR`, `PON` i `RAC`;
- izdavanje iste vrste dokumenta je idempotentno, a storniranje se beleži u audit log;
- dodata administracija PDF/fakturisanje podešavanja i nove dozvole `reports.view`, `reports.export`, `invoices.manage`, `invoices.view_own`;
- dodata migracija `2026_07_23_000009_create_reports_documents_and_supplier_assignment.php`;
- mobilni tekst `Provizije` poravnat je ulevo kao ostale navigacione stavke;
- dodati feature i unit testovi za supplier scope, izveštaje, CSV/PDF i poslovne dokumente.

## 2.0.0-beta6 — Post-login runtime recovery

- uklonjen je direktan `SettingsService`/DB poziv iz authenticated Blade footera koji je zaobilazio beta5 fallback i mogao oboriti dashboard odmah nakon prijave;
- header više ne pokreće nezaštićen lazy upit za korisničku ulogu; prikaz imena, inicijala i uloge koristi odbrambene `User` helper metode;
- dodat je globalni `EnsureRuntimeDirectories` middleware koji pre session/cache obrade kreira i proverava `sessions`, file cache, compiled views, log i `bootstrap/cache` direktorijume;
- neispravne hosting dozvole sada vraćaju kontrolisani HTTP 503 sa repair instrukcijom umesto generičkog HTTP 500;
- Full i Upgrade paket sada čuvaju obavezne runtime direktorijume kroz `.gitignore` placeholdere, bez uključivanja stvarnih log/session/cache/view podataka;
- `CACHE_LIMITER=file` je eksplicitno definisan kako login throttle ne bi zavisio od slučajne promene opšteg cache store-a;
- login tok hvata Turnstile, Laravel user lookup, session establishment i remember-token probleme; validna prijava bez dostupnog `remember_token` nastavlja se bez persistent cookie-ja;
- logging u login/dashboard recovery toku je best-effort i ne može ponovo izazvati HTTP 500;
- dodata je repair migracija `2026_07_23_000008_repair_authenticated_runtime_beta6.php` za core login/settings/access tabele i kolone;
- `app:deployment-check --repair` sada popravlja runtime direktorijume, proverava stvarni upis i kompajlira Blade view cache;
- `app:auth-doctor LOGIN --render-dashboard` renderuje isti dashboard i kompletan authenticated layout i prikazuje tačnu exception klasu/poruku;
- dodati su feature testovi za dashboard bez settings/roles tabela i remember login bez `remember_token` kolone;
- package static check dozvoljava samo `.gitignore` placeholdere u runtime direktorijumima i i dalje odbija stvarne runtime podatke.

## 2.0.0-beta5 — Login 500 recovery & deployment check split

- ispravljen je 500 odgovor odmah nakon prijave kada produkciona šema nije potpuno migrirana;
- upis `last_login_at` i automatski password rehash sada su best-effort operacije: uspešna autentikacija se više ne obara zbog neobavezne telemetry kolone;
- dashboard proverava postojanje potrebnih tabela i kolona, izoluje svaki statistički upit i koristi bezbedne nulte vrednosti umesto HTTP 500;
- dashboard više ne zavisi od implicitno prosleđene promenljive kursa iz layout view composer-a;
- korisničke role/dozvole imaju odbrambeni fallback kada su permission tabele privremeno nedostupne;
- migracije `000005` i `000006` postale su idempotentne za oporavak delimično primenjenog upgrade-a;
- dodata je nepovratna repair migracija `2026_07_23_000007_repair_production_schema_beta5.php` koja dopunjava nedostajuće operativne tabele, kolone i indekse bez brisanja podataka;
- deployment check sada proverava i ključne kolone, ne samo nazive tabela, a seeder se pokušava nezavisno čak i kada migracija prijavi grešku;
- `bin/static-check.php` razdvaja produkcioni runtime režim od sanitized ZIP provere: `.env`, logovi, sesije i Turnstile secret više nisu lažni FAIL na živom serveru;
- ZIP hygiene provere se pokreću isključivo sa `php bin/static-check.php --package` nad raspakovanim sanitized paketom;
- dodati su feature testovi za dashboard bez operativnih tabela i login bez `last_login_at` kolone.

## 2.0.0-beta4 — Legacy dashboard parity & deployment repair

- početna stranica je vizuelno usklađena sa potvrđenim legacy PHP dashboardom: gradijentni hero, verzijski badge, četiri obojene akcije, kurs kartica i 12 obojenih KPI kartica;
- desktop header koristi legacy dvoredni raspored sa brandingom i quick actions u prvom redu, a ikonama, dropdown navigacijom i odjavom u drugom;
- mobilni header zadržava logo, kurs, temu, avatar i hamburger, dok se kompletna navigacija otvara kao vertikalni drawer bez horizontalnog skrola;
- dodat je Auto/Tamna/Svetla režim teme sa praćenjem sistemske teme;
- dashboard statistike pokrivaju statuse porudžbina, vrednost bez otkazanih, ukupan/aktivan/nizak/nulti lager i statuse korisnika;
- `app:deployment-check --repair` pokreće migracije i `CoreAccessSeeder`;
- legacy zaštita se proverava kroz read-only session i aktivan pre-query SQL guard, uz opcionu strogu proveru grantova;
- dodati su reusable SVG icon component, dashboard feature testovi i proširene statičke regresione provere.

## 2.0.0-beta3 — Catalog detail 500 hotfix

- javni i API detalj artikla više ne zavise od implicitnog `{product:slug}` model binding toka;
- vidljivost artikla, status i korisnički kategorijski pristup proveravaju se u jednom eksplicitnom slug upitu;
- nepoznat ili nedostupan slug vraća kontrolisani HTTP 404 umesto neobrađene greške;
- URL-ovi legacy i novih slika generišu se odbrambeno, pa jedan neispravan zapis slike više ne može oboriti celu stranicu artikla;
- detalj artikla prikazuje samo slike sa validnim URL-om i pada na „Bez slike“ kada validna slika ne postoji;
- linkovi iz kataloga eksplicitno prosleđuju slug;
- dodati feature testovi za tri prijavljena slug obrasca, legacy sliku, neispravan zapis slike i nepoznat slug;
- beta3 ne uvodi novu migraciju baze; obavezno je `php artisan optimize:clear` zbog izmene ruta i Blade view-a.

## 2.0.0-beta2 — Responsive navigation & compact admin forms

- mobilna navigacija više nema horizontalni scroll; pri širini do 1250 px koristi pristupačan hamburger meni;
- hamburger meni podržava zatvaranje klikom na stavku, klikom van menija, tasterom Escape i promenom širine prozora;
- dropdown stavke Artikli, Korisnici i Podešavanja prikazuju se unutar mobilnog menija bez izlaska iz viewporta;
- forme za dodavanje korisnika više se ne rastežu do visine liste postojećih korisnika;
- admin gridovi i forme artikala koriste sadržajnu visinu, poravnanje na vrh i stabilnu visinu input/select polja;
- poboljšan je mobilni raspored header akcija, korisničkog profila, kursa i dugmeta za odjavu;
- dodate statičke regresione provere za hamburger markup, JavaScript kontrolu i compact-form CSS pravila;
- pripremljeni Full i Upgrade beta1 → beta2 paketi sa SHA-256 manifestima.

## 2.0.0-beta1 — Production Orders & Inventory

- Laravel baza postaje produkcioni izvor za sve nove porudžbine i stanje lagera;
- uvedeno transakcijsko kreiranje porudžbine sa `SELECT ... FOR UPDATE`, proverom kompletnog lagera i all-or-nothing upisom;
- uvedena idempotency zaštita za web/API porudžbine i ručne korekcije lagera;
- svaka stavka čuva snapshot SKU-a, naziva, cene, valute i provizije, dok porudžbina čuva EUR/RSD kurs;
- lager se umanjuje jednom pri kreiranju, a pri otkazivanju vraća tačno jednom kroz jedinstveni `event_key`;
- legacy porudžbine su označene kao istorijski read-only zapisi i ne mogu menjati Laravel lager;
- dodate korisničke i administratorske web/API rute za porudžbine, statuse, plaćanje, tracking i otkazivanje;
- dodata ručna korekcija lagera sa obaveznom napomenom, dozvolom i audit zapisom;
- dodate dozvole `orders.cancel_own` i `stock.adjust`;
- legacy konekcija dobija MySQL `READ ONLY` session režim, pre-query SQL guard i proveru grantova;
- Redis je potpuno isključen iz database/cache/queue konfiguracije; koriste se file session/cache i sync queue;
- dodati feature testovi za transakcije, nedovoljan lager, idempotency, jednokratni povrat, legacy zaštitu i korekcije lagera;
- dodate PHP lint i Composer PSR-4 autoload provere;
- pripremljeni Full i Upgrade paketi sa SHA-256 manifestima.

## 2.0.0-alpha3.1

- ispravljeno prosleđivanje branding promenljivih svim `auth.*` Blade view-ovima, uključujući login i reset lozinke;
- `ExchangeRateHistory` model eksplicitno koristi postojeću tabelu `exchange_rate_history`;
- usklađena runtime verzija u `config/app.php` sa paketom;
- statičke provere sada otkrivaju regresije u auth view composer-u, mapiranju kursne istorije i runtime verziji.

## 2.0.0-alpha3

- uveden prošireni `legacy:import` sa scope opcijama `access`, `catalog`, `settings`, `operations`, `remaining` i `all`;
- dodat bezbedan dry-run i očuvanje postojećih Laravel korisnika i lokalnih kataloških izmena;
- dodata migracija za žiro račune, porudžbine, stavke, status istoriju, provizije, IPS QR, promene lagera, istoriju kursa i odvojeni legacy audit log;
- dodata administracija izgleda sajta, light/dark logotipa, favicona i footera;
- dodat ručni i automatski EUR/RSD kurs sa istorijom i dnevnim scheduler zadatkom;
- dodato upravljanje žiro računima sa normalizacijom i MOD 97 proverom;
- dodato upravljanje korisnicima, statusima, ulogama, grupama, dozvolama i kategorijskim pristupom;
- dodat read-only pregled svih porudžbina, detalja porudžbine, provizija i promena lagera;
- dodata stranica naloga i bezbedna promena lozinke uz opoziv tokena;
- obnovljen glavni meni, dinamički branding, footer i EUR/RSD indikator u zaglavlju;
- legacy slike artikala sada se serviraju kroz autorizovanu Laravel media rutu iz konfigurisanog lokalnog `LEGACY_MEDIA_ROOT` foldera;
- Settings servis koristi cache sa bezbednim podrazumevanim vrednostima;
- dopunjena Redis konfiguracija za phpredis, odvojeni application/cache DB i persistent konekcije;
- deployment check proširen na nove tabele i dozvole;
- dodati Unit i Feature testovi za MOD 97, izgled, kurs i žiro račun;
- ispravljen importer settings grane i QR kolona usklađena sa legacy `MEDIUMBLOB` tipom na MySQL/MariaDB;
- SVG upload logotipa nije dozvoljen; podržani su PNG, JPEG i WebP.

## 2.0.0-alpha2.2.1

- ispravljen fatalni konflikt pomoćne metode `AuthDoctorCommand::fail()` sa Laravel Console klasom;
- statička provera odbija istu regresiju u budućnosti.

## 2.0.0-alpha2.2

- uklonjeni tehnički/staging detalji sa login stranice;
- dodat case-insensitive login resolver, auth/mail/cache dijagnostika i kontrolisani oporavak legacy korisnika;
- pripremljeni Redis i Memcached store-ovi.

## 2.0.0-alpha2.1

- ispravljena prijava uvezenih korisnika;
- dodat reset zaboravljene lozinke i CLI reset.

## 2.0.0-alpha2

- uveden CRUD artikala, automatski SKU, slike, šifarnici, audit log i kontrolisana kataloška sinhronizacija.

## 2.0.0-alpha1

- Laravel osnova, prijava, read-only katalog, Sanctum API i početni legacy importer.
