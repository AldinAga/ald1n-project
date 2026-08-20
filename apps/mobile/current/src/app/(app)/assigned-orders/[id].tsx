import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { Screen } from '@/components/layout/screen';
import { Card } from '@/components/ui/card';
import { Pill } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { api } from '@/lib/api/endpoints';
import { formatDate, formatMoney, humanize } from '@/lib/formatters';
import { useThemedStyles } from '@/theme/app-theme';

export default function AssignedOrderDetailScreen() {
  const styles = useThemedStyles(createStyles);
  const { id } = useLocalSearchParams<{ id: string }>();
  const orderId = Number(id);
  const validOrderId = Number.isInteger(orderId) && orderId > 0;
  const { can } = useAuth();
  const allowed = can('orders.manage');

  const query = useQuery({
    queryKey: ['assigned-order', orderId],
    queryFn: () => api.orders.assignedDetail(orderId),
    enabled: allowed && validOrderId,
  });

  if (!allowed) return <UnavailableState title="Dodeljena porudžbina nije dostupna" />;
  if (!validOrderId) return <ErrorState error={new Error('Neispravan identifikator porudžbine.')} />;
  if (query.isLoading) return <LoadingState label="Učitavanje dodeljene porudžbine…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const order = query.data;

  return (
    <Screen>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Nazad na dodeljene</Text>
      </Pressable>

      <View style={styles.heading}>
        <View style={styles.flexOne}>
          <Text style={styles.eyebrow}>DODELJENA PORUDŽBINA</Text>
          <Text style={styles.title}>{order.order_number}</Text>
        </View>
        <Pill tone={order.status === 'completed' ? 'success' : order.status === 'cancelled' ? 'danger' : 'primary'}>
          {humanize(order.status)}
        </Pill>
      </View>

      <Card style={styles.summary}>
        <Info label="Ukupna vrednost" value={formatMoney(order.subtotal_rsd)} />
        <Info label="Kreirano" value={formatDate(order.created_at, true)} />
        <Info label="Workflow status" value={humanize(order.workflow_status)} />
        <Info label="Lager" value={order.inventory_state ? humanize(order.inventory_state) : '—'} />
        <Info label="Način plaćanja" value={humanize(order.payment_method)} />
        <Info label="Status plaćanja" value={humanize(order.payment_status)} />
        <Info label="Tracking" value={order.tracking_number ?? '—'} />
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>Stavke</Text>
        {order.items?.length ? order.items.map((item) => (
          <View key={item.id} style={styles.item}>
            <View style={styles.flexOne}>
              <Text style={styles.itemName}>{item.name}</Text>
              <Text style={styles.itemMeta}>{item.sku} · {item.quantity} kom.</Text>
            </View>
            <Text style={styles.itemPrice}>{formatMoney(item.line_total_rsd)}</Text>
          </View>
        )) : <Text style={styles.muted}>Nema dostupnih stavki.</Text>}
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>Kupac i isporuka</Text>
        <Info label="Kupac" value={order.shipping.full_name} />
        <Info label="Adresa" value={`${order.shipping.address}, ${order.shipping.postal_code} ${order.shipping.city}`} />
        <Info label="Telefon" value={order.shipping.phone} />
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>Odgovorno lice</Text>
        <Info label="Ime" value={order.supplier.name ?? '—'} />
        <Info label="Uloga" value={order.supplier.role ?? '—'} />
        <Info label="E-mail" value={order.supplier.email ?? '—'} />
        <Info label="Telefon" value={order.supplier.phone ?? '—'} />
      </Card>

      {order.commission ? (
        <Card>
          <Text style={styles.sectionTitle}>Provizija</Text>
          <Info label="Iznos" value={`${order.commission.total_eur.toFixed(2)} EUR`} />
          <Info label="Status" value={humanize(order.commission.status)} />
        </Card>
      ) : null}

      <Text style={styles.notice}>Ovaj ekran je read-only Assigned-to-me prikaz. Operativne workflow akcije nisu uključene u P1.1.</Text>
    </Screen>
  );
}

function Info({ label, value }: { label: string; value: string }) {
  const styles = useThemedStyles(createStyles);
  return (
    <View style={styles.info}>
      <Text style={styles.label}>{label}</Text>
      <Text style={styles.infoValue}>{value}</Text>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    heading: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    flexOne: { flex: 1 },
    eyebrow: { ...typography.small, color: theme.primary, letterSpacing: 1.2, fontWeight: '800' },
    title: { ...typography.h1, color: theme.ink, marginTop: 3 },
    summary: { gap: 0 },
    sectionTitle: { ...typography.h3, color: theme.ink, marginBottom: spacing.sm },
    info: { minHeight: 48, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md, borderTopWidth: 1, borderTopColor: theme.line },
    label: { ...typography.small, color: theme.muted },
    infoValue: { ...typography.label, color: theme.ink, textAlign: 'right', flex: 1 },
    item: { minHeight: 62, flexDirection: 'row', alignItems: 'center', gap: spacing.md, borderTopWidth: 1, borderTopColor: theme.line },
    itemName: { ...typography.label, color: theme.ink },
    itemMeta: { ...typography.small, color: theme.muted, marginTop: 3 },
    itemPrice: { ...typography.label, color: theme.primaryDark },
    muted: { ...typography.body, color: theme.muted },
    notice: { ...typography.small, color: theme.muted, paddingBottom: spacing.lg },
  });
}
