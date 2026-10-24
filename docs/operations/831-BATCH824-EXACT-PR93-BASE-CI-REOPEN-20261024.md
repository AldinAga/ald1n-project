# B824 — Correct PR93 ancestry restores exact-source CI for B821 receipt regression

Date 2026-10-24. **Concrete root-cause resolved:** GitHub PR93 metadata identifies its actual head `feat/b799-public-retail-catalog-cart-checkout` at `fab3f4991fe5792e28e5a402dbf402617fb5c303`; prior base `feat/b799-complete-gated-public-storefront` was incorrect. A new **Draft [PR98](https://github.com/AldinAga/ald1n-project/pull/98)** bases B821 test-only branch `test/b821-receipt-privacy-negative-20261024`, head `df9c0b363b19b996730513853b531f5d811cf2f3`, on the true PR93 head: **1 commit / 1 file**. Old incorrectly based PR96 and PR97 stay CLOSED and unmerged; no safety-denied B815 fixture edits.

**CI triggered automatically and genuine runs observed** for exact B821 head (not hypothetical):
- [B803 full baseline/candidate](https://github.com/AldinAga/ald1n-project/actions/runs/40125729495): in progress at evidence collection.
- [B799 Chromium](https://github.com/AldinAga/ald1n-project/actions/runs/40125729487): in progress at evidence collection.
- [B799 MariaDB QA](https://github.com/AldinAga/ald1n-project/actions/runs/40125729578): FAIL, 37 tests/221 assertions, 3 errors/14 failures (existing guest-positive patterns including 302 vs 200).
- [B802 role isolation](https://github.com/AldinAga/ald1n-project/actions/runs/40125729567): FAIL, 20 tests/122 assertions, 3 errors/4 failures.
- [B806 targeted security](https://github.com/AldinAga/ald1n-project/actions/runs/40125729539): FAIL, 24 tests/88 assertions, 1 error/23 failures.
- [B798 first payment/warranty](https://github.com/AldinAga/ald1n-project/actions/runs/40125729550), [B797 PDF](https://github.com/AldinAga/ald1n-project/actions/runs/40125729499), [B796 operator](https://github.com/AldinAga/ald1n-project/actions/runs/40125729506), [warranty expiry](https://github.com/AldinAga/ald1n-project/actions/runs/40125729734), [B794 UI](https://github.com/AldinAga/ald1n-project/actions/runs/40125729489): PASS.

**Limits:** 10 observed workflows at this snapshot (5 PASS, 3 FAIL, 2 running); this is NOT final full CI or proof new B821 test cases ran. Additional GitHub workflow attempts on PR98 were also observed. Obtain exact-final B803 comparison and specific B821 test execution evidence before new test PASS. The 11 B820 media tests remain NOT_RUN. All known 49 historical failure identities, including B815 previously safety-DENIED fixture, are preserved; no skips, bypass, merges, deploys, public ON, production scans, SSH, EAS or OTA.

Production B798/public storefront OFF. Implementation PR93 Draft, isolated PR98 Draft test-only. Full release HOLD pending full WSTG/ASVS item-level catalog, accepted end-to-end and merchant/legal, SMTP, restore/FPM/OPcache gates. Accepted functional ledger 7/17=41.2% program, 7/15=46.7% CMS, 6/6 initial UI.
