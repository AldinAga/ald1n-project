# Batch705: Independent Review and CloudLinux Release Gates

Status: DRAFT / not a production deployment approval.

## Fixed source authority at implementation start

- Main: `bc7fdd3580c02ac3d45fd1b76b3b09aaf24bd60d`
- PR16: `0ccf871451ee7efeebbb257282c2615d8eab172e` (retirement)
- PR20: `f3cf3045fa3c0dd052c21aae0200147f8702c2dc` (Laravel/MySQL baseline)
- PR21: `c210e91e587fc00d87b0c729f7b4ed1572b48bbc` (ENUM instant DDL)
- PR22: `aab218a07017f1be11f5386559b054afe7fb796e` (MariaDB 10.11.19)
- PR23: `d80ad94e6fadb9f8bffc177d23e1a63c843fd26d` (QA orchestrator)
- Batch705: resolve actual head from draft PR #24 after CI; do not guess it.

## Required independent human code review

Reviewers must have no authorship conflict, approve the **exact current commit** and inspect:

1. Data loss / accidental hard deletes in retirement and user/account scopes in PR16.
2. MySQL/MariaDB `ENUM` DDL: 10.11.19 CloudLinux flavor, quoting, `NULL` semantics, zero-copy and timeout behavior. Verify `down()` fails closed.
3. Combined Laravel 158/158 and Android 168/168 APIs and permission invariants.
4. Protected `.htaccess` / eight host override snapshots and dirty-worktree checkout. Verify no blind `git pull`, `reset`, `clean` or migration.
5. Backup manifest/integrity versus actual isolated restore; owner-deferred restore is NOT PASS.

Report any blocking findings in GitHub review and keep all PRs DRAFT until accepted. Author-side self review is NOT independent.

## Offline package preview

`batch705-release-preview.yml` assembles the combined source tree in an ephemeral GitHub runner. It intentionally removes the protected production `public/.htaccess` and preview iOS Firebase files from the **staging copy only**, never from GitHub source or live hosting. It checks for symlinks and private credentials and publishes **only the manifest/hash/file list**, not an installable tarball.

A preview PASS means the source staging inventory was generated without obvious unsafe paths. It does NOT prove a real CloudLinux deploy adapter, DB privileges, PHP extension compatibility, composer production install, queue/cron health, or rollback.

## Production acceptance prerequisites (unmet)

- Review approval from independent engineer/security reviewer for the complete PR16/20/21/22/23/24 chain.
- Fresh current 2-backup state; `app:backup-verify` is logical integrity, NOT a restore test.
- Explicit owner decision on the previously deferred isolated restore drill. Do NOT execute unless owner resumes.
- Privileged DBA assessment of metadata locks, transactions and no silent table copy for the actual CloudLinux MariaDB build.
- Protected host overrides, canonical webroot and dirty checkout restoration/cutover plan, tested on a staging clone.
- Signed release plan and exact source/QA authority before any production DDL or deployment.
- Separate Mobile production OTA/EAS/Play and iOS device gates. Batch705 requests none of those actions.

## Release algorithm (future separately authorized batch)

1. Re-fetch main and PR exact SHAs, independent reviews and CI.
2. Re-check current backups and host drift; prove restore policy and MDL readiness.
3. Prepare a fresh isolated staging copy with protected overrides and production config kept outside tracked code.
4. Dry-run migration against staging clone and compare business invariant snapshots.
5. Obtain separate explicit approval for a time-bounded release window and rollback/roll-forward decision tree.
6. Only after that, enable a short-lived audited deployment adapter on the authorized host. This is NOT implemented here.

## Machine-readable status

BATCH=705
QA=AUTOMATED_EXACT_SOURCE_14_JOBS
STAGING=EPHEMERAL_SHA256_PREVIEW_ONLY
INDEPENDENT_REVIEW=NOT_ATTESTED
PRODUCTION_DB_VERSION=10.11.19-MariaDB-cll-lve_FROM_REPORT702
RESTORE_DRILL=OWNER_DEFERRED_NOT_RUN
HOST_MDL_PROOF=NOT_ATTESTED
HOST_OVERRIDE_CUTOVER=NOT_ATTESTED
DEPLOYMENT_ADAPTER=NOT_INSTALLED
PRODUCTION_DEPLOYMENT=NO
GOOGLE_PLAY=NO
