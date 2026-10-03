# Ald1n Operations - Current Canonical State

- Canonical directory: `docs/operations`
- Archive policy: **append-only** for numbered reports.
- Current application version: **1.0.0**
- Current runtimeVersion: **1.0.0-build17**
- Release-readiness source authority before final docs-only commit: `a283c1a028d84a49d15d19bab534c5cc08ad3807`
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
- Latest report: `docs/operations/512R2-FRESH-RELEASE-READINESS-RECOVERY-20261003-110013.md`
- No EAS build, EAS submit, OTA publish, Google Play action, migration, or database mutation was performed by Batch512R2.
- Next gate: **one controlled Android production Build23**, followed by explicit submit using the exact returned EAS Build ID.

## Planned Build23 command

```bash
/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package="eas-cli@24.8.0" -- eas build --platform android --profile production --non-interactive --wait --json
```

## Google Play - Napomene o verziji

Poboljšana kompatibilnost i stabilnost aplikacije na Android uređajima.
Ažurirane sistemske komponente u okviru Expo SDK 57.
Dodatna interna poboljšanja pouzdanosti i performansi.
