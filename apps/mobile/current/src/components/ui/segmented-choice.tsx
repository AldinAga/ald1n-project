import { Button as TamaguiButton, Text, XStack } from 'tamagui';

import { radii, spacing, typography } from '@/constants/theme';

export type SegmentedChoiceOption = { value: string; label: string };

export function SegmentedChoice({
  value,
  options,
  onChange,
  accessibilityLabel,
}: {
  value: string;
  options: readonly SegmentedChoiceOption[];
  onChange: (value: string) => void;
  accessibilityLabel: string;
}) {
  return (
    <XStack
      accessibilityLabel={accessibilityLabel}
      accessibilityRole="radiogroup"
      gap={4}
      padding={4}
      borderRadius={radii.pill}
      borderWidth={1}
      borderColor="$line"
      backgroundColor="$surfaceContainer"
    >
      {options.map((option) => {
        const selected = option.value === value;
        return (
          <TamaguiButton
            unstyled
            key={option.value}
            accessibilityRole="radio"
            accessibilityState={{ checked: selected }}
            onPress={() => onChange(option.value)}
            flex={1}
            minWidth={0}
            minHeight={44}
            paddingHorizontal={spacing.sm}
            borderRadius={radii.pill}
            borderWidth={1}
            borderColor={selected ? '$brand' : 'transparent'}
            backgroundColor={selected ? '$brandContainer' : 'transparent'}
            alignItems="center"
            justifyContent="center"
            transition="quickLessBouncy"
            pressStyle={{ scale: 0.97, opacity: 0.9 }}
          >
            <Text
              numberOfLines={1}
              fontSize={typography.small.fontSize}
              lineHeight={typography.small.lineHeight}
              fontWeight={selected ? '900' : '700'}
              color={selected ? '$onBrandContainer' : '$textMuted'}
            >
              {option.label}
            </Text>
          </TamaguiButton>
        );
      })}
    </XStack>
  );
}
