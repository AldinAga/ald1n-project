import { Platform } from 'react-native';

import { ald1nDesignTokens } from '@/design/ald1n-tokens.generated';

const light = ald1nDesignTokens.light;

export const colors = {
  background: light.background,
  surface: light.surface,
  surfaceMuted: light.surfaceMuted,
  surfaceContainer: light.surfaceContainer,
  surfaceContainerHigh: light.surfaceContainerHigh,

  ink: light.text,
  muted: light.muted,
  line: light.border,
  outline: light.outline,

  primary: light.primary,
  primaryDark: light.primaryStrong,
  primarySoft: light.primaryContainer,
  primaryContainer: light.primaryContainer,
  onPrimaryContainer: light.onPrimaryContainer,

  secondaryContainer: light.secondaryContainer,

  accent: light.accent,
  accentSoft: light.accentSoft,

  success: light.success,
  successSoft: light.successSoft,

  warning: light.warning,
  warningSoft: light.warningSoft,

  danger: light.danger,
  dangerSoft: light.dangerSoft,

  info: light.info,
  infoSoft: light.infoSoft,

  white: light.white,
  black: light.black,

  hero: light.hero,
  heroMuted: light.heroMuted,
} as const;

export const spacing = ald1nDesignTokens.spacing;
export const radii = ald1nDesignTokens.radii;

export const shadow = Platform.select({
  ios: {
    shadowColor: colors.black,
    shadowOpacity: 0.07,
    shadowRadius: 22,
    shadowOffset: { width: 0, height: 10 },
  },

  android: {
    elevation: 2,
  },

  default: {},
});

export const typography = {
  hero: {
    fontSize: 36,
    lineHeight: 41,
    fontWeight: '900' as const,
    letterSpacing: -0.6,
  },

  h1: {
    fontSize: 30,
    lineHeight: 36,
    fontWeight: '900' as const,
    letterSpacing: -0.4,
  },

  h2: {
    fontSize: 22,
    lineHeight: 28,
    fontWeight: '800' as const,
    letterSpacing: -0.2,
  },

  h3: {
    fontSize: 18,
    lineHeight: 23,
    fontWeight: '800' as const,
  },

  body: {
    fontSize: 15,
    lineHeight: 22,
    fontWeight: '400' as const,
  },

  label: {
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '800' as const,
  },

  small: {
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '600' as const,
  },
};
