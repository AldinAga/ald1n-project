# Admin okvir (modul 1)

Autor: Ald1n <https://ald1n.com>

Okvir admin aplikacije (zaglavlje, glavna navigacija, mobilni meni, pretraga, teme, poruke, podnožje) radi na Kobalt tokenima i `a1-` komponentama. Sadržaj stranica i dalje koristi stari CSS dok njegov modul ne dođe na red.

## Šta je u okviru

- `layouts/app.blade.php`: jedino mesto sa markupom okvira.
- `ald1n-tokens.css` i `ald1n-admin.css` se učitavaju posle starih stilova. Blok „Module 1: admin shell“ na kraju `ald1n-admin.css` sadrži samo komponente okvira.
- Okvir nosi klasu `a1-shell` (zaglavlje, podnožje, preskakanje, dijalog pretrage, poruke), `<body>` klasu `a1-body`. `a1-app` se ne stavlja na `<body>`, da stara pozadina i fontovi sadržaja ostanu isti.
- `<div class="page-shell">` ostaje omot sadržaja unutar `<main id="main-content">`.

## Navigacija

Iste stavke, redosled, nazivi, rute, provere dozvola i brojači kao ranije. Modul isključen u podešavanjima se sada ne ispisuje (ranije je bio sakriven CSS-om).

- Desktop (od 769 px): jedna traka sa markom, pretragom, korpom, obaveštenjima, kursom, temom i korisnikom. Glavna navigacija je drugi red do 1439 px, a od 1440 px u istom redu. Grupe su `details` padajući meniji. Otvaraju se klikom, tastaturom i prelaskom miša; Escape i izlazak fokusa ih zatvaraju.
- Telefon (do 768 px): dugme „Meni“ (`aria-expanded`) otvara ploču ispod trake. Dok je otvorena: pozadina stranice je `inert`, skrol je zaključan, Tab ostaje u zaglavlju, Escape i klik na zatamnjenje je zatvaraju, a fokus se vraća na dugme.
- Aktivna stranica nosi `aria-current="page"` (tačno poklapanje adrese). Grupa ili odeljak u kome se stranica nalazi dobija `a1-nav__link--active`.
- Tema: tri ikonice (sistem, svetla, tamna), `data-theme-mode-option`, primena bez ponovnog učitavanja, isti ključ `ald1n-theme-mode` u `localStorage`.
- Valuta prikaza i odjava su u korisničkom meniju.

## Provera

Vizuelna provera administracije ima strogi režim za modul `shell` (`ADMIN_VISUAL_STRICT_MODULES: 'shell'`). Za stranice modula `shell` axe meri samo elemente sa `.a1-shell`, jer je sadržaj stranice `/account` još star. Pravila za dokument (naslov, jezik) važe za celu stranicu.
