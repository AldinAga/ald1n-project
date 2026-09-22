# Mobile v0.8.0 Shipment UI + Central Courier Directory - Batch 11 V2

- Timestamp: `20260821-122208`
- Mobile package version: `0.8.0`
- Source mutation: `YES`
- Source mode on entry: `BATCH10_PASS`
- Business DB mutation by this script: `NO`
- Migration: `NO`
- Dependency install: `NO`
- EAS build: `NO`
- Git/GitHub checkpoint: `NO` - milestone-only policy
- Backup: `/home/icaffeco/backups/releases/mobile-v0.8.0-shipment-ui-central-courier-directory-batch11-v2-20260821-122208`
- Corrects failed artifact: `039` with staged mutation, flexible anchors and captured patch diagnostics.
- Rollback contract: `SNAPSHOT_BEFORE_SEMANTIC_PREFLIGHT`

## Implemented

- Shipment panel now selects an active courier from the central server-driven directory and sends canonical `courier_service_id`.
- Shipment keeps shipped date/time, recipient name/phone, tracking number, optional proof and note.
- Courier tracking URL is exposed only as validated public `tracking_url`; private/internal URL/path fields remain sanitized.
- Existing and newly selected courier tracking pages can be opened from Android.
- Recording shipment remains distinct from order completion; existing `OrderShipmentService` remains authoritative.
- SuperAdmin-only central Courier Directory provides list/create/update for name, HTTPS tracking URL, sort order, active state and default state.
- Existing `CourierDirectoryService` remains the business authority and no hardcoded courier list is introduced in Mobile.
- No courier delete workflow was introduced.

## Validation

- PHP lint: `PASS`
- API route contract: `PASS`
- Mobile typecheck: `PASS`
- Mobile project validator: `PASS`
- Design token check: `PASS`
- CMS canonical static check: `PASS`
- OpenAPI parity: `PASS` across `3` copies
- Release metadata immutability: `PASS`
- EAS: `NOT RUN`
- GitHub full update: `NOT RUN`

## Mandatory v0.8 extension parity

| Workstream | Before | After |
|---|---:|---:|
| Product list + open/edit existing product | FULL | FULL |
| Archive + Restore | FULL | FULL |
| Product Image Manager | FULL | FULL |
| Direct Sale | FULL | FULL |
| Shipment + central courier directory | PARTIAL | FULL |
| Complete user management | MISSING | MISSING |
| EUR/RSD Exchange Rate | MISSING | MISSING |

- Deterministic extension score: `10/14`
- Mandatory extension parity: `71%`

## Final

- BATCH11_V2_RESULT: `PASS`
- NEXT_ARTIFACT_SEQUENCE: `042`
- NEXT_ACTION: `Complete User Management`
