import { useQuery } from '@tanstack/react-query';
import { useMemo, useState } from 'react';
import {
  Image,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
  type GestureResponderEvent,
} from 'react-native';

import { useAppFeedback } from '@/components/ui/app-feedback';
import { Glyph } from '@/components/ui/glyph';
import {
  radii,
  spacing,
  typography,
  type AppColors,
} from '@/constants/theme';
import { downloadAndShareProductImage } from '@/features/catalog/product-image-download';
import { api } from '@/lib/api/endpoints';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';
import type { ProductImage } from '@/types/api';

type GalleryMode = 'catalog' | 'detail';

type GalleryItem = {
  key: string;
  url: string;
  primary: boolean;
};

type ProductImageGalleryProps = {
  productName: string;
  productSku: string;
  productSlug?: string;
  primaryImageUrl?: string | null;
  images?: ProductImage[];
  mode: GalleryMode;
  stopParentPress?: boolean;
};

function normalizeImages(
  images: ProductImage[] | undefined,
  primaryImageUrl: string | null | undefined,
): GalleryItem[] {
  const items: GalleryItem[] = [];
  const seen = new Set<string>();

  for (const image of images ?? []) {
    const url = image.url?.trim();
    if (!url || seen.has(url)) continue;
    seen.add(url);
    items.push({
      key: `${image.id}-${url}`,
      url,
      primary: Boolean(image.primary),
    });
  }

  const primaryUrl = primaryImageUrl?.trim();
  if (primaryUrl) {
    const existing = items.find((item) => item.url === primaryUrl);
    if (existing) {
      existing.primary = true;
    } else {
      items.unshift({
        key: `primary-${primaryUrl}`,
        url: primaryUrl,
        primary: true,
      });
    }
  }

  return items;
}

export function ProductImageGallery({
  productName,
  productSku,
  productSlug,
  primaryImageUrl,
  images,
  mode,
  stopParentPress = false,
}: ProductImageGalleryProps) {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const [downloadingKey, setDownloadingKey] = useState<string | null>(null);

  const needsDetail = mode === 'catalog' && images === undefined && Boolean(productSlug);
  const detailQuery = useQuery({
    queryKey: ['product', productSlug],
    queryFn: () => api.catalog.product(String(productSlug)),
    enabled: needsDetail,
    staleTime: 45_000,
  });

  const resolvedImages = useMemo(
    () => normalizeImages(
      images ?? detailQuery.data?.images,
      detailQuery.data?.primary_image_url ?? primaryImageUrl,
    ),
    [detailQuery.data?.images, detailQuery.data?.primary_image_url, images, primaryImageUrl],
  );

  const stopEvent = (event: GestureResponderEvent) => {
    if (stopParentPress) event.stopPropagation();
  };

  const handleDownload = async (
    image: GalleryItem,
    index: number,
    event: GestureResponderEvent,
  ) => {
    stopEvent(event);
    if (downloadingKey) return;
    setDownloadingKey(image.key);
    try {
      await downloadAndShareProductImage({
        url: image.url,
        productName,
        productSku,
        imageNumber: index + 1,
      });
    } catch (error) {
      feedback.notify({
        tone: 'danger',
        title: 'Preuzimanje slike nije uspelo',
        message: error instanceof Error ? error.message : 'Pokušaj ponovo.',
      });
    } finally {
      setDownloadingKey(null);
    }
  };

  if (needsDetail && detailQuery.isLoading) {
    return (
      <View style={styles.loading} onTouchStart={stopEvent}>
        <Text style={styles.loadingText}>Učitavanje svih slika...</Text>
      </View>
    );
  }

  if (needsDetail && detailQuery.isError) {
    return (
      <View style={styles.loading} onTouchStart={stopEvent}>
        <Text style={styles.loadingText}>Slike trenutno nisu dostupne.</Text>
        <Pressable
          accessibilityRole="button"
          onPress={(event) => {
            stopEvent(event);
            void detailQuery.refetch();
          }}
          style={({ pressed }) => [styles.retryButton, pressed && styles.pressed]}
        >
          <Text style={styles.retryText}>Pokušaj ponovo</Text>
        </Pressable>
      </View>
    );
  }

  if (resolvedImages.length === 0) {
    return (
      <View style={styles.empty} onTouchStart={stopEvent}>
        <Glyph name="box" size={30} color={themeColors.primary} />
        <Text style={styles.loadingText}>Artikal nema sačuvane fotografije.</Text>
      </View>
    );
  }

  return (
    <View style={styles.root} onTouchStart={stopEvent}>
      <View style={styles.header}>
        <Text style={styles.title}>Fotografije</Text>
        <Text style={styles.count}>{resolvedImages.length} ukupno</Text>
      </View>

      <ScrollView
        horizontal
        nestedScrollEnabled
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}
      >
        {resolvedImages.map((image, index) => {
          const downloading = downloadingKey === image.key;
          return (
            <View
              key={image.key}
              style={[
                styles.tile,
                mode === 'catalog' ? styles.catalogTile : styles.detailTile,
              ]}
            >
              <Image
                source={{ uri: image.url }}
                style={[
                  styles.image,
                  mode === 'catalog' ? styles.catalogImage : styles.detailImage,
                ]}
                resizeMode="contain"
              />

              <View style={styles.metaRow}>
                <View style={styles.labelWrap}>
                  <Text style={styles.imageLabel}>Slika {index + 1}</Text>
                  {image.primary ? <Text style={styles.primaryLabel}>Glavna</Text> : null}
                </View>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel={`Preuzmi sliku ${index + 1}`}
                  disabled={Boolean(downloadingKey)}
                  onPress={(event) => void handleDownload(image, index, event)}
                  style={({ pressed }) => [
                    styles.downloadButton,
                    pressed && styles.pressed,
                    downloadingKey && !downloading ? styles.disabled : null,
                  ]}
                >
                  <Text style={styles.downloadText}>
                    {downloading ? 'Priprema...' : 'Preuzmi'}
                  </Text>
                </Pressable>
              </View>
            </View>
          );
        })}
      </ScrollView>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    root: {
      gap: spacing.sm,
    },
    header: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: spacing.sm,
    },
    title: {
      ...typography.h3,
      color: theme.ink,
    },
    count: {
      ...typography.small,
      color: theme.muted,
    },
    scrollContent: {
      gap: spacing.md,
      paddingRight: spacing.sm,
    },
    tile: {
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      overflow: 'hidden',
      backgroundColor: theme.surface,
    },
    catalogTile: {
      width: 172,
    },
    detailTile: {
      width: 286,
    },
    image: {
      width: '100%',
      backgroundColor: theme.surfaceMuted,
    },
    catalogImage: {
      height: 126,
    },
    detailImage: {
      height: 236,
    },
    metaRow: {
      minHeight: 52,
      paddingHorizontal: spacing.sm,
      paddingVertical: spacing.sm,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.sm,
      borderTopWidth: 1,
      borderTopColor: theme.line,
    },
    labelWrap: {
      flex: 1,
      minWidth: 0,
    },
    imageLabel: {
      ...typography.small,
      color: theme.ink,
    },
    primaryLabel: {
      ...typography.small,
      color: theme.primary,
      marginTop: 2,
    },
    downloadButton: {
      minHeight: 36,
      paddingHorizontal: spacing.md,
      borderRadius: radii.pill,
      backgroundColor: theme.primarySoft,
      alignItems: 'center',
      justifyContent: 'center',
    },
    downloadText: {
      ...typography.label,
      color: theme.primaryDark,
    },
    loading: {
      minHeight: 72,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.md,
      backgroundColor: theme.surfaceMuted,
      padding: spacing.md,
      alignItems: 'center',
      justifyContent: 'center',
      gap: spacing.sm,
    },
    empty: {
      minHeight: 92,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.md,
      backgroundColor: theme.surfaceMuted,
      padding: spacing.md,
      alignItems: 'center',
      justifyContent: 'center',
      gap: spacing.sm,
    },
    loadingText: {
      ...typography.small,
      color: theme.muted,
      textAlign: 'center',
    },
    retryButton: {
      minHeight: 34,
      paddingHorizontal: spacing.md,
      borderRadius: radii.pill,
      borderWidth: 1,
      borderColor: theme.line,
      backgroundColor: theme.surface,
      alignItems: 'center',
      justifyContent: 'center',
    },
    retryText: {
      ...typography.label,
      color: theme.primary,
    },
    pressed: {
      opacity: 0.72,
    },
    disabled: {
      opacity: 0.45,
    },
  });
}
