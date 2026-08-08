# Arhitektura v2.0.0-beta6

## Izvori podataka

- `mysql` / `icaffeco_lrvl`: jedini READ/WRITE produkcioni izvor za Laravel katalog, nove porudžbine, provizije i lager;
- `legacy` / `icaffeco_cms`: istorijski izvor, read-only kroz DB session i SQL guard;
- sinhronizacija podržanih podataka ostaje jednosmerna `legacy → Laravel`.

## Production Orders & Inventory

`OrderService` izvršava jednu MySQL transakciju: zaključava idempotency zapis i proizvode stabilnim redosledom, proverava pristup/lager/kurs, kreira snapshotove, umanjuje lager, upisuje jedinstvena stock movements kretanja, proviziju, istoriju i audit. Greška vraća celu transakciju.

`OrderWorkflowService` vraća lager samo za Laravel porudžbine i samo jednom. `inventory_state` i jedinstveni `cancel-return` event key sprečavaju dvostruki povrat.

## Post-login runtime sloj

Beta6 dodaje globalni `EnsureRuntimeDirectories` middleware pre Laravel session/cache obrade. On proverava:

- `storage/framework/sessions`;
- `storage/framework/cache/data`;
- `storage/framework/views`;
- `storage/logs`;
- `bootstrap/cache`.

Direktorijumi se best-effort kreiraju sa `0775`. Kada nisu upisivi, vraća se kontrolisani HTTP 503 pre session, throttle ili Blade upisa. `app:deployment-check --repair` radi jači write probe i kompajlira sve view-ove.

File rate limiter je eksplicitno definisan kroz `CACHE_LIMITER=file`; Redis nije deo arhitekture.

## Authenticated shell bez hard DB zavisnosti

View composer nezavisno pokušava settings, assets, exchange rate i korisnički prikaz. Svaki segment ima lokalni fallback. `layouts.app` ne poziva `SettingsService` niti direktno pokreće role query. Footer template se renderuje iz već učitanih vrednosti.

`User::displayInitial()` i `User::roleName()` su odbrambeni helper-i. Gate/permission logika takođe vraća bezbedan deny/fallback kada su access tabele privremeno nedostupne.

`DashboardController` izoluje relation, permission, order, product, user i settings segmente. Warning logging je best-effort, pa neupisiv log direktorijum ne može da obori fallback.

## Login tok

Login redosled je:

1. Turnstile validacija sa kontrolisanim transport/JSON greškama;
2. user lookup po username/e-mail vrednosti;
3. konstantni dummy hash za nepostojeći nalog;
4. provera aktivnog statusa i lozinke;
5. best-effort password rehash;
6. provera dostupnosti `remember_token` pre persistent login-a;
7. Auth login i session regeneracija unutar zaštićenog bloka;
8. best-effort `last_login_at` telemetry;
9. redirect isključivo na dashboard.

## Repair šema

Migracija `000008` popravlja core tabele i kolone potrebne za login, settings, password reset i Sanctum bez brisanja podataka. Deployment repair zatim pokreće `CoreAccessSeeder`.

## Legacy zaštita

Zaštita ima tri preporučena sloja:

1. poseban MySQL korisnik sa `SELECT`/`SHOW VIEW` grantovima;
2. PDO init `SET SESSION TRANSACTION READ ONLY`;
3. `LegacyReadOnlyGuard` kroz `beforeExecuting`.

## UI

Desktop i mobilni header, legacy dashboard hero, obojene akcije/KPI kartice, hamburger drawer i compact admin forme ostaju kao u beta4/beta5.


## Operational Orders & Commissions (v2.1.0-beta2)

- `OrderOperationalService` upravlja preuzimanjem, rokovima, internim napomenama i ponovnom dodelom.
- `CommissionWorkflowService` sprovodi statusnu mašinu provizija i masovne payment batch transakcije.
- `OrderTimelineService` spaja status istoriju, audit, dodele, provizije i privatne napomene.
- `OperationalNotificationService` šalje neblokirajuća database obaveštenja, uz opcion mail kanal.
- Admin scope se uvek ograničava preko `orders.supplier_user_id`; SuperAdministrator ima globalni scope.
- Sve finansijske i assignment promene koriste MySQL transakcije, row lock i audit istoriju.


## Migration runtime compatibility (v2.1.0-beta3.1)

Migracije u globalnom namespace-u ne koriste import prostih globalnih klasa kao `use Throwable;`. Catch blokovi koriste `\Throwable`, jer PHP 8.4 takav import prijavljuje kao warning, a produkcioni error handler može warning pretvoriti u exception pre izvršenja migracije. Build lint tretira warnings/deprecations/notices kao neuspeh.

## Payments, Advanced Inventory & Operational Reports (v2.1.0-beta3)

- `OrderPaymentService` vodi privatne potvrde, verifikovane uplate/refundacije i preračun salda.
- `IpsPaymentPayloadService` generiše i čuva IPS podatke iz snapshot-a porudžbine i aktivnog žiro računa.
- `AdvancedInventoryService` koristi idempotency, sortirani `lockForUpdate`, stock movements i audit za ulaz robe i popis.
- `order_payments` je finansijski ledger; samo status `verified` utiče na `orders.paid_total_rsd`.
- `stock_receipts`/`inventory_counts` i njihove stavke predstavljaju nepromenljivu operativnu istoriju.
- potvrde uplate se čuvaju na privatnom `local` disku, nikada direktno pod javnim URL-om.
- `/admin/inventory` i Reports beta3 sažeci imaju schema readiness i recovery fallback.


## Commission action modal (v2.1.0-beta3.2)

Administratorska obrada provizije koristi viewport modal umesto apsolutnog popover-a unutar `overflow:auto` tabele. Time se UI sloj odvaja od horizontalnog scroll kontejnera, tabela zadržava prirodnu visinu, a formular koristi raspoloživu površinu ekrana. Samo veoma duga istorija dobija lokalni modal scroll.

## Operativna automatizacija (v2.1.0-beta4)

- `OperationalAutomationService` je jedina domena za scan operativnih rokova, dospelih plaćanja i lager upozorenja.
- `operational_alerts.alert_key` obezbeđuje deduplikaciju; otvoreni problem se ažurira umesto kreiranja novih redova.
- `automation_runs` čuva svako scheduler ili ručno pokretanje, rezultate i grešku.
- `notification_preferences` kontroliše in-app/e-mail kanal i poslovne kategorije po korisniku.
- Scheduler koristi `Cache::lock` nad file cache store-om i `withoutOverlapping`; Redis nije potreban.
- Scan se pokreće hourly, a dnevni digest u 08:05. Jedini hosting cron je `artisan schedule:run` svakog minuta.

## Evidencija isporuke i otpremnica (v2.1.0-beta7.8)

`OrderWorkflowService::complete()` sada u istoj zaključanoj transakciji obrađuje konačnu naplatu i strukturiranu isporuku. `order_deliveries` čuva način, stvarni datum, primaoca, referencu, napomenu, potvrdioca i metapodatke privatnog dokaza.

Dokaz isporuke se čuva na `local` disku. Ne postoji javna storage ruta; `OrderDeliveryController` prvo koristi `OrderAccessService`, zatim vraća fajl sa `private, no-store` header-ima. Zamena dokaza je atomska u odnosu na poslovni upis: novi fajl se briše ako transakcija padne, a prethodni tek nakon uspešnog commit-a.

`delivery_note` je četvrti tip poslovnog dokumenta. Dobija `OTP-YYYY-NNNNNN` broj i snapshot podataka isporuke u `order_documents`, tako da kasnija korekcija operativnog zapisa ne menja već izdatu otpremnicu.

SuperAdministrator može da ukloni terminalnu blokadu kroz `OrderWorkflowService::reopen()`. Razlog je obavezan i auditovan. Uplate, delivery zapis, dokaz i istorijski dokumenti se ne brišu; samo se uklanjaju `completed_*` vrednosti da bi se dozvolila kontrolisana korekcija i novo kompletiranje.

## Otpremnica schema hardening (v2.1.0-beta7.9)

`order_documents.document_type` je normalizovan na `VARCHAR(40)` zbog kompatibilnosti sa novim tipovima dokumenata. Dozvoljene vrednosti ostaju strogo kontrolisane u Form Request/controller i servisnom sloju. Preflight provera otkriva neprimenjenu migraciju pre INSERT-a, a svi neočekivani kvarovi dobijaju incident ID.

## Postprodajni modul (v2.1.0-beta7.10)

Postprodajni slučaj je zaseban agregat vezan za porudžbinu. `after_sales_cases` čuva poslovni status, prioritet, SLA rok, odgovorno lice i snapshot krajnjeg kupca. Pogođene stavke se čuvaju u `after_sales_case_items`, komunikacija u `after_sales_messages`, privatni prilozi u `after_sales_attachments`, a nepromenljiva istorija statusa u `after_sales_status_history`.

Korisničke poruke su javne učesnicima slučaja. Administratorske poruke mogu biti javne ili interne. Prilozi se čuvaju na privatnom `local` disku i preuzimaju samo kroz autorizovanu kontrolersku rutu.

Slučaj se može otvoriti samo za isporučenu ili kompletiranu porudžbinu. Otvaranje i rešavanje slučaja ne menjaju automatski finansije ili lager; takve promene ostaju u postojećim transakcijskim modulima.


## Postprodajni slučajevi — beta7.10

Postprodajni domen je odvojen od operativnog statusa porudžbine. `after_sales_cases` je aggregate root, dok stavke, poruke, prilozi i statusna istorija čuvaju snapshot i nepromenljivu poslovnu istoriju. Finansije i lager se ne menjaju pri samom otvaranju ili odobravanju slučaja.

`AfterSalesCaseService` zaključava porudžbinu pri otvaranju i slučaj pri komunikaciji ili promeni statusa. Autorizacija se ponavlja nakon `lockForUpdate()` kako promena dodele ili zatvaranje u konkurentnom zahtevu ne bi omogućili zastarelu akciju. Privatni prilozi se čuvaju na `local` disku i preuzimaju kroz autorizovanu rutu sa `Cache-Control: private, no-store`.

SLA se određuje prema prioritetu, automatizacija kreira deduplikovano upozorenje za probijen rok, a dashboard daje operativni pregled aktivnih i zakasnelih slučajeva. Ako odgovorni administrator nije aktivan, slučaj ostaje u centralnom redu i obaveštava aktivne SuperAdministratore.


## Izvršenje postprodajnih odluka (v2.1.0-beta7.11)

`after_sales_actions` predstavlja kontrolisanu izvršnu jedinicu koja nastaje nakon postprodajne odluke. Radnja ima sopstveni poslovni broj, tip, status, odgovorno lice, termin, rok i javne/interne napomene. `after_sales_action_items` povezuje izvršenje sa snapshot stavkama slučaja i čuva količinu, dispoziciju i eventualni stock movement.

`AfterSalesActionService` zaključava radnju, slučaj, porudžbinu i sve lokalne proizvode stabilnim redosledom. Zamenska isporuka koristi negativni stock movement, a prijem vraćene robe povećava lager samo kada je dispozicija `restock`. Svaki efekat koristi jedinstveni `event_key`, pa retry ili dvostruki klik ne može ponoviti knjiženje.

Refundacija se evidentira u postojećem `order_payments` ledger-u i povezuje preko `after_sales_action_id`. Dozvoljena je na kompletiranoj porudžbini jer predstavlja odobrenu postprodajnu korekciju, ali iznos ne može premašiti trenutno neto uplaćeni iznos.

Konačni status slučaja je odvojen od statusa radnje. Slučaj ne može biti rešen, odbijen ili zatvoren dok postoje aktivne radnje. Za popravku, zamenu, povrat ili refundaciju mora postojati završena radnja, čime se sprečava administrativno zatvaranje bez stvarnog izvršenja.

## Terenske operacije i radni nalozi (v2.1.0-beta7.12)

Fizičke postprodajne radnje (`service_visit`, `replacement_dispatch`, `return_receipt`) imaju tačno jedan `FieldWorkOrder`. Radni nalog je operativni sloj koji čuva snapshot kupca i adrese, ekipu, termin, status dolaska, kilometražu, troškove i dokaz izvršenja.

`FieldWorkOrderPlanner` je jedino mesto za kreiranje i raspoređivanje naloga. Prilikom dodele zaključava red aktivne ekipe i proverava preklapanje intervala. `FieldOperationsService` upravlja statusima i završava povezanu `AfterSalesAction` u istoj poslovnoj transakciji.

Privatni dokazi se čuvaju na `local` disku i preuzimaju kroz autorizovanu rutu. `OperationalAutomationService` vodi deduplikovana upozorenja za probijene termine i naloge koji nisu raspoređeni duže od 24 sata.

## Servisni lager i nabavka (v2.1.0-beta7.13)

Servisni delovi su odvojeni od prodajnog lagera proizvoda. `service_parts.stock_quantity` predstavlja fizičko stanje, dok `reserved_quantity` predstavlja količinu zaključanu za aktivne terenske radne naloge. Raspoloživa količina računa se kao fizičko stanje minus rezervacije.

`field_work_order_parts` čuva snapshot šifre, naziva, jedinice i cene dela. Završetak radnog naloga knjiži stvarni utrošak kroz `service_part_movements`, a preostalu rezervaciju oslobađa u istoj transakciji. Otkazivanje naloga oslobađa sve rezervacije bez promene fizičkog stanja.

Nabavka se vodi kroz `service_part_purchase_requests` i stavke. Prijem je idempotentan i za svaki deo kreira jedinstveno kretanje, povećava stanje i računa ponderisanu prosečnu cenu.

## Revizije poslovnih dokumenata (v2.1.0-beta7.14)

`order_documents` više nije ograničen na jedan red po kombinaciji porudžbine i tipa dokumenta. Integritet aktivnog dokumenta čuva servis unutar transakcije koja zaključava porudžbinu. Ako postoji `issued` dokument istog tipa, vraća se postojeći zapis. Ako su prethodne revizije stornirane, kreira se nova revizija sa sledećim `revision_number` i `supersedes_document_id` vezom. Time istorija ostaje nepromenljiva, a ponovljeni zahtev ne stvara duplikat aktivnog dokumenta.

## E-mail outbox, dokumenti i NBS IPS QR (v2.1.0-beta7.16)

`order_email_outbox` je pouzdani izlazni red za sve e-mail događaje porudžbine. Poslovni servis samo upisuje događaj nakon uspešnog commit-a; SMTP se obrađuje odvojeno kroz `app:order-email-dispatch`. Time nedostupan mail server ne može poništiti kreiranje porudžbine, promenu statusa, uplatu ili izdavanje dokumenta.

Svaki red ima deterministički dedupe ključ, sopstvenog primaoca, vreme dospeća, broj pokušaja i eventualnu vezu ka tačnoj reviziji dokumenta. Dispatcher grupiše kompatibilne događaje po primaocu i intervalu, resetuje zaglavljene `sending` redove i proverava da dokument nije storniran između claim-a i slanja. PDF prilog se čita iz privatnog storage-a tek u trenutku slanja.

`NbsIpsQrService` generiše IPS payload iz snapshot podataka porudžbine i tačnog ukupnog iznosa dokumenta. Za predračun i račun sa uplatom na račun poziva se zvanični NBS servis, a vraćeni PNG i payload se čuvaju uz konkretnu reviziju u `order_documents`. PDF writer ugrađuje PNG bez GD ekstenzije. Novi finansijski dokument se ne izdaje ako podaci za plaćanje ili QR nisu validni; ranije stornirani istorijski dokument bez snapshot-a ostaje pregledljiv bez novog poziva NBS servisu.

Garancijski rok se računa deterministički: prvo `duration_months` sa `addMonthsNoOverflow()`, zatim `duration_days`. Oba podatka se snapshotuju u izdati garantni list.


## Correlated catalog specifications (beta7.18)

`SpecificationField.parent_field_id` definiše zavisnost polja, dok `specification_options` čuva stabilne vrednosti opcija. Tabela `specification_option_dependencies` mapira dozvoljene roditelj–dete parove. JavaScript samo poboljšava korisničko iskustvo; `ProductRequest` ponavlja sve provere na serveru. `value_detail` čuva precizan model bez fragmentacije porodice koja se koristi za filtriranje. `CatalogSpecificationFilterService` centralizuje iste select, range, boolean, text i detail filtere za korisnički i administratorski katalog.


## Smart Product Management (beta7.19)

`ProductType` je centralni šablon proizvoda. `ProductTemplateService` primenjuje podrazumevane vrednosti i generiše naziv; `ProductCompletenessService` obračunava ponderisanu kompletnost i kontroliše minimalni prag; `ProductBulkService` izvršava pregledane masovne operacije; `ProductAdminService` koristi ista pravila pri običnom čuvanju i kloniranju. Klon čuva `source_product_id`, ali nikada ne nasleđuje lager, rezervacije, serijske brojeve ili istoriju.

## Product Variants (beta7.20)

`products` ostaje roditeljski kataloški zapis. `product_variants` sadrži prodajnu konfiguraciju sa sopstvenim SKU-om, cenom i lagerom, dok `product_variant_spec_values` čuva razlike u specifikacijama. Parent `stock_quantity` je denormalizovani zbir aktivnih varijanti, a `default_variant_id` određuje prikazanu cenu. `order_items` čuva i FK i nepromenljivi snapshot varijante. Stock movement, postprodaja i garancija nose `product_variant_id`, ali istorijski snapshot ostaje autoritativan za dokumente.

## RC1 assurance sloj

Release Candidate uvodi assurance sloj iznad postojecih module doctor komandi:

- package assurance: `ReleaseIntegrityCommand`;
- environment assurance: `SecurityHardeningDoctorCommand`;
- schema assurance: `MigrationsDoctorCommand`;
- authorization assurance: `AccessControlDoctorCommand`;
- recovery assurance: `BackupVerifyCommand`;
- orchestration: `app:release-check --profile=rc`.

Komande ne menjaju poslovne podatke. One koriste postojecu Laravel konfiguraciju, route registry, migrations tabelu, access tabele i backup manifest da bi dokazale da je isti kod koji je testiran zaista postavljen i operativno spreman.


## v2.1.0 Stable release sloj

Stable izdanje zadrzava RC1 arhitekturu bez nove migracije. `stable` release profil je namerno identican `rc` profilu, kako promocija ne bi oslabila sigurnosne, migration, access-control, backup, render ili poslovne provere.

## v2.1.3 Tip proizvoda kao izvor klasifikacije

`ProductType` je od v2.1.3 jedini izbor na formi artikla za klasifikaciju tip/kategorija. `product_types.category_id` mapira tip na jednu internu kategoriju, dok `product_categories` ostaje kompatibilni sloj za postojece permission scope-ove, filtere i izvestaje. Servisi za create, update, clone i bulk sinhronizuju ovu vezu i ne prihvataju rucnu kategoriju sa forme artikla.

Vise diskova ostaje jedna poslovna specifikacija radi kompatibilnosti pretrage, ali `value_json` cuva uredjaje kao uredjenu listu objekata `{type, capacity_gb}`. `value_text` se paralelno cuva kao citljiv prikaz.

## v2.1.3.1 integritet kataloga

`ProductTypeCategoryService` je jedina servisna tačka za automatsko mapiranje tipa na kategoriju i sinhronizaciju `product_categories`. `SpecificationFieldLifecycleService` centralizuje destruktivno brisanje polja i repair istorijskih orphan/stale referenci. Product i variant write tokovi filtriraju ulaz prema aktivnim poljima dodeljenim trenutnom tipu.

## v2.1.3.2 FormRequest regex pravila

SKU validacija u `ProductRequest` i `ProductVariantRequest` koristi delimiter koji nije deo dozvoljenog skupa znakova. Time se uklanja mogućnost da pogrešno escape-ovanje kose crte izazove runtime upozorenje pre ulaska u controller i servisni sloj.

## v2.1.3.3 izvedeni ukupan kapacitet diskova

`StorageSpecificationService` centralizuje detekciju, povezivanje, migraciju i runtime računanje diskova. Izvorno text/select polje ima `storage_role=components`, a staro numeričko polje `storage_role=total_capacity` i `storage_source_field_id`. Izvor čuva uređeni JSON i kompatibilni tekst, dok izvedeno polje čuva samo numerički zbir radi filtera i izveštaja. FormRequest i servisni sloj oba ponavljaju proračun, čime UI ili ručno izmenjen zahtev ne mogu da razdvoje zbir od pojedinačnih diskova.

## v2.1.4 Product model i lifecycle

`products.model_name` je namensko polje za komercijalnu oznaku uređaja i nije specifikaciono polje procesora. `ProductTemplateService` koristi ga za `{model}`, dok `ProductDeletionService` centralizuje proveru poslovnih blokada, DB transakciju, audit i opciono post-commit uklanjanje `storage/app/public/products/{id}` direktorijuma. Destruktivna radnja ostaje ograničena postojećim Catalog ownership pravilima.

## v2.1.5 UX runtime i stabilnost dugih formulara

`public/assets/js/ux-runtime.js` je progresivni sloj iznad postojećih Blade formulara. Ne menja server-side pravila niti endpoint-e: povezuje validation poruke sa poljima, sprečava dvostruko slanje, postavlja `aria-busy`, podržava `Ctrl/Cmd + S`, upozorava na nesačuvane izmene samo na eksplicitno označenim dugim formularima i na telefonu prikazuje dock koji aktivira originalno submit dugme. Time sve postojeće FormRequest i controller provere ostaju autoritativne.

Migracija `000037` ne uvodi novo specifikaciono polje. Ona koristi postojeće zapise identifikovane stabilnim slugovima `desktop-racunar` i `snaga-napajanja`, dopunjava samo pivot vezu `product_type_fields` kada nedostaje i normalizuje redosled na korake od 10. `app:cms-v2-1-5-doctor --repair` ponavlja istu bezbednu proveru na produkcionim podacima. Ista komanda dodatno prolazi kroz Laravel route registry i potvrđuje da svaka controller ruta upućuje na postojeću klasu i metodu, čime se rano otkrivaju deploy regresije koje bi završile kao Error 404/500.

## v2.1.6 Performance & Data Quality

`CatalogReferenceCache` kešira read-only šifarnike kataloga i filter polja uz eksplicitno invalidiranje nakon administrativnih izmena. `User` kešira resolved role/permission skup samo tokom životnog ciklusa trenutnog modela/requesta.

`DataQualityService` sprovodi read-only audit i odvojenu nedestruktivnu popravku. Rezultati se čuvaju u `data_quality_snapshots`, a web pristup zahteva `catalog.audit`.
