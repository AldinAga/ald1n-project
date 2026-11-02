# B1047 — M13 StoreFront source-only integration and real-browser QA

Date: 2026-11-02 · Repo: `AldinAga/ald1n-project` · Priority: public M13 read-only catalog.

## Owner decision and prior evidence

User requested continued StoreFront development and explicitly declined redundant generic SMTP probes. The observed B1045 `HOLD_NON_SMTP_MAIL_TRANSPORT` was a pre-send transport-name check; it did not prove the existing CMS outbound notifications were broken. Do not run B1045 again or change mail configuration for this catalog batch.

The original exact draft candidate is PR111 `3acc37e90332b0d8fde659503c5cc1a8b5fcc6aa`. Actual current `main` before this source-only B1047 batch was `57b6cf5072f3426fe35af15f4fe4f099405aafe4`, with **zero** active `apps/cms/current` StoreFront files/routes. Old PR and current main have diverged. Historical GitHub create-branch / canonical index safety denials remain binding, so B1047 does **not** attempt another branch, bypass a denied write, merge the whole old PR or put public runtime source in active main.

B1046 previously produced genuine isolated MariaDB10.11/PHP8.4 **5/5 tests, 51 assertions PASS** in GitHub Actions [41536027493](https://github.com/AldinAga/ald1n-project/actions/runs/41536027493), including one real RED-to-GREEN privacy fix in gated image response: set `private, no-store`, not `public`. B1046 was not runtime integration or production acceptance. [B1046 engineering report](https://github.com/AldinAga/ald1n-project/blob/main/docs/operations/B1046-STOREFRONT-M13-REAL-CI-RED-GREEN-20261102.md).

## New actual B1047 source work, beyond B1046

GitHub `main` now carries **development tools/workflow files only** under `tools/storefront/b1047` and `.github/workflows`, not deployed Laravel runtime. The B1047 finalizer imports the prior 10 source files from immutable PR111 using the B1046 SHA-pinned staging step, and then:

- Removes all unregistered legacy cart `add`, `update`, `remove` and `cart` methods from the staged `PublicShopController`; only `catalog`, `show` and `media` remain.
- Keeps public GET/HEAD routes `GET /katalog`, `GET /katalog/{slug}`, `GET /katalog/slika/{image}`; does not expose any public checkout, cart, registration, stock mutation, order, email, or POST routes.
- Adds two isolated IP-scoped named Laravel throttle policies: `public-shop-browse` and `public-shop-images`, with 120/minute and 300/minute budgets respectively, without changing internal CMS limiters.
- Preserves the `PUBLIC_STOREFRONT_ENABLED=false` default, separate database emergency-OFF gate, active-and-priced product visibility, XSS escaping, and explicit image private/no-store noindex.
- Builds an installable source diff containing **12 and only 12** CMS paths (the original 10 plus `routes/web.php` and `AppServiceProvider.php`), and verifies it can be applied to a *second clean main checkout*. The patch artifact exists only in disposable GitHub CI and Google Drive; it has **NOT** been applied to active runtime main or CloudLinux.
- Adds separate synthetic-data-only browser seeder/DB unchanged-proof and real Playwright Chromium/Firefox tests at desktop **1440**, mobile **390**, compact **320** pixels; checks product details, search, stock filter, no secret cost/commission, no buy actions, real image cache headers, no horizontal overflow, no missing CSS, forbidden shopping endpoints and emergency OFF immediate 404. Expected 12 browser screenshots in ZIP artifact.

[Source finalizer](https://github.com/AldinAga/ald1n-project/blob/main/tools/storefront/b1047/b1047_finalize_m13_source.py) · [Synthetic seeder](https://github.com/AldinAga/ald1n-project/blob/main/tools/storefront/b1047/b1047_seed_catalog.php) · [Real browser harness](https://github.com/AldinAga/ald1n-project/blob/main/tools/storefront/b1047/b1047_catalog_browser.cjs) · [CI workflow](https://github.com/AldinAga/ald1n-project/actions/workflows/b1047-m13-source-integration-browser-qa.yml).

All four files were archived on owner Google Drive, and verified GitHub readback. Local checks: YAML syntax, six Bash run-blocks `bash -n`, Python compile, PHP lint and Node syntax PASS. These syntax checks are not a real browser or database PASS.

## Actual CI events and interpretation

- [First B1047 GitHub run41537601555](https://github.com/AldinAga/ald1n-project/actions/runs/41537601555) **FAILED before Composer/PHP/browser**, after the stage builder generated the 12-path diff and independent checkout successfully applied it. The CI file census used only `git diff --name-only` in the clean checkout, which omits ten new *untracked* files created by `git apply`. Actual cause: `HOLD_B1047_PATCH_12_FILES`. This is a fixture/counting logic error, not a Laravel runtime failure, and does not authorize skipping any test.
- [Second B1047 GitHub run41537787217](https://github.com/AldinAga/ald1n-project/actions/runs/41537787217), corrected workflow source `58b4f48611c94b843bf1d0b387a92365a13a9167`, **PENDING terminal run result when this section was prepared**. The corrected census **requires exactly 2 tracked modified paths + exactly 10 newly added untracked paths**, and compares the contents of each against the first staged checkout. Keep an honest status until terminal readback is recorded below.

## Security and release boundaries

The original **56 B803 failure identities are OPEN_NOT_WAIVED** under [Issue104](https://github.com/AldinAga/ald1n-project/issues/104). Previously denied B815 fixture/CSRF rewrite is not touched. Owner-accepted M17 and B1044 M10/M11 80/80 must not be repeated. StoreFront merchant approval, registered/verified/tracked buyer M15, full B803 integration, enforced CSP nonce/hash and browser UAT (Issue101), independent 368-item WSTG/ASVS L2 assessment, host staging/UAT and explicit owner deployment/activation gate remain prerequisites before public StoreFront ON. B1047 has NO external email, production DB, GitHub runtime source merge, CloudLinux production change, EAS/APK/AAB build, OTA or Google Play action.

Formal owner acceptance remains **12/17 program**, **10/15 CMS**, **6/6 original UI**. M13/M14/M15 remain OPEN pending actual authorized runtime integration and release gates; partial CI success is not owner milestone acceptance.

## Browser result and follow-up image integrity

[Third B1047 GitHub run41537992482](https://github.com/AldinAga/ald1n-project/actions/runs/41537992482) completed SUCCESS at source SHA `438a6e0258e754928f890e0d10d90e2720ad3834`: portable 12-path diff and clean independent checkout verified; exactly three scoped GET/HEAD routes and named throttle policies; full 5/5 unchanged Laravel/MariaDB regression tests, **51 assertions / 0 failures/errors/skips**; real Chromium and Firefox **six** desktop/390px/320px viewport scenarios PASS, 12 screenshots, two emergency OFF 404 proofs, and unchanged orders/stock verification PASS. Original CI ZIP artifact ID `13183624447`, verified SHA256 `908af2b9412ff3a4fa1f5151454fadbde608defba9018b4729279eebdc91acd9`; portable patch SHA256 `c05f0bf39babf62870ce8ea2607151f6015a526c9ef1977da796f266d06692fc`, size `38520` bytes. Local ZIP inspection confirmed 16 entries including 12 screenshots and original JUnit test identities.

**Quality finding from actual screenshot review:** the historic one-pixel base64 PNG used for synthetic media has an invalid PNG IDAT checksum, independently reproduced with Pillow `verify()`; Chromium tolerated the bytes in the screenshot while Firefox showed image `alt` text instead of pixels. This is strictly a **fixture image problem**, not evidence about real product photographs. The browser harness originally asserted HTTP200, MIME and cache privacy, but not image decoding. The test fixture has now been changed to produce a valid `320x240` PNG via GD, and both Chromium and Firefox are required to report `img.complete` plus `naturalWidth>=320` and `naturalHeight>=240`. No production media service changed.

[Fourth B1047 run41538553739](https://github.com/AldinAga/ald1n-project/actions/runs/41538553739), exact source commit `e290f09c3ff0b19aa0d88e1194c5865ed9bbb886`, **COMPLETED SUCCESS**. Confirmed all prior 12-path portable patch, three GET-only routes/named middleware, 5/5 original PHP/MariaDB cases and 51 assertions, six Chromium+Firefox desktop/390/320px browser cases, 12 screenshots, real image `naturalWidth>=320` and `naturalHeight>=240` checks, two emergency OFF 404 proofs and no order/stock movement after browsing. Original ZIP artifact ID `13184079637` has independently verified SHA256 `87d55fd5713715e8396c07981b376a21d6dfba4f74f996f240fd6cc82ca42022`, 16 entries, 12 valid screenshots and exact original JUnit identity; the 12-path patch SHA256 remains `c05f0bf39babf62870ce8ea2607151f6015a526c9ef1977da796f266d06692fc` (38520 bytes). The source patch was **not** applied to active GitHub CMS main or CloudLinux.

## Terminal outcome

**PASS_SOURCE_ONLY_M13_PORTABLE_PATCH_BROWSER_GATE** at B1047 exact head `e290f09c3ff0b19aa0d88e1194c5865ed9bbb886`. Full GitHub job **COMPLETED SUCCESS**. Approved-scope engineering artifacts are permanently available:

- [Final original ZIP: patch + JUnit + 12 real Chromium/Firefox screenshots](https://drive.google.com/file/d/1nJRc5KTGug3X6Y-X0EMF56EO3d2Q6yWn/view) (verified SHA256 `87d55fd5713715e8396c07981b376a21d6dfba4f74f996f240fd6cc82ca42022`).
- [Standalone, explicitly UNAPPLIED 12-file source patch](https://drive.google.com/file/d/1kMdmMV6JNhhNS-_s8SWL8F1sSWVxjJ8h/view) (SHA256 `c05f0bf39babf62870ce8ea2607151f6015a526c9ef1977da796f266d06692fc`). Do **not** apply to production or alter server state automatically.
- [Original prior B1047 v3 browser evidence](https://drive.google.com/file/d/1uXF21j6M8YtUCMDx2kFeCOqshekT5kGR/view) retained to explain synthetic PNG visual discrepancy and correction.
- All scripts and updated workflow archived in project Drive source folder. The existing `docs/operations/000-LATEST.md` GitHub write remains historically safety-denied and untouched; this full report plus canonical Google Drive runbook and Issue104 are updated instead.

**Next permitted action:** obtain authorized branch or explicit reviewed main-runtime integration path and review/apply only this exact 12-file M13 patch to source with default OFF, then real staging/CloudLinux deployment gate. After independent owner M13 acceptance, progress to M14 cart and M15 registered/verified Kupac checkout with merchant/legal/security evidence. Original 56 B803 identities and 368 OWASP/ASVS controls remain OPEN_NOT_WAIVED; `PUBLIC_STOREFRONT_ENABLED` stays OFF on production. No mail probe, production DB mutation, or EAS/Google Play operations were performed.

