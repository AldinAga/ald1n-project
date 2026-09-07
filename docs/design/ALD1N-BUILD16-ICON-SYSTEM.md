# Ald1n Build16 Icon System

Status: CANONICAL RUNTIME AUTHORITY

## 1. Principle

Ald1n uses one coherent icon family per product surface. Icons support recognition and hierarchy; they do not replace labels for important business actions.

## 2. Android / Expo

- Authority: Expo Symbols cross-platform API.
- Android rendering: Material Symbols.
- iOS rendering: SF Symbols.
- Weight: medium for the shared operational glyph layer.
- Text and emoji pseudo-icon fallbacks are forbidden.
- Every shared glyph must provide explicit iOS, Android and Web names.
- Filled variants are reserved for selected/current state or semantic confirmation; ordinary actions stay visually restrained.

## 3. Laravel CMS

- Runtime family: Phosphor.
- Delivery: local bundled SVG sprite only; no CDN runtime dependency.
- Runtime weight: regular across navigation and operational actions.
- Existing x-icon semantic names remain the compatibility API; callers do not depend on Phosphor file names.
- The JSON registry in packages/icon-system/ald1n-icons.json is the semantic map and bundle provenance authority.
- The runtime bundle is generated from pinned @phosphor-icons/core@2.1.1 MIT assets and committed as public/assets/icons/phosphor-regular.svg.
- Unknown semantic names fail soft to the locally bundled Phosphor circle glyph; new semantic names must be registered before use.

## 4. Taste rules adopted

- No emoji icons in product UI.
- No random hand-drawn SVG additions.
- No mixed icon families on the same surface.
- Do not use a pill merely because an icon is present.
- Icons use restrained neutral/brand color and become stronger only for active or semantic states.

## 5. Accessibility

- Icon-only controls require an accessible label.
- Important actions keep visible text.
- Touch targets remain at least 48dp on Android.
- Decorative icons remain hidden from the accessibility reading order when the surrounding control already provides the label.


## 6. Build16 Batch126 runtime switch

- Laravel runtime switch completed in Batch126 V5.
- Both global search surfaces (header toggle and search dialog field) now use the same x-icon compatibility API instead of separate inline SVG geometry.
- The archive semantic uses the canonical Phosphor raw asset name archive; the upstream archive-box catalog value is an alias rather than a raw SVG filename.
- The CMS shell uses Operator radii and restrained elevation while preserving the existing two-row navigation, permissions and mobile hamburger contract.
- The vendored MIT license is normalized to LF line endings with no trailing whitespace before staging.
