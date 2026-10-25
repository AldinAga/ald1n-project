# Report859 — B899–B901 Storefront relative HTML origins, filtered pagination and full B803 lineage

**Date:** 2026-10-25 Europe/Belgrade. **No CloudLinux/production mutation; Storefront remains OFF.**

## Fresh provenance, source and scope

- Reconstructed from GitHub `main` at [Report858](858-BATCH896-897-REGISTRATION-CSP-20261025.md) `b6b22623950b247322a94da97762107e347e270d`, canonical `AGENTS.md`, `docs/operations/000-LATEST.md` (stale B832; previous direct edit was safety denied, **not retried**), SEC02/16/22 security plan, open release issues [#104](https://github.com/AldinAga/ald1n-project/issues/104) and [#101](https://github.com/AldinAga/ald1n-project/issues/101), and the connected Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md` (B806 policy). No completed batch or Android native build was rerun.
- Scoped [Draft PR109](https://github.com/AldinAga/ald1n-project/pull/109), branch `fix/b899-storefront-html-link-origin-20261025`, immutable stacked base **`f03fc4ddc04cf20abff3596f38f0195dbf8f80dc`** (PR108 source), exact candidate **HEAD `ccc4ca043a0f164aa6ee993af50ab18ee64daf4a`**. No merge.
- Exactly **10 paths** relative to that base: `.github/workflows/b899-storefront-html-origin-qa.yml`, `apps/cms/current/app/Services/PublicRetailCatalogService.php`, `apps/cms/current/tests/Feature/StorefrontHtmlOriginBoundaryB899Test.php`, and seven Blade views `resources/views/public-shop/{layout,catalog,show,cart,checkout,order,register}.blade.php`. The protected historical B805/B809/B815 fixtures, Composer lock, live authentication/merchant code, binary Android source and production configs were untouched.

## TDD — observed failures and bounded remediation

1. Added independent synthetic MariaDB 10.11.19/PHP8.4 real HTTP tests with merchant fixture, approved synthetic registration sender, synthetic images and attacker-like Host/Forwarded/Origin/Referer. [Original RED #40223159294](https://github.com/AldinAga/ald1n-project/actions/runs/40223159294) demonstrated **5 failing assertions**: layout CSS, navigation/form/image DTO links emitted **absolute configured `https://cms.ald1n.com` URLs** while the explicit first-party relative URL contract required root-relative paths. **No untrusted Host takeover was observed.** This is origin-dependency hardening, not a confirmed exploitable Host injection. [Verified original RED artifact Drive ZIP](https://drive.google.com/file/d/1sr7kwSvMd2B4wLzhATq7qSZeKQ19q4VH/view?usp=drivesdk).
2. Changed only public Blade same-origin links/forms plus stylesheet to root-relative paths; public catalog image DTO uses `route('public-shop.media', ..., false)`. **External merchant-approved terms/privacy/courier URLs and canonical buyer mail receipt URLs remain intentionally independently configured/validated**, not rewritten. [First GREEN #40223321945](https://github.com/AldinAga/ald1n-project/actions/runs/40223321945) **5/5 PHP HTTP tests, 54 assertions PASS**. [Original Drive GREEN evidence](https://drive.google.com/file/d/1wr-pvrPu5KZyjbE2WLmVOboqL8jC5W21/view?usp=drivesdk).
3. Independently tested catalog pagination with 22 disposable synthetic products and `q=B899` filter. [Real B901 RED #40223475188](https://github.com/AldinAga/ald1n-project/actions/runs/40223475188) gave **5 previously green, 1 new failure**: default Laravel paginator linked to `https://cms.ald1n.com/katalog?q=B899&page=2` rather than `/katalog?q=B899&page=2`. [Original paginated RED ZIP on Drive](https://drive.google.com/file/d/1AUBH_eDuCE1oE_ItFfYVMJD78YWqHC4P/view?usp=drivesdk).
4. Applied `->paginate(20)->withPath(route('public-shop.catalog', [], false))->withQueryString()`. This preserves filter and page state and leaves `sort`, stock and price semantics unchanged. On an intermediate run, all six PHPUnit cases were green but the separate exact-case verifier still asserted five **unique** identities; inspected the genuine JUnit, corrected only `len(set(ids)) == 6`, never disabled/waived any case. [Final exact-head scoped #40223700200](https://github.com/AldinAga/ald1n-project/actions/runs/40223700200) **SUCCESS: 6/6 unique PHP HTTP cases, 65 assertions**, no fail/error/skipped, exact HEAD, immutable parent and path allowlist. [Verified 8,872-byte original JUnit/source/source-SHA/CI ZIP on Drive](https://drive.google.com/file/d/1fujDmUGDC3qSkodn8-oLnA4vK6skLpbT/view?usp=drivesdk).
5. Repeated stale JUnit unique-count CI blocker from B896 and B901 was consolidated into canonical [`AGENTS.md`](../../AGENTS.md), `main` commit **`3e952c6c2e2daf4800eeece0ef4c58bc7c723247`**, with test identity gate safeguards and a precise public relative-route source contract. This separate main docs-only change does not imply PR109 merge or release.

## Mandatory unmodified original full-suite regression gate

- [Exact-head full B803 CI #40223700268](https://github.com/AldinAga/ald1n-project/actions/runs/40223700268) **FAIL / RELEASE HOLD**, real PHP8.4/MariaDB10.11.19: immutable historical baseline **451 cases / 44 failures+errors** (38 failure, 6 error, four warnings), candidate **654 cases / 98 failures+errors** (89 failure, 9 error). Comparator **`NEW_FAILURES=56 WHOLE_SUITE_GREEN=NO`**.
- Independently compared all **56 case identity strings** with prior [B896 full #40221900467](https://github.com/AldinAga/ald1n-project/actions/runs/40221900467): **56 same, 0 added, 0 removed**. Original 56 B803 release identity conflicts and provider-denied protected fixture edit are **not resolved or waived**; unmodified JUnit and exact lineage are preserved. [Verified 368,788-byte full-suite JUnit/log/manifest ZIP on Google Drive](https://drive.google.com/file/d/1EkU_ITQIhqhdGiCus-AhLJG18H3lt2HQ/view?usp=drivesdk).
- Security acceptance is NOT equivalent to 6/6 green. Full OWASP WSTG 4.2/ASVS5 L2 368-item inventory still **0/368 independently accepted**; buyer registration browser UAT SEC23 remains safety-gated; exact CloudLinux FPM/SMTP/merchant/legal/backup conditions HOLD. Existing B798 runtime Storefront remains OFF.
- Android M16 earlier source PR82 at `6d11b766131ca3cfa946a984b1cc9ed834c00239`: real native/debug AAB and 16KiB emulator [CI #40192813557](https://github.com/AldinAga/ald1n-project/actions/runs/40192813557) **technical PASS**. Actual signed EAS AAB with remote `versionCode > rejected 27`, signer identity, physical 16 KiB device and Play acceptance remain HOLD. SDK58/M17 deferred. No EAS/OTA/Play production build/submit, no SSH, no production install or data changes, no destructive cleanup.

## Accepted progress and follow-up

Owner program ledger **7/17 = 41.2% overall**, **7/15 = 46.7% CMS**, **6/6 = 100% initial UI**, unchanged because this is **Draft/source/CI** not production acceptance. Next: formal reconciliation of historical 56 B803 test identities without weakening verified activated Kupac, owned receipts, tracked sessions or merchant gate, and complete security/browser/host/merchant approval before Storefront ON; independently M16 signed AAB/Play gate when authorized. No operator action currently needed.

B899_B901_HEAD=ccc4ca043a0f164aa6ee993af50ab18ee64daf4a
B899_RED=5_EXPECTED_CANONICAL_ABSOLUTE_LINKS
B901_RED=ONE_ABSOLUTE_PAGINATION_LINK
B899_B901_GREEN=6_OF_6_REAL_HTTP_65_ASSERTIONS
B803_BASELINE=451_CASES_44_FAILURES_ERRORS
B803_CANDIDATE=654_CASES_98_FAILURES_ERRORS
B803_FAILURE_IDENTITIES=56_EXACT_UNCHANGED
ADDITIONAL_REGRESSION_VS_REPORT858=ZERO
SECURITY_ACCEPTANCE=0_OF_368
ANDROID16KB_DEBUG_EMULATOR=PASS
ANDROID16KB_SIGNED_PLAY=HOLD
STOREFRONT_PRODUCTION=OFF
ACCEPTED_PROGRAM_MILESTONES=7_OF_17_41_2_PERCENT
PRODUCTION_MUTATION=NONE
OWNER_SSH_REQUIRED=NO
