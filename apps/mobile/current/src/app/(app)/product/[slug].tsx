import { useQuery } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import * as Clipboard from 'expo-clipboard';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { ProductImageGallery } from '@/components/catalog/product-image-gallery';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
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

  const { slug } = useLocalSearchParams<{ slug: string }> ();
  const { hasFeature } = useAuth();
  const { addItem, itemCount } = useCart();
  const allowed = hasFeature('catalog');
  const canCreateOrder = hasFeature('order_create');
  const query = useQuery({
    queryKey: ['product', slug],
    queryFn: () => api.catalog.product(slug),
    enabled: allowed && Boolean(slug),
  });
  const [quantity, setQuantity] = useState(1);
  const product = query.data;

  if (!allowed) return <UnavailableState title="Proizvod nije dostupan" />;
  if (query.isLoading) return <LoadingState label="Učitavanje proizvoda…" />;
  if (query.isError || !product) {
    return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  }

  const stock = product.stock_quantity;
  const price = product.price ?? null;
  const selectedSku = product.sku;
  const cartImageUrl = product.primary_image_thumbnail_url
    ?? product.primary_image_display_url
    ?? product.primary_image_url;
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
      imageUrl: cartImageUrl,
    });

    void (async () => {
      const openCart = await feedback.confirm({
        tone: 'success',
        title: 'Dodato u korpu',
        message: `${product.name} je dodat u korpu.`,
        confirmLabel: 'Otvori korpu',
        cancelLabel: 'Nastavi kupovinu',
      });

      if (openCart) {
        router.push('/cart');
      }
    })();
  };

  return (
    <Screen>
      {/* MOBILE_BUILD16_PRODUCT_DETAIL_REDESIGN_BATCH128 */}
      <View style={styles.topBar}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Nazad na katalog"
          onPress={() => router.back()}
          style={({ pressed }) => [styles.navButton, pressed && styles.pressed]}
        >
          <Glyph
            name="arrow"
            size={18}
            color={themeColors.primary}
            style={styles.backGlyph}
          />
          <Text style={styles.navButtonText}>Katalog</Text>
        </Pressable>

        {canCreateOrder ? (
          <Pressable
            accessibilityRole="button"
            accessibilityLabel={`Otvori korpu${itemCount ? `, ${itemCount} stavki` : ''}`}
            onPress={() => router.push('/cart')}
            style={({ pressed }) => [styles.navButton, pressed && styles.pressed]}
          >
            <Glyph name="cart" size={18} color={themeColors.primary} />
            <Text style={styles.navButtonText}>Korpa{itemCount ? ` · ${itemCount}` : ''}</Text>
          </Pressable>
        ) : null}
      </View>

      <View style={styles.identitySurface}>
        <View style={styles.identityAccent} />
        <View style={styles.tags}>
          <Pill tone={stock > 0 ? 'success' : 'danger'}>
            {stock > 0 ? `${stock} na stanju` : 'Nema na stanju'}
          </Pill>
          {product.brand ? <Pill>{product.brand.name}</Pill> : null}
        </View>
        <Text style={styles.title}>{product.name}</Text>
        <Text style={styles.sku}>{selectedSku}{product.model ? ` · ${product.model}` : ''}</Text>
      </View>

      <View style={styles.gallerySurface}>
        <ProductImageGallery
          mode="detail"
          images={product.images}
          primaryImageUrl={product.primary_image_url}
          primaryImageOriginalUrl={product.primary_image_original_url ?? product.primary_image_url}
          primaryImageDisplayUrl={product.primary_image_display_url ?? product.primary_image_url}
          primaryImageThumbnailUrl={product.primary_image_thumbnail_url ?? product.primary_image_display_url ?? product.primary_image_url}
          productName={product.name}
          productSku={product.sku}
        />
      </View>

      {/* MOBILE_V0_9_PRODUCT_DETAIL_COMMISSION_DIRECT_SALE_BATCH5A */}
      <View style={styles.commercialSurface}>
        <View style={styles.commercialPrimary}>
          <Text style={styles.metricLabel}>Prodajna cena</Text>
          <Text style={styles.price}>
            {price
              ? formatPrimaryMoney(price.amount, price.currency)
              : 'Nije dostupna za ovu dozvolu'}
          </Text>
        </View>
        <View style={styles.commercialMetrics}>
          <View style={[styles.compactMetric, styles.metricDivider]}>
            <Text style={styles.metricLabel}>Provizija po komadu</Text>
            <Text style={styles.metricValue}>{formatPrimaryMoney(product.commission_eur, 'EUR')}</Text>
          </View>
          <View style={styles.compactMetric}>
            <Text style={styles.metricLabel}>Lager</Text>
            <Text style={styles.metricValue}>{stock} kom.</Text>
          </View>
        </View>
      </View>

      {product.description ? (
        <View style={styles.operatorSection}>
          <View style={styles.sectionHeader}>
            <View style={styles.sectionHeadingCopy}>
              <Text style={styles.sectionEyebrow}>INFORMACIJE</Text>
              <Text style={styles.sectionTitle}>Opis artikla</Text>
            </View>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Kopiraj opis artikla"
              onPress={() => void copyDescription()}
              style={({ pressed }) => [styles.secondaryAction, pressed && styles.pressed]}
            >
              <Text style={styles.secondaryActionText}>Kopiraj opis</Text>
            </Pressable>
          </View>
          <Text style={styles.description}>{product.description}</Text>
        </View>
      ) : null}

      {product.specifications?.length ? (
        <View style={styles.operatorSection}>
          <View style={styles.sectionHeadingCopy}>
            <Text style={styles.sectionEyebrow}>PODACI</Text>
            <Text style={styles.sectionTitle}>Specifikacije</Text>
          </View>
          <View style={styles.specList}>
            {product.specifications.map((spec, index) => (
              <View key={`${spec.slug}-${index}`} style={styles.spec}>
                <Text style={styles.specLabel}>{spec.field}</Text>
                <Text style={styles.specValue}>
                  {String(spec.value ?? '—')}{spec.unit ? ` ${spec.unit}` : ''}
                </Text>
              </View>
            ))}
          </View>
        </View>
      ) : null}

      {canCreateOrder ? (
        <View style={[styles.operatorSection, styles.buySection]}>
          <View style={styles.buyHead}>
            <View style={styles.buyCopyWrap}>
              <Text style={styles.sectionEyebrow}>KUPOVINA</Text>
              <Text style={styles.sectionTitle}>Količina</Text>
              <Text style={styles.buyCopy}>
                {stock > 0 ? `Maksimalno ${stock} kom.` : 'Proizvod trenutno nije raspoloživ.'}
              </Text>
            </View>
            <View style={styles.stepper}>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Smanji količinu"
                disabled={quantity <= 1}
                onPress={() => setQuantity((value) => Math.max(1, value - 1))}
                style={({ pressed }) => [
                  styles.stepButton,
                  quantity <= 1 && styles.disabled,
                  pressed && quantity > 1 && styles.pressed,
                ]}
              >
                <Text style={styles.stepText}>−</Text>
              </Pressable>
              <Text style={styles.quantity}>{quantity}</Text>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Povećaj količinu"
                disabled={quantity >= stock}
                onPress={() => setQuantity((value) => Math.min(stock, value + 1))}
                style={({ pressed }) => [
                  styles.stepButton,
                  quantity >= stock && styles.disabled,
                  pressed && quantity < stock && styles.pressed,
                ]}
              >
                <Text style={styles.stepText}>+</Text>
              </Pressable>
            </View>
          </View>
          <Button onPress={addToCart} disabled={!canAdd}>
            Dodaj u korpu{canAdd ? ` · ${quantity} kom.` : ''}
          </Button>
        </View>
      ) : (
        <View style={styles.unavailableSurface}>
          <Glyph name="info" size={20} color={themeColors.muted} />
          <Text style={styles.unavailableText}>Kreiranje porudžbine nije dostupno za ovaj nalog.</Text>
        </View>
      )}

      <View style={styles.productAdminActionsV09}>
        {isSuperAdminProductView ? (
          <Pressable
            accessibilityRole="button"
            onPress={() => router.push({
              pathname: '/admin/catalog/[id]/direct-sale',
              params: { id: String(product.id) },
            })}
            style={({ pressed }) => [
              styles.productAdminActionV09,
              styles.productAdminActionPrimaryV09,
              pressed && styles.pressed,
            ]}
          >
            <Text style={styles.productAdminActionPrimaryTextV09}>Direktna prodaja</Text>
          </Pressable>
        ) : null}

        {canManageProductFromDetail ? (
          <Pressable
            accessibilityRole="button"
            onPress={() => {
              scheduleCatalogProductEditHandoff(product.id);
              router.replace('/admin/catalog');
            }}
            style={({ pressed }) => [styles.productAdminActionV09, pressed && styles.pressed]}
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
    topBar: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    navButton: {
      minHeight: 40,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.xs,
      paddingHorizontal: spacing.sm,
      borderRadius: radii.md,
    },
    navButtonText: { ...typography.label, color: theme.primary },
    backGlyph: { transform: [{ rotate: '180deg' }] },
    identitySurface: {
      position: 'relative',
      overflow: 'hidden',
      gap: spacing.sm,
      padding: spacing.lg,
      paddingLeft: spacing.xl,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.xl,
      backgroundColor: theme.surface,
    },
    identityAccent: {
      position: 'absolute',
      left: 0,
      top: 0,
      bottom: 0,
      width: 4,
      backgroundColor: theme.primary,
    },
    tags: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    title: { ...typography.h1, color: theme.ink },
    sku: { ...typography.body, color: theme.muted },
    gallerySurface: { gap: spacing.sm },
    commercialSurface: {
      overflow: 'hidden',
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surface,
    },
    commercialPrimary: { padding: spacing.lg },
    metricLabel: { ...typography.small, color: theme.muted },
    price: {
      ...typography.h2,
      color: theme.primaryDark,
      marginTop: spacing.xs,
      fontVariant: ['tabular-nums'],
    },
    commercialMetrics: {
      flexDirection: 'row',
      borderTopWidth: 1,
      borderTopColor: theme.line,
    },
    compactMetric: { flex: 1, gap: spacing.xs, padding: spacing.md },
    metricDivider: { borderRightWidth: 1, borderRightColor: theme.line },
    metricValue: { ...typography.label, color: theme.ink, fontVariant: ['tabular-nums'] },
    operatorSection: {
      gap: spacing.md,
      padding: spacing.lg,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surface,
    },
    sectionHeader: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    sectionHeadingCopy: { flex: 1, gap: 3 },
    sectionEyebrow: {
      ...typography.small,
      color: theme.primary,
      fontWeight: '800',
      letterSpacing: 0.7,
    },
    sectionTitle: { ...typography.h3, color: theme.ink },
    secondaryAction: {
      minHeight: 38,
      alignItems: 'center',
      justifyContent: 'center',
      paddingHorizontal: spacing.md,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.md,
      backgroundColor: theme.surfaceContainer,
    },
    secondaryActionText: { ...typography.label, color: theme.primary },
    description: { ...typography.body, color: theme.muted },
    specList: { borderTopWidth: 1, borderTopColor: theme.line },
    spec: {
      minHeight: 48,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
      borderBottomWidth: 1,
      borderBottomColor: theme.line,
    },
    specLabel: { ...typography.small, color: theme.muted, flex: 1 },
    specValue: { ...typography.label, color: theme.ink, textAlign: 'right', flex: 1 },
    buySection: { gap: spacing.lg },
    buyHead: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      flexWrap: 'wrap',
      gap: spacing.md,
    },
    buyCopyWrap: { flex: 1, minWidth: 170, gap: 3 },
    buyCopy: { ...typography.small, color: theme.muted },
    stepper: {
      flexDirection: 'row',
      alignItems: 'center',
      overflow: 'hidden',
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.md,
      backgroundColor: theme.surfaceContainer,
    },
    stepButton: {
      width: 42,
      height: 42,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: theme.surfaceContainer,
    },
    stepText: { fontSize: 20, lineHeight: 23, color: theme.primaryDark, fontWeight: '800' },
    quantity: {
      minWidth: 44,
      textAlign: 'center',
      ...typography.label,
      color: theme.ink,
      fontVariant: ['tabular-nums'],
    },
    unavailableSurface: {
      minHeight: 58,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.sm,
      padding: spacing.md,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surfaceMuted,
    },
    unavailableText: { ...typography.small, color: theme.muted, flex: 1 },
    productAdminActionsV09: {
      gap: spacing.sm,
      paddingTop: spacing.md,
      borderTopWidth: 1,
      borderTopColor: theme.line,
    },
    productAdminActionV09: {
      minHeight: 50,
      alignItems: 'center',
      justifyContent: 'center',
      paddingHorizontal: spacing.lg,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.md,
      backgroundColor: theme.surface,
    },
    productAdminActionPrimaryV09: {
      backgroundColor: theme.primary,
      borderColor: theme.primary,
    },
    productAdminActionTextV09: { ...typography.label, color: theme.primary },
    productAdminActionPrimaryTextV09: { ...typography.label, color: theme.onPrimary },
    pressed: { opacity: 0.78 },
    disabled: { opacity: 0.35 },
  });
}
