# Ald1n Operations - Current Canonical State

- Canonical directory: `docs/operations`
- Archive policy: **append-only** for numbered reports.
- Current application version: **1.0.0**
- Current runtimeVersion: **1.0.0-build17**
- Current source authority after Batch520: `dff990823507719d5e2f476523cdeeb9e15cdd5b`
- Last existing Android production build: **Build22 / versionCode 22**
- Build profile / channel: **production / production**
- Build23: **DEFERRED**; no build/submit/OTA/Google Play action performed.
- Future Build23 release mode: **production build with AutoSubmit** after all gates and explicit owner authorization.
- Google Play release notes: **required as copy/paste text at Build23 completion**.
- Batch520: **PASS** — subagent shipment tracking visibility + Push/E-mail/Both preference.
- Next gate: **Google Play pre-Build23 compliance/native audit (API 36, R8/resource shrinking, 16 KB readiness, adaptive/edge-to-edge, image/memory), then fresh release readiness and physical acceptance before explicit Build23 AutoSubmit authorization.**

## Batch520 - Subagent Shipment Tracking Notifications

- Status: **PASS**
- Source commit: `dff990823507719d5e2f476523cdeeb9e15cdd5b`
- Creator/subagent sees shipment tracking prominently near the top of the customer order detail with courier, shipped time, copy and safe HTTPS tracking actions.
- Notification settings expose exactly Push, E-mail or Push + E-mail for shipment tracking alerts; no disabled/none option is introduced.
- Canonical OrderShipmentService sends the creator/subagent shipment alert using the chosen channel while keeping a persistent in-app event record.
- Push selection reuses existing device registration; email reuses existing operational notification mail channel.
- Existing supplier/operational order email flow remains separate and creator duplication is suppressed for the shipment event.
- CMS static 983/983, Mobile typecheck/validator, Expo checks and OpenAPI parity passed.
- Production migration applied only after verified backup.
- No EAS build, submit, OTA or Google Play action.

---

---

## Batch518A - APK Upload & Order Continuation

- Status: **PASS**
- Source commit: `e55cbc9d9062c96f405d83e57a592ae5b545776e`
- Android payment-proof upload now uses the existing Expo File + Expo multipart transport instead of legacy React Native uri/name/type multipart.
- Successful customer proof upload refreshes order/payment context and leaves explicit success feedback on the same order.
- Successful admin shipment now continues directly to Isporuka, where tracking is prominent with copy and safe HTTPS open actions.
- CMS static remains 983/983; Mobile typecheck/validator, Expo and OpenAPI parity gates passed.
- No schema/database mutation, EAS build, submit, OTA publish or Google Play action. Build23 remains deferred.
- Next grouped batch: Batch518B Navigation UX Consolidation.

---

## Batch517 - Catalog Image Availability & Legacy Media Publication

- Status: **PASS**
- Source commit: `6ddb27ba26bf893ca1659f814eaa02398b607556`
- Legacy product images for the targeted production SKU set are published to canonical public storage with validated originals and derivatives.
- Catalog API primary-image presentation now falls back to the first ordered image when historical primary flags are missing.
- The authenticated legacy Web media route remains private; Mobile continues using public thumbnail/display/original URLs.
- Production repair was dry-run gated and, when needed, executed only after a verified database backup.
- CMS static remains 983/983; Mobile/Expo/OpenAPI gates passed.
- No EAS build, submit, OTA publish or Google Play action. Build23 remains deferred.
- Next grouped chain: Batch518A APK payment-proof upload + order continuation, then Batch518B navigation UX consolidation.

---


## Batch516 - Financial State Canonicalization

- Status: **PASS**
- Source commit: `318c0cb9f7225f2f2139f72debbb5723872e6316`
- `order_payments` is the canonical received/refunded money ledger; order financial status fields are derived through one projector.
- Manual Web/API/Mobile payment-status mutation is removed; real ledger operations remain authoritative.
- GET order detail no longer persists IPS cache; downstream IPS/receivables reconciliation runs after canonical mutations.
- Controlled production derived-state repair completed only after dry-run census and verified database backup.
- CMS static remains 983/983; Mobile/Expo/OpenAPI parity gates passed.
- No EAS build, submit, OTA publish or Google Play action. Build23 remains deferred.

---


## Batch515R3 - Customer Order Amendment Recovery V3

- Status: **PASS**
- Source commit: `5ceb1cbc43aaeaa132fe9abc2e4a1ea8e2555d27`
- Customer can amend products, quantities, delivery data and note until shipment/completion/cancellation lock.
- Inventory deltas, idempotency and optimistic concurrency protect retries and stale user/admin screens.
- Order documents now persist immutable item snapshots; customer amendment invalidates active confirmation/proforma/invoice for later revision.
- Payment ledger is preserved; payment state, receivables and commission workflow are reconciled with the amended subtotal/items.
- Laravel product save labels use canonical UTF-8 `Sačuvaj` copy.
- Controlled migration `2026_10_03_000200_create_order_document_items_batch515.php` applied after DB backup and source gates.
- No EAS build, EAS submit, OTA publish or Google Play action. Build23 remains deferred.

---


## Planned Build23 command

```bash
/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package="eas-cli@24.8.0" -- eas build --platform android --profile production --non-interactive --wait --json
```

## Google Play - Napomene o verziji

Poboljšana kompatibilnost i stabilnost aplikacije na Android uređajima.
Ažurirane sistemske komponente u okviru Expo SDK 57.
Dodatna interna poboljšanja pouzdanosti i performansi.

---

## Batch513R2 - Shipment / Delivery Dual Action UX Recovery V2

- Status: **PASS**
- Source commit: `3cbd0d0a67023352292020722441b29185649ca4`
- Recovery: Batch513 stopped in preflight on pre-existing audit/runtime residue; Batch513R then reached CMS static and exposed a stale beta7.7 literal sentinel. Batch513R2 preserves the same domain logic, keeps residue read-only, and aligns that static regression sentinel with the approved dual-action UX.
- Laravel order detail now exposes one logistics action hub with separate confirmation for shipment sent and shipment delivered.
- Mobile exposes the two primary shipment/delivery actions vertically, with COD-aware delivery wording.
- Existing shipment, completion and payment domain semantics are preserved.
- No migration, database mutation, EAS build, EAS submit, OTA publish or Google Play action.
- Build23 remains deferred.
