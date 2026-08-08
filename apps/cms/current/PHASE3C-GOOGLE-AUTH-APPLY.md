# Phase 3C Google auth — deployment candidate

Ovaj paket se ne raspakuje na produkciju dok PRECONDITION-SHA256.txt ne prođe protiv trenutnih produkcionih fajlova i dok postoji svež verifikovan rollback backup.

Prvo se instalira sa svim `MOBILE_GOOGLE_*` flagovima na `false`, pokreće migracija 000043 i `app:mobile-google-auth-doctor`. Tek posle Firebase/SHA-1/OAuth konfiguracije uključuje se Google auth. Registracija može biti uključena nezavisno. Auto-aktivacija ostaje `false` dok se eksplicitno ne odluči drugačije.

## R2 bezbednosna napomena

Automatsko povezivanje postojećeg CMS naloga po e-mailu dozvoljeno je samo kada je Google autoritativan za adresu: `@gmail.com`, ili potvrđen Google Workspace identitet sa `hd` claim-om. Za spoljne/non-Gmail adrese postojeći CMS nalog se ne povezuje automatski; time se izbegava preuzimanje naloga samo na osnovu ranije verifikovanog third-party e-maila.
