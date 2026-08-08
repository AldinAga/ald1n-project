# Nadogradnja na v2.1.0-beta7.17.2

Ovaj hotfix rešava prerano zatvaranje desktop navigacionog podmenija kada se miš pomeri sa glavne stavke prema padajućem meniju. Uzrok je bio fizički razmak između `summary` elementa i apsolutno pozicioniranog podmenija, zbog čega se `mouseleave` aktivirao pre nego što je kursor stigao do podmenija.

## Novo ponašanje

- hover otvara podmeni;
- podmeni ostaje otvoren dok je kursor iznad glavne stavke ili samog podmenija;
- uveden je grace period od 280 ms za prelazak preko razmaka;
- CSS hover bridge pokriva razmak između stavke i menija;
- klik prikvači podmeni sve do klika van menija, ponovnog klika ili Escape tastera;
- izbor linka zatvara podmeni i mobilnu navigaciju;
- istovremeno je otvoren najviše jedan podmeni.

## Postavljanje

Paket se postavlja preko v2.1.0-beta7.17.1. Nema nove migracije baze.

```bash
php artisan optimize:clear
php artisan view:clear
php artisan optimize
```

Zbog promene Blade layout-a i CSS-a preporučuje se hard refresh u browseru (`Ctrl+F5`).
