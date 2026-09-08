import { useQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { useCallback, useMemo, useState } from 'react';
import {
  FlatList,
  Pressable,
  StyleSheet,
  Text,
  View,
  useWindowDimensions,
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
  originalUrl: string;
  displayUrl: string;
  primary: boolean;
};

// MOBILE_BUILD17_PHONE_MEDIA_ASPECT_BATCH137
const DEFAULT_GALLERY_ASPECT_RATIO = 4 / 3;
const MIN_GALLERY_ASPECT_RATIO = 9 / 16;
const MAX_GALLERY_ASPECT_RATIO = 16 / 9;

function safeGalleryAspectRatio(width: number, height: number): number {
  if (!Number.isFinite(width) || !Number.isFinite(height) || width <= 0 || height <= 0) {
    return DEFAULT_GALLERY_ASPECT_RATIO;
  }

  return Math.max(MIN_GALLERY_ASPECT_RATIO, Math.min(MAX_GALLERY_ASPECT_RATIO, width / height));
}

type ProductImageGalleryProps = {
  productName: string;
  productSku: string;
  productSlug?: string;
  primaryImageUrl?: string | null;
  primaryImageOriginalUrl?: string | null;
  primaryImageDisplayUrl?: string | null;
  primaryImageThumbnailUrl?: string | null;
  images?: ProductImage[];
  mode: GalleryMode;
  stopParentPress?: boolean;
};

function cleanUrl(value: string | null | undefined): string | null {
  const cleaned = value?.trim();
  return cleaned ? cleaned : null;
}

function normalizeImages(
  images: ProductImage[] | undefined,
  primaryImageUrl: string | null | undefined,
  primaryImageOriginalUrl: string | null | undefined,
  primaryImageDisplayUrl: string | null | undefined,
  primaryImageThumbnailUrl: string | null | undefined,
  mode: GalleryMode,
): GalleryItem[] {
  const items: GalleryItem[] = [];
  const seen = new Set<string> ();

  for (const image of images ?? []) {
    const originalUrl = cleanUrl(image.original_url) ?? cleanUrl(image.url);
    const displayUrl = mode === 'catalog'
      ? cleanUrl(image.thumbnail_url) ?? cleanUrl(image.display_url) ?? originalUrl
      : cleanUrl(image.display_url) ?? cleanUrl(image.thumbnail_url) ?? originalUrl;
    if (!originalUrl || !displayUrl) continue;
    const identity = `${image.id}:${originalUrl}`;
    if (seen.has(identity)) continue;
    seen.add(identity);
    items.push({
      key: identity,
      originalUrl,
      displayUrl,
      primary: Boolean(image.primary),
    });
  }

  const primaryOriginal = cleanUrl(primaryImageOriginalUrl) ?? cleanUrl(primaryImageUrl);
  const primaryDisplay = mode === 'catalog'
    ? cleanUrl(primaryImageThumbnailUrl) ?? cleanUrl(primaryImageDisplayUrl) ?? primaryOriginal
    : cleanUrl(primaryImageDisplayUrl) ?? cleanUrl(primaryImageThumbnailUrl) ?? primaryOriginal;

  if (primaryOriginal && primaryDisplay) {
    const existing = items.find((item) => item.originalUrl === primaryOriginal);
    if (existing) {
      existing.primary = true;
      existing.displayUrl = primaryDisplay;
    } else {
      items.unshift({
        key: `primary:${primaryOriginal}`,
        originalUrl: primaryOriginal,
        displayUrl: primaryDisplay,
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
  primaryImageOriginalUrl,
  primaryImageDisplayUrl,
  primaryImageThumbnailUrl,
  images,
  mode,
  stopParentPress = false,
}: ProductImageGalleryProps) {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const { width: viewportWidth } = useWindowDimensions();
  const [downloadingKey, setDownloadingKey] = useState<string | null> (null);
  const [aspectRatios, setAspectRatios] = useState<Record<string, number>> ({});
  const detailTileWidth = Math.max(260, Math.min(420, viewportWidth - (spacing.lg * 2)));

  const rememberAspectRatio = useCallback((key: string, width: number, height: number) => {
    const next = safeGalleryAspectRatio(width, height);
    setAspectRatios((current) => current[key] === next ? current : { ...current, [key]: next });
  }, []);

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
      detailQuery.data?.primary_image_original_url ?? primaryImageOriginalUrl,
      detailQuery.data?.primary_image_display_url ?? primaryImageDisplayUrl,
      detailQuery.data?.primary_image_thumbnail_url ?? primaryImageThumbnailUrl,
      mode,
    ),
    [
      detailQuery.data?.images,
      detailQuery.data?.primary_image_url,
      detailQuery.data?.primary_image_original_url,
      detailQuery.data?.primary_image_display_url,
      detailQuery.data?.primary_image_thumbnail_url,
      images,
      mode,
      primaryImageDisplayUrl,
      primaryImageOriginalUrl,
      primaryImageThumbnailUrl,
      primaryImageUrl,
    ],
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
        originalUrl: image.originalUrl,
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

      <FlatList
        data={resolvedImages}
        horizontal
        nestedScrollEnabled
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}
        keyExtractor={(item) => item.key}
        initialNumToRender={2}
        maxToRenderPerBatch={3}
        windowSize={3}
        removeClippedSubviews
        renderItem={({ item: image, index }) => {
          const downloading = downloadingKey === image.key;
          const tileWidth = mode === 'catalog' ? 172 : detailTileWidth;
          const aspectRatio = aspectRatios[image.key] ?? DEFAULT_GALLERY_ASPECT_RATIO;
          return (
            <View style={[styles.tile, { width: tileWidth }]}>
              <Image
                source={{ uri: image.displayUrl }}
                style={[styles.image, { aspectRatio }]}
                contentFit="contain"
                cachePolicy="memory-disk"
                transition={120}
                recyclingKey={image.key}
                onLoad={(event) => rememberAspectRatio(
                  image.key,
                  event.source.width,
                  event.source.height,
                )}
              />

              <View style={styles.metaRow}>
                <View style={styles.labelWrap}>
                  <Text style={styles.imageLabel}>Slika {index + 1}</Text>
                  {image.primary ? <Text style={styles.primaryLabel}>Glavna</Text> : null}
                </View>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel={`Preuzmi originalnu sliku ${index + 1}`}
                  disabled={Boolean(downloadingKey)}
                  onPress={(event) => void handleDownload(image, index, event)}
                  style={({ pressed }) => [
                    styles.downloadButton,
                    pressed && styles.pressed,
                    downloadingKey && !downloading ? styles.disabled : null,
                  ]}
                >
                  <Text style={styles.downloadText}>
                    {downloading ? 'Priprema originala...' : 'Preuzmi original'}
                  </Text>
                </Pressable>
              </View>
            </View>
          );
        }}
      />
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    root: { gap: spacing.sm },
    header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: spacing.sm },
    title: { ...typography.h3, color: theme.ink },
    count: { ...typography.small, color: theme.muted },
    scrollContent: { gap: spacing.md, paddingRight: spacing.sm },
    tile: { borderWidth: 1, borderColor: theme.line, borderRadius: radii.lg, overflow: 'hidden', backgroundColor: theme.surface },
    image: { width: '100%', backgroundColor: theme.surfaceMuted },
    metaRow: { minHeight: 52, paddingHorizontal: spacing.sm, paddingVertical: spacing.sm, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.sm, borderTopWidth: 1, borderTopColor: theme.line },
    labelWrap: { flex: 1, minWidth: 0 },
    imageLabel: { ...typography.small, color: theme.ink },
    primaryLabel: { ...typography.small, color: theme.primary, marginTop: 2 },
    downloadButton: { minHeight: 36, paddingHorizontal: spacing.md, borderRadius: radii.pill, backgroundColor: theme.primarySoft, alignItems: 'center', justifyContent: 'center' },
    downloadText: { ...typography.label, color: theme.primaryDark },
    loading: { minHeight: 72, borderWidth: 1, borderColor: theme.line, borderRadius: radii.md, backgroundColor: theme.surfaceMuted, padding: spacing.md, alignItems: 'center', justifyContent: 'center', gap: spacing.sm },
    empty: { minHeight: 92, borderWidth: 1, borderColor: theme.line, borderRadius: radii.md, backgroundColor: theme.surfaceMuted, padding: spacing.md, alignItems: 'center', justifyContent: 'center', gap: spacing.sm },
    loadingText: { ...typography.small, color: theme.muted, textAlign: 'center' },
    retryButton: { minHeight: 34, paddingHorizontal: spacing.md, borderRadius: radii.pill, borderWidth: 1, borderColor: theme.line, backgroundColor: theme.surface, alignItems: 'center', justifyContent: 'center' },
    retryText: { ...typography.label, color: theme.primary },
    pressed: { opacity: 0.72 },
    disabled: { opacity: 0.45 },
  });
}
