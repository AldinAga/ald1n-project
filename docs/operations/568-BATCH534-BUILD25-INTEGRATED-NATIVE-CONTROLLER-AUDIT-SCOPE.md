# Batch534 / Report568 - isolated native plus release-controller integration

- Canonical main base: `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`; **main unchanged**.
- Existing native audit source: `7ff4a5ee1dabfb09131893041de33731d26b11d7` (Report562 SHA-256 `84f12740b6c507f1ed933d2d9778bd2dd1375bfa0fa104ac541570d668170244`).
- Existing controller audit: `e636c60fa77b9459300f1cd2a324b9ff61944298` (Report567 SHA-256 `ce289c9aa70d41f8b377bd0f4a0dd4fcc7253b16ecf972d23af7b38ff078fe67`).
- This is a new **audit-only** integration branch, parented to the successful controller audit candidate, not a production-ready build source.
- Native source and two native regression files were restored byte-for-byte from R3F; no CI workflow was copied.
- Release controller, CMS clean-clone fixture repair, and all Report567 committed sources are inherited without modification.
- Offline native/source tests, controller tests, Mobile TypeScript/validator, CMS static 983/983 and OpenAPI parity must pass before an audit-only push.
- No GitHub-hosted Actions are requested; no EAS CLI execute, production build, submit, OTA, Google Play action, CMS/database mutation or main push.
- Independent native CI proof for the **combined** source is still required before release. Previous native CI proof on `7ff4a5ee1dabfb09131893041de33731d26b11d7` does **not** certify this combined commit.
- EAS CLI pin conflict remains unresolved: AGENTS.md requires 24.7.0; eas.json requires 24.8.0.
- Host JDK signing test is advisory evidence only and never authorizes a production AAB. Device API 24-32/33+ splash verification and all remaining A03-A14 release findings stay open.
- Canonical server still has exact historical staged docs and other source residue; the Build25 controller will fail closed on dirty source.
- Command-level evidence is in server report `/home/icaffeco/ald1n-project/incoming/568-BATCH534-BUILD25-NATIVE-CONTROLLER-INTEGRATION-20261009-084743-4174776.md`.

**Release readiness:** `BLOCKED_OPEN_RELEASE_GATES`; `BUILD25_AUTHORIZED=NO`.
