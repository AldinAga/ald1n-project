import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import {
  FlatList,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { PageHeader } from '@/components/layout/page-header';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Pill, type PillTone } from '@/components/ui/pill';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { useMoneyPresentation } from '@/features/preferences/money-presentation';
import { api } from '@/lib/api/endpoints';
import { formatDate } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';
import type { Commission, CommissionListParams, CommissionStatus } from '@/types/api';

const STATUS_OPTIONS: Array<{ value?: CommissionStatus; label: string }> = [
  { label: 'Sve' },
  { value: 'pending', label: 'Na čekanju' },
  { value: 'approved', label: 'Odobrene' },
  { value: 'paid', label: 'Isplaćene' },
  { value: 'cancelled', label: 'Stornirane' },
];

function statusTone(status: CommissionStatus): PillTone {
  if (status === 'paid') return 'success';
  if (status === 'cancelled') return 'danger';
  if (status === 'approved') return 'info';
  return 'warning';
}

function isIsoDate(value: string): boolean {
  if (value === '') return true;
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;

  const parsed = new Date(`${value}T00:00:00Z`);
  return !Number.isNaN(parsed.getTime()) && parsed.toISOString().slice(0, 10) === value;
}

function CommissionCard({ item }: { item: Commission }) {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);
  const { formatPrimaryMoney } = useMoneyPresentation();

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Otvori proviziju za porudžbinu ${item.order.order_number}`}
      onPress={() => router.push({ pathname: '/commissions/[id]', params: { id: String(item.id) } })}
      style={({ pressed }) => pressed && styles.pressed}
    >
      <Card style={styles.commissionCard}>
        <View style={styles.cardHeader}>
          <View style={styles.cardTitleWrap}>
            <Text style={styles.eyebrow}>PROVIZIJA</Text>
            <Text style={styles.orderNumber}>{item.order.order_number}</Text>
          </View>
          <Pill tone={statusTone(item.status)}>{item.status_label}</Pill>
        </View>

        <Text style={styles.amount}>{formatPrimaryMoney(item.total_eur, 'EUR')}</Text>
        <Text style={styles.meta}>Odgovorno lice: {item.responsible_name}</Text>
        {item.status_note ? <Text style={styles.note}>{item.status_note}</Text> : null}

        <View style={styles.rule} />

        <View style={styles.footer}>
          <Text style={styles.date}>
            Ažurirano {formatDate(item.status_updated_at ?? item.updated_at ?? item.created_at, true)}
          </Text>
          <Text style={styles.openLabel}>Detalji ›</Text>
        </View>
      </Card>
    </Pressable>
  );
}

export default function CommissionsListScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);
  const { formatPrimaryMoney } = useMoneyPresentation();
  const { bootstrap, can } = useAuth();
  const allowed = can('commissions.view_own');

  const [page, setPage] = useState(1);
  const [status, setStatus] = useState<CommissionStatus | undefined>();
  const [draftQ, setDraftQ] = useState('');
  const [draftDateFrom, setDraftDateFrom] = useState('');
  const [draftDateTo, setDraftDateTo] = useState('');
  const [applied, setApplied] = useState<Pick<CommissionListParams, 'q' | 'date_from' | 'date_to'>>({});
  const [filterError, setFilterError] = useState<string | null>(null);

  const params = useMemo<CommissionListParams>(() => ({
    q: applied.q,
    status,
    date_from: applied.date_from,
    date_to: applied.date_to,
    page,
  }), [applied, page, status]);

  const query = useQuery({
    queryKey: ['commissions', params],
    queryFn: () => api.commissions.list(params),
    enabled: allowed,
  });

  const applyFilters = () => {
    const q = draftQ.trim();
    const dateFrom = draftDateFrom.trim();
    const dateTo = draftDateTo.trim();

    if (!isIsoDate(dateFrom) || !isIsoDate(dateTo)) {
      setFilterError('Datumi moraju biti u formatu YYYY-MM-DD.');
      return;
    }

    if (dateFrom && dateTo && dateTo < dateFrom) {
      setFilterError('Datum „Do“ ne može biti pre datuma „Od“.');
      return;
    }

    setFilterError(null);
    setPage(1);
    setApplied({
      q: q || undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
    });
  };

  const resetFilters = () => {
    setFilterError(null);
    setDraftQ('');
    setDraftDateFrom('');
    setDraftDateTo('');
    setApplied({});
    setStatus(undefined);
    setPage(1);
  };

  if (!allowed) return <UnavailableState title="Provizije nisu dostupne" />;
  if (query.isLoading) return <LoadingState label="Učitavanje provizija…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const summary = query.data?.summary;
  const meta = query.data?.meta;
  const currentPage = meta?.current_page ?? page;
  const lastPage = meta?.last_page ?? currentPage;

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <FlatList
        data={query.data?.data ?? []}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => <CommissionCard item={item} />}
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
            <PageHeader title="Moje provizije" eyebrow="Lični pregled" name={bootstrap?.user.name} />
            <Text style={styles.copy}>
              Prati obračun, odobrenje i isplatu provizija povezanih sa tvojim porudžbinama.
            </Text>

            <View style={styles.summaryGrid}>
              <Card style={styles.summaryCard}>
                <Text style={styles.summaryValue}>{formatPrimaryMoney(summary?.pending_eur ?? 0, 'EUR')}</Text>
                <Text style={styles.summaryLabel}>Na čekanju</Text>
              </Card>
              <Card style={styles.summaryCard}>
                <Text style={styles.summaryValue}>{formatPrimaryMoney(summary?.approved_eur ?? 0, 'EUR')}</Text>
                <Text style={styles.summaryLabel}>Odobreno</Text>
              </Card>
              <Card style={styles.summaryCard}>
                <Text style={styles.summaryValue}>{formatPrimaryMoney(summary?.paid_eur ?? 0, 'EUR')}</Text>
                <Text style={styles.summaryLabel}>Isplaćeno</Text>
              </Card>
              <Card style={styles.summaryCard}>
                <Text style={styles.summaryValue}>{String(summary?.count ?? 0)}</Text>
                <Text style={styles.summaryLabel}>Ukupno zapisa</Text>
              </Card>
            </View>

            <Card style={styles.filterCard}>
              <Text style={styles.sectionTitle}>Filteri</Text>
              <TextField
                label="Pretraga"
                value={draftQ}
                onChangeText={setDraftQ}
                placeholder="Broj porudžbine"
                autoCapitalize="none"
                autoCorrect={false}
                maxLength={190}
                returnKeyType="search"
                onSubmitEditing={applyFilters}
              />

              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.statusFilters}
              >
                {STATUS_OPTIONS.map((option) => {
                  const active = status === option.value;
                  const tone: PillTone = active
                    ? option.value ? statusTone(option.value) : 'primary'
                    : 'neutral';

                  return (
                    <Pressable
                      key={option.value ?? 'all'}
                      accessibilityRole="button"
                      accessibilityState={{ selected: active }}
                      onPress={() => {
                        setStatus(option.value);
                        setPage(1);
                      }}
                      style={({ pressed }) => pressed && styles.filterPressed}
                    >
                      <Pill tone={tone}>{option.label}</Pill>
                    </Pressable>
                  );
                })}
              </ScrollView>

              <View style={styles.dateFields}>
                <View style={styles.dateField}>
                  <TextField
                    label="Od (YYYY-MM-DD)"
                    value={draftDateFrom}
                    onChangeText={setDraftDateFrom}
                    autoCapitalize="none"
                    autoCorrect={false}
                    maxLength={10}
                  />
                </View>
                <View style={styles.dateField}>
                  <TextField
                    label="Do (YYYY-MM-DD)"
                    value={draftDateTo}
                    onChangeText={setDraftDateTo}
                    autoCapitalize="none"
                    autoCorrect={false}
                    maxLength={10}
                  />
                </View>
              </View>

              {filterError ? <Text style={styles.filterError}>{filterError}</Text> : null}

              <View style={styles.filterActions}>
                <Button onPress={applyFilters} style={styles.filterButton}>Filtriraj</Button>
                <Button variant="ghost" onPress={resetFilters} style={styles.filterButton}>Resetuj</Button>
              </View>
            </Card>
          </View>
        )}
        ListEmptyComponent={(
          <EmptyState
            title="Nema provizija"
            message="Za izabrane filtere nema provizija. Novi obračuni će se pojaviti nakon kreiranja odgovarajućih porudžbina."
          />
        )}
        ListFooterComponent={(
          <View style={styles.pagination}>
            <Text style={styles.paginationMeta}>
              Strana {currentPage} od {lastPage} · {meta?.total ?? query.data?.data.length ?? 0} zapisa
            </Text>
            <View style={styles.paginationButtons}>
              {currentPage > 1 ? (
                <Button variant="ghost" onPress={() => setPage((value) => Math.max(1, value - 1))}>
                  ‹ Prethodna
                </Button>
              ) : null}
              {currentPage < lastPage ? (
                <Button variant="secondary" onPress={() => setPage((value) => value + 1)}>
                  Sledeća ›
                </Button>
              ) : null}
            </View>
          </View>
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
      gap: spacing.md,
      marginBottom: spacing.lg,
    },
    copy: {
      ...typography.body,
      color: theme.muted,
    },
    summaryGrid: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: spacing.sm,
    },
    summaryCard: {
      width: '48%',
      gap: spacing.xs,
    },
    summaryValue: {
      ...typography.h3,
      color: theme.ink,
    },
    summaryLabel: {
      ...typography.small,
      color: theme.muted,
    },
    filterCard: {
      gap: spacing.md,
    },
    sectionTitle: {
      ...typography.h3,
      color: theme.ink,
    },
    statusFilters: {
      gap: spacing.sm,
      paddingVertical: spacing.xs,
    },
    filterPressed: {
      opacity: 0.78,
    },
    dateFields: {
      flexDirection: 'row',
      gap: spacing.sm,
    },
    dateField: {
      flex: 1,
    },
    filterError: {
      ...typography.small,
      color: theme.danger,
    },
    filterActions: {
      flexDirection: 'row',
      gap: spacing.sm,
    },
    filterButton: {
      flex: 1,
    },
    pressed: {
      transform: [{ scale: 0.99 }],
      opacity: 0.92,
    },
    commissionCard: {
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
    orderNumber: {
      ...typography.h3,
      color: theme.ink,
      marginTop: 3,
    },
    amount: {
      ...typography.h1,
      color: theme.ink,
    },
    meta: {
      ...typography.small,
      color: theme.muted,
    },
    note: {
      ...typography.body,
      color: theme.ink,
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
    pagination: {
      gap: spacing.sm,
      paddingTop: spacing.lg,
      paddingBottom: spacing.lg,
      alignItems: 'center',
    },
    paginationMeta: {
      ...typography.small,
      color: theme.muted,
      textAlign: 'center',
    },
    paginationButtons: {
      flexDirection: 'row',
      gap: spacing.sm,
      justifyContent: 'center',
    },
  });
}
