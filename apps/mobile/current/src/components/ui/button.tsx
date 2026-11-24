import type {
  ComponentProps,
  PropsWithChildren,
} from 'react';
import { Button as TamaguiButton, Spinner, Text } from 'tamagui';
import {
  controlHeight,
  focusRing,
  radii,
  shadowThemes,
  spacing,
  typography,
} from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';

type TamaguiButtonProps =
  ComponentProps<typeof TamaguiButton>;

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger';

const palettes = {
  primary: {
    background: '$brand',
    border: '$brand',
    text: '$onBrand',
  },
  secondary: {
    background: '$brandContainer',
    border: '$brandContainer',
    text: '$onBrandContainer',
  },
  ghost: {
    background: '$surfaceContainer',
    border: '$line',
    text: '$text',
  },
  // Solid red with its own label colour; `$danger` is a dark text tone and is not a fill.
  danger: {
    background: '$dangerFill',
    border: '$dangerFill',
    text: '$onDangerFill',
  },
} as const;

export function Button({
  children,
  onPress,
  loading = false,
  disabled = false,
  variant = 'primary',
  style,
  accessibilityLabel,
}: PropsWithChildren<{
  onPress?: TamaguiButtonProps['onPress'];
  loading?: boolean;
  disabled?: TamaguiButtonProps['disabled'];
  variant?: Variant;
  style?: TamaguiButtonProps['style'];
  accessibilityLabel?: string;
}>) {
  const palette = palettes[variant];
  const { scheme } = useAppTheme();
  // MOBILE_GLOBAL_REPEATABLE_ACTIONS_V07
  // Loading is a transient busy indicator. Native disabled state is reserved for true structural unavailability.
  const isDisabled = Boolean(disabled);
  // MOBILE_GLOBAL_UNRESTRICTED_TAPS_V07
  // Busy/loading remains visual state only. Every tap is forwarded to the caller; business idempotency belongs in the action layer.
  const pressHandler = onPress;

  return (
    <TamaguiButton
      unstyled
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel}
      accessibilityState={{ disabled: isDisabled, busy: loading }}
      disabled={isDisabled}
      onPress={pressHandler}
      minHeight={controlHeight}
      paddingHorizontal={spacing.xl}
      borderRadius={radii.md}
      borderWidth={1}
      borderColor={palette.border}
      backgroundColor={palette.background}
      alignItems="center"
      justifyContent="center"
      opacity={isDisabled ? 0.48 : 1}
      transition="quickLessBouncy"
      pressStyle={{
        scale: 0.975,
        opacity: 0.94,
      }}
      focusStyle={{
        borderColor: '$focusRing',
        borderWidth: focusRing.width,
      }}
      style={[variant === 'primary' && !isDisabled ? { boxShadow: shadowThemes[scheme].button } : null, style]}
    >
      {loading ? (
        <Spinner color={palette.text} />
      ) : (
        <Text
          numberOfLines={1}
          ellipsizeMode="tail"
          flexShrink={1}
          fontSize={typography.label.fontSize}
          lineHeight={typography.label.lineHeight}
          fontWeight="700"
          letterSpacing={0.07}
          color={palette.text}
          textAlign="center"
        >
          {children}
        </Text>
      )}
    </TamaguiButton>
  );
}
