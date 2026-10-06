# Ald1n Operations - Current Canonical State

- Canonical directory: `docs/operations`
- Archive policy: **append-only** for numbered reports.
- Current application version: **1.0.0**
- Current runtimeVersion: **1.0.0-build17**
- Current source authority after Batch518A: `e55cbc9d9062c96f405d83e57a592ae5b545776e`
- Last existing Android production build: **Build22 / versionCode 22**
- Build22 EAS ID: `b32bfcbd-5f4c-45f5-95a9-5b756749b31a`
- Build22 source commit: `e082641bd4e58c320d4faca0dd50997deca7f416`
- Build profile / channel: **production / production**
- SDK57 patch alignment: **PASS**, commit `4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5`
- Payment/IPS settlement repair: **PASS / CLOSED**, commit `d69605e6d09ebe9be14c104eef257769c5ebaac4`
- Production submit procedure hardening: **PASS**, commit `350ee82767edd4b6a1cf989b94567dc4db903e29`
- Batch512 first attempt: **FAIL before release gates; audit preserved**
- Batch512R: **FAIL at historical-report-sync; audit preserved**
- Batch512R2 fresh release readiness: **PASS**
- EAS remote Android versionCode at readiness: **22**
- Next expected versionCode if one new production build is executed: **23**
- Latest report: `docs/operations/522-BATCH518A-APK-UPLOAD-ORDER-CONTINUATION-20261006-083557.md`
- No EAS build, EAS submit, OTA publish, Google Play action, migration, or database mutation was performed by Batch512R2.
- Build23: **DEFERRED BY USER** so additional grouped functionality can be included before spending another production build.
- Next gate: **continue grouped source work; run fresh release readiness again before any future Build23.**

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
