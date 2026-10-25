# 841 — B838 registered-buyer receipt revocation and Android M16 reconciliation

Date: 2026-10-25, Europe/Belgrade. Source/CI only. No production checkout, deployment, EAS build, OTA, Play upload, SMTP, real user order, SSH, or public storefront enablement.

## Canonical reconstruction

- Repository `AldinAga/ald1n-project`; verified `main` `84c58b47fa34c4528f9323e6ba837e2919cf429b` at start of B838, and current PR82/PR93 heads verified before writes.
- Read `AGENTS.md`, `docs/operations/000-LATEST.md` (stale B832 due to prior explicit provider safety denial), latest numbered reports #839/#840 and canonical Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md` (B806 requirements).
- **Do not bypass previously safety-denied edits to B809/B815 guest/session fixtures**, and do not rewrite `000-LATEST.md` through another path. Existing full storefront CI remains HOLD.
- Production CMS B798; public storefront OFF. PR93 is DRAFT; PR82 is OPEN/UNMERGED.

## Actual source and functional work

- PR93 `apps/cms/current/tests/Feature/PublicRegisteredReceiptBoundaryB836Test.php` B838 commit `dbc8c4fc5996cfd1f3eb879267fb7e1310265d78` adds two independent synthetic cases without editing restricted fixtures:
  1. Logged-in registered buyer's **revoked** receipt token must return 404; expiry then returns 404; order and token ledger unchanged.
  2. Live SuperAdmin public-storefront OFF makes valid logged-in buyer's receipt, document and warranty paths 404; reopening restores valid original receipt and leaves order unchanged.
- Exact-source isolated [MariaDB CI40181781970](https://github.com/AldinAga/ald1n-project/actions/runs/40181781970) **PASS 6 tests / 45 assertions / 0 failures**. Existing B836 baseline was 4/25, so this is +2 tests and +20 assertions.
- [B838 source patch + README archive](https://drive.google.com/file/d/1XN1Xgr8S6AxSVhTCZHV_8nHlo0RTFNHt/view?usp=drivesdk) SHA256 `b4f394a481d11564cf3bc04a2555c68f84fcb31ae3fa6ef720b701050a6403e4`. The ZIP contains a diff as archival context; canonical executable test is already in PR93 and must not be blindly re-applied.

## Android M16—evidence vs release acceptance

- PR82 source `b9f569280d99a9f1e6b0cb24f2427f9d6bc69b9f`. Its exact-head [16KB emulator infra CI40180986597](https://github.com/AldinAga/ald1n-project/actions/runs/40180986597) **PASS**: actual PAGE_SIZE 16384, Android API35, x86_64, flight mode. **No application installed or launched in infrastructure job**.
- [Signed AAB source CI40180986589](https://github.com/AldinAga/ald1n-project/actions/runs/40180986589): 67/67 tests PASS. Other 4 source-only workflows green at review checkpoint (5/6 total).
- [Full native debug APK+AAB+emulator CI40180986542](https://github.com/AldinAga/ald1n-project/actions/runs/40180986542) **IN_PROGRESS during B838 checkpoint**; Kotlin/Fabric compilation PASS, Gradle assembleDebug stage still running. Do not call app installation, AAB EAS signing, runtime stability, 16KB physical-device or Play approval PASS before evidence.
- M16 remains HOLD: actual debug app install/start and signed fresh production AAB, remote versionCode, signing identity, production derived APK and physical 16KB UAT/Play.
- M17 Expo SDK58 remains blocked behind M16 and backend/release gate.

## Formal acceptance and next gate

- Canonical Report796 acceptance ledger unchanged: **overall 7/17 = 41.2%; CMS 7/15 = 46.7%; original UI 6/6 = 100%**. These are acceptance milestones, not source-code completeness estimates.
- B838 advanced a concrete additional scoped Laravel authorization regression; PR93 full suite still HOLD due old/denied fixture conflicts and comprehensive B806 merchant/SMTP/pen-test/restore/UAT gates.
- Next mobile step: inspect final CI40180986542 result and precise stage-coded ADB failure or full emulator PASS. Do not trigger another duplicate native build without that evidence.
- No operator SSH step is necessary in this batch.
