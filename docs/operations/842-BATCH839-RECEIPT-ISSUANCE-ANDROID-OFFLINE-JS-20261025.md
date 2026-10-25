# 842 — B839 Registered receipt issuance + Android 16 KB offline JS smoke

Date: 2026-10-25 (Europe/Belgrade). Source and disposable CI only. NO PRODUCTION DEPLOY/BUILD/OTA/PLAY.

## Authority reconstruction

- Repo: `AldinAga/ald1n-project`; initial GitHub main SHA `cd7ad43bf5f5052bd7fee174dbc7734d71830220`.
- Read `AGENTS.md` and current Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md` and `docs/operations/000-LATEST.md`. Canonical latest index still begins B832 because prior update attempts were provider-denied. Respect that block; use numbered reports rather than retrying forbidden writes.
- B806 WSTG 4.2/ASVS5 L2 penetration gate remains mandatory before storefront public ON. B809 owner decision remains registered Kupac only. No edits to earlier safety-denied B809/B815 legacy fixtures.

## Functional correction A: issuance-time buyer and order state

- [Draft PR93](https://github.com/AldinAga/ald1n-project/pull/93) source head `666406a660a018a6579725c92ce297d1136e6b43`.
- `apps/cms/current/app/Services/PublicOrderAccessService.php`: a fresh private receipt capability cannot be minted for a blocked or retired buyer, or for an order already purged. Existing registered role + matching email + unique order conditions retained. This closes an issuance-time security gap distinct from read-time session checks.
- `apps/cms/current/tests/Feature/PublicRegisteredReceiptBoundaryB836Test.php`: four new regressions for blocked, retired, purged and valid active buyer receipt issuance; pre-existing six scenarios retained. First CI caught a test-only invalid MariaDB enum `inactive`; corrected to actual allowed `blocked` after checking UserController.
- **Exact-head isolated PHP8.4/MariaDB10.11 [CI 40182363902](https://github.com/AldinAga/ald1n-project/actions/runs/40182363902): 10 PHPUnit tests, 55 assertions, ZERO failures, PASS**. Source is still DRAFT/unmerged; not full storefront approval.

## Functional correction B: a genuine offline app smoke artifact

- [PR82](https://github.com/AldinAga/ald1n-project/pull/82) source head `bb9c9f8b66d1fe67432f47fab0268fe3375c82b0`, unmerged.
- Prior [full Android native CI 40180986542](https://github.com/AldinAga/ald1n-project/actions/runs/40180986542): all-ELF debug APK, zipalign, AAB and bundletool tests PASS, emulator 16KB kernel and APK installation PASS; test failed at `HOLD_ADB_APP_PROCESS_EXIT_1` seven seconds after launch. This is a **real app smoke HOLD**, not a native 16KB ELF failure.
- Official Expo/React Native docs confirm debug variants normally rely on Metro instead of embedding JS. Metro dependence is a plausible cause, not yet a proven sole root cause.
- CI now generates a **disposable, local Gradle release-variant APK** using `./gradlew :app:assembleRelease --no-daemon --stacktrace`; requires embedded `assets/index.android.bundle`, ELF >=16KB, APK ZIP alignment and an APK signature matching the locally generated **debug** signing identity. All checks fail closed; no EAS production build/Play signing, quota spend, store submission or OTA. The emulator installs this self-contained APK only after offline/airplane mode validation.
- ADB smoke now classifies a missing `pidof` result as APP_PROCESS_NOT_ALIVE, or an app-scoped AndroidRuntime fatal, without emitting raw logcat or private tokens. Two regressions added.
- Exact new-source [signed AAB/source CI 40182756609](https://github.com/AldinAga/ald1n-project/actions/runs/40182756609): **69/69 tests PASS**. [Full native and embedded-JS emulator CI 40182756640](https://github.com/AldinAga/ald1n-project/actions/runs/40182756640): IN_PROGRESS at checkpoint. Not app UAT PASS before completion.

## Archives and traceability

- [Google Drive combined source patch and README](https://drive.google.com/file/d/12NBA7I4Y0dOt90S-WzRK1aDKZjvP79bG/view?usp=drivesdk); local SHA256 `daf3de06d52ce62f0bba0f5082b3cbf5cfafe33f2f87a80f84e37ebf216f29d4`.
- [Google Drive Laravel-only issuance patch](https://drive.google.com/file/d/1eF5dcZeuZbTtigqz0fqh7anwjVotpNby/view?usp=drivesdk).
- GitHub PR82/PR93 contain authoritative source code. Patches are for archival/review only; never apply against a stale host checkout.

## Acceptance and safe gates

| Milestone | Accepted | Formal percentage |
|---|---:|---:|
| Total Ald1n program | 7 / 17 | 41.2% |
| Laravel CMS | 7 / 15 | 46.7% |
| Original UI requirements | 6 / 6 | 100.0% |

These are formal functional-acceptance ledger values, NOT code-completeness percentages.

- M16 HOLD: CI offline JS app smoke pending; fresh separately authorized signed EAS AAB with remote versionCode, signer/source verification, physical 16KB device UAT and Play acceptance still required.
- M17 Expo SDK58 upgrade only after M16 gates, no repeat of finished prior batches.
- Laravel storefront remains OFF: old global CI failures, merchant/SMTP, backup/restore, WSTG/ASVS penetration and production UAT unresolved. Production CMS remains B798.
- No SSH required. No CMS DB/production, EAS, Play, OTA or actual customer data touched.
