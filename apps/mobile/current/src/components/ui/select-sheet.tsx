import { useState } from 'react';
import { Modal, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';

export type SelectSheetOption = {
  value: string;
  label: string;
  detail?: string;
  disabled?: boolean;
};

type SelectSheetProps = {
  label: string;
  value: string;
  options: SelectSheetOption[];
  onChange: (value: string) => void;
  placeholder?: string;
  error?: string;
  disabled?: boolean;
};

export function SelectSheet({
  label,
  value,
  options,
  onChange,
  placeholder = 'Izaberi',
  error,
  disabled = false,
}: SelectSheetProps) {
  const [open, setOpen] = useState(false);
  const { colors } = useAppTheme();
  const styles = createStyles(colors);
  const selected = options.find((option) => option.value === value);

  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <Pressable
        accessibilityRole="button"
        accessibilityState={{ disabled, expanded: open }}
        disabled={disabled}
        onPress={() => setOpen(true)}
        style={({ pressed }) => [
          styles.trigger,
          error ? styles.triggerError : null,
          disabled ? styles.disabled : null,
          pressed && !disabled ? styles.pressed : null,
        ]}
      >
        <Text style={selected ? styles.value : styles.placeholder} numberOfLines={1}>
          {selected?.label ?? placeholder}
        </Text>
        <Text style={styles.chevron}>⌄</Text>
      </Pressable>
      {error ? <Text style={styles.error}>{error}</Text> : null}

      <Modal
        animationType="slide"
        transparent
        visible={open}
        onRequestClose={() => setOpen(false)}
      >
        <View style={styles.modalRoot}>
          <Pressable style={styles.backdrop} onPress={() => setOpen(false)} />
          <View style={styles.sheet}>
            <View style={styles.handle} />
            <View style={styles.sheetHeader}>
              <Text style={styles.sheetTitle}>{label}</Text>
              <Pressable accessibilityRole="button" onPress={() => setOpen(false)}>
                <Text style={styles.close}>Zatvori</Text>
              </Pressable>
            </View>
            <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.options}>
              {options.map((option) => {
                const active = option.value === value;
                return (
                  <Pressable
                    key={option.value}
                    accessibilityRole="button"
                    accessibilityState={{ selected: active, disabled: option.disabled }}
                    disabled={option.disabled}
                    onPress={() => {
                      onChange(option.value);
                      setOpen(false);
                    }}
                    style={({ pressed }) => [
                      styles.option,
                      active ? styles.optionActive : null,
                      option.disabled ? styles.disabled : null,
                      pressed && !option.disabled ? styles.pressed : null,
                    ]}
                  >
                    <View style={styles.optionCopy}>
                      <Text style={active ? styles.optionLabelActive : styles.optionLabel}>{option.label}</Text>
                      {option.detail ? <Text style={styles.optionDetail}>{option.detail}</Text> : null}
                    </View>
                    {active ? <Text style={styles.check}>✓</Text> : null}
                  </Pressable>
                );
              })}
            </ScrollView>
          </View>
        </View>
      </Modal>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    field: { gap: spacing.sm },
    label: { ...typography.label, color: theme.ink, paddingHorizontal: spacing.xs },
    trigger: {
      minHeight: 56,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.xl,
      backgroundColor: theme.surfaceContainer,
      paddingHorizontal: spacing.lg,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    triggerError: { borderColor: theme.danger },
    value: { ...typography.body, color: theme.ink, flex: 1 },
    placeholder: { ...typography.body, color: theme.muted, flex: 1 },
    chevron: { ...typography.h3, color: theme.muted },
    error: { ...typography.small, color: theme.danger, paddingHorizontal: spacing.xs },
    modalRoot: { flex: 1, justifyContent: 'flex-end' },
    backdrop: { position: 'absolute', top: 0, right: 0, bottom: 0, left: 0, backgroundColor: 'rgba(0,0,0,0.45)' },
    sheet: {
      maxHeight: '78%',
      backgroundColor: theme.surface,
      borderTopLeftRadius: 28,
      borderTopRightRadius: 28,
      paddingHorizontal: spacing.lg,
      paddingBottom: spacing.xxl,
    },
    handle: {
      width: 48,
      height: 5,
      borderRadius: radii.pill,
      backgroundColor: theme.line,
      alignSelf: 'center',
      marginTop: spacing.sm,
      marginBottom: spacing.lg,
    },
    sheetHeader: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
      marginBottom: spacing.md,
    },
    sheetTitle: { ...typography.h2, color: theme.ink, flex: 1 },
    close: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    options: { gap: spacing.sm, paddingBottom: spacing.xl },
    option: {
      minHeight: 58,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      paddingHorizontal: spacing.lg,
      paddingVertical: spacing.md,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md,
      backgroundColor: theme.surfaceContainer,
    },
    optionActive: { borderColor: theme.primary, backgroundColor: theme.primarySoft },
    optionCopy: { flex: 1 },
    optionLabel: { ...typography.label, color: theme.ink },
    optionLabelActive: { ...typography.label, color: theme.primary },
    optionDetail: { ...typography.small, color: theme.muted, marginTop: 2 },
    check: { ...typography.h3, color: theme.primary },
    disabled: { opacity: 0.45 },
    pressed: { opacity: 0.82 },
  });
}
