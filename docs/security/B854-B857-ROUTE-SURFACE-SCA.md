# B854–B857: executable security source map and dependency advisory gate

2026-10-25. **Staged test/source-only work on existing PR99, not a release.**

## Source authority

Fresh main `e85722aa57e8f46e27cc92c2f0ecea85be229a48` (Report847); existing Draft public-shop PR93 `acb8b9c42ec9254b1a27567abca29a26c1f733f8`; existing stacked Draft security PR99 parent `062462a5767881b915360f1dc202e2cc2c2c3a00`. Canonical AGENTS, current runbook and known denied B805/B809/B815 fixture edits remain binding.

## B854 — SEC01 public endpoint inventory from the real app router

With PHP 8.4 and locked Composer packages, `php artisan route:list --json` generates the actual route table in isolated GitHub CI. No HTTP requests, active attacks, controller changes or production connections. The offline inventory generator enumerates every registered route and records method, URI, controller, name and middleware. This gives an exact source-bound surface map for the later independent penetration audit.

## B855 — SEC04/SEC21 routing contract fail-closed

The generator binds **14 explicit public-shop route names** with allowed methods, URIs, controller actions and exact throttle middleware. Both registration endpoints also require the `guest` middleware. Missing/duplicated/extra public endpoints, new unreviewed method/path, controller action changes, missing web/throttle/guest boundaries, invalid source SHA and malformed Laravel JSON all fail closed. It deliberately does **not** infer that controller-side buyer authorization is proven from routing metadata alone, and it does not say that a configured throttle has been tested under load.

## B856 — adversarial offline unit tests

Run at least 13 tests for legitimate route census plus missing, changed, unthrottled, duplicated, unexpected and malformed route lists, all without contacting any application or rewriting protected historical tests.

## B857 — SEC25 official Composer lockfile advisory signal

A separate workflow job runs `composer audit --locked --format=json` against the exact existing Laravel `composer.lock` and captures the actual command return code, advisory JSON, JSON hash and lockfile SHA-256. A fail-closed parser refuses incomplete or bogus clean results; concrete advisories or abandoned dependencies are explicit HOLD requiring review and (if applicable) separately approved remedial changes. At least 12 adversarial parser tests run first. This is a bounded advisory check of locked PHP packages, **not** a full SCA/SAST/DAST program or a statement that vulnerabilities cannot exist.

## Interpretation and release gates

A passing route census proves only that those 14 routes still agree with the reviewed declarative source contract. A passing Composer audit proves only that the live advisory database returned no findings for current locked packages *at that query time*. Neither constitutes a successful OWASP WSTG4.2/ASVS5.0.0 test. All **368 item IDs remain UNASSESSED / NOT_RUN** until individual evidence review; formal `verified_security_pass=0`.

The prior approved synthetic checks B845/B846 and B847–B849 are retained without edit. The 56 B803 new failing identities and protected B805/B809/B815 failures are not altered/waived. No new Laravel business logic, no public storefront ON, no production mail, buyer/order data, SSH, EAS, OTA, Google Play, or autonomous merge. Main accepted progress remains 7/17=41.2%, CMS 7/15=46.7%, UI 6/6=100%. Android PR82 M16 native 16 KiB remains separately gated until a new EAS-signed AAB, physical UAT and Play acceptance; SDK58 still postponed.
