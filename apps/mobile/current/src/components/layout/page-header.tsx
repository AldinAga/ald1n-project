import { Pressable, StyleSheet, Text, View } from 'react-native';
import { BrandMark } from '@/components/ui/brand-mark';
import { Glyph } from '@/components/ui/glyph';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { initials } from '@/lib/formatters';

export function PageHeader({ title, eyebrow, name, onRefresh, refreshing = false }: {
  title: string;
  eyebrow?: string;
  name?: string | null;
  onRefresh?: () => void;
  refreshing?: boolean;
}) {
  return (
    <View style={styles.row}>
      <View style={styles.brandRow}>
        <BrandMark size={42} />
        <View style={styles.copy}>
          {eyebrow ? <Text style={styles.eyebrow}>{eyebrow}</Text> : null}
          <Text style={styles.title} numberOfLines={1}>{title}</Text>
        </View>
      </View>
      {onRefresh ? (
        <Pressable accessibilityLabel="Osveži" onPress={onRefresh} disabled={refreshing} style={styles.action}>
          <Glyph name="refresh" size={22} color={colors.ink} style={refreshing ? styles.spinHint : undefined} />
        </Pressable>
      ) : (
        <View style={styles.avatar}><Text style={styles.avatarText}>{initials(name)}</Text></View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  row: { minHeight: 66, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: spacing.md },
  brandRow: { flex: 1, minWidth: 0, flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  copy: { flex: 1, minWidth: 0 },
  eyebrow: { ...typography.small, color: colors.primary, textTransform: 'uppercase', letterSpacing: 1.1, fontWeight: '800' },
  title: { ...typography.h2, color: colors.ink },
  action: { width: 44, height: 44, borderRadius: radii.lg, backgroundColor: colors.surface, borderWidth: 1, borderColor: colors.line, alignItems: 'center', justifyContent: 'center' },
  spinHint: { opacity: 0.45 },
  avatar: { width: 44, height: 44, borderRadius: 16, backgroundColor: colors.primarySoft, alignItems: 'center', justifyContent: 'center' },
  avatarText: { ...typography.label, color: colors.primaryDark }
});
