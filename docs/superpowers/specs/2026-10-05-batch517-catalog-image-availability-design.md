# Batch517 Catalog Image Availability & Legacy Media Publication Design

## Goal

Close the known production class where a product has CMS-visible images but Mobile catalog/detail cannot render them because legacy `product_images` rows resolve through the session-authenticated Web `media.product` route.

Batch517 must preserve the existing Product/Image API shape, existing Mobile image fallback order, Product Variants decommissioning, and Build23 deferral.

## Authority entering Batch517

- GitHub `main` docs head after Batch516 operations checkpoint: `feaaa95a067092d154a8ef05e98378422155aa7a`.
- Application source authority: `318c0cb9f7225f2f2139f72debbb5723872e6316`.
- Batch516: PASS.
- App version: `1.0.0`.
- runtimeVersion: `1.0.0-build17`.
- Last Android production build: Build22 / versionCode 22.
- Build23 remains deferred.
- No EAS build, submit, OTA publish or Google Play action in Batch517.
- No schema migration planned.

## Verified source finding

`ProductImage::url` returns `route('media.product', image)` for `storage_disk=legacy`.

That route is inside the Web middleware group:

- `auth`
- `active`
- `tracked-session`

CMS browsers can therefore render legacy images through their Web session, while ordinary `expo-image` requests do not use the authenticated API binary transport and cannot rely on the Web session. Public product images and generated public derivatives do not have this mismatch.

## Scope

### 1. Presentation fallback hardening

Add a product presentation-image relation that selects the first ordered image with primary images preferred, but does not require `is_primary=1`.

Catalog list eager-loads that relation and `ProductResource` uses it for the existing `primary_image_*` fields. This protects Mobile/CMS API presentation from historical rows that have images but no valid primary flag.

No API field names change.

### 2. Controlled legacy publication service

Create a service that can publish one validated legacy `ProductImage` into canonical public storage.

Rules:

- never expose the legacy Web media route publicly;
- validate legacy root and path with the existing `ProductImageDerivativeService::sourceAbsolutePath()` authority;
- allow only JPEG, PNG and WebP;
- deterministic target path by product/image identity;
- copy atomically through a temporary file;
- compute SHA-256, MIME and size from the copied public file;
- update only `storage_disk`, `file_path`, `mime_type`, `file_size`, `file_hash` for that image row;
- preserve original filename, sort order, primary flag and image ID;
- generate/refresh thumbnail and display WebP derivatives after publication;
- if publication fails before the DB row is switched, leave the legacy row unchanged;
- if derivatives fail after a valid public original exists, report failure rather than deleting the original.

### 3. Catalog image availability doctor

Add:

`php artisan app:catalog-image-availability-doctor`

Options:

- repeatable `--sku=<SKU>`
- `--apply`

Dry-run reports per selected product and aggregate markers:

- product found/not found;
- image row count;
- legacy/public row counts;
- source-file validity;
- zero/multiple primary state;
- presentation image availability;
- missing thumbnail/display derivatives;
- whether Mobile presentation is safe without a Web session.

Apply rules:

- only selected SKUs are mutated in Batch517 production execution;
- stop before DB mutation if any selected product image has malformed/missing source;
- normalize primary flag only when the product has image rows and primary count is not exactly one;
- publish every valid legacy image row for the selected product;
- regenerate missing derivatives for public rows;
- rerun dry-run and require all selected products Mobile-safe.

The command remains reusable for future arbitrary `--sku` values and must not hardcode the production SKU list internally.

### 4. Production target set for Batch517

Batch517 execution audits and, only when safe, repairs these previously reported SKUs:

- `LATITUDE-551P`
- `HP-630-G10`
- `ThinPad-T15-Gen2`
- `SSD-256GB-M2`
- `T490S-TOUCHSCREEN`
- `ASUS-TUF-GAMING`
- `DELL-LATITUDE-5440`
- `SAMSUNG-RAM-MEMORIJA-DDR4-8GB-SKHYNIX-MICRON-POLOVNO`

The command itself remains generic.

If an SKU is not found because the historical spelling changed, the batch must report it and stop before applying data changes. Do not guess a replacement product.

### 5. Mobile parity

Existing Mobile image components already consume:

1. `primary_image_thumbnail_url`
2. `primary_image_display_url`
3. `primary_image_url`

and product detail consumes image-level thumbnail/display/original fallbacks.

Batch517 does not create a second Mobile media transport. Instead it repairs the backend/public-media authority so the existing Mobile contract works.

Add/adjust Mobile validator coverage so the public thumbnail/display/original fallback remains required.

### 6. Static/OpenAPI contract

- No OpenAPI field/path change is planned.
- All three OpenAPI copies must remain byte-identical.
- CMS static remains exactly 983 checks; strengthen an existing image/media sentinel rather than increasing the count.
- Product Variants remain decommissioned.

## TDD / dependency-free contract

Create a Batch517 contract that is RED on pre-Batch517 source and GREEN only when:

- Product has presentation-image fallback relation;
- catalog list eager-loads it;
- `ProductResource` uses it for primary image fields;
- publication service exists and refuses unsafe storage/path semantics;
- doctor exists with dry-run/apply and repeatable SKU option;
- Web `media.product` route remains authenticated (not opened publicly);
- Mobile fallback order remains intact;
- no Product Variants contract is restored.

## Production data gate

Before `--apply`:

1. run targeted dry-run for all eight SKUs;
2. require every selected SKU to exist;
3. require every image source row to be readable and safe;
4. require no unsupported storage disk or MIME;
5. create and verify canonical manual DB backup;
6. only then run targeted `--apply`;
7. rerun dry-run and require zero Mobile-unsafe selected products.

No product business data, stock, pricing, orders or ledger rows may change.

## Commit / release policy

Expected source commit:

`fix(catalog): publish legacy product images for mobile`

Expected next operations report numeric prefix: `519-`.

No version, runtimeVersion or versionCode bump. No EAS/OTA/Play action. Build23 stays deferred.
