# Batch705 Release Candidate Manifest Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Produce a reproducible, read-only deployment-package preview and independently reviewable release-gate receipt without deploying Ald1n CMS.

**Architecture:** An isolated GitHub Actions runner assembles the exact pinned PR-chain source with PR16 in an ephemeral Git tree, extracts a scoped stage, removes the protected production .htaccess from the staged payload, and validates the stage with a dependency-free Node 22 utility. A second job watches the three required QA workflows (14 jobs); the release decision is separately fail-closed and can never deploy. Report and package hashes are delivered as short-lived Actions artifacts.

**Tech Stack:** GitHub Actions, Node 22.16, built-in node:test, Git archive, Bash, SHA-256.

**Spec:** AGENTS.md sections 1A/1B, 17-19, 22, 26; docs/operations/703-ISSUE19-PR22-MARIADB1011-QA-20261017.md; Batch704 exact-source QA.

## Global Constraints

- Current main SHA must be re-read and pinned; never use stale or guessed values.
- PR16, PR20, PR21, PR22, PR23 remain DRAFT; no automated merges or production deployment.
- MariaDB 10.11.19 CloudLinux parity is not exactly proven and MDL privileges are insufficient.
- Owner-deferred isolated restore drill MUST NOT be run or interpreted as PASS.
- No copying a tracked `.htaccess` over the protected production override.
- GitHub jobs may create only ephemeral stage/report artifacts, not changes on hosting.
- Stage outputs are explicitly NOT production-ready packages.

## Review Focus

- Unsafe path or symlink in staged archive must fail before an artifact is published.
- Any accidental .env, key, backup, or tracked public/.htaccess leakage must fail before packaging.
- QA from a different commit/branch, missing/skipped/duplicated job must not pass.
- Self-approval, stale reviewer approval, or missing reviewer must not count as independent review.
- Even successful QA and a valid staging artifact cannot bypass missing restore/MDL/backup/host cutover gates.

---

### Task 1: Pure staging and release-evidence validator

**Files:**
- Create `scripts/batch705/release-proof.test.mjs`
- Create `scripts/batch705/release-proof.mjs`

**Interfaces:**
- `inspectStage(rootDir)` returns relative file paths, byte total and strict status or throws.
- `assessReview(prHead, author, reviews)` accepts only a non-author's APPROVED review pinned to the current SHA.
- `evaluateReleaseGate({qa, staging, reviews, backup, restore, mdl, host})` returns `authorized:false` and a clear blocker list.

- [ ] Write behavior tests for stage traversal/symlink/protected secrets and review identity.
- [ ] Run `node --test scripts/batch705/release-proof.test.mjs` to verify RED (missing module).
- [ ] Implement minimal pure helpers.
- [ ] Re-run same tests and require PASS.
- [ ] Commit exactly test + implementation and the plan.

### Task 2: Deterministic preview manifest CLI

**Files:**
- Extend `scripts/batch705/release-proof.mjs`
- Extend `scripts/batch705/release-proof.test.mjs`

**Interfaces:**
- `buildManifest({sha,tree,archiveSha,archiveSize,stage})` provides a read-only source receipt.
- CLI `--stage <root> <sha> <tree> <sha256> <size>` writes JSON to stdout only.

- [ ] Test invalid sha, incomplete stage, untrusted archive sha, and reproducible output.
- [ ] Verify RED.
- [ ] Implement CLI and manifest serializer.
- [ ] Verify GREEN and repeat all Task 1 tests.
- [ ] Commit.

### Task 3: CI dry-run package, exact QA and reviewer handoff

**Files:**
- Create `.github/workflows/batch705-release-preview.yml`
- Extend three Issue19 QA workflow branch triggers for `audit/batch705-release-readiness-20261017`.
- Create `docs/operations/705-BATCH705-RELEASE-PREVIEW-POLICY-20261017.md` after results.

- [ ] Add Actions workflow that checks exact main and PR23 ancestry, creates stage in runner temp and proves public/.htaccess is excluded.
- [ ] Wait for 14 exact same-SHA QA jobs or record failure/timeout, no publish on missing jobs.
- [ ] Save stage manifest/tar SHA with no deploy credentials, and open an issue if QA fails.
- [ ] CI proof: local tests GREEN, 14/14 QA jobs, preview artifact safe, explicit RELEASE_HOLD.
- [ ] Open DRAFT PR and request human independent review via an actionable evidence packet.
