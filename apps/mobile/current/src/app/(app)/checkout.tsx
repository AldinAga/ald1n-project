import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import * as Crypto from 'expo-crypto';
import { router } from 'expo-router';
import { useEffect, useRef, useState } from 'react';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { useCart } from '@/features/cart/cart-provider';
import { api } from '@/lib/api/endpoints';
import { ApiError } from '@/lib/api/client';
import type { CreateOrderInput, PaymentMethod } from '@/types/api';

export default function CheckoutScreen() {
  const client = useQueryClient();
  const { bootstrap, hasFeature } = useAuth();
  const { items, clearCart } = useCart();
  const allowed = hasFeature('order_create');
  const options = useQuery({ queryKey: ['order-options'], queryFn: api.orders.options, enabled: allowed && items.length > 0, staleTime: 5 * 60_000 });
  const initialized = useRef(false);
  const [shipping, setShipping] = useState({ fullName: '', address: '', city: '', postalCode: '', phone: '' });
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | ''>('');
  const [bankAccountId, setBankAccountId] = useState<number | null>(null);
  const [supplierUserId, setSupplierUserId] = useState<number | null>(null);
  const [customerNote, setCustomerNote] = useState('');
  const [formError, setFormError] = useState<string | null>(null);
  const [lastSubmission, setLastSubmission] = useState<{ key: string; payloadJson: string } | null>(null);

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
      phone: defaults.phone || user?.phone || ''
    });
    const firstPayment = options.data.payment_methods[0]?.value;
    if (firstPayment) setPaymentMethod(firstPayment);
    setSupplierUserId(options.data.suppliers[0]?.id ?? null);
  }, [bootstrap?.user, options.data]);

  useEffect(() => {
    if (paymentMethod === 'bank_transfer' && bankAccountId === null && options.data?.bank_accounts[0]) {
      setBankAccountId(options.data.bank_accounts[0].id);
    }
    if (paymentMethod !== 'bank_transfer') setBankAccountId(null);
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
      Alert.alert('Porudžbina je kreirana', `${order.order_number} je uspešno poslata.`);
    },
    onError: (error) => {
      const message = error instanceof ApiError ? error.firstFieldError() ?? error.message : 'Porudžbina nije kreirana.';
      setFormError(message);
    }
  });

  if (!allowed) return <UnavailableState title="Kreiranje porudžbine nije dostupno" />;
  if (!items.length) return <UnavailableState title="Korpa je prazna" message="Dodaj proizvod u korpu pre kreiranja porudžbine." />;
  if (options.isLoading) return <LoadingState label="Priprema porudžbine…" />;
  if (options.isError || !options.data) return <ErrorState error={options.error} onRetry={() => void options.refetch()} />;

  const selectedPayment = options.data.payment_methods.find((method) => method.value === paymentMethod);
  const submit = () => {
    setFormError(null);
    const required = [shipping.fullName, shipping.address, shipping.city, shipping.postalCode, shipping.phone];
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
    if (items.length > options.data.limits.max_items) {
      setFormError(`Porudžbina može imati najviše ${options.data.limits.max_items} različitih stavki.`);
      return;
    }
    if (items.some((item) => item.quantity > options.data.limits.max_quantity_per_item)) {
      setFormError(`Maksimalna količina po stavci je ${options.data.limits.max_quantity_per_item}.`);
      return;
    }
    if (customerNote.length > options.data.limits.customer_note_max_length) {
      setFormError(`Napomena može imati najviše ${options.data.limits.customer_note_max_length} karaktera.`);
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
      items: items.map((item) => ({
        product_id: item.productId,
        product_variant_id: item.variantId,
        quantity: item.quantity
      }))
    });
  };

  return (
    <Screen keyboardShouldPersistTaps="handled">
      <Pressable onPress={() => router.back()}><Text style={styles.back}>‹ Nazad na korpu</Text></Pressable>
      <View>
        <Text style={styles.eyebrow}>CHECKOUT</Text>
        <Text style={styles.title}>Kreiraj porudžbinu</Text>
        <Text style={styles.subtitle}>{items.length} {items.length === 1 ? 'stavka' : 'stavke'} · server ponovo proverava lager i cenu.</Text>
      </View>

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Isporuka</Text>
        <TextField label="Ime i prezime *" value={shipping.fullName} onChangeText={(value) => setShipping((current) => ({ ...current, fullName: value }))} autoCapitalize="words" />
        <TextField label="Adresa *" value={shipping.address} onChangeText={(value) => setShipping((current) => ({ ...current, address: value }))} />
        <TextField label="Grad *" value={shipping.city} onChangeText={(value) => setShipping((current) => ({ ...current, city: value }))} />
        <TextField label="Poštanski broj *" value={shipping.postalCode} onChangeText={(value) => setShipping((current) => ({ ...current, postalCode: value }))} keyboardType="number-pad" />
        <TextField label="Telefon *" value={shipping.phone} onChangeText={(value) => setShipping((current) => ({ ...current, phone: value }))} keyboardType="phone-pad" />
      </Card>

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Način plaćanja</Text>
        {options.data.payment_methods.map((method) => (
          <Pressable key={method.value} onPress={() => setPaymentMethod(method.value)} style={[styles.option, paymentMethod === method.value && styles.optionSelected]}>
            <View style={[styles.radio, paymentMethod === method.value && styles.radioSelected]} />
            <View style={{ flex: 1 }}><Text style={styles.optionTitle}>{method.label}</Text><Text style={styles.optionCopy}>{method.requires_bank_account ? 'Izaberi račun na koji će uplata biti izvršena.' : 'Plaćanje prilikom preuzimanja.'}</Text></View>
          </Pressable>
        ))}

        {selectedPayment?.requires_bank_account ? (
          <View style={styles.nested}>
            <Text style={styles.nestedTitle}>Račun za uplatu</Text>
            {options.data.bank_accounts.length ? options.data.bank_accounts.map((account) => (
              <Pressable key={account.id} onPress={() => setBankAccountId(account.id)} style={[styles.option, bankAccountId === account.id && styles.optionSelected]}>
                <View style={[styles.radio, bankAccountId === account.id && styles.radioSelected]} />
                <View style={{ flex: 1 }}><Text style={styles.optionTitle}>{account.label}</Text><Text style={styles.optionCopy}>{account.recipient_name} · {account.account_number}</Text></View>
              </Pressable>
            )) : <Text style={styles.warning}>Nema aktivnog računa za uplatu.</Text>}
          </View>
        ) : null}
      </Card>

      {options.data.suppliers.length ? (
        <Card style={styles.section}>
          <Text style={styles.sectionTitle}>Odgovorno lice</Text>
          <Text style={styles.help}>Ako ne izabereš drugo lice, koristi se prvi dostupni administrator.</Text>
          {options.data.suppliers.map((supplier) => (
            <Pressable key={supplier.id} onPress={() => setSupplierUserId(supplier.id)} style={[styles.option, supplierUserId === supplier.id && styles.optionSelected]}>
              <View style={[styles.radio, supplierUserId === supplier.id && styles.radioSelected]} />
              <View style={{ flex: 1 }}><Text style={styles.optionTitle}>{supplier.name}</Text><Text style={styles.optionCopy}>{supplier.role ?? 'administrator'}{supplier.phone ? ` · ${supplier.phone}` : ''}</Text></View>
            </Pressable>
          ))}
        </Card>
      ) : null}

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Napomena</Text>
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
        <Text style={styles.counter}>{customerNote.length}/{options.data.limits.customer_note_max_length}</Text>
      </Card>

      <Card muted>
        <Text style={styles.reviewTitle}>Pre potvrde</Text>
        <Text style={styles.reviewCopy}>Porudžbina sadrži {items.reduce((sum, item) => sum + item.quantity, 0)} komada. Isti Idempotency-Key se koristi pri bezbednom retry-u istog zahteva, pa timeout ne sme napraviti duplikat.</Text>
      </Card>

      {formError ? <Text style={styles.error}>{formError}</Text> : null}
      <Button onPress={submit} loading={mutation.isPending} disabled={selectedPayment?.requires_bank_account === true && options.data.bank_accounts.length === 0}>Potvrdi i kreiraj porudžbinu</Button>
    </Screen>
  );
}

const styles = StyleSheet.create({
  back: { ...typography.label, color: colors.primary, paddingVertical: spacing.sm },
  eyebrow: { ...typography.small, color: colors.primary, letterSpacing: 1.2, fontWeight: '800' },
  title: { ...typography.h1, color: colors.ink, marginTop: spacing.xs },
  subtitle: { ...typography.body, color: colors.muted, marginTop: spacing.xs },
  section: { gap: spacing.md },
  sectionTitle: { ...typography.h3, color: colors.ink },
  option: { minHeight: 66, flexDirection: 'row', alignItems: 'center', gap: spacing.md, borderWidth: 1, borderColor: colors.line, borderRadius: radii.lg, padding: spacing.md, backgroundColor: colors.surface },
  optionSelected: { borderColor: colors.primary, backgroundColor: colors.primarySoft },
  radio: { width: 18, height: 18, borderRadius: 9, borderWidth: 2, borderColor: colors.muted, backgroundColor: colors.surface },
  radioSelected: { borderColor: colors.primary, backgroundColor: colors.primary },
  optionTitle: { ...typography.label, color: colors.ink },
  optionCopy: { ...typography.small, color: colors.muted, marginTop: 3 },
  nested: { gap: spacing.sm, paddingTop: spacing.sm },
  nestedTitle: { ...typography.label, color: colors.ink },
  warning: { ...typography.body, color: colors.danger },
  help: { ...typography.small, color: colors.muted, marginTop: -spacing.xs },
  noteInput: { minHeight: 116, paddingTop: spacing.md },
  counter: { ...typography.small, color: colors.muted, textAlign: 'right' },
  reviewTitle: { ...typography.label, color: colors.ink },
  reviewCopy: { ...typography.small, color: colors.muted, marginTop: spacing.xs },
  error: { ...typography.body, color: colors.danger, textAlign: 'center', paddingHorizontal: spacing.md }
});
