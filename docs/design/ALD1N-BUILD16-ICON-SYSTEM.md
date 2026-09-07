# Ald1n Build16 Icon System

Status: CANONICAL MIGRATION CONTRACT

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

- Target family: Phosphor.
- Delivery: local bundled assets only; no CDN runtime dependency.
- Target weight: regular, with a single consistent family across navigation and operational actions.
- Existing x-icon semantic names remain the compatibility contract during migration.
- The JSON registry in packages/icon-system/ald1n-icons.json maps those semantic names to Phosphor targets.
- Actual SVG vendoring and x-icon runtime switch are intentionally deferred to the dedicated migration batch so every icon can be visually verified together.

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
