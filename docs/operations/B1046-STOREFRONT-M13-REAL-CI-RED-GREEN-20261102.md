# B1046 — M13 public StoreFront catalog: real isolated merged-main RED-to-GREEN, no deployment

Date: 2026-11-02 (Europe/Belgrade)
Scope: source-only, exact older StoreFront PR111 into disposable latest-main PHP8.4/MariaDB10.11 runner.

## Owner direction: mail work postponed

Owner confirms that operational CMS emails for newly added products are already being delivered correctly and explicitly prioritizes StoreFront. Their real CloudLinux B1045 attempt printed `B1045_PHP=HOLD_NON_SMTP_MAIL_TRANSPORT` and `B1045_RESULT=HOLD_RUNTIME_PREFLIGHT` before a recipient or any email was sent. B1045 only insists the transport name equal `smtp`; this is an overly restrictive test policy and does NOT show the existing CMS email channel is broken. Do not retry B1045 or change .env/mail config to satisfy a redundant SMTP-only probe. Keep separate registration email/source provider checks for when real buyers can register; never claim this provider is independently verified by that owner's report.

## Canonical source and current topology

- GitHub main before B1046 source artifact work: e9f0c6a29f9840df102d0bcc2a634a19a66dd53f. Read canonical AGENTS.md and Drive ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md; GitHub docs/operations/000-LATEST.md remains known stale from B1032 due a prior write safety denial.
- Source StoreFront is existing draft PR111 at immutable head 3acc37e90332b0d8fde659503c5cc1a8b5fcc6aa, not merged or deployed. Current main has NO /katalog public routes or PublicShopController source. PR111 and the main are diverged (257 commits ahead / 367 behind when compared at this batch), so never blind-merge or copy a full historic repo tree over main.
- Exact Git blob identity for current-main Product.php and PR111 Product.php is the same: b67d8d47b6b0ad49e44f8b41b129cdbba3917b75. Ten exact StoreFront candidate blobs are pinned in the B1046 staging helper.
- M10/M11 production B962 and 80/80 B1044 CI remain completed; M17 owner-accepted and signed 16KiB AAB already PASS. NO repeat of previous release/build or tests.

## B1046 exact isolated source bridge

- Source stage script: https://github.com/AldinAga/ald1n-project/blob/main/tools/storefront/b1046/b1046_stage_catalog.py
- Source-only PHP/MariaDB tests: https://github.com/AldinAga/ald1n-project/blob/main/tools/storefront/b1046/B1046M13ReadOnlyCatalogIntegrationTest.php
- GitHub Actions workflow: https://github.com/AldinAga/ald1n-project/actions/workflows/b1046-m13-readonly-catalog-main-bridge.yml
- First source RED: https://github.com/AldinAga/ald1n-project/actions/runs/41535722205 ; actual PHP8.4.27/MariaDB10.11.19, 5 tests run, 4 PASS/1 FAIL, 49 assertions. Four passed controls covered OFF/default, synthetic safe listing, validated search, escaped product detail. Real failure: dynamic image response's Cache-Control was `max-age=0, no-store, public`, although candidate source intended `private, no-store, max-age=0`. This demonstrates an actual HTTP/cache directive mismatch, not one of the old B803 56 historic guest-contract failures.
- Targeted source fix in disposable stage ONLY: construct BinaryFileResponse, call `$response->setPrivate()`, then set `Cache-Control: private, no-store, max-age=0` at the end. The HTTP test was strengthened to independently require `private`, `no-store` and ABSENCE of `public`, without depending on header token order. No edit to the historic storefront PR's code, to active main CMS runtime or to any denied fixture.
- Second real source GREEN: https://github.com/AldinAga/ald1n-project/actions/runs/41536027493 at source commit 38b083c3afe98a9d31750235c9e9e452fc2105f0. Actual disposable PHP8.4/MariaDB CI: **5/5 unique HTTP tests, 51 assertions, 0 failures/errors/skips; route census 3/3 GET-only and source blob pins PASS**. Original JUnit 5 identities, route JSON and machine receipt archived as original ZIP, sha256 0a8dea1bd00595b21c057fa2e7bfdf71b84462f79948a6c8a10d4bc68aae910d. Receipt status `PASS_READONLY_M13_ISOLATED_MAIN_CANDIDATE_HTTP_GATE_ONLY`.
- Original CI artifact Drive: https://drive.google.com/file/d/1FJwsPJk3CjHUdRQFSuiFiSRk6kctSLSv/view
- Source helper v2 Drive: https://drive.google.com/file/d/106fvFY0vxSO-xeDK-u1MCtS9JN-G9LSY/view
- Test file v2 Drive: https://drive.google.com/file/d/1ICuohsSExYzUMGxREW8mq6vHoL4ZqoJg/view
- Initial source handoff Drive: https://drive.google.com/file/d/1CJ1VhfIMXMkjbvgguH8lugIm-b9Rexvh/view
- Exact active main CMS tree, CloudLinux, mail config, customers, product stock, production MariaDB and Google Play have NOT been changed. EAS paid Android builds: ZERO. No OTA, no public route deploy, no StoreFront ON.

## Next actual development tasks, no redundant tests

1. **M13 catalog code integration**: move only the accepted GET catalog, detail, media and read-only assets (with the cache privacy fix) onto an authorized current-main integration target, with OFF-by-default switch, namespaced throttles and correctly registered links. Avoid importing the old cart/checkout methods as publicly callable routes. Retest exact integration and browser accessibility on disposable/staging first. The B1046 result is proof that source bridges against current-main Product model; it is NOT itself the deployed integration.
2. **M14 cart**: preserve public browsing while only registration-verified active Kupac can create an order, never reinstate old anonymous sale; cart does not reserve stock and always reprices from current DB; idempotent checkout separate.
3. **M15 order completion and follow-up**: require verified active registered Kupac + tracked session; approved merchant identity, payment, delivery and legal URLs/receipt, origin-safe links, nonce/idempotency, stock ledger, valid access to private receipts. Registration email path should be tested specifically at that time, without a redundant generic SMTP trial.
4. **Original B803 56 failures** remain OPEN_NOT_WAIVED. Do not relax auth/session/CSRF, do not edit previously denied B815 or older B805/B809 fixtures via another path, do not skip tests to force PASS. Distinguish obsolete guest-positive expectations from real defects with independently approved case-by-case functional contracts. SEC23 CSP script restriction and Chrome/Firefox UAT (Issue101), full WSTG/ASVS L2 368-control register and controlled release remain mandatory before any StoreFront ON.

## Formal acceptance scope

B1046's M13 isolated HTTP gate is PASS only. M13–M15 still OPEN, B803 issue104 OPEN, M10/M11 owner-provider acceptance open, program acceptance remains 12/17=70.6% and CMS 10/15=66.7%, UI 6/6. No source merge, no production release or billable Android native build was authorized or performed.
