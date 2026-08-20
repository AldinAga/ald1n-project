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
- v0.8.0 implementation progress: 50% after Batch 3 PASS.
- Latest certified work: Purchase Costs + SuperAdmin Inventory KPI Batch 3 PASS.
- Latest report: `docs/operations/MOBILE-V0.8.0-PURCHASE-COSTS-SUPERADMIN-INVENTORY-KPI-BATCH3-20260820-100509.md`.

## Latest v0.8 certified state

- Batch 2 remains certified: manual product status control, no stock-driven activation, duplicate disk interface removed, canonical multi-disk repeater preserved.
- One-time SuperAdministrator purchase-cost helper uses only canonical `products.purchase_price_rsd`.
- Missing/zero purchase cost is the default helper view; all products remain optionally reviewable.
- Save All supports normal Tab navigation and Enter advances to the next cost input.
- Purchase-cost helper never changes stock quantity, sale price or product status.
- `ManagementReportService::inventory()` is the shared backend authority for inventory valuation.
- SuperAdministrator Web and Mobile show exactly three inventory valuation KPIs:
  - Vrednost po nabavnoj ceni;
  - Vrednost po prodajnoj ceni;
  - Ukupna očekivana zarada.
- The previously incomplete fourth KPI was not guessed and is not implemented.
- Mobile receives the same valuation through the Admin Foundation API; no parallel mobile calculation exists.
- OpenAPI canonical/CMS/Mobile copies are synchronized.
- GitHub checkpoint secret scan uses explicit `-e` so patterns beginning with hyphens cannot be parsed as options.

## Next implementation step

v0.8.0 Batch 4:

1. Add `Odloženo plaćanje` to Web and Mobile order creation.
2. Reuse the existing Receivables domain; do not create a second debt system.
3. Create/reconcile the receivable for the unpaid balance and due date through the existing canonical services.
4. Preserve normal payments, installments and close-on-zero behavior.
5. Then scope warranty-expiry notifications to warranties belonging to products actually ordered by that customer.
6. Finish Web/Mobile parity and v0.8 certification without unnecessary EAS builds.

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

After this Batch 3 report is confirmed PASS:

```bash
bash /home/icaffeco/ald1n-project/scripts/github-checkpoint.sh "v0.8 Batch 3 PASS - purchase costs and SuperAdmin inventory valuation"
```

The helper refuses remote divergence, blocks high-risk paths and secret signatures, never force-pushes, commits, pushes and verifies the remote SHA.
