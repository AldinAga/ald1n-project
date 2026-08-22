import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams, type Href } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminCatalog,
  type AdminCatalogProductDetail,
  type AdminCatalogProductUpdateInput,
} from '@/features/admin/catalog-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { RemoteProductImageManager } from '@/features/catalog/product-image-manager';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import { useAppTheme } from '@/theme/app-theme';
import type {
  AdminCatalogSpecificationField,
} from '@/types/api';

type StorageRow = { type: string; capacityGb: string };

function emptyStorageRow(): StorageRow { return { type: '', capacityGb: '' }; }
function apiMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return fallback;
}
function serverFieldErrors(error: unknown): Record<string, string> {
  if (!(error instanceof ApiError)) return {};
  const mapped: Record<string, string> = {};
  for (const [field, messages] of Object.entries(error.errors)) {
    const first = messages[0];
    if (first) mapped[field] = first;
  }
  return mapped;
}
function nonNegativeDecimal(value: string): number | null {
  const normalized = value.trim().replace(',', '.');
  if (!normalized) return null;
  const parsed = Number(normalized);
  return Number.isFinite(parsed) && parsed >= 0 ? parsed : null;
}
function nonNegativeInteger(value: string): number | null {
  const normalized = value.trim();
  if (!/^\d+$/.test(normalized)) return null;
  const parsed = Number(normalized);
  return Number.isSafeInteger(parsed) && parsed >= 0 ? parsed : null;
}
function optionalPositiveId(value: string): number | undefined {
  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : undefined;
}
function specificationLabel(field: AdminCatalogSpecificationField): string {
  return `${field.name}${field.required ? ' *' : ''}${field.unit ? ` (${field.unit})` : ''}`;
}
function selectableOptions(
  field: AdminCatalogSpecificationField,
  fields: AdminCatalogSpecificationField[],
  specs: Record<string, string>,
) {
  if (!field.parent_field_id) return field.options;
  const parentField = fields.find((candidate) => candidate.id === field.parent_field_id);
  const parentValue = specs[String(field.parent_field_id)] ?? '';
  const parentOption = parentField?.options.find((option) => option.value === parentValue);
  if (!parentOption) return field.options.filter((option) => option.parent_option_ids.length === 0);
  return field.options.filter(
    (option) => option.parent_option_ids.length === 0 || option.parent_option_ids.includes(parentOption.id),
  );
}
function toText(value: string | number | boolean | undefined): string {
  if (value === undefined) return '';
  if (typeof value === 'boolean') return value ? '1' : '0';
  return String(value);
}
function initialStorage(detail: AdminCatalogProductDetail): Record<string, StorageRow[]> {
  const result: Record<string, StorageRow[]> = {};
  for (const [fieldId, rows] of Object.entries(detail.spec_structured ?? {})) {
    result[fieldId] = rows.length > 0
      ? rows.map((row) => ({ type: row.type ?? '', capacityGb: row.capacity_gb === undefined ? '' : String(row.capacity_gb) }))
      : [emptyStorageRow()];
  }
  return result;
}

// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8
// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8_V2
// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8_V3
// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8_V4
export default function AdminCatalogEditScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { can, bootstrap } = useAuth();
  const isSuperAdmin = bootstrap?.user.role?.slug === 'superadmin';
  const allowed = can('catalog.manage_products');
  const canManageImages = can('catalog.manage_images');
  const params = useLocalSearchParams<{ id?: string | string[] }> ();
  const rawId = Array.isArray(params.id) ? params.id[0] : params.id;
  const productId = Number(rawId);
  const validId = Number.isInteger(productId) && productId > 0;

  const [hydratedId, setHydratedId] = useState<number | null> (null);
  const [productTypeId, setProductTypeId] = useState('');
  const [brandId, setBrandId] = useState('');
  const [lineId, setLineId] = useState('');
  const [sku, setSku] = useState('');
  const [modelName, setModelName] = useState('');
  const [name, setName] = useState('');
  const [priceAmount, setPriceAmount] = useState('');
  const [currency, setCurrency] = useState<'EUR' | 'RSD'> ('EUR');
  const [purchasePriceRsd, setPurchasePriceRsd] = useState('');
  const [manualCommissionEur, setManualCommissionEur] = useState('');
  const [description, setDescription] = useState('');
  const [notes, setNotes] = useState('');
  const [stockQuantity, setStockQuantity] = useState('0');
  const [lowStockThreshold, setLowStockThreshold] = useState('1');
  const [status, setStatus] = useState<'draft' | 'active' | 'inactive'> ('inactive');
  const [specs, setSpecs] = useState<Record<string, string>> ({});
  const [specDetails, setSpecDetails] = useState<Record<string, string>> ({});
  const [storageRows, setStorageRows] = useState<Record<string, StorageRow[]>> ({});
  const [errors, setErrors] = useState<Record<string, string>> ({});
  const [submitError, setSubmitError] = useState<string | null> (null);

  const optionsQuery = useQuery({
    queryKey: ['admin-catalog-create-options'],
    queryFn: api.admin.catalog.options,
    enabled: allowed,
  });
  const detailQuery = useQuery({
    queryKey: ['admin', 'catalog', 'product', productId],
    queryFn: () => apiAdminCatalog.detail(productId),
    enabled: allowed && validId,
  });

  useEffect(() => {
    const detail = detailQuery.data?.data;
    if (!detail || hydratedId === detail.id) return;
    setProductTypeId(detail.product_type_id ? String(detail.product_type_id) : '');
    setBrandId(detail.brand_id ? String(detail.brand_id) : '');
    setLineId(detail.product_line_id ? String(detail.product_line_id) : '');
    setSku(detail.sku);
    setModelName(detail.model_name ?? '');
    setName(detail.name);
    setPriceAmount(String(detail.price_amount));
    setCurrency(detail.price_currency === 'RSD' ? 'RSD' : 'EUR');
    setPurchasePriceRsd(detail.purchase_price_rsd === null ? '' : String(detail.purchase_price_rsd));
    setManualCommissionEur(detail.manual_commission_eur === null ? '' : String(detail.manual_commission_eur));
    setDescription(detail.description ?? '');
    setNotes(detail.notes ?? '');
    setStockQuantity(String(detail.stock_quantity));
    setLowStockThreshold(String(detail.low_stock_threshold));
    setStatus(detail.status === 'active' || detail.status === 'draft' || detail.status === 'inactive' ? detail.status : 'inactive');
    setSpecs(Object.fromEntries(Object.entries(detail.specs ?? {}).map(([key, value]) => [key, toText(value)])));
    setSpecDetails({ ...(detail.spec_details ?? {}) });
    setStorageRows(initialStorage(detail));
    setErrors({});
    setSubmitError(null);
    setHydratedId(detail.id);
  }, [detailQuery.data, hydratedId]);

  const product = detailQuery.data?.data;
  const options = optionsQuery.data;
  const selectedType = options?.types.find((type) => String(type.id) === productTypeId);
  const specificationFields = selectedType?.fields ?? [];
  const storageFields = specificationFields.filter((field) => field.storage_repeater?.enabled);
  const storageDerivedFieldIds = new Set(
    storageFields.map((field) => field.storage_repeater?.total_field_id).filter((id): id is number => typeof id === 'number'),
  );
  const standardSpecificationFields = specificationFields.filter(
    (field) => !field.storage_repeater?.enabled && !field.read_only_derived && !storageDerivedFieldIds.has(field.id),
  );
  const selectedProductTypeId = optionalPositiveId(productTypeId);
  const selectedBrandId = optionalPositiveId(brandId);
  const brandOptions = (options?.brands ?? [])
    .filter((brand) => selectedProductTypeId !== undefined && brand.product_type_ids.includes(selectedProductTypeId))
    .map((brand) => ({ value: String(brand.id), label: brand.name }));
  const lineOptions = (options?.lines ?? [])
    .filter((line) => selectedProductTypeId !== undefined
      && selectedBrandId !== undefined
      && line.brand_id === selectedBrandId
      && line.product_type_ids.includes(selectedProductTypeId))
    .map((line) => ({ value: String(line.id), label: line.name }));
  const imageUploadEnabled = Boolean(product?.capabilities.manage_images && canManageImages && options?.capabilities.image_upload);

  const refreshAll = async () => {
    await Promise.all([
      client.invalidateQueries({ queryKey: ['admin', 'catalog'] }),
      client.invalidateQueries({ queryKey: ['products'] }),
      client.invalidateQueries({ queryKey: ['catalog-filters'] }),
    ]);
  };

  const saveMutation = useMutation({
    mutationFn: (input: AdminCatalogProductUpdateInput) =>
      apiAdminCatalog.update(productId, input),
    onSuccess: async (response) => {
      setHydratedId(null);
      await refreshAll();
      await detailQuery.refetch();
      feedback.notify({
        tone: 'success',
        title: 'Izmene su sačuvane',
        message: `${response.data.name} · ${response.data.sku}`,
      });
    },
    onError: (error) => {
      setErrors(serverFieldErrors(error));
      setSubmitError(apiMessage(error, 'Artikal trenutno nije moguće izmeniti.'));
      feedback.notify({
        tone: 'warning',
        title: 'Izmene nisu sačuvane',
        message: 'Podaci ostaju u formi. Ispravi grešku i pokušaj ponovo.',
      });
    },
  });

  const archiveMutation = useMutation({
    mutationFn: () => apiAdminCatalog.archive(productId),
    onSuccess: async () => {
      await refreshAll();
      feedback.notify({ tone: 'success', title: 'Artikal je arhiviran', message: 'Uklonjen je iz operativnog kataloga i dostupan je u Arhivirano.' });
      router.replace('/admin/catalog' as Href);
    },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Arhiviranje nije uspelo', message: apiMessage(error, 'Pokušaj ponovo.') }),
  });

  const restoreMutation = useMutation({
    mutationFn: () => apiAdminCatalog.restore(productId),
    onSuccess: async () => {
      setHydratedId(null);
      await refreshAll();
      await detailQuery.refetch();
      feedback.notify({ tone: 'success', title: 'Arhiviranje je opozvano', message: 'Artikal je vraćen u operativni katalog kao neaktivan.' });
    },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Vraćanje nije uspelo', message: apiMessage(error, 'Pokušaj ponovo.') }),
  });

  if (!allowed) return <UnavailableState title="Administracija artikala nije dostupna" />;
  if (!validId) return <UnavailableState title="Artikal nije validan" />;
  if (optionsQuery.isLoading || detailQuery.isLoading) return <LoadingState label="Učitavanje artikla…" />;
  if (optionsQuery.isError || !options) return <ErrorState error={optionsQuery.error} onRetry={() => void optionsQuery.refetch()} />;
  if (detailQuery.isError || !product) return <ErrorState error={detailQuery.error} onRetry={() => void detailQuery.refetch()} />;
  if (hydratedId !== product.id) return <LoadingState label="Priprema edit forme…" />;

  const changeType = (value: string) => {
    setProductTypeId(value);
    setBrandId('');
    setLineId('');
    setSpecDetails({});
    const type = options.types.find((candidate) => String(candidate.id) === value);
    const defaults: Record<string, string> = {};
    const nextStorageRows: Record<string, StorageRow[]> = {};
    for (const field of type?.fields ?? []) {
      if (field.storage_repeater?.enabled) nextStorageRows[String(field.id)] = [emptyStorageRow()];
      else if (!field.read_only_derived && field.default_value !== null && field.default_value !== undefined) defaults[String(field.id)] = String(field.default_value);
    }
    setSpecs(defaults);
    setStorageRows(nextStorageRows);
  };

  const setSpecValue = (field: AdminCatalogSpecificationField, value: string) => {
    setSpecs((current) => {
      const next = { ...current, [String(field.id)]: value };
      for (const child of specificationFields) if (child.parent_field_id === field.id) delete next[String(child.id)];
      return next;
    });
    setSpecDetails((current) => {
      const next = { ...current };
      for (const child of specificationFields) if (child.parent_field_id === field.id) delete next[String(child.id)];
      return next;
    });
  };

  const updateStorageRow = (fieldId: number, rowIndex: number, patch: Partial<StorageRow>) => {
    const key = String(fieldId);
    setStorageRows((current) => ({
      ...current,
      [key]: (current[key] ?? [emptyStorageRow()]).map((row, index) => index === rowIndex ? { ...row, ...patch } : row),
    }));
  };
  const addStorageRow = (field: AdminCatalogSpecificationField) => {
    const repeater = field.storage_repeater;
    if (!repeater) return;
    const key = String(field.id);
    setStorageRows((current) => {
      const rows = current[key] ?? [emptyStorageRow()];
      return rows.length >= repeater.max_items ? current : { ...current, [key]: [...rows, emptyStorageRow()] };
    });
  };
  const removeStorageRow = (fieldId: number, rowIndex: number) => {
    const key = String(fieldId);
    setStorageRows((current) => {
      const next = (current[key] ?? [emptyStorageRow()]).filter((_, index) => index !== rowIndex);
      return { ...current, [key]: next.length > 0 ? next : [emptyStorageRow()] };
    });
  };
  const storageTotal = (fieldId: number) => (storageRows[String(fieldId)] ?? [])
    .reduce((sum, row) => sum + (nonNegativeInteger(row.capacityGb) ?? 0), 0);

  const submit = () => {
    const nextErrors: Record<string, string> = {};
    const price = nonNegativeDecimal(priceAmount);
    const purchase = purchasePriceRsd.trim() ? nonNegativeDecimal(purchasePriceRsd) : undefined;
    const commission = manualCommissionEur.trim() ? nonNegativeDecimal(manualCommissionEur) : undefined;
    const stock = nonNegativeInteger(stockQuantity);
    const threshold = nonNegativeInteger(lowStockThreshold);

    if (!sku.trim()) nextErrors.sku = 'SKU je obavezan kod izmene artikla.';
    if (!name.trim() && (!selectedType || !selectedType.auto_name_enabled)) nextErrors.name = 'Unesi naziv ili koristi automatsko formiranje naziva.';
    if (price === null) nextErrors.price_amount = 'Unesi ispravnu prodajnu cenu.';
    if (purchasePriceRsd.trim() && purchase === null) nextErrors.purchase_price_rsd = 'Unesi ispravnu nabavnu cenu.';
    if (manualCommissionEur.trim() && commission === null) nextErrors.manual_commission_eur = 'Unesi ispravnu proviziju.';
    if (!description.trim()) nextErrors.description = 'Opis je obavezan.';
    if (stock === null) nextErrors.stock_quantity = 'Lager mora biti ceo broj 0 ili veći.';
    if (threshold === null) nextErrors.low_stock_threshold = 'Prag lagera mora biti ceo broj 0 ili veći.';

    for (const field of standardSpecificationFields) {
      const value = specs[String(field.id)] ?? '';
      if (field.required && !value.trim()) nextErrors[`specs.${field.id}`] = `${field.name} je obavezno polje.`;
    }
    for (const field of storageFields) {
      const rows = storageRows[String(field.id)] ?? [emptyStorageRow()];
      const entered = rows.some((row) => row.type.trim() || row.capacityGb.trim());
      if (field.required && !entered) nextErrors[`spec_lists.${field.id}`] = `${field.name} je obavezno polje.`;
      if (entered) rows.forEach((row, index) => {
        if (!row.type.trim()) nextErrors[`spec_lists.${field.id}.${index}`] = 'Izaberi tip diska.';
        const capacity = nonNegativeInteger(row.capacityGb);
        if (capacity === null || capacity > 10000000) nextErrors[`spec_capacities.${field.id}.${index}`] = 'Kapacitet mora biti ceo broj 0-10000000 GB.';
      });
    }

    setErrors(nextErrors);
    setSubmitError(null);
    if (Object.keys(nextErrors).length > 0 || price === null || stock === null || threshold === null) {
      feedback.notify({ tone: 'warning', title: 'Dopuni obavezna polja', message: 'Sve izmene ostaju u formi.' });
      return;
    }

    const cleanSpecs: Record<string, string | number | boolean> = {};
    const cleanDetails: Record<string, string> = {};
    const cleanSpecLists: Record<string, string[]> = {};
    const cleanSpecCapacities: Record<string, number[]> = {};
    const cleanSpecStructured: Record<string, Array<{ type: string; capacity_gb?: number }>> = {};

    for (const field of standardSpecificationFields) {
      const key = String(field.id);
      const value = (specs[key] ?? '').trim();
      const detail = (specDetails[key] ?? '').trim();
      if (value) cleanSpecs[key] = value;
      if (detail) cleanDetails[key] = detail;
    }
    for (const field of storageFields) {
      const key = String(field.id);
      const rows = (storageRows[key] ?? [])
        .filter((row) => row.type.trim() || row.capacityGb.trim())
        .map((row) => ({ type: row.type.trim(), capacity_gb: nonNegativeInteger(row.capacityGb) ?? 0 }));
      const first = rows[0];
      if (!first) continue;
      cleanSpecs[key] = first.type;
      cleanSpecLists[key] = rows.map((row) => row.type);
      cleanSpecCapacities[key] = rows.map((row) => row.capacity_gb);
      cleanSpecStructured[key] = rows;
    }

    const input: AdminCatalogProductUpdateInput = {
      product_type_id: optionalPositiveId(productTypeId),
      brand_id: optionalPositiveId(brandId),
      product_line_id: optionalPositiveId(lineId),
      category_ids: selectedType?.category_id ? [selectedType.category_id] : product.category_ids,
      sku: sku.trim(),
      regenerate_sku: false,
      model_name: modelName.trim() || undefined,
      name: name.trim() || undefined,
      regenerate_name: Boolean(selectedType?.auto_name_enabled && !name.trim()),
      price_amount: price,
      price_currency: currency,
      purchase_price_rsd: purchase ?? undefined,
      manual_commission_eur: commission ?? undefined,
      description: description.trim(),
      notes: notes.trim() || undefined,
      stock_quantity: stock,
      low_stock_threshold: threshold,
      status,
      specs: Object.keys(cleanSpecs).length ? cleanSpecs : undefined,
      spec_details: Object.keys(cleanDetails).length ? cleanDetails : undefined,
      spec_lists: Object.keys(cleanSpecLists).length ? cleanSpecLists : undefined,
      spec_capacities: Object.keys(cleanSpecCapacities).length ? cleanSpecCapacities : undefined,
      spec_structured: Object.keys(cleanSpecStructured).length ? cleanSpecStructured : undefined,
    };
    saveMutation.mutate(input);
  };

  return (
    <Screen contentStyle={styles.content} keyboardShouldPersistTaps="handled">
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Katalog artikala</Text>
      </Pressable>

      <View style={styles.heading}>
        <Text style={styles.eyebrow}>ADMIN · KATALOG · EDIT</Text>
        <Text style={styles.title}>{product.name}</Text>
        <Text style={styles.copy}>{product.sku} · ID #{product.id}</Text>
      </View>

      {product.is_archived ? (
        <Card style={styles.archiveCard}>
          <Text style={styles.sectionTitle}>Artikal je arhiviran</Text>
          <Text style={styles.help}>Arhivirani artikal je van operativnog kataloga. Opozovi arhiviranje da bi ponovo mogao da ga menjaš.</Text>
          <Button variant="secondary" loading={restoreMutation.isPending} onPress={() => restoreMutation.mutate()}>
            Opozovi Arhiviranje
          </Button>
        </Card>
      ) : null}

      {submitError ? (
        <Card style={styles.errorCard}>
          <Text style={styles.errorTitle}>Artikal nije sačuvan</Text>
          <Text style={styles.help}>{submitError}</Text>
        </Card>
      ) : null}

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Identitet artikla</Text>
        <SelectSheet
          label="Tip artikla"
          value={productTypeId}
          disabled={product.is_archived}
          options={[
            { value: '', label: 'Bez tipa / ručni naziv' },
            ...options.types.map((type) => ({ value: String(type.id), label: type.name, detail: type.category_name ?? 'Nema sistemsku kategoriju' })),
          ]}
          onChange={changeType}
        />
        <SelectSheet
          label="Brend"
          value={brandId}
          disabled={product.is_archived || !productTypeId}
          options={[{ value: '', label: 'Bez brenda' }, ...brandOptions]}
          onChange={(value) => { setBrandId(value); setLineId(''); }}
        />
        <SelectSheet
          label="Linija"
          value={lineId}
          disabled={product.is_archived || !productTypeId || !brandId}
          options={[{ value: '', label: 'Bez linije' }, ...lineOptions]}
          onChange={setLineId}
        />
        <TextField label="SKU" value={sku} onChangeText={setSku} error={errors.sku} disabled={product.is_archived} autoCapitalize="characters" />
        <TextField label="Model" value={modelName} onChangeText={setModelName} disabled={product.is_archived} />
        <TextField label="Naziv" value={name} onChangeText={setName} error={errors.name} disabled={product.is_archived} />
      </View>

      {selectedType ? (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Specifikacije · {selectedType.name}</Text>

          {storageFields.map((field) => {
            const repeater = field.storage_repeater;
            if (!repeater) return null;
            const key = String(field.id);
            const rows = storageRows[key] ?? [emptyStorageRow()];
            const total = storageTotal(field.id);
            return (
              <Card key={`storage-${field.id}`} muted style={styles.storageCard}>
                <Text style={styles.storageTitle}>{field.name}{field.required ? ' *' : ''}</Text>
                <Text style={styles.help}>{repeater.total_field_name}: {total} {repeater.total_unit ?? repeater.capacity_unit}</Text>
                {errors[`spec_lists.${field.id}`] ? <Text style={styles.inlineError}>{errors[`spec_lists.${field.id}`]}</Text> : null}
                {rows.map((row, index) => (
                  <View key={`${field.id}:${index}`} style={styles.storageRow}>
                    <Text style={styles.storageRowTitle}>Disk {index + 1}</Text>
                    <SelectSheet
                      label="Tip diska"
                      value={row.type}
                      disabled={product.is_archived}
                      options={[{ value: '', label: 'Nije izabrano' }, ...field.options.map((option) => ({ value: option.value, label: option.label }))]}
                      onChange={(value) => updateStorageRow(field.id, index, { type: value })}
                      error={errors[`spec_lists.${field.id}.${index}`]}
                    />
                    <TextField
                      label={`Kapacitet (${repeater.capacity_unit})`}
                      value={row.capacityGb}
                      onChangeText={(value) => updateStorageRow(field.id, index, { capacityGb: value })}
                      keyboardType="number-pad"
                      disabled={product.is_archived}
                      error={errors[`spec_capacities.${field.id}.${index}`]}
                    />
                    {rows.length > 1 ? (
                      <Button variant="ghost" disabled={product.is_archived} onPress={() => removeStorageRow(field.id, index)}>Ukloni disk</Button>
                    ) : null}
                  </View>
                ))}
                {rows.length < repeater.max_items ? (
                  <Button variant="secondary" disabled={product.is_archived} onPress={() => addStorageRow(field)}>Dodaj disk</Button>
                ) : null}
              </Card>
            );
          })}

          {standardSpecificationFields.map((field) => {
            const key = String(field.id);
            const value = specs[key] ?? '';
            const choices = selectableOptions(field, specificationFields, specs);
            const fieldError = errors[`specs.${field.id}`];
            return (
              <View key={field.id} style={styles.specField}>
                {field.data_type === 'select' && field.options.length > 0 ? (
                  <SelectSheet
                    label={specificationLabel(field)}
                    value={value}
                    disabled={product.is_archived}
                    options={[{ value: '', label: 'Nije izabrano' }, ...choices.map((option) => ({ value: option.value, label: option.label }))]}
                    onChange={(next) => setSpecValue(field, next)}
                    error={fieldError}
                  />
                ) : field.data_type === 'boolean' ? (
                  <SelectSheet
                    label={specificationLabel(field)}
                    value={value}
                    disabled={product.is_archived}
                    options={[{ value: '', label: 'Nije izabrano' }, { value: '1', label: 'Da' }, { value: '0', label: 'Ne' }]}
                    onChange={(next) => setSpecValue(field, next)}
                    error={fieldError}
                  />
                ) : (
                  <TextField
                    label={specificationLabel(field)}
                    value={value}
                    onChangeText={(next) => setSpecValue(field, next)}
                    disabled={product.is_archived}
                    keyboardType={field.data_type === 'integer' || field.data_type === 'decimal' ? 'decimal-pad' : 'default'}
                    error={fieldError}
                  />
                )}
                {field.detail_input_enabled ? (
                  <TextField
                    label={field.detail_label ?? `${field.name} · detalj`}
                    value={specDetails[key] ?? ''}
                    onChangeText={(next) => setSpecDetails((current) => ({ ...current, [key]: next }))}
                    disabled={product.is_archived}
                    error={errors[`spec_details.${field.id}`]}
                  />
                ) : null}
              </View>
            );
          })}
        </View>
      ) : null}

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Cena i lager</Text>
        <TextField label="Prodajna cena" value={priceAmount} onChangeText={setPriceAmount} keyboardType="decimal-pad" disabled={product.is_archived} error={errors.price_amount} />
        <SelectSheet label="Valuta" value={currency} disabled={product.is_archived} options={[{ value: 'EUR', label: 'EUR' }, { value: 'RSD', label: 'RSD' }]} onChange={(value) => setCurrency(value === 'RSD' ? 'RSD' : 'EUR')} />
        <TextField label="Nabavna cena (RSD)" value={purchasePriceRsd} onChangeText={setPurchasePriceRsd} keyboardType="decimal-pad" disabled={product.is_archived} error={errors.purchase_price_rsd} />
        <TextField label="Ručna provizija (EUR)" value={manualCommissionEur} onChangeText={setManualCommissionEur} keyboardType="decimal-pad" disabled={product.is_archived} error={errors.manual_commission_eur} />
        <TextField label="Lager" value={stockQuantity} onChangeText={setStockQuantity} keyboardType="number-pad" disabled={product.is_archived || !product.capabilities.stock_adjust} error={errors.stock_quantity} />
        <TextField label="Prag niskog lagera" value={lowStockThreshold} onChangeText={setLowStockThreshold} keyboardType="number-pad" disabled={product.is_archived} error={errors.low_stock_threshold} />
        <SelectSheet label="Status" value={status} disabled={product.is_archived} options={[{ value: 'draft', label: 'Nacrt' }, { value: 'active', label: 'Aktivan' }, { value: 'inactive', label: 'Neaktivan' }]} onChange={(value) => setStatus(value === 'active' || value === 'draft' ? value : 'inactive')} />
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Opis</Text>
        <TextField label="Opis" value={description} onChangeText={setDescription} multiline disabled={product.is_archived} error={errors.description} />
        <TextField label="Interne napomene" value={notes} onChangeText={setNotes} multiline disabled={product.is_archived} error={errors.notes} />
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Fotografije artikla</Text>
        <RemoteProductImageManager
          productId={product.id}
          limits={options.image_limits}
          enabled={imageUploadEnabled}
          readOnly={product.is_archived}
          onChanged={async () => {
            await refreshAll();
            await detailQuery.refetch();
          }}
        />
      </View>

      {/* MOBILE_V0_8_SUPERADMIN_DIRECT_SALE_BATCH10 */}
      {isSuperAdmin && !product.is_archived ? (
        <Card style={styles.directSaleCard}>
          <View style={styles.heading}>
            <Text style={styles.sectionTitle}>Direktna prodaja</Text>
            <Text style={styles.help}>
              SuperAdministrator može evidentirati prodaju ovog artikla direktno iz kataloga. Prodaja koristi postojeći DirectSaleService, umanjuje lager i kreira plaćenu/dostavljenu porudžbinu.
            </Text>
          </View>
          <Button
            variant="secondary"
            onPress={() => router.push({
              pathname: '/admin/catalog/[id]/direct-sale',
              params: { id: String(product.id) },
            } as Href)}
          >
            Evidentiraj prodaju
          </Button>
        </Card>
      ) : null}

      {!product.is_archived ? (
        <View style={styles.section}>
          <Button loading={saveMutation.isPending} onPress={submit}>Sačuvaj izmene</Button>
          {product.capabilities.archive ? (
            <Button variant="danger" loading={archiveMutation.isPending} onPress={() => archiveMutation.mutate()}>
              Arhiviraj artikal
            </Button>
          ) : null}
        </View>
      ) : null}
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.xl },
    back: { ...typography.label, color: theme.primary },
    heading: { gap: spacing.xs },
    eyebrow: { ...typography.small, color: theme.primary, fontWeight: '800' },
    title: { ...typography.h1, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
    section: { gap: spacing.md },
    sectionTitle: { ...typography.h3, color: theme.ink },
    help: { ...typography.body, color: theme.muted },
    errorCard: { gap: spacing.sm, borderColor: theme.danger },
    errorTitle: { ...typography.h3, color: theme.danger },
    archiveCard: { gap: spacing.md },
    specField: { gap: spacing.sm },
    storageCard: { gap: spacing.md },
    storageTitle: { ...typography.h3, color: theme.ink },
    storageRow: { gap: spacing.sm, paddingTop: spacing.sm },
    storageRowTitle: { ...typography.label, color: theme.ink },
    inlineError: { ...typography.small, color: theme.danger },
    imageRow: { gap: spacing.sm },
    flexOne: { flex: 1, minWidth: 0 },
    imageName: { ...typography.label, color: theme.ink },
    directSaleCard: { gap: spacing.md, borderWidth: 1, borderColor: theme.primaryContainer },
  });
}
