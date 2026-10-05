# Batch517 Catalog Image Availability & Legacy Media Publication Implementation Plan

> Execute only after fresh authority reconstruction. This plan is docs-only; application source authority entering execution remains Batch516 source commit.

**Goal:** Make CMS-visible legacy product images reliably render in Mobile without weakening Web authentication or introducing a second Mobile media transport.

**Spec:** `docs/superpowers/specs/2026-10-05-batch517-catalog-image-availability-design.md`

## Locked authority

- Batch516 PASS.
- Application source authority: `318c0cb9f7225f2f2139f72debbb5723872e6316`.
- GitHub main may include later docs-only Batch517 planning commits.
- App version `1.0.0`.
- runtimeVersion `1.0.0-build17`.
- Last production Android build Build22 / versionCode 22.
- Build23 deferred.
- No EAS build, submit, OTA, Play action, version bump, runtimeVersion change, native dependency change or schema migration.
- CMS static target exactly 983/983.
- All three OpenAPI copies remain byte-identical.

## Task 1 - Dependency-free RED contract

**Create**
- `apps/cms/current/bin/batch517-catalog-image-availability-contract.php`

RED must prove pre-Batch517 source lacks:
- Product presentation-image fallback relation;
- controlled legacy publication service;
- catalog image availability doctor.

GREEN must additionally prove:
- `media.product` stays inside authenticated Web middleware;
- ProductResource keeps existing field names;
- Mobile keeps thumbnail -> display -> original fallback;
- Product Variants remain absent.

## Task 2 - Presentation image fallback

**Modify**
- `apps/cms/current/app/Models/Product.php`
- `apps/cms/current/app/Services/CatalogQueryService.php`
- `apps/cms/current/app/Http/Controllers/Api/V1/CatalogController.php`
- `apps/cms/current/app/Http/Resources/ProductResource.php`

Implementation:
- add `presentationImage()` relation using primary-first then sort-order/id;
- eager-load it for catalog list;
- detail loads it alongside full `images`;
- map existing `primary_image_url`, `primary_image_original_url`, `primary_image_display_url`, `primary_image_thumbnail_url` through presentationImage;
- no response field rename/addition required.

## Task 3 - Controlled legacy publisher

**Create**
- `apps/cms/current/app/Services/ProductImagePublicationService.php`

Behavior:
- accepts a locked/known ProductImage;
- no-op for already-public rows after validating source and ensuring derivatives;
- for legacy rows, resolve source through `ProductImageDerivativeService::sourceAbsolutePath()`;
- validate MIME JPEG/PNG/WebP;
- deterministic target `products/{product_id}/legacy-{image_id}.{ext}`;
- copy to a temporary file outside final destination, fsync/size/hash validation where practical, then atomic rename/copy fallback;
- transactionally switch DB row to public metadata only after public original exists;
- preserve image identity/filename/order/primary;
- generate/refresh derivatives;
- throw on unsupported storage or unreadable source;
- never delete legacy source.

## Task 4 - Generic audit/repair doctor

**Create**
- `apps/cms/current/app/Console/Commands/CatalogImageAvailabilityDoctorCommand.php`

Signature:
`app:catalog-image-availability-doctor {--sku=*} {--apply}`

Dry-run:
- requires at least one SKU;
- exact SKU lookup only;
- report one machine-readable line per product;
- count image rows, storage classes, valid source rows, primary count, missing derivatives;
- classify `MOBILE_SAFE=YES/NO`;
- aggregate markers:
  - `TARGET_PRODUCTS=`
  - `MISSING_PRODUCTS=`
  - `INVALID_SOURCE_IMAGES=`
  - `UNSUPPORTED_STORAGE_IMAGES=`
  - `MOBILE_UNSAFE_PRODUCTS=`
  - `LEGACY_IMAGES=`
  - `PUBLIC_IMAGES=`
  - `MISSING_DERIVATIVES=`

Apply:
- pre-scan all targets first;
- if any missing product, invalid source, unsupported disk/MIME, or image-less target exists, fail before mutation;
- DB transaction per product for primary normalization;
- publish legacy rows through publication service;
- ensure derivatives for public rows;
- rerun internal audit and return failure unless all selected products Mobile-safe.

No hardcoded target SKU list in command.

## Task 5 - Static and Mobile regression guards

**Modify**
- `apps/cms/current/bin/static-check.php`
- `apps/mobile/current/scripts/validate-project.mjs`

Static-check:
- replace/strengthen one existing media check without changing total count 983;
- assert presentation-image relation, publication service and authenticated legacy route contract.

Mobile validator:
- assert ProductCard fallback order remains thumbnail -> display -> base URL;
- assert detail gallery keeps thumbnail/display/original fallback;
- do not add a second authenticated image transport.

## Task 6 - Production target audit and safe repair

Batch runner target SKUs:

1. `LATITUDE-551P`
2. `HP-630-G10`
3. `ThinPad-T15-Gen2`
4. `SSD-256GB-M2`
5. `T490S-TOUCHSCREEN`
6. `ASUS-TUF-GAMING`
7. `DELL-LATITUDE-5440`
8. `SAMSUNG-RAM-MEMORIJA-DDR4-8GB-SKHYNIX-MICRON-POLOVNO`

Execution order:
1. fresh GitHub/main + 000-LATEST + report518 + AGENTS + spec/plan reconstruction;
2. machine-stable worktree preflight;
3. source backup;
4. dependency-free RED;
5. source patch;
6. exact scope + PHP/Node syntax;
7. GREEN contract;
8. CMS static 983/983;
9. Mobile typecheck + validator;
10. Expo check + Doctor;
11. OpenAPI parity;
12. targeted image doctor dry-run;
13. hard stop on any missing/unsafe target;
14. canonical manual DB backup + verify;
15. targeted `--apply`;
16. targeted post-repair dry-run requiring zero Mobile-unsafe target;
17. rerun contract/static/mobile/OpenAPI gates;
18. source commit/push;
19. operation report + 000-LATEST docs checkpoint.

## Expected source scope

- `apps/cms/current/app/Models/Product.php`
- `apps/cms/current/app/Services/CatalogQueryService.php`
- `apps/cms/current/app/Http/Controllers/Api/V1/CatalogController.php`
- `apps/cms/current/app/Http/Resources/ProductResource.php`
- `apps/cms/current/app/Services/ProductImagePublicationService.php`
- `apps/cms/current/app/Console/Commands/CatalogImageAvailabilityDoctorCommand.php`
- `apps/cms/current/bin/batch517-catalog-image-availability-contract.php`
- `apps/cms/current/bin/static-check.php`
- `apps/mobile/current/scripts/validate-project.mjs`

OpenAPI files are read-only parity gates for Batch517.

## Commit/report

Source commit:
`fix(catalog): publish legacy product images for mobile`

Operation report:
`519-BATCH517-CATALOG-IMAGE-AVAILABILITY-<timestamp>.md`

Required report markers:

```text
BATCH_RESULT=
FAILED_STAGE=
SOURCE_MUTATION=
COMMIT_CREATED=
PUSH_COMPLETED=
SOURCE_COMMIT=
TARGET_SKUS=
IMAGE_AUDIT_BEFORE=
DB_BACKUP=
IMAGE_REPAIR_APPLIED=
IMAGE_AUDIT_AFTER=
CMS_STATIC=
MOBILE_TYPECHECK=
MOBILE_VALIDATOR=
EXPO_CHECK=
EXPO_DOCTOR=
OPENAPI_PARITY=
PHPUNIT_FEATURE_TESTS=
APP_VERSION=1.0.0
RUNTIME_VERSION=1.0.0-build17
VERSION_CODE_LAST_PRODUCTION=22
PROFILE_CHANNEL=production/production
EAS_BUILD_STARTED=NO
EAS_SUBMIT_STARTED=NO
OTA_PUBLISHED=NO
GOOGLE_PLAY_ACTION=NO
BUILD23_DEFERRED=YES
NEXT_ACTION=
```

## Recovery rule

Do not pre-create R/R2 variants. On FAIL, bind exact report SHA-256 and continue from recorded source/DB/commit state without repeating successful DB publication or commits.
