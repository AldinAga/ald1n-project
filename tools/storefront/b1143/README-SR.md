# B1143 — kontrolisana OFF instalacija bez uslova slobodnog OPcache-a

Paket nastavlja tačno završeni pokušaj B1142 iz izveštaja 1102 i ponovo koristi postojeći kompletan kandidat sa 8.531 fajlom. Prodavnica ostaje ISKLJUČENA. Ne izvršava migracije, promene baze ili zastavice prodavnice, restart PHP-a/naloga, reset ili invalidaciju OPcache-a, niti izmene drugih sajtova.

Raspakuj ceo ZIP u `/home/icaffeco/ald1n-project`. Njegov folder `incoming/` postavlja nove B1143 fajlove na predviđena mesta; stari B1142 i raniji fajlovi ostaju sačuvani. Skripta sama proverava kompletan paket i postojeće tri B1134 zavisnosti. Pokreni samo:

```bash
bash /home/icaffeco/ald1n-project/incoming/b1143-controlled-install.sh
```

Za rad bez OPcache-a ovaj paket dodaje tačno označen dodatak u CMS fajlu `apps/cms/current/public/.htaccess`: LiteSpeed `php_flag opcache.enable Off`. Postojeći sadržaj, prava pristupa i privatne kopije originala čuvaju se. Postojeći pregledani OFF clamp u `config/public_storefront.php` takođe ostaje na snazi. `.user.ini` i originalni `public/index.php` ostaju neizmenjeni. Pre dodavanja proverava se ograničena lista cPanel document-root putanja; prijavljuju se samo zbirni brojevi i logički rezultati. To nije univerzalni dokaz izolacije svih mogućih serverskih alias/rewrite pravila.

Instalacija napreduje tek ako stvarni CMS web zahtev potvrdi isključen OPcache pri prvom koraku pomoćnog koda, a isti URI izvrši različite A/B vrednosti istog dolaznog PHP fajla i njegove zavisnosti. Provera ne menja PHP podešavanje iz već pokrenutog skripta. Svežina neizmenjenog glavnog `index.php` zaključuje se iz proverenog izvornog koda i početnog PHP podešavanja; njegov sadržaj se ne menja radi testa. CI odvojeno proverava stvarno zastareo kod u memoriji i disk kešu i izvršavanje svežeg koda uz početno isključenje; to ne predstavlja dokaz da konkretni LiteSpeed hosting prihvata `.htaccess` pravilo. Taj dokaz je uslov stvarnog pokušaja.

Zadržane su provere identiteta PHP procesa, obe zadržane faze zahteva, prelazak istog procesa u mirovanje, roditeljska grupa, potpuni pregled procesa i mirno stanje pre zamene direktorijuma i oporavka. Saobraćaj drugih sajtova može odložiti ovu proveru ili izazvati HOLD; skripta ih ne zaustavlja. Privremena CMS maintenance zabrana i tehničko pauziranje njegovog schedulera deo su postojeće kontrolisane instalacije. Obični kontrolni HTTP zahtevi mogu izvršavati postojeću aplikaciju i njene uobičajene upite.

Ako lokalno pravilo nije podržano, ignorisano je ili izazove HTTP 500, kod se ne zamenjuje i originalna konfiguracija se vraća. Ako je kod već zamenjen, prvo se provereno vraća stari kod uz isključen OPcache. Originalna konfiguracija vraća se tek potom; obični `/login` mora odgovoriti 200, a opozvani pomoćni URI 404, pre proglašenja uspešnog oporavka. Ako taj korak ne uspe, ranije dokazano isključenje može se ponovo primeniti samo na CMS, uz njegovu owned maintenance zabranu i HOLD. Nedokazano ili nepodržano pravilo ne primenjuje se ponovo. Prekinut oporavak nastavlja se iz sačuvanog dnevnika, bez ponovnog prepisivanja kandidata ili starih dnevnika.

Pošalji samo novonastali numerisani izveštaj iz `docs/operations/`, uključujući završne redove `EXIT=` i `REPORT=`. Ne šalji privatne foldere stanja, `state.json`, `web.json`, signing key, nonce markere, originalne konfiguracione kopije, sadržaj procesa ili promenljive okruženja. PASS instalacije znači instaliran CMS sa prodavnicom OFF; javno otvaranje i kupovni testovi slede zasebno.
