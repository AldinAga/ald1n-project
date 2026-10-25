# Report847 — B850–B853: full Laravel regression triage + Android M16 exact source provenance

Date: 2026-10-25 (Europe/Belgrade). **Read-only Laravel forensic audit; Android PR82 CI-only source edits. No production mutation, no EAS/OTA/Play, no hosting SSH.**

## Fresh source and rules
- Main at intake: `f7aeee1ca7e6da8f2066442b58a24535aea029ee` (Report846, highest previous numeric report); GitHub `AGENTS.md`, `docs/operations/000-LATEST.md`, Report846, PR93/99/82 and exact Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md` read. The `000-LATEST.md` heading remains stale at B832; an earlier direct modification was safety-denied. **No bypass or retry**. This new numbered report is current evidence authority.
- CMS candidate PR99 remains DRAFT, exact `062462a5767881b915360f1dc202e2cc2c2c3a00`, stacked on Draft PR93 `acb8b9c42ec9254b1a27567abca29a26c1f733f8`. Production is still B798, storefront **OFF**.
- Android M16 PR82 before changes: `bb9c9f8b66d1fe67432f47fab0268fe3375c82b0`; after B853 source change: `6d11b766131ca3cfa946a984b1cc9ed834c00239`. Only two existing GitHub Actions workflow files changed. No release state changed.

## B850: independently obtained exact raw B803 CI evidence
- Recovered complete immutable [full-suite B803 CI #40191150069](https://github.com/AldinAga/ald1n-project/actions/runs/40191150069), original artifact ID `12598381021`, ZIP SHA-256 `6e6fbccaf724d2e411c3f07ab03791b503813a4dbb8eefcc14012ad832b664e6`, size 363132 B.
- [Original raw CI archive on Drive](https://drive.google.com/file/d/1g7cbCWlL9WuZvvRoJBbADTGehmT8gjW5/view?usp=drivesdk) uploaded and read back at 363132 B. Archive contains baseline/candidate JUnit, raw logs, full-suite-comparison.json, runtime-manifest.json and source-head.txt. No silent synthetic fixture modifications.

## B851: strict differential failure reconstruction from actual JUnit
- Baseline runtime commit `c4a5f060cc00b5d1be0e4d8068309524a4b99c99`, candidate source `062462a5767881b915360f1dc202e2cc2c2c3a00`. Inputs independently checked against runtime manifest and source-head.txt.
- Real JUnit identities cross-verified against machine comparison: baseline **451 cases / 44 failed**, candidate **574 cases / 98 failed**; **56 newly failing IDs**, **2 prior failed cases fixed**, **42 persistently failing baseline identities**.
- Of the **56 new**, 20 test identities were already present in baseline and 36 newly present only on candidate. For detailed review, strictly by test name/category *only*: 13 explicitly contain "guest"/"anonymous"; 1 is provider-protected `BuyerCheckoutSessionBoundaryB815Test::test_storefront_off_remains_404_before_any_session_gate`; 1 registered buyer 403 `StorefrontRegistrationOnlyB809Test::test_only_registered_customer_can_buy_and_form_email_cannot_be_forged`; **41 remain unclassified/unwaived**. These classes do NOT establish root cause or justify skipping any failure.
- Baseline and candidate logs include both old guest assumptions and tests for authenticated checkout; **all 56 remain RELEASE HOLD**. No fake whole-suite green, no weakened authorization, no ignored test failures.

## B852: reusable offline evidence parser + adversarial safety tests
- Implemented offline `triage_b803_evidence.py` with fail-closed SHA/head binding, JUnit case-identity validation, differential set-equivalence proof, duplicate/skipped/invalid/full-suite-false-pass refusal, CSV+JSON+MD output and explicit release HOLD.
- **12/12 local synthetic unit tests PASS** (`test_triage_b803_evidence.py`); independently run real source artifact analysis returned `B850_B852_B803_EXACT_INTEGRITY_PASS_GLOBAL_RELEASE_HOLD`.
- Script SHA-256 `12ee4028ef3cb8d5ab4262103964c5f8bf2191f7fcdf38175da35c5537baa853`; tests SHA-256 `3cdad2a036ec0cd2815edc833d7880330065da28c59e71bda8b0ee2b2b12778b`; output package SHA-256 `cc966b1575ab8e5c31acb3aed8c7016da2d85f50318187877a5845bb383e5ba8`.
- [Google Drive analysis ZIP](https://drive.google.com/file/d/10hfWk56jtMTFwOyu8vO_WNlml7XCDbhK/view?usp=drivesdk) (13320 B, Drive verified) contains Python script, unit tests, full 56-row CSV, original-bound JSON and explanatory Markdown. This is a diagnostic bundle, not production PHP code.
- A proposed separate real-HTTP CSRF probe edit was **provider safety-denied**; not committed, run, or reattempted. Actual standalone HTTP CSRF testing remains not completed. No alternative route was used to bypass the denial.

## B853: repair Android exact-source QA provenance without building
- Two current PR82 workflows had `actions/checkout` with no explicit source ref; a `pull_request` event may default to synthetic merge state instead of tested head. Fixed scoped workflow files:
  - `.github/workflows/android-v15-nativegauss-16kb-qa.yml`: pin event SHA via `${{ github.event.pull_request.head.sha || github.sha }}` (supports both PR and push).
  - `.github/workflows/android-v15-signed-aab-gate-source-qa.yml`: pin `${{ github.event.pull_request.head.sha }}` (PR-only).
- In both, new fail-closed step checks `git rev-parse HEAD` against event SHA and emits exact source marker. Applied as one atomic GitHub commit `6d11b766131ca3cfa946a984b1cc9ed834c00239` on **existing PR82**; branch not merged. No source application code changed.
- [Signed AAB source gate CI #40192813594](https://github.com/AldinAga/ald1n-project/actions/runs/40192813594) **PASS** at new PR82 head: 69/69 Node tests, zero failures; printed `B853_EXACT_GITHUB_SOURCE_COMMIT=6d11b766131ca3cfa946a984b1cc9ed834c00239`. It explicitly reports `SIGNING_CERTIFICATE_AND_AAB=NOT_SUPPLIED_SOURCE_ONLY`.
- [Native candidate source QA #40192813569](https://github.com/AldinAga/ald1n-project/actions/runs/40192813569) **PASS**, 283/283 Node tests. [Native 16 KB compiler/ELF/Android emulator CI #40192809779](https://github.com/AldinAga/ald1n-project/actions/runs/40192809779) was IN_PROGRESS during report drafting and is not claimed PASS. Other workflow statuses require exact head follow-up.
- Play rejected former signed v1.5.0 versionCode27 runtime `1.5.0-native1`. New production profile/channel would be `production/production`, app v1.5.0, Android `com.ald1n.mobile`, gated runtime `1.5.0-native2`; **remote versionCode UNVERIFIED**; no new EAS-signed AAB built. Physical 16 KB and Google Play acceptance remain HOLD, SDK58 postponed.

## Mandatory guardrails, progress and handoff
- No GitHub PR merge, no production-host checkout changes, no real mail/order/customer access, no live DAST, no EAS native build/OTA/Play, no cleanup or SSH scripts. No user SSH required at this stage.
- B844 368 OWASP WSTG4.2 + ASVS5.0.0 L1/L2 controls remain **UNASSESSED / NOT_RUN**; security PASS count is 0. Additional synthetic tests are not an independent penetration-test certificate.
- Program accepted: **7/17 = 41.2%**, CMS **7/15 = 46.7%**, original UI **6/6 = 100%**. Neither forensic classification nor source QA changes count as an additional production acceptance milestone.
- Next work: independent review/closure of 56 exact source-diff regressions without touching provider-denied tests; complete OWASP applicability/isolated active tests and rollback/backup/SMTP/merchant/physical release gates; finish PR82 native CI then only after approval run fresh signed EAS production AAB, artifact checks, device and Play gates.
- Sequence: Report846 predecessor -> Report847 this. Historical operation log available on GitHub, no CloudLinux artifact deployed.

BATCH_RESULT=PASS_SCOPED_FORENSICS_AND_ANDROID_SOURCE_GATE
SOURCE_MUTATION=PR82_CI_WORKFLOWS_ONLY
SOURCE_COMMIT=6d11b766131ca3cfa946a984b1cc9ed834c00239
BUILD_CREATED=NO
GOOGLE_PLAY_ACTION=NONE
PRODUCTION_PROMOTED=NO
NEXT_ACTION=B803_56_UNWAIVED_FAILURES_AND_ANDROID_NATIVE_CI_RECHECK
