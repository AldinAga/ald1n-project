import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Pill } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { colors, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { api } from '@/lib/api/endpoints';
import { formatDate, formatMoney, humanize } from '@/lib/formatters';

export default function OrderDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const orderId = Number(id);
  const client = useQueryClient();
  const { hasFeature } = useAuth();
  const allowed = hasFeature('orders');
  const query = useQuery({ queryKey: ['order', orderId], queryFn: () => api.orders.detail(orderId), enabled: allowed && Number.isInteger(orderId) && orderId > 0 });
  const cancel = useMutation({ mutationFn: () => api.orders.cancel(orderId), onSuccess: async () => { await client.invalidateQueries({ queryKey: ['orders'] }); await client.invalidateQueries({ queryKey: ['order', orderId] }); } });
  if (!allowed) return <UnavailableState title="Porudžbina nije dostupna" />;
  if (query.isLoading) return <LoadingState label="Učitavanje porudžbine…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  const order = query.data;
  const canCancel = hasFeature('order_cancel') && !['cancelled', 'completed', 'delivered'].includes(order.status);
  const confirmCancel = () => Alert.alert('Otkazivanje porudžbine', 'Ova akcija menja poslovni status porudžbine. Nastaviti?', [{ text: 'Ne', style: 'cancel' }, { text: 'Otkaži porudžbinu', style: 'destructive', onPress: () => cancel.mutate() }]);
  return (
    <Screen>
      <Pressable onPress={() => router.back()}><Text style={styles.back}>‹ Nazad na porudžbine</Text></Pressable>
      <View style={styles.heading}><View><Text style={styles.eyebrow}>PORUDŽBINA</Text><Text style={styles.title}>{order.order_number}</Text></View><Pill tone={order.status === 'completed' ? 'success' : order.status === 'cancelled' ? 'danger' : 'primary'}>{humanize(order.status)}</Pill></View>
      <Card style={styles.total}><View><Text style={styles.label}>Ukupna vrednost</Text><Text style={styles.totalValue}>{formatMoney(order.subtotal_rsd)}</Text></View><View><Text style={styles.label}>Kreirano</Text><Text style={styles.value}>{formatDate(order.created_at, true)}</Text></View></Card>
      <Card><Text style={styles.sectionTitle}>Stavke</Text>{order.items?.map((item) => <View key={item.id} style={styles.item}><View style={{ flex: 1 }}><Text style={styles.itemName}>{item.name}</Text><Text style={styles.itemMeta}>{item.sku} · {item.quantity} kom.</Text></View><Text style={styles.itemPrice}>{formatMoney(item.line_total_rsd)}</Text></View>)}</Card>
      <Card><Text style={styles.sectionTitle}>Isporuka</Text><Text style={styles.value}>{order.shipping.full_name}</Text><Text style={styles.muted}>{order.shipping.address}, {order.shipping.postal_code} {order.shipping.city}</Text><Text style={styles.muted}>{order.shipping.phone}</Text></Card>
      <Card><Text style={styles.sectionTitle}>Plaćanje i dobavljač</Text><Info label="Način plaćanja" value={humanize(order.payment_method)} /><Info label="Status plaćanja" value={humanize(order.payment_status)} /><Info label="Dobavljač" value={order.supplier.name ?? '—'} /></Card>
      {canCancel ? <Button variant="danger" onPress={confirmCancel} loading={cancel.isPending}>Otkaži porudžbinu</Button> : null}
    </Screen>
  );
}

function Info({ label, value }: { label: string; value: string }) { return <View style={styles.info}><Text style={styles.label}>{label}</Text><Text style={styles.value}>{value}</Text></View>; }
const styles = StyleSheet.create({
  back: { ...typography.label, color: colors.primary, paddingVertical: spacing.sm },
  heading: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
  eyebrow: { ...typography.small, color: colors.primary, letterSpacing: 1.2, fontWeight: '800' },
  title: { ...typography.h1, color: colors.ink, marginTop: 3 },
  total: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.lg },
  label: { ...typography.small, color: colors.muted },
  value: { ...typography.label, color: colors.ink, marginTop: 3 },
  totalValue: { ...typography.h2, color: colors.primaryDark, marginTop: 3 },
  sectionTitle: { ...typography.h3, color: colors.ink, marginBottom: spacing.md },
  item: { minHeight: 62, flexDirection: 'row', alignItems: 'center', gap: spacing.md, borderTopWidth: 1, borderTopColor: colors.line },
  itemName: { ...typography.label, color: colors.ink },
  itemMeta: { ...typography.small, color: colors.muted, marginTop: 3 },
  itemPrice: { ...typography.label, color: colors.primaryDark },
  muted: { ...typography.body, color: colors.muted, marginTop: spacing.xs },
  info: { minHeight: 48, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md, borderTopWidth: 1, borderTopColor: colors.line }
});
