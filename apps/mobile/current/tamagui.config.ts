import {
  createV5Theme,
  defaultConfig,
} from '@tamagui/config/v5';
import { animations } from '@tamagui/config/v5-reanimated';
import { createTamagui } from 'tamagui';

import {
  ald1nDarkPalette,
  ald1nDesignTokens,
  ald1nLightPalette,
} from './src/design/ald1n-tokens.generated';

const themes = createV5Theme({
  lightPalette: [...ald1nLightPalette],
  darkPalette: [...ald1nDarkPalette],

  componentThemes: false,

  getTheme: ({ scheme }) => {
    const colorScheme: 'light' | 'dark' =
      scheme === 'dark' ? 'dark' : 'light';

    const tokens = ald1nDesignTokens[colorScheme];

    return {
      brand: tokens.primary,
      brandStrong: tokens.primaryStrong,
      brandContainer: tokens.primaryContainer,
      onBrandContainer: tokens.onPrimaryContainer,

      surface: tokens.surface,
      surfaceMuted: tokens.surfaceMuted,
      surfaceContainer: tokens.surfaceContainer,
      surfaceContainerHigh: tokens.surfaceContainerHigh,

      textMuted: tokens.muted,
      line: tokens.border,
      outlineBrand: tokens.outline,

      accent: tokens.accent,
      success: tokens.success,
      warning: tokens.warning,
      danger: tokens.danger,
      info: tokens.info,
    };
  },
});

export const tamaguiConfig = createTamagui({
  ...defaultConfig,
  animations,
  themes,
});

export default tamaguiConfig;

export type TamaguiConfig = typeof tamaguiConfig;

declare module 'tamagui' {
  interface TamaguiCustomConfig extends TamaguiConfig {}
}
