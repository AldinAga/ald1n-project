import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Pill } from '@/components/ui/pill';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminBrands,
  type AdminBrandInput,
  type AdminBrandProductType,
  type AdminManagedBrand,
} from '@/features/admin/brand-manager-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

// MOBILE_V0_9_GLOBAL_BRAND_MANAGER_BATCH3
export default function AdminBrandsScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { bootstrap, can } = useAuth();
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const allowed = can('catalog.manage_taxonomy');

  const [draftQ, setDraftQ] = useState('');
  const [draftType, setDraftType] = useState('');
  const [filters, setFilters] = useState<{ q?: string; product_type_id?: number }> ({});
  const [editingId, setEditingId] = useState<number | null> (null);
  const [formOpen, setFormOpen] = useState(false);
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [websiteUrl, setWebsiteUrl] = useState('');
  const [status, setStatus] = useState<'active' | 'inactive'> ('active');
  const [sortOrder, setSortOrder] = useState('100');
  const [selectedTypes, setSelectedTypes] = useState<number[]> ([]);
  const [lineNames, setLineNames] = useState<Record<string, string[]>> ({});

  const listQuery = useQuery({
    queryKey: adminQueryKeys.brandsList(filters),
    queryFn: () => apiAdminBrands.list(filters),
    enabled: allowed,
  });
  const optionsQuery = useQuery({
    queryKey: adminQueryKeys.brandOptions(),
    queryFn: apiAdminBrands.options,
    enabled: allowed,
  });

  const mutation = useMutation({
    mutationFn: ({ id, input }: { id: number | null; input: AdminBrandInput }) =>
      id === null ? apiAdminBrands.create(input) : apiAdminBrands.update(id, input),
    onSuccess: async (response) => {
      feedback.notify({ tone: 'success', title: 'Brend je sačuvan', message: response.data.name });
      closeForm();
      await Promise.all([
        client.invalidateQueries({ queryKey: ['admin', 'brands'] }),
        client.invalidateQueries({ queryKey: ['admin', 'catalog'] }),
      ]);
    },
    onError: (error) => feedback.notify({
      tone: 'danger',
      title: 'Brend nije sačuvan',
      message: error instanceof Error ? error.message : 'Server je odbio izmenu.',
    }),
  });

  if (!allowed) return <UnavailableState title="Upravljanje brendovima nije dostupno" />;
  if (listQuery.isLoading || optionsQuery.isLoading) return <LoadingState label="Učitavanje brendova…" />;
  if (listQuery.isError || !listQuery.data) return <ErrorState error={listQuery.error} onRetry={() => void listQuery.refetch()} />;
  if (optionsQuery.isError || !optionsQuery.data) return <ErrorState error={optionsQuery.error} onRetry={() => void optionsQuery.refetch()} />;

  const data = listQuery.data.data;
  const options = optionsQuery.data.data;
  const typeOptions = [
    { value: '', label: 'Svi tipovi' },
    ...options.product_types.map((type) => ({
      value: String(type.id),
      label: type.name,
      detail: type.category_name ?? 'Bez kategorije',
    })),
  ];

  function applyFilters(): void {
    const next: { q?: string; product_type_id?: number } = {};
    if (draftQ.trim()) next.q = draftQ.trim();
    const typeId = Number(draftType);
    if (Number.isInteger(typeId) && typeId > 0) next.product_type_id = typeId;
    setFilters(next);
  }

  function clearFilters(): void {
    setDraftQ('');
    setDraftType('');
    setFilters({});
  }

  function resetForm(): void {
    setEditingId(null);
    setName('');
    setDescription('');
    setWebsiteUrl('');
    setStatus('active');
    setSortOrder('100');
    setSelectedTypes([]);
    setLineNames({});
  }

  function closeForm(): void {
    setFormOpen(false);
    resetForm();
  }

  function newBrand(): void {
    resetForm();
    setFormOpen(true);
  }

  function editBrand(brand: AdminManagedBrand): void {
    const nextLines: Record<string, string[]> = {};
    for (const typeId of brand.product_type_ids) {
      nextLines[String(typeId)] = (brand.line_groups[String(typeId)] ?? []).slice(0, 3).map((line) => line.name);
    }
    setEditingId(brand.id);
    setName(brand.name);
    setDescription(brand.description ?? '');
    setWebsiteUrl(brand.website_url ?? '');
    setStatus(brand.status);
    setSortOrder(String(brand.sort_order));
    setSelectedTypes([...brand.product_type_ids]);
    setLineNames(nextLines);
    setFormOpen(true);
  }

  function toggleType(typeId: number): void {
    setSelectedTypes((current) => current.includes(typeId)
      ? current.filter((id) => id !== typeId)
      : [...current, typeId].sort((a, b) => a - b));
  }

  function setLine(typeId: number, slot: number, value: string): void {
    setLineNames((current) => {
      const next = [...(current[String(typeId)] ?? ['', '', ''])];
      while (next.length < 3) next.push('');
      next[slot] = value;
      return { ...current, [String(typeId)]: next.slice(0, 3) };
    });
  }

  function save(): void {
    const order = Number(sortOrder);
    if (!name.trim()) {
      feedback.notify({ tone: 'warning', title: 'Naziv je obavezan', message: 'Unesi naziv globalnog brenda.' });
      return;
    }
    if (selectedTypes.length === 0) {
      feedback.notify({ tone: 'warning', title: 'Izaberi tip', message: 'Brend mora pripadati najmanje jednom tipu proizvoda.' });
      return;
    }
    if (!Number.isInteger(order) || order < 0 || order > 1000000) {
      feedback.notify({ tone: 'warning', title: 'Redosled nije validan', message: 'Unesi ceo broj od 0 do 1000000.' });
      return;
    }
    if (websiteUrl.trim() && !/^https?:\/\//i.test(websiteUrl.trim())) {
      feedback.notify({ tone: 'warning', title: 'Website nije validan', message: 'Koristi pun http:// ili https:// URL.' });
      return;
    }

    const line_names_by_type: Record<string, string[]> = {};
    for (const typeId of selectedTypes) {
      line_names_by_type[String(typeId)] = (lineNames[String(typeId)] ?? [])
        .map((value) => value.trim())
        .filter(Boolean)
        .slice(0, 3);
    }

    mutation.mutate({
      id: editingId,
      input: {
        name: name.trim(),
        description: description.trim() || null,
        website_url: websiteUrl.trim() || null,
        status,
        sort_order: order,
        product_type_ids: [...selectedTypes].sort((a, b) => a - b),
        line_names_by_type,
      },
    });
  }

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Administracija</Text>
      </Pressable>

      <PageHeader title="Brendovi" eyebrow="Admin · Katalog" name={bootstrap?.user.name} />
      <Text style={styles.copy}>
        Globalni Brand Manager za sve tipove proizvoda. Jedan brend može pripadati više tipova, a linije ostaju vezane samo za relevantne tipove.
      </Text>

      <Card style={styles.filtersCard}>
        <View style={styles.rowBetween}>
          <View style={styles.flex}>
            <Text style={styles.sectionTitle}>Pretraga i filter</Text>
            <Text style={styles.muted}>{data.brands.length} prikazano</Text>
          </View>
          <Button variant="secondary" onPress={newBrand}>+ Dodaj brend</Button>
        </View>
        <TextField label="Pretraga brenda" value={draftQ} onChangeText={setDraftQ} placeholder="Naziv, opis ili website" />
        <SelectSheet label="Tip / kategorija" value={draftType} options={typeOptions} onChange={setDraftType} />
        <View style={styles.actions}>
          <Button onPress={applyFilters}>Primeni filtere</Button>
          {(draftQ || draftType) ? <Button variant="secondary" onPress={clearFilters}>Očisti</Button> : null}
        </View>
      </Card>

      {formOpen ? (
        <Card style={styles.formCard}>
          <View style={styles.rowBetween}>
            <View style={styles.flex}>
              <Text style={styles.sectionTitle}>{editingId === null ? 'Novi brend' : 'Uredi brend'}</Text>
              <Text style={styles.muted}>Slug je automatski. Do tri kurirane linije po tipu.</Text>
            </View>
            <Button variant="secondary" onPress={closeForm}>Zatvori</Button>
          </View>
          <TextField label="Naziv" value={name} onChangeText={setName} placeholder="npr. Samsung" />
          <TextField label="Zvanični website" value={websiteUrl} onChangeText={setWebsiteUrl} autoCapitalize="none" keyboardType="url" placeholder="https://…" />
          <TextField label="Opis na srpskom" value={description} onChangeText={setDescription} multiline placeholder="Kratak opis proizvođača" />
          <SelectSheet
            label="Status"
            value={status}
            options={options.statuses}
            onChange={(value) => { if (value === 'active' || value === 'inactive') setStatus(value); }}
          />
          <TextField label="Redosled" value={sortOrder} onChangeText={setSortOrder} keyboardType="number-pad" />

          <Text style={styles.subhead}>Povezani tipovi i linije</Text>
          {options.product_types.map((type) => (
            <TypeEditor
              key={type.id}
              type={type}
              selected={selectedTypes.includes(type.id)}
              lines={lineNames[String(type.id)] ?? []}
              onToggle={() => toggleType(type.id)}
              onLine={(slot, value) => setLine(type.id, slot, value)}
              styles={styles}
            />
          ))}
          <Button onPress={save}>{editingId === null ? 'Kreiraj brend' : 'Sačuvaj izmene'}</Button>
        </Card>
      ) : null}

      <View style={styles.rowBetween}>
        <Text style={styles.sectionTitle}>Globalni brendovi</Text>
        <Button variant="secondary" onPress={() => void listQuery.refetch()}>{listQuery.isFetching ? 'Osvežavanje…' : 'Osveži'}</Button>
      </View>

      {data.brands.length === 0 ? (
        <Card><Text style={styles.muted}>Nema brendova za izabrane filtere.</Text></Card>
      ) : data.brands.map((brand) => (
        <Card key={brand.id} style={styles.brandCard}>
          <View style={styles.rowBetween}>
            <View style={styles.flex}>
              <Text style={styles.brandName}>{brand.name}</Text>
              <Text style={styles.muted}>{brand.slug}</Text>
            </View>
            <Pill tone={brand.status === 'active' ? 'success' : 'neutral'}>{brand.status === 'active' ? 'Aktivan' : 'Neaktivan'}</Pill>
          </View>
          {brand.description ? <Text style={styles.description}>{brand.description}</Text> : null}
          <Text style={styles.meta}>Tipovi: {brand.product_types.map((type) => type.name).join(', ') || '—'}</Text>
          <Text style={styles.meta}>Linije: {brand.lines.length ? brand.lines.slice(0, 6).map((line) => line.name).join(', ') : '—'}{brand.lines.length > 6 ? '…' : ''}</Text>
          {brand.website_url ? <Text style={styles.meta}>{brand.website_url}</Text> : null}
          <View style={styles.actions}>
            <Button variant="secondary" onPress={() => editBrand(brand)}>Uredi</Button>
          </View>
        </Card>
      ))}
    </Screen>
  );
}

function TypeEditor({
  type,
  selected,
  lines,
  onToggle,
  onLine,
  styles,
}: {
  type: AdminBrandProductType;
  selected: boolean;
  lines: string[];
  onToggle: () => void;
  onLine: (slot: number, value: string) => void;
  styles: ReturnType<typeof createStyles>;
}) {
  return (
    <Card style={styles.typeCard}>
      <View style={styles.rowBetween}>
        <View style={styles.flex}>
          <Text style={styles.typeName}>{type.name}</Text>
          <Text style={styles.muted}>{type.category_name ?? 'Bez kategorije'}</Text>
        </View>
        <Button variant="secondary" onPress={onToggle}>{selected ? 'Uključeno ✓' : 'Uključi'}</Button>
      </View>
      {selected ? [0, 1, 2].map((slot) => (
        <TextField
          key={slot}
          label={`Linija ${slot + 1}`}
          value={lines[slot] ?? ''}
          onChangeText={(value) => onLine(slot, value)}
          placeholder="Opcionalno"
        />
      )) : null}
    </Card>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: spacing.xl },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    copy: { ...typography.body, color: theme.muted },
    filtersCard: { gap: spacing.md },
    formCard: { gap: spacing.md },
    brandCard: { gap: spacing.sm },
    typeCard: { gap: spacing.sm },
    rowBetween: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    flex: { flex: 1, minWidth: 0 },
    actions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    sectionTitle: { ...typography.h3, color: theme.ink },
    subhead: { ...typography.label, color: theme.ink, marginTop: spacing.sm },
    brandName: { ...typography.h3, color: theme.ink },
    typeName: { ...typography.label, color: theme.ink },
    description: { ...typography.body, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    meta: { ...typography.small, color: theme.muted },
  });
}
