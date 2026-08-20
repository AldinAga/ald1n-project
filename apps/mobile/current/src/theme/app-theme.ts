import { useMemo } from 'react';
import {
  useColorScheme,
  type ColorSchemeName,
} from 'react-native';

import {
  colorThemes,
  type AppColors,
  type AppColorScheme,
} from '@/constants/theme';

export type AppThemeMode =
  | AppColorScheme
  | 'system';

/*
 * Dark mode ostaje namerno zakljucan dok svi RN
 * consumers ne budu theme-aware.
 *
 * Finalni activation korak menja samo:
 *
 *   'light' -> 'system'
 *
 * zajedno sa TamaguiProvider, StatusBar i app.config.js.
 */
export const appThemeMode: AppThemeMode = 'system';

export function resolveAppColorScheme(
  preferredScheme: ColorSchemeName,
): AppColorScheme {
  return preferredScheme === 'dark'
    ? 'dark'
    : 'light';
}

export function resolveAppThemeMode(
  mode: AppThemeMode,
  preferredScheme: ColorSchemeName,
): AppColorScheme {
  return mode === 'system'
    ? resolveAppColorScheme(preferredScheme)
    : mode;
}

export function getAppColors(
  scheme: AppColorScheme,
): AppColors {
  return colorThemes[scheme];
}

export function useAppTheme() {
  const preferredScheme = useColorScheme();

  const scheme = resolveAppThemeMode(
    appThemeMode,
    preferredScheme,
  );

  const colors = colorThemes[scheme];

  return useMemo(
    () => ({
      scheme,
      colors,
      isDark: scheme === 'dark',
    }) as const,
    [scheme, colors],
  );
}

export function useThemedStyles<T>(
  factory: (colors: AppColors) => T,
): T {
  const { colors } = useAppTheme();

  return useMemo(
    () => factory(colors),
    [factory, colors],
  );
}
