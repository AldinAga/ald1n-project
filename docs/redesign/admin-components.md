# Admin komponente (`ald1n-admin.css`)

Autor: Ald1n <https://ald1n.com>

Ovo je sloj komponenti za admin CMS. Gradi se samo na Kobalt tokenima (`ald1n-tokens.css`, promenljive `--a1-*`) i prati izgled javne prodavnice: isto staklo, isti radijusi, ista tipografija, isti fokus. Admin i prodavnica treba da izgledaju kao jedan proizvod.

Ova isporuka ne menja ništa što produkcija prikazuje. CSS nije povezan ni sa jednom stranicom.

## Fajlovi

- `apps/cms/current/public/assets/css/ald1n-admin.css`: komponente, jedan fajl, ručno pisan, bez build koraka.
- `apps/cms/current/tests/Browser/fixtures/admin-components.html`: galerija sa svim komponentama i tri kompozicije (kontrolna tabla, spisak porudžbina, detalj porudžbine). Nije pod `public/` i nema rutu.
- `apps/cms/current/tests/Browser/admin-components-browser.cjs`: testovi galerije.
- `.github/workflows/admin-components-qa.yml`: CI (bez deploya).

Redosled učitavanja na migriranoj stranici: prvo `ald1n-tokens.css`, zatim `ald1n-admin.css`. Na `<body>` ide klasa `a1-app`. Na `<html>` ide `data-theme="light|dark|system"`.

## Pravila

1. Jedna glavna radnja po ekranu. Samo jedno dugme ima `a1-btn--primary`. Ostalo je `a1-btn` ili `a1-btn--quiet`.
2. Status je uvek tačka i tekst (`a1-pill`). Boja sama nikad ne nosi značenje. Isto važi za strelice u KPI kartici (strelica plus broj) i za grešku u obrascu (ikona plus tekst).
3. Prvo čitljivost. Tekst stoji na gustoj podlozi. Bez `backdrop-filter`, uz smanjenu providnost, veći kontrast ili forsirane boje, površine postaju pune.
4. Admin nema dugme „Nazad na vrh“. Javni delo ga ima, admin ne.
5. Tema se menja ikonicama (monitor, sunce, mesec), bez teksta. Svako dugme ima `aria-label`.
6. Svaka klasa počinje sa `a1-`. Nijedan goli element nije stilizovan, pa fajl može da stoji pored starog admin CSS-a dok moduli prelaze jedan po jedan.
7. Najmanja meta za dodir je 44 px. Fokus se uvek vidi. Hover ne pomera raspored.
8. Boje, radijusi, senke, blur i trajanja dolaze iz tokena. Izuzeci su navedeni na kraju.

## Pragovi (breakpoints)

560, 768, 1100 i 1440 px. Prodavnica koristi 560, 720, 860, 920, 1100 i 1180. Admin koristi 768 jer je tako traženo za mobilni meni. Zaglavlje je u jednom redu tek od 1440 px, ispod toga navigacija ide u drugi red.

## Komponente i HTML ugovor

### Okvir aplikacije

| Klasa | Šta je |
| --- | --- |
| `a1-app` | `<body>`: pozadina sa sjajevima, tekst, font |
| `a1-skip` | veza „Preskoči na sadržaj“, prvi element u `<body>` |
| `a1-wrap`, `a1-main` | širina sadržaja i `<main>` |
| `a1-header`, `a1-header__bar` | lepljivo stakleno zaglavlje |
| `a1-brand`, `a1-brand__mark`, `a1-brand__text` | marka |
| `a1-header__search` | forma pretrage u zaglavlju |
| `a1-header__quick` | korpa i obaveštenja (uvek vidljivi) |
| `a1-menu-cb`, `a1-menu-btn` | mobilni meni |
| `a1-header__panel`, `a1-header__nav`, `a1-nav`, `a1-nav__link` | glavna navigacija |
| `a1-nav-dd`, `a1-nav-dd__panel`, `a1-nav-dd__item`, `a1-nav-dd__sep` | padajući meni |
| `a1-rate`, `a1-theme`, `a1-theme__opt`, `a1-user`, `a1-user__avatar` | kurs, izbor teme, korisnik |

Ugovor za zaglavlje:

- `<header class="a1-header">` sadrži `<div class="a1-wrap a1-header__bar">`.
- Unutar trake, redom: `a1-brand` (veza sa `aria-label`), `<form role="search" class="a1-header__search">` sa skrivenim `<label>`, dugmad `a1-btn a1-btn--icon a1-btn--sm a1-header__quick` sa `aria-label`, zatim `<input type="checkbox" class="a1-menu-cb" aria-label="Meni">` i odmah iza njega `<label class="a1-menu-btn" for>`, pa `<div class="a1-header__panel">`.
- Mobilni meni radi bez JavaScripta: ako je potvrdni okvir štikliran, panel se otvara kao ploča ispod trake. Okvir i panel moraju biti braća u istom roditelju.
- Panel sadrži `<nav class="a1-header__nav" aria-label>` sa `<ul class="a1-nav">`, zatim `a1-rate`, `a1-theme` i korisnički meni. Od 769 px panel se rastvara u traku (`display: contents`).
- Padajući meni je `<details class="a1-nav-dd" name="grupa">` sa `<summary class="a1-nav__link">` i `<div class="a1-nav-dd__panel">` sa vezama `a1-nav-dd__item`. Atribut `name` daje da je otvoren najviše jedan meni. Radi bez JavaScripta i sa tastature (Enter ili razmak na `summary`). Zatvaranje tasterom Escape traži malu skriptu i nije deo ove isporuke.
- Za meni uz desnu ivicu dodaj `a1-nav-dd--end`.
- Aktivna stavka nosi `aria-current="page"`.
- Izbor teme: `<div class="a1-theme" role="group" aria-label="Tema">` sa tri `<button class="a1-theme__opt" data-theme="system|light|dark" aria-pressed aria-label>`, svako sa ikonicom i bez teksta.

### Zaglavlje stranice, podnavigacija, traka radnji

| Klasa | Šta je |
| --- | --- |
| `a1-pagehead`, `a1-pagehead__text`, `a1-pagehead__actions` | naslov, putanja i radnje |
| `a1-eyebrow`, `a1-title`, `a1-lead` | nadnaslov, `<h1>`, uvodni tekst |
| `a1-crumbs` | putanja: `<nav aria-label="Putanja"><ol class="a1-crumbs">`, poslednja stavka je tekst sa `aria-current="page"` |
| `a1-tabs`, `a1-tabs__link` | podnavigacija: `<nav aria-label><ul class="a1-tabs">`, veze `a1-tabs__link`, aktivna sa `aria-current="page"` |
| `a1-actionbar`, `a1-actionbar__status`, `a1-actionbar__buttons` | lepljiva traka na dnu dugih obrazaca |

Svaka stranica ima tačno jedan `<h1 class="a1-title">` i tačno jedno primarno dugme.

### Dugmad i veze

| Klasa | Šta je |
| --- | --- |
| `a1-btn` | sekundarno dugme (podrazumevano) |
| `a1-btn--primary`, `--quiet`, `--danger` | varijante |
| `a1-btn--sm` | 44 px visine |
| `a1-btn--icon` | samo ikonica, obavezan `aria-label` |
| `a1-btn--block` | pun red |
| `a1-count`, `a1-count--float` | brojač, plutajući na uglu dugmeta |
| `a1-link`, `a1-link--inline`, `a1-link--quiet` | veze |

Stanja: `:hover`, `:focus-visible`, `:active`, `disabled` ili `aria-disabled="true"`, `aria-busy="true"` (vrteška, tekst ostaje u DOM-u radi čitača). Opasna radnja ima tamno obojenu pozadinu, ne punu crvenu, dok ne dobijemo token (vidi dole).

`a1-link--inline` je jedino mesto gde meta za dodir može da bude manja od 44 px: veza usred rečenice.

### Obrasci

| Klasa | Šta je |
| --- | --- |
| `a1-form`, `a1-row` | razmak i red sa više polja |
| `a1-fieldset`, `a1-legend` | grupa polja |
| `a1-field`, `a1-field--inline` | polje: oznaka, kontrola, pomoć, greška |
| `a1-label`, `a1-req`, `a1-help`, `a1-error` | oznaka, zvezdica, pomoć, greška |
| `a1-input`, `a1-select`, `a1-textarea` | kontrole (tekst, e-pošta, datum, pretraga, broj) |
| `a1-search` | polje pretrage sa ikonicom |
| `a1-group`, `a1-group__unit` | polje sa jedinicom (RSD, kg) |
| `a1-check`, `a1-check__input`, `a1-check__box`, `a1-check__box--radio` | polje za potvrdu i radio dugme |
| `a1-switch__track` | prekidač (`<input type="checkbox" role="switch" class="a1-check__input">`) |
| `a1-drop`, `a1-drop__input`, `a1-drop__title` | zona za otpremanje fajlova |

Ugovor za polje:

- `<label class="a1-label" for="id">` je obavezan.
- Zvezdica je `<span class="a1-req" aria-hidden="true">*</span>`, a kontrola nosi atribut `required`. Iznad obrasca stoji rečenica da su polja sa zvezdicom obavezna.
- Pomoć je `<p class="a1-help" id="id-help">`, greška je `<p class="a1-error" id="id-err">` sa ikonicom i tekstom. Kontrola ima `aria-describedby="id-help id-err"`.
- Neispravno polje nosi `aria-invalid="true"`. Boja ivice je dopuna, poruka je glavna stvar.
- Potvrdno polje: `<label class="a1-check"><input class="a1-check__input" type="checkbox"><span class="a1-check__box" aria-hidden="true"></span><span>Tekst</span></label>`. Pravi `<input>` je providan i pokriva celu oznaku, pa je meta velika. Nacrtani kvadrat je odmah iza njega i nosi prsten fokusa. Zato `<input>` i `.a1-check__box` moraju biti susedi.
- Zona za otpremanje: `<label class="a1-drop">` koji sadrži `<input type="file" class="a1-drop__input">`.
- Raspored u jednom redu je `a1-field--inline` (od 560 px).

### Podaci

| Klasa | Šta je |
| --- | --- |
| `a1-tablewrap` | okvir tabele: `role="region"`, `aria-label`, `tabindex="0"` |
| `a1-tablewrap--sticky` | lepljivo zaglavlje kolona unutar okvira (od 769 px) |
| `a1-table`, `a1-table--stack` | tabela, i varijanta sa redovima jedan ispod drugog |
| `a1-table__num`, `__select`, `__actions`, `__id`, `__sub` | brojevi, izbor reda, radnje, broj dokumenta, sporedni red |
| `a1-pager`, `a1-pager__status`, `a1-pager__list`, `a1-page` | straničenje |
| `a1-filters`, `a1-filters__fields`, `a1-filters__actions` | traka filtera |
| `a1-results`, `a1-results__count`, `a1-chips`, `a1-chip` | broj rezultata i aktivni filteri |
| `a1-empty`, `a1-empty__icon` | prazno stanje |
| `a1-skeleton`, `--title`, `--block`, `a1-skeleton-list` | učitavanje |

Tabela na telefonu. Prodavnica na telefonu slaže redove korpe jedan ispod drugog, pa to radimo i mi za tabele sa pet ili manje kolona (`a1-table--stack`, do 560 px). Svaka ćelija nosi `data-label` sa imenom kolone. Pošto pregledači tada gube semantiku tabele, tabela dobija eksplicitne uloge: `role="table"`, `rowgroup`, `row`, `columnheader`, `cell`. Tabele sa mnogo kolona (zalihe, izveštaji) ostaju obična `a1-table`: skroluje se samo njen okvir `a1-tablewrap`, ne cela stranica. Okvir mora imati `tabindex="0"` da bi se skrol radio tastaturom.

Brojčane kolone: `th` i `td` sa `a1-table__num`, cifre su `tabular-nums`. Izbor reda je `a1-table__select` sa potvrdnim poljem i `aria-label` koji sadrži broj dokumenta. Izabran red se oboji sam. Radnje u redu su `a1-btn--icon a1-btn--sm` sa `aria-label` koji sadrži broj dokumenta.

Čip filtera je veza koja uklanja filter: `<a class="a1-chip" aria-label="Ukloni filter: ...">`. Visok je 44 px.

### Statusi

| Klasa | Šta je |
| --- | --- |
| `a1-pill` | siva, bez statusa |
| `a1-pill--ok`, `--info`, `--warning`, `--danger` | statusi |
| `a1-pill--off` | prazna (šuplja) tačka |

Ugovor: `<span class="a1-pill a1-pill--ok">Plaćeno</span>`. Tekst je obavezan. Tačku crta CSS.

Preslikavanje na primer porudžbina: Plaćeno je `ok`, Poslato je `info`, U obradi je `warning`, Otkazano je `danger`, Čeka uplatu je `off`.

### Površine

| Klasa | Šta je |
| --- | --- |
| `a1-card`, `a1-card--flush`, `a1-card__head`, `a1-card__head--pad`, `a1-card__body` | kartica |
| `a1-kpi`, `a1-kpi__label`, `a1-kpi__value`, `a1-kpi__delta` (`--up`, `--down`) | KPI kartica |
| `a1-dl`, `a1-dl__row` | lista pojmova: `<dl>` sa `div.a1-dl__row` oko svakog para `dt` i `dd` |
| `a1-acc`, `a1-acc__body` | harmonika na `<details>` |
| `a1-grid`, `a1-grid--kpi`, `a1-grid--2`, `a1-split` | rasporedi |
| `a1-alert`, `--ok`, `--warning`, `--danger` | poruka u stranici |
| `a1-toasts`, `a1-toasts--static` | oblast za brza obaveštenja |
| `a1-dialog`, `__head`, `__body`, `__foot` | prozor |

Poruka: ikonica plus tekst. Naslov je `a1-alert__title`. Hitne greške imaju `role="alert"`, ostale `role="status"`.

Toast oblast je `<div class="a1-toasts" role="status" aria-live="polite">`. Svaki toast je `a1-alert` sa dugmetom za zatvaranje.

Prozor je izvorni `<dialog class="a1-dialog" aria-labelledby>` sa `<form method="dialog">`. Otvara se sa `showModal()` ili sa `command="show-modal" commandfor="id"` na dugmetu. Fokus ulazi u prozor, Escape ga zatvara. Ne koristimo `show()`: nemodalni prozor se crta u toku stranice.

Prozor za potvrdu opasne radnje ima dva dugmeta: neutralno („Ne, zadrži“) i `a1-btn--danger` sa glagolom radnje.

Tooltip nismo pravili. Nijedan ekran ga ne traži, a ikonice imaju `aria-label`.

### Grafikoni

- Stubići: `<ol class="a1-bars" aria-label>` sa `<li>` po stubiću, u njemu `.a1-bars__area` sa `.a1-bars__value` i `.a1-bars__bar style="--a1c-value:42"`, zatim natpis. Vrednost je broj od 0 do 100. Istaknut stubić je `aria-current="true"`. Ovde koristimo `style`, što admin CSP dozvoljava. Vrednost se uvek ispisuje i kao broj.
- Traka napretka: izvorni `<progress class="a1-progress" max value>`. Bez `style`.
- Krug: SVG sa `class="a1-donut"`, dva kruga, `pathLength="100"` i `stroke-dasharray="72 28"`. Bez `style`, samo atributi. Natpis je `role="img"` sa `aria-label`.
- `a1-breakdown`: red sa pilulom, trakom i brojem. `a1-keylist`: legenda.

Gde god je moglo, vrednost ide kroz atribut. Samo stubićima treba `style`, jer atribut ne može da određuje visinu u CSS-u bez `attr()` sa tipom, koji još nije svuda podržan.

### Pomoćne klase

`a1-sr` (samo za čitače), `a1-stack`, `a1-cluster`, `a1-gap-4|12|24|40`, `a1-end`, `a1-num`, `a1-muted`, `a1-text`, `a1-h2`, `a1-h3`, `a1-icon`.

## Rezervni slojevi

Tokeni već menjaju ispune u pune boje i gase sjajeve i blur za: nepostojanje `backdrop-filter`, `prefers-reduced-transparency`, `prefers-contrast: more` i `forced-colors: active`. Ovaj fajl dodaje samo ono što tokeni ne mogu: gasi prelive na površinama, zadebljava ivice kod većeg kontrasta, a kod forsiranih boja postavlja sistemske boje na tačke, prekidače, stubiće i polja. `prefers-reduced-motion`: prelazi i animacije su u bloku `no-preference`, pa kod smanjenog kretanja ništa ne animira, ni vrteška ni skeleton.

## Kako modul prelazi na nove komponente

1. U Blade izgledu modula dodaj `ald1n-tokens.css` i `ald1n-admin.css`, pa `a1-app` na `<body>`.
2. Zameni stare klase `a1-` klasama iz ove liste, element po element. Prati ugovore iznad.
3. Proveri tamnu i svetlu temu na 375, 768 i 1280 px, i sa tastature.
4. Obriši stari CSS koji je modul koristio. Ne slažemo nove komponente preko starih. Staro se briše čim ga nijedna stranica više ne koristi.
5. Kad je poslednji modul prešao, brišu se stari admin CSS fajlovi (pet fajlova, 482.698 bajtova danas).

## Tokeni koji nedostaju

Tokene ne menjamo u ovoj isporuci. Gde nedostaju, koristimo lokalne promenljive `--a1x-*` u vrhu fajla. Predlog je da se dodaju u `ald1n.tokens.json`.

| Šta | Danas u fajlu | Predlog tokena |
| --- | --- | --- |
| Zatamnjenje iza prozora | `--a1x-scrim` | `color.scrim` (obe teme) |
| Providnost onemogućenog | `--a1x-disabled-opacity` | `opacity.disabled` |
| Debljina ivice 1 px i 2 px | `--a1x-border-width`, `--a1x-border-width-strong` | `size.border`, `size.border-strong` |
| Širina sadržaja | `--a1x-container` | `size.container` |
| Veličina ikonice 20 px | `--a1x-icon` | `size.icon` |
| Slojevi (30, 40, 50) | `--a1x-z-header`, `-menu`, `-toast` | `z.header`, `z.menu`, `z.toast` |
| Pun crveni dugmad za opasnu radnju | koristimo `status.danger` bez pune pozadine | `button.danger.bg`, `.hover`, `.on` sa kontrastom 4,5:1 |
| Razmak 32 px | nije u skali (4, 8, 12, 16, 20, 24, 28, 40, 48, 72, 80) | `space.32` |
| Manji radijus (7 do 8 px) | koristimo `radius.sm` ili ručno 7 px za kvadrat potvrdnog polja | `radius.xs` |
| Veličina naslova stranice do 36 px | `clamp(24px, 3.4vw, 36px)` | `font.size.2xl` |
| Mere malih elemenata (potvrdno polje 24, prekidač 52 x 30, tačka 8, brojač 22, avatar 34) | ručno upisane | `size.check`, `size.switch`, `size.dot`, `size.count`, `size.avatar` |
| Visina stubića 200 i kruga 140 | ručno upisano | `size.chart` |
| Razmak 2 px i 3 px (izbor teme) | ručno upisano | `space.2`, `space.3` |

Pragovi (560, 768, 1100, 1440) ne mogu u CSS promenljive jer se `@media` ne čita iz njih. Zapisani su u zaglavlju fajla.

## Provera

`node tests/Browser/admin-components-browser.cjs` otvara galeriju u Chromiumu i WebKitu, na 360, 375, 430, 768 i 1280 px, u svetloj i tamnoj temi, u četiri prikaza. Pada na: svako kršenje axe pravila (kontrast i strukturna pravila), vodoravni skrol, kontrolu bez imena, kontrolu bez vidljivog fokusa, metu manju od 44 px i grešku u konzoli. Posebno se proveravaju otvoren mobilni meni, otvoren padajući meni i modalni prozor, kao i forsirane boje, smanjena providnost i veći kontrast (Chromium) sa punim površinama.

### Provera kontrasta (stvarna)

axe tekst na staklu i prelivima prijavljuje kao `incomplete`, ne kao kršenje, pa ga sam ne obara. Zato skripta ima sopstvenu proveru koja radi u svakom prikazu, temi i širini, i pada ispod 4,5:1 za običan tekst, odnosno 3:1 za veliki (od 24 px, ili od 18,66 px podebljano).

Implementirana je puna verzija, ne ona manja: za svaki vidljiv element sa tekstom skripta uzima izračunatu boju teksta i slaže pozadinu od korena naviše. Providne `background-color` slojeve slaže jedan preko drugog do prvog neprozirnog sloja (ili bele). Za slojeve sa `background-image` bira najgori slučaj:
- svaki stop radijalnog preliva (sjajevi na `.a1-app`) uzima se punom bojom i sopstvenim alfa kanalom;
- uspravni linearni preliv (sjaj dugmeta, sjaj zaglavlja) uzorkuje se na gornjoj ivici okvira teksta.

Poznata ograničenja, bez izuzetaka u kodu:
- nema uzorkovanja po pikselu: najgori stop radijalnog preliva važi za ceo element, što je strože od stvarnosti;
- `opacity` roditelja, `mix-blend-mode`, tekst preko slike i pozadina iza zamućenja (`backdrop-filter`) se ne računaju, jer su prelivi ispod stakla blagi;
- pozadinska slika samog polja (strelica `select`) se preskače, a tekst providne boje (zauzeto dugme) i onemogućene kontrole su izuzeti kao u WCAG-u;
- nagnuti linearni prelivi uzimaju se kao najgori stop, ne kao uzorak;
- forsirane boje i veći kontrast i dalje se proveravaju samo kao pune površine, kontrast tamo ne merimo.

### Fokus ispod lepljivih traka (WCAG 2.2, 2.4.11)

Vrednosti su visina trake plus jedna kontrola (zaglavlje do 130 px, traka radnji do 108 px, kontrola oko 50 px): Chromium kontrolu koja samo delimično zalazi u prostor padding-a smatra vidljivom i ne skroluje, što je izmereno (padding jednak visini zaglavlja, 136 px, ostavlja polje 7 px ispod trake). `html:has(.a1-app)` ima `scroll-padding-block` iz `--a1x-sticky-top` (zaglavlje, do tri reda na 360 px) i `--a1x-sticky-bottom` (traka radnji), a `.a1-tablewrap--sticky` ima `scroll-padding-top` iz `--a1x-sticky-th`. Skripta nakon svakog Tab koraka čeka kraj skrola i proverava da okvir fokusirane kontrole ne preseca zaglavlje, traku radnji ni lepljivo zaglavlje tabele. Na svaki treći korak dodatno radi probu tastaturom: Shift+Tab, pomeranje stranice tako da kontrola bude ispod zaglavlja (pa ispod trake radnji), zatim Tab nazad na kontrolu. Tab sam obično dovodi kontrolu na sredinu ekrana, pa bez ove probe provera ne bi mogla da padne (programski `focus()` nije dobra zamena, Chromium tada često ne skroluje). Izuzeci: kontrola viša od slobodnog dela ekrana (skrolabilni omotač tabele) i otvoren mobilni meni, koji nema zamku fokusa, praćeno posebno.

Proveru treba da obori ovo: nuliranje `--a1x-sticky-top` i `--a1x-sticky-bottom`; proveravano lokalno u Chromiumu na 360 px, 76 padova provere fokusa. Provera kontrasta pada kad se boja teksta `.a1-actionbar__status` privremeno zameni sa #9aa0a6 (2,51:1 naspram 4,5:1, axe to ne hvata); obe izmene su vraćene. `scroll-behavior:smooth` važi samo na stranicama sa `.a1-app`.
