# Report857 — B893–B895 public storefront media root confinement and exact CI evidence

Date 2026-10-25. **Source-only; production NOT changed.** Highest previously verified `main` operations report: 856. Canonical `AGENTS.md`, stale `000-LATEST.md` (past direct edit safety-denied: NOT retried), [Report856](856-BATCH890-892-STOREFRONT-TRACKED-SESSION-20261025.md), [Issue104](https://github.com/AldinAga/ald1n-project/issues/104) and Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md` were reviewed before beginning. New docs here are mandatory durable evidence; no unneeded operator script, repeated host batch or EAS build was created.

## GitHub change, frozen parent and security reproduction

- [Draft PR107](https://github.com/AldinAga/ald1n-project/pull/107), branch `fix/b893-storefront-media-path-20261025`, stacked on PR106 parent **`c84ea75ace582ce8f8ac85920fc9f543693d1c3a`**. Exact final source HEAD **`1fa7dd8b4e3226c24aa1febe87be71a159906ae3`**.
- Precisely **three** paths changed relative to that parent: `.github/workflows/b893-storefront-media-path-qa.yml`, `apps/cms/current/app/Http/Controllers/PublicShopController.php`, `apps/cms/current/tests/Feature/StorefrontMediaPathBoundaryB893Test.php`. Existing B805/B809/B815 protected fixtures, Composer lock, app routing/auth and Android source unchanged.
- Independent TDD synthetic HTTP fixture uses `Storage::fake('public')`, temporary synthetic media roots, MariaDB 10.11.19, PHP 8.4, known roles/products/images and `symlink()`; no real customer/production media or live HTTP target. Following initial fixture setup corrections, [RED CI #40220077755](https://github.com/AldinAga/ald1n-project/actions/runs/40220077755) executed exactly **5 cases, 22 assertions and 3 purposeful FAILS**: (1) public `products/{id}` symlink to an external directory, (2) public product directory alias to **another product's** image folder within the disk root, and (3) legacy `uploads/products` symlink outside the configured legacy root. All three expected HTTP404, observed **HTTP200** in original code. Two regular public/legacy PNG cases already PASS. This is source/CI evidence of missing root confinement, NOT evidence of a production exploit.
- **B893 source remediation**: require canonical `$allowed` media directory to stay inside canonical `$root`; for the public disk, require that directory to equal **exactly** canonical `products/{currentProductId}` under the public root, prohibiting cross-product symlink aliases. Preserve the existing `$full` canonical descendant test and strict JPEG/PNG/WebP MIME gate. Regular legacy image roots remain supported when their resolved products directory stays under the configured root. Do not follow external symlink roots.
- [Final exact-head B893 CI #40220164764](https://github.com/AldinAga/ald1n-project/actions/runs/40220164764) **SUCCESS**, PHP8.4 + MariaDB10.11.19: **5/5 real HTTP cases, 27 assertions**, no failures, no skipped/duplicate identities, SHA256 source proof, exact parent/protected-file path boundary. [Verified 8,107-byte Drive ZIP with original source, SHA and JUnit](https://drive.google.com/file/d/1u42U-F_--hiiXRUB5MiZiPPLj4Rf_4Z7/view?usp=drivesdk).

## Full-suite release gate — genuine inherited RED, no new failures

- [Exact-head B803 full CMS CI #40220164801](https://github.com/AldinAga/ald1n-project/actions/runs/40220164801): original baseline **451 cases, 44 failures/errors** (38 failures, 6 errors, four warnings); full candidate **641 cases, 98 failures/errors** (89 failures, 9 errors). Comparator: **`NEW_FAILURES=56 WHOLE_SUITE_GREEN=NO`**. All 56 candidate-vs-baseline failure identities compared individually with [B892 full CI #40218859228](https://github.com/AldinAga/ald1n-project/actions/runs/40218859228): **56 exact same, 0 added, 0 removed**. No test excluded, disabled, silently renamed or waived.
- [Verified 367,864-byte Drive ZIP](https://drive.google.com/file/d/1pB2gTdbm9UI4HVjRjGlR6cwxXTuUEpG-/view?usp=drivesdk) preserves original 451- and 641-case JUnit, complete log, immutable runtime manifest and identity comparison.
- Global suite **FAIL** remains a genuine release HOLD; prior guest-success versus later verified Kupac policy conflict and provider safety-denied B815 fixture still require independent authorized resolution. New B893/SEC13 bounded tests do not prove the 368-item OWASP penetration catalog, physical symlink-race resistance, infrastructure paths or live production acceptance. ASVS/WSTG applicable review remains **0/368 independently accepted**.

## Parallel Android 16 KiB status and owner boundary

- Existing PR82 exact native compiler/debug-AAB/ELF/zipalign + Android15 offline 16 KiB emulator [CI #40192813557](https://github.com/AldinAga/ald1n-project/actions/runs/40192813557) at `6d11b766131ca3cfa946a984b1cc9ed834c00239` **PASS**; no redundant native rebuild. This is **not** signed production Play AAB or physical device acceptance. M16 HOLD: authorized clean source + backend warranty, EAS remote versionCode strictly greater than rejected 27, signer identity, newly signed artifact verification, actual 16KiB device UAT and Google Play acceptance. M17 SDK58 deferred.
- No SSH/script requested; no deployment, production data mutation, Storefront ON, EAS native build, OTA update, Play submission, PR merge or cleanup. Public Storefront remains **OFF** on B798 runtime.

## Accepted program progress

- **7/17 = 41.2% program**, **7/15 = 46.7% CMS**, **6/6 = 100% original UI**; these are formally accepted owner milestones and are not code coverage or percent of source implemented. This batch closes a **scoped source/CITest finding**, not an accepted production milestone.
- Next release gates: independent resolution of 56 unchanged B803 failure identities without relaxing Kupac/receipt security, complete OWASP/merchant/legal/SMTP/backup/FPM/staging/security approvals for Storefront ON, then signed Android M16 EAS artifact/device/Play. No operator action until a source-verified canonical `incoming/` host script is genuinely necessary.

PR107_SOURCE_SHA=1fa7dd8b4e3226c24aa1febe87be71a159906ae3
B893_RED=3_HTTP200_INSTEAD_OF_404
B893_GREEN=5_OF_5_PHPUNIT_PASS_27_ASSERTIONS
B803_BASELINE=451_CASES_44_ERRORS_FAILURES
B803_CANDIDATE=641_CASES_98_ERRORS_FAILURES
B803_NEW_FAILURE_IDENTITIES=56_EXACT_UNCHANGED
B803_ADDITIONAL_FAILURE_IDENTITIES_VS_B892=0
ANDROID_16KB_DEBUG_EMULATOR=PASS
ANDROID_16KB_SIGNED_RELEASE=HOLD
STOREFRONT_PRODUCTION=OFF
OWNER_SSH=NOT_NEEDED
ACCEPTED_PROGRESS=7_OF_17_41_2_PERCENT
PRODUCTION_MUTATION=NONE
