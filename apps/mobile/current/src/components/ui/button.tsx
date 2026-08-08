import type { PropsWithChildren } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, type StyleProp, type ViewStyle } from 'react-native';
import { colors, radii, spacing, typography } from '@/constants/theme';

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger';

export function Button({ children, onPress, loading = false, disabled = false, variant = 'primary', style }:
  PropsWithChildren<{ onPress?: () => void; loading?: boolean; disabled?: boolean; variant?: Variant; style?: StyleProp<ViewStyle> }>) {
  const palette = variantStyles[variant];
  return (
    <Pressable
      accessibilityRole="button"
      disabled={disabled || loading}
      onPress={onPress}
      style={({ pressed }) => [styles.base, palette.container, pressed && styles.pressed, (disabled || loading) && styles.disabled, style]}
    >
      {loading ? <ActivityIndicator color={palette.text.color} /> : <Text style={[styles.label, palette.text]}>{children}</Text>}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  base: {
    minHeight: 56,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: spacing.xl,
    borderRadius: radii.pill,
    borderWidth: 1
  },
  label: typography.label,
  pressed: { transform: [{ scale: 0.98 }], opacity: 0.9 },
  disabled: { opacity: 0.48 }
});

const variantStyles = {
  primary: StyleSheet.create({ container: { backgroundColor: colors.primary, borderColor: colors.primary }, text: { color: colors.white } }),
  secondary: StyleSheet.create({ container: { backgroundColor: colors.primaryContainer, borderColor: colors.primaryContainer }, text: { color: colors.onPrimaryContainer } }),
  ghost: StyleSheet.create({ container: { backgroundColor: colors.surfaceContainer, borderColor: colors.surfaceContainer }, text: { color: colors.ink } }),
  danger: StyleSheet.create({ container: { backgroundColor: colors.dangerSoft, borderColor: colors.dangerSoft }, text: { color: colors.danger } })
};
