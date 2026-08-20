import type { ReactNode } from 'react';
import { Text, XStack } from 'tamagui';

import {
  radii,
  spacing,
  typography,
} from '@/constants/theme';

export type PillTone =
  | 'neutral'
  | 'primary'
  | 'success'
  | 'warning'
  | 'danger'
  | 'info';

const palettes = {
  neutral: {
    background: '$surfaceMuted',
    text: '$textMuted',
  },

  primary: {
    background: '$brandContainer',
    text: '$brandStrong',
  },

  success: {
    background: '$successContainer',
    text: '$success',
  },

  warning: {
    background: '$warningContainer',
    text: '$warning',
  },

  danger: {
    background: '$dangerContainer',
    text: '$danger',
  },

  info: {
    background: '$infoContainer',
    text: '$info',
  },
} as const;

export function Pill({
  children,
  tone = 'neutral',
}: {
  children: ReactNode;
  tone?: PillTone;
}) {
  const palette = palettes[tone];

  return (
    <XStack
      alignSelf="flex-start"
      paddingHorizontal={spacing.md}
      paddingVertical={6}
      borderRadius={radii.pill}
      backgroundColor={palette.background}
      alignItems="center"
      transition="100ms"
    >
      <Text
        fontSize={typography.small.fontSize}
        lineHeight={typography.small.lineHeight}
        fontWeight="700"
        color={palette.text}
      >
        {children}
      </Text>
    </XStack>
  );
}
