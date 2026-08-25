import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmAction } from '@/components/ui/confirm-action';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminOrders,
  type AdminArchivedOrderItem,
  type AdminOrdersPerPage,
} from '@/features/admin/orders-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

function formatDateTime(value: string | null): string {
  if (!value) return '—';
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString('sr-RS');
}

function formatMoney(value: number): string {
  return `${new Intl.NumberFormat('sr-RS', { maximumFractionDigits: 2 }).format(value)} RSD`;
}

export default function AdminArchivedOrdersScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const allowed = can('orders.manage');

  const [draftQ, setDraftQ] = useState('');
  const [q, setQ] = useState('');
  const [page, setPage] = useState(1);
  const [perPage] = useState<AdminOrdersPerPage>(40);
  const [purgeTarget, setPurgeTarget] = useState<AdminArchivedOrderItem | null>(null);
  const [confirmation, setConfirmation] = useState('');
  const [purgeReason, setPurgeReason] = useState('');
  const [purgeConfirmVisible, setPurgeConfirmVisible] = useState(false);

  const params = { q: q || undefined, page, per_page: perPage };
  const query = useQuery({
    queryKey: adminQueryKeys.adminOrderArchives(params),
    queryFn: () => apiAdminOrders.archived(params),
    enabled: allowed,
  });

  const refreshAll = async () => {
    await client.invalidateQueries({ queryKey: adminQueryKeys.adminOrdersRoot() });
  };

  const restoreMutation = useMutation({
    mutationFn: (item: AdminArchivedOrderItem) => apiAdminOrders.restoreArchived(item.id),
    onSuccess: async (response) => {
      await refreshAll();
      feedback.notify({
        tone: 'success',
        title: 'Porudžbina je vraćena',
        message: response.data.order_number,
      });
    },
    onError: (error) => feedback.notify({
      tone: 'danger',
      title: 'Vraćanje nije uspelo',
      message: error instanceof Error ? error.message : 'Pokušaj ponovo.',
    }),
  });

  const purgeMutation = useMutation({
    mutationFn: () => {
      if (!purgeTarget) throw new Error('Porudžbina nije izabrana.');
      return apiAdminOrders.purgeArchived(purgeTarget.id, {
        confirmation: confirmation.trim(),
        purge_reason: purgeReason.trim(),
      });
    },
    onSuccess: async () => {
      setPurgeConfirmVisible(false);
      setPurgeTarget(null);
      setConfirmation('');
      setPurgeReason('');
      await refreshAll();
      feedback.notify({
        tone: 'success',
        title: 'Operativni purge je završen',
        message: 'Poslovna istorija potrebna za integritet ostaje sačuvana.',
      });
    },
    onError: (error) => {
      setPurgeConfirmVisible(false);
      feedback.notify({
        tone: 'danger',
        title: 'Purge nije uspeo',
        message: error instanceof Error ? error.message : 'Pokušaj ponovo.',
      });
    },
  });

  if (!allowed) return <UnavailableState title="Arhiva porudžbina nije dostupna" />;
  if (query.isLoading) return <LoadingState label="Učitavanje arhiviranih porudžbina…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const response = query.data;

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Porudžbine admin</Text>
      </Pressable>
      <PageHeader title="Arhivirane porudžbine" eyebrow="Admin · Prodaja" name={bootstrap?.user.name} />
      <Text style={styles.copy}>
        Arhiva koristi postojeći OrderArchiveService. Restore vraća porudžbinu u operativni prikaz, dok je purge dostupan samo SuperAdministratoru.
      </Text>

      <Card style={styles.card}>
        <TextField
          label="Pretraga"
          value={draftQ}
          onChangeText={setDraftQ}
          placeholder="Broj porudžbine, kupac ili telefon"
        />
        <View style={styles.actionsRow}>
          <Button onPress={() => { setPage(1); setQ(draftQ.trim()); }}>Pretraži</Button>
          <Button variant="secondary" onPress={() => { setDraftQ(''); setQ(''); setPage(1); }}>Očisti</Button>
        </View>
      </Card>

      <View style={styles.sectionHead}>
        <View>
          <Text style={styles.sectionTitle}>Arhiva</Text>
          <Text style={styles.muted}>{response.pagination.total} ukupno</Text>
        </View>
        <Button variant="secondary" onPress={() => void query.refetch()}>
          {query.isFetching ? 'Osvežavanje…' : 'Osveži'}
        </Button>
      </View>

      {response.data.length === 0 ? (
        <Card style={styles.card}><Text style={styles.muted}>Nema arhiviranih porudžbina.</Text></Card>
      ) : response.data.map((item) => (
        <Card key={item.id} style={styles.card}>
          <View style={styles.rowBetween}>
            <Text style={styles.cardTitle}>{item.order_number}</Text>
            <Text style={styles.status}>{item.status}</Text>
          </View>
          <Text style={styles.body}>{item.customer.name || 'Kupac nije dostupan'}</Text>
          <Text style={styles.muted}>{item.supplier?.name ? `Odgovorno lice: ${item.supplier.name}` : 'Odgovorno lice nije evidentirano'}</Text>
          <Text style={styles.muted}>Arhivirano: {formatDateTime(item.archived_at)}</Text>
          <Text style={styles.muted}>Završeno: {formatDateTime(item.completed_at)}</Text>
          <Text style={styles.body}>Razlog: {item.archive_reason || '—'}</Text>
          <Text style={styles.total}>{formatMoney(item.subtotal_rsd)}</Text>
          <View style={styles.actionsRow}>
            {item.can_restore ? (
              <Button
                variant="secondary"
                loading={restoreMutation.isPending && restoreMutation.variables?.id === item.id}
                onPress={() => restoreMutation.mutate(item)}
              >
                Vrati iz arhive
              </Button>
            ) : null}
            {item.can_purge && response.capabilities.purge ? (
              <Button
                variant="secondary"
                onPress={() => {
                  setPurgeTarget(item);
                  setConfirmation('');
                  setPurgeReason('');
                }}
              >
                Trajno ukloni
              </Button>
            ) : null}
          </View>
        </Card>
      ))}

      <Card style={styles.paginationCard}>
        <Text style={styles.muted}>Strana {response.pagination.current_page} od {Math.max(response.pagination.last_page, 1)}</Text>
        <View style={styles.actionsRow}>
          <Button variant="secondary" disabled={page <= 1} onPress={() => setPage((current) => Math.max(1, current - 1))}>Prethodna</Button>
          <Button variant="secondary" disabled={page >= response.pagination.last_page} onPress={() => setPage((current) => current + 1)}>Sledeća</Button>
        </View>
      </Card>

      {purgeTarget ? (
        <Card style={styles.dangerCard}>
          <Text style={styles.sectionTitle}>SuperAdmin · Operativni purge</Text>
          <Text style={styles.copy}>
            Ova radnja uklanja porudžbinu iz operativnih i arhivskih prikaza, ali zadržava poslovnu istoriju potrebnu za integritet.
          </Text>
          <Text style={styles.body}>Za potvrdu upiši: {purgeTarget.order_number}</Text>
          <TextField label="Broj porudžbine" value={confirmation} onChangeText={setConfirmation} />
          <TextField label="Razlog purge-a" value={purgeReason} onChangeText={setPurgeReason} multiline />
          <View style={styles.actionsRow}>
            <Button
              variant="secondary"
              disabled={confirmation.trim() !== purgeTarget.order_number || purgeReason.trim().length < 5 || purgeMutation.isPending}
              onPress={() => setPurgeConfirmVisible(true)}
            >
              Potvrdi trajno uklanjanje
            </Button>
            <Button variant="secondary" onPress={() => setPurgeTarget(null)}>Odustani</Button>
          </View>
        </Card>
      ) : null}

      <ConfirmAction
        visible={purgeConfirmVisible}
        title="Trajno ukloni porudžbinu?"
        message={purgeTarget ? `${purgeTarget.order_number} više neće biti dostupna kroz normalni restore tok.` : 'Porudžbina će biti trajno uklonjena.'}
        confirmLabel="Trajno ukloni"
        destructive
        busy={purgeMutation.isPending}
        onConfirm={() => purgeMutation.mutate()}
        onCancel={() => setPurgeConfirmVisible(false)}
      />
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 140, gap: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    copy: { ...typography.body, color: theme.muted },
    card: { gap: spacing.sm },
    dangerCard: { gap: spacing.md },
    sectionHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
    sectionTitle: { ...typography.h3, color: theme.ink },
    cardTitle: { ...typography.label, color: theme.ink, flex: 1 },
    body: { ...typography.body, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    status: { ...typography.label, color: theme.primary },
    total: { ...typography.h3, color: theme.ink },
    rowBetween: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    actionsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    paginationCard: { gap: spacing.sm },
  });
}
