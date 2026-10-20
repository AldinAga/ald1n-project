# Punjači za laptopove — kurirani tipovi priključaka (Draft)

Data-only Laravel migration: \`2026_10_20_154800_add_laptop_charger_catalog.php\`.
No schema change, no existing product mutation, no variant editor and NO production migration without a separate release gate.

- Product type + category: **Punjači za laptopove**, shared slug \`punjaci-za-laptopove\`.
- Brand associations: **only brands already associated with an existing laptop/notebook product type**. Do not invent or auto-add brands.
- Existing \`brand_product_type\` and \`product_line_product_type\` implement per-brand **Tip priključka**. Existing product editor shows this label only for the new charger type. Existing laptop family lines are never automatically copied into this type.
- Existing numeric \`snaga-napajanja\` specification is **reused** and required for charger articles, unit W. No duplicate "Watt" field. Source test installs missing historical field get it once with the canonical slug; existing incompatible field causes a clear HOLD.
- New data is idempotent. Intentional inactive lines are not reactivated; unknown brand names are not assigned unverified connector types. \`down()\` never deletes existing catalog records.
- **Safety:** matching barrel tip does *not* prove charger compatibility. Confirm original voltage, polarity, center/ID pin and sufficient wattage on the exact laptop before sale. USB-C requires suitable USB-C PD and adequate rating. MagSafe generations are different.

## Connector notes / reference sources (research on 2026-10-20)

| Brand | Labels | Dimensions/reference |
| --- | --- | --- |
| Dell | Debela igla, Tanka igla, Type-C | 7,4×5,0; 4,5×3,0 mm; https://www.delltechnologies.com/asset/en-us/products/electronics-and-accessories/technical-support/client-peripherals-dell-parts-eguide.pdf |
| HP | Debela igla, Plava igla, Type-C | 7,4×5,0; 4,5×3,0 mm; https://www.hp.com/gb-en/tech-takes/accessories/how-to/how-to-choose-right-charging-cord-hp-laptop.html |
| Lenovo | Kocka/Slim Tip, Tanka, Okrugli, Type-C | 11×4,5 mm (vendor dimension), 4,0×1,7; 7,9×5,5; Lenovo confirms rectangular Slim Tip: https://www.lenovo.com/us/en/p/accessories-and-software/chargers-and-batteries/chargers/0b46994 ; vendor dimensions: https://gdtechnologies.co.za/products/lenovo-ideapad-y50-ac-adapter |
| ASUS / Acer | Selected known round tips + Type-C | https://hotspotpro.rs/kako-da-odaberete-odgovarajuci-punjac-za-laptop/ |
| Apple | MagSafe 1, 2, 3, Type-C | Distinct magnetic connector generations, not round mm dimensions: https://support.apple.com/en-ie/102397 |
| Microsoft | Surface Connect, Type-C | https://support.microsoft.com/en-US/surface/battery/usb-c-and-fast-charging-for-surface |
| MSI, Huawei, Honor, Samsung, Toshiba/Dynabook | Common curated tip types, where brand already linked to laptop | https://blicshop.rs/kfd-65w-18-5v-20v-3-5a-max-univerzalni-adapter-za-laptop ; vendor crosschecks required for exact laptop model |

A type name or connector label is NOT a compatibility guarantee or claim that all laptop models support USB-C power input. Physical matching, output voltage and model compatibility remain a sale-time manual verification step.
