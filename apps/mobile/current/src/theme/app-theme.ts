import { useMemo } from 'react';
import { useColorScheme, type ColorSchemeName } from 'react-native';

import { colorThemes, type AppColors, type AppColorScheme } from '@/constants/theme';
import { useAppPreferences, type AppThemeMode } from '@/features/preferences/app-preferences';

export type { AppThemeMode } from '@/features/preferences/app-preferences';
export const appThemeMode: AppThemeMode = 'system';

export function resolveAppColorScheme(preferredScheme: ColorSchemeName): AppColorScheme {
  return preferredScheme === 'dark' ? 'dark' : 'light';
}

export function resolveAppThemeMode(
  mode: AppThemeMode,
  preferredScheme: ColorSchemeName,
): AppColorScheme {
  return mode === 'system' ? resolveAppColorScheme(preferredScheme) : mode;
}

export function getAppColors(scheme: AppColorScheme): AppColors {
  return colorThemes[scheme];
}

export function useAppTheme() {
  const preferredScheme = useColorScheme();
  const { themeMode } = useAppPreferences();
  const scheme = resolveAppThemeMode(themeMode ?? appThemeMode, preferredScheme);
  const colors = colorThemes[scheme];
  return useMemo(() => ({ scheme, colors, isDark: scheme === 'dark' }) as const, [scheme, colors]);
}

export function useThemedStyles<T> (factory: (colors: AppColors) => T): T {
  const { colors } = useAppTheme();
  return useMemo(() => factory(colors), [factory, colors]);
}
