# v2.2.0 Mobile API Foundation — production readiness

- [ ] pre-upgrade v2.1.6 rollback backup je sačuvan i preuzet van hostinga;
- [ ] v2.2.0 hotfix PATCH je raspakovan preko postojeće instalacije;
- [ ] `.env` koristi `QUEUE_CONNECTION=database`;
- [ ] `php artisan optimize:clear` je pokrenut posle izmene `.env`;
- [ ] `bin/v2.2.0-production-readiness-hotfix-smoke.php` prolazi 10/10;
- [ ] `app:cms-v2-2-0-doctor --strict` prolazi;
- [ ] napravljen je novi v2.2.0 backup, dok je stari v2.1.6 backup zadržan za rollback;
- [ ] `app:backup-verify` nema WARN o verziji;
- [ ] disk health prikazuje HEALTHY kada postoji stotine GB slobodnog prostora;
- [ ] `app:release-check --profile=stable --strict` završava sa `STABLE READY`;
- [ ] trajni database queue worker je pokrenut ili konfigurisan kroz hosting watchdog;
- [ ] PHPUnit je izvršen na staging/local okruženju pre produkcionog `composer install --no-dev`.

# v2.2.0 produkciona kontrolna lista

- [ ] potvrđena početna verzija `2.1.6`;
- [ ] napravljen i verifikovan pre-deploy backup;
- [ ] `APP_URL` je tačan javni HTTPS URL backend-a;
- [ ] `.env` koristi `QUEUE_CONNECTION=database`;
- [ ] podešene MOBILE_ANDROID/MOBILE_IOS minimalne i aktuelne verzije;
- [ ] izvršene migracije `000039`, `000040` i `000041`;
- [ ] `bin/cms-v2.2.0-smoke.php` prolazi;
- [ ] `app:cms-v2-2-0-doctor --strict` prolazi;
- [ ] `route:list --path=api/v1` prikazuje nove Mobile API rute;
- [ ] test login vraća Sanctum token, permissions i `api_version=v1`;
- [ ] `/api/v1/bootstrap` vraća user/features/app ugovor;
- [ ] registracija, izmena i opoziv test uređaja rade;
- [ ] katalog filteri i order options rade sa stvarnim dozvolama;
- [ ] notification list/read/read-all rade samo za vlasnika;
- [ ] validaciona greška vraća `code`, `errors` i `request_id`;
- [ ] queue worker je instaliran i održava se automatski;
- [ ] `queue:restart` je izvršen nakon deploy-a;
- [ ] `MOBILE_PUSH_ENABLED=false` dok push dispatcher ne bude uveden;
- [ ] napravljen i verifikovan svež v2.2.0 backup;
- [ ] Stable release check završava sa `RELEASE CHECK: STABLE READY`.
