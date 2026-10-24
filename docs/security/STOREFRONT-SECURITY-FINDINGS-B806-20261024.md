# Storefront security findings - B806 targeted phase 1

Date: 2026-10-24. This is a targeted source-security assessment with regression proof, NOT a completed comprehensive penetration test or production exploit report.

Baseline: d4e9e2ba506e82d2f4b5ec639c6ce6b39748f34d.
Corrected source: 08d0f44366d7c83c94e83196a0a9965152e84363, existing PR93, NOT merged/deployed.
Target: synthetic Laravel13/PHP8.4.27/MariaDB10.11.19 in isolated GitHub CI. No actual customer or production request/data was used.

## B806-F01 - request-origin poisoning of generated links

Status: FIXED AND RETESTED IN SOURCE. Provisional severity: medium, conditional on deployment accepting attacker-supplied authority headers. Reverse-proxy host enforcement and live exploitability were NOT tested; no production compromise is claimed.

The public checkout previously generated absolute buyer receipt and operator notification URLs using the active request Host. A synthetic request with Host injected.example.test created an email receipt URL and operator notification pointing to that untrusted origin. The checkout redirect also included the same origin and private receipt capability. The buyer email issue was reproduced in the first RED suite; the operator notification issue was separately reproduced after the first partial patch, using its real synthetic database notification.

Correction: absolute outbound links use trusted configured APP_URL plus internally generated relative route paths. The response Location is an internally generated relative path, created directly as RedirectResponse so Redirector cannot re-expand it from Host. The session flash message and committed order/idempotency behavior are preserved. Deployment must retain a valid trusted APP_URL; this patch does not replace host/proxy configuration review.

Evidence:
- RED270c2c366ad2161fa33870df9ae8f05b3cf59128: workflow40090762492, artifact12558867772, sixteen tests /191assertions /4expected failures (including cache checks below), zero errors.
- Second RED9e84dfcc26eea86b0090f57611a8e567a9d3446f: workflow40091134621, artifact12559415878, seventeen tests /203assertions /1expected failure. Expected canonical operator URL; observed injected origin.
- GREEN08d0f44366d7c83c94e83196a0a9965152e84363: workflow40091416291, artifact12559053189, seventeen tests /205assertions /0failures/errors. Host, forwarded-host and forwarded-protocol input cases, relative receipt redirect and real operator notification assertions pass.

## B806-F02 - cart and checkout missing explicit no-store

Status: FIXED AND RETESTED IN SOURCE. Provisional severity: low privacy-hardening gap. Prior private/no-cache headers already restricted shared caching; this is NOT proof of an observed shared-cache customer-data leak.

The cart and checkout contain session-specific content, CSRF/purchase nonces and potentially redisplayed buyer data. Their responses lacked an explicit no-store directive. Two synthetic GET regressions reproduced that no-store was absent.

Correction: SecurityHeaders adds private,no-store,max-age=0, Pragma:no-cache and X-Robots-Tag:noindex,nofollow,noarchive to the public cart and checkout route families. Existing private receipt/documents retain their stronger no-referrer policy. Canonical existing global headers are preserved.

Both GET regressions pass on the corrected exact commit. No production cache or configuration was mutated.

## What the17 passing tests actually cover

Three buyer email origin cases, one redirect-origin case, two session-page no-store cases, seven invalid/tampered checkout inputs, one price/privilege/paid-state mass-assignment case, one product/buyer HTML encoding case, one hashed/expired/revoked/nonnumeric capability case, and one operator notification-origin case. Test counts include data-provider variants.

The mail transport is captured/faked; controllers, sessions, database orders, stock and database notifications use actual application paths. ThrottleRequests is intentionally disabled for these integration fixtures. Laravel test-kernel requests do not establish real-browser CSRF behavior. Real HTTP CSRF/rate tests, full authorization/IDOR matrix, complete input corpus, SAST/SCA/secrets checks, authenticated/anonymous DAST/manual review, infrastructure checks and the item-level WSTG/ASVS applicability register remain outstanding under the dedicated security plan.

Only two runtime files changed in B806: PublicCheckoutController.php and SecurityHeaders.php. No prices, payment posting, role migration, dependencies, production source, database, ON/OFF state, native build or cleanup was changed. Review is author self-review plus automated CI, not an independent external penetration assessment.

The full CMS baseline/candidate comparison and all existing CI workflows are a separate exact-source gate; consult the current operations report for their final result. Inherited legacy failures must be listed and cannot be represented as a globally green suite.

## Remediation and release boundaries

These two confirmed findings are patched and retested, but the comprehensive penetration-testing task is still OPEN. Every further confirmed finding requires a failing synthetic regression, a scoped patch and a same-commit retest. NOT_RUN, BLOCKED and NOT_APPLICABLE must not be counted as PASS. All applicable catalog items must be accounted for before claiming completion.

Original owner Report815 already completed B804 prerequisites; do not rerun it. The previous B805 local payload targeted the older source and is stale after these corrections. Regenerate/retest a later controlled OFF-rollout package for the final approved source. Public ON still requires the complete security, merchant, backup/restoration, FPM/OPcache and acceptance gates. No SSH action is required merely to publish this source work.

Accepted milestone ledger remains7/17=41.2% overall and7/15=46.7% CMS. This report advances security work, not final production acceptance.
