# Report 843 - Parallel B840 Android M16, B841 verified Kupac, B842 SEC02

Date: 2026-10-25 (Europe/Belgrade). Source and isolated CI only.

## Reconstructed authority and safety

Repository: AldinAga/ald1n-project. Main SHA at batch intake:
0aed0ee1d574c6181c91b55831f2ef51e4aa8187.
Read current AGENTS.md, canonical Drive ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md,
docs/operations/000-LATEST.md and reports 841/842, plus B806 WSTG v4.2 / ASVS 5
Level 2 security plan. The older latest index begins B832 after an explicit
write denial. Do not circumvent that denial. This numbered report is the
separate evidence trail; not an implicit production release authorization.

No edits to safety-denied B805/B809/B815 old fixture paths. No production host,
real customers, payment, SMTP, EAS, Expo OTA, Google Play or public ON.

## B840 - Android 16 KiB source, AAB and real offline launch

PR82 exact source at checkpoint:
bb9c9f8b66d1fe67432f47fab0268fe3375c82b0.

Source-only signed AAB gate tests: GitHub run 40182756609, 69/69 PASS.
Independent real 16 KiB Android 15 emulator infrastructure: PASS
(run 40182756677; 16,384-byte guest page size). The original debug APK
(previous native run 40180986542) passed native ELF + AAB and was installed,
but the debuggable process failed to remain running offline. B839 replaced
that Metro-dependent variant with a standalone JS-bundled release-VARIANT
APK signed with a disposable local debug signer (never the production key).

Current full native/AAB/self-contained offline launch run 40182756640:
IN_PROGRESS at report checkpoint. Its native debug APK/ELF phase completed
successfully and AAB/bundletool step was in progress. Release APK bundle,
process PID survival, app-scoped fatal scan, signed production EAS AAB, real
remote versionCode, physical 16 KiB UAT and Play acceptance NOT YET ATTESTED.
M16 release remains HOLD. M17 Expo SDK 58 follows only after M16 closes.

## B841 - Verified activation required for every Kupac order boundary

Draft PR93 source commit:
c4a400ecb3e498889879eaffd8aec7088d1411d4.

Modified PublicOrderAccessService to require email_verified_at and
portal_activated_at before issuing or reading a registered Kupac receipt.
Modified PublicCheckoutController to apply the same rule to checkout GET/POST.
Modified PublicRetailCheckoutService to apply it in both the direct domain
entrypoint and the locked DB buyer re-read before recording inventory/order.
Three new isolated regressions: unverified buyer capability/read, never
activated buyer capability, and unverified checkout GET/POST with unchanged DB.

PHP 8.4 / MariaDB 10.11 exact-source CI run 40183556532 PASS:
13 tests, 67 assertions, zero failures. This is targeted regression success,
not storefront final acceptance.

## B842 - SEC02 hostile Host redirect and JSON negative controls

Draft PR93 exact source commit:
0ee7ceeb204e217800fcb3ea3ea41493b564c4f0.

Two separate tests check that the anonymous checkout login redirect does not
adopt a forged Host and that anonymous JSON checkout returns HTTP 401 without
a redirect or new order attempt. Existing source passed both; there was NO
confirmed SEC02 defect to fix. Do not claim a RED/GREEN vulnerability here.

Combined PHP8.4/MariaDB10.11 exact-source CI run 40183664742 PASS:
15 tests, 78 assertions, zero failures. All 15 include earlier B836-B841 tests:
this is a cumulative suite total, not 13+15 independently additive tests.

The full B806 WSTG/ASVS item-level penetration census, independent security
retest, global storefront CI, merchant/SMTP proof, backups/restore, production
PHP-FPM provenance and UAT remain outstanding. PR93 remains DRAFT/unmerged.

## Archival proof

Canonical code is in PR82 and PR93 at the listed commits. Combined source QA
archive, with B836-B842 reconstructed executable PHPUnit test and exact
matched Git blob f09fa1e28b7362659cbe1d413f0e950a785f0a6b:
https://drive.google.com/file/d/18bVRFglhyrQjBbCuOT47wT7i0u-p7jOX/view?usp=drivesdk

Android source workflow:
https://github.com/AldinAga/ald1n-project/actions/runs/40182756640
Laravel activation CI:
https://github.com/AldinAga/ald1n-project/actions/runs/40183556532
Laravel SEC02 CI:
https://github.com/AldinAga/ald1n-project/actions/runs/40183664742

## Canonical acceptance and next gates

Overall 7 / 17 = 41.2%; CMS 7 / 15 = 46.7%; original UI 6 / 6 = 100%.
These are accepted milestones, NOT percent of all implemented source code.

Next Android closure evidence: the exact-source isolated full native run must
complete its app-bundled offline 16 KiB emulator smoke; then separately
approved signed release AAB and physical/Play checks. Next CMS closure
evidence: full regression and OWASP security inventory/retests, merchant,
SMTP, restore, CloudLinux deploy/rollback, production UAT. Public storefront
stays OFF under B798. No user SSH operation is required by this report.
