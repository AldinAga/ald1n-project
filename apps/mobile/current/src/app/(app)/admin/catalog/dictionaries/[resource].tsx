import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, type Href, useLocalSearchParams } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmAction } from '@/components/ui/confirm-action';
import { Pill } from '@/components/ui/pill';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminDictionaries,
  type AdminDictionaryDataType,
  type AdminDictionaryFilterType,
  type AdminDictionaryInput,
  type AdminDictionaryItem,
  type AdminDictionaryResource,
  type AdminDictionaryStatus,
} from '@/features/admin/dictionary-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

// MOBILE_V1_0_ADMIN_CATALOG_DICTIONARIES_BATCH22
const RESOURCE_LABELS: Record<AdminDictionaryResource, string> = {
  categories: 'Kategorije',
  'product-lines': 'Linije proizvoda',
  'product-types': 'Tipovi proizvoda',
  'specification-fields': 'Specifikaciona polja',
};

function parseResource(value: string | string[] | undefined): AdminDictionaryResource | null {
  const raw = Array.isArray(value) ? value[0] : value;
  return raw === 'categories' || raw === 'product-lines' || raw === 'product-types' || raw === 'specification-fields' ? raw : null;
}

function optionalNumber(value: string): number | null {
  if (!value.trim()) return null;
  const parsed = Number(value.replace(',', '.'));
  return Number.isFinite(parsed) ? parsed : null;
}

export default function AdminDictionaryResourceScreen() {
  const { resource: resourceParam } = useLocalSearchParams<{ resource?: string | string[] }> ();
  const resource = parseResource(resourceParam);
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { bootstrap, can } = useAuth();
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const allowed = can('catalog.manage_taxonomy');

  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null> (null);
  const [name, setName] = useState('');
  const [slug, setSlug] = useState('');
  const [status, setStatus] = useState<AdminDictionaryStatus> ('active');
  const [sortOrder, setSortOrder] = useState('0');
  const [parentId, setParentId] = useState('');
  const [description, setDescription] = useState('');
  const [brandId, setBrandId] = useState('');
  const [dataType, setDataType] = useState<AdminDictionaryDataType> ('text');
  const [filterType, setFilterType] = useState<AdminDictionaryFilterType> ('none');
  const [unit, setUnit] = useState('');
  const [placeholder, setPlaceholder] = useState('');
  const [helpText, setHelpText] = useState('');
  const [optionsText, setOptionsText] = useState('');
  const [minValue, setMinValue] = useState('');
  const [maxValue, setMaxValue] = useState('');
  const [parentFieldId, setParentFieldId] = useState('');
  const [dependencyMapText, setDependencyMapText] = useState('');
  const [detailInputEnabled, setDetailInputEnabled] = useState(false);
  const [detailLabel, setDetailLabel] = useState('');
  const [detailPlaceholder, setDetailPlaceholder] = useState('');
  const [deactivateTarget, setDeactivateTarget] = useState<AdminDictionaryItem | null> (null);
  const [purgeTarget, setPurgeTarget] = useState<AdminDictionaryItem | null> (null);
  const [purgeNames, setPurgeNames] = useState<Record<string, string>> ({});

  const listQuery = useQuery({
    queryKey: resource ? adminQueryKeys.dictionary(resource) : ['admin', 'catalog', 'dictionaries', 'invalid'],
    queryFn: () => apiAdminDictionaries.list(resource as AdminDictionaryResource),
    enabled: allowed && resource !== null,
  });

  const invalidate = async () => {
    if (!resource) return;
    await client.invalidateQueries({ queryKey: adminQueryKeys.dictionary(resource) });
  };

  const saveMutation = useMutation({
    mutationFn: ({ id, input }: { id: number | null; input: AdminDictionaryInput }) => {
      if (!resource) throw new Error('Nepoznat šifarnik.');
      return id === null ? apiAdminDictionaries.create(resource, input) : apiAdminDictionaries.update(resource, id, input);
    },
    onSuccess: async (response) => {
      feedback.notify({ tone: 'success', title: 'Sačuvano', message: response.message });
      closeForm();
      await invalidate();
    },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Izmena nije sačuvana', message: error instanceof Error ? error.message : 'Server je odbio izmenu.' }),
  });

  const deactivateMutation = useMutation({
    mutationFn: (itemId: number) => {
      if (!resource) throw new Error('Nepoznat šifarnik.');
      return apiAdminDictionaries.deactivate(resource, itemId);
    },
    onSuccess: async (response) => {
      setDeactivateTarget(null);
      feedback.notify({ tone: 'success', title: 'Deaktivirano', message: response.message });
      await invalidate();
    },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Deaktiviranje nije uspelo', message: error instanceof Error ? error.message : 'Server je odbio akciju.' }),
  });

  const reorderMutation = useMutation({
    mutationFn: (ids: number[]) => {
      if (!resource) throw new Error('Nepoznat šifarnik.');
      return apiAdminDictionaries.reorder(resource, ids);
    },
    onSuccess: async () => {
      feedback.notify({ tone: 'success', title: 'Raspored je sačuvan' });
      await invalidate();
    },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Raspored nije sačuvan', message: error instanceof Error ? error.message : 'Server je odbio raspored.' }),
  });

  const purgeMutation = useMutation({
    mutationFn: ({ id, confirmName }: { id: number; confirmName: string }) => apiAdminDictionaries.purgeSpecificationField(id, confirmName),
    onSuccess: async (response) => {
      setPurgeTarget(null);
      feedback.notify({ tone: 'success', title: 'Polje je trajno obrisano', message: response.message });
      await invalidate();
    },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Trajno brisanje nije uspelo', message: error instanceof Error ? error.message : 'Server je odbio trajno brisanje.' }),
  });

  if (!allowed) return <UnavailableState title="Upravljanje šifarnicima nije dostupno" />;
  if (!resource) return <UnavailableState title="Nepoznat šifarnik" />;
  if (listQuery.isLoading) return <LoadingState label="Učitavanje šifarnika…" />;
  if (listQuery.isError || !listQuery.data) return <ErrorState error={listQuery.error} onRetry={() => void listQuery.refetch()} />;

  const data = listQuery.data;

  function resetForm(): void {
    setEditingId(null);
    setName('');
    setSlug('');
    setStatus('active');
    setSortOrder('0');
    setParentId('');
    setDescription('');
    setBrandId('');
    setDataType('text');
    setFilterType('none');
    setUnit('');
    setPlaceholder('');
    setHelpText('');
    setOptionsText('');
    setMinValue('');
    setMaxValue('');
    setParentFieldId('');
    setDependencyMapText('');
    setDetailInputEnabled(false);
    setDetailLabel('');
    setDetailPlaceholder('');
  }

  function closeForm(): void {
    setFormOpen(false);
    resetForm();
  }

  function newItem(): void {
    resetForm();
    setFormOpen(true);
  }

  function editItem(item: AdminDictionaryItem): void {
    if (resource === 'product-types') {
      router.push(`/admin/catalog/dictionaries/product-types/${item.id}` as Href);
      return;
    }
    setEditingId(item.id);
    setName(item.name);
    setSlug(item.slug);
    setStatus(item.status);
    setSortOrder(String(item.sort_order));
    setParentId(item.parent_id ? String(item.parent_id) : '');
    setDescription(item.description ?? '');
    setBrandId(item.brand_id ? String(item.brand_id) : '');
    setDataType(item.data_type ?? 'text');
    setFilterType(item.filter_type ?? 'none');
    setUnit(item.unit ?? '');
    setPlaceholder(item.placeholder ?? '');
    setHelpText(item.help_text ?? '');
    setOptionsText(item.options_text ?? '');
    setMinValue(item.min_value === null || item.min_value === undefined ? '' : String(item.min_value));
    setMaxValue(item.max_value === null || item.max_value === undefined ? '' : String(item.max_value));
    setParentFieldId(item.parent_field_id ? String(item.parent_field_id) : '');
    setDependencyMapText(item.dependency_map_text ?? '');
    setDetailInputEnabled(Boolean(item.detail_input_enabled));
    setDetailLabel(item.detail_label ?? '');
    setDetailPlaceholder(item.detail_placeholder ?? '');
    setFormOpen(true);
  }

  function save(): void {
    const order = Number(sortOrder);
    if (!name.trim()) {
      feedback.notify({ tone: 'warning', title: 'Naziv je obavezan' });
      return;
    }
    if (!Number.isInteger(order) || order < 0 || order > 1000000) {
      feedback.notify({ tone: 'warning', title: 'Redosled nije validan', message: 'Unesi ceo broj od 0 do 1000000.' });
      return;
    }

    const input: AdminDictionaryInput = {
      name: name.trim(),
      slug: slug.trim() || null,
      status,
      sort_order: order,
    };

    if (resource === 'categories') {
      input.parent_id = parentId ? Number(parentId) : null;
      input.description = description.trim() || null;
    }
    if (resource === 'product-lines') {
      const parsedBrand = Number(brandId);
      if (!Number.isInteger(parsedBrand) || parsedBrand < 1) {
        feedback.notify({ tone: 'warning', title: 'Brend je obavezan' });
        return;
      }
      input.brand_id = parsedBrand;
    }
    if (resource === 'product-types') {
      input.description = description.trim() || null;
    }
    if (resource === 'specification-fields') {
      input.data_type = dataType;
      input.filter_type = filterType;
      input.unit = unit.trim() || null;
      input.placeholder = placeholder.trim() || null;
      input.help_text = helpText.trim() || null;
      input.options_text = dataType === 'select' ? optionsText : null;
      input.min_value = optionalNumber(minValue);
      input.max_value = optionalNumber(maxValue);
      input.parent_field_id = dataType === 'select' && parentFieldId ? Number(parentFieldId) : null;
      input.dependency_map_text = dataType === 'select' ? dependencyMapText.trim() || null : null;
      input.detail_input_enabled = dataType === 'select' && detailInputEnabled;
      input.detail_label = dataType === 'select' && detailInputEnabled ? detailLabel.trim() || null : null;
      input.detail_placeholder = dataType === 'select' && detailInputEnabled ? detailPlaceholder.trim() || null : null;
    }

    saveMutation.mutate({ id: editingId, input });
  }

  function move(itemId: number, direction: -1 | 1): void {
    const ids = data.items.map((item) => item.id);
    const index = ids.indexOf(itemId);
    const nextIndex = index + direction;
    if (index < 0 || nextIndex < 0 || nextIndex >= ids.length) return;
    // MOBILE_V1_0_BATCH22_V4_TYPESCRIPT_STRICT_REORDER
    const currentId = ids[index];
    const adjacentId = ids[nextIndex];
    if (currentId === undefined || adjacentId === undefined) return;
    ids[index] = adjacentId;
    ids[nextIndex] = currentId;
    reorderMutation.mutate(ids);
  }

  function requestPurge(item: AdminDictionaryItem): void {
    const typed = purgeNames[String(item.id)] ?? '';
    if (typed.trim() !== item.name.trim()) {
      feedback.notify({ tone: 'warning', title: 'Potvrda se ne podudara', message: `Upiši tačan naziv: ${item.name}` });
      return;
    }
    setPurgeTarget(item);
  }

  const parentCategoryOptions = [{ value: '', label: 'Bez roditelja' }, ...data.references.categories.filter((ref) => ref.id !== editingId).map((ref) => ({ value: String(ref.id), label: ref.name }))];
  const brandOptions = [{ value: '', label: 'Izaberi brend' }, ...data.references.brands.map((ref) => ({ value: String(ref.id), label: ref.name }))];
  const parentFieldOptions = [{ value: '', label: 'Nema roditeljskog polja' }, ...data.references.selectable_fields.filter((ref) => ref.id !== editingId).map((ref) => ({ value: String(ref.id), label: ref.name, detail: ref.slug }))];

  return (
    <Screen contentStyle={styles.content} keyboardShouldPersistTaps="handled">
      <Button variant="ghost" onPress={() => router.push('/admin/catalog/dictionaries' as Href)}>‹ Šifarnici</Button>
      <PageHeader title={RESOURCE_LABELS[resource]} eyebrow="Admin · Katalog · Šifarnici" name={bootstrap?.user.name} />
      <Text style={styles.copy}>Deaktiviranje čuva istorijske veze. Redosled menjaš akcijama gore/dole koje koriste isti server reorder ugovor kao CMS.</Text>

      <View style={styles.rowBetween}>
        <Text style={styles.sectionTitle}>{data.items.length} stavki</Text>
        <Button variant="secondary" onPress={newItem}>+ Dodaj</Button>
      </View>

      {formOpen ? (
        <Card style={styles.formCard}>
          <View style={styles.rowBetween}>
            <Text style={styles.sectionTitle}>{editingId === null ? 'Nova stavka' : 'Uredi stavku'}</Text>
            <Button variant="ghost" onPress={closeForm}>Zatvori</Button>
          </View>
          <TextField label="Naziv *" value={name} onChangeText={setName} maxLength={120} />
          <TextField label="Slug" value={slug} onChangeText={setSlug} autoCapitalize="none" placeholder="automatski" maxLength={140} />
          <SelectSheet label="Status" value={status} options={data.options.statuses} onChange={(value) => { if (value === 'active' || value === 'inactive') setStatus(value); }} />
          <TextField label="Redosled" value={sortOrder} onChangeText={setSortOrder} keyboardType="number-pad" />

          {resource === 'categories' ? (
            <>
              <SelectSheet label="Nadređena kategorija" value={parentId} options={parentCategoryOptions} onChange={setParentId} />
              <TextField label="Opis" value={description} onChangeText={setDescription} multiline />
            </>
          ) : null}

          {resource === 'product-lines' ? <SelectSheet label="Brend *" value={brandId} options={brandOptions} onChange={setBrandId} /> : null}

          {resource === 'product-types' ? (
            <>
              <Text style={styles.copy}>Sistem automatski pronalazi ili kreira kategoriju iz naziva tipa. Posle kreiranja otvori „Podesi tip“ za kompletnost, naziv i specifikacije.</Text>
              <TextField label="Opis" value={description} onChangeText={setDescription} multiline />
            </>
          ) : null}

          {resource === 'specification-fields' ? (
            <>
              <SelectSheet label="Tip podatka" value={dataType} options={data.options.data_types.map((value) => ({ value, label: value }))} onChange={(value) => { if (value === 'text' || value === 'integer' || value === 'decimal' || value === 'select' || value === 'boolean') setDataType(value); }} />
              <SelectSheet label="Tip filtera" value={filterType} options={data.options.filter_types.map((value) => ({ value, label: value }))} onChange={(value) => { if (value === 'none' || value === 'select' || value === 'range' || value === 'boolean' || value === 'text') setFilterType(value); }} />
              <TextField label="Jedinica" value={unit} onChangeText={setUnit} placeholder="npr. GB" />
              <TextField label="Placeholder" value={placeholder} onChangeText={setPlaceholder} />
              <TextField label="Minimum" value={minValue} onChangeText={setMinValue} keyboardType="decimal-pad" />
              <TextField label="Maksimum" value={maxValue} onChangeText={setMaxValue} keyboardType="decimal-pad" />
              {dataType === 'select' ? (
                <>
                  <TextField label="Opcije · jedna po redu" value={optionsText} onChangeText={setOptionsText} multiline />
                  <SelectSheet label="Zavisi od polja" value={parentFieldId} options={parentFieldOptions} onChange={setParentFieldId} />
                  <Button variant={detailInputEnabled ? 'secondary' : 'ghost'} onPress={() => setDetailInputEnabled((current) => !current)}>{detailInputEnabled ? 'Dodatno tekstualno polje ✓' : 'Uključi dodatno tekstualno polje'}</Button>
                  {detailInputEnabled ? (
                    <>
                      <TextField label="Naziv dodatnog polja" value={detailLabel} onChangeText={setDetailLabel} />
                      <TextField label="Placeholder dodatnog polja" value={detailPlaceholder} onChangeText={setDetailPlaceholder} />
                    </>
                  ) : null}
                  <TextField label="Korelacije opcija" value={dependencyMapText} onChangeText={setDependencyMapText} multiline placeholder={'Intel => Intel Core i3 | Intel Core i5\nAMD => AMD Ryzen 5 | AMD Ryzen 7'} />
                </>
              ) : null}
              <TextField label="Pomoćni tekst" value={helpText} onChangeText={setHelpText} />
            </>
          ) : null}

          <Button loading={saveMutation.isPending} onPress={save}>{editingId === null ? 'Kreiraj' : 'Sačuvaj izmene'}</Button>
        </Card>
      ) : null}

      {data.items.length === 0 ? <Card><Text style={styles.copy}>Nema stavki. Dodaj prvu stavku.</Text></Card> : null}

      {data.items.map((item, index) => (
        <Card key={item.id} style={styles.itemCard}>
          <View style={styles.rowBetween}>
            <View style={styles.flex}>
              <Text style={styles.itemName}>{item.name}</Text>
              <Text style={styles.meta}>{item.slug} · redosled {item.sort_order}</Text>
            </View>
            <Pill tone={item.status === 'active' ? 'success' : 'neutral'}>{item.status === 'active' ? 'Aktivno' : 'Neaktivno'}</Pill>
          </View>

          {resource === 'categories' ? <Text style={styles.meta}>Roditelj: {item.parent_id ? data.references.categories.find((ref) => ref.id === item.parent_id)?.name ?? `#${item.parent_id}` : 'Bez roditelja'}</Text> : null}
          {resource === 'product-lines' ? <Text style={styles.meta}>Brend: {item.brand?.name ?? '—'}</Text> : null}
          {resource === 'product-types' ? <Text style={styles.meta}>Kategorija: {item.category?.name ?? 'Automatska pri čuvanju'} · Specifikacije: {item.fields_count ?? 0} · Artikli: {item.products_count ?? 0}</Text> : null}
          {resource === 'specification-fields' ? (
            <>
              <Text style={styles.meta}>{item.data_type} · filter {item.filter_type}{item.unit ? ` · ${item.unit}` : ''}</Text>
              <Text style={styles.meta}>Tipovi: {item.usage?.types ?? 0} · Artikli: {item.usage?.products ?? 0} · Zavisna polja: {item.usage?.children ?? 0}</Text>
              {item.parent_field ? <Text style={styles.meta}>Zavisi od: {item.parent_field.name}</Text> : null}
            </>
          ) : null}

          <View style={styles.actions}>
            <Button variant="secondary" disabled={index === 0} loading={reorderMutation.isPending} onPress={() => move(item.id, -1)}>Pomeri gore</Button>
            <Button variant="secondary" disabled={index === data.items.length - 1} loading={reorderMutation.isPending} onPress={() => move(item.id, 1)}>Pomeri dole</Button>
            <Button variant="secondary" onPress={() => editItem(item)}>{resource === 'product-types' ? 'Podesi tip' : 'Uredi'}</Button>
            {item.status === 'active' ? <Button variant="danger" onPress={() => setDeactivateTarget(item)}>Deaktiviraj</Button> : null}
          </View>

          {resource === 'specification-fields' ? (
            <View style={styles.dangerZone}>
              <Text style={styles.dangerTitle}>Trajno brisanje</Text>
              <Text style={styles.copy}>Briše polje, vrednosti, veze opcija i dodelu tipovima. Upiši tačan naziv za potvrdu.</Text>
              <TextField label={`Tačan naziv: ${item.name}`} value={purgeNames[String(item.id)] ?? ''} onChangeText={(value) => setPurgeNames((current) => ({ ...current, [String(item.id)]: value }))} />
              <Button variant="danger" onPress={() => requestPurge(item)}>Obriši trajno</Button>
            </View>
          ) : null}
        </Card>
      ))}

      <ConfirmAction
        visible={deactivateTarget !== null}
        title="Deaktivirati stavku?"
        message={deactivateTarget ? `Deaktiviraš „${deactivateTarget.name}“. Istorijske veze ostaju sačuvane.` : ''}
        confirmLabel="Deaktiviraj"
        destructive
        busy={deactivateMutation.isPending}
        onCancel={() => setDeactivateTarget(null)}
        onConfirm={() => { if (deactivateTarget) deactivateMutation.mutate(deactivateTarget.id); }}
      />
      <ConfirmAction
        visible={purgeTarget !== null}
        title="Trajno obrisati specifikaciono polje?"
        message={purgeTarget ? `Ovo trajno briše „${purgeTarget.name}“ i povezane vrednosti. Akcija koristi isti bezbedni lifecycle servis kao CMS.` : ''}
        confirmLabel="Obriši trajno"
        destructive
        busy={purgeMutation.isPending}
        onCancel={() => setPurgeTarget(null)}
        onConfirm={() => { if (purgeTarget) purgeMutation.mutate({ id: purgeTarget.id, confirmName: purgeNames[String(purgeTarget.id)] ?? '' }); }}
      />
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: spacing.xxl },
    formCard: { gap: spacing.md },
    itemCard: { gap: spacing.md },
    rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: spacing.md },
    flex: { flex: 1, minWidth: 0 },
    sectionTitle: { ...typography.h3, color: theme.ink },
    itemName: { ...typography.h3, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
    meta: { ...typography.small, color: theme.muted },
    actions: { gap: spacing.sm },
    dangerZone: { gap: spacing.sm, borderTopWidth: 1, borderTopColor: theme.line, paddingTop: spacing.md },
    dangerTitle: { ...typography.label, color: theme.danger },
  });
}
