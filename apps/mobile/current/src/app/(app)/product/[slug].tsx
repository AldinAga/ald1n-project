import { useQuery } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Glyph } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { useCart } from '@/features/cart/cart-provider';
import { api } from '@/lib/api/endpoints';
import { formatMoney } from '@/lib/formatters';

export default function ProductDetailScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const { hasFeature } = useAuth();
  const { addItem, itemCount } = useCart();
  const allowed = hasFeature('catalog');
  const canCreateOrder = hasFeature('order_create');
  const query = useQuery({ queryKey: ['product', slug], queryFn: () => api.catalog.product(slug), enabled: allowed && Boolean(slug) });
  const [selectedVariantId, setSelectedVariantId] = useState<number | null>(null);
  const [quantity, setQuantity] = useState(1);
  const product = query.data;

  useEffect(() => {
    if (!product?.variants_enabled) {
      setSelectedVariantId(null);
      return;
    }
    const variants = product.variants ?? [];
    if (selectedVariantId !== null && variants.some((variant) => variant.id === selectedVariantId)) return;
    const preferred = variants.find((variant) => variant.default && variant.stock_quantity > 0)
      ?? variants.find((variant) => variant.stock_quantity > 0)
      ?? variants[0];
    setSelectedVariantId(preferred?.id ?? null);
  }, [product, selectedVariantId]);

  useEffect(() => setQuantity(1), [selectedVariantId]);

  if (!allowed) return <UnavailableState title="Proizvod nije dostupan" />;
  if (query.isLoading) return <LoadingState label="Učitavanje proizvoda…" />;
  if (query.isError || !product) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const selectedVariant = product.variants_enabled
    ? product.variants?.find((variant) => variant.id === selectedVariantId) ?? null
    : null;
  const stock = selectedVariant?.stock_quantity ?? product.stock_quantity;
  const price = selectedVariant?.price ?? product.price ?? null;
  const selectedSku = selectedVariant?.sku ?? product.sku;
  const imageUrl = selectedVariant?.images.find((image) => image.primary)?.url
    ?? selectedVariant?.images[0]?.url
    ?? product.primary_image_url;
  const requiresVariant = product.variants_enabled;
  const canAdd = canCreateOrder && stock > 0 && (!requiresVariant || selectedVariant !== null);

  const addToCart = () => {
    if (!canAdd) return;
    addItem({
      productId: product.id,
      productSlug: product.slug,
      productName: product.name,
      productSku: product.sku,
      variantId: selectedVariant?.id ?? null,
      variantName: selectedVariant?.name ?? null,
      sku: selectedSku,
      price,
      quantity,
      maxQuantity: stock,
      imageUrl
    });
    Alert.alert('Dodato u korpu', `${product.name}${selectedVariant ? ` · ${selectedVariant.name}` : ''} je dodat u korpu.`, [
      { text: 'Nastavi kupovinu', style: 'cancel' },
      { text: 'Otvori korpu', onPress: () => router.push('/cart') }
    ]);
  };

  return (
    <Screen>
      <View style={styles.topRow}>
        <Pressable onPress={() => router.back()} style={styles.back}><Text style={styles.backText}>‹ Nazad na katalog</Text></Pressable>
        {canCreateOrder ? <Pressable onPress={() => router.push('/cart')} style={styles.cartLink}><Glyph name="cart" size={18} color={colors.primary} /><Text style={styles.cartText}>Korpa{itemCount ? ` (${itemCount})` : ''}</Text></Pressable> : null}
      </View>
      <View style={styles.imageWrap}>{imageUrl ? <Image source={{ uri: imageUrl }} style={styles.image} resizeMode="contain" /> : <Glyph name="box" size={46} color={colors.primary} />}</View>
      <View style={styles.tags}><Pill tone={stock > 0 ? 'success' : 'danger'}>{stock > 0 ? `${stock} na stanju` : 'Nema na stanju'}</Pill>{product.brand ? <Pill>{product.brand.name}</Pill> : null}</View>
      <Text style={styles.title}>{product.name}</Text>
      <Text style={styles.sku}>{selectedSku}{product.model ? ` · ${product.model}` : ''}</Text>
      <Card style={styles.priceCard}><View><Text style={styles.label}>Prodajna cena</Text><Text style={styles.price}>{price ? formatMoney(price.amount, price.currency) : 'Nije dostupna za ovu dozvolu'}</Text></View><View style={styles.priceIcon}><Glyph name="catalog" color={colors.primary} size={24} /></View></Card>
      {product.description ? <Card><Text style={styles.sectionTitle}>Opis</Text><Text style={styles.description}>{product.description}</Text></Card> : null}
      {product.specifications?.length ? <Card><Text style={styles.sectionTitle}>Specifikacije</Text>{product.specifications.map((spec, index) => <View key={`${spec.slug}-${index}`} style={styles.spec}><Text style={styles.specLabel}>{spec.field}</Text><Text style={styles.specValue}>{String(spec.value ?? '—')}{spec.unit ? ` ${spec.unit}` : ''}</Text></View>)}</Card> : null}
      {product.variants_enabled && product.variants?.length ? <Card><Text style={styles.sectionTitle}>Izaberi konfiguraciju</Text>{product.variants.map((variant) => {
        const active = selectedVariantId === variant.id;
        return <Pressable key={variant.id} onPress={() => setSelectedVariantId(variant.id)} style={[styles.variant, active && styles.variantSelected]}><View style={[styles.radio, active && styles.radioSelected]} /><View style={{ flex: 1 }}><Text style={styles.variantName}>{variant.name}</Text><Text style={styles.variantMeta}>{variant.sku} · {variant.stock_quantity} na stanju</Text></View>{variant.price ? <Text style={styles.variantPrice}>{formatMoney(variant.price.amount, variant.price.currency)}</Text> : null}</Pressable>;
      })}</Card> : null}

      {canCreateOrder ? (
        <Card style={styles.buyCard}>
          <View style={styles.buyHead}><View><Text style={styles.sectionTitle}>Količina</Text><Text style={styles.buyCopy}>{stock > 0 ? `Maksimalno ${stock} kom.` : 'Proizvod trenutno nije raspoloživ.'}</Text></View>
            <View style={styles.stepper}>
              <Pressable disabled={quantity <= 1} onPress={() => setQuantity((value) => Math.max(1, value - 1))} style={[styles.stepButton, quantity <= 1 && styles.disabled]}><Text style={styles.stepText}>−</Text></Pressable>
              <Text style={styles.quantity}>{quantity}</Text>
              <Pressable disabled={quantity >= stock} onPress={() => setQuantity((value) => Math.min(stock, value + 1))} style={[styles.stepButton, quantity >= stock && styles.disabled]}><Text style={styles.stepText}>+</Text></Pressable>
            </View>
          </View>
          <Button onPress={addToCart} disabled={!canAdd}>Dodaj u korpu{canAdd ? ` · ${quantity} kom.` : ''}</Button>
        </Card>
      ) : <Button disabled>Kreiranje porudžbine nije dostupno za ovaj nalog</Button>}
    </Screen>
  );
}

const styles = StyleSheet.create({
  topRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
  back: { alignSelf: 'flex-start', paddingVertical: spacing.sm },
  backText: { ...typography.label, color: colors.primary },
  cartLink: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingVertical: spacing.sm },
  cartText: { ...typography.label, color: colors.primary },
  imageWrap: { height: 310, borderRadius: 28, backgroundColor: colors.surfaceMuted, borderWidth: 1, borderColor: colors.line, alignItems: 'center', justifyContent: 'center', overflow: 'hidden' },
  image: { width: '100%', height: '100%' },
  tags: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  title: { ...typography.h1, color: colors.ink },
  sku: { ...typography.body, color: colors.muted },
  priceCard: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  label: { ...typography.small, color: colors.muted },
  price: { ...typography.h2, color: colors.primaryDark, marginTop: spacing.xs },
  priceIcon: { width: 50, height: 50, borderRadius: radii.lg, backgroundColor: colors.primarySoft, alignItems: 'center', justifyContent: 'center' },
  sectionTitle: { ...typography.h3, color: colors.ink, marginBottom: spacing.md },
  description: { ...typography.body, color: colors.muted },
  spec: { minHeight: 46, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: spacing.md, borderTopWidth: 1, borderTopColor: colors.line },
  specLabel: { ...typography.small, color: colors.muted, flex: 1 },
  specValue: { ...typography.label, color: colors.ink, textAlign: 'right', flex: 1 },
  variant: { minHeight: 70, flexDirection: 'row', alignItems: 'center', gap: spacing.md, borderTopWidth: 1, borderTopColor: colors.line, paddingHorizontal: spacing.sm },
  variantSelected: { backgroundColor: colors.primarySoft, borderRadius: radii.md, borderTopColor: colors.primarySoft },
  radio: { width: 18, height: 18, borderRadius: 9, borderWidth: 2, borderColor: colors.muted, backgroundColor: colors.surface },
  radioSelected: { borderColor: colors.primary, backgroundColor: colors.primary },
  variantName: { ...typography.label, color: colors.ink },
  variantMeta: { ...typography.small, color: colors.muted, marginTop: 3 },
  variantPrice: { ...typography.label, color: colors.primaryDark },
  buyCard: { gap: spacing.md },
  buyHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
  buyCopy: { ...typography.small, color: colors.muted, marginTop: -spacing.sm },
  stepper: { flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: colors.line, borderRadius: radii.md, overflow: 'hidden' },
  stepButton: { width: 42, height: 42, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.surfaceMuted },
  stepText: { fontSize: 20, lineHeight: 23, color: colors.primaryDark, fontWeight: '800' },
  quantity: { minWidth: 42, textAlign: 'center', ...typography.label, color: colors.ink },
  disabled: { opacity: 0.35 }
});
