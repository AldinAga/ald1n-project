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
  type AdminDirectSaleInput,
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

function validIsoDateOnOrAfterToday(value: string): boolean {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value.trim());
  if (!match) return false;
  const year = Number(match[1]);
  const month = Number(match[2]);
  const day = Number(match[3]);
  const candidate = new Date(Date.UTC(year, month - 1, day));
  if (candidate.getUTCFullYear() !== year || candidate.getUTCMonth() !== month - 1 || candidate.getUTCDate() !== day) return false;
  const now = new Date();
  const today = `${now.getFullYear().toString().padStart(4, '0')}-${(now.getMonth() + 1).toString().padStart(2, '0')}-${now.getDate().toString().padStart(2, '0')}`;
  return value.trim() >= today;
}

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
  const [installmentCount, setInstallmentCount] = useState('1');
  const [paymentDueAt, setPaymentDueAt] = useState('');
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
      await Promise.all([
        client.invalidateQueries({ queryKey: ['admin'] }),
        client.invalidateQueries({ queryKey: ['products'] }),
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

  const prepareSubmission = () => {
    const nextErrors: Record<string, string> = {};
    const qty = positiveInteger(quantity);
    const price = positiveDecimal(salePriceRsd);

    if (qty === null || qty > 1000) nextErrors.quantity = 'Količina mora biti ceo broj između 1 i 1000.';
    else if (qty > options.product.stock_quantity) nextErrors.quantity = 'Količina je veća od raspoloživog lagera.';
    if (price === null) nextErrors.sale_price_rsd = 'Prodajna cena mora biti veća od nule.';
    else if (options.product.catalog_unit_price_rsd !== null && price > options.product.catalog_unit_price_rsd) {
      nextErrors.sale_price_rsd = `Cena po komadu ne sme biti veća od ${moneyRsd(options.product.catalog_unit_price_rsd)}.`;
    }
    const deferred = paymentMethod === 'deferred_payment';
    const installments = deferred ? positiveInteger(installmentCount) : null;
    if (deferred && (installments === null || installments > 24)) nextErrors.installment_count = 'Broj rata mora biti ceo broj između 1 i 24.';
    if (deferred && !validIsoDateOnOrAfterToday(paymentDueAt)) nextErrors.payment_due_at = 'Unesi današnji ili budući datum u formatu YYYY-MM-DD.';
    if (!idempotencyKey) nextErrors.idempotency_key = 'Idempotency ključ nije spreman. Osveži ekran.';

    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0 || qty === null || price === null || !idempotencyKey || (deferred && installments === null)) {
      feedback.notify({ tone: 'warning', title: 'Proveri podatke prodaje', message: 'Ispravi označena polja i pokušaj ponovo.' });
      return;
    }

    setPendingPayload({
      buyer_name: buyerName.trim() || undefined,
      buyer_phone: buyerPhone.trim() || undefined,
      quantity: qty,
      sale_price_rsd: price,
      payment_method: paymentMethod,
      installment_count: deferred && installments !== null ? installments : undefined,
      payment_due_at: deferred ? paymentDueAt.trim() : undefined,
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
          Maksimalna prodajna cena po komadu: {options.product.catalog_unit_price_rsd !== null ? moneyRsd(options.product.catalog_unit_price_rsd) : 'nije dostupna'}
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
          Cena može biti niža, ali ne može biti viša od zadate cene artikla preračunate u RSD.
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
            <TextField
              label="Broj rata"
              value={installmentCount}
              onChangeText={setInstallmentCount}
              keyboardType="number-pad"
              error={errors.installment_count}
            />
            <TextField
              label="Konačni datum pune uplate (YYYY-MM-DD)"
              value={paymentDueAt}
              onChangeText={setPaymentDueAt}
              placeholder="2026-12-31"
              error={errors.payment_due_at}
            />
            <Text style={styles.help}>Server kreira 1–24 rate kroz postojeći Receivables sistem; poslednja rata dospeva tačno na izabrani konačni datum.</Text>
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
          ? `Evidentira se ${pendingPayload.quantity} kom. po ${moneyRsd(pendingPayload.sale_price_rsd)}.${pendingPayload.payment_method === 'deferred_payment' ? ` Plan: ${pendingPayload.installment_count} rata, puna isplata do ${pendingPayload.payment_due_at}.` : ''} Lager će odmah biti umanjen.`
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
    totalCard: { gap: spacing.xs },
    totalLabel: { ...typography.label, color: theme.muted },
    totalValue: { ...typography.h2, color: theme.primary },
    inlineError: { ...typography.small, color: theme.danger },
  });
}
