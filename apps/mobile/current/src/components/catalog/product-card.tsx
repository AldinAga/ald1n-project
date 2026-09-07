import { Image } from 'expo-image';
import { useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { ProductImageGallery } from '@/components/catalog/product-image-gallery';
import { Glyph } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useMoneyPresentation } from '@/features/preferences/money-presentation';
import { useAppTheme } from '@/theme/app-theme';
import type { Product } from '@/types/api';

// MOBILE_BUILD16_CATALOG_PRODUCT_ROW_BATCH127
export function ProductCard({
  product,
  onPress,
  showSku = true,
}: {
  product: Product;
  onPress: () => void;
  showSku?: boolean;
}) {
  const { colors: themeColors } = useAppTheme();
  const { formatPrimaryMoney } = useMoneyPresentation();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);
  const [imagesOpen, setImagesOpen] = useState(false);

  const catalogImageUrl = product.primary_image_thumbnail_url
    ?? product.primary_image_display_url
    ?? product.primary_image_url;

  const stockTone = product.stock_quantity > 5
    ? 'success'
    : product.stock_quantity > 0
      ? 'warning'
      : 'danger';

  const stockLabel = product.stock_quantity > 0
    ? `${product.stock_quantity} na stanju`
    : 'Nema na stanju';

  const metaLabel = [product.brand?.name, product.type?.name]
    .filter(Boolean)
    .join(' · ') || (showSku ? product.sku : null);

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Otvori artikal ${product.name}`}
      onPress={onPress}
      style={({ pressed }) => [styles.shell, pressed && styles.pressed]}
    >
      <View style={styles.row}>
        <View style={styles.imageWrap}>
          {catalogImageUrl ? (
            <Image
              source={{ uri: catalogImageUrl }}
              style={styles.image}
              contentFit="contain"
              cachePolicy="memory-disk"
              transition={100}
            />
          ) : (
            <Glyph name="box" size={32} color={themeColors.primary} />
          )}
        </View>

        <View style={styles.content}>
          <View style={styles.topRow}>
            <Pill tone={stockTone}>{stockLabel}</Pill>
            <Glyph name="arrow" size={21} color={themeColors.muted} />
          </View>

          <Text style={styles.name} numberOfLines={2}>{product.name}</Text>
          {metaLabel ? <Text style={styles.meta} numberOfLines={1}>{metaLabel}</Text> : null}
          {showSku ? <Text style={styles.sku} numberOfLines={1}>SKU: {product.sku}</Text> : null}

          <View style={styles.valueRow}>
            <Text style={styles.price} numberOfLines={1}>
              {product.price
                ? formatPrimaryMoney(product.price.amount, product.price.currency)
                : 'Cena po dozvoli'}
            </Text>
            {/* MOBILE_V0_9_CATALOG_COMMISSION_BATCH5A */}
            <Text style={styles.commission} numberOfLines={1}>
              Provizija: {formatPrimaryMoney(product.commission_eur, 'EUR')}
            </Text>
          </View>

          <Pressable
            accessibilityRole="button"
            accessibilityLabel={imagesOpen ? 'Sakrij fotografije artikla' : 'Prikaži sve fotografije artikla'}
            onPress={(event) => {
              event.stopPropagation();
              setImagesOpen((current) => !current);
            }}
            style={({ pressed }) => [styles.galleryToggle, pressed && styles.galleryTogglePressed]}
          >
            <Text style={styles.galleryToggleText}>{imagesOpen ? 'Sakrij slike' : 'Sve slike'}</Text>
          </Pressable>
        </View>
      </View>

      {imagesOpen ? (
        <View style={styles.gallerySection}>
          <ProductImageGallery
            mode="catalog"
            productName={product.name}
            productSku={product.sku}
            productSlug={product.slug}
            primaryImageUrl={product.primary_image_url}
            primaryImageOriginalUrl={product.primary_image_original_url ?? product.primary_image_url}
            primaryImageDisplayUrl={product.primary_image_display_url ?? product.primary_image_url}
            primaryImageThumbnailUrl={product.primary_image_thumbnail_url ?? product.primary_image_display_url ?? product.primary_image_url}
            stopParentPress
          />
        </View>
      ) : null}
    </Pressable>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    shell: {
      overflow: 'hidden',
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surface,
      shadowColor: theme.black,
      shadowOpacity: 0.08,
      shadowRadius: 5,
      shadowOffset: { width: 0, height: 2 },
      elevation: 1,
    },
    pressed: { opacity: 0.94, transform: [{ scale: 0.988 }] },
    row: { minHeight: 142, flexDirection: 'row' },
    imageWrap: {
      width: 118,
      minHeight: 142,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: theme.surfaceMuted,
      borderRightWidth: 1,
      borderRightColor: theme.line,
    },
    image: { width: '100%', height: '100%' },
    content: { flex: 1, padding: spacing.md, gap: 6 },
    topRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.sm,
    },
    name: { ...typography.h3, color: theme.ink, lineHeight: 21 },
    meta: { ...typography.small, color: theme.muted },
    sku: { ...typography.small, color: theme.muted },
    valueRow: {
      marginTop: 2,
      paddingTop: spacing.sm,
      borderTopWidth: 1,
      borderTopColor: theme.line,
      gap: 2,
    },
    price: { ...typography.label, color: theme.primaryDark },
    commission: { ...typography.small, color: theme.muted },
    galleryToggle: {
      alignSelf: 'flex-start',
      minHeight: 34,
      justifyContent: 'center',
      marginTop: 2,
      paddingHorizontal: spacing.md,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.md,
      backgroundColor: theme.surfaceContainer,
    },
    galleryTogglePressed: { opacity: 0.76 },
    galleryToggleText: { ...typography.small, color: theme.primary, fontWeight: '800' },
    gallerySection: {
      padding: spacing.md,
      borderTopWidth: 1,
      borderTopColor: theme.line,
      backgroundColor: theme.surface,
    },
  });
}
