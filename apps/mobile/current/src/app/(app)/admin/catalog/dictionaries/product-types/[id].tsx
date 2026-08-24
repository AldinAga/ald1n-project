import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, type Href, useLocalSearchParams } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

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
  apiAdminDictionaries,
  type AdminDictionaryInput,
  type AdminDictionaryStatus,
  type AdminProductTypeField,
  type AdminProductTypeFieldConfigInput,
} from '@/features/admin/dictionary-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

// MOBILE_V1_0_ADMIN_CATALOG_DICTIONARIES_BATCH22
function positiveInt(value: string, fallback: number, min: number, max: number): number {
  const parsed = Number(value);
  if (!Number.isInteger(parsed)) return fallback;
  return Math.max(min, Math.min(max, parsed));
}

export default function AdminProductTypeDictionaryDetailScreen() {
  const params = useLocalSearchParams<{ id?: string | string[] }> ();
  const rawId = Array.isArray(params.id) ? params.id[0] : params.id;
  const productTypeId = Number(rawId);
  const validId = Number.isInteger(productTypeId) && productTypeId > 0;
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { bootstrap, can } = useAuth();
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const allowed = can('catalog.manage_taxonomy');

  const [name, setName] = useState('');
  const [slug, setSlug] = useState('');
  const [description, setDescription] = useState('');
  const [status, setStatus] = useState<AdminDictionaryStatus> ('active');
  const [sortOrder, setSortOrder] = useState('0');
  const [defaultProductStatus, setDefaultProductStatus] = useState<'draft' | 'active' | 'inactive'> ('draft');
  const [minimumCompleteness, setMinimumCompleteness] = useState('0');
  const [autoNameEnabled, setAutoNameEnabled] = useState(false);
  const [nameTemplate, setNameTemplate] = useState('');
  const [requiredCoreFields, setRequiredCoreFields] = useState<string[]> ([]);
  const [fieldConfig, setFieldConfig] = useState<Record<string, AdminProductTypeFieldConfigInput>> ({});
  const [hydratedId, setHydratedId] = useState<number | null> (null);

  const query = useQuery({
    queryKey: validId ? adminQueryKeys.dictionaryProductType(productTypeId) : ['admin', 'catalog', 'dictionaries', 'product-types', 'invalid'],
    queryFn: () => apiAdminDictionaries.productType(productTypeId),
    enabled: allowed && validId,
  });

  const data = query.data;
  useEffect(() => {
    if (!data || hydratedId === data.product_type.id) return;
    const item = data.product_type;
    setName(item.name);
    setSlug(item.slug);
    setDescription(item.description ?? '');
    setStatus(item.status);
    setSortOrder(String(item.sort_order));
    setDefaultProductStatus(item.default_product_status);
    setMinimumCompleteness(String(item.minimum_completeness_percent));
    setAutoNameEnabled(item.auto_name_enabled);
    setNameTemplate(item.name_template ?? '');
    setRequiredCoreFields([...item.required_core_fields]);
    const next: Record<string, AdminProductTypeFieldConfigInput> = {};
    for (const field of data.fields) {
      next[String(field.id)] = {
        enabled: field.enabled,
        is_required: field.is_required,
        is_filterable: field.is_filterable,
        show_in_summary: field.show_in_summary,
        include_in_name: field.include_in_name,
        sort_order: field.sort_order,
        completeness_weight: field.completeness_weight,
        default_value: field.default_value,
        default_detail: field.default_detail,
      };
    }
    setFieldConfig(next);
    setHydratedId(item.id);
  }, [data, hydratedId]);

  const invalidate = async () => {
    await Promise.all([
      client.invalidateQueries({ queryKey: adminQueryKeys.dictionaryProductType(productTypeId) }),
      client.invalidateQueries({ queryKey: adminQueryKeys.dictionary('product-types') }),
    ]);
  };

  const saveMutation = useMutation({
    mutationFn: (input: AdminDictionaryInput) => apiAdminDictionaries.update('product-types', productTypeId, input),
    onSuccess: async (response) => {
      feedback.notify({ tone: 'success', title: 'Tip proizvoda je sačuvan', message: response.message });
      setHydratedId(null);
      await invalidate();
    },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Tip nije sačuvan', message: error instanceof Error ? error.message : 'Server je odbio izmenu.' }),
  });

  const reorderMutation = useMutation({
    mutationFn: (ids: number[]) => apiAdminDictionaries.reorderProductTypeFields(productTypeId, ids),
    onSuccess: async () => {
      feedback.notify({ tone: 'success', title: 'Raspored specifikacija je sačuvan' });
      setHydratedId(null);
      await invalidate();
    },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Raspored nije sačuvan', message: error instanceof Error ? error.message : 'Server je odbio raspored.' }),
  });

  if (!allowed) return <UnavailableState title="Podešavanje tipa nije dostupno" />;
  if (!validId) return <UnavailableState title="Nepoznat tip proizvoda" />;
  if (query.isLoading) return <LoadingState label="Učitavanje tipa proizvoda…" />;
  if (query.isError || !data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  // MOBILE_V1_0_BATCH22_V4_TYPESCRIPT_STRICT_DATA_NARROWING
  const loadedData = data;

  function patchField(fieldId: number, patch: Partial<AdminProductTypeFieldConfigInput>): void {
    setFieldConfig((current) => ({
      ...current,
      [String(fieldId)]: { ...(current[String(fieldId)] ?? { enabled: false }), ...patch },
    }));
  }

  function toggleCore(value: string): void {
    setRequiredCoreFields((current) => current.includes(value) ? current.filter((item) => item !== value) : [...current, value]);
  }

  function save(): void {
    if (!name.trim()) {
      feedback.notify({ tone: 'warning', title: 'Naziv je obavezan' });
      return;
    }
    const order = positiveInt(sortOrder, -1, 0, 1000000);
    if (order < 0) {
      feedback.notify({ tone: 'warning', title: 'Redosled nije validan' });
      return;
    }
    const completeness = positiveInt(minimumCompleteness, -1, 0, 100);
    if (completeness < 0) {
      feedback.notify({ tone: 'warning', title: 'Kompletnost nije validna', message: 'Unesi ceo broj od 0 do 100.' });
      return;
    }

    const normalizedConfig: Record<string, AdminProductTypeFieldConfigInput> = {};
    for (const field of loadedData.fields) {
      const row = fieldConfig[String(field.id)] ?? { enabled: false };
      normalizedConfig[String(field.id)] = {
        enabled: Boolean(row.enabled),
        is_required: Boolean(row.is_required),
        is_filterable: Boolean(row.is_filterable),
        show_in_summary: Boolean(row.show_in_summary),
        include_in_name: Boolean(row.include_in_name),
        sort_order: positiveInt(String(row.sort_order ?? field.sort_order), field.sort_order, 0, 1000000),
        completeness_weight: positiveInt(String(row.completeness_weight ?? 1), 1, 1, 100),
        default_value: String(row.default_value ?? '').trim() || null,
        default_detail: String(row.default_detail ?? '').trim() || null,
      };
    }

    saveMutation.mutate({
      name: name.trim(),
      slug: slug.trim() || null,
      status,
      sort_order: order,
      description: description.trim() || null,
      name_template: nameTemplate.trim() || null,
      auto_name_enabled: autoNameEnabled,
      minimum_completeness_percent: completeness,
      default_product_status: defaultProductStatus,
      required_core_fields: [...requiredCoreFields],
      field_config: normalizedConfig,
    });
  }

  function moveAssigned(fieldId: number, direction: -1 | 1): void {
    const ids = loadedData.fields.filter((field) => field.enabled).map((field) => field.id);
    const index = ids.indexOf(fieldId);
    const next = index + direction;
    if (index < 0 || next < 0 || next >= ids.length) return;
    // MOBILE_V1_0_BATCH22_V4_TYPESCRIPT_STRICT_REORDER
    const currentId = ids[index];
    const adjacentId = ids[next];
    if (currentId === undefined || adjacentId === undefined) return;
    ids[index] = adjacentId;
    ids[next] = currentId;
    reorderMutation.mutate(ids);
  }

  const serverEnabledFields = loadedData.fields.filter((field) => field.enabled);

  return (
    <Screen contentStyle={styles.content} keyboardShouldPersistTaps="handled">
      <Button variant="ghost" onPress={() => router.push('/admin/catalog/dictionaries/product-types' as Href)}>‹ Tipovi proizvoda</Button>
      <PageHeader title={data.product_type.name} eyebrow="Admin · Šifarnici · Tip proizvoda" name={bootstrap?.user.name} />

      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Osnovna podešavanja</Text>
        <TextField label="Naziv *" value={name} onChangeText={setName} maxLength={120} />
        <TextField label="Slug" value={slug} onChangeText={setSlug} autoCapitalize="none" maxLength={140} />
        <SelectSheet label="Status" value={status} options={[{ value: 'active', label: 'Aktivno' }, { value: 'inactive', label: 'Neaktivno' }]} onChange={(value) => { if (value === 'active' || value === 'inactive') setStatus(value); }} />
        <TextField label="Redosled" value={sortOrder} onChangeText={setSortOrder} keyboardType="number-pad" />
        <TextField label="Opis" value={description} onChangeText={setDescription} multiline />
        <Text style={styles.meta}>Automatska sistemska kategorija: {data.product_type.category?.name ?? 'biće kreirana pri čuvanju'}</Text>
        <Text style={styles.meta}>Artikli ovog tipa: {data.product_type.products_count}</Text>
      </Card>

      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Kompletnost i naziv artikla</Text>
        <SelectSheet
          label="Podrazumevani status novog artikla"
          value={defaultProductStatus}
          options={[{ value: 'draft', label: 'Nacrt' }, { value: 'active', label: 'Aktivan' }, { value: 'inactive', label: 'Neaktivan' }]}
          onChange={(value) => { if (value === 'draft' || value === 'active' || value === 'inactive') setDefaultProductStatus(value); }}
        />
        <TextField label="Minimum kompletnosti za aktivaciju (%)" value={minimumCompleteness} onChangeText={setMinimumCompleteness} keyboardType="number-pad" />
        <Button variant={autoNameEnabled ? 'secondary' : 'ghost'} onPress={() => setAutoNameEnabled((current) => !current)}>{autoNameEnabled ? 'Automatski naziv uključen ✓' : 'Uključi automatski naziv'}</Button>
        <TextField label="Šablon naziva" value={nameTemplate} onChangeText={setNameTemplate} placeholder="{brand} {line} {model} {cpu_family} {ram} {storage}" />
        <Text style={styles.label}>Obavezni osnovni podaci</Text>
        <View style={styles.actions}>
          {data.required_core_options.map((option) => {
            const selected = requiredCoreFields.includes(option.value);
            return <Button key={option.value} variant={selected ? 'secondary' : 'ghost'} onPress={() => toggleCore(option.value)}>{option.label}{selected ? ' ✓' : ''}</Button>;
          })}
        </View>
      </Card>

      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Specifikacije tipa</Text>
        <Text style={styles.copy}>Uključi polja i podesi obaveznost, filter, sažetak, naziv, težinu kompletnosti i podrazumevane vrednosti. Roditeljska zavisna polja server uključuje automatski.</Text>
        {data.fields.map((field) => (
          <ProductTypeFieldEditor key={field.id} field={field} config={fieldConfig[String(field.id)] ?? { enabled: field.enabled }} patch={(patch) => patchField(field.id, patch)} styles={styles} />
        ))}
      </Card>

      {serverEnabledFields.length > 0 ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Raspored dodeljenih specifikacija</Text>
          <Text style={styles.copy}>Za novouključeno polje prvo sačuvaj tip, pa zatim promeni njegov redosled.</Text>
          {serverEnabledFields.map((field, index) => (
            <View key={field.id} style={styles.reorderRow}>
              <View style={styles.flex}>
                <Text style={styles.fieldName}>{field.name}</Text>
                <Text style={styles.meta}>#{index + 1} · sort {field.sort_order}</Text>
              </View>
              <View style={styles.compactActions}>
                <Button variant="secondary" disabled={index === 0} loading={reorderMutation.isPending} onPress={() => moveAssigned(field.id, -1)}>Gore</Button>
                <Button variant="secondary" disabled={index === serverEnabledFields.length - 1} loading={reorderMutation.isPending} onPress={() => moveAssigned(field.id, 1)}>Dole</Button>
              </View>
            </View>
          ))}
        </Card>
      ) : null}

      <Button loading={saveMutation.isPending} onPress={save}>Sačuvaj tip proizvoda</Button>
    </Screen>
  );
}

function ProductTypeFieldEditor({ field, config, patch, styles }: { field: AdminProductTypeField; config: AdminProductTypeFieldConfigInput; patch: (patch: Partial<AdminProductTypeFieldConfigInput>) => void; styles: ReturnType<typeof createStyles> }) {
  const enabled = Boolean(config.enabled);
  const toggle = (key: keyof Pick<AdminProductTypeFieldConfigInput, 'is_required' | 'is_filterable' | 'show_in_summary' | 'include_in_name'>) => {
    if (key === 'is_required') patch({ is_required: !Boolean(config.is_required) });
    if (key === 'is_filterable') patch({ is_filterable: !Boolean(config.is_filterable) });
    if (key === 'show_in_summary') patch({ show_in_summary: !Boolean(config.show_in_summary) });
    if (key === 'include_in_name') patch({ include_in_name: !Boolean(config.include_in_name) });
  };
  return (
    <Card style={styles.fieldCard}>
      <View style={styles.rowBetween}>
        <View style={styles.flex}>
          <Text style={styles.fieldName}>{field.name}</Text>
          <Text style={styles.meta}>{field.slug} · {field.data_type}{field.unit ? ` · ${field.unit}` : ''}</Text>
        </View>
        <Pill tone={enabled ? 'success' : 'neutral'}>{enabled ? 'Uključeno' : 'Isključeno'}</Pill>
      </View>
      <Button variant={enabled ? 'secondary' : 'ghost'} onPress={() => patch({ enabled: !enabled })}>{enabled ? 'Isključi polje' : 'Uključi polje'}</Button>
      {enabled ? (
        <>
          <View style={styles.actions}>
            <Button variant={config.is_required ? 'secondary' : 'ghost'} onPress={() => toggle('is_required')}>Obavezno{config.is_required ? ' ✓' : ''}</Button>
            <Button variant={config.is_filterable ? 'secondary' : 'ghost'} onPress={() => toggle('is_filterable')}>Filter{config.is_filterable ? ' ✓' : ''}</Button>
            <Button variant={config.show_in_summary ? 'secondary' : 'ghost'} onPress={() => toggle('show_in_summary')}>Sažetak{config.show_in_summary ? ' ✓' : ''}</Button>
            <Button variant={config.include_in_name ? 'secondary' : 'ghost'} onPress={() => toggle('include_in_name')}>Naziv{config.include_in_name ? ' ✓' : ''}</Button>
          </View>
          <TextField label="Redosled" value={String(config.sort_order ?? field.sort_order)} onChangeText={(value) => patch({ sort_order: Number(value) })} keyboardType="number-pad" />
          <TextField label="Težina kompletnosti" value={String(config.completeness_weight ?? 1)} onChangeText={(value) => patch({ completeness_weight: Number(value) })} keyboardType="number-pad" />
          <TextField label="Podrazumevana vrednost" value={String(config.default_value ?? '')} onChangeText={(value) => patch({ default_value: value })} />
          {field.detail_input_enabled ? <TextField label="Podrazumevani detalj" value={String(config.default_detail ?? '')} onChangeText={(value) => patch({ default_detail: value })} /> : null}
        </>
      ) : null}
    </Card>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: spacing.xxl },
    card: { gap: spacing.md },
    fieldCard: { gap: spacing.sm },
    rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: spacing.md },
    flex: { flex: 1, minWidth: 0 },
    sectionTitle: { ...typography.h3, color: theme.ink },
    fieldName: { ...typography.label, color: theme.ink },
    label: { ...typography.label, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
    meta: { ...typography.small, color: theme.muted },
    actions: { gap: spacing.sm },
    compactActions: { gap: spacing.xs },
    reorderRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, borderTopWidth: 1, borderTopColor: theme.line, paddingTop: spacing.sm },
  });
}
