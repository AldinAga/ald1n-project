# B822 — CI execution route investigation / PR ancestry gate

2026-10-24. Fresh main `7143d55ff0906f2dc1dd9867b87c8d9710dc851f`, AGENTS.md, 000-LATEST.md, canonical Drive runbook verified. B821 isolated test branch `test/b821-receipt-privacy-negative-20261024` at `df9c0b363b19b996730513853b531f5d811cf2f3` contains two synthetic negative cases; NOT RUN.

GitHub workflow lookup for exact commit had returned zero runs. No available GitHub connector action supports manually dispatching a workflow; do not manufacture a CI result.

Compared test branch through a *new*, draft PR97 with base `feat/b799-complete-gated-public-storefront` (base SHA `5b5bf092c47baf203742808da5c8844d0d9313e9`). Comparison returned **133 commits / 95 changed files / 8304 additions / 18 deletions**, not a test-only change. PR97 was immediately **CLOSED without merge**. Original PR96 remains CLOSED. Root finding: old named storefront base branch is not a trustworthy exact-source comparison base for the B818 test branch; matching branch names is insufficient. Never reopen these PRs to force CI or merge them. Preserve exact B818 source and attempt future test integration only with verified full SHA/common ancestor and authorized exact-source workflow. Existing PR93 remains canonical draft.

No application code, B815 safety-denied fixture, production, SSH or deploy changed. CI stays 9/14 with previously catalogued 49 failure identities. B820 planned 11 and B821 2 cases remain NOT_RUN. WSTG/ASVS item-level catalog remains pending. Accepted 7/17=41.2% program, 7/15=46.7% CMS, 6/6 UI. Production B798 storefront OFF.
