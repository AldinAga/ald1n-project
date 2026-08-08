import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Card } from '@/components/ui/card';
import { Glyph } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import { colors, spacing, typography } from '@/constants/theme';
import { formatDate, formatMoney, humanize } from '@/lib/formatters';
import type { Order } from '@/types/api';

function statusTone(status: string): 'neutral' | 'primary' | 'success' | 'warning' | 'danger' {
  if (['completed', 'delivered', 'paid'].includes(status)) return 'success';
  if (['cancelled', 'rejected'].includes(status)) return 'danger';
  if (['pending', 'awaiting_payment'].includes(status)) return 'warning';
  return 'primary';
}

export function OrderCard({ order, onPress }: { order: Order; onPress: () => void }) {
  return (
    <Pressable onPress={onPress} style={({ pressed }) => pressed && styles.pressed}>
      <Card style={styles.card}>
        <View style={styles.header}>
          <View style={styles.numberWrap}>
            <Text style={styles.eyebrow}>PORUDŽBINA</Text>
            <Text style={styles.number}>{order.order_number}</Text>
          </View>
          <Pill tone={statusTone(order.status)}>{humanize(order.status)}</Pill>
        </View>
        <View style={styles.rule} />
        <View style={styles.footer}>
          <View><Text style={styles.label}>Ukupno</Text><Text style={styles.value}>{formatMoney(order.subtotal_rsd)}</Text></View>
          <View><Text style={styles.label}>Datum</Text><Text style={styles.valueSmall}>{formatDate(order.created_at)}</Text></View>
          <Glyph name="arrow" size={26} color={colors.muted} />
        </View>
      </Card>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  pressed: { transform: [{ scale: 0.99 }], opacity: 0.92 },
  card: { gap: spacing.md },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: spacing.md },
  numberWrap: { flex: 1 },
  eyebrow: { ...typography.small, color: colors.primary, letterSpacing: 1.2, fontWeight: '800' },
  number: { ...typography.h3, color: colors.ink, marginTop: 3 },
  rule: { height: 1, backgroundColor: colors.line },
  footer: { flexDirection: 'row', alignItems: 'center', gap: spacing.lg },
  label: { ...typography.small, color: colors.muted },
  value: { ...typography.label, color: colors.ink, marginTop: 2 },
  valueSmall: { ...typography.small, color: colors.ink, marginTop: 2 }
});
