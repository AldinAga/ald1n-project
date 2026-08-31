import { Image } from 'expo-image';
import { useMemo, useState } from 'react';
import {
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { ProductImageGallery } from '@/components/catalog/product-image-gallery';
import { Card } from '@/components/ui/card';
import { Glyph } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import {
  radii,
  spacing,
  typography,
  type AppColors,
} from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';
import { useMoneyPresentation } from '@/features/preferences/money-presentation';
import type { Product } from '@/types/api';

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

  const styles = useMemo(
    () => createStyles(themeColors),
    [themeColors],
  );
  const [imagesOpen, setImagesOpen] = useState(false);
  const catalogImageUrl = product.primary_image_thumbnail_url
    ?? product.primary_image_display_url
    ?? product.primary_image_url;

  const stockTone =
    product.stock_quantity > 5
      ? 'success'
      : product.stock_quantity > 0
        ? 'warning'
        : 'danger';

  const stockLabel =
    product.stock_quantity > 0
      ? `${product.stock_quantity} na stanju`
      : 'Nema na stanju';

  // MOBILE_V1_0_CATALOG_HIDE_SKU_LIST_BATCH80
  const metaLabel =
    product.brand?.name
    ?? product.type?.name
    ?? (showSku ? product.sku : null);

  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) =>
        pressed && styles.pressed
      }
    >
      <Card style={styles.card}>
        <View style={styles.imageWrap}>
          {catalogImageUrl ? (
            <Image
              source={{
                uri: catalogImageUrl,
              }}
              style={styles.image}
              contentFit="contain"
              cachePolicy="memory-disk"
              transition={100}
            />
          ) : (
            <Glyph
              name="box"
              size={34}
              color={themeColors.primary}
            />
          )}
        </View>

        <View style={styles.content}>
          <View style={styles.topRow}>
            <Pill tone={stockTone}>
              {stockLabel}
            </Pill>

            <Glyph
              name="arrow"
              size={25}
              color={themeColors.muted}
            />
          </View>

          <Text
            style={styles.name}
            numberOfLines={2}
          >
            {product.name}
          </Text>

          {metaLabel ? (
            <Text
              style={styles.meta}
              numberOfLines={1}
            >
              {metaLabel}
            </Text>
          ) : null}

          <View style={styles.footer}>
            <Text style={styles.price}>
              {product.price
                ? formatPrimaryMoney(
                    product.price.amount,
                    product.price.currency,
                  )
                : 'Cena po dozvoli'}
            </Text>

                        {/* MOBILE_V0_9_CATALOG_COMMISSION_BATCH5A */}
            <Text style={styles.sku}>Provizija: {formatPrimaryMoney(product.commission_eur, 'EUR')}</Text>
{showSku ? (
              <Text style={styles.sku}>
                {product.sku}
              </Text>
            ) : null}
          </View>
        </View>

        <View style={styles.gallerySection}>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel={imagesOpen ? 'Sakrij fotografije artikla' : 'Prikaži sve fotografije artikla'}
            onPress={(event) => {
              event.stopPropagation();
              setImagesOpen((current) => !current);
            }}
            style={({ pressed }) => [
              styles.galleryToggle,
              pressed && styles.galleryTogglePressed,
            ]}
          >
            <Text style={styles.galleryToggleText}>
              {imagesOpen ? 'Sakrij slike' : 'Sve slike'}
            </Text>
          </Pressable>

          {imagesOpen ? (
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
          ) : null}
        </View>
      </Card>
    </Pressable>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    pressed: {
      transform: [{ scale: 0.99 }],
      opacity: 0.92,
    },

    card: {
      padding: 0,
      overflow: 'hidden',
    },

    imageWrap: {
      height: 180,
      backgroundColor: theme.surfaceMuted,
      alignItems: 'center',
      justifyContent: 'center',
      borderTopLeftRadius: radii.xl,
      borderTopRightRadius: radii.xl,
    },

    image: {
      width: '100%',
      height: '100%',
    },

    content: {
      padding: spacing.lg,
      gap: spacing.sm,
    },

    topRow: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
    },

    name: {
      ...typography.h3,
      color: theme.ink,
    },

    meta: {
      ...typography.small,
      color: theme.muted,
    },

    footer: {
      marginTop: spacing.sm,
      paddingTop: spacing.md,
      borderTopWidth: 1,
      borderTopColor: theme.line,
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: spacing.sm,
    },

    price: {
      ...typography.label,
      color: theme.primaryDark,
      flex: 1,
    },

    sku: {
      ...typography.small,
      color: theme.muted,
    },

    gallerySection: {
      paddingHorizontal: spacing.lg,
      paddingBottom: spacing.lg,
      gap: spacing.md,
      borderTopWidth: 1,
      borderTopColor: theme.line,
      backgroundColor: theme.surface,
    },

    galleryToggle: {
      minHeight: 44,
      marginTop: spacing.md,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.pill,
      backgroundColor: theme.surfaceMuted,
      alignItems: 'center',
      justifyContent: 'center',
      paddingHorizontal: spacing.md,
    },

    galleryTogglePressed: {
      opacity: 0.72,
    },

    galleryToggleText: {
      ...typography.label,
      color: theme.primary,
    },
  });
}
