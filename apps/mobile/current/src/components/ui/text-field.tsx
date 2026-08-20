import {
  forwardRef,
  type ComponentProps,
  type ComponentRef,
} from 'react';
import { Input, Text, YStack } from 'tamagui';

import { radii, spacing, typography } from '@/constants/theme';

type TamaguiInputProps = ComponentProps<typeof Input>;

export type TextFieldProps = Omit<TamaguiInputProps, 'ref'> & {
  label: string;
  error?: string;
};

type TextFieldRef = ComponentRef<typeof Input>;

export const TextField = forwardRef<TextFieldRef, TextFieldProps>(
  function TextField(
    {
      label,
      error,
      style,
      placeholderTextColor = '$placeholderColor',
      selectionColor = '$brand',
      ...inputProps
    },
    ref,
  ) {
    return (
      <YStack gap={spacing.sm}>
        <Text
          paddingHorizontal={spacing.xs}
          fontSize={typography.label.fontSize}
          lineHeight={typography.label.lineHeight}
          fontWeight="800"
          color="$color"
        >
          {label}
        </Text>

        <Input
          unstyled
          ref={ref}
          minHeight={56}
          borderWidth={1}
          borderColor={error ? '$danger' : '$line'}
          borderRadius={radii.xl}
          backgroundColor="$surfaceContainer"
          color="$color"
          paddingHorizontal={spacing.lg}
          fontSize={16}
          transition="100ms"
          focusStyle={{
            borderColor: error ? '$danger' : '$brand',
            backgroundColor: '$surface',
          }}
          style={style}
          {...inputProps}
          placeholderTextColor={placeholderTextColor}
          selectionColor={selectionColor}
        />

        {error ? (
          <Text
            paddingHorizontal={spacing.xs}
            fontSize={typography.small.fontSize}
            lineHeight={typography.small.lineHeight}
            fontWeight="600"
            color="$danger"
          >
            {error}
          </Text>
        ) : null}
      </YStack>
    );
  },
);

TextField.displayName = 'TextField';
