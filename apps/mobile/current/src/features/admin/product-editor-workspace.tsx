// MOBILE_BATCH680_PRODUCT_EDITOR_UX2_WORKSPACES
// Hidden sections remain mounted; switching workspaces never resets unsaved field state.
import { useEffect, useMemo, useState } from 'react';
import { Keyboard, Pressable, ScrollView, StyleSheet, Text, View, useWindowDimensions } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Button } from '@/components/ui/button';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';

export type ProductEditorWorkspace = 'identity' | 'specifications' | 'commercial' | 'description' | 'images' | 'advanced';

const WORKSPACES: ReadonlyArray<{ key: ProductEditorWorkspace; title: string }> = [
  { key: 'identity', title: 'Osnovno' },
  { key: 'specifications', title: 'Specifikacije' },
  { key: 'commercial', title: 'Cena i lager' },
  { key: 'description', title: 'Opis' },
  { key: 'images', title: 'Fotografije' },
  { key: 'advanced', title: 'Napredno' },
];

export function productEditorWorkspaceForError(field: string): ProductEditorWorkspace {
  if (field.startsWith('spec')) return 'specifications';
  if (['price_amount', 'price_currency', 'currency', 'purchase_price_rsd', 'manual_commission_eur', 'stock_quantity', 'low_stock_threshold', 'status'].includes(field)) return 'commercial';
  if (field === 'description' || field === 'notes') return 'description';
  if (field.startsWith('images') || field.startsWith('image')) return 'images';
  return 'identity';
}

export function ProductEditorWorkspaceNav({
  active,
  onChange,
  hasSpecifications,
  showAdvanced = false,
}: {
  active: ProductEditorWorkspace;
  onChange: (workspace: ProductEditorWorkspace) => void;
  hasSpecifications: boolean;
  showAdvanced?: boolean;
}) {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);

  return (
    <View style={styles.nav}>
      <Text style={styles.navHeading}>Radne celine · uneti podaci ostaju u formi</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} keyboardShouldPersistTaps="handled" contentContainerStyle={styles.navTrack}>
        {WORKSPACES.filter(({ key }) => (key !== 'specifications' || hasSpecifications) && (key !== 'advanced' || showAdvanced)).map(({ key, title }) => (
          <Pressable
            key={key}
            accessibilityRole="tab"
            accessibilityLabel={title}
            accessibilityState={{ selected: active === key }}
            onPress={() => onChange(key)}
            style={[styles.tab, active === key ? styles.tabActive : null]}
          >
            <Text style={[styles.tabText, active === key ? styles.tabTextActive : null]}>{title}</Text>
          </Pressable>
        ))}
      </ScrollView>
    </View>
  );
}

export function ProductEditorSaveDock({
  onSaveDraft,
  onSave,
  saving,
  saveLabel,
}: {
  onSaveDraft: () => void;
  onSave: () => void;
  saving: boolean;
  saveLabel: string;
}) {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  const { width } = useWindowDimensions();
  const insets = useSafeAreaInsets();
  const [keyboardVisible, setKeyboardVisible] = useState(false);

  useEffect(() => {
    const show = Keyboard.addListener('keyboardDidShow', () => setKeyboardVisible(true));
    const hide = Keyboard.addListener('keyboardDidHide', () => setKeyboardVisible(false));
    return () => { show.remove(); hide.remove(); };
  }, []);

  if (keyboardVisible) return null;

  return (
    <View style={[styles.dock, { paddingBottom: width >= 600 ? Math.max(insets.bottom, spacing.sm) : Math.max(insets.bottom, 0) + 76 }]}>
      <View style={styles.dockRow}>
        <View style={styles.dockAction}>
          <Button variant="secondary" onPress={onSaveDraft} loading={saving}>Sačuvaj nacrt</Button>
        </View>
        <View style={styles.dockAction}>
          <Button onPress={onSave} loading={saving}>{saveLabel}</Button>
        </View>
      </View>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    nav: {
      paddingVertical: spacing.sm,
      borderBottomWidth: StyleSheet.hairlineWidth,
      borderBottomColor: theme.line,
      backgroundColor: theme.background,
      gap: spacing.xs,
    },
    navHeading: {
      ...typography.small,
      color: theme.muted,
      paddingHorizontal: spacing.lg,
      fontSize: 11,
    },
    navTrack: {
      paddingHorizontal: spacing.lg,
      gap: spacing.sm,
      alignItems: 'center',
    },
    tab: {
      minHeight: 44,
      paddingHorizontal: spacing.md,
      borderRadius: 14,
      justifyContent: 'center',
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: theme.line,
      backgroundColor: theme.surface,
    },
    tabActive: { backgroundColor: theme.primaryContainer, borderColor: theme.primary },
    tabText: { ...typography.small, color: theme.ink, fontWeight: '700' },
    tabTextActive: { color: theme.primary, fontWeight: '800' },
    dock: {
      paddingHorizontal: spacing.md,
      paddingTop: spacing.sm,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: theme.line,
      backgroundColor: theme.surface,
    },
    dockRow: { flexDirection: 'row', gap: spacing.sm },
    dockAction: { flex: 1, minWidth: 0 },
  });
}
