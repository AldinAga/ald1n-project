
============================================================
MOBILE v0.7.0 - VERSION TRANSITION - BATCH 2
============================================================
SEMVER 0.6.0 -> 0.7.0 + RUNTIME FALLBACK + VALIDATOR CONTRACT
DATE=Tue Aug 18 12:34:13 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-VERSION-TRANSITION-BATCH2-20260818-123413.md
BACKUP=/home/icaffeco/backups/releases/mobile-v0.7.0-version-transition-batch2-20260818-123413
SOURCE_SCOPE=6_MOBILE_VERSION_CONTRACT_FILES_ONLY
MANAGED_FILES=6
RELEASE_VERSION_FROM=0.6.0
RELEASE_VERSION_TO=0.7.0
DATABASE_WRITES_EXPECTED=0
MIGRATIONS_RUN=NO
NEW_NATIVE_DEPENDENCY=NO
EAS_COMMANDS_RUN=NO
EAS_BUILD=NO
REPORT_GENERATION=ENABLED_DOCS_OPERATIONS

============================================================
0. CONCURRENCY + PREFLIGHT
============================================================
OTHER_ALD1N_LOCK_COUNT=0
CONCURRENCY_LOCK=ACQUIRED
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
NODE_22_CANONICAL_RUNTIME=PASS
NPM_10_CANONICAL_CLI=PASS
KICKOFF_REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.7.0-KICKOFF-ORDERS-ADMIN-AUDIT-BATCH1-V2-20260818-114317.md
KICKOFF_PREREQUISITE=PASS
OPENAPI_PRE_PARITY=PASS

============================================================
1. EXACT ACCEPTED v0.6.0 VERSION CONTRACT
============================================================
ACCEPTED_V0_6_VERSION_CONTRACT=PASS

============================================================
2. BACKUP SIX VERSION CONTRACT FILES
============================================================
BACKUP_READY=YES

============================================================
3. BUILD v0.7.0 VERSION CONTRACT IN TEMP
============================================================
TEMP_VERSION_TRANSITION_PATCH=PASS

============================================================
4. TEMP CONTRACT VERIFICATION
============================================================
TEMP_V0_7_VERSION_CONTRACT=PASS
TEMP_VALIDATOR_SYNTAX=PASS

============================================================
5. INSTALL ATOMIC VERSION TRANSITION
============================================================
VERSION_TRANSITION_INSTALL=PASS

============================================================
6. FULL MOBILE QUALITY GATES
============================================================

> ald1n-mobile@0.7.0 typecheck
> tsc --noEmit

MOBILE_TYPECHECK=PASS
PASS package.json postoji.
PASS app.config.js postoji.
PASS eas.json postoji.
PASS .env.example postoji.
PASS assets/icon.png postoji.
PASS assets/adaptive-icon.png postoji.
PASS assets/splash-icon.png postoji.
PASS src/app/_layout.tsx postoji.
PASS src/app/(auth)/login.tsx postoji.
PASS src/app/(app)/(tabs)/home.tsx postoji.
PASS src/app/(app)/(tabs)/catalog.tsx postoji.
PASS src/app/(app)/(tabs)/orders.tsx postoji.
PASS src/app/(app)/(tabs)/notifications.tsx postoji.
PASS src/app/(app)/(tabs)/account.tsx postoji.
PASS src/app/(app)/product/[slug].tsx postoji.
PASS src/app/(app)/order/[id].tsx postoji.
PASS src/app/(app)/devices.tsx postoji.
PASS src/app/(app)/cart.tsx postoji.
PASS src/app/(app)/checkout.tsx postoji.
PASS src/app/(app)/notification-settings.tsx postoji.
PASS src/app/(app)/after-sales/index.tsx postoji.
PASS src/app/(app)/after-sales/[id].tsx postoji.
PASS src/app/(app)/after-sales/create/[orderId].tsx postoji.
PASS src/app/(app)/warranties/index.tsx postoji.
PASS src/app/(app)/warranties/[id].tsx postoji.
PASS src/app/(app)/commissions/index.tsx postoji.
PASS src/app/(app)/commissions/[id].tsx postoji.
PASS src/app/(app)/assigned-orders/index.tsx postoji.
PASS src/app/(app)/assigned-orders/[id].tsx postoji.
PASS src/features/warranties/warranty-pdf.ts postoji.
PASS src/features/orders/order-post-create-files.ts postoji.
PASS src/features/after-sales/attachment-picker.ts postoji.
PASS src/features/after-sales/attachment-download.ts postoji.
PASS src/lib/api/client.ts postoji.
PASS src/lib/api/endpoints.ts postoji.
PASS src/features/auth/auth-provider.tsx postoji.
PASS src/features/auth/google-auth.ts postoji.
PASS src/features/device/device-registrar.tsx postoji.
PASS src/features/cart/cart-provider.tsx postoji.
PASS src/features/notifications/push-service.ts postoji.
PASS src/features/notifications/push-notification-bridge.tsx postoji.
PASS docs/openapi.yaml postoji.
PASS tamagui.config.ts postoji.
PASS src/design/ald1n-tokens.generated.ts postoji.
PASS Generated design token fajlovi su sinhronizovani sa canonical JSON source-om.
PASS Tamagui onBrand koristi canonical onPrimary semantic token.
PASS package.json je validan JSON.
PASS eas.json je validan JSON.
PASS Expo SDK 57 verzija prati zvanični template.
PASS React Native verzija prati Expo SDK 57 template.
PASS Expo Router verzija je zaključana.
PASS Expo development client je uključen.
PASS SecureStore zavisnost postoji.
PASS TanStack Query zavisnost postoji.
PASS Minimalna Node.js verzija odgovara SDK 57 zahtevu.
PASS Aplikaciona package verzija je 0.7.0.
file:///home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:114
assert(packageLock.version === '0.7.0' && packageLock.packages?.['']?.version === '0.7.0', 'package-lock release verzija je 0.7.0.');
       ^

ReferenceError: packageLock is not defined
    at file:///home/icaffeco/ald1n-project/apps/mobile/current/scripts/validate-project.mjs:114:8
    at ModuleJob.run (node:internal/modules/esm/module_job:343:25)
    at async onImport.tracePromise.__proto__ (node:internal/modules/esm/loader:681:26)
    at async asyncRunEntryPointWithESMLoader (node:internal/modules/run_main:117:5)

Node.js v22.23.2

============================================================
ROLLBACK
============================================================
ROLLBACK_VERSION_TRANSITION_FILES=RESTORED_6
ROLLBACK_DATABASE=NO_DATABASE_WRITES
ROLLBACK_BACKUP=/home/icaffeco/backups/releases/mobile-v0.7.0-version-transition-batch2-20260818-123413
