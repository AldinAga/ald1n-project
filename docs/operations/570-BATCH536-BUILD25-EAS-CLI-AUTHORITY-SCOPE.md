# Batch536 / Report570 - Build25 EAS CLI authority reconciliation

- Bound predecessor: Report569 SHA-256 `fdd61eaa59a40ad4e2081177b30bfc7abdd2e594f0151d2ee03947bed2c3e5d0`; integrated candidate `ee930fa179dae401afd40c44e6fe95f3e3c80e00`.
- Canonical authority is `AGENTS.md` EAS CLI `24.7.0`; the previous candidate pinned `24.8.0` in `eas.json` and the legacy direct-submit helper.
- Isolated audit branch updates exactly four files: `eas.json`, legacy direct-submit helper CLI pin, release authority tests, and EAS transport test fixtures.
- The direct production Build25 controller and legacy direct-submit execution remain **disabled**; no source was changed on canonical `main`.
- Canonical pinned npm-exec may run only `eas --version` in this batch; no EAS remote version query, EAS build, submit, OTA, Google Play mutation, CMS/database write, or GitHub Actions request.
- All Build25 native/device/signature/A03-A14 gates remain open until separately certified; controller cannot dispatch a build.
- Five other legacy npm scripts still use unpinned `eas-cli@latest` and were not changed here; a later scoped hardening review is needed before they may be used operationally.
- The prior independent native CI proof was for another source commit, not for this new candidate. Do not label this branch release-ready.
- Exact commands, tests, source SHA and Git remote verification are recorded in the server-side Report570.

**Production release:** `BUILD25_AUTHORIZED=NO`, `RELEASE_READINESS=BLOCKED_OPEN_RELEASE_GATES`.
