// Tamagui 12-step palettes on the Kobalt hues (replaces the legacy orange palettes).
// Author: Ald1n <https://ald1n.com>
// Steps run background -> foreground. Anchors are Kobalt token values: page, field, solid, primary, link, heading, text.

export const kobaltLightPalette = [
  '#ffffff', // surface.solid
  '#f4f6f9', // image.stage
  '#edf0f4', // surface.page
  '#e8ecf1', // image.stageEnd
  '#d6def3',
  '#b4c3e8',
  '#8da3dc',
  '#2d50bf', // button.primary.bg
  '#2546ad', // accent.link
  '#1f3f99', // backToTop.bg
  '#33404c', // text.secondary
  '#121b24', // text.primary
] as const;

export const kobaltDarkPalette = [
  '#07090c', // image.lightboxBg
  '#0d1216', // surface.page
  '#111820', // surface.field
  '#182026', // surface.solid
  '#212c36',
  '#2f3c4a',
  '#3a4a5c',
  '#3557c7', // button.primary.bg
  '#4466d6', // backToTop.hover
  '#a9c3ff', // accent.link
  '#c8d2d9', // text.secondary
  '#eef2f5', // text.primary
] as const;
