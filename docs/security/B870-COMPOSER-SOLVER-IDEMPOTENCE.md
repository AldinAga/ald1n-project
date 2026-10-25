# B870 — B858 Composer solver idempotence and exact-head CI provenance

Date: 2026-10-25. Source-only, Draft PR100 only. No production deploy or GitHub merge.

## Recurring automation defect and why it matters

The original B858 job unconditionally executed `composer update laravel/framework guzzlehttp/guzzle:^7.15.2 league/commonmark league/flysystem symfony/http-foundation --with-all-dependencies` on **every** PR100 push. Therefore a documentation or unrelated security-header source commit could unexpectedly change the lockfile to newer, still clean package patches even though `composer audit --locked` had **zero advisories**. This happened after the 52/52 privacy/CSP HTTP CI on original source `aaef34701e550875694481532b966c51bee4eb53`: the GitHub Actions bot advanced the same Draft PR to `fbd98208bc6e8f15541a8fcd7b1255a584589a5f`, changing **only `apps/cms/current/composer.lock`** (10-line diff). The runner's original 52/515 proof was pinned to the prior head, and a new immutable source ID needs fresh exact-head verification.

The bot-triggered CI events at new head reported `action_required`, not tested FAIL or PASS. Do **not** convert that state to a test outcome or bypass any GitHub owner approval. No EAS or hosting interaction resulted.

## Idempotent security-only remediation policy

The updated B858 workflow first executes an **actual read-only `composer audit --locked --format=json`** on the committed lock, parsing both Composer JSON/exit status through the previously fail-closed B857 classifier. It records the preliminary audit decision and hash evidence:

- If result is `PASS_LOCKED_COMPOSER_AUDIT` (0 advisories and 0 abandoned packages), run **`composer install --no-scripts --no-plugins`** without solving newer package versions, enforce no lock or composer.json diff, and re-audit. No bot commit is created.
- If real advisories or abandoned packages are reported and the JSON is valid, run the existing bounded security update solver and only commit a validated `composer.lock` if every strict source and real MariaDB suite gate passes.
- Malformed/unavailable Composer JSON, unexplained nonzero return code, invalid lockfile, dependency solver error, unexpected file mutation or CI failure is an explicit HOLD, never interpreted as a clean advisory count.
- Any change to the lockfile invalidates earlier exact-head test provenance and requires rerunning scoped and full-suite source delta review; this workflow cannot grant production release acceptance by itself.

The existing B858 focused checkout/receipt/media suites remain intact (41 tests). The B866 new 52/515 HTTP privacy/CSP proof remains genuine on original commit `aaef34701e550875694481532b966c51bee4eb53`; **the newer lock head is not claimed to have passed those tests until another exact-head CI completes**.

CSP `base-uri 'none'` amendment and issue #101 for the remaining JavaScript CSP policy remain separate, staged source-only controls; full OWASP V3.4.3 is NOT PASS. Original 56 B803 failures and 368 OWASP item reviews remain release HOLD.

Acceptance ledger unchanged: overall **7/17=41.2%**, CMS **7/15=46.7%**, initial UI **6/6=100%**; no user SSH, CMS production, EAS, OTA or Play action.
