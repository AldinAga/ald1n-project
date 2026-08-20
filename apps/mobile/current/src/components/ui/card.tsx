import type {
  ComponentProps,
  PropsWithChildren,
} from 'react';
import { YStack } from 'tamagui';

import {
  radii,
  spacing,
} from '@/constants/theme';

type TamaguiCardProps =
  ComponentProps<typeof YStack>;

export type CardProps = PropsWithChildren<{
  style?: TamaguiCardProps['style'];
  muted?: boolean;
}>;

export function Card({
  children,
  style,
  muted = false,
}: CardProps) {
  return (
    <YStack
      backgroundColor={
        muted ? '$surfaceContainer' : '$surface'
      }
      borderRadius={radii.xl}
      padding={spacing.lg}
      transition="200ms"
      boxShadow={
        muted
          ? undefined
          : '0px 10px 22px $shadow1'
      }
      style={style}
    >
      {children}
    </YStack>
  );
}
