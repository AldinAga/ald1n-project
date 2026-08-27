import { useQuery } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import * as Clipboard from 'expo-clipboard';
import { useState } from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { Glyph } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';
import { useMoneyPresentation } from '@/features/preferences/money-presentation';
import { useAuth } from '@/features/auth/auth-provider';
import { useCart } from '@/features/cart/cart-provider';
import { scheduleCatalogProductEditHandoff } from '@/features/catalog/catalog-product-edit-handoff';
import { api } from '@/lib/api/endpoints';

export default function ProductDetailScreen() {
  const productAdminAuth = useAuth();
  const isSuperAdminProductView = productAdminAuth.bootstrap?.user.role?.slug === 'superadmin';
  const canManageProductFromDetail = productAdminAuth.can('catalog.manage_products');
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const { formatPrimaryMoney } = useMoneyPresentation();
  const feedback = useAppFeedback();

  const { slug } = useLocalSearchParams<{ slug: string }>();
  const { hasFeature } = useAuth();
  const { addItem, itemCount } = useCart();
  const allowed = hasFeature('catalog');
  const canCreateOrder = hasFeature('order_create');
  const query = useQuery({ queryKey: ['product', slug], queryFn: () => api.catalog.product(slug), enabled: allowed && Boolean(slug) });
  const [quantity, setQuantity] = useState(1);
  const product = query.data;

  if (!allowed) return <UnavailableState title="Proizvod nije dostupan" />;
  if (query.isLoading) return <LoadingState label="Učitavanje proizvoda…" />;
  if (query.isError || !product) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const stock = product.stock_quantity;
  const price = product.price ?? null;
  const selectedSku = product.sku;
  const imageUrl = product.primary_image_url;
  const canAdd = canCreateOrder && stock > 0;

  const copyDescription = async () => {
    if (!product.description || product.description.trim() === '') return;

    try {
      await Clipboard.setStringAsync(product.description);
      feedback.notify({
        tone: 'success',
        title: 'Opis kopiran',
        message: 'Opis artikla je kopiran.',
      });
    } catch (error) {
      feedback.notify({
        tone: 'danger',
        title: 'Kopiranje nije uspelo',
        message: error instanceof Error ? error.message : 'Pokušaj ponovo.',
      });
    }
  };
  const addToCart = () => {
    if (!canAdd) return;
    addItem({
      productId: product.id,
      productSlug: product.slug,
      productName: product.name,
      productSku: product.sku,
      sku: selectedSku,
      price,
      quantity,
      maxQuantity: stock,
      imageUrl
    });
    void (async () => {
      const openCart = await feedback.confirm({
        tone: 'success',
        title: 'Dodato u korpu',
        message: `${product.name} je dodat u korpu.`,
        confirmLabel: 'Otvori korpu',
        cancelLabel: 'Nastavi kupovinu'
      });

      if (openCart) {
        router.push('/cart');
      }
    })();
  };

  return (
    <Screen>
      <View style={styles.topRow}>
        <Pressable onPress={() => router.back()} style={styles.back}><Text style={styles.backText}>‹ Nazad na katalog</Text></Pressable>
        {canCreateOrder ? <Pressable onPress={() => router.push('/cart')} style={styles.cartLink}><Glyph name="cart" size={18} color={themeColors.primary} /><Text style={styles.cartText}>Korpa{itemCount ? ` (${itemCount})` : ''}</Text></Pressable> : null}
      </View>
      <View style={styles.imageWrap}>{imageUrl ? <Image source={{ uri: imageUrl }} style={styles.image} resizeMode="contain" /> : <Glyph name="box" size={46} color={themeColors.primary} />}</View>
      <View style={styles.tags}><Pill tone={stock > 0 ? 'success' : 'danger'}>{stock > 0 ? `${stock} na stanju` : 'Nema na stanju'}</Pill>{product.brand ? <Pill>{product.brand.name}</Pill> : null}</View>
      <Text style={styles.title}>{product.name}</Text>
      <Text style={styles.sku}>{selectedSku}{product.model ? ` · ${product.model}` : ''}</Text>
      <Card style={styles.priceCard}><View><Text style={styles.label}>Prodajna cena</Text><Text style={styles.price}>{price ? formatPrimaryMoney(price.amount, price.currency) : 'Nije dostupna za ovu dozvolu'}</Text></View><View style={styles.priceIcon}><Glyph name="catalog" color={themeColors.primary} size={24} /></View></Card>
      {product.description ? (
        <Card>
          <View style={styles.descriptionHeader}>
            <Text style={styles.descriptionTitle}>Opis artikla</Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Kopiraj opis artikla"
              onPress={() => void copyDescription()}
              style={({ pressed }) => [styles.copyDescriptionButton, pressed && styles.copyDescriptionButtonPressed]}
            >
              <Text style={styles.copyDescriptionLabel}>Kopiraj opis</Text>
            </Pressable>
          </View>
          <Text style={styles.description}>{product.description}</Text>
        </Card>
      ) : null}
      {product.specifications?.length ? <Card><Text style={styles.sectionTitle}>Specifikacije</Text>{product.specifications.map((spec, index) => <View key={`${spec.slug}-${index}`} style={styles.spec}><Text style={styles.specLabel}>{spec.field}</Text><Text style={styles.specValue}>{String(spec.value ?? '—')}{spec.unit ? ` ${spec.unit}` : ''}</Text></View>)}</Card> : null}

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
          {/* MOBILE_V0_9_PRODUCT_DETAIL_COMMISSION_DIRECT_SALE_BATCH5A */}
      <Card style={styles.commissionCardV09}>
        <Text style={styles.label}>Provizija</Text>
        <Text style={styles.commissionValueV09}>{formatPrimaryMoney(product.commission_eur, 'EUR')}</Text>
        <Text style={styles.description}>Server obračun provizije za jedan komad artikla.</Text>
      </Card>

      <View style={styles.productAdminActionsV09}>
        {isSuperAdminProductView ? (
          <Pressable
            onPress={() => router.push({ pathname: '/admin/catalog/[id]/direct-sale', params: { id: String(product.id) } })}
            style={({ pressed }) => [styles.productAdminActionV09, styles.productAdminActionPrimaryV09, pressed && styles.copyDescriptionButtonPressed]}
          >
            <Text style={styles.productAdminActionPrimaryTextV09}>Direktna prodaja</Text>
          </Pressable>
        ) : null}

        {canManageProductFromDetail ? (
          <Pressable
            onPress={() => {
              scheduleCatalogProductEditHandoff(product.id);
              router.replace('/admin/catalog');
            }}
            style={({ pressed }) => [styles.productAdminActionV09, pressed && styles.copyDescriptionButtonPressed]}
          >
            <Text style={styles.productAdminActionTextV09}>Uredi artikal</Text>
          </Pressable>
        ) : null}
      </View>
</Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
  topRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
  back: { alignSelf: 'flex-start', paddingVertical: spacing.sm },
  backText: { ...typography.label, color: theme.primary },
  cartLink: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingVertical: spacing.sm },
  cartText: { ...typography.label, color: theme.primary },
  imageWrap: { height: 310, borderRadius: 28, backgroundColor: theme.surfaceMuted, borderWidth: 1, borderColor: theme.line, alignItems: 'center', justifyContent: 'center', overflow: 'hidden' },
  image: { width: '100%', height: '100%' },
  tags: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  title: { ...typography.h1, color: theme.ink },
  sku: { ...typography.body, color: theme.muted },
  priceCard: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  label: { ...typography.small, color: theme.muted },
  price: { ...typography.h2, color: theme.primaryDark, marginTop: spacing.xs },
  priceIcon: { width: 50, height: 50, borderRadius: radii.lg, backgroundColor: theme.primarySoft, alignItems: 'center', justifyContent: 'center' },
  sectionTitle: { ...typography.h3, color: theme.ink, marginBottom: spacing.md },
  descriptionHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md, marginBottom: spacing.md },
  descriptionTitle: { ...typography.h3, color: theme.ink, flex: 1 },
  copyDescriptionButton: { minHeight: 38, paddingHorizontal: spacing.md, borderRadius: radii.pill, borderWidth: 1, borderColor: theme.line, backgroundColor: theme.surfaceMuted, alignItems: 'center', justifyContent: 'center' },
  copyDescriptionButtonPressed: { opacity: 0.72 },
  copyDescriptionLabel: { ...typography.label, color: theme.primary },
  description: { ...typography.body, color: theme.muted },
  spec: { minHeight: 46, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: spacing.md, borderTopWidth: 1, borderTopColor: theme.line },
  specLabel: { ...typography.small, color: theme.muted, flex: 1 },
  specValue: { ...typography.label, color: theme.ink, textAlign: 'right', flex: 1 },
  buyCard: { gap: spacing.md },
  buyHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
  buyCopy: { ...typography.small, color: theme.muted, marginTop: -spacing.sm },
  stepper: { flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: theme.line, borderRadius: radii.md, overflow: 'hidden' },
  stepButton: { width: 42, height: 42, alignItems: 'center', justifyContent: 'center', backgroundColor: theme.surfaceMuted },
  stepText: { fontSize: 20, lineHeight: 23, color: theme.primaryDark, fontWeight: '800' },
  quantity: { minWidth: 42, textAlign: 'center', ...typography.label, color: theme.ink },
  disabled: { opacity: 0.35 }
,
  commissionCardV09: { gap: spacing.xs },
  commissionValueV09: { ...typography.h2, color: theme.primaryDark },
  productAdminActionsV09: { gap: spacing.sm, marginTop: spacing.sm },
  productAdminActionV09: { minHeight: 52, borderRadius: radii.lg, borderWidth: 1, borderColor: theme.line, backgroundColor: theme.surface, alignItems: 'center', justifyContent: 'center', paddingHorizontal: spacing.lg },
  productAdminActionPrimaryV09: { backgroundColor: theme.primary, borderColor: theme.primary },
  productAdminActionTextV09: { ...typography.label, color: theme.primary },
  productAdminActionPrimaryTextV09: { ...typography.label, color: theme.onPrimary },
});
}
