import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Pill } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { hasAdminAccess } from '@/features/admin/admin-access';
import { apiAdmin } from '@/features/admin/admin-api';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { useAuth } from '@/features/auth/auth-provider';
import { useThemedStyles } from '@/theme/app-theme';

// MOBILE_V0_7_ADMIN_HUB_COMMISSION_PRIORITY
export default function AdminIndexScreen() {
  const styles = useThemedStyles(createStyles);
  const { bootstrap, can } = useAuth();

  const allowed = hasAdminAccess({
    permissions: bootstrap?.permissions ?? [],
    roleSlug: bootstrap?.user.role?.slug,
  });

  const query = useQuery({
    queryKey: adminQueryKeys.foundation(),
    queryFn: apiAdmin.foundation,
    enabled: allowed,
    staleTime: 60_000,
  });

  if (!allowed) {
    return <UnavailableState title="Administracija nije dostupna" />;
  }

  if (query.isLoading) {
    return <LoadingState label="Učitavanje admin prostora…" />;
  }

  if (query.isError) {
    return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  }

  const foundation = query.data;

  if (!foundation) {
    return <UnavailableState title="Admin podaci nisu dostupni" />;
  }

  const modules = foundation.modules.filter((module) => module.enabled);

  return (
    <Screen>
      <PageHeader
        title="Administracija"
        eyebrow="Ald1n CMS · Admin"
        name={bootstrap?.user.name}
      />

      <Card style={styles.hero}>
        <Pill tone="primary">ADMIN RADNI PROSTOR</Pill>
        <Text style={styles.heroTitle}>
          Centralizovan pristup administratorskim modulima.
        </Text>
        <Text style={styles.heroCopy}>
          Dozvole, API namespace i query-key konvencije dele isti foundation.
        </Text>
        <Text style={styles.metric}>
          {foundation.enabled_module_count} dostupnih domena
        </Text>
      </Card>

      <View style={styles.quickActions}>
        {can('catalog.manage_products') ? (
          <Button
            variant="secondary"
            onPress={() => router.push('/admin/catalog/create')}
          >
            Dodaj artikal
          </Button>
        ) : null}

        {can('commissions.manage') ? (
          <Button
            variant="secondary"
            onPress={() => router.push('/admin/commissions')}
          >
            Provizije
          </Button>
        ) : null}

        {can('orders.manage') ? (
          <Button
            variant="secondary"
            onPress={() => router.push('/admin/orders')}
          >
            Dodeljene porudžbine
          </Button>
        ) : null}
        {can('after_sales.manage') ? (
          <Button
            variant="secondary"
            onPress={() => router.push('/admin/after-sales')}
          >
            Postprodaja admin
          </Button>
        ) : null}
        {can('field_operations.view') ? (
          <Button
            variant="secondary"
            onPress={() => router.push('/admin/field-operations')}
          >
            Terenske operacije
          </Button>
        ) : null}
        {can('receivables.manage') ? (
          <Button
            variant="secondary"
            onPress={() => router.push('/admin/receivables')}
          >
            Potraživanja
          </Button>
        ) : null}
        {(can('service_parts.view') || can('service_parts.manage') || can('service_parts.procurement')) ? (
          <Button
            variant="secondary"
            onPress={() => router.push('/admin/service-parts')}
          >
            Servisni lager
          </Button>
        ) : null}
        {(can('stock.view') || can('stock.adjust') || can('inventory.receive') || can('inventory.count') || can('inventory.export')) ? (
          <Button
            variant="secondary"
            onPress={() => router.push('/admin/inventory')}
          >
            Lager
          </Button>
        ) : null}

        

        {can('warranties.manage') ? (
          <Button
            variant="secondary"
            onPress={() => router.push('/admin/warranties')}
          >
            Garancije admin
          </Button>
        ) : null}

        {can('reports.view') ? (
          <Button
            variant="secondary"
            onPress={() => router.push('/admin/reports')}
          >
            Izveštaji admin
          </Button>
        ) : null}

        {can('system.health') ? (
          <Button
            variant="secondary"
            onPress={() => router.push('/admin/system-health')}
          >
            Zdravlje sistema
          </Button>
        ) : null}

        {can('security.view') ? (
          <Button
            variant="secondary"
            onPress={() => router.push('/admin/audit')}
          >
            Audit i bezbednost
          </Button>
        ) : null}
      </View>

      <View style={styles.sectionHead}>
        <Text style={styles.sectionTitle}>Admin moduli</Text>
        <Text style={styles.sectionMeta}>P3 se uključuje po domenima</Text>
      </View>

      <View style={styles.modules}>
        {modules.map((module) => (
          <Card key={module.key} style={styles.moduleCard}>
            <View style={styles.moduleHead}>
              <Text style={styles.moduleTitle}>{module.label}</Text>
              <Pill tone="success">DOZVOLJENO</Pill>
            </View>
            <Text style={styles.moduleCopy}>{module.description}</Text>
            <Text style={styles.moduleMeta}>
              Foundation spreman · domen funkcije se uključuju po P3 batch-evima.
            </Text>
          </Card>
        ))}
      </View>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    hero: {
      gap: spacing.md,
    },
    heroTitle: {
      ...typography.h2,
      color: theme.ink,
    },
    heroCopy: {
      ...typography.body,
      color: theme.muted,
    },
    metric: {
      ...typography.label,
      color: theme.primary,
    },
    quickActions: {
      gap: spacing.sm,
    },
    sectionHead: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: spacing.md,
    },
    sectionTitle: {
      ...typography.h2,
      color: theme.ink,
      flex: 1,
    },
    sectionMeta: {
      ...typography.small,
      color: theme.muted,
    },
    modules: {
      gap: spacing.md,
    },
    moduleCard: {
      gap: spacing.sm,
    },
    moduleHead: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: spacing.sm,
    },
    moduleTitle: {
      ...typography.h3,
      color: theme.ink,
      flex: 1,
    },
    moduleCopy: {
      ...typography.body,
      color: theme.muted,
    },
    moduleMeta: {
      ...typography.small,
      color: theme.muted,
    },
  });
}
