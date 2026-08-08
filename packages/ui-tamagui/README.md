# Ald1n UI / Tamagui

## Foundation

- Tamagui 2
- Config v5
- Reanimated animation driver
- Shared Ald1n Violet Light/Dark design tokens
- Android and future iOS use the same Expo source
- Laravel receives generated CSS variables from the same token source

## Migration policy

Business logic is not rewritten as part of the UI migration.

Keep unchanged unless separately approved:

- API contracts
- Sanctum auth
- Google Sign-In
- SecureStore
- push registration/delivery
- TanStack Query
- Expo Router routes
- cart/checkout/idempotency

UI migration proceeds component-by-component.

## Theme rollout

Phase 4B:
- foundation only
- TamaguiProvider
- shared tokens
- no forced mobile dark-mode switch yet

Phase 4C:
- PageHeader
- Screen
- Button
- Card
- TextField
- Pill / Badge
- bottom navigation

Phase 4D:
- Login
- Home
- Catalog
- Orders
- Notifications
- Account

Laravel theme deployment is a separate CMS visual release.
