import { Image } from 'expo-image';
import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmAction } from '@/components/ui/confirm-action';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { apiAdminCatalog } from '@/features/admin/catalog-admin-api';
import {
  formatProductImageSize,
  pickProductImages,
} from '@/features/catalog/product-image-picker';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import { useAppTheme } from '@/theme/app-theme';
import type {
  AdminProductImageLimits,
  AdminProductImageUploadFile,
  AdminProductManagedImage,
} from '@/types/api';

// MOBILE_V0_8_SHARED_PRODUCT_IMAGE_MANAGER_BATCH9
export type DraftImageRotation = 0 | 90 | 180 | 270;
export type DraftProductImage = AdminProductImageUploadFile & {
  rotation_degrees: DraftImageRotation;
};

function apiMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return fallback;
}

function nextRotation(value: DraftImageRotation): DraftImageRotation {
  if (value === 0) return 90;
  if (value === 90) return 180;
  if (value === 180) return 270;
  return 0;
}

function moveItem<T> (source: T[], from: number, to: number): T[] {
  if (from === to || from < 0 || to < 0 || from >= source.length || to >= source.length) return source;
  const next = [...source];
  const item = next[from];
  if (item === undefined) return source;
  next.splice(from, 1);
  next.splice(to, 0, item);
  return next;
}

function imageKey(image: Pick<AdminProductImageUploadFile, 'uri' | 'name' | 'size'>): string {
  return `${image.uri}\u0000${image.name}\u0000${image.size}`;
}

export function DraftProductImageManager({
  images,
  onChange,
  limits,
  enabled,
  helper,
}: {
  images: DraftProductImage[];
  onChange: (images: DraftProductImage[]) => void;
  limits: AdminProductImageLimits;
  enabled: boolean;
  helper?: string;
}) {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const [picking, setPicking] = useState(false);

  const pick = async () => {
    setPicking(true);
    try {
      const result = await pickProductImages(images, limits);
      if (result.files.length > 0) {
        const existing = new Set(images.map(imageKey));
        const appended: DraftProductImage[] = [];
        for (const file of result.files) {
          if (existing.has(imageKey(file))) continue;
          existing.add(imageKey(file));
          appended.push({ ...file, rotation_degrees: 0 });
        }
        if (appended.length > 0) onChange([...images, ...appended]);
      }
      if (result.rejected.length > 0) {
        feedback.notify({
          tone: 'warning',
          title: 'Neke fotografije nisu dodate',
          message: result.rejected.join(' '),
          durationMs: 5200,
        });
      }
    } catch (error) {
      feedback.notify({
        tone: 'danger',
        title: 'Fotografije nije moguće izabrati',
        message: apiMessage(error, 'Pokušaj ponovo.'),
      });
    } finally {
      setPicking(false);
    }
  };

  if (!enabled) {
    return (
      <Card muted>
        <Text style={styles.help}>
          Fotografije može menjati korisnik sa catalog.manage_images dozvolom.
        </Text>
      </Card>
    );
  }

  return (
    <View style={styles.manager}>
      <Text style={styles.help}>
        {helper ?? `JPG/JPEG/PNG/WebP · do ${limits.max_files} fajlova po izboru · maksimalno ${formatProductImageSize(limits.max_bytes)} po fotografiji.`}
      </Text>

      <Button variant="secondary" onPress={() => void pick()} loading={picking}>
        Dodaj fotografije ({images.length})
      </Button>

      {images.length === 0 ? (
        <Card muted>
          <Text style={styles.help}>Nema izabranih fotografija.</Text>
        </Card>
      ) : null}

      {images.map((image, index) => (
        <Card key={imageKey(image)} style={styles.imageCard}>
          <View style={styles.previewFrame}>
            <Image
              accessibilityIgnoresInvertColors
              source={{ uri: image.uri }}
              contentFit="contain"
              cachePolicy="memory-disk"
              style={[
                styles.preview,
                { transform: [{ rotate: `${image.rotation_degrees}deg` }] },
              ]}
            />
          </View>

          <View style={styles.imageCopy}>
            <Text style={styles.imageName}>{image.name}</Text>
            <Text style={styles.help}>
              {image.type} · {formatProductImageSize(image.size)} · rotacija {image.rotation_degrees}°
            </Text>
            {index === 0 ? <Text style={styles.primaryLabel}>GLAVNA FOTOGRAFIJA</Text> : null}
          </View>

          <View style={styles.actionGrid}>
            {index !== 0 ? (
              <Button variant="secondary" onPress={() => onChange(moveItem(images, index, 0))}>
                Postavi kao glavnu
              </Button>
            ) : null}
            <Button
              variant="ghost"
              disabled={index <= 1 && images[0] !== undefined}
              onPress={() => onChange(moveItem(images, index, index - 1))}
            >
              Pomeri gore
            </Button>
            <Button
              variant="ghost"
              disabled={index === 0 || index >= images.length - 1}
              onPress={() => onChange(moveItem(images, index, index + 1))}
            >
              Pomeri dole
            </Button>
            <Button
              variant="ghost"
              onPress={() => onChange(images.map((item, itemIndex) => (
                itemIndex === index
                  ? { ...item, rotation_degrees: nextRotation(item.rotation_degrees) }
                  : item
              )))}
            >
              Rotiraj 90°
            </Button>
            <Button
              variant="danger"
              onPress={() => onChange(images.filter((_, itemIndex) => itemIndex !== index))}
            >
              Ukloni
            </Button>
          </View>
        </Card>
      ))}
    </View>
  );
}

type RemoteAction =
  | { kind: 'primary'; imageId: number }
  | { kind: 'rotate'; imageId: number }
  | { kind: 'reorder'; imageIds: number[] }
  | { kind: 'delete'; imageId: number };

export function RemoteProductImageManager({
  productId,
  limits,
  enabled,
  readOnly = false,
  onChanged,
}: {
  productId: number;
  limits: AdminProductImageLimits;
  enabled: boolean;
  readOnly?: boolean;
  onChanged?: () => void | Promise<void>;
}) {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const [drafts, setDrafts] = useState<DraftProductImage[]> ([]);
  const [deleteTarget, setDeleteTarget] = useState<AdminProductManagedImage | null> (null);

  const queryKey = ['admin', 'catalog', 'product-images', productId] as const;
  const query = useQuery({
    queryKey,
    queryFn: () => apiAdminCatalog.images(productId),
    enabled,
  });

  const refresh = async () => {
    await client.invalidateQueries({ queryKey: ['admin', 'catalog', 'product-images', productId] });
    if (onChanged) await onChanged();
  };

  const mutation = useMutation({
    mutationFn: async (action: RemoteAction) => {
      if (action.kind === 'primary') return apiAdminCatalog.setPrimaryImage(productId, action.imageId);
      if (action.kind === 'rotate') return apiAdminCatalog.rotateImage(productId, action.imageId, 90);
      if (action.kind === 'reorder') return apiAdminCatalog.reorderImages(productId, action.imageIds);
      return apiAdminCatalog.deleteImage(productId, action.imageId);
    },
    onSuccess: async (response) => {
      client.setQueryData(queryKey, response);
      await refresh();
      feedback.notify({
        tone: 'success',
        title: 'Fotografije su ažurirane',
        message: response.message,
      });
    },
    onError: (error) => {
      feedback.notify({
        tone: 'danger',
        title: 'Izmena fotografije nije uspela',
        message: apiMessage(error, 'Pokušaj ponovo.'),
      });
    },
  });

  const uploadMutation = useMutation({
    mutationFn: async (items: DraftProductImage[]) => {
      const uploaded = await api.admin.catalog.uploadProductImages(productId, items);
      const skipped = new Set(uploaded.skipped_input_indexes);
      if (uploaded.uploaded_images.length !== items.length - skipped.size) {
        throw new Error('Server nije vratio očekivani plan novih fotografija.');
      }
      let managedIndex = 0;
      for (let index = 0; index < items.length; index += 1) {
        if (skipped.has(index)) continue;
        const draft = items[index];
        const managed = uploaded.uploaded_images[managedIndex];
        managedIndex += 1;
        if (!draft || !managed || draft.rotation_degrees === 0) continue;
        await apiAdminCatalog.rotateImage(productId, managed.id, draft.rotation_degrees);
      }
      return uploaded;
    },
    onSuccess: async (response) => {
      setDrafts([]);
      await refresh();
      feedback.notify({
        tone: 'success',
        title: response.uploaded_count > 0 ? 'Fotografije su dodate' : 'Nema novih fotografija',
        message: response.skipped_duplicate_count > 0
          ? `Preskočeno duplikata: ${response.skipped_duplicate_count}.`
          : response.message,
      });
    },
    onError: (error) => {
      feedback.notify({
        tone: 'danger',
        title: 'Slanje fotografija nije uspelo',
        message: apiMessage(error, 'Izabrane fotografije ostaju u pripremi. Pokušaj ponovo.'),
      });
    },
  });

  if (!enabled) {
    return (
      <Card muted>
        <Text style={styles.help}>Nemaš dozvolu za administratorsko upravljanje fotografijama.</Text>
      </Card>
    );
  }

  if (query.isLoading) {
    return (
      <Card muted>
        <Text style={styles.help}>Učitavanje fotografija…</Text>
      </Card>
    );
  }

  if (query.isError || !query.data) {
    return (
      <Card style={styles.imageCard}>
        <Text style={styles.errorText}>{apiMessage(query.error, 'Fotografije trenutno nisu dostupne.')}</Text>
        <Button variant="ghost" onPress={() => void query.refetch()}>Pokušaj ponovo</Button>
      </Card>
    );
  }

  const managed = query.data.data;
  const firstIsPrimary = managed[0]?.is_primary === true;
  const minimumMovableIndex = firstIsPrimary ? 1 : 0;

  return (
    <View style={styles.manager}>
      <Text style={styles.help}>
        Ukupno fotografija: {managed.length}. Glavna fotografija je uvek prva. Rotacija menja stvarni fajl na serveru.
      </Text>

      {managed.length === 0 ? (
        <Card muted>
          <Text style={styles.help}>Artikal trenutno nema fotografije.</Text>
        </Card>
      ) : null}

      {managed.map((image, index) => (
        <Card key={image.id} style={styles.imageCard}>
          <View style={styles.previewFrame}>
            {(image.thumbnail_url ?? image.display_url ?? image.original_url ?? image.url) ? (
              <Image
                accessibilityIgnoresInvertColors
                source={{ uri: image.thumbnail_url ?? image.display_url ?? image.original_url ?? image.url ?? undefined }}
                contentFit="contain"
                cachePolicy="memory-disk"
                transition={80}
                recyclingKey={String(image.id)}
                style={styles.preview}
              />
            ) : (
              <View style={styles.previewMissing}>
                <Text style={styles.help}>Preview nije dostupan</Text>
              </View>
            )}
          </View>

          <View style={styles.imageCopy}>
            <Text style={styles.imageName}>{image.original_filename ?? `Slika #${image.id}`}</Text>
            <Text style={styles.help}>
              {image.mime_type ?? 'nepoznat format'}
              {image.file_size !== null ? ` · ${formatProductImageSize(image.file_size)}` : ''}
              {` · ${image.storage_disk}`}
            </Text>
            {image.is_primary ? <Text style={styles.primaryLabel}>GLAVNA FOTOGRAFIJA</Text> : null}
            {!image.can_delete ? (
              <Text style={styles.legacyNote}>
                Legacy fotografija je read-only za brisanje. Rotiraj je jednom da backend napravi lokalnu copy-on-write kopiju.
              </Text>
            ) : null}
          </View>

          {!readOnly ? (
            <View style={styles.actionGrid}>
              {!image.is_primary ? (
                <Button
                  variant="secondary"
                  onPress={() => mutation.mutate({ kind: 'primary', imageId: image.id })}
                >
                  Postavi kao glavnu
                </Button>
              ) : null}
              <Button
                variant="ghost"
                disabled={index <= minimumMovableIndex}
                onPress={() => mutation.mutate({
                  kind: 'reorder',
                  imageIds: moveItem(managed, index, index - 1).map((item) => item.id),
                })}
              >
                Pomeri gore
              </Button>
              <Button
                variant="ghost"
                disabled={image.is_primary || index >= managed.length - 1}
                onPress={() => mutation.mutate({
                  kind: 'reorder',
                  imageIds: moveItem(managed, index, index + 1).map((item) => item.id),
                })}
              >
                Pomeri dole
              </Button>
              <Button
                variant="ghost"
                onPress={() => mutation.mutate({ kind: 'rotate', imageId: image.id })}
              >
                Rotiraj 90°
              </Button>
              <Button
                variant="danger"
                disabled={!image.can_delete}
                onPress={() => setDeleteTarget(image)}
              >
                Obriši sliku
              </Button>
            </View>
          ) : null}
        </Card>
      ))}

      {!readOnly ? (
        <View style={styles.uploadSection}>
          <Text style={styles.sectionTitle}>Dodaj nove fotografije</Text>
          <DraftProductImageManager
            images={drafts}
            onChange={setDrafts}
            limits={limits}
            enabled
            helper="Pre slanja možeš pregledati, promeniti redosled i rotirati nove fotografije. Server sprečava duplo slanje identičnog fajla po sadržaju."
          />
          {drafts.length > 0 ? (
            <Button
              onPress={() => uploadMutation.mutate(drafts)}
              loading={uploadMutation.isPending}
            >
              Otpremi izabrane fotografije
            </Button>
          ) : null}
        </View>
      ) : (
        <Card muted>
          <Text style={styles.help}>Arhivirani artikal ima read-only pregled fotografija dok ne opozoveš arhiviranje.</Text>
        </Card>
      )}

      <ConfirmAction
        visible={deleteTarget !== null}
        title="Obriši fotografiju?"
        message={deleteTarget?.original_filename ?? 'Izabrana fotografija biće trajno uklonjena sa artikla.'}
        confirmLabel="Obriši"
        destructive
        onCancel={() => setDeleteTarget(null)}
        onConfirm={() => {
          const target = deleteTarget;
          setDeleteTarget(null);
          if (target) mutation.mutate({ kind: 'delete', imageId: target.id });
        }}
      />
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    manager: { gap: spacing.md },
    help: { ...typography.small, color: theme.muted },
    sectionTitle: { ...typography.h3, color: theme.ink },
    imageCard: { gap: spacing.md },
    previewFrame: {
      minHeight: 220,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: theme.line,
      borderRadius: 18,
      backgroundColor: theme.surfaceContainer,
      overflow: 'hidden',
      alignItems: 'center',
      justifyContent: 'center',
    },
    preview: { width: '100%', height: 220 },
    previewMissing: { minHeight: 220, alignItems: 'center', justifyContent: 'center' },
    imageCopy: { gap: spacing.xs },
    imageName: { ...typography.label, color: theme.ink },
    primaryLabel: { ...typography.small, color: theme.primary, fontWeight: '900' },
    legacyNote: { ...typography.small, color: theme.warning },
    errorText: { ...typography.small, color: theme.danger },
    actionGrid: { gap: spacing.sm },
    uploadSection: { gap: spacing.md, paddingTop: spacing.md },
  });
}
