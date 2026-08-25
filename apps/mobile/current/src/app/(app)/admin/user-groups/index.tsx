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
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
import { Pill } from '@/components/ui/pill';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminUserGroups,
  type AdminUserGroupCategoryMode,
  type AdminUserGroupInput,
  type AdminUserGroupListParams,
  type AdminUserGroupRecord,
  type AdminUserGroupStatus,
} from '@/features/admin/user-groups-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { ApiError } from '@/lib/api/client';
import { useAppTheme } from '@/theme/app-theme';

type Draft = {
  id: number | null;
  name: string;
  slug: string;
  description: string;
  status: AdminUserGroupStatus;
  categoryMode: AdminUserGroupCategoryMode;
  includeUncategorized: boolean;
  sortOrder: string;
  permissionIds: number[];
  categoryIds: number[];
};

const EMPTY_DRAFT: Draft = {
  id: null,
  name: '',
  slug: '',
  description: '',
  status: 'active',
  categoryMode: 'all',
  includeUncategorized: true,
  sortOrder: '0',
  permissionIds: [],
  categoryIds: [],
};

function fromGroup(group: AdminUserGroupRecord): Draft {
  return {
    id: group.id,
    name: group.name,
    slug: group.slug,
    description: group.description ?? '',
    status: group.status,
    categoryMode: group.category_access_mode,
    includeUncategorized: group.include_uncategorized,
    sortOrder: String(group.sort_order),
    permissionIds: group.permissions.map((permission) => permission.id),
    categoryIds: group.categories.map((category) => category.id),
  };
}

function toInput(draft: Draft): AdminUserGroupInput {
  return {
    name: draft.name.trim(),
    slug: draft.slug.trim() || null,
    description: draft.description.trim() || null,
    status: draft.status,
    category_access_mode: draft.categoryMode,
    include_uncategorized: draft.includeUncategorized,
    sort_order: Number.parseInt(draft.sortOrder, 10) || 0,
    permissions: draft.permissionIds,
    categories: draft.categoryIds,
  };
}

function apiFieldErrors(error: unknown): Record<string, string> {
  if (!(error instanceof ApiError)) return {};
  const result: Record<string, string> = {};
  for (const [field, messages] of Object.entries(error.errors)) {
    if (messages[0]) result[field] = messages[0];
  }
  return result;
}

function toggleId(values: number[], id: number): number[] {
  return values.includes(id) ? values.filter((value) => value !== id) : [...values, id];
}

// MOBILE_V1_0_USER_GROUPS_PARITY_BATCH34
export default function AdminUserGroupsScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const feedback = useAppFeedback();
  const queryClient = useQueryClient();
  const allowed = can('system.manage_users');

  const [draftQuery, setDraftQuery] = useState('');
  const [draftStatus, setDraftStatus] = useState<AdminUserGroupStatus | ''>('');
  const [applied, setApplied] = useState<AdminUserGroupListParams>({});
  const [editorOpen, setEditorOpen] = useState(false);
  const [draft, setDraft] = useState<Draft>(EMPTY_DRAFT);
  const [serverErrors, setServerErrors] = useState<Record<string, string>>({});
  const [permissionSearch, setPermissionSearch] = useState('');
  const [deleteTarget, setDeleteTarget] = useState<AdminUserGroupRecord | null>(null);

  const query = useQuery({
    queryKey: adminQueryKeys.userGroups(applied),
    queryFn: () => apiAdminUserGroups.list(applied),
    enabled: allowed,
  });

  const saveMutation = useMutation({
    mutationFn: (input: AdminUserGroupInput) => draft.id === null
      ? apiAdminUserGroups.create(input)
      : apiAdminUserGroups.update(draft.id, input),
    onSuccess: async (response) => {
      await queryClient.invalidateQueries({ queryKey: adminQueryKeys.userGroupsRoot() });
      feedback.notify({ tone: 'success', title: 'Grupa je sačuvana', message: response.data.name });
      setEditorOpen(false);
      setDraft(EMPTY_DRAFT);
      setServerErrors({});
    },
    onError: (error) => {
      setServerErrors(apiFieldErrors(error));
      feedback.notify({
        tone: 'danger',
        title: 'Grupa nije sačuvana',
        message: error instanceof Error ? error.message : 'Server je odbio izmenu.',
      });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (groupId: number) => apiAdminUserGroups.remove(groupId),
    onSuccess: async (response) => {
      await queryClient.invalidateQueries({ queryKey: adminQueryKeys.userGroupsRoot() });
      feedback.notify({ tone: 'success', title: 'Grupa je obrisana', message: response.message });
      setDeleteTarget(null);
    },
    onError: (error) => {
      feedback.notify({
        tone: 'danger',
        title: 'Grupa nije obrisana',
        message: error instanceof Error ? error.message : 'Brisanje nije dozvoljeno.',
      });
      setDeleteTarget(null);
    },
  });

  if (!allowed) return <UnavailableState title="Grupe pristupa nisu dostupne" />;
  if (query.isLoading) return <LoadingState label="Učitavanje grupa pristupa…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const response = query.data;
  const visiblePermissions = response.options.permissions.filter((permission) => {
    const needle = permissionSearch.trim().toLocaleLowerCase('sr');
    if (!needle) return true;
    return `${permission.name} ${permission.slug} ${permission.description ?? ''}`.toLocaleLowerCase('sr').includes(needle);
  });

  const openCreate = () => {
    setDraft(EMPTY_DRAFT);
    setServerErrors({});
    setPermissionSearch('');
    setEditorOpen(true);
  };

  const openEdit = (group: AdminUserGroupRecord) => {
    setDraft(fromGroup(group));
    setServerErrors({});
    setPermissionSearch('');
    setEditorOpen(true);
  };

  const applyFilters = () => {
    const next: AdminUserGroupListParams = {};
    if (draftQuery.trim()) next.q = draftQuery.trim();
    if (draftStatus) next.status = draftStatus;
    setApplied(next);
  };

  const clearFilters = () => {
    setDraftQuery('');
    setDraftStatus('');
    setApplied({});
  };

  const activeFilterCount = Number(Boolean(draftQuery.trim())) + Number(Boolean(draftStatus));

  return (
    <Screen contentStyle={styles.content} keyboardShouldPersistTaps="handled">
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Administracija</Text>
      </Pressable>

      <PageHeader title="Grupe pristupa" eyebrow="Admin · Pristup i ovlašćenja" name={bootstrap?.user.name} />

      <Card style={styles.hero}>
        <Text style={styles.heroTitle}>Permission paketi i kataloški scope</Text>
        <Text style={styles.muted}>
          Isti Laravel model upravlja dozvolama, pristupom kategorijama i dodeljenim korisnicima na Web i Mobile interfejsu.
        </Text>
        {response.capabilities.create ? <Button variant="secondary" onPress={openCreate}>Nova grupa pristupa</Button> : null}
      </Card>

      <Card style={styles.filtersCard}>
        <View style={styles.rowBetween}>
          <Text style={styles.sectionTitle}>Filteri</Text>
          <Text style={styles.muted}>{activeFilterCount} aktivnih</Text>
        </View>
        <FilterBar activeCount={activeFilterCount} onClear={clearFilters}>
          {response.options.statuses.map((option) => (
            <FilterChip
              key={option.value}
              label={option.label}
              active={draftStatus === option.value}
              onPress={() => setDraftStatus(draftStatus === option.value ? '' : option.value)}
            />
          ))}
        </FilterBar>
        <TextField label="Pretraga" value={draftQuery} onChangeText={setDraftQuery} placeholder="Naziv, slug ili opis" />
        <Button onPress={applyFilters}>Primeni filtere</Button>
      </Card>

      <View style={styles.rowBetween}>
        <View>
          <Text style={styles.sectionTitle}>Konfigurisane grupe</Text>
          <Text style={styles.muted}>{response.data.length} prikazano</Text>
        </View>
        <Button variant="secondary" onPress={() => void query.refetch()}>{query.isFetching ? 'Osvežavanje…' : 'Osveži'}</Button>
      </View>

      {response.data.length === 0 ? (
        <Card style={styles.card}><Text style={styles.muted}>Nema grupa koje odgovaraju filterima.</Text></Card>
      ) : response.data.map((group) => (
        <Card key={group.id} style={styles.card}>
          <View style={styles.rowBetween}>
            <View style={styles.flexOne}>
              <Text style={styles.cardTitle}>{group.name}</Text>
              <Text style={styles.muted}>{group.description ?? 'Bez dodatnog opisa.'}</Text>
            </View>
            <Pill tone={group.status === 'active' ? 'success' : 'neutral'}>{group.status === 'active' ? 'AKTIVNA' : 'NEAKTIVNA'}</Pill>
          </View>
          <Text style={styles.body}>Slug: {group.slug}</Text>
          <Text style={styles.muted}>{group.users_count} korisnika · {group.permissions.length} dozvola · {group.categories.length} kategorija</Text>
          <Text style={styles.muted}>Scope: {response.options.category_access_modes.find((mode) => mode.value === group.category_access_mode)?.label ?? group.category_access_mode} · redosled {group.sort_order}</Text>
          <View style={styles.actionsRow}>
            {response.capabilities.update ? <Button variant="secondary" onPress={() => openEdit(group)}>Uredi</Button> : null}
            {response.capabilities.delete_empty ? (
              <Button variant="danger" disabled={!group.can_delete} onPress={() => setDeleteTarget(group)}>
                {group.can_delete ? 'Obriši' : 'Ima korisnike'}
              </Button>
            ) : null}
          </View>
        </Card>
      ))}

      {editorOpen ? (
        <Card style={styles.editorCard}>
          <View style={styles.rowBetween}>
            <Text style={styles.sectionTitle}>{draft.id === null ? 'Nova grupa' : 'Uredi grupu'}</Text>
            <Button variant="ghost" onPress={() => setEditorOpen(false)}>Zatvori</Button>
          </View>

          <TextField label="Naziv grupe" value={draft.name} onChangeText={(name) => setDraft((current) => ({ ...current, name }))} error={serverErrors.name} />
          <TextField label="Slug" value={draft.slug} onChangeText={(slug) => setDraft((current) => ({ ...current, slug }))} placeholder="Automatski ako ostane prazno" error={serverErrors.slug} autoCapitalize="none" />
          <TextField label="Opis" value={draft.description} onChangeText={(description) => setDraft((current) => ({ ...current, description }))} error={serverErrors.description} />
          <TextField label="Redosled" value={draft.sortOrder} onChangeText={(sortOrder) => setDraft((current) => ({ ...current, sortOrder }))} keyboardType="number-pad" error={serverErrors.sort_order} />
          <SelectSheet
            label="Status"
            value={draft.status}
            options={response.options.statuses}
            onChange={(value) => {
              if (value === 'active' || value === 'inactive') setDraft((current) => ({ ...current, status: value }));
            }}
          />
          <SelectSheet
            label="Pristup kategorijama"
            value={draft.categoryMode}
            options={response.options.category_access_modes}
            onChange={(value) => {
              if (value === 'all' || value === 'selected' || value === 'none') setDraft((current) => ({ ...current, categoryMode: value }));
            }}
          />

          <Pressable style={styles.toggleRow} accessibilityRole="checkbox" accessibilityState={{ checked: draft.includeUncategorized }} onPress={() => setDraft((current) => ({ ...current, includeUncategorized: !current.includeUncategorized }))}>
            <Pill tone={draft.includeUncategorized ? 'success' : 'neutral'}>{draft.includeUncategorized ? 'DA' : 'NE'}</Pill>
            <View style={styles.flexOne}>
              <Text style={styles.body}>Uključi nekategorisane artikle</Text>
              <Text style={styles.muted}>Važi u skladu sa postojećom logikom kataloškog scope-a.</Text>
            </View>
          </Pressable>

          <View style={styles.editorSection}>
            <View style={styles.rowBetween}>
              <Text style={styles.sectionTitle}>Dozvole</Text>
              <Text style={styles.muted}>{draft.permissionIds.length} izabrano</Text>
            </View>
            <TextField label="Pronađi dozvolu" value={permissionSearch} onChangeText={setPermissionSearch} placeholder="Naziv, opis ili tehnički ključ" />
            <View style={styles.actionsRow}>
              <Button variant="ghost" onPress={() => setDraft((current) => ({ ...current, permissionIds: response.options.permissions.map((permission) => permission.id) }))}>Izaberi sve</Button>
              <Button variant="ghost" onPress={() => setDraft((current) => ({ ...current, permissionIds: [] }))}>Poništi</Button>
            </View>
            {visiblePermissions.map((permission) => {
              const selected = draft.permissionIds.includes(permission.id);
              return (
                <Pressable key={permission.id} style={styles.optionRow} accessibilityRole="checkbox" accessibilityState={{ checked: selected }} onPress={() => setDraft((current) => ({ ...current, permissionIds: toggleId(current.permissionIds, permission.id) }))}>
                  <Pill tone={selected ? 'primary' : 'neutral'}>{selected ? '✓' : '—'}</Pill>
                  <View style={styles.flexOne}>
                    <Text style={styles.body}>{permission.name}</Text>
                    <Text style={styles.muted}>{permission.description ?? permission.slug}</Text>
                    <Text style={styles.technical}>{permission.slug}</Text>
                  </View>
                </Pressable>
              );
            })}
          </View>

          {draft.categoryMode === 'selected' ? (
            <View style={styles.editorSection}>
              <View style={styles.rowBetween}>
                <Text style={styles.sectionTitle}>Kategorije</Text>
                <Text style={styles.muted}>{draft.categoryIds.length} izabrano</Text>
              </View>
              {response.options.categories.map((category) => {
                const selected = draft.categoryIds.includes(category.id);
                return (
                  <Pressable key={category.id} style={styles.optionRow} accessibilityRole="checkbox" accessibilityState={{ checked: selected }} onPress={() => setDraft((current) => ({ ...current, categoryIds: toggleId(current.categoryIds, category.id) }))}>
                    <Pill tone={selected ? 'primary' : 'neutral'}>{selected ? '✓' : '—'}</Pill>
                    <Text style={styles.body}>{category.name}</Text>
                  </Pressable>
                );
              })}
            </View>
          ) : null}

          <Button loading={saveMutation.isPending} onPress={() => {
            setServerErrors({});
            saveMutation.mutate(toInput(draft));
          }}>{draft.id === null ? 'Kreiraj grupu' : 'Sačuvaj izmene'}</Button>
        </Card>
      ) : null}

      <ConfirmAction
        visible={deleteTarget !== null}
        title="Obriši grupu pristupa?"
        message={deleteTarget ? `Grupa „${deleteTarget.name}” može se obrisati samo ako nema nijednog korisnika.` : ''}
        confirmLabel="Obriši grupu"
        destructive
        busy={deleteMutation.isPending}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={() => {
          if (deleteTarget) deleteMutation.mutate(deleteTarget.id);
        }}
      />
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 160, gap: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    hero: { gap: spacing.md },
    heroTitle: { ...typography.h2, color: theme.ink },
    filtersCard: { gap: spacing.md },
    sectionTitle: { ...typography.h3, color: theme.ink },
    card: { gap: spacing.sm },
    cardTitle: { ...typography.h3, color: theme.ink },
    body: { ...typography.body, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    technical: { ...typography.small, color: theme.primary },
    flexOne: { flex: 1, gap: spacing.xs },
    rowBetween: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    actionsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    editorCard: { gap: spacing.lg },
    editorSection: { gap: spacing.sm },
    optionRow: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.sm, paddingVertical: spacing.sm },
    toggleRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingVertical: spacing.sm },
  });
}
