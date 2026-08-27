import { useMemo } from 'react';
import {
  Image,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';

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
}: {
  product: Product;
  onPress: () => void;
}) {
  const { colors: themeColors } = useAppTheme();
  const { formatPrimaryMoney } = useMoneyPresentation();

  const styles = useMemo(
    () => createStyles(themeColors),
    [themeColors],
  );

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

  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) =>
        pressed && styles.pressed
      }
    >
      <Card style={styles.card}>
        <View style={styles.imageWrap}>
          {product.primary_image_url ? (
            <Image
              source={{
                uri: product.primary_image_url,
              }}
              style={styles.image}
              resizeMode="contain"
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

          <Text
            style={styles.meta}
            numberOfLines={1}
          >
            {product.brand?.name ??
              product.type?.name ??
              product.sku}
          </Text>

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
<Text style={styles.sku}>
              {product.sku}
            </Text>
          </View>
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
  });
}
