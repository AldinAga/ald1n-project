# Batch518 Pre-Build23 Mobile Workflow Reliability & Navigation UX Design

## Goal

Before Release Build23, fix two owner-reported production APK problems and make operational navigation substantially more natural:

1. payment-proof upload from the installed Android APK is unreliable/non-functional;
2. after operational actions such as confirming a shipment, the user loses the obvious path to tracking and the next action.

This is a grouped Mobile workflow/UX feature chain and must be completed before fresh Build23 release-readiness.

## Authority

- Batch516 application source authority entering current grouped work: `318c0cb9f7225f2f2139f72debbb5723872e6316`.
- Batch517 Catalog Image Availability is planned first and remains the next execution batch.
- Batch518 runs only after Batch517 PASS/recovery closure and fresh reconstruction.
- App version stays `1.0.0`.
- runtimeVersion stays `1.0.0-build17` unless later native analysis proves a change is mandatory.
- Build22/versionCode22 remains last production build until Build23.
- No Build23 until Batch517 + Batch518 are PASS and a fresh release-readiness gate passes.

## Verified source findings

### Payment proof

Customer order payment-proof upload currently builds a classic React Native multipart payload:

`FormData.append('proof', { uri, name, type })`

and sends it through ordinary `apiRequest/fetch`.

The project already has a newer APK-safe pattern for Product Image upload:

- Expo `File(uri)`
- `apiExpoMultipartRequest`
- Expo fetch multipart transport.

Batch518 standardizes payment-proof upload on the proven Expo file transport instead of maintaining two Android multipart implementations.

### Shipment/tracking UX

Admin order detail currently has six workspaces:

- Pregled
- Kupac i stavke
- Isporuka
- Finansije
- Dokumenti
- Tok i akcije

Tracking exists inside the Isporuka workspace and inside the shipment action form. Generic mutation success closes the action panel and only shows a success notification. It does not intentionally move the user to the Isporuka workspace or surface the saved tracking number as the immediate continuation.

This creates the reported “where do I go now?” experience.

## Part A - APK file upload reliability

### A1. One canonical Mobile multipart file transport

Use `expo-file-system` `File` + `apiExpoMultipartRequest` for payment proof.

Do not manually set multipart Content-Type/boundary.

Preserve:
- Bearer auth;
- amount/date/reference/note fields;
- server endpoint and backend validation;
- file limits/MIME/extensions.

### A2. Android URI support

The proof picker/transport must explicitly cover Android APK URI forms including `content://` and file URIs returned by Expo file picker.

Before upload:
- selected file must exist/read;
- nonzero size;
- permitted MIME/extension;
- filename sanitized/preserved for server metadata.

No base64 conversion and no full-file JS memory buffering.

### A3. Shared file upload helper

Where practical, extract a small reusable Mobile multipart-file helper so Product Image and payment proof use the same primitive. Do not refactor unrelated upload flows unless contract coverage proves they use the same safe primitive.

### A4. UX state

Payment-proof flow must visibly distinguish:
- choosing file;
- selected file;
- uploading;
- upload success;
- upload failure with server/request ID where available.

On success:
- clear form;
- refresh order + post-create financial data;
- scroll/focus user to Payments section or show a direct “Otvori uplate” continuation.

## Part B - Natural operational navigation

### B1. Keep the stable primary tabs

Keep:
- Početna
- Katalog
- Porudžbine
- Obaveštenja
- Nalog

Do not add another permanent tab for Admin/Tracking.

### B2. Contextual “Sledeći korak”

Order detail and Admin order detail get a prominent workflow card near the top:

`Sledeći korak`

It derives from server capabilities/current state, not duplicated business rules.

Examples:
- waiting for payment -> “Dodaj / proveri uplatu”;
- ready to ship -> “Evidentiraj slanje”;
- shipped -> “Prati pošiljku” and/or “Potvrdi isporuku”;
- completed -> “Dokumenti / reklamacija / garancija” as permitted.

The card contains at most 1 primary + 1 secondary continuation to avoid action overload.

### B3. Post-action continuation

After successful shipment:
- action panel closes;
- order query refreshes;
- workspace automatically becomes `fulfillment`;
- shipment summary is brought into view;
- saved tracking number is shown prominently;
- actions: `Kopiraj broj`, `Otvori praćenje` when a safe tracking URL exists;
- “Sledeći korak” changes to delivery confirmation when permitted.

After payment-proof success:
- stay on same order;
- focus Finance/Payments context;
- show newly submitted proof and status.

After delivery confirmation:
- remain on same order and expose completion status/documents, not dump user back into a generic list.

### B4. Direct workspace navigation

The six admin order workspaces remain as information architecture but become easier to reach:
- compact horizontally scrollable chips/segmented navigation;
- active workspace visually obvious;
- tracking/payment/documents can be reached by direct action links from Overview;
- no requirement to return to Admin home between related order tasks.

### B5. Navigation hierarchy and back behavior

Introduce consistent screen context:
- clear title + entity identifier;
- meaningful Back behavior to the immediate origin list/search screen;
- avoid stacking duplicate copies of the same route;
- preserve list filter/search state when returning from detail where Expo Router allows it without global hacks.

### B6. Admin discovery

Admin hub remains grouped, but high-frequency operational entries are promoted:
- Porudžbine
- Isporuke
- Direktna prodaja
- Potraživanja

Home “Brze akcije” should link directly to the most common permitted workflow rather than forcing Home -> Admin -> group -> list for every operation.

No permission bypass: visibility continues to come from existing capabilities/permissions.

## Acceptance scenarios

### Payment proof APK

On a physical Android APK:
1. open an order eligible for payment proof;
2. choose JPG/PNG/PDF from Android picker;
3. selected file is displayed;
4. upload succeeds;
5. proof appears in ledger/post-create refresh;
6. proof can be opened again through secure download;
7. cancellation and invalid type/oversize produce readable errors.

### Shipment continuation

1. open an admin order ready to ship;
2. choose courier;
3. enter tracking number;
4. confirm shipment;
5. without leaving the order, Isporuka workspace is shown;
6. saved tracking number is immediately visible;
7. Copy/Open tracking actions are directly available;
8. next operational action is obvious.

### Navigation smoke

For SuperAdmin and normal permitted operator:
- bottom tabs remain stable;
- Home -> common operational action is <=2 taps to relevant list/workspace;
- Order list -> order -> payment/shipment/tracking -> back preserves understandable context;
- no dead-end page without a visible next/back action;
- permission-hidden modules stay hidden.

## Implementation split

Batch518 is one feature chain but may execute as two consecutive source batches if scope is safer:

### Batch518A - APK Upload + Order Continuation
Critical correctness:
- payment proof Expo multipart transport;
- upload state/error handling;
- post-shipment direct tracking continuation;
- post-payment direct Finance continuation;
- contract/typecheck/validator.

### Batch518B - Navigation UX consolidation
Broader UX:
- contextual next-step card;
- compact order workspace navigation;
- direct Home/Admin shortcuts;
- consistent header/back/context behavior;
- navigation regression contract.

Both must PASS before Build23 release-readiness. No Build23 between A and B.

## Native/build assessment

The currently identified fixes use Expo SDK57 libraries already present in the application (`expo-file-system`, Expo Router, existing UI primitives). Therefore the design does not currently require adding a native dependency or changing runtimeVersion.

This must be revalidated during implementation. If a new native dependency is discovered, stop and revise release plan before Build23.

## Gates

Each implementation batch:
- dependency-free RED/GREEN contract for behavioral change;
- Mobile typecheck;
- Mobile validator zero FAIL;
- Expo install check;
- Expo Doctor;
- CMS 983/983 when shared API/backend contract is touched;
- OpenAPI parity if contract touched;
- exact source scope;
- no EAS build/submit/OTA/Play.

Final after Batch518B:
- fresh production release-readiness;
- physical APK acceptance specifically for payment proof + shipment/tracking flow;
- only then authorize Build23.
