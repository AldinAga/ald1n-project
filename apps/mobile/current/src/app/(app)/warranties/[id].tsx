import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { Pill, type PillTone } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { openWarrantyPdf } from '@/features/warranties/warranty-pdf';
import { api } from '@/lib/api/endpoints';
import { formatDate } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';
import type { WarrantyMaintenanceStatus, WarrantyStatus } from '@/types/api';

function warrantyTone(status: WarrantyStatus): PillTone {
  if (status === 'active') return 'success';
  if (status === 'expired') return 'warning';
  return 'danger';
}

function maintenanceTone(status: WarrantyMaintenanceStatus): PillTone {
  if (status === 'completed') return 'success';
  if (status === 'cancelled') return 'neutral';
  if (status === 'scheduled') return 'info';
  return 'warning';
}

function formatDuration(months: number | null, days: number | null): string {
  const parts: string[] = [];
  if (months) parts.push(`${months} meseci`);
  if (days) parts.push(`${days} dana`);
  return parts.length > 0 ? parts.join(' + ') : 'Nije definisano';
}

export default function WarrantyDetailScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);
  const feedback = useAppFeedback();
  const [openingPdf, setOpeningPdf] = useState(false);
  const { id } = useLocalSearchParams<{ id: string }>();
  const warrantyId = Number(id);
  const { can, hasFeature } = useAuth();
  const allowed = can('warranties.view_own');
  const validId = Number.isInteger(warrantyId) && warrantyId > 0;
  const query = useQuery({
    queryKey: ['warranties', warrantyId],
    queryFn: () => api.warranties.detail(warrantyId),
    enabled: allowed && validId,
  });

  if (!allowed) return <UnavailableState title="Garancija nije dostupna" />;
  if (!validId) return <ErrorState error={new Error('Neispravan identifikator garancije.')} />;
  if (query.isLoading) return <LoadingState label="Učitavanje garancije…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const warranty = query.data;

  const openPdf = async () => {
    if (openingPdf) return;
    setOpeningPdf(true);

    try {
      await openWarrantyPdf(warranty.id, warranty.warranty_number);
    } catch (error) {
      feedback.notify({
        tone: 'danger',
        title: 'Garantni list nije moguće otvoriti',
        message: error instanceof Error && error.message
          ? error.message
          : 'Pokušaj ponovo za nekoliko trenutaka.',
        durationMs: 5200,
      });
    } finally {
      setOpeningPdf(false);
    }
  };

  return (
    <Screen>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Nazad na garancije</Text>
      </Pressable>

      <View style={styles.heading}>
        <View style={styles.headingCopy}>
          <Text style={styles.eyebrow}>GARANTNI LIST</Text>
          <Text style={styles.title}>{warranty.warranty_number}</Text>
        </View>
        <Pill tone={warrantyTone(warranty.status)}>{warranty.status_label}</Pill>
      </View>

      <Card style={styles.heroCard}>
        <Text style={styles.productName}>{warranty.product_name}</Text>
        {warranty.product_sku ? <Text style={styles.meta}>SKU {warranty.product_sku}</Text> : null}
        <View style={styles.rule} />
        <Info label="Porudžbina" value={warranty.order.order_number} />
        <Info label="Količina" value={`${warranty.quantity} kom.`} />
        <Info
          label="Serijski brojevi"
          value={warranty.serial_numbers.length > 0 ? warranty.serial_numbers.join(', ') : 'Nisu evidentirani'}
        />
        <Info label="Početak" value={warranty.starts_at ? formatDate(warranty.starts_at) : '—'} />
        <Info label="Važi do" value={warranty.expires_at ? formatDate(warranty.expires_at) : '—'} />

        <Button
          onPress={() => void openPdf()}
          loading={openingPdf}
        >
          Otvori / podeli PDF
        </Button>
        {hasFeature('orders') ? (
          <Button
            variant="secondary"
            onPress={() => router.push({ pathname: '/order/[id]', params: { id: String(warranty.order.id) } })}
          >
            Otvori porudžbinu
          </Button>
        ) : null}
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>Sažetak garancije</Text>
        <Info label="Trajanje" value={formatDuration(warranty.duration_months, warranty.duration_days)} />
        <Info
          label="Interval održavanja"
          value={warranty.maintenance_interval_months ? `${warranty.maintenance_interval_months} meseci` : 'Nije definisano'}
        />
        <Info
          label="Poslednje održavanje"
          value={warranty.last_maintenance_at ? formatDate(warranty.last_maintenance_at) : '—'}
        />
        <Info
          label="Sledeće održavanje"
          value={warranty.next_maintenance_at ? formatDate(warranty.next_maintenance_at) : '—'}
        />
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>Uslovi</Text>
        <Text style={styles.bodyText}>{warranty.terms?.trim() || 'Nisu uneti posebni uslovi.'}</Text>
      </Card>

      {warranty.status === 'void' ? (
        <View style={styles.voidNotice}>
          <Text style={styles.voidTitle}>Garancija je poništena</Text>
          <Text style={styles.voidCopy}>{warranty.void_reason?.trim() || 'Razlog nije naveden.'}</Text>
        </View>
      ) : null}

      <Card>
        <Text style={styles.sectionTitle}>Preventivno održavanje</Text>
        {warranty.maintenance_records.length > 0 ? warranty.maintenance_records.map((record) => (
          <View key={record.id} style={styles.maintenanceBlock}>
            <View style={styles.rowBetween}>
              <View style={styles.flexOne}>
                <Text style={styles.maintenanceDate}>
                  {record.due_at ? formatDate(record.due_at) : 'Datum nije definisan'}
                </Text>
                <Text style={styles.meta}>Planirani termin održavanja</Text>
              </View>
              <Pill tone={maintenanceTone(record.status)}>{record.status_label}</Pill>
            </View>

            {record.scheduled_at ? (
              <Text style={styles.meta}>Zakazano: {formatDate(record.scheduled_at, true)}</Text>
            ) : null}
            {record.completed_at ? (
              <Text style={styles.meta}>Završeno: {formatDate(record.completed_at, true)}</Text>
            ) : null}
            {record.result ? <Text style={styles.result}>{record.result}</Text> : null}
          </View>
        )) : (
          <Text style={styles.muted}>Preventivno održavanje nije definisano.</Text>
        )}
      </Card>
    </Screen>
  );
}

function Info({ label, value }: { label: string; value: string }) {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);

  return (
    <View style={styles.info}>
      <Text style={styles.label}>{label}</Text>
      <Text style={styles.value}>{value}</Text>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    back: {
      ...typography.label,
      color: theme.primary,
      paddingVertical: spacing.sm,
    },
    heading: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    headingCopy: {
      flex: 1,
    },
    eyebrow: {
      ...typography.small,
      color: theme.primary,
      letterSpacing: 1.2,
      fontWeight: '800',
    },
    title: {
      ...typography.h1,
      color: theme.ink,
      marginTop: 3,
    },
    heroCard: {
      gap: spacing.md,
    },
    productName: {
      ...typography.h3,
      color: theme.ink,
    },
    sectionTitle: {
      ...typography.h3,
      color: theme.ink,
      marginBottom: spacing.md,
    },
    info: {
      minHeight: 48,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
      borderTopWidth: 1,
      borderTopColor: theme.line,
    },
    label: {
      ...typography.small,
      color: theme.muted,
      flex: 1,
    },
    value: {
      ...typography.label,
      color: theme.ink,
      flex: 1,
      textAlign: 'right',
    },
    rule: {
      height: 1,
      backgroundColor: theme.line,
    },
    bodyText: {
      ...typography.body,
      color: theme.ink,
    },
    meta: {
      ...typography.small,
      color: theme.muted,
    },
    muted: {
      ...typography.body,
      color: theme.muted,
    },
    voidNotice: {
      padding: spacing.lg,
      backgroundColor: theme.dangerSoft,
      gap: spacing.xs,
    },
    voidTitle: {
      ...typography.h3,
      color: theme.danger,
    },
    voidCopy: {
      ...typography.body,
      color: theme.danger,
    },
    maintenanceBlock: {
      gap: spacing.sm,
      paddingVertical: spacing.md,
      borderTopWidth: 1,
      borderTopColor: theme.line,
    },
    rowBetween: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    flexOne: {
      flex: 1,
    },
    maintenanceDate: {
      ...typography.label,
      color: theme.ink,
    },
    result: {
      ...typography.body,
      color: theme.ink,
    },
  });
}
