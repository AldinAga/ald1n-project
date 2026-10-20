# Punjači za laptopove — kurirani šifarnik, source-only

Zasebna Draft PR implementacija. **Nema automatske migracije ili izmene produkcione baze.**

- Sistemsku kategoriju i tip proizvoda nazvati **Punjači za laptopove**, slug `punjaci-za-laptopove`.
- Postojeći laptop brendovi se nasleđuju **samo ako već imaju type-scope vezu za laptop**. Ne kreirati izmišljene brendove.
- Postojeće linije proizvoda predstavljaju **tip konektora** specifičan za brend, bez vraćanja ukinutih Product Variants.
- Polje **Snaga napajanja (W)** koristi već postojeći slug `snaga-napajanja`; ne praviti duplikat. Tip je na aktivaciji 100% kompletan: obavezni su brend, konkretan konektor i snaga u W.
- `php artisan app:catalog-laptop-chargers --dry-run` je jedino dozvoljeni podrazumevani režim, bez upisa. `--apply` tek posle posebne autorizacije produkcionog Laravel/MariaDB/host izvoda. CLI komandni poziv sa produkcionog hosta dostaviti isključivo preko kanonskog `incoming/` skripta uz izveštaj u `docs/operations/` i SHA-256.
- Tipovi: Dell (7,4 × 5,0; 4,5 × 3,0; USB-C), HP (7,4 × 5,0; plava 4,5 × 3,0; USB-C), Lenovo (pravougaoni Slim Tip 11,0 × 4,5; tanki 4,0 × 1,7; okrugli 7,9 × 5,5; USB-C); dopunski ASUS, Acer, MSI, Apple, Microsoft, Huawei, Honor, Samsung, Toshiba, Dynabook.
- Okrugli priključci: spoljašnji × unutrašnji prečnik (mm). Lenovo Slim Tip: širina × visina (mm). USB-C/USB PD, MagSafe i Surface Connect su tipovi standarda, **bez proizvoljnih mm**.
- **Nisu automatski kompatibilni samo na osnovu oblika**: pri prodaji proveriti model laptopa, napon/polaritet, centralni ID pin, jačinu struje, snagu u W, USB-C PD profil i kabl.

Izvori:
- https://www.hp.com/us-en/tech-takes/laptops/how-to/how-to-choose-right-charging-cord-hp-laptop.html
- https://pcsupport.lenovo.com/us/en/products/laptops-and-netbooks/lenovo-b-series-laptops/b70-80/solutions/pd014168-ac-dc-and-acdc-power-adapter-charger-reference-guide
- https://www.thinkwiki.org/wiki/Power_Port
- https://hotspotpro.rs/kako-da-odaberete-odgovarajuci-punjac-za-laptop/
- https://support.apple.com/en-gb/102397
- https://support.microsoft.com/en-us/surface/battery/how-to-charge-surface

QA: PHP 8.4, disposable MariaDB 10.11.19, type/brand/unit tests, real provisioning/idempotency integration, static source checks. EAS builds, OTA, Play, produkciona baza i `main` su HOLD.
