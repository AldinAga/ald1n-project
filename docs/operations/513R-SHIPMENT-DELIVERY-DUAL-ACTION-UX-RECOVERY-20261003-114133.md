# Batch 513R - Shipment / Delivery Dual Action UX Recovery

- RESULT: FAIL
- TIMESTAMP: 20261003-114133
- MODE: CONTROLLED_SOURCE_UX_REFACTOR_RECOVERY_NO_BUILD
- PRE_HEAD: 5b97ff56f3be533f29bb8c96d0303a1bc8ebbfe4
- SOURCE_COMMIT: NONE
- DOCS_COMMIT: NONE
- FAILED_STAGE: cms-static
- FAILURE_REASON: unexpected rc=1 line=573
- APP_VERSION: 1.0.0
- RUNTIME_VERSION: 1.0.0-build17
- BUILD23_DEFERRED_BY_USER: YES
- EAS_BUILD_STARTED: NO
- EAS_SUBMIT_STARTED: NO
- OTA_PUBLISHED: NO
- GOOGLE_PLAY_ACTION: NO
- DATABASE_CHANGED: NO
- MIGRATION_RUN: NO
- PREEXISTING_AUDIT_RUNTIME_RESIDUE: ALLOWED_READ_ONLY
- RECOVERS_FAILED_BATCH513_REPORT: docs/operations/513-SHIPMENT-DELIVERY-DUAL-ACTION-UX-20261003-112738.md

## UX contract

- Laravel: one Slanje i isporuka action hub with separate shipment and delivery confirmation buttons.
- Laravel: shipment and delivery forms are shown on demand instead of both occupying the page simultaneously.
- Mobile: shipment and delivery confirmations are full-width stacked actions.
- COD: delivery action label explicitly confirms delivery and collected cash-on-delivery amount.
- Non-COD: delivery action label confirms delivery only.
- Existing OrderShipmentService and OrderWorkflowService payment/completion semantics are unchanged.

## Gates

- TDD pre-patch contract: PASS_EXPECTED_FAILURE
- TDD post-patch contract: PASS
- TypeScript: PASS
- Mobile validator: PASS_ZERO_FAIL
- CMS static: NOT_RUN
- Expo install --check: NOT_RUN
- Expo Doctor: NOT_RUN
- OpenAPI parity: NOT_RUN
- git diff --check: PASS
- source scope: PASS_EXACT_THREE_FILES
- source committed: NO
- source pushed: NO

## Source scope

1. `apps/cms/current/resources/views/admin/orders/show.blade.php`
2. `apps/mobile/current/src/features/admin/orders-admin-actions.tsx`
3. `apps/mobile/current/scripts/validate-project.mjs`

## Release decision

- Build22 remains the last production build.
- Build23 is intentionally deferred so additional grouped functionality can be included before spending another production build.
- Before any future Build23, run a fresh release-readiness gate again from the then-current main.
