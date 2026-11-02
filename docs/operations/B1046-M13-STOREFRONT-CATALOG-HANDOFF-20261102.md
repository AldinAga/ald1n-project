# B1046 M13 StoreFront catalog: isolated current-main bridge, NO production activation

Date: 2026-11-02. Status at preparation: local YAML/Bash/Python/PHP syntax PASS. Real GitHub Actions M13 HTTP QA NOT_YET_RUN.

Owner direction: skip repeating generic mail tests and focus StoreFront; conserve EAS builds. Existing CMS outgoing new-product emails reported working by owner. B1045 `HOLD_NON_SMTP_MAIL_TRANSPORT` is a deliberately restrictive SMTP-specific preflight, NOT proof that functional emails fail. No B1045 retry, config mutation or mailer replacement is needed for M13.

Source-of-truth GitHub main before batch: e9f0c6a29f9840df102d0bcc2a634a19a66dd53f. Read repo AGENTS.md, stale 000-LATEST.md, canonical Drive ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md and Issue #104. Candidate is existing open draft PR #111 exact 3acc37e90332b0d8fde659503c5cc1a8b5fcc6aa, not merged. GitHub compare confirms PR111 and main diverged (257 commits ahead, 367 behind as of batch). DO NOT blindly merge or deploy the draft stack.

B1046 staging files under tools/storefront/b1046, isolated runner ONLY:
- b1046_stage_catalog.py: exact source blob checks for ten StoreFront files from PR111. Confirms Product model blob identical to current main; fail-closed if main now has a catalog route. Copies source into disposable main checkout, removes cart and registration links/forms from read-only preview views, appends exactly three GET routes to the temporary checkout, retains default OFF toggle. Does not alter GitHub main CMS runtime or CloudLinux.
- B1046M13ReadOnlyCatalogIntegrationTest.php: 5 real synthetic Laravel/MariaDB feature tests (default OFF/404, three GET only/no cart or checkout, visible priced product without internal purchase-price/commission, safe search validation, XSS escaping, image-cache privacy and revocation).
- b1046-m13-readonly-catalog-main-bridge.yml: GitHub Actions with exact candidate immutable ref + main checkout, PHP8.4/MariaDB10.11, Composer lock on disposable runner, source/app checkout guards, five-case strict JUnit census, sanitized report. No EAS, OTA, Play, SMTP or production DB.

All original B803 56 unresolved test identities remain OPEN, unchanged and not waived. Previously denied B815 fixture edits remain denied; B1046 does not fetch/alter/copy B815. SEC23 enforced CSP with browser UAT, merchant/commercial approval, registered-and-verified customer auth/session, payment+delivery, checkout idempotency, warranty and private order receipts remain separate before M14/M15 rollout. No storefront ON even if M13 isolated QA PASS.

M13 SUCCESS only means existing StoreFront catalog code can run with current-main Product model and locked Laravel dependencies, with all purchase routes ABSENT, disabled by default. It is not main source integration, approval, business UAT, production deploy or release of a shop. Next work: reconcile and implement M13 read-only catalog into an authorized source integration branch after review, then tackle M14/M15 tracked Kupac checkout and original B803 failure ledger under immutable contract without test suppression.
