import type {
  ComponentProps,
  PropsWithChildren,
} from 'react';
import { YStack } from 'tamagui';

import {
  radii,
  shadowThemes,
  spacing,
} from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';

type TamaguiCardProps =
  ComponentProps<typeof YStack>;

export type CardProps = PropsWithChildren<{
  style?: TamaguiCardProps['style'];
  muted?: boolean;
  /** Translucent Kobalt glass panel instead of the solid surface. */
  glass?: boolean;
}>;

export function Card({
  children,
  style,
  muted = false,
  glass = false,
}: CardProps) {
  const { scheme } = useAppTheme();
  return (
    <YStack
      backgroundColor={
        muted ? '$surfaceContainer' : glass ? '$glass' : '$surface'
      }
      borderRadius={radii.lg}
      padding={spacing.lg}
      borderWidth={1}
      borderColor="$line"
      transition="200ms"
      style={[muted ? null : { boxShadow: shadowThemes[scheme].card }, style]}
    >
      {children}
    </YStack>
  );
}
