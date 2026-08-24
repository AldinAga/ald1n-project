import { useMemo, useState } from 'react';
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

function apiMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return fallback;
}

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
  const [deleteImages, setDeleteImages] = useState(true);
  const [totalReason, setTotalReason] = useState('');
  const [totalConfirmation, setTotalConfirmation] = useState('');
  const [totalIrreversible, setTotalIrreversible] = useState('');
  const [retentionAcknowledged, setRetentionAcknowledged] = useState(false);
  const [confirmationMode, setConfirmationMode] = useState<ConfirmationMode> (null);

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

  const requestPurge = () => {
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

  const requestTotalPurge = () => {
    if (totalReason.trim().length < data.total_purge_reason_min_length) {
      feedback.notify({
        tone: 'warning',
        title: 'Razlog je prekratak',
        message: `Unesi najmanje ${data.total_purge_reason_min_length} karaktera.`,
      });
      return;
    }
    if (totalConfirmation.trim() !== data.sku) {
      feedback.notify({ tone: 'warning', title: 'Prva potvrda nije tačna', message: `Upiši tačan SKU: ${data.sku}.` });
      return;
    }
    if (!irreversible || totalIrreversible.trim() !== irreversible) {
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
        Arhiviranje je reverzibilno. Trajno brisanje i Total Product Purge nisu normalno reverzibilni i server uvek ponovo proverava poslovne zavisnosti.
      </Text>

      <View style={styles.subsection}>
        <Text style={styles.subTitle}>Trajno obriši artikal</Text>
        {blockers.length > 0 ? (
          <View style={styles.blockers}>
            <Text style={styles.dangerText}>Obično trajno brisanje je blokirano zbog poslovne istorije:</Text>
            {blockers.map(([label, count]) => (
              <Text key={label} style={styles.help}>• {label}: {count}</Text>
            ))}
            <Text style={styles.help}>Arhiviranje čuva istorijske dokumente i veze.</Text>
          </View>
        ) : (
          <>
            <TextField
              label={`Za potvrdu upiši SKU: ${data.sku}`}
              value={confirmation}
              onChangeText={setConfirmation}
              maxLength={100}
              autoCapitalize="characters"
              autoCorrect={false}
            />
            <Button
              variant={deleteImages ? 'secondary' : 'ghost'}
              onPress={() => setDeleteImages((current) => !current)}
            >
              {deleteImages ? 'Lokalne slike: OBRIŠI' : 'Lokalne slike: OSTAVI'}
            </Button>
            <Text style={styles.help}>Legacy read-only slike se ne brišu na izvornom serveru; njihovi CMS zapisi se uklanjaju.</Text>
            <Button variant="danger" loading={purgeMutation.isPending} onPress={requestPurge}>
              Trajno obriši artikal
            </Button>
          </>
        )}
      </View>

      {data.total_purge_available && irreversible ? (
        <View style={styles.totalPurge}>
          <Text style={styles.subTitle}>Total Product Purge — SuperAdmin</Text>
          <Text style={styles.dangerText}>
            ZERO TRACE uklanja live identitet artikla, rediguje poslovnu istoriju i nema normalan restore.
          </Text>
          <Text style={styles.help}>{data.retention_notice}</Text>
          <TextField
            label="Razlog Total Product Purge operacije"
            value={totalReason}
            onChangeText={setTotalReason}
            maxLength={data.total_purge_reason_max_length}
            multiline
          />
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
          <Button
            variant={retentionAcknowledged ? 'secondary' : 'ghost'}
            onPress={() => setRetentionAcknowledged((current) => !current)}
          >
            {retentionAcknowledged ? 'Retention granica: POTVRĐENA' : 'Potvrdi retention granicu'}
          </Button>
          <Button variant="danger" loading={totalPurgeMutation.isPending} onPress={requestTotalPurge}>
            Trajno obriši SVE live tragove artikla
          </Button>
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
        message={`POZOR: Total Product Purge za ${data.sku} nema normalan restore. Nastavi samo ako želiš uklanjanje svih live tragova artikla.`}
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
    sectionTitle: { ...typography.h3, color: theme.danger },
    subTitle: { ...typography.label, color: theme.ink, fontWeight: '800' },
    help: { ...typography.body, color: theme.muted },
    dangerText: { ...typography.body, color: theme.danger, fontWeight: '700' },
  });
}
