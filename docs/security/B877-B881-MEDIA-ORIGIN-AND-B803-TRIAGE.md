# B877–B881 — public media cache revocation, relative cart redirects and B803 evidence triage

Date: 2026-10-25 (Europe/Belgrade). New **child Draft PR** based exactly on PR102 `5315d72a7b918f7a9f0104a820656d0a6920fe0d`. Three and only three CMS paths are intentionally changed: `PublicShopController.php`, `SecurityHeaders.php` and new `PublicMediaCacheAndCartRedirectB877Test.php`. Historical B805/B809/B815 provider-denied tests and the B871 Composer immutable evidence remain untouched.

## B877 — a real HTTP intermediary bypass gap when storefront is switched OFF

The existing route `public-shop.media` points to `PublicShopController::media()`. Before this change, it served validated synthetic JPEG/PNG/WebP product files with `Cache-Control: public, max-age=3600` when the storefront was ON. Existing B847 tested a fresh origin GET after switching OFF and correctly received 404. However, **that origin-only assertion cannot prevent a shared/intermediary cache from serving a previously cached 200 image for up to one hour after emergency OFF**. This is a source-identified release-gate gap, not proof of an observed production CDN leak. The real CloudLinux/CDN behavior has NOT been independently verified.

The source correction marks dynamic images `Cache-Control: private, no-store, max-age=0`, `Pragma: no-cache`, `X-Robots-Tag: noindex, nofollow, noarchive` and preserves `X-Content-Type-Options: nosniff`. The **global** `SecurityHeaders` route-name policy also applies these private no-store headers to `public-shop.media` before both HTTP 200 and route-resolved 404/error responses. This avoids accidentally caching emergency-OFF 404 and protects if the controller's response construction changes. Existing strict disk/path/MIME/symlink/visibility controls remain unchanged.

Important limitation: adding `no-store` prevents conforming intermediaries from caching **future** successful responses; it does not retroactively purge assets already cached while `public,max-age=3600` was active. Before any production public-ON/rollback, the separately authorized edge/CDN cache invalidation/revalidation gate remains mandatory. The performance cost of no-store media is documented and must be assessed against the current strict emergency OFF requirement; do not quietly relax it.

## B878 — internal cart POST/PATCH/DELETE redirects no longer derive absolute Location from untrusted authority

Three `PublicShopController` methods previously used `redirect()->route('public-shop.cart')`, which by default can generate absolute URLs using request routing context. The registered checkout `PublicCheckoutController::store()` already deliberately returns a relative `Location` to avoid Host/Forwarded header influence. Extend the same invariant to public cart `add`, `update`, and `remove` using `route(...,false)` in a plain `RedirectResponse`. For add, explicitly attach the current session before its standard status flash message. Stock, cart quantity limits, CSRF middleware, role checks, forms and route names are unchanged.

The new bounded real Laravel HTTP suite verifies relative `/korpa` responses even when Host/X-Forwarded-Host/X-Forwarded-Proto/Origin headers are malicious synthetic values, checks existing flash and cart quantities, and asserts OFF rejects writes. No production request is made. These are *local non-bypass security regressions*, not a proof that every upstream proxy honors trusted host configuration correctly.

## B879 — source and controlled MariaDB regression

A separate fail-closed [B877 isolated QA workflow](../.github/workflows/b877-public-media-cart-qa.yml) pins exact child HEAD, PHP 8.4, MariaDB 10.11.19, Composer lock and an allowlist of exactly three CMS changes. It executes 9 new real HTTP tests plus **six unchanged** security suites: B847 15, B866 11, B872 13, B876 3, B843 11 and B836 15, totaling **77 PHPUnit identities**. Its JUnit parser must find every identity, no duplicate/skipped/error/warning, with strict source hash and synthetic-only readback. Archive JUnit, complete sources and JSON to the existing Drive evidence folder. Any failure means HOLD and must be corrected before claiming scoped PASS. No re-execution of failed historical guest-only fixtures or independent CSRF probe.

## B880 — all 56 current B803 new failures are explicitly classified, not waived

Raw, immutable JUnit from [PR102 exact-source complete B803 #40209090923](https://github.com/AldinAga/ald1n-project/actions/runs/40209090923), [original ZIP on Google Drive](https://drive.google.com/file/d/1Ql9HsCx7BW4gUtgByPCyEsXxcv7lMpsu/view?usp=drivesdk), was read directly. The candidate has **601 unique cases / 98 failures+errors**, production baseline **451 unique cases / 44 failures+errors**; candidate introduces exactly **56 new problem identities**, unchanged from PR100 source. Those 56 new identities break down by actual first error/response:

| Observed first failure | Exact count | Status and interpretation |
| --- | ---: | --- |
| Expected HTTP 200, got 302 | 40 | Older checkout/security flows now redirect. A redirect destination or root cause is NOT proven identical for all 40; inspect each before claiming a regression or waiver |
| Expected HTTP 200, got 403 | 9 | Authorization/buyer activation/tracked session may be involved; individual prerequisites require source inspection |
| Expected HTTP 200, got 404 | 3 | B818 private receipt case on registered account without a tracked session; current access service requires matching active buyer with registered portal session |
| `Illuminate\Validation\ValidationException` | 3 | B799 capability fixtures do not satisfy current registered-buyer issuance conditions; no accountless capability should be silently re-enabled |
| Undefined legacy method `Error` | 1 | B815 suite uses a method no longer provided by state service; previous repair attempt was provider safety-denied and MUST NOT be retried, bypassed, or replaced through another fixture |

This breakdown is **a precise symptom census** (40+9+3+3+1=56), not a 56-item root-cause signoff. `PublicRetailCheckoutB799Test::test_anonymous_checkout_creates_one_real_operational_order_with_no_commission` asserts a successful accountless sale; the current `PublicCheckoutController::requireRetailBuyer()` explicitly forbids it, following the newer owner-accepted **registered Kupac only** policy. Changing code to make that old test green would break access control and is prohibited. Similarly, `PublicOrderAccessService::issue()` refuses null-user and unverifiable buyers; `order()` requires authenticated buyer and a valid tracked portal session. The B818 test directly fabricates an order/capability then calls a private route without a valid logged-in tracked session. This is not authority to weaken private receipt security.

Do not modify/skip/exclude, reinterpret or fake PASS for the 56. They remain **open release HOLD**, pending authorized historical test contract reconciliation and independent full-suite re-execution, while existing newer B843, B847, B866, B872, B876 and registration/Chromium controls demonstrate bounded parts of the intended newer behavior. The original baseline has 44 separate inherited failures, and no "zero new regressions" result is synonymous with a fully green suite. Historical issue #95 cleanup stays deferred until all gates complete.

## B881 — exact release boundary and next approved work

- **All 368** OWASP WSTG4.2/ASVS5 L1+L2 item IDs still UNASSESSED/NOT_RUN for *independent full controls*. These two additional source improvements provide **partial** SEC02 (redirect origin) and SEC22 (cache) proof only. A scope-specific test can never mark an entire OWASP requirement independently PASS.
- Main runs B798 production storefront OFF. PR93, PR99, PR100 and PR102 remain in their recorded state; child PR must remain Draft, unmerged. No production host, SSH, protected fixture edits, merchant credentials/orders/payments, actual customer email, EAS build, OTA or Play release.
- Android M16 16 KB compatibility technical CI was accepted **technically** but not formal M16 Play release; signed EAS AAB with remotely confirmed versionCode > rejected 27, independent physical UAT and Google Play acceptance still HOLD. SDK58/M17 deferred.
- Formal project acceptance **7/17 = 41.2%**, CMS **7/15 = 46.7%**, initial UI **6/6 = 100%**. Fixing cache/redirect origin is development progress and adds *zero* owner-accepted production milestones. Do not invent a higher completion percentage.

CI_SCOPED_77_TARGET_EXPECTED=NOT_YET_RUN
B803_56_FAILURES=FULLY_ACCOUNTED_NOT_WAIVED
SEC02_SEC22=PARTIAL_ONLY
PUBLIC_STOREFRONT=OFF_NO_DEPLOY
OWNER_SSH_REQUIRED=NO
