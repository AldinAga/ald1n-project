# Prelazak na v2.1.5

Polazna verzija: **v2.1.4.1**.

v2.1.5 uvodi globalna UX i mobilna poboljšanja, sticky akcije na dugim formularima, zaštitu od duplog slanja i gubitka nesačuvanih izmena, jasnija validaciona stanja, prilagođene sistemske error stranice i novu runtime/Blade proveru.

Paket sadrži i migraciju `000037`, koja koristi već postojeće specifikaciono polje `snaga-napajanja`, povezuje ga sa tipom `desktop-racunar` kada je potrebno i postavlja ga približno u sredinu rasporeda specifikacija. Ne kreira se duplirano polje.

## Postupak

```bash
cd /home/icaffeco/cms.ald1n.com

/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify
```

Raspakuj UPGRADE paket preko postojeće instalacije, pa pokreni:

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize --strict-psr

/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan view:clear
/usr/local/bin/php artisan route:clear
/usr/local/bin/php artisan migrate --force

/usr/local/bin/php bin/cms-v2.1.5-smoke.php
/usr/local/bin/php bin/product-type-page-render-hotfix-smoke.php
/usr/local/bin/php artisan app:cms-v2-1-5-doctor --render --repair
/usr/local/bin/php artisan app:cms-v2-1-4-doctor --repair
```

Doctor treba da potvrdi da sve registrovane controller akcije ruta postoje, da se Blade prikazi kompajliraju i da je polje **Snaga napajanja** povezano sa tipom **Desktop računar** u srednjoj zoni specifikacija.

Zatim proveri na telefonu i računaru:

- katalog i filtere;
- dodavanje i izmenu artikla;
- kreiranje porudžbine;
- stranicu `/admin/catalog-settings/product-type/desktop-racunar`;
- da je `snaga-napajanja` među specifikacijama računara i približno po sredini;
- validacionu grešku na obaveznom polju;
- upozorenje pri napuštanju izmenjenog, nesačuvanog dugog formulara;
- 404 stranicu otvaranjem nepostojeće adrese.

Napravi svež v2.1.5 backup, pa pokreni:

```bash
/usr/local/bin/php artisan app:backup-create --type=manual
/usr/local/bin/php artisan app:backup-verify

/usr/local/bin/php artisan app:release-check \
  --profile=stable \
  --render \
  --strict \
  --snapshot \
  --repair \
  --report=v2.1.5-stable-acceptance.json

/usr/local/bin/php artisan optimize
```

Očekivani završetak:

```text
RELEASE CHECK: STABLE READY
```
