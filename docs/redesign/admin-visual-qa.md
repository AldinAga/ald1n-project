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

Mobilni meni na 1280 px ne postoji (tada je navigacija raširena), pa se ta ćelija preskače i tako je označena.

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

## Uključivanje strogog režima

Kad je modul prepravljen, njegovi nalazi moraju da budu nula. U `admin-visual-qa.yml`, u bloku `env` posla, odkomentariši i popuni:

```yaml
ADMIN_VISUAL_STRICT_MODULES: 'orders'
```

Više modula razdvajamo zarezom (`orders,reports`). Svaki nalaz u tim modulima tada obara posao, a ostali moduli ostaju samo u baseline-u. Kad su svi moduli gotovi, umesto liste postavljamo `ADMIN_VISUAL_STRICT: '1'`. Imena modula: `shell`, `dashboard`, `orders`, `products`, `warranties`, `customers`, `reports`, `settings`, `auth`, `error`.

## Šta ne pokrivamo

Samo čitamo stranice, ništa ne šaljemo. Ne proveravamo stanja sa greškama u formama, modalne prozore unutar modula ni PDF izlaze. Mobilne aplikacije nisu deo ove provere.
