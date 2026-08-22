import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { apiAdminCatalog } from '@/features/admin/catalog-admin-api';
import {
  DraftProductImageManager,
  type DraftProductImage,
} from '@/features/catalog/product-image-manager';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import { useAppTheme } from '@/theme/app-theme';
import type {
  AdminCatalogSpecificationField,
  AdminProductCreateInput,
} from '@/types/api';

function apiMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return fallback;
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
  if (!value) return undefined;
  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : undefined;
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

function specificationLabel(field: AdminCatalogSpecificationField): string {
  const required = field.required ? ' *' : '';
  const unit = field.unit ? ` (${field.unit})` : '';
  return `${field.name}${required}${unit}`;
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

  if (!parentOption) {
    return field.options.filter((option) => option.parent_option_ids.length === 0);
  }

  return field.options.filter(
    (option) => option.parent_option_ids.length === 0 || option.parent_option_ids.includes(parentOption.id),
  );
}

// MOBILE_ADMIN_PRODUCT_CREATE_BATCH2B_V06
type StorageRow = {
  type: string;
  capacityGb: string;
};

function emptyStorageRow(): StorageRow {
  return { type: '', capacityGb: '' };
}

export default function AdminCatalogCreateScreen() {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { can } = useAuth();
  const allowed = can('catalog.manage_products');
  const canManageImages = can('catalog.manage_images');

  const [productTypeId, setProductTypeId] = useState('');
  const [brandId, setBrandId] = useState('');
  const [lineId, setLineId] = useState('');
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
  const [status, setStatus] = useState<'draft' | 'active' | 'inactive'> ('draft');
  const [specs, setSpecs] = useState<Record<string, string>> ({});
  const [specDetails, setSpecDetails] = useState<Record<string, string>> ({});
  const [storageRows, setStorageRows] = useState<Record<string, StorageRow[]>> ({});
  // MOBILE_V0_8_SHARED_PRODUCT_IMAGE_MANAGER_BATCH9
  const [images, setImages] = useState<DraftProductImage[]> ([]);
  const [errors, setErrors] = useState<Record<string, string>> ({});
  const [submitError, setSubmitError] = useState<string | null> (null);

  const optionsQuery = useQuery({
    queryKey: ['admin-catalog-create-options'],
    queryFn: api.admin.catalog.options,
    enabled: allowed,
  });

  const mutation = useMutation({
    mutationFn: async ({
      input,
      imageFiles,
    }: {
      input: AdminProductCreateInput;
      imageFiles: DraftProductImage[];
    }) => {
      const response = await api.admin.catalog.createProduct(input);
      let imageWarning: string | null = null;

      if (imageFiles.length > 0) {
        try {
          const uploaded = await api.admin.catalog.uploadProductImages(response.data.id, imageFiles);
          const skipped = new Set(uploaded.skipped_input_indexes);
          if (uploaded.uploaded_images.length !== imageFiles.length - skipped.size) {
            throw new Error('Server nije vratio očekivani plan novih fotografija.');
          }
          let managedIndex = 0;
          for (let index = 0; index < imageFiles.length; index += 1) {
            if (skipped.has(index)) continue;
            const draft = imageFiles[index];
            const managed = uploaded.uploaded_images[managedIndex];
            managedIndex += 1;
            if (!draft || !managed || draft.rotation_degrees === 0) continue;
            await apiAdminCatalog.rotateImage(response.data.id, managed.id, draft.rotation_degrees);
          }
        } catch (error) {
          imageWarning = apiMessage(
            error,
            'Artikal je kreiran, ali plan fotografija nije u potpunosti primenjen.',
          );
        }
      }

      return { response, imageWarning };
    },
    onSuccess: async ({ response, imageWarning }) => {
      await Promise.all([
        client.invalidateQueries({ queryKey: ['products'] }),
        client.invalidateQueries({ queryKey: ['catalog-filters'] }),
        client.invalidateQueries({ queryKey: ['admin-catalog-create-options'] }),
      ]);

      feedback.notify(
        imageWarning
          ? {
              tone: 'warning',
              title: 'Artikal je kreiran, fotografije nisu poslate',
              message: imageWarning,
              durationMs: 5200,
            }
          : {
              tone: 'success',
              title: 'Artikal je kreiran',
              message: `${response.data.name} · ${response.data.sku}`,
            },
      );

      router.replace({
        pathname: '/product/[slug]',
        params: { slug: response.data.slug },
      });
    },
    onError: (error) => {
      setErrors(serverFieldErrors(error));
      setSubmitError(apiMessage(error, 'Artikal trenutno nije moguće kreirati.'));
      feedback.notify({
        tone: 'warning',
        title: 'Artikal nije sačuvan',
        message: 'Uneti podaci i izabrane fotografije ostaju u formi. Ispravi grešku i pokušaj ponovo.',
      });
    },
  });

  if (!allowed) return <UnavailableState title="Dodavanje artikala nije dostupno" />;
  if (optionsQuery.isLoading) return <LoadingState label="Učitavanje opcija artikla…" />;
  if (optionsQuery.isError || !optionsQuery.data) {
    return <ErrorState error={optionsQuery.error} onRetry={() => void optionsQuery.refetch()} />;
  }

  const options = optionsQuery.data;
  const selectedType = options.types.find((type) => String(type.id) === productTypeId);
  const specificationFields = selectedType?.fields ?? [];
  const storageFields = specificationFields.filter((field) => field.storage_repeater?.enabled);
  const storageDerivedFieldIds = new Set(
    storageFields.map((field) => field.storage_repeater?.total_field_id).filter((id): id is number => typeof id === 'number'),
  );
  const standardSpecificationFields = specificationFields.filter(
    (field) => !field.storage_repeater?.enabled && !field.read_only_derived && !storageDerivedFieldIds.has(field.id),
  );
  // MOBILE_PRODUCT_CREATE_TYPE_SCOPED_TAXONOMY_V07
  const selectedProductTypeId = optionalPositiveId(productTypeId);
  const selectedBrandId = optionalPositiveId(brandId);
  const brandOptions = options.brands
    .filter((brand) => selectedProductTypeId !== undefined && brand.product_type_ids.includes(selectedProductTypeId))
    .map((brand) => ({ value: String(brand.id), label: brand.name }));
  const lineOptions = options.lines
    .filter((line) => selectedProductTypeId !== undefined
      && selectedBrandId !== undefined
      && line.brand_id === selectedBrandId
      && line.product_type_ids.includes(selectedProductTypeId))
    .map((line) => ({ value: String(line.id), label: line.name }));
  const imageUploadEnabled = canManageImages && options.capabilities.image_upload;

  const changeType = (value: string) => {
    setProductTypeId(value);
    setBrandId('');
    setLineId('');
    setErrors((current) => {
      const next = { ...current };
      for (const key of Object.keys(next)) {
        if (
          key.startsWith('specs.')
          || key.startsWith('spec_details.')
          || key.startsWith('spec_lists.')
          || key.startsWith('spec_capacities.')
          || key.startsWith('spec_structured.')
        ) delete next[key];
      }
      return next;
    });
    setSpecDetails({});
    setStorageRows({});

    const type = options.types.find((candidate) => String(candidate.id) === value);
    if (!type) {
      setSpecs({});
      return;
    }

    const defaults: Record<string, string> = {};
    const nextStorageRows: Record<string, StorageRow[]> = {};
    for (const field of type.fields) {
      if (field.storage_repeater?.enabled) {
        nextStorageRows[String(field.id)] = [emptyStorageRow()];
        continue;
      }
      if (!field.read_only_derived && field.default_value !== null && field.default_value !== undefined) {
        defaults[String(field.id)] = String(field.default_value);
      }
    }
    setSpecs(defaults);
    setStorageRows(nextStorageRows);
  };

  const setSpecValue = (field: AdminCatalogSpecificationField, value: string) => {
    setSpecs((current) => {
      const next = { ...current, [String(field.id)]: value };
      for (const child of specificationFields) {
        if (child.parent_field_id === field.id) delete next[String(child.id)];
      }
      return next;
    });
    setSpecDetails((current) => {
      const next = { ...current };
      for (const child of specificationFields) {
        if (child.parent_field_id === field.id) delete next[String(child.id)];
      }
      return next;
    });
  };

  const updateStorageRow = (fieldId: number, rowIndex: number, patch: Partial<StorageRow>) => {
    const key = String(fieldId);
    setStorageRows((current) => ({
      ...current,
      [key]: (current[key] ?? [emptyStorageRow()]).map((row, index) => (
        index === rowIndex ? { ...row, ...patch } : row
      )),
    }));
  };

  const addStorageRow = (field: AdminCatalogSpecificationField) => {
    const repeater = field.storage_repeater;
    if (!repeater) return;
    const key = String(field.id);
    setStorageRows((current) => {
      const rows = current[key] ?? [emptyStorageRow()];
      if (rows.length >= repeater.max_items) return current;
      return { ...current, [key]: [...rows, emptyStorageRow()] };
    });
  };

  const removeStorageRow = (fieldId: number, rowIndex: number) => {
    const key = String(fieldId);
    setStorageRows((current) => {
      const rows = current[key] ?? [emptyStorageRow()];
      const nextRows = rows.filter((_, index) => index !== rowIndex);
      return { ...current, [key]: nextRows.length > 0 ? nextRows : [emptyStorageRow()] };
    });
  };

  const storageTotal = (fieldId: number): number => (storageRows[String(fieldId)] ?? [])
    .reduce((sum, row) => sum + (nonNegativeInteger(row.capacityGb) ?? 0), 0);

  // MOBILE_PRODUCT_CREATE_REPEATABLE_ACTIONS_DRAFT_PRESERVATION_V07
  // MOBILE_PRODUCT_CREATE_PERSISTENT_DRAFT_RETRY_V07
  // MOBILE_V0_8_SHARED_PRODUCT_IMAGE_MANAGER_BATCH9
  // Draft image order and rotation are preserved until successful product creation.

  const submit = () => {
    const nextErrors: Record<string, string> = {};
    const price = nonNegativeDecimal(priceAmount);
    const purchase = purchasePriceRsd.trim() ? nonNegativeDecimal(purchasePriceRsd) : undefined;
    const commission = manualCommissionEur.trim() ? nonNegativeDecimal(manualCommissionEur) : undefined;
    const stock = nonNegativeInteger(stockQuantity);
    const threshold = nonNegativeInteger(lowStockThreshold);

    if (!name.trim() && (!selectedType || !selectedType.auto_name_enabled)) {
      nextErrors.name = 'Unesi naziv ili izaberi tip koji podržava automatsko generisanje naziva.';
    }
    if (price === null) nextErrors.price_amount = 'Unesi ispravnu prodajnu cenu.';
    if (purchasePriceRsd.trim() && purchase === null) nextErrors.purchase_price_rsd = 'Unesi ispravnu nabavnu cenu.';
    if (manualCommissionEur.trim() && commission === null) nextErrors.manual_commission_eur = 'Unesi ispravnu proviziju.';
    if (!description.trim()) nextErrors.description = 'Opis je obavezan.';
    if (stock === null) nextErrors.stock_quantity = 'Lager mora biti ceo broj 0 ili veći.';
    if (threshold === null) nextErrors.low_stock_threshold = 'Prag lagera mora biti ceo broj 0 ili veći.';

    for (const field of standardSpecificationFields) {
      const value = specs[String(field.id)] ?? '';
      if (field.required && !value.trim()) {
        nextErrors[`specs.${field.id}`] = `${field.name} je obavezno polje.`;
      }
    }

    for (const field of storageFields) {
      const rows = storageRows[String(field.id)] ?? [emptyStorageRow()];
      const hasEnteredRow = rows.some((row) => row.type.trim() || row.capacityGb.trim());
      if (field.required && !hasEnteredRow) {
        nextErrors[`spec_lists.${field.id}`] = `${field.name} je obavezno polje.`;
      }
      if (hasEnteredRow) rows.forEach((row, index) => {
        if (!row.type.trim()) {
          nextErrors[`spec_lists.${field.id}.${index}`] = 'Izaberi tip diska.';
        }
        const capacity = nonNegativeInteger(row.capacityGb);
        if (capacity === null || capacity > 10000000) {
          nextErrors[`spec_capacities.${field.id}.${index}`] = 'Kapacitet mora biti ceo broj od 0 do 10000000 GB.';
        }
      });
    }

    setErrors(nextErrors);
    setSubmitError(null);
    if (Object.keys(nextErrors).length > 0 || price === null || stock === null || threshold === null) {
      feedback.notify({
        tone: 'warning',
        title: 'Dopuni obavezna polja',
        message: 'Sve što si uneo ostaje u formi. Ispravi označena polja i ponovo klikni Kreiraj artikal.',
      });
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
        .map((row) => ({
          type: row.type.trim(),
          capacity_gb: nonNegativeInteger(row.capacityGb) ?? 0,
        }));
      const firstRow = rows[0];
      if (!firstRow) continue;
      cleanSpecs[key] = firstRow.type;
      cleanSpecLists[key] = rows.map((row) => row.type);
      cleanSpecCapacities[key] = rows.map((row) => row.capacity_gb);
      cleanSpecStructured[key] = rows;
    }

    const input: AdminProductCreateInput = {
      product_type_id: optionalPositiveId(productTypeId),
      brand_id: optionalPositiveId(brandId),
      product_line_id: optionalPositiveId(lineId),
      model_name: modelName.trim() || undefined,
      name: name.trim() || undefined,
      regenerate_name: Boolean(selectedType?.auto_name_enabled && !name.trim()),
      regenerate_sku: true,
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

    mutation.mutate({
      input,
      imageFiles: imageUploadEnabled ? images : [],
    });
  };

  return (
    <Screen contentStyle={styles.content} keyboardShouldPersistTaps="handled">
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Nazad</Text>
      </Pressable>

      <View style={styles.heading}>
        <Text style={styles.eyebrow}>ADMIN · KATALOG</Text>
        <Text style={styles.title}>Dodaj artikal</Text>
        <Text style={styles.copy}>
          Forma koristi ista Laravel pravila kao CMS. SKU, naziv, zavisnosti specifikacija i završna validacija ostaju autoritativno na serveru.
        </Text>
      </View>

      <Card muted style={styles.notice}>
        <Text style={styles.noticeTitle}>Batch 2B · Diskovi i automatski kapacitet</Text>
        <Text style={styles.noticeCopy}>
          Multi-disk polje prati isti CMS storage model: do 8 diskova, redosled unosa je redosled prikaza, a ukupan kapacitet je samo pregled i ponovo ga računa backend.
        </Text>
      </Card>

      {submitError ? (
        <Card style={styles.errorCard}>
          <Text style={styles.errorTitle}>Artikal nije sačuvan</Text>
          <Text style={styles.errorCopy}>{submitError}</Text>
        </Card>
      ) : null}

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Identitet artikla</Text>
        <SelectSheet
          label="Tip artikla"
          value={productTypeId}
          placeholder="Bez tipa / ručni naziv"
          options={[
            { value: '', label: 'Bez tipa / ručni naziv' },
            ...options.types.map((type) => ({
              value: String(type.id),
              label: type.name,
              detail: type.category_name ?? 'Nema sistemsku kategoriju',
            })),
          ]}
          onChange={changeType}
        />
        <SelectSheet
          label="Brend"
          value={brandId}
          placeholder={productTypeId ? 'Bez brenda' : 'Prvo izaberi tip artikla'}
          disabled={!productTypeId}
          options={[
            { value: '', label: 'Bez brenda' },
            ...brandOptions,
          ]}
          onChange={(value) => {
            setBrandId(value);
            setLineId('');
          }}
        />
        <SelectSheet
          label="Linija"
          value={lineId}
          placeholder={!productTypeId ? 'Prvo izaberi tip artikla' : brandId ? 'Bez linije' : 'Prvo izaberi brend'}
          disabled={!productTypeId || !brandId}
          options={[{ value: '', label: 'Bez linije' }, ...lineOptions]}
          onChange={setLineId}
        />
        <TextField
          label="Model"
          value={modelName}
          onChangeText={setModelName}
          placeholder="npr. IdeaPad Slim 5"
        />
        <TextField
          label="Naziv"
          value={name}
          onChangeText={setName}
          error={errors.name}
          placeholder={productTypeId ? 'Ostavi prazno za automatski naziv' : 'Naziv artikla'}
        />
      </View>

      {selectedType ? (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Specifikacije · {selectedType.name}</Text>
          {specificationFields.length === 0 ? (
            <Text style={styles.help}>Ovaj tip nema aktivna specifikaciona polja.</Text>
          ) : null}

          {storageFields.map((field) => {
            const repeater = field.storage_repeater;
            if (!repeater) return null;
            const key = String(field.id);
            const rows = storageRows[key] ?? [emptyStorageRow()];
            const total = storageTotal(field.id);
            const groupError = errors[`spec_lists.${field.id}`] ?? errors[`spec_structured.${field.id}`] ?? errors[`specs.${field.id}`];

            return (
              <Card key={`storage-${field.id}`} muted style={styles.storageCard}>
                <View style={styles.storageHeader}>
                  <View style={styles.storageHeadingCopy}>
                    <Text style={styles.storageTitle}>{field.name}{field.required ? ' *' : ''}</Text>
                    <Text style={styles.help}>Do {repeater.max_items} diskova · redosled unosa je redosled prikaza.</Text>
                  </View>
                  <View style={styles.storageTotalBox}>
                    <Text style={styles.storageTotalLabel}>{repeater.total_field_name}</Text>
                    <Text style={styles.storageTotalValue}>
                      {total} {repeater.total_unit ?? repeater.capacity_unit}
                    </Text>
                  </View>
                </View>

                {groupError ? <Text style={styles.inlineError}>{groupError}</Text> : null}

                {rows.map((row, index) => (
                  <View key={`${field.id}:${index}`} style={styles.storageRow}>
                    <Text style={styles.storageRowTitle}>Disk {index + 1}</Text>
                    <SelectSheet
                      label="Tip diska"
                      value={row.type}
                      placeholder="Izaberi tip diska"
                      options={[
                        { value: '', label: 'Nije izabrano' },
                        ...field.options.map((option) => ({ value: option.value, label: option.label })),
                      ]}
                      onChange={(next) => updateStorageRow(field.id, index, { type: next })}
                      error={errors[`spec_lists.${field.id}.${index}`] ?? errors[`spec_structured.${field.id}.${index}.type`]}
                    />
                    <TextField
                      label={`Kapacitet (${repeater.capacity_unit})`}
                      value={row.capacityGb}
                      onChangeText={(next) => updateStorageRow(field.id, index, { capacityGb: next })}
                      keyboardType="number-pad"
                      error={errors[`spec_capacities.${field.id}.${index}`] ?? errors[`spec_structured.${field.id}.${index}.capacity_gb`]}
                      placeholder="npr. 512"
                    />
                    {rows.length > 1 || row.type || row.capacityGb ? (
                      <Pressable
                        accessibilityRole="button"
                        accessibilityLabel={`Ukloni disk ${index + 1}`}
                        onPress={() => removeStorageRow(field.id, index)}
                      >
                        <Text style={styles.remove}>Ukloni disk</Text>
                      </Pressable>
                    ) : null}
                  </View>
                ))}

                <Button
                  variant="secondary"
                  onPress={() => addStorageRow(field)}
                >
                  Dodaj još jedan disk ({rows.length}/{repeater.max_items})
                </Button>
              </Card>
            );
          })}

          {standardSpecificationFields.map((field) => {
            const key = String(field.id);
            const value = specs[key] ?? '';
            const fieldError = errors[`specs.${field.id}`];
            const detailError = errors[`spec_details.${field.id}`];
            const selectOptions = selectableOptions(field, specificationFields, specs);
            const isSelect = field.data_type === 'select' || field.options.length > 0;
            const isBoolean = field.data_type === 'boolean';
            const isNumeric =
              field.data_type === 'number'
              || field.data_type === 'integer'
              || field.filter_type === 'number'
              || field.filter_type === 'range';

            return (
              <View key={field.id} style={styles.specField}>
                {isBoolean ? (
                  <SelectSheet
                    label={specificationLabel(field)}
                    value={value}
                    options={[
                      { value: '', label: 'Nije izabrano' },
                      { value: '1', label: 'Da' },
                      { value: '0', label: 'Ne' },
                    ]}
                    onChange={(next) => setSpecValue(field, next)}
                    error={fieldError}
                  />
                ) : isSelect ? (
                  <SelectSheet
                    label={specificationLabel(field)}
                    value={value}
                    placeholder={field.parent_field_id ? 'Izaberi nakon nadređene opcije' : 'Izaberi'}
                    options={[
                      { value: '', label: 'Nije izabrano' },
                      ...selectOptions.map((option) => ({
                        value: option.value,
                        label: option.label,
                      })),
                    ]}
                    onChange={(next) => setSpecValue(field, next)}
                    error={fieldError}
                  />
                ) : (
                  <TextField
                    label={specificationLabel(field)}
                    value={value}
                    onChangeText={(next) => setSpecValue(field, next)}
                    keyboardType={isNumeric ? 'decimal-pad' : 'default'}
                    error={fieldError}
                    placeholder={field.required ? 'Obavezno' : 'Opcionalno'}
                  />
                )}

                {field.detail_input_enabled ? (
                  <TextField
                    label={field.detail_label ?? `${field.name} · detalj`}
                    value={specDetails[key] ?? ''}
                    onChangeText={(next) => setSpecDetails((current) => ({ ...current, [key]: next }))}
                    error={detailError}
                    placeholder="Dodatni detalj"
                  />
                ) : null}
              </View>
            );
          })}
        </View>
      ) : null}

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Cena i status</Text>
        <TextField
          label="Prodajna cena"
          value={priceAmount}
          onChangeText={setPriceAmount}
          keyboardType="decimal-pad"
          error={errors.price_amount}
          placeholder="0.00"
        />
        <SelectSheet
          label="Valuta"
          value={currency}
          options={options.currencies.map((item) => ({ value: item.value, label: item.label }))}
          onChange={(value) => {
            if (value === 'EUR' || value === 'RSD') setCurrency(value);
          }}
        />
        <TextField
          label="Nabavna cena (RSD)"
          value={purchasePriceRsd}
          onChangeText={setPurchasePriceRsd}
          keyboardType="decimal-pad"
          error={errors.purchase_price_rsd}
          placeholder="Opcionalno"
        />
        <TextField
          label="Ručna provizija (EUR)"
          value={manualCommissionEur}
          onChangeText={setManualCommissionEur}
          keyboardType="decimal-pad"
          error={errors.manual_commission_eur}
          placeholder="Opcionalno"
        />
        {/* MOBILE_V0_7_COMMISSION_PERCENTAGE_POLICY */}
        <Text style={styles.help}>
          Prazno polje koristi automatskih 10% vrednosti artikla (maksimalno 50 EUR). Ručna provizija mora biti najmanje 10% vrednosti artikla preračunate u EUR.
        </Text>
        <SelectSheet
          label="Status"
          value={status}
          options={options.statuses.map((item) => ({ value: item.value, label: item.label }))}
          onChange={(value) => {
            if (value === 'draft' || value === 'active' || value === 'inactive') setStatus(value);
          }}
        />
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Opis</Text>
        <TextField
          label="Opis"
          value={description}
          onChangeText={setDescription}
          multiline
          numberOfLines={6}
          error={errors.description}
          placeholder="Kompletan opis artikla"
          style={styles.multiline}
        />
        <TextField
          label="Interne napomene"
          value={notes}
          onChangeText={setNotes}
          multiline
          numberOfLines={4}
          placeholder="Opcionalno"
          style={styles.multilineSmall}
        />
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Lager</Text>
        <TextField
          label="Početno stanje"
          value={stockQuantity}
          onChangeText={setStockQuantity}
          keyboardType="number-pad"
          error={errors.stock_quantity}
        />
        <TextField
          label="Prag niskog lagera"
          value={lowStockThreshold}
          onChangeText={setLowStockThreshold}
          keyboardType="number-pad"
          error={errors.low_stock_threshold}
        />
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Fotografije</Text>
        <DraftProductImageManager
          images={images}
          onChange={setImages}
          limits={options.image_limits}
          enabled={imageUploadEnabled}
          helper="Prva fotografija je glavna. Pre čuvanja možeš menjati redosled, rotirati za 90° i ukloniti svaku fotografiju. Rotacija se fizički primenjuje na serveru odmah nakon kreiranja artikla."
        />
      </View>

      <Button onPress={submit} loading={mutation.isPending}>
        Kreiraj artikal
      </Button>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 140, gap: spacing.xl },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    heading: { gap: spacing.sm },
    eyebrow: { ...typography.small, color: theme.primary, fontWeight: '800', letterSpacing: 1.1 },
    title: { ...typography.h1, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
    notice: { gap: spacing.sm },
    noticeTitle: { ...typography.label, color: theme.ink },
    noticeCopy: { ...typography.small, color: theme.muted },
    errorCard: { borderWidth: 1, borderColor: theme.danger, gap: spacing.xs },
    errorTitle: { ...typography.label, color: theme.danger },
    errorCopy: { ...typography.small, color: theme.ink },
    section: { gap: spacing.md },
    sectionTitle: { ...typography.h2, color: theme.ink, marginTop: spacing.sm },
    help: { ...typography.small, color: theme.muted },
    specField: { gap: spacing.sm },
    storageCard: { gap: spacing.md },
    storageHeader: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
    storageHeadingCopy: { flex: 1, gap: spacing.xs },
    storageTitle: { ...typography.h3, color: theme.ink },
    storageTotalBox: { alignItems: 'flex-end', gap: 2 },
    storageTotalLabel: { ...typography.small, color: theme.muted, textAlign: 'right' },
    storageTotalValue: { ...typography.h3, color: theme.primary },
    storageRow: { gap: spacing.sm, paddingTop: spacing.sm, borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: theme.line },
    storageRowTitle: { ...typography.label, color: theme.ink },
    inlineError: { ...typography.small, color: theme.danger },
    multiline: { minHeight: 150, textAlignVertical: 'top', paddingTop: spacing.lg },
    multilineSmall: { minHeight: 110, textAlignVertical: 'top', paddingTop: spacing.lg },
    imageRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md,
      paddingVertical: spacing.sm,
      borderBottomWidth: StyleSheet.hairlineWidth,
      borderBottomColor: theme.line,
    },
    imageCopy: { flex: 1, gap: 2 },
    imageName: { ...typography.label, color: theme.ink },
    imageMeta: { ...typography.small, color: theme.muted },
    remove: { ...typography.label, color: theme.danger, paddingVertical: spacing.sm },
  });
}
