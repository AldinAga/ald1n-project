# B1049 — M13 StoreFront independent catalog-only route hardening, real CI handoff

Date: 2026-11-02 (Europe/Belgrade). GitHub repository: `AldinAga/ald1n-project`.

## 1. Factual source and prior result

- Original authenticated GitHub main: `8ae037aaa8e7b1bde80d2a03b29a526628646155`, last B1047 documentation and AGENTS guardrails, no active CMS storefront runtime.
- Exact B1047 base source-only patch SHA-256: `c05f0bf39babf62870ce8ea2607151f6015a526c9ef1977da796f266d06692fc`, original successful [B1047 browser and PHP CI](https://github.com/AldinAga/ald1n-project/actions/runs/41538553739).
- Exact B1048 independent catalog-only overlay SHA-256: `571c890aa964123f90da8734deec2e65599c639b4aa385fe12cb2304cad0bde3`. Its prior local 67/67 pure assertions were NOT proof of a full Laravel source integration; full B1048 CI was not previously executed.

## 2. Actual newly discovered security bypass — RED / GREEN locally proven

The B1048 `PublicCatalogReadOnlyGate::permits()` examined route names beginning `public-shop.` and three known shopping prefixes, but did not reject an **unnamed** `POST /katalog/checkout` or `GET /katalog/other` registered beside the three approved GET routes. Local real PHP 8.4 reproduction before the fix returned `B1048_UNNAMED_SHOP_WRITE_GATE=INCORRECTLY_ALLOWS`.

B1049 adds one strictly scoped source-only patch, SHA-256 `e4dfaec2bddd375e1a0ed283a5e5d989b1f52f06e908d6b47d80e51af83cbe1a`. The hardening rejects every unexpected handler under `/katalog` regardless of route name, plus known separate shopping prefixes. Local original B1048 gate fails the new test (RED) and B1049 patched gate passes **74/74 pure PHP assertions**, including all 67 original assertions and seven new unnamed-path negatives. No existing negative gate was suppressed.

## 3. Actual first GitHub Actions result — NOT overall PASS

[Run 41540790119](https://github.com/AldinAga/ald1n-project/actions/runs/41540790119) on head `5598fb0fe52c8c0f6b039c20a9ac44cfebba6c56` **COMPLETED FAILURE**.

- Exact immutable PR111 import, exact B1047/B1048/B1049 overlay SHA gates: **PASS**.
- Combined **13-path** source patch applied to a separate clean checkout: **PASS** (2 modified + 11 new, no other CMS mutations).
- Independent 74-case pure PHP gate, PHP syntax, MariaDB 10.11 source and three GET + exact throttle census: **PASS**.
- Original five adapted B1046 HTTP cases **5/5 PASS** (from original JUnit).
- Eight B1049 HTTP cases: **7 PASS, 1 ERROR**. Exact failing identity: `test_emergency_off_revokes_catalogue_and_media_without_cache_delay` with MariaDB `SQLSTATE[HY000] 1364 Field 'description' doesn't have a default value`. The fixture built a synthetic Product without the required description; no runtime production `Product` model or database bug is proven by this error. We do NOT claim 13/13 PASS or execute browsers on a failed PHP source gate.
- Original failing GitHub ZIP artifact `13186312279` downloaded, independently inspected and copied to owner Drive: https://drive.google.com/file/d/18Ln9e3sMxUldeepDOGvtADlLdAqexi1u/view. ZIP SHA256 `9212fcbc227859aa876654ebcb4c821c4caff5f6e2a575bb48768f2344eb1f4c`. Contains authentic 5-test and 8-test JUnit XML, combined 13-path source patch and route/source receipt. Browser step was skipped by genuine fail-closed pipeline.

## 4. Corrective source and next authorized test

- Updated only the synthetic test fixture to add nonsecret `'description' => 'Synthetic fixture only'` when inserting a disposable product. PHP lint PASS. Corrected test source SHA256 `0d8703adfd3614f52da779962d78e4c21940a7d141f8878b131a0a5724c95467`.
- The corrected source is committed and verified on `main`: `8effc53d35b748b3e28a23c49d427e570c0bfef2` (`tools/storefront/b1049/B1049CatalogIsolationIntegrationTest.php` only). Its exact v2 source is archived on Drive: https://drive.google.com/file/d/1XT0mQEPBvTphwyWQ5kzI_Kd35uBdudt7/view.
- The existing [B1049 GitHub Actions workflow](https://github.com/AldinAga/ald1n-project/actions/workflows/b1049-m13-independent-catalog-isolation.yml) includes `workflow_dispatch` and is configured to check out the latest selected branch at each **new** manual run. It does not run EAS, Play, email, CloudLinux or production DB actions.
- An attempted automatic re-trigger by updating this existing workflow file was **blocked by OpenAI safety checks**. DO NOT retry/bypass this denial by renaming, repackaging, changing another workflow or GitHub write endpoint. No new successful B1049 CI run exists in this checkpoint. A normal authorized GitHub Actions manual Run workflow on `main`, performed by repo owner, is the remaining non-build CI action. **Do not click `Re-run jobs` on the first old run**: that would reuse the original head `5598...` and miss the corrected test fixture. Prefer a NEW manual `workflow_dispatch` on up-to-date main if the owner elects to run it.
- One auxiliary script upload was safety-denied and not retried; required B1049 exact workflow, patches and executable tests were successfully archived on Drive and GitHub independently before this denial.

## 5. Immutable release constraints and formal milestones

No active GitHub `apps/cms/current` runtime source edits, no CloudLinux deployment, no production DB/stock/order, no public storefront activation or login/checkout, no external mail/push, no Android EAS native build, OTA or Google Play changes were made. The main source code is still not integrated with the M13 patch. B803 original 56 failing test identities OPEN_NOT_WAIVED, prior B815 and 000-LATEST safety denials preserved, security CSP/ASVS/WSTG mandatory gate remains. This source candidate does not satisfy staging, production PHP-FPM/OPcache or owner product acceptance.

Accepted milestones unchanged: **12/17 total (70.6%)**, **10/15 CMS (66.7%)**, **6/6 initial UI (100%)**. M13/M14/M15 remain OPEN.
