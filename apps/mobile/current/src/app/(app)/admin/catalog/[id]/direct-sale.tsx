import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams, type Href } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmAction } from '@/components/ui/confirm-action';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminCatalog,
  type AdminDirectSaleImmediatePaymentMethod,
  type AdminDirectSaleInput,
  type AdminDirectSaleInstallment,
  type AdminDirectSalePaymentMethod,
} from '@/features/admin/catalog-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { ApiError } from '@/lib/api/client';
import { useAppTheme } from '@/theme/app-theme';

function apiMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return fallback;
}

function fieldErrors(error: unknown): Record<string, string> {
  if (!(error instanceof ApiError)) return {};
  const result: Record<string, string> = {};
  for (const [key, values] of Object.entries(error.errors)) {
    const first = values[0];
    if (first) result[key] = first;
  }
  return result;
}

function positiveInteger(value: string): number | null {
  if (!/^\d+$/.test(value.trim())) return null;
  const parsed = Number(value);
  return Number.isSafeInteger(parsed) && parsed >= 1 ? parsed : null;
}

function positiveDecimal(value: string): number | null {
  const normalized = value.trim().replace(',', '.');
  if (!normalized) return null;
  const parsed = Number(normalized);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}

function moneyRsd(value: number): string {
  return `${new Intl.NumberFormat('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value)} RSD`;
}

type InstallmentDraft = { amount: string; dueAt: string };

function localIsoDate(date: Date): string {
  return `${date.getFullYear().toString().padStart(4, '0')}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')}`;
}

function localTodayIso(): string {
  return localIsoDate(new Date());
}

function defaultInstallmentDueAt(index: number): string {
  if (index <= 0) return localTodayIso();
  const now = new Date();
  const firstOfTarget = new Date(now.getFullYear(), now.getMonth() + index, 1, 12, 0, 0, 0);
  const lastDay = new Date(firstOfTarget.getFullYear(), firstOfTarget.getMonth() + 1, 0).getDate();
  firstOfTarget.setDate(Math.min(now.getDate(), lastDay));
  return localIsoDate(firstOfTarget);
}

function buildInstallmentDrafts(count: number, current: InstallmentDraft[] = []): InstallmentDraft[] {
  return Array.from({ length: count }, (_, index) => current[index] ?? {
    amount: '',
    dueAt: defaultInstallmentDueAt(index),
  });
}

function validIsoDateOnOrAfterToday(value: string): boolean {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value.trim());
  if (!match) return false;
  const year = Number(match[1]);
  const month = Number(match[2]);
  const day = Number(match[3]);
  const candidate = new Date(Date.UTC(year, month - 1, day));
  if (candidate.getUTCFullYear() !== year || candidate.getUTCMonth() !== month - 1 || candidate.getUTCDate() !== day) return false;
  return value.trim() >= localTodayIso();
}

// MOBILE_V1_0_DIRECT_SALE_UNBOUNDED_PRICE_BATCH21
// MOBILE_V0_9_DIRECT_SALE_DEFERRED_PAYMENT_RECEIVABLES_BATCH5B_V2
// MOBILE_V0_8_SUPERADMIN_DIRECT_SALE_BATCH10
export default function AdminProductDirectSaleScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { bootstrap } = useAuth();
  const isSuperAdmin = bootstrap?.user.role?.slug === 'superadmin';
  const params = useLocalSearchParams<{ id?: string | string[] }> ();
  const rawId = Array.isArray(params.id) ? params.id[0] : params.id;
  const productId = Number(rawId);
  const validId = Number.isInteger(productId) && productId > 0;

  const [buyerName, setBuyerName] = useState('');
  const [buyerPhone, setBuyerPhone] = useState('');
  const [quantity, setQuantity] = useState('1');
  const [salePriceRsd, setSalePriceRsd] = useState('');
  const [paymentMethod, setPaymentMethod] = useState<AdminDirectSalePaymentMethod> ('cash');
  const [installmentCount, setInstallmentCount] = useState('3');
  const [firstPaymentMethod, setFirstPaymentMethod] = useState<AdminDirectSaleImmediatePaymentMethod> ('cash');
  const [installmentRows, setInstallmentRows] = useState<InstallmentDraft[]> (() => buildInstallmentDrafts(3));
  const [idempotencyKey, setIdempotencyKey] = useState('');
  const [errors, setErrors] = useState<Record<string, string>> ({});
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [pendingPayload, setPendingPayload] = useState<AdminDirectSaleInput | null> (null);

  const optionsQuery = useQuery({
    queryKey: ['admin', 'catalog', 'direct-sale', productId],
    queryFn: () => apiAdminCatalog.directSaleOptions(productId),
    enabled: isSuperAdmin && validId,
    staleTime: 30_000,
  });

  useEffect(() => {
    const options = optionsQuery.data;
    if (!options || idempotencyKey) return;
    setIdempotencyKey(options.idempotency_key);
    if (options.product.catalog_unit_price_rsd !== null) {
      setSalePriceRsd(options.product.catalog_unit_price_rsd.toFixed(2));
    }
  }, [optionsQuery.data, idempotencyKey]);

  const saleMutation = useMutation({
    mutationFn: (input: AdminDirectSaleInput) => apiAdminCatalog.recordDirectSale(productId, input),
    onSuccess: async (response) => {
      // MOBILE_BUILD16_CATALOG_MUTATION_FRESHNESS_BATCH134
      await Promise.all([
        client.invalidateQueries({ queryKey: ['admin'] }),
        client.invalidateQueries({ queryKey: ['products'] }),
        client.invalidateQueries({ queryKey: ['product'] }),
        client.invalidateQueries({ queryKey: ['catalog-filters'] }),
      ]);
      feedback.notify({
        tone: 'success',
        title: 'Prodaja je evidentirana',
        message: `${response.data.order_number} · preostali lager: ${response.data.stock_quantity_after}`,
        durationMs: 5200,
      });
      router.replace({
        pathname: '/admin/orders/[id]',
        params: { id: String(response.data.order_id) },
      } as Href);
    },
    onError: (error) => {
      setErrors(fieldErrors(error));
      feedback.notify({
        tone: 'danger',
        title: 'Prodaja nije evidentirana',
        message: apiMessage(
          error,
          'Ako je mreža prekinuta nakon slanja, ne menjaj podatke i ponovi isti zahtev. Isti idempotency ključ sprečava duplu prodaju.',
        ),
        durationMs: 6500,
      });
    },
  });

  if (!isSuperAdmin) return <UnavailableState title="Direktna prodaja je dostupna samo SuperAdministratoru" />;
  if (!validId) return <UnavailableState title="Artikal nije validan" />;
  if (optionsQuery.isLoading) return <LoadingState label="Priprema direktne prodaje…" />;
  if (optionsQuery.isError || !optionsQuery.data) {
    return <ErrorState error={optionsQuery.error} onRetry={() => void optionsQuery.refetch()} />;
  }

  const options = optionsQuery.data;
  const parsedQuantity = positiveInteger(quantity);
  const parsedPrice = positiveDecimal(salePriceRsd);
  const total = parsedQuantity !== null && parsedPrice !== null ? parsedQuantity * parsedPrice : null;
  const expectedInstallmentCents = total !== null ? Math.round(total * 100) : null;
  const installmentTotalCents = installmentRows.reduce((sum, row) => {
    const amount = positiveDecimal(row.amount);
    return sum + (amount === null ? 0 : Math.round(amount * 100));
  }, 0);
  const installmentDeltaCents = expectedInstallmentCents === null ? null : expectedInstallmentCents - installmentTotalCents;

  const updateInstallmentCount = (value: string) => {
    setInstallmentCount(value);
    const parsed = positiveInteger(value);
    if (parsed !== null && parsed <= 24) {
      setInstallmentRows((current) => buildInstallmentDrafts(parsed, current));
    }
  };

  const updateInstallmentRow = (index: number, patch: Partial<InstallmentDraft>) => {
    setInstallmentRows((current) => current.map((row, rowIndex) => (
      rowIndex === index ? { ...row, ...patch } : row
    )));
  };

  const splitInstallmentsEvenly = () => {
    const count = positiveInteger(installmentCount);
    if (expectedInstallmentCents === null || count === null || count > 24 || expectedInstallmentCents < count) {
      feedback.notify({ tone: 'warning', title: 'Unesi cenu i broj rata', message: 'Ukupna prodaja i broj rata moraju biti validni pre raspodele.' });
      return;
    }
    const base = Math.floor(expectedInstallmentCents / count);
    let remainder = expectedInstallmentCents % count;
    setInstallmentRows((current) => buildInstallmentDrafts(count, current).map((row, index) => {
      const cents = base + (remainder > 0 ? 1 : 0);
      if (remainder > 0) remainder -= 1;
      return {
        amount: (cents / 100).toFixed(2),
        dueAt: index === 0 ? localTodayIso() : (row.dueAt || defaultInstallmentDueAt(index)),
      };
    }));
  };

  const prepareSubmission = () => {
    const nextErrors: Record<string, string> = {};
    const qty = positiveInteger(quantity);
    const price = positiveDecimal(salePriceRsd);

    if (qty === null || qty > 1000) nextErrors.quantity = 'Količina mora biti ceo broj između 1 i 1000.';
    else if (qty > options.product.stock_quantity) nextErrors.quantity = 'Količina je veća od raspoloživog lagera.';
    if (price === null) nextErrors.sale_price_rsd = 'Prodajna cena mora biti veća od nule.';

    const deferred = paymentMethod === 'deferred_payment';
    const count = deferred ? positiveInteger(installmentCount) : null;
    let normalizedInstallments: AdminDirectSaleInstallment[] | undefined;
    let finalDueAt: string | undefined;

    if (deferred && (count === null || count > 24)) {
      nextErrors.installment_count = 'Broj rata mora biti ceo broj između 1 i 24.';
    } else if (deferred && count !== null) {
      if (installmentRows.length !== count) {
        nextErrors.installment_count = 'Broj redova rata mora odgovarati izabranom broju rata.';
      } else {
        const today = localTodayIso();
        let previousDue = '';
        let sumCents = 0;
        const rows: AdminDirectSaleInstallment[] = [];

        installmentRows.forEach((row, index) => {
          const amount = positiveDecimal(row.amount);
          const dueAt = index === 0 ? today : row.dueAt.trim();
          if (amount === null) {
            nextErrors[`installments.${index}.amount_rsd`] = 'Iznos rate mora biti veći od nule.';
          }
          if (!validIsoDateOnOrAfterToday(dueAt)) {
            nextErrors[`installments.${index}.due_at`] = 'Datum rate mora biti današnji ili budući datum.';
          } else if (index === 0 && dueAt !== today) {
            nextErrors[`installments.${index}.due_at`] = 'Prva rata mora biti današnja.';
          } else if (previousDue && dueAt < previousDue) {
            nextErrors[`installments.${index}.due_at`] = 'Datumi rata moraju biti hronološki poređani.';
          }
          if (amount !== null) sumCents += Math.round(amount * 100);
          if (dueAt) previousDue = dueAt;
          if (amount !== null && validIsoDateOnOrAfterToday(dueAt)) {
            rows.push({ amount_rsd: amount, due_at: dueAt });
          }
        });

        const expectedCents = price !== null && qty !== null ? Math.round(price * qty * 100) : null;
        if (expectedCents !== null && sumCents !== expectedCents) {
          nextErrors.installments = 'Zbir rata mora biti jednak ukupnoj vrednosti direktne prodaje.';
        }
        if (rows.length === count && !nextErrors.installments) {
          normalizedInstallments = rows;
          finalDueAt = rows[rows.length - 1]?.due_at;
        }
      }
    }

    if (!idempotencyKey) nextErrors.idempotency_key = 'Idempotency ključ nije spreman. Osveži ekran.';

    setErrors(nextErrors);
    if (
      Object.keys(nextErrors).length > 0
      || qty === null
      || price === null
      || !idempotencyKey
      || (deferred && (count === null || !normalizedInstallments || !finalDueAt))
    ) {
      feedback.notify({ tone: 'warning', title: 'Proveri podatke prodaje', message: 'Ispravi označena polja i pokušaj ponovo.' });
      return;
    }

    setPendingPayload({
      buyer_name: buyerName.trim() || undefined,
      buyer_phone: buyerPhone.trim() || undefined,
      quantity: qty,
      sale_price_rsd: price,
      payment_method: paymentMethod,
      installment_count: deferred && count !== null ? count : undefined,
      payment_due_at: deferred ? finalDueAt : undefined,
      installments: deferred ? normalizedInstallments : undefined,
      first_payment_method: deferred ? firstPaymentMethod : undefined,
      idempotency_key: idempotencyKey,
    });
    setConfirmOpen(true);
  };

  const confirmSale = () => {
    const payload = pendingPayload;
    setConfirmOpen(false);
    if (!payload) return;
    // MOBILE_GLOBAL_UNRESTRICTED_TAPS_V07
    // Repeated submissions intentionally reuse the same idempotency key; backend owns duplicate protection.
    saleMutation.mutate(payload);
  };

  return (
    <Screen contentStyle={styles.content} keyboardShouldPersistTaps="handled">
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Nazad na artikal</Text>
      </Pressable>

      <View style={styles.heading}>
        <Text style={styles.eyebrow}>SUPERADMIN · PRODAJA</Text>
        <Text style={styles.title}>Evidentiraj prodaju</Text>
        <Text style={styles.copy}>
          Direktna prodaja koristi isti Laravel DirectSaleService kao CMS. Standardna plaćanja se odmah zatvaraju, dok odloženo plaćanje isporučuje robu odmah i otvara postojeći Receivables plan naplate.
        </Text>
      </View>

      <Card style={styles.productCard}>
        <Text style={styles.productName}>{options.product.name}</Text>
        <Text style={styles.meta}>{options.product.sku} · status {options.product.status}</Text>
        <Text style={styles.meta}>Raspoloživ lager: {options.product.stock_quantity}</Text>
        <Text style={styles.meta}>
          Zadata cena: {options.product.catalog_price_amount} {options.product.catalog_price_currency}
        </Text>
        <Text style={styles.price}>
          Zadata cena preračunata u RSD: {options.product.catalog_unit_price_rsd !== null ? moneyRsd(options.product.catalog_unit_price_rsd) : 'nije dostupna'}
        </Text>
        {options.eur_rsd_rate !== null ? (
          <Text style={styles.meta}>EUR/RSD kurs: {options.eur_rsd_rate}</Text>
        ) : null}
      </Card>

      {!options.can_submit ? (
        <Card style={styles.blockedCard}>
          <Text style={styles.blockedTitle}>Prodaja trenutno nije dostupna</Text>
          <Text style={styles.blockedText}>{options.blocking_reason ?? 'Proveri status artikla, lager i kurs.'}</Text>
        </Card>
      ) : null}

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Kupac</Text>
        <TextField
          label="Ime kupca"
          value={buyerName}
          onChangeText={setBuyerName}
          placeholder="Krajnji kupac (opcionalno)"
          maxLength={190}
          error={errors.buyer_name}
        />
        <TextField
          label="Telefon kupca"
          value={buyerPhone}
          onChangeText={setBuyerPhone}
          keyboardType="phone-pad"
          placeholder="Opcionalno"
          maxLength={80}
          error={errors.buyer_phone}
        />
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Prodaja</Text>
        <TextField
          label="Količina"
          value={quantity}
          onChangeText={setQuantity}
          keyboardType="number-pad"
          error={errors.quantity}
        />
        <TextField
          label="Prodajna cena po komadu (RSD)"
          value={salePriceRsd}
          onChangeText={setSalePriceRsd}
          keyboardType="decimal-pad"
          error={errors.sale_price_rsd}
        />
        <Text style={styles.help}>
          Unesi pozitivnu prodajnu cenu po komadu; zadata kataloška cena služi samo kao referenca.
        </Text>
        <SelectSheet
          label="Način plaćanja"
          value={paymentMethod}
          options={options.payment_methods.map((method) => ({ value: method.value, label: method.label }))}
          onChange={(value) => {
            if (value === 'cash' || value === 'card' || value === 'bank_transfer' || value === 'other' || value === 'deferred_payment') setPaymentMethod(value);
          }}
        />
        {paymentMethod === 'deferred_payment' ? (
          <Card muted style={styles.deferredCard}>
            <Text style={styles.sectionTitle}>Plan odloženog plaćanja</Text>
            <Text style={styles.help}>
              Podrazumevano su 3 rate. Iznos i datum svake rate možeš menjati; prva rata je obavezno današnja i server je odmah evidentira kao stvarnu uplatu.
            </Text>
            <TextField
              label="Broj rata"
              value={installmentCount}
              onChangeText={updateInstallmentCount}
              keyboardType="number-pad"
              error={errors.installment_count}
            />
            <SelectSheet
              label="Način plaćanja prve rate"
              value={firstPaymentMethod}
              options={[
                { value: 'cash', label: 'Gotovina' },
                { value: 'card', label: 'Kartica' },
                { value: 'bank_transfer', label: 'Bankovni prenos' },
                { value: 'other', label: 'Ostalo' },
              ]}
              onChange={(value) => {
                if (value === 'cash' || value === 'card' || value === 'bank_transfer' || value === 'other') setFirstPaymentMethod(value);
              }}
            />
            <Button variant="secondary" onPress={splitInstallmentsEvenly}>
              Rasporedi iznos ravnomerno
            </Button>
            {installmentRows.map((row, index) => (
              <View key={`installment-${index}`} style={styles.installmentRow}>
                <Text style={styles.installmentTitle}>Rata {index + 1}</Text>
                <TextField
                  label="Iznos rate (RSD)"
                  value={row.amount}
                  onChangeText={(value) => updateInstallmentRow(index, { amount: value })}
                  keyboardType="decimal-pad"
                  error={errors[`installments.${index}.amount_rsd`]}
                />
                <TextField
                  label={index === 0 ? 'Datum dospeća · prva rata danas' : 'Datum dospeća (YYYY-MM-DD)'}
                  value={index === 0 ? localTodayIso() : row.dueAt}
                  onChangeText={(value) => updateInstallmentRow(index, { dueAt: value })}
                  disabled={index === 0}
                  placeholder={defaultInstallmentDueAt(index)}
                  error={errors[`installments.${index}.due_at`]}
                />
              </View>
            ))}
            {errors.installments ? <Text style={styles.inlineError}>{errors.installments}</Text> : null}
            <View style={styles.installmentSummary}>
              <Text style={styles.totalLabel}>Ukupno po ratama</Text>
              <Text style={styles.installmentSummaryValue}>{moneyRsd(installmentTotalCents / 100)}</Text>
              <Text style={[styles.help, installmentDeltaCents === 0 ? styles.balanced : null]}>
                {expectedInstallmentCents === null
                  ? 'Unesi cenu i količinu, zatim rasporedi iznos.'
                  : installmentDeltaCents === 0
                    ? 'Plan je usklađen sa ukupnom prodajom.'
                    : `Razlika do ukupne prodaje: ${moneyRsd((installmentDeltaCents ?? 0) / 100)}`}
              </Text>
            </View>
          </Card>
        ) : null}
      </View>

      <Card muted style={styles.totalCard}>
        <Text style={styles.totalLabel}>Ukupna prodaja</Text>
        <Text style={styles.totalValue}>{total !== null ? moneyRsd(total) : '—'}</Text>
        <Text style={styles.help}>
          {paymentMethod === 'deferred_payment'
            ? 'Artikal se odmah smatra isporučenim i lager se umanjuje, ali dug ostaje otvoren i prati se kroz Potraživanja do pune isplate.'
            : 'Nakon potvrde porudžbina se evidentira kao plaćena i lično dostavljena.'}
        </Text>
      </Card>

      <Button
        onPress={prepareSubmission}
        loading={saleMutation.isPending}
        disabled={!options.can_submit}
      >
        Evidentiraj prodaju
      </Button>

      {errors.idempotency_key ? <Text style={styles.inlineError}>{errors.idempotency_key}</Text> : null}

      <ConfirmAction
        visible={confirmOpen}
        title="Potvrdi direktnu prodaju"
        message={pendingPayload
          ? `Evidentira se ${pendingPayload.quantity} kom. po ${moneyRsd(pendingPayload.sale_price_rsd)}.${pendingPayload.payment_method === 'deferred_payment' ? ` Plan: ${pendingPayload.installment_count} rata, prva rata ${pendingPayload.installments?.[0] ? moneyRsd(pendingPayload.installments[0].amount_rsd) : '—'} odmah, puna isplata do ${pendingPayload.payment_due_at}.` : ''} Lager će odmah biti umanjen.`
          : 'Proveri podatke prodaje.'}
        confirmLabel="Potvrdi prodaju"
        cancelLabel="Odustani"
        destructive={false}
        onConfirm={confirmSale}
        onCancel={() => setConfirmOpen(false)}
      />
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.xl, paddingBottom: 120 },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    heading: { gap: spacing.xs },
    eyebrow: { ...typography.small, color: theme.primary, fontWeight: '800', letterSpacing: 1.1 },
    title: { ...typography.h1, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
    productCard: { gap: spacing.sm },
    productName: { ...typography.h2, color: theme.ink },
    meta: { ...typography.body, color: theme.muted },
    price: { ...typography.label, color: theme.ink },
    blockedCard: { gap: spacing.sm, borderWidth: 1, borderColor: theme.danger },
    blockedTitle: { ...typography.h3, color: theme.danger },
    blockedText: { ...typography.body, color: theme.ink },
    section: { gap: spacing.md },
    sectionTitle: { ...typography.h3, color: theme.ink },
    help: { ...typography.small, color: theme.muted },
    deferredCard: { gap: spacing.md },
    installmentRow: { gap: spacing.sm, paddingTop: spacing.sm, borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: theme.line },
    installmentTitle: { ...typography.label, color: theme.ink },
    installmentSummary: { gap: spacing.xs, paddingTop: spacing.sm, borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: theme.line },
    installmentSummaryValue: { ...typography.h3, color: theme.primary },
    balanced: { color: theme.success },
    totalCard: { gap: spacing.xs },
    totalLabel: { ...typography.label, color: theme.muted },
    totalValue: { ...typography.h2, color: theme.primary },
    inlineError: { ...typography.small, color: theme.danger },
  });
}
