# B866–B869 — CSP base URI hardening and isolated HTTP private-response checks

2026-10-25. **Scoped PHP8.4/MariaDB CI only.** Existing Draft PR100 stacks above Draft PR99/PR93. No CloudLinux production alteration, real customers, mail, active scanner/CSRF, Play/EAS, source merge or release.

## B866: verified source security gap in ASVS 5.0.0 V3.4.3

The pinned ASVS v5.0.0 Level 2 requirement `V3.4.3` requires a Content-Security-Policy that includes `object-src 'none'` and **`base-uri 'none'`** as part of a properly defined script/content loading policy. Before this batch, `apps/cms/current/app/Http/Middleware/SecurityHeaders.php` set `base-uri 'self'`, permitting HTML `<base>` to redirect relative URL resolution under the same origin. The initial exact-source body was `d8142bb61edd0dd5737f065a1b1c53e5782bf942`; the existing policy also does not set an effective `script-src` or `default-src` allowlist. That latter gap is material: **the complete ASVS V3.4.3 control is still NOT VERIFIED and not marked PASS**.

The only application source mutation in this batch is changing `base-uri 'self'` to `base-uri 'none'` in the global SecurityHeaders middleware, with the existing frame/form/object-source and cross-origin policies unchanged. Runtime CI must confirm there are no application Blade HTML `<base>` elements before approving this narrow change. Full script policy rollout needs a deliberate nonce/hash and inline-JavaScript audit plus actual browser/UAT; do not enforce a new script-src blindly because the current CMS layouts contain real inline scripts.

## B867–B868: actual framework HTTP behaviour in disposable MariaDB

New independent `PublicResponseHeadersB866Test.php` exercises 11 cases on normal Laravel test HTTP requests (not mocks of the middleware). The test covers 200 login/password forms, storefront-OFF 404 on registration/checkout/private receipt/document/warranty, JSON 404 capability denial, and global security/CORS/host-header response controls. It checks: `Cache-Control: private, no-store`, noindex/noarchive, `X-Content-Type-Options: nosniff`, appropriate `Referrer-Policy: no-referrer`, CSP `base-uri 'none'`, `object-src 'none'`, `frame-ancestors 'self'`, and absence of attacker-controlled CORS allow headers. Only synthetic test strings and standard Laravel session state are used; no actual bearer token is issued in these tests.

These are narrow real HTTP checks, not coverage of actual CloudLinux proxy headers, TLS/HSTS, browser CSP compatibility, independent penetration testing or all possible status codes. There is deliberately **no new standalone CSRF probe**, and no alteration/waiver of the provider-denied B805/B809/B815 fixtures.

## B869: existing protected functional invariants remain checked

The bounded CI job reruns existing, unchanged real framework PHP test classes, with all source and test identities intact:
- B836 registered-receipt/authenticated owner access: 15 tests.
- B847 public catalogue/media isolation: 15 tests.
- B843 checkout replay and stock/no-double-write: 11 tests.
- B866 privacy and CSP changes: 11 tests.

Total expected exactly **52 tests**, without one skipped, duplicated, failed, error or warned identity. The job verifies original source `base-uri 'self'` and patched source `base-uri 'none'`, hashes the JUnit result, and archives implementation and test evidence. This cannot convert the 368 OWASP items to PASS, does not waive the 56 B803 newly failing identities, and does not authorize the drafted Composer lock patch for production.

## Exact OWASP item relationship (PARTIAL SOURCE/HTTP EVIDENCE ONLY)

- `v5.0.0-3.4.3` (ASVS L2): base-uri corrected; CSP script enforcement and browser compatibility not done — control **NOT_RUN/PARTIAL_ONLY**, no full PASS.
- `v5.0.0-3.4.4` (ASVS L2): nosniff asserted on selected public response classes only.
- `v5.0.0-3.4.5` (ASVS L2): no-referrer asserted on sensitive paths only, not all external links.
- `v5.0.0-3.4.6` (ASVS L2): frame-ancestors asserted on selected public/error responses only.
- `v5.0.0-14.3.2` (ASVS L2): anti-cache asserted on a limited sensitive route/error matrix only.
- `v5.0.0-3.4.2` (ASVS L2): untrusted `Origin` and forwarded host do not yield ACAO/ACAC on login. Complete cross-origin route matrix still pending.

The 368-item source-of-truth queue remains **UNASSESSED/NOT_RUN (0 complete independent security passes)** until every applicable requirement receives its own reviewed scope, actual evidence and independent retest.

## Release and milestones

CMS security/release HOLD until 56 full-suite B803 regressions are appropriately reviewed/fixed, OWASP test matrix assessed, merchant terms/sender/CloudLinux backup and operator UAT. Composer package advisory repair remains unmerged/un-deployed. Android M16 native 16KiB emulator QA PASSED previously but signed EAS AAB, remote versionCode, real device UAT and Play acceptance remain HOLD; SDK58 remains deferred.

Owner acceptance remains **7/17 = 41.2%** overall, **7/15 = 46.7%** CMS, **6/6 = 100%** initial UI. No SSH request for this source-only work.
