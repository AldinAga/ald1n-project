# Report576 / Batch542 - exact Report575 P1/P2 offline remediation

- Bound to Report575 SHA-256 `92fa58454845a2c09e0615b9a5c862722bb3d5cbbf71d794e8d5928fe6b6ad2b` with `BATCH_RESULT=PASS_DEEP_DIAGNOSTIC_AUDIT_WITH_ACTIONABLE_FINDINGS_NOT_RELEASE_READY`.
- Source parent: `7c4e4ef435b46a65bc0e0db57dd74509a62a5e89`; observed `main`: `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`.
- **P1 `SIGNATURE_TEST_MISSING_FROM_EARLY_CI`:** add existing real JDK `verify-signature.test.mjs` negative/positive cryptographic regression to cheap native CI after JDK17 and before Android SDK, with explicit java/jar/keytool/jarsigner/zip/unzip tool availability check. Does not produce/use production signing credentials. Does not request CI.
- **P2 `OPERATIONS_LATEST_POINTER_STALE`:** add current Build25 audit authority to `docs/operations/000-LATEST.md`, preserve old snapshot under clearly labeled historical section, label obsolete Build23 EAS command, and state remote Android versionCode is unverified. Historical numbered operation reports remain unchanged.
- Test-first RED on parent, GREEN after scoped patch, existing cheap CI contract, native source, release controller, Mobile TypeScript/validator, CMS 983/983, Expo check and three OpenAPI parity; crypto test itself is BLOCKED on hosting where JDK signing tools are absent and will run only in separately authorized Actions after its JDK setup.
- Only changed paths: `.github/workflows/ald1n-native-528.yml`; `apps/mobile/current/scripts/release25/batch576-report575-remediation.test.mjs`; `docs/operations/000-LATEST.md`; and this scope document.
- The native workflow still triggers exclusively on nonexistent `audit/build25-native-ci-explicit-trigger-20261009`; its uploader is hard disabled. Never push the trigger branch without owner approval and cost quota review.
- Remain BLOCKED: billed GitHub native CI for exact new SHA, EAS remote versionCode, upload signing cert, native AAB/16 KB, physical devices, A03-A14, owner billing/Play/source merge authority.
- Absolutely no real build, production signing, EAS build/submit/update, Play, OTA, database mutation, artifact deletion, CI dispatch, or production/main checkout mutation.

**Overall Build25 estimated progress: 65% before -> 65% after, delta 0 percentage points; not release ready.**
