// Adapter: nested Kobalt tokens -> the flat AppColors keys the app already uses.
// Author: Ald1n <https://ald1n.com>
import { kobaltDark, kobaltLight } from './ald1n-tokens.kobalt.generated';

// Takes the literal palettes (not the widened KobaltPalette) so literal colour types reach AppColors.
export function createAppColors(k: typeof kobaltDark | typeof kobaltLight) {
  const dark = k === kobaltDark;
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

    // Dark: the primary fill #3557c7 is only 2.6:1 as text/icons on dark surfaces, so primary and accent use the link tone and the label turns dark.
    primary: dark ? k.accent.link : k.button.primary.bg,
    onPrimary: dark ? k.surface.page : k.button.primary.on,
    primaryDark: k.accent.link,
    primarySoft: k.accent.soft,
    primaryContainer: k.accent.soft,
    onPrimaryContainer: k.accent.link,

    secondaryContainer: k.button.ghost.bg,

    accent: dark ? k.accent.link : k.button.primary.bg,
    accentSoft: k.accent.soft,

    // Status .text variants: the .dot colours of ok, warning and info fail AA as text on a white surface.
    success: k.status.ok.text,
    successSoft: k.status.ok.bg,

    warning: k.status.warning.text,
    warningSoft: k.status.warning.bg,

    // .text variant: the red .dot is 4.3:1 on the light danger tint; the text tone passes everywhere.
    danger: k.status.danger.text,
    // Dark danger is light pink, so its label must be dark; light danger takes the white label.
    onDanger: dark ? k.surface.page : k.button.primary.on,
    dangerSoft: k.status.danger.bg,
    // Solid red surface for danger buttons and badges: the red .dot with white (light, 4.9:1) or page-dark (dark, 6:1) label.
    // `danger` stays the dark text tone, so it must not be used as a fill.
    dangerFill: k.status.danger.dot,
    onDangerFill: dark ? k.surface.page : k.button.primary.on,

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
