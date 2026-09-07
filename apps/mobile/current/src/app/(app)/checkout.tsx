import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import * as Crypto from 'expo-crypto';
import { router } from 'expo-router';
import { useEffect, useRef, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Glyph } from '@/components/ui/glyph';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { useCart } from '@/features/cart/cart-provider';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';
import type { CreateOrderInput, PaymentMethod } from '@/types/api';

export default function CheckoutScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { bootstrap, hasFeature } = useAuth();
  const { items, clearCart } = useCart();
  const allowed = hasFeature('order_create');

  const options = useQuery({
    queryKey: ['order-options'],
    queryFn: api.orders.options,
    enabled: allowed && items.length > 0,
    staleTime: 5 * 60_000,
  });

  const initialized = useRef(false);
  const [shipping, setShipping] = useState({
    fullName: '',
    address: '',
    city: '',
    postalCode: '',
    phone: '',
  });
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | ''> ('');
  const [bankAccountId, setBankAccountId] = useState<number | null> (null);
  const [supplierUserId, setSupplierUserId] = useState<number | null> (null);
  const [paymentDueAt, setPaymentDueAt] = useState('');
  const [customerNote, setCustomerNote] = useState('');
  const [formError, setFormError] = useState<string | null> (null);
  const [lastSubmission, setLastSubmission] = useState<{ key: string; payloadJson: string } | null> (null);

  useEffect(() => {
    if (!options.data || initialized.current) return;
    initialized.current = true;

    const defaults = options.data.shipping_defaults;
    const user = bootstrap?.user;

    setShipping({
      fullName: defaults.full_name || user?.name || '',
      address: defaults.address || user?.address || '',
      city: defaults.city || user?.city || '',
      postalCode: defaults.postal_code || user?.postal_code || '',
      phone: defaults.phone || user?.phone || '',
    });

    const firstPayment = options.data.payment_methods[0]?.value;
    if (firstPayment) setPaymentMethod(firstPayment);
    setSupplierUserId(options.data.suppliers[0]?.id ?? null);
  }, [bootstrap?.user, options.data]);

  useEffect(() => {
    if (
      paymentMethod === 'bank_transfer'
      && bankAccountId === null
      && options.data?.bank_accounts[0]
    ) {
      setBankAccountId(options.data.bank_accounts[0].id);
    }

    if (paymentMethod !== 'bank_transfer') setBankAccountId(null);
    if (paymentMethod !== 'deferred_payment') setPaymentDueAt('');
  }, [bankAccountId, options.data?.bank_accounts, paymentMethod]);

  const mutation = useMutation({
    mutationFn: async (payload: CreateOrderInput) => {
      const payloadJson = JSON.stringify(payload);
      let key: string;

      if (lastSubmission?.payloadJson === payloadJson) {
        key = lastSubmission.key;
      } else if (lastSubmission) {
        key = Crypto.randomUUID();
      } else {
        key = options.data?.idempotency_key || Crypto.randomUUID();
      }

      setLastSubmission({ key, payloadJson });
      return api.orders.create(payload, key);
    },
    onSuccess: async (order) => {
      clearCart();
      setLastSubmission(null);
      await client.invalidateQueries({ queryKey: ['orders'] });
      await client.invalidateQueries({ queryKey: ['order-options'] });
      router.replace({ pathname: '/order/[id]', params: { id: String(order.id) } });
      feedback.notify({
        tone: 'success',
        title: 'Porudžbina je kreirana',
        message: `${order.order_number} je uspešno poslata.`,
      });
    },
    onError: (error) => {
      const message = error instanceof ApiError
        ? error.firstFieldError() ?? error.message
        : 'Porudžbina nije kreirana.';
      setFormError(message);
    },
  });

  if (!allowed) return <UnavailableState title="Kreiranje porudžbine nije dostupno" />;
  if (!items.length) {
    return (
      <UnavailableState
        title="Korpa je prazna"
        message="Dodaj proizvod u korpu pre kreiranja porudžbine."
      />
    );
  }
  if (options.isLoading) return <LoadingState label="Priprema porudžbine…" />;
  if (options.isError || !options.data) {
    return <ErrorState error={options.error} onRetry={() => void options.refetch()} />;
  }

  const selectedPayment = options.data.payment_methods.find(
    (method) => method.value === paymentMethod,
  );

  const submit = () => {
    setFormError(null);

    const required = [
      shipping.fullName,
      shipping.address,
      shipping.city,
      shipping.postalCode,
      shipping.phone,
    ];

    if (required.some((value) => !value.trim())) {
      setFormError('Popuni sva obavezna polja za isporuku.');
      return;
    }

    if (!paymentMethod) {
      setFormError('Izaberi način plaćanja.');
      return;
    }

    if (selectedPayment?.requires_bank_account && bankAccountId === null) {
      setFormError('Izaberi račun za uplatu.');
      return;
    }

    if (
      selectedPayment?.requires_due_date
      && !/^\d{4}-\d{2}-\d{2}$/.test(paymentDueAt.trim())
    ) {
      setFormError('Unesi datum dospeća u formatu YYYY-MM-DD.');
      return;
    }

    if (items.length > options.data.limits.max_items) {
      setFormError(
        `Porudžbina može imati najviše ${options.data.limits.max_items} različitih stavki.`,
      );
      return;
    }

    if (items.some((item) => item.quantity > options.data.limits.max_quantity_per_item)) {
      setFormError(
        `Maksimalna količina po stavci je ${options.data.limits.max_quantity_per_item}.`,
      );
      return;
    }

    if (customerNote.length > options.data.limits.customer_note_max_length) {
      setFormError(
        `Napomena može imati najviše ${options.data.limits.customer_note_max_length} karaktera.`,
      );
      return;
    }

    mutation.mutate({
      supplier_user_id: supplierUserId,
      shipping_full_name: shipping.fullName.trim(),
      shipping_address: shipping.address.trim(),
      shipping_city: shipping.city.trim(),
      shipping_postal_code: shipping.postalCode.trim(),
      shipping_phone: shipping.phone.trim(),
      customer_note: customerNote.trim() || null,
      payment_method: paymentMethod,
      bank_account_id: selectedPayment?.requires_bank_account ? bankAccountId : null,
      payment_due_at: selectedPayment?.requires_due_date ? paymentDueAt.trim() : null,
      items: items.map((item) => ({
        product_id: item.productId,
        quantity: item.quantity,
      })),
    });
  };

  return (
    <Screen keyboardShouldPersistTaps="handled">
      {/* MOBILE_BUILD16_CHECKOUT_REDESIGN_BATCH131 */}
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Nazad na korpu"
        onPress={() => router.back()}
        style={({ pressed }) => [styles.backButton, pressed && styles.pressed]}
      >
        <Glyph name="arrow" size={20} color={themeColors.primary} style={styles.backGlyph} />
        <Text style={styles.backLabel}>Nazad na korpu</Text>
      </Pressable>

      <View style={styles.identity}>
        <Text style={styles.eyebrow}>CHECKOUT</Text>
        <Text style={styles.title}>Kreiraj porudžbinu</Text>
        <Text style={styles.subtitle}>
          {items.length} {items.length === 1 ? 'stavka' : 'stavke'} · server ponovo proverava lager i cenu.
        </Text>
      </View>

      <View style={styles.section}>
        <SectionHeading step="1" title="Isporuka" copy="Podaci kupca i adresa dostave." styles={styles} />
        <View style={styles.sectionBody}>
          <TextField
            label="Ime i prezime *"
            value={shipping.fullName}
            onChangeText={(value) => setShipping((current) => ({ ...current, fullName: value }))}
            autoCapitalize="words"
          />
          <TextField
            label="Adresa *"
            value={shipping.address}
            onChangeText={(value) => setShipping((current) => ({ ...current, address: value }))}
          />
          <View style={styles.twoColumn}>
            <View style={styles.flexOne}>
              <TextField
                label="Grad *"
                value={shipping.city}
                onChangeText={(value) => setShipping((current) => ({ ...current, city: value }))}
              />
            </View>
            <View style={styles.flexOne}>
              <TextField
                label="Poštanski broj *"
                value={shipping.postalCode}
                onChangeText={(value) => setShipping((current) => ({ ...current, postalCode: value }))}
                keyboardType="number-pad"
              />
            </View>
          </View>
          <TextField
            label="Telefon *"
            value={shipping.phone}
            onChangeText={(value) => setShipping((current) => ({ ...current, phone: value }))}
            keyboardType="phone-pad"
          />
        </View>
      </View>

      <View style={styles.section}>
        <SectionHeading
          step="2"
          title="Plaćanje"
          copy="Izaberi način plaćanja koji server dozvoljava."
          styles={styles}
        />
        <View style={styles.optionGroup}>
          {options.data.payment_methods.map((method, index) => (
            <Pressable
              key={method.value}
              accessibilityRole="radio"
              accessibilityState={{ checked: paymentMethod === method.value }}
              onPress={() => setPaymentMethod(method.value)}
              style={({ pressed }) => [
                styles.option,
                index < options.data.payment_methods.length - 1 && styles.optionDivider,
                paymentMethod === method.value && styles.optionSelected,
                pressed && styles.optionPressed,
              ]}
            >
              <View style={[styles.radio, paymentMethod === method.value && styles.radioSelected]}>
                {paymentMethod === method.value ? <View style={styles.radioDot} /> : null}
              </View>
              <View style={styles.optionCopyWrap}>
                <Text style={styles.optionTitle}>{method.label}</Text>
                <Text style={styles.optionCopy}>
                  {method.requires_bank_account
                    ? 'Izaberi račun na koji će uplata biti izvršena.'
                    : method.requires_due_date
                      ? 'Plaćanje se prati kroz postojeća potraživanja do izabranog datuma dospeća.'
                      : 'Plaćanje prilikom preuzimanja.'}
                </Text>
              </View>
            </Pressable>
          ))}
        </View>

        {selectedPayment?.requires_due_date ? (
          <View style={styles.nestedSurface}>
            <Text style={styles.nestedTitle}>Datum dospeća</Text>
            <TextField
              label="Datum dospeća (YYYY-MM-DD) *"
              value={paymentDueAt}
              onChangeText={setPaymentDueAt}
              placeholder="2026-09-20"
              autoCapitalize="none"
            />
            <Text style={styles.help}>
              Dug se vodi kroz postojeći Receivables sistem i zatvara kada preostali saldo postane nula.
            </Text>
          </View>
        ) : null}

        {selectedPayment?.requires_bank_account ? (
          <View style={styles.nestedSurface}>
            <Text style={styles.nestedTitle}>Račun za uplatu</Text>
            {options.data.bank_accounts.length ? (
              <View style={styles.optionGroup}>
                {options.data.bank_accounts.map((account, index) => (
                  <Pressable
                    key={account.id}
                    accessibilityRole="radio"
                    accessibilityState={{ checked: bankAccountId === account.id }}
                    onPress={() => setBankAccountId(account.id)}
                    style={({ pressed }) => [
                      styles.option,
                      index < options.data.bank_accounts.length - 1 && styles.optionDivider,
                      bankAccountId === account.id && styles.optionSelected,
                      pressed && styles.optionPressed,
                    ]}
                  >
                    <View style={[styles.radio, bankAccountId === account.id && styles.radioSelected]}>
                      {bankAccountId === account.id ? <View style={styles.radioDot} /> : null}
                    </View>
                    <View style={styles.optionCopyWrap}>
                      <Text style={styles.optionTitle}>{account.label}</Text>
                      <Text style={styles.optionCopy}>
                        {account.recipient_name} · {account.account_number}
                      </Text>
                    </View>
                  </Pressable>
                ))}
              </View>
            ) : (
              <Text style={styles.warning}>Nema aktivnog računa za uplatu.</Text>
            )}
          </View>
        ) : null}
      </View>

      {options.data.suppliers.length ? (
        <View style={styles.section}>
          <SectionHeading
            step="3"
            title="Odgovorno lice"
            copy="Porudžbina se dodeljuje izabranom administratoru."
            styles={styles}
          />
          <View style={styles.optionGroup}>
            {options.data.suppliers.map((supplier, index) => (
              <Pressable
                key={supplier.id}
                accessibilityRole="radio"
                accessibilityState={{ checked: supplierUserId === supplier.id }}
                onPress={() => setSupplierUserId(supplier.id)}
                style={({ pressed }) => [
                  styles.option,
                  index < options.data.suppliers.length - 1 && styles.optionDivider,
                  supplierUserId === supplier.id && styles.optionSelected,
                  pressed && styles.optionPressed,
                ]}
              >
                <View style={[styles.radio, supplierUserId === supplier.id && styles.radioSelected]}>
                  {supplierUserId === supplier.id ? <View style={styles.radioDot} /> : null}
                </View>
                <View style={styles.optionCopyWrap}>
                  <Text style={styles.optionTitle}>{supplier.name}</Text>
                  <Text style={styles.optionCopy}>
                    {supplier.role ?? 'administrator'}
                    {supplier.phone ? ` · ${supplier.phone}` : ''}
                  </Text>
                </View>
              </Pressable>
            ))}
          </View>
        </View>
      ) : null}

      <View style={styles.section}>
        <SectionHeading
          step="4"
          title="Napomena"
          copy="Opciona poruka uz porudžbinu."
          styles={styles}
        />
        <View style={styles.sectionBody}>
          <TextField
            label="Napomena za porudžbinu"
            value={customerNote}
            onChangeText={setCustomerNote}
            multiline
            numberOfLines={4}
            maxLength={options.data.limits.customer_note_max_length}
            style={styles.noteInput}
            textAlignVertical="top"
          />
          <Text style={styles.counter}>
            {customerNote.length}/{options.data.limits.customer_note_max_length}
          </Text>
        </View>
      </View>

      <View style={styles.reviewSurface}>
        <View style={styles.reviewIcon}>
          <Glyph name="check" size={22} color={themeColors.primary} />
        </View>
        <View style={styles.flexOne}>
          <Text style={styles.reviewTitle}>Spremno za potvrdu</Text>
          <Text style={styles.reviewCopy}>
            Porudžbina sadrži {items.reduce((sum, item) => sum + item.quantity, 0)} komada. Isti Idempotency-Key se koristi pri bezbednom retry-u istog zahteva, pa timeout ne sme napraviti duplikat.
          </Text>
        </View>
      </View>

      {formError ? <Text style={styles.error}>{formError}</Text> : null}

      <Button
        onPress={submit}
        loading={mutation.isPending}
        disabled={
          selectedPayment?.requires_bank_account === true
          && options.data.bank_accounts.length === 0
        }
      >
        Potvrdi i kreiraj porudžbinu
      </Button>
    </Screen>
  );
}

function SectionHeading({
  step,
  title,
  copy,
  styles,
}: {
  step: string;
  title: string;
  copy: string;
  styles: ReturnType<typeof createStyles>;
}) {
  return (
    <View style={styles.sectionHeading}>
      <View style={styles.stepBadge}>
        <Text style={styles.stepBadgeText}>{step}</Text>
      </View>
      <View style={styles.flexOne}>
        <Text style={styles.sectionTitle}>{title}</Text>
        <Text style={styles.sectionCopy}>{copy}</Text>
      </View>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    backButton: {
      alignSelf: 'flex-start',
      minHeight: 40,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.xs,
    },
    backGlyph: { transform: [{ rotate: '180deg' }] },
    backLabel: { ...typography.label, color: theme.primary },
    pressed: { opacity: 0.68 },
    identity: { gap: spacing.xs },
    eyebrow: {
      ...typography.small,
      color: theme.primary,
      letterSpacing: 1.2,
      fontWeight: '800',
    },
    title: { ...typography.h1, color: theme.ink },
    subtitle: { ...typography.body, color: theme.muted },
    section: {
      gap: spacing.md,
      paddingTop: spacing.sm,
    },
    sectionHeading: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      gap: spacing.md,
    },
    stepBadge: {
      width: 34,
      height: 34,
      borderRadius: radii.md,
      backgroundColor: theme.primarySoft,
      alignItems: 'center',
      justifyContent: 'center',
    },
    stepBadgeText: {
      ...typography.label,
      color: theme.primaryDark,
      fontVariant: ['tabular-nums'],
    },
    sectionTitle: { ...typography.h3, color: theme.ink },
    sectionCopy: { ...typography.small, color: theme.muted, marginTop: 2 },
    sectionBody: {
      gap: spacing.md,
      padding: spacing.lg,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.xl,
      backgroundColor: theme.surface,
    },
    twoColumn: { flexDirection: 'row', gap: spacing.md },
    flexOne: { flex: 1, minWidth: 0 },
    optionGroup: {
      overflow: 'hidden',
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.xl,
      backgroundColor: theme.surface,
    },
    option: {
      minHeight: 72,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md,
      paddingHorizontal: spacing.lg,
      paddingVertical: spacing.md,
      backgroundColor: theme.surface,
    },
    optionDivider: {
      borderBottomWidth: StyleSheet.hairlineWidth,
      borderBottomColor: theme.line,
    },
    optionSelected: { backgroundColor: theme.primarySoft },
    optionPressed: { opacity: 0.82 },
    radio: {
      width: 20,
      height: 20,
      borderRadius: radii.pill,
      borderWidth: 2,
      borderColor: theme.outline,
      backgroundColor: theme.surface,
      alignItems: 'center',
      justifyContent: 'center',
    },
    radioSelected: { borderColor: theme.primary },
    radioDot: {
      width: 8,
      height: 8,
      borderRadius: radii.pill,
      backgroundColor: theme.primary,
    },
    optionCopyWrap: { flex: 1, minWidth: 0 },
    optionTitle: { ...typography.label, color: theme.ink },
    optionCopy: { ...typography.small, color: theme.muted, marginTop: 3 },
    nestedSurface: {
      gap: spacing.sm,
      padding: spacing.lg,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.xl,
      backgroundColor: theme.surfaceContainer,
    },
    nestedTitle: { ...typography.label, color: theme.ink },
    warning: { ...typography.body, color: theme.danger },
    help: { ...typography.small, color: theme.muted },
    noteInput: { minHeight: 116, paddingTop: spacing.md },
    counter: {
      ...typography.small,
      color: theme.muted,
      textAlign: 'right',
      fontVariant: ['tabular-nums'],
    },
    reviewSurface: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      gap: spacing.md,
      padding: spacing.lg,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.xl,
      backgroundColor: theme.surfaceContainer,
    },
    reviewIcon: {
      width: 42,
      height: 42,
      borderRadius: radii.md,
      backgroundColor: theme.primarySoft,
      alignItems: 'center',
      justifyContent: 'center',
    },
    reviewTitle: { ...typography.label, color: theme.ink },
    reviewCopy: { ...typography.small, color: theme.muted, marginTop: spacing.xs },
    error: {
      ...typography.body,
      color: theme.danger,
      textAlign: 'center',
      paddingHorizontal: spacing.md,
    },
  });
}
