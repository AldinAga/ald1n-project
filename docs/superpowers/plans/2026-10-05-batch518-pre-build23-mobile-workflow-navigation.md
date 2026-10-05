# Batch518 Pre-Build23 Mobile Workflow Reliability & Navigation UX Implementation Plan

**Design:** `docs/superpowers/specs/2026-10-05-batch518-pre-build23-mobile-workflow-navigation-design.md`

## Dependency order

1. Batch517 Catalog Image Availability must PASS first.
2. Batch518A fixes APK upload + order continuation.
3. Batch518B completes navigation UX consolidation.
4. Fresh release-readiness.
5. Physical APK acceptance.
6. Only then Build23 may be authorized.

No EAS build, submit, OTA or Play action in 518A/518B.

---

# Batch518A - APK Upload & Order Continuation

## Task 1 - Payment proof transport RED contract

Create a dependency-free Mobile contract that requires:
- payment proof no longer appends raw `{uri,name,type}` object to ordinary fetch FormData;
- it uses Expo `File` and `apiExpoMultipartRequest`;
- auth/error semantics remain intact;
- Android content/file URI is accepted by the picker/transport path.

Run RED on source after Batch517 before modifying production code.

## Task 2 - Canonical multipart helper

Likely modify:
- `apps/mobile/current/src/lib/api/client.ts`
- `apps/mobile/current/src/lib/api/endpoints.ts`
- `apps/mobile/current/src/features/orders/order-post-create-files.ts`
- optionally a new `src/lib/api/multipart-file.ts`

Requirements:
- no manual multipart boundary;
- no base64;
- no full binary JS buffering;
- Expo File transport;
- timeout >= existing product-image upload timeout;
- preserve server error envelope/request ID.

Keep Product Image upload behavior unchanged or migrate it to the same helper only if behavior is byte/contract equivalent.

## Task 3 - Payment proof screen state

Modify:
- `apps/mobile/current/src/app/(app)/order/[id].tsx`

After successful proof:
- invalidate order + post-create queries;
- clear chosen file/form;
- show success;
- move/focus to payment/finance content;
- newly submitted entry must be visible without leaving screen.

## Task 4 - Shipment success continuation

Modify:
- `apps/mobile/current/src/features/admin/orders-admin-actions.tsx`
- `apps/mobile/current/src/app/(app)/admin/orders/[id].tsx`

Mutation command gains an optional post-success continuation intent.

Shipment success:
- set workspace `fulfillment`;
- refetch before presenting continuation;
- expose saved tracking number prominently;
- copy tracking action;
- open safe tracking URL action;
- next-step action becomes delivery confirmation when capability permits.

Do not derive shipment/delivery permission independently from backend actions/capabilities.

## Task 5 - 518A gates

- RED/GREEN contract.
- Mobile typecheck.
- validator zero FAIL.
- Expo check/Doctor.
- CMS 983/983 if backend/API untouched should still run as regression.
- OpenAPI parity read-only.
- no native dependency additions.
- no EAS/OTA/Play.

Expected commit:
`fix(mobile): harden payment proof upload and order continuation`

---

# Batch518B - Navigation UX Consolidation

## Task 1 - Navigation contract

Create a dependency-free navigation contract for:
- exactly five stable bottom tabs;
- direct common operational actions from Home/Admin;
- order detail contextual next step;
- admin order workspace direct navigation;
- explicit back/context behavior.

RED first against 518A source.

## Task 2 - Contextual next-step component

Create a reusable component/service such as:
- `src/components/workflow/next-step-card.tsx`
- `src/features/orders/order-next-step.ts`

Input is existing API actions/capabilities/status data.
Output is presentation/navigation only.

No duplicated business authorization rules.

Use on:
- customer order detail;
- admin order detail;
- optionally Home focus panel for high-value direct continuation.

## Task 3 - Admin order workspace navigation

Refactor six workspaces into compact horizontally scrollable chips/segmented controls with:
- current workspace clearly selected;
- direct links from Overview summary rows/cards to Isporuka/Finansije/Dokumenti;
- no extra Admin-home hop.

Tracking in fulfillment gets:
- large tracking number;
- copy button;
- open courier tracking button;
- shipment timestamp/courier nearby.

## Task 4 - Home/Admin shortcuts

Home:
- keep existing section hierarchy;
- promote permission-aware shortcuts to common task destinations;
- Porudžbine and operational work should be reachable in <=2 taps.

Admin hub:
- keep grouped modules;
- place frequent operational rows before lower-frequency configuration/system areas;
- search remains available.

## Task 5 - Back/origin behavior

For list/detail pairs:
- preserve origin route/filter parameters where practical;
- avoid duplicate push of identical route;
- use replace/back deliberately after successful creation/edit workflows;
- every detail screen has visible context and a predictable return path.

## Task 6 - 518B gates

- RED/GREEN navigation contract.
- Mobile typecheck.
- validator zero FAIL.
- Expo check/Doctor.
- CMS 983/983.
- OpenAPI parity.
- manual route inventory/smoke.
- no native dependency change.

Expected commit:
`feat(mobile): simplify operational navigation and next steps`

---

# Final pre-Build23 acceptance

After both PASS:
- fresh authority reconstruction;
- fresh release-readiness;
- physical installed APK acceptance for:
  - payment proof JPG;
  - payment proof PDF;
  - payment proof failure/oversize;
  - shipment with courier/tracking;
  - immediate Copy/Open tracking;
  - delivery continuation;
  - back navigation/order list context;
  - Home/Admin <=2 tap operational access.

Only after PASS is Build23 eligible.

## Release state remains locked until then

- app version `1.0.0`
- runtimeVersion `1.0.0-build17`
- last production versionCode `22`
- next expected versionCode `23`
- profile/channel `production/production`
- Build23 deferred
