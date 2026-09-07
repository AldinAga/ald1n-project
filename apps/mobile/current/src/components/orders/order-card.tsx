import { useMemo } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Glyph } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { formatDate, humanize } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';
import { useMoneyPresentation } from '@/features/preferences/money-presentation';
import type { Order } from '@/types/api';

function statusTone(
  status: string,
): 'neutral' | 'primary' | 'success' | 'warning' | 'danger' {
  if (['completed', 'delivered', 'paid'].includes(status)) return 'success';
  if (['cancelled', 'rejected'].includes(status)) return 'danger';
  if (['pending', 'awaiting_payment'].includes(status)) return 'warning';
  return 'primary';
}

export function OrderCard({ order, onPress }: { order: Order; onPress: () => void }) {
  const { colors: themeColors } = useAppTheme();
  const { formatPrimaryMoney } = useMoneyPresentation();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Otvori porudžbinu ${order.order_number}`}
      onPress={onPress}
      style={({ pressed }) => [styles.surface, pressed && styles.pressed]}
    >
      {/* MOBILE_BUILD16_ORDER_CARD_REDESIGN_BATCH129 */}
      <View style={styles.rail} />
      <View style={styles.header}>
        <View style={styles.numberWrap}>
          <Text style={styles.eyebrow}>PORUDŽBINA</Text>
          <Text style={styles.number}>{order.order_number}</Text>
        </View>
        <Pill tone={statusTone(order.status)}>{humanize(order.status)}</Pill>
      </View>

      <View style={styles.metaRow}>
        <View style={styles.metric}>
          <Text style={styles.label}>Ukupno</Text>
          <Text style={styles.amount}>{formatPrimaryMoney(order.subtotal_rsd)}</Text>
        </View>
        <View style={styles.metric}>
          <Text style={styles.label}>Datum</Text>
          <Text style={styles.value}>{formatDate(order.created_at)}</Text>
        </View>
        <View style={styles.arrowTile}>
          <Glyph name="arrow" size={20} color={themeColors.primary} />
        </View>
      </View>
    </Pressable>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    surface: {
      position: 'relative',
      overflow: 'hidden',
      gap: spacing.md,
      padding: spacing.md,
      paddingLeft: spacing.lg,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surface,
      shadowColor: theme.black,
      shadowOpacity: 0.06,
      shadowRadius: 6,
      shadowOffset: { width: 0, height: 2 },
      elevation: 1,
    },
    rail: {
      position: 'absolute',
      left: 0,
      top: 0,
      bottom: 0,
      width: 4,
      backgroundColor: theme.primary,
    },
    pressed: { transform: [{ scale: 0.992 }], opacity: 0.9 },
    header: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'flex-start',
      gap: spacing.md,
    },
    numberWrap: { flex: 1, minWidth: 0 },
    eyebrow: { ...typography.small, color: theme.primary, letterSpacing: 1, fontWeight: '800' },
    number: { ...typography.h3, color: theme.ink, marginTop: 2 },
    metaRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md,
      paddingTop: spacing.sm,
      borderTopWidth: 1,
      borderTopColor: theme.line,
    },
    metric: { flex: 1, minWidth: 0 },
    label: { ...typography.small, color: theme.muted },
    amount: { ...typography.label, color: theme.primaryDark, marginTop: 2, fontVariant: ['tabular-nums'] },
    value: { ...typography.small, color: theme.ink, marginTop: 2 },
    arrowTile: {
      width: 38,
      height: 38,
      alignItems: 'center',
      justifyContent: 'center',
      borderRadius: radii.md,
      backgroundColor: theme.primarySoft,
    },
  });
}
