# Prijava i resetovanje lozinke

## Prijava legacy korisnika

Stari CMS čuva lozinke u koloni `users.password_hash` pomoću PHP `password_hash()` funkcije. Laravel Alpha2.1 eksplicitno učitava korisnika po korisničkom imenu ili e-mailu i proverava hash preko Laravel Hash sloja.

Uspešna prijava:

- zahteva status `active`;
- regeneriše session ID;
- može da zapamti uređaj 30 dana;
- automatski rehashuje validnu lozinku kada je potrebno;
- upisuje `last_login_at`.

## Reset link

- token ima 80 nasumičnih alfanumeričkih karaktera;
- u bazi se čuva samo SHA-256 hash tokena;
- link važi 60 minuta;
- svaki novi zahtev poništava prethodni token tog korisnika;
- token se može iskoristiti samo jednom;
- odgovor zahteva ne otkriva da li e-mail postoji;
- zahtev je zaštićen Turnstile proverom i rate limiting-om.

## Posle promene lozinke

Sistem:

- postavlja novi `password_hash`;
- upisuje `password_changed_at`;
- menja `remember_token`;
- briše sve Sanctum tokene korisnika;
- briše sve njegove reset tokene.

## Terminal reset

Za hitan pristup bez e-maila:

```bash
php artisan app:reset-user-password KORISNICKO_IME_ILI_EMAIL
```

## Test SMTP-a

```bash
php artisan app:send-test-mail PRIMALAC@example.com
```

Reset funkcija nije spremna za korisnike dok test poruka ne stigne na stvarni mailbox.
