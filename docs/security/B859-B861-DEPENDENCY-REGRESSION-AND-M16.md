# B859–B861: full locked-dependency regression proof and Android 16 KB acceptance boundary

Date: 2026-10-25. All work is **isolated CI/source documentation only**. Neither a public catalog activation nor a signed Android production release is authorized.

## B859 — exact-version dependency upgrade non-regression

Existing [Draft PR100](https://github.com/AldinAga/ald1n-project/pull/100) carries the B858 lockfile patch that changed 51 locked packages to eliminate all 19 composer advisories. The exact **pre-patch PHP source** is `6af1e49df4f96d8a7fb284b3f93cc7c90075c264`; the only CMS tracked file changed from that source is `apps/cms/current/composer.lock`. Do not adapt protected B805/B809/B815 tests or remove checkout security controls.

The `b859-composer-full-suite-delta.yml` workflow performs *two actual complete 574-case* PHP8.4/MariaDB10.11.19 runs against identical application source and PHP test code: once with the original lockfile, then with patched PR100 dependencies. Both historical suites may be RED. The Python verifier fails closed if test case identities drift, counts are not **574/574**, cases are skipped or duplicated, XML is invalid, evidence SHAs lack exact commit provenance, Composer lockfiles are identical, new failure/error identities occur, or additional warnings appear. It reports explicit failure identity differences; a scoped no-new-regression PASS **never** means that all CMS tests or any OWASP penetration controls passed.

This directly resolves the open question left by 41/398 scoped B858 tests: did the 51-package upgrade introduce a **new full-suite regression**? Independently, the [B803 exact-head baseline/candidate CI](https://github.com/AldinAga/ald1n-project/actions/runs/40194344679) had baseline **451 cases / 44 failures or errors**, and candidate **574 cases / 98 failures or errors** with **56 new failing identities**, still an unconditional public-storefront release HOLD. The 56 issues remain unwaived. One B815 test edit was explicitly denied; no indirect edits, exclusions or alternative fixtures are permitted.

## B860 — fail-closed synthetic test identity controls

Adversarial unit tests ensure the B859 verifier refuses a disappearing or duplicate case, a new failure, a new warning, any skipped test, bad XML, version/source drift, and a misleading unchanged lockfile. Existing failing cases are preserved in exact prepatch and patched JUnit files in GitHub Actions artifacts with SHA-256 provenance. **A zero *delta* is not a clean full suite.** Uncertainty about the cause of any single historical checkout failure remains explicit unless independently proven.

## B861 — Android 16 KB native technical QA is complete, M16 release is not

Authoritative [PR82 exact-head full native CI #40192813557](https://github.com/AldinAga/ald1n-project/actions/runs/40192813557) completed **SUCCESS** at source commit `6d11b766131ca3cfa946a984b1cc9ed834c00239`. This is a genuine new outcome since Report848:

- Full Kotlin/Fabric bridge compilation PASS with SHA-256-pinned upstream v2.0.11 Liquid Glass AAR.
- Generated Android debug APK: 16 KiB ELF LOAD alignment for all packaged 64-bit native libraries and zipalign PASS.
- Generated disposable debug AAB: bundletool 1.18.3 `PAGE_ALIGNMENT_16K`; derived universal APK ELF/zipalign/signature checks PASS using a **new temporary local CI signer**.
- Complete isolated APK with bundled JavaScript created under a local debug key, installed and launched offline on Android 15 `google_apis_ps16k` emulator; result `PASS_CI_EMULATOR_16KB_OFFLINE_LAUNCH_ONLY`.
- App identity v1.5.0, package `com.ald1n.mobile`, production channel and proposed runtime `1.5.0-native2` verified as *source configuration*, NOT as an existing signed EAS artifact. The CI output explicitly states `NO_EAS_BUILD=YES`.

**Still mandatory for M16 acceptance:** the canonical backend warranty-expiry owner/migration rollout; clean exact-source release integration; authenticated EAS project and remote `versionCode` attestation (**currently unknown**, and new value must exceed Play-rejected 27); a separately authorized new EAS-signed production AAB with **independent source/build ID and signing-certificate provenance**, post-build binary 16 KiB checks; full real-device Liquid Glass/accessibility UAT and Google Play acceptance. No production EAS build, Play submit, OTA, CloudLinux SSH or merge occurred in this batch. SDK58/M17 must remain deferred until M16 release gates actually close.

## Accounted-for security and milestone status

- B857/B858 source-only Composer audit **0 advisories and 0 abandoned**, isolated 41 PHPUnit / 398 assertions PASS. This does not patch running CloudLinux production until authorized deployment.
- B844 security register still contains **368 WSTG v4.2/ASVS 5 L1–L2 items marked UNASSESSED/NOT_RUN**. No independent full penetration-test certification.
- Formal Report796 milestone acceptance ledger is unchanged: **7/17=41.2% overall**, **7/15=46.7% CMS**, **6/6=100% basic UI**. Technical QA progress is real, but source/CI does not equal production acceptance.
- Existing Draft PR93, Draft PR99, and Draft PR100 all remain open; Android PR82 unmerged. No credential reuse, user data, SSH, real payments, email, OTA, EAS or Google Play mutation.
