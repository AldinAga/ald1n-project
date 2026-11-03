# B1071 — isolated QA recovery and M14/M15 replay-snapshot integrity (SOURCE_ONLY)

**2026-11-03.** **SOURCE_CANDIDATE_UNAPPLIED**. Not a production release, not an owner-approved M14/M15 completion and not a CloudLinux script. The exact source patch and all tests are archived on Drive; **no application source has been changed in GitHub `main` or on the dirty CloudLinux host**.

## Exact source provenance

- Current `main` before this docs-only checkpoint: `adc6d51b3b6d859de70e317f3c5c7ca7b843ee6b`.
- Source baseline is **[Draft PR #93](https://github.com/AldinAga/ald1n-project/pull/93)** `feat/b799-public-retail-catalog-cart-checkout`, pinned exact head `acb8b9c42ec9254b1a27567abca29a26c1f733f8`. **PR #111 is M11 warranty, NOT M14/M15; previous references were stale**.
- Original `PublicCartService.php` Git blob `87237d51a372f200e511a7114ae80808d4ba6e2d`; original `PublicRetailCheckoutService.php` blob `fb29ad856a5989b8a460a69d702e33b5a28aad45`. Both verified against locally reconstructed exact bytes, not guessed.
- [Unapplied source patch](https://drive.google.com/file/d/1YgqafVA3HaIkx0bruizVHvGaBwX9aVt5/view) `B1071-PR93-M14-M15-AND-QA-INFRA-UNAPPLIED.patch`, SHA256 `510e661167d31e2b6654afbac4809133bda696be066df199a9a1a19b4e84628a`. Exactly **10 paths = 2 modified services, 8 added test/QA files**. `git apply --check` and exact output comparison passed on a *disposable, independent copy* of PR #93 baseline; never execute on production.
- [Complete source + QA ZIP](https://drive.google.com/file/d/1RZH9IK4Nyggh3bJsbeub_tNOvEpGC5wh/view), SHA256 `c78a02ae6936b76223ece05306a30650e1ac78a6d8f3e142efebbebc21f195f4`. ZIP manifest individually SHA-256 verified. [Detailed signed-off scope/evidence report](https://drive.google.com/file/d/1UU2pXZfOxx5tz4pPpcONTa3S4UwLPxqv/view), [implementation plan](https://drive.google.com/file/d/1YVjQfMPlnA3wvifZluVJzy8WGLPNrZOr/view), [local test transcript](https://drive.google.com/file/d/1iyMCIkuqJxDv0iFzQosfrO7QDhojHNM2/view).

## Concrete source work + QA fixes

1. M14 `PublicRetailCartReplaySnapshot::validated(mixed)`: enforce exactly typed positive integer product IDs and integer 1..20 quantity, 1..25 distinct items, sorted by key. Reject the **entire** invalid saved cart instead of silently sanitizing or coercing.
2. M14 `PublicCartService::replaySnapshot()`: completed checkout nonce must exactly match. Invalid cart raises Laravel validation before transaction; current live-cart logic untouched.
3. M15 `PublicRetailCheckoutService::place()`: removes untrusted `last_cart` passthrough and calls validated M14 method **before HMAC fingerprint and DB::transaction**. Existing registered buyer, merchant policy, idempotency/stock checks unchanged.
4. QA independent `ScopedQaGrantValidator` checks *literal escaped* underscores for the exact named test database; refuses wildcard/global/other-scope/role/grant-option rows. It does not connect to MariaDB, and actual host grants are NOT VERIFIED.
5. QA `SourceManifestValidator` fails closed on **zero-file** census, symlinks, unsafe paths, secrets, unreadable/missing paths and SHA drift; requires exact isolated candidate allowlist.

**Locally verified in PHP 8.4:** 17 pure replay cases, 21 QA grant/manifest cases, 4 M14 service cases, 6 source-contract cases and **4 real PHP checkout pre-transaction cases with synthetic dependencies**. The checkout test was RED on original PR #93 (invalid cart reaches simulated DB boundary) and GREEN on candidate (invalid cart rejected before transaction). Patched-repo runner re-exercised 21 QA, 15 replay, 4 checkout cases; 9/9 PHP lints and bash syntax PASS. These suites share cases; do not add them as independent release coverage. **NO real Laravel PHPUnit/MariaDB/browser/production FPM run**; new PHPUnit test added but unexecuted here.

**Safety:** B1069 is **REVOKED_DO_NOT_RUN**. Never repeat or bypass B805/B809/B815 denied source test modifications, never waive B803 56 original identities or SEC23/CSP/368 WSTG-ASVS reviews. No prod DB/schema/checkout/feature flags/Git reset/deploy/EAS/OTA/Play. Host production HEAD remains separately pinned `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`; do NOT claim it equals GitHub main.

## Milestones / HOLD

- Last real host B1068: **24 PASS / 2 HOLD / 11 INFO** (24/26=92.3% decisive audit only). Original two HOLDs: missing B1065 final QA cleanup receipt; CLI effective flags stdout/stderr ambiguous. Public OFF HTTP denies access but is not enabled-browser attestation.
- **Formal accepted work unchanged:** Program **11/17 =64.7%**, Laravel CMS **10/15 =66.7%**, first six UI **6/6=100%**. M14 and M15 remain **OPEN_NOT_OWNER_ACCEPTED**, regardless of this source-only improvement.
- **Release HOLD:** full original CI identity regressions, real isolated MariaDB transactional/inventory concurrency tests, Chromium/Firefox enabled UAT with decoded images, production FPM+OPcache exact source, buyer session/merchant/legal/SEC and owner launch approval.

**Next permitted step:** independent review of source ZIP and exact PR #93 diff, then execute all original M14/M15 and new PHPUnit tests in isolated PHP8.4/MariaDB10.11 CI with no fixture exclusions. Only then consider further feature implementation; no production activation. [Canonical Drive runbook](https://drive.google.com/file/d/10Lxe_reZ1csYOMK9ESjZ8NUmEglDpOMy/view).
