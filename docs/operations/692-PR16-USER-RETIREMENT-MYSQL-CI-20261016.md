# Report692 — PR16 user retirement MySQL source QA (2026-10-16)

**State:** PASS_ISOLATED_MYSQL_SOURCE_QA_ONLY — **NOT APPROVED FOR MERGE OR PRODUCTION**

## Source and environment
- Repository: `AldinAga/ald1n-project`
- Draft PR: https://github.com/AldinAga/ald1n-project/pull/16
- Tested source commit: `fd7f70ddb35a4f3b2c22af261e7a0e4a52eeea67`
- Main reference at source preflight: `bc7fdd3580c02ac3d45fd1b76b3b09aaf24bd60d`.
- Exact-source CI: https://github.com/AldinAga/ald1n-project/actions/runs/38921288867
- Ephemeral GitHub Actions MySQL 8.4 / PHP 8.4: **12 tests / 90 assertions PASS**, `DB_DATABASE=ald1n_retirement_ci`, database identity asserted before running.
- Mobile: TypeScript PASS; validator **0 FAIL**; full Release25 **163/163 PASS**; Laravel PHP syntax PASS.

## Implemented in draft PR16 (no production deployment)
- Laravel non-physical account retirement with `retired_at`, `status=blocked`, SuperAdmin-only preview, explicit `OBRISI` confirmation and API/CMS routes.
- Android admin user detail and central typed API connected to preview/confirmation with capability checks.
- Retired accounts omitted from active CMS/API user lists; AdminUserService refuses reactivation/edit of retired accounts.
- Orders transferred to active invoking SuperAdmin inside DB transaction.
- Each transferred order records append-only `portal_order_link_history` with original owner, new owner, actor and reason.
- Existing commission financial attribution, product creator IDs, CRM notes, archived order actor IDs and historical foreign-key links are preserved; no user/business hard delete.
- Web sessions revoked with `revoked_by`, devices retained but marked revoked and push destinations cleared; Sanctum tokens deleted.
- Colliding `orders(user_id,idempotency_key_hash)` uniqueness causes a full transaction rollback (verified by real MySQL test).
- Repeat retirement, non-SuperAdmin, self/superadmin, pre-blocked, and unconfirmed attempts are rejected.

## Explicit fail-closed constraints still in force
- If target owns `order_commissions.user_id`, `product_warranties.user_id`, `portal_conversations.user_id`, or historical `order_assignments.new_supplier_user_id`, retirement is blocked (422) until full policy classification. No financial or warranty rows are deleted to circumvent this.
- 2026-10-16 production read-only inventory reported **121 FK references and 7 user-like unconstrained columns**; current isolated CI validates critical FK examples, not exhaustive per-relation live business semantics.
- Remaining gates: review all active task assignees and 121/7 graph; full Laravel suite and CMS route authenticated MySQL tests; production schema parity and migration/rollback plan; staging UAT and owner-authorized release. Do not infer physical Android UAT from source tests.
- PR16 is **DRAFT** and must not be merged/deployed based only on these isolated tests.

## Production guardrails
- Report691 confirmed existing Android production OTA group `c00f227a-9136-4287-a857-bf961d1e35d1`, app `1.0.0`, versionCode `25`, runtime `1.0.0-build17` (Batch687 catalog); PR16 never published an OTA.
- No production database writes, migrations, CMS deployments, APK builds, EAS OTA updates or Play submissions were carried out for this PR16 batch.
- CloudLinux/Unlimited.rs Node22/JDK17 and operator path rules remain in `AGENTS.md` and must be read afresh.
