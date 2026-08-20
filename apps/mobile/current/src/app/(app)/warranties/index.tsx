import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { PageHeader } from '@/components/layout/page-header';
import { Card } from '@/components/ui/card';
import { Pill, type PillTone } from '@/components/ui/pill';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { api } from '@/lib/api/endpoints';
import { formatDate } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';
import type { WarrantyStatus, WarrantySummary } from '@/types/api';

function statusTone(status: WarrantyStatus): PillTone {
  if (status === 'active') return 'success';
  if (status === 'expired') return 'warning';
  return 'danger';
}

function WarrantyCard({ item }: { item: WarrantySummary }) {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Otvori garanciju ${item.warranty_number}`}
      onPress={() => router.push({ pathname: '/warranties/[id]', params: { id: String(item.id) } })}
      style={({ pressed }) => pressed && styles.pressed}
    >
      <Card style={styles.card}>
        <View style={styles.cardHeader}>
          <View style={styles.cardTitleWrap}>
            <Text style={styles.eyebrow}>GARANTNI LIST</Text>
            <Text style={styles.warrantyNumber}>{item.warranty_number}</Text>
          </View>
          <Pill tone={statusTone(item.status)}>{item.status_label}</Pill>
        </View>

        <Text style={styles.productName}>{item.product_name}</Text>
        {item.product_sku ? <Text style={styles.meta}>SKU {item.product_sku}</Text> : null}

        <View style={styles.infoGrid}>
          <Info label="Porudžbina" value={item.order.order_number} />
          <Info label="Važi do" value={item.expires_at ? formatDate(item.expires_at) : '—'} />
          <Info
            label="Sledeće održavanje"
            value={item.next_maintenance_at ? formatDate(item.next_maintenance_at) : 'Nije definisano'}
          />
        </View>

        <View style={styles.rule} />

        <View style={styles.footer}>
          <Text style={styles.date}>
            Ažurirano {formatDate(item.updated_at ?? item.created_at, true)}
          </Text>
          <Text style={styles.openLabel}>Detalji ›</Text>
        </View>
      </Card>
    </Pressable>
  );
}

function Info({ label, value }: { label: string; value: string }) {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);

  return (
    <View style={styles.infoRow}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue}>{value}</Text>
    </View>
  );
}

export default function WarrantiesListScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);
  const { bootstrap, can } = useAuth();
  const allowed = can('warranties.view_own');
  const query = useQuery({
    queryKey: ['warranties'],
    queryFn: () => api.warranties.list(),
    enabled: allowed,
  });

  if (!allowed) return <UnavailableState title="Garancije nisu dostupne" />;
  if (query.isLoading) return <LoadingState label="Učitavanje garancija…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <FlatList
        data={query.data?.data ?? []}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => <WarrantyCard item={item} />}
        ItemSeparatorComponent={() => <View style={{ height: spacing.md }} />}
        refreshControl={(
          <RefreshControl
            refreshing={query.isRefetching}
            onRefresh={() => void query.refetch()}
            tintColor={themeColors.primary}
          />
        )}
        contentContainerStyle={styles.content}
        ListHeaderComponent={(
          <View style={styles.header}>
            <PageHeader
              title="Moje garancije"
              eyebrow="Postprodaja"
              name={bootstrap?.user.name}
            />
            <Text style={styles.copy}>
              Garantni listovi, rokovi i planirano preventivno održavanje za tvoje isporučene proizvode.
            </Text>
          </View>
        )}
        ListEmptyComponent={(
          <EmptyState
            title="Još nema garantnih listova"
            message="Garancije se automatski formiraju nakon završene isporuke kada proizvod ima odgovarajuće pravilo garancije."
          />
        )}
      />
    </SafeAreaView>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    safe: {
      flex: 1,
      backgroundColor: theme.background,
    },
    content: {
      paddingHorizontal: spacing.lg,
      paddingBottom: 120,
      backgroundColor: theme.background,
    },
    header: {
      gap: spacing.sm,
      marginBottom: spacing.lg,
    },
    copy: {
      ...typography.body,
      color: theme.muted,
    },
    pressed: {
      transform: [{ scale: 0.99 }],
      opacity: 0.92,
    },
    card: {
      gap: spacing.md,
    },
    cardHeader: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    cardTitleWrap: {
      flex: 1,
    },
    eyebrow: {
      ...typography.small,
      color: theme.primary,
      letterSpacing: 1.1,
      fontWeight: '800',
    },
    warrantyNumber: {
      ...typography.h3,
      color: theme.ink,
      marginTop: 3,
    },
    productName: {
      ...typography.body,
      color: theme.ink,
      fontWeight: '700',
    },
    meta: {
      ...typography.small,
      color: theme.muted,
    },
    infoGrid: {
      gap: spacing.xs,
    },
    infoRow: {
      minHeight: 32,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    infoLabel: {
      ...typography.small,
      color: theme.muted,
      flex: 1,
    },
    infoValue: {
      ...typography.label,
      color: theme.ink,
      flex: 1,
      textAlign: 'right',
    },
    rule: {
      height: 1,
      backgroundColor: theme.line,
    },
    footer: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    date: {
      ...typography.small,
      color: theme.muted,
      flex: 1,
    },
    openLabel: {
      ...typography.label,
      color: theme.primary,
    },
  });
}
