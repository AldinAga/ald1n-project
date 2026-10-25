# Report 845 — B845/B846 scoped buyer replay negative tests and evidence gate

Date: 2026-10-25 (Europe/Belgrade). Source/CI-only work. No production mutation or operator SSH.

## Authorities
Fresh `main` at intake: `09c7176f1d96a46c649a98fbc551cd4d71c5b37f`; root AGENTS.md, `docs/operations/000-LATEST.md`, Report844 and canonical Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md` read. Index heading remains at B832 after an earlier denied direct edit; DO NOT bypass provider denial. Latest numbered operations report before this report: 844. PR93 canonical source head before extension: `acb8b9c42ec9254b1a27567abca29a26c1f733f8`, Draft, unmerged. PR82 16 KiB source head `bb9c9f8b66d1fe67432f47fab0268fe3375c82b0`; full native/offline emulator PASS previously established. B798 production storefront OFF.

## B845: real CI finding and bounded resolution
- New Draft [PR99](https://github.com/AldinAga/ald1n-project/pull/99), based exactly on PR93 source, originally `31fe0fc66a9c8f9a5525daef3ee16ebe43cd464f`: added six additional synthetic negative replay tests to existing B843 suite (changed address/fingerprint, missing encrypted token, altered token hash, reassigned order buyer, blocked buyer, emergency storefront OFF), kept one order/attempt/access/stock movement invariant and unchanged remaining stock.
- [Initial CI 40187241458](https://github.com/AldinAga/ald1n-project/actions/runs/40187241458) failed in new *test expectation only*: blocked buyer caused 401 (middleware authentication revocation), test incorrectly required 403. Application correctly denied replay. No change to production controller/permissions, no bypass.
- Minimal correction `c4912edc66e7b6231044ea553c2bf3bad5d2916b` changes this one assertion to `assertUnauthorized()` and documents why. [Exact-head GitHub Actions 40187415146](https://github.com/AldinAga/ald1n-project/actions/runs/40187415146) **SUCCESS**: B843 replay 11 tests/271 assertions, B836 private receipt boundary 15 tests/78 assertions: **26 tests/349 assertions PASS** on synthetic PHP8.4/MariaDB10.11.19. B846 evidence-compiler tests **10/10 PASS**; expected JSON marker `B846_SCOPE=26_TESTS_RELEASE_HOLD`. Proven by immutable exact CI job logs/JUnit.
- [Google Drive CI evidence ZIP](https://drive.google.com/file/d/1KHcENS8BeLs2CT5rFLKyTjG7XkshwU4m/view?usp=drivesdk), original GitHub Actions artifact ID `12597305295`, uploaded and Drive metadata read back (18189 bytes).

## B846: evidence boundary
- Fail-closed JUnit evidence parser checks six named additional negatives, 11 replay and 15 receipt-boundary cases; rejects missing/duplicate/skipped/failed/errored cases and wrong source SHA and issues scoped JSON provenance with JUnit SHA-256. This does **not** certify any OWASP item: B844's **368** WSTG4.2/ASVS5 L1/L2 controls remain `UNASSESSED/NOT_RUN`; no completed independent penetration test.
- Existing full B803 and B802/B806 workflows from PR99 still have inherited failures from older guest-positive expectations/specific B809 fixture. [B806 40187241486](https://github.com/AldinAga/ald1n-project/actions/runs/40187241486) failed on B809 `StorefrontRegistrationOnlyB809Test` expected 200, got 403; safety-denied fixture must not be modified/bypassed. No full-suite GREEN claim. B802 [40187241496](https://github.com/AldinAga/ald1n-project/actions/runs/40187241496) retains 8 historic failures. Auto-concurrency cancellation is not a pass.
- PR99 remains DRAFT, unmerged. No production host, DB, SMTP, EAS, OTA, Play, source merge, SSH, or customer access. Do not run B804/B805 again; protected project production files and rules remain unchanged.

## Progress and next gates
- Functional acceptance **7/17 = 41.2%** overall, **7/15 = 46.7%** CMS, original UI **6/6 = 100%**. Scoped security regression improved, but zero newly accepted milestones.
- Next: independent item-level 368 control applicability and isolated negative testing, retain historic failure identities without test suppression, full exact-head CI and independent audit; merchant policy, real SMTP and safe backup/restore/FPM/CloudLinux/owner acceptance before public ON.
- Android M16 requires a separately authorized signed EAS production AAB (versionCode > 27), artifact provenance/16 KiB checks, physical UAT and Google Play acceptance. M17 SDK58 starts after M16. No user SSH step at this source-only stage.
