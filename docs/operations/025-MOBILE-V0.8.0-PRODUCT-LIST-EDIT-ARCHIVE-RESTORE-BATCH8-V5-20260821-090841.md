# Mobile v0.8.0 Product List/Edit/Archive/Restore - Batch 8 V5

- Timestamp: `20260821-090841`
- Mobile package version: `0.8.0`
- Source mutation: `YES`
- Business DB mutation by this script: `NO`
- Migration: `NO`
- Dependency install: `NO`
- EAS build: `NO`
- Git/GitHub checkpoint: `NO` - milestone-only policy
- Corrects failed artifacts: `017`, `019`, `021` and `023`; V5 replaces the unavailable Artisan test command with the canonical production CMS static-check runner.
- Source mode on entry: `BASELINE`
- Backup: `/home/icaffeco/backups/releases/mobile-v0.8.0-product-list-edit-archive-restore-batch8-v5-20260821-090841`

## Implemented

- Android Admin catalog list with search, status filter, pagination and Archived mode.
- Open existing product from Android and edit SKU, type, brand, line, model, name, dynamic specifications, repeatable storage, price, purchase price, commission, stock, low-stock threshold, status, description and internal notes.
- Existing ProductRequest + ProductAdminService remain the single backend validation/business authority.
- Android Archive product action.
- Android Restore / Opozovi Arhiviranje action.
- Strict TypeScript narrowing for catalog options is explicit before Edit callbacks use the payload.
- TextField archived/read-only state uses the actual Tamagui `disabled` contract instead of unsupported React Native `editable`.
- New screens use canonical local `theme` naming with AppColors `ink` / `muted` and typography `small` tokens; no validator-forbidden `colors.*` consumer remains.
- New Expo Router paths use typed Href casts so stale generated route declarations cannot break source typecheck.
- Existing category relation is preserved when Edit has no type-derived category replacement.
- Stock quantity is editable only when backend capability stock_adjust is present.
- Archive/Restore endpoints are repeat-safe and retain existing Laravel audit logic.
- Existing image upload remains available from Edit; full shared Product Image Manager is intentionally next Batch.
- Admin Hub now exposes Katalog artikala separately from Dodaj artikal.
- OpenAPI contract updated across `3` byte-identical copies.

## Validation

- PHP lint: `PASS`
- API route contract: `PASS`
- Mobile typecheck: `PASS`
- Mobile project validator: `PASS`
- Design token check: `PASS`
- OpenAPI parity: `PASS`
- CMS canonical static check: `PASS` (Ukupno: 983, neuspešno: 0)
- EAS: `NOT RUN`
- GitHub full update: `NOT RUN`

## Mandatory v0.8 extension parity

| Workstream | Before | After |
|---|---:|---:|
| Product list + open/edit existing product | MISSING | FULL |
| Archive + Restore | MISSING | FULL |
| Product Image Manager | PARTIAL | PARTIAL |
| Direct Sale | MISSING | MISSING |
| Shipment + central courier directory | PARTIAL | PARTIAL |
| Complete user management | MISSING | MISSING |
| EUR/RSD Exchange Rate | MISSING | MISSING |

- Deterministic extension score: `6/14`
- Mandatory extension parity: `43%`
- Historic original v0.8 source/runtime certification remains valid.

## Final

- BATCH8_V5_RESULT: `PASS`
- NEXT_ARTIFACT_SEQUENCE: `026`
- NEXT_ACTION: shared Product Image Manager for Create + Edit: existing image index, upload, primary, rotate, reorder and delete.
