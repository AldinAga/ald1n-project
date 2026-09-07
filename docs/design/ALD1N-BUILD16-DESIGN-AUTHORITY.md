# Ald1n Build16 Design Authority

Status: CANONICAL FOUNDATION v1
Scope: Laravel CMS + Android / Expo application
Build target: Android Build 16
Visual direction: Premium Utilitarian Operator UI

## 1. Design read

Ald1n is an operational business product, not a marketing site. The interface must optimize daily work: scanability, hierarchy, state clarity, speed, touch confidence and low fatigue. Build16 may feel more alive and premium, but motion and decoration never outrank operational clarity.

The visual system deliberately rejects generic AI-purple / blue-glow styling, card-in-card layouts, random glassmorphism, excessive pill shapes, mixed icon families and animation without purpose.

## 2. Color authority

The canonical source is:

`packages/design-tokens/ald1n-operator.json`

Light mode uses cool graphite neutrals with a single burnt-orange brand accent.

Dark mode uses near-black graphite surfaces with the same burnt-orange accent, tuned brighter for contrast.

Brand accent:
- Light primary: `#C45116`
- Dark primary: `#FF8A3D`

Semantic success, warning, danger and info colors remain separate because they communicate state. They are not secondary brand accents.

Legacy violet filenames, violet palette values and purple glow are not part of Build16 design authority.

## 3. Surface and card rules

Cards exist only when a bounded object or elevation communicates real hierarchy.

Prefer:
- whitespace,
- dividers,
- tonal surface changes,
- grouped rows,
- direct hierarchy.

Avoid:
- card inside card,
- every section boxed,
- border + shadow + radius on every group,
- multiple nested framing levels.

## 4. Shape authority

- small: 8px
- medium: 10px
- large: 14px
- extra large: 18px
- modal / large container: 24px
- pill: reserved for chips, statuses and controls whose meaning benefits from a pill shape

Primary buttons are not required to be pills. Existing pill buttons are compatibility surfaces until the component migration batch.

## 5. Motion authority

Motion must communicate one of:
- feedback,
- state change,
- continuity,
- spatial relationship,
- rare delight.

Canonical timing:
- instant: 50ms
- press: 120ms
- fast: 160ms
- standard: 200ms
- emphasized: 280ms
- slow: 400ms

Canonical easing:
- enter / exit: `cubic-bezier(0.23, 1, 0.32, 1)`
- movement: `cubic-bezier(0.77, 0, 0.175, 1)`
- sheets: `cubic-bezier(0.32, 0.72, 0, 1)`

No global bounce. No scroll hijacking. No perpetual motion in operational screens. Reduced-motion paths are mandatory when motion components are implemented.

## 6. Icon authority

### Android / Expo

Primary icon authority remains `expo-symbols`, using the Android Material Symbols mapping already present in the project.

Rules:
- one platform-native family,
- outlined / regular appearance by default,
- filled state only for selected navigation, active state or semantic emphasis,
- consistent optical size,
- no emoji icons,
- no Unicode decorative fallback in final shipped Build16 UI,
- no second icon library mixed into the same Android surface,
- no hand-drawn SVG icons for routine UI actions.

The current text fallbacks in `Glyph` are temporary compatibility behavior and must be removed or replaced during the icon migration batch.

### Laravel CMS

Target icon family: Phosphor.

Rules:
- one Phosphor family across the CMS,
- consistent weight / stroke,
- no mixed Lucide / Feather / random SVG visual language,
- no new hand-authored path data,
- no runtime third-party CDN dependency: icon assets must be bundled or vendored locally,
- existing `<x-icon>` remains compatibility-only until the dedicated migration batch.

## 7. Typography direction

Target visual direction is a characterful modern sans, with Geist / Geist Mono as the current preferred candidate.

Font files are not introduced by this foundation batch. Typography packaging and Android/web rendering must be verified before the family becomes runtime authority.

Data-heavy values should use tabular numerals where supported.

## 8. Android rules

- Android-native premium, not a phone-sized web page.
- 48dp minimum touch targets.
- Safe areas and system back behavior remain authoritative.
- Bottom navigation is for top-level peer destinations.
- Drill-down navigation keeps native spatial hierarchy.
- Sheets, dialogs, feedback and pressed states receive purposeful motion.
- Dense operational lists do not receive decorative stagger animation.

## 9. Laravel rules

- Keep Blade/CSS architecture.
- Prefer CSS transitions and isolated JS behavior.
- Do not add GSAP, Motion or a new frontend framework for ordinary CMS interaction.
- Use the same semantic color, radius and motion vocabulary as Android.
- Focus rings and keyboard usability are mandatory.

## 10. Build16 migration order

1. Foundation tokens and design contract.
2. Global primitives, icon foundation, button/input/card rules.
3. Mobile Home.
4. Laravel Home.
5. Mobile navigation, sheets, feedback and common list patterns.
6. CMS shell, navigation, tables, forms and feedback.
7. Business screens in controlled groups.
8. Accessibility, reduced motion, font-scale and dark-mode QA.
9. Final Android Build16 device certification.

This document is the Build16 design authority unless a later explicit report supersedes it.
