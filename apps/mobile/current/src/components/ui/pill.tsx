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
  | 'dangerSolid'
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

  // Red background badge: dedicated fill and label keys (white / page-dark, >= 4.5:1).
  dangerSolid: {
    background: '$dangerFill',
    text: '$onDangerFill',
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
      // Never narrower than its word and never wider than the row: the label ellipsizes instead of breaking.
      flexShrink={0}
      maxWidth="100%"
      paddingHorizontal={spacing.md}
      paddingVertical={6}
      borderRadius={radii.pill}
      backgroundColor={palette.background}
      alignItems="center"
      transition="100ms"
    >
      <Text
        numberOfLines={2}
        ellipsizeMode="tail"
        flexShrink={1}
        fontSize={typography.small.fontSize}
        lineHeight={typography.small.lineHeight}
        fontWeight="700"
        letterSpacing={0.24}
        color={palette.text}
      >
        {children}
      </Text>
    </XStack>
  );
}
