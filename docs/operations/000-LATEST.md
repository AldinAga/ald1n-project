# CURRENT B804 - source QA recovered; exact-source backup prerequisite SSH next

Updated: 2026-10-24. Read root [AGENTS.md](../../AGENTS.md), the [operations supplement](AGENTS.md), this index, current reports and the canonical Google Drive runbook before proceeding.

## Current authority

- Application [PR93](https://github.com/AldinAga/ald1n-project/pull/93) remains **DRAFT, NOT MERGED, NOT DEPLOYED**. Exact source: `d4e9e2ba506e82d2f4b5ec639c6ce6b39748f34d`, tree `67e51b9112c93414277c5ca8eb5a8e57045318bc`, existing branch `feat/b799-public-retail-catalog-cart-checkout`.
- [Report814](814-BATCH804-QA-RECOVERY-RELEASE-GATES-20261024.md) records **13/13 successful exact-head CI workflows**, scoped payment/access **20 tests / 211 assertions**, public Chromium with two independently witnessed concurrent checkout races, and complete baseline comparison. Full suite: 451 baseline cases/44 problems versus 459 candidate cases/42 inherited problems, **zero new failure identities**. This is not a wholly green legacy suite.
- B804 corrects the concurrency test witness, not financial runtime behavior: independent nonces, distinct SQL connection IDs in two consecutive snapshots, unchanged302+422 last-unit and302+302 identical-receipt outcomes, independent DB stock assertions. Different SQL wait stages must not be confused with absence of concurrency.
- Buyer payment choices are exactly **Pouzece, Uplata na racun, Placanje pri licnom preuzimanju/dostavi**. No public deferred credit; internal SubAgent/direct-sale credit unchanged. In-person selection stays unpaid until genuine operator-recorded collection and requires pickup/own delivery.

## Actual host state - do not replay old work

- Last accepted production deployment is **B798**, owner Report804 / [Report805](805-BATCH798-PRODUCTION-CERTIFICATION-20261023.md), runtime source `c4a5f060cc00b5d1be0e4d8068309524a4b99c99`. Host HEAD `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`, 84 dirty tracked paths, protected index and .htaccess. Public storefront source/tables not deployed, flags OFF as last attested by Report810.
- [Report813](813-B803-SOURCE-PUBLISH-RELEASE-PREPARATION-20261024-095217.md) is **HOLD_PR_HEAD_MOVED, RC2**, not PASS. Its stale source patch must never be rerun. It did not finish backup gates or deploy anything. Working GitHub/Drive write actions were separately verified; do not require host SSH merely to publish source.
- Do not replay B798, B800 or earlier accepted batches, merge competing PR92, or confuse connector test PR94 with application changes.

## Next genuine operator step

Run the prepared **B804 exact-source and two-backup prerequisites only**. Source artifacts are pinned at commit `1240fcd3ad9efe4ef341aa197d4b63635b27b02f` under tools/operator-source:

- `ald1n-b804-release-readiness-v1.sh`, SHA256 `be560e0490bd6336d91084e2c3c5c77e9de9781b8c79d3c1875c35c3a8a16597`.
- PHP helper SHA256 `0b7ae942705806b1610f46201265bc71e5d324764e9f145e6dbc892bdcb03cb2`; manifest SHA256 `2d25f76fcf59235218458293aedc127dbadf8212e21fb45a5d2fdeaf95726108`.

One self-checking SSH bootstrap installs only the three canonical incoming/ artifacts. It checks exact current PR head/CI, original Report813, 44 affected runtime paths and two distinct completed full backups through the existing unchanged first-party read-only verifier. Freshness preparation window is explicitly newest<=24h, second<=48h; restore drill is not performed. It preserves host HEAD/index/.htaccess/.env, creates only its numbered docs/operations report, and performs **no application, DB, configuration, backup-create/prune, migration, maintenance, checkout/pull/reset, dependency install or deployment**. Expected report815 only if fresh max(remote,local)+1 confirms that number.

After the returned evidence, resolve actual remaining gates and prepare a separate controlled OFF rollout. A read-only prerequisite PASS does not authorize public opening. True merchant policies, shipping/financial settings, backup/restore, FPM/OPcache and actual customer acceptance remain separate gates. No EAS/native/OTA/Google Play action.

## Mandatory progress and final cleanup - owner rule

**Always show progress in every final Ald1n status:** program **7/17 = 41.2%**, CMS **7/15 = 46.7%**, original UI **6/6 = 100%**. Reverify before changing credit. These are accepted milestones, not code volume, estimated time or scoped test pass rate. Source QA alone adds no acceptance credit.

**Mandatory large inode cleanup after all current changes and acceptance:** persistent [issue95](https://github.com/AldinAga/ald1n-project/issues/95). Owner estimate >400,000 files/inodes is not measured. Inventory first, approved path allowlist/dry-run, verified Drive archives, protect active runtime/dependencies/uploads/secrets and two stable backups, one safe SSH batch with before/after inode/byte/reduction evidence. No deletion now; do not silently add cleanup to the17-milestone denominator.

The canonical Drive runbook (original ID `10Lxe_reZ1csYOMK9ESjZ8NUmEglDpOMy`) now contains these new rules with all prior content preserved. Old dated B797/B800 checkpoints are historical, not current instructions to replay work.

## Preserved history

The previous full index is retained byte-for-byte in [archive/000-LATEST-PRE-B804-20261024.md](archive/000-LATEST-PRE-B804-20261024.md), original blob `e2b3218116629fbaae79db2b22eb3eb1f8cc923d`. No historical report or guardrail was deleted. Earlier checkpoints remain evidence of their dates; newest verified state above supersedes stale PENDING headers.
