import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiAdminCatalog } from '@/features/admin/catalog-admin-api';
import {
  apiCatalogAdvanced,
  type CatalogBulkInput,
  type CatalogBulkPreview,
} from '@/features/admin/catalog-advanced-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { ApiError } from '@/lib/api/client';
import { useAppTheme } from '@/theme/app-theme';

function errorMessage(error: unknown): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  return error instanceof Error ? error.message : 'Operacija nije uspela.';
}
function optionalId(value: string): number | undefined {
  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : undefined;
}
function optionalNumber(value: string): number | undefined {
  const parsed = Number(value.trim().replace(',', '.'));
  return Number.isFinite(parsed) && parsed >= 0 ? parsed : undefined;
}
function FlagButton({ label, active, onPress }: { label: string; active: boolean; onPress: () => void }) {
  return <Button variant={active ? 'secondary' : 'ghost'} onPress={onPress}>{active ? `✓ ${label}` : label}</Button>;
}

// MOBILE_V1_0_CATALOG_ADVANCED_PARITY_BATCH35
export default function AdminCatalogBulkScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { can, bootstrap } = useAuth();
  const allowed = can('catalog.manage_products');

  const [searchText, setSearchText] = useState('');
  const [search, setSearch] = useState('');
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [applyStatus, setApplyStatus] = useState(false);
  const [status, setStatus] = useState<'draft' | 'active' | 'inactive'>('inactive');
  const [applyBrand, setApplyBrand] = useState(false);
  const [brandId, setBrandId] = useState('');
  const [applyLine, setApplyLine] = useState(false);
  const [lineId, setLineId] = useState('');
  const [applyType, setApplyType] = useState(false);
  const [typeId, setTypeId] = useState('');
  const [priceAction, setPriceAction] = useState<CatalogBulkInput['price_action']>();
  const [priceValue, setPriceValue] = useState('');
  const [specAction, setSpecAction] = useState<'set' | 'clear' | undefined>();
  const [specFieldId, setSpecFieldId] = useState('');
  const [specValue, setSpecValue] = useState('');
  const [specDetail, setSpecDetail] = useState('');
  const [regenerateNames, setRegenerateNames] = useState(false);
  const [preview, setPreview] = useState<CatalogBulkPreview | null>(null);

  const productsQuery = useQuery({
    queryKey: adminQueryKeys.catalogAdvancedProducts(search),
    queryFn: () => apiAdminCatalog.list({ q: search || undefined, page: 1, per_page: 100 }),
    enabled: allowed,
  });
  const optionsQuery = useQuery({
    queryKey: adminQueryKeys.catalogBulkOptions(),
    queryFn: apiCatalogAdvanced.bulkOptions,
    enabled: allowed,
  });

  const buildInput = (): CatalogBulkInput => ({
    product_ids: selectedIds,
    apply_status: applyStatus || undefined,
    status: applyStatus ? status : undefined,
    apply_brand: applyBrand || undefined,
    brand_id: applyBrand ? optionalId(brandId) : undefined,
    apply_line: applyLine || undefined,
    product_line_id: applyLine ? optionalId(lineId) : undefined,
    apply_type: applyType || undefined,
    product_type_id: applyType ? optionalId(typeId) : undefined,
    price_action: priceAction,
    price_value: priceAction ? optionalNumber(priceValue) : undefined,
    specification_action: specAction,
    specification_field_id: specAction ? optionalId(specFieldId) : undefined,
    specification_value: specAction === 'set' ? specValue : undefined,
    specification_detail: specAction === 'set' ? specDetail : undefined,
    regenerate_names: regenerateNames || undefined,
  });

  const previewMutation = useMutation({
    mutationFn: () => apiCatalogAdvanced.bulkPreview(buildInput()),
    onSuccess: setPreview,
    onError: (error) => feedback.notify({ tone: 'warning', title: 'Bulk pregled nije uspeo', message: errorMessage(error) }),
  });
  const executeMutation = useMutation({
    mutationFn: () => apiCatalogAdvanced.bulkExecute(buildInput()),
    onSuccess: async (response) => {
      setPreview(null);
      setSelectedIds([]);
      await client.invalidateQueries({ queryKey: adminQueryKeys.catalogAdvancedRoot() });
      feedback.notify({ tone: 'success', title: 'Masovna izmena je završena', message: `${response.data.updated} artikala je obrađeno.` });
      await productsQuery.refetch();
    },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Masovna izmena nije uspela', message: errorMessage(error) }),
  });

  if (!allowed) return <UnavailableState title="Masovne izmene nisu dostupne" />;
  if (productsQuery.isLoading || optionsQuery.isLoading) return <LoadingState label="Učitavanje bulk alata…" />;
  if (productsQuery.isError || !productsQuery.data) return <ErrorState error={productsQuery.error} onRetry={() => void productsQuery.refetch()} />;
  if (optionsQuery.isError || !optionsQuery.data) return <ErrorState error={optionsQuery.error} onRetry={() => void optionsQuery.refetch()} />;

  const options = optionsQuery.data;
  const products = productsQuery.data.data;
  const selectedBrandId = optionalId(brandId);
  const visibleLines = options.lines.filter((line) => selectedBrandId === undefined || line.brand_id === selectedBrandId);
  const toggleProduct = (id: number) => {
    setPreview(null);
    setSelectedIds((current) => current.includes(id) ? current.filter((value) => value !== id) : [...current, id]);
  };

  return (
    <Screen contentStyle={styles.content} keyboardShouldPersistTaps="handled">
      <Pressable accessibilityRole="button" onPress={() => router.back()}><Text style={styles.back}>‹ Katalog i lager</Text></Pressable>
      <PageHeader title="Masovne izmene" eyebrow="Admin · Katalog" name={bootstrap?.user.name} />
      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>1. Izaberi artikle</Text>
        <TextField label="Pretraga" value={searchText} onChangeText={setSearchText} placeholder="Naziv, SKU ili model" />
        <Button variant="secondary" onPress={() => { setSearch(searchText.trim()); setSelectedIds([]); setPreview(null); }}>Pretraži</Button>
        <Text style={styles.muted}>Izabrano: {selectedIds.length} · maksimum {options.max_products}</Text>
        {products.map((product) => (
          <Button key={product.id} variant={selectedIds.includes(product.id) ? 'secondary' : 'ghost'} onPress={() => toggleProduct(product.id)}>
            {selectedIds.includes(product.id) ? '✓ ' : ''}{product.sku} · {product.name}
          </Button>
        ))}
      </Card>

      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>2. Izaberi promene</Text>
        <FlagButton label="Promeni status" active={applyStatus} onPress={() => { setApplyStatus(!applyStatus); setPreview(null); }} />
        {applyStatus ? <SelectSheet label="Novi status" value={status} options={[{ value: 'draft', label: 'Nacrt' }, { value: 'active', label: 'Aktivan' }, { value: 'inactive', label: 'Neaktivan' }]} onChange={(value) => setStatus(value === 'draft' || value === 'active' ? value : 'inactive')} /> : null}

        <FlagButton label="Promeni brend" active={applyBrand} onPress={() => { setApplyBrand(!applyBrand); setPreview(null); }} />
        {applyBrand ? <SelectSheet label="Brend" value={brandId} options={[{ value: '', label: 'Bez brenda' }, ...options.brands.map((item) => ({ value: String(item.id), label: item.name }))]} onChange={(value) => { setBrandId(value); setLineId(''); }} /> : null}

        <FlagButton label="Promeni liniju" active={applyLine} onPress={() => { setApplyLine(!applyLine); setPreview(null); }} />
        {applyLine ? <SelectSheet label="Linija" value={lineId} options={[{ value: '', label: 'Bez linije' }, ...visibleLines.map((item) => ({ value: String(item.id), label: item.name }))]} onChange={setLineId} /> : null}

        <FlagButton label="Promeni tip" active={applyType} onPress={() => { setApplyType(!applyType); setPreview(null); }} />
        {applyType ? <SelectSheet label="Tip proizvoda" value={typeId} options={[{ value: '', label: 'Bez tipa' }, ...options.types.map((item) => ({ value: String(item.id), label: item.name }))]} onChange={setTypeId} /> : null}

        <SelectSheet label="Promena cene" value={priceAction ?? ''} options={[
          { value: '', label: 'Bez promene cene' },
          { value: 'set', label: 'Postavi iznos' },
          { value: 'increase_percent', label: 'Povećaj %' },
          { value: 'decrease_percent', label: 'Smanji %' },
          { value: 'increase_fixed', label: 'Povećaj fiksno' },
          { value: 'decrease_fixed', label: 'Smanji fiksno' },
        ]} onChange={(value) => setPriceAction(value === 'set' || value === 'increase_percent' || value === 'decrease_percent' || value === 'increase_fixed' || value === 'decrease_fixed' ? value : undefined)} />
        {priceAction ? <TextField label="Vrednost promene" value={priceValue} onChangeText={setPriceValue} keyboardType="decimal-pad" /> : null}

        <SelectSheet label="Specifikacija" value={specAction ?? ''} options={[{ value: '', label: 'Bez promene specifikacije' }, { value: 'set', label: 'Postavi vrednost' }, { value: 'clear', label: 'Obriši vrednost' }]} onChange={(value) => setSpecAction(value === 'set' || value === 'clear' ? value : undefined)} />
        {specAction ? <SelectSheet label="Polje" value={specFieldId} options={[{ value: '', label: 'Izaberi polje' }, ...options.specification_fields.map((item) => ({ value: String(item.id), label: `${item.name}${item.unit ? ` (${item.unit})` : ''}` }))]} onChange={setSpecFieldId} /> : null}
        {specAction === 'set' ? <TextField label="Vrednost specifikacije" value={specValue} onChangeText={setSpecValue} /> : null}
        {specAction === 'set' ? <TextField label="Detalj specifikacije (opciono)" value={specDetail} onChangeText={setSpecDetail} /> : null}

        <FlagButton label="Regeneriši nazive prema šablonu" active={regenerateNames} onPress={() => { setRegenerateNames(!regenerateNames); setPreview(null); }} />
        <Button loading={previewMutation.isPending} disabled={selectedIds.length === 0} onPress={() => previewMutation.mutate()}>Pregledaj izmene</Button>
      </Card>

      {preview ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>3. Potvrda</Text>
          <Text style={styles.muted}>Biće obrađeno {preview.count} artikala.</Text>
          {preview.summary.map((item) => <Text key={item} style={styles.body}>• {item}</Text>)}
          {preview.products.slice(0, 20).map((item) => <Text key={item.id} style={styles.muted}>{item.sku} · {item.name}</Text>)}
          {preview.count > 20 ? <Text style={styles.muted}>… i još {preview.count - 20} artikala</Text> : null}
          <Button loading={executeMutation.isPending} onPress={() => executeMutation.mutate()}>Izvrši prikazane izmene</Button>
        </Card>
      ) : null}
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: 140 },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    card: { gap: spacing.md },
    sectionTitle: { ...typography.h3, color: theme.ink },
    body: { ...typography.body, color: theme.ink },
    muted: { ...typography.body, color: theme.muted },
  });
}
