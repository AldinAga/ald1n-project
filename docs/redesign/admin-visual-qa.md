# Vizuelna provera administracije (Faza 0)

Autor: Ald1n <https://ald1n.com>

Pre nego što prepravimo bilo koji admin modul, imamo alat koji prikazuje prave admin stranice i čuva snimke današnjeg izgleda. Svaki sledeći modul tako može da pokaže „pre i posle“ i da se proveri na kontrast i prelom rasporeda.

## Šta alat pokriva

Workflow `admin-visual-qa.yml` podiže jednokratni Laravel sa praznom MariaDB bazom i veštačkim podacima iz `tests/Browser/seed-admin-visual-qa.php`. Prijavljuje se kao seedovani superadmin i otvara po jednu reprezentativnu stranicu svakog modula, redom kojim ćemo ih prepravljati:

- zajednički raspored i navigacija: obična stranica, otvoren padajući meni, otvorena globalna pretraga, mobilni meni;
- kontrolna tabla;
- porudžbine: lista, jedna porudžbina, nova porudžbina (nju otvara seedovani kupac, jer superadmin nema pravo `orders.create`);
- artikli: lista (`/catalog`, jer `/admin/catalog` preusmerava tamo), forma artikla, napredni lager, šifarnik;
- garancije: lista i jedna garancija;
- korisnici: lista, grupe pristupa, portal kupaca;
- izveštaji: upravljački izveštaj, potraživanja, provizije;
- podešavanja: pregled, izgled, javna prodavnica, SMTP, dokumenta;
- prijava i zaboravljena lozinka (kao gost);
- jedna 404 stranica. Ona je trenutno uvek tamna (`errors/minimal.blade.php` ima fiksan `data-theme="dark"`), pa se to beleži kao nalaz `theme-not-applied`, a ne kao greška infrastrukture.

Svaka stranica se prikazuje u Chromium-u i WebKit-u, na 375, 430, 768 i 1280 piksela, u svetloj i tamnoj temi. Tema se postavlja istim putem kao kod korisnika: ključ `ald1n-theme-mode` u `localStorage`, koji `layouts/app.blade.php` čita u zaglavlju. Ako tema ne dobije traženu vrednost, ćelija se računa kao greška infrastrukture.

Po ćeliji (stranica, pregledač, širina, tema) beležimo:

- PNG snimak cele stranice;
- da li dokument skroluje vodoravno;
- konzolne greške i neuspele zahteve ka istom izvoru;
- axe-core: `color-contrast`, `document-title`, `html-has-lang`, `label`, `button-name`, `link-name`, `image-alt`.

Dugme mobilnog menija danas postoji na svim širinama, pa se meni otvara i na 1280 px. Ako se to promeni, ćelija bez dugmeta se preskače i tako je označena u JSON-u.

Alat sam sebe proverava jednom po pokretanju: u stranicu ubacuje element širok 3000 px i traži da detektor preliva to uhvati, inače je to greška infrastrukture. Uz svaki snimak čuvamo i vidljivi tekst (`text/`), da se razlika između dva pokretanja lako nađe.

## Stabilnost snimaka

Animacije, prelazi i kursor su isključeni, a pre snimka čekamo fontove i mirnu mrežu. Datumi, vremena i izrazi „pre N minuta“ u tekstu zamenjuju se fiksnim vrednostima, a podaci iz seed skripte imaju fiksne datume. Na snimku su maskirani (siva ploča): kurs EUR/RSD u zaglavlju i na kontrolnoj tabli, bedž kursa, `canvas` elementi i blokovi grafikona (`[class*="chart-"]`), jer zavise od kursa i od „danas“. Axe i provera preliva rade pre zamene teksta, na pravom sadržaju. Kontrolna tabla se u istom prolazu snima dva puta i upoređuje, a rezultat piše u rezimeu.

Chromium i WebKit rade istovremeno u istom poslu (dva Node procesa, isti server), da ceo skup ostane ispod ~25 minuta.

## Kako se čita baseline

Posao ne pada zbog kontrasta ni preliva. Sve nalaze upisuje u `admin-visual-baseline.json` (artefakt, čuva se 30 dana). Svaki nalaz ima polja `module`, `page`, `browser`, `width`, `theme`, `rule`, `count` i `selectors` (do tri primera). Pravilo može biti axe pravilo, `horizontal-overflow`, `console-error` ili `failed-request`.

Ljudski pregled je u sažetku posla (`admin-visual-summary.md`):

- tabela po modulu: stranice, ćelije, čvorovi sa lošim kontrastom, stranice sa prelomom, strukturne greške, konzola i zahtevi;
- kontrast po temi i širini;
- preliv po temi i širini;
- najčešća pravila.

Ove brojke su naše „pre“ stanje. Posao pada samo kad nešto ne radi: stranica se nije učitala, prijava nije uspela, server je vratio 5xx, stranica nas je preusmerila na drugu adresu, tema nije primenjena ili snimak nedostaje.

## Poređenje pre i posle za jedan modul

1. Preuzmi artefakte `admin-visual-baseline-<sha>` i `admin-visual-screenshots-<sha>` sa poslednjeg zelenog pokretanja na `main`.
2. Posle prepravke modula preuzmi iste artefakte sa PR-a.
3. Snimci imaju ime `modul__stranica__pregledač__širina__tema.png`, pa se par „pre/posle“ nalazi po imenu.
4. U JSON-u uporedi nalaze istog `module`: brojke moraju da padnu na nulu za kontrast i preliv.

## Modul 3: porudžbine (strogo)

Modul `orders` je od sada u strogom režimu, uz `shell` i `dashboard`: `ADMIN_VISUAL_STRICT_MODULES: 'shell,dashboard,orders'`. Svaki nalaz u njegovim ćelijama obara posao.

Šta pokrivaju ćelije (sve imaju `module: orders`):

- admin lista porudžbina: osnovna, sa filterima i pretragom, prazna (`?q=nema-takvog-broja-vizuelni-qa`), druga strana (`?page=2`), po jedna za svaki status (`processing`, `confirmed`, `shipped`, `completed`, `cancelled`; `new` je u filtriranoj ćeliji), filter pažnje (`?attention=unaccepted`) i arhiva (`/admin/orders/archived`);
- admin detalj: osnovna porudžbina, plaćena, delimično plaćena, poslata, otkazana, sa 15 stavki, sa imenom od tačno 60 znakova i sa iznosom 12.345.678,90 RSD. Arhivirana porudžbina nema stranicu detalja (404 je namerno), zato je pokrivena arhivom;
- kupac: moje porudžbine, detalj porudžbine, nova porudžbina i korpa (korpa se proverava prazna, jer alat ne može da napuni sesiju);
- reklamacije: lista i detalj kao kupac, lista i detalj kao admin;
- terenski rad: lista naloga, jedan nalog, ekipe;
- servisni delovi: delovi, nabavke i jedna nabavka.

Širine po ćeliji: glavne stranice (admin lista, admin detalj, nova porudžbina, moje porudžbine) imaju svih sedam širina `360, 375, 430, 768, 1200, 1280, 1440`. Stranice sa stresnim podacima (kupac detalj, filtrirana, prazna, druga strana, arhiva, 15 stavki, 60 znakova, veliki iznos) imaju `360, 430, 768, 1280`. Detalji plaćena, delimično plaćena, poslata i otkazana imaju `360, 768, 1280`. Status liste, filter pažnje, korpa, reklamacije, terenski rad i servisni delovi imaju `360, 1280`. Ukupno 212 ćelija po pregledaču (oko 15 minuta više nego pre ovog modula); posao ima limit od 45 minuta, pa se pri dodavanju stranica prvo skraćuje spisak širina sekundarnih stranica.

Dodatne provere samo za `orders`, povrh onih koje svi moduli već imaju (preliv, konzola, zahtevi, axe iz tabele gore):

- axe: još `select-name`, `aria-valid-attr-value`, `aria-required-attr`, `tabindex`, `scrollable-region-focusable`, `nested-interactive`;
- izračunati kontrast u stranici (`computed-contrast`) za sadržaj unutar `.page-shell`;
- `unlabeled-control`: vidljiv link, dugme, `summary`, polje ili izbor bez pristupačnog imena;
- `orders-structure`: tačno jedan `h1` na stranici.

Seed (`seed-admin-visual-qa.php`) dodaje 45 operativnih porudžbina (lista se deli na dve strane, 40 po strani) i 6 arhiviranih. Svako stanje je kombinacija koju aplikacija stvarno ume da napravi: iznos plaćen u `paid_total_rsd` odgovara proverenim uplatama, otkazana porudžbina ima vraćen lager, a arhivirati se mogu samo završene ili otkazane (zato je arhivirana porudžbina otkazana). Uz to: jedna uplata na čekanju, predračun i interna napomena na osnovnoj porudžbini, pošiljka sa brojem za praćenje, jedna reklamacija koju je otvorio kupac sa tri poruke, jedan terenski nalog sa ekipom i jedan servisni deo sa nabavkom. ID-jevi u fixture fajlu: `order`, `order_paid`, `order_partly_paid`, `order_shipped`, `order_completed`, `order_cancelled`, `order_archived`, `order_items15`, `order_long_name`, `order_long_amount`, `case`, `work_order`, `team`, `part`, `purchase_request`.

Kako kontrolna tabla ostaje ista: „poslednje porudžbine“ su sedam operativnih sa najnovijim `updated_at`, pa osnovna porudžbina ostaje prva, iza nje idu obične porudžbine sa kratkim imenima, a sve stresne su starije. Porudžbina sa 12.345.678,90 RSD je otkazana, jer kontrolna tabla ne računa otkazane u vrednost i otvoreni saldo. Završena porudžbina ima `completed_at` 20.10.2025, van svih perioda koje kontrolna tabla prikazuje, a rok reklamacije i termin terenskog naloga su 2031, pa nema „preko roka“ stavki.

## Uključivanje strogog režima

Kad je modul prepravljen, njegovi nalazi moraju da budu nula. U `admin-visual-qa.yml`, u bloku `env` posla, dodaj ga u listu (danas: `shell,dashboard,orders`):

```yaml
ADMIN_VISUAL_STRICT_MODULES: 'shell,dashboard,orders'
```

Više modula razdvajamo zarezom (`orders,reports`). Svaki nalaz u tim modulima tada obara posao, a ostali moduli ostaju samo u baseline-u. Kad su svi moduli gotovi, umesto liste postavljamo `ADMIN_VISUAL_STRICT: '1'`. Imena modula: `shell`, `dashboard`, `orders`, `products`, `warranties`, `customers`, `reports`, `settings`, `auth`, `error`.

## Šta ne pokrivamo

Samo čitamo stranice, ništa ne šaljemo. Ne proveravamo stanja sa greškama u formama, modalne prozore unutar modula ni PDF izlaze. Mobilne aplikacije nisu deo ove provere.

Alat koristi `bypassCSP: true`, jer je to potrebno da se ubaci axe-core. Zato konzolne greške izazvane CSP-om ovaj prolaz ne hvata. Strogi režim za modul `error` ne može da prođe dok taj prikaz ne primeni temu: njegova svetla ćelija uvek beleži `theme-not-applied`.
