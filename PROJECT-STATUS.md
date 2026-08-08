# Ald1n Project Status

## Mobile

Known-good real-device release: v0.3.0
Current source before v0.4.0: v0.3.1
Safe-area fix: included in source, separate APK intentionally skipped
Next EAS build target: v0.4.0

Expo SDK: 57
React Native: 0.86.2
Google Sign-In: operational
FCM V1: configured
Push client: implemented
Push backend delivery: intentionally disabled until final live push acceptance

## CMS

Version: 2.2.0
Production: /home/icaffeco/cms.ald1n.com
Phase 3B push backend: installed
Phase 3C Google auth: installed
Google auth: enabled
Google registration: enabled
Google registration auto-activate: false

## Repository architecture

apps/mobile/current
- shared Expo source for Android and future iOS
- independent npm dependency root

apps/cms/current
- sanitized Laravel source
- production .env/vendor/storage runtime data are NOT copied

packages/design-tokens
- source of truth for Ald1n Light/Dark design system

packages/ui-tamagui
- Tamagui/Ald1n component design documentation and shared definitions

packages/web-theme
- generated Laravel CSS theme tokens

packages/api-contract
- shared OpenAPI contract

## Next release

Mobile v0.4.0
- Tamagui 2 foundation
- Config v5
- Ald1n Violet Light/Dark tokens
- Reanimated animation driver
- safe-area normalization
- shared primitive UI layer
- no business/API behavior changes
