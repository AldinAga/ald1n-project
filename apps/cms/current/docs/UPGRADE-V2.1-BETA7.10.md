# Upgrade na v2.1.0-beta7.10

Ova verzija uvodi kompletan postprodajni modul za reklamacije, povrate i servisne zahteve povezane sa isporučenim porudžbinama.

## Šta je dodato

- jedinstveni broj slučaja formata `PS-YYYYMMDD-000001`;
- tri tipa: reklamacija, povrat i servisni zahtev;
- pogođene stavke porudžbine, količine i opis problema po stavci;
- prioriteti i automatski SLA rok;
- odgovorno lice i administratorski scope;
- statusni tok od otvaranja do zatvaranja;
- javna komunikacija sa korisnikom i interne administratorske napomene;
- privatni PDF/JPG/PNG/WebP prilozi do 10 MB;
- strukturisana odluka i obrazloženje rešenja;
- istorija statusa i audit događaji;
- obaveštenja i automatsko upozorenje za probijen rok;
- doctor komanda `app:after-sales-doctor`;
- KPI kartice na dashboardu za aktivne, probijene i slučajeve koji čekaju kupca;
- SuperAdministrator fallback obaveštenje kada slučaj nema aktivnog dodeljenog administratora;
- kontrolisano ponovno otvaranje konačnog slučaja uz obavezan razlog;
- privatni download priloga bez browser cache-a.

## Migracija

Obavezno pokrenuti:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\CoreAccessSeeder' --force
```

Migracija kreira tabele:

- `after_sales_cases`;
- `after_sales_case_items`;
- `after_sales_messages`;
- `after_sales_attachments`;
- `after_sales_status_history`.

Ne menja postojeće porudžbine, uplate, dokumente ili lager.

## Provera

```bash
php artisan app:deployment-check
php artisan app:after-sales-doctor
php artisan app:automation-doctor
```

## Važno poslovno pravilo

Otvaranje postprodajnog slučaja samo po sebi ne menja lager niti finansije. Odluke poput zamene, povrata novca ili povrata robe evidentiraju se kao odluka slučaja, dok se stvarna finansijska ili lager operacija i dalje izvršava kroz postojeće kontrolisane module.


## Statusna i konkurentna zaštita

- zaključani slučaj se ponovo proverava unutar transakcije pre dodavanja poruke;
- slučaj koji je drugi administrator upravo zatvorio neće prihvatiti novu poruku;
- prelaz iz `resolved` u `closed` zadržava originalni `resolved_at` datum;
- vraćanje konačnog slučaja u obradu dozvoljeno je SuperAdministratoru uz obavezan razlog;
- promena prioriteta može ponovo izračunati SLA rok kada rok nije ručno unet.
