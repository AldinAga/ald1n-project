import { forwardRef } from 'react';
import { StyleSheet, Text, TextInput, View, type TextInputProps } from 'react-native';
import { colors, radii, spacing, typography } from '@/constants/theme';

export const TextField = forwardRef<TextInput, TextInputProps & { label: string; error?: string }>(
  function TextField({ label, error, style, ...props }, ref) {
    return (
      <View style={styles.wrapper}>
        <Text style={styles.label}>{label}</Text>
        <TextInput
          ref={ref}
          placeholderTextColor={colors.muted}
          selectionColor={colors.primary}
          style={[styles.input, error && styles.inputError, style]}
          {...props}
        />
        {error ? <Text style={styles.error}>{error}</Text> : null}
      </View>
    );
  }
);

const styles = StyleSheet.create({
  wrapper: { gap: spacing.sm },
  label: { ...typography.label, color: colors.ink, paddingHorizontal: spacing.xs },
  input: {
    minHeight: 56,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.xl,
    backgroundColor: colors.surfaceContainer,
    color: colors.ink,
    paddingHorizontal: spacing.lg,
    fontSize: 16
  },
  inputError: { borderColor: colors.danger },
  error: { ...typography.small, color: colors.danger, paddingHorizontal: spacing.xs }
});
