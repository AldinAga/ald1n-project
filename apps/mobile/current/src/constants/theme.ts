import { Platform } from 'react-native';

import {
  ald1nDesignTokens,
  type Ald1nColorScheme,
} from '@/design/ald1n-tokens.generated';

type DesignColorTokens =
  (typeof ald1nDesignTokens)[Ald1nColorScheme];

function createColors(tokens: DesignColorTokens) {
  return {
    background: tokens.background,

    surface: tokens.surface,
    surfaceMuted: tokens.surfaceMuted,
    surfaceContainer: tokens.surfaceContainer,
    surfaceContainerHigh: tokens.surfaceContainerHigh,

    ink: tokens.text,
    muted: tokens.muted,
    line: tokens.border,
    outline: tokens.outline,

    primary: tokens.primary,
    onPrimary: tokens.onPrimary,
    primaryDark: tokens.primaryStrong,
    primarySoft: tokens.primaryContainer,
    primaryContainer: tokens.primaryContainer,
    onPrimaryContainer: tokens.onPrimaryContainer,

    secondaryContainer: tokens.secondaryContainer,

    accent: tokens.accent,
    accentSoft: tokens.accentSoft,

    success: tokens.success,
    successSoft: tokens.successSoft,

    warning: tokens.warning,
    warningSoft: tokens.warningSoft,

    danger: tokens.danger,
    onDanger: tokens.onDanger,
    dangerSoft: tokens.dangerSoft,

    info: tokens.info,
    infoSoft: tokens.infoSoft,

    white: tokens.white,
    black: tokens.black,

    hero: tokens.hero,
    heroMuted: tokens.heroMuted,
  } as const;
}

export const colorThemes = {
  light: createColors(ald1nDesignTokens.light),
  dark: createColors(ald1nDesignTokens.dark),
} as const;

export type AppColorScheme = keyof typeof colorThemes;
export type AppColors = ReturnType<typeof createColors>;

/*
 * Transitional compatibility alias.
 *
 * Postojeci RN ekran(i) jos uvek koriste staticki colors.*
 * i zato ostaju light dok ih kontrolisano ne migriramo.
 *
 * Kada poslednji staticki consumer nestane, ovaj alias se brise.
 */
export const colors: AppColors = colorThemes.light;

export const spacing = ald1nDesignTokens.spacing;
export const radii = ald1nDesignTokens.radii;

/*
 * RN-specific shadow.
 *
 * Ovo je namerno RN adapter. Trenutno ga koristi login.tsx,
 * koji renderuje React Native View/StyleSheet komponente.
 */
export const shadow = Platform.select({
  ios: {
    shadowColor: colors.black,
    shadowOpacity: 0.07,
    shadowRadius: 22,
    shadowOffset: {
      width: 0,
      height: 10,
    },
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
