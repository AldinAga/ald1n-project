import { Platform } from 'react-native';

import { ald1nDesignTokens } from '@/design/ald1n-tokens.generated';
import { kobalt } from '@/design/ald1n-tokens.kobalt.generated';
import { createAppColors } from '@/design/kobalt-app-colors';

// Colours come from the Kobalt tokens through the adapter; spacing, radii and motion stay on the legacy tokens.
export const colorThemes = {
  light: createAppColors(kobalt.light),
  dark: createAppColors(kobalt.dark),
} as const;

export const shadowThemes = {
  light: kobalt.light.shadow,
  dark: kobalt.dark.shadow,
} as const;

export type AppColorScheme = keyof typeof colorThemes;
export type AppColors = (typeof colorThemes)[AppColorScheme];

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
export const motion = ald1nDesignTokens.motion;

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
