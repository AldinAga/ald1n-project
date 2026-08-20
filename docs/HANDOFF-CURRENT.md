# ALD1N PROJECT - CURRENT DEVELOPMENT HANDOFF

Updated: 2026-08-20

## Canonical project

- Project root: `/home/icaffeco/ald1n-project`
- Laravel CMS: `/home/icaffeco/ald1n-project/apps/cms/current`
- Mobile: `/home/icaffeco/ald1n-project/apps/mobile/current`
- Git branch: `main`
- GitHub backup: `AldinAga/ald1n-project`
- GitHub checkpoint remote: `github-backup`

## Release and development state

- Mobile v0.7.0: 100% complete, Google Play closed-test device accepted and release-frozen except critical defects.
- v0.8.0 implementation progress: 90%.
- Batch 1 foundation audit: PASS.
- Batch 2 product status + disk-storage cleanup: PASS.
- Batch 3 purchase-cost entry + SuperAdmin inventory valuation KPI: PASS.
- Batch 4 deferred payment through existing Receivables: PASS.
- Batch 5 strict warranty customer ownership + notification scope: PASS.
- No EAS build was used for routine v0.8 source work.

## Current v0.8 certified state

- Manual active/inactive product status control exists without stock-driven auto-activation.
- Duplicate `Interfejs diska` field is removed; `Tip diska` remains the canonical server-driven multi-disk source.
- SuperAdministrator has one-time fast entry for canonical `products.purchase_price_rsd`.
- SuperAdministrator Web/Mobile inventory valuation uses one Laravel `ManagementReportService` authority.
- Three KPI cards exist: purchase value, sale value and expected profit. No fourth KPI was guessed.
- `deferred_payment` / `Odloženo plaćanje` is available on Web and Mobile order creation.
- Deferred payment requires an explicit non-past `payment_due_at` and does not require a bank account.
- Existing `ReceivablesService` is the only debt authority for bank transfer + deferred payment.
- Existing installment plans, payment ledger reconciliation and zero-balance auto-close are reused; no parallel debt tables exist.
- Payment-proof upload remains bank-transfer-only.
- Product Variants remain permanently decommissioned.

## Next implementation step

v0.8.0 final Web/Mobile parity audit, source/runtime certification and release checkpoint.

Batch 5 warranty notification ownership is complete: customer warranty access and expiry/maintenance notifications use the strict `warranty -> order item -> order -> user` authority. Broken owner chains skip customer delivery and customer warranty visibility, while administrator operational notifications remain separate.

## Permanent engineering guards

- Shell scripts: never use process substitution or descriptor-backed pseudo-file paths; avoid unavailable Python CLI dependencies.
- Use audited Node 22 + npm 10 CLI on CloudLinux.
- Every mutating batch requires backup, rollback and quality gates.
- Preserve unrelated Git state.
- All Git commands for delivered batches run only through the universal full-safe backup helper after functional PASS gates.
- The helper backs up the complete safe project state, blocks secrets/runtime payloads and never force-pushes.
- Product Variants are permanently decommissioned.
- Do not reintroduce global one-shot busy/pending tap guards.
- GitHub stores the complete safe source/history/handoff project, not production DB, `.env`, private payloads or runtime backups.
- Never commit secrets, SQL dumps, archives, keystores or credentials.
