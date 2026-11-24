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

const kobaltScale = kobalt.scale;

export const spacing = ald1nDesignTokens.spacing;
// Kobalt radii (12 / 16 / 22 / pill). The legacy keys are kept so existing screens pick up the new scale.
export const radii = {
  sm: kobaltScale.radius.sm,
  md: kobaltScale.radius.md,
  lg: kobaltScale.radius.lg,
  xl: kobaltScale.radius.lg,
  xxl: kobaltScale.radius.lg,
  pill: kobaltScale.radius.pill,
} as const;
export const motion = ald1nDesignTokens.motion;
// Kobalt control height (48) and the 44 dp minimum touch target.
export const controlHeight = kobaltScale.size.control;
export const touchTarget = kobaltScale.size.touchTarget;
export const focusRing = kobaltScale.focus;

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

// Kobalt type: weights snap to 100-steps (display 780 -> 800, label 620 -> 600, bold 700); tracking is em * size.
export const typography = {
  hero: {
    fontSize: 36,
    lineHeight: 41,
    fontWeight: '800' as const,
    letterSpacing: -1,
  },

  h1: {
    fontSize: 30,
    lineHeight: 36,
    fontWeight: '800' as const,
    letterSpacing: -0.6,
  },

  h2: {
    fontSize: 22,
    lineHeight: 28,
    fontWeight: '700' as const,
    letterSpacing: -0.3,
  },

  h3: {
    fontSize: 18,
    lineHeight: 23,
    fontWeight: '700' as const,
    letterSpacing: -0.2,
  },

  body: {
    fontSize: 15,
    lineHeight: 22,
    fontWeight: '400' as const,
  },

  label: {
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600' as const,
    letterSpacing: 0.26,
  },

  small: {
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '600' as const,
  },
};
