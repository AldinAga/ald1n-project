# v2.1.0 Stable Operations

## Politika stabilne grane

U Stable grani dozvoljene su bezbednosne ispravke, regresioni hotfix paketi i kompatibilne korekcije bez promene potvrdenih poslovnih pravila. Velike funkcionalnosti razvijaju se u narednoj minor verziji.

## Redovne provere

Dnevno proveriti scheduler heartbeat, automation rezultat, outbox greske i slobodan prostor. Nedeljno pokrenuti `app:system-health` i pregledati aplikacioni log. Mesecno napraviti manual backup i izvrsiti `app:backup-verify`.

## Release provera

```bash
/usr/local/bin/php artisan app:release-check --profile=stable --render --strict --snapshot
```

Svaki Stable hotfix mora zadrzati `0 WARN` i `0 FAIL` u strict rezimu.

## Incident

Kod critical incidenta prvo sacuvati logove i trenutni backup, zatim izolovati uzrok. Ne menjati poslovne podatke direktnim SQL-om bez dokumentovanog razloga i audit traga.

## v2.1.5 mobilna i runtime kontrola

Posle nadogradnje proveriti bar jedan dugi formular proizvoda i porudžbine na telefonu, upozorenje za nesačuvane izmene i stranice 404/500. Pri promenama specifikacija desktop računara `app:cms-v2-1-5-doctor --repair` može ponovo povezati `snaga-napajanja` i vratiti ga u srednju zonu bez kreiranja duplikata.

## Data Quality rutina od v2.1.6

Preporučeno je jednom nedeljno otvoriti Data Quality Center ili pokrenuti `php artisan app:data-quality-doctor`. Bezbedni repair režim koristi se tek nakon pregleda izveštaja i aktuelnog backupa.
