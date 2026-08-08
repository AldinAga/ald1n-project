# Ald1n CMS Phase 3B Push Delivery — candidate apply plan

Ovaj backend patch je pripremljen nad v2.2.0 Mobile API Foundation baseline-om. **Ne primenjivati ga na produkciju dok se ne uporede SHA-256 vrednosti modifikovanih postojećih fajlova sa trenutnim produkcionim kopijama.** Ako se hash razlikuje, prvo se pravi merge patch nad trenutnom produkcijom.

Redosled kada v0.2.0 Cart/Checkout acceptance prođe:

1. pokrenuti `PUSH-BACKEND-PREFLIGHT.sh` i sačuvati report;
2. napraviti ugrađeni backup + puni tar snapshot po postojećoj rollback proceduri;
3. uporediti SHA-256 za `OperationalNotificationService.php`, `config/mobile.php`, `routes/console.php` i `.env.example`;
4. primeniti patch dok `MOBILE_PUSH_ENABLED=false`;
5. `php artisan migrate --force`;
6. `php artisan optimize:clear`;
7. `php artisan app:mobile-push-doctor`;
8. potvrditi `php artisan schedule:list | grep mobile-push`;
9. završiti FCM V1 i mobilnu v0.3 registraciju na realnom uređaju;
10. tek tada postaviti `MOBILE_PUSH_ENABLED=true`, očistiti config cache i pokrenuti `app:mobile-push-doctor --strict`.

Push outbox i dispatcher su namerno odvojeni od HTTP/API zahteva; greška Expo servisa ne sme da rollbackuje poslovnu operaciju.
