import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { StyleSheet, Text, View } from 'react-native';

import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmAction } from '@/components/ui/confirm-action';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminCatalog,
  type AdminCatalogProductPurgeInput,
  type AdminCatalogProductTotalPurgeInput,
} from '@/features/admin/catalog-admin-api';
import { ApiError } from '@/lib/api/client';
import { useAppTheme } from '@/theme/app-theme';

type ConfirmationMode = 'purge' | 'total' | null;

type ReadinessItem = {
  label: string;
  ready: boolean;
};

function apiMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return fallback;
}

// MOBILE_BATCH174_TOTAL_PRODUCT_PURGE_UX_HARDENING
export function ProductDeletionAdmin({
  productId,
  onDeleted,
}: {
  productId: number;
  onDeleted: () => void | Promise<void>;
}) {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const [confirmation, setConfirmation] = useState('');
  const [deleteImagesOverride, setDeleteImagesOverride] = useState<boolean | null> (null);
  const [totalPurgeUnlocked, setTotalPurgeUnlocked] = useState(false);
  const [totalReason, setTotalReason] = useState('');
  const [totalConfirmation, setTotalConfirmation] = useState('');
  const [totalIrreversible, setTotalIrreversible] = useState('');
  const [retentionAcknowledged, setRetentionAcknowledged] = useState(false);
  const [confirmationMode, setConfirmationMode] = useState<ConfirmationMode> (null);

  useEffect(() => {
    setConfirmation('');
    setDeleteImagesOverride(null);
    setTotalPurgeUnlocked(false);
    setTotalReason('');
    setTotalConfirmation('');
    setTotalIrreversible('');
    setRetentionAcknowledged(false);
    setConfirmationMode(null);
  }, [productId]);

  const deletionQuery = useQuery({
    queryKey: ['admin', 'catalog', 'product', productId, 'deletion'],
    queryFn: () => apiAdminCatalog.deletion(productId),
  });

  const purgeMutation = useMutation({
    mutationFn: (input: AdminCatalogProductPurgeInput) =>
      apiAdminCatalog.purge(productId, input),
    onSuccess: async (response) => {
      feedback.notify({
        tone: 'success',
        title: 'Artikal je trajno obrisan',
        message: response.message,
      });
      await onDeleted();
    },
    onError: (error) => feedback.notify({
      tone: 'danger',
      title: 'Trajno brisanje nije izvršeno',
      message: apiMessage(error, 'Server je odbio trajno brisanje artikla.'),
    }),
  });

  const totalPurgeMutation = useMutation({
    mutationFn: (input: AdminCatalogProductTotalPurgeInput) =>
      apiAdminCatalog.totalPurge(productId, input),
    onSuccess: async (response) => {
      feedback.notify({
        tone: 'success',
        title: 'Total Product Purge je završen',
        message: response.message,
      });
      await onDeleted();
    },
    onError: (error) => feedback.notify({
      tone: 'danger',
      title: 'Total Product Purge nije izvršen',
      message: apiMessage(error, 'Server je odbio Total Product Purge.'),
    }),
  });

  if (deletionQuery.isLoading) {
    return (
      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Opasna zona</Text>
        <Text style={styles.help}>Provera uslova za trajno brisanje…</Text>
      </Card>
    );
  }

  if (deletionQuery.isError || !deletionQuery.data) {
    return (
      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Opasna zona</Text>
        <Text style={styles.dangerText}>{apiMessage(deletionQuery.error, 'Uslove za trajno brisanje nije moguće učitati.')}</Text>
        <Button variant="ghost" onPress={() => void deletionQuery.refetch()}>Pokušaj ponovo</Button>
      </Card>
    );
  }

  const data = deletionQuery.data;
  const blockers = Object.entries(data.blockers);
  const irreversible = data.total_purge_irreversible_confirmation;
  const deleteImages = deleteImagesOverride ?? data.delete_images_default;
  const regularPurgeReady = data.purge_available && confirmation.trim() === data.sku;
  const totalReasonLength = totalReason.trim().length;
  const totalReasonReady = totalReasonLength >= data.total_purge_reason_min_length
    && totalReasonLength <= data.total_purge_reason_max_length;
  const totalSkuReady = totalConfirmation.trim() === data.sku;
  const totalIrreversibleReady = Boolean(
    irreversible && totalIrreversible.trim() === irreversible,
  );
  const canRequestTotalPurge = data.total_purge_available
    && totalPurgeUnlocked
    && totalReasonReady
    && totalSkuReady
    && totalIrreversibleReady
    && retentionAcknowledged;
  const totalReadiness: ReadinessItem[] = [
    {
      label: `Razlog ima ${data.total_purge_reason_min_length}–${data.total_purge_reason_max_length} karaktera`,
      ready: totalReasonReady,
    },
    { label: `SKU potvrda je tačno ${data.sku}`, ready: totalSkuReady },
    { label: 'Nepovratna fraza je uneta tačno', ready: totalIrreversibleReady },
    { label: 'Retention granica je prihvaćena', ready: retentionAcknowledged },
  ];

  const requestPurge = () => {
    if (!data.purge_available) {
      feedback.notify({
        tone: 'warning',
        title: 'Trajno brisanje nije dostupno',
        message: 'Server je prijavio poslovne zavisnosti. Koristi arhiviranje ili SuperAdmin Total Product Purge kada je taj tok dostupan.',
      });
      return;
    }
    if (confirmation.trim() !== data.sku) {
      feedback.notify({
        tone: 'warning',
        title: 'SKU potvrda nije tačna',
        message: `Upiši tačno ${data.sku} da bi server prihvatio trajno brisanje.`,
      });
      return;
    }

    setConfirmationMode('purge');
  };

  const executePurge = () => {
    setConfirmationMode(null);
    purgeMutation.mutate({
      confirmation: confirmation.trim(),
      delete_images: deleteImages,
    });
  };

  const unlockTotalPurge = () => {
    setTotalReason('');
    setTotalConfirmation('');
    setTotalIrreversible('');
    setRetentionAcknowledged(false);
    setConfirmationMode(null);
    setTotalPurgeUnlocked(true);
  };

  const lockTotalPurge = () => {
    setTotalPurgeUnlocked(false);
    setTotalReason('');
    setTotalConfirmation('');
    setTotalIrreversible('');
    setRetentionAcknowledged(false);
    setConfirmationMode(null);
  };

  const requestTotalPurge = () => {
    if (!totalPurgeUnlocked) {
      feedback.notify({
        tone: 'warning',
        title: 'Total Product Purge je zaključan',
        message: 'Prvo eksplicitno otključaj nepovratnu operaciju.',
      });
      return;
    }
    if (!totalReasonReady) {
      feedback.notify({
        tone: 'warning',
        title: 'Razlog nije validan',
        message: `Unesi između ${data.total_purge_reason_min_length} i ${data.total_purge_reason_max_length} karaktera.`,
      });
      return;
    }
    if (!totalSkuReady) {
      feedback.notify({ tone: 'warning', title: 'Prva potvrda nije tačna', message: `Upiši tačan SKU: ${data.sku}.` });
      return;
    }
    if (!totalIrreversibleReady) {
      feedback.notify({ tone: 'warning', title: 'Nepovratna potvrda nije tačna', message: 'Upiši tačno serverom zadatu nepovratnu frazu.' });
      return;
    }
    if (!retentionAcknowledged) {
      feedback.notify({ tone: 'warning', title: 'Retention granica nije potvrđena', message: data.retention_notice });
      return;
    }

    setConfirmationMode('total');
  };

  const executeTotalPurge = () => {
    setConfirmationMode(null);
    totalPurgeMutation.mutate({
      total_confirmation: totalConfirmation.trim(),
      total_reason: totalReason.trim(),
      total_irreversible_confirmation: totalIrreversible.trim(),
      total_retention_acknowledged: true,
    });
  };

  return (
    <Card style={styles.card}>
      <Text style={styles.sectionTitle}>Opasna zona</Text>
      <Text style={styles.help}>
        Arhiviranje je reverzibilno. Trajno brisanje i Total Product Purge nisu normalno reverzibilni, a server uvek ponovo proverava poslovne zavisnosti pre izvršenja.
      </Text>

      <View style={styles.subsection}>
        <Text style={styles.subTitle}>Trajno obriši artikal</Text>
        {!data.purge_available ? (
          <View style={styles.blockers}>
            <Text style={styles.dangerText}>Obično trajno brisanje trenutno nije dozvoljeno.</Text>
            {blockers.length > 0 ? blockers.map(([label, count]) => (
              <Text key={label} style={styles.help}>• {label}: {count}</Text>
            )) : (
              <Text style={styles.help}>Server nije odobrio purge za trenutno stanje artikla.</Text>
            )}
            <Text style={styles.help}>Arhiviranje čuva istorijske dokumente i veze.</Text>
          </View>
        ) : (
          <>
            <Text style={styles.help}>
              Ovaj tok je namenjen artiklu bez blokirajuće poslovne istorije. Za finalnu potvrdu moraš ručno uneti tačan SKU.
            </Text>
            <TextField
              label={`Za potvrdu upiši SKU: ${data.sku}`}
              value={confirmation}
              onChangeText={setConfirmation}
              maxLength={100}
              autoCapitalize="characters"
              autoCorrect={false}
            />
            <Text style={regularPurgeReady ? styles.readyText : styles.pendingText}>
              {regularPurgeReady
                ? 'SKU je potvrđen. Finalna potvrda je dostupna.'
                : 'Finalna potvrda ostaje zaključana dok SKU nije unet tačno.'}
            </Text>
            <Button
              variant={deleteImages ? 'secondary' : 'ghost'}
              onPress={() => setDeleteImagesOverride(!deleteImages)}
            >
              {deleteImages ? 'Lokalne slike: OBRIŠI' : 'Lokalne slike: OSTAVI'}
            </Button>
            <Text style={styles.help}>
              Početna vrednost dolazi sa servera. Legacy read-only slike se ne brišu na izvornom serveru; njihovi CMS zapisi se uklanjaju.
            </Text>
            <Button
              variant="danger"
              disabled={!regularPurgeReady}
              loading={purgeMutation.isPending}
              onPress={requestPurge}
            >
              Trajno obriši artikal
            </Button>
          </>
        )}
      </View>

      {data.total_purge_available && irreversible ? (
        <View style={styles.totalPurge}>
          <Text style={styles.subTitle}>Total Product Purge — SuperAdmin</Text>
          <Text style={styles.dangerText}>
            Izuzetno destruktivna akcija. ZERO TRACE uklanja live identitet artikla, slike i specifikacije; poslovna istorija ostaje kao događaj, ali se veza i identitet izabranog artikla uklanjaju ili rediguju.
          </Text>
          <Text style={styles.help}>
            Audit, notification, async i data-quality tragovi se brišu ili rediguju, a server posle commita proverava database i filesystem ZERO TRACE stanje.
          </Text>

          <View style={styles.targetBox}>
            <Text style={styles.targetLabel}>Cilj nepovratne operacije</Text>
            <Text style={styles.targetName}>{data.name}</Text>
            <Text style={styles.targetSku}>SKU: {data.sku}</Text>
          </View>

          <View style={styles.retentionBox}>
            <Text style={styles.subTitle}>Retention granica</Text>
            <Text style={styles.help}>{data.retention_notice}</Text>
          </View>

          {!totalPurgeUnlocked ? (
            <View style={styles.lockedWorkspace}>
              <Text style={styles.help}>
                Forma je zaključana po defaultu. Otključavanje samo prikazuje polja za proveru; ništa se ne briše bez kompletnog readiness-a i još jedne finalne potvrde.
              </Text>
              <Button variant="secondary" onPress={unlockTotalPurge}>
                Otključaj Total Product Purge
              </Button>
            </View>
          ) : (
            <View style={styles.unlockedWorkspace}>
              <Text style={styles.subTitle}>Provera pre finalne potvrde</Text>
              <TextField
                label="Razlog Total Product Purge operacije"
                value={totalReason}
                onChangeText={setTotalReason}
                maxLength={data.total_purge_reason_max_length}
                multiline
              />
              <Text style={totalReasonReady ? styles.readyText : styles.pendingText}>
                Razlog: {totalReasonLength} karaktera · minimum {data.total_purge_reason_min_length}, maksimum {data.total_purge_reason_max_length}.
              </Text>
              <TextField
                label={`1. potvrda — tačan SKU: ${data.sku}`}
                value={totalConfirmation}
                onChangeText={setTotalConfirmation}
                maxLength={100}
                autoCapitalize="characters"
                autoCorrect={false}
              />
              <TextField
                label={`2. nepovratna potvrda — upiši tačno: ${irreversible}`}
                value={totalIrreversible}
                onChangeText={setTotalIrreversible}
                maxLength={100}
                autoCorrect={false}
              />

              <View style={styles.readinessList}>
                {totalReadiness.map((item) => (
                  <View key={item.label} style={styles.readinessRow}>
                    <Text style={styles.readinessLabel}>{item.label}</Text>
                    <Text style={item.ready ? styles.readyText : styles.pendingText}>
                      {item.ready ? 'SPREMNO' : 'POTREBNO'}
                    </Text>
                  </View>
                ))}
              </View>

              <Button
                variant={retentionAcknowledged ? 'secondary' : 'ghost'}
                onPress={() => setRetentionAcknowledged((current) => !current)}
              >
                {retentionAcknowledged
                  ? 'Retention granica: PRIHVAĆENA'
                  : 'Razumem i prihvatam retention granicu'}
              </Button>
              <Button variant="ghost" onPress={lockTotalPurge}>
                Zaključaj i očisti potvrde
              </Button>
              <Button
                variant="danger"
                disabled={!canRequestTotalPurge}
                loading={totalPurgeMutation.isPending}
                onPress={requestTotalPurge}
              >
                Nastavi na finalnu Total Product Purge potvrdu
              </Button>
            </View>
          )}
        </View>
      ) : null}

      <ConfirmAction
        visible={confirmationMode === 'purge'}
        title="Potvrdi trajno brisanje"
        message={`Trajno obrisati artikal ${data.sku}? Ovu radnju nije moguće poništiti.`}
        confirmLabel="Trajno obriši artikal"
        destructive
        onCancel={() => setConfirmationMode(null)}
        onConfirm={executePurge}
      />
      <ConfirmAction
        visible={confirmationMode === 'total'}
        title="Potvrdi Total Product Purge"
        message={`POZOR: Total Product Purge za ${data.name} (${data.sku}) nema normalan restore. Nastavi samo ako želiš uklanjanje svih live tragova identiteta ovog artikla.`}
        confirmLabel="Trajno obriši SVE live tragove"
        destructive
        onCancel={() => setConfirmationMode(null)}
        onConfirm={executeTotalPurge}
      />
    </Card>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    card: { gap: spacing.lg },
    subsection: { gap: spacing.md },
    totalPurge: { gap: spacing.md, borderTopWidth: 1, borderTopColor: theme.danger, paddingTop: spacing.lg },
    blockers: { gap: spacing.xs },
    targetBox: { gap: spacing.xs, borderLeftWidth: 3, borderLeftColor: theme.danger, paddingLeft: spacing.md },
    retentionBox: { gap: spacing.xs },
    lockedWorkspace: { gap: spacing.md },
    unlockedWorkspace: { gap: spacing.md },
    readinessList: { gap: spacing.sm },
    readinessRow: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    readinessLabel: { ...typography.body, color: theme.ink, flex: 1 },
    sectionTitle: { ...typography.h3, color: theme.danger },
    subTitle: { ...typography.label, color: theme.ink, fontWeight: '800' },
    help: { ...typography.body, color: theme.muted },
    dangerText: { ...typography.body, color: theme.danger, fontWeight: '700' },
    targetLabel: { ...typography.small, color: theme.danger, fontWeight: '800' },
    targetName: { ...typography.h3, color: theme.ink },
    targetSku: { ...typography.label, color: theme.muted },
    readyText: { ...typography.small, color: theme.primary, fontWeight: '800' },
    pendingText: { ...typography.small, color: theme.muted, fontWeight: '700' },
  });
}
