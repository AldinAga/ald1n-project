# Cleanup audit: AldinAga/ald1n-project (2026-11-14)

Read-only. Nothing was closed, deleted, commented, checked out or pushed. Evidence comes from `git` on the fetched refs (`origin/main` = af1e46a3) and `gh` reads. Nothing was executed: no PHP, Node or CI was run, so "main has it" means "the code and tests are present on main", not "the tests pass". Items I did not check are marked UNCHECKED.

How to read the evidence columns: "ident/differs/missing" counts files the branch changed (against its merge-base) that are byte-identical on main, present on main in another form, or absent from main.

## 0. Summary

| Area | Result |
|---|---|
| Old open PRs (41) | SUPERSEDED 11, OBSOLETE 18, DOC-ONLY 2, HAS UNSHIPPED VALUE 10 |
| Open issues (12) | CLOSE 11, KEEP 1 (issue 104) |
| Remote branches (235 + main = 236) | (a) merged into main, no open PR: 110. (b) not merged, no open PR: 75. (c) heads of the 41 old PRs: 41 (plus 9 heads of current PRs 175-183, ignored) |
| Tags / releases | 1 tag (`ald1n-operations-archive-pre-build18-20260919`), 0 GitHub releases |

Missing security protection on main: nothing critical. One partial gap (section 1.2, item S1): rejected-validation and `back()` redirects in the login, forgot/reset and activation flows still follow the caller's Referer; Host-header poisoning is covered by a different mechanism. Severity low, because the CSRF check runs before validation.

The one real defect found in the old PRs is not a missing security header but PR 151 (section 1.3, U4): on main and on the SDK 58 line, app start re-enables push consent that the user switched off.

Note: PR 183 (`codex/b1099-gates-allowlist-threshold-20261114`) and issue 184 were opened while this audit ran. They are not part of this audit.

## 1. Part 1: old open PRs

### 1.1 Verdict table

| PR | Head branch | What it does | Evidence against main | Verdict |
|---|---|---|---|---|
| 1 | audit/android-native-497 | CI audit of Android native modernization (patches, gradle init, 2 workflows) | 9 of 23 files identical on main; the plugin `plugins/with-android-native-modernization.js` exists on main in a later form; the rest are CI patch files of an SDK 57-era audit | OBSOLETE |
| 3 | docs/build23-24-native-ci-lessons-build25-gates-20261008 | Postmortem doc + AGENTS.md section 27 | Doc `docs/incident-reviews/2026-10-08-build23-24-batch528-native-ci-postmortem.md` is not on main; main AGENTS.md has no Build23/KaModule/splash text (grep) | DOC-ONLY. Lasting value: yes. 14 proven Android native CI lessons (Gradle composite, Lint KaModule crash, API33 splash attribute, disk-space order). Port before closing |
| 33 | integration/ecc-guided-agents-20261018 | Codex-side ECC skills, `.codex/config.toml`, CloudLinux guardrail script and workflow | All 19 files absent on main. Main AGENTS.md section 0ZE already carries the subagent and ECC rule. Codex-specific tooling | OBSOLETE. Optional salvage: `docs/ECC-CLOUDLINUX-CONTRACT.md` (UNCHECKED whether main AGENTS.md duplicates it) |
| 34 | qa/cms-lg2-production-public-20261018 | Read-only browser QA of CMS Liquid Glass 2.0 | 2 files (workflow + .cjs), absent on main, QA of a design being replaced | OBSOLETE |
| 47 | feature/android-lg03-wave2-native-navigation-candidate-20261020 | LG-03 native glass bottom navigation, Play-testing AAB profile | `android-native-glass-bottom-nav.tsx` is on neither main nor PR 177 head. Config is SDK 57 era (`app.config.js`, `eas.json`, `package.json` differ) | OBSOLETE (visual, replaced by redesign) |
| 48-51 | feature/android-lg04-wave1..wave4-* | LG-04 header, catalog controls, action sheets, shortcuts (stacked on 47) | New files `glass-header-action.tsx`, `glass-header-policy.ts`, `sheet-motion-policy.ts`, `glass-workspace-shortcut.tsx` are on neither main nor PR 177 head | OBSOLETE (visual) |
| 53 | ops/android-lg04-one-internal-uat-apk-20261020 | One-shot internal UAT APK, signing gates | Same stack; owner prohibited these builds; SDK 57 EAS config | OBSOLETE |
| 55 | feature/android-lg04-wave5-navigation-orders-polish-20261020 | Wave 5 glass shortcuts + admin order item summary | CMS part is identical on main: `OrderController.php` (item_summary at line 174), `OrderIndexService.php`, `AdminOrderItemSummaryTest.php`. Mobile glass parts absent. Only OpenAPI text differs (see U3) | OBSOLETE (non-visual part already on main) |
| 61 | feature/android-lg04-wave5-home-admin-orders-polish-20261020 | Earlier variant of 55 (`AdminOrderListItemSummaryTest`) | Replaced by 55's variant, which is the one on main | OBSOLETE |
| 54 | feature/catalog-laptop-chargers-types-brands-watt-20261020 | Charger catalog via artisan command + provisioner | Main has the same data via migration `2026_10_20_210000_add_laptop_chargers_catalog.php` (commit 02954abc): type `punjaci-za-laptopove`, shared field `snaga-napajanja` (W), per-brand connector lines in `CONNECTORS` (Dell, HP, Lenovo, ASUS, Acer, MSI, Apple, Microsoft, ...), plus `LaptopChargersCatalogTest`. `BrandLineCatalogService.php:304-305` keeps chargers out of laptop model lines | SUPERSEDED (different implementation, same behaviour) |
| 56 | feature/catalog-laptop-chargers-connector-taxonomy-20261020 | Connector taxonomy tests + mobile catalog tab | Same as 54 for the CMS side | SUPERSEDED |
| 58 | feature/catalog-laptop-chargers-20261020 | Charger migration `2026_10_20_154800` + mobile admin catalog | CMS side superseded by main's `2026_10_20_210000` migration. Mobile "Tip prikljucka" label is not on main or PR 177 head (U2) | HAS UNSHIPPED VALUE (U2, tiny) |
| 59 | integration/lg04-wave5-laptop-chargers-source-20261020 | 55 + 58 integration | Same as 55 and 58 | HAS UNSHIPPED VALUE (U2, U3, tiny) |
| 67 | feat/warranty-expiry-opt-in-owner-scope-20261021 | Warranty expiry alerts default OFF, order-owner scoped | Backend is on main and stricter: column migration `2026_10_21_080000` (default 0, INSTANT alter), `UpdateNotificationPreferencesRequest`, `NotificationPreferenceResource:26`, `UserNotificationPreferenceService:44` identical to the PR; `WarrantyExpiryRecipientPolicy.php:49,59` (owner scope), `OperationalNotificationService.php:101`, `MobilePushDispatcher.php:68-75` (send-time recheck). Mobile toggle is missing (U1) | HAS UNSHIPPED VALUE (U1, tiny) |
| 70-75 | feature/build26-* waves, integration/build26-lg04-waves1-4-current-main | Build26 native Liquid Glass foundation and waves 1-4 on current main | Same native-glass components missing from main and PR 177. 2 of the commits per PR are already applied (git cherry `-`) | OBSOLETE (visual) |
| 76, 77, 80 | release/v1-5-warranty-mobile-consolidation, release/v1.5.0-android-native-candidate, integration/android-v15-build26-source-qa | v1.5 candidate: 75 plus warranty toggle, version 1.5.0, runtime `1.5.0-native1`. PR 77 and PR 80 have the same diff stat (41 files, +1922/-141) | The release config is dead (SDK 57; live release is SDK 58 versionCode 29). The warranty toggle is the only non-visual content (U1) | HAS UNSHIPPED VALUE (U1). Port U1 once, then 67, 76, 77, 80 all close |
| 87 | feat/b796-operator-orders-glass-tracking-20261022 | Operator-only orders, glass motion, first-panel shipment tracking | Operator rule on main: `User.php:78-87` `isPersonalOrderRestricted`, `OrderService.php:36`; `OperatorWorkspace796Test` exists on main. Tracking: `app/Support/TrackingUrl.php` + `TrackingUrl796Test` do what the PR's `ShipmentTrackingUrl` does (https only, no credentials/IP/localhost/controls); `OrderDetailPresenter.php:417` uses it. CSS/glass parts are visual | SUPERSEDED |
| 92 | feat/b799-public-retail-full-flow-20261023 | First public storefront implementation (`PublicRetailController`, `PublicRetailService`, `routes/public_retail.php`, `config/public_retail.php`) | None of those files exist on main. Main's storefront is a different line: `PublicShopController`, `PublicRetailCatalogService`, `PublicCheckoutController`, `routes/public-retail-access.php`, `config/public_storefront.php`, `PublicOrderAccess` model | SUPERSEDED (abandoned approach) |
| 94 | chore/connector-write-proof-20261024-r813 | One doc: connector write proof | Doc absent on main. Pure evidence note | DOC-ONLY. Lasting value: low |
| 98 | test/b821-receipt-privacy-negative-20261024 | One test file, B821 receipt token/privacy | `PublicReceiptTokenPrivacyB821Test.php` absent on main. Behaviour present (`SecurityHeaders.php:63-70`, `PublicOrderCapabilityB799Test`) | HAS UNSHIPPED VALUE (tests only, U6) |
| 99 | test/b845-b846-replay-negative-controls-20261025 | B847 catalog surface tests (15), replay controls, python evidence tools | `PublicCatalogSurfaceSecurityB847Test.php` absent on main. Behaviour present: LIKE escaping `PublicRetailCatalogService.php:39`, media boundary `PublicProductImageFiles`, B893 test identical on main | HAS UNSHIPPED VALUE (tests only, U6). The python/doc tooling is low value |
| 100 | fix/b858-laravel-composer-advisories-20261025 | Composer lock refresh, B866 `base-uri 'none'`, audit-first workflow | Main's lock is newer: laravel/framework v13.40.0 (PR 13.38.0), symfony/http-foundation v8.1.11 (PR 8.1.9), commonmark 2.10.7 (PR 2.10.4). `SecurityHeaders.php:25` has `base-uri 'none'`. Main has `cms-dependency-audit.yml`, `b1104-cms-security-lock.yml` | SUPERSEDED |
| 102 | fix/b872-public-no-script-csp-20261025 | Scoped CSP for public storefront pages, stable MariaDB role IDs | `PublicNoScriptCsp.php` on main is the same idea, stronger (per-request nonce); `CoreAccessSeeder` and `CoreAccessRoleIdStabilityB876Test` identical on main; `PublicNoScriptCspB872Test` on main | SUPERSEDED |
| 103 | fix/b877-public-media-and-cart-20261025 | Private no-store media, Host-safe cart redirects | `SecurityHeaders.php:45-51`, `PublicShopController.php:100-103,121,131,138`; `PublicMediaCacheAndCartRedirectB877Test` identical on main | SUPERSEDED |
| 105 | fix/b883-auth-local-redirects-20261025 | Host/Referer-safe redirects for login, reset, activation (`LocalAuthFormRequest`, `CustomerActivationRequest`, Login/Forgot/Reset requests) | Host part: covered differently, see S1. Referer part: not on main. 3 request classes and `AuthLocalRedirectB883Test` absent | HAS UNSHIPPED VALUE (S1) |
| 106 | fix/b888-storefront-checkout-origin-20261025 | Checkout login redirect safe from hostile Host | `PublicCheckoutController.php` identical on main (`:29-30,59` relative `/login`, `:177` relative order URL). `StorefrontCheckoutOriginB888Test` absent (U6) | SUPERSEDED |
| 107 | fix/b893-storefront-media-path-20261025 | Reject symlink root escape in public media | `PublicProductImageFiles.php:32-81` (moved, "unchanged" per its docblock); `StorefrontMediaPathBoundaryB893Test` identical on main | SUPERSEDED |
| 108 | fix/b896-storefront-register-csp-20261025 | Registration-only Turnstile script CSP | `PublicNoScriptCsp.php:45-65`; `TrustedScriptCspB1115Test` covers Turnstile tags. `PublicRegistrationScriptCspB896Test` absent (U6) | SUPERSEDED |
| 109 | fix/b899-storefront-html-link-origin-20261025 | Relative storefront links, filtered pagination | `route(..., false)` in catalog view (`catalog.blade.php:91,117`), `PublicRetailCatalogService.php:84-86` (`withPath(route(...,false))->withQueryString()`), `StorefrontHtmlOriginBoundaryB899Test` on main (differs by 8 lines only) | SUPERSEDED |
| 145 | codex/b1111-observed-host-php-lock-compat-20261104 | CI-only diagnostic of observed host PHP vs approved lock (Report1073), draft | 3 files, all absent from main; one workflow, one helper, one gzip fixture. The question it asked was overtaken by the B1144-B1152 installers now on main (`tools/storefront/b1144..b1152`) | OBSOLETE. UNCHECKED: whether the host PHP/lock compatibility question was formally closed by the owner |
| 151 | codex/b1124-passive-notification-consent-20261104 | Draft, test-only: passive startup must not re-enable consent | 2 test files absent on main; the fix was never written. Defect is live, see U4 | HAS UNSHIPPED VALUE (U4) |

Chain structure, so closing is done in the right order. LG-04 waves: 47 <- 48 <- 49 <- 50 <- 51 <- 53, 55, 56, 58, 61; 59 sits on 55. Build26: 70 <- 71 <- 72 <- 73 <- 74; 75 and 80 sit on main; 76 on 75; 77 on 76. Security: 98 and 99 sit on `feat/b799-public-retail-catalog-cart-checkout` (that branch exists and is merged into main); 99 <- 100 <- 102 <- 103 <- 105 <- 106 <- 107 <- 108 <- 109. Close each stack top-down, or all members in one go, so that no child PR is left pointing at a deleted base branch.

### 1.2 Security chain, fix by fix (PRs 92, 98-109)

| # | Fix | Main has an equivalent? | Where on main |
|---|---|---|---|
| 1 | CSP for public storefront pages | YES, stronger | `PublicNoScriptCsp.php:79-84` (`default-src 'none'`, `script-src 'nonce-...'`, `script-src-attr 'none'`, `frame-ancestors 'none'`); class registered `bootstrap/app.php:42`. Baseline CSP for every response `SecurityHeaders.php:25` |
| 2 | `base-uri 'none'`, `object-src 'none'` (B866) | YES | `SecurityHeaders.php:25` |
| 3 | Host/Referer-safe redirects, Host part | YES, different mechanism | Global `PinAppUrlOrigin` forces the root URL to APP_URL for every request (`PinAppUrlOrigin.php:29-36`, prepended at `bootstrap/app.php:39`), tests `AppUrlOriginPinTest`, `PasswordResetTest.php:116-135`. Storefront redirects are explicit relative: `PublicCheckoutController.php:30,59,177`, `PublicShopController.php:121,131,138`, `PublicBuyerRegistrationController.php:113` |
| 4 | Host/Referer-safe redirects, Referer part (auth flows) | PARTIAL, S1 | Main uses `back()` in `AuthenticatedSessionController.php:48,69`, `PasswordResetController.php:36,50,69`, `CustomerActivationController.php:50-60`, and plain `FormRequest` classes (`LoginRequest.php`). These follow the request Referer. The PR's `LocalAuthFormRequest` and `getRedirectUrl()` overrides are not on main. Customer login redirect is relative (`AuthenticatedSessionController.php:120`) |
| 5 | Symlink root escape in storefront media | YES | `PublicProductImageFiles.php:32-81` (original, legacy and public disks) and `:84-110` (WebP renditions); controller comment `PublicShopController.php:85`; test identical on main |
| 6 | Private no-store media and cart | YES | `SecurityHeaders.php:45-51`, media response headers `PublicShopController.php:100-103` |
| 7 | Turnstile script CSP, registration only | YES | `PublicNoScriptCsp.php:45-65`, `SecurityHeaders.php:80-87` |
| 8 | Relative links and filtered pagination | YES | `PublicRetailCatalogService.php:84-86`, views use `route(..., false)` |
| 9 | Checkout origin | YES | `PublicCheckoutController.php:29-30,59,177` (file identical to the PR) |
| 10 | Order pages: no referrer, no store | YES | `SecurityHeaders.php:63-70` |
| 11 | Composer advisories | YES, newer | `composer.lock`: laravel/framework v13.40.0 vs PR 13.38.0 |
| 12 | Guest checkout policy reconciliation (issue 104) | YES, test updated | `PublicRetailCheckoutB799Test.php:62-70` logs a registered buyer in before the checkout; `PublicCheckoutController.php:19-59` `requireRetailBuyer` |

S1 detail: for a rejected login, forgot-password, reset or activation POST, Laravel's `back()` and the default FormRequest redirect target the Referer header. The attacker cannot post cross-site without a valid CSRF token (419 comes first), so the exposure is a hostile proxy or extension, not a remote attacker. Fixing it is port work: `LocalAuthFormRequest.php` and `CustomerActivationRequest.php` (new), changes in `LoginRequest`, `ForgotPasswordRequest`, `ResetPasswordRequest`, `CustomerActivationController`, plus `AuthLocalRedirectB883Test`, all from the head of PR 109 (`origin/fix/b899-storefront-html-link-origin-20261025`). Do this before deleting that branch, or record that you accept the gap.

### 1.3 Items with unshipped value (so they can be ported later)

| ID | What | Files (head branch) | Size |
|---|---|---|---|
| U1 | Mobile toggle for warranty-expiry alerts. Backend already returns and accepts `warranty_expiry_alerts` (default false), the app has no switch for it. Not on main, not on PR 177 head | `apps/mobile/current/src/app/(app)/notification-settings.tsx` (new row "Isticanje garancije", copy for "Garancije" row), `apps/mobile/current/src/types/api.ts` (`warranty_expiry_alerts?: boolean`). From PR 67 (same in 76, 77, 80). Optional doc: `apps/mobile/current/docs/WARRANTY-EXPIRY-NOTIFICATION-RELEASE-GATES.md` | about 5 lines |
| U2 | Mobile admin catalog: for product type "Punjaci za laptopove" show the line selector as "Tip prikljucka" (placeholder and empty option too). Data model stores connectors as product lines, the app still says "Linija" | `apps/mobile/current/src/app/(app)/admin/catalog/create.tsx`, `.../admin/catalog/[id].tsx` (PR 58/59) | about 10 lines each |
| U3 | OpenAPI description of `item_summary` on the admin orders list (the field itself is on main) | `apps/cms/current/docs/openapi.yaml` (PR 55/59) | about 20 lines of YAML |
| U4 | Passive notification consent defect, LIVE on main and on PR 177 head. `syncCurrentDeviceAtStartup` sends `notifications_enabled: true` whenever the OS permission is granted (`apps/mobile/current/src/features/notifications/push-service.ts:80`). `disableCurrentDevicePush` (`:158-167`) clears the token and sets false, so the next app start re-registers and turns consent back on. `MobileDeviceController.php:53-62` only defaults new or revoked rows to true; a grep for `notifications_enabled` in `app/` found no passive-suppression logic. The PR ships 14 Laravel tests (`PassiveDeviceConsentB1124Test`) and a mobile test (`push-startup-consent.test.mjs`) but the fix was never written | `apps/cms/current/tests/Feature/PassiveDeviceConsentB1124Test.php`, `apps/mobile/current/scripts/release25/push-startup-consent.test.mjs` (head `origin/codex/b1124-passive-notification-consent-20261104`) | fix plus tests; UNCHECKED whether the 14 tests currently fail on main (not run) |
| U5 | Auth redirect Referer hardening, see S1 | files listed in 1.2 | 3 classes, 3 edits, 1 test |
| U6 | Regression tests whose behaviour is already on main: `PublicReceiptTokenPrivacyB821Test` (PR 98), `PublicCatalogSurfaceSecurityB847Test` (15 tests) and `PublicResponseHeadersB866Test` (PR 99/100), `StorefrontCheckoutOriginB888Test` (106), `PublicRegistrationScriptCspB896Test` (108), `tests/Browser/b874-no-script-csp-browser.cjs` (102). UNCHECKED whether they pass against main | `apps/cms/current/tests/Feature/...` | tests only |
| U7 | Docs only on branches (see section 3.4): the Build23-24 postmortem (PR 3) is the one worth keeping | `docs/incident-reviews/2026-10-08-build23-24-batch528-native-ci-postmortem.md`, AGENTS.md section 27 | doc |

Side observation, not a verdict: `react-native-android-liquid-glass` 0.2.1 is still a dependency on main (`apps/mobile/current/package.json:73`) and on PR 177 (`:63`), but no file under `src` imports it. The redesign may want to drop it; UNCHECKED whether the 16 KB pin test (`nativegauss-16kb-pin.test.mjs`) needs it.

## 2. Part 2: open issues

| Issue | Title (short) | Verdict | Evidence |
|---|---|---|---|
| 12 | Remove deprecated npm transitives from EAS25/Expo57 | CLOSE (when PR 177 merges) | Main lock still has xcode 3.0.1, uuid 7.0.3 and 1 `deprecated` entry. PR 177 head lock: xcode 4.0.0, no uuid 7, 0 `deprecated` entries. UNCHECKED: EAS CLI's own separate tree |
| 19 | Triage 26 Laravel/MySQL baseline failures | CLOSE | PR 20 "repair legacy Laravel/MySQL cross-feature baseline (Issue 19)" is MERGED (2026-10-17); its QA attestation records 128/128 passing. Wider full-suite failures are tracked by issue 104 |
| 25 | CMS release deployed, DBA/review audit evidence | CLOSE (needs the owner's explicit nod) | Release 2026-10-17 (PR 27) is long superseded by later releases. Remaining boxes are paperwork that cannot be produced retroactively (DBA ticket was a literal placeholder, no non-author review). The issue says not to erase silently, so close with a comment that records them as accepted deviations |
| 28 | Liquid Glass CMS UX: archived-order Danger Zone, sticky actions | CLOSE | PR 29 MERGED 2026-10-18. Danger Zone exists on main: `resources/views/admin/orders/archived.blade.php:72,93-131` (reason, exact-number confirmation, separate destructive button). Sticky editor bar: `admin/products/form.blade.php` exists, visual state UNCHECKED. CSS will be replaced by the redesign, so copy requirement B (sticky bar hierarchy, safe-area padding) into the redesign backlog |
| 31 | Android v1.4: integrate Kyant0 Backdrop Liquid Glass | CLOSE (obsolete) | Native glass R&D. The glass components named in the issue's line of work exist on neither main nor PR 177 head, and the redesign replaces the visuals |
| 36 | CMS Liquid Glass R6 dark micro-polish | CLOSE (obsolete) | Explicitly deferred cosmetic polish of the old design; new Kobalt tokens in PR 176 replace those CSS tokens |
| 52 | LG-04 waves 1-4 UAT and Play beta gate (HOLD) | CLOSE (obsolete) | The candidate PRs 47-51 are obsolete (section 1); the live release is SDK 58 versionCode 29 |
| 95 | Mandatory big inode cleanup + permanent percent reporting | CLOSE | Owner-stated: cleanup done 2026-11-14, host about 554k of 1.2M inodes. Host state UNCHECKED by me. The "show percent of closed stages in status reports" rule is a working rule; main AGENTS.md contains the percent ledger text (e.g. line 197), so it does not need a ticket |
| 101 | Enforced script CSP with nonce/hash before storefront ON | CLOSE | Baseline script CSP on every response: `SecurityHeaders.php:25` (`script-src 'nonce-...'`, `script-src-attr 'none'`, `object-src 'none'`, `base-uri 'none'`). Storefront: `PublicNoScriptCsp.php:49-84`. CMS and auth layouts carry nonces (`layouts/app.blade.php` 6, `layouts/guest.blade.php` 2, auth pages) and `TrustedScriptCspB1115Test` plus `tests/Browser/b1115-auth-csp-browser.cjs` exist. Remaining gap, not script-related: CMS pages have no `default-src` or `style-src` (the layout still has an inline `style=` at `app.blade.php:113`); not what the issue asked |
| 104 | Reconcile 56 full-suite identities (B803) | KEEP | The named conflict is fixed (`PublicRetailCheckoutB799Test.php` logs a registered buyer in), but the last full regression runs on main (B1072-B1074, 2026-11-03) failed and none ran since; no evidence the full suite is green on current main. One sentence for the ticket: re-run the full suite on main and replace the 56-identity list with the current failing set |
| 112 | Prove queued warranty-expiry revocation for push and mail | CLOSE | Push: `MobilePushDispatcher.php:44-80` re-authorizes user, push consent and warranty-expiry policy at send time; tests `MobilePushCategoryRevocationB911Test` (9 tests), `MobilePushRecipientOwnershipB909Test`, `WarrantyExpiryFreshEligibilityB905Test`. Mail: enqueue-time recheck `OperationalNotificationService.php:101`; B1044 CI (80/80 incl. MailChannel cases) is cited in the issue. UNCHECKED: whether a queue-time mail gap exists (the issue says to document none if absent) |
| 114 | Prepaid bank warranty activation failure: durable recovery | CLOSE (note) | Option 1 of the issue was built and deployed: `WarrantyActivationRecoveryService`, `WarrantyActivationRecoveryCommand`, `WarrantyActivationRecoveryAttempt`, migration `2026_10_27_230000`, tests `WarrantyActivationRecoveryB946Test` and `WarrantyActivationB949IntegrationTest`. COD stays atomic (`OrderWorkflowService.php:404`); prepaid path stays post-commit with a log warning (`:427`) and relies on the recovery job. Caveat: `config/warranty_recovery.php` defaults `enabled` to false (env `WARRANTY_ACTIVATION_RECOVERY_ENABLED`); UNCHECKED whether the host has it switched on. If it is off, keep the issue until it is |


## 3. Part 3: remote branches

Counts: 235 branches plus main = 236. (a) merged into origin/main and no open PR: **110**. (b) not merged and no open PR: **75**. (c) heads of the old open PRs: **41**. Heads of current PRs 175-183 (ignored): **9**. 110 + 75 + 41 + 9 = 235. No open-PR head is merged into main.

Caution for (a): a branch being merged means its tip is an ancestor of main, so deleting it loses no commits. Several workflows on main are pinned to these branch names (see 3.5); deleting the branch makes those workflows unreachable by push.

### 3.1 List (a): merged into origin/main, no open PR (safe to delete)

| Branch | Last commit |
|---|---|
| audit/497-github-capability-20260927 | 2026-09-26 |
| audit/b720-integrated-release-candidate-20261017 | 2026-10-17 |
| audit/batch704-release-orchestrator-20261017 | 2026-10-17 |
| audit/batch705-release-readiness-20261017 | 2026-10-17 |
| audit/batch709-release-contract-20261017 | 2026-10-17 |
| audit/build25-ci-cost-gates-20261009 | 2026-10-09 |
| audit/build25-combined-native-ci-20261009 | 2026-10-09 |
| audit/build25-deep-offline-convergence-20261009 | 2026-10-09 |
| audit/build25-eas-cli-24-7-reconciled-20261009 | 2026-10-09 |
| audit/build25-eas-project-identity-preflight-20261010 | 2026-10-10 |
| audit/build25-firebase-eas-safe-recovery-20261009 | 2026-10-09 |
| audit/build25-native-ci-explicit-trigger-20261009 | 2026-10-09 |
| audit/build25-native-elf-16kb-policy-20261010 | 2026-10-10 |
| audit/build25-p0-integrated-candidate-20261009 | 2026-10-09 |
| audit/build25-p0-release-controller-20261009 | 2026-10-09 |
| audit/build25-report575-targeted-fixes-20261009 | 2026-10-09 |
| audit/build25-separated-build-submit-20261010 | 2026-10-10 |
| audit/issue19-enum-migration-gate-20261017 | 2026-10-17 |
| audit/issue19-mariadb1011-parity-20261017 | 2026-10-17 |
| codex/b1080-history-preflight-20261104 | 2026-11-04 |
| codex/b1080-main-postmerge-20261104 | 2026-11-04 |
| codex/b1080-release25-history-20261104 | 2026-11-04 |
| codex/b1080-release-history-main-20261104 | 2026-11-04 |
| codex/b1081-positive-commission-main-20261104 | 2026-11-04 |
| codex/b1082-storefront-current-main-20261104 | 2026-11-05 |
| codex/b1083-product-only-test-contracts-20261104 | 2026-11-04 |
| codex/b1083-storefront-refresh-prep-20261104 | 2026-11-04 |
| codex/b1084-delegated-contracts-20261104 | 2026-11-04 |
| codex/b1085-registration-required-fields-green-20261104 | 2026-11-04 |
| codex/b1085-registration-required-fields-prep-20261104 | 2026-11-04 |
| codex/b1086-two-column-opaque-menu-20261104 | 2026-11-04 |
| codex/b1090-direct-sale-contracts-20261104 | 2026-11-04 |
| codex/b1091-current-contracts-20261104 | 2026-11-04 |
| codex/b1104-cms-security-20261104 | 2026-11-04 |
| codex/b1106-mobile-js-security-20261104 | 2026-11-04 |
| codex/b1108-host-inventory-20261104 | 2026-11-04 |
| codex/b1109-host-readiness-20261104 | 2026-11-04 |
| codex/b1116-storefront-trusted-scripts-20261104 | 2026-11-04 |
| codex/b1119-owned-cancel-lock-20261104 | 2026-11-04 |
| codex/b1120-mobile-private-download-prefix-20261104 | 2026-11-04 |
| codex/b1121-upload-boundary-20261104 | 2026-11-04 |
| codex/b1125-symfony-patch-20261104 | 2026-11-04 |
| codex/b1149-installer-20261110 | 2026-11-10 |
| codex/b1150-installer-20261112 | 2026-11-12 |
| codex/b1151-installer-20261112 | 2026-11-12 |
| codex/b1152-installer-20261113 | 2026-11-13 |
| codex/docs-report-1118-20261113 | 2026-11-13 |
| codex/docs-reports-1114-1115-20261112 | 2026-11-12 |
| codex/integration-b1149-20261110 | 2026-11-10 |
| codex/integration-b1149-b1151-20261112 | 2026-11-12 |
| codex/integration-b1152-20261113 | 2026-11-13 |
| codex/legal-email-visible-20261112 | 2026-11-12 |
| codex/merchant-draft-password-throttle-20261112 | 2026-11-12 |
| codex/pdf-logo-raster-fallback-20261113 | 2026-11-13 |
| codex/product-gallery-20261110 | 2026-11-10 |
| codex/product-media-route-20261112 | 2026-11-12 |
| codex/reset-link-host-20261113 | 2026-11-13 |
| codex/smtp-settings-20261110 | 2026-11-10 |
| codex/svg-logo-20261110 | 2026-11-10 |
| codex/theme-instant-20261110 | 2026-11-10 |
| codex/url-origin-pin-20261113 | 2026-11-13 |
| design/ald1n-mobile-workspace-v2-20261010 | 2026-10-10 |
| design/cms-liquid-glass-r4-calibration-20261018 | 2026-10-18 |
| design/dashboard-six-kpi-brand-mix-20261018 | 2026-10-18 |
| design/liquid-glass-2-r1-20261018 | 2026-10-18 |
| design/liquid-glass-cms-20261018 | 2026-10-18 |
| diag/b968-android16kb-offline-launch | 2026-10-29 |
| docs/android-aab-16kb-release-gate-20261021 | 2026-10-21 |
| docs/b796-source-and-first-payment-20261022 | 2026-10-22 |
| docs/b797-pdf-source-checkpoint-20261023 | 2026-10-22 |
| docs/report795-owner-requirements-20261022 | 2026-10-22 |
| docs/sdk58-upgrade-readiness-20261021 | 2026-10-21 |
| feat/ald1n-admin-orders-index-ux-20261010 | 2026-10-10 |
| feat/ald1n-notifications-inbox-ux-20261010 | 2026-10-10 |
| feat/ald1n-order-buyer-first-web-20261010 | 2026-10-10 |
| feat/b799-complete-gated-public-storefront | 2026-10-23 |
| feat/b799-public-retail-catalog-cart-checkout | 2026-10-25 |
| feat/b946-warranty-activation-recovery | 2026-10-28 |
| feat/b963-android-m16-main-reconcile | 2026-10-29 |
| feat/b989-v15-one-time-eas-profile | 2026-10-30 |
| feat/cms-gainward-gpu-official-series-20261020 | 2026-10-20 |
| feat/dashboard-d2-comparison-20261018 | 2026-10-18 |
| feat/dashboard-d3-brand-charts-20261018 | 2026-10-18 |
| feat/m10-m11-main16-qa-20261027 | 2026-10-27 |
| feature/android-lg03-wave1-nav-material-20261019 | 2026-10-19 |
| feature/cms-laptop-charger-catalog-20261020 | 2026-10-20 |
| feature/lg04-wave2-3-native-safe-main-20261021 | 2026-10-21 |
| feature/lg04-wave5-home-admin-shortcut-safe-20261021 | 2026-10-21 |
| feature/lg04-wave5-mobile-orders-main-20261021 | 2026-10-21 |
| feature/lg04-wave5-order-summary-main-20261021 | 2026-10-21 |
| feature/user-retirement-batch2-20261016 | 2026-10-16 |
| fix/ald1n-catalog-card-actions-form-safety-20261010 | 2026-10-10 |
| fix/ald1n-catalog-grid-4321-20261010 | 2026-10-10 |
| fix/b1075-storefront-recovery-20261103 | 2026-11-04 |
| fix/b999-canonical-health-gate-generated-cache-20261030 | 2026-10-30 |
| fix/cms-background-product-dashboard-spacing-20261021 | 2026-10-21 |
| fix/cms-dashboard-full-bleed-aurora-20261021 | 2026-10-21 |
| fix/cms-retirement-action-user-card-layout-20261019 | 2026-10-19 |
| fix/dashboard-default-month-to-date-20261018 | 2026-10-19 |
| fix/issue19-laravel-mysql-baseline-20261016 | 2026-10-17 |
| fix/laravel-mobile-menu-single-column-20261103 | 2026-11-04 |
| fix/mobile-home-focus-tile-navigation-20261021 | 2026-10-21 |
| fix/mobile-product-editor-android-keyboard-reveal-20261020 | 2026-10-21 |
| fix/windows-validator-paths-20261103 | 2026-11-04 |
| prep/b1075-reviewed-security-20261104 | 2026-11-04 |
| qa/android-lg03-wave2-real-app-debug-compile-20261019 | 2026-10-19 |
| qa/user-retirement-authenticated-browser-20261019 | 2026-10-19 |
| qa/user-retirement-current-main-mariadb-20261019 | 2026-10-19 |
| release/android-native-497 | 2026-10-01 |
| release/warranty-expiry-backend-current-main-20261021 | 2026-10-21 |

### 3.2 List (b): not merged into main, no open PR (75 branches)

Columns: commits ahead of main; last commit date; evidence from git (cherry = patches already on main; contained = tip is an ancestor of another branch; PR record = a PR with this head existed); files the branch changed that are identical on main / present but different / absent; suggestion.

| Branch | Ahead | Last commit | Evidence | ident/differs/missing | Suggestion |
|---|---:|---|---|---|---|
| docs/build25-release-controller-design-20261008 | 2 | 2026-10-08 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/0/2 of 2 | Only copy of 2 Build25 auto-submit design docs (superpowers spec + plan). Doc salvage or tag, then delete. |
| audit/batch528-native-release-gate | 8 | 2026-10-08 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 9/2/10 of 21 | Only copy of docs/operations reports 551-562 that the Build23-24 postmortem (PR 3) cites. Tag or port reports before deleting. |
| fix/ald1n-laravel-css-shell-standards-20261010 | 3 | 2026-10-10 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/2/1 of 3 | Old CSS shell standards plus one smoke script; replaced by the redesign. Delete. |
| feat/ald1n-mobile-workspace-v2-20261010 | 16 | 2026-10-10 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 7/2/1 of 10 | 7 of 10 files identical on main; only report 592 missing. Shipped. Delete. |
| cleanup/batch632-root-artifacts-20261012 | 2 | 2026-10-12 | PR MERGED #6:MERGED | 2/0/0 of 2 | Squash-merged PR; content on main. Delete. |
| audit/batch632-pr4-main-baseline-20261012 | 3 | 2026-10-12 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/0/1 of 1 | CI-only audit branch (workflow files that are not on main by design). Delete. |
| audit/batch632-order-notification-route-recovery-20261012 | 5 | 2026-10-12 | PR MERGED #4:MERGED | 3/2/0 of 5 | Squash-merged PR; content on main. Delete. |
| audit/batch632-cleanup-proof-20261012 | 3 | 2026-10-12 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 2/0/1 of 3 | CI-only audit branch (workflow files that are not on main by design). Delete. |
| audit/batch632-pr4-mysql-feature-20261012 | 8 | 2026-10-12 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 3/2/1 of 6 | CI-only audit branch (workflow files that are not on main by design). Delete. |
| audit/batch632-signature-jdk17-20261012 | 10 | 2026-10-12 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/6/1 of 7 | CI-only audit branch (workflow files that are not on main by design). Delete. |
| audit/batch632-release25-pin-parity-20261012 | 7 | 2026-10-12 | PR MERGED #5:MERGED | 0/6/0 of 6 | Squash-merged PR; content on main. Delete. |
| feat/batch646-mobile-order-unavailable-ux-20261013 | 4 | 2026-10-13 | PR MERGED #7:MERGED | 4/0/0 of 4 | Squash-merged PR; content on main. Delete. |
| audit/batch646-mobile-order-unavailable-ci-20261013 | 5 | 2026-10-13 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 4/0/1 of 5 | CI-only audit branch (workflow files that are not on main by design). Delete. |
| audit/batch668-pr8-nav-jdk17-20261015 | 7 | 2026-10-15 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 1/3/1 of 5 | CI-only audit branch (workflow files that are not on main by design). Delete. |
| feat/batch664-role-aware-floating-bottom-nav-20261015 | 6 | 2026-10-15 | PR MERGED #8:MERGED | 1/3/0 of 4 | Squash-merged PR; content on main. Delete. |
| feat/batch678-compact-gray-glass-nav-20261015 | 3 | 2026-10-15 | PR MERGED #9:MERGED | 1/2/0 of 3 | Squash-merged PR; content on main. Delete. |
| audit/batch680-product-editor-release25-jdk17-20261015 | 7 | 2026-10-15 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 2/3/1 of 6 | CI-only audit branch (workflow files that are not on main by design). Delete. |
| audit/batch679-pr9-glass-nav-jdk17-ci-20261015 | 4 | 2026-10-15 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/4/1 of 5 | CI-only audit branch (workflow files that are not on main by design). Delete. |
| feat/batch680-product-editor-workspaces-20261015 | 6 | 2026-10-15 | PR MERGED #10:MERGED | 2/3/0 of 5 | Squash-merged PR; content on main. Delete. |
| feat/batch684-final-qa-ios-readiness-20261016 | 8 | 2026-10-16 | PR MERGED #13:MERGED | 0/7/0 of 7 | Squash-merged PR; content on main. Delete. |
| docs/cloudlinux-permanent-toolchain-guards-20261016 | 1 | 2026-10-16 | APPLIED (git cherry: all patches on main) | n/a (patches applied) | Patches already on main. Delete. |
| fix/catalog-infinite-scroll-20261016 | 7 | 2026-10-16 | PR MERGED #17:MERGED | 5/0/0 of 5 | Squash-merged PR; content on main. Delete. |
| feat/batch685-ios-firebase-profile-split-20261016 | 6 | 2026-10-16 | PR MERGED #14:MERGED | 1/5/0 of 6 | Squash-merged PR; content on main. Delete. |
| audit/batch683-npm-deprecated-origin-20261016 | 1 | 2026-10-16 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/0/1 of 1 | CI-only audit branch (workflow files that are not on main by design). Delete. |
| audit/batch684-ios-qa-php84-jdk17-20261016 | 9 | 2026-10-16 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/7/1 of 8 | CI-only audit branch (workflow files that are not on main by design). Delete. |
| audit/batch681-eas-cli25-compat-20261016 | 1 | 2026-10-16 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/0/1 of 1 | CI-only audit branch (workflow files that are not on main by design). Delete. |
| audit/batch682-eas-cli25-full-release25-20261016 | 11 | 2026-10-16 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 1/9/1 of 11 | CI-only audit branch (workflow files that are not on main by design). Delete. |
| audit/batch685-ios-firebase-profile-jdk17-php84-20261016 | 7 | 2026-10-16 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 1/5/1 of 7 | CI-only audit branch (workflow files that are not on main by design). Delete. |
| chore/batch682-eas-cli25-pin-upgrade-20261016 | 10 | 2026-10-16 | PR MERGED #11:MERGED | 1/9/0 of 10 | Squash-merged PR; content on main. Delete. |
| feat/batch686-ios-preview-simulator-build-20261016 | 7 | 2026-10-16 | PR MERGED #15:MERGED | 2/5/0 of 7 | Squash-merged PR; content on main. Delete. |
| audit/batch686-ios-simulator-native-prep-jdk17-20261016 | 8 | 2026-10-16 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 2/5/1 of 8 | CI-only audit branch (workflow files that are not on main by design). Delete. |
| feature/android-v14-liquid-glass-poc-20261018 | 11 | 2026-10-18 | PR CLOSED unmerged #32:CLOSED | 0/0/3 of 3 | Closed PR 32, v1.4 glass prototype. Obsolete. Delete. |
| feature/android-lg04-wave5-glass-admin-orders-polish-20261020 | 57 | 2026-10-20 | INSIDE OPEN PR PR58 | 0/19/27 of 46 | Commits live inside an open PR head; delete only after that PR is closed or merged. |
| feature/catalog-laptop-chargers-existing-brands-20261020 | 2 | 2026-10-20 | PR CLOSED unmerged #57:CLOSED | 0/0/4 of 4 | Closed PR 57. Charger migration is on main in another file. Delete. |
| ops/android-v15-aab27-bundletool-readonly-20261021 | 48 | 2026-10-21 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/23/19 of 42 | v1.5 AAB27 native-glass ops line; same 42-file diff on all five. Obsolete. Delete (optional single tag). |
| ops/android-v15-16kb-aab27-audit-20261021 | 48 | 2026-10-21 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/23/19 of 42 | v1.5 AAB27 native-glass ops line; same 42-file diff on all five. Obsolete. Delete (optional single tag). |
| feature/cms-dashboard-d3-side-by-side-trend-legend-20261021 | 4 | 2026-10-21 | PR MERGED #79:MERGED | 2/2/0 of 4 | Squash-merged PR; content on main. Delete. |
| ops/cloudlinux-deploy-and-16kb-verifier-20261021 | 3 | 2026-10-21 | PR MERGED #85:MERGED | 7/0/0 of 7 | Squash-merged PR; content on main. Delete. |
| ops/android-v15-production-readonly-eas-20261021 | 46 | 2026-10-21 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/23/19 of 42 | v1.5 AAB27 native-glass ops line; same 42-file diff on all five. Obsolete. Delete (optional single tag). |
| ops/android-v15-one-production-aab-20261021 | 48 | 2026-10-21 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/23/19 of 42 | v1.5 AAB27 native-glass ops line; same 42-file diff on all five. Obsolete. Delete (optional single tag). |
| ops/android-v15-nativegauss-upstream-aar-audit-20261021 | 48 | 2026-10-21 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/23/19 of 42 | v1.5 AAB27 native-glass ops line; same 42-file diff on all five. Obsolete. Delete (optional single tag). |
| feat/b797-first-payment-warranty-20261022 | 29 | 2026-10-22 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/23/30 of 53 | Earlier, larger warranty design (24 code files absent). Replaced by the B798 isolation line, merged as PR 91; main has WarrantyIssuanceService and migration 2026_10_23_010000. Delete (optional tag). |
| feat/b797-pdf-terms-20261023 | 5 | 2026-10-22 | PR MERGED #90:MERGED | 12/5/0 of 17 | Squash-merged PR; content on main. Delete. |
| feat/b796-operator-motion-tracking-20261022 | 13 | 2026-10-22 | PR MERGED #88:MERGED | 10/6/0 of 16 | Squash-merged PR; content on main. Delete. |
| docs/b796-production-receipts-798-799-20261022 | 2 | 2026-10-22 | PR MERGED #89:MERGED | 0/2/0 of 2 | Squash-merged PR; content on main. Delete. |
| feat/b794-ui-polish-20261022 | 15 | 2026-10-22 | PR MERGED #86:MERGED | 9/5/1 of 15 | Squash-merged PR; content on main. Delete. |
| feat/b798-first-payment-warranty-expiry-isolation | 9 | 2026-10-23 | PR MERGED #91:MERGED | 15/6/0 of 21 | Squash-merged PR; content on main. Delete. |
| fix/b902-payment-transition-notifications-20261025 | 89 | 2026-10-25 | PR CLOSED unmerged #110:CLOSED | 11/24/50 of 85 | Closed PR 110. Contains a full copy of the PR 99-109 chain (50 files absent on main), so it carries the S1 files too. Do not delete before S1 is decided. |
| fix/android-v15-16kb-nativegauss-20261021 | 81 | 2026-10-25 | PR MERGED #82:MERGED | 11/25/18 of 54 | Squash-merged PR; content on main. Delete. |
| fix/b905-warranty-expiry-live-eligibility-20261026 | 97 | 2026-10-26 | PR CLOSED unmerged #111:CLOSED | 13/24/51 of 88 | Closed PR 111. Same as fix/b902: carries the S1 files. Main has the B905 test and policy. |
| release/android-v15-production-aab-one-20261021 | 82 | 2026-10-28 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 11/25/18 of 54 | v1.5 native-glass AAB27 line (16 KiB rejected per issue 52). Native glass components absent on main and PR 177. Obsolete. Delete (optional tag). |
| fix/b997r-cloudlinux-findmnt-compat-20261030 | 1 | 2026-10-30 | PR CLOSED unmerged #119:CLOSED | 0/0/1 of 1 | One HOLD doc only on this branch (closed PR 119). Delete. |
| docs/b997r-findmnt-cloudlinux-recovery-20261030 | 2 | 2026-10-30 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/0/2 of 2 | Only copy of B997R findmnt HOLD doc and compat .sh. Salvage or tag, then delete. |
| b1017-isolated-npm12-eas26-20261031 | 1 | 2026-10-31 | APPLIED (git cherry: all patches on main) | n/a (patches applied) | Patches already on main. Delete. |
| fix/b1008-cms-mobile-dashboard-quick-actions-20261031 | 2 | 2026-10-31 | PR MERGED #120:MERGED | 3/2/0 of 5 | Squash-merged PR; content on main. Delete. |
| fix/b1012-alt-node22-eas-cli-runtime-20261031 | 2 | 2026-10-31 | PR MERGED #121:MERGED | 6/3/0 of 9 | Squash-merged PR; content on main. Delete. |
| m17-sdk58-isolated-upgrade-20261031 | 17 | 2026-11-01 | INSIDE OPEN PR PR180,PR177,PR179 | 0/24/5 of 29 | Commits live inside an open PR head; delete only after that PR is closed or merged. |
| feat/b1030-eas27-npm12-sdk58-audit-20261101 | 19 | 2026-11-01 | INSIDE OPEN PR PR180,PR177,PR179 | 0/24/8 of 32 | Commits live inside an open PR head; delete only after that PR is closed or merged. |
| feat/b1029-m17-sdk58-play-rc-source-20261101 | 18 | 2026-11-01 | INSIDE OPEN PR PR180,PR177,PR179 | 0/24/7 of 31 | Commits live inside an open PR head; delete only after that PR is closed or merged. |
| feat/b1032-eas27-single-authority-host-isolated-20261101 | 39 | 2026-11-01 | INSIDE OPEN PR PR180,PR177,PR179 | 0/30/17 of 47 | Commits live inside an open PR head; delete only after that PR is closed or merged. |
| feat/b1031-nitro2-eas27-npm12-android16kb-20261101 | 31 | 2026-11-01 | INSIDE OPEN PR PR180,PR177,PR179 | 0/28/12 of 40 | Commits live inside an open PR head; delete only after that PR is closed or merged. |
| codex/b1145-migrate-unclamp-20261108 | 97 | 2026-11-08 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 9/11/143 of 163 | Tip of the B1131-B1145 installer line (97 commits). Superseded by B1149-B1152. Tag if history is wanted, otherwise delete. |
| codex/b1146-legal-overlay-20261108 | 8 | 2026-11-08 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 20/0/1 of 21 | 20 of 21 files identical on main (workflow missing). Delete. |
| codex/b1144-schema-compat-20261108 | 2 | 2026-11-08 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/0/3 of 3 | CI workflow + smoke test for B1144 schema compat. Delete. |
| codex/b1131-stage-helper-ci-20261104 | 51 | 2026-11-08 | SUBSUMED by codex/b1144-lean-install-20261108, codex/b1145-migrate-unclamp-20261108 | 3/7/121 of 131 | Installer iteration. tools/storefront/b1131..b1143 exist only on this line; main carries b1134 and b1144-b1152. Superseded; tag if iteration history is wanted. |
| codex/b1144-lean-install-20261108 | 82 | 2026-11-08 | SUBSUMED by codex/b1145-migrate-unclamp-20261108 | 6/9/134 of 149 | Installer iteration contained in b1145. Superseded by B1149-B1152 on main. |
| codex/legal-pages-menu-20261108 | 2 | 2026-11-08 | PR MERGED #152:MERGED | 14/9/0 of 23 | Squash-merged PR; content on main. Delete. |
| codex/ci-main-full-recheck-20261108 | 1 | 2026-11-08 | PR CLOSED unmerged #153:CLOSED | 0/1/0 of 1 | PR closed unmerged; check the identical/differs/missing counts, otherwise delete. |
| codex/storefront-glass-20261109 | 20 | 2026-11-09 | PR MERGED #155:MERGED | 4/18/0 of 22 | Squash-merged PR; content on main. Delete. |
| codex/catalog-redesign-20261108 | 11 | 2026-11-09 | PR CLOSED unmerged #154:CLOSED | 2/12/0 of 14 | PR closed unmerged; check the identical/differs/missing counts, otherwise delete. |
| codex/b1147-catalog-overlay-20261109 | 20 | 2026-11-09 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 32/12/1 of 45 | 32 of 45 files identical on main, rest changed later. Delete. |
| codex/b1148-glass-overlay-20261109 | 20 | 2026-11-09 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 44/19/0 of 63 | 44 of 63 files identical on main, rest changed later. Delete. |
| codex/docs-reports-1104-1113-20261110 | 1 | 2026-11-10 | APPLIED (git cherry: all patches on main) | n/a (patches applied) | Patches already on main. Delete. |
| tmp/mutation-check-20261112 | 1 | 2026-11-12 | UNIQUE (no PR, not contained, cherry has unapplied patches) | 0/0/3 of 3 | Throwaway mutation-check script and workflow. Delete. |
| codex/cookie-text-shop-theme-20261113 | 1 | 2026-11-13 | PR CLOSED unmerged #169:CLOSED | 0/2/0 of 2 | PR closed unmerged; check the identical/differs/missing counts, otherwise delete. |

Classification counts for (b): APPLIED 3; INSIDE 6; PR 32; SUBSUMED 2; UNIQUE 32.

### 3.3 List (c): heads of the 41 old open PRs

| PR | Head branch | Ahead | Last commit | cherry +/- | ident/differs/missing | Verdict |
|---:|---|---:|---|---|---|---|
| 1 | audit/android-native-497 | 23 | 2026-10-01 | +23/-0 | 9/6/8 of 23 | OBSOLETE |
| 3 | docs/build23-24-native-ci-lessons-build25-gates-20261008 | 2 | 2026-10-08 | +2/-0 | 0/1/1 of 2 | DOC-ONLY |
| 33 | integration/ecc-guided-agents-20261018 | 31 | 2026-10-18 | +31/-0 | 0/0/19 of 19 | OBSOLETE |
| 34 | qa/cms-lg2-production-public-20261018 | 4 | 2026-10-18 | +4/-0 | 0/0/2 of 2 | OBSOLETE |
| 47 | feature/android-lg03-wave2-native-navigation-candidate-20261020 | 27 | 2026-10-20 | +27/-0 | 0/10/8 of 18 | OBSOLETE |
| 48 | feature/android-lg04-wave1-readable-header-glass-20261020 | 38 | 2026-10-20 | +38/-0 | 0/11/14 of 25 | OBSOLETE |
| 49 | feature/android-lg04-wave2-catalog-controls-20261020 | 43 | 2026-10-20 | +43/-0 | 0/12/16 of 28 | OBSOLETE |
| 50 | feature/android-lg04-wave3-accessible-action-sheets-20261020 | 53 | 2026-10-20 | +53/-0 | 0/14/19 of 33 | OBSOLETE |
| 51 | feature/android-lg04-wave4-home-admin-shortcuts-20261020 | 55 | 2026-10-20 | +55/-0 | 0/16/23 of 39 | OBSOLETE |
| 53 | ops/android-lg04-one-internal-uat-apk-20261020 | 57 | 2026-10-20 | +57/-0 | 0/16/24 of 40 | OBSOLETE |
| 54 | feature/catalog-laptop-chargers-types-brands-watt-20261020 | 8 | 2026-10-20 | +8/-0 | 0/1/6 of 7 | SUPERSEDED |
| 55 | feature/android-lg04-wave5-navigation-orders-polish-20261020 | 66 | 2026-10-20 | +66/-0 | 5/20/25 of 50 | OBSOLETE |
| 56 | feature/catalog-laptop-chargers-connector-taxonomy-20261020 | 56 | 2026-10-20 | +56/-0 | 0/16/26 of 42 | SUPERSEDED |
| 58 | feature/catalog-laptop-chargers-20261020 | 57 | 2026-10-20 | +57/-0 | 0/19/27 of 46 | HAS UNSHIPPED VALUE (U2) |
| 59 | integration/lg04-wave5-laptop-chargers-source-20261020 | 68 | 2026-10-20 | +68/-0 | 5/23/33 of 61 | HAS UNSHIPPED VALUE (U2,U3) |
| 61 | feature/android-lg04-wave5-home-admin-orders-polish-20261020 | 66 | 2026-10-20 | +66/-0 | 0/20/26 of 46 | OBSOLETE |
| 67 | feat/warranty-expiry-opt-in-owner-scope-20261021 | 15 | 2026-10-21 | +14/-0 | 4/5/2 of 11 | HAS UNSHIPPED VALUE (U1) |
| 70 | feature/build26-native-liquid-glass-foundation-main-20261021 | 7 | 2026-10-21 | +5/-2 | 1/9/3 of 13 | OBSOLETE |
| 71 | feature/build26-lg04-wave1-header-main-20261021 | 12 | 2026-10-21 | +10/-2 | 1/10/8 of 19 | OBSOLETE |
| 72 | feature/build26-lg04-wave2-catalog-main-20261021 | 17 | 2026-10-21 | +13/-2 | 1/11/10 of 22 | OBSOLETE |
| 73 | feature/build26-lg04-wave3-sheets-main-20261021 | 23 | 2026-10-21 | +18/-2 | 1/13/13 of 27 | OBSOLETE |
| 74 | feature/build26-lg04-wave4-native-shortcuts-main-20261021 | 31 | 2026-10-21 | +26/-2 | 0/16/15 of 31 | OBSOLETE |
| 75 | integration/build26-lg04-waves1-4-current-main-20261021 | 33 | 2026-10-21 | +28/-2 | 0/16/16 of 32 | OBSOLETE |
| 76 | release/v1-5-warranty-mobile-consolidation-20261021 | 37 | 2026-10-21 | +32/-2 | 0/18/18 of 36 | HAS UNSHIPPED VALUE (U1) |
| 77 | release/v1.5.0-android-native-candidate-20261021 | 44 | 2026-10-21 | +39/-2 | 0/23/18 of 41 | HAS UNSHIPPED VALUE (U1) |
| 80 | integration/android-v15-build26-source-qa-20261021 | 46 | 2026-10-21 | +39/-2 | 0/23/18 of 41 | HAS UNSHIPPED VALUE (U1) |
| 87 | feat/b796-operator-orders-glass-tracking-20261022 | 18 | 2026-10-22 | +18/-0 | 0/15/4 of 19 | SUPERSEDED |
| 92 | feat/b799-public-retail-full-flow-20261023 | 8 | 2026-10-23 | +8/-0 | 0/6/14 of 20 | SUPERSEDED |
| 94 | chore/connector-write-proof-20261024-r813 | 2 | 2026-10-24 | +2/-0 | 0/0/1 of 1 | DOC-ONLY |
| 98 | test/b821-receipt-privacy-negative-20261024 | 1 | 2026-10-24 | +1/-0 | 0/0/1 of 1 | HAS UNSHIPPED VALUE (U6) |
| 99 | test/b845-b846-replay-negative-controls-20261025 | 9 | 2026-10-25 | +9/-0 | 0/2/14 of 16 | HAS UNSHIPPED VALUE (U6) |
| 100 | fix/b858-laravel-composer-advisories-20261025 | 20 | 2026-10-25 | +20/-0 | 0/7/30 of 37 | SUPERSEDED |
| 102 | fix/b872-public-no-script-csp-20261025 | 23 | 2026-10-25 | +23/-0 | 2/10/36 of 48 | SUPERSEDED |
| 103 | fix/b877-public-media-and-cart-20261025 | 24 | 2026-10-25 | +24/-0 | 3/11/38 of 52 | SUPERSEDED |
| 105 | fix/b883-auth-local-redirects-20261025 | 27 | 2026-10-25 | +27/-0 | 3/17/43 of 63 | HAS UNSHIPPED VALUE (S1/U5) |
| 106 | fix/b888-storefront-checkout-origin-20261025 | 58 | 2026-10-25 | +58/-0 | 6/17/45 of 68 | SUPERSEDED |
| 107 | fix/b893-storefront-media-path-20261025 | 63 | 2026-10-25 | +63/-0 | 7/17/46 of 70 | SUPERSEDED |
| 108 | fix/b896-storefront-register-csp-20261025 | 71 | 2026-10-25 | +71/-0 | 7/17/48 of 72 | SUPERSEDED |
| 109 | fix/b899-storefront-html-link-origin-20261025 | 85 | 2026-10-25 | +78/-7 | 10/23/49 of 82 | SUPERSEDED |
| 145 | codex/b1111-observed-host-php-lock-compat-20261104 | 5 | 2026-11-04 | +5/-0 | 0/0/3 of 3 | OBSOLETE |
| 151 | codex/b1124-passive-notification-consent-20261104 | 2 | 2026-11-04 | +2/-0 | 0/0/2 of 2 | HAS UNSHIPPED VALUE (U4) |

Heads of current PRs (ignored): #175 codex/redesign-admin-visual-qa-20261114; #176 codex/redesign-tokens-20261114; #177 codex/mobile-sdk58-into-main-20261114; #178 codex/redesign-components-20261114; #179 codex/mobile-sdk58-patch-58025-20261114; #180 codex/mobile-runtime-no-sdk57-default-20261114; #181 codex/storefront-logo-height-setting-20261114; #182 codex/storefront-catalog-view-perpage-20261114; #183 codex/b1099-gates-allowlist-threshold-20261114.


### 3.4 Files that exist only on unmerged branches (check before deleting those branches)

54 files under `docs/` are on branches that are not merged into main (checked over list (b) and list (c) heads; the first branch that carries each file is shown). 29 are under `docs/security` (PR 99 chain: B845-B857 notes and python tools), 13 under `docs/operations` (Build23-24 reports 551-562, B997R, report 592), 7 under `docs/superpowers`, plus the Build23-24 postmortem, the two ECC docs, one review and one third-party licence.


```text
docs/ECC-ADOPTION-20261018.md <- integration/ecc-guided-agents-20261018 
docs/ECC-CLOUDLINUX-CONTRACT.md <- integration/ecc-guided-agents-20261018 
docs/incident-reviews/2026-10-08-build23-24-batch528-native-ci-postmortem.md <- docs/build23-24-native-ci-lessons-build25-gates-20261008 
docs/operations/551-BATCH528-NATIVE-GATE-SCOPE.md <- audit/batch528-native-release-gate 
docs/operations/552-BATCH528R-COMPOSITE-AUDIT-SCOPE.md <- audit/batch528-native-release-gate 
docs/operations/555-BATCH528R2B-DEPENDENCY-GRAPH-DIFF-INVENTORY.md <- audit/batch528-native-release-gate 
docs/operations/558-BATCH528R3B-NATIVE-DISK-CAPACITY-RECOVERY.md <- audit/batch528-native-release-gate 
docs/operations/559-BATCH528R3C-REQUIRED-CMAKE-BOOTSTRAP-RECOVERY.md <- audit/batch528-native-release-gate 
docs/operations/560-BATCH528R3D-WORKLETS-LINT-KAMODULE-RECOVERY.md <- audit/batch528-native-release-gate 
docs/operations/561-BATCH528R3E-EARLY-LINT-RECOVERY.md <- audit/batch528-native-release-gate 
docs/operations/562-BATCH528R3F-SPLASH-API33-RESOURCE-CORRECTION.md <- audit/batch528-native-release-gate 
docs/operations/592-BATCH558-MOBILE-WORKSPACE-V2-SOURCE-REPORT.md <- feat/ald1n-mobile-workspace-v2-20261010 
docs/operations/B997R-CLOUDLINUX-MISSING-FINDMNT-HOLD-20261030.md <- fix/b997r-cloudlinux-findmnt-compat-20261030 
docs/operations/B997R-FINDMNT-HOLD-AND-SAFE-RECOVERY-20261030.md <- docs/b997r-findmnt-cloudlinux-recovery-20261030 
docs/operations/B997R-FINDMNT-MOUNTINFO-COMPAT.sh <- docs/b997r-findmnt-cloudlinux-recovery-20261030 
docs/operations/connector-write-proof-20261024-r813.md <- chore/connector-write-proof-20261024-r813 
docs/reviews/ecc-cloudlinux-ci-20261018.md <- integration/ecc-guided-agents-20261018 
docs/security/B845-B846-SCOPED-NEGATIVE-CONTROLS.md <- fix/b858-laravel-composer-advisories-20261025 (+10)
docs/security/B847-B849-PUBLIC-CATALOG-SECURITY-QA.md <- fix/b858-laravel-composer-advisories-20261025 (+10)
docs/security/B854-B857-ROUTE-SURFACE-SCA.md <- fix/b858-laravel-composer-advisories-20261025 (+10)
docs/security/B858-COMPOSER-ADVISORY-REPAIR.md <- fix/b858-laravel-composer-advisories-20261025 (+9)
docs/security/B859-B861-DEPENDENCY-REGRESSION-AND-M16.md <- fix/b858-laravel-composer-advisories-20261025 (+9)
docs/security/B862-B865-OWASP-REQUIREMENT-TEXT-AND-SCOPING.md <- fix/b858-laravel-composer-advisories-20261025 (+9)
docs/security/B866-B869-HTTP-PRIVACY-CSP-PARTIAL.md <- fix/b858-laravel-composer-advisories-20261025 (+9)
docs/security/B870-COMPOSER-SOLVER-IDEMPOTENCE.md <- fix/b858-laravel-composer-advisories-20261025 (+9)
docs/security/B871-LOCK-ONLY-585-CASE-REBASE.md <- fix/b858-laravel-composer-advisories-20261025 (+9)
docs/security/B872-B875-PUBLIC-NOSCRIPT-CSP.md <- fix/b872-public-no-script-csp-20261025 (+8)
docs/security/B876-ROLE-ID-STABILITY.md <- fix/b872-public-no-script-csp-20261025 (+8)
docs/security/B877-B881-MEDIA-ORIGIN-AND-B803-TRIAGE.md <- fix/b877-public-media-and-cart-20261025 (+7)
docs/security/B883-B887-AUTH-REDIRECTS-56-IDENTITY-AUDIT.md <- fix/b883-auth-local-redirects-20261025 (+6)
docs/security/tools/b846_receipt_scope.py <- fix/b858-laravel-composer-advisories-20261025 (+10)
docs/security/tools/b849_catalog_evidence.py <- fix/b858-laravel-composer-advisories-20261025 (+10)
docs/security/tools/b854_route_surface.py <- fix/b858-laravel-composer-advisories-20261025 (+10)
docs/security/tools/b857_composer_audit_gate.py <- fix/b858-laravel-composer-advisories-20261025 (+10)
docs/security/tools/b859_dependency_lock_delta.py <- fix/b858-laravel-composer-advisories-20261025 (+9)
docs/security/tools/b862_owasp_review_queue.py <- fix/b858-laravel-composer-advisories-20261025 (+9)
docs/security/tools/b871_dependency_lock_delta.py <- fix/b858-laravel-composer-advisories-20261025 (+9)
docs/security/tools/b873_public_blade_csp_inventory.py <- fix/b872-public-no-script-csp-20261025 (+8)
docs/security/tools/test_b846_receipt_scope.py <- fix/b858-laravel-composer-advisories-20261025 (+10)
docs/security/tools/test_b849_catalog_evidence.py <- fix/b858-laravel-composer-advisories-20261025 (+10)
docs/security/tools/test_b854_route_surface.py <- fix/b858-laravel-composer-advisories-20261025 (+10)
docs/security/tools/test_b857_composer_audit_gate.py <- fix/b858-laravel-composer-advisories-20261025 (+10)
docs/security/tools/test_b859_dependency_lock_delta.py <- fix/b858-laravel-composer-advisories-20261025 (+9)
docs/security/tools/test_b862_owasp_review_queue.py <- fix/b858-laravel-composer-advisories-20261025 (+9)
docs/security/tools/test_b871_dependency_lock_delta.py <- fix/b858-laravel-composer-advisories-20261025 (+9)
docs/security/tools/test_b873_public_blade_csp_inventory.py <- fix/b872-public-no-script-csp-20261025 (+8)
docs/superpowers/plans/2026-10-07-batch528-native-release-gate.md <- audit/batch528-native-release-gate 
docs/superpowers/plans/2026-10-08-build25-verified-aab-auto-submit-implementation.md <- docs/build25-release-controller-design-20261008 
docs/superpowers/plans/2026-10-20-ald1n-lg04-wave4-home-admin.md <- feature/android-lg04-wave4-home-admin-shortcuts-20261020 (+7)
docs/superpowers/plans/2026-10-20-catalog-laptop-chargers.md <- feature/catalog-laptop-chargers-connector-taxonomy-20261020 
docs/superpowers/plans/2026-10-20-lg04-wave5-chargers-integration.md <- integration/lg04-wave5-laptop-chargers-source-20261020 
docs/superpowers/specs/2026-10-07-build22-24-deep-audit.md <- audit/batch528-native-release-gate 
docs/superpowers/specs/2026-10-08-build25-verified-aab-auto-submit-design.md <- docs/build25-release-controller-design-20261008 
docs/third-party/ECC-MIT-LICENSE.txt <- integration/ecc-guided-agents-20261018 
```


### 3.5 Tags, releases and other clutter

| Item | State |
|---|---|
| Tags | 1: `ald1n-operations-archive-pre-build18-20260919` (commit c7901e0d, 2026-09-19, "archive Build18 foundation recovery evidence"). Looks intentional; keep |
| GitHub releases | none (`gh release list` returns an empty list) |
| Environments | 0 (`gh api .../environments` total_count 0) |
| Stray file on main | `apps/cms/current/app/Console/Commands/AuthDoctorCommand.php.backup` (backup copy of a live command) |
| Odd root entries on main | `.locks/cms-post-ux-icon-size-centering-visual-confirmation-batch3.lock`, `releases/mobile/mobile-v0.3.1-source-before-v0.4.0-20260808-112814.tar.gz.sha256` (a checksum without the archive in the tree). UNCHECKED whether either is referenced by any script |
| `docs/operations` | 833 files on main (reports; large but intentional per AGENTS.md) |
| `tools/storefront` | 16 per-batch directories on main (b1046 ... b1152); older batches are likely dead weight, UNCHECKED which are still referenced |
| `react-native-android-liquid-glass` | still in `apps/mobile/current/package.json:73` with no importer under `src` (see section 1.3) |

Workflows on main: 75 files. A static read of each `on:` block (regex based, so UNCHECKED for edge cases) found the following that can only fire on a push to, or a PR against, one named non-main branch. All of those branch names still exist today (all in list (a), except `ops/cloudlinux-deploy-and-16kb-verifier-20261021`, which is in list (b) as a squash-merged PR 85), so deleting the branches turns these workflows into dead files:

| Workflow | Trigger | Pinned branch(es) |
|---|---|---|
| ald1n-native-528.yml | push only | audit/build25-native-ci-explicit-trigger-20261009 |
| android-aab27-relro-readonly.yml | push only | ops/cloudlinux-deploy-and-16kb-verifier-20261021 |
| batch704-qa-controller.yml | push only | audit/batch704-release-orchestrator-20261017 |
| batch705-release-preview.yml | push only | audit/batch705-release-readiness-20261017 |
| batch709-release-contract.yml | push only | audit/batch709-release-contract-20261017 |
| issue19-enum-safety-audit.yml | push only | 4 audit/* branches of 2026-10-17 |
| issue19-laravel-mysql-qa.yml | push only | 4 audit/* branches plus fix/issue19-laravel-mysql-baseline-20261016 |
| issue19-mariadb-1011-qa.yml | push only | 3 audit/* branches of 2026-10-17 |
| pr16-cross-feature-main-vs-candidate.yml | push only | feature/user-retirement-batch2-20261016 |
| user-retirement-draft-qa.yml | push only | feature/user-retirement-batch2-20261016 |
| b1075-storefront-recovery.yml | pull_request only | base branch feat/b799-public-retail-catalog-cart-checkout |

Workflows that listen to pushes on a named branch plus a dispatch or PR trigger (still reachable, but the push half is dead after branch cleanup): b1149-installer.yml .. b1152-installer.yml (their own installer branches), b794-cms-ui-qa.yml, cms-dashboard-d3-side-by-side-qa.yml, cms-dashboard-full-bleed-aurora-qa.yml, cms-gainward-gpu-series-source-qa.yml, cms-lg04-wave5-order-summary-source-qa.yml, lg04-wave5-home-admin-native-safe-qa.yml, mobile-home-focus-navigation-qa.yml, mobile-lg04-wave5-orders-current-main-qa.yml, mobile-product-editor-keyboard-reveal-source-qa.yml, warranty-expiry-backend-main-qa.yml, ald1n-v15-release-candidate-no-build.yml, android-v15-nativegauss-16kb-qa.yml, batch721-integrated-candidate-mariadb.yml, liquid-glass-cms-qa.yml, catalog-pagination-hotfix-qa.yml.

Manual-only (`workflow_dispatch`): b1025-m17-sdk58-eas-preview-android.yml, b1041-m17-sdk58-eas27-signed-aab-manual.yml.

No workflow has a `schedule` trigger.

## 4. What I did not check

- No test, PHP, Node or CI was executed. Every "main has it" is a static comparison of code and test files.
- Host state (inode cleanup for issue 95, whether `WARRANTY_ACTIVATION_RECOVERY_ENABLED` is on, whether the PR 145 host question is closed).
- Whether the live Play release (versionCode 29) equals the head of PR 177; I compared against PR 177's head.
- Whether the U6 tests and the 14 tests of PR 151 pass or fail on current main.
- Closed and merged PRs outside this list; only their state was read for the branch classification.
- Pushing, deleting and closing were not tried. Squash-merged work shows as "unmerged" in `git branch --merged`; for list (b) I used the PR record and the per-file identity counts instead.
- Raw data used for the tables was generated with git and gh into the scratchpad of this session; nothing in this file contains secrets.
