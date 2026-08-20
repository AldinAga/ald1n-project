import type {
  ComponentProps,
  PropsWithChildren,
} from 'react';
import { Button as TamaguiButton } from 'tamagui';

import { radii } from '@/constants/theme';

type TamaguiButtonProps =
  ComponentProps<typeof TamaguiButton>;

export type IconButtonProps = PropsWithChildren<{
  accessibilityLabel: string;
  onPress?: TamaguiButtonProps['onPress'];
  disabled?: TamaguiButtonProps['disabled'];
}>;

export function IconButton({
  children,
  accessibilityLabel,
  onPress,
  disabled = false,
}: IconButtonProps) {
  return (
    <TamaguiButton
      unstyled
      accessibilityLabel={accessibilityLabel}
      accessibilityRole="button"
      disabled={disabled}
      onPress={onPress}
      width={44}
      height={44}
      borderRadius={radii.lg}
      backgroundColor="$surface"
      borderWidth={1}
      borderColor="$line"
      alignItems="center"
      justifyContent="center"
      opacity={disabled ? 0.48 : 1}
      transition="quickLessBouncy"
      pressStyle={{
        scale: 0.94,
        backgroundColor: '$brandContainer',
        borderColor: '$brandContainer',
      }}
    >
      {children}
    </TamaguiButton>
  );
}
