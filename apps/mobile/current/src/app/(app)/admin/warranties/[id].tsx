import { useEffect, useMemo, useState } from 'react';
import {
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';
import {
  router,
  useLocalSearchParams,
} from 'expo-router';
import {
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmAction } from '@/components/ui/confirm-action';
import { DateTimeField } from '@/components/ui/date-time-field';
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
import {
  ErrorState,
  LoadingState,
  UnavailableState,
} from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminWarranties,
  type AdminWarranty,
  type AdminWarrantyCompleteInput,
  type AdminWarrantyMaintenanceRecord,
  type AdminWarrantyScheduleInput,
  type AdminWarrantyUpdateInput,
} from '@/features/admin/warranties-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { openAdminWarrantyPdf } from '@/features/warranties/warranty-pdf';
import { formatDate } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';

// MOBILE_V1_0_ADMIN_WARRANTY_DETAIL_UX_REORGANIZATION_BATCH85
type WarrantyWorkspace = 'overview' | 'details' | 'maintenance' | 'document' | 'void';
type MaintenanceMode = 'schedule' | 'complete';

const WARRANTY_WORKSPACE_OPTIONS: Array<{ value: WarrantyWorkspace; label: string; description: string }> = [
  { value: 'overview', label: 'Pregled', description: 'Status garancije, kupac, artikal i najvažniji rokovi na jednom mestu.' },
  { value: 'details', label: 'Podaci', description: 'Serijski brojevi, period važenja i uslovi garantnog lista.' },
  { value: 'maintenance', label: 'Održavanje', description: 'Preventivni termini, servisne reference i završeni maintenance zapisi.' },
  { value: 'document', label: 'Dokument', description: 'Garantni PDF i read-only snapshot ključnih podataka dokumenta.' },
  { value: 'void', label: 'Poništavanje', description: 'Audit status i kontrolisana danger-zone akcija poništavanja garancije.' },
];

function errorMessage(
  error: unknown,
  fallback: string,
): string {
  if (error instanceof Error && error.message.trim()) {
    return error.message;
  }

  return fallback;
}

function initialDateTime(): string {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');

  return `${year}-${month}-${day} ${hours}:${minutes}`;
}

export default function AdminWarrantyDetailScreen() {
  const routeParams = useLocalSearchParams();
  const rawId = Array.isArray(routeParams.id)
    ? routeParams.id[0]
    : routeParams.id;
  const warrantyId = Number(rawId);
  const validId =
    Number.isInteger(warrantyId)
    && warrantyId > 0;

  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const allowed = can('warranties.manage');
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const [workspace, setWorkspace] = useState<WarrantyWorkspace> ('overview');
  const [openingPdf, setOpeningPdf] = useState(false);

  const openPdf = async (warrantyId: number, warrantyNumber: string) => {
    if (openingPdf) {
      return;
    }

    setOpeningPdf(true);

    try {
      await openAdminWarrantyPdf(
        warrantyId,
        warrantyNumber,
      );
    } catch (error) {
      feedback.notify({
        tone: 'danger',
        title: 'PDF nije moguće otvoriti',
        message: errorMessage(
          error,
          'Pokušaj ponovo za nekoliko trenutaka.',
        ),
      });
    } finally {
      setOpeningPdf(false);
    }
  };

  const [startsAt, setStartsAt] = useState('');
  const [expiresAt, setExpiresAt] = useState('');
  const [serialNumbers, setSerialNumbers] = useState('');
  const [terms, setTerms] = useState('');

  const [voidReason, setVoidReason] = useState('');
  const [confirmVoid, setConfirmVoid] = useState(false);

  const [maintenanceRecordId, setMaintenanceRecordId] =
    useState<number | null> (null);
  const [maintenanceMode, setMaintenanceMode] =
    useState<MaintenanceMode | null> (null);
  const [maintenanceDateTime, setMaintenanceDateTime] =
    useState('');
  const [serviceReference, setServiceReference] =
    useState('');
  const [maintenanceResult, setMaintenanceResult] =
    useState('');
  const [maintenanceNotes, setMaintenanceNotes] =
    useState('');

  const query = useQuery({
    queryKey: adminQueryKeys.warranty(warrantyId),
    queryFn: () => apiAdminWarranties.detail(warrantyId),
    enabled: allowed && validId,
  });

  useEffect(() => {
    const warranty = query.data;

    if (!warranty) {
      return;
    }

    setStartsAt(warranty.starts_at ?? '');
    setExpiresAt(warranty.expires_at ?? '');
    setSerialNumbers(warranty.serial_numbers.join('\n'));
    setTerms(warranty.terms ?? '');
  }, [query.data]);

  const syncWarranty = async (
    updated: AdminWarranty,
    message: string,
  ) => {
    client.setQueryData(
      adminQueryKeys.warranty(warrantyId),
      updated,
    );

    await client.invalidateQueries({
      queryKey: adminQueryKeys.warranties(),
    });

    feedback.notify({
      tone: 'success',
      title: 'Garancija je ažurirana',
      message,
    });
  };

  const updateMutation = useMutation({
    mutationFn: (input: AdminWarrantyUpdateInput) =>
      apiAdminWarranties.update(warrantyId, input),

    onSuccess: async (updated) => {
      await syncWarranty(
        updated,
        'Podaci garantnog lista su sačuvani.',
      );
    },

    onError: (error) => {
      feedback.notify({
        tone: 'danger',
        title: 'Izmena nije uspela',
        message: errorMessage(
          error,
          'Proveri podatke i pokušaj ponovo.',
        ),
      });
    },
  });

  const voidMutation = useMutation({
    mutationFn: (reason: string) =>
      apiAdminWarranties.void(
        warrantyId,
        { reason },
      ),

    onSuccess: async (updated) => {
      setVoidReason('');
      setConfirmVoid(false);

      await syncWarranty(
        updated,
        'Garancija je poništena uz audit trag.',
      );
    },

    onError: (error) => {
      feedback.notify({
        tone: 'danger',
        title: 'Poništavanje nije uspelo',
        message: errorMessage(
          error,
          'Proveri razlog i pokušaj ponovo.',
        ),
      });
    },
  });

  const maintenanceMutation = useMutation({
    mutationFn: async ({
      recordId,
      mode,
      scheduleInput,
      completeInput,
    }: {
      recordId: number;
      mode: MaintenanceMode;
      scheduleInput?: AdminWarrantyScheduleInput;
      completeInput?: AdminWarrantyCompleteInput;
    }) => {
      if (mode === 'schedule') {
        if (!scheduleInput) {
          throw new Error('Nedostaje termin održavanja.');
        }

        return apiAdminWarranties.scheduleMaintenance(
          warrantyId,
          recordId,
          scheduleInput,
        );
      }

      if (!completeInput) {
        throw new Error('Nedostaju podaci o završetku.');
      }

      return apiAdminWarranties.completeMaintenance(
        warrantyId,
        recordId,
        completeInput,
      );
    },

    onSuccess: async (updated) => {
      closeMaintenanceForm();

      await syncWarranty(
        updated,
        'Evidencija održavanja je ažurirana.',
      );
    },

    onError: (error) => {
      feedback.notify({
        tone: 'danger',
        title: 'Održavanje nije ažurirano',
        message: errorMessage(
          error,
          'Proveri podatke i pokušaj ponovo.',
        ),
      });
    },
  });

  const closeMaintenanceForm = () => {
    setMaintenanceRecordId(null);
    setMaintenanceMode(null);
    setMaintenanceDateTime('');
    setServiceReference('');
    setMaintenanceResult('');
    setMaintenanceNotes('');
  };

  const openMaintenanceForm = (
    record: AdminWarrantyMaintenanceRecord,
    mode: MaintenanceMode,
  ) => {
    setMaintenanceRecordId(record.id);
    setMaintenanceMode(mode);

    if (mode === 'schedule') {
      const existing = record.scheduled_at
        ? record.scheduled_at.slice(0, 16).replace('T', ' ')
        : '';

      setMaintenanceDateTime(existing);
    } else {
      setMaintenanceDateTime(initialDateTime());
    }

    setServiceReference(record.service_reference ?? '');
    setMaintenanceResult(record.result ?? '');
    setMaintenanceNotes(record.notes ?? '');
  };

  const submitUpdate = () => {
    if (!startsAt || !expiresAt) {
      feedback.notify({
        tone: 'danger',
        title: 'Rokovi su obavezni',
        message: 'Unesi datum početka i datum isteka garancije.',
      });
      return;
    }

    if (expiresAt < startsAt) {
      feedback.notify({
        tone: 'danger',
        title: 'Neispravan period',
        message: 'Datum isteka ne može biti pre datuma početka.',
      });
      return;
    }

    const input: AdminWarrantyUpdateInput = {
      starts_at: startsAt,
      expires_at: expiresAt,
    };

    if (serialNumbers.trim()) {
      input.serial_numbers = serialNumbers.trim();
    }

    if (terms.trim()) {
      input.terms_snapshot = terms.trim();
    }

    updateMutation.mutate(input);
  };

  const submitMaintenance = () => {
    if (
      maintenanceRecordId === null
      || maintenanceMode === null
    ) {
      return;
    }

    if (!maintenanceDateTime.trim()) {
      feedback.notify({
        tone: 'danger',
        title: 'Datum i vreme su obavezni',
        message: 'Unesi termin održavanja.',
      });
      return;
    }

    if (
      maintenanceMode === 'complete'
      && !maintenanceResult.trim()
    ) {
      feedback.notify({
        tone: 'danger',
        title: 'Rezultat je obavezan',
        message: 'Unesi rezultat preventivnog održavanja.',
      });
      return;
    }

    if (maintenanceMode === 'schedule') {
      const input: AdminWarrantyScheduleInput = {
        scheduled_at: maintenanceDateTime.trim(),
      };

      if (serviceReference.trim()) {
        input.service_reference = serviceReference.trim();
      }

      if (maintenanceNotes.trim()) {
        input.notes = maintenanceNotes.trim();
      }

      maintenanceMutation.mutate({
        recordId: maintenanceRecordId,
        mode: 'schedule',
        scheduleInput: input,
      });

      return;
    }

    const input: AdminWarrantyCompleteInput = {
      completed_at: maintenanceDateTime.trim(),
      result: maintenanceResult.trim(),
    };

    if (serviceReference.trim()) {
      input.service_reference = serviceReference.trim();
    }

    if (maintenanceNotes.trim()) {
      input.notes = maintenanceNotes.trim();
    }

    maintenanceMutation.mutate({
      recordId: maintenanceRecordId,
      mode: 'complete',
      completeInput: input,
    });
  };

  if (!allowed) {
    return (
      <UnavailableState title="Administracija garancija nije dostupna" />
    );
  }

  if (!validId) {
    return (
      <UnavailableState title="Neispravan identifikator garancije" />
    );
  }

  if (query.isLoading) {
    return <LoadingState label="Učitavanje garancije…" />;
  }

  if (query.isError || !query.data) {
    return (
      <ErrorState
        error={query.error}
        onRetry={() => void query.refetch()}
      />
    );
  }

  const warranty = query.data;
  const workspaceMeta = WARRANTY_WORKSPACE_OPTIONS.find((option) => option.value === workspace);

  return (
    <Screen contentStyle={styles.content}>
      <Pressable
        accessibilityRole="button"
        onPress={() => router.back()}
      >
        <Text style={styles.back}>‹ Garancije</Text>
      </Pressable>

      <PageHeader
        title={warranty.warranty_number}
        eyebrow="Admin · Garantni list"
        name={bootstrap?.user.name}
      />

      <Card style={styles.workspaceCard}>
        <Text style={styles.sectionTitle}>Radni prostor garancije</Text>
        <Text style={styles.meta}>{workspaceMeta?.description ?? 'Izaberi deo garantnog lista koji želiš da obradiš.'}</Text>
        <FilterBar>
          {WARRANTY_WORKSPACE_OPTIONS.map((option) => (
            <FilterChip
              key={option.value}
              label={option.label}
              active={workspace === option.value}
              onPress={() => setWorkspace(option.value)}
            />
          ))}
        </FilterBar>
      </Card>

      <Card style={styles.card}>
        <View style={styles.headerRow}>
          <View style={styles.headerTitle}>
            <Text style={styles.productName}>
              {warranty.product_name}
            </Text>

            <Text style={styles.meta}>
              {warranty.order.order_number}
              {' · '}
              {warranty.customer.name}
            </Text>
          </View>

          <Text style={styles.status}>
            {warranty.status_label}
          </Text>
        </View>

        <DetailRow
          label="SKU"
          value={warranty.product_sku ?? '—'}
          styles={styles}
        />
        <DetailRow
          label="Količina"
          value={String(warranty.quantity)}
          styles={styles}
        />
        <DetailRow
          label="Telefon"
          value={warranty.customer.phone ?? '—'}
          styles={styles}
        />
        <DetailRow
          label="Adresa"
          value={[
            warranty.customer.address,
            warranty.customer.postal_code,
            warranty.customer.city,
          ].filter(Boolean).join(', ')}
          styles={styles}
        />
        <DetailRow
          label="Pravilo"
          value={warranty.rule?.name ?? 'Snapshot bez aktivnog pravila'}
          styles={styles}
        />
      </Card>

      {workspace === 'details' ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Podaci garantnog lista</Text>
          <DetailRow label="Početak" value={warranty.starts_at ? formatDate(warranty.starts_at) : '—'} styles={styles} />
          <DetailRow label="Važi do" value={warranty.expires_at ? formatDate(warranty.expires_at) : '—'} styles={styles} />
          <DetailRow label="Serijski brojevi" value={warranty.serial_numbers.length > 0 ? warranty.serial_numbers.join(', ') : '—'} styles={styles} />
          <DetailRow label="Uslovi" value={warranty.terms ?? '—'} styles={styles} />
        </Card>
      ) : null}

      {workspace === 'details' && warranty.capabilities.can_update ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>
            Serijski brojevi i rok
          </Text>

          <DateTimeField
            label="Početak"
            mode="date"
            value={startsAt}
            onChangeText={setStartsAt}
            required
          />

          <DateTimeField
            label="Važi do"
            mode="date"
            value={expiresAt}
            onChangeText={setExpiresAt}
            required
          />

          <TextField
            label="Serijski brojevi"
            value={serialNumbers}
            onChangeText={setSerialNumbers}
            multiline
            numberOfLines={5}
            placeholder="Jedan serijski broj po redu"
          />

          <TextField
            label="Uslovi garancije"
            value={terms}
            onChangeText={setTerms}
            multiline
            numberOfLines={6}
          />

          <Button
            loading={updateMutation.isPending}
            onPress={submitUpdate}
          >
            Sačuvaj podatke
          </Button>
        </Card>
      ) : null}

      {workspace === 'overview' ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Rokovi</Text>

        <DetailRow
          label="Početak"
          value={warranty.starts_at
            ? formatDate(warranty.starts_at)
            : '—'}
          styles={styles}
        />

        <DetailRow
          label="Ističe"
          value={warranty.expires_at
            ? formatDate(warranty.expires_at)
            : '—'}
          styles={styles}
        />

        <DetailRow
          label="Trajanje"
          value={durationLabel(warranty)}
          styles={styles}
        />

        <DetailRow
          label="Poslednje održavanje"
          value={warranty.last_maintenance_at
            ? formatDate(warranty.last_maintenance_at)
            : '—'}
          styles={styles}
        />

        <DetailRow
          label="Sledeće održavanje"
          value={warranty.next_maintenance_at
            ? formatDate(warranty.next_maintenance_at)
            : '—'}
          styles={styles}
        />
        </Card>
      ) : null}

      {workspace === 'document' ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Garantni dokument</Text>
          <Text style={styles.meta}>PDF koristi postojeći autentifikovani Bearer download, privatni cache i share tok.</Text>
          <DetailRow label="Broj garancije" value={warranty.warranty_number} styles={styles} />
          <DetailRow label="Pravilo" value={warranty.rule?.name ?? 'Snapshot bez aktivnog pravila'} styles={styles} />
          <DetailRow label="Serijski brojevi" value={warranty.serial_numbers.length > 0 ? warranty.serial_numbers.join(', ') : '—'} styles={styles} />
          <DetailRow label="Uslovi" value={warranty.terms ?? '—'} styles={styles} />
          <Button
            variant="secondary"
            loading={openingPdf}
            onPress={() => void openPdf(warranty.id, warranty.warranty_number)}
          >
            Otvori / podeli PDF
          </Button>
        </Card>
      ) : null}

      {workspace === 'maintenance' ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>
            Preventivno održavanje
          </Text>

        {warranty.maintenance_records.length === 0 ? (
          <Text style={styles.meta}>
            Za ovu garanciju nije definisano preventivno održavanje.
          </Text>
        ) : (
          warranty.maintenance_records.map((record) => (
            <MaintenanceCard
              key={record.id}
              record={record}
              busy={maintenanceMutation.isPending}
              styles={styles}
              onSchedule={() => {
                openMaintenanceForm(record, 'schedule');
              }}
              onComplete={() => {
                openMaintenanceForm(record, 'complete');
              }}
            />
          ))
        )}
        </Card>
      ) : null}

      {workspace === 'maintenance' && maintenanceRecordId !== null && maintenanceMode ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>
            {maintenanceMode === 'schedule'
              ? 'Zakaži održavanje'
              : 'Evidentiraj završetak'}
          </Text>

          <DateTimeField
            label={maintenanceMode === 'schedule'
              ? 'Termin'
              : 'Završeno'}
            value={maintenanceDateTime}
            onChangeText={setMaintenanceDateTime}
            required
          />

          <TextField
            label={maintenanceMode === 'schedule'
              ? 'Referenca'
              : 'Servisni nalog'}
            value={serviceReference}
            onChangeText={setServiceReference}
          />

          {maintenanceMode === 'complete' ? (
            <TextField
              label="Rezultat održavanja"
              value={maintenanceResult}
              onChangeText={setMaintenanceResult}
              multiline
              numberOfLines={4}
            />
          ) : null}

          <TextField
            label="Interna napomena"
            value={maintenanceNotes}
            onChangeText={setMaintenanceNotes}
            multiline
            numberOfLines={4}
          />

          <View style={styles.actionsRow}>
            <Button
              variant="secondary"
              onPress={closeMaintenanceForm}
            >
              Odustani
            </Button>

            <Button
              loading={maintenanceMutation.isPending}
              onPress={submitMaintenance}
            >
              Sačuvaj
            </Button>
          </View>
        </Card>
      ) : null}

      {workspace === 'void' && warranty.void ? (
        <Card style={styles.voidCard}>
          <Text style={styles.sectionTitle}>
            Garancija je poništena
          </Text>

          <DetailRow
            label="Razlog"
            value={warranty.void.reason ?? '—'}
            styles={styles}
          />

          <DetailRow
            label="Datum"
            value={warranty.void.voided_at
              ? formatDate(warranty.void.voided_at)
              : '—'}
            styles={styles}
          />

          <DetailRow
            label="Evidentirao"
            value={warranty.void.voided_by_name ?? '—'}
            styles={styles}
          />
        </Card>
      ) : null}

      {workspace === 'void' && warranty.capabilities.can_void ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>
            Poništi garanciju
          </Text>

          <TextField
            label="Razlog poništavanja"
            value={voidReason}
            onChangeText={setVoidReason}
            multiline
            numberOfLines={4}
          />

          <Button
            variant="danger"
            disabled={
              voidReason.trim().length < 5
            }
            loading={voidMutation.isPending}
            onPress={() => setConfirmVoid(true)}
          >
            Poništi garanciju
          </Button>
        </Card>
      ) : null}

      {workspace === 'void' && !warranty.void && !warranty.capabilities.can_void ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Poništavanje nije dostupno</Text>
          <Text style={styles.meta}>Server capability trenutno ne dozvoljava poništavanje ove garancije.</Text>
        </Card>
      ) : null}

      <ConfirmAction
        visible={confirmVoid}
        title="Poništi garanciju"
        message="Ova akcija čuva audit trag i otkazuje aktivne termine održavanja. Nastaviti?"
        confirmLabel="Poništi garanciju"
        destructive
        busy={voidMutation.isPending}
        onCancel={() => setConfirmVoid(false)}
        onConfirm={() => {
          const reason = voidReason.trim();

          if (reason.length >= 5) {
            voidMutation.mutate(reason);
          }
        }}
      />
    </Screen>
  );
}

function MaintenanceCard({
  record,
  busy,
  styles,
  onSchedule,
  onComplete,
}: {
  record: AdminWarrantyMaintenanceRecord;
  busy: boolean;
  styles: ReturnType<typeof createStyles>;
  onSchedule: () => void;
  onComplete: () => void;
}) {
  return (
    <View style={styles.maintenanceCard}>
      <View style={styles.headerRow}>
        <Text style={styles.maintenanceTitle}>
          {record.due_at
            ? formatDate(record.due_at)
            : 'Termin bez datuma'}
        </Text>

        <Text style={styles.status}>
          {record.status_label}
        </Text>
      </View>

      {record.scheduled_at ? (
        <Text style={styles.meta}>
          Zakazano: {formatDate(record.scheduled_at)}
        </Text>
      ) : null}

      {record.completed_at ? (
        <Text style={styles.meta}>
          Završeno: {formatDate(record.completed_at)}
        </Text>
      ) : null}

      {record.service_reference ? (
        <Text style={styles.meta}>
          Referenca: {record.service_reference}
        </Text>
      ) : null}

      {record.result ? (
        <Text style={styles.meta}>
          Rezultat: {record.result}
        </Text>
      ) : null}

      {record.notes ? (
        <Text style={styles.meta}>
          Napomena: {record.notes}
        </Text>
      ) : null}

      {(record.can_schedule || record.can_complete) ? (
        <View style={styles.actionsRow}>
          {record.can_schedule ? (
            <Button
              variant="secondary"
              onPress={onSchedule}
            >
              Zakaži
            </Button>
          ) : null}

          {record.can_complete ? (
            <Button
              onPress={onComplete}
            >
              Evidentiraj završetak
            </Button>
          ) : null}
        </View>
      ) : null}
    </View>
  );
}

function durationLabel(warranty: AdminWarranty): string {
  const parts: string[] = [];

  if (warranty.duration_months) {
    parts.push(`${warranty.duration_months} meseci`);
  }

  if (warranty.duration_days) {
    parts.push(`${warranty.duration_days} dana`);
  }

  return parts.length > 0
    ? parts.join(' + ')
    : '—';
}

function DetailRow({
  label,
  value,
  styles,
}: {
  label: string;
  value: string;
  styles: ReturnType<typeof createStyles>;
}) {
  return (
    <View style={styles.detailRow}>
      <Text style={styles.label}>{label}</Text>
      <Text style={styles.value}>{value}</Text>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: {
      paddingBottom: 140,
      gap: spacing.lg,
    },
    back: {
      ...typography.label,
      color: theme.primary,
      paddingVertical: spacing.sm,
    },
    workspaceCard: {
      gap: spacing.md,
      borderColor: theme.primary,
    },
    card: {
      gap: spacing.md,
    },
    voidCard: {
      gap: spacing.md,
      borderColor: theme.danger,
      borderWidth: 1,
    },
    headerRow: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      gap: spacing.md,
    },
    headerTitle: {
      flex: 1,
      gap: 2,
    },
    productName: {
      ...typography.h3,
      color: theme.ink,
    },
    status: {
      ...typography.small,
      color: theme.primary,
      fontWeight: '800',
    },
    meta: {
      ...typography.small,
      color: theme.muted,
    },
    sectionTitle: {
      ...typography.h3,
      color: theme.ink,
    },
    detailRow: {
      gap: 2,
    },
    label: {
      ...typography.small,
      color: theme.muted,
    },
    value: {
      ...typography.body,
      color: theme.ink,
    },
    maintenanceCard: {
      gap: spacing.sm,
      paddingVertical: spacing.md,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: theme.line,
    },
    maintenanceTitle: {
      ...typography.label,
      color: theme.ink,
      flex: 1,
    },
    actionsRow: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: spacing.sm,
    },
  });
}
