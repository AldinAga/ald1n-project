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

- Mobile v0.7.0: 100% complete and device accepted through Google Play closed testing.
- v0.8.0 implementation progress: 25%.
- Latest certified work: Product Status + Disk Storage Cleanup Batch 2 V3 PASS.
- Latest report: `docs/operations/MOBILE-V0.8.0-PRODUCT-STATUS-DISK-STORAGE-CLEANUP-BATCH2-V3-20260820-090039.md`.

## Latest v0.8 certified state

- Manual product status control exists.
- Stock quantity does not automatically activate/deactivate products.
- Completeness gate returns the activation blocking reason.
- Duplicate `Interfejs diska` contract is removed.
- `Tip diska` is the single canonical multi-disk repeater source.
- `Ukupan kapacitet diskova` remains derived from the disk list.
- Product Variants remain decommissioned.
- Mobile storage remains server-driven.
- Mobile typecheck, validator, design-token check and CMS static 983/983 are PASS.

## Next implementation step

v0.8.0 Batch 3:

1. SuperAdministrator-only one-time fast purchase-cost entry for existing products using canonical `products.purchase_price_rsd`.
2. Default view prioritizes products with missing/zero purchase cost.
3. Spreadsheet-like fast entry and Save All; no parallel costing model.
4. Shared backend authority for SuperAdministrator inventory KPIs on Web and Mobile:
   - inventory value at purchase cost;
   - inventory value at sale price;
   - expected gross profit = sale-stock value minus purchase-stock value.
5. Do not guess the previously incomplete fourth KPI label; clarify only when implementing that KPI.

Then continue: Deferred Payment through existing Receivables, warranty notification ownership scope, Web/Mobile parity and final certification.

## Permanent engineering guards

- Never use shell process substitution or descriptor-backed pseudo-file paths in delivered hosting scripts.
- Avoid unavailable Python CLI dependencies in hosting scripts.
- Use audited Node 22 + npm 10 CLI on CloudLinux.
- Every mutating batch requires backup, rollback and validation.
- Preserve unrelated Git state.
- Product Variants are permanently decommissioned.
- Do not reintroduce one-shot busy/pending tap guards globally.
- GitHub is source/history backup, not database/private-storage disaster recovery.
- Never commit `.env`, private keys, credentials, SQL dumps, runtime backups or archives.

## GitHub checkpoint workflow

After a stable PASS and an updated handoff:

```bash
bash /home/icaffeco/ald1n-project/scripts/github-checkpoint.sh "descriptive PASS checkpoint message"
```

The helper refuses remote divergence, blocks high-risk paths and secret signatures, never force-pushes, commits, pushes and verifies the remote SHA.
