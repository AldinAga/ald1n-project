// Adapter: nested Kobalt tokens -> the flat AppColors keys the app already uses.
// Author: Ald1n <https://ald1n.com>
import { kobaltDark, kobaltLight } from './ald1n-tokens.kobalt.generated';

// Takes the literal palettes (not the widened KobaltPalette) so literal colour types reach AppColors.
export function createAppColors(k: typeof kobaltDark | typeof kobaltLight) {
  return {
    background: k.surface.page,

    surface: k.surface.solid,
    // Recessed wells share the page tone; containers are translucent tints over the surface.
    surfaceMuted: k.surface.page,
    surfaceContainer: k.button.ghost.bg,
    surfaceContainerHigh: k.button.ghost.hover,

    ink: k.text.primary,
    muted: k.text.tertiary,
    line: k.border.normal,
    outline: k.border.strong,

    primary: k.button.primary.bg,
    onPrimary: k.button.primary.on,
    primaryDark: k.accent.link,
    primarySoft: k.accent.soft,
    primaryContainer: k.accent.soft,
    onPrimaryContainer: k.accent.link,

    secondaryContainer: k.button.ghost.bg,

    accent: k.button.primary.bg,
    accentSoft: k.accent.soft,

    // Status .text variants: the .dot colours of ok, warning and info fail AA as text on a white surface.
    success: k.status.ok.text,
    successSoft: k.status.ok.bg,

    warning: k.status.warning.text,
    warningSoft: k.status.warning.bg,

    danger: k.status.danger.dot,
    // Dark dot is light pink, so its label must be dark; light dot takes the white label.
    onDanger: k === kobaltDark ? k.surface.page : k.button.primary.on,
    dangerSoft: k.status.danger.bg,

    info: k.status.info.text,
    infoSoft: k.status.info.bg,

    white: k.button.primary.on,
    black: k.image.lightboxBg,

    // Hero panels are dark in both themes.
    hero: kobaltDark.surface.page,
    heroMuted: kobaltDark.text.secondary,

    glassSoft: k.glass.soft,
    glass: k.glass.standard,
    glassDense: k.glass.dense,
    glassStrong: k.glass.strong,
    focusRing: k.focus.ring,
  } as const;
}
