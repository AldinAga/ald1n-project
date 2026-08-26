import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router, type Href, useLocalSearchParams } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
import { Pill, type PillTone } from '@/components/ui/pill';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminUsers,
  type AdminUser,
  type AdminUserListParams,
  type AdminUsersPerPage,
  type AdminUserStatus,
} from '@/features/admin/users-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

const PER_PAGE_OPTIONS: Array<{ value: string; label: string }> = [
  { value: '20', label: '20 po strani' },
  { value: '50', label: '50 po strani' },
  { value: '100', label: '100 po strani' },
];

function statusLabel(status: AdminUserStatus): string {
  if (status === 'active') return 'Aktivan';
  if (status === 'pending') return 'Na čekanju';
  return 'Blokiran';
}

function statusTone(status: AdminUserStatus): PillTone {
  if (status === 'active') return 'success';
  if (status === 'pending') return 'warning';
  return 'danger';
}

function formatDateTime(value: string | null): string {
  if (!value) return 'Nije evidentirano';
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString('sr-RS');
}

// MOBILE_V0_8_COMPLETE_USER_MANAGEMENT_BATCH12
export default function AdminUsersIndexScreen() {
  // MOBILE_V1_0_GLOBAL_SEARCH_PARITY_BATCH38
  const params = useLocalSearchParams<{ q?: string | string[] }> ();
  const initialQueryParam = Array.isArray(params.q) ? params.q[0] : params.q;
  const initialQuery = typeof initialQueryParam === 'string' ? initialQueryParam.trim().slice(0, 80) : '';
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const allowed = can('system.manage_users');

  const [draftQuery, setDraftQuery] = useState(initialQuery);
  const [draftStatus, setDraftStatus] = useState<AdminUserStatus | ''> ('');
  const [draftPerPage, setDraftPerPage] = useState<AdminUsersPerPage> (20);
  const [applied, setApplied] = useState<AdminUserListParams> ({ page: 1, per_page: 20, ...(initialQuery ? { q: initialQuery } : {}) });

  const query = useQuery({
    queryKey: adminQueryKeys.usersList(applied),
    queryFn: () => apiAdminUsers.list(applied),
    enabled: allowed,
  });

  if (!allowed) return <UnavailableState title="Upravljanje korisnicima nije dostupno" />;
  if (query.isLoading) return <LoadingState label="Učitavanje korisnika…" />;
  if (query.isError || !query.data) {
    return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  }

  const response = query.data;
  const activeCount = Number(Boolean(draftQuery.trim()))
    + Number(Boolean(draftStatus))
    + Number(draftPerPage !== 20);

  const applyFilters = () => {
    const next: AdminUserListParams = { page: 1, per_page: draftPerPage };
    if (draftQuery.trim()) next.q = draftQuery.trim();
    if (draftStatus) next.status = draftStatus;
    setApplied(next);
  };

  const clearFilters = () => {
    setDraftQuery('');
    setDraftStatus('');
    setDraftPerPage(20);
    setApplied({ page: 1, per_page: 20 });
  };

  const setPage = (page: number) => {
    if (page < 1 || page > Math.max(response.pagination.last_page, 1)) return;
    setApplied((current) => ({ ...current, page }));
  };

  return (
    <Screen contentStyle={styles.content} keyboardShouldPersistTaps="handled">
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Administracija</Text>
      </Pressable>

      <PageHeader
        title="Korisnici"
        eyebrow="Admin · Pristup i ovlašćenja"
        name={bootstrap?.user.name}
      />

      <Card style={styles.hero}>
        <Text style={styles.heroTitle}>Kompletno upravljanje korisnicima</Text>
        <Text style={styles.muted}>
          Lista, pretraga, status, uloga, grupa pristupa, profil i upravljanje lozinkom koriste isti Laravel bezbednosni model kao portal.
        </Text>
        {response.capabilities.create ? (
          <Button
            variant="secondary"
            onPress={() => router.push('/admin/users/create' as Href)}
          >
            Dodaj korisnika
          </Button>
        ) : null}
      </Card>

      <Card style={styles.filtersCard}>
        <View style={styles.rowBetween}>
          <Text style={styles.sectionTitle}>Filteri</Text>
          <Text style={styles.muted}>{activeCount} aktivnih</Text>
        </View>
        <FilterBar activeCount={activeCount} onClear={clearFilters}>
          {response.filter_options.statuses.map((option) => (
            <FilterChip
              key={option.value}
              label={option.label}
              active={draftStatus === option.value}
              onPress={() => setDraftStatus(draftStatus === option.value ? '' : option.value)}
            />
          ))}
        </FilterBar>
        <TextField
          label="Pretraga"
          value={draftQuery}
          onChangeText={setDraftQuery}
          placeholder="Korisničko ime, e-mail ili ime"
          autoCapitalize="none"
        />
        <SelectSheet
          label="Status"
          value={draftStatus}
          options={[
            { value: '', label: 'Svi statusi' },
            ...response.filter_options.statuses,
          ]}
          onChange={(value) => {
            if (value === '' || value === 'pending' || value === 'active' || value === 'blocked') {
              setDraftStatus(value);
            }
          }}
        />
        <SelectSheet
          label="Broj po strani"
          value={String(draftPerPage)}
          options={PER_PAGE_OPTIONS}
          onChange={(value) => {
            const next = Number(value);
            if (next === 20 || next === 50 || next === 100) setDraftPerPage(next);
          }}
        />
        <Button onPress={applyFilters}>Primeni filtere</Button>
      </Card>

      <View style={styles.rowBetween}>
        <View>
          <Text style={styles.sectionTitle}>Nalozi</Text>
          <Text style={styles.muted}>{response.pagination.total} ukupno</Text>
        </View>
        <Button variant="secondary" onPress={() => void query.refetch()}>
          {query.isFetching ? 'Osvežavanje…' : 'Osveži'}
        </Button>
      </View>

      {response.data.length === 0 ? (
        <Card style={styles.card}>
          <Text style={styles.muted}>Nema korisnika koji odgovaraju trenutnim filterima.</Text>
        </Card>
      ) : (
        response.data.map((user) => (
          <UserCard key={user.id} user={user} styles={styles} />
        ))
      )}

      <Card style={styles.paginationCard}>
        <Text style={styles.muted}>
          Strana {response.pagination.current_page} od {Math.max(response.pagination.last_page, 1)}
        </Text>
        <View style={styles.actionsRow}>
          <Button
            variant="secondary"
            disabled={response.pagination.current_page <= 1}
            onPress={() => setPage(response.pagination.current_page - 1)}
          >
            Prethodna
          </Button>
          <Button
            variant="secondary"
            disabled={response.pagination.current_page >= response.pagination.last_page}
            onPress={() => setPage(response.pagination.current_page + 1)}
          >
            Sledeća
          </Button>
        </View>
      </Card>
    </Screen>
  );
}

function UserCard({
  user,
  styles,
}: {
  user: AdminUser;
  styles: ReturnType<typeof createStyles>;
}) {
  return (
    <Pressable
      accessibilityRole="button"
      onPress={() => router.push({
        pathname: '/admin/users/[id]',
        params: { id: String(user.id) },
      } as Href)}
    >
      <Card style={styles.card}>
        <View style={styles.rowBetween}>
          <View style={styles.identity}>
            <Text style={styles.cardTitle}>{user.name}</Text>
            <Text style={styles.muted}>{user.username} · {user.email}</Text>
          </View>
          <Pill tone={statusTone(user.status)}>{statusLabel(user.status)}</Pill>
        </View>
        <Text style={styles.body}>Uloga: {user.role?.name ?? 'Bez uloge'}</Text>
        <Text style={styles.muted}>Grupa: {user.group?.name ?? 'Bez grupe'}</Text>
        <Text style={styles.muted}>Telefon: {user.phone ?? 'Nije unet'}</Text>
        <Text style={styles.muted}>Poslednja prijava: {formatDateTime(user.last_login_at)}</Text>
        {user.last_active_superadmin_protected ? (
          <Text style={styles.guard}>Poslednji aktivni SuperAdmin · zaštićen od degradiranja i blokiranja</Text>
        ) : null}
      </Card>
    </Pressable>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 140, gap: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    hero: { gap: spacing.md },
    heroTitle: { ...typography.h2, color: theme.ink },
    filtersCard: { gap: spacing.md },
    sectionTitle: { ...typography.h3, color: theme.ink },
    card: { gap: spacing.sm },
    cardTitle: { ...typography.h3, color: theme.ink },
    body: { ...typography.body, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    guard: { ...typography.small, color: theme.warning },
    identity: { flex: 1, gap: 2 },
    rowBetween: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    paginationCard: { gap: spacing.md },
    actionsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  });
}
