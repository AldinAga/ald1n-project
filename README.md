# Ald1n CMS & Mobile

Private monorepo for the Ald1n business platform. The repository contains the Laravel CMS/backend, the Expo/React Native mobile application, shared API contracts, design-system packages, release evidence, and operational tooling used to run the production system.

> **Status:** active internal production project. This repository is proprietary and is not intended for public redistribution.

## Current baseline

| Area | Current baseline |
| --- | --- |
| Mobile app | `1.0.0` |
| Mobile runtime | `1.0.0-build17` |
| Expo | SDK 57 |
| React Native | 0.86.x |
| CMS | Laravel 13 / PHP 8.4 |
| Production API | `https://cms.ald1n.com/api/v1` |
| Android package | `com.ald1n.mobile` |
| OTA | EAS Update, production channel |

Verified closeout snapshot on **2026-09-12**:

- CMS static gate: **983 / 983 passed, 0 failed**
- Mobile TypeScript: **PASS**
- Mobile project validator: **0 failures**
- OpenAPI: **3 copies byte-identical**
- Product Variants: **fully decommissioned from active runtime**
- Current release line: **Build17-compatible production runtime**

## Repository layout

```text
ald1n-project/
├── apps/
│   ├── cms/current/        # Laravel CMS, API and web administration
│   └── mobile/current/     # Expo / React Native application
├── packages/
│   ├── api-contract/       # Canonical OpenAPI contract
│   ├── design-tokens/      # Shared Ald1n design tokens
│   ├── ui-tamagui/         # Shared mobile UI definitions/documentation
│   └── web-theme/          # Generated Laravel/web theme tokens
├── docs/
│   └── operations/         # Batch reports, release evidence and operational history
├── releases/               # Release-oriented repository artifacts
└── scripts/                # Repository automation and validation helpers
```

## Main capabilities

The platform combines operational CMS workflows and a mobile application around one API and one set of business rules.

### Catalog and inventory

- Product catalog, search and server-driven filters
- Product creation/editing, media handling and image derivatives
- Categories, brands, brand lines and specification dictionaries
- Stock, receipts, counts and inventory adjustments
- Archive, controlled purge and data-quality tooling
- Product-only commerce model; Product Variants are retired

### Orders and sales

- Customer and administrative order flows
- Assigned orders and operational timelines
- Direct Sale from product detail for authorized users
- Deferred-payment plans with installments and final due date
- Payments, payment proofs, business documents and delivery evidence
- Courier directory, tracking and shipment workflows
- Receivables and collection workflows

### Commissions

- Commission-bearing order list with totals and responsible user
- Single-page commission detail with immutable order-item snapshots
- Pending approval workflow
- Approved payout workflow with payment method, reference and confirmation
- Paid read-only state and history
- Bulk payment plus CSV/PDF exports

### Service and customer care

- Warranties and warranty maintenance
- After-sales cases and communication
- Field work orders and service teams
- Service parts, suppliers and procurement
- Customer portal and customer conversations

### Administration and reporting

- Role/permission-aware administration hub
- Users and access groups
- Operational and management reports
- Audit and security events
- System Health
- Backup creation, verification and retention
- Notification preferences and operational automation

## Technology

### CMS / API

- PHP 8.4
- Laravel 13
- Laravel Sanctum
- MySQL / MariaDB
- Server-rendered administration UI
- Local PDF generation for business documents

CMS source:

```text
apps/cms/current
```

### Mobile

- Expo SDK 57
- React Native 0.86.x
- TypeScript
- Expo Router
- TanStack Query
- Tamagui 2
- Reanimated
- Expo Updates / EAS Update
- SecureStore, notifications and authenticated private-file flows

Mobile source:

```text
apps/mobile/current
```

## API contract

The canonical OpenAPI document is:

```text
packages/api-contract/openapi.yaml
```

It is mirrored byte-for-byte to:

```text
apps/cms/current/docs/openapi.yaml
apps/mobile/current/docs/openapi.yaml
```

Any API change must keep all three copies synchronized and pass the project validation gates.

## Validation

### CMS

From `apps/cms/current`:

```bash
php bin/static-check.php
```

The current certified baseline is:

```text
Ukupno: 983, neuspešno: 0
```

### Mobile

From `apps/mobile/current`:

```bash
npm run typecheck
npm run validate
```

The validator must finish with:

```text
Ukupno FAIL: 0
```

### OpenAPI

Before a release/checkpoint, confirm that the CMS, Mobile and canonical OpenAPI files are byte-identical.

## Backup and recovery

The CMS contains first-party backup tooling. A manual backup can be created with:

```bash
cd apps/cms/current
php artisan app:backup-create --type=manual
```

Verify a backup before considering it restore-ready:

```bash
php artisan app:backup-verify
```

Backups include the database and configured private storage paths and are protected by SHA-256 manifest verification.

## Release model

The mobile application uses EAS Build for native binaries and EAS Update for compatible JavaScript/TypeScript production updates.

Current production mobile authority:

```text
App:             Ald1n CMS
Version:         1.0.0
Runtime:         1.0.0-build17
Android package: com.ald1n.mobile
Channel:         production
```

A new native build is created only when a native/config/dependency change requires it. Compatible application changes can be released through the matching runtime with the normal release gates and physical-device acceptance.

## Operational rules

- Do not commit `.env`, passwords, private keys or production secrets.
- Do not modify production data during read-only validation/checkpoint batches.
- Keep CMS, Mobile and canonical OpenAPI contracts synchronized.
- Keep Product Variants decommissioned unless a future approved requirement explicitly restores them.
- Prefer small, auditable batches with backup/rollback where mutation is involved.
- Use `docs/operations/` reports as the audit trail for release and maintenance work.
- Production release work must pass the CMS static gate, Mobile TypeScript check, Mobile validator and relevant device acceptance.

## CloudLinux / Expo note

On the production CloudLinux host, Expo is used for compatibility checks rather than blind dependency repair. In particular, do **not** use `expo install --fix` as an installation workflow on that host. Follow the project operations runbooks for dependency work and EAS release commands.

## Documentation

Operational evidence and historical batch reports live in:

```text
docs/operations/
```

These reports document source authority, release gates, OTA/build decisions, physical-device acceptance, backups, cleanup and rollback information.

## License

Copyright © Ald1n. All rights reserved.

This is proprietary software for internal use. No permission is granted to copy, redistribute, sublicense or publish the source code without explicit authorization.
