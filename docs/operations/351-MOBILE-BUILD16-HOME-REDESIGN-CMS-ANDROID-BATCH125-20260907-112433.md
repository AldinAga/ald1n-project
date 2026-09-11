# Report351 - Build16 Home Redesign CMS + Android Batch125

- Timestamp: 20260907-112433
- Purpose: implement the approved Ald1n Operator Home redesign on Android and Laravel CMS while preserving existing business data, permissions, APIs and Product Variants decommissioning
- Expected baseline: e76228b77de0ab0f2def70d3b54800644d09d9ed
- Design authority: Ald1n Operator v1
- Previous authority: Report350 V3 / Batch124 PASS
- Android icon authority: Expo Symbols / Material Symbols one family
- Laravel icon authority: existing x-icon semantic compatibility layer; Phosphor runtime vendoring remains isolated for the next icon migration batch
- EAS build creation: NO
- OTA publish: NO
- Database writes: NO
- Product Variants: MUST REMAIN DECOMMISSIONED

============================================================
0. SOURCE AUTHORITY AND WORKTREE GUARDS
============================================================
SHELL_HOME_ENV=PASS_DIRECTORY_PRESERVED_FOR_EXPO
BRANCH=main
LOCAL_HEAD=e76228b77de0ab0f2def70d3b54800644d09d9ed
REMOTE_HEAD=e76228b77de0ab0f2def70d3b54800644d09d9ed
HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
HTACCESS_DRIFT=PASS_KNOWN_RUNTIME_EXCEPTION
FAIL_CODE=UNEXPECTED_UNTRACKED_SOURCE
.build16-batch123-backup-20260907-082312/apps/cms/current/public/assets/css/ald1n-ui-v2.css
.build16-batch123-backup-20260907-082312/apps/mobile/current/src/design/ald1n-tokens.generated.ts
.build16-batch123-backup-20260907-082312/packages/design-tokens/ald1n-violet.json
.build16-batch123-backup-20260907-082312/packages/web-theme/ald1n-violet.css
.build16-batch123-backup-20260907-082312/scripts/generate-design-tokens.mjs
.build16-batch123-v2-backup-20260907-083012/apps/cms/current/public/assets/css/ald1n-ui-v2.css
.build16-batch123-v2-backup-20260907-083012/apps/mobile/current/package-lock.json
.build16-batch123-v2-backup-20260907-083012/apps/mobile/current/package.json
.build16-batch123-v2-backup-20260907-083012/apps/mobile/current/scripts/validate-project.mjs
.build16-batch123-v2-backup-20260907-083012/apps/mobile/current/src/design/ald1n-tokens.generated.ts
.build16-batch123-v2-backup-20260907-083012/packages/design-tokens/ald1n-violet.json
.build16-batch123-v2-backup-20260907-083012/packages/web-theme/ald1n-violet.css
.build16-batch123-v2-backup-20260907-083012/scripts/generate-design-tokens.mjs
.build16-batch123-v3-backup-20260907-084320/apps/cms/current/public/assets/css/ald1n-ui-v2.css
.build16-batch123-v3-backup-20260907-084320/apps/mobile/current/package-lock.json
.build16-batch123-v3-backup-20260907-084320/apps/mobile/current/package.json
.build16-batch123-v3-backup-20260907-084320/apps/mobile/current/scripts/validate-project.mjs
.build16-batch123-v3-backup-20260907-084320/apps/mobile/current/src/design/ald1n-tokens.generated.ts
.build16-batch123-v3-backup-20260907-084320/packages/design-tokens/ald1n-violet.json
.build16-batch123-v3-backup-20260907-084320/packages/web-theme/ald1n-violet.css
.build16-batch123-v3-backup-20260907-084320/scripts/generate-design-tokens.mjs
.build16-batch123-v4-backup-20260907-091704/apps/cms/current/public/assets/css/ald1n-ui-v2.css
.build16-batch123-v4-backup-20260907-091704/apps/mobile/current/package-lock.json
.build16-batch123-v4-backup-20260907-091704/apps/mobile/current/package.json
.build16-batch123-v4-backup-20260907-091704/apps/mobile/current/scripts/validate-project.mjs
.build16-batch123-v4-backup-20260907-091704/apps/mobile/current/src/design/ald1n-tokens.generated.ts
.build16-batch123-v4-backup-20260907-091704/packages/design-tokens/ald1n-violet.json
.build16-batch123-v4-backup-20260907-091704/packages/web-theme/ald1n-violet.css
.build16-batch123-v4-backup-20260907-091704/scripts/generate-design-tokens.mjs
.build16-batch123-v6-backup-20260907-092919/apps/cms/current/public/assets/css/ald1n-ui-v2.css
.build16-batch123-v6-backup-20260907-092919/apps/mobile/current/package-lock.json
.build16-batch123-v6-backup-20260907-092919/apps/mobile/current/package.json
.build16-batch123-v6-backup-20260907-092919/apps/mobile/current/scripts/validate-project.mjs
.build16-batch123-v6-backup-20260907-092919/apps/mobile/current/src/design/ald1n-tokens.generated.ts
.build16-batch123-v6-backup-20260907-092919/packages/design-tokens/ald1n-violet.json
.build16-batch123-v6-backup-20260907-092919/packages/web-theme/ald1n-violet.css
.build16-batch123-v6-backup-20260907-092919/scripts/generate-design-tokens.mjs
.build16-batch124-backup-20260907-100747/apps/cms/current/public/assets/css/ald1n-ui-v2.css
.build16-batch124-backup-20260907-100747/apps/mobile/current/scripts/validate-project.mjs
.build16-batch124-backup-20260907-100747/apps/mobile/current/src/app/(app)/(tabs)/home.tsx
.build16-batch124-backup-20260907-100747/apps/mobile/current/src/components/ui/button.tsx
.build16-batch124-backup-20260907-100747/apps/mobile/current/src/components/ui/card.tsx
.build16-batch124-backup-20260907-100747/apps/mobile/current/src/components/ui/glyph.tsx
.build16-batch124-backup-20260907-100747/apps/mobile/current/src/constants/theme.ts
.build16-batch124-backup-20260907-110746/apps/cms/current/public/assets/css/ald1n-ui-v2.css
.build16-batch124-backup-20260907-110746/apps/mobile/current/scripts/validate-project.mjs
.build16-batch124-backup-20260907-110746/apps/mobile/current/src/app/(app)/(tabs)/home.tsx
.build16-batch124-backup-20260907-110746/apps/mobile/current/src/components/ui/button.tsx
.build16-batch124-backup-20260907-110746/apps/mobile/current/src/components/ui/card.tsx
.build16-batch124-backup-20260907-110746/apps/mobile/current/src/components/ui/glyph.tsx
.build16-batch124-backup-20260907-110746/apps/mobile/current/src/constants/theme.ts
.build16-batch124-backup-20260907-111307/apps/cms/current/public/assets/css/ald1n-ui-v2.css
.build16-batch124-backup-20260907-111307/apps/mobile/current/scripts/validate-project.mjs
.build16-batch124-backup-20260907-111307/apps/mobile/current/src/app/(app)/(tabs)/home.tsx
.build16-batch124-backup-20260907-111307/apps/mobile/current/src/components/ui/button.tsx
.build16-batch124-backup-20260907-111307/apps/mobile/current/src/components/ui/card.tsx
.build16-batch124-backup-20260907-111307/apps/mobile/current/src/components/ui/glyph.tsx
.build16-batch124-backup-20260907-111307/apps/mobile/current/src/constants/theme.ts

BATCH125_RESULT=FAIL
REPORT351_RESULT=FAIL
SOURCE_COMMIT_CREATED=0
FAIL_RC=26
ROLLBACK_UNCOMMITTED=YES
