# B883–B887 — prevent attacker-origin auth redirects and classify inherited 56 without waivers

Date: 2026-10-25. **Stacked Draft** on PR103 exact source `985c289e5d2dc1f8532af4ea6b87eb18ba630478`; do NOT merge, deploy, run production CSRF probes, send actual mail, or issue a Play build.

## B883 — login authority and invalid credential/Turnstile response boundary

A source review identified the exact `AuthenticatedSessionController` flow: rejected credentials and Turnstile call Laravel `back()`, which may follow an attacker-supplied `Referer`. Successful staff login and staff logout used `redirect()->route(...)`, which can create an absolute Location influenced by request authority if upstream host validation is incomplete. Public Kupac login was already relative. This is an **application source control weakness**; no exploitation of real production or third-party systems was performed or claimed.

Use explicitly **relative named-route locations**: active staff login `/`, active buyer `/korpa`, invalid credentials / Turnstile / malformed `LoginRequest` `/login`, and logout `/login`. Preserve original field-specific error flashes and **only** the username/email old input, never flash the password; preserve the intended registered buyer role/activation/session controls. Add `LoginRequest::getRedirectUrl()` override so FormRequest validation errors never take their redirect from an arbitrary Referer. **B887 follow-up:** stock Laravel validation handler converts even an internally generated relative route to a canonical APP_URL absolute Location; all four sensitive FormRequests now extend `LocalAuthFormRequest`, which returns a native local RedirectResponse for HTML validation errors, explicitly preserves field errors and safe old-input fields, and delegates JSON requests to stock Laravel 422 handling. Sensitive tokens/passwords are never flashed.

## B884 — password request and password reset remain on same-origin relative forms

Previously both `PasswordResetController::storeLinkRequest` `back()` variants (Turnstile failure and generic unknown/known address success) could respond to caller-supplied Referer. Success now returns relative `/forgot-password` with the same generic status; Turnstile failure returns the same local page with field error. Invalid email FormRequest overrides `getRedirectUrl` to `/forgot-password`. Existing server-side address lookup and fake-notification behavior are unchanged.

For reset POST, `ResetPasswordRequest::localFormLocation` returns only `/reset-password/{token}` when the untrusted token is **strictly 80 ASCII alphanumeric characters**, or `/forgot-password` otherwise; FormRequest validation errors and expired/unknown token errors use this safe path. Successful reset returns relative `/login`, keeping its existing status flash and server-side token revocation. The path is never built from Host/Forwarded headers and cannot contain an injected scheme, slash, backslash or query. Token remains a bearer secret: the existing `no-referrer`/`no-store` rules still apply; do not include it in logging/analytics. A fixed route is security-functional work, not an independently complete assessment of reset-token lifecycle.

## B885 — customer activation form validation and failures

`CustomerActivationController` formerly used `$request->validate()` and `back()` for Turnstile failure/unknown token, inheriting browser-supplied Referer redirect behavior. A new `CustomerActivationRequest` preserves the same exact token/password rules and moves automatic validation redirect to strictly local `/activate-account/{token}` for well-shaped 80-character ASCII alphanumeric token or `/login` for invalid/missing token. The controller's manual errors use this same path and existing session error flash; successful activation returns relative `/login`. Real activation token generation/replay, role, password, Turnstile service and customer email approval were not modified.

## B886 — isolated real HTTP and original compatible authentication suites

The new `AuthLocalRedirectB883Test.php` executes **17** real synthetic Laravel HTTP cases on disposable PHP8.4/MariaDB10.11.19 with fake email notifications, disabled external Turnstile and no production requests. Each adversarial POST uses hostile `Host`, `X-Forwarded-Host`, `X-Forwarded-Proto`, `Origin`, and **`Referer`** headers. Required outcomes: local exact Location, sensitive `no-referrer`/`private,no-store` headers, no plaintext credential flash, no unintended user/checkout writes, and authenticated staff/buyer behavior intact.

The same job re-runs these six existing tests sets unchanged: `AuthenticationTest` (6), `LoginDashboardFallbackTest` (5), `PasswordResetTest` (3), `RetiredActiveLoginB813Test` (4), `SensitiveAuthPostBoundaryB817Test` (6), `SensitiveAuthResponsePrivacyB816Test` (8) plus B866 (11) and B872 (13) for a total **73 expected case identities** (17 new + 56 existing). Every actual JUnit case identity must be present exactly once, with no skipped/warnings/failures/errors. Do not change historical B799/B805/B809/B815 guest/security fixtures or use denied standalone CSRF probing to make CI green. All calls synthetic/local only.

Real CI job, exact HEAD/source/JUnit and hash records must be obtained from completed GitHub Actions; **do not claim PASS before it succeeds**. An unchanged B803 full baseline-vs-current run is separately required, and global release remains HOLD until that run and all other approval gates pass.

### First B883 exact-head CI: RED evidence, independently corrected

The initial immutable [CI #40213320653](https://github.com/AldinAga/ald1n-project/actions/runs/40213320653) at `a54fa310ace9777fcd2ee4aa520981bb4cd5d1f2` ran **71 tests, 10 failures**. Root causes were **not** the old 56 production baseline: a manually constructed error response lacked RedirectResponse request context for Laravel's `onlyInput()` and threw HTTP500; Laravel FormRequest validation still absolutized safe APP_URL redirects (`https://cms.ald1n.com/login` instead of literal `/login`); logout lacked the stricter sensitive-route privacy headers. These are exact narrow new-implementation bugs, not grounds to rename, bypass, skip or relax tests.

The correction adds `setRequest($request)` to the login errors flow, a new `LocalAuthFormRequest` for strictly relative validation errors while retaining JSON 422, and includes `logout` in the same private/no-referrer security-header scope. Two additional synthetic JSON validation tests explicitly check 422 remains intact. This expands the **allowed** CMS changed-path list from 8 to 10 and targeted expectation from 71 to 73. The old RED CI record is retained; only fresh exact-head CI can establish eventual scoped PASS. All three previous B805/B809/B815 forbidden fixture edits remain untouched.

### Second B883 CI: 73/73 PHP green, fail-closed identity proof had a stale count

[CI #40213837492](https://github.com/AldinAga/ald1n-project/actions/runs/40213837492) at exact corrected source `1c7b0e3199a1e0bd8fe3ef6cfbc52ad697b01a59` ran **73 real PHPUnit tests / 785 assertions, 0 test failures/errors/warnings**. Its JUnit identity validator correctly refused to claim CI success because the *unique-ID length guard* still hard-coded the prior 71 cases while the full expected-class map and total case guard were already 73. The immutable raw JUnit was independently parsed: **73 total, 73 unique, all nine class counts exactly expected, zero missing IDs**. This is purely a validation-count mismatch; **no case is removed, renamed, skipped, or reclassified**. The validator now derives both total and unique bounds from the exact per-class expected map and additionally requires the expected sum = 73; next run must independently confirm. Full B803 release remains HOLD.

## B887 — all 56 inherited B803 red cases explicitly retained, not bypassed

Immutable [Report853](../../docs/operations/853-BATCH877-881-MEDIA-CART-CACHE-56-TRIAGE-20261025.md) and [B803 raw 610-case JUnit](https://drive.google.com/file/d/1y8h1b0l4GaaUBOQIy-x8lXwGoKAONPeb/view?usp=drivesdk) show production base **451 cases / 44 red**, candidate **610 cases / 98 red**, exactly **56 new red identities** versus the old base, unchanged from PR102. Reading all 56 original testcases gives a precise cross-class census:

| Failing test class | Identity count |
| --- | ---: |
| `StorefrontSecurityB806Test` | 16 |
| `PublicRetailCheckoutB799Test` | 14 |
| `StorefrontRoleBoundaryB808Test` | 7 |
| `PublicRetailPaymentB803Test` | 5 |
| `PublicOrderCapabilityB799Test` | 3 |
| `PublicReceiptSwitchBoundaryB818Test` | 3 |
| `PublicRetailBuyerCheckoutB802Test` | 3 |
| `StorefrontRegisteredBuyerLifecycleB811Test` | 2 |
| `BuyerCheckoutSessionBoundaryB815Test` | 1 |
| `StorefrontNotificationOriginB806Test` | 1 |
| `StorefrontRegistrationOnlyB809Test` | 1 |
| **Total, no waived/omitted identities** | **56** |

Observed symptoms are **40 expected HTTP 200 vs actual 302, 9 expected 200 vs 403, 3 expected 200 vs 404, 3 ValidationException, 1 undefined method in provider-denied B815**. These symptoms are not a claim all 56 share one root cause. **Do not** relax registered verified Kupac-only checkout, merchant policy, tracked portal sessions, private receipt ownership, no anonymous buyer capability, nor the explicitly provider-denied B805/B809/B815 test edit. Owner and project rules require independent safe reconciliation of obsolete test contracts vs actual defects before any source release; [Issue104](https://github.com/AldinAga/ald1n-project/issues/104) stays OPEN.

All **368 OWASP WSTG4.2/ASVS5 L1/L2** items remain UNASSESSED/NOT_RUN for *independent complete per-item assessment*; this login/forgot/activation local-origin work is targeted partial security evidence only. Public shop remains OFF in CloudLinux. Android M16 signed production AAB/versionCode>27/real 16KB UAT/Play acceptance HOLD, SDK58 postponed. Formal accepted milestones **7/17=41.2%**, CMS **7/15=46.7%**, initial UI **6/6=100%**. **No SSH requested, no production mutation, no cleanup.**

B883_BATCH_STATUS=IMPLEMENTED_PENDING_CI_EVIDENCE
B883_EXPECTED_REAL_HTTP_IDENTITIES=73
B803_HISTORICAL_NEW_RED=56_NO_WAIVER
FULL_OWASP_COMPLETE_PASS=0_OF_368
PRODUCTION_MUTATED=NO
