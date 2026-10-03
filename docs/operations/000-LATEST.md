# Ald1n Operations - Current Canonical State

- Canonical directory: `docs/operations`
- Archive policy: **append-only** for numbered reports.
- Current application version: **1.0.0**
- Current runtimeVersion: **1.0.0-build17**
- Current source authority after Batch513R2: `3cbd0d0a67023352292020722441b29185649ca4`
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
- Latest report: `docs/operations/513R2-SHIPMENT-DELIVERY-DUAL-ACTION-UX-RECOVERY-20261003-120531.md`
- No EAS build, EAS submit, OTA publish, Google Play action, migration, or database mutation was performed by Batch512R2.
- Build23: **DEFERRED BY USER** so additional grouped functionality can be included before spending another production build.
- Next gate: **continue grouped source work; run fresh release readiness again before any future Build23.**

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
