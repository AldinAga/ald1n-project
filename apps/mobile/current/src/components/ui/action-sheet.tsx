/**
 * LG-04 Wave 3: polished, readable action sheet.
 *
 * This component also backs destructive confirmations: never animate the
 * action, trigger it automatically, substitute permissions or add transparent
 * content over warnings. Only the native Modal entrance is motion-aware.
 */
import { Modal, Pressable, StyleSheet, Text, View } from 'react-native';

import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';
import { useSheetAnimationType } from './sheet-motion-policy';

export type SheetActionTone = 'default' | 'danger';
export type SheetAction = {
  key: string;
  label: string;
  description?: string;
  tone?: SheetActionTone;
  disabled?: boolean;
};

type Props = {
  visible: boolean;
  title: string;
  message?: string;
  actions: readonly SheetAction[];
  onSelect: (key: string) => void;
  onClose: () => void;
};

export function ActionSheet({ visible, title, message, actions, onSelect, onClose }: Props) {
  const { colors: theme } = useAppTheme();
  const styles = createStyles(theme);
  const animationType = useSheetAnimationType();

  return (
    <Modal
      animationType={animationType}
      onRequestClose={onClose}
      transparent
      visible={visible}
    >
      <View style={styles.root}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Zatvori akcije"
          onPress={onClose}
          style={StyleSheet.absoluteFill}
        />
        <View accessibilityViewIsModal style={styles.sheet}>
          <View style={styles.handle} />
          <Text style={styles.title}>{title}</Text>
          {message ? <Text style={styles.message}>{message}</Text> : null}
          <View style={styles.actions}>
            {actions.map((action) => {
              const danger = action.tone === 'danger';
              return (
                <Pressable
                  key={action.key}
                  accessibilityRole="button"
                  accessibilityLabel={action.label}
                  accessibilityState={{ disabled: action.disabled ?? false }}
                  disabled={action.disabled ?? false}
                  onPress={() => onSelect(action.key)}
                  style={({ pressed }) => [
                    styles.action,
                    danger ? styles.dangerBorder : null,
                    action.disabled ? styles.disabled : null,
                    pressed ? styles.pressed : null,
                  ]}
                >
                  <Text style={[styles.actionLabel, danger ? styles.dangerText : null]}>
                    {action.label}
                  </Text>
                  {action.description ? (
                    <Text style={styles.description}>{action.description}</Text>
                  ) : null}
                </Pressable>
              );
            })}
          </View>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Zatvori"
            onPress={onClose}
            style={({ pressed }) => [styles.close, pressed ? styles.pressed : null]}
          >
            <Text style={styles.closeText}>Zatvori</Text>
          </Pressable>
        </View>
      </View>
    </Modal>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    root: {
      flex: 1,
      justifyContent: 'flex-end',
      backgroundColor: 'rgba(0,0,0,0.34)',
    },
    sheet: {
      gap: spacing.md,
      borderTopLeftRadius: 24,
      borderTopRightRadius: 24,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderColor: theme.outline,
      backgroundColor: theme.surface,
      paddingHorizontal: spacing.lg,
      paddingTop: spacing.sm,
      paddingBottom: spacing.xl,
    },
    handle: {
      width: 48,
      height: 5,
      alignSelf: 'center',
      borderRadius: 999,
      backgroundColor: theme.primarySoft,
    },
    title: { ...typography.h3, color: theme.ink },
    message: { ...typography.body, color: theme.muted },
    actions: { gap: spacing.sm },
    action: {
      minHeight: 56,
      justifyContent: 'center',
      gap: 2,
      borderRadius: 16,
      borderWidth: 1,
      borderColor: theme.line,
      backgroundColor: theme.surfaceContainer,
      paddingHorizontal: spacing.md,
      paddingVertical: spacing.sm,
    },
    dangerBorder: { borderColor: theme.danger },
    actionLabel: { ...typography.label, color: theme.ink },
    dangerText: { color: theme.danger },
    description: { ...typography.small, color: theme.muted },
    close: {
      minHeight: 48,
      alignItems: 'center',
      justifyContent: 'center',
    },
    closeText: { ...typography.label, color: theme.primary },
    disabled: { opacity: 0.45 },
    pressed: { opacity: 0.82 },
  });
}
