import { router } from 'expo-router';
import { Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Card } from '@/components/ui/card';
import { Glyph } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { useCart } from '@/features/cart/cart-provider';

export default function HomeScreen() {
  const { bootstrap, refreshBootstrap, hasFeature } = useAuth();
  const { itemCount } = useCart();
  const user = bootstrap?.user;
  const quickActions = [
    hasFeature('catalog') ? { title: 'Otvori katalog', copy: 'Pretraži aktivne proizvode', glyph: 'catalog' as const, route: '/catalog' as const } : null,
    hasFeature('order_create') ? { title: 'Korpa', copy: itemCount ? `${itemCount} komada spremno` : 'Pripremi novu porudžbinu', glyph: 'cart' as const, route: '/cart' as const } : null,
    hasFeature('orders') ? { title: 'Moje porudžbine', copy: 'Proveri status i detalje', glyph: 'orders' as const, route: '/orders' as const } : null,
    hasFeature('notifications') ? { title: 'Obaveštenja', copy: `${bootstrap?.notification_counts.unread ?? 0} nepročitanih`, glyph: 'bell' as const, route: '/notifications' as const } : null
  ].filter(Boolean) as Array<{ title: string; copy: string; glyph: 'catalog' | 'cart' | 'orders' | 'bell'; route: '/catalog' | '/cart' | '/orders' | '/notifications' }>;

  return (
    <Screen refreshControl={<RefreshControl refreshing={false} onRefresh={() => void refreshBootstrap()} tintColor={colors.primary} />}>
      <PageHeader title="Pregled" eyebrow="Ald1n Mobile" name={user?.name} />
      <View style={styles.hero}>
        <View style={styles.heroOrb} />
        <Pill tone="warning">AKTIVAN RADNI PROSTOR</Pill>
        <Text style={styles.greeting}>Zdravo, {user?.first_name ?? user?.name?.split(' ')[0] ?? 'korisniče'}.</Text>
        <Text style={styles.heroCopy}>Najvažniji poslovni podaci su spremni za mobilni rad.</Text>
        <View style={styles.heroMeta}><Text style={styles.heroMetaLabel}>Uloga</Text><Text style={styles.heroMetaValue}>{user?.role?.name ?? 'Korisnik'}</Text></View>
      </View>

      <View style={styles.metrics}>
        <Metric value={String(bootstrap?.notification_counts.unread ?? 0)} label="Nepročitano" tone="primary" />
        <Metric value={String(bootstrap?.permissions.length ?? 0)} label="Dozvole" tone="accent" />
        <Metric value={bootstrap?.app.api_version ?? 'v1'} label="API" tone="success" />
      </View>

      <View style={styles.sectionHead}><Text style={styles.sectionTitle}>Brze akcije</Text><Text style={styles.sectionMeta}>{quickActions.length} dostupno</Text></View>
      <View style={styles.actions}>
        {quickActions.map((action) => (
          <Pressable key={action.title} onPress={() => router.push(action.route)} style={({ pressed }) => pressed && styles.pressed}>
            <Card style={styles.actionCard}>
              <View style={styles.actionIcon}><Glyph name={action.glyph} size={24} color={colors.primary} /></View>
              <View style={styles.actionCopy}><Text style={styles.actionTitle}>{action.title}</Text><Text style={styles.actionText}>{action.copy}</Text></View>
              <Glyph name="arrow" size={27} color={colors.muted} />
            </Card>
          </Pressable>
        ))}
      </View>

      <Card muted style={styles.foundation}>
        <View style={styles.foundationIcon}><Glyph name="check" color={colors.success} size={22} /></View>
        <View style={{ flex: 1 }}><Text style={styles.foundationTitle}>Sve je sinhronizovano</Text><Text style={styles.foundationCopy}>Katalog, porudžbine i poslovna obaveštenja koriste isti bezbedan Ald1n nalog.</Text></View>
      </Card>
    </Screen>
  );
}

function Metric({ value, label, tone }: { value: string; label: string; tone: 'primary' | 'accent' | 'success' }) {
  return <Card style={styles.metricCard}><View style={[styles.metricDot, toneStyles[tone]]} /><Text style={styles.metricValue}>{value}</Text><Text style={styles.metricLabel}>{label}</Text></Card>;
}

const toneStyles = StyleSheet.create({ primary: { backgroundColor: colors.primary }, accent: { backgroundColor: colors.accent }, success: { backgroundColor: colors.success } });
const styles = StyleSheet.create({
  hero: { overflow: 'hidden', minHeight: 250, borderRadius: 28, padding: spacing.xxl, backgroundColor: colors.hero, justifyContent: 'flex-end', gap: spacing.md },
  heroOrb: { position: 'absolute', width: 250, height: 250, borderRadius: 125, backgroundColor: colors.primary, right: -95, top: -80, opacity: 0.5 },
  greeting: { ...typography.hero, color: colors.white, maxWidth: 310 },
  heroCopy: { ...typography.body, color: colors.heroMuted, maxWidth: 330 },
  heroMeta: { alignSelf: 'flex-start', flexDirection: 'row', gap: spacing.sm, alignItems: 'center', marginTop: spacing.sm },
  heroMetaLabel: { ...typography.small, color: colors.heroMuted },
  heroMetaValue: { ...typography.label, color: colors.white },
  metrics: { flexDirection: 'row', gap: spacing.sm },
  metricCard: { flex: 1, padding: spacing.md, gap: 3, borderRadius: radii.lg },
  metricDot: { width: 8, height: 8, borderRadius: 4, marginBottom: spacing.sm },
  metricValue: { ...typography.h2, color: colors.ink },
  metricLabel: { ...typography.small, color: colors.muted },
  sectionHead: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  sectionTitle: { ...typography.h2, color: colors.ink },
  sectionMeta: { ...typography.small, color: colors.muted },
  actions: { gap: spacing.md },
  pressed: { transform: [{ scale: 0.99 }], opacity: 0.92 },
  actionCard: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  actionIcon: { width: 48, height: 48, borderRadius: radii.lg, backgroundColor: colors.primarySoft, alignItems: 'center', justifyContent: 'center' },
  actionCopy: { flex: 1 },
  actionTitle: { ...typography.h3, color: colors.ink },
  actionText: { ...typography.small, color: colors.muted, marginTop: 3 },
  foundation: { flexDirection: 'row', gap: spacing.md, alignItems: 'flex-start' },
  foundationIcon: { width: 38, height: 38, borderRadius: radii.md, backgroundColor: colors.successSoft, alignItems: 'center', justifyContent: 'center' },
  foundationTitle: { ...typography.label, color: colors.ink },
  foundationCopy: { ...typography.small, color: colors.muted, marginTop: 4 }
});
