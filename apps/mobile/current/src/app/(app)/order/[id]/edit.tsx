import { useEffect, useMemo, useRef, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import * as Crypto from 'expo-crypto';
import { router, useLocalSearchParams } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Glyph } from '@/components/ui/glyph';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';
import type { Order, Product, UpdateOrderInput } from '@/types/api';

type EditableItem = {
  product_id: number;
  name: string;
  sku: string;
  quantity: number;
};

function initialItems(order: Order): EditableItem[] {
  return (order.items ?? []).map((item) => ({
    product_id: item.product_id,
    name: item.name,
    sku: item.sku,
    quantity: item.quantity,
  }));
}

export default function OrderEditScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { hasFeature } = useAuth();
  const { id } = useLocalSearchParams<{ id: string }>();
  const orderId = Number(id);
  const validId = Number.isInteger(orderId) && orderId > 0;
  const allowed = hasFeature('orders');
  const initializedToken = useRef<string | null>(null);

  const orderQuery = useQuery({
    queryKey: ['order', orderId],
    queryFn: () => api.orders.detail(orderId),
    enabled: allowed && validId,
  });

  const [items, setItems] = useState<EditableItem[]>([]);
  const [shipping, setShipping] = useState({ fullName: '', address: '', city: '', postalCode: '', phone: '' });
  const [note, setNote] = useState('');
  const [search, setSearch] = useState('');
  const [formError, setFormError] = useState<string | null>(null);
  const [staleConflict, setStaleConflict] = useState(false);
  const lastSubmission = useRef<{ key: string; payloadJson: string } | null>(null);

  const order = orderQuery.data;
  useEffect(() => {
    if (!order || initializedToken.current === order.edit_token) return;
    initializedToken.current = order.edit_token;
    setItems(initialItems(order));
    setShipping({
      fullName: order.shipping.full_name ?? '',
      address: order.shipping.address ?? '',
      city: order.shipping.city ?? '',
      postalCode: order.shipping.postal_code ?? '',
      phone: order.shipping.phone ?? '',
    });
    setNote(order.customer_note ?? '');
    setStaleConflict(false);
    setFormError(null);
    lastSubmission.current = null;
  }, [order]);

  const searchQuery = useQuery({
    queryKey: ['order-amendment-catalog-search', search.trim()],
    queryFn: async () => {
      const response = await api.catalog.products({ q: search.trim(), stock: 'available', page: 1, per_page: 8 });
      return response.data;
    },
    enabled: allowed && search.trim().length >= 2,
    staleTime: 20_000,
  });

  const mutation = useMutation({
    mutationFn: async (payload: UpdateOrderInput) => {
      const payloadJson = JSON.stringify(payload);
      const key = lastSubmission.current?.payloadJson === payloadJson ? lastSubmission.current.key : Crypto.randomUUID();
      lastSubmission.current = { key, payloadJson };
      return api.orders.update(orderId, payload, key);
    },
    onSuccess: async (updated) => {
      lastSubmission.current = null;
      await client.invalidateQueries({ queryKey: ['orders'] });
      await client.invalidateQueries({ queryKey: ['order', orderId] });
      await client.invalidateQueries({ queryKey: ['order-post-create', orderId] });
      feedback.notify({
        tone: 'success',
        title: 'Izmene su sačuvane',
        message: 'Administrator će proveriti poslednju verziju porudžbine pre slanja.',
      });
      router.replace({ pathname: '/order/[id]', params: { id: String(updated.id) } });
    },
    onError: (error) => {
      if (error instanceof ApiError && error.status === 409) {
        setStaleConflict(true);
        setFormError('Porudžbina je promenjena na drugom mestu. Osveži podatke i proveri poslednju verziju pre čuvanja.');
        return;
      }
      setFormError(error instanceof ApiError ? error.firstFieldError() ?? error.message : 'Izmene trenutno nije moguće sačuvati.');
    },
  });

  const canSave = useMemo(() => items.length > 0 && shipping.fullName.trim() !== '' && shipping.address.trim() !== '' && shipping.city.trim() !== '' && shipping.postalCode.trim() !== '' && shipping.phone.trim() !== '', [items, shipping]);

  const changeQuantity = (productId: number, delta: number) => {
    setItems((current) => current.map((item) => item.product_id === productId
      ? { ...item, quantity: Math.max(1, Math.min(1000, item.quantity + delta)) }
      : item));
  };

  const removeItem = (productId: number) => {
    setItems((current) => current.length <= 1 ? current : current.filter((item) => item.product_id !== productId));
  };

  const addProduct = (product: Product) => {
    setItems((current) => {
      const existing = current.find((item) => item.product_id === product.id);
      if (existing) {
        return current.map((item) => item.product_id === product.id
          ? { ...item, quantity: Math.min(1000, item.quantity + 1) }
          : item);
      }
      return [...current, { product_id: product.id, name: product.name, sku: product.sku, quantity: 1 }];
    });
    setSearch('');
  };

  const refreshAfterConflict = async () => {
    initializedToken.current = null;
    lastSubmission.current = null;
    await orderQuery.refetch();
  };

  const submit = () => {
    if (!order) return;
    if (!order.capabilities.can_amend) {
      setFormError('Porudžbina više ne može da se menja jer je poslata, završena ili otkazana.');
      return;
    }
    if (!canSave) {
      setFormError('Popuni sva obavezna polja i ostavi najmanje jednu stavku.');
      return;
    }
    setFormError(null);
    setStaleConflict(false);
    mutation.mutate({
      expected_edit_token: order.edit_token,
      shipping_full_name: shipping.fullName.trim(),
      shipping_address: shipping.address.trim(),
      shipping_city: shipping.city.trim(),
      shipping_postal_code: shipping.postalCode.trim(),
      shipping_phone: shipping.phone.trim(),
      customer_note: note.trim() || null,
      items: items.map((item) => ({ product_id: item.product_id, quantity: item.quantity })),
    });
  };

  if (!allowed) return <UnavailableState title="Porudžbina nije dostupna" />;
  if (!validId) return <ErrorState error={new Error('Neispravan identifikator porudžbine.')} />;
  if (orderQuery.isLoading) return <LoadingState label="Učitavanje porudžbine…" />;
  if (orderQuery.isError || !order) return <ErrorState error={orderQuery.error} onRetry={() => void orderQuery.refetch()} />;
  if (!order.capabilities.can_amend && !staleConflict) {
    return <UnavailableState title="Porudžbina je zaključana" message="Izmene nisu dostupne nakon slanja, završetka ili otkazivanja porudžbine." />;
  }

  return (
    <Screen keyboardShouldPersistTaps="handled">
      <Pressable accessibilityRole="button" accessibilityLabel="Nazad na porudžbinu" onPress={() => router.back()} style={({ pressed }) => [styles.backButton, pressed && styles.pressed]}>
        <Glyph name="arrow" size={18} color={themeColors.primary} style={styles.backGlyph} />
        <Text style={styles.backText}>Porudžbina</Text>
      </Pressable>

      <View style={styles.heading}>
        <Text style={styles.eyebrow}>PORUDŽBINA {order.order_number}</Text>
        <Text style={styles.title}>Uredi porudžbinu</Text>
        <Text style={styles.help}>Artikle, količine, adresu i napomenu možeš menjati sve dok pošiljka ne bude poslata.</Text>
      </View>

      <View style={styles.section}>
        <Text style={styles.step}>1 · STAVKE</Text>
        {items.map((item) => (
          <View key={item.product_id} style={styles.itemRow}>
            <View style={styles.flexOne}>
              <Text style={styles.itemName}>{item.name}</Text>
              <Text style={styles.muted}>{item.sku}</Text>
            </View>
            <View style={styles.quantityRow}>
              <Button variant="ghost" onPress={() => changeQuantity(item.product_id, -1)}>−</Button>
              <Text style={styles.quantity}>{item.quantity}</Text>
              <Button variant="ghost" onPress={() => changeQuantity(item.product_id, 1)}>+</Button>
            </View>
            <Button variant="danger" disabled={items.length <= 1} onPress={() => removeItem(item.product_id)}>Ukloni</Button>
          </View>
        ))}

        <TextField label="Dodaj artikal" value={search} onChangeText={setSearch} placeholder="Pretraži naziv ili SKU" autoCapitalize="none" />
        {search.trim().length >= 2 && searchQuery.isLoading ? <Text style={styles.muted}>Pretraga…</Text> : null}
        {(searchQuery.data ?? []).map((product) => (
          <Pressable key={product.id} accessibilityRole="button" onPress={() => addProduct(product)} style={({ pressed }) => [styles.searchResult, pressed && styles.pressed]}>
            <View style={styles.flexOne}>
              <Text style={styles.itemName}>{product.name}</Text>
              <Text style={styles.muted}>{product.sku} · lager {product.stock_quantity}</Text>
            </View>
            <Text style={styles.addLabel}>Dodaj</Text>
          </Pressable>
        ))}
      </View>

      <View style={styles.section}>
        <Text style={styles.step}>2 · DOSTAVA</Text>
        <TextField label="Ime i prezime *" value={shipping.fullName} onChangeText={(value) => setShipping((current) => ({ ...current, fullName: value }))} />
        <TextField label="Telefon *" value={shipping.phone} keyboardType="phone-pad" onChangeText={(value) => setShipping((current) => ({ ...current, phone: value }))} />
        <TextField label="Adresa *" value={shipping.address} onChangeText={(value) => setShipping((current) => ({ ...current, address: value }))} />
        <TextField label="Grad *" value={shipping.city} onChangeText={(value) => setShipping((current) => ({ ...current, city: value }))} />
        <TextField label="Poštanski broj *" value={shipping.postalCode} onChangeText={(value) => setShipping((current) => ({ ...current, postalCode: value }))} />
      </View>

      <View style={styles.section}>
        <Text style={styles.step}>3 · NAPOMENA</Text>
        <TextField label="Napomena za porudžbinu" value={note} onChangeText={setNote} multiline numberOfLines={5} maxLength={5000} textAlignVertical="top" />
      </View>

      {formError ? <Text style={styles.error}>{formError}</Text> : null}
      {staleConflict ? <Button variant="secondary" onPress={() => void refreshAfterConflict()}>Osveži poslednju verziju</Button> : null}
      <Button loading={mutation.isPending} disabled={!canSave || staleConflict} onPress={submit}>Sačuvaj izmene porudžbine</Button>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    backButton: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, alignSelf: 'flex-start' },
    backGlyph: { transform: [{ rotate: '180deg' }] },
    backText: { ...typography.label, color: theme.primary },
    pressed: { opacity: 0.72 },
    heading: { gap: spacing.sm },
    eyebrow: { ...typography.small, color: theme.primary, fontWeight: '800' },
    title: { ...typography.h1, color: theme.ink },
    help: { ...typography.body, color: theme.muted },
    section: { gap: spacing.md, padding: spacing.lg, borderWidth: 1, borderColor: theme.line, borderRadius: radii.xl, backgroundColor: theme.surface },
    step: { ...typography.label, color: theme.primary },
    itemRow: { gap: spacing.sm, paddingVertical: spacing.sm, borderBottomWidth: 1, borderBottomColor: theme.line },
    itemName: { ...typography.body, color: theme.ink, fontWeight: '700' },
    muted: { ...typography.small, color: theme.muted },
    flexOne: { flex: 1 },
    quantityRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
    quantity: { ...typography.h3, minWidth: 34, textAlign: 'center', color: theme.ink },
    searchResult: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, padding: spacing.md, borderRadius: radii.lg, backgroundColor: theme.surfaceContainer },
    addLabel: { ...typography.label, color: theme.primary },
    error: { ...typography.body, color: theme.danger, fontWeight: '700' },
  });
}
