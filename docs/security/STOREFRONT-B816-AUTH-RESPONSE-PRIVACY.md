# B816 — Browser privacy headers for tokenized authentication routes (partial security coverage)

Date: 2026-10-24. Source: Draft PR #93, `5e31333e9e2c7d48366b890e2e2ff3e88b969123`. Production B798 storefront OFF; no host/deployment request.

## Findings and minimized correction

**B816-F01 — one-time token URLs may be exposed to same-origin referrers.** Before B816, the application-wide `Referrer-Policy: same-origin` also applied to password-reset and account-activation GET URLs carrying one-time bearer credentials in the URL path, and to Google OAuth callback URLs carrying authorization code/state. This is a verified missing protective header in isolated responses; it is **not** evidence of an observed production exfiltration.

**B816-F02 — registration and OAuth callback responses lacked uniform no-store/indexing protection.** Redirects/validation messages and a prospective buyer form can contain session and personal data. Previous `SecurityHeaders` only applied no-store to some authentication routes. This is privacy defense in depth, not a proven shared-cache breach.

The sole runtime change is `apps/cms/current/app/Http/Middleware/SecurityHeaders.php`. The following named routes now receive `Referrer-Policy: no-referrer`, `Cache-Control: private, no-store, max-age=0`, `Pragma: no-cache`, and `X-Robots-Tag: noindex, nofollow, noarchive`:
- `login` / `login.store`
- `password.*` (forgot/reset GET, password update POST)
- `customer-activation.*`
- `public-shop.register` / `public-shop.register.submit`
- `auth.google.*` (OAuth redirect/callback)

Global CSP, X-Frame-Options, Permissions-Policy, HSTS and nosniff remain. Admin system-health/Turnstile cache headers are preserved independently. Guest public catalog browsing remains cache-eligible and retains its existing `same-origin` referrer policy. Nothing about customer login, purchasing, data, mail, roles or runtime release flags changes.

## RED/GREEN and exact coverage

- RED source `d8647211ae1bebbdb36757f7436ef0c363d385a5`, [CI run40110116447](https://github.com/AldinAga/ald1n-project/actions/runs/40110116447): **8 tests /17 assertions /7 expected failures** on sensitive route referrer headers; public catalog negative control passed. No flaky infrastructure errors.
- GREEN source `5e31333e9e2c7d48366b890e2e2ff3e88b969123`, [CI run40110368273](https://github.com/AldinAga/ald1n-project/actions/runs/40110368273): **8 tests /51 assertions PASS** on real Laravel/PHP8.4/MariaDB10.11.19, mail and OAuth delivery synthetic/disabled. Existing B809/B810 12/160, B811 4/91, B813 4/29, B814 4/25, B815 5/61 passed in the same scoped workflow.
- Tested GET paths: `/login`, `/forgot-password`, invalid `/reset-password/{80}`, invalid `/activate-account/{80}`, `/registracija`, locally rejected Google callback; POST registration validation response; public catalog unchanged. No real auth token, Google request, SMTP, CDN or production browser/network analysis.
- This B806 workflow as a whole still FAILS its untouched B799–B808 historical guest checkout tests. Scoped B816 GREEN is not a full QA, penetration or release pass.

## OWASP mapping — partial evidence, not control certification

| Reference | Relationship | Current WSTG/ASVS census status |
| --- | --- | --- |
| WSTG4.2 `WSTG-ATHN-06` Browser Cache Weaknesses | Auth and token URL no-store/private/nocache response tests | NOT_RUN full catalog control (partial source/header test only) |
| WSTG4.2 `WSTG-SESS-04` Exposed Session Variables | No-referrer for auth/activation/reset/OAuth URLs | NOT_RUN complete session-variable review |
| ASVS5.0.0 L2 cumulative `V3.4.1`–`V3.4.6` Browser Security Mechanism Headers | Selected response header checks and negative public-catalog control | NOT_RUN complete requirement-by-requirement verification |
| Plan workstreams `SEC07`, `SEC22`, `SEC23` | Authentication token exposure, cache/referrer and browser headers | PARTIAL ONLY; remaining cases not executed |

The pinned 368-row [B808 WSTG/ASVS register](STOREFRONT-WSTG42-ASVS500-L2-CENSUS-B808.csv) remains unchanged: every row still needs applicability and complete supported control-level evidence. This report adds a narrowly bounded test trail and **does not convert any whole control from NOT_RUN to PASS**.

## Release boundaries

Other blockers remain, including 48 untouched historical guest-positive failures, one B815 buyer OFF fixture failure whose attempted correction was safety-denied, exact-source full CI, 368-control assessment, legal merchant/payment/shipping approvals, real SMTP deliverability/SPF/DKIM/DMARC, FPM/OPcache, verified restore drill and owner buyer UAT. Do not retry or route around any denied fixture/packaging action. No SSH, migration, production write, storefront ON, Android/EAS/OTA/Play or inode cleanup.

## Exact-head integration of the stronger login contract

The first runtime GREEN `5e31333e9e2c7d48366b890e2e2ff3e88b969123` was target-PASS, but its full B803 regression reported **one additional mismatch** in the unrelated `SecurityHealthBackupTest::test_security_headers_and_request_id_are_present` assertion, which still expected the previous weaker `Referrer-Policy: same-origin` on `GET /login`. This was a test expectation conflicting with an intentional stricter security requirement, not evidence the new policy was broken.

The existing assertion was **strengthened** at source `aafcba888807ab13d0c12109d423c27734f60741` to require `no-referrer`, private/no-store and noindex while preserving nosniff, SAMEORIGIN frame policy and request ID assertions. The test was not disabled, deleted or waived. No B799–B808 guest-positive fixture or safety-denied B815 buyer test was changed. Full exact-head CI and complete manual security assessment remain release gates.

## Normalized Cache-Control assert (same protection, more robust validation)

At intermediate source `aafcba888807ab13d0c12109d423c27734f60741`, B803 full-run JUnit showed that Symfony normalized the response's `Cache-Control` directive order to `max-age=0, no-store, private`. The strengthened existing `SecurityHealthBackupTest` had incorrectly compared the raw header string to `private, no-store, max-age=0`; these values are semantically identical. At final source `fa8a1892dc9bc925c524a6184a660fd16d0bc5a8`, the same existing test still requires **all three directives** via independent `assertStringContainsString` checks. It also still requires exact `no-referrer`, `noindex`, `nosniff`, frame restrictions, and `X-Request-ID`. Nothing was skipped, omitted or waived. This is a semantic test correction rather than a runtime change or relaxation. [Final B816 targeted CI run](https://github.com/AldinAga/ald1n-project/actions/runs/40111258792) has the new privacy suite scoped PASS. The unchanged legacy guest-positive and separately safety-denied B815 buyer fixture remain full release blockers.

[Final exact-source snapshot on owner's Drive](https://docs.google.com/document/d/1HrKsITp08XrWZVajdiOuP-xS-Zvrf4lxIYVlhMf_tyg/edit). Full global comparator evidence remains separate; no claim of WSTG/ASVS completion.
