import { useMemo, useState } from 'react';
import {
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';
import { router } from 'expo-router';
import {
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import {
  AsyncLookup,
  type AsyncLookupOption,
} from '@/components/ui/async-lookup';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmAction } from '@/components/ui/confirm-action';
import { SelectSheet } from '@/components/ui/select-sheet';
import {
  ErrorState,
  LoadingState,
  UnavailableState,
} from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminWarranties,
  type AdminWarrantyRule,
  type AdminWarrantyRuleInput,
  type AdminWarrantyRuleProduct,
  type AdminWarrantyRuleScope,
} from '@/features/admin/warranties-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

type ProductOption = AsyncLookupOption & {
  product: AdminWarrantyRuleProduct;
};

const SCOPE_OPTIONS = [
  { value: 'global', label: 'Svi proizvodi' },
  { value: 'category', label: 'Kategorija' },
  { value: 'product', label: 'Pojedinačni proizvod' },
] as const;

function errorMessage(error: unknown, fallback: string): string {
  return error instanceof Error && error.message.trim()
    ? error.message
    : fallback;
}

function integerField(
  value: string,
  label: string,
  min: number,
  max: number,
): number {
  const normalized = value.trim();
  const parsed = Number(normalized);

  if (
    normalized === ''
    || !Number.isInteger(parsed)
    || parsed < min
    || parsed > max
  ) {
    throw new Error(`${label} mora biti ceo broj od ${min} do ${max}.`);
  }

  return parsed;
}

function optionalIntegerField(
  value: string,
  label: string,
  min: number,
  max: number,
): number | null {
  if (!value.trim()) {
    return null;
  }

  return integerField(value, label, min, max);
}

export default function AdminWarrantyRulesScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const allowed = can('warranties.manage');
  const feedback = useAppFeedback();
  const client = useQueryClient();

  const [editingId, setEditingId] = useState<number | null> (null);
  const [name, setName] = useState('');
  const [scope, setScope] = useState<AdminWarrantyRuleScope> ('global');
  const [categoryId, setCategoryId] = useState('');
  const [productId, setProductId] = useState<number | null> (null);
  const [productQuery, setProductQuery] = useState('');
  const [durationMonths, setDurationMonths] = useState('24');
  const [durationDays, setDurationDays] = useState('0');
  const [maintenanceMonths, setMaintenanceMonths] = useState('');
  const [priority, setPriority] = useState('0');
  const [terms, setTerms] = useState('');
  const [isActive, setIsActive] = useState(true);
  const [confirmBackfill, setConfirmBackfill] = useState(false);

  const query = useQuery({
    queryKey: adminQueryKeys.warrantyRules(),
    queryFn: () => apiAdminWarranties.rules(),
    enabled: allowed,
  });

  const resetForm = () => {
    setEditingId(null);
    setName('');
    setScope('global');
    setCategoryId('');
    setProductId(null);
    setProductQuery('');
    setDurationMonths('24');
    setDurationDays('0');
    setMaintenanceMonths('');
    setPriority('0');
    setTerms('');
    setIsActive(true);
  };

  const editRule = (rule: AdminWarrantyRule) => {
    setEditingId(rule.id);
    setName(rule.name);
    setScope(rule.scope_type);
    setCategoryId(rule.category_id ? String(rule.category_id) : '');
    setProductId(rule.product_id);
    setProductQuery(
      rule.product_name
        ? `${rule.product_sku ? `${rule.product_sku} · ` : ''}${rule.product_name}`
        : '',
    );
    setDurationMonths(String(rule.duration_months));
    setDurationDays(String(rule.duration_days));
    setMaintenanceMonths(
      rule.maintenance_interval_months === null
        ? ''
        : String(rule.maintenance_interval_months),
    );
    setPriority(String(rule.priority));
    setTerms(rule.terms ?? '');
    setIsActive(rule.is_active);
  };

  const buildInput = (): AdminWarrantyRuleInput => {
    const cleanName = name.trim();

    if (!cleanName) {
      throw new Error('Naziv pravila je obavezan.');
    }

    const input: AdminWarrantyRuleInput = {
      name: cleanName,
      scope_type: scope,
      duration_months: integerField(
        durationMonths,
        'Trajanje u mesecima',
        0,
        240,
      ),
      duration_days: integerField(
        durationDays,
        'Dodatni dani',
        0,
        3650,
      ),
      maintenance_interval_months: optionalIntegerField(
        maintenanceMonths,
        'Preventivni interval',
        1,
        120,
      ),
      priority: integerField(priority, 'Prioritet', -1000, 1000),
      terms: terms.trim() || null,
      is_active: isActive,
    };

    if (scope === 'category') {
      const parsedCategory = Number(categoryId);

      if (!Number.isInteger(parsedCategory) || parsedCategory <= 0) {
        throw new Error('Izaberi kategoriju za ovo pravilo.');
      }

      input.category_id = parsedCategory;
      input.product_id = null;
    } else if (scope === 'product') {
      if (!productId) {
        throw new Error('Izaberi proizvod za ovo pravilo.');
      }

      input.product_id = productId;
      input.category_id = null;
    } else {
      input.category_id = null;
      input.product_id = null;
    }

    return input;
  };

  const saveMutation = useMutation({
    mutationFn: async () => {
      const input = buildInput();

      if (editingId !== null) {
        return apiAdminWarranties.updateRule(editingId, input);
      }

      return apiAdminWarranties.createRule(input);
    },
    onSuccess: async () => {
      await Promise.all([
        client.invalidateQueries({ queryKey: adminQueryKeys.warrantyRules() }),
        client.invalidateQueries({ queryKey: adminQueryKeys.warranties() }),
      ]);
      feedback.notify({
        tone: 'success',
        title: editingId === null ? 'Pravilo je kreirano' : 'Pravilo je izmenjeno',
        message: 'Nova pravila utiču na buduće garantne listove; postojeći snapshot ostaje nepromenjen.',
      });
      resetForm();
    },
    onError: (error) => {
      feedback.notify({
        tone: 'danger',
        title: 'Pravilo nije sačuvano',
        message: errorMessage(error, 'Proveri podatke i pokušaj ponovo.'),
      });
    },
  });

  const backfillMutation = useMutation({
    mutationFn: () => apiAdminWarranties.backfill(500),
    onSuccess: async (response) => {
      setConfirmBackfill(false);
      await client.invalidateQueries({ queryKey: adminQueryKeys.warranties() });
      feedback.notify({
        tone: 'success',
        title: 'Backfill je završen',
        message: `Generisano: ${response.data.created} garantnih listova.`,
      });
    },
    onError: (error) => {
      feedback.notify({
        tone: 'danger',
        title: 'Backfill nije uspeo',
        message: errorMessage(error, 'Pokušaj ponovo kasnije.'),
      });
    },
  });

  if (!allowed) {
    return <UnavailableState title="Pravila garancije nisu dostupna" />;
  }

  if (query.isLoading) {
    return <LoadingState label="Učitavanje pravila garancije…" />;
  }

  if (query.isError || !query.data) {
    return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  }

  const data = query.data;
  const productNeedle = productQuery.trim().toLocaleLowerCase('sr');
  const productOptions: ProductOption[] = productNeedle.length < 2
    ? []
    : data.products
        .filter((product) => {
          const haystack = `${product.sku} ${product.name}`.toLocaleLowerCase('sr');
          return haystack.includes(productNeedle);
        })
        .slice(0, 8)
        .map((product) => ({
          id: product.id,
          label: product.name,
          subtitle: product.sku || null,
          product,
        }));

  const canSave = editingId === null
    ? data.capabilities.create
    : data.capabilities.update;

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Garancije</Text>
      </Pressable>

      <PageHeader
        title="Pravila garancije"
        eyebrow="Admin · Wave A"
        name={bootstrap?.user.name}
      />

      <Text style={styles.copy}>
        Pravila se primenjuju na buduće garantne listove po prioritetu:
        proizvod, kategorija, pa globalno pravilo.
      </Text>

      {data.capabilities.backfill ? (
        <Button
          variant="secondary"
          onPress={() => setConfirmBackfill(true)}
        >
          Generiši nedostajuće garancije
        </Button>
      ) : null}

      <Card style={styles.card}>
        <View style={styles.titleRow}>
          <Text style={styles.sectionTitle}>
            {editingId === null ? 'Novo pravilo' : `Izmena #${editingId}`}
          </Text>
          {editingId !== null ? (
            <Button variant="secondary" onPress={resetForm}>
              Novo
            </Button>
          ) : null}
        </View>

        <TextField label="Naziv" value={name} onChangeText={setName} />

        <SelectSheet
          label="Obuhvat"
          value={scope}
          options={SCOPE_OPTIONS.map((option) => ({ ...option }))}
          onChange={(value) => {
            if (value !== 'global' && value !== 'category' && value !== 'product') {
              return;
            }
            setScope(value);
            setCategoryId('');
            setProductId(null);
            setProductQuery('');
          }}
        />

        {scope === 'category' ? (
          <SelectSheet
            label="Kategorija"
            value={categoryId}
            options={[
              { value: '', label: 'Izaberi kategoriju' },
              ...data.categories.map((category) => ({
                value: String(category.id),
                label: category.name,
              })),
            ]}
            onChange={setCategoryId}
          />
        ) : null}

        {scope === 'product' ? (
          <View style={styles.lookupWrap}>
            <AsyncLookup<ProductOption>
              label="Proizvod"
              query={productQuery}
              onQueryChange={(value) => {
                setProductQuery(value);
                setProductId(null);
              }}
              options={productOptions}
              onSelect={(option) => {
                setProductId(option.product.id);
                setProductQuery(`${option.product.sku} · ${option.product.name}`);
              }}
              placeholder="SKU ili naziv proizvoda"
            />
            {productId ? (
              <Text style={styles.selected}>Izabran proizvod ID {productId}</Text>
            ) : null}
          </View>
        ) : null}

        <TextField
          label="Trajanje (meseci)"
          value={durationMonths}
          onChangeText={setDurationMonths}
          keyboardType="number-pad"
        />
        <TextField
          label="Dodatni dani"
          value={durationDays}
          onChangeText={setDurationDays}
          keyboardType="number-pad"
        />
        <TextField
          label="Preventivni interval (meseci)"
          value={maintenanceMonths}
          onChangeText={setMaintenanceMonths}
          keyboardType="number-pad"
          placeholder="Bez intervala"
        />
        <TextField
          label="Prioritet"
          value={priority}
          onChangeText={setPriority}
          keyboardType="numbers-and-punctuation"
        />
        <TextField
          label="Uslovi"
          value={terms}
          onChangeText={setTerms}
          multiline
          numberOfLines={6}
        />

        <Button
          variant={isActive ? 'secondary' : 'danger'}
          onPress={() => setIsActive((current) => !current)}
        >
          {isActive ? 'Status: Aktivno' : 'Status: Neaktivno'}
        </Button>

        {canSave ? (
          <Button
            loading={saveMutation.isPending}
            onPress={() => saveMutation.mutate()}
          >
            {editingId === null ? 'Kreiraj pravilo' : 'Sačuvaj pravilo'}
          </Button>
        ) : (
          <Text style={styles.copy}>Ova akcija trenutno nije dostupna.</Text>
        )}
      </Card>

      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Postojeća pravila</Text>
        {data.data.length === 0 ? (
          <Text style={styles.copy}>Nema definisanih pravila.</Text>
        ) : (
          data.data.map((rule) => (
            <Pressable
              key={rule.id}
              accessibilityRole="button"
              onPress={() => editRule(rule)}
              style={({ pressed }) => [
                styles.ruleRow,
                pressed ? styles.pressed : null,
              ]}
            >
              <View style={styles.ruleCopy}>
                <Text style={styles.ruleTitle}>{rule.name}</Text>
                <Text style={styles.ruleMeta}>
                  {rule.scope_type === 'product'
                    ? rule.product_name ?? 'Obrisan proizvod'
                    : rule.scope_type === 'category'
                      ? rule.category_name ?? 'Obrisana kategorija'
                      : 'Svi proizvodi'}
                  {' · Prioritet '}{rule.priority}
                </Text>
              </View>
              <Text style={rule.is_active ? styles.active : styles.inactive}>
                {rule.is_active ? 'Aktivno' : 'Neaktivno'}
              </Text>
            </Pressable>
          ))
        )}
      </Card>

      <ConfirmAction
        visible={confirmBackfill}
        title="Generiši nedostajuće garancije"
        message="Biće obrađeno najviše 500 kompletiranih porudžbina u dozvoljenom supplier scope-u. Postojeći garantni listovi se ne dupliraju."
        confirmLabel="Pokreni backfill"
        busy={backfillMutation.isPending}
        onCancel={() => setConfirmBackfill(false)}
        onConfirm={() => backfillMutation.mutate()}
      />
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: {
      gap: spacing.lg,
      paddingBottom: spacing.xxl,
    },
    back: {
      ...typography.label,
      color: theme.primary,
    },
    copy: {
      ...typography.body,
      color: theme.muted,
    },
    card: {
      gap: spacing.md,
    },
    titleRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    sectionTitle: {
      ...typography.h3,
      color: theme.ink,
      flex: 1,
    },
    lookupWrap: {
      gap: spacing.xs,
    },
    selected: {
      ...typography.small,
      color: theme.success,
    },
    ruleRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: theme.line,
      paddingVertical: spacing.md,
    },
    pressed: {
      opacity: 0.72,
    },
    ruleCopy: {
      flex: 1,
      gap: spacing.xs,
    },
    ruleTitle: {
      ...typography.label,
      color: theme.ink,
    },
    ruleMeta: {
      ...typography.small,
      color: theme.muted,
    },
    active: {
      ...typography.small,
      color: theme.success,
    },
    inactive: {
      ...typography.small,
      color: theme.danger,
    },
  });
}
