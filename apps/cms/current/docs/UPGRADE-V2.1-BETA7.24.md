# Nadogradnja na v2.1.0-beta7.24

## Namena paketa

Customer Portal 2.0 uvodi bezbedno uključivanje kupaca u sistem, povezivanje postojećih porudžbina, profil, kontrolu aktivnih prijava i centralnu komunikaciju sa podrškom.

## Obavezni preduslovi

- polazna verzija: `v2.1.0-beta7.23.2`;
- kompletan backup baze i aplikacije;
- ispravan SMTP za slanje aktivacionih poziva;
- Laravel scheduler mora raditi svakog minuta;
- PHP putanja na trenutnom serveru: `/usr/local/bin/php`.

## Postavljanje

```bash
cd /home/icaffeco/cms.ald1n.com
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr
/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan view:clear
/usr/local/bin/php artisan migrate --force
/usr/local/bin/php artisan db:seed --class=Database\\Seeders\\CoreAccessSeeder --force
/usr/local/bin/php artisan app:customer-portal-doctor --repair --render
/usr/local/bin/php artisan app:customer-portal-maintenance
/usr/local/bin/php bin/customer-portal-2-smoke.php
/usr/local/bin/php artisan app:release-check --profile=full --repair --render --snapshot
/usr/local/bin/php artisan optimize
```

## Novi tok aktivacije

Administrator otvara **Korisnici → Customer Portal**, kreira kupca i šalje poziv. Sistem u bazi čuva samo SHA-256 hash tokena. Link važi 72 sata, može se upotrebiti jednom i zahteva Turnstile kada je globalno omogućen. Kupac postavlja lozinku, a nalog postaje aktivan. Ponovno slanje linka aktivnom kupcu ne zaključava postojeći nalog.

## Povezivanje postojeće porudžbine

Na detalju kupca administrator pretražuje porudžbinu. Promena postojećeg vlasnika zahteva checkbox potvrdu i obrazloženje. Sistem ažurira garancije, opciono komunikacije, i čuva neizmenjivu istoriju u `portal_order_link_history`.

## Komunikacija

Kupac vidi samo poruke sa `visibility=public`. Administratori vide i interne beleške. Interna beleška se ne učitava u korisničkom kontroleru niti korisničkom Blade prikazu.

## Aktivne prijave

Registry radi nezavisno od Laravel session drivera. Opozvana prijava se prekida na sledećem zahtevu. Promena lozinke opoziva sve druge prijave, API tokene i remember-me tokene, zatim rotira i ponovo registruje trenutnu sesiju. Zastarele evidencije se automatski zatvaraju prema session lifetime-u ili remember-me roku.

## Nova migracija

`database/migrations/2026_08_01_000032_create_customer_portal_2_beta7_24.php`

Dodaje profile kolone u `users` i tabele:

- `user_activation_tokens`;
- `user_login_sessions`;
- `portal_conversations`;
- `portal_messages`;
- `portal_order_link_history`.

Rollback namerno ne briše bezbednosne, komunikacione i audit podatke.

## Automatsko održavanje

Scheduler svakog dana u 03:45 pokreće:

```bash
/usr/local/bin/php artisan app:customer-portal-maintenance
```

Komanda uklanja iskorišćene ili dugo istekle aktivacione tokene i zatvara zastarele evidencije prijava. Hosting cron i dalje mora pokretati `artisan schedule:run` svakog minuta.
