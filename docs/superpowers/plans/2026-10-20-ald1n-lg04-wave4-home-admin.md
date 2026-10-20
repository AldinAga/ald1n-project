# LG-04 Wave 4 — Home and Administration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement task-by-task, preserving test-first RED/GREEN evidence.

**Goal:** Add narrowly bounded Android native-glass affordances to already-authorized Home quick links and Administration global search, leaving business data and other actions opaque.

**Architecture:** Stack one Draft PR on Wave3 PR #50 exact SHA 65a88e7c10b017b648b29f08dd256840e94d6a3f (which stacks on Wave2/1 and Build26 native PR47). Reuse the existing glass-header policy and native dependency for one icon-sized lens per primary link, and one Pressable for the whole link. Keep all role/capability checks, routes, data and backend identical.

**Tech Stack:** Expo SDK57, React Native 0.86.3, TypeScript, react-native-android-liquid-glass 0.2.1, native Animated, Node22, GitHub Actions.

**Spec:** apps/mobile/current/docs/ANDROID-LG04-DEVICE-COMPATIBILITY.md; apps/mobile/current/docs/ANDROID-LG04-NEXT-PLAY-OPEN-TESTING-AUTOSUBMIT.md; AGENTS.md.

## Global Constraints

- Android app 1.0.0; Build26 testing source 58b5e45b6b53a60e547e225f03ab43dd9ca85361, runtime 1.0.0-lg03-play-testing1, profile/channel lg03-play-testing; beta only and no auto-submit.
- Do not alter CMS, API, DB, role permission logic, product variants, production Android Build25, native packages/lockfile, EAS identities or runtime.
- Native glass applies only to the 48dp icon; title, description, prices, inventory, report amounts, search input and cards remain on solid theme surfaces.
- Unknown OS accessibility setting, Reduce Transparency, High Text Contrast or pipeline failure => opaque icon. Reduce Motion or unknown preference => no press motion. Non-Android/API24-32 => solid.
- Preserve existing Home /admin/catalog/create and /cart navigation through permission checks. Admin /admin/search remains gated by hasAdminAccess.
- No merge, production build, Android/OTA publish, Play, iOS build or release automation.

## Review Focus

- Device is API32 / iOS/web: never instantiate a native lens (Task 1).
- Accessibility preference query errors/unknown: prefer solid and static display (Task 1).
- Native pipeline failure/background activity: stop native enhancement, preserve navigation (Task 1).
- Screen 320dp/large fonts: one accessible full-width tap and wrapping readable text (Task 1).
- Non-admin or unavailable capability: no new shortcut, privilege, data query or route appears (Tasks 2–3).

---

### Task 1: Accessible 48dp glass accent shortcut

**Files:**
- Create: apps/mobile/current/src/components/workspaces/glass-workspace-shortcut.tsx
- Test: apps/mobile/current/scripts/release25/android-lg04-home-admin-shortcuts.test.mjs

**Interfaces:** export function GlassWorkspaceShortcut(props: {title:string;copy?:string;glyph:GlyphName;onPress:()=>void}): React.JSX.Element. The caller alone gates permissions and owns the route. Consume resolveHeaderGlassPolicy (existing).

- [ ] Write the new regression tests FIRST and prove expected missing-component RED in exact-head PR CI.
- [ ] Implement single accessible Pressable, 48dp native-or-solid icon on collapsable=false native parent, opaque text/card, OS accessibility fallback and native-driver press spring.
- [ ] Check exact-head TypeScript, validator and regression tests in CI (GREEN).

### Task 2: Home quick shortcuts without KPI or permission drift

**Files:**
- Modify: apps/mobile/current/src/app/(app)/(tabs)/home.tsx
- Test: apps/mobile/current/scripts/release25/android-lg04-home-admin-shortcuts.test.mjs

**Interfaces:** HomeActionList(actions, glassShortcuts=false) uses GlassWorkspaceShortcut only when rendering quickActions; other groups keep OperatorRow.

- [ ] RED Home assertions demand opt-in rendering and unchanged capability checks.
- [ ] Wire the existing quickActions array through the new component without modifying any routes or queries.
- [ ] GREEN; visually constrained to actions, not KPI/amounts/focus tiles.

### Task 3: Admin global search and release docs

**Files:**
- Modify: apps/mobile/current/src/app/(app)/admin/index.tsx
- Modify: apps/mobile/current/docs/ANDROID-LG04-DEVICE-COMPATIBILITY.md
- Modify: apps/mobile/current/docs/ANDROID-LG04-NEXT-PLAY-OPEN-TESTING-AUTOSUBMIT.md
- Create: .github/workflows/android-lg04-wave4-home-admin-source-qa.yml
- Test: apps/mobile/current/scripts/release25/android-lg04-home-admin-shortcuts.test.mjs

**Interfaces:** Admin search CTA remains onPress={() => router.push('/admin/search' as Href)}, after hasAdminAccess guard and success data gate.

- [ ] RED Admin, device and beta-only requirements with wave4 test.
- [ ] Replace only global-search OperatorRow with GlassWorkspaceShortcut, leave opaque adminSearch TextInput and group cards intact.
- [ ] Document Wave4 accessibility/device UAT, append accurate Serbian Play beta notes, CI uses no release permissions and complete Release25 suite.
- [ ] GREEN exact-head TypeScript, validator, Release25, Android JS bundle and native pin/release assertions.
- [ ] Leave all PR #47–#51 equivalents Draft; no merge or Play mutation. Record actual CI SHA and gaps in PR description.

## Verification

- npm ci --no-audit --no-fund
- npm run typecheck
- npm run validate
- node --test scripts/release25/android-lg04-home-admin-shortcuts.test.mjs
- npm run test:release25
- EXPO_PUBLIC_APP_ENV=production EXPO_PUBLIC_API_URL=https://cms.ald1n.com/api/v1 ALD1N_LG03_PLAY_TESTING=1 EAS_BUILD_PROFILE=lg03-play-testing npx expo export --platform android --output-dir "$RUNNER_TEMP/ald1n-lg04-wave4-android"

Physical Android compatibility, native-glass optics, performance and high-contrast UAT are still independent HOLDs; source CI is not a release authorization.
