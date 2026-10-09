# Batch537 / Report571 - exact integrated Build25 native CI trigger

- Report570 SHA-256: `0dee05e5a9743d3d6f29fa0e3e75eef5e693d1378a783c57c8b8a1696ab736ea`.
- New audit CI branch parents **exactly** `a7564f7e59acf97393097b604a4922cd883a534b`, the successfully pinned Build25 integrated candidate.
- CI support is reused from successful independent native audit source `7ff4a5ee1dabfb09131893041de33731d26b11d7`, GitHub Actions run `37759246073` (passed for the former source, **not** sufficient for this new candidate).
- Copy-only from R3F: `batch528-ci.sh`, `batch528-native-snapshot.gradle`, `batch528r-composite-probe.mjs`, `batch528r-wiring.test.mjs`. Workflow `.github/workflows/ald1n-native-528.yml` is copied and retargeted to run only upon a push to `audit/build25-combined-native-ci-20261009`.
- Exact triggering Git SHA is checked out by the runner. Native CI exercises real Expo prebuild, generated API33 splash contract, :app lintRelease, Kotlin RED/GREEN, four ABI release compilation, bundleRelease, R8 execution, and evidence upload.
- Runner disk clean-up is intentionally limited to the ephemeral GitHub-hosted runner by `GITHUB_ACTIONS=true`; **no server cleanup**.
- No source code or dependency change, EAS build, production signing, OTA, Google Play submit, DB write or main push is permitted by this batch.
- The GitHub Actions result is **PENDING after this push**, not PASS. The exact run ID, triggering source SHA, final conclusion, R8/lint/compilation evidence and uploaded artifact must be independently verified before certifying the native gate.
- Signing acceptance, API24-32 / API33+ device splash, A03-A14 findings and Build25 operational controller remain separately blocked.

**Release status:** `BUILD25_AUTHORIZED=NO`. CI audit artifacts are not production-signed AABs.
