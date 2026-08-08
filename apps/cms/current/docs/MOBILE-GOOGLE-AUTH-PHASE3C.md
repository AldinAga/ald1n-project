# Phase 3C — Google login/registration

## Arhitektura

Firebase/Google Cloud daje Android OAuth konfiguraciju i `google-services.json`, ali Laravel ostaje jedini autoritet za poslovnu sesiju i dozvole. Mobilni klijent dobija Google ID token, šalje ga preko HTTPS na `POST /api/v1/auth/google`, Laravel proverava potpis i OIDC claim-ove i tek zatim izdaje postojeći Sanctum token.

Backend ne čuva Google access/refresh tokene. U `user_external_identities` se čuva samo provider, stabilni Google `sub` i poslednji potvrđeni e-mail.

## Registracija

Podrazumevano:

```env
MOBILE_GOOGLE_AUTH_ENABLED=false
MOBILE_GOOGLE_REGISTRATION_ENABLED=false
MOBILE_GOOGLE_REGISTRATION_AUTO_ACTIVATE=false
```

Kada se Google auth uključi, aktivni postojeći CMS korisnik sa potvrđenim istim e-mailom može biti povezan i prijavljen. Kada se uključi i `MOBILE_GOOGLE_REGISTRATION_ENABLED=true`, potpuno novi Google nalog kreira standardnog korisnika sa statusom `pending`. Time Google prijava ne zaobilazi postojeću administrativnu aktivaciju.

Automatska aktivacija novih Google registracija postoji samo kao eksplicitna opcija `MOBILE_GOOGLE_REGISTRATION_AUTO_ACTIVATE=true` i ne treba je uključivati bez poslovne odluke.

## Aktivacija

Pre `MOBILE_GOOGLE_AUTH_ENABLED=true` obavezno:

1. Firebase Android app package `com.ald1n.mobile.preview`;
2. SHA-1 EAS preview signing sertifikata;
3. Google provider uključen u Firebase Authentication;
4. ažuriran `google-services.json` u mobilnom workspace-u;
5. `GOOGLE_OAUTH_WEB_CLIENT_ID` je Web application OAuth client ID iz istog projekta;
6. `php artisan app:mobile-google-auth-doctor` prolazi.

## Bezbedno povezivanje postojećih naloga

Google `sub` ostaje primarni spoljašnji identitet. Povezivanje postojećeg CMS naloga samo po podudarnom e-mailu radi automatski isključivo za `@gmail.com` ili potvrđen Google Workspace identitet (`email_verified=true` i prisutan `hd`). Za Google naloge sa third-party e-mail adresom automatsko povezivanje postojećeg CMS naloga se odbija; korisnik se prvo prijavljuje CMS lozinkom, a eksplicitni link flow se dodaje zasebno. Novi Google nalog i dalje može pokrenuti standardnu `pending` registraciju kada je registracija omogućena.
