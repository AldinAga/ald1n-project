# Storefront penetration testing and remediation plan

Owner requirement: 2026-10-24. Mandatory before public storefront activation, and after material security-related changes. This is an execution plan, NOT a claim that penetration testing is complete.

## Authority and current state

Repository: AldinAga/ald1n-project. Existing application PR93. Observed source at plan creation: d4e9e2ba506e82d2f4b5ec639c6ce6b39748f34d. Production remains the B798 runtime, public storefront OFF. Owner Report815 has already completed B804 affected-path and two-backup prerequisites; do not replay B804. B805 is an incomplete local OFF-rollout preparation, not an executable production release. Any security source correction invalidates the old target-specific B805 payload; regenerate and retest it before delivery.

Scope: public catalog/product/media, cart, guest and registered Kupac checkout, private order/document/warranty access, authentication and session boundaries with SubAgent/Admin/SuperAdministrator, operator storefront settings/toggle, relevant API routes and infrastructure serving these features. Three public payments remain COD, bank transfer and payment at personal pickup/own delivery. No public deferred credit.

## What 'every known test' means operationally

Inventory EVERY test in the pinned OWASP WSTG v4.2 catalog and applicable OWASP ASVS 5.0.0 Level 2 controls; supplement with the application's actual attack surface, relevant dependency advisories and new findings before final release. For each catalog item record its exact version/identifier, applicability rationale, route/role, method, expected result and evidence. Do not equate an OWASP Top 10 checklist or a scanner run with complete coverage.

No honest test can establish that every possible attack has been exhausted or guarantee zero future vulnerabilities. The required deliverable is complete accounted-for coverage of the defined applicable catalog, with NOT_APPLICABLE reasons reviewed, plus all confirmed findings repaired and retested. 'Not tested', 'blocked' and 'not applicable' are different states and must never be counted as PASS.

References verified on 2026-10-24:
- OWASP WSTG stable v4.2: https://owasp.org/projects/web-security-testing-guide ; https://wstg.owasp.org/v42/
- OWASP ASVS stable 5.0.0: https://owasp.org/projects/asvs ; https://github.com/OWASP/ASVS
- ZAP active scan behavior and return codes: https://www.zaproxy.org/docs/docker/full-scan/

## Rules of engagement

Use disposable PHP 8.4 / MariaDB 10.11.19 CI or a separately approved isolated staging environment with synthetic users, products and orders. Pin exact source, dependencies, scanner images/rules and configuration; record UTC timestamps and hashes. Test as anonymous, Kupac A/B, SubAgent A/B, Admin and SuperAdministrator, including inactive/retired users and revoked sessions/tokens. Application target scope must be explicitly allowlisted.

Do not actively scan production, stress shared CloudLinux hosting, send real customer emails/payments/SMS, read real customer private data, create live orders, attack external merchant/carrier URLs, or test another tenant. Production TLS/proxy/webroot/FPM checks are a distinct authorized, bounded read-only stage. DoS/rate-limit stress, destructive uploads, request-smuggling and infrastructure tests require isolated infrastructure and explicit relevant permission; otherwise mark BLOCKED, not PASS. Never publish live secrets, credentials, bearer receipt URLs or personal data in GitHub evidence.

## Required test workstreams

Each row is a workstream, not a substitute for the complete item-level WSTG/ASVS inventory.

| ID | Workstream and required cases | Evidence / environment |
| --- | --- | --- |
| SEC01 | Route/method/parameter inventory; reachable debug, source, backup, .env, .git and admin surfaces | Source map plus isolated HTTP |
| SEC02 | Host and forwarded-host/proto poisoning, absolute URLs and redirects, canonical email receipt origin | Synthetic HTTP and captured mail |
| SEC03 | TLS, certificates, HSTS, proxy trust, HTTP downgrade and origin isolation | Approved infrastructure checks |
| SEC04 | Anonymous/Kupac/SubAgent/Admin/SuperAdmin role isolation, direct route/API calls and wildcard-token bypass | Synthetic role/route matrix |
| SEC05 | Horizontal access/IDOR/BOLA across order, item, document, warranty, shipment and user IDs | Two independent synthetic buyers |
| SEC06 | Capability entropy, at-rest hashing/encryption, cross-order scope, expiration, revocation, archived/purged records | Integration plus bounded negative requests |
| SEC07 | Authentication, activation, password reset, account enumeration, password rules and throttling | Isolated authentication suite |
| SEC08 | Session fixation, regeneration/logout/revocation, cookie Secure/HttpOnly/SameSite, session concurrency | Actual HTTP/browser, not test-only CSRF bypass |
| SEC09 | CSRF on cart/checkout/settings/toggle/payment endpoints; unsafe GET mutation; method overrides | Actual middleware HTTP requests |
| SEC10 | Reflected/stored/DOM XSS in product fields, filters, buyer data, notes, courier links and admin views | Payload corpus, rendered browser and sink review |
| SEC11 | SQL/NoSQL injection, LIKE wildcard escaping, sort/identifier allowlists, error-based leakage | Input corpus and DB invariants |
| SEC12 | Command/template/expression injection, unsafe deserialization and XML entities where present | Reachable sink inventory; justified N/A otherwise |
| SEC13 | Public media path traversal, encoding variants, symlinks, extension/MIME mismatch, SVG/HTML/polyglots | Synthetic filesystem/media fixtures |
| SEC14 | Upload controls, executable storage, attachment ownership, filename/archive/path limits | Isolated uploads only; no shell execution |
| SEC15 | SSRF, private/loopback/metadata destinations, DNS/redirect variants and outbound URL use | Controlled sinks only; no third-party probing |
| SEC16 | Open redirects, response splitting, CRLF, HTTP parameter pollution and duplicate/array/null parameters | Synthetic HTTP corpus |
| SEC17 | Price/currency/quantity/discount/cost/commission and customer/supplier/status mass-assignment tampering | Financial and stock invariants |
| SEC18 | COD/bank/in-person compatibility; no forged paid state, false delivery or premature warranty | Real operational integration on synthetic data |
| SEC19 | Checkout replay/nonces, differing payloads, buyer changes, concurrent last-unit sales and duplicate requests | Independent DB connections and HTTP processes |
| SEC20 | ON/OFF concurrency, stale policy approval, altered merchant terms/bank/role, withdrawal before commit | Concurrent and deterministic service tests |
| SEC21 | Rate limiting, brute-force enumeration, automation abuse, cart/session/input/output size limits | Bounded tests and explicit load ceilings |
| SEC22 | Cache isolation/private/no-store, bearer URL referrer leakage, history/indexing, CDN/cache poisoning | HTTP headers and browser/edge review |
| SEC23 | CORS, CSP, clickjacking, mixed content, MIME sniffing, framing and third-party assets | Browser and response headers |
| SEC24 | Logs/errors/audit trails/mail failure paths; redaction of PII, credentials and bearer tokens | Synthetic failures and log inspection |
| SEC25 | Dependency CVEs, lockfiles, abandoned packages, secrets scanning, build provenance and CI permissions | Pinned SCA/SAST/secret checks, reviewed findings |
| SEC26 | Business workflow abuse: cancellation/return/refund/partial payment, inventory and warranty integrity | Synthetic end-to-end state machine |
| SEC27 | Request smuggling, parser discrepancies, HTTP/2/proxy cache behavior and resource exhaustion where applicable | Isolated proxy stack; never blind production load |
| SEC28 | OFF rollout, migration boundaries, rollback, least privilege, backups and independent PHP-FPM provenance | Existing deployment gates, not waived by security CI |

## Execution sequence and task-level delivery

1. Reconstruct current source and Report815; record the exact milestone denominator and unresolved B805 rollout work. Keep all production switches unchanged.
2. Create an item-level coverage register with statuses NOT_RUN / PASS / FAIL / BLOCKED / NOT_APPLICABLE and versioned WSTG/ASVS references. Start source review and targeted security regressions on PR93. Do not open another competing storefront implementation.
3. For every reproducible finding, record a minimal synthetic failing test first, then make the smallest scoped correction, run the same test again and preserve RED/GREEN evidence. Add negative controls to prevent weakening assertions merely to pass.
4. Run authenticated and anonymous browser/manual logic testing and pinned automated SAST/SCA/secret/DAST tooling in the isolated target. Validate actual scanner alerts; scanner error or missing credentials is not clean security coverage.
5. Run the complete CMS baseline/candidate comparison and relevant existing role/payment/PDF/concurrency/browser suites on the exact corrected commit. Preserve inherited failures by test name; a comparison with zero new failures is not a globally green legacy suite.
6. Review all findings, retest the fixed commit, rerun the exploit family and linked business regressions, and update the coverage register. Recheck changed source after integration with fresh main. Keep review independence honest: author self-review is not independent penetration testing.
7. Regenerate the OFF-rollout payload/manifests for the final tested source, verify rollback/controller/DDL scope and current backups, then deliver one canonical integrity-checked SSH step when genuinely needed. Public ON additionally requires real merchant policy and production acceptance. No automated activation from test success.

## Finding and closure contract

Each finding must include ID, affected exact commit/route/role, severity and impact, prerequisites, synthetic reproduction, evidence, remediation commit, automated regression, independent retest result and remaining limitations. All confirmed findings must be fixed and patched; untested or accepted-risk exceptions cannot silently close the owner's requirement. Any unresolved critical/high finding blocks release; unresolved lower findings remain visible and must be resolved before claiming the complete penetration task finished. A documented false positive or genuinely inapplicable case needs a reason, not a blanket ignore.

Security completion requires every applicable planned item accounted for, no unresolved confirmed exploitable finding, successful fix retests, exact-source evidence, and documented residual uncertainty. An initial targeted batch is not a comprehensive penetration-test certificate.

## Progress display: acceptance versus actual batch work

The existing fixed ledger from Report796 has 17 equal-weight acceptance milestones: M01-M15 CMS, M16 Android 16 KB signed release, M17 SDK58 release. Seven CLOSED means M01 backend deployment plus M02-M07 the six accepted UI changes. Thus 7/17=41.2% overall, 7/15=46.7% CMS. These are final-acceptance counters, not batch counts, elapsed time or percentage of historical code.

Report815 closed a source/backup prerequisite, not another one of those functional milestones. B805 prepared rollback locally but was incomplete and did not deploy. Always show the acceptance fractions, AND name completed work in the current batch, source/CI state, deployment state, acceptance blockers and what closes the next milestone. Do not label the lone41.2% as total development progress. Track the new security gate and final cleanup issue95 explicitly without silently changing the17 denominator. Security progress must use its own item-level applicable-test denominator only after the inventory is completed; until then use named completed/pending work, not an invented percentage.
