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
import type { AfterSalesCaseSummary, AfterSalesStatus } from '@/types/api';

function statusTone(status: AfterSalesStatus): PillTone {
  if (status === 'resolved' || status === 'approved') return 'success';
  if (status === 'rejected') return 'danger';
  if (status === 'under_review' || status === 'awaiting_customer') return 'warning';
  if (status === 'in_service') return 'info';
  if (status === 'closed') return 'neutral';
  return 'primary';
}

function CaseCard({ item }: { item: AfterSalesCaseSummary }) {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);

  return (
    <Pressable
      onPress={() => router.push({ pathname: '/after-sales/[id]', params: { id: String(item.id) } })}
      style={({ pressed }) => pressed && styles.pressed}
    >
      <Card style={styles.card}>
        <View style={styles.cardHeader}>
          <View style={styles.cardTitleWrap}>
            <Text style={styles.eyebrow}>{item.case_type_label.toUpperCase()}</Text>
            <Text style={styles.caseNumber}>{item.case_number}</Text>
          </View>
          <Pill tone={statusTone(item.status)}>{item.status_label}</Pill>
        </View>

        <Text style={styles.subject}>{item.subject}</Text>

        <View style={styles.metaRow}>
          <Text style={styles.meta}>Porudžbina {item.order.order_number}</Text>
          <Text style={styles.meta}>{item.priority_label}</Text>
        </View>

        <View style={styles.rule} />

        <View style={styles.footer}>
          <Text style={styles.date}>Ažurirano {formatDate(item.updated_at ?? item.created_at, true)}</Text>
          <Text style={styles.openLabel}>Otvori ›</Text>
        </View>
      </Card>
    </Pressable>
  );
}

export default function AfterSalesListScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);
  const { bootstrap, can } = useAuth();
  const allowed = can('after_sales.view_own');
  const query = useQuery({
    queryKey: ['after-sales'],
    queryFn: () => api.afterSales.list(),
    enabled: allowed
  });

  if (!allowed) return <UnavailableState title="Reklamacije i servis nisu dostupni" />;
  if (query.isLoading) return <LoadingState label="Učitavanje reklamacija i servisa…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <FlatList
        data={query.data?.data ?? []}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => <CaseCard item={item} />}
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
              title="Reklamacije i servis"
              eyebrow="Podrška nakon kupovine"
              name={bootstrap?.user.name}
            />
            <Text style={styles.copy}>
              Prati reklamacije, povrate i servisne zahteve povezane sa tvojim porudžbinama.
            </Text>
          </View>
        )}
        ListEmptyComponent={(
          <EmptyState
            title="Nema zahteva"
            message="Kada pošalješ reklamaciju, povrat ili servisni zahtev, pojaviće se ovde."
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
    caseNumber: {
      ...typography.h3,
      color: theme.ink,
      marginTop: 3,
    },
    subject: {
      ...typography.body,
      color: theme.ink,
      fontWeight: '700',
    },
    metaRow: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: spacing.md,
    },
    meta: {
      ...typography.small,
      color: theme.muted,
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
