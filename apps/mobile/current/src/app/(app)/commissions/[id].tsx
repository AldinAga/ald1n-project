import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Pill, type PillTone } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { api } from '@/lib/api/endpoints';
import { formatDate, formatMoney } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';
import type { CommissionStatus } from '@/types/api';

function statusTone(status: CommissionStatus): PillTone {
  if (status === 'paid') return 'success';
  if (status === 'cancelled') return 'danger';
  if (status === 'approved') return 'info';
  return 'warning';
}

export default function CommissionDetailScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);
  const { id } = useLocalSearchParams<{ id: string }>();
  const commissionId = Number(id);
  const { can, hasFeature } = useAuth();
  const allowed = can('commissions.view_own');
  const validId = Number.isInteger(commissionId) && commissionId > 0;

  const query = useQuery({
    queryKey: ['commissions', commissionId],
    queryFn: () => api.commissions.detail(commissionId),
    enabled: allowed && validId,
  });

  if (!allowed) return <UnavailableState title="Provizija nije dostupna" />;
  if (!validId) return <ErrorState error={new Error('Neispravan identifikator provizije.')} />;
  if (query.isLoading) return <LoadingState label="Učitavanje provizije…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const commission = query.data;

  return (
    <Screen>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Nazad na provizije</Text>
      </Pressable>

      <View style={styles.heading}>
        <View style={styles.headingCopy}>
          <Text style={styles.eyebrow}>PROVIZIJA</Text>
          <Text style={styles.title}>{commission.order.order_number}</Text>
        </View>
        <Pill tone={statusTone(commission.status)}>{commission.status_label}</Pill>
      </View>

      <Card style={styles.heroCard}>
        <Text style={styles.amount}>{formatMoney(commission.total_eur, 'EUR')}</Text>
        <Text style={styles.meta}>Obračun za porudžbinu {commission.order.order_number}</Text>
        <View style={styles.rule} />
        <Info label="Odgovorno lice" value={commission.responsible_name} />
        <Info label="Status" value={commission.status_label} />
        <Info
          label="Poslednja promena"
          value={formatDate(commission.status_updated_at ?? commission.updated_at ?? commission.created_at, true)}
        />

        {hasFeature('orders') ? (
          <Button
            variant="secondary"
            onPress={() => router.push({ pathname: '/order/[id]', params: { id: String(commission.order.id) } })}
          >
            Otvori porudžbinu
          </Button>
        ) : null}
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>Napomena uz status</Text>
        <Text style={styles.bodyText}>{commission.status_note?.trim() || 'Nema dodatne napomene.'}</Text>
      </Card>

      {commission.payment ? (
        <Card>
          <Text style={styles.sectionTitle}>Isplata</Text>
          <Info label="Način isplate" value={commission.payment.method_label || '—'} />
          <Info label="Referenca" value={commission.payment.reference || '—'} />
          <Info
            label="Datum isplate"
            value={commission.payment.paid_at ? formatDate(commission.payment.paid_at, true) : '—'}
          />
        </Card>
      ) : (
        <Card muted>
          <Text style={styles.sectionTitle}>Isplata</Text>
          <Text style={styles.muted}>Podaci o isplati će biti prikazani kada provizija dobije status „Isplaćena“.</Text>
        </Card>
      )}

      <Card muted>
        <Text style={styles.sectionTitle}>Evidencija</Text>
        <Info label="Kreirano" value={formatDate(commission.created_at, true)} />
        <Info label="Ažurirano" value={formatDate(commission.updated_at, true)} />
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
    amount: {
      ...typography.h1,
      color: theme.ink,
    },
    meta: {
      ...typography.small,
      color: theme.muted,
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
    bodyText: {
      ...typography.body,
      color: theme.ink,
    },
    muted: {
      ...typography.body,
      color: theme.muted,
    },
    rule: {
      height: 1,
      backgroundColor: theme.line,
    },
  });
}
